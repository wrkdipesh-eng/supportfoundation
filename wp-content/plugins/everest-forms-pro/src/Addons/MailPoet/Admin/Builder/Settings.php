<?php
/**
 * MailPoet Settings.
 *
 * @package EverestForms\MailPoet\Admin\Builder
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\MailPoet\Admin\Builder;

use EverestForms\Pro\Addons\MailPoet\Admin\Integration\MailPoetIntegration;

defined( 'ABSPATH' ) || exit;


/**
 * MailPoet Integration.
 */
class Settings {
	/**
	 * Integration ID.
	 *
	 * @since 1.0.0
	 */
	protected $id;
	/**
	 * Integration ID.
	 *
	 * @since 1.0.0
	 */
	protected $icon;
	/**
	 * Integration ID.
	 *
	 * @since 1.0.0
	 */
	protected $name;
	/**
	 * Constructor.
	 */
	public function __construct() {
		$integration_instance = new MailPoetIntegration();
		$this->id             = $integration_instance->id;
		$this->icon           = $integration_instance->icon;
		$this->name           = $integration_instance->method_title;

		add_filter( 'everest_forms_available_integrations', array( $this, 'register_mailpoet_integration' ) );
		add_action( 'everest_forms_providers_panel_content', array( $this, 'mailpoet_output_panel_content' ) );
	}
	/**
	 * Register mailpoet integration.
	 *
	 * @param  array $integrations List of integrations.
	 * @return array of registered integrations.
	 */
	public function register_mailpoet_integration( $integrations ) {

		$integrations[ $this->id ] = array(
			'id'   => $this->id,
			'icon' => $this->icon,
			'name' => $this->name,
		);

		return $integrations;
	}

	public function mailpoet_output_panel_content() {
		$is_configured = MailPoetIntegration::is_configured();

		?>
		<div class="evf-panel-content-section evf-panel-content-section-<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>-provider">
			<div class="evf-content-section-title"><?php echo esc_html( $this->name ); ?></div>
			<div class="evf-provider-connections-wrap evf-clear">
				<div class="evf-provider-connections">
				<?php
				if ( evf_string_to_bool( $is_configured ) ) {
					$gloabal_connection_status = get_option( 'everest_forms_integrations_' . $this->id, false );
					if ( evf_string_to_bool( $gloabal_connection_status ) ) {
						$this->output_integration_connection();
					} else {
						printf( esc_html__( 'Please connect the mailpoet from the global setting.', 'everest-forms-pro' ) );
					}
				} else {
					$notice = MailPoetIntegration::configuration_setup_message();
					echo '<div class="everest-form-add-connection-notice">' . $notice . '</div>';
				}
				?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get integration ID
	 *
	 * @return array Integration stored data.
	 */
	private function get_integration() {
		$integrations         = get_option( 'everest_forms_integrations', array() );
		$integration_instance = new MailPoetIntegration();

		return in_array( $integration_instance->id, array_keys( $integrations ), true ) ? $integrations[ $integration_instance->id ] : array();
	}

	/**
	 * Get form data
	 *
	 * @return array form data.
	 */
	private function form_data() {
		$form_data = array();

		if ( ! empty( $_GET['form_id'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_data = EVF()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return $form_data;
	}
	/**
	 * Get MailPoet lists.
	 *
	 * @since 1.0.0
	 */
	protected function get_mailpoet_lists() {
		if ( ! class_exists( \MailPoet\API\API::class ) ) {
			return false;
		}
		$lists           = \MailPoet\API\API::MP( 'v1' )->getLists();
		$formatted_lists = array();
		foreach ( $lists as $list ) {
			$formatted_lists[ $list['id'] ] = $list['name'];
		}

		return $formatted_lists;
	}
	/**
	 * Output for mailpoet connection.
	 *
	 * @since 1.0.0
	 */
	protected function output_integration_connection() {
		$form_data   = $this->form_data();
		$integration = $this->get_integration();
		echo '<div class="evf-mailpoet-builder-wrapper">';
		echo '<div class="evf-mailpoet-enabler">';
		everest_forms_panel_field(
			'toggle',
			'settings',
			'enable_mailpoet',
			$form_data,
			__( 'Enable MailPoet', 'everest-forms-pro' ),
			array(
				'default' => '0',
				'tooltip' => esc_html__( 'Enable to sync the entry.', 'everest-forms-pro' ),
			)
		);
		echo '</div>';
		echo '<div class="evf-mailpoet-settings">';
		everest_forms_panel_field(
			'text',
			'settings[mailpoet]',
			'feed_title',
			$form_data,
			esc_html__( 'Feed Name', 'everest-forms-pro' ),
			array(
				'default' => isset( $form_data['settings']['mailpoet']['feed_title'] ) ? $form_data['settings']['mailpoet']['feed_title'] : esc_html__( 'MailPoet Integration Feed', 'everest-forms-pro' ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings[mailpoet]',
			'list_id',
			$form_data,
			esc_html__( 'MailPoet List', 'everest-forms-pro' ),
			array(
				'default' => isset( $form_data['settings']['mailpoet']['list_id'] ) ? $form_data['settings']['mailpoet']['list_id'] : '',
				/* translators: %1$s - general settings docs url */
				'tooltip' => sprintf( esc_html__( 'Select the MailPoet List you would like to add your contacts to.', 'everest-forms-pro' ) ),
				'options' => $this->get_mailpoet_lists(),
			)
		);
		everest_forms_panel_field(
			'title',
			'settings[mailpoet]',
			'Primary fields',
			$form_data,
			esc_html__( 'Primary fields', 'everest-forms-pro' ),
			array(
				'tooltip' => sprintf( esc_html__( 'Match the MailPoet merge tags with the corresponding Everest Forms fields by selecting the relevant form field from the list.', 'everest-forms-pro' ) ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings[mailpoet]',
			'email_field',
			$form_data,
			esc_html__( 'Email Address', 'everest-forms-pro' ),
			array(
				'default'     => isset( $form_data['settings']['mailpoet']['email_field'] ) ? $form_data['settings']['mailpoet']['email_field'] : '',
				'options'     => $this->get_fields( $form_data, MailPoetIntegration::get_supported_field_types( 'email_field' ) ),
				'input_class' => 'everest-forms-field-map-select',
				'data'        => array( 'supported-field-type' => implode( ' ', MailPoetIntegration::get_supported_field_types( 'email_field' ) ) ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings[mailpoet]',
			'first_name_field',
			$form_data,
			esc_html__( 'First Name', 'everest-forms-pro' ),
			array(
				'default'     => isset( $form_data['settings']['mailpoet']['first_name_field'] ) ? $form_data['settings']['mailpoet']['first_name_field'] : '',
				'options'     => $this->get_fields( $form_data, MailPoetIntegration::get_supported_field_types( 'first_name_field' ) ),
				'input_class' => 'everest-forms-field-map-select',
				'data'        => array( 'supported-field-type' => implode( ' ', MailPoetIntegration::get_supported_field_types( 'first_name_field' ) ) ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings[mailpoet]',
			'last_name_field',
			$form_data,
			esc_html__( 'Last Name', 'everest-forms-pro' ),
			array(
				'default'     => isset( $form_data['settings']['mailpoet']['last_name_field'] ) ? $form_data['settings']['mailpoet']['last_name_field'] : '',
				'options'     => $this->get_fields( $form_data, MailPoetIntegration::get_supported_field_types( 'last_name_field' ) ),
				'input_class' => 'everest-forms-field-map-select',
				'data'        => array( 'supported-field-type' => implode( ' ', MailPoetIntegration::get_supported_field_types( 'last_name_field' ) ) ),
			)
		);
		// everest_forms_panel_field(
		// 'title',
		// 'settings[mailpoet]',
		// 'custom fields',
		// $form_data,
		// esc_html__( 'Custom fields', 'everest-forms-pro' ),
		// array(
		// 'tooltip' => sprintf( esc_html__( 'Select the field to be used as a custom.', 'everest-forms-pro' ) ),
		// )
		// );

		// echo '<div class="everest-forms-field-map-table everest-forms-addable-list everest-forms-border-container everest-forms-panel-field-select">';
		// $custom_fields = $this->get_custom_field_list( $form_data );

		// echo '<ul>';
		// $meta = isset( $form_data['settings']['mailpoet']['custom_fields'] ) ? $form_data['settings']['mailpoet']['custom_fields'] : array( false );
		// foreach ( $meta as $meta_key => $meta_field ) {
		// $key  = false !== $meta_field ? preg_replace( '/[^a-zA-Z0-9_\-]/', '', $meta_key ) : '';
		// $name = ! empty( $key ) ? 'settings[mailpoet][custom_fields][' . $key . ']' : '';

		// echo '<li>';
		// echo '<span class="key">';
		// echo '<input class="widefat key-source" type="text" value="' . esc_attr( $key ) . '" placeholder="' . esc_attr( 'Enter meta key', 'everest-forms-pro' ) . '" />';
		// echo '</span>';
		// echo '<span class="field"><select class="widefat key-destination everest-forms-field-map-select" data-name="settings[mailpoet][custom_fields][{source}]" name="' . $name . '" data-supported-field-type="' . esc_attr( implode( ' ', MailPoetIntegration::get_supported_field_types( 'custom_fields' ) ) ) . '">';
		// echo '<option value="">' . esc_html__( '--- Select Field ---', 'everest-forms-pro' ) . '</option>';
		// if ( ! empty( $custom_fields ) ) {
		// foreach ( $custom_fields as $id => $field_label ) {
		// printf( '<option value="%s" %s>%s</option>', $id, selected( $meta_field, $id, false ), esc_attr( $field_label ) );
		// }
		// }
		// echo '<select></span>';
		// echo '<span class="actions"><a class="add" href="#"><i class="dashicons dashicons-plus"></i></a><a class="remove" href="#"><i class="dashicons dashicons-minus"></i></a></span>';
		// echo '</li>';
		// }

		// echo '</ul>';
		// echo '</div>';
		// everest_forms_panel_field(
		// 'toggle',
		// 'settings[mailpoet]',
		// 'confirmation_email',
		// $form_data,
		// __( 'Send Confirmation Email ', 'everest-forms-pro' ),
		// array(
		// 'default' => isset( $form_data['settings']['mailpoet']['confirmation_email'] ) ? $form_data['settings']['mailpoet']['confirmation_email'] : '0',
		// )
		// );
		echo '</div>';

		echo '</div>';
	}
	/**
	 * Email field list for MailPoet Email.
	 *
	 * @since 1.0.0
	 * @param [array] $form_data The form data.
	 */
	public function get_fields( $form_data, $field_type = array() ) {
		$fields        = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$modified_list = array();

		foreach ( $fields as $field ) {
			if ( ! empty( $field_type ) ) {
				if ( in_array( $field['type'], $field_type, true ) ) {
					$modified_list[ $field['id'] ] = $field['label'];
				}
				continue;
			}
			$modified_list[ $field['id'] ] = $field['label'];
		}

		return $modified_list;
	}

	/**
	 * Custom field list.
	 *
	 * @since 1.0.0
	 * @param [array] $form_data The field data.
	 */
	public function get_custom_field_list( $form_data ) {
		$fields            = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$custom_field_list = array();
		foreach ( $fields as $field ) {
			if ( in_array( $field['type'], MailPoetIntegration::get_supported_field_types( 'custom_fields' ), true ) ) {

				$custom_field_list[ $field['id'] ] = $field['label'];
			}
		}
		return $custom_field_list;
	}
}
