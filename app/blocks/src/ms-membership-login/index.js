import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-membership-login', {
    title: 'MemberDash - Membership Login',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save() {
        return null;
    },
} );
