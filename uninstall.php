<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 2/11/2016
 * Time: 11:58 AM
 */
namespace WCP;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'wysiwyg-custom-products.php';

if ( WP_UNINSTALL_PLUGIN !== Wcp_Plugin::$basename ) {
	exit;
}

/**
 * Class Uninstall
 *
 * @package WCP
 * @since    1.0.0
 * @updated  1.1.2
 *
 */
class Uninstall {

	/**
	 * Uninstall constructor.
	 *
	 * @since    1.0.0
	 * @updated  1.1.2
	 */
	public function __construct() {

		if ( 'no' === get_option( 'settings', 'yes', 'clean_delete' ) ) {
			return; // User wants to keep data.
		}

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			global $wpdb;
			$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );

			if ( ! empty( $blogs ) ) {
				/** @noinspection ForeachSourceInspection */
				foreach ( $blogs as $blog ) {
					switch_to_blog( $blog['blog_id'] );
					/** @noinspection DisconnectedForeachInstructionInspection */
					$this->delete_metadata();
					/** @noinspection DisconnectedForeachInstructionInspection */
					$this->delete_options();
				}
			} else {
				$this->delete_metadata();
				$this->delete_options();

			}
			delete_site_option( 'ver' );
			delete_site_option( 'db_ver' );
			delete_site_option( 'settings' );
		} else {
			$this->delete_metadata();
			$this->delete_options();
		}
	}

	/**
	 * Deletes the wysiwyg Custom Products meta data from the products.
	 *
	 * @since   1.0.0
	 * @updated 1.0.5
	 */
	private function delete_metadata() {
		$metadataNames = (array) Wcp_Plugin::get_metadata_names();

		foreach ( $metadataNames as $metaType => $names ) {
			foreach ( $names as $name ) {
				delete_all_metadata( $metaType, $name );
			}
		}
	}

	/**
	 *  Deletes the version and layout information from the options
	 *
	 * @since   1.0.0
	 * @updated 1.0.5
	 */
	private function delete_options() {
		$options = (array) Wcp_Plugin::get_option_names();
		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}
}

global $uninstaller;
$uninstaller = new Uninstall();
