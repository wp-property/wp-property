<?php
/**
 * Admin
 *
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPRETSC {

  if( !class_exists( 'UsabilityDynamics\WPRETSC\Admin' ) ) {

    /**
     *
     *
     * @author peshkov@UD
     */
    class Admin {

      /**
       *
       * @var object UsabilityDynamics\UI\Settings
       */
      public $ui;

      /**
       * Constructor
       *
       * @author peshkov@UD
       */
      public function __construct() {

        /* Handle Scripts and Styles */
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        /**
         * Setup Admin Settings Interface
         */
        $this->ui = new \UsabilityDynamics\UI\Settings( ud_get_wp_rets_client()->settings, ud_get_wp_rets_client()->get_schema( 'extra.ui', true ) );

      }

      /**
       * Register admin Scripts and Styles.
       *
       */
      public function admin_enqueue_scripts() {
        $screen = get_current_screen();

        switch( $screen->id ) {

          case 'settings_page_wp_rets_client':
            wp_enqueue_style( 'wpretsc-settings', ud_get_wp_rets_client()->path( 'static/styles/admin/settings.css', 'url' ), array(), ud_get_wp_rets_client( 'version' ) );
            break;

        }

      }

    }

  }

}
