<?php
/**
 * EverestForms MailPoet Admin
 *
 * @package EverestForms\MailPoet\Admin\Integration
 * @version 1.0.0
 * @since   1.0.0
 */
namespace EverestForms\Pro\Addons\MailPoet\Admin\Integration;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MailPoetIntegration' ) ) :

	/**
	 * MailPoetIntegration class.
	 *
	 * @since 1.0.0
	 */
	class MailPoetIntegration extends \EVF_Integration {

		/**
		 * Account status.
		 *
		 * @var string
		 */
		public $account_status = '';

		/**
		 * Init and hook in the integration.
		 */
		public function __construct() {
			$this->id                 = 'mailpoet';
			$this->icon               = plugins_url( 'src/Addons/MailPoet/assets/img/mailpoet.png', EFP_PLUGIN_FILE );
			$this->method_title       = esc_html__( 'MailPoet', 'everest-forms-pro' );
			$this->method_description = esc_html__( 'MailPoet Integration with Everest Forms', 'everest-forms-pro' );
			// Register the category.
			add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );
			$is_cofigured         = $this->is_configured();
			$connection_status    = get_option( 'everest_forms_integrations_' . $this->id, false );
			$this->account_status = evf_string_to_bool( $connection_status ) && $is_cofigured ? 'connected' : '';
		}


		/**
		 * Registers this integration under its category.
		 *
		 * @param array $map Integration ID to category label map.
		 * @return array
		 */
		public function register_category( $map ) {
			$map[ $this->id ] = esc_html__( 'Email Marketing', 'everest-forms-pro' );
			return $map;
		}

		/**
		 * Outputs the inner connection form content for inline accordion rendering.
		 *
		 * @since x.x.x
		 */
		public function output_connection_form() {
			$is_checked = ( 'connected' === $this->account_status ) ? 'checked' : '';
			?>
	<div class="integration-connection-detail">
		<div class="evf-account-connect">
			<h3><?php esc_html_e( 'Enable Connection', 'everest-forms-pro' ); ?></h3>
			<?php if ( $this->is_configured() ) : ?>
				<form class="evf-mailpoet-connection-form">
					<div class="evf-connection-form-wrapper">
						<div class="evf_integration_checkbox_wrapper">
							<input type="checkbox" name="evf_enable_mailpoet" class="evf_enable_mailpoet" value="1" <?php echo esc_attr( $is_checked ); ?>>
							<label class="evf_integration_checkbox_label">
								<?php esc_html_e( 'Check the fields to connect your MailPoet account', 'everest-forms-pro' ); ?>
							</label>
						</div>
					</div>
					<button type="button" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-mailpoet" data-source="<?php echo esc_attr( $this->id ); ?>">
						<?php esc_html_e( 'Save', 'everest-forms-pro' ); ?>
					</button>
				</form>
			<?php else : ?>
				<div>
					<p class="evf-warning"><?php echo $this->configuration_setup_message(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
			<?php
		}

		/**
		 * Facilitates mailpoet integration. Evoked from extensibility.
		 * Kept intact for full backward compatibility (deep-links, bookmarks).
		 */
		public function output_integration() {
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration' ) ); ?>" class="everest-forms-integration-back-button">
				<span><?php echo esc_html__( 'Back', 'everest-forms-pro' ); ?></span>
			</a>

			<div class="everest-forms-accordion-wrapper">
				<div class="everest-forms-accordion-item is-open">

					<div class="everest-forms-accordion-header">
						<span class="everest-forms-accordion-icon">
							<img src="<?php echo esc_attr( $this->icon ); ?>" alt="<?php echo esc_attr( $this->method_title ); ?>">
						</span>
						<h3 class="everest-forms-accordion-title">
							<?php echo esc_html( $this->method_title ); ?>
						</h3>
						<span class="everest-forms-accordion-toggle">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
								<path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</div>

					<div class="everest-forms-accordion-content">
						<div class="everest-forms-accordion-content-inner">
							<?php $this->output_connection_form(); ?>
						</div>
					</div>

				</div>
			</div>
			<?php
		}

		/**
		 * Check the mailpoet configuration.
		 *
		 * @since 1.0.0
		 * @return boolean
		 */
		public static function is_configured() {
			try {
				if ( ! class_exists( \MailPoet\API\API::class ) ) {
					return false;
				}
				$api = \MailPoet\API\API::MP( 'v1' )->isSetupComplete();
				if ( $api ) {
					return $api;
				}
			} catch ( Exception $e ) {

			}

			return false;
		}

		/**
		 * Configuration setup message.
		 *
		 * @since 1.0.0
		 */
		public static function configuration_setup_message() {
			$message = sprintf( "%s <a href='%s' target='_blank'>%s</a>", esc_html__( 'MailPoet is not configured yet! Please configure your MailPoet api first.', 'everest-forms-pro' ), esc_url( 'https://docs.everestforms.net/' ), esc_html__( 'For more', 'everest-form-mailpoet' ) );
			return $message;
		}

		/**
		 * Supported custom field types.
		 *
		 * @param string $field_for The field for.
		 *
		 * @since 1.0.0
		 */
		public static function get_supported_field_types( $field_for ) {
			switch ( $field_for ) {
				case 'email_field':
					$field_types = array( 'email' );
					break;
				case 'first_name_field':
				case 'last_name_field':
					$field_types = array( 'first-name', 'last-name', 'text', 'dat-time', 'url', 'number' );
					break;
				case 'custom_fields':
					$field_types = array( 'first-name', 'last-name', 'text', 'date-time', 'textarea', 'radio', 'select', 'checkbox' );
					break;
				default:
					$field_types = array();
					break;
			}
			return $field_types;
		}
	}
endif;
