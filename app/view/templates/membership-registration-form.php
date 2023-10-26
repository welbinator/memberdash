<?php if ( is_ms_registration_form_title_exists() ) : ?>
		<legend><?php echo get_ms_registration_form_title(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></legend>
<?php endif; ?>

<?php

echo get_ms_registration_form_fields(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

echo '<div class="ms-extra-fields">';

/**
 * Trigger default WordPress action to allow other plugins
 * to add custom fields to the registration form.
 *
 * The signup_extra_fields defined in wp-signup.php which is used
 *              for Multisite signup process.
 *
 * register_form Defined in wp-login.php which is only used for
 *              Single site registration process.
 *
 * @since 1.0.0
 */
ms_registration_form_extra_fields();

echo '</div>';

echo get_ms_registration_form_register_button(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

ms_registration_form_error();

/**
 * This hook is intended to output hidden fields or JS code
 * at the end of the form tag.
 *
 * @since 1.0.0
 */
do_action( 'ms_shortcode_register_form_end', ms_registration_form_obj() );
?>
<br><br>
<?php
if ( is_ms_registration_form_login_link_exists() ) {
		echo get_ms_registration_form_login_link(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
