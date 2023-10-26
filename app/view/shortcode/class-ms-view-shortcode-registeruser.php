<?php
/**
 * Register User Shortcode View
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

/**
 * Displays the register user shortcode.
 *
 * @since 1.0.0
 */
class MS_View_Shortcode_RegisterUser extends MS_View {

	/**
	 * Returns the HTML code.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		// If user is logged in, they obviously cannot register again ;)
		if ( is_user_logged_in() ) {
			return ''; }

		$fields = $this->prepare_fields();

		$this->add_action(
			'ms_shortcode_register_form_end',
			'add_scripts'
		);

		// When redirecting to login form we want to keep the previously submitted form data.
		$url_data = $_POST;
				// Removing unnecessary data
				unset( $url_data['action'] );
				unset( $url_data['step'] );
				unset( $url_data['password'] );
				unset( $url_data['password2'] );

		$url_data['do-login'] = '1';
		$login_url            = esc_url_raw( add_query_arg( $url_data ) );

		if ( ! empty( $_REQUEST['do-login'] ) ) {
			$register_url = esc_url_raw( remove_query_arg( 'do-login' ) );

			$back_link = array(
				'url'   => $register_url,
				'class' => 'alignleft',
				'value' => __( 'Cancel', 'memberdash' ),
			);

			$html  = do_shortcode(
				sprintf(
					'[%s show_note=false title="%s"]',
					MS_Helper_Shortcode::SCODE_LOGIN,
					__( 'Login', 'memberdash' )
				)
			);
			$html .= MS_Helper_Html::html_link( $back_link, true );
			return $html;
		}

		$login_link = array(
			'title' => __( 'Login', 'memberdash' ),
			'url'   => $login_url,
			'class' => 'alignleft',
			'value' => __( 'Already have a user account?', 'memberdash' ),
		);

		$register_button = array(
			'id'    => 'register',
			'type'  => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => $this->data['label_register'],
		);

		/**
		 * The register button can be modified via a custom filter.
		 * Either update the array properties or replace the array with some
		 * HTML string that will be output.
		 *
		 * @since 1.0.0
		 * @param  array|string $register_button
		 * @param  array $data
		 */
		$register_button = apply_filters(
			'ms_shortcode_register_button',
			$register_button,
			$this->data
		);

		$title = $this->data['title'];

		wp_enqueue_style( 'ms-styles' );

		ob_start();

		$reg_url = apply_filters(
			'ms_shortcode_register_form_url',
			// MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER ),
						// MS_Model_Pages::current_page(),
			get_permalink(),
			$this->data
		);
		$reg_url = esc_url_raw(
			add_query_arg( 'action', 'register_user', $reg_url )
		);

		// Default WP action hook
		do_action( 'before_signup_form' );
		?>
		<div class="ms-membership-form-wrapper">
			<?php $this->render_errors(); ?>
		<?php

		$login_link_exists = $this->data['loginlink'];
		$reg_action_url    = esc_url( $reg_url );

		if ( is_multisite() ) {
			$empty_error = new WP_Error();
		}

		$mwps_reg_error = $this->error;

		$template_data = array(
			'title'             => $title,
			'fields'            => $fields,
			'empty_error'       => isset( $empty_error ) ? $empty_error : '',
			'register_button'   => $register_button,
			'mwps_reg_error'    => $mwps_reg_error,
			'login_link_exists' => $login_link_exists,
			'login_link'        => $login_link,
			'mwps_obj'          => $this,
		);

		MS_Helper_Template::$ms_registration_form = $template_data;
		?>
		<form id="ms-shortcode-register-user-form" class="form-membership" action="<?php echo esc_url( $reg_action_url ); ?>" method="post">
		<?php
			wp_nonce_field( $this->data['action'] );

		$path = MS_Helper_Template::template_exists( 'membership-registration-form.php' );
		if ( $path ) {
			require $path;
		}
		?>
		</form>
		</div>
		<?php
		// Default WP action hook.

		// Intentionally removed, because this hook should be only used in the
		// blog-signup form. Not during user registration.
		// do_action( 'signup_blogform', array() );

		do_action( 'after_signup_form' );

		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		return apply_filters(
			'ms_shortcode_register',
			$html,
			$this->data
		);
	}

	/**
	 * Prepares the fields that are displayed in the form.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function prepare_fields() {
		$data = $this->data;

		$fields = array(
			'membership_id' => array(
				'id'    => 'membership_id',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $data['membership_id'],
			),

			'first_name'    => array(
				'id'          => 'first_name',
				'title'       => $data['label_first_name'],
				'placeholder' => $data['hint_first_name'],
				'type'        => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'       => $data['first_name'],
			),

			'last_name'     => array(
				'id'          => 'last_name',
				'title'       => $data['label_last_name'],
				'placeholder' => $data['hint_last_name'],
				'type'        => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'       => $data['last_name'],
			),

			'username'      => array(
				'id'          => 'username',
				'title'       => $data['label_username'],
				'placeholder' => $data['hint_username'],
				'type'        => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'       => $data['username'],
			),

			'email'         => array(
				'id'          => 'email',
				'title'       => $data['label_email'],
				'placeholder' => $data['hint_email'],
				'type'        => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value'       => $data['email'],
			),

			'password'      => array(
				'id'          => 'password',
				'title'       => $data['label_password'],
				'placeholder' => $data['hint_password'],
				'type'        => MS_Helper_Html::INPUT_TYPE_PASSWORD,
				'value'       => '',
			),

			'password2'     => array(
				'id'          => 'password2',
				'title'       => $data['label_password2'],
				'placeholder' => $data['hint_password2'],
				'type'        => MS_Helper_Html::INPUT_TYPE_PASSWORD,
				'value'       => '',
			),

			'action'        => array(
				'id'    => 'action',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $data['action'],
			),

			'step'          => array(
				'id'    => 'step',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $data['step'],
			),
		);

		// Show the privacy checkbox only if the page is public.
		$privacy_link = MS_Helper_Utility::get_privacy_page();
		if ( $privacy_link ) {
			$fields['privacy_check'] = array(
				'id'    => 'privacy_check',
				'title' => sprintf( __( 'By creating an account you agree to the sites %1$sPrivacy Policy%2$s', 'memberdash' ), '<a href="' . $privacy_link . '" target="_blank">', '</a>' ),
				'type'  => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'value' => 'privacy',
			);
		}

		return apply_filters(
			'ms_shortcode_register_form_fields',
			$fields,
			$this
		);
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
			'username'      => array(
				'required' => true,
			),
			'password'      => array(
				'required'  => true,
				'minlength' => 5,
			),
			'password2'     => array(
				'required' => true,
				'equalTo'  => '.ms-form-element #password',
			),
			'privacy_check' => array(
				'required' => true,
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
			'ms_shortcode_register_form_rules',
			$rule_data
		);

		ob_start();
		?>
		jQuery(function() {
			var args = {
				errorClass: 'ms-validation-error',
				rules: <?php echo wp_json_encode( $rule_data ); ?>,
				<?php if ( apply_filters( 'ms_shortcode_register_form_validate_blur', true ) ) : ?>
					onfocusout: function(element) {
						this.element(element);
					}
				<?php endif; ?>
			};

			jQuery( '#ms-shortcode-register-user-form' ).validate( args );
			jQuery( document ).on( 'submit', '#ms-shortcode-register-user-form', function(){
				jQuery( this ).find( 'button[type="submit"]' ).prop("disabled",true);
			});
		});
		<?php
		$script = ob_get_clean();
		mslib3()->ui->js( 'jquery-validate' );
		mslib3()->ui->script( $script );
	}

	/**
	 * Renders error messages.
	 *
	 * @since 1.0.0
	 * @internal
	 */
	protected function render_errors() {
		$errors = MS_Controller_Frontend::$register_errors;

		if ( ! empty( $errors ) ) {
			?>
			<div class="ms-alert-box ms-alert-error">
				<?php echo $errors; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php
		}
	}

}
