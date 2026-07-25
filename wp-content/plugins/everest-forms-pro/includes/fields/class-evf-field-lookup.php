<?php
/**
 * Lookup field
 *
 * @package EverestForms_Pro\Fields
 * @since   1.6.7.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Lookup Class.
 */
class EVF_Field_Lookup extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Lookup', 'everest-forms-pro' );
		$this->type     = 'lookup';
		$this->icon     = 'evf-icon evf-icon-lookup';
		$this->order    = 250;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
					'choose_lookup_format',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'lookup_options',
					'placeholder',
					'label_hide',
					'css',
					'field_visiblity',
				),
			),
		);
		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
		add_action( 'wp_ajax_nopriv_everest_forms_lookup_field_filters', array( $this, 'evf_lookup_field_filters' ) );
		add_action( 'wp_ajax_everest_forms_lookup_field_filters', array( $this, 'evf_lookup_field_filters' ) );
		add_action( 'wp_ajax_evf_field_name_list_for_lookup', array( $this, 'evf_field_name_list_for_lookup' ) );
	}

	/**
	 * Lookup field format option.
	 *
	 * @since 1.6.7.1
	 * @param array $field Field Data.
	 */
	public function choose_lookup_format( $field ) {
		$format        = ! empty( $field['lookup_format'] ) ? esc_attr( $field['lookup_format'] ) : 'dropdown';
		$format_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lookup_format',
				'value'   => __( 'Lookup Format', 'everest-forms-pro' ),
				'tooltip' => __( 'Select a format to display the lookup data.', 'everest-forms-pro' ),
			),
			false
		);
		$format_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'lookup_format',
				'value'   => $format,
				'options' => array(
					'dropdown' => __( 'Dropdown', 'everest-forms-pro' ),
				),
			),
			false
		);
		$args          = array(
			'slug'    => 'lookup_format',
			'content' => $format_label . $format_select,
		);
		$this->field_element( 'row', $field, $args );
	}
	/**
	 * To get form list.
	 */
	private function evf_form_list() {
		$current_form_id = isset( $_REQUEST['form_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['form_id'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification
		$args            = array(
			'post_type' => 'everest_form',
		);

		$posts     = get_posts( $args );
		$form_list = array( 'none' => esc_html__( '---Select Form---', 'everest-forms-pro' ) );

		foreach ( $posts as $post ) {
			if ( $current_form_id == $post->ID ) {
				continue;
			}
			$form_list[ $post->ID ] = $post->post_title;
		}

		return $form_list;
	}
	/**
	 * Form's field list.
	 *
	 * @param int $form_id form id.
	 */
	private function get_form_fields( $form_id ) {
		$lookup_field_name_option_list = array(
			'none' => __( '---Select Field---', 'everest-forms-pro' ),
		);
		if ( ! empty( $form_id ) && 'none' !== $form_id ) {
			$form          = json_decode( get_post_field( 'post_content', $form_id ) );
			$exclude_field = array( 'image-upload','signature' );
			$form_arr      = isset($form->form_fields) ? (array) $form->form_fields : array();
			foreach ( $form_arr as $key => $value ) {
				if ( in_array( $value->type, $exclude_field, true ) ) {
					continue;
				}
				$lookup_field_name_option_list[ $key ] = $value->label;
			}
		}
		return $lookup_field_name_option_list;
	}
	/**
	 * Lookup Options.
	 *
	 * @since 1.6.7.1
	 * @param array $field Field Data.
	 */
	public function lookup_options( $field ) {

		$lookup_options_label = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lookup_options',
				'value'   => __( 'Lookup Options', 'everest-forms-pro' ),
				'tooltip' => __( 'Select the form and field for lookup the data.', 'everest-forms-pro' ),
			),
			false
		);

		$args = array(
			'slug'    => 'lookup_option_lists`',
			'content' => $lookup_options_label,
		);
		$this->field_element( 'row', $field, $args );
		// select form.
		echo '<div class="format-selected-lookup format-selected">';
		echo '<div class="everest-forms-border-container everest-forms-lookup">';
		echo '<h4 class="everest-forms-border-container-title">' . esc_html__( 'Lookup Options', 'everest-forms' ) . '</h4>'; // phpcs:ignore WordPress.Security.NonceVerification
		$lookup_form_name               = ! empty( $field['lookup_form_name'] ) ? esc_attr( $field['lookup_form_name'] ) : '';
		$lookup_form_name_option_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lookup_form_name_option',
				'value'   => __( 'Select Form', 'everest-forms-pro' ),
				'class'   => 'evf-lookup-form-name-label',
				'tooltip' => __( 'Select the form for lookup the data.', 'everest-forms-pro' ),
			),
			false
		);
		$lookup_form_name_option_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'lookup_form_name',
				'value'   => $lookup_form_name,
				'class'   => 'evf-lookup-form-name-select',
				'options' => self::evf_form_list(),
			),
			false
		);

		// Select field.
		$lookup_field_name               = ! empty( $field['lookup_field_name'] ) ? esc_attr( $field['lookup_field_name'] ) : array( 'none' );
		$lookup_field_name_option_list   = self::get_form_fields( $lookup_form_name );
		$lookup_field_name_option_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lookup_form_field_option',
				'value'   => __( 'Select Field', 'everest-forms-pro' ),
				'class'   => 'evf-lookup-field-name-label',
				'tooltip' => __( 'Select the field for lookup the data.', 'everest-forms-pro' ),
			),
			false
		);
		$lookup_field_name_option_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'lookup_field_name',
				'value'   => $lookup_field_name,
				'class'   => 'evf-lookup-field-name-select',
				'options' => $lookup_field_name_option_list,
			),
			false
		);

		// Lookup filter by field.
		$lookup_filter_by_field  = isset( $field['lookup_filter_by_field_name'] ) ? $field['lookup_filter_by_field_name'] : array();
		$lookup_filter_by_fields = array();
		if ( isset( $_REQUEST['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form_id  = sanitize_text_field( wp_unslash( $_REQUEST['form_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$form     = json_decode( get_post_field( 'post_content', $form_id ) );
			$form_arr = (array) $form->form_fields;
			foreach ( $form_arr as $key => $value ) {
				if ( $field['id'] === $key ) {
					continue;
				}
				if ( 'lookup' === $value->type ) {
					$lookup_filter_by_fields[ $key ] = $value->label;
				}
			}
		}
		$lookup_filter_by_field_option_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lookup_filter_by_field_option',
				'value'   => __( 'Filter By Lookup Field', 'everest-forms-pro' ),
				'tooltip' => __( 'Select the fields to filter the lookup the data.', 'everest-forms-pro' ),
			),
			false
		);
		$lookup_filter_by_field_option_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'     => 'lookup_filter_by_field_name',
				'value'    => $lookup_filter_by_field,
				'multiple' => true,
				'class'    => 'evf-enhanced-select evf-lookup-filter-by-field-select',
				'options'  => $lookup_filter_by_fields,
			),
			false
		);
		// multi select.
		$lookup_multiple_select_option = isset( $field['lookup_multiple_select'] ) ? $field['lookup_multiple_select'] : false;
		$lookup_multi_select_checkbox  = $this->field_element(
			'toggle',
			$field,
			array(
				'slug'    => 'lookup_multiple_select',
				'value'   => evf_string_to_bool( $lookup_multiple_select_option ),
				'desc'    => __( 'Enable Multiple Select', 'everest-forms-pro' ),
				'tooltip' => __( 'Check to enable the multiple select option.', 'everest-forms-pro' ),

			),
			false
		);

		$args = array(
			'slug'    => 'lookup_option_lists',
			'content' => $lookup_form_name_option_label . $lookup_form_name_option_select . $lookup_field_name_option_label . $lookup_field_name_option_select . $lookup_filter_by_field_option_label . $lookup_filter_by_field_option_select . $lookup_multi_select_checkbox,
		);
		$this->field_element( 'row', $field, $args );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.6.7.1
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		// Define data.
		$form_id           = absint( $form_data['id'] );
		$field_id          = $field['id'];
		$field             = apply_filters( 'everest_forms_dropdown_options', $field );
		$filterbyfield     = isset( $field['lookup_filter_by_field_name'] ) ? $field['lookup_filter_by_field_name'] : array();
		$lookup_form_name  = isset( $field['lookup_form_name'] ) ? $field['lookup_form_name'] : '';
		$lookup_field_name = isset( $field['lookup_field_name'] ) ? $field['lookup_field_name'] : '';
		$display_format    = isset( $field['lookup_format'] ) ? $field['lookup_format'] : '';

		// Set input container (<select>) properties.
		$properties['input_container'] = array(
			'class' => array( 'input-text','evf-lookup' ),
			'data'  => array(
				'lookup-form-id'    => $lookup_form_name,
				'lookup-field-name' => $lookup_field_name,
				'filter-by'         => implode( ' ', $filterbyfield ),
				'display-format'    => $display_format,
			),
			'id'    => "evf-{$form_id}-field_{$field_id}",
			'attr'  => array(
				'name' => "everest_forms[form_fields][{$field_id}]",
			),
		);

		// Required class for validation.
		if ( ! empty( $field['required'] ) ) {
			$properties['input_container']['class'][] = 'evf-field-required';
		}

		return $properties;
	}


	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.6.7.1
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder    = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$field_type     = ! empty( $field['type'] ) ? esc_attr( $field['type'] ) : '';
		$display_format = ! empty( $field['lookup_format'] ) ? esc_attr( $field['lookup_format'] ) : 'dropdown';
		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		switch ( $display_format ) {
			case 'dropdown':
				echo '<select data-field-type="' . esc_attr( $field_type ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="widefat primary-input" disabled>';
				echo '<option value="option1">' . esc_html__( 'Option 1', 'everest-forms-pro' ) . '</option>';
				echo '<option value="option1">' . esc_html__( 'Option 1', 'everest-forms-pro' ) . '</option>';
				echo '<option value="option1">' . esc_html__( 'Option 1', 'everest-forms-pro' ) . '</option>';
				echo '</select>';
				break;
		}

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Get the form data.
	 *
	 * @since 1.6.7.1
	 *
	 * @param string  $field_id field id.
	 * @param integer $form_id form id.
	 */
	public function form_field_data( $field_id, $form_id ) {
		global $wpdb;
		$results            = $wpdb->get_results( $wpdb->prepare( "SELECT fields FROM {$wpdb->prefix}evf_entries WHERE form_id = %d AND status = %s", $form_id, 'publish' ) ); // WPCS: cache ok, DB call ok.
		$required_data_list = array( '' => esc_html__( 'Choose value', 'everest-forms-pro' ) );
		foreach ( $results as $result ) {
			$fields = (array) json_decode( $result->fields );
			if ( array_key_exists( $field_id, $fields ) ) {
				$field_value     = $fields[ $field_id ]->value;
				$new_select_list = array();
				if ( isset( $field_value->type ) ) {
					if ( 'checkbox' === $field_value->type ) {
						foreach ( $field_value->label as $label ) {
							$new_select_list[ "$label" ] = $label;
						}
					} elseif ( 'radio' === $field_value->type ) {
						$new_select_list[ "$field_value->label" ] = $field_value->label;
					}
				} elseif ( is_array( $field_value ) ) {
					foreach ( $field_value as $value ) {
						$new_select_list[ "$value" ] = $value;
					}
				} else {
					$new_select_list[ "$field_value" ] = $field_value;
				}
				$required_data_list = array_merge( $required_data_list, $new_select_list );
			}
		}
		return $required_data_list;
	}
	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.6.7.1
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {

		$container = $field['properties']['input_container'];
		$choices   = $field['properties']['inputs'];

		$format_type = $field['lookup_format'];
		// Optional placeholder.
		$field_placeholder = ! empty( $field['placeholder'] ) ? evf_string_translation( $form_data['id'], $field['id'], $field['placeholder'], '-placeholder' ) : '';
		$has_default       = false;
		// for multiple select.
		$lookup_multiple_select = isset( $field['lookup_multiple_select'] ) ? $field['lookup_multiple_select'] : false;
		$is_multiple            = false;
		if ( evf_string_to_bool( $lookup_multiple_select ) ) {
			$is_multiple                   = true;
			$container['attr']['multiple'] = 'multiple';

			// Change a name attribute.
			if ( ! empty( $container['attr']['name'] ) ) {
				$container['attr']['name'] .= '[]';
			}
			$container['class'][]             = 'evf-enhanced-select';
			$container['data']['placeholder'] = esc_attr( $field_placeholder );
		}
		// for filter by other  lookup field.
		$filterbyfield = isset( $field['lookup_filter_by_field_name'] ) ? $field['lookup_filter_by_field_name'] : array();
		if ( ! empty( $filterbyfield ) ) {
			array_push( $container['class'], 'evf-lookup-filter' );
		}
		// Conditional logic.
		if ( isset( $choices['primary'] ) ) {
			$container['attr']['conditional_id'] = $choices['primary']['attr']['conditional_id'];

			if ( isset( $choices['primary']['attr']['conditional_rules'] ) ) {
				$container['attr']['conditional_rules'] = $choices['primary']['attr']['conditional_rules'];
			}
		}

		switch ( $format_type ) {
			case 'dropdown':
				$field_name = 'everest_forms["form_fields"]["' . $field['id'] . '"]';
				$field_id   = 'evf' . $form_data['id'] . 'field' . $field['id'];
				if ( isset( $field['lookup_form_name'] ) && isset( $field['lookup_field_name'] ) ) {
					if ( 'none' !== $field['lookup_form_name'] || 'none' !== $field['lookup_field_name'] ) {
						$selected_form_field_datas = self::form_field_data( $field['lookup_field_name'], $field['lookup_form_name'] );
					}
				}
				printf(
					'<select %s >',
					evf_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] )
				);
				if ( ! empty( $field_placeholder ) ) {
					printf( '<option value="" class="placeholder" disabled %s>%s</option>', selected( false, $has_default || $is_multiple, false ), esc_html( $field_placeholder ) );
				}
				if ( ! empty( $selected_form_field_datas ) && empty( $filterbyfield ) ) {
					foreach ( $selected_form_field_datas as $key => $data ) {
						if ( ( $is_multiple && '' === $key ) || ( ! empty( $field_placeholder ) && '' === $key ) ) {
							continue;
						}
						printf( '<option value="%s">%s</option>', esc_attr( $key ), esc_attr( $data ) );
					}
				}
				printf( '</select>' );
				break;
		}
	}
	/**
	 * Give the List of field name
	 */
	public function evf_field_name_list_for_lookup() {
		try {
			check_ajax_referer( 'evf_lookup_field_nonce', 'security' );
			$form_id = isset( $_POST['formID'] ) ? sanitize_text_field( wp_unslash( $_POST['formID'] ) ) : '';
			if ( '' === $form_id ) {
				return;
			}
			$field_list = self::get_form_fields( $form_id );
			wp_send_json_success( $field_list );
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Lookup filter ajax callback.
	 */
	public function evf_lookup_field_filters() {
		global $wpdb;
		try {
			check_ajax_referer( 'everest_forms_lookup_nonce', 'security' );
			$filter_arr   = isset( $_POST['filterArr'] ) ? $_POST['filterArr'] : array();
			$form_id      = isset( $_POST['lookupformid'] ) ? sanitize_text_field( wp_unslash( $_POST['lookupformid'] ) ) : array();
			$lookup_field = isset( $_POST['lookupformfield'] ) ? sanitize_text_field( wp_unslash( $_POST['lookupformfield'] ) ) : array();
			if ( empty( $filter_arr ) || empty( $form_id ) || empty( $lookup_field ) ) {
				wp_send_json_error( esc_html__( 'Invalid data', 'everest-forms-pro' ) );
			}
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT fields FROM {$wpdb->prefix}evf_entries WHERE form_id = %d AND status = %s", $form_id, 'publish' ) ); // WPCS: cache ok, DB call ok.

			$required_data_list = array();
			foreach ( $filter_arr as $key => $filter ) {
				$select                     = explode( '_', $key )[1];
				$required_data_list[ $key ] = array();
				foreach ( $results as $result ) {
					$fields   = (array) json_decode( $result->fields );
					$count    = 0;
					$doFilter = false;
					foreach ( $filter as $filter_key => $filter_value ) {

						if ( $count > 0 ) {
							if ( ! $doFilter ) {
								break;
							}
						}
						if ( array_key_exists( $lookup_field, $fields ) ) {
							$field_val = $fields[ $lookup_field ]->value;
							if ( isset( $field_val->type ) ) {
								if ( 'checkbox' === $field_val->type ) {
									if ( in_array( $filter_value['elValue'], $field_val->label, true ) ) {
										$doFilter = true;
										++$count;
									}
								} elseif ( 'radio' === $field_val->type ) {
									if ( $filter_value['elValue'] === $field_val->label ) {
										$doFilter = true;
										++$count;
									}
								}
							} elseif ( is_array( $field_val ) ) {
								if ( in_array( $filter_value['elValue'], $field_val, true ) ) {
									$doFilter = true;
									++$count;
								}
							} elseif ( $filter_value['elValue'] === $field_val ) {

								$doFilter = true;
								++$count;
							}
						}
					}
					if ( $doFilter ) {
						foreach ( $filter as $filter_key => $filter_value ) {
							$field_value     = $fields[ $filter_value['lookupField'] ]->value;
							$new_select_list = array();
							if ( isset( $field_value->type ) ) {
								if ( 'checkbox' === $field_value->type ) {
									foreach ( $field_value->label as $label ) {
										$new_select_list[] = $label;
									}
								} elseif ( 'radio' === $field_value->type ) {
									$new_select_list[] = $field_value->label;
								}
							} elseif ( is_array( $field_value ) ) {
								foreach ( $field_value as $value ) {
									$new_select_list[] = $value;
								}
							} else {
								$new_select_list[] = $field_value;
							}
							$new_select_list_json = wp_json_encode( $new_select_list );
							if ( ! array_key_exists( $key, $required_data_list ) ) {
								$required_data_list[ $key ] = $new_select_list_json;
							}
							array_push( $required_data_list[ $key ], $new_select_list_json );

						}
					}
				}
			}
			wp_send_json_success( $required_data_list );

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}
}
