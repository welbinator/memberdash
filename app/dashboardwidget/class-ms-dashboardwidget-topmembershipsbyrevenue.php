<?php

/**

 * Widget: Top selling memberships by revenue
 *
 * @since 1.0.0
 */
class MS_DashboardWidget_TopMembershipsByRevenue extends MS_DashboardWidget {

	const ID                        = 'ms_widget_top_memberships_by_revenue';
	const DEFAULT_START_DAYS_BEFORE = 6;
	const DEFAULT_TOP_RESULTS       = 10;

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since 1.0.0

	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Return the widget title.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'MemberDash | Top selling memberships by revenue', 'memberdash' );
	}

	/**
	 * Initializes the Widget. Always executed.
	 *
	 * @since 1.0.0
	 */
	public function init() {

	}

	protected function ajax_process_daterange_callback( $data = array() ) {
		$settings                          = MS_Factory::load( 'MS_Model_Settings' );
		$data['start_date_before_default'] = self::DEFAULT_START_DAYS_BEFORE;

		$result = self::$reporting_model->get_top_selling_memberships_by_revenue( $this->get_datarange_start_date( $data ), $this->get_datarange_end_date( $data ), self::DEFAULT_TOP_RESULTS );

		if ( 0 === count( $result ) ) {
			echo esc_html__( 'There are no selling in that period.', 'memberdash' );
		} else {
			?>
			<table style="width: 100%" class="wp-list-table widefat fixed memberships ms-border ms-border-solid ms-border-gray-200">
				<thead>
					<th style="text-align: left" class="manage-column column-cb"><?php esc_html_e( 'Membership', 'memberdash' ); ?></th>
					<th style="text-align: right" class="manage-column column-cb"><?php esc_html_e( 'Sales amount', 'memberdash' ); ?></th>
					<th style="text-align: right" class="manage-column column-cb"><?php echo esc_html( sprintf( __( 'Total revenue (%s)', 'memberdash' ), $settings->currency ) ); ?></th>
				</thead>

				<tbody>
					<?php
					foreach ( $result as $row ) :
						?>
					<tr class="alternate item">
						<td class="">
						<span class="ms-type-desc ms-ml-1 ms-inline-block ms-user"><?php echo esc_html( $row->membership_name ); ?></span>
						</td>
						<td class="" style="text-align: right">
							<span class="ms-type-desc ms-ml-1 ms-inline-block ms-user"><?php echo esc_html( $row->sales_amount ); ?></span>
						</td>
						<td class="" style="text-align: right">
							<span class="ms-type-desc ms-ml-1 ms-inline-block ms-user"><?php echo esc_html( number_format_i18n( $row->total_revenue, 2 ) ); ?></span>
						</td>
					</tr>
						<?php
					endforeach;
					?>
				</tbody>
			</table>
					<?php
		}
	}

				/**
				 * Render the widget.
				 *
				 * @return void HTML content
				 */
	public function render() {
				$ajax_block_id = "data-{$this->get_id()}";
		?>
<div id="<?php echo esc_attr( $this->get_id() ); ?>" class="ms-memberdash">
	<div class="memberdash-wp-dashboard">
					<?php $this->render_daterange( $ajax_block_id, self::DEFAULT_START_DAYS_BEFORE ); ?>
		<div id="<?php echo esc_attr( $ajax_block_id ); ?>" class="memberdash-wp-dashboard-table">
					<?php $this->ajax_process_daterange_callback(); ?>
		</div>
	</div>
</div>
					<?php
	}
}
