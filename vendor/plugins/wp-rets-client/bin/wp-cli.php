<?php
/**
 * The file can be used separately of plugin.
 * Just copy it to mu-plugins folder ( `wp-content/mu-plugins/` ).
 *
 * Adds WP-CLI commands:
 *
 * # Removes all unassigned retcsi attachments
 * wp retsci cleanup
 *
 * ######################################################################
 *
 * Note, if there are a LOT of attachments, it, probably, would be better
 * to remove them directly using the following SQL Queries:
 *
 * # At first we MUST delete post meta:
 * DELETE FROM wp_postmeta WHERE post_id IN ( SELECT ID FROM wp_posts WHERE post_type="attachment" AND post_parent = 0 AND guid LIKE "%cdn.rets.ci%" );
 *
 * # Then we should delete attachments posts:
 * DELETE FROM wp_posts WHERE post_type="attachment" AND post_parent = 0 AND guid LIKE "%cdn.rets.ci%"
 *
 * ######################################################################
 *
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

if ( !defined('WP_CLI') || !WP_CLI ) {
  return;
}

if( !class_exists( 'WPP_CLI_RETSCI_Command' ) ) {

  /**
   * CLI Commands for RETSCI
   *
   */
  class WPP_CLI_RETSCI_Command extends WP_CLI_Command {

    /**
     * Cleans up properties and its data:
     * - updates terms counts
     *
     *
     * Example: wp retsci cleanup
     *
     * @synopsis [--taxonomy]
     * @param array $args
     * @param array $assoc_args
     */
    public function cleanup( $args, $assoc_args ) {

      timer_start();

      add_action( 'wrc::_update_terms_counts_helper::done', array( $this, '_update_terms_counts_action' ), 10, 3 );

      $taxonomy = !empty( $assoc_args[ 'taxonomy' ] ) ? $assoc_args[ 'taxonomy' ] : null;

      // Update property terms counts
      $result = ud_get_wp_rets_client()->update_terms_counts( $taxonomy );

      if( is_wp_error( $result ) ) {
        WP_CLI::error( $result->get_error_message() );
      }

      WP_CLI::log( WP_CLI::colorize( '%Y' . __( 'Total time elapsed: ' ) . '%N' . timer_stop() ) );
      WP_CLI::success( __( 'Done!' ) );

    }

    /**
     * Cleanup:
     * - removes all unassigned retsci attachments
     *
     * Example: wp retsci delete_attachments --posts-per-page=100
     *
     * @synopsis [--posts-per-page]
     * @param array $args
     * @param array $assoc_args
     */
    public function delete_attachments( $args, $assoc_args ) {

      timer_start();

      $this->_delete_attachments( $assoc_args );

      WP_CLI::log( WP_CLI::colorize( '%Y' . __( 'Total time elapsed: ' ) . '%N' . timer_stop() ) );
      WP_CLI::success( __( 'Done!' ) );

    }

    /**
     * @param $terms
     * @param $query
     * @param $error
     */
    public function _update_terms_counts_action( $terms, $query, $error ) {
      if( is_wp_error( $error ) ) {
        WP_CLI::log( $error->get_error_message() );
      } else {
        WP_CLI::log( sprintf( __( 'Updated [%s] terms counts for [%s] taxonomy' ), count( $terms ), $query[ 'taxonomy' ] ) );
      }
    }

    /**
     *
     */
    public function _posts_where( $where, &$wp_query ) {
      global $wpdb;
      $where .= ' AND ' . $wpdb->posts . '.guid LIKE \'%cdn.rets.ci%\'';
      return $where;
    }

    /**
     *
     * @param $assoc_args
     */
    protected function _delete_attachments( $assoc_args ) {

      if ( ! empty( $assoc_args['posts-per-page'] ) ) {
        $assoc_args['posts-per-page'] = absint( $assoc_args['posts-per-page'] );
      } else {
        $assoc_args['posts-per-page'] = 10;
      }

      $query_args = array(
        'posts_per_page'         => $assoc_args['posts-per-page'],
        'post_type'              => 'attachment',
        'post_status'            => 'inherit',
        'offset'                 => 0,
        'ignore_sticky_posts'    => true,
        'orderby'                => 'ID',
        'order'                  => 'DESC',
        'post_parent'            => 0
      );

      timer_start();

      WP_CLI::log( __( 'Removing un-assigned retsci attachments...', ud_get_wp_property()->domain ) );

      $result = $this->_delete_helper( $query_args );

      WP_CLI::log( sprintf( __( 'Number of attachments removed from site %d: %d', ud_get_wp_property()->domain ), get_current_blog_id(), $result['removed'] ) );

      if ( ! empty( $result['errors'] ) ) {
        WP_CLI::error( sprintf( __( 'Number of errors on site %d: %d', ud_get_wp_property()->domain ), get_current_blog_id(), count( $result['errors'] ) ) );
      }

      WP_CLI::log( WP_CLI::colorize( '%Y' . __( 'Total time elapsed: ', ud_get_wp_property()->domain ) . '%N' . timer_stop() ) );

      WP_CLI::success( __( 'Done!', ud_get_wp_property()->domain ) );
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
      $offset = 0;

      $query_args =  array_merge( array(
        'posts_per_page'         => 10,
        'post_status'            => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
        'offset'                 => 0,
        'ignore_sticky_posts'    => true,
        'orderby'                => 'ID',
        'order'                  => 'DESC',
      ), $args );

      if( empty( $query_args[ 'post_type' ] ) ) {
        WP_CLI::error( 'post_type is not defined.' );
        return;
      }

      add_filter( 'posts_where', array( $this, '_posts_where' ), 10, 2 );

      /**
       * Create WP_Query here and reuse it in the loop to avoid high memory consumption.
       */
      $query = new WP_Query();

      while ( true ) {

        $query->query( $query_args );

        if ( $query->have_posts() ) {

          while ( $query->have_posts() ) {
            $query->the_post();

            $post = get_post();

            if( strpos($post->guid, 'cdn.rets.ci') === false ) {
              WP_CLI::log( sprintf( 'Invalid attachment [%s] was scrolled. GUID: [%s]', $post->ID, $post->guid ) );
            }

            if( wp_delete_post( $post->ID, true ) ) {
              $removed ++;
              WP_CLI::log( current_time( 'mysql' ) . ' Removed Post [' . $post->ID . ']' );
            } else {
              $errors[] = $post->ID;
              WP_CLI::log( current_time( 'mysql' ) . ' ERROR on removing Post [' . $post->ID . ']' );
            }

          }
        } else {
          break;
        }

        WP_CLI::log( 'Processed: ' . ( $query->post_count + $offset ) . '. Left: ' . $query->found_posts . ' entries. . .' );

        $offset += $query_args['posts_per_page'];

        usleep( 500 );

        // Avoid running out of memory
        $this->_stop_the_insanity();

      }

      remove_filter( 'posts_where', array( $this, '_posts_where' ), 10, 2 );

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

  WP_CLI::add_command( 'retsci', 'WPP_CLI_RETSCI_Command' );

}
