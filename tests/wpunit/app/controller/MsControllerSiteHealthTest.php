<?php
/**
 * Test Site Health controller.
 *
 * @package MemberDash\Tests
 */

use MemberDash\Tests\Base\TestCase;
use MemberDash\Tests\Traits\WithHelper;
use MemberDash\Tests\Traits\WithFaker;

/**
 * Class to test the Site Health controller class.
 */
class MS_ModelSiteHealthTest extends TestCase {
	use WithHelper;
	use WithFaker;

	/**
	 * Test debug information hook called.
	 *
	 * @return void
	 */
	public function test_debug_information_hook_called(): void {
		// Arrange.

		$controller = $this->get_site_health_controller_instance();

		// Assert.

		$this->assertInstanceOf( MS_Controller_SiteHealth::class, $controller );
		$this->assertGreaterThan(
			0,
			has_filter( 'debug_information', array( $controller, 'add_site_health_info' ) )
		);
	}

	/**
	 * Test getting site health info data.
	 *
	 * @throws Exception If method not found.
	 *
	 * @return void
	 */
	public function test_add_site_health_info(): void {
		// Arrange.

		$controller = $this->get_site_health_controller_instance();
		$root_key   = MS_Helper_Cast::to_string( $this->helper->get_protected_constant( MS_Controller_SiteHealth::class, 'SITE_HEALTH_KEY' ) );

		// Act.

		if ( ! method_exists( $controller, 'add_site_health_info' ) ) {
			throw new Exception( 'Method add_site_health_info not found!' );
		}

		$result = $controller->add_site_health_info( [] );

		// Assert.

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( $root_key, $result );
		$this->assertIsArray( $result[ $root_key ] );
		$this->assertArrayHasKey( 'label', $result[ $root_key ] );
		$this->assertArrayHasKey( 'fields', $result[ $root_key ] );
		$this->assertSame( 'MemberDash', $result[ $root_key ]['label'] );
		$this->assertIsArray( $result[ $root_key ]['fields'] );
	}

	/**
	 * Test getting fields.
	 *
	 * @throws Exception If method not found.
	 *
	 * @return void
	 */
	public function test_get_fields(): void {
		// Arrange.

		$controller    = $this->get_site_health_controller_instance();
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

		if ( ! method_exists( $controller, 'get_fields' ) ) {
			throw new Exception( 'Method get_fields not found!' );
		}

		$result = $controller->get_fields();

		// Assert.

		$this->assertIsArray( $result );
		$this->assertSame( count( $expected_keys ), count( $result ) );
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $result );
		}

		$this->assertIsString( $result['plugin_version']['value'] );

		$this->assertIsString( $result['last_updated']['value'] );
		$this->assertIsInt( $result['last_updated']['debug'] );

		$this->assertIsInt( $result['total_memberships']['value'] );

		$this->assertIsInt( $result['total_paid_memberships']['value'] );

		$this->assertIsInt( $result['total_free_memberships']['value'] );

		$this->assertIsInt( $result['total_finite_memberships']['value'] );

		$this->assertIsInt( $result['total_date_range_memberships']['value'] );

		$this->assertIsInt( $result['total_one_time_memberships']['value'] );

		$this->assertIsInt( $result['total_recurring_memberships']['value'] );

		$this->assertIsInt( $result['total_free_trials']['value'] );

		$this->assertIsInt( $result['total_paying_customers']['value'] );

		$this->assertIsInt( $result['total_free_customers']['value'] );

		$this->assertIsString( $result['is_multiple_memberships']['value'] );
		$this->assertIsBool( $result['is_multiple_memberships']['debug'] );

		$this->assertIsString( $result['monthly_revenue']['value'] );
		$this->assertIsFloat( $result['monthly_revenue']['debug'] );

		$this->assertIsString( $result['payment_gateways']['value'] );
		$this->assertIsArray( $result['payment_gateways']['debug'] );

		$this->assertIsString( $result['active_addons']['value'] );
		$this->assertIsArray( $result['active_addons']['debug'] );

		$this->assertIsString( $result['ld_activated']['value'] );
		$this->assertIsBool( $result['ld_activated']['debug'] );

		$this->assertIsString( $result['ld_addon_enabled']['value'] );
		$this->assertIsBool( $result['ld_addon_enabled']['debug'] );

		$this->assertIsString( $result['md_to_ld']['value'] );
		$this->assertIsBool( $result['md_to_ld']['debug'] );

		$this->assertIsString( $result['ld_to_md']['value'] );
		$this->assertIsBool( $result['ld_to_md']['debug'] );
	}

	/**
	 * Test getting model.
	 *
	 * @return void
	 */
	public function test_get_model(): void {
		// Arrange.

		$controller = $this->get_site_health_controller_instance();

		// Act.

		$result = $this->helper->call_protected_method( $controller, 'get_model' );

		// Assert.

		$this->assertInstanceOf( MS_Model_SiteHealth::class, $result );
	}

	/**
	 * Test bool to yes/no string.
	 *
	 * @dataProvider data_provider_test_bool_to_yes_no_string
	 *
	 * @param mixed  $value    Value.
	 * @param string $expected Expected.
	 *
	 * @return void
	 */
	public function test_bool_to_yes_no_string( $value, $expected ): void {
		// Arrange.

		$controller = $this->get_site_health_controller_instance();

		// Act.

		$result = $this->helper->call_protected_method( $controller, 'bool_to_yes_no_string', [ $value ] );

		// Assert.

		$this->assertIsString( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test array to list string.
	 *
	 * @dataProvider data_provider_test_array_to_list_string
	 *
	 * @param mixed  $value    Value.
	 * @param string $expected Expected.
	 *
	 * @return void
	 */
	public function test_array_to_list_string( $value, string $expected ): void {
		// Arrange.

		$controller = $this->get_site_health_controller_instance();

		// Act.

		$result = $this->helper->call_protected_method( $controller, 'array_to_list_string', [ $value ] );

		// Assert.

		$this->assertIsString( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test format number.
	 *
	 * @dataProvider data_provider_test_format_number
	 *
	 * @param mixed  $value    Value.
	 * @param string $expected Expected.
	 *
	 * @return void
	 */
	public function test_format_number( $value, string $expected ): void {
		// Arrange.

		$controller = $this->get_site_health_controller_instance();

		// Act.

		$result = $this->helper->call_protected_method( $controller, 'format_number', [ $value ] );

		// Assert.

		$this->assertIsString( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test format date.
	 *
	 * @dataProvider data_provider_test_format_date
	 *
	 * @param mixed  $value    Value.
	 * @param string $expected Expected.
	 *
	 * @return void
	 */
	public function test_format_date( $value, string $expected ): void {
		// Arrange.

		$controller = $this->get_site_health_controller_instance();

		// Act.

		$result = $this->helper->call_protected_method( $controller, 'format_date', [ $value ] );

		// Assert.

		$this->assertIsString( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test data provider for test_bool_to_yes_no_string.
	 *
	 * @return Generator
	 */
	public function data_provider_test_bool_to_yes_no_string(): Generator {
		yield 'true' => [ true, 'Yes' ];
		yield 'false' => [ false, 'No' ];
		yield 'int 1' => [ 1, 'Yes' ];
		yield 'zero' => [ 0, 'No' ];
		yield 'string' => [ 'Hello', 'Yes' ];
		yield 'empty string' => [ '', 'No' ];
	}

	/**
	 * Test data provider for test_array_to_list_string.
	 *
	 * @return Generator
	 */
	public function data_provider_test_array_to_list_string(): Generator {
		yield 'numeric array' => [ [ 1, 2, 3 ], '1, 2, 3' ];
		yield 'array of strings' => [ [ 'Hello', 'World' ], 'Hello, World' ];
		yield 'string' => [ '1, 2, 3', '' ];
		yield 'false' => [ false, '' ];
		yield 'true' => [ true, '' ];
	}

	/**
	 * Test data provider for test_format_number.
	 *
	 * @return Generator
	 */
	public function data_provider_test_format_number(): Generator {
		yield 'false' => [ false, '0' ];
		yield 'string' => [ 'Hello World', '0' ];
		yield 'int' => [ 10, '10.00' ];
		yield 'float' => [ 99.99, '99.99' ];
	}

	/**
	 * Test data provider for test_format_date.
	 *
	 * @return Generator
	 */
	public function data_provider_test_format_date(): Generator {
		$date_format = MS_Helper_Cast::to_string( get_option( 'date_format' ) );

		yield 'never' => [ false, 'Never' ];
		yield 'yesterday' => [
			strtotime( '-1 day' ),
			wp_date( $date_format, strtotime( '-1 day' ) ),
		];
		yield 'last week' => [
			strtotime( '-1 week' ),
			wp_date( $date_format, strtotime( '-1 week' ) ),
		];
		yield 'last month' => [
			strtotime( '-1 month' ),
			wp_date( $date_format, strtotime( '-1 month' ) ),
		];
	}

	/**
	 * Get Site Health controller instance.
	 *
	 * @throws Exception If controller not found.
	 *
	 * @return object
	 */
	protected function get_site_health_controller_instance(): object {
		$plugin     = MS_Plugin::instance();
		$controller = $this->helper->get_protected_property( $plugin, 'controller' );

		if ( ! is_object( $controller ) ) {
			throw new Exception( 'MS_Controller_SiteHealth not found!' );
		}

		$controllers = $this->helper->get_protected_property( $controller, 'controllers' );

		if ( is_array( $controllers ) && array_key_exists( 'site_health', $controllers ) ) {
			return $controllers['site_health'];
		}

		throw new Exception( 'MS_Controller_SiteHealth not found!' );
	}
}
