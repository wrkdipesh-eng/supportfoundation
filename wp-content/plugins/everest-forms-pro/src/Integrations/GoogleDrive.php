<?php
/**
 * Google Drive Integration.
 *
 * @package EverestForms\Pro\Integrations
 * @since   1.3.7
 */

namespace EverestForms\Pro\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Google Drive Integration.
 */
class GoogleDrive extends \EVF_Integration {

	/**
	 * Client.
	 *
	 * @var object
	 */
	public $client;

	/**
	 * Drive integration client id.
	 *
	 * @var string
	 */
	public $client_id;

	/**
	 * Drive integration client Secret.
	 *
	 * @var string
	 */
	public $client_secret;

	/**
	 * Drive integration refresh token.
	 *
	 * @var string
	 */
	public $refresh_token;

	/**
	 * Drive integration authorization code.
	 *
	 * @var string
	 */
	public $auth_code;

	/**
	 * Drive access token.
	 *
	 * @var string
	 */
	public $access_token;

	/**
	 * Integration object associated with Drive.
	 *
	 * @var object
	 */
	public $integration;

	/**
	 * Status of the Drive account integration.
	 *
	 * @var string
	 */
	public $account_status;

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id                 = 'google_drive';
		$this->icon               = plugins_url( '/assets/img/google-drive.png', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'Google Drive', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'Google Drive Integration with Everest Forms', 'everest-forms-pro' );

		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );

		$this->init_settings();
		$this->init_form_fields();

		$this->client_id     = isset( $this->settings['client_id'] ) ? $this->settings['client_id'] : 'OTgzMTQ3ODg2ODk2LXR0azhqdW8xZTE1NzB1bWtyN2lxczlpMnZqYWdidTY2LmFwcHMuZ29vZ2xldXNlcmNvbnRlbnQuY29t';
		$this->client_secret = isset( $this->settings['client_secret'] ) ? $this->settings['client_secret'] : 'WXVKcnVIOWVDRXNrU3lmRW5oM2NJQnJf';

		$this->auth_code      = $this->get_option( 'auth_code' );
		$this->access_token   = $this->get_option( 'access_token' );
		$this->refresh_token  = $this->get_option( 'refresh_token' );
		$this->integration    = $this->get_integration();
		$this->account_status = $this->is_auth_required() ? 'disconnected' : 'connected';

		if ( $this->is_integration_page() ) {
			$this->client = $this->get_client();
		}

		add_action( 'everest_forms_integration_account_connect_' . $this->id, array( $this, 'api_authenticate' ) );
		add_action( 'everest_forms_integration_account_disconnect_' . $this->id, array( $this, 'api_deauthenticate' ) );
		add_action( 'template_redirect', array( $this, 'google_drive_token_page' ) );
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
	 * Google Drive Verification Token Display Page.
	 *
	 * @since 1.6.1
	 *
	 * @return void
	 */
	public function google_drive_token_page() {
		if ( ! empty( $_GET['code'] ) && ! empty( $_GET['scope'] ) && 'https://www.googleapis.com/auth/drive.file' === $_GET['scope'] ) {
			wp_head();
			?>
			<style>
				.evf_google_drive_token input{
					width:800px;
				}
			</style>
			<div class="evf_google_drive_token" id="evf_google_drive_token" style="width:80%; margin:auto; margin-top: 80px;">
				<p><h3>Your Token is: </h3></p>
				<br/>
				<p><input type="text" readonly value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['code'] ) ) ); ?>" /></p>
			</div>
			<?php
			wp_footer();
			exit();
		}
	}

	/**
	 * Initialize integration settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'auth_code' => array(
				'title'   => __( 'Enter Google Access Code', 'everest-forms-pro' ),
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
	 * @link  https://developers.google.com/sheets/api/quickstart/php
	 *
	 * @param  bool $is_ajax If called from Ajax.
	 * @return \Google_Client
	 */
	public function get_client( $is_ajax = false ) {
		$logger = evf_get_logger();

		if ( ! empty( $this->client ) && ! $is_ajax ) {
			return $this->client;
		}

		$client = new \Google_Client();
		$client->setClientId( base64_decode( $this->client_id ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$client->setClientSecret( base64_decode( $this->client_secret ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$client->setRedirectUri( trailingslashit( home_url() ) );
		$client->setApplicationName( 'Everest Forms - Google Drive API v' . EFP_VERSION );
		$client->setScopes( \Google_Service_Drive::DRIVE_FILE );
		$client->setAccessType( 'offline' );
		$client->setPrompt( 'select_account consent' );

		$client = apply_filters( 'everest_forms_google_sheets_auth_get_client_custom_options', $client );

		if (
			function_exists( 'wp_get_environment_type' )
			&& 'local' === wp_get_environment_type()
		) {
			$httpClient = new \GuzzleHttp\Client(
				array(
					'exceptions' => false,
					'verify'     => false,
					'base_uri'   => $client->getConfig( 'base_path' ),
				)
			);
			$client->setHttpClient( $httpClient );
		}

		if ( $is_ajax ) {
			return $client;
		}

		if ( ! empty( $this->auth_code ) && $this->is_auth_required() ) {
			try {
				$accessToken = $client->fetchAccessTokenWithAuthCode( $this->auth_code );
			} catch ( \Exception $e ) {
				$accessToken['error'] = $e->getMessage();
				$logger->error(
					sprintf( 'Unable to fetch access token with auth code: %s', $accessToken['error'] ),
					array(
						'source' => 'google-sheets',
					)
				);
			}

			if ( ! empty( $accessToken['error'] ) ) {
				return $client;
			}

			$this->update_option( 'access_token', $client->getAccessToken() );
			$this->update_option( 'refresh_token', $client->getRefreshToken() );
		}

		if ( ! empty( $this->access_token ) ) {
			$client->setAccessToken( $this->access_token );
		}

		if ( $client->isAccessTokenExpired() ) {
			$refresh = $client->getRefreshToken();
			if ( empty( $refresh ) && isset( $this->refresh_token ) ) {
				$refresh = $this->refresh_token;
			}

			if ( ! empty( $refresh ) ) {
				try {
					$refreshToken = $client->fetchAccessTokenWithRefreshToken( $refresh );
				} catch ( \Exception $e ) {
					$refreshToken['error'] = $e->getMessage();
					$logger->error(
						sprintf( 'Unable to fetch access token with refresh token: %s', $refreshToken['error'] ),
						array(
							'source' => 'google-sheets',
						)
					);
				}

				if ( ! empty( $refreshToken['error'] ) ) {
					return $client;
				}

				$this->update_option( 'access_token', $client->getAccessToken() );
				$this->update_option( 'refresh_token', $client->getRefreshToken() );
			}
		}

		return $client;
	}

	/**
	 * Google Drive API authenticate.
	 *
	 * @since 1.3.7
	 *
	 * @param array $posted_data Posted client credentials.
	 */
	public function api_authenticate( $posted_data ) {
		if ( isset( $posted_data['everest_forms_google_drive_client_id'] ) && isset( $posted_data['everest_forms_google_drive_client_secret'] ) ) {
			$this->client_id     = base64_encode( $this->get_field_value( 'client_id', array(), $posted_data ) );
			$this->client_secret = base64_encode( $this->get_field_value( 'client_secret', array(), $posted_data ) );
		}

		$auth_code = $this->get_field_value( 'auth_code', array(), $posted_data );

		if ( empty( $auth_code ) ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not authenticate to the Google Drive.', 'everest-forms-pro' ),
					'error_msg' => esc_html__( 'Please provide the correct Google access code.', 'everest-forms-pro' ),
				)
			);
		}

		$client      = $this->get_client( true );
		$accessToken = $client->fetchAccessTokenWithAuthCode( $auth_code );

		if ( isset( $accessToken['access_token'] ) ) {
			$this->update_option( 'auth_code', $auth_code );
			$this->update_option( 'access_token', $client->getAccessToken() );
			$this->update_option( 'refresh_token', $client->getRefreshToken() );
			$this->update_option( 'client_id', $this->client_id );
			$this->update_option( 'client_secret', $this->client_secret );

			wp_send_json_success(
				array(
					'button'      => esc_html__( 'Remove Authentication', 'everest-forms-pro' ),
					'description' => esc_html__( 'Google Drive account authenticated.', 'everest-forms-pro' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not authenticate to the Google Drive.', 'everest-forms-pro' ),
					'error_msg' => esc_html( $accessToken['error_description'] ),
				)
			);
		}
	}

	/**
	 * Google Drive API deauthenticate.
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
					'oauth'       => filter_var( $client->createAuthUrl(), FILTER_SANITIZE_URL ),
					'button'      => esc_html__( 'Authenticate with Google account', 'everest-forms-pro' ),
					'description' => esc_html__( 'Get the Google access code, we will use that code to authenticate with Google.', 'everest-forms-pro' ),
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
			<h3><?php esc_html_e( 'Authenticate Google Drive', 'everest-forms-pro' ); ?></h3>

			<?php if ( empty( $this->auth_code ) && $this->is_auth_required() ) : ?>
				<p>
					<?php
					printf(
						/* translators: %1$s - Documentation Link Starts; %2$s - Documentation Link ends. */
						esc_html__(
							'Get the Google access code, we will use that code to authenticate with Google. Follow the %1$s documentation %2$s to get Google Client ID and Secret Key.',
							'everest-forms-pro'
						),
						'<a href="https://docs.everestforms.net/docs/how-to-upload-files-to-google-drive-or-dropbox/" target="_blank">',
						'</a>'
					);
					?>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'Google Drive account authenticated.', 'everest-forms-pro' ); ?></p>
			<?php endif; ?>

			<form>
				<?php if ( empty( $this->auth_code ) && $this->is_auth_required() ) : ?>

					<div class="evf-connection-form evf-google-drive">

						<div class="evf-connection-field">
							<label>
								<strong><?php esc_html_e( 'Google Client ID', 'everest-forms-pro' ); ?></strong>
								<input
									type="text"
									name="<?php echo esc_attr( $this->get_field_key( 'client_id' ) ); ?>"
									id="<?php echo esc_attr( $this->get_field_key( 'client_id' ) ); ?>"
									class="<?php echo esc_attr( $this->get_field_key( 'client_id' ) ); ?>"
									placeholder="<?php esc_attr_e( 'Enter Google Client ID', 'everest-forms-pro' ); ?>"
									value=""
								>
							</label>
						</div>

						<div class="evf-connection-field">
							<label>
								<strong><?php esc_html_e( 'Google Client Secret', 'everest-forms-pro' ); ?></strong>
								<input
									type="text"
									name="<?php echo esc_attr( $this->get_field_key( 'client_secret' ) ); ?>"
									id="<?php echo esc_attr( $this->get_field_key( 'client_secret' ) ); ?>"
									class="<?php echo esc_attr( $this->get_field_key( 'client_secret' ) ); ?>"
									placeholder="<?php esc_attr_e( 'Enter Google Client Secret', 'everest-forms-pro' ); ?>"
									value=""
								>
							</label>
						</div>

					</div>

					<div class="hidden evf-connection-form">
						<div class="evf-connection-field">
							<label>
								<strong><?php esc_html_e( 'Google Access Code', 'everest-forms-pro' ); ?></strong>
								<input
									type="text"
									name="<?php echo esc_attr( $this->get_field_key( 'auth_code' ) ); ?>"
									id="<?php echo esc_attr( $this->get_field_key( 'auth_code' ) ); ?>"
									class="<?php echo esc_attr( $this->get_field_key( 'auth_code' ) ); ?>"
									placeholder="<?php esc_attr_e( 'Enter Google Access Code', 'everest-forms-pro' ); ?>"
									value="<?php echo esc_attr( $this->auth_code ); ?>"
								>
							</label>
						</div>
						<button
							type="submit"
							class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account"
							style="padding:7px 14px"
							data-source="<?php echo esc_attr( $this->id ); ?>"
						>
							<?php esc_html_e( 'Verify access code', 'everest-forms-pro' ); ?>
						</button>
					</div>


					<a 	href="<?php echo esc_url( filter_var( $this->client->createAuthUrl(), FILTER_SANITIZE_URL ) ); ?>"
						class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-open-window"
						data-source="<?php echo esc_attr( $this->id ); ?>"
					>
						<?php esc_html_e( 'Authenticate with Google account', 'everest-forms-pro' ); ?>
					</a>

				<?php else : ?>


						href="#"
						class="everest-forms-btn everest-forms-btn-secondary everest-forms-integration-disconnect-account"
						data-source="<?php echo esc_attr( $this->id ); ?>"
					>
						<?php esc_html_e( 'Remove Authentication', 'everest-forms-pro' ); ?>
					</a>

				<?php endif; ?>
			</form>
		</div>
	</div>
		<?php
	}

	/**
	 * Outputs the Google Drive integration settings page wrapped in the
	 * accordion UI. Kept for backward-compatible deep-links.
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
