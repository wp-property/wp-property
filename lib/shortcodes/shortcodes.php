<?php

/**
 * Return primary image for a listing.
 *
 * @param string $atts
 */
static function property_primary_image( $atts = '' ) {

}

/**
 * Section that is inserted into the_content area on single listing pages.
 * This element replicates a large part what the legacy property.php template did.
 * This is a very basic example, this shortcode would need to assume a lot of defaults to assist with providing the children shortcodes with enough arguments.
 * This could be called on a single listing page, but also within a search result loop.
 *
 * @todo Will need to add classes wrapping the individual elements here.
 * @since 2.0
 * @author potanin@UD
 */
static function property_body( $atts, $content = '' ) {

  $parts = array();

  $parts[ ] = do_shortcode( '[property_image]' );

  $parts[ ] = do_shortcode( '[property_attributes]' );

  $parts[ ] = do_shortcode( '[property_taxonomy_terms]' );

  $parts[ ] = do_shortcode( '[property_map]' );

  return implode( '', $parts );

}

/**
 * Displays featured properties
 * Performs searching/filtering functions, provides template with $properties file
 * Returns html content to be displayed after location attribute on property edit page
 *
 * @todo     Consider making this function depend on shortcode_property_overview() more so pagination and sorting functions work.
 * @since    0.60
 *
 * @param bool   $atts
 *
 * @param string $content
 *
 * @internal param string $listing_id Listing ID must be passed
 * @return string
 */
static function featured_properties( $atts = false, $content = '' ) {
  global $wp_properties, $wpp_query, $post;

  $default_property_type = \UsabilityDynamics\WPP\Utility::get_most_common_property_type();

  if( !$atts ) {
    $atts = array();
  }

  $defaults = array(
    'property_type'          => '',
    'type'                   => '',
    'class'                  => 'shortcode_featured_properties',
    'per_page'               => '6',
    'sorter_type'            => 'none',
    'show_children'          => 'false',
    'hide_count'             => true,
    'fancybox_preview'       => 'false',
    'bottom_pagination_flag' => 'false',
    'pagination'             => 'off',
    'stats'                  => '',
    'image_type'             => 'thumbnail',
    'thumbnail_size'         => 'thumbnail'
  );

  $args = wp_parse_args( $atts, $defaults );

  /** Using "image_type" is obsolete */
  if( empty( $args[ 'thumbnail_size' ] ) && !empty( $args[ 'image_type' ] ) ) {
    $args[ 'thumbnail_size' ] = $args[ 'image_type' ];
  }

  /** Using "type" is obsolete. If property_type is not set, but type is, we set property_type from type */
  if( !empty( $args[ 'type' ] ) && empty( $args[ 'property_type' ] ) ) {
    $args[ 'property_type' ] = $args[ 'type' ];
  }

  if( empty( $args[ 'property_type' ] ) ) {
    $args[ 'property_type' ] = $default_property_type;
  }

  // Convert shortcode multi-property-type string to array
  if( !empty( $args[ 'stats' ] ) ) {

    if( strpos( $args[ 'stats' ], "," ) ) {
      $args[ 'stats' ] = explode( ",", $args[ 'stats' ] );
    }

    if( !is_array( $args[ 'stats' ] ) ) {
      $args[ 'stats' ] = array( $args[ 'stats' ] );
    }

    foreach( (array) $args[ 'stats' ] as $key => $stat ) {
      $args[ 'stats' ][ $key ] = trim( $stat );
    }

  }

  $args[ 'thumbnail_size' ]  = $args[ 'image_type' ];
  $args[ 'disable_wrapper' ] = 'true';
  $args[ 'featured' ]        = 'true';
  $args[ 'template' ]        = 'featured-shortcode';

  unset( $args[ 'image_type' ] );
  unset( $args[ 'type' ] );

  $result = self::property_overview( $args );

  return $result;
}

/**
 * Returns the property search widget
 *
 * @since 1.04
 */
static function property_search( $atts = "" ) {
  global $post, $wp_properties;

  extract( shortcode_atts( array(
    'searchable_attributes'     => '',
    'searchable_property_types' => '',
    'pagination'                => 'on',
    'group_attributes'          => 'off',
    'per_page'                  => '10'
  ), $atts ) );

  if( empty( $searchable_attributes ) ) {

    /** get first 3 attributes to prevent people from accidentally loading them all ( long query ) */
    $searchable_attributes = array_slice( (array) $wp_properties[ 'searchable_attributes' ], 0, 5 );

  } elseif( is_string( $searchable_attributes ) ) {
    $searchable_attributes = explode( ",", $searchable_attributes );
  }

  $searchable_attributes = array_unique( (array) $searchable_attributes );

  if( empty( $searchable_property_types ) ) {
    $searchable_property_types = $wp_properties[ 'searchable_property_types' ];
  } elseif( is_string( $searchable_property_types ) ) {
    $searchable_property_types = explode( ",", $searchable_property_types );
  }

  $widget_id = $post->ID . "_search";

  $search_args[ 'searchable_attributes' ]     = $searchable_attributes;
  $search_args[ 'searchable_property_types' ] = $searchable_property_types;
  $search_args[ 'group_attributes' ]          = ( $group_attributes == 'on' || $group_attributes == 'true' ? true : false );
  $search_args[ 'per_page' ]                  = $per_page;
  $search_args[ 'pagination' ]                = $pagination;
  $search_args[ 'instance_id' ]               = $widget_id;

  $content = array( '<div class="wpp_shortcode_search">' );

  ob_start();
  draw_property_search_form( $search_args );
  $content[ ] = ob_get_clean();

  $content[ ] = '</div>';

  return implode( '', $content );

}

/**
 * Get terms from all property taxonomies, grouped by taxonomy
 *
 * @todo Make sure the label/title is rendered correctly when grouped and ungrouped. - potanin@UD
 * @todo Improve so shortcode arguments are passed to draw_stats - potanin@UD 5/24/12
 * @uses draw_stats
 * @since 1.35.0
 */
static function property_attributes( $atts = false ) {
  global $wp_properties, $property;

  if( is_admin() && !DOING_AJAX ) {
    return sprintf( __( '%1$s Attributes', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );
  }

  $atts = shortcode_atts( array(
    'property_id'    => $property[ 'ID' ],
    'title'          => false,
    'group'          => false,
    'sort_by_groups' => !empty( $wp_properties[ 'property_groups' ] ) && $wp_properties[ 'configuration' ][ 'property_overview' ][ 'sort_stats_by_groups' ] == 'true' ? true : false
  ), $atts );

  $html[ ] = draw_stats( "return=true&make_link=true&group={$atts[group]}&title={$atts['title']}&sort_by_groups={$atts['sort_by_groups']}", $property );

  return implode( '', (array) $html );

}

/**
 * Get terms from all property taxonomies, grouped by taxonomy
 *
 * @todo Add support to recognize requested taxonomy and default to all, as well as some other shortcode-configured settings - potanin@UD 5/24/12
 * @since 1.35.0
 */
static function taxonomy_terms( $atts = false ) {
  global $wp_properties, $post, $property;

  if( is_admin() && !DOING_AJAX ) {
    return sprintf( __( '%1$s Taxonomy Terms', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );
  }

  $atts = shortcode_atts( array(
    'property_id' => $property[ 'ID' ],
    'title'       => false,
    'taxonomy'    => ''
  ), $atts );

  foreach( (array) $wp_properties[ 'taxonomies' ] as $tax_slug => $tax_data ) {

    $terms = get_features( "property_id={$atts['property_id']}&type={$tax_slug}&format=list&links=true&return=true" );

    if( !empty( $terms ) ) {

      $html[ ] = '<div class="' . wpp_css( 'attribute_list::list_item', 'wpp_attributes', true ) . '">';

      if( $atts[ 'title' ] ) {
        $html[ ] = '<h2 class="wpp_list_title">' . $tax_data[ 'labels' ][ 'name' ] . '</h2>';
      }

      $html[ ] = '<ul class="' . wpp_css( 'attribute_list::list_item', 'wpp_feature_list wpp_attribute_list wpp_taxonomy_terms', true ) . '">';
      $html[ ] = $terms;
      $html[ ] = '</ul>';

      $html[ ] = '</div>'; /* .wpp_attributes */

    }

  }

  return implode( '', (array) $html );

}

/**
 * Retrieve property attribute using shortcode.
 *
 * @since 1.26.0
 */
static function property_attribute( $atts = array() ) {
  global $post, $property;

  if( is_admin() && !DOING_AJAX ) {
    return sprintf( __( '%1$s Attribute', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );
  }

  $this_property = $property;

  if( empty( $this_property ) && $post->post_type == WPP_Object ) {
    $this_property = $post;
  }

  $this_property = (array) $this_property;

  $args = shortcode_atts( array(
    'property_id'      => $this_property[ 'ID' ],
    'attribute'        => '',
    'before'           => '',
    'after'            => '',
    'if_empty'         => '',
    'do_not_format'    => '',
    'make_terms_links' => 'false',
    'separator'        => ' ',
    'strip_tags'       => ''
  ), $atts );

  if( empty( $args[ 'attribute' ] ) ) {
    return false;
  }

  $attribute = $args[ 'attribute' ];

  if( $args[ 'property_id' ] != $this_property[ 'ID' ] ) {

    $this_property = \UsabilityDynamics\WPP\Utility::get_property( $args[ 'property_id' ] );

    if( $args[ 'do_not_format' ] != "true" ) {
      $this_property = prepare_property_for_display( $this_property );
    }

  }

  if( is_object_in_taxonomy( WPP_Object, $attribute ) && taxonomy_exists( $attribute ) ) {
    foreach( (array) wp_get_object_terms( $this_property[ 'ID' ], $attribute ) as $term_data ) {

      if( $args[ 'make_terms_links' ] == 'true' ) {
        $terms[ ] = '<a class="wpp_term_link" href="' . get_term_link( $term_data, $attribute ) . '"><span class="wpp_term">' . $term_data->name . '</span></a>';
      } else {
        $terms[ ] = '<span class="wpp_term">' . $term_data->name . '</span>';
      }
    }

    if( is_array( $terms ) && !empty( $terms ) ) {
      $value = implode( $args[ 'separator' ], $terms );
    }

  }

  /** Try to get value using get get_attribute() function */
  if( !$value && function_exists( 'get_attribute' ) ) {
    $value = get_attribute( $attribute, array(
      'return'          => 'true',
      'property_object' => $this_property
    ) );
  }

  if( !empty( $args[ 'before' ] ) ) {
    $return[ 'before' ] = html_entity_decode( $args[ 'before' ] );
  }

  $return[ 'value' ] = apply_filters( 'wpp_property_attribute_shortcode', $value, $the_property );

  if( $args[ 'strip_tags' ] == "true" && !empty( $return[ 'value' ] ) ) {
    $return[ 'value' ] = strip_tags( $return[ 'value' ] );
  }

  if( !empty( $args[ 'after' ] ) ) {
    $return[ 'after' ] = html_entity_decode( $args[ 'after' ] );
  }

  /** When no value is found */
  if( empty( $value[ 'value' ] ) ) {

    if( !empty( $args[ 'if_empty' ] ) ) {
      return $args[ 'if_empty' ];
    } else {
      return false;
    }
  }

  if( is_array( $return ) ) {
    return implode( '', $return );
  }

  return false;

}

/**
 * Displays a map for the current property.
 * Must be used on a property page, or within a property loop where the global $post or $property variable is for a property object.
 *
 * @since 1.26.0
 */
static function property_map( $atts = false ) {
  global $post, $property;

  //** backup of global $property */
  $_property = $property;

  if( is_admin() && !DOING_AJAX ) {
    return sprintf( __( '%1$s Map', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );
  }

  $atts = shortcode_atts( array(
    'width'        => '100%',
    'height'       => '450px',
    'zoom_level'   => '13',
    'hide_infobox' => 'false',
    'property_id'  => $property->ID
  ), $atts );

  /** Try to get property if an ID is passed */
  if( is_numeric( $atts[ 'property_id' ] ) ) {
    $property = \UsabilityDynamics\WPP\Utility::get_property( $atts[ 'property_id' ] );
  }

  /** Load into $property object */
  if( !isset( $property ) ) {
    $property = (array) $post;
  }

  /** Force map to be enabled here */
  $skip_default_google_map_check = true;

  $map_width    = $atts[ 'width' ];
  $map_height   = $atts[ 'height' ];
  $hide_infobox = ( $atts[ 'hide_infobox' ] == 'true' ? true : false );

  /** Find most appropriate template */
  $template_found = \UsabilityDynamics\WPP\Utility::get_template_part( array(
    'content-single-property-map',
    'property-map'
  ), array( WPP_Templates ) );

  if( !$template_found ) {
    return false;
  }

  ob_start();
  include $template_found;
  $html = ob_get_contents();
  ob_end_clean();

  $html = apply_filters( 'wpp::property_map_content', $html, $atts );

  //** restore of global $property */
  $property = $_property;

  return $html;
}

/**
 * Display list of attached files to a s post.
 * Function ported over from List Attachments Shortcode plugin.
 *
 * @version 1.25.0
 */
static function list_attachments( $atts = array() ) {
  global $post, $wp_query;

  $r = '';

  $atts = shortcode_atts( array(
    'type'                 => NULL,
    'orderby'              => NULL,
    'groupby'              => NULL,
    'order'                => NULL,
    'post_id'              => false,
    'before_list'          => '',
    'after_list'           => '',
    'opening'              => '<ul class="attachment-list wpp_attachment_list">',
    'closing'              => '</ul>',
    'before_item'          => '<li>',
    'after_item'           => '</li>',
    'show_descriptions'    => true,
    'include_icon_classes' => true,
    'showsize'             => false
  ), $atts );

  if( isset( $atts[ 'post_id' ] ) && is_numeric( $atts[ 'post_id' ] ) ) {
    $post = get_post( $atts[ 'post_id' ] );
  }

  if( !$post ) {
    return;
  }

  if( !empty( $atts[ 'type' ] ) ) {
    $types = explode( ',', str_replace( ' ', '', $atts[ 'type' ] ) );
  } else {
    $types = array();
  }

  $showsize   = ( $atts[ 'showsize' ] == true || $atts[ 'showsize' ] == 'true' || $atts[ 'showsize' ] == 1 ) ? true : false;
  $upload_dir = wp_upload_dir();

  $op = clone $post;
  $oq = clone $wp_query;

  foreach( array( 'before_list', 'after_list', 'opening', 'closing', 'before_item', 'after_item' ) as $htmlItem ) {
    $atts[ $htmlItem ] = str_replace( array( '&lt;', '&gt;' ), array( '<', '>' ), $atts[ $htmlItem ] );
  }

  $args = array(
    'post_type'   => 'attachment',
    'numberposts' => -1,
    'post_status' => null,
    'post_parent' => $post->ID,
  );

  if( !empty( $atts[ 'orderby' ] ) ) {
    $args[ 'orderby' ] = $atts[ 'orderby' ];
  }
  if( !empty( $atts[ 'order' ] ) ) {
    $atts[ 'order' ] = ( in_array( $atts[ 'order' ], array( 'a', 'asc', 'ascending' ) ) ) ? 'asc' : 'desc';
    $args[ 'order' ] = $atts[ 'order' ];
  }
  if( !empty( $atts[ 'groupby' ] ) ) {
    $args[ 'orderby' ] = $atts[ 'groupby' ];
  }

  $attachments = get_posts( $args );

  if( !empty( $attachments ) ) {
    $grouper = $atts[ 'groupby' ];
    $test    = $attachments;
    $test    = array_shift( $test );
    if( !property_exists( $test, $grouper ) ) {
      $grouper = 'post_' . $grouper;
    }

    $attlist = array();

    foreach( $attachments as $att ) {
      $key = ( !empty( $atts[ 'groupby' ] ) ) ? $att->$grouper : $att->ID;
      $key .= ( !empty( $atts[ 'orderby' ] ) ) ? $att->$atts[ 'orderby' ] : '';

      $attlink = wp_get_attachment_url( $att->ID );

      if( !empty( $types ) && is_array( $types ) ) {
        foreach( $types as $t ) {
          if( substr( $attlink, ( 0 - strlen( '.' . $t ) ) ) == '.' . $t ) {
            $attlist[ $key ]          = clone $att;
            $attlist[ $key ]->attlink = $attlink;
          }
        }
      } else {
        $attlist[ $key ]          = clone $att;
        $attlist[ $key ]->attlink = $attlink;
      }
    }
    if( $atts[ 'groupby' ] ) {
      if( $atts[ 'order' ] == 'asc' ) {
        ksort( $attlist );
      } else {
        krsort( $attlist );
      }
    }
  }

  if( !empty( $attlist ) && is_array( $attlist ) ) {
    $open = false;
    $r    = $atts[ 'before_list' ] . $atts[ 'opening' ];
    foreach( $attlist as $att ) {

      $container_classes = array( 'attachment_container' );

      //** Determine class to display for this file type */
      if( $atts[ 'include_icon_classes' ] ) {

        switch( $att->post_mime_type ) {

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

      if( $icon_class ) {
        $container_classes[ ] = 'has_icon';
      }

      if( !empty( $echo_description ) ) {
        $container_classes[ ] = 'has_description';
      }

      //** Add conditional classes if class is not already passed into container */
      if( !strpos( $atts[ 'before_item' ], 'class' ) ) {
        $this_before_item = str_replace( '>', ' class="' . implode( ' ', $container_classes ) . '">', $atts[ 'before_item' ] );
      }

      $echo_size = ( ( $showsize ) ? ' <span class="attachment-size">' . \UsabilityDynamics\WPP\Utility::get_filesize( str_replace( $upload_dir[ 'baseurl' ], $upload_dir[ 'basedir' ], $attlink ) ) . '</span>' : '' );

      if( !empty( $atts[ 'groupby' ] ) && $current_group != $att->$grouper ) {
        if( $open ) {
          $r .= $atts[ 'closing' ] . $atts[ 'after_item' ];
          $open = false;
        }
        $r .= $atts[ 'before_item' ] . '<h3>' . $att->$grouper . '</h3>' . $atts[ 'opening' ];
        $open          = true;
        $current_group = $att->$grouper;
      }
      $attlink = $att->attlink;
      $r .= $this_before_item . '<a href="' . $attlink . '" title="' . $echo_title . '" class="wpp_attachment ' . $icon_class . '">' . apply_filters( 'the_title', $att->post_title ) . '</a>' . $echo_size . $echo_description . $atts[ 'after_item' ];
    }
    if( $open ) {
      $r .= $atts[ 'closing' ] . $atts[ 'after_item' ];
    }
    $r .= $atts[ 'closing' ] . $atts[ 'after_list' ];
  }

  $wp_query = clone $oq;
  $post     = clone $op;

  return $r;

}