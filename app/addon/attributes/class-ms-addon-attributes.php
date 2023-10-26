<?php
/**
 * Add-on: Add custom Attributes to memberships.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

/**
 * Add-on: Add custom Attributes to memberships.
 *
 * @since 1.0.0
 */
class MS_Addon_Attributes extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.0.0
	 */
	const ID = 'addon_attribute';

	/**
	 * Ajax action identifier used on the Settings page.
	 *
	 * @since 1.0.0
	 */
	const AJAX_ACTION_SAVE_SETTING = 'addon_attribute_save_setting';

	/**
	 * Ajax action identifier used on the Settings page.
	 *
	 * @since 1.0.0
	 */
	const AJAX_ACTION_DELETE_SETTING = 'addon_attribute_delete_setting';

	/**
	 * Ajax action identifier used in the Membership settings.
	 *
	 * @since 1.0.0
	 */
	const AJAX_ACTION_SAVE_ATTRIBUTE = 'addon_attribute_save_attribute';

	/**
	 * The shortcode which can be used to access custom attributes.
	 *
	 * @since 1.0.0
	 */
	const SHORTCODE = 'ms-membership-attr';

	/**
	 * Checks if the current Add-on is enabled.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_active() {
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
		if ( self::is_active() ) {
			// --- Plugin settings ---

			// Display a new tab in settings page.
			$this->add_filter(
				'ms_controller_settings_get_tabs',
				'add_settings_tab'
			);

			// Display the new settings page contents.
			$this->add_filter(
				'ms_view_settings_edit_render_callback',
				'manage_settings_callback',
				10,
				3
			);

			// Add settings javascript.
			$this->add_action(
				'ms_controller_settings_enqueue_scripts_' . self::ID,
				'enqueue_settings_scripts'
			);

			// Ajax handler that saves a single attribute definition.
			$this->add_ajax_action(
				self::AJAX_ACTION_SAVE_SETTING,
				'ajax_save_setting'
			);

			// Ajax handler that deletes a single attribute definition.
			$this->add_ajax_action(
				self::AJAX_ACTION_DELETE_SETTING,
				'ajax_delete_setting'
			);

			// --- Membership settings ---

			// Display a new tab in edit page.
			$this->add_filter(
				'ms_controller_membership_tabs',
				'add_membership_tab'
			);

			// Display the new edit page contents.
			$this->add_filter(
				'ms_view_membership_edit_render_callback',
				'manage_membership_callback',
				10,
				3
			);

			// Add settings javascript.
			$this->add_action(
				'ms_controller_membership_enqueue_scripts_tab-' . self::ID,
				'enqueue_membership_scripts'
			);

			// Ajax handler that deletes a single attribute definition.
			$this->add_ajax_action(
				self::AJAX_ACTION_SAVE_ATTRIBUTE,
				'ajax_save_attribute'
			);

			// --- Access/integration ---

			// Register the shortcode to access custom attributes.
			add_shortcode(
				self::SHORTCODE,
				array( $this, 'do_shortcode' )
			);

			// Output shortcode info on the help page.
			$this->add_action(
				'ms_view_help_shortcodes-membership',
				'help_page'
			);

			$this->add_filter(
				'ms_membership_attr',
				'get_attr_filter',
				10,
				3
			);
		}
	}

	/**
	 * Registers the Add-On.
	 *
	 * @since 1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name'        => __( 'Membership Attributes', 'memberdash' ),
			'description' => __( 'Add custom attributes to your memberships that you can use in shortcodes and code.', 'memberdash' ),
		);
		return $list;
	}


	/*
	===========================================*\
	===============================================
	==                                           ==
	==           DATA ACCESS FUNCTIONS           ==
	==                                           ==
	===============================================
	\*===========================================*/


	/**
	 * Saves a single field definition to the database.
	 *
	 * @since 1.0.0
	 * @param  array $field The field details.
	 * @return array The data that was saved to database (or false on error).
	 */
	public static function save_field_def( $field ) {
		$res = false;

		// Sanitize new field data.
		mslib3()->array->equip( $field, 'title', 'slug', 'type', 'info' );
		$field = (object) $field;

		$field->title = html_entity_decode( trim( $field->title ) );
		$field->slug  = strtolower( trim( $field->slug ) );
		$field->type  = strtolower( trim( $field->type ) );
		$field->info  = html_entity_decode( trim( $field->info ) );

		if ( ! $field->title || ! $field->slug || ! $field->type ) {
			// Stop if a required property is empty.
			return $res;
		}

		// Load existing fields.
		$settings = MS_Plugin::instance()->settings;
		$fields   = $settings->get_custom_setting( self::ID, 'fields' );
		$fields   = mslib3()->array->get( $fields );

		// Check for duplicates.
		$duplicate = false;
		if ( $field->slug != $field->old_slug ) {
			foreach ( $fields as $saved_field ) {
				if ( $saved_field->slug == $field->slug ) {
					$duplicate = true;
					break;
				}
			}
		}

		// Determine the item that is updated or inserted.
		$insert_at = count( $fields );
		if ( $field->old_slug ) {
			foreach ( $fields as $index => $saved_field ) {
				if ( $saved_field->slug == $field->old_slug ) {
					$insert_at = $index;
					break;
				}
			}
		}

		// Save new field if everything is okay.
		if ( ! $duplicate ) {
			$fields[ $insert_at ] = $field;
			$fields               = array_values( $fields );
			$settings->set_custom_setting( self::ID, 'fields', $fields );
			$settings->save();
			$res = $field;
		}

		return $res;
	}

	/**
	 * Deletes a single field definition from the database.
	 *
	 * @since 1.0.0
	 * @param  string $slug The slug that identifies the field.
	 * @return bool
	 */
	public static function remove_field_def( $slug ) {
		$res = false;

		// Load existing fields.
		$settings = MS_Plugin::instance()->settings;
		$fields   = $settings->get_custom_setting( self::ID, 'fields' );
		$fields   = mslib3()->array->get( $fields );

		// Find the field and remove it.
		foreach ( $fields as $index => $saved_field ) {
			if ( $saved_field->slug == $slug ) {
				unset( $fields[ $index ] );
				$res = true;
				break;
			}
		}

		// Save modified field if everything is okay.
		if ( $res ) {
			$settings->set_custom_setting( self::ID, 'fields', $fields );
			$settings->save();
		}

		return $res;
	}

	/**
	 * Returns a single field definition.
	 *
	 * @since 1.0.0
	 * @param  string $slug The slug to identify the field.
	 * @return false|object The field definition or false.
	 */
	public static function get_field_def( $slug ) {
		$res = false;

		$settings = MS_Plugin::instance()->settings;
		$fields   = $settings->get_custom_setting( self::ID, 'fields' );
		$fields   = mslib3()->array->get( $fields );

		foreach ( $fields as $field ) {
			if ( $field->slug == $slug ) {
				$res = $field;
				break;
			}
		}

		return $res;
	}

	/**
	 * Returns a list of all field definition.
	 *
	 * @since 1.0.0
	 * @return array A list of field definitions.
	 */
	public static function list_field_def() {
		$settings = MS_Plugin::instance()->settings;
		$fields   = $settings->get_custom_setting( self::ID, 'fields' );
		$fields   = mslib3()->array->get( $fields );

		return $fields;
	}

	/**
	 * Returns a custom attribute of the specified membership.
	 *
	 * @since 1.0.0
	 * @param  string                  $slug The field to fetch.
	 * @param  int|MS_Model_Membership $membership_id The Membership.
	 * @return false|string The field value.
	 */
	public static function get_attr( $slug, $membership_id = 0 ) {
		$res = false;

		if ( ! $membership_id ) {
			$auto_id    = apply_filters( 'ms_detect_membership_id', 0 );
			$membership = MS_Factory::load( 'MS_Model_Membership', $auto_id );
		} elseif ( $membership_id instanceof MS_Model_Membership ) {
			$membership = $membership_id;
		} else {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		}

		if ( $membership->is_valid() ) {
			$field = self::get_field_def( $slug );
			$res   = $membership->get_custom_data( 'attr_' . $slug );

			switch ( $field->type ) {
				case 'bool':
					$res = mslib3()->is_true( $res );
					break;

				case 'number':
					$res = intval( $res );
					break;
			}
		}

		return $res;
	}

	/**
	 * Saves a custom attribute to the specified membership.
	 *
	 * @since 1.0.0
	 * @param string $slug The field to update.
	 * @param string $value The new value to assign.
	 * @param int    $membership_id The membership.
	 */
	public static function set_attr( $slug, $value, $membership_id = 0 ) {
		if ( ! $membership_id ) {
			$auto_id    = apply_filters( 'ms_detect_membership_id' );
			$membership = MS_Factory::load( 'MS_Model_Membership', $auto_id );
		} elseif ( $membership_id instanceof MS_Model_Membership ) {
			$membership = $membership_id;
		} else {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		}

		if ( $membership->is_valid() ) {
			$membership->set_custom_data(
				'attr_' . $slug,
				$value
			);
			$membership->save();
		}
	}


	/*
	======================================*\
	==========================================
	==                                      ==
	==           GENERAL SETTINGS           ==
	==                                      ==
	==========================================
	\*======================================*/


	/**
	 * Add the Attributes tab to the Plugin Settings page.
	 *
	 * @filter ms_controller_settings_get_tabs
	 *
	 * @since 1.0.0
	 * @param  array $tabs The default list of edit tabs.
	 * @return array The modified list of tabs.
	 */
	public function add_settings_tab( $tabs ) {
		$tabs[ self::ID ] = array(
			'title'        => __( 'Membership Attributes', 'memberdash' ),
			'url'          => MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => self::ID )
			),
			'svg_view_box' => '0 0 152 152',
			'icon_path'    => '<path d="M117.433 128.708H39.5675C36.2461 128.71 33.0192 127.603 30.3982 125.563C27.7772 123.523 25.9121 120.666 25.0983 117.446L13.8362 72.6962C13.2706 70.5013 13.2897 68.1965 13.8916 66.0113C14.4935 63.826 15.6573 61.8365 17.2671 60.2408C18.8752 58.6496 20.8653 57.4984 23.0464 56.8976C25.2275 56.2969 27.5264 56.2667 29.7225 56.81L45.8325 60.8375L66.865 34.5842C68.3073 32.9117 70.0933 31.5699 72.1011 30.6501C74.109 29.7303 76.2915 29.2542 78.5 29.2542C80.7085 29.2542 82.891 29.7303 84.8989 30.6501C86.9067 31.5699 88.6927 32.9117 90.135 34.5842L111.168 60.8375L127.278 56.81C129.474 56.2667 131.772 56.2969 133.954 56.8976C136.135 57.4984 138.125 58.6496 139.733 60.2408C141.343 61.8365 142.506 63.826 143.108 66.0113C143.71 68.1965 143.729 70.5013 143.164 72.6962L131.902 117.446C131.088 120.666 129.223 123.523 126.602 125.563C123.981 127.603 120.754 128.71 117.433 128.708ZM29.0513 71.8012L39.5675 113.792H117.433L127.949 71.8012L114.822 75.0829C112.048 75.7813 109.132 75.6675 106.422 74.755C103.711 73.8426 101.319 72.1701 99.5325 69.9367L78.5 43.6833L57.4675 69.9367C55.7109 72.1033 53.3821 73.734 50.7454 74.6437C48.1086 75.5533 45.2697 75.7055 42.5508 75.0829L29.0513 71.8012Z" fill="#9DA3AF"/><path d="M9.51042 50.3958C14.6593 50.3958 18.8333 46.2218 18.8333 41.0729C18.8333 35.924 14.6593 31.75 9.51042 31.75C4.36151 31.75 0.1875 35.924 0.1875 41.0729C0.1875 46.2218 4.36151 50.3958 9.51042 50.3958Z" fill="#9DA3AF"/><path d="M147.49 50.3958C152.639 50.3958 156.813 46.2218 156.813 41.0729C156.813 35.924 152.639 31.75 147.49 31.75C142.341 31.75 138.167 35.924 138.167 41.0729C138.167 46.2218 142.341 50.3958 147.49 50.3958Z" fill="#9DA3AF"/><path d="M78.5 18.698C83.6489 18.698 87.8229 14.5239 87.8229 9.37504C87.8229 4.22614 83.6489 0.052124 78.5 0.052124C73.3511 0.052124 69.1771 4.22614 69.1771 9.37504C69.1771 14.5239 73.3511 18.698 78.5 18.698Z" fill="#9DA3AF"/><path d="M123.25 151.083H33.75C31.7719 151.083 29.8749 150.298 28.4762 148.899C27.0775 147.5 26.2917 145.603 26.2917 143.625C26.2917 141.647 27.0775 139.75 28.4762 138.351C29.8749 136.953 31.7719 136.167 33.75 136.167H123.25C125.228 136.167 127.125 136.953 128.524 138.351C129.923 139.75 130.708 141.647 130.708 143.625C130.708 145.603 129.923 147.5 128.524 148.899C127.125 150.298 125.228 151.083 123.25 151.083Z" fill="#9DA3AF"/>',
		);

		return $tabs;
	}

	/**
	 * Add callback to display our custom edit tab contents.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_view_settings_edit_render_callback
	 *
	 * @param  array  $callback The current function callback.
	 * @param  string $tab The current membership edit tab.
	 * @param  array  $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_settings_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view       = MS_Factory::load( 'MS_Addon_Attributes_View_Settings' );
			$view->data = $data;
			$callback   = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Enqueue admin scripts in the settings screen.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_settings_scripts() {
		$addon_url = MS_Plugin::instance()->url . '/app/addon/attributes/';

		$data = array(
			'lang' => array(
				'edit_title' => __( 'Edit Attribute', 'memberdash' ),
			),
		);

		mslib3()->ui->data( 'ms_data', $data );
		mslib3()->ui->add( 'jquery-ui' );
		mslib3()->ui->add( 'jquery-ui-sortable' );
		mslib3()->ui->add( $addon_url . 'assets/js/settings.js' );
		mslib3()->ui->add( $addon_url . 'assets/css/attributes.css' );
	}

	/**
	 * Ajax handler that saves an attribute definition.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_setting() {
		$res    = (object) array();
		$fields = array( 'title', 'slug', 'type' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			mslib3()->array->equip_post( 'info', 'old_slug' );
			mslib3()->array->strip_slashes( $_POST, 'name', 'info' );
			$field = array(
				'title'    => esc_attr( $_POST['title'] ),
				'old_slug' => sanitize_html_class( $_POST['old_slug'] ),
				'slug'     => sanitize_html_class( $_POST['slug'] ),
				'type'     => esc_html( $_POST['type'] ),
				'info'     => esc_attr( $_POST['info'] ),
			);

			$ok = self::save_field_def( $field );

			if ( $ok ) {
				$res->items = self::list_field_def();
				$res->ok    = true;
			}
		}

		echo wp_json_encode( $res );
		exit;
	}

	/**
	 * Ajax handler that deletes an attribute definition.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_setting() {
		$res    = (object) array();
		$fields = array( 'slug' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			$ok = self::remove_field_def( $_POST['slug'] );

			if ( $ok ) {
				$res->items = self::list_field_def();
				$res->ok    = true;
			}
		}

		echo wp_json_encode( $res );
		exit;
	}


	/*
	=========================================*\
	=============================================
	==                                         ==
	==           MEMBERSHIP SETTINGS           ==
	==                                         ==
	=============================================
	\*=========================================*/


	/**
	 * Add the Attributes tab to the Membership editor.
	 *
	 * @filter ms_controller_membership_tabs
	 *
	 * @since 1.0.0
	 * @param  array $tabs The default list of edit tabs.
	 * @return array The modified list of tabs.
	 */
	public function add_membership_tab( $tabs ) {
		$tabs[ self::ID ] = array(
			'title' => __( 'Membership Attributes', 'memberdash' ),
		);

		return $tabs;
	}

	/**
	 * Add callback to display our custom edit tab contents.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_view_membership_edit_render_callback
	 *
	 * @param  array  $callback The current function callback.
	 * @param  string $tab The current membership edit tab.
	 * @param  array  $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_membership_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view       = MS_Factory::load( 'MS_Addon_Attributes_View_Membership' );
			$view->data = $data;
			$callback   = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Enqueue admin scripts in the membership settings screen.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_membership_scripts() {
		$addon_url = MS_Plugin::instance()->url . '/app/addon/attributes/';

		mslib3()->ui->add( $addon_url . 'assets/css/attributes.css' );
	}

	/**
	 * Ajax handler that saves a membership attribute.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_attribute() {
		$res    = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		$fields = array( 'field', 'value', 'membership_id' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			$id = intval( $_POST['membership_id'] );
			self::set_attr( $_POST['field'], $_POST['value'], $id );
			$res = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}

		echo $res; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}


	/*
	========================================*\
	============================================
	==                                        ==
	==           ACCESS/INTEGRATION           ==
	==                                        ==
	============================================
	\*========================================*/


	/**
	 * Parses the custom shortcode and returns the value of the attribute.
	 *
	 * @since 1.0.0
	 * @param  array  $atts The shortcode attributes.
	 * @param  string $content Content between the shortcode open/close tags.
	 * @return string The attribute value.
	 */
	public function do_shortcode( $atts, $content = '' ) {
		$data = apply_filters(
			'ms_addon_attributes_shortcode_atts',
			shortcode_atts(
				array(
					'id'      => 0,
					'slug'    => '',
					'title'   => false,
					'default' => '',
				),
				$atts
			)
		);

		$value = $data['default'];
		$title = '';
		$field = self::get_field_def( $data['slug'] );

		if ( $field ) {
			$membership_id = apply_filters(
				'ms_detect_membership_id',
				$data['id']
			);

			// Fetch the attribute value.
			if ( $membership_id ) {
				$attr = self::get_attr( $data['slug'], $membership_id );

				if ( false !== $attr ) {
					$value = $attr;
				}
			}

			// Prepare the field title.
			if ( mslib3()->is_true( $data['title'] ) ) {
				$title = '<span class="ms-title">' . $field->title . '</span> ';
			}
		}

		$value = '<span class="ms-value">' . do_shortcode( $value ) . '</span>';

		$html = sprintf(
			'<span class="ms-attr ms-attr-%s">%s%s</span>',
			$data['slug'],
			$title,
			$value
		);

		return apply_filters(
			'ms_addon_attributes_shortcode',
			$html,
			$data,
			$content
		);
	}

	/**
	 * Output shortcode info on the Help page.
	 *
	 * @since 1.0.0
	 */
	public function help_page() {
		?>
		<div id="<?php echo esc_attr( self::SHORTCODE ); ?>" class="ms-help-box">
			<h3><code>[<?php echo esc_attr( self::SHORTCODE ); ?>]</code></h3>

			<?php echo esc_html_x( 'Output the value of a Custom Membership Attribute.', 'help', 'memberdash' ); ?>
			<div class="ms-help-toggle"><?php echo esc_html_x( 'Expand', 'help', 'memberdash' ); ?></div>
			<div class="ms-help-details" style="display:none">
				<ul>
					<li>
						<code>slug</code>
						<?php echo esc_html_x( '(Text)', 'help', 'memberdash' ); ?>
						<strong><?php echo esc_html_x( 'Required', 'help', 'memberdash' ); ?></strong>.
						<?php echo esc_html_x( 'Slug of the custom attribute', 'help', 'memberdash' ); ?>.
					</li>
					<li>
						<code>id</code>
						<?php echo esc_html_x( '(Single ID)', 'help', 'memberdash' ); ?>
						<?php echo esc_html_x( 'The membership ID', 'help', 'memberdash' ); ?>.
						<span class="ms-help-default">
							<?php echo esc_html_x( 'Default:', 'help', 'memberdash' ); ?>
							<?php esc_html_e( 'Automatic detection', 'memberdash' ); ?>
						</span><br />
						<em><?php echo esc_html_x( 'If not specified the plugin attempts to identify the currently displayed membership by examining the URL, request data and subscriptions of the current member', 'help', 'memberdash' ); ?></em>.
					</li>
					<li>
						<code>title</code>
						<?php echo esc_html_x( '(yes|no)', 'help', 'memberdash' ); ?>
						<?php echo esc_html_x( 'Prefix the field title to the output', 'help', 'memberdash' ); ?>.
						<span class="ms-help-default">
							<?php echo esc_html_x( 'Default:', 'help', 'memberdash' ); ?>
							no
						</span>
					</li>
					<li>
						<code>default</code>
						<?php echo esc_html_x( '(Text)', 'help', 'memberdash' ); ?>
						<?php echo esc_html_x( 'Default value to display if no membership was found or the membership did not define the attribute', 'help', 'memberdash' ); ?>.
						<span class="ms-help-default">
							<?php echo esc_html_x( 'Default:', 'help', 'memberdash' ); ?>
							""
						</span>
					</li>
				</ul>

				<p><em><?php echo esc_html_x( 'Example:', 'help', 'memberdash' ); ?></em></p>
				<p><code>[<?php echo esc_attr( self::SHORTCODE ); ?> slug="intro"]</code></p>
				<p><code>[<?php echo esc_attr( self::SHORTCODE ); ?> slug="intro" id="5" default="An awesome offer!"]</code></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles a filter function and returns a single membership attribute.
	 *
	 * @since 1.0.0
	 * @param  string $default Default value.
	 * @param  string $slug Attribute slug.
	 * @param  int    $membership_id Optional. Membership-ID.
	 * @return string The attribute value.
	 */
	public function get_attr_filter( $default, $slug, $membership_id = 0 ) {
		$val = self::get_attr( $slug, $membership_id );

		if ( $val ) {
			$default = $val;
		}

		return $default;
	}

}

/**
 * Convenience function to access a membership attribute value.
 *
 * @since 1.0.0
 * @param  string $slug The attribute slug.
 * @param  int    $membership_id Membership ID.
 * @return string|false The attribute value or false.
 */
function ms_membership_attr( $slug, $membership_id = 0 ) {
	return MS_Addon_Attributes::get_attr( $slug, $membership_id );
}

/**
 * Convenience function to modify a membership attribute value.
 *
 * @since 1.0.0
 * @param  string $slug The attribute slug.
 * @param  string $value The attribute value to assign.
 * @param  int    $membership_id Membership ID.
 */
function ms_membership_attr_set( $slug, $value, $membership_id = 0 ) {
	MS_Addon_Attributes::set_attr( $slug, $value, $membership_id );
}
