<?php
/**
 * Software License Manager.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

/**
 * The class handling license verification, detect new plugin version and process the update.
 *
 * @since 1.0.0
 */
class MS_Licensing extends MS_Controller {
	const LICENSE_SERVER              = 'https://checkout.learndash.com/wp-json/learndash/v1';
	const CHECK_FREQUENCY             = 24 * 60 * 60; // 24 hours
	const OPT_LICENSE_KEY             = 'ms_license_key';
	const OPT_LICENSE_EMAIL           = 'ms_license_email';
	const OPT_LICENSE_KEY_ERROR       = 'ms_license_key_error';
	const OPT_LICENSE_KEY_DATA        = 'ms_license_key_data';
	const OPT_LICENSE_KEY_NEXT_CHECK  = 'ms_license_key_next_check';
	const OPT_LICENSE_KEY_RETRY_CHECK = 'ms_license_key_retry_check';

	/**
	 * Activates a license (and registers the domain the request is coming from).
	 *
	 * @since 1.0.0
	 *
	 * @param string $email The license email.
	 * @param string $key   The license key.
	 *
	 * @return void
	 */
	public function activate( string $email, string $key ) {
		if ( empty( $key ) || empty( $email ) ) {
			return; // No license key.
		}
		$params = array(
			'license_key' => rawurlencode( $key ),
			'site_url'    => get_site_url(),
			'email'       => $email,
		);

		$response = $this->api_request( '/site/auth', $params, 'POST' );
		if ( is_wp_error( $response ) ) {
			update_option( self::OPT_LICENSE_KEY_ERROR, $response->get_error_message() );

			return;
		}

		// update license.
		$this->update_license_data(
			array(
				'subscription'  => $response,
				'license_key'   => $key,
				'license_email' => $email,
			)
		);
	}

	/**
	 * Check license.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function check() {
		$this->maybe_update_data();
		$data = $this->get_data();
		if ( ! is_array( $data ) ) {
			add_action( 'admin_notices', array( $this, 'show_license_error' ) );
		} else {
			// check updates.
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
			add_filter( 'plugins_api', array( $this, 'filter_plugins_api' ), 10, 3 );
		}

		add_filter( 'http_request_args', array( $this, 'maybe_append_auth_headers' ), 10, 2 );
	}

	/**
	 * Add our plugin information so it can be retrieved via the function plugins_api
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $result Default update-info provided by WordPress.
	 * @param string $action What action was requested (theme or plugin?).
	 * @param mixed  $args   Details used to build default update-info.
	 *
	 * @return mixed
	 */
	public function filter_plugins_api( $result, string $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( is_object( $args ) && property_exists( $args, 'slug' ) && 'memberdash' !== $args->slug ) {
			return $result;
		}

		$data = $this->get_data();

		$remote_version = $this->get_plugin_remote_version( $data );
		if ( empty( $remote_version ) ) {
			return $result;
		}

		return $remote_version;
	}

	/**
	 * When we download a project, the auth header should be added.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $parsed_args An array of HTTP request arguments.
	 * @param string              $url         The request URL.
	 *
	 * @return array<string,mixed>
	 */
	public function maybe_append_auth_headers( array $parsed_args, string $url ): array {
		$needle = self::LICENSE_SERVER . '/repo/plugin_memberdash';
		if ( strpos( $url, $needle ) !== 0 ) {
			return $parsed_args;
		}
		if ( ! is_array( $parsed_args['headers'] ) ) {
			$parsed_args['headers'] = array();
		}

		$parsed_args['headers'] = array_merge( $parsed_args['headers'], $this->get_auth_headers() );

		return $parsed_args;
	}

	/**
	 * Return the auth headers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,string>
	 */
	private function get_auth_headers(): array {
		$data = $this->get_data();
		if ( ! is_array( $data ) ) {
			return array();
		}

		return array(
			'Learndash-Site-Url'        => network_site_url(),
			'Learndash-Hub-License-Key' => $data['key'],
			'Learndash-Hub-Email'       => $data['email'],
		);
	}

	/**
	 * Check for updates.
	 *
	 * @since 1.0.0
	 *
	 * @param object $transient The update_plugins transient.
	 *
	 * @return mixed
	 */
	public function check_update( $transient ) {
		if ( ! is_object( $transient ) || ! property_exists( $transient, 'response' ) ) {
			return $transient;
		}

		$data = $this->get_data();
		if ( ! is_array( $data ) || empty( $data['key'] ) || empty( $data['email'] ) ) {
			return $transient;
		}

		$remote_version = $this->get_plugin_remote_version( $data );
		if ( ! $remote_version ) {
			return $transient; // no remote version found.
		}

		// add update information.
		$obj = (object) array(
			'id'            => MEMBERDASH_PLUGIN,
			'slug'          => MEMBERDASH_PLUGIN_NAME,
			'plugin'        => MEMBERDASH_PLUGIN,
			'new_version'   => $remote_version->stable_tag,
			'url'           => $remote_version->plugin_uri,
			'package'       => $remote_version->package,
			'icons'         => array(),
			'banners'       => array(),
			'banners_rtl'   => array(),
			'tested'        => $remote_version->tested_up_to,
			'requires_php'  => $remote_version->requires_php,
			'compatibility' => new stdClass(),
		);

		// if a newer version is available, add the update.
		if ( version_compare( MEMBERDASH_VERSION, $remote_version->stable_tag, '<' ) ) {
			$transient->response[ $obj->plugin ] = $obj;
		} else {
			$transient->no_update[ $obj->plugin ] = $obj;
		}

		return $transient;
	}

	/**
	 * Get plugin remote version.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The license data array.
	 *
	 * @return bool|object {stable_tag:string,plugin_uri:string,package:string,tested_up_to:string,requires_php:string}
	 */
	public function get_plugin_remote_version( array $data ) {
		if ( ! isset( $data['key'] ) || ! isset( $data['email'] ) ) {
			return false;
		}

		$response = $this->api_request( '/repo/plugin_memberdash', array(), 'GET', $this->get_auth_headers() );

		if ( is_wp_error( $response ) || empty( $response['memberdash'] ) ) {
			return false;
		}

		/**
		 * The memberdash data returning from API.
		 *
		 * @var array{latest_version:string,plugin_uri:string,download_url:string,tested:string,requires_php:string} $response
		 */
		$response = $response['memberdash'];

		return (object) array_merge(
			array(
				'stable_tag'   => $response['latest_version'],
				'plugin_uri'   => $response['plugin_uri'],
				'package'      => $response['download_url'],
				'tested_up_to' => $response['tested'],
				'requires_php' => $response['requires_php'],
			),
			$response
		);
	}

	/**
	 * Check if we need update license data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_update_data(): void {
		$key   = get_option( self::OPT_LICENSE_KEY );
		$email = get_option( self::OPT_LICENSE_EMAIL );
		if ( empty( $key ) || empty( $email ) || ! is_string( $email ) || ! is_string( $key ) ) {
			return;
		}

		$next_check = get_option( self::OPT_LICENSE_KEY_NEXT_CHECK );
		if ( empty( $next_check ) || $next_check <= time() ) {
			$this->activate( $email, $key );
			update_option( self::OPT_LICENSE_KEY_NEXT_CHECK, time() + self::CHECK_FREQUENCY );
		}
	}

	/**
	 * Show small admin alert about subscription status.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function show_license_error(): void {
		$class   = 'notice notice-error is-dismissible';
		$message = sprintf(
		// translators: placeholders: Plugin name, Plugin license link.
			esc_html_x( 'Your license for %1$s is invalid or incomplete. Please enter a valid license or purchase one %2$s.', 'placeholders: Plugin name. Plugin license link.', 'memberdash' ),
			'<strong>MemberDash</strong>',
			'<a href="' . admin_url( 'admin.php?page=membership-settings&tab=licensing' ) . '">' . esc_html__( 'here', 'memberdash' ) . '</a>'
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Return the license key data:
	 * - In case of error, return a string with the error message.
	 * - In case of success, return an array with keys below.
	 * - In case of no keys found, return false.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|string|array {
	 * @type string $key The license key.
	 * @type string $status The license status.
	 * @type string $email The license user email.
	 * @type string $name The license user name.
	 * @type string $expires The license expiration date.
	 * @type string $registered_domains array{
	 * @type string $domain The domain name.
	 *   }
	 * @type string $domains_left
	 * */
	public function get_data() {
		// check for error.
		$error = get_option( self::OPT_LICENSE_KEY_ERROR );
		if ( ! empty( $error ) ) {
			return $error;
		}

		$license_data = get_option( self::OPT_LICENSE_KEY_DATA );
		if ( ! $license_data || ! is_array( $license_data ) ) {
			return false; // no keys found.
		}
		$date_format = get_option( 'date_format' );
		$expiry      = $license_data['subscription']['expiry'];
		if ( ! empty( $date_format ) && is_string( $date_format ) ) {
			$expiry = date_i18n( $date_format, strtotime( $expiry ) );
		}

		return array(
			'key'     => $license_data['license_key'],
			'status'  => __( 'Active', 'memberdash' ),
			'email'   => $license_data['license_email'],
			'expires' => $expiry,
		);
	}

	/**
	 * Trigger to API endpoint for verifying the license key
	 *
	 * @since 1.0.0
	 *
	 * @param string                $endpoint The REST API endpoint.
	 * @param array<string,string>  $params   URL parameter: array("param" => "value") ==> index.php?param=value.
	 * @param string                $method   The method of the request.
	 * @param array<string, string> $headers  The request headers.
	 *
	 * @return array<mixed> | WP_Error
	 */
	private function api_request( string $endpoint, array $params = array(), string $method = 'GET', array $headers = array() ) {
		$url = self::LICENSE_SERVER . $endpoint;
		if ( 'GET' === $method ) {
			$url    = add_query_arg( $params, $url );
			$params = array();
		}
		$response = wp_remote_request(
			$url,
			array(
				'body'    => $params,
				'method'  => $method,
				'headers' => $headers,
			)
		);

		// Check for error in the response.
		if ( is_wp_error( $response ) ) {
			if ( WP_DEBUG ) {
				error_log( esc_html( $response->get_error_message() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			return $response;
		}
		$returned_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( ! is_array( $returned_data ) ) {
				$returned_data = array();
			}
			$err_message = $returned_data['message'] ?? __( 'There seems to be an issue while connecting to our API. Please try again', 'memberdash' );
			$err_code    = $returned_data['code'] ?? 'apiRequest';

			return new WP_Error( $err_code, $err_message );
		}

		return (array) $returned_data;
	}

	/**
	 * Deactivates a license.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the license was deactivated.
	 */
	public function deactivate(): bool {
		$data = $this->get_data();

		if ( ! is_array( $data ) ) {
			$this->clear_data();

			return true;
		}

		$params = array(
			'license_key' => $data['key'],
			'site_url'    => get_site_url(),
			'email'       => $data['email'],
		);

		$response = $this->api_request( '/site/auth', $params, 'DELETE' );
		if ( is_wp_error( $response ) ) {
			return false;
		} else {
			$this->clear_data();
		}

		return true;
	}

	/**
	 * Automate activate if we found an auth-token, if not, then just fallback to manual flow.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_automate_activate() {
		// if this constant not defined, something wrong.
		if ( ! defined( 'MEMBERDASH_PLUGIN_DIR' ) ) {
			return;
		}
		$token_path = MEMBERDASH_PLUGIN_DIR . '/auth-token.php';
		if ( ! file_exists( $token_path ) ) {
			return;
		}

		$token = sanitize_key( include $token_path );
		/**
		 * Returning from API.
		 *
		 * @var array{license_key:string,user_email:string}| WP_Error $resp
		 */
		$resp = $this->api_request(
			'/site/auth_token',
			array(
				'auth_token' => $token,
				'site_url'   => get_site_url(),
			),
			'POST'
		);
		if ( is_wp_error( $resp ) ) {

			return;
		}

		// update license.
		$this->update_license_data(
			array(
				'subscription'  => $resp,
				'license_key'   => $resp['license_key'],
				'license_email' => $resp['user_email'],
			)
		);

		/**
		 * The setting class.
		 *
		 * @var MS_Model_Settings $settings
		 */
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$settings->set_license_email( $resp['user_email'] );
		$settings->set_license_key( $resp['license_key'] );
		$settings->save();
	}

	/**
	 * Delete all the cached.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function clear_data(): void {
		delete_option( self::OPT_LICENSE_KEY );
		delete_option( self::OPT_LICENSE_KEY_DATA );
		delete_option( self::OPT_LICENSE_KEY_ERROR );
		delete_option( self::OPT_LICENSE_KEY_NEXT_CHECK );
		delete_option( self::OPT_LICENSE_KEY_RETRY_CHECK );
	}

	/**
	 * Update the license data for the current license key.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data The result from endpoint.
	 *
	 * @return void True if the license data was updated.
	 */
	private function update_license_data( array $data ): void {
		update_option( self::OPT_LICENSE_KEY, $data['license_key'] );
		update_option( self::OPT_LICENSE_EMAIL, $data['license_email'] );
		update_option( self::OPT_LICENSE_KEY_DATA, $data );
		delete_option( self::OPT_LICENSE_KEY_ERROR );
		update_option( self::OPT_LICENSE_KEY_RETRY_CHECK, 0 );
	}
}
