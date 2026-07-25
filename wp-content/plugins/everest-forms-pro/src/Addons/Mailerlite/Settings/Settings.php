<?php
/**
 * EverestForms MailerLite Admin Settings
 *
 * @package EverestForms\Pro\Addons\Mailerlite\Settings.
 * @version 1.0.0
 * @since   1.7.7
 */

namespace EverestForms\Pro\Addons\Mailerlite\Settings;

use EverestForms_MailerLite;
use Exception;

defined( 'ABSPATH' ) || exit;


/**
 * Class Settings.
 */
class Settings {

	/**
	 * Integration ID.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Integration icon URL.
	 *
	 * @var string
	 */
	public $icon = '';

	/**
	 * Integration title.
	 *
	 * @var string
	 */
	public $method_title = '';

	/**
	 * Integration description.
	 *
	 * @var string
	 */
	public $method_description = '';

	/**
	 * Account connection status.
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
	 * Initialize.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->id                 = 'mailerlite';
		$this->icon               = plugins_url( 'src/Addons/Mailerlite/assets/img/mailerlite.png', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'MailerLite', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'MailerLite Integration with Everest Forms', 'everest-forms-pro' );
		$connected_lists          = get_option( 'everest_forms_integrations', array() );
		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );
		if ( ! empty( $connected_lists[ $this->id ] ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}
		add_action( 'everest_forms_integration_account_connect_mailerlite', array( $this, 'api_connect' ) );
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
	 * API Connect Mailerlite function.
	 *
	 * @param array $posted_data Has information on api connection & integration.
	 */
	public function api_connect( $posted_data ) {
		if ( ! class_exists( '\MailerLiteApi\MailerLite' ) ) {
			require_once dirname( EFP_PLUGIN_FILE ) . '/vendor/autoload.php';
		}

		if ( empty( trim( $posted_data['apikey'] ) ) ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => esc_html__( 'Please enter your MailerLite API key.', 'everest-forms-pro' ),
				)
			);
			return;
		}

		$api_key    = trim( $posted_data['apikey'] );
		$is_jwt_key = str_starts_with( $api_key, 'eyJ' );

		if ( $is_jwt_key ) {
			// New MailerLite API (post March 2022) — uses JWT Bearer token.
			$response = wp_remote_get(
				'https://connect.mailerlite.com/api/subscribers?limit=1',
				array(
					'headers'   => array(
						'Authorization' => 'Bearer ' . $api_key,
						'Content-Type'  => 'application/json',
						'Accept'        => 'application/json',
					),
					'timeout'   => 15,
					'sslverify' => false,
				)
			);

			if ( is_wp_error( $response ) ) {
				wp_send_json_error(
					array(
						'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
						'error_msg' => $response->get_error_message(),
					)
				);
				return;
			}

			$status_code = (int) wp_remote_retrieve_response_code( $response );

			if ( 200 !== $status_code ) {
				wp_send_json_error(
					array(
						'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
						'error_msg' => esc_html__( 'Invalid API key. Please check and try again.', 'everest-forms-pro' ),
					)
				);
				return;
			}
		} else {
			// Classic MailerLite API v2 — uses short alphanumeric key.
			try {
				$auth = new \MailerLiteApi\MailerLite( $api_key );
			} catch ( Exception $e ) {
				wp_send_json_error(
					array(
						'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
						'error_msg' => $e->getMessage(),
					)
				);
				return;
			}

			if ( is_wp_error( $auth ) ) {
				$details = true === $auth ? $auth : __( 'Could not verify API URL', 'everest-forms-pro' );
				// * translators: %s: Error thrown by API authentication issues. */
				EverestForms_MailerLite::log( sprintf( __( 'MailerLite API error: %s', 'everest-forms-pro' ), $details ), 'error' );
				// * translators: %s: Error thrown by API authentication issues. */
				$error_msg = sprintf( __( 'API auth error: %s', 'everest-forms-pro' ), $details );
				wp_send_json_error(
					array(
						'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
						'error_msg' => $error_msg,
					)
				);
				return;
			}

			$fields = $auth->fields()->get();

			if ( ! is_array( $fields ) ) {
				wp_send_json_error(
					array(
						'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
						'error_msg' => esc_html__( 'Invalid API key. Please check and try again.', 'everest-forms-pro' ),
					)
				);
				return;
			}
		}

		// Key is valid — store and respond.
		$to_be_stored_id    = uniqid();
		$connected_accounts = get_option( 'everest_forms_integrations', array() );

		$connected_accounts['mailerlite'][ $to_be_stored_id ] = array(
			'api'   => $api_key,
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
			<p><?php esc_html_e( 'Fill out the fields to connect your MailerLite account', 'everest-forms-pro' ); ?></p>
			<form>
				<div class="evf-connection-form">
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'MailerLite API Key', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter MailerLite API Key', 'everest-forms-pro' ); ?>" class="evf-apikey">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'MailerLite Nick Name', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter MailerLite Nick Name', 'everest-forms-pro' ); ?>" class="evf-nickname">
						</label>
					</div>
				</div>
				<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
					<?php esc_html_e( 'Connect to MailerLite', 'everest-forms-pro' ); ?>
				</a>
			</form>
		</div>
		<div class="evf-connection-list">
			<table class="evf-connection-list-table">
				<tbody>
					<?php
					if ( ! empty( $connected_lists ) && isset( $connected_lists['mailerlite'] ) ) {
						foreach ( $connected_lists['mailerlite'] as $key => $list ) {
							?>
							<tr>
								<td><strong><?php echo sanitize_text_field( $list['label'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></strong></td>
								<td><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Connected on: %s', 'everest-forms-pro' ), date_i18n( get_option( 'date_format' ), $list['date'] ) ) ); ?></td>
								<td>
									<a href='#' class='disconnect everest-forms-integration-disconnect-account' data-source='mailerlite' data-key='<?php echo esc_attr( $key ); ?>'>
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
	 * Facilitates mailerlite integration. Evoked from extensibility.
	 */
	public function output_integration() {
		$connected_lists = get_option( 'everest_forms_integrations', array() );
		if ( ! empty( $connected_lists['mailerlite'] ) ) {
			$status_class = 'Connected';
		} else {
			$status_class = '';
		}
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration' ) ); ?>" class="everest-forms-integration-back-button">
			<span><?php echo esc_html__( 'Back', 'everest-forms-pro' ); ?></span>
		</a>
		<div class="everest-forms-integration-content">
			<div class="integration-addon-detail">
				<div class="evf-integration-info-header">
					<figure class="evf-integration-logo">
						<img src="<?php echo esc_attr( $this->icon ); ?>" alt="<?php echo esc_attr( 'test' ); ?>">
					</figure>
					<div class="integration-info">
						<h3><?php echo $this->method_title; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></h3>
						<div class="integration-status <?php echo $status_class; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>">
							<span class="toggle-switch connected"><?php echo $status_class; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></span>
						</div>
					</div>
				</div>
				<p><?php echo $this->method_description; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></p>
			</div>
			<?php $this->output_connection_form(); ?>
		</div>
		<?php
	}
}
