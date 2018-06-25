<?php
/**
 * Default API Usage Examples
 */

// Widget address format
// add_filter( "wpp_stat_filter_{$wp_properties[ 'configuration' ]['address_attribute']}", "wpp_format_address_attribute", 0, 3 );

// Add post-thumbnails support
add_action( "after_setup_theme", function(){
  add_theme_support( 'post-thumbnails' );
} );

//** Add dollar sign to all attributes marked as currency */
if ( isset( $wp_properties[ 'currency_attributes' ] ) && is_array( $wp_properties[ 'currency_attributes' ] ) ) {
  foreach ( $wp_properties[ 'currency_attributes' ] as $attribute ) {
    add_filter( "wpp_stat_filter_{$attribute}", 'add_dollar_sign' );
  }
}

//** Format values as numeric if marked as numeric_attributes */
if ( isset( $wp_properties[ 'numeric_attributes' ] ) && is_array( $wp_properties[ 'numeric_attributes' ] ) ) {
  foreach ( $wp_properties[ 'numeric_attributes' ] as $attribute ) {
    add_filter( "wpp_stat_filter_{$attribute}", array( 'WPP_F', 'format_numeric' ) );
  }

  if ( in_array( 'area', $wp_properties[ 'numeric_attributes' ] ) ) {
    add_filter( "wpp_stat_filter_area", 'add_square_foot' );
  }
}

//** Format values for checkboxes */
if ( isset( $wp_properties['searchable_attr_fields'] ) && is_array( $wp_properties['searchable_attr_fields'] ) ) {
  foreach( $wp_properties['searchable_attr_fields'] as $key => $value ) {
    if ( $value == 'checkbox' ) {
      add_filter( "wpp_stat_filter_$key", function( $value ) {
        if( empty( $value ) || in_array( $value, array( '0', 'false' ) ) ) {
          $value = __( 'No', ud_get_wp_property('domain') );
        } else {
          $value = __( 'Yes', ud_get_wp_property('domain') );
        }
        return $value;
      } );
    }
  }
}

// Exclude hidden attributes from frontend
add_filter( 'wpp_get_property', 'wpp_exclude_hidden_attributes' );

add_filter( 'wpp_get_property', 'add_display_address' );

add_filter( 'wpp_property_inheritance', 'add_city_to_inheritance' );
add_filter( 'wpp_searchable_attributes', 'add_city_to_searchable' );

add_filter( 'wpp_property_stat_labels', 'wpp_unique_key_labels', 20 );

add_filter( 'the_password_form', 'wpp_password_protected_property_form' );

// Coordinate manual override
//add_filter( 'wpp_property_stats_input_' . $wp_properties[ 'configuration' ][ 'address_attribute' ], 'wpp_property_stats_input_address', 0, 3 );

  add_action('save_property', 'wpp_save_property_aggregated_data', 10, 2 );

//add_action("wpp_ui_after_attribute_{$wp_properties['configuration']['address_attribute']}", 'wpp_show_coords');
add_action( 'wpp_ui_after_attribute_price', 'wpp_show_week_month_selection' );

/**
 * Add labels to system-generated attributes that do not have custom-set values
 *
 * @since 1.22.0
 */
function wpp_unique_key_labels( $stats ) {

  if ( empty( $stats[ 'property_type' ] ) ) {
    $stats[ 'property_type' ] = sprintf( __( '%s Type', ud_get_wp_property()->domain ), \WPP_F::property_label() );
  }

  if ( empty( $stats[ 'city' ] ) ) {
    $stats[ 'city' ] = __( 'City', ud_get_wp_property()->domain );
  }

  return $stats;

}

function wpp_password_protected_property_form( $output ) {
  global $post;

  if ( $post->post_type != 'property' )
    return $output;

  return str_replace( "This post is password protected", "This property is password protected", $output );
}

/**
 * Formats address on print.  If address it not formatted, makes an on-the-fly call to GMaps for validation.
 *
 *
 * @since 1.04
 */
function wpp_format_address_attribute( $data, $property = false, $format = "[street_number] [street_name], [city], [state]" ) {
  global $wp_properties;

  if ( !is_object( $property ) ) {
    return $data;
  }

  $currenty_address = $property->$wp_properties[ 'configuration' ][ 'address_attribute' ];

  //** If the currently requested properties address has not been formatted, and on-the-fly geo-lookup has not been disabled, try to look up now */
  if ( 
    ( !isset( $property->address_is_formatted ) || !$property->address_is_formatted ) 
    && $wp_properties[ 'configuration' ][ 'do_not_automatically_geo_validate_on_property_view' ] != 'true' 
  ) {
    //** Silently attempt to validate address, right now */
    $geo_data = WPP_F::revalidate_all_addresses( array( 'property_ids' => array( $property->ID ), 'echo_result' => false, 'return_geo_data' => true ) );
    if ( $this_geo_data = $geo_data[ 'geo_data' ][ $property->ID ] ) {
      $street_number = $this_geo_data->street_number;
      $route = $this_geo_data->route;
      $city = $this_geo_data->city;
      $state = $this_geo_data->state;
      $state_code = $this_geo_data->state_code;
      $county = $this_geo_data->county;
      $country = $this_geo_data->country;
      $postal_code = $this_geo_data->postal_code;
    }
  } else {
    $street_number = isset( $property->street_number ) ? $property->street_number : false;
    $route = isset( $property->route ) ? $property->route : false;
    $city = isset( $property->city ) ? $property->city : false;
    $state = isset( $property->state ) ? $property->state : false;
    $state_code = isset( $property->state_code ) ? $property->state_code : false;
    $county = isset( $property->county ) ? $property->county : false;
    $country = isset( $property->country ) ? $property->country : false;
    $postal_code = isset( $property->postal_code ) ? $property->postal_code : false;
  }

  $display_address = $format;

  $display_address = str_replace( "[street_number]", $street_number, $display_address );
  $display_address = str_replace( "[street_name]", $route, $display_address );
  $display_address = str_replace( "[city]", "$city", $display_address );
  $display_address = str_replace( "[state]", "$state", $display_address );
  $display_address = str_replace( "[state_code]", "$state_code", $display_address );
  $display_address = str_replace( "[county]", "$county", $display_address );
  $display_address = str_replace( "[country]", "$country", $display_address );
  $display_address = str_replace( "[zip_code]", "$postal_code", $display_address );
  $display_address = str_replace( "[zip]", "$postal_code", $display_address );
  $display_address = str_replace( "[postal_code]", "$postal_code", $display_address );
  $display_address = preg_replace( '/^\n+|^[\t\s]*\n+/m', "", $display_address );
  
  if ( str_replace( array( ' ', ',' ), '', $display_address ) == '' ) {
    if ( !empty( $currenty_address ) ) {
      return $currenty_address;
    } else {
      return;
    }
  }

  // Remove empty lines
  foreach ( explode( "\n", $display_address ) as $line ) {

    $line = trim( $line );

    // Remove line if comma is first character
    if ( strlen( $line ) < 3 && ( strpos( $line, ',' ) === 1 || strpos( $line, ',' ) === 0 ) ) {
      continue;
    }

    $return[ ] = $line;

  }

  if ( is_array( $return ) ) {
    return implode( "\n", $return );
  }

}

function wpp_property_stats_add_sold_or_rented( $property_stats ) {

  $property_stats[ 'for_sale' ] = __( "For Sale", ud_get_wp_property()->domain );
  $property_stats[ 'for_rent' ] = __( "For Rent", ud_get_wp_property()->domain );

  return $property_stats;
}

function wpp_property_stats_input_for_rent_make_checkbox( $content, $slug, $object ) {
  $checked = ( $object[ $slug ] == 'true' ? ' checked="true" ' : false );
  return "<input type='hidden' name='wpp_data[meta][{$slug}]'  value='false'  /><input type='checkbox' id='wpp_meta_{$slug}' name='wpp_data[meta][{$slug}]'  value='true' $checked /> <label for='wpp_meta_{$slug}'>" . __( 'This is a rental property.', ud_get_wp_property()->domain ) . "</label>";
}

function wpp_property_stats_input_for_sale_make_checkbox( $content, $slug, $object ) {
  $checked = ( $object[ $slug ] == 'true' ? ' checked="true" ' : false );
  return "<input type='hidden'  name='wpp_data[meta][{$slug}]'  value='false' /><input type='checkbox' id='wpp_meta_{$slug}' name='wpp_data[meta][{$slug}]'  value='true' $checked /> <label for='wpp_meta_{$slug}'>" . __( 'This property is for sale.', ud_get_wp_property()->domain ) . "</label>";
}

/**
 * Add UI to set custom coordinates on property editing page
 *
 * @depreciated in
 * @since 1.04
 */
function wpp_property_stats_input_address( $content, $slug, $object ) {

  ob_start();

  ?>
  <div class="wpp_attribute_row_address">
          <?php echo $content; ?>
    <div class="wpp_attribute_row_address_options">
          <input type="hidden" name="wpp_data[meta][manual_coordinates]" value="false"/>
          <input type="checkbox" id="wpp_manual_coordinates" name="wpp_data[meta][manual_coordinates]" value="true" <?php isset( $object[ 'manual_coordinates' ] ) ? checked( $object[ 'manual_coordinates' ], 1 ) : ''; ?> />
          <label for="wpp_manual_coordinates"><?php echo __( 'Set Coordinates Manually.', ud_get_wp_property()->domain ); ?></label>
          <div id="wpp_coordinates" style="<?php echo !isset( $object[ 'manual_coordinates' ] ) ? 'display:none;' : ''; ?>">
            <ul>
              <li>
                  <input type="text" id="wpp_meta_latitude" name="wpp_data[meta][latitude]" value="<?php echo isset( $object[ 'latitude' ] ) ? $object[ 'latitude' ] : ''; ?>"/>
                  <label><?php echo __( 'Latitude', ud_get_wp_property()->domain ) ?></label>
                  <div class="wpp_clear"></div>
                </li>
                <li>
                  <input type="text" id="wpp_meta_longitude" name="wpp_data[meta][longitude]" value="<?php echo isset( $object[ 'longitude' ] ) ? $object[ 'longitude' ] : ''; ?>"/>
                  <label><?php echo __( 'Longitude', ud_get_wp_property()->domain ) ?></label>
                  <div class="wpp_clear"></div>
                </li>
              </ul>
          </div>
      </div>
    </div>
  <script type="text/javascript">

      jQuery( document ).ready( function () {

        jQuery( 'input#wpp_manual_coordinates' ).change( function () {

          var use_manual_coordinates;

          if ( jQuery( this ).is( ":checked" ) ) {
            use_manual_coordinates = true;
            jQuery( '#wpp_coordinates' ).show();

          } else {
            use_manual_coordinates = false;
            jQuery( '#wpp_coordinates' ).hide();
          }

        } );

      } );

    </script>
  <?php

  $content = ob_get_contents();
  ob_end_clean();

  return $content;
}

/**
 * Updates numeric and currency attribute of parent property on child property saving.
 * Sets attribute's value based on children values ( sets aggregated value ).
 *
 * * Tries to figure out which attributes can be handled as a "range". (legacy-logic)
 * * Iterates over all children of the parent and writes any computed ranges directly to parents meta.
 *
 * @param integer $post_id
 * @used WPP_F::save_property();
 * @author peshkov@UD
 * @return null
 */
function wpp_save_property_aggregated_data( $post_id, $args ) {
  global $wpdb, $wp_properties;

  if( empty( $_REQUEST[ 'parent_id' ] ) && empty($args['parent_id']) ) {
    return null;
  }

  $parent_id = !empty( $_REQUEST[ 'parent_id' ] )? $_REQUEST[ 'parent_id' ] : $args['parent_id'];

  //** Get all children */
  $children = $wpdb->get_col( $wpdb->prepare( "
    SELECT ID
      FROM {$wpdb->posts}
        WHERE  post_type = 'property'
        AND post_status = 'publish'
        AND post_parent = %s
          ORDER BY menu_order ASC
  ", $parent_id ) );

  if ( count( $children ) > 0 ) {

    $range = array();

    //** Cycle through children and get necessary variables */
    foreach ( $children as $child_id ) {

      $child_object = WPP_F::get_property( $child_id, "load_parent=false" );

      //** Exclude variables from searchable attributes (to prevent ranges) */
      $excluded_attributes = $wp_properties[ 'geo_type_attributes' ];
      $excluded_attributes[ ] = $wp_properties[ 'configuration' ][ 'address_attribute' ];

      foreach ( $wp_properties[ 'searchable_attributes' ] as $searchable_attribute ) {

        $attribute_data = UsabilityDynamics\WPP\Attributes::get_attribute_data( $searchable_attribute );

        if ( !empty( $attribute_data[ 'numeric' ] ) || !empty( $attribute_data[ 'currency' ] ) ) {
          if ( !empty( $child_object[ $searchable_attribute ] ) && !in_array( $searchable_attribute, $excluded_attributes ) ) {
            if ( !isset( $range[ $searchable_attribute ] ) ) $range[ $searchable_attribute ] = array();
            $range[ $searchable_attribute ][ ] = $child_object[ $searchable_attribute ];
          }

        }
      }
    }

    foreach ( $range as $range_attribute => $range_values ) {

      //* Cycle through all values of this range (attribute), and fix any ranges that use dashes */
      foreach ( $range_values as $key => $single_value ) {

        //* Remove dollar signs */
        $single_value = str_replace( "$", '', $single_value );

        //* Fix ranges */
        if ( strpos( $single_value, '&ndash;' ) ) {
          $split = explode( '&ndash;', $single_value );

          foreach ( $split as $new_single_value ) {
            if ( !empty( $new_single_value ) ) {
              array_push( $range_values, trim( $new_single_value ) );
            }
          }
          //* Unset original value with dash */
          unset( $range_values[ $key ] );

        }

      }

      $average = isset( $wp_properties[ 'configuration' ][ 'show_aggregated_value_as_average' ] ) ? $wp_properties[ 'configuration' ][ 'show_aggregated_value_as_average' ] : false;

      $val = @array_sum( $range_values );
      $val = is_numeric( $val ) && $val > 0 ? ( $average == 'true' ? ceil( $val / count( $range_values ) ) : $val ) : 0;

      update_post_meta( $parent_id, $range_attribute, $val );

    }

  }

}

function wpp_stat_filter_for_rent_fix( $value ) {
  if ( $value == '1' )
    return __( 'Yes', ud_get_wp_property()->domain );
}

function wpp_stat_filter_for_sale_fix( $value ) {
  if ( $value == '1' )
    return __( 'Yes', ud_get_wp_property()->domain );
}

/**
 * Adds option 'format phone number' to settings of property page
 *
 * @since 1.16.2
 *
 */
function add_format_true_checkbox() {
  global $wp_properties;
  echo '<li>' . WPP_F::checkbox( "name=wpp_settings[configuration][property_overview][format_true_checkbox]&label=" . __( 'Convert "Yes" and "True" values to checked icons on the front-end.', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'format_true_checkbox' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'format_true_checkbox' ] : false ) ) . '</li>';
}

/**
 * Add "city" as an inheritable attribute for city property_type
 *
 * Modifies $wp_properties['property_inheritance'] in WPP_F::settings_action(), overriding database settings
 *
 * @since 1.0
 *
 * @param array $property_inheritance
 *
 * @return array $property_inheritance
 */
function add_city_to_inheritance( $property_inheritance ) {

  $property_inheritance[ 'floorplan' ][ ] = 'city';

  return $property_inheritance;
}

/**
 * Adds city to searchable
 *
 * Modifies $wp_properties['searchable_attributes'] in WPP_F::settings_action(), overriding database settings
 *
 * @since 1.0
 *
 * @param string $area
 *
 * @return string $area
 */
function add_city_to_searchable( $array ) {

  global $wp_properties;

  /** Determine if property attribute 'city' already exists, we don't need to set searchable here */
  if ( empty( $wp_properties[ 'property_stats' ] ) ) {
    if ( is_array( $array ) && !in_array( 'city', $array ) ) {
      array_push( $array, 'city' );
    }
  }

  return $array;
}

/**
 * Adds "sq. ft." to the end of all area attributes
 *
 *
 * @since 1.0
 *
 * @param string $area
 *
 * @return string $area
 */
function add_square_foot( $area ) {
  global $wp_properties;
  $area_dimensions = $wp_properties['configuration']['area_dimensions'] ? $wp_properties['configuration']['area_dimensions'] : 'sq. ft';
  return $area . ' ' . $area_dimensions;
}

/**
 * Demonstrates how to add a new attribute to the property class
 *
 * @since 1.08
 * @uses WPP_F::get_coordinates() Creates an array from string $args.
 *
 * @param string $listing_id Listing ID must be passed
 */
function add_display_address( $property ) {
  global $wp_properties;

  // Don't execute function if coordinates are set to manual
  if ( isset( $property[ 'manual_coordinates' ] ) && $property[ 'manual_coordinates' ] == 'true' )
    return $property;

  $display_address = $wp_properties[ 'configuration' ][ 'display_address_format' ];

  if ( empty( $display_address ) ) {
    $display_address = "[street_number] [street_name], [city], [state]";
  }

  $display_address_code = $display_address;

  // Check if property is supposed to inehrit the address
  if ( 
    isset( $property[ 'parent_id' ] )
    && !empty( $property[ 'property_type' ] )
    && isset( $wp_properties[ 'property_inheritance' ][ $property[ 'property_type' ] ] )
    && in_array( $wp_properties[ 'configuration' ][ 'address_attribute' ], (array)$wp_properties[ 'property_inheritance' ][ $property[ 'property_type' ] ] )
  ) {

    if ( get_post_meta( $property[ 'parent_id' ], 'address_is_formatted', true ) ) {
      // Also assign to $property[] to make data accessible later.
      $property[ 'street_number' ] = $street_number = get_post_meta( $property[ 'parent_id' ], 'street_number', true );
      $property[ 'route' ] = $route = get_post_meta( $property[ 'parent_id' ], 'route', true );
      $property[ 'city' ] = $city = get_post_meta( $property[ 'parent_id' ], 'city', true );
      $property[ 'state' ] = $state = get_post_meta( $property[ 'parent_id' ], 'state', true );
      $property[ 'state_code' ] = $state_code = get_post_meta( $property[ 'parent_id' ], 'state_code', true );
      $property[ 'postal_code' ] = $postal_code = get_post_meta( $property[ 'parent_id' ], 'postal_code', true );
      $property[ 'county' ] = $county = get_post_meta( $property[ 'parent_id' ], 'county', true );
      $property[ 'country' ] = $country = get_post_meta( $property[ 'parent_id' ], 'country', true );

      $display_address = str_replace( "[street_number]", $street_number, $display_address );
      $display_address = str_replace( "[street_name]", $route, $display_address );
      $display_address = str_replace( "[city]", "$city", $display_address );
      $display_address = str_replace( "[state]", "$state", $display_address );
      $display_address = str_replace( "[state_code]", "$state_code", $display_address );
      $display_address = str_replace( "[country]", "$country", $display_address );
      $display_address = str_replace( "[county]", "$county", $display_address );
      $display_address = str_replace( "[zip_code]", "$postal_code", $display_address );
      $display_address = str_replace( "[zip]", "$postal_code", $display_address );
      $display_address = str_replace( "[postal_code]", "$postal_code", $display_address );
      $display_address = preg_replace( '/^\n+|^[\t\s]*\n+/m', "", $display_address );
      $display_address = nl2br( $display_address );

    }
  } else {

    // Verify that address has been converted via Google Maps API
    if ( isset( $property[ 'address_is_formatted' ] ) && $property[ 'address_is_formatted' ] ) {

      $street_number = isset( $property[ 'street_number' ] ) ? $property[ 'street_number' ] : '';
      $route = isset( $property[ 'route' ] ) ? $property[ 'route' ] : '';
      $city = isset( $property[ 'city' ] ) ? $property[ 'city' ] : '';
      $state = isset( $property[ 'state' ] ) ? $property[ 'state' ] : '';
      $state_code = isset( $property[ 'state_code' ] ) ? $property[ 'state_code' ] : '';
      $country = isset( $property[ 'country' ] ) ? $property[ 'country' ] : '';
      $postal_code = isset( $property[ 'postal_code' ] ) ? $property[ 'postal_code' ] : '';
      $county = isset( $property[ 'county' ] ) ? $property[ 'county' ] : '';

      $display_address = str_replace( "[street_number]", $street_number, $display_address );
      $display_address = str_replace( "[street_name]", $route, $display_address );
      $display_address = str_replace( "[city]", "$city", $display_address );
      $display_address = str_replace( "[state]", "$state", $display_address );
      $display_address = str_replace( "[state_code]", "$state_code", $display_address );
      $display_address = str_replace( "[country]", "$country", $display_address );
      $display_address = str_replace( "[county]", "$county", $display_address );
      $display_address = str_replace( "[zip_code]", "$postal_code", $display_address );
      $display_address = str_replace( "[zip]", "$postal_code", $display_address );
      $display_address = str_replace( "[postal_code]", "$postal_code", $display_address );
      $display_address = preg_replace( '/^\n+|^[\t\s]*\n+/m', "", $display_address );
      $display_address = nl2br( $display_address );

    }

  }

  // If somebody is smart enough to do the following with regular expressions, let us know!

  $comma_killer = explode( ",", $display_address );

  if ( is_array( $comma_killer ) )
    foreach ( $comma_killer as $key => $addy_line )
      if ( isset( $addy_line ) )
        if ( trim( $addy_line ) == "" )
          unset( $comma_killer[ $key ] );

  $display_address = implode( ", ", $comma_killer );

  $empty_line_killer = explode( "<br />", $display_address );

  if ( is_array( $empty_line_killer ) )
    foreach ( $empty_line_killer as $key => $addy_line )
      if ( isset( $addy_line ) )
        if ( trim( $addy_line ) == "" )
          unset( $empty_line_killer[ $key ] );

  if ( is_array( $empty_line_killer ) ) {
    $display_address = implode( "<br />", $empty_line_killer );
  }

  $property[ 'display_address' ] = apply_filters( 'wpp_display_address', $display_address, $property );

  // Don't return if result matches the
  if ( str_replace( array( " ", ",", "\n" ), "", $display_address_code ) == str_replace( array( " ", ",", "\n" ), "", $display_address ) ) {
    $property[ 'display_address' ] = "";
  }

  //** Make sure that address isn't retunred with no data */
  if ( str_replace( ',', '', $property[ 'display_address' ] ) == '' ) {
    /* No Address */
  }

  return $property;
}

/**
 * Demonstrates how to add dollar signs before all prices and deposits
 *
 * @since 1.15.3
 * @uses WPP_F::get_coordinates() Creates an array from string $args.
 *
 * @param string $listing_id Listing ID must be passed
 */
function add_dollar_sign( $content ) {
  global $wp_properties;

  $currency_symbol = ( !empty( $wp_properties[ 'configuration' ][ 'currency_symbol' ] ) ? $wp_properties[ 'configuration' ][ 'currency_symbol' ] : "$" );
  $currency_symbol_placement = ( !empty( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ] ) ? $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ] : "before" );

  if( is_string( $content ) ) {
    $content = trim( str_replace( array( $currency_symbol, "," ), "", $content ) );
  }

  if ( !is_numeric( $content ) ) {
    return preg_replace_callback( '/(\d+)/', function( $matches ) {
      return add_dollar_sign( $matches[0] );
    }, $content );
  } else {
    return ( $currency_symbol_placement == 'before' ? $currency_symbol : '' ) . WPP_F::format_numeric( $content ) . ( $currency_symbol_placement == 'after' ? $currency_symbol : '' );
  }
}

/**
 * Display latitude and longitude on listing edit page below address field
 *
 * Echos html content to be displayed after location attribute on property edit page
 *
 * @since 1.0
 * @uses WPP_F::get_coordinates() Creates an array from string $args.
 *
 * @param string $listing_id Listing ID must be passed
 */
function wpp_show_coords( $listing_id = false ) {

  if ( !$listing_id )
    return;

  // If latitude and logitude meta isn't set, returns false
  $coords = WPP_F::get_coordinates( $listing_id );

  echo "<span class='description'>";
  if ( $coords ) {
    _e( "Address was validated by Google Maps.", ud_get_wp_property()->domain );
  } else {
    _e( "Address has not yet been validated, should be formatted as: street, city, state, postal code, country. Locations are validated through Google Maps.", ud_get_wp_property()->domain );
  }
  echo "</span>";

}

/**
 * Add week/month dropdown after price
 *
 * Displays a hidden field on property edit page setting the property price frequency
 *
 * @since 1.0
 *
 * @param string $listing_id Listing ID must be passed
 */
function wpp_show_week_month_selection( $listing_id = false ) {
  if ( !$listing_id )
    return;

  echo '<input type="hidden" name="wpp_data[meta][price_per]" value="month" />';

  /*

  Uncomment the following to allow the editor to select if price is monthly and weekly.
  Or add your own frequencies.

    <select id="wpp_meta_price_per" name="wpp_data[meta][price_per]">
      <option value=""></option>
      <option <?php if(get_post_meta($listing_id, 'price_per', true) == 'week') echo "SELECTED"; ?> value="week">week</option>
      <option <?php if(get_post_meta($listing_id, 'price_per', true) == 'month') echo "SELECTED"; ?> value="month">month</option>
    </select>.
  */
}

/**
 *
 * Group search values
 *
 */
function group_search_values( $values ) {
  $result = array();

  if ( !is_array( $values ) ) {
    return $values;
  }

  $min = 0;
  $max = 0;
  $control = false;

  for ( $i = 0; $i < count( $values ); $i++ ) {
    $value = (int) $values[ $i ];
    if ( !$control && $min == 0 && $value != 0 ) {
      $control = true;
      $min = $value;
    } elseif ( $value < $min ) {
      $min = $value;
    } elseif ( $value > $max ) {
      $max = $value;
    }
  }

  $range = $max - $min;

  if ( $range == 0 ) {
    return $values;
  }

  $s = round( $range / 10 );
  $stepup = ( $s > 1 ) ? $s : 1;

  $result[ ] = $min;
  for ( $i = ( $min + $stepup ); $i < $max; $i ) {
    $result[ ] = $i;
    $i = $i + $stepup;
  }
  $result[ ] = $max;

  return $result;
}

/**
 * Exclude Hidden Property Atributes from data to don't show them on frontend
 *
 * @param array $property
 *
 * @return array $property
 */
function wpp_exclude_hidden_attributes( $property ) {
  global $wp_properties;

  if ( !current_user_can( 'manage_options' ) ) {
    foreach ( $property as $slug => $value ) {
      // Determine if the attribute is hidden for frontend
      if ( !empty($wp_properties[ 'hidden_frontend_attributes' ]) && in_array( $slug, (array) $wp_properties[ 'hidden_frontend_attributes' ] ) ) {
        unset( $property[ $slug ] );
      }
    }
  }

  return $property;
}

