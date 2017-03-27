<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}
WP_CLI::add_command( 'property', 'WPP_CLI_Command' );

/**
 * CLI Commands for WP-Property
 *
 */
class WPP_CLI_Command extends WP_CLI_Command {

  /**
   * Delete all 'property' posts for a site
   *
   * @synopsis [--posts-per-page]
   * @param array $args
   * @param array $assoc_args
   */
  public function delete( $args, $assoc_args ) {

    if ( ! empty( $assoc_args['posts-per-page'] ) ) {
      $assoc_args['posts-per-page'] = absint( $assoc_args['posts-per-page'] );
    } else {
      $assoc_args['posts-per-page'] = 10;
    }

    timer_start();

    WP_CLI::log( __( 'Removing properties...', 'wpp' ) );

    $result = $this->_delete_helper( $assoc_args );

    WP_CLI::log( sprintf( __( 'Number of properties removed from site %d: %d', 'wpp' ), get_current_blog_id(), $result['removed'] ) );

    if ( ! empty( $result['errors'] ) ) {
      WP_CLI::error( sprintf( __( 'Number of errors on site %d: %d', 'wpp' ), get_current_blog_id(), count( $result['errors'] ) ) );
    }

    WP_CLI::log( WP_CLI::colorize( '%Y' . __( 'Total time elapsed: ', 'wpp' ) . '%N' . timer_stop() ) );

    WP_CLI::success( __( 'Done!', 'wpp' ) );
  }

  /**
   * Helper method for removing property posts
   *
   * @param array $args
   * @return array
   */
  private function _delete_helper( $args ) {
    $removed = 0;
    $errors = array();

    $posts_per_page = 10;
    if ( ! empty( $args['posts-per-page'] ) ) {
      $posts_per_page = absint( $args['posts-per-page'] );
    }

    $offset = 0;
    if ( ! empty( $args['offset'] ) ) {
      $offset = absint( $args['offset'] );
    }

    /**
     * Create WP_Query here and reuse it in the loop to avoid high memory consumption.
     */
    $query = new WP_Query();

    while ( true ) {

      $args = apply_filters( 'wpp::cli::delete::args', array(
        'posts_per_page'         => $posts_per_page,
        'post_type'              => 'property',
        'post_status'            => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
        'offset'                 => $offset,
        'ignore_sticky_posts'    => true,
        'orderby'                => 'ID',
        'order'                  => 'DESC',
      ) );

      $query->query( $args );

      if ( $query->have_posts() ) {

        while ( $query->have_posts() ) {
          $query->the_post();

          $post_id = get_the_ID();

          if( wp_delete_post( $post_id, true ) ) {
            $removed ++;
            WP_CLI::log( current_time( 'mysql' ) . ' Removed Property [' . $post_id . ']' );
          } else {
            $errors[] = get_the_ID();
          }

        }
      } else {
        break;
      }

      WP_CLI::log( 'Processed ' . ( $query->post_count + $offset ) . '/' . $query->found_posts . ' entries. . .' );

      $offset += $posts_per_page;

      usleep( 500 );

      // Avoid running out of memory
      $this->_stop_the_insanity();

    }

    wp_reset_postdata();

    return array( 'removed' => $removed, 'errors' => $errors );
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
        $cache_property = new ReflectionProperty( $wp_object_cache, 'cache' );
        if ( $cache_property->isPublic() ) {
          $wp_object_cache->cache = array();
        }
        unset( $cache_property );
      } catch ( ReflectionException $e ) {
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
