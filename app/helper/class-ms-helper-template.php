<?php
/**
 * Utilities class
 *
 * @since 1.0.0
 */
class MS_Helper_Template extends MS_Helper {

	const TARGET_DIRECTORY   = 'membership';
	const TEMPLATE_DIRECTORY = 'app/view/templates/';

	public static $ms_single_box        = array();
	public static $ms_registration_form = array();
	public static $ms_front_payment     = array();
	public static $ms_account           = array();

	public static function get_template_dir() {
		return MEMBERDASH_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::TEMPLATE_DIRECTORY;
	}

	public static function in_child_theme( $file ) {
		$path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . self::TARGET_DIRECTORY . DIRECTORY_SEPARATOR . $file;
		return file_exists( $path ) ? $path : false;
	}

	public static function in_parent_theme( $file ) {
		$path = get_template_directory() . DIRECTORY_SEPARATOR . self::TARGET_DIRECTORY . DIRECTORY_SEPARATOR . $file;
		return file_exists( $path ) ? $path : false;
	}

	public static function template_exists( $file ) {
		if ( $path = self::in_child_theme( $file ) ) { //phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure,WordPress.CodeAnalysis.AssignmentInCondition.Found
			return $path;
		} elseif ( $path = self::in_parent_theme( $file ) ) { //phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure,WordPress.CodeAnalysis.AssignmentInCondition.Found
			return $path;
		} else {
			return self::get_template_dir() . $file;
		}
	}

}
