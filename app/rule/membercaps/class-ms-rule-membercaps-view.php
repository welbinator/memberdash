<?php
/**
 * This file contains the MS_Rule_MemberCaps_View class.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 */

/**
 * Class MS_Rule_MemberCaps_View
 *
 * Provides a view for managing member capabilities.
 */
class MS_Rule_MemberCaps_View extends MS_View {
	/**
	 * Render the HTML view of the class.
	 *
	 * @since 1.0.0
	 *
	 * @return string The HTML output.
	 */
	public function to_html() {
		$membership = MS_Model_Membership::get_base();
		$rule       = $membership->get_rule( MS_Rule_MemberCaps::RULE_ID );

		$rule_listtable = new MS_Rule_MemberCaps_ListTable( $rule );
		$rule_listtable->prepare_items();

		$header_data          = array();
		$header_data['title'] = __( 'Assign WordPress Capabilities to your Members', 'memberdash' );
		$header_data['desc']  = array(
			__( 'Fine-tune member permissions by assigning certain Capabilities to each Membership. All Members of that Membership are granted the specified Capabilities.', 'memberdash' ),
			__( 'Important: All users that are not inside these Memberships will be stripped of any Protected Capability!', 'memberdash' ),
			__( 'You should only use these rules if you know what you are doing! Granting the wrong capabilities makes your website prone to abuse. For a bit of security we already removed the most critical Capabilities from this list.', 'memberdash' ),
		);

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			$header_data,
			MS_Rule_MemberCaps::RULE_ID,
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );

			$rule_listtable->views();
			$rule_listtable->search_box( __( 'Capability', 'memberdash' ) );
			?>
			<form action="" method="post">
				<?php
				$rule_listtable->display();

				do_action(
					'ms_view_membership_protectedcontent_footer',
					MS_Rule_MemberCaps::RULE_ID,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer();
		return ob_get_clean();
	}

}
