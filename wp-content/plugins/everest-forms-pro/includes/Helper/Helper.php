<?php
/**
 * Core function for the plugin.
 *
 * @package EverestForms\Pro\Helper
 * @since 1.9.7
 */

namespace EverestForms\Pro\Helper;

/**
 * FormHelper.
 *
 * @since 1.9.7
 */
class Helper {

	/**
	 * Check if the field entries are editable.
	 *
	 * @since 1.9.7
	 *
	 * @param string $field_type Field type.
	 *
	 * @return bool
	 */
	public static function is_field_entries_editable( $field_type ) {

		$allowed_field_types = array(
			'first-name',
			'last-name',
			'text',
			'textarea',
			'wysiwyg',
			'select',
			'radio',
			'checkbox',
			'number',
			'email',
			'url',
			'date-time',
			'phone',
			'address',
			'country',
			'range-slider',
			'rating',
			'file-upload',
			'image-upload',
			'signature',
			'color'
		);
		if ( is_admin() ) {
			$allowed_field_types = array_merge( $allowed_field_types, array( 'hidden' ) );
		}
		return (bool) apply_filters(
			'everest_forms_entries_field_editable',
			in_array(
				$field_type,
				$allowed_field_types,
				true
			),
			$field_type
		);
	}

}
