<?php

/**
 * Dialog: Member Profile
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 * @package MemberDash
 * @subpackage View
 */
class MS_View_Member_Dialog extends MS_Dialog {

	const ACTION_SAVE = 'ms_save_member';

	/**
	 * Generate/Prepare the dialog attributes.
	 *
	 * @since 1.0.0
	 */
	public function prepare() {
		$member_id = $_POST['member_id'];
		$member    = MS_Factory::load( 'MS_Model_Member', $member_id );

		$data = array(
			'model' => $member,
		);

		$data = apply_filters( 'ms_view_member_dialog_data', $data );

		// Dialog Title
		$this->title = sprintf(
			__( 'Profile: %1$s %2$s', 'memberdash' ),
			esc_html( $member->first_name ),
			esc_html( $member->last_name )
		);

		// Dialog Size
		$this->width  = 940;
		$this->height = 500;

		// Contents
		$this->content = $this->get_contents( $data );

		// Make the dialog modal
		$this->modal = true;
	}

	/**
	 * Save the gateway details.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function submit() {
		$data = $_POST;
		$res  = MS_Helper_Member::MEMBER_MSG_NOT_UPDATED;

		unset( $data['action'] );
		unset( $data['dialog'] );

		// Update the memberships
		if ( isset( $_POST['dialog_action'] )
			&& $this->verify_nonce( $_POST['dialog_action'] )
			&& isset( $_POST['member_id'] )
		) {
			// No input fields, so we cannot save anything...
			$res = MS_Helper_Member::MEMBER_MSG_UPDATED;
		}

		return $res;
	}

	/**
	 * Returns the content of the dialog
	 *
	 * @param array $data The data.
	 *
	 * @since 1.0.0
	 *
	 * @return object
	 */
	public function get_contents( $data ) {
		$member = $data['model'];

		$currency   = MS_Plugin::instance()->settings->currency;
		$show_trial = MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL );

		$all_subscriptions = MS_Model_Relationship::get_subscriptions(
			array(
				'user_id'  => $member->id,
				'status'   => 'all',
				'meta_key' => 'expire_date',
				'orderby'  => 'meta_value',
				'order'    => 'DESC',
			)
		);

		// Prepare the form fields.
		$inp_dialog = array(
			'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name'  => 'dialog',
			'value' => 'View_Member_Dialog',
		);

		$inp_id = array(
			'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name'  => 'member_id',
			'value' => $member->id,
		);

		$inp_nonce = array(
			'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name'  => '_wpnonce',
			'value' => wp_create_nonce( self::ACTION_SAVE ),
		);

		$inp_action = array(
			'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name'  => 'dialog_action',
			'value' => self::ACTION_SAVE,
		);

		$inp_save = array(
			'type'  => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Save', 'memberdash' ),
			'class' => 'ms-submit-form',
			'data'  => array(
				'form' => 'ms-edit-member',
			),
		);

		$inp_cancel = array(
			'type'  => MS_Helper_Html::INPUT_TYPE_BUTTON,
			'value' => __( 'Close', 'memberdash' ),
			'class' => 'close',
		);

		ob_start();
		?>
		<div>
			<form class="ms-form memberdash-ajax-update ms-edit-member" data-memberdash-ajax="<?php echo esc_attr( 'save' ); ?>">
				<div class="ms-form memberdash-form memberdash-grid-8">
					<table class="widefat">
					<thead>
						<tr>
							<th class="column-membership">
								<?php esc_html_e( 'Membership', 'memberdash' ); ?>
							</th>
							<th class="column-status">
								<?php esc_html_e( 'Status', 'memberdash' ); ?>
							</th>
							<th class="column-start">
								<?php esc_html_e( 'Subscribed on', 'memberdash' ); ?>
							</th>
							<th class="column-expire">
								<?php esc_html_e( 'Expires on', 'memberdash' ); ?>
							</th>
							<?php if ( $show_trial ) : ?>
							<th class="column-trialexpire">
								<?php esc_html_e( 'Trial until', 'memberdash' ); ?>
							</th>
							<?php endif; ?>
							<th class="column-payments">
								<?php esc_html_e( 'Payments', 'memberdash' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $all_subscriptions as $subscription ) :
						$membership = $subscription->get_membership();
						$payments   = $subscription->get_payments();

						$num_payments    = count( $payments );
						$amount_payments = 0;
						foreach ( $payments as $payment ) {
							$amount_payments += $payment['amount'];
						}

						$subscription_info = array(
							'subscription_id' => $subscription->id,
						);
						$update_info       = array(
							'subscription_id' => $subscription->id,
							'statuscheck'     => 'yes',
						);
						?>
						<tr>
							<td class="column-membership">
								<?php $membership->name_tag(); ?>
							</td>
							<td class="column-status">
								<?php
								printf(
									'<a href="#" data-ms-dialog="View_Member_Subscription" data-ms-data="%2$s">%1$s</a>
									<a href="#" data-ms-dialog="View_Member_Subscription" data-ms-data="%3$s" title="%5$s">%4$s</a>',
									esc_attr( $subscription->status ),
									esc_attr( wp_json_encode( $subscription_info ) ),
									esc_attr( wp_json_encode( $update_info ) ),
									'<i class="dashicons dashicons-update"></i>',
									esc_html__( 'Check and update subscription status', 'memberdash' )
								);
								?>
							</td>
							<td class="column-start">
								<?php echo esc_html( $subscription->start_date ); ?>
							</td>
							<td class="column-expire">
								<?php echo esc_html( $subscription->expire_date ); ?>
							</td>
							<?php if ( $show_trial ) : ?>
							<td class="column-trialexpire">
								<?php
								if ( $subscription->start_date == $subscription->trial_expire_date ) {
									echo '-';
								} else {
									echo esc_html( $subscription->trial_expire_date );
								}
								?>
							</td>
							<?php endif; ?>
							<td class="column-payments">
								<?php
								$total = sprintf(
									'<b>%1$s</b> (%3$s %2$s)',
									$num_payments,
									MS_Helper_Billing::format_price( $amount_payments ),
									$currency
								);

								printf(
									'<a href="#" data-ms-dialog="View_Member_Payment" data-ms-data="%1$s">%2$s</a>',
									esc_attr( wp_json_encode( $subscription_info ) ),
									wp_kses_post( $total )
								);
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
					</table>
				</div>
				<?php
				MS_Helper_Html::html_element( $inp_id );
				MS_Helper_Html::html_element( $inp_dialog );
				MS_Helper_Html::html_element( $inp_nonce );
				MS_Helper_Html::html_element( $inp_action );
				?>
			</form>
			<div class="buttons">
				<?php
				MS_Helper_Html::html_element( $inp_cancel );
				// MS_Helper_Html::html_element( $inp_save );
				?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		return apply_filters( 'ms_view_member_dialog_to_html', $html );
	}

};
