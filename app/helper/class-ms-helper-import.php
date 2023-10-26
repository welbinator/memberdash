<?php
/**
 * Helper class for import functions
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Helper
 */

/**
 * Helper class for import functions
 *
 * @since 1.0.0
 */
class MS_Helper_Import extends MS_Helper {
	/**
	 * Membership to import view
	 * Converts the membership objet to viewable data
	 *
	 * @return array
	 */
	public static function membership_to_view( $item, $ms_types, $ms_paytypes ) {
		if ( ! isset( $ms_types[ $item->type ] ) ) {
			$item->type = MS_Model_Membership::TYPE_STANDARD;
		}

		if ( empty( $item->payment_type ) ) {
			if ( ! empty( $item->pay_type ) ) {
				// Compatibility with bug in old M1 export files.
				$item->payment_type = $item->pay_type;
			} else {
				$item->payment_type = 'permanent';
			}
		}

		switch ( $item->payment_type ) {
			case 'recurring':
				$payment_type = MS_Model_Membership::PAYMENT_TYPE_RECURRING;
				break;

			case 'finite':
				$payment_type = MS_Model_Membership::PAYMENT_TYPE_FINITE;
				break;

			case 'date':
				$payment_type = MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE;
				break;

			default:
				$payment_type = MS_Model_Membership::PAYMENT_TYPE_PERMANENT;
				break;
		}

		return array(
			$item->name,
			$ms_types[ $item->type ],
			$ms_paytypes[ $payment_type ],
			$item->description,
		);
	}

	/**
	 * Member to view. Converts the member data to viewable data.
	 *
	 * @since 1.0.0
	 *
	 * @param object $item Member object.
	 *
	 * @return array
	 */
	public static function member_to_view( $item ) {
		$invoices_counter = 0;

		// skip users without username or email.
		if ( empty( $item->username ) || empty( $item->email ) ) {
			return [];
		}

		if (
			isset( $item->subscriptions )
			&& is_array( $item->subscriptions )
		) {
			foreach ( $item->subscriptions as $registration ) {
				if (
				! isset( $registration->invoices )
				|| ! is_array( $registration->invoices )
				) {
					continue;
				}

				$invoices_counter += count( $registration->invoices );
			}
		}

		return array(
			$item->username,
			$item->email,
			isset( $item->subscriptions ) && is_array( $item->subscriptions ) ? count( $item->subscriptions ) : 0,
			$invoices_counter,
		);
	}
}

