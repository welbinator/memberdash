<?php
/**
 * Gateway controller.
 *
 * @package MemberDash
 * @subpackage Controller
 */

/**
 * Gateway controller class.
 *
 * @since 1.0.0
 */
class MS_Controller_Gateway extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_TOGGLE_GATEWAY = 'toggle_gateway';
	const AJAX_ACTION_UPDATE_GATEWAY = 'update_gateway';
	const AJAX_ACTION_CHECK_GATEWAYS = 'verify_enabled_gateways';

	/**
	 * Allowed actions to execute in template_redirect hook.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $allowed_actions = array( 'update_card', 'purchase_button' );

	/**
	 * Prepare the gateway controller.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_action( 'template_redirect', 'process_actions', 1 );

		$this->add_action( 'ms_controller_gateway_settings_render_view', 'gateway_settings_edit' );

		$this->add_action( 'ms_view_shortcode_invoice_purchase_button', 'invoice_purchase_button', 10, 2 );
		$this->add_action( 'ms_view_frontend_payment_purchase_button', 'purchase_button', 10, 2 );

		$this->add_action( 'ms_controller_frontend_signup_gateway_form', 'gateway_form_mgr', 1 );
		$this->add_action( 'ms_controller_frontend_signup_process_purchase', 'process_purchase', 1 );
		$this->add_filter( 'ms_view_shortcode_membershipsignup_cancel_button', 'cancel_button', 10, 2 );

		$this->add_action( 'ms_view_shortcode_account_card_info', 'card_info' );

		$this->add_action( 'wp_loaded', 'handle_webhook', 10, 0 );
		$this->add_action( 'pre_get_posts', 'handle_payment_return', 1 );
		$this->add_action( 'ms_gateway_transaction_log', 'log_transaction', 10, 8 );

		$this->add_ajax_action( self::AJAX_ACTION_TOGGLE_GATEWAY, 'toggle_ajax_action' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_GATEWAY, 'ajax_action_update_gateway' );
		$this->add_ajax_action( self::AJAX_ACTION_CHECK_GATEWAYS, 'ajax_action_verify_enabled_gateways' );
	}

	/**
	 * Handle URI actions for registration.
	 *
	 * Matches returned 'action' to method to execute.
	 *
	 * Related action hooks:
	 * - template_redirect
	 *
	 * @since 1.0.0
	 */
	public function process_actions() {
		$action = $this->get_action();

		/**
		 * If $action is set, then call relevant method.
		 *
		 * Methods:
		 *
		 * @see $allowed_actions property
		 */
		if ( ! empty( $action )
			&& method_exists( $this, $action )
			&& in_array( $action, $this->allowed_actions )
		) {
			$this->$action();
		}
	}

	/**
	 * Method that is triggered by the Ajax action AJAX_ACTION_CHECK_GATEWAYS. It will check for active payment
	 * gateways excluding free. Log an error if the nonce is invalid or the user is not admin.
	 *
	 * @since 1.0.2
	 *
	 * @return void Does not return a value, but it sends a JSON response.
	 */
	public function ajax_action_verify_enabled_gateways(): void {
		mslib3()->array->strip_slashes( $_GET, array( 'nonce', 'action_verify' ) );
		$action = sanitize_key( $_GET['action_verify'] ?? '' );
		$nonce  = sanitize_key( $_GET['nonce'] ?? '' );

		// Bail if the user is not an admin or the nonce is invalid.
		if ( ! $this->is_admin_user()
			|| ! wp_verify_nonce( $nonce, $action )
		) {
			MS_Helper_Debug::debug_log(
				__( 'Invalid nonce verification', 'memberdash' ),
			);
			wp_send_json_error(
				array(
					'invalid_request' => 'yes',
				)
			);
		}

		$active_gateways = MS_Model_Gateway::get_gateways( true, true );
		if ( count( $active_gateways ) > 0 ) {
			wp_send_json_success( array(), 200 );
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'You need to enable at least one payment gateway to continue.', 'memberdash' ),
				)
			);
		}
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related action hooks:
	 * - wp_ajax_toggle_gateway
	 *
	 * @since 1.0.0
	 */
	public function toggle_ajax_action() {
		$msg = 0;

		$fields = array( 'gateway_id' );
		if ( $this->verify_nonce()
			&& self::validate_required( $fields )
			&& $this->is_admin_user()
		) {
			$msg = $this->gateway_list_do_action(
				'toggle_activation',
				array( $_POST['gateway_id'] )
			);
		}

		wp_die( $msg ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Handle Ajax update gateway action.
	 *
	 * Related action hooks:
	 * - wp_ajax_update_gateway
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_gateway() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$fields = array( 'action', 'gateway_id', 'field', 'value' );

		if ( $this->verify_nonce()
			&& ( self::validate_required( $fields ) || $_POST['field'] == 'pay_button_url' )
			&& $this->is_admin_user()
		) {
			mslib3()->array->strip_slashes( $_POST, 'value' );

			$msg = $this->gateway_list_do_action(
				$_POST['action'],
				array( $_POST['gateway_id'] ),
				array( $_POST['field'] => $_POST['value'] )
			);
		}

		wp_die( $msg ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Show gateway settings page.
	 *
	 * Related action hooks:
	 * - ms_controller_gateway_settings_render_view
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway_id The gateway ID.
	 *
	 * @return void
	 */
	public function gateway_settings_edit( $gateway_id ) {
		if ( ! empty( $gateway_id )
			&& MS_Model_Gateway::is_valid_gateway( $gateway_id )
		) {
			switch ( $gateway_id ) {
				case MS_Gateway_Manual::ID:
					$view = MS_Factory::create( 'MS_Gateway_Manual_View_Settings' );
					break;

				case MS_Gateway_Paypalstandard::ID:
					$view = MS_Factory::create( 'MS_Gateway_Paypalstandard_View_Settings' );
					break;

				case MS_Gateway_Stripe::ID:
					$view = MS_Factory::create( 'MS_Gateway_Stripe_View_Settings' );
					break;

				default:
					// Empty form.
					$view = MS_Factory::create( 'MS_View' );
					break;
			}

			$data = array(
				'model'  => MS_Model_Gateway::factory( $gateway_id ),
				'action' => 'edit',
			);

			$view->data = apply_filters(
				'ms_gateway_view_settings_edit_data',
				$data
			);
			$view       = apply_filters(
				'ms_gateway_view_settings_edit',
				$view,
				$gateway_id
			);

			$view->render();
		}
	}

	/**
	 * Handle Payment Gateway list actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $action The action to execute.
	 * @param int[]   $gateways The gateways IDs to process.
	 * @param mixed[] $fields The data to process.
	 */
	public function gateway_list_do_action( $action, $gateways, $fields = null ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		foreach ( $gateways as $gateway_id ) {
			$gateway = MS_Model_Gateway::factory( $gateway_id );

			switch ( $action ) {
				case 'toggle_activation':
					$gateway->active = ! $gateway->active;
					$gateway->save();
					$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;

					/**
					 * Hook called after a gateway-status was toggled.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ms_gateway_toggle_' . $gateway_id, $gateway );
					break;

				case 'edit':
				case 'update_gateway':
					foreach ( $fields as $field => $value ) {
						$gateway->$field = trim( $value );
					}
					$gateway->save();

					/*
					 * $settings->is_global_payments_set is used to hide global
					 * payment settings in the membership setup payment step
					 */
					if ( $gateway->is_configured() ) {
						$settings                         = MS_Factory::load( 'MS_Model_Settings' );
						$settings->is_global_payments_set = true;
						$settings->save();
						$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
					} else {
						$msg = MS_Helper_Settings::SETTINGS_MSG_UNCONFIGURED;
					}

					/**
					 * Hook called after a gateway-settings were modified.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ms_gateway_changed_' . $gateway_id, $gateway );
					break;
			}
		}

		return apply_filters(
			'ms_controller_gateway_gateway_list_do_action',
			$msg,
			$action,
			$gateways,
			$fields,
			$this
		);
	}

	/**
	 * Show gateway purchase button.
	 *
	 * Related action hooks:
	 * - ms_view_frontend_payment_purchase_button
	 * - ms_view_shortcode_invoice_purchase_button
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $subscription The subscription.
	 * @param MS_Model_Invoice      $invoice      The invoice.
	 *
	 * @return void
	 */
	public function purchase_button( $subscription, $invoice ) {
		// Get only active gateways.
		$gateways       = MS_Model_Gateway::get_gateways( true );
		$data           = array();
		$gateways_count = count( $gateways );

		// Make sure free gateway is at last.
		if ( isset( $gateways[ MS_Gateway_Free::ID ] ) ) {
			$free = $gateways[ MS_Gateway_Free::ID ];
			unset( $gateways[ MS_Gateway_Free::ID ] );
			$gateways[ MS_Gateway_Free::ID ] = $free;
		}

		$membership = $subscription->get_membership();
		$is_free    = (
					$membership->is_free()
					|| 0. === (float) $invoice->total
					|| ( $invoice->uses_trial && $membership->payment_type !== MS_Model_Membership::PAYMENT_TYPE_RECURRING )
					);

		// show gateway purchase button for every active gateway.
		foreach ( $gateways as $gateway ) {
			$view = null;

			// Skip gateways that are not configured.
			if ( ! $gateway->is_configured() ) {
				continue;
			}

			if ( ! $membership->can_use_gateway( $gateway->id ) ) {
				continue;
			}

			// Hide Stripe Plan Button - Merged to Stripe.
			if ( MS_Gateway_Stripeplan::ID === $gateway->id ) {
				continue;
			}

			$data['ms_relationship'] = $subscription;
			$data['gateway']         = $gateway;
			$data['step']            = MS_Controller_Frontend::STEP_PROCESS_PURCHASE;

			// Free membership, show only free gateway.
			if ( $is_free ) {
				if ( MS_Gateway_Free::ID !== $gateway->id || $gateways_count <= 1 ) {
					// If there are no other gateways active, do not show free button.
					continue;
				}
			} elseif ( MS_Gateway_Free::ID === $gateway->id ) {
				// Skip free gateway.
				continue;
			}

			$view_class = get_class( $gateway ) . '_View_Button';
			$view       = MS_Factory::create( $view_class );

			if ( ! empty( $view ) ) {
				$view = apply_filters(
					'ms_gateway_view_button',
					$view,
					$gateway->id
				);

				$view->data = apply_filters(
					'ms_gateway_view_button_data',
					$data,
					$gateway->id
				);

				$html = apply_filters(
					'ms_controller_gateway_purchase_button_' . $gateway->id,
					$view->to_html(),
					$subscription,
					$this
				);

				echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

	}


	/**
	 * Show gateway purchase button in invoice.
	 *
	 * Related action hooks:
	 * - ms_view_frontend_payment_purchase_button
	 * - ms_view_shortcode_invoice_purchase_button
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $subscription The subscription.
	 * @param MS_Model_Invoice      $invoice      The invoice.
	 *
	 * @return void
	 */
	public function invoice_purchase_button( $subscription, $invoice ) {
		// Get only active gateways.
		$gateways = MS_Model_Gateway::get_gateways( true );
		$data     = array();

		$membership = $subscription->get_membership();
		$is_free    = (
			$membership->is_free()
			|| 0. === (float) $invoice->total
			|| ( $invoice->uses_trial && $membership->payment_type !== MS_Model_Membership::PAYMENT_TYPE_RECURRING )
			);

		// show gateway purchase button for every active gateway.
		foreach ( $gateways as $gateway ) {
			$view = null;

			// Skip gateways that are not configured.
			if ( ! $gateway->is_configured() ) {
				continue;
			}

			if ( ! $membership->can_use_gateway( $gateway->id ) ) {
				continue;
			}

			$data['ms_relationship'] = $subscription;
			$data['gateway']         = $gateway;
			$data['step']            = MS_Controller_Frontend::STEP_PROCESS_PURCHASE;

			// Free membership, show only free gateway.
			if ( $is_free ) {
				if ( MS_Gateway_Free::ID !== $gateway->id && ! $invoice->uses_trial ) {
					continue;
				}
			} elseif ( MS_Gateway_Free::ID === $gateway->id ) {
				// Skip free gateway.
				continue;
			}

			$view_class = get_class( $gateway ) . '_View_Button';
			$view       = MS_Factory::create( $view_class );

			if ( ! empty( $view ) ) {
				$view = apply_filters(
					'ms_gateway_view_button',
					$view,
					$gateway->id
				);

				$view->data = apply_filters(
					'ms_gateway_view_button_data',
					$data,
					$gateway->id
				);

				$html = apply_filters(
					'ms_controller_gateway_purchase_button_' . $gateway->id,
					$view->to_html(),
					$subscription,
					$this
				);

				echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	/**
	 * Show gateway purchase button.
	 *
	 * Related action hooks:
	 * - ms_view_shortcode_membershipsignup_cancel_button
	 *
	 * @since 1.0.0
	 */
	public function cancel_button( $button, $subscription ) {
		$view                    = null;
		$data                    = array();
		$data['ms_relationship'] = $subscription;
		$new_button              = null;

		switch ( $subscription->gateway_id ) {
			case MS_Gateway_Paypalstandard::ID:
				$view            = MS_Factory::create( 'MS_Gateway_Paypalstandard_View_Cancel' );
				$data['gateway'] = $subscription->get_gateway();
				break;

			case MS_Gateway_Stripe::ID:
			case MS_Gateway_Free::ID:
			case MS_Gateway_Manual::ID:
			default:
				break;
		}
		$view = apply_filters( 'ms_gateway_view_cancel_button', $view );

		if ( $view && $view instanceof MS_View ) {
			$view->data = apply_filters(
				'ms_gateway_view_cancel_button_data',
				$data
			);

			$new_button = $view->get_button();
		}

		if ( ! $new_button ) {
			$new_button = $button;
		}

		return apply_filters(
			'ms_controller_gateway_cancel_button',
			$new_button,
			$subscription,
			$this
		);
	}

	/**
	 * Set hook to handle gateway extra form to commit payments.
	 *
	 * Related action hooks:
	 * - ms_controller_frontend_signup_gateway_form
	 *
	 * @since 1.0.0
	 */
	public function gateway_form_mgr() {
		// Display the gateway form.
		$this->add_filter( 'the_content', 'gateway_form', 10 );
	}

	/**
	 * Handles gateway extra form to commit payments.
	 *
	 * Related filter hooks:
	 * - the_content
	 *
	 * @since 1.0.0
	 *
	 * @param  string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function gateway_form( $content ) {
		$data = array();
		$html = '';

		// Do not parse the form when building the excerpt.
		global $wp_current_filter;
		if ( in_array( 'get_the_excerpt', $wp_current_filter ) ) {
			return '';
		}

		$fields = array( 'gateway', 'ms_relationship_id' );
		if ( self::validate_required( $fields )
			&& MS_Model_Gateway::is_valid_gateway( $_POST['gateway'] )
		) {
			$data['gateway']            = $_POST['gateway'];
			$data['ms_relationship_id'] = $_POST['ms_relationship_id'];
			$view                       = null;

			$subscription = MS_Factory::load(
				'MS_Model_Relationship',
				$_POST['ms_relationship_id']
			);

			switch ( $_POST['gateway'] ) {
				default:
					break;
			}

			$view = apply_filters(
				'ms_gateway_view_form',
				$view,
				$_POST['gateway'],
				$subscription
			);

			if ( $view && $view instanceof MS_View ) {
				$view->data = apply_filters(
					'ms_gateway_view_form_data',
					$data
				);

				$html = $view->to_html();
			}

			return apply_filters(
				'ms_controller_gateway_form',
				$html,
				$this
			);
		}
	}

	/**
	 * Process purchase using gateway.
	 *
	 * Related Action Hooks:
	 * - ms_controller_frontend_signup_process_purchase
	 *
	 * @since 1.0.0
	 */
	public function process_purchase() {
		$fields = array( 'gateway', 'ms_relationship_id' );

		mslib3()->array->equip_request( 'gateway', 'ms_relationship_id' );

		$valid      = true;
		$nonce_name = $_REQUEST['gateway'] . '_' . $_REQUEST['ms_relationship_id'];

		if ( ! self::validate_required( $fields, 'any' ) ) {
			$valid = false;
			$err   = 'GAT-01 (invalid fields)';
		} elseif ( ! MS_Model_Gateway::is_valid_gateway( $_REQUEST['gateway'] ) ) {
			$valid = false;
			$err   = 'GAT-02 (invalid gateway)';
		} elseif ( ! $this->verify_nonce( $nonce_name, 'any' ) ) {
			$valid = false;
			$err   = 'GAT-03 (invalid nonce)';
		}

		if ( $valid ) {
			$subscription = MS_Factory::load(
				'MS_Model_Relationship',
				$_REQUEST['ms_relationship_id']
			);

			$gateway_id = $_REQUEST['gateway'];
			$gateway    = MS_Model_Gateway::factory( $gateway_id );

			try {
				$invoice = $gateway->process_purchase( $subscription );

				$this->check_future_subscription_date( $invoice, $subscription );

				// If invoice is successfully paid, redirect to welcome page.
				if ( $invoice->is_paid()
					|| ( $invoice->uses_trial
						&& MS_Model_Invoice::STATUS_BILLED == $invoice->status
					)
				) {
					// Make sure to respect the single-membership rule
					$this->validate_membership_states( $subscription );

					// Redirect user to the Payment-Completed page.
					if ( ! defined( 'IS_UNIT_TEST' ) ) {
							MS_Model_Pages::redirect_to(
								MS_Model_Pages::MS_PAGE_REG_COMPLETE,
								array( 'ms_relationship_id' => $subscription->id )
							);
					}
				} elseif ( MS_Gateway_Manual::ID == $gateway_id ) {
					// For manual gateway payments.
					$this->add_action( 'the_content', 'purchase_info_content' );
				} else {
					// Something went wrong, the payment was not successful.
					$this->add_action( 'the_content', 'purchase_error_content' );
				}
			} catch ( Exception $e ) {
				MS_Helper_Debug::debug_log( $e->getMessage() );

				switch ( $gateway_id ) {
					case MS_Gateway_Stripe::ID:
					case MS_Gateway_Stripeplan::ID:
						$_POST['error'] = sprintf(
						// translators: %s: Error message.
							__( 'Error: %s', 'memberdash' ),
							$e->getMessage()
						);

						// Hack to send the error message back to the payment_table.
						MS_Plugin::instance()->controller->controllers['frontend']->add_action(
							'the_content',
							'payment_table',
							1
						);
						break;

					default:
						do_action( 'ms_controller_gateway_form_error', $e );
						$this->add_action( 'the_content', 'purchase_error_content' );
						break;
				}
			}
		} else {
			MS_Helper_Debug::debug_log( 'Error Code ' . $err );

			$this->add_action( 'the_content', 'purchase_error_content' );
		}

		// Hack to show signup page in case of errors
		$ms_page = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_REGISTER );

		if ( $ms_page ) {
			// During unit-testing the $ms_page object might be empty.
			global $wp_query;
			$wp_query->query_vars['page_id']   = $ms_page->ID;
			$wp_query->query_vars['post_type'] = 'page';
		}

		do_action(
			'ms_controller_gateway_process_purchase_after',
			$this
		);
	}

	/**
	 * Check if a subscription set for a future date is being paid for now
	 * If payment is being done before the due date, we have to adjust the
	 * subscription date to match the payment gateway
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Invoice      $invoice - the current invoice
	 * @param MS_Model_Relationship $subscription - the subscription being paid for
	 */
	private function check_future_subscription_date( $invoice, $subscription ) {
		// Incase they are paying for a subscription before the start date, we adjust the dates
		$current_date = MS_Helper_Period::current_date( null, true );

		$valid_date = MS_Helper_Period::is_after(
			$subscription->start_date,
			$current_date
		);

		if ( $valid_date ) {
			$expire_date               = $subscription->calc_expire_date( $current_date, $invoice->is_paid() );
			$subscription->start_date  = $current_date;
			$subscription->expire_date = $expire_date;
			$subscription->save();
		}
	}

	/**
	 * Make sure that we respect the Single-Membership rule.
	 * This rule is active when the "Multiple-Memberships" Add-on is DISABLED.
	 *
	 * @since 1.0.0
	 *
	 * @param  MS_Model_Relationship $new_relationship
	 */
	protected function validate_membership_states( $new_relationship ) {
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
			// Multiple memberships allowed. No need to check anything.
			return;
		}

		$cancel_these = array(
			MS_Model_Relationship::STATUS_TRIAL,
			MS_Model_Relationship::STATUS_ACTIVE,
			MS_Model_Relationship::STATUS_PENDING,
		);

		$member = $new_relationship->get_member();
		foreach ( $member->subscriptions as $subscription ) {
			if ( $subscription->id === $new_relationship->id ) {
				continue; }
			if ( in_array( $subscription->status, $cancel_these ) ) {
				$subscription->cancel_membership();
			}
		}
	}

	/**
	 * Show signup page with custom content.
	 *
	 * This is used by manual gateway (overridden) to show payment info.
	 *
	 * Related action hooks:
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The page content to filter.
	 * @return string The filtered content.
	 */
	public function purchase_info_content( $content ) {
		return apply_filters(
			'ms_controller_gateway_purchase_info_content',
			$content,
			$this
		);
	}

	/**
	 * Show error message in the signup page.
	 *
	 * Related action hooks:
	 *
	 * @param string $content The content.
	 *
	 * @since 1.0.0
	 */
	public function purchase_error_content( $content ) {
		return apply_filters(
			'ms_controller_gateway_purchase_error_content',
			__( 'Sorry, your signup request has failed. Try again.', 'memberdash' ),
			$content,
			$this
		);
	}


	/**
	 * Handle payment gateway return IPNs.
	 *
	 * Used by Paypal gateways.
	 * A redirection rule is set up in the main MS_Plugin object
	 * (protected_content.php):
	 * /ms-payment-return/XYZ becomes index.php?paymentgateway=XYZ
	 *
	 * Related action hooks:
	 * - pre_get_posts
	 *
	 * @todo Review how this works when we use OAuth API's with gateways.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $wp_query The WordPress query object
	 */
	public function handle_payment_return( $wp_query ) {
		// Do not check custom loops.
		if ( ! $wp_query->is_main_query() ) {
			return; }

		if ( ! empty( $wp_query->query_vars['paymentgateway'] ) ) {
			$gateway = $wp_query->query_vars['paymentgateway'];

			/**
			 * In 1.1.0 the underscore in payment gateway names was removed.
			 * To compensate for this we need to continue listen to these old
			 * gateway-names.
			 */
			switch ( $gateway ) {
				case 'paypal_standard':
					$gateway = 'paypalstandard';
					break;
				case 'paypal-standard':
					$gateway = 'paypalstandard';
					break;
				case 'paypalexpress':
					$gateway = 'paypalstandard';
					break;
			}

			if ( MS_Model_Gateway::is_active( $gateway ) ) {
				$action = 'ms_gateway_handle_payment_return_' . $gateway;
				do_action( $action );
			} else {
				// Log the payment attempt when the gateway is not active.
				if ( MS_Model_Gateway::is_valid_gateway( $gateway ) ) {
					$note = __( 'Gateway is inactive', 'memberdash' );
				} else {
					$note = sprintf(
					// translators: %s: Gateway name.
						__( 'Unknown Gateway: %s', 'memberdash' ),
						$gateway
					);
				}

				do_action(
					'ms_gateway_transaction_log',
					$gateway, // gateway ID
					'handle', // request|process|handle
					false, // success flag
					0, // subscription ID
					0, // invoice ID
					0, // charged amount
					$note, // Descriptive text
					'' // External ID
				);
			}
		}
	}


	/**
	 * Handle Web Hooks. Used by gateways that have webhooks
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_webhook() {
		if ( ! isset( $_GET['memberdash-integration'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$gateway = sanitize_text_field( wp_unslash( $_GET['memberdash-integration'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		// normalize gateway name.
		switch ( $gateway ) {
			case 'stripe':
			case 'stripeplan':
				$gateway = 'stripeplan';
				break;
		}

		if ( empty( $gateway ) ) {
			return;
		}

		if ( MS_Model_Gateway::is_active( $gateway ) ) {
			$action = 'ms_gateway_handle_webhook_' . $gateway;
			do_action( $action );
			return;
		}

		// Log the payment attempt when the gateway is not active.
		if ( MS_Model_Gateway::is_valid_gateway( $gateway ) ) {
			$note = __( 'WebHook : Gateway is inactive', 'memberdash' );
		} else {
			$note = sprintf(
			// translators: %s: Gateway name.
				__( 'WebHook : Unknown Gateway: %s', 'memberdash' ),
				$gateway
			);
		}

		do_action(
			'ms_gateway_transaction_log',
			$gateway, // gateway ID.
			'handle', // request|process|handle.
			false, // success flag.
			0, // subscription ID.
			0, // invoice ID.
			0, // charged amount.
			$note, // Descriptive text.
			'' // External ID.
		);

		wp_send_json_error(
			array(
				'message' => $note,
			)
		);
	}

	/**
	 * Show gateway credit card information.
	 *
	 * If a card is used, show it in account's page.
	 *
	 * Related action hooks:
	 * - ms_view_shortcode_account_card_info
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data The data passed to hooked view.
	 */
	public function card_info( $data = null ) {
		if ( ! empty( $data['gateway'] ) && is_array( $data['gateway'] ) ) {
			$gateways = array();

			foreach ( $data['gateway'] as $ms_relationship_id => $gateway ) {
				// avoid duplicates
				if ( ! in_array( $gateway->id, $gateways ) ) {
					$gateways[] = $gateway->id;
				} else {
					continue;
				}
				$view = null;

				switch ( $gateway->id ) {
					case MS_Gateway_Stripe::ID:
						$member         = MS_Model_Member::get_current_member();
						$data['stripe'] = $member->get_gateway_profile(
							$gateway->id
						);

						if ( empty( $data['stripe']['card_exp'] ) ) {
							continue 2;
						}

						$view                       = MS_Factory::create( 'MS_Gateway_Stripe_View_Card' );
						$data['member']             = $member;
						$data['publishable_key']    = $gateway->get_publishable_key();
						$data['ms_relationship_id'] = $ms_relationship_id;
						$data['gateway']            = $gateway;
						break;
					default:
						break;
				}

				if ( ! empty( $view ) ) {
					$view       = apply_filters(
						'ms_gateway_view_change_card',
						$view,
						$gateway->id
					);
					$view->data = apply_filters(
						'ms_gateway_view_change_card_data',
						$data,
						$gateway->id
					);

					$html = $view->to_html();
					echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}
	}

	/**
	 * Handle update credit card information in gateway.
	 *
	 * Used to change credit card info in account's page.
	 *
	 * Related action hooks:
	 * - template_redirect
	 *
	 * @since 1.0.0
	 */
	public function update_card() {
		if ( ! empty( $_POST['gateway'] ) ) {
			$gateway = MS_Model_Gateway::factory( $_POST['gateway'] );
			$member  = MS_Model_Member::get_current_member();

			switch ( $gateway->id ) {
				case MS_Gateway_Stripe::ID:
					if ( ! empty( $_POST['stripeToken'] ) && $this->verify_nonce() ) {
						mslib3()->array->strip_slashes( $_POST, 'stripeToken' );

						$gateway->add_card( $member, $_POST['stripeToken'] );
						if ( ! empty( $_POST['ms_relationship_id'] ) ) {
							$ms_relationship = MS_Factory::load(
								'MS_Model_Relationship',
								$_POST['ms_relationship_id']
							);
							MS_Model_Event::save_event(
								MS_Model_Event::TYPE_UPDATED_INFO,
								$ms_relationship
							);
						}

						wp_safe_redirect(
							esc_url_raw( add_query_arg( array( 'msg' => 1 ) ) )
						);
						exit;
					}
					break;
				default:
					break;
			}
		}

		do_action(
			'ms_controller_gateway_update_card',
			$this
		);
	}

	/**
	 * Saves transaction details to the database. The transaction logs can later
	 * be displayed in the Billings section.
	 *
	 * @since 1.0.0
	 * @internal Action handler for 'ms_gateway_transaction_log'
	 *
	 * @param string $gateway_id The gateway ID.
	 * @param string $method Following values:
	 *        "handle": IPN response
	 *        "process": Process order (i.e. user comes from Payment screen)
	 *        "request": Automatically request recurring payment
	 * @param bool   $success True means that the transaction was paid/successful.
	 *          False indicates an error.
	 *          NULL indicates a message that was intentionally skipped.
	 * @param int    $subscription_id
	 * @param int    $invoice_id
	 * @param float  $amount Payment amount.
	 * @param string $notes Additional text to describe the transaction or error.
	 * @param string $external_id The gateways transaction ID.
	 */
	public function log_transaction( $gateway_id, $method, $success, $subscription_id, $invoice_id, $amount, $notes, $external_id ) {
		$log                  = MS_Factory::create( 'MS_Model_Transactionlog' );
		$log->description     = $notes;
		$log->gateway_id      = $gateway_id;
		$log->method          = $method;
		$log->success         = $success;
		$log->subscription_id = $subscription_id;
		$log->invoice_id      = $invoice_id;
		$log->amount          = $amount;
		$log->external_id     = $external_id;
		$log->save();
	}
}
