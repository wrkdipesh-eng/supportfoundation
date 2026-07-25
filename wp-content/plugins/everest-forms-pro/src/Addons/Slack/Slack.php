<?php
/**
 * Main file for Slack.
 *
 * @package EverestForms\Pro\Addons\Slack
 * @since 3.0.5
 */

namespace EverestForms\Pro\Addons\Slack;

use EverestForms\Pro\Addons\Slack\Builder\Builder;
use EverestForms\Pro\Addons\Slack\Process\Process;

defined( 'ABSPATH' ) || exit;

/**
 * Main class for Slack integration.
 *
 * @since 3.0.5
 */
class Slack {
	/**
	 * Slack constructor.
	 *
	 * @since 3.0.5
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
	 * @since 3.0.5
	 *
	 * @return void
	 */
	public function includes() {
		if ( is_admin() ) {
			new Builder();
		}

		new Process();
	}
}
