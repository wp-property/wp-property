<?php
/**
 * UI Settings
 *
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\WS_UI_Settings' ) ) {

    /**
     *
     *
     * @author peshkov@UD
     */
    class WS_UI_Settings extends \UsabilityDynamics\UI\Settings {

      /**
       * Constructor
       *
       */
      public function __construct( $settings = null, $schema = null ) {

        parent::__construct( $settings, $schema );

        /** Fix menu order */
        if( has_action( 'admin_menu', array( $this, 'admin_menu' ) ) ) {
          remove_action( 'admin_menu', array( $this, 'admin_menu' ), 100 );
          add_action( 'admin_menu', array( $this, 'admin_menu' ), 1 );
        }

      }

    }

  }

}
