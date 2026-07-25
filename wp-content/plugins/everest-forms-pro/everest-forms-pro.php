<?php
/**
 * Plugin Name: Everest Forms (Pro)
 * Plugin URI: https://everestforms.net/
 * Description: Everest Forms features and extensions controller.
 * Version: 1.9.17
 * Author: WPEverest
 * Author URI: https://wpeverest.com
 * Text Domain: everest-forms-pro
 * Domain Path: /languages/
 * Requires at least: 5.5
 * Requires PHP: 7.2
 * Requires License: yes
 * WordPress Available: no
 *
 * EVF requires at least: 1.8.0
 * EVF tested up to: 3.0.8
 *
 * @package EverestForms_Pro
 */

defined( 'ABSPATH' ) || exit;

// Define plugin version.
if ( ! defined( 'EFP_VERSION' ) ) {
	define( 'EFP_VERSION', '1.9.17' );
}

if ( ! defined( 'EFP_ABSPATH' ) ) {
	define( 'EFP_ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

// Define plugin root file.
if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
	define( 'EFP_PLUGIN_FILE', __FILE__ );
}

// Include the main EverestForms_Pro class.
if ( ! class_exists( 'EverestForms_Pro' ) ) {
	include_once __DIR__ . '/includes/class-everest-forms-pro.php';
}

// Include the main EVF_Plugin_Updater class.
if ( ! class_exists( 'EVF_Plugin_Updater' ) ) {
	include_once __DIR__ . '/includes/class-evf-plugin-updater.php';
}

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'EverestForms_Pro', 'get_instance' ), 0 );

/**
 * To clear rewrite_rules.
 *
 * @since 1.7.9
 */
register_activation_hook( __FILE__, array( 'EverestForms_Pro', 'everest_forms_plugin_activation' ) );

/**
 * Autoload the packages.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $autoloader ) && version_compare( PHP_VERSION, '7.1.3', '>=' ) ) {
	require $autoloader;
} else {
	if ( version_compare( PHP_VERSION, '7.1.3', '<=' ) ) {
		return;
	}

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			sprintf(
				/* translators: 1: composer command. 2: plugin directory */
				esc_html__( 'Your installation of the Everest Forms (Pro) plugin is incomplete. Please run %1$s within the %2$s directory.', 'everest-forms-pro' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}

	/**
	 * Outputs an admin notice if composer install has not been ran.
	 */
	add_action(
		'admin_notices',
		function () {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: 1: composer command. 2: plugin directory */
						esc_html__( 'Your installation of the Everest Forms (Pro) plugin is incomplete. Please run %1$s within the %2$s directory.', 'everest-forms-pro' ),
						'<code>composer install</code>',
						'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

	/**
	 * Register Everest Forms with ThemeGrill SDK
	 */
	add_filter(
		'themegrill_sdk_products',
		function ( $products ) {
			$products[] = EFP_PLUGIN_FILE;
			return $products;
		},
		10,
		1
	);

	\EverestForms\Pro\Service::instance();
	\EverestForms\Pro\Capabilities\Integrations::instance();
