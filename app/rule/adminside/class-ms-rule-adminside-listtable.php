<?php
/**
 * Membership List Table
 *
 * @since 1.0.0
 */
class MS_Rule_Adminside_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = MS_Rule_Adminside::RULE_ID;

	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular']       = __( 'Admin Page', 'memberdash' );
		$this->name['plural']         = __( 'Admin Pages', 'memberdash' );
		$this->name['default_access'] = __( 'Handled by WordPress', 'memberdash' );
	}

	public function get_columns() {
		$columns = array(
			'cb'     => true,
			'name'   => __( 'Admin Side Page', 'memberdash' ),
			'access' => true,
		);

		return apply_filters(
			"ms_helper_listtable_{$this->id}_columns",
			$columns
		);
	}

	public function column_name( $item ) {
		return $item->post_title;
	}

}
