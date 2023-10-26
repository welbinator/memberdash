import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useSelect } from '@wordpress/data';
const __ = wp.i18n.__;

export default function Edit( props ) {
    function id( value ) {
        props.setAttributes( { id: value } );
    }
    function label( value ) {
        props.setAttributes( { label: value } );
    }

    const all_memberships = useSelect( ( select ) => {
        return select( 'core' ).getEntityRecords( 'memberdash-blocks/v1', 'memberships/list', {} );
    } );

    if ( all_memberships == undefined ) {
        return <p>Loading Block...</p>;
    }

    if ( props.attributes.id == undefined || props.attributes.id == '' ) {
        return (
            <div className="ms-membership-details">
                <InspectorControls>
                    <PanelBody title="Block Settings">
                        <PanelRow>
                            <select onChange={ ( e ) => props.setAttributes( { id: e.target.value } ) }>
                                <option value="">{ __( 'Select a Membership', 'featured-professor' ) }</option>
                                { all_memberships.map( ( membership ) => {
                                    return (
                                        <option value={ membership.id } selected={ props.attributes.id == membership.id }>
                                            { membership.name }
                                        </option>
                                    );
                                } ) }
                            </select>
                        </PanelRow>
                        <PanelRow>
                            <TextControl help="The membership label" label="Label" value={ props.attributes.label } onChange={ label } />
                        </PanelRow>
                    </PanelBody>
                </InspectorControls>
                <h3>Please choose a Membership</h3>
            </div>
        );
    }
    return (
        <div className="ms-membership-details">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
                        <select onChange={ ( e ) => props.setAttributes( { id: e.target.value } ) }>
                            <option value="">{ __( 'Select a Membership', 'featured-professor' ) }</option>
                            { all_memberships.map( ( membership ) => {
                                return (
                                    <option value={ membership.id } selected={ props.attributes.id == membership.id }>
                                        { membership.name }
                                    </option>
                                );
                            } ) }
                        </select>
                    </PanelRow>
                    <PanelRow>
                        <TextControl help="The membership label" label="Label" value={ props.attributes.label } onChange={ label } />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="memberdash/ms-membership-details"
                attributes={ {
                    id: props.attributes.id,
                    label: props.attributes.label,
                } }
            />
        </div>
    );
}
