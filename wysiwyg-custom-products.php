<?php
/**
 * Plugin Name: Wysiwyg Custom Products
 * Plugin URI: https://tazziedave.com/wp-plugins/wysiwyg-custom-products
 * Description: Enables a live WYSIWYG preview of custom products where text is edited in text area or text field in woo commerce.
 * Version: 1.1.2
 * Author: Tazziedave
 * Author URI: https://tazziedave.com
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wysiwyg-custom-products
 * Domain Path: /languages
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace WCP;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( class_exists( 'Wcp_Plugin' ) ) {
	return;
}

/**
 * Shorthand constant
 */
if ( ! defined( 'DS' ) ) {
	define( 'DS', DIRECTORY_SEPARATOR );
}

if ( ! defined( 'PHP_INT_MIN' ) ) {
	define( 'PHP_INT_MIN', ~PHP_INT_MAX);
}

if ( ! defined( 'WCP_COMMON_DIR' ) ) {
	define( 'WCP_COMMON_DIR', __DIR__ . DS . 'common' . DS ); // path with trailing slash
}


require_once WCP_COMMON_DIR . 'wp-helpers.php';
require_once WCP_COMMON_DIR . 'utilities.php';
require_once WCP_COMMON_DIR . 'stubs.php';
require_once WCP_COMMON_DIR . 'class-wp-html-helper.php';
require_once WCP_COMMON_DIR . 'class-layout.php';

/**
 * Class Wcp_Plugin
 *
 * @package WCP
 *
 * @since   1.0.0
 * @updated 1.0.7
 */
class Wcp_Plugin {

	/**
	 * Basic plugin information
	 */
	const PLUGIN_TITLE = 'Wysiwyg Custom Products';
	const PLUGIN_NAME = 'wysiwyg-custom-products';
	/**
	 *  Plug in version
	 */
	const VER = '1.1.2';
	/**
	 * Database version. Used in class-plugin to run updates as necessary
	 */
	const DB_VER = 1;

	/**
	 * prefix for all option and metadata stored.
	 */
	const OPTION_PREFIX = 'wcp_';
	const META_PREFIX = '_wcp_';

	const OTHER_VERSION_OPTION_PREFIX = 'wcpp_';
	const OTHER_VERSION_META_PREFIX = '_wcpp_';

	/**
	 *  Internationalisation data
	 */
	const TRANSLATION_DOMAIN = self::PLUGIN_NAME;
	const TRANSLATION_SUB_DIRECTORY = 'languages';
	const DEFAULT_LOCALE = '';

	/*
	 * Specifies how many gravity fields can be auto populated per product
	 */
	const MAX_OVERRIDE_FIELDS = 3;

	/**
	 * @var string Localised name of the plugin
	 */
	static public $localePluginTitle;

	/**
	 * @var string Localised settings tab title
	 */
	static public $localeSettingsTab;

	/**
	 * @var string Will point this directory
	 */
	static public $pluginDirectory;
	/**
	 * @var string Provides plugin_basename() information
	 */
	static public $basename;

	/**
	 * @var string Url for the plugin page
	 */
	static public $pluginUrl;
	/**
	 * @var string Url for the assets
	 */
	static public $assetsUrl;
	/**
	 * @var string Url for the stylesheet assets
	 */
	static public $cssUrl;
	/**
	 * @var string Url for the javascript assets
	 */
	static public $jsUrl;
	/**
	 * @var string Url for any user assets
	 */
	static public $userUrl;

	/**
	 * Wcp_Plugin constructor.
	 *
	 * @param null $locale
	 *
	 * @since   1.0.0
	 */
	public function __construct( $locale = null ) {
		self::$pluginDirectory = plugin_basename( __DIR__ );
		self::$basename        = plugin_basename( __FILE__ );
		self::$pluginUrl       = plugin_dir_url( __FILE__ );
		self::$assetsUrl       = self::$pluginUrl . 'assets/';
		self::$cssUrl          = self::$assetsUrl . 'stylesheets/';
		self::$jsUrl           = self::$assetsUrl . 'js/';
		self::$userUrl         = self::$pluginUrl . 'user/';

		load_plugin_textdomain( self::TRANSLATION_DOMAIN,
		                        false,
		                        trailingslashit( self::$pluginDirectory . DS . self::TRANSLATION_SUB_DIRECTORY ) );
		// Add callback for WP to get our locale
		add_filter( 'plugin_locale', [ $this, 'get_plugin_locale_callback' ], $priority = 10, $accepted_args = 2 );

		// translators: Name of plugin - free version
		self::$localePluginTitle = __( 'Wysiwyg Custom Products', 'wysiwyg-custom-products' );

		// translators: Tab on the settings menu
		self::$localeSettingsTab = __( 'Wysiwyg Customize', 'wysiwyg-custom-products' );
	}

	/**
	 * Returns array of option names used by plugin [name, name, ...], etc
	 *
	 * @return array
	 *
	 * @since 1.0.5
	 */
	static public function get_reserved_option_names() {
		$result = [];
		// Reserved option names - also used in admin-settings.js initialise
		$result[] = 'settings';
		$result[] = 'ver';
		$result[] = 'db_ver';
		$result[] = 'layouts';

		return $result;
	}

	/**
	 * Returns array of option names used by plugin and any layouts [name, name, ...], etc
	 *
	 * @param string $prefix Optional
	 *
	 * @return array
	 *
	 * @since 1.0.5
	 */
	static public function get_option_names( $prefix = Wcp_Plugin::OPTION_PREFIX ) {
		$result = self::get_reserved_option_names();

		// Now add layout options
		try {
			$layouts = Layout::loadLayouts( $prefix );
			foreach ( $layouts as $layout ) {
				$result[] = $layout;
			}
		}
		catch ( \Exception $e ) {
			// Ignore errors
		}

		return $result;
	}

	/**
	 * Returns associative array of metadata keys used by plugin meta_type => [meta_key, meta_key], etc
	 *
	 * @return array
	 *
	 * @since   1.0.5
	 * @updated 1.0.7
	 */
	static public function get_metadata_names() {
		$result         = [];
		$result['post'] = [];

		// NB attribute values meta data on for products will not get cleared
		// These field names come from class-products.php
		$result['post'][] = 'layout';
		$result['post'][] = 'catalog_text';
		$result['post'][] = 'specific_lines';
		$result['post'][] = 'product_text';
		$result['post'][] = 'background_image';
		for ( $i = 1; $i <= Wcp_Plugin::MAX_OVERRIDE_FIELDS; $i ++ ) {
			$result['post'][] = 'field_label_' . $i;
			$result['post'][] = 'field_label_override_' . $i;
			$result['post'][] = 'field_values_' . $i;
		}

		return $result;
	}

	/**
	 * Routine called if on any admin page
	 *
	 * @since   1.0.0
	 * @updated 1.0.7
	 */
	public function admin() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			// Plugin.php sometimes not loaded, so we'll do it
			$adminDir = get_admin_path();
			require_once $adminDir . 'includes' . DS . 'plugin.php';
		}

		if ( is_plugin_active( self::$basename ) ) {
			require_once __DIR__ . DS . 'admin' . DS . 'class-admin.php';
		} else {
			register_activation_hook( __FILE__, [ $this, 'activate' ] );
		}
	}

	/**
	 * Called when plugin is activated
	 *
	 * @throws LayoutException // If can't create and save default, we've got a problem!
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$required = '';

		if ( ! function_exists( 'version_compare' ) || version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
			// translators: Error message for insufficient PHP version
			$required = __( 'PHP Version 5.4.0 or above', 'wysiwyg-custom-products' );
		} else {
			if ( ! class_exists( 'WooCommerce' ) ) {
				$required = 'WooCommerce';
			}
		}

		if ( '' !== $required ) {
			// translators: Error message when plugin dependencies are not available
			$errorMessage = sprintf( __( 'Plugin cannot be activated because it needs %s to be active.',
			                             'wysiwyg-custom-products' ), $required );
			trigger_error( Wcp_Plugin::$localePluginTitle . ' ' . $errorMessage, E_USER_ERROR );
		}

		if ( false !== get_option( 'ver', false, null, self::OTHER_VERSION_OPTION_PREFIX ) ) {
			require_once __DIR__ . DS . 'admin' . DS . 'version-change.php';

			return;
		}

		update_option( 'ver', self::VER );
		add_option( 'db_ver', self::DB_VER );

		require_once WCP_COMMON_DIR . 'class-layout.php';
		/**
		 * @var Layout $layout
		 */

		$layout = new Layout();
		$layout->save_defaults();
		$settings = get_option( 'settings', [] );
		if ( count( $settings ) === 0 ) {
			$settings['CurrentLayout'] = $layout->getName();
			add_option( 'settings', $settings, false );
		}

	}

	/**
	 * Called when not in admin pages
	 *
	 * @since   1.0.0
	 */
	public function frontend() {
		require_once __DIR__ . DS . 'frontend' . DS . 'class-frontend.php';
	}

	/**
	 * Called when WP is loading translations and is looking for the locale.
	 * If it's our domain and our locale is set, override it, otherwise just passed back.
	 *
	 * @param $locale
	 * @param $domain
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 */
	public function get_plugin_locale_callback( $locale, $domain ) {
		if ( null !== self::TRANSLATION_DOMAIN && $domain === self::TRANSLATION_DOMAIN && ( '' !== self::DEFAULT_LOCALE ) ) {
			$locale = self::DEFAULT_LOCALE;
		}

		return $locale;
	}
}

$wysiwygCP = new Wcp_Plugin();

if ( ! defined( 'WP_UNINSTALL_PLUGIN ' ) ) { // Check to see if being instantiated for uninstall purposes
	if ( is_admin() ) {
		$wysiwygCP->admin();
	} else {
		$wysiwygCP->frontend();
	}
}
