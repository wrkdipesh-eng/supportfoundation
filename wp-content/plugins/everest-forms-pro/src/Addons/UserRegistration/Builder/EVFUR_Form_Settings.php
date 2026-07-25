<?php
/**
 * EverestForms User Registration Form Settings
 *
 * @package EverestForms_User_Registration\Admin
 * @version 1.0.0
 */

 namespace EverestForms\Pro\Addons\UserRegistration\Builder;

use EverestForms\Pro\Addons\UserRegistration\Helper;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVFUR_Form_Settings', false ) ) {
	return new EVFUR_Form_Settings();
}

/**
 * EVFUR_Form_Settings Class.
 */
class EVFUR_Form_Settings {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'everest_forms_builder_settings_section', array( $this, 'add_settings_section' ) );
		add_action( 'everest_forms_settings_panel_content', array( $this, 'output_user_registration_settings' ) );
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
			'user-registration' => esc_html__( 'User Registration', 'everest-forms-pro' ),
		);

		return array_merge( $sections, $new_sections );
	}

	/**
	 * Output User Registration settings.
	 *
	 * @since 1.0.0
	 *
	 * @param object $object Form settings object.
	 */
	public function output_user_registration_settings( $object ) {
		$value        = isset( $object->form_data['settings']['enable_user_registration'] ) ? $object->form_data['settings']['enable_user_registration'] : 0;
		$checked      = checked( '1', $value, false );
		$hidden_class = 0 === absint( $value ) ? 'everest-forms-hidden' : '';

		$disabled = ' disabled = "disabled"';
		if ( ! empty( $object->form_data['form_fields'] ) ) {
			foreach ( $object->form_data['form_fields'] as $form_field ) {
				if ( 'email' === $form_field['type'] ) {
					$disabled = '';
				}
			}
		}
		?>
		<div class="evf-content-section evf-content-user-registration-settings">
			<div class="evf-content-section-title">
				<div class="evf-title"><?php esc_html_e( 'User Registration', 'everest-forms-pro' ); ?></div>
				<div class="evf-toggle-section">
					<label class="evf-toggle-switch">
						<input type="checkbox" name="settings[enable_user_registration]" value="1" <?php echo esc_attr( $checked . $disabled ); ?> >
						<span class="evf-toggle-switch-wrap"></span>
						<span class="evf-toggle-switch-control"></span>
					</label>
				</div>
			</div>
		<?php
		if ( 'everest-forms-hidden' === $hidden_class && empty( $disabled ) ) {
			echo '<p class="user-registration-disable-message everest-forms-notice everest-forms-notice-info">' . esc_html__( 'User registration is currently disabled. Please enable it to register users with Everest Forms.', 'everest-forms-pro' ) . '</p>';
		} elseif ( ! empty( $disabled ) ) {
			echo '<p class="user-registration-disable-message everest-forms-notice everest-forms-notice-info">' . esc_html__( 'User registration is currently disabled. Please add Email field to enable user registration.', 'everest-forms-pro' ) . '</p>';
		}
		?>
		<div class="evf-content-section-body <?php echo esc_attr( $hidden_class ); ?>">
		<?php
		everest_forms_panel_field(
			'select',
			'settings',
			'user_registration_user_login',
			$object->form_data,
			esc_html__( 'Username', 'everest-forms-pro' ),
			array(
				'field_map'   => array( 'name', 'text' ),
				'placeholder' => esc_html__( '--- Select Field ---', 'everest-forms-pro' ),
				'tooltip'     => esc_html__( 'If a username is not set or provided, it will become the user\'s email address', 'everest-forms-pro' ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'user_registration_first_name',
			$object->form_data,
			esc_html__( 'First Name', 'everest-forms-pro' ),
			array(
				'field_map'   => array( 'name', 'text', 'first-name' ),
				'placeholder' => esc_html__( '--- Select Field ---', 'everest-forms-pro' ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'user_registration_last_name',
			$object->form_data,
			esc_html__( 'Last Name', 'everest-forms-pro' ),
			array(
				'field_map'   => array( 'last-name', 'name', 'text' ),
				'placeholder' => esc_html__( '--- Select Field ---', 'everest-forms-pro' ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'user_registration_display_name',
			$object->form_data,
			esc_html__( 'Display Name', 'everest-forms-pro' ),
			array(
				'field_map'   => array( 'name', 'text' ),
				'placeholder' => esc_html__( '--- Select Field ---', 'everest-forms-pro' ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'user_registration_user_pass',
			$object->form_data,
			esc_html__( 'Password', 'everest-forms-pro' ),
			array(
				'field_map'   => array( 'password' ),
				'placeholder' => esc_html__( 'Auto generate', 'everest-forms-pro' ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'user_registration_user_email',
			$object->form_data,
			esc_html__( 'Email', 'everest-forms-pro' ),
			array(
				'field_map' => array( 'email' ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'user_registration_user_url',
			$object->form_data,
			esc_html__( 'Website', 'everest-forms-pro' ),
			array(
				'field_map'   => array( 'url' ),
				'placeholder' => esc_html__( '--- Select Field ---', 'everest-forms-pro' ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'user_registration_description',
			$object->form_data,
			esc_html__( 'Biographical Info', 'everest-forms-pro' ),
			array(
				'field_map'   => array( 'textarea' ),
				'placeholder' => esc_html__( '--- Select Field ---', 'everest-forms-pro' ),
			)
		);
		$editable_user_roles = array_reverse( get_editable_roles() );
		$user_roles_options  = array();
		foreach ( $editable_user_roles as $user_role => $details ) {
			$user_roles_options[ $user_role ] = translate_user_role( $details['name'] );
		}
		everest_forms_panel_field(
			'select',
			'settings',
			'user_registration_role',
			$object->form_data,
			esc_html__( 'User Role', 'everest-forms-pro' ),
			array(
				'default' => get_option( 'default_role' ),
				'options' => $user_roles_options,
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'user_registration_login_options',
			$object->form_data,
			esc_html__( 'User Login Options', 'everest-forms-pro' ),
			array(
				'default' => 'auto_login',
				'options' => array(
					'auto_login'         => esc_html__( 'Auto Login', 'everest-forms-pro' ),
					'admin_approval'     => esc_html__( 'Admin approval', 'everest-forms-pro' ),
					'email_confirmation' => esc_html__( 'Email confirmation', 'everest-forms-pro' ),
				),
			)
		);
		everest_forms_panel_field(
			'toggle',
			'settings',
			'user_registration_email_user',
			$object->form_data,
			esc_html__( 'Send email to the user containing account information', 'everest-forms-pro' )
		);
		everest_forms_panel_field(
			'toggle',
			'settings',
			'user_registration_email_admin',
			$object->form_data,
			esc_html__( 'Send email to the admin', 'everest-forms-pro' )
		);
		$option_pages = array();
		$pages        = get_pages();
		foreach ( $pages as $page ) {
			$depth                     = count( $page->ancestors );
			$option_pages[ $page->ID ] = str_repeat( '-', $depth ) . ' ' . $page->post_title;
		}
		everest_forms_panel_field(
			'select',
			'settings',
			'registration_activation_confirmation',
			$object->form_data,
			esc_html__( 'User Activation Confirmation Page', 'everest-forms-pro' ),
			array(
				'placeholder' => esc_html__( 'Home page', 'everest-forms-pro' ),
				'options'     => $option_pages,
				'tooltip'     => esc_html__( 'Choose the page to redirect after user email verification.', 'everest-forms-pro' ),
			)
		);

		$social_networks  = Helper::everest_forms_social_networks();
		$enabled_networks = array();

		foreach ( $social_networks as $network_key => $network_data ) {
			if ( 'yes' === get_option( 'everest_forms_social_setting_enable_social_registration' ) && 'yes' === get_option( $network_data['enable_id'] ) ) {
				$enabled_networks[ $network_key ] = ucfirst( $network_key );
			}
		}

		/**
		 * Social connect option.
		 *
		 * @since 1.7.9
		 */
		everest_forms_panel_field(
			'select',
			'settings',
			'social_login_network_select',
			$object->form_data,
			__( 'Social Connect Option', 'everest-forms-pro' ),
			array(
				'default'     => '',
				'tooltip'     => __( 'Choose social connect to use.', 'everest-forms-pro' ),
				'input_class' => 'evf-enhanced-select',
				'options'     => $enabled_networks,
				'multiple'    => true,
			)
		);
		?>
			<div class="everest-forms-field-map-table everest-forms-addable-list everest-forms-border-container everest-forms-panel-field-meta">
				<h4 class="everest-forms-border-container-title"><?php esc_html_e( 'Custom User Meta', 'everest-forms-pro' ); ?></h4>
					<ul>
					<?php
					$fields = evf_get_form_fields( $object->form_data );
					$meta   = ! empty( $object->form_data['settings']['registration_meta'] ) ? $object->form_data['settings']['registration_meta'] : array( false );
					foreach ( $meta as $meta_key => $meta_field ) :
						$key  = false !== $meta_field ? preg_replace( '/[^a-zA-Z0-9_\-]/', '', $meta_key ) : '';
						$name = ! empty( $key ) ? 'settings[registration_meta][' . $key . ']' : '';
						?>
						<li>
							<span class="key">
								<input class="widefat key-source" type="text" value="<?php echo esc_attr( $key ); ?>" placeholder="<?php esc_attr_e( 'Enter meta key...', 'everest-forms-pro' ); ?>">
							</span>
							<span class="field">
								<select class="widefat key-destination everest-forms-field-map-select" data-name="settings[registration_meta][{source}]" name="<?php echo esc_attr( $name ); ?>" data-field-map-allowed="all-fields">
									<option value=""><?php esc_html_e( '--- Select Field ---', 'everest-forms-pro' ); ?></option>
									<?php
									if ( ! empty( $fields ) ) {
										foreach ( $fields as $id => $field ) {
											printf( '<option value="%s" %s>%s</option>', esc_attr( $field['id'] ), selected( $meta_field, $id, false ), esc_html( $field['label'] ) );
										}
									}
									?>
								</select>
							</span>
							<span class="actions">
								<a class="add" href="#"><i class="dashicons dashicons-plus"></i></a>
								<a class="remove" href="#"><i class="dashicons dashicons-minus"></i></a>
							</span>
						</li>
					<?php endforeach; ?>
					</ul>
			</div>
		</div>
		</div>
		<?php
	}

}
