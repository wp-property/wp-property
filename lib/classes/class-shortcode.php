<?php
/**
 * Shortcode
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\Shortcode')) {

    class Shortcode extends \UsabilityDynamics\Shortcode\Shortcode {

      /**
       * Determines template and renders it
       *
       *
       */
      public function get_template( $template, $data, $output = true ) {
        $name = apply_filters( $this->id . '_template_name', array( $template ), $this );
        /* Set possible pathes where templates could be stored. */
        $path = apply_filters( $this->id . '_template_path', array(
            ud_get_wp_property()->path( 'static/views', 'dir' ),
        ) );

        $path = \UsabilityDynamics\Utility::get_template_part( $name, $path, array(
            'load' => false
        ) );

        if( $output ) {
          extract( $data );
          include $path;
        } else {
          ob_start();
          extract( $data );
          include $path;
          return ob_get_clean();
        }
      }

    }

  }
}