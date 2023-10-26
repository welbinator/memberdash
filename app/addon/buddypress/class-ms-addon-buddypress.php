<?php
/**
 * BuddyPress Add-on.
 *
 * @since 1.0.0
 *
 * @package Memberdash
 */

/**
 * BuddyPress Add-on.
 *
 * @since 1.0.0
 */
class MS_Addon_BuddyPress extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.0.0
	 */
	const ID = 'buddypress';

	/**
	 * The flag to determine if we want to use the BuddyPress registration page
	 * or default MWPS registration page.
	 *
	 * @since 1.0.0
	 * @var  bool
	 */
	protected $buddypress_registration = true;

		/**
		 * The flag to determine if we want to show the BuddyPress xprofile fields
		 * MWPS Account page.
		 *
		 * @since 1.0.0
		 * @var  bool
		 */
	protected $buddypress_xprofile = false;

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_active() {
		if ( ! self::buddypress_active( true )
			&& MS_Model_Addon::is_enabled( self::ID )
		) {
			$model = MS_Factory::load( 'MS_Model_Addon' );
			$model->disable( self::ID );
		}

		return MS_Model_Addon::is_enabled( self::ID );
	}

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
	 * Initializes the Add-on. Always executed.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		if ( self::buddypress_active() ) {
			$this->collision_check();
		}

		if ( self::is_active() ) {
			$this->buddypress_registration = mslib3()->is_true(
				$this->get_setting( 'buddypress_registration' )
			);

			$this->buddypress_xprofile = mslib3()->is_true(
				$this->get_setting( 'buddypress_xprofile' )
			);

			// Export xprofile fields.
			$this->add_filter(
				'ms_export/export_member',
				'export_xprofile_fields',
				10,
				3
			);

			// Import xprofile fields.
			$this->add_action(
				'ms_import_member_imported',
				'import_xprofile_fields',
				10,
				2
			);

			// Import xprofile fields.
			$this->add_action(
				'ms_import_member_imported',
				'import_xprofile_fields',
				10,
				2
			);

			// Only show protection in actual BP site.
			if ( self::buddypress_active() ) {
				$this->add_filter(
					'ms_controller_protection_tabs',
					'rule_tabs'
				);

				MS_Factory::load( 'MS_Addon_BuddyPress_Rule' );
			}

			/*
			 * Using the BuddyPress registration form is optional.
			 * These actions are only needed when the BuddyPress registration
			 * form is used instead of the MWPS registration form.
			 */
			if ( $this->buddypress_registration ) {
				$this->add_filter(
					'ms_frontend_custom_registration_form',
					'registration_form'
				);

				$this->add_action(
					'ms_controller_frontend_register_user_before',
					'prepare_create_user'
				);

				$this->add_action(
					'ms_controller_frontend_register_user_complete',
					'save_custom_fields'
				);

				$this->add_action(
					'wp',
					'bp_mwps_process_signup_errors'
				);

				$this->add_filter(
					'ms_model_membership_create_new_user_validation_errors',
					'check_bp_xprofile_validation',
					10,
					1
				);

			}

			if ( $this->buddypress_xprofile ) {
				$this->add_action(
					'ms_view_account_profile_before_card',
					'bp_xprofile_into_account_page',
					10,
					2
				);
			}

			// Disable BuddyPress Email activation.
			if ( $this->buddypress_registration ) {
				add_filter(
					'bp_core_signup_send_activation_key',
					'__return_false'
				);

				add_filter(
					'bp_registration_needs_activation',
					'__return_false'
				);

				$this->add_action(
					'bp_core_signup_user',
					'disable_validation'
				);
			}
		} else {
			$this->buddypress_registration = false;
		}
	}

	/**
	 * Checks, if some BuddyPress pages overlap with MWPS membership pages.
	 *
	 * In some cases people used the same page-ID for both BuddyPress
	 * registration and MWPS registration. This will cause problems and must be
	 * resolved to have MWPS and BuddyPress work symbiotically.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function collision_check(): void {
		$buddy_pages = MS_Factory::get_option( 'bp-pages' );

		if ( ! is_array( $buddy_pages ) ) {
			// Okay, no BuddyPress pages set up yet.
			return;
		}

		$duplicates = array();
		foreach ( $buddy_pages as $type => $page_id ) {
			$collision = MS_Model_Pages::get_page_by( 'id', $page_id );
			if ( $collision ) {
				$title = $collision->post_title;
				if ( ! $title ) {
					$title = $collision->post_name;
				}

				$duplicates[] = sprintf( '%s - %s', $page_id, $title );
			}
		}

		if ( count( $duplicates ) ) {
			$msg = sprintf(
				'%s<br><br>%s',
				sprintf(
					__( 'BuddyPress uses a page that is also used as a Membership page by Membership.<br>Please assign a different page for either %1$sMembership%2$s or %3$sBuddyPress%4$s to avoid conflicts.', 'memberdash' ),
					'<a href="' . MS_Controller_Plugin::get_admin_url( 'settings' ) . '">',
					'</a>',
					'<a href="' . admin_url( 'admin.php?page=bp-page-settings' ) . '">',
					'</a>'
				),
				implode( '<br>', $duplicates )
			);
			mslib3()->ui->admin_message( $msg, 'error' );
		}
	}

	/**
	 * Registers the Add-On
	 *
	 * @since 1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name'        => __( 'BuddyPress Integration', 'memberdash' ),
			'description' => __( 'Integrate BuddyPress with Membership.', 'memberdash' ),
			'icon'        => 'dashicons dashicons-groups',
			'details'     => array(
				array(
					'type'  => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => __( 'Protection Rules', 'memberdash' ),
					'desc'  => __( 'Adds BuddyPress rules in the "Protection Rules" page.', 'memberdash' ),
				),
				array(
					'id'        => 'buddypress_registration',
					'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
					'title'     => __( 'Use BuddyPress Registration', 'memberdash' ),
					'desc'      =>
						__( 'Enable this option to use the BuddyPress registration page instead of the Membership registration page.', 'memberdash' ) .
						'<br />' .
						__( 'New users are automatically activated by Membership and no confirmation email is sent to the user!', 'memberdash' ),
					'value'     => $this->buddypress_registration,
					'ajax_data' => array(
						'action' => $this->ajax_action(),
						'field'  => 'buddypress_registration',
					),
				),
				array(
					'id'        => 'buddypress_xprofile',
					'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
					'title'     => __( 'Show xProfile Fields', 'memberdash' ),
					'desc'      =>
						__( 'Show BuddyPress xprofile fields in membership account page', 'memberdash' ),
					'value'     => $this->buddypress_xprofile,
					'ajax_data' => array(
						'action' => $this->ajax_action(),
						'field'  => 'buddypress_xprofile',
					),
				),
			),
		);

		if ( ! self::buddypress_active( true ) ) {
			$list[ self::ID ]->description .= sprintf(
				'<br /><b>%s</b>',
				__( 'Activate BuddyPress to use this Add-on', 'memberdash' )
			);
			$list[ self::ID ]->action       = '-';
		}

		return $list;
	}

	/**
	 * Returns true, when the BuddyPress plugin is activated.
	 *
	 * @param bool $subsite_check Should check if BP is installed in a subsite.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function buddypress_active( $subsite_check = false ) {
		// If BuddyPress is installed within a sub site and network protect mode is enabled.
		if ( $subsite_check && defined( 'MS_PROTECT_NETWORK' ) && MS_PROTECT_NETWORK && defined( 'BP_ROOT_BLOG' ) ) {
			// Switch to the BP site.
			switch_to_blog( BP_ROOT_BLOG );
			// Check if plugin is active.
			$active = is_plugin_active( 'buddypress/bp-loader.php' );
			// Restore to old blog.
			restore_current_blog();

			return $active;
		}

		global $bp;

		return ( ! empty( $bp ) && function_exists( 'bp_buffer_template_part' ) && function_exists( 'buddypress' ) );
	}

	/**
	 * Add buddypress rule tabs in membership level edit.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param array $tabs The current tabs.
	 * @param int   $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function rule_tabs( $tabs ) {
		$rule          = MS_Addon_Buddypress_Rule::RULE_ID;
		$tabs[ $rule ] = true;

		return $tabs;
	}

	/**
	 * Show BP xprofile field value in MWPS account page
	 *
	 * @since 1.0.3
	 *
	 * @param object $member Current Member Account
	 * @param object $account Account object
	 */
	public function bp_xprofile_into_account_page( $member, $account ) {

		ob_start();

		$profile_groups    = BP_XProfile_Group::get( array( 'fetch_fields' => true ) );
		$profile_groups    = mslib3()->array->get( $profile_groups );
		$disallowed_fields = array();
		$disallowed_fields = apply_filters(
			'ms_bp_profile_fields_account_disallowed_fields',
			$disallowed_fields
		);

		?>
		<div id="mwps-bp-profile">
			<h2><?php esc_html_e( 'Extra Profile Information', 'memberdash' ); ?></h2>
			<table>
				<?php foreach ( $profile_groups as $profile_group ) { ?>
					<?php
					$fields = mslib3()->array->get( $profile_group->fields );
					foreach ( $fields as $field ) {
						if ( in_array( $field->name, $disallowed_fields ) ) {
							continue;
						}
						?>
					<tr>
						<th class="ms-label-title"><?php echo esc_html( $field->name ); ?> : </th>
						<td class="ms-label-field">
							<?php
								$user_data = bp_get_profile_field_data(
									array(
										'user_id' => get_current_user_id(),
										'field'   => $field->name,
									)
								);
							if ( is_array( $user_data ) ) {
								echo implode( ', ', $user_data ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								echo $user_data; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</td>
					</tr>
					<?php } ?>
				<?php } ?>
			</table>
		</div>
		<?php

		$output = ob_get_contents();
		ob_end_clean();

		$output = apply_filters(
			'ms_bp_profile_fields_account',
			$output,
			$profile_groups,
			$disallowed_fields
		);

		echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Display the BuddyPress registration form instead of the default
	 * Membership registration form.
	 *
	 * @since 1.0.0
	 * @return string HTML code of the registration form or empty string to use
	 *                the default form.
	 */
	public function registration_form( $code ) {
		global $bp;

		if ( self::buddypress_active() ) {
			// Add Membership fields to the form so we know what comes next.
			$this->add_action( 'bp_custom_signup_steps', 'membership_fields' );

			// Redirect everything after the submit button to output buffer...
			$this->add_action(
				'bp_after_registration_submit_buttons',
				'catch_nonce_field',
				9999
			);

			// Tell BuddyPress that we want the registration form.
			$bp->signup->step = 'request-details';

			// Get the BuddyPress registration page.
			$code = bp_buffer_template_part( 'members/register', null, false );

			// Don't add <p> tags, the form is already formatted!
			remove_filter( 'the_content', 'wpautop' );
		}

		return $code;
	}

	/**
	 * Check buddypress xprofile field validation
	 * when using buddypress registration form on signup
	 *
	 * @param object $validation_errors Validation error object.
	 *
	 * @since 1.0.0
	 *
	 * @return object Validation error object
	 */
	public function check_bp_xprofile_validation( $validation_errors ) {
		$bp = buddypress();

		// Make sure hidden field is passed and populated.
		if ( isset( $_POST['signup_profile_field_ids'] ) && ! empty( $_POST['signup_profile_field_ids'] ) ) {

			// Let's compact any profile field info into an array.
			$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

			// Loop through the posted fields formatting any date box values then validate the field.
			foreach ( (array) $profile_field_ids as $field_id ) {

				// Create errors for required fields without values.
				if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST[ 'field_' . $field_id ] ) && ! bp_current_user_can( 'bp_moderate' ) ) {
					$validation_errors->add(
						'xprofile_' . $field_id,
						__( 'You have missed a required fields.', 'memberdash' )
					);
				}
			}
		}

		return $validation_errors;
	}

	/**
	 * Check the registration form error
	 * when using buddypress registration form on signup
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function bp_mwps_process_signup_errors() {
		if ( is_user_logged_in() ) {
			return;
		}

		if ( ! isset( $_POST['signup_username'] ) ) {
			return;
		}

		if ( bp_is_current_component( 'register' ) ) {
			return;
		}

		$bp = buddypress();

		do_action( 'bp_signup_pre_validate' );
		$account_details = bp_core_validate_user_signup( $_POST['signup_username'], $_POST['signup_email'] );

		if ( ! empty( $account_details['errors']->errors['user_name'] ) ) {
			$bp->signup->errors['signup_username'] = $account_details['errors']->errors['user_name'][0];
		}

		if ( ! empty( $account_details['errors']->errors['user_email'] ) ) {
			$bp->signup->errors['signup_email'] = $account_details['errors']->errors['user_email'][0];
		}

		// Check that both password fields are filled in.
		if ( empty( $_POST['signup_password'] ) || empty( $_POST['signup_password_confirm'] ) ) {
			$bp->signup->errors['signup_password'] = __( 'Please make sure you enter your password twice', 'memberdash' );
		}

		// Check that the passwords match.
		if ( ( ! empty( $_POST['signup_password'] ) && ! empty( $_POST['signup_password_confirm'] ) ) && $_POST['signup_password'] != $_POST['signup_password_confirm'] ) {
			$bp->signup->errors['signup_password'] = __( 'The passwords you entered do not match.', 'memberdash' );
		}

		$bp->signup->username = $_POST['signup_username'];
		$bp->signup->email    = $_POST['signup_email'];

		// Now we've checked account details, we can check profile information.
		if ( bp_is_active( 'xprofile' ) ) {
			$this->_check_xprofile_fields();
		}

		// Finally, let's check the blog details, if the user wants a blog and blog creation is enabled.
		if ( isset( $_POST['signup_with_blog'] ) ) {
			$this->_check_blog_fields();
		}

		do_action( 'bp_signup_validate' );

		$this->_create_action_error_cb();

	}

	/**
	 * Check the xprofile fields validation
	 * when using buddypress registration form on signup
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function _check_xprofile_fields() {
		$bp = buddypress();

		// Make sure hidden field is passed and populated.
		if ( isset( $_POST['signup_profile_field_ids'] ) && ! empty( $_POST['signup_profile_field_ids'] ) ) {

			// Let's compact any profile field info into an array.
			$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

			// Loop through the posted fields formatting any date box values then validate the field.
			foreach ( (array) $profile_field_ids as $field_id ) {
				if ( ! isset( $_POST[ 'field_' . $field_id ] ) ) {
					if ( ! empty( $_POST[ 'field_' . $field_id . '_day' ] ) && ! empty( $_POST[ 'field_' . $field_id . '_month' ] ) && ! empty( $_POST[ 'field_' . $field_id . '_year' ] ) ) {
						$_POST[ 'field_' . $field_id ] = ( new Datetime( $_POST[ 'field_' . $field_id . '_day' ] . $_POST[ 'field_' . $field_id . '_month' ] . $_POST[ 'field_' . $field_id . '_year' ], wp_timezone() ) )->format( 'Y-m-d H:i:s' );
					}
				}

				// Create errors for required fields without values.
				if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST[ 'field_' . $field_id ] ) && ! bp_current_user_can( 'bp_moderate' ) ) {
					$bp->signup->errors[ 'field_' . $field_id ] = __( 'This is a required field', 'memberdash' );
				}
			}

			// This situation doesn't naturally occur so bounce to website root.
		} else {
			bp_core_redirect( bp_get_root_domain() );
		}
	}

	/**
	 * Check blog fields validation
	 * when using buddypress registration form on signup
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function _check_blog_fields() {
		$bp = buddypress();

		$active_signup = bp_core_get_root_option( 'registration' );

		if ( 'blog' == $active_signup || 'all' == $active_signup ) {
			$blog_details = bp_core_validate_blog_signup( $_POST['signup_blog_url'], $_POST['signup_blog_title'] );

			// If there are errors with blog details, set them for display.
			if ( ! empty( $blog_details['errors']->errors['blogname'] ) ) {
				$bp->signup->errors['signup_blog_url'] = $blog_details['errors']->errors['blogname'][0];
			}

			if ( ! empty( $blog_details['errors']->errors['blog_title'] ) ) {
				$bp->signup->errors['signup_blog_title'] = $blog_details['errors']->errors['blog_title'][0];
			}
		}
	}

	/**
	 * Create bp fields error callback action
	 * when using buddypress registration form on signup
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function _create_action_error_cb() {
		$bp = buddypress();

		if ( ! empty( $bp->signup->errors ) ) {
			// There is error, so show errors using action hook
			foreach ( (array) $bp->signup->errors as $fieldname => $error_message ) {
				$this->add_action( 'bp_' . $fieldname . '_errors', '_bp_errors' );
			}
		}
	}

	public function _bp_errors( $error_message ) {
		echo wp_kses_post( apply_filters( 'bp_members_signup_error_message', '<div class="error">' . stripslashes( addslashes( $error_message ) ) . '</div>' ) );
	}


	/**
	 * Redirects all output to the Buffer, so we can easily discard it later...
	 *
	 * @since 1.0.0
	 */
	public function catch_nonce_field() {
		ob_start();
	}

	/**
	 * Output hidden form fields that are parsed by Membership when the
	 * registration was completed.
	 *
	 * This is used to recognize that the registration should be handled by
	 * Membership and which screen to display next.
	 *
	 * Note that the form is submitted to Membership, so we need to
	 * handle the background stuff. BuddyPress will not do it for us...
	 *
	 * @since 1.0.0
	 */
	public function membership_fields() {
		/*
		 * Discard the contents of the output buffer. It only contains the
		 * BuddyPress nonce fields.
		 */
		ob_end_clean();

		$field_membership = array(
			'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name'  => 'membership_id',
			'value' => $_REQUEST['membership_id'],
		);
		$field_action     = array(
			'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name'  => 'action',
			'value' => 'register_user',
		);
		$field_step       = array(
			'type'  => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name'  => 'step',
			'value' => MS_Controller_Frontend::STEP_REGISTER_SUBMIT,
		);

		MS_Helper_Html::html_element( $field_membership );
		MS_Helper_Html::html_element( $field_action );
		MS_Helper_Html::html_element( $field_step );
		wp_nonce_field( $field_action['value'] );
	}

	/**
	 * The Registration form was submitted and the nonce-check verified.
	 * We have to match the BuddyPress field-names with the
	 * Membership names.
	 *
	 * This preparation only ensures that the user can be created.
	 * XProfile fields are not handled here...
	 *
	 * @since 1.0.0
	 */
	public function prepare_create_user() {
		$_REQUEST['first_name'] = $_REQUEST['signup_username'];
		$_REQUEST['last_name']  = '';
		$_REQUEST['username']   = $_REQUEST['signup_username'];
		$_REQUEST['email']      = $_REQUEST['signup_email'];
		$_REQUEST['password']   = $_REQUEST['signup_password'];
		$_REQUEST['password2']  = $_REQUEST['signup_password_confirm'];
	}

	/**
	 * After the user was successfully created we now have the opportunity to
	 * save the XProfile fields.
	 *
	 * @see bp-xprofile-screens.php function xprofile_screen_edit_profile()
	 *
	 * @since 1.0.0
	 * @param  WP_User $user The new user.
	 */
	public function save_custom_fields( $user ) {
		if ( ! bp_is_active( 'xprofile' ) ) {
			return; }

		// Make sure hidden field is passed and populated
		if ( isset( $_POST['signup_profile_field_ids'] )
			&& ! empty( $_POST['signup_profile_field_ids'] )
		) {
			// Let's compact any profile field info into an array.
			$profile_field_ids = wp_parse_id_list( $_POST['signup_profile_field_ids'] );

			// Loop through the posted fields formatting any date box values then add to usermeta.
			foreach ( (array) $profile_field_ids as $field_id ) {
				$value      = '';
				$visibility = 'public';

				if ( ! isset( $_POST[ 'field_' . $field_id ] ) ) {
					// Build the value of date-fields.
					if ( ! empty( $_POST[ 'field_' . $field_id . '_day' ] )
						&& ! empty( $_POST[ 'field_' . $field_id . '_month' ] )
						&& ! empty( $_POST[ 'field_' . $field_id . '_year' ] )
					) {
						// Concatenate the values.
						$date_value =
							$_POST[ 'field_' . $field_id . '_day' ] . ' ' .
							$_POST[ 'field_' . $field_id . '_month' ] . ' ' .
							$_POST[ 'field_' . $field_id . '_year' ];

						// Turn the concatenated value into a timestamp.
						$_POST[ 'field_' . $field_id ] = ( new Datetime( $date_value, wp_timezone() ) )->format( 'Y-m-d H:i:s' );
					}
				}

				if ( ! empty( $_POST[ 'field_' . $field_id ] ) ) {
					$value = $_POST[ 'field_' . $field_id ];
				}

				if ( ! empty( $_POST[ 'field_' . $field_id . '_visibility' ] ) ) {
					$visibility = $_POST[ 'field_' . $field_id . '_visibility' ];
				}

				xprofile_set_field_visibility_level( $field_id, $user->id, $visibility );
				xprofile_set_field_data( $field_id, $user->id, $value, false );
			}
		}
	}

	/**
	 * Automatically confirms new registrations.
	 *
	 * @since 1.0.0
	 * @param  int $user_id The new User-ID
	 */
	public function disable_validation( $user_id ) {
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		$member->confirm();
	}

	/**
	 * Export member xprofile data if BuddyPress addon is enabled.
	 *
	 * Export all member BuddyPress XProfile fields using BP functions.
	 *
	 * @param array           $output          Output array.
	 * @param MS_Model_Member $member          Member object.
	 * @param object          $export_base_obj Export object.
	 *
	 * @since 1.0.0
	 *
	 * @return array Export data.
	 */
	public function export_xprofile_fields( $output, $member, $export_base_obj ) {
		// Get X Profile field values.
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'xprofile' ) ) {
			// Get profile field groups.
			$profile_groups = BP_XProfile_Group::get(
				array(
					'fetch_fields' => true,
					'user_id'      => $member->id,
				)
			);

			// Make sure it is array.
			$profile_groups = mslib3()->array->get( $profile_groups );

			// Loop through each fields.
			foreach ( $profile_groups as $profile_group ) {
				$fields = mslib3()->array->get( $profile_group->fields );
				// Loop through each fields in group.
				foreach ( $fields as $field ) {
					$output['xprofile'][] = array(
						'id'    => $field->id,
						'type'  => $field->type,
						'value' => xprofile_get_field_data( $field->id, $member->id ),
					);
				}
			}
		}

		/**
		 * Filter to add/edit fields to member export.
		 *
		 * @param array  $output          Output data.
		 * @param object $member          Member object.
		 * @param object $export_base_obj Export modal.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'ms_export_xprofile_fields', $output, $member, $export_base_obj );
	}

	/**
	 * Import member xprofile data if BuddyPress addon is enabled.
	 *
	 * Import all member BuddyPress XProfile fields using BP functions.
	 * NOTE: We will import the field values only if the same field exist
	 * in new site.
	 *
	 * @param object $member Member object.
	 * @param object $obj    Export object.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function import_xprofile_fields( $member, $obj ) {
		if ( empty( $obj->xprofile ) ) {
			return;
		}

		// Continue only if BuddyPress is available.
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'xprofile' ) ) {
			// Loop through each field.
			foreach ( $obj->xprofile as $field ) {
				// Continue only if field id is set.
				if ( ! isset( $field['id'], $field['type'], $field['value'] ) ) {
					continue;
				}

				// Get current site's field data.
				$field_data = xprofile_get_field( $field['id'] );
				// Import only if the field type is same.
				if ( isset( $field_data->type ) && $field_data->type === $field['type'] ) {
					// Set the data.
					xprofile_set_field_data( $field['id'], $member->id, $field['value'] );
				}
			}
		}
	}
}
