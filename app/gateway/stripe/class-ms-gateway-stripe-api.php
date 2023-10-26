<?php
/**
 * Stripe Gateway API
 *
 * @since 1.0.0
 *
 * @package    MemberDash
 * @subpackage Model
 */

use StellarWP\Memberdash\Stripe;

/**
 * Stripe Gateway API Integration.
 *
 * This object is shared between the Stripe Single and Stripe Subscription
 * gateways.
 *
 * @since 1.0.0
 */
class MS_Gateway_Stripe_Api extends MS_Model_Option {

	/**
	 * Gateway class id.
	 */
	const ID = 'stripe';

	/**
	 * Gateway singleton instance.
	 *
	 * @since 1.0.0
	 * @var   string $instance
	 */
	public static $instance;

	/**
	 * Holds a reference to the parent gateway (either stripe or stripeplan)
	 *
	 * @since 1.0.0
	 * @var   MS_Gateway_Stripe|MS_Gateway_Stripeplan
	 */
	protected $_gateway = null;

	/**
	 * Returns the stripe API version.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_stripe_api_version(): string {
		/**
		 * Filters the Stripe API version.
		 *
		 * @since 1.0.0
		 *
		 * @param string $version The Stripe API version.
		 *
		 * @return string
		 */
		return apply_filters( 'ms_gateway_stripe_api_version', '2022-11-15' );
	}

	/**
	 * Sets the parent gateway of the API object.
	 *
	 * The parent gateway object is used to fetch the API keys.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Gateway $gateway The parent gateway.
	 */
	public function set_gateway( $gateway ) {
		$this->_gateway = $gateway;

		Stripe\Stripe::setApiKey( $this->_gateway->get_secret_key() );

		// Make sure everyone is using the same API version. we can update this if/when necessary.
		// If we don't set this, Stripe will use latest version, which may break our implementation.
		Stripe\Stripe::setApiVersion( self::get_stripe_api_version() );

		Stripe\Stripe::setMaxNetworkRetries( 3 );
	}

	/**
	 * Creates a Stripe Session for a one-time payment.
	 *
	 * @since 1.0.0
	 *
	 * @param Stripe\Customer     $customer      The customer object.
	 * @param MS_Model_Invoice    $invoice       The invoice object.
	 * @param MS_Model_Membership $membership    The membership object.
	 *
	 * @return Stripe\Checkout\Session
	 */
	public function createPaymentSession( $customer, $invoice, $membership ) {
		$cancel_url  = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_MEMBERSHIPS, false );
		$success_url = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE, false );

		$member_id     = get_current_user_id();
		$membership_id = $membership->get_id();
		$subscription  = MS_Model_Relationship::get_subscription( $member_id, $membership_id );

		$session = Stripe\Checkout\Session::create(
			[
				'customer'             => $customer->id,
				'payment_method_types' => [ 'card' ],
				'metadata'             => $this->get_stripe_metadata( $membership_id, $member_id ),
				'line_items'           => [
					[
						'price_data' => [
							'currency'     => strtolower( $invoice->currency ),
							'unit_amount'  => intval( $invoice->total * 100 ),
							'product_data' => [
								'name'        => 'Membership',
								'description' => $invoice->name,
							],
						],
						'quantity'   => 1,
					],
				],
				'client_reference_id'  => $membership_id . ',' . $member_id,
				'mode'                 => 'payment',
				'success_url'          => esc_url( $success_url . '?ms_relationship_id=' . $subscription->id ),
				'cancel_url'           => $cancel_url,
			]
		);

		/**
		 * Filters the Stripe payment session.
		 *
		 * @since 1.0.0
		 *
		 * @param Stripe\Checkout\Session $session    The session object.
		 * @param Stripe\Customer         $customer   The customer object.
		 * @param MS_Model_Invoice        $invoice    The invoice object.
		 * @param MS_Model_Membership     $membership The membership object.
		 * @param MS_Gateway_Stripe_Api   $stripe_api The Stripe API object.
		 *
		 * @return Stripe\Checkout\Session
		 */
		return apply_filters(
			'memberdash_gateway_stripe_payment_session',
			$session,
			$customer,
			$invoice,
			$membership,
			$this
		);
	}

	/**
	 * Returns the Stripe metadata.
	 *
	 * @since 1.0.0
	 *
	 * @param int $membership_id The membership id.
	 * @param int $member_id     The member id.
	 *
	 * @return array<string, mixed>
	 */
	private function get_stripe_metadata( int $membership_id, int $member_id ): array {
		/**
		 * Filters the Stripe metadata.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed>  $metadata   The metadata.
		 * @param MS_Gateway_Stripe_Api $stripe_api The Stripe API object.
		 *
		 * @return array<string, mixed>
		 */
		return apply_filters(
			'memberdash_gateway_stripe_metadata',
			[
				'is_memberdash'      => true,
				'memberdash_version' => defined( 'MEMBERDASH_VERSION' ) ? MEMBERDASH_VERSION : 'unknown',
				'membership_id'      => $membership_id,
				'member_id'          => $member_id,
			],
			$this
		);
	}

	/**
	 * Creates a Stripe Session for a subscription payment.
	 *
	 * @since 1.0.0
	 *
	 * @param Stripe\Customer     $customer      The customer object.
	 * @param MS_Model_Invoice    $invoice       The invoice object.
	 * @param MS_Model_Membership $membership    The membership object.
	 * @param int                 $plan_id       The plan id.
	 *
	 * @return Stripe\Checkout\Session
	 */
	public function createSubscriptionPaymentSession( $customer, $invoice, $membership, $plan_id ) {
		$cancel_url  = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_MEMBERSHIPS, false );
		$success_url = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE, false );

		$member_id     = get_current_user_id();
		$membership_id = $membership->get_id();

		$args = [
			'customer'             => $customer->id,
			'payment_method_types' => [ 'card' ],
			'metadata'             => $this->get_stripe_metadata( $membership_id, $member_id ),
			'line_items'           => [
				[
					'price'    => $plan_id,
					'quantity' => 1,
				],
			],
			'client_reference_id'  => $membership_id . ',' . $member_id,
			'mode'                 => 'subscription',
			'success_url'          => $success_url,
			'cancel_url'           => $cancel_url,
			'subscription_data'    => [
				'metadata' => $this->get_stripe_metadata( $membership_id, $member_id ),
			],
		];

		// add coupon to subscription.
		if ( $invoice->coupon_id ) {
			$args['discounts'] = [
				[
					'coupon' => MS_Gateway_Stripeplan::get_the_id( (string) $invoice->coupon_id, 'coupon' ),
				],
			];
		}

		// add trial to subscription.
		if ( $invoice->uses_trial ) {
			$args['subscription_data']['trial_period_days'] = MS_Helper_Period::get_period_in_days(
				$membership->trial_period_unit,
				$membership->trial_period_type
			);
		}

		$session = Stripe\Checkout\Session::create( $args );

		/**
		 * Filters the Stripe subscription session.
		 *
		 * @since 1.0.0
		 *
		 * @param Stripe\Checkout\Session $session    The session object.
		 * @param Stripe\Customer         $customer   The customer object.
		 * @param MS_Model_Invoice        $invoice    The invoice object.
		 * @param MS_Model_Membership     $membership The membership object.
		 * @param int                     $plan_id    The plan id.
		 * @param MS_Gateway_Stripe_Api   $stripe_api The Stripe API object.
		 *
		 * @return Stripe\Checkout\Session
		 */
		return apply_filters(
			'memberdash_gateway_stripe_subscription_session',
			$session,
			$customer,
			$invoice,
			$membership,
			$plan_id,
			$this
		);
	}


	/**
	 * Get Member's Stripe Customer Object, creates a new customer if not found.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Member $member The member.
	 *
	 * @return Stripe\Customer $customer
	 */
	public function get_stripe_customer( $member ) {
		$customer = $this->find_customer( $member );

		if ( empty( $customer ) ) {
			$customer = Stripe\Customer::create(
				array(
					'email'  => $member->email,
					'name'   => $member->get_fullname(),
					'expand' => array(
						'subscriptions',
					),
				)
			);

			$member->set_gateway_profile( self::ID, 'customer_id', $customer->id );
			$member->save();
		}

		return apply_filters(
			'ms_gateway_stripe_get_stripe_customer',
			$customer,
			$member,
			$this
		);
	}

	/**
	 * Get Member's Stripe Customer Object.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param MS_Model_Member $member The member.
	 *
	 * @return Stripe\Customer $customer
	 */
	public function find_customer( $member ) {
		$customer_id = $member->get_gateway_profile( self::ID, 'customer_id' );

		if ( ! empty( $customer_id ) ) {
			try {
				$customer = Stripe\Customer::retrieve(
					array(
						'id'     => $customer_id,
						'expand' => array(
							'subscriptions',
						),
					)
				);
			} catch ( Exception $e ) {
				$note = 'Stripe error: ' . $e->getMessage();
			}

			// Seems like the customer was manually deleted on Stripe website.
			if ( empty( $customer ) ) {
				$customer = null;
				$member->set_gateway_profile( self::ID, 'customer_id', '' );
			}
		}

		return apply_filters(
			'ms_gateway_stripe_find_customer',
			isset( $customer ) ? $customer : null,
			$member,
			$this
		);
	}

	/**
	 * Add card info to Stripe customer profile and to WordPress user meta.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @param MS_Model_Member $member   The member model.
	 * @param Stripe\Customer $customer The stripe customer object.
	 * @param string          $token    The stripe card token generated by the gateway.
	 */
	public function add_card( $member, $customer, $token ) {
		$card = false;

		// 1. Save card to Stripe profile.
		if ( ! empty( $customer->sources ) ) {
			$card                     = $customer->sources->create( array( 'card' => $token ) );
			$customer->default_source = $card->id;
		}

		if ( $card ) {
			$customer->save();
		}

		/**
		 * Fires after a credit card was saved to the Stripe customer profile.
		 *
		 * @since 1.0.0
		 */
		do_action( 'ms_gateway_stripe_credit_card_saved', $card, $member, $this );

		// 2. Save card to WordPress user meta.

		if ( $card ) {
			$member->set_gateway_profile(
				self::ID,
				'card_exp',
				gmdate( 'Y-m-d', strtotime( "{$card->exp_year}-{$card->exp_month}-01" ) )
			);
			$member->set_gateway_profile( self::ID, 'card_num', $card->last4 );
			$member->save();
		}

		do_action(
			'ms_gateway_stripe_add_card_info_after',
			$customer,
			$token,
			$this
		);
	}

	/**
	 * Creates a one-time charge that is immediately captured.
	 *
	 * This means the money is instantly transferred to our own stripe account.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param  Stripe\Customer $customer    Stripe customer to charge.
	 * @param  float           $amount      Amount in currency (i.e. in USD, not in cents)
	 * @param  string          $currency    3-digit currency code.
	 * @param  string          $description This is displayed on the invoice to customer.
	 *
	 * @return Stripe\Charge The resulting charge object.
	 */
	public function charge( $customer, $amount, $currency, $description ) {

		$amount = apply_filters(
			'ms_gateway_stripe_charge_amount',
			$amount,
			$currency
		);

		$charge = Stripe\Charge::create(
			array(
				'customer'    => $customer->id,
				'amount'      => intval( $amount * 100 ), // Amount in cents!
				'currency'    => strtolower( $currency ),
				'description' => $description,
			)
		);

		return apply_filters(
			'ms_gateway_stripe_charge',
			$charge,
			$customer,
			$amount,
			$currency,
			$description,
			$this
		);
	}

	/**
	 * Fetches an existing subscription from Stripe and returns it.
	 *
	 * If the specified customer did not subscribe to the membership then
	 * boolean FALSE will be returned.
	 *
	 * @since 1.0.0
	 *
	 * @param  Stripe\Customer     $customer   Stripe customer to charge.
	 * @param  MS_Model_Membership $membership The membership.
	 *
	 * @return Stripe\Subscription|false The resulting charge object.
	 */
	public function get_subscription( $customer, $membership ) {
		$plan_id = MS_Gateway_Stripeplan::get_the_id( (string) $membership->id, 'plan' );

		/*
		 * Check all subscriptions of the customer and find the subscription
		 * for the specified membership.
		 */
		$last_checked = false;
		$has_more     = false;
		$subscription = false;

		do {
			$args = array();

			if ( $last_checked ) {
				$args['starting_after'] = $last_checked;
			}

			$active_subs = $customer->subscriptions->all( $args );
			$has_more    = $active_subs->has_more;

			foreach ( $active_subs->data as $sub ) {
				if ( strval( $sub->plan->id ) === strval( $plan_id ) ) {
					$subscription = $sub;
					$has_more     = false;
					break 2;
				}
				$last_checked = $sub->id;
			}
		} while ( $has_more );

		return apply_filters(
			'ms_gateway_stripe_get_subscription',
			$subscription,
			$customer,
			$membership,
			$this
		);
	}

	/**
	 * Returns the Stripe Subscription ID attached to the invoice related to the membership.
	 *
	 * @param  array<Stripe\InvoiceLineItem> $stripe_invoice_data The stripe invoice data.
	 * @param  string                        $membership_id       The membership ID.
	 *
	 * @since 1.0.3
	 *
	 * @return string|null
	 */
	public function get_stripe_subscription_id( $stripe_invoice_data, $membership_id ) {
		$plan_id = MS_Gateway_Stripeplan::get_the_id( $membership_id, 'plan' );

		foreach ( $stripe_invoice_data as $line ) {
			if ( ! isset( $line->plan ) ) {
				continue;
			}

			if ( $line->plan->id === $plan_id ) {
				return $line->subscription;
			}
		}

		return null;
	}

	/**
	 * Returns the member's subscription object from the Stripe invoice.
	 *
	 * @since 1.0.3
	 *
	 * @param MS_Model_Member $member         The member.
	 * @param Stripe\Invoice  $stripe_invoice The Stripe invoice.
	 *
	 * @return MS_Model_Relationship|null
	 */
	public function get_member_subscription_from_stripe_invoice( MS_Model_Member $member, Stripe\Invoice $stripe_invoice ) {
		$membership_id = null;

		foreach ( $stripe_invoice->lines->data as $item ) {
			if ( ! isset( $item->plan ) ) {
				continue;
			}

			if (
				isset( $item->metadata->membership_id )
				&& $item->metadata->membership_id
			) {
				$membership_id = $item->metadata->membership_id;
			} else {
				// legacy support for old invoices.
				$membership_id = $this->get_membership_id_from_plan_id( $member->get_subscriptions(), $item->plan->id );
			}

			if ( $membership_id ) {
				return $member->get_subscription( $membership_id );
			}
		}

		return null;
	}

	/**
	 * Returns the member's subscription object from the Stripe Subscription.
	 *
	 * @since 1.0.3
	 *
	 * @param MS_Model_Member     $member              The member.
	 * @param Stripe\Subscription $stripe_subscription The Stripe subscription.
	 *
	 * @return MS_Model_Relationship|null
	 */
	public function get_member_subscription_from_stripe_subscription( MS_Model_Member $member, Stripe\Subscription $stripe_subscription ) {
		$membership_id = null;

		if (
			isset( $stripe_subscription->metadata->membership_id )
			&& $stripe_subscription->metadata->membership_id
		) {
			$membership_id = $stripe_subscription->metadata->membership_id;
		} else {
			// legacy support for old invoices.
			foreach ( $stripe_subscription->items->data as $item ) {
				$subscriptions              = $member->get_subscriptions();
				$subscription_membership_id = $this->get_membership_id_from_plan_id( $subscriptions, $item->plan->id );

				if ( $subscription_membership_id ) {
					$membership_id = $subscription_membership_id;
					break;
				}
			}
		}

		if ( $membership_id ) {
			return $member->get_subscription( $membership_id );
		}

		return null;
	}

	/**
	 * Returns the membership id from a given plan id.
	 *
	 * @since 1.0.3
	 *
	 * @param array<MS_Model_Relationship> $subscriptions List of subscriptions.
	 * @param string                       $plan_id       The plan id.
	 *
	 * @return int|null
	 */
	private function get_membership_id_from_plan_id( array $subscriptions, string $plan_id ): ?int {
		foreach ( $subscriptions as $subscription ) {
			if ( $subscription->is_system() ) {
				continue;
			}

			$subscription_membership_id = $subscription->get_membership_id();

			$membership_plan_id = MS_Gateway_Stripeplan::get_the_id( (string) $subscription_membership_id, 'plan' );

			if ( $plan_id === $membership_plan_id ) {
				return $subscription_membership_id;
			}
		}

		return null;
	}

	/**
	 * Creates a subscription that starts immediately.
	 *
	 * @since 1.0.0
	 *
	 * @param  Stripe\Customer  $customer Stripe customer to charge.
	 * @param  MS_Model_Invoice $invoice  The relevant invoice.
	 *
	 * @return Stripe\Subscription The resulting charge object.
	 */
	public function subscribe( $customer, $invoice ) {
		$membership = $invoice->get_membership();
		$plan_id    = MS_Gateway_Stripeplan::get_the_id( (string) $membership->id, 'plan' );

		$subscription = $this->get_subscription( $customer, $membership );

		// We don't need cancelled subscriptions.
		if ( isset( $subscription->cancel_at_period_end ) && $subscription->cancel_at_period_end === true ) {
			try {
				// Cancel the subscription immediately.
				$subscription->cancel();
			} catch ( Exception $e ) {
				// Well, failed to cancel.
			}
			// No subscription.
			$subscription = false;
		}

		/*
		 * If no active subscription was found for the membership create it.
		 */
		if ( ! $subscription ) {
			$tax_percent = null;
			$coupon_id   = null;

			if ( is_numeric( $invoice->tax_rate ) && $invoice->tax_rate > 0 ) {
				$tax_percent = floatval( $invoice->tax_rate );
			}

			if ( $invoice->coupon_id ) {
				$coupon_id = MS_Gateway_Stripeplan::get_the_id(
					(string) $invoice->coupon_id,
					'coupon'
				);
			}

			$args = array(
				'plan'        => $plan_id,
				'tax_percent' => $tax_percent,
				'coupon'      => $coupon_id,
			);

			$subscription = $customer->subscriptions->create( $args );
		}

		return apply_filters(
			'ms_gateway_stripe_subscribe',
			$subscription,
			$customer,
			$invoice,
			$membership,
			$this
		);
	}

	/**
	 * Creates or updates the payment plan specified by the function parameter.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param array $plan_data The plan-object containing all details for Stripe.
	 */
	public function create_or_update_plan( $plan_data ) {
		$item_id   = $plan_data['id'];
		$all_items = MS_Factory::get_transient( 'ms_stripeplan_plans' );
		$all_items = mslib3()->array->get( $all_items );

		if ( ! isset( $all_items[ $item_id ] ) || ! $all_items[ $item_id ] instanceof Stripe\Plan ) {
			try {
				$item = Stripe\Plan::retrieve( $item_id );
			} catch ( Exception $e ) {
				// If the plan does not exist then stripe will throw an Exception.
				$item = false;
			}
			$all_items[ $item_id ] = $item;
		} else {
			$item = $all_items[ $item_id ];
		}

		/*
		 * Stripe can only update the plan-name, so we have to delete and
		 * recreate the plan manually.
		 */
		if ( $item && $item instanceof Stripe\Plan ) {
			try {
				$item->delete();
				$all_items[ $item_id ] = false;
			} catch ( Exception $e ) {
				// If the plan does not exist then stripe will throw an Exception, but it's ok.
				$all_items[ $item_id ] = false;
			}
		}

		if ( $plan_data['amount'] > 0 ) {
			try {
				$item = Stripe\Plan::create( $plan_data );
			} catch ( Exception $e ) {
				$item = false;
				MS_Helper_Debug::debug_log( 'Stripe plan creation failed: ' . $e->getMessage() );
				MS_Helper_Debug::debug_log( $plan_data );
			}

			$all_items[ $item_id ] = $item;
		}

		MS_Factory::set_transient(
			'ms_stripeplan_plans',
			$all_items,
			HOUR_IN_SECONDS
		);
	}

	/**
	 * Creates or updates the coupon specified by the function parameter.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param array $coupon_data The object containing all details for Stripe.
	 */
	public function create_or_update_coupon( $coupon_data ) {
		$item_id   = $coupon_data['id'];
		$all_items = MS_Factory::get_transient( 'ms_stripeplan_plans' );
		$all_items = mslib3()->array->get( $all_items );

		if ( ! isset( $all_items[ $item_id ] )
			|| ! $all_items[ $item_id ] instanceof Stripe\Coupon ) {
			try {
				$item = Stripe\Coupon::retrieve( $item_id );
			} catch ( Exception $e ) {
				// If the coupon does not exist then stripe will throw an Exception.
				$item = false;
			}
			$all_items[ $item_id ] = $item;
		} else {
			$item = $all_items[ $item_id ];
		}

		/*
		 * Stripe can only update the coupon-name, so we have to delete and
		 * recreate the coupon manually.
		 */

		if ( $item && $item instanceof Stripe\Coupon ) {
			$item->delete();
			$all_items[ $item_id ] = false;
		}

		$item                  = Stripe\Coupon::create( $coupon_data );
		$all_items[ $item_id ] = $item;

		MS_Factory::set_transient(
			'ms_stripeplan_coupons',
			$all_items,
			HOUR_IN_SECONDS
		);

	}


	/**
	 * Deleted the coupon specified by the function parameter.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param string $coupon_id -  The coupon id
	 */
	public function delete_coupon( $coupon_id ) {
		$item_id   = $coupon_id;
		$all_items = MS_Factory::get_transient( 'ms_stripeplan_plans' );
		$all_items = mslib3()->array->get( $all_items );

		if ( ! isset( $all_items[ $item_id ] )
			|| ! $all_items[ $item_id ] instanceof Stripe\Coupon ) {
			try {
				$item = Stripe\Coupon::retrieve( $item_id );
			} catch ( Exception $e ) {
				// If the coupon does not exist then stripe will throw an Exception.
				$item = false;
			}
			$all_items[ $item_id ] = $item;
		} else {
			$item = $all_items[ $item_id ];
		}

		// Delete Coupon.
		if ( $item && $item instanceof Stripe\Coupon ) {
			$item->delete();
			$all_items[ $item_id ] = false;
		}
		MS_Factory::set_transient(
			'ms_stripeplan_coupons',
			$all_items,
			HOUR_IN_SECONDS
		);
	}
}
