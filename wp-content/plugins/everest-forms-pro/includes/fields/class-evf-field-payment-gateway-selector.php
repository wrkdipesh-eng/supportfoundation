<?php
/**
 * Payment gateway selector field.
 *
 * @package EverestForms_Pro\Fields
 * @since   1.9.15
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Gateway_Selector Class.
 */
class EVF_Field_Payment_Gateway_Selector extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Payment Gateway', 'everest-forms-pro' );
		$this->type     = 'payment-gateway-selector';
		$this->icon     = 'evf-icon evf-icon-payment';
		$this->order    = 45;
		$this->group    = 'payment';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'payment_gateway_choice',
					'description',
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
		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 *
	 * Builder note: the Payment Gateway field is mutually exclusive with the legacy
	 * Credit Card, Authorize.Net, and Square fields — see
	 * EVFPanelBuilder.syncPaymentMethodDependentFields() in form-builder.js.
	 */
	public function init_hooks() {
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
		add_filter( 'everest_forms_field_new_required', array( $this, 'field_default_required' ), 5, 3 );
		add_filter( "everest_forms_should_display_field_{$this->type}", array( $this, 'should_display_field' ), 10, 3 );
		add_action( 'everest_forms_shortcode_scripts', array( $this, 'load_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_styles' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_frontend_styles' ) );
		add_filter( 'everest_forms_one_time_draggable_form_fields', array( $this, 'register_one_time_draggable_type' ) );
		add_filter( 'everest_forms_field_preview_class', array( $this, 'builder_preview_extra_class' ), 10, 2 );
		add_filter( 'everest_forms_builder_save_form_data', array( $this, 'normalize_allowlist_on_save' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_builder_scripts' ) );
	}

	/**
	 * Enqueue the builder-panel JS for the gateway selector (sortable + accordion).
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_builder_scripts( $hook ) {
		if ( 'everest-forms_page_evf-builder' !== $hook ) {
			return;
		}
		$js_path = plugin_dir_path( EFP_PLUGIN_FILE ) . 'assets/js/admin/payment-gateway-selector-builder.js';
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script(
			'evf-payment-gateway-selector-builder',
			plugins_url( 'assets/js/admin/payment-gateway-selector-builder.js', EFP_PLUGIN_FILE ),
			array( 'jquery', 'jquery-ui-sortable' ),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1.0.0',
			true
		);
	}

	/**
	 * When all "Gateways" checkboxes are unchecked, none are POSTed — store an empty allowlist.
	 *
	 * @param array $data Parsed builder save payload.
	 * @return array
	 */
	public function normalize_allowlist_on_save( $data ) {
		if ( empty( $data['form_fields'] ) || ! is_array( $data['form_fields'] ) ) {
			return $data;
		}
		foreach ( $data['form_fields'] as $fid => $field ) {
			if ( empty( $field['type'] ) || $this->type !== $field['type'] ) {
				continue;
			}
			if ( empty( $field['pgw_allowlist_sent'] ) ) {
				continue;
			}
			if ( ! isset( $field['allowed_gateways'] ) ) {
				$data['form_fields'][ $fid ]['allowed_gateways'] = array();
				continue;
			}
			if ( ! is_array( $field['allowed_gateways'] ) ) {
				$data['form_fields'][ $fid ]['allowed_gateways'] = array();
			}
		}
		return $data;
	}

	/**
	 * Allow only one Payment Gateway field per form (builder sidebar disables after add).
	 *
	 * @param array $types Field types handled by EVFPanelBuilder.oneTimeDraggableField().
	 * @return array
	 */
	public function register_one_time_draggable_type( $types ) {
		if ( ! is_array( $types ) ) {
			$types = array();
		}
		$types[] = 'payment-gateway-selector';
		return $types;
	}

	/**
	 * Prevent duplicating the only allowed Payment Gateway field in the builder.
	 *
	 * @param string $css Preview CSS classes.
	 * @param array  $field Field data.
	 * @return string
	 */
	public function builder_preview_extra_class( $css, $field ) {
		if ( ! empty( $field['type'] ) && $this->type === $field['type'] ) {
			$css .= ' no-duplicate';
		}
		return $css;
	}

	/**
	 * Only show when the form has payment gateways enabled.
	 *
	 * @param bool  $bool      Whether to display.
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 * @return bool
	 */
	public function should_display_field( $bool, $field, $form_data ) {
		if ( ! $bool ) {
			return false;
		}
		foreach ( $this->get_gateway_rows_visibility( $field, $form_data ) as $row ) {
			if ( ! empty( $row['selectable'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Field properties.
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data.
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ) {
		$properties['inputs']['primary']['class'][] = 'evf-payment-gateway-selector-inputs';
		$properties['label']['required']            = false;
		if ( ! empty( $properties['label']['value'] ) && false === strpos( (string) $properties['label']['value'], 'class="required"' ) ) {
			$properties['label']['value'] .= ' <span class="required">*</span>';
		}
		return $properties;
	}

	/**
	 * Payment method should always be required.
	 *
	 * @param bool  $required Required status.
	 * @param array $field    Field settings.
	 *
	 * @return bool
	 */
	public function field_default_required( $required, $field ) {
		if ( $this->type === $field['type'] ) {
			return true;
		}

		return $required;
	}

	/**
	 * Builder: gateways to expose on the frontend.
	 *
	 * @param array $field Field data.
	 */
	public function payment_gateway_choice( $field ) {
		$form_id = ! empty( $this->form_id ) ? absint( $this->form_id ) : 0;
		if ( ! $form_id && isset( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_id = absint( wp_unslash( $_GET['form_id'] ) );
		}
		$form_data = array();
		if ( $form_id ) {
			$form_data = evf()->form->get( $form_id, array( 'content_only' => true ) );
		} elseif ( ! empty( $this->form_data ) && is_array( $this->form_data ) ) {
			// On freshly dropped fields in builder, form_id may not be set yet.
			$form_data = $this->form_data;
		}

		$enabled_slugs = array_flip( evf_payment_gateway_selector_form_enabled_gateways( $form_data ) );
		$labels        = evf_payment_gateway_selector_labels();

		// Only show gateways whose addon is active.
		foreach ( array_keys( $labels ) as $slug ) {
			if ( ! evf_payment_gateway_selector_is_addon_active( $slug ) ) {
				unset( $labels[ $slug ] );
			}
		}

		// If no gateway addons are active, show a helpful message instead of the list.
		if ( empty( $labels ) ) {
			$tooltip    = esc_html__( 'Enable which payment gateways appear on the form.', 'everest-forms-pro' );
			$addons_url = admin_url( 'admin.php?page=evf-dashboard#/features?category=Payment%20Gateways' );

			$output  = $this->field_element(
				'label',
				$field,
				array(
					'slug'    => 'payment_gateway_choice',
					'value'   => esc_html__( 'Gateways', 'everest-forms-pro' ),
					'tooltip' => $tooltip,
				),
				false
			);

			$output .= '<p class="description" style="margin:8px 0;color:#5a5c63;">' .
				sprintf(
					/* translators: %s: Addons page URL. */
					wp_kses_post( __( 'You haven\'t enabled any payment gateway add-ons yet. <a href="%s" target="_blank" rel="noopener noreferrer">Enable a payment gateway add-on</a> to get started.', 'everest-forms-pro' ) ),
					esc_url( $addons_url )
				) .
			'</p>';

			$this->field_element(
				'row',
				$field,
				array(
					'slug'    => 'payment_gateway_choice',
					'content' => $output,
				)
			);

			return;
		}
		$saved           = isset( $field['allowed_gateways'] ) && is_array( $field['allowed_gateways'] ) ? $field['allowed_gateways'] : array();
		$allowlist_saved = ! empty( $field['pgw_allowlist_sent'] );

		$drag_icon = '<svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" role="img" aria-hidden="true" focusable="false"><path d="M13,8c0.6,0,1-0.4,1-1s-0.4-1-1-1s-1,0.4-1,1S12.4,8,13,8z M5,6C4.4,6,4,6.4,4,7s0.4,1,1,1s1-0.4,1-1S5.6,6,5,6z M5,10 c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S5.6,10,5,10z M13,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S13.6,10,13,10z M9,6 C8.4,6,8,6.4,8,7s0.4,1,1,1s1-0.4,1-1S9.6,6,9,6z M9,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S9.6,10,9,10z"></path></svg>';

		$rows  = '<input type="hidden" name="form_fields[' . esc_attr( $field['id'] ) . '][pgw_allowlist_sent]" value="1" />';
		$rows .= '<div class="evf-pgw-builder-list" id="evf-pgw-sortable-' . esc_attr( $field['id'] ) . '">';

		$enabled_labels  = array();
		$disabled_labels = array();
		foreach ( $labels as $slug => $gateway_label ) {
			if ( isset( $enabled_slugs[ $slug ] ) ) {
				$enabled_labels[ $slug ] = $gateway_label;
			} else {
				$disabled_labels[ $slug ] = $gateway_label;
			}
		}
		$labels = $enabled_labels + $disabled_labels;

		$global_paypal_email = get_option( 'everest_forms_paypal_email', '' );
		foreach ( $labels as $slug => $gateway_label ) {
			$is_on      = isset( $enabled_slugs[ $slug ] );
			// Before first save (legacy): default all enabled gateways checked. After save: only checked slugs.
			// Exception: PayPal defaults unchecked when no global credentials are configured.
			$paypal_no_global = ( 'paypal' === $slug && empty( $global_paypal_email ) );
			$checked          = ( ! $allowlist_saved && empty( $saved ) )
				? ( $paypal_no_global ? false : true )
				: in_array( $slug, $saved, true );
			$item_class = 'evf-pgw-builder-item' . ( $is_on ? '' : ' evf-pgw-builder-item--disabled' );
			$input_name = 'form_fields[' . esc_attr( $field['id'] ) . '][allowed_gateways][]';

			/**
			 * Filter: inject accordion panel HTML for a gateway row in the builder.
			 *
			 * @param string $html      Panel HTML (empty string = panel still renders, just blank).
			 * @param string $slug      Gateway slug (e.g. 'stripe').
			 * @param array  $field     Payment gateway selector field data.
			 * @param array  $form_data Decoded form settings (for field-map selects, etc.).
			 */
			$panel_html     = (string) apply_filters( 'evf_pgw_builder_gateway_panel_html', '', $slug, $field, $form_data );
			$chevron_hidden = ( '' === $panel_html );
			$chevron_hidden = (bool) apply_filters( 'evf_pgw_builder_gateway_chevron_hidden', $chevron_hidden, $slug, $field, $form_data, $panel_html );

			$rows .= '<div class="' . $item_class . '">';
			$rows .= '<div class="evf-pgw-builder-row" data-gateway="' . esc_attr( $slug ) . '">';

			$rows .= '<span class="evf-pgw-builder-drag" title="' . esc_attr__( 'Drag to reorder', 'everest-forms-pro' ) . '">' . $drag_icon . '</span>';

			$rows .= '<span class="evf-pgw-builder-name">' . esc_html( $gateway_label ) . '</span>';

			$rows .= '<label class="evf-pgw-builder-toggle">';
			if ( $is_on ) {
				$rows .= '<input type="checkbox" name="' . $input_name . '" value="' . esc_attr( $slug ) . '"' . checked( $checked, true, false ) . '>';
			} else {
				// Keep name/value so builder JS can enable these when the gateway is turned on in Payments.
				$rows .= '<input type="checkbox" disabled="disabled" name="' . $input_name . '" value="' . esc_attr( $slug ) . '">';
			}
			$rows .= '<span class="evf-pgw-builder-toggle-track"><span class="evf-pgw-builder-toggle-thumb"></span></span>';
			$rows .= '</label>';

			$chevron_class  = 'evf-pgw-builder-chevron' . ( $chevron_hidden ? ' evf-pgw-builder-chevron--hidden' : '' );
			$chevron_style  = ( ! $checked && ! $chevron_hidden ) ? ' style="display:none;"' : '';
			$rows          .= '<button type="button" class="' . esc_attr( $chevron_class ) . '"' . $chevron_style . ' aria-expanded="false" aria-label="' . esc_attr__( 'Expand', 'everest-forms-pro' ) . '"></button>';

			$rows .= '</div>';

			if ( '' !== $panel_html ) {
				$panel_style  = $checked ? '' : ' style="display:none;"';
				$rows .= '<div class="evf-pgw-builder-panel"' . $panel_style . '>';
				$rows .= '<div class="evf-pgw-builder-panel-content">' . $panel_html . '</div>';
				$rows .= '</div>';
			}

			$rows .= '</div>';
		}

		$rows .= '</div>';

		$tooltip = esc_html__( 'Enable which payment gateways appear on the form.', 'everest-forms-pro' );

		$output  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'payment_gateway_choice',
				'value'   => esc_html__( 'Gateways', 'everest-forms-pro' ),
				'tooltip' => $tooltip,
			),
			false
		);
		$output .= $rows;

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'payment_gateway_choice',
				'content' => $output,
			)
		);
	}

	/**
	 * All gateways for this field with Payments on/off state (for preview and frontend list).
	 *
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 * @return array<int,array{slug:string,label:string,selectable:bool}>
	 */
	protected function get_gateway_rows_visibility( $field, $form_data ) {
		$labels      = evf_payment_gateway_selector_labels();
		$enabled_ids = array_flip( evf_payment_gateway_selector_form_enabled_gateways( $form_data ) );
		$rows        = array();

		// Legacy forms (no pgw_allowlist_sent): do not filter by allowlist — show every gateway label row.
		// After at least one save with our hidden marker: respect allowlist, including empty (show none).
		$explicit_allowlist = ! empty( $field['pgw_allowlist_sent'] );
		$allowed_slugs      = array();
		if ( $explicit_allowlist ) {
			$allowed_slugs = isset( $field['allowed_gateways'] ) && is_array( $field['allowed_gateways'] ) ? $field['allowed_gateways'] : array();
		}

		if ( $explicit_allowlist ) {
			// Respect saved order: iterate allowed_gateways[] as-saved, not labels map.
			foreach ( $allowed_slugs as $slug ) {
				if ( ! isset( $labels[ $slug ] ) ) {
					continue;
				}
				$rows[] = array(
					'slug'       => $slug,
					'label'      => $labels[ $slug ],
					'selectable' => isset( $enabled_ids[ $slug ] ),
				);
			}
		} else {
			// Legacy (no allowlist): use labels order, show all gateways.
			foreach ( $labels as $slug => $label ) {
				$rows[] = array(
					'slug'       => $slug,
					'label'      => $label,
					'selectable' => isset( $enabled_ids[ $slug ] ),
				);
			}
		}

		// PayPal: selectable if addon active AND ("Use Global" on with global creds OR per-form email filled).
		foreach ( $rows as &$row ) {
			if ( 'paypal' !== $row['slug'] ) {
				continue;
			}
			if ( ! evf_payment_gateway_selector_is_addon_active( 'paypal' ) ) {
				$row['selectable'] = false;
				continue;
			}
			$paypal_payments  = isset( $form_data['payments']['paypal'] ) ? $form_data['payments']['paypal'] : array();
			$per_form_email   = ! empty( $paypal_payments['paypal_email'] );
			$has_global_creds = ! empty( get_option( 'everest_forms_paypal_email', '' ) );
			$use_global       = isset( $paypal_payments['use_global_setting'] )
				? ( function_exists( 'evf_string_to_bool' ) ? evf_string_to_bool( $paypal_payments['use_global_setting'] ) : ! empty( $paypal_payments['use_global_setting'] ) )
				: $has_global_creds;
			$row['selectable'] = ( $use_global && $has_global_creds ) || $per_form_email;
		}
		unset( $row );

		return $rows;
	}

	/**
	 * Gateways the user can choose: enabled on form ∩ allowed in field settings.
	 *
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 * @return array<string,string> Slug => label.
	 */
	protected function get_display_gateways( $field, $form_data ) {
		$out = array();
		foreach ( $this->get_gateway_rows_visibility( $field, $form_data ) as $row ) {
			if ( ! empty( $row['selectable'] ) ) {
				$out[ $row['slug'] ] = $row['label'];
			}
		}
		return $out;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$this->field_preview_option( 'label', $field );

		$form_id    = ! empty( $this->form_id ) ? absint( $this->form_id ) : 0;
		$form_data  = $form_id ? evf()->form->get( $form_id, array( 'content_only' => true ) ) : array();
		$rows       = is_array( $form_data ) ? $this->get_gateway_rows_visibility( $field, $form_data ) : array();

		$preview_rows = array();
		foreach ( $rows as $r ) {
			if ( ! empty( $r['selectable'] ) ) {
				$preview_rows[] = $r;
			}
		}

		echo '<div class="evf-payment-gateway-selector-preview-wrap">';
		if ( empty( $rows ) ) {
			$addons_url   = admin_url( 'admin.php?page=evf-dashboard#/features?category=Payment%20Gateways' );
			$none_checked = ! empty( $field['pgw_allowlist_sent'] ) && ( ! isset( $field['allowed_gateways'] ) || ! is_array( $field['allowed_gateways'] ) || empty( $field['allowed_gateways'] ) );
			$empty_msg    = $none_checked
				? esc_html__( 'Enable a payment gateway in the field options to start accepting payments.', 'everest-forms-pro' )
				: sprintf(
					/* translators: %s: Addons page URL. */
					wp_kses_post( __( 'You haven\'t enabled any payment gateway add-ons yet. <a href="%s" target="_blank" rel="noopener noreferrer">Enable a payment gateway add-on</a> to get started.', 'everest-forms-pro' ) ),
					esc_url( $addons_url )
				);
			echo '<p class="description evf-payment-gateway-selector-preview-note" style="margin:8px 0;color:#5a5c63;">' . $empty_msg . '</p>';
		} elseif ( empty( $preview_rows ) ) {
			echo '<p class="description evf-payment-gateway-selector-preview-note" style="margin:8px 0;color:#5a5c63;">' . esc_html__( 'Enable a payment gateway in the field options to start accepting payments.', 'everest-forms-pro' ) . '</p>';
		} else {
			echo '<div class="evf-pgw-grid evf-pgw-grid--preview" style="pointer-events:none;">';
			foreach ( $preview_rows as $row ) {
				echo '<div class="evf-pgw-logo-tile">' . $this->get_gateway_logo_html( $row['slug'] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';
		}
		echo '</div>';

		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Maps gateway slugs to their SVG asset filenames in assets/img/payment/.
	 *
	 * @return array<string,string>
	 */
	protected function get_gateway_logo_assets() {
		return array(
			'stripe'        => 'stripe.svg',
			'paypal'        => 'paypal.svg',
			'square'        => 'square_logo.svg',
			'mollie'        => 'mollie.svg',
			'razorpay'      => 'razorpay_logo.svg.svg',
			'authorize_net' => 'authorize.net.svg',
		);
	}

	/**
	 * Returns the branded logo HTML for a given gateway slug.
	 *
	 * @param string $slug Gateway slug.
	 * @return string
	 */
	protected function get_gateway_logo_html( $slug ) {
		$assets = $this->get_gateway_logo_assets();

		if ( isset( $assets[ $slug ] ) ) {
			$url   = esc_url( plugins_url( 'assets/img/payment/' . $assets[ $slug ], EFP_PLUGIN_FILE ) );
			$label = esc_attr( ucfirst( str_replace( '_', ' ', $slug ) ) );
			return '<span class="evf-pgw-logo evf-pgw-logo--' . esc_attr( $slug ) . '">'
				. '<img src="' . $url . '" alt="' . $label . '" class="evf-pgw-logo-img" />'
				. '</span>';
		}

		return '<span class="evf-pgw-logo">' . esc_html( ucfirst( str_replace( '_', ' ', $slug ) ) ) . '</span>';
	}

	/**
	 * Returns the redirect hint panel HTML for a gateway.
	 *
	 * @param string $slug  Gateway slug.
	 * @param string $label Gateway display label.
	 * @return string
	 */
	protected function get_gateway_hint_html( $slug, $label ) {
		$icon_svg = '<svg class="evf-pgw-alt-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14L21 3"/><path d="M21 14v7h-7"/><path d="M3 10V3h7"/></svg>';

		/* translators: %s: Gateway name (e.g. PayPal). */
		$text = sprintf(
			/* translators: %s: payment gateway name */
			esc_html__( "You'll be redirected to %s to complete your purchase securely.", 'everest-forms-pro' ),
			'<strong>' . esc_html( $label ) . '</strong>'
		);

		return '<div class="evf-payment-gateway-hint evf-pgw-alt-panel" data-evf-gateway-hint="' . esc_attr( $slug ) . '" style="display:none;" role="note">'
			. $icon_svg
			. '<div>' . $text . '</div>'
			. '</div>';
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @param array $field      Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data  All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$rows = $this->get_gateway_rows_visibility( $field, $form_data );
		if ( empty( $rows ) ) {
			return;
		}

		$selectable_count = 0;
		foreach ( $rows as $row ) {
			if ( ! empty( $row['selectable'] ) ) {
				++$selectable_count;
			}
		}
		$auto_select_single = ( 1 === $selectable_count );

		$primary         = $field['properties']['inputs']['primary'];
		$field_id        = $field['id'];
		$hint_html       = '';
		$required        = 'required';
		$is_first_choice = true;
		$first_slug      = '';

		foreach ( $rows as $row ) {
			if ( empty( $row['selectable'] ) ) {
				continue;
			}
			if ( '' === $first_slug ) {
				$first_slug = $row['slug'];
			}
			if ( in_array( $row['slug'], array( 'paypal', 'mollie', 'razorpay' ), true ) ) {
				$hint_html .= $this->get_gateway_hint_html( $row['slug'], $row['label'] );
			}
		}

		echo '<fieldset ' . evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="evf-pgw-grid">';

		foreach ( $rows as $row ) {
			if ( empty( $row['selectable'] ) ) {
				continue;
			}
			$slug       = $row['slug'];
			$input_id   = $primary['id'] . '-' . $slug;
			$is_checked = $auto_select_single && $slug === $first_slug;
			$card_class = 'evf-pgw-card' . ( $is_checked ? ' evf-pgw-card--selected' : '' );

			printf(
				'<label class="%1$s" for="%2$s">'
				. '<input type="radio" class="evf-payment-gateway-radio evf-pgw-radio" id="%2$s" name="everest_forms[form_fields][%3$s]" value="%4$s" data-evf-gateway="%4$s" %5$s %6$s />'
				. '%7$s'
				. '</label>',
				esc_attr( $card_class ),
				esc_attr( $input_id ),
				esc_attr( $field_id ),
				esc_attr( $slug ),
				checked( $is_checked, true, false ),
				$is_first_choice ? $required : '',
				$this->get_gateway_logo_html( $slug ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			$is_first_choice = false;
		}

		echo '</div>';

		if ( $hint_html ) {
			echo '<div class="evf-payment-gateway-hints">' . $hint_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</fieldset>';

		// Fallback Stripe mount when selector includes Stripe but no credit-card field exists.
		$has_selectable_stripe        = false;
		$has_selectable_square        = false;
		$has_selectable_authorize_net = false;
		foreach ( $rows as $row ) {
			if ( ! empty( $row['selectable'] ) && 'stripe' === $row['slug'] ) {
				$has_selectable_stripe = true;
			}
			if ( ! empty( $row['selectable'] ) && 'square' === $row['slug'] ) {
				$has_selectable_square = true;
			}
			if ( ! empty( $row['selectable'] ) && 'authorize_net' === $row['slug'] ) {
				$has_selectable_authorize_net = true;
			}
		}

		if ( $has_selectable_stripe && ! $this->form_has_field_type( $form_data, 'credit-card' ) ) {
			$form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
			if ( $form_id ) {
				printf(
					'<div class="evf-field evf-field-credit-card evf-payment-gateway-selector-stripe-proxy evf-pgw-card-panel" style="display:none;"><div class="evf-pgw-stripe-mount"><div id="everest_forms_stripe_gateway_%1$d" data-gateway="stripe" class="input-text everest-forms-gateway" data-form-id="%1$d"></div></div><label id="card-errors-stripe-%1$d" class="evf-error evf-pgw-card-errors" role="alert"></label></div>',
					$form_id
				);
			}
		}

		// Fallback Square mount when selector includes Square but no square-payment field exists.
		if ( $has_selectable_square && ! $this->form_has_field_type( $form_data, 'square-payment' ) ) {
			$form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
			if ( $form_id ) {
				$ssl_warning_html = '';
				if ( ! is_ssl() ) {
					$ssl_warning_html = '<label class="everest-forms-ssl-warning evf-error">' . esc_html__( 'Please establish an SSL connection on this page to create a Square payment.', 'everest-forms-pro' ) . '</label>';
				}
				printf(
					'<div class="evf-field evf-field-square-payment evf-payment-gateway-selector-square-proxy evf-pgw-card-panel" style="display:none;"><div class="evf-pgw-square-mount"><div id="everest_forms_square_gateway_%1$d" data-gateway="square" class="input-text everest-forms-gateway" data-form-id="%1$d"></div></div><label id="card-errors-square-%1$d" class="evf-error evf-pgw-card-errors" role="alert"></label>%2$s</div>',
					$form_id,
					$ssl_warning_html
				);
			}
		}

		// Fallback Authorize.Net card inputs when selector includes Authorize.Net but no authorize-net field exists.
		if ( $has_selectable_authorize_net && ! $this->form_has_field_type( $form_data, 'authorize-net' ) ) {
			$form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
			if ( $form_id ) {
				$this->render_authorize_net_gateway_selector_proxy( $form_id );
			}
		}
	}

	/**
	 * Output minimal Authorize.Net card markup for payment-gateway-selector (matches addon field classes for Accept.js).
	 *
	 * @param int $form_id Form ID.
	 */
	protected function render_authorize_net_gateway_selector_proxy( $form_id ) {
		$form_id   = absint( $form_id );
		$id_prefix = 'evf-' . $form_id . '-authorize-net-pgw-proxy';

		echo '<div class="evf-field evf-field-authorize-net evf-payment-gateway-selector-authorize-net-proxy evf-pgw-card-panel evf-pgw-authorize-card" style="display:none;">';

		printf(
			'<input type="hidden" class="everest-forms-authorize_net" data-gateway="authorize-net" data-form-id="%s" />',
			esc_attr( (string) $form_id )
		);

		printf(
			'<input type="hidden" id="%1$s-hidden" class="evf-authorize-net-credit-card-input input-text" />',
			esc_attr( $id_prefix )
		);

		// Card number.
		echo '<div class="evf-field-row"><div class="evf-field-authorize-net-number">';
		echo '<label class="everest-forms-sub-label" for="' . esc_attr( $id_prefix ) . '-card">' . esc_html__( 'Card Number', 'everest-forms-pro' ) . '</label>';
		printf(
			'<input type="text" id="%1$s-card" class="evf-field-authorize-net-card-number evf-pgw-input validate-required" data-rule-creditcard="yes" name="" value="" autocomplete="off" placeholder="' . esc_attr__( '1234 1234 1234 1234', 'everest-forms-pro' ) . '" />',
			esc_attr( $id_prefix )
		);
		echo '</div></div>';

		// Expiration + CVC (single aligned row).
		echo '<div class="evf-field-row evf-pgw-authorize-inline-row">';
		echo '<div class="evf-pgw-authorize-col evf-field-authorize-net-month">';
		echo '<label class="everest-forms-sub-label" for="' . esc_attr( $id_prefix ) . '-month">' . esc_html__( 'Month', 'everest-forms-pro' ) . '</label>';
		printf(
			'<select id="%1$s-month" class="evf-field-authorize-net-expiration-month evf-pgw-input validate-required">',
			esc_attr( $id_prefix )
		);
		echo '<option class="placeholder" value="" selected disabled>' . esc_html__( 'MM', 'everest-forms-pro' ) . '</option>';
		foreach ( range( 1, 12 ) as $m ) {
			$val = sprintf( '%02d', $m );
			printf( '<option value="%1$s">%2$s</option>', esc_attr( $val ), esc_html( $val ) );
		}
		echo '</select></div>';

		echo '<div class="evf-pgw-authorize-col evf-field-authorize-net-year">';
		echo '<label class="everest-forms-sub-label" for="' . esc_attr( $id_prefix ) . '-year">' . esc_html__( 'Year', 'everest-forms-pro' ) . '</label>';
		printf(
			'<select id="%1$s-year" class="evf-field-authorize-net-expiration-year evf-pgw-input validate-required">',
			esc_attr( $id_prefix )
		);
		echo '<option class="placeholder" value="" selected disabled>' . esc_html__( 'YY', 'everest-forms-pro' ) . '</option>';
		$base = (int) gmdate( 'y' );
		$end  = $base + 10;
		for ( $y = $base; $y <= $end; $y++ ) {
			printf( '<option value="%1$d">%1$d</option>', absint( $y ) );
		}
		echo '</select></div>';

		echo '<div class="evf-pgw-authorize-col evf-field-authorize-net-cvc">';
		echo '<label class="everest-forms-sub-label" for="' . esc_attr( $id_prefix ) . '-cvc">' . esc_html__( 'CVC', 'everest-forms-pro' ) . '</label>';
		printf(
			'<input type="text" id="%1$s-cvc" class="evf-field-authorize-net-card-code evf-pgw-input validate-required" data-rule-cvc="yes" name="" value="" maxlength="4" autocomplete="off" placeholder="' . esc_attr__( 'CVC', 'everest-forms-pro' ) . '" />',
			esc_attr( $id_prefix )
		);
		echo '</div>';

		echo '</div>';

		if ( ! is_ssl() ) {
			echo '<label class="everest-forms-ssl-warning evf-error">' . esc_html__( 'Please establish an SSL connection on this page, as it is required by Authorize.net for security.', 'everest-forms-pro' ) . '</label>';
		}

		echo '</div>';
	}

	/**
	 * Check whether form contains a field type.
	 *
	 * @param array  $form_data Form data.
	 * @param string $type      Field type slug.
	 * @return bool
	 */
	protected function form_has_field_type( $form_data, $type ) {
		if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
			return false;
		}
		foreach ( $form_data['form_fields'] as $f ) {
			if ( ! empty( $f['type'] ) && $type === $f['type'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Validate submission.
	 *
	 * @param string $field_id     Field id.
	 * @param mixed  $field_submit Submitted value.
	 * @param array  $form_data    Form data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		parent::validate( $field_id, $field_submit, $form_data );
		if ( ! empty( evf()->task->errors[ $form_data['id'] ][ $field_id ] ) ) {
			return;
		}
		if ( '' === $field_submit || null === $field_submit ) {
			$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' === $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ? $form_data['form_fields'][ $field_id ]['required-field-message'] : get_option( 'everest_forms_required_validation' );
			evf()->task->errors[ $form_data['id'] ][ $field_id ] = esc_html( $required_message );
			update_option( 'evf_validation_error', 'yes' );
			return;
		}
		$field = isset( $form_data['form_fields'][ $field_id ] ) ? $form_data['form_fields'][ $field_id ] : array();
		$allowed = $this->get_display_gateways( $field, $form_data );
		if ( ! isset( $allowed[ sanitize_key( $field_submit ) ] ) ) {
			evf()->task->errors[ $form_data['id'] ][ $field_id ] = esc_html__( 'Please choose a valid payment method.', 'everest-forms-pro' );
			update_option( 'evf_validation_error', 'yes' );
		}
	}

	/**
	 * Format entry value.
	 *
	 * @param string $field_id     Field id.
	 * @param mixed  $field_submit Submit value.
	 * @param array  $form_data    Form data.
	 * @param string $meta_key     Meta key.
	 * @return array
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$field = $form_data['form_fields'][ $field_id ];
		$slug  = sanitize_key( $field_submit );
		$labels = evf_payment_gateway_selector_labels();
		$label = isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug;

		return array(
			'id'        => $field_id,
			'type'      => $this->type,
			'meta_key'  => $meta_key,
			'value'     => array(
				'name'  => $field['label'],
				'type'  => $this->type,
				'label' => $label,
			),
			'value_raw' => $slug,
		);
	}

	/**
	 * Entry export / email.
	 *
	 * @param array $field Field with value.
	 * @return array
	 */
	public function field_exporter( $field ) {
		$slug  = isset( $field['value_raw'] ) ? $field['value_raw'] : ( isset( $field['value'] ) ? $field['value'] : '' );
		$labels = evf_payment_gateway_selector_labels();
		$text = isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug;

		return array(
			'label' => ! empty( $field['name'] ) ? $field['name'] : $this->name . ' - ' . ( isset( $field['id'] ) ? $field['id'] : '' ),
			'value' => $text ? sanitize_text_field( $text ) : false,
		);
	}

	/**
	 * Register payment gateway selector frontend styles.
	 */
	protected function register_frontend_styles() {
		$css_path = plugin_dir_path( EFP_PLUGIN_FILE ) . 'assets/css/evf-payment-gateway-selector-frontend.css';
		$version  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : ( defined( 'EFP_VERSION' ) ? EFP_VERSION : '1.0.0' );

		wp_register_style(
			'evf-payment-gateway-selector-frontend',
			plugins_url( 'assets/css/evf-payment-gateway-selector-frontend.css', EFP_PLUGIN_FILE ),
			array(),
			$version
		);
	}

	/**
	 * Enqueue card-grid styles (form builder look) for the payment gateway field.
	 */
	protected function enqueue_frontend_styles() {
		$this->register_frontend_styles();
		wp_enqueue_style( 'evf-payment-gateway-selector-frontend' );
	}

	/**
	 * Load styles in the block editor so embedded forms match the form builder preview.
	 */
	public function enqueue_block_editor_styles() {
		$css_path = plugin_dir_path( EFP_PLUGIN_FILE ) . 'assets/css/evf-payment-gateway-selector-frontend.css';
		$version  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : ( defined( 'EFP_VERSION' ) ? EFP_VERSION : '1.0.0' );

		wp_enqueue_style(
			'evf-payment-gateway-selector-frontend',
			plugins_url( 'assets/css/evf-payment-gateway-selector-frontend.css', EFP_PLUGIN_FILE ),
			array( 'everest-forms-block-editor' ),
			$version
		);
	}

	/**
	 * Load styles on the frontend when the Everest Forms block is present.
	 */
	public function enqueue_block_frontend_styles() {
		if ( is_admin() || ! function_exists( 'has_block' ) || ! has_block( 'everest-forms/form-selector' ) ) {
			return;
		}
		$this->enqueue_frontend_styles();
	}

	/**
	 * Enqueue frontend script when the form includes this field.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public function load_assets( $atts ) {
		if ( empty( $atts['id'] ) ) {
			return;
		}
		$form_data = evf()->form->get( absint( $atts['id'] ), array( 'content_only' => true ) );
		if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
			return;
		}
		foreach ( $form_data['form_fields'] as $f ) {
			if ( ! empty( $f['type'] ) && 'payment-gateway-selector' === $f['type'] ) {
				$this->enqueue_frontend_styles();

				wp_enqueue_script(
					'evf-payment-gateway-selector',
					plugins_url( 'assets/js/frontend/payment-gateway-selector.js', EFP_PLUGIN_FILE ),
					array( 'jquery' ),
					defined( 'EFP_VERSION' ) ? EFP_VERSION : '1.0.0',
					true
				);

				// Ensure Stripe/Square frontend assets are loaded when selector offers these
				// but dedicated fields are not present.
				$has_selector_stripe        = false;
				$has_selector_square        = false;
				$has_selector_authorize_net = false;
				foreach ( $form_data['form_fields'] as $sf ) {
					if ( empty( $sf['type'] ) || 'payment-gateway-selector' !== $sf['type'] ) {
						continue;
					}
					foreach ( $this->get_gateway_rows_visibility( $sf, $form_data ) as $row ) {
						if ( ! empty( $row['selectable'] ) && 'stripe' === $row['slug'] ) {
							$has_selector_stripe = true;
						}
						if ( ! empty( $row['selectable'] ) && 'square' === $row['slug'] ) {
							$has_selector_square = true;
						}
						if ( ! empty( $row['selectable'] ) && 'authorize_net' === $row['slug'] ) {
							$has_selector_authorize_net = true;
						}
						if ( $has_selector_stripe && $has_selector_square && $has_selector_authorize_net ) {
							break 2;
						}
					}
				}

				if ( $has_selector_stripe && ! $this->form_has_field_type( $form_data, 'credit-card' ) && defined( 'EVF_STRIPE_PLUGIN_FILE' ) ) {
					$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
					$stripe_key = 'no' === get_option( 'everest_forms_stripe_test_mode' ) ? get_option( 'everest_forms_stripe_live_publishable_key' ) : get_option( 'everest_forms_stripe_test_publishable_key' );
					$enabled_gateways = array();
					if ( ! empty( $form_data['payments'] ) && is_array( $form_data['payments'] ) ) {
						foreach ( $form_data['payments'] as $gateway => $gateway_data ) {
							if ( isset( $gateway_data[ 'enable_' . $gateway ] ) && '1' === (string) $gateway_data[ 'enable_' . $gateway ] ) {
								$enabled_gateways[ $gateway ] = '1';
							}
						}
					}
					$stripe_payment_details = isset( $form_data['payments']['stripe']['connection_1'] ) ? $form_data['payments']['stripe']['connection_1'] : array();
					$recurring_flag           = isset( $form_data['payments']['stripe']['recurring'] ) ? $form_data['payments']['stripe']['recurring'] : '0';
					if ( ( '0' === (string) $recurring_flag || '' === (string) $recurring_flag ) && function_exists( 'evf_form_uses_subscription_with_payment_gateway_selector' ) && evf_form_uses_subscription_with_payment_gateway_selector( $form_data ) && function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'stripe' ) ) ) {
						$recurring_flag = '1';
					}
					$evf_stripe_params      = array(
						'publishable_key'            => $stripe_key,
						'everest_forms_currency'     => get_option( 'everest_forms_currency' ),
						'enabled_gateways'           => $enabled_gateways,
						'recurring'                  => $recurring_flag,
						'i18n_auth_fail'             => __( 'No internet, check you connection and try again.', 'everest-forms-stripe' ),
						'ideal_payment_failed'       => __( 'Payment Failed, Please try again', 'everest-forms-stripe' ),
						'stripe_conditional_details' => $stripe_payment_details,
					);

					wp_enqueue_script( 'everest-forms-stripe-v3', 'https://js.stripe.com/v3/', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
					wp_enqueue_script(
						'everest-forms-stripe',
						plugins_url( "/assets/js/frontend/everest-forms-stripe{$suffix}.js", EVF_STRIPE_PLUGIN_FILE ),
						array( 'jquery' ),
						defined( 'EVF_STRIPE_VERSION' ) ? EVF_STRIPE_VERSION : '1.0.0',
						true
					);
					wp_localize_script( 'everest-forms-stripe', 'evf_stripe_params_' . absint( $atts['id'] ), $evf_stripe_params );
				}

				if ( $has_selector_square && ! $this->form_has_field_type( $form_data, 'square-payment' ) ) {
					$square_test_application_id = get_option( 'everest_forms_square_test_app_id', '' );
					$square_test_access_token   = get_option( 'everest_forms_square_test_access_token', '' );
					$square_test_location_id    = get_option( 'everest_forms_square_test_location_id', '' );
					$check_square_test_mode     = get_option( 'everest_forms_pro_square_test_mode', 'no' );
					$square_live_application_id = get_option( 'everest_forms_square_live_app_id', '' );
					$square_live_access_token   = get_option( 'everest_forms_square_live_access_token', '' );
					$square_live_location_id    = get_option( 'everest_forms_square_live_location_id', '' );

					wp_enqueue_script( 'everest-forms-pro-square-payment' );
					if ( function_exists( 'evf_square_mark_recurring_form_script' ) ) {
						evf_square_mark_recurring_form_script( absint( $atts['id'] ), $form_data );
					}
					if ( ! empty( $square_test_application_id ) && ! empty( $square_test_access_token ) && ! empty( $square_test_location_id ) && 'yes' === $check_square_test_mode ) {
						wp_enqueue_script( 'everest-forms-pro-square-v1', 'https://sandbox.web.squarecdn.com/v1/square.js', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
					} elseif ( ! empty( $square_live_application_id ) && ! empty( $square_live_access_token ) && ! empty( $square_live_location_id ) && 'no' === $check_square_test_mode ) {
						wp_enqueue_script( 'everest-forms-pro-square-v1', 'https://web.squarecdn.com/v1/square.js', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
					}
				}

				if ( $has_selector_authorize_net && ! $this->form_has_field_type( $form_data, 'authorize-net' ) && defined( 'EVF_AUTHORIZE_NET_PLUGIN_FILE' ) && class_exists( '\EverestForms\AuthorizeNet\Helpers' ) && class_exists( '\EverestForms\AuthorizeNet\Api' ) ) {
					$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
					$mode   = \EverestForms\AuthorizeNet\Helpers::get_authorize_net_mode();
					$an_js  = array(
						'invalid_card_number_err_msg' => esc_html__( 'Please enter a valid credit card number.', 'everest-forms-pro' ),
						'invalid_cvc_err_msg'         => esc_html__( 'Please enter a valid CVC.', 'everest-forms-pro' ),
						'api_login_id'                => \EverestForms\AuthorizeNet\Helpers::get_api_login_id(),
						'public_client_key'           => ( new \EverestForms\AuthorizeNet\Api() )->get_public_client_key( $mode ),
						'submit_button_text'          => isset( $form_data['settings']['submit_button_text'] ) ? $form_data['settings']['submit_button_text'] : __( 'Submit', 'everest-forms-pro' ),
					);

					wp_enqueue_script(
						'everest-forms-authorize-net-payment',
						plugins_url( '/assets/js/lib/jquery.payment.min.js', EVF_AUTHORIZE_NET_PLUGIN_FILE ),
						array( 'jquery' ),
						defined( 'EVF_AUTHORIZE_NET_VERSION' ) ? EVF_AUTHORIZE_NET_VERSION : '1.0.0',
						true
					);
					wp_enqueue_script(
						'everest-forms-authorize-net-accept-js',
						'live' === $mode ? 'https://js.authorize.net/v1/Accept.js' : 'https://jstest.authorize.net/v1/Accept.js',
						array(),
						null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
					);
					wp_enqueue_script(
						'everest-forms-authorize-net',
						plugins_url( "/assets/js/frontend/everest-forms-authorize-net{$suffix}.js", EVF_AUTHORIZE_NET_PLUGIN_FILE ),
						array( 'jquery' ),
						defined( 'EVF_AUTHORIZE_NET_VERSION' ) ? EVF_AUTHORIZE_NET_VERSION : '1.0.0',
						true
					);
					wp_localize_script( 'everest-forms-authorize-net', 'everest_forms_authorize_net', $an_js );
				}
				return;
			}
		}
	}
}
