<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 12/10/2016
 * Time: 9:19 AM
 *
 * @since 1.0.0
 */

namespace WCP;
if ( class_exists( 'Ajax' ) ) {
	return;
}

/**
 * Class Ajax
 *
 * @package    WCP
 * @subpackage Admin
 *
 * @since      1.0.0
 * @updated    1.1.2
 */
class Ajax {
	/**
	 * Ajax constructor.
	 *
	 * @since   1.0.0
	 * @updated 1.1.2
	 */
	public function __construct() {
		add_action( 'wp_ajax_get_layout', [ $this, 'get_layout' ] );
		add_action( 'wp_ajax_get_image_attr', [ $this, 'get_image_attr' ] );
		add_action( 'wp_ajax_post_layout', [ $this, 'save_layout' ] );
		add_action( 'wp_ajax_post_rename', [ $this, 'rename_layout' ] );
		add_action( 'wp_ajax_post_copy', [ $this, 'copy_layout' ] );
		add_action( 'wp_ajax_post_delete', [ $this, 'delete_layout' ] );
		add_action( 'wp_ajax_post_plugin_delete', [ $this, 'plugin_delete' ] );
	}

	/**
	 * Loads a layout array and then sends it to requester
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function get_layout() {
		$this->check_nonce_get();
		$name = $this->is_valid_name( $_GET['name'], true ); // Must exist

		if ( false === $name ) {
			wp_send_json( [] );
		}

		$layout = Layout::ajax_get( $name );
		if ( ! is_array( $layout ) ) {
			wp_send_json( [] );
		}

		$layout = apply_filters( 'ajax_after_fetch_layout', $layout );
		// Make current;
		update_option( 'settings', $name, 'CurrentLayout' );
		wp_send_json( $layout );
	}

	/**
	 * Gets image metadata for an attachment
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	public function get_image_attr() {
		$this->check_nonce_get();
		$attachmentId = (int) $_GET['attachment'];
		$size = 'shop_single';

		$image = wp_get_attachment_image_src( $attachmentId, $size );
		// Overriding WP's resizing of image in admin for "editing"
		$attributes = wc_get_image_size( $size );
		if ( $attributes ) {
			$image['width']  = maybe_get( $attributes, 'width', $image['width'] );
			$image['height'] = maybe_get( $attributes, 'height', $image['height'] );
		}

		wp_send_json( $image );
	}

	/**
	 * Saves modified layout
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	public function save_layout() {
		$this->check_nonce_post();

		$name = $this->is_valid_name( $_POST['name'], true ); // Must exist
		if ( $name === false ) {
			wp_die( 'Layout does not exist' );
		}

		$layout = $_POST['layout'];

		array_to_int( $layout, false );

		$layout = apply_filters( 'ajax_before_save_layout', $layout );

		try {
			Layout::is_layout_valid( $layout, true ); // Throws an exception if layout is not valid, sanitizes as well
			update_option( $name, $layout );
			$msg = '';
		} catch ( LayoutException $e ) {
			$msg = $e->getMessage();
		}

		wp_die( $msg ); // this is required to terminate immediately and return a proper response
	}

	/**
	 * Renames a layout by a copy/delete mechanism
	 *
	 * @since   1.0.0
	 * @updated 1.0.5
	 */
	public function rename_layout() {
		$this->check_nonce_post();

		$name = $this->is_valid_name( $_POST['name'], true ); // Must exist
		if ( $name === false ) {
			wp_die( 'Layout does not exist' );
		}

		$newName = $this->is_valid_name( $_POST['new-name'], false ); // Mustn't exist
		if ( $newName === false ) {
			wp_die( 'Cannot rename to an existing layout' );
		}

		$layout = Layout::get_layout_array( $name );
		if ( ! $layout ) {
			wp_die( 'Cannot rename invalid layout' );
		}

		// Copy layout to new name and delete old option
		add_option( $newName, $layout, false );
		delete_option( $name );
		// Set the new name as the current one
		update_option( 'settings', $name, 'CurrentLayout' );

		// Replace name in style list
		try {
			$layouts = Layout::loadLayouts();
			$key     = array_search( $name, $layouts, false );
			if ( false !== $key ) {
				$layouts[ $key ] = $newName;
			} else { // Should never happen
				$layouts[] = $newName;
			}
			update_option( 'layouts', $layouts );
		} catch ( \Exception $e ) {
			// Ignore exceptions
		}
		wp_die();
	}

	/**
	 * Copies the named layout to another with the new-name. New-name is add to list of layouts
	 *
	 * @since   1.0.0
	 * @updated 1.0.5
	 */
	public function copy_layout() {
		$this->check_nonce_post();

		$name = $this->is_valid_name( $_POST['name'], true ); // Must exist
		if ( $name === false ) {
			wp_die( 'Layout does not exist' );
		}

		$newName = $this->is_valid_name( $_POST['new-name'], false ); // Mustn't exist
		if ( $newName === false ) {
			wp_die( 'Cannot copy to an existing layout' );
		}

		$layout = Layout::get_layout_array( $name );
		if ( ! $layout ) {
			wp_die( 'Cannot copy invalid layout' );
		}

		add_option( $newName, $layout, false );
		// Set the new name as the current one
		update_option( 'settings', $newName, 'CurrentLayout' );
		try {
			// Add name to style list
			$layouts   = Layout::loadLayouts();
			$layouts[] = $newName;
			update_option( 'layouts', $layouts );
		} catch ( \Exception $e ) {
			// Ignore exceptions
		}

		wp_die();
	}

	/**
	 * Deletes named layout
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function delete_layout() {
		$this->check_nonce_post();

		$name = $this->is_valid_name( $_POST['name'], true ); // Must exist
		if ( $name === false ) {
			wp_die( 'Layout does not exist' );
		}
		// Delete old layout
		// The current name will be set in get_layout
		delete_option( $name );
		// Remove name from layouts list
		delete_option( 'layouts', $name, true ); // true says look for name in list and delete
		wp_die();
	}

	/**
	 * Saves plugin clean delete option
	 *
	 * @since   1.1.2
	 */
	public function plugin_delete() {
		$this->check_nonce_post();

		$value = $_POST['delete_value'];
		if ( ! in_array( $value, [ 'no', 'yes' ], true ) ) {
			wp_die();
		}
		update_option( 'settings', $value, 'clean_delete' );
		wp_die();
	}

	/**
	 * Used to check the nonce for GET calls
	 *
	 * @since 1.0.0
	 */
	private function check_nonce_get() {
		$nonce = isset( $_GET['wcp-nonce'] ) ? $_GET['wcp-nonce'] : false;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wcp-settings' ) ) {
			wp_die();
		}
	}

	/**
	 * Used to check the nonce for POST calls
	 *
	 * @since 1.0.0
	 */
	private function check_nonce_post() {
		$nonce = isset( $_POST['wcp-nonce'] ) ? $_POST['wcp-nonce'] : false;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wcp-settings' ) ) {
			wp_die();
		}
	}

	/**
	 * Checks to make sure a layout name is valid and, optionally, whether the layout exists
	 *
	 * @param string $checkName
	 * @param bool $existAlready
	 *
	 * @return bool|string
	 *
	 * @since   1.0.1
	 * @updated 1.0.5
	 */
	private function is_valid_name( $checkName, $existAlready = false ) {
		$validName = false;

		if ( is_non_empty_string( $checkName ) ) {
			$validName = sanitize_text_field( $checkName );

			if ( in_arrayi( $validName, Wcp_Plugin::get_reserved_option_names(), true ) ) {
				return false;
			}

			if ( $existAlready ) {
				$validName = Layout::does_layout_exist( $validName ) ? $validName : false;
			} else {
				$validName = Layout::does_layout_exist( $validName ) ? false : $validName;
			}
		}

		return $validName;
	}
}

global $ajax;
$ajax = new Ajax();
