<?php
/**
 * Dashboard Widgets controller
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Controller
 */
abstract class MS_DashboardWidget extends MS_Controller {

	const AJAX_DATERANGE = 'ms_daterange';

	/**
	 * Reference to the MS_Model_Reporting instance.
	 *
	 * @type MS_Model_Reporting
	 */
	protected static $reporting_model = null;

	/**
	 * Initialize the Dashboard Widget.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		// initialize the dashboard widget
		$this->init();

		// register the dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'register' ) );

		self::$reporting_model = MS_Factory::load( 'MS_Model_Reporting' );

		$this->add_ajax_action( $this->ajax_datarange_action(), array( $this, 'ajax_process_daterange' ) );
	}

	/**
	 * Register the Dashboard Widget.
	 */
	public function register() {
		wp_add_dashboard_widget(
			$this->get_id(),
			$this->get_name(),
			array( $this, 'render' )
		);
	}

	/**
	 * Returns the Widget ID
	 *
	 * @since 1.0.0
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * Returns the Widget title.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Render the widget.
	 *
	 * @since 1.0.0
	 * @return void HTML content
	 */
	abstract public function render();


	/**
	 * Initializes the Widget. Always executed.
	 *
	 * @since 1.0.0
	 */
	abstract public function init();

	public function ajax_process_daterange() {
		$data = array(
			'start_date' => isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '',
			'end_date'   => isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '',
		);

		// validating parameters
		if ( empty( $data['start_date'] ) || empty( $data['end_date'] ) ) {
			esc_html_e( 'Invalid parameters.', 'memberdash' );
			wp_die();
		}

		$this->ajax_process_daterange_callback( $data );
		wp_die();
	}

	/**
	 * Render the date range ajax block id.
	 * Must be implemented by the child class.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function ajax_process_daterange_callback( $data ) {
		trigger_error( 'ajax_process_daterange_callback() not implemented by Dashboard Widget', E_USER_WARNING ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
	}

	/**
	 * Returns the Ajax action string used to process data range.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function ajax_datarange_action() {
		return self::AJAX_DATERANGE . '-' . $this->get_id();
	}

	protected function get_datarange_start_date( $data = array() ) {
		return ! empty( $data['start_date'] ) ? $data['start_date'] : ( new Datetime( 'now', wp_timezone() ) )->modify( '-' . $data['start_date_before_default'] . ' days' )->format( 'Y-m-d' );
	}
	protected function get_datarange_end_date( $data = array() ) {
		return ! empty( $data['end_date'] ) ? $data['end_date'] : ( new Datetime( 'now', wp_timezone() ) )->format( 'Y-m-d' );
	}

	/**
	 * Render a date range selector.
	 *
	 * @since 1.0.0
	 * @param string $ajax_block_id The ID of the HTML element that has to be updated by AJAX.
	 * @param int    $start_days_before The number of days before today as the default start date. Default: 6.
	 * @return void HTML content
	 */
	protected function render_daterange( $ajax_block_id, $start_days_before = 6 ) {
		$daterange_id = 'daterange-' . $this->get_id();
		?>
		<div class="memberdash-wp-dashboard-daterange-wrapper ms-my-2 ms-h-10 ms-border-0">
			<div id="<?php echo esc_attr( $daterange_id ); ?>"
				style="display: flex; justify-content: space-between"
				class="ms-daterange memberdash-wp-dashboard-daterange  memberdash-field-input ms-rounded-md ms-h-10 ms-flex ms-flex-row ms-items-center ms-justify-between"
				data-ajax-action="<?php echo esc_attr( $this->ajax_datarange_action() ); ?>"
				data-start-days-before="<?php echo esc_attr( $start_days_before ); ?>"
				data-ajax-block-id="<?php echo esc_attr( $ajax_block_id ); ?>">
				<div><i class="dashicons dashicons-calendar-alt"></i>&nbsp;<span></span></div>
				<i class="dashicons dashicons-arrow-down-alt2"></i>
			</div>
		</div>
		<span id="spinner-<?php echo esc_attr( $daterange_id ); ?>" class="spinner" style="float: inherit;"></span>
		<div id="error-<?php echo esc_attr( $daterange_id ); ?>" style="display: none">
			<div class="memberdash-wp-dashboard-daterange-error">
				<i class="dashicons dashicons-warning"></i>
				<span><?php esc_html_e( 'We could not update this report at this time. Please refresh the page to try again.', 'memberdash' ); ?></span>
			</div>
		</div>
		<?php
	}
}
