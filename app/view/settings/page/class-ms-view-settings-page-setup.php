<?php
/**
 * Displays the Setup form.
 * Used in both the success popup when creating the first membership and in the
 * settings page.
 *
 * @since 1.0.0
 * @package MemberDash
 * @subpackage Model
 */
class MS_View_Settings_Page_Setup extends MS_View {

	/**
	 * Type of form displayed. Used to determine height of the popup.
	 *
	 * @var string
	 */
	protected $form_type = 'full';

	/**
	 * Displays the settings form.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		if ( ! empty( $_REQUEST['full_popup'] ) ) {
			$show_wizard_done = true;
		} else {
			$show_wizard_done = MS_Plugin::instance()->settings->is_first_membership;
		}

		if ( $show_wizard_done ) {
			$this->form_type = 'full';
			$code            = $this->html_full_form();
		} else {
			$this->form_type = 'short';
			$code            = $this->html_short_form();
		}

		return $code;
	}

	/**
	 * Display the small "completed" form
	 *
	 * @since 1.0.0
	 * @return string HTML Code
	 */
	public function html_short_form() {
		$code = sprintf(
			'<center>%1$s</center>',
			sprintf(
				__( 'You can now go to page %1$sProtection Rules%2$s to set up access levels for this Membership.', 'memberdash' ),
				sprintf( '<a href="%1$s">', MS_Controller_Plugin::get_admin_url( 'protection' ) ),
				'</a>'
			)
		);

		return $code;
	}

	/**
	 * Display the full settings form, used either by first membership
	 * "completed" popup and also by the general settings tab.
	 *
	 * @since 1.0.0
	 * @return string HTML code
	 */
	public function html_full_form() {
		$fields = $this->prepare_fields();

		ob_start();
		?>
		<div class="ms-setup-form ms-space-y-6">

			<?php MS_Helper_Html::settings_box_header(); ?>
				<?php if ( ! MS_Plugin::is_network_wide() ) : ?>
					<div class="ms-setup-nav">
						<div class="ms-title">
							<i class="ms-icon dashicons dashicons-menu"></i>
							<?php esc_html_e( 'Please select pages you want to appear in your Navigation', 'memberdash' ); ?>
						</div>
						<div class="ms-description">
							<?php
							printf(
								esc_html__( 'You can always change those later by going to %1$s in your admin sidebar.', 'memberdash' ),
								sprintf(
									'<a href="%1$s" target="_blank">%2$s</a>',
									esc_url( admin_url( 'nav-menus.php' ) ),
									esc_html__( 'Appearance', 'memberdash' ) . ' &raquo; ' . esc_html__( 'Menus', 'memberdash' )
								)
							);
							?>
						</div>
						<?php echo $this->show_menu_controls(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php else : ?>
					<div class="ms-setup-site">
						<div class="ms-title">
							<i class="ms-icon dashicons dashicons-admin-network"></i>
							<?php esc_html_e( 'Select the Site that hosts Membership Pages', 'memberdash' ); ?>
						</div>
						<div class="ms-description">
							<?php esc_html_e( 'When you change the site new Membership Pages are created on the selected site. You can customize or replace these pages at any time.', 'memberdash' ); ?>
						</div>
						<?php
						$site_options = MS_Helper_Settings::get_blogs();
						$site_fields  = array(
							array(
								'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
								'id'            => 'network_site',
								'title'         => __( 'Select the site that hosts the Membership Pages', 'memberdash' ),
								'value'         => MS_Model_Pages::get_site_info( 'id' ),
								'field_options' => $site_options,
								'class'         => 'ms-site-options',
							),
							array(
								'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
								'name'  => 'action',
								'value' => 'network_site',
							),
							array(
								'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
								'name'  => '_wpnonce',
								'value' => wp_create_nonce( 'network_site' ),
							),
							array(
								'type'  => MS_Helper_Html::INPUT_TYPE_SUBMIT,
								'value' => __( 'Save', 'memberdash' ),
							),
							array(
								'type'  => MS_Helper_Html::INPUT_TYPE_BUTTON,
								'class' => 'ms-setup-pages-cancel',
								'value' => __( 'Cancel', 'memberdash' ),
							),
						);
						?>
						<div class="ms-setup-pages-site">
							<div class="ms-setup-pages-site-info">
							<?php
							printf(
								esc_html__( 'Membership pages are located on site %s', 'memberdash' ),
								'<strong>' . esc_attr( MS_Model_Pages::get_site_info( 'title' ) ) . '</strong>'
							);
							?>
							<a href="#change-site" class="ms-setup-pages-change-site">
							<?php
							esc_html_e( 'Change site...', 'memberdash' );
							?>
							</a></div>
							<div class="ms-setup-pages-site-form cf" style="display:none;">
								<?php
								foreach ( $site_fields as $field ) {
									MS_Helper_Html::html_element( $field );
								}
								?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php MS_Helper_Html::settings_box_footer(); ?>

			<?php
				MS_Helper_Html::settings_box_header(
					__( 'Membership Pages', 'memberdash' ),
					__( 'Set Up membership pages that will be displayed on your website.', 'memberdash' )
				);
			?>
				<div class="ms-setup-pages ms-space-y-5">
					<?php
					if ( is_array( $fields['pages'] ) ) {
						$page_types      = array_keys( $fields['pages'] );
						$page_types_menu = array(
							'memberships',
							'register',
							'account',
						);
						$page_types_rest = array_diff( $page_types, $page_types_menu );
						$groups          = array(
							'in-menu' => $page_types_menu,
							'no-menu' => $page_types_rest,
						);

						$pages_site_id = MS_Model_Pages::get_site_info( 'id' );
						MS_Factory::select_blog( $pages_site_id );

						foreach ( $groups as $group_key => $group_items ) :
							printf( '<div class="ms-space-y-5 %1$s">', esc_attr( $group_key ) );

							foreach ( $group_items as $key ) :
								$field = $fields['pages'][ $key ];
								?>
								<div class="ms-settings-page-wrapper">
									<?php MS_Helper_Html::html_separator(); ?>

									<?php MS_Helper_Html::html_element( $field ); ?>

									<div class="ms-action ms-font-light ms-mt-1.5">
										<?php
										MS_Helper_Html::html_link(
											array(
												'id'      => 'url_page_' . $field['value'],
												'url'     => '',
												'value'   => __( 'view page', 'memberdash' ),
												'target'  => '_blank',
												'data_ms' => array(
													'base' => MS_Helper_Utility::get_home_url(
														$pages_site_id,
														'index.php?page_id='
													),
												),
											)
										);
										?>
										<span> | </span>
										<?php
										MS_Helper_Html::html_link(
											array(
												'id'      => 'edit_url_page_' . $field['value'],
												'url'     => '',
												'value'   => __( 'edit page', 'memberdash' ),
												'target'  => '_blank',
												'data_ms' => array(
													'base' => get_admin_url(
														$pages_site_id,
														'post.php?action=edit&post='
													),
												),
											)
										);
										?>
									</div>
								</div>
								<?php
							endforeach;

							echo '</div>';
						endforeach;
					} else {
						echo $fields['pages']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}

					MS_Factory::revert_blog();
					?>
				</div>
			<?php MS_Helper_Html::settings_box_footer(); ?>
		</div>
		<?php

		$html = ob_get_clean();

		return apply_filters(
			'ms_view_settings_page_setup_to_html',
			$html
		);
	}

	/**
	 * Prepare the HTML fields that can be displayed
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function prepare_fields() {
		// Prepare the return value.
		$nav   = array();
		$pages = array();

		MS_Model_Pages::create_missing_pages();
		$page_types      = MS_Model_Pages::get_page_types();
		$page_types_menu = array(
			'memberships',
			'register',
			'account',
		);
		$page_types_rest = array_diff( $page_types, $page_types_menu );

		// Prepare NAV fields.
		$menu_action = MS_Controller_Pages::AJAX_ACTION_TOGGLE_MENU;
		$menu_nonce  = wp_create_nonce( $menu_action );
		foreach ( $page_types_menu as $type ) {
			$nav_exists   = MS_Model_Pages::has_menu( $type );
			$nav[ $type ] = array(
				'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'id'        => 'nav_' . $type,
				'value'     => $nav_exists,
				'title'     => $page_types[ $type ],
				'ajax_data' => array(
					'action'   => $menu_action,
					'item'     => $type,
					'_wpnonce' => $menu_nonce,
				),
			);
		}

		// Prepare PAGES fields.
		$pages_action = MS_Controller_Pages::AJAX_ACTION_UPDATE_PAGES;
		$pages_nonce  = wp_create_nonce( $pages_action );

		foreach ( $page_types as $type => $label ) {
			$page_id = MS_Model_Pages::get_setting( $type );

			$pages[ $type ] = array(
				'id'            => $type,
				'type'          => MS_Helper_Html::INPUT_TYPE_WP_PAGES,
				'title'         => $label,
				'desc'          => MS_Model_Pages::get_description( $type ),
				'value'         => $page_id,
				'field_options' => array(
					'no_item' => __( '- Select a page -', 'memberdash' ),
				),
				'ajax_data'     => array(
					'field'    => $type,
					'action'   => $pages_action,
					'_wpnonce' => $pages_nonce,
				),
			);
		}

		$fields = array(
			'nav'   => $nav,
			'pages' => $pages,
		);

		return apply_filters(
			'ms_view_settings_page_setup_prepare_fields',
			$fields,
			$this
		);
	}

	/**
	 * Outputs the HTML code to toggle Membership menu items.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function show_menu_controls() {
		$code           = '';
		$can_create_nav = MS_Model_Pages::can_edit_menus();

		if ( $can_create_nav ) {
			$fields = $this->prepare_fields();
			foreach ( $fields['nav'] as $field ) {
				$code .= MS_Helper_Html::html_element( $field, true );
			}
		} else {
			$button = array(
				'id'        => 'create_menu',
				'type'      => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value'     => __( 'Okay, create the menu', 'memberdash' ),
				'ajax_data' => array(
					'action'   => MS_Controller_Pages::AJAX_ACTION_CREATE_MENU,
					'_wpnonce' => wp_create_nonce( MS_Controller_Pages::AJAX_ACTION_CREATE_MENU ),
				),
			);
			$code   = sprintf(
				'<div style="padding-left:10px"><p><em>%s</em></p><p>%s</p></div>',
				__( 'Wait! You did not create a menu yet...<br>Let us create it now, so you can choose which pages to display to your visitors!', 'memberdash' ),
				MS_Helper_Html::html_element( $button, true )
			);
		}

		return '<div class="ms-nav-controls">' . $code . '</div>';
	}

	/**
	 * Returns the height needed to display this dialog inside a popup without
	 * adding scrollbars
	 *
	 * @since 1.0.0
	 * @return int Popup height
	 */
	public function dialog_height() {
		switch ( $this->form_type ) {
			case 'short':
				$height = 200;
				break;

			case 'full':
			default:
				if ( MS_Model_Pages::can_edit_menus() ) {
					$height = 412;
				} else {
					$height = 460;
				}
				break;
		}

		return $height;
	}

}
