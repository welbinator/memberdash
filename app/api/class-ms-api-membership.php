<?php
/**
 * Membership API hook
 *
 * Manages all Membership API actions
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Api
 */
class MS_Api_Membership extends MS_Api {

	const BASE_API_ROUTE = '/membership/';

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
			self::$instance = new MS_Api_Membership();
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

		register_rest_route(
			$namespace,
			self::BASE_API_ROUTE . 'list',
			array(
				'method'              => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_memberships' ),
				'permission_callback' => array( $this, 'validate_request' ),
			)
		);

		register_rest_route(
			$namespace,
			self::BASE_API_ROUTE . 'get',
			array(
				'method'              => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_membership' ),
				'permission_callback' => array( $this, 'validate_request' ),
				'args'                => array(
					'param' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'int|string',
						'description'       => __( 'The Membership ID or name or slug', 'memberdash' ),
					),
				),
			)
		);
	}

	/**
	 * List Memberships
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return MS_Model_Membership[] List of all available Memberships.
	 */
	public function list_memberships( $request ) {
		return MS_Model_Membership::get_public_memberships();
	}

	/**
	 * Get Membership
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return MS_Model_Membership The membership object.
	 */
	public function get_membership( $request ) {
		$param = $request->get_param( 'param' );
		if ( ! is_numeric( $param ) ) {
			$param = MS_Model_Membership::get_membership_id( $param );
		}
		$membership = MS_Factory::load( 'MS_Model_Membership', intval( $param ) );
		return $membership;
	}
}

