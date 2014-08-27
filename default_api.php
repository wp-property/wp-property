<?php
/**
 * Default API Usage Examples
 *
 * @since 0.0.1
 * @class wpp_default_api
 */
class wpp_default_api {

  /**
   * Loader for WPP API functions.
   *
   * @version 1.25.0
   */
  static function plugins_loaded() {

    //** Load API towards the end of init */
    add_filter( 'init', array( 'wpp_default_api', 'init' ), 0, 30 );

  }

  /**
   * Loader for WPP API functions.
   *
   * @version 1.25.0
   */
  static function init() {
    global $shortcode_tags;

    $shortcodes = array_keys( (array) $shortcode_tags );

    //** Load list-attachments shortcode if the List Attachments Shortcode plugin does not exist */
    if ( !in_array( 'list-attachments', $shortcodes ) ) {
      add_shortcode( 'list_attachments', array( 'wpp_default_api', 'list_attachments' ) );
    }

  }

  /**
   * Display list of attached files to a s post.
   *
   * Function ported over from List Attachments Shortcode plugin.
   *
   * @version 1.25.0
   */
  static function list_attachments( $atts = array() ) {
    global $post, $wp_query;

    $r = '';

    if ( !is_array( $atts ) ) {
      $atts = array();
    }

    $defaults = array(
      'type' => NULL,
      'orderby' => NULL,
      'groupby' => NULL,
      'order' => NULL,
      'post_id' => false,
      'before_list' => '',
      'after_list' => '',
      'opening' => '<ul class="attachment-list wpp_attachment_list">',
      'closing' => '</ul>',
      'before_item' => '<li>',
      'after_item' => '</li>',
      'show_descriptions' => true,
      'include_icon_classes' => true,
      'showsize' => false
    );

    $atts = array_merge( $defaults, $atts );

    if ( isset( $atts[ 'post_id' ] ) && is_numeric( $atts[ 'post_id' ] ) ) {
      $post = get_post( $atts[ 'post_id' ] );
    }

    if ( !$post ) {
      return;
    }

    if ( !empty( $atts[ 'type' ] ) ) {
      $types = explode( ',', str_replace( ' ', '', $atts[ 'type' ] ) );
    } else {
      $types = array();
    }

    $showsize = ( $atts[ 'showsize' ] == true || $atts[ 'showsize' ] == 'true' || $atts[ 'showsize' ] == 1 ) ? true : false;
    $upload_dir = wp_upload_dir();

    $op = clone $post;
    $oq = clone $wp_query;

    foreach ( array( 'before_list', 'after_list', 'opening', 'closing', 'before_item', 'after_item' ) as $htmlItem ) {
      $atts[ $htmlItem ] = str_replace( array( '&lt;', '&gt;' ), array( '<', '>' ), $atts[ $htmlItem ] );
    }

    $args = array(
      'post_type' => 'attachment',
      'numberposts' => -1,
      'post_status' => null,
      'post_parent' => $post->ID,
    );

    if ( !empty( $atts[ 'orderby' ] ) ) {
      $args[ 'orderby' ] = $atts[ 'orderby' ];
    }
    if ( !empty( $atts[ 'order' ] ) ) {
      $atts[ 'order' ] = ( in_array( $atts[ 'order' ], array( 'a', 'asc', 'ascending' ) ) ) ? 'asc' : 'desc';
      $args[ 'order' ] = $atts[ 'order' ];
    }
    if ( !empty( $atts[ 'groupby' ] ) ) {
      $args[ 'orderby' ] = $atts[ 'groupby' ];
    }

    $attachments = get_posts( $args );

    if ( $attachments ) {
      $grouper = $atts[ 'groupby' ];
      $test = $attachments;
      $test = array_shift( $test );
      if ( !property_exists( $test, $grouper ) ) {
        $grouper = 'post_' . $grouper;
      }

      $attlist = array();

      foreach ( $attachments as $att ) {
        $key = ( !empty( $atts[ 'groupby' ] ) ) ? $att->$grouper : $att->ID;
        $key .= ( !empty( $atts[ 'orderby' ] ) ) ? $att->$atts[ 'orderby' ] : '';

        $attlink = wp_get_attachment_url( $att->ID );

        if ( count( $types ) ) {
          foreach ( $types as $t ) {
            if ( substr( $attlink, ( 0 - strlen( '.' . $t ) ) ) == '.' . $t ) {
              $attlist[ $key ] = clone $att;
              $attlist[ $key ]->attlink = $attlink;
            }
          }
        } else {
          $attlist[ $key ] = clone $att;
          $attlist[ $key ]->attlink = $attlink;
        }
      }
      if ( $atts[ 'groupby' ] ) {
        if ( $atts[ 'order' ] == 'asc' ) {
          ksort( $attlist );
        } else {
          krsort( $attlist );
        }
      }
    }

    if ( count( $attlist ) ) {
      $open = false;
      $r = $atts[ 'before_list' ] . $atts[ 'opening' ];
      foreach ( $attlist as $att ) {

        $container_classes = array( 'attachment_container' );

        //** Determine class to display for this file type */
        if ( $atts[ 'include_icon_classes' ] ) {

          switch ( $att->post_mime_type ) {

            case 'application/zip':
              $class = 'zip';
              break;

            case 'vnd.ms-excel':
              $class = 'excel';
              break;

            case 'image/jpeg':
            case 'image/png':
            case 'image/gif':
            case 'image/bmp':
              $class = 'image';
              break;

            default:
              $class = 'default';
              break;
          }
        }

        $icon_class = ( $class ? 'wpp_attachment_icon file-' . $class : false );

        //** Determine if description shuold be displayed, and if it is not empty */
        $echo_description = ( $atts[ 'show_descriptions' ] && !empty( $att->post_content ) ? ' <span class="attachment_description"> ' . $att->post_content . ' </span> ' : false );

        $echo_title = ( $att->post_excerpt ? $att->post_excerpt : __( 'View ', 'wpp' ) . apply_filters( 'the_title_attribute', $att->post_title ) );

        if ( $icon_class ) {
          $container_classes[ ] = 'has_icon';
        }

        if ( !empty( $echo_description ) ) {
          $container_classes[ ] = 'has_description';
        }

        //** Add conditional classes if class is not already passed into container */
        if ( !strpos( $atts[ 'before_item' ], 'class' ) ) {
          $this_before_item = str_replace( '>', ' class="' . implode( ' ', $container_classes ) . '">', $atts[ 'before_item' ] );
        }

        $echo_size = ( ( $showsize ) ? ' <span class="attachment-size">' . WPP_F::get_filesize( str_replace( $upload_dir[ 'baseurl' ], $upload_dir[ 'basedir' ], $attlink ) ) . '</span>' : '' );

        if ( !empty( $atts[ 'groupby' ] ) && $current_group != $att->$grouper ) {
          if ( $open ) {
            $r .= $atts[ 'closing' ] . $atts[ 'after_item' ];
            $open = false;
          }
          $r .= $atts[ 'before_item' ] . '<h3>' . $att->$grouper . '</h3>' . $atts[ 'opening' ];
          $open = true;
          $current_group = $att->$grouper;
        }
        $attlink = $att->attlink;
        $r .= $this_before_item . '<a href="' . $attlink . '" title="' . $echo_title . '" class="wpp_attachment ' . $icon_class . '">' . apply_filters( 'the_title', $att->post_title ) . '</a>' . $echo_size . $echo_description . $atts[ 'after_item' ];
      }
      if ( $open ) {
        $r .= $atts[ 'closing' ] . $atts[ 'after_item' ];
      }
      $r .= $atts[ 'closing' ] . $atts[ 'after_list' ];
    }

    $wp_query = clone $oq;
    $post = clone $op;

    return $r;

  }

}

// Initialize WPP Default API */
add_filter( 'plugins_loaded', array( 'wpp_default_api', 'plugins_loaded' ), 0, 9 );

// Widget address format
add_filter( "wpp_stat_filter_{$wp_properties[ 'configuration' ]['address_attribute']}", "wpp_format_address_attribute", 0, 3 );

// Add additional Google Maps localizations
add_filter( "wpp_google_maps_localizations", "wpp_add_additional_google_maps_localizations" );

// Add post-thumbnails support
add_action( "after_setup_theme", array( 'WPP_Core', "after_setup_theme" ) );

// Add some default actions
//add_filter("wpp_stat_filter_price", 'add_dollar_sign');
//add_filter("wpp_stat_filter_deposit", 'add_dollar_sign');

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
        add_filter("wpp_stat_filter_$key", create_function(' $value ', ' return $value == "0" ? __( "No", "wpp" ) : __( "Yes", "wpp" ); '));
      }
    }
  }

  add_filter("wpp_stat_filter_phone_number", 'format_phone_number');

// Exclude hidden attributes from frontend
add_filter( 'wpp_get_property', 'wpp_exclude_hidden_attributes' );

add_filter( 'wpp_get_property', 'add_display_address' );

add_filter( 'wpp_property_inheritance', 'add_city_to_inheritance' );
add_filter( 'wpp_searchable_attributes', 'add_city_to_searchable' );

add_filter( 'wpp_property_stat_labels', 'wpp_unique_key_labels', 20 );

add_filter( 'the_password_form', 'wpp_password_protected_property_form' );

// Coordinate manual override
add_filter( 'wpp_property_stats_input_' . $wp_properties[ 'configuration' ][ 'address_attribute' ], 'wpp_property_stats_input_address', 0, 3 );

  add_action('save_property', 'wpp_save_property_aggregated_data' );

//add_action("wpp_ui_after_attribute_{$wp_properties['configuration']['address_attribute']}", 'wpp_show_coords');
add_action( 'wpp_ui_after_attribute_price', 'wpp_show_week_month_selection' );

//**  Adds additional settings for Property Page */
add_action( 'wpp_settings_page_property_page', 'add_format_phone_number_checkbox' );

/**
 * Add labels to system-generated attributes that do not have custom-set values
 *
 * @since 1.22.0
 */
function wpp_unique_key_labels( $stats ) {

  if ( empty( $stats[ 'property_type' ] ) ) {
    $stats[ 'property_type' ] = __( 'Property Type', 'wpp' );
  }

  if ( empty( $stats[ 'city' ] ) ) {
    $stats[ 'city' ] = __( 'City', 'wpp' );
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
 * Example of how to add a new language to Google Maps localization
 *
 * @since 1.04
 */
function wpp_add_additional_google_maps_localizations( $attributes ) {
  $attributes[ 'fi' ] = "Finnish";
  return $attributes;
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

  $property_stats[ 'for_sale' ] = __( "For Sale", 'wpp' );
  $property_stats[ 'for_rent' ] = __( "For Rent", 'wpp' );

  return $property_stats;
}

function wpp_property_stats_input_for_rent_make_checkbox( $content, $slug, $object ) {
  $checked = ( $object[ $slug ] == 'true' ? ' checked="true" ' : false );
  return "<input type='hidden' name='wpp_data[meta][{$slug}]'  value='false'  /><input type='checkbox' id='wpp_meta_{$slug}' name='wpp_data[meta][{$slug}]'  value='true' $checked /> <label for='wpp_meta_{$slug}'>" . __( 'This is a rental property.', 'wpp' ) . "</label>";
}

function wpp_property_stats_input_for_sale_make_checkbox( $content, $slug, $object ) {
  $checked = ( $object[ $slug ] == 'true' ? ' checked="true" ' : false );
  return "<input type='hidden'  name='wpp_data[meta][{$slug}]'  value='false' /><input type='checkbox' id='wpp_meta_{$slug}' name='wpp_data[meta][{$slug}]'  value='true' $checked /> <label for='wpp_meta_{$slug}'>" . __( 'This property is for sale.', 'wpp' ) . "</label>";
}

/**
 * Add UI to set custom coordinates on property editing page
 *
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
          <label for="wpp_manual_coordinates"><?php echo __( 'Set Coordinates Manually.', 'wpp' ); ?></label>
          <div id="wpp_coordinates" style="<?php echo !isset( $object[ 'manual_coordinates' ] ) ? 'display:none;' : ''; ?>">
            <ul>
              <li>
                  <input type="text" id="wpp_meta_latitude" name="wpp_data[meta][latitude]" value="<?php echo isset( $object[ 'latitude' ] ) ? $object[ 'latitude' ] : ''; ?>"/>
                  <label><?php echo __( 'Latitude', 'wpp' ) ?></label>
                  <div class="wpp_clear"></div>
                </li>
                <li>
                  <input type="text" id="wpp_meta_longitude" name="wpp_data[meta][longitude]" value="<?php echo isset( $object[ 'longitude' ] ) ? $object[ 'longitude' ] : ''; ?>"/>
                  <label><?php echo __( 'Longitude', 'wpp' ) ?></label>
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
 * @param integer $post_id
 * @used WPP_F::save_property();
 * @author peshkov@UD
 */
function wpp_save_property_aggregated_data( $post_id ) {
  global $wpdb, $wp_properties;

  if( empty( $_REQUEST[ 'parent_id' ] ) ) {
    return null;
  }

  //** Get children */
  $children = $wpdb->get_col( $wpdb->prepare( "
    SELECT ID
      FROM {$wpdb->posts}
        WHERE  post_type = 'property'
        AND post_status = 'publish'
        AND post_parent = %s
          ORDER BY menu_order ASC
  ", $_REQUEST[ 'parent_id' ] ) );

  if ( count( $children ) > 0 ) {

    $range = array();

    //** Cycle through children and get necessary variables */
    foreach ( $children as $child_id ) {

      $child_object = WPP_F::get_property( $child_id, "load_parent=false" );

      //** Exclude variables from searchable attributes (to prevent ranges) */
      $excluded_attributes = $wp_properties[ 'geo_type_attributes' ];
      $excluded_attributes[ ] = $wp_properties[ 'configuration' ][ 'address_attribute' ];

      foreach ( $wp_properties[ 'searchable_attributes' ] as $searchable_attribute ) {

        $attribute_data = WPP_F::get_attribute_data( $searchable_attribute );

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

      update_post_meta( $_REQUEST[ 'parent_id' ], $range_attribute, $val );

    }

  }

}

function wpp_stat_filter_for_rent_fix( $value ) {
  if ( $value == '1' )
    return __( 'Yes', 'wpp' );
}

function wpp_stat_filter_for_sale_fix( $value ) {
  if ( $value == '1' )
    return __( 'Yes', 'wpp' );
}

/**
 * Formats phone number for display
 *
 *
 * @since 1.0
 *
 * @param string $phone_number
 *
 * @return string $phone_number
 */
function format_phone_number( $phone_number ) {
  global $wp_properties;

  if ( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'format_phone_number' ] == 'true' ) {
    $phone_number = preg_replace( "[^0-9]", '', $phone_number );
    if ( strlen( $phone_number ) != 10 ) {
      return $phone_number;
    }
    $sArea = substr( $phone_number, 0, 3 );
    $sPrefix = substr( $phone_number, 3, 3 );
    $sNumber = substr( $phone_number, 6, 4 );
    $phone_number = "(" . $sArea . ") " . $sPrefix . "-" . $sNumber;
  }

  return $phone_number;
}

/**
 * Adds option 'format phone number' to settings of property page
 */
function add_format_phone_number_checkbox() {
  global $wp_properties;
  echo '<li>' . WPP_F::checkbox( "name=wpp_settings[configuration][property_overview][format_phone_number]&label=" . __( 'Format phone number.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'format_phone_number' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'format_phone_number' ] : false ) ) . '</li>';
}

/**
 * Adds option 'format phone number' to settings of property page
 *
 * @since 1.16.2
 *
 */
function add_format_true_checkbox() {
  global $wp_properties;
  echo '<li>' . WPP_F::checkbox( "name=wpp_settings[configuration][property_overview][format_true_checkbox]&label=" . __( 'Convert "Yes" and "True" values to checked icons on the front-end.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'format_true_checkbox' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'format_true_checkbox' ] : false ) ) . '</li>';
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
  return $area . __( " sq. ft.", 'wpp' );
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
    && isset( $wp_properties[ 'property_inheritance' ][ $property[ 'property_type' ] ] )
    && in_array( $wp_properties[ 'configuration' ][ 'address_attribute' ], (array)$wp_properties[ 'property_inheritance' ][ $property[ 'property_type' ] ] )
  ) {

    if ( get_post_meta( $property[ 'parent_id' ], 'address_is_formatted', true ) ) {
      $street_number = get_post_meta( $property[ 'parent_id' ], 'street_number', true );
      $route = get_post_meta( $property[ 'parent_id' ], 'route', true );
      $city = get_post_meta( $property[ 'parent_id' ], 'city', true );
      $state = get_post_meta( $property[ 'parent_id' ], 'state', true );
      $state_code = get_post_meta( $property[ 'parent_id' ], 'state_code', true );
      $postal_code = get_post_meta( $property[ 'parent_id' ], 'postal_code', true );
      $county = get_post_meta( $property[ 'parent_id' ], 'county', true );
      $country = get_post_meta( $property[ 'parent_id' ], 'country', true );

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

  $content = trim( str_replace( array( "$", "," ), "", $content ) );

  if ( !is_numeric( $content ) ) {
    return preg_replace_callback( '/(\d+)/', create_function(
      '$matches',
      'return add_dollar_sign( $matches[0] );'
    ), $content );
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
    _e( "Address was validated by Google Maps.", 'wpp' );
  } else {
    _e( "Address has not yet been validated, should be formatted as: street, city, state, postal code, country. Locations are validated through Google Maps.", 'wpp' );
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
      if ( in_array( $slug, (array) $wp_properties[ 'hidden_frontend_attributes' ] ) ) {
        unset( $property[ $slug ] );
      }
    }
  }

  return $property;
}

