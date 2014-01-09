<?php
/**
 * WP-Property Mail Notifications
 * Contains set of static methods for notifications
 *
 * @version 1.0
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @since 1.38
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Meta_Box' ) ) {

    if( !defined( 'RWMB_URL' ) ) {
      define( 'RWMB_URL', WPP_URL . 'vendor/usabilitydynamics/meta-box/' );
      define( 'RWMB_DIR', WPP_Path. 'vendor/usabilitydynamics/meta-box/' );
      define( 'RWMB_VER', '4.3.4' );
      define( 'RWMB_JS_URL', trailingslashit( RWMB_URL . 'js' ) );
      define( 'RWMB_CSS_URL', trailingslashit( RWMB_URL . 'css' ) );
      define( 'RWMB_INC_DIR', trailingslashit( RWMB_DIR . 'inc' ) );
      define( 'RWMB_FIELDS_DIR', trailingslashit( RWMB_INC_DIR . 'fields' ) );
    }

    /**
     * Class Meta_Box
     *
     * @package UsabilityDynamics\WPP
     */
    class Meta_Box extends \RW_Meta_Box {

      /**
       * Instantiate New Meta Box
       *
       * ## Options
       * - size
       * - class
       * - multiple
       * - clone
       * - std
       * - desc
       * - format
       * - before
       * - after
       * - afterfield_name
       * - required
       * - placeholder
       * - context
       * - priority
       * - pages
       * - autosave
       * - default_hidden
       *
       * @param array $args
       */
      function __construct( $args ) {

        $args = Utility::parse_args( $args, array(
          'context'  => 'normal',
          'priority' => 'normal',
          'pages'    => array( 'property' ),
          'autosave' => true,
          //'default_hidden' => false,
          //'size'          => 30,
          //'class'         => 'my-class',
          //'multiple'      => false,
          //'clone'         => false,
          //'std'           => '',
          //'desc'          => '',
          //'format'        => '',
          //'before'        => 'before',
          //'after'         => 'after',
          //'field_name'    => isset( $field['id'] ) ? $field['id'] : '',
          //'required'      => false,
          //'placeholder'   => '',
        ));

        parent::__construct( (array) $args );

      }

    }

  }

}



