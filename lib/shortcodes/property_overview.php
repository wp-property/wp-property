<?php
/**
 * Name: Property Overview
 * ID: property_overview
 * Type: shortcode
 * Group: WP-Property
 * Class: UsabilityDynamics\WPP\Shortcode_Overview
 * Version: 2.0.0
 * Description: Property Overview shortcode is used to display a list of properties.  It returns all the published properties on your site
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Shortcode_Overview' ) ) {
    /**
     * Displays property overview
     * Performs searching/filtering functions, provides template with $properties file
     * Retirms html content to be displayed after location attribute on property edit page
     *
     * @since 1.081
     *
     * @param string $atts
     * @param string $content
     *
     * @return string $result
     * @uses \UsabilityDynamics\WPP\Utility::get_properties()
     */
    class Shortcode_Overview extends \UsabilityDynamics\Shortcode\Shortcode {
    
      public $id = 'property_overview';
      
      public $group = 'WP-Property';
      
      public function __construct( $options = array() ) {
        $this->name = __( 'Property Overview', 'wpp' );
        $this->description = __( 'Property Overview shortcode is used to display a list of properties.  It returns all the published properties on your site', 'wpp' );
        $this->params = array(
          'show_children' => array( 
            'type' => 'boolean',
            'description' => __( 'Switches children property displaying', 'wpp' ),
            'default' => __( 'Property Settings configuration', 'wpp' ),
          ),
          'child_properties_title' => array( 
            'description' => __( 'Renames child properties tittle', 'wpp' ),
            'default' => __( 'Floor plans at location', 'wpp' ),
          ),
          'fancybox_preview' => array( 
            'type' => 'boolean',
            'description' => __( 'Switches fancybox preview', 'wpp' ),
            'default' => __( 'Property Settings configuration', 'wpp' ),
          ),
          'bottom_pagination_flag' => array( 
            'type' => 'boolean',
            'description' => __( 'Switches bottom pagination flag', 'wpp' ),
            'default' => __( 'Property Settings configuration', 'wpp' ),
          ),
          'thumbnail_size' => array(),
          'sort_by_text ' => array(),
          'sort_by' => array(),
          'sort_order' => array(),
          'template' => array(),
          'sorter_type' => array(),
          'pagination' => array(),
          'per_page' => array(),
          'starting_row' => array(),
          'detail_button' => array(),
          'hide_count' => array(),
          'in_new_window ' => array(),
          'strict_search' => array(),
        );
        
        // Ajax pagination for property_overview
        add_action( "wp_ajax_wpp_property_overview_pagination", array( $this, "ajax_call" ) );
        add_action( "wp_ajax_nopriv_wpp_property_overview_pagination", array( $this, "ajax_call" ) );
        
        parent::__construct( $options );
      }

      public function call( $atts = "" ) {
        global $wp_properties, $wpp_query, $property, $post, $wp_query;

        $atts = wp_parse_args( $atts, array(
          'strict_search' => 'false'
        ) );

        Utility::force_script_inclusion( 'jquery-ui-widget' );
        Utility::force_script_inclusion( 'jquery-ui-mouse' );
        Utility::force_script_inclusion( 'jquery-ui-slider' );
        Utility::force_script_inclusion( 'wpp-jquery-address' );
        Utility::force_script_inclusion( 'wpp-jquery-scrollTo' );
        Utility::force_script_inclusion( 'wpp-jquery-fancybox' );
        Utility::force_script_inclusion( 'wp-property-frontend' );

        //** Load all queriable attributes **/
        foreach ( Utility::get_queryable_keys() as $key ) {
          //** This needs to be done because a key has to exist in the $deafult array for shortcode_atts() to load passed value */
          $queryable_keys[ $key ] = false;
        }

        //** Allow the shorthand of "type" as long as there is not a custom attribute of "type". If "type" does exist as an attribute, then users need to use the full "property_type" query tag. **/
        if ( !array_key_exists( 'type', $queryable_keys ) && ( is_array( $atts ) && array_key_exists( 'type', $atts ) ) ) {
          $atts[ 'property_type' ] = $atts[ 'type' ];
          unset( $atts[ 'type' ] );
        }

        //** Get ALL allowed attributes that may be passed via shortcode (to include property attributes) */
        $defaults[ 'show_children' ] = ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] : 'true' );
        $defaults[ 'child_properties_title' ] = __( 'Floor plans at location:', 'wpp' );
        $defaults[ 'fancybox_preview' ] = $wp_properties[ 'configuration' ][ 'property_overview' ][ 'fancybox_preview' ];
        $defaults[ 'bottom_pagination_flag' ] = ( $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] == 'true' ? true : false );
        $defaults[ 'thumbnail_size' ] = $wp_properties[ 'configuration' ][ 'property_overview' ][ 'thumbnail_size' ];
        $defaults[ 'sort_by_text' ] = __( 'Sort By:', 'wpp' );
        $defaults[ 'sort_by' ] = 'post_date';
        $defaults[ 'sort_order' ] = 'DESC';
        $defaults[ 'template' ] = false;
        $defaults[ 'ajax_call' ] = false;
        $defaults[ 'disable_wrapper' ] = false;
        $defaults[ 'sorter_type' ] = 'buttons';
        $defaults[ 'pagination' ] = 'on';
        $defaults[ 'hide_count' ] = false;
        $defaults[ 'per_page' ] = 10;
        $defaults[ 'starting_row' ] = 0;
        $defaults[ 'unique_hash' ] = rand( 10000, 99900 );
        $defaults[ 'detail_button' ] = false;
        $defaults[ 'stats' ] = '';
        $defaults[ 'class' ] = 'wpp_property_overview_shortcode';
        $defaults[ 'in_new_window' ] = false;

        $defaults = apply_filters( 'shortcode_property_overview_allowed_args', $defaults, $atts );

        //** We add # to value which says that we don't want to use LIKE in SQL query for searching this value. */
        $required_strict_search = apply_filters( 'wpp::required_strict_search', array( 'wpp_agents' ) );
        foreach ( $atts as $key => $val ) {
          if ( ( ( $atts[ 'strict_search' ] == 'true' && isset( $wp_properties[ 'property_stats' ][ $key ] ) ) ||
               in_array( $key, $required_strict_search ) ) &&
               !key_exists( $key, $defaults ) && $key != 'property_type'
          ) {
            if ( substr_count( $val, ',' ) || substr_count( $val, '&ndash;' ) || substr_count( $val, '--' ) ) {
              continue;
            }
            $atts[ $key ] = '#' . $val . '#';
          }
        }

        if ( !empty( $atts[ 'ajax_call' ] ) ) {
          //** If AJAX call then the passed args have all the data we need */
          $wpp_query = $atts;

          //* Fix ajax data. Boolean value false is returned as string 'false'. */
          foreach ( $wpp_query as $key => $value ) {
            if ( $value == 'false' ) {
              $wpp_query[ $key ] = false;
            }
          }

          $wpp_query[ 'ajax_call' ] = true;

          //** Everything stays the same except for sort order and page */
          $wpp_query[ 'starting_row' ] = ( ( $wpp_query[ 'requested_page' ] - 1 ) * $wpp_query[ 'per_page' ] );

          //** Figure out current page */
          $wpp_query[ 'current_page' ] = $wpp_query[ 'requested_page' ];

        } else {
          /** Determine if fancybox style is included */
          Utility::force_style_inclusion( 'wpp-jquery-fancybox-css' );

          //** Merge defaults with passed arguments */
          $wpp_query = shortcode_atts( $defaults, $atts );
          $wpp_query[ 'query' ] = shortcode_atts( $queryable_keys, $atts );

          //** Handle search */
          if ( $wpp_search = $_REQUEST[ 'wpp_search' ] ) {
            $wpp_query[ 'query' ] = shortcode_atts( $wpp_query[ 'query' ], $wpp_search );
            $wpp_query[ 'query' ] = Utility::prepare_search_attributes( $wpp_query[ 'query' ] );

            if ( isset( $_REQUEST[ 'wpp_search' ][ 'sort_by' ] ) ) {
              $wpp_query[ 'sort_by' ] = $_REQUEST[ 'wpp_search' ][ 'sort_by' ];
            }

            if ( isset( $_REQUEST[ 'wpp_search' ][ 'sort_order' ] ) ) {
              $wpp_query[ 'sort_order' ] = $_REQUEST[ 'wpp_search' ][ 'sort_order' ];
            }

            if ( isset( $_REQUEST[ 'wpp_search' ][ 'pagination' ] ) ) {
              $wpp_query[ 'pagination' ] = $_REQUEST[ 'wpp_search' ][ 'pagination' ];
            }

            if ( isset( $_REQUEST[ 'wpp_search' ][ 'per_page' ] ) ) {
              $wpp_query[ 'per_page' ] = $_REQUEST[ 'wpp_search' ][ 'per_page' ];
            }
          }

        }

        //** Load certain settings into query for get_properties() to use */
        $wpp_query[ 'query' ][ 'sort_by' ] = $wpp_query[ 'sort_by' ];
        $wpp_query[ 'query' ][ 'sort_order' ] = $wpp_query[ 'sort_order' ];

        $wpp_query[ 'query' ][ 'pagi' ] = $wpp_query[ 'starting_row' ] . '--' . $wpp_query[ 'per_page' ];

        if ( !isset( $wpp_query[ 'current_page' ] ) ) {
          $wpp_query[ 'current_page' ] = ( $wpp_query[ 'starting_row' ] / $wpp_query[ 'per_page' ] ) + 1;
        }

        //** Load settings that are not passed via shortcode atts */
        $wpp_query[ 'sortable_attrs' ] = Utility::get_sortable_keys();

        //** Replace dynamic field values */

        //** Detect currently property for conditional in-shortcode usage that will be replaced from values */
        if ( isset( $post ) ) {

          $dynamic_fields[ 'post_id' ] = $post->ID;
          $dynamic_fields[ 'post_parent' ] = $post->parent_id;
          $dynamic_fields[ 'property_type' ] = $post->property_type;

          $dynamic_fields = apply_filters( 'shortcode_property_overview_dynamic_fields', $dynamic_fields );

          if ( is_array( $dynamic_fields ) ) {
            foreach ( $wpp_query[ 'query' ] as $query_key => $query_value ) {
              if ( !empty( $dynamic_fields[ $query_value ] ) ) {
                $wpp_query[ 'query' ][ $query_key ] = $dynamic_fields[ $query_value ];
              }
            }
          }
        }

        //** Remove all blank values */
        $wpp_query[ 'query' ] = array_filter( $wpp_query[ 'query' ] );

        //** Unset this because it gets passed with query (for back-button support) but not used by get_properties() */
        unset( $wpp_query[ 'query' ][ 'per_page' ] );
        unset( $wpp_query[ 'query' ][ 'pagination' ] );
        unset( $wpp_query[ 'query' ][ 'requested_page' ] );

        //** Load the results */
        $wpp_query[ 'properties' ] = Utility::get_properties( $wpp_query[ 'query' ], true );

        //** Calculate number of pages */
        if ( $wpp_query[ 'pagination' ] == 'on' ) {
          $wpp_query[ 'pages' ] = ceil( $wpp_query[ 'properties' ][ 'total' ] / $wpp_query[ 'per_page' ] );
        }

        //** Set for quick access (for templates */
        $property_type = $wpp_query[ 'query' ][ 'property_type' ];

        if ( !empty( $property_type ) ) {
          foreach ( (array) $wp_properties[ 'hidden_attributes' ][ $property_type ] as $attr_key ) {
            unset( $wpp_query[ 'sortable_attrs' ][ $attr_key ] );
          }
        }

        //** Legacy Support - include variables so old templates still work */
        $properties = $wpp_query[ 'properties' ][ 'results' ];
        $thumbnail_sizes = Utility::image_sizes( $wpp_query[ 'thumbnail_size' ] );
        $child_properties_title = $wpp_query[ 'child_properties_title' ];
        $unique = $wpp_query[ 'unique_hash' ];
        $thumbnail_size = $wpp_query[ 'thumbnail_size' ];

        //* Debugger */
        if ( $wp_properties[ 'configuration' ][ 'developer_mode' ] == 'true' && !$wpp_query[ 'ajax_call' ] ) {
          echo '<script type="text/javascript">console.log( ' . json_encode( $wpp_query ) . ' ); </script>';
        }

        ob_start();

        //** Make certain variables available to be used within the single listing page */
        $wpp_overview_shortcode_vars = apply_filters( 'wpp_overview_shortcode_vars', array(
          'wp_properties' => $wp_properties,
          'wpp_query' => $wpp_query
        ) );

        //** By merging our extra variables into $wp_query->query_vars they will be extracted in load_template() */
        if ( is_array( $wpp_overview_shortcode_vars ) ) {
          $wp_query->query_vars = array_merge( $wp_query->query_vars, $wpp_overview_shortcode_vars );
        }

        $template = $wpp_query[ 'template' ];
        $fancybox_preview = $wpp_query[ 'fancybox_preview' ];
        $show_children = $wpp_query[ 'show_children' ];
        $class = $wpp_query[ 'class' ];
        $stats = $wpp_query[ 'stats' ];
        $in_new_window = ( !empty( $wpp_query[ 'in_new_window' ] ) ? " target=\"_blank\" " : "" );

        //** Make query_vars available to emulate WP template loading */
        extract( $wp_query->query_vars, EXTR_SKIP );

        //** Try find custom template */
        $template_found = Utility::get_template_part( array(
          "property-overview-{$template}",
          "property-overview-{$property_type}",
          "property-{$template}",
          "property-overview",
        ), array( WPP_Templates ) );
        
        if ( $template_found ) {
          include $template_found;
        }

        $ob_get_contents = ob_get_contents();
        ob_end_clean();

        $ob_get_contents = apply_filters( 'shortcode_property_overview_content', $ob_get_contents, $wpp_query );

        // Initialize result (content which will be shown) and open wrap (div) with unique id
        if ( $wpp_query[ 'disable_wrapper' ] != 'true' ) {
          $result[ 'top' ] = '<div id="wpp_shortcode_' . $defaults[ 'unique_hash' ] . '" class="wpp_ui ' . $wpp_query[ 'class' ] . '">';
        }

        $result[ 'top_pagination' ] = wpp_draw_pagination( array(
          'class' => 'wpp_top_pagination',
          'sorter_type' => $wpp_query[ 'sorter_type' ],
          'hide_count' => $wpp_query[ 'hide_count' ],
          'sort_by_text' => $wpp_query[ 'sort_by_text' ],
        ) );
        $result[ 'result' ] = $ob_get_contents;

        if ( $wpp_query[ 'bottom_pagination_flag' ] == 'true' ) {
          $result[ 'bottom_pagination' ] = wpp_draw_pagination( array(
            'class' => 'wpp_bottom_pagination',
            'sorter_type' => $wpp_query[ 'sorter_type' ],
            'hide_count' => $wpp_query[ 'hide_count' ],
            'sort_by_text' => $wpp_query[ 'sort_by_text' ],
            'javascript' => false
          ) );
        }

        if ( $wpp_query[ 'disable_wrapper' ] != 'true' ) {
          $result[ 'bottom' ] = '</div>';
        }

        $result = apply_filters( 'wpp_property_overview_render', $result );

        if ( $wpp_query[ 'ajax_call' ] ) {
          return json_encode( array( 'wpp_query' => $wpp_query, 'display' => implode( '', $result ) ) );
        } else {
          return implode( '', $result );
        }
      }
    
      /**
       * Returns property overview data for AJAX calls
       *
       */
      function ajax_property_overview() {
        $params = $_REQUEST[ 'wpp_ajax_query' ];
        if ( !empty( $params[ 'action' ] ) ) {
          unset( $params[ 'action' ] );
        }
        $params[ 'ajax_call' ] = true;
        $data = $this->call( $params );
        die( $data );
      }
    
    }
  }

}
