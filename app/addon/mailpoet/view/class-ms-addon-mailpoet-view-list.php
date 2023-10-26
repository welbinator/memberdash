<?php
/**
 * Renders MailPoet list.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage View
 */
class MS_Addon_Mailpoet_View_List extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function render_tab() {
		$fields = $this->prepare_fields();

		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array(
					'title' => __( 'MailPoet List', 'memberdash' ),
					'desc'  => '',
				)
			);

			echo '<div class="ms-attributes">';
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			echo '</div>';
			?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	protected function prepare_fields() {

		$membership = $this->data['membership'];
		$addon      = MS_Factory::load( 'MS_Addon_Mailpoet' );

		$list   = $addon->ms_mailpoet_list();
		$action = MS_Addon_MailPoet::AJAX_ACTION_SAVE_LIST_ID;
		$value  = $addon->ms_mailpoet_get_membership_list_id( $membership );
		$fields = array(
			'duration' => array(
				'id'                    => 'mailpoet',
				'title'                 => 'Select List',
				'desc'                  => 'When user subscribe to this membership user will subscribe to selected list',
				'type'                  => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options'         => $list,
				'value'                 => $value,
				'attr_data_placeholder' => 'Select List',
				'ajax_data'             => array(
					'action'        => $action,
					'_wpnonce'      => wp_create_nonce( $action ),
					'field'         => 'course',
					'membership_id' => $membership->id,
				),
			),
		);

		return apply_filters(
			'ms_addon_learndash_view_edit_prepare_fields',
			$fields,
			$this
		);
	}

}
