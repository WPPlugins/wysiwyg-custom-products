<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 19/08/16
 * Time: 11:28 AM
 *
 * @since   1.0.0
 */

namespace WCP;
/**
 * Routine to html decode - including any quotes - a string or array of strings
 *
 * @param mixed $input
 *
 * @return array|mixed|string
 *
 * @since   1.0.0
 * @updated 1.0.1
 */
function html_ent_quotes( $input ) {
	if ( is_string( $input ) ) {
		$result = htmlspecialchars_decode( $input, ENT_QUOTES );
	} elseif ( is_array( $input ) ) {
		$result = [];
		/** @noinspection ForeachSourceInspection */
		foreach ( $input as $key => $value ) {
			$result[ $key ] = is_string( $value ) ? htmlspecialchars_decode( $value, ENT_QUOTES ) : $value;
		}
	} else {
		$result = $input;
	}

	return $result;
}

/**
 * Routine to replace the ubiquitous x = isset(array[y]) ? array[y] : defaultX calls
 * x = maybe_get(array, y, default)
 *
 * Works with string or integer keys, returns a false by default if default is not specified.
 * Can optionally trim any string result
 *
 * @param array      $array
 * @param int|string $field
 * @param mixed      $default
 * @param bool       $trim
 *
 * @return mixed
 *
 * @since   1.0.0
 */
function maybe_get( $array, $field, $default = false, $trim = true ) {
	$result = $default;
	if ( is_string( $field ) ) {
		$result = isset( $array[ $field ] ) ? $array[ $field ] : $default;
	} elseif ( is_int( $field ) ) {
		if ( $field < count( $array ) ) {
			$result = $array[ $field ];
		}
	}

	if ( is_string( $result ) && $trim ) {
		return trim( $result );
	} else {
		return $result;
	}
}

/**
 * Goes through the passed array and turns any number or number string into an integer
 * Optionally can treat the empty string as a 0
 *
 * @param array $array
 * @param bool  $emptyStringToZero
 *
 * @return array
 *
 * @since   1.0.0
 */
function array_to_int( &$array, $emptyStringToZero = true ) {
	array_walk_recursive( $array, 'WCP\to_int', $emptyStringToZero );

	return $array;
}

/**
 * Function used for the above array walk
 *
 * @param mixed $value
 * @param void  $key
 * @param bool  $emptyStringToZero
 *
 * @since   1.0.0
 */
function to_int(
	&$value,
	$key,
	$emptyStringToZero
) {
	/** @noinspection ReferenceMismatchInspection */
	if ( is_numeric( $value ) ) {
		$value = (int) $value;
	} elseif ( $emptyStringToZero && ( '' === $value ) ) {
		$value = 0;
	}
}

/**
 * Ensures $value is an integer and that it's between min and max
 *
 * @param     $value
 * @param int $min
 * @param int $max
 *
 * @return bool
 *
 * @since 1.0.1
 */
function int_range_check( $value, $min = PHP_INT_MIN, $max = PHP_INT_MAX ) {
	if ( ! is_int( $value ) ) {
		return false;
	}

	return ( $value >= $min && $value <= $max );
}

/**
 * Ensures $str is a string and that it contains at least one character
 *
 * @param $str
 *
 * @return bool
 *
 * @since 1.0.1
 */
function is_non_empty_string( $str ) {
	return is_string( $str ) && ( strlen( trim( $str ) ) > 0 );
}

/**
 * Case insensitive array search
 *
 * @param      $needle
 * @param      $haystack
 * @param bool $strict optional - True checks types
 *
 * @return bool
 *
 * @since 1.0.5
 */
function in_arrayi( $needle, $haystack, $strict = false ) {
	return in_array( strtolower( $needle ), array_map( 'strtolower', $haystack ), $strict );
}

/**
 * Creates a colour string suitable for use in CSS, HTML etc. from an integer value
 *
 * @param int $colorValue
 *
 * @return string
 *
 * @since 1.1.1
 */
function htmlColorHex( $colorValue ) {
	return '#' . substr( '000000' . dechex( $colorValue ), - 6 );
}
