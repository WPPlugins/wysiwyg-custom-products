<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 7/10/16
 * Time: 12:48 PM
 */

namespace WCP;
use Exception;

if ( class_exists( 'Layout' ) ) {
	return;
}

/**
 * Class WCP\LayoutException
 *
 * @package    WCP
 * @subpackage Common
 *
 * @since      1.0.0
 */
class LayoutException extends Exception {
}

/**
 * Class Layout
 *
 * @package    WCP
 * @subpackage Common
 *
 * @since      1.0.0
 * @updated    1.1.1
 */
class Layout {
	/**
	 * Maximum number of text lines for a layout
	 */
	const MAX_LINES = 10;

	// Sanity checks - added 1.0.1
	/**
	 * Minimum width, height of any image specified for a layout
	 */
	const MIN_IMAGE_SIZE = 75;

	/**
	 * Maximum width, height of any image specified for a layout
	 */
	const MAX_IMAGE_SIZE = 2048;

	/**
	 * Maximum width, height of any image specified for a layout
	 */
	const DEFAULT_IMAGE_SIZE = 600;

	/**
	 * Minimum font size
	 */
	const MIN_FONT_SIZE = 6;
	/**
	 * @var int width of output image determined by size string
	 */
	public $width;
	/**
	 * @var int height of output image determined by size string
	 */
	public $height;
	/**
	 * @var int Maximum number of lines that current layout uses
	 */
	public $maxLines;
	/**
	 * @var int Current number of lines being formatted
	 */
	public $currentLines;
	/**
	 * @var array List of line formats
	 */
	public $formats = [];
	/**
	 * @var int Background image attachment Id. Only used on frontend. Set as part of product maintenance
	 */
	public $background;
	/**
	 * @var int Maintenance image attachment Id. Overridden by frontend - basic product image.
	 */
	public $image;
	/**
	 * @var int Foreground image attachment Id. Used at all stages. Set as part of layout maintenance
	 */
	public $overlay;
	/**
	 * @var string Sizing name used by WP and WooCommerce
	 */
	public $size;
	/**
	 * @var int Font fill color when working on layout
	 */
	public $inkColor;
	/**
	 * @var int Sizing box color when working on layout
	 */
	public $activeMouseColor;
	/**
	 * @var int nonSizing box color when working on layout
	 */
	public $inactiveMouseColor;

	/**
	 * @var array Starting layout for new installs
	 */
	protected static $defaultLayout =
		[
			'template' => [
				'SetupImage'         => 0,
				'OverlayImage'       => 0,
				'SetupWidth'         => self::DEFAULT_IMAGE_SIZE,
				'SetupHeight'        => self::DEFAULT_IMAGE_SIZE,
				'MaxLines'           => 1,
				'CurrentLines'       => 1,
				'MultilineReformat'  => '',
				'NumberOfLines'      => '',
				'SinglelineReformat' => '',
				'InkColor'           => 0x000000,  // Black
				'ActiveMouseColor'   => 0x00FFFF,  // Aqua
				'InactiveMouseColor' => 0x800080,  // Purple
				'Formats'            => [
					'Lines1' => [
						[
							'Y'          => 300, // self::DEFAULT_IMAGE_SIZE / 2, PHP < 5.6 fiddle
							'X'          => 300, // self::DEFAULT_IMAGE_SIZE / 2,
							'Width'      => 300, // self::DEFAULT_IMAGE_SIZE / 2,
							'Align'      => 'C',
							'MinFont'    => 45,
							'MaxFont'    => 60,
							'Attributes' => '',
							'Css'        => '',
						],
					],
				],
			],
		];
	/**
	 * @var array List of all layout names
	 */
	private $layouts = [];
	/**
	 * @var string Current layout name
	 */
	private $name;
	/**
	 * @var string Message used if a text is too long when user is editing a multi-line product
	 */
	private $multiLineReformatMsg;
	/**
	 * @var string Message used if too many lines (>maxLines) are used when use is editing a multi-line product
	 */
	private $numberOfLinesMsg;
	/**
	 * @var string Message used if a text is too long when user is editing a product that has one or more single line
	 *      fields
	 */
	private $singleLineReformatMsg;
	/**
	 * @var int $setupWidth of layout, Used for scaling
	 */
	private $setupWidth;
	/**
	 * @var int $setupHeight of layout, Used for scaling
	 */
	private $setupHeight;
	/**
	 * @var float X scaling factor
	 */
	private $scaleX;
	/**
	 * @var float Y scaling factor
	 */
	private $scaleY;

	/**
	 * Layout constructor.
	 *
	 * @param string $layoutName Optional, name of layout to load. Just layout list is loaded otherwise.
	 *
	 * @throws LayoutException  If layout is invalid
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function __construct( $layoutName = '' ) {
		$this->layouts = self::loadLayouts();
		if ( ! empty( $layoutName ) ) {
			$this->load_by_name( $layoutName );
		}

	}

	/**
	 * Loads layouts option and sanitizes to make sure nothings corrupt
	 *
	 * @param  string $prefix Optional, specify option prefix to get layouts for
	 *
	 * @return array
	 *
	 * @throws \WCP\LayoutException
	 *
	 * @since   1.0.1
	 * @updated 1.0.5
	 */
	public static function loadLayouts( $prefix = Wcp_Plugin::OPTION_PREFIX ) {
		$result  = [];
		$layouts = (array) get_option( 'layouts', [], null, $prefix );
		if ( ! is_array( $layouts ) ) {
			throw new LayoutException( 'Wysiwyg Custom Products layouts have been corrupted' );
		}

		foreach ( $layouts as $layout ) {
			$result[] = sanitize_text_field( $layout );
		}

		return $result;
	}

	/**
	 * Validator to see if a string is the name of an existing layout
	 *
	 * @param string $checkName
	 *
	 * @return bool
	 *
	 * @since   1.0.1
	 * @updated 1.0.5
	 */
	public static function does_layout_exist( $checkName ) {
		try {
			$result = in_arrayi( $checkName, self::loadLayouts(), true );
		}
		catch
		( \Exception $e ) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Retrieves and validates the named layout from the database
	 *
	 * @param $layoutName
	 *
	 * @return bool|mixed
	 *
	 * @since 1.0.1
	 */
	public static function get_layout_array( $layoutName ) {
		$layout = get_option_entquotes( $layoutName );
		try {
			self::is_layout_valid( $layout );
		} catch ( LayoutException $e ) {
			return false;
		}

		return $layout;
	}

	/**
	 * Makes array safe for Ajax GET
	 *
	 * @param string $layoutName
	 *
	 * @return array
	 *
	 * @since   1.0.1
	 */
	public static function ajax_get( $layoutName ) {
		$result = self::get_layout_array( $layoutName );

		if ( $result ) {
			// Override plain text
			$result['MultilineReformat']  = self::esc_textarea_json_output( $result['MultilineReformat'] );
			$result['NumberOfLines']      = self::esc_textarea_json_output( $result['NumberOfLines'] );
			$result['SinglelineReformat'] = self::esc_textarea_json_output( $result['SinglelineReformat'] );
		}

		return $result;
	}

	/**
	 *  Deletes all WCP layout data
	 *
	 * @since   1.0.0
	 * @updated 1.0.4
	 */
	public static function delete_layout_data() {
		try {
			$layouts = self::loadLayouts();
			foreach ( $layouts as $layout ) {
				delete_option( $layout );
			}
		} catch ( \Exception $e ) {
			// Ignore errors
		}
		delete_option( 'layouts' );
	}

	/**
	 * Validator to see if an array has correct layout information
	 *
	 * @param array &$layout
	 * @param bool  $sanitize Indicates that fields should be sanitized as necessary (Optional)
	 *
	 * @throws LayoutException If layout is invalid
	 *
	 * @since   1.0.1
	 * @updated 1.1.1
	 */
	public static function is_layout_valid( array &$layout, $sanitize = false ) {
		if ( ! is_array( $layout ) ) {
			throw new LayoutException( 'Invalid Wysiwyg Custom Products format supplied' );
		}

		if ( ! isset( $layout['InkColor'] ) ) { // 1.1.1 Format update
			$layout['InkColor']           = self::$defaultLayout['template']['InkColor'];
			$layout['ActiveMouseColor']   = self::$defaultLayout['template']['ActiveMouseColor'];
			$layout['InactiveMouseColor'] = self::$defaultLayout['template']['InactiveMouseColor'];
		}

		// Check to see that all, but only, the required values are supplied
		if ( array_diff( array_keys( self::$defaultLayout['template'] ), array_keys( $layout ) ) !== [] ) {
			throw new LayoutException( 'Invalid Wysiwyg Custom Products format supplied' );
		}

		self::int_check( $layout['SetupImage'] ); // Assume any int is valid
		self::int_check( $layout['OverlayImage'] ); // Assume any int is valid
		self::int_check( $layout['SetupWidth'], self::MIN_IMAGE_SIZE, self::MAX_IMAGE_SIZE );
		self::int_check( $layout['SetupHeight'], self::MIN_IMAGE_SIZE, self::MAX_IMAGE_SIZE );
		self::int_check( $layout['MaxLines'], 1, self::MAX_LINES );
		$maxLines = $layout['MaxLines'];
		self::int_check( $layout['CurrentLines'], 1, $maxLines );
		self::is_string( $layout['MultilineReformat'] );
		self::is_string( $layout['NumberOfLines'] );
		self::is_string( $layout['SinglelineReformat'] );
		self::int_check( $layout['InkColor'], 0, 0xFFFFFF );
		self::int_check( $layout['ActiveMouseColor'], 0, 0xFFFFFF );
		self::int_check( $layout['InactiveMouseColor'], 0, 0xFFFFFF );

		if ( $sanitize ) {
			$layout['MultilineReformat']  = sanitize_textarea_field( $layout['MultilineReformat'] );
			$layout['NumberOfLines']      = sanitize_textarea_field( $layout['NumberOfLines'] );
			$layout['SinglelineReformat'] = sanitize_textarea_field( $layout['SinglelineReformat'] );
		}


		if ( ! is_array( $layout['Formats'] ) ) {
			throw new LayoutException( 'Invalid Wysiwyg Custom Products format supplied' );
		}

		$formatKeys = [];
		for ( $i = 1; $i <= $maxLines; $i ++ ) {
			$formatKeys[] = 'Lines' . $i;
		}

		// Check to see that all, but only, the required formats are supplied
		/* @var array $formats */
		$formats = &$layout['Formats'];
		if ( array_diff( $formatKeys, array_keys( $formats ) ) !== [] ) {
			throw new LayoutException( 'Invalid Wysiwyg Custom Products format supplied' );
		}

		// Now make sure each format has the correct information
		$formatKeys  = array_keys( self::$defaultLayout['template']['Formats']['Lines1'][0] );
		$maxFontSize = intdiv( $layout['SetupHeight'], 2 );
		for ( $i = 1; $i <= $maxLines; $i ++ ) {
			self::is_format_valid( $formats, $i, $formatKeys, $maxFontSize, $sanitize );
		}
	}

	/**
	 * Determines size of image being shown based on $size string and sets up
	 * scaling factors
	 *
	 * @param string $size
	 *
	 * @since   1.0.0
	 * @updated 1.0.8
	 */
	public function set_size( $size ) {
		$this->size   = $size;
		$this->width  = self::DEFAULT_IMAGE_SIZE;
		$this->height = self::DEFAULT_IMAGE_SIZE;

		$attributes = wc_get_image_size( $size );
		if ( $attributes ) {
			$this->width  = maybe_get( $attributes, 'width', $this->width );
			$this->height = maybe_get( $attributes, 'height', $this->height );
		}

		$this->scaleX = $this->width / $this->setupWidth;
		$this->scaleY = $this->height / $this->setupHeight;
	}

	/**
	 * Name getter
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Name setter
	 *
	 * @param string $name
	 *
	 * @since 1.0.1
	 */
	public function setName( $name ) {
		$this->name = sanitize_text_field( $name );
	}

	/**
	 * Message getter
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	public function getMultiLineReformatMsg() {
		return $this->multiLineReformatMsg;
	}

	/**
	 * Message setter
	 *
	 * @param string $msg
	 *
	 * @since 1.0.1
	 */
	public function setMultiLineReformatMsg( $msg ) {
		$this->multiLineReformatMsg = self::sanitize_textarea_input( $msg );
	}

	/**
	 * Message getter
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	public function getNumberOfLinesMsg() {
		return $this->numberOfLinesMsg;
	}

	/**
	 * Message setter
	 *
	 * @param string $msg
	 *
	 * @since 1.0.1
	 */
	public function setNumberOfLinesMsg( $msg ) {
		$this->numberOfLinesMsg = self::sanitize_textarea_input( $msg );
	}

	/**
	 * Message getter
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	public function getSingleLineReformatMsg() {
		return $this->singleLineReformatMsg;
	}

	/**
	 * Message setter
	 *
	 * @param string $msg
	 *
	 * @since 1.0.1
	 */
	public function setSingleLineReformatMsg( $msg ) {
		$this->singleLineReformatMsg = self::sanitize_textarea_input( $msg );
	}

	/**
	 * Gets the a hex string representation of the requested color
	 *
	 * @param string $color Color to return as string hex
	 *
	 * @return string
	 *
	 * @since    1.1.1
	 */
	public function getColorString( $color ) {
		switch ( $color ) {
			case 'ink' :
				$value = $this->inkColor;
				break;
			case 'size' :
				$value = $this->activeMouseColor;
				break;
			case 'non-size' :
				$value = $this->inactiveMouseColor;
		}

		return htmlColorHex( $value );
	}

	/**
	 * @param string $layoutName
	 *
	 * @throws LayoutException If layout does not exist or is invalid
	 *
	 * @since   1.0.0
	 */
	public function load_by_name( $layoutName ) {
		$layout = get_option_entquotes( $layoutName );
		$this->load_layout( $layout, $layoutName );
	}

	/**
	 * @param array  $layout
	 * @param string $layoutName
	 *
	 * @throws LayoutException  If layout is invalid
	 *
	 * @since   1.0.0
	 * @updated 1.1.1
	 */
	public function load_layout( $layout, $layoutName ) {
		self::is_layout_valid( $layout );

		$this->name               = $layoutName;
		$this->setupWidth         = $layout['SetupWidth'];
		$this->setupHeight        = $layout['SetupHeight'];
		$this->width              = $this->setupWidth;  // Assume same for starters
		$this->height             = $this->setupHeight;
		$this->scaleX             = 1.0;
		$this->scaleY             = 1.0;
		$this->maxLines           = $layout['MaxLines'];
		$this->currentLines       = $layout['CurrentLines'];
		$this->image              = $layout['SetupImage'];
		$this->overlay            = $layout['OverlayImage'];
		$this->inkColor           = $layout['InkColor'];
		$this->activeMouseColor   = $layout['ActiveMouseColor'];
		$this->inactiveMouseColor = $layout['InactiveMouseColor'];

		$this->setMultiLineReformatMsg( $layout['MultilineReformat'] );
		$this->setNumberOfLinesMsg( $layout['NumberOfLines'] );
		$this->setSingleLineReformatMsg( $layout['SinglelineReformat'] );

		$this->formats = $layout['Formats'];

		do_action( 'load_layout' );
	}

	/**
	 * Converts current values as an array for saving & Ajax GET
	 *
	 * @return array
	 *
	 * @since   1.0.0
	 * @updated 1.1.1
	 */
	public function as_array() {
		$result                       = [];
		$result['SetupImage']         = $this->image;
		$result['OverlayImage']       = $this->overlay;
		$result['SetupWidth'] = $this->setupWidth;
		$result['SetupHeight'] = $this->setupHeight;
		$result['MaxLines']           = $this->maxLines;
		$result['CurrentLines']       = $this->currentLines;
		$result['Formats']            = $this->formats;
		$result['InkColor'] = $this->inkColor;
		$result['ActiveMouseColor'] = $this->activeMouseColor;
		$result['InactiveMouseColor'] = $this->inactiveMouseColor;
		$result['MultilineReformat']  = $this->multiLineReformatMsg;
		$result['NumberOfLines']      = $this->numberOfLinesMsg;
		$result['SinglelineReformat'] = $this->singleLineReformatMsg;

		$result = apply_filters( 'layout_as_array', $result );

		return apply_filters( 'layout_as_array_' . $this->name, $result );
	}

	/**
	 * Saves current layout
	 *
	 * @since   1.0.0
	 */
	public function save() {
		$array = $this->as_array();
		array_to_int( $array );
		update_option( $this->name, $array );
	}

	/**
	 * Clears any existing layouts (if present and specified) then creates and saves the defaultLayout from above
	 *
	 * @param bool $overwrite If set forces current layouts to be discarded and the default saved
	 *
	 * @throws LayoutException  If layout is invalid
	 *
	 * @return bool
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function save_defaults( $overwrite = false ) {
		if ( $this->layouts && ! $overwrite ) {
			return false;
		}

		self::delete_layout_data();

		$optionNames = []; // add default layouts
		foreach ( self::$defaultLayout as $name => $option ) {
			if ( '' === $option['MultilineReformat'] ) {
				$option['MultilineReformat'] = self::get_overflow_message( 'MultilineReformat' );
			}
			if ( '' === $option['NumberOfLines'] ) {
				$option['NumberOfLines'] = self::get_overflow_message( 'NumberOfLines' );
			}
			if ( '' === $option['SinglelineReformat'] ) {
				$option['SinglelineReformat'] = self::get_overflow_message( 'SinglelineReformat' );
			}

			$this->load_layout( $option, $name );

			$array = $this->as_array();
			array_to_int( $array, false );
			$array = apply_filters( 'save_default_array', $array );
			$array = apply_filters( 'save_default_array_' . $name, $array );

			add_option( $name, $array, false );
			$optionNames[] = $name;
		}

		$optionNames = apply_filters( 'save_default_names', $optionNames );
		add_option( 'layouts', $optionNames, false );

		return true;
	}

	/**
	 * Creates an abbreviated array for JSON purposes for the format specified by the number of lines
	 *
	 * @param $numberOfLines
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 */
	public function compact_format( $numberOfLines ) {
		$lines   = maybe_get( $this->formats, 'Lines' . $numberOfLines, [] );
		$formats = [];

		/** @noinspection ForeachSourceInspection */
		foreach ( $lines as $line ) {
			$line      = apply_filters( 'before_line_implode', $line ); // Allows for modification of any of the following values
			$line      = implode( ',', [
				$this->scaleY( $line['Y'] ),
				$this->scaleX( $line['X'] ),
				$this->scaleX( $line['Width'] ),
				$line['Align'],
				$this->scaleY( $line['MinFont'] ),
				$this->scaleY( $line['MaxFont'] ),
				$line['Attributes'],
				$line['Css'],
			] );
			$line      = apply_filters( 'after_line_implode', $line ); // Allows for the addition of extra values
			$formats[] = $line;
		}

		return apply_filters( 'compact_format',
			implode( '|', $formats ) ); // Allows for the addition of extra format lines
	}

	/**
	 * Adjusts the Y value depending on size of setup image and displayed size
	 *
	 * @param number $value
	 *
	 * @return int
	 *
	 * @since   1.0.8
	 */
	public function scaleY( $value ) {
		return floor( $value * $this->scaleY );
	}

	/**
	 * Adjusts the Y value depending on size of setup image and displayed size
	 *
	 * @param number $value
	 *
	 * @return int
	 *
	 * @since   1.0.8
	 */
	public function scaleX( $value ) {
		return floor( $value * $this->scaleX );
	}

	/**
	 * Validator to make sure a value is an integer and within a range if specified
	 *
	 * @param     $value
	 * @param int $min
	 * @param int $max
	 *
	 * @throws LayoutException
	 *
	 * @since 1.0.1
	 */
	private static function int_check( $value, $min = PHP_INT_MIN, $max = PHP_INT_MAX ) {
		if ( ! int_range_check( $value, $min, $max ) ) { // Wrong type or out of range
			throw new LayoutException( 'Invalid Wysiwyg Custom Products format supplied' );
		}
	}

	/**
	 * Validator to make sure a value is a string
	 *
	 * @param $value
	 *
	 * @throws LayoutException
	 *
	 * @since 1.0.1
	 */
	private static function is_string( $value ) {
		if ( ! \is_string( $value ) ) { // Wrong type
			throw new LayoutException( 'Invalid Wysiwyg Custom Products format supplied' );
		}
	}

	/**
	 * Checks to make sure $Layout['Formats']['Lines' . $lines] is a suitably formed format
	 *
	 * @param array   &$formatArray $Layout['Formats']
	 * @param integer $lines        Number of lines in this format
	 * @param array   $formatKeys   ['Y', 'X', ..., 'MaxFont'] Derived from default layout
	 * @param integer $maxFontSize  Calculated as half image height
	 * @param bool    $sanitize     Indicates that fields should be sanitized as necessary
	 *
	 * @throws LayoutException
	 *
	 * @since   1.0.1
	 * @updated 1.1.0
	 */
	private static function is_format_valid( array &$formatArray, $lines, array $formatKeys, $maxFontSize, $sanitize ) {
		/* @var array $format */
		$format = &$formatArray[ 'Lines' . $lines ];
		// Does the format contain the required number of line entries
		if ( count( $format ) !== $lines ) {
			throw new LayoutException( 'Invalid Wysiwyg Custom Products format supplied' );
		}

		// Does each line in the format contain the correct information
		/* @var array $line */
		foreach ( $format as &$line ) {
			// 1.1.0 Update - add 'Attributes' and Css as necessary
			if ( ! isset( $line['Attributes'] ) ) {
				$line['Attributes'] = '';
			}

			if ( ! isset( $line['Css'] ) ) {
				$line['Css'] = '';
			}

			if ( array_diff( $formatKeys, array_keys( $line ) ) !== [] ) {
				throw new LayoutException( 'Invalid Wysiwyg Custom Products format supplied' );
			}

			self::int_check( $line['Y'], 0, self::MAX_IMAGE_SIZE );
			self::int_check( $line['X'], 0, self::MAX_IMAGE_SIZE );
			self::int_check( $line['Width'], 0, self::MAX_IMAGE_SIZE );
			self::int_check( $line['MinFont'], self::MIN_FONT_SIZE, $maxFontSize );
			self::int_check( $line['MaxFont'], $line['MinFont'], $maxFontSize );

			$align = $line['Align'];
			if ( ! is_string( $align ) || ( strlen( $align ) !== 1 ) || ( strpos( 'CLR', $align ) === false ) ) {
				throw new LayoutException( 'Invalid Wysiwyg Custom Products format supplied' );
			}
			self::is_string( $line['Attributes'] );
			self::is_string( $line['Css'] );

			if ( $sanitize ) {
				$line['Attributes'] = sanitize_text_field( $line['Attributes'] );
				$line['Css'] = sanitize_text_field( $line['Css'] );
				$line['Css'] = str_replace( [ ',', '|' ], ' ', $line['Css'] ); // Remove compact line delimiters
			}

			if ( strpos( $line['Css'], ',' ) || strpos( $line['Css'], '|' ) ) {  // Ensure none have snuck in
				throw new LayoutException( 'Invalid Wysiwyg Custom Products format supplied' );
			}
		}
	}

	/**
	 * Self check to make sure error messages are safe before accepting
	 *
	 * @param string $msg
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	private static function sanitize_textarea_input( $msg ) {
		return sanitize_textarea_field( stripslashes( $msg ) );
	}

	/**
	 * Escapes text area for JSON output
	 *
	 * @param string $msg
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	private static function esc_textarea_json_output( $msg ) {
		return htmlspecialchars_decode( esc_textarea( stripslashes( $msg ) ),
			ENT_QUOTES ); // Need to replace any quotes that the esc_textarea
	}

	/**
	 * Gets a default overflow message when one isn't specified. Done this way to allow for translation
	 *
	 * @param string $messageName
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	private static function get_overflow_message( $messageName ) {
		switch ( $messageName ) {
			case 'MultilineReformat':
				// translators: text used when customer uses too long a text in a text area
				$message = esc_html__( 'Please continue with message. Press [Enter] for new lines - type size will adjust. Tip: Edit line breaks to get desired layout.',
					'wysiwyg-custom-products' );
				break;
			case 'NumberOfLines':
				// translators: text used when customer uses too many lines in a text area
				$message = esc_html__( "Sorry, that's too many lines.", 'wysiwyg-custom-products' );
				break;
			case 'SinglelineReformat':
				// translators: text used when customer uses too long a text in a single text input
				$message = esc_html__( 'Text is too long to fit. Please check length of text.',
					'wysiwyg-custom-products' );
				break;
			default:
				$message = '';
		}

		return apply_filters( 'get_overflow_msg_' . $messageName, $message );
	}
}
