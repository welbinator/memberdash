<?php
/**
 * Stripe plan gateway.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Model
 */

use StellarWP\Memberdash\Stripe;

/**
 * Stripe Gateway Integration for repeated payments (payment plans).
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since 1.0.0
 */
class MS_Gateway_Stripeplan extends MS_Gateway {

	const ID = 'stripeplan';

	/**
	 * Gateway singleton instance.
	 *
	 * @since 1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Stripe test secret key (sandbox).
	 *
	 * @see https://support.stripe.com/questions/where-do-i-find-my-api-keys
	 *
	 * @since 1.0.0
	 * @var string $test_secret_key
	 */
	protected $test_secret_key = false;

	/**
	 * Stripe Secret key (live).
	 *
	 * @since 1.0.0
	 * @var string $secret_key
	 */
	protected $secret_key = false;

	/**
	 * Stripe test publishable key (sandbox).
	 *
	 * @since 1.0.0
	 * @var string $test_publishable_key
	 */
	protected $test_publishable_key = false;

	/**
	 * Stripe publishable key (live).
	 *
	 * @since 1.0.0
	 * @var string $publishable_key
	 */
	protected $publishable_key = false;

	/**
	 * Stripe Vendor Logo.
	 *
	 * @since 1.0.0
	 * @var string $vendor_logo
	 */
	protected $vendor_logo = '';

	/**
	 * Instance of the shared stripe API integration
	 *
	 * @since 1.0.0
	 * @var MS_Gateway_Stripe_Api $api
	 */
	protected $_api;

	/**
	 * Initialize the object.
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		parent::after_load();
		$this->_api = MS_Factory::load( 'MS_Gateway_Stripe_Api' );

		// use single configuration.
		$single_gateway = MS_Factory::load( 'MS_Gateway_Stripe' );
		$stripe_keys    = $single_gateway->get_stripe_keys_array();

		$this->test_secret_key      = $stripe_keys['test_secret_key'];
		$this->secret_key           = $stripe_keys['secret_key'];
		$this->test_publishable_key = $stripe_keys['test_publishable_key'];
		$this->publishable_key      = $stripe_keys['publishable_key'];

		$this->id                        = self::ID;
		$this->name                      = __( 'Stripe Subscriptions Gateway', 'memberdash' );
		$this->group                     = 'Stripe';
		$this->manual_payment            = false; // Recurring charged automatically.
		$this->pro_rate                  = true;
		$this->unsupported_payment_types = array(
			MS_Model_Membership::PAYMENT_TYPE_PERMANENT,
			MS_Model_Membership::PAYMENT_TYPE_FINITE,
			MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE,
		);

		// Syncs the gateway with the parent gateway (stripe single gateway).

		$this->add_action(
			'ms_gateway_toggle_stripe',
			'sync_gateway'
		);

		$this->add_action(
			'ms_gateway_changed_stripe',
			'sync_gateway'
		);

		// Update a single payment plan.
		$this->add_action(
			'ms_saved_MS_Model_Membership',
			'update_stripe_data_membership'
		);

		// Update a single coupon.
		$this->add_action(
			'ms_saved_MS_Addon_Coupon_Model',
			'update_stripe_data_coupon'
		);

		// Delete Coupon.
		$this->add_action(
			'ms_deleted_MS_Addon_Coupon_Model',
			'delete_stripe_coupon',
			10,
			3
		);

		$this->add_filter(
			'ms_model_pages_get_ms_page_url',
			'ms_model_pages_get_ms_page_url_cb',
			99,
			4
		);

		$this->add_action(
			'wp_ajax_stripeSubSession',
			'stripeSubSession'
		);
	}

	/**
	 * Syncs the gateway with the single gateway settings.
	 *
	 * @param MS_Gateway_Stripe $single_gateway The single gateway.
	 *
	 * @return void
	 */
	public function sync_gateway( $single_gateway ) {
		$this->active = $single_gateway->active;
		$this->mode   = $single_gateway->mode;
		$this->save();

		if ( $this->active ) {
			$stripe_keys = $single_gateway->get_stripe_keys_array();

			$this->test_secret_key      = $stripe_keys['test_secret_key'];
			$this->secret_key           = $stripe_keys['secret_key'];
			$this->test_publishable_key = $stripe_keys['test_publishable_key'];
			$this->publishable_key      = $stripe_keys['publishable_key'];

			$this->update_stripe_data();
		}
	}

	/**
	 * Create a new Stripe session for a subscription.
	 *
	 * @return void
	 */
	public function stripeSubSession() {
		$sub = $_POST['sub'];

		$subscription = MS_Model_Relationship::get_subscription(
			$sub['_saved_data']['user_id'] ?? '',
			$sub['_saved_data']['membership_id'] ?? ''
		);

		if ( ! empty( $subscription ) ) {
				$this->_api->set_gateway( $this );

				$member = $subscription->get_member();

				$invoice = $subscription->get_next_billable_invoice();

				$membership = $invoice->get_membership();

				$note = 'Stripe Processing';

			try {
				$customer = $this->_api->get_stripe_customer( $member );

				if ( 0. === (float) $invoice->total ) {
					// Free, just process.
					$invoice->changed();
					$note = __( 'No payment for free membership.', 'memberdash' );

					echo wp_json_encode(
						array(
							'msg'    => esc_html( $note ),
							'status' => '0',
						)
					);
					exit();

				} else {
					$plan_id = self::get_the_id( (string) $membership->id, 'plan' );

					$session = $this->_api->createSubscriptionPaymentSession(
						$customer,
						$invoice,
						$membership,
						$plan_id
					);

					echo wp_json_encode(
						array(
							'session_id' => $session->id,
							'status'     => '1',
						)
					);
					exit();
				}
			} catch ( Exception $e ) {
					$note = 'Stripe error: ' . $e->getMessage();
					MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED, $subscription );
			}
		} else {
			$note = __( 'Subscription not found.', 'memberdash' );
		}

		echo wp_json_encode(
			array(
				'msg'    => esc_html( $note ),
				'status' => '0',
			)
		);
		exit();
	}

	/**
	 * Force SSL when Stripe in Live mode
	 *
	 * @since 1.0.0
	 *
	 * @param String $url The modified or raw URL
	 * @param String $page_type Check if this is a membership page
	 * @param Bool   $ssl If SSL enabled or not
	 * @param Int    $site_id The ID of site
	 *
	 * @return String $url Modified or raw URL
	 */
	public function ms_model_pages_get_ms_page_url_cb( $url, $page_type, $ssl, $site_id ) {
		/**
		* Constant MWPS_FORCE_NO_SSL
		*
		* It's needed, if :
		*      - the user has no SSL
		*      - the user has SSL but doesn't want to force
		*      - The user has multiple gateways like Paypal and Stripe and doesn't want to force
		*
		* If the user has SSL certificate, this rule won't work
		*/
		if ( ! defined( 'MWPS_FORCE_NO_SSL' ) ) {
			if ( $this->active && $this->is_live_mode() ) {
				if ( $page_type == MS_Model_Pages::MS_PAGE_MEMBERSHIPS || $page_type == MS_Model_Pages::MS_PAGE_REGISTER ) {
					$url = MS_Helper_Utility::get_ssl_url( $url );
				}
			}
		}

		return $url;
	}

	/**
	 * Creates the external Stripe-ID of the specified item.
	 *
	 * This ID takes the current WordPress Site-URL into account to avoid
	 * collisions when several Membership sites use the same stripe account.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @param string $id   The internal ID.
	 * @param string $type The item type, e.g. 'plan' or 'coupon'.
	 *
	 * @return string The external Stripe-ID.
	 */
	public static function get_the_id( $id, $type = 'item' ) {
		static $Base = null;
		if ( null === $Base ) {
			$Base = get_option( 'site_url' );
		}

		$hash   = strtolower( md5( $Base . $type . $id ) );
		$hash   = mslib3()->convert(
			$hash,
			'0123456789abcdef',
			'0123456789ABCDEFGHIJKLMNOPQRSTUVXXYZabcdefghijklmnopqrstuvxxyz' // cspell:disable-line.
		);
		$result = 'ms-' . $type . '-' . $id . '-' . $hash;
		return $result;
	}



	/**
	 * Checks all Memberships and creates/updates the payment plan on stripe if
	 * the membership changed since the plan was last changed.
	 *
	 * This function is called when the gateway is activated and after a
	 * membership was saved to database.
	 *
	 * @since 1.0.0
	 */
	public function update_stripe_data() {
		if ( ! $this->active ) {
			return false;
		}

		$this->_api->set_gateway( $this );

		// 1. Update all payment plans.
		$memberships = MS_Model_Membership::get_memberships();
		foreach ( $memberships as $membership ) {
			$this->update_stripe_data_membership( $membership, true );
		}

		// 2. Update all coupons (if Add-on is enabled)
		if ( MS_Addon_Coupon::is_active() ) {
			$coupons = MS_Addon_Coupon_Model::get_coupons();
			foreach ( $coupons as $coupon ) {
				$this->update_stripe_data_coupon( $coupon, true );
			}
		}
	}

	/**
	 * Creates or updates a single payment plan on Stripe.
	 *
	 * This function is called when the gateway is activated and after a
	 * membership was saved to database.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Added $force_update parameter.
	 *
	 * @param MS_Model_Membership $membership   The membership.
	 * @param bool                $force_update If true, the plan will be updated even if it was not changed. Default false.
	 *
	 * @return bool True if the plan was updated.
	 */
	public function update_stripe_data_membership( $membership, $force_update = false ) {
		if ( ! $this->active ) {
			return false;
		}

		$this->_api->set_gateway( $this );

		$plan_data = array(
			'id'     => self::get_the_id( (string) $membership->id, 'plan' ),
			'amount' => 0,
		);

		if ( ! $membership->is_free()
			&& $membership->payment_type === MS_Model_Membership::PAYMENT_TYPE_RECURRING
		) {
			// Prepare the plan-data for Stripe.

			$trial_days = null;
			if ( $membership->has_trial() ) {
				$trial_days = MS_Helper_Period::get_period_in_days(
					$membership->trial_period_unit,
					$membership->trial_period_type
				);
			}

			$interval  = 'day';
			$max_count = 365;
			switch ( $membership->pay_cycle_period_type ) {
				case MS_Helper_Period::PERIOD_TYPE_WEEKS:
					$interval  = 'week';
					$max_count = 52;
					break;

				case MS_Helper_Period::PERIOD_TYPE_MONTHS:
					$interval  = 'month';
					$max_count = 12;
					break;

				case MS_Helper_Period::PERIOD_TYPE_YEARS:
					$interval  = 'year';
					$max_count = 1;
					break;
			}

			$interval_count = min(
				$max_count,
				$membership->pay_cycle_period_unit
			);

			$settings                       = MS_Plugin::instance()->settings;
			$plan_data['amount']            = absint( $membership->price * 100 );
			$plan_data['currency']          = $settings->currency;
			$plan_data['product']           = array(
				'name' => $membership->name,
			);
			$plan_data['interval']          = $interval;
			$plan_data['interval_count']    = $interval_count;
			$plan_data['trial_period_days'] = $trial_days;

			// Check if the plan needs to be updated.
			$serialized_data = wp_json_encode( $plan_data );
			$temp_key        = substr( 'ms-stripe-' . $plan_data['id'], 0, 45 );
			$temp_data       = MS_Factory::get_transient( $temp_key );

			if (
				$force_update
				|| $temp_data !== $serialized_data
			) {
				MS_Factory::set_transient(
					$temp_key,
					$serialized_data,
					HOUR_IN_SECONDS
				);

				$this->_api->create_or_update_plan( $plan_data );

				return true;
			}
		}

		return false;
	}

	/**
	 * Creates or updates a single coupon on Stripe.
	 *
	 * This function is called when the gateway is activated and after a
	 * coupon was saved to database.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Addon_Coupon_Model $coupon       The coupon.
	 * @param bool                  $force_update If true, the coupon will be updated even if it was not changed. Default false.
	 *
	 * @return bool True if the coupon was updated.
	 */
	public function update_stripe_data_coupon( $coupon, $force_update = false ) {
		if ( ! $this->active ) {
			return false;
		}
		$this->_api->set_gateway( $this );

		$settings    = MS_Plugin::instance()->settings;
		$duration    = MS_Addon_Coupon_Model::DURATION_ONCE === $coupon->get_duration() ? 'once' : 'forever';
		$percent_off = null;
		$amount_off  = null;

		if ( MS_Addon_Coupon_Model::TYPE_VALUE === $coupon->get_discount_type() ) {
			$amount_off = absint( $coupon->get_discount() * 100 );
		} else {
			$percent_off = $coupon->get_discount();
		}

		$coupon_data = apply_filters(
			'ms_gateway_stripe_coupon_data',
			array(
				'id'          => self::get_the_id( (string) $coupon->get_id(), 'coupon' ),
				'duration'    => $duration,
				'amount_off'  => $amount_off,
				'percent_off' => $percent_off,
				'currency'    => $settings->currency,
			),
			$coupon,
			$settings
		);

		// Check if the plan needs to be updated.
		$serialized_data = wp_json_encode( $coupon_data );
		$temp_key        = substr( 'ms-stripe-' . $coupon_data['id'], 0, 45 );
		$temp_data       = MS_Factory::get_transient( $temp_key );

		if (
			$force_update
			|| $temp_data !== $serialized_data
		) {
			MS_Factory::set_transient(
				$temp_key,
				$serialized_data,
				HOUR_IN_SECONDS
			);

			$this->_api->create_or_update_coupon( $coupon_data );

			return true;
		}

		return false;
	}

	/**
	 * Action when coupon is deleted
	 *
	 * @param MS_Addon_Coupon_Model $coupon - the current coupon
	 * @param bool                  $deleted - if it was deleted
	 * @param int                   $id - the reference ID
	 *
	 * @since 1.1.5
	 */
	public function delete_stripe_coupon( $coupon, $deleted, $id ) {
		if ( ! $this->active ) {
			return false;
		}

		$this->_api->set_gateway( $this );

		$coupon_id = apply_filters(
			'ms_gateway_stripe_coupon_id',
			self::get_the_id( (string) $id, 'coupon' ),
			$id,
			$coupon
		);

		$this->_api->delete_coupon( $coupon_id );
	}

	/**
	 * Validates the webhook event and returns an event data.
	 *
	 * @since 1.0.3
	 *
	 * @return Stripe\Event
	 */
	private function validate_webhook_event_or_fail(): Stripe\Event {
		$payload = file_get_contents( 'php://input' );
		$this->log( $payload );

		if ( empty( $payload ) ) {
			$this->log( 'Empty payload.' );

			wp_send_json_error(
				new WP_Error( 'bad_request', 'Empty JSON body.' ),
				400
			);
		}

		$event_json = json_decode( $payload );

		if ( JSON_ERROR_NONE !== json_last_error() || ! isset( $event_json->id ) ) {
			$this->log( 'Invalid payload.' );

			wp_send_json_error(
				new WP_Error( 'bad_request', 'Invalid JSON.' ),
				400
			);
		}

		// check if the event needs to be processed.

		if ( ! $this->is_processed_event( $event_json->type ) ) {
			$this->log( 'Event not processable.' );

			wp_send_json_success(
				array(
					'message' => 'The event is not processable by MemberDash and was ignored.',
				),
				200
			);
		}

		return Stripe\Event::retrieve( $event_json->id );
	}

	/**
	 * Processes the checkout.session.completed event.
	 *
	 * @since 1.0.3
	 *
	 * @param Stripe\Event $event The event.
	 *
	 * @return string
	 */
	private function process_event_checkout_session_completed( Stripe\Event $event ) {
		if ( ! isset( $event->data->object ) ) {
			$this->log( 'Stripe event is invalid.' );

			wp_send_json_error(
				new WP_Error( 'bad_request', 'Stripe event is invalid.' ),
				400
			);
		}

		/**
		 * Stripe session object.
		 *
		 * @var Stripe\Checkout\Session $stripe_session
		 */
		$stripe_session = $event->data->object;

		$reference = explode( ',', (string) $stripe_session->client_reference_id );

		if ( count( $reference ) !== 2 ) {
			$this->log( 'Invalid reference. Event is not from MemberDash.' );

			wp_send_json_success(
				array(
					'message' => 'Invalid reference. Event is not from MemberDash and was ignored.',
				),
				200
			);
		}

		$user_id       = $reference['1'];
		$membership_id = $reference['0'];

		$subscription = MS_Model_Relationship::get_subscription( $user_id, $membership_id );

		if ( ! $subscription ) {
			$this->log( 'Subscription not found.' );

			wp_send_json_success(
				array(
					'message' => 'Subscription not found. The event was ignored.',
				),
				200
			);
		}

		$membership = $subscription->get_membership();

		if ( $membership->supports_recurring_payments() ) {
			$this->log( 'Subscription is recurring.' );

			wp_send_json_success(
				array(
					'message' => 'Subscription is recurring. The event was ignored.',
				),
				200
			);
		}

		$invoice = $subscription->get_current_invoice();

		if ( $invoice->is_paid() ) {
			$this->log( 'Invoice already paid.' );

			wp_send_json_success(
				array(
					'message' => 'Invoice already paid. The event was ignored.',
				),
				200
			);
		}

		if ( isset( $stripe_session->tax_percent ) ) {
			$invoice->tax_rate = $stripe_session->tax_percent;
			$invoice->save();
		}

		$notes = __( 'Payment successful', 'memberdash' );
		$invoice->add_notes( $notes );

		$invoice->pay_it( self::ID, $stripe_session->invoice );

		if ( defined( 'MS_STRIPE_PLAN_RENEWAL_MAIL' ) && MS_STRIPE_PLAN_RENEWAL_MAIL ) {
			MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_RENEWED, $subscription );
		}

		do_action(
			'ms_gateway_transaction_log',
			self::ID, // gateway ID.
			'handle', // request|process|handle.
			true, // success flag.
			$subscription->id, // subscription ID.
			$invoice->id, // invoice ID.
			$invoice->total, // charged amount.
			$notes, // Descriptive text.
			$stripe_session->invoice // External ID.
		);

		return 'The one-time payment was processed successfully for user ' . $user_id . ' and membership ' . $membership_id . '.';
	}

	/**
	 * Processes the invoice.payment_succeeded event.
	 *
	 * @since 1.0.3
	 *
	 * @param Stripe\Event $event The event.
	 *
	 * @return string
	 */
	private function process_event_invoice_payment_succeeded( Stripe\Event $event ) {
		if ( ! isset( $event->data->object ) ) {
			$this->log( 'Stripe event is invalid.' );

			wp_send_json_error(
				new WP_Error( 'bad_request', 'Stripe event is invalid.' ),
				400
			);
		}

		/**
		 * Stripe invoice object.
		 *
		 * @var Stripe\Invoice $stripe_invoice
		 */
		$stripe_invoice = $event->data->object;

		$email = $stripe_invoice->customer_email;

		if ( ! function_exists( 'get_user_by' ) ) {
			include_once ABSPATH . 'wp-includes/pluggable.php';
		}

		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			$this->log( 'User not found.' );

			wp_send_json_success(
				array(
					'message' => 'User not found. The event was ignored.',
				),
				200
			);
		}

		/**
		 * Member object.
		 *
		 * @var MS_Model_Member $member
		 */
		$member = MS_Factory::load( 'MS_Model_Member', $user->ID );

		if ( ! $member ) {
			$this->log( 'Member not found.' );

			wp_send_json_success(
				array(
					'message' => 'Member not found. The event was ignored.',
				),
				200
			);
		}

		$subscription = $this->_api->get_member_subscription_from_stripe_invoice( $member, $stripe_invoice );

		if ( ! $subscription ) {
			$this->log( 'Subscription not found.' );

			wp_send_json_success(
				array(
					'message' => 'Subscription not found. The event was ignored.',
				),
				200
			);
		}

		$membership = $subscription->get_membership();

		// skip if subscription is not recurring.

		if ( ! $membership->supports_recurring_payments() ) {
			$this->log( 'Subscription is not recurring.' );

			wp_send_json_success(
				array(
					'message' => 'Subscription is not recurring. The event was ignored.',
				),
				200
			);
		}

		$invoice = $subscription->get_current_invoice();

		// Skip if invoice was already processed.

		if ( $subscription->is_payment_processed( $stripe_invoice->id ) ) {
			$this->log( 'Invoice already processed.' );

			wp_send_json_success(
				array(
					'message' => 'Invoice already processed. The event was ignored.',
				),
				200
			);
		}

		if ( $invoice->is_paid() ) {
			$invoice = $subscription->get_next_invoice();
		}

		if ( isset( $stripe_invoice->tax_percent ) ) {
			$invoice->tax_rate = $stripe_invoice->tax_percent;
			$invoice->save();
		}

		$invoice->pay_it( self::ID, $stripe_invoice->id );
		$this->cancel_if_done( $subscription, (string) $stripe_invoice->subscription );

		$notes = __( 'Payment successful', 'memberdash' );
		$invoice->add_notes( $notes );

		if ( defined( 'MS_STRIPE_PLAN_RENEWAL_MAIL' ) && MS_STRIPE_PLAN_RENEWAL_MAIL ) {
			MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_RENEWED, $subscription );
		}

		do_action(
			'ms_gateway_transaction_log',
			self::ID, // gateway ID.
			'handle', // request|process|handle.
			true, // success flag.
			$subscription->id, // subscription ID.
			$invoice->id, // invoice ID.
			$invoice->total, // charged amount.
			$notes, // Descriptive text.
			$stripe_invoice->id // External ID.
		);

		return 'The invoice ' . $invoice->get_id() . ' was paid successfully for user ' . $user->ID . ' and membership ' . $invoice->membership_id . '.';
	}

	/**
	 * Processes the customer.subscription.deleted event.
	 *
	 * @since 1.0.3
	 *
	 * @param Stripe\Event $event The event.
	 *
	 * @return string
	 */
	private function process_event_customer_subscription_deleted( Stripe\Event $event ) {
		if ( ! isset( $event->data->object ) ) {
			$this->log( 'Stripe event is invalid.' );

			wp_send_json_error(
				new WP_Error( 'bad_request', 'Stripe event is invalid.' ),
				400
			);
		}

		/**
		 * Stripe subscription object.
		 *
		 * @var Stripe\Subscription $stripe_subscription
		 */
		$stripe_subscription = $event->data->object;

		$stripe_customer = Stripe\Customer::retrieve( $stripe_subscription->customer );

		$email = $stripe_customer->email;

		if ( ! function_exists( 'get_user_by' ) ) {
			include_once ABSPATH . 'wp-includes/pluggable.php';
		}

		$user = get_user_by( 'email', (string) $email );

		if ( ! $user ) {
			$this->log( 'User not found.' );

			wp_send_json_success(
				array(
					'message' => 'User not found. The event was ignored.',
				),
				200
			);
		}

		/**
		 * Member object.
		 *
		 * @var MS_Model_Member $member
		 */
		$member = MS_Factory::load( 'MS_Model_Member', $user->ID );

		if ( ! $member ) {
			$this->log( 'Member not found.' );

			wp_send_json_success(
				array(
					'message' => 'Member not found. The event was ignored.',
				),
				200
			);
		}

		$subscription = $this->_api->get_member_subscription_from_stripe_subscription( $member, $stripe_subscription );

		if ( ! $subscription ) {
			$this->log( 'Subscription not found.' );

			wp_send_json_success(
				array(
					'message' => 'Subscription not found. The event was ignored.',
				),
				200
			);
		}

		$membership = $subscription->get_membership();
		$member->cancel_membership( $membership->id );

		return 'The subscription was cancelled for user ' . $user->ID . ' and membership ' . $membership->id . '.';
	}

	/**
	 * Processes the invoice.payment_failed event.
	 *
	 * @since 1.0.3
	 *
	 * @param Stripe\Event $event The event.
	 *
	 * @return string
	 */
	private function process_event_invoice_payment_failed( Stripe\Event $event ) {
		if ( ! isset( $event->data->object ) ) {
			$this->log( 'Stripe event is invalid.' );

			wp_send_json_error(
				new WP_Error( 'bad_request', 'Stripe event is invalid.' ),
				400
			);
		}

		/**
		 * Stripe invoice object.
		 *
		 * @var Stripe\Invoice $stripe_invoice
		 */
		$stripe_invoice = $event->data->object;

		$email = $stripe_invoice->customer_email;

		if ( ! function_exists( 'get_user_by' ) ) {
			include_once ABSPATH . 'wp-includes/pluggable.php';
		}

		$user = get_user_by( 'email', (string) $email );

		if ( ! $user ) {
			$this->log( 'User not found.' );

			wp_send_json_success(
				array(
					'message' => 'User not found. The event was ignored.',
				),
				200
			);
		}

		/**
		 * Member object.
		 *
		 * @var MS_Model_Member $member
		 */
		$member = MS_Factory::load( 'MS_Model_Member', $user->ID );

		if ( ! $member ) {
			$this->log( 'Member not found.' );

			wp_send_json_success(
				array(
					'message' => 'Member not found. The event was ignored.',
				),
				200
			);
		}

		$subscription = $this->_api->get_member_subscription_from_stripe_invoice( $member, $stripe_invoice );

		if ( ! $subscription ) {
			$this->log( 'Subscription not found.' );

			wp_send_json_success(
				array(
					'message' => 'Subscription not found. The event was ignored.',
				),
				200
			);
		}

		$membership = $subscription->get_membership();
		$member->cancel_membership( $membership->id );

		return 'The subscription was cancelled for user ' . $user->ID . ' and membership ' . $membership->id . '.';
	}

	/**
	 * Process Stripe WebHook requests
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_webhook() {
		do_action(
			'ms_gateway_handle_stripe_webhook_before',
			$this
		);

		$this->_api->set_gateway( $this );

		try {
			$event          = $this->validate_webhook_event_or_fail();
			$stripe_message = '';

			switch ( $event->type ) {
				case 'checkout.session.completed':
					$stripe_message = $this->process_event_checkout_session_completed( $event );
					break;

				case 'invoice.payment_succeeded':
					$stripe_message = $this->process_event_invoice_payment_succeeded( $event );
					break;

				case 'customer.subscription.deleted':
					$stripe_message = $this->process_event_customer_subscription_deleted( $event );
					break;

				case 'invoice.payment_failed':
					$stripe_message = $this->process_event_invoice_payment_failed( $event );
					break;

				default:
					break;
			}
		} catch ( Exception $e ) {
			$stripe_message = 'An unexpected error occurred while processing the event: ' . $e->getMessage();
			$this->log( $stripe_message );
			$this->log( $e );
			MS_Helper_Debug::debug_log( $stripe_message );
		}

		do_action(
			'ms_gateway_handle_stripe_webhook_after',
			$this
		);

		wp_send_json_success(
			array(
				'message'         => ! empty( $stripe_message ) ? 'The event was processed.' : 'The event was ignored.',
				'additional_data' => $stripe_message,
			),
			200
		);
	}

	/**
	 * Checks if the given event needs to be processed.
	 *
	 * @param string $event The event to check.
	 *
	 * @return bool
	 */
	private function is_processed_event( $event ) {
		$needs_processing = in_array(
			$event,
			array( 'invoice.payment_succeeded', 'customer.subscription.deleted', 'invoice.payment_failed', 'checkout.session.completed' ),
			true
		);

		return apply_filters(
			'ms_gateway_stripeplan_is_processed_event',
			$needs_processing
		);
	}

	/**
	 * Processes purchase action.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 */
	public function process_purchase( $subscription ) {
		// Nothing to do here. Stripe payment is handled by webhook.
		// We need to override it because the base class implementation. We need to investigate this case later and then deprecate it or change the architecture.
	}

	/**
	 * Check if the subscription is still active.
	 * Only used in tests
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 *
	 * @return bool True on success.
	 */
	public function request_payment( $subscription ) {
		if ( defined( 'IS_UNIT_TEST' ) && IS_UNIT_TEST ) {
			$was_paid    = false;
			$note        = '';
			$external_id = '';

			do_action(
				'ms_gateway_stripeplan_request_payment_before',
				$subscription,
				$this
			);
			$this->_api->set_gateway( $this );

			$member  = $subscription->get_member();
			$invoice = $subscription->get_current_invoice();

			if ( ! $invoice->is_paid() ) {
				try {
					$customer = $this->_api->find_customer( $member );

					if ( ! empty( $customer ) ) {
						if ( 0 == $invoice->total ) {
							$invoice->changed();
							$success = true;
							$note    = __( 'No payment required for free membership', 'memberdash' );
						} else {
							// Get or create the subscription.
							$stripe_subscription = $this->_api->subscribe(
								$customer,
								$invoice
							);
							$external_id         = $stripe_subscription->id;

							$note = $this->get_description_for_sub( $stripe_subscription );

							if ( 'active' === $stripe_subscription->status || 'trialing' === $stripe_subscription->status ) {
								$was_paid = true;
								$invoice->pay_it( self::ID, $external_id );
								$this->cancel_if_done( $subscription, $stripe_subscription->id );
							}
						}
					} else {
						MS_Helper_Debug::debug_log( "Stripe customer is empty for user $member->username" );
					}
				} catch ( Exception $e ) {
					$note = 'Stripe error: ' . $e->getMessage();
					MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED, $subscription );
					MS_Helper_Debug::debug_log( $note );
				}
			} else {
				// Invoice was already paid earlier.
				$was_paid = true;
			}

			do_action(
				'ms_gateway_stripeplan_request_payment_after',
				$subscription,
				$was_paid,
				$this
			);

			do_action(
				'ms_gateway_transaction_log',
				self::ID, // gateway ID
				'request', // request|process|handle
				$was_paid, // success flag
				$subscription->id, // subscription ID
				$invoice->id, // invoice ID
				$invoice->total, // charged amount
				$note, // Descriptive text
				$external_id // External ID
			);

			return $was_paid;
		} else {
			do_action(
				'ms_gateway_request_payment',
				$subscription,
				$this
			);

			// Default to "Payment successful"
			return true;
		}
	}

	/**
	 * Returns a description for the specified stripe subscription.
	 * Also populates some $_POST fields to store additional details in the
	 * transaction logs.
	 *
	 * @since 1.0.0
	 *
	 * @param  Stripe\Subscription $stripe_sub The Stripe subscription object.
	 *
	 * @return string
	 */
	protected function get_description_for_sub( $stripe_sub ) {
		$note = '';

		switch ( $stripe_sub->status ) {
			case 'trialing':
				$note = __( 'Stripe subscription is in trial period', 'memberdash' );
				break;
			case 'active':
				$note = __( 'Payment successful', 'memberdash' );
				break;

			case 'past_due':
				$note = __( 'Stripe payment failed (payment is past due)', 'memberdash' );
				break;

			case 'canceled':
				$note = __( 'Stripe subscription canceled', 'memberdash' );
				break;

			case 'unpaid':
				$note = __( 'Payment failed, retry-attempts exhausted', 'memberdash' );
				break;

			default:
				$note = sprintf(
				// translators: Subscription status.
					__( 'Stripe subscription is "%s"', 'memberdash' ),
					$stripe_sub->status
				);
				break;
		}

		$_POST['API Response: id']                   = $stripe_sub->id;
		$_POST['API Response: status']               = $stripe_sub->status;
		$_POST['API Response: canceled_at']          = $stripe_sub->canceled_at;
		$_POST['API Response: current_period_start'] = $stripe_sub->current_period_start;
		$_POST['API Response: current_period_end']   = $stripe_sub->current_period_end;
		$_POST['API Response: ended_at']             = $stripe_sub->ended_at;

		return $note;
	}

	/**
	 * Checks if a subscription has reached the maximum pay cycle repetitions.
	 * If the last pay cycle was paid then the subscription is cancelled.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $subscription           The subscription.
	 * @param string                $stripe_subscription_id The Stripe subscription ID.
	 *
	 * @return void
	 */
	protected function cancel_if_done( $subscription, $stripe_subscription_id ) {
		$membership = $subscription->get_membership();

		if ( $membership->pay_cycle_repetitions < 1 ) {
			return;
		}

		$payments = $subscription->get_payments();
		if ( count( $payments ) < $membership->pay_cycle_repetitions ) {
			return;
		}

		$subscription = Stripe\Subscription::update(
			$stripe_subscription_id,
			array(
				'cancel_at_period_end' => true,
			)
		);
	}

	/**
	 * When a member cancels a subscription we need to notify Stripe to also
	 * cancel the Stripe subscription.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 */
	public function cancel_membership( $subscription ) {
		parent::cancel_membership( $subscription );
		$this->_api->set_gateway( $this );

		$customer   = $this->_api->find_customer( $subscription->get_member() );
		$membership = $subscription->get_membership();
		$stripe_sub = false;

		if ( $customer ) {
			$stripe_sub = $this->_api->get_subscription(
				$customer,
				$membership
			);
		}

		if ( $stripe_sub ) {
			$subscription = Stripe\Subscription::update(
				$stripe_sub->id,
				array(
					'cancel_at_period_end' => true,
				)
			);
		}
	}

	/**
	 * Get Stripe publishable key.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @return string The Stripe API publishable key.
	 */
	public function get_publishable_key() {
		$publishable_key = null;

		if ( $this->is_live_mode() ) {
			$publishable_key = $this->publishable_key;
		} else {
			$publishable_key = $this->test_publishable_key;
		}

		return apply_filters(
			'ms_gateway_stripeplan_get_publishable_key',
			$publishable_key
		);
	}

	/**
	 * Get Stripe secret key.
	 *
	 * @since 1.0.0
	 * @internal The secret key should not be used outside this object!
	 *
	 * @return string The Stripe API secret key.
	 */
	public function get_secret_key() {
		$secret_key = null;

		if ( $this->is_live_mode() ) {
			$secret_key = $this->secret_key;
		} else {
			$secret_key = $this->test_secret_key;
		}

		return apply_filters(
			'ms_gateway_stripeplan_get_secret_key',
			$secret_key
		);
	}

	/**
	 * Get Stripe Vendor Logo.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @return string The Stripe Vendor Logo.
	 */
	public function get_vendor_logo() {
		$vendor_logo = null;

		$vendor_logo = $this->vendor_logo;

		return apply_filters(
			'ms_gateway_stripe_get_vendor_logo',
			$vendor_logo
		);
	}

	/**
	 * Verify required fields.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @return boolean True if configured.
	 */
	public function is_configured() {
		$key_pub = $this->get_publishable_key();
		$key_sec = $this->get_secret_key();

		$is_configured = ! ( empty( $key_pub ) || empty( $key_sec ) );

		return apply_filters(
			'ms_gateway_stripeplan_is_configured',
			$is_configured
		);
	}

	/**
	 * Auto-update some fields of the _api instance if required.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param string $key Field name.
	 * @param mixed  $value Field value.
	 */
	public function __set( $key, $value ) {
		switch ( $key ) {
			case 'test_secret_key':
			case 'test_publishable_key':
			case 'secret_key':
			case 'publishable_key':
				$this->_api->$key = $value;
				break;
		}

		if ( property_exists( $this, $key ) ) {
			$this->$key = $value;
		}
	}

}
