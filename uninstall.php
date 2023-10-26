<?php
/**
 * Uninstall MemberDash
 *
 * Deletes the following plugin data:
 *  - Telemetry information.
 *
 * @since       1.0.2
 *
 * @package     MemberDash
 * @subpackage  Uninstall
 */

// Bail if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

use StellarWP\Memberdash\StellarWP\Telemetry\Uninstall;

// Remove Telemetry stuff.
Uninstall::run( 'memberdash' );
