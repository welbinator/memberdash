<?php
/**
 *
 * Gateway Model
 *
 * @package MemberDash
 * @subpackage Model
 */

/**
 * Gateway parent model.
 *
 * Every payment gateway extends from this class.
 * A payment gateway can process payments using three possible functions:
 *
 * - - - - - - - - - -
 *
 * function handle_return()
 *   This function is called by MWPS when the IPN URL was called.
 *   E.g. calling "/ms-payment-return/paypalstandard" will trigger the function
 *   handle_return() for the PayPal Standard gateway.
 *   Subscription data must be fetched from the $_POST data collection.
 *
 * function process_purchase( $subscription )
 *   Called automatically by MWPS when a new subscription was created, i.e.
 *   handles the first payment of any subscription.
 *   This function might create a new customer account/etc via the gateway API.
 *
 * function request_payment( $subscription )
 *   Called automatically by MWPS when a payment is due, i.e. when the second
 *   payment of a recurring subscription is due.
 *
 * - - - - - - - - - -
 *
 * A single gateway should not implement all three payment methods! Either use
 *   handle_return   - or -
 *   process_purchase and request_payment
 *
 * @since 1.0.0
 */
class MS_Gateway extends MS_Model_Option {

	/**
	 * Gateway operation mode content.
	 *
	 * @since 1.0.0
	 * @see $mode
	 * @var string The operation mode.
	 */
	const MODE_SANDBOX = 'sandbox';
	const MODE_LIVE    = 'live';

	const ID = 'admin';

	/**
	 * Singleton object.
	 *
	 * @since 1.0.0
	 * @see $type
	 * @var string The singleton object.
	 */
	public static $instance;

	/**
	 * Gateway group.
	 *
	 * This is a label that is used to group settings together on the Payment
	 * Settings page.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $group = '';

	/**
	 * Gateway ID.
	 *
	 * @since 1.0.0
	 * @var int $id
	 */
	protected $id = 'admin';

	/**
	 * Gateway name.
	 *
	 * @since 1.0.0
	 * @var string $name
	 */
	protected $name = '';

	/**
	 * Gateway description.
	 *
	 * @since 1.0.0
	 * @var string $description
	 */
	protected $description = '';

	/**
	 * Gateway active status.
	 *
	 * @since 1.0.0
	 *
	 * @var bool $active
	 */
	protected $active = false;

	/**
	 * Manual payment indicator.
	 *
	 * True: Recurring payments need to be made manually.
	 * False: Gateway is capable of automatic recurring payments.
	 *
	 * @since 1.0.0
	 * @var bool $manual_payment
	 */
	protected $manual_payment = true;

	/**
	 * List of payment_type IDs that are not supported by this gateway.
	 *
	 * @since 1.0.0
	 * @var array $unsupported_payment_types
	 */
	protected $unsupported_payment_types = array();

	/**
	 * Gateway allows Pro rating.
	 *
	 * Pro rating means that a user will get a discount for a new subscription
	 * when he upgrades from another subscription that is not fully consumed
	 * yet.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	protected $pro_rate = false;

	/**
	 * Custom payment button text or url.
	 *
	 * Overrides default purchase button.
	 *
	 * @since 1.0.0
	 * @var string $pay_button_url The url or button label (text).
	 */
	protected $pay_button_url;

	/**
	 * Custom cancel button text or url.
	 *
	 * Overrides default cancel button.
	 *
	 * @since 1.0.0
	 * @var string $cancel_button_url The url or button label (text).
	 */
	protected $cancel_button_url;

	/**
	 * Gateway operation mode.
	 *
	 * Live or sandbox (test) mode.
	 *
	 * @since 1.0.0
	 * @var string $mode
	 */
	protected $mode;

	/**
	 * Hook to process gateway returns (IPN).
	 *
	 * @see MS_Controller_Gateway: handle_payment_return()
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		do_action( 'ms_gateway_after_load', $this );

		if ( $this->active ) {
			$this->add_action(
				'ms_gateway_handle_payment_return_' . $this->id,
				'handle_return'
			);

			$this->add_action(
				'ms_gateway_handle_webhook_' . $this->id,
				'handle_webhook'
			);
		}

		$this->add_filter( 'ms_model_gateway_register', 'register' );
	}

	/**
	 * Registers the Gateway
	 *
	 * @since 1.0.0
	 * @param  array $list The gateway list.
	 * @return array The updated gateway list.
	 */
	public function register( $list ) {
		$class = get_class( $this );
		$id    = constant( $class . '::ID' );

		$list[ $id ] = $class;

		return $list;
	}

	/**
	 * Checks if the specified payment type is supported by the current gateway.
	 *
	 * @since 1.0.0
	 * @param  string|MS_Model_Membership $payment_type Either a payment type
	 *         identifier or a membership model object.
	 * @return bool
	 */
	public function payment_type_supported( $payment_type ) {
		if ( is_object( $payment_type ) ) {
			$payment_type = $payment_type->payment_type;
		}

		$types  = $this->supported_payment_types();
		$result = isset( $types[ $payment_type ] );

		return $result;
	}

	/**
	 * Returns a list of supported payment types.
	 *
	 * @since 1.0.0
	 * @return array Payment types, index is the type-key / value the label.
	 */
	public function supported_payment_types() {
		static $Payment_Types = array();

		if ( ! isset( $Payment_Types[ $this->id ] ) ) {
			$Payment_Types[ $this->id ] = MS_Model_Membership::get_payment_types();

			foreach ( $this->unsupported_payment_types as $remove ) {
				unset( $Payment_Types[ $this->id ][ $remove ] );
			}
		}

		return $Payment_Types[ $this->id ];
	}

	/**
	 * Processes gateway IPN return.
	 *
	 * Overridden in child gateway classes.
	 *
	 * @since 1.0.0
	 * @param  MS_Model_Transactionlog $log Optional. A transaction log item
	 *         that will be updated instead of creating a new log entry.
	 */
	public function handle_return( $log = false ) {
		do_action(
			'ms_gateway_handle_return',
			$this,
			$log
		);
	}

	/**
	 * Process WebHook requests
	 *
	 * @since 1.0.0
	 */
	public function handle_webhook() {
		do_action(
			'ms_gateway_handle_webhook',
			$this
		);
	}

	/**
	 * Processes purchase action.
	 *
	 * This function is called when a payment was made: We check if the
	 * transaction was successful. If it was we call `$invoice->changed()` which
	 * will update the membership status accordingly.
	 *
	 * Overridden in child classes.
	 * This parent method only covers free purchases.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship $ms_relationship The related membership relationship.
	 */
	public function process_purchase( $subscription ) {
		do_action(
			'ms_gateway_process_purchase_before',
			$subscription,
			$this
		);

		$invoice             = $subscription->get_current_invoice();
		$invoice->gateway_id = $this->id;
		$invoice->save();

		// The default handler only processes free subscriptions.
		if ( 0 == $invoice->total ) {
			$invoice->changed();
		}

		return apply_filters(
			'ms_gateway_process_purchase',
			$invoice
		);
	}

	/**
	 * Propagate membership cancellation to the gateway.
	 *
	 * Overridden in child classes.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 */
	public function cancel_membership( $subscription ) {
		do_action(
			'ms_gateway_cancel_membership',
			$subscription,
			$this
		);
	}

	/**
	 * Request automatic payment to the gateway.
	 *
	 * Overridden in child gateway classes.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 * @return bool True on success.
	 */
	public function request_payment( $subscription ) {
		do_action(
			'ms_gateway_request_payment',
			$subscription,
			$this
		);

		// Default to "Payment successful"
		return true;
	}

	/**
	 * Check for card expiration date.
	 *
	 * Save event for card expire soon.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 */
	public function check_card_expiration( $subscription ) {
		do_action( 'ms_gateway_check_card_expiration_before', $this );

		$member   = MS_Factory::load( 'MS_Model_Member', $subscription->user_id );
		$card_exp = $member->get_gateway_profile( $this->id, 'card_exp' );

		if ( ! empty( $card_exp ) ) {
			$comm = MS_Model_Communication::get_communication(
				MS_Model_Communication::COMM_TYPE_CREDIT_CARD_EXPIRE
			);

			$days             = MS_Helper_Period::get_period_in_days(
				$comm->period['period_unit'],
				$comm->period['period_type']
			);
			$card_expire_days = MS_Helper_Period::subtract_dates(
				$card_exp,
				MS_Helper_Period::current_date(),
				DAY_IN_SECONDS, // return value in DAYS.
				true // return negative value if first date is before second date.
			);
			if ( $card_expire_days < 0 || ( $days == $card_expire_days ) ) {
				MS_Model_Event::save_event(
					MS_Model_Event::TYPE_CREDIT_CARD_EXPIRE,
					$subscription
				);
			}
		}

		do_action(
			'ms_gateway_check_card_expiration_after',
			$this,
			$subscription
		);
	}

	/**
	 * Url that fires handle_return of this gateway (IPN).
	 * If $ipn_listening_url is invalid, return default.
	 *
	 * @since 1.0.0
	 * @return string The return url.
	 */
	public function get_return_url() {
		$url = $this->get_default_return_url();

		if ( isset( $this->ipn_listening_url ) && filter_var( $this->ipn_listening_url, FILTER_VALIDATE_URL ) ) {
			$url = $this->ipn_listening_url;
		}

		return apply_filters(
			'ms_gateway_get_return_url',
			$url,
			$this
		);
	}

	/**
	 * Default Url that fires handle_return of this gateway (IPN).
	 *
	 * @since 1.0.0
	 * @return string The return url.
	 */
	public function get_default_return_url() {
		return MS_Helper_Utility::home_url( '/ms-payment-return/' . $this->id );
	}

	/**
	 * Url that fires handle_webhook of this gateway (IPN).
	 *
	 * @since 1.0.0
	 * @return string The webhook url.
	 */
	public function get_webhook_url() {
		$url = MS_Helper_Utility::home_url( '?memberdash-integration=' . $this->id );

		return apply_filters(
			'ms_gateway_get_webhook_url',
			$url,
			$this
		);
	}

	/**
	 * Get gateway mode types.
	 *
	 * @since 1.0.0
	 * @return array {
	 *     Returns array of ( $mode_type => $description ).
	 *     @type string $mode_type The mode type.
	 *     @type string $description The mode type description.
	 * }
	 */
	public function get_mode_types() {
		$mode_types = array(
			self::MODE_LIVE    => __( 'Live Site', 'memberdash' ),
			self::MODE_SANDBOX => __( 'Sandbox Mode (test)', 'memberdash' ),
		);

		return apply_filters(
			'ms_gateway_get_mode_types',
			$mode_types,
			$this
		);
	}

	/**
	 * Return if is live mode.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if is in live mode.
	 */
	public function is_live_mode() {
		if ( empty( $this->mode ) ) {
			$this->mode = self::MODE_SANDBOX;
		}

		$is_live_mode = ( self::MODE_SANDBOX !== $this->mode );

		return apply_filters(
			'ms_gateway_is_live_mode',
			$is_live_mode
		);
	}

	/**
	 * Verify required fields.
	 *
	 * To be overridden in children classes.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_configured() {
		MS_Helper_Debug::debug_log(
			sprintf(
			// translators: %s is the gateway id.
				__( 'Override the is_configured method of the %s-gateway', 'memberdash' ),
				$this->id
			)
		);

		return false;
	}

	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed  $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'id':
				case 'name':
					break;

				case 'description':
				case 'pay_button_url':
				case 'upgrade_button_url':
				case 'cancel_button_url':
					$this->$property = trim( sanitize_text_field( $value ) );
					break;

				case 'active':
				case 'manual_payment':
					$this->$property = ( ! empty( $value ) ? true : false );
					break;

				default:
					if ( is_string( $value ) ) {
						$this->$property = trim( $value );
					}
					break;
			}
		}

		do_action(
			'ms_gateway__set_after',
			$property,
			$value,
			$this
		);
	}

	/**
	 * Return a property value
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param  string $name The name of a property to associate.
	 * @return mixed The value of a property.
	 */
	public function __get( $property ) {
		$value = null;

		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'active':
				case 'manual_payment':
					return ( ! empty( $this->$property ) ? true : false );

				case 'id':
				case 'name':
				case 'description':
				case 'pay_button_url':
				case 'upgrade_button_url':
				case 'cancel_button_url':
				case 'mode':
					$value = trim( $this->$property );
					break;

				default:
					$value = $this->$property;
					break;
			}
		}

		return apply_filters(
			'ms_gateway__get',
			$value,
			$property,
			$this
		);
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
	 * Get countries code and names.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     Returns array of ( $code => $name ).
	 *     @type string $code The country code.
	 *     @type string $name The country name.
	 * }
	 */
	public static function get_country_codes() {
		static $Countries = null;

		if ( is_null( $Countries ) ) {
			$Countries = array(
				''   => '- ' . __( 'Select country', 'memberdash' ) . ' -',
				'AX' => __( 'Aland Islands', 'memberdash' ),
				'AL' => __( 'Albania', 'memberdash' ),
				'DZ' => __( 'Algeria', 'memberdash' ),
				'AS' => __( 'American Samoa', 'memberdash' ),
				'AD' => __( 'Andorra', 'memberdash' ),
				'AI' => __( 'Anguilla', 'memberdash' ),
				'AQ' => __( 'Antarctica', 'memberdash' ),
				'AG' => __( 'Antigua And Barbuda', 'memberdash' ),
				'AR' => __( 'Argentina', 'memberdash' ),
				'AM' => __( 'Armenia', 'memberdash' ),
				'AW' => __( 'Aruba', 'memberdash' ),
				'AU' => __( 'Australia', 'memberdash' ),
				'AT' => __( 'Austria', 'memberdash' ),
				'AZ' => __( 'Azerbaijan', 'memberdash' ),
				'BS' => __( 'Bahamas', 'memberdash' ),
				'BH' => __( 'Bahrain', 'memberdash' ),
				'BD' => __( 'Bangladesh', 'memberdash' ),
				'BB' => __( 'Barbados', 'memberdash' ),
				'BE' => __( 'Belgium', 'memberdash' ),
				'BZ' => __( 'Belize', 'memberdash' ),
				'BJ' => __( 'Benin', 'memberdash' ),
				'BM' => __( 'Bermuda', 'memberdash' ),
				'BT' => __( 'Bhutan', 'memberdash' ),
				'BA' => __( 'Bosnia-herzegovina', 'memberdash' ),
				'BW' => __( 'Botswana', 'memberdash' ),
				'BV' => __( 'Bouvet Island', 'memberdash' ),
				'BR' => __( 'Brazil', 'memberdash' ),
				'IO' => __( 'British Indian Ocean Territory', 'memberdash' ),
				'BN' => __( 'Brunei Darussalam', 'memberdash' ),
				'BG' => __( 'Bulgaria', 'memberdash' ),
				'BF' => __( 'Burkina Faso', 'memberdash' ),
				'CA' => __( 'Canada', 'memberdash' ),
				'CV' => __( 'Cape Verde', 'memberdash' ),
				'KY' => __( 'Cayman Islands', 'memberdash' ),
				'CF' => __( 'Central African Republic', 'memberdash' ),
				'CL' => __( 'Chile', 'memberdash' ),
				'CN' => __( 'China', 'memberdash' ),
				'CX' => __( 'Christmas Island', 'memberdash' ),
				'CC' => __( 'Cocos (keeling) Islands', 'memberdash' ),
				'CO' => __( 'Colombia', 'memberdash' ),
				'CK' => __( 'Cook Islands', 'memberdash' ),
				'CR' => __( 'Costa Rica', 'memberdash' ),
				'HR' => __( 'Croatia', 'memberdash' ),
				'CY' => __( 'Cyprus', 'memberdash' ),
				'CZ' => __( 'Czech Republic', 'memberdash' ),
				'DK' => __( 'Denmark', 'memberdash' ),
				'DJ' => __( 'Djibouti', 'memberdash' ),
				'DM' => __( 'Dominica', 'memberdash' ),
				'DO' => __( 'Dominican Republic', 'memberdash' ),
				'EC' => __( 'Ecuador', 'memberdash' ),
				'EG' => __( 'Egypt', 'memberdash' ),
				'SV' => __( 'El Salvador', 'memberdash' ),
				'EE' => __( 'Estonia', 'memberdash' ),
				'FK' => __( 'Falkland Islands (malvinas)', 'memberdash' ),
				'FO' => __( 'Faroe Islands', 'memberdash' ),
				'FJ' => __( 'Fiji', 'memberdash' ),
				'FI' => __( 'Finland', 'memberdash' ),
				'FR' => __( 'France', 'memberdash' ),
				'GF' => __( 'French Guiana', 'memberdash' ),
				'PF' => __( 'French Polynesia', 'memberdash' ),
				'TF' => __( 'French Southern Territories', 'memberdash' ),
				'GA' => __( 'Gabon', 'memberdash' ),
				'GM' => __( 'Gambia', 'memberdash' ),
				'GE' => __( 'Georgia', 'memberdash' ),
				'DE' => __( 'Germany', 'memberdash' ),
				'GH' => __( 'Ghana', 'memberdash' ),
				'GI' => __( 'Gibraltar', 'memberdash' ),
				'GR' => __( 'Greece', 'memberdash' ),
				'GL' => __( 'Greenland', 'memberdash' ),
				'GD' => __( 'Grenada', 'memberdash' ),
				'GP' => __( 'Guadeloupe', 'memberdash' ),
				'GU' => __( 'Guam', 'memberdash' ),
				'GG' => __( 'Guernsey', 'memberdash' ),
				'GY' => __( 'Guyana', 'memberdash' ),
				'HM' => __( 'Heard Island And Mcdonald Islands', 'memberdash' ),
				'VA' => __( 'Holy See (vatican City State)', 'memberdash' ),
				'HN' => __( 'Honduras', 'memberdash' ),
				'HK' => __( 'Hong Kong', 'memberdash' ),
				'HU' => __( 'Hungary', 'memberdash' ),
				'IS' => __( 'Iceland', 'memberdash' ),
				'IN' => __( 'India', 'memberdash' ),
				'ID' => __( 'Indonesia', 'memberdash' ),
				'IE' => __( 'Ireland', 'memberdash' ),
				'IM' => __( 'Isle Of Man', 'memberdash' ),
				'IL' => __( 'Israel', 'memberdash' ),
				'IT' => __( 'Italy', 'memberdash' ),
				'JM' => __( 'Jamaica', 'memberdash' ),
				'JP' => __( 'Japan', 'memberdash' ),
				'JE' => __( 'Jersey', 'memberdash' ),
				'JO' => __( 'Jordan', 'memberdash' ),
				'KZ' => __( 'Kazakhstan', 'memberdash' ),
				'KE' => __( 'Kenya', 'memberdash' ),
				'KI' => __( 'Kiribati', 'memberdash' ),
				'KR' => __( 'Korea, Republic Of', 'memberdash' ),
				'KW' => __( 'Kuwait', 'memberdash' ),
				'KG' => __( 'Kyrgyzstan', 'memberdash' ),
				'LV' => __( 'Latvia', 'memberdash' ),
				'LS' => __( 'Lesotho', 'memberdash' ),
				'LI' => __( 'Liechtenstein', 'memberdash' ),
				'LT' => __( 'Lithuania', 'memberdash' ),
				'LU' => __( 'Luxembourg', 'memberdash' ),
				'MO' => __( 'Macao', 'memberdash' ),
				'MK' => __( 'Macedonia', 'memberdash' ),
				'MG' => __( 'Madagascar', 'memberdash' ),
				'MW' => __( 'Malawi', 'memberdash' ),
				'MY' => __( 'Malaysia', 'memberdash' ),
				'MT' => __( 'Malta', 'memberdash' ),
				'MH' => __( 'Marshall Islands', 'memberdash' ),
				'MQ' => __( 'Martinique', 'memberdash' ),
				'MR' => __( 'Mauritania', 'memberdash' ),
				'MU' => __( 'Mauritius', 'memberdash' ),
				'YT' => __( 'Mayotte', 'memberdash' ),
				'MX' => __( 'Mexico', 'memberdash' ),
				'FM' => __( 'Micronesia, Federated States Of', 'memberdash' ),
				'MD' => __( 'Moldova, Republic Of', 'memberdash' ),
				'MC' => __( 'Monaco', 'memberdash' ),
				'MN' => __( 'Mongolia', 'memberdash' ),
				'ME' => __( 'Montenegro', 'memberdash' ),
				'MS' => __( 'Montserrat', 'memberdash' ),
				'MA' => __( 'Morocco', 'memberdash' ),
				'MZ' => __( 'Mozambique', 'memberdash' ),
				'NA' => __( 'Namibia', 'memberdash' ),
				'NR' => __( 'Nauru', 'memberdash' ),
				'NP' => __( 'Nepal', 'memberdash' ),
				'NL' => __( 'Netherlands', 'memberdash' ),
				'AN' => __( 'Netherlands Antilles', 'memberdash' ),
				'NC' => __( 'New Caledonia', 'memberdash' ),
				'NZ' => __( 'New Zealand', 'memberdash' ),
				'NI' => __( 'Nicaragua', 'memberdash' ),
				'NE' => __( 'Niger', 'memberdash' ),
				'NU' => __( 'Niue', 'memberdash' ),
				'NF' => __( 'Norfolk Island', 'memberdash' ),
				'MP' => __( 'Northern Mariana Islands', 'memberdash' ),
				'NO' => __( 'Norway', 'memberdash' ),
				'OM' => __( 'Oman', 'memberdash' ),
				'PW' => __( 'Palau', 'memberdash' ),
				'PS' => __( 'Palestine', 'memberdash' ),
				'PA' => __( 'Panama', 'memberdash' ),
				'PY' => __( 'Paraguay', 'memberdash' ),
				'PE' => __( 'Peru', 'memberdash' ),
				'PH' => __( 'Philippines', 'memberdash' ),
				'PN' => __( 'Pitcairn', 'memberdash' ),
				'PL' => __( 'Poland', 'memberdash' ),
				'PT' => __( 'Portugal', 'memberdash' ),
				'PR' => __( 'Puerto Rico', 'memberdash' ),
				'QA' => __( 'Qatar', 'memberdash' ),
				'RE' => __( 'Reunion', 'memberdash' ),
				'RO' => __( 'Romania', 'memberdash' ),
				'RU' => __( 'Russian Federation', 'memberdash' ),
				'RW' => __( 'Rwanda', 'memberdash' ),
				'SH' => __( 'Saint Helena', 'memberdash' ),
				'KN' => __( 'Saint Kitts And Nevis', 'memberdash' ),
				'LC' => __( 'Saint Lucia', 'memberdash' ),
				'PM' => __( 'Saint Pierre And Miquelon', 'memberdash' ),
				'VC' => __( 'Saint Vincent And The Grenadines', 'memberdash' ),
				'WS' => __( 'Samoa', 'memberdash' ),
				'SM' => __( 'San Marino', 'memberdash' ),
				'ST' => __( 'Sao Tome And Principe', 'memberdash' ),
				'SA' => __( 'Saudi Arabia', 'memberdash' ),
				'SN' => __( 'Senegal', 'memberdash' ),
				'RS' => __( 'Serbia', 'memberdash' ),
				'SC' => __( 'Seychelles', 'memberdash' ),
				'SG' => __( 'Singapore', 'memberdash' ),
				'SK' => __( 'Slovakia', 'memberdash' ),
				'SI' => __( 'Slovenia', 'memberdash' ),
				'SB' => __( 'Solomon Islands', 'memberdash' ),
				'ZA' => __( 'South Africa', 'memberdash' ),
				'GS' => __( 'South Georgia And The South Sandwich Islands', 'memberdash' ),
				'ES' => __( 'Spain', 'memberdash' ),
				'SR' => __( 'Suriname', 'memberdash' ),
				'SJ' => __( 'Svalbard And Jan Mayen', 'memberdash' ),
				'SZ' => __( 'Swaziland', 'memberdash' ),
				'SE' => __( 'Sweden', 'memberdash' ),
				'CH' => __( 'Switzerland', 'memberdash' ),
				'TW' => __( 'Taiwan, Province Of China', 'memberdash' ),
				'TZ' => __( 'Tanzania, United Republic Of', 'memberdash' ),
				'TH' => __( 'Thailand', 'memberdash' ),
				'TL' => __( 'Timor-leste', 'memberdash' ),
				'TG' => __( 'Togo', 'memberdash' ),
				'TK' => __( 'Tokelau', 'memberdash' ),
				'TO' => __( 'Tonga', 'memberdash' ),
				'TT' => __( 'Trinidad And Tobago', 'memberdash' ),
				'TN' => __( 'Tunisia', 'memberdash' ),
				'TR' => __( 'Turkey', 'memberdash' ),
				'TM' => __( 'Turkmenistan', 'memberdash' ),
				'TC' => __( 'Turks And Caicos Islands', 'memberdash' ),
				'TV' => __( 'Tuvalu', 'memberdash' ),
				'UG' => __( 'Uganda', 'memberdash' ),
				'UA' => __( 'Ukraine', 'memberdash' ),
				'AE' => __( 'United Arab Emirates', 'memberdash' ),
				'GB' => __( 'United Kingdom', 'memberdash' ),
				'US' => __( 'United States', 'memberdash' ),
				'UM' => __( 'United States Minor Outlying Islands', 'memberdash' ),
				'UY' => __( 'Uruguay', 'memberdash' ),
				'UZ' => __( 'Uzbekistan', 'memberdash' ),
				'VU' => __( 'Vanuatu', 'memberdash' ),
				'VE' => __( 'Venezuela', 'memberdash' ),
				'VN' => __( 'Viet Nam', 'memberdash' ),
				'VG' => __( 'Virgin Islands, British', 'memberdash' ),
				'VI' => __( 'Virgin Islands, U.s.', 'memberdash' ),
				'WF' => __( 'Wallis And Futuna', 'memberdash' ),
				'EH' => __( 'Western Sahara', 'memberdash' ),
				'ZM' => __( 'Zambia', 'memberdash' ),
			);

			$Countries = apply_filters(
				'ms_gateway_get_country_codes',
				$Countries
			);
		}

		return $Countries;
	}
}
