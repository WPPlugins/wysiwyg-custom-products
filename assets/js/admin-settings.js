/**
 * Created by Dave on 24/08/16.
 *
 * @since   1.0.0
 * @updated 1.1.2
 */

/* JSHint/JSLint set up */
/*global ajaxurl      */  // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
/*global wcp_messages */  // created in HTML by wp_localize_script()
/*global interact     */  // from interact.js
/*global wp           */  // created by wp_enqueue_media()
/*global postboxes    */
/*global pagenow      */

//noinspection AssignmentResultUsedJS,JSUnusedLocalSymbols,JSLint
(function ( $wcpA, $, undefined ) { // undefined is declared but not provided so that it IS undefined.
    // undefined can be assigned another value by malicious code.
    "use strict";

    //noinspection MagicNumberJS,LocalVariableNamingConventionJS
    var NEW_LINE_OFFSET = 1.1;  // Would like to be const but avoiding ECMA script 6 for the moment

    var currentLayout       = {};
    var layoutName          = "";
    var currentLines        = 0;
    var lastLineNbr         = 0;
    var bModified           = true;
    var bResetting          = false;
    var existingLayoutNames = [];
    var sameAsRow;
    var keepSameCheckboxes  = {};

    var keepSame = {
        0: false, // Y
        1: true,  // X
        2: true,  // Align
        3: true,  // Width
        4: true,  // MinFont
        5: true,  // MaxFont
    };

    var lines = [], nonce;

    /**
     * Set up page with currently selected layout information
     *
     * @param skipImage boolean Indicates whether images should be reloaded or not. Set for initial page load
     *
     * @since   1.0.0
     * @updated 1.0.1
     */
    function reloadLayout( skipImage ) {
        if ( !skipImage ) {
            //noinspection JSUnresolvedVariable
            imageChange( "wcp_main_image", currentLayout.SetupImage );
        }

        $( "#max_lines" ).val( currentLayout.MaxLines ).trigger( "change" );
        $( "#current_lines" ).val( currentLayout.CurrentLines ).trigger( "change" );
        $( "#multiline_msg" ).val( currentLayout.MultilineReformat );
        $( "#too_many_lines_msg" ).val( currentLayout.NumberOfLines );
        $( "#singleline_msg" ).val( currentLayout.SinglelineReformat );

        $("#ink_color").iris("color", decimalToHex(currentLayout.InkColor));
        $("#sizing_color").iris("color", decimalToHex(currentLayout.ActiveMouseColor));
        $("#non_sizing_color").iris("color", decimalToHex(currentLayout.InactiveMouseColor));

        setModified( false );
    }

    /**
     * Makes sure that the field in the new name text box is based on the currently selected layout and
     * is unique by addition of the word - copy and a version number if necessary.
     *
     * @param currentName
     *
     * @since 1.0.0
     */
    function setNewName( currentName ) {
        var newName;

        newName = currentName;
        if ( 0 > newName.toLowerCase().indexOf( "copy" ) ) {
            newName += " - copy";
        }

        while ($.inArray(newName.toLowerCase().trim(), existingLayoutNames) > -1) {
            if ( $.isNumeric( newName.slice( -1 ) ) ) { // If already has a number
                // Increment
                newName = newName.replace( /\d+$/, function ( s ) {
                    return +s + 1;
                } );
            } else {
                newName += "1";
            }
        }
        $( "input#new_name" ).val( newName ).trigger( "change" );
    }

    /**
     * Called when the new name is being edited. Makes sure there's no name clash with an existing layout.
     * or reserved option name. Disables the copy and rename button if there is.
     *
     * @param event
     *
     * @since   1.0.0
     * @updated 1.1.0
     */
    function changeName( event ) {
        var name = $(event.currentTarget).val().toLowerCase().trim(), disabled;

        disabled = ((0 === name.length) || ($.inArray( name, existingLayoutNames ) > -1)) && !bModified;

        $("#wcp_copy").toggleClass("disabled", disabled);
        $("#wcp_rename").toggleClass("disabled", disabled);
    }

    /**
     * Ajax call to fetch the selected layout. Skip image is set during initialisation
     *
     * @param el        {jQuery}  The select element
     * @param skipImage bool
     *
     * @since 1.0.0
     */
    function doLoadLayout( el, skipImage ) {
        var data;

        layoutName = el.val();
        data       = {
            "wcp-nonce" : nonce,
            "action"    : "get_layout",
            "name"      : layoutName
        };

        $.get( ajaxurl, data, function ( response ) {
            currentLayout = response;
            reloadLayout( skipImage );
        } );

        setNewName( layoutName );
    }

    /**
     * Event fired on change of option on the layout select field
     *
     * @param event
     *
     * @since 1.0.0
     */
    function loadLayout( event ) {
        bResetting = true;
        doLoadLayout( $( event.currentTarget ), false );
    }

    /**
     * Chooses which image to load based on the calling element's id
     *
     * @param imageId       string
     * @param attachmentId  int     AttachmentId returned from the media selector
     *
     * @since 1.0.0
     */
    function imageChange( imageId, attachmentId ) {
        switch ( imageId ) {
            case "wcp_main_image":
                getImage( attachmentId, "SetupImage", "svg_background" );
                break;
            default: // Do nothing
        }
    }

    /**
     * Ajax call to get the selected image information
     *
     * @param attachmentId  int     AttachmentId returned from the media selector
     * @param setField      string  Field within current_layout being modified
     * @param imageSelector string  DOM SVG element id
     *
     * @since   1.0.0
     * @updated 1.0.8
     */
    function getImage( attachmentId, setField, imageSelector ) {
        var image, data = {
            "wcp-nonce"  : nonce,
            "action"     : "get_image_attr",
            "attachment" : attachmentId
        };

        currentLayout[ setField ] = attachmentId;
        if ( attachmentId ) {
            $.get( ajaxurl, data, function ( response ) {
                var i;
                    currentLayout.SetupHeight = response.height;
                    currentLayout.SetupWidth  = response.width;

                    for ( i = 0; i < lines.length; i++ ) {
                        lines[ i ].setMaxSizes( response.height, response.width );
                    }

                    // Have to do this using DOM element rather than jQuery because jQuery forces attribute names to lowercase
                    // being a "foreign" XML object, SVG is case sensitive, so it has to be "viewBox" not "viewbox"
                    document.getElementById("wcp_svg_image").setAttribute("viewBox", "0 0 " + response.width + " " + response.height);
                image = $( "#" + imageSelector );
                image.attr( "width", response.width );
                image.attr( "height", response.height );
                image.attr( "xlink:href", response.url );
            } );
        } else {
            $( "#" + imageSelector ).attr( "xlink:href", "" );
        }

        setModified( !bResetting );
    }

    /**
     * Get's either the selected layout or the list of layouts available
     *
     * @param selected
     * @returns {jQuery}
     *
     * @since 1.0.0
     */
    function getLayoutSelectorOptions( selected ) {
        var selector = $( "select#layouts" );
        if ( selected ) {
            return selector.children( "option:selected" );
        }
        return selector.children( "option" );
    }

    /**
     * Ajax call to post the modified layout for saving
     *
     * @since   1.0.0
     * @updated 1.0.1
     */
    function saveLayout() {
        var data = {
            "wcp-nonce" : nonce,
            "action"    : "post_layout",
            "name"      : layoutName,
            "layout"    : currentLayout
        };

        //noinspection JSCheckFunctionSignatures
        $.post( ajaxurl, data ).done( function ( errorMsg ) {
            if ( errorMsg ) {
                alert( errorMsg );
            } else {
                setModified( false );
            }
        } );
    }

    /**
     * Ajax call to post a name change
     *
     * @since   1.0.0
     * @updated 1.1.0
     */
    function renameLayout() {
        var newName = $("#new_name").val();
        var data = {
            "wcp-nonce" : nonce,
            "action"    : "post_rename",
            "name": layoutName,  // Needed because the ajax handler doesn't know where we are at
            "new-name"  : newName
        };

        if ($("#wcp_rename").hasClass("disabled")) {
            return;
        }

        //noinspection JSCheckFunctionSignatures
        $.post( ajaxurl, data ).done( function () {
            var currentOption = getLayoutSelectorOptions( true );
            currentOption.val( newName ); // Change name in select list
            currentOption.text( newName );
            existingLayoutNames[existingLayoutNames.indexOf(layoutName.toLowerCase())] = newName.toLowerCase();
            layoutName = newName;
            setNewName( newName ); // bump new name - Now the current name
        } );
    }

    /**
     * Ajax call to copy the currently selected layout to the new name
     *
     * @since   1.0.0
     * @updated 1.1.0
     */
    function copyLayout() {
        var newName = $("#new_name").val();
        var data = {
            "wcp-nonce" : nonce,
            "action"    : "post_copy",
            "name"      : layoutName,
            "new-name"  : newName
        };

        if ($("#wcp_copy").hasClass("disabled")) {
            return;
        }

        //noinspection JSCheckFunctionSignatures
        $.post( ajaxurl, data ).done( function () {
	        var currentOption = getLayoutSelectorOptions(true);
	        var option        = new Option(newName, newName, true, true);

            currentOption.after( option ); // Add new name to select list and select - set in new Option above
            existingLayoutNames.push(newName.toLowerCase()); // add layout name
            setNewName( newName ); // bump new name - Now the current name
            layoutName = newName;
            deleteVisibility(); // Should have at least two now
        } );
    }

    /**
     * Ajax call to delete the selected layout. Confirmation is obtained in maybeDelete.
     *
     * @since   1.0.0
     * @updated 1.1.0
     */
    function deleteLayout() {
        var data = {
            "wcp-nonce" : nonce,
            "action"    : "post_delete",
            "name"      : layoutName
        };


        //noinspection JSCheckFunctionSignatures
        $.post( ajaxurl, data ).done( function () {
            var currentOption = getLayoutSelectorOptions(true);
            var lcLayoutName = layoutName.toLowerCase();
            var newOption;
            // choose new item select list
            newOption         = currentOption.next();
            if ( !newOption.length ) {
                newOption = currentOption.prev();
            }
            // delete layout from select list, and name array
            currentOption.remove();
            deleteVisibility(); // might have be last one


            existingLayoutNames = $.grep( existingLayoutNames, function ( value ) {
                return value !== lcLayoutName;
            } );

            // Load new layout
            $( "select#layouts" ).val( newOption.val() ).trigger( "change" );
        } );
    }


    /**
     * Called when the max lines selection is changed. Adds or removes format lines as required.
     *
     * @param event
     *
     * @since 1.0.0
     */
    function setMaxLines( event ) {
        var maxLines           = parseInt( $( event.currentTarget ).val(), 10 );
        var currentLinesSelect = $( "select#current_lines" );
        var i;
        var j;
        var lastFormat;
        var newFormat;

        if ( maxLines < currentLayout.MaxLines ) { // Reducing lines
            //noinspection JSUnresolvedVariable
            if ( !window.confirm( wcp_messages.reducing_max_lines ) ) {
                $( event.currentTarget ).val( currentLayout.MaxLines );
                return;  // User doesn't want to lose formatting, bail
            }

            // Definitely reducing lines, remove unwanted formats
            for ( i = currentLayout.MaxLines; i > maxLines; i-- ) {
                //noinspection JSUnresolvedVariable
                delete currentLayout.Formats[ "Lines" + i ];
            }

            // Reduce current line to maxLines if necessary
            if ( maxLines < currentLayout.CurrentLines ) {
                $( "#current_lines" ).val( maxLines ).trigger( "change" );
            }
        } else { // Increasing lines
            for ( i = currentLayout.MaxLines; i < maxLines; i++ ) {
                // Copy previous format
                //noinspection JSUnresolvedVariable
                lastFormat = currentLayout.Formats[ "Lines" + i ];
                newFormat                                              = [];

                for ( j = 0; j < i; j++ ) {
                    newFormat.push( $.extend( {}, lastFormat[ j ] ) );
                }
                newFormat.push( $.extend( {}, lastFormat[ j - 1 ] ) ); // Recopy last format line

                // Move it to the new y position, making sure it doesn't fall off the image
                newFormat[ j ].Y += Math.floor( NEW_LINE_OFFSET * newFormat[ j ].MaxFont );
                if ( newFormat[ j ].Y > (currentLayout.SetupHeight - (newFormat[ j ].MaxFont / 2) ) ) {
                    newFormat[ j ].Y = (currentLayout.SetupHeight - (newFormat[ j ].MaxFont / 2));
                }

                //noinspection JSUnresolvedVariable
                currentLayout.Formats[ "Lines" + (parseInt( i ) + 1) ] = newFormat;
            }
            // Show new max lines
            $( "#current_lines" ).val( maxLines ).trigger( "change" );
        }

        // Modify current lines selection to show available lines
        currentLinesSelect.children( "option" ).each( function ( idx, option ) {
            $( option ).toggleClass( "hidden", idx >= maxLines );
        } );

        currentLayout.MaxLines = maxLines;

        setModified( true );
    }

    /**
     * Called when the number of lines being formatted is changed. Reloads the line managers with the appropriate
     * data.
     *
     * @param event
     *
     * @since 1.0.0
     */
    function doFormatLines( event ) {
        var formatLines = parseInt( $( event.currentTarget ).val(), 10 );
        var i;
        var action;
        var checkVal;

        for ( i = 0; i < lines.length; i++ ) {
            lines[i].setLine(currentLayout, formatLines);
        }
        currentLines               = formatLines;
        currentLayout.CurrentLines = formatLines; // No modified, will only get saved if user makes other changes
        // Could save it by ajax, but can't really see the point

        lastLineNbr = 0;

        if ( 1 < currentLines ) {
            for ( action = $wcpA.actions.X; action <= $wcpA.actions.MaxFont; action++ ) {
                checkVal = lines[ 0 ].getVal( action );
                for ( i = 1; i < currentLines; i++ ) {
                    if ( checkVal !== lines[ i ].getVal( action ) ) {
                        checkVal = false;
                        //noinspection BreakStatementJS
                        break;
                    }
                }

                keepSame[action] = !!checkVal;
                keepSameCheckboxes[action].checked = keepSame[action];
            }
        }

        sameAsRow.toggleClass( "hidden", 2 > currentLines );
    }

    /**
     * Called when the font size radio buttons are changed. Causes the line managers to update accordingly.
     *
     * @param event
     *
     * @since 1.0.0
     */
    function doChooseSetFont( event ) {
        var font = $( event.currentTarget ).val(), i;

        for ( i = 0; i < lines.length; i++ ) {
            lines[ i ].setSizing( font );
        }
    }

    /**
     * Sets the appropriate value to passed line. Checks to see if that particular
     * value (action) is marked as "Keep Same" using the checkbox. If so, sets it for all lines.
     *
     * @param action   One of the actions enumerated in line-manager.js
     * @param lineNbr  Number of the line to be modified and highlighted if necessary
     * @param value    Numeric
     *
     * @since 1.0.0
     */
    function setVal( action, lineNbr, value ) {
        lastLineNbr = lineNbr;
        if (keepSame[action]) {
            setAllValues( action, value, lineNbr );
        } else {
            doAction( action, lines[ lineNbr ], value, true );
            highlightRow( lineNbr );
        }
    }

    /**
     * Set a value from a mouse action
     *
     * @param action  One of the mouse actions enumerated in line-manager.js
     * @param event   target is one of the SVG rectangles
     *
     * @since 1.0.0
     */
    function setMouseValue( action, event ) {
        var lineNbr = parseInt( event.target.id.substr( -1, 1 ) );
        lastLineNbr = lineNbr;
        setAllValues( action, event, lineNbr );
    }

    /**
     * Sets/Resets whether a value should be made the same for all the lines in the current format.
     * If being set, then it causes all of the lines to be updated to the last used (highlighted) line value.
     *
     * @param action       One of the actions enumerated in line-manager.js
     * @param setKeepSame  bool
     *
     * @since 1.0.0
     */
    function setSameAs( action, setKeepSame ) {
        keepSame[action] = setKeepSame;

        if ( setKeepSame ) {
            setAllValues( action, lines[ lastLineNbr ].getVal( action ), lastLineNbr );
        }
    }

    /**
     * Updates all of the lines with a particular value
     *
     * @param action     One of the actions enumerated in line-manager.js
     * @param value      Numeric
     * @param lineNbr    Number of the line being currently modified (so should be highlighted)
     *
     * @since 1.0.0
     */
    function setAllValues( action, value, lineNbr ) {
        var i;

        for ( i = 0; i < currentLines; i++ ) {
            doAction( action, lines[ i ], value, lineNbr === i );
        }
        highlightRow( lineNbr );
    }

    /**
     * Gets all of the line managers to turn on/off highlighting.
     *
     * @param lineNbr  Number of the line to highlighted
     *
     * @since 1.0.0
     */
    function highlightRow( lineNbr ) {
        var i;

        for ( i = 0; i < currentLines; i++ ) {
            lines[ i ].highlight( lineNbr === i ); // Turn ON when this is the current line
        }
    }

    /**
     * Sets the parameter (action) value for the line passed. sourceLine tells the mouse actions whether
     * this comes from the current line or is being passed on.
     *
     * @param action       One of the actions enumerated in line-manager.js - value to set
     * @param line         Line manager being acted on
     * @param value        Integer or mouse event containing multiple values - depends on action
     * @param sourceLine   bool
     *
     * @since 1.0.0
     */
    function doAction( action, line, value, sourceLine ) {
        switch ( action ) {
            case $wcpA.actions.Y:
                line.setY( value );
                break;
            case $wcpA.actions.X:
                line.setX( value );
                break;
            case $wcpA.actions.Align:
                line.setAlign( value );
                break;
            case $wcpA.actions.Width:
                line.setWidth( value );
                break;
            case $wcpA.actions.MinFont:
                line.setMinFont( value );
                break;
            case $wcpA.actions.MaxFont:
                line.setMaxFont( value );
                break;
            case $wcpA.actions.Move:
                line.doMove( value, keepSame, sourceLine );
                break;
            case $wcpA.actions.Resize:
                line.doResize( value, keepSame, sourceLine );
                break;
            case $wcpA.actions.ResizeEnd:
                line.doResizeEnd();
                break;
            default:
          // Do nothing
        }

        line.drawVisuals();
        setModified( true );
    }

    /**
     * Called when the user error messages are changed
     *
     * @param event
     *
     * @since 1.0.0
     */
    function changeMessage( event ) {
        var id   = event.currentTarget.id;
        var text = $( event.currentTarget ).val();

        switch ( id ) {
            case "multiline_msg":
                currentLayout.MultilineReformat = text;
                break;
            case "too_many_lines_msg":
                currentLayout.NumberOfLines = text;
                break;
            case "singleline_msg":
                currentLayout.SinglelineReformat = text;
                break;
            default:
          // Do nothing
        }

        setModified( true );
    }

    /**
     * Set or clear the modified status. Changes layout and functionality accordingly
     *
     * @param value   bool
     *
     * @since 1.0.0
     */
    function setModified( value ) {
        var layoutSelect;

        if ( !bModified ) {
            bResetting = false;
        }

        if ( bModified === value ) {
            return;
        }

        bModified = value;

        $("#wcp_save").toggleClass("disabled", !bModified);
        $("#wcp_cancel").toggleClass("disabled", !bModified);
        $("#wcp_copy").parent().toggleClass("hidden", bModified);
        $( "input#new_name" ).parent().toggleClass( "hidden", bModified );
        deleteVisibility();
        layoutSelect = $( "select#layouts" );
        layoutSelect.prop( "disabled", bModified );

        if ( bModified ) {
            window.onbeforeunload = function () {
                //noinspection JSUnresolvedVariable
                return wcp_messages.modified_leave;
            };
        } else {
            layoutSelect.focus();
            window.onbeforeunload = null;
        }
    }

    /**
     * Modifies screen based on whether an the currently selected layout can be deleted.
     * Can only delete a non-modified layout if there's more than one
     *
     * @since 1.0.0
     */
    function deleteVisibility() {
        $("#wcp_delete").toggleClass("hidden", bModified || (2 > getLayoutSelectorOptions().length)); // Can't delete last one!
    }

    /**
     * Reload the current layout
     *
     * @since 1.1.0
     */
    function cancelChanges() {
        bResetting = true;
        doLoadLayout($("select#layouts"), false);
    }

    /**
     * Called when the delete layout button is clicked. Checks for confirmation before calling delete function
     *
     * @since 1.0.0
     */
    function maybeDelete() {
        //noinspection JSUnresolvedVariable
        if ( window.confirm( wcp_messages.confirm_delete + " " + layoutName ) ) {
            deleteLayout();
        }
    }

    /**
     * Handle color change event
     *
     * @param  event
     * @param  ui
     *
     * @since  1.1.1
     */

    function colorChange(event, ui) {
        var id = event.target.id;
        var color = ui.color._color;
        var colorHex = decimalToHex(color);
        var selector;

        switch (id) {
            case "ink_color" :
                selector = "display_text";
                currentLayout.InkColor = color;
                break;
            case "sizing_color" :
                selector = "size_rects";
                currentLayout.ActiveMouseColor = color;
                break;
            case "non_sizing_color" :
                selector = "non_size_rects";
                currentLayout.InactiveMouseColor = color;
                break;
        }

        $("#" + selector).css("fill", colorHex);
        setModified(true);
    }

    /**
     * Utility function to get suitable hex string for colors
     *
     * @param  d integer
     *
     * @return string
     *
     * @since  1.1.1
     */
    function decimalToHex(d) {
        var hex = Number(d).toString(16);
        hex = "#000000".substr(0, 7 - hex.length) + hex;
        return hex;
    }

    /**
     * Ajax call to save the clean plugin delete option
     *
     * @param  event
     *
     * @since   1.1.2
     */
    function saveDeleteValue(event) {
        var data = {
            "wcp-nonce": nonce,
            "action": "post_plugin_delete",
            "delete_value": event.target.checked ? "yes" : "no"
        };

        //noinspection JSCheckFunctionSignatures
        $.post(ajaxurl, data);
    }

    /**
     * Uses interact.js to handle mouse events. i.e. text resizing and moving
     *
     * @since 1.0.0
     */
    function initialiseMouseActions() {
        //noinspection JSUnusedGlobalSymbols,JSUnusedGlobalSymbols,JSUnresolvedFunction
        interact( ".resize-drag" )
          .draggable( {
                          onmove : function ( event ) {
                              setMouseValue( $wcpA.actions.Move, event );
                          }
                      } )
          .resizable( {
                          preserveAspectRatio : false,
                          edges               : { left : true, right : true, bottom : true, top : true },
                          snap                : {
                              targets        : [
                                  interact.createSnapGrid( { x : 1, y : 1 } )
                              ],
                              range          : Infinity,
                              relativePoints : [ { x : 0, y : 0 } ]
                          },
                          onend               : function ( event ) {
                              setMouseValue( $wcpA.actions.ResizeEnd, event );
                          }
                      } )
          .on( "resizemove", function ( event ) {
              setMouseValue( $wcpA.actions.Resize, event );
          } );
    }

    /**
     * Sets up the media browser (added by wp_enqueue_media in php)
     *
     * @since 1.0.0
     */
    function initialiseImageBrowser() {
        $( ".wcp-browse-image" ).each( function () {
            $( this ).on( "click", function ( event ) {
                var self = $( this );
                var fileFrame;

                event.preventDefault();

                // Create the media frame.
                wp.media.frames.file_frame = wp.media( {
                                                           title    : self.data( "uploader_title" ),
                                                           button   : {
                                                               text : self.data( "uploader_button_text" )
                                                           },
                                                           multiple : false
                                                       } );

                fileFrame = wp.media.frames.file_frame;

                fileFrame.on( "select", function () {
                    var attachment = fileFrame.state().get( "selection" ).first().toJSON();
                    imageChange( self[ 0 ].id, attachment.id );
                } );

                // Finally, open the modal
                fileFrame.open();
            } );
        } );
    }

    /**
     * Set up color pickers
     *
     * @since  1.1.1
     */
    function initialiseColorPickers() {
        $(".color-picker").wpColorPicker({change: colorChange});

    }
    /**
     * Sets up all of the javascript actions and any other initialisation required
     *
     * @since   1.0.0
     * @updated 1.1.2
     */
    $wcpA.initialise = function () {
        var table        = $( "table#line_formats" );
        var layoutSelect = $( "select#layouts" );

        // Get the magic number for Ajax calls
        nonce = $( "#wcp_nonce" ).val();

        // Associate each line on table with it's LineManager
        table.find( "tr.format-line" ).each( function ( rowIdx, aRow ) {
            var row = $( aRow );

            lines.push( new $wcpA.LineManager( rowIdx, row ) );
            row.children( "td" ).each( function ( colIdx, cell ) {
                // Make the input field in each cell call the update with the appropriate:
                // action - colIdx, line number - rowIdx, and the new value
                var input = $( cell.children[ 0 ] );
                input.change( function () {
                    setVal( colIdx, rowIdx, $( this ).val() );
                } );
            } );
        } );

        sameAsRow = $( table.find( "tr.same-size" )[ 0 ] );
        sameAsRow.children( "td" ).each( function ( colIdx, cell ) {
            var input  = cell.children[ 0 ];
            var action = colIdx + 1; // + 1 because "Y" is a th header cell

            // Store the object for later setting if necessary
            keepSameCheckboxes[action] = input;
            // Get each checkbox to make the appropriate update
            $( input ).change( function () {
                setSameAs( action, this.checked );
            } );
        } );


        // Change of user error message text
        $( ".overflow-message" ).each( function () {
            $( this ).on( "change keyup paste cut input", changeMessage );
        } );

        // Create list of layouts available
        existingLayoutNames = layoutSelect.find( "option" ).map( function () {
            return $( this ).val().toLowerCase();
        } ).get();

        // Add reserved option names
        existingLayoutNames.push( "settings" );
        existingLayoutNames.push( "ver" );
        existingLayoutNames.push( "db_ver" );
        existingLayoutNames.push( "layout" );

        // Allows checking of "new" name to make sure no clashes with an existing layout
        $( "input#new_name" ).on( "change keyup paste cut input", changeName );

        // What to do when the selected layout changes
        layoutSelect.on( "change", loadLayout );
        doLoadLayout( layoutSelect, true ); // Skip image on initial - loaded as part of page to avoid ajax delays

        // New image selected in media browser
        $( ".wcp_attachment_id" ).each( function () {
            $( this ).on( "change", imageChange );
        } );

        // Format modifications
        $( "#max_lines" ).on( "change", setMaxLines );
        $( "#current_lines" ).on( "change", doFormatLines );

        // Should mouse actions be on MinFont or MaxFont
        $( "[name='current_font']" ).each( function () {
            $( this ).on( "click", doChooseSetFont );
        } );

        // Basic button actions
        $("#wcp_rename").on("click", renameLayout);
        $("#wcp_copy").on("click", copyLayout);
        $("#wcp_cancel").on("click", cancelChanges);
        $("#wcp_save").on("click", saveLayout);
        $( "#wcp_delete" ).on( "click", maybeDelete );
        // Now all parameters are set up, show the table
        table.removeClass( "hidden" );

        $("#save_on_delete").on("click", saveDeleteValue);
        // Final initialisation
        initialiseMouseActions();
        initialiseImageBrowser();
        initialiseColorPickers();

        // Add meta-box handling
        // close postboxes that should be closed
        $(".if-js-closed").removeClass("if-js-closed").addClass("closed");
        // postboxes setup
        postboxes.add_postbox_toggles(pagenow);
    };
}( window.$wcpA = window.$wcpA || {}, jQuery ));   // $wcpA is extended or created as needed.
                                                   // jQuery is assigned to $
                                                   // "undefined" is undefined

/**
 * Loader function
 *
 * @since 1.0.0
 */
jQuery( document ).ready( function () {
    "use strict";
    window.$wcpA.initialise();
} );

