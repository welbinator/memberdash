import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-membership-register-user', {
    title: 'MemberDash - Membership Register User',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save() {
        return null;
    },
} );
