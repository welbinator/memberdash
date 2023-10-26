<?php

class MS_Gateway_Paypalstandard_View_Settings extends MS_View {

	public function to_html() {
		$fields  = $this->prepare_fields();
		$gateway = $this->data['model'];

		ob_start();
		// Render tabbed interface.
		?>
		<form class="ms-gateway-settings-form ms-form">
			<?php
			$description = sprintf(
				'%s<br />%s<br />',
				__( 'This advanced PayPal gateway will handle all payment types, including trial periods and recurring payments. However, it should not be used for permanent type memberships, as here it will display "pay again after 5 years" during checkout.', 'memberdash' ),
				__( 'In order for Membership to function correctly you must setup an IPN listening URL with PayPal. Make sure to complete this step, otherwise we are not notified when a member cancels their subscription.', 'memberdash' )
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
			'merchant_id'       => array(
				'id'          => 'merchant_id',
				'type'        => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'       => __( 'PayPal Merchant Account ID', 'memberdash' ),
				'desc'        => sprintf(
					__( 'Note: This is <i>not the email address</i> but the merchant ID found in %1$syour PayPal profile%2$s. (in Sandbox mode use your Sandbox Email address)', 'memberdash' ), // cspell:disable-line.
					'<a href="https://www.paypal.com/webapps/customerprofile/summary.view" target="_blank">',
					'</a>'
				),
				'value'       => $gateway->merchant_id,
				'placeholder' => 'SGGGX43FAKKXN', // cspell:disable-line.
				'class'       => 'ms-text-large',
				'ajax_data'   => array( 1 ),
			),

			'paypal_site'       => array(
				'id'            => 'paypal_site',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'PayPal Site', 'memberdash' ),
				'field_options' => $gateway->get_paypal_sites(),
				'value'         => $gateway->paypal_site,
				'class'         => 'ms-text-large',
				'ajax_data'     => array( 1 ),
			),

			'mode'              => array(
				'id'            => 'mode',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'PayPal Mode', 'memberdash' ),
				'value'         => $gateway->mode,
				'field_options' => $gateway->get_mode_types(),
				'class'         => 'ms-text-large',
				'ajax_data'     => array( 1 ),
			),

			'ipn_listening_url' => array(
				'id'        => 'ipn_listening_url',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => sprintf(
					__( 'IPN listening URL (You shouldn\'t change this value on production. Default value: %s)', 'memberdash' ),
					$gateway->get_default_return_url()
				),
				'value'     => $gateway->get_return_url(),
				'class'     => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'pay_button_url'    => array(
				'id'        => 'pay_button_url',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => apply_filters(
					'ms_translation_flag',
					__( 'Payment button label or URL', 'memberdash' ),
					'gateway-button' . $gateway->id
				),
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
