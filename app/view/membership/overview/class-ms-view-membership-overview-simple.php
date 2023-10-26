<?php
/**
 * Membership Overview View.
 *
 * @since  1.0.0
 *
 * @package MemberDash
 * @subpackage View
 */

/**
 * Membership Overview View.
 *
 * @since  1.0.0
 */
class MS_View_Membership_Overview_Simple extends MS_View {

	/**
	 * Returns the html for the membership overview page.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$this->check_simulation();

		$membership = $this->data['membership'];

		$toggle = array(
			'id'      => 'ms-toggle-' . $membership->id,
			'type'    => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'value'   => $membership->active,
			'class'   => '',
			'data_ms' => array(
				'action'        => MS_Controller_Membership::AJAX_ACTION_TOGGLE_MEMBERSHIP,
				'field'         => 'active',
				'membership_id' => $membership->id,
			),
		);

		$status_class = '';
		if ( $membership->active ) {
			$status_class = 'ms-active';
		}

		$edit_button = sprintf(
			'<a href="?page=%1$s&step=%2$s&tab=%3$s&membership_id=%4$s" class="button ms-ml-3 ms-mt-1">%5$s</a>',
			esc_attr( $_REQUEST['page'] ),
			MS_Controller_Membership::STEP_EDIT,
			MS_Controller_Membership::TAB_DETAILS,
			esc_attr( $membership->id ),
			'<i class="memberdash-fa memberdash-fa-pencil"></i> ' . __( 'Edit', 'memberdash' )
		);

		ob_start();
		?>
		<div class="wrap ms-wrap ms-membership-overview">
			<?php
			$desc = array(
				__( 'Here you find a summary of this membership, and alter any of its details.', 'memberdash' ),
				sprintf(
					__( 'This is a %s', 'memberdash' ),
					$membership->get_type_description()
				),
			);

			MS_Helper_Html::settings_header(
				array(
					'title' => sprintf( __( '%s Overview', 'memberdash' ), $membership->name ) . $edit_button,
					'desc'  => $desc,
				)
			);
			?>

			<div class="ms-membership-status-wrapper ms-bg-white ms-rounded-lg ms-shadow ms-p-6 ms-mb-6">
				<div class="ms-flex ms-items-center ms-space-x-2">
					<?php MS_Helper_Html::html_element( $toggle ); ?>

					<div id="ms-membership-status" class="ms-membership-status <?php echo esc_attr( $status_class ); ?>">
						<?php
						printf(
							'<div class="ms-active">%s</div>',
							sprintf(
								__( 'Membership is %s', 'memberdash' ), //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'<span id="ms-membership-status-text" class="ms-ok">' .
								esc_html__( 'Active', 'memberdash' ) .
								'</span>'
							)
						);
						printf(
							'<div>%s</div>',
							sprintf(
								__( 'Membership is %s', 'memberdash' ), //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'<span id="ms-membership-status-text" class="ms-nok">' .
								esc_html__( 'Inactive', 'memberdash' ) .
								'</span>'
							)
						);
						?>
					</div>
				</div>
			</div>

			<?php $this->available_content_panel(); ?>
		</div>

		<?php
		$html = ob_get_clean();

		return $html;
	}

	public function news_panel() {
		?>
		<div class="ms-settings-box ms-space-y-5">
			<h3>
				<i class="ms-low memberdash-fa memberdash-fa-globe"></i>
				<?php esc_html_e( 'Recent News', 'memberdash' ); ?>
			</h3>

			<?php if ( ! empty( $this->data['events'] ) ) : ?>
				<div class="inside group">
					<?php $this->news_panel_data( $this->data['events'] ); ?>
				</div>

				<div class="ms-news-view-wrapper">
					<?php
					$url = esc_url_raw(
						add_query_arg( array( 'step' => MS_Controller_Membership::STEP_NEWS ) )
					);
					MS_Helper_Html::html_element(
						array(
							'id'    => 'view_news',
							'type'  => MS_Helper_Html::TYPE_HTML_LINK,
							'value' => __( 'View More News', 'memberdash' ),
							'url'   => $url,
							'class' => 'memberdash-field-button button',
						)
					);
					?>
				</div>
			<?php else : ?>
				<div class="inside group">
					<p class="ms-italic">
					<?php esc_html_e( 'There will be some interesting news here when your site gets going.', 'memberdash' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders the Members panel
	 *
	 * @since 1.0.0
	 */
	public function members_panel() {
		$count         = count( $this->data['members'] );
		$membership_id = $this->data['membership']->id;
		?>
		<div class="ms-settings-box ms-space-y-5">
			<h3>
				<i class="ms-low memberdash-fa memberdash-fa-user"></i>
				<?php printf( esc_html__( 'New Members (%s)', 'memberdash' ), esc_attr( $count ) ); ?>
			</h3>

			<?php if ( $count > 0 ) : ?>
				<div class="inside group">
					<?php
					$this->members_panel_data(
						$this->data['members'],
						$membership_id
					);
					?>
				</div>

				<div class="ms-member-edit-wrapper">
					<?php
					$url = MS_Controller_Plugin::get_admin_url(
						'members',
						array( 'membership_id' => $membership_id )
					);
					MS_Helper_Html::html_element(
						array(
							'id'    => 'edit_members',
							'type'  => MS_Helper_Html::TYPE_HTML_LINK,
							'value' => __( 'All Members', 'memberdash' ),
							'url'   => $url,
							'class' => 'memberdash-field-button button',
						)
					);
					?>
				</div>
			<?php else : ?>
				<div class="inside group">
					<p class="ms-italic">
					<?php esc_html_e( 'No members yet.', 'memberdash' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Echo the news-contents. This function can be overwritten by other views
	 * to customize the list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $items List of news to display.
	 */
	protected function news_panel_data( $items ) {
		$item      = 0;
		$max_items = 10;
		$class     = '';
		?>
		<table class="ms-list-table widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'memberdash' ); ?></th>
					<th><?php esc_html_e( 'Member', 'memberdash' ); ?></th>
					<th><?php esc_html_e( 'Event', 'memberdash' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $this->data['events'] as $event ) :
				$item++;
				if ( $item > $max_items ) {
					break; }
				$class = ( 'alternate' == $class ? '' : 'alternate' );
				?>
				<tr class="<?php echo esc_attr( $class ); ?>">
					<td>
					<?php
					echo esc_html(
						MS_Helper_Period::format_date( $event->post_modified )
					);
					?>
					</td>
					<td><?php echo esc_html( MS_Model_Member::get_username( $event->user_id ) ); ?></td>
					<td><?php echo esc_html( $event->description ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Echo a member-list. This function can be overwritten by other views
	 * to customize the list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $members List of members to display.
	 */
	protected function members_panel_data( $members, $membership_id ) {
		$item         = 0;
		$max_items    = 10;
		$class        = '';
		$status_types = MS_Model_Relationship::get_status_types();
		?>
		<table class="ms-list-table widefat">
			<thead>
				<th><?php esc_html_e( 'Member', 'memberdash' ); ?></th>
				<th><?php esc_html_e( 'Since', 'memberdash' ); ?></th>
				<th><?php esc_html_e( 'Status', 'memberdash' ); ?></th>
			</thead>
			<tbody>
			<?php
			foreach ( $this->data['members'] as $member ) :
				$item++;
				if ( $item > $max_items ) {
					break; }
				$class        = ( 'alternate' == $class ? '' : 'alternate' );
				$subscription = $member->get_subscription( $membership_id );
				?>
				<tr class="<?php echo esc_attr( $class ); ?>">
					<td><?php echo esc_html( $member->username ); ?></td>
					<td>
					<?php
					echo esc_html(
						MS_Helper_Period::format_date( $subscription->start_date )
					);
					?>
					</td>
					<td><?php echo esc_html( $status_types[ $subscription->status ] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	public function available_content_panel() {
		$membership = $this->data['membership'];
		$desc       = $membership->get_description();

		?>
		<div class="ms-overview-container">
			<div class="ms-settings ms-pb-3">
				<div class="ms-overview-top ms-p-6 ms-mb-6 ms-bg-white ms-rounded-lg ms-shadow">
					<div class="ms-settings-desc ms-description membership-description">
						<?php echo $desc; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>

					<div class="ms-flex ms-flex-col lg:ms-flex-row ms-space-x-0 lg:ms-space-x-10 ms-space-y-10 lg:ms-space-y-0">
						<?php
						$this->news_panel();
						$this->members_panel();
						?>
					</div>
				</div>

				<div class="ms-overview-available-content-wrapper ms-overview-bottom ms-bg-white ms-rounded-lg ms-shadow ms-p-6">
					<h3>
						<i class="ms-img-unlock"></i> <?php esc_html_e( 'Available Content', 'memberdash' ); ?>
					</h3>

					<div class="ms-description">
						<?php
						printf(
							__( 'This is Content which <span class="ms-bold">%s</span> members has access to.', 'memberdash' ), //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							esc_html( $this->data['membership']->name )
						);
						?>
					</div>
					<div class="inside ms-mt-5 ms-flex ms-flex-col lg:ms-flex-row ms-space-x-0 lg:ms-space-x-10 ms-space-y-10 lg:ms-space-y-0 ms-items-start ms-gap-10">
						<?php $this->available_content_panel_data(); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	protected function available_content_panel_data() {
		$membership = $this->data['membership'];
		$rule_types = MS_Model_Rule::get_rule_types();

		?>
		<?php
			foreach ( $rule_types as $rule_type ) {
				$rule = $membership->get_rule( $rule_type );
				if ( ! $rule->is_active() ) {
					continue; }

				if ( $rule->has_rules() ) {
					$this->content_box( array(), $rule );
				}
			}
			?>
		<?php

		if ( ! $membership->is_free ) {
			$payment_url = esc_url_raw(
				add_query_arg(
					array(
						'step' => MS_Controller_Membership::STEP_PAYMENT,
						'edit' => 1,
					)
				)
			);

			MS_Helper_Html::html_element(
				array(
					'id'    => 'setup_payment',
					'type'  => MS_Helper_Html::TYPE_HTML_LINK,
					'value' => __( 'Payment Options', 'memberdash' ),
					'url'   => $payment_url,
					'class' => 'memberdash-field-button button',
				)
			);
		}
	}

	/**
	 * Outputs a content list as tag-list.
	 *
	 * @since 1.0.0
	 *
	 * @param  array   $contents List of content items to display.
	 * @param  MS_Rule $rule     The rule object.
	 */
	protected function content_box( $contents, $rule ) {
		static $row_items = 0;

		$rule_titles = MS_Model_Rule::get_rule_type_titles();
		$title       = $rule_titles[ $rule->rule_type ];
		$contents    = (array) $rule->get_contents( null, true );

		$membership_id = $this->data['membership']->id;

		$row_items++;
		$new_row  = ( 0 == $row_items % 4 );
		$show_sep = ( 0 == ( $row_items - 1 ) % 4 );

		if ( $show_sep && $row_items > 1 ) {
			MS_Helper_Html::html_separator();
		}
		?>
		<div class="ms-available-content-column">
			<?php
			if ( ! $new_row ) {
				// MS_Helper_Html::html_separator( 'vertical' ); 
			}
			?>
			<div class="ms-bold">
				<?php printf( '%s (%s):', $title, $rule->count_rules() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="inside">
				<ul class="ms-content-tag-list ms-group ms-flex ms-flex-wrap">
				<?php
				foreach ( $contents as $content ) {
					if ( $content->access ) {
						MS_Helper_Html::content_tag( $content );
					}
				}
				?>
				</ul>

				<div class="ms-protection-edit-wrapper">
					<?php
					$edit_url = MS_Controller_Plugin::get_admin_url(
						'protection',
						array(
							'tab'           => $rule->rule_type,
							'membership_id' => $membership_id,
						)
					);

					MS_Helper_Html::html_element(
						array(
							'id'    => 'edit_' . $rule->rule_type,
							'type'  => MS_Helper_Html::TYPE_HTML_LINK,
							'title' => $title,
							'value' => sprintf( __( 'Edit %s Access', 'memberdash' ), $title ),
							'url'   => $edit_url,
							'class' => 'memberdash-field-button button',
						)
					);
					?>
				</div>
			</div>
		</div>
		<?php
		if ( $new_row ) {
			echo '</div><div class="ms-group">';
		}
	}
}
