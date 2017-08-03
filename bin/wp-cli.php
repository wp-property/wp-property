<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}
WP_CLI::add_command( 'property', 'WPP_CLI_Property_Command' );
WP_CLI::add_command( 'wpp-term', 'WPP_CLI_Terms_Command' );

/**
 * CLI Commands for WP-Property posts
 *
 */
class WPP_CLI_Property_Command extends WP_CLI_Command {

  /**
   * May trigger custom actions.
   * Just set the action you want to execute
   *
   * e.g., the following command:
   *
   * `wp property trigger --do-action=upgrade_property_types`
   *
   * will trigger do_action( 'wpp::cli::trigger::upgrade_property_types' );
   *
   * @synopsis [--posts-per-page] [--do-action]
   * @param array $args
   * @param array $assoc_args
   */
  public function trigger( $args, $assoc_args ) {

    if( empty( $assoc_args['do-action'] ) ) {
      WP_CLI::error( __( '--do-action argument must be provided' ) );
    }

    timer_start();

    $action = 'wpp::cli::trigger::' . $assoc_args['do-action'];

    WP_CLI::log( sprintf( __( 'Triggering [%s] action...', ud_get_wp_property()->domain ), $action ) );

    do_action( $action, $assoc_args );

    WP_CLI::log( WP_CLI::colorize( '%Y' . __( 'Total time elapsed: ', ud_get_wp_property()->domain ) . '%N' . timer_stop() ) );

    WP_CLI::success( __( 'Done!', ud_get_wp_property()->domain ) );

  }

  /**
   * Scrolls all 'property' posts for a site
   * and call action 'wpp::cli::scroll' which can be used for different purposes
   * e.g. to update/fix specific post's data
   *
   * @synopsis [--posts-per-page] [--do-action]
   * @param array $args
   * @param array $assoc_args
   */
  public function scroll( $args, $assoc_args ) {

    if ( ! empty( $assoc_args['posts-per-page'] ) ) {
      $assoc_args['posts-per-page'] = absint( $assoc_args['posts-per-page'] );
    } else {
      $assoc_args['posts-per-page'] = 10;
    }

    $assoc_args['do-action'] = !empty( $assoc_args['do-action'] ) ? $assoc_args['do-action'] : '';

    timer_start();

    WP_CLI::log( __( 'Scrolling properties...', ud_get_wp_property()->domain ) );

    $this->_scroll_helper( $assoc_args );

    WP_CLI::log( WP_CLI::colorize( '%Y' . __( 'Total time elapsed: ', ud_get_wp_property()->domain ) . '%N' . timer_stop() ) );

    WP_CLI::success( __( 'Done!', ud_get_wp_property()->domain ) );

  }

  /**
   * Delete all 'property' posts for a site
   *
   * @synopsis [--posts-per-page] [--force-delete-meta]
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

    // May be in some cases we could delete meta at first
    // So it might decrease operation time.
    if ( ! empty( $assoc_args['force-delete-meta'] ) ) {
      WP_CLI::log( __( 'Removing properties meta at first...', ud_get_wp_property()->domain ) );
      $this->_delete_properties_meta();
    }

    WP_CLI::log( __( 'Removing properties...', ud_get_wp_property()->domain ) );

    $result = $this->_delete_helper( $assoc_args );

    WP_CLI::log( sprintf( __( 'Number of properties removed from site %d: %d', ud_get_wp_property()->domain ), get_current_blog_id(), $result['removed'] ) );

    if ( ! empty( $result['errors'] ) ) {
      WP_CLI::error( sprintf( __( 'Number of errors on site %d: %d', ud_get_wp_property()->domain ), get_current_blog_id(), count( $result['errors'] ) ) );
    }

    WP_CLI::log( WP_CLI::colorize( '%Y' . __( 'Total time elapsed: ', ud_get_wp_property()->domain ) . '%N' . timer_stop() ) );

    WP_CLI::success( __( 'Done!', ud_get_wp_property()->domain ) );
  }

  /**
   * Removes directly from DataBase all meta related to property post type.
   */
  function _delete_properties_meta() {
    global $wpdb;

    $total = 0;

    while( true ) {

      $meta_ids = $wpdb->get_col( "
        SELECT wpm.meta_id
	        FROM $wpdb->postmeta wpm
	        INNER JOIN $wpdb->posts wp ON (wp.ID = wpm.post_id)
		      WHERE wp.post_type = 'property'
		        LIMIT 0, 1000;
		  " );

      // If there are no results just break the loop
      if( empty( $meta_ids ) ) {
        break;
      }

      $total += count( $meta_ids );

      $meta_ids = implode( ",", $meta_ids );

      $wpdb->query( "
        DELETE
          FROM $wpdb->postmeta
          WHERE meta_id IN ( $meta_ids )

      " );

    }

    WP_CLI::log( sprintf( __( 'Removed [%d] meta fields which belong to property type', ud_get_wp_property()->domain ), $total ) );

  }

  /**
   * Helper method for scrolling property posts
   *
   * @param array $args
   * @return array
   */
  private function _scroll_helper( $args ) {

    $posts_per_page = 10;
    if ( ! empty( $args['posts-per-page'] ) ) {
      $posts_per_page = absint( $args['posts-per-page'] );
    }

    $do_action = '';
    if ( ! empty( $args['do-action'] ) ) {
      $do_action = $args['do-action'];
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

      $args = apply_filters( 'wpp::cli::scroll::args', array(
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

          $action = 'wpp::cli::scroll';
          if( !empty( $do_action ) ) {
            $action .= "::" . $do_action;
          }

          do_action( $action, get_the_ID(), $args );

        }
      } else {
        break;
      }

      WP_CLI::log( 'Processed: ' . ( $query->post_count + $offset ) . '. Total: ' . $query->found_posts . ' entries. . .' );

      $offset += $posts_per_page;

      usleep( 500 );

      // Avoid running out of memory
      $this->_stop_the_insanity();

    }

    wp_reset_postdata();

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
        'offset'                 => 0,
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

      WP_CLI::log( 'Processed: ' . ( $query->post_count + $offset ) . '. Left: ' . $query->found_posts . ' entries. . .' );

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

/**
 * CLI Commands for WP-Property Terms (Taxonomies)
 *
 */
class WPP_CLI_Terms_Command extends WP_CLI_Command {

  /**
   * Removes broken terms and not registered taxonomies.
   *
   * @synopsis [--exclude-taxonomy]
   */
  public function cleanup( $args, $assoc_args ) {
    global $wpdb;

    timer_start();

    // STEP 1. Remove all terms which do not belong to any taxonomy ( broken relationships )

    $term_ids = $wpdb->get_col( "
      SELECT term_id
	      FROM $wpdb->terms
        WHERE term_id NOT IN ( SELECT distinct( term_id ) FROM $wpdb->term_taxonomy );
    " );

    if( !empty( $term_ids ) ) {
      WP_CLI::log( sprintf( __( 'Removing [%d] terms which do not belong to any taxonomy', ud_get_wp_property()->domain ), count( $term_ids ) ) );
      $condition = implode( "','", $term_ids );
      // Remove all term meta fields which belong to broken terms
      $wpdb->query("DELETE FROM $wpdb->termmeta WHERE term_id IN ( '$condition' );");
      // Now, remove all broken terms
      $wpdb->query("DELETE FROM $wpdb->terms WHERE term_id IN ( '$condition' );");
    } else {
      WP_CLI::log( 'No terms, which do not belong to any taxonomy, found.' );
    }

    // STEP 2. Remove all terms which do not belong to any registered taxonomy
    // NOTE: Think twice before run the command.

    $exclude = get_taxonomies();

    // In some cases you may want to exclude taxonomy which is not registered
    // So you can set your custom list of taxonomies via comma which should be ignored
    // using parameter --exclude-taxonomy
    if ( ! empty( $assoc_args['exclude-taxonomy'] ) ) {
      $custom_exclude = explode( ',', $assoc_args['exclude-taxonomy'] );
      foreach( $custom_exclude as $taxonomy ) {
        $taxonomy = trim( $taxonomy );
        if( !in_array( $taxonomy, $exclude ) ) {
          $exclude[$taxonomy] = $taxonomy;
        }
      }
    }

    $condition = implode( "','", $exclude );

    $term_ids = $wpdb->get_col( "
      SELECT distinct( term_id ) AS term_id
        FROM $wpdb->term_taxonomy
      WHERE taxonomy NOT IN ( '$condition' );
    " );

    if( !empty( $term_ids ) ) {
      WP_CLI::log( sprintf( __( 'Removing [%d] terms which do not belong to any registered taxonomy', ud_get_wp_property()->domain ), count( $term_ids ) ) );
      $condition = implode( "','", $term_ids );
      // Remove all term meta fields which belong to the terms
      $wpdb->query("DELETE FROM $wpdb->termmeta WHERE term_id IN ( '$condition' );");
      // Now, remove the terms
      $wpdb->query("DELETE FROM $wpdb->terms WHERE term_id IN ( '$condition' );");
    } else {
      WP_CLI::log( 'No terms, which do not belong to any registered taxonomy, found.' );
    }

    // STEP 3. Remove all not registered and 'property' taxonomies.

    $condition = implode( "','", $exclude );

    $taxonomy_ids = $wpdb->get_col( "
      SELECT distinct( term_taxonomy_id ) AS term_taxonomy_id
        FROM $wpdb->term_taxonomy
      WHERE taxonomy NOT IN ( '$condition' );
    " );

    if( !empty( $taxonomy_ids ) ) {
      WP_CLI::log( sprintf( __( 'Removing [%d] taxonomies which are not registered', ud_get_wp_property()->domain ), count( $taxonomy_ids ) ) );
      $condition = implode( "','", $taxonomy_ids );
      // Remove all term meta fields which belong to the terms
      $wpdb->query("DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ( '$condition' );");
      // Now, remove the terms
      $wpdb->query("DELETE FROM $wpdb->term_taxonomy WHERE term_taxonomy_id IN ( '$condition' );");
    } else {
      WP_CLI::log( 'No taxonomies, which are not registered, found.' );
    }

    WP_CLI::log( WP_CLI::colorize( '%Y' . __( 'Total time elapsed: ', ud_get_wp_property()->domain ) . '%N' . timer_stop() ) );

    WP_CLI::success( __( 'Done!', ud_get_wp_property()->domain ) );

  }

  /**
   * Delete all 'property' terms for a site
   *
   * @synopsis [--posts-per-page] [--taxonomy]
   * @param array $args
   * @param array $assoc_args
   *
   *
   */
  public function delete( $args, $assoc_args ) {

    if ( ! empty( $assoc_args['posts-per-page'] ) ) {
      $assoc_args['posts-per-page'] = absint( $assoc_args['posts-per-page'] );
    } else {
      $assoc_args['posts-per-page'] = 100;
    }

    $taxonomies = get_object_taxonomies( 'property' );
    if ( ! empty( $assoc_args['taxonomy'] ) ) {
      $taxonomy = $assoc_args['taxonomy'];
      if( in_array( $taxonomy, $taxonomies ) ) {
        $taxonomies = array( $taxonomy );
      } else if ( !taxonomy_exists( $taxonomy ) && WPP_F::verify_have_system_taxonomy( $taxonomy ) ) {
        $taxonomies = array( $taxonomy );
      } else {
        WP_CLI::error( __( 'Provided taxonomy does not belong to property', ud_get_wp_property()->domain ) );
        exit();
      }
    }

    timer_start();

    $results = array();
    $total = 0;
    foreach( $taxonomies as $taxonomy ) {
      WP_CLI::log( sprintf( __( 'Removing terms for [%s] taxonomy...', ud_get_wp_property()->domain ), $taxonomy ) );
      $result = $this->_delete_helper( $taxonomy, $assoc_args );
      WP_CLI::log( sprintf( __( 'Number of [%s] terms removed from site %d: %d', ud_get_wp_property()->domain ), $taxonomy, get_current_blog_id(), $result['removed'] ) );
      if ( ! empty( $result['errors'] ) ) {
        WP_CLI::warning( sprintf( __( 'Number of errors for taxonomy [%s] on site %d: %d', ud_get_wp_property()->domain ), $taxonomy, get_current_blog_id(), count( $result['errors'] ) ) );
      }
      $total += $result['removed'];
      $results[] = $result;
    }

    WP_CLI::log( sprintf( __( 'Total number of terms removed from site %d: %d', ud_get_wp_property()->domain ), get_current_blog_id(), $total ) );

    WP_CLI::log( WP_CLI::colorize( '%Y' . __( 'Total time elapsed: ', ud_get_wp_property()->domain ) . '%N' . timer_stop() ) );

    WP_CLI::success( __( 'Done!', ud_get_wp_property()->domain ) );
  }

  /**
   * Helper method for removing property terms
   *
   * @param string $taxonomy
   * @param array $args
   * @return array
   */
  private function _delete_helper( $taxonomy, $args ) {
    $removed = 0;
    $errors = array();

    $posts_per_page = 100;
    if ( ! empty( $args['posts-per-page'] ) ) {
      $posts_per_page = absint( $args['posts-per-page'] );
    }

    $offset = 0;
    if ( ! empty( $args['offset'] ) ) {
      $offset = absint( $args['offset'] );
    }

    /**
     * Create WP_Term_Query here and reuse it in the loop to avoid high memory consumption.
     */
    $query = new WP_Term_Query();

    while ( true ) {

      $args = apply_filters( 'wpp::cli::term::delete::args', array(
        'taxonomy'               => $taxonomy,
        'number'                 => $posts_per_page,
        'hide_empty'             => false,
        'fields'                 => 'id=>name',
        'offset'                 => 0,
        'orderby'                => 'term_id',
        'order'                  => 'DESC',
      ) );

      $query->query( $args );

      if ( !empty( $query->terms ) ) {

        foreach ( $query->terms as $id => $name ) {
          if( wp_delete_term( $id, $taxonomy ) ) {
            $removed ++;
            WP_CLI::log( current_time( 'mysql' ) . ' Removed Term [' . $name . ']' );
          } else {
            $errors[] = $id;
            WP_CLI::warning( sprintf( __( "Error occurred on trying to remove term '%s' [%d] for taxonomy [%s]" ), $name, $id, $taxonomy ) );
          }
        }

      } else {
        break;
      }

      WP_CLI::log( 'Processed: ' . ( count( $query->terms ) + $offset ) );

      $offset += count( $query->terms );

      usleep( 500 );

    }

    return array( 'removed' => $removed, 'errors' => $errors );
  }

}
