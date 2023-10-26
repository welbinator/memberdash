import { InspectorControls } from '@wordpress/block-editor';
import {TextControl, PanelBody, PanelRow, ToggleControl} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( props ) {
    function show_membership( value ) {
        props.setAttributes( { show_membership: value } );
    }
    function membership_title( value ) {
        props.setAttributes( { membership_title: value } );
    }
    function show_membership_change( value ) {
        props.setAttributes( { show_membership_change: value } );
    }
    function membership_change_label( value ) {
        props.setAttributes( { membership_change_label: value } );
    }
    function show_profile( value ) {
        props.setAttributes( { show_profile: value } );
    }
    function profile_title( value ) {
        props.setAttributes( { profile_title: value } );
    }
    function show_profile_change( value ) {
        props.setAttributes( { show_profile_change: value } );
    }
    function profile_change_label( value ) {
        props.setAttributes( { profile_change_label: value } );
    }
    function show_invoices( value ) {
        props.setAttributes( { show_invoices: value } );
    }
    function invoices_title( value ) {
        props.setAttributes( { invoices_title: value } );
    }
    function limit_invoices( value ) {
        props.setAttributes( { limit_invoices: value } );
    }
    function show_all_invoices( value ) {
        props.setAttributes( { show_all_invoices: value } );
    }
    function invoices_details_label( value ) {
        props.setAttributes( { invoices_details_label: value } );
    }
    function show_activity( value ) {
        props.setAttributes( { show_activity: value } );
    }
    function activity_title( value ) {
        props.setAttributes( { activity_title: value } );
    }
    function limit_activities( value ) {
        props.setAttributes( { limit_activities: value } );
    }
    function show_all_activities( value ) {
        props.setAttributes( { show_all_activities: value } );
    }
    function activity_details_label( value ) {
        props.setAttributes( { activity_details_label: value } );
    }

    return (
        <div className="ms-membership-account">
            <InspectorControls>
                <PanelBody title="Membership section">
                    <PanelRow>
                        <ToggleControl label="Show membership" checked={ props.attributes.show_membership } onChange={ show_membership } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Default's to Your Membership if left blank" label="Membership title" value={ props.attributes.membership_title } onChange={ membership_title } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Show membership change" checked={ props.attributes.show_membership_change } onChange={ show_membership_change } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Default's to Change if left blank" label="Membership change label" value={ props.attributes.membership_change_label } onChange={ membership_change_label } />
                    </PanelRow>
                </PanelBody>
                <PanelBody title="Profile section">
                    <PanelRow>
                        <ToggleControl label="Show profile" checked={ props.attributes.show_profile } onChange={ show_profile } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Default's to Personal details if left blank" label="Profile title" value={ props.attributes.profile_title } onChange={ profile_title } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Show profile change" checked={ props.attributes.show_profile_change } onChange={ show_profile_change } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Default's to Edit if left blank" label="Profile change label" value={ props.attributes.profile_change_label } onChange={ profile_change_label } />
                    </PanelRow>
                </PanelBody>
                <PanelBody title="Invoices section">
                    <PanelRow>
                        <ToggleControl label="Show invoices" checked={ props.attributes.show_invoices } onChange={ show_invoices } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Default's to Invoices if left blank" label="Invoices title" value={ props.attributes.invoices_title } onChange={ invoices_title } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl type="Number" help="Default's to 10 if left blank" label="Limit invoices" value={ props.attributes.limit_invoices } onChange={ limit_invoices } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Show all invoices" checked={ props.attributes.show_all_invoices } onChange={ show_all_invoices } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Default's to View all if left blank" label="Invoices details label" value={ props.attributes.invoices_details_label } onChange={ invoices_details_label } />
                    </PanelRow>
                </PanelBody>
                <PanelBody title="Activities section">
                    <PanelRow>
                        <ToggleControl label="Show activity" checked={ props.attributes.show_activity } onChange={ show_activity } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Default's to Activities if left blank" label="Activity title" value={ props.attributes.activity_title } onChange={ activity_title } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl type="Number" help="Default's to 10 if left blank" label="Limit activities" value={ props.attributes.limit_activities } onChange={ limit_activities } />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl label="Show all activities" checked={ props.attributes.show_all_activities } onChange={ show_all_activities } />
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="Default's to View all if left blank" label="Activity details label" value={ props.attributes.activity_details_label } onChange={ activity_details_label } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="memberdash/ms-membership-account"
                attributes={ {
                    show_membership: props.attributes.show_membership,
                    membership_title: props.attributes.membership_title,
                    show_membership_change: props.attributes.show_membership_change,
                    membership_change_label: props.attributes.membership_change_label,
                    show_profile: props.attributes.show_profile,
                    profile_title: props.attributes.profile_title,
                    show_profile_change: props.attributes.show_profile_change,
                    profile_change_label: props.attributes.profile_change_label,
                    show_invoices: props.attributes.show_invoices,
                    invoices_title: props.attributes.invoices_title,
                    limit_invoices: props.attributes.limit_invoices,
                    show_all_invoices: props.attributes.show_all_invoices,
                    invoices_details_label: props.attributes.invoices_details_label,
                    show_activity: props.attributes.show_activity,
                    activity_title: props.attributes.activity_title,
                    limit_activities: props.attributes.limit_activities,
                    show_all_activities: props.attributes.show_all_activities,
                    activity_details_label: props.attributes.activity_details_label,
                } }
            />
        </div>
    );
}
