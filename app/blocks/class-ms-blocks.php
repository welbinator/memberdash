<?php
/**
 * MemberDash Blocks
 *
 * @since 1.0.0
 *
 * @package Memberdash
 */

/**
 * Class MS_Blocks
 *
 * @since 1.0.0
 */
class MS_Blocks extends  MS_Controller_Shortcode {

	public $MS_Controller_Shortcode = '';

	/**
	 * The constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Load MS_Controller_Shortcode shortcodes to be available of use
		$this->MS_Controller_Shortcode = new MS_Controller_Shortcode();
		// $this->MS_Rule_Shortcode_Model = new MS_Rule_Shortcode_Model();

		// Register a new category for blocks called MemberDash.
		add_action( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );

		add_action( 'init', array( $this, 'register_blocks_js' ) );

		add_action( 'rest_api_init', array( $this, 'api_register_get_memberships' ) );

		// add_shortcode( 'ms-invoice', array( $this->MS_Controller_Shortcode, 'membership_invoice' ) );
	}

	/*
	 * Register Rest API Route to get memberships
	 * */

	public function api_register_get_memberships() {
		register_rest_route(
			'memberdash-blocks/v1',
			'memberships/list',
			array(
				'methods'             => WP_REST_SERVER::READABLE,
				'callback'            => array( $this, 'get_memberships' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function get_memberships() {
		$memberships = MS_Model_Membership::get_public_memberships();
		$array       = array();
		foreach ( $memberships as $key => $data ) {
			$array[] = array(
				'id'   => $data->id,
				'name' => $data->name,
			);
		}
		return $array;
	}

	/*
	 * Register a WordPress Gutenberg Block!
	 * @params $categories (array)
	 */
	public function register_block_category( $categories ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'memberdash-blocks',
					'title' => __( 'MemberDash Blocks', 'memberdash' ),
				),
			)
		);
	}

	/**
	 * Registers blocks and their scripts.
	 *
	 * @return void
	 */
	public function register_blocks_js() {
		wp_register_script( 'ms-gutenberg-blocks', plugin_dir_url( __FILE__ ) . 'build/ms-gutenberg-blocks.js', array( 'wp-blocks', 'wp-element', 'wp-editor' ), MEMBERDASH_VERSION, true );
		wp_register_style( 'ms-gutenberg-blocks-styles', plugin_dir_url( __DIR__ ) . 'assets/css/ms-public.css', array(), MEMBERDASH_VERSION );

		register_block_type(
			'memberdash/ms-invoice',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'ms_invoice' ),
				'attributes'      => array(
					'id'         => array(
						'type' => 'string',
					),
					'pay_button' => array(
						'type' => 'boolean',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-membership-account',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'membership_account' ),
				'attributes'      => array(
					'show_membership'         => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
					'membership_title'        => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Your Membership',
						'description' => __( 'Default\'s to Your Membership if left blank', 'memberdash' ),
						'field_type'  => 'text',
					),
					'show_membership_change'  => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
					'membership_change_label' => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Change',
						'description' => __( 'Default\'s to Change if left blank', 'memberdash' ),
						'field_type'  => 'text',
					),
					'show_profile'            => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
					'profile_title'           => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Personal details',
						'description' => __( 'Default\'s to Personal details if left blank', 'memberdash' ),
						'field_type'  => 'text',
					),
					'show_profile_change'     => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
					'profile_change_label'    => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Edit',
						'description' => __( 'Default\'s to Edit if left blank', 'memberdash' ),
						'field_type'  => 'text',
					),
					'show_invoices'           => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
					'invoices_title'          => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Invoices',
						'description' => __( 'Default\'s to Invoices if left blank', 'memberdash' ),
						'field_type'  => 'text',
					),
					'limit_invoices'          => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 10,
						'description' => __( 'Default\'s to 10 if left blank', 'memberdash' ),
						'field_type'  => 'number',
					),
					'show_all_invoices'       => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
					'invoices_details_label'  => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'View all',
						'description' => __( 'Default\'s to View all if left blank', 'memberdash' ),
						'field_type'  => 'text',
					),
					'show_activity'           => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
					'activity_title'          => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Activities',
						'description' => __( 'Default\'s to Activities if left blank', 'memberdash' ),
						'field_type'  => 'text',
					),
					'limit_activities'        => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 10,
						'description' => __( 'Default\'s to 10 if left blank', 'memberdash' ),
						'field_type'  => 'number',
					),
					'show_all_activities'     => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
					'activity_details_label'  => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'View all',
						'description' => __( 'Default\'s to View all if left blank', 'memberdash' ),
						'field_type'  => 'text',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-membership-account-link',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'membership_account_link' ),
				'attributes'      => array(
					'label' => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Visit your account page for more information',
						'description' => __( 'Default\'s to Visit your account page for more information if left blank', 'memberdash' ),
						'field_type'  => 'text',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-membership-buy',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'membership_buy' ),
				'attributes'      => array(
					'id'    => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'description' => __( 'The membership ID', 'memberdash' ),
						'field_type'  => 'text',
					),
					'label' => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Signup',
						'description' => __( 'The membership label', 'memberdash' ),
						'field_type'  => 'text',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-membership-details',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'membership_details' ),
				'attributes'      => array(
					'id'    => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'description' => __( 'The membership ID', 'memberdash' ),
						'field_type'  => 'text',
					),
					'label' => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Membership details:',
						'description' => __( 'The membership label', 'memberdash' ),
						'field_type'  => 'text',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-membership-price',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'membership_price' ),
				'attributes'      => array(
					'id'       => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'description' => __( 'The membership ID', 'memberdash' ),
						'field_type'  => 'text',
					),
					'currency' => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
					'label'    => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'description' => __( 'Default\'s to Membership price if left blank', 'memberdash' ),
						'default'     => 'Membership price',
						'field_type'  => 'text',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-membership-title',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'membership_title' ),
				'attributes'      => array(
					'id'    => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'The membership ID', 'memberdash' ),
						'field_type'  => 'text',
					),
					'label' => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Membership title',
						'description' => __( 'The membership label', 'memberdash' ),
						'field_type'  => 'text',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-membership-note',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'ms_note' ),
				'attributes'      => array(
					'type'  => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'info',
						'description' => __( 'Type', 'memberdash' ),
						'field_type'  => 'text',
					),
					'class' => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'description' => __( 'Class', 'memberdash' ),
						'field_type'  => 'text',
					),
					'note'  => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'description' => __( 'Message to display', 'memberdash' ),
						'field_type'  => 'text',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-user',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'show_to_user' ),
				'attributes'      => array(
					'type' => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'loggedin',
						'description' => __( 'Type', 'memberdash' ),
						'field_type'  => 'text',
					),
					'msg'  => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'description' => __( 'Message to display', 'memberdash' ),
						'field_type'  => 'text',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-protect-content',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'protect_content_shortcode' ),
				'attributes'      => array(
					'id'     => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'description' => __( 'Only members with following membership id can access the content', 'memberdash' ),
						'field_type'  => 'number',
					),
					'silent' => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
					'access' => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-membership-signup',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'membership_signup' ),
				'attributes'      => array(
					'membership_signup_text' => array(
						'type'    => 'string',
						'field'   => 'TextControl',
						'default' => 'Signup',
					),
					'membership_move_text'   => array(
						'type'    => 'string',
						'field'   => 'TextControl',
						'default' => 'Change',
					),
					'membership_cancel_text' => array(
						'type'    => 'string',
						'field'   => 'TextControl',
						'default' => 'Cancel',
					),
					'membership_renew_text'  => array(
						'type'    => 'string',
						'field'   => 'TextControl',
						'default' => 'Renew',
					),
					'membership_pay_text'    => array(
						'type'    => 'string',
						'field'   => 'TextControl',
						'default' => 'Complete Payment',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-member-info',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'ms_member_info' ),
				'attributes'      => array(
					'value'          => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'fullname',
						'description' => __( '(email|firstname|lastname|fullname|memberships|custom) Defines which value to display. A custom field can be set via the API (you find the API docs on the Advanced Settings tab) ', 'memberdash' ),
						'field_type'  => 'text',
					),
					'default'        => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Please enter a valid user id',
						'description' => __( '(Text) Default value to display when the defined field is empty', 'memberdash' ),
						'field_type'  => 'text',
					),
					'before'         => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '<p>',
						'description' => __( '(Text) Display this text before the field value. Only used when the field is not empty ', 'memberdash' ),
						'field_type'  => 'text',
					),
					'after'          => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '</p>',
						'description' => __( '(Text) Display this text after the field value. Only used when the field is not empty', 'memberdash' ),
						'field_type'  => 'text',
					),
					'custom_field'   => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( '(Text) Only relevant for the value custom. This is the name of the custom field to get', 'memberdash' ),
						'field_type'  => 'text',
					),
					'list_separator' => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( '(Text) Used when the field value is a list (i.e. Membership list or contents of a custom field) ', 'memberdash' ),
						'field_type'  => 'text',
					),
					'list_before'    => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( '(Text) Used when the field value is a list (i.e. Membership list or contents of a custom field)', 'memberdash' ),
						'field_type'  => 'text',
					),
					'list_after'     => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( '(Text) Used when the field value is a list (i.e. Membership list or contents of a custom field)', 'memberdash' ),
						'field_type'  => 'text',
					),
					'user'           => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 0,
						'description' => __( ' (User-ID) Use this to display data of any user. If not specified then the current user is displayed', 'memberdash' ),
						'field_type'  => 'number',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-membership-register-user',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'membership_register_user' ),
				'attributes'      => array(
					'title'            => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Create an Account',
						'description' => __( 'Title of the register form', 'memberdash' ),
						'field_type'  => 'text',
					),
					'first_name'       => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'Initial value for first name', 'memberdash' ),
						'field_type'  => 'text',
					),
					'last_name'        => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'Initial value for last name', 'memberdash' ),
						'field_type'  => 'text',
					),
					'username'         => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'Initial value for username', 'memberdash' ),
						'field_type'  => 'text',
					),
					'email'            => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'Initial value for email address', 'memberdash' ),
						'field_type'  => 'text',
					),
					'membership_id'    => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'Membership ID to assign to the new user. This field is hidden and cannot be changed during registration. Note: If this membership requires payment, the user will be redirected to the payment gateway after registration', 'memberdash' ),
						'field_type'  => 'number',
					),
					'loginlink'        => array(
						'type'    => 'boolean',
						'field'   => 'ToggleControl',
						'default' => true,
					),

					'label_first_name' => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'First Name',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_last_name'  => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Last Name',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_username'   => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Choose a Username',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_email'      => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Email Address',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_password'   => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Password',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_password2'  => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Confirm Password',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_register'   => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Register My Account',
						'description' => '',
						'field_type'  => 'text',
					),
					'hint_first_name'  => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'Placeholder inside Field', 'memberdash' ),
						'field_type'  => 'text',
					),
					'hint_last_name'   => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'Placeholder inside Field', 'memberdash' ),
						'field_type'  => 'text',
					),
					'hint_username'    => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'Placeholder inside Field', 'memberdash' ),
						'field_type'  => 'text',
					),
					'hint_email'       => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'Placeholder inside Field ', 'memberdash' ),
						'field_type'  => 'text',
					),
					'hint_password'    => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'Placeholder inside Field', 'memberdash' ),
						'field_type'  => 'text',
					),
					'hint_password2'   => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Confirm Password',
						'description' => __( 'Placeholder inside Field', 'memberdash' ),
						'field_type'  => 'text',
					),
				),
			)
		);

		register_block_type(
			'memberdash/ms-membership-login',
			array(
				'editor_script'   => 'ms-gutenberg-blocks',
				'editor_style'    => 'ms-gutenberg-blocks-styles',
				'render_callback' => array( $this, 'membership_login' ),
				'attributes'      => array(
					'title'               => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => __( 'The title above the login form', 'memberdash' ),
						'field_type'  => 'text',
					),
					'show_labels'         => array(
						'type'        => 'boolean',
						'field'       => 'ToggleControl',
						'default'     => false,
						'description' => __( 'Set to "yes" to display the labels for username and password in front of the input fields', 'memberdash' ),
					),
					'redirect_login'      => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => MS_Model_Pages::get_url_after_login(),
						'description' => __( 'The page to display after the user was logged-in', 'memberdash' ),
						'field_type'  => 'text',
					),
					'redirect_logout'     => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => MS_Model_Pages::get_url_after_logout(),
						'description' => __( 'The page to display after the user was logged out', 'memberdash' ),
						'field_type'  => 'text',
					),
					'header'              => array(
						'type'        => 'boolean',
						'field'       => 'ToggleControl',
						'default'     => true,
						'description' => '',
					),
					'register'            => array(
						'type'        => 'boolean',
						'field'       => 'ToggleControl',
						'default'     => true,
						'description' => '',
					),
					'autofocus'           => array(
						'type'        => 'boolean',
						'field'       => 'ToggleControl',
						'default'     => true,
						'description' => __( 'Focus the login-form on page load', 'memberdash' ),
					),

					'holder'              => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'div',
						'description' => '',
						'field_type'  => 'text',
					),
					'holderclass'         => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'ms-login-form',
						'description' => '',
						'field_type'  => 'text',
					),
					'item'                => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => '',
						'field_type'  => 'text',
					),
					'itemclass'           => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => '',
						'field_type'  => 'text',
					),
					'prefix'              => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => '',
						'field_type'  => 'text',
					),
					'postfix'             => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => '',
						'field_type'  => 'text',
					),
					'wrapwith'            => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => '',
						'field_type'  => 'text',
					),
					'wrapwithclass'       => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => '',
						'field_type'  => 'text',
					),
					'form'                => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'logout',
						'description' => __( '(login|lost|logout) Defines which form should be displayed. An empty value allows the plugin to automatically choose between login/logout', 'memberdash' ),
						'field_type'  => 'text',
					),
					'nav_pos'             => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'top',
						'description' => '',
						'field_type'  => 'text',
					),

					'show_note'           => array(
						'type'        => 'boolean',
						'field'       => 'ToggleControl',
						'default'     => true,
						'description' => __( 'Show a \"You are not logged in\" note above the login form', 'memberdash' ),
					),
					'label_username'      => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Username',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_password'      => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Password',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_remember'      => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Remember Me',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_log_in'        => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Log In',
						'description' => '',
						'field_type'  => 'text',
					),
					'id_login_form'       => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'loginform',
						'description' => '',
						'field_type'  => 'text',
					),
					'id_username'         => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'user_login',
						'description' => '',
						'field_type'  => 'text',
					),
					'id_password'         => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'user_pass',
						'description' => '',
						'field_type'  => 'text',
					),
					'id_remember'         => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'rememberme',
						'description' => '',
						'field_type'  => 'text',
					),
					'id_login'            => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'wp-submit',
						'description' => '',
						'field_type'  => 'text',
					),
					'show_remember'       => array(
						'type'        => 'boolean',
						'field'       => 'ToggleControl',
						'default'     => true,
						'description' => __( 'Toggle to show Remember me', 'memberdash' ),
					),
					'value_remember'      => array(
						'type'        => 'boolean',
						'field'       => 'ToggleControl',
						'default'     => true,
						'description' => __( 'Toggle to set \"Remember me\" checkbox as checked', 'memberdash' ),
					),
					'value_username'      => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => '',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_lost_username' => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Username or E-mail',
						'description' => '',
						'field_type'  => 'text',
					),
					'label_lostpass'      => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'Reset Password',
						'description' => '',
						'field_type'  => 'text',
					),
					'id_lost_form'        => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'lostpasswordform',
						'description' => '',
						'field_type'  => 'text',
					),
					'id_lost_username'    => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'user_login',
						'description' => '',
						'field_type'  => 'text',
					),
					'id_lostpass'         => array(
						'type'        => 'string',
						'field'       => 'TextControl',
						'default'     => 'wp-submit',
						'description' => '',
						'field_type'  => 'text',
					),
				),
			)
		);

	}

	public function membership_login( $atts ) {
		return $this->MS_Controller_Shortcode->membership_login( $atts );
	}

	public function membership_register_user( $atts ) {
		return $this->MS_Controller_Shortcode->membership_register_user( $atts );
	}

	public function ms_member_info( $atts, $content = '' ) {
		return $this->MS_Controller_Shortcode->ms_member_info( $atts );
	}
	public function membership_signup( $atts ) {
		return $this->MS_Controller_Shortcode->membership_signup( $atts );
	}

	public function protect_content_shortcode( $atts, $content ) {
		$user          = wp_get_current_user();
		$allowed_roles = array( 'administrator' );
		if ( array_intersect( $allowed_roles, $user->roles ) ) {
			return MS_Rule_Shortcode_Model::debug_protect_content_shortcode( $atts, $content );
		} else {
			return MS_Rule_Shortcode_Model::protect_content_shortcode( $atts, $content );
		}
	}

	public function show_to_user( $atts, $content = '' ) {
		if ( empty( $atts['msg'] ) ) {
			return __( 'Please enter your message to display on sidebar.', 'memberdash' );
		} else {
			$content = $atts['msg'];
			return $this->MS_Controller_Shortcode->show_to_user( $atts, $content );
		}
	}

	public function ms_note( $atts, $content = '' ) {
		if ( empty( $atts['note'] ) ) {
			return __( 'Please enter your note to display on sidebar.', 'memberdash' );
		} else {
			$content = $atts['note'];
			return $this->MS_Controller_Shortcode->ms_note( $atts, $content );
		}
	}

	public function membership_title( $atts ) {
		return $this->MS_Controller_Shortcode->membership_title( $atts );
	}

	public function membership_price( $atts ) {
		return $this->MS_Controller_Shortcode->membership_price( $atts );
	}

	public function membership_details( $atts ) {
		return $this->MS_Controller_Shortcode->membership_details( $atts );
	}

	public function membership_buy( $atts ) {
		return $this->MS_Controller_Shortcode->membership_buy( $atts );
	}

	public function membership_account_link( $atts ) {
		return $this->MS_Controller_Shortcode->membership_account_link( $atts );
	}

	public function ms_invoice( $atts ) {
		return $this->MS_Controller_Shortcode->membership_invoice( $atts );
	}

	public function membership_account( $atts ) {
		return $this->MS_Controller_Shortcode->membership_account( $atts );
	}

}
