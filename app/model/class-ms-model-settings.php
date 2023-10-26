<?php
/**
 * Settings model.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Model
 */

/**
 * Settings model.
 *
 * Singleton. Persisted by parent class MS_Model_Option.
 *
 * @since 1.0.0
 */
class MS_Model_Settings extends MS_Model_Option {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @static var MS_Model_Settings
	 */
	public static $instance;

	/**
	 * Protection Message Type constants.
	 *
	 * User can set 3 different protection message defaults:
	 * - Whole page is protected
	 * - Shortcode content is protected
	 * - Read-more content is protected
	 *
	 * @since 1.0.0
	 */
	const PROTECTION_MSG_CONTENT   = 'content';
	const PROTECTION_MSG_SHORTCODE = 'shortcode';
	const PROTECTION_MSG_MORE_TAG  = 'more_tag';

	/**
	 * ID of the model object.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $id = 'ms_plugin_settings';

	/**
	 * Model name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name = 'Plugin settings';

	/**
	 * Current db version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $version = '';

	/**
	 * Plugin enabled status indicator.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $plugin_enabled = false;

	/**
	 * Initial setup status indicator.
	 *
	 * Wizard mode.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $initial_setup = true;

	/**
	 * Is set to false when the first membership was created.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $is_first_membership = true;

	/**
	 * Is set to false when the first paid membership was created.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $is_first_paid_membership = true;

	/**
	 * Wizard step tracker.
	 *
	 * Indicate which step of the wizard.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $wizard_step = '';

	/**
	 * Hide Membership Menu pointer indicator.
	 *
	 * Wizard mode.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $hide_wizard_pointer = false;

	/**
	 * Hide Toolbar for non admin users indicator.
	 *
	 * Wizard mode.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $hide_admin_bar = true;


	/**
	 * Enable use of cron when performing backend actions
	 *
	 * Wizard mode.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $enable_cron_use = true;


	/**
	 * Enable use of query cache
	 *
	 * Settings
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $enable_query_cache = false;


	/**
	 * Force a single payment gateway as the default gateway
	 *
	 * Settings
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $force_single_gateway = false;


	/**
	 * Registration verification
	 *
	 * Settings
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $force_registration_verification = false;

	/**
	 * The currency used in the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $currency = 'USD';

	/**
	 * The license key used in the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $license_key = '';

	/**
	 * The license email that used in the plugin.
	 *
	 * @var string
	 */
	protected $license_email = '';

	/**
	 * The name used in the invoices.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $invoice_sender_name = '';

	/**
	 * The company name used in the invoices.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $company_name = '';

	/**
	 * The invoice billing address.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $billing_address = '';

	/**
	 * The company VAT/TAX Number used in the invoices.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $company_vax_tax_number = '';

	/**
	 * Global payments already set indicator.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $is_global_payments_set = false;

	/**
	 * Protection Messages.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $protection_messages = array();

	/**
	 * How menu items are protected.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $menu_protection = 'item';

	/**
	 * Media / Downloads settings.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $downloads = array(
		'protection_type'    => MS_Rule_Media_Model::PROTECTION_TYPE_COMPLETE,
		'masked_url'         => 'downloads',
		'direct_access'      => array( 'jpg', 'jpeg', 'png', 'gif', 'mp3', 'ogg' ),
		'application_server' => '',
	);

	/**
	 * Invoice Settings
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $invoice = array(
		'sequence_type'  => MS_Addon_Invoice::DEFAULT_SEQUENCE,
		'invoice_prefix' => 'MS-',
	);

	/**
	 * Global payments already set indicator.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $is_advanced_media_protection = false;

	/**
	 * Default WP Rest settings
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $wprest = array(
		'api_namespace' => MS_Addon_WPRest::API_NAMESPACE,
		'api_passkey'   => '',
	);

	/**
	 * Import flags
	 *
	 * When data was imported a flag can be set here to remember that some
	 * members come from there.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $import = array();

	/**
	 * Special view.
	 *
	 * This defines a special view that is displayed when the plugin is loaded
	 * instead of the default plugin page that would be displayed.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $special_view = false;

	/**
	 * Get protection message types.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] The available protection message types.
	 */
	public static function get_protection_msg_types() {
		$types = array(
			self::PROTECTION_MSG_CONTENT,
			self::PROTECTION_MSG_SHORTCODE,
			self::PROTECTION_MSG_MORE_TAG,
		);

		return apply_filters( 'ms_model_settings_get_protection_msg_types', $types );
	}

	/**
	 * Validate protection message type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The protection message type to validate.
	 * @return boolean True if valid.
	 */
	public static function is_valid_protection_msg_type( $type ) {
		$types = self::get_protection_msg_types();

		return apply_filters(
			'ms_model_settings_is_valid_protection_msg_type',
			in_array( $type, $types )
		);
	}

	/**
	 * Set protection message type.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $type The protection message type.
	 * @param string              $msg The protection message.
	 * @param  MS_Model_Membership $membership Optional. If defined the
	 *         protection message specific for this membership will be set.
	 */
	public function set_protection_message( $type, $msg, $membership = null ) {
		if ( self::is_valid_protection_msg_type( $type ) ) {
			$key = $type;

			if ( $membership ) {
				if ( $membership instanceof MS_Model_Membership ) {
					$key .= '_' . $membership->id;
				} elseif ( is_scalar( $membership ) ) {
					$key .= '_' . $membership;
				}
			}

			if ( null === $msg ) {
				unset( $this->protection_messages[ $key ] );
			} else {
				$this->protection_messages[ $key ] = stripslashes( wp_kses_post( $msg ) );
			}
		}

		do_action(
			'ms_model_settings_set_protection_message',
			$type,
			$msg,
			$membership,
			$this
		);
	}

	/**
	 * Get protection message type.
	 *
	 * @since 1.0.0
	 *
	 * @param string                  $type       The protection message type.
	 * @param MS_Model_Membership|int $membership Optional. If defined the protection message specific for this membership will be returned.
	 * @param bool                    $found      This is set to true if the specified membership did override this message.
	 *
	 * @return string $msg The protection message.
	 */
	public function get_protection_message( $type, $membership = null, &$found = null ) {
		$msg   = '';
		$found = false;

		if ( self::is_valid_protection_msg_type( $type ) ) {
			$key = $type;

			if ( $membership ) {
				if ( $membership instanceof MS_Model_Membership ) {
					$key_override = $key . '_' . $membership->id;
				} elseif ( is_scalar( $membership ) ) {
					$key_override = $key . '_' . $membership;
				} else {
					$key_override = $key;
				}
				if ( isset( $this->protection_messages[ $key_override ] ) ) {
					$key   = $key_override;
					$found = true;
				}
			}

			if ( isset( $this->protection_messages[ $key ] ) ) {
				$msg = $this->protection_messages[ $key ];
			} else {
				$msg = __( 'The content you are trying to access is only available to members. Sorry.', 'memberdash' );
			}
		}

		return apply_filters(
			'ms_model_settings_get_protection_message',
			$msg,
			$type,
			$this
		);
	}

	/**
	 * Activates a special view.
	 * Next time the plugin is loaded this special view is displayed.
	 *
	 * This should be set in MS_Model_Upgrade (or earlier) to ensure the special
	 * view is displayed on the current page request.
	 *
	 * @since 1.0.0
	 * @param  string $name Name of the view to display.
	 */
	public static function set_special_view( $name ) {
		$settings               = MS_Factory::load( 'MS_Model_Settings' );
		$settings->special_view = $name;
		$settings->save();
	}

	/**
	 * Returns the currently set special view.
	 *
	 * @since 1.0.0
	 * @return string Name of the view to display.
	 */
	public static function get_special_view() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$view     = $settings->special_view;
		return $view;
	}

	/**
	 * Deactivates the special view.
	 *
	 * @since 1.0.0
	 */
	public static function reset_special_view() {
		$settings               = MS_Factory::load( 'MS_Model_Settings' );
		$settings->special_view = false;
		$settings->save();
	}

	/**
	 * Get available currencies.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     @type string $currency The currency.
	 *     @type string $title The currency title.
	 * }
	 */
	public static function get_currencies() {
		static $Currencies = null;

		if ( null === $Currencies ) {
			$Currencies = apply_filters(
				'ms_model_settings_get_currencies',
				array(
					'ALL' => __( 'ALL - Albania Lek', 'memberdash' ),
					'AFN' => __( 'AFN - Afghanistan Afghani', 'memberdash' ),
					'ARS' => __( 'ARS - Argentina Peso', 'memberdash' ),
					'AWG' => __( 'AWG - Aruba Guilder', 'memberdash' ),
					'AUD' => __( 'AUD - Australia Dollar', 'memberdash' ),
					'AZN' => __( 'AZN - Azerbaijan New Manat', 'memberdash' ),
					'BSD' => __( 'BSD - Bahamas Dollar', 'memberdash' ),
					'BBD' => __( 'BBD - Barbados Dollar', 'memberdash' ),
					'BDT' => __( 'BDT - Bangladeshi taka', 'memberdash' ),
					'BYR' => __( 'BYR - Belarus Ruble', 'memberdash' ),
					'BZD' => __( 'BZD - Belize Dollar', 'memberdash' ),
					'BMD' => __( 'BMD - Bermuda Dollar', 'memberdash' ),
					'BOB' => __( 'BOB - Bolivia Boliviano', 'memberdash' ),
					'BAM' => __( 'BAM - Bosnia and Herzegovina Convertible Marka', 'memberdash' ),
					'BWP' => __( 'BWP - Botswana Pula', 'memberdash' ),
					'BGN' => __( 'BGN - Bulgaria Lev', 'memberdash' ),
					'BRL' => __( 'BRL - Brazil Real', 'memberdash' ),
					'BND' => __( 'BND - Brunei Darussalam Dollar', 'memberdash' ),
					'KHR' => __( 'KHR - Cambodia Riel', 'memberdash' ),
					'CAD' => __( 'CAD - Canada Dollar', 'memberdash' ),
					'KYD' => __( 'KYD - Cayman Islands Dollar', 'memberdash' ),
					'CLP' => __( 'CLP - Chile Peso', 'memberdash' ),
					'CNY' => __( 'CNY - China Yuan Renminbi', 'memberdash' ),
					'COP' => __( 'COP - Colombia Peso', 'memberdash' ),
					'CRC' => __( 'CRC - Costa Rica Colon', 'memberdash' ),
					'HRK' => __( 'HRK - Croatia Kuna', 'memberdash' ),
					'CUP' => __( 'CUP - Cuba Peso', 'memberdash' ),
					'CZK' => __( 'CZK - Czech Republic Koruna', 'memberdash' ),
					'DKK' => __( 'DKK - Denmark Krone', 'memberdash' ),
					'DOP' => __( 'DOP - Dominican Republic Peso', 'memberdash' ),
					'XCD' => __( 'XCD - East Caribbean Dollar', 'memberdash' ),
					'EGP' => __( 'EGP - Egypt Pound', 'memberdash' ),
					'SVC' => __( 'SVC - El Salvador Colon', 'memberdash' ),
					'EEK' => __( 'EEK - Estonia Kroon', 'memberdash' ),
					'EUR' => __( 'EUR - Euro Member Countries', 'memberdash' ),
					'FKP' => __( 'FKP - Falkland Islands (Malvinas) Pound', 'memberdash' ),
					'FJD' => __( 'FJD - Fiji Dollar', 'memberdash' ),
					'GHC' => __( 'GHC - Ghana Cedis', 'memberdash' ),
					'GIP' => __( 'GIP - Gibraltar Pound', 'memberdash' ),
					'GTQ' => __( 'GTQ - Guatemala Quetzal', 'memberdash' ),
					'GGP' => __( 'GGP - Guernsey Pound', 'memberdash' ),
					'GYD' => __( 'GYD - Guyana Dollar', 'memberdash' ),
					'HNL' => __( 'HNL - Honduras Lempira', 'memberdash' ),
					'HKD' => __( 'HKD - Hong Kong Dollar', 'memberdash' ),
					'HUF' => __( 'HUF - Hungary Forint', 'memberdash' ),
					'ISK' => __( 'ISK - Iceland Krona', 'memberdash' ),
					'INR' => __( 'INR - India Rupee', 'memberdash' ),
					'IDR' => __( 'IDR - Indonesia Rupiah', 'memberdash' ),
					'IRR' => __( 'IRR - Iran Rial', 'memberdash' ),
					'IMP' => __( 'IMP - Isle of Man Pound', 'memberdash' ),
					'ILS' => __( 'ILS - Israel Shekel', 'memberdash' ),
					'JMD' => __( 'JMD - Jamaica Dollar', 'memberdash' ),
					'JPY' => __( 'JPY - Japan Yen', 'memberdash' ),
					'JEP' => __( 'JEP - Jersey Pound', 'memberdash' ),
					'KZT' => __( 'KZT - Kazakhstan Tenge', 'memberdash' ),
					'KPW' => __( 'KPW - Korea (North) Won', 'memberdash' ),
					'KRW' => __( 'KRW - Korea (South) Won', 'memberdash' ),
					'KGS' => __( 'KGS - Kyrgyzstan Som', 'memberdash' ),
					'LAK' => __( 'LAK - Laos Kip', 'memberdash' ),
					'LVL' => __( 'LVL - Latvia Lat', 'memberdash' ),
					'LBP' => __( 'LBP - Lebanon Pound', 'memberdash' ),
					'LRD' => __( 'LRD - Liberia Dollar', 'memberdash' ),
					'LTL' => __( 'LTL - Lithuania Litas', 'memberdash' ),
					'MKD' => __( 'MKD - Macedonia Denar', 'memberdash' ),
					'MYR' => __( 'MYR - Malaysia Ringgit', 'memberdash' ),
					'MUR' => __( 'MUR - Mauritius Rupee', 'memberdash' ),
					'MXN' => __( 'MXN - Mexico Peso', 'memberdash' ),
					'MNT' => __( 'MNT - Mongolia Tughrik', 'memberdash' ),
					'MZN' => __( 'MZN - Mozambique Metical', 'memberdash' ),
					'NAD' => __( 'NAD - Namibia Dollar', 'memberdash' ),
					'NPR' => __( 'NPR - Nepal Rupee', 'memberdash' ),
					'ANG' => __( 'ANG - Netherlands Antilles Guilder', 'memberdash' ),
					'NZD' => __( 'NZD - New Zealand Dollar', 'memberdash' ),
					'NIO' => __( 'NIO - Nicaragua Cordoba', 'memberdash' ),
					'NGN' => __( 'NGN - Nigeria Naira', 'memberdash' ),
					'NOK' => __( 'NOK - Norway Krone', 'memberdash' ),
					'OMR' => __( 'OMR - Oman Rial', 'memberdash' ),
					'PKR' => __( 'PKR - Pakistan Rupee', 'memberdash' ),
					'PAB' => __( 'PAB - Panama Balboa', 'memberdash' ),
					'PYG' => __( 'PYG - Paraguay Guarani', 'memberdash' ),
					'PEN' => __( 'PEN - Peru Nuevo Sol', 'memberdash' ),
					'PHP' => __( 'PHP - Philippines Peso', 'memberdash' ),
					'PLN' => __( 'PLN - Poland Zloty', 'memberdash' ),
					'QAR' => __( 'QAR - Qatar Riyal', 'memberdash' ),
					'RON' => __( 'RON - Romania New Leu', 'memberdash' ),
					'RUB' => __( 'RUB - Russia Ruble', 'memberdash' ),
					'SHP' => __( 'SHP - Saint Helena Pound', 'memberdash' ),
					'SAR' => __( 'SAR - Saudi Arabia Riyal', 'memberdash' ),
					'RSD' => __( 'RSD - Serbia Dinar', 'memberdash' ),
					'SCR' => __( 'SCR - Seychelles Rupee', 'memberdash' ),
					'SGD' => __( 'SGD - Singapore Dollar', 'memberdash' ),
					'SBD' => __( 'SBD - Solomon Islands Dollar', 'memberdash' ),
					'SOS' => __( 'SOS - Somalia Shilling', 'memberdash' ),
					'ZAR' => __( 'ZAR - South Africa Rand', 'memberdash' ),
					'LKR' => __( 'LKR - Sri Lanka Rupee', 'memberdash' ),
					'SEK' => __( 'SEK - Sweden Krona', 'memberdash' ),
					'CHF' => __( 'CHF - Switzerland Franc', 'memberdash' ),
					'SRD' => __( 'SRD - Suriname Dollar', 'memberdash' ),
					'SYP' => __( 'SYP - Syria Pound', 'memberdash' ),
					'TWD' => __( 'TWD - Taiwan New Dollar', 'memberdash' ),
					'THB' => __( 'THB - Thailand Baht', 'memberdash' ),
					'TTD' => __( 'TTD - Trinidad and Tobago Dollar', 'memberdash' ),
					'TRY' => __( 'TRY - Turkey Lira', 'memberdash' ),
					'TRL' => __( 'TRL - Turkey Lira', 'memberdash' ),
					'TVD' => __( 'TVD - Tuvalu Dollar', 'memberdash' ),
					'UAH' => __( 'UAH - Ukraine Hryvna', 'memberdash' ),
					'GBP' => __( 'GBP - United Kingdom Pound', 'memberdash' ),
					'USD' => __( 'USD - United States Dollar', 'memberdash' ),
					'UYU' => __( 'UYU - Uruguay Peso', 'memberdash' ),
					'UZS' => __( 'UZS - Uzbekistan Som', 'memberdash' ),
					'VEF' => __( 'VEF - Venezuela Bolivar', 'memberdash' ),
					'VND' => __( 'VND - Viet Nam Dong', 'memberdash' ),
					'YER' => __( 'YER - Yemen Rial', 'memberdash' ),
					'ZWD' => __( 'ZWD - Zimbabwe Dollar', 'memberdash' ),
				)
			);
		}

		return $Currencies;
	}

	/**
	 * Set specific property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed  $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'currency':
					if ( array_key_exists( $value, self::get_currencies() ) ) {
						$this->$property = $value;
					}
					break;

				case 'invoice_sender_name':
				case 'company_name':
				case 'billing_address':
				case 'company_vax_tax_number':
				case 'license_key':
				case 'license_email':
					$this->$property = sanitize_text_field( $value );
					break;

				case 'plugin_enabled':
				case 'initial_setup':
				case 'is_first_membership':
				case 'enable_cron_use':
				case 'enable_query_cache':
				case 'force_single_gateway':
				case 'hide_admin_bar':
					$this->$property = mslib3()->is_true( $value );
					break;

				case 'force_registration_verification':
					$is_enabled    = mslib3()->is_true( $value );
					$comm          = MS_Model_Communication::get_communication( MS_Model_Communication::COMM_TYPE_REGISTRATION_VERIFY );
					$comm->enabled = $is_enabled;
					$comm->save();
					$this->$property = mslib3()->is_true( $is_enabled );
					break;

				default:
					$this->$property = $value;
					break;
			}
		} else {
			switch ( $property ) {
				case 'protection_type':
					if ( MS_Rule_Media_Model::is_valid_protection_type( $value ) ) {
						$this->downloads['protection_type'] = $value;
					}
					break;

				case 'masked_url':
					$this->downloads['masked_url'] = sanitize_text_field( $value );
					break;

				case 'advanced_media_protection':
					$create_htaccess = mslib3()->is_true( $value );
					if ( $create_htaccess ) {
						MS_Model_Addon::toggle_media_htaccess( $this );
					} else {
						MS_Helper_Media::clear_htaccess();
					}
					$this->is_advanced_media_protection = $create_htaccess;
					break;

				case 'direct_access':
					$this->downloads['direct_access'] = explode( ',', sanitize_text_field( $value ) );
					break;
				case 'application_server':
					$this->downloads['application_server'] = sanitize_text_field( $value );
					break;

				case 'sequence_type':
					$this->invoice['sequence_type'] = sanitize_text_field( $value );
					break;

				case 'invoice_prefix':
					$this->invoice['invoice_prefix'] = sanitize_text_field( $value );
					break;

				case 'api_namespace':
					$this->wprest['api_namespace'] = sanitize_text_field( $value );
					break;

				case 'api_passkey':
					$this->wprest['api_passkey'] = sanitize_text_field( $value );
					break;

			}
		}
	}

	/**
	 * Returns a specific property.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $property The name of a property.
	 * @return mixed $value The value of a property.
	 */
	public function __get( $property ) {
		$value = null;

		switch ( $property ) {
			case 'menu_protection':
				if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_ADV_MENUS ) ) {
					$value = 'item';
				} else {
					$value = $this->menu_protection;
				}
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$value = $this->$property;
				} else {
					switch ( $property ) {
						case 'currency_symbol':
							// Same translation table in:
							// -> ms-view-membership-setup-payment.js
							$symbol = $this->currency;
							switch ( $symbol ) {
								case 'USD':
									$symbol = '$';
									break;
								case 'EUR':
									$symbol = '€';
									break;
								case 'JPY':
									$symbol = '¥';
									break;
							}
							$value = $symbol;
					}
				}
		}

		return apply_filters( 'ms_model_settings__get', $value, $property, $this );
	}

	/**
	 * Check if property isset.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param string $property The name of a property.
	 * @return mixed Returns true/false.
	 */
	public function __isset( $property ) {
		return isset( $this->$property );
	}

	/**
	 * Setter method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $license_key The license key.
	 *
	 * @return void
	 */
	public function set_license_key( string $license_key ): void {
		$this->license_key = $license_key;
	}

	/**
	 * Setter method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $license_email The license email.
	 *
	 * @return void
	 */
	public function set_license_email( string $license_email ): void {
		$this->license_email = $license_email;
	}

	/**
	 * Returns the currency used in the plugin.
	 *
	 * @since 1.0.3
	 *
	 * @return string
	 */
	public function get_currency(): string {
		return $this->currency;
	}

	/**
	 * Sets the currency used in the plugin.
	 *
	 * @since 1.0.3
	 *
	 * @param string $currency Currency.
	 *
	 * @return void
	 */
	public function set_currency( string $currency ): void {
		$this->currency = $currency;
	}

	/**
	 * Returns the invoice sender name.
	 *
	 * @since 1.0.3
	 *
	 * @return string
	 */
	public function get_invoice_sender_name(): string {
		return $this->invoice_sender_name;
	}

	/**
	 * Sets the invoice sender name.
	 *
	 * @since 1.0.3
	 *
	 * @param string $invoice_sender_name Invoice sender name.
	 *
	 * @return void
	 */
	public function set_invoice_sender_name( string $invoice_sender_name ): void {
		$this->invoice_sender_name = $invoice_sender_name;
	}

	/**
	 * Returns the billing address.
	 *
	 * @since 1.0.3
	 *
	 * @return string
	 */
	public function get_billing_address(): string {
		return $this->billing_address;
	}

	/**
	 * Sets the billing address.
	 *
	 * @since 1.0.3
	 *
	 * @param string $billing_address Billing address.
	 *
	 * @return void
	 */
	public function set_billing_address( string $billing_address ): void {
		$this->billing_address = $billing_address;
	}

	/**
	 * Returns the company name.
	 *
	 * @since 1.0.3
	 *
	 * @return string
	 */
	public function get_company_name(): string {
		return $this->company_name;
	}

	/**
	 * Sets the company name.
	 *
	 * @since 1.0.3
	 *
	 * @param string $company_name Company name.
	 *
	 * @return void
	 */
	public function set_company_name( string $company_name ): void {
		$this->company_name = $company_name;
	}

	/**
	 * Returns the company VAT/TAX number.
	 *
	 * @since 1.0.3
	 *
	 * @return string
	 */
	public function get_company_vax_tax_number(): string {
		return $this->company_vax_tax_number;
	}

	/**
	 * Sets the company VAT/TAX number.
	 *
	 * @since 1.0.3
	 *
	 * @param string $company_vax_tax_number Company VAT/TAX number.
	 *
	 * @return void
	 */
	public function set_company_vax_tax_number( string $company_vax_tax_number ): void {
		$this->company_vax_tax_number = $company_vax_tax_number;
	}
}
