<?php
/**
 * WP-Property Core Framework
 *
 * Contains primary functions for setting up the framework of the plugin.
 *
 * @version 1.08
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @subpackage Main
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\WPP_Core' ) ) {

    class WPP_Core {

      /**
       * Highest-level function initialized on plugin load
       *
       * @since 1.11
       *
       */
      function WPP_Core() {

      }

      /**
       * Register metaboxes.
       *
       * @global type $post
       * @global type $wpdb
       */
      function add_meta_boxes() {
        global $post, $wpdb;

        //** Add metabox for child properties */
        if( $post->post_type == 'property' && $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = '{$post->ID}' AND post_status = 'publish' " ) ) {
          add_meta_box( 'wpp_property_children', sprintf( __( 'Child %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ), array( 'WPP_UI', 'child_properties' ), 'property', 'side', 'high' );
        }
      }

      /**
       * Check if WP-Property RaaS Active
       *
       * @return bool
       */
      static function is_active() {
        return true;
      }

      /**
       * Adds thumbnail feature to WP-Property pages
       *
       *
       * @todo Make sure only ran on property pages
       * @since 0.60
       *
       */
      static function after_setup_theme() {
        add_theme_support( 'post-thumbnails' );
      }

      /**
       * Adds "Settings" link to the plugin overview page
       *
       *  *
       * @since 0.60
       *
       */
      static function plugin_action_links( $links, $file ) {

        if( $file == 'wp-property/wp-property.php' ) {
          $settings_link = '<a href="' . admin_url( "edit.php?post_type=property&page=property_settings" ) . '">' . __( 'Settings', 'wpp' ) . '</a>';
          array_unshift( $links, $settings_link ); // before other links
        }

        return $links;
      }

      /**
       * Can enqueue scripts on specific pages, and print content into head
       *
       *
       * @uses $current_screen global variable
       * @since 0.53
       *
       */
      function admin_enqueue_scripts( $hook ) {
        global $current_screen, $wp_properties, $wpdb;

        wp_localize_script( 'wpp-localization', 'wpp', array( 'instance' => $this->get_instance() ) );

        switch( $current_screen->id ) {

          //** Property Overview Page and Edit Property page */
          case 'property_page_all_properties':
            wp_enqueue_script( 'wp-property-backend-global' );
            wp_enqueue_script( 'wp-property-admin-overview' );

          case 'property':
            wp_enqueue_script( 'wp-property-global' );
            //** Enabldes fancybox js, css and loads overview scripts */
            wp_enqueue_script( 'post' );
            wp_enqueue_script( 'postbox' );
            wp_enqueue_script( 'wpp-jquery-fancybox' );
            wp_enqueue_script( 'wpp-jquery-data-tables' );
            wp_enqueue_style( 'wpp-jquery-fancybox-css' );
            wp_enqueue_style( 'wpp-jquery-data-tables' );
            //** Get width of overview table thumbnail, and set css */
            $thumbnail_attribs = WPP_F::image_sizes( $wp_properties[ 'configuration' ][ 'admin_ui' ][ 'overview_table_thumbnail_size' ] );
            $thumbnail_width   = ( !empty( $thumbnail_attribs[ 'width' ] ) ? $thumbnail_attribs[ 'width' ] : false );
            if( $thumbnail_width ) {
              ?>
              <style typ="text/css">
            #wp-list-table.wp-list-table .column-thumbnail {
              width: <?php echo $thumbnail_width + 20; ?>px;
            }

            #wp-list-table.wp-list-table td.column-thumbnail {
              text-align: right;
            }

            #wp-list-table.wp-list-table .column-type {
              width: 90px;
            }

            #wp-list-table.wp-list-table .column-menu_order {
              width: 50px;
            }

            #wp-list-table.wp-list-table td.column-menu_order {
              text-align: center;
            }

            #wp-list-table.wp-list-table .column-featured {
              width: 100px;
            }

            #wp-list-table.wp-list-table .check-column {
              width: 26px;
            }
          </style>
            <?php
            }
            break;

          //** Settings Page */
          case 'property_page_property_settings':
            wp_enqueue_script( 'wp-property-backend-global' );
            wp_enqueue_script( 'wp-property-global' );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'wpp-jquery-colorpicker' );
            wp_enqueue_script( 'wp-property-admin-settings' );
            wp_enqueue_style( 'wpp-jquery-colorpicker-css' );
            break;

          //** Widgets Page */
          case 'widgets':
            wp_enqueue_script( 'wp-property-backend-global' );
            wp_enqueue_script( 'wp-property-global' );
            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_style( 'jquery-ui' );
            wp_enqueue_script( 'wp-property-admin-widgets' );
            break;

        }

        //** Automatically insert styles sheet if one exists with $current_screen->ID name */
        if( file_exists( WPP_Path . "/css/{$current_screen->id}.css" ) ) {
          wp_enqueue_style( $current_screen->id . '-style', WPP_URL . "/css/{$current_screen->id}.css", array(), WPP_Version, 'screen' );
        }

        //** Automatically insert JS sheet if one exists with $current_screen->ID name */
        if( file_exists( WPP_Path . "js/{$current_screen->id}.js" ) ) {
          wp_enqueue_script( $current_screen->id . '-js', WPP_URL . "js/{$current_screen->id}.js", array( 'jquery' ), WPP_Version, 'wp-property-backend-global' );
        }

        //** Enqueue CSS styles on all pages */
        if( file_exists( WPP_Path . 'css/wp_properties_admin.css' ) ) {
          wp_register_style( 'wpp-admin-styles', WPP_URL . 'css/wp_properties_admin.css' );
          wp_enqueue_style( 'wpp-admin-styles' );
        }

      }

      /**
       * Sets up additional pages and loads their scripts
       *
       * @since 0.5
       *
       */
      static function admin_menu() {
        global $wp_properties, $submenu;

        // Create property settings page
        $modules_page   = add_submenu_page( 'edit.php?post_type=property', __( 'Modules', 'wpp' ), __( 'Modules', 'wpp' ), 'manage_wpp_modules', 'modules', create_function( '', 'global $wp_properties; include "ui/page_modules.php";' ) );
        $settings_page  = add_submenu_page( 'edit.php?post_type=property', __( 'Settings', 'wpp' ), __( 'Settings', 'wpp' ), 'manage_wpp_settings', 'property_settings', create_function( '', 'global $wp_properties; include "ui/page_settings.php";' ) );
        $all_properties = add_submenu_page( 'edit.php?post_type=property', $wp_properties[ 'labels' ][ 'all_items' ], $wp_properties[ 'labels' ][ 'all_items' ], 'edit_wpp_properties', 'all_properties', create_function( '', 'global $wp_properties, $screen_layout_columns; include "ui/page_all_properties.php";' ) );

        /**
         * Next used to add custom submenu page 'All Properties' with Javascript dataTable
         *
         * @author Anton K
         */
        if( !empty( $submenu[ 'edit.php?post_type=property' ] ) ) {

          //** Comment next line if you want to get back old Property list page. */
          array_shift( $submenu[ 'edit.php?post_type=property' ] );

          foreach( $submenu[ 'edit.php?post_type=property' ] as $key => $page ) {
            if( $page[ 2 ] == 'all_properties' ) {
              unset( $submenu[ 'edit.php?post_type=property' ][ $key ] );
              array_unshift( $submenu[ 'edit.php?post_type=property' ], $page );
            } elseif( $page[ 2 ] == 'post-new.php?post_type=property' ) {
              //** Removes 'Add Property' from menu if user can not edit properties. peshkov@UD */
              if( !current_user_can( 'edit_wpp_property' ) ) {
                unset( $submenu[ 'edit.php?post_type=property' ][ $key ] );
              }
            }
          }
        }

        do_action( 'wpp_admin_menu' );

        // Load jQuery UI Tabs and Cookie into settings page (settings_page_property_settings)
        add_action( 'admin_print_scripts-' . $settings_page, create_function( '', "wp_enqueue_script('jquery-ui-tabs');wp_enqueue_script('jquery-cookie');" ) );
        add_action( 'admin_print_scripts-' . $modules_page, create_function( '', "wp_enqueue_script('jquery-ui-tabs');wp_enqueue_script('jquery-cookie');" ) );
        add_action( 'admin_print_scripts-' . $all_properties, create_function( '', "wp_enqueue_script('jquery-ui-tabs');wp_enqueue_script('jquery-cookie');" ) );

      }

      /**
       * Modify admin body class on property pages for CSS
       *
       * @todo $current_screen does not seem to work in 3.8.
       *
       * @since 0.5
       */
      static function admin_body_class( $admin_body_class ) {
        global $current_screen;

        $classes = explode( ' ', trim( $admin_body_class ) );

        $classes[ ] = self::is_active() ? 'wpp-connected' : 'wpp-disconnected';

        if( $current_screen->id == 'edit-property' ) {
          $classes[ ] = 'wpp_property_edit';
        }

        if( $current_screen->id == 'property' ) {
          $classes[ ] = 'wpp_property_edit';
        }

        return implode( ' ', array_unique( $classes ) );

      }

      /**
       * Fixed property pages being seen as 404 pages
       *
       * Ran on parse_request;
       *
       * WP handle_404() function decides if current request should be a 404 page
       * Marking the global variable $wp_query->is_search to true makes the function
       * assume that the request is a search.
       *
       * @param $query
       *
       * @since 0.5
       */
      static function parse_request( $query ) {
        global $wp, $wp_query, $wp_properties, $wpdb;

        //** If we don't have permalinks, our base slug is always default */
        if( get_option( 'permalink_structure' ) == '' ) {
          $wp_properties[ 'configuration' ][ 'base_slug' ] = 'property';
        }

        //** If we are displaying search results, we can assume this is the default property page */
        if( is_array( $_REQUEST[ 'wpp_search' ] ) ) {

          if( isset( $_POST[ 'wpp_search' ] ) ) {
            $query = '?' . http_build_query( array( 'wpp_search' => $_REQUEST[ 'wpp_search' ] ), '', '&' );
            wp_redirect( WPP_F::base_url( $wp_properties[ 'configuration' ][ 'base_slug' ] ) . $query );
            die();
          }

          $wp_query->wpp_root_property_page = true;
          $wp_query->wpp_search_page        = true;
        }

        //** Determine if this is the Default Property Page */

        if( isset( $wp_properties[ 'configuration' ][ 'base_slug' ] ) && $wp->request == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
          $wp_query->wpp_root_property_page = true;
        }

        if( !empty( $wp_properties[ 'configuration' ][ 'base_slug' ] ) && $wp->query_string == "p=" . $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
          $wp_query->wpp_root_property_page = true;
        }

        if( isset( $query->query_vars[ 'name' ] ) && $query->query_vars[ 'name' ] == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
          $wp_query->wpp_root_property_page = true;
        }

        if( isset( $query->query_vars[ 'pagename' ] ) && $query->query_vars[ 'pagename' ] == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
          $wp_query->wpp_root_property_page = true;
        }

        if( isset( $query->query_vars[ 'category_name' ] ) && $query->query_vars[ 'category_name' ] == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
          $wp_query->wpp_root_property_page = true;
        }

        //** If this is a the root property page, and the Dynamic Default Property page is used */
        if( $wp_query->wpp_root_property_page && $wp_properties[ 'configuration' ][ 'base_slug' ] == 'property' ) {
          $wp_query->wpp_default_property_page = true;

          WPP_F::console_log( 'Overriding default 404 page status.' );

          /** Set to override the 404 status */
          add_action( 'wp', create_function( '', 'status_header( 200 );' ) );

          //** Prevent is_404() in template files from returning true */
          add_action( 'template_redirect', create_function( '', ' global $wp_query; $wp_query->is_404 = false;' ), 0, 10 );
        }

        if( $wp_query->wpp_search_page ) {
          $wpp_pages[ ] = 'Search Page';
        }

        if( $wp_query->wpp_default_property_page ) {
          $wpp_pages[ ] = 'Default Property Page';
        }

        if( $wp_query->wpp_root_property_page ) {
          $wpp_pages[ ] = 'Root Property Page.';
        }

        if( is_array( $wpp_pages ) ) {
          WPP_F::console_log( 'WPP_F::parse_request() ran, determined that request is for: ' . implode( ', ', $wpp_pages ) );
        }

      }

      /**
       * Modifies post content
       *
       * @since 1.04
       *
       */
      static function the_content( $content ) {
        global $post, $wp_properties, $wp_query;

        if( !isset( $wp_query->is_property_overview ) ) {
          return $content;
        }

        //** Handle automatic PO inserting for non-search root page */
        if( !$wp_query->wpp_search_page && $wp_query->wpp_root_property_page && $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] == 'true' ) {
          WPP_F::console_log( 'Automatically inserted property overview shortcode into page content.' );

          return WPP_Core::shortcode_property_overview();
        }

        //** Handle automatic PO inserting for search pages */
        if( $wp_query->wpp_search_page && $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] != 'true' ) {
          WPP_F::console_log( 'Automatically inserted property overview shortcode into search page content.' );

          return WPP_Core::shortcode_property_overview();
        }

        return $content;
      }

      /**
       * Hooks into save_post function and saves additional property data
       *
       *
       * @todo Add some sort of custom capability so not only admins can make properties as featured. i.e. Agents can make their own properties featured.
       * @since 1.04
       *
       */
      static function save_property( $post_id ) {
        global $wp_properties, $wp_version;

        $_wpnonce = ( version_compare( $wp_version, '3.5', '>=' ) ? 'update-post_' : 'update-property_' ) . $post_id;
        if( !wp_verify_nonce( $_POST[ '_wpnonce' ], $_wpnonce ) || $_POST[ 'post_type' ] !== 'property' ) {
          return $post_id;
        }

        //* Delete cache files of search values for search widget's form */
        $directory = WPP_Path . 'cache/searchwidget';

        if( is_dir( $directory ) ) {
          $dir = opendir( $directory );
          while( ( $cachefile = readdir( $dir ) ) ) {
            if( is_file( $directory . "/" . $cachefile ) ) {
              unlink( $directory . "/" . $cachefile );
            }
          }
        }

        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
          return $post_id;
        }

        $update_data = $_REQUEST[ 'wpp_data' ][ 'meta' ];

        //** Neccessary meta data which is required by Supermap Premium Feature. Should be always set even the Supermap disabled. peshkov@UD */
        if( empty( $_REQUEST[ 'exclude_from_supermap' ] ) ) {
          if( !metadata_exists( 'post', $post_id, 'exclude_from_supermap' ) ) {
            $update_data[ 'exclude_from_supermap' ] = 'false';
          }
        }

        if( (float) $update_data[ 'latitude' ] == 0 ) $update_data[ 'latitude' ] = '';
        if( (float) $update_data[ 'longitude' ] == 0 ) $update_data[ 'longitude' ] = '';

        /* get old coordinates and location */
        $old_lat  = get_post_meta( $post_id, 'latitude', true );
        $old_lng  = get_post_meta( $post_id, 'longitude', true );
        $geo_data = array(
          'old_coordinates' => ( ( empty( $old_lat ) ) || ( empty( $old_lng ) ) ) ? "" : array( 'lat' => $old_lat, 'lng' => $old_lng ),
          'old_location'    => ( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) ) ? get_post_meta( $post_id, $wp_properties[ 'configuration' ][ 'address_attribute' ], true ) : ''
        );

        foreach( $update_data as $meta_key => $meta_value ) {
          $attribute_data = WPP_F::get_attribute_data( $meta_key );

          //* Cleans the user input */
          $meta_value = WPP_F::encode_mysql_input( $meta_value, $meta_key );

          //* Only admins can mark properties as featured. */
          if( $meta_key == 'featured' && !current_user_can( 'manage_options' ) ) {
            //** But be sure that meta 'featured' exists at all */
            if( !metadata_exists( 'post', $post_id, $meta_key ) ) {
              $meta_value = 'false';
            } else {
              continue;
            }
          }

          //* Remove certain characters */

          if( $attribute_data[ 'currency' ] || $attribute_data[ 'numeric' ] ) {
            $meta_value = str_replace( array( "$", "," ), '', $meta_value );
          }

          //* Overwrite old post meta allowing only one value */
          delete_post_meta( $post_id, $meta_key );
          add_post_meta( $post_id, $meta_key, $meta_value );
        }

        //* Check if property has children */
        $children = get_children( "post_parent=$post_id&post_type=property" );

        //* Write any data to children properties that are supposed to inherit things */
        if( count( $children ) > 0 ) {
          //* 1) Go through all children */
          foreach( $children as $child_id => $child_data ) {
            //* Determine child property_type */
            $child_property_type = get_post_meta( $child_id, 'property_type', true );
            //* Check if child's property type has inheritence rules, and if meta_key exists in inheritance array */
            if( is_array( $wp_properties[ 'property_inheritance' ][ $child_property_type ] ) ) {
              foreach( $wp_properties[ 'property_inheritance' ][ $child_property_type ] as $i_meta_key ) {
                $parent_meta_value = get_post_meta( $post_id, $i_meta_key, true );
                //* inheritance rule exists for this property_type for this meta_key */
                update_post_meta( $child_id, $i_meta_key, $parent_meta_value );
              }
            }
          }
        }

        WPP_F::maybe_set_gpid( $post_id );

        if( isset( $_REQUEST[ 'parent_id' ] ) ) {
          $_REQUEST[ 'parent_id' ] = WPP_F::update_parent_id( $_REQUEST[ 'parent_id' ], $post_id );
        }

        do_action( 'save_property', $post_id );

        return true;
      }

      /**
       * Inserts content into the "Publish" metabox on property pages
       *
       * @since 1.04
       *
       */
      static function post_submitbox_misc_actions() {
        global $post, $wp_properties;

        if( $post->post_type == 'property' ) {

          ?>
          <div class="misc-pub-section ">

        <ul>
          <li><?php _e( 'Menu Sort Order:', 'wpp' ) ?> <?php echo WPP_F::input( "name=menu_order&special=size=4", $post->menu_order ); ?></li>

          <?php if( current_user_can( 'manage_options' ) && $wp_properties[ 'configuration' ][ 'do_not_use' ][ 'featured' ] != 'true' ) { ?>
            <li><?php echo WPP_F::checkbox( "name=wpp_data[meta][featured]&label=" . __( 'Display in featured listings.', 'wpp' ), get_post_meta( $post->ID, 'featured', true ) ); ?></li>
          <?php } ?>

          <?php do_action( 'wpp_publish_box_options' ); ?>
        </ul>

      </div>
        <?php

        }

        return;

      }

      /**
       * Removes "quick edit" link on property type objects
       *
       * Called in via page_row_actions filter
       *
       * @since 0.5
       *
       */
      static function property_row_actions( $actions, $post ) {
        if( $post->post_type != 'property' )
          return $actions;

        unset( $actions[ 'inline' ] );

        return $actions;
      }

      /**
       * Adds property-relevant messages to the property post type object
       *
       *
       * @since 0.5
       *
       */
      static function property_updated_messages( $messages ) {
        global $post_id, $post;

        $messages[ 'property' ] = array(
          0  => '', // Unused. Messages start at index 1.
          1  => sprintf( __( '%2s updated. <a href="%s">view %1s</a>', 'wpp' ), WPP_F::property_label( 'singular' ), esc_url( get_permalink( $post_id ) ), WPP_F::property_label( 'singular' ) ),
          2  => __( 'Custom field updated.', 'wpp' ),
          3  => __( 'Custom field deleted.', 'wpp' ),
          4  => sprintf( __( '%1s updated.', 'wpp' ), WPP_F::property_label( 'singular' ) ),
          /* translators: %s: date and time of the revision */
          5  => isset( $_GET[ 'revision' ] ) ? sprintf( __( '%1s restored to revision from %s', 'wpp' ), WPP_F::property_label( 'singular' ), wp_post_revision_title( (int) $_GET[ 'revision' ], false ) ) : false,
          6  => sprintf( __( '%1s published. <a href="%s">View %2s</a>', 'wpp' ), WPP_F::property_label( 'singular' ), esc_url( get_permalink( $post_id ) ), WPP_F::property_label( 'singular' ) ),
          7  => sprintf( __( '%1s saved.', 'wpp' ), WPP_F::property_label( 'singular' ) ),
          8  => sprintf( __( '%1s submitted. <a target="_blank" href="%s">Preview %2s</a>', 'wpp' ), WPP_F::property_label( 'singular' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ), WPP_F::property_label( 'singular' ) ),
          9  => sprintf( __( '%1s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview %2s</a>', 'wpp' ),
            // translators: Publish box date format, see http://php.net/date
            WPP_F::property_label( 'singular' ),
            date_i18n( __( 'M j, Y @ G:i', 'wpp' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_id ) ), WPP_F::property_label( 'singular' ) ),
          10 => sprintf( __( '%1s draft updated. <a target="_blank" href="%s">Preview %2s</a>', 'wpp' ), WPP_F::property_label( 'singular' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ), WPP_F::property_label( 'singular' ) ),
        );

        $messages = apply_filters( 'wpp_updated_messages', $messages );

        return $messages;
      }

      /**
       * Sets up property-type columns
       *
       * @since 0.54
       * @uses $wp_properties WP-Property configuration array
       * @access public
       *
       */
      static function edit_columns( $columns ) {
        global $wp_properties;

        unset( $columns );

        $columns[ 'cb' ]            = "<input type=\"checkbox\" />";
        $columns[ 'title' ]         = __( 'Title', 'wpp' );
        $columns[ 'property_type' ] = __( 'Type', 'wpp' );

        if( is_array( $wp_properties[ 'property_stats' ] ) ) {
          foreach( $wp_properties[ 'property_stats' ] as $slug => $title )
            $columns[ $slug ] = $title;
        } else {
          $columns = $columns;
        }

        $columns[ 'city' ]       = __( 'City', 'wpp' );
        $columns[ 'overview' ]   = __( 'Overview', 'wpp' );
        $columns[ 'featured' ]   = __( 'Featured', 'wpp' );
        $columns[ 'menu_order' ] = __( 'Order', 'wpp' );
        $columns[ 'thumbnail' ]  = __( 'Thumbnail', 'wpp' );

        $columns = apply_filters( 'wpp_admin_overview_columns', $columns );

        //
        return $columns;
      }

      /**
       * Sets up sortable columns columns
       *
       * @since 1.08
       *
       */
      static function sortable_columns( $columns ) {
        global $wp_properties;

        $columns[ 'type' ]     = 'type';
        $columns[ 'featured' ] = 'featured';

        if( is_array( $wp_properties[ 'property_stats' ] ) ) {
          foreach( $wp_properties[ 'property_stats' ] as $slug => $title )
            $columns[ $slug ] = $slug;
        }

        $columns = apply_filters( 'wpp_admin_sortable_columns', $columns );

        return $columns;
      }

      /**
       * Displays featured properties
       *
       * Performs searching/filtering functions, provides template with $properties file
       * Retirms html content to be displayed after location attribute on property edit page
       *
       * @todo Consider making this function depend on shortcode_property_overview() more so pagination and sorting functions work.
       *
       * @since 0.60
       *
       * @param bool $atts
       *
       * @internal param string $listing_id Listing ID must be passed
       *
       * @return string
       * @uses WPP_F::get_properties()
       */
      static function shortcode_featured_properties( $atts = false ) {
        global $wp_properties, $wpp_query, $post;

        $default_property_type = WPP_F::get_most_common_property_type();

        if( !$atts ) {
          $atts = array();
        }
        $hide_count = '';
        $defaults   = array(
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
          'thumbnail_size'         => 'thumbnail'
        );

        $args = array_merge( $defaults, $atts );

        //** Using "image_type" is obsolete */
        if( $args[ 'thumbnail_size' ] == $defaults[ 'thumbnail_size' ] && !empty( $args[ 'image_type' ] ) ) {
          $args[ 'thumbnail_size' ] = $args[ 'image_type' ];
        }

        //** Using "type" is obsolete. If property_type is not set, but type is, we set property_type from type */
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

          foreach( $args[ 'stats' ] as $key => $stat ) {
            $args[ 'stats' ][ $key ] = trim( $stat );
          }

        }

        $args[ 'disable_wrapper' ] = 'true';
        $args[ 'featured' ]        = 'true';
        $args[ 'template' ]        = 'featured-shortcode';

        unset( $args[ 'image_type' ] );
        unset( $args[ 'type' ] );

        $result = WPP_Core::shortcode_property_overview( $args );

        return $result;
      }

      /**
       * Returns the property search widget
       *
       *
       * @since 1.04
       *
       */
      static function shortcode_property_search( $atts = "" ) {
        global $post, $wp_properties;
        $group_attributes = '';
        $per_page         = '';
        $pagination       = '';
        extract( shortcode_atts( array(
          'searchable_attributes'     => '',
          'searchable_property_types' => '',
          'pagination'                => 'on',
          'group_attributes'          => 'off',
          'per_page'                  => '10'
        ), $atts ) );

        if( empty( $searchable_attributes ) ) {

          //** get first 3 attributes to prevent people from accidentally loading them all (long query) */
          $searchable_attributes = array_slice( $wp_properties[ 'searchable_attributes' ], 0, 5 );

        } else {
          $searchable_attributes = explode( ",", $searchable_attributes );
        }

        $searchable_attributes = array_unique( $searchable_attributes );

        if( empty( $searchable_property_types ) ) {
          $searchable_property_types = $wp_properties[ 'searchable_property_types' ];
        } else {
          $searchable_property_types = explode( ",", $searchable_property_types );
        }

        $widget_id = $post->ID . "_search";

        ob_start();
        echo '<div class="wpp_shortcode_search">';

        $search_args[ 'searchable_attributes' ]     = $searchable_attributes;
        $search_args[ 'searchable_property_types' ] = $searchable_property_types;
        $search_args[ 'group_attributes' ]          = ( $group_attributes == 'on' || $group_attributes == 'true' ? true : false );
        $search_args[ 'per_page' ]                  = $per_page;
        $search_args[ 'pagination' ]                = $pagination;
        $search_args[ 'instance_id' ]               = $widget_id;

        draw_property_search_form( $search_args );

        echo "</div>";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

      }

      /**
       * Displays property overview
       *
       * Performs searching/filtering functions, provides template with $properties file
       * Retirms html content to be displayed after location attribute on property edit page
       *
       * @since 1.081
       *
       * @param string $atts
       *
       * @internal param string $listing_id Listing ID must be passed
       *
       * @return string $result
       *
       * @uses WPP_F::get_properties()
       */
      static function shortcode_property_overview( $atts = "" ) {
        global $wp_properties, $wpp_query, $property, $post, $wp_query;

        $atts = wp_parse_args( $atts, array(
          'strict_search' => 'false'
        ) );

        WPP_F::force_script_inclusion( 'jquery-ui-widget' );
        WPP_F::force_script_inclusion( 'jquery-ui-mouse' );
        WPP_F::force_script_inclusion( 'jquery-ui-slider' );
        WPP_F::force_script_inclusion( 'wpp-jquery-address' );
        WPP_F::force_script_inclusion( 'wpp-jquery-scrollTo' );
        WPP_F::force_script_inclusion( 'wpp-jquery-fancybox' );
        WPP_F::force_script_inclusion( 'wp-property-frontend' );

        //** Load all queriable attributes **/
        foreach( WPP_F::get_queryable_keys() as $key ) {
          //** This needs to be done because a key has to exist in the $deafult array for shortcode_atts() to load passed value */
          $queryable_keys[ $key ] = false;
        }

        //** Allow the shorthand of "type" as long as there is not a custom attribute of "type". If "type" does exist as an attribute, then users need to use the full "property_type" query tag. **/
        if( !array_key_exists( 'type', $queryable_keys ) && ( is_array( $atts ) && array_key_exists( 'type', $atts ) ) ) {
          $atts[ 'property_type' ] = $atts[ 'type' ];
          unset( $atts[ 'type' ] );
        }

        //** Get ALL allowed attributes that may be passed via shortcode (to include property attributes) */
        $defaults[ 'show_children' ]          = ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] : 'true' );
        $defaults[ 'child_properties_title' ] = __( 'Floor plans at location:', 'wpp' );
        $defaults[ 'fancybox_preview' ]       = $wp_properties[ 'configuration' ][ 'property_overview' ][ 'fancybox_preview' ];
        $defaults[ 'bottom_pagination_flag' ] = ( $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] == 'true' ? true : false );
        $defaults[ 'thumbnail_size' ]         = $wp_properties[ 'configuration' ][ 'property_overview' ][ 'thumbnail_size' ];
        $defaults[ 'sort_by_text' ]           = __( 'Sort By:', 'wpp' );
        $defaults[ 'sort_by' ]                = 'post_date';
        $defaults[ 'sort_order' ]             = 'DESC';
        $defaults[ 'template' ]               = false;
        $defaults[ 'ajax_call' ]              = false;
        $defaults[ 'disable_wrapper' ]        = false;
        $defaults[ 'sorter_type' ]            = 'buttons';
        $defaults[ 'pagination' ]             = 'on';
        $defaults[ 'hide_count' ]             = false;
        $defaults[ 'per_page' ]               = 10;
        $defaults[ 'starting_row' ]           = 0;
        $defaults[ 'unique_hash' ]            = rand( 10000, 99900 );
        $defaults[ 'detail_button' ]          = false;
        $defaults[ 'stats' ]                  = '';
        $defaults[ 'class' ]                  = 'wpp_property_overview_shortcode';
        $defaults[ 'in_new_window' ]          = false;

        $defaults = apply_filters( 'shortcode_property_overview_allowed_args', $defaults, $atts );

        //** We add # to value which says that we don't want to use LIKE in SQL query for searching this value. */
        $required_strict_search = apply_filters( 'wpp::required_strict_search', array( 'wpp_agents' ) );
        foreach( $atts as $key => $val ) {
          if( ( ( $atts[ 'strict_search' ] == 'true' && isset( $wp_properties[ 'property_stats' ][ $key ] ) ) ||
              in_array( $key, $required_strict_search ) ) &&
            !key_exists( $key, $defaults ) && $key != 'property_type'
          ) {
            if( substr_count( $val, ',' ) || substr_count( $val, '&ndash;' ) || substr_count( $val, '--' ) ) {
              continue;
            }
            $atts[ $key ] = '#' . $val . '#';
          }
        }

        if( !empty( $atts[ 'ajax_call' ] ) ) {
          //** If AJAX call then the passed args have all the data we need */
          $wpp_query = $atts;

          //* Fix ajax data. Boolean value false is returned as string 'false'. */
          foreach( $wpp_query as $key => $value ) {
            if( $value == 'false' ) {
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
          WPP_F::force_style_inclusion( 'wpp-jquery-fancybox-css' );

          //** Merge defaults with passed arguments */
          $wpp_query            = shortcode_atts( $defaults, $atts );
          $wpp_query[ 'query' ] = shortcode_atts( $queryable_keys, $atts );

          //** Handle search */
          if( $wpp_search = $_REQUEST[ 'wpp_search' ] ) {
            $wpp_query[ 'query' ] = shortcode_atts( $wpp_query[ 'query' ], $wpp_search );
            $wpp_query[ 'query' ] = WPP_F::prepare_search_attributes( $wpp_query[ 'query' ] );

            if( isset( $_REQUEST[ 'wpp_search' ][ 'sort_by' ] ) ) {
              $wpp_query[ 'sort_by' ] = $_REQUEST[ 'wpp_search' ][ 'sort_by' ];
            }

            if( isset( $_REQUEST[ 'wpp_search' ][ 'sort_order' ] ) ) {
              $wpp_query[ 'sort_order' ] = $_REQUEST[ 'wpp_search' ][ 'sort_order' ];
            }

            if( isset( $_REQUEST[ 'wpp_search' ][ 'pagination' ] ) ) {
              $wpp_query[ 'pagination' ] = $_REQUEST[ 'wpp_search' ][ 'pagination' ];
            }

            if( isset( $_REQUEST[ 'wpp_search' ][ 'per_page' ] ) ) {
              $wpp_query[ 'per_page' ] = $_REQUEST[ 'wpp_search' ][ 'per_page' ];
            }
          }

        }

        //** Load certain settings into query for get_properties() to use */
        $wpp_query[ 'query' ][ 'sort_by' ]    = $wpp_query[ 'sort_by' ];
        $wpp_query[ 'query' ][ 'sort_order' ] = $wpp_query[ 'sort_order' ];

        $wpp_query[ 'query' ][ 'pagi' ] = $wpp_query[ 'starting_row' ] . '--' . $wpp_query[ 'per_page' ];

        if( !isset( $wpp_query[ 'current_page' ] ) ) {
          $wpp_query[ 'current_page' ] = ( $wpp_query[ 'starting_row' ] / $wpp_query[ 'per_page' ] ) + 1;
        }

        //** Load settings that are not passed via shortcode atts */
        $wpp_query[ 'sortable_attrs' ] = WPP_F::get_sortable_keys();

        //** Replace dynamic field values */

        //** Detect currently property for conditional in-shortcode usage that will be replaced from values */
        if( isset( $post ) ) {

          $dynamic_fields[ 'post_id' ]       = $post->ID;
          $dynamic_fields[ 'post_parent' ]   = $post->parent_id;
          $dynamic_fields[ 'property_type' ] = $post->property_type;

          $dynamic_fields = apply_filters( 'shortcode_property_overview_dynamic_fields', $dynamic_fields );

          if( is_array( $dynamic_fields ) ) {
            foreach( $wpp_query[ 'query' ] as $query_key => $query_value ) {
              if( !empty( $dynamic_fields[ $query_value ] ) ) {
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
        $wpp_query[ 'properties' ] = WPP_F::get_properties( $wpp_query[ 'query' ], true );

        //** Calculate number of pages */
        if( $wpp_query[ 'pagination' ] == 'on' ) {
          $wpp_query[ 'pages' ] = ceil( $wpp_query[ 'properties' ][ 'total' ] / $wpp_query[ 'per_page' ] );
        }

        //** Set for quick access (for templates */
        $property_type = $wpp_query[ 'query' ][ 'property_type' ];

        if( !empty( $property_type ) ) {
          foreach( (array) $wp_properties[ 'hidden_attributes' ][ $property_type ] as $attr_key ) {
            unset( $wpp_query[ 'sortable_attrs' ][ $attr_key ] );
          }
        }

        //** Legacy Support - include variables so old templates still work */
        $properties             = $wpp_query[ 'properties' ][ 'results' ];
        $thumbnail_sizes        = WPP_F::image_sizes( $wpp_query[ 'thumbnail_size' ] );
        $child_properties_title = $wpp_query[ 'child_properties_title' ];
        $unique                 = $wpp_query[ 'unique_hash' ];
        $thumbnail_size         = $wpp_query[ 'thumbnail_size' ];

        //* Debugger */
        if( $wp_properties[ 'configuration' ][ 'developer_mode' ] == 'true' && !$wpp_query[ 'ajax_call' ] ) {
          echo '<script type="text/javascript">console.log( ' . json_encode( $wpp_query ) . ' ); </script>';
        }

        ob_start();

        //** Make certain variables available to be used within the single listing page */
        $wpp_overview_shortcode_vars = apply_filters( 'wpp_overview_shortcode_vars', array(
          'wp_properties' => $wp_properties,
          'wpp_query'     => $wpp_query
        ) );

        //** By merging our extra variables into $wp_query->query_vars they will be extracted in load_template() */
        if( is_array( $wpp_overview_shortcode_vars ) ) {
          $wp_query->query_vars = array_merge( $wp_query->query_vars, $wpp_overview_shortcode_vars );
        }

        $template         = $wpp_query[ 'template' ];
        $fancybox_preview = $wpp_query[ 'fancybox_preview' ];
        $show_children    = $wpp_query[ 'show_children' ];
        $class            = $wpp_query[ 'class' ];
        $stats            = $wpp_query[ 'stats' ];
        $in_new_window    = ( !empty( $wpp_query[ 'in_new_window' ] ) ? " target=\"_blank\" " : "" );

        //** Make query_vars available to emulate WP template loading */
        extract( $wp_query->query_vars, EXTR_SKIP );

        //** Try find custom template */
        $template_found = WPP_F::get_template_part( array(
          "property-overview-{$template}",
          "property-overview-{$property_type}",
          "property-{$template}",
          "property-overview",
        ), array( WPP_Templates ) );

        if( $template_found ) {
          include $template_found;
        }

        $ob_get_contents = ob_get_contents();
        ob_end_clean();

        $ob_get_contents = apply_filters( 'shortcode_property_overview_content', $ob_get_contents, $wpp_query );

        // Initialize result (content which will be shown) and open wrap (div) with unique id
        if( $wpp_query[ 'disable_wrapper' ] != 'true' ) {
          $result[ 'top' ] = '<div id="wpp_shortcode_' . $defaults[ 'unique_hash' ] . '" class="wpp_ui ' . $wpp_query[ 'class' ] . '">';
        }

        $result[ 'top_pagination' ] = wpp_draw_pagination( array(
          'class'        => 'wpp_top_pagination',
          'sorter_type'  => $wpp_query[ 'sorter_type' ],
          'hide_count'   => $wpp_query[ 'hide_count' ],
          'sort_by_text' => $wpp_query[ 'sort_by_text' ],
        ) );
        $result[ 'result' ]         = $ob_get_contents;

        if( $wpp_query[ 'bottom_pagination_flag' ] == 'true' ) {
          $result[ 'bottom_pagination' ] = wpp_draw_pagination( array(
            'class'        => 'wpp_bottom_pagination',
            'sorter_type'  => $wpp_query[ 'sorter_type' ],
            'hide_count'   => $wpp_query[ 'hide_count' ],
            'sort_by_text' => $wpp_query[ 'sort_by_text' ],
            'javascript'   => false
          ) );
        }

        if( $wpp_query[ 'disable_wrapper' ] != 'true' ) {
          $result[ 'bottom' ] = '</div>';
        }

        $result = apply_filters( 'wpp_property_overview_render', $result );

        if( $wpp_query[ 'ajax_call' ] ) {
          return json_encode( array( 'wpp_query' => $wpp_query, 'display' => implode( '', $result ) ) );
        } else {
          return implode( '', $result );
        }
      }

      /**
       * Retrevie property attribute using shortcode.
       *
       *
       * @since 1.26.0
       *
       */
      static function shortcode_property_attribute( $atts = false ) {
        global $post, $property;

        $this_property = $property;

        if( empty( $this_property ) && $post->post_type == 'property' ) {
          $this_property = $post;
        }

        $this_property = (array) $this_property;

        if( !$atts ) {
          $atts = array();
        }

        $defaults = array(
          'property_id'      => $this_property[ 'ID' ],
          'attribute'        => '',
          'before'           => '',
          'after'            => '',
          'if_empty'         => '',
          'do_not_format'    => '',
          'make_terms_links' => 'false',
          'separator'        => ' ',
          'strip_tags'       => ''
        );

        $args = array_merge( $defaults, $atts );

        if( empty( $args[ 'attribute' ] ) ) {
          return false;
        }

        $attribute = $args[ 'attribute' ];

        if( $args[ 'property_id' ] != $this_property[ 'ID' ] ) {

          $this_property = WPP_F::get_property( $args[ 'property_id' ] );

          if( $args[ 'do_not_format' ] != "true" ) {
            $this_property = prepare_property_for_display( $this_property );
          }

        } else {
          $this_property = $this_property;
        }

        if( is_taxonomy( $attribute ) && is_object_in_taxonomy( 'property', $attribute ) ) {
          foreach( wp_get_object_terms( $this_property[ 'ID' ], $attribute ) as $term_data ) {

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

        //** Try to get value using get get_attribute() function */
        if( !$value && function_exists( 'get_attribute' ) ) {
          $value = get_attribute( $attribute, array(
            'return'          => 'true',
            'property_object' => $this_property
          ) );
        }

        if( !empty( $args[ 'before' ] ) ) {
          $return[ 'before' ] = html_entity_decode( $args[ 'before' ] );
        }

        $return[ 'value' ] = apply_filters( 'wpp_property_attribute_shortcode', $value, $this_property );

        if( $args[ 'strip_tags' ] == "true" && !empty( $return[ 'value' ] ) ) {
          $return[ 'value' ] = strip_tags( $return[ 'value' ] );
        }

        if( !empty( $args[ 'after' ] ) ) {
          $return[ 'after' ] = html_entity_decode( $args[ 'after' ] );
        }

        //** When no value is found */
        if( empty( $return[ 'value' ] ) ) {

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
       *
       * Must be used on a property page, or within a property loop where the global $post or $property variable is for a property object.
       *
       * @since 1.26.0
       *
       */
      static function shortcode_property_map( $atts = false ) {
        global $post, $property;

        if( !$atts ) {
          $atts = array();
        }

        $defaults = array(
          'width'        => '100%',
          'height'       => '450px',
          'zoom_level'   => '13',
          'hide_infobox' => 'false',
          'property_id'  => false
        );

        $args = array_merge( $defaults, $atts );

        //** Try to get property if an ID is passed */
        if( is_numeric( $args[ 'property_id' ] ) ) {
          $property = WPP_F::get_property( $args[ 'property_id' ] );
        }

        //** Load into $property object */
        if( !isset( $property ) ) {
          $property = $post;
        }

        //** Convert to array */
        $property = (array) $property;

        //** Force map to be enabled here */
        $skip_default_google_map_check = true;

        $map_width    = $args[ 'width' ];
        $map_height   = $args[ 'height' ];
        $hide_infobox = ( $args[ 'hide_infobox' ] == 'true' ? true : false );

        //** Find most appropriate template */
        $template_found = WPP_F::get_template_part( array( "content-single-property-map", "property-map" ), array( WPP_Templates ) );
        if( !$template_found ) {
          return false;
        }
        ob_start();
        include $template_found;
        $html = ob_get_contents();
        ob_end_clean();

        $html = apply_filters( 'shortcode_property_map_content', $html, $args );

        return $html;
      }

      /**
       * Return property overview data for AJAX calls
       *
       * @since 0.723
       *
       * @uses WPP_Core::shortcode_property_overview()
       *
       */
      static function ajax_property_overview() {

        $params = $_REQUEST[ 'wpp_ajax_query' ];

        if( !empty( $params[ 'action' ] ) ) {
          unset( $params[ 'action' ] );
        }

        $params[ 'ajax_call' ] = true;

        $data = WPP_Core::shortcode_property_overview( $params );

        die( $data );
      }

      /**
       * Adds wp-property-listing class in search results and property_overview pages
       *
       * @since 0.7260
       */
      static function properties_body_class( $classes ) {
        global $post, $wp_properties;

        if( strpos( $post->post_content, "property_overview" ) || ( is_search() && isset( $_REQUEST[ 'wpp_search' ] ) ) || ( $wp_properties[ 'configuration' ][ 'base_slug' ] == $post->post_name ) ) {
          $classes[ ] = 'wp-property-listing';
        }

        return $classes;
      }

      /**
       * Checks settings data on accord with existing wp_properties data ( before option updates )
       *
       * @param array $wpp_settings New wpp settings data
       * @param array $wp_properties Old wpp settings data
       *
       * @return array $wpp_settings
       */
      static function check_wp_settings_data( $wpp_settings, $wp_properties ) {
        if( is_array( $wpp_settings ) && is_array( $wp_properties ) ) {
          foreach( $wp_properties as $key => $value ) {
            if( !isset( $wpp_settings[ $key ] ) ) {
              switch( $key ) {
                case 'hidden_attributes':
                case 'property_inheritance':
                  $wpp_settings[ $key ] = array();
                  break;
              }
            }
          }
        }

        return $wpp_settings;
      }

      /**
       * Hack to avoid issues with capabilities and views.
       *
       */
      static function current_screen( $screen ) {

        // property_page_all_properties
        // property_page_property_settings
        // property_page_features

        switch( $screen->id ) {
          case "edit-property":
            wp_redirect( 'edit.php?post_type=property&page=all_properties' );
            exit();
            break;
        }

        return $screen;
      }

      /**
       * Adds all WPP custom capabilities to administrator role.
       * Premium feature capabilities are added by filter in this function, see below.
       *
       * @author peshkov@UD
       */
      static function set_capabilities() {
        global $wpp_capabilities;

        //* Get Administrator role for adding custom capabilities */
        $role =& get_role( 'administrator' );

        //* General WPP capabilities */
        $wpp_capabilities = array(

          //* Manage WPP Properties Capabilities */
          'edit_wpp_properties'        => sprintf( __( 'View %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ),
          'edit_wpp_property'          => sprintf( __( 'Add/Edit %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ),
          'edit_others_wpp_properties' => sprintf( __( 'Edit Other %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ),
          //'read_wpp_property' => __( 'Read Property', 'wpp' ),
          'delete_wpp_property'        => sprintf( __( 'Delete %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ),
          'publish_wpp_properties'     => sprintf( __( 'Publish %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ),
          //'read_private_wpp_properties' => __( 'Read Private Properties', 'wpp' ),

          //* WPP Settings capability */
          'manage_wpp_settings'        => __( 'Manage Settings', 'wpp' ),
          'manage_wpp_modules'         => __( 'Manage Features', 'wpp' ),

          //* WPP Taxonomies capability */
          'manage_wpp_categories'      => __( 'Manage Taxonomies', 'wpp' )
        );

        //* Adds Premium Feature Capabilities */
        $wpp_capabilities = apply_filters( 'wpp_capabilities', $wpp_capabilities );

        if( !is_object( $role ) ) {
          return;
        }

        foreach( $wpp_capabilities as $cap => $value ) {
          if( empty( $role->capabilities[ $cap ] ) ) {
            $role->add_cap( $cap );
          }
        }
      }

      /**
       * Generates javascript file with localization.
       * Adds localization support to all WP-Property scripts.
       * Accessible via wp-ajax.php calls.
       *
       * @since 1.37.3.2
       * @author peshkov@UD
       */
      static function localize_scripts() {

        $l10n = array();

        //** Include the list of translations */
        include_once WPP_Path . 'l10n.php';

        /** All additional localizations must be added using the filter below. */
        $l10n = apply_filters( 'wpp::js::localization', $l10n );

        foreach( (array) $l10n as $key => $value ) {
          if( !is_scalar( $value ) ) {
            continue;
          }
          $l10n[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
        }

        header( 'Content-type: application/x-javascript' );

        die( "var wpp = ( typeof wpp === 'object' ) ? wpp : {}; wpp.strings = " . json_encode( $l10n ) . ';' );

      }

      /**
       * WPP Contextual Help
       *
       * @global $current_screen
       *
       * @param  $args
       *
       * @author korotkov@ud
       */
      static function wpp_contextual_help( $args = array() ) {
        global $contextual_help;

        $defaults = array(
          'contextual_help' => array()
        );

        extract( wp_parse_args( $args, $defaults ) );

        //** If method exists add_help_tab in WP_Screen */
        if( is_callable( array( 'WP_Screen', 'add_help_tab' ) ) ) {

          //** Loop through help items and build tabs */
          foreach( (array) $contextual_help as $help_tab_title => $help ) {

            //** Add tab with current info */
            get_current_screen()->add_help_tab(
              array(
                'id'      => sanitize_title( $help_tab_title ),
                'title'   => __( $help_tab_title, 'wpp' ),
                'content' => implode( "\n", (array) $contextual_help[ $help_tab_title ] ),
              )
            );

          }

          //** Add help sidebar with More Links */
          get_current_screen()->set_help_sidebar(
            '<p><strong>' . __( 'For more information:', 'wpp' ) . '</strong></p>' .
            '<p>' . __( '<a href="https://usabilitydynamics.com/products/wp-property/" target="_blank">WP-Property Product Page</a>', 'wpp' ) . '</p>' .
            '<p>' . __( '<a href="https://usabilitydynamics.com/products/wp-property/forum/" target="_blank">WP-Property Forums</a>', 'wpp' ) . '</p>' .
            '<p>' . __( '<a href="https://usabilitydynamics.com/help/" target="_blank">WP-Property Tutorials</a>', 'wpp' ) . '</p>'
          );

        } else {
          global $current_screen;
          add_contextual_help( $current_screen->id, '<p>' . __( 'Please upgrade Wordpress to the latest version for detailed help.', 'wpp' ) . '</p><p>' . __( 'Or visit <a href="https://usabilitydynamics.com/tutorials/wp-property-help/" target="_blank">WP-Property Help Page</a> on UsabilityDynamics.com', 'wpp' ) . '</p>' );
        }
      }

      /**
       * Returns specific instance data which is used by javascript
       * Javascript Reference: window.wpp.instance
       *
       * @author peshkov@UD
       * @since 1.38
       * @return array
       */
      function get_instance() {
        global $wp_properties;

        $data = array(
          'request'        => $_REQUEST,
          'get'            => $_GET,
          'post'           => $_POST,
          'iframe_enabled' => false,
          'ajax_url'       => admin_url( 'admin-ajax.php' ),
          'home_url'       => home_url(),
          'user_logged_in' => is_user_logged_in() ? 'true' : 'false',
          'settings'       => $wp_properties,
        );

        if( isset( $data[ 'request' ][ 'wp_customize' ] ) && $data[ 'request' ][ 'wp_customize' ] == 'on' ) {
          $data[ 'iframe_enabled' ] = true;
        }

        return apply_filters( 'wpp::get_instance', $data );
      }

    }

  }

}


