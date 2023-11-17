<?php
/**
 * Controller for Site Health
 *
 * @since 1.1.0
 *
 * @package MemberDash
 */

/**
 * Site Health controller.
 *
 * @since 1.1.0
 */
class MS_Controller_SiteHealth extends MS_Controller {
	/**
	 * Site Health key.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	private const SITE_HEALTH_KEY = 'memberdash';

	/**
	 * Fields.
	 *
	 * @since 1.1.0
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private $fields = [];

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_filter( 'debug_information', 'add_site_health_info' );
	}

	/**
	 * Get fields.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string,array<string,mixed>> $debug_info Debug info.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function add_site_health_info( array $debug_info ): array {
		$debug_info[ self::SITE_HEALTH_KEY ] = [
			'label'  => __( 'MemberDash', 'memberdash' ),
			'fields' => $this->get_fields(),
		];

		return $debug_info;
	}

	/**
	 * Get fields.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_fields(): array {
		if ( ! empty( $this->fields ) ) {
			return $this->fields;
		}

		$model = $this->get_model();
		$data  = $model->get_debug_data();

		$this->fields = [
			'plugin_version'               => [
				'label' => __( 'Plugin Version', 'memberdash' ),
				'value' => $data['plugin_version'],
			],
			'last_updated'                 => [
				'label' => __( 'Last Update', 'memberdash' ),
				'value' => $this->format_date( $data['last_updated'] ),
				'debug' => $data['last_updated'],
			],
			'total_memberships'            => [
				'label' => __( 'Total Number of Memberships', 'memberdash' ),
				'value' => $data['total_memberships'],
			],
			'total_paid_memberships'       => [
				'label' => __( 'Number of paid Memberships', 'memberdash' ),
				'value' => $data['total_paid_memberships'],
			],
			'total_free_memberships'       => [
				'label' => __( 'Number of free Memberships', 'memberdash' ),
				'value' => $data['total_free_memberships'],
			],
			'total_finite_memberships'     => [
				'label' => __( 'Number of finite Memberships', 'memberdash' ),
				'value' => $data['total_finite_memberships'],
			],
			'total_date_range_memberships' => [
				'label' => __( 'Number of date range Memberships', 'memberdash' ),
				'value' => $data['total_date_range_memberships'],
			],
			'total_one_time_memberships'   => [
				'label' => __( 'Number of one-time Memberships', 'memberdash' ),
				'value' => $data['total_one_time_memberships'],
			],
			'total_recurring_memberships'  => [
				'label' => __( 'Number of recurring Memberships', 'memberdash' ),
				'value' => $data['total_recurring_memberships'],
			],
			'total_free_trials'            => [
				'label' => __( 'Number of free trial Memberships', 'memberdash' ),
				'value' => $data['total_free_trials'],
			],
			'total_paying_customers'       => [
				'label' => __( 'Number of paying members (customers)', 'memberdash' ),
				'value' => $data['total_paying_customers'],
			],
			'total_free_customers'         => [
				'label' => __( 'Number of customers who have registered for a free membership', 'memberdash' ),
				'value' => $data['total_free_customers'],
			],
			'is_multiple_memberships'      => [
				'label' => __( 'Is Multiple Memberships Activated?', 'memberdash' ),
				'value' => $this->bool_to_yes_no_string( $data['is_multiple_memberships'] ),
				'debug' => $data['is_multiple_memberships'],
			],
			'monthly_revenue'              => [
				'label' => __( 'Monthly revenue', 'memberdash' ),
				'value' => $this->format_number( $data['monthly_revenue'] ),
				'debug' => $data['monthly_revenue'],
			],
			'payment_gateways'             => [
				'label' => __( 'Which payment gateway is being used', 'memberdash' ),
				'value' => $this->array_to_list_string( $data['payment_gateways'] ),
				'debug' => $data['payment_gateways'],
			],
			'active_addons'                => [
				'label' => __( 'Which add-ons are activated', 'memberdash' ),
				'value' => $this->array_to_list_string( $data['active_addons'] ),
				'debug' => $data['active_addons'],
			],
			'ld_activated'                 => [
				'label' => __( 'Is LearnDash installed and activated?', 'memberdash' ),
				'value' => $this->bool_to_yes_no_string( $data['ld_activated'] ),
				'debug' => $data['ld_activated'],
			],
			'ld_addon_enabled'             => [
				'label' => __( 'Is LearnDash integration add-on enabled?', 'memberdash' ),
				'value' => $this->bool_to_yes_no_string( $data['ld_addon_enabled'] ),
				'debug' => $data['ld_addon_enabled'],
			],
			'md_to_ld'                     => [
				'label' => __( 'If LD integration enabled, is MD to LD selected?', 'memberdash' ),
				'value' => $this->bool_to_yes_no_string( $data['md_to_ld'] ),
				'debug' => $data['md_to_ld'],
			],
			'ld_to_md'                     => [
				'label' => __( 'If LD integration enabled, is LD to MD selected?', 'memberdash' ),
				'value' => $this->bool_to_yes_no_string( $data['ld_to_md'] ),
				'debug' => $data['ld_to_md'],
			],
		];

		return $this->fields;
	}

	/**
	 * Get model.
	 *
	 * @since 1.1.0
	 *
	 * @return MS_Model_SiteHealth
	 */
	protected function get_model(): MS_Model_SiteHealth {
		return MS_Factory::load( 'MS_Model_SiteHealth' );
	}

	/**
	 * Converts a boolean value to the "Yes" or "No" string.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $value Value.
	 *
	 * @return string
	 */
	protected function bool_to_yes_no_string( $value ): string {
		return $value ? __( 'Yes', 'memberdash' ) : __( 'No', 'memberdash' );
	}

	/**
	 * Converts an array to a comma-separated string.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $value Value.
	 *
	 * @return string
	 */
	protected function array_to_list_string( $value ): string {
		return is_array( $value ) ? join( ', ', $value ) : '';
	}

	/**
	 * Formats a number.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $value Value.
	 *
	 * @return string
	 */
	protected function format_number( $value ): string {
		$value = MS_Helper_Cast::to_float( $value );

		if ( ! $value ) {
			return '0';
		}

		return (string) MS_Helper_Billing::format_price( $value );
	}

	/**
	 * Formats a date.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $timestamp Timestamp.
	 *
	 * @return string
	 */
	protected function format_date( $timestamp ): string {
		$format    = MS_Helper_Cast::to_string( get_option( 'date_format' ) );
		$timestamp = MS_Helper_Cast::to_int( $timestamp );
		$default   = __( 'Never', 'memberdash' );

		if (
			! $timestamp
			|| ! $format
		) {
			return $default;
		}

		$date = wp_date( $format, $timestamp );

		return $date ? $date : $default;
	}
}
