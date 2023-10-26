<?php

/**

 * Widget: Users stats
 *
 * @since 1.0.0
 */
class MS_DashboardWidget_Users extends MS_DashboardWidget {

	const ID = 'ms_widget_users';

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
		return __( 'MemberDash | Users', 'memberdash' );
	}

	/**
	 * Initializes the Widget. Always executed.
	 *
	 * @since 1.0.0
	 */
	public function init() {

	}

	/**
	 * Render the widget.
	 *
	 * @return void HTML content
	 */
	public function render() {

		$result = self::$reporting_model->get_users_stats();
		?>
		<div id="<?php echo esc_attr( $this->get_id() ); ?>" style="width:100%" class="ms-memberdash ms-flex ms-flex-col ms-items-center">

				<div class="memberdash-data-block ms-w-1/2">
					<div class="memberdash-data-block-wrapper">
						<h3 class="memberdash-data-block-title ms-uppercase ms-font-normal"><?php echo esc_html_e( 'Active users last 3 months', 'memberdash' ); ?></h3>
						<div class="memberdash-data-block-data ms-text-5xl"><?php echo esc_attr( $result['active_users'] ); ?></div>
					</div>
				</div>
				<div class="memberdash-data-block ms-w-1/2">
					<div class="memberdash-data-block-wrapper">
						<h3 class="memberdash-data-block-title ms-uppercase ms-font-normal"><?php echo esc_html_e( 'New users 7 past days', 'memberdash' ); ?></h3>
						<div class="memberdash-data-block-data ms-text-5xl"><?php echo esc_attr( $result['new_users'] ); ?></div>
					</div>
				</div>

		</div>
		<?php
	}
}
