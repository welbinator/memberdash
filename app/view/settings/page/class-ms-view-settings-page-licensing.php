<?php
/**
 * Settings > Licensing.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

/**
 * Settings > Licensing page class.
 *
 * @since 1.0.0
 */
class MS_View_Settings_Page_Licensing extends MS_View_Settings_Edit {

	/**
	 * Return the HTML form.
	 *
	 * @since 1.0.0
	 *
	 * @return object
	 */
	public function to_html() {
		$settings      = $this->data['settings'];
		$license_key   = $settings->license_key;
		$license_email = $settings->license_email;

		$license_controller = MS_Factory::load( 'MS_Licensing', $license_key );
		$license_data       = $license_controller->get_data();

		$fields = array(
			'license_email'    => array(
				'id'        => 'license_email',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => __( 'Email', 'memberdash' ),
				'value'     => $license_email,
				'class'     => 'ms-w-full',
				'maxlength' => 35,
			),
			'license_key'      => array(
				'id'        => 'license_key',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => __( 'License key', 'memberdash' ),
				'value'     => $license_key,
				'class'     => 'ms-w-full',
				'maxlength' => 35,
			),
			'nonce_save'       => array(
				'id'    => 'nonce_save',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( MS_Controller_Settings::POST_ACTION_UPDATE_LICENSE_KEY ),
			),
			'nonce_deactivate' => array(
				'id'    => 'nonce_deactivate',
				'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( MS_Controller_Settings::POST_ACTION_DEACTIVATE_LICENSE_KEY ),
			),
		);

		$action_save_url       = esc_url_raw( add_query_arg( 'action', MS_Controller_Settings::POST_ACTION_UPDATE_LICENSE_KEY, admin_url( 'admin-post.php' ) ) );
		$action_deactivate_url = esc_url_raw( add_query_arg( 'action', MS_Controller_Settings::POST_ACTION_DEACTIVATE_LICENSE_KEY, admin_url( 'admin-post.php' ) ) );

		ob_start();
		MS_Helper_Html::settings_tab_header(
			array(
				'title' => __( 'Licensing', 'memberdash' ),
			)
		);
		?>

		<?php MS_Helper_Html::settings_box_header(); ?>

		<form action="<?php echo esc_url( $action_save_url ); ?>" method="post" class="cf">
			<?php MS_Helper_Html::html_element( $fields['nonce_save'] ); ?>
			<div class="lg:ms-items-end ms-items-start ms-space-y-5">
				<div class="ms-pr-1 lg:ms-w-2/5 lg:ms-mb-0 ms-mb-2">
					<?php MS_Helper_Html::html_element( $fields['license_email'] ); ?>
				</div>
				<div class="ms-pr-1 lg:ms-w-2/5 lg:ms-mb-0 ms-mb-2">
					<?php MS_Helper_Html::html_element( $fields['license_key'] ); ?>
				</div>
			</div>
			<div class="memberdash-separator"></div>
			<div>
				<?php
				MS_Helper_Html::html_submit(
					array(
						'id'    => 'ms_save_license',
						'value' => __( 'Save License', 'memberdash' ),
						'class' => 'memberdash-field-input button button-primary ms-bg-black ms-border-black ms-text-white ms-shadow-none memberdash-ajax-update',
					)
				);
				?>
			</div>
		</form>

		<?php if ( ! empty( $license_key ) && false !== $license_data ) { ?>
			<?php if ( ! is_array( $license_data ) ) { ?>
				<div class="ms-text-error"><?php echo esc_html( $license_data ); ?></div>
			<?php } else { ?>
				<div>
					<span class="memberdash-field-label memberdash-label-license_key"><?php esc_html_e( 'License information', 'memberdash' ); ?></span>
					<div class="ms-flex ms-flex-row ms-my-4">
						<span class="ms-pr-2"><?php esc_html_e( 'Status', 'memberdash' ); ?></span>
						<span class="ms-rounded-full ms-text-center ms-px-2 ms-w-20 <?php echo 'Active' === $license_data['status'] ? esc_attr( 'ms-bg-green-400 ms-text-white' ) : esc_attr( 'ms-bg-gray-300' ); ?>"><?php echo esc_html( $license_data['status'] ); ?></span>
					</div>
					<?php if ( ! empty( $license_data['name'] ) ) { ?>
						<div class="ms-flex ms-flex-col ms-mb-2">
							<span class="ms-pr-2"><?php esc_html_e( 'Customer', 'memberdash' ); ?></span>
							<span class="ms-font-bold"><?php echo esc_html( $license_data['name'] ); ?></span>
						</div>
					<?php } ?>
					<div class="ms-flex ms-flex-col ms-mb-2">
						<span class="ms-pr-2"><?php esc_html_e( 'Expiry', 'memberdash' ); ?></span>
						<span class="ms-font-bold"><?php echo esc_html( $license_data['expires'] ); ?></span>
					</div>
				</div>
				<form action="<?php echo esc_url( $action_deactivate_url ); ?>" method="post" class="cf ms-space-y-6">
					<?php MS_Helper_Html::html_element( $fields['nonce_deactivate'] ); ?>
					<div class="ms-block">
					<?php
					MS_Helper_Html::html_submit(
						array(
							'id'    => 'ms_deactivate_license',
							'value' => __( 'Deactivate License', 'memberdash' ),
							'class' => 'button button-primary',
						)
					);
					?>
					</div>
				</form>
			<?php } ?>
		<?php } ?>

		<?php
			return ob_get_clean();
	}

}
