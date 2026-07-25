<?php
/**
 * Pipedrive Settings.
 *
 * @package EverestForms\Pro\Addons\PipeDrive\Settings
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\PipeDrive\Settings;

use EverestForms\Pro\Addons\PipeDrive\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Pipedrive Integration.
 */
class Settings extends \EVF_Integration {

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
		$this->id                 = 'pipedrive';
		$this->icon               = plugins_url( '/src/Addons/PipeDrive/assets/images/pipedrive.png', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'Pipedrive', 'everest-forms-pipedrive' );
		$this->method_description = esc_html__( 'Pipedrive Integration with Everest Forms', 'everest-forms-pipedrive' );
		$connected_lists          = get_option( 'everest_forms_integrations', array() );
		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );
		if ( ! empty( $connected_lists[ $this->id ] ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}

		add_action( 'everest_forms_integration_account_connect_pipedrive', array( $this, 'api_connect' ) );
	}

	/**
	 * Registers this integration under its category.
	 *
	 * @param array $map Integration ID to category label map.
	 * @return array
	 */
	public function register_category( $map ) {
		$map[ $this->id ] = esc_html__( 'CRM', 'everest-forms-pro' );
		return $map;
	}

	/**
	 * API Connect Pipedrive function.
	 *
	 * @param array $posted_data Has information on api connection & integration.
	 * @throws \Exception Exception.
	 */
	public function api_connect( $posted_data ) {

		if ( empty( $posted_data['apikey'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'Pipedrive API key is required.', 'everest-forms-pipedrive' ),
				)
			);
		}

		try {

			$api_key_to_be_connected = new API( trim( $posted_data['apikey'] ) );
			$valid                   = $api_key_to_be_connected->auth_test();
			if ( isset( $valid['errorCode'] ) && 401 === $valid['errorCode'] ) {
				throw new \Exception( "Invalid Pipedrive API key `{$posted_data['apikey']}` supplied." );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pipedrive' ),
					'error_msg' => $e->getMessage(),
				)
			);
		}

		$to_be_stored_id    = uniqid();
		$connected_accounts = get_option( 'everest_forms_integrations', array() );

		$connected_accounts['pipedrive'][ $to_be_stored_id ] = array(
			'api'   => trim( $posted_data['apikey'] ),
			'label' => sanitize_text_field( $posted_data['label'] ),
			'date'  => time(),
		);

		update_option( 'everest_forms_integrations', $connected_accounts );

		$output  = '<tr>';
		$output .= '<td><strong>' . sanitize_text_field( $_POST['label'] ) . '</strong></td>'; // @codingStandardsIgnoreLine
		/* translators: %s: Date of connection. */
		$output .= '<td>' . sprintf( esc_html__( 'Connected on: %s', 'everest-forms-pipedrive' ), date_i18n( get_option( 'date_format', time() ) ) ) . '</td>';
		$output .= '<td><a href="#" class="disconnect everest-forms-integration-disconnect-account" data-source="' . esc_attr( $_POST['source'] ) . '" data-key="' . esc_attr( $to_be_stored_id ) . '">' . esc_html__( 'Disconnect', 'everest-forms-pipedrive' ) . '</a></td>'; // @codingStandardsIgnoreLine
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
			<h3><?php esc_html_e( 'Add New Connection', 'everest-forms-pipedrive' ); ?></h3>
			<p><?php esc_html_e( 'Fill out the fields to connect your Pipedrive account', 'everest-forms-pipedrive' ); ?></p>
			<form>
				<div class="evf-connection-form">
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Pipedrive API Key', 'everest-forms-pipedrive' ); ?></strong>
							<input type="text" name="evf-apikey" id="evf-apikey" class="evf-apikey" placeholder="<?php esc_attr_e( 'Enter Pipedrive API Key', 'everest-forms-pipedrive' ); ?>">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Pipedrive Nick Name', 'everest-forms-pipedrive' ); ?></strong>
							<input type="text" name="evf-nickname" id="evf-nickname" class="evf-nickname" placeholder="<?php esc_attr_e( 'Enter Pipedrive Nick Name', 'everest-forms-pipedrive' ); ?>">
						</label>
					</div>
				</div>
				<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
					<?php esc_html_e( 'Connect to Pipedrive', 'everest-forms-pipedrive' ); ?>
				</a>
			</form>
		</div>
		<div class="evf-connection-list">
			<table class="evf-connection-list-table">
				<tbody>
					<?php
					if ( ! empty( $connected_lists ) && isset( $connected_lists['pipedrive'] ) ) {
						foreach ( $connected_lists['pipedrive'] as $key => $list ) {
							?>
							<tr>
								<td><strong><?php echo sanitize_text_field( $list['label'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></strong></td>
								<td><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Connected on: %s', 'everest-forms-pipedrive' ), date_i18n( get_option( 'date_format' ), $list['date'] ) ) ); ?></td>
								<td>
									<a href='#' class='disconnect everest-forms-integration-disconnect-account' data-source='pipedrive' data-key='<?php echo esc_attr( $key ); ?>'>
										<?php esc_html_e( 'Disconnect', 'everest-forms-pipedrive' ); ?>
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
	 * Facilitates pipedrive integration. Evoked from extensibility.
	 * Kept intact for full backward compatibility (deep-links, bookmarks).
	 */
	public function output_integration() {
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration' ) ); ?>" class="everest-forms-integration-back-button">
			<span><?php echo esc_html__( 'Back', 'everest-forms-pipedrive' ); ?></span>
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
}
