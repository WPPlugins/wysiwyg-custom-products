<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 3/01/2017
 * Time: 1:32 PM
 */

if ( ! function_exists( 'intdiv' ) ) {
	/**
	 * Provide intdiv function for PHP < 7
	 * May return a different value as I'm not sure how PHP 7 rounds - assumed default
	 *
	 * @param number $dividend
	 * @param number $divisor
	 *
	 * @return int
	 *
	 * @throws \RangeException
	 *
	 * @since   1.0.1
	 * @updated 1.0.3
	 */
	function intdiv( $dividend, $divisor ) {
		if ( ( PHP_INT_MIN === $dividend ) && ( - 1 === $divisor ) ) {
			throw new \RangeException( 'Division of PHP_INT_MIN by -1 is not an integer' );
		}

		return (int) round( $dividend / $divisor );
	}
}

if ( ! function_exists( '_sanitize_text_fields' ) ) {
	/**
	 *
	 * Provide following for pre 4.7.0 WordPress
	 *
	 * Internal helper function to sanitize a string from user input or from the db
	 *
	 * @since  4.7.0
	 * @access private
	 *
	 * @param string $str           String to sanitize.
	 * @param bool   $keep_newlines optional Whether to keep newlines. Default: false.
	 *
	 * @return string Sanitized string.
	 */
	function _sanitize_text_fields( $str, $keep_newlines = false ) {
		$filtered = wp_check_invalid_utf8( $str );

		if ( strpos( $filtered, '<' ) !== false ) {
			$filtered = wp_pre_kses_less_than( $filtered );
			// This will strip extra whitespace for us.
			$filtered = wp_strip_all_tags( $filtered, false );

			// Use html entities in a special case to make sure no later
			// newline stripping stage could lead to a functional tag
			$filtered = str_replace( "<\n", "&lt;\n", $filtered );
		}

		if ( ! $keep_newlines ) {
			$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
		}
		$filtered = trim( $filtered );

		$found = false;
		while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
			$filtered = str_replace( $match[0], '', $filtered );
			$found    = true;
		}

		if ( $found ) {
			// Strip out the whitespace that may now exist after removing the octets.
			$filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
		}

		return $filtered;
	}

	/**
	 *
	 * Sanitizes a multiline string from user input or from the database.
	 *
	 * The function is like sanitize_text_field(), but preserves
	 * new lines (\n) and other whitespace, which are legitimate
	 * input in textarea elements.
	 *
	 * @see   sanitize_text_field()
	 *
	 * @since 4.7.0
	 *
	 * @param string $str String to sanitize.
	 *
	 * @return string Sanitized string.
	 */
	function sanitize_textarea_field( $str ) {
		$filtered = _sanitize_text_fields( $str, true );

		/**
		 * Filters a sanitized textarea field string.
		 *
		 * @since 4.7.0
		 *
		 * @param string $filtered The sanitized string.
		 * @param string $str      The string prior to being sanitized.
		 */
		return apply_filters( 'sanitize_textarea_field', $filtered, $str );
	}
}
