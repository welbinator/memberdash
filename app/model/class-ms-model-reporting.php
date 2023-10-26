<?php
/**
 * Model: Reporting
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Model
 */

/**
 * Manage Reporting queries
 *
 * @since 1.0.0
 */
class MS_Model_Reporting extends MS_Model_Option {

	/**
	 * Get total revenue (sales) by date range.
	 *
	 * @param string $start_date The start date of the date range in the Y-m-d format.
	 * @param string $end_date The end date of the date range in the Y-m-d format.
	 * @return array {
	 *     @type int $sales_amount The sales amount.
	 *     @type float $total_revenue Total revenue (sales).
	 * }
	 */
	public function get_revenue_by_date_range( $start_date, $end_date ) {
		global $wpdb;

		$sql    = $wpdb->prepare(
			"SELECT Count(meta_value) AS sales_amount,
      Sum(meta_value)   AS total_revenue
      FROM   {$wpdb->postmeta}
      WHERE  meta_key = 'amount'
          AND post_id IN (SELECT post_id
                          FROM  {$wpdb->posts} AS p
                                JOIN {$wpdb->postmeta} AS m
                                  ON p.id = m.post_id
                          WHERE  post_type = %s
                                AND Date(post_date) BETWEEN
                                    %s AND %s
                                AND ( meta_key = 'status'
                                      AND meta_value = 'paid' ))",
			MS_Model_Invoice::$POST_TYPE,
			$start_date,
			$end_date
		);
		$result = $wpdb->get_row( $sql );

		$revenue = array(
			'sales_amount'  => $result->sales_amount,
			'total_revenue' => $result->total_revenue,
		);
		return $revenue;
	}


	/**
	 * Get some users stats
	 *
	 * @return array {
	 *     @type int $active_users Active users last 3 months.
	 *     @type int $new_users New users 7 past days.
	 * }
	 */
	public function get_users_stats() {
		global $wpdb;

		$sql = "SELECT (SELECT Count(meta_value) AS active_users
                        FROM   {$wpdb->usermeta}
                        WHERE  meta_key = 'ms_last_login_date'
                              AND meta_value BETWEEN Date_sub(CURDATE(), INTERVAL 3 MONTH) AND
                                                      CURDATE()) AS active_users,
                      (SELECT Count(meta_value) AS new_users
                        FROM   {$wpdb->usermeta}
                        WHERE  meta_key = 'ms_registration_date'
                              AND meta_value BETWEEN Date_sub(CURDATE(), INTERVAL 6 DAY) AND
                                                      CURDATE()) AS new_users";

		$result = $wpdb->get_row( $sql );

		$revenue = array(
			'active_users' => $result->active_users,
			'new_users'    => $result->new_users,
		);
		return $revenue;
	}

	/**
	 * Get top selling memberships by revenue by date range.
	 *
	 * @param string $start_date The start date of the date range in the Y-m-d format.
	 * @param string $end_date The end date of the date range in the Y-m-d format.
	 * @param string $limit Top n results. Default: 10.
	 * @return array Array with top membership data {
	 *     @type int $membership_id The membership ID.
	 *     @type string $membership_name The membership name.
	 *     @type int $sales_amount The sales amount.
	 *     @type float $total_revenue Total revenue (sales).
	 * }
	 */
	public function get_top_selling_memberships_by_revenue( $start_date, $end_date, $limit = 10 ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT mship.meta_value       AS membership_id,
      Count(mamt.meta_value) AS sales_amount,
      Sum(mamt.meta_value)   AS total_revenue
      FROM   {$wpdb->postmeta} AS mamt
            JOIN {$wpdb->postmeta} AS mship
              ON mamt.post_id = mship.post_id
                AND mship.meta_key = 'membership_id'
      WHERE  mamt.meta_key = 'amount'
            AND mamt.post_id IN (SELECT post_id
                                FROM   {$wpdb->posts} AS p
                                        JOIN {$wpdb->postmeta} AS mStatus
                                          ON p.id = mStatus.post_id
                                            AND mStatus.meta_key = 'status'
                                WHERE  post_type = 'ms_invoice'
                                        AND Date(post_date) BETWEEN
                                            %s AND %s
                                        AND mStatus.meta_value = 'paid')
      GROUP  BY ( membership_id )
      ORDER  BY total_revenue DESC
      LIMIT  %d",
			$start_date,
			$end_date,
			$limit
		);

		// get membership names
		$membership_names = MS_Model_Membership::get_membership_names(
			array(
				'include_base'  => 0,
				'include_guest' => 0,
			)
		);

		$result          = $wpdb->get_results( $sql );
		$top_memberships = array();
		foreach ( $result as $row ) {
			$membership_data                  = new stdClass();
			$membership_data->membership_id   = $row->membership_id;
			$membership_data->membership_name = $membership_names[ $membership_data->membership_id ];
			$membership_data->sales_amount    = $row->sales_amount;
			$membership_data->total_revenue   = $row->total_revenue;

			$top_memberships [] = $membership_data;
		}

		return $top_memberships;
	}

	/**
	 * Get new users by date range.
	 *
	 * @param string $start_date The start date of the date range in the Y-m-d format.
	 * @param string $end_date The end date of the date range in the Y-m-d format.
	 * @return array Array with header and data. {
	 *     'header': Array with column names.
	 *     'data': Array with the data.
	 * }
	 */
	public function get_new_users_by_date_range( $start_date, $end_date ) {
		global $wpdb;

		$header = apply_filters(
			'ms_model_reporting_new_users_csv_header',
			array(
				__( 'User ID', 'memberdash' ),
				__( 'Login', 'memberdash' ),
				__( 'Email', 'memberdash' ),
				__( 'Display Name', 'memberdash' ),
				__( 'Registration Date', 'memberdash' ),
			)
		);
		$data   = array();

		$sql = $wpdb->prepare(
			"SELECT u.id,
      u.user_login,
      u.user_email,
      u.display_name,
      meta_value AS 'registration_date'
      FROM   wp_users u
            JOIN {$wpdb->usermeta} m
              ON u.id = m.user_id
                AND m.meta_key = 'ms_registration_date'
      WHERE  Date(meta_value) BETWEEN %s AND %s
      ORDER  BY u.id ASC",
			$start_date,
			$end_date
		);

		$result = $wpdb->get_results( $sql );
		$count  = 0;
		foreach ( $result as $row ) {
			$data[ $count ]['id']                = $row->id;
			$data[ $count ]['user_login']        = $row->user_login;
			$data[ $count ]['user_email']        = $row->user_email;
			$data[ $count ]['display_name']      = $row->display_name;
			$data[ $count ]['registration_date'] = $row->registration_date;
			$count ++;
		}

		return array(
			'header' => $header,
			'data'   => $data,
		);
	}

	/**
	 * Get new paying users by date range, membership and payment gateway.
	 *
	 * @param string $start_date The start date of the date range in the Y-m-d format.
	 * @param string $end_date The end date of the date range in the Y-m-d format.
	 * @param int    $membership_id The membership ID (optional).
	 * @param int    $payment_gateway_id The payment gateway ID (optional).
	 * @return array Array with header and data. {
	 *     'header': Array with column names.
	 *     'data': Array with the data.
	 * }
	 */
	public function get_new_paying_users_by_filter( $start_date, $end_date, $membership_id = 0, $payment_gateway_id = 0 ) {
		global $wpdb;

		$header = apply_filters(
			'ms_model_reporting_paying_users_csv_header',
			array(
				__( 'User ID', 'memberdash' ),
				__( 'Login', 'memberdash' ),
				__( 'Email', 'memberdash' ),
				__( 'Display Name', 'memberdash' ),
				__( 'Registration Date', 'memberdash' ),
				__( 'Membership Name', 'memberdash' ),
				__( 'Payment Gateway', 'memberdash' ),
			)
		);
		$data   = array();

		$args                        = array();
		$args['posts_per_page']      = -1;
		$args['number']              = false;
		$args['offset']              = 0;
		$args['subscription_status'] = MS_Model_Relationship::STATUS_ACTIVE;
		if ( ! empty( $membership_id ) ) {
			$args['membership_id'] = $membership_id;
		}
		$args['meta_query'] = array(
			array(
				'key'     => 'ms_registration_date',
				'value'   => array( $start_date, $end_date ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			),
		);

		$count   = 0;
		$members = MS_Model_Member::get_members( $args, MS_Model_Member::SEARCH_ONLY_MEMBERS );

		if ( is_array( $members ) && ! empty( $members ) ) {
			$gateways    = MS_Model_Gateway::get_gateway_names( false, true );
			$memberships = MS_Model_Membership::get_memberships(
				array(
					'include_base'  => 0,
					'include_guest' => 0,
				),
				true
			);

			foreach ( $members as $member ) {
				if ( $member->subscriptions ) {
					foreach ( $member->subscriptions as $subscription ) {
						$the_membership = $memberships[ $subscription->membership_id ];
						if ( $the_membership->is_free ) {
							continue; // just paid memberships.
						}

						if ( ! empty( $payment_gateway_id ) && $payment_gateway_id !== $subscription->gateway_id ) {
								continue; // filter by payment gateway.
						}

						$data[ $count ]['id']                = $member->id;
						$data[ $count ]['login']             = $member->username;
						$data[ $count ]['email']             = $member->email;
						$data[ $count ]['display_name']      = $member->display_name;
						$data[ $count ]['registration_date'] = $member->registration_date;
						$data[ $count ]['membership']        = $the_membership->name;
						$data[ $count ]['gateway']           = $gateways[ $subscription->gateway_id ];
						$count++;
					}
				}
			}
		}

		return array(
			'header' => $header,
			'data'   => $data,
		);
	}

	/**
	 * Get paying members that haven't logged in within the previous 3 months
	 *
	 * @return array Array with header and data. {
	 *     'header': Array with column names.
	 *     'data': Array with the data.
	 * }
	 */
	public function get_paying_users_no_login_3_months() {
		global $wpdb;

		$header = apply_filters(
			'ms_model_reporting_paying_users_csv_header',
			array(
				__( 'User ID', 'memberdash' ),
				__( 'Login', 'memberdash' ),
				__( 'Email', 'memberdash' ),
				__( 'Display Name', 'memberdash' ),
				__( 'Registration Date', 'memberdash' ),
				__( 'Last Login Date', 'memberdash' ),
				__( 'Membership Name', 'memberdash' ),
				__( 'Payment Gateway', 'memberdash' ),
			)
		);
		$data   = array();

		$args                        = array();
		$args['posts_per_page']      = -1;
		$args['number']              = false;
		$args['offset']              = 0;
		$args['subscription_status'] = MS_Model_Relationship::STATUS_ACTIVE;

		$args['meta_query'] = array(
			array(
				'key'     => 'ms_last_login_date',
				'value'   => gmdate( 'Y-m-d', strtotime( '-3 MONTH' ) ),
				'compare' => '<',
				'type'    => 'DATE',
			),
		);

		$count   = 0;
		$members = MS_Model_Member::get_members( $args, MS_Model_Member::SEARCH_ONLY_MEMBERS );

		if ( is_array( $members ) && ! empty( $members ) ) {
			$gateways    = MS_Model_Gateway::get_gateway_names( false, true );
			$memberships = MS_Model_Membership::get_memberships(
				array(
					'include_base'  => 0,
					'include_guest' => 0,
				),
				true
			);

			foreach ( $members as $member ) {
				if ( $member->subscriptions ) {
					foreach ( $member->subscriptions as $subscription ) {
						$the_membership = $memberships[ $subscription->membership_id ];
						if ( $the_membership->is_free ) {
							continue; // just paid memberships.
						}

						$data[ $count ]['id']                = $member->id;
						$data[ $count ]['login']             = $member->username;
						$data[ $count ]['email']             = $member->email;
						$data[ $count ]['display_name']      = $member->display_name;
						$data[ $count ]['registration_date'] = $member->registration_date;
						$data[ $count ]['last_login_date']   = $member->last_login_date;
						$data[ $count ]['membership']        = $the_membership->name;
						$data[ $count ]['gateway']           = $gateways[ $subscription->gateway_id ];
						$count++;
					}
				}
			}
		}

		return array(
			'header' => $header,
			'data'   => $data,
		);

	}

	/**
	 * Download CSV file.
	 *
	 * @param string                           $filename The filename suffix.
	 * @param  array Array with header and data. {
	 *     'header': Array with column names.
	 *     'data': Array with the data.
	 * }
	 * @return void Outputs CSV file
	 */
	public function download_csv_data( $data, $filename = 'report.csv' ) {
		$contents     = __( 'No Data', 'memberdash' );
		$dir          = MS_Helper_Media::get_membership_dir();
		$milliseconds = round( microtime( true ) * 1000 );
		$filename     = $milliseconds . '-' . $filename;

		$filepath = $dir . DIRECTORY_SEPARATOR . $filename;
		$status   = MS_Helper_Media::create_csv( $filepath, $data['data'], $data['header'] );
		if ( $status && file_exists( $filepath ) ) {
			$handle = fopen( $filepath, 'rb' );
			if ( $handle ) {
				$contents = fread( $handle, filesize( $filepath ) ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
				fclose( $handle );
			}
			unlink( $filepath );
		}
		mslib3()->net->file_download( $contents, $filename );
	}

}
