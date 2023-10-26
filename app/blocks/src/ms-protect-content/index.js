import Edit from './edit';
import {InnerBlocks} from "@wordpress/block-editor";

wp.blocks.registerBlockType( 'memberdash/ms-protect-content', {
    title: 'MemberDash - Protect Content',
    icon: 'text',
    category: 'memberdash-blocks',
    example: {},
    edit: Edit,
    save( props ) {
        return (
            <InnerBlocks.Content />
        )
    },
} );
