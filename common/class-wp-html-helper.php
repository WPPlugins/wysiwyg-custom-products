<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 13/12/2016
 * Time: 3:41 PM
 */

namespace WCP;
/**
 * Class Wp_Html_Helper
 *
 * @package WCP
 * @subpackage
 *
 * @since   1.0.1
 * @updated 1.1.0
 */

if ( class_exists( 'Wp_Html_Helper' ) ) {
	return;
}

require_once WCP_COMMON_DIR . 'class-html-helper.php';

class Wp_Html_Helper extends Html_Helper {

	/**
	 * Used to determine if this should currently be doing any escaping
	 *
	 * @var bool
	 */
	private $doEscape = true;

	/**
	 * Stores doEscape value for pushes and pops
	 *
	 * @var array
	 */
	private $doEscapeStack = [];

	/**
	 * Save's last doEscape value and sets new one
	 *
	 * @param $doEscape
	 *
	 * @since 1.0.1
	 */
	public function pushDoEscape( $doEscape ) {
		$this->doEscapeStack[] = $this->doEscape;
		$this->doEscape        = $doEscape;
	}

	/**
	 * Restores last doEscape value
	 *
	 * @since 1.0.1
	 */
	public function popDoEscape() {
		$this->doEscape = true; // Assume default value
		if ( count( $this->doEscapeStack ) ) {
			$this->doEscape = array_pop( $this->doEscapeStack );
		}
	}/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Constructs an opening tag. eg o_tag('span') becomes <span>
	 * Escapes the tag. Everything else is escaped later
	 *
	 * @param string       $tag
	 * @param string|array $classes
	 * @param string       $id
	 * @param array        $attributes
	 * @param array        $styles
	 * @param bool         $selfClosing See Html_Helper for more information on selfClosing
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function o_tag(
		$tag, $classes = '', $id = '', array $attributes = [], array $styles = [],
		$selfClosing = false
	) {
		if ( $this->doEscape ) {
			$tag = tag_escape( $tag );
		}

		return parent::o_tag( $tag, $classes, $id, $attributes, $styles,
		                      $selfClosing );
	}

	/**
	 * Closes one or more tags separated by <space>. eg c_tag('span p div') produces </span></p></div>
	 * Escapes each tag
	 *
	 * @param string $tag
	 *
	 * @return null|string
	 *
	 * @since 1.0.1
	 */
	public function c_tag( $tag ) {
		if ( $this->doEscape ) {
			$html = '';
			$tags = explode( ' ', $tag );

			$internal       = $this->internal;
			$this->internal = true;
			foreach ( $tags as $aTag ) {
				$html .= parent::c_tag( tag_escape( $aTag ) );
			}

			return $this->output( $html, $internal );
		} else {
			return parent::c_tag( $tag );
		}
	}/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Builds and then outputs a complete html <tag attributes>data</tag>
	 * Escapes the data. All else is escaped by the later routines.
	 *
	 * eg tag('span', 'This is a span') becomes <span>This is a span</span>
	 *  tag('p', 'paragraph', 'my-css', 'my-id') becomes <p id='my-id' class='my-css'>paragraph</p>
	 *
	 * @param string       $tag
	 * @param string       $data
	 * @param string|array $classes
	 * @param string       $id
	 * @param array        $attributes
	 * @param array        $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.1
	 */
	public function tag( $tag, $data, $classes = '', $id = '', array $attributes = [], array $styles = [] ) {
		if ( $this->doEscape ) {
			$data = esc_html( $data );
		}

		return parent::tag( $tag, $data, $classes, $id, $attributes, $styles );
	}

	/**
	 * Creates the opening tag for a form
	 *
	 * @param string $name
	 * @param string $method
	 * @param string $action
	 * @param string $target
	 * @param array  $attributes
	 *
	 * @return null|string
	 *
	 * @since    1.1.0
	 */
	public function o_form( $name, $method = 'post', $action = '', $target = '', array $attributes = [] ) {
		if ( $this->doEscape ) {
			$action = esc_url( $action );
		}

		return parent::o_form( $name, $method, $action, $target, $attributes );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Creates a text area input field
	 * Escapes the value (text)
	 *
	 * @param string       $id
	 * @param int          $rows
	 * @param int          $cols
	 * @param string       $label
	 * @param string|null  $value
	 * @param string|array $classes
	 * @param array        $attributes
	 * @param array        $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.1
	 */
	public function text_area(
		$id, $rows, $cols, $label = null, $value = null, $classes = '', array $attributes = [],
		array $styles = []
	) {
		if ( $this->doEscape ) {
			$value = esc_textarea( $value );
		}

		return parent::text_area( $id, $rows, $cols, $label, $value, $classes, $attributes,
		                          $styles );
	}/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Generates a link
	 * Escapes href
	 *
	 * @param string       $text
	 * @param string       $href
	 * @param string|array $classes
	 * @param string       $id
	 * @param array        $attributes
	 * @param array        $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.1
	 */
	public function a( $text, $href, $classes = '', $id = '', array $attributes = [], array $styles = [] ) {
		if ( $this->doEscape ) {
			$href = esc_url( $href );
		}

		return parent::a( $text, $href, $classes, $id, $attributes, $styles );
	}/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Outputs an <img  /> tag
	 * Escapes src
	 *
	 * @param        $src
	 * @param null   $width
	 * @param null   $height
	 * @param string $alt
	 * @param string $classes
	 * @param string $id
	 * @param array  $attributes
	 * @param array  $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.1
	 */
	public function img(
		$src, $width = null, $height = null, $alt = '', $classes = '', $id = '',
		array $attributes = [],
		array $styles = []
	) {
		if ( $this->doEscape ) {
			$src = esc_url( $src );
		}

		return parent::img( $src, $width, $height, $alt, $classes, $id, $attributes,
		                    $styles );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Creates SVG <image ... /> tag getting the correct image and size from the WP size string
	 * Escapes href
	 *
	 * @param int    $attachmentId
	 * @param string $size
	 * @param bool   $forceSize Forces image size based on $size with no manipulation by WP for admin editing
	 * @param string $classes
	 * @param string $id
	 * @param array  $attributes
	 * @param array  $styles
	 *
	 * @return null|string
	 *
	 * @since   1.0.8
	 * @updated 1.1.0
	 */
	public function svg_sized_img(
		$attachmentId, $size, $forceSize = false, $classes = '', $id = '', array $attributes = [],
		array $styles = []
	) {
		$image = wp_get_attachment_image_src( $attachmentId, $size );

//		Overriding WP's resizing of image in admin for "editing"
		if ( $forceSize ) {
			$attributes = wc_get_image_size( $size );
			if ( $attributes ) {
				$image['width']  = maybe_get( $attributes, 'width', $image['width'] );
				$image['height'] = maybe_get( $attributes, 'height', $image['height'] );
			}

		}

		return $this->svg_img( $image['url'], $image['width'], $image['height'], $classes, $id, $attributes,
			$styles );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Creates SVG <image ... /> tag with some standard settings
	 * Escapes href
	 *
	 * @param string $href
	 * @param int    $width
	 * @param int    $height
	 * @param string $classes
	 * @param string $id
	 * @param array  $attributes
	 * @param array  $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.1
	 */
	public function svg_img(
		$href, $width, $height, $classes = '', $id = '', array $attributes = [],
		array $styles = []
	) {
		if ( $this->doEscape ) {
			$href = esc_url( $href );
		}

		return parent::svg_img( $href, $width, $height, $classes, $id, $attributes,
		                        $styles );
	}

	/**
	 * Outputs a <label for="id">label</label> tag. If $label is empty, nothing is generated.
	 * Escapes id
	 *
	 * @param string $id
	 * @param string $label
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function lbl( $id, $label ) {
		if ( $this->doEscape ) {
			$id = tag_escape( $id );
		}

		return parent::lbl( $id, $label );
	}/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Creates one or more radio buttons with the same name attribute. The button/values can be specified by
	 * the $valueHandling parameter. See:  set_options
	 *
	 * Escapes the separator. All else is handled later
	 *
	 * @param string       $name
	 * @param array        $options
	 * @param mixed        $value
	 * @param string       $separator
	 * @param int          $valueHandling
	 * @param string|array $classes
	 * @param array        $attributes
	 * @param array        $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function rb(
		$name, $options, $value = null, $separator = '<br>', $valueHandling = self::VALUE_EQUALS_LABEL,
		$classes = '', array $attributes = [], array $styles = []
	) {
		$escapedSeparator = $separator;

		if ( $this->doEscape ) {
			$escapedSeparator = wp_kses( $separator, [ 'br' => [] ] );
		}

		return parent::rb( $name, $options, $value, $escapedSeparator, $valueHandling, $classes, $attributes,
		                   $styles );
	}

	/**
	 * Routine to create/modify select and radio button option arrays
	 * escapes each label (key) and value as appropriate
	 *
	 *
	 * Array is modified according to the $valueHandling parameter:
	 *
	 * Html_Helper::VALUE_TO_LABEL, Html_Helper::VALUE_IS_ZERO_BASED, Html_Helper::VALUE_IS_ONE_BASED
	 * array is untouched it is already set or easy numeric values
	 *
	 * Html_Helper::VALUE_EQUALS_LABEL
	 * Non associative array supplied but want value to be same as text, so array is combined to make [Value] = Value
	 *
	 * Html_Helper:: LABEL_TO_VALUE
	 * Associative array supplied but the opposite way round to how it's used within helper, so it's swapped to make
	 * [Value] = Key
	 *
	 * @param array $options
	 * @param int   $valueHandling
	 *
	 * @return array
	 *
	 * @since 1.0.1
	 */
	public function set_options( $options, $valueHandling ) {
		$result = parent::set_options( $options, $valueHandling );

		// By now key = label and value is $result[key]
		if ( $this->doEscape ) {
			$result = self::esc_array( $result, 'esc_html', [
				'WCP\Wp_Html_Helper',
				'maybe_esc_attr',
			] ); // Maybe escape because value could be number
		}

		return $result;
	}

	/**
	 * Applies supplied escapes to every key and value in the array
	 *
	 * @param array         $array
	 * @param null|callable $escKey
	 * @param null|callable $escValue
	 *
	 * @return array
	 * @since 1.0.1
	 */
	protected static function esc_array( array $array, $escKey, $escValue ) {
		$escapedResult = [];
		foreach ( $array as $key => $value ) {
			$escapedKey                   = is_callable( $escKey ) ? $escKey( $key ) : $key;
			$escapedValue                 = is_callable( $escValue ) ? $escValue( $value ) : $value;
			$escapedResult[ $escapedKey ] = $escapedValue;
		}

		return $escapedResult;
	}

	/**
	 * Escape an HTML tag name.
	 *
	 * @param string $attrName
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	protected static function esc_attr_name( $attrName ) {
		return strtolower( preg_replace( '/[^a-zA-Z0-9_:\-]/', '', $attrName ) );
	}

	/**
	 * If set, creates an id attribute
	 * Escapes the id
	 *
	 * @param string|null $id
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	protected function get_id( $id ) {
		if ( $this->doEscape ) {
			$id = self::maybe_esc_attr( $id );
		}

		return parent::get_id( $id );
	}

	/**
	 * If set, creates a name attribute
	 * Escapes the name
	 *
	 * @param string|null $name
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	protected function get_name( $name ) {
		if ( $this->doEscape ) {
			$name = self::maybe_esc_attr( $name );
		}

		return parent::get_name( $name );
	}

	/**
	 * Creates a 'style="color:blue;text-align:center" html attribute based on content of array
	 * Escapes each style name and value
	 *
	 * @param array $styles
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	protected function get_styles( $styles ) {
		if ( $this->doEscape ) {
			$styles = self::esc_array( $styles, 'esc_attr', 'esc_attr' );
		}

		return parent::get_styles( $styles );
	}

	/**
	 * Creates a single attribute="value" or attribute='value' string.
	 * Escapes the attribute and the value
	 *
	 * If $value is contained in an array, it forces use of single quotes, necessary for json data
	 * eg set_attribute( 'alt', 'A "nice" picture' )
	 * becomes alt="A "nice" picture"  --oops, so use
	 * set_attribute( 'alt', ['A "nice" picture'] )
	 * to become alt='A "nice" picture'
	 *
	 * But still use set_attribute( 'alt', "Can't touch this" )
	 *
	 * @param string $attribute
	 * @param mixed  $value
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	protected function set_attribute( $attribute, $value ) {
		if ( $this->doEscape ) {

			$quote = '"';

			if ( is_array( $value ) ) {
				$quote = "'";
				$value = $value[0];
			}

			return ' ' . self::esc_attr_name( $attribute ) . '=' . $quote . esc_attr( $value ) . $quote;
		} else {
			return parent::set_attribute( $attribute, $value );
		}
	}

	/**
	 * Creates the opening part of an <input .... /> tag.
	 * Escapes type and value. $id, $name done later
	 *
	 * @param string      $type
	 * @param string|null $value
	 * @param string|null $id
	 * @param string|null $name
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	protected function start_tag_input( $type, $value, $id, $name = null ) {
		if ( $this->doEscape ) {
			return parent::start_tag_input( esc_attr( $type ), self::maybe_esc_attr( $value ), $id, $name );
		} else {
			return parent::start_tag_input( $type, $value, $id, $name );
		}
	}

	/**
	 * Checks to make sure that a value is a string before applying esc_attr
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 *
	 * @since 1.0.1
	 */
	private static function maybe_esc_attr( $value ) {
		if ( is_string( $value ) ) {
			return esc_attr( $value );
		} else {
			return $value;
		}
	}

}
