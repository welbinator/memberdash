<?php
/**
 * Uopz trait.
 *
 * @package MemberDash\Tests
 */

namespace MemberDash\Tests\Traits;

use BadMethodCallException;

/**
 * LearnDash test uopz trait.
 */
trait WithUopz {
	/**
	 * The list of set returns.
	 *
	 * @var array<string|string|array<string>>
	 */
	private $uopz_set_returns = array();

	/**
	 * Function calls.
	 *
	 * @var array<string,array{count:int}>
	 */
	protected static $function_calls = array();

	/**
	 * Override a class method with a return value.
	 *
	 * @param string $class_name   Class name.
	 * @param string $method_name  Method name.
	 * @param mixed  $return_value Return value.
	 *
	 * @return void
	 */
	protected function uopz_set_class_method_return( string $class_name, string $method_name, $return_value ): void {
		$this->skip_test_if_no_uopz();

		$target = array( $class_name, $method_name );

		if ( ! class_exists( $class_name ) ) {
			eval( // phpcs:ignore Squiz.PHP.Eval.Discouraged
				sprintf( 'class %s { public function %s(){} }', ...$target )
			);
		}

		$this->uopz_set_returns[] = $target;

		uopz_set_return( $class_name, $method_name, $return_value, is_callable( $return_value ) );
	}

	/**
	 * Override a function with a return value.
	 *
	 * @param string $function_name Function name.
	 * @param mixed  $return_value  Return value.
	 *
	 * @return void
	 */
	protected function uopz_set_function_return( string $function_name, $return_value ): void {
		$this->skip_test_if_no_uopz();

		$this->uopz_set_returns[] = $function_name;

		uopz_set_return( $function_name, $return_value, is_callable( $return_value ) );
	}

	/**
	 * Returns how many times a function has been called.
	 *
	 * @param string $function_name Function name.
	 *
	 * @throws BadMethodCallException If the track_function_call method has not been called.
	 *
	 * @return int Number of times the function has been called.
	 */
	public function get_function_times_called( string $function_name ): int {
		$this->skip_test_if_no_uopz();

		if ( ! array_key_exists( $function_name, self::$function_calls ) ) {
			throw new BadMethodCallException( 'You must call the `track_function_call` method before calling `get_function_times_called`' );
		}

		return self::$function_calls[ $function_name ]['count'];
	}

	/**
	 * Returns how many times a class method has been called.
	 *
	 * @param string $class_name  Class name.
	 * @param string $method_name Method name.
	 *
	 * @throws BadMethodCallException If the track_class_method_call method has not been called.
	 *
	 * @return int Number of times the class method has been called.
	 */
	public function get_class_method_times_called( string $class_name, string $method_name ): int {
		$this->skip_test_if_no_uopz();

		$method_key = $class_name . '::' . $method_name;

		if ( ! array_key_exists( $method_key, self::$function_calls ) ) {
			throw new BadMethodCallException( 'You must call the `track_class_method_call` method before calling `get_class_method_times_called`' );
		}

		return self::$function_calls[ $method_key ]['count'];
	}

	/**
	 * Adds function call to the list.
	 *
	 * @param string $function_name Function name.
	 *
	 * @return void
	 */
	public static function add_function_call( string $function_name ): void {
		if ( ! array_key_exists( $function_name, self::$function_calls ) ) {
			self::$function_calls[ $function_name ] = [
				'count' => 0,
			];
		}

		self::$function_calls[ $function_name ]['count']++;
	}

	/**
	 * Restores all the uopz changes.
	 *
	 * @after
	 *
	 * @return void
	 */
	protected function uopz_clean(): void {
		if ( ! extension_loaded( 'uopz' ) ) {
			return;
		}

		foreach ( $this->uopz_set_returns as $callable ) {
			if ( is_array( $callable ) ) {
				uopz_unset_return( ...$callable );
			} elseif ( is_string( $callable ) ) {
				uopz_unset_return( $callable );
			}
		}

		foreach ( array_keys( self::$function_calls ) as $function_name ) {
			if ( strpos( '::', $function_name ) ) {
				[ $class_name, $method_name ] = explode( '::', $function_name );

				uopz_unset_hook( $class_name, $method_name );

			} else {
				uopz_unset_hook( $function_name );
			}
		}

		self::$function_calls = [];
	}

	/**
	 * Skips the current test if the uopz extension is not loaded.
	 *
	 * @return void
	 */
	private function skip_test_if_no_uopz(): void {
		if ( ! extension_loaded( 'uopz' ) ) {
			$this->markTestSkipped( 'This test requires the uopz extension.' ); // @phpstan-ignore-line -- PHPUnit method.
		}
	}
}
