import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import {
	TextControl,
	TextareaControl,
	PanelBody,
	PanelRow,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Edit function for the membership thank you page block.
 *
 * @param {Object} props The block properties.
 *
 * @return {Object} The block edit component.
 */
export default function Edit(props) {
	/**
	 * Update the fallback message.
	 *
	 * @param {string} value The new message.
	 */
	function fallbackMessage(value) {
		props.setAttributes({ fallback_message: value });
	}

	/**
	 * Update the fallback link label.
	 *
	 * @param {string} value The new label.
	 */
	function fallbackLinkLabel(value) {
		props.setAttributes({ fallback_link_label: value });
	}

	/**
	 * Update the free membership message.
	 *
	 * @param {string} value The new message.
	 */
	function freeMembershipMessage(value) {
		props.setAttributes({ free_membership_message: value });
	}

	/**
	 * Update the paid membership message.
	 *
	 * @param {string} value The new message.
	 */
	function paidMembershipMessage(value) {
		props.setAttributes({ paid_membership_message: value });
	}

	return (
		<div className="ms-membership-thank-you-page">
			<InspectorControls>
				<PanelBody title="Block Settings">
					<PanelRow>
						<TextareaControl
							// translators: %link%: Placeholder for the redirect URL.
							help={__(
								'Keep the "%link%" placeholder to display a redirect URL.',
								'memberdash'
							)}
							label={__('Message for non-members', 'memberdash')}
							value={props.attributes.fallback_message}
							onChange={fallbackMessage}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							help={__(
								'Non-members redirect URL label.',
								'memberdash'
							)}
							label={__('Redirect URL label', 'memberdash')}
							value={props.attributes.fallback_link_label}
							onChange={fallbackLinkLabel}
						/>
					</PanelRow>
					<PanelRow>
						<TextareaControl
							// translators: %membership_name%: Placeholder for the membership name.
							help={__(
								'Keep the "%membership_name%" placeholder to display the membership name.',
								'memberdash'
							)}
							label={__('Message for free members', 'memberdash')}
							value={props.attributes.free_membership_message}
							onChange={freeMembershipMessage}
						/>
					</PanelRow>
					<PanelRow>
						<TextareaControl
							// translators: %membership_name%: Placeholder for the membership name.
							help={__(
								'Keep the "%membership_name%" placeholder to display the membership name.',
								'memberdash'
							)}
							label={__('Message for paid members', 'memberdash')}
							value={props.attributes.paid_membership_message}
							onChange={paidMembershipMessage}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="memberdash/ms-membership-thank-you-page"
				attributes={{
					...props.attributes,
				}}
			/>
		</div>
	);
}
