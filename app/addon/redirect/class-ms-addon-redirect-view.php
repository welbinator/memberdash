<?php

/**
 * The Settings-Form
 */
class MS_Addon_Redirect_View extends MS_View {

	public function render_tab() {
		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array(
					'title' => __( 'Redirect Settings', 'memberdash' ),
					'desc'  => array(
						__( 'Specify your custom URLs here. You can use either an absolute URL (starting with "http://") or an site-relative path (like "/some-page/")', 'memberdash' ),
						sprintf(
						// translators: %1$s: opening link tag, %2$s: closing link tag, %3$s: shortcode.
							__( 'The URLs you specify here can always be overwritten in the %1$slogin shortcode%2$s using the redirect-attributes. Example: <code>[%3$s redirect_login="/welcome/" redirect_logout="/good-bye/"]</code>.', 'memberdash' ), // cspell:disable-line.
							sprintf(
								'<a href="%s#ms-membership-login" target="_blank">',
								MS_Controller_Plugin::get_admin_url(
									'help',
									array( 'tab' => 'shortcodes' )
								)
							),
							'</a>',
							MS_Helper_Shortcode::SCODE_LOGIN
						),
					),
				)
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

	public function prepare_fields() {
		$model = MS_Addon_Redirect::model();

		$action = MS_Addon_Redirect::AJAX_SAVE_SETTING;

		$fields = array(
			'redirect_login'  => array(
				'id'          => 'redirect_login',
				'type'        => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'       => __( 'After Login', 'memberdash' ),
				'desc'        => __(
					'<p>This page is displayed to users right after login.</p>
                                                                <p>You can add [username] to URL which will be replaced by members username.<p>
                                                                <p>Useful for redirecting to a BuddyPress profile page.</p>
                                                                <p>Example: http://yourdomain.com/members/[username]/profile will be replaced with http://yourdomain.com/members/myusername/profile</p>',
					'memberdash'
				),
				'placeholder' => MS_Model_Pages::get_url_after_login( false ),
				'value'       => $model->get( 'redirect_login' ),
				'class'       => 'ms-text-large',
				'ajax_data'   => array(
					'field'  => 'redirect_login',
					'action' => $action,
				),
			),

			'redirect_logout' => array(
				'id'          => 'redirect_logout',
				'type'        => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'       => __( 'After Logout', 'memberdash' ),
				'desc'        => __( 'This page is displayed to users right after they did log out.', 'memberdash' ),
				'placeholder' => MS_Model_Pages::get_url_after_logout( false ),
				'value'       => $model->get( 'redirect_logout' ),
				'class'       => 'ms-text-large',
				'ajax_data'   => array(
					'field'  => 'redirect_logout',
					'action' => $action,
				),
			),
		);

		return $fields;
	}
}
