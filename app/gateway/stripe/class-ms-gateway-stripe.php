<?php
/**
 * Stripe Gateway Integration.
 *
 * @package MemberDash
 * @subpackage Model
 */

/**
 * Stripe Gateway Integration.
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since 1.0.0
 */
class MS_Gateway_Stripe extends MS_Gateway {

	const ID = 'stripe';

	const CONNECT_SERVER_URL = 'https://connect.learndash.com/memberdash/stripe/connect.php';

	const STRIPE_RETURNED_SUCCESS               = 1;
	const STRIPE_RETURNED_AND_PROCESSED_SUCCESS = 2;

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
	protected $test_secret_key = '';

	/**
	 * Stripe Secret key (live).
	 *
	 * @since 1.0.0
	 * @var string $secret_key
	 */
	protected $secret_key = '';

	/**
	 * Stripe test publishable key (sandbox).
	 *
	 * @since 1.0.0
	 * @var string $test_publishable_key
	 */
	protected $test_publishable_key = '';

	/**
	 * Stripe publishable key (live).
	 *
	 * @since 1.0.0
	 * @var string $publishable_key
	 */
	protected $publishable_key = '';

	/**
	 * Stripe Account ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $account_id = '';

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
	protected $_api = null;

	/**
	 * Initialize the object.
	 *
	 * @since 1.0.0
	 * @internal
	 */
	public function after_load() {
		parent::after_load();
		$this->_api = MS_Factory::load( 'MS_Gateway_Stripe_Api' );

		$this->id             = self::ID;
		$this->name           = __( 'Stripe Connect Gateway', 'memberdash' );
		$this->group          = 'Stripe';
		$this->manual_payment = true; // Recurring billed/paid manually
		$this->pro_rate       = true;

		$this->add_filter(
			'ms_model_pages_get_ms_page_url',
			'ms_model_pages_get_ms_page_url_cb',
			99,
			4
		);

		$this->add_action( 'wp_ajax_stripeSession', array( $this, 'stripeSession' ) );

		$this->add_action( 'admin_init', array( $this, 'handle_stripe_connect_requests' ) );
		$this->add_action( 'admin_notices', array( $this, 'show_webhook_notice' ) );
	}


	/**
	 * Shows a notice to configure the Stripe webhook.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function show_webhook_notice() {
		if ( ! isset( $_GET['md_stripe_connected'] ) || self::STRIPE_RETURNED_AND_PROCESSED_SUCCESS !== intval( $_GET['md_stripe_connected'] ) ) {
			return;
		}

		if ( ! $this->account_is_connected() ) {
			return;
		}

		$webhook_title = esc_html__(
			'You are connected! Please configure your Stripe webhook to finalize the setup.',
			'memberdash'
		);

		$webhook_first_detail = sprintf(
			'%1$s %2$s',
			__(
				'In order for Stripe to function properly, you must add a new Stripe webhook endpoint. To do this please visit the <a href=\'https://dashboard.stripe.com/webhooks\' target=\'_blank\'>Webhooks Section of your Stripe Dashboard</a> and click the <strong>Add endpoint</strong> button and paste the following URL:',
				'memberdash'
			),
			"<strong>{$this->get_webhook_url()}</strong>"
		);

		$webhook_second_detail = esc_html__(
			'Stripe webhooks are required so Memberdash can communicate properly with the payment gateway to confirm payment completion, renewals, and more.',
			'memberdash'
		);
		?>
		<div class="notice notice-info is-dismissible">
			<h1><?php echo $webhook_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
			<p><?php echo $webhook_first_detail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<p><?php echo $webhook_second_detail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		</div>
		<?php
	}

	/**
	 * Handle Stripe Connect requests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_stripe_connect_requests() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['md_stripe_connected'] ) && self::STRIPE_RETURNED_SUCCESS === intval( $_GET['md_stripe_connected'] ) ) {
			$this->handle_connection_request();
		}

		if (
		( isset( $_GET['md_stripe_disconnected'] ) && self::STRIPE_RETURNED_SUCCESS === intval( $_GET['md_stripe_disconnected'] ) ) ||
		( isset( $_GET['md_stripe_error'] ) && 1 === intval( $_GET['md_stripe_error'] ) && ! isset( $_GET['md_stripe_disconnected'] ) )
		) {
			$this->handle_disconnection_request();
		}
	}

	/**
	 * Handle Stripe Connection requests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function handle_connection_request() {
		$this->account_id           = sanitize_text_field( wp_unslash( $_GET['stripe_user_id'] ?? '' ) );
		$this->secret_key           = sanitize_text_field( wp_unslash( $_GET['stripe_access_token'] ?? '' ) );
		$this->test_secret_key      = sanitize_text_field( wp_unslash( $_GET['stripe_access_token_test'] ?? '' ) );
		$this->publishable_key      = sanitize_text_field( wp_unslash( $_GET['stripe_publishable_key'] ?? '' ) );
		$this->test_publishable_key = sanitize_text_field( wp_unslash( $_GET['stripe_publishable_key_test'] ?? '' ) );

		$this->save();

		$reload_url = remove_query_arg(
			array( 'md_stripe_connected', 'md_stripe_disconnected', 'stripe_user_id', 'stripe_access_token', 'stripe_access_token_test', 'stripe_publishable_key', 'stripe_publishable_key_test', 'md_stripe_error', 'error_code', 'error_message' )
		);
		$reload_url = add_query_arg(
			array( 'md_stripe_connected' => self::STRIPE_RETURNED_AND_PROCESSED_SUCCESS ),
			$reload_url
		);

		$this->enable_gateway();

		wp_safe_redirect( $reload_url );
		exit;
	}

	/**
	 * Handle Stripe Disconnection requests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function handle_disconnection_request() {
		$this->account_id           = '';
		$this->secret_key           = '';
		$this->test_secret_key      = '';
		$this->publishable_key      = '';
		$this->test_publishable_key = '';

		$this->save();

		$reload_url = remove_query_arg( array( 'md_stripe_connected' ) );
		$reload_url = add_query_arg(
			array( 'md_stripe_disconnected' => self::STRIPE_RETURNED_AND_PROCESSED_SUCCESS ),
			$reload_url
		);

		$this->disable_gateway();

		wp_safe_redirect( $reload_url );
		exit;
	}

	/**
	 * Enables this gateway and runs any necessary setup.
	 *
	 * @return void
	 */
	private function enable_gateway() {
		$this->active = true;
		$this->save();

		// stripe plan gateway.
		$stripe_plan_gateway = MS_Model_Gateway::factory( MS_Gateway_Stripeplan::ID );
		$stripe_plan_gateway->sync_gateway( $this );
	}

	/**
	 * Disables this gateway and runs any necessary cleanup.
	 *
	 * @return void
	 */
	private function disable_gateway() {
		$this->active = false;
		$this->save();

		// stripe plan gateway.
		$stripe_plan_gateway = MS_Model_Gateway::factory( MS_Gateway_Stripeplan::ID );
		$stripe_plan_gateway->sync_gateway( $this );
	}

	/**
	 * Checks if account is already connected.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function account_is_connected(): bool {
		return ! empty( $this->account_id );
	}

	/**
	 * Generates a connect url.
	 *
	 * @since 1.0.0
	 *
	 * @param string $return_url The url to return to after connection. Defaults to the current page.
	 *
	 * @return string
	 */
	public function get_connect_url( $return_url = '' ): string {
		if ( empty( $return_url ) ) {
			// remove any subfolder from the home url, if present.
			$url_parsed = wp_parse_url( home_url() );
			$return_url = $url_parsed['scheme'] . '://' . $url_parsed['host'] . add_query_arg( array() ); // @phpstan-ignore-line -- home url is safe.
		}

		$args = array(
			'stripe_action' => 'connect',
			'return_url'    => rawurlencode( $return_url ),
		);

		return add_query_arg(
			$args,
			esc_url_raw( self::CONNECT_SERVER_URL )
		);
	}

	/**
	 * Generates Stripe disconnect url.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_disconnect_url(): string {
		// remove any subfolder from the home url, if present.
		$url_parsed = wp_parse_url( home_url() );
		$return_url = $url_parsed['scheme'] . '://' . $url_parsed['host'] . add_query_arg( array() ); // @phpstan-ignore-line -- home url is safe.

		$args = array(
			'stripe_action'  => 'disconnect',
			'stripe_user_id' => $this->account_id,
			'return_url'     => rawurlencode( $return_url ),
		);

		return add_query_arg(
			$args,
			esc_url_raw( self::CONNECT_SERVER_URL )
		);
	}

	/**
	 * Creates a Stripe session.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function stripeSession() {
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
					$session = $this->_api->createPaymentSession(
						$customer,
						$invoice,
						$membership
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
	 * Processes purchase action.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 */
	public function process_purchase( $subscription ) {
		// Nothing to do here. Stripe payment is handled by webhook.
		// We need to override it because the base class implementation. We need to investigate this case later and then deprecate it or change the architecture.
	}

	/**
	 * Request automatic payment to the gateway.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 * @return bool True on success.
	 */
	public function request_payment( $subscription ) {
		$was_paid    = false;
		$note        = '';
		$external_id = '';

		do_action(
			'ms_gateway_stripe_request_payment_before',
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
						$note    = __( 'No payment for free membership', 'memberdash' );
					} else {
						$charge      = $this->_api->charge(
							$customer,
							$invoice->total,
							$invoice->currency,
							$invoice->name
						);
						$external_id = $charge->id;

						if ( true == $charge->paid ) {
							$was_paid = true;
							$invoice->pay_it( self::ID, $external_id );
							$note = __( 'Payment successful', 'memberdash' );
						} else {
							$note = __( 'Stripe payment failed', 'memberdash' );
						}
					}
				} else {
					$note = "Stripe customer is empty for user $member->username";
					MS_Helper_Debug::debug_log( $note );
				}
			} catch ( Exception $e ) {
				$note = 'Stripe error: ' . $e->getMessage();
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED, $subscription );
				MS_Helper_Debug::debug_log( $note );
			}
		} else {
			// Invoice was already paid earlier.
			$was_paid = true;
			$note     = __( 'Invoice already paid', 'memberdash' );
		}

		$invoice->gateway_id = self::ID;
		$invoice->save();

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

		do_action(
			'ms_gateway_stripe_request_payment_after',
			$subscription,
			$was_paid,
			$this
		);

		return $was_paid;
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
			'ms_gateway_stripe_get_publishable_key',
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
			'ms_gateway_stripe_get_secret_key',
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
			'ms_gateway_stripe_is_configured',
			$is_configured
		);
	}

	/**
	 * Returns an array of all Stripe keys.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public function get_stripe_keys_array(): array {
		return [
			'secret_key'           => $this->secret_key,
			'publishable_key'      => $this->publishable_key,
			'test_secret_key'      => $this->test_secret_key,
			'test_publishable_key' => $this->test_publishable_key,
		];
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
