<?php
/**
 * EverestForms Campaign Monitor Admin
 *
 * @package EverestForms_CampaignMonitor\admin
 * @version 1.0.0
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\CampaignMonitor\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * CampaignMonitor
 */
class Settings extends \EVF_Integration {

	/**
	 * Logger Instance
	 *
	 * @var object
	 */
	public static $log = false;

	/**
	 * Account connection status.
	 *
	 * @var string
	 */
	public $account_status = '';

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id                 = 'campaign_monitor';
		$this->icon               = plugins_url( 'src/Addons/CampaignMonitor/assets/img/campaign-monitor-logo.png', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'Campaign Monitor', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'Campaign Monitor Integration with Everest Forms', 'everest-forms-pro' );
		$connected_lists          = get_option( 'everest_forms_integrations', array() );
		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );
		if ( ! empty( $connected_lists[ $this->id ] ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}

		add_action( 'everest_forms_integration_account_connect_campaign_monitor', array( $this, 'api_connect' ) );
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
			<p><?php esc_html_e( 'Fill out the fields to connect your Campaign Monitor account', 'everest-forms-pro' ); ?></p>
			<form>
				<div class="evf-connection-form">
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'API Key', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter API Key', 'everest-forms-pro' ); ?>" class="evf-apikey">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Client ID', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Client ID', 'everest-forms-pro' ); ?>" class="evf-clientid">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Nick Name', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Nick Name', 'everest-forms-pro' ); ?>" class="evf-nickname">
						</label>
					</div>
				</div>
				<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
					<?php esc_html_e( 'Connect to Campaign Monitor', 'everest-forms-pro' ); ?>
				</a>
			</form>
		</div>
		<div class="evf-connection-list">
			<table class="evf-connection-list-table">
				<tbody>
					<?php
					if ( ! empty( $connected_lists ) && isset( $connected_lists['campaign_monitor'] ) ) {
						foreach ( $connected_lists['campaign_monitor'] as $key => $list ) {
							?>
							<tr>
								<td><strong><?php echo sanitize_text_field( $list['label'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></strong></td>
								<td><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Connected on: %s', 'everest-forms-pro' ), date_i18n( get_option( 'date_format' ), $list['date'] ) ) ); ?></td>
								<td>
									<a href='#' class='disconnect everest-forms-integration-disconnect-account' data-source='campaign_monitor' data-key='<?php echo esc_attr( $key ); ?>'>
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
	 * Facilitates Campaign Monitor integration. Evoked from extensibility.
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
	 * API Connect Campaign Monitor function.
	 *
	 * @param array $posted_data Has information on api connection & integration.
	 */
	public function api_connect( $posted_data ) {
		if ( ! class_exists( 'Campaign_Monitor' ) ) {
			require_once dirname( EFP_PLUGIN_FILE ) . '/src/Addons/CampaignMonitor/vendor/campaign-monitor.php';
		}
		$api = new \Campaign_Monitor( $posted_data['apikey'], $posted_data['client_id'] );
		try {
			$api->get_lists();
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => $e->getMessage(),
				)
			);
		}

		if ( is_wp_error( $api ) ) {
			$details = true === $api ? $api : __( 'Could not verify API URL', 'everest-forms-pro' );
			/*
			translators: %s: Error thrown by API authentication issues. */
			EverestForms_Campaign_Monitor::log( sprintf( __( 'Campaign Monitor API error: %s', 'everest-forms-pro' ), $details ), 'error' );
			/* translators: %s: Error thrown by API authentication issues. */
			$error_msg = sprintf( __( 'API auth error: %s', 'everest-forms-pro' ), $details );
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => $error_msg,
				)
			);
		}

		$to_be_stored_id    = uniqid();
		$connected_accounts = get_option( 'everest_forms_integrations', array() );

		$connected_accounts['campaign_monitor'][ $to_be_stored_id ] = array(
			'api'       => trim( $posted_data['apikey'] ),
			'client_id' => sanitize_text_field( $posted_data['client_id'] ),
			'label'     => sanitize_text_field( $posted_data['label'] ),
			'date'      => time(),
		);
		update_option( 'everest_forms_integrations', $connected_accounts );

		$output  = '<tr>';
		$output .= '<td><strong>' . sanitize_text_field( $_POST['label'] ) . '</strong></td>'; // @codingStandardsIgnoreLine
		/* translators: %s: Date of connection. */
		$output .= '<td>' . sprintf( esc_html__( 'Connected on: %s', 'everest-forms-pro' ), date_i18n( get_option( 'date_format', time() ) ) ) . '</td>';
		$output .= '<td><a href="#" class="disconnect everest-forms-integration-disconnect-account" data-source="' . esc_attr( $_POST['source'] ) . '" data-key="' . esc_attr( $to_be_stored_id ) . '">' . esc_html__( 'Disconnect', 'everest-forms-pro' ) . '</a></td>'; // @codingStandardsIgnoreLine
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
	 * emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( empty( self::$log ) ) {
			self::$log = evf_get_logger();
		}
		self::$log->log( $level, $message, array( 'source' => 'campaign_monitor' ) );
	}
}
