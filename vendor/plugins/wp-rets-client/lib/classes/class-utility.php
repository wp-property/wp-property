<?php
/**
 * Bootstrap
 *
 * @since 4.0.0
 */
namespace UsabilityDynamics\WPRETSC {

  use WP_Query;
  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPRETSC\Utility' ) ) {

    final class Utility {

      /**
       * Insert new media, remove any old media if no longer in payload.
       *
       * wp post list --post_parent=27735668 --post_type=attachment
       *
       * @param $_post_id
       * @param $_rets_media
       * @param $_rets_media.updated - timestamp of last update
       * @param $_rets_media.items - array of items
       * @return bool
       */
      static public function insert_media( $_post_id, $_rets_media ) {
        global $wpdb;

        if( !isset( $_rets_media ) || !is_array($_rets_media ) || !isset( $_rets_media['items'])) {
          return false;
        }

        ud_get_wp_rets_client()->write_log( "Running [insert_media] for [$_post_id], have [" .  count( $_rets_media['items'] ) . "] media items, updated [" . $_rets_media['updated'] ."].", 'debug' );

        if( isset( $_rets_media[ 'updated' ] )) {
          $_updated_formatted = date("Y-m-d H:i:s",strtotime($_rets_media[ 'updated' ]));
          $_query =  "SELECT ID, post_modified_gmt, guid FROM {$wpdb->posts} WHERE post_parent='$_post_id' AND post_type='attachment' AND post_mime_type='image/jpeg' AND post_modified_gmt < '$_updated_formatted'; " ;
        } else {
          $_query =  "SELECT ID, post_modified_gmt, guid FROM {$wpdb->posts} WHERE post_parent='$_post_id' AND post_type='attachment' AND post_mime_type='image/jpeg'; " ;
        }

        // @temp, always remove all.
        $_query =  "SELECT ID, post_modified_gmt, guid FROM {$wpdb->posts} WHERE post_parent='$_post_id' AND post_type='attachment' AND post_mime_type='image/jpeg'; " ;

        $_attachments = $wpdb->get_results($_query);

        // Remove old attachments..
        foreach( (array) $_attachments as $_attachment ) {
          $wpdb->delete( $wpdb->posts, array( 'ID' => $_attachment->ID ) );
          $wpdb->delete( $wpdb->postmeta, array( 'post_id' => $_attachment->ID ) );
        }

        if( count( $_attachments ) ) {
          ud_get_wp_rets_client()->write_log( "Deleted [" .  count( $_attachments ) . "] that are older than our new updated [" . $_rets_media['updated'] ."] timestamp.", 'debug' );
        }

        $items = (array) $_rets_media['items'];
        // Be sure out media items sorted by menu_order.
        usort( $items, function($a,$b) {
          if($a['menu_order'] === $b['menu_order']) return 0;
          return ($a['menu_order'] < $b['menu_order']) ? -1 : 1;
        });

        $_thumbnail_setting = false;

        // Insert new media.
        foreach( $items as $media ) {

          $filetype = wp_check_filetype( basename( $media[ 'guid' ] ), null );

          $attachment = array(
            'guid' => $media[ 'guid' ],
            'post_mime_type' => ( !empty( $filetype[ 'type' ] ) ? $filetype[ 'type' ] : 'image/jpeg' ),
            'post_title' => $media['post_title'],
            'post_content' => $media['post_content'],
            'post_excerpt' => isset( $media['post_excerpt'] ) ? $media['post_excerpt'] : '',
            'post_status' => 'inherit',
            'menu_order' => $media[ 'menu_order' ] ? ( (int)$media[ 'menu_order' ] ) : null,
          );

          if( isset( $media[ 'updated' ] ) ) {

            array_merge( $attachment, array(
              'post_modified' => date( "Y-m-d H:i:s", strtotime( $_rets_media[ 'updated' ] ) ),
              'post_modified_gmt' => date( "Y-m-d H:i:s", strtotime( $_rets_media[ 'updated' ] ) ),
              'post_date' => date( "Y-m-d H:i:s", strtotime( $media[ 'updated' ] ) ),
              'post_date_gmt' => isset( $media[ 'updated' ] ) ? date( "Y-m-d H:i:s", strtotime( $media[ 'updated' ] ) ) : null
            ) );

          }

          $attach_id = wp_insert_attachment( $attachment, $media[ 'guid' ], $_post_id );

          update_post_meta( $attach_id, '_is_remote', '1' );

          // set the first item from media as the thumbnail
          // in case it could not be set, we try again and again with other media files
          if( !$_thumbnail_setting ) {
            //set_post_thumbnail( $_post_id, $attach_id );

            // No idea why but set_post_thumbnail() fails routinely as does update_post_meta, testing this method.
            delete_post_meta( $_post_id, '_thumbnail_id' );

            $_thumbnail_setting = add_post_meta( $_post_id, '_thumbnail_id', (int) $attach_id );

            if( $_thumbnail_setting ) {
              ud_get_wp_rets_client()->write_log( 'Setting thumbnail [' . $attach_id . '] to post [' . $_post_id . '] because it has order of 1, result: ', 'debug' );
            } else {
              ud_get_wp_rets_client()->write_log( 'Error! Failured at setting thumbnail [' . $attach_id . '] to post [' . $_post_id . ']', 'error' );
            }

          }

        }


        ud_get_wp_rets_client()->write_log( "Inserted or updated [" .  count( $_rets_media['items'] ) . "] that are older than our new updated [" . $_rets_media['updated'] ."] timestamp.", 'debug' );

        return true;

      }

      /**
       * Creates taxonomies and terms. Also handles hierarchies.
       *
       * @author potanin@UD
       * @param $_post_id
       * @param $_post_data_tax_input
       * @param array $post_data
       */
      static public function insert_property_terms( $_post_id, $_post_data_tax_input, $post_data = array() ) {

        ud_get_wp_rets_client()->write_log( "Have [" . count( $_post_data_tax_input ) . "] taxonomies to process.", 'debug' );

        foreach( (array) $_post_data_tax_input as $tax_name => $tax_tags ) {
          ud_get_wp_rets_client()->write_log( "Starting to process [$tax_name] taxonomy.", 'debug' );

          $handled = apply_filters( 'retsci::insert_property_terms::handle', false, $tax_name, array(
            'post_id' => $_post_id,
            'post_data_tax_input' => $_post_data_tax_input,
            'post_data' => $post_data,
          ) );

          if( $handled ) {
            ud_get_wp_rets_client()->write_log( 'Taxonomy [' . $tax_name . '] has been handled via filter [wpp::insert_property_terms::handle]', 'debug' );
            continue;
          }

          // Flush all old terms for particular taxonomy, before we set new terms.
          wp_delete_object_term_relationships( $_post_id, $tax_name );

          // Avoid hierarchical taxonomies since they do not allow simple-value passing.
          if( method_exists( 'WPP_F', 'verify_have_system_taxonomy' ) ) {
            WPP_F::verify_have_system_taxonomy( $tax_name, array( 'hierarchical' => false ) );
          } else {
            Utility::verify_have_system_taxonomy( $tax_name, array( 'hierarchical' => false ) );
          }

          if( is_taxonomy_hierarchical( $tax_name ) ) {
            ud_get_wp_rets_client()->write_log( "Handling hierarchical taxonomy [$tax_name].", 'debug' );

            $_terms = array();

            foreach( $tax_tags as $_term_name ) {

              if( is_object( $_term_name ) || is_array( $_term_name ) ) {

                if( isset( $_term_name[ '_id'] ) ) {
                  ud_get_wp_rets_client()->write_log( "Have hierarchical object term [$tax_name] with [" . $_term_name[ '_id'] . "] _id.", 'debug' );
                  $_insert_result = WPP_F::insert_terms($_post_id, array($_term_name), array( '_taxonomy' => $tax_name ) );
                  ud_get_wp_rets_client()->write_log( "Inserted [" . count( $_insert_result['set_terms'] ) . "] terms for [$tax_name] taxonomy.", 'debug' );
                }

                continue;
              }

              ud_get_wp_rets_client()->write_log( "Handling inserting term [$_term_name] for [$tax_name].", 'debug' );

              $_term_parts = explode( ' > ', $_term_name );

              $_term_parent_value = $_term_parts[0];

              if( isset( $_term_parts[1] ) && $_term_parts[1] ) {
                $_term_child_value = $_term_parts[1];
              } else {
                $_term_child_value = null;
              }

              $_term = get_term_by( 'slug', sanitize_title( $_term_name ), $tax_name, ARRAY_A );
              $_term_parent = get_term_by( 'slug', sanitize_title( $_term_parent_value ), $tax_name, ARRAY_A );

              if( is_wp_error( $_term_parent ) ) {
                ud_get_wp_rets_client()->write_log( "Error inserting term [$tax_name]: " . $_term_parent->get_error_message(), 'error' );
                //continue;
              }

              if( !$_term_parent ) {
                ud_get_wp_rets_client()->write_log( "Did not find parent term [$tax_name] - [$_term_parent_value].", 'warn' );

                $_term_parent = wp_insert_term( $_term_parent_value, $tax_name, array(
                  "slug" => sanitize_title( $_term_parent_value )
                ));

                if( is_wp_error( $_term_parent ) ) {
                  ud_get_wp_rets_client()->write_log( "Error creating term [$_term_parent_value] with [" . $_term_parent->get_error_message() ."].", 'error' );
                } else {
                  ud_get_wp_rets_client()->write_log( "Created parent term [$_term_parent_value] with [" . $_term_parent['term_id'] ."].", 'info' );
                }

              }

              if( $_term_parent && !$_term && isset( $_term_parts ) && $_term_child_value  ) {

                ud_get_wp_rets_client()->write_log( "Did not find child term [$_term_child_value] with slug [" .sanitize_title( $_term_name ) . "].", 'info' );
                $_term = wp_insert_term( $_term_name, $tax_name, array(
                  "parent" => $_term_parent['term_id'],
                  "slug" => sanitize_title( $_term_name ),
                  "description" => $_term_child_value
                ));

                // add_term_meta();

                if( $_term && !is_wp_error( $_term ) ) {

                  $_child_term_name_change = wp_update_term( $_term['term_id'], $tax_name, array(
                    'name' => $_term_parent_value,
                    'slug' => sanitize_title( $_term_name )
                  ));


                }

                ud_get_wp_rets_client()->write_log( "Created child term [$_term_name] with [" . $_term['term_id'] ."] for [$_term_parent_value] parent.", 'debug' );
              }

              if( $_term_parent && $_term_parent['term_id'] ) {
                $_terms[] = $_term_parent['term_id'];
              }

              if( $_term && $_term['term_id'] ) {
                // ud_get_wp_rets_client()->write_log( "Did not find and could not create child term [$_term_parent_value] using [".sanitize_title( $_term_parts[1] )."] slug" );
                $_terms[] = $_term['term_id'];
              }

            }

            if( isset( $_terms ) && !empty( $_terms ) ) {
              $_inserted_terms = wp_set_post_terms( $_post_id, $_terms, $tax_name );
              ud_get_wp_rets_client()->write_log( "Inserted [" . count( $_inserted_terms ) . "] terms.", 'info' );
            }

          }

          if( !is_taxonomy_hierarchical( $tax_name ) ) {
            ud_get_wp_rets_client()->write_log( "Handling non-hierarchical taxonomy [$tax_name].", 'debug' );

            $_terms = array();

            // check each tag, make sure its NOT an an array.
            foreach( $tax_tags as $_term_name ) {

              // Item is an array, which means this entry includes term meta.
              if( is_object( $_term_name ) || is_array( $_term_name ) && isset( $_term_name[ '_id'] ) ) {
                $_insert_result = WPP_F::insert_terms($_post_id, array($_term_name), array( '_taxonomy' => $tax_name ) );
                ud_get_wp_rets_client()->write_log( "Inserted [" . count( $_insert_result['set_terms'] ) . "] non-hierarchical terms for [$tax_name] taxonomy with [" . $_term_name[ '_id'] . "] _id.", 'debug' );
              } else {
                $_terms[] = $_term_name;
              }

            }

            if( isset( $_terms ) && !empty( $_terms ) ) {
              $_inserted_terms = wp_set_post_terms( $_post_id, $_terms, $tax_name );
              ud_get_wp_rets_client()->write_log( "Inserted [" . count( $_inserted_terms ) . "] terms into [$tax_name] taxonomy.", "debug" );
            }

          }

        }

      }

      /**
       * Registers a system taxonomy if needed with most essential arguments.
       *
       * @since 2.2.1
       * @author potanin@UD
       * @param string $taxonomy
       * @param array $args
       * @return string
       */
      static public function verify_have_system_taxonomy($taxonomy = '', $args = array())
      {

        $args = wp_parse_args($args, array(
          'hierarchical' => true
        ));

        if (taxonomy_exists($taxonomy)) {
          return $taxonomy;
        }

        register_taxonomy( substr( $taxonomy, 0, 32 ), array( 'property' ), array(
          'hierarchical' => $args['hierarchical'],
          'update_count_callback' => null,
          'labels' => array(),
          'show_ui' => false,
          'show_in_menu' => false,
          'show_admin_column' => false,
          'meta_box_cb' => false,
          'query_var' => false,
          'rewrite' => false
        ));

        if (taxonomy_exists($taxonomy)) {
          return $taxonomy;
        } else {
          return false;
        }

      }

      /**
       * Get published, private and future property counts for each schedule.
       *
       * @param array $options
       * @return array|bool|mixed
       */
      static public function get_schedule_stats( $options = array() ) {
        global $wpdb;

        $terms = get_terms( array(
          'taxonomy' => 'rets_schedule',
          'orderby' => 'name',
          'order'=> 'DESC',
          'hide_empty' => false
        ) );

        ud_get_wp_rets_client()->write_log( 'Starting [get_schedule_stats].', 'debug' );

        foreach( $terms as $_term ) {
          wp_update_term_count_now( $_term->term_taxonomy_id, 'rets_schedule' );
        }

        ud_get_wp_rets_client()->write_log( 'Completed term count in [get_schedule_stats].', 'debug' );

        $options = wp_parse_args( $options, array(
          'cache' => 'schedule-stats'
        ));

        if( $options[ 'cache' ] ) {

          $_cache = wp_cache_get( $options[ 'cache' ], 'wp-rets-client' );

          if( $_cache ) {
            $_cache[ '_cached' ] = true;
            return $_cache;
          }

        }

        //foreach( $wpdb->get_results( "SELECT meta_value as schedule_id, count(meta_value) as count from {$wpdb->postmeta} where meta_key = 'wpp_import_schedule_id' group by meta_value order by count DESC;" ) as $_data ) {
        //  $_stats[ $_data->schedule_id ] = $_data->count;
        //}

        $_data = array();

        $_total = 0;

        foreach( $terms as $_term ) {

          $query = new WP_Query( array(
            'post_status' => array( 'publish', 'private', 'future', 'draft' ),
            'post_type'   => 'property',
            'posts_per_page' => 1,
            'tax_query' => array(
              array(
                'taxonomy' => 'rets_schedule',
                'field'    => 'slug',
                'terms'    => array( $_term->slug ),
              ),
            ),
            //'meta_key'    => ( defined( 'RETS_ID_KEY' ) ? RETS_ID_KEY : 'wpp::rets_pk' ),
            //'meta_value'  => $rets_id,
          ) );

          $_data[] = array(
            '_id' => strval( $_term->slug ),
            'schedule' => strval( $_term->slug ),
            'total' => intval( $query->found_posts ),
            //'term_count' => $_term->count,
            //'meta_count' => isset( $_stats[ $_term->slug ] ) ? intval( $_stats[ $_term->slug ] ) : null,
            //'total' => $_term->count,
            //'posts' => $posts->found_posts
          );

          $_total = $_total + $query->found_posts;
        }
        //die( '<pre>' . print_r( $terms , true ) . '</pre>' );

        $_result = array(
          'ok' => true,
          'data' => $_data,
          'terms' => $terms,
          'total' => $_total
          //'stats' => $_stats
        );

        if( $options[ 'cache' ] ) {
          wp_cache_set( $options[ 'cache' ], $_result, 'wp-rets-client', 3600 );
        }

        return $_result;

      }

      /**
       *
       * @param $rets_id
       * @return null
       */
      static public function find_property_by_rets_id( $rets_id ) {
        global $wpdb;

        $_cache_key = 'mls-id-' . $rets_id;

        $_cache = wp_cache_get( $_cache_key, 'wp-rets-client' );

        if( $_cache ) {
          ud_get_wp_rets_client()->write_log( 'Found [' . $_cache . '] using $rets_id  [' . $rets_id . '] in cache.', 'debug' );
          return $_cache;
        }

        $_actual_post_id = $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='rets_id' AND meta_value={$rets_id};" );

        if( $_actual_post_id ) {
          wp_cache_set( $_cache_key, $_actual_post_id, 'wp-rets-client', 86400  );
          return $_actual_post_id;
        }

        // temp support for old format
        if( empty( $_actual_post_id ) ) {
          $_actual_post_id = $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='rets.id' AND meta_value={$rets_id};" );
          wp_cache_set( $_cache_key, $_actual_post_id, 'wp-rets-client', 86400 );
          return $_actual_post_id;
        }

        // this excludes any orphan meta as well as "inherit" posts, it will also use the post with ther LOWER ID meaning its more likely to be original
        $query = new WP_Query( array(
          'post_status' => array( 'publish', 'draft', 'pending', 'trash', 'private', 'future' ),
          'post_type'   => 'property',
          'meta_key'    => ( defined( 'RETS_ID_KEY' ) ? RETS_ID_KEY : 'wpp::rets_pk' ),
          'meta_value'  => $rets_id,
        ) );

        // @TODO: check if query returned result! And there is no Error! peshkov@UD

        // what if there is two - we fucked up somewhere before...
        if( count( $query->posts ) > 1 ) {
          ud_get_wp_rets_client()->write_log( "Error! Multiple (".count( $query->posts ).") matches found for rets_id [" . $rets_id . "]." );
        }

        if( count( $query->posts ) > 0 ) {
          ud_get_wp_rets_client()->write_log( 'Found ' . $query->posts[0]->ID . ' using $rets_id: ' . $rets_id);

          wp_cache_set( $_cache_key, $query->posts[0]->ID, 'wp-rets-client', 86400  );

          return $query->posts[0]->ID;


        } else {
          ud_get_wp_rets_client()->write_log( 'Did not find any post ID using $rets_id [' . $rets_id . '].' );
        }

        return null;

      }

      /**
       * Write to Log. Writes to either rets-log.log or rets-debug.log, depending on type.
       *
       * The rets-debug.log file is not written to if it does not exist.
       *
       * By the time the post_data gets here it already has an ID because get_default_post_to_edit() is used to create it
       *  it is created with "auto-draft" status but all meta is already added to it.
       *
       * - all post meta/terms added by this thing are attached to the original post, it seems
       *
       * tail -f wp-content/rets-log.log
       * tail -f wp-content/rets-debug.log
       *
       * @param $data
       * @param $type
       * @return bool
       */
      static public function write_log( $data, $type = 'debug' ) {

        // same format as debug.log
        $_time_stamp = date('d-M-Y h:i:s T', time());

        if( is_array( $data ) || is_object( $data ) ) {
          $_content = '[' . $_time_stamp  . '] ' . print_r( $data, true ) . ' [' . timer_stop() . 's].' . "\n";
        } else {
          $_content = '[' . $_time_stamp  . '] ' . $data . ' [' . timer_stop() . 's].' . "\n";
        }

        if( $type === 'error' || $type === 'info' || $type === 'warning' ) {
          file_put_contents( ABSPATH . rtrim( ud_get_wp_rets_client()->logfile, '/\\' ), $_content, FILE_APPEND  );
          return true;
        }

        if( file_exists( ud_get_wp_rets_client()->debug_file ) ) {
          file_put_contents( ABSPATH . rtrim( ud_get_wp_rets_client()->debug_file, '/\\' ), $_content, FILE_APPEND  );
          return true;
        }

        return false;

      }

      /**
       * Build Date Array Range for Histogram Buckets.
       *
       * @author potanin@UD
       * @see http://stackoverflow.com/questions/4312439/php-return-all-dates-between-two-dates-in-an-array
       * @param $strDateFrom
       * @param $strDateTo
       * @return array
       */
      public static function build_date_range( $strDateFrom,$strDateTo ) {
        // takes two dates formatted as YYYY-MM-DD and creates an
        // inclusive array of the dates between the from and to dates.

        // could test validity of dates here but I'm already doing
        // that in the main script

        $aryRange=array();

        $iDateFrom=mktime(1,0,0,substr($strDateFrom,5,2),     substr($strDateFrom,8,2),substr($strDateFrom,0,4));
        $iDateTo=mktime(1,0,0,substr($strDateTo,5,2),     substr($strDateTo,8,2),substr($strDateTo,0,4));

        if ($iDateTo>=$iDateFrom)
        {
          array_push($aryRange,date('Y-m-d',$iDateFrom)); // first entry
          while ($iDateFrom<$iDateTo)
          {
            $iDateFrom+=86400; // add 24 hours
            array_push($aryRange,date('Y-m-d',$iDateFrom));
          }
        }
        return $aryRange;

      }

      /**
       * Get Detailed Modified Listing Histogram Data
       *
       * @author potanin@UD
       * @param $options
       * @return array|null|object
       */
      public static function query_modified_listings( $options ) {
        global $wpdb;

        $options = (object) wp_parse_args( $options, array(
          "limit" => 10
        ));

        // automatically set end
        if( !$options->endOfStartDate ) {
          $options->endOfStartDate = date('Y-m-d', strtotime($options->startDate. ' + 1 day'));
        }

        if( !$options->dateMetaField ) {
          $options->dateMetaField = 'rets_modified_datetime';
        }
        // $limit = 'LIMIT 0, 20;';

        $_query = "SELECT posts.ID, pm_modified.meta_value as {$options->dateMetaField}, pm_schedule.meta_value as schedule_id
          FROM {$wpdb->posts} posts
          LEFT JOIN {$wpdb->postmeta} pm_modified ON posts.ID = pm_modified.post_id
          LEFT JOIN {$wpdb->postmeta} pm_schedule ON posts.ID = pm_schedule.post_id
          WHERE 
            pm_schedule.meta_key='wpp_import_schedule_id' AND
            pm_schedule.meta_value='{$options->schedule}' AND
            pm_modified.meta_key='{$options->dateMetaField}' AND
            pm_modified.meta_value between DATE('{$options->startDate} 00:00:00') AND DATE('{$options->endOfStartDate} 00:00:00') AND 
            posts.post_type='property'  
            {$options->limit}";

        //echo($_query);

        // AND posts.post_status in ('publish', 'draft')
        // , p.post_title, p.post_status, p.post_modified
        // meta_value between DATE('2016-07-15 00:00:00') AND DATE('2016-07-16 00:00:00') AND
        $_result = $wpdb->get_results( $_query );

        return $_result;

      }

    }

  }

}
