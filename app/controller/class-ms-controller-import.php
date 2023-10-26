<?php
/**
 * Controller.
 *
 * @package MemberDash
 */

/**
 * Class that handles Import/Export functions.
 *
 * @since 1.0.0
 * @package MemberDash
 * @subpackage Controller
 */
class MS_Controller_Import extends MS_Controller {

	// Action definitions.
	const ACTION_EXPORT      = 'export';
	const ACTION_PREVIEW     = 'preview';
	const ACTION_IMPORT_USER = 'import_users';

	// Ajax action: Import data.
	const AJAX_ACTION_IMPORT       = 'ms_import';
	const AJAX_ACTION_IMPORT_USERS = 'ms_import_users';

	// Ajax action: Save an automatic transaction matching (Billings page).
	const AJAX_ACTION_MATCH = 'ms_save_matching';

	// Ajax action: Retry to process a single transaction (Billings page).
	const AJAX_ACTION_RETRY = 'transaction_retry';

	/**
	 * Prepare the Import manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_ajax_action(
			self::AJAX_ACTION_IMPORT,
			'ajax_action_import'
		);

		$this->add_ajax_action(
			self::AJAX_ACTION_IMPORT_USERS,
			'ajax_action_import_users'
		);

		$this->add_ajax_action(
			self::AJAX_ACTION_MATCH,
			'ajax_action_match'
		);

		$this->add_ajax_action(
			self::AJAX_ACTION_RETRY,
			'ajax_action_retry'
		);
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {
		$tab_key = 'import'; // Should be unique plugin-wide value of `&tab=`.

		$this->run_action(
			'ms_controller_settings_enqueue_scripts_' . $tab_key,
			'enqueue_scripts'
		);
	}

	/**
	 * Handles the matching of transaction details with a membership.
	 *
	 * Expected JSON output:
	 * {
	 *     success [bool]
	 *     data [object] {
	 *         message [string]
	 *     }
	 * }
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_match() {
		if ( ! $this->is_admin_user() ) {
			return;
		}

		$fields_match = array( 'match_with', 'source', 'source_id' );

		// Save details of a single invoice.
		if ( $this->verify_nonce()
			&& self::validate_required( $fields_match )
		) {
			$source    = $_REQUEST['source'];
			$source_id = $_REQUEST['source_id'];
			$match_id  = $_REQUEST['match_with'];

			if ( MS_Model_Import::match_with_source( $match_id, $source_id, $source ) ) {
				wp_send_json_success(
					array(
						'message' => __( 'Matching details saved. Future transactions are automatically processed from now on!', 'memberdash' ),
					)
				);
			}
		}

		wp_send_json_error();
		exit;
	}

	/**
	 * Retries to process a single error-state transaction.
	 *
	 * Expected JSON output:
	 * {
	 *     success [bool]
	 *     data [object] {
	 *         desc [string]
	 *         status [string]
	 *     }
	 * }
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_retry() {
		if ( ! $this->is_admin_user() ) {
			return;
		}

		$fields_retry = array( 'id' );

		// Save details of a single invoice.
		if ( $this->verify_nonce()
			&& self::validate_required( $fields_retry )
		) {
			$log_id = intval( $_POST['id'] );

			MS_Model_Import::retry_to_process( $log_id );

			$log = MS_Factory::load( 'MS_Model_Transactionlog', $log_id );
			wp_send_json_success(
				array(
					'desc'  => $log->description,
					'state' => $log->state,
				)
			);
		}

		wp_send_json_error(
			array(
				'desc'   => '',
				'status' => '',
			)
		);
	}

	/**
	 * Handles an import batch that is sent via ajax.
	 *
	 * One batch includes multiple import commands that are to be processed in
	 * the specified order.
	 *
	 * Expected output:
	 *   OK:<number of successful commands>
	 *   ERR
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_import() {
		$res     = 'ERR';
		$success = 0;

		if ( ! isset( $_POST['items'] ) || ! isset( $_POST['source'] ) ) {
			echo 'ERR';
			exit;
		}

		$batch  = $_POST['items'];
		$source = $_POST['source'];

		$res = 'OK';
		foreach ( $batch as $item ) {
			if ( $this->process_item( $item, $source ) ) {
				$success++;
			}
		}

		echo esc_html( $res . ':' . $success );
		exit;
	}

	/**
	 * Handles an import batch of users that is sent via ajax.
	 *
	 * One batch includes multiple import commands that are to be processed in
	 * the specified order.
	 *
	 * Expected output:
	 *   OK:<number of successful commands>
	 *   ERR
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_import_users() {
		$res     = 'ERR';
		$success = 0;

		if ( ! isset( $_POST['items'] ) ) {
			echo 'ERR';
			exit;
		}

		$batch = $_POST['items'];

		$res = 'OK';
		foreach ( $batch as $item ) {
			if ( $this->process_user( $item ) ) {
				$success++;
			}
		}

		echo esc_html( $res . ':' . $success );
		exit;
	}

	/**
	 * Processes a single import command.
	 *
	 * @since 1.0.0
	 * @param  array  $item The import command.
	 * @param  string $source The import source.
	 * @return bool
	 */
	protected function process_item( $item, $source ) {
		$res = false;

		mslib3()->array->equip( $item, 'task', 'data' );
		$task = $item['task'];
		$data = $item['data'];

		/**
		 * Model that handles the import process.
		 *
		 * @var MS_Model_Import $model
		 */
		$model             = MS_Factory::create( 'MS_Model_Import' );
		$model->source_key = $source;

		if ( $data instanceof SimpleXMLElement ) {
			$data = MS_Helper_Utility::xml2array( $data );
			$data = (object) $data;
		}

		// Set MS_STOP_EMAILS modifier to suppress any outgoing emails.
		MS_Plugin::set_modifier( 'MS_STOP_EMAILS', true );

		switch ( $task ) {
			case 'start':
				mslib3()->array->equip( $item, 'clear' );
				$clear = mslib3()->is_true( $item['clear'] );
				$model->start( $clear );
				$res = true;
				break;

			case 'import-membership':
				// Function expects an object, not an array!
				if ( is_array( $data ) && isset( $data['membership'] ) && count( $data['membership'] ) > 0 ) {
					foreach ( $data['membership'] as $membership ) {
						$membership = (object) $membership;
						$model->import_membership( $membership );
					}
				} else {
					$data = (object) $data;
					$model->import_membership( $data );
				}
				$res = true;
				break;

			case 'import-member':
				// Function expects an object, not an array!
				if ( is_array( $data ) && isset( $data['member'] ) && count( $data['member'] ) > 0 ) {
					foreach ( $data['member'] as $member ) {
						$member = (object) $member;
						$model->import_member( $member );
					}
				} else {
					$data = (object) $data;
					$model->import_member( $data );
				}
				$res = true;
				break;

			case 'import-settings':
				mslib3()->array->equip( $item, 'setting', 'value' );
				$setting = $item['setting'];
				$value   = $item['value'];
				$model->import_setting( $setting, $value );
				$res = true;
				break;

			case 'done':
				$model->done();
				$res = true;
				break;
		}

		/**
		 * After the import action was completed notify other objects and
		 * add-ons.
		 *
		 * @since 1.0.0
		 */
		do_action( 'ms_import_action_' . $task, $item );

		return $res;
	}

	/**
	 * Processes a single import command.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $item The import command.
	 *
	 * @return bool
	 */
	protected function process_user( $item ) {
		$res = false;

		mslib3()->array->equip( $item, 'task', 'data', 'membership', 'status', 'start', 'expire' );
		$task          = $item['task'];
		$data          = $item['data'];
		$membership_id = MS_Helper_Cast::to_int( $item['membership'] );
		$status        = MS_Helper_Cast::to_string( $item['status'] );
		$start         = MS_Helper_Cast::to_string( $item['start'] );
		$expire        = MS_Helper_Cast::to_string( $item['expire'] );

		/**
		 * Model that handles the import process.
		 *
		 * @var MS_Model_Import $model
		 */
		$model             = MS_Factory::create( 'MS_Model_Import' );
		$model->source_key = 'membership';

		// Set MS_STOP_EMAILS modifier to suppress any outgoing emails.
		MS_Plugin::set_modifier( 'MS_STOP_EMAILS', true );

		switch ( $task ) {
			case 'start':
				mslib3()->array->equip( $item, 'clear' );
				$clear = mslib3()->is_true( $item['clear'] );
				$model->start( $clear );
				$res = true;
				break;

			case 'import-member':
				$data = (object) $data;
				$model->import_user( $data, $membership_id, $status, $start, $expire );
				$res = true;
				break;

			case 'done':
				$model->done();
				$res = true;
				break;
		}

		/**
		 * After the import action was completed notify other objects and
		 * add-ons.
		 *
		 * @since 1.0.0
		 */
		do_action( 'ms_import_action_' . $task, $item );

		return $res;
	}

	/**
	 * Main entry point: Processes the import/export action.
	 *
	 * This function is called by the settings-controller whenever the
	 * Import/Export tab provides a correct nonce. We will first find out which
	 * action to execute and then handle all the details...
	 *
	 * @since 1.0.0
	 */
	public function process() {
		mslib3()->array->equip_post( 'action', 'import_source' );
		$action = $_POST['action'];

		if ( isset( $_POST['submit'] ) ) {
			$action = $_POST['submit'];
		}

		switch ( $action ) {
			case self::ACTION_EXPORT:
				/**
				 * Model that handles the export process.
				 *
				 * @var MS_Model_Export $handler
				 */
				$handler = MS_Factory::create( 'MS_Model_Export' );
				$handler->process();
				break;

			case self::ACTION_PREVIEW:
				/**
				 * Model that handles the import view.
				 *
				 * @var MS_View_Settings_Import_Settings $view
				 */
				$view       = MS_Factory::create( 'MS_View_Settings_Import_Settings' );
				$model_name = 'MS_Model_Import_' . $_POST['import_source'];
				$model      = null;

				try {
					$model = MS_Factory::create( $model_name );
				} catch ( Exception $ex ) {
					self::_message(
						'error',
						__( 'This import source is not supported. Please choose an xml or json file.', 'memberdash' )
					);
				}

				if ( $model instanceof MS_Model_Import ) {
					if ( $model->prepare() ) {
						$data = array(
							'model' => $model,
						);

						$view->set_data(
							/**
							 * Filters the data that is passed to the import view.
							 *
							 * @since 1.0.0
							 *
							 * @param array<string,mixed> $data The data.
							 *
							 * @return array<string,mixed>
							 */
							apply_filters(
								'ms_view_import_data',
								$data
							)
						);

						self::_message(
							'preview',
							/**
							 * Filters the HTML preview of the import view.
							 *
							 * @since 1.0.0
							 *
							 * @param string $html The HTML.
							 *
							 * @return string
							 */
							apply_filters(
								'ms_view_import_preview',
								$view->to_html()
							)
						);
					}
				}
				break;

			case self::ACTION_IMPORT_USER:
				/**
				 * Import users view.
				 *
				 * @var MS_View_Settings_Import_Users $view
				 */
				$view = MS_Factory::create( 'MS_View_Settings_Import_Users' );

				/**
				 * Model that handles the import process.
				 *
				 * @var MS_Model_Import_User $model
				 */
				$model = MS_Factory::create( 'MS_Model_Import_User' );

				if ( $model->prepare() ) {
					$data = array(
						'model' => $model,
					);

					$view->set_data(
						/**
						 * Filters the data that is passed to the import users view.
						 *
						 * @since 1.0.0
						 *
						 * @param array<string,mixed> $data The data.
						 *
						 * @return array<string,mixed>
						 */
						apply_filters(
							'ms_view_import_users_data',
							$data
						)
					);

					self::_message(
						'preview',
						/**
						 * Filters the HTML preview of the import users view.
						 *
						 * @since 1.0.0
						 *
						 * @param string $html The HTML.
						 *
						 * @return string
						 */
						apply_filters(
							'ms_view_import_users_preview',
							$view->to_html()
						)
					);
				}
				break;
		}
	}

	/**
	 * Enqueue admin scripts in the settings screen.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init'    => array( 'view_settings_import' ),
			'close_link' => admin_url( 'admin.php?page=membership-settings&tab=import' ),
			'lang'       => array(
				'progress_title'         => __( 'Importing data...', 'memberdash' ),
				'close_progress'         => __( 'Okay', 'memberdash' ),
				'import_done'            => __( 'All done!', 'memberdash' ),
				'task_start'             => __( 'Preparing...', 'memberdash' ),
				'task_done'              => __( 'Cleaning up...', 'memberdash' ),
				'task_import_member'     => __( 'Importing Member', 'memberdash' ),
				'task_import_membership' => __( 'Importing Membership', 'memberdash' ),
				'task_import_settings'   => __( 'Importing Settings', 'memberdash' ),
			),
		);

		mslib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}
}
