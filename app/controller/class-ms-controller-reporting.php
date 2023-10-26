<?php

/**
 * Controller for managing reportings.
 *
 * @since 1.0.0
 *
 * @package    MemberDash
 * @subpackage Controller
 */
class MS_Controller_Reporting extends MS_Controller {

	/**
	 * Reporting types
	 */
	const REPORT_TYPE_NEW_USERS                      = 'new_users';
	const REPORT_TYPE_NEW_PAYING_USERS               = 'new_paying_users';
	const REPORT_TYPE_PAYING_USERS_NO_LOGIN_3_MONTHS = 'paying_users_no_login_3_months';


	const ACTION_CSV_DOWNLOAD = 'ms_reporting_csv_download';

	/**
	 * Prepare the Member manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_action(
			'admin_action_' . self::ACTION_CSV_DOWNLOAD,
			'download_csv_report'
		);
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {
		$hook = MS_Controller_Plugin::admin_page_hook( 'reporting' );
	}

	/**
	 * Export report as CSV.
	 *
	 * @return csv file
	 */
	public function download_csv_report() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'ms_reporting_csv_export' ) ) {
			return;
		}

		$report_type = isset( $_REQUEST['report_type'] ) ? $_REQUEST['report_type'] : '';
		if ( empty( $report_type ) ) {
			return;
		}

		$start_date = isset( $_REQUEST['start_date'] ) ? $_REQUEST['start_date'] : '';
		$end_date   = isset( $_REQUEST['end_date'] ) ? $_REQUEST['end_date'] : '';
		$membership = isset( $_REQUEST['membership'] ) ? $_REQUEST['membership'] : '';
		$gateway    = isset( $_REQUEST['gateway'] ) ? $_REQUEST['gateway'] : '';

		$report_data     = null;
		$report_filename = null;
		$reporting_model = MS_Factory::load( 'MS_Model_Reporting' );
		switch ( $report_type ) {
			case self::REPORT_TYPE_NEW_USERS:
				if ( $start_date && $end_date ) {
					$report_data     = $reporting_model->get_new_users_by_date_range( $start_date, $end_date );
					$report_filename = 'new_users.csv';
				}
				break;
			case self::REPORT_TYPE_NEW_PAYING_USERS:
				if ( $start_date && $end_date ) {
					$report_data     = $reporting_model->get_new_paying_users_by_filter( $start_date, $end_date, $membership, $gateway );
					$report_filename = 'new_paying_users.csv';
				}
				break;
			case self::REPORT_TYPE_PAYING_USERS_NO_LOGIN_3_MONTHS:
				$report_data     = $reporting_model->get_paying_users_no_login_3_months();
				$report_filename = 'paying_users_no_login_3_months.csv';
				break;
			default:
				return;
		}

		if ( ! empty( $report_data ) ) {
			$reporting_model->download_csv_data( $report_data, $report_filename );
		}

	}

	/**
	 * Show Reporting page
	 *
	 * Called by MS_Controller_Plugin::route_submenu_request()
	 *
	 * @since 1.0.0
	 */
	public function admin_page() {
		$data       = array(
			'widgets'          => MS_Factory::load( 'MS_Model_DashboardWidget' )->get_widgets(),
			'report_type'      => $this->get_report_type(),
			'memberships'      => $this->get_memberships(),
			'gateways'         => $this->get_gateways(),
			'download_csv_url' => $this->get_url_csv_download(),
		);
		$view       = MS_Factory::create( 'MS_View_Reporting' );
		$view->data = apply_filters( 'ms_view_reporting_data', $data, $this );
		$view->render();
	}

	public function get_report_type() {
		return array(
			''                                 => __( 'Select report type', 'memberdash' ),
			self::REPORT_TYPE_NEW_USERS        => __( 'New Users', 'memberdash' ),
			self::REPORT_TYPE_NEW_PAYING_USERS => __( 'New Paying Users', 'memberdash' ),
			self::REPORT_TYPE_PAYING_USERS_NO_LOGIN_3_MONTHS => __( 'Paying members that haven\'t logged in within the previous 3 months', 'memberdash' ),
		);
	}

	public function get_memberships() {
		return array( '' => __( 'Select membership', 'memberdash' ) ) + MS_Model_Membership::get_membership_names(
			array(
				'include_base'  => 0,
				'include_guest' => 0,
			)
		);
	}

	public function get_gateways() {
		return array( '' => __( 'Select payment gateway', 'memberdash' ) ) + MS_Model_Gateway::get_gateway_names( false, true );
	}

	public function get_url_csv_download() {
		return admin_url( 'admin.php?action=' . self::ACTION_CSV_DOWNLOAD . '&_wpnonce=' . wp_create_nonce( 'ms_reporting_csv_export' ) );
	}

}
