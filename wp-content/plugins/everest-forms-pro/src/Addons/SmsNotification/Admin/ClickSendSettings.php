<?php
/**
 * SMS Notification Settings.
 *
 * @package EverestForms\Pro\Addons\SmsNotification\Admin
 * @since   1.7.9
 */

namespace EverestForms\Pro\Addons\SmsNotification\Admin;

use EverestForms\Pro\Addons\SmsNotification\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Twilio Integration.
 *
 * @since 1.7.9
 */
class ClickSendSettings extends \EVF_Integration {

	/**
	 * API.
	 *
	 * @var object
	 */
	public $API;

	/**
	 * Auth code for authentication.
	 *
	 * @var string
	 */
	public $auth_code = '';

	/**
	 * Integration data.
	 *
	 * @var array
	 */
	public $integration = array();

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
		$this->id                 = 'click_send';
		$this->icon               = plugins_url( 'src/Addons/SmsNotification/assets/images/clicksend.png', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'ClickSend', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'ClickSend integration for SMS Notifications Integration with Everest Forms', 'everest-forms-pro' );

		$this->auth_code      = $this->get_option( 'clicksend_auth' );
		$this->integration    = $this->get_integration();
		$this->account_status = empty( $this->auth_code ) ? 'disconnected' : 'connected';

		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );

		// ClickSend API Authenticate.
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
		$map[ $this->id ] = esc_html__( 'SMS Notifications', 'everest-forms-pro' );
		return $map;
	}

	/**
	 * SMS Notifications authenticate.
	 *
	 * @param array $posted_data Posted client credentials.
	 *
	 * @since 1.7.9
	 */
	public function api_authenticate( $posted_data ) {
		$this->client_phone_number = $this->get_field_value( 'client_phone_number', array(), $posted_data );
		$this->client_username     = $posted_data['everest_forms_sms_notifications_client_username'];
		$this->client_api          = $posted_data['everest_forms_sms_notifications_client_api'];

		// Is valid auth to proceed?
		if ( empty( $this->client_phone_number ) && empty( $this->client_username ) && empty( $this->client_api ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'Please fill the full details.', 'everest-forms-pro' ),
				)
			);
		}

		try {
			$account_sid = $this->client_username;
			$auth_token  = $this->client_api;

			$auth = new API( trim( $account_sid ), trim( $auth_token ) );

			$account = $auth->auth_clicksend( $account_sid, $auth_token );

			if ( 200 !== $account['http_code'] ) {
				wp_send_json_error(
					array(
						'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
						'error_msg' => esc_html__( 'Unauthorized Account sid or Auth token.', 'everest-forms-pro' ),
					)
				);
			} else {
				$this->update_option( 'clicksend_auth', $this->client_api );
				$this->update_option( 'client_username', $this->client_username );
				$this->update_option( 'client_phone_number', $this->client_phone_number );

				wp_send_json_success(
					array(
						'button'      => esc_html__( 'Remove Authentication', 'everest-forms-pro' ),
						'description' => esc_html__( 'Clicksend account authenticated.', 'everest-forms-pro' ),
					)
				);
			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => esc_html__( 'Unauthorized Account sid or Auth token.', 'everest-forms-pro' ),
				)
			);
		}
	}

	/**
	 * Twilio API de-authenticate.
	 *
	 * @param array $posted_data Posted client credentials.
	 *
	 * @since 1.7.9
	 */
	public function api_deauthenticate( $posted_data ) {
		$this->init_settings();
		if (
			empty( $posted_data['key'] )
			&& $this->id === $posted_data['source']
		) {
			update_option( $this->get_option_key(), array() );
			wp_send_json_success(
				array(
					'remove' => false,
				)
			);
		}
	}

	/**
	 * Outputs the inner connection form content for inline accordion rendering.
	 *
	 * @since x.x.x
	 */
	public function output_connection_form() {
		?>
		<div class="integration-connection-detail">
			<div class="evf-account-connect">
				<h3><?php esc_html_e( 'ClickSend SMS Setting', 'everest-forms-pro' ); ?></h3>

				<?php if ( empty( $this->auth_code ) ) : ?>
					<p><?php esc_html_e( 'Please fill out the fields to connect your ClickSend account.', 'everest-forms-pro' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'ClickSend account authenticated.', 'everest-forms-pro' ); ?></p>
				<?php endif; ?>

				<form>
					<?php if ( empty( $this->auth_code ) ) : ?>
						<div class="evf-connection-form evf-sms-notifications">
							<div class="evf-connection-field">
								<label>
									<strong><?php esc_html_e( 'Number From', 'everest-forms-pro' ); ?></strong>
									<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'client_phone_number' ) ); ?>" id="<?php echo esc_attr( $this->get_field_key( 'client_phone_number' ) ); ?>" class="<?php echo esc_attr( $this->get_field_key( 'client_phone_number' ) ); ?>" placeholder="<?php esc_attr_e( 'Enter ClickSend number', 'everest-forms-pro' ); ?>" value="">
								</label>
							</div>
							<div class="evf-connection-field">
								<label>
									<strong><?php esc_html_e( 'Username', 'everest-forms-pro' ); ?></strong>
									<input type="text" name="everest_forms_sms_notifications_client_username" id="everest_forms_sms_notifications_client_username" class="everest_forms_sms_notifications_client_username" placeholder="<?php esc_attr_e( 'Enter ClickSend Username', 'everest-forms-pro' ); ?>" value="">
								</label>
							</div>
							<div class="evf-connection-field">
								<label>
									<strong><?php esc_html_e( 'API Key', 'everest-forms-pro' ); ?></strong>
									<input type="text" name="everest_forms_sms_notifications_client_api" id="everest_forms_sms_notifications_client_api" class="everest_forms_sms_notifications_client_api" placeholder="<?php esc_attr_e( 'Enter ClickSend API Key', 'everest-forms-pro' ); ?>" value="">
								</label>
							</div>
						</div>
						<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
							<?php esc_html_e( 'Authenticate with ClickSend', 'everest-forms-pro' ); ?>
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
	 * Facilitates ClickSend integration. Evoked from extensibility.
	 *
	 * @since 1.7.9
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
}
