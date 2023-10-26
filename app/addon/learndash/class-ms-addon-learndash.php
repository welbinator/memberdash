<?php
/**
 * Integration for LeanDash Courses
 *
 * @since 1.0.0
 * @package MemberDash
 */

/**
 * Integration for LeanDash Courses
 *
 * @since 1.0.0
 */
class MS_Addon_Learndash extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.0.0
	 */
	const ID = 'addon_learndash';

	/**
	 * WPML Translation context.
	 *
	 * @since 1.0.0
	 */
	const CONTEXT = 'Membership';

	/**
	 * Value from WPML: The site default language.
	 *
	 * @var string
	 */
	protected $default_lang = '';

	/**
	 * Value from WPML: Currently selected language.
	 *
	 * @var string
	 */
	protected $current_lang = '';

	/**
	 * Ajax action identifier used in the Membership settings.
	 *
	 * @since 1.0.0
	 */
	const AJAX_ACTION_SAVE_COURSES = 'addon_learndash_save_courses';

	const AJAX_ACTION_SAVE_SETTINGS = 'addon_learndash_save_settings';

	const ENROLL_TO_MEMBERSHIP = 'enroll_to_membership'; // default

	const ENROLL_TO_COURSE = 'enroll_to_course';

	protected $enroll_type = 'enroll_to_membership';


	/**
	 * Checks if the current Add-on is enabled.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_active() {
		if ( ! self::learndash_active()
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
		static $Init_Done = false;

		if ( $Init_Done ) {
			return;
		}

		$Init_Done = true;

		if ( self::is_active() ) {

			$this->check_requirements();

			$this->enroll_type = ( $this->get_setting( 'enroll_type' ) ) ? $this->get_setting( 'enroll_type' ) : self::ENROLL_TO_MEMBERSHIP;

			// Default.
			// When user purchase membership it will enroll to course.
			if ( $this->enroll_type === self::ENROLL_TO_COURSE ) {
				$this->add_filter(
					'ms_controller_membership_tabs',
					'ms_learndash_tab',
					5,
					3
				);

				$this->add_action(
					'ms_view_membership_edit_render_callback',
					'ms_learndash_tab_view',
					10,
					3
				);

				// Ajax handler that saves a courses ids.
				$this->add_ajax_action(
					self::AJAX_ACTION_SAVE_COURSES,
					'ms_learndash_ajax_save_courses'
				);

				$this->add_action(
					'ms_model_relationship_create_ms_relationship_after',
					'ms_learndash_enroll_to_course',
					10,
					5
				);

			}

			// When user purchase course it will enroll to membership.
			if ( $this->enroll_type === self::ENROLL_TO_MEMBERSHIP ) {
				$this->add_action(
					'add_meta_boxes',
					'ms_register_meta_box'
				);

				$this->add_filter(
					'learndash_header_tab_menu',
					'ms_learndash_menu',
					5,
					3
				);

				$this->add_action(
					'save_post_' . learndash_get_post_type_slug( 'course' ),
					'ms_learndash_membership_save'
				);

				$this->add_action(
					'learndash_update_course_access',
					'ms_enroll_to_membership',
					10,
					4
				);

			}

			$this->add_ajax_action(
				self::AJAX_ACTION_SAVE_SETTINGS,
				'ms_learndash_ajax_save_settings'
			);

		} else {
			$this->add_action( 'ms_model_addon_enable', 'enable_addon' );
		}
	}

	public function ms_learndash_enroll_to_course( $subscription, $membership_id, $user_id, $gateway_id, $move_from_id ) {

		if ( ! empty( $subscription ) ) {

			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );

			if ( $membership->is_valid() ) {
				$courses = self::ms_get_learndash_membership_courses( $membership );

				if ( ! empty( $courses ) ) {
					foreach ( $courses as $course ) {
						ld_update_course_access( $user_id, $course );
					}
				}
			}
		}

	}

	public static function ms_get_learndash_membership_courses( $membership ) {

		return $membership->get_custom_data( 'course_list' );

	}



	public function ms_learndash_ajax_save_settings() {
		$res    = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		$fields = array( 'field', 'membership_id' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			$values = $_POST['values'];
			$res    = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}

		echo $res; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Ajax handler that saves a membership attribute.
	 *
	 * @since 1.0.0
	 */
	public function ms_learndash_ajax_save_courses() {
		$res    = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		$fields = array( 'field', 'membership_id' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			$id         = intval( $_POST['membership_id'] );
			$values     = isset( $_POST['values'] ) ? $_POST['values'] : array();
			$membership = MS_Factory::load( 'MS_Model_Membership', $id );

			if ( $membership->is_valid() ) {

				$membership->set_custom_data(
					'course_list',
					$values
				);
				$membership->save();
			}
			$res = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}

		echo $res; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public static function ms_list_of_learndash_courses() {

		$args = array(
			'numberposts' => -1,
			'post_type'   => learndash_get_post_type_slug( 'course' ),
		);

		$courses = get_posts( $args );
		if ( ! empty( $courses ) ) {
			foreach ( $courses as $course ) {
				$course_list[ $course->ID ] = $course->post_title;
			}
		} else {
			$course_list[''] = 'No Courses Available';
		}

		return $course_list;
	}

	public function ms_learndash_tab_view( $callback, $tab, $data ) {

		if ( $tab == self::ID ) {
			$view       = MS_Factory::load( 'MS_Addon_Learndash_View_List' );
			$view->data = $data;
			$callback   = array( $view, 'render_tab' );
		}
			return $callback;
	}

	public function ms_learndash_tab( $tabs ) {
		$tabs[ self::ID ] = array(
			'title' => __( 'LearnDash', 'memberdash' ),
		);

		return $tabs;
	}


	public function ms_learndash_menu( $header_data, $menu_tab_key, $screen_post_type ) {
		$screen = get_current_screen();
		if ( $screen_post_type === $screen->id ) {
				$header_data = array_merge(
					$header_data,
					array(
						array(
							'id'                  => 'ms_learndash_course_membership',
							'name'                => esc_html__( 'Membership', 'memberdash' ),
							'metaboxes'           => array( 'learndash-course-membership' ),
							'showDocumentSidebar' => 'false',
						),
					)
				);
			return $header_data;
		}
		return $header_data;
	}

	public function ms_register_meta_box() {
		add_meta_box( 'learndash-course-membership', esc_html__( 'Membership', 'memberdash' ), array( $this, 'ms_meta_box_callback' ), learndash_get_post_type_slug( 'course' ), 'normal', 'high' );
	}

	public function ms_meta_box_callback( $post ) {
		$memberships = MS_Model_Membership::get_memberships();
		$value       = get_post_meta( $post->ID, 'ms_course_membership', true );
		/*var_dump($value);die;*/
		if ( ! empty( $memberships ) ) { ?>
				<p>
					<label for="ms_course_membership">
						<?php esc_html_e( 'Membership', 'memberdash' ); ?>
					</label>
					<select id="ms_course_membership" name="ms_course_membership" >
						<option value="" <?php echo selected( $value, '' ); ?>>
								Select Membership
							</option>
						<?php
						foreach ( $memberships as $membership ) {
							?>
							<option value="<?php echo esc_attr( $membership->id ); ?>" <?php echo selected( $value, $membership->id ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<?php echo esc_html( $membership->name ); ?>
							</option>
							<?php
						}
						?>

					</select>

				<p><small>*User will enroll to selected membership</small></p>
				</p>
				<?php

		} else {
			?>
				<p>Please add Membership</p>
			<?php
		}
	}

	public function ms_learndash_membership_save( $post_id ) {
		if ( ! isset( $_POST['ms_course_membership'] ) ) {
			return $post_id;
		}
		$membership_id = $_POST['ms_course_membership'];
		update_post_meta( $post_id, 'ms_course_membership', $membership_id );
	}


	/**
	 * Enrolls a user to a membership when a user purchases a course.
	 *
	 * @since 1.0.0
	 *
	 * @param int          $user_id            The user ID.
	 * @param int          $course_id          The course ID.
	 * @param array<mixed> $course_access_list The course access list.
	 * @param bool         $remove             True if the user is removed from the course.
	 *
	 * @return void
	 */
	public function ms_enroll_to_membership( $user_id, $course_id, $course_access_list, $remove ) {
		// skip if user is removed from course.
		if ( $remove ) {
			return;
		}

		$membership_id = get_post_meta( $course_id, 'ms_course_membership', true );

		if ( empty( $membership_id ) ) {
			return;
		}

		/**
		* Member object.
		*
		* @var MS_Model_Member $member
		*/
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );

		$subscription = $member->add_membership( intval( $membership_id ) );

		if ( ! empty( $subscription ) ) {
			$subscription->save();
		}
	}

	/**
	 * Chooses how MD and LD integrate
	 *
	 * @since 1.0.0
	 */
	public static function enroll_types() {

		return array(
			self::ENROLL_TO_COURSE     => __( 'When a user subscribes to a Membership they will be automatically enrolled into the selected Course', 'memberdash' ),
			self::ENROLL_TO_MEMBERSHIP => __( 'When a user enrolls in a Course they will be automatically subscribed to the selected Membership', 'memberdash' ),
		);

	}

	/**
	 * Registers the Add-On.
	 *
	 * @since 1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {

		/*var_dump($settings);*/
		$list[ self::ID ] = (object) array(
			'name'        => __( 'LearnDash Integration', 'memberdash' ),
			'description' => __( 'This add-on integrates MemberDash with LearnDash', 'memberdash' ),
			'footer'      => sprintf( '<i class="dashicons dashicons dashicons-admin-settings"></i> %s', __( 'Options available', 'memberdash' ) ),
			'class'       => 'ms-options',
			'details'     => array(
				array(
					'id'            => 'enroll_type',
					'type'          => MS_Helper_Html::INPUT_TYPE_RADIO,
					'title'         => __( 'Select enroll to ', 'memberdash' ),
					'value'         => $this->enroll_type,
					'field_options' => self::enroll_types(),
					'class'         => 'ms-radio',
					'ajax_data'     => array(
						'field'    => 'enroll_type',
						'action'   => $this->ajax_action(),
						'_wpnonce' => true, // Nonce will be generated from 'action'
					),
				),
			),
		);

		if ( ! self::learndash_active() ) {
			$list[ self::ID ]->description .= sprintf(
				'<br /><b>%s</b>',
				__( 'Activate LearnDash to use this Add-on', 'memberdash' )
			);
			$list[ self::ID ]->action       = '-';
		}

		return $list;
	}

	/**
	 * Checks if the LearnDash plugin is active.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function learndash_active() {
		return defined( 'LEARNDASH_VERSION' );
	}

	/**
	 * Function is triggered every time an add-on is enabled.
	 *
	 * We flush the Factory Cache when the LearnDash Add-on is enabled so all strings
	 * are properly registered for translation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $addon The Add-on ID.
	 */
	public function enable_addon( $addon ) {
		if ( self::ID === $addon ) {
			MS_Factory::clear();
		}
	}

	/**
	 * Check if LearnDash is up to date and required modules are active.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function check_requirements(): void {
		if ( ! self::learndash_active() ) {
			mslib3()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'LearnDash not active!', 'memberdash' ),
					__( 'Heads up: Activate the LearnDash plugin to enable the LearnDash integration.', 'memberdash' )
				),
				'err'
			);
			return;
		}

		if ( version_compare( LEARNDASH_VERSION, '4.7.0', 'lt' ) ) {
			mslib3()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'Great, you\'re using LearnDash!', 'memberdash' ),
					__( 'Heads up: Your version of LearnDash is outdated. Please update LearnDash to version <b>4.7.0 or higher</b> to enable the LearnDash integration.', 'memberdash' )
				),
				'err'
			);
			return;
		}
	}
}
