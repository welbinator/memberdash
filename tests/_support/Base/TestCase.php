<?php
/**
 * Test Case Base Class.
 *
 * @package MemberDash\Tests
 */

namespace MemberDash\Tests\Base;

use MemberDash\Tests\Traits\WithHooks;

/**
 * LearnDash Test Case.
 */
class TestCase extends \Codeception\TestCase\WPTestCase {
	/**
	 * Setup the test case.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Following code tracks applied filters, how many times they were applied, and with what arguments.
		// This code can't be moved to the trait because of uopz_set_hook restrictions.

		if (
			in_array( WithHooks::class, class_uses( static::class ), true )
			&& extension_loaded( 'uopz' )
		) {
			$class_name   = static::class;
			$track_filter = function ( ...$args ) use ( $class_name ) {
				if ( method_exists( $class_name, 'add_applied_filter' ) ) {
					$filter_name = array_shift( $args );

					$class_name::add_applied_filter( $filter_name, $args ); // @phpstan-ignore-line -- this is a WithHooks trait method.
				}
			};

			uopz_set_hook( 'apply_filters', $track_filter ); // @phpstan-ignore-line
			uopz_set_hook( 'apply_filters_deprecated', $track_filter ); // @phpstan-ignore-line
		}
	}

	/**
	 * Registers the intention to count function calls.
	 *
	 * This code can't be moved to the trait because of uopz_set_hook restrictions.
	 *
	 * @param string ...$function_names Function names.
	 *
	 * @return void
	 */
	protected function track_function_call( string ...$function_names ): void {
		if ( ! extension_loaded( 'uopz' ) ) {
			return;
		}

		foreach ( $function_names as $function_name ) {
			$class_name = static::class;

			$class_name::$function_calls[ $function_name ] = [
				'count' => 0,
			];

			uopz_set_hook( // @phpstan-ignore-line
				$function_name,
				// @phpstan-ignore-next-line
				function () use ( $class_name, $function_name ) {
					$class_name::add_function_call( $function_name ); // @phpstan-ignore-line -- this is a WithUopz trait method.
				}
			);
		}
	}

	/**
	 * Registers the intention to count class method calls.
	 *
	 * This code can't be moved to the trait because of uopz_set_hook restrictions.
	 *
	 * @param string $class_name  Class name.
	 * @param string $method_name Method name.
	 *
	 * @return void
	 */
	protected function track_class_method_call( string $class_name, string $method_name ): void {
		if ( ! extension_loaded( 'uopz' ) ) {
			return;
		}

		$test_class_name = static::class;
		$method_key      = $class_name . '::' . $method_name;

		$test_class_name::$function_calls[ $method_key ] = array(
			'count' => 0,
		);

		uopz_set_hook( // @phpstan-ignore-line
			$class_name,
			$method_name,
			// @phpstan-ignore-next-line
			function () use ( $test_class_name, $method_key ) {
				$test_class_name::add_function_call( $method_key ); // @phpstan-ignore-line -- this is a WithUopz trait method.
			}
		);
	}

	/**
	 * Gets the path to the test data folder.
	 *
	 * @return string
	 */
	protected function get_test_data_path(): string {
		return dirname( __DIR__, 2 ) . '/_data/';
	}

	/**
	 * Marks a test skipped if the version is below the passed one.
	 *
	 * @param string $version PHP Version.
	 *
	 * @return void
	 */
	protected function mark_skipped_with_php_version_below( string $version ): void {
		if ( version_compare( PHP_VERSION, $version, '<' ) ) {
			$this->markTestSkipped( 'This test can\'t be run with PHP below ' . $version );
		}
	}
}
