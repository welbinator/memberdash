import { InspectorControls } from '@wordpress/block-editor';
import {TextControl, PanelBody, PanelRow, ToggleControl} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( props ) {
    function updateId( value ) {
        props.setAttributes( { id: value } );
    }
    function updatePayButton( value ) {
        props.setAttributes( { pay_button: value } );
    }
    return (
        <div className="ms-invoice-wrapper">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
                        <TextControl type="number" label="ID" value={ props.attributes.id } onChange={ updateId } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Pay Button" checked={ props.attributes.pay_button } onChange={ updatePayButton } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="memberdash/ms-invoice"
                attributes={ {
                    id: props.attributes.id,
                    pay_button: props.attributes.pay_button,
                } }
            />
        </div>
    );
}
