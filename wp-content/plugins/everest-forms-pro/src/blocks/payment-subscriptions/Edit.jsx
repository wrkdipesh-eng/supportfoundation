/**
 * Block editor UI: Payment History.
 */
import { createElement } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const BLOCK_NAME = 'everest-forms/payment-subscriptions';

function getFormsOptions() {
	const fromPro =
		typeof window._EVF_PS_BLOCK_ !== 'undefined' &&
		Array.isArray( window._EVF_PS_BLOCK_.forms )
			? window._EVF_PS_BLOCK_.forms
			: null;
	const fromCore =
		typeof window._EVF_BLOCKS_ !== 'undefined' &&
		Array.isArray( window._EVF_BLOCKS_.forms )
			? window._EVF_BLOCKS_.forms
			: [];
	const forms = fromPro || fromCore;
	const options = [
		{ value: 0, label: __( 'All forms', 'everest-forms-pro' ) },
	];
	forms.forEach( ( f ) => {
		options.push( {
			value: parseInt( f.ID, 10 ),
			label: f.post_title || '#' + f.ID,
		} );
	} );
	return options;
}

export default function Edit( props ) {
	const {
		attributes: { formId = 0 },
		setAttributes,
	} = props;

	const blockProps = useBlockProps( {
		className: 'evf-block-payment-subscriptions-editor',
	} );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Payment History', 'everest-forms-pro' ) }>
					<SelectControl
						label={ __( 'Limit to form', 'everest-forms-pro' ) }
						value={ formId }
						options={ getFormsOptions() }
						onChange={ ( v ) =>
							setAttributes( { formId: parseInt( v, 10 ) || 0 } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block={ BLOCK_NAME }
				attributes={ props.attributes }
			/>
		</div>
	);
}
