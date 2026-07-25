<?php
/**
 * EverestForms Constant Contact Admin.
 *
 * @package EverestForms\Constant_Contact\Settings\Constant_Contact_Integration
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\ConstantContact\Settings;

use EverestForms\Pro\Addons\ConstantContact\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 *
 * @since 1.0.0
 */
class Settings extends \EVF_Integration {

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
		$this->id                 = 'constant_contact';
		$this->icon               = plugins_url( 'src/Addons/ConstantContact/assets/img/constant-contact.jpg', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'Constant Contact', 'everest-forms-constant-contact' );
		$this->method_description = esc_html__( 'Constant Contact Integration with Everest Forms', 'everest-forms-constant-contact' );
		$connected_lists          = get_option( 'everest_forms_integrations', array() );
		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );

		if ( ! empty( $connected_lists[ $this->id ] ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'constant_contact_token_page' ) );
		add_action( 'everest_forms_integration_account_connect_constant_contact', array( $this, 'api_connect' ) );
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
	 * Admin Enqueue Scripts.
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( 'everest-forms_page_evf-settings' === $screen_id || 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_style( 'everest-forms-constant-contact-style', plugins_url( '/src/Addons/ConstantContact/assets/css/admin.css', EFP_PLUGIN_FILE ), array(), EFP_PLUGIN_FILE );
		}
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
			<h3><?php esc_html_e( 'Add New Connection', 'everest-forms-constant-contact' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: %1$s - Documentation Link Starts; %2$s - Documentation Link ends. */
					esc_html__(
						'Get the Constant Contact access code, we will use that code to authenticate with Constant Contact. Follow the %1$s documentation %2$s to get Constant Contact Client ID and Secret Key.',
						'everest-forms-constant-contact'
					),
					'<a href="https://docs.everestforms.net/docs/constant-contact/" target="_blank">',
					'</a>'
				);
				?>
			</p>
			<form>
				<div class="evf-connection-form constant-contact-connection-form">
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Constant Contact Client ID', 'everest-forms-constant-contact' ); ?></strong>
							<input type="text" name="client_id" placeholder="<?php esc_attr_e( 'Enter CC Client ID', 'everest-forms-constant-contact' ); ?>" class="everest_forms_constant_contact_client_id" required>
						</label>
					</div>
					<div class="evf-connection-field everest-forms-hidden">
						<label>
							<strong><?php esc_html_e( 'Constant Contact Client Secret', 'everest-forms-constant-contact' ); ?></strong>
							<input type="password" name="client_secret" placeholder="<?php esc_attr_e( 'Enter CC Client Secret', 'everest-forms-constant-contact' ); ?>" class="everest_forms_constant_contact_client_secret" required>
						</label>
					</div>
					<div class="evf-connection-field everest-forms-hidden">
						<label>
							<strong><?php esc_html_e( 'Constant Contact Account Nickname', 'everest-forms-constant-contact' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Account Nickname', 'everest-forms-constant-contact' ); ?>" class="everest_forms_constant_contact_label" required>
						</label>
					</div>
					<div class="evf-connection-field everest-forms-hidden">
						<label>
							<strong><?php esc_html_e( 'Constant Contact Access Code', 'everest-forms-constant-contact' ); ?></strong>
							<input type="text" name="<?php echo esc_attr( 'auth_code' ); ?>" class="<?php echo esc_attr( 'everest_forms_constant_contact_auth_code' ); ?>" placeholder="<?php esc_attr_e( 'Enter CC Access Code', 'everest-forms-constant-contact' ); ?>" required>
						</label>
					</div>
				</div>
				<div class="everest-forms-hidden">
					<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
						<?php esc_html_e( 'Connect to Constant Contact', 'everest-forms-constant-contact' ); ?>
					</button>
				</div>
				<a href="<?php echo esc_url( filter_var( self::create_auth_url(), FILTER_SANITIZE_URL ) ); ?>" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-open-window" data-source="<?php echo esc_attr( $this->id ); ?>">
					<?php esc_html_e( 'Get Access Code', 'everest-forms-constant-contact' ); ?>
				</a>
			</form>
		</div>
		<div class="evf-connection-list">
			<table class="evf-connection-list-table">
				<tbody>
					<?php
					if ( ! empty( $connected_lists['constant_contact'] ) ) {
						foreach ( $connected_lists['constant_contact'] as $key => $list ) {
							?>
							<tr>
								<td><strong><?php echo sanitize_text_field( $list['label'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></strong></td>
								<td><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Connected on: %s', 'everest-forms-constant-contact' ), date_i18n( get_option( 'date_format' ), $list['date'] ) ) ); ?></td>
								<td>
									<a href='#' class='disconnect everest-forms-integration-disconnect-account' data-source='constant_contact' data-key='<?php echo esc_attr( $key ); ?>'>
										<?php esc_html_e( 'Disconnect', 'everest-forms-constant-contact' ); ?>
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
	 * Facilitates constant contact integration. Evoked from extensibility.
	 * Kept intact for full backward compatibility (deep-links, bookmarks).
	 */
	public function output_integration() {
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration' ) ); ?>" class="everest-forms-integration-back-button">
			<span><?php echo esc_html__( 'Back', 'everest-forms-constant-contact' ); ?></span>
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
	 * Create Auth URL.
	 */
	public static function create_auth_url() {
		$connection = array(
			'client_id'     => 'OTgzMTQ3ODg2ODk2LXR0azhqdW8xZTE1NzB1bWtyN2lxczlpMnZqYWdidTY2LmFwcHMuZ29vZ2xldXNlcmNvbnRlbnQuY29t',
			'client_secret' => 'WXVKcnVIOWVDRXNrU3lmRW5oM2NJQnJf',
		);

		$client       = new API( $connection );
		$redirect_url = $client->get_authorization_url();

		return $redirect_url;
	}

	/**
	 * Get Access Code.
	 *
	 * @since 1.0.0
	 */
	public function constant_contact_token_page() {
		if ( isset( $_REQUEST['evf_constant_contact_auth'] ) ) { // phpcs:ignore

			if ( isset( $_REQUEST['code'] ) ) { // phpcs:ignore
				?>
				<!DOCTYPE html>
				<html lang="en">
					<head>
						<meta charset="UTF-8">
						<meta http-equiv="X-UA-Compatible" content="IE=edge">
						<meta name="viewport" content="width=device-width, initial-scale=1.0">
						<title>Constant Contact - Access Code || Everest Forms</title>
						<style>
							.evf_constant_contact_access_token input{
								width:100%;
							}
						</style>
					</head>
					<body>
						<div class="evf_constant_contact_access_token" id="evf_constant_contact_access_token" style="width:80%; margin:auto; margin-top: 80px;">
							<p><h3>Your Constant Contact Access Code is: </h3></p>
							<br/>
							<p><input type="text" readonly value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['code'] ) ) ); // phpcs:ignore ?>"/></p>
						</div>
					</body>
				</html>
				<?php
				exit();
			} elseif ( isset( $_REQUEST['error'] ) && 'consent_required' === $_REQUEST['error'] ) { // phpcs:ignore
				?>
				<!DOCTYPE html>
				<html lang="en">
					<head>
						<meta charset="UTF-8">
						<meta http-equiv="X-UA-Compatible" content="IE=edge">
						<meta name="viewport" content="width=device-width, initial-scale=1.0">
						<title>Constant Contact - Access Denied || Everest Forms</title>
					</head>
					<body>
						<div class="evf-constant-contact-authentication-msg" style="width:80%; margin:auto; margin-top: 80px;">
							<p><h3>You denied your application access to your Constant Contact data. Now, you can close this window. </h3></p>
						</div>
					</body>
				</html>
				<?php
				exit();
			}
		}
	}

	/**
	 * API Connect Constant Contact function.
	 *
	 * @param array $posted_data Has information on api connection & integration.
	 * @throws \Exception Exception.
	 */
	public function api_connect( $posted_data ) {
		if ( empty( $posted_data['client_id'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'Invalid Constant Contact Client ID.', 'everest-forms-constant-contact' ),
				)
			);
		}

		if ( empty( $posted_data['client_secret'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'Constant Contact Client Secret is required.', 'everest-forms-constant-contact' ),
				)
			);
		}

		if ( empty( $posted_data['auth_code'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'Constant Contact Access Code is required.', 'everest-forms-constant-contact' ),
				)
			);
		}

		if ( empty( $posted_data['label'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'Constant Contact account nickname is required.', 'everest-forms-constant-contact' ),
				)
			);
		}

		try {

			$connection = array(
				'client_id'     => trim( $posted_data['client_id'] ),
				'client_secret' => trim( $posted_data['client_secret'] ),
				'auth_code'     => trim( $posted_data['auth_code'] ),
			);

			$client   = new API( $connection );
			$settings = $client->create_access_token( $connection['auth_code'], $connection );

			if ( isset( $settings['access_token'] ) && ! empty( $settings['access_token'] ) ) {
				$settings['status']      = true;
				$settings['label']       = sanitize_text_field( $posted_data['label'] );
				$settings['date']        = time();
				$connected_accounts      = get_option( 'everest_forms_integrations', array() );
				$api_key_to_be_connected = uniqid();

				$connected_accounts['constant_contact'][ $api_key_to_be_connected ] = $settings;

				update_option( 'everest_forms_integrations', $connected_accounts );

				$output  = '<tr>';
				$output .= '<td><strong>' . $settings['label'] . '</strong></td>'; // @codingStandardsIgnoreLine
				/* translators: %s: Date of connection. */
				$output .= '<td>' . sprintf( esc_html__( 'Connected on: %s', 'everest-forms-constant-contact' ), date_i18n( get_option( 'date_format', $settings['date'] ) ) ) . '</td>';
				$output .= '<td><a href="#" class="disconnect everest-forms-integration-disconnect-account" data-source="' . esc_attr( $_POST['source'] ) . '" data-key="' . esc_attr( $api_key_to_be_connected ) . '">' . esc_html__( 'Disconnect', 'everest-forms-constant-contact' ) . '</a></td>'; // @codingStandardsIgnoreLine
				$output .= '</tr>';

				wp_send_json_success(
					array(
						'html' => $output,
					)
				);
			} else {
				$error_message = '';
				if ( isset( $settings['error_description'] ) ) {
					$error_message .= "`{$settings['error_description']}` for Constant Contact";
				}
				throw new \Exception( $error_message );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-constant-contact' ),
					'error_msg' => $e->getMessage(),
				)
			);
		}
	}
}
