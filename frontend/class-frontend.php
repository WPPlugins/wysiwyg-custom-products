<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 7/10/16
 * Time: 12:30 PM
 */

namespace WCP;
if ( class_exists( 'Frontend' ) ) {
	return;
}

/**
 * Class Frontend
 *
 * @package    WCP
 * @subpackage Frontend
 *
 * @since      1.0.0
 * @updated    1.1.2
 */
class Frontend {

	const HANDLED_SIZES = 'shop_single;shop_catalog;';

	/**
	 * @var Layout
	 */
	private $layout;

	/**
	 * @var Wp_Html_Helper
	 */
	private $h;

	/**
	 * @var bool
	 */
	private $canvasDone = false;


	/**
	 * Frontend constructor.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		try {
			$this->layout = new Layout();
		}
		catch ( LayoutException $e ) {
			return;
		}
		add_filter( 'post_thumbnail_html', [ $this, 'product_thumbnail' ], 100, 5 );
	}


	/**
	 * Hook to determine whether the product being output is set up for Wysiwyg Custom Products
	 * Returns modified Html with the appropriate SVG image if it is
	 *
	 * @param string       $html              The post thumbnail HTML.
	 * @param int          $post_id           The post ID.
	 * @param string       $post_thumbnail_id The post thumbnail ID.
	 * @param string|array $size              The post thumbnail size. Image size or array of width and height
	 *                                        values (in that order). Default 'post-thumbnail'.
	 * @param string       $attr              Query string of attributes.
	 *
	 * @return string      Possibly modified $html
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	public function product_thumbnail(
		$html,
		$post_id,
		$post_thumbnail_id,
		$size,
		$attr
	) {

		if ( false === stripos( self::HANDLED_SIZES, $size . ';' ) ) {
			return $html;
		}

		$layoutName = get_post_meta( $post_id, 'layout', true );
		if ( empty( $layoutName ) || ( 'N/A' === $layoutName ) ) {
			return $html;
		}

		$layout = get_option( $layoutName );
		if ( ! $layout ) {// Invalid format name, leave it be
			return $html;
		}

		try {
			$this->layout->load_layout( $layout, $layoutName );
		}
		catch ( LayoutException $e ) {
			return $html; // Invalid layout
		}

		$this->h = new Wp_Html_Helper( Html_Helper::BUILD_HTML );

		$this->layout->set_size( $size );
		$this->layout->image = $post_thumbnail_id;

		$lines = get_post_meta( $post_id, 'specific_lines', true );

		/* Add stylesheet for SVG's and fonts */
		register_style( 'fonts', [], uniqid( '1.1.', false ), 'all', Wcp_Plugin::$userUrl . 'fonts.css',
		                Wcp_Plugin::OPTION_PREFIX . 'UserStyle' );
		register_style( 'frontend', [], '1.1.0' );

		wp_enqueue_script( 'jquery' );
		register_script( 'font-metrics', [], '1.1.0' );
		register_script( 'font-scalers', [ 'jquery', [ 'font-metrics' ] ], '1.1.0' );
		register_script( 'baseline-fix', [ 'jquery', [ 'font-metrics', 'font-scalers' ] ],
			'1.1.0' ); // for catalog images, whether on product page or in catalog

		if ( 'shop_single' === $size ) {
			/* Add live update javascript */
			register_script( 'attrchange', [ 'jquery' ], '1.0' );
			register_script( 'frontend', [ 'jquery', [ 'attrchange' ] ], '1.1.2' );

			/* Construct the html */
			$newHtml = $this->modified_img( $html );
			$newHtml .= $this->product_shop_single( $post_id, $lines );

			return apply_filters( 'frontend_shop_single', $newHtml, $post_id, $post_thumbnail_id, $size, $attr );
		} elseif ( 'shop_catalog' === $size ) {
			$newHtml = $this->product_shop_catalog( $html, $post_id );

			return apply_filters( 'frontend_shop_catalog', $newHtml, $post_id, $post_thumbnail_id, $size, $attr );
		} else {
			return $html;
		}
	}

	/**
	 * Returns supplied img html tag, modified so that it does not show. Used to monitor for WooCommerce image
	 * changes.
	 *
	 * @param string $html
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 */
	private function modified_img( $html ) {
		$h = $this->h;

		$source = $h->extract_tags( $html, 'img' )[0]['attributes'];
		$source['class'] .= ' wcp-hidden';
		// Rebuild image tag with 0 height and width and wcp-hidden class
		$h->img( $source['src'], 0, 0, $source['alt'], $source['class'], '', [ 'title' => $source['title'] ] );

		return $h->get_html();
	}

	/**
	 * Builds SVG html in $this->h
	 *
	 * @param array       $liveLines
	 * @param array|null  $message
	 * @param array|null  $formats
	 * @param string|null $divId
	 * @param string|null $imageId
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	private function svg_html( $liveLines, $message = null, $formats = null, $divId = null, $imageId = null ) {
		$h = $this->h;
		$l = $this->layout;

		$editable = null !== $divId;

		if ( ! $this->canvasDone ) {
			$h->o_div( 'hidden' );
			$h->o_div( '', 'wcp_canvas_div' );
			$h->tag( 'canvas', '', 'wcp-canvas' );
			$h->c_div( 2 );
			$this->canvasDone = true;
		}

		if ( $editable ) {
			$h->o_div( 'svg wcp', $divId );
			$h->o_div( '', 'wcp_attributes' );
		} else {
			$h->o_div( 'svg wcp wcp-catalog' );
		}

		$h->o_svg( $l->width, $l->height, 'responsive', '', [ 'height' => '180%' ] );
		$h->o_tag( 'g' );

		$h->svg_sized_img( $l->image, $l->size, false, '', 'svg_image' );

		if ( $formats ) {
			$h->o_tag( 'text', 'svg-text', $imageId . '_text', [ 'data-formats' => [ json_encode( $formats ) ] ] );
		} else {
			$h->o_tag( 'text', 'svg-text' );
		}

		$i = 0;
		foreach ( $liveLines as $line ) {
			$y           = $l->scaleY( $line['Y'] );
			$x           = $l->scaleX( $line['X'] );
			$maxFontSize = $l->scaleY( $line['MaxFont'] );
			$minFontSize = $l->scaleY( $line['MinFont'] );
			$maxWidth    = $l->scaleX( $line['Width'] );
			switch ( $line['Align'] ) {
				case 'l':
				case 'L':
					$align = 'start';
					break;
				case 'r':
				case 'R':
					$align = 'end';
					break;
				default:
					$align = 'middle';
			}

			$text = isset( $message[ $i ] ) ? trim( $message[ $i ] ) : '';
			$classes = $editable ? ' wcp-live-span' : '';
			$id      = $editable ? "tspan$i" : '';

			$h->tspan( $text, $x, $y, 'wcp-line' . ( $i + 1 ) . $classes, $id, [
				'text-anchor' => $align,
				'data'        => [
					'nominal-y' => $y,
					'min-font'  => $minFontSize,
					'max-font'  => $maxFontSize,
					'width'     => $maxWidth,
				],
			],
				[ 'font-size' => $maxFontSize . 'px' ] );
			$i ++;
		}

		$h->c_tag( 'text g svg' );
		$h->c_div( 0 ); // Close divs as required
	}

	/**
	 * Creates the SVG html when this is the product being browsed by the customer
	 *
	 * @param int $post_id The post ID.
	 * @param int $lines   specific number of format lines.
	 *
	 * @return string      New $html
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	private function product_shop_single( $post_id, $lines ) {
		$h = $this->h;
		$l = $this->layout;

		$formats    = [];
		$liveUpdate = false;
		$wcpLines   = $l->formats;
		if ( $lines ) {
			$liveUpdate = maybe_get( $wcpLines, 'Lines' . $lines );
		}

		if ( $liveUpdate ) {
			$line      = [ 'l' => $lines, 'f' => $l->compact_format( $lines ) ];
			$formats[] = $line;
		} else {
			for ( $i = 1; $i <= $l->maxLines; $i ++ ) {
				$multiLine = maybe_get( $wcpLines, 'Lines' . $i );
				if ( $multiLine ) {
					$line       = [ 'l' => $i, 'f' => $l->compact_format( $i ) ];
					$liveUpdate = $multiLine;
					$formats[]  = $line;
				}
			}
		}

		$productText = htmlspecialchars_decode( get_post_meta( $post_id, 'product_text', true ), ENT_QUOTES );

		$message = $productText ? explode( '|', $productText ) : false;

		$this->svg_html( $liveUpdate, $message, $formats, 'front_svg', 'svg_image' );
		$h->tag( 'p', $l->getMultiLineReformatMsg(), 'wcp-hidden wcp-lost-text', 'wcp_multiline' );
		$h->tag( 'p', $l->getNumberOfLinesMsg(), 'wcp-hidden wcp-lost-text', 'wcp_too_many_lines' );
		$h->tag( 'p', $l->getSingleLineReformatMsg(), 'wcp-hidden wcp-lost-text', 'wcp_single' );

		return $h->get_html(); //Todo . $this->svg_html($wcpFormat, $attributes, $liveUpdate, "BackSvg", $formats, $message);
	}


	/**
	 * Creates the SVG html for any products being shown in the catalog
	 *
	 * @param string $html    The post thumbnail HTML.
	 * @param int    $post_id The post ID.
	 *
	 * @return string          New $html
	 *
	 * @since   1.0.0
	 */
	private function product_shop_catalog( $html, $post_id ) {
		$lineSeparators = "\r\n";
		$catalogText    = htmlspecialchars_decode( get_post_meta( $post_id, 'catalog_text', true ), ENT_QUOTES );

		if ( empty( $catalogText ) ) {
			$catalogText    = wc_get_product( $post_id )->get_title();
			$lineSeparators = ' ';
		}

		if ( '---' !== $catalogText ) {
			$catalogText = explode( $lineSeparators, $catalogText );

			do {
				$liveUpdate = maybe_get( $this->layout->formats, 'Lines' . count( $catalogText ) );
				if ( ! $liveUpdate ) {
					array_pop( $catalogText );
				}
			} while ( ! $liveUpdate && ( count( $catalogText ) > 0 ) );

			if ( $liveUpdate ) {
				$this->svg_html( $liveUpdate, $catalogText );

				return $this->h->get_html();
			}
		}

		return $html;
	}

}

global $frontend;
$frontend = new Frontend();
