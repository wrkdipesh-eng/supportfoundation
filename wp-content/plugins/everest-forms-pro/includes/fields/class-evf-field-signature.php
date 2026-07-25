<?php
/**
 * Signature Field
 *
 * @package EverestForms_Pro\Fields
 * @since   1.2.1
 */

defined( 'ABSPATH' ) || exit;




/**
 * EVF_Field_Signature Class.
 */
class EVF_Field_Signature extends EVF_Form_Fields {

	/**
	 * Default value options.
	 *
	 * @var string
	 */
	public $defaults_option_value;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Signature', 'everest-forms-pro' );
		$this->type     = 'signature';
		$this->icon     = 'evf-icon evf-icon-signature';
		$this->order    = 100;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
					'option_display',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'label_hide',
					'css',
				),
			),
		);

		$this->defaults_option_value = array(
			'background_color' => 'rgb(255, 255, 255)',
			'pen_color'        => 'rgba(0,0,0,1)',
			'image_format'     => 'png',
		);

		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_action( 'everest_forms_shortcode_scripts', array( $this, 'load_assets' ) );

		add_filter( 'everest_forms_process_after_filter', array( $this, 'signature_upload' ), 10, 3 );
		add_filter( 'everest_forms_process_filter_entry_update', array( $this, 'signature_upload' ), 10, 3 );
		add_filter( 'everest_forms_html_field_value', array( $this, 'render_image_file' ), 10, 4 );
		add_filter( 'everest_forms_plaintext_field_value', array( $this, 'plaintext_field_value' ), 10, 4 );
		add_filter( 'everest_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
	}

	/**
	 * Queue frontend scripts.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public function load_assets( $atts ) {
		$form_data = evf()->form->get( $atts['id'], array( 'content_only' => true ) );

		if ( ! empty( $form_data['form_fields'] ) ) {
			$data               = $this->defaults_option_value;
			$is_signature_field = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type' => 'signature',
				)
			);

			if ( ! empty( $is_signature_field ) ) {
				wp_enqueue_script( 'everest-forms-signature' );
				?>
				<script id="<?php echo esc_attr( 'everest-forms-signature' ); ?>">
					const evf_signature_params = <?php echo wp_json_encode( $data ); ?>
				</script>
				<?php
			}
		}
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Label.
		$this->field_preview_option( 'label', $field );

		echo "<canvas style='width:100%;height:100px;max-width:100%;max-height:100%;'></canvas>";

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Option display in sidebar.
	 *
	 * @param array $field Field Data.
	 */
	public function option_display( $field ) {
		$file_format   = $this->defaults_option_value['image_format'];
		$field_options = sprintf( '<input type="hidden"  name="form_fields[%s][signature_file_format]" value="%s" />', $field['id'], $file_format );

		// Field option row (markup) including label and input.
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'signature',
				'content' => $field_options,
			)
		);
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$primary           = $field['properties']['inputs']['primary'];
		$conditional_id    = isset( $field['properties']['inputs']['primary']['attr']['conditional_id'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_id'] : '';
		$conditional_rules = isset( $field['properties']['inputs']['primary']['attr']['conditional_rules'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_rules'] : '';
		printf( '<div id="everest_form_signature_canvas_%s" class="everest_form_signature_canvas-wrap" data-image-format="%s" data-form-id="%s" data-field-id="%s" >', esc_html( $field['id'] ), esc_html( isset( $field['signature_file_format'] ) ? $field['signature_file_format'] : 'png' ), esc_html( $form_data['id'] ), esc_html( $field['id'] ) );
		printf( "<canvas id='evf-signature-canvas-%s' class='evf-signature-canvas' style='width:%s;height:200px;max-width:%s;max-height:%s;' ></canvas>", esc_html( $field['id'] ), '100%', '100%', '100%' );

		$value = isset( $primary['attr']['value'] ) ? $primary['attr']['value'] : '';

		$img_url = '';

		if ( ! empty( $value ) ) {
			$uploads = wp_upload_dir();
			$img_url = trailingslashit( content_url() ) . str_replace( str_replace( 'uploads', '', $uploads['basedir'] ), '', $value );
		}

		printf(
			'<input type="hidden" id="evf-signature-img-input-%s" class="evf-signature-input input-text" name="everest_forms[form_fields][%s][signature_image]" conditional_rules="%s" conditional_id="%s" data-signature-url="%s" value="%s" %s / > ',
			esc_html( $field['id'] ),
			esc_html( $field['id'] ),
			esc_attr( $conditional_rules ),
			esc_attr( $conditional_id ),
			esc_url( $img_url ),
			$value,
			esc_attr( $primary['required'] )
		);

		printf(
			'<input type="hidden" class="input-text" name="everest_forms_%s_old_signature_image_%s" value="%s" / > ',
			absint( isset( $_GET['form_id'] ) ? $_GET['form_id'] : 0 ),
			esc_html( $field['id'] ),
			$value,
		);

		printf( ' <a href="JavaScript:void(0);" title="%s" style="text-decoration: none;" id="everest-form-signature-reset-%s" class="evf-signature-reset"><span class="dashicons dashicons-no-alt"></span> </a> ', esc_attr__( 'Clear Signature', 'everest-forms-pro' ), esc_html( $field['id'] ) );
		printf( '</div>' );
	}

	/**
	 * Validates signature field.
	 *
	 * @param int   $field_id Field Id.
	 * @param array $field_submit Submitted Field.
	 * @param array $form_data Form Data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		$field_type       = isset( $form_data['form_fields'][ $field_id ]['type'] ) ? $form_data['form_fields'][ $field_id ]['type'] : '';
		$field_required   = isset( $form_data['form_fields'][ $field_id ]['required'] ) ? $form_data['form_fields'][ $field_id ]['required'] : '';
		$entry            = isset( $form_data['entry'] ) ? $form_data['entry'] : array();
		$visible          = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );
		$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' == $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ? $form_data['form_fields'][ $field_id ]['required-field-message'] : get_option( 'everest_forms_required_validation' );

		$file        = isset( $field_submit['signature_image'] ) ? $field_submit['signature_image'] : '';
		$file_format = isset( $form_data['form_fields'][ $field_id ]['signature_file_format'] ) ? $form_data['form_fields'][ $field_id ]['signature_file_format'] : 'png';
		$check_str   = "data:image/{$file_format};base64";

		if ( preg_match( '/^(phar|file|php|zlib|zip):\/\//i', $file ) ) {
			$validation_text = esc_html__( 'Invalid signature image source.', 'everest-forms-pro' );
			update_option( 'evf_validation_error', 'yes' );
		}elseif ( false === strpos( $file, $check_str ) ) {
			$validation_text = esc_html__( 'Invalid signature image format.', 'everest-forms-pro' );
			update_option( 'evf_validation_error', 'yes' );
		}

		if ( empty( $visible ) || empty( $field_required ) ) {
			return;
		}

		if ( empty( $file ) ) {
			$validation_text = $required_message;
		}

		if ( isset( $validation_text ) ) {
			EVF()->task->errors[ $form_data['id'] ][ $field_id ] = apply_filters( 'everest_forms_type_validation', $validation_text );
			update_option( 'evf_validation_error', 'yes' );
		}
	}

	/**
	 * Function to convert blob into image file and save it to entry.
	 *
	 * @param array $form_fields Form fields Data.
	 * @param array $entry       Form Entry Data.
	 * @param array $form_data   Form Data Object.
	 *
	 * @return $form_fields Form Fields Data.
	 */
	public function signature_upload( $form_fields, $entry, $form_data ) {
		$img_num = 1;
		foreach ( $form_fields as $key => $field ) {

			if ( isset( $field['type'] ) && 'signature' === $field['type'] ) {

				++$img_num;

				// Define data.
				$uploads                    = wp_upload_dir();
				$form_id                    = absint( $form_data['id'] );
				$evf_uploads_root           = trailingslashit( $uploads['basedir'] ) . 'everest_forms_uploads';
				$signature_upload_directory = trailingslashit( $evf_uploads_root . '/' . $form_id ) . 'signature';

				// Check for form upload directory destination.
				if ( ! file_exists( $signature_upload_directory ) ) {
					wp_mkdir_p( $signature_upload_directory );
				}

				// Check if the index.html exists in the root uploads director, if not create it.
				if ( ! file_exists( trailingslashit( $signature_upload_directory ) . 'index.html' ) ) {
					file_put_contents( trailingslashit( $signature_upload_directory ) . 'index.html', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
				}

				// Create Image from blob and save it.
				$data_uri          = $field['value'];
				$file_format       = isset( $form_data['form_fields'][ $field['id'] ]['signature_file_format'] ) ? $form_data['form_fields'][ $field['id'] ]['signature_file_format'] : 'png';
				$check_file_format = "data:image/{$file_format};base64,";
				if ( false !== strpos( $field['value'], $check_file_format ) ) {
					$encoded_image = str_replace( $check_file_format, '', $data_uri );
					$decoded_image = base64_decode( $encoded_image ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
					$file          = trailingslashit( $signature_upload_directory ) . 'signature_' . time() . '-' . $img_num . wp_rand( 0, 10 ) . wp_rand( 0, 10 ) . ".{$file_format}";
					file_put_contents( $file, $decoded_image ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
					$form_fields[ $key ]['value'] = $file;
				}
			}
		}
		return $form_fields;
	}

	/**
	 * Display Image file on entries
	 *
	 * @param array  $val Entry Value.
	 * @param array  $field_val Field Value.
	 * @param array  $form_data Form Data.
	 * @param string $context   Field Context.
	 *
	 * @return $val Return Signature Image Tag.
	 */
	public function render_image_file( $val, $field_val, $form_data = array(), $context = '' ) {
		$uploads = wp_upload_dir();
		if ( 'evffl-display-popup' === $context ) {
			if ( isset( $form_data['form_fields'] ) && is_array( $form_data['form_fields'] ) ) {
				foreach ( $form_data['form_fields'] as $fields_data ) {
					$field_meta_key  = isset( $field_val['meta-key'] ) ? sanitize_text_field( wp_unslash( (string) $field_val['meta-key'] ) ) : '';
					$fields_meta_key = isset( $fields_data['meta-key'] ) ? sanitize_text_field( wp_unslash( (string) $fields_data['meta-key'] ) ) : '';
					$fields_type     = isset( $fields_data['type'] ) ? sanitize_text_field( wp_unslash( (string) $fields_data['type'] ) ) : '';

					if ( $field_meta_key === $fields_meta_key ) {
						if ( 'signature' === $fields_type ) {
							$raw_value = isset( $field_val['value'] ) ? wp_unslash( (string) $field_val['value'] ) : '';
							$img_url   = trailingslashit( content_url() ) . str_replace( str_replace( 'uploads', '', $uploads['basedir'] ), '', $raw_value );

							$val = sprintf(
								'<a href="%1$s" target="_blank" rel="noopener noreferrer"><img src="%1$s" style="width:200px;" alt="" /></a>',
								esc_url( $img_url )
							);

							return $val;
						}
					}
				}
			}
		}

		if ( ! is_array( $field_val ) && false !== strpos( (string) $field_val, $uploads['basedir'] ) && 'image/png' === $this->mime_content_type( $field_val ) ) {
			$field_val = wp_unslash( (string) $field_val );
			$img_url   = trailingslashit( content_url() ) . str_replace( str_replace( 'uploads', '', $uploads['basedir'] ), '', $field_val );
			$file      = $uploads['basedir'] . str_replace( '/uploads/', '/', str_replace( content_url(), '', $img_url ) );

			if ( in_array( $context, array( 'email-html', 'export-csv', 'export-pdf' ), true ) ) {
				if ( 'export-pdf' === $context || 'email-html' === $context ) {
					if ( 'email-html' === $context ) {
						$val = sprintf(
							'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
							esc_url( $img_url ),
							esc_html__( 'Signature', 'everest-forms-pro' )
						);
					} else {
						$val = sprintf(
							'<img src="%s" style="width:200px;height=100px;" alt="" />',
							esc_attr( $file )
						);
					}

					return $val;
				} elseif ( 'export-csv' === $context ) {
					$val = esc_url_raw( $img_url );
				} else {
					$val = sprintf(
						'<a href="%1$s" rel="noopener noreferrer" target="_blank">%2$s</a>',
						esc_url( $img_url ),
						esc_html__( 'Signature', 'everest-forms-pro' )
					);
				}
			} else {
				$val = sprintf(
					'<img src="%1$s" style="width:300px;height=150px;max-height:%2$s;max-width:%3$s;" alt="" />',
					esc_url( $img_url ),
					esc_attr( '100%' ),
					esc_attr( '100%' )
				);
			}
			return $val;
		} else {
			return $val;
		}
	}

	/**
	 * Customize format for Plain field value.
	 *
	 * @param  string $val       Value of the field in plain format.
	 * @param  array  $field_val Field value object.
	 * @param  array  $form_data Form data object.
	 * @param  string $context   Context string.
	 * @return string $val       Value returned.
	 */
	public function plaintext_field_value( $val, $field_val, $form_data = array(), $context = '' ) {
		$uploads = wp_upload_dir();
		if ( ! is_array( $field_val ) && strpos( $field_val, $uploads['basedir'] ) !== false && 'image/png' === $this->mime_content_type( $field_val ) ) {
			if ( 'email-plain' === $context ) {
				$img_url = trailingslashit( content_url() ) . str_replace( str_replace( 'uploads', '', $uploads['basedir'] ), '', $field_val );
				return esc_url( $img_url ) . "\r\n\r\n";
			}
		}
		return $val;
	}

	/**
	 * Filter callback for outputting formatted data.
	 *
	 * @param array $field Field Data.
	 */
	public function field_exporter( $field ) {
		$value = '';

		$field_val = $field['value'];
		$uploads   = wp_upload_dir();

		if ( ! is_array( $field_val ) && strpos( $field_val, $uploads['basedir'] ) !== false && 'image/png' === $this->mime_content_type( $field_val ) ) {
			$field['value'] = trailingslashit( content_url() ) . str_replace( str_replace( 'uploads', '', $uploads['basedir'] ), '', $field_val );
		}

		$image_styles = array(
			'width'      => '150px',
			'height'     => '80px',
			'max-width'  => '100px',
			'max-height' => '200px',
		);

		/**
		 * Filter to allow modification of image dimensions.
		 *
		 * @param array $image_styles Associative array of CSS styles (width, height, etc.).
		 * @param array $field        The full field array.
		 */
		$image_styles = apply_filters( 'everest_forms_export_image_styles', $image_styles, $field );

		$style_attr = '';
		foreach ( $image_styles as $key => $val ) {
			$style_attr .= sprintf( '%s:%s;', esc_attr( $key ), esc_attr( $val ) );
		}

		if ( ! empty( $field['value'] ) ) {
			$value = sprintf(
				'<img src="%s" style="%s" />',
				esc_url( $field['value'] ),
				esc_attr( $style_attr )
			);
		}

		return array(
			'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
			'value' => ! empty( $value ) ? $value : false,
		);
	}

	/**
	 * Get mime content type of files. It handles the absence of default PHP
	 * mime_content_type() function.
	 *
	 * @since 1.3.5
	 *
	 * @param string $filename File name.
	 *
	 * @return string Mime type.
	 */
	private function mime_content_type( $filename ) {
		$mime_type = false;

		if ( preg_match( '/^phar:\/\//i', $filename ) ) {
			return false;
		}

		if ( ! file_exists( $filename ) ) {
			return false;
		}

		$uploads = wp_get_upload_dir();
		$uploads_dir = realpath( $uploads['basedir'] );
		$real_file   = realpath( $filename );

		if ( ! $uploads_dir || ! $real_file || strpos( $real_file, $uploads_dir ) !== 0 ) {
			return false;
		}

		$filetype = wp_check_filetype_and_ext( $filename, basename( $filename ) );

		if ( ! empty( $filetype['type'] ) ) {
			$mime_type = $filetype['type'];
		}

		return $mime_type;
	}
}
