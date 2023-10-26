<?php
/**
 * Protection API hook
 *
 * Manages all Protection API actions
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Api
 */
class MS_Api_Protection extends MS_Api {

	const BASE_API_ROUTE = '/protection/';

	/**
	 * Singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var MS_Plugin
	 */
	private static $instance = null;


	/**
	 * Returns singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @static
	 * @access public
	 *
	 * @return MS_Api
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new MS_Api_Protection();
		}

		return self::$instance;
	}

	/**
	 * Set up the api routes
	 *
	 * @param string $namespace The parent namespace.
	 *
	 * @since 1.0.0
	 */
	public function set_up_route( $namespace ) {

	}
}

