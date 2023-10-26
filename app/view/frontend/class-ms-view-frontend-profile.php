<?php
/**
 * Frontend profile view.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

/**
 * Frontend profile view.
 *
 * @since 1.0.0
 */
class MS_View_Frontend_Profile extends MS_View {

	/**
	 * Return the HTML code.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		$fields = $this->prepare_fields();

		$cancel = array(
			'id'    => 'cancel',
			'type'  => MS_Helper_Html::TYPE_HTML_LINK,
			'title' => __( 'Cancel', 'memberdash' ),
			'value' => __( 'Cancel', 'memberdash' ),
			'url'   => esc_url_raw( remove_query_arg( array( 'action' ) ) ),
			'class' => 'memberdash-field-button button',
		);

		$this->add_action(
			'ms_view_frontend_profile_after_fields',
			'add_scripts'
		);

		ob_start();
		?>
		<div class="ms-membership-form-wrapper">
			<?php $this->render_errors(); ?>
			<form id="ms-view-frontend-profile-form" class="form-membership" action="" method="post">
				<legend><?php esc_html_e( 'Edit profile', 'memberdash' ); ?></legend>
				<?php
				foreach ( $fields as $field ) {
					if ( is_string( $field ) ) {
						MS_Helper_Html::html_element( $field );
					} elseif ( MS_Helper_Html::INPUT_TYPE_HIDDEN == $field['type'] ) {
						MS_Helper_Html::html_element( $field );
					} else {
						?>
						<div class="ms-form-element ms-form-element-<?php echo esc_attr( $field['id'] ); ?>">
							<?php MS_Helper_Html::html_element( $field ); ?>
						</div>
						<?php
					}
				}
				do_action( 'ms_view_frontend_profile_after_fields' );
				do_action( 'ms_view_frontend_profile_extra_fields', $this->error );
				?>
			</form>
			<div class="ms-form-element">
			<?php MS_Helper_Html::html_link( $cancel ); ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		return $html;
	}

	/**
	 * Prepare the fields that are displayed in the form.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function prepare_fields() {
		$member = $this->data['member'];

		$fields = array(
			'first_name' => array(
				'id'    => 'first_name',
				'title' => __( 'First Name', 'memberdash' ),
				'type'  => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $member->first_name,
			),
			'last_name'  => array(
				'id'    => 'last_name',
				'title' => __( 'Last Name', 'memberdash' ),
				'type'  => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $member->last_name,
			),
			'email'      => array(
				'id'    => 'email',
				'title' => __( 'Email Address', 'memberdash' ),
				'type'  => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $member->email,
			),
			'password'   => array(
				'id'    => 'password',
				'title' => __( 'Password', 'memberdash' ),
				'type'  => MS_Helper_Html::INPUT_TYPE_PASSWORD,
				'value' => '',
			),
			'password2'  => array(
				'id'    => 'password2',
				'title' => __( 'Confirm Password', 'memberdash' ),
				'type'  => MS_Helper_Html::INPUT_TYPE_PASSWORD,
				'value' => '',
			),
			'submit'     => array(
				'id'    => 'submit',
				'type'  => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Save Changes', 'memberdash' ),
			),
			'_wpnonce'   => array(
				'id'    => '_wpnonce',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $this->data['action'] ),
			),
			'action'     => array(
				'id'    => 'action',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['action'],
			),
		);

		$fields = apply_filters(
			'ms_view_profile_fields',
			$fields,
			$this
		);

		return $fields;
	}

	/**
	 * Outputs the javascript used by the registration form.
	 *
	 * @since 1.0.0
	 */
	public static function add_scripts() {
		static $Scripts_Done = false;

		// Make sure to only execute that function once.
		if ( $Scripts_Done ) {
			return; }
		$Scripts_Done = true;

		$rule_data = array(
			'email'     => array(
				'required' => true,
			),
			'password'  => array(
				'minlength' => 5,
			),
			'password2' => array(
				'equalTo' => '.ms-form-element #password',
			),
		);

		/**
		 * Allow other plugins or Add-ons to modify the validation rules on the
		 * registration page.
		 *
		 * @since 1.0.0
		 * @var  array
		 */
		$rule_data = apply_filters(
			'ms_view_profile_form_rules',
			$rule_data
		);

		ob_start();
		?>
		jQuery(function() {
		var args = {
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: <?php echo wp_json_encode( $rule_data ); ?>
		};

		jQuery( '#ms-view-frontend-profile-form' ).validate( args );
		});
		<?php
		$script = ob_get_clean();
		mslib3()->ui->script( $script );
	}

	/**
	 * Renders error messages.
	 *
	 * @since 1.0.0
	 * @internal
	 */
	protected function render_errors() {
		if ( ! empty( $this->data['errors'] ) ) {
			?>
			<div class="ms-alert-box ms-alert-error">
				<?php echo $this->data['errors']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php
		}
	}

}
