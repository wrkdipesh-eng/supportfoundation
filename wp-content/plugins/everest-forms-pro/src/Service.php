<?php
/**
 * Integrations Service.
 *
 * @package EverestForms\Pro
 * @since   1.3.7
 */

namespace EverestForms\Pro;

defined( 'ABSPATH' ) || exit;

use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
use EverestForms\Pro\Addons\Moosend\Builder\Settings as Moosend;
use EverestForms\Pro\Addons\Trello\Builder\Settings as Trello;
use EverestForms\Pro\Addons\OnePageCrm\Builder\Settings as OnePageCrm;
use EverestForms\Pro\Addons\iContact\Builder\Settings as iContact;
use EverestForms\Pro\Addons\Square\Builder\Builder as Square;
use EverestForms\Pro\Addons\Square\Settings\Settings as Square_Settings;
use EverestForms\Pro\Addons\WooCommerce\WooCommerce;
use EverestForms\Pro\Addons\MailPoet\MailPoet;
use EverestForms\Pro\Addons\Telegram\Builder\Builder as Telegram;
use EverestForms\Pro\Addons\Salesflare\Builder\Settings as Salesflare;
use EverestForms\Pro\Addons\Brevo\Builder\SendinblueMarketing as Brevo;
use EverestForms\Pro\Addons\LandingPages\LandingPages;
use EverestForms\Pro\Addons\Mailerlite\Builder\Builder as Mailerlite;
use EverestForms\Pro\Addons\Mailchimp\Builder\Builder as Mailchimp;
use EverestForms\Pro\Addons\Aweber\Builder\Builder as Aweber;
use EverestForms\Pro\Addons\SmsNotification\SmsNotification;
use EverestForms\Pro\Addons\Slack\Slack;
use EverestForms\Pro\Addons\Coupons\Coupons;

/**
 * Service class.
 */
class Service {

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance;

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.3.7
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'everest-forms-pro' ), '1.3.7' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.3.7
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'everest-forms-pro' ), '1.3.7' );
	}

	/**
	 * Main plugin class instance.
	 *
	 * Ensures only one instance of the plugin is loaded or can be loaded.
	 *
	 * @return object Main instance of the class.
	 */
	final public static function instance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Plugin Constructor.
	 */
	public function __construct() {
		add_filter( 'everest_forms_integrations', array( $this, 'add_integration' ) );
		add_action( 'init', array( $this, 'everest_forms_addons_init' ) );

		// Integrations - Form settings.
		add_filter( 'everest_forms_integration_uploads', array( $this, 'dropbox_uploads' ), 5, 2 );
		add_filter( 'everest_forms_integration_uploads', array( $this, 'google_drive_uploads' ), 5, 2 );
		add_action( 'everest_forms_inline_integrations_settings', array( $this, 'integrations_settings' ), 10, 2 );
		add_action( 'everest_forms_process_complete', array( $this, 'send_pdf_google_drive' ), 10, 4 );
		add_action( 'everest_forms_process_complete', array( $this, 'send_pdf_dropbox' ), 10, 4 );
	}

	/**
	 * Initializes the Everest Forms addons.
	 *
	 * @since 1.0.0
	 */
	public function everest_forms_addons_init() {

		if ( ! function_exists( 'evf_get_license_plan' ) || ! evf_get_license_plan() ) {
			return;
		}

		$enabled_features = get_option( 'everest_forms_enabled_features', array() );
		if ( empty( $enabled_features ) ) {
			return;
		}
		$classes = array(
			'moosend'           => '\EverestForms\Pro\Addons\Moosend\Builder\Settings',
			'woocommerce'       => '\EverestForms\Pro\Addons\WooCommerce\WooCommerce',
			'trello'            => '\EverestForms\Pro\Addons\Trello\Builder\Settings',
			'onepagecrm'        => '\EverestForms\Pro\Addons\OnePageCrm\Builder\Settings',
			'icontact'          => '\EverestForms\Pro\Addons\iContact\Builder\Settings',
			'mailpoet'          => '\EverestForms\Pro\Addons\MailPoet\MailPoet',
			'telegram'          => '\EverestForms\Pro\Addons\Telegram\Telegram',
			'drip'              => '\EverestForms\Pro\Addons\Drip\Builder\DripMarketing',
			'brevo'             => '\EverestForms\Pro\Addons\Brevo\Builder\SendinblueMarketing',
			'getresponse'       => '\EverestForms\Pro\Addons\GetResponse\Builder\GetResponseMarketing',
			'convertkit'        => '\EverestForms\Pro\Addons\ConvertKit\Builder\Settings',
			'salesflare'        => '\EverestForms\Pro\Addons\Salesflare\Builder\Settings',
			'constant-contact'  => '\EverestForms\Pro\Addons\ConstantContact\Builder\Settings',
			'pipedrive'         => '\EverestForms\Pro\Addons\PipeDrive\Builder\Settings',
			'square'            => '\EverestForms\Pro\Addons\Square\Square',
			'airtable'          => '\EverestForms\Pro\Addons\Airtable\Builder\Settings',
			'landingpages'      => '\EverestForms\Pro\Addons\LandingPages\LandingPages',
			'mailerlite'        => '\EverestForms\Pro\Addons\Mailerlite\Builder\Builder',
			'mailchimp'         => '\EverestForms\Pro\Addons\Mailchimp\Builder\Builder',
			'mollie'            => '\EverestForms\Pro\Addons\Mollie\Mollie',
			'calculations'      => '\EverestForms\Pro\Addons\Calculation\Calculation',
			'aweber'            => '\EverestForms\Pro\Addons\Aweber\Builder\Builder',
			'sms-notifications' => '\EverestForms\Pro\Addons\SmsNotification\SmsNotification',
			'slack'             => '\EverestForms\Pro\Addons\Slack\Slack',
			'user-registration' => '\EverestForms\Pro\Addons\UserRegistration\UserRegistration',
			'activecampaign'    => '\EverestForms\Pro\Addons\ActiveCampaign\Builder\Settings',
			'getgist'           => '\EverestForms\Pro\Addons\GetGist\Builder\Builder',
			'campaign-monitor'  => '\EverestForms\Pro\Addons\CampaignMonitor\Builder\Settings',
			'cleverreach'       => '\EverestForms\Pro\Addons\CleverReach\Builder\Builder',
			'coupons'           => '\EverestForms\Pro\Addons\Coupons\Coupons',
			'qr-generator'      => '\EverestForms\Pro\Addons\QRGenerator\QRGenerator',
			'amocrm'            => '\EverestForms\Pro\Addons\AmoCRM\Builder\Settings',
		);
		foreach ( $classes as $key => $class_name ) {
			$key = 'everest-forms-' . $key;
			if ( in_array( $key, $enabled_features, true ) ) {

				if ( class_exists( $class_name ) ) {
					new $class_name();
				}
			}
		}
	}

	/**
	 * Send the Pdf to google drive.
	 *
	 * @since 1.9.0
	 *
	 * @param array $fields    Fields for the Form.
	 * @param array $entry     Form Entry.
	 * @param array $form_data Form Data object.
	 * @param int   $entry_id  Entry Identifier.
	 */
	public function send_pdf_dropbox( $fields, $entry, $form_data, $entry_id ) {

		if ( isset( $form_data['settings']['dropbox_enabled'], $form_data['settings']['enable_send_pdf_dropbox'] ) && '1' === $form_data['settings']['dropbox_enabled'] && '1' === $form_data['settings']['enable_send_pdf_dropbox'] ) {
			$client        = new \EverestForms\Pro\Integrations\Dropbox();
			$dropbox       = $client->get_client();
			$path          = isset( $form_data['settings']['dropbox_destination_path'] ) ? rtrim( $form_data['settings']['dropbox_destination_path'], '/' ) : '';
			$form_title    = isset( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : '';
			$file_name     = $form_title . '-' . uniqid() . '.pdf';
			$file_location = apply_filters( 'everest_forms_send_pdf_to_google_drive_or_dropbox', $attachment = '', $fields, $form_data, $entry_id );
			// Check folder exists or create it.
			if ( ! empty( $path ) && '/' !== $path ) {
				try {
					// Get folder metadata.
					$destination_folder = $dropbox->getMetadata( $path );

					// If destination folder is not a folder, return current file URL.
					if ( 'folder' !== $destination_folder->{'.tag'} ) {
						$logger = evf_get_logger();
						$logger->error(
							esc_html__( 'Unable to upload file because destination is not a folder.', 'everest-forms-pro' ),
							array(
								'source' => 'file-upload',
							)
						);
					}
				} catch ( \Exception $e ) {
					// Folder does not exist. Try to create it.
					try {
						// Create folder.
						$dropbox->createFolder( $path );
					} catch ( \Exception $e ) {
						// Log that folder could not be created.
						$logger = evf_get_logger();
						$logger->error(
							sprintf( 'Unable to upload file because destination folder could not be created: %s', $e->getMessage() ),
							array(
								'source' => 'file-upload',
							)
						);
					}
				}
			}

			try {
				$dropboxFile  = new DropboxFile( $file_location );
				$uploadedFile = $dropbox->upload( $dropboxFile, trailingslashit( $path ) . $file_name, array( 'autorename' => true ) );
				try {
					$shareable_links = $dropbox->postToAPI(
						'/sharing/create_shared_link_with_settings',
						array(
							'path'     => $uploadedFile->getPathDisplay(),
							'settings' => array(
								'requested_visibility' => 'public',
							),
						)
					);

						$file_data = $shareable_links->getDecodedBody();

					if ( isset( $form_data['settings']['enable_send_file_directly_to_cloud'] ) && '1' === $form_data['settings']['enable_send_file_directly_to_cloud'] ) {
						do_action( 'everest_forms_send_file_directly_to_cloud', $file_location );
					}
				} catch ( DropboxClientException $e ) {
					$shareable_links = $dropbox->postToAPI(
						'/sharing/list_shared_links',
						array(
							'path' => $uploadedFile->getPathDisplay(),
						)
					);

					// Get existing sharable links.
					$file_data = $shareable_links->getDecodedBody();

					// If links were found, return first link.
					if ( ! empty( $file_data['links'] ) ) {
						if ( isset( $form_data['settings']['enable_send_file_directly_to_cloud'] ) && '1' === $form_data['settings']['enable_send_file_directly_to_cloud'] ) {
							do_action( 'everest_forms_send_file_directly_to_cloud', $file_location );
						}
					}
				}
			} catch ( DropboxClientException $e ) {
				// Log that folder could not be created.
				$logger = evf_get_logger();
				$logger->error(
					$e->getMessage(),
					array(
						'source' => 'send-pdf-dropbox',
					)
				);
			}
		}
	}

	/**
	 * Send the Pdf to google drive.
	 *
	 * @since 1.9.0
	 *
	 * @param array $fields    Fields for the Form.
	 * @param array $entry     Form Entry.
	 * @param array $form_data Form Data object.
	 * @param int   $entry_id  Entry Identifier.
	 */
	public function send_pdf_google_drive( $fields, $entry, $form_data, $entry_id ) {
		if ( isset( $form_data['settings']['google_drive_enabled'], $form_data['settings']['enable_send_pdf_google_drive'] ) && '1' === $form_data['settings']['google_drive_enabled'] && '1' === $form_data['settings']['enable_send_pdf_google_drive'] ) {
			$custom_form_title = isset( $form_data['settings']['pdf_submission']['everest_forms_pdf_custom_file_name'] ) ? $form_data['settings']['pdf_submission']['everest_forms_pdf_custom_file_name'] : '';
			$client            = new \EverestForms\Pro\Integrations\GoogleDrive();
			$service           = new \Google_Service_Drive( $client->get_client() );
			$form_title        = isset( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : '';

			if ( '' !== $custom_form_title ) {
				$file_name = sanitize_file_name( apply_filters( 'everest_forms_process_smart_tags', $custom_form_title, $form_data, $fields, $entry_id ) );
			} else {
				$file_name = $form_title . '-' . uniqid();
			}

			$fileMetadata = $this->create_drive_file( $file_name, $form_data['settings']['google_drive_destination_path'], $service );
			$content      = file_get_contents( apply_filters( 'everest_forms_send_pdf_to_google_drive_or_dropbox', $attachment = '', $fields, $form_data, $entry_id ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			$created_file = $service->files->create(
				$fileMetadata,
				array(
					'data'       => $content,
					'uploadType' => 'multipart',
					'fields'     => 'id',
				)
			);

				$response = $service->files->get( $created_file->id, array( 'fields' => 'id, webContentLink' ) );
			if ( $response && isset( $response->webContentLink ) ) {
				if ( isset( $form_data['settings']['enable_send_file_directly_to_cloud'] ) && '1' === $form_data['settings']['enable_send_file_directly_to_cloud'] ) {
					do_action( 'everest_forms_send_file_directly_to_cloud', $content );
				}
			}
		}
	}

	/**
	 * Register integration.
	 *
	 * @param array $integrations List of integrations.
	 */
	public function add_integration( $integrations ) {
		$integrations[] = 'EverestForms\Pro\Integrations\Dropbox';
		$integrations[] = 'EverestForms\Pro\Integrations\GoogleDrive';
		$integrations[] = 'EverestForms\Pro\Integrations\GoogleCalendar';

		if ( ! evf_get_license_plan() ) {
			return $integrations;
		}

		$enabled_features = get_option( 'everest_forms_enabled_features', array() );
		if ( empty( $enabled_features ) ) {
			return $integrations;
		}
		$classes = array(
			'moosend'           => 'EverestForms\Pro\Addons\Moosend\Settings\Settings',
			'trello'            => 'EverestForms\Pro\Addons\Trello\Settings\Settings',
			'onepagecrm'        => 'EverestForms\Pro\Addons\OnePageCrm\Settings\Settings',
			'icontact'          => 'EverestForms\Pro\Addons\iContact\Settings\Settings',
			'mailpoet'          => 'EverestForms\Pro\Addons\MailPoet\Admin\Integration\MailPoetIntegration',
			'drip'              => 'EverestForms\Pro\Addons\Drip\Settings\Settings',
			'brevo'             => 'EverestForms\Pro\Addons\Brevo\Settings\Settings',
			'getresponse'       => 'EverestForms\Pro\Addons\GetResponse\Settings\Settings',
			'convertkit'        => 'EverestForms\Pro\Addons\ConvertKit\Settings\Settings',
			'salesflare'        => 'EverestForms\Pro\Addons\Salesflare\Settings\Settings',
			'telegram'          => '\EverestForms\Pro\Addons\Telegram\Settings\Settings',
			'constant-contact'  => 'EverestForms\Pro\Addons\ConstantContact\Settings\Settings',
			'pipedrive'         => 'EverestForms\Pro\Addons\PipeDrive\Settings\Settings',
			'airtable'          => 'EverestForms\Pro\Addons\Airtable\Settings\Settings',
			'mailerlite'        => 'EverestForms\Pro\Addons\Mailerlite\Settings\Settings',
			'mailchimp'         => 'EverestForms\Pro\Addons\Mailchimp\Settings\Settings',
			'aweber'            => 'EverestForms\Pro\Addons\Aweber\Settings\Settings',
			'getgist'           => 'EverestForms\Pro\Addons\GetGist\Settings\Settings',
			'activecampaign'    => '\EverestForms\Pro\Addons\ActiveCampaign\Settings\Settings',
			'campaign-monitor'  => 'EverestForms\Pro\Addons\CampaignMonitor\Settings\Settings',
			'cleverreach'       => 'EverestForms\Pro\Addons\CleverReach\Settings\Settings',
			'sms-notifications' => 'EverestForms\Pro\Addons\SmsNotification\Admin\Settings',
			'amocrm'            => 'EverestForms\Pro\Addons\AmoCRM\Settings\Settings',
		);
		foreach ( $classes as $key => $class_name ) {
			$key = 'everest-forms-' . $key;
			if ( in_array( $key, $enabled_features, true ) ) {
				$integrations[] = $class_name;
			}
		}

		// Includes the ClickSend Settings if SMS Notification is enabled.
		if ( in_array( 'everest-forms-sms-notifications', $enabled_features, true ) ) {
			$integrations[] = 'EverestForms\Pro\Addons\SmsNotification\Admin\ClickSendSettings';
		}
		return $integrations;
	}

	/**
	 * Upload files to Dropbox.
	 *
	 * @param array $file      File data.
	 * @param array $form_data Form Data.
	 */
	public function dropbox_uploads( $file, $form_data ) {

		if ( isset( $form_data['settings']['dropbox_enabled'] ) && '1' === $form_data['settings']['dropbox_enabled'] ) {
			$client  = new \EverestForms\Pro\Integrations\Dropbox();
			$dropbox = $client->get_client();
			$path    = isset( $form_data['settings']['dropbox_destination_path'] ) ? rtrim( $form_data['settings']['dropbox_destination_path'], '/' ) : '';

			// Check folder exists or create it.
			if ( ! empty( $path ) && '/' !== $path ) {
				try {
					// Get folder metadata.
					$destination_folder = $dropbox->getMetadata( $path );

					// If destination folder is not a folder, return current file URL.
					if ( 'folder' !== $destination_folder->{'.tag'} ) {
						$logger = evf_get_logger();
						$logger->error(
							esc_html__( 'Unable to upload file because destination is not a folder.', 'everest-forms-pro' ),
							array(
								'source' => 'file-upload',
							)
						);
					}
				} catch ( \Exception $e ) {
					// Folder does not exist. Try to create it.
					try {
						// Create folder.
						$dropbox->createFolder( $path );
					} catch ( \Exception $e ) {
						// Log that folder could not be created.
						$logger = evf_get_logger();
						$logger->error(
							sprintf( 'Unable to upload file because destination folder could not be created: %s', $e->getMessage() ),
							array(
								'source' => 'file-upload',
							)
						);
						return $file;
					}
				}
			}

			try {
				// Create Dropbox File from Path.
				$dropboxFile = new DropboxFile( $file['tmp_path'] );

				// Upload the file to Dropbox.
				$uploadedFile = $dropbox->upload( $dropboxFile, trailingslashit( $path ) . $file['file_name_new'], array( 'autorename' => true ) );

				try {
					$shareable_links = $dropbox->postToAPI(
						'/sharing/create_shared_link_with_settings',
						array(
							'path'     => $uploadedFile->getPathDisplay(),
							'settings' => array(
								'requested_visibility' => 'public',
							),
						)
					);

					$file_data = $shareable_links->getDecodedBody();

					// Get the dropbox URL to the file.
					if ( ! empty( $file_data['url'] ) ) {
						if ( isset( $form_data['settings']['enable_send_file_directly_to_cloud'] ) && '1' === $form_data['settings']['enable_send_file_directly_to_cloud'] ) {
							do_action( 'everest_forms_send_file_directly_to_cloud', $file['tmp_path'] );
							return array_merge(
								$file,
								array(
									'external' => 'dropbox',
									'file_url' => esc_url_raw( $file_data['url'] ),
								)
							);
						}
					}
				} catch ( DropboxClientException $e ) {
					try {
						$shareable_links = $dropbox->postToAPI(
							'/sharing/list_shared_links',
							array(
								'path' => $uploadedFile->getPathDisplay(),
							)
						);

						// Get existing sharable links.
						$file_data = $shareable_links->getDecodedBody();

						// If links were found, return first link.
						if ( ! empty( $file_data['links'] ) ) {
							if ( isset( $form_data['settings']['enable_send_file_directly_to_cloud'] ) && '1' === $form_data['settings']['enable_send_file_directly_to_cloud'] ) {
								do_action( 'everest_forms_send_file_directly_to_cloud', $file['tmp_path'] );
								return array_merge(
									$file,
									array(
										'external' => 'dropbox',
										'file_url' => esc_url_raw( $file_data['links'][0]['url'] ),
									)
								);
							}
						}
					} catch ( DropboxClientException $e ) {
						// Log that folder could not be created.
						$logger = evf_get_logger();
						$logger->error(
							/* translators: %s: message */
							sprintf( esc_html__( 'Unable to create shareable link for file: %s', 'everest-forms-pro' ), $e->getMessage() ),
							array(
								'source' => 'file-upload',
							)
						);
						return $file;
					}
				}
			} catch ( DropboxClientException $e ) {
				// Log that folder could not be created.
				$logger = evf_get_logger();
				$logger->error(
					$e->getMessage(),
					array(
						'source' => 'file-upload',
					)
				);
				return $file;
			}
		}

		return $file;
	}

	/**
	 * Upload files to Google Drive.
	 *
	 * @param array $file      File data.
	 * @param array $form_data Form Data.
	 */
	public function google_drive_uploads( $file, $form_data ) {

		if ( isset( $form_data['settings']['google_drive_enabled'] ) && '1' === $form_data['settings']['google_drive_enabled'] ) {
			$client  = new \EverestForms\Pro\Integrations\GoogleDrive();
			$service = new \Google_Service_Drive( $client->get_client() );
			try {
				$fileMetadata = $this->create_drive_file( $file['file_name_new'], $form_data['settings']['google_drive_destination_path'], $service );
				$content      = file_get_contents( $file['tmp_path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

				$created_file = $service->files->create(
					$fileMetadata,
					array(
						'data'       => $content,
						'uploadType' => 'multipart',
						'fields'     => 'id',
					)
				);

				$response = $service->files->get( $created_file->id, array( 'fields' => 'id, webContentLink' ) );
				// Get the drive URL to the file.
				if ( $response && isset( $response->webContentLink ) ) {
					if ( isset( $form_data['settings']['enable_send_file_directly_to_cloud'] ) && '1' === $form_data['settings']['enable_send_file_directly_to_cloud'] ) {
						do_action( 'everest_forms_send_file_directly_to_cloud', $file['tmp_path'] );
						return array_merge(
							$file,
							array(
								'external' => 'google drive',
								'file_url' => esc_url_raw( $response['webContentLink'] ),
							)
						);
					}

					$logger = evf_get_logger();
					$logger->info(
						'File uploaded Successfully to Google Drive.',
						array(
							'source' => 'file-upload',
						)
					);
				}
			} catch ( \Google_Exception $e ) {
				// Log that folder could not be created.
				$logger = evf_get_logger();
				$logger->error(
					$e->getMessage(),
					array(
						'source' => 'file-upload',
					)
				);
				return $file;
			}
		}

		return $file;
	}

	/**
	 * Create Google Drive file object.
	 *
	 * @param string $filename File name.
	 * @param string $path     File path.
	 * @param object $service  Drive service.
	 *
	 * @return \Google_Service_Drive_DriveFile
	 */
	public function create_drive_file( $filename, $path, $service ) {
		$args = array(
			'name' => $filename,
		);

		// Check folder exists or create it.
		if ( ! empty( $path ) && '/' !== $path ) {
			$folder_id       = $this->create_or_get_directory( $path, $service );
			$args['parents'] = array( $folder_id );
		}

		return new \Google_Service_Drive_DriveFile( $args );
	}

	/**
	 * Create or get directory.
	 *
	 * @param string $path    File path.
	 * @param object $service Drive service.
	 */
	public function create_or_get_directory( $path, $service ) {
		$directory_ids = $this->get_directory_id( $path, $service );

		$parent_id = null;
		foreach ( $directory_ids as $name => $file_id ) {
			if ( is_null( $file_id ) ) {
				// Create Dir.
				$args = array(
					'mimeType' => 'application/vnd.google-apps.folder',
					'name'     => $name,
				);

				if ( $parent_id ) {
					$args['parents'] = array( $parent_id );
				}
				$dir     = new \Google_Service_Drive_DriveFile( $args );
				$file    = $service->files->create( $dir );
				$file_id = $file->id;

				$directory_ids[ $name ] = $file_id;
			}

			$parent_id = $file_id;
		}

		return $parent_id;
	}

	/**
	 * Get directory IDs.
	 *
	 * @param string $path    File path.
	 * @param object $service Drive service.
	 */
	protected function get_directory_id( $path, $service ) {
		$directories   = array_filter( explode( '/', $path ) );
		$directory_ids = array_fill_keys( $directories, null );

		$current_directories = $this->get_all_directories( $service );

		$parents = array();
		foreach ( $directories as $key => $directory ) {
			$parent = isset( $parents[ $key ] ) ? $parents[ $key ] : null;

			$found_directory = $this->find_directory( $directory, $parent, $current_directories );
			if ( ! $found_directory ) {
				continue;
			}

			$directory_ids[ $directory ] = $found_directory;

			$next_key = $key + 1;
			if ( isset( $directories[ $next_key ] ) ) {
				$parents[ $key + 1 ] = $found_directory;
			}
		}

		return $directory_ids;
	}

	/**
	 * Get all Google Drive directories.
	 *
	 * @param object $service Drive service.
	 *
	 * @return array of Google drive directories.
	 */
	protected function get_all_directories( $service ) {
		$token           = '';
		$all_directories = array();

		do {
			$args = array(
				'fields'                    => 'nextPageToken, files(id, name, parents)',
				'q'                         => 'mimeType = \'application/vnd.google-apps.folder\' AND trashed != true',
				'pageSize'                  => 100,
				'supportsAllDrives'         => true,
				'includeItemsFromAllDrives' => true,
			);

			if ( $token ) {
				$args['pageToken'] = $token;
			}

			$directories     = $service->files->listFiles( $args );
			$all_directories = array_merge( $all_directories, $directories->getFiles() );
			$token           = $directories->getNextPageToken();
		} while ( ! is_null( $token ) );

		$directories = array();
		foreach ( $all_directories as $file ) {
			$id = $file->getID();

			$directories[ $id ] = array(
				'id'      => $id,
				'name'    => $file->getName(),
				'parents' => $file->getParents(),
			);
		}

		return $directories;
	}

	/**
	 * Find Google Drive directory.
	 *
	 * @param string $name        Directory name to find.
	 * @param string $parent      Parent ID of directory.
	 * @param array  $directories List of all directories.
	 *
	 * @return string|bool Found directory ID or false.
	 */
	protected function find_directory( $name, $parent, $directories ) {
		foreach ( $directories as $id => $directory ) {
			if ( strtolower( $name ) !== strtolower( $directory['name'] ) ) {
				continue;
			}

			if ( ! $parent && ( is_null( $directory['parents'] ) || ! isset( $directories[ $directory['parents'][0] ] ) ) ) {
				return $id;
			}

			if ( $parent && in_array( $parent, $directory['parents'], true ) ) {
				return $id;
			}
		}

		return false;
	}

	/**
	 * Integrations Settings.
	 *
	 * @param array $form_data Form Data.
	 * @param array $settings  Form Settings.
	 */
	public function integrations_settings( $form_data, $settings ) {
		echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'External Upload Path', 'everest-forms' ) . '</h4>';
		everest_forms_panel_field(
			'toggle',
			'settings',
			'dropbox_enabled',
			$form_data,
			esc_html__( 'Dropbox File path', 'everest-forms' ),
			array(
				'default' => isset( $settings['dropbox_enabled'] ) ? $settings['dropbox_enabled'] : 0,
				'tooltip' => esc_html__( 'Custom directory for the files to be uploaded to in your Dropbox /Apps/Everest Forms/ folder.', 'everest-forms-pro' ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'dropbox_destination_path',
			$form_data,
			'',
			array(
				'class'   => isset( $settings['dropbox_enabled'] ) && '1' === $settings['dropbox_enabled'] ? '' : 'everest-forms-hidden',
				'default' => isset( $settings['dropbox_destination_path'] ) ? $settings['dropbox_destination_path'] : '',
			)
		);
		everest_forms_panel_field(
			'toggle',
			'settings',
			'google_drive_enabled',
			$form_data,
			esc_html__( 'Google Drive File path', 'everest-forms' ),
			array(
				'default' => isset( $settings['google_drive_enabled'] ) ? $settings['google_drive_enabled'] : 0,
				'tooltip' => esc_html__( 'Custom directory for the files to be uploaded to in Google Drive.', 'everest-forms-pro' ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'google_drive_destination_path',
			$form_data,
			'',
			array(
				'class'   => isset( $settings['google_drive_enabled'] ) && '1' === $settings['google_drive_enabled'] ? '' : 'everest-forms-hidden',
				'default' => isset( $settings['google_drive_destination_path'] ) ? $settings['google_drive_destination_path'] : '',
			)
		);

		do_action( 'everest_forms_external_file_path', $form_data, $settings );

		echo '</div>';
	}
}
