<?php
/**
 * View.
 *
 * @package MemberDash
 */

/**
 * Displays the Import preview.
 *
 * @since 1.0.0
 */
class MS_View_Settings_Import_Users extends MS_View {

	/**
	 * Displays the import preview form.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {

		$data = apply_filters(
			'ms_import_preview_users_data_before',
			$this->data['model']->source
		);

		$data = (object) $data;

		$fields = $this->prepare_fields( $data );

		$overview_box = array(
			$fields['details'],
			$fields['sep'],
			$fields['batchsize'],
			$fields['sep'],
			$fields['back'],
			$fields['import'],
		);

		ob_start();
		MS_Helper_Html::settings_box(
			$overview_box,
			__( 'Import Overview', 'memberdash' )
		);

		MS_Helper_Html::settings_box(
			array( $fields['users'] ),
			__( 'List of all users', 'memberdash' ),
			'',
			'open'
		);
		$data->source_key = 'membership';
		echo '<script>window._ms_import_obj = ' . wp_json_encode( $data ) . '</script>';

		$html = ob_get_clean();

		return apply_filters(
			'ms_import_users_preview_object',
			$html,
			$data
		);
	}


	/**
	 * Prepare the HTML fields that can be displayed
	 *
	 * @since 1.0.0
	 *
	 * @param  object $data The import data object.
	 * @return array
	 */
	protected function prepare_fields( $data ) {
		$users = array(
			array(
				__( 'Username', 'memberdash' ),
				__( 'Email', 'memberdash' ),
				__( 'Membership', 'memberdash' ),
				__( 'Status', 'memberdash' ),
				__( 'Start Date', 'memberdash' ),
				__( 'Expire Date', 'memberdash' ),
			),
		);

		$membership       = $data->membership;
		$membership_name  = false;
		$membership_names = array();

		if ( $membership ) {
			$membership      = MS_Factory::load(
				'MS_Model_Membership',
				$membership
			);
			$membership_name = $membership->name;
		}

		foreach ( $data->users as $item ) {
			if ( ! $membership_name ) {
				// check if exist in $membership_names.
				if ( isset( $membership_names[ $item->membershipid ] ) ) {
					$membership_import_name = $membership_names[ $item->membershipid ];
				} else {
					$membership = MS_Factory::load(
						'MS_Model_Membership',
						$item->membershipid
					);
					if ( $membership->id ) {
						$membership_import_name = $membership->name;
					} else {
						$membership_import_name = __( 'N/A', 'memberdash' );
					}

					// save to $membership_names.
					$membership_names[ $item->membershipid ] = $membership_import_name;
				}
			} else {
				$membership_import_name = $membership_name;
			}

			$users[] = array(
				$item->username,
				$item->email,
				$membership_import_name,
				$data->status,
				$data->start,
				$data->expire,
			);
		}

		$fields['details'] = array(
			'type'          => MS_Helper_Html::TYPE_HTML_TABLE,
			'class'         => 'ms-import-preview',
			'value'         => array(
				array(
					__( 'Content', 'memberdash' ),
					sprintf( '%1$s Users', '<b>' . count( $data->users ) . '</b>' ),
				),
			),
			'field_options' => array(
				'head_col'  => true,
				'head_row'  => false,
				'col_class' => array( 'preview-label', 'preview-data' ),
			),
		);

		$batchsizes = array(
			1   => __( 'Each item on its own', 'memberdash' ),
			10  => __( 'Small (10 items)', 'memberdash' ),
			30  => __( 'Normal (30 items)', 'memberdash' ),
			100 => __( 'Big (100 items)', 'memberdash' ),
		);

		$fields['batchsize'] = array(
			'id'            => 'batchsize',
			'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
			'title'         => __( 'Batch size for import', 'memberdash' ),
			'desc'          => __( 'Big batches will be processed faster but may result in PHP Memory errors.', 'memberdash' ),
			'value'         => 10,
			'field_options' => $batchsizes,
			'class'         => 'sel-batchsize',
		);

		$fields['users'] = array(
			'type'          => MS_Helper_Html::TYPE_HTML_TABLE,
			'class'         => 'ms-import-preview',
			'value'         => $users,
			'field_options' => array(
				'head_col'  => false,
				'head_row'  => true,
				'col_class' => array( 'preview-name', 'preview-email', 'preview-count', 'preview-count' ),
			),
		);

		$fields['sep'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
		);

		$fields['back'] = array(
			'type'  => MS_Helper_Html::TYPE_HTML_LINK,
			'class' => 'memberdash-field-button button',
			'value' => __( 'Cancel', 'memberdash' ),
			'url'   => $_SERVER['REQUEST_URI'],
		);

		$fields['skip'] = array(
			'type'  => MS_Helper_Html::TYPE_HTML_LINK,
			'class' => 'memberdash-field-button button',
			'value' => __( 'Skip', 'memberdash' ),
			'url'   => MS_Controller_Plugin::get_admin_url(
				false,
				array( 'skip_import' => 1 )
			),
		);

		$fields['import'] = array(
			'id'           => 'btn-user-import',
			'type'         => MS_Helper_Html::INPUT_TYPE_BUTTON,
			'value'        => __( 'Import', 'memberdash' ),
			'button_value' => MS_Controller_Import::AJAX_ACTION_IMPORT_USERS,
			'button_type'  => 'submit',
		);

		return $fields;
	}
}

