import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-invoice', {
    title: 'MemberDash - Invoice',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save() {
        return null;
    },
} );
