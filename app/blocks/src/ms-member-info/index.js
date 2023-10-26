import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-member-info', {
    title: 'MemberDash - Member Info',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save() {
        return null;
    },
} );
