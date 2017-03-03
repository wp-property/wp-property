<?php

/**
 *
 */
namespace UsabilityDynamics\WPRETSC {

  if( !class_exists( '\UsabilityDynamics\WPRETSC\Widget' ) ) {

    /**
     * Class Widget
     * @package UsabilityDynamics\WPRETSC
     */
    class Widget extends \UsabilityDynamics\WPRETSC\Dashboard_Widget {

      /**
       * @var string
       */
      public $widget_id = 'wpp_rets_client_widget';

      /**
       * @var string
       */
      public $widget_title = 'rets.ci';

      /**
       * Widget constructor.
       */
      public function __construct() {
        parent::__construct(array());
      }

      /**
       * @param \UsabilityDynamics\WPP\type $args
       * @param \UsabilityDynamics\WPP\type $instance
       * @return string
       */
      public function widget($args, $instance) {

        if( defined( 'UD_RETSCI_AJAX_API_URL' ) ) {
          $api_url = trailingslashit(UD_RETSCI_AJAX_API_URL);
        } else {
          $api_url = 'https://api.rets.ci/';
        }

        wp_enqueue_script( 'wpp_retsci_app', ud_get_wp_rets_client()->path( 'static/scripts/app.js', 'url' ), array( 'jquery' ) );

        wp_localize_script( 'wpp_retsci_app', 'wpp_retsci_client', array(
          'timezone' => get_option( 'timezone_string' ),
          'time_format' => get_option( 'time_format' ),
        ) );

        /**
         * Show stats
         */
        $data = json_encode(array(
          'site_id' => get_site_option( 'ud_site_id' ),
          'site_secret_token' => get_site_option( 'ud_site_secret_token' ),
          'api_url' => $api_url
        ));

        ob_start();
        include_once ud_get_wp_rets_client()->path( 'static/views/widget-stats.php', 'dir' );
        echo apply_filters( 'wpp_retsci_widget_stats_content', ob_get_clean() );

      }

    }
  }
}