<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 19/08/16
 * Time: 11:20 AM
 *
 * @since 1.0.0
 */
namespace WCP;
if ( class_exists( 'Settings' ) ) {
	return;
}

/**
 * Class Settings
 *
 * Administration page for manipulating layouts
 *
 * @package    WCP
 * @subpackage Admin
 *
 * @since      1.0.0
 * @updated    1.1.2
 */
class Settings {
	/**
	 * @var string
	 */
	private $screenId;

	/**
	 * @var array
	 */
	private $layouts;

	/**
	 * @var string
	 */
	private $layoutName;

	/**
	 * @var Layout
	 */
	private $layout;

	/**
	 * @var array
	 */
	private $messages;

	/**
	 * @var Wp_Html_Helper
	 */
	private $htmlEcho;

	/**
	 * @var Wp_Html_Helper
	 */
	private $htmlBuild;

	/**
	 * @var Wp_Html_Helper
	 */
	private $htmlReturn;

	/**
	 * @var array
	 */
	private $numberOfLines;


	/**
	 * Settings constructor.
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}

	/**
	 * WP Hooks
	 *
	 * @since 1.1.0
	 */
	public function admin_menu() {
		// Add this tab
		$this->screenId = add_options_page( Wcp_Plugin::$localeSettingsTab,
			Wcp_Plugin::$localeSettingsTab,
			'manage_options',
			Wcp_Plugin::PLUGIN_NAME . '-settings' );

		// Deal with admin header stuff
		add_action( "load-{$this->screenId}", [ $this, 'load_page' ] );

		// Display our page
		add_action( "{$this->screenId}", [ $this, 'display_page' ] );
	}

	/**
	 * Prepares page for display
	 *
	 * @since 1.1.0
	 */
	public function load_page() {
		// Can the user do this?
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen           = get_current_screen();
		// Make sure it's us
		if ( $screen->id !== $this->screenId ) {
			return;
		}

		// Setup helpers
		$shortcuts['div']['r']      = 'row';
		$shortcuts['input']['s']    = 'small-text';
		$shortcuts['input']['r']    = 'regular-text';
		$shortcuts['input']['m']    = 'medium-text';
		$shortcuts['checkbox']['c'] = 'checkbox';
		$this->htmlEcho   = new Wp_Html_Helper( Html_Helper::ECHO_ONLY, $shortcuts );
		$this->htmlBuild  = new Wp_Html_Helper( Html_Helper::BUILD_HTML, $shortcuts );
		$this->htmlReturn = new Wp_Html_Helper( Html_Helper::RETURN_ONLY, $shortcuts );

		$this->add_help( $screen );
		$this->add_options( $screen );
		$this->add_meta_boxes();
	}

	/**
	 *
	 */
	public function display_page() {
		$this->initialise();

		$html = $this->htmlEcho;

		$html->o_div( 'wrap' );
		// translators: Plugin settings page title - added to the translated plugin title
		$html->tag( 'h1', Wcp_Plugin::$localePluginTitle . ' ' . __( 'Settings', 'wysiwyg-custom-products' ), 'wp-heading-inline' );

		$html->o_form( 'wcp_form' );

		/* Used to save closed meta boxes and their order */
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		// My nonce
		$html->input( 'hidden', 'wcp_nonce', '', wp_create_nonce( 'wcp-settings' ) );

		$html->o_div( '', 'poststuff' );
		$html->o_div( 'metabox-holder columns-' . get_current_screen()->get_columns(), 'post-body' );

		//do_action( 'before_settings' );
		$html->o_div( '', 'post-body-content' );
		$this->layout_section();
		$html->c_div();

		$html->o_div( 'postbox-container', 'postbox-container-1' );
		do_meta_boxes( '', 'side', null );
		$html->c_div();

		$html->o_div( 'postbox-container', 'postbox-container-2' );
		do_meta_boxes( '', 'normal', null );
		do_meta_boxes( '', 'advanced', null );
		$html->c_div();

		//do_action( 'after_settings' );

		$html->c_div( 2 ); // 'post-body poststuff'
		$html->c_tag( 'form' );
		$html->c_div(); // wrap
	}

	/**
	 * Displays the layout operations panel
	 *
	 * @since    1.1.0 Refactored
	 * @updated  1.1.2
	 */
	public function layout_operations_meta_box() {
		$htmlBuild = $this->htmlBuild;
		$htmlEcho  = $this->htmlEcho;


		$htmlEcho->o_div( 'woocommerce_options_panel' );
		$htmlEcho->pushDoEscape( false );

		// translators: Layout selection label on the settings page
		$htmlBuild->sel( 'layouts', $this->layouts, __( 'Choose layout', 'wysiwyg-custom-products' ), $this->layoutName );
		// translators: delete layout prompt text
		$htmlBuild->tag( 'span', __( 'Delete layout', 'wysiwyg-custom-products' ), 'wcp-link' . ( count( $this->layouts ) < 2 ? ' hidden' : '' ),
			'wcp_delete' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );


		// translators: label for the new name field when a layout is renamed or copied to a new name
		$htmlBuild->text( 'new_name', __( 'New name', 'wysiwyg-custom-products' ) );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlBuild->tag( 'span',
			// translators: copy layout button text
			__( 'Copy to new name', 'wysiwyg-custom-products' ),
			'button button-primary button-large',
			'wcp_copy' );

		// translators: rename layout prompt text
		$htmlBuild->tag( 'span', __( 'Change name', 'wysiwyg-custom-products' ), 'wcp-link', 'wcp_rename' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlEcho->popDoEscape();
		$htmlEcho->c_div();
	}

	/**
	 * Displays the line formatting panel
	 *
	 * @since    1.1.0 Refactored
	 */
	public function line_format_meta_box() {
		$htmlBuild = $this->htmlBuild;
		$htmlEcho  = $this->htmlEcho;
		$htmlReturn = $this->htmlReturn;

		$htmlEcho->o_div( 'woocommerce_options_panel' );
		$htmlEcho->pushDoEscape( false );

		$alignments = [
			// translators: Center aligned text
			'C' => __( 'Centered', 'wysiwyg-custom-products' ),
			// translators: Left aligned text
			'L' => __( 'Left Aligned', 'wysiwyg-custom-products' ),
			// translators: Right aligned text
			'R' => __( 'Right Aligned', 'wysiwyg-custom-products' ),
		];

		$htmlBuild->sel( 'max_lines',
			$this->numberOfLines,
			// translators: prompt for maximum number of lines in layout
			__( 'Maximum possible lines', 'wysiwyg-custom-products' ),
			$this->layout->maxLines,
			Html_Helper::VALUE_IS_ONE_BASED
		);

		$htmlBuild->tag( 'span',
			// translators: Update layout button text
			__( 'Update', 'wysiwyg-custom-products' ),
			'button button-primary button-large right disabled',
			'wcp_save' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$currentLines = $this->layout->currentLines;
		$htmlBuild->sel( 'current_lines',
			$this->numberOfLines,
			// translators: Prompt for choosing how many lines to format
			__( 'Format for', 'wysiwyg-custom-products' ),
			$currentLines,
			Html_Helper::VALUE_IS_ONE_BASED
		);

		$htmlBuild->tag( 'span',
			// translators: cancel modifications link
			__( 'Cancel changes', 'wysiwyg-custom-products' ),
			'wcp-link right disabled',
			'wcp_cancel' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		// translators: Prompt for choosing the font size to work with
		$htmlBuild->lbl( 'current_font', __( 'Choose font size', 'wysiwyg-custom-products' ) );
		$htmlBuild->rb( 'current_font', [
			// translators: Minimum font label used on settings page
			__( 'Min Font', 'wysiwyg-custom-products' ) => 'MinFont',
			// translators: Maximum font label used on settings page
			__( 'Max Font', 'wysiwyg-custom-products' ) => 'MaxFont',
		],
			'MaxFont', '', Html_Helper::LABEL_TO_VALUE );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlEcho->o_tag( 'table', 'hidden', 'line_formats' );
		$htmlEcho->tr( [
			'Y',
			'X',
			// translators: Font alignment table heading
			__( 'Alignment', 'wysiwyg-custom-products' ),
			// translators: Field width table heading
			__( 'Width', 'wysiwyg-custom-products' ),
			__( 'Min Font', 'wysiwyg-custom-products' ),
			__( 'Max Font', 'wysiwyg-custom-products' ),
		], 'h' );

		$htmlEcho->pushDoEscape( false ); // Don't want the pre-escaped inner controls to be escaped into html data for the table
		for ( $i = 0; $i < Layout::MAX_LINES; $i ++ ) {
			$hidden = $i < $currentLines ? '' : ' hidden';
			$htmlEcho->tr( [
				// Y
				$htmlReturn->number( null, null, 0, 's', [ 'min' => 0, 'max' => $this->layout->height, 'step' => 1 ] ),
				// X
				$htmlReturn->number( null, null, 0, 's', [ 'min' => 0, 'max' => $this->layout->width, 'step' => 1 ] ),
				// Align
				$htmlReturn->sel( null, $alignments, null, 'C', Html_Helper::VALUE_TO_LABEL ),
				// Width
				$htmlReturn->number( null, null, 0, 's', [ 'min' => 0, 'max' => $this->layout->width, 'step' => 1 ] ),
				// MinFont
				$htmlReturn->number( null, null, 0, 's', [
					'min'  => Layout::MIN_FONT_SIZE,
					'max'  => intdiv( $this->layout->height, 2 ),
					'step' => 1,
				] ),
				// MaxFont
				$htmlReturn->number( null, null, 0, 's', [
					'min'  => Layout::MIN_FONT_SIZE,
					'max'  => intdiv( $this->layout->height, 2 ),
					'step' => 1,
				] ),
			],
				'd',
				'',
				'format-line' . ( 0 === $i ? ' wcp-highlight' : $hidden ) );
		}

		$htmlEcho->tr( [
			// translators: Heading in table to let user know checkboxes are used to make the fields identical for all lines
			esc_attr__( 'Make same', 'wysiwyg-custom-products' ),
			// line heading Not escaped by the wp-html-helper, so do it here
			$htmlReturn->cbx( 'x-same' ),
			$htmlReturn->cbx( 'align-same' ),
			$htmlReturn->cbx( 'width-same' ),
			$htmlReturn->cbx( 'min-font-same' ),
			$htmlReturn->cbx( 'max-font-same' ),
		],
			[ 'h' ], // heading for first cell only
			'c', // center
			'same-size' );

		$htmlEcho->popDoEscape();
		$htmlEcho->popDoEscape(); // Back to escaping

		$htmlEcho->c_tag( 'table' );
		$htmlEcho->c_div();
	}

	/**
	 * Displays the error messages panel
	 *
	 * @since    1.1.0 Refactored
	 */
	public function messages_meta_box() {
		$htmlBuild = $this->htmlBuild;
		$htmlEcho  = $this->htmlEcho;

		$htmlEcho->o_div( 'woocommerce_options_panel' );
		$htmlEcho->pushDoEscape( false );

		// translators: Error message when a customer tries to put too much text on a line when they can use more lines
		$htmlBuild->text_area( 'multiline_msg', 3, 40, __( 'Text too wide - paragraph', 'wysiwyg-custom-products' ), $this->layout->getMultiLineReformatMsg(), 'overflow-message' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		// translators: Error message when a customer tries to have too many lines for the current layout
		$htmlBuild->text_area( 'too_many_lines_msg', 3, 40, __( 'Too many lines', 'wysiwyg-custom-products' ), $this->layout->getNumberOfLinesMsg(), 'overflow-message' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		// translators: Error message when a customer tries to put too much text on a line when they can only use one line
		$htmlBuild->text_area( 'singleline_msg', 3, 40, __( 'Text too wide - text field', 'wysiwyg-custom-products' ), $this->layout->getSingleLineReformatMsg(), 'overflow-message' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlEcho->popDoEscape();
		$htmlEcho->c_div();
	}

	/**
	 * Displays the style selection panel
	 *
	 * @since   1.1.0
	 */
	public function styles_meta_box() {
		$htmlEcho       = $this->htmlEcho;
		$htmlReturn = $this->htmlReturn;


		$attr_tax = wc_get_attribute_taxonomies();
		$htmlEcho->o_div( 'woocommerce_options_panel variations' );

		if ( ! empty( $attr_tax ) ) {
			$htmlEcho->o_tag( 'table' );
			$htmlEcho->pushDoEscape( false ); // Don't want the pre-escaped inner controls to be escaped into html data for the table
			$htmlEcho->o_tag( 'tr' );
			// translators: Attribute selection table heading
			$htmlEcho->th( __( 'Attribute', 'wysiwyg-custom-products' ), '', '', '', [ 'width' => '100px' ] );
			// translators: Attribute value selection table heading
			$htmlEcho->th( 'Value', '', '', '', [ 'width' => '100px' ] );
			$htmlEcho->c_tag( 'tr' );

			foreach ( $attr_tax as $tax ) {
				$term   = 'pa_' . $tax->attribute_name;
				$values = (array) get_terms( [ 'taxonomy' => $term, 'hide_empty' => false ] );

				$options = [ '' => "" ];
				foreach ( $values as $value ) {
					$options[ $value->slug ] = $value->name;
				}

				$htmlEcho->tr( [
					$tax->attribute_label,
					// Attributes
					$htmlReturn->sel( $term, $options, null, null, Html_Helper::VALUE_TO_LABEL ),
				] );
			}
			$htmlEcho->popDoEscape(); // Back to escaping
			$htmlEcho->c_tag( 'table' );
		}

		$htmlEcho->c_div();
	}

	/**
	 * Displays the color selection panel
	 *
	 * @since   1.1.1
	 */
	public function color_picker_meta_box() {
		$htmlBuild = $this->htmlBuild;
		$htmlEcho  = $this->htmlEcho;

		$htmlEcho->o_div( 'woocommerce_options_panel' );
		$htmlEcho->pushDoEscape( false );

		$htmlBuild->text( 'ink_color',
			// translators: Color of font in use
			__( 'Font Color', 'wysiwyg-custom-products' ),
			$this->layout->getColorString( 'ink' ),
			'color-picker' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlBuild->text( 'sizing_color',
			// translators: Colour of rectangles being used to size min or max font as selected
			__( 'Current Font Box', 'wysiwyg-custom-products' ),
			$this->layout->getColorString( 'size' ),
			'color-picker' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlBuild->text( 'non_sizing_color',
			// translators: Colour of rectangles being used indicate non selected font
			__( 'Non Selected Box', 'wysiwyg-custom-products' ),
			$this->layout->getColorString( 'non-size' ),
			'color-picker' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlEcho->popDoEscape();
		$htmlEcho->c_div();
	}

	/**
	 * Displays the global options panel
	 *
	 * @since   1.1.2
	 */
	public function global_options_meta_box() {
		$htmlBuild = $this->htmlBuild;
		$htmlEcho  = $this->htmlEcho;

		$htmlEcho->o_div( 'woocommerce_options_panel' );
		$htmlEcho->pushDoEscape( false );

		$cleanDelete = get_option( 'settings', 'yes', 'clean_delete' );
		$htmlBuild->cbx( 'save_on_delete',
			// translators: Whether settings should be removed from database when plugin is deleted
			__( 'Clean plugin delete', 'wysiwyg-custom-products' ),
			'no' !== $cleanDelete );
		$htmlBuild->tag( 'span', 'Delete all plugin information (including associations with product) when the plugin is deleted. Clear if changing plugin versions.', 'description' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		 // translators: first part of change font message. Used as a link.
		 $htmlBuild->a( __( 'Change font', 'wysiwyg-custom-products' ),
		 admin_url() . 'plugin-editor.php?file=' . Wcp_Plugin::PLUGIN_NAME . '%2Fuser%2Ffonts.css' );
		 // translators: second part of change font message.
		 $htmlBuild->suffix_html( esc_html__( ' for all layouts and products.', 'wysiwyg-custom-products' ) );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlEcho->popDoEscape();
		$htmlEcho->c_div();
	}
	/**
	 * Creates layout (the only) tab content
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	private function layout_section() {
		$this->layout_image_section();
	}

	/**
	 * Image(s) and associated buttons for layout tab - half width
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	private function layout_image_section() {
		$htmlEcho = $this->htmlEcho;

		$this->display_image();

		$htmlEcho->o_div( 'image-operations' );
		$htmlEcho->o_tag( 'p', 'center' );
		// translators: prompt for selection of a product image to create a layout for
		$htmlEcho->tag( 'span', __( 'Choose Product Image', 'wysiwyg-custom-products' ), 'wcp-link wcp-browse-image',
			'wcp_main_image',
			[
				'data' => [
					// translators: media browser title when selecting product image
					'uploader_title'       => __( 'Select layout product image', 'wysiwyg-custom-products' ),
					// translators: media browser button text when selecting product image
					'uploader_button_text' => __( 'Set Layout Image', 'wysiwyg-custom-products' ),
				],
			]
		);
		$htmlEcho->c_tag( 'p div' );
	}

	/**
	 * Creates the SVG image for the image portion of layout tab
	 *
	 * @since   1.0.0
	 * @updated 1.1.1
	 */
	private function display_image() {
		$htmlEcho  = $this->htmlEcho;
		$htmlBuild = $this->htmlBuild;
		$layout = $this->layout;

		$htmlEcho->o_div( 'hidden' );
		$htmlEcho->o_div( '', 'wcp_canvas_div' );
		$htmlEcho->tag( 'canvas', '', 'wcp-canvas' );
		$htmlEcho->c_div( 2 );

		$htmlBuild->o_div( 'svg wcp', 'wcp_image_div' );
		$htmlBuild->o_svg( $layout->width, $layout->height, 'responsive', 'wcp_svg_image', [ 'height' => '180%' ] );

		$htmlBuild->o_tag( 'g' );

		$htmlBuild->svg_sized_img( $layout->image, $layout->size, true, '', 'svg_background' );

		$htmlBuild->o_tag( 'text', '', 'display_text', [], [ 'fill' => $layout->getColorString( 'ink' ) ]);
		for ( $i = 0; $i < Layout::MAX_LINES; $i ++ ) {
			$htmlBuild->tspan( '', 0, 0, 'hidden wcp-line' . ( $i + 1 ), "tspan$i" );
		}
		$htmlBuild->c_tag( 'text' );

		$htmlBuild->o_tag( 'g', '', 'non_size_rects', [], [
			'fill'         => $layout->getColorString( 'non-size' ),
			'fill-opacity' => 0.2
		] );
		for ( $i = 0; $i < Layout::MAX_LINES; $i ++ ) {
			$htmlBuild->rect( 0, 0, 1, 1, [], 'hidden', "nonSizeRect$i", [] );
		}
		$htmlBuild->c_tag( 'g' );

		$htmlBuild->o_tag( 'g', '', 'size_rects', [], [
			'fill'           => $layout->getColorString( 'size' ),
			'fill-opacity'   => 0.3,
			'stroke-opacity' => 0.7
		]);
		for ( $i = 0; $i < Layout::MAX_LINES; $i ++ ) {
			$htmlBuild->rect( 0, 0, 1, 1, [], 'resize-drag hidden', "rect$i", [] );
		}
		$htmlBuild->c_tag( 'g g svg div' );

		echo apply_filters( 'settings_svg_image', $htmlBuild->get_html() );
	}

	/**
	 * Define our metaboxes
	 *
	 * @since    1.1.0
	 * @updated  1.1.2
	 */
	private function add_meta_boxes() {
		add_meta_box( 'wcp_layout_operations',
			// translators: Heading for layout operations panel
			__( 'Layout Operations', 'wysiwyg-custom-products' ),
			[ $this, 'layout_operations_meta_box' ], null, 'side' );

		// translators: Heading for layout formatting panel
		add_meta_box( 'wcp_line_format', __( 'Format Layout', 'wysiwyg-custom-products' ),
			[ $this, 'line_format_meta_box' ], null, 'side' );

		// translators: Heading for end customer error messages panel
		add_meta_box( 'wcp_error_messages', __( 'Customer Error Messages', 'wysiwyg-custom-products' ),
			[ $this, 'messages_meta_box' ], null, 'side' );
		// translators: Heading for layout color selection
		add_meta_box( 'wcp_colors', __( 'Layout Colors', 'wysiwyg-custom-products' ),
		              [ $this, 'color_picker_meta_box' ], null, 'normal' );

		// translators: Heading for global options section
		add_meta_box( 'wcp_global_options', __( 'Global Plugin Options', 'wysiwyg-custom-products' ),
		              [ $this, 'global_options_meta_box' ], null, 'normal' );
	}

	/**
	 * Add help information
	 *
	 * @param $screen \WP_Screen
	 *
	 * @since    1.1.0
	 * @updated  1.1.2
	 */
	private function add_help( $screen ) {
		$htmlReturn = $this->htmlReturn;
		$htmlBuild  = $this->htmlBuild;

		$screen->add_help_tab( [
			'id'      => 'wcp-overview-tab',
			'title'   => __( 'Overview', 'wysiwyg-custom-products' ),
			'content' => $htmlReturn->tag( 'p',
				// translators: settings screen overview help text
				__( 'This screen enables you to create, modify and copy layouts for any products that need real-time customer previews.',
					'wysiwyg-custom-products' ) )
		] );

		$screen->add_help_tab( [
			'id'      => 'wcp-operations-tab',
			'title'   => __( 'Layout Operations', 'wysiwyg-custom-products' ),
			'content' => $htmlReturn->tag( 'p',
				// translators: settings screen layout operations meta box help - free version
				__( 'Copy, rename or delete layouts here. Change font for all layouts and products by using link to edit stylesheet.',
					'wysiwyg-custom-products' ) )
		] );

		$htmlBuild->tag( 'p',
			// translators: settings screen format layout meta box help - overview
			__( 'Change the number of lines available in a layout. For each line count specify the formatting of the lines.',
				'wysiwyg-custom-products' ) );
		$htmlBuild->tag( 'p',
			// translators: format - use of font radio buttons
			__( 'Use the Max Font and Min Font radio buttons to change the view.',
				'wysiwyg-custom-products' ) );
		$htmlBuild->tag( 'p',
			// translators: format - using make same check boxes
			__( 'If the "make same" checkboxes are ticked then that value will be set for all lines for the current number of lines.  When ticking an empty box, all of the lines are made the same as the highlighted line.',
				'wysiwyg-custom-products' ) );
		$htmlBuild->tag( 'p',
			// translators: format - use of mouse
			__( 'The formats (width, font size, and position) can also be modified using the mouse.',
				'wysiwyg-custom-products' ) );

		$screen->add_help_tab( [
			'id'      => 'wcp-format-tab',
			'title'   => __( 'Format Layout', 'wysiwyg-custom-products' ),
			'content' => $htmlBuild->get_html()
		] );

		$screen->add_help_tab( [
			'id'      => 'wcp-messages-tab',
			// translators: Customer error messages help tag title
			'title'   => __( 'Customer Messages', 'wysiwyg-custom-products' ),
			'content' => $htmlReturn->tag( 'p',
				// translators: Customer error messages overview
				__( 'These are the messages shown to the customer when the text they type cannot be displayed using the applied format.  They can be set on a per layout basis.',
					'wysiwyg-custom-products' ) )
		] );

		$screen->add_help_tab( [
			'id'      => 'wcp-color-tab',
			// translators: Layout color choices help tab title
			'title'   => __( 'Layout Colors', 'wysiwyg-custom-products' ),
			'content' => $htmlReturn->tag( 'p',
				// translators: settings screen color selection meta box help
				__( 'Here you can change the formatting colors used to suit the chosen image.',
					'wysiwyg-custom-products' ) )
		] );

		$htmlBuild->tag( 'p',
			// translators: introduction to help text about global settings section
			__( 'This section is for any options that are plugin (not layout) wide.' ) );
		$htmlBuild->tag( 'p',
			// translators: help text clean plugin delete checkbox
			__( 'Clean plugin delete: If this is checked (the default) *ALL* data associated with the plugin is removed when the plugin is deleted. 
			If it is not checked, then the data in the database is retained. Useful if upgrading or re-installing.' ) );

		$screen->add_help_tab( [
			'id'      => 'wcp-global-tab',
			// translators: Global plugin options help tab title
			'title'   => __( 'Global Plugin Options', 'wysiwyg-custom-products' ),
			'content' => $htmlBuild->get_html(),
		] );

//		$screen->set_help_sidebar( __('Sidetext', 'wysiwyg-custom-products'));

	}

	/**
	 * Add screen options
	 *
	 * @param $screen \WP_Screen
	 *
	 * @since  1.1.0
	 */
	private function add_options( $screen ) {
		/* Add screen option: user can choose between 1 or 2 columns (default 2) */
		$screen->add_option( 'layout_columns', [ 'max' => 2, 'default' => 2 ] );
	}

	/**
	 * Sets everything up for use
	 *
	 * @since   1.1.0
	 * @updated 1.1.2
	 */
	private function initialise() {
		$this->load_layouts();

		// Load scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );
		wp_enqueue_script( 'postbox' );
		wp_enqueue_media();

		register_script( 'interact', [], '1.0' );
		register_script( 'font-metrics', [], '1.1.0' );
		register_script( 'font-scalers', [ 'jquery', [ 'font-metrics' ] ], '1.1.0' );

		register_script( 'line-manager', [ 'jquery', [ 'font-metrics', 'font-scalers' ] ], '1.1.0' );
		register_script( 'admin-settings',
			[ 'jquery', 'wp-color-picker', [ 'interact', 'font-metrics', 'font-scalers', 'line-manager' ] ],
			'1.1.2' );

		// Load styles
		wp_enqueue_style( 'woocommerce_admin_styles' ); // Leverage woocommerce formatting
		wp_enqueue_style( 'wp-color-picker' );

		register_style( 'frontend', [], '1.1' );
		register_style( 'settings', [], '1.1' );

		register_style( 'fonts', [], uniqid( '1.1.', false ), 'all', Wcp_Plugin::$userUrl . 'fonts.css',
			'wcpUserStyle' );


		// Set up message arrays

		// translators: Used when selecting formats for multiple (>1) lines
		$lines               = ' ' . ucfirst( _n( 'line', 'lines', 2, 'wysiwyg-custom-products' ) );
		$this->numberOfLines = [
			// translators: Used when formatting just one line
			__( 'Single Line', 'wysiwyg-custom-products' ),
			// translators: Numbers up to 10
			__( 'Two', 'wysiwyg-custom-products' ) . $lines,
			__( 'Three', 'wysiwyg-custom-products' ) . $lines,
			__( 'Four', 'wysiwyg-custom-products' ) . $lines,
			__( 'Five', 'wysiwyg-custom-products' ) . $lines,
			__( 'Six', 'wysiwyg-custom-products' ) . $lines,
			__( 'Seven', 'wysiwyg-custom-products' ) . $lines,
			__( 'Eight', 'wysiwyg-custom-products' ) . $lines,
			__( 'Nine', 'wysiwyg-custom-products' ) . $lines,
			__( 'Ten', 'wysiwyg-custom-products' ) . $lines,
		];

		$this->messages = [
			// translators: warning message when making change to layout is potentially damaging
			'reducing_max_lines'  => __( 'Reducing maximum number of lines will cause loss of formatting information. Are you sure?',
				'wysiwyg-custom-products' ),
			// translators: warning when leaving the layout edit page with unsaved changes
			'modified_leave'      => __( 'Changes will be lost. Are you sure you want to leave?',
				'wysiwyg-custom-products' ),
			// translators: confirmation that the user wants to delete the selected layout
			'confirm_delete'      => __( 'Are you sure you want to delete', 'wysiwyg-custom-products' ),
		];
		// Force free prefix to avoid too much mucking around in admin-settings.js for premium version
		localize_script( 'admin-settings', 'messages', $this->messages, 'wcp_' );
	}

	/**
	 * Initialise the layout section
	 *
	 * @since  1.1.0
	 */
	private function load_layouts() {
		try {
			$layouts          = Layout::loadLayouts();
			$this->layoutName = sanitize_text_field( get_option( 'settings', current( $layouts ), 'CurrentLayout' ) );
			$key              = array_search( $this->layoutName, $layouts, false );
			if ( false === $key ) {
				$this->layoutName = current( $layouts );
				update_option( 'settings', $this->layoutName, 'CurrentLayout' );
			}
		} catch ( \Exception $e ) {
			$this->layoutName = 'template'; // Try the default
			$layouts          = [ 'template' ];
		}
		$this->layouts = $layouts;

		try {
			$this->layout = new Layout( $this->layoutName );
			$this->layout->set_size( 'shop_single' );  // Use the size for customer display as size for doing layout
		} catch ( LayoutException $e ) {
		}
	}
}

global $wcpSettings;
$wcpSettings = new Settings();
