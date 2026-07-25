<?php
/**
 * AmoCRM Settings.
 *
 * @package EverestForms\Pro\Addons\AmoCRM\Settings
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\AmoCRM\Settings;

use EverestForms\Pro\Addons\AmoCRM\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * AmoCRM Integration.
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
		$this->id                 = 'amocrm';
		$this->icon               = plugins_url( 'src/Addons/AmoCRM/assets/images/amoCRM.png', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'amoCRM', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'amoCRM Integration with Everest Forms', 'everest-forms-pro' );
		$connected_lists          = get_option( 'everest_forms_integrations', array() );

		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );

		if ( ! empty( $connected_lists[ $this->id ] ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}

		add_action( 'everest_forms_integration_account_connect_amocrm', array( $this, 'api_connect' ) );
		add_action( 'admin_init', array( $this, 'amocrm_token_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
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
	 * API Connect amoCRM function.
	 *
	 * @param array $posted_data Has information on api connection & integration.
	 * @throws \Exception Exception.
	 */
	public function api_connect( $posted_data ) {
		if ( empty( $posted_data['client_id'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'Invalid amoCRM Client ID.', 'everest-forms-pro' ),
				)
			);
		}

		if ( empty( $posted_data['secret_key'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'amoCRM Client Secret is required.', 'everest-forms-pro' ),
				)
			);
		}

		if ( empty( $posted_data['access_code'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'amoCRM Access Code is required.', 'everest-forms-pro' ),
				)
			);
		}

		if ( empty( $posted_data['referer_url'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'amoCRM referer url is required.', 'everest-forms-pro' ),
				)
			);
		}

		if ( empty( $posted_data['label'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'amoCRM account nickname is required.', 'everest-forms-pro' ),
				)
			);
		}

		try {

			$connection = array(
				'client_id'  => trim( $posted_data['client_id'] ),
				'secret_key' => trim( $posted_data['secret_key'] ),
				'auth_code'  => trim( $posted_data['access_code'] ),
			);

			$client   = new API( $connection );
			$settings = (array) $client->create_access_token( $connection['auth_code'], $posted_data['referer_url'], $connection );

			if ( isset( $settings['access_token'] ) && ! empty( $settings['access_token'] ) ) {
				$settings['status']      = true;
				$settings['label']       = sanitize_text_field( $posted_data['label'] );
				$settings['date']        = time();
				$connected_accounts      = get_option( 'everest_forms_integrations', array() );
				$api_key_to_be_connected = uniqid();

				$connected_accounts['amocrm'][ $api_key_to_be_connected ] = $settings;

				update_option( 'everest_forms_integrations', $connected_accounts );

				$output  = '<tr>';
				$output .= '<td><strong>' . $settings['label'] . '</strong></td>'; // @codingStandardsIgnoreLine
				/* translators: %s: Date of connection. */
				$output .= '<td>' . sprintf( esc_html__( 'Connected on: %s', 'everest-forms-pro' ), date_i18n( get_option( 'date_format', $settings['date'] ) ) ) . '</td>';
				$output .= '<td><a href="#" class="disconnect everest-forms-integration-disconnect-account" data-source="' . esc_attr( $_POST['source'] ) . '" data-key="' . esc_attr( $api_key_to_be_connected ) . '">' . esc_html__( 'Disconnect', 'everest-forms-pro' ) . '</a></td>'; // @codingStandardsIgnoreLine
				$output .= '</tr>';

				wp_send_json_success(
					array(
						'html' => $output,
					)
				);
			} else {
				$error_message = '';
				if ( isset( $settings['errors'] ) ) {
					$error_message .= $settings['errors'][400][0];
				}
				throw new \Exception( $error_message );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => $e->getMessage(),
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
		$connected_lists = get_option( 'everest_forms_integrations', array() );
		?>
	<div class="integration-connection-detail">
		<div class="evf-account-connect">
			<h3><?php esc_html_e( 'Add New Connection', 'everest-forms-pro' ); ?></h3>
			<p>
				<ol type="1">
					<li>
					<?php
					printf(
						wp_kses(
							__( 'Create an account on <a href="https://www.kommo.com/" target="_blank">amoCRM</a>.', 'everest-forms-pro' ),
							array(
								'a' => array(
									'href'   => array(),
									'target' => array(),
								),
							)
						)
					);
					?>
						</li>
					<li><?php printf( wp_kses( __( 'Navigate to <strong>Settings</strong> > <strong>Integrations</strong>, then click on <strong>Create Integration</strong> in the top-right corner.', 'everest-forms-pro' ), array( 'strong' => array() ) ) ); ?></li>
					<li>
					<?php
					printf(
						wp_kses(
							__( 'Set the redirect URL to the following: <strong>%s</strong><br>Make sure to check the <strong>Allow Access: All</strong> option.', 'everest-forms-pro' ),
							array(
								'strong' => array(),
								'br'     => array(),
							)
						),
						esc_html( home_url( '/wp-admin/?evf_amocrm_auth=true' ) )
					);
					?>
						</li>
					<li><?php esc_html_e( 'Enter a name for your integration and provide a short description, then save the settings.', 'everest-forms-pro' ); ?></li>
					<li><?php printf( wp_kses( __( 'Under <strong>Private Integrations</strong>, find your new integration, click on it, and go to <strong>Keys and Scopes</strong> to retrieve the <strong>Secret Key</strong> and <strong>Integration ID</strong>.', 'everest-forms-pro' ), array( 'strong' => array() ) ) ); ?></li>
				</ol>
			</p>
			<form>
				<div class="evf-connection-form amocrm-connection-form">
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Integration ID', 'everest-forms-pro' ); ?></strong>
							<input type="text" name="client_id" placeholder="<?php esc_attr_e( 'Enter Integration ID', 'everest-forms-pro' ); ?>" class="everest_forms_amocrm_client_id" required>
						</label>
					</div>
					<div class="evf-connection-field everest-forms-hidden">
						<label>
							<strong><?php esc_html_e( 'Secret Key', 'everest-forms-pro' ); ?></strong>
							<input type="password" name="secret_key" placeholder="<?php esc_attr_e( 'Enter Secret Key', 'everest-forms-pro' ); ?>" class="everest_forms_amocrm_secret_key" required>
						</label>
					</div>
					<div class="evf-connection-field everest-forms-hidden">
						<label>
							<strong><?php esc_html_e( 'Account Nickname', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Nickname', 'everest-forms-pro' ); ?>" class="everest_forms_amocrm_label" required>
						</label>
					</div>
					<div class="evf-connection-field everest-forms-hidden">
						<label>
							<strong><?php esc_html_e( 'Access Code', 'everest-forms-pro' ); ?></strong>
							<input type="password" name="<?php echo esc_attr( 'access_code' ); ?>" class="<?php echo esc_attr( 'everest_forms_amocrm_access_code' ); ?>" placeholder="<?php esc_attr_e( 'Enter Access Code', 'everest-forms-pro' ); ?>" required>
						</label>
					</div>
					<div class="evf-connection-field everest-forms-hidden">
						<label>
							<strong><?php esc_html_e( 'Referer URL', 'everest-forms-pro' ); ?></strong>
							<input type="text" name="<?php echo esc_attr( 'referer_url' ); ?>" class="<?php echo esc_attr( 'everest_forms_amocrm_referer_url' ); ?>" placeholder="<?php esc_attr_e( 'Enter Referer URL', 'everest-forms-pro' ); ?>" required>
						</label>
					</div>
				</div>
				<div class="everest-forms-hidden">
					<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account everest_forms_connect_to_amocrm" data-source="<?php echo esc_attr( $this->id ); ?>">
						<?php esc_html_e( 'Connect to amoCRM', 'everest-forms-pro' ); ?>
					</button>
				</div>
				<a href="<?php echo esc_url( filter_var( self::create_auth_url(), FILTER_SANITIZE_URL ) ); ?>" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-open-window everest_forms_get_access_code_btn" data-source="<?php echo esc_attr( $this->id ); ?>">
					<?php esc_html_e( 'Get Access Code', 'everest-forms-pro' ); ?>
				</a>
			</form>
		</div>
		<div class="evf-connection-list">
			<table class="evf-connection-list-table">
				<tbody>
					<?php
					if ( ! empty( $connected_lists['amocrm'] ) ) {
						foreach ( $connected_lists['amocrm'] as $key => $list ) {
							?>
							<tr>
								<td><strong><?php echo sanitize_text_field( $list['label'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></strong></td>
								<td><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Connected on: %s', 'everest-forms-pro' ), date_i18n( get_option( 'date_format' ), $list['date'] ) ) ); ?></td>
								<td>
									<a href='#' class='disconnect everest-forms-integration-disconnect-account' data-source='amocrm' data-key='<?php echo esc_attr( $key ); ?>'>
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
	 * Facilitates amoCRM integration. Evoked from extensibility.
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
	 * Create Auth URL.
	 */
	public static function create_auth_url() {
		$connection   = array(
			'client_id'     => '95d831e1-c424-4160-8b73-69c817a61407',
			'client_secret' => 'Wl4hpGvJBTIaG8EwR3GDtmbCvzmJVBaSbvVrmSGwQVp0FRnYlR52cAf4imxhSniQ',
		);
		$client       = new API( $connection );
		$redirect_url = $client->get_authorization_url();

		return $redirect_url;
	}

	/**
	 * Get Access Code.
	 *
	 * @since 1.7.9
	 */
	public function amocrm_token_page() {
		if ( isset( $_REQUEST['evf_amocrm_auth'] ) ) { // phpcs:ignore

			if ( isset( $_REQUEST['code'] ) ) { // phpcs:ignore
				?>
				<!DOCTYPE html>
				<html lang="en">
					<head>
						<meta charset="UTF-8">
						<meta http-equiv="X-UA-Compatible" content="IE=edge">
						<meta name="viewport" content="width=device-width, initial-scale=1.0">
						<title><?php esc_html( 'amoCRM - Access Code || Everest Forms' ); ?></title>
						<style>
							.evf_amocrm_access_token input{
								width:100%;
							}
						</style>
					</head>
					<body>
						<div class="evf_amocrm_access_token" id="evf_amocrm_access_token" style="width:80%; margin:auto; margin-top: 80px;">
							<p><label>Your amoCRM Access Code is: </label></p>
							<p><input type="text" readonly value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['code'] ) ) ); // phpcs:ignore ?>"/></p>
							<p><label>Your referer url is: </label></p>
							<p><input type="text" readonly value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['referer'] ) ) ); // phpcs:ignore ?>"/></p>
						</div>
					</body>
				</html>
				<?php
				exit();
			}
		}
	}

	/**
	 * Admin Enqueue Scripts.
	 *
	 * @since 1.7.9
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( 'everest-forms_page_evf-settings' === $screen_id || 'everest-forms_page_evf-builder' === $screen_id ) {
		wp_enqueue_style( 'everest-forms-amocrm-style', plugins_url( 'src/Addons/AmoCRM/assets/css/admin/admin.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION );
		}
	}
}
