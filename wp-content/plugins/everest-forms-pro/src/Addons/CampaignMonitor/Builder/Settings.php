<?php
/**
 * Campaign Monitor Marketting class.
 *
 * @package  EverestForms\Pro\Addons\CampaignMonitor\Builder
 * @version 1.0.0
 * @since   1.0.0
 */
namespace EverestForms\Pro\Addons\CampaignMonitor\Builder;

use EverestForms\Pro\Addons\CampaignMonitor\Settings\Settings as EverestForms_Campaign_Monitor;

defined( 'ABSPATH' ) || exit;

/**
 * Campaign Monitor Marketing class.
 */
class Settings extends \EVF_Email_Marketing {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'campaign_monitor';
		$this->name = __( 'Campaign Monitor', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/CampaignMonitor/assets/img/campaign-monitor-logo.png', EFP_PLUGIN_FILE );

		parent::__construct();
	}

	/**
	 * Process and submit entry to provider.
	 *
	 * @param array $fields    List of form fields.
	 * @param array $entry     User submitted data.
	 * @param array $form_data Prepared form settings.
	 * @param int   $entry_id  Entry ID.
	 */
	public function process_feed( $fields, $entry, $form_data, $entry_id = 0 ) {
		// Only run if this form has connections for this provider.
		if ( empty( $form_data['integrations'][ $this->id ] ) ) {
			return;
		}
		// Fire for each connection.
		foreach ( $form_data['integrations'][ $this->id ] as $connection ) :

			// Check for conditional logic.
			if ( isset( $connection['conditional_logic']['status'] ) ) {
				$con_field_select          = isset( $connection['conditional_logic']['field_select'] ) ? $connection['conditional_logic']['field_select'] : '';
				$con_field_condition       = isset( $connection['conditional_logic']['condition'] ) ? $connection['conditional_logic']['condition'] : '';
				$con_field_input_choice    = isset( $connection['conditional_logic']['input_choice'] ) ? $connection['conditional_logic']['input_choice'] : '';
				$con_field_multiple_choice = isset( $connection['conditional_logic']['multiple_choice'] ) ? $connection['conditional_logic']['multiple_choice'] : '';
				$con_field_country_choice  = isset( $connection['conditional_logic']['country_choice'] ) ? $connection['conditional_logic']['country_choice'] : '';

				if ( ! empty( $con_field_input_choice ) ) {
					$con_field_choice = $con_field_input_choice;
				} elseif ( ! empty( $con_field_multiple_choice ) ) {
					$con_field_choice = $con_field_multiple_choice;
				} elseif ( ! empty( $con_field_country_choice ) ) {
					$con_field_choice = $con_field_country_choice;
				} else {
					$con_field_choice = '';
				}

				if ( 'is' === $con_field_condition ) {
					if ( isset( $entry['form_fields'][ $con_field_select ] ) ) {
						if ( is_array( $entry['form_fields'][ $con_field_select ] ) ) {
							if ( ! in_array( $con_field_choice, $entry['form_fields'][ $con_field_select ], true ) ) {
								continue;
							}
						} else {
							if ( $entry['form_fields'][ $con_field_select ] !== $con_field_choice ) {
								continue;
							}
						}
					} else {
						continue;
					}
				} elseif ( 'is_not' === $con_field_condition ) {
					if ( isset( $entry['form_fields'][ $con_field_select ] ) ) {
						if ( is_array( $entry['form_fields'][ $con_field_select ] ) ) {
							if ( in_array( $con_field_choice, $entry['form_fields'][ $con_field_select ], true ) ) {
								continue;
							}
						} else {
							if ( $entry['form_fields'][ $con_field_select ] === $con_field_choice ) {
								continue;
							}
						}
					}
				}
			}

			$account_id = $connection['account_id'];
			$list_id    = $connection['list_id'];
			$name_data  = explode( '.', $connection['fields']['fullname'] );
			$email_data = explode( '.', $connection['fields']['email'] );
			$data       = array(
				'Name'           => $fields[ $name_data[0] ]['value'],
				'EmailAddress'   => $fields[ $email_data[0] ]['value'],
				'CustomFields'   => array(),
				'ConsentToTrack' => 'yes',
				'Resubscribe'    => true, // Set to false, won't subscribe even new email addresses to CM?
			);
			$api        = $this->api_connect( $account_id );
			if ( is_wp_error( $api ) ) {
				continue;
			}

			// Email is required.
			if ( empty( $data['EmailAddress'] ) ) {
				continue;
			}

			// Setup merge fields.
			foreach ( $connection['fields'] as $name => $merge_field ) {

				// Don't include EMAIL and FULLNAME.
				if ( 'fullname' === $name || 'email' === $name ) {
					continue;
				}

				// Check if merge vars are used.
				if ( empty( $merge_field ) ) {
					continue;
				}

				$merge_field = explode( '.', $merge_field );
				$id          = $merge_field[0]; // evf Field ID.
				$key         = ! empty( $merge_field[1] ) ? $merge_field[1] : 'value';

				// Check if mapped form field has a value.
				if ( empty( $fields[ $id ][ $key ] ) ) {
					continue;
				}

				if ( 'radio' === $fields[ $id ]['type'] || 'checkbox' === $fields[ $id ]['type'] ) {
					$value = $fields[ $id ][ $key ]['label'];
				} else {
					$value = $fields[ $id ][ $key ];
				}

				if ( is_array( $value ) ) {
					foreach ( $value as $val ) {
						$data['CustomFields'][] = array(
							'Key'   => '[' . $name . ']',
							'Value' => $val,
						);
					}
				} else {
					$data['CustomFields'][] = array(
						'Key'   => '[' . $name . ']',
						'Value' => $value,
					);
				}
			}

			// send contact to api.
			try {
				$form_id = isset( $form_data['id'] ) ? $form_data['id'] : 0;
				$this->api[ $account_id ]->subscribe( $list_id, $data, $form_id, $entry_id );
			} catch ( Exception $e ) {
				/*
				 translators: %s: API Authentication Error. */
				EverestForms_Campaign_Monitor::log( sprintf( __( 'Campaign Monitor Subscription error: %s', 'everest-forms-pro' ), $e->getMessage() ), 'error' );

				/* translators: %s: API Authentication Error */
				$error_msg = sprintf( __( 'Campaign Monitor Subscription error: %s', 'everest-forms-pro' ), $e->getMessage() );

				return $this->error( $error_msg );
			}
		endforeach;

	}

	/**
	 * Authenticate with the Integration API.
	 *
	 * @param array  $data    Data passed for API authorization.
	 * @param string $form_id Form ID.
	 *
	 * @return mixed id or error object
	 */
	public function authorize_api( $data = array(), $form_id = '' ) {
		if ( ! class_exists( 'Campaign_Monitor' ) ) {
			require_once dirname( EFP_PLUGIN_FILE ) . '/src/Addons/CampaignMonitor/vendor/campaign-monitor.php';
		}
		// Connect via API.
		$api = new Campaign_Monitor( $data['apikey'], $data['client_id'] );

		try {
			$api->get_lists();
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => $e->getMessage(),
				)
			);
		}
		if ( is_wp_error( $api ) ) {
			$details = true === $api ? $api : __( 'Could not verify API URL', 'everest-forms-pro' );
			/* translators: %s: Error thrown by API authentication issues. */
			EverestForms_Campaign_Monitor::log( sprintf( __( 'Campaign Monitor API error: %s', 'everest-forms-pro' ), $details ), 'error' );
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

		$connected_accounts[ $this->id ][ $to_be_stored_id ] = array(
			'api'       => trim( $data['apikey'] ),
			'client_id' => sanitize_text_field( $data['client_id'] ),
			'label'     => sanitize_text_field( $data['label'] ),
			'date'      => time(),
		);
		update_option( 'everest_forms_integrations', $connected_accounts );

		return $id;
	}

	/**
	 * Connect to API.
	 *
	 * @param  string $account_id  Account ID for Campaign Monitor.
	 * @return mixed array or error object
	 */
	public function api_connect( $account_id ) {
		if ( ! class_exists( 'Campaign_Monitor' ) ) {
			require_once dirname( EFP_PLUGIN_FILE ) . '/src/Addons/CampaignMonitor/vendor/campaign-monitor.php';
		}
		if ( ! empty( $this->api[ $account_id ] ) ) {
			return $this->api[ $account_id ];
		} else {
			$providers = get_option( 'everest_forms_integrations' );
			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) ) {
				$this->api[ $account_id ] = new \Campaign_Monitor( $providers[ $this->id ][ $account_id ]['api'], $providers[ $this->id ][ $account_id ]['client_id'] );
				if ( is_wp_error( $this->api ) ) {
					return;
				}
				return $this->api[ $account_id ];
			} else {
				return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
			}
		}
	}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		if ( is_wp_error( $this->api ) ) {
			return;
		}
		try {
			$lists = $this->api[ $account_id ]->get_lists();

			return $lists;
		} catch ( Exception $e ) {
			/* translators: %s: API Authentication Error. */
			EverestForms_Campaign_Monitor::log( sprintf( __( 'Campaign Monitor API error: %s', 'everest-forms-pro' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error */
			$error_msg = sprintf( __( 'API list error: %s', 'everest-forms-pro' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}

	/**
	 * Get Integration group lists.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 * @param string $list_id       List id for fetching.
	 *
	 * @return mixed array or error object
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		return new \WP_Error( esc_html__( 'Groups do not exist.', 'everest-forms-pro' ) );
	}

	/**
	 * Fetch Integration account list fields.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching api fields.
	 * @param string $list_id       List id for fetching api fields.
	 */
	public function fetch_api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
		$this->api_connect( $account_id );

		try {
			$fields         = $this->api[ $account_id ]->get_list_custom_fields( $list_id );
			$default_fields = array(
				array(
					'name'       => 'Full Name',
					'req'        => false,
					'tag'        => 'fullname',
					'field_type' => 'text',
				),
				array(
					'name'       => 'Email',
					'req'        => true,
					'tag'        => 'email',
					'field_type' => 'email',
				),
			);

			return array_merge( $default_fields, $fields );

		} catch ( Exception $e ) {

			/* translators: %s: API Authentication Error. */
			EverestForms_Campaign_Monitor::log( sprintf( __( 'Campaign Monitor API error: %s', 'everest-forms-pro' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-pro' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}
}
