<?php
/**
 * Main class for Everest Forms Mailpoet Integration.
 *
 * @since 1.7.7
 *
 * @package EverestForms\Pro\Telegram
 */

namespace EverestForms\Pro\Addons\Telegram;

defined( 'ABSPATH' ) || exit;

use EverestForms\Pro\Addons\Telegram\Settings\Settings;
use EverestForms\Pro\Addons\Telegram\Builder\Builder;
use EverestForms\Pro\Addons\Telegram\Process\Process;

/**
 * Main class for telegram integration.
 *
 * @since 1.7.7
 */
class Telegram {
	/**
	 * Telegram constructor.
	 *
	 * @since 1.7.7
	 */
	public function __construct() {
		$this->includes();
	}

	/**
	 * Initializes the plugin by instantiating the required classes for different requests.
	 * If the request is for admin, an instance of the Admin class is created.
	 * If the request is for frontend, an instance of the Frontend class is created.
	 * It also checks if a rewrite rule flush is needed, and flushes the rules if necessary.
	 *
	 * @since 0
	 */
	public function includes() {
		if ( is_admin() ) {
			new Builder();
			new Settings();
		}

		new Process();
	}
}
