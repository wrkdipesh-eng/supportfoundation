<?php
/**
 *  Landing Pages Frontend.
 *
 * @package EverestForms_Pro\Frontend;
 * @since   1.7.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * LandingPagesSettings Class.
 *
 * @since 1.7.2
 */
class EVF_LANDING_PAGE_FRONTEND {

	/**
	 * Form data.
	 *
	 * @var array
	 *
	 * @since 1.7.2
	 */
	protected $form_data;


	/**
	 * Primary class constructor.
	 */
	public function __construct() {
		add_action( 'parse_request', array( $this, 'evf_landing_page_request' ) );
		add_filter( 'everest_forms_get_multiple_forms_args', array( $this, 'manage_capability' ), 10, 2 );
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
				// Poppins Font Import
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,500;1,600&display=swap';
				break;

			case 'theme_2':
				// Forum Font Import
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Forum&display=swap';
				break;

			case 'theme_3':
				// Open Sans Font Import
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap';
				break;

			case 'theme_4':
				// Playfair Display Font Import
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap';
				break;

			case 'theme_5':
				// Poppins Font Import
				$fonts_url = 'https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,500;1,600&display=swap';
				break;

			case 'theme_6':
				// Inter Font Import
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
		return EFP_ABSPATH . 'templates/form-landing-page-templates/form-landing-page.php';
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
		$footer               = isset( $this->form_data ['settings']['everest_forms_form_landing_page_footer_text'] ) ? $this->form_data ['settings']['everest_forms_form_landing_page_footer_text'] : '';
		$enable_evf_footer    = isset( $this->form_data ['settings']['everest_forms_form_landing_page_enable_evf_footer'] ) ? $this->form_data ['settings']['everest_forms_form_landing_page_enable_evf_footer'] : false;
		$enable_evf_brand  = isset( $this->form_data ['settings']['everest_forms_form_landing_page_enable_branding'] ) ? $this->form_data ['settings']['everest_forms_form_landing_page_enable_branding'] : true;
		$footer_div           = '';

		if ( $enable_evf_footer ) {
			$footer_div .= '<div class="evf-landing-page-form-footer">';
			$footer_div .= '<p>' . esc_html__( $footer, 'everest-forms-pro' ) . '</p>';
			if ( $enable_evf_brand ) {
				$footer_div .= '<a href="https://everestforms.net/" target="_blank" class="evf-landing-page-form-footer-evf-footer">';
				$footer_div .= sprintf( '<p>%s</p>', esc_html__( 'Created with', 'everest-forms-pro' ) );
				$footer_div .= '<img src="' . plugins_url( 'assets/img/evf-footer.png', EFP_PLUGIN_FILE ) . '">';
				$footer_div .= '</a>';
			}
			$footer_div .= '</div>';

			$footer_div = apply_filters( 'everest_forms_landing_page_footer', $footer_div );
		}

		echo $footer_div;
	}
}

new EVF_LANDING_PAGE_FRONTEND();
