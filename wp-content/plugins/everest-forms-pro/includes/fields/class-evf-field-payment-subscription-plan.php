<?php
/**
 * Payment subscription plan field
 *
 * @since   1.9.3
 *
 * @package EverestForms\Fields
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Subscription_Plan Class.
 *
 * @since 1.9.3
 */
class EVF_Field_Payment_Subscription_Plan extends EVF_Form_Fields {

	/**
	 * Constructor.
	 *
	 * @since 1.9.3
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Subscription Plan', 'everest-forms' );
		$this->type     = 'payment-subscription-plan';
		$this->icon     = 'evf-icon evf-icon-subscription-plan';
		$this->order    = 12;
		$this->group    = 'payment';
		$this->defaults = array(
			1 => array(
				'label'   => esc_html__( 'First Choice', 'everest-forms-pro' ),
				'value'   => '10.00',
				'image'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => esc_html__( 'Second Choice', 'everest-forms-pro' ),
				'value'   => '20.00',
				'image'   => '',
				'default' => '',
			),
			3 => array(
				'label'   => esc_html__( 'Third Choice', 'everest-forms-pro' ),
				'value'   => '30.00',
				'image'   => '',
				'default' => '',
			),
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'plan_choices',
					'choices_images',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'input_columns',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 *
	 * @since 1.9.3
	 */
	public function init_hooks() {
		add_filter( 'everest_forms_html_field_value', array( $this, 'html_field_value' ), 10, 4 );
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
		add_action( 'everest_forms_shortcode_scripts', array( $this, 'load_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_styles' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_frontend_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_builder_styles' ) );
	}

	/**
	 * Register shared subscription plan field styles.
	 *
	 * @return void
	 */
	protected function register_frontend_styles() {
		$css_path = plugin_dir_path( EFP_PLUGIN_FILE ) . 'assets/css/evf-payment-subscription-plan-frontend.css';
		$version  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : ( defined( 'EFP_VERSION' ) ? EFP_VERSION : '1.0.0' );

		wp_register_style(
			'evf-payment-subscription-plan-frontend',
			plugins_url( 'assets/css/evf-payment-subscription-plan-frontend.css', EFP_PLUGIN_FILE ),
			array(),
			$version
		);
	}

	/**
	 * Enqueue card layout styles when the form includes a subscription plan field.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return void
	 */
	public function load_assets( $atts ) {
		if ( empty( $atts['id'] ) ) {
			return;
		}

		$form_data = evf()->form->get( absint( $atts['id'] ), array( 'content_only' => true ) );
		if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
			return;
		}

		foreach ( $form_data['form_fields'] as $field ) {
			if ( ! empty( $field['type'] ) && $this->type === $field['type'] ) {
				$this->register_frontend_styles();
				wp_enqueue_style( 'evf-payment-subscription-plan-frontend' );
				return;
			}
		}
	}

	/**
	 * Load styles in the block editor so embedded forms match the builder preview.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_styles() {
		$this->register_frontend_styles();
		wp_enqueue_style(
			'evf-payment-subscription-plan-frontend',
			plugins_url( 'assets/css/evf-payment-subscription-plan-frontend.css', EFP_PLUGIN_FILE ),
			array( 'everest-forms-block-editor' ),
			file_exists( plugin_dir_path( EFP_PLUGIN_FILE ) . 'assets/css/evf-payment-subscription-plan-frontend.css' )
				? (string) filemtime( plugin_dir_path( EFP_PLUGIN_FILE ) . 'assets/css/evf-payment-subscription-plan-frontend.css' )
				: ( defined( 'EFP_VERSION' ) ? EFP_VERSION : '1.0.0' )
		);
	}

	/**
	 * Load styles on the frontend when the Everest Forms block is present.
	 *
	 * @return void
	 */
	public function enqueue_block_frontend_styles() {
		if ( is_admin() || ! function_exists( 'has_block' ) || ! has_block( 'everest-forms/form-selector' ) ) {
			return;
		}

		$this->register_frontend_styles();
		wp_enqueue_style( 'evf-payment-subscription-plan-frontend' );
	}

	/**
	 * Ensure builder canvas preview uses the same card layout as the frontend.
	 *
	 * @param string $hook_suffix Admin page hook.
	 * @return void
	 */
	public function enqueue_builder_styles( $hook_suffix ) {
		if ( 'everest-forms_page_evf-builder' !== $hook_suffix ) {
			return;
		}

		$this->register_frontend_styles();
		wp_enqueue_style( 'evf-payment-subscription-plan-frontend' );
	}

	/**
	 * Return images, if any, for HTML supported values.
	 *
	 * @since 1.3.0
	 *
	 * @param string $value     Field value.
	 * @param array  $field     Field settings.
	 * @param array  $form_data Form data and settings.
	 * @param string $context   Value display context.
	 *
	 * @return string
	 */
	public function html_field_value( $value, $field, $form_data = array(), $context = '' ) {
		if ( is_serialized( $field ) || in_array( $context, array( 'email-html', 'export-pdf' ), true ) ) {
			$field_value = maybe_unserialize( $field );
			$field_type  = isset( $field_value['type'] ) ? sanitize_text_field( wp_unslash( (string) $field_value['type'] ) ) : 'payment-subscription-plan';

			if ( $field_type === $this->type ) {
				if (
					'entry-table' !== $context
					&& ! empty( $field_value['label'] )
					&& ! empty( $field_value['image'] )
					&& apply_filters( 'everest_forms_payment_multiple_field_html_value_images', true, $context )
				) {
					return sprintf(
						'<span style="max-width:200px;display:block;margin:0 0 5px 0;"><img src="%1$s" style="max-width:100%%;display:block;margin:0;" alt="" /></span>%2$s',
						esc_url( $field_value['image'] ),
						esc_html( wp_unslash( (string) $field_value['label'] ) )
					);
				} elseif ( isset( $field_value['label'] ) ) {
					return esc_html( wp_unslash( (string) $field_value['label'] ) );
				}
			}
		}

		return $value;
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		// Define data.
		$form_id  = absint( $form_data['id'] );
		$field_id = $field['id'];
		$choices  = $field['choices'];

		// Remove primary input.
		unset( $properties['inputs']['primary'] );

		// Set input container (ul) properties.
		$properties['input_container'] = array(
			'class' => array( 'primary-input' ),
			'data'  => array(),
			'attr'  => array(),
			'id'    => "evf-{$form_id}-field_{$field_id}",
		);

		// Set input properties.
		foreach ( $choices as $key => $choice ) {
			// BW compatibility for choice value.
			if ( ! empty( $field['amount'][ $key ]['value'] ) ) {
				$choice['value'] = $field['amount'][ $key ]['value'];
			}

			$properties['inputs'][ $key ] = array(
				'container' => array(
					'attr'  => array(),
					'class' => array( "choice-{$key}" ),
					'data'  => array(),
					'id'    => '',
				),
				'label'     => array(
					'attr'  => array(
						'for' => "evf-{$form_id}-field_{$field_id}_{$key}",
					),
					'class' => array( 'everest-forms-field-label-inline' ),
					'data'  => array(),
					'id'    => '',
					'text'  => $this->build_choice_label_html( $form_id, $field_id, $choice, $key ),
				),
				'attr'      => array(
					'name'  => "everest_forms[form_fields][{$field_id}]",
					'value' => $key,
				),
				'class'     => array( 'input-text', 'evf-payment-price' ),
				'data'      => array(
					'amount' => evf_sanitize_amount( $choice['value'] ),
				),
				'id'        => "evf-{$form_id}-field_{$field_id}_{$key}",
				'image'     => isset( $choice['image'] ) ? $choice['image'] : '',
				'required'  => ! empty( $field['required'] ) ? 'required' : '',
				'default'   => isset( $choice['default'] ),
			);
		}

		// Required class for validation.
		if ( ! empty( $field['required'] ) ) {
			$properties['input_container']['class'][] = 'evf-field-required';
		}

		// Custom properties if enabled image choices.
		if ( ! empty( $field['choices_images'] ) ) {
			$properties['input_container']['class'][] = 'everest-forms-image-choices';

			foreach ( $properties['inputs'] as $key => $inputs ) {
				$properties['inputs'][ $key ]['container']['class'][] = 'everest-forms-image-choices-item';
			}
		}

		// Add selected class for choices with defaults.
		foreach ( $properties['inputs'] as $key => $inputs ) {
			if ( ! empty( $inputs['default'] ) ) {
				$properties['inputs'][ $key ]['container']['class'][] = 'everest-forms-selected';
			}
		}

		return $properties;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$this->field_preview_option( 'label', $field );

		$values         = ! empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
		$choices_images = ! empty( $field['choices_images'] );
		$list_class     = 'widefat primary-input' . ( $choices_images ? ' everest-forms-image-choices' : '' );

		printf( '<ul class="%s">', esc_attr( $list_class ) );
		foreach ( $values as $value ) {
			$default   = isset( $value['default'] ) ? $value['default'] : '';
			$raw_label = isset( $value['label'] ) ? $value['label'] : '';
			$amount    = evf_format_amount( evf_sanitize_amount( isset( $value['value'] ) ? $value['value'] : '0' ), true );

			$item_class = ! empty( $value['default'] ) ? 'everest-forms-selected' : '';
			printf( '<li class="%s">', esc_attr( $item_class ) );
			printf( '<input type="radio" %s disabled>', checked( '1', $default, false ) );

			if ( $choices_images ) {
				$placeholder = evf()->plugin_url( 'assets/images/everest-forms-placeholder.png' );
				$image_src   = ! empty( $value['image'] ) ? esc_url( $value['image'] ) : $placeholder;
				echo '<label>';
				printf( '<span class="everest-forms-image-choices-image"><img src="%s" alt="%s"></span>', esc_url( $image_src ), esc_attr( $raw_label ) );
				echo wp_kses_post( $this->build_plan_label_html( $raw_label, $amount, $value ) );
				echo '</label>';
			} else {
				echo '<label class="everest-forms-field-label-inline">';
				echo wp_kses_post( $this->build_plan_label_html( $raw_label, $amount, $value ) );
				echo '</label>';
			}

			echo '</li>';
		}
		echo '</ul>';

		$this->field_preview_option( 'description', $field );
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
		// Define data.
		$container = $field['properties']['input_container'];
		$choices   = $field['properties']['inputs'];

		// List.
		printf( '<ul %s>', evf_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] ) );

		foreach ( $choices as $key => $choice ) {
			if ( empty( $choice['container'] ) ) {
				continue;
			}

			// Conditional logic.
			$choice['attr']['conditional_id'] = $choices['primary']['attr']['conditional_id'];
			if ( isset( $choices['primary']['attr']['conditional_rules'] ) ) {
				$choice['attr']['conditional_rules'] = $choices['primary']['attr']['conditional_rules'];
			}

			printf( '<li %s>', evf_html_attributes( $choice['container']['id'], $choice['container']['class'], $choice['container']['data'], $choice['container']['attr'] ) );

			if ( ! empty( $field['choices_images'] ) ) {
				// Make image choices keyboard-accessible.
				$choice['label']['attr']['tabindex'] = 0;

				// Image choices.
				printf( '<label %s>', evf_html_attributes( $choice['label']['id'], $choice['label']['class'], $choice['label']['data'], $choice['label']['attr'] ) );

				if ( ! empty( $choice['image'] ) ) {
					printf(
						'<span class="everest-forms-image-choices-image"><img src="%s" alt="%s"%s></span>',
						esc_url( $choice['image'] ),
						esc_attr( $choice['label']['text'] ),
						! empty( $choice['label']['text'] ) ? ' title="' . esc_attr( $choice['label']['text'] ) . '"' : ''
					);
				}

				echo '<br>';

				$choice['attr']['tabindex'] = '-1';

				printf( '<input type="radio" %s %s %s>', evf_html_attributes( $choice['id'], $choice['class'], $choice['data'], $choice['attr'] ), esc_attr( $choice['required'] ), checked( '1', $choice['default'], false ) );
				echo '<label class="everest-forms-image-choices-label">' . wp_kses_post( $choice['label']['text'] ) . '</label>';
				echo '</label>';
			} else {
				// Normal display.
				printf( '<input type="radio" %s %s %s>', evf_html_attributes( $choice['id'], $choice['class'], $choice['data'], $choice['attr'] ), esc_attr( $choice['required'] ), checked( '1', $choice['default'], false ) );
				printf( '<label %s>%s</label>', evf_html_attributes( $choice['label']['id'], $choice['label']['class'], $choice['label']['data'], $choice['label']['attr'] ), wp_kses_post( $choice['label']['text'] ) );
			}

			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Build HTML label text for a single subscription plan choice (frontend).
	 *
	 * @param int    $form_id  Form ID.
	 * @param string $field_id Field ID.
	 * @param array  $choice   Choice data array.
	 * @param int    $key      Choice key.
	 * @return string HTML string (safe for wp_kses_post).
	 */
	private function build_choice_label_html( $form_id, $field_id, $choice, $key ) {
		$label  = evf_string_translation( $form_id, $field_id, $choice['label'], '-choice-' . $key );
		$amount = evf_format_amount( evf_sanitize_amount( $choice['value'] ), true );
		return $this->build_plan_label_html( $label, $amount, $choice );
	}

	/**
	 * Core HTML builder for a subscription plan choice label.
	 *
	 * @param string $label  Resolved label text (already translated if needed).
	 * @param string $amount Formatted amount string with currency symbol.
	 * @param array  $choice Full choice data array.
	 * @return string HTML string (safe for wp_kses_post).
	 */
	private function build_plan_label_html( $label, $amount, $choice ) {
		$period = isset( $choice['recurring_period'] ) ? sanitize_key( $choice['recurring_period'] ) : 'month';
		$count  = max( 1, (int) ( isset( $choice['interval_count'] ) ? $choice['interval_count'] : 1 ) );

		$period_map = array(
			/* translators: recurring billing period */
			'day'   => _n( 'day', 'days', $count, 'everest-forms-pro' ),
			'week'  => _n( 'week', 'weeks', $count, 'everest-forms-pro' ),
			'month' => _n( 'month', 'months', $count, 'everest-forms-pro' ),
			'year'  => _n( 'year', 'years', $count, 'everest-forms-pro' ),
		);
		$period_label  = isset( $period_map[ $period ] ) ? $period_map[ $period ] : $period;
		$period_suffix = $count > 1 ? "/{$count} {$period_label}" : "/{$period_label}";

		$main = sprintf(
			'<span class="evf-plan-main"><span class="evf-plan-name">%s</span><strong class="evf-plan-price">%s%s</strong></span>',
			esc_html( $label ),
			esc_html( $amount ),
			esc_html( $period_suffix )
		);

		$meta_parts = array();

		if ( ! empty( $choice['trail_period_enable'] ) ) {
			$t_count  = max( 1, (int) ( isset( $choice['trail_interval_count'] ) ? $choice['trail_interval_count'] : 1 ) );
			$t_period = isset( $choice['trail_recurring_period'] ) ? sanitize_key( $choice['trail_recurring_period'] ) : 'week';
			$t_map    = array(
				'day'   => _n( 'day', 'days', $t_count, 'everest-forms-pro' ),
				'week'  => _n( 'week', 'weeks', $t_count, 'everest-forms-pro' ),
				'month' => _n( 'month', 'months', $t_count, 'everest-forms-pro' ),
				'year'  => _n( 'year', 'years', $t_count, 'everest-forms-pro' ),
			);
			$t_label      = isset( $t_map[ $t_period ] ) ? $t_map[ $t_period ] : $t_period;
			/* translators: 1: trial count, 2: period label */
			$meta_parts[] = esc_html( sprintf( __( '%1$d %2$s free trial', 'everest-forms-pro' ), $t_count, $t_label ) );
		}

		$meta = ! empty( $meta_parts )
			? sprintf( '<span class="evf-plan-meta">%s</span>', implode( ' &middot; ', $meta_parts ) )
			: '';

		return '<span class="evf-plan-wrap">' . $main . $meta . '</span>';
	}

	/**
	 * Validates field on form submit.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $field_id Field Id.
	 * @param array $field_submit Submitted Field.
	 * @param array $form_data Form Data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		$entry            = isset( $form_data['entry'] ) ? $form_data['entry'] : array();
		$visible          = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );
		$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' == $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ? $form_data['form_fields'][ $field_id ]['required-field-message'] : get_option( 'everest_forms_required_validation' );

		if ( false === $visible ) {
			return;
		}
		// Basic required check - If field is marked as required, check for entry data.
		if ( ! empty( $form_data['fields'][ $field_id ]['required'] ) && empty( $field_submit ) ) {
			evf()->task->errors[ $form_data['id'] ][ $field_id ] = $required_message;
			update_option( 'evf_validation_error', 'yes' );
		}

		// Validate that the option selected is real.
		if ( ! empty( $field_submit ) && empty( $form_data['form_fields'][ $field_id ]['choices'][ $field_submit ] ) ) {
			evf()->task->errors[ $form_data['id'] ][ $field_id ] = esc_html__( 'Invalid payment option', 'everest-forms-pro' );
			update_option( 'evf_validation_error', 'yes' );
		}
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_id Field Id.
	 * @param array  $field_submit Submitted Field.
	 * @param array  $form_data All Form Data.
	 * @param string $meta_key Field Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$field        = $form_data['form_fields'][ $field_id ];
		$name         = make_clickable( $field['label'] );
		$value_raw    = sanitize_text_field( $field_submit );
		$amount       = 0;
		$value        = '';
		$choice_label = '';
		$image        = '';

		// BW compatibility for choice value for amount.
		if ( ! empty( $field['amount'][ $field_submit ]['value'] ) ) {
			$field['choices'][ $field_submit ]['value'] = $field['amount'][ $field_submit ]['value'];
		}

		if ( ! empty( $field_submit ) && ! empty( $field['choices'][ $field_submit ]['value'] ) ) {
			$amount = evf_sanitize_amount( $field['choices'][ $field_submit ]['value'] );
			$value  = evf_format_amount( $amount, true );

			if ( ! empty( $field['choices'][ $field_submit ]['label'] ) ) {
				$choice_label = sanitize_text_field( $field['choices'][ $field_submit ]['label'] );
				$value        = $choice_label . ' - ' . $value;
			}

			if ( ! empty( $field['choices_images'] ) ) {
				$image = ! empty( $field['choices'][ $field_submit ]['image'] ) ? esc_url_raw( $field['choices'][ $field_submit ]['image'] ) : '';
			}
		}

		evf()->task->form_fields[ $field_id ] = array(
			'id'           => $field_id,
			'type'         => $this->type,
			'value'        => array(
				'name'     => $name,
				'type'     => $this->type,
				'label'    => $value,
				'amount'   => evf_format_amount( $amount ),
				'currency' => get_option( 'everest_forms_currency', 'USD' ),
				'image'    => $image,
			),
			'meta_key'     => $meta_key,
			'amount_raw'   => $amount,
			'value_raw'    => $value_raw,
			'value_choice' => $choice_label,
		);
	}
}
