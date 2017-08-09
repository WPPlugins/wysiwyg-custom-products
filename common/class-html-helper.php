<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 5/09/16
 * Time: 1:17 PM
 *
 * @since 1.0.0
 */

namespace WCP;
/**
 * Class Html_Helper
 *
 * @package    WCP
 * @subpackage Helpers
 *
 * @since      1.0.0
 * @updated    1.1.0
 */

if ( class_exists( 'Html_Helper' ) ) {
	return;
}

// Todo add syntax checker for HTML, HTML5 - attributes, styles etc.
//
/**
 * Class Html_Helper
 * @package WCP
 *
 * @since   1.0.0
 */
class Html_Helper {
	/**
	 * Html output handling constants
	 */

	/**
	 * Echo the html and return null
	 */
	const ECHO_ONLY = 1;
	/**
	 * Just pass it back to calling function
	 */
	const RETURN_ONLY = 2;
	/**
	 * Echo it and pass it back
	 */
	const ECHO_AND_RETURN = 3;
	/**
	 * Add it to an internal string for eventual returning
	 *
	 * Useful to avoid a whole series of $html .= $html_helper-> calls
	 */
	const BUILD_HTML = 4;

	/**
	 * How select field and radio button options should be handled
	 */

	/**
	 * For an array ['Option 1', 'Option 2', ...]
	 * Text becomes 'Option 1' : value is 0, 'Option 2' : value is 1, etc.
	 */
	const VALUE_IS_ZERO_BASED = 0;
	/**
	 * For an array ['Option 1', 'Option 2', ...]
	 * Text becomes 'Option 1' : value is 1, 'Option 2' : value is 2, etc.
	 */
	const VALUE_IS_ONE_BASED = 1;
	/**
	 * For an array ['Option 1', 'Option 2', ...]
	 * Text becomes 'Option 1' : value is 'Option 1', 'Option 2' : value is 'Option 2', etc.
	 */
	const VALUE_EQUALS_LABEL = 2;
	/**
	 * For an array ['key 1' = 'value 1', 'key 2' => 'value 2', ...]
	 * Text becomes 'value 1' : value is 'key 1', 'value 2' : value is 'key 2', etc.
	 */
	const VALUE_TO_LABEL = 3;
	/**
	 * For an array ['key 1' = 'value 1', 'key 2' => 'value 2', ...]
	 * Text becomes 'key 1' : value is 'value 1', 'key 2' : value is 'value 2', etc.
	 */
	const LABEL_TO_VALUE = 4;

	/**
	 * String array of any attributes that don't require the attribute="value" syntax, just the word itself
	 * Would prefer simple array but only supported PHP 5.6+
	 */
	const SINGLE_WORD_ATTRIBUTES = ':disabled:checked:selected:autofocus:readonly:multiple:';
	/**
	 * @var bool indicates if a routine is being called to build html for another routine
	 */
	protected $internal = false;
	/**
	 * @var int one of the first four constants
	 *
	 */
	private $handling;
	/**
	 * @var array used by push and pop calls. Allows for different handling in different parts of the program
	 */
	private $handlingStack = [];
	/**
	 * @var string Built html string when handling is BUILD_HTML
	 */
	private $html;
	/**
	 * @var array Stack used for holding partially built html when handling is pushed or popped
	 */
	private $htmlStack = [];
	/**
	 * @var array Array that allows class name expansion from an abbreviation. This can be global or tag specific.
	 */
	private $classShortcuts = [];

	/**
	 * @var array  Array of int counts for how many divs we are into the html
	 */
	private $divCountStack = [ 0 ];
	/**
	 * @var bool Allows for the stacks to be inspected if problems occur. If true stacks also hold pushing function
	 *      information
	 */
	private $debug = false;
	/**
	 * Indicates how far up the debug stack the calling function will be
	 * 0 will be class calling function
	 * so calling function is 1 (modified for any call_by_name invocations)
	 *
	 * @var int
	 *
	 */
	private $backTraceLevel = 1;

	/**
	 * Html_Helper constructor.
	 *
	 * @param int $newHandling
	 * @param array $classShortcuts
	 *
	 * @since   1.0.0
	 */
	public function __construct( $newHandling = self::ECHO_ONLY, array $classShortcuts = [] ) {
		if ( ! $this->set_handling( $newHandling ) ) {
			$this->set_handling( self::ECHO_ONLY );
		}
		$this->classShortcuts = $classShortcuts;
	}

	/**
	 * Outputs a series of &nbsp; + ' ' + &nbsp; ... enforcing finishing on &nbsp;.
	 *
	 * @param  integer $nbr Optional. Number of 'non breaking' spaces to create;
	 *
	 * @return null|string
	 *
	 * @since  1.0.7
	 */
	static public function spaces( $nbr = 1 ) {
		$nbsp = '&#160;'; // Use entity number rather than entity name for better support
		$html = $nbsp;

		for ( $i = 1; $i < $nbr; $i ++ ) {
			if ( ( ( $i + 1 ) === $nbr ) || ( ( $i % 2 ) === 0 ) ) {
				$html .= $nbsp;
			} else {
				$html .= ' ';
			}
		}

		return $html;
	}

	/**
	 * Sets how html string should be handled at output stage
	 *
	 * @param int $newHandling
	 *
	 * @return bool
	 *
	 * @since   1.0.0
	 */
	public function set_handling( $newHandling ) {
		if ( ( $newHandling < self::ECHO_ONLY ) || ( $newHandling > self::BUILD_HTML ) ) {
			return false;
		}
		$this->handling = $newHandling;

		return true;
	}

	/**
	 * Set the class shortcuts for a particular tag. Replaces any existing shortcuts for the tag
	 * e.g set_tag_class_shortcuts( 'div', ['r' => 'row', '1/2' => 'one-half', ...] )
	 *
	 * @param string $tag
	 * @param array $shortcuts
	 *
	 * @since 1.0.0
	 */
	public function set_tag_class_shortcuts( $tag, $shortcuts ) {
		$this->classShortcuts[ $tag ] = $shortcuts;
	}

	/**
	 * Set a specified class shortcut a tag
	 * e.g set_tag_class_shortcut( 'div', 'col3', 'columns-3' )
	 *
	 * @param string $tag
	 * @param string $abbreviation
	 * @param string $class
	 *
	 * @since 1.0.0
	 */
	public function set_tag_class_shortcut( $tag, $abbreviation, $class ) {

		$this->classShortcuts[ $tag ][ $abbreviation ] = $class;
	}

	/**
	 * Gets all set shortcuts for helper
	 *
	 * @return array
	 *
	 * @since 1.0.7
	 */
	public function getClassShortcuts() {
		return $this->classShortcuts;
	}

	/**
	 * Returns the built html string, optionally saving it for further use (maybe logging?)
	 *
	 * @param bool $preserve
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_html( $preserve = false ) {
		$html = $this->html;
		if ( ! $preserve ) {
			$this->html = '';
		}

		return $html;
	}

	/**
	 * Set the current html string with a value. Possibly useful before building additional html
	 *
	 * @param string $html
	 *
	 * @return Html_Helper
	 *
	 * @since 1.0.0
	 */
	public function set_html( $html ) {
		$this->html = $html;

		return $this;
	}

	/**
	 * Prepend the current html string with a value.
	 *
	 * @param string $html
	 *
	 * @return Html_Helper
	 *
	 * @since 1.0.0
	 */
	public function prefix_html( $html ) {
		$this->html = $html . $this->html;

		return $this;
	}

	/**
	 * Append additional html to the current built html string.
	 *
	 * @param string $html
	 *
	 * @return Html_Helper
	 *
	 * @since 1.0.0
	 */
	public function suffix_html( $html ) {
		$this->html .= $html;

		return $this;
	}


	/**
	 * Sets how html string should be handled at output stage, preserving last handling for a pop
	 *
	 * @param int $newHandling
	 *
	 * @return bool
	 *
	 * @since   1.0.0
	 */
	public function push_handling( $newHandling ) {
		$currentHandling = $this->handling;
		if ( $this->set_handling( $newHandling ) ) {
			if ( $this->debug ) {
				$caller                = $this->back_trace();
				$this->handlingStack[] = [
					'handling' => $currentHandling,
					'caller'   => $caller,
				];
				$this->htmlStack[]     = [
					'html'   => $this->html,
					'caller' => $caller,
				];
			} else {
				$this->handlingStack[] = $currentHandling;
				$this->htmlStack[]     = $this->html;
			}

			$this->html            = '';
			$this->divCountStack[] = 0;

			return true;
		}

		return false;
	}

	/**
	 * Resets how html string should be handled
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function pop_handling() {
		if ( count( $this->handlingStack ) ) {
			$this->set_handling( $this->array_pop( $this->handlingStack, 'handling' ) );
			$this->html = $this->array_pop( $this->htmlStack, 'html' );

			array_pop( $this->divCountStack );

			return true;
		}

		return false;
	}

	/* Following three allow for immediate override of current handling by allowing calling by name.
	 * The _name indicates handling to use when calling that function. Probably only ever use _r to get hold
	 * of html when embedding in another call. But all three are there for completeness.
	 * First argument is name of method, any following arguments are passed to the method
	 *
	 * e.g
	 * $buttonHtml = $htmlHelper->_r( 'btn', 'my-button', 'Press This' );
	 *
	 * is the same as
	 *
	 * $buttonHtml = $htmlHelper->btn( 'my-button', 'Press This' ); (with the handling set to self::RETURN_ONLY)
	 *
	 * I've found this useful when creating tables with input fields. ie:
	 *
	 * $htmlHelper->td($htmlHelper->_r('numeric', ....));
	 *
	 * Although if doing this a large number of times, a secondary helper is probably more efficient and tidier
	 */
	/**
	 * Calls named function and echoes html
	 *
	 * @return null
	 *
	 * @since 1.0.0
	 */
	public function _e() {
		return $this->call_by_name( self::ECHO_ONLY, func_get_args() );
	}

	/**
	 * Calls named function echoes and returns html
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function _er() {
		return $this->call_by_name( self::ECHO_AND_RETURN, func_get_args() );
	}

	/**
	 * Calls named function and returns html
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function _r() {
		return $this->call_by_name( self::RETURN_ONLY, func_get_args() );
	}

	/* Basic Formatting section */

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Builds and then outputs a complete html <tag attributes>data</tag>
	 *
	 * eg tag('span', 'This is a span') becomes <span>This is a span</span>
	 *  tag('p', 'paragraph', 'my-css', 'my-id') becomes <p id='my-id' class='my-css'>paragraph</p>
	 *
	 * @param string       $tag
	 * @param string       $data
	 * @param string|array $classes
	 * @param string $id
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function tag( $tag, $data, $classes = '', $id = '', array $attributes = [], array $styles = [] ) {
		$internal       = $this->internal;
		$this->internal = true;
		$html           = $this->o_tag( $tag, $classes, $id, $attributes, $styles );
		if ( null !== $data ) {
			$html .= $data;
		}
		$html .= $this->c_tag( $tag );

		return $this->output( $html, $internal );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Constructs an opening tag.
	 *
	 * eg o_tag('span') becomes <span>
	 *
	 * selfClosing can be set to true for void tags:
	 *
	 * <area />
	 * <base />
	 * <br />
	 * <col />
	 * <command />
	 * <embed />
	 * <hr />
	 * <img />
	 * <input />
	 * <keygen />
	 * <link />
	 * <meta />
	 * <param />
	 * <source />
	 * <track />
	 * <wbr />
	 *
	 * (Technically an error in HTML4, but accepted, ignored in HTML5 but REQUIRED for XHTML!)
	 * Internally, this class generates input and img to self-close to be on the safe side.
	 *
	 * @param string $tag
	 * @param string|array $classes
	 * @param string $id
	 * @param array $attributes
	 * @param array $styles
	 * @param bool $selfClosing
	 *
	 * @return null|string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function o_tag(
		$tag, $classes = '', $id = '', array $attributes = [], array $styles = [],
		$selfClosing = false
	) {

		if ( 'div' === strtolower( $tag ) ) {
			$count = count( $this->divCountStack );
			$this->divCountStack[ $count - 1 ] ++;
		}
		$html = '<' . $tag;
		$html .= $this->get_id( $id );
		$html .= $this->get_classes( $tag, $classes );
		$html .= $this->get_attributes( $attributes );
		$html .= $this->get_styles( $styles );
		if ( $selfClosing ) {
			$html .= ' /';
		}
		$html .= '>';

		return $this->output( $html );
	}

	/**
	 * Closes one or more tags separated by <space>.
	 *
	 * eg c_tag('span p div') produces </span></p></div>
	 *
	 * @param string $tag
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function c_tag( $tag ) {
		$html = '';
		$tags = explode( ' ', $tag );

		foreach ( $tags as $aTag ) {
			if ( 'div' === strtolower( $aTag ) ) {
				$count = count( $this->divCountStack );
				$this->divCountStack[ $count - 1 ] --;
			}
			$html .= '</' . $aTag . '>';
		}

		return $this->output( $html );
	}

	/**
	 * Open one or more divs, with one or more classes. Single div only if you want id or other attributes
	 *
	 * @param array|string $classes Special handling of array. Multiple divs instead of multiple classes. Use array of
	 *                              arrays if you want to specify multiple classes using array (string is much easier)
	 * @param string       $id
	 * @param array        $attributes
	 * @param array        $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function o_div( $classes = '', $id = '', array $attributes = [], array $styles = [] ) {
		if ( is_array( $classes ) ) {
			$internal       = $this->internal;
			$this->internal = true;
			$html           = '';
			/** @noinspection ForeachSourceInspection */
			foreach ( $classes as $divClasses ) {
				$html .= $this->o_tag( 'div', $divClasses );
			}

			return $this->output( $html, $internal );
		} else {
			return $this->o_tag( 'div', $classes, $id, $attributes, $styles );
		}
	}

	/**
	 * Closes 1 or more divs - specified by $n. Additional arguments are then used to open new divs using the above
	 *
	 * @param int               $n 0 means clear down all divs, -ve numbers mean leave $n divs open
	 * @param string|array|null $classes
	 * @param string|array      $id
	 * @param array             $attributes
	 * @param array             $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function c_div( $n = 1, $classes = null, $id = '', array $attributes = [], array $styles = [] ) {
		$this->internal = true;
		$html           = '';

		if ( $n > 0 ) {
			for ( $i = 0; $i < $n; $i ++ ) {
				$html .= $this->c_tag( 'div' );

			}
		} else {
			$divsToClose = end( $this->divCountStack ) + $n;
			for ( $i = 0; $i < $divsToClose; $i ++ ) {
				$html .= $this->c_tag( 'div' );
			}
		}

		if ( null !== $classes ) {
			$html .= $this->o_div( $classes, $id, $attributes, $styles );
		}

		return $this->output( $html, false );
	}

	/**
	 * Outputs one or more <br/> tags
	 *
	 * @param int $n
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function br( $n = 1 ) {
		return $this->output( str_repeat( '<br/>', $n ) );
	}

	/**
	 * Outputs a series of &nbsp; + ' ' + &nbsp; ... enforcing finishing on &nbsp;.
	 *
	 * @param  integer $nbr Optional. Number of 'non breaking' spaces to create;
	 *
	 * @return null|string
	 *
	 * @since  1.0.7
	 */
	public function nbsp( $nbr = 1 ) {
		return $this->output( self::spaces( $nbr ) );
	}
	/* Table section */

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Outputs a row of table data
	 *
	 * eg <tr><th>Head 1</th><th>Head 2</th></tr> or
	 *    <tr><td>20</td><td>55.2</td></tr> or even
	 *    <tr><th>Totals</th><td>12355.2</td></tr>
	 *
	 * @param array $data
	 * @param string|array $cellType
	 * @param string|array $align
	 * @param string|array $classes
	 * @param string $id
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function tr(
		$data, $cellType = 'd', $align = '', $classes = '', $id = '', array $attributes = [],
		array $styles = []
	) {

		$this->internal = true;
		// TODO allow for classes, id, to be arrays or array of arrays['tr', 'td1','td2',..]
		// TODO allow for attributes, styles, to be array of arrays ['tr', 'td1','td2',..]
		$html = $this->o_tag( 'tr', $classes, $id, $attributes, $styles );

		$cellData        = [];
		$defaultCellType = is_string( $cellType ) ? $cellType : 'd';
		$defaultAlign    = is_string( $align ) ? $align : '';
		foreach ( $data as $cell ) {
			$cellData[] = [ 'd' => $cell, 't' => $defaultCellType, 'a' => $defaultAlign ];
		}

		self::set_array_attribute( $cellData, 't', $cellType );
		self::set_array_attribute( $cellData, 'a', $align );

		foreach ( $cellData as $cell ) {
			if ( 'h' === $cell['t'] ) {
				$html .= $this->th( $cell['d'], $cell['a'] );
			} else {
				$html .= $this->td( $cell['d'], $cell['a'] );
			}
		}

		$html .= $this->c_tag( 'tr' );

		return $this->output( $html, false );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Outputs a table heading cell
	 *
	 * @param string $heading
	 * @param string $align
	 * @param string|array $classes
	 * @param string $id
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function th( $heading, $align = '', $classes = '', $id = '', array $attributes = [], array $styles = [] ) {
		$internal       = $this->internal;
		$this->internal = true;
		$this->check_align( $align, $attributes );
		$html = $this->tag( 'th', $heading, $classes, $id, $attributes, $styles );

		return $this->output( $html, $internal );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Outputs a table data cell
	 *
	 * @param string $text
	 * @param string $align
	 * @param string|array $classes
	 * @param string $id
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function td( $text, $align = '', $classes = '', $id = '', array $attributes = [], array $styles = [] ) {
		$internal       = $this->internal;
		$this->internal = true;
		$this->check_align( $align, $attributes );
		$html = $this->tag( 'td', $text, $classes, $id, $attributes, $styles );

		return $this->output( $html, $internal );
	}

	/* User inputs section */

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
	 */
	public function o_form( $name, $method = 'post', $action = '', $target = '', array $attributes = [] ) {
		$attributes['name']   = $name;
		$attributes['method'] = $method;
		self::maybe_set( $attributes, 'action', $action, true ); // ignore blanks
		self::maybe_set( $attributes, 'target', $target, true );

		return $this->o_tag( 'form', '', '', $attributes );
	}



	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates a button input field
	 *
	 * @param string $caption
	 * @param string|array $classes
	 * @param string $name
	 * @param mixed $value
	 * @param bool $disabled
	 * @param string $type
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 * @internal param string $id
	 *
	 * @since    1.0.0
	 * @updated  1.0.1
	 */
	public function btn(
		$caption, $classes = '', $name = '', $value = null, $disabled = false, $type = 'button',
		array $attributes = [], array $styles = []
	) {

		self::maybe_set( $attributes, 'type', $type );
		self::maybe_set( $attributes, 'value', $value, true ); // don't if empty
		self::maybe_set( $attributes, 'name', $name, true );
		self::maybe_set( $attributes, 'disabled', $disabled );

		if ( '' === $classes ) {
			$classes = 'button';
		}

		return $this->tag( 'button', $caption, $classes, $name, $attributes, $styles );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Generates a link
	 *
	 * @param string $text
	 * @param string $href
	 * @param string|array $classes
	 * @param string $id
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.1
	 */
	public function a( $text, $href, $classes = '', $id = '', array $attributes = [], array $styles = [] ) {
		$attributes['href'] = $href;

		return $this->tag( 'a', $text, $classes, $id, $attributes, $styles );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates a number input field
	 *
	 * @param string $id
	 * @param string $label
	 * @param int $value
	 * @param string|array $classes
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function number( $id, $label, $value = 0, $classes = '', array $attributes = [], array $styles = [] ) {
		$this->internal = true;
		$html           = $this->input( 'number', $id, $label, $value, $classes, $attributes, $styles );

		return $this->output( $html, false );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates a generalised 'text' input field
	 *
	 * @param string $type
	 * @param string $id
	 * @param string $label
	 * @param int|string $value
	 * @param string|array $classes
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function input( $type, $id, $label, $value = 0, $classes = '', array $attributes = [], array $styles = [] ) {
		$internal       = $this->internal;
		$this->internal = true;

		$html = $this->lbl( $id, $label );
		$html .= $this->start_tag_input( $type, $value, $id, $id );
		$html .= $this->get_classes( [ $type, 'input' ], $classes, [ $type ] );
		$html .= $this->get_attributes( $attributes );
		$html .= $this->get_styles( $styles );
		$html .= '/>';

		return $this->output( $html, $internal );
	}

	/**
	 * Outputs a <label for="id">label</label> tag. If $label is empty, nothing is generated.
	 *
	 * @param string $id
	 * @param string $label
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function lbl( $id, $label ) {
		$internal       = $this->internal;
		$this->internal = true;
		$html           = $label ? $this->tag( 'label', $label, '', '', [ 'for' => $id ] ) : '';

		return $this->output( $html, $internal );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates a specific text input field
	 *
	 * @param string $id
	 * @param string $label
	 * @param string|null $value
	 * @param string|array $classes
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function text( $id, $label, $value = null, $classes = '', array $attributes = [], array $styles = [] ) {
		$this->internal = true;
		$html           = $this->input( 'text', $id, $label, $value, $classes, $attributes, $styles );

		return $this->output( $html, false );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates a text area input field
	 *
	 * @param string $id
	 * @param int $rows
	 * @param int $cols
	 * @param string $label
	 * @param string|null $value
	 * @param string|array $classes
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function text_area(
		$id, $rows, $cols, $label = null, $value = null, $classes = '', array $attributes = [],
		array $styles = []
	) {
		$this->internal = true;
		$html           = $this->lbl( $id, $label );

		self::maybe_set( $attributes, 'rows', $rows );
		self::maybe_set( $attributes, 'cols', $cols );
		$text = is_string( $value ) ? $value : '';

		$html .= $this->tag( 'textarea', $text, $classes, $id, $attributes, $styles );

		return $this->output( $html, false );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates a checkbox input
	 *
	 * @param string $id
	 * @param string|null $label
	 * @param bool $checked
	 * @param string $value
	 * @param string|array $classes
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function cbx(
		$id, $label = null, $checked = true, $value = '', $classes = '', array $attributes = [],
		array $styles = []
	) {
		$this->internal = true;

		$type = 'checkbox';
		$html = $this->lbl( $id, $label );
		$html .= $this->start_tag_input( $type, $value, $id, $id );
		$html .= $this->get_classes( [ $type, 'input' ], $classes, [ $type ] );
		$attributes['checked'] = $checked;
		$html .= $this->get_attributes( $attributes );
		$html .= $this->get_styles( $styles );
		$html .= '/>';

		return $this->output( $html, false );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates one or more radio buttons with the same name attribute. The button/values can be specified by
	 * the $valueHandling parameter. See:  'How select field and radio button options should be handled' in the
	 * constants section
	 *
	 * @param string $name
	 * @param array $options
	 * @param mixed $value
	 * @param string $separator
	 * @param int $valueHandling
	 * @param string|array $classes
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function rb(
		$name, $options, $value = null, $separator = '<br>', $valueHandling = self::VALUE_EQUALS_LABEL,
		$classes = '', array $attributes = [], array $styles = []
	) {
		$this->internal = true;
		$type           = 'radio';
		$html           = '';

		$setUpOptions = $this->set_options( $options, $valueHandling );

		foreach ( $setUpOptions as $option => $label ) {
			if ( self::VALUE_IS_ONE_BASED === $valueHandling ) {
				$option ++;
			}
			$html .= $this->start_tag_input( $type, $option, null, $name );
			$html .= $this->get_classes( $type, $classes, [ $type ] );
			$attributes['checked'] = $option === $value;
			$html .= $this->get_attributes( $attributes );
			$html .= $this->get_styles( $styles );
			$html .= '/>';
			$html .= $label . $separator;
		}

		return $this->output( $html, false );
	}

	/**
	 * Routine to create/modify select and radio button option arrays
	 *
	 * @param array $options
	 * @param int $valueHandling
	 *
	 * @return array
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function set_options( $options, $valueHandling ) {
		$result = $options;

		// self::VALUE_TO_LABEL is untouched as that is what we use to allow for easy numeric values
		// self::VALUE_IS_ZERO_BASED and self::VALUE_IS_ONE_BASED don't need any special handling at this stage

		switch ( $valueHandling ) {
			case self::VALUE_EQUALS_LABEL:
				// Non keyed array supplied but want value to be same as text
				$result = array_combine( $options, $options );
				break;
			// Keyed array supplied but the opposite way round to how it's used within helper
			case self::LABEL_TO_VALUE:
				$result = array_flip( $options );
				break;
		}

		return $result;
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates a selection drop down.
	 *
	 * The option text/values can be specified by the $valueHandling parameter.
	 * See:  'How select field and radio button options should be handled' in the constants section
	 *
	 * @param string $id
	 * @param array $options
	 * @param string|null $label
	 * @param mixed $value
	 * @param int $valueHandling
	 * @param string|array $classes
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	public function sel(
		$id, $options, $label, $value = null, $valueHandling = self::VALUE_EQUALS_LABEL, $classes = '',
		array $attributes = [], array $styles = []
	) {
		$this->internal     = true;
		$html               = $this->lbl( $id, $label );
		if ( $id ) {
			$attributes['name'] = $id;
		}
		$html .= $this->o_tag( 'select', $classes, $id, $attributes, $styles );

		$setUpOptions = $this->set_options( $options, $valueHandling );

		$attributes = [];
		foreach ( $setUpOptions as $option => $optionLabel ) {
			if ( self::VALUE_IS_ONE_BASED === $valueHandling ) {
				$option ++;
			}
			$attributes['value']    = $option;
			$attributes['selected'] = $option === $value;
			$html .= $this->tag( 'option', $optionLabel, '', '', $attributes );
		}

		$html .= $this->c_tag( 'select' );

		return $this->output( $html, false );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Outputs an <img  /> tag
	 *
	 * @param        $src
	 * @param null $width
	 * @param null $height
	 * @param string $alt
	 * @param string $classes
	 * @param string $id
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 */
	public function img(
		$src, $width = null, $height = null, $alt = '', $classes = '', $id = '',
		array $attributes = [],
		array $styles = []
	) {
		self::maybe_set( $attributes, 'src', $src );
		self::maybe_set( $attributes, 'width', $width );
		self::maybe_set( $attributes, 'height', $height );
		self::maybe_set( $attributes, 'alt', $alt );

		return $this->o_tag( 'img', $classes, $id, $attributes, $styles, true );
	}
	/* Svg section */

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates the opening <svg> tag with some standardised settings
	 *
	 * @param int $viewboxWidth
	 * @param int $viewboxHeight
	 * @param string $classes
	 * @param string $id
	 * @param array $attributes
	 * @param array $styles
	 * @param string $viewbox
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function o_svg(
		$viewboxWidth, $viewboxHeight, $classes = '', $id = '', array $attributes = [],
		array $styles = [],
		$viewbox = ''
	) {
		self::maybe_set( $attributes, 'xmlns', 'http://www.w3.org/2000/svg' );
		self::maybe_set( $attributes, 'xmlns:xlink', 'http://www.w3.org/1999/xlink' );
		self::maybe_set( $attributes, 'version', '1.1' );
		self::maybe_set( $attributes, 'height', '100%' );
		self::maybe_set( $attributes, 'width', '100%' );
		if ( $viewbox ) {
			$attributes['viewBox'] = $viewbox;
		} else {
			$viewbox = '0 0 ' . $viewboxWidth . ' ' . $viewboxHeight;
			self::maybe_set( $attributes, 'viewBox', $viewbox );
		}

		return $this->o_tag( 'svg', $classes, $id, $attributes, $styles );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates svg <tspan>text</tspan> tags
	 *
	 * @param string $text
	 * @param array|int|null $x int is an absolute int, [int] is a delta
	 * @param array|int|null $y ditto
	 * @param string $classes
	 * @param string $id
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function tspan(
		$text, $x = null, $y = null, $classes = '', $id = '', array $attributes = [],
		array $styles = []
	) {
		if ( null !== $x ) {
			if ( is_array( $x ) ) {
				self::maybe_set( $attributes, 'dx', $x[0] );
			} else {
				self::maybe_set( $attributes, 'x', $x );
			}
		}
		if ( null !== $y ) {
			if ( is_array( $y ) ) {
				self::maybe_set( $attributes, 'dy', $y[0] );
			} else {
				self::maybe_set( $attributes, 'y', $y );
			}
		}

		return $this->tag( 'tspan', $text, $classes, $id, $attributes, $styles );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates SVG <rect ... /> tag
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @param array $styles
	 * @param string $classes
	 * @param string $id
	 * @param array $attributes
	 * @param null $rx
	 * @param null $ry
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function rect(
		$x, $y, $width, $height, array $styles = [], $classes = '', $id = '', array $attributes = [],
		$rx = null,
		$ry = null
	) {
		self::maybe_set( $attributes, 'x', $x );
		self::maybe_set( $attributes, 'y', $y );
		self::maybe_set( $attributes, 'width', $width );
		self::maybe_set( $attributes, 'height', $height );
		self::maybe_set( $attributes, 'rx', $rx, true ); // ignore nulls
		self::maybe_set( $attributes, 'ry', $ry, true );

		return $this->o_tag( 'rect', $classes, $id, $attributes, $styles, true );
	}

	/** @noinspection MoreThanThreeArgumentsInspection */
	/**
	 * Creates SVG <image ... /> tag with some standard settings
	 *
	 * @param string $href
	 * @param int $width
	 * @param int $height
	 * @param string $classes
	 * @param string $id
	 * @param array $attributes
	 * @param array $styles
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function svg_img(
		$href, $width, $height, $classes = '', $id = '', array $attributes = [],
		array $styles = []
	) {
		self::maybe_set( $attributes, 'xlink:href', $href );
		self::maybe_set( $attributes, 'style', 'image-rendering:optimizeQuality' );
		self::maybe_set( $attributes, 'preserveAspectRatio', 'xMidYMid slice' );
		self::maybe_set( $attributes, 'width', $width );
		self::maybe_set( $attributes, 'height', $height );

		return $this->o_tag( 'image', $classes, $id, $attributes, $styles, true );
	}


	/* Attribute section */

	/**
	 * Returns checked attribute based on passed value
	 *
	 * @param bool $checked
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function checked( $checked ) {
		return $checked ? ' checked' : '';
	}

	/**
	 * Returns disabled attribute based on passed value
	 *
	 * @param bool $disabled
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function disabled( $disabled ) {
		return $disabled ? ' disabled' : '';
	}

	/**
	 * Returns selected attribute based on passed value
	 *
	 * @param bool $selected
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function selected( $selected ) {
		return $selected ? ' selected' : '';
	}

	/**
	 * Returns autofocus attribute based on passed value
	 *
	 * @param bool $autofocus
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function autofocus( $autofocus ) {
		return $autofocus ? ' autofocus' : '';
	}

	/**
	 * Returns readonly attribute based on passed value
	 *
	 * @param bool $readonly
	 *
	 * @return string
	 *
	 * @since 1.0.8
	 */
	public function readonly( $readonly ) {
		return $readonly ? ' readonly' : '';
	}

	/**
	 * extract_tags()
	 * Extract specific HTML tags and their attributes from a string.
	 *
	 * You can either specify one tag, an array of tag names, or a regular expression that matches the tag name(s).
	 * If multiple tags are specified you must also set the $selfclosing parameter and it must be the same for
	 * all specified tags (so you can't extract both normal and self-closing tags in one go).
	 *
	 * The function returns a numerically indexed array of extracted tags. Each entry is an associative array
	 * with these keys :
	 *  tag_name    - the name of the extracted tag, e.g. "a" or "img".
	 *  offset      - the numberic offset of the first character of the tag within the HTML source.
	 *  contents    - the inner HTML of the tag. This is always empty for self-closing tags.
	 *  attributes  - a name -> value array of the tag's attributes, or an empty array if the tag has none.
	 *  full_tag    - the entire matched tag, e.g. '<a href="http://example.com">example.com</a>'. This key
	 *                will only be present if you set $return_the_entire_tag to true.
	 *
	 * @param string $html The HTML code to search for tags.
	 * @param string|array $tag The tag(s) to extract.
	 * @param bool $selfclosing Whether the tag is self-closing or not. Setting it to null will force
	 *                                            the script to try and make an educated guess.
	 * @param bool $return_the_entire_tag Return the entire matched tag in 'full_tag' key of the results array.
	 * @param string $charset The character set of the HTML code. Defaults to ISO-8859-1.
	 *
	 * @return array An array of extracted tags, or an empty array if no matching tags were found.
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function extract_tags(
		$html, $tag, $selfclosing = null, $return_the_entire_tag = false, $charset = 'ISO-8859-1'
	) {

		if ( is_array( $tag ) ) {
			$tag = implode( '|', $tag );
		}

		// If the user didn't specify if $tag is a self-closing tag we try to auto-detect it
		// by checking against a list of known self-closing tags.
		$selfclosing_tags = array(
			'area',
			'base',
			'basefont',
			'br',
			'hr',
			'input',
			'img',
			'link',
			'meta',
			'col',
			'param',
		);
		if ( null === $selfclosing ) {
			$selfclosing = in_array( $tag, $selfclosing_tags, true );
		}

		//The regexp is different for normal and self-closing tags because I can't figure out
		//how to make a sufficiently robust unified one.
		if ( $selfclosing ) {
			$tag_pattern =
				'@<(?P<tag>' . $tag . ')           # <tag
            (?P<attributes>\s[^>]+)?       # attributes, if any
            \s*/?>                   # /> or just >, being lenient here 
            @xsi';
		} else {
			$tag_pattern =
				'@<(?P<tag>' . $tag . ')           # <tag
            (?P<attributes>\s[^>]+)?       # attributes, if any
            \s*>                 # >
            (?P<contents>.*?)         # tag contents
            </(?P=tag)>               # the closing </tag>
            @xsi';
		}

		$attribute_pattern =
			'@
        (?P<name>\w+)                         # attribute name
        \s*=\s*
        (
            (?P<quote>[\"\'])(?P<value_quoted>.*?)(?P=quote)    # a quoted value
            |                           # or
            (?P<value_unquoted>[^\s"\']+?)(?:\s+|$)           # an unquoted value (terminated by whitespace or EOF) 
        )
        @xsi';

		//Find all tags
		if ( ! preg_match_all( $tag_pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
			//Return an empty array if we didn't find anything
			return array();
		}

		$tags = array();
		/** @noinspection ForeachSourceInspection */
		foreach ( $matches as $match ) {

			//Parse tag attributes, if any
			$attributes = array();
			if ( ( ! empty( $match['attributes'][0] ) ) && preg_match_all( $attribute_pattern, $match['attributes'][0],
					$attribute_data, PREG_SET_ORDER )
			) {
				//Turn the attribute data into a name->value array
				/** @noinspection ForeachSourceInspection */
				foreach ( $attribute_data as $attr ) {
					$value = '';
					if ( ! empty( $attr['value_quoted'] ) ) {
						$value = $attr['value_quoted'];
					} else if ( ! empty( $attr['value_unquoted'] ) ) {
						$value = $attr['value_unquoted'];
					}

					//Passing the value through html_entity_decode is handy when you want
					//to extract link URLs or something like that. You might want to remove
					//or modify this call if it doesn't fit your situation.
					$value = html_entity_decode( $value, ENT_QUOTES, $charset );

					$attributes[ $attr['name'] ] = $value;
				}
			}
			$tag = array(
				'tag_name'   => $match['tag'][0],
				'offset'     => $match[0][1],
				'contents'   => ! empty( $match['contents'] ) ? $match['contents'][0] : '',
				//empty for self-closing tags
				'attributes' => $attributes,
			);
			if ( $return_the_entire_tag ) {
				$tag['full_tag'] = $match[0][0];
			}

			$tags[] = $tag;
		}

		return $tags;
	}

	/**
	 * Determines what to do with a created html string.
	 *
	 * If the routine is being called by another routine then $this->internal will be set to true and the $html returned
	 * When the calling routine is finished, it then passes $internal = false to this routine to clear the flag.
	 *
	 * When $this->internal is false, the $html is handled according to the $this->handling setting
	 *
	 * @param string $html
	 * @param null|bool $internal
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	protected function output( $html, $internal = null ) {
		if ( null !== $internal ) {
			$this->internal = $internal;
		}

		$result = $html;

		if ( ! $this->internal ) {
			switch ( $this->handling ) {
				case self::ECHO_AND_RETURN:
					echo $html . "\r\n";
					break;
				case self::ECHO_ONLY:
					echo $html . "\r\n";
					$result = null;
					break;
				case self::BUILD_HTML:
					$this->html .= $html;
					break;
				default: //RETURN_ONLY
			}
		}

		return $result;
	}

	/**
	 * If set, creates an id attribute
	 *
	 * @param string|null $id
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	protected function get_id( $id ) {
		return ! empty( $id ) ? ' id="' . $id . '"' : '';
	}

	/**
	 * Creates a single attribute="value" or attribute='value' string.
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
	 * @param mixed $value
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	protected function set_attribute( $attribute, $value ) {
		$quote = '"';

		if ( is_array( $value ) ) {
			$quote = '\'';
			$value = $value[0];
		}

		return ' ' . $attribute . '=' . $quote . $value . $quote;
	}

	/**
	 * Creates a series of attribute="value" html statements based on content of array
	 *
	 * This has special handling for attributes 'selected', 'checked' and 'disabled'. The value is treated as a boolean
	 * and the attribute is just shown if value is true. See const SINGLE_WORD_ATTRIBUTES
	 *
	 * Additional special handling is used for an attribute with the key of 'data' and an array value: e.g.
	 * [ ..., 'data' => [ 'x' => 34, 'y' => 35, 'wibble' => 'A cunning plan' ] ,...] will be expanded as
	 * data-x="34" data-y="35" data-wibble="A cunning plan"
	 *
	 * Likewise if the key is 'style' or 'styles' and the value is an array, the value array is treated as a styles
	 * array
	 *
	 * Furthermore, if the value of an attribute has to use single quotes eg if it contains double-quotes like a JSON
	 * value the value should be made into an array. e.g.
	 * [ alt => ['A "nice" picture'], 'data' => [ 'JSON' => [json_string], 'wibble' => ['A "cunning" plan'] ] ,...]
	 * will be expanded as
	 * alt='A "nice" picture' data-JSON='json_string' data-wibble='A "cunning" plan'
	 *
	 * @param array $attributes
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 * @updated 1.0.4
	 */
	protected function get_attributes( array $attributes ) {

		$result = '';

		foreach ( $attributes as $property => $value ) {
			if ( false !== stripos( self::SINGLE_WORD_ATTRIBUTES, ':' . $property . ':' ) ) {
				$result .= $value ? " $property" : '';
			} elseif ( 'data' === $property && is_array( $value ) ) {
				/** @noinspection ForeachSourceInspection */
				foreach ( $value as $dataProperty => $data ) {
					$result .= $this->set_attribute( 'data-' . $dataProperty, $data );
				}
			} elseif /** @noinspection NotOptimalIfConditionsInspection */
			( ( 'style' === $property || 'styles' === $property ) && is_array( $value )
			) {
				$result .= $this->get_styles( $value );
			} else {
				$result .= $this->set_attribute( $property, $value );
			}
		}

		return $result;
	}

	/**
	 * Creates a 'style="color:blue;text-align:center" html attribute based on content of array
	 *
	 * @param array $styles
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	protected function get_styles( $styles ) {
		$result = '';

		if ( $styles ) {
			$result = ' style="';
			foreach ( $styles as $style => $value ) {
				$result .= $style . ':' . $value . ';';
			}
			$result .= '"';
		}

		return $result;
	}

	/**
	 * Creates the opening part of an <input .... /> tag
	 *
	 * @param string $type
	 * @param string|null $value
	 * @param string|null $id
	 * @param string|null $name
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	protected function start_tag_input( $type, $value, $id, $name = null ) {
		$html = '<input type="' . $type . '"';
		$html .= $this->get_id( $id );
		$html .= $this->get_name( $name );
		if ( $value ) {
			$html .= ' value="' . $value . '"';
		}

		return $html;
	}

	/**
	 * If set, creates a name attribute
	 *
	 * @param string|null $name
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	protected function get_name( $name ) {

		return ! empty( $name ) ? ' name="' . $name . '"' : '';
	}

	/**
	 * Used to split up multiple values into an array for the specified attribute. See tr for usage.
	 *
	 * @param array &$theData - returned
	 * @param string $attribute
	 * @param array $values
	 *
	 * @since 1.0.0
	 */
	private static function set_array_attribute( &$theData, $attribute, $values ) {
		if ( ! is_array( $values ) ) {
			return;
		}

		foreach ( $values as $idx => $value ) {
			$theData[ $idx ][ $attribute ] = $value;
		}
	}

	/**
	 * Set's a value for $array[$field] if the array does not already have a value
	 * (even if it's false or null... unless $ignoreEmpty is true)
	 *
	 * @param array  &$array
	 * @param string $field
	 * @param mixed  $value
	 * @param bool   $ignoreEmpty
	 *
	 * @since 1.1.0
	 */
	private static function maybe_set( &$array, $field, $value, $ignoreEmpty = false ) {
		if ( $ignoreEmpty && empty( $value ) ) {
			return;
		}
		if ( ! isset( $array[ $field ] ) ) {
			$array[ $field ] = $value;
		}
	}

	/**
	 * Used by _e() _r() and _er() to handle arguments and make sure named function exists
	 *
	 * @param int $handling
	 * @param array $args
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	private function call_by_name( $handling, $args ) {
		$result = '';

		$this->backTraceLevel += 2; // Additional 2 for _e, _r, _er invocation and this routine
		$this->push_handling( $handling );

		$function = array_shift( $args );
		if ( is_callable( [ $this, $function ] ) ) {
			$result = call_user_func_array( [ $this, $function ], $args );
		}

		$this->pop_handling();
		$this->backTraceLevel -= 2;

		return $result;
	}

	/**
	 * Gets debugging info when required
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private function back_trace() {
		$debugInfo = debug_backtrace();
		$caller    = $debugInfo[ $this->backTraceLevel ];

		return 'Line: ' . $caller['line'] . ' File: ' . $caller['file'];
	}

	/**
	 * Return either the value from a stack, or the value specified by the field if debugging
	 *
	 * @param array &$array
	 * @param string $field
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	private function array_pop( array &$array, $field ) {
		$pop = array_pop( $array );
		if ( $this->debug ) {
			$pop = $pop[ $field ];
		}

		return $pop;
	}

	/**
	 * Expansion of class shortcuts into their full form. Works from lowest tag level to global level
	 * allowing for same shortcut to mean different things
	 *
	 * @param string|array $tag
	 * @param string|array $classes
	 * @param array $extraClasses
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	private function get_classes( $tag, $classes, array $extraClasses = [] ) {
		$result = '';
		$tags   = [];

		if ( is_string( $tag ) ) {
			$tags[] = $tag;
		} elseif ( is_array( $tag ) ) {
			$tags = array_merge( $tags, $tag );
		}
		$tags[] = '*';

		$classArray = $extraClasses;
		if ( is_string( $classes ) ) {
			$classArray = array_merge( $classArray, explode( ' ', $classes ) );
		} elseif ( is_array( $classes ) ) {
			$classArray = array_merge( $classArray, $classes );
		}

		foreach ( $extraClasses as $class ) {
			if ( ! isset( $classArray[ $class ] ) ) {
				$classArray[] = $class;
			}
		}

		if ( count( $this->classShortcuts ) > 0 ) {
			foreach ( $classArray as $class ) {
				foreach ( $tags as $aTag ) {
					if ( isset( $this->classShortcuts[ $aTag ][ $class ] ) ) {
						$class = $this->classShortcuts[ $aTag ][ $class ];
						continue; // Stop looking at first tag expansion
					}
				}
				$result .= $class . ' ';
			}
		} else {
			foreach ( $classArray as $class ) {
				$result .= $class . ' ';
			}
		}

		$result = trim( $result );

		return $result ? $this->set_attribute( 'class',
			$result ) : ''; // Use set_attribute to escape everything is needed
	}

	/**
	 * Create an 'align' attribute allowing for abbreviations. If invalid, no align attribute is set up
	 *
	 * @param string $align
	 * @param array $attributes
	 *
	 * @since 1.0.0
	 */
	private function check_align( $align, &$attributes ) {
		if ( $align && ! isset( $attributes['align'] ) ) {
			switch ( strtolower( $align )[0] ) {
				case 'c':
					$pAlign = 'center';
					break;
				case 'r':
					$pAlign = 'right';
					break;
				case 'l':
					$pAlign = 'left';
					break;
				default:
					$pAlign = null;
			}
			if ( $pAlign ) {
				$attributes['align'] = $pAlign;
			}
		}
	}
}
