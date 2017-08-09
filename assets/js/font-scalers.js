/**
 * Font scaling objects.
 * FontScaler       refactored from  font-metrics
 * FontScalerArray  Creates and reuses FontScalers based on font family name, using a single canvas
 *
 * @since   1.1.0
 */

/* JSHint/JSLint set up */
/*global getCSSValue    */  // in font-metrics.js
/*global isFontMetrics  */  // in font-metrics.js


/**
 * Utility object to provide font metric calculations
 *
 * @param canvas    canvas in the DOM context where the font is to be scaled
 * @param testText  string  Text to be used for calculation purposes. 'Xy' is a useful short string
 *                          that gives reasonable ascender and descender measurements
 * @constructor
 *
 * @since   1.0.0
 * @updated 1.0.1
 */
function FontScaler(canvas, testText) {
    "use strict";
    this.canvas = canvas;
    this.setTestText(testText);
}

/**
 * Does the actual measurements and sets the sizing ratio's using the fontmetrics library above
 *
 * @since   1.0.0
 * @updated 1.0.8
 */
FontScaler.prototype.refresh = function () {
    "use strict";
    var context;
    var fontSize = parseInt(getCSSValue(this.canvas, "font-size").replace("px", ""), 10);

    if (!isFontMetrics) { // Take a guess!
        this.yOffsetRatio = 0.25;
        this.sizeRatio = 1;
        return;
    }
    this.canvas.width = fontSize * this.testText.length * 1.5;
    this.canvas.height = 3 * fontSize;

    context = this.canvas.getContext("2d");
    this.metrics = context.measureText(this.testText);

    this.yOffsetRatio = (this.metrics.ascent - this.metrics.descent) / (this.metrics.fontsize * 2);
    this.sizeRatio = this.metrics.height / this.metrics.fontsize;
    this.canvas.width = 0;
    this.canvas.height = 0;

};

/**
 * Modify the text to be used for measurements
 *
 * @param testText  string - see constructor
 *
 * @since 1.0.0
 */
FontScaler.prototype.setTestText = function (testText) {
    "use strict";
    this.testText = testText;
    this.refresh();
};

/**
 * Returns the value that Y has to be modified to simulate the 'middle' baseline for a given font size
 *
 * @param fontSize  numeric
 * @returns number
 *
 * @since 1.0.0
 */
FontScaler.prototype.getMiddleYOffset = function (fontSize) {
    "use strict";
    return fontSize * this.yOffsetRatio;
};

/**
 * Returns the modified height of a font based on the measured metrics for a given font size
 *
 * @param   fontSize numeric
 * @returns number
 *
 * @since 1.0.0
 */
FontScaler.prototype.getModifiedHeight = function (fontSize) {
    "use strict";
    return fontSize * this.sizeRatio;
};


/**
 * Utility object provide for font scaling for different fonts
 *
 * @param div       {jQuery} Div containing the canvas to do the measuring
 * @param testText  string   Text to be used for calculation purposes. 'Xy' is a useful short string
 *                           that gives reasonable ascender and descender measurements
 * @constructor
 *
 * @since   1.1.0
 */
function FontScalerArray(div, testText) {
    "use strict";
    this.div = div;
    this.testText = testText;
    this.fontScalers = {};
}

/**
 * Creates a font scaler for a particular font family
 *
 * @param fontFamily  string   Font family name needing a font scaler
 *
 * @since   1.1.0
 */
FontScalerArray.prototype.addFontScaler = function (fontFamily) {
    "use strict";
    this.div.css("font-family", fontFamily);
    this.fontScalers[fontFamily] = new FontScaler(this.div.find("canvas")[0], "IXhljgy");
};
/**
 * Returns a suitable scaler for an element
 *
 * @param el        jQuery
 * @returns FontScaler
 *
 * @since 1.1.0
 */
FontScalerArray.prototype.getFontScaler = function (el) {
    "use strict";
    var fontFamily = el.css("font-family");

    if (undefined === this.fontScalers[fontFamily]) {
        this.addFontScaler(fontFamily);
    }
    return this.fontScalers[fontFamily];
};
/**
 * Returns the value that Y has to be modified to simulate the 'middle' baseline for a given font size for an element
 *
 * @param el        jQuery
 * @param fontSize  numeric
 * @returns number
 *
 * @since 1.1.0
 */
FontScalerArray.prototype.getMiddleYOffset = function (el, fontSize) {
    "use strict";
    var fontFamily = el.css("font-family");

    if (undefined === this.fontScalers[fontFamily]) {
        this.addFontScaler(fontFamily);
    }
    return this.fontScalers[fontFamily].getMiddleYOffset(fontSize);
};
/**
 * Returns the modified height of a font based on the measured metrics for a given font size for an element
 *
 * @param el        jQuery
 * @param fontSize  numeric
 * @returns number
 *
 * @since 1.1.0
 */
FontScalerArray.prototype.getModifiedHeight = function (el, fontSize) {
    "use strict";
    var fontFamily = el.css("font-family");

    if (undefined === this.fontScalers[fontFamily]) {
        this.addFontScaler(fontFamily);
    }
    return this.fontScalers[fontFamily].getModifiedHeight(fontSize);
};

