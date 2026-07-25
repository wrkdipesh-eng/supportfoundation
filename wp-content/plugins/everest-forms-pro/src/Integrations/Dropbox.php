<?php
/**
 * Dropbox Integration.
 *
 * @package EverestForms\Pro\Integrations
 * @since   1.3.7
 */

namespace EverestForms\Pro\Integrations;

defined( 'ABSPATH' ) || exit;

use Kunnu\Dropbox\Dropbox as IntegrationDropbox;
use Kunnu\Dropbox\DropboxApp;

/**
 * Dropbox Integration.
 */
class Dropbox extends \EVF_Integration {

	/**
	 * Client.
	 *
	 * @var object
	 */
	public $client;

	/**
	 * Dropbox integration client id.
	 *
	 * @var string
	 */
	public $client_id;

	/**
	 * Dropbox integration client Secret.
	 *
	 * @var string
	 */
	public $client_secret;

	/**
	 * Dropbox integration authorization code.
	 *
	 * @var string
	 */
	public $auth_code;

	/**
	 * Dropbox access token.
	 *
	 * @var string
	 */
	public $access_token;

	/**
	 * Refresh token.
	 *
	 * @var string
	 */
	public $refresh_token;

	/**
	 * Integration object associated with Dropbox.
	 *
	 * @var object
	 */
	public $integration;

	/**
	 * Status of the Dropbox account integration.
	 *
	 * @var string
	 */
	public $account_status;

	/**
	 * Access token expiry timestamp.
	 *
	 * @var int
	 */
	public $access_token_expiry;

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id                 = 'dropbox';
		$this->icon               = plugins_url( '/assets/img/dropbox.png', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'Dropbox', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'Dropbox Integration with Everest Forms', 'everest-forms-pro' );
		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );
		$this->init_settings();
		$this->init_form_fields();

		$this->client_id     = 'ZXF6MGllM2F2d3BrNXZq';
		$this->client_secret = 'ZDcydXJienJ3NXQ1eHdi';

		$this->auth_code           = $this->get_option( 'auth_code' );
		$this->access_token        = $this->get_option( 'access_token' );
		$this->access_token_expiry = $this->get_option( 'access_token_expiry' );
		$this->refresh_token       = $this->get_option( 'refresh_token' );
		$this->account_status      = $this->is_auth_required() ? 'disconnected' : 'connected';

		if ( $this->is_integration_page() ) {
			$this->client = $this->get_client();
		}

		add_action( 'everest_forms_integration_account_connect_' . $this->id, array( $this, 'api_authenticate' ) );
		add_action( 'everest_forms_integration_account_disconnect_' . $this->id, array( $this, 'api_deauthenticate' ) );
	}


	/**
	 * Registers this integration under its category.
	 *
	 * @param array $map Integration ID to category label map.
	 * @return array
	 */
	public function register_category( $map ) {
		$map[ $this->id ] = esc_html__( 'Cloud Storage', 'everest-forms-cloud-storage' );
		return $map;
	}

	/**
	 * Initialize integration settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'auth_code' => array(
				'title'   => __( 'Enter Dropbox Access Code', 'everest-forms-pro' ),
				'type'    => 'text',
				'default' => '',
			),
		);
	}

	/**
	 * Is authentication required?
	 *
	 * @return bool
	 */
	public function is_auth_required() {
		return empty( $this->access_token ) || empty( $this->refresh_token );
	}

	/**
	 * Returns an authorized API client.
	 *
	 * @since 1.3.7
	 * @link  https://github.com/kunalvarma05/dropbox-php-sdk/wiki/Authentication-and-Authorization
	 *
	 * @param  bool $is_ajax If called from Ajax.
	 * @return \Dropbox
	 */
	public function get_client( $is_ajax = false ) {
		$logger = evf_get_logger();

		if ( ! empty( $this->client ) && ! $is_ajax ) {
			return $this->client;
		}

		$app = new DropboxApp( base64_decode( $this->client_id ), base64_decode( $this->client_secret ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( ! defined( 'EVF_DEBUG' ) ) {
			$guzzleClient = null;
		} else {
			$guzzleClient = new \GuzzleHttp\Client(
				array(
					'verify' => false,
				)
			);
		}

		$client = new IntegrationDropbox( $app, array( 'http_client_handler' => $guzzleClient ) );

		if ( $is_ajax ) {
			return $client;
		}

		if ( ! empty( $this->auth_code ) && $this->is_auth_required() ) {
			try {
				$authHelper  = $client->getAuthHelper();
				$accessToken = $authHelper->getAccessToken( $this->auth_code );

				if ( ! empty( $accessToken->getToken() ) ) {
					$this->update_option( 'access_token', $accessToken->getToken() );
					$this->update_option( 'refresh_token', $accessToken->getToken() );
					$this->update_option( 'access_token_expiry', $accessToken->getToken() );
				}
			} catch ( \Exception $e ) {
				$accessToken['error'] = $e->getMessage();
				$logger->error(
					sprintf( 'Unable to fetch access token with auth code: %s', $accessToken['error'] ),
					array(
						'source' => 'dropbox',
					)
				);
			}
		}

		if ( ! empty( $this->access_token ) && ! empty( $this->refresh_token ) && time() > $this->access_token_expiry ) {
			try {
				$authHelper                = $client->getAuthHelper();
				$accessToken               = $authHelper->getOAuth2Client()->getAccessToken( $this->refresh_token, null, 'refresh_token' );
				$this->access_token        = $accessToken['access_token'];
				$this->access_token_expiry = time() + $accessToken['expires_in'];
				$this->update_option( 'access_token', $this->access_token );
				$this->update_option( 'access_token_expiry', $this->access_token_expiry );
			} catch ( \Exception $e ) {
				$error = $e->getMessage();
				$logger->error(
					sprintf( 'Unable to fetch access token with refreshed token: %s', $error ),
					array(
						'source' => 'dropbox',
					)
				);
			}
		}

		if ( ! empty( $this->access_token ) ) {
			$client->setAccessToken( $this->access_token );
		}

		return $client;
	}

	/**
	 * Dropbox API authenticate.
	 *
	 * @since 1.3.7
	 *
	 * @param array $posted_data Posted client credentials.
	 */
	public function api_authenticate( $posted_data ) {
		$auth_code = $this->get_field_value( 'auth_code', $this->form_fields['auth_code'], $posted_data );

		if ( empty( $auth_code ) ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not authenticate to the Dropbox.', 'everest-forms-pro' ),
					'error_msg' => esc_html__( 'Please provide the correct Dropbox access code.', 'everest-forms-pro' ),
				)
			);
		}

		try {
			$client      = $this->get_client( true );
			$authHelper  = $client->getAuthHelper();
			$accessToken = $authHelper->getAccessToken( $auth_code );

			if ( ! empty( $accessToken->getToken() ) ) {
				$this->update_option( 'auth_code', $auth_code );
				$this->update_option( 'access_token', $accessToken->getToken() );
				$this->update_option( 'refresh_token', $accessToken->getRefreshToken() );
				$this->update_option( 'access_token_expiry', time() + $accessToken->getExpiryTime() );
				wp_send_json_success(
					array(
						'button'      => esc_html__( 'Remove Authentication', 'everest-forms-pro' ),
						'description' => esc_html__( 'Dropbox account authenticated.', 'everest-forms-pro' ),
					)
				);
			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not authenticate to the Dropbox.', 'everest-forms-pro' ),
					'error_msg' => ucfirst( json_decode( $e->getMessage() )->error_description ),
				)
			);
		}
	}

	/**
	 * Dropbox API deauthenticate.
	 *
	 * @since 1.3.7
	 *
	 * @param array $posted_data Posted client credentials.
	 */
	public function api_deauthenticate( $posted_data ) {
		$this->init_settings();

		$client = $this->get_client( true );

		if (
			empty( $posted_data['key'] )
			&& $this->id === $posted_data['source']
		) {
			update_option( $this->get_option_key(), array() );
			wp_send_json_success(
				array(
					'remove'      => false,
					'oauth'       => filter_var( $client->getAuthHelper()->getAuthUrl(), FILTER_SANITIZE_URL ),
					'button'      => esc_html__( 'Authenticate with Dropbox account', 'everest-forms-pro' ),
					'description' => esc_html__( 'Get the Dropbox access code, we will use that code to authenticate.', 'everest-forms-pro' ),
				)
			);
		}
	}

	/**
	 * Outputs the inner connection form content.
	 *
	 * @since x.x.x
	 */
	public function output_connection_form() {
		?>
	<div class="integration-connection-detail">
		<div class="evf-account-connect">
			<h3><?php esc_html_e( 'Authenticate Dropbox', 'everest-forms-pro' ); ?></h3>

			<?php if ( empty( $this->auth_code ) && $this->is_auth_required() ) : ?>
				<p><?php esc_html_e( 'Get the Dropbox access code, we will use that code to authenticate with Dropbox.', 'everest-forms-pro' ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Dropbox account authenticated.', 'everest-forms-pro' ); ?></p>
			<?php endif; ?>

			<form>
				<div class="evf-connection-form hidden">
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Dropbox Access Code', 'everest-forms-pro' ); ?></strong>
							<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'auth_code' ) ); ?>" id="<?php echo esc_attr( $this->get_field_key( 'auth_code' ) ); ?>" class="<?php echo esc_attr( $this->get_field_key( 'auth_code' ) ); ?>" placeholder="<?php esc_attr_e( 'Enter Dropbox Access Code', 'everest-forms-pro' ); ?>" value="<?php echo esc_attr( $this->auth_code ); ?>">
						</label>
					</div>
					<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" style="padding:7px 14px" data-source="<?php echo esc_attr( $this->id ); ?>">
						<?php esc_html_e( 'Verify access code', 'everest-forms-pro' ); ?>
					</button>
				</div>

				<?php if ( empty( $this->auth_code ) && $this->is_auth_required() ) : ?>
					<a href="<?php echo esc_url( filter_var( $this->client->getAuthHelper()->getAuthUrl( null, array(), null, 'offline' ), FILTER_SANITIZE_URL ) ); ?>" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-open-window" data-source="<?php echo esc_attr( $this->id ); ?>">
						<?php esc_html_e( 'Authenticate with Dropbox account', 'everest-forms-pro' ); ?>
					</a>
				<?php else : ?>
					<a href="#" class="everest-forms-btn everest-forms-btn-secondary everest-forms-integration-disconnect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
						<?php esc_html_e( 'Remove Authentication', 'everest-forms-pro' ); ?>
					</a>
				<?php endif; ?>
			</form>
		</div>
	</div>
		<?php
	}

	/**
	 * Outputs the Dropbox integration settings page wrapped in the accordion
	 * UI. Kept for backward-compatible deep-links (e.g. section=dropbox).
	 *
	 * @since x.x.x
	 */
	public function output_integration() {
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration' ) ); ?>" class="everest-forms-integration-back-button">
			<span><?php esc_html_e( 'Back', 'everest-forms-pro' ); ?></span>
		</a>

		<div class="everest-forms-accordion-wrapper">
			<div class="everest-forms-accordion-item is-open">

				<div class="everest-forms-accordion-header">
					<span class="everest-forms-accordion-icon">
						<img src="<?php echo esc_url( $this->icon ); ?>" alt="<?php echo esc_attr( $this->method_title ); ?>">
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
