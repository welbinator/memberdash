<?php
/**
 * Membership Login Widget.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

/**
 * Membership Login Widget class.
 *
 * @since 1.0.0
 */
class MS_Widget_Login extends WP_Widget {

	/**
	 * Constructor.
	 * Sets up the widgets name etc.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			'ms_widget_login',
			__( '[Membership] Login', 'memberdash' ),
			array(
				'description' => __( 'Display a Login Form to all guests. Logged-in users will see a Logout link.', 'memberdash' ),
			)
		);
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		global $post;
		$redirect_login  = false;
		$redirect_logout = false;
		$shortcode_args  = '';

		// Do not show login widget in actual login page.
		if ( isset( $post->post_content ) &&
		// Make sure we already have login shortcode in this page.
		MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_LOGIN, $post->post_content ) &&
		/**
		* Filter hook to disable hiding widget.
		*
		* @since 1.0.0
		*/
		apply_filters( 'ms_widget_login_hide_on_login_page', true )
		) {
			return;
		}

		if ( ! empty( $instance['redirect_login'] ) ) {
			$redirect_login = mslib3()->net->expand_url( $instance['redirect_login'] );
		}

		if ( ! empty( $instance['redirect_logout'] ) ) {
			$redirect_logout = mslib3()->net->expand_url( $instance['redirect_logout'] );
		}

		if ( ! empty( $instance['shortcode_args'] ) ) {
			$shortcode_args = $instance['shortcode_args'];
		}

		echo $args['before_widget']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo apply_filters( 'widget_title', $instance['title'] ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $args['after_title']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$scode = sprintf(
			'[%1$s header="no" %2$s %3$s %4$s]',
			MS_Helper_Shortcode::SCODE_LOGIN,
			$redirect_login ? 'redirect_login="' . $redirect_login . '"' : '',
			$redirect_logout ? 'redirect_logout="' . $redirect_logout . '"' : '',
			$shortcode_args
		);
		echo do_shortcode( $scode );

		echo $args['after_widget']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$title           = __( 'Login', 'memberdash' );
		$redirect_login  = '';
		$redirect_logout = '';
		$shortcode_args  = '';

		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		}

		if ( isset( $instance['redirect_login'] ) ) {
			$redirect_login = $instance['redirect_login'];
		}

		if ( isset( $instance['redirect_logout'] ) ) {
			$redirect_logout = $instance['redirect_logout'];
		}

		if ( isset( $instance['shortcode_args'] ) ) {
			$shortcode_args = $instance['shortcode_args'];
		}

		$placeholder_login = MS_Model_Pages::get_url_after_login();
		if ( strlen( $placeholder_login ) > 55 ) {
			$parts             = explode( '://', $placeholder_login );
			$placeholder_login = $parts[0] . '://' . substr( $parts[1], 0, 5 ) . '&hellip;' . substr( $parts[1], -38 );
		}
		$placeholder_logout = MS_Model_Pages::get_url_after_logout();
		if ( strlen( $placeholder_logout ) > 55 ) {
			$parts              = explode( '://', $placeholder_logout );
			$placeholder_logout = $parts[0] . '://' . substr( $parts[1], 0, 5 ) . '&hellip;' . substr( $parts[1], -38 );
		}

		$field_title = array(
			'id'    => $this->get_field_id( 'title' ),
			'name'  => $this->get_field_name( 'title' ),
			'type'  => MS_Helper_Html::INPUT_TYPE_TEXT,
			'title' => __( 'Title:', 'memberdash' ),
			'value' => $title,
			'class' => 'widefat',
		);

		$field_redirect_login = array(
			'id'          => $this->get_field_id( 'redirect_login' ),
			'name'        => $this->get_field_name( 'redirect_login' ),
			'type'        => MS_Helper_Html::INPUT_TYPE_TEXT,
			'title'       => __( 'Show this page after login:', 'memberdash' ),
			'value'       => $redirect_login,
			'placeholder' => $placeholder_login,
			'class'       => 'widefat',
		);

		$field_redirect_logout = array(
			'id'          => $this->get_field_id( 'redirect_logout' ),
			'name'        => $this->get_field_name( 'redirect_logout' ),
			'type'        => MS_Helper_Html::INPUT_TYPE_TEXT,
			'title'       => __( 'Show this page after logout:', 'memberdash' ),
			'value'       => $redirect_logout,
			'placeholder' => $placeholder_logout,
			'class'       => 'widefat',
		);

		$field_shortcode_args = array(
			'id'          => $this->get_field_id( 'shortcode_args' ),
			'name'        => $this->get_field_name( 'shortcode_args' ),
			'type'        => MS_Helper_Html::INPUT_TYPE_TEXT,
			'title'       => __( 'Shortcode Options:', 'memberdash' ),
			'desc'        => sprintf(
			// translators: 1. Opening anchor tag, 2. Closing anchor tag.
				__( 'Arguments to pass to the %1$slogin shortcode%2$s', 'memberdash' ), // cspell:disable-line.
				sprintf(
					'<a href="%s#ms-membership-login" target="_blank">',
					MS_Controller_Plugin::get_admin_url(
						'help',
						array( 'tab' => 'shortcodes' )
					)
				),
				'</a>'
			),
			'value'       => $shortcode_args,
			'placeholder' => 'header="no"',
			'class'       => 'widefat',
		);

		MS_Helper_Html::html_element( $field_title );
		MS_Helper_Html::html_element( $field_redirect_login );
		MS_Helper_Html::html_element( $field_redirect_logout );
		MS_Helper_Html::html_element( $field_shortcode_args );
	}

	/**
	 * Processing widget options on save
	 *
	 * @since 1.0.0
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                    = array();
		$instance['title']           = '';
		$instance['redirect_login']  = '';
		$instance['redirect_logout'] = '';
		$instance['shortcode_args']  = '';

		if ( isset( $new_instance['title'] ) ) {
			$instance['title'] = wp_strip_all_tags( $new_instance['title'] );
		}

		if ( isset( $new_instance['redirect_login'] ) ) {
			$instance['redirect_login'] = wp_strip_all_tags( $new_instance['redirect_login'] );
		}

		if ( isset( $new_instance['redirect_logout'] ) ) {
			$instance['redirect_logout'] = wp_strip_all_tags( $new_instance['redirect_logout'] );
		}

		if ( isset( $new_instance['shortcode_args'] ) ) {
			$instance['shortcode_args'] = wp_strip_all_tags( $new_instance['shortcode_args'] );
		}

		return $instance;
	}
}
