( function( jQuery, l10n ){

  /**
   *
   * @param options
   */
  jQuery.fn.wpp_pagination = function(options) {

    /** Making variables public */
    var vars = jQuery.extend({
      'type': 'numeric',
      'unique_id': false,
      'use_pagination': true,
      'pages': null,
      'query': {},
      'ajax_url': false
    }, options);

    if( !vars.unique_id || !vars.ajax_url ) {
      return;
    }

    /**
     * The functionality below is used for pagination and sorting the list of properties
     * It can be many times (on multiple shortcodes)
     * So the current javascript functionality should not to be initialized twice.
     *
     * Init global WPP_QUERY variable which will contain all query objects
     */
    if ( typeof window.wpp_query == 'undefined' ) {
      window.wpp_query = {};
    }

    /**
     *
     */
    if ( typeof window.wpp_overview_document_ready == 'undefined' ) {
      window.wpp_overview_document_ready = false;
    }

    /**
     * Initialize shortcode's wpp_query object
     */
    if ( typeof wpp_query[ vars.unique_id ] == 'undefined' ) {
      window.wpp_query[ vars.unique_id ] = vars.query;
      window.wpp_query[ vars.unique_id].default_query = vars.query.query;
      window.wpp_query[ vars.unique_id].pagination_type = vars.type;
      window.wpp_query[ vars.unique_id].index = Object.keys(wpp_query).length;
    }

    /*
     * Init variable only at once
     */
    if ( typeof window.wpp_pagination_history_ran == 'undefined' ) {
      window.wpp_pagination_history_ran = false;
    }

    if ( typeof window.wpp_overview_first_load == 'undefined' ) {
      window.wpp_overview_first_load = true;
    }

    /**
     * Parse location (address) hash,
     * Setup shortcode params by hash params
     * Calls ajax pagination
     */
    function callPagination ( event ) {
      /*
       * We have to be sure that DOM is ready
       * if it's not, wait 0.1 sec and call function again
       */
      if ( !window.wpp_overview_document_ready ) {
        window.setTimeout( function () {
          callPagination( event );
        }, 100 );
        return false;
      }
      var history = {};
      /* Parse hash value (params) */
      var hashes = event.value.replace( /^\//, '' );
      /* Determine if we have hash params */
      if ( hashes ) {
        hashes = hashes.split( '&' );
        for ( var i in hashes ) {
          if ( typeof hashes[i] != 'function' ) {
            hash = hashes[i].split( '=' );
            history[hash[0]] = hash[1];
          }
        }
        if ( history.i ) {
          /* get current shortcode's object */
          var q = false;
          for( var i in wpp_query ) {
            if( wpp_query[i].index == history.i ) {
              q = wpp_query[i];
              break;
            }
          }

          if(!q) {
            return false;
          }

          if ( history.sort_by && history.sort_by != '' ) {
            q.sort_by = history.sort_by;
          }
          if ( history.sort_order && history.sort_order != '' ) {
            q.sort_order = history.sort_order;
          }
          /* 'Select/Unselect' sortable buttons */
          var sortable_links = jQuery( '#wpp_shortcode_' + q.unique_hash + ' .wpp_sortable_link' );
          if ( sortable_links.length > 0 ) {
            sortable_links.each( function ( i, e ) {
              jQuery( e ).removeClass( "wpp_sorted_element" );
              if ( jQuery( e ).attr( 'sort_slug' ) == q.sort_by ) {
                jQuery( e ).addClass( "wpp_sorted_element" );
              }
            } );
          }
          if ( history.requested_page && history.requested_page != '' ) {
            do_ajax_pagination( q.unique_hash, q.pagination_type, history.requested_page );
          } else {
            do_ajax_pagination( q.unique_hash, q.pagination_type, 1 );
          }
        } else {
          return false;
        }
      } else {
        /* Looks like it's base url
         * Determine if this first load, we do nothing
         * If not, - we use 'back button' functionality.
         */
        if ( window.wpp_overview_first_load ) {
          window.wpp_overview_first_load = false;
        } else {
          /*
           * Set default pagination values for all shortcodes
           */
          for ( var i in wpp_query ) {
            wpp_query[i].sort_by = wpp_query[i].default_query.sort_by;
            wpp_query[i].sort_order = wpp_query[i].default_query.sort_order;
            /* 'Select/Unselect' sortable buttons */
            var sortable_links = jQuery( '#wpp_shortcode_' + wpp_query[i].unique_hash + ' .wpp_sortable_link' );
            if ( sortable_links.length > 0 ) {
              sortable_links.each( function ( ie, e ) {
                jQuery( e ).removeClass( "wpp_sorted_element" );
                if ( jQuery( e ).attr( 'sort_slug' ) == wpp_query[i].sort_by ) {
                  jQuery( e ).addClass( "wpp_sorted_element" );
                }
              } );
            }
            do_ajax_pagination( wpp_query[i].unique_hash, wpp_query[i].pagination_type, 1, false);
          }
        }
      }
    }

    /**
     * Changes location (address) hash based on pagination
     *
     * We use this function extend of do_ajax_pagination()
     * because do_ajax_pagination() is called on change Address Value's event
     *
     * @param int this_page Page which will be loaded
     * @param object data WPP_QUERY object
     * @return object data Returns updated WPP_QUERY object
     */
    function changeAddressValue ( this_page, data ) {
      /* Set data query which will be used in history hash below */
      var q = {
        requested_page: this_page,
        sort_order: data.sort_order,
        sort_by: data.sort_by,
        i: data.index
      };
      /* Update WPP_QUERY query */
      data.query.requested_page = this_page;
      data.query.sort_order = data.sort_order;
      data.query.sort_by = data.sort_by;
      /*
       * Update page URL for back-button support (needs to do sort order and direction)
       * jQuery.address.value() and jQuery.address.path() double binds jQuery.change() event, some way
       * so for now, we use window.location
       */
      var history = jQuery.param( q );
      window.location.hash = '/' + history;
      return data;
    }

    /**
     * Draw our numeric navigation buttons.
     *
     */
    function prepare_numeric_pagination_ui( number ) {
      var c = jQuery( '<ul class="property-overview-navigation"></ul>' ),
        query = window.wpp_query[ vars.unique_id ],
        el = jQuery( "#wpp_shortcode_" + query.unique_hash + " .wpp_pagination_buttons_wrapper" );
      console.log( 'draw_pagination', number, query );
      number = parseInt( number );
      /** Maybe, render 'first' page link */
      if( number > 1 ) {
        c.append( '<li data-page="1" class="first-page-btn"><a href="javascript:;" class="btn button">' + l10n.first + '</a></li>' );
        c.append( '<li data-page="' + ( number - 1 ) + '" class="previous-page-btn"><a href="javascript:;" class="btn button">' + l10n.previous + '</a></li>' );
        //c.append( '<li class="dots">...</li>' );
      }

      c.append( '<li class="pages"><ul></ul></li>' );

      p = c.find( '.pages ul' );

      /** Maybe, render two previous pages links */
      for( i=2; i>= 1; i-- ) {
        var page = number - i;
        if( page > 0 ) {
          p.append( '<li data-page="' + page + '" class="page"><a href="javascript:;" class="btn button">' + page + '</a></li>' );
        }
      }

      /** Render current page link */
      p.append( '<li data-page="' + number + '" data-current="true" class="page current-page"><a href="javascript:;" class="btn button">' + number + '</span></li>' );

      /** Maybe, render two next pages links */
      var show_last = true;
      for( i=1; i<= 2; i++ ) {
        var page = number + i;
        if( page > query.pages ) {
          show_last = false;
          console.log('page > query.pages', page, query.pages);
          break;
        } else if ( page == query.pages ) {
          //show_last = false;
          console.log('page == query.pages', page, query.pages);
          p.append( '<li data-page="' + page + '" class="page"><a href="javascript:;" class="btn button">' + page + '</a></li>' );
          break;
        } else {
          p.append( '<li data-page="' + page + '" class="page"><a href="javascript:;" class="btn button">' + page + '</a></li>' );
        }
      }

      c.append( '</ul></li>' );

      /** Maybe, render 'last' page link */
      if( show_last ) {
        //c.append( '<li class="dots">...</li>' );
        c.append( '<li data-page="' + ( number + 1 ) + '" class="previous-page-btn"><a href="javascript:;" class="btn button">' + l10n.next + '</a></li>' );
        c.append( '<li data-page="' + query.pages + '" class="last-page-btn"><a href="javascript:;" class="btn button">' + l10n.last + '</a></li>' );
      }
      /** Update our HTML */
      el.html( c );
      /** Add 'click' events for our links */
      el.find( 'li' ).one( 'click', function() {
        if( jQuery( this ).hasClass( 'dots' ) || typeof jQuery( this ).data( 'page' ) == 'undefined' ) {
          return false;
        }
        window.wpp_query[ vars.unique_id ] = changeAddressValue( jQuery( this ).data( 'page' ), window.wpp_query[ vars.unique_id ] );
      } );
    }

    /**
     * Run pagination
     *
     * @param unique_id
     * @param this_page
     * @param scroll_to
     * @returns {boolean}
     */
    function do_ajax_pagination( unique_id, type, this_page, scroll_to ) {
      if( typeof this_page == 'undefined' ) {
        return false;
      }
      if( typeof type == 'undefined' ) {
        return false;
      }
      if ( typeof this_page == 'undefined' ) {
        this_page = 1;
      }
      if ( typeof scroll_to == 'undefined' ) {
        scroll_to = true;
      }

      switch( type ) {
        case 'slider':
          // Do nothing
          break;
        case 'numeric':
          // Update our numeric pagination!
          prepare_numeric_pagination_ui( this_page, unique_id );
          break;
      }

      /* Update page counter */
      jQuery( "#wpp_shortcode_" + unique_id +  " .wpp_current_page_count" ).text( this_page );
      jQuery( "#wpp_shortcode_" + unique_id +  " .wpp_pagination_slider .slider_page_info .val" ).text( this_page );
      /* Update sliders  */
      jQuery( "#wpp_shortcode_" + unique_id +  " .wpp_pagination_slider" ).slider( "value", this_page );
      jQuery( '#wpp_shortcode_' + unique_id +  ' .ajax_loader' ).show();
      /* Scroll page to the top of the current shortcode */
      if ( scroll_to ) {
        jQuery( document ).trigger( 'wpp_pagination_change', {'overview_id': unique_id} );
      }

      data = window.wpp_query[ unique_id ];
      data.ajax_call = 'true';
      data.requested_page = this_page;

      jQuery.post( vars.ajax_url, {
        action: 'wpp_property_overview_pagination',
        wpp_ajax_query: data
      }, function ( result_data ) {
        jQuery( '#wpp_shortcode_' + unique_id +  ' .ajax_loader' ).hide();

        var p_list = jQuery( '.wpp_property_view_result', result_data.display );
        //* Determine if p_list is empty try previous version's selector */
        if ( p_list.length == 0 ) {
          p_list = jQuery( '.wpp_row_view', result_data.display );
        }

        var content = ( p_list.length > 0 ) ? p_list.html() : result_data.display;
        var p_wrapper = jQuery( '#wpp_shortcode_' + unique_id +  ' .wpp_property_view_result' );

        //* Determine if p_wrapper is empty try previous version's selector */
        if ( p_wrapper.length == 0 ) {
          p_wrapper = jQuery( '#wpp_shortcode_' + unique_id +  ' .wpp_row_view' )
        }

        p_wrapper.html( content );

        /* Update max page in slider and in display */
        if( vars.use_pagination ) {

          switch( type ) {

            case 'slider':
              jQuery( "#wpp_shortcode_" + unique_id +  " .wpp_pagination_slider" ).slider( "option", "max", result_data.wpp_query.pages );
              jQuery( "#wpp_shortcode_" + unique_id +  " .wpp_total_page_count" ).text( result_data.wpp_query.pages );
              max_slider_pos = result_data.wpp_query.pages;
              if ( max_slider_pos == 0 ) jQuery( "#wpp_shortcode_" + unique_id + " .wpp_current_page_count" ).text( 0 );
              break;

            case 'numeric':
              last_property = parseInt(result_data.wpp_query.starting_row) + parseInt(result_data.wpp_query.per_page);
              last_property = last_property > parseInt(result_data.wpp_query.properties.total) ? parseInt(result_data.wpp_query.properties.total) : last_property;
              jQuery("#wpp_shortcode_" + unique_id +  " .wpp_display_from_property").text(parseInt(result_data.wpp_query.starting_row) + 1);
              jQuery("#wpp_shortcode_" + unique_id +  " .wpp_display_to_property").text(last_property);
              break;

          }

        }

        jQuery( "#wpp_shortcode_" + unique_id +  " a.fancybox_image" ).fancybox( {
          'transitionIn': 'elastic',
          'transitionOut': 'elastic',
          'speedIn': 600,
          'speedOut': 200,
          'overlayShow': false
        } );

        jQuery( document ).trigger( 'wpp_pagination_change_complete', {'overview_id': unique_id} );

      }, "json" );

    }

    /**
     *
     */
    function init_slider_pagination() {

      if ( !jQuery.isFunction( jQuery.fn.slider ) ) {
        jQuery( ".wpp_pagination_slider_wrapper" ).hide();
        return null;
      }

      //** Do not assign click event again */
      if ( !jQuery( '#wpp_shortcode_' + vars.unique_id +  ' .wpp_pagination_back' ).data( 'events' ) ) {
        jQuery( '#wpp_shortcode_' + vars.unique_id +  ' .wpp_pagination_back' ).click( function () {
          var current_value = jQuery( "#wpp_shortcode_" + vars.unique_id +  " .wpp_pagination_slider" ).slider( "value" );
          if ( current_value == 1 ) {
            return;
          }
          var new_value = current_value - 1;
          jQuery( "#wpp_shortcode_" + vars.unique_id +  " .wpp_pagination_slider" ).slider( "value", new_value );
          window.wpp_query[ vars.unique_id ] = changeAddressValue( new_value, window.wpp_query[ vars.unique_id ] );
        } );
      }

      if ( !jQuery( '#wpp_shortcode_' + vars.unique_id +  ' .wpp_pagination_forward' ).data( 'events' ) ) {
        jQuery( '#wpp_shortcode_' + vars.unique_id +  ' .wpp_pagination_forward' ).click( function () {
          var current_value = jQuery( "#wpp_shortcode_" + vars.unique_id +  " .wpp_pagination_slider" ).slider( "value" );
          if ( max_slider_pos && (current_value == max_slider_pos || max_slider_pos < 1 ) ) {
            return;
          }
          var new_value = current_value + 1;
          jQuery( "#wpp_shortcode_" + vars.unique_id +  " .wpp_pagination_slider" ).slider( "value", new_value );
          window.wpp_query[ vars.unique_id ] = changeAddressValue( new_value, window.wpp_query[ vars.unique_id ] );
        } );
      }

      jQuery( "#wpp_shortcode_" + vars.unique_id +  " .wpp_pagination_slider_wrapper" ).each( function () {
        var this_parent = this;
        /* Slider */
        jQuery( '.wpp_pagination_slider', this ).slider( {
          value: 1,
          min: 1,
          max: vars.pages,
          step: 1,
          slide: function ( event, ui ) {
            /* Update page counter - we do it here because we want it to be instant */
            jQuery( "#wpp_shortcode_" + vars.unique_id +  " .wpp_current_page_count" ).text( ui.value );
            jQuery( "#wpp_shortcode_" + vars.unique_id +  " .wpp_pagination_slider .slider_page_info .val" ).text( ui.value );
          },
          stop: function ( event, ui ) {
            window.wpp_query[ vars.unique_id ] = changeAddressValue( ui.value, window.wpp_query[ vars.unique_id ] );
          }

        } );

        /* Fix slider width based on button width */
        var slider_width = (jQuery( this_parent ).width() - jQuery( ".wpp_pagination_back", this_parent ).outerWidth() - jQuery( ".wpp_pagination_forward", this_parent ).outerWidth() - 30);
        jQuery( ".wpp_pagination_slider", this_parent ).css( 'width', slider_width );

        jQuery( '.wpp_pagination_slider .ui-slider-handle', this ).append( '<div class="slider_page_info"><div class="val">1</div><div class="arrow"></div></div>' );

      } );

    }

    /**
     *
     */
    function init_numeric_pagination() {
      prepare_numeric_pagination_ui( 1, vars.unique_id );
    }

    /**
     * EVENTS
     */
    jQuery( document ).ready( function () {

      /* Watch for address URL for back buttons support */
      if ( !window.wpp_pagination_history_ran ) {
        window.wpp_pagination_history_ran = true;
        /*
         * On change location (address) Event.
         *
         * Also used as Back button functionality.
         *
         * Attention! This event is unique (binds at once) and is used for any (multiple) shortcodes
         */
        jQuery.address.change( function ( event ) {
          callPagination( event );
        } );
      }

      window.wpp_overview_document_ready = true;

      max_slider_pos = vars.pages;

      if( vars.use_pagination ) {
        switch (vars.type) {
          case 'slider':
            init_slider_pagination();
            break;
          case 'numeric':
            init_numeric_pagination();
            break;
        }
      }

      if ( !jQuery( '#wpp_shortcode_' + vars.unique_id +  ' .wpp_sortable_link' ).data( 'events' ) ) {
        jQuery( '#wpp_shortcode_' + vars.unique_id +  ' .wpp_sortable_link' ).click( function () {
          var attribute = jQuery( this ).attr( 'sort_slug' );
          var sort_order = jQuery( this ).attr( 'sort_order' );
          var this_attribute = jQuery( "#wpp_shortcode_" + vars.unique_id +  " .wpp_sortable_link[sort_slug=" + attribute + "]" );
          if ( jQuery( this ).is( ".wpp_sorted_element" ) ) {
            var currently_sorted = true;
            /* If this attribute is already sorted, we switch sort order */
            if ( sort_order == "ASC" ) {
              sort_order = "DESC";
            } else if ( sort_order == "DESC" ) {
              sort_order = "ASC";
            }
          }
          jQuery( "#wpp_shortcode_" + vars.unique_id +  " .wpp_sortable_link" ).removeClass( "wpp_sorted_element" );
          window.wpp_query[ vars.unique_id ].sort_by = attribute;
          window.wpp_query[ vars.unique_id ].sort_order = sort_order;
          jQuery( this_attribute ).addClass( "wpp_sorted_element" );
          jQuery( this_attribute ).attr( "sort_order", sort_order );
          /* Get ajax results and reset to first page */
          window.wpp_query[ vars.unique_id ] = changeAddressValue( 1, window.wpp_query[ vars.unique_id ] );
        } );
      }

      if ( !jQuery( '#wpp_shortcode_' + vars.unique_id +  ' .wpp_sortable_dropdown' ).data( 'events' ) ) {
        jQuery( '#wpp_shortcode_' + vars.unique_id +  ' .wpp_sortable_dropdown' ).change( function () {
          var parent = jQuery( this ).parents( '.wpp_sorter_options' );
          var attribute = jQuery( ":selected", this ).attr( 'sort_slug' );
          var sort_element = jQuery( ".sort_order", parent );
          var sort_order = jQuery( sort_element ).attr( 'sort_order' );
          window.wpp_query[ vars.unique_id ].sort_by = attribute;
          window.wpp_query[ vars.unique_id ].sort_order = sort_order;
          /* Get ajax results and reset to first page */
          window.wpp_query[ vars.unique_id ] = changeAddressValue( 1, window.wpp_query[ vars.unique_id ] );
        } );
      }

      if ( !jQuery( '#wpp_shortcode_' + vars.unique_id +  ' .wpp_overview_sorter' ).data( 'events' ) ) {
        jQuery( '#wpp_shortcode_' + vars.unique_id +  ' .wpp_overview_sorter' ).click( function () {
          var parent = jQuery( this ).parents( '.wpp_sorter_options' );
          var sort_element = this;
          var dropdown_element = jQuery( ".wpp_sortable_dropdown", parent );
          var attribute = jQuery( ":selected", dropdown_element ).attr( 'sort_slug' );
          var sort_order = jQuery( sort_element ).attr( 'sort_order' );
          jQuery( sort_element ).removeClass( sort_order );
          /* If this attribute is already sorted, we switch sort order */
          if ( sort_order == "ASC" ) {
            sort_order = "DESC";
          } else if ( sort_order == "DESC" ) {
            sort_order = "ASC";
          }
          window.wpp_query[ vars.unique_id ].sort_by = attribute;
          window.wpp_query[ vars.unique_id ].sort_order = sort_order;
          jQuery( sort_element ).attr( "sort_order", sort_order );
          jQuery( sort_element ).addClass( sort_order );
          /* Get ajax results and reset to first page */
          window.wpp_query[ vars.unique_id ] = changeAddressValue( 1, window.wpp_query[ vars.unique_id ] );
        } );
      }

    } );

  };

} )( jQuery, _wpp_overview_pagination );