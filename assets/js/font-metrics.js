/** This file contains a large extract from Mike "Pomax" Kamermans' fontmetrics library.
 * See the copyright message below and has the same license.
 *
 * It has been modified to only include those metrics required to enable simulation of the
 * SVG dominant-baseline, alignment-baseline 'middle' for unsupported browsers (I'm looking at
 * you, microsoft!)
 *
 * @since   1.0.0
 * @updated 1.1.0
 */

/**
 This library rewrites the Canvas2D "measureText" function
 so that it returns a more complete metrics object.
 This library is licensed under the MIT (Expat) license,
 the text for which is included below.

 ** -----------------------------------------------------------------------------

 CHANGELOG:

 2012-01-21 - Whitespace handling added by Joe Turner
 (https://github.com/oampo)
 2016-08-11 - Removal of unneeded metrics and above whitespace handling by Dave Hobart
 2017-02-05 - Refactored getCSSValue to expose this useful function by Dave Hobart

 ** -----------------------------------------------------------------------------

 Copyright (C) 2011 by Mike "Pomax" Kamermans

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in
 all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 THE SOFTWARE.
 **/
var isFontMetrics = true;

/**
 *  shortcut function for getting computed CSS values
 */
var getCSSValue = function (element, property) {
    "use strict";

    return document.defaultView.getComputedStyle(element, null).getPropertyValue(property);
};

(function () {

    "use strict";

    // if there is no getComputedStyle, this library won't work.
    if ( !document.defaultView.getComputedStyle ) {
        isFontMetrics = false;
        return;
    }

    // store the old text metrics function on the Canvas2D prototype
    //noinspection JSLint
    CanvasRenderingContext2D.prototype.measureTextWidth = CanvasRenderingContext2D.prototype.measureText;

    //noinspection FunctionTooLongJS,JSLint
    /**
     * The new text metrics function
     */
    CanvasRenderingContext2D.prototype.measureText = function ( textstring ) {
        var metrics    = this.measureTextWidth( textstring );
        var fontFamily = getCSSValue( this.canvas, "font-family" );
        var fontSize   = parseInt( getCSSValue( this.canvas, "font-size" ).replace( "px", "" ), 10 );

        metrics.fontsize = fontSize;

        // for text lead values, we meaure a multiline text container.
        var leadDiv            = document.createElement( "div" );
        leadDiv.style.position = "absolute";
        leadDiv.style.opacity  = 0;
        leadDiv.style.font     = this.font;
        leadDiv.innerHTML      = textstring + "<br/>" + textstring;
        document.body.appendChild( leadDiv );

        // make some initial guess at the text leading (using the standard TeX ratio)
        metrics.leading = 1.2 * fontSize;

        // then we try to get the real value from the browser
        var leadDivHeight = getCSSValue( leadDiv, "height" );
        leadDivHeight     = leadDivHeight.replace( "px", "" );
        if ( leadDivHeight >= fontSize * 2 ) {
            metrics.leading = (leadDivHeight / 2) | 0;
        }
//        leadDiv.innerHTML = ''; // Necessary for ie

        document.body.removeChild( leadDiv );

        // Have characters, so measure the text
        var canvas              = document.createElement( "canvas" );
        var padding             = 100;
        canvas.width            = metrics.width + padding;
        canvas.height           = 3 * fontSize;
        canvas.style.opacity    = 1;
        canvas.style.fontFamily = fontFamily;
        canvas.style.fontSize   = fontSize;
        var ctx                 = canvas.getContext( "2d" );
        ctx.font                = fontSize + "px " + fontFamily;

        var w        = canvas.width;
        var h        = canvas.height;
        var baseline = h / 2;

        // Set all canvas pixeldata values to 255, with all the content
        // data being 0. This lets us scan for data[i] != 255.
        ctx.fillStyle = "white";
        ctx.fillRect( -1, -1, w + 2, h + 2 );
        ctx.fillStyle = "black";
        ctx.fillText( textstring, padding / 2, baseline );
        var pixelData = ctx.getImageData( 0, 0, w, h ).data;

        // canvas pixel data is w*4 by h*4, because R, G, B and A are separate,
        // consecutive values in the array, rather than stored as 32 bit ints.
        var i   = 0;
        var w4  = w * 4;
        var len = pixelData.length;

        // Finding the ascent uses a normal, forward scanline
        while ( ++i < len && 255 === pixelData[ i ] ) {
        }
        var ascent = (i / w4) | 0;

        // Finding the descent uses a reverse scanline
        i = len - 1;
        while ( 0 < --i && 255 === pixelData[ i ] ) {
        }
        var descent = (i / w4) | 0;

        // set font metrics
        metrics.ascent  = (baseline - ascent);
        metrics.descent = (descent - baseline);
        metrics.height  = descent - ascent;
        canvas          = null; // Allow for garbage heap clearing

        return metrics;
    };

}());
