import Edit from './edit';

wp.blocks.registerBlockType( 'memberdash/ms-membership-account-link', {
    title: 'MemberDash - Account Link',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save( props ) {
        return null;
    },
} );
