<?php
/**
 * Main class for Everest Forms Mollie.
 *
 * @package EverestForms\Pro\Mollie
 *
 * @since 1.7.7
 */

namespace EverestForms\Pro\Addons\Mollie;

use EverestForms\Pro\Addons\Mollie\settings\Settings;
use EverestForms\Pro\Addons\Mollie\builder\Builder;
use EverestForms\Pro\Addons\Mollie\Process\Process as Process;

defined( 'ABSPATH' ) || exit;

/**
 * Everest Forms Mollie Class.
 *
 * @since 1.7.7
 */
class Mollie {
	/**
	 * EverestForms_Mollie Constructor
	 *
	 * @since 0
	 */
	public function __construct() {
		$this->includes();
	}

	/**
	 * Initializes the feature by instantiating the required classes for different requests.
	 * If the request is for frontend, an instance of the Frontend class is created.
	 * It also checks if a rewrite rule flush is needed, and flushes the rules if necessary.
	 *
	 * @since 1.7.7
	 */
	public function includes() {
		if ( is_admin() ) {
			new Settings();
			new Builder();
		}
		new Process();
	}
}
