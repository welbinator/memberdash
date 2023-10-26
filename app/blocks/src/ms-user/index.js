import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-user', {
    title: 'MemberDash - User',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save() {
        return null;
    },
} );
