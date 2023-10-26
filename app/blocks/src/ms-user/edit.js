import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import {useState} from "@wordpress/element";

export default function Edit( props ) {
    const [ message, setMessage ] = useState( props.attributes.msg || '' )
    const [ userType, setUserType ] = useState( props.attributes.type || 'loggedin' )

    function updateType( value ) {
        props.setAttributes( { type: value } )
        setUserType( value )
    }
    function updateMsg( value ) {
        props.setAttributes( { msg: value } )
        setMessage( value )
    }
    return (
        <div className="ms-user">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
                        <TextControl help="Message to display" label="Message" value={ message } onChange={ updateMsg } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="(all|loggedin|guest|admin|non-admin) Decide, which type of users will see the message. Default is loggedin" label="Type" value={ userType } onChange={ updateType } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="memberdash/ms-user"
                attributes={ {
                    msg: props.attributes.msg,
                    type: props.attributes.type,
                } }
            />
        </div>
    );
}
