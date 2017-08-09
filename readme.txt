=== Wysiwyg Custom Products ===
Contributors: tazziedave
Donate link: https://tazziedave.com/plugins/wysiwyg-custom-products#Donate
Tags: WYSIWYG, wysiwyg, custom, customised, customized, preview, live, update, view, woocommerce
Requires at least: 4.5.4
Tested up to: 4.8.0
Stable tag: 1.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Provides live visual "WYSIWYG" on product image when customer changes text in edit fields on customizable products.

== Description ==

This plugin replaces the standard product image with a new image that has the text updated, in a font of your choice, in front of the product image as the customer enters their message.

Each layout can be specified from one up to a maximum of ten lines. As the customer types, the text is semi-automatically adjusted to fit. The font size is set as part of the layout and the text is shrunk to the minimum font size as required. When the customer hits the **Enter** key, the layout adjusts according to the number of lines now being typed. If the customer types too long a message (it won't fit within the width specified in the layout), or they try to have too many lines, then warning messages are displayed.

The administrator can create as many different layouts as they want and can associate each layout with any number of products.

When the WYSIWYG customized product is displayed as a catalog item the title of the product is automatically displayed as a message, or you can enter your own message to be shown in the catalog.

As this is a visual plugin, it's easier to see than describe, so take a look at the [demonstration video](https://tazziedave.com/plugins/wysiwyg-custom-products#Demonstration) or, for a live example, try [Heartstrings](http://heartstringshop.co.uk/make-your-own-plaque). Sorry orders only available in the UK. Choose any of the products and type in the message box. (As an aside, ALL of the round products on the Make Your Own Plaque page use the same images. Just one per color. The backgrounds and the overlays are added by a premium version of this product.)

This plugin requires WooCommerce. It also requires PHP Version 5.4.0 or above.

**Additional features available in the Premium version:**

* Add overlays on the product image in a layout
* Add background images to a product
* Use one or more text fields and drop down fields as well as the paragraph field
* Add Static text to a product along with variable fields
* Pre-populate and tweak the gravity forms in the product data
* Use attributes to choose css styles so that font, color, etc can be modified on a line by line basis
* Support and updates for 1 year

See the [premium features demonstration video](https://tazziedave.com/plugins/wysiwyg-custom-products-premium#Demonstration).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wysiwyg-custom-products` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Switch to the **Settings->Wysiwyg Customize** screen
1. (Optional) Specify your required font by editing the fonts.css file by clicking on the **Change font** link. See the [instruction video on font installation and selection](https://tazziedave.com/plugins/wysiwyg-custom-products#Choose-Fonts) for a detailed guide on how to do this.
1. Create the layouts desired for your customizable products
1. Modify customizable products using the new **Wysiwyg Customization** tab in the Product Data section.
    * Choose one of the layouts created in the previous step.
    * In the 'Catalog text' field type the text you'd like to see shown on the product on any product list pages. (If left blank then the product title is shown.)

See **Other Notes** tab for more information on creating layouts.

Note: For the purposes of this plugin a "customizable product" is one that has a either a textarea (paragraph) or text field on it. The first field will be the one that updates the image preview.

== Frequently Asked Questions ==

= What's with this having to edit the fonts.css file? =

Sorry about that, but to get version 1.0 out at some stage, I had to come up with the easiest way I could think of to allow for the users to specify the font to be shown on the products! Look for an early upgrade that'll make this much easier (I hope).

= Can I have different fonts for different layouts/products? =

Yes! It's a premium feature.

= Does it *really* require version 4.5.4 or higher? =

Good question. That's just the earliest version that the plugin has been tested on. If you're actually running an eCommerce store on an earlier version, you're welcome to try it, but you ought to think seriously about updating!

= What is the sound of one hand clapping? =

== Screenshots ==

1. First settings screen. A very boring layout with no image as a basic template.
2. Setting screen with image added and width and Y position adjusted. Shown with minimum font selected.
3. This is how a finished layout may look. Shown with maximum font selected.
4. This is two screen grabs in one. The left hand side is showing the **Wysiwyg Customization** product tab, with the 'Heart' layout selected. The right hand side shows how that product would appear in the catalog (the product is named Example Wysiwyg Product).
5. Same as above, but with specific text to be shown on catalog pages.
6. What the customer sees when placing the product order.
7. Two more images of the same product. When the color variation is changed, the image associated with that variation is updated behind the text that the customer has already typed.
8. The text overflow error messages as seen by the customer when they are typing their own message.

== Changelog ==

= 1.0 =
* Initial Release.

= 1.0.3 =
* Fix to make work with WordPress Versions earlier than 4.7.0

= 1.0.4 =
* Fix to make work with PHP Versions earlier than 5.6

= 1.0.5 =
* Prevent creation of layouts that will override plugin reserved settings
* Created cleaner upgrade/downgrade handling
* Added English UK translation
* Tested to work with 4.7.1

= 1.0.6 =
* Fix to ensure that admin path can be obtained. Bug when both http and https used.

= 1.0.7 =
* Removed Gravity Forms dependencies.
* Free version now allows for a single text field as well as a textarea
* Fix when custom product is not the first catalog item on a page

= 1.0.8 =
* Fixed background selection in Premium version
* Fixed alignment issues between admin and shop areas
* Minor bug fixes

= 1.1.0 =
* Modified settings panel to make it more user friendly and WordPress consistent
* Added ability in premium version to specify CSS attribute values to individual lines. Allows for setting fonts, colors, etc. on a line by line basis.
* Final fix of alignment issues between admin and shop areas

= 1.1.1 =
* Added ability to change colors on settings page so darker images can be more easily handled.

= 1.1.2 =
* Added setting option to allow for data to be kept when uninstalling
* Premium- Use default attribute value if no Wysiwyg attribute value set.

== Upgrade Notice ==
Layout data can now be saved when uninstalling to allow for easier upgrade.

== Creating Layouts - Instructions ==

Below are the step by step instructions for creating and maintaining layouts. There's also a [layout demonstration video](https://tazziedave.com/plugins/wysiwyg-custom-products#Maintain-Layouts) and a [detailed how-to guide](https://tazziedave.com/plugins/wcp-how-to/).

= Set Up Template =
 Use the **Layout product image** button to select a customizable product example picture. The Font size, Width, Y and X positions can be adjusted in the grid or by using the mouse. By default it is the Maximum font that is adjustable by the mouse (shown in light blue - the darker band is the minimum font) use the radio buttons to swap this around.

 The customer error messages shown below the format are able to be set on a per layout basis but you can set up the default messages here.  First message is when the text won't fit within the specified width at the minimum font size. The second one is when they try to have more than the maximum number of possible lines.

 Once you have set up a suitable template layout click the **Save** button.

= Copy to Your Own Layout =
 Once the template has been saved, type the name of your new layout in the **New Name** text box, then click **Copy**. Note that the **Delete** button is now available as there is more than one layout.

= Adding Available Lines =
If you want more lines to be available in a layout it is best to increase the **Maximum possible lines** one at a time and adjust the new format at each step.

= Formatting For Different Lines =
Once a layout has more than one line, you can choose the number of lines that you wish to format in the **Format for** drop down. When two or more lines are selected the grid has the **Make same** check boxes added to the bottom. When these are checked then any adjustment (in the grid or by using the mouse) will be applied to all of the lines in that format. If you wish to have different values for one or more lines, then uncheck the appropriate box. If a box is then rechecked the value from the last adjusted line, which has the highlighting around it, is copied to all of the other lines.

= Renaming Layout =
If you change your mind about the name of a layout, simply type the new name in the **New Name** text box, then click **Rename**. Note: This only affects the layout name. If the layout has already been linked to products the link will be broken as they will still refer to the old name. This can be useful as you can create a different layout with the same name and it'll be applied across all of the associated products. As a precaution against this happening accidentally, the **Copy** and **Rename** buttons are disabled if the **New Name** is the same as an existing name.

= And The Next Layout =
You can use any layout as the basis for the next layout when copying - you don't have to use the template.  So just use the layout that has the best formatting, the right image or the most appropriate error messages.

== For Developers ==
The plugin has a reasonable amount of filters and action hooks to allow for a fair degree of customization and extension.

= Filters =

###Source: frontend/class-frontend.php###
    apply_filters( 'wcp_frontend_shop_single', string $html, int $post_id, string $post_thumbnail_id, string|array $size, string $attr )
    apply_filters( 'wcp_frontend_shop_catalog', string $html, int $post_id, string $post_thumbnail_id, string|array $size, string $attr )
    These filter the generated product page and catalog images respectively. They hook into filter 'post_thumbnail_html'.

###Source: common/class-layout.php###
    apply_filters( 'wcp_layout_as_array', array $layout )
    apply_filters( 'wcp_layout_as_array_{$name}', array $layout )
    Filters the layout when the class is being output as an array for saving or being passed to settings page.
    Can intercept for all layouts or specific names of layouts.

    apply_filters( 'wcp_save_default_array', array $layout )
    apply_filters( 'wcp_save_default_array_{$name}', array $layout )
    Initial set up: saving the template layout.

    apply_filters( 'wcp_save_default_names', array $layoutNames )
    Initial set up: saving names of any default layouts.

    apply_filters( 'wcp_get_overflow_msg_{$messageName}', $message )
    Initial set up: filter the default error messages.

    apply_filters( 'wcp_before_line_implode', array $line )
    apply_filters( 'wcp_after_line_implode', string $line )
    apply_filters( 'wcp_compact_format', array $formats )
    Manipulate the formatting information being passed to the edit page.

###Source: admin/class-settings.php###
    apply_filters( 'wcp_settings_svg_image', string $html )
    Filter the image being displayed on the settings page

###Source: admin/class-ajax.php###
    apply_filters( 'wcp_ajax_after_fetch_layout', Layout $layout )
    Filter before returning a layout to a get_layout request.

    apply_filters( 'wcp_ajax_before_save_layout', array $layout )
    Filter before saving layout to database.

###Source: admin/class-products.php###
    apply_filters( 'wcp_custom_product_tags', array $tabs )
    Filter the additional tabs on the woocommerce product page.

    apply_filters( 'wcp_save_field_{$fieldName}', mixed $value )
    Filter before saving an entered field value


= Action Hooks =

###Source: common/class-layout.php###
    do_action( 'wcp_load_layout' )
    After layout is loaded.

###Source: admin/class-products.php###
    do_action( 'wcp_before_options_content' )
    do_action( 'wcp_after_options_content' )
    Hooks around the Wysiwyg Customize tab html

    do_action( 'wcp_before_product_data_save' );
    do_action( 'wcp_after_product_data_save' );
    Hooks when saving the Wysiwyg data for a product
