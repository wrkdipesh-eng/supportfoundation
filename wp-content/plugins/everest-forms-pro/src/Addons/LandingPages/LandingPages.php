<?php
/**
 * Main plugin class.
 *
 * @package EverestForms\WooCommerce
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\LandingPages;

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class LandingPages {


	/**
	 * Plugin Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'everest_forms_builder_settings_section', array( $this, 'add_settings_section' ) );
		add_action( 'everest_forms_settings_panel_content', array( $this, 'output_landing_pages_settings' ) );
		add_filter( 'everest_forms_save_form_data', array( $this, 'create_the_landing_page_slug' ), 10, 3 );
		add_action( 'parse_request', array( $this, 'evf_landing_page_request' ) );
		add_filter( 'everest_forms_get_multiple_forms_args', array( $this, 'manage_capability' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.9.3
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Admin scripts for EVF builder page.
		if ( 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_register_style( 'everest-forms-pro-landing-pages-admin-css', plugins_url( 'src/Addons/LandingPages/assets/css/admin.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION );
			wp_enqueue_style( 'everest-forms-pro-landing-pages-admin-css' );
		}

		wp_enqueue_script( 'everest-forms-pro-landing-pages-admin', plugins_url( 'src/Addons/LandingPages/assets/js/frontend/evf-landing-page-frontend' . $suffix . '.js', EFP_PLUGIN_FILE ), array( 'jquery' ), EFP_VERSION, true );
	}
	/**
	 * Register settings section.
	 *
	 * @since 1.0.0
	 * @param  array $sections Settings section.
	 * @return array
	 */
	public function add_settings_section( $sections ) {
		$new_sections = array(
			'form-landing-pages' => esc_html__( 'Form Landing Pages', 'everest-forms-pro' ),
		);

		return array_merge( $sections, $new_sections );
	}


	/**
	 * Output Form Pages Settings.
	 *
	 * @since 1.7.2
	 *
	 * @param object $object Form settings object.
	 */
	public function output_landing_pages_settings( $object ) {
		$settings = isset( $object->form_data['settings'] ) ? $object->form_data['settings'] : array();
		?>
		<div class="evf-content-section evf-content-form-landing-pages-settings">
		<div class="evf-content-section-title"><?php esc_html_e( 'Form Landing Page', 'everest-forms-pro' ); ?>
				<div class="evf-landing-page-content-header-button">
					<?php
					echo '<a href="' . esc_url( home_url( $object->form->post_name ) ) . '" class="evf-button evf-content-general-form-preview" id="everest-forms-form-landing-page-preview-button" target="_blank">' . esc_html__( 'Preview Landing Form Page', 'everest-forms-pro' ) . '</a>';
					?>
				</div>
		</div>
			<?php
					everest_forms_panel_field(
						'toggle',
						'settings',
						'everest_forms_enable_form_landing_pages',
						$object->form_data,
						esc_html__( 'Enable Form Page Mode.', 'everest-forms-pro' ),
						array(
							'default' => isset( $settings['everest_forms_enable_form_landing_pages'] ) ? $settings['everest_forms_enable_form_landing_pages'] : 0,
						)
					);
			?>
			<div class="evf-content-section-body evf-content-form-landing-pages-settings-body">
				<?php
				$footer_enable = isset( $settings['everest_forms_form_landing_page_enable_evf_footer'] ) ? $settings['everest_forms_form_landing_page_enable_evf_footer'] : 0;
					everest_forms_panel_field(
						'text',
						'settings',
						'everest_forms_form_landing_page_form_url',
						$object->form_data,
						__( 'Form URL', 'everest-forms-pro' ),
						array(
							'default'     => isset( $object->form->post_name ) ? esc_html( urldecode( $object->form->post_name ) ) : '',
							'placeholder' => 'Landing Pages Forms URL',
							'after_label' => '<div class="everest-forms-form-landing-page-form-url"><span class="everest-forms-home-page-url">' . trailingslashit( home_url() ) . '</span>',
							'after'       => '</div>',
							'class'       => 'widefat',
						)
					);

					everest_forms_panel_field(
						'image',
						'settings',
						'everest_forms_form_landing_page_header_logo',
						$object->form_data,
						__( 'Logo', 'everest-forms-pro' ),
						array(
							'default' => isset( $settings['everest_forms_form_landing_page_header_logo'] ) ? $settings['everest_forms_form_landing_page_header_logo'] : '',
							'image'   => array(
								'alt'         => 'Upload Logo',
								'button-text' => 'Upload Logo',
							),
						)
					);

					everest_forms_panel_field(
						'radio-image',
						'settings',
						'everest_forms_form_landing_page_color_theme',
						$object->form_data,
						__( 'Form Theme', 'everest-forms-pro' ),
						array(
							'default' => isset( $settings['everest_forms_form_landing_page_color_theme'] ) ? $settings['everest_forms_form_landing_page_color_theme'] : 'theme_1',
							'options' => array(
								'theme_1' => array(
									'image' => plugins_url(
										'src/Addons/LandingPages/assets/images/form_themes/form-landing-page-blue.svg',
										EFP_PLUGIN_FILE
									),
								),
								'theme_2' => array(
									'image' => plugins_url(
										'src/Addons/LandingPages/assets/images/form_themes/form-landing-page-orange.svg',
										EFP_PLUGIN_FILE
									),
								),
								'theme_3' => array(
									'image' => plugins_url(
										'src/Addons/LandingPages/assets/images/form_themes/form-landing-page-dark-blue.svg',
										EFP_PLUGIN_FILE
									),
								),
								'theme_4' => array(
									'image' => plugins_url(
										'src/Addons/LandingPages/assets/images/form_themes/form-landing-page-pink.svg',
										EFP_PLUGIN_FILE
									),
								),
								'theme_5' => array(
									'image' => plugins_url(
										'src/Addons/LandingPages/assets/images/form_themes/form-landing-page-green.svg',
										EFP_PLUGIN_FILE
									),
								),
								'theme_6' => array(
									'image' => plugins_url(
										'src/Addons/LandingPages/assets/images/form_themes/form-landing-page-red.svg',
										EFP_PLUGIN_FILE
									),
								),
							),
						)
					);

					echo '<hr color="#e9e9e9">';

					everest_forms_panel_field(
						'toggle',
						'settings',
						'everest_forms_form_landing_page_enable_form_header',
						$object->form_data,
						esc_html__( 'Enable Form Header.', 'everest-forms-pro' ),
						array(
							'default' => isset( $settings['everest_forms_form_landing_page_enable_form_header'] ) ? $settings['everest_forms_form_landing_page_enable_form_header'] : 0,
						)
					);

				echo "<div id='evf-landing-page-form-header-content'>";
				echo "<p id='evf-landing-page-form-header-title'>Form Header Option</p>";
				echo '<hr color="#e9e9e9">';
					everest_forms_panel_field(
						'text',
						'settings',
						'everest_forms_form_landing_page_form_page_title',
						$object->form_data,
						__( 'Form Page Title', 'everest-forms-pro' ),
						array(
							'default'     => isset( $settings['everest_forms_form_landing_page_form_page_title'] ) ? $settings['everest_forms_form_landing_page_form_page_title'] : '',
							'placeholder' => 'Form Title',
							'tooltip'     => esc_html__( 'Enter the Title of the Form to be displayed.', 'everest-forms-pro' ),
						)
					);

					everest_forms_panel_field(
						'tinymce',
						'settings',
						'everest_forms_form_landing_page_form_page_message',
						$object->form_data,
						__( 'Message', 'everest-forms-pro' ),
						array(
							'default'     => isset( $settings['everest_forms_form_landing_page_form_page_message'] ) ? $settings['everest_forms_form_landing_page_form_page_message'] : '',
							'placeholder' => 'Message to be Displayed.',
							'tooltip'     => esc_html__( 'Enter the Message to be displayed.', 'everest-forms-pro' ),
						)
					);

					echo '</div><hr color="#e9e9e9">';

					everest_forms_panel_field(
						'toggle',
						'settings',
						'everest_forms_form_landing_page_enable_evf_footer',
						$object->form_data,
						esc_html__( 'Enable Everest Forms Footer', 'everest-forms-pro' ),
						array(
							'default' => isset( $settings['everest_forms_form_landing_page_enable_evf_footer'] ) ? $settings['everest_forms_form_landing_page_enable_evf_footer'] : 0,
						)
					);

					echo '<div id="evf-landing-page-form-footer-content">';
					echo "<p id='evf-landing-page-form-footer-title'>Form Footer Option</p>";
					echo '<hr color="#e9e9e9">';
					everest_forms_panel_field(
						'text',
						'settings',
						'everest_forms_form_landing_page_footer_text',
						$object->form_data,
						__( 'Footer Text', 'everest-forms-pro' ),
						array(
							'default'     => isset( $settings['everest_forms_form_landing_page_footer_text'] ) ? $settings['everest_forms_form_landing_page_footer_text'] : '',
							'placeholder' => 'Footer Text to be Displayed',
							'tooltip'     => esc_html__( 'Enter the Footer text to be displayed.', 'everest-forms-pro' ),
						)
					);

						everest_forms_panel_field(
							'toggle',
							'settings',
							'everest_forms_form_landing_page_enable_branding',
							$object->form_data,
							esc_html__( 'Enable Everest Forms Branding', 'everest-forms-pro' ),
							array(
								'default' => isset( $settings['everest_forms_form_landing_page_enable_branding'] ) ? $settings['everest_forms_form_landing_page_enable_branding'] : 1,
							)
						);

						everest_forms_panel_field(
							'toggle',
							'settings',
							'everest_forms_form_landing_page_enable_footer_background',
							$object->form_data,
							esc_html__( 'Enable Footer Background', 'everest-forms-pro' ),
							array(
								'default' => isset( $settings['everest_forms_form_landing_page_enable_footer_background'] ) ? $settings['everest_forms_form_landing_page_enable_footer_background'] : 0,
							)
						);
					echo '</div>';
				?>
			</div>
		</div>
		<?php
	}


	/**
	 * Pre process data before saving it in form_data when editing form.
	 *
	 * @since 1.7.2
	 *
	 * @param array $data Data from $_POST.
	 * @param int   $form_id Data from_id.
	 * @param array $form_data Form Data.
	 */
	public function create_the_landing_page_slug( $data, $form_id, $form_data ) {

		if ( empty( $form_data['settings']['everest_forms_enable_form_landing_pages'] ) ) {
			return $data;
		}

		$form_slug = ! empty( $form_data['settings']['everest_forms_form_landing_page_form_url'] ) ? sanitize_title( $form_data['settings']['everest_forms_form_landing_page_form_url'] ) : '';

		if ( empty( $form_id ) ) {
			return $data;
		}

		if ( empty( $form_slug ) ) {
			$form_name = evf()->form->get( $form_id );

			// Give the original if the user input is empty.
			$data['landing_pages'] = array(
				'slug' => isset( $form_name->post_name ) ? $form_name->post_name : '',
				'url'  => esc_url( home_url( isset( $form_name->post_name ) ? $form_name->post_name : '' ) ),
			);

			return $data;
		}

		$unique_slug = $this->make_unique_slug( $form_slug, $form_id );

		$result = wp_update_post(
			array(
				'ID'        => $form_id,
				'post_name' => $unique_slug,
			)
		);

		if ( $result !== $form_id ) {
			return $data;
		}

		$data['landing_pages'] = array(
			'slug' => $unique_slug,
			'url'  => esc_url( home_url( $unique_slug ) ),
		);

		return $data;
	}

	/**
	 * Check if the slug is unique.
	 *
	 * @since 1.7.2
	 *
	 * @param string $slug    Slug to check.
	 * @param int    $post_id Post ID.
	 *
	 * @return string
	 */
	public function make_unique_slug( $slug, $post_id ) {

		global $wpdb;

		$check_slug = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND ID != %d LIMIT 1";
		$post_name  = $wpdb->get_var( $wpdb->prepare( $check_slug, $slug, $post_id ) );

		if ( null === $post_name ) {
			return $slug;
		}

		$suffix = 2;

		do {
			$alternate_post_name = _truncate_post_slug( $slug, 200 - ( \strlen( $suffix ) + 1 ) ) . "-$suffix";
			$post_name           = $wpdb->get_var( $wpdb->prepare( $check_slug, $alternate_post_name, $post_id ) );
			++$suffix;
		} while ( $post_name );

		return $alternate_post_name;
	}

	/**
	 * Manage Capability to show form landing page.
	 *
	 * @since 1.7.2
	 *
	 * @param  array  $args Arguments.
	 * @param  string $content_only Content Only.
	 */
	public function manage_capability( $args, $content_only ) {
		$args['cap'] = 'everest_forms_pro_view_landing_page';
		return $args;
	}

	/**
	 * Handle the request.
	 *
	 * @since 1.7.2
	 *
	 * @param \WP $wp WP instance.
	 */
	public function evf_landing_page_request( $wp ) {

		if ( ! empty( $wp->query_vars['name'] ) ) {
			$request = $wp->query_vars['name'];
		}

		if ( empty( $request ) && ! empty( $wp->query_vars['pagename'] ) ) {
			$request = $wp->query_vars['pagename'];
		}

		if ( empty( $request ) ) {
			$request = ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$request = ! empty( $request ) ? sanitize_key( wp_parse_url( $request, PHP_URL_PATH ) ) : '';
		}

		$forms = ! empty( $request ) ? evf()->form->get( '', array( 'name' => $request ) ) : array();

		$form = ! empty( $forms[0] ) ? $forms[0] : null;

		if ( ! isset( $form->post_type ) || 'everest_form' !== $form->post_type ) {
			return;
		}

		$form_data = evf_decode( $form->post_content );

		if ( empty( $form_data['settings']['everest_forms_enable_form_landing_pages'] ) ) {
			return;
		}

		$this->form_data = $form_data;

		if ( ! empty( $wp->query_vars['pagename'] ) ) {
			$wp->query_vars['name'] = $wp->query_vars['pagename'];
			unset( $wp->query_vars['pagename'] );
		}

		if ( empty( $wp->query_vars['name'] ) ) {
			$wp->query_vars['name'] = $request;
		}

		$wp->query_vars['post_type'] = 'everest_form';

		unset( $wp->query_vars['error'] );

		$this->init();
	}

	/**
	 * Initialize.
	 *
	 * @since 1.7.2
	 */
	public function init() {
		add_filter( 'template_include', array( $this, 'form_landing_page_template' ), PHP_INT_MAX );
		add_action( 'everest_forms_form_landing_page_logo', array( $this, 'frontend_logo_html' ) );
		add_action( 'everest_forms_form_landing_page_content_before', array( $this, 'frontend_title_message_html' ) );
		add_action( 'everest_forms_form_landing_page_footer', array( $this, 'footer_html' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ) );
		add_filter( 'everest_forms_frontend_form_data', array( $this, 'ignore_field' ) );
	}


	/**
	 * Form Data.
	 *
	 * @since 1.7.2
	 *
	 * @param array $form_data Form data.
	 */
	public function ignore_field( $form_data ) {
		$exclude = array( 'title', 'html', 'captcha', 'divider', 'reset', 'hidden', 'repeater-fields' );
		foreach ( $form_data['form_fields'] as $id => $field ) {
			if ( in_array( $field['type'], $exclude, true ) || isset( $field['repeater-fields'] ) && 'yes' === $field['repeater-fields'] ) {
				unset( $form_data['form_fields'][ $id ] );
			}
		}
		return $form_data;
	}

	/**
	 * Frontend Enqueue scripts.
	 *
	 * @since 1.7.2
	 */
	public function frontend_enqueue_scripts() {
		$landing_page_theme = isset( $this->form_data ['settings']['everest_forms_form_landing_page_color_theme'] ) ? $this->form_data ['settings']['everest_forms_form_landing_page_color_theme'] : 'theme_1';

		if ( ! empty( $landing_page_theme ) ) {
			wp_enqueue_style( 'everest-forms-form-landing-page-theme-style', plugins_url( "/assets/css/{$landing_page_theme}.css", EFP_PLUGIN_FILE ), array() );

			$this->load_theme_fonts( $landing_page_theme );
		}
	}

	/**
	 * Enqueue the theme font files.
	 *
	 * @param [string] $theme Selected theme id.
	 * @return void
	 */
	private function load_theme_fonts( $theme ) {

		switch ( $theme ) {
			case 'theme_1':
				// Poppins Font Import.
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,500;1,600&display=swap';
				break;

			case 'theme_2':
				// Forum Font Import.
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Forum&display=swap';
				break;

			case 'theme_3':
				// Open Sans Font Import.
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap';
				break;

			case 'theme_4':
				// Playfair Display Font Import.
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap';
				break;

			case 'theme_5':
				// Poppins Font Import.
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,500;1,600&display=swap';
				break;

			case 'theme_6':
				// Inter Font Import.
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap';
				break;
		}

		$fonts_url = evf_maybe_get_local_font_url( $fonts_url );

		wp_enqueue_style( 'everest-forms-form-landing-page-theme-style-fonts', esc_url( $fonts_url ), array() );
	}

	/**
	 * Form Landing Page Template.
	 *
	 * @since 1.7.2
	 */
	public function form_landing_page_template() {
		return EFP_ABSPATH . 'src/Addons/LandingPages/templates/form-landing-page-templates/form-landing-page.php';
	}

	/**
	 * Form Landing Page Template Logo.
	 *
	 * @since 1.7.2
	 */
	public function frontend_logo_html() {
		$logo = ( isset( $this->form_data ['settings']['everest_forms_form_landing_page_header_logo'] ) ) ? $this->form_data ['settings']['everest_forms_form_landing_page_header_logo'] : '';

		if ( ! empty( $logo ) ) {
			echo '<div class="evf-landing-page-form-logo">';
			echo '<img src="' . esc_attr( $logo ) . '" class="evf-landing-page-form-logo-image">';
			echo '</div>';
		}
	}

	/**
	 * Welcome Message Template.
	 *
	 * @since 1.7.2
	 */
	public function frontend_title_message_html() {
		$title              = ( isset( $this->form_data ['settings']['everest_forms_form_landing_page_form_page_title'] ) && ! empty( $this->form_data ['settings']['everest_forms_form_landing_page_form_page_title'] ) ) ? $this->form_data ['settings']['everest_forms_form_landing_page_form_page_title'] : 'Everest Form';
		$message            = ( isset( $this->form_data ['settings']['everest_forms_form_landing_page_form_page_message'] ) && ! empty( $this->form_data ['settings']['everest_forms_form_landing_page_form_page_message'] ) ) ? $this->form_data ['settings']['everest_forms_form_landing_page_form_page_message'] : 'Please feel free to contact us!';
		$enable_form_header = isset( $this->form_data ['settings']['everest_forms_form_landing_page_enable_form_header'] ) ? $this->form_data ['settings']['everest_forms_form_landing_page_enable_form_header'] : false;

		if ( $enable_form_header ) {
			echo '<div class="evf-landing-page-form-content-header">';
			echo '<h3>' . esc_html__( $title, 'everest-forms-pro' ) . '</h3>';
			echo '<p>' . __( $message, 'everest-forms-pro' ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Footer HTML.
	 *
	 * @since 1.7.2
	 */
	public function footer_html() {
		$footer                   = isset( $this->form_data ['settings']['everest_forms_form_landing_page_footer_text'] ) ? $this->form_data ['settings']['everest_forms_form_landing_page_footer_text'] : '';
		$enable_evf_footer        = isset( $this->form_data ['settings']['everest_forms_form_landing_page_enable_evf_footer'] ) ? $this->form_data ['settings']['everest_forms_form_landing_page_enable_evf_footer'] : false;
		$enable_evf_brand         = isset( $this->form_data ['settings']['everest_forms_form_landing_page_enable_branding'] ) ? $this->form_data ['settings']['everest_forms_form_landing_page_enable_branding'] : true;
		$enable_footer_background = isset( $this->form_data ['settings']['everest_forms_form_landing_page_enable_footer_background'] ) ? $this->form_data ['settings']['everest_forms_form_landing_page_enable_footer_background'] : false;
		$footer_class             = evf_string_to_bool( $enable_footer_background ) ? 'evf-landing-page-form-footer-background' : '';
		$img_path                 = evf_string_to_bool( $enable_footer_background ) ? plugins_url( 'src/Addons/LandingPages/assets/images/evf-white-brand.png', EFP_PLUGIN_FILE ) : plugins_url( 'assets/img/evf-footer.png', EFP_PLUGIN_FILE );
		$footer_div               = '';

		if ( $enable_evf_footer ) {
			$footer_div .= '<div class="evf-landing-page-form-footer ' . esc_attr( $footer_class ) . '">';
			$footer_div .= '<p>' . esc_html__( $footer, 'everest-forms-pro' ) . '</p>';
			if ( $enable_evf_brand ) {
				$footer_div .= '<a href="https://everestforms.net/" target="_blank" class="evf-landing-page-form-footer-evf-footer">';
				$footer_div .= sprintf( '<p>%s</p>', esc_html__( 'Created with', 'everest-forms-pro' ) );
				$footer_div .= '<img src="' . esc_attr( $img_path ) . '">';
				$footer_div .= '</a>';
			}
			$footer_div .= '</div>';

			$footer_div = apply_filters( 'everest_forms_landing_page_footer', $footer_div );
		}

		echo $footer_div;
	}
}
