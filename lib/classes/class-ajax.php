<?php
/**
 * Wordpress Ajax Handler
 *
 * @since 1.0.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Ajax' ) ) {

    final class Ajax extends Scaffold {

      /**
       * The list of wp_ajax_{name} actions
       *
       * @var array
       */
      var $actions = array(
        'wpp_autocomplete_property',
      );

      /**
       * The list of wp_ajax_nopriv_{name} actions
       *
       * @var array
       */
      var $nopriv = array();

      /**
       * Init AJAX actions
       *
       * @author peshkov@UD
       */
      public function __construct(){
        parent::__construct();

        /**
         * Maybe extend the list by external modules.
         */
        $this->actions = apply_filters( 'wpp::ajax_actions', $this->actions );
        $this->nopriv = apply_filters( 'wpp::ajax_nopriv', $this->nopriv );

        foreach( $this->actions as $action ) {
          add_action( 'wp_ajax_' . $action, array( $this, 'request' ) );
        }

        foreach( $this->nopriv as $action ) {
          add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'request' ) );
        }

      }

      /**
       * Handles AJAX request
       *
       * @author peshkov@UD
       */
      public function request() {

        $response = array(
          'message' => '',
          'html' => '',
        );

        try{

          $action = $_REQUEST[ 'action' ];
          /** Determine if the current class has the method to handle request */
          if( is_callable( array( $this, 'action_'. $action ) ) ) {
            $response = call_user_func_array( array( $this, 'action_' . $action ), array( $_REQUEST ) );
          }
          /** Determine if external function exists to handle request */
          elseif ( is_callable( 'action_' . $action ) ) {
            $response = call_user_func_array( $action, array( $_REQUEST ) );
          }
          elseif ( is_callable( $action ) ) {
            $response = call_user_func_array( $action, array( $_REQUEST ) );
          }
          /** Oops! */
          else {
            throw new \Exception( __( 'Incorrect Request' ) );
          }

        } catch( \Exception $e ) {
          wp_send_json_error( $e->getMessage() );
        }

        wp_send_json_success( $response );

      }

      /**
       * Sends json.
       * Use it if custom response should be sent.
       *
       * @param array $response
       * @author peshkov@UD
       */
      public function send_json( $response ) {
        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
          wp_die();
        } else {
          die;
        }
      }

      /**
       * Returns search results of found 'Properties' for Autocomplete
       *
       * @author peshkov@UD
       */
      public function action_wpp_autocomplete_property( $args ) {
        $args = wp_parse_args( $args, array(
          'term' => '',
        ) );

        $response = $this->get_autocomplete_post( $args[ 'term' ], 'property' );
        $this->send_json( $response );
      }

      /**
       *
       */
      private function get_autocomplete_post( $s, $type ) {
        $response = array();

        $posts = get_posts( array(
          's' => $s,
          'post_type' => $type,
          'numberposts' => 20,

        ) );

        if( !empty( $posts ) ) {
          foreach( $posts as $post ) {
            array_push( $response, array(
              'value' => $post->ID,
              'label' => $post->post_title . ' ( ID #' . $post->ID . ' )'
            ) );
          }

        }

        return $response;

      }

    }

  }

}
