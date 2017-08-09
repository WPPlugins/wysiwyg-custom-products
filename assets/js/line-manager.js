/**
 * Created by Dave on 6/10/16.
 *
 * Line manager is used to handle all of the visuals and settings for a single line within a layout format
 *
 * @since   1.0.0
 * @updated 1.1.0
 */

/* JSHint/JSLint set up */
/*global FontScalerArray */   // declared in font-scalars.js


//noinspection JSUnusedLocalSymbols,JSLint,AssignmentResultUsedJS
(function ( $wcpA, $, undefined ) {
    "use strict";

    $wcpA.fontScalerArray = new FontScalerArray($("#wcp_canvas_div"), "IXhljgy");

    $wcpA.actions = {  // First 6 correspond to columns in table. Last 3 are for mouse manipulation
        Y         : 0,
        X         : 1,
        Align     : 2,
        Width     : 3,
        MinFont   : 4,
        MaxFont   : 5,
        Move: 20,
        Resize: 21,
        ResizeEnd: 22
    };


    /**
     * Initialisation and association of DOM elements as required
     *
     * @param lineIndex  Which line is this line
     * @param row        {jQuery} object associated with the table row
     * @constructor
     *
     * @since 1.0.0
     */
    $wcpA.LineManager = function ( lineIndex, row ) {
        var cells = row.children( "td" );

        this.Index       = lineIndex;
        this.tSpan       = $( "#tspan" + this.Index );
        this.tSpanEl     = this.tSpan[ 0 ];
        this.Rect        = $( "#rect" + this.Index );
        this.NonSizeRect = $( "#nonSizeRect" + this.Index );
        this.Row         = row;
        this.yCell       = $( cells[ $wcpA.actions.Y ].firstChild );
        this.xCell       = $( cells[ $wcpA.actions.X ].firstChild );
        this.alignCell   = $( cells[ $wcpA.actions.Align ].firstChild );
        this.widthCell   = $( cells[ $wcpA.actions.Width ].firstChild );
        this.minFontCell = $( cells[ $wcpA.actions.MinFont ].firstChild );
        this.maxFontCell = $( cells[ $wcpA.actions.MaxFont ].firstChild );
        this.setSizing( "MaxFont" );
        this.resizing = false;
        this.Active   = false;
    };


    /**
     * Called when a layout is loaded or the number of lines being formatted is changed
     *
     * Checks to see if current LineManager is required (this.Active)
     * If the line is active, reads the appropriate format values from:
     * Layout[formats[lines{nbrLines}[this line number]
     *
     * @param Layout      Current layout
     * @param nbrLines    Current number of lines being formatted
     *
     * @since   1.0.0
     * @updated 1.1.0
     */
    $wcpA.LineManager.prototype.setLine = function (Layout, nbrLines) {
        this.setActive( this.Index < nbrLines );

        if ( this.Active ) {
            //noinspection JSUnresolvedVariable
            this.Line = Layout.Formats[ "Lines" + nbrLines ][ this.Index ];
            this.setX( this.Line.X );
            this.setY( this.Line.Y );
            this.setAlign( this.Line.Align );
            this.setWidth( this.Line.Width );
            this.setMinFont( this.Line.MinFont );
            this.setMaxFont( this.Line.MaxFont );
            this.drawVisuals();
        }
    };
    /**
     * Sets whether mouse operations are on the min font or the max font
     *
     * @param sizingFont string  value obtained from the radio buttons
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setSizing = function ( sizingFont ) {
        this.sizing    = sizingFont;
        this.nonSizing = "MaxFont" === sizingFont ? "MinFont" : "MaxFont";
        this.drawVisuals();
    };

    /**
     * Sets the text font size and then works out available characters based on width
     *
     * @param size    int   font size
     * @param width   int   width of tspan
     *
     * @since   1.0.0
     * @updated 1.1.0
     */
    $wcpA.LineManager.prototype.setFontSize = function ( size, width ) {
        var text       = "X";
        var textExtend = "yX";
        var char       = "";
        var i          = 0;

        // Pad with XyX...
        this.tSpan.attr("style", "font-size:" + size + "px");
        //noinspection JSUnresolvedFunction
        do {
            text += char;
            char = textExtend[ i % 2 ];
            //noinspection JSLint
            i++;

            this.tSpan.text( text + char );
        } while ( this.tSpanEl.getComputedTextLength() < width );

        // Pad with i
        char = "";
        //noinspection JSUnresolvedFunction
        do {
            text += char;
            char = "i";
            this.tSpan.text( text + char );
        } while ( this.tSpanEl.getComputedTextLength() < width );
        this.tSpan.text( text );
    };
    /**
     * Sets the size of an SVG Rect
     *
     * @param rect    {jQuery}  // SVG rect
     * @param x       numeric
     * @param y       numeric
     * @param width   numeric
     * @param height  numeric
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setRectSize = function ( rect, x, y, width, height ) {
        rect.attr( "x", x );
        rect.attr( "y", y );
        rect.attr( "width", width );
        rect.attr( "height", height );

    };
    /**
     * Calculates visual sizes for current line and gets text and rectangles repainted
     *
     * @since   1.0.0
     * @updated 1.1.0
     */
    $wcpA.LineManager.prototype.drawVisuals = function () {
        var fontScaler;
        var x;
        var y;
        var width;
        var height;

        if ( !this.Active ) {
            return;
        }

        fontScaler = $wcpA.fontScalerArray.getFontScaler(this.tSpan);
        width  = this.Line.Width;
        height = this.Line[ this.sizing ];

        // Set up text element
        this.tSpan.attr( "x", this.Line.X );
        this.tSpan.attr("y", this.Line.Y + fontScaler.getMiddleYOffset(height));
        this.setFontSize( height, width );

        switch ( this.Line.Align ) {
            case "L":
                this.tSpan.attr( "text-anchor", "start" );
                x = this.Line.X;  // Adjust X for rectangles
                break;
            case "R":
                this.tSpan.attr( "text-anchor", "end" );
                x = this.Line.X - width;
                break;
            default:
                this.tSpan.attr( "text-anchor", "middle" );
                x = this.Line.X - (width / 2);
        }

        // Set up rectangles
        height = fontScaler.getModifiedHeight(height);
        y      = this.Line.Y - (height / 2);
        this.setRectSize( this.Rect, x, y, width, height );

        height = fontScaler.getModifiedHeight(this.Line[this.nonSizing]);
        y      = this.Line.Y - (height / 2);
        this.setRectSize( this.NonSizeRect, x, y, width, height );
    };
    /**
     * Sets active state. Hides or shows appropriate DOM elements
     *
     * @param active
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setActive = function ( active ) {
        var inactive = !active;

        this.Active = active;
        this.tSpan.toggleClass( "hidden", inactive );
        this.Rect.toggleClass( "hidden", inactive );
        this.NonSizeRect.toggleClass( "hidden", inactive );
        this.Row.toggleClass( "hidden", inactive );
    };
    /**
     * Makes sure that a value is within the acceptable range, as set for the number input fields.
     *
     * @param value        number
     * @param checkField   numeric input field with min and max attributes set
     * @returns number
     *
     * @since 1.0.0
     */
    function limitRange( value, checkField ) {
        var minValue = parseInt( checkField.attr( "min" ), 10 );
        var maxValue = parseInt( checkField.attr( "max" ), 10 );
        var limitedValue;

        limitedValue = Math.min( value, maxValue );
        limitedValue = Math.max( limitedValue, minValue );

        return limitedValue;
    }

    /**
     * Alters the Y (vertical) value for the current line
     *
     * For X, Y and Height during a mouse operation, this.resizing is set and float values are allowed for
     * smooth mouse operation. After the mouse operation, the values are rounded back down to
     * integers (in doResizeEnd)
     *
     * @param value  numeric
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setY = function ( value ) {
        var rangedValue = limitRange( value, this.yCell );
        var iValue      = Math.floor( rangedValue );
        this.Line.Y     = this.resizing ? rangedValue : iValue;
        this.yCell.val( iValue );
    };
    /**
     * Alters the X (horizontal) value for the current line
     *
     * @param value
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setX = function ( value ) {
        var rangedValue = limitRange( value, this.xCell );
        var iValue      = Math.floor( rangedValue );
        this.Line.X     = this.resizing ? rangedValue : iValue;
        this.xCell.val( iValue );
    };
    /**
     * Alters the alignment of the current line. X is also modified as necessary.
     *
     * @param value
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setAlign = function ( value ) {
        var width = this.Line.Width;
        var initialOffset;
        var newOffset;
        var deltaX;

        function getTspanOffset( align ) {
            switch ( align ) {
                case "L":
                    return 0;
                case "R":
                    return width;
                default:
                    return width / 2;
            }
        }

        this.alignCell.val( value );
        if ( value === this.Line.Align ) {
            return;
        }

        initialOffset = getTspanOffset( this.Line.Align );
        newOffset     = getTspanOffset( value );
        deltaX        = newOffset - initialOffset;
        this.setX( this.Line.X + deltaX );
        this.Line.Align = value;
    };

    /**
     * Alters the Width value for the current line
     *
     * @param value
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setWidth = function ( value ) {
        var rangedValue = limitRange( value, this.xCell );
        var iValue      = Math.floor( rangedValue );
        this.Line.Width = this.resizing ? rangedValue : iValue;
        this.widthCell.val( iValue );
    };
    /**
     * Alters the 'Height' value for the current line. This corresponds to the currently chosen sizing font
     *
     * @param value
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setHeight = function ( value ) {
        if ( "MinFont" === this.sizing ) {
            this.setMinFont( value );
        } else {
            this.setMaxFont( value );
        }
    };
    /**
     * Sets the minimum font size. Can't be more than max font size.
     *
     * @param value  numeric
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setMinFont = function ( value ) {
        var iValue;
        var maxValue = limitRange( value, this.minFontCell );

        if ( !this.resizing ) { // Prevent value going larger than MaxFont
            maxValue = Math.min( value, this.Line.MaxFont );
        }

        iValue            = Math.floor( maxValue );
        this.Line.MinFont = this.resizing ? maxValue : iValue; // Snap back after resizing
        this.minFontCell.val( iValue ); // Only show whole numbers
    };
    /**
     * Sets the maximum font size. Can't be less than min font size.
     *
     * @param value  numeric
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setMaxFont = function ( value ) {
        var iValue;
        var minValue = limitRange( value, this.maxFontCell );

        if ( !this.resizing ) {  // Prevent value going smaller than MinFont
            minValue = Math.max( value, this.Line.MinFont );
        }

        iValue            = Math.floor( minValue );
        this.Line.MaxFont = this.resizing ? minValue : iValue; // Snap back after resizing
        this.maxFontCell.val( iValue ); // Only show whole numbers
    };
    /**
     * Obtains the appropriate value from the line Manager
     *
     * @param action One of the actions enumerated in line-manager.js
     *
     * @returns int
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.getVal = function ( action ) {
        switch ( action ) {
            case $wcpA.actions.Y:
                return this.Line.Y;
            case $wcpA.actions.X:
                return this.Line.X;
            case $wcpA.actions.Align:
                return this.Line.Align;
            case $wcpA.actions.Width:
                return this.Line.Width;
            case $wcpA.actions.MinFont:
                return this.Line.MinFont;
            case $wcpA.actions.MaxFont:
                return this.Line.MaxFont;
            default:
                return 0;
        }
    };

    /**
     * Mouse is being used to change X and/or Y.
     *
     * All lines get called for all mouse events. They only act if the current line is the source of
     * the action. Or the checkboxes indicate that all lines should share a value.
     *
     * @param event        Mouse event     dx and dy contains movement information
     * @param keepSame     Array of bool   Parameter kept the same indicators
     * @param sourceLine   bool            This line is the source of the mouse event
     *
     * @since   1.0.0
     * @updated 1.1.0
     */
    $wcpA.LineManager.prototype.doMove = function ( event, keepSame, sourceLine ) {
        this.resizing = true;

        if (sourceLine || keepSame[$wcpA.actions.X]) {  // Do X move if this is us, or they're keeping sync
            this.setX( this.Line.X + event.dx );
        }
        if (sourceLine || keepSame[$wcpA.actions.Y]) { // Do Y move if this is us, or they're keeping sync
            this.setY( this.Line.Y + event.dy );
        }
    };

    /**
     * Mouse is being used to extend/shrink width and/or height.
     *
     * @param event        Mouse event     deltaRect.width and deltaRect.height contains sizing information
     * @param keepSame     Array of bool   Parameter kept the same indicators
     * @param sourceLine   bool            This line is the source of the mouse event
     *
     * @since   1.0.0
     * @updated 1.1.0
     */
    $wcpA.LineManager.prototype.doResize = function ( event, keepSame, sourceLine ) {
        //noinspection JSUnresolvedVariable
        var deltaRect = event.deltaRect;
        var xOffset;
        var width;

        //noinspection OverlyComplexBooleanExpressionJS
        if (!(sourceLine || keepSame[$wcpA.actions.X] || keepSame[$wcpA.actions.Width] || keepSame[$wcpA.actions[this.sizing]])) { // Nothing for us, get out
            return;
        }

        this.resizing = true;
        if ( deltaRect.width ) {
            width = deltaRect.width;
            switch ( this.Line.Align ) {
                case "L":
                    xOffset = deltaRect.left;
                    break;
                case "R":
                    xOffset = deltaRect.right;
                    break;
                default:
                    xOffset = (deltaRect.right + deltaRect.left) / 2;
            }

            if (sourceLine || keepSame[$wcpA.actions.X]) {
                this.setX( this.Line.X + xOffset );
            }
            if (sourceLine || keepSame[$wcpA.actions.Width]) {
                this.setWidth( this.Line.Width + width );
            }
        }

        if (deltaRect.height && (sourceLine || keepSame[$wcpA.actions[this.sizing]])) {
            this.setHeight( this.Line[ this.sizing ] + (deltaRect.height * 2) );
        }
    };

    /**
     * Called after all mouse operations for all lines. Set everything to nearest integer
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.doResizeEnd = function () {
        this.resizing = false;
        this.setX( this.Line.X );
        this.setY( this.Line.Y );
        this.setWidth( this.Line.Width );
        this.setHeight( this.Line[ this.sizing ] );
    };

    /**
     * Turn highlighting on/off for row.
     *
     * @param isActive  bool  True says this is the line being modified, highlight it.
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.highlight = function ( isActive ) {
        this.Row.toggleClass( "wcp-highlight", isActive );
    };

    /**
     * Sets up the numeric input fields with appropriate limits for size of image. Also checks current
     * values if active.
     *
     * @param maxHeight
     * @param maxWidth
     *
     * @since 1.0.0
     */
    $wcpA.LineManager.prototype.setMaxSizes = function ( maxHeight, maxWidth ) {
        this.yCell.attr( "max", maxHeight );
        this.xCell.attr( "max", maxWidth );
        this.widthCell.attr( "max", maxWidth );
        this.minFontCell.attr( "max", Math.floor( maxHeight / 2 ) );
        this.maxFontCell.attr( "max", Math.floor( maxHeight / 2 ) );

        // If we're currently showing make sure all values within new range
        if ( this.Active ) {
            this.setX( this.Line.X );
            this.setY( this.Line.Y );
            this.setWidth( this.Line.Width );
            this.setMinFont( this.Line.MinFont );
            this.setMaxFont( this.Line.MaxFont );
        }
    };

}( window.$wcpA = window.$wcpA || {}, jQuery ));

