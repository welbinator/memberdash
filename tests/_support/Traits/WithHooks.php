<?php
/**
 * Hooks trait.
 *
 * @package MemberDash\Tests
 */

namespace MemberDash\Tests\Traits;

/**
 * LearnDash test hook trait.
 */
trait WithHooks {
	/**
	 * Applied filters.
	 *
	 * @var array<string,array{count:int, args: array<int,mixed>[]}>
	 */
	protected static $applied_filters = [];

	/**
	 * Returns the number of times a filter has been applied.
	 *
	 * @param string $filter_name Filter name.
	 *
	 * @return int Number of times the filter has been applied.
	 */
	public function applied_filter( string $filter_name ): int {
		if ( ! extension_loaded( 'uopz' ) || ! array_key_exists( $filter_name, self::$applied_filters ) ) {
			return 0;
		}

		return self::$applied_filters[ $filter_name ]['count'];
	}

	/**
	 * Clears the applied filters list. Sometimes it's needed to clear the list if some code in the arrangement section called them.
	 *
	 * @return void
	 */
	public static function clear_applied_filters(): void {
		self::$applied_filters = [];
	}

	/**
	 * Returns the args of the applied filter.
	 *
	 * @param string $filter_name    Filter name.
	 * @param int    $call_iteration Filter call iteration number. Sometimes a filter is called multiple times and contains different arguments. Defaults to 1 (the first call).
	 *
	 * @return mixed[]|null Filter args array or null if a filter was not applied.
	 */
	public function applied_filter_args( string $filter_name, int $call_iteration = 1 ): ?array {
		if (
			! extension_loaded( 'uopz' )
			|| ! array_key_exists( $filter_name, self::$applied_filters )
			|| $call_iteration < 1
		) {
			return null;
		}

		return self::$applied_filters[ $filter_name ]['args'][ $call_iteration - 1 ];
	}

	/**
	 * Adds applied filter to the list.
	 *
	 * @param string  $filter_name Filter name.
	 * @param mixed[] $filter_args Filter args.
	 *
	 * @return void
	 */
	public static function add_applied_filter( string $filter_name, array $filter_args ): void {
		if ( ! isset( self::$applied_filters[ $filter_name ] ) ) {
			self::$applied_filters[ $filter_name ] = array(
				'count' => 0,
				'args'  => [],
			);
		}

		self::$applied_filters[ $filter_name ]['count']++;
		self::$applied_filters[ $filter_name ]['args'][] = $filter_args;
	}

	/**
	 * Restores all the uopz changes.
	 *
	 * @after
	 *
	 * @return void
	 */
	public function hooks_clean(): void {
		self::$applied_filters = array();

		if ( ! extension_loaded( 'uopz' ) ) {
			return;
		}

		uopz_unset_hook( 'apply_filters' );
		uopz_unset_hook( 'apply_filters_deprecated' );
	}
}
