import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-membership-account', {
    title: 'MemberDash - Membership Account',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save() {
        return null;
    },
} );
