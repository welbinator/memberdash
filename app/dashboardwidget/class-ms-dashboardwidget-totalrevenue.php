<?php

/**

 * Widget: Total revenue (sales)
 *
 * @since 1.0.0
 */
class MS_DashboardWidget_TotalRevenue extends MS_DashboardWidget {

	const ID                        = 'ms_widget_total_revenue';
	const DEFAULT_START_DAYS_BEFORE = 6;

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
		return __( 'MemberDash | Total revenue (sales)', 'memberdash' );
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

		$result = self::$reporting_model->get_revenue_by_date_range( $this->get_datarange_start_date( $data ), $this->get_datarange_end_date( $data ) );
		?>
			<div class="ms-flex ms-flex-col ms-items-center" style="width:100%">
				<div class="memberdash-data-block ms-w-1/2">
					<div class="memberdash-data-block-wrapper">
						<h3 class="memberdash-data-block-title ms-uppercase ms-font-normal"><?php echo esc_html_e( 'Sales amount', 'memberdash' ); ?></h3>
						<div class="memberdash-data-block-data ms-text-5xl"><?php echo esc_attr( $result['sales_amount'] ); ?></div>
					</div>
				</div>
				<div class="memberdash-data-block ms-w-1/2">
					<div class="memberdash-data-block-wrapper">
						<h3 class="memberdash-data-block-title ms-uppercase ms-font-normal"><?php echo esc_html_e( 'Total Revenue', 'memberdash' ); ?></h3>
						<div class="memberdash-data-block-data ms-text-5xl"><?php echo esc_attr( number_format_i18n( $result['total_revenue'], 2 ) ) . ' <span class="ms-text-lg">' . esc_attr( $settings->currency ) . '</span>'; ?></div>
					</div>
				</div>
			</div>
		<?php
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
		<div id="<?php echo esc_attr( $ajax_block_id ); ?>" class="memberdash-wp-dashboard-stats ms-block">
			<?php $this->ajax_process_daterange_callback(); ?>
		</div>
	</div>
</div>
		<?php
	}
}
