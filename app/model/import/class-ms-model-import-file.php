<?php
/**
 * Base class for all import handlers.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Model
 */

/**
 * Base class for all import handlers.
 *
 * @since 1.0.0
 */
class MS_Model_Import_File extends MS_Model_Import {
	/**
	 * This function parses the Import source (i.e. an file-upload) and returns
	 * true in case the source data is valid. When returning true then the
	 * $source property of the model is set to the sanitized import source data.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function prepare() {
		self::_message( 'preview', false );

		if ( empty( $_FILES ) || ! isset( $_FILES['upload'] ) ) {
			self::_message( 'error', __( 'No file was uploaded. Please try again.', 'memberdash' ) );
			return false;
		}

		$file = wp_unslash( $_FILES['upload'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $file['name'] ) ) {
			self::_message( 'error', __( 'Please upload an export file.', 'memberdash' ) );
			return false;
		}

		if ( empty( $file['size'] ) ) {
			self::_message( 'error', __( 'The uploaded file is empty. Please try again.', 'memberdash' ) );
			return false;
		}

		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			self::_message( 'error', __( 'Uploaded file not found. Please try again.', 'memberdash' ) );
			return false;
		}

		$content = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( empty( $content ) ) {
			self::_message( 'error', __( 'Error reading uploaded file. Please try again.', 'memberdash' ) );
			return false;
		}

		$data_type = MS_Model_Export::JSON_EXPORT;
		try {
			$data = json_decode( $content );
		} catch ( Exception $ex ) {
			$data      = '';
			$data_type = false;
		}

		$json_error = json_last_error();
		if ( $json_error !== JSON_ERROR_NONE ) {
			libxml_use_internal_errors( true );

			$data = simplexml_load_string( $content );
			if ( $data === false ) {
				$data      = '';
				$data_type = false;
			} else {
				$data_type = MS_Model_Export::XML_EXPORT;
			}

			libxml_clear_errors();
		}

		if ( $data_type !== false ) {
			$data = $this->validate_data( $data );
		}

		if (
			$data_type === false
			|| empty( $data )
		) {
			self::_message( 'error', __( 'No valid export file uploaded. Please try again.', 'memberdash' ) );
			return false;
		}

		$this->source = $data;
		return true;
	}

	/**
	 * Returns true if the specific import-source is present and can be used
	 * for import.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function present() {
		return true;
	}

}
