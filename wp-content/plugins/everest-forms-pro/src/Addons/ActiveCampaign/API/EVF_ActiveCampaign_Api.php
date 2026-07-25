<?php
/**
 * Active Campaign contact api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API;

use EverestForms\Pro\Addons\ActiveCampaign\API\Account\EVF_ActiveCampaign_Account_Api;
use EverestForms\Pro\Addons\ActiveCampaign\API\Contact\EVF_ActiveCampaign_Contact_Api;

class EVF_ActiveCampaign_Api {
	/**
	 * API URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $api_url = null;

	/**
	 * API Key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $api_key = null;

	/**
	 * Instance variable.
	 *
	 * @var EVF_ActiveCampaign_Api
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url API URL.
	 * @param string $api_key API Key.
	 */
	private function __construct( $api_url, $api_key ) {
		$this->api_url = $api_url;
		$this->api_key = $api_key;
	}

	/**
	 * Get the instance of the class
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url API URL.
	 * @param string $api_key API Key.
	 *
	 * @return EVF_ActiveCampaign_Api
	 */
	public static function get_instance( $api_url, $api_key ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $api_url, $api_key );
		}

		return self::$instance;
	}

	/**
	 * Get API URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_api_url() {
		return $this->api_url;
	}

	/**
	 * Get API Key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_api_key() {
		return $this->api_key;
	}

	/**
	 * Get the api instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name
	 *
	 * @return EVF_Abstract_ActiveCampaign_Api
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'account':
				return new EVF_ActiveCampaign_Account_Api( $this->api_url, $this->api_key );
				break;

			case 'authenticate':
				return new EVF_ActiveCampaign_Authenticate_Api( $this->api_url, $this->api_key );
				break;

			case 'automation':
				return new EVF_ActiveCampaign_Automation_Api( $this->api_url, $this->api_key );
				break;

			case 'campaign':
				return new EVF_ActiveCampaign_Campaign_Api( $this->api_url, $this->api_key );
				break;

			case 'contact':
				return new EVF_ActiveCampaign_Contact_Api( $this->api_url, $this->api_key );
				break;

			case 'list':
				return new EVF_ActiveCampaign_List_Api( $this->api_url, $this->api_key );
				break;

			case 'tag':
				return new EVF_ActiveCampaign_Tag_Api( $this->api_url, $this->api_key );
				break;

			case 'note':
				return new EVF_ActiveCampaign_Note_Api( $this->api_url, $this->api_key );
				break;

			default:
				return new \WP_Error( 'invalid_api_instance', esc_html__( 'Invalid api instance' ) );
				break;
		}
	}
}
