<div id="ms-membership-wrapper-<?php echo get_ms_single_box_membership_id(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" class="<?php echo get_ms_single_box_wrapper_classes(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<div class="ms-top-bar">
		<span class="ms-title"><?php echo get_ms_single_box_membership_name(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	</div>

	<div class="ms-price-details">
		<div class="ms-description"><?php echo get_ms_single_box_membership_description(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<div class="ms-price price"><?php echo get_ms_single_box_membership_price(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

		<?php if ( is_ms_single_box_msg() ) : ?>
			<div class="ms-bottom-msg"><?php echo get_ms_single_box_msg(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
	</div>

	<div class="ms-bottom-bar">
		<?php
		if ( is_ms_single_box_action_pay() ) {
			echo get_ms_single_box_payment_btn(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo get_ms_single_box_hidden_fields(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		/**
		 * It's possible to add custom fields to the signup box.
		 *
		 * @since 1.0.0
		 */
		do_action( 'ms_shortcode_signup_form_end', get_ms_single_box_membership_obj() );

		echo get_ms_single_box_btn(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</div>
