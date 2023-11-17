import Edit from './edit';

wp.blocks.registerBlockType('memberdash/ms-membership-thank-you-page', {
	title: 'MemberDash - Thank You Page',
	icon: 'text',
	category: 'memberdash-blocks',
	example: {},
	edit: Edit,
	save() {
		return null;
	},
});
