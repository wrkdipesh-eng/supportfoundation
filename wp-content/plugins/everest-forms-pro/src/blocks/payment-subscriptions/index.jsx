/**
 * Registers the Payment History block (editor script).
 */
import { createElement } from '@wordpress/element';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './Edit';

const icon = (
	<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
		<path
			fill="currentColor"
			d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8h16v10zm-8-4c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"
		/>
	</svg>
);

registerBlockType( 'everest-forms/payment-subscriptions', {
	title: __( 'Payment History', 'everest-forms-pro' ),
	description: __(
		"Display the current user's payment history and subscriptions from Everest Forms entries.",
		'everest-forms-pro'
	),
	category: 'everest-forms',
	icon,
	supports: {
		html: false,
		align: [ 'wide', 'full' ],
	},
	edit: Edit,
	save: () => null,
} );
