import {InnerBlocks, InspectorControls, useBlockProps} from '@wordpress/block-editor';
import {TextControl, PanelBody, PanelRow, ToggleControl} from '@wordpress/components';

export default function Edit( props ) {
    const blockProps = useBlockProps();
    const block_style = {
        backgroundColor: '#EEE',
        padding: '4px',
        fontSize: '12px',
        color: '#666',
        fontWeight: 'bold',
        opacity: '0.5',
        cursor: 'pointer',
    };

    function id( value ) {
        props.setAttributes( { id: value } );
    }
    function access( value ) {
        props.setAttributes( { access: value } );
    }
    function silent( value ) {
        props.setAttributes( { silent: value } );
    }

    return (
        <div className="ms-protect-content">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
                        <TextControl type="number" help="Only members with following membership id can access the content to display. Accepts comma separated list of IDs" label="Membership ID" value={ props.attributes.id } onChange={ id } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl help="(yes|no) Defines if members of the memberships can see or not see the content" label="Access" checked={ props.attributes.access } onChange={ access } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl help="yes|no) Silent protection removes content without displaying any message to the user" label="Silent" checked={ props.attributes.silent } onChange={ silent } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <div style={ block_style }>Start of [ms-protect-content]</div>
            <div { ...blockProps }>
                <InnerBlocks />
            </div>
            <div style={ block_style }>End of [/ms-protect-content]</div>
        </div>
    );
}
