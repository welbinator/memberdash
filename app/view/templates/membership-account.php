<div class="ms-account-wrapper">
		<?php if ( ms_is_user_logged_in() ) : ?>
				<?php if ( ms_show_users_membership() ) : ?>
				<div id="account-membership">
				<h2>
						<?php
						echo get_ms_ac_title(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						if ( show_membership_change_link() ) {
								echo get_ms_ac_signup_modified_url(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
				</h2>
					<?php
					/**
					 * Add custom content right before the memberships list.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ms_view_account_memberships_top', get_ms_ac_member_obj(), get_ms_ac_account_obj() );

					if ( is_ms_admin_user() ) {
						esc_html_e( 'You are an admin user and have access to all memberships', 'memberdash' );
					} else {
						if ( has_ms_ac_subscriptions() ) {
							?>
								<table>
										<tr>
												<th class="ms-col-membership">
												<?php
														esc_html_e( 'Membership name', 'memberdash' );
												?>
												</th>
												<th class="ms-col-status">
												<?php
														esc_html_e( 'Status', 'memberdash' );
												?>
												</th>
												<th class="ms-col-expire-date">
												<?php
														esc_html_e( 'Expire date', 'memberdash' );
												?>
												</th>
										</tr>
										<?php
										$empty              = true;
										$mwps_subscriptions = get_ms_ac_subscriptions();
										foreach ( $mwps_subscriptions as $subscription ) :
												$empty = false;
												ms_account_the_membership( $subscription );
											?>
												<tr class="<?php echo get_ms_account_classes(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
														<td class="ms-col-membership"><?php echo get_ms_account_membership_name(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
														<td class="ms-col-status"><?php echo get_ms_account_membership_status(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
														<td class="ms-col-expire-date"><?php echo get_ms_account_expire_date(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
												</tr>
											<?php
										endforeach;

										if ( $empty ) {
												echo get_ms_no_account_membership_status(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}
										?>
								</table>
							<?php
						} else {
							esc_html_e( 'No memberships', 'memberdash' );
						}
					}
					/**
					 * Add custom content right after the memberships list.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ms_view_account_memberships_bottom', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
					?>
				</div>
				<?php endif; ?>
				<?php
				// ===================================================== PROFILE
				if ( is_ms_ac_show_profile() ) :
					?>
				<div id="account-profile">
				<h2>
						<?php
						echo get_ms_ac_profile_title(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						if ( is_ms_ac_show_profile_change() ) {
								echo get_ms_ac_profile_change_link(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
				</h2>
					<?php
					/**
					 * Add custom content right before the profile overview.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ms_view_account_profile_top', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
					?>
				<table>
						<?php $profile_fields = get_ms_ac_profile_fields(); ?>
						<?php foreach ( $profile_fields as $field => $title ) : ?>
								<tr>
										<th class="ms-label-title"><?php echo esc_html( $title ); ?>: </th>
										<td class="ms-label-field"><?php echo esc_html( get_ms_ac_profile_info( $field ) ); ?></td>
								</tr>
						<?php endforeach; ?>
				</table>
					<?php
					do_action( 'ms_view_account_profile_before_card', get_ms_ac_member_obj(), get_ms_ac_account_obj() );


					do_action( 'ms_view_shortcode_account_card_info', get_ms_ac_data() );

					/**
					 * Add custom content right after the profile overview.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ms_view_account_profile_bottom', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
					?>
				</div>
					<?php
				endif;
				// END: if ( $show_profile )
				// =============================================================
				?>

				<?php
				// ==================================================== INVOICES
				if ( is_ms_ac_show_invoices() ) :
					?>
				<div id="account-invoices">
				<h2>
						<?php
						echo get_ms_ac_invoices_title(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						if ( is_ms_ac_show_all_invoices() ) {
								echo get_ms_ac_invoices_detail_label(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
				</h2>
					<?php
					/**
					 * Add custom content right before the invoice overview list.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ms_view_account_invoices_top', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
					?>
				<table>
						<thead>
								<tr>
										<th class="ms-col-invoice-no">
										<?php
												esc_html_e( 'Invoice #', 'memberdash' );
										?>
										</th>
										<th class="ms-col-invoice-status">
										<?php
												esc_html_e( 'Status', 'memberdash' );
										?>
										</th>
										<th class="ms-col-invoice-total">
										<?php
										printf(
											'%s (%s)',
											esc_html__( 'Total', 'memberdash' ),
											esc_attr( MS_Plugin::instance()->settings->currency )
										);
										?>
										</th>
										<th class="ms-col-invoice-title">
										<?php
												esc_html_e( 'Membership', 'memberdash' );
										?>
										</th>
										<th class="ms-col-invoice-due">
										<?php
												esc_html_e( 'Due date', 'memberdash' );
										?>
										</th>
								</tr>
						</thead>
						<tbody>
						<?php $mwps_invoices = get_ms_ac_invoices(); ?>
						<?php
						foreach ( $mwps_invoices as $invoice ) :
								ms_account_the_invoice( $invoice );
							?>
								<tr class="<?php echo get_ms_invoice_classes(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
										<td class="ms-col-invoice-no"><?php echo get_ms_invoice_number(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
										<td class="ms-col-invoice-status"><?php echo get_ms_invoice_next_status(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
										<td class="ms-col-invoice-total"><?php echo get_ms_invoice_total(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
										<td class="ms-col-invoice-title"><?php echo get_ms_invoice_name(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
										<td class="ms-col-invoice-due"><?php echo get_ms_invoice_due_date(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
								</tr>
						<?php endforeach; ?>
						</tbody>
				</table>
					<?php
					/**
					 * Add custom content right after the invoices overview list.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ms_view_account_invoices_bottom', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
					?>
				</div>
					<?php
				endif;
				?>

				<?php
				// ==================================================== ACTIVITY
				if ( is_ms_ac_show_activity() ) :
					?>
				<div id="account-activity">
				<h2>
						<?php
						echo get_ms_ac_activity_title(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						if ( is_ms_ac_show_all_activities() ) {
								echo get_ms_ac_activity_details_label(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
				</h2>
					<?php
					/**
					 * Add custom content right before the activities overview list.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ms_view_account_activity_top', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
					?>
				<table>
						<thead>
								<tr>
										<th class="ms-col-activity-date">
										<?php
												esc_html_e( 'Date', 'memberdash' );
										?>
										</th>
										<th class="ms-col-activity-title">
										<?php
												esc_html_e( 'Activity', 'memberdash' );
										?>
										</th>
								</tr>
						</thead>
						<tbody>
						<?php $mwps_events = get_ms_ac_events(); ?>
						<?php
						foreach ( $mwps_events as $event ) :
								ms_account_the_event( $event );
							?>
								<tr class="<?php echo get_ms_event_classes(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
										<td class="ms-col-activity-date"><?php echo get_ms_event_date(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
										<td class="ms-col-activity-title"><?php echo get_ms_event_description(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
								</tr>
						<?php endforeach; ?>
						</tbody>
				</table>
					<?php
					/**
					 * Add custom content right after the activities overview list.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ms_view_account_activity_bottom', get_ms_ac_member_obj(), get_ms_ac_account_obj() );
					?>
				</div>
					<?php
				endif;
				?>

			<?php
		else :

			if ( ! has_ms_ac_login_form() ) {
					echo get_ms_ac_login_form(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		endif;
		?>
</div>
