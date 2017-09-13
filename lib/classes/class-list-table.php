<?php
/**
 * Properties List Table class.
 *
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;
  use UsabilityDynamics\WPLT\WP_List_Table;

  if( !class_exists( 'UsabilityDynamics\WPP\List_Table' ) ) {

    class List_Table extends WP_List_Table {

      /**
       * @param array $args
       */
      public function __construct( $args = array() ) {

        wp_enqueue_script( 'wp-property-backend-global' );
        wp_enqueue_script( 'wp-property-admin-overview' );
        wp_enqueue_script( 'wpp-jquery-fancybox' );
        wp_enqueue_style( 'wpp-jquery-fancybox-css' );

        $this->args = wp_parse_args( $args, array(
          //singular name of the listed records
          'singular' => WPP_F::property_label(),
          //plural name of the listed records
          'plural' => WPP_F::property_label( 'plural' ),
          // Post Type
          'post_type' => 'property',
          'orderby' => 'ID',
          'order' => 'DESC',
        ) );

        //Set parent defaults
        parent::__construct( $this->args );

        add_filter( 'wplt_column_title_label', array( $this, 'get_column_title_label' ), 10, 2 );

        /* Determine if column contains numeric values */
        add_filter( 'wplt:orderby:is_numeric', array( $this, 'is_numeric_column' ), 10, 2 );

      }

      /**
       * Wrapper for UsabilityDynamics\WPLT\WP_List_Table::ajax_response() function.
       * To add the pagination args on response. 
       *
       * @access public
       */
      public function ajax_response() {
        do_action('wplt::ajax_response_action');
        $response = parent::ajax_response();
        $response['pagination']['_pagination_args'] = $this->_pagination_args;
        return $response;
      }

      /**
       * Allows to modify WP_Query arguments
       *
       * @param array $args
       * @return array
       */
      public function filter_wp_query( $args ) {
        /**
         * We switch 'any' with all possible statuses.
         * Because in some cases 'any' includes 'trash' and 'auto-draft' statuses
         * e.g. when custom post status registered it breaks 'exclude_from_search' for 'trash' status.
         */
        if( !empty( $args[ 'post_status' ] ) && is_string( $args[ 'post_status' ] ) && $args[ 'post_status' ] == 'any' ) {
          $args[ 'post_status' ] = array(
            'publish',
            'pending',
            'draft',
            'future',
            'private',
            'inherit'
          );
        }
        return apply_filters( 'wpp::all_properties::wp_query::args', $args );
      }

      /**
       * Determines if orderby values are numeric.
       * @param $bool
       * @param $column
       * @return bool
       */
      public function is_numeric_column( $bool, $column ) {
        $types = ud_get_wp_property( 'admin_attr_fields', array() );
        if( !empty( $types[ $column ] ) && in_array( $types[ $column ], array( 'number', 'currency' ) ) ) {
          return true;
        }
        return $bool;
      }

      /**
       * @return mixed|void
       */
      public function get_columns() {
        $columns = apply_filters( 'wpp_overview_columns', array(
          'cb' => '<input type="checkbox" />',
          'title' => __( 'Title', ud_get_wp_property( 'domain' ) ),
          'status' => __( 'Status', ud_get_wp_property( 'domain' ) ),
          'property_type' => __( 'Type', ud_get_wp_property( 'domain' ) ),
          'overview' => __( 'Overview', ud_get_wp_property( 'domain' ) ),
          'created' => __( 'Added', ud_get_wp_property( 'domain' ) ),
          'modified' => __( 'Updated', ud_get_wp_property( 'domain' ) ),
          'featured' => __( 'Featured', ud_get_wp_property( 'domain' ) ),
          'related' => sprintf( __( 'Related %s', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label('plural') ),
        ) );

        $meta = ud_get_wp_property( 'property_stats', array() );

        foreach( ud_get_wp_property( 'column_attributes', array() ) as $id => $slug ) {
          if( !empty( $meta[ $slug ] ) ) {
            $columns[ $slug ] = $meta[ $slug ];
          }
        }

        $columns[ 'thumbnail' ] = __( 'Thumbnail', ud_get_wp_property( 'domain' ) );
        return $columns;
      }

      /**
       * Sortable columns
       *
       * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
       */
      public function get_sortable_columns() {
        $columns = array(
          'title' => array( 'title', false ),  //true means it's already sorted
          'created' => array( 'date', false ),
          'property_type' => array( 'property_type', false ),
          'featured' => array( 'featured', false ),
          'modified' => array( 'modified', false ),
        );

        $sortable_attributes = ud_get_wp_property( 'sortable_attributes', array() );
        if( !empty( $sortable_attributes ) && is_array( $sortable_attributes ) ) {
          foreach( $sortable_attributes as $attribute ) {
            $columns[ $attribute ] = array( $attribute, false );
          }
        }

        $columns = apply_filters( 'wpp::columns::sortable', $columns );

        return $columns;
      }

      /**
       * Returns default value for column
       *
       * @param array $item
       * @param array $column_name
       * @return string
       */
      public function column_default( $item, $column_name ) {
        switch( $column_name ) {
          default:
            $attributes = ud_get_wp_property( 'property_stats' );
            if( array_key_exists( $column_name, $attributes ) ) {
              $value = get_post_meta( $item->ID, $column_name );
              $attribute = Attributes::get_attribute_data( $column_name );
              if( !$attribute[ 'multiple' ] ) {
                $value = !empty( $value[ 0 ] ) ? $value[ 0 ] : "-";
              } else {
                $value = implode( '<br/>', $value );
              }
              $value = apply_filters( "wpp::attribute::display", $value, $column_name, $item );
              $value = apply_filters( "wpp_stat_filter_{$column_name}", $value, $item );
              if( !empty( $value ) ) {
                return $value;
              }
            } else {
              $value = '';
              if( isset( $item->{$column_name} ) && is_string( $item->{$column_name} ) ) {
                $value = $item->{$column_name};
              }
              return apply_filters( "wpp_stat_filter_{$column_name}", $value, $item );
            }
        }
        return '-';
      }

      /**
       * Return Property Status
       *
       * @param $post
       * @return string
       */
      public function column_status( $post ) {
        switch( $post->post_status ) {
          case 'publish':
            $status = __( 'Published', ud_get_wp_property( 'domain' ) );
            break;
          case 'pending':
            $status = __( 'Pending', ud_get_wp_property( 'domain' ) );
            break;
          case 'trash':
            $status = __( 'Trashed', ud_get_wp_property( 'domain' ) );
            break;
          case 'auto-draft':
            $status = __( 'Auto Draft', ud_get_wp_property( 'domain' ) );
            break;
          default:
            $status = apply_filters( 'wpp::column_status::custom', ucfirst( $post->post_status ) );
            break;
        }
        return $status;
      }

      /**
       * Return Created date
       *
       * @param $post
       * @return string
       */
      public function column_created( $post ) {
        return get_the_date( get_option( 'date_format' ) . " " . get_option( 'time_format' ), $post );
      }

      /**
       * Return Modified date
       *
       * @param $post
       * @return string
       */
      public function column_modified( $post ) {
        return get_post_modified_time( get_option( 'date_format' ) . " " . get_option( 'time_format' ), null, $post, true );
      }

      /**
       * Shows Property Type
       *
       * - includes link to view type in admin UI
       *
       * @param $post
       * @return mixed|string
       */
      public function column_property_type( $post ) {
        $property_types = (array) ud_get_wp_property( 'property_types' );

        $type_slug = $post->property_type;

        if( isset( $type_slug ) && is_string( $type_slug ) && is_array( $property_types ) && !empty( $property_types[ $type_slug ] ) ) {
          $type_label = $property_types[ $type_slug ];
        }

        if( isset( $type_label ) && isset( $type_slug ) ) {
          $_html = '<a href="' . admin_url( 'edit.php?post_type=property&page=all_properties&wpp_listing_type=' . $type_slug ) . '" target="_blank" class="wpp-type-label" data-type="' . $type_slug . '">' . $type_label . '</a>';
        } else {
          $_html = '-';
        }

        return $_html;

      }

      /**
       * Return Overview Information
       *
       * @param $post
       * @return mixed|string
       */
      public function column_overview( $post ) {
        $data = '';
        $attributes = ud_get_wp_property( 'property_stats' );
        $stat_count = 0;
        $hidden_count = 0;
        $display_stats = array();

        $meta = get_post_custom( $post->ID );
        foreach( $meta as $k => $value ) {
          if( !array_key_exists( $k, $attributes ) ) {
            continue;
          }
          //** If has _ prefix it's a built-in WP key */
          if( '_' == $k{0} ) {
            continue;
          }
          $attribute = Attributes::get_attribute_data( $k );
          if( !$attribute[ 'multiple' ] ) {
            $value = $value[ 0 ];
          }
          $value = apply_filters( "wpp::attribute::display", $value, $k );
          $value = apply_filters( "wpp_stat_filter_{$k}", $value );
          $stat_count++;
          $stat_row_class = '';
          if( $stat_count > 5 ) {
            $stat_row_class = 'hidden wpp_overview_hidden_stats';
            $hidden_count++;
          }
          $display_stats[ $k ] = '<li class="' . $stat_row_class . '"><span class="wpp_label">' . $attributes[$k] . ':</span> <span class="wpp_value">' . $value . '</span></li>';
        }

        if( is_array( $display_stats ) && count( $display_stats ) > 0 ) {
          if( $stat_count > 5 ) {
            $display_stats[ 'toggle_advanced' ] = '<li class="wpp_show_advanced" advanced_option_class="wpp_overview_hidden_stats">' . sprintf( __( 'Toggle %1s more.', ud_get_wp_property()->domain ), $hidden_count ) . '</li>';
          }
          $data = '<ul class="wpp_overview_column_stats wpp_something_advanced_wrapper">' . implode( '', $display_stats ) . '</ul>';
        }
        return $data;
      }

      /**
       * Return Featured
       *
       * @param $post
       * @return mixed|string
       */
      public function column_featured( $post ) {
        $data = '';
        $featured = get_post_meta( $post->ID, 'featured', true );
        $featured = !empty( $featured ) && !in_array( $featured, array( '0', 'false' ) ) ? true : false;
        if( current_user_can( 'manage_wpp_make_featured' ) ) {
          if( $featured ) {
            $data .= "<input type='button' id='wpp_feature_{$post->ID}' class='wpp_featured_toggle wpp_is_featured' nonce='" . wp_create_nonce( 'wpp_make_featured_' . $post->ID ) . "' value='" . __( 'Featured', ud_get_wp_property( 'domain' ) ) . "' />";
          } else {
            $data .= "<input type='button' id='wpp_feature_{$post->ID}' class='wpp_featured_toggle' nonce='" . wp_create_nonce( 'wpp_make_featured_' . $post->ID ) . "'  value='" . __( 'Add to Featured', ud_get_wp_property( 'domain' ) ) . "' />";
          }
        } else {
          $data = $featured ? __( 'Featured', ud_get_wp_property( 'domain' ) ) : '';
        }
        return $data;
      }

      /**
       * Return Related Properties
       *
       * @todo Return children and/or parents in list.
       * @todo Use get_children()
       *
       * @param $post
       * @return mixed|string
       */
      public function column_related( $post ) {
        global $wpdb;

        $count = 0;
        $hidden_count = 0;

        $_response = array();

        $_parent_id = wp_get_post_parent_id( $post->ID );

        // If a parent is found and it is not same as the current property (can happen by mistake during imports) - potanin@UD
        if( $_parent_id && $_parent_id !== 0 && $_parent_id !== $post->ID) {
          $_parent_post = get_post($_parent_id);
          $_response[] = '<div class="wpp-property-parent"><a href="' . get_edit_post_link($_parent_id) . '">' . $_parent_post->post_title . '</a>' . '</div>';
        }

        $posts = $wpdb->get_results( "
          SELECT ID, post_title
            FROM {$wpdb->posts}
              WHERE post_type = 'property'
              AND post_status = 'publish'
              AND post_parent = '{$post->ID}' ORDER BY menu_order ASC
        ", ARRAY_A );

        if( !empty( $posts ) ) {
          $data = array();
          foreach( $posts as $post ) {
            $count++;
            $class = '';
            if( $count > 3 ) {
              $class = 'hidden wpp_overview_hidden_stats';
              $hidden_count++;
            }
            $data[] = '<li class="' . $class . '"><a href="' . get_edit_post_link($post['ID']) . '">' . $post[ 'post_title' ] . '</a></li>';
          }
          if( $count > 3 ) {
            $data[] = '<li class="wpp_show_advanced" advanced_option_class="wpp_overview_hidden_stats">' . sprintf( __( 'Toggle %1s more.', ud_get_wp_property()->domain ), $hidden_count ) . '</li>';
          }
          $_response[] =  '<div class="child-properties"><ul class="wpp_something_advanced_wrapper">' . implode( '', $data ) . '</ul>';
        }

        return implode( '', $_response );
      }

      /**
       * Return Thumnail
       *
       * @param $post
       * @return mixed|string
       */
      public function column_thumbnail( $post ) {

        $data = '';

        $wp_image_sizes = get_intermediate_image_sizes();
        $thumbnail_id = Property_Factory::get_thumbnail_id( $post->ID );

        if( $thumbnail_id ) {
          foreach( $wp_image_sizes as $image_name ) {
            $this_url = wp_get_attachment_image_src( $thumbnail_id, $image_name, true );
            $return[ 'images' ][ $image_name ] = $this_url[ 0 ];
          }
          $featured_image_id = $thumbnail_id;
        }

        if( empty( $featured_image_id ) ) {
          return $data;
        }

        $overview_thumb_type = ud_get_wp_property( 'configuration.admin_ui.overview_table_thumbnail_size' );

        if( empty( $overview_thumb_type ) ) {
          $overview_thumb_type = 'thumbnail';
        }

        $image_large_obj = wp_get_attachment_image_src( $featured_image_id, 'medium' );
        $image_thumb_obj = wp_get_attachment_image_src( $featured_image_id, $overview_thumb_type );

        if( !empty( $image_large_obj ) && !empty( $image_thumb_obj ) ) {
          $data = '<a href="' . $image_large_obj[ '0' ] . '" class="fancybox" rel="overview_group" title="' . $post->post_title . '"><img src="' . $image_thumb_obj[ '0' ] . '" width="' . $image_thumb_obj[ '1' ] . '" height="' . $image_thumb_obj[ '2' ] . '" /></a>';
        }

        return $data;
      }

      /**
       * Returns label for Title Column
       * @param $post
       * @return string|void
       * @internal param $title
       */
      public function get_column_title_label( $title, $post ) {
        $title = get_the_title( $post );

        if( empty( $title ) ) {
          $title = __( '(no name)' );
        }

        return $title;
      }

      /**
       * Add Bulk Actions
       *
       * @return array
       */
      public function get_bulk_actions() {
        $actions = array();

        if( current_user_can( 'delete_wpp_properties' ) ) {
          $actions[ 'untrash' ] = __( 'Restore', ud_get_wp_property( 'domain' ) );
          //$actions[ 'refresh' ] = __( 'Refresh', ud_get_wp_property( 'domain' ) );
          $actions[ 'delete' ] = __( 'Delete', ud_get_wp_property( 'domain' ) );
        }

        return apply_filters( 'wpp::all_properties::bulk_actions', $actions );

      }

      /**
       * Handle Bulk Action's request
       *
       */
      public function process_bulk_action() {

        try {

          switch( $this->current_action() ) {

            case 'untrash':
              if( empty( $_REQUEST[ 'post_ids' ] ) || !is_array( $_REQUEST[ 'post_ids' ] ) ) {
                throw new \Exception( sprintf( __( 'Invalid request: no %s IDs provided.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ) );
              }
              $unauthorized = 0;
              $post_ids = $_REQUEST[ 'post_ids' ];
              foreach( $post_ids as $post_id ) {
                $post_id = (int)$post_id;
                if(current_user_can('delete_wpp_property', $post_id)){
                  wp_untrash_post( $post_id );
                }
                else{
                  $unauthorized++;
                }
              }
              if( $unauthorized > 0 ) {
                $this->message = sprintf( __( 'You don\'t have permission to restore one or more selected %s.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label( 'plural' ) );
              } else{
                $this->message = sprintf( __( 'Selected %s have been successfully restored from Trash.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label( 'plural' ) );
              }
              break;

            case 'delete':
              if( empty( $_REQUEST[ 'post_ids' ] ) || !is_array( $_REQUEST[ 'post_ids' ] ) ) {
                throw new \Exception( sprintf( __( 'Invalid request: no %s IDs provided.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ) );
              }
              $unauthorized = 0;
              $post_ids = $_REQUEST[ 'post_ids' ];
              $unauthorized = 0;
              $trashed = 0;
              $deleted = 0;
              foreach( $post_ids as $post_id ) {
                $post_id = (int)$post_id;
                if(!current_user_can('delete_wpp_property', $post_id)){
                  $unauthorized++;
                }
                elseif( get_post_status( $post_id ) == 'trash' ) {
                  $deleted++;
                  wp_delete_post( $post_id );
                } else {
                  $trashed++;
                  wp_trash_post( $post_id );
                }
              }

              if( $unauthorized > 0 ) {
                $this->message = sprintf( __( 'You don\'t have permission to delete one or more selected %s.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label( 'plural' ) );
              } elseif( $trashed > 0 && $deleted > 0 ) {
                $this->message = sprintf( __( 'Selected %s have been successfully moved to Trash or deleted.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label( 'plural' ) );
              } elseif( $trashed > 0 ) {
                $this->message = sprintf( __( 'Selected %s have been successfully moved to Trash.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label( 'plural' ) );
              } elseif( $deleted > 0 ) {
                $this->message = sprintf( __( 'Selected %s have been successfully deleted.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label( 'plural' ) );
              } else {
                throw new \Exception( sprintf( __( 'No one %s was deleted.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ) );
              }
              break;

            default:
              //** Any custom action can be processed using action hook */
              do_action( 'wpp::all_properties::process_bulk_action', $this->current_action(), $this );
              break;

          }

        } catch ( \Exception $e ) {
          $this->error = $e->getMessage();
        }

      }

    }

  }

}
