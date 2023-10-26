import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-membership-price', {
    title: 'MemberDash - Membership Price',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save() {
        return null;
    },
} );
