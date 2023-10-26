import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-membership-note', {
    title: 'MemberDash - Membership note',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save() {
        return null;
    },
} );
