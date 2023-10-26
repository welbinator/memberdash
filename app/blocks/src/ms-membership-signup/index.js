import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-membership-signup', {
    title: 'MemberDash - Membership Signup',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save() {
        return null;
    },
} );
