<?php

class MS_Addon_Mailchimp_View extends MS_View {

	/**
	 * Returns the HTML code of the Settings form.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function render_tab() {
		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'MailChimp Settings', 'memberdash' ) )
			);

			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Prepare fields that are displayed in the form.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function prepare_fields() {
		$api_status = MS_Addon_Mailchimp::get_api_status();
		$settings   = $this->data['settings'];

		$action      = MS_Controller_Settings::AJAX_ACTION_UPDATE_CUSTOM_SETTING;
		$auto_opt_in = $settings->get_custom_setting( 'mailchimp', 'auto_opt_in' );
		$auto_opt_in = mslib3()->is_true( $auto_opt_in );

		$fields = array(
			'mailchimp_api_test'    => array(
				'id'    => 'mailchimp_api_test',
				'type'  => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'MailChimp API test status: ', 'memberdash' ),
				'value' => ( $api_status ) ? __( 'Verified', 'memberdash' ) : __( 'Not connected', 'memberdash' ),
				'class' => ( $api_status ) ? 'ms-ok' : 'ms-nok',
			),

			'mailchimp_api_key'     => array(
				'id'        => 'mailchimp_api_key',
				'name'      => 'custom[mailchimp][api_key]',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => __( 'MailChimp API Key', 'memberdash' ),
				'desc'      => sprintf(
					'<div>' . __( 'Visit <a href="%1$s">your API dashboard</a> to create an API Key.', 'memberdash' ) . '</div>',
					'http://admin.mailchimp.com/account/api" target="_blank'
				),
				'value'     => $settings->get_custom_setting( 'mailchimp', 'api_key' ),
				'class'     => 'ms-text-medium',
				'ajax_data' => array(
					'group'  => 'mailchimp',
					'field'  => 'api_key',
					'action' => $action,
				),
			),

			'separator'             => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'auto_opt_in'           => array(
				'id'        => 'auto_opt_in',
				'name'      => 'custom[mailchimp][auto_opt_in]',
				'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title'     => __( 'Automatically opt-in new users to the mailing list.', 'memberdash' ),
				'desc'      => __( 'Users will not receive an email confirmation. You are responsible to inform your users.', 'memberdash' ),
				'value'     => $auto_opt_in,
				'class'     => 'inp-before',
				'ajax_data' => array(
					'group'  => 'mailchimp',
					'field'  => 'auto_opt_in',
					'action' => $action,
				),
			),

			'separator1'            => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'mail_list_registered'  => array(
				'id'            => 'mail_list_registered',
				'name'          => 'custom[mailchimp][mail_list_registered]',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'Registered users mailing list (not members)', 'memberdash' ),
				'field_options' => MS_Addon_Mailchimp::get_mail_lists(),
				'value'         => $settings->get_custom_setting( 'mailchimp', 'mail_list_registered' ),
				'ajax_data'     => array(
					'group'  => 'mailchimp',
					'field'  => 'mail_list_registered',
					'action' => $action,
				),
			),

			'mail_list_members'     => array(
				'id'            => 'mail_list_members',
				'name'          => 'custom[mailchimp][mail_list_members]',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'Members mailing list', 'memberdash' ),
				'field_options' => MS_Addon_Mailchimp::get_mail_lists(),
				'value'         => $settings->get_custom_setting( 'mailchimp', 'mail_list_members' ),
				'ajax_data'     => array(
					'group'  => 'mailchimp',
					'field'  => 'mail_list_members',
					'action' => $action,
				),
			),

			'mail_list_deactivated' => array(
				'id'            => 'mail_list_deactivated',
				'name'          => 'custom[mailchimp][mail_list_deactivated]',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'Deactivated memberships mailing list', 'memberdash' ),
				'field_options' => MS_Addon_Mailchimp::get_mail_lists(),
				'value'         => $settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' ),
				'ajax_data'     => array(
					'group'  => 'mailchimp',
					'field'  => 'mail_list_deactivated',
					'action' => $action,
				),
			),
		);

		return $fields;
	}
}
