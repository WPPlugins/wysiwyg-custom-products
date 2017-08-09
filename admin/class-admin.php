<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 7/10/16
 * Time: 12:31 PM
 *
 * @since   1.0.0
 * @updated 1.1.0
 */

namespace WCP;
if ( class_exists( 'Admin' ) ) {
	return;
}

/**
 * Class Admin
 *
 * Controls invocation of administration functions
 *
 * @package    WCP
 * @subpackage Admin
 *
 * @since      1.0.0
 * @updated    1.1.0
 */
class Admin {

	/**
	 * Admin constructor.
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	public function __construct() {

		if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) // Tuck away actions not required when handling an ajax request
		{
			require_once __DIR__ . DS . 'class-settings.php';
			require_once __DIR__ . DS . 'class-plugin.php';
		}

		require_once __DIR__ . DS . 'class-products.php';  // Might be needed for variation handling
		require_once __DIR__ . DS . 'class-ajax.php';
	}
}

global $admin;
$admin = new Admin();
