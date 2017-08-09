/**
 * Created by Dave on 28/09/16.
 *
 * @since   1.0.0
 * @updated 1.1.0
 */

/*global FontScalerArray */

//noinspection AssignmentResultUsedJS,JSUnusedLocalSymbols,JSLint
(function ( $wcp, $, undefined ) {
    "use strict";
    $wcp.fontScalerArray = new FontScalerArray($("#wcp_canvas_div"), "IXhljgy");


    /**
     * Reduces font size of text to fit maxWidth
     *
     * @param tSpan        {jQuery}   tspan for text setting purposes
     *
     * @since 1.0.0
     */
    function shrinkText( tSpan ) {
        var setFont       = parseInt( tSpan.css( "font-size" ), 10 );
        var minFont       = parseInt( tSpan.data( "min-font" ), 10 );
        var maxWidth      = parseInt( tSpan.data( "width" ), 10 );
        //noinspection JSUnresolvedFunction
        var measuredWidth = tSpan[ 0 ].getComputedTextLength();

        while ( (measuredWidth > maxWidth) && (setFont > minFont) ) {
            setFont--;
            setFontSize( tSpan, setFont );
            //noinspection JSUnresolvedFunction
            measuredWidth = tSpan[ 0 ].getComputedTextLength();
        }

        return setFont;
    }

    /**
     * Calculates the smallest font size across all tspans. This is then the max font to use
     *
     * @param tSpans        {jQuery}   tspans to be balanced
     *
     * @since 1.0.0
     */
    function setMaxFont( tSpans ) {
        var result           = shrinkText( $( tSpans[ 0 ] ) );
        var maxFont          = result;
        var AdjustmentNeeded = false;
        var i;

        for ( i = 1; i < tSpans.length; i++ ) {
            result = shrinkText( $( tSpans[ i ] ) );
            if ( maxFont !== result ) {
                AdjustmentNeeded = true;
                maxFont          = Math.min( maxFont, result );
            }
        }

        if ( AdjustmentNeeded ) {
            return maxFont;
        } else {
            return 0;
        }
    }

    /**
     * Sets the font size and y attributes of the the tspan.
     *
     * @param tSpan         {jQuery}    SVG tspan element being adjusted
     * @param desiredSize   numeric     Font size wanted
     *
     * @since   1.0.0
     * @updated 1.0.2
     */
    function setFontSize( tSpan, desiredSize ) {
        var minFont  = parseInt( tSpan.data( "min-font" ), 10 );
        var maxFont = parseInt( tSpan.data( "max-font" ), 10 );
        var fontSize = Math.max( Math.min( desiredSize, maxFont ), minFont ); // Make sure desired size doesn't bust
                                                                              // this spans font range

        tSpan.attr( "style", "font-size:" + fontSize + "px" );
    }
    /**
     * Adjust the Y value for all catalog product SVG tspans to simulate
     * the baseline 'middle' setting that I would have liked to use.
     *
     * @since   1.0.0
     * @updated 1.1.0
     */
    $wcp.fixCatalogProducts = function () {
        var catalogProducts = $( "div.wcp-catalog" );

        if ( 0 === catalogProducts.length ) {
            return; // Nothing to do
        }

        if (!$wcp.fontScalerArray) { // oops! Give up
            return;
        }

        catalogProducts.each( function () {
            var tSpans;
            var maxFont;

            tSpans  = $( this ).find( "tspan" );
            maxFont = setMaxFont( tSpans );
            tSpans.each( function () {
                var tSpan = $( this );
                var y;
                var fontSize;

                if ( tSpan.text() ) {
                    if ( maxFont ) { // If Tspans font sizes need to be balanced.
                        setFontSize( tSpan, maxFont );
                    }

                    if ( $wcp.fontAdjust ) {  //  Adjusts y for middle baseline
                        y        = parseInt( tSpan.attr( "y" ) );
                        fontSize = parseInt( tSpan.css( "font-size" ), 10 );
                        y += $wcp.fontScalerArray.getMiddleYOffset(this, fontSize);
                        tSpan.attr( "y", y );
                    }
                }
            } );
        } );
    };
}( window.$wcp = window.$wcp || {}, jQuery ));

/**
 * Loader function
 *
 * @since 1.0.0
 */
jQuery( document ).ready( function () {
    "use strict";
    window.$wcp.fixCatalogProducts();
} );
