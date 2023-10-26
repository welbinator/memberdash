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
class MS_View_Settings_Import_Settings extends MS_View {

	/**
	 * Displays the import preview form.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		$data    = apply_filters(
			'ms_import_preview_data_before',
			$this->data['model']->source
		);
		$compact = ! empty( $this->data['compact'] );
		if ( ! is_object( $data ) ) {
			$data = (object) array(
				'memberships' => array(),
				'members'     => array(),
				'notes'       => array(),
				'settings'    => array(),
				'source'      => '',
				'export_time' => '',
			);
		}

		// Converts object to array.
		if ( $data instanceof SimpleXMLElement ) {
			$data = MS_Helper_Utility::xml2array( $data );
			$data = (object) $data;
		}
		$data->memberships = isset( $data->memberships ) ? (array) $data->memberships : array();
		$data->members     = isset( $data->members ) ? (array) $data->members : array();

		$fields = $this->prepare_fields( $data );

		if ( $compact ) {
			$overview_box = array(
				$fields['batchsize'],
				$fields['sep'],
				$fields['clear_all'],
				$fields['skip'],
				$fields['import'],
			);
		} else {
			$overview_box = array(
				$fields['details'],
				$fields['sep'],
				$fields['batchsize'],
				$fields['sep'],
				$fields['clear_all'],
				$fields['back'],
				$fields['import'],
				$fields['download'],
			);
		}

		ob_start();
		MS_Helper_Html::settings_box(
			$overview_box,
			__( 'Import Overview', 'memberdash' )
		);

		if ( ! $compact ) {
			if ( ! empty( $data->memberships ) ) {
				MS_Helper_Html::settings_box(
					array( $fields['memberships'] ),
					__( 'List of all Memberships', 'memberdash' ),
					'',
					'open'
				);
			}

			if ( ! empty( $data->members ) ) {
				MS_Helper_Html::settings_box(
					array( $fields['members'] ),
					__( 'List of all Members', 'memberdash' ),
					'',
					'open'
				);
			}

			if ( isset( $data->settings ) ) {
				MS_Helper_Html::settings_box(
					$fields['settings'],
					__( 'Imported Settings', 'memberdash' ),
					'',
					'open'
				);
			}
		}

		echo '<script>window._ms_import_obj = ' . wp_json_encode( $data ) . '</script>';

		$html = ob_get_clean();

		return apply_filters(
			'ms_import_preview_object',
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
		// List of known Membership types; used to display the nice-name.
		$ms_types    = MS_Model_Membership::get_types();
		$ms_paytypes = MS_Model_Membership::get_payment_types();

		$total_memberships = 0;
		$total_members     = 0;

		// Prepare the "Memberships" table.
		$memberships = array();
		if ( isset( $data->memberships ) && ! empty( $data->memberships ) ) {
			$memberships = array(
				array(
					__( 'Membership name', 'memberdash' ),
					__( 'Membership Type', 'memberdash' ),
					__( 'Payment Type', 'memberdash' ),
					__( 'Description', 'memberdash' ),
				),
			);

			foreach ( $data->memberships as $item ) {
				if ( is_array( $item ) && isset( $item['membership'] ) && is_array( $item['membership'] ) ) {
					foreach ( $item['membership'] as $membership ) {
						$membership    = (object) $membership;
						$output        = MS_Helper_Import::membership_to_view( $membership, $ms_types, $ms_paytypes );
						$memberships[] = $output;
						$total_memberships++;
					}
				} else {
					$output        = MS_Helper_Import::membership_to_view( $item, $ms_types, $ms_paytypes );
					$memberships[] = $output;
					$total_memberships++;
				}
			}
		}

		$members = array();
		if ( isset( $data->members ) && ! empty( $data->members ) ) {
			// Prepare the "Members" table.
			$members = array(
				array(
					__( 'Username', 'memberdash' ),
					__( 'Email', 'memberdash' ),
					__( 'Subscriptions', 'memberdash' ),
					__( 'Invoices', 'memberdash' ),
				),
			);

			foreach ( $data->members as $item ) {
				if ( is_array( $item ) && isset( $item['member'] ) && is_array( $item['member'] ) ) {
					foreach ( $item['member'] as $member ) {
						$member    = (object) $member;
						$output    = MS_Helper_Import::member_to_view( $member );
						$members[] = $output;
						$total_members++;
					}
				} else {
					$output    = MS_Helper_Import::member_to_view( $item );
					$members[] = $output;
					$total_members++;
				}
			}
		}

		$settings = [];
		if ( isset( $data->settings ) ) {
			foreach ( $data->settings as $setting => $group_fields ) {
				switch ( $setting ) {
					case 'global_payment_settings':
						/**
						 * Global Payment Settings View.
						 *
						 * @var MS_View_Settings_Page_Payment $global_payment_view
						 */
						$global_payment_view = MS_Factory::load( 'MS_View_Settings_Page_Payment' );

						$html_fields = $global_payment_view->get_global_payment_fields();

						$group_values = array(
							array(
								__( 'Field', 'memberdash' ),
								__( 'Value', 'memberdash' ),
							),
						);

						foreach ( $group_fields as $key => $val ) {
							$group_values[] = array(
								$html_fields[ $key ]['title'],
								$val,
							);
						}

						$settings[ __( 'Global Payment Settings', 'memberdash' ) ] = $group_values;

						break;
				}
			}
		}

		// Prepare the return value.
		$fields = array();

		// Export-Notes.
		$notes = '';
		if ( isset( $data->notes ) ) {
			if ( is_scalar( $data->notes ) ) {
				$notes = array( $data->notes );
			}

			$in_sub = false;
			$notes  = '<ul class="ms-import-notes">';
			foreach ( $data->notes as $line => $text ) {
				if ( is_array( $text ) && isset( $text['note'] ) ) {
					$text = $text['note'];
				}
				$is_sub = ( strpos( $text, '- ' ) === 0 );
				if ( $in_sub != $is_sub ) {
					$in_sub = $is_sub;
					if ( $is_sub ) {
						$notes .= '<ul>';
					} else {
						$notes .= '</ul>';
					}
				}
				if ( $in_sub ) {
					$text = substr( $text, 2 );
				}
				$notes .= '<li>' . $text;
			}
			$notes .= '</ul>';
		}

		if ( ( isset( $data->memberships ) && ! empty( $data->memberships ) ) && ( isset( $data->members ) && ! empty( $data->members ) ) ) {

			$fields['details'] = array(
				'type'          => MS_Helper_Html::TYPE_HTML_TABLE,
				'class'         => 'ms-import-preview',
				'value'         => array(
					array(
						__( 'Data source', 'memberdash' ),
						$data->source .
						' &emsp; <small>' .
						sprintf(
							__( 'exported on %1$s', 'memberdash' ),
							$data->export_time
						) .
						'</small>',
					),
					array(
						__( 'Content', 'memberdash' ),
						sprintf(
							_n(
								'%1$s Membership',
								'%1$s Memberships',
								$total_memberships,
								'memberdash'
							),
							'<b>' . $total_memberships . '</b>'
						) . ' / ' . sprintf(
							_n(
								'%1$s Member',
								'%1$s Members',
								$total_members,
								'memberdash'
							),
							'<b>' . $total_members . '</b>'
						),
					),
				),
				'field_options' => array(
					'head_col'  => true,
					'head_row'  => false,
					'col_class' => array( 'preview-label', 'preview-data' ),
				),
			);
		} elseif ( isset( $data->memberships ) && ! empty( $data->memberships ) ) {
			$fields['details'] = array(
				'type'          => MS_Helper_Html::TYPE_HTML_TABLE,
				'class'         => 'ms-import-preview',
				'value'         => array(
					array(
						__( 'Data source', 'memberdash' ),
						$data->source .
						' &emsp; <small>' .
						sprintf(
							__( 'exported on %1$s', 'memberdash' ),
							$data->export_time
						) .
						'</small>',
					),
					array(
						__( 'Content', 'memberdash' ),
						sprintf(
							_n(
								'%1$s Membership',
								'%1$s Memberships',
								$total_memberships,
								'memberdash'
							),
							'<b>' . $total_memberships . '</b>'
						),
					),
				),
				'field_options' => array(
					'head_col'  => true,
					'head_row'  => false,
					'col_class' => array( 'preview-label', 'preview-data' ),
				),
			);
		} elseif ( isset( $data->members ) && ! empty( $data->members ) ) {
			$fields['details'] = array(
				'type'          => MS_Helper_Html::TYPE_HTML_TABLE,
				'class'         => 'ms-import-preview',
				'value'         => array(
					array(
						__( 'Data source', 'memberdash' ),
						$data->source .
						' &emsp; <small>' .
						sprintf(
							__( 'exported on %1$s', 'memberdash' ),
							$data->export_time
						) .
						'</small>',
					),
					array(
						__( 'Content', 'memberdash' ),
						sprintf(
							_n(
								'%1$s Member',
								'%1$s Members',
								$total_members,
								'memberdash'
							),
							'<b>' . $total_members . '</b>'
						),
					),
				),
				'field_options' => array(
					'head_col'  => true,
					'head_row'  => false,
					'col_class' => array( 'preview-label', 'preview-data' ),
				),
			);
		}

		if ( ! empty( $notes ) ) {
			$fields['details']['value'][] = array(
				__( 'Please note', 'memberdash' ),
				$notes,
			);
		}

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

		$fields['clear_all'] = array(
			'id'    => 'clear_all',
			'type'  => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
			'title' => __( 'Replace current content with import data (removes existing Memberships/Members before importing data)', 'memberdash' ),
			'class' => 'widefat',
		);

		if ( ! empty( $memberships ) ) {
			$fields['memberships'] = array(
				'type'          => MS_Helper_Html::TYPE_HTML_TABLE,
				'class'         => 'ms-import-preview',
				'value'         => $memberships,
				'field_options' => array(
					'head_col'  => false,
					'head_row'  => true,
					'col_class' => array( 'preview-name', 'preview-type', 'preview-pay-type', 'preview-desc' ),
				),
			);
		}

		if ( ! empty( $members ) ) {
			$fields['members'] = array(
				'type'          => MS_Helper_Html::TYPE_HTML_TABLE,
				'class'         => 'ms-import-preview',
				'value'         => $members,
				'field_options' => array(
					'head_col'  => false,
					'head_row'  => true,
					'col_class' => array( 'preview-name', 'preview-email', 'preview-count', 'preview-count' ),
				),
			);
		}
		if ( ! empty( $settings ) ) {
			$fields['settings'] = [];

			foreach ( $settings as $group_title => $group_fields ) {
				$fields['settings'][] = array(
					'type'  => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => $group_title,
				);

				$fields['settings'][] = array(
					'type'          => MS_Helper_Html::TYPE_HTML_TABLE,
					'class'         => 'ms-import-preview',
					'value'         => $group_fields,
					'field_options' => array(
						'head_col' => false,
						'head_row' => true,
					),
				);
			}
		}

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
			'id'           => 'btn-import',
			'type'         => MS_Helper_Html::INPUT_TYPE_BUTTON,
			'value'        => __( 'Import', 'memberdash' ),
			'button_value' => MS_Controller_Import::AJAX_ACTION_IMPORT,
			'button_type'  => 'submit',
		);

		$fields['download'] = array(
			'id'    => 'btn-download',
			'type'  => MS_Helper_Html::INPUT_TYPE_BUTTON,
			'value' => __( 'Download as Export File', 'memberdash' ),
			'class' => 'button-link',
		);

		return $fields;
	}
}
