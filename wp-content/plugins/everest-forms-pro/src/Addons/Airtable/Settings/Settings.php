<?php
/**
 * Airtable Settings.
 *
 * @package EverestForms\Pro\Addons\Airtable\Settings;
 * @since   1.0.0
 */
namespace EverestForms\Pro\Addons\Airtable\Settings;

use EverestForms\Pro\Addons\Airtable\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Airtable Integration.
 */
class Settings extends \EVF_Integration {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id                 = 'airtable';
		$this->icon               = plugins_url( 'src/Addons/Airtable/assets/img/Airtable.png', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'Airtable', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'Airtable Integration with Everest Forms', 'everest-forms-pro' );

		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );

		$connected_lists = get_option( 'everest_forms_integrations', array() );
		if ( ! empty( $connected_lists[ $this->id ] ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}

		add_action( 'everest_forms_integration_account_connect_airtable', array( $this, 'api_connect' ) );
	}

	/**
	 * Registers this integration under its category.
	 *
	 * @param array $map Integration ID to category label map.
	 * @return array
	 */
	public function register_category( $map ) {
		$map[ $this->id ] = esc_html__( 'CRM', 'everest-forms-cloud-storage' );
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
			<div>
				<p>
					<?php
					printf(
						wp_kses(
							__( 'You can create Access Token from this <a href="https://airtable.com/create/tokens">Link</a><br>Give a name for your token then select these scope <strong>data.records:read</strong>, <strong>data.records:write</strong> and <strong>schema.bases:read</strong> by clicking Add a scope.', 'everest-forms-pro' ),
							array(
								'a'      => array( 'href' => array() ),
								'br'     => array(),
								'strong' => array(),
							)
						)
					);
					?>
				</p>
			</div>
			<p><?php esc_html_e( 'Fill out the fields to connect your Airtable account', 'everest-forms-pro' ); ?></p>
			<form>
				<div class="evf-connection-form">
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Nickname', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Nickname', 'everest-forms-pro' ); ?>" class="evf-nickname">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Access Token', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Access Token', 'everest-forms-pro' ); ?>" class="evf-apikey">
						</label>
					</div>
				</div>
				<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
					<?php esc_html_e( 'Connect to Airtable', 'everest-forms-pro' ); ?>
				</a>
			</form>
		</div>
		<div class="evf-connection-list">
			<table class="evf-connection-list-table">
				<tbody>
					<?php
					if ( ! empty( $connected_lists ) && isset( $connected_lists['airtable'] ) ) {
						foreach ( $connected_lists['airtable'] as $key => $list ) {
							?>
							<tr>
								<td><strong><?php echo sanitize_text_field( $list['label'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></strong></td>
								<td><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Connected on: %s', 'everest-forms-pro' ), date_i18n( get_option( 'date_format' ), $list['date'] ) ) ); ?></td>
								<td>
									<a href='#' class='disconnect everest-forms-integration-disconnect-account' data-source='airtable' data-key='<?php echo esc_attr( $key ); ?>'>
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
	 * Output Integration.
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
	 * API Connect Airtable function.
	 *
	 * @param array $posted_data Has information on api connection & integration.
	 * @throws \Exception Exception.
	 */
	public function api_connect( $posted_data ) {
		if ( empty( $posted_data['apikey'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'Airtable API key is required.', 'everest-forms-pro' ),
				)
			);
		}
		$logger = new \EVF_Logger();
		try {
			$api_key_to_be_connected = new API( trim( $posted_data['apikey'] ) );
			$valid                   = $api_key_to_be_connected->get_workspace_list();
			if ( isset( $valid['error'] ) ) {
				$logger->log( 'error', esc_html__( 'Airtable API error: Could not connect to api ', 'everest-forms-pro' ) );
				throw new \Exception( 'Please provide valid Access Token.' );
			} else {
				$logger->log( 'info', esc_html__( 'Airtable Api connection successful.', 'everest-forms-pro' ), array( 'source' => 'airtable' ) );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => $e->getMessage(),
				)
			);
		}

		$to_be_stored_id    = uniqid();
		$connected_accounts = get_option( 'everest_forms_integrations', array() );

		$connected_accounts['airtable'][ $to_be_stored_id ] = array(
			'api'   => trim( $posted_data['apikey'] ),
			'label' => sanitize_text_field( $posted_data['label'] ),
			'date'  => time(),
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
}
