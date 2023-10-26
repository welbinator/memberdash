<?php
/**
 * Abstract class for all Rest Api Endpoints.
 *
 * All api classes will extend or inherit from the MS_Api class.
 * Methods of this class will control the flow and behavior of the plugin
 * by using MS_Model objects.
 *
 * @since 1.0.0
 *
 * @uses MS_Model
 *
 * @package MemberDash
 */
class MS_Api extends MS_Hooker {

	/**
	 * Pass Key
	 *
	 * @since 1.0.0
	 */
	protected $pass_key = null;

	/**
	 * MS_Model Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		/**
		 * Actions to execute when constructing the parent Model.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_Model object.
		 */
		do_action( 'ms_api_construct', $this );

		$this->add_action( 'ms_addon_wprest_register_route', 'register_route', 10, 2 );
	}

	/**
	 * Register the route
	 *
	 * @param String $namespace - the parent namespace
	 * @param String $pass_key - the api passkey set up in the settings
	 *
	 * @since 1.0.0
	 */
	public function register_route( $namespace, $pass_key ) {
		$this->pass_key = $pass_key;
		$this->set_up_route( $namespace );
	}

	/**
	 * Set up the api routes
	 *
	 * @param String $namespace - the parent namespace
	 *
	 * @since 1.0.0
	 */
	public function set_up_route( $namespace ) {

	}

	/**
	 * Validate the request
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function validate_request( $request ) {
		$pass_key = $request->get_param( 'pass_key' );
		if ( empty( $pass_key ) || empty( $this->pass_key ) || $pass_key != $this->pass_key ) {
			return new WP_Error( 'rest_user_cannot_view', __( 'Invalid request, you are not allowed to make this request', 'memberdash' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return apply_filters( 'ms_api_validate_request', true );
	}
}

