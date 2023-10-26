<?php
/**
 * Add-on for MailPoet
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

/**
 * Add-on for MailPoet
 *
 * @since 1.0.0
 */
class MS_Addon_MailPoet extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.0.0
	 */
	const ID = 'addon_mailpoet';

	/**
	 * Ajax action identifier used in the Membership settings.
	 *
	 * @since 1.0.0
	 */
	const AJAX_ACTION_SAVE_LIST_ID = 'addon_mailpoet_save_list_id';

	/**
	 * Checks if the current Add-on is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_active() {
		if ( ! self::mailpoet_active()
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
			return; }
		$Init_Done = true;

		if ( self::is_active() ) {

			$this->check_requirements();

			$this->add_filter(
				'ms_controller_membership_tabs',
				'ms_mailpoet_tab',
				5,
				3
			);

			$this->add_action(
				'ms_view_membership_edit_render_callback',
				'ms_mailpoet_tab_view',
				10,
				3
			);

			// Ajax handler that saves a MailPoet List id.
			$this->add_ajax_action(
				self::AJAX_ACTION_SAVE_LIST_ID,
				'ms_mailpoet_ajax_save_list_id'
			);

			$this->add_action(
				'ms_model_relationship_create_ms_relationship_after',
				'ms_mailpoet_subscribe_to_list',
				10,
				5
			);

		} else {
			$this->add_action( 'ms_model_addon_enable', 'enable_addon' );
		}
	}

	public function ms_mailpoet_subscribe_to_list( $subscription, $membership_id, $user_id, $gateway_id, $move_from_id ) {

		if ( ! empty( $subscription ) ) {

			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );

			if ( $membership->is_valid() ) {
				$list_id = $this->ms_mailpoet_get_membership_list_id( $membership );
				$user    = get_userdata( $user_id );
				if ( $user ) {
					$mailpoet_api = \MailPoet\API\API::MP( 'v1' );
					$subscriber   = array(
						'email'      => $user->user_email,
						'first_name' => $user->first_name,
						'last_name'  => $user->last_name,
					);
					$option       = array( 'send_confirmation_email' => true );
					$list_ids     = array( $list_id );
					try {
							$get_subscriber = $mailpoet_api->getSubscriber( $subscriber['email'] );
						if ( ! $get_subscriber ) {
							// Subscriber doesn't exist let's create one
							$mailpoet_api->addSubscriber( $subscriber, $list_ids );
						} else {
							// In case subscriber exists just add him to new lists
							$mailpoet_api->subscribeToLists( $subscriber['email'], $list_ids, $option );
						}
					} catch ( \Exception $e ) {
						$error_message = $e->getMessage();
					}
				}
			}
		}

	}

	public function ms_mailpoet_get_membership_list_id( $membership ) {

		return $membership->get_custom_data( 'mailpoet_list' );

	}


	/**
	 * Ajax handler that saves a membership attribute.
	 *
	 * @since 1.0.0
	 */
	public function ms_mailpoet_ajax_save_list_id() {

		$res    = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		$fields = array( 'field', 'membership_id' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			$id         = intval( $_POST['membership_id'] );
			$value      = $_POST['value'];
			$membership = MS_Factory::load( 'MS_Model_Membership', $id );

			if ( $membership->is_valid() ) {
				$membership->set_custom_data(
					'mailpoet_list',
					$value
				);
				$membership->save();
			}
			$res = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}

		echo $res; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public function ms_mailpoet_list() {

		$mailpoet_api = \MailPoet\API\API::MP( 'v1' );
		// Get available list so that a subscriber can choose in which to subscribe
		$lists        = $mailpoet_api->getLists();
		$mailpoetList = array();

		if ( ! empty( $lists ) ) {

			$mailpoetList[''] = 'Select List';

			foreach ( $lists as $list ) {
				$mailpoetList[ $list['id'] ] = $list['name'];
			}
		} else {
			$mailpoetList[''] = 'No List Available';
		}

		return $mailpoetList;
	}

	/**
	 * Renders the MailPoet tab.
	 *
	 * @since 1.0.0
	 *
	 * @param callable|array<mixed> $callback The original callback.
	 * @param string                $tab      The current tab.
	 * @param array<mixed>          $data     The data to pass to the view.
	 *
	 * @return callable|array<mixed> The updated callback.
	 */
	public function ms_mailpoet_tab_view( $callback, $tab, $data ) {
		if ( $tab === self::ID ) {
			$view       = MS_Factory::load( 'MS_Addon_Mailpoet_View_List' );
			$view->data = $data;
			$callback   = array( $view, 'render_tab' );
		}

		return $callback;
	}

	public function ms_mailpoet_tab( $tabs ) {
		$tabs[ self::ID ] = array(
			'title' => __( 'MailPoet', 'memberdash' ),
		);

		return $tabs;
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
			'icon'        => 'dashicons dashicons-email-alt',
			'name'        => __( 'MailPoet Integration', 'memberdash' ),
			'description' => __( 'Use MailPoet to enroll user in selected MailPoet list.', 'memberdash' ),
		);

		if ( ! self::mailpoet_active() ) {
			$list[ self::ID ]->description .= sprintf(
				'<br /><b>%s</b>',
				__( 'Activate MailPoet to use this Add-on', 'memberdash' )
			);
			$list[ self::ID ]->action       = '-';
		}

		return $list;
	}

	/**
	 * Checks if the Mailpoet plugin is active.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function mailpoet_active() {
		if ( class_exists( \MailPoet\API\API::class ) ) {
			return defined( 'MAILPOET_VERSION' );
		}
		return false;
	}

	/**
	 * Function is triggered every time an add-on is enabled.
	 *
	 * We flush the Factory Cache when the Mailpoet Add-on is enabled so all strings
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
	 * Check if Mailpoet is up to date and required modules are active.
	 *
	 * @since 1.0.0
	 */
	protected function check_requirements() {
		if ( ! self::mailpoet_active() ) {
			mslib3()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'MailPoet not active!', 'memberdash' ),
					__( 'Heads up: Activate the Mailpoet plugin to enable the Mailpoet integration.', 'memberdash' )
				),
				'err'
			);
			return;
		}

		if ( version_compare( MAILPOET_VERSION, '4.7.0', 'lt' ) ) {
			mslib3()->ui->admin_message(
				sprintf(
					'<b>%s</b><br>%s',
					__( 'Great, you\'re using Mailpoet!', 'memberdash' ),
					__( 'Heads up: Your version of Mailpoet is outdated. Please update Mailpoet to version <b>4.7.0 or higher</b> to enable the Mailpoet integration.', 'memberdash' )
				),
				'err'
			);
			return;
		}
	}
}
