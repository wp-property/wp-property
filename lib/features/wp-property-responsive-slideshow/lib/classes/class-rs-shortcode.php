<?php
/**
 * Shortcode
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\RS_Shortcode')) {

    class RS_Shortcode extends \UsabilityDynamics\Shortcode\Shortcode {

      /**
       * Determines template and renders it
       *
       *
       */
      public function get_template( $template, $data, $output = true ) {
        $name = apply_filters( $this->id . '_template_name', array( $template ), $this );
        /* Set possible pathes where templates could be stored. */
        $path = apply_filters( $this->id . '_template_path', array(
          ud_get_wpp_resp_slideshow()->path( 'static/views', 'dir' ),
        ) );

        $path = \UsabilityDynamics\Utility::get_template_part( $name, $path, array(
          'load' => false
        ) );

        if($path){
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
}