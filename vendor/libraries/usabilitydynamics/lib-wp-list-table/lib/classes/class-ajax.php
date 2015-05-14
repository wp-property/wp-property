<?php
/**
 * WP List Table Ajax Handler
 *
 * @since 1.0.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPLT {

  if( !class_exists( 'UsabilityDynamics\WPLT\Ajax' ) ) {

    final class Ajax {

      /**
       * The list of wp_ajax_{name} actions
       *
       * @var array
       */
      var $actions = array(
        'wplt_list_table',
      );

      /**
       * Init AJAX actions
       *
       * @author peshkov@UD
       */
      public function __construct(){

        /**
         * Maybe extend the list of available actions.
         */
        $this->actions = apply_filters( 'wplt_ajax_actions', $this->actions );

        foreach( $this->actions as $action ) {
          add_action( 'wp_ajax_' . $action, array( $this, 'request' ) );
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
       * Use it, if custom response should be sent.
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
       *  Default ajax request logic for List Table
       */
      public function action_wplt_list_table( $args ) {

        $class = !empty( $args[ 'class' ] ) ? urldecode($args[ 'class' ]) : '\UsabilityDynamics\WPLT\WP_List_Table';

        if( empty( $class ) || !is_string( $class ) || !class_exists( $class ) ) {
          throw new \Exception( __( 'Required WP List Table class does not exist' ) );
        }

        $list_table = new $class( $args );
        $response = $list_table->ajax_response();
        if( !$response ) {
          throw new \Exception( __( 'Error occurred on trying to get data.' ) );
        }
        return $response;

      }

    }

  }

}
