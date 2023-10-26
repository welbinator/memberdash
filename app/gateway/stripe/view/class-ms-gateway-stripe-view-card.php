<?php

class MS_Gateway_Stripe_View_Card extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();

		ob_start();
		?>
			<div class="ms-wrap ms-card-info-wrapper">
				<h2><?php esc_html_e( 'Credit card info', 'memberdash' ); ?> </h2>
				<table class="ms-table">
					<tbody>
						<tr>
							<th><?php esc_html_e( 'Card Number', 'memberdash' ); ?></th>
							<th><?php esc_html_e( 'Card Expiration date', 'memberdash' ); ?></th>
						</tr>
						<tr>
							<td><?php echo '**** **** **** ' . esc_attr( $this->data['stripe']['card_num'] ); ?></td>
							<td><?php echo '' . esc_attr( $this->data['stripe']['card_exp'] ); ?></td>
						</tr>
					</tbody>
				</table>
				<form action="" method="post">
					<?php
					foreach ( $fields as $field ) {
						MS_Helper_Html::html_element( $field );
					}
					?>
					<script
						src="https://checkout.stripe.com/checkout.js" class="stripe-button"
						data-key="<?php echo esc_attr( $this->data['publishable_key'] ); ?>"
						data-amount="0"
						data-name="<?php echo esc_attr( bloginfo( 'name' ) ); ?>"
						data-description="<?php esc_attr_e( 'Just change card', 'memberdash' ); ?>"
						data-panel-label="<?php esc_attr_e( 'Change credit card', 'memberdash' ); ?>"
						data-email="<?php echo esc_attr( $this->data['member']->email ); ?>"
						data-label="<?php esc_attr_e( 'Change card number', 'memberdash' ); ?>"
						>
					</script>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	private function prepare_fields() {
		$fields = array(
			'gateway'            => array(
				'id'    => 'gateway',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['gateway']->id,
			),

			'ms_relationship_id' => array(
				'id'    => 'ms_relationship_id',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['ms_relationship_id'],
			),

			'_wpnonce'           => array(
				'id'    => '_wpnonce',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( 'update_card' ),
			),

			'action'             => array(
				'id'    => 'action',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'update_card',
			),
		);

		return $fields;
	}
}
