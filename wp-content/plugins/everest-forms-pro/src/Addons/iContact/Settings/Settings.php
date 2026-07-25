<?php
/**
 * iContact Settings.
 *
 * @package EverestForms\iContact\Settings;
 * @since   1.0.0
 */
namespace EverestForms\Pro\Addons\iContact\Settings;

use EverestForms\Pro\Addons\iContact\Api\Api;

defined( 'ABSPATH' ) || exit;

/**
 * iContact Integration.
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
		$this->id                 = 'icontact';
		$this->icon               = plugins_url( 'src/Addons/iContact/assets/img/iContact.png', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'iContact', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'iContact Integration with Everest Forms', 'everest-forms-pro' );
		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );
		$connected_lists = get_option( 'everest_forms_integrations', array() );
		if ( ! empty( $connected_lists[ $this->id ] ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}

		add_action( 'everest_forms_integration_account_connect_icontact', array( $this, 'api_connect' ) );
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
			<div>
				<p><?php esc_html_e( 'Everest Forms iContact Add-On requires your Application ID, API username and API password. To obtain an application ID, follow the steps described below:', 'everest-forms-pro' ); ?></p>
				<ol>
					<li>
						<?php
						printf(
							wp_kses(
								__( 'Visit iContact\'s <a href="https://app.icontact.com/icp/core/registerapp/" target="_blank">application registration page</a>', 'everest-forms-pro' ),
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
					<li><?php esc_html_e( 'Set an application name and description for your application.', 'everest-forms-pro' ); ?></li>
					<li><?php esc_html_e( 'Choose to show information for API 2.0.', 'everest-forms-pro' ); ?></li>
					<li><?php esc_html_e( 'Copy the provided API-AppId into the Application ID setting field below.', 'everest-forms-pro' ); ?></li>
					<li><?php esc_html_e( 'Click "Enable this AppId for your account".', 'everest-forms-pro' ); ?></li>
					<li><?php esc_html_e( 'Create a password for your application and click save.', 'everest-forms-pro' ); ?></li>
					<li><?php esc_html_e( 'Enter your API password, along with your iContact account username, into the fields below.', 'everest-forms-pro' ); ?></li>
				</ol>
			</div>
			<p><?php esc_html_e( 'Fill out the fields to connect your iContact account', 'everest-forms-pro' ); ?></p>
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
							<strong><?php esc_html_e( 'Application Key', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Application Key', 'everest-forms-pro' ); ?>" class="evf-apikey">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Account Email Address', 'everest-forms-pro' ); ?></strong>
							<input type="email" placeholder="<?php esc_attr_e( 'Enter Account Email Address', 'everest-forms-pro' ); ?>" class="evf-icontact-email">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'API Password', 'everest-forms-pro' ); ?></strong>
							<input type="password" placeholder="<?php esc_attr_e( 'Enter API Password', 'everest-forms-pro' ); ?>" class="evf-icontact-password">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Account ID', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Account ID', 'everest-forms-pro' ); ?>" class="evf-icontact-account-id">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Client Folder ID', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Client Folder ID', 'everest-forms-pro' ); ?>" class="evf-icontact-folder-id">
						</label>
					</div>
				</div>
				<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
					<?php esc_html_e( 'Connect to iContact', 'everest-forms-pro' ); ?>
				</a>
			</form>
		</div>
		<div class="evf-connection-list">
			<table class="evf-connection-list-table">
				<tbody>
					<?php
					if ( ! empty( $connected_lists ) && isset( $connected_lists['icontact'] ) ) {
						foreach ( $connected_lists['icontact'] as $key => $list ) {
							?>
							<tr>
								<td><strong><?php echo sanitize_text_field( $list['label'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></strong></td>
								<td><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Connected on: %s', 'everest-forms-pro' ), date_i18n( get_option( 'date_format' ), $list['date'] ) ) ); ?></td>
								<td>
									<a href='#' class='disconnect everest-forms-integration-disconnect-account' data-source='icontact' data-key='<?php echo esc_attr( $key ); ?>'>
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
	 * API Connect iContact function.
	 *
	 * @param array $posted_data Has information on api connection & integration.
	 * @throws \Exception Exception.
	 */
	public function api_connect( $posted_data ) {
		if ( empty( $posted_data['apikey'] ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'iContact API key is required.', 'everest-forms-pro' ),
				)
			);
		}
		try {
			$api_key_to_be_connected = new Api( trim( $posted_data['apikey'] ), sanitize_email( $posted_data['ic_email'] ), sanitize_text_field( $posted_data['ic_password'] ), sanitize_text_field( $posted_data['ic_folder_id'] ), sanitize_text_field( $posted_data['ic_account_id'] ) );
			$valid                   = $api_key_to_be_connected->auth_test( 'test' );
			if ( isset( $valid['errors'] ) ) {
				throw new \Exception( $valid['errors']['0'] );
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

		$connected_accounts['icontact'][ $to_be_stored_id ] = array(
			'api'         => trim( $posted_data['apikey'] ),
			'label'       => sanitize_text_field( $posted_data['label'] ),
			'username'    => sanitize_email( $posted_data['ic_email'] ),
			'apipassword' => sanitize_text_field( $posted_data['ic_password'] ),
			'accountid'   => sanitize_text_field( $posted_data['ic_account_id'] ),
			'folderid'    => sanitize_text_field( $posted_data['ic_folder_id'] ),
			'date'        => time(),
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
