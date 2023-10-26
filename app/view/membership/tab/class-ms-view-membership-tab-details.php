<?php

/**
 * Tab: Edit Membership Details
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 * @package MemberDash
 * @subpackage View
 */
class MS_View_Membership_Tab_Details extends MS_View {

	/**
	 * Returns the content of the dialog
	 *
	 * @since 1.0.0
	 *
	 * @return object
	 */
	public function to_html() {
		$field      = $this->get_fields();
		$membership = $this->data['membership'];

		ob_start();
		?>
		<div>
			<form class="ms-form memberdash-ajax-update ms-edit-membership" data-memberdash-ajax="<?php echo esc_attr( 'save' ); ?>">
				<div class="ms-form memberdash-form ms-space-y-5 ms-flex ms-flex-col">
					<?php
					MS_Helper_Html::html_element( $field['name'] );

					if ( ! $membership->is_system() ) {
						MS_Helper_Html::html_element( $field['description'] );
					}

					MS_Helper_Html::html_element( $field['active'] );

					if ( ! $membership->is_system() ) {
						MS_Helper_Html::html_element( $field['public'] );
						MS_Helper_Html::html_element( $field['paid'] );
						MS_Helper_Html::html_element( $field['priority'] );
					}
					?>
				</div>
			</form>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_membership_edit_to_html', $html, $field, $membership );
	}

	/**
	 * Prepares fields for the edit form.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_fields() {
		$membership = $this->data['membership'];
		$action     = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
		$nonce      = wp_create_nonce( $action );

		$fields = array();

		// Prepare the form fields.
		$fields['name'] = array(
			'id'        => 'name',
			'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
			'title'     => apply_filters(
				'ms_translation_flag',
				__( 'Name:', 'memberdash' ),
				'membership-name'
			),
			'value'     => $membership->name,
			'ajax_data' => array( 1 ),
		);

		$fields['description'] = array(
			'id'        => 'description',
			'type'      => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
			'title'     => apply_filters(
				'ms_translation_flag',
				__( 'Description:', 'memberdash' ),
				'membership-name'
			),
			'value'     => $membership->description,
			'ajax_data' => array( 1 ),
		);

		$fields['active'] = array(
			'id'        => 'active',
			'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title'     => __( 'This membership is active', 'memberdash' ),
			'class'     => 'ms-active',
			'value'     => $membership->active,
			'ajax_data' => array( 1 ),
		);

		$fields['public'] = array(
			'id'        => 'public',
			'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title'     => __( 'This membership is public', 'memberdash' ),
			'desc'      => __( 'Users can see it listed on your site and can register for it', 'memberdash' ),
			'class'     => 'ms-public',
			'value'     => $membership->public,
			'ajax_data' => array( 1 ),
		);

		$fields['paid'] = array(
			'id'        => 'is_paid',
			'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'title'     => __( 'This is a paid membership', 'memberdash' ),
			'class'     => 'ms-paid',
			'value'     => $membership->is_paid,
			'ajax_data' => array( 1 ),
		);

		$priority_list = array();
		$args          = array( 'include_guest' => 0 );
		$count         = MS_Model_Membership::get_membership_count( $args );
		for ( $i = 1; $i <= $count; $i++ ) {
			$priority_list[ $i ] = $i;
		}
		$priority_list[ $membership->priority ] = $membership->priority;

		$fields['priority'] = array(
			'id'            => 'priority',
			'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
			'title'         => __( 'Membership order', 'memberdash' ),
			'desc'          => __( 'This defines the display order on the Membership Page.', 'memberdash' ),
			'class'         => 'ms-priority',
			'before'        => __( 'Order', 'memberdash' ),
			'value'         => $membership->priority,
			'field_options' => $priority_list,
			'ajax_data'     => array( 1 ),
		);

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
			$fields['priority']['desc'] .= '<br>' .
				__( 'It also controls which Protection Message is used in case a member has multiple memberships (the lowest value wins)', 'memberdash' );
		}

				$fields = apply_filters(
					'ms_view_membership_details_tab',
					$fields,
					$membership,
					$this->data
				);

		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['ajax_data'] ) ) {
				if ( ! empty( $field['ajax_data']['action'] ) ) {
					continue;
				}

				if ( ! isset( $fields[ $key ]['ajax_data']['field'] ) ) {
					$fields[ $key ]['ajax_data']['field'] = $fields[ $key ]['id'];
				}
				$fields[ $key ]['ajax_data']['_wpnonce']      = $nonce;
				$fields[ $key ]['ajax_data']['action']        = $action;
				$fields[ $key ]['ajax_data']['membership_id'] = $membership->id;
			}
		}

		return $fields;
	}

};
