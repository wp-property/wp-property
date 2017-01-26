<?php
/**
 * Bootstrap
 *
 * @since 4.0.0
 */
namespace UsabilityDynamics\WPRETSC {

  if( !class_exists( 'UsabilityDynamics\WPRETSC\Utility' ) ) {

    final class Utility {

      /**
       *
       * @param $rets_id
       * @return null
       */
      public function find_property_by_rets_id( $rets_id ) {
        global $wpdb;

        $_actual_post_id = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='rets_id' AND meta_value={$rets_id};" );

        // temp support for old format
        if( empty( $_actual_post_id ) ) {
          $_actual_post_id = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='rets.id' AND meta_value={$rets_id};" );
        }

        // this excludes any orphan meta as well as "inherit" posts, it will also use the post with ther LOWER ID meaning its more likely to be original
        $query = new \WP_Query( array(
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
          return $query->posts[0]->ID;
        } else {
          ud_get_wp_rets_client()->write_log( 'Did not find any post ID using $rets_id [' . $rets_id . '].' );
        }

        return null;

      }

      /**
       *
       * By the time the post_data gets here it already has an ID because get_default_post_to_edit() is used to create it
       *  it is created with "auto-draft" status but all meta is already added to it.
       *
       * - all post meta/terms added by this thing are attached to the original post, it seems
       * @param $data
       */
      public function write_log( $data, $file = false ) {
        $file = $file ? $file : ud_get_wp_rets_client()->logfile;
        file_put_contents( ABSPATH . rtrim( $file, '/\\' ), '' . print_r( $data, true ) . ' in ' . timer_stop() . ' seconds.' . "\n", FILE_APPEND  );
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

        $options = (object) $options;

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
            {$limit}";

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
