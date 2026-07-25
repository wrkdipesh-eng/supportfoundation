<?php
/**
 * Main plugin class for Everest Forms MailPoet.
 *
 * @package EVFMailPoet
 * @since   1.0.0
 */

 namespace  EverestForms\Pro\Addons\MailPoet;

use EverestForms\Pro\Addons\MailPoet\Admin\Admin;
use EverestForms\Pro\Addons\MailPoet\Frontend\Frontend;
use EverestForms\Pro\Addons\MailPoet\Admin\Integration\MailPoetIntegration;
use EverestForms\Pro\Addons\MailPoet\Admin\Builder\Settings;

defined( 'ABSPATH' ) || exit;


	/**
	 * Main plugin class for Everest Forms MailPoet.
	 *
	 * @since 1.0.0
	 */
class MailPoet {

	 /**
	  * Constructor class
	  *
	  * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function includes() {
			new Admin();
			new Settings();
			new Frontend();
	}

}
