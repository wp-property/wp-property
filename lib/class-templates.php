<?php
/**
 * WP-Property default templates actions and filters
 * Specific Themes compatibility hooks also should be added here.
 *
 * @version 0.1
 * @since 2.0
 * @author team@ud
 * @package WP-Property
 * @subpackage Templates
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Templates' ) ) {

    class Templates {

      /**
       * Hooks Setter
       *
       * @action after_setup_theme (10)
       * @author peshkov@UD
       */
      static function initialize() {

        //** Property template hook */
        add_action( 'wpp::tmpl::property::bottom', array( __CLASS__, 'wpp_tmpl_property_bottom' ) );

      }

      /**
       * Property template hook
       *
       * @see templates/property.php
       * @author peshkov@UD
       */
      static function wpp_tmpl_property_bottom() {
        if( get_template() == 'suffusion' ) {
        }
      }

    }
  }
}
