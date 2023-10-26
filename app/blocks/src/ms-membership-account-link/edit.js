import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( props ) {
    function label( value ) {
        props.setAttributes( { label: value } );
    }
    return (
        <div className="ms-invoice-wrapper">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
                        <TextControl help="Default's to Visit your account page for more information if left blank" label="Label" value={ props.attributes.label } onChange={ label } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="memberdash/ms-membership-account-link"
                attributes={ {
                    label: props.attributes.label,
                } }
            />
        </div>
    );

}
