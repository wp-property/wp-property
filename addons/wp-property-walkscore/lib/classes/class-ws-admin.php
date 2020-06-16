<?php
/**
 * Admin
 *
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\WS_Admin' ) ) {

    /**
     *
     *
     * @author peshkov@UD
     */
    class WS_Admin {

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
        $this->ui = new WS_UI_Settings( ud_get_wpp_walkscore()->settings, ud_get_wpp_walkscore()->get_schema( 'extra.schemas.ui', true ) );

        add_action( 'ud:ui:settings:view:section:score_api:bottom', array( $this, 'custom_ui' ) );

        /**
         * Add Walk Score Meta Box on Edit Property page
         */
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
      }

      /**
       * Register admin Scripts and Styles.
       *
       */
      public function admin_enqueue_scripts() {
        $screen = get_current_screen();

        switch( $screen->id ) {

          case 'property_page_walkscore':

            wp_enqueue_script( 'property-walkscore-settings', ud_get_wpp_walkscore()->path( 'static/scripts/admin/property-walkscore-settings.js', 'url' ), array( 'jquery' ), ud_get_wpp_walkscore( 'version' ) );
            wp_localize_script( 'property-walkscore-settings', '_walkscore_settings', array(
              'admin_ajax' => admin_url('admin-ajax.php'),
              'got_ids' => sprintf( __( 'List of %s which do not have Walk Score is got. Start getting Walk Scores...', ud_get_wpp_walkscore('domain') ), \WPP_F::property_label( 'plural' ) ),
              'error_occurred' => __( 'Sorry, something went wrong! Please reload page and try again.', ud_get_wpp_walkscore('domain') ),
              'done' => __( 'Done.', ud_get_wpp_walkscore('domain') ),
            ) );

            wp_enqueue_style( 'property-walkscore-settings', ud_get_wpp_walkscore()->path( 'static/styles/admin/property-walkscore-settings.css', 'url' ), array(), ud_get_wpp_walkscore( 'version' ) );

            break;

          case 'property':

            wp_enqueue_style( 'property-walkscore-edit', ud_get_wpp_walkscore()->path( 'static/styles/admin/edit-property.css', 'url' ), array(), ud_get_wpp_walkscore( 'version' ) );

            break;

        }

      }

      /**
       *
       */
      public function custom_ui() {
        $screen = get_current_screen();

        if ($screen->id !== 'property_page_walkscore') {
          return false;
        }

        $hook = current_filter();

        switch ($hook) {

          case 'ud:ui:settings:view:section:score_api:bottom':
            include( ud_get_wpp_walkscore()->path( 'static/views/admin/bulk_score_request.php', 'dir' ) );
            break;

        }

      }

      /**
       * Add Walk Score Meta Box on Edit Property page
       *
       */
      public function add_meta_boxes() {
        add_meta_box( 'wpp_ws_walkscore', __( 'Walk Score', ud_get_wpp_walkscore('domain') ), array( $this, 'render_walkscore_metabox' ), 'property', 'side', 'core' );
      }

      /**
       * Render Walk Score Meta Box on Edit Property page
       *
       */
      public function render_walkscore_metabox( $post ) {

        $walkscore = get_post_meta( $post->ID, '_ws_walkscore', true );
        $walkscore_data = get_post_meta( $post->ID, '_ws_walkscore_response', true );

        include( ud_get_wpp_walkscore()->path( 'static/views/admin/metabox_walkscore.php', 'dir' ) );
      }

    }

  }

}
