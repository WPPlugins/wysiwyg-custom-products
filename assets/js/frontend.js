/**
 * Created by Dave on 2/08/16.
 *
 *
 * @since   1.0.0
 * @updated 1.1.2
 */

/* JSHint/JSLint set up */
// /*global attrchange */ // declared in attrchange.js

//noinspection JSUnusedLocalSymbols,AssignmentResultUsedJS,JSLint
(function ( $wcp, $, undefined ) {
    "use strict";

    var currentLineCount = 0;
    var formats          = {};
    var minLineCount     = 0;
    var maxLineCount     = 0;
    var lostText         = 0;

    var message = [];
    var textOperations = "change keyup paste cut input";
    var selectOperations = "change";

    //noinspection LocalVariableNamingConventionJS
    var TOO_LONG = 1;    // Would like to be const but avoiding ECMA script 6 for the moment
    //noinspection LocalVariableNamingConventionJS
    var TOO_MANY_LINES = 2;

    /**
     * Sets the font size and y attributes of the the tspan. Adjusts y for middle baseline
     *
     * @param tSpan         {jQuery}    SVG tspan element being adjusted
     * @param desiredSize   numeric     Font size wanted
     *
     * @since 1.0.0
     */
    function setFontSize( tSpan, desiredSize ) {
        var y        = parseInt( tSpan.data( "nominal-y" ), 10 );
        var minFont  = parseInt( tSpan.data( "min-font" ), 10 );
        var maxFont  = parseInt( tSpan.data( "max-font" ), 10 );
        var fontSize = Math.max( Math.min( desiredSize, maxFont ), minFont ); // Make sure desired size doesn't bust
                                                                              // this spans font range
        tSpan.attr( "style", "font-size:" + fontSize + "px" );
        if ($wcp.fontScalerArray) {
            tSpan.attr("y", y + $wcp.fontScalerArray.getMiddleYOffset(tSpan, fontSize));
        }
    }

    /**
     * Initial run over tspans to get baseline modified
     *
     * @since   1.0.0
     * @updated 1.0.7
     */
    function initialiseTspans() {
        var text = $( $( "div#front_svg" ).find( ".svg-text" ).first() );  // Avoid catalog products - just want main
        text.find( "tspan" ).each( function () {
            var tspan = $( this );
            var size  = parseInt( tspan.css( "font-size" ), 10 );
            setFontSize( tspan, size );
        } );
    }

    /**
     * Reduces font size and/or amount of text to fit maxWidth
     *
     * @param el           tspan      as DOM element. used for measurement
     * @param tSpan        {jQuery}   version of same tspan for text setting purposes
     * @param width        numeric    Currently measured width
     * @param maxWidth     numeric    Maximum width allowed
     * @param currentFont  numeric    Size of font currently being used
     * @param minFont      numeric    Minimum size of font for this tspan
     * @param newText      string     Text desired for the tspan
     *
     * @since 1.0.0
     */
    function shrinkText( el, tSpan, width, maxWidth, currentFont, minFont, newText ) {
        var setFont       = currentFont;
        var measuredWidth = width;
        var setText       = newText;

//        Try just reducing font size
        while ( (measuredWidth > maxWidth) && (setFont > minFont) ) {
            setFont--;
            setFontSize( tSpan, setFont );
            //noinspection JSUnresolvedFunction
            measuredWidth = el.getComputedTextLength();
        }

//        At min font size and it doesn't fit, shorten the text
        while ( measuredWidth > maxWidth ) {
            lostText |= TOO_LONG;
            setText = setText.substring( 0, setText.length - 1 );
            tSpan.text( setText );
            //noinspection JSUnresolvedFunction
            measuredWidth = el.getComputedTextLength();
        }
    }

    /**
     * Sees if text can be enlarged after modification to other tspans
     *
     * @param el           tspan      as DOM element. used for measurement
     * @param tSpan        {jQuery}   version of same tspan for text setting purposes
     * @param width        numeric    Currently measured width
     * @param maxWidth     numeric    Maximum width allowed
     * @param currentFont  numeric    Size of font currently being used
     * @param maxFont      numeric    Maximum size of font for this tspan
     *
     * @since 1.0.0
     */
    function maybeGrowText( el, tSpan, width, maxWidth, currentFont, maxFont ) {
        var setFont       = currentFont;
        var measuredWidth = width;

//        Try growing font size
        while ( (measuredWidth < maxWidth) && (setFont < maxFont) ) {
            setFont++;
            setFontSize( tSpan, setFont );
            //noinspection JSUnresolvedFunction
            measuredWidth = el.getComputedTextLength();
        }

//        Check for overflow and reduce again if necessary
        if ( measuredWidth > maxWidth ) {
            setFont--;
            setFontSize( tSpan, setFont );
        }
    }

    /**
     * Sets the named tspan text and adjusts size as required
     *
     * @param id         string  tspan id
     * @param newText    string  required text
     * @param modified   bool    forces modified status even if text hasn't changed
     *
     * @since 1.0.0
     */
    function setTspan( id, newText, modified ) {
        var el = document.getElementById( id );
        var tSpan;
        var currentText;
        var width;
        var maxWidth;
        var minFont;
        var maxFont;
        var currentFont;

        if ( !el ) {  // Trying to set more lines than exist
            lostText |= TOO_MANY_LINES;
            return;
        }

        tSpan       = $( el );
        currentText = tSpan.text();
        minFont     = parseInt( tSpan.data( "min-font" ), 10 );
        maxFont     = parseInt( tSpan.data( "max-font" ), 10 );
        maxWidth    = parseInt( tSpan.data( "width" ), 10 );
        currentFont = parseInt( tSpan.css( "font-size" ), 10 );

        if ( !modified && (newText === currentText) ) { // No change to this tspan just see if it can grow again
            //noinspection JSUnresolvedFunction
            width = el.getComputedTextLength();
            maybeGrowText( el, tSpan, width, maxWidth, currentFont, maxFont ); //
            return;
        }

        tSpan.text( newText );

        if ( "" === newText ) {
            setFontSize( tSpan, maxFont );
        } else {
            //noinspection JSUnresolvedFunction
            width = el.getComputedTextLength();
            if ( width > maxWidth ) {
                shrinkText( el, tSpan, width, maxWidth, currentFont, minFont, newText );
            } else if ( (width < maxWidth) && (currentFont < maxFont) ) {
                maybeGrowText( el, tSpan, width, maxWidth, currentFont, maxFont );
            }
        }
    }

    /**
     * Adjusts all tspans to have the font size of the smallest tspan. setFontSize checks range of desired size.
     *
     * @since    1.0.0
     */
    function balanceTSpans() {
        var i;
        var minFont;
        var maxFont;
        var spanFont;

        spanFont = parseInt( $( "#tspan0" ).css( "font-size" ), 10 );
        minFont  = spanFont;
        maxFont  = spanFont;

        for ( i = 1; i < currentLineCount; i++ ) {
            spanFont = parseInt( $( "#tspan" + i ).css( "font-size" ), 10 );
            minFont  = Math.min( minFont, spanFont );
            maxFont  = Math.max( maxFont, spanFont );
        }

        if ( minFont !== maxFont ) {
            for ( i = 0; i < currentLineCount; i++ ) {
                setFontSize( $( "#tspan" + i ), minFont );
            }
        }
    }

    /**
     * Hides or shows the lost text messages based on the bit values in lostText
     *
     * @since 1.0.0
     */
    function maybeShowLostText() {
        var lostTextMsg  = $( ".wcp-too-long" );
        var tooManyLines = $( "#wcp_too_many_lines" );

        if ( lostTextMsg.length ) {
            //noinspection JSBitwiseOperatorUsage
            lostTextMsg.toggleClass( "wcp-hidden", !(lostText & TOO_LONG) );
            //noinspection JSBitwiseOperatorUsage
            tooManyLines.toggleClass( "wcp-hidden", !(lostText & TOO_MANY_LINES) );
        }
    }

    /**
     * Updates text in a single tspan
     *
     * @param el  {jQuery}  Source of text to be set
     * @param idx int       Index of associated tspan
     *
     * @since   1.0.0
     */

    function doTspanUpdate( el, idx ) {
        var text       = el.val();
        lostText       = 0;
        message[ idx ] = text;
        setTspan( "tspan" + idx, text, false );
        maybeShowLostText();
    }

    /**
     * Called when a DOM element with an associated tspan is changed
     *
     * @param event
     *
     * @since 1.0.0
     */
    function tspanUpdate( event ) {
        doTspanUpdate( $( event.currentTarget ), event.data.index );
    }


    /**
     * Checks to see if the number of lines being edited is changed. Updates formatting accordingly.
     *
     * @param   targetLineCount  int  Number of lines in the message being displayed
     *
     * @returns boolean               Indicates whether number of lines have changed. True if yes.
     *
     * @since 1.0.0
     */
    function maybeChangeLineCount( targetLineCount ) {
        var newLineCount = targetLineCount;
        var i;
        var tspan;
        var format;

        // Check value and make sure it's in range. Flag error if too many lines
        if ( newLineCount < minLineCount ) {
            newLineCount = minLineCount;
        }

        if ( newLineCount > maxLineCount ) {
            lostText |= TOO_MANY_LINES;
            newLineCount = maxLineCount;
        }

        // Line count has changed, reformat all tspans
        if ( newLineCount !== currentLineCount ) {
            currentLineCount = newLineCount;
            format           = formats[ currentLineCount ];
            for ( i = 0; i < currentLineCount; i++ ) {
                tspan = $( "#tspan" + i );
                tspan.attr( "x", format[ i ].x );
                tspan.attr( "y", format[ i ].y );
                tspan.data( "nominal-y", format[ i ].y );
                switch ( format[ i ].align ) {
                    case "L":
                        tspan.attr( "text-anchor", "start" );
                        break;
                    case "R":
                        tspan.attr( "text-anchor", "end" );
                        break;
                    default:
                        tspan.attr( "text-anchor", "middle" );
                }

                setFontSize( tspan, format[ i ].maxFont );
                tspan.data( "min-font", format[ i ].minFont );
                tspan.data( "max-font", format[ i ].maxFont );
                tspan.data( "width", format[ i ].width );
            }
            return true;
        }
        return false;
    }


    /**
     * Displays the lines of text in the associated tspans
     *
     * @param lines array of strings|false
     *
     * @since   1.0.0
     * @updated 1.0.7
     */
    function displayMessage( lines ) {
        var i;
        var formatModified;

        lostText          = 0;
            formatModified = maybeChangeLineCount( lines.length );

        for ( i = 0; i < maxLineCount; i++ ) {
            if ( i < currentLineCount ) {
                setTspan( "tspan" + i, lines[ i ], formatModified ); // Force rewrite if format has changed
            } else { // Clear unused tspans
                setTspan( "tspan" + i, "", false );
            }
        }

        balanceTSpans();     // Set all spans to smallest appropriate size
        maybeShowLostText(); // Show any user error messages
    }

    /**
     * Displays the text from a text area. Split into an array of lines.
     *
     * @param el  {jQuery}   Text area
     *
     * @since 1.0.0
     */
    function doTspanMultiUpdate( el ) {
        var message = el.val();
        displayMessage( message.split( /\r\n|\n|\r/g ) );  // LineBreaks -> array of lines
    }

    /**
     * Called when content of text area is changed
     *
     * @param event
     *
     * @since 1.0.0
     */
    function tspanMultiUpdate( event ) {
        doTspanMultiUpdate( $( event.currentTarget ) );
    }


    function updateImage( event ) {
        if ( "src" === event.attributeName ) {
            $( "#svg_image" ).attr( "xlink:href", event.newValue );
        }
    }
    /**
     * Parses a format line into it's component parts
     *
     * @param   line   string  Parameters separated by ","
     *
     * @returns Object
     *
     * @since 1.0.0
     */
    function formatLine( line ) {
        var result = {};
        var parts;

        parts          = line.split( "," );
        result.y       = parseInt( parts[ 0 ], 10 );
        result.x       = parseInt( parts[ 1 ], 10 );
        result.width   = parseInt( parts[ 2 ], 10 );
        result.align   = parts[ 3 ];
        result.minFont = parseInt( parts[ 4 ], 10 );
        result.maxFont = parseInt( parts[ 5 ], 10 );

        return result;
    }

    /**
     * Splits a .l ("LinesN") format into an array of the individual (parsed) lines.
     *
     * @param    format   string  Format lines separated by "|"
     * @returns  Array
     *
     * @since 1.0.0
     */
    function parseFormat( format ) {
        var result = [];
        var lines  = format.split( "|" );
        var i;
        var len    = lines.length;

        for ( i = 0; i < len; i++ ) {
            result.push( formatLine( lines[ i ] ) );
        }
        return result;
    }

    /**
     * Breaks the data-formats JSON into the array of formats
     *
     * @param el   JQuery element
     *
     * @since 1.0.0
     */
    function setFormats( el ) {
        var array = el.data( "formats" );
        var len   = array.length;
        var format;
        var i;

        if ( len ) {
            minLineCount = array[ 0 ].l;
            for ( i = 0; i < len; i++ ) {
                format              = array[ i ];
                formats[ format.l ] = parseFormat( format.f );
                maxLineCount        = format.l;
            }
        }
    }

    function setParagraph(el) {
        if (0 === el.length) {
            return false;
        }

        el.each(function () {
            var self = $(this);
            if (!self.is("textarea")) {
                self = $(self.find("textarea").first());
            }
            self.on(textOperations, tspanMultiUpdate);
            doTspanMultiUpdate(self);
        } );


        return el.last();
    }

    function setTextfield(el) {
        var input;
        if (0 === el.length) {
            return false;
        }

         input = $(el.first());
         if (!input.is("input[type=text]")) {
            input = $(input.find("input[type=text]").first());
        }
         input.on(textOperations, { index : 0 }, tspanUpdate );
         doTspanUpdate( input, 0 );
         return el.first();
    }


    //noinspection FunctionTooLongJS
    /**
     * Sets up all of the javascript actions and any other initialisation required
     *
     * @since   1.0.0
     * @updated 1.1.2
     */
    $wcp.initialise = function () {
        var cartForm = $("form.cart");
        var last;
        var multiline;
        var i;
        var inputInitialiser;
        var inputSelection = [
            {context: null, selector: ".wcp-paragraph", initialiser: setParagraph, multiline: true},
            {context: null, selector: ".wcp-textarea", initialiser: setParagraph, multiline: true},
            {context: null, selector: ".wcp-single-line", initialiser: setTextfield, multiline: false},
            {context: null, selector: ".wcp-textfield", initialiser: setTextfield, multiline: false},
            {context: cartForm, selector: "textarea", initialiser: setParagraph, multiline: true},
            {context: cartForm, selector: "input[type=text]", initialiser: setTextfield, multiline: false}
        ];

        setFormats($("#svg_image_text"));



        for (i = 0; (i < inputSelection.length) && !last; i++) {
            inputInitialiser = inputSelection[i];
            multiline = inputInitialiser.multiline;
            if (inputInitialiser.context) {
                last = inputInitialiser.initialiser(inputInitialiser.context.find(inputInitialiser.selector));
            } else {
                last = inputInitialiser.initialiser($(inputInitialiser.selector));
            }
        }

        if (last) {  // Move message paragraphs to the appropriate location
            last = last.closest("div");
            $( "p#wcp_too_many_lines" ).appendTo( last );
            if ( multiline ) {
                $( "p#wcp_multiline" ).addClass( "wcp-too-long" ).appendTo( last );
            } else {
                $( "p#wcp_single" ).addClass( "wcp-too-long" ).appendTo( last );
            }
        }

        if (minLineCount === maxLineCount) { // Fixed format, set everything up for the spans
            maybeChangeLineCount(minLineCount);
        }
        // Following productImg select taken (except for initial form select) from add-to-cart-variation.js in WooCommerce/assets/js/frontend
        var form       = $( "form.variations_form" );
        var product    = form.closest( ".product" );
        var productImg = product.find( "div.images img:eq(0)" );
        //noinspection JSUnresolvedFunction
        $( productImg ).attrchange( {
                                        trackValues : true, // enables tracking old and new values
                                        callback    : updateImage
                                    } );

        initialiseTspans();
    };

}( window.$wcp = window.$wcp || {}, jQuery ));

/**
 * Loader function
 *
 * @since 1.0.0
 */
jQuery( document ).ready( function () {
    "use strict";
    window.$wcp.initialise();
} );
