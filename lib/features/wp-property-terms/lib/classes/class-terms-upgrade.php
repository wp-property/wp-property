<?php
/**
 * WP-Property Upgrade Handler
 *
 * @since 2.1.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Terms_Upgrade' ) ) {

    class Terms_Upgrade {

      /**
       * Run Upgrade Process
       *
       * @param $old_version
       * @param $new_version
       */
      static public function run( $old_version, $new_version ){
        global $wp_properties;

        /**
         * Specific upgrade conditions.
         */
        switch( true ) {

          case ( version_compare( $old_version, '1.0.2', '<' ) ):
            $taxonomies = ud_get_wpp_terms()->define_taxonomies( array() );
            //$settings = ud_get_wpp_terms()->get( 'config.taxonomies', array() );
            if( is_array( $taxonomies ) ) {
              foreach ($taxonomies as $taxonomy => &$args) {
                if(isset($args['show_ui'])){
                  $args['show_in_menu'] = '';
                  $args['add_native_mtbox'] = '';
                  if($args['show_ui'] == "true"){
                    $args['show_in_menu'] = 'true';
                    $args['add_native_mtbox'] = 'true';
                  }
                  unset($args['show_ui']);
                }
              }
              ud_get_wpp_terms()->set( 'config.taxonomies', $taxonomies );
              ud_get_wpp_terms()->settings->commit();
            }
          break;

        }
        /* Additional stuff can be handled here */
        do_action( ud_get_wpp_terms()->slug . '::upgrade', $old_version, $new_version );
      }

    }

  }

}
