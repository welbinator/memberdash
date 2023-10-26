<?php
/**
 * Controller for managing Plugin Settings.
 *
 * The primary entry point for managing Membership admin pages.
 *
 * @package MemberDash
 * @subpackage Controller
 */

/**
 * Controller for managing Plugin Settings.
 *
 * The primary entry point for managing Membership admin pages.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Controller
 */
class MS_Controller_Settings extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_TOGGLE_SETTINGS        = 'toggle_settings';
	const AJAX_ACTION_UPDATE_SETTING         = 'update_setting';
	const AJAX_ACTION_UPDATE_CUSTOM_SETTING  = 'update_custom_setting';
	const AJAX_ACTION_UPDATE_PROTECTION_MSG  = 'update_protection_msg';
	const AJAX_ACTION_TOGGLE_CRON            = 'toggle_cron';
	const AJAX_ACTION_TOGGLE_PROTECTION_FILE = 'toggle_protection_file';
	const AJAX_ACTION_GENERATE_INVOICE_ID    = 'generate_invoice_id';

	/**
	 * POST action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const POST_ACTION_UPDATE_LICENSE_KEY     = 'update_license_key';
	const POST_ACTION_DEACTIVATE_LICENSE_KEY = 'deactivate_license_key';

	/**
	 * Settings tabs.
	 *
	 * @since 1.0.0
	 *
	 * @var   string
	 */
	const TAB_GENERAL     = 'general';
	const TAB_PAYMENT     = 'payment';
	const TAB_MESSAGES    = 'messages';
	const TAB_EMAILS      = 'emails';
	const TAB_MEDIA       = 'media';
	const TAB_ADDON_MEDIA = 'addon-media';
	const TAB_IMPORT      = 'import';
	const TAB_LICENSING   = 'licensing';

	/**
	 * The current active tab in the vertical navigation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $active_tab = null;

	/**
	 * Construct Settings manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		/*
		 * Check if the user wants to manually run the Cron services.
		 * This block calls the action 'ms_run_cron_services' which is defined
		 * in MS_Model_Plugin. It will run all cron jobs and re-schedule them.
		 *
		 * @since 1.0.0
		 */
		if ( isset( $_REQUEST['run_cron'] ) ) {
			$url = esc_url_raw( remove_query_arg( 'run_cron' ) );
			do_action( 'ms_run_cron_services', $_REQUEST['run_cron'] );
			wp_safe_redirect( $url );
			exit;
		}

		$this->add_action(
			'ms_controller_membership_setup_completed',
			'auto_setup_settings'
		);

		$this->add_action(
			'admin_action_membership_user_sample_csv',
			'membership_user_sample_csv'
		);

		$this->add_ajax_action( self::AJAX_ACTION_TOGGLE_SETTINGS, 'ajax_action_toggle_settings' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_SETTING, 'ajax_action_update_setting' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_CUSTOM_SETTING, 'ajax_action_update_custom_setting' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_PROTECTION_MSG, 'ajax_action_update_protection_msg' );
		$this->add_ajax_action( self::AJAX_ACTION_TOGGLE_CRON, 'ajax_action_toggle_cron' );
		$this->add_ajax_action( self::AJAX_ACTION_TOGGLE_PROTECTION_FILE, 'ajax_action_toggle_protection_file' );
		$this->add_ajax_action( self::AJAX_ACTION_GENERATE_INVOICE_ID, 'ajax_action_generate_invoice_id' );

		// post actions
		$this->add_action( 'admin_post_' . self::POST_ACTION_UPDATE_LICENSE_KEY, 'post_action_update_license_key' );
		$this->add_action( 'admin_post_' . self::POST_ACTION_DEACTIVATE_LICENSE_KEY, 'post_action_deactivate_license_key' );

	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {
		$hook = MS_Controller_Plugin::admin_page_hook( 'settings' );

		$this->run_action( 'load-' . $hook, 'admin_settings_manager' );
		$this->run_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
		$this->run_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );
	}

	/**
	 * Get settings model
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Settings
	 */
	public function get_model() {
		return MS_Factory::load( 'MS_Model_Settings' );
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * Related action hooks:
	 * * wp_ajax_toggle_settings
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_toggle_settings() {
		$msg = 0;

		$fields = array( 'setting' );
		if ( $this->verify_nonce()
			&& self::validate_required( $fields )
			&& $this->is_admin_user()
		) {
			$msg = $this->save_general(
				$_POST['action'],
				array( $_POST['setting'] => 1 )
			);
		}

		wp_die( $msg ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Save licensing
	 *
	 * @since 1.0.0
	 */
	public function post_action_update_license_key() {
		$post_data = wp_unslash( $_POST );
		if ( isset( $post_data['nonce_save'] ) && wp_verify_nonce( $post_data['nonce_save'], self::POST_ACTION_UPDATE_LICENSE_KEY ) ) {
			$key   = isset( $post_data['license_key'] ) ? sanitize_text_field( $post_data['license_key'] ) : '';
			$email = isset( $post_data['license_email'] ) ? sanitize_text_field( $post_data['license_email'] ) : '';

			$license_controller = MS_Factory::load( 'MS_Licensing' );
			$license_controller->activate( $email, $key );
			$this->save_general(
				'update_setting',
				array(
					'license_key'   => $key,
					'license_email' => $email,
				)
			);

			wp_safe_redirect( admin_url( 'admin.php?page=membership-settings&tab=licensing' ) );
			exit();
		}
	}


	/**
	 * Deactivate licensing
	 *
	 * @since 1.0.0
	 */
	public function post_action_deactivate_license_key() {
		if ( isset( $_POST['nonce_deactivate'] ) && wp_verify_nonce( $_POST['nonce_deactivate'], self::POST_ACTION_DEACTIVATE_LICENSE_KEY ) ) {
			$license_controller = MS_Factory::load( 'MS_Licensing' );
			$result             = $license_controller->deactivate();
			if ( $result ) {
				$this->save_general( 'update_setting', array( 'license_key' => '' ) );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=membership-settings&tab=licensing' ) );
			exit();
		}
	}

	/**
	 * Handle Ajax update setting action.
	 *
	 * Related action hooks:
	 * * wp_ajax_update_setting
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			mslib3()->array->strip_slashes( $_POST, 'value' );

			$msg = $this->save_general(
				$_POST['action'],
				array( $_POST['field'] => $_POST['value'] )
			);

			// Some settings require to flush WP rewrite rules.
			flush_rewrite_rules();
		}

		wp_die( $msg ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Handle Ajax update custom setting action.
	 *
	 * Related action hooks:
	 * * wp_ajax_update_custom_setting
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_custom_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'group', 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$settings = $this->get_model();
			mslib3()->array->strip_slashes( $_POST, 'value' );

			$group = $_POST['group'];
			$field = $_POST['field'];
			$value = $_POST['value'];
			$settings->set_custom_setting( $group, $field, $value );
			$settings->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;

		}

		wp_die( $msg ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Handle Ajax update protection msg.
	 *
	 * Related action hooks:
	 * * wp_ajax_update_protection_msg
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_protection_msg() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		$isset_update = array( 'type', 'value' );
		$isset_toggle = array( 'field', 'value', 'membership_id' );

		// Update a message.
		if ( $this->verify_nonce() && $this->is_admin_user() ) {
			$settings = $this->get_model();

			if ( self::validate_required( $isset_update, 'POST', false ) ) {
				mslib3()->array->strip_slashes( $_POST, 'value' );
				mslib3()->array->equip_post( 'membership_id' );

				$settings->set_protection_message(
					$_POST['type'],
					$_POST['value'],
					$_POST['membership_id']
				);
				$settings->save();
				$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;

				// Toggle a override message flag.
			} elseif ( self::validate_required( $isset_toggle, 'POST', false ) ) {
				$field = $_POST['field'];

				if ( 0 === strpos( $field, 'override_' ) ) {
					$type = substr( $field, 9 );
					if ( mslib3()->is_true( $_POST['value'] ) ) {
						$settings->set_protection_message(
							$type,
							$settings->get_protection_message( $type ),
							$_POST['membership_id']
						);
					} else {
						$settings->set_protection_message(
							$type,
							null,
							$_POST['membership_id']
						);
					}

					$settings->save();
					$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
				}
			}
		}

		wp_die( $msg ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Auto setup settings.
	 *
	 * Fires after a membership setup is completed.
	 * This hook is executed every time a new membership is created.
	 *
	 * Related Action Hooks:
	 * - ms_controller_membership_setup_completed
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Membership $membership
	 */
	public function auto_setup_settings( $membership ) {
		$settings = $this->get_model();

		// Create special pages.
		MS_Model_Pages::create_missing_pages();

		$pg_prot_cont = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_PROTECTED_CONTENT );
		$pg_acco      = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_ACCOUNT );
		$pg_regi      = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_REGISTER );
		$pg_regi_comp = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_REG_COMPLETE );
		$pg_memb      = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_MEMBERSHIPS );

		// Publish special pages.
		// Tip: Only pages must be published that are added to the menu.
		wp_publish_post( $pg_acco->ID );
		if ( ! $membership->private ) {
			wp_publish_post( $pg_memb->ID );
			wp_publish_post( $pg_regi->ID );
		}

		// Create new WordPress menu-items.
		MS_Model_Pages::create_menu( MS_Model_Pages::MS_PAGE_ACCOUNT );
		if ( ! $membership->private ) {
			MS_Model_Pages::create_menu( MS_Model_Pages::MS_PAGE_MEMBERSHIPS );
			MS_Model_Pages::create_menu( MS_Model_Pages::MS_PAGE_REGISTER );
		}

		// Enable Membership.
		$settings->plugin_enabled = true;
		$settings->save();

		// Enable the "Allow user registration" setting of WordPress
		MS_Model_Member::allow_registration();
	}

	/**
	 * Get available tabs for editing the membership.
	 *
	 * @since 1.0.0
	 *
	 * @return array The tabs' configuration.
	 */
	public function get_tabs() {
		$tabs     = array(
			self::TAB_GENERAL     => array(
				'title'     => __( 'General', 'memberdash' ),
				'icon_path' => '
			  		<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
			  		<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
				',
			),
			self::TAB_PAYMENT     => array(
				'title'     => __( 'Payment', 'memberdash' ),
				'icon_path' => '
  					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
				',
			),
			self::TAB_MESSAGES    => array(
				'title'     => __( 'Protection Messages', 'memberdash' ),
				'icon_path' => '
  					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
				',
			),
			self::TAB_EMAILS      => array(
				'title'     => __( 'Automated Email Responses', 'memberdash' ),
				'icon_path' => '
  					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
				',
			),
			self::TAB_ADDON_MEDIA => array(
				'title'     => __( 'Media Protection', 'memberdash' ),
				'icon_path' => '
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
				',
			),
			self::TAB_MEDIA       => array(
				'title'     => __( 'Advanced Media Protection', 'memberdash' ),
				'icon_path' => '
  					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
				',
			),
			self::TAB_IMPORT      => array(
				'title'     => __( 'Import Tool', 'memberdash' ),
				'icon_path' => '
  					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
				',
			),
			self::TAB_LICENSING   => array(
				'title'     => __( 'Licensing', 'memberdash' ),
				'icon_path' => '
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
				',
			),
		);
		$settings = $this->get_model();
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) || ! $settings->is_advanced_media_protection ) {
			unset( $tabs[ self::TAB_MEDIA ] );
		}

		$def_key = MS_Controller_Plugin::MENU_SLUG . '-settings';
		mslib3()->array->equip_get( 'page' );
		$page = sanitize_html_class( $_GET['page'], $def_key );

		foreach ( $tabs as $key => $tab ) {
			$tabs[ $key ]['url'] = sprintf(
				'admin.php?page=%1$s&tab=%2$s',
				esc_attr( $page ),
				esc_attr( $key )
			);
		}

		return apply_filters( 'ms_controller_settings_get_tabs', $tabs, $this );
	}

	/**
	 * Get the current active settings page/tab.
	 *
	 * @since 1.0.0
	 */
	public function get_active_tab() {
		if ( null === $this->active_tab ) {
			if ( ! MS_Controller_Plugin::is_page( 'settings' ) ) {
				$this->active_tab = '';
			} else {
				$tabs = $this->get_tabs();

				reset( $tabs );
				$first_key = key( $tabs );

				// Setup navigation tabs.
				mslib3()->array->equip_get( 'tab' );
				$active_tab = sanitize_html_class( $_GET['tab'], $first_key );

				if ( ! array_key_exists( $active_tab, $tabs ) ) {
					$new_url = esc_url_raw(
						add_query_arg( array( 'tab' => $first_key ) )
					);
					wp_safe_redirect( $new_url );
					exit;
				} else {
					$this->active_tab = apply_filters(
						'ms_controller_settings_get_active_tab',
						$active_tab
					);
				}
			}
		}

		return apply_filters(
			'ms_controller_settings_get_active_tab',
			$this->active_tab,
			$this
		);
	}

	/**
	 * Manages settings actions.
	 *
	 * Verifies GET and POST requests to manage settings.
	 *
	 * @since 1.0.0
	 */
	public function admin_settings_manager() {
		MS_Helper_Settings::print_admin_message();
		$this->get_active_tab();
		$msg      = 0;
		$redirect = false;

		if ( $this->is_admin_user() ) {
			if ( $this->verify_nonce() || $this->verify_nonce( null, 'GET' ) ) {
				/**
				 * After verifying permissions those filters can be used by Add-ons
				 * to process their own settings form.
				 *
				 * @since 1.0.0
				 */
				do_action(
					'ms_admin_settings_manager-' . $this->active_tab
				);
				do_action(
					'ms_admin_settings_manager',
					$this->active_tab
				);

				switch ( $this->active_tab ) {
					case self::TAB_GENERAL:
						mslib3()->array->equip_request( 'action', 'network_site' );
						$action = $_REQUEST['action'];

						$redirect = esc_url_raw(
							remove_query_arg( array( 'msg' => $msg ) )
						);

						// See if we change settings for the network-wide mode.
						if ( MS_Plugin::is_network_wide() ) {
							$new_site_id = intval( $_REQUEST['network_site'] );

							if ( 'network_site' == $action && ! empty( $new_site_id ) ) {
								$old_site_id = MS_Model_Pages::get_setting( 'site_id' );
								if ( $old_site_id != $new_site_id ) {
									MS_Model_Pages::set_setting( 'site_id', $new_site_id );
									$msg      = MS_Helper_Settings::SETTINGS_MSG_SITE_UPDATED;
									$redirect = esc_url_raw(
										add_query_arg( array( 'msg' => $msg ) )
									);
								}
							}
						}
						break;

					case self::TAB_IMPORT:
						/**
						 * Import controller.
						 *
						 * @var MS_Controller_Import $tool
						 */
						$tool = MS_Factory::create( 'MS_Controller_Import' );

						// Output is passed to the view via self::_message().
						$tool->process();
						break;

					case self::TAB_PAYMENT:
					case self::TAB_MESSAGES:
						break;

					default:
						break;
				}
			}
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit();
		}
	}

	/**
	 * Callback function from 'Membership' navigation.
	 *
	 * Menu Item: Membership > Settings
	 *
	 * @since 1.0.0
	 */
	public function admin_page() {
		$hook = 'ms_controller_settings-' . $this->active_tab;

		do_action( $hook );

		$view = MS_Factory::create( 'MS_View_Settings_Edit' );
		$view = apply_filters( $hook . '_view', $view );

		$data             = array();
		$data['tabs']     = $this->get_tabs();
		$data['settings'] = $this->get_model();

		$data['message'] = self::_message();

		if ( isset( $data['message']['error'] ) ) {
			mslib3()->ui->admin_message( $data['message']['error'], 'error', '', 'form_error' );
		}

		switch ( $this->get_active_tab() ) {
			case self::TAB_EMAILS:
				$type = MS_Model_Communication::COMM_TYPE_REGISTRATION;

				$temp_type = isset( $_GET['comm_type'] ) ? $_GET['comm_type'] : '';
				if ( MS_Model_Communication::is_valid_communication_type( $temp_type ) ) {
					$type = $temp_type;
				}

				$comm = MS_Model_Communication::get_communication( $type );

				$data['comm'] = $comm;
				break;

			case self::TAB_IMPORT:
				$url             = wp_nonce_url( admin_url( 'admin.php?action=membership_user_sample_csv' ), 'sample_users_csv' );
				$data['types']   = MS_Model_Export::export_types();
				$data['formats'] = MS_Model_Export::export_formats();
				$data['sample']  = $url;
				break;
		}

		$data        = array_merge( $data, $view->data );
		$view->data  = apply_filters( $hook . '_data', $data );
		$view->model = $this->get_model();
		$view->render();
	}

	/**
	 * Sample CSV file for user import
	 *
	 * @return csv file
	 */
	public function membership_user_sample_csv() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return; }

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'sample_users_csv' ) ) {
			return; }

		$contents  = 'username,email,firstname,lastname,membershipid' . "\r\n";
		$contents .= 'user1,user1@email.com,John,Doe,1' . "\r\n";
		$contents .= 'user2,user2@email.com,Jane,Doe,2';
		mslib3()->net->file_download( $contents, 'ms_sample_user_export.csv' );
	}

	/**
	 * Save general tab settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The action to execute.
	 * @param string $settings Array of settings to which action will be taken.
	 */
	public function save_general( $action, $fields ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		$settings = $this->get_model();

		if ( is_array( $fields ) ) {
			foreach ( $fields as $field => $value ) {
				switch ( $action ) {
					case 'toggle_activation':
					case 'toggle_settings':
						$settings->$field = ! $settings->$field;
						break;

					case 'save_general':
					case 'submit_payment':
					case 'save_downloads':
					case 'save_payment_settings':
					case 'update_setting':
					default:
						$settings->$field = $value;
						break;
				}
			}
			$settings->save();

			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		return apply_filters(
			'ms_controller_settings_save_general',
			$msg,
			$action,
			$fields,
			$this
		);
	}

	/**
	 * Load Membership admin scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$active_tab = $this->get_active_tab();
		do_action( 'ms_controller_settings_enqueue_scripts_' . $active_tab );

		$plugin_url  = MS_Plugin::instance()->url;
		$version     = MS_Plugin::instance()->version;
		$initial_url = MS_Controller_Plugin::get_admin_url();

		$data = array(
			'ms_init'     => array(),
			'initial_url' => $initial_url,
		);

		$data['ms_init'][] = 'view_settings';

		switch ( $active_tab ) {
			case self::TAB_PAYMENT:
				add_thickbox();
				$data['ms_init'][] = 'view_settings_payment';
				break;

			case self::TAB_MESSAGES:
				$data['ms_init'][] = 'view_settings_protection';
				break;

			case self::TAB_EMAILS:
				$data['ms_init'][] = 'view_settings_automated_msg';
				break;

			case self::TAB_GENERAL:
				$data['ms_init'][] = 'view_settings_setup';
				break;

			case self::TAB_ADDON_MEDIA:
				$data['ms_init'][] = 'view_settings_addon_media';
				break;

			case self::TAB_MEDIA:
				$data['ms_init'][] = 'view_settings_media';
				break;
		}
		wp_enqueue_script( 'jquery-ui-datepicker' );
		mslib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

	/**
	 * Load Member manager specific styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		mslib3()->ui->add( 'jquery-ui' );
	}

	/**
	 * Toggle Cron once enabled or disabled
	 *
	 * This actions is called once the switch in the settings is toggled
	 * It calls th action in MS_Model_Plugin
	 *
	 * @since 1.0.3.6
	 */
	public function ajax_action_toggle_cron() {
		if ( $this->verify_nonce( 'toggle_settings' )
			&& $this->is_admin_user()
		) {
			do_action( 'ms_toggle_cron', null );
			wp_send_json_success();
		}
		wp_send_json_error();

	}

	/**
	 * Toggle protection file creation or update
	 * This creates or modifies the .htaccess file in the uploads directory
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_toggle_protection_file() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		if ( $this->verify_nonce( 'toggle_protection_file' )
			&& $this->is_admin_user()
		) {
			$response = MS_Helper_Media::clear_htaccess();
			if ( ! is_wp_error( $response ) ) {
				MS_Model_Addon::toggle_media_htaccess();
				$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
			}
		}
		wp_die( $msg ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}


	/**
	 * Generate Invoice Ids
	 * Ajax action to update ids of past invoices
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_generate_invoice_id() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		if ( $this->verify_nonce() && $this->is_admin_user() ) {
			MS_Addon_Invoice::set_invoice_numeric_id();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}
		wp_die( $msg ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
