<?php
/**
 * EverestForms ConvertKit Admin
 *
 * @package EverestForms\Pro\Addons\ConvertKit\Settings
 * @version 1.0.0
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\ConvertKit\Settings;

use EverestForms\Pro\Addons\ConvertKit\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Class EVF_ConvertKit_Integration for integration of ConvertKit.
 */
class Settings extends \EVF_Integration {


	/**
	 * Account ID for current account.
	 *
	 * @var string
	 */
	public $account = false;

	/**
	 * ID of ConvertKit
	 *
	 * @var int
	 */
	public $id;

	/**
	 * URL to the icon image for the ConvertKit.
	 *
	 * @var string
	 */
	public $icon;

	/**
	 * Display title of the ConvertKit in the admin interface.
	 *
	 * @var string
	 */
	public $method_title;

	/**
	 * Description of the ConvertKit for the admin interface.
	 *
	 * @var string
	 */
	public $method_description;

	/**
	 * Status of the account connection (e.g., 'connected' or empty).
	 *
	 * @var string
	 */
	public $account_status;

	/**
	 * Logger Instance
	 *
	 * @var object
	 */
	public static $log = false;

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id                 = 'convertkit';
		$this->icon               = plugins_url( 'src/Addons/ConvertKit/assets/img/convertkit.png', EFP_PLUGIN_FILE );
		$this->method_title       = __( 'ConvertKit', 'everest-forms-pro' );
		$this->method_description = __( 'Marketing automation can be hard to wrap your brain around, but with ConvertKit, its easy.', 'everest-forms-pro' );
		$connected_lists          = get_option( 'everest_forms_integrations', array() );
		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );
		if ( ! empty( $connected_lists[ $this->id ] ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}

		add_action( 'everest_forms_integration_account_connect_convertkit', array( $this, 'connect_to_api' ) );
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
		$connected_lists = get_option( 'everest_forms_integrations', array() );
		?>
	<div class="integration-connection-detail">
		<div class="evf-account-connect">
			<h3><?php esc_html_e( 'Add New Connection', 'everest-forms-pro' ); ?></h3>
			<p><?php esc_html_e( 'Please fill out all of the fields below to add your new provider account.', 'everest-forms-pro' ); ?></p>
			<form>
				<div class="evf-connection-form">
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'ConvertKit API Key', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter ConvertKit API Key', 'everest-forms-pro' ); ?>" class="evf-apikey">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'ConvertKit Nick Name', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter ConvertKit Nick Name', 'everest-forms-pro' ); ?>" class="evf-nickname">
						</label>
					</div>
				</div>
				<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
					<?php esc_html_e( 'Connect to ConvertKit', 'everest-forms-pro' ); ?>
				</a>
			</form>
		</div>
		<div class="evf-connection-list">
			<table class="evf-connection-list-table">
				<tbody>
					<?php
					if ( ! empty( $connected_lists['convertkit'] ) ) {
						foreach ( $connected_lists['convertkit'] as $key => $list ) {
							?>
							<tr>
								<td><strong><?php echo sanitize_text_field( $list['label'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></strong></td>
								<td><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Connected on: %s', 'everest-forms-pro' ), date_i18n( get_option( 'date_format' ), $list['date'] ) ) ); ?></td>
								<td>
									<a href='#' class='disconnect everest-forms-integration-disconnect-account' data-source='convertkit' data-key='<?php echo esc_attr( $key ); ?>'>
										<?php esc_html_e( 'Disconnect', 'everest-forms-pro' ); ?>
									</a>
								</td>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
		<?php
	}

	/**
	 * Function output_integration for rendering.
	 * Kept intact for full backward compatibility (deep-links, bookmarks).
	 */
	public function output_integration() {
		$connected_lists = get_option( 'everest_forms_integrations', array() );
		if ( ! empty( $connected_lists['convertkit'] ) ) {
			$status_class = 'connected';
		} else {
			$status_class = '';
		}
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration' ) ); ?>" class="everest-forms-integration-back-button">
			<span><?php echo esc_html__( 'Back', 'everest-forms-pro' ); ?></span>
		</a>

		<div class="everest-forms-accordion-wrapper">
			<div class="everest-forms-accordion-item is-open">

				<div class="everest-forms-accordion-header">
					<span class="everest-forms-accordion-icon">
						<img src="<?php echo esc_html( $this->icon ); ?>" alt="<?php echo esc_attr( $this->method_title ); ?>">
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
	 * Connect to API function.
	 *
	 * @param array|mixed $posted_data Posted Data for API connection.
	 */
	public function connect_to_api( $posted_data ) {
		$api_details = new API();
		$status      = $api_details->get_api_forms( trim( $posted_data['apikey'] ) );

		if ( ! empty( $status->errors ) ) {
			$details = esc_html__( 'Could not verify API key', 'everest-forms-pro' );
			self::log( sprintf( esc_html__( 'ConvertKit API error: Could not connect to api', 'everest-forms-pro' ) ), 'error' );
			/* translators: %s: API communication error message. */
			$error_msg = sprintf( esc_html__( 'API authentication error: %s', 'everest-forms-pro' ), $details );
			wp_send_json_error(
				array(
					'error'     => $details,
					'error_msg' => $error_msg,
				)
			);
		}

		$to_be_stored_id    = uniqid();
		$connected_accounts = get_option( 'everest_forms_integrations', array() );

		$connected_accounts['convertkit'][ $to_be_stored_id ] = array(
			'api'   => trim( $posted_data['apikey'] ),
			'label' => sanitize_text_field( $posted_data['label'] ),
			'date'  => time(),
		);
		update_option( 'everest_forms_integrations', $connected_accounts );
		$output  = '';
		$output .= '<tr><td><strong>' . ( ! empty( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '' ) . '</strong></td>'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		/* translators: %s: Connection identifier name for the ConvertKit campaign. */
		$output .= '<td>' . sprintf( esc_html__( 'Connected on: %s', 'everest-forms-pro' ), date_i18n( get_option( 'date_format', time() ) ) ) . '</td>';
		$output .= '<td><a href="#" class="disconnect everest-forms-integration-disconnect-account" data-source="' . ( ! empty( $_POST['source'] ) ? $_POST['source'] : '' ) . '" data-key="' . esc_attr( $to_be_stored_id ) . '">' . esc_html__( 'Disconnect', 'everest-forms-pro' ) . '</a></td>'; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$output .= '</tr>';

			wp_send_json_success(
				array(
					'html' => $output,
				)
			);
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( empty( self::$log ) ) {
			self::$log = evf_get_logger();
		}
		self::$log->log( $level, $message, array( 'source' => 'convertkit' ) );
	}
}
