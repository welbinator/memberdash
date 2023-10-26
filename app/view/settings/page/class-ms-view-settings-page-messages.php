<?php
/**
 * Membership Settings Page.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

/**
 * Membership Settings Page class.
 *
 * @since 1.0.0
 */
class MS_View_Settings_Page_Messages extends MS_View_Settings_Edit {

	/**
	 * Return the HTML form.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		$fields = $this->get_fields();

		// TODO: Fix that condition again.
		$has_more = true;
		$title    = __( 'Protection Messages', 'memberdash' );

		if ( isset( $this->data['membership'] ) ) {
			$membership = $this->data['membership'];
		} else {
			$membership = false;
		}

		if ( $membership instanceof MS_Model_Membership ) {
			$settings_url = MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => MS_Controller_Settings::TAB_MESSAGES )
			);
			$desc         = sprintf(
			// translators: 1. Opening anchor tag, 2. Closing anchor tag.
				__( 'Here you can override %1$sdefault settings%2$s for this membership.', 'memberdash' ), // cspell:disable-line.
				'<a href="' . $settings_url . '">',
				'</a>'
			);
		} else {
			$desc = '';
		}

		ob_start();
		MS_Helper_Html::settings_tab_header(
			array(
				'title' => $title,
				'desc'  => $desc,
			)
		);
		?>
		<form class="ms-form ms-flex ms-flex-col ms-space-y-6" action="" method="post">
			<?php
			$subtitle = apply_filters(
				'ms_translation_flag',
				__( 'Content protection message', 'memberdash' ),
				'message-protected'
			);
			MS_Helper_Html::settings_box(
				$fields['content'],
				$subtitle,
				'',
				'open'
			);

			$subtitle = apply_filters(
				'ms_translation_flag',
				__( 'Shortcode protection message', 'memberdash' ),
				'message-shortcode'
			);
			MS_Helper_Html::settings_box(
				$fields['shortcode'],
				$subtitle,
				'',
				'open'
			);

			if ( $has_more ) {
				$subtitle = apply_filters(
					'ms_translation_flag',
					__( 'More tag protection message', 'memberdash' ),
					'message-more_tag'
				);
				MS_Helper_Html::settings_box(
					$fields['more_tag'],
					$subtitle,
					'',
					'open'
				);
			}
			?>
		</form>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * Prepare the fields that are displayed in the form.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_fields() {
		$settings = $this->data['settings'];

		if ( isset( $this->data['membership'] ) ) {
			$membership = $this->data['membership'];
		} else {
			$membership = false;
		}

		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_PROTECTION_MSG;
		$nonce  = wp_create_nonce( $action );
		$groups = array();

		$message_content = $settings->get_protection_message(
			MS_Model_Settings::PROTECTION_MSG_CONTENT,
			$membership,
			$override_content
		);

		$groups['content'] = array(
			'description' => array(
				'type'          => MS_Helper_Html::TYPE_HTML_TEXT,
				'value'         => __( 'Message displayed when the whole page is protected via shortcode <code>[ms-protection-message]</code>.', 'memberdash' ),
				'wrapper_class' => 'ms-block ms-text-base ms-font-light',
			),
			'override'    => array(
				'id'            => 'override_content',
				'type'          => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value'         => $override_content,
				'before'        => __( 'Use default message', 'memberdash' ),
				'after'         => __( 'Define custom message', 'memberdash' ),
				'wrapper_class' => 'ms-block ms-spaced',
				'class'         => 'override-slider',
				'ajax_data'     => array( 1 ),
			),
			'editor'      => array(
				'id'            => 'content',
				'type'          => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
				'value'         => $message_content,
				'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
			),
			'save'        => array(
				'id'        => 'save_content',
				'type'      => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value'     => __( 'Save', 'memberdash' ),
				'class'     => 'button-primary ms-bg-black ms-border-black ms-text-white ms-shadow-none',
				'ajax_data' => array( 'type' => 'content' ),
			),
		);

		$message_shortcode = $settings->get_protection_message(
			MS_Model_Settings::PROTECTION_MSG_SHORTCODE,
			$membership,
			$override_shortcode
		);

		$groups['shortcode'] = array(
			'description' => array(
				'type'          => MS_Helper_Html::TYPE_HTML_TEXT,
				'value'         => __( 'Message displayed in place of a protected shortcode output.', 'memberdash' ),
				'wrapper_class' => 'ms-block ms-text-base ms-font-light',
			),
			'override'    => array(
				'id'            => 'override_shortcode',
				'type'          => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value'         => $override_shortcode,
				'before'        => __( 'Use default message', 'memberdash' ),
				'after'         => __( 'Define custom message', 'memberdash' ),
				'wrapper_class' => 'ms-block ms-spaced',
				'class'         => 'override-slider',
				'ajax_data'     => array( 1 ),
			),
			'editor'      => array(
				'id'            => 'shortcode',
				'type'          => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
				'value'         => $message_shortcode,
				'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
			),
			'save'        => array(
				'id'        => 'save_content',
				'type'      => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value'     => __( 'Save', 'memberdash' ),
				'class'     => 'button-primary ms-bg-black ms-border-black ms-text-white ms-shadow-none',
				'ajax_data' => array( 'type' => 'shortcode' ),
			),
		);

		$message_more_tag = $settings->get_protection_message(
			MS_Model_Settings::PROTECTION_MSG_MORE_TAG,
			$membership,
			$override_more_tag
		);

		$groups['more_tag'] = array(
			'description' => array(
				'type'          => MS_Helper_Html::TYPE_HTML_TEXT,
				'value'         => __( 'Message displayed in place of the read-more contents.', 'memberdash' ),
				'wrapper_class' => 'ms-block ms-text-base ms-font-light',
			),
			'override'    => array(
				'id'            => 'override_content',
				'type'          => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value'         => $override_more_tag,
				'before'        => __( 'Use default message', 'memberdash' ),
				'after'         => __( 'Define custom message', 'memberdash' ),
				'wrapper_class' => 'ms-block ms-spaced',
				'class'         => 'override-slider',
				'ajax_data'     => array( 1 ),
			),
			'editor'      => array(
				'id'            => 'more_tag',
				'type'          => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
				'value'         => $message_more_tag,
				'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
			),
			'save'        => array(
				'id'        => 'save_content',
				'type'      => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value'     => __( 'Save', 'memberdash' ),
				'class'     => 'button-primary ms-bg-black ms-border-black ms-text-white ms-shadow-none',
				'ajax_data' => array( 'type' => 'more_tag' ),
			),
		);

		foreach ( $groups as $key => $fields ) {
			if ( ! ( $membership instanceof MS_Model_Membership ) ) {
				unset( $fields['override'] );
			}

			foreach ( $fields as $id => $field ) {
				if ( empty( $field['ajax_data'] ) ) {
					continue;
				}
				if ( ! empty( $field['ajax_data']['action'] ) ) {
					continue;
				}

				if ( ! isset( $fields[ $id ]['ajax_data']['field'] ) ) {
					$fields[ $id ]['ajax_data']['field'] = $fields[ $id ]['id'];
				}
				$fields[ $id ]['ajax_data']['_wpnonce'] = $nonce;
				$fields[ $id ]['ajax_data']['action']   = $action;

				if ( $membership instanceof MS_Model_Membership ) {
					$fields[ $id ]['ajax_data']['membership_id'] = $membership->id;
				}
			}

			$groups[ $key ] = $fields;
		}

		return apply_filters(
			'ms_view_settings_prepare_pages_fields',
			$groups
		);
	}
}
