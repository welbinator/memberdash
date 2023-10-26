<?php
/**
 * Add-On controller for: MailChimp
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Controller
 */

/**
 * Class MS_Addon_Mailchimp.
 */
class MS_Addon_Mailchimp extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.0.0
	 */
	const ID = 'mailchimp';

	/**
	 * Mailchimp API object
	 *
	 * @var MWPS_Mailchimp
	 */
	protected static $mailchimp_api = null;

	/**
	 * Checks if the current Add-on is enabled
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
			$this->add_filter(
				'ms_controller_settings_get_tabs',
				'settings_tabs',
				10,
				2
			);

			$this->add_action(
				'ms_controller_settings_enqueue_scripts_' . self::ID,
				'enqueue_scripts'
			);

			$this->add_filter(
				'ms_view_settings_edit_render_callback',
				'manage_render_callback',
				10,
				3
			);

			// Watch for REGISTER event: Subscribe user to list.
			$this->add_action(
				'ms_model_event_' . MS_Model_Event::TYPE_MS_REGISTERED,
				'subscribe_registered',
				10,
				2
			);

			// Watch for SIGN UP event: Subscribe user to list.
			$this->add_action(
				'ms_model_event_' . MS_Model_Event::TYPE_MS_SIGNED_UP,
				'subscribe_members',
				10,
				2
			);

			// Watch for DEACTIVATE event: Subscribe user to list.
			$this->add_action(
				'ms_model_event_' . MS_Model_Event::TYPE_MS_DEACTIVATED,
				'subscribe_deactivated',
				10,
				2
			);

			$this->add_filter(
				'ms_view_membership_details_tab',
				'mc_fields_for_ms',
				10,
				3
			);

			$this->add_filter(
				'ms_view_membership_edit_to_html',
				'mc_custom_html',
				10,
				3
			);

			$this->add_action(
				'ms_model_membership__set_after',
				'ms_model_membership__set_after_cb',
				10,
				3
			);

			$this->add_action(
				'ms_model_membership__get',
				'ms_model_membership__get_cb',
				10,
				3
			);
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
			'name'        => __( 'MailChimp Integration', 'memberdash' ),
			'description' => __( 'Enable MailChimp integration.', 'memberdash' ),
			'icon'        => 'dashicons dashicons-email',
		);

		return $list;
	}

	/**
	 * Mailchimp Error logging
	 *
	 * @since 1.1.2
	 */
	private static function mailchimp_log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			mslib3()->debug->log( '[MWPS] Mailchimp Error : ' . $message );
		}
	}

	/**
	 * A new user registered (not a Member yet).
	 *
	 * @since 1.0.0
	 * @param  mixed $event
	 * @param  mixed $member
	 */
	public function subscribe_registered( $event, $member ) {
		try {
			$list_id = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' );
			if ( $list_id ) {

				$dont_subscribe     = apply_filters( 'ms_dont_subscribe_registered', false, $member, $list_id, $event );
				$already_subscribed = self::is_user_subscribed( $member->email, $list_id );

				if ( $dont_subscribe ) {
					// Unsubscribe from registered members mail list, if already subscribed.
					if ( $already_subscribed ) {
						self::unsubscribe_user( $member->email, $list_id );
					}
				} else {
					// Subscribe to registered members mail list.
					if ( ! $already_subscribed ) {
						self::subscribe_user( $member, $list_id );
					}
				}
			}
		} catch ( Exception $e ) {
			self::mailchimp_log( $e->getMessage() );
		}
	}

	/**
	 * A user subscribed to a membership.
	 *
	 * @since 1.0.0
	 * @param  mixed $event
	 * @param  mixed $member
	 */
	public function subscribe_members( $event, $subscription ) {
		try {
			$member = $subscription->get_member();

			$mail_list_registered  = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' );
			$mail_list_deactivated = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' );
			$mail_list_members     = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_members' );

			if ( $mail_list_members != $mail_list_registered ) {
				/** Verify if is subscribed to registered mail list and remove it. */
				if ( self::is_user_subscribed( $member->email, $mail_list_registered ) ) {
					self::unsubscribe_user( $member->email, $mail_list_registered );
				}
			}

			if ( $mail_list_members != $mail_list_deactivated ) {
				/** Verify if is subscribed to deactivated mail list and remove it. */
				if ( self::is_user_subscribed( $member->email, $mail_list_deactivated ) ) {
					self::unsubscribe_user( $member->email, $mail_list_deactivated );
				}
			}

			$custom_list_id = get_option( 'ms_mc_m_id_' . $subscription->membership_id );

			if ( isset( $custom_list_id ) && 0 != $custom_list_id ) {
				$list_id = $custom_list_id;
			} else {
				$list_id = $mail_list_members;
			}

			if ( $list_id ) {

				$dont_subscribe     = apply_filters( 'ms_dont_subscribe_members', false, $member, $list_id, $event );
				$already_subscribed = self::is_user_subscribed( $member->email, $list_id );

				if ( $dont_subscribe ) {
					/**  Unsubscribe from members mail list, if already subscribed. */
					if ( $already_subscribed ) {
						self::unsubscribe_user( $member->email, $list_id );
					}
				} else {
					/** Subscribe to members mail list. */
					if ( $already_subscribed ) {
						self::unsubscribe_user( $member->email, $list_id );
					}
					self::subscribe_user( $member, $list_id );
				}
			}
		} catch ( Exception $e ) {
			self::mailchimp_log( $e->getMessage() );
		}
	}

	/**
	 * A membership was deactivated (e.g. expired or manually cancelled)
	 *
	 * @since 1.0.0
	 *
	 * @param string $event The event name.
	 * @param object $subscription The subscription object.
	 *
	 * @return void
	 */
	public function subscribe_deactivated( $event, $subscription ) {
		try {
			$member = $subscription->get_member();

			// Check if member has a new subscription
			$membership     = $subscription->get_membership();
			$new_membership = MS_Factory::load(
				'MS_Model_Membership',
				$membership->on_end_membership_id
			);
			if ( ! $new_membership->is_valid() ) {

				$mail_list_registered  = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_registered' );
				$mail_list_deactivated = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' );
				$mail_list_members     = self::$settings->get_custom_setting( 'mailchimp', 'mail_list_members' );

				if ( $mail_list_deactivated == $mail_list_registered ) {
					// Verify if is subscribed to registered mail list and remove it.
					if ( self::is_user_subscribed( $member->email, $mail_list_registered ) ) {
						self::unsubscribe_user( $member->email, $mail_list_registered );
					}
				}

				if ( $mail_list_deactivated == $mail_list_members ) {
					// Verify if is subscribed to members mail list and remove it.
					if ( self::is_user_subscribed( $member->email, $mail_list_members ) ) {
						self::unsubscribe_user( $member->email, $mail_list_members );
					}
				}

				$dont_subscribe     = apply_filters( 'ms_dont_subscribe_deactivated', false, $member, $mail_list_deactivated, $event );
				$already_subscribed = self::is_user_subscribed( $member->email, $mail_list_deactivated );

				if ( $dont_subscribe ) {
					// Unsubscribe from deactivated members mail list, if already subscribed.
					if ( $already_subscribed ) {
						self::unsubscribe_user( $member->email, $mail_list_deactivated );
					}
				} else {
					// Subscribe to deactivated members mail list.
					if ( ! $already_subscribed ) {
						self::subscribe_user( $member, $mail_list_deactivated );
					}
				}
			}
		} catch ( Exception $e ) {
			self::mailchimp_log( $e->getMessage() );
		}
	}

	/**
	 * Add mailchimp settings tab in settings page.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param  array $tabs The current tabs.
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID ] = array(
			'title'        => __( 'MailChimp', 'memberdash' ),
			'url'          => MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => self::ID )
			),
			'svg_view_box' => '0 0 154 154',
			'icon_path'    => '<path d="M25.641 96.6607C26.0581 96.7185 26.6677 96.4169 27.0462 95.3774L27.1553 95.0437C27.3222 94.4983 27.643 93.4845 28.1563 92.6695C28.9584 91.4568 30.3059 90.6675 31.8395 90.6675C32.7314 90.6675 33.5592 90.937 34.2522 91.3926C35.9462 92.5155 36.5942 94.5945 35.8756 96.5901C35.497 97.6232 34.8938 99.5931 35.0222 101.21C35.2981 104.495 37.3065 105.811 39.1032 105.958C40.8549 106.023 42.0805 105.028 42.3885 104.303C42.581 103.886 42.4206 103.623 42.3243 103.507L42.3307 103.501C42.0612 103.161 41.6121 103.263 41.1822 103.366C40.8742 103.45 40.5212 103.501 40.1555 103.507C40.1427 103.507 40.1234 103.507 40.1042 103.507C39.3534 103.507 38.6925 103.135 38.2882 102.564L38.2818 102.558C37.7813 101.788 37.8134 100.639 38.3588 99.3236L38.6155 98.7333C39.501 96.757 40.9704 93.4395 39.3213 90.2825C38.0829 87.9084 36.0552 86.4197 33.6169 86.1117C33.3346 86.0732 33.0137 86.054 32.6929 86.054C30.5818 86.054 28.6761 86.9523 27.3414 88.3896L27.335 88.396C24.9095 91.0782 24.5309 94.7357 25.0057 96.0319C25.179 96.5003 25.4485 96.635 25.641 96.6607ZM113.889 78.9764C113.864 80.4843 114.73 81.7227 115.821 81.742C116.912 81.7612 117.829 80.5549 117.855 79.047C117.874 77.539 117.008 76.3006 115.917 76.2749C114.82 76.2557 113.909 77.462 113.889 78.9764ZM113.427 72.7714C114.48 72.6367 115.545 72.6367 116.597 72.7714C117.168 71.456 117.265 69.191 116.751 66.727C115.981 63.063 114.961 60.8429 112.831 61.1894C110.7 61.5359 110.617 64.1924 111.387 67.8563C111.817 69.916 112.58 71.6806 113.427 72.7714Z" fill="#9DA3AF"/><path d="M36.0039 119.703C44.9808 140.493 65.4885 153.249 89.5382 153.968C115.34 154.738 136.996 142.572 146.075 120.717C146.672 119.177 149.187 112.266 149.187 106.17C149.187 100.036 145.742 97.4948 143.547 97.4948C143.483 97.2574 143.053 95.6597 142.456 93.7347L141.244 90.4622C143.643 86.8496 143.682 83.622 143.368 81.7997C143.021 79.5346 142.091 77.6032 140.198 75.614C138.311 73.612 134.442 71.5715 129.007 70.0443L126.158 69.2487C126.145 69.1332 126.01 62.4919 125.882 59.6429C125.799 57.5896 125.619 54.3684 124.631 51.205C123.45 46.9123 121.39 43.1649 118.811 40.7651C125.908 33.3731 130.342 25.2239 130.329 18.2362C130.31 4.79325 113.883 0.718669 93.6384 9.15017L89.3521 10.9789C87.3501 9.00259 84.7834 6.47442 82.2232 3.95267L81.4852 3.234C58.3917 -17.0042 -13.7638 63.602 9.30416 83.1857L14.3541 87.472C13.3595 90.0579 12.7884 93.0481 12.7884 96.1794C12.7884 97.1419 12.8462 98.0852 12.9488 99.022C13.5007 104.399 16.2598 109.539 20.7322 113.517C24.9801 117.303 30.5754 119.709 36.0039 119.703ZM111.701 47.3358C111.907 47.3037 112.414 47.1176 113.427 47.1689C114.518 47.2138 115.506 47.5539 116.353 48.1122C119.754 50.3901 120.235 55.902 120.415 59.9445C120.518 62.2545 120.794 67.8242 120.89 69.4283C121.108 73.0858 122.064 73.5992 124.002 74.2408C125.086 74.6066 126.107 74.8761 127.595 75.2932C132.106 76.5701 134.776 77.8598 136.463 79.5218C137.471 80.5548 137.933 81.6585 138.08 82.7108C138.619 86.6122 135.077 91.4311 125.683 95.8073C115.417 100.594 102.975 101.807 94.3763 100.844L91.3669 100.504C84.4882 99.5738 80.5677 108.506 84.6872 114.627C87.3501 118.574 94.6009 121.14 101.845 121.14C118.477 121.14 131.253 114.011 136.008 107.845L136.393 107.3C136.624 106.947 136.431 106.754 136.136 106.953C132.254 109.622 115.006 120.229 96.5451 117.034C96.5451 117.034 94.3057 116.668 92.2588 115.866C90.6354 115.23 87.2346 113.658 86.8239 110.149C101.711 114.775 111.085 110.399 111.085 110.399L111.092 110.405C111.252 110.328 111.361 110.168 111.361 109.982C111.361 109.969 111.361 109.956 111.361 109.943C111.342 109.725 111.156 109.558 110.931 109.558C110.912 109.558 110.899 109.558 110.88 109.558C110.88 109.558 98.6755 111.374 87.1447 107.126C88.396 103.026 91.7391 104.508 96.789 104.919C98.0338 104.996 99.4904 105.041 100.96 105.041C107.633 105.041 114.075 104.085 120.191 102.346C125.394 100.825 132.183 97.8734 137.483 93.6833C139.274 97.6488 139.915 102.012 139.915 102.012C139.915 102.012 141.301 101.762 142.469 102.481C143.56 103.161 144.369 104.572 143.817 108.223C142.7 115.044 139.813 120.582 134.962 125.677C131.984 128.904 128.462 131.58 124.535 133.563L124.323 133.659C122.379 134.718 120.081 135.712 117.701 136.521L117.38 136.611C99.099 142.61 80.388 136.014 74.3563 121.853C73.92 120.929 73.5029 119.812 73.1821 118.663L73.1436 118.503C70.5769 109.167 72.7586 97.9697 79.5859 90.9178C80.003 90.4686 80.4329 89.936 80.4329 89.2751C80.4329 88.7168 80.08 88.1329 79.7784 87.7158C77.3914 84.238 69.1203 78.309 70.7886 66.836C71.9757 58.5906 79.156 52.7899 85.8357 53.1364L87.5297 53.2327C90.4301 53.4059 92.9518 53.7781 95.3452 53.8808C99.3364 54.054 102.923 53.4701 107.178 49.9088C108.608 48.7089 109.757 47.663 111.701 47.3358ZM93.7796 26.6035C94.0362 26.5843 94.1646 26.9179 93.9528 27.0783C92.9647 27.8419 92.0792 28.7338 91.322 29.7284C91.2899 29.7733 91.2707 29.8247 91.2707 29.8824C91.2707 30.0236 91.3797 30.1327 91.5209 30.1391C95.7238 30.1712 101.653 31.647 105.516 33.8287C105.772 33.9763 105.593 34.4832 105.297 34.419C99.4519 33.0715 89.8846 32.0448 79.9452 34.4832C71.071 36.6584 64.3014 40.0143 59.3606 43.6269C59.1103 43.813 58.8216 43.4858 59.0141 43.2483C64.7377 36.6007 71.7832 30.8257 78.0844 27.5853C78.3026 27.4762 78.5336 27.7136 78.4181 27.9253C77.9111 28.8365 76.9486 30.7936 76.6471 32.2694C76.6406 32.2887 76.6407 32.3079 76.6407 32.3272C76.6407 32.4683 76.7562 32.5838 76.8973 32.5838C76.9487 32.5838 77 32.571 77.0385 32.5389C80.9655 29.8503 87.7864 26.9693 93.7796 26.6035ZM11.4409 77.7187C6.98132 69.2166 16.2983 52.6808 22.8112 43.3446C38.9107 20.2703 64.0961 2.80409 75.768 5.97392C77.6609 6.51292 83.9428 13.8279 83.9428 13.8279C83.9428 13.8279 72.2837 20.328 61.4652 29.3948C46.893 40.6688 35.8884 57.057 29.2921 74.844C24.1138 75.8514 19.5516 78.8031 16.7603 82.8777C15.0984 81.4788 11.9927 78.771 11.4409 77.7187ZM30.6524 80.3752C31.4866 80.2019 32.4491 80.0993 33.4308 80.0993C33.6875 80.0993 33.9377 80.1057 34.188 80.1185H34.1495C38.8144 80.3752 45.6802 83.9685 47.2459 94.1774C48.6319 103.212 46.4246 112.42 37.9995 113.864V113.857C37.3707 113.973 36.652 114.037 35.9141 114.037C35.805 114.037 35.6959 114.037 35.5932 114.031H35.6061C27.8162 113.819 19.3976 106.773 18.5634 98.406C17.6394 89.166 22.3364 82.0563 30.6524 80.3752Z" fill="#9DA3AF"/><path d="M89.705 71.0902C87.3309 71.0902 85.1428 71.873 83.3589 73.1756C82.3387 73.9264 81.3826 74.9659 81.5109 75.5947C81.5623 75.8 81.7099 75.954 82.0692 76.0054C82.9162 76.1016 85.8486 74.613 89.2302 74.4012C91.6236 74.2536 93.6064 75.0044 95.1271 75.6781C96.6543 76.3519 97.5911 76.7882 97.9569 76.4032C98.1943 76.1594 98.1237 75.6974 97.7644 75.1006C97.0072 73.8622 95.4608 72.611 93.8181 71.9115C92.6054 71.3918 91.1873 71.0902 89.705 71.0902ZM107.248 83.885C108.589 84.5524 110.078 84.2893 110.559 83.3075C111.047 82.313 110.341 80.9719 108.994 80.311C107.652 79.6436 106.164 79.9067 105.683 80.8885C105.195 81.883 105.901 83.2241 107.248 83.885ZM92.0728 77.4427L92.0599 77.4299C90.0772 77.7507 88.9799 78.4052 88.2805 79.0148C87.6774 79.541 87.3052 80.1249 87.3052 80.5355L87.4592 80.9013L87.7864 81.0296C88.2356 81.0296 89.243 80.619 89.243 80.619C90.5649 80.0992 92.1049 79.804 93.709 79.804C94.3956 79.804 95.0629 79.8554 95.7174 79.9645L95.6469 79.958C96.6414 80.0735 97.1163 80.1377 97.328 79.7912C97.3922 79.695 97.4756 79.4768 97.2767 79.1495C96.8147 78.3924 94.8063 77.1155 92.0728 77.4427Z" fill="#9DA3AF"/><path d="M83.3333 73.1878L83.3581 73.1754L83.3643 73.1692L83.3333 73.1878Z" fill="#9DA3AF"/><path d="M12.9488 99.0925L12.9424 99.0155L12.936 98.9771L12.9488 99.0925Z" fill="#9DA3AF"/><path d="M120.563 102.262C120.441 102.301 120.306 102.307 120.184 102.346C120.146 102.359 120.101 102.378 120.069 102.384L120.563 102.262Z" fill="#9DA3AF"/>',
		);

		return $tabs;
	}

	/**
	 * Enqueue admin scripts in the settings screen.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' => array( 'view_settings_mailchimp' ),
		);

		mslib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

	/**
	 * Add mailchimp views callback.
	 *
	 * @since 1.0.0
	 *
	 * @filter ms_view_settings_edit_render_callback
	 *
	 * @param  array  $callback The current function callback.
	 * @param  string $tab The current membership rule tab.
	 * @param  array  $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_render_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view       = MS_Factory::load( 'MS_Addon_Mailchimp_View' );
			$view->data = $data;
			$callback   = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Get mailchimp api lib status.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean true on successfully loaded api, false otherwise.
	 */
	public static function get_api_status() {
		$status = false;

		try {
			$status = self::load_mailchimp_api();
		} catch ( Exception $e ) {
			self::mailchimp_log( $e->getMessage() );
		}

		return $status;
	}

	/**
	 * Load the Mailchimp API
	 *
	 * @since 1.0.0
	 *
	 * @return MWPS_Mailchimp Object
	 */
	public static function load_mailchimp_api() {
		if ( empty( self::$mailchimp_api ) || ! self::$mailchimp_api instanceof MWPS_Mailchimp ) {
			if ( ! class_exists( 'MWPS_Mailchimp' ) ) {
				require_once MS_Plugin::instance()->dir . '/lib/mailchimp-api/Mailchimp.php';
			}
			$api_key = self::$settings->get_custom_setting( 'mailchimp', 'api_key' );
			if ( ! empty( $api_key ) ) {
				$exploded    = explode( '-', $api_key );
				$data_center = end( $exploded );

				$api = new MWPS_Mailchimp( $api_key, $data_center );

				self::$mailchimp_api = $api;
			} else {
				return false;
			}
		}

		return self::$mailchimp_api;
	}

	/**
	 * Get the lists of a Mailchimp account.
	 *
	 * @return Array Lists info
	 */
	public static function get_mail_lists( $default = null ) {
		static $Mail_lists = null;

		if ( null === $default ) {
			$default = __( 'None', 'memberdash' );
		}

		if ( null === $Mail_lists ) {
			$Mail_lists = array( 0 => $default );

			if ( self::get_api_status() ) {
				$page           = 0;
				$items_per_page = 25;
				$iterations     = 0;

				do {
					$response = self::$mailchimp_api->get_lists(
						$items_per_page,
						$page
					);

					$page++;
					$iterations++;

					if ( is_wp_error( $response ) ) {
						$has_more = false;
						self::mailchimp_log( $response->get_error_message() );
					} else {
						$lists    = $response->lists; // @phpstan-ignore-line -- Response is an object.
						$has_more = count( $lists ) >= $items_per_page;
						foreach ( $lists as $list ) {
							$list                      = (array) $list;
							$Mail_lists[ $list['id'] ] = $list['name'];
						}
					}

					// Force to exit the loop after max. 100 API calls (2500 lists).
					if ( $iterations > 100 ) {
						$has_more = false;
					}
				} while ( $has_more );
			}
		}

		return $Mail_lists;
	}

	/**
	 * Check if a user is subscribed in the list
	 *
	 * @param  string $user_email
	 * @param  string $list_id
	 * @return bool True if the user is subscribed already to the list
	 */
	public static function is_user_subscribed( $user_email, $list_id ) {
		$subscribed = false;

		if ( is_email( $user_email ) && self::get_api_status() ) {

			$results = self::$mailchimp_api->check_email( $list_id, $user_email );

			if ( ! is_wp_error( $results ) ) {
				$subscribed = true;
			}
		}

		return $subscribed;
	}

	/**
	 * Subscribe a user to a Mailchimp list
	 *
	 * @since 1.0.0
	 *
	 * @param  MS_Model_Member $member
	 * @param  int             $list_id
	 */
	public static function subscribe_user( $member, $list_id ) {
		if ( is_email( $member->email ) && self::get_api_status() ) {
			$auto_opt_in = self::$settings->get_custom_setting(
				'mailchimp',
				'auto_opt_in'
			);
			$auto_opt_in = mslib3()->is_true( $auto_opt_in );

			$update = apply_filters(
				'ms_addon_mailchimp_subscribe_user_update',
				true,
				$member,
				$list_id
			);

			$subscribe_data = array(
				'email_address' => $member->email,
				'status'        => ( $auto_opt_in ) ? 'subscribed' : 'pending',
			);

			$merge_vars          = array();
			$merge_vars['FNAME'] = $member->first_name;
			$merge_vars['LNAME'] = $member->last_name;

			if ( empty( $merge_vars['FNAME'] ) ) {
				unset( $merge_vars['FNAME'] );
			}
			if ( empty( $merge_vars['LNAME'] ) ) {
				unset( $merge_vars['LNAME'] );
			}

			$merge_vars = apply_filters(
				'ms_addon_mailchimp_subscribe_user_merge_vars',
				$merge_vars,
				$member,
				$list_id
			);

			$subscribe_data['merge_fields'] = $merge_vars;

			$res = self::$mailchimp_api->subscribe( $list_id, $subscribe_data );

			if ( is_wp_error( $res ) ) {
				self::mailchimp_log( $res->get_error_message() );
			}
		}
	}

	/**
	 * Update a user data in a list
	 *
	 * @since 1.0.0
	 *
	 * @param  string $user_email
	 * @param  string $list_id
	 * @param  array  $merge_vars {
	 *      $FNAME => First name
	 *      $LNAME => Last Name
	 *  }
	 */
	public static function update_user( $user_email, $list_id, $merge_vars ) {
		if ( self::get_api_status() ) {

			$res = self::$mailchimp_api->update_subscription(
				$list_id,
				$user_email,
				$merge_vars
			);
			if ( is_wp_error( $res ) ) {
				self::mailchimp_log( $res->get_error_message() );
			}
		}
	}

	/**
	 * Unsubscribe a user from a list
	 *
	 * @since 1.0.0
	 * @param  string $user_email
	 * @param  string $list_id
	 */
	public static function unsubscribe_user( $user_email, $list_id ) {
		if ( self::get_api_status() ) {
			$res = self::$mailchimp_api->unsubscribe(
				$list_id,
				$user_email
			);
			if ( is_wp_error( $res ) ) {
				self::mailchimp_log( $res->get_error_message() );
			}
		}
	}

	/**
	 * Add additional field to show a list of mailchimp list
	 *
	 * @since 1.0.0
	 */
	public function mc_fields_for_ms( $fields, $membership, $data ) {

		$mail_list = self::get_mail_lists( __( 'Default', 'memberdash' ) );

		$fields['ms_mc'] = array(
			'id'            => 'ms_mc',
			'type'          => MS_Helper_Html::INPUT_TYPE_SELECT,
			'title'         => __( 'Mailchimp List', 'memberdash' ),
			'desc'          => __( 'You can select a list for this membership.', 'memberdash' ),
			'class'         => 'ms-mc',
			'before'        => __( 'Select a list', 'memberdash' ),
			'value'         => $membership->ms_mc,
			'field_options' => $mail_list,
			'ajax_data'     => array( 1 ),
		);

		return $fields;

	}

	/**
	 * Modify the edit membership basic settings page
	 *
	 * @since 1.0.0
	 */
	public function mc_custom_html( $html, $field, $membership ) {
		ob_start();
		?>
		<div>
			<form class="ms-form memberdash-ajax-update ms-edit-membership" data-memberdash-ajax="<?php echo esc_attr( 'save' ); ?>">
				<div class="ms-form memberdash-form memberdash-grid-8">
					<div class="col-5">
						<?php
						MS_Helper_Html::html_element( $field['name'] );
						if ( ! $membership->is_system() ) {
							MS_Helper_Html::html_element( $field['description'] );
						}
						?>
					</div>
					<div class="col-3">
						<?php
						MS_Helper_Html::html_element( $field['active'] );
						if ( ! $membership->is_system() ) {
							MS_Helper_Html::html_element( $field['public'] );
							MS_Helper_Html::html_element( $field['paid'] );
						}
						?>
					</div>
				</div>
				<div class="ms-form memberdash-form memberdash-grid-8">
					<div class="col-8">
					<?php
					if ( ! $membership->is_system() ) {
						MS_Helper_Html::html_element( $field['priority'] );
					}
					echo '<hr>';
					MS_Helper_Html::html_element( $field['ms_mc'] );
					?>
					</div>
				</div>
			</form>
		</div>
		<?php
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Save custom list for individual membership
	 *
	 * @since 1.0.0
	 *
	 * @param string              $property   Property name.
	 * @param mixed               $value      Property value.
	 * @param MS_Model_Membership $membership Membership object.
	 *
	 * @return void
	 */
	public function ms_model_membership__set_after_cb( $property, $value, $membership ) {
		if ( 'ms_mc' == $property ) {
			update_option( 'ms_mc_m_id_' . $membership->id, $value );
		}
	}

	/**
	 * Retrieve custom list for individual membership
	 *
	 * @since 1.0.0
	 */
	public function ms_model_membership__get_cb( $value, $property, $membership ) {
		if ( 'ms_mc' == $property ) {
			return get_option( 'ms_mc_m_id_' . $membership->id );
		}

		return $value;
	}
}
