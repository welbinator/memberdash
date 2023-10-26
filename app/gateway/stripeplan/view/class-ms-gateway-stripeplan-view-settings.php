<?php

class MS_Gateway_Stripeplan_View_Settings extends MS_View {

	public function to_html() {
		$fields  = $this->prepare_fields();
		$gateway = $this->data['model'];

		ob_start();
		/** Render tabbed interface. */
		?>
		<form class="ms-gateway-settings-form ms-form">
			<?php
			$description = sprintf(
				'%1$s<br />%2$s<br />%3$s',
				__( 'Best used for recurring payments.', 'memberdash' ),
				sprintf(
					__( 'You can find your Stripe API Keys in your %1$sAccount Settings%2$s.', 'memberdash' ),
					'<a href="https://dashboard.stripe.com/account/apikeys" target="_blank">',
					'</a>'
				),
				sprintf(
					__( 'Set up the url <strong>%1$s</strong> in your %2$sStripe Webhook Settings%3$s', 'memberdash' ),
					$this->data['model']->get_webhook_url(),
					'<a href="https://dashboard.stripe.com/account/webhooks" target="_blank">',
					'</a>'
				)
			);

			MS_Helper_Html::settings_box_header( '', $description );
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			MS_Helper_Html::settings_box_footer();
			?>
		</form>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	protected function prepare_fields() {
		$gateway = $this->data['model'];
		$action  = MS_Controller_Gateway::AJAX_ACTION_UPDATE_GATEWAY;
		$nonce   = wp_create_nonce( $action );

		$fields = array(
			'mode'                 => array(
				'id'            => 'mode',
				'title'         => __( 'Mode', 'memberdash' ),
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value'         => $gateway->mode,
				'field_options' => $gateway->get_mode_types(),
				'class'         => 'ms-text-large',
				'ajax_data'     => array( 1 ),
			),

			'test_secret_key'      => array(
				'id'        => 'test_secret_key',
				'title'     => __( 'API test secret key', 'memberdash' ),
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'     => $gateway->test_secret_key,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'test_publishable_key' => array(
				'id'        => 'test_publishable_key',
				'title'     => __( 'API test publishable key', 'memberdash' ),
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'     => $gateway->test_publishable_key,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'secret_key'           => array(
				'id'        => 'secret_key',
				'title'     => __( 'API live secret key', 'memberdash' ),
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'     => $gateway->secret_key,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'publishable_key'      => array(
				'id'        => 'publishable_key',
				'title'     => __( 'API live publishable key', 'memberdash' ),
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'     => $gateway->publishable_key,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'vendor_logo'          => array(
				'id'        => 'vendor_logo',
				'title'     => __( 'Vendor logo (must be at least 128px by 128px)', 'memberdash' ),
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'     => $gateway->vendor_logo,
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'pay_button_url'       => array(
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

}
