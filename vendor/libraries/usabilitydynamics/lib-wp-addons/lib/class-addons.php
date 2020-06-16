<?php
/**
 * Add-ons Admin
 *
 * @namespace UsabilityDynamics
 *
 */

namespace UsabilityDynamics\WPA {

  use UsabilityDynamics\UD_API\UI;
  use UsabilityDynamics\UD_API\Admin;

  if( !class_exists( 'UsabilityDynamics\WPA\Addons' ) ) {

    /**
     *
     * @author: peshkov@UD
     */
    class Addons extends Admin {

      /**
       *
       */
      public static $version = '1.0.0';

      /**
       * Don't ever change this, as it will mess with the data stored of which products are activated, etc.
       *
       */
      private $token;

      /**
       *
       */
      public $error;

      /**
       *
       */
      public $ui;

      /**
       *
       */
      private $addons = array();

      /**
       *
       */
      private $addons_homepage;

      /**
       *
       */
      private $bootstrap;

      /**
       *
       */
      public function __construct( $bootstrap ) {
        $this->bootstrap = $bootstrap;

        $schema = $bootstrap->get_schema( 'extra.schemas.licenses.client' );

        $this->args = $schema;
        $this->slug = $schema[ 'slug' ];

        //** Don't ever change this, as it will mess with the data stored of which products are activated, etc. */
        $this->token = 'udl_' . $this->slug;

        //** UI */
        $this->ui = new UI( array( 'token' => $this->token, 'screens' => array()) );

        $path = wp_normalize_path( dirname( __DIR__ ) );
        $this->screens_path = trailingslashit( $path . '/static/templates' );

        $this->addons = $this->bootstrap->get_schema( 'extra.schemas.addons' );
        $this->init_addons();

        $this->addons_homepage = $this->bootstrap->get_schema( 'extra.schemas.addons_homepage' );

        //** Add Add-ons page */
        add_action( 'admin_menu', array( $this, 'register_addons_screen' ), 999 );

        /**
         * add admin notices
         */
        add_filter( 'ud:warnings:admin_notices', array( $this, 'skip_licenses_notices' ), 99, 2);
      }


      /**
       * Skipping notices for licenses
       * @param $warnings
       * @param $args
       * @return array
       */
      public function skip_licenses_notices ( $warnings, $args ) {
        if ( is_array($warnings) && !empty($warnings) ) {
          foreach( $warnings as $k=>$warning ) {
            if ( strpos( $warning, 'License is not active' )) {
              unset($warnings[$k]);
            }
          }
        }
        return $warnings;
      }


      /**
       * Init active addons
       */
      public function init_addons() {
        $active_addons = get_option( $this->bootstrap->domain . '_active_addons', array() );
        if( !empty( $active_addons ) ) {
          foreach( $this->addons as $addon ) {
            if( in_array( $addon[ 'slug' ], $active_addons ) && file_exists( $this->bootstrap->root_path . '/' . $addon[ 'path' ] ) ) {
              require_once( $this->bootstrap->root_path . '/' . $addon[ 'path' ] );
            }
          }
        }
      }

      /**
       * Register the admin screen.
       *
       * @access public
       * @since   1.0.0
       * @return   void
       */
      public function register_addons_screen() {
        $args = $this->args;
        $screen = !empty( $args[ 'screen' ] ) ? $args[ 'screen' ] : false;
        $this->icon_url = !empty( $screen[ 'icon_url' ] ) ? $screen[ 'icon_url' ] : '';
        $this->position = !empty( $screen[ 'position' ] ) ? $screen[ 'position' ] : 66;
        $this->menu_title = !empty( $screen[ 'menu_title' ] ) ? $screen[ 'menu_title' ] : __( 'Add-ons', $this->domain );
        $this->page_title = !empty( $screen[ 'page_title' ] ) ? $screen[ 'page_title' ] : __( 'Add-ons Manager', $this->domain );
        $this->menu_slug = $this->slug . '_' . sanitize_key( $screen[ 'menu_title' ] );

        global $submenu;
        /**
         * removing old page with add-ons
         */
        remove_submenu_page( $screen[ 'parent' ], $this->slug . '_' . sanitize_key( $screen[ 'page_title' ] ) );

        /**
         * adding new page with add-ons
         */
        $this->hook = add_submenu_page( $screen[ 'parent' ], $this->page_title, $this->menu_title, 'manage_options', $this->menu_slug, array( $this, 'settings_screen_addon' ) );

        add_action( 'load-' . $this->hook, array( $this, 'process_request' ) );
        add_action( 'admin_print_styles-' . $this->hook, array( $this, 'enqueue_styles' ) );
        add_action( 'admin_print_scripts-' . $this->hook, array( $this, 'enqueue_scripts' ) );
      }

      /**
       * Load the main management screen.
       *
       * @access public
       * @since   1.0.0
       * @return   void
       */
      public function settings_screen_addon() {
        $this->ui->get_header();
        require_once( $this->screens_path . 'screen-manage-addons.php' );
        $this->ui->get_footer();
      }

      /**
       * Process the action for the admin screen.
       * @since  1.0.0
       * @return  void
       */
      public function process_request() {

        add_action( 'admin_notices', array( $this, 'admin_notices' ) );

        $supported_actions = array( 'activate-addons' );
        if( !isset( $_REQUEST[ 'action' ] ) || !in_array( $_REQUEST[ 'action' ], $supported_actions ) ) {
          return null;
        }

        $response = false;
        $status = 'false';
        $type = $_REQUEST[ 'action' ];

        switch( $type ) {
          case 'activate-addons':
            $products = array();
            if( isset( $_POST[ 'products' ] ) && 0 < count( $_POST[ 'products' ] ) ) {
              foreach( $_POST[ 'products' ] as $k => $v ) {
                if( $v == '1' ) {
                  $products[] = $k;
                }
              }
            }
            update_option( $this->bootstrap->domain . '_active_addons', $products );

            $response = true;
            break;

          default:
            break;
        }

        if( $response == true ) {
          $status = 'true';
        }

        $redirect_url = \UsabilityDynamics\Utility::current_url( array( 'type' => urlencode( $type ), 'status' => urlencode( $status ) ), array( 'action', 'filepath', '_wpnonce' ) );
        wp_safe_redirect( $redirect_url );
        exit;
      }

      /**
       * Admin notices
       */
      public function admin_notices() {

        if( isset( $_GET[ 'status' ] ) && in_array( $_GET[ 'status' ], array( 'true', 'false' ) ) && isset( $_GET[ 'type' ] ) ) {
          $classes = array( 'true' => 'updated', 'false' => 'error' );
          switch( $_GET[ 'type' ] ) {

            default:
              if( 'true' == $_GET[ 'status' ] ) {
                $message = __( 'Add-ons saved successfully.', $this->domain );
              } else {
                $message = __( 'There was an error and not all add-ons were activated.', $this->domain );
              }
              break;
          }

          $response = '<div class="' . esc_attr( $classes[ $_GET[ 'status' ] ] ) . ' fade">' . "\n";
          $response .= wpautop( $message );
          $response .= '</div>' . "\n";

          if( '' != $response ) {
            echo $response;
          }
        }

      }

      /**
       * Enqueue admin scripts.
       *
       * @access  public
       * @since   1.0.0
       * @return  void
       */
      public function enqueue_scripts() {
        wp_enqueue_script( 'post' );
      }

      /**
       * Get detected addons.
       *
       * @access public
       * @since   1.0.0
       * @return   void
       */
      protected function get_detected_addons() {

        if( is_array( $this->addons ) && ( 0 < count( $this->addons ) ) ) {
          $active_addons = get_option( $this->bootstrap->domain . '_active_addons', array() );
          $plugins = $this->get_detected_plugins();
          if( !empty( $plugins ) ) {
            foreach( $plugins as $plugin ) {
              $products[ $plugin[ 'product_id' ] ][ 'status' ] = is_plugin_active( $plugin[ 'product_file_path' ] );
              $products[ $plugin[ 'product_id' ] ][ 'file_path' ] = $plugin[ 'product_file_path' ];
            }
          }
          foreach( $this->addons as $k => $addon ) {
            if( in_array( $addon[ 'slug' ], $active_addons ) ) {
              $this->addons[ $k ][ 'active' ] = 1;
            }
            if( isset( $products[ $addon[ 'product_id' ] ] ) && $products[ $addon[ 'product_id' ] ][ 'status' ] == true ) {
              $this->addons[ $k ][ 'plugin_active' ] = 1;
              $this->addons[ $k ][ 'plugin_deactivate_link' ] = $this->get_deactivation_link( $products[ $addon[ 'product_id' ] ][ 'file_path' ] );
            }
          }
        }

        return $this->addons;
      }

      /**
       * Get plugin deactivation link
       * @param $plugin
       * @return string
       */
      function get_deactivation_link( $plugin ) {
        if( strpos( $plugin, '/' ) ) {
          $plugin = str_replace( '\/', '%2F', $plugin );
        }
        $url = sprintf( admin_url( 'plugins.php?action=deactivate&plugin=%s&plugin_status=all&paged=1&s' ), $plugin );
        $_REQUEST[ 'plugin' ] = $plugin;
        $url = wp_nonce_url( $url, 'deactivate-plugin_' . $plugin );
        return $url;
      }

    }

  }

}
