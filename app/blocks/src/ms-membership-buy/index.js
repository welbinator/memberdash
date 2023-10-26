import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-membership-buy', {
    title: 'MemberDash - Membership Buy',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save( props ) {
        return null;
    },
} );
