<?php
/**
 * Class that handles Membership Export functions.
 *
 * @since 1.0.0
 * @package MemberDash
 * @subpackage Model
 */
class MS_Model_Export_Membership extends MS_Model_Export_Base {


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
		$data                  = $this->export_base( 'memberships' );
		$data['memberships']   = array();
		$membership            = MS_Model_Membership::get_base();
		$data['memberships'][] = $this->export_membership( $membership );
		$memberships           = MS_Model_Membership::get_memberships( array( 'post_parent' => 0 ) );
		foreach ( $memberships as $membership ) {
			$data['memberships'][] = $this->export_membership( $membership );
		}

		$milliseconds = round( microtime( true ) * 1000 );
		$file_name    = $milliseconds . '_membership-memberships';
		switch ( $format ) {
			case MS_Model_Export::JSON_EXPORT:
				mslib3()->net->file_download( wp_json_encode( $data ), $file_name . '.json' );
				break;

			case MS_Model_Export::XML_EXPORT:
				$xml = new SimpleXMLElement( '<?xml version="1.0"?><membership></membership>' );
				foreach ( $data as $key => $memberships ) {
					if ( is_array( $memberships ) ) {
						$node = $xml->addChild( $key );
						foreach ( $memberships as $membership ) {
							if ( is_array( $membership ) ) {
								$subnode = $node->addChild( substr( $key, 0, -1 ) );
								MS_Helper_Media::generate_xml( $subnode, $membership );
							} else {
								$node->addChild( substr( $key, 0, -1 ), $membership );
							}
						}
					} else {
						$xml->addChild( $key, esc_html( $memberships ) );
					}
				}
				mslib3()->net->file_download( $xml->asXML(), $file_name . '.xml' );
				break;
		}
	}
}


