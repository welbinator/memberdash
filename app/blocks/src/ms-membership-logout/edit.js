import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { TextControl, PanelBody, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit(props) {
	function redirectLogout(value) {
		props.setAttributes({ redirect_logout: value });
	}
	function holder(value) {
		props.setAttributes({ holder: value });
	}
	function holderClass(value) {
		props.setAttributes({ holderclass: value });
	}

	return (
		<div className="ms-membership-logout">
			<InspectorControls>
				<PanelBody title="Block Settings">
					<PanelRow>
						<TextControl
							help={__(
								'The page to display after the user was logged out.',
								'memberdash'
							)}
							label={__('Redirect Logout', 'memberdash')}
							value={props.attributes.redirect_logout}
							onChange={redirectLogout}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__('Holder', 'memberdash')}
							value={props.attributes.holder}
							onChange={holder}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__('Holder Class', 'memberdash')}
							value={props.attributes.holderclass}
							onChange={holderClass}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="memberdash/ms-membership-logout"
				attributes={{
					redirect_logout: props.attributes.redirect_logout,
					holder: props.attributes.holder,
					holderclass: props.attributes.holderclass,
				}}
			/>
		</div>
	);
}
