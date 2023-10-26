<?php
/**
 * Controller for Telemetry Integration
 *
 * @since 1.0.2
 *
 * @package MemberDash
 * @subpackage Controller
 */

use StellarWP\Memberdash\StellarWP\ContainerContract\ContainerInterface as Container;
use StellarWP\Memberdash\StellarWP\Telemetry\Opt_In\Status;
use StellarWP\Memberdash\StellarWP\Telemetry\Opt_In\Opt_In_Template;
use StellarWP\Memberdash\StellarWP\Telemetry\Config;

/**
 * Class MS_Controller_Telemetry.
 *
 * @since 1.0.2
 */
class MS_Controller_Telemetry extends MS_Controller {

	/**
	 * The Container Interface.
	 *
	 * @since 1.0.2
	 *
	 * @var Container $container The Telemetry Container.
	 */
	protected Container $container;

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function admin_init(): void {
		$hooks = array(
			MS_Controller_Plugin::admin_page_hook(),
			MS_Controller_Plugin::admin_page_hook( 'members' ),
			MS_Controller_Plugin::admin_page_hook( 'protection' ),
			MS_Controller_Plugin::admin_page_hook( 'add-member' ),
			MS_Controller_Plugin::admin_page_hook( 'billing' ),
			MS_Controller_Plugin::admin_page_hook( 'coupons' ),
			MS_Controller_Plugin::admin_page_hook( 'addon' ),
			MS_Controller_Plugin::admin_page_hook( 'reporting' ),
			MS_Controller_Plugin::admin_page_hook( 'settings' ),
			MS_Controller_Plugin::admin_page_hook( 'help' ),
		);

		foreach ( $hooks as $hook ) {
			$this->run_action( 'admin_print_scripts-' . $hook, 'load_telemetry_modal' );
		}
		$this->add_action( 'admin_notices', 'show_telemetry_notice' );
		$this->add_filter( 'stellarwp/telemetry/memberdash/exit_interview_args', 'exit_interview' );
		$this->add_filter( 'admin_init', 'update_opt_in_get_status' );
		$this->add_filter( 'plugin_action_links', 'add_opt_in_link', 10, 2 );
		// Initialize the container.
		$this->container = Config::get_container();
	}

	/**
	 * Show a message to the users that they have opted out from Telemetry.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function show_telemetry_notice(): void {
		// Bail early if we're not showing a messages.
		if ( ! isset( $_GET['memberdash-opt-in-msg'] ) ) {
			return;
		}

		$value = (int) filter_input( INPUT_GET, 'memberdash-opt-in-msg' );

		if ( $value ) {
			$class   = 'notice notice-success is-dismissible';
			$message = __( 'You have opted out of sharing information with Telemetry.', 'memberdash' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	}

	/**
	 * Update the Opt-In status. This captures the link that was trigger in the Plugins Actions Page.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function update_opt_in_get_status(): void {
		// Bail early if we're not saving the Opt-In Status field.
		if ( ! isset( $_GET['opt-in-status'] ) ) {
			return;
		}

		$value    = (int) filter_input( INPUT_GET, 'opt-in-status' );
		$redirect = '';

		try {
			if ( $value ) {
				// Set Telemetry modal to be visible if the user decides to Opt In.
				update_option( $this->container->get( Opt_In_Template::class )->get_option_name(), '1' );
				$redirect = add_query_arg( '', '', esc_url( admin_url( 'admin.php?page=membership-settings' ) ) );
			} else {
				$this->container->get( Status::class )->set_status( false );
				$redirect = add_query_arg( 'memberdash-opt-in-msg', 1, esc_url( admin_url( 'admin.php?page=membership-settings' ) ) );
			}
		} catch ( Exception $ex ) {
			MS_Helper_Debug::debug_log( "Couldn't resolve the Telemetry Status class. Exception: " . $ex->getMessage() );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Add an action link to the MemberDash plugin actions.
	 *
	 * @since 1.0.2
	 *
	 * @param array<string> $plugin_actions The WordPress plugin actions.
	 * @param string        $plugin_file    The current plugin file that the actions is being called.
	 *
	 * @return array<string> The filtered actions.
	 */
	public function add_opt_in_link( array $plugin_actions, string $plugin_file ): array {
		$new_actions = array();

		try {
			$opt_in_status = $this->container->get( Status::class )->is_active();

			if ( ( $opt_in_status && ( basename( MEMBERDASH_PLUGIN_BASE_DIR ) . '/memberdash.php' === $plugin_file ) ) ) {
				$new_actions['memberdash_opt_out'] = sprintf(
				// translators: %1$s: The admin URL, %2$s: The Opt-Out alt text.
					__( '<a href="%1$s" alt="%2$s">Opt-Out</a>', 'memberdash' ),
					esc_url( admin_url( 'plugins.php?opt-in-status=0' ) ),
					__( 'Change status to opted out. This means that you do not agree to share basic information to help Memberdash better.', 'memberdash' )
				);
			} elseif ( ( ! $opt_in_status && ( basename( MEMBERDASH_PLUGIN_BASE_DIR ) . '/memberdash.php' === $plugin_file ) ) ) {
				$new_actions['memberdash_opt_in'] = sprintf(
				// translators: %1$s: The admin URL, %2$s: The Opt-In alt text.
					__( '<a href="%1$s" alt="%2$s">Opt-In</a>', 'memberdash' ),
					esc_url( admin_url( 'plugins.php?opt-in-status=1' ) ),
					__( 'Change status to opted in. This means that you agree to share basic information to help make Memberdash better.', 'memberdash' )
				);
			}
		} catch ( Exception $ex ) {
			MS_Helper_Debug::debug_log( "Couldn't resolve the Telemetry Status class. Exception: " . $ex->getMessage() );
		}

		return array_merge( $new_actions, $plugin_actions );
	}

	/**
	 * Add Telemetry modal to plugin pages.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function load_telemetry_modal(): void {
		$this->add_telemetry_modal( get_current_screen() );
	}

	/**
	 * Shows Telemetry modal.
	 *
	 * @since 1.0.2
	 *
	 * @param WP_Screen|null $current_screen Current screen.
	 *
	 * @return void
	 */
	public function add_telemetry_modal( ?WP_Screen $current_screen ): void {
		if (
				! empty( $current_screen->post_type )
			|| (
				! empty( $current_screen->parent_file )
				&& 'membership' === $current_screen->parent_file
			)
			|| (
				is_admin()
				&& isset( $_GET['page'] )
				&& false !== strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'membership' )
			)
		) {
			add_filter(
				'stellarwp/telemetry/memberdash/optin_args', // cspell:disable-line.
				function( $args ) {
					$args['plugin_logo']        = esc_attr( MS_Plugin::instance()->get_url() ) . 'app/assets/images/memberdash-icon.svg';
					$args['plugin_logo_width']  = 100;
					$args['plugin_logo_height'] = 100;
					$args['plugin_logo_alt']    = 'MemberDash Logo that looks like a lock icon in violet and blue color.';

					$args['heading'] = esc_html__( 'We hope you love MemberDash!', 'memberdash' );

					$args['intro'] = sprintf(
					// translators: %1$s: The user name.
						esc_html__(
							'Hi, %1$s! This is an invitation to help us improve MemberDash products by sharing product usage data with StellarWP. MemberDash is part of the StellarWP Family of Brands. If you opt in we\'ll share some helpful WordPress and StellarWP product info with you from time to time. And if you skip this, that\'s okay! Our products will continue to work.',
							'memberdash'
						),
						$args['user_name']
					);

					$args['permissions_url'] = 'https://www.learndash.com/telemetry-tracking/';
					$args['tos_url']         = 'https://www.learndash.com/terms-and-conditions/';

					return $args;
				}
			);

			do_action( 'stellarwp/telemetry/memberdash/optin' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.ValidHookName.UseUnderscores
		}
	}

	/**
	 * Sets the logo and labels for the exit interview.
	 *
	 * @since 1.0.2
	 *
	 * @param mixed[] $args The exit interview labels.
	 *
	 * @return mixed[] The custom labels.
	 */
	public function exit_interview( array $args ): array {
		$args['plugin_logo_width']  = 100;
		$args['plugin_logo_height'] = 100;
		$args['plugin_logo']        = esc_attr( MS_Plugin::instance()->get_url() ) . 'app/assets/images/memberdash-icon.svg';
		$args['plugin_logo_alt']    = 'MemberDash Logo that looks like a lock icon in violet and blue color.';

		return $args;
	}
}
