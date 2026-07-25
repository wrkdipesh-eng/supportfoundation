<?php
/**
 * QRGenerator Settings.
 *
 * @package EverestForms_Pro\QRGenerator
 * @since   1.7.9
 */

namespace EverestForms\Pro\Addons\QRGenerator\Builder;

use chillerlan\QRCode\QRCode;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 *
 * @since 1.7.9
 */
class Settings {

	/**
	 * Constructor.
	 *
	 * @since 1.7.9
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_qr_generator_admin_scripts' ) );
		add_filter( 'everest_forms_builder_settings_section', array( $this, 'add_settings_section' ) );
		add_action( 'everest_forms_settings_panel_content', array( $this, 'output_qr_generator_settings' ) );
		add_filter( 'everest_forms_save_form_data', array( $this, 'create_the_landing_page_slug' ), 10, 3 );
	}

	/**
	 * Load scripts.
	 *
	 * @since 1.7.9
	 */
	public function load_qr_generator_admin_scripts() {
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Enqueue Scripts.
		wp_register_script( 'everest-forms-qr-generator-script', plugins_url( "src/Addons/QRGenerator/assets/js/admin{$suffix}.js", EFP_PLUGIN_FILE ), array(), EFP_VERSION, true );
		if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
			wp_enqueue_script( 'everest-forms-qr-generator-script' );
			wp_localize_script(
				'everest-forms-qr-generator-script',
				'qr_generator',
				array(
					'qr-generator' => esc_html__( 'QRGenerator is currently disabled . Please enable it to activate this feature with Everest Forms . ', 'everest-forms-pro' ),
					'qr_title'     => esc_html__( 'Scan QR Code', 'everest-forms-pro' ),
					'qr_icon' => plugins_url( 'src/Addons/QRGenerator/assets/images/qr-icon.png', EFP_PLUGIN_FILE ),
				)
			);
		}
		wp_enqueue_style( 'everest-forms-qr-generator-admin-builder', plugins_url( 'src/Addons/QRGenerator/assets/css/admin/builder.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION );
	}

	/**
	 * Register settings section.
	 *
	 * @since 1.7.9
	 *
	 * @param  array $sections Settings section.
	 * @return array
	 */
	public function add_settings_section( $sections ) {
		$new_sections = array(
			'qr_generator' => esc_html__( 'QR Generator', 'everest-forms-pro' ),
		);

		return array_merge( $sections, $new_sections );
	}

	/**
	 * Output User Registration settings.
	 *
	 * @since 1.7.9
	 *
	 * @param object $object Form settings object.
	 */
	public function output_qr_generator_settings( $object ) {
		$settings     = isset( $object->form_data['settings'] ) ? $object->form_data['settings'] : array();
		$hidden_class = ! isset( $settings['qr_generator'] ) ? 'everest-forms-hidden' : ( isset( $settings['qr_generator']['evf_enable_public_link'] ) && 1 != (int) $settings['qr_generator']['evf_enable_public_link'] ? 'everest-forms-hidden' : '' );

		echo "<div class = 'evf-content-section evf-content-qr_generator-settings'>";
		echo '<div class="evf-content-section-title">';
		esc_html_e( 'QR Generator', 'everest-forms' );
		echo '</div>';

		everest_forms_panel_field(
			'toggle',
			'settings[qr_generator]',
			'evf_enable_public_link',
			$object->form_data,
			esc_html__( 'Enable public link and QR', 'everest-forms' ),
			array(
				'default' => isset( $settings['qr_generator']['evf_enable_public_link'] ) ? $settings['qr_generator']['evf_enable_public_link'] : 0,
			)
		);
		echo '<div class="evf-content-qr_generator-settings-inner ' . esc_attr( $hidden_class ) . '">';

		if ( ! isset( $settings['qr_generator']['public_link_id'] ) ) {
			$unique_id = substr( uniqid(), 8, 4 );
		}
		$default_public_link_id = isset( $settings['qr_generator']['public_link_id'] ) ? $settings['qr_generator']['public_link_id'] : sanitize_text_field( $_GET['form_id'] ) . $unique_id;
		$public_link            = get_site_url() . '/everest-forms/' . $default_public_link_id;
		?>

		<label>Your public link</label>
		<div class="everest_forms_qr_generator_wrapper">
			<span class="shortcode evf-shortcode-field">
				<input type="text" onfocus="this.select();" readonly="readonly" value="<?php echo esc_attr( $public_link ); ?> " class="large-text code" name="settings[qr_generator][public_link]">
				<button class="button evf-copy-shortcode help_tip" type="button" href="#" data-tip="<?php esc_attr_e( 'Copy Public Link!', 'everest-forms' ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'everest-forms' ); ?>">
					<span class="dashicons dashicons-admin-page"></span>
				</button>
			</span>
			<button id="everest_forms_generate_qr_btn">Generate QR</button>
		</div>

		<input type="hidden" value="<?php echo esc_attr( $default_public_link_id ); ?>" name="settings[qr_generator][public_link_id]" readonly/>
		<?php
			$qr_code = ( new QRCode() )->render( $public_link );
			echo '<input id="everest_forms_public_link_qr" type="hidden" value="' . $qr_code . '"/>';
			echo '</div>';
			echo '</div>';
	}

	/**
	 * Pre process data before saving it in form_data when editing form.
	 *
	 * @since 1.7.9
	 *
	 * @param array $data Data from $_POST.
	 * @param int   $form_id Data from_id.
	 * @param array $form_data Form Data.
	 */
	public function create_the_landing_page_slug( $data, $form_id, $form_data ) {
		if ( empty( $form_data['settings']['qr_generator']['evf_enable_public_link'] ) ) {
			return $data;
		}
		$public_link    = ! empty( $form_data['settings']['qr_generator']['public_link'] ) ? $form_data['settings']['qr_generator']['public_link'] : '';
		$public_link_id = ! empty( $form_data['settings']['qr_generator']['public_link_id'] ) ? $form_data['settings']['qr_generator']['public_link_id'] : '';

		if ( empty( $form_id ) ) {
			return $data;
		}

		$result = wp_update_post(
			array(
				'ID'        => $form_id,
				'post_name' => $public_link_id,
			)
		);

		if ( $result !== $form_id ) {
			return $data;
		}

		return $data;
	}
}
