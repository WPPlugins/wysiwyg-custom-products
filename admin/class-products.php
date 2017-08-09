<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 19/08/16
 * Time: 11:20 AM
 *
 * @since      1.0.0
 * @updated    1.0.4
 */
namespace WCP;
if ( class_exists( 'Products' ) ) {
	return;
}

/**
 * Class Products
 *
 * @package    Wcp_Plugin
 * @subpackage Admin
 *
 * @since      1.0.0
 * @updated    1.0.4
 */
class Products {
	/**
	 * @var int Current post (product) id
	 */
	private $postId;

	/**
	 * @var Html_Helper
	 */
	private $h;

	/**
	 * @var Wc_Helper
	 */
	private $wc;

	/**
	 * Products constructor.
	 *
	 * @since   1.0.0
	 * @updated 1.0.7
	 */
	public function __construct() {
		$this->h = new Html_Helper();
		// This is the only place Wc_Helper is used, so include it here
		require_once WCP_COMMON_DIR . 'class-wc-helpers.php';

		$this->wc = new Wc_Helper( Wcp_Plugin::META_PREFIX );

		// Create tabs
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'custom_product_tabs' ] );

		// Display Fields
		add_action( 'woocommerce_product_data_panels', [ $this, 'wcp_options_content' ] );
		// Save Fields
		add_action( 'woocommerce_process_product_meta', [ $this, 'custom_fields_save' ] );

	}

	/**
	 * @param $tabs
	 *
	 * @return mixed
	 *
	 * @since   1.0.0
	 * @updated 1.0.7
	 */
	public function custom_product_tabs( $tabs ) {
		$tabs['wcp'] = [
			// translators: text used as plugin data tab on product page
			'label'  => __( 'Wysiwyg Customization', 'wysiwyg-custom-products' ),
			'target' => 'wcp_options',
			'class'  => [ 'hide_if_virtual hide_if_grouped active hide_if_downloadable' ],
		];
		return apply_filters( 'custom_product_tags', $tabs );
	}

	/**
	 *  Creates input fields for the WCP options for a product
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	public function wcp_options_content() {
		global $post_ID;

		register_style( 'products', [], '1.1.0' );

		$h  = $this->h;
		$wc = $this->wc;

		$h->o_div( 'panel woocommerce_options_panel', 'wcp_options' );
		$h->o_div( 'options_group', 'wcp_options' );

		do_action( 'before_options_content' );

		try {
			$layouts = Layout::loadLayouts(); // Sanitized layouts
		} catch ( \Exception $e ) {
			$layouts = [];
		}

		array_unshift( $layouts, 'N/A' );

		// WooCommerce is responsible for escaping it's output fields as appropriate.

		// translators: Layout selection label on product page
		$wc->select( [], 'layout', array_combine( $layouts, $layouts ),
			__( 'Choose layout', 'wysiwyg-custom-products' ) );

		$wc->textarea_input( [],
			'catalog_text',
			// translators: prompt for the static text to be shown when this product is being listed on catalog pages
			__( 'Catalog text', 'wysiwyg-custom-products' ),
			// translators: description about the static text to be shown when this product is being listed on catalog pages
			__( 'Optional: Lines of text to be displayed on product list pages. Product title is used if left empty. Use --- for blank image.',
				'wysiwyg-custom-products' ) );


		do_action( 'after_options_content' );
		$h->c_div( 0 );

	}

	/**
	 * Saves the fields on both tabs
	 *
	 * @param int $post_ID
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	public function custom_fields_save( $post_ID ) {
		$this->postId = $post_ID;
		do_action( 'before_product_data_save' );

		$this->field_save( 'layout', 'WCP\is_non_empty_string', 'sanitize_text_field' );
		$this->field_save( 'catalog_text', 'is_string', 'sanitize_textarea_field' );

		do_action( 'after_product_data_save' );
	}

	/**
	 * Saves specified field, optional delete of empty field
	 *
	 * @param string        $fieldName
	 * @param callable      $validator
	 * @param callable|null $sanitizer
	 * @param string        $emptyAction What to do if field is empty ie. 0, '', null etc.
	 *
	 * @since   1.0.0
	 * @updated 1.0.4
	 */
	private function field_save( $fieldName, $validator, $sanitizer = null, $emptyAction = 'delete' ) {
		try {
			$value = get_field_value( $_POST, $fieldName ); // Will throw exception if value is not found
			$value = apply_filters( 'save_field_' . $fieldName, $value );

			if ( empty( $value ) ) {
				if ( 'delete' === $emptyAction ) {
					delete_post_meta( $this->postId, $fieldName );

					return;
				} elseif ( 'ignore' === $emptyAction ) {
					return;
				}
			}

			if ( ! ( is_callable( $validator ) && $validator( $value ) ) ) {
				return;
			}

			if ( null !== $sanitizer ) {
				if ( ! is_callable( $sanitizer ) ) {
					return;
				}
				$value = $sanitizer( $value );
			}

			update_post_meta( $this->postId, $fieldName, $value );
		} catch ( \Exception $e ) {
			// Ignore exceptions
		}
	}
}

global $productAdmin;
$productAdmin = new Products();
