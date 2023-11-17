import Edit from './edit';

wp.blocks.registerBlockType('memberdash/ms-membership-logout', {
	title: 'MemberDash - Membership Logout',
	icon: 'text',
	category: 'memberdash-blocks',
	example: {},
	edit: Edit,
	save() {
		return null;
	},
});
