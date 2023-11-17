<?php
/**
 * Test Site Health model.
 *
 * @package MemberDash\Tests
 */

use MemberDash\Tests\Base\IntegrationTestCase;
use MemberDash\Tests\Traits\WithHelper;
use MemberDash\Tests\Traits\WithUopz;
use MemberDash\Tests\Traits\WithFaker;

/**
 * Class to test the Site Health model class.
 */
class MS_ModelSiteHealthTest extends IntegrationTestCase {
	use WithHelper;
	use WithUopz;
	use WithFaker;

	/**
	 * Tests getting debug data.
	 *
	 * @return void
	 */
	public function test_get_debug_data(): void {
		// Arrange.

		$model         = new MS_Model_SiteHealth();
		$expected_keys = [
			'plugin_version',
			'last_updated',
			'total_memberships',
			'total_paid_memberships',
			'total_free_memberships',
			'total_finite_memberships',
			'total_date_range_memberships',
			'total_one_time_memberships',
			'total_recurring_memberships',
			'total_free_trials',
			'total_paying_customers',
			'total_free_customers',
			'is_multiple_memberships',
			'monthly_revenue',
			'payment_gateways',
			'active_addons',
			'ld_activated',
			'ld_addon_enabled',
			'md_to_ld',
			'ld_to_md',
		];

		// Act.

		$result = $model->get_debug_data();

		// Assert.

		$this->assertIsArray( $result );
		$this->assertSameSize( $expected_keys, $result );
		$this->assertSame( $expected_keys, array_keys( $result ) );
	}

	/**
	 * Tests getting plugin version.
	 *
	 * @return void
	 */
	public function test_get_plugin_version(): void {
		// Arrange.

		$model    = new MS_Model_SiteHealth();
		$expected = MEMBERDASH_VERSION;

		// Act.

		$result = $this->helper->call_protected_method( $model, 'get_plugin_version' );

		// Assert.

		$this->assertIsString( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting last updated date.
	 *
	 * @return void
	 */
	public function test_get_last_updated_date(): void {
		// Arrange.

		$model           = new MS_Model_SiteHealth();
		$version         = $this->faker->semver();
		$version_history = [
			time() => $version,
		];

		$this->uopz_set_class_method_return(
			'MS_Model_Settings',
			'get_version_history',
			function() use ( $version_history ) {
				return $version_history;
			}
		);

		// Act.

		$result = $this->helper->call_protected_method( $model, 'get_last_updated_date' );

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( array_key_first( $version_history ), $result );
	}

	/**
	 * Tests getting memberships total.
	 *
	 * @return void
	 */
	public function test_get_memberships_total(): void {
		// Arrange.

		$model       = new MS_Model_SiteHealth();
		$memberships = $this->create_memberships();
		$expected    = array_sum( $memberships );

		// Act.

		$result = $this->helper->call_protected_method( $model, 'get_memberships_total' );

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting paid memberships total.
	 *
	 * @return void
	 */
	public function test_get_paid_memberships_total(): void {
		// Arrange.

		$model       = new MS_Model_SiteHealth();
		$args        = [
			'is_free' => 0,
		];
		$memberships = $this->create_memberships( $args );
		$expected    = array_sum( $memberships );

		// Act.

		$result = $this->helper->call_protected_method( $model, 'get_paid_memberships_total' );

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting free memberships total.
	 *
	 * @return void
	 */
	public function test_get_free_memberships_total(): void {
		// Arrange.

		$model       = new MS_Model_SiteHealth();
		$args        = [
			'price'   => '',
			'is_free' => 1,
		];
		$memberships = $this->create_memberships( $args );
		$expected    = array_sum( $memberships );

		// Act.

		$result = $this->helper->call_protected_method( $model, 'get_free_memberships_total' );

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting finite memberships total.
	 *
	 * @return void
	 */
	public function test_get_finite_memberships_total(): void {
		// Arrange.

		$model       = new MS_Model_SiteHealth();
		$memberships = $this->create_memberships();
		$expected    = $memberships[ MS_Model_Membership::PAYMENT_TYPE_FINITE ];

		// Act.

		$result = $this->helper->call_protected_method( $model, 'get_finite_memberships_total' );

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting date range memberships total.
	 *
	 * @return void
	 */
	public function test_get_date_range_memberships_total(): void {
		// Arrange.

		$model       = new MS_Model_SiteHealth();
		$memberships = $this->create_memberships();
		$expected    = $memberships[ MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE ];

		// Act.

		$result = $this->helper->call_protected_method(
			$model,
			'get_date_range_memberships_total'
		);

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting one time memberships total.
	 *
	 * @return void
	 */
	public function test_get_one_time_memberships_total(): void {
		// Arrange.

		$model       = new MS_Model_SiteHealth();
		$memberships = $this->create_memberships();
		$expected    = $memberships[ MS_Model_Membership::PAYMENT_TYPE_PERMANENT ];

		// Act.

		$result = $this->helper->call_protected_method(
			$model,
			'get_one_time_memberships_total'
		);

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting recurring memberships total.
	 *
	 * @return void
	 */
	public function test_get_recurring_memberships_total(): void {
		// Arrange.

		$model       = new MS_Model_SiteHealth();
		$memberships = $this->create_memberships();
		$expected    = $memberships[ MS_Model_Membership::PAYMENT_TYPE_RECURRING ];

		// Act.

		$result = $this->helper->call_protected_method(
			$model,
			'get_recurring_memberships_total'
		);

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting free trials total.
	 *
	 * @return void
	 */
	public function test_get_free_trials_total(): void {
		// Arrange.

		$model       = new MS_Model_SiteHealth();
		$args        = [
			'trial_period_enabled' => 1,
		];
		$memberships = $this->create_memberships( $args );
		$expected    = array_sum( $memberships );

		// Act.

		$result = $this->helper->call_protected_method(
			$model,
			'get_free_trials_total'
		);

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting paying customers total.
	 *
	 * @return void
	 */
	public function test_get_paying_customers_total(): void {
		// Arrange.

		$model         = new MS_Model_SiteHealth();
		$relationships = $this->create_relationships();
		$expected      = $relationships['free'] + $relationships['admin'];

		// Act.

		$result = $this->helper->call_protected_method(
			$model,
			'get_paying_customers_total'
		);

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting free customers total.
	 *
	 * @return void
	 */
	public function test_get_free_customers_total(): void {
		// Arrange.

		$model         = new MS_Model_SiteHealth();
		$relationships = $this->create_relationships();

		// Remove free gateways.
		unset( $relationships['free'], $relationships['admin'] );
		$expected = array_sum( $relationships );

		// Act.

		$result = $this->helper->call_protected_method(
			$model,
			'get_free_customers_total'
		);

		// Assert.

		$this->assertIsInt( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests checking if multiple memberships are enabled.
	 *
	 * @return void
	 */
	public function test_is_multiple_memberships_enabled(): void {
		// Arrange.

		$model    = new MS_Model_SiteHealth();
		$expected = MS_Model_Addon::is_enabled( 'multi_memberships' );

		// Act.

		$result = $this->helper->call_protected_method(
			$model,
			'is_multiple_memberships_enabled'
		);

		// Assert.

		$this->assertIsBool( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting monthly revenue.
	 *
	 * @return void
	 */
	public function test_get_monthly_revenue(): void {
		// Arrange.

		$model           = new MS_Model_SiteHealth();
		$post_type       = $this->get_invoice_post_type();
		$number_of_posts = $this->faker->numberBetween( 1, 10 );
		$amount          = 20;
		$expected        = floatval( $number_of_posts * $amount );

		for ( $i = 0; $i < $number_of_posts; $i++ ) {
			$this->factory()->post->create(
				[
					'post_type'   => $post_type,
					'post_status' => 'private',
					'post_date'   => gmdate( 'Y-m-d H:i:s', intval( strtotime( "-$i days" ) ) ),
					'meta_input'  => [
						'amount' => $amount,
						'status' => 'paid',
					],
				]
			);
		}

		// Act.

		$result = $this->helper->call_protected_method( $model, 'get_monthly_revenue' );

		// Assert.

		$this->assertIsFloat( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting active payment gateways.
	 *
	 * @return void
	 */
	public function test_get_active_payment_gateways(): void {
		// Arrange.

		$model    = new MS_Model_SiteHealth();
		$gateways = MS_Model_Gateway::get_gateways( true );
		$expected = array_filter(
			array_map(
				function ( $gateway ) {
					return $gateway->name;
				},
				$gateways
			)
		);

		// Act.

		$result = $this->helper->call_protected_method(
			$model,
			'get_active_payment_gateways'
		);

		// Assert.

		$this->assertIsArray( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests getting active addons.
	 *
	 * @return void
	 */
	public function test_get_active_addons(): void {
		// Arrange.

		$model    = new MS_Model_SiteHealth();
		$addons   = MS_Model_Addon::get_addons();
		$expected = array_filter(
			array_map(
				function ( $addon ) {
					return $addon->active ? $addon->title : null;
				},
				$addons
			)
		);

		// Act.

		$result = $this->helper->call_protected_method( $model, 'get_active_addons' );

		// Assert.

		$this->assertIsArray( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests checking if LearnDash is activated.
	 *
	 * @return void
	 */
	public function test_is_ld_activated(): void {
		// Arrange.

		$model    = new MS_Model_SiteHealth();
		$expected = MS_Addon_Learndash::learndash_active();

		// Act.

		$result = $this->helper->call_protected_method( $model, 'is_ld_activated' );

		// Assert.

		$this->assertIsBool( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests checking if LearnDash addon is enabled.
	 *
	 * @return void
	 */
	public function test_is_ld_addon_enabled(): void {
		// Arrange.

		$model    = new MS_Model_SiteHealth();
		$expected = MS_Addon_Learndash::is_active();

		// Act.

		$result = $this->helper->call_protected_method( $model, 'is_ld_addon_enabled' );

		// Assert.

		$this->assertIsBool( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests checking if LD integration type is valid.
	 *
	 * @return void
	 */
	public function test_check_ld_integration_type(): void {
		// Arrange.

		$model = new MS_Model_SiteHealth();

		// Act.

		$result = $this->helper->call_protected_method(
			$model,
			'check_ld_integration_type',
			[ 'enroll_type' => MS_Addon_Learndash::ENROLL_TO_MEMBERSHIP ]
		);

		$this->assertIsBool( $result );
	}

	/**
	 * Tests checking if MD to LD is enabled.
	 *
	 * @return void
	 */
	public function test_is_md_to_ld_enabled(): void {
		// Arrange.

		$model        = new MS_Model_SiteHealth();
		$settings     = MS_Factory::load( 'MS_Model_Settings' );
		$current_type = $settings->get_custom_setting(
			MS_Addon_Learndash::ID,
			'enroll_type'
		);
		$expected     = $current_type === MS_Addon_Learndash::ENROLL_TO_COURSE;

		// Act.

		$result = $this->helper->call_protected_method( $model, 'is_md_to_ld_enabled' );

		// Assert.

		$this->assertIsBool( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Tests checking if LD to MD is enabled.
	 *
	 * @return void
	 */
	public function test_is_ld_to_md_enabled(): void {
		// Arrange.

		$model        = new MS_Model_SiteHealth();
		$settings     = MS_Factory::load( 'MS_Model_Settings' );
		$current_type = $settings->get_custom_setting(
			MS_Addon_Learndash::ID,
			'enroll_type'
		);
		$expected     = $current_type === MS_Addon_Learndash::ENROLL_TO_MEMBERSHIP;

		// Act.

		$result = $this->helper->call_protected_method( $model, 'is_ld_to_md_enabled' );

		// Assert.

		$this->assertIsBool( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Reset static cache key of MS_Model_Membership::get_membership_ids().
	 *
	 * @return void
	 */
	protected function reset_get_membership_ids_cache(): void {
		$uid = wp_generate_uuid4();
		$this->uopz_set_function_return(
			'wp_json_encode',
			function() use ( $uid ) {
				return $uid;
			}
		);
	}

	/**
	 * Get membership post type.
	 *
	 * @return mixed
	 */
	protected function get_membership_post_type() {
		return MS_Model_Membership::get_post_type();
	}

	/**
	 * Get memberships payment statuses.
	 *
	 * @return string[]
	 */
	protected function get_memberships_payment_statuses(): array {
		return [
			MS_Model_Membership::PAYMENT_TYPE_PERMANENT,
			MS_Model_Membership::PAYMENT_TYPE_FINITE,
			MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE,
			MS_Model_Membership::PAYMENT_TYPE_RECURRING,
			MS_Model_Membership::PAYMENT_TYPE_NONE,
		];
	}

	/**
	 * Get relationship post type.
	 *
	 * @return mixed
	 */
	protected function get_relationship_post_type() {
		return MS_Model_Relationship::get_post_type();
	}

	/**
	 * Get relationship gateway IDs.
	 *
	 * @return string[]
	 */
	protected function get_relationship_gateway_ids(): array {
		return [
			'free', // Free.
			'admin', // Free.
			'stripe', // Paid.
			'paypalstandard', // Paid.
		];
	}

	/**
	 * Get invoice post type.
	 *
	 * @return mixed
	 */
	protected function get_invoice_post_type() {
		return MS_Model_Invoice::get_post_type();
	}

	/**
	 * Create memberships.
	 *
	 * @param array<string,mixed> $post_meta Post meta.
	 *
	 * @return array<string,int> Returns an array of payment statuses and their totals.
	 */
	protected function create_memberships( array $post_meta = [] ): array {
		$post_type        = $this->get_membership_post_type();
		$number_of_posts  = $this->faker->numberBetween( 1, 10 );
		$payment_statuses = $this->get_memberships_payment_statuses();
		$total            = [];
		$defaults         = [
			'active'       => 1,
			'payment_type' => '',
			'price'        => '20',
			'type'         => MS_Model_Membership::TYPE_STANDARD,
		];

		// Set default post meta.
		$post_meta = wp_parse_args( $post_meta, $defaults );

		// Set default totals to zero.
		foreach ( $payment_statuses as $payment_status ) {
			$total[ $payment_status ] = 0;
		}

		// Create posts.
		for ( $i = 0; $i < $number_of_posts; $i++ ) {
			foreach ( $payment_statuses as $payment_status ) {
				// Set payment type.
				$post_meta['payment_type'] = $payment_status;

				$this->factory()->post->create(
					[
						'post_type'   => $post_type,
						'post_status' => 'publish',
						'meta_input'  => $post_meta,
					]
				);

				$total[ $payment_status ]++;
			}
		}

		// Reset static cache.
		$this->reset_get_membership_ids_cache();

		return $total;
	}

	/**
	 * Create relationships.
	 *
	 * @param array<string,mixed> $post_meta Post meta.
	 *
	 * @return array<string,int> Returns an array of gateway IDs and their totals.
	 */
	protected function create_relationships( array $post_meta = [] ): array {
		$post_type       = $this->get_relationship_post_type();
		$number_of_posts = $this->faker->numberBetween( 1, 10 );
		$gateways        = $this->get_relationship_gateway_ids();
		$total           = [];
		$defaults        = [
			'status'     => 'active',
			'gateway_id' => '',
		];
		$post_meta       = wp_parse_args( $post_meta, $defaults );

		// Set default totals to zero.
		foreach ( $gateways as $gateway_id ) {
			$total[ $gateway_id ] = 0;
		}

		// Create posts.
		for ( $i = 0; $i < $number_of_posts; $i++ ) {
			foreach ( $gateways as $gateway_id ) {
				// Set gateway ID.
				$post_meta['gateway_id'] = $gateway_id;

				$this->factory()->post->create(
					[
						'post_type'   => $post_type,
						'post_status' => 'publish',
						'meta_input'  => $post_meta,
					]
				);

				$total[ $gateway_id ]++;
			}
		}

		return $total;
	}
}
