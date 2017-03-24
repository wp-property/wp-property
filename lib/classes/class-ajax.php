<?php
/**
 * Wordpress Ajax Handler
 *
 * @since 1.0.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;
  use MatthiasMullie\Minify\Exception;

  if( !class_exists( 'UsabilityDynamics\WPP\Ajax' ) ) {

    final class Ajax extends Scaffold {

      /**
       * The list of wp_ajax_{name} actions
       *
       * @var array
       */
      var $actions = array(
        'wpp_clone_property',
        'wpp_autocomplete_property_parent',
        'wpp_ajax_property_query',
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
      public function __construct() {
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

        try {

          $action = $_REQUEST[ 'action' ];
          /** Determine if the current class has the method to handle request */
          if( is_callable( array( $this, 'action_' . $action ) ) ) {
            $response = call_user_func_array( array( $this, 'action_' . $action ), array( $_REQUEST ) );
          } /** Determine if external function exists to handle request */
          elseif( is_callable( 'action_' . $action ) ) {
            $response = call_user_func_array( $action, array( $_REQUEST ) );
          } elseif( is_callable( $action ) ) {
            $response = call_user_func_array( $action, array( $_REQUEST ) );
          } /** Oops! */
          else {
            throw new \Exception( __( 'Incorrect Request' ) );
          }

        } catch ( \Exception $e ) {
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
        if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
          wp_die();
        } else {
          die;
        }
      }

      /**
       * Creates new property based on existing one (clones).
       *
       * @param $args
       * @return array
       * @throws \Exception
       */
      public function action_wpp_clone_property( $args ) {

        if( empty( $args[ 'post_id' ] ) || !is_numeric( $args[ 'post_id' ] ) ) {
          throw new \Exception( __( 'Invalid Post ID', ud_get_wp_property( 'domain' ) ) );
        }

        if( get_post_type( $args[ 'post_id' ] ) !== 'property' ) {
          throw new \Exception( sprintf( __( 'Invalid Post ID. It does not belong to %s.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ) );
        }

        $post = get_post( $args[ 'post_id' ], ARRAY_A );

        /* Clone Property */

        $postmap = array(
          'post_title',
          'post_content',
          'post_excerpt',
          'ping_status',
          'comment_status',
          'post_type',
          'post_status',
          'comment_status',
          'post_parent',
        );

        $_post = array();
        foreach( $post as $k => $v ) {
          if( in_array( $k, $postmap ) ) {
            switch( $k ) {
              case 'post_title':
                $v .= ' (' . __( 'Clone', ud_get_wp_property( 'domain' ) ) . ')';
              default:
                $_post[ $k ] = $v;
                break;
            }
          }
        }

        $post_id = wp_insert_post( $_post );

        /* Clone Property Attributes and Meta */

        if( is_wp_error( $post_id ) || !$post_id ) {
          throw new \Exception( __( 'Could not create new Post.', ud_get_wp_property( 'domain' ) ) );
        }

        $meta = array_unique( array_merge(
          array( 'property_type' ),
          array_keys( ud_get_wp_property( 'property_stats', array() ) ),
          array_keys( ud_get_wp_property( 'property_meta', array() ) )
        ) );

        foreach( $meta as $key ) {
          $v = get_post_meta( $post[ 'ID' ], $key, true );
          add_post_meta( $post_id, $key, $v );
        }

        /* Probably add custom actions ( e.g. by Add-on ) */
        do_action( 'wpp::clone_property::action', $post, $post_id );

        return array(
          'post_id' => $post_id,
          'redirect' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
        );
      }

      /**
       * Returns search results of found 'Properties' for Autocomplete
       *
       * @author peshkov@UD
       */
      public function action_wpp_autocomplete_property_parent( $args ) {
        $args = wp_parse_args( $args, array(
          'term' => '',
        ) );

        $response = $this->get_autocomplete_post( $args[ 'term' ], 'property' );
        foreach( $response as $i => $p ) {
          $response[ $i ][ 'excerpt' ] = \RWMB_Wpp_Parent_Field::prepare_parent_label( $p[ 'excerpt' ] );
        }
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
          'post_status' => apply_filters( 'wpp::autocomplete_post::status', 'any', $s, $type ),
        ) );

        if( !empty( $posts ) ) {
          foreach( $posts as $post ) {
            array_push( $response, array(
              'value' => $post->ID,
              'label' => $post->post_title . ' ( ID #' . $post->ID . ' )',
              'excerpt' => $post->ID,
            ) );
          }

        }

        return $response;

      }

      /**
       * Property Object previewer
       *
       * @author potanin@UD
       * @param array $args
       * @return array
       */
      public function action_wpp_ajax_property_query($args = array( )) {

        $_property_id = trim( $args[ "property_id" ] );

        $class = WPP_F::get_property( $_property_id, array( 'load_gallery' => false, 'cache' => false ) );

        $_display = prepare_property_for_display( $class );

        return array(
          'property' => $class,
          'for_display' => $_display
        );

      }

    }

  }

}
