/**
 * XMLI Scripts
 *
 *
 *
 * @version 4.0.3
 * @author: potanin@UD
 * @date: 8/19/13
 */


// Ensure XMLI string object exist.
wpp.strings = wpp.strings || {};

// XMLI Instance Object.
wpp.xmli = jQuery.extend({

  settings: {
    debug: true
  },

  /**
   * Initializer.
   *
   */
  ready: function ready() {
    wpp.xmli.debug( 'wpp.xmli.ready()' );

    var import_hash = false;
    var schedule_id;

    wpp.xmli.refresh_dom();

    jQuery( ".wpp_xi_sort_rules" ).live( "click", function () {

      var list_wrapper = jQuery( "#wpp_property_import_attribute_mapper" );
      var listitems = jQuery( ".wpp_dynamic_table_row", list_wrapper ).get();

      listitems.sort( function ( a, b ) {

        var compA = jQuery( "select.wpp_import_attribute_dropdown option:selected", a ).text();
        var compB = jQuery( "select.wpp_import_attribute_dropdown option:selected", b ).text();

        if ( compA === undefined ) {
          compA = 0;
        } else {
          compA = compA;
        }

        if ( compB === undefined ) {
          compB = 0;
        } else {
          compB = compB;
        }

        var index = ( compA < compB ) ? -1 : ( compA > compB ) ? 1 : 0;
        console.log( compA + ' - ' + compB + ': ' + index );

        return index;

      } )

      jQuery.each( listitems, function ( idx, itm ) {
        list_wrapper.append( itm );
      });

    });

    jQuery( "#wpp_property_import_choose_root_element" ).live( "change", function () {
      var value = jQuery( this ).val();
      var fixed_value = value.replace( /'/g, '"' );
      jQuery( this ).val( fixed_value );
    });

    jQuery( ".wpp_xi_advanced_setting input[type=checkbox]" ).live( "change", function () {
      var wrapper = jQuery( this ).closest( ".wpp_xi_advanced_setting" );
      if ( jQuery( this ).is( ":checked" ) ) {
        jQuery( wrapper ).addClass( "wpp_xi_enabld_row" );
      } else {
        jQuery( wrapper ).removeClass( "wpp_xi_enabld_row" );
      }

      wpp.xmli.advanced_option_counter();

    });

    jQuery( ".wpp_xi_advanced_setting input[type=text]" ).live( "change", function () {
      var wrapper = jQuery( this ).closest( ".wpp_xi_advanced_setting" );
      var value = jQuery( this ).val();

      if ( value === "" || value === '0' ) {

        /* If 0 blank out this value */
        jQuery( this ).val( '' );

        /* Check if all inputs are empty */
        if ( jQuery( "input:text[value != '' ]", wrapper ).length == 0 ) {
          jQuery( wrapper ).removeClass( 'wpp_xi_enabld_row' );
        }

      } else {
        jQuery( wrapper ).addClass( 'wpp_xi_enabld_row' );
      }

      wpp.xmli.advanced_option_counter();

    });

    jQuery( '.wpp_property_toggle_import_settings' ).live( "click", function () {
      jQuery( ".wpp_property_import_settings" ).toggle();
      wpp.xmli.advanced_option_counter();
    });

    jQuery( ".wpp_import_delete_row" ).live( "click", function () {
      if ( !jQuery( 'input[name^="wpp_property_import[map]"]:checkbox:checked' ).length ) return false;
      if ( !confirm( 'Are you sure you want remove these items?' ) ) return false;
      jQuery( 'input[name^="wpp_property_import[map]"]:checkbox' ).each( function () {
        if ( this.checked ) {
          if ( jQuery( '#wpp_property_import_attribute_mapper .wpp_dynamic_table_row' ).length == 1 )
            jQuery( ".wpp_add_row" ).click();
          jQuery( this ).parents( '.wpp_dynamic_table_row' ).remove();

        }
      } )
      jQuery( jQuery( '[name^="wpp_property_import[map]"]:checkbox' ).parents( '.wpp_dynamic_table_row' ).get().reverse() ).each( function ( index ) {
        jQuery( this ).find( 'input,select' ).each( function () {
          var old_name = jQuery( this ).attr( 'name' );
          var matches = old_name.match( /\[( \d{1,2} )\]/ );
          if ( matches ) {
            old_count = parseInt( matches[1] );
            new_count = ( index + 1 );
          }
          var new_name = old_name.replace( '[' + old_count + ']', '[' + new_count + ']' );
          jQuery( this ).attr( 'name', new_name );
        });
      } )

      //** Create unique ID selected  **/
      wpp.xmli.import_build_unique_id_selector();
    } )

    jQuery( '#check_all' ).live( 'click', function () {
      if ( this.checked ) {
        jQuery( '[name^="wpp_property_import[map]"]:checkbox' ).attr( 'checked', 'checked' );
      } else {
        jQuery( '[name^="wpp_property_import[map]"]:checkbox' ).attr( 'checked', '' );
      }
    } )

    jQuery( "#wpp_i_do_full_import" ).live( "click", function () {

      /* Blank out preview result since it will be deleted after completion of import */
      jQuery( "wpp_i_preview_raw_data_result" ).html( "" );

      var import_hash = jQuery( "#import_hash" ).val();

      if ( import_hash != "" ) {
        window.open( wpp.instance.home_url + "/?wpp_schedule_import=" + import_hash + "&echo_log=true", 'wpp_i_do_full_import' );
      } else {
        wpp.xmli.actions_bar_message( wpp.strings.xmli.please_save, "bad", 7000 );
      }

    });

    /**
     * Media Import Starter
     *
     * @since 4.0.3
     */
    jQuery( "#wpp_i_do_media_import" ).live( "click", function () {

      var import_hash = jQuery( "#import_hash" ).val();

      if ( import_hash != "" ) {
        window.open( wpp.instance.home_url + "/?wpp_manage_pending_images=" + import_hash + "&echo_log=true", 'wpp_manage_pending_images' );
      } else {
        wpp.xmli.actions_bar_message( wpp.strings.xmli.please_save, "bad", 7000 );
      }

    });

    jQuery( "#wpp_i_preview_action" ).live( "click", function () {

      var source_type = jQuery( "#wpp_property_import_source_type option:selected" ).val();

      jQuery( "#wpp_i_preview_action" ).val( 'Loading...' );

      /*jQuery( "#wpp_i_preview_action" ).attr( 'disabled', true ); */

      jQuery( "#wpp_import_object_preview" ).html( "<pre class=\"wpp_class_pre\"></pre>" );

      var params = {
        action: 'wpp_property_import_handler',
        wpp_action: 'execute_schedule_import',
        preview: 'true',
        data: jQuery( "#wpp_property_import_setup" ).serialize()
      }

      if ( schedule_id !== undefined ) {
        params.schedule_id = schedule_id;
      }

      if ( source_type !== undefined ) {
        params.source_type = source_type;
      }

      jQuery.post( wpp.instance.ajax_url, params,function ( result ) {
        jQuery( "#wpp_i_preview_action" ).attr( 'disabled', false );
        jQuery( "#wpp_i_preview_action" ).val( 'Preview Again' );

        if ( result.success == 'true' ) {
          wpp.xmli.actions_bar_message();
          jQuery( "#wpp_import_object_preview" ).show();
          jQuery( "#wpp_import_object_preview" ).html( "<pre class=\"wpp_class_pre\">" + result.ui + "</pre>" );
        } else {
          alert( result.message );
        }

      }, 'json' ).success(function () {
        } ).error( function ( result ) {
          if ( result.status == 500 ) {
            wpp.xmli.actions_bar_message( wpp.strings.xmli.out_of_memory, 'bad' );
            jQuery( "#wpp_i_preview_action" ).val( 'Preview Again' );
          }
        });
    });

    jQuery( ".wpp_i_close_preview" ).live( "click", function () {
      jQuery( "#wpp_i_preview_raw_data" ).val( 'Preview Raw Data' );
      jQuery( ".wpp_i_close_preview" ).hide();
      jQuery( ".wppi_raw_preview_result" ).hide();
      jQuery( ".wppi_raw_preview_result" ).html( "" );
    });

    jQuery( "#wpp_i_preview_raw_data" ).live( "click", function () {

      var source_type = jQuery( "#wpp_property_import_source_type option:selected" ).val();

      if ( !wpp.xmli.validate_source_info( source_type ) ) {
        return false;
      }

      if ( source_type == "" ) {
        jQuery( "#wpp_property_import_source_type" ).addClass( 'wpp_error' );
        return;
      } else {
        jQuery( "#wpp_property_import_source_type" ).removeClass( 'wpp_error' );
      }

      wpp.xmli.preview_raw_preview_result( wpp.strings.xmli.loading );

      jQuery( "#wpp_i_preview_raw_data" ).attr( 'disabled', true );

      var params = {
        action: 'wpp_property_import_handler',
        wpp_action: 'execute_schedule_import',
        raw_preview: 'true',
        data: jQuery( "#wpp_property_import_setup" ).serialize()
      }

      if ( schedule_id !== undefined ) {
        params.schedule_id = schedule_id;
      }

      if ( source_type !== undefined ) {
        params.source_type = source_type;
      }

      wpp.xmli.loading_show();

      jQuery.post( wpp.instance.ajax_url, params,function ( result ) {

        wpp.xmli.loading_hide();

        jQuery( "#wpp_i_preview_raw_data" ).attr( 'disabled', false );
        jQuery( "#wpp_i_preview_raw_data" ).val( 'Preview Again' );

        if ( result.success === true || result.success === 'true' ) {

          wpp.xmli.preview_raw_preview_result( result.preview_bar_message );

          /* Should always return a schedule ID */
          wpp.xmli.set_schedule_id( result.schedule_id );

          jQuery( ".wpp_i_close_preview" ).show();
          jQuery( ".wppi_raw_preview_result" ).show();
          jQuery( ".wppi_raw_preview_result" ).html( "<pre class=\"wpp_class_pre\">" + result.ui + "</pre>" );
        } else {
          wpp.xmli.preview_raw_preview_result( result.message, "bad" );
        }

        wpp.xmli.loading_hide();

      }, 'json' ).success(function () {
        } ).error( function ( result ) {

          jQuery( "#wpp_i_preview_raw_data" ).attr( 'disabled', false );

          if ( result.status == 500 ) {
            wpp.xmli.preview_raw_preview_result( wpp.strings.xmli.out_of_memory, 'bad' );
            jQuery( "#wpp_i_preview_raw_data" ).val( 'Preview Raw Data' );
          }

          wpp.xmli.preview_raw_preview_result( result.responseText, "bad" );

          wpp.xmli.loading_hide();

        });


    });

    //** Update to get matched tags from array. */
    jQuery( "#wpp_import_auto_match" ).live( "click", function () {
      wpp.xmli.perform_auto_matching();
    });

    jQuery( "#wpp_property_import_save" ).live( "click", function ( e ) {

      e.preventDefault();

      var this_button = this;
      var original_text = wpp.strings.xmli.save;

      wpp.xmli.actions_bar_message( wpp.strings.xmli.saving );

      jQuery( this_button ).val( wpp.strings.xmli.processing );


      var params = {
        action: 'wpp_property_import_handler',
        wpp_action: 'save_new_schedule',
        data: jQuery( "#wpp_property_import_setup" ).serialize()
      }

      /* schedule_id may have been created during a preview or source eval */
      if ( schedule_id !== undefined ) {
        params.schedule_id = schedule_id;
      }

      jQuery.post( wpp.instance.ajax_url, params, function ( result ) {
        if ( result.success == 'true' ) {
          wpp.xmli.actions_bar_message( wpp.strings.xmli.saved, "good", 7000 );
          wpp.xmli.set_schedule_id( result.schedule_id );
          wpp.xmli.set_schedule_hash( result.hash );
          jQuery( this_button ).val( original_text );

        } else {
          wpp.xmli.actions_bar_message( result.message, 'error' );
        }
      }, 'json' );
    });

    jQuery( "#wpp_property_import_update" ).live( "click", function ( e ) {

      e.preventDefault();

      var this_button = this;
      var original_text = wpp.strings.xmli.save;

      wpp.xmli.actions_bar_message( wpp.strings.xmli.updating );

      jQuery( this_button ).val( wpp.strings.xmli.processing );

      schedule_id = jQuery( this ).attr( 'schedule_id' );

      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_property_import_handler',
        wpp_action: 'update_schedule',
        schedule_id: schedule_id,
        data: jQuery( "#wpp_property_import_setup" ).serialize()
      }, function ( result ) {

        if ( typeof result === 'object' && result.success === 'true' ) {
          wpp.xmli.set_schedule_id( result.schedule_id );
          wpp.xmli.set_schedule_hash( result.hash );
          wpp.xmli.actions_bar_message( wpp.strings.xmli.updated, "good", 7000 );

          jQuery( this_button ).val( original_text );

        } else {
          wpp.xmli.actions_bar_message( result.message, 'error' );
        }

      }, 'json' );
    });

    /* Activated when "Add New" buttin is clicked */
    jQuery( "#wpp_property_import_add_import" ).click( function () {
      jQuery( ".updated" ).remove();
      wpp.xmli.show_schedule_editor_ui();
    });

    jQuery( ".wpp_property_import_edit_report" ).click( function ( e ) {
      var schedule_id = jQuery( this ).attr( 'schedule_id' );
      wpp.xmli.show_schedule_editor_ui( schedule_id );
    });

    /* Sort schedules on overview page */
    jQuery( ".wpp_i_sort_schedules" ).click( function ( e ) {

      e.preventDefault();

      jQuery( ".wpp_i_sort_schedules a" ).removeClass( "current" );

      jQuery( "a", this ).addClass( "current" );

      var sort_by = jQuery( this ).attr( "sort_by" );
      var sort_direction = jQuery( this ).attr( "sort_direction" );
      var mylist = jQuery( '#wpp_property_import_overview tbody' );
      var listitems = mylist.children( 'tr' ).get();

      listitems.sort( function ( a, b ) {

        var compA = jQuery( a ).attr( sort_by );
        var compB = jQuery( b ).attr( sort_by );

        if ( compA === undefined ) {
          compA = 0;
        } else {
          compA = parseInt( compA );
        }

        if ( compB === undefined ) {
          compB = 0;
        } else {
          compB = parseInt( compB );
        }

        if ( sort_direction == "DESC" ) {
          var index = ( compA < compB ) ? -1 : ( compA > compB ) ? 1 : 0;
        } else {
          var index = ( compA > compB ) ? -1 : ( compA < compB ) ? 1 : 0;
        }


        return index;

      } )

//** Switch sorting direction */
      if ( sort_direction == "DESC" ) {
        jQuery( this ).attr( "sort_direction", "ASC" )
      } else {
        jQuery( this ).attr( "sort_direction", "DESC" )
      }

      jQuery.each( listitems, function ( idx, itm ) {
        mylist.append( itm );
      });


    });

    jQuery( ".wppi_delete_all_feed_properties" ).click( function ( e ) {

      e.preventDefault();

      var verify_response;
      var row = jQuery( this ).parents( ".wpp_i_schedule_row" );
      var total_properties = jQuery( row ).attr( "total_properties" );
      var schedule_id = jQuery( row ).attr( "schedule_id" );
      var import_title = jQuery( row ).attr( "import_title" );

      verify_response = prompt( "Confirm that you want to delete all " + total_properties + " properties from this feed by typing in in 'delete' below, or press 'Cancel' to cancel." );

      if ( verify_response == "delete" ) {

        jQuery( "#wpp_property_import_ajax" ).show();
        jQuery( "#wpp_property_import_ajax" ).html( "<div class='updated below-h2'><p>Deleting all properties from " + import_title + ". You can close your browser, the operation will continue until completion.</p></div>" );

        jQuery.post( wpp.instance.ajax_url, {
          action: 'wpp_property_import_handler',
          schedule_id: schedule_id,
          wpp_action: 'delete_all_schedule_properties'
        }, function ( result ) {
          if ( result.success == 'true' ) {
            jQuery( "#wpp_property_import_ajax" ).html( "<div class='updated below-h2'><p>" + result.ui + "</p></div>" );
          } else {
            jQuery( "#wpp_property_import_ajax" ).html( "<div class='updated below-h2'><p>" + wpp.strings.xmli.error_occured + "</p></div>" );
          }
        }, 'json' );

      } else {
        return;
      }

    });

    jQuery( ".wpp_property_import_delete_report" ).click( function ( e ) {
      e.preventDefault();

      if ( !confirm( wpp.strings.xmli.are_you_sure ) )
        return;

      var schedule_id = jQuery( this ).attr( 'schedule_id' );
      var rmel = jQuery( this ).parents( 'tr' );

      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_property_import_handler',
        schedule_id: schedule_id,
        wpp_action: 'delete_schedule'
      }, function ( result ) {
        if ( result.success == 'true' ) {
          jQuery( rmel ).remove();
          if ( jQuery( '#wpp_property_import_overview tr' ).length == 1 )
            jQuery( '#wpp_property_import_overview' ).remove();
        }
      }, 'json' );
    });

    /* Vlidate source and get info when one of the source-related fields is updated. */
    jQuery( "#wpp_property_import_remote_url, #wpp_property_import_username, #wpp_property_import_password" ).live( "change", function () {
      wpp.xmli.determine_settings( {} );
    });

    /* If source is selected, remove error and determine settings */
    jQuery( "#wpp_property_import_source_type" ).live( "change", wpp.xmli.source_changed );

    /* Vlidate source when "Source is Good" label is pressed. Third argument forces cache refresh. */
    jQuery( '#wpp_property_import_source_status' ).live( 'click', function () {
      wpp.xmli.evaluate_source( this, false, true );
    });

    jQuery( "#wpp_property_import_unique_id" ).live( 'change', function () {
      wpp.xmli.import_build_unique_id_selector();
    });

    jQuery( 'select[name^="wpp_property_import[map]"]' ).live( 'change', function () {
      wpp.xmli.import_build_unique_id_selector();
    });

  },

  /**
   * Source Changed.
   *
   */
  source_changed: function source_changed() {
    wpp.xmli.debug( 'wpp.xmli.source_changed()' )

    wpp.xmli.determine_settings( {} );

    jQuery( "#wpp_property_import_source_type" ).removeClass( 'wpp_error' );

  },

  /**
   * XMLI Logger.
   *
   * @usage
   *    wpp.xmli.debug( 'My message.' );
   *
   * @for wpp.xmli
   * @method log
   * @author potanin@UD
   */
  debug: function debug() {

    // Ignore if debugging is not enabled.
    if( !wpp.xmli.settings.debug ) {
      return;
    }

    if( 'function' === typeof console.log ) {
      console.log.apply( console, arguments );
    }

  },

  /**
   * Toggle loading icon at the top of the page.
   *
   */
  loading_show: function loading_show () {
    jQuery( ".wpp_xi_loader" ).css( "display", "inline-block" );
  },

  /**
   * Toggle loading icon at the top of the page.
   *
   */
  loading_hide: function loading_hide () {
    jQuery( ".wpp_xi_loader" ).hide();
  },

  /**
   * Rebuild unique ID dropdown
   *
   */
  import_build_unique_id_selector: function import_build_unique_id_selector () {

    var uid_container = jQuery( ".wpp_i_unique_id_wrapper" );
    var uid_select_element = jQuery( "#wpp_property_import_unique_id" );

    /* Get current UID */
    var selected_id = uid_select_element.val();

    var selected_attributes = jQuery( "select[name^='wpp_property_import[map]'] option:selected[value!='']" ).length;

    /* Blank out dropdown eleemnt */
    uid_select_element.html( '' );

    uid_select_element.append( '<option value=""> - </option>' );

    jQuery( 'select[name^="wpp_property_import[map]"] option:selected' ).each( function () {

      var attribute_slug = jQuery( this ).val();

      var cur = jQuery( 'select#wpp_property_import_unique_id option[value="' + attribute_slug + '"]' );

      /* Make sure that the attribute isn't already added to the UID dropdown and a value exists  */
      if ( cur.length == 0 && cur.val() != "" ) {

        var title = jQuery( this ).html();
        uid_select_element.append( '<option value="' + attribute_slug + '">' + title + '</option>' );
      }

      if ( selected_id != "" && selected_id != null ) {
        uid_select_element.val( selected_id );
      }

    });

    //* No attribute found, nothing to display in UID dropdown */
    if ( selected_attributes == 0 ) {
      jQuery( '.wpp_i_unique_id_wrapper' ).hide();
    } else {
      jQuery( '.wpp_i_unique_id_wrapper' ).show();

      if ( selected_id == "" ) {
        jQuery( "span.description", uid_container ).html( wpp.strings.xmli.select_unique_id );
      } else {
        jQuery( "span.description", uid_container ).html( wpp.strings.xmli.unique_id_attribute );
      }
    }


  },

  /**
   * Add attribute and xPath rows based on matched tags from XML feed and avaialble attributes.
   *
   */
  perform_auto_matching: function perform_auto_matching () {
    wpp.xmli.debug( 'wpp.xmli.perform_auto_matching()' );

    var wpp_all_importable_attributes = new Array();
    var wpp_all_importable_attributes_labels = new Array();

    var wpp_successful_matches = 0;

    var source_type = jQuery( "#wpp_property_import_source_type" ).val();

    jQuery( "#wpp_import_auto_match" ).attr( 'disabled', false );

    jQuery( "#wpp_import_auto_match" ).val( 'Automatically Match' );

    //** If tags are not defined, attempt to reload */
    if ( wpp_auto_matched_tags === undefined ) {

      //** Disable button and start auto import */
      jQuery( "#wpp_import_auto_match" ).val( 'Reloading XML...' );
      jQuery( "#wpp_import_auto_match" ).attr( 'disabled', true );

      wpp.xmli.evaluate_source( false, wpp.xmli.perform_auto_matching );

      return;

    }

    /* Get all WPP tags from first dropdown */
    jQuery( "#wpp_property_import_attribute_mapper .wpp_dynamic_table_row option" ).each( function () {

      var meta_key = jQuery( this ).val();
      var label = jQuery( this ).text();

      /* Add to importable attributes array if key is not blank, and not already in there. Note: inArray() returns -1 one when no match is found. */
      if ( meta_key != "" && !jQuery.inArray( meta_key, wpp_all_importable_attributes ) != -1 ) {
        wpp_all_importable_attributes.push( meta_key );
      }

      /* Add to importable attribute labels as well. */
      if ( label != "" && !jQuery.inArray( label, wpp_all_importable_attributes_labels ) != -1 ) {
        wpp_all_importable_attributes_labels.push( label );
      }

    });


    /* Cycle through auto-matched tags, and attempt to match them with WPP tags */
    jQuery.each( wpp_auto_matched_tags, function () {

      /* Get key from XML source */
      var xml_tag = String( this );

      /* We convert to lower case for comparison because WPP attribute slugs are always lower case . */
      var wpp_like_xml_tag = wpp_create_slug( xml_tag );

      /* console.log( xml_tag + " slugged to: " + wpp_like_xml_tag ); */

      /* Do special functions if this is WPP Import */
      if ( source_type == 'wpp' ) {

        // Do not load images this way, the real images are in the 'gallery' key in WPP exports
        if ( wpp_like_xml_tag == 'images' ) {
          return true;
        }
      }

      /* Check if current xml_tag ( from auto_matched array from XML source exists in importable attributes array. */
      if ( jQuery.inArray( wpp_like_xml_tag, wpp_all_importable_attributes ) != -1 ) {

        /* We have a match for this attribute */

      } else {

        /* Try matching based on label if nothing is found in keys */
        if ( jQuery.inArray( wpp_like_xml_tag, wpp_all_importable_attributes_labels ) != -1 ) {

        } else {
          return;
        }

        return;
      }

      /* If this attribute already appears to be mapped over, we skip. */
      if ( jQuery( '#wpp_property_import_attribute_mapper .wpp_dynamic_table_row option[value=' + wpp_like_xml_tag + ']:selected' ).length > 0 ) {
        return true;
      }

      /* Add row to table, and enter xpath rule */
      var added_row = wpp_add_row( jQuery( '.wpp_add_row' ) );

      jQuery( '.wpp_import_attribute_dropdown', added_row ).val( wpp_like_xml_tag );

      jQuery( '.xpath_rule', added_row ).val( xml_tag );

      wpp_successful_matches++;


    });

    //** Handle special WPP attributes **/
    if ( source_type == 'wpp' ) {

      if ( jQuery( '#wpp_property_import_attribute_mapper .wpp_dynamic_table_row option[value=images]:selected' ).length < 1 ) {
        var added_row = wpp_add_row( jQuery( '.wpp_add_row' ) );
        jQuery( '.wpp_import_attribute_dropdown', added_row ).val( 'images' );
        jQuery( '.xpath_rule', added_row ).val( 'gallery/*/large' );
        wpp_successful_matches++;
      }
    }

    if ( source_type == 'rets' ) {

      /*
       console.log( 'trying to add rets image' );
       console.log( jQuery( '#wpp_property_import_attribute_mapper .wpp_dynamic_table_row option[value=images]:selected' ).length );
       */

      if ( jQuery( '#wpp_property_import_attribute_mapper .wpp_dynamic_table_row option[value=images]:selected' ).length < 1 ) {
        var added_row = wpp_add_row( jQuery( '.wpp_add_row' ) );
        jQuery( '.wpp_import_attribute_dropdown', added_row ).val( 'images' );
        jQuery( '.xpath_rule', added_row ).val( 'wpp_gallery/*/path' );
        wpp_successful_matches++;
      }
    }

    /* alert( wpp_successful_matches ); Should do something with the result, although it is clearly visual. */

    //** Clean up table **/
    wpp.xmli.rule_table_remove_blank_rows();

    //** Create unique ID selected  **/
    wpp.xmli.import_build_unique_id_selector();

    //** Select WPP Unique ID  **/
    if ( source_type == 'wpp' ) {
      jQuery( "#wpp_property_import_unique_id" ).val( 'wpp_gpid' );
    }

  },

  /**
   * Remove any blank rows from table.
   *
   */
  rule_table_remove_blank_rows: function rule_table_remove_blank_rows () {

    if ( jQuery( "#wpp_property_import_attribute_mapper .wpp_dynamic_table_row" ).length < 2 ) {
      return;
    }

    jQuery( "#wpp_property_import_attribute_mapper .wpp_dynamic_table_row" ).each( function () {

      var wpp_import_attribute_dropdown = jQuery( '.wpp_import_attribute_dropdown option:selected', this ).val();

      var xpath_rule = jQuery( '.xpath_rule', this ).val();

      /* console.log( wpp_import_attribute_dropdown + ' xpath_rule ' + xpath_rule ); */

      if ( xpath_rule == '' && wpp_import_attribute_dropdown == '' ) {
        jQuery( this ).remove();
      }

    });


  },

  /**
   * Ran when any information regarding the source URL, type, or login information is chaged.
   *
   * Verifies source can be loaded and is valid.
   * Returns matched_tags and stored in wpp_auto_matched_tags
   */
  evaluate_source: function evaluate_source ( object, callback_func, do_not_use_cache ) {
    wpp.xmli.debug( 'wpp.xmli.evaluate_source()' );

    /* Be default we do not re-cache */
    if ( !do_not_use_cache ) {
      do_not_use_cache = false;
    }

    var remote_url = jQuery( "#wpp_property_import_remote_url" ).val();
    var import_type = jQuery( "#wpp_property_import_source_type option:selected" ).val();

    if ( !remote_url.length || import_type == "" ) {
      return;
    }

    jQuery( ".wpp_i_source_feedback" ).hide();

    //** Set root element query based on source type, and show/hide other items */
    jQuery( '.wpp_property_import_gs_options .wpp_i_advanced_source_settings, .wpp_property_import_rets_options .wpp_i_advanced_source_settings' ).hide();

    if ( import_type == "wpp" ) {
      jQuery( "#wpp_property_import_choose_root_element" ).val( '/objects/object' );
    } else if ( import_type == "gs" ) {
      jQuery( "#wpp_property_import_choose_root_element" ).val( 'ROW' );
      jQuery( '.wpp_property_import_gs_options .wpp_i_advanced_source_settings' ).show();
    } else if ( import_type == "rets" ) {
      jQuery( "#wpp_property_import_choose_root_element" ).val( '/ROWS/ROW' );
      jQuery( '.wpp_property_import_rets_options .wpp_i_advanced_source_settings' ).show();
    }

    //** If currently edited element is the remote URL, try to guess the source type */
    if ( jQuery( object ).attr( 'id' ) == 'wpp_property_import_remote_url' ) {

      //** Set Import Type to WPP if source appears to be a WPP export */
      if ( remote_url.search( "action=wpp_export_properties" ) > 0 ) {
        //** Set source type to WPP */
        jQuery( "#wpp_property_import_source_type" ).val( 'wpp' );

      } else if ( remote_url.search( "spreadsheets.google.com" ) > 0 ) {
        //** Set source type to Google Spreadsheet */
        jQuery( "#wpp_property_import_source_type" ).val( 'gs' );

      }

    }

    /** If we're RETS or Google, and we don't have a user/pass, we return */
    if ( import_type == "rets" || import_type == "gs" ) {
      if ( import_type == "gs" && ( jQuery( "#wpp_property_import_username" ).val() == "" || jQuery( "#wpp_property_import_password" ).val() == "" ) ) {
        alert( "Please fill out all required fields, check the advanced properties if you're unsure of where to look." );
        return false;
      }
      if ( import_type == "rets" && ( jQuery( "#wpp_property_import_rets_username" ).val() == "" || jQuery( "#wpp_property_import_rets_password" ).val() == "" || jQuery( "#wpp_property_import_rets_class" ).val() == "" ) ) {
        alert( "Please fill out all required fields, check the advanced properties if you're unsure of where to look." );
        return false;
      }
    }

    wpp.xmli.loading_show();

    wpp.xmli.source_status( 'processing' );

    var params = {
      action: 'wpp_property_import_handler',
      wpp_action: 'execute_schedule_import',
      wpp_action_type: 'source_evaluation',
      source_type: jQuery( "#wpp_property_import_source_type option:selected" ).val(),
      data: jQuery( "#wpp_property_import_setup" ).serialize()
    }

    if ( do_not_use_cache ) {
      params.do_not_use_cache = true;
    }

    //** If we have a schedule_id, we pass it into source eval */
    if ( schedule_id !== undefined ) {
      params.schedule_id = schedule_id;
    }

    jQuery.post( wpp.instance.ajax_url, params,function ( result ) {

      wpp.xmli.loading_hide();

      /* Should always return a schedule ID */
      wpp.xmli.set_schedule_id( result.schedule_id );
      wpp.xmli.set_schedule_hash( result.hash );

      //** Load auto matched tags into global variable */
      wpp_auto_matched_tags = result.auto_matched_tags;

      //** Enable the Automatically Match button */
      jQuery( "#wpp_import_auto_match" ).val( wpp.strings.xmli.automatically_match );
      jQuery( "#wpp_import_auto_match" ).attr( "disabled", false );

      if ( result.success == 'true' ) {
        wpp.xmli.source_status( 'good' );

        /* Callback a function, most likely wpp.xmli.perform_auto_matching() to finish matching */
        if ( callback_func && typeof( callback_func ) === "function" ) {
          callback_func();
        }

      } else {
        wpp.xmli.source_status( 'bad' );
        wpp.xmli.source_check_result( result.message, 'bad' );
      }

    }, 'json' ).success(function () {
      } ).error( function ( result ) {

        wpp.xmli.loading_hide();

        if ( result.status == 500 ) {
          wpp.xmli.source_status( 'server_error' );
          wpp.xmli.source_check_result( wpp.strings.xmli.evaluation_500_error, 'bad' );
          return;
        }

        /* Proper result not returned, and not a specific error */
        wpp.xmli.source_status( 'bad' );
        wpp.xmli.source_check_result( wpp.strings.xmli.request_error + " " + result.responseText, 'bad' );
        return;

      });

  },

  /**
   * Sets schedule_id for current DOM
   *
   */
  set_schedule_id: function set_schedule_id ( schedule_id ) {

    /* Set URL hash */
    window.location.hash = schedule_id;

    /* Return for good measure */
    return schedule_id;

  },

  /**
   * Sets schedule_id for current DOM
   *
   */
  set_schedule_hash: function set_schedule_hash ( schedule_hash ) {

    /* Set global variable */
    import_hash = schedule_hash;

    /* Set DOM element */
    jQuery( "#import_hash" ).val( import_hash );

    /* Return for good measure */
    return schedule_hash;

  },

  /**
   * Display a message in the "Action Bar", below the Attribute Map
   *
   */
  actions_bar_message: function actions_bar_message ( message, type, delay ) {
    var error_class = false;
    var element = jQuery( ".wpp_i_import_actions_bar .wpp_i_ajax_message" );

    /* Remove all classes */
    element.removeClass( "wpp_i_error_text" );

    if ( type !== undefined && type != "" ) {

      if ( type == 'bad' ) {
        var add_class = 'wpp_i_error_text'
      } else if ( type == 'good' ) {
        var add_class = ''
      } else {
        var add_class = type;
      }

    }

    //* If no message passed, just hide the element and bail */

    if ( message == "" || message == undefined ) {

      if ( delay != undefined ) {
        element.delay( delay ).fadeOut( "slow" );
      } else {
        element.hide();
      }

      return;
    }

    /* If we are adding a class */
    if ( add_class ) {


      //* Add default class back on if one was passd */
      element.addClass( 'wpp_i_ajax_message' );

      /* Add custom class */
      element.addClass( add_class );
    }

    element.show();

    element.html( message );

    if ( delay != undefined ) {
      element.delay( delay ).fadeOut( "slow" );
    }

  },

  /**
   * Display a message in the "Raw Preview" message section
   *
   */
  source_check_result: function source_check_result ( message, type ) {
    var element = jQuery( "li.wpp_i_source_feedback" );

    element.show();
    element.removeClass( 'wpp_i_error_text' );

    if ( message === undefined ) {
      element.html( '' );
      element.hide( '' );
      return;
    }

    if ( type == 'bad' ) {
      element.addClass( 'wpp_i_error_text' );
    }

    element.html( message );

  },

  /**
   * Display a message in the "Raw Preview" message section
   *
   */
  preview_raw_preview_result: function preview_raw_preview_result ( message, type ) {

    var element = jQuery( "span.wpp_i_preview_raw_data_result" );

    element.removeClass( 'wpp_i_error_text' );

    if ( message === undefined ) {
      element.html( '' );
      return;
    }

    if ( type == 'bad' ) {
      element.addClass( 'wpp_i_error_text' );
    }

    element.html( message );

  },

  /**
   * Sets the status of the source URL in UI
   *
   */
  source_status: function source_status ( status ) {
    wpp.xmli.debug( 'wpp.xmli.source_status()' );

    jQuery( "#wpp_property_import_source_status" ).removeClass();

    if ( status == '' ) {
      jQuery( "#wpp_property_import_source_status" ).hide();
      return;
    }

    jQuery( "#wpp_property_import_source_status" ).show();

    if ( status == 'ready_to_check' ) {
      jQuery( "#wpp_property_import_source_status" ).text( 'Check Source' );
    }

    if ( status == 'processing' ) {
      jQuery( "#wpp_property_import_source_status" ).addClass( 'wpp_import_source_processing' );
      jQuery( "#wpp_property_import_source_status" ).text( wpp.strings.xmli.source_is_good );
    }

    if ( status == 'good' ) {
      jQuery( "#wpp_property_import_source_status" ).addClass( 'wpp_import_source_good' );
      jQuery( "#wpp_property_import_source_status" ).text( wpp.strings.xmli.source_is_good );
    }

    if ( status == 'bad' ) {
      jQuery( "#wpp_property_import_source_status" ).addClass( 'wpp_import_source_bad' );
      jQuery( "#wpp_property_import_source_status" ).text( wpp.strings.xmli.cannot_reload_source );
    }

    if ( status == 'server_error' ) {
      jQuery( "#wpp_property_import_source_status" ).addClass( 'wpp_import_source_bad' );
      jQuery( "#wpp_property_import_source_status" ).text( wpp.strings.xmli.internal_server_error );
    }

  },

  /**
   * Ensure all necessary data for given source is filled in
   *
   * @param type
   * @returns {boolean}
   */
  validate_source_info: function validate_source_info ( type ) {
    wpp.xmli.debug( 'wpp.xmli.validate_source_info()' );

    var required_fields = jQuery( "input.wpp_required", "[wpp_i_source_type=" + type + "]" );
    var success = true;

    if ( required_fields.length < 1 ) {
      return true;
    }

    jQuery( required_fields ).each( function () {
      var value = jQuery.trim( jQuery( this ).val() );

      if ( value == "" ) {
        jQuery( this ).addClass( "wpp_error" );
        success = false;
      } else {
        jQuery( this ).val( value );
        jQuery( this ).removeClass( "wpp_error" );
      }

    });

    return success;
  },

  /**
   * Displays editor UI
   *
   * @param passed_schedule_id
   */
  show_schedule_editor_ui: function show_schedule_editor_ui ( passed_schedule_id ) {

    wpp.xmli.loading_show();

    jQuery( ".updated" ).remove();

    if ( passed_schedule_id == "" ) {
      var new_import = true;
    }

    schedule_id = passed_schedule_id;

    params = {
      action: 'wpp_property_import_handler',
      wpp_action: 'add_edit_schedule'
    }

    if ( !new_import ) {
      params.schedule_id = schedule_id;
    }

    jQuery.post( wpp.instance.ajax_url, params, function ( result ) {
      if ( result.success == 'true' ) {
        jQuery( ".wpp_import_overview_page_element" ).hide();
        jQuery( "#wpp_property_import_ajax" ).html( result.ui ).show();
        jQuery( "#wpp_property_import_name" ).focus();

        wpp.xmli.loading_hide();

        wpp.xmli.run_on_import_ui_display( result );

      }
    }, 'json' );

  },

  /**
   * Handles functions and UI configurations when a major DOM change is made
   *
   */
  advanced_option_counter: function advanced_option_counter () {

    /* Special Rules: Limits cannot be used with property deletion */
    if ( jQuery( '#wpp_property_limit_properties' ).val() || jQuery( '#wpp_property_limit_scanned_properties' ).val() ) {
      wpp.xmli.disable_advanced_option( '#wpp_property_import_remove_non_existant_properties' );
    } else {
      wpp.xmli.enable_advanced_option( '#wpp_property_import_remove_non_existant_properties' );
    }

    /* Special Rules: Enable SQL Query option when Advanced Logging is on */
    if ( jQuery( '#wpp_import_log_detail' ).is( ':checked' ) ) {
      wpp.xmli.enable_advanced_option( 'input[name="wpp_property_import[show_sql_queries]"]' );
    } else {
      wpp.xmli.disable_advanced_option( 'input[name="wpp_property_import[show_sql_queries]"]' );
    }

    /* Special Rules: If set to remove all properties from this feed only, option of removing all properties not available */
    if ( jQuery( 'input[name="wpp_property_import[remove_all_from_this_source]"]' ).is( ':checked' ) ) {
      wpp.xmli.disable_advanced_option( 'input[name="wpp_property_import[remove_all_before_import]"]' );
    } else {
      wpp.xmli.enable_advanced_option( 'input[name="wpp_property_import[remove_all_before_import]"]' );
    }

    var count = jQuery( ".wpp_xi_advanced_setting.wpp_xi_enabld_row" ).length;

    if ( jQuery( ".wpp_property_import_settings" ).is( ":visible" ) || count == 0 ) {
      jQuery( "span.wpp.xmli.advanced_option_counter" ).html( '' );
      return;
    }

    jQuery( "span.wpp.xmli.advanced_option_counter" ).html( "( " + count + " " + wpp.strings.xmli.enabled_options + ")" );

  },

  /**
   * Disables, resets, and grays out an option
   *
   * @param element
   */
  disable_advanced_option: function disable_advanced_option ( element ) {
    jQuery( element ).prop( 'disabled', true );
    jQuery( element ).prop( 'checked', false );
    jQuery( element ).closest( 'li.wpp_xi_advanced_setting' ).css( 'opacity', 0.3 ).removeClass( '.wpp_xi_enabld_row' );
  },

  /**
   * Enables an option
   *
   * @param element
   */
  enable_advanced_option: function enable_advanced_option ( element ) {
    jQuery( element ).prop( 'disabled', false );
    jQuery( element ).closest( 'li.wpp_xi_advanced_setting' ).css( 'opacity', 1 );
  },

  /**
   * Handles functions and UI configurations when a major DOM change is made
   *
   */
  refresh_dom: function refresh_dom () {
    wpp.xmli.debug( 'wpp.xmli.refresh_dom()' );

    var current_page = false;

    if ( window.location.hash ) {

      if ( window.location.hash.length == 11 ) {
        var hash = window.location.hash.replace( "#", "" );
        wpp.xmli.show_schedule_editor_ui( hash );
        current_page = 'schedule_editor';
      } else if ( window.location.hash == "#add_new_schedule" ) {
        wpp.xmli.show_schedule_editor_ui( hash );
        current_page = 'add_new_schedule';
      } else {
        current_page = 'overview';
      }

    }
  },

  /**
   * Determine if "Toggle Advanced Settings" link should be displayed for this source type
   *
   * @param response
   */
  determine_settings: function determine_settings ( response ) {
    wpp.xmli.debug( 'wpp.xmli.determine_settings()' );

    var source_type = jQuery( "#wpp_property_import_source_type option:selected" ).val();
    var source_label = jQuery( "#wpp_property_import_source_type option:selected" ).html();
    var source_url = jQuery( "#wpp_property_import_remote_url" ).val();

    if ( source_url != "" && source_type != "" ) {
      wpp.xmli.source_status( 'ready_to_check' );
    } else {
      wpp.xmli.source_status( '' );
    }

    /* Hide all non-import-type-specific advanced options */
    jQuery( ".wpp_i_advanced_source_settings" ).hide();

    /* Hide all configuration not related to this type */
    jQuery( ".wpp_something_advanced_wrapper.wppi_source_option_preview_wrapper .wpp_i_source_specific" ).hide();
    jQuery( ".wpp_something_advanced_wrapper.wppi_source_option_preview_wrapper .wpp_i_source_specific[wpp_i_source_type = " + source_type + " ]" ).not( ".wpp_i_advanced_source_settings" ).show();

    /* Check if this source type has any advance settings, and then dispaly the link if appropriate */
    if ( jQuery( "li.wpp_i_advanced_source_settings[wpp_i_source_type='" + source_type + "']" ).length ) {
      jQuery( ".wppi_source_option_preview_wrapper.wpp_something_advanced_wrapper" ).show();
      jQuery( ".wppi_source_option_preview_wrapper.wpp_something_advanced_wrapper .wpp_show_advanced" ).text( wpp.strings.xmli.toggle_advanced + " " + source_label + " " + wpp.strings.xmli.settings );
    } else {
      jQuery( ".wppi_source_option_preview_wrapper.wpp_something_advanced_wrapper" ).hide();
    }
    
    /* Exclude specific advanced settings for specific sources  */
    jQuery( 'li.wpp_xi_advanced_setting' ).each( function( i, e ) {
      jQuery( e ).show();
      var exclude = typeof jQuery( e ).data( 'exclude_type' ) !== 'undefined' ? jQuery( e ).data( 'exclude_type' ) : '';
      exclude = exclude.split( ',' );
      jQuery.each( exclude, function( k, a ) {
        if( a.length > 0 && source_type == a ) {
          jQuery( e ).hide();
          return;
        }
      } );
    } );

    /* Check to see if any of our fields need to be update automatically */
    if ( !response.schedule_exists ) {
      if ( source_type == "rets" || source_type == "gs" ) {
        jQuery( "#wpp_property_import_choose_root_element" ).val( "//ROW" );
      }

      if ( source_type == "csv" ) {
        jQuery( "#wpp_property_import_choose_root_element" ).val( "//object" );
      }
    }

  },

  /**
   * Ran after the import editor screen is displayed
   *
   * @param response
   */
  run_on_import_ui_display: function run_on_import_ui_display ( response ) {
    wpp.xmli.debug( 'wpp.xmli.run_on_import_ui_display()' );

    wpp.xmli.determine_settings( response );
    wpp.xmli.import_build_unique_id_selector();

    jQuery( ".wpp_xi_advanced_setting input[type=checkbox]" ).each( function () {
      var wrapper = jQuery( this ).closest( "li.wpp_xi_advanced_setting" );
      if ( jQuery( this ).is( ":checked" ) ) {
        jQuery( wrapper ).addClass( 'wpp_xi_enabld_row' );
      } else {
        jQuery( wrapper ).removeClass( 'wpp_xi_enabld_row' );
      }
    });

    jQuery( ".wpp_xi_advanced_setting input[type=text]" ).each( function () {
      var wrapper = jQuery( this ).closest( "li.wpp_xi_advanced_setting" );

      if ( jQuery( this ).val() != "" && jQuery( this ).val() != "0" ) {
        jQuery( wrapper ).addClass( 'wpp_xi_enabld_row' );
        return;
      } else {

        /* Clear out zeroes */
        jQuery( this ).val( '' );

        jQuery( wrapper ).removeClass( 'wpp_xi_enabld_row' );
      }
    });

    wpp.xmli.advanced_option_counter();

  }

}, wpp.xmli || {} );

// Bind XMLI initializer.
jQuery( document ).ready( wpp.xmli.ready );

