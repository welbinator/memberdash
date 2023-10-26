import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-membership-details', {
    title: 'MemberDash - Membership Details',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save() {
        return null;
    },
} );
