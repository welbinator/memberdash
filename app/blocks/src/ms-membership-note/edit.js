import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useState } from '@wordpress/element';

export default function Edit( props ) {
    const [ noteMessage, setNoteMessage ] = useState( props.attributes.note || '' )
    const [ noteType, setNoteType ] = useState( props.attributes.type || 'info' )
    const [ noteClass, setNoteClass ] = useState( props.attributes.class || '' )

    function updateType( value ) {
        props.setAttributes( { type: value } )
        setNoteType( value )
    }
    function updateClass( value ) {
        props.setAttributes( { class: value } )
        setNoteClass( value )
    }
    function note( value ) {
        props.setAttributes( { note: value } )
        setNoteMessage( value )
    }
    return (
        <div className="ms-note">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
                        <TextControl label="Message to display" value={ noteMessage } onChange={ note } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="(info|warning) Default is info" label="Type" value={ noteType } onChange={ updateType } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="An additional CSS class that should be added to the notice" label="Class" value={ noteClass } onChange={ updateClass } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="memberdash/ms-membership-note"
                attributes={ {
                    note: props.attributes.note,
                    type: props.attributes.type,
                    class: props.attributes.class,
                } }
            />
        </div>
    );
}
