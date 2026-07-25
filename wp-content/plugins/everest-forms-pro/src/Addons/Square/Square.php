<?php
/**
 * Main plugin class.
 *
 * @package EverestForms\Pro\Addons\Square
 * @since   1.0.0
 */

namespace  EverestForms\Pro\Addons\Square;

use EverestForms\Pro\Addons\Square\Settings\Settings;
use EverestForms\Pro\Addons\Square\Builder\Builder;
use EverestForms\Pro\Addons\Square\Process\Process;


/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class Square {


	/**
	 * Plugin Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Init function.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		if ( is_admin() ) {
			new Settings();
			new Builder();
		}
		new Process();
	}
}
