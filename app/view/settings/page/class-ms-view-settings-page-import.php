<?php
/**
 * View.
 *
 * @package MemberDash
 */

/**
 * Render the Settings > Import Tool pages.
 */
class MS_View_Settings_Page_Import extends MS_View_Settings_Edit {

	/**
	 * Returns the page contents.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$export_action      = MS_Controller_Import::ACTION_EXPORT;
		$import_action      = MS_Controller_Import::ACTION_PREVIEW;
		$import_user_action = MS_Controller_Import::ACTION_IMPORT_USER;
		$messages           = $this->data['message'];

		$preview = false;
		if ( isset( $messages['preview'] ) ) {
			$preview = $messages['preview'];
		}

		$export_fields = array(
			'type'      => array(
				'id'            => 'type',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'Select export type', 'memberdash' ),
				'field_options' => $this->data['types'],
				'class'         => 'ms-select ms-select-type',
			),
			'format'    => array(
				'id'            => 'format',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'Select export format', 'memberdash' ),
				'field_options' => $this->data['formats'],
				'class'         => 'ms-select ms-select-format',
			),
			'separator' => array(
				'type'  => MS_Helper_Html::TYPE_HTML_SEPARATOR,
				'value' => 'horizontal',
			),
			'export'    => array(
				'id'    => 'btn_export',
				'type'  => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Generate Export', 'memberdash' ),
				'class' => 'ms-bg-black ms-border-black ms-text-white ms-shadow-none',
			),
			'action'    => array(
				'id'    => 'action',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $export_action,
			),
			'nonce'     => array(
				'id'    => '_wpnonce',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $export_action ),
			),
		);

		$file_field     = array(
			'id'       => 'upload',
			'type'     => MS_Helper_Html::INPUT_TYPE_FILE,
			'title'    => __( 'From export file (.json or .xml)', 'memberdash' ),
			'required' => true,
		);
		$import_options = array(
			'file'       => array(
				'text'     => MS_Helper_Html::html_element( $file_field, true ),
				'disabled' => ! MS_Model_Import_File::present(),
			),
			'membership' => array(
				'text'     => __( 'Membership', 'memberdash' ),
				'disabled' => ! MS_Model_Import_Membership::present(),
			),
		);

		$sel_source = 'file';
		if ( isset( $_REQUEST['import_source'] )
			&& isset( $import_options[ $_REQUEST['import_source'] ] )
		) {
			$sel_source = $_REQUEST['import_source'];
		}

		$import_fields = array(
			'source'    => array(
				'id'            => 'import_source',
				'type'          => MS_Helper_Html::INPUT_TYPE_RADIO,
				'title'         => __( 'Choose an import source', 'memberdash' ),
				'field_options' => $import_options,
				'value'         => $sel_source,
			),
			'separator' => array(
				'type'  => MS_Helper_Html::TYPE_HTML_SEPARATOR,
				'value' => 'horizontal',
			),
			'import'    => array(
				'id'    => 'btn_import',
				'type'  => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Preview Import', 'memberdash' ),
				'class' => 'ms-bg-black ms-border-black ms-text-white ms-shadow-none',
			),
			'action'    => array(
				'id'    => 'action',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $import_action,
			),
			'nonce'     => array(
				'id'    => '_wpnonce',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $import_action ),
			),
		);

		$status_options = array(
			MS_Model_Relationship::STATUS_PENDING     => __( 'Pending (activate on next payment)', 'memberdash' ),
			MS_Model_Relationship::STATUS_WAITING     => __( 'Waiting (activate on start date)', 'memberdash' ),
			MS_Model_Relationship::STATUS_ACTIVE      => __( 'Active', 'memberdash' ),
			MS_Model_Relationship::STATUS_CANCELED    => __( 'Cancelled (deactivate on expire date)', 'memberdash' ),
			MS_Model_Relationship::STATUS_EXPIRED     => __( 'Expired (no access) ', 'memberdash' ),
			MS_Model_Relationship::STATUS_DEACTIVATED => __( 'Deactivated (no access)', 'memberdash' ),
		);

		$import_users_fields = array(
			'file'       => array(
				'id'       => 'upload',
				'type'     => MS_Helper_Html::INPUT_TYPE_FILE,
				'title'    => sprintf(
					// translators: %1$s and %2$s are placeholders for HTML 'a' tags.
					__( 'User List CSV File (.csv or .txt) %1$sDownload sample file%2$s', 'memberdash' ),
					'<a href="' . $this->data['sample'] . '">',
					'</a>'
				),
				'required' => true,
			),
			'membership' => array(
				'id'            => 'users-membership',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => MS_Model_Export::get_memberships(),
				'class'         => 'ms-select',
				'title'         => __( 'Optionally assign users to selected membership', 'memberdash' ),
			),
			'status'     => array(
				'id'            => 'users-status',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $status_options,
				'class'         => 'ms-select',
				'title'         => __( 'Optionally assign users a selected status', 'memberdash' ),
			),
			'start'      => array(
				'name'  => 'users-start',
				'type'  => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
				'title' => __( 'Start Date', 'memberdash' ),
			),
			'expire'     => array(
				'name'  => 'users-expire',
				'type'  => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
				'title' => __( 'Expire Date', 'memberdash' ),
			),
			'separator'  => array(
				'type'  => MS_Helper_Html::TYPE_HTML_SEPARATOR,
				'value' => 'horizontal',
			),
			'import'     => array(
				'id'    => 'btn_user_import',
				'type'  => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Upload Users', 'memberdash' ),
				'class' => 'ms-bg-black ms-border-black ms-text-white ms-shadow-none',
			),
			'action'     => array(
				'id'    => 'action',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $import_user_action,
			),
			'nonce'      => array(
				'id'    => '_wpnonce',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $import_user_action ),
			),
		);

		ob_start();

		MS_Helper_Html::settings_tab_header(
			array( 'title' => __( 'Import Tool', 'memberdash' ) )
		);
		?>

		<?php if ( $preview ) : ?>
			<form action="" method="post">
				<?php echo $preview; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</form>
		<?php else : ?>
			<form action="" method="post" enctype="multipart/form-data">
				<?php
				MS_Helper_Html::settings_box(
					$import_fields,
					__( 'Import data', 'memberdash' ),
					__(
						'Import data into this installation.',
						'memberdash'
					)
				);
				?>
			</form>
			<form action="" method="post">
				<?php
				MS_Helper_Html::settings_box(
					$export_fields,
					__( 'Export data', 'memberdash' ),
					__( 'Generate an export file using one of the options above.', 'memberdash' )
				);
				?>
			</form>
			<form action="" method="post" enctype="multipart/form-data">
				<?php
				MS_Helper_Html::settings_box(
					$import_users_fields,
					__( 'Bulk Import users', 'memberdash' ),
					__(
						'Upload and create users as members. All uploaded members will have active subscriptions.',
						'memberdash'
					)
				);
				?>
			</form>
		<?php endif; ?>

		<?php
		return ob_get_clean();
	}
}
