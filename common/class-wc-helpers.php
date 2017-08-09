<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 12/10/2016
 * Time: 9:29 AM
 */

namespace WCP;
if ( class_exists( 'Wc_Helper' ) ) {
	return;
}

/**
 * Class Wc_Helper
 *
 * @package    WCP
 * @subpackage Helpers
 *
 * @since      1.0.0
 * @updated    1.0.1
 */
class Wc_Helper {

	/**
	 * @var string
	 */
	public $field_prefix;

	/**
	 * Wc_Helper constructor.
	 *
	 * @param string $field_prefix
	 *
	 * @since    1.0.0
	 * @updated  1.0.1
	 */
	public function __construct( $field_prefix = '' ) {
		$this->field_prefix = $field_prefix;
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Outputs a text input box with caller specified type and datatype
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $label
	 * @param string|null $type
	 * @param string|null $placeholder
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $dataType
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param mixed|null  $value
	 *
	 * @since   1.0.0
	 */
	public function text_type_input(
		$field, $id = null, $label = null, $type = null, $placeholder = null,
		$description = null,
		$descTip = null, $dataType = null, $class = null, $style = null,
		$customAttributes = null,
		$wrapperClass = null, $name = null, $value = null
	) {

		$field = $this->set_all_fields( $field, $id, $value, $class, $name, $label, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, $placeholder, $type, $dataType );

		woocommerce_wp_text_input( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Outputs a generic text input box
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $label
	 * @param string|null $placeholder
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $dataType
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param mixed|null  $value
	 *
	 * @since   1.0.0
	 */
	public function text_input(
		$field, $id = null, $label = null, $placeholder = null, $description = null,
		$descTip = null,
		$dataType = null, $class = null, $style = null, $customAttributes = null,
		$wrapperClass = null,
		$name = null, $value = null
	) {

		$field = $this->set_all_fields( $field, $id, $value, $class, $name, $label, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, $placeholder, 'text', $dataType );

		woocommerce_wp_text_input( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Outputs a numeric specific text input box
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $label
	 * @param string|null $placeholder
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $dataType
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param mixed|null  $value
	 *
	 * @since   1.0.0
	 */
	public function number_input(
		$field, $id = null, $label = null, $placeholder = null, $description = null,
		$descTip = null, $dataType = null, $class = null, $style = null, $customAttributes = null,
		$wrapperClass = null, $name = null, $value = null
	) {

		$field = $this->set_all_fields( $field, $id, $value, $class, $name, $label, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, $placeholder, 'number', $dataType );

		woocommerce_wp_text_input( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Outputs a price specific text input box
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $label
	 * @param string|null $placeholder
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param mixed|null  $value
	 *
	 * @since   1.0.0
	 */
	public function price_input(
		$field, $id = null, $label = null, $placeholder = null, $description = null,
		$descTip = null, $class = null, $style = null, $customAttributes = null,
		$wrapperClass = null, $name = null, $value = null
	) {

		$field = $this->set_all_fields( $field, $id, $value, $class, $name, $label, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, $placeholder, 'text', 'price' );

		woocommerce_wp_text_input( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Outputs a decimal specific text input box
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $label
	 * @param string|null $placeholder
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param mixed|null  $value
	 *
	 * @since   1.0.0
	 */
	public function decimal_input(
		$field, $id = null, $label = null, $placeholder = null, $description = null,
		$descTip = null, $class = null, $style = null, $customAttributes = null,
		$wrapperClass = null, $name = null, $value = null
	) {

		$field = $this->set_all_fields( $field, $id, $value, $class, $name, $label, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, $placeholder, 'text', 'decimal' );

		woocommerce_wp_text_input( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Outputs a stock specific text input box
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $label
	 * @param string|null $placeholder
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param mixed|null  $value
	 *
	 * @since   1.0.0
	 */
	public function stock_input(
		$field, $id = null, $label = null, $placeholder = null, $description = null,
		$descTip = null, $class = null, $style = null, $customAttributes = null,
		$wrapperClass = null, $name = null, $value = null
	) {

		$field = $this->set_all_fields( $field, $id, $value, $class, $name, $label, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, $placeholder, 'text', 'stock' );

		woocommerce_wp_text_input( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Outputs a url specific text input box
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $label
	 * @param string|null $placeholder
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param mixed|null  $value
	 *
	 * @since   1.0.0
	 */
	public function url_input(
		$field, $id = null, $label = null, $placeholder = null, $description = null,
		$descTip = null, $class = null, $style = null, $customAttributes = null,
		$wrapperClass = null, $name = null, $value = null
	) {

		$field = $this->set_all_fields( $field, $id, $value, $class, $name, $label, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, $placeholder, 'text', 'url' );

		woocommerce_wp_text_input( $field );
	}

	/**
	 * Output a hidden input box.
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $class
	 * @param string|null $value
	 *
	 * @since   1.0.0
	 */
	public function hidden_input( $field, $id = null, $class = null, $value = null ) {
		$field = $this->set_all_fields( $field, $id, $value, $class );
		woocommerce_wp_hidden_input( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Output a textarea input box.
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $label
	 * @param string|null $placeholder
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param string|null $value
	 *
	 * @since   1.0.0
	 */
	public function textarea_input(
		$field, $id = null, $label = null, $placeholder = null, $description = null,
		$descTip = null, $class = null, $style = null, $customAttributes = null,
		$wrapperClass = null, $name = null, $value = null
	) {
		$field = $this->set_all_fields( $field, $id, $value, $class, $name, $label, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, $placeholder );
		woocommerce_wp_textarea_input( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Output a checkbox input box.
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $cbValue
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param string|null $value
	 *
	 * @since   1.0.0
	 */
	public function checkbox(
		$field, $id = null, $cbValue = null, $description = null, $descTip = null, $class = null,
		$style = null, $customAttributes = null, $wrapperClass = null, $name = null, $value = null
	) {

		$field = $this->set_all_fields( $field, $id, $value, $class, $name, null, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, null, null, null, null, $cbValue );
		woocommerce_wp_checkbox( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Output a select input box.
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $options
	 * @param string|null $label
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param string|null $value
	 *
	 * @since   1.0.0
	 */
	public function select(
		$field, $id = null, $options = null, $label = null, $description = null, $descTip = null,
		$class = null, $style = null, $customAttributes = null, $wrapperClass = null, $name = null,
		$value = null
	) {

		$field = $this->set_all_fields( $field, $id, $value, $class, $name, $label, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, null, null, null, $options );

		woocommerce_wp_select( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Output a radio input box.
	 *
	 * @param array       $field
	 * @param string|null $id
	 * @param string|null $options
	 * @param string|null $label
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $class
	 * @param string|null $style
	 * @param array|null  $customAttributes
	 * @param string|null $wrapperClass
	 * @param string|null $name
	 * @param string|null $value
	 *
	 * @since   1.0.0
	 */
	public function radio(
		$field, $id = null, $options = null, $label = null, $description = null, $descTip = null,
		$class = null, $style = null, $customAttributes = null, $wrapperClass = null, $name = null,
		$value = null
	) {

		$field = $this->set_all_fields( $field, $id, $value, $class, $name, $label, $description, $descTip, $style,
		                                $wrapperClass,
		                                $customAttributes, null, null, null, $options );

		woocommerce_wp_radio( $field );
	}
	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Creates the array of 'attribute' => 'value' in the $field array
	 *
	 * @param  array      $field
	 * @param string      $id
	 * @param string      $value
	 * @param string|null $class
	 * @param string|null $name
	 * @param string|null $label
	 * @param string|null $description
	 * @param bool|null   $descTip default true
	 * @param string|null $style
	 * @param string|null $wrapperClass
	 * @param string|null $customAttributes
	 * @param string|null $placeholder
	 * @param string|null $type
	 * @param string|null $dataType
	 * @param string|null $options
	 * @param string|null $cbValue
	 *
	 * @return mixed
	 *
	 * @since   1.0.0
	 */
	protected function set_all_fields(
		$field, $id, $value, $class = null, $name = null, $label = null,
		$description = null,
		$descTip = null,
		$style = null, $wrapperClass = null, $customAttributes = null,
		$placeholder = null,
		$type = null,
		$dataType = null, $options = null, $cbValue = null
	) {

		// Add prefix to field names
		$id   = $this->maybe_prefix( $id );
		$name = $this->maybe_prefix( $name );

		$this->maybe_set_field( $field, 'id', $id );
		$this->maybe_set_field( $field, 'options', $options );
		$this->maybe_set_field( $field, 'label', $label );
		$this->maybe_set_field( $field, 'class', $class );
		$this->maybe_set_field( $field, 'placeholder', $placeholder );
		$this->maybe_set_field( $field, 'style', $style );
		$this->maybe_set_field( $field, 'wrapper_class', $wrapperClass );
		$this->maybe_set_field( $field, 'value', $value );
		$this->maybe_set_field( $field, 'name', $name );
		$this->maybe_set_field( $field, 'custom_attributes', $customAttributes );
		$this->maybe_set_field( $field, 'description', $description );
		$descTip = null !== $descTip ? $descTip : true;
		$this->maybe_set_field( $field, 'desc_tip', $descTip );
		$this->maybe_set_field( $field, 'type', $type );
		$this->maybe_set_field( $field, 'data_type', $dataType );
		$this->maybe_set_field( $field, 'cbvalue', $cbValue );

		return $field;
	}

	/**
	 * Adds prefix to id and name if string
	 *
	 * @param mixed $identifier
	 *
	 * @return mixed
	 *
	 * @since    1.0.0
	 * @updated  1.0.1
	 */
	protected function maybe_prefix( $identifier ) {
		if ( is_string( $identifier ) ) {
			return $this->field_prefix . $identifier;
		}

		return $identifier;
	}

	/**
	 * Sets up the field value in the array
	 *
	 * @param array  $array
	 * @param string $field
	 * @param mixed  $value
	 *
	 * @since   1.0.0
	 */
	private function maybe_set_field( &$array, $field, $value ) {
		if ( null !== $value && ! isset( $array[ $field ] ) ) {
			$array[ $field ] = $value;
		}
	}
}
