/**
 * Script Ran on Customizer Side
 *
 * Handles editor and UI.
 *
 * We use jQuery.ui.tabs( options, element ) instead of jQuery().tabs( options ).
 *
 */
define( 'udx.ui.dynamic-table', [ 'udx.utility' ], function dynamicTable() {
  console.log( 'udx.ui.dynamic-table', 'dynamicTable' );

  /**
   * Creates a Dynamic Table Instance
   *
   * @method createInstance
   */
  return function domReady() {
    console.log( 'udx.ui.dynamic-table', 'domReady' );

    // Modules.
    var Utility = require( 'udx.utility' );

    /**
     * Bind ColorPicker with input fields '.wpp_input_colorpicker'
     *
     * @param object instance. jQuery object
     */
    function bind_color_picker( instance ) {
      if( typeof window.jQuery.prototype.ColorPicker == 'function' ) {
        if( !instance ) {
          instance = jQuery( 'body' );
        }
        jQuery( '.wpp_input_colorpicker', instance ).ColorPicker( {
          onSubmit: function( hsb, hex, rgb, el ) {
            jQuery( el ).val( '#' + hex );
            jQuery( el ).ColorPickerHide();
            jQuery( el ).trigger( 'change' );
          },
          onBeforeShow: function() {
            jQuery( this ).ColorPickerSetColor( this.value );
          }
        } ).bind( 'keyup', function() {
            jQuery( this ).ColorPickerSetColor( this.value );
          });
      }
    }

    /**
     * Updates Row field names
     *
     * @param object instance. DOM element
     * @param boolean allowRandomSlug. Determine if Row can contains random slugs.
     */
    function update_row_names( instance, allowRandomSlug ) {
      if( typeof instance == 'undefined' ) {
        return false;
      }
      if( typeof allowRandomSlug == 'undefined' ) {
        var allowRandomSlug = false;
      }

      var this_row = jQuery( instance ).parents( 'tr.wpp_dynamic_table_row' );

      // Slug of row in question
      var old_slug = jQuery( this_row ).attr( 'slug' );

      // Get data from input.slug_setter
      var new_slug = jQuery( instance ).val();

      // Convert into slug
      new_slug = Utility.create_slug( new_slug );

      // Don't allow to blank out slugs
      if( new_slug == "" ) {
        if( allowRandomSlug && !jQuery( instance ).hasClass( 'wpp_slug_can_be_empty' ) ) {
          new_slug = 'random_' + Math.floor( Math.random() * 1000 );
        } else {
          return;
        }
      }

      // There is no sense to continue if slugs are the same /
      if( old_slug == new_slug ) {
        //* new_slug = 'random_' + Math.floor(Math.random()*1000); */
        return;
      }

      // Get all slugs of the table
      jQuery( instance ).addClass( 'wpp_current_slug_is_being_checked' );
      var slugs = jQuery( this_row ).parents( 'table' ).find( 'input.slug' );
      slugs.each( function( k, v ) {
        if( jQuery( v ).val() == new_slug && !jQuery( v ).hasClass( 'wpp_current_slug_is_being_checked' ) ) {
          new_slug = 'random_' + Math.floor( Math.random() * 1000 );
          return false;
        }
      });
      jQuery( instance ).removeClass( 'wpp_current_slug_is_being_checked' );

      // If slug input.slug exists in row, we modify it
      jQuery( ".slug", this_row ).val( new_slug );
      // Update row slug
      jQuery( this_row ).attr( 'slug', new_slug );

      // Cycle through all child elements and fix names
      jQuery( 'input,select,textarea', this_row ).each( function( i, e ) {
        var old_name = jQuery( e ).attr( 'name' );
        if( typeof old_name != 'undefined' && !jQuery( e ).hasClass( 'wpp_no_change_name' ) ) {
          var new_name = old_name.replace( '[' + old_slug + ']', '[' + new_slug + ']' );
          // Update to new name
          jQuery( e ).attr( 'name', new_name );
        }
        var old_id = jQuery( e ).attr( 'id' );
        if( typeof old_id != 'undefined' ) {
          var new_id = old_id.replace( old_slug, new_slug );
          jQuery( e ).attr( 'id', new_id );
        }
      });

      // Cycle through labels too
      jQuery( 'label', this_row ).each( function( i, e ) {
        if( typeof jQuery( e ).attr( 'for' ) != 'undefined' ) {
          var old_for = jQuery( e ).attr( 'for' );
          var new_for = old_for.replace( old_slug, new_slug );
          // Update to new name
          jQuery( e ).attr( 'for', new_for );
        }
      });

      jQuery( ".slug", this_row ).trigger( 'change' );
    }

    /**
     * Set unique IDs and FORs of DOM elements recursivly
     *
     * @param object el. jQuery DOM object
     * @param integer unique. Unique suffix which will be added to all IDs and FORs
     * @author Maxim Peshkov
     */
    function set_unique_ids( el, unique ) {
      if( typeof el == "undefined" || el.size() === 0 ) {
        return;
      }

      el.each( function() {
        var child = jQuery( this );

        if( child.children().size() > 0 ) {
          set_unique_ids( child.children(), unique );
        }

        var id = child.attr( 'id' );
        if( typeof id != 'undefined' ) {
          child.attr( 'id', id + '_' + unique );
        }

        var efor = child.attr( 'for' );
        if( typeof efor != 'undefined' ) {
          child.attr( 'for', efor + '_' + unique );
        }
      });
    }

    /**
     * Adds new Row to the table
     *
     * @param element
     * @return
     */
    function add_row( element ) {
      var auto_increment = false;
      var table = jQuery( element ).parents( '.ud_ui_dynamic_table' );
      var table_id = jQuery( table ).attr( "id" );
      var callback_function;

      //* Determine if table rows are numeric */
      if( jQuery( table ).attr( 'auto_increment' ) == 'true' ) {
        var auto_increment = true;
      } else if( jQuery( table ).attr( 'use_random_row_id' ) == 'true' ) {
        var use_random_row_id = true;
      } else if( jQuery( table ).attr( 'allow_random_slug' ) == 'true' ) {
        var allow_random_slug = true;
      }

      //* Clone last row */
      var cloned = jQuery( ".wpp_dynamic_table_row:last", table ).clone();

      // Set unique 'id's and 'for's for elements of the new row 
      
      var unique = Math.floor( Math.random() * 1000 );
      set_unique_ids( cloned, unique );

      // Increment name value automatically
      if( auto_increment ) {
        //* Cycle through all child elements and fix names */
        jQuery( 'input,select,textarea', cloned ).each( function( element ) {
          var old_name = jQuery( this ).attr( 'name' );
          var matches = old_name.match( /\[(\d{1,2})\]/ );
          if( matches ) {
            old_count = parseInt( matches[1] );
            new_count = (old_count + 1);
          }
          var new_name = old_name.replace( '[' + old_count + ']', '[' + new_count + ']' );
          //* Update to new name */
          jQuery( this ).attr( 'name', new_name );
        });

      } else if( use_random_row_id ) {
        //* Get the current random id of row */
        var random_row_id = jQuery( cloned ).attr( 'random_row_id' );
        var new_random_row_id = Math.floor( Math.random() * 1000 )
        //* Cycle through all child elements and fix names */
        jQuery( 'input,select,textarea', cloned ).each( function( element ) {
          var old_name = jQuery( this ).attr( 'name' );
          var new_name = old_name.replace( '[' + random_row_id + ']', '[' + new_random_row_id + ']' );
          //* Update to new name */
          jQuery( this ).attr( 'name', new_name );
        });
        jQuery( cloned ).attr( 'random_row_id', new_random_row_id );

      } else if( allow_random_slug ) {
        //* Update Row names */
        var slug_setter = jQuery( "input.slug_setter", cloned );
        jQuery( slug_setter ).attr( 'value', '' );
        if( slug_setter.length > 0 ) {
          update_row_names( slug_setter.get( 0 ), true );
        }
      }

      // Insert new row after last one
      jQuery( cloned ).appendTo( table );

      //* Get Last row to update names to match slug */
      var added_row = jQuery( ".wpp_dynamic_table_row:last", table );

      // Bind (Set) ColorPicker with new fields '.wpp_input_colorpicker'
      bind_color_picker( added_row );
      
      // Display row just in case
      jQuery( added_row ).show();

      //* Blank out all values */
      jQuery( "textarea", added_row ).val( '' );
      jQuery( "select", added_row ).val( '' );
      jQuery( "input[type=text]", added_row ).val( '' );
      jQuery( "input[type=checkbox]", added_row ).attr( 'checked', false );

      //* Unset 'new_row' attribute */
      jQuery( added_row ).attr( 'new_row', 'true' );

      //* Focus on new element */
      jQuery( 'input.slug_setter', added_row ).focus();

      //* Fire Event after Row added to the Table */
      added_row.trigger( 'added' );

      if( callback_function = jQuery( element ).attr( "callback_function" ) ) {
        call_method( callback_function, window, added_row );
      }

      return added_row;

    }

    /**
     * Call Method.
     *
     * @param functionName
     * @param context
     * @param args
     * @return
     */
    function call_method( functionName, context, args ) {
      var args = Array.prototype.slice.call( arguments ).splice( 2 );
      var namespaces = functionName.split( "." );
      var func = namespaces.pop();
      for( var i = 0; i < namespaces.length; i++ ) {
        context = context[namespaces[i]];
      }
      return context[func].apply( this, args );
    }

  };

});

