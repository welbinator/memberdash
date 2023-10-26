<?php
/**
 * Membership List Table
 *
 * @since 1.0.0
 */
class MS_Rule_MemberRoles_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = MS_Rule_MemberRoles::RULE_ID;

	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular']       = __( 'Role', 'memberdash' );
		$this->name['plural']         = __( 'Roles', 'memberdash' );
		$this->name['default_access'] = __( 'Default WordPress Logic', 'memberdash' );
	}

	public function get_columns() {
		$name_label = __( 'Role', 'memberdash' );

		$columns = array(
			'cb'     => true,
			'name'   => $name_label,
			'access' => true,
		);

		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_columns',
			$columns
		);
	}

	public function column_name( $item ) {
		return $item->post_title;
	}

}
