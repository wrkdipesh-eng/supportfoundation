<?php
/**
 * EverestForms GetResponse Admin Settings
 *
 * @package EverestForms\Pro\Addons\GetResponse\Settings
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\GetResponse\Settings;

use EverestForms\Pro\Addons\GetResponse\API\API;

defined( 'ABSPATH' ) || exit;


/**
 * GetResponse Setting Class.
 */
class Settings extends \EVF_Integration {

	/**
	 * Account status.
	 *
	 * @var string
	 */
	public $account_status = '';

	/**
	 * Account ID for current account.
	 *
	 * @var string
	 */
	public $account = false;

	/**
	 * Logger Instance
	 *
	 * @var object
	 */
	public static $log = false;

	/**
	 * Initialize.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->id                 = 'getresponse';
		$this->icon               = plugins_url( 'src/Addons/GetResponse/assets/img/getresponse.jpg', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'GetResponse', 'everest-forms-getresponse' );
		$this->method_description = esc_html__( 'GetResponse Integration with Everest Forms', 'everest-forms-getresponse' );
		$connected_lists          = get_option( 'everest_forms_integrations', array() );
		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );

		if ( ! empty( $connected_lists[ $this->id ] ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}

		add_action( 'everest_forms_integration_account_connect_getresponse', array( $this, 'api_connect' ) );
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
	 * API Connect GetResponse function.
	 *
	 * @param array $posted_data Has information on api connection & integration.
	 * @throws \Exception Exception.
	 */
	public function api_connect( $posted_data ) {
		if ( empty( $posted_data['apikey'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'GetResponse API key is required.', 'everest-forms-getresponse' ),
				)
			);
		}

		if ( empty( $posted_data['label'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'Connection nickname is required.', 'everest-forms-getresponse' ),
				)
			);
		}

		try {

			$api_key_to_be_connected = new API( trim( $posted_data['apikey'] ) );

			$valid = $api_key_to_be_connected->validate_api_key();

			if ( ! $valid ) {

				throw new \Exception( "Invalid GetResponse API key `{$posted_data['apikey']}` supplied." );

			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-getresponse' ),
					'error_msg' => $e->getMessage(),
				)
			);
		}

		$to_be_stored_id    = uniqid();
		$connected_accounts = get_option( 'everest_forms_integrations', array() );

		$connected_accounts['getresponse'][ $to_be_stored_id ] = array(
			'api'   => trim( $posted_data['apikey'] ),
			'label' => sanitize_text_field( $posted_data['label'] ),
			'date'  => time(),
		);

		update_option( 'everest_forms_integrations', $connected_accounts );

		$output  = '<tr>';
		$output .= '<td><strong>' . sanitize_text_field( $_POST['label'] ) . '</strong></td>'; // @codingStandardsIgnoreLine
		/* translators: %s: Date of connection. */
		$output .= '<td>' . sprintf( esc_html__( 'Connected on: %s', 'everest-forms-getresponse' ), date_i18n( get_option( 'date_format', time() ) ) ) . '</td>';
		$output .= '<td><a href="#" class="disconnect everest-forms-integration-disconnect-account" data-source="' . esc_attr( $_POST['source'] ) . '" data-key="' . esc_attr( $to_be_stored_id ) . '">' . esc_html__( 'Disconnect', 'everest-forms-getresponse' ) . '</a></td>'; // @codingStandardsIgnoreLine
		$output .= '</tr>';

		wp_send_json_success(
			array(
				'html' => $output,
			)
		);
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
			<h3><?php esc_html_e( 'Add New Connection', 'everest-forms-getresponse' ); ?></h3>
			<p><?php esc_html_e( 'Fill out the fields to connect your GetResponse account', 'everest-forms-getresponse' ); ?></p>
			<form>
				<div class="evf-connection-form">
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'API Key', 'everest-forms-getresponse' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter API Key', 'everest-forms-getresponse' ); ?>" class="evf-apikey">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Nick Name', 'everest-forms-getresponse' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Nick Name', 'everest-forms-getresponse' ); ?>" class="evf-nickname">
						</label>
					</div>
				</div>
				<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
					<?php esc_html_e( 'Connect to GetResponse', 'everest-forms-getresponse' ); ?>
				</a>
			</form>
		</div>
		<div class="evf-connection-list">
			<table class="evf-connection-list-table">
				<tbody>
					<?php
					if ( ! empty( $connected_lists['getresponse'] ) ) {
						foreach ( $connected_lists['getresponse'] as $key => $list ) {
							?>
							<tr>
								<td><strong><?php echo sanitize_text_field( $list['label'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></strong></td>
								<td><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Connected on: %s', 'everest-forms-getresponse' ), date_i18n( get_option( 'date_format' ), $list['date'] ) ) ); ?></td>
								<td>
									<a href='#' class='disconnect everest-forms-integration-disconnect-account' data-source='getresponse' data-key='<?php echo esc_attr( $key ); ?>'>
										<?php esc_html_e( 'Disconnect', 'everest-forms-getresponse' ); ?>
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
	 * Facilitates getresponse integration. Evoked from extensibility.
	 * Kept intact for full backward compatibility (deep-links, bookmarks).
	 */
	public function output_integration() {
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration' ) ); ?>" class="everest-forms-integration-back-button">
			<span><?php echo esc_html__( 'Back', 'everest-forms-getresponse' ); ?></span>
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
		self::$log->log( $level, $message, array( 'source' => 'getresponse' ) );
	}
}
