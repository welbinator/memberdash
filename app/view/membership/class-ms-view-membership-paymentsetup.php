<?php
/**
 * Payment Setup page
 *
 * Only used when creating a new membership.
 *
 * @since 1.0.0
 * @package MemberDash
 * @subpackage View
 */

/**
 * Class MS_View_Membership_PaymentSetup.
 *
 * View class for rendering the Payment Setup page during new membership creation.
 *
 * @since 1.0.0
 */
class MS_View_Membership_PaymentSetup extends MS_View {

	/**
	 * Create view output.
	 *
	 * This method generates the HTML output for the Payment Setup page
	 * used when creating a new membership.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If class was not found or couldn't create it.
	 *
	 * @return string|false The contents of the output buffer and end output buffering.
	 * If output buffering isn't active then false is returned.
	 */
	public function to_html() {
		$fields = $this->get_fields();
		/**
		 * MS_Controller_Plugin object created by the MS_Factory.
		 *
		 * @var MS_Controller_Plugin $ms_plugin
		 */
		$ms_plugin = MS_Factory::create( 'MS_Controller_Plugin' );

		$ms_plugin->check_active_payment_gateway( true );

		ob_start();
		?>

		<div class="wrap ms-wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Payment', 'memberdash' ),
					'desc'  => __( 'Set up your payment gateways and Membership Price', 'memberdash' ),
				)
			);
			?>

			<div class="ms-flex ms-flex-col md:ms-flex-row md:ms-space-x-12 ms-space-y-6 md:ms-space-y-0 ms-mt-12">
				<?php if ( $this->is_global_payments_set() ) : ?>
					<div class="ms-w-full">
						<?php $this->specific_payment_settings(); ?>
						<?php MS_Helper_Html::settings_box_footer(); ?>
					</div>
				<?php else : ?>
					<div class="ms-w-full md:ms-w-1/2">
						<?php $this->global_payment_settings(); ?>
						<?php MS_Helper_Html::settings_box_footer(); ?>
					</div>

					<div class="ms-w-full md:ms-w-1/2">
						<?php $this->specific_payment_settings(); ?>
						<?php MS_Helper_Html::settings_box_footer(); ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="ms-w-full">
				<?php
				MS_Helper_Html::settings_footer(
					$fields['control_fields'],
					$this->data['show_next_button']
				);
				?>
			</div>
		</div>

		<?php
		return ob_get_clean(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get the payment control fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array{ control_fields: array<string, mixed> } The control fields.
	 */
	private function get_fields() {
		$membership          = $this->data['membership'];
		$gateway_check_async = array();

		// If the wizard was triggered.
		if ( ! $this->is_global_payments_set() ) {
			$gateway_check_async = array(
				'id'    => 'gateway_check_async',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'no',
			);
		}

		$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;

		$fields = array(
			'control_fields' => array(
				'membership_id' => array(
					'id'    => 'membership_id',
					'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $membership->id,
				),
				'step'          => array(
					'id'    => 'step',
					'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['step'],
				),
				'action'        => array(
					'id'    => 'action',
					'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
				),
				'_wpnonce'      => array(
					'id'    => '_wpnonce',
					'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => wp_create_nonce( $this->data['action'] ),
				),
			),
		);
		// Add the gateway check field to verify async since we need to check after the user set the gateway during the wizard.
		if ( ! empty( $gateway_check_async ) ) {
			$fields['control_fields']['gateway_check_async'] = $gateway_check_async;
		}

		return apply_filters(
			'ms_view_membership_payment_get_fields',
			$fields
		);
	}

	public function is_global_payments_set() {
		return $this->data['is_global_payments_set'];
	}

	/**
	 * Render the Payment settings the first time the user creates a membership.
	 * After the user set up a payment gateway these options are not displayed
	 * anymore
	 *
	 * @since 1.0.0
	 */
	public function global_payment_settings() {
		if ( $this->is_global_payments_set() ) {
			return;
		}

		$view = MS_Factory::create( 'MS_View_Settings_Page_Payment' );

		echo $view->render();//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the payment box for a single Membership subscription.
	 *
	 * @since 1.0.0
	 */
	public function specific_payment_settings() {
		$membership = $this->data['membership'];

		$title = sprintf(
			__( 'Payment settings for %s', 'memberdash' ),
			$membership->get_name_tag()
		);

		MS_Helper_Html::settings_tab_header(
			array(
				__( 'Payment Gateways', 'memberdash' ),
				__( 'You need to set-up at least one Payment Gateway to be able to process payments.', 'memberdash' ),
			)
		);
		?>
			<div class="ms-settings-wrapper ms-space-y-6 ms-specific-payment-wrapper">
				<div class="ms-header">
					<div class="ms-settings-tab-title">
						<?php echo $title; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="ms-settings-description">
						<div class="ms-description">
							<?php esc_html_e( 'These are NOT shared across all memberships.', 'memberdash' ); ?>
						</div>
					</div>
				</div>

				<?php MS_Helper_Html::settings_box_header(); ?>
					<div class="inside">
						<?php
						$view       = MS_Factory::create( 'MS_View_Membership_Tab_Payment' );
						$view->data = $this->data;
						echo $view->to_html(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>
				<?php MS_Helper_Html::save_text(); ?>
			</div>
		<?php
	}

}
