<?php
/**
 * View.
 *
 * @package MemberDash
 */

/**
 * Renders Help and Documentation Page.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 *
 * @return object
 */
class MS_View_Help extends MS_View {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @since 1.0.0
	 *
	 * @return object
	 */
	public function to_html() {
		$this->check_simulation();

		// Setup navigation tabs.
		$tabs = $this->data['tabs'];

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Help and documentation', 'memberdash' ),
				)
			);
			?>

			<div class="lg:ms-grid lg:ms-grid-cols-12 lg:ms-gap-x-5">
				<?php
				$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );

				// Call the appropriate form to render.
				$callback_name   = 'render_tab_' . str_replace( '-', '_', $active_tab );
				$render_callback = apply_filters(
					'ms_view_help_render_callback',
					array( $this, $callback_name ),
					$active_tab,
					$this->data
				);
				?>
				<div class="ms-space-y-6 lg:ms-col-span-9 ms-settings ms-help-content">
					<?php
					$html = call_user_func( $render_callback );
					$html = apply_filters( 'ms_view_help_' . $callback_name, $html );
					echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the General help contents
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function render_tab_general() {
		ob_start();
		?>
		<h2 class="ms-mt-0">
			<?php echo wp_kses_post( __( 'You\'re awesome :)', 'memberdash' ) ); ?><br />
		</h2>
		<p>
			<em><?php echo wp_kses_post( __( 'Thank you for using MemberDash!', 'memberdash' ) ); ?></em>
			<br/ ><br />
			<?php echo wp_kses_post( _x( 'Here is a quick overview:', 'help', 'memberdash' ) ); ?>
		</p>
		<div>
		<?php
		echo wp_kses_post(
			sprintf(
			// translators: placeholder: plugin version - help
				_x( 'You use version <strong>%s</strong> of MemberDash', 'placeholder: plugin version - help', 'memberdash' ),
				esc_attr( MEMBERDASH_VERSION )
			)
		);
		if ( is_multisite() ) {
			if ( MS_Plugin::is_network_wide() ) {
				echo wp_kses_post(
					sprintf(
						'<br />' .
						// translators: placeholder: icon tag - help
						_x( 'Your Protection mode is <strong>%s network-wide</strong>.', 'placeholder: icon tag - help', 'memberdash' ),
						'<i class="memberdash-fa memberdash-fa-globe"></i>'
					)
				);
			} else {
				echo wp_kses_post(
					sprintf(
						'<br />' .
						// translators: placeholder: icon tag - help
						_x( 'Your Protection covers <strong>%s only this site</strong>.', 'placeholder: icon tag - help', 'memberdash' ),
						'<i class="memberdash-fa memberdash-fa-home"></i>'
					)
				);
			}
		}
		$admin_cap = MS_Plugin::instance()->controller->capability;
		if ( $admin_cap ) {
			echo wp_kses_post(
				sprintf(
					'<br />' .
					// translators: placeholder: admin cap name - help
					_x( 'All users with capability <strong>%s</strong> are MWPS Admin-users.', 'placeholder: admin cap name - help', 'memberdash' ),
					$admin_cap
				)
			);
		} else {
			echo wp_kses_post(
				sprintf(
					'<br />' .
					_x( 'Only the <strong>Network-Admin</strong> can manage MWPS.', 'help', 'memberdash' )
				)
			);
		}
		if ( defined( 'MS_STOP_EMAILS' ) && MS_STOP_EMAILS ) {
			echo wp_kses_post(
				sprintf(
					'<br />' .
					_x( 'Currently MWPS is configured to <strong>not send</strong> any emails.', 'help', 'memberdash' )
				)
			);
		}
		if ( defined( 'MS_LOCK_SUBSCRIPTIONS' ) && MS_LOCK_SUBSCRIPTIONS ) {
			echo wp_kses_post(
				sprintf(
					'<br />' .
					_x( 'Currently MWPS is configured <strong>not expire/change</strong> any subscription status.', 'help', 'memberdash' )
				)
			);
		}
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			echo wp_kses_post(
				sprintf(
					'<br />' .
					_x( 'Warning: DISABLE_WP_CRON is <strong>enabled</strong> on this site! MWPS will not send all emails or change subscription status when expire date is reached!', 'help', 'memberdash' )
				)
			);
		}
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			echo wp_kses_post(
				sprintf(
					'<br />' .
					_x( 'WP_DEBUG is <strong>enabled</strong> on this site.', 'help', 'memberdash' )
				)
			);
		} else {
			echo wp_kses_post(
				sprintf(
					'<br />' .
					_x( 'WP_DEBUG is <strong>disabled</strong> on this site.', 'help', 'memberdash' )
				)
			);
		}
		?>
		</div>
		<?php MS_Helper_Html::html_separator(); ?>
		<h2>
			<?php echo wp_kses_post( _x( 'Plugin menu', 'help', 'memberdash' ) ); ?>
		</h2>
		<table cellspacing="0" cellpadding="4" border="0" width="100%">
			<tr>
				<td>
					<span class="top-menu">
					<div class="menu-image dashicons dashicons-lock"></div>
					<?php echo wp_kses_post( __( 'MemberDash', 'memberdash' ) ); ?>
					</span>
				</td>
				<td></td>
			</tr>
			<tr class="alternate">
				<td><span><?php echo wp_kses_post( __( 'Memberships', 'memberdash' ) ); ?></span></td>
				<td><?php echo wp_kses_post( _x( 'Create and manage Membership plans that users can sign up for', 'help', 'memberdash' ) ); ?></td>
			</tr>
			<tr>
				<td><span><?php echo wp_kses_post( __( 'Protection Rules', 'memberdash' ) ); ?></span></td>
				<td><?php echo wp_kses_post( _x( 'Set the protection options, i.e. which pages are protected by which membership', 'help', 'memberdash' ) ); ?></td>
			</tr>
			<tr class="alternate">
				<td><span><?php echo wp_kses_post( __( 'All Members', 'memberdash' ) ); ?></span></td>
				<td><?php echo wp_kses_post( _x( 'Lists all your WordPress users and allows you to manage their Memberships', 'help', 'memberdash' ) ); ?></td>
			</tr>
			<tr>
				<td><span><?php echo wp_kses_post( __( 'Add Member', 'memberdash' ) ); ?></span></td>
				<td><?php echo wp_kses_post( _x( 'Create a new WP User or edit subscriptions of an existing user', 'help', 'memberdash' ) ); ?></td>
			</tr>
			<tr class="alternate">
				<td><span><?php echo wp_kses_post( __( 'Billing', 'memberdash' ) ); ?></span></td>
				<td><?php echo wp_kses_post( _x( 'Manage sent invoices, including details such as the payment status. <em>Only visible when you have at least one paid membership</em>', 'help', 'memberdash' ) ); ?></td>
			</tr>
			<tr>
				<td><span><?php echo wp_kses_post( __( 'Coupons', 'memberdash' ) ); ?></span></td>
				<td><?php echo wp_kses_post( _x( 'Manage your discount coupons. <em>Requires Add-on "Coupons"</em>', 'help', 'memberdash' ) ); ?></td>
			</tr>
			<tr class="alternate">
				<td><span><?php echo wp_kses_post( __( 'Invitation Codes', 'memberdash' ) ); ?></span></td>
				<td><?php echo wp_kses_post( _x( 'Manage your invitation codes. <em>Requires Add-on "Invitation Codes"</em>', 'help', 'memberdash' ) ); ?></td>
			</tr>
			<tr>
				<td><span><?php echo wp_kses_post( __( 'Add-ons', 'memberdash' ) ); ?></span></td>
				<td><?php echo wp_kses_post( _x( 'Activate Add-ons', 'help', 'memberdash' ) ); ?></td>
			</tr>
			<tr class="alternate">
				<td><span><?php echo wp_kses_post( __( 'Settings', 'memberdash' ) ); ?></span></td>
				<td><?php echo wp_kses_post( _x( 'Global plugin options, such as Membership pages, payment options and email templates', 'help', 'memberdash' ) ); ?></td>
			</tr>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the Shortcode help contents
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function render_tab_shortcodes() {
		ob_start();
		?>

		<?php
		/*********
		 * *********   ms-protect-content   **************************************
		 *********/
		?>
		<h2 class="ms-mt-0"><?php echo wp_kses_post( _x( 'Common shortcodes', 'help', 'memberdash' ) ); ?></h2>

		<div id="ms-protect-content" class="ms-help-box">
			<h3><span class="ms-code">[ms-protect-content]</span></h3>

			<?php echo wp_kses_post( _x( 'Wrap this around any content to protect it for/from certain members (based on their Membership level)', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">id</span>
						<?php echo wp_kses_post( _x( '(ID list)', 'help', 'memberdash' ) ); ?>
						<strong><?php echo wp_kses_post( _x( 'Required', 'help', 'memberdash' ) ); ?></strong>.
						<?php echo wp_kses_post( _x( 'One or more membership IDs. Shortcode is triggered when the user belongs to at least one of these memberships', 'help', 'memberdash' ) ); ?>
					</li>
					<li>
						<span class="ms-code">access</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Defines if members of the memberships can see or not see the content', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">silent</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Silent protection removes content without displaying any message to the user', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							no
						</span>
					</li>
					<li>
						<span class="ms-code">msg</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Provide a custom protection message. <em>This will only be displayed when silent is not true</em>', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p>
					<span class="ms-code">[ms-protect-content id="1"]</span>
					<?php echo wp_kses_post( _x( 'Only members of membership-1 can see this!', 'help', 'memberdash' ) ); ?>
					<span class="ms-code">[/ms-protect-content]</span>
				</p>
				<p>
					<span class="ms-code">[ms-protect-content id="2,3" access="no" silent="yes"]</span>
					<?php echo wp_kses_post( _x( 'Everybody except members of memberships 2 or 3 can see this!', 'help', 'memberdash' ) ); ?>
					<span class="ms-code">[/ms-protect-content]</span>
				</p>
			</div>
		</div>


		<?php
		/*********
		 * *********   ms-user   *************************************************
		 *********/
		?>

		<div id="ms-user" class="ms-help-box">
			<h3><span class="ms-code">[ms-user]</span></h3>

			<?php echo wp_kses_post( _x( 'Shows the content only to certain users (ignoring the Membership level)', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">type</span>
						<?php echo wp_kses_post( _x( '(all|loggedin|guest|admin|non-admin)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Decide, which type of users will see the message', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"loggedin"
						</span>
					</li>
					<li>
						<span class="ms-code">msg</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Provide a custom protection message that is displayed to users that have no access to the content', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p>
					<span class="ms-code">[ms-user]</span>
					<?php echo wp_kses_post( _x( 'You are logged in', 'help', 'memberdash' ) ); ?>
					<span class="ms-code">[/ms-user]</span>
				</p>
				<p>
					<span class="ms-code">[ms-user type="guest"]</span>
					<?php printf( esc_html_x( '<a href="">Sign up now</a>! <a href="">Already have an account</a>?', 'help', 'memberdash' ) ); ?>
					<span class="ms-code">[/ms-user]</span>
				</p>
			</div>
		</div>


		<?php
		/*********
		 * *********   ms-membership-register-user   *****************************
		 *********/
		?>

		<div id="ms-membership-register-user" class="ms-help-box">
			<h3><span class="ms-code">[ms-membership-register-user]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays a registration form. Visitors can create a WordPress user account with this form', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">title</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Title of the register form', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Create an Account', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">first_name</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Initial value for first name', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">last_name</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Initial value for last name', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">username</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Initial value for username', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">email</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Initial value for email address', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">membership_id</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Membership ID to assign to the new user. This field is hidden and cannot be changed during registration. <em>Note: If this membership requires payment, the user will be redirected to the payment gateway after registration</em>', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">loginlink</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Display a login-link below the form', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"yes"
						</span>
					</li>
				</ul>

				<h4><?php echo wp_kses_post( __( 'Field labels', 'memberdash' ) ); ?></h4>
				<ul>
					<li>
						<span class="ms-code">label_first_name</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							"<?php echo wp_kses_post( __( 'First Name', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">label_last_name</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							"<?php echo wp_kses_post( __( 'Last Name', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">label_username</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							"<?php echo wp_kses_post( __( 'Choose a Username', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">label_email</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							"<?php echo wp_kses_post( __( 'Email Address', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">label_password</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							"<?php echo wp_kses_post( __( 'Password', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">label_password2</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							"<?php echo wp_kses_post( __( 'Confirm Password', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">label_register</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							"<?php echo wp_kses_post( __( 'Register My Account', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">hint_first_name</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Placeholder inside Field', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							""
						</span>
					</li>
					<li>
						<span class="ms-code">hint_last_name</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Placeholder inside Field', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							""
						</span>
					</li>
					<li>
						<span class="ms-code">hint_username</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Placeholder inside Field', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							""
						</span>
					</li>
					<li>
						<span class="ms-code">hint_email</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Placeholder inside Field', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							""
						</span>
					</li>
					<li>
						<span class="ms-code">hint_password</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Placeholder inside Field', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							""
						</span>
					</li>
					<li>
						<span class="ms-code">hint_password2</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Placeholder inside Field', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							""
						</span>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-membership-register-user]</span></p>
				<p><span class="ms-code">[ms-membership-register-user title="" hint_email="john@email.com" label_password2="Repeat"]</span></p>
			</div>
		</div>


		<?php
		/*********
		 * *********   ms-membership-signup   ************************************
		 *********/
		?>

		<div id="ms-membership-signup" class="ms-help-box">
			<h3><span class="ms-code">[ms-membership-signup]</span></h3>

			<?php echo wp_kses_post( _x( 'Shows a list of all memberships which the current user can sign up for', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php echo wp_kses_post( _x( 'Common options', 'help', 'memberdash' ) ); ?></h4>
				<ul>
					<li>
						<span class="ms-code"><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_SIGNUP ); ?>_text</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Button label', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Signup', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code"><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_MOVE ); ?>_text</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Button label', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Change', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code"><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL ); ?>_text</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Button label', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Cancel', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code"><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW ); ?>_text</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Button label', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Renew', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code"><?php echo esc_html( MS_Helper_Membership::MEMBERSHIP_ACTION_PAY ); ?>_text</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Button label', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Complete Payment', 'memberdash' ) ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-membership-signup]</span></p>
			</div>
		</div>



		<?php
		/*********
		 * *********   ms-membership-login   *************************************
		 *********/
		?>

		<div id="ms-membership-login" class="ms-help-box">
			<h3><span class="ms-code">[ms-membership-login]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays the login/lost-password form, or for logged in users a logout link', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php echo wp_kses_post( _x( 'Common options', 'help', 'memberdash' ) ); ?></h4>
				<ul>
					<li>
						<span class="ms-code">title</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'The title above the login form', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">show_labels</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Set to "yes" to display the labels for username and password in front of the input fields', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							no
						</span>
					</li>
					<li>
						<span class="ms-code">redirect_login</span>
						<?php echo wp_kses_post( _x( '(URL)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'The page to display after the user was logged in', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo esc_url( MS_Model_Pages::get_url_after_login() ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">redirect_logout</span>
						<?php echo wp_kses_post( _x( '(URL)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'The page to display after the user was logged out', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo esc_url( MS_Model_Pages::get_url_after_logout() ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">header</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">register</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">autofocus</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Focus the login-form on page load', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
				</ul>

				<h4><?php echo wp_kses_post( _x( 'More options', 'help', 'memberdash' ) ); ?></h4>
				<ul>
					<li>
						<span class="ms-code">holder</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"div"
						</span>
					</li>
					<li>
						<span class="ms-code">holderclass</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"ms-login-form"
						</span>
					</li>
					<li>
						<span class="ms-code">item</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">itemclass</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">prefix</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">postfix</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">wrapwith</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">wrapwithclass</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">form</span>
						<?php echo wp_kses_post( _x( '(login|lost|logout)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Defines which form should be displayed. An empty value allows the plugin to automatically choose between login/logout', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">nav_pos</span>
						<?php echo wp_kses_post( _x( '(top|bottom)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"top"
						</span>
					</li>
				</ul>

				<h4>
				<?php
				echo wp_kses_post(
					sprintf(
					// translators: placeholder: form name
						__( 'Options only for <span class="ms-code">%s</span>', 'memberdash' ),
						'form="login"'
					)
				);
				?>
				</h4>
				<ul>
					<li>
						<span class="ms-code">show_note</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Show a "You are not logged in" note above the login form', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">label_username</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Username', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">label_password</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Password', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">label_remember</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Remember Me', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">label_log_in</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Log In', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">id_login_form</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"loginform"
						</span>
					</li>
					<li>
						<span class="ms-code">id_username</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"user_login"
						</span>
					</li>
					<li>
						<span class="ms-code">id_password</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"user_pass"
						</span>
					</li>
					<li>
						<span class="ms-code">id_remember</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"rememberme"
						</span>
					</li>
					<li>
						<span class="ms-code">id_login</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"wp-submit"
						</span>
					</li>
					<li>
						<span class="ms-code">show_remember</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">value_username</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">value_remember</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Set this to "yes" to default the "Remember me" checkbox to checked', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							no
						</span>
					</li>
				</ul>

				<h4>
				<?php
				echo wp_kses_post(
					sprintf(
					// translators: placeholder: form name
						__( 'Options only for <span class="ms-code">%s</span>', 'memberdash' ),
						'form="lost"'
					)
				);
				?>
				</h4>
				<ul>
					<li>
						<span class="ms-code">label_lost_username</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Username or E-mail', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">label_lostpass</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Reset Password', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">id_lost_form</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"lostpasswordform"
						</span>
					</li>
					<li>
						<span class="ms-code">id_lost_username</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"user_login"
						</span>
					</li>
					<li>
						<span class="ms-code">id_lostpass</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"wp-submit"
						</span>
					</li>
					<li>
						<span class="ms-code">value_username</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-membership-login]</span></p>
				<p>
					<span class="ms-code">[ms-membership-login form="logout"]</span>
					<?php echo wp_kses_post( _x( 'is identical to', 'help', 'memberdash' ) ); ?>
					<span class="ms-code">[ms-membership-logout]</span>
				</p>
			</div>
		</div>


		<?php
		/*********
		 * *********   ms-note   *************************************************
		 *********/
		?>

		<div id="ms-note" class="ms-help-box">
			<h3><span class="ms-code">[ms-note]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays a info/success message to the user', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">type</span>
						(info|warning)
						<?php echo wp_kses_post( _x( 'The type of the notice. Info is green and warning red', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"info"
						</span>
					</li>
					<li>
						<span class="ms-code">class</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'An additional CSS class that should be added to the notice', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p>
					<span class="ms-code">[ms-note type="info"]</span>
					<?php echo wp_kses_post( _x( 'Thanks for joining our Premium Membership!', 'help', 'memberdash' ) ); ?>
					<span class="ms-code">[/ms-note]</span>
				</p>
				<p>
					<span class="ms-code">[ms-note type="warning"]</span>
					<?php echo wp_kses_post( _x( 'Please log in to access this page!', 'help', 'memberdash' ) ); ?>
					<span class="ms-code">[/ms-note]</span>
				</p>
			</div>
		</div>

		<?php
		/*********
		 * *********   ms-member-info   ******************************************
		 *********/
		?>

		<div id="ms-member-info" class="ms-help-box">
			<h3><span class="ms-code">[ms-member-info]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays details about the current member, like the members first name or a list of memberships he subscribed to', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">value</span>
						(email|firstname|lastname|fullname|memberships|custom)
						<?php echo wp_kses_post( _x( 'Defines which value to display.<br>A custom field can be set via the API (you find the API docs on the Advanced Settings tab)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"fullname"
						</span>
					</li>
					<li>
						<span class="ms-code">default</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Default value to display when the defined field is empty', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">before</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Display this text before the field value. Only used when the field is not empty', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"&lt;span&gt;"
						</span>
					</li>
					<li>
						<span class="ms-code">after</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Display this text after the field value. Only used when the field is not empty', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"&lt;/span&gt;"
						</span>
					</li>
					<li>
						<span class="ms-code">custom_field</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Only relevant for the value <span class="ms-code">custom</span>. This is the name of the custom field to get', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">list_separator</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Used when the field value is a list (i.e. Membership list or contents of a custom field)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							", "
						</span>
					</li>
					<li>
						<span class="ms-code">list_before</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Used when the field value is a list (i.e. Membership list or contents of a custom field)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">list_after</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Used when the field value is a list (i.e. Membership list or contents of a custom field)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							""
						</span>
					</li>
					<li>
						<span class="ms-code">user</span>
						<?php echo wp_kses_post( _x( '(User-ID)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Use this to display data of any user. If not specified then the current user is displayed', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							0
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p>
					<span class="ms-code">[ms-member-info value="fullname" default="(Guest)"]</span>
				</p>
				<p>
					<span class="ms-code">[ms-member-info value="memberships" default="Sign up now!" list_separator=" | " before="Your Memberships: "]</span>
				</p>
			</div>
		</div>

		<?php
		/**
		 * Allow Add-ons to add their own shortcode documentation.
		 *
		 * @since 1.0.0
		 */
		do_action( 'ms_view_help_shortcodes_common' );
		?>




		<h2><?php echo wp_kses_post( _x( 'Membership shortcodes', 'help', 'memberdash' ) ); ?></h2>


		<?php
		/*********
		 * *********   ms-membership-title   *************************************
		 *********/
		?>

		<div id="ms-membership-title" class="ms-help-box">
			<h3><span class="ms-code">[ms-membership-title]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays the name of a specific membership', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">id</span>
						<?php echo wp_kses_post( _x( '(Single ID)', 'help', 'memberdash' ) ); ?>
						<strong><?php echo wp_kses_post( _x( 'Required', 'help', 'memberdash' ) ); ?></strong>.
						<?php echo wp_kses_post( _x( 'The membership ID', 'help', 'memberdash' ) ); ?>
					</li>
					<li>
						<span class="ms-code">label</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Displayed in front of the title', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Membership title:', 'memberdash' ) ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-membership-title id="5" label=""]</span></p>
			</div>
		</div>


		<?php
		/*********
		 * *********   ms-membership-price   *************************************
		 *********/
		?>

		<div id="ms-membership-price" class="ms-help-box">
			<h3><span class="ms-code">[ms-membership-price]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays the price of a specific membership', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">id</span>
						<?php echo wp_kses_post( _x( '(Single ID)', 'help', 'memberdash' ) ); ?>
						<strong><?php echo wp_kses_post( _x( 'Required', 'help', 'memberdash' ) ); ?></strong>.
						<?php echo wp_kses_post( _x( 'The membership ID', 'help', 'memberdash' ) ); ?>
					</li>
					<li>
						<span class="ms-code">currency</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">label</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Displayed in front of the price', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Membership price:', 'memberdash' ) ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-membership-price id="5" currency="no" label="Only today:"]</span> $</p>
			</div>
		</div>


		<?php
		/*********
		 * *********   ms-membership-details   ***********************************
		 *********/
		?>

		<div id="ms-membership-details" class="ms-help-box">
			<h3><span class="ms-code">[ms-membership-details]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays the description of a specific membership', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">id</span>
						<?php echo wp_kses_post( _x( '(Single ID)', 'help', 'memberdash' ) ); ?>
						<strong><?php echo wp_kses_post( _x( 'Required', 'help', 'memberdash' ) ); ?></strong>.
						<?php echo wp_kses_post( _x( 'The membership ID', 'help', 'memberdash' ) ); ?>
					</li>
					<li>
						<span class="ms-code">label</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Displayed in front of the description', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Membership details:', 'memberdash' ) ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-membership-details id="5"]</span></p>
			</div>
		</div>


		<?php
		/*********
		 * *********   ms-membership-buy   *************************************
		 *********/
		?>

		<div id="ms-membership-buy" class="ms-help-box">
			<h3><span class="ms-code">[ms-membership-buy]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays a button to buy/sign-up for the specified membership', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">id</span>
						<?php echo wp_kses_post( _x( '(Single ID)', 'help', 'memberdash' ) ); ?>
						<strong><?php echo wp_kses_post( _x( 'Required', 'help', 'memberdash' ) ); ?></strong>.
						<?php echo wp_kses_post( _x( 'The membership ID', 'help', 'memberdash' ) ); ?>
					</li>
					<li>
						<span class="ms-code">label</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'The button label', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Signup', 'memberdash' ) ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-membership-buy id="5" label="Buy now!"]</span></p>
			</div>
		</div>

		<?php
		/**
		 * Allow Add-ons to add their own shortcode documentation.
		 *
		 * @since 1.0.0
		 */
		do_action( 'ms_view_help_shortcodes_membership' );
		?>



		<h2><?php echo wp_kses_post( _x( 'Less common shortcodes', 'help', 'memberdash' ) ); ?></h2>


		<?php
		/*********
		 * *********   ms-membership-logout   ************************************
		 *********/
		?>

		<div id="ms-membership-logout" class="ms-help-box">
			<h3><span class="ms-code">[ms-membership-logout]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays a logout link. When the user is not logged in then the shortcode will return an empty string', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php echo wp_kses_post( _x( 'Common options', 'help', 'memberdash' ) ); ?></h4>
				<ul>
					<li>
						<span class="ms-code">redirect</span>
						<?php echo wp_kses_post( _x( '(URL)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'The page to display after the user was logged out', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo esc_url( MS_Model_Pages::get_url_after_logout() ); ?>"
						</span>
					</li>
				</ul>

				<h4><?php echo wp_kses_post( _x( 'More options', 'help', 'memberdash' ) ); ?></h4>
				<ul>
					<li>
						<span class="ms-code">holder</span>
						<?php echo wp_kses_post( _x( 'Wrapper element (div, span, p)', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"div"
						</span>
					</li>
					<li>
						<span class="ms-code">holder_class</span>
						<?php echo wp_kses_post( _x( 'Class for the wrapper', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"ms-logout-form"
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-membership-logout]</span></p>
			</div>
		</div>


		<?php
		/*********
		 * *********   ms-membership-account-link   ******************************
		 *********/
		?>

		<div id="ms-membership-account-link" class="ms-help-box">
			<h3><span class="ms-code">[ms-membership-account-link]</span></h3>

			<?php echo wp_kses_post( _x( 'Inserts a simple link to the Account page', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">label</span>
						<?php echo wp_kses_post( _x( '(Text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'The contents of the link', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Visit your account page for more information', 'memberdash' ) ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p>
					<?php echo wp_kses_post( _x( 'Manage subscriptions in', 'help', 'memberdash' ) ); ?>
					<span class="ms-code">[ms-membership-account-link label="<?php echo wp_kses_post( _x( 'your Account', 'help', 'memberdash' ) ); ?>"]!</span>
				</p>
			</div>
		</div>


		<?php
		/*********
		 * *********   ms-protection-message   ***********************************
		 *********/
		?>

		<div id="ms-protection-message" class="ms-help-box">
			<h3><span class="ms-code">[ms-protection-message]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays the protection message on pages that the user cannot access. This shortcode should only be used on the Membership Page "Membership"', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li><em><?php echo wp_kses_post( _x( 'no arguments', 'help', 'memberdash' ) ); ?></em></li>
				</ul>

				<p>
					<?php echo wp_kses_post( _x( 'Tip: If the user is not logged in this shortcode will also display the default login form. <em>If you provide your own login form via the shortcode [ms-membership-login] then this shortcode will not add a second login form.</em>', 'help', 'memberdash' ) ); ?>
				</p>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-protection-message]</span></p>
			</div>
		</div>

		<?php
		/*********
		 * *********   ms-membership-account   ***********************************
		 *********/
		?>

		<div id="ms-membership-account" class="ms-help-box">
			<h3><span class="ms-code">[ms-membership-account]</span></h3>

			<?php echo wp_kses_post( _x( 'Displays the "My Account" page of the currently logged in user', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<h4><?php echo wp_kses_post( __( 'Membership section', 'memberdash' ) ); ?></h4>
				<ul>
					<li>
						<span class="ms-code">show_membership</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Whether to display the users current memberships', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">membership_title</span>
						<?php echo wp_kses_post( _x( '(text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Title of the current memberships section', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Your Membership', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">show_membership_change</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Display the link to subscribe to other memberships', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">membership_change_label</span>
						<?php echo wp_kses_post( _x( '(text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Title of the link', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Change', 'memberdash' ) ); ?>"
						</span>
					</li>
				</ul>

				<h4><?php echo wp_kses_post( __( 'Profile section', 'memberdash' ) ); ?></h4>
				<ul>
					<li>
						<span class="ms-code">show_profile</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Whether to display the users profile details', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">profile_title</span>
						<?php echo wp_kses_post( _x( '(text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Title of the user profile section', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Personal details', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">show_profile_change</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Display the link to edit the users profile', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">profile_change_label</span>
						<?php echo wp_kses_post( _x( '(text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Title of the link', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Edit', 'memberdash' ) ); ?>"
						</span>
					</li>
				</ul>

				<h4><?php echo wp_kses_post( __( 'Invoices section', 'memberdash' ) ); ?></h4>
				<ul>
					<li>
						<span class="ms-code">show_invoices</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Whether to display the section listing recent invoices', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">invoices_title</span>
						<?php echo wp_kses_post( _x( '(text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Title of the invoices section', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Invoices', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">limit_invoices</span>
						<?php echo wp_kses_post( _x( '(Number)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Number of invoices to display in the recent invoices list', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							10
						</span>
					</li>
					<li>
						<span class="ms-code">show_all_invoices</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Display the link to the complete list of users invoices', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">invoices_details_label</span>
						<?php echo wp_kses_post( _x( '(text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Title of the link', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'View all', 'memberdash' ) ); ?>"
						</span>
					</li>
				</ul>

				<h4><?php echo wp_kses_post( __( 'Activities section', 'memberdash' ) ); ?></h4>
				<ul>
					<li>
						<span class="ms-code">show_activity</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Whether to display the section containing the users recent activities', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">activity_title</span>
						<?php echo wp_kses_post( _x( '(text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Title of the activities section', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'Activities', 'memberdash' ) ); ?>"
						</span>
					</li>
					<li>
						<span class="ms-code">limit_activities</span>
						<?php echo wp_kses_post( _x( '(Number)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Number of items to display in the recent activities list', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							10
						</span>
					</li>
					<li>
						<span class="ms-code">show_all_activities</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Display the link to the complete list of users activities', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
					<li>
						<span class="ms-code">activity_details_label</span>
						<?php echo wp_kses_post( _x( '(text)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'Title of the link', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							"<?php echo wp_kses_post( __( 'View all', 'memberdash' ) ); ?>"
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-membership-account]</span></p>
				<p><span class="ms-code">[ms-membership-account show_profile_change="no" show_activity="no" limit_activities="3" activity_title="Last 3 activities"]</span></p>
			</div>
		</div>


		<?php
		/*********
		 * *********   ms-invoice   **********************************************
		 *********/
		?>

		<div id="ms-invoice" class="ms-help-box">
			<h3><span class="ms-code">[ms-invoice]</span></h3>

			<?php echo wp_kses_post( _x( 'Display an invoice to the user. Not very useful in most cases, as the invoice can only be viewed by the invoice recipient', 'help', 'memberdash' ) ); ?>
			<div class="ms-help-toggle"><?php echo wp_kses_post( _x( 'Expand', 'help', 'memberdash' ) ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<span class="ms-code">id</span>
						<?php echo wp_kses_post( _x( '(Single ID)', 'help', 'memberdash' ) ); ?>
						<strong><?php echo wp_kses_post( _x( 'Required', 'help', 'memberdash' ) ); ?></strong>.
						<?php echo wp_kses_post( _x( 'The Invoice ID', 'help', 'memberdash' ) ); ?>
					</li>
					<li>
						<span class="ms-code">pay_button</span>
						<?php echo wp_kses_post( _x( '(yes|no)', 'help', 'memberdash' ) ); ?>
						<?php echo wp_kses_post( _x( 'If the invoice should contain a "Pay" button', 'help', 'memberdash' ) ); ?>
						<span class="ms-help-default">
							<?php echo wp_kses_post( _x( 'Default:', 'help', 'memberdash' ) ); ?>
							yes
						</span>
					</li>
				</ul>

				<p><em><?php echo wp_kses_post( _x( 'Example:', 'help', 'memberdash' ) ); ?></em></p>
				<p><span class="ms-code">[ms-invoice id="123"]</span></p>
			</div>
		</div>

		<?php
		/**
		 * Allow Add-ons to add their own shortcode documentation.
		 *
		 * @since 1.0.0
		 */
		do_action( 'ms_view_help_shortcodes_other' );
		?>

		<?php
		$html = ob_get_clean();

		return apply_filters(
			'ms_view_help_shortcodes',
			$html
		);
	}

	/**
	 * Renders the Network-Wide Protection help contents
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function render_tab_network() {
		ob_start();
		?>
		<h2><?php echo wp_kses_post( _x( 'Network-Wide Protection', 'help', 'memberdash' ) ); ?></h2>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Enable Network-Wide mode', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'In wp-config.php add the line <span class="ms-code">define( "MS_PROTECT_NETWORK", true );</span> to enable network wide protection. Important: Settings for Network-Wide mode are stored differently than normal (site-wide) settings. After switching to network wide mode the first time you have to set up the plugin again.<br />Note: The plugin will automatically enable itself network wide, you only need to add the option above.', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Disable Network-Wide mode', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'Simply remove the line <span class="ms-code">define( "MS_PROTECT_NETWORK", true );</span> from wp-config.php to switch back to site-wide protection. All your previous Memberships will still be there (if you created site-wide memberships before enabling network-wide mode)<br />Note: After this change the plugin will still be enabled network wide, you have to go to Network Admin > Plugins and disable it if you only want to protect certain sites in your network.', 'help', 'memberdash' ) ); ?>
		</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the Advanced settings help contents
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function render_tab_advanced() {
		ob_start();
		?>
		<h2 class="ms-mt-0"><?php echo wp_kses_post( _x( 'Advanced Settings', 'help', 'memberdash' ) ); ?></h2>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Reset', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'Open the Settings page and add <span class="ms-code">&reset=1</span> to the URL. A prompt is displayed that can be used to reset all Membership settings. Use this to clean all traces after testing the plugin.', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Fix subscriptions', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'Open the Settings page and add <span class="ms-code">&fixsub=1</span> to the URL. A prompt is displayed that can be used to fix Membership subscriptions. Use this to fix subscriptions that are out of sync with Stripe.', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Stop Emails', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'In wp-config.php add the line <span class="ms-code">define( "MS_STOP_EMAILS", true );</span> to force Protected Content to <em>not</em> send any emails to Members. This can be used when testing to prevent your users from getting email notifications.', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Reduce Emails', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'By default your members will get an email for every event that is handled (see the "Settings > Automated Email Responses" section). However, you can reduce the emails sent to your users by adding the following line to your wp-config.php <span class="ms-code">define( "MS_DUPLICATE_EMAIL_HOURS", 24 );</span>. This will prevent the same email being sent more than once every 24 hours.', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Lock Subscription Status', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'In wp-config.php add the line <span class="ms-code">define( "MS_LOCK_SUBSCRIPTIONS", true );</span> to disable automatic status-checks of subscriptions. Registration is still possible, but after this the Subscription status will not change anymore. Effectively Subscriptions will not expire anymore.', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'No Admin Shortcode Preview', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'By default the user will see additional information on the page when using the shortcode <span class="ms-code">[ms-protect-content]</span>. To disable this additional output add the line <span class="ms-code">define( "MS_NO_SHORTCODE_PREVIEW", true );</span> in wp-config.php.', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Define Membership Admin users', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'By default all users with capability <span class="ms-code">manage_options</span> are considered Membership admin users and have unlimited access to the whole site (including protected content). To change the required capability add the line <span class="ms-code">define( "MS_ADMIN_CAPABILITY", "manage_options" );</span> in wp-config.php. When you set the value to <span class="ms-code">false</span> then only the Superadmin has full access to the site.', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Debugging incorrect page access', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'MWPS has a small debugging tool built into it, that allows you to analyze access issues for the current user. To use this tool you have to set <span class="ms-code">define( "WP_DEBUG", true );</span> on your site. Next open the page that you want to analyze and add <span class="ms-code">?explain=access</span> to the page URL. As a result you will not see the normal page contents but a lot of useful details on the access permissions.', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Keep a log of all outgoing emails', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'If you want to keep track of all the emails that MWPS sends to your members then add the line <span class="ms-code">define( "MS_LOG_EMAILS", true );</span> to your wp-config.php. A new navigation link will be displayed here in the Help page to review the email history.', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Disable default email on registration', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( 'To disable WP default email on registration from back end, use <span class="ms-code">define( "MS_DISABLE_WP_NEW_USER_NOTIFICATION", true );</span> in wp-config.php file.', 'help', 'memberdash' ) ); ?>
		</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders the Customize Membership help contents
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function render_tab_branding() {
		ob_start();
		?>
		<h2 class="ms-mt-0"><?php echo wp_kses_post( _x( 'Template Hierarchy', 'help', 'memberdash' ) ); ?></h2>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
				// translators: placeholder: 'a' tags with links
					_x( 'By default Membership will render the page contents defined in your %1$sMembership Pages%2$s using the themes standard template for single pages. However, you can customize this very easy by creating special %3$stemplate files%4$s in the theme.', 'help', 'memberdash' ), // cspell:disable-line.
					'<a href="' . esc_url( MS_Controller_Plugin::get_admin_url( 'settings' ) ) . '">',
					'</a>',
					'<a href="https://developer.wordpress.org/themes/basics/template-files/" target="_blank">',
					'</a>'
				)
			);
			?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Account Page', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( '1. <tt>mwps-account.php</tt>', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '2. Default single-page template', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Membership List Page', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( '1. <tt>mwps-memberships-100.php</tt> (Not the list, only checkout for Membership 100)', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '2. <tt>mwps-memberships.php</tt>', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '3. Default single-page template', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Registration Page', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( '1. <tt>mwps-register-100.php</tt> (Not the list, only checkout for Membership 100)', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '2. <tt>mwps-register.php</tt>', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '3. Default single-page template', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Thank-You Page', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( '1. <tt>mwps-registration-complete-100.php</tt> (After subscribing to Membership 100)', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '2. <tt>mwps-registration-complete.php</tt>', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '3. Default single-page template', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Protected Content Page', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( '1. <tt>mwps-protected-content-100.php</tt> (Page is protected by Membership 100)', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '2. <tt>mwps-protected-content.php</tt>', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '3. Default single-page template', 'help', 'memberdash' ) ); ?>
		</p>
		<p>
			<strong><?php echo wp_kses_post( _x( 'Invoice Layout', 'help', 'memberdash' ) ); ?></strong><br />
			<?php echo wp_kses_post( _x( '1. <tt>mwps-invoice-100.php</tt> (Used by all invoices for Membership 100)', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '2. <tt>mwps-invoice.php</tt>', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '3. <tt>single-ms-invoice.php</tt>', 'help', 'memberdash' ) ); ?><br />
			<?php echo wp_kses_post( _x( '4. Default invoice template by Membership', 'help', 'memberdash' ) ); ?>
		</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the email history list.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function render_tab_emails() {
		$listview = MS_Factory::create( 'MS_Helper_ListTable_CommunicationLog' );
		$listview->prepare_items();

		ob_start();
		?>
		<div class="wrap ms-wrap ms-communicationlog">
			<?php
			$listview->views();
			?>
			<form action="" method="post">
				<?php $listview->display(); ?>
			</form>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}


}
