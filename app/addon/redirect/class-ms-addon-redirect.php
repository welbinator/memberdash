<?php
/**
 * Add-On controller for: Redirect control
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Controller
 */

/**
 * Class MS_Addon_Redirect.
 */
class MS_Addon_Redirect extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.0.0
	 */
	const ID = 'addon_redirect';

	// Ajax Actions
	const AJAX_SAVE_SETTING = 'addon_redirect_save';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		if ( self::is_active() ) {
			// Add new settings tab
			$this->add_filter(
				'ms_controller_settings_get_tabs',
				'settings_tabs',
				10,
				2
			);

			$this->add_filter(
				'ms_view_settings_edit_render_callback',
				'manage_render_callback',
				10,
				3
			);

			// Save settings via ajax
			$this->add_ajax_action(
				self::AJAX_SAVE_SETTING,
				'ajax_save_setting'
			);

			// Add filter to replace the default plugin URLs with custom URLs
			$this->add_action(
				'ms_url_after_login',
				'filter_url_after_login',
				10,
				2
			);

			$this->add_action(
				'ms_url_after_logout',
				'filter_url_after_logout',
				10,
				2
			);

			$this->add_filter(
				'login_redirect',
				'mwps_login_redirect',
				999,
				3
			);

			$this->add_action(
				'wp_logout',
				'mwps_logout_redirect',
				999
			);
		}
	}

	/**
	 * Registers the Add-On
	 *
	 * @since 1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name'        => __( 'Redirect Control', 'memberdash' ),
			'description' => __( 'Define your individual URL to display after a user is logged-in or logged-out.', 'memberdash' ),
			'details'     => array(
				array(
					'type'  => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => __( 'Settings', 'memberdash' ),
					'desc'  => __( 'When this Add-on is enabled you will see a new section in the "Settings" page with additional options.', 'memberdash' ),
				),
			),
		);

		return $list;
	}

	/**
	 * Returns the Redirect-Settings model.
	 *
	 * @since 1.0.0
	 * @return MS_Addon_Redirect_Model
	 */
	public static function model() {
		static $Model = null;

		if ( null === $Model ) {
			$Model = MS_Factory::load( 'MS_Addon_Redirect_Model' );
		}

		return $Model;
	}

	/**
	 * Add redirect settings tab in settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tabs The current tabs.
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID ] = array(
			'title'        => __( 'Redirect', 'memberdash' ),
			'url'          => MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => self::ID )
			),
			'svg_view_box' => '0 0 166 152',
			'icon_path'    => '<path d="M113.857 151.429H38.4286C17.8572 151.429 0.714355 134.286 0.714355 113.714V38.2858C0.714355 17.7144 17.8572 0.571533 38.4286 0.571533H55.5715C59.6858 0.571533 62.4286 3.31439 62.4286 7.42868C62.4286 11.543 59.6858 14.2858 55.5715 14.2858H38.4286C25.4001 14.2858 14.4286 25.2572 14.4286 38.2858V113.714C14.4286 126.743 25.4001 137.714 38.4286 137.714H113.857C126.886 137.714 137.857 126.743 137.857 113.714V103.429C137.857 99.3144 140.6 96.5715 144.714 96.5715C148.829 96.5715 151.571 99.3144 151.571 103.429V113.714C151.571 134.286 134.429 151.429 113.857 151.429Z" fill="#9DA3AF"/><path d="M50.7715 103.429C48.0287 103.429 45.2858 101.371 43.9144 98.6286C42.543 93.8286 41.8572 88.3429 41.8572 82.8572C41.8572 52.6858 66.543 28.0001 96.7144 28.0001H110.429V7.42863C110.429 4.68577 111.8 1.94291 114.543 1.2572C117.286 -0.114229 120.029 0.571485 122.086 2.62863L163.229 43.7715C165.972 46.5143 165.972 50.6286 163.229 53.3715L122.086 94.5144C120.029 96.5715 117.286 97.2572 114.543 95.8858C111.8 95.2001 110.429 92.4572 110.429 89.7143V69.1429H96.7144C78.2001 69.1429 62.4287 80.8001 56.943 98.6286C56.2572 101.371 53.5144 103.429 50.7715 103.429ZM96.7144 55.4286H117.286C121.4 55.4286 124.143 58.1715 124.143 62.2858V73.2572L148.829 48.5715L124.143 23.8858V34.8572C124.143 38.9715 121.4 41.7143 117.286 41.7143H96.7144C77.5144 41.7143 61.743 54.7429 56.943 72.5715C67.2287 61.6001 80.943 55.4286 96.7144 55.4286Z" fill="#9DA3AF"/>',
		);

		return $tabs;
	}

	/**
	 * Add redirect settings-view callback.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $callback The current function callback.
	 * @param  string $tab The current membership rule tab.
	 * @param  array  $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_render_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view     = MS_Factory::load( 'MS_Addon_Redirect_View' );
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Handle Ajax update custom setting action.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$model = self::model();

			$model->set( $_POST['field'], $_POST['value'] );
			$model->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		wp_die( $msg ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Replaces the default "After Login" URL
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url
	 * @return string
	 */
	public function filter_url_after_login( $url, $enforce ) {
		if ( ! $enforce ) {
			$model   = self::model();
			$new_url = $model->get( 'redirect_login' );

			if ( ! empty( $new_url ) ) {
				$url = mslib3()->net->expand_url( $new_url );
			}
		}

		return $url;
	}

	/**
	 * Login redirect
	 *
	 * @since 1.0.2.8
	 */
	public function mwps_login_redirect( $redirect_to, $request, $user ) {
		if ( isset( $user->ID ) && ! MS_Model_Member::is_admin_user( $user->ID ) ) {
			$model   = self::model();
			$new_url = $model->get( 'redirect_login' );

			if ( ! empty( $new_url ) ) {
				$redirect_to = mslib3()->net->expand_url( $new_url );
			}
		}

		return self::mwps_replace_username_url( $redirect_to, $user );
	}

	/**
	 * Replaces the default "After Logout" URL
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url
	 * @return string
	 */
	public function filter_url_after_logout( $url, $enforce ) {
		if ( ! $enforce ) {
			$model   = self::model();
			$new_url = $model->get( 'redirect_logout' );

			if ( ! empty( $new_url ) ) {
				$url = mslib3()->net->expand_url( $new_url );
			}
		}

		return $url;
	}

	/**
	 * Logout URL
	 *
	 * @since 1.0.2.8
	 */
	public function mwps_logout_redirect() {
		$model   = self::model();
		$new_url = $model->get( 'redirect_logout' );

		if ( ! empty( $new_url ) ) {
			$logout_url = mslib3()->net->expand_url( $new_url );
		} else {
			$logout_url = site_url();
		}

		wp_safe_redirect( $logout_url );
		exit;
	}

	public function mwps_replace_username_url( $url, $user ) {
		if ( strpos( $url, '[username]' ) ) {
			if ( ! isset( $user->ID ) ) {
				return $url;
			}

			$user_info = get_userdata( $user->ID );
			$url       = str_replace( '[username]', $user_info->user_login, $url );
		}

		return $url;
	}

}
