<?php
/**
 * WP-Property default templates actions and filters
 * Specific Themes compatibility hooks also should be added here.
 *
 * @version 0.1
 * @since 2.0
 * @author team@ud
 * @package WP-Property
 * @subpackage Template
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Template' ) ) {

    class Template {

      /**
       * Find and Load Template
       *
       * @author potanin@UD
       * @since 2.0.0
       *
       * @param       $name
       * @param array $args
       *
       * @internal param bool $once
       *
       * @return mixed
       */
      static function load( $name, $args = array() ) {

        $args = (object) wp_parse_args( $args, array(
          'once'   => false,
          'prefix' => '',
        ) );

        // Add prefix if not already there.
        if( strpos( $name, $args->prefix ) !== 0 ) {
          $name = $args->prefix . $name;
        }

        if( $_path = self::locate( $name ) ) {

          if( $args->once ) {
            return include_once( $_path );
          }

          return include( $_path );

        }

        return false;

      }

      /**
       * Locate template by checking known locations
       *
       * @author potanin@UD
       * @since 2.0.0
       *
       * @param bool $name
       *
       * @return bool
       */
      static function locate( $name = false ) {

        if( !$name || !defined( 'WPP_Path' ) || !WPP_Path ) {
          return false;
        }

        $_paths = apply_filters( 'wpp:template_paths', array(
          trailingslashit( get_template_directory() ) . 'wpp',
          trailingslashit( get_template_directory() ),
          trailingslashit( get_stylesheet_directory() ) . 'wpp',
          trailingslashit( get_stylesheet_directory() ),
          WPP_Path . 'templates'
        ));

        foreach( (array) $_paths as $path ) {

          if( file_exists( trailingslashit( $path ) . $name ) ) {
            return trailingslashit( $path ) . $name;
          }

        }

        return false;

      }

      /**
       * Hooks Setter
       *
       * @action after_setup_theme (10)
       * @author peshkov@UD
       */
      static function initialize() {

        // Property template hook
        add_action( 'wpp::tmpl::property::bottom', array( __CLASS__, 'wpp_tmpl_property_bottom' ) );

      }

      /**
       * Property template hook
       *
       * @see templates/property.php
       * @author peshkov@UD
       */
      static function wpp_tmpl_property_bottom() {

        if( get_template() == 'suffusion' ) {}

      }

    }

  }

}
