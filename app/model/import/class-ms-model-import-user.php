<?php
/**
 * Model class for importing users from a CSV file.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Model
 */

/**
 * Model class for importing users from a CSV file.
 *
 * @since 1.0.0
 */
class MS_Model_Import_User extends MS_Model_Import {

	/**
	 * Process the import;
	 *
	 * Parse the uploaded CSV file and import the data.
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
			self::_message( 'error', __( 'Please upload a CSV file.', 'memberdash' ) );
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

		// validate file type.

		/**
		 * Filter the valid file types for CSV import.
		 *
		 * @since 1.0.3
		 *
		 * @return array<string, string>
		 */
		$valid_filetypes = apply_filters(
			'memberdash_csv_import_valid_filetypes',
			array(
				'csv' => 'text/csv',
				'txt' => 'text/plain',
			)
		);

		$filetype = wp_check_filetype( $file['name'], $valid_filetypes );

		if ( ! in_array( $filetype['type'], $valid_filetypes, true ) ) {
			self::_message( 'error', __( 'Invalid file type. Please try again.', 'memberdash' ) );
			return false;
		}

		$membership = isset( $_POST['users-membership'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
					? MS_Helper_Cast::to_int(
						sanitize_text_field(
							wp_unslash(
								$_POST['users-membership'] // phpcs:ignore WordPress.Security.NonceVerification.Missing
							)
						)
					)
					: 0;

		$status = isset( $_POST['users-status'] ) ? sanitize_text_field( wp_unslash( $_POST['users-status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$start  = isset( $_POST['users-start'] ) ? sanitize_text_field( wp_unslash( $_POST['users-start'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$expire = isset( $_POST['users-expire'] ) ? sanitize_text_field( wp_unslash( $_POST['users-expire'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// validate CSV content.

		$required_header = [ 'username', 'email' ];

		$csv_content = array_map( 'str_getcsv', file( $file['tmp_name'] ) );

		$csv_header = array_shift( $csv_content );
		$csv_header = $csv_header
					? array_map(
						function( $item ) {
							return strtolower( MS_Helper_Cast::to_string( $item ) );
						},
						$csv_header
					)
					: null;

		if ( ! empty( $csv_header ) ) {
			if ( ! empty( array_diff( $required_header, $csv_header ) ) ) {
				self::_message( 'error', __( 'Invalid CSV file: required column headers are missing. Please check the sample file.', 'memberdash' ) );
				return false;
			}

			array_walk(
				$csv_content,
				function( &$item ) use ( $csv_header ) {
					// make sure each row has the same number of columns.
					$item = array_pad( $item, count( $csv_header ), '' );

					// combine column header with row data.
					$item = array_combine( $csv_header, $item );
				}
			);
		}

		if ( empty( $csv_content ) ) {
			self::_message( 'error', __( 'No valid user CSV file uploaded. Please try again.', 'memberdash' ) );
			return false;
		}

		$users = $this->create_users_object_from_csv( $csv_content );

		if ( empty( $users ) ) {
			self::_message( 'error', __( 'No valid users were found. Please try again', 'memberdash' ) );
			return false;
		}

		$this->source = array(
			'membership' => $membership,
			'status'     => $status,
			'start'      => $start,
			'expire'     => $expire,
			'users'      => $users,
		);

		return true;
	}

	/**
	 * Creates an array of users objects from the CSV content.
	 *
	 * @since 1.0.3
	 *
	 * @param array<array<mixed>> $csv_content The CSV content.
	 *
	 * @return array<object>
	 */
	private function create_users_object_from_csv( array $csv_content ): array {
		$users = array();

		foreach ( $csv_content as $item ) {
			$user = new stdClass();

			if (
				empty( $item['username'] )
				|| empty( $item['email'] )
			) {
				continue; // skip invalid user.
			}

			$user->username     = $item['username'];
			$user->email        = $item['email'];
			$user->firstname    = $item['firstname'] ?? '';
			$user->lastname     = $item['lastname'] ?? '';
			$user->membershipid = $item['membershipid'] ?? '';

			$users[] = $user;
		}

		return $users;
	}
}

