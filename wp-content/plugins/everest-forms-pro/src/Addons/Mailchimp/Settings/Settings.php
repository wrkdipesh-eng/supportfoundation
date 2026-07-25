<?php
/**
 * EverestForms MailChimp Admin Settings
 *
 * @package EverestForms\Pro\Addons\Mailchimp\Settings
 * @version 1.0.0
 * @since   1.7.7
 */

namespace EverestForms\Pro\Addons\Mailchimp\Settings;

use EverestForms\Pro\Addons\Mailchimp\MailChimp_EVF;

defined( 'ABSPATH' ) || exit;


/**
 * Class Settings.
 */
class Settings {

	/**
	 * Account ID for current account.
	 *
	 * @var string
	 */
	public $account = false;

	/**
	 * ID of Mail Chimp
	 *
	 * @var int
	 */
	public $id;

	/**
	 * URL to the icon image for the Mailchimp.
	 *
	 * @var string
	 */
	public $icon;

	/**
	 * Display title of the Mailchimp in the admin interface.
	 *
	 * @var string
	 */
	public $method_title;

	/**
	 * Description of the Mailchimp for the admin interface.
	 *
	 * @var string
	 */
	public $method_description;

	/**
	 * Status of the account connection (e.g., 'connected' or empty).
	 *
	 * @var string
	 */
	public $account_status;

	/**
	 * Initialize.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->id                 = 'mailchimp';
		$this->icon               = plugins_url( 'src/Addons/Mailchimp/assets/img/mailchimp.png', EFP_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'MailChimp', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'MailChimp Integration with Everest Forms', 'everest-forms-pro' );
		$connected_lists          = get_option( 'everest_forms_integrations', array() );
		// Register the category.
		add_filter( 'everest_forms_integration_categories', array( $this, 'register_category' ) );
		if ( ! empty( $connected_lists[ $this->id ] ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}
		add_action( 'everest_forms_integration_account_connect_mailchimp', array( $this, 'api_connect' ) );
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
	 * API Connect Mailchimp function.
	 *
	 * @param array $posted_data Has information on api connection & integration.
	 */
	public function api_connect( $posted_data ) {
		if ( ! class_exists( 'MailChimp_EVF' ) ) {
			require_once dirname( EFP_PLUGIN_FILE ) . '/src/Addons/Mailchimp/vendor/Mailchimp.php';
		}
		try {
			$api_key_to_be_connected = new MailChimp_EVF( trim( $posted_data['apikey'] ) );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => $e->getMessage(),
				)
			);
		}

		$api_details = $api_key_to_be_connected->get( '' );

		if ( empty( $api_details['account_id'] ) ) {
			$details = ! empty( $api_details['detail'] ) ? $api_details['detail'] : __( 'Could not verify API key', 'everest-forms-pro' );
			/* translators: %s: Error thrown by API authentication issues. */
			EverestForms_MailChimp::log( sprintf( __( 'MailChimp API error: %s', 'everest-forms-pro' ), $api_details ), 'error' );
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

		$connected_accounts['mailchimp'][ $to_be_stored_id ] = array(
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
			<p><?php esc_html_e( 'Fill out the fields to connect your Mailchimp account', 'everest-forms-pro' ); ?></p>
			<form>
				<div class="evf-connection-form">
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Mailchimp API Key', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Mailchimp API Key', 'everest-forms-pro' ); ?>" class="evf-apikey">
						</label>
					</div>
					<div class="evf-connection-field">
						<label>
							<strong><?php esc_html_e( 'Mailchimp Nick Name', 'everest-forms-pro' ); ?></strong>
							<input type="text" placeholder="<?php esc_attr_e( 'Enter Mailchimp Nick Name', 'everest-forms-pro' ); ?>" class="evf-nickname">
						</label>
					</div>
				</div>
				<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
					<?php esc_html_e( 'Connect to Mailchimp', 'everest-forms-pro' ); ?>
				</a>
			</form>
		</div>
		<div class="evf-connection-list">
			<table class="evf-connection-list-table">
				<tbody>
					<?php
					if ( ! empty( $connected_lists ) && isset( $connected_lists['mailchimp'] ) ) {
						foreach ( $connected_lists['mailchimp'] as $key => $list ) {
							?>
							<tr>
								<td><strong><?php echo sanitize_text_field( $list['label'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></strong></td>
								<td><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Connected on: %s', 'everest-forms-pro' ), date_i18n( get_option( 'date_format' ), $list['date'] ) ) ); ?></td>
								<td>
									<a href='#' class='disconnect everest-forms-integration-disconnect-account' data-source='mailchimp' data-key='<?php echo esc_attr( $key ); ?>'>
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
	 * Facilitates mailchimp integration. Evoked from extensibility.
	 */
	public function output_integration() {
		$connected_lists = get_option( 'everest_forms_integrations', array() );
		if ( ! empty( $connected_lists['mailchimp'] ) ) {
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
