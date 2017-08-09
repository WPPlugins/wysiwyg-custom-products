<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 11/10/2016
 * Time: 10:25 AM
 *
 * @since   1.0.0
 * @updated 1.0.6
 */

namespace WCP;
/**
 *  Standard prefixes for all options and metadata
 */
const PREFIX      = Wcp_Plugin::OPTION_PREFIX;
const META_PREFIX = Wcp_Plugin::META_PREFIX;

/* Plugin option helpers */

/**
 * Provides add_option functionality with Plugin prefix added to name
 *
 * @param string      $option
 * @param string      $value
 * @param string|bool $autoload
 * @param string      $prefix
 *
 * @return bool False if option was not added and true if option was added.
 *
 * @since   1.0.0
 */
function add_option( $option, $value = '', $autoload = true, $prefix = PREFIX ) {
	return \add_option( $prefix . $option, $value, '', $autoload ); // empty string for deprecated!
}

/**
 * Provides update_option functionality with Plugin prefix added to name
 * If $field is set then the option is assumed to be an array and the option[field] is set to the value
 * otherwise the option is set to the value
 *
 * @param string           $option
 * @param mixed            $value
 * @param string|integer|null $field // if set, treat option as array of options
 * @param string|bool|null $autoload
 * @param string           $prefix
 *
 * @return bool False if value was not updated and true if value was updated.
 *
 * @since   1.0.0
 */
function update_option( $option, $value, $field = null, $autoload = null, $prefix = PREFIX ) {
	if ( null !== $field ) {
		$options           = get_option( $option, [] );
		$options[ $field ] = $value;

		return \update_option( $prefix . $option, $options, $autoload );
	} else {
		return \update_option( $prefix . $option, $value, $autoload );
	}
}

/**
 * Provides get_option functionality with Plugin prefix added to name
 * If $field is set then the option is assumed to be an array and the option[field] is returned
 * otherwise the option value is returned
 *
 * @param string $option
 * @param mixed  $default
 * @param string|integer|null $field // if set, treat option as array of options
 * @param string $prefix
 *
 * @return mixed
 *
 * @since   1.0.0
 */
function get_option( $option, $default = false, $field = null, $prefix = PREFIX ) {
	$options = \get_option( $prefix . $option ); // Don't want default in case of an expected array

	if ( null !== $field && is_array( $options ) ) {
		$result = isset( $options[ $field ] ) ? $options[ $field ] : $default;
	} else {
		$result = \get_option( $prefix . $option, $default );
	}

	return $result;
}

/**
 * Rename options in the wp option table
 *
 * @param string $oldOptionName
 * @param string $newOptionName
 *
 * @return false|int
 *
 * @since 1.0.5
 */
function rename_option( $oldOptionName, $newOptionName ) {
	global $wpdb;

	return $wpdb->update(
		$wpdb->options,
		array(
			'option_name' => $newOptionName    // column & new value
		),
		array( 'option_name' => $oldOptionName ),  // match old name
		array(
			'%s',
		),
		// where clause(s) format types
		array(
			'%s',
		)
	);
}
/**
 * As above but also does an html decode including quotes
 *
 * @param string $option
 * @param mixed  $default
 * @param string|integer|null $field // if set, treat option as array of options
 * @param string $prefix
 *
 * @return mixed
 *
 * @since   1.0.0
 */
function get_option_entquotes( $option, $default = false, $field = null, $prefix = PREFIX ) {
	$options = \get_option( $prefix . $option ); // Don't want default in case of an expected array
	if ( null !== $field && is_array( $options ) ) {
		$result = isset( $options[ $field ] ) ? $options[ $field ] : $default;
	} else {
		$result = \get_option( $prefix . $option, $default );
	}

	return html_ent_quotes( $result );
}

/**
 * Provides delete_option functionality with Plugin prefix added to name
 *
 * If $field is not set the option value is deleted.
 *
 * If $field is set and $fieldIsValue = false then the option is assumed to be an array and the
 * option[field] is removed from the array
 *
 * If $field is set and $fieldIsValue = true then the option is assumed to be an array of 'names'
 * and option[] = field is removed from the array if the 'name' in $field is found
 *
 * @param string $option
 * @param string|integer|null $field        // if set, treat option as array of options
 * @param bool                $fieldIsValue // normally $field is treated as a the key. If $fieldIsValue is set true,
 *                                          // the array is treated as a list of values and $field is looked for
 * @param string $prefix
 *
 * @return bool True, if option is successfully deleted. False on failure.
 *
 * @since   1.0.0
 */
function delete_option( $option, $field = null, $fieldIsValue = false, $prefix = PREFIX ) {
	$options = get_option( $option );
	if ( null !== $field && is_array( $options ) ) {
		if ( $fieldIsValue ) {
			$key = array_search( $field, $options, false );
			if ( false !== $key ) {
				unset( $options[ $key ] );
			}
		} else {
			unset( $options[ $field ] );
		}

		return update_option( $option, $options );
	} else {
		return \delete_option( $prefix . $option );
	}
}

/**
 * Provides delete_site_option functionality with Plugin prefix added to name
 *
 * @param string $option
 * @param string $prefix
 *
 * @return bool True, if option is successfully deleted. False on failure.
 *
 * @since   1.0.0
 */
function delete_site_option( $option, $prefix = PREFIX ) {
	if ( function_exists( '\delete_site_option' ) ) {
		return \delete_site_option( $prefix . $option );
	}

	return false;
}

/* Plugin meta data helpers */

/**
 * Provides get_post_meta functionality with Plugin meta prefix added to name
 *
 * @param integer $post_id
 * @param string  $meta_key
 * @param bool    $single
 * @param string  $prefix
 *
 * @return mixed Will be an array if $single is false. Will be value of meta data
 *               field if $single is true.
 *
 * @since   1.0.0
 */
function get_post_meta( $post_id, $meta_key = '', $single = false, $prefix = META_PREFIX ) {
	return \get_post_meta( $post_id, $prefix . $meta_key, $single );
}

/**
 * As above but also does an html decode including quotes
 *
 * @param integer $post_id
 * @param string  $meta_key
 * @param bool    $single
 * @param string  $prefix
 *
 * @return mixed Will be an array if $single is false. Will be value of meta data
 *               field if $single is true.
 *
 * @since   1.0.0
 */
function get_post_meta_entquotes( $post_id, $meta_key = '', $single = false, $prefix = META_PREFIX ) {
	return html_ent_quotes( \get_post_meta( $post_id, $prefix . $meta_key, $single ) );
}

/**
 * Gets a field name from the array (typically _$POST) with Plugin meta prefix added to field name
 *
 * @param array  $post
 * @param string $fieldName
 * @param string $prefix
 *
 * @return mixed
 *
 * @since   1.0.0
 */
function get_field_value( $post, $fieldName, $prefix = META_PREFIX ) {
	return $post[ $prefix . $fieldName ];
}

/**
 * Provides update_post_meta functionality with Plugin prefix added to name
 *
 * @param integer $postId
 * @param string  $meta_key
 * @param mixed   $meta_value
 * @param string  $prefix
 *
 * @return int|bool Meta ID if the key didn't exist, true on successful update,
 *                  false on failure.
 *
 * @since   1.0.0
 */
function update_post_meta( $postId, $meta_key, $meta_value, $prefix = META_PREFIX ) {
	return \update_post_meta( $postId, $prefix . $meta_key, $meta_value );
}

/**
 * Provides delete_post_meta functionality with Plugin prefix added to name
 *
 * @param integer $postId
 * @param  string $meta_key
 * @param string  $prefix
 *
 * @return bool True on success, false on failure.
 *
 * @since   1.0.0
 */
function delete_post_meta( $postId, $meta_key, $prefix = META_PREFIX ) {
	return \delete_post_meta( $postId, $prefix . $meta_key );
}

/**
 * Provides delete_all_metadata functionality with Plugin prefix added to name
 *
 * @param  string $meta_type
 * @param  string $meta_key
 * @param string  $prefix
 *
 * @return bool True on success, false on failure.
 *
 * @since   1.0.0
 */
function delete_all_metadata( $meta_type, $meta_key, $prefix = META_PREFIX ) {
//    $user_id    = 0; // This will be ignored, since we are deleting for all users.
//    $meta_value = ''; // Also ignored. The meta will be deleted regardless of value.
//    $delete_all = true;
	return \delete_metadata( $meta_type, 0, $prefix . $meta_key, '', true );
}

/**
 * Rename meta keys in the appropriate meta table
 *
 * @param string $meta_type
 * @param string $old_meta_key
 * @param string $new_meta_key
 *
 * @return false|int
 *
 * @since 1.0.5
 */
function rename_post_metadata( $meta_type, $old_meta_key, $new_meta_key ) {
	global $wpdb;

	switch ( $meta_type ) {
		case 'comment':
			$table = $wpdb->commentmeta;
			break;
		case 'user':
			$table = $wpdb->usermeta;
			break;
		default:
			$table = $wpdb->postmeta;
			break;
	}

	return $wpdb->update(
		$table,
		array(
			'meta_key' => $new_meta_key    // column & new value
		),
		array( 'meta_key' => $old_meta_key ),  // match old name
		array(
			'%s',
		),
		// where clause(s) format types
		array(
			'%s',
		)
	);
}

/* Helpers to deal with attachments */

/**
 * Gets wp_get_attachment_metadata and, optionally the url for the attachment added to the metadata
 *
 * @param int  $attachmentId
 * @param bool $wantUrl
 *
 * @return array
 *
 * @since   1.0.0
 */
function wp_get_attachment_metadata( $attachmentId, $wantUrl = true ) {
	$result = \wp_get_attachment_metadata( $attachmentId );
	if ( $result && $wantUrl ) {
		$result['url'] = \wp_get_attachment_url( $attachmentId );
	}

	return $result;
}

/**
 * Gets wp_get_attachment_metadata for an image and, optionally the url for the attachment added to the metadata
 * If the attachment is not an image, returns dummy data
 *
 * @param int  $attachmentId
 * @param int  $width
 * @param int  $height
 * @param bool $wantUrl
 *
 * @return array
 *
 * @since   1.0.0
 * @updated 1.0.1
 */
function get_image_metadata(
	$attachmentId, $width = Layout::DEFAULT_IMAGE_SIZE, $height = Layout::DEFAULT_IMAGE_SIZE, $wantUrl = true
) {
	$result = [ 'url' => '', 'width' => $width, 'height' => $height ]; // basically empty if not image
	// url is set whether wanted or not. Just ignore it!
	if ( \wp_attachment_is_image( $attachmentId ) ) {
		$result = wp_get_attachment_metadata( $attachmentId, $wantUrl );
	}

	return $result;
}

/**
 * Gets a single sized attachment image and adds appropriate keys to the array
 *
 * @param int $attachmentId
 * @param string $size
 *
 * @return array
 *
 * @since   1.0.8
 */
function wp_get_attachment_image_src( $attachmentId, $size ) {
	$image = \wp_get_attachment_image_src( $attachmentId, $size );
	if ( ! $image ) {
		$image = [ '', 0, 0, false ];
	}

	return array_combine( [ 'url', 'width', 'height', 'intermediate' ], $image );
}
/* Hook and filter helpers */

/**
 * Applies filters with $prefix prepended to $tag
 * Allows for all 'myplugin_my_filter' tags to be simply listed as 'my_filter'
 *
 * @param string $tag   The name of the filter hook.
 * @param mixed  $value The value on which the filters hooked to `$tag` are applied on.
 * @param string $prefix
 *
 * @return mixed The filtered value after all hooked functions are applied to it.* @return mixed|void
 *
 * @since   1.0.0
 */
function apply_filters( $tag, $value, $prefix = PREFIX ) {
	return \apply_filters( $prefix . $tag, $value );
}

/**
 * Does action with $prefix prepended to $tag
 * Allows for all 'myplugin_my_action' tags to be simply listed as 'my_action'
 *
 * @param string $tag     The name of the action to be executed.
 * @param mixed  $arg,... Optional. Additional arguments which are passed on to the
 *                        functions hooked to the action. Default empty.
 * @param string $prefix
 *
 * @since   1.0.0
 */
function do_action( $tag, $arg = '', $prefix = PREFIX ) {
	\do_action( $prefix . $tag, $arg );
}

/* Script and stylesheet helpers */

/** @noinspection MoreThanThreeArgumentsInspection */

/**
 * Simplifies registering and enqueuing a script.
 *
 * By default it uses the $scriptName as follows:
 * $handle = myplugin_$scriptName
 * $src    = plugin class jsUrl with the name '$scriptName.js'
 *
 * @param string $scriptName
 * @param array            $deps      Optional. An array of registered script handles this script depends on.
 *                                    Default empty array. Enclose script handles in an inner array to get them
 *                                    prefixed.
 * @param string|bool|null $ver       Optional. String specifying script version number, if it has one, which is added
 *                                    to the URL as a query string for cache busting purposes. If version is set to
 *                                    false, a version number is automatically added equal to current installed
 *                                    WordPress version. If set to null, no version is added.
 * @param null|bool        $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 *                                    Default null which is then true because I prefer my javascript in the footer by
 *                                    default.
 * @param null|string      src        Option overrides default handling for the location. Full URL of the script, or
 *                                    path of the script relative to the WordPress root directory.
 * @param null|string      $handle    Overrides default handling for the $handle
 * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root
 *                                    directory.
 *
 * @return bool                       Whether the script has been registered. True on success, false on failure.
 *
 * @since   1.0.0
 */
function register_script( $scriptName, array $deps = [], $ver = false, $in_footer = null, $src = null, $handle = null
) {

	$expandedDeps = [];

	$handle = $handle ? $handle : PREFIX . $scriptName;
	$src    = $src ? $src : Wcp_Plugin::$jsUrl . $scriptName . '.js';

	if ( null === $in_footer ) {
		$in_footer = true;
	}

	foreach ( $deps as $dep ) {
		if ( is_string( $dep ) ) {
			$expandedDeps[] = $dep;
		} elseif ( is_array( $dep ) ) {
			/** @noinspection ForeachSourceInspection */
			foreach ( $dep as $prefixedDep ) {
				$expandedDeps[] = PREFIX . $prefixedDep;
			}
		}
	}
	if ( wp_register_script( $handle, $src, $expandedDeps, $ver, $in_footer ) ) {
		wp_enqueue_script( $handle );

		return true;
	} else {
		return false;
	}
}

/**
 * Simplify Localizing a script.
 *
 * $prefix is automatically prepended to $handle and $object_name
 * Works only if the script has already been added.
 *
 * @param string $handle      Script handle the data will be attached to.
 * @param string $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable.
 *                            Example: '/[a-zA-Z0-9_]+/'.
 * @param array  $l10n        The data itself. The data can be either a single or multi-dimensional array.
 * @param string $prefix      Optional, by default the Plugin option prefix
 *
 * @return bool               True if the script was successfully localized, false otherwise.
 *
 * @since   1.0.0
 */
function localize_script( $handle, $object_name, $l10n, $prefix = PREFIX ) {
	return wp_localize_script( $prefix . $handle, $prefix . $object_name, $l10n );
}

/** @noinspection MoreThanThreeArgumentsInspection */

/**
 * /**
 * Simplifies registering and enqueuing a style.
 *
 * By default it uses the $styleName as follows:
 * $handle = myplugin_$styleName
 * $src    = plugin class cssURL with the name '$styleName.css'
 *
 * @param string $styleName
 * @param array            $deps   Optional. An array of registered stylesheet handles this stylesheet depends on.
 *                                 Default empty array. Enclose stylesheet handles in an inner array to get them
 *                                 prefixed.
 * @param string|bool|null $ver    Optional. String specifying stylesheet version number, if it has one, which is added
 *                                 to the URL as a query string for cache busting purposes. If version is set to false,
 *                                 a version number is automatically added equal to current installed WordPress
 *                                 version. If set to null, no version is added.
 * @param string           $media  Optional. The media for which this stylesheet has been defined.
 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media
 *                                 queries like
 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
 * @param null|string      $src    Optional. Overrides default handling for the location.
 *                                 Full URL of the script, or path of the script relative to the WordPress root
 *                                 directory.
 * @param null|string      $handle Optional. Overrides default handling for the $handle
 *
 * @return bool                    Whether the style has been registered. True on success, false on failure.
 *
 * @since   1.0.0
 */
function register_style( $styleName, array $deps = [], $ver = false, $media = 'all', $src = null, $handle = null ) {
	$expandedDeps = [];

	$handle = $handle ? $handle : PREFIX . $styleName;
	$src    = $src ? $src : Wcp_Plugin::$cssUrl . $styleName . '.css';

	foreach ( $deps as $dep ) {
		if ( is_string( $dep ) ) {
			$expandedDeps[] = $dep;
		} elseif ( is_array( $dep ) ) {
			/** @noinspection ForeachSourceInspection */
			foreach ( $dep as $prefixedDep ) {
				$expandedDeps[] = PREFIX . $prefixedDep;
			}
		}
	}

	if ( wp_register_style( $handle, $src, $expandedDeps, $ver, $media ) ) {
		wp_enqueue_style( $handle );

		return true;
	} else {
		return false;
	}
}

/**
 * Obtain the path to the admin directory.
 *
 * @return string
 *
 * @updated 1.0.6
 */
function get_admin_path() {
	// Replace the site base URL with the absolute path to its installation directory.
	$blogUrl  = preg_replace( "(^https?://)", "", get_bloginfo( 'url' ) );
	$adminUrl = preg_replace( "(^https?://)", "", get_admin_url() );

	return str_replace( $blogUrl . '/', ABSPATH, $adminUrl );
}
