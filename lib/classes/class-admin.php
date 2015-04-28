<?php
/**
 * Admin Panel UI functionality
 *
 * @since 2.0.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\Admin')) {

    class Admin extends Scaffold {

      /**
       * Adds all required hooks
       */
      public function __construct() {

        parent::__construct();

        //** Load admin header scripts */
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        /** Admin interface init */
        add_action( "admin_init", array( $this, "admin_init" ) );
        add_action( "admin_menu", array( $this, 'admin_menu' ), 20 );
        add_action( "admin_menu", array( $this, 'admin_menu_settings' ), 50 );

        /** Plug page actions -> Add Settings Link to plugin overview page */
        add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
      }

      /**
       * Can enqueue scripts on specific pages, and print content into head
       *
       *
       * @uses $current_screen global variable
       * @since 0.53
       *
       */
      public function enqueue_scripts() {
        global $current_screen, $wp_properties;

        wp_localize_script( 'wpp-localization', 'wpp', array( 'instance' => $this->instance->core->get_instance() ) );

        switch( $current_screen->id ) {

          //** Property Overview Page and Edit Property page */
          case 'property_page_all_properties':
            wp_enqueue_script( 'wp-property-backend-global' );
            wp_enqueue_script( 'wp-property-admin-overview' );

          case 'property':

            /** Add 'Clone Property' button if user has permissions to create property. */
            if( $current_screen->id == 'property' ) {
              global $post;

              $post_type_object = get_post_type_object('property');
              if( current_user_can( $post_type_object->cap->create_posts ) ) {
                wp_enqueue_script( 'wpp-clone-property', $this->instance->path( 'static/scripts/wpp.admin.clone.js', 'url' ), array( 'jquery', 'wp-property-global' ), $this->instance->get('version'), true );
              }
            }


            wp_enqueue_script( 'wp-property-global' );
            //** Enabldes fancybox js, css and loads overview scripts */
            wp_enqueue_script( 'post' );
            wp_enqueue_script( 'postbox' );
            wp_enqueue_script( 'wpp-jquery-fancybox' );
            wp_enqueue_script( 'wpp-jquery-data-tables' );
            wp_enqueue_style( 'wpp-jquery-fancybox-css' );
            wp_enqueue_style( 'wpp-jquery-data-tables' );
            //** Get width of overview table thumbnail, and set css */
            $thumbnail_attribs = \WPP_F::image_sizes( $wp_properties[ 'configuration' ][ 'admin_ui' ][ 'overview_table_thumbnail_size' ] );
            $thumbnail_width = ( !empty( $thumbnail_attribs[ 'width' ] ) ? $thumbnail_attribs[ 'width' ] : false );
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
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_script( 'jquery-cookie' );
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
        if( file_exists( $this->instance->path( "static/styles/{$current_screen->id}.css", 'dir' ) ) ) {
          wp_enqueue_style( $current_screen->id . '-style', $this->instance->path( "static/styles/{$current_screen->id}.css", 'url' ), array(), WPP_Version, 'screen' );
        }

        //** Automatically insert JS sheet if one exists with $current_screen->ID name */
        if( file_exists( $this->instance->path( "static/scripts/{$current_screen->id}.js", 'dir' ) ) ) {
          wp_enqueue_script( $current_screen->id . '-js', $this->instance->path( "static/scripts/{$current_screen->id}.js", 'url' ), array( 'jquery' ), WPP_Version, 'wp-property-backend-global' );
        }

        //** Enqueue CSS styles on all pages */
        if( file_exists( $this->instance->path( 'static/styles/wpp.admin.css', 'dir' ) ) ) {
          wp_register_style( 'wpp-admin-styles', $this->instance->path( 'static/styles/wpp.admin.css', 'url' ), array(), WPP_Version );
          wp_enqueue_style( 'wpp-admin-styles' );
        }

      }

      /**
       * Runs pre-header functions on admin-side only
       *
       * Checks if plugin has been updated.
       *
       * @since 1.10
       *
       */
      public function admin_init() {
        global $wp_properties;

        \WPP_F::fix_screen_options();

        //* Adds metabox 'General Information' to Property Edit Page */

        /*
        add_meta_box( 'wpp_property_meta', __( 'General Information', 'wpp' ), array( 'WPP_UI', 'metabox_meta' ), 'property', 'normal', 'high' );
        // Adds 'Group' metaboxes to Property Edit Page
        if ( !empty( $wp_properties[ 'property_groups' ] ) ) {
          foreach ( $wp_properties[ 'property_groups' ] as $slug => $group ) {
            // There is no sense to add metabox if no one attribute assigned to group
            if ( !in_array( $slug, $wp_properties[ 'property_stats_groups' ] ) ) {
              continue;
            }
            // Determine if Group name is empty we add 'NO NAME', other way metabox will not be added
            if ( empty( $group[ 'name' ] ) ) {
              $group[ 'name' ] = __( 'NO NAME', 'wpp' );
            }
            add_meta_box( $slug, __( $group[ 'name' ], 'wpp' ), array( 'WPP_UI', 'metabox_meta' ), 'property', 'normal', 'high', array( 'group' => $slug ) );
          }
        }
        //*/

        add_meta_box( 'property_filter', $wp_properties[ 'labels' ][ 'name' ] . ' ' . __( 'Search', 'wpp' ), array( 'WPP_UI', 'metabox_property_filter' ), 'property_page_all_properties', 'normal' );

        // Add metaboxes
        do_action( 'wpp_metaboxes' );

        \WPP_F::manual_activation();

        //** Download backup of configuration */
        if(
          isset( $_REQUEST[ 'page' ] )
          && $_REQUEST[ 'page' ] == 'property_settings'
          && isset( $_REQUEST[ 'wpp_action' ] )
          && $_REQUEST[ 'wpp_action' ] == 'download-wpp-backup'
          && isset( $_REQUEST[ '_wpnonce' ] )
          && wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'download-wpp-backup' )
        ) {
          $sitename = sanitize_key( get_bloginfo( 'name' ) );
          $filename = $sitename . '-wp-property.' . date( 'Y-m-d' ) . '.json';

          header( "Cache-Control: public" );
          header( "Content-Description: File Transfer" );
          header( "Content-Disposition: attachment; filename=$filename" );
          header( "Content-Transfer-Encoding: binary" );
          header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ), true );

          echo json_encode( $wp_properties, JSON_PRETTY_PRINT );

          die();
        }
      }

      /**
       * Sets up additional pages and loads their scripts
       *
       * @since 0.5
       *
       */
      function admin_menu() {
        global $wp_properties, $submenu;

        // Create property settings page
        add_submenu_page( 'edit.php?post_type=property', $wp_properties[ 'labels' ][ 'all_items' ], $wp_properties[ 'labels' ][ 'all_items' ], 'edit_wpp_properties', 'all_properties', function () {
          global $wp_properties, $screen_layout_columns;
          include $this->instance->path( "lib/ui/page_all_properties.php", 'dir' );
        } );

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

      }

      /**
       *
       */
      public function admin_menu_settings() {

        $settings_page = add_submenu_page( 'edit.php?post_type=property', __( 'Settings', 'wpp' ), __( 'Settings', 'wpp' ), 'manage_wpp_settings', 'property_settings', function () {
          global $wp_properties;
          include $this->instance->path( "lib/ui/page_settings.php", 'dir' );
        } );

      }

      /**
       * Adds "Settings" link to the plugin overview page
       *
       *  *
       * @since 0.60
       *
       */
      public function plugin_action_links( $links, $file ) {

        if( $file == 'wp-property/wp-property.php' ) {
          $settings_link = '<a href="' . admin_url( "edit.php?post_type=property&page=property_settings" ) . '">' . __( 'Settings', ud_get_wp_property('domain') ) . '</a>';
          array_unshift( $links, $settings_link ); // before other links
        }
        return $links;
      }

    }

  }

}