<?php
/**
 * Class that handles Full Export functions.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage Model
 */

/**
 * Full Export class
 *
 * @since 1.0.0
 */
class MS_Model_Export_Full extends MS_Model_Export_Base {
	/**
	 * Main entry point: Handles the export action.
	 *
	 * This task will exit the current request as the result will be a download
	 * and no HTML page that is displayed.
	 *
	 * @param String $format - export format
	 *
	 * @since 1.0.0
	 */
	public function process( $format ) {
		$data                  = $this->export_base();
		$data['memberships']   = array();
		$data['members']       = array();
		$membership            = MS_Model_Membership::get_base();
		$data['memberships'][] = $this->export_membership( $membership );
		$memberships           = MS_Model_Membership::get_memberships( array( 'post_parent' => 0 ) );
		$members               = MS_Model_Member::get_members();
		foreach ( $memberships as $membership ) {
			$data['memberships'][] = $this->export_membership( $membership );
		}
		foreach ( $members as $member ) {
			if ( ! $member->is_member ) {
				continue;
			}

			$data['members'][] = $this->export_member( $member );
		}

		$milliseconds = round( microtime( true ) * 1000 );
		$file_name    = $milliseconds . '_membership-full';
		switch ( $format ) {
			case MS_Model_Export::JSON_EXPORT:
				mslib3()->net->file_download( wp_json_encode( $data ), $file_name . '.json' );
				break;

			case MS_Model_Export::XML_EXPORT:
				$xml = new SimpleXMLElement( '<?xml version="1.0"?><membership></membership>' );
				foreach ( $data as $key => $datas ) {
					if ( is_array( $datas ) ) {
						$node = $xml->addChild( $key );
						foreach ( $datas as $d ) {
							if ( is_array( $d ) ) {
								$subnode = $node->addChild( substr( $key, 0, -1 ) );
								MS_Helper_Media::generate_xml( $subnode, $d );
							} else {
								$node->addChild( substr( $key, 0, -1 ), $d );
							}
						}
					} else {
						$xml->addChild( $key, $datas );
					}
				}
				mslib3()->net->file_download( $xml->asXML(), $file_name . '.xml' );
				break;
		}
	}
}


