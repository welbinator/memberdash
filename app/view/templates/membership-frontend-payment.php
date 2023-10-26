<?php
/**
 * Membership frontend payment template.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

?>
<div class="<?php echo get_ms_pm_membership_wrapper_class(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
		<legend><?php esc_html_e( 'Join Membership', 'memberdash' ); ?></legend>
		<p class="ms-alert-box <?php echo get_ms_pm_alert_box_class(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
				<?php echo get_ms_pm_message(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</p>
		<table class="ms-purchase-table">
				<tr>
						<td class="ms-title-column">
								<?php esc_html_e( 'Name', 'memberdash' ); ?>
						</td>
						<td class="ms-details-column">
								<?php echo get_ms_pm_membership_name(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
				</tr>

				<?php if ( is_ms_pm_membership_description() ) : ?>
						<tr>
								<td class="ms-title-column">
										<?php esc_html_e( 'Description', 'memberdash' ); ?>
								</td>
								<td class="ms-desc-column">
										<span class="ms-membership-description">
										<?php
												echo get_ms_pm_membership_description(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
										</span>
								</td>
						</tr>
				<?php endif; ?>

				<?php if ( ! is_ms_pm_membership_free() && ! is_ms_admin_user() ) : ?>
						<?php if ( is_ms_pm_invoice_discount() || is_ms_pm_invoice_pro_rate() || is_ms_pm_invoice_tax_rate() ) : ?>
						<tr>
								<td class="ms-title-column">
										<?php esc_html_e( 'Price', 'memberdash' ); ?>
								</td>
								<td class="ms-details-column">
										<?php
										if ( get_ms_pm_membership_price() > 0 ) {
												echo get_ms_pm_membership_formatted_price(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										} else {
												esc_html_e( 'Free', 'memberdash' );
										}
										?>
								</td>
						</tr>
						<?php endif; ?>

						<?php if ( is_ms_pm_invoice_discount() ) : ?>
								<tr>
										<td class="ms-title-column">
												<?php esc_html_e( 'Coupon Discount', 'memberdash' ); ?>
										</td>
										<td class="ms-price-column">
												<?php echo get_ms_pm_invoice_formatted_discount(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</td>
								</tr>
						<?php endif; ?>

						<?php if ( is_ms_pm_invoice_pro_rate() ) : ?>
								<tr>
										<td class="ms-title-column">
												<?php esc_html_e( 'Pro-Rate Discount', 'memberdash' ); ?>
										</td>
										<td class="ms-price-column">
												<?php echo get_ms_pm_invoice_formatted_pro_rate(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</td>
								</tr>
						<?php endif; ?>

						<?php if ( is_ms_pm_show_tax() ) : ?>
								<tr>
										<td class="ms-title-column">
												<?php echo get_ms_pm_invoice_tax_name(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</td>
										<td class="ms-price-column">
												<?php echo get_ms_pm_invoice_formatted_tax(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</td>
								</tr>
						<?php endif; ?>

						<tr>
								<td class="ms-title-column">
										<?php esc_html_e( 'Total', 'memberdash' ); ?>
								</td>
								<td class="ms-price-column ms-total">
										<?php
										if ( get_ms_pm_invoice_total() > 0 ) {
											if ( is_ms_admin_user() ) {
												echo get_ms_pm_invoice_formatted_total_for_admin(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											} else {
												echo get_ms_pm_invoice_formatted_total(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											}
										} else {
												esc_html_e( 'Free', 'memberdash' );
										}
										?>
								</td>
						</tr>

						<?php if ( is_ms_pm_trial() ) : ?>
								<tr>
										<td class="ms-title-column">
												<?php esc_html_e( 'Payment due', 'memberdash' ); ?>
										</td>
										<td class="ms-desc-column">
										<?php
												echo get_ms_pm_invoice_formatted_due_date(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
										</td>
								</tr>
								<tr>
										<td class="ms-title-column">
												<?php esc_html_e( 'Trial price', 'memberdash' ); ?>
										</td>
										<td class="ms-desc-column">
										<?php
										if ( get_ms_pm_invoice_trial_price() > 0 ) {
												echo get_ms_pm_invoice_formatted_trial_price(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										} else {
												esc_html_e( 'Free', 'memberdash' );
										}
										?>
										</td>
								</tr>
						<?php endif; ?>

						<?php
						do_action(
							'ms_view_frontend_payment_after_total_row',
							get_ms_payment_subscription(),
							get_ms_payment_invoice(),
							get_ms_payment_obj()
						);
						?>

						<tr>
								<td class="ms-desc-column" colspan="2">
										<span class="ms-membership-description">
										<?php
												echo get_ms_pm_invoice_payment_description(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
										</span>
								</td>
						</tr>
				<?php endif; ?>

				<?php if ( is_ms_pm_cancel_warning() ) : ?>
						<tr>
								<td class="ms-desc-warning" colspan="2">
										<span class="ms-cancel-other-memberships">
										<?php
												echo get_ms_pm_cancel_warning(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
										</span>
								</td>
						</tr>
				<?php endif; ?>

				<?php if ( is_ms_admin_user() ) : ?>
						<tr>
								<td class="ms-desc-adminnote" colspan="2">
										<em>
										<?php
										esc_html_e( 'As admin user you already have access to this membership', 'memberdash' );
										?>
										</em>
								</td>
						</tr>
					<?php
				else :
						do_action(
							'ms_view_frontend_payment_purchase_button',
							get_ms_payment_subscription(),
							get_ms_payment_invoice(),
							get_ms_payment_obj()
						);
				endif;
				?>
				</table>
</div>
<?php
do_action( 'ms_view_frontend_payment_after', get_ms_payment_obj_data(), get_ms_payment_obj() );
do_action( 'ms_show_prices' );

$ms_active_gateways = MS_Model_Gateway::get_gateways( true, true );

// Check if active payment gateways exists.
if ( count( $ms_active_gateways ) === 0 ) :
	?>
	<p class="ms-alert-box ms-alert-warning">
		<?php esc_html_e( 'Please contact the site administrator to complete your payment.', 'memberdash' ); ?>
	</p>
	<?php
endif;

if ( is_ms_pm_show_tax() ) {
		do_action( 'ms_tax_editor', get_ms_payment_invoice() );
}
?>
<div style="clear:both;"></div>
