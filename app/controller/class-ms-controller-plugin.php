<?php
/**
 * Primary controller for Membership Plugin.
 *
 * This controller is created during the `setup_theme` hook!
 *
 * Responsible for flow control, navigation and invoking other controllers.
 *
 * @since 1.0.0
 *
 * @package    MemberDash
 * @subpackage Controller
 */

/**
 * Primary controller for Membership Plugin.
 *
 * @since 1.0.0
 */
class MS_Controller_Plugin extends MS_Controller {

	/**
	 * Plugin Menu slug.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	const MENU_SLUG = 'membership';

	/**
	 * The slug of the top-level admin page
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private static $base_slug = '';

	/**
	 * Capability required to count as MWPS 'admin' user. Admin users have full
	 * access to all MWPS features.
	 *
	 * @var $capability
	 * @since 1.0.0
	 */
	protected $capability = 'manage_options';

	/**
	 * Instance of MS_Model_Plugin.
	 *
	 * @var $model
	 * @since 1.0.0
	 */
	private $model;

	/**
	 * Pointer array for other controllers.
	 *
	 * @var $controllers
	 * @since 1.0.0
	 */
	protected $controllers = array();

	/**
	 * Stores the callback handler for the submenu items.
	 * It is set by self::route_submenu_request() and is used by
	 * self::handle_submenu_request()
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $menu_handler = null;

	/**
	 * Constructs the primary Plugin controller.
	 *
	 * Created by the MS_Plugin object during the setup_theme action.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		/**
		 * Fix for IE: This is a privacy policy which states, that we do not
		 * collect personal contact information without consent.
		 *
		 * Note that other plugins that output this header later will overwrite
		 * it! So this is a default value if no other file sends the P3P header.
		 *
		 * @since 1.0.0
		 */
		$p3p_done = false;
		foreach ( headers_list() as $header ) {
			if ( false !== stripos( $header, 'P3P:' ) ) {
				$p3p_done = true;
				break;
			}
		}

		// We can not set header, when it's already sent.
		if ( ! $p3p_done && ! headers_sent() ) {
			header( 'P3P:CP="NOI"' );
		}

		/*
		 * Remove the "&msg" attribute from the URL if it was already present in
		 * the previous request.
		 */
		if ( empty( $_POST ) ) {
			/*
			 * No form was submitted:
			 * It's save to redirect the request without losing form-data.
			 */
			if ( isset( $_GET['msg'] )
			&& isset( $_SERVER['HTTP_REFERER'] )
			&& MS_Helper_Utility::is_current_url( $_SERVER['HTTP_REFERER'] )
			) {
				// A msg is set AND the referer URL has the same msg flag!
				$url = esc_url_raw( remove_query_arg( array( 'msg' ) ) );
				wp_safe_redirect( $url );
				exit;
			}
		}

		/**
		 * We allow two ways to modify the default Admin-Capability setting:
		 *
		 * Either by defining the constant in wp-config or by using the filter.
		 * The constant takes priority over the filter.
		 *
		 * @since 1.0.0
		 */
		if ( defined( 'MS_ADMIN_CAPABILITY' ) && MS_ADMIN_CAPABILITY ) {
			$this->capability = MS_ADMIN_CAPABILITY;
		} else {
			$this->capability = apply_filters(
				'ms_admin_user_capability',
				$this->capability
			);
		}

		// Create core controllers that are available on every page.
		$this->model                               = MS_Factory::load( 'MS_Model_Plugin' );
		$this->dialogs                             = MS_Factory::load( 'MS_Controller_Dialog' );
		$this->controllers['widget']               = MS_Factory::load( 'MS_Controller_Widget' );
		$this->controllers['membership']           = MS_Factory::load( 'MS_Controller_Membership' );
		$this->controllers['protection']           = MS_Factory::load( 'MS_Controller_Protection' );
		$this->controllers['rule']                 = MS_Factory::load( 'MS_Controller_Rule' );
		$this->controllers['member']               = MS_Factory::load( 'MS_Controller_Member' );
		$this->controllers['billing']              = MS_Factory::load( 'MS_Controller_Billing' );
		$this->controllers['addon']                = MS_Factory::load( 'MS_Controller_Addon' );
		$this->controllers['pages']                = MS_Factory::load( 'MS_Controller_Pages' );
		$this->controllers['settings']             = MS_Factory::load( 'MS_Controller_Settings' );
		$this->controllers['communication']        = MS_Factory::load( 'MS_Controller_Communication' );
		$this->controllers['gateway']              = MS_Factory::load( 'MS_Controller_Gateway' );
		$this->controllers['admin_bar']            = MS_Factory::load( 'MS_Controller_Adminbar' );
		$this->controllers['membership_metabox']   = MS_Factory::load( 'MS_Controller_Metabox' );
		$this->controllers['membership_shortcode'] = MS_Factory::load( 'MS_Controller_Shortcode' );
		$this->controllers['frontend']             = MS_Factory::load( 'MS_Controller_Frontend' );
		$this->controllers['import']               = MS_Factory::load( 'MS_Controller_Import' );
		$this->controllers['help']                 = MS_Factory::load( 'MS_Controller_Help' );
		$this->controllers['reporting']            = MS_Factory::load( 'MS_Controller_Reporting' );
		$this->controllers['compatibility']        = MS_Factory::load( 'MS_Controller_Compatibility' );
		$this->controllers['telemetry']            = MS_Factory::load( 'MS_Controller_Telemetry' );
		$this->controllers['gutenberg_blocks']     = MS_Factory::load( 'MS_Blocks' );

		// API should be the last Controller to create.
		$this->controllers['api'] = MS_Controller_Api::instance();

		// Load the template-tags.
		require_once MS_Plugin::instance()->dir . 'app/template/template-tags.php';

		// Register all available styles and scripts. Nothing is enqueued.
		$this->add_action( 'wp_loaded', 'wp_loaded' );

		// Setup plugin admin UI.
		$this->add_action( 'admin_menu', 'add_menu_pages' ); // for multisite, it needs too for Protection Rules page
		if ( is_multisite() && MS_Plugin::is_network_wide() ) {
			$this->add_action( 'network_admin_menu', 'add_menu_pages' );
		}

		// Select the right page to display.
		$this->add_action( 'admin_init', 'route_submenu_request' );

		// Plugin policy settings
		$this->add_action( 'admin_init', 'add_privacy_policy_content' );

		// This will do the ADMIN-SIDE initialization of the controllers
		$this->add_action( 'ms_plugin_admin_setup', 'run_admin_init' );

		// Changes the current themes "single" template to the invoice form when an invoice is displayed.
		$this->add_filter( 'single_template', 'custom_single_template' );
		$this->add_filter( 'page_template', 'custom_page_template' );

		// Register styles and javascripts for use in front-end
		$this->add_action( 'ms_register_public_scripts', 'register_public_scripts' );
		$this->add_action( 'ms_register_public_scripts', 'register_public_styles' );
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_plugin_styles' );
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_plugin_scripts' );

		// Register ajax to manage admin notices
		$this->add_ajax_action( 'ms_admin_notice_dismiss', 'ajax_admin_notice_dismiss' );

		/**
		 * Register data exporters and erasers
		 */
		$this->add_filter( 'wp_privacy_personal_data_exporters', 'register_exporter' );
		$this->add_filter( 'wp_privacy_personal_data_erasers', 'register_eraser' );
	}

	public function ajax_admin_notice_dismiss() {
		$notice_id = sanitize_key( isset( $_POST['notice_id'] ) ? $_POST['notice_id'] : '' );
		if ( ! empty( $notice_id ) ) {
			$admin_notices_dismissed = get_user_meta( get_current_user_id(), 'ms_admin_notices_dismissed', true );
			$admin_notices_dismissed = ! empty( $admin_notices_dismissed ) ? $admin_notices_dismissed : array();

			$admin_notices_dismissed[ $notice_id ] = true;
			update_user_meta( get_current_user_id(), 'ms_admin_notices_dismissed', $admin_notices_dismissed );
		}

		wp_send_json_success();
	}

	/**
	 * Creates all the plugin controllers and initialize stuff.
	 *
	 * This is done after admin_menu (when in admin site) or
	 * after setup_theme (on front-end)
	 *
	 * @since 1.0.0
	 */
	public function run_admin_init() {
		if ( ! is_admin() && ! is_network_admin() ) {
			return;
		}

		/*
		 * This function is used to redirect the user to special kind of page
		 * that is not available via the menu.
		 */
		$this->check_special_view();

		foreach ( $this->controllers as $obj ) {
			$obj->admin_init();
		}

		// Register styles and javascripts for use in admin-side
		$this->run_action( 'ms_register_admin_scripts', 'register_admin_scripts' );
		$this->run_action( 'ms_register_admin_scripts', 'register_admin_styles' );
		$this->run_action( 'admin_enqueue_scripts', 'enqueue_plugin_admin_styles' );
		$this->run_action( 'admin_enqueue_scripts', 'enqueue_plugin_admin_scripts' );
	}

	/**
	 * If a special view is active then we ensure that it is displayed now.
	 *
	 * A special view is not accessible via the normal menu structure, like
	 * a Migration assistant or an overview page after updating the plugin.
	 *
	 * Special views can be set/reset/checked via these functions:
	 *   MS_Model_Settings::set_special_view( 'name' );
	 *   MS_Model_Settings::get_special_view();
	 *   MS_Model_Settings::reset_special_view();
	 *
	 * @since 1.0.0
	 */
	protected function check_special_view() {
		$view_name = MS_Model_Settings::get_special_view();

		if ( ! $view_name ) {
			return;
		}

		$view = MS_Factory::load( $view_name );
		if ( $view != null ) {
			$view->enqueue_scripts();

			// Modify the main menu to handle our special_view for default item.
			add_submenu_page(
				self::$base_slug,
				__( 'Membership', 'memberdash' ),
				__( 'Membership', 'memberdash' ),
				$this->capability,
				self::$base_slug,
				array( $this, 'handle_special_view' )
			);
		} else {
			MS_Model_Settings::reset_special_view();
			return;
		}
	}

	/**
	 * Function is only called when a special view is defined. This function
	 * will load that view and display it.
	 *
	 * @since 1.0.0
	 */
	public function handle_special_view() {
		$view_name = MS_Model_Settings::get_special_view();
		$view      = MS_Factory::load( $view_name );

		echo $view->to_html(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Returns the WordPress hook that identifies a Membership admin page.
	 *
	 * Important: In order for this function to work as expected it needs to
	 * be called *after* the admin-menu was registered!
	 *
	 * @param string $subpage
	 *
	 * @since 1.0.0
	 * @return string The internal hook name
	 */
	public static function admin_page_hook( $subpage = '' ) {
		if ( empty( $subpage ) ) {
			$plugin_page = self::MENU_SLUG;
		} else {
			$plugin_page = self::MENU_SLUG . '-' . $subpage;
		}

		if ( ! function_exists( 'get_plugin_page_hookname' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$the_parent = 'admin.php';
		$hook       = get_plugin_page_hookname( $plugin_page, $the_parent );

		return $hook;
	}

	/**
	 * Register scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function wp_loaded() {
		if ( is_admin() || is_network_admin() ) {
			do_action( 'ms_register_admin_scripts' );
		} else {
			do_action( 'ms_register_public_scripts' );
		}
	}

	/**
	 * Adds Dashboard navigation menus.
	 *
	 * @since 1.0.0
	 */
	public function add_menu_pages() {
		global $submenu;
		$limited_mode = false;

		$view = MS_Model_Settings::get_special_view();
		if ( $view ) {
			// A special view is displayed. Do not display other menu items.
			$pages = array();

			$limited_mode = true;
		} elseif ( MS_Plugin::is_wizard() ) {
			// Submenus definition: Wizard mode
			$pages = $this->get_setup_menu_pages();

			$limited_mode = true;
		} else {
			// Submenus definition: Normal mode
			$pages = $this->get_default_menu_pages();

			if ( MS_Plugin::is_network_wide() && ! is_network_admin() ) {
				$limited_mode = true;
			}
		}

		/**
		 * Allow Add-ons and other plugins to add menu pages.
		 *
		 * A menu item is defined by an array containing the following members:
		 *   'title' => '...',
		 *   'slug' => '...',
		 *   'function' => callback
		 *
		 * @var array
		 */
		$pages = apply_filters(
			'ms_plugin_menu_pages',
			$pages,
			$limited_mode,
			$this
		);

		$page_keys = array_keys( $pages );
		$slug      = '';
		if ( isset( $page_keys[0] ) && $pages[ $page_keys[0] ] ) {
			$slug = $pages[ $page_keys[0] ]['slug'];
		}
		if ( empty( $slug ) ) {
			self::$base_slug = self::MENU_SLUG;
		} else {
			self::$base_slug = self::MENU_SLUG . '-' . $slug;
		}

		/*
		 * Create primary menu item: Membership.
		 *
		 * The menu title is not translatable because of a bug in WordPress core
		 * https://core.trac.wordpress.org/ticket/18857
		 * Until this bug is closed the title (2nd argument) can't be translated
		 */
		add_menu_page(
			__( 'MemberDash', 'memberdash' ), // no i18n!
			'MemberDash', // no i18n!. Translating menu could affect screen ids
			$this->capability,
			self::$base_slug,
			null,
			'dashicons-lock'
		);

		// Create submenus
		foreach ( $pages as $page ) {
			if ( ! is_array( $page ) ) {
				continue;
			}

			if ( empty( $page['link'] ) ) {
				$menu_link = false;
			} else {
				$menu_link = $page['link'];
			}

			$slug = self::MENU_SLUG;
			if ( ! empty( $page['slug'] ) ) {
				$slug .= '-' . $page['slug'];
			}

			$page_title   = apply_filters( 'ms_admin_submenu_page_title_' . $slug, $page['title'], $slug, self::$base_slug );
			$menu_title   = apply_filters( 'ms_admin_submenu_menu_title_' . $slug, $page['title'], $slug, self::$base_slug );
			$capability   = apply_filters( 'ms_admin_submenu_capability_' . $slug, $this->capability, $slug, self::$base_slug );
			$submenu_slug = apply_filters( 'ms_admin_submenu_slug_' . $slug, $slug, self::$base_slug );

			add_submenu_page(
				self::$base_slug,
				wp_strip_all_tags( $page_title ),
				$menu_title,
				$capability,
				$submenu_slug,
				array( $this, 'handle_submenu_request' )
			);

			/*
			 * WordPress does not support absolute URLs in the admin-menu.
			 * So we have to many modify the menu-link href value if our slug
			 * is an absolute URL.
			 */
			if ( $menu_link ) {
				$item                                   = end( $submenu[ self::$base_slug ] );
				$key                                    = key( $submenu[ self::$base_slug ] );
				$submenu[ self::$base_slug ][ $key ][2] = $menu_link;
			}
		}

		do_action( 'ms_controller_plugin_add_menu_pages', $this );

		// Setup the rest of the plugin after the menu was registered.
		do_action( 'ms_plugin_admin_setup' );
	}

	/**
	 * Returns the admin menu items for setting up the plugin.
	 * Helper function used by add_menu_pages
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_setup_menu_pages() {
		$pages = array(
			'setup' => array(
				'title' => __( 'Set-up', 'memberdash' ),
				'slug'  => '',
			),
		);

		$step = $this->controllers['membership']->get_step();
		if ( MS_Controller_Membership::STEP_ADD_NEW == $step ) {
			$pages['setup']['slug'] = 'setup';

			$pages[ self::MENU_SLUG ] = array(
				'title' => __( 'Protection Rules', 'memberdash' ),
				'slug'  => '',
			);
		}

		return $pages;
	}

	/**
	 * Returns the default admin menu items for Membership.
	 * Helper function used by add_menu_pages
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_default_menu_pages() {
		$show_billing = false;

		$pages = array(
			'memberships'       => array(
				'title' => __( 'Memberships', 'memberdash' ),
				'slug'  => '',
			),
			'protected-content' => array(
				'title' => __( 'Protection Rules', 'memberdash' ),
				'slug'  => 'protection',
			),
			'members'           => array(
				'title' => __( 'All Members', 'memberdash' ),
				'slug'  => 'members',
			),
			'add-member'        => array(
				'title' => __( 'Add Member', 'memberdash' ),
				'slug'  => 'add-member',
			),
			'billing'           => false,
			'addon'             => array(
				'title' => __( 'Add-ons', 'memberdash' ),
				'slug'  => 'addon',
			),
			'reporting'         => array(
				'title' => __( 'Reporting', 'memberdash' ),
				'slug'  => 'reporting',
			),
			'settings'          => array(
				'title' => __( 'Settings', 'memberdash' ),
				'slug'  => 'settings',
			),
			'help'              => array(
				'title' => __( 'Help', 'memberdash' ),
				'slug'  => 'help',
			),
		);

		$show_billing = MS_Model_Membership::have_paid_membership();

		if ( $show_billing ) {
			$bill_count = MS_Model_Invoice::get_unpaid_invoice_count( null, true );

			if ( $bill_count > 0 ) {
				$msg = '%1$s <span class="update-plugins count-%3$s"><span class="pending-count"><i class="hidden">(</i>%2$s<i class="hidden">)</i></span></span>';
			} else {
				$msg = '%1$s';
			}

			$pages['billing'] = array(
				'title' => sprintf(
					$msg,
					__( 'Billing', 'memberdash' ),
					$bill_count,
					sanitize_html_class( $bill_count, '0' )
				),
				'slug'  => 'billing',
			);

		}

		self::check_active_payment_gateway();

		return $pages;
	}

	/**
	 * Check there is any active payment gateway. If no active payment gateway exists then
	 * It will create an admin notices to let the user know about it. If the function receives
	 * a $recreate_notice parameter set to true, then it will clear the dismissible notices
	 * store in the user meta, and then it will trigger the notices again.
	 *
	 * @since 1.0.2
	 *
	 * @param bool $recreate_notice To decide the recreation of the notice.
	 *
	 * @return void
	 */
	public function check_active_payment_gateway( bool $recreate_notice = false ): void {
		$active_gateways = MS_Model_Gateway::get_gateways( true, true );
		// @var array|false $admin_notices_dismissed
		$admin_notices_dismissed = get_user_meta( get_current_user_id(), 'ms_admin_notices_dismissed', true );
		// @var array|false $admin_notices
		$admin_notices = get_transient( 'memberdash_admin_notices' );

		try {
			$admin_message = sprintf(
				// translators: %1$s The Admin URL. %2$s The anchor/link closing tag.
				__( 'It looks like you haven’t set up a payment gateway yet which means your customers won’t be able to register for paid memberships.<br />%1$sFix this now%2$s', 'memberdash' ),
				'<a href="' . self::get_admin_url( 'settings', array( 'tab' => MS_Controller_Settings::TAB_PAYMENT ) ) . '">',
				'</a>'
			);
			// Filter the notices to display in all the pages or only in MemberDash pages.
			$this->add_filter( 'ms_admin_global_notices', 'ms_filter_global_notices' );
			$this->add_filter( 'ms_notice_pages_view', 'ms_set_notice_pages' );

			$gateway_notice_id = 'ms_admin_gateway_notice';
			$dismiss_id_set    = is_array( $admin_notices_dismissed ) && isset( $admin_notices_dismissed[ $gateway_notice_id ] );

			if ( count( $active_gateways ) === 0 ) {
				if ( $dismiss_id_set && $recreate_notice && is_array( $admin_notices ) ) {
					// Clear the notice dismissed value so that it can be trigger again.
					delete_user_meta( get_current_user_id(), 'ms_admin_notices_dismissed', $admin_notices_dismissed );
					// Delete the notices from the store notices so that it can be display again.
					foreach ( $admin_notices as $key => $notice ) {
						if ( isset( $notice['id'] ) && $notice['id'] === $gateway_notice_id ) {
							unset( $admin_notices[ $key ] );
						}
					}
					// Store the updated notice array.
					set_transient( 'memberdash_admin_notices', $admin_notices );
				}
				mslib3()->ui->admin_message( $admin_message, 'warning', '', $gateway_notice_id );

			} else {
				$admin_notices_dismissed = get_user_meta( get_current_user_id(), 'ms_admin_notices_dismissed', true );
				$admin_notices_dismissed = ! empty( $admin_notices_dismissed ) ? $admin_notices_dismissed : array();

				/**
				 * The array with notices or an empty array since we normalized it above.
				 *
				 * @var array<string> $admin_notices_dismissed
				 */
				$admin_notices_dismissed[ $gateway_notice_id ] = true;
				update_user_meta( get_current_user_id(), 'ms_admin_notices_dismissed', $admin_notices_dismissed );
			}
		} catch ( Exception $ex ) {
			MS_Helper_Debug::debug_log( $ex->getMessage() );
		}
	}

	/**
	 * This will return an array of MemberDash pages that will be use by the notice creation
	 * in `mslib3()->ui->_admin_notice_callback`
	 *
	 * @since 1.0.2
	 *
	 * @return array<string> The MemberDash admin pages.
	 */
	public function ms_set_notice_pages(): array {
		return array(
			self::admin_page_hook(),
			self::admin_page_hook( 'members' ),
			self::admin_page_hook( 'protection' ),
			self::admin_page_hook( 'add-member' ),
			self::admin_page_hook( 'billing' ),
			self::admin_page_hook( 'coupons' ),
			self::admin_page_hook( 'addon' ),
			self::admin_page_hook( 'reporting' ),
			self::admin_page_hook( 'settings' ),
			self::admin_page_hook( 'help' ),
		);
	}

	/**
	 * To be use by the filter `ms_notice_pages_view` to prevent
	 * the notice to be visible in all the pages.
	 *
	 * @since 1.0.2
	 *
	 * @return bool False to display only in MemberDash pages.
	 */
	public function ms_filter_global_notices(): bool {
		return false;
	}

	/**
	 * Handles all menu-items and calls the correct callback function.
	 *
	 * We introduce this routing function to monitor all menu-item calls so we
	 * can make sure that network-wide protection loads the correct blog or
	 * admin-area before displaying the page.
	 *
	 * This function will only handle submenu items of the Membership menu!
	 *
	 * @since 1.0.0
	 */
	public function route_submenu_request() {
		global $submenu;
		$handler   = null;
		$handle_it = false;

		if ( ! isset( $_GET['page'] ) ) {
			return;
		}
		if ( $_GET['page'] === self::$base_slug ) {
			$handle_it = true;
		} elseif ( isset( $submenu[ self::$base_slug ] ) ) {
			foreach ( $submenu[ self::$base_slug ] as $item ) {
				if ( $_GET['page'] === $item[2] ) {
					$handle_it = true;
					break;
				}
			}
		}
		if ( ! $handle_it ) {
			return;
		}

		if ( MS_Plugin::is_wizard() ) {
			$step_add = MS_Controller_Membership::STEP_ADD_NEW == MS_Plugin::instance()->settings->wizard_step;

			if ( ! $step_add || self::is_page( 'setup' ) ) {
				$handler = array(
					'any',
					array( $this->controllers['membership'], 'admin_page_router' ),
				);
			} else {
				$handler = array(
					'site',
					array( $this->controllers['protection'], 'admin_page' ),
				);
			}
		} else {
			if ( self::is_page( '' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['membership'], 'admin_page_router' ),
				);
			} elseif ( self::is_page( 'protection' ) ) {
				$handler = array(
					'site',
					array( $this->controllers['protection'], 'admin_page' ),
				);
			} elseif ( self::is_page( 'members' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['member'], 'admin_page' ),
				);
			} elseif ( self::is_page( 'add-member' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['member'], 'admin_page_editor' ),
				);
			} elseif ( self::is_page( 'addon' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['addon'], 'admin_page' ),
				);
			} elseif ( self::is_page( 'reporting' ) ) {
				$handler = array(
					'any',
					array( $this->controllers['reporting'], 'admin_page' ),
				);
			} elseif ( self::is_page( 'settings' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['settings'], 'admin_page' ),
				);
			} elseif ( self::is_page( 'help' ) ) {
				$handler = array(
					'any',
					array( $this->controllers['help'], 'admin_page' ),
				);
			} elseif ( self::is_page( 'billing' ) ) {
				$handler = array(
					'network',
					array( $this->controllers['billing'], 'admin_page' ),
				);
			}
		}

		/**
		 * Filter that allows Add-ons to add their own sub-menu handlers.
		 *
		 * @since 1.0.0
		 */
		$handler = apply_filters(
			'ms_route_submenu_request',
			$handler,
			$this
		);

		// Provide a fallback handler in case we could not identify the handler.
		if ( ! $handler ) {
			$handler = array(
				'network',
				array( $this->controllers['membership'], 'membership_admin_page_router' ),
			);
		}

		// Handle the target attribute specified in $handler[0]
		if ( MS_Plugin::is_network_wide() && 'any' != $handler[0] ) {
			$redirect     = false;
			$admin_script = 'admin.php?' . $_SERVER['QUERY_STRING'];

			if ( 'network' == $handler[0] && ! is_network_admin() ) {
				$redirect = network_admin_url( $admin_script );
			} elseif ( 'site' == $handler[0] && is_network_admin() ) {
				$redirect = admin_url( $admin_script );
			}

			if ( $redirect ) {
				if ( headers_sent() ) {
					echo '<script>location.href=' . wp_json_encode( $redirect ) . ';</script>';
				} else {
					wp_safe_redirect( $redirect );
				}

				exit;
			}
		}

		$this->menu_handler = $handler;
	}

	/**
	 * Add Privacy policy details
	 *
	 * @since 1.1.5
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = __(
			'<h3>Payment Gateways</h3>
		<p class="privacy-policy-tutorial">
			If you choose to accept Payments using Stripe, or PayPal, some of your customers data will be passed to the respective third party.
			Some of the data includes :
			<ul>
				<li>Name</li>
				<li>Email</li>
				<li>Address</li>
				<li>Phone</li>
				<li>City/State/Zip</li>
			</ul>
			We will store order information for XXX years for tax and accounting purposes. This includes your name, email address and billing addresses.
		</p>

		<h3>Who on our team has access</h3>
		<p class="privacy-policy-tutorial">
			Members of our team have access to the information you provide us. Both Administrators and Shop Managers can access:
			<ul>
				<li>Order information like what was purchased, when it was purchased and</li>
				<li>Customer information like your name, email address, and billing information.</li>
			</ul>
		</p>',
			'memberdash'
		);

		wp_add_privacy_policy_content(
			'Membership',
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/**
	 * Simply calls the menu-handler callback function.
	 *
	 * This function was determined by the previous call to
	 * self::route_submenu_request() during the admin_init hook.
	 *
	 * @since 1.0.0
	 */
	public function handle_submenu_request() {
		if ( ! empty( $this->menu_handler ) ) {
			// This function will actually render the requested page!
			call_user_func( $this->menu_handler[1] );
		}
	}

	/**
	 * Checks if the current user is on the specified Membership admin page.
	 *
	 * @param string $slug The membership slug (without the menu-slug prefix)
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_page( $slug ) {
		$curpage = false;
		if ( isset( $_REQUEST['page'] ) ) {
			$curpage = sanitize_html_class( $_REQUEST['page'] );
		}

		if ( empty( $slug ) ) {
			$slug = self::$base_slug;
		} else {
			$slug = self::MENU_SLUG . '-' . $slug;
		}

		return $curpage == $slug;
	}

	/**
	 * Checks if the current user is on any Membership admin page.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_admin_page() {
		$curpage = false;
		if ( isset( $_REQUEST['page'] ) ) {
			$curpage = sanitize_html_class( $_REQUEST['page'] );
		}

		$slug = self::$base_slug;

		return ( strpos( $curpage, $slug ) !== false );
	}

	/**
	 * Get admin url.
	 *
	 * @param string $slug Optional. Slug of the admin page, if empty the link
	 *                     points to the main admin page.
	 *
	 * @since 1.0.0
	 * @return string The full URL to the admin page.
	 */
	public static function get_admin_url( $slug = '', $args = null ) {
		$base_slug = self::$base_slug;

		// These slugs are opened in network-admin for network-wide protection.
		$global_slugs = array(
			'memberships',
			'addon',
			'settings',
		);

		// Determine if the slug is opened in network-admin or site admin.
		$network_slug = MS_Plugin::is_network_wide()
						&& ( in_array( $slug, $global_slugs ) || is_network_admin() );

		if ( $network_slug ) {
			$base_slug = self::MENU_SLUG;
			if ( 'memberships' === $slug ) {
				$slug = '';
			}
		}

		if ( 'MENU_SLUG' == $slug ) {
			$slug = self::MENU_SLUG;
		} elseif ( empty( $slug ) ) {
			$slug = self::$base_slug;
		} else {
			$slug = self::MENU_SLUG . '-' . $slug;
		}

		if ( ! $slug ) {
			$slug = self::MENU_SLUG;
		}

		if ( $network_slug ) {
			$url = network_admin_url( 'admin.php?page=' . $slug );
		} else {
			$url = admin_url( 'admin.php?page=' . $slug );
		}

		if ( $args ) {
			$url = esc_url_raw( add_query_arg( $args, $url ) );
		}

		return apply_filters(
			'ms_controller_plugin_get_admin_url',
			$url
		);
	}

	/**
	 * Get admin settings url.
	 *
	 * @since 1.0.0
	 */
	public static function get_admin_settings_url() {
		return apply_filters(
			'ms_controller_plugin_get_admin_url',
			admin_url( 'admin.php?page=' . self::MENU_SLUG . '-settings' )
		);
	}

	/**
	 * Use a special template for our custom post types.
	 *
	 * Invoices:
	 * Replaces the themes "Single" template with our invoice template when an
	 * invoice is displayed. The theme can override this by defining its own
	 * mwps-invoice.php / single-ms-invoice.php template.
	 *
	 * You can even specify a membership ID in the page template to create
	 * a custom invoice form based on the membership that is billed.
	 * Example:
	 *     mwps-invoice-100.php (Invoice form for membership 100)
	 *
	 * @param string $template The template path to filter.
	 *
	 * @since 1.0.0
	 * @see    filter single_template
	 *
	 * @return string The template path.
	 */
	public function custom_single_template( $default_template ) {
		global $post;
		$template = '';

		// Checks for invoice single template.
		if ( $post->post_type == MS_Model_Invoice::get_post_type() ) {
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $post->ID );

			// First look for themes 'mwps-invoice-100.php' template (membership ID).
			$template = get_query_template(
				'mwps',
				array( 'mwps-invoice-' . $invoice->membership_id . '.php' )
			);

			// Fallback to themes 'mwps-invoice.php' template.
			if ( ! $template ) {
				$template = get_query_template(
					'mwps',
					array( 'mwps-invoice.php' )
				);
			}

			// Second look for themes 'single-ms-invoice.php' template.
			if ( ! $template && strpos( $default_template, '/single-ms-invoice.php' ) ) {
				$template = $default_template;
			}

			// Last: Use the default MWPS invoice template.
			if ( ! $template ) {
				$invoice_template = apply_filters(
					'ms_controller_plugin_invoice_template',
					MS_Plugin::instance()->dir . 'app/template/single-ms-invoice.php'
				);

				if ( file_exists( $invoice_template ) ) {
					$template = $invoice_template;
				}
			}
		}

		if ( ! $template ) {
			$template = $default_template;
		}

		return $template;
	}

	/**
	 * Use a special template for our membership pages.
	 *
	 * Recognized templates are:
	 *     mwps-memberships.php
	 *     mwps-protected-content.php
	 *     mwps-account.php
	 *     mwps-register.php
	 *     mwps-registration-complete.php
	 *
	 * Note that certain pages receive a membership-ID when they are loaded
	 * (like the mwps-registration-complete or mwps-register pages).
	 * You can even specify special pages for each membership.
	 *
	 * Example:
	 *     mwps-register-100.php (register form for membership 100)
	 *     mwps-registration-complete-100.php (thank you page for membership 100)
	 *
	 * @param string $template The default template path to filter.
	 *
	 * @since 1.0.0
	 * @see    filter page_template
	 *
	 * @return string The custom template path.
	 */
	public function custom_page_template( $default_template ) {
		$template = '';

		// Checks for invoice single template.
		$type = MS_Model_Pages::is_membership_page();
		if ( $type ) {
			$membership_id = apply_filters(
				'ms_detect_membership_id',
				0,
				true,
				true
			);

			if ( $membership_id ) {
				$template = get_query_template(
					'mwps',
					array( 'mwps-' . $type . '-' . $membership_id . '.php' )
				);
			}

			if ( ! $template ) {
				$template = get_query_template(
					'mwps',
					array( 'mwps-' . $type . '.php' )
				);
			}
		}

		if ( ! $template ) {
			$template = $default_template;
		}

		return $template;
	}

	/**
	 * Returns information on current memberships and access to current page.
	 *
	 * Wrapper for MS_Model_Plugin->get_access_info()
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_access_info() {
		return $this->model->get_access_info();
	}

	/**
	 * Returns a list with complete admin menu items.
	 *
	 * Wrapper for MS_Model_Plugin->get_admin_menu()
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_admin_menu() {
		return $this->model->get_admin_menu();
	}

	/**
	 * Register scripts that are used on the dashboard.
	 *
	 * @since 1.0.0
	 */
	public function register_admin_scripts() {
		$plugin_url = MS_Plugin::instance()->url;
		$version    = MS_Plugin::instance()->version;

		wp_register_script(
			'ms-admin-memberdash',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/mwps.memberdash-ui.3' ),
			array( 'jquery' ),
			$version,
			true
		);

		// Helper functions for ms-admin.
		wp_register_script(
			'memberdash-helper',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/memberdash-helper' ),
			array( 'jquery', 'ms-admin-memberdash' ),
			$version,
			true
		);

		// The main plugin script.
		wp_register_script(
			'ms-admin',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/ms-admin' ),
			array( 'jquery', 'ms-admin-memberdash', 'memberdash-helper' ),
			$version,
			true
		);

		wp_register_script(
			'mwps-jquery-plugins',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/jquery.mwps.plugins' ),
			array( 'jquery' ),
			$version,
			true
		);

		if ( ! wp_script_is( 'jquery-validate', 'registered' ) ) {
			wp_register_script(
				'jquery-validate',
				$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/jquery.mwps.validate' ),
				array( 'jquery' ),
				$version,
				true
			);
		}

		if ( ! wp_script_is( 'moment', 'registered' ) ) {
			wp_register_script(
				'moment',
				$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/lib/moment.min' ),
				null,
				'2.18.1',
				true
			);
		}

		if ( ! wp_script_is( 'daterangepicker', 'registered' ) ) {
			wp_register_script(
				'daterangepicker',
				$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/lib/daterangepicker.min' ),
				array( 'moment' ),
				'3.14.1',
				true
			);
		}

		// The Dashboard Widget script.
		wp_register_script(
			'ms-dashboard',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/ms-dashboard' ),
			array( 'jquery', 'moment', 'daterangepicker' ),
			$version,
			true
		);

		// Localize text.
		wp_localize_script(
			'ms-admin',
			'ms_admin_text',
			$this->admin_localize_text()
		);

		wp_localize_script(
			'ms-dashboard',
			'ms_text',
			$this->dashboard_localize_text()
		);
	}

	private function dashboard_localize_text() {
		return array(
			'select_report_type_warning' => __( 'Please select a report type.', 'memberdash' ),
		);
	}

	/**
	 * Translatable strings array.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function admin_localize_text() {
		$messages = array(
			'recurring_cancel_warning'    => esc_html__( 'Please note, if this membership has an active subscription in payment gateway, it will be canceled and you may not be able to re-activate it later.', 'memberdash' ),
			'membership_multiple_limit'   => MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ? 0 : 1,
			'membership_multiple_message' => esc_html__( 'Multiple memberships feature is not enabled.', 'memberdash' ),
		);

		/**
		 * Filter to add translatable text to admin scripts.
		 *
		 * @param array Text.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'ms_controller_plugin_localize_admin', $messages );
	}

	/**
	 * Register styles that are used on the dashboard.
	 *
	 * @since 1.0.0
	 */
	public function register_admin_styles() {
		$plugin_url = MS_Plugin::instance()->url;
		$version    = MS_Plugin::instance()->version;

		// The main plugin style.
		wp_register_style(
			'ms-admin-styles',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/css/ms-admin', 'css' ),
			null,
			$version
		);

		// The dashboard style.
		wp_register_style(
			'ms-dashboard-styles',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/css/ms-dashboard', 'css' ),
			null,
			$version
		);

		// Daterangepicker style.
		if ( ! wp_style_is( 'daterangepicker', 'registered' ) ) {
			wp_register_style(
				'daterangepicker',
				$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/css/lib/daterangepicker.min', 'css' ),
				null,
				'3.14.1'
			);
		}

		// Custom tailwind style.
		wp_register_style(
			'ms-admin-tailwind',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/css/ms-tailwind', 'css' ),
			null,
			$version
		);
	}

	/**
	 * Register scripts that are used on the front-end.
	 *
	 * @since 1.0.0
	 */
	public function register_public_scripts() {
		$plugin_url = MS_Plugin::instance()->url;
		$version    = MS_Plugin::instance()->version;

		wp_register_script(
			'ms-ajax-login',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/ms-public-ajax' ),
			array( 'jquery' ),
			$version,
			true // last param forces script to load in footer
		);

		wp_register_script(
			'ms-public',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/ms-public' ),
			array( 'jquery' ),
			$version,
			true
		);

		wp_register_script(
			'mwps-jquery-plugins',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/jquery.mwps.plugins' ),
			array( 'jquery' ),
			$version,
			true
		);

		wp_register_script(
			'jquery-validate',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/jquery.mwps.validate' ),
			array( 'jquery' ),
			$version,
			true
		);

		wp_register_script(
			'ms-admin-memberdash',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/mwps.memberdash-ui.3' ),
			array( 'jquery' ),
			$version,
			true
		);

		// The main plugin script.
		wp_register_script(
			'ms-admin',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/js/ms-admin' ),
			array( 'jquery', 'jquery-validate', 'mwps-jquery-plugins', 'ms-admin-memberdash' ),
			$version,
			true
		);

	}

	/**
	 * Register styles that are used on the front-end.
	 *
	 * @since 1.0.0
	 */
	public function register_public_styles() {
		$plugin_url = MS_Plugin::instance()->url;
		$version    = MS_Plugin::instance()->version;

		// The main plugin style.
		wp_register_style(
			'ms-styles',
			$plugin_url . MS_Helper_Utility::asset_link( 'app/assets/css/ms-public', 'css' ),
			array(),
			$version
		);
	}

	/**
	 * Adds CSS for Membership settings pages.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_plugin_admin_styles( $hook ) {
		// Load only on membership pages
		$screen = get_current_screen();
		if ( strpos( $screen->id, 'membership' ) !== false ) {
			mslib3()->ui->css( 'ms-admin-styles' );
			mslib3()->ui->css( 'ms-admin-tailwind' );
			mslib3()->ui->add( 'core' );
			wp_dequeue_script( 'memberdash-memberdash-ui-3-min-js' );
			wp_deregister_script( 'memberdash-memberdash-ui-3-min-js' );
			wp_dequeue_script( 'memberdash-memberdash-ui-3-js' );
			wp_deregister_script( 'memberdash-memberdash-ui-3-js' );
			mslib3()->ui->add( 'select' );
			mslib3()->ui->add( 'fontawesome' );
		}
	}

	/**
	 * Adds CSS for Membership pages used in the front end.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_plugin_styles() {
		// Front-End styles are enqueued by MS_Controller_Frontend.
	}

	/**
	 * Register JavaScript for Membership settings pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_plugin_admin_scripts( $hook ) {
		// Load only on membership pages
		$screen = get_current_screen();
		if ( empty( $screen->id ) ) {
			return;
		}

		// Load scripts only on mwps pages.
		if ( strpos( $screen->id, 'memberdash' ) !== false ) {
			// Missing scripts needed for the meta box
			mslib3()->ui->js( 'mwps-jquery-plugins' );
			if ( self::is_admin_page() ) {
				mslib3()->ui->js( 'jquery-validate' );
			}
			mslib3()->ui->js( 'ms-admin' );
			mslib3()->ui->add( 'select' );
		}
	}

	/**
	 * Adds JavaScript for Membership pages used in the front end.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_plugin_scripts() {
		// Front-End scripts are enqueued by MS_Controller_Frontend.
	}

	/**
	 * Adds a javascript to the page that will translate the jQuery validator
	 * messages.
	 *
	 * @since 1.0.0
	 */
	public static function translate_jquery_validator() {
		ob_start();
		?>
		jQuery.extend( jQuery.validator.messages, {
		required: "<?php esc_html_e( 'This field is required.', 'memberdash' ); ?>",
		remote: "<?php esc_html_e( 'Please fix this field.', 'memberdash' ); ?>",
		email: "<?php esc_html_e( 'Please enter a valid email address.', 'memberdash' ); ?>",
		url: "<?php esc_html_e( 'Please enter a valid URL.', 'memberdash' ); ?>",
		date: "<?php esc_html_e( 'Please enter a valid date.', 'memberdash' ); ?>",
		dateISO: "<?php esc_html_e( 'Please enter a valid date ( ISO ).', 'memberdash' ); ?>",
		number: "<?php esc_html_e( 'Please enter a valid number.', 'memberdash' ); ?>",
		digits: "<?php esc_html_e( 'Please enter only digits.', 'memberdash' ); ?>",
		creditcard: "<?php esc_html_e( 'Please enter a valid credit card number.', 'memberdash' ); ?>",
		equalTo: "<?php esc_html_e( 'Please enter the same value again.', 'memberdash' ); ?>",
		maxlength: jQuery.validator.format( "<?php esc_html_e( 'Please enter no more than {0} characters.', 'memberdash' ); ?>" ),
		minlength: jQuery.validator.format( "<?php esc_html_e( 'Please enter at least {0} characters.', 'memberdash' ); ?>" ),
		rangelength: jQuery.validator.format( "<?php esc_html_e( 'Please enter a value between {0} and {1} characters long.', 'memberdash' ); ?>" ),
		range: jQuery.validator.format( "<?php esc_html_e( 'Please enter a value between {0} and {1}.', 'memberdash' ); ?>" ),
		max: jQuery.validator.format( "<?php esc_html_e( 'Please enter a value less than or equal to {0}.', 'memberdash' ); ?>" ),
		min: jQuery.validator.format( "<?php esc_html_e( 'Please enter a value greater than or equal to {0}.', 'memberdash' ); ?>" )
		});
		<?php
		$script = ob_get_clean();
		mslib3()->ui->script( $script );
	}

	/**
	 * Register exporters
	 *
	 * @param array $exporters - current registered exporters
	 *
	 * @since 1.1.5
	 *
	 * @return array $exporters
	 */
	public function register_exporter( $exporters ) {
		$exporters['membership'] = array(
			'exporter_friendly_name' => __( 'Membership Exporter', 'memberdash' ),
			'callback'               => array( $this, 'add_export_data' ),
		);
		return $exporters;

	}

	/**
	 * Register erasers
	 *
	 * @param array $erasers - current registered erasers
	 *
	 * @since 1.1.5
	 *
	 * @return array $erasers
	 */
	public function register_eraser( $erasers ) {
		$erasers['membership'] = array(
			'eraser_friendly_name' => __( 'Membership Eraser', 'memberdash' ),
			'callback'             => array( $this, 'erase_data' ),
		);
		return $erasers;
	}

	/**
	 * Add export data
	 *
	 * @param string $email_address - current email address
	 * @param int    $page          - current page
	 *
	 * @since 1.1.5
	 *
	 * @return array
	 */
	public function add_export_data( $email_address, $page = 1 ) {
		$user     = get_user_by( 'email', $email_address );
		$exporter = new MS_Model_Export_Members();
		$data     = $exporter->member_data( $user->ID );
		return array(
			'data' => $data,
			'done' => true,
		);
	}

	/**
	 * Erase data
	 *
	 * @param string $email_address - current email address
	 * @param int    $page          - current page
	 *
	 * @since 1.1.5
	 *
	 * @return array
	 */
	public function erase_data( $email_address, $page = 1 ) {
		$user          = get_user_by( 'email', $email_address );
		$member        = MS_Factory::load( 'MS_Model_Member', $user->ID );
		$items_removed = 0;
		foreach ( $member->subscriptions as $sub ) {
			$sub->delete();
			$items_removed++;
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => array( __( 'All Subscription Data deleted', 'memberdash' ) ),
			'done'           => true,
		);
	}
}
