<?php

class MS_Gateway_Stripe_View_Settings extends MS_View {

	public function to_html() {
		$fields  = $this->prepare_fields();
		$gateway = $this->data['model'];

		ob_start();
		/** Render tabbed interface. */
		?>
		<form class="ms-gateway-settings-form ms-form">
			<?php

			$description = sprintf(
				'%1$s<br/><br/>%2$s',
				__( 'Best used for recurring payments.', 'memberdash' ),
				sprintf(
					__( 'Set up the url <strong>%1$s</strong> in your %2$sStripe Webhook Settings%3$s', 'memberdash' ),
					$gateway->get_webhook_url(),
					'<a href="https://dashboard.stripe.com/account/webhooks" target="_blank">',
					'</a>'
				)
			);

			MS_Helper_Html::settings_box_header( '', $description );
			MS_Helper_Html::html_element( $this->get_connection_button() );
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			MS_Helper_Html::settings_box_footer();
			?>
		</form>
		<?php
		return ob_get_clean();
	}

	protected function prepare_fields() {
		$gateway = $this->data['model'];
		$action  = MS_Controller_Gateway::AJAX_ACTION_UPDATE_GATEWAY;
		$nonce   = wp_create_nonce( $action );

		$fields = array(
			'mode'           => array(
				'id'            => 'mode',
				'title'         => __( 'Mode', 'memberdash' ),
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value'         => $gateway->mode,
				'field_options' => $gateway->get_mode_types(),
				'class'         => 'ms-text-large',
				'ajax_data'     => array( 1 ),
			),

			'vendor_logo'    => array(
				'id'        => 'vendor_logo',
				'title'     => __( 'Vendor Logo (Must be at least 128px by 128px)', 'memberdash' ),
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'     => $gateway->vendor_logo,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'pay_button_url' => array(
				'id'        => 'pay_button_url',
				'title'     => apply_filters(
					'ms_translation_flag',
					__( 'Payment button label', 'memberdash' ),
					'gateway-button' . $gateway->id
				),
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'     => $gateway->pay_button_url,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),
		);

		// Process the fields and add missing default attributes.
		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['ajax_data'] ) ) {
				$fields[ $key ]['ajax_data']['field']      = $fields[ $key ]['id'];
				$fields[ $key ]['ajax_data']['_wpnonce']   = $nonce;
				$fields[ $key ]['ajax_data']['action']     = $action;
				$fields[ $key ]['ajax_data']['gateway_id'] = $gateway->id;
			}
		}

		return $fields;
	}

	/**
	 * Returns the connection button.
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	private function get_connection_button(): array {
		$button_text = $this->data['model']->account_is_connected() ? __( 'Disconnect Stripe', 'memberdash' ) : __( 'Connect Stripe', 'memberdash' );
		return array(
			'type'  => MS_Helper_Html::TYPE_HTML_LINK,
			'title' => $button_text,
			'value' => '<span class="ms-text-lg">' . $button_text . '</span>',
			'url'   => $this->data['model']->account_is_connected() ? $this->data['model']->get_disconnect_url() : $this->data['model']->get_connect_url(),
			'class' => 'button button-primary ms-bg-black ms-border-black ms-text-white ms-shadow-none ms-text-center',
		);
	}
}
