import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( props ) {
    function membership_signup_text( value ) {
        props.setAttributes( { membership_signup_text: value } );
    }
    function membership_move_text( value ) {
        props.setAttributes( { membership_move_text: value } );
    }
    function membership_cancel_text( value ) {
        props.setAttributes( { membership_cancel_text: value } );
    }
    function membership_renew_text( value ) {
        props.setAttributes( { membership_renew_text: value } );
    }
    function membership_pay_text( value ) {
        props.setAttributes( { membership_pay_text: value } );
    }
    return (
        <div className="ms-membership-signup">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
                        <TextControl label="Signup Text" value={ props.attributes.membership_signup_text } onChange={ membership_signup_text } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl label="Signup Text" value={ props.attributes.membership_move_text } onChange={ membership_move_text } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl label="Signup Text" value={ props.attributes.membership_cancel_text } onChange={ membership_cancel_text } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl label="Signup Text" value={ props.attributes.membership_renew_text } onChange={ membership_renew_text } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl label="Signup Text" value={ props.attributes.membership_pay_text } onChange={ membership_pay_text } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="memberdash/ms-membership-signup"
                attributes={ {
                    membership_signup_text: props.attributes.membership_signup_text,
                    membership_move_text: props.attributes.membership_move_text,
                    membership_cancel_text: props.attributes.membership_cancel_text,
                    membership_renew_text: props.attributes.membership_renew_text,
                    membership_pay_text: props.attributes.membership_pay_text,
                } }
            />
        </div>
    );
}
