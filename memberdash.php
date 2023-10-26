<?php
/**
 * Plugin Name: MemberDash
 * Version: 1.0.3
 * Description: The most powerful, easy to use and flexible membership plugin for WordPress sites available.
 * Author: LearnDash
 * Author URI: https://learndash.com/memberdash-plugin
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: memberdash
 * Domain Path: /languages/
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

// cspell:ignore Philipp Stracker Incsub Fabio Onishi Ivanov Kitterhing Rheinard Korf Ashok Nath Joji Mori Estevão Oliveira Costa Nikolay Strikhar Israel Barragan.

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'vendor-prefixed/autoload.php';

use Memberdash\Core\App;
use Memberdash\Core\Container;
use StellarWP\Memberdash\StellarWP\Telemetry\Config;
use StellarWP\Memberdash\StellarWP\Telemetry\Core as Telemetry;

/**
 * Copyright notice
 *
 * @copyright Incsub (http://incsub.com/)
 *
 * Authors: Philipp Stracker, Fabio Jun Onishi, Victor Ivanov, Jack Kitterhing, Rheinard Korf, Ashok Kumar Nath, Paul Kevin
 * Contributors: Joji Mori, Patrick Cohen, Estevão de Oliveira da Costa, Nikolay Strikhar, Israel Barragan
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 */

/**
 * Initializes constants and create the main plugin object MS_Plugin.
 * This function is called *instantly* when this file was loaded.
 *
 * @since 1.0.0
 */
function memberdash_init_app() {
	if ( defined( 'MEMBERDASH_PLUGIN' ) ) {
		$plugin_name = 'MemberDash';
		if ( is_admin() ) {
			// Can happen in Multisite installs where a sub-site has activated the
			// plugin and then the plugin is also activated in network-admin.
			printf(
				'<div class="notice error"><p><strong>%s</strong>: %s</p></div>',
				sprintf(
				// translators: %s is the plugin name.
					esc_html__( 'Could not load the plugin %s, because another version of the plugin is already loaded', 'memberdash' ),
					esc_attr( $plugin_name )
				),
				esc_html( MEMBERDASH_PLUGIN . ' (v' . MEMBERDASH_VERSION . ')' )
			);
		}
		return;
	}

	/**
	 * Plugin version
	 *
	 * @since 1.0.0
	 */
	define( 'MEMBERDASH_VERSION', '1.0.3' );

	/**
	 * Plugin main-file.
	 *
	 * @since 1.0.0
	 */
	define( 'MEMBERDASH_PLUGIN_FILE', __FILE__ );

	/**
	 * Plugin identifier constant.
	 *
	 * @since 1.0.0
	 */
	define( 'MEMBERDASH_PLUGIN', plugin_basename( __FILE__ ) );

	/**
	 * Plugin name dir constant.
	 *
	 * @since 1.0.0
	 */
	define( 'MEMBERDASH_PLUGIN_NAME', dirname( MEMBERDASH_PLUGIN ) );

	/**
	 * Plugin name dir constant.
	 *
	 * @since 1.0.0
	 */
	define( 'MEMBERDASH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

	/**
	 * Plugin base dir
	 *
	 * @since 1.0.0
	 */
	define( 'MEMBERDASH_PLUGIN_BASE_DIR', dirname( __FILE__ ) );

	$externals = array(
		dirname( __FILE__ ) . '/lib/memberdash/core.php',
	);

	foreach ( $externals as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path; }
	}

	/**
	 * Translation.
	 *
	 * Tip:
	 *   The translation files must have the filename [TEXT-DOMAIN]-[locale].mo
	 *   Example: memberdash-en_EN.mo  /  memberdash-de_DE.mo
	 */
	function memberdash_translate_plugin() {
		load_plugin_textdomain(
			'memberdash',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
	add_action( 'plugins_loaded', 'memberdash_translate_plugin' );

	add_action(
		'plugins_loaded',
		function () {
			// Telemetry.
			$telemetry_server_url = defined( 'STELLARWP_TELEMETRY_SERVER' ) && ! empty( STELLARWP_TELEMETRY_SERVER )
				? STELLARWP_TELEMETRY_SERVER
				: 'https://telemetry.stellarwp.com/api/v1';

			App::set_container( new Container() );
			Config::set_container( App::container() );
			Config::set_server_url( $telemetry_server_url );
			Config::set_hook_prefix( 'memberdash' );
			Config::set_stellar_slug( 'memberdash' );

			Telemetry::instance()->init( __FILE__ );
		},
		0
	);

	include MEMBERDASH_PLUGIN_BASE_DIR . '/app/ms-loader.php';

	// Initialize the MWPS class loader.
	$loader = new MS_Loader();

	/**
	 * Create an instance of the plugin object.
	 *
	 * This is the primary entry point for the MemberDash plugin.
	 *
	 * @since 1.0.0
	 */
	MS_Plugin::instance();

	/**
	 * Ajax Logins
	 *
	 * @since 1.0.0
	 */
	MS_Auth::check_ms_ajax();

}

memberdash_init_app();
