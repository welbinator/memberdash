<?php

class MS_View_Frontend_Invoices extends MS_View {

	public function to_html() {
		ob_start();
		?>
		<div class="ms-account-wrapper">
			<?php if ( MS_Model_Member::is_logged_in() ) : ?>
				<h2>
					<?php esc_html_e( 'Invoice', 'memberdash' ); ?>
				</h2>
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
					<?php
					foreach ( $this->data['invoices'] as $invoice ) :
						$inv_membership = MS_Factory::load( 'MS_Model_Membership', $invoice->membership_id );
						$inv_classes    = array(
							'ms-invoice-' . $invoice->id,
							'ms-subscription-' . $invoice->ms_relationship_id,
							'ms-invoice-' . $invoice->status,
							'ms-gateway-' . $invoice->gateway_id,
							'ms-membership-' . $invoice->membership_id,
							'ms-type-' . $inv_membership->type,
							'ms-payment-' . $inv_membership->payment_type,
						);
						?>
						<tr class="<?php echo esc_attr( implode( ' ', $inv_classes ) ); ?>">
							<td class="ms-col-invoice-no">
							<?php
							printf(
								'<a href="%s">%s</a>',
								esc_url( get_permalink( $invoice->id ) ),
								esc_attr( $invoice->get_invoice_number() )
							);
							?>
							</td>
							<td class="ms-col-invoice-status">
							<?php
								echo esc_html( $invoice->status_text() );
							?>
							</td>
							<td class="ms-col-invoice-total">
							<?php
								echo esc_html( MS_Helper_Billing::format_price( $invoice->total ) );
							?>
							</td>
							<td class="ms-col-invoice-title">
							<?php
								echo esc_html( $inv_membership->name );
							?>
							</td>
							<td class="ms-col-invoice-due">
							<?php
								echo esc_html(
									MS_Helper_Period::format_date(
										$invoice->due_date,
										__( 'F j', 'memberdash' )
									)
								);
							?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<?php
				$redirect = esc_url_raw( add_query_arg( array() ) );
				$title    = __( 'Your account', 'memberdash' );
				echo do_shortcode( "[ms-membership-login redirect='$redirect' title='$title']" );
				?>
			<?php endif; ?>
		</div>
		<?php
		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		return $html;
	}

}
