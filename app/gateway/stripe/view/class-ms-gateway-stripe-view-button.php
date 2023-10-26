<?php
/**
 * Stripe Button View Class
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

/**
 * Stripe Button View Class
 *
 * @since 1.0.0
 */
class MS_Gateway_Stripe_View_Button extends MS_View {

	/**
	 * Returns the HTML for the button.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$fields       = $this->prepare_fields();
		$subscription = $this->data['ms_relationship'];
		$invoice      = $subscription->get_next_billable_invoice();
		$member       = MS_Model_Member::get_current_member();
		$gateway      = $this->data['gateway'];
		$membership   = $subscription->get_membership();

		if ( $membership->payment_type === MS_Model_Membership::PAYMENT_TYPE_RECURRING ) {
			$action = 'stripeSubSession';
		} else {
			$action = 'stripeSession';
		}

		// Stripe is using Ajax, so the URL is empty.
		$action_url = apply_filters(
			'ms_gateway_stripe_view_button_form_action_url',
			''
		);

		$row_class = 'gateway_' . $gateway->id;
		if ( ! $gateway->is_live_mode() ) {
			$row_class .= ' sandbox-mode';
		}

		$label = ( $gateway->pay_button_url === '' ) ? __( 'Pay with Stripe', 'memberdash' ) : $gateway->pay_button_url;

		$stripe_data = array(
			'name'        => get_bloginfo( 'name' ),
			'description' => wp_strip_all_tags( $invoice->short_description ),
			'label'       => $label,
		);

		/**
		 * Users can change details (like the title or description) of the
		 * stripe checkout popup.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		$stripe_data = apply_filters(
			'ms_gateway_stripe_form_details',
			$stripe_data,
			$invoice
		);

		$stripe_data['email']    = $member->email;
		$stripe_data['key']      = $gateway->get_publishable_key();
		$stripe_data['currency'] = $invoice->currency;
		$stripe_data['amount']   = ceil( abs( $invoice->total * 100 ) ); // Amount in cents.
		$stripe_data['image']    = $gateway->get_vendor_logo();
		$stripe_data['locale']   = 'auto';
		$stripe_data['zip-code'] = 'true';

		$stripe_data = apply_filters(
			'ms_gateway_stripe_form_details_after',
			$stripe_data,
			$invoice
		);

		ob_start();
		?>
		<form action="<?php echo esc_url( $action_url ); ?>" method="post">
			<?php
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
			<script
				src="https://checkout.stripe.com/checkout.js" class="stripe-button"
				<?php
				foreach ( $stripe_data as $key => $value ) {
					printf(
						'data-%s="%s" ',
						esc_attr( $key ),
						esc_attr( $value )
					);
				}
				?>
			></script>
		</form>
		<?php
		$payment_form = apply_filters(
			'ms_gateway_form',
			ob_get_clean(),
			$gateway,
			$invoice,
			$this
		);

		ob_start();
		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<td class="ms-buy-now-column" colspan="2">
				<!-- <?php echo $payment_form; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> -->
				<!-- New Method of stripe  -->
				<script src="https://js.stripe.com/v3/"></script> <?php //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
				<script>
					var stripe = Stripe('<?php echo esc_attr( $gateway->get_publishable_key() ); ?>');
					var elements = stripe.elements();
				</script>
				<button id="stripePayment"><?php echo esc_html( $label ); ?></button>
			</td>
		</tr>

		<script>
			jQuery("#stripePayment").click(function() {
				var data={};
				data['sub'] = <?php echo wp_json_encode( $subscription ); ?>;
				data['action'] = '<?php echo esc_attr( $action ); ?>';
				jQuery.ajax({
					type: 'POST',
					dataType: 'json',
					url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
					data: data,
					success: function( result ) {
						if (result.status == 1) {
							console.log(result.session_id);
							stripe.redirectToCheckout({
							sessionId: result.session_id
							}).then(function (result) {
							});
						}else{
							console.log(result.msg);
							alert("<?php esc_html_e( 'An error occurred while processing your payment. Please contact the administrator or try it again later.', 'memberdash' ); ?>");
						}
					}
				});
			});
		</script>

		<?php
		$html = ob_get_clean();

		$html = apply_filters(
			'ms_gateway_button-' . $gateway->id,
			$html,
			$this
		);

		return $html;
	}

	private function prepare_fields() {
		$gateway      = $this->data['gateway'];
		$subscription = $this->data['ms_relationship'];

		$fields = array(
			'_wpnonce'           => array(
				'id'    => '_wpnonce',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce(
					$gateway->id . '_' . $subscription->id
				),
			),
			'gateway'            => array(
				'id'    => 'gateway',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $gateway->id,
			),
			'ms_relationship_id' => array(
				'id'    => 'ms_relationship_id',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $subscription->id,
			),
			'step'               => array(
				'id'    => 'step',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		return $fields;
	}
}
