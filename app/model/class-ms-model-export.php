<?php
/**
 * Model
 *
 * @package MemberDash
 */

/**
 * Base class for all export handlers.
 *
 * @since 1.0.0
 */
class MS_Model_Export extends MS_Model {

	/**
	 * Export Settings
	 */
	const PLUGIN_SETTINGS      = 'plugin';
	const FULL_MEMBERSHIP_DATA = 'full';
	const MEMBERSHIP_ONLY      = 'membership';
	const MEMBERS_ONLY         = 'members';

	/**
	 * Export formats
	 */
	const JSON_EXPORT = 'json';
	const XML_EXPORT  = 'xml';


	/**
	 * Main entry point: Handles the export action.
	 *
	 * This task will exit the current request as the result will be a download
	 * and no HTML page that is displayed.
	 *
	 * @since 1.0.0
	 */
	public function process() {
		$type              = $_POST['type'];
		$format            = $_POST['format'];
		$supported_types   = self::export_types();
		$supported_formats = self::export_formats();
		$supported_types   = array_keys( $supported_types );
		$supported_formats = array_keys( $supported_formats );
		if ( in_array( $type, $supported_types ) && in_array( $format, $supported_formats ) ) {
			switch ( $type ) {
				case self::PLUGIN_SETTINGS:
					/**
					 * Model for exporting plugin settings.
					 *
					 * @var MS_Model_Export_Settings $handler
					 */
					$handler = MS_Factory::create( 'MS_Model_Export_Settings' );
					$handler->process();
					break;

				case self::FULL_MEMBERSHIP_DATA:
					/**
					 * Model for exporting full membership data.
					 *
					 * @var MS_Model_Export_Full $handler
					 */
					$handler = MS_Factory::create( 'MS_Model_Export_Full' );
					$handler->process( $format );
					break;

				case self::MEMBERSHIP_ONLY:
					/**
					 * Model for exporting memberships.
					 *
					 * @var MS_Model_Export_Membership $handler
					 */
					$handler = MS_Factory::create( 'MS_Model_Export_Membership' );
					$handler->process( $format );
					break;

				case self::MEMBERS_ONLY:
					$handler = MS_Factory::create( 'MS_Model_Export_Members' );
					$handler->process( $format );
					break;

				default:
					mslib3()->net->file_download( __( 'Export type not yet supported', 'memberdash' ), 'error.json' );
					break;
			}
		} else {
			mslib3()->net->file_download( __( 'Invalid export type or format', 'memberdash' ), 'error.json' );
		}

	}

	/**
	 * Export types
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,string>
	 */
	public static function export_types() {
		return array(
			self::PLUGIN_SETTINGS      => __( 'Global Payment Settings (Note that this is not a full backup of the plugin settings)', 'memberdash' ),
			self::FULL_MEMBERSHIP_DATA => __( 'Full Membership Data (Members and Memberships)', 'memberdash' ),
			self::MEMBERSHIP_ONLY      => __( 'Memberships Only', 'memberdash' ),
			self::MEMBERS_ONLY         => __( 'Members Only', 'memberdash' ),
		);
	}

	/**
	 * Supported Export types
	 *
	 * @since 1.0.0
	 *
	 * @return Array
	 */
	public static function export_formats() {
		return array(
			self::JSON_EXPORT => __( 'JSON', 'memberdash' ),
			self::XML_EXPORT  => __( 'XML', 'memberdash' ),
		);
	}

	/**
	 * Get Membership list
	 *
	 * @return Array
	 */
	public static function get_memberships() {
		$membership_select   = array();
		$memberships         = MS_Model_Membership::get_public_memberships();
		$membership_select[] = __( 'None', 'memberdash' );
		foreach ( $memberships as $key => $item ) {
			$membership_select[ $item->id ] = $item->name;
		}
		return $membership_select;
	}

}
