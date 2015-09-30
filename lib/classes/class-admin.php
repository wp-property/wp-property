<?php
/**
 * Admin Panel UI functionality
 *
 * @since 2.0.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if (!class_exists('UsabilityDynamics\WPP\Admin')) {

    class Admin extends Scaffold {

      /**
       * Adds all required hooks
       */
      public function __construct() {

        parent::__construct();

        /**
         * Init 'All Properties' page.
         */
        new Admin_Overview();

        //** Load admin header scripts */
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        /** Admin interface init */
        add_action( "admin_init", array( $this, "admin_init" ) );

        // @todo Move back to Settings -> Properties as it was years ago.
        add_action( "admin_menu", array( $this, 'admin_menu' ), 150 );
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
        global $current_screen;

        wp_localize_script( 'wpp-localization', 'wpp', array( 'instance' => $this->instance->core->get_instance() ) );

        switch( $current_screen->id ) {

          //** Edit Property page */
          case 'property':
            global $post;

            $post_type_object = get_post_type_object('property');
            if( current_user_can( $post_type_object->cap->create_posts ) ) {
              wp_enqueue_script( 'wpp-clone-property', $this->instance->path( 'static/scripts/wpp.admin.clone.js', 'url' ), array( 'jquery', 'wp-property-global' ), $this->instance->get('version'), true );
            }

            wp_enqueue_script( 'wp-property-global' );
            wp_enqueue_script( 'wp-property-backend-editor' );
            //** Enabldes fancybox js, css and loads overview scripts */
            wp_enqueue_script( 'post' );
            wp_enqueue_script( 'postbox' );
            wp_enqueue_script( 'wpp-jquery-fancybox' );
            wp_enqueue_style( 'wpp-jquery-fancybox-css' );
            wp_enqueue_script( 'wp-property-backend-global' );
            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_style( 'jquery-ui' );
            wp_enqueue_script( 'wp-property-admin-widgets' );
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

        // Add metaboxes
        do_action( 'wpp_metaboxes' );

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
       *
       */
      public function admin_menu() {

        $settings_page = add_submenu_page( 'edit.php?post_type=property', __( 'Settings', ud_get_wp_property()->domain ), __( 'Settings', ud_get_wp_property()->domain ), 'manage_wpp_settings', 'property_settings', function () {
          global $wp_properties;
          include ud_get_wp_property()->path( "static/views/admin/settings.php", 'dir' );
        } );

      }

    }

  }

}