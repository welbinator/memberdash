import { InspectorControls } from '@wordpress/block-editor';
import {TextControl, PanelBody, PanelRow, SelectControl, ToggleControl} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
const __ = wp.i18n.__;

export default function Edit( props ) {
    function title( value ) {
        props.setAttributes( { title: value } );
    }
    function first_name( value ) {
        props.setAttributes( { first_name: value } );
    }
    function last_name( value ) {
        props.setAttributes( { last_name: value } );
    }
    function username( value ) {
        props.setAttributes( { username: value } );
    }
    function email( value ) {
        props.setAttributes( { email: value } );
    }
    function setMembershipId( value ) {
        props.setAttributes( { membership_id: value } );
    }
    function loginlink( value ) {
        props.setAttributes( { loginlink: value } );
    }
    function label_first_name( value ) {
        props.setAttributes( { label_first_name: value } );
    }
    function label_last_name( value ) {
        props.setAttributes( { label_last_name: value } );
    }
    function label_username( value ) {
        props.setAttributes( { label_username: value } );
    }
    function label_email( value ) {
        props.setAttributes( { label_email: value } );
    }
    function label_password( value ) {
        props.setAttributes( { label_password: value } );
    }
    function label_password2( value ) {
        props.setAttributes( { label_password2: value } );
    }
    function label_register( value ) {
        props.setAttributes( { label_register: value } );
    }
    function hint_first_name( value ) {
        props.setAttributes( { hint_first_name: value } );
    }
    function hint_last_name( value ) {
        props.setAttributes( { hint_last_name: value } );
    }
    function hint_username( value ) {
        props.setAttributes( { hint_username: value } );
    }
    function hint_email( value ) {
        props.setAttributes( { hint_email: value } );
    }
    function hint_password( value ) {
        props.setAttributes( { hint_password: value } );
    }
    function hint_password2( value ) {
        props.setAttributes( { hint_password2: value } );
    }

    const all_memberships = useSelect( ( select ) => {
        return select( 'core' ).getEntityRecords( 'memberdash-blocks/v1', 'memberships/list', {} );
    } );

    if ( all_memberships === undefined || all_memberships === null ) {
        return <p>Loading Block...</p>;
    }

    return (
        <div className="ms-membership-register-user">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
                        <TextControl help="Title of the register form" label="Title" value={ props.attributes.title } onChange={ title } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Initial value for first name" label="First name" value={ props.attributes.first_name } onChange={ first_name } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Initial value for last name" label="Last name" value={ props.attributes.last_name } onChange={ last_name } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Initial value for username" label="Username" value={ props.attributes.username } onChange={ username } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Initial value for email address" label="Email" value={ props.attributes.email } onChange={ email } />
                    </PanelRow>
                    <PanelRow>
                        <SelectControl label="Membership" value={ props.attributes.membership_id } onChange={ setMembershipId }>
                            <option value={ props.attributes.membership_id }>{ __( 'Select a Membership', 'featured-professor' ) }</option>
                            { all_memberships.map( ( membership ) => {
                                return (
                                    <option key={ membership.id } value={ membership.id } >
                                        { membership.name }
                                    </option>
                                );
                            } ) }
                        </SelectControl>
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Show Login Link" checked={ props.attributes.loginlink } onChange={ loginlink } />
                    </PanelRow>
                </PanelBody>
                <PanelBody title="Field labels">
                    <PanelRow>
                        <TextControl help="" label="Label first name" value={ props.attributes.label_first_name } onChange={ label_first_name } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label last name" value={ props.attributes.label_last_name } onChange={ label_last_name } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label username" value={ props.attributes.label_username } onChange={ label_username } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label email" value={ props.attributes.label_email } onChange={ label_email } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label password" value={ props.attributes.label_password } onChange={ label_password } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label password2" value={ props.attributes.label_password2 } onChange={ label_password2 } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="" label="Label register" value={ props.attributes.label_register } onChange={ label_register } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Placeholder inside Field" label="Hint first name" value={ props.attributes.hint_first_name } onChange={ hint_first_name } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Placeholder inside Field" label="Hint last name" value={ props.attributes.hint_last_name } onChange={ hint_last_name } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Placeholder inside Field" label="Hint username" value={ props.attributes.hint_username } onChange={ hint_username } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Placeholder inside Field " label="Hint email" value={ props.attributes.hint_email } onChange={ hint_email } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Placeholder inside Field" label="Hint password" value={ props.attributes.hint_password } onChange={ hint_password } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Placeholder inside Field" label="Hint password2" value={ props.attributes.hint_password2 } onChange={ hint_password2 } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <p><strong>[ms-membership-register-user]</strong> <em>This is a Registration form. Only non-logged-in users can see this form. Please open incognito to preview.</em></p>
        </div>
    );
}
