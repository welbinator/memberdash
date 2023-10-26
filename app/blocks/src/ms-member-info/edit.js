import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
export default function Edit( props ) {
    function update_value( val ) {
        props.setAttributes( { value: val } );
    }
    function defaultValue( val ) {
        props.setAttributes( { default: val } );
    }
    function before( value ) {
        props.setAttributes( { before: value } );
    }
    function after( value ) {
        props.setAttributes( { after: value } );
    }
    function custom_field( value ) {
        props.setAttributes( { custom_field: value } );
    }
    function list_separator( value ) {
        props.setAttributes( { list_separator: value } );
    }
    function list_before( value ) {
        props.setAttributes( { list_before: value } );
    }
    function list_after( value ) {
        props.setAttributes( { list_after: value } );
    }
    function user( value ) {
        props.setAttributes( { user: value } );
    }
    return (
        <div className="ms-member-info">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
                        <TextControl type="Number" help=" (User-ID) Use this to display data of any user. If not specified then the current user is displayed" label="User" value={ props.attributes.user } onChange={ user } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="(email|firstname|lastname|fullname|memberships|custom) Defines which value to display. A custom field can be set via the API (you find the API docs on the Advanced Settings tab) " label="Value" value={ props.attributes.value } onChange={ update_value } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="(Text) Default value to display when the defined field is empty" label="Default" value={ props.attributes.default } onChange={ defaultValue } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="(Text) Display this text before the field value. Only used when the field is not empty " label="Before" value={ props.attributes.before } onChange={ before } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="(Text) Display this text after the field value. Only used when the field is not empty" label="After" value={ props.attributes.after } onChange={ after } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="(Text) Only relevant for the value custom. This is the name of the custom field to get" label="Custom field" value={ props.attributes.custom_field } onChange={ custom_field } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="(Text) Used when the field value is a list (i.e. Membership list or contents of a custom field) " label="List separator" value={ props.attributes.list_separator } onChange={ list_separator } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="(Text) Used when the field value is a list (i.e. Membership list or contents of a custom field)" label="List before" value={ props.attributes.list_before } onChange={ list_before } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="(Text) Used when the field value is a list (i.e. Membership list or contents of a custom field)" label="List after" value={ props.attributes.list_after } onChange={ list_after } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="memberdash/ms-member-info"
                attributes={ {
                    value: props.attributes.value,
                    default: props.attributes.default,
                    before: props.attributes.before,
                    after: props.attributes.after,
                    custom_field: props.attributes.custom_field,
                    list_separator: props.attributes.list_separator,
                    list_before: props.attributes.list_before,
                    list_after: props.attributes.list_after,
                    user: props.attributes.user,
                } }
            />
        </div>
    );
}
