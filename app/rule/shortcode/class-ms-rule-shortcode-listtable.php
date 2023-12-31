<?php
/**
 * Membership List Table
 *
 * @since 1.0.0
 */
class MS_Rule_Shortcode_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = MS_Rule_Shortcode::RULE_ID;

	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular'] = __( 'Shortcode', 'memberdash' );
		$this->name['plural']   = __( 'Shortcodes', 'memberdash' );
	}

	public function get_columns() {
		return apply_filters(
			'membership_helper_listtable_' . $this->id . '_columns',
			array(
				'cb'     => true,
				'name'   => __( 'Shortcode', 'memberdash' ),
				'access' => true,
			)
		);
	}

	public function column_name( $item ) {
		$html = $item->name;
		return $html;
	}

}
