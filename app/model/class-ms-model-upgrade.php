<?php
/**
 * Upgrade DB model.
 *
 * Manages DB upgrading.
 *
 * IMPORTANT: Make sure that the snapshot_data() function is up-to-date!
 * Things that are missed during back-up might be lost forever...
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Model
 */
class MS_Model_Upgrade extends MS_Model {

	/**
	 * Initialize upgrading check.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::update();

		MS_Factory::load( 'MS_Model_Upgrade' );

		// This function is intended for development/testing only!
		self::maybe_restore();

		// This is a hidden feature available in the Settings > General page.
		add_action( 'init', array( __CLASS__, 'maybe_reset' ) );

		// This is a hidden feature available in Settings > General page.
		add_action( 'init', array( __CLASS__, 'maybe_fix_stripe_subs' ) );

		// Prevent WordPress from updating the Membership plugin when the
		// WPSATCHEL Dashboard is disabled.
		if ( ! class_exists( 'WPSATCHEL_Dashboard' ) ) {
			add_filter(
				'plugins_api',
				array( __CLASS__, 'no_dash_plugins_api' ),
				101,
				3
			);
			add_filter(
				'site_transient_update_plugins',
				array( __CLASS__, 'no_dash_update_plugins' )
			);
		}

		do_action( 'ms_model_upgrade_init' );
	}

	/**
	 * Upgrade database.
	 *
	 * @since 1.0.0
	 * @param  bool $force Also execute update logic when version did not change.
	 */
	public static function update( $force = false, $rewrite = false ) {
		static $Done = false;

		if ( $Done && ! $force ) {
			return; }

		// Migration handler has its own valid_user check.
		self::check_migration_handler();

		// Updates are only triggered from Admin-Side by an Admin user.
		if ( ! self::valid_user() ) {
			return; }

		// Check for correct network-wide protection setup.
		self::check_settings();

		$settings    = MS_Factory::load( 'MS_Model_Settings' );
		$old_version = $settings->version; // Old: The version in DB.
		$new_version = MS_Plugin::instance()->version; // New: Version in file.

		$is_new_setup = empty( $old_version );

		// Compare current src version to DB version:
		// We only do UP-grades but no DOWN-grades!
		if ( $old_version ) {
			$version_changed = version_compare( $old_version, $new_version, 'lt' );
		} else {
			$version_changed = true;
		}

		if ( $force || $version_changed ) {
			$Done = true;
			$msg  = array();

			/*
			 * ----- General update logic, executed on every update ------------
			 */

			do_action(
				'ms_model_upgrade_before_update',
				$settings,
				$old_version,
				$new_version,
				$force
			);

			// Prepare the Update message.
			if ( ! $version_changed ) {
				$msg[] = sprintf(
					__( '<strong>MemberDash</strong> is set up for version %1$s!', 'memberdash' ),
					$new_version
				);
			} else {
				$msg[] = sprintf(
					__( '<strong>MemberDash</strong> was updated to version %1$s!', 'memberdash' ),
					$new_version
				);
			}

			// Every time the plugin is updated we clear the cache.
			MS_Factory::clear();

			// Create missing Membership pages.
			$new_pages = MS_Model_Pages::create_missing_pages();

			if ( ! empty( $new_pages ) ) {
				$msg[] = sprintf(
					__( 'New Membership pages created: "%1$s".', 'memberdash' ),
					implode( '", "', $new_pages )
				);
			}

			// Remove an old version of Protected Content
			// TODO: REMOVE THIS BLOCK/FUNCTION END OF 2015
			if ( $version_changed ) {
				self::remove_old_copy();
			}

			// Note: We do not create menu items on upgrade! Users might have
			// intentionally removed the items from the menu...

			/*
			 * ----- Version-Specific update logic -----------------------------
			 */

			// Upgrade from a 1.0.0.x version to 1.0.1.0 or higher
			if ( version_compare( $old_version, '1.0.1.0', 'lt' ) ) {
				self::_upgrade_1_0_1_0();
			}

			// Upgrade from 1.0.1.0 version to 1.0.1.1 or higher
			if ( version_compare( $old_version, '1.0.1.1', 'lt' ) ) {
				self::_upgrade_1_0_1_1();
			}

			// Upgrade from 1.0.1.x version to 1.0.2.0 or higher
			if ( version_compare( $old_version, '1.0.2.0', 'lt' ) ) {
				self::_upgrade_1_0_2_0();
			}

			// Upgrade from 1.0.2.x version to 1.0.2.4 or higher
			if ( version_compare( $old_version, '1.0.2.4', 'lt' ) ) {
				self::_upgrade_1_0_2_4();
			}

			/*
			 * ----- General update logic, executed on every update ------------
			 */

			$settings->version = $new_version;
			$settings->save();

			// Display a message after the page is reloaded.
			if ( ! $is_new_setup ) {
				mslib3()->ui->admin_message( implode( '<br>', $msg ), '', '', 'ms-update' );
			}

			do_action(
				'ms_model_upgrade_after_update',
				$settings,
				$old_version,
				$new_version,
				$force
			);

			$addons = MS_Factory::load( 'MS_Model_Addon' );
			$addons->flush_list();

			// This will reload the current page.
			MS_Plugin::flush_rewrite_rules();

			if ( $rewrite ) {
				flush_rewrite_rules();
			}
		}
	}

	/**
	 * When Dashboard is disabled this function will tell WordPress
	 * to not update Membership.
	 *
	 * PRO ONLY!
	 *
	 * @since 1.0.0
	 * @param  mixed  $res False: Update plugin / True: WP ignores plugin.
	 * @param  string $action
	 * @param  object $args
	 * @return mixed
	 */
	public static function no_dash_plugins_api( $res, $action, $args ) {
		if ( ! empty( $args ) && is_object( $args ) ) {
			if ( isset( $args->slug ) ) {
				if ( 'memberdash_install-1003656' == $args->slug ) {
					$res = true;
				} elseif ( 'memberdash_install-130' == $args->slug ) {
					$res = true;
				} elseif ( 'membership' == $args->slug ) {
					$res = true;
				}
			}
		}

		return $res;
	}

	/**
	 * Filter the site transient value right before it is returned by the
	 * get_site_transient function.
	 * We mark the Membership plugin for "no update".
	 *
	 * PRO ONLY!
	 *
	 * @since 1.0.0
	 * @param  object $data
	 * @return object
	 */
	public static function no_dash_update_plugins( $data ) {
		if ( ! empty( $data ) && is_object( $data ) && ! empty( $data->response ) ) {
			if ( isset( $data->response['membership/membership.php'] ) ) {
				$data->no_update['membership/membership.php'] = $data->response['membership/membership.php'];
				unset( $data->response['membership/membership.php'] );
			}
		}

		return $data;
	}



	/**
	 * Upgrade from any 1.0.0.x version to a higher version.
	 */
	private static function _upgrade_1_0_1_0() {
		mslib3()->updates->clear();

		/*
		 * The "is_member" flag of users was not correctly saved when a
		 * subscription was added via the MWPS > Members page.
		 * Fix this now.
		 */
		{
			global $wpdb;
			$sql    = "
			SELECT DISTINCT usr.user_id
			FROM
				{$wpdb->posts} post
				LEFT JOIN {$wpdb->usermeta} usr
					ON usr.user_id = post.post_author
					AND usr.meta_key = 'ms_is_member'
			WHERE
				post_type = 'ms_relationship'
				AND (usr.meta_value IS NULL OR usr.meta_value != 1);
			";
			$result = $wpdb->get_col( $sql );
		foreach ( $result as $user_id ) {
			mslib3()->updates->add( 'update_user_meta', $user_id, 'ms_is_member', true );
		}
		}

		// Execute all queued actions!
		mslib3()->updates->plugin( 'membership' );
		mslib3()->updates->execute();
	}

	/**
	 * Upgrade from 1.0.1.0 version to a higher version.
	 */
	private static function _upgrade_1_0_1_1() {
		mslib3()->updates->clear();

		/*
		 * A bug in 1.0.1 created multiple copies of email templates.
		 * This update block will delete the duplicates again.
		 */
		{
			global $wpdb;
			$sql = "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE
				post_type = 'ms_communication'
				AND ID NOT IN (
				SELECT
					MIN( p.ID ) ID
				FROM
					{$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} m1
					ON m1.post_id = p.ID AND m1.meta_key = 'type'
				WHERE
					p.post_type = 'ms_communication'
					AND LENGTH( m1.meta_value ) > 0
				GROUP BY
					m1.meta_value,
					p.post_parent
				);
			";
			$ids = $wpdb->get_col( $sql );

		foreach ( $ids as $id ) {
			mslib3()->updates->add( 'wp_delete_post', $id, true );
		}
		}

		// Execute all queued actions!
		mslib3()->updates->plugin( 'membership' );
		mslib3()->updates->execute();
	}

	/**
	 * Upgrade from 1.0.1.1 version to a higher version.
	 */
	private static function _upgrade_1_0_2_0() {
		mslib3()->updates->clear();

		/*
		 * Transaction logs are a bit messed up because some meta-keys have an
		 * underscore as prefix while other do not have it. This query removes
		 * the underscore from all transaction-log meta key names.
		 */
		{
			global $wpdb;
			$sql = "
			UPDATE {$wpdb->postmeta}
			INNER JOIN {$wpdb->posts} ON {$wpdb->postmeta}.post_id={$wpdb->posts}.ID
			SET meta_key = SUBSTR(meta_key,2)
			WHERE
				{$wpdb->posts}.post_type = 'ms_transaction_log'
				AND SUBSTR({$wpdb->postmeta}.meta_key,1,1) = '_'
			";
			$ids = $wpdb->query( $sql );
		}
	}

	/**
	 * Upgrade from 1.0.2.x version to 1.0.2.4 version.
	 */
	private static function _upgrade_1_0_2_4() {
		mslib3()->updates->clear();

		/*
		 * Transaction matching of M1 payments with MWPS memberships has improved
		 * so a single MWPS membership can be matched with multiple transaction
		 * types.
		 */
		{
			$memberships = MS_Model_Membership::get_memberships();
		foreach ( $memberships as $membership ) {
			$source_id = $membership->source_id;
			if ( empty( $source_id ) ) {
				continue; }

			$data = mslib3()->array->get(
				$membership->get_custom_data( 'matching' )
			);

			if ( ! isset( $data['m1'] ) ) {
				$data['m1'] = array(); }
			$data['m1']   = mslib3()->array->get( $data['m1'] );
			$data['m1'][] = $source_id;
			$membership->set_custom_data( 'matching', $data );
			$membership->save();
		}
		}
	}


	/**
	 * Used when upgrading from Membership to MWPS. If both Membership and
	 * Protected Content are installed when upgrading then the old
	 * "protected-content" folder may survive the upgrade and needs to be
	 * manually removed.
	 *
	 * @since 1.0.0
	 */
	private static function remove_old_copy() {
		$new_dir     = WP_PLUGIN_DIR . '/membership';
		$old_dir     = WP_PLUGIN_DIR . '/protected-content';
		$old_plugins = array(
			'protected-content/protected-content.php',
			'membership/membershippremium.php',
		);
		$new_plugin  = plugin_basename( MS_Plugin::instance()->file );

		// Make sure that the current plugin is the official MWPS one.
		if ( false === strpos( MS_Plugin::instance()->dir, $new_dir ) ) {
			// Cancel: This plugin is not the official plugin (maybe a backup or beta version)

			if ( false !== strpos( MS_Plugin::instance()->dir, $old_dir ) ) {
				mslib3()->ui->admin_message(
					__( '<b>Upgrade warning</b>:<br>The Membership plugin is installed in an deprecated folder. Some users did report issues when the plugin is installed in this directory.<br>To fix this issue please follow these steps:<br><br>1. Delete* the old Membership Premium plugin if it is still installed.<br>2. Delete* the Membership plugin.<br>3. Re-install Membership from the Dashboard - your existing data is not affected by this.<br><br>*) <em>Only deactivating the plugins does not work, you have to delete them.</em>', 'memberdash' ),
					'error'
				);
			}

			return;
		}

		// 1. See if there is a old copy of the plugin directory. Delete it.
		if ( is_dir( $old_dir ) && is_file( $old_dir . '/protected-content.php' ) ) {
			// Looks like the old version of this plugin is still installed. Remove it.
			try {
				unlink( $old_dir . '/protected-content.php' );
				array_map( 'unlink', glob( "$old_dir/*.*" ) );
				rmdir( $old_dir );
			} catch ( Exception $e ) {
				// Something went wrong when removing the old plugin.
			}
		}

		// 2. See if WordPress uses an old plugin in the DB. Update it.
		if ( is_multisite() ) {
			$global_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
			foreach ( $global_plugins as $key => $the_path ) {
				if ( in_array( $the_path, $old_plugins ) ) {
					$global_plugins[ $key ] = $new_plugin;
				}
			}
			update_site_option( 'active_sitewide_plugins', $global_plugins );
		}

		$site_plugins = (array) get_option( 'active_plugins', array() );
		foreach ( $site_plugins as $key => $the_path ) {
			if ( in_array( $the_path, $old_plugins ) ) {
				$site_plugins[ $key ] = $new_plugin;
			}
		}
		update_option( 'active_plugins', $site_plugins );
	}



	/**
	 * Completely whipe all Membership data from Database.
	 *
	 * Note: This function is not used currently...
	 *
	 * @since 1.0.0
	 */
	private static function cleanup_db() {
		global $wpdb;
		$sql       = array();
		$trash_ids = array();

		// Delete membership meta-data from users.
		$users = MS_Model_Member::get_members();
		foreach ( $users as $user ) {
			$user->delete_all_membership_usermeta();
			$user->save();
		}

		// Determine IDs of Membership Pages.
		$page_types = MS_Model_Pages::get_page_types();
		foreach ( $page_types as $type => $name ) {
			$page_id     = MS_Model_Pages::get_setting( $type );
			$trash_ids[] = $page_id;
		}

		/**
		 * Delete all plugin settings.
		 * Settings are saved by classes that extend MS_Model_option
		 */
		foreach ( MS_Model_Gateway::get_gateways() as $option ) {
			$option->delete();
		}
		MS_Factory::load( 'MS_Model_Addon' )->delete();
		MS_Factory::load( 'MS_Model_Pages' )->delete();
		MS_Factory::load( 'MS_Model_Settings' )->delete();

		/**
		 * Delete transient data
		 * Transient data is saved by classed that extend MS_Model_Transient
		 */
		MS_Factory::load( 'MS_Model_Simulate' )->delete();

		/**
		 * Delete all plugin content.
		 * Content is saved by classes that extend MS_Model_CustomPostType
		 */
		$ms_posttypes = array(
			MS_Model_Communication::get_post_type(),
			MS_Model_Event::get_post_type(),
			MS_Model_Invoice::get_post_type(),
			MS_Model_Transactionlog::get_post_type(),
			MS_Model_Membership::get_post_type(),
			MS_Model_Relationship::get_post_type(),
			MS_Addon_Coupon_Model::get_post_type(),
			MS_Addon_Invitation_Model::get_post_type(),
		);

		foreach ( $ms_posttypes as $type ) {
			$sql[] = $wpdb->prepare(
				"DELETE FROM $wpdb->posts WHERE post_type = %s;",
				$type
			);
		}

		// Remove orphaned post-metadata.
		$sql[] = "
		DELETE FROM $wpdb->postmeta
		WHERE NOT EXISTS (
			SELECT 1 FROM $wpdb->posts tmp WHERE tmp.ID = post_id
		);
		";

		// Clear all WP transient cache.
		$sql[] = "
		DELETE FROM $wpdb->options
		WHERE option_name LIKE '_transient_%';
		";

		// clear all plugin usermeta
		$sql[] = "DELETE FROM $wpdb->usermeta
              WHERE meta_key LIKE '_ms_%'
                    OR meta_key LIKE 'ms_%'";

		foreach ( $sql as $s ) {
			$wpdb->query( $s );
		}

		// Move Membership pages to trash.
		foreach ( $trash_ids as $id ) {
			wp_delete_post( $id, true );
		}

		// Clear all data from WP Object cache.
		MS_Helper_Cache::flush_cache();

		// Redirect to the main page.
		wp_safe_redirect( MS_Controller_Plugin::get_admin_url() );
		exit;
	}

	/**
	 * Fix subscriptions with issue where Stripe payments would not go active.
	 *
	 * @since 1.0.3.4
	 */
	private static function fix_subs() {
		/**
		 * Because the issue is only with recurring payments, we get all memberships
		 * with a price and recurring payment.
		 */
		$paid_memberships = MS_Model_Membership::get_memberships(
			array(
				'meta_query' => array(
					array(
						'key'     => 'price',
						'value'   => 0,
						'compare' => '>',
					),
					array(
						'key'   => 'payment_type',
						'value' => 'recurring',
					),
				),
			)
		);

		// Loop over the memberships.
		foreach ( $paid_memberships as $membership ) {

			// Bug only applies to Stripe.
			if ( ! $membership->can_use_gateway( 'stripeplan' ) ) {
				return;
			}

			// Get all the members in the selected membership.
			$members = $membership->get_members(
				array(
					'status' => 'all',
				)
			);

			// Loop through all the members.
			foreach ( $members as $member ) {
				$subscription = $member->get_subscription( $membership->id );

				// Check if the bug is present.
				if ( $subscription && ( $subscription->current_invoice_number < count( $subscription->get_invoices() ) ) ) {
					$subscription->current_invoice_number = count( $subscription->get_invoices() );
					$subscription->save();
				}
			}
		}

	}

	/**
	 * Checks several settings to make sure that MWPS is fully working.
	 *
	 * A) Makes sure that network-wide protection works by ensuring that the
	 *    plugin is also network-activated.
	 * B) Checks if the permalink structure uses the post-name
	 *
	 * @since 1.0.0
	 */
	private static function check_settings() {
		static $Setting_Check_Done = false;

		if ( ! $Setting_Check_Done ) {
			$Setting_Check_Done = true;

			// A) Check plugin activation in network-wide mode.
			if ( is_multisite() ) {

				if ( MS_Plugin::is_network_wide() ) {
					// This function does not exist in network admin
					if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
						require_once ABSPATH . '/wp-admin/includes/plugin.php';
					}

					if ( ! is_plugin_active_for_network( MEMBERDASH_PLUGIN ) ) {
						activate_plugin( MEMBERDASH_PLUGIN, null, true );
						mslib3()->ui->admin_message(
							__( 'Info: Membership is not activated network-wide', 'memberdash' )
						);
					}
				}
			}

			if ( false === ( get_option( 'permalink_structure' ) ) ) {
				mslib3()->ui->admin_message(
					sprintf(
						__( 'You need to enable %1$sPretty Permalinks%2$s in your %3$sPermalink structure%4$s to ensure Membership is working correctly.', 'memberdash' ),
						'<strong>',
						'</strong>',
						'<a href="' . admin_url( 'options-permalink.php' ) . '">',
						'</a>'
					),
					'err'
				);
			}
		}

	}

	/**
	 * This function checks if we arrive here after a migration, i.e. after the
	 * user updated Membership Premium or Protected Content to MWPS
	 *
	 * @since 1.0.0
	 */
	private static function check_migration_handler() {
		$migrate  = '';
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		// Check Migration from old Membership plugin.
		$option_m1      = '_memberdash_update_to_mwps';
		$option_m1_free = '_wporg_update_to_mwps';
		$from_m1        = get_site_option( $option_m1 );
		$from_m1_free   = get_site_option( $option_m1_free );

		if ( $from_m1 || $from_m1_free ) {
			$migrate = 'm1';

			delete_site_option( $option_m1 );
			delete_site_option( $option_m1_free );
			$settings->set_special_view( 'MS_View_MigrationM1' );
		}

		$view = $settings->get_special_view();

		if ( $migrate || 'MS_View_MigrationM1' == $view ) {
			if ( ! empty( $_REQUEST['skip_import'] ) ) {
				$settings->reset_special_view();
				wp_safe_redirect(
					esc_url_raw( remove_query_arg( array( 'skip_import' ) ) )
				);
				exit;
			} else {
				$settings->set_special_view( 'MS_View_MigrationM1' );

				// Complete the migration when the import is done.
				add_action(
					'ms_import_action_done',
					array( 'MS_Model_Settings', 'reset_special_view' )
				);
			}
		}
	}

	/**
	 * Returns a secure token to trigger advanced admin actions like db-reset
	 * or restoring a snapshot.
	 *
	 * - Only one token is valid at any given time.
	 * - Each token has a timeout of max. 120 seconds.
	 * - Each token can be used once only.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param  string $action Like a nonce, this is the action to execute.
	 * @return array Intended usage: add_query_param( $token, $url )
	 */
	public static function get_token( $action ) {
		if ( ! is_user_logged_in() ) {
			return array(); }
		if ( ! is_admin() ) {
			return array(); }

		$one_time_key = uniqid();
		MS_Factory::set_transient( 'ms_one_time_key-' . $action, $one_time_key, 120 );

		// Token is valid for 86 seconds because of usage of date('B')
		$plain = $action . '-' . ( new Datetime( 'now', wp_timezone() ) )->format( 'B' ) . ':' . get_current_user_id() . '-' . $one_time_key;
		$token = array( 'ms_token' => wp_create_nonce( $plain ) );
		return $token;
	}

	/**
	 * Verifies the admin token in the $_GET collection
	 *
	 * $_GET['ms_token'] must match the current ms_token
	 * $_POST['confirm'] must have value 'yes'
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param  string $action Like a nonce, this is the action to execute.
	 * @return bool
	 */
	private static function verify_token( $action ) {
		if ( ! self::valid_user() ) {
			return false; }

		if ( empty( $_GET['ms_token'] ) ) {
			return false; }
		$get_token = $_GET['ms_token'];

		if ( empty( $_POST['confirm'] ) ) {
			return false; }
		if ( 'yes' != $_POST['confirm'] ) {
			return false; }

		$one_time_key = MS_Factory::get_transient( 'ms_one_time_key-' . $action );
		MS_Factory::delete_transient( 'ms_one_time_key-' . $action );
		if ( empty( $one_time_key ) ) {
			return false; }

		// We verify the current and the previous beat
		$plain_token_1 = $action . '-' . ( new Datetime( 'now', wp_timezone() ) )->format( 'B' ) . ':' . get_current_user_id() . '-' . $one_time_key;
		$plain_token_2 = $action . '-' . ( ( new Datetime( 'now', wp_timezone() ) )->format( 'B' ) - 1 ) . ':' . get_current_user_id() . '-' . $one_time_key;

		if ( wp_verify_nonce( $get_token, $plain_token_1 ) ) {
			return true; }
		if ( wp_verify_nonce( $get_token, $plain_token_2 ) ) {
			return true; }

		return false;
	}

	/**
	 * Verifies the following conditions:
	 * - Current user is logged in and has admin permissions
	 * - The request is an wp-admin request
	 * - The request is not an Ajax call
	 *
	 * @since 1.0.0
	 * @return bool True if all conditions are true
	 */
	private static function valid_user() {
		/**
		 * Determine user_id from request cookies.
		 *
		 * @see wp-includes/pluggable.php wp_currentuserinfo()
		 */
		$user_id = apply_filters( 'determine_current_user', false );

		if ( ! $user_id ) {
			return false; }
		if ( ! is_admin() ) {
			return false; }
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false; }
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return false; }
		/**
		 * BuddyPress notices hack
		 */
		$bp = function_exists( 'buddypress' ) ? buddypress() : null;
		if ( null != $bp ) {
			if ( ! did_action( 'init' ) ) {
				return false; }
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return false; }

		return true;
	}

	/**
	 * Checks if valid reset-instructions are present. If yes, then wipe the
	 * plugin settings.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_reset() {
		static $Reset_Done = false;

		if ( ! $Reset_Done ) {
			$Reset_Done = true;
			if ( ! self::verify_token( 'reset' ) ) {
				return false;
			}
			self::cleanup_db();
			delete_site_transient( 'ms_model_simulate-network' );
			$msg = __( 'Membership successfully reset!', 'memberdash' );
			mslib3()->ui->admin_message( $msg );

			wp_safe_redirect( MS_Controller_Plugin::get_admin_url( 'MENU_SLUG' ) );
			exit;
		}
	}

	/**
	 * Checks if valid fixsub-instructions are present. If yes, then fix the
	 * plugin subscriptions.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_fix_stripe_subs() {
		static $Fix_Done = false;

		if ( ! $Fix_Done ) {
			$Fix_Done = true;
			if ( ! self::verify_token( 'fixsub' ) ) {
				return false; }

			self::fix_subs();
			$msg = __( 'Membership subscriptions fixed!', 'memberdash' );
			mslib3()->ui->admin_message( $msg );

			wp_safe_redirect( MS_Controller_Plugin::get_admin_url( 'MENU_SLUG' ) );
			exit;
		}
	}

	/**
	 * Checks if valid restore-options are specified. If they are, the snapshot
	 * will be restored.
	 *
	 * @since 1.0.0
	 * @internal This function is intended for development/testing only!
	 */
	private static function maybe_restore() {
		static $Restore_Done = false;

		if ( ! $Restore_Done ) {
			$Restore_Done = true;
			if ( empty( $_POST['restore_snapshot'] ) ) {
				return false; }
			$snapshot = $_POST['restore_snapshot'];

			if ( ! self::verify_token( 'restore' ) ) {
				return false; }

			mslib3()->updates->plugin( 'membership' );
			if ( mslib3()->updates->restore( $snapshot ) ) {
				printf(
					'<p>' .
					esc_html__( 'The Membership Snapshot "%s" was restored!', 'memberdash' ) .
					'</p>',
					esc_attr( $snapshot )
				);

				printf(
					'<p><b>' .
					esc_html__( 'To prevent auto-updating the DB again we stop here!', 'memberdash' ) .
					'</b></p>'
				);

				printf(
					'<p>' .
					wp_kses_post( __( 'You now have the option to <br />(A) downgrade the plugin to an earlier version via FTP or <br />(B) to %1$sre-run the upgrade process%2$s.', 'memberdash' ) ) .
					'</p>',
					'<a href="' . esc_url( MS_Controller_Plugin::get_admin_url( 'MENU_SLUG' ) ) . '">',
					'</a>'
				);

				wp_die( '', 'Snapshot Restored' );
			}
		}
	}

};
