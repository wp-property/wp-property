<?php
/**
 * WP_Query Scroller
 *
 *
 * @TODO: move to separate vendor library
 */
namespace UsabilityDynamics {

  use WP_Query;

  if( !class_exists( 'UsabilityDynamics\WP_Query_Scroller' ) ) {

    final class WP_Query_Scroller {

      /**
       *
       */
      public function __construct() {}

      /**
       * @param $query_args
       * @param $instance
       * @param $callback
       * @param $bulk
       * @return WP_Error | bool
       */
      public function scroll( $query_args, $instance = 'post', $callback, $bulk = false ) {
        if( !$callback || !is_callable( $callback ) ) {
          return new \WP_Error( __( 'Callback is not defined or not callable' ) );
        }
        switch( $instance ) {
          case 'term':
            return $this->_scroll_term_data( $query_args, $callback, $bulk );
          case 'post':
            return $this->_scroll_post_data( $query_args, $callback, $bulk );
        }
        return new \WP_Error( sprintf( __( 'Instance [%s] is no supported' ), $instance ) );
      }

      /**
       * Scroll Helper method for scrolling terms data
       *
       * @param array $query_args
       * @return bool
       */
      private function _scroll_term_data( $query_args, $callback, $bulk = false ) {
        $offset = 0;

        $query_args =  array_merge( array(
          'number'                 => 10,
          // NOTE: If hierarchical is TRUE, limit ( number ) will be ignored!
          'hierarchical'           => false,
          'hide_empty'             => false,
          'offset'                 => 0
        ), $query_args );

        $query = new \WP_Term_Query();

        while ( true ) {

          $terms = $query->query( $query_args );

          if ( !empty( $terms ) ) {

            if( $bulk ) {
              call_user_func_array( $callback, array( $terms, $query_args ) );
            } else {
              foreach( $terms as $term ) {
                call_user_func_array( $callback, array( $term, $query_args ) );
              }
            }

          } else {
            break;
          }

          $query_args['offset'] += $query_args['number'];

          usleep( 500 );

          // Avoid running out of memory
          $this->_stop_the_insanity();

        }

        return true;
      }

      /**
       * Scroll Helper method for scrolling posts data
       *
       * @param array $query_args
       * @return bool
       */
      private function _scroll_post_data( $query_args, $callback, $bulk ) {
        $offset = 0;

        $query_args =  array_merge( array(
          'posts_per_page'         => 100,
          'offset'                 => 0
        ), $query_args );

        $query = new \WP_Query();

        while ( true ) {

          $query->query( $query_args );

          if ( $query->have_posts() ) {

            while ( $query->have_posts() ) {
              $query->the_post();
              $post = get_post();

              call_user_func_array( $callback, array( $post, $query_args ) );

            }
          } else {
            break;
          }

          $query_args['offset'] += $query_args['posts_per_page'];

          usleep( 500 );

          // Avoid running out of memory
          $this->_stop_the_insanity();

        }

        wp_reset_postdata();

        return true;
      }

      /**
       * Resets some values to reduce memory footprint.
       */
      private function _stop_the_insanity() {
        global $wpdb, $wp_object_cache, $wp_actions, $wp_filter;

        $wpdb->queries = array();

        if ( is_object( $wp_object_cache ) ) {
          $wp_object_cache->group_ops = array();
          $wp_object_cache->stats = array();
          $wp_object_cache->memcache_debug = array();

          // Make sure this is a public property, before trying to clear it
          try {
            $cache_property = new \ReflectionProperty( $wp_object_cache, 'cache' );
            if ( $cache_property->isPublic() ) {
              $wp_object_cache->cache = array();
            }
            unset( $cache_property );
          } catch ( \ReflectionException $e ) {
          }

          /*
           * In the case where we're not using an external object cache, we need to call flush on the default
           * WordPress object cache class to clear the values from the cache property
           */
          if ( ! wp_using_ext_object_cache() ) {
            wp_cache_flush();
          }

          if ( is_callable( $wp_object_cache, '__remoteset' ) ) {
            call_user_func( array( $wp_object_cache, '__remoteset' ) ); // important
          }
        }

        // WP_Query class adds filter get_term_metadata using its own instance
        // what prevents WP_Query class from being destructed by PHP gc.
        //    if ( $q['update_post_term_cache'] ) {
        //        add_filter( 'get_term_metadata', array( $this, 'lazyload_term_meta' ), 10, 2 );
        //    }
        // It's high memory consuming as WP_Query instance holds all query results inside itself
        // and in theory $wp_filter will not stop growing until Out Of Memory exception occurs.
        if ( isset( $wp_filter['get_term_metadata'] ) ) {
          /*
           * WordPress 4.7 has a new Hook infrastructure, so we need to make sure
           * we're accessing the global array properly
           */
          if ( class_exists( 'WP_Hook' ) && $wp_filter['get_term_metadata'] instanceof WP_Hook ) {
            $filter_callbacks   = &$wp_filter['get_term_metadata']->callbacks;
          } else {
            $filter_callbacks   = &$wp_filter['get_term_metadata'];
          }
          if ( isset( $filter_callbacks[10] ) ) {
            foreach ( $filter_callbacks[10] as $hook => $content ) {
              if ( preg_match( '#^[0-9a-f]{32}lazyload_term_meta$#', $hook ) ) {
                unset( $filter_callbacks[10][ $hook ] );
              }
            }
          }
        }
      }

    }

  }

}
