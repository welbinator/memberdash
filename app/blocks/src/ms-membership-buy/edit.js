import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useSelect } from '@wordpress/data';
const __ = wp.i18n.__;

export default function Edit( props ) {
    function id( event ) {
        props.setAttributes( { id: event.target.value } );
    }
    function label( value ) {
        props.setAttributes( { label: value } );
    }

    const all_memberships = useSelect( ( select ) => {
        return select( 'core' ).getEntityRecords( 'memberdash-blocks/v1', 'memberships/list', {} );
    } );

    if ( all_memberships === undefined || all_memberships === null ) {
        return <p>Loading Block...</p>;
    }

    if ( props.attributes.id === undefined || props.attributes.id === '' ) {
        return (
            <div className="ms-membership-buy">
                <InspectorControls>
                    <PanelBody title="Block Settings">
                        <PanelRow>
							<select value={ props.attributes.id } onChange={ id }>
                                <option value="">{ __( 'Select a Membership', 'featured-professor' ) }</option>
                                { all_memberships.map( ( membership ) => {
                                    return (
                                        <option key={ membership.id } value={ membership.id }>
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
        <div className="ms-membership-buy">
            <InspectorControls>
                <PanelBody title="Block Settings">
                    <PanelRow>
						<select value={ props.attributes.id } onChange={ id }>
                            <option value="">{ __( 'Select a Membership', 'featured-professor' ) }</option>
                            { all_memberships.map( ( membership ) => {
                                return (
                                    <option key={ membership } value={ membership.id }>
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
                block="memberdash/ms-membership-buy"
                attributes={ {
                    id: props.attributes.id,
                    label: props.attributes.label,
                } }
            />
        </div>
    );
}
