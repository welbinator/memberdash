<?php
/**
 * View.
 *
 * @package MemberDash
 */

/**
 * Renders Reporting Page.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 *
 * @return object
 */
class MS_View_Reporting extends MS_View {

	public function to_html() {
		$this->check_simulation();

		ob_start();
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Reporting', 'memberdash' ),
				)
			);
			?>
			<div class="ms-flex lg:ms-flex-row ms-flex-col">
				<div class="ms-bg-white ms-rounded-lg ms-shadow ms-divide-y ms-divide-gray-200 lg:ms-w-1/3 ms-w-full ms-mb-4" style="height: 460px; min-width: 550px;">
					<div class="box-reporting-csv ms-py-4 ms-px-8">
						<div class="ms-flex ms-flex-col ms-mb-8">
							<h4 class="ms-text-lg ms-pb-2 ms-m-0 ms-mt-4"><?php esc_html_e( 'CSV Reports', 'memberdash' ); ?></h4>
							<span><?php esc_html_e( 'Use the filters to generate and download the report', 'memberdash' ); ?></span>
							<div class="ms-py-4 ms-flex ms-flex-col">
								<select id="ms-reporting-type" class="ms-my-2 memberdash-field-input memberdash-field-select ms-flex">
									<?php foreach ( $this->data['report_type'] as $key => $value ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
									<?php endforeach; ?>
								</select>
								<span id="ms-daterange-wrapper" class="memberdash-wp-dashboard-daterange-wrapper ms-my-2 ms-h-10" style="display:none;">
									<div id="ms-reporting-daterange"
										class="ms-daterange memberdash-wp-dashboard-daterange memberdash-field-input ms-rounded-md ms-h-10 ms-flex ms-flex-row ms-items-center ms-justify-between"
										data-start-days-before="6">
										<div><i class="dashicons dashicons-calendar-alt"></i>&nbsp;<span></span></div><i class="dashicons dashicons-arrow-down-alt2"></i>
									</div>
								</span>
								<select id="ms-reporting-membership" class="ms-my-2 memberdash-field-input memberdash-field-select ms-flex" style="display:none;">
									<?php foreach ( $this->data['memberships'] as $key => $value ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
									<?php endforeach; ?>
								</select>
								<select id="ms-reporting-gateway" class="ms-my-2 memberdash-field-input memberdash-field-select ms-flex" style="display:none;">
									<?php foreach ( $this->data['gateways'] as $key => $value ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="item-icon"><i class="memberdash-fa memberdash-fa-table"></i></div>
					</div>
					<div class="ms-h-20 ms-border-0 ms-border-t ms-border-solid ms-border-gray-200 ms-flex ms-items-center ms-justify-end">
						<button id="ms-download-csv" data-url="<?php echo esc_attr( $this->data['download_csv_url'] ); ?>" class="ms-bg-white ms-border-0 ms-cursor-pointer button-reporting-csv ms-font-bold ms-px-8 ms-py-4"><?php esc_html_e( 'Download Report', 'memberdash' ); ?></button>
					</div>
				</div>
				<div id="ms-reporting-widgets" class="lg:ms-w-2/3 ms-w-full ms-flex ms-flex-wrap">
					<?php foreach ( $this->data['widgets'] as $widget ) : ?>
						<div 
							class="ms-reporting-widget ms-w-full lg:ms-w-1/3 postbox ms-bg-white ms-rounded-lg ms-shadow ms-divide-y ms-divide-gray-200 ms-border-0 ms-p-4"
							style="min-width: 420px;"
							>
							<div class="ms-flex ms-flex-col ms-p-2">
								<h4 class="ms-text-lg ms-pb-2 ms-m-0"><?php echo esc_html( $widget->get_name() ); ?></h4>
							</div>
							<div class="inside">
								<?php $widget->render(); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<?php
			return ob_get_clean();
	}

}
