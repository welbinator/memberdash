<?php

class MS_Rule_Content_View extends MS_View {

	public function to_html() {
		$membership = MS_Model_Membership::get_base();
		$rule       = $membership->get_rule( MS_Rule_Content::RULE_ID );

		// This fixes the list-title generated by MS_Helper_ListTable_Rule.
		unset( $_GET['status'] );

		$rule_listtable = new MS_Rule_Content_ListTable( $rule );
		$rule_listtable->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'Comments & More Tag', 'memberdash' ),
				'desc'  => __( 'Decide how to protect Comments and More Tag contents.', 'memberdash' ),
			),
			MS_Rule_Content::RULE_ID,
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );

			$rule_listtable->views();
			$rule_listtable->search_box( __( 'Pages', 'memberdash' ) );
			?>
			<form action="" method="post">
				<?php
				$rule_listtable->display();

				do_action(
					'ms_view_membership_protectedcontent_footer',
					MS_Rule_Content::RULE_ID,
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
