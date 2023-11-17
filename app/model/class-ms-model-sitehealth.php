<?php
/**
 * Model for Site Health
 *
 * @since 1.1.0
 *
 * @package MemberDash
 */

/**
 * Site Health model.
 *
 * @since 1.1.0
 */
class MS_Model_SiteHealth extends MS_Model {
	/**
	 * Get debug data.
	 *
	 * @since 1.1.0
	 *
	 * @return mixed[]
	 */
	public function get_debug_data(): array {
		// TODO: Add new methods to fetch data for each field.
		$data = [
			'plugin_version'               => $this->get_plugin_version(),
			'last_updated'                 => $this->get_last_updated_date(),
			'total_memberships'            => $this->get_memberships_total(),
			'total_paid_memberships'       => $this->get_paid_memberships_total(),
			'total_free_memberships'       => $this->get_free_memberships_total(),
			'total_finite_memberships'     => $this->get_finite_memberships_total(),
			'total_date_range_memberships' => $this->get_date_range_memberships_total(),
			'total_one_time_memberships'   => $this->get_one_time_memberships_total(),
			'total_recurring_memberships'  => $this->get_recurring_memberships_total(),
			'total_free_trials'            => $this->get_free_trials_total(),
			'total_paying_customers'       => $this->get_paying_customers_total(),
			'total_free_customers'         => $this->get_free_customers_total(),
			'is_multiple_memberships'      => $this->is_multiple_memberships_enabled(),
			'monthly_revenue'              => $this->get_monthly_revenue(),
			'payment_gateways'             => $this->get_active_payment_gateways(),
			'active_addons'                => $this->get_active_addons(),
			'ld_activated'                 => $this->is_ld_activated(),
			'ld_addon_enabled'             => $this->is_ld_addon_enabled(),
			'md_to_ld'                     => $this->is_md_to_ld_enabled(),
			'ld_to_md'                     => $this->is_ld_to_md_enabled(),
		];

		return $data;
	}

	/**
	 * Get plugin version.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	private function get_plugin_version(): string {
		return defined( 'MEMBERDASH_VERSION' ) ? MEMBERDASH_VERSION : '';
	}

	/**
	 * Get last updated date.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_last_updated_date(): int {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$history  = $settings->get_version_history();

		return intval( array_key_first( $history ) );
	}

	/**
	 * Get memberships total.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_memberships_total(): int {
		return MS_Model_Membership::get_membership_count();
	}

	/**
	 * Get paid memberships total.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_paid_memberships_total(): int {
		$args = [
			'include_guest' => false,
			'meta_query'    => [
				'not_free' => [
					'key'     => 'is_free',
					'value'   => '1',
					'compare' => '!=',
				],
			],
		];

		return MS_Model_Membership::get_membership_count( $args );
	}

	/**
	 * Get free memberships total.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_free_memberships_total(): int {
		$args = [
			'include_guest' => false,
			'meta_query'    => [
				'is_free' => [
					'key'     => 'is_free',
					'value'   => '1',
					'compare' => '=',
				],
			],
		];

		return MS_Model_Membership::get_membership_count( $args );
	}

	/**
	 * Get finite memberships total.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_finite_memberships_total(): int {
		$args = [
			'include_guest' => false,
			'meta_query'    => [
				'is_finite' => [
					'key'     => 'payment_type',
					'value'   => MS_Model_Membership::PAYMENT_TYPE_FINITE,
					'compare' => '=',
				],
			],
		];

		return MS_Model_Membership::get_membership_count( $args );
	}

	/**
	 * Get date range memberships total.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_date_range_memberships_total(): int {
		$args = [
			'include_guest' => false,
			'meta_query'    => [
				'is_date_range' => [
					'key'     => 'payment_type',
					'value'   => MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE,
					'compare' => '=',
				],
			],
		];

		return MS_Model_Membership::get_membership_count( $args );
	}

	/**
	 * Get one time memberships total.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_one_time_memberships_total(): int {
		$args = [
			'include_guest' => false,
			'meta_query'    => [
				'is_one_time' => [
					'key'     => 'payment_type',
					'value'   => MS_Model_Membership::PAYMENT_TYPE_PERMANENT,
					'compare' => '=',
				],
			],
		];

		return MS_Model_Membership::get_membership_count( $args );
	}

	/**
	 * Get recurring memberships total.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_recurring_memberships_total(): int {
		$args = [
			'include_guest' => false,
			'meta_query'    => [
				'is_recurring' => [
					'key'     => 'payment_type',
					'value'   => MS_Model_Membership::PAYMENT_TYPE_RECURRING,
					'compare' => '=',
				],
			],
		];

		return MS_Model_Membership::get_membership_count( $args );
	}

	/**
	 * Get free trials total.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_free_trials_total(): int {
		$args = [
			'include_guest' => false,
			'meta_query'    => [
				'is_free_trial' => [
					'key'     => 'trial_period_enabled',
					'value'   => '1',
					'compare' => '=',
				],
			],
		];

		return MS_Model_Membership::get_membership_count( $args );
	}

	/**
	 * Get paying customers total.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_paying_customers_total(): int {
		global $wpdb;

		$post_type = MS_Model_Relationship::get_post_type();

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} sta ON sta.post_id = p.ID AND sta.meta_key = 'status'
				INNER JOIN {$wpdb->postmeta} gtw ON gtw.post_id = p.ID AND gtw.meta_key = 'gateway_id'
				WHERE p.post_type = %s
				AND sta.meta_value = 'active'
				AND gtw.meta_value NOT IN ('free', 'admin')",
				$post_type
			)
		);
	}

	/**
	 * Get free customers total.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	private function get_free_customers_total(): int {
		global $wpdb;

		$post_type = MS_Model_Relationship::get_post_type();

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} sta ON sta.post_id = p.ID AND sta.meta_key = 'status'
				INNER JOIN {$wpdb->postmeta} gtw ON gtw.post_id = p.ID AND gtw.meta_key = 'gateway_id'
				WHERE p.post_type = %s
				AND sta.meta_value = 'active'
				AND gtw.meta_value IN ('free', 'admin')",
				$post_type
			)
		);
	}

	/**
	 * Check if multiple memberships are enabled.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	private function is_multiple_memberships_enabled(): bool {
		return MS_Model_Addon::is_enabled( 'multi_memberships' );
	}

	/**
	 * Get monthly revenue info.
	 *
	 * @since 1.1.0
	 *
	 * @return float
	 */
	private function get_monthly_revenue(): float {
		$reporting_model = MS_Factory::load( 'MS_Model_Reporting' );

		$start_date = ( new Datetime( '-30 days', wp_timezone() ) )->format( 'Y-m-d' );
		$end_date   = ( new Datetime( 'now', wp_timezone() ) )->format( 'Y-m-d' );
		$revenue    = $reporting_model->get_revenue_by_date_range( $start_date, $end_date );

		return (float) $revenue['total_revenue'];
	}

	/**
	 * Get active payment gateways info.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string,string> List of payment gateways including slug => name.
	 */
	private function get_active_payment_gateways(): array {
		$gateways = MS_Model_Gateway::get_gateways( true );

		return array_filter(
			array_map(
				function ( $gateway ) {
					return $gateway->name;
				},
				$gateways
			)
		);
	}

	/**
	 * Get active add-ons info.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string,string> List of active add-ons including slug => title.
	 */
	private function get_active_addons(): array {
		$addons = MS_Model_Addon::get_addons();

		return array_filter(
			array_map(
				function ( $addon ) {
					return $addon->active ? $addon->title : null;
				},
				$addons
			)
		);
	}

	/**
	 * Check if LearnDash is activated.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	private function is_ld_activated(): bool {
		return MS_Addon_Learndash::learndash_active();
	}

	/**
	 * Check if LearnDash add-on is enabled.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	private function is_ld_addon_enabled(): bool {
		return MS_Addon_Learndash::is_active();
	}

	/**
	 * Get LearnDash integration type.
	 *
	 * @since 1.1.0
	 *
	 * @param string $enroll_type Enroll type slug.
	 *
	 * @return bool
	 */
	private function check_ld_integration_type( string $enroll_type = '' ): bool {
		$settings     = MS_Factory::load( 'MS_Model_Settings' );
		$current_type = $settings->get_custom_setting(
			MS_Addon_Learndash::ID,
			'enroll_type'
		);

		return $current_type === $enroll_type;
	}

	/**
	 * Check if MemberDash to LearnDash integration is selected.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	private function is_md_to_ld_enabled(): bool {
		return $this->check_ld_integration_type( MS_Addon_Learndash::ENROLL_TO_COURSE );
	}

	/**
	 * Check if LearnDash to MemberDash integration is selected.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	private function is_ld_to_md_enabled(): bool {
		return $this->check_ld_integration_type( MS_Addon_Learndash::ENROLL_TO_MEMBERSHIP );
	}
}
