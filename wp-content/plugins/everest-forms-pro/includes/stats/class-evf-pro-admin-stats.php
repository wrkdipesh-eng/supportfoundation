<?php
/**
 * Class
 *
 * Everest_Forms_Pro_Admin_Stats
 *
 * @package Everest_Forms_Pro
 * @since  1.6.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Everest_Forms_Pro_Admin_Stats' ) ) {

	/**
	 * Everest_Forms_Pro_Admin_Stats class.
	 */
	class Everest_Forms_Pro_Admin_Stats {
		/**
		 * Remote URl Constant.
		 */
		const REMOTE_URL = 'https://stats.wpeverest.com/wp-json/tgreporting/v1/process-premium/';

		/**
		 * Constructor of the class.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'on_update_request' ), 4 );
			add_action( 'activated_plugin', array( $this, 'on_addon_activate' ), 20, 2 ); // Hook on plugin activation ( Our EVF addons activation ).
			add_action( 'upgrader_process_complete', array( $this, 'on_addon_update' ), 20, 2 ); // Hook on plugin activation ( Our EVF addons activation ).

			/**
			 * Enable module tracking.
			 *
			 * @since 1.7.9
			 */
			add_action( 'evf_feature_track_data_for_tg_user_tracking', array( $this, 'on_module_activate' ) ); // Hook on module activation ( Our EVF module activation ).
		}

		/**
		 * Track module installation data.
		 *
		 * @since 1.7.9
		 *
		 * @param  string $slug Slug.
		 */
		public function on_module_activate( $slug ) {
			$our_modules  = $this->get_modules();
			$module_lists = wp_list_pluck( $our_modules, 'slug' );

			if ( ! in_array( $slug, $module_lists, true ) ) {
				return;
			}

			$this->call_api();
		}

		/**
		 * Get product license key.
		 */
		public function get_base_product_license() {
			return get_option( 'everest-forms-pro_license_key' );
		}

		/**
		 * Get Pro addon file.
		 */
		public function get_base_product() {
			return 'everest-forms-pro/everest-forms-pro.php';
		}

		/**
		 * Get all modules.
		 *
		 * @since 1.7.9
		 */
		public function get_modules() {
			$all_extension = file_get_contents( evf()->plugin_path() . '/assets/extensions-json/sections/all_extensions.json' );
			$module_lists  = array();

			if ( evf_is_json( $all_extension ) ) {
				$module_lists = json_decode( $all_extension, true );
			}
			return isset( $module_lists['features'] ) ? $module_lists['features'] : array();
		}

		/**
		 * Get all addons.
		 */
		public function get_addons() {
			$addon_json  = file_get_contents( evf()->plugin_path() . '/assets/extensions-json/sections/all_extensions.json' );
			$addon_lists = array();

			if ( evf_is_json( $addon_json ) ) {
				$addon_lists = json_decode( $addon_json, true );
			}
			return isset( $addon_lists['products'] ) ? $addon_lists['products'] : array();
		}

		/**
		 * Get all addons List.
		 *
		 * @param array $custom_list_of_addons addon list.
		 */
		public function get_addon_lists( $custom_list_of_addons = array() ) {

			if ( count( $custom_list_of_addons ) < 1 ) {

				$our_addons    = $this->get_addons();
				$product_lists = wp_list_pluck( $our_addons, 'slug' );

			} else {
				$product_lists = $custom_list_of_addons;
			}

			$active_plugins   = get_option( 'active_plugins', array() );
			$enabled_features = get_option( 'everest_forms_enabled_features', array() );

			$addons_data = array(
				$this->get_base_product() => array(
					'product_name'    => __( 'Everest Forms Pro', 'everest-forms-pro' ),
					'product_version' => EFP_VERSION,
					'product_meta'    => array( 'license_key' => $this->get_base_product_license() ),
					'product_type'    => 'plugin',
					'product_slug'    => $this->get_base_product(),
					'is_premium'      => 1
				)
			);

			foreach ( $active_plugins as $plugin ) {
				$plugin_array = explode( '/', $plugin );
				$plugin_item  = isset( $plugin_array[0] ) ? $plugin_array[0] : '';

				if ( in_array( $plugin_item, $product_lists, true ) ) {
					$addon_file      = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin;
					$addon_file_data = get_plugin_data( $addon_file );

					$addons_data[ $plugin ] = array(
						'product_name'    => isset( $addon_file_data['Name'] ) ? trim( $addon_file_data['Name'] ) : '',
						'product_version' => isset( $addon_file_data['Version'] ) ? trim( $addon_file_data['Version'] ) : '',
						'product_type'    => 'plugin',
						'product_slug'    => $plugin,
						'is_premium'      => 1
					);
				}
			}

			if ( ! empty( $enabled_features ) ) {
				$our_modules     = $this->get_modules();
				$modules_by_slug = array_column( $our_modules, null, 'slug' );
				foreach ( $enabled_features as $slug ) {
					if ( isset( $modules_by_slug[ $slug ] ) ) {
						$module               = $modules_by_slug[ $slug ];
						$addons_data[ $slug ] = array(
							'product_name'    => $module['name'],
							'product_version' => EFP_VERSION,
							'product_type'    => 'module',
							'product_slug'    => $slug,
							'is_premium'      => 1
						);
					}
				}
			}

			return $addons_data;
		}

		/**
		 * Send Request for old users before 1.6.3
		 */
		public function on_update_request() {

			$license_key = $this->get_base_product_license();

			if ( '' === $license_key ) {
				return;
			}
			$update_only_before_version = '1.6.3';
			$one_time_requested         = get_option( 'everest_forms_pro_stats_one_time_requested', false );

			if ( $one_time_requested ) {
				return;
			}

			if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( EFP_VERSION, $update_only_before_version, '<' ) ) {
				$this->call_api();
				update_option( 'everest_forms_pro_stats_one_time_requested', true );
			}
		}

		/**
		 * Send Request on addon activated.
		 *
		 * @param string $plugin Plugin.
		 * @param mixed  $network_wide Network.
		 */
		public function on_addon_activate( $plugin, $network_wide ) {
			$plugin_array = explode( '/', $plugin );
			$plugin_item  = isset( $plugin_array[0] ) ? $plugin_array[0] : '';

			if ( '' === $plugin_item ) {
				return;
			}
			$our_addons  = $this->get_addons();
			$addon_lists = wp_list_pluck( $our_addons, 'slug' );

			if ( ! in_array( $plugin_item, $addon_lists, true ) ) {
				return;
			}

			$this->call_api();
		}

		/**
		 * Send Request on addon update.
		 *
		 * @param \WP_Upgrader $upgrader Upgrader.
		 * @param array        $hooks_extra Hook.
		 */
		public function on_addon_update( \WP_Upgrader $upgrader, $hooks_extra ) {
			$action = isset( $hooks_extra['action'] ) ? $hooks_extra['action'] : '';

			if ( 'update' !== $action ) {
				return;
			}
			$type = isset( $hooks_extra['type'] ) ? $hooks_extra['type'] : '';

			if ( 'plugin' !== $type ) {
				return;
			}

			$update_plugins = isset( $hooks_extra['plugins'] ) ? $hooks_extra['plugins'] : array();
			$update_plugin  = isset( $hooks_extra['plugin'] ) ? $hooks_extra['plugin'] : '';
			$bulk           = isset( $hooks_extra['bulk'] ) ? (bool) $hooks_extra['bulk'] : false;

			if ( '' !== $update_plugin ) {
				array_push( $update_plugins, $update_plugin );
			}

			$addons          = $this->get_addons();
			$addon_lists     = wp_list_pluck( $addons, 'slug' );
			$our_addon_files = array();

			foreach ( $update_plugins as $plugin ) {
				$plugin_array = explode( '/', $plugin );
				$plugin_item  = isset( $plugin_array[0] ) ? $plugin_array[0] : '';

				if ( in_array( $plugin_item, $addon_lists, true ) ) {
					$our_addon_files[] = $plugin_item;
				}
			}

			if ( 1 > count( $our_addon_files ) ) {
				return;
			}

			if ( $bulk ) {
				$this->call_api();
			} else {
				$this->call_api( $our_addon_files );
			}
		}
		/**
		 * Get api stats url.
		 *
		 * @return string
		 */
		private function get_stats_api_url() {
			$url = '';
			// Ingore for development mode.
			if ( defined( 'EVF_DEV' ) && EVF_DEV ) {
				if ( defined( 'TG_USERS_TRACKING_VERSION' ) ) {
					$url = rest_url() . 'tgreporting/v1/process-premium/';
				};
			} else {
				$url = self::REMOTE_URL;
			}

			return $url;
		}
		/**
		 * Call API.
		 *
		 * @param array $custom_list_of_addons Custom addon List.
		 */
		public function call_api( $custom_list_of_addons = array() ) {
			$stats_api_url = $this->get_stats_api_url();

			if ( '' === $stats_api_url ) {
				return;
			}

			$data                 = array();
			$data['product_data'] = $this->get_addon_lists( $custom_list_of_addons );
			$data['admin_email']  = get_bloginfo( 'admin_email' );
			$data['website_url']  = get_bloginfo( 'url' );
			$data['wp_version']   = get_bloginfo( 'version' );
			$data['base_product'] = $this->get_base_product();
			$this->send_request( $stats_api_url, apply_filters( 'everest_forms_pro_stats_data', $data ) );
		}

		/**
		 * Send Request to API.
		 *
		 * @param string $url URL.
		 * @param array  $data Data.
		 */
		public function send_request( $url, $data ) {
			$headers = array(
				'user-agent' => 'EverestForms/' . EFP_VERSION . '; ' . get_bloginfo( 'url' ),
			);

			$response = wp_remote_post(
				$url,
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => $headers,
					'body'        => array( 'premium_data' => $data )
				)
			);

			return json_decode( wp_remote_retrieve_body( $response ), true );
		}
	}
}

new Everest_Forms_Pro_Admin_Stats();
