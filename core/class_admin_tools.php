<?php
/*
Name: Admin Tools
Feature ID: 1
Minimum Core Version: 1.36.0
Version: 3.5.2
Description: Tools for developing themes and extensions for WP-Property.
Class: class_admin_tools
*/

add_action( 'wpp_init', array( 'class_admin_tools', 'init' ) );
add_action( 'wpp_pre_init', array( 'class_admin_tools', 'pre_init' ) );

if( !class_exists( 'class_admin_tools' ) ) {

  /**
   * class_admin_tools Class
   *
   * Contains administrative functions
   *
   * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
   *
   * @version 1.0
   * @author Andy Potanin <andy.potanin@twincitiestech.com>
   * @package WP-Property
   * @subpackage Admin Functions
   */
  class class_admin_tools {

    /*
     * (custom) Capability to manage the current feature
     */
    static protected $capability = "manage_wpp_admintools";

    /**
     * Special functions that must be called prior to init
     *
     */
    static function pre_init() {
      /* Add capability */
      add_filter( 'wpp_capabilities', array( 'class_admin_tools', "add_capability" ) );
    }

    /*
     * Apply feature's Hooks and other functionality
     */
    static function init() {

      if( current_user_can( self::$capability ) ) {
        //** Add Inquiry page to Property Settings page array */
        add_filter( 'wpp_settings_nav', array( 'class_admin_tools', 'settings_nav' ) );
        //** Add Settings Page */
        add_action( 'wpp_settings_content_admin_tools', array( 'class_admin_tools', 'settings_page' ) );
        //** Contextual Help */
        add_action( 'property_page_property_settings_help', array( 'class_admin_tools', 'wpp_contextual_help' ) );
      }

    }

    /*
     * Adds Custom capability to the current premium feature
     */
    static function add_capability( $capabilities ) {

      $capabilities[ self::$capability ] = __( 'Manage Admin Tools', 'wpp' );

      return $capabilities;
    }

    /**
     * Add Contextual help item
     *
     * @param type $data
     *
     * @return string
     * @author korotkov@ud
     */
    static function wpp_contextual_help( $data ) {

      $data[ 'Developer' ][ ] = '<h3>' . __( 'Developer', 'wpp' ) . '</h3>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'The <b>slug</b> is automatically created from the title and is used in the back-end.  It is also used for template selection, example: floorplan will look for a template called property-floorplan.php in your theme folder, or default to property.php if nothing is found.' ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'If <b>Searchable</b> is checked then the property will be loaded for search, and available on the property search widget.' ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'If <b>Location Matters</b> is checked, then an address field will be displayed for the property, and validated against Google Maps API.  Additionally, the property will be displayed on the SuperMap, if the feature is installed.' ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( '<b>Hidden Attributes</b> determine which attributes are not applicable to the given property type, and will be grayed out in the back-end.' ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( '<b>Inheritance</b> determines which attributes should be automatically inherited from the parent property' ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'Property attributes are meant to be short entries that can be searchable, on the back-end attributes will be displayed as single-line input boxes. On the front-end they are displayed using a definitions list.' ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'Making an attribute as "searchable" will list it as one of the searchable options in the Property Search widget settings.' ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'Be advised, attributes added via add_filter() function supercede the settings on this page.' ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( '<b>Search Input:</b> Select and input type and enter comma-separated values that you would like to be used in property search, on the front-end.', 'wpp' ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( '<b>Data Entry:</b> Enter comma-separated values that you would like to use on the back-end when editing properties.', 'wpp' ) . '</p>';

      return $data;

    }

    /**
     * Adds admin tools manu to settings page navigation
     *
     * @version 1.0
     * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
     */
    static function settings_nav( $tabs ) {

      $tabs[ 'admin_tools' ] = array(
        'slug'  => 'admin_tools',
        'title' => __( 'Developer', 'wpp' )
      );

      return $tabs;
    }

    /**
     * Displays advanced management page
     *
     *
     * @version 1.0
     * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
     */
    static function settings_page() {
      global $wpdb, $wp_properties;

      $wpp_inheritable_attributes = $wp_properties[ 'property_stats' ];

      ?>

      <script type="text/javascript">
      var geo_type_attrs = <?php echo json_encode((array)$wp_properties['geo_type_attributes']); ?>

        jQuery( document ).ready( function() {

          jQuery( "#wpp_inquiry_attribute_fields tbody" ).sortable( {
            delay: 200
          } );

          jQuery( "#wpp_inquiry_meta_fields tbody" ).sortable( {
            delay: 200
          } );

          jQuery( "#wpp_inquiry_attribute_fields tbody tr, #wpp_inquiry_meta_fields tbody tr" ).live( "mouseover", function() {
            jQuery( this ).addClass( "wpp_draggable_handle_show" );
          } );
          ;

          jQuery( "#wpp_inquiry_attribute_fields tbody tr, #wpp_inquiry_meta_fields tbody tr" ).live( "mouseout", function() {
            jQuery( this ).removeClass( "wpp_draggable_handle_show" );
          } );
          ;

          /* Show advanced settings for an attribute when a certain value is changed */

          /*
           jQuery(".wpp_searchable_attr_fields").live("change", function() {
           var parent = jQuery(this).closest(".wpp_dynamic_table_row");
           jQuery(".wpp_development_advanced_option", parent).show();
           });
           */

          jQuery( ".wpp_all_advanced_settings" ).live( "click", function() {
            var action = jQuery( this ).attr( "action" );

            if( action == "expand" ) {
              jQuery( "#wpp_inquiry_attribute_fields .wpp_development_advanced_option" ).show();
            }

            if( action == "collapse" ) {
              jQuery( "#wpp_inquiry_attribute_fields .wpp_development_advanced_option" ).hide();
            }

          } )

          //* Stats to group functionality */
          jQuery( '.wpp_attribute_group' ).wppGroups();

          //* Fire Event after Row is added */
          jQuery( '#wpp_inquiry_attribute_fields tr' ).live( 'added', function() {
            //* Remove notice block if it exists */
            var notice = jQuery( this ).find( '.wpp_notice' );
            if( notice.length > 0 ) {
              notice.remove();
            }
            //* Unassign Group from just added Attribute */
            jQuery( 'input.wpp_group_slug', this ).val( '' );
            this.removeAttribute( 'wpp_attribute_group' );

            //* Remove background-color from the added row if it's set */
            if( typeof jQuery.browser.msie != 'undefined' && (parseInt( jQuery.browser.version ) == 9) ) {
              //* HACK FOR IE9 (it's just unset background color) peshkov@UD: */
              setTimeout( function() {
                var lr = jQuery( '#wpp_inquiry_attribute_fields tr.wpp_dynamic_table_row' ).last();
                var bc = lr.css( 'background-color' );
                lr.css( 'background-color', '' );
                jQuery( document ).bind( 'mousemove', function() {
                  setTimeout( function() {
                    lr.prev().css( 'background-color', bc );
                  }, 50 );
                  jQuery( document ).unbind( 'mousemove' );
                } );
              }, 50 );
            } else {
              jQuery( this ).css( 'background-color', '' );
            }

            //* Stat to group functionality */
            jQuery( this ).find( '.wpp_attribute_group' ).wppGroups();

          } );

          //* Determine if slug of property stat is the same as Geo Type has and show notice */
          jQuery( '#wpp_inquiry_attribute_fields tr .wpp_stats_slug_field' ).live( 'change', function() {
            var slug = jQuery( this ).val();
            var geo_type = false;
            if( typeof geo_type_attrs == 'object' ) {
              for( var i in geo_type_attrs ) {
                if( slug == geo_type_attrs[i] ) {
                  geo_type = true;
                  break;
                }
              }
            }
            var notice = jQuery( this ).parent().find( '.wpp_notice' );
            if( geo_type ) {
              if( !notice.length > 0 ) {
                //* Toggle Advanced option to show notice */
                var advanced_options = (jQuery( this ).parents( 'tr.wpp_dynamic_table_row' ).find( '.wpp_development_advanced_option' ));
                if( advanced_options.length > 0 ) {
                  if( jQuery( advanced_options.get( 0 ) ).is( ':hidden' ) ) {
                    jQuery( this ).parents( 'tr.wpp_dynamic_table_row' ).find( '.wpp_show_advanced' ).trigger( 'click' );
                  }
                }
                jQuery( this ).parent().append( '<div class="wpp_notice"></div>' );
                notice = jQuery( this ).parent().find( '.wpp_notice' );
              }
              notice.html( '<span><?php _e('Attention! This attribute (slug) is used by Google Validator and Address Display functionality. It is set automaticaly and can not be edited on Property Adding/Updating page.','wpp'); ?></span>' );
            } else {
              if( notice.length > 0 ) {
                notice.remove();
              }
            }
          } );

          jQuery( ".wpp_pre_defined_value_setter" ).live( "change", function() {
            set_pre_defined_values_for_attribute( this );
          } );

          jQuery( ".wpp_pre_defined_value_setter" ).each( function() {
            set_pre_defined_values_for_attribute( this );
          } );

          function set_pre_defined_values_for_attribute( setter_element ) {

            var wrapper = jQuery( setter_element ).closest( "ul" );
            var setting = jQuery( setter_element ).val();
            var value_field = jQuery( "textarea.wpp_attribute_pre_defined_values", wrapper );

            switch( setting ) {

              case 'input':
                jQuery( value_field ).hide();
                break;

              case 'range_input':
                jQuery( value_field ).hide();
                break;

              case 'dropdown':
                jQuery( value_field ).show();
                break;

              case 'checkbox':
                jQuery( value_field ).hide();
                break;

              case 'multi_checkbox':
                jQuery( value_field ).show();
                break;

              default:
                jQuery( value_field ).hide();

            }

          }

        } );
    </script>
      <style type="style/text">
    #wpp_inquiry_attribute_fields tbody tr {
      cursor: move;
    }

    #wpp_inquiry_meta_fields tbody tr {
      cursor: move;
    }
    </style>


      <table class="form-table">

      <tr>
        <td>
          <div>
            <h3 style="float:left;"><?php printf( __( '%1s Attributes', 'wpp' ), WPP_F::property_label() ); ?></h3>
            <span class="">
            <div class="wpp_property_stat_functions">
              <?php _e( 'Advanced Stats Settings:', 'wpp' ) ?>
              <span class="wpp_all_advanced_settings" action="expand"><?php _e( 'expand all', 'wpp' ) ?></span>,
              <span class="wpp_all_advanced_settings" action="collapse"><?php _e( 'collapse all', 'wpp' ) ?></span>.
              <input type="button" id="sort_stats_by_groups" class="button-secondary" value="<?php _e( 'Sort Stats by Groups', 'wpp' ) ?>"/>
            </div>
            <div class="clear"></div>
          </div>

          <div id="wpp_dialog_wrapper_for_groups"></div>
          <div id="wpp_attribute_groups">
              <table cellpadding="0" cellspacing="0" allow_random_slug="true" class="ud_ui_dynamic_table widefat wpp_sortable">
                <thead>
                  <tr>
                    <th class="wpp_group_assign_col">&nbsp;</th>
                    <th class='wpp_draggable_handle'>&nbsp;</th>
                    <th class="wpp_group_name_col"><?php _e( 'Group Name', 'wpp' ) ?></th>
                    <th class="wpp_group_slug_col"><?php _e( 'Slug', 'wpp' ) ?></th>
                    <th class='wpp_group_main_col'><?php _e( 'Main', 'wpp' ) ?></th>
                    <th class="wpp_group_color_col"><?php _e( 'Group Color', 'wpp' ) ?></th>
                    <th class="wpp_group_action_col">&nbsp;</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                if( empty( $wp_properties[ 'property_groups' ] ) ) {
                  //* If there is no any group, we set default */
                  $wp_properties[ 'property_groups' ] = array(
                    'main' => array(
                      'name'  => 'Main',
                      'color' => '#bdd6ff'
                    )
                  );
                }
                ?>
                <?php foreach( $wp_properties[ 'property_groups' ] as $slug => $group ): ?>
                  <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row='false'>
                    <td class="wpp_group_assign_col">
                      <input type="button" class="wpp_assign_to_group button-secondary" value="<?php _e( 'Assign', 'wpp' ) ?>"/>
                    </td>
                    <td class="wpp_draggable_handle">&nbsp;</td>
                    <td class="wpp_group_name_col">
                      <input class="slug_setter" type="text" name="wpp_settings[property_groups][<?php echo $slug; ?>][name]" value="<?php echo $group[ 'name' ]; ?>"/>
                    </td>
                    <td class="wpp_group_slug_col">
                      <input type="text" class="slug" readonly='readonly' value="<?php echo $slug; ?>"/>
                    </td>
                    <td class="wpp_group_main_col">
                      <input type="radio" class="wpp_no_change_name" name="wpp_settings[configuration][main_stats_group]" <?php echo( isset( $wp_properties[ 'configuration' ][ 'main_stats_group' ] ) && $wp_properties[ 'configuration' ][ 'main_stats_group' ] == $slug ? "checked=\"checked\"" : "" ); ?> value="<?php echo $slug; ?>"/>
                    </td>
                    <td class="wpp_group_color_col">
                      <input type="text" class="wpp_input_colorpicker" name="wpp_settings[property_groups][<?php echo $slug; ?>][color]" value="<?php echo $group[ 'color' ]; ?>"/>
                    </td>
                    <td class="wpp_group_action_col">
                      <span class="wpp_delete_row wpp_link"><?php _e( 'Delete', 'wpp' ) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan='7'>
                      <div style="float:left;text-align:left;">
                        <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Group', 'wpp' ) ?>"/>
                        <input type="button" class="wpp_unassign_from_group button-secondary" value="<?php _e( 'Unassign from Group', 'wpp' ) ?>"/>
                      </div>
                      <div style="float:right;">
                        <input type="button" class="wpp_close_dialog button-secondary" value="<?php _e( 'Apply', 'wpp' ) ?>"/>
                      </div>
                      <div class="clear"></div>
                    </td>
                  </tr>
                </tfoot>
              </table>
          </div>

          <table id="wpp_inquiry_attribute_fields" class="ud_ui_dynamic_table widefat" allow_random_slug="true">
          <thead>
            <tr>
              <th class='wpp_draggable_handle'>&nbsp;</th>
              <th class='wpp_attribute_name_col'><?php _e( 'Attribute Name', 'wpp' ) ?></th>
              <th class='wpp_attribute_group_col'><?php _e( 'Group', 'wpp' ) ?></th>
              <th class='wpp_settings_input_col'><?php _e( 'Settings', 'wpp' ) ?></th>
              <th class='wpp_search_input_col'><?php _e( 'Search Input', 'wpp' ) ?></th>
              <th class='wpp_admin_input_col'><?php _e( 'Data Entry', 'wpp' ) ?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach( $wp_properties[ 'property_stats' ] as $slug => $label ): ?>
            <?php $gslug = false; ?>
            <?php $group = false; ?>
            <?php if( !empty( $wp_properties[ 'property_stats_groups' ][ $slug ] ) ) : ?>
              <?php $gslug = $wp_properties[ 'property_stats_groups' ][ $slug ]; ?>
              <?php $group = $wp_properties[ 'property_groups' ][ $gslug ]; ?>
            <?php endif; ?>
            <tr class="wpp_dynamic_table_row" <?php echo( !empty( $gslug ) ? "wpp_attribute_group=\"" . $gslug . "\"" : "" ); ?> style="<?php echo( !empty( $group[ 'color' ] ) ? "background-color:" . $group[ 'color' ] : "" ); ?>" slug="<?php echo $slug; ?>" new_row='false'>

            <td class="wpp_draggable_handle">&nbsp;</td>

            <td class="wpp_attribute_name_col">
              <ul class="wpp_attribute_name">
                <li>
                  <input class="slug_setter" type="text" name="wpp_settings[property_stats][<?php echo $slug; ?>]" value="<?php echo $label; ?>"/>
                </li>
                <li class="wpp_development_advanced_option">
                  <input type="text" class="slug wpp_stats_slug_field" readonly='readonly' value="<?php echo $slug; ?>"/>
                  <?php if( in_array( $slug, $wp_properties[ 'geo_type_attributes' ] ) ): ?>
                    <div class="wpp_notice">
                    <span><?php _e( 'Attention! This attribute (slug) is used by Google Validator and Address Display functionality. It is set automaticaly and can not be edited on Property Adding/Updating page.', 'wpp' ); ?></span>
                  </div>
                  <?php endif; ?>
                </li>
                <?php do_action( 'wpp::property_attributes::attribute_name', $slug ); ?>
                <li>
                  <span class="wpp_show_advanced"><?php _e( 'Toggle Advanced Settings', 'wpp' ); ?></span>
                </li>
              </ul>
            </td>

            <td class="wpp_attribute_group_col">
              <input type="text" class="wpp_attribute_group" value="<?php echo( !empty( $group[ 'name' ] ) ? $group[ 'name' ] : "" ); ?>"/>
              <input type="hidden" class="wpp_group_slug" name="wpp_settings[property_stats_groups][<?php echo $slug; ?>]" value="<?php echo( !empty( $gslug ) ? $gslug : "" ); ?>">
            </td>

            <td class="wpp_settings_input_col">
              <ul>
                <li>
                  <label>
                    <input <?php if( in_array( $slug, ( ( !empty( $wp_properties[ 'sortable_attributes' ] ) ? $wp_properties[ 'sortable_attributes' ] : array() ) ) ) ) echo " CHECKED "; ?> type="checkbox" class="slug" name="wpp_settings[sortable_attributes][]" value="<?php echo $slug; ?>"/>
                    <?php _e( 'Sortable.', 'wpp' ); ?>
                  </label>
                </li>
                <li>
                  <label>
                    <input <?php echo ( isset( $wp_properties[ 'searchable_attributes' ] ) && is_array( $wp_properties[ 'searchable_attributes' ] ) && in_array( $slug, $wp_properties[ 'searchable_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[searchable_attributes][]" value="<?php echo $slug; ?>"/>
                    <?php _e( 'Searchable.', 'wpp' ); ?>
                  </label>
                </li>
                <li class="wpp_development_advanced_option">
                  <label>
                    <input <?php echo ( isset( $wp_properties[ 'hidden_frontend_attributes' ] ) && is_array( $wp_properties[ 'hidden_frontend_attributes' ] ) && in_array( $slug, $wp_properties[ 'hidden_frontend_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[hidden_frontend_attributes][]" value="<?php echo $slug; ?>"/>
                    <?php _e( 'Admin Only.', 'wpp' ); ?>
                  </label>
                </li>
                <li class="wpp_development_advanced_option">
                  <label>
                    <input <?php echo ( isset( $wp_properties[ 'numeric_attributes' ] ) && is_array( $wp_properties[ 'numeric_attributes' ] ) && in_array( $slug, $wp_properties[ 'numeric_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[numeric_attributes][]" value="<?php echo $slug; ?>"/>
                    <?php _e( 'Format: numeric.', 'wpp' ); ?>
                  </label>
                </li>
                <li class="wpp_development_advanced_option">
                  <label>
                    <input <?php echo ( isset( $wp_properties[ 'currency_attributes' ] ) && is_array( $wp_properties[ 'currency_attributes' ] ) && in_array( $slug, $wp_properties[ 'currency_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[currency_attributes][]" value="<?php echo $slug; ?>"/>
                    <?php _e( 'Format: currency.', 'wpp' ); ?>
                  </label>
                </li>
                <li class="wpp_development_advanced_option">
                  <label>
                    <input <?php echo ( isset( $wp_properties[ 'column_attributes' ] ) && is_array( $wp_properties[ 'column_attributes' ] ) && in_array( $slug, $wp_properties[ 'column_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[column_attributes][]" value="<?php echo $slug; ?>"/>
                    <?php _e( 'Show in "All Properties" table.', 'wpp' ); ?>
                  </label>
                </li>
                <?php do_action( 'wpp::property_attributes::settings', $slug ); ?>
                <li class="wpp_development_advanced_option">
                  <span class="wpp_delete_row wpp_link"><?php _e( 'Delete Attribute', 'wpp' ) ?></span>
                </li>
              </ul>
            </td>

            <td class="wpp_search_input_col">
              <ul>
                <li>
                  <select name="wpp_settings[searchable_attr_fields][<?php echo $slug; ?>]" class="wpp_pre_defined_value_setter wpp_searchable_attr_fields">
                    <option value=""> - </option>
                    <option value="input" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'input' ); ?>><?php _e( 'Free Text', 'wpp' ) ?></option>
                    <option value="range_input" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'range_input' ); ?>><?php _e( 'Text Input Range', 'wpp' ) ?></option>
                    <option value="range_dropdown" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'range_dropdown' ); ?>><?php _e( 'Range Dropdown', 'wpp' ) ?></option>
                    <option value="dropdown" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'dropdown' ); ?>><?php _e( 'Dropdown Selection', 'wpp' ) ?></option>
                    <option value="checkbox" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'checkbox' ); ?>><?php _e( 'Single Checkbox', 'wpp' ) ?></option>
                    <option value="multi_checkbox" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'multi_checkbox' ); ?>><?php _e( 'Multi-Checkbox', 'wpp' ) ?></option>
                    <?php do_action( 'wpp::property_attributes::searchable_attr_field', $slug ); ?>
                  </select>
                </li>
                <li>
                  <textarea class="wpp_attribute_pre_defined_values" name="wpp_settings[predefined_search_values][<?php echo $slug; ?>]"><?php echo isset( $wp_properties[ 'predefined_search_values' ][ $slug ] ) ? $wp_properties[ 'predefined_search_values' ][ $slug ] : ''; ?></textarea>
                </li>
              </ul>
            </td>

            <td class="wpp_admin_input_col">
              <ul>
                <li>
                  <select name="wpp_settings[admin_attr_fields][<?php echo $slug; ?>]" class="wpp_pre_defined_value_setter wpp_searchable_attr_fields">
                    <option value=""> - </option>
                    <option value="input" <?php if( isset( $wp_properties[ 'admin_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'admin_attr_fields' ][ $slug ], 'input' ); ?>><?php _e( 'Free Text', 'wpp' ) ?></option>
                    <option value="dropdown" <?php if( isset( $wp_properties[ 'admin_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'admin_attr_fields' ][ $slug ], 'dropdown' ); ?>><?php _e( 'Dropdown Selection', 'wpp' ) ?></option>
                    <option value="checkbox" <?php if( isset( $wp_properties[ 'admin_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'admin_attr_fields' ][ $slug ], 'checkbox' ); ?>><?php _e( 'Single Checkbox', 'wpp' ) ?></option>
                    <?php do_action( 'wpp::property_attributes::admin_attr_field', $slug ); ?>
                  </select>
                </li>
                <li>
                  <textarea class="wpp_attribute_pre_defined_values" name="wpp_settings[predefined_values][<?php echo $slug; ?>]"><?php echo isset( $wp_properties[ 'predefined_values' ][ $slug ] ) ? $wp_properties[ 'predefined_values' ][ $slug ] : ''; ?></textarea>
                </li>
              </ul>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>

          <tfoot>
            <tr>
              <td colspan='6'>
              <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', 'wpp' ) ?>"/>
              </td>
            </tr>
          </tfoot>

          </table>
          <br class="cb"/>
          <h3><?php printf( __( '%1s Meta', 'wpp' ), WPP_F::property_label() ); ?></h3>
          <p><?php _e( 'Meta is used for descriptions,  on the back-end  meta fields will be displayed as textareas.  On the front-end they will be displayed as individual sections.', 'wpp' ) ?></p>

          <table id="wpp_inquiry_meta_fields" class="ud_ui_dynamic_table widefat">
          <thead>
            <tr>
              <th class='wpp_draggable_handle'>&nbsp;</th>
              <th class='wpp_attribute_name_col'><?php _e( 'Attribute Name', 'wpp' ) ?></th>
              <th class='wpp_attribute_slug_col'><?php _e( 'Attribute Slug', 'wpp' ) ?></th>
              <th class='wpp_settings_col'><?php _e( 'Settings', 'wpp' ) ?></th>
              <th class='wpp_delete_col'>&nbsp;</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach( $wp_properties[ 'property_meta' ] as $slug => $label ): ?>

            <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row='false'>
            <th class='wpp_draggable_handle'>&nbsp;</th>
            <td>
             <ul>
              <li>
                 <input class="slug_setter" type="text" name="wpp_settings[property_meta][<?php echo $slug; ?>]" value="<?php echo $label; ?>"/>
              </li>
              </ul>
            <td>
              <ul>
              <li>
                 <input type="text" class="slug" readonly='readonly' value="<?php echo $slug; ?>"/>
              </li>
              </ul>
            </td>
            <td>
              <ul>
                </li>
                <input <?php echo ( isset( $wp_properties[ 'hidden_frontend_attributes' ] ) && in_array( $slug, (array)$wp_properties[ 'hidden_frontend_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[hidden_frontend_attributes][]" value="<?php echo $slug; ?>"/>
                <label><?php _e( 'Show in Admin Only', 'wpp' ); ?></label>
                </li>
              </ul>
            </td>

              <td>
              <span class="wpp_delete_row wpp_link"><?php _e( 'Delete Meta Attribute', 'wpp' ) ?></span>
              </td>
          </tr>

          <?php endforeach; ?>
          </tbody>

          <tfoot>
            <tr>
              <td colspan='4'>
              <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', 'wpp' ) ?>"/>
              </td>
            </tr>
          </tfoot>

          </table>
        </td>
      </tr>

  <tr>
        <td>
          <h3><?php printf( __( '%1s Types', 'wpp' ), WPP_F::property_label() ); ?></h3>
          <table id="wpp_inquiry_property_types" class="ud_ui_dynamic_table widefat" allow_random_slug="true">
          <thead>
            <tr>
              <th><?php _e( 'Type', 'wpp' ) ?></th>
              <th><?php _e( 'Slug', 'wpp' ) ?></th>
              <th><?php _e( 'Settings', 'wpp' ) ?></th>
              <th><?php _e( 'Hidden Attributes', 'wpp' ) ?></th>
              <th><?php _e( 'Inherit from Parent', 'wpp' ) ?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach( $wp_properties[ 'property_types' ] as $property_slug => $label ): ?>

            <tr class="wpp_dynamic_table_row" slug="<?php echo $property_slug; ?>" new_row='false'>
            <td>
              <input class="slug_setter" type="text" name="wpp_settings[property_types][<?php echo $property_slug; ?>]" value="<?php echo $label; ?>"/><br/>
              <span class="wpp_delete_row wpp_link">Delete</span>
            </td>
            <td>
              <input type="text" class="slug" readonly='readonly' value="<?php echo $property_slug; ?>"/>
            </td>

            <td>
              <ul>
                <li>
                  <label for="<?php echo $property_slug; ?>_searchable_property_types">
                    <input class="slug" id="<?php echo $property_slug; ?>_searchable_property_types" <?php if( is_array( $wp_properties[ 'searchable_property_types' ] ) && in_array( $property_slug, $wp_properties[ 'searchable_property_types' ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[searchable_property_types][]" value="<?php echo $property_slug; ?>"/>
                    <?php _e( 'Searchable', 'wpp' ) ?>
                  </label>
                </li>

                <li>
                  <label for="<?php echo $property_slug; ?>_location_matters">
                    <input class="slug" id="<?php echo $property_slug; ?>_location_matters"  <?php if( in_array( $property_slug, $wp_properties[ 'location_matters' ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[location_matters][]" value="<?php echo $property_slug; ?>"/>
                    <?php _e( 'Location Matters', 'wpp' ) ?>
                  </label>
                </li>
                <?php $property_type_settings = apply_filters( 'wpp_property_type_settings', array(), $property_slug ); ?>
                <?php foreach( (array) $property_type_settings as $property_type_setting ) : ?>
                  <li>
                  <?php echo $property_type_setting; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </td>

            <td>
              <ul class="wp-tab-panel wpp_hidden_property_attributes wpp_something_advanced_wrapper">

              <li class="wpp_show_advanced" wrapper="wpp_something_advanced_wrapper"><?php _e( 'Toggle Attributes Selection', 'wpp' ); ?></li>

                <?php foreach( $wp_properties[ 'property_stats' ] as $property_stat_slug => $property_stat_label ) : ?>
                  <li class="wpp_development_advanced_option">
                    <input id="<?php echo $property_slug . "_" . $property_stat_slug; ?>_hidden_attributes" <?php if( isset( $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) && in_array( $property_stat_slug, $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[hidden_attributes][<?php echo $property_slug; ?>][]" value="<?php echo $property_stat_slug; ?>"/>
                    <label for="<?php echo $property_slug . "_" . $property_stat_slug; ?>_hidden_attributes">
                      <?php echo $property_stat_label; ?>
                    </label>
                  </li>
                <?php endforeach; ?>

                <?php foreach( $wp_properties[ 'property_meta' ] as $property_meta_slug => $property_meta_label ) : ?>
                  <li class="wpp_development_advanced_option">
                    <input id="<?php echo $property_slug . "_" . $property_meta_slug; ?>_hidden_attributes" <?php if( isset( $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) && in_array( $property_meta_slug, $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[hidden_attributes][<?php echo $property_slug; ?>][]" value="<?php echo $property_meta_slug; ?>"/>
                    <label for="<?php echo $property_slug . "_" . $property_meta_slug; ?>_hidden_attributes">
                      <?php echo $property_meta_label; ?>
                    </label>
                  </li>
                <?php endforeach; ?>

                <?php if( empty( $wp_properties[ 'property_stats' ][ 'parent' ] ) ) : ?>
                  <li class="wpp_development_advanced_option">
                    <input id="<?php echo $property_slug; ?>parent_hidden_attributes" <?php if( isset( $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) && in_array( 'parent', $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[hidden_attributes][<?php echo $property_slug; ?>][]" value="parent"/>
                    <label for="<?php echo $property_slug; ?>parent_hidden_attributes"><?php _e( 'Parent Selection', 'wpp' ); ?></label>
                  </li>
                <?php endif; ?>

              </ul>
            </td>

             <td>
              <ul class="wp-tab-panel wpp_inherited_property_attributes wpp_something_advanced_wrapper">
                <li class="wpp_show_advanced" wrapper="wpp_something_advanced_wrapper"><?php _e( 'Toggle Attributes Selection', 'wpp' ); ?></li>
                <?php foreach( $wpp_inheritable_attributes as $property_stat_slug => $property_stat_label ): ?>
                  <li class="wpp_development_advanced_option">
                  <input id="<?php echo $property_slug . "_" . $property_stat_slug; ?>_inheritance" <?php if( isset( $wp_properties[ 'property_inheritance' ][ $property_slug ] ) && in_array( $property_stat_slug, $wp_properties[ 'property_inheritance' ][ $property_slug ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[property_inheritance][<?php echo $property_slug; ?>][]" value="<?php echo $property_stat_slug; ?>"/>
                  <label for="<?php echo $property_slug . "_" . $property_stat_slug; ?>_inheritance">
                    <?php echo $property_stat_label; ?>
                  </label>
                </li>
                <?php endforeach; ?>
                <li>
              </ul>
            </td>

          </tr>

          <?php endforeach; ?>
          </tbody>

          <tfoot>
            <tr>
              <td colspan='5'>
              <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', 'wpp' ) ?>"/>
              </td>
            </tr>
          </tfoot>

          </table>
        </td>
      </tr>


      <tr>
        <td>
          <h3><?php _e( 'Advanced Options', 'wpp' ); ?></h3>
          <ul>
            <li>
              <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][show_ud_log]&label=" . __( 'Show Log.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'show_ud_log' ] ) ? $wp_properties[ 'configuration' ][ 'show_ud_log' ] : false ) ); ?>
              <br/>
              <span class="description"><?php _e( 'The log is always active, but the UI is hidden.  If enabled, it will be visible in the admin sidebar.', 'wpp' ); ?></span>
            </li>
            <li>
              <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][allow_parent_deep_depth]&label=" . __( 'Enable \'Falls Under\' deep depth.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] ) ? $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] : false ) ); ?>
              <br/>
              <span class="description"><?php printf( __( 'Allows to set child %1s as parent.', 'wpp' ), WPP_F::property_label( 'singular' ) )  ?></span>
            </li>
            <li>
              <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][disable_automatic_feature_update]&label=" . __( 'Disable automatic feature updates.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'disable_automatic_feature_update' ] ) ? $wp_properties[ 'configuration' ][ 'disable_automatic_feature_update' ] : false ) ); ?>
              <br/>
              <span class="description"><?php _e( 'If disabled, feature updates will not be downloaded automatically.', 'wpp' ); ?></span>
            </li>
            <li>
              <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][disable_wordpress_postmeta_cache]&label=" . __( 'Disable WordPress update_post_caches() function.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'disable_wordpress_postmeta_cache' ] ) ? $wp_properties[ 'configuration' ][ 'disable_wordpress_postmeta_cache' ] : false ) ); ?>
              <br/>
              <span class="description"><?php printf( __('This may solve Out of Memory issues if you have a lot of %1s.','wpp'), WPP_F::property_label( 'plural' )); ?></span>
            </li>
            <li>
              <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][developer_mode]&label=" . __( 'Enable developer mode - some extra information displayed via Firebug console.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'developer_mode' ] ) ? $wp_properties[ 'configuration' ][ 'developer_mode' ] : false ) ); ?>
              <br/>
            </li>

          </ul>
        </td>
      </tr>



    </table>

    <?php
    }

  }

}
