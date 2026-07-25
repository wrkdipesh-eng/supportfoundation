<?php
/**
 * Trello Settings.
 *
 * @package EverestForms\Trello\Builder
 * @since   1.0.0
 */

 namespace  EverestForms\Pro\Addons\Trello\Builder;

 use EverestForms\Pro\Addons\Trello\Api\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Trello Integration.
 */
class Settings extends  \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'trello';
		$this->name = __( 'Trello', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/Trello/assets/img/Trello.png', EFP_PLUGIN_FILE );

		parent::__construct();
	}

	/**
	 * Get Integration account lists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_lists( $connection_id = '', $account_id = '', $board_id = '' ) {
		$api = $this->api_connect( $account_id );
		try {
			$boards    = $api->get_boards();
			$boards_id = ! empty( $board_id ) ? $board_id : $boards['0']['id'];

			$lists           = $this->get_lists( $account_id, $boards_id );
			$boards_labels   = $this->get_boards_labels( $account_id, $boards_id );
			$formattedBoards = $this->get_boards( $account_id, $boards );
			$members         = $this->get_board_members( $account_id, $boards_id );

			if ( ! empty( $formattedBoards ) ) {
				$boards_array          = $this->format_api_data( $formattedBoards );
				$list_array['boards']  = $boards_array;
				$list_array['lists']   = $lists;
				$list_array['labels']  = $boards_labels;
				$list_array['members'] = $members;
				return $list_array;
			} else {
				$error_msg = __( 'API list error: No lists found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Trello API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}

	/**
	 * Retrieves the formatted boards based on the given account ID and boards array.
	 *
	 * @since 1.0.0
	 *
	 * @param string $account_id The ID of the account.
	 * @param array  $boards The array of boards.
	 * @return array The formatted boards array.
	 */
	protected function get_boards( $account_id, $boards ) {
		$api = $this->api_connect( $account_id );
		if ( ! $api ) {
			return array();
		}

		$formattedBoards = array();

		foreach ( $boards as $board ) {
			if ( is_array( $board ) ) {
				$formattedBoards[ $board['id'] ] = $board['name'];
			}
		}
		return $formattedBoards;
	}

	/**
	 * Connect to API.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $account_id  Account ID.
	 * @return mixed array or error object
	 */
	public function api_connect( $account_id ) {
		if ( ! empty( $this->api ) && $account_id === $this->account ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );

			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) && ! empty( $providers[ $this->id ][ $account_id ]['access_token'] ) ) {
				$api_key       = $providers[ $this->id ][ $account_id ]['api'];
				$access_token  = $providers[ $this->id ][ $account_id ]['access_token'];
				$this->account = $account_id;
				$this->api     = new Api( $api_key, $access_token );
				return $this->api;
			} else {
				return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
			}
		}
	}

	/**
	 * Retrieves the lists associated with a specific board.
	 *
	 * @since 1.0.0
	 *
	 * @param int $account_id The ID of the account.
	 * @param int $board_id The ID of the board.
	 * @return array The formatted lists array.
	 */
	public function get_lists( $account_id, $board_id ) {
		$api = $this->api_connect( $account_id );

		if ( ! $api ) {
			return array();
		}
		$lists = $api->get_lists( $board_id );

		$formatted_lists = array();

		foreach ( $lists as $list ) {
			if ( is_array( $list ) ) {
				$formatted_lists[ $list['id'] ] = $list['name'];
			}
		}

		$list_array = $this->format_api_data( $formatted_lists );

		return $list_array;
	}

	/**
	 * Retrieves the labels of a specific board in the Trello API.
	 *
	 * @since 1.0.0
	 *
	 * @param int $account_id The ID of the Trello account.
	 * @param int $boardId    The ID of the board to retrieve labels from.
	 * @return array An array of formatted labels for the specified board.
	 */
	protected function get_boards_labels( $account_id, $boardId ) {
		$api = $this->api_connect( $account_id );

		if ( ! $api ) {
			return array();
		}

		$labels = $api->get_labels( $boardId );

		if ( is_wp_error( $labels ) ) {
			return array();
		}

		$formattedLabels = array();
		foreach ( $labels as $label ) {
			if ( is_array( $label ) ) {
				$formattedLabels[ $label['id'] ] = $label['name'] ?: $label['color'];
			}
		}

		$list_array = $this->format_api_data( $formattedLabels );

		return $list_array;
	}

	/**
	 * Retrieves the members of a specific board in the Trello API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $account_id The ID of the account to connect to the API.
	 * @param string $boardId The ID of the board to retrieve the members from.
	 * @return array An array containing the formatted members of the board.
	 */
	public function get_board_members( $account_id, $boardId ) {
		$api = $this->api_connect( $account_id );

		if ( ! $api ) {
			return array();
		}

		$members = $api->get_members( $boardId );

		if ( is_wp_error( $members ) ) {
			return array();
		}

		$formattedMembers = array();
		foreach ( $members as $member ) {
			if ( is_array( $member ) ) {
				$formattedMembers[ $member['id'] ] = $member['fullName'] . ' (@' . $member['username'] . ')';
			}
		}

		$list_array = $this->format_api_data( $formattedMembers );

		return $list_array;
	}

	/**
	 * Formats the given data into an array with 'id' and 'name' keys.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The data to be formatted.
	 * @return array The formatted data as an associative array.
	 */
	protected function format_api_data( $data ) {
		if ( ! empty( $data ) ) {
			$list_array = array();
			foreach ( $data as $id => $value ) {
				$list_array[ $id ] = array(
					'id'   => $id,
					'name' => ! empty( $value ) ? $value : __( 'Unknown list', 'everest-forms-pro' ),
				);
			}
		}

		return $list_array;
	}

	/**
	 * Authenticate with the Integration API.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $data    Data passed for API authorization.
	 * @param string $form_id Form ID.
	 * @throws \Exception Exception.
	 * @return mixed id or error object
	 */
	public function authorize_api( $data = array(), $form_id = '' ) {
		$api = new Api( trim( $data['apikey'] ), trim( $data['access_token'] ) );

		if ( ! $api->auth_test() ) {

			return $this->error( __( 'Could not verify API key', 'everest-forms-pro' ) );
		}

		$id           = uniqid();
		$integrations = get_option( 'everest_forms_integrations', array() );

		$integrations[ $this->id ][ $id ] = array(
			'api'          => trim( $data['apikey'] ),
			'access_token' => trim( $data['access_token'] ),
			'label'        => sanitize_text_field( $data['label'] ),
			'date'         => time(),
		);
		update_option( 'everest_forms_integrations', $integrations );

		return $id;
	}


	/**
	 * Fetch Integration account list fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching api fields.
	 * @param string $list_id       List id for fetching api fields.
	 *
	 * @return mixed array or error object
	 */
	public function fetch_api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$fld = array(
				array(
					'id'         => 0,
					'name'       => __( 'Card Title', 'everest-forms-pro' ),
					'req'        => true,
					'field_type' => 'card_title',
					'tag'        => 'card_title',
				),
				array(
					'id'         => 1,
					'name'       => __( 'Email Address', 'everest-forms-pro' ),
					'req'        => true,
					'field_type' => 'email',
					'tag'        => 'email',
				),
				array(
					'id'         => 2,
					'name'       => __( 'Content Description', 'everest-forms-pro' ),
					'req'        => true,
					'field_type' => 'content_description',
					'tag'        => 'content_description',
				),
				array(
					'id'         => 3,
					'name'       => __( 'Vote', 'everest-forms-pro' ),
					'req'        => true,
					'field_type' => 'vote',
					'tag'        => 'vote',
				),
				array(
					'id'         => 4,
					'name'       => __( 'Attachment', 'everest-forms-pro' ),
					'req'        => true,
					'field_type' => 'attachment',
					'tag'        => 'attachment',
				),
			);

		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Trello API Data field error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}
		return $fld;
	}

	/**
	 * Fetch API Groups.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID.
	 * @param string $account_id Account ID.
	 * @param string $list_id List ID.
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		return $this->error( esc_html__( 'Trello won\'t support Groups.', 'everest-forms-pro' ) );
	}

	/**
	 * Process and submit entry to provider.
	 *
	 * @since 1.0.0
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

		$data        = array();
		$desc_email  = '';
		$description = '';
		$vote        = 'no';
		$file        = '';
		$file_name   = '';

		// Fire for each connection.
		foreach ( $form_data['integrations'][ $this->id ] as $connection ) :
			// Check for conditional logic.
			$account_id = $connection['account_id'];
			$api        = $this->api_connect( $account_id );

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

			$data['idList']    = $connection['board_list_id'];
			$data['idMembers'] = $connection['board_member_id'];
			$data['idLabels']  = $connection['board_label_id'];

			if ( is_wp_error( $api ) ) {
				continue;
			}

			$connection['custom_field_value'] = isset( $connection['custom_field_value'] ) ? $connection['custom_field_value'] : array();

			// Setup merge custom fields.
			foreach ( $connection['custom_field_value'] as $name => $custom_field ) {
				$custom_field = explode( '.', $custom_field );
				$id           = $custom_field[0]; // evf Field ID.
				$key          = ! empty( $custom_field[1] ) ? $custom_field[1] : 'value';
				$type         = ! empty( $custom_field[2] ) ? $custom_field[2] : 'text'; // MC merge field type.

				// Check if mapped form field has a value.
				if ( empty( $fields[ $id ][ $key ] ) ) {
					continue;
				}

				$value = $fields[ $id ][ $key ];

				// Format edge cases pre-API call.
				if ( is_array( $value ) ) {
					switch ( $fields[ $id ]['type'] ) {
						case 'radio':
						case 'payment-radio':
							$value = ! empty( $value['label'] ) ? $value['label'] : '';
							break;

						case 'checkbox':
						case 'payment-checkbox':
							$value = ! empty( $value['label'] ) && is_array( $value['label'] ) ? '||' . implode( '||', (array) $value['label'] ) . '||' : $value['label'];
							break;

						case 'address':
							$value = implode( ', ', $value );
							break;

						default:
							$value = implode( ' ', $value );
							break;
					}
				}
				$custom_field_value[ $connection['custom_field'][ $name ] ] = $value;
			}

			// Setup merge fields.
			foreach ( $connection['fields'] as $name => $merge_field ) {
				// Check if merge vars are used.
				if ( empty( $merge_field ) ) {
					continue;
				}

				$merge_field = explode( '.', $merge_field );
				$id          = $merge_field[0]; // evf Field ID.
				$key         = ! empty( $merge_field[1] ) ? $merge_field[1] : 'value';
				$type        = ! empty( $merge_field[2] ) ? $merge_field[2] : 'text'; // MC merge field type.

				// Check if mapped form field has a value.
				if ( empty( $fields[ $id ][ $key ] ) ) {
					continue;
				}

				$value = $fields[ $id ][ $key ];

				// Format edge cases pre-API call.
				if ( is_array( $value ) ) {
					switch ( $fields[ $id ]['type'] ) {
						case 'radio':
						case 'payment-radio':
							$value = ! empty( $value['label'] ) ? $value['name'] . ' : ' . $value['label'] : '';
							break;

						case 'checkbox':
						case 'payment-checkbox':
							$value = ! empty( $value['label'] ) && is_array( $value['label'] ) ? $value['name'] . ' : ||' . implode( '||', (array) $value['label'] ) . '||' : $value['label'];
							break;

						case 'address':
							$value = implode( ', ', $value );
							break;

						case 'select':
							$value = $fields[ $id ]['name'] . ' : ' . $value['0'];
							break;

						default:
							$value = implode( ' ', $value );
							break;
					}
				}

				if ( 'card_title' === $type ) {
					$data['name'] = $value;
				} else {
					if ( 'email' === $type ) {
						$desc_email = 'From : ' . $value . "\n";
					}
					if ( 'content_description' === $type ) {
						$description .= $value . "\n";
					}
					if ( 'vote' === $type ) {
						$vote = $value;
					}
					if ( 'attachment' === $type ) {
						$file      = $value;
						$file_name = basename( $file );
					}
				}
			}
		endforeach;
		$data['desc'] = $description . $desc_email;
		$data['pos']  = 'bottom';
		$response     = $api->add_card( $data );

		if ( 'yes' === $vote ) {
			$api->make_request( 'cards/' . $response['id'] . '/membersVoted', array( 'value' => $response['idMembers']['0'] ), 'POST' );
		}

		if ( ! empty( $file ) ) {
			$api->make_request(
				'cards/' . $response['id'] . '/attachments',
				array(
					'url'  => $file,
					'name' => $file_name,
				),
				'POST'
			);
		}
	}
}
