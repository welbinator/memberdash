<?php
/**
 * Memberdash App service locator class.
 *
 * @since 1.0.2
 *
 * @package Memberdash\Core
 */

namespace Memberdash\Core;

use StellarWP\Memberdash\lucatume\DI52\App as DI52App;

/**
 * Memberdash App service locator class.
 *
 * @since 1.0.2
 */
class App extends DI52App {
	/**
	 * A reference to the singleton instance of the DI container
	 * the application uses as Service Locator.
	 *
	 * @since 1.0.2
	 *
	 * @phpstan-ignore-next-line -- type overridden intentionally.
	 * @var Container|null
	 */
	protected static $container;

	/**
	 * Returns the singleton instance of the DI container the application
	 * will use as Service Locator.
	 *
	 * @since 1.0.2
	 *
	 * @return Container The singleton instance of the Container used as Service Locator
	 *                   by the application.
	 */
	public static function container(): Container {
		if ( ! isset( static::$container ) ) {
			static::$container = new Container();
		}

		return static::$container;
	}

	/**
	 * Sets the container instance the Application should use as a Service Locator.
	 *
	 * If the Application already stores a reference to a Container instance, then
	 * this will be replaced by the new one.
	 *
	 * @param Container $container A reference to the Container instance the Application
	 *                                should use as a Service Locator.
	 *
	 * @since 1.0.2
	 *
	 * @return void The method does not return any value.
	 */
	public static function set_container( Container $container ) {
		static::$container = $container;
	}
}
