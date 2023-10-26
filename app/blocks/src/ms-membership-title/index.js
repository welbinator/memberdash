import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';

registerBlockType( 'memberdash/ms-membership-title', {
	title: 'MemberDash - Membership Title',
	icon: 'text',
	category: 'memberdash-blocks',
	example: {},
	edit: Edit,
	save() {
		return null;
	},
} );
