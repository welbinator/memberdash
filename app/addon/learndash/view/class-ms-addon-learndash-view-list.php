<?php
/**
 * Renders LearnDash Course list.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage View
 */
class MS_Addon_Learndash_View_List extends MS_View {

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
					'title' => __( 'LearnDash Courses', 'memberdash' ),
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

		$courses = MS_Addon_Learndash::ms_list_of_learndash_courses();
		$action  = MS_Addon_Learndash::AJAX_ACTION_SAVE_COURSES;
		$value   = MS_Addon_Learndash::ms_get_learndash_membership_courses( $membership );
		$fields  = array(
			'duration' => array(
				'id'                    => 'course',
				'title'                 => 'Select Course',
				'desc'                  => 'When user subscribe to this membership user will enroll to selected course',
				'type'                  => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options'         => $courses,
				'value'                 => $value,
				'multiple'              => true,
				'attr_data_placeholder' => 'Select Course',
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
