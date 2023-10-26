<?php
/**
 * Membership Attributes View Settings Add-on.
 *
 * @since  1.0.0
 *
 * @package Memberdash
 */

/**
 * Membership Attributes View Settings Add-on.
 *
 * @since  1.0.0
 */
class MS_Addon_Attributes_View_Settings extends MS_View {

	/**
	 * Outputs the HTML code of the Settings form.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_tab() {
		$groups = $this->prepare_fields();

		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array(
					'title' => __( 'Custom Membership Attributes', 'memberdash' ),
					'desc'  => __( 'Define custom fields that are available in the Memberships Edit-Page.', 'memberdash' ),
				)
			);

			foreach ( $groups as $key => $fields ) {
				echo '<div class="ms-group ms-group-' . esc_attr( $key ) . '">';
				foreach ( $fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				echo '</div>';
			}
			MS_Helper_Html::html_separator();

			$help_link = MS_Controller_Plugin::get_admin_url(
				'help',
				array( 'tab' => 'shortcodes' )
			);

			printf(
				'<p>%s</p><ul><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ul>',
				esc_html__( 'How to use custom attribute values:', 'memberdash' ),
				sprintf(
				// translators: %1$s: opening link tag, %2$s: closing link tag, %3$s: shortcode.
					esc_html__( 'Via the %1$sshortcode%2$s %3$s', 'memberdash' ), // cspell:disable-line.
					'<a href="' . esc_url( $help_link ) . '#ms-membership-buy">',
					'</a>',
					'<code>[<b>' . esc_attr( MS_Addon_Attributes::SHORTCODE ) . '</b> slug="slug" id="..."]</code>'
				),
				sprintf(
					esc_html__( 'Via WordPress filter %s', 'memberdash' ),
					'<code>$val = apply_filters( "<b>ms_membership_attr</b>", "", "slug", $membership_id );</code>'
				),
				sprintf(
					esc_html__( 'Get via php function %s', 'memberdash' ),
					'<code>$val = <b>ms_membership_attr</b>( "slug", $membership_id );</code>'
				),
				sprintf(
					esc_html__( 'Set via php function %s', 'memberdash' ),
					'<code><b>ms_membership_attr_set</b>( "slug", $val, $membership_id );</code>'
				)
			);
			?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Prepare fields that are displayed in the form.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function prepare_fields() {
		$action_save   = MS_Addon_Attributes::AJAX_ACTION_SAVE_SETTING;
		$action_delete = MS_Addon_Attributes::AJAX_ACTION_DELETE_SETTING;

		$attribute_types = array(
			'text'     => __( 'Simple text field', 'memberdash' ),
			'number'   => __( 'Numeric field (integer)', 'memberdash' ),
			'textarea' => __( 'Multi-line text', 'memberdash' ),
			'bool'     => __( 'Yes|No', 'memberdash' ),
		);

		$field_def   = MS_Addon_Attributes::list_field_def();
		$fieldlist   = array();
		$fieldlist[] = array(
			__( 'Attribute Title', 'memberdash' ),
			__( 'Attribute Slug', 'memberdash' ),
			__( 'Attribute Type', 'memberdash' ),
			__( 'Attribute Infos', 'memberdash' ),
		);
		foreach ( $field_def as $field ) {
			$fieldlist[] = array(
				$field->title,
				'<code>' . $field->slug . '</code>',
				$field->type,
				$field->info,
			);
		}

		$fields = array();

		$fields['fields'] = array(
			'add_field' => array(
				'id'    => 'add_field',
				'type'  => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'New Attribute', 'memberdash' ),
				'class' => 'add_field',
			),
			'fieldlist' => array(
				'id'            => 'fieldlist',
				'type'          => MS_Helper_Html::TYPE_HTML_TABLE,
				'value'         => $fieldlist,
				'field_options' => array(
					'head_row' => true,
				),
				'class'         => 'field-list',
			),
		);

		$fields['editor no-auto-init'] = array(
			'title'         => array(
				'id'    => 'title',
				'class' => 'title',
				'type'  => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Attribute Name', 'memberdash' ),
				'desc'  => __( 'A human readable title of the Attribute.', 'memberdash' ),
			),
			'slug'          => array(
				'id'    => 'slug',
				'class' => 'slug',
				'type'  => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Attribute Slug', 'memberdash' ),
				'desc'  => __( 'You use the slug in the attribute shortcode and in PHP code to access a value.', 'memberdash' ),
			),
			'type'          => array(
				'id'            => 'type',
				'class'         => 'type',
				'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title'         => __( 'Attribute Type', 'memberdash' ),
				'desc'          => __( 'Decide what kind of data will be stored by the attribute.', 'memberdash' ),
				'field_options' => $attribute_types,
			),
			'info'          => array(
				'id'    => 'info',
				'class' => 'info',
				'type'  => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
				'title' => __( 'Attribute Infos', 'memberdash' ),
				'desc'  => __( 'Additional details displayed in the Membership editor. Only Admin users can see this value.', 'memberdash' ),
			),
			'old_slug'      => array(
				'id'    => 'old_slug',
				'class' => 'old_slug',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			),
			'action_save'   => array(
				'id'    => 'action_save',
				'class' => 'action_save',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action_save,
			),
			'nonce_save'    => array(
				'id'    => 'nonce_save',
				'class' => 'nonce_save',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action_save ),
			),
			'action_delete' => array(
				'id'    => 'action_delete',
				'class' => 'action_delete',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action_delete,
			),
			'nonce_delete'  => array(
				'id'    => 'nonce_delete',
				'class' => 'nonce_delete',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action_delete ),
			),
			'buttons'       => array(
				'type'  => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' =>
					MS_Helper_Html::html_element(
						array(
							'id'    => 'btn_delete',
							'class' => 'btn_delete button-link danger',
							'type'  => MS_Helper_Html::INPUT_TYPE_BUTTON,
							'value' => __( 'Delete', 'memberdash' ),
						),
						true
					) .
					MS_Helper_Html::html_element(
						array(
							'id'    => 'btn_cancel',
							'class' => 'btn_cancel close',
							'type'  => MS_Helper_Html::INPUT_TYPE_BUTTON,
							'value' => __( 'Cancel', 'memberdash' ),
						),
						true
					) .
					MS_Helper_Html::html_element(
						array(
							'id'    => 'btn_save',
							'class' => 'btn_save button-primary',
							'type'  => MS_Helper_Html::INPUT_TYPE_BUTTON,
							'value' => __( 'Save Attribute', 'memberdash' ),
						),
						true
					),
				'class' => 'buttons',
			),
		);

		return $fields;
	}
}
