<?php
/**
 * Manage Dashboard Widgets.
 *
 * Dashboard Widgets are stored in the directory /app/dashboardwidget
 * Each Dashboard Widget must provide a file called `class-ms-dashboardwidget-<dashboard_widget_name>.php`
 * This file must define class MS_DashboardWidget_<dashboard_widget_name>.
 * This object is responsible to initialize the dashboard widget logic.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Model
 */

/**
 * Class MS_Model_DashboardWidget.
 *
 * @since 1.0.0
 */
class MS_Model_DashboardWidget extends MS_Model_Option {

	private $widgets = array();

	public function __construct() {
		parent::__construct();

		// enqueue scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_scripts' ) );
	}

	public function enqueue_dashboard_styles() {
		$screen = get_current_screen();
		if ( in_array( $screen->id, array( 'memberdash_page_membership-reporting', 'dashboard' ), true ) ) {
			wp_enqueue_style( 'ms-dashboard-styles' );
			wp_enqueue_style( 'daterangepicker' );
		}
	}

	public function enqueue_dashboard_scripts() {
		$screen = get_current_screen();
		if ( in_array( $screen->id, array( 'memberdash_page_membership-reporting', 'dashboard' ), true ) ) {
			wp_enqueue_script( 'ms-dashboard' );
		}
	}

	public function get_widgets() {
		if ( empty( $this->widgets ) ) {
			$this->load_dashboard_widgets();
		}
		return $this->widgets;
	}

	/**
	 * Checks the /app/dashboardwidget directory for a list of all dashboard widgets and load these
	 * files.
	 *
	 * @since 1.0.0
	 */
	public function load_dashboard_widgets() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// check if widgets are already loaded
		if ( ! empty( $this->widgets ) ) {
			return;
		}

		$content_dir = trailingslashit( dirname( dirname( MS_Plugin::instance()->dir ) ) );
		$plugin_dir  = substr( MS_Plugin::instance()->dir, strlen( $content_dir ) );

		$widgets_dirs = array();
		$paths        = MS_Loader::load_paths();
		foreach ( $paths as $path ) {
			$widgets_dirs[] = $plugin_dir . $path . '/dashboardwidget/';
		}

		$widgets_files = array();
		foreach ( $widgets_dirs as $widgets_dir ) {
			$mask    = $content_dir . $widgets_dir . 'class-ms-dashboardwidget-*.php';
			$widgets = glob( $mask );

			foreach ( $widgets as $file ) {
				$widget = basename( $file );
				if ( empty( $widgets_files[ $widget ] ) ) {
					$widget_path              = substr( $file, strlen( $content_dir ) );
					$widgets_files[ $widget ] = $widget_path;
				}
			}

			/**
			 * Allow other plugins/themes to register custom widgets
			 *
			 * @since 1.0.0
			 *
			 * @var array
			 */
			$widgets_files = apply_filters(
				'ms_model_widgets_files',
				$widgets_files
			);
		}

		// Loop all recognized Widgets and initialize them.
		foreach ( $widgets_files as $file ) {
			$widget = $content_dir . $file;

			// Get class-name from file-name
			$class = basename( $file );
			$class = str_replace( '.php', '', $class );
			$class = implode( '_', array_map( 'ucfirst', explode( '-', $class ) ) );
			$class = substr( $class, 6 ); // remove 'Class_' prefix

			if ( file_exists( $widget ) ) {
				if ( ! class_exists( $class ) ) {
					try {
						include_once $widget;
					} catch ( Exception $ex ) {
					}
				}

				if ( class_exists( $class ) ) {
					$this->widgets[ $class ] = MS_Factory::load( $class );
				}
			}
		}

		/**
		 * Allow custom widget initialization code to run
		 *
		 * @since 1.0.0
		 */
		do_action( 'ms_model_dashboard_widget_load' );
	}
}
