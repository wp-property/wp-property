<?php

/**
 * Compatibility connector for Real Homes theme
 */
namespace UsabilityDynamics\WPRETSC\Connectors {

  if ( !class_exists( 'UsabilityDynamics\WPRETSC\Connectors\RealHomes' ) ) {

    /**
     * Class RealHomes
     * @package UsabilityDynamics\WPRETSC\Connectors
     */
    final class RealHomes {

      /**
       * Constructor.
       */
      public function __construct() {

        add_action( 'wrc_property_published', array( $this, 'detect_the_agent' ), 100, 2 );
        add_action( 'wrc_property_published', array( $this, 'update_video_thumbnail' ), 101, 2 );

        add_action( 'wrc_inserted_media', array( $this, 'update_property_gallery' ), 100, 2 );
        add_action( 'wrc_inserted_media', array( $this, 'update_video_thumbnail' ), 101, 2 );

      }

      /**
       * Update RealHomes Property Gallery data
       * action: wrc_property_published
       *
       * @param $post_id
       * @param $media_data
       *
       */
      public function update_property_gallery( $post_id, $media_data ) {
        global $wpdb;

        delete_post_meta( $post_id, 'REAL_HOMES_property_images' );

        $attachments = $wpdb->get_results( $wpdb->prepare( "SELECT ID, guid, post_mime_type FROM $wpdb->posts WHERE post_parent=%s AND post_status='inherit' AND post_type='attachment'", $post_id ), ARRAY_A );
        if( !empty( $attachments ) && is_array( $attachments ) ) {
          foreach( $attachments as $attachment ) {
            if( strpos( $attachment[ 'guid' ], 'cdn.rets.ci' ) !== false ) {
              add_post_meta( $post_id, 'REAL_HOMES_property_images', $attachment['ID'] );
            }
          }
        }

      }

      /**
       * Update RealHomes Property Video Thumbnail
       * action: wrc_property_published
       *
       * @param $post_id
       * @param $post_data
       */
      public function update_video_thumbnail( $post_id, $post_data ) {

        delete_post_meta( $post_id, 'REAL_HOMES_tour_video_image' );

        $video_url = get_post_meta( $post_id, 'REAL_HOMES_tour_video_url', true );

        if( !empty( $video_url ) ) {
          $media = get_post_meta( $post_id, 'REAL_HOMES_property_images' );
          if( !empty( $media ) && count( $media ) >= 1 ) {
            add_post_meta( $post_id, 'REAL_HOMES_tour_video_image', $media[0] );
          }
        }

      }

      /**
       * Try to detect RealHomes Agent for the Property
       * action: wrc_property_published
       *
       * @param $post_id
       * @param $post_data
       */
      public function detect_the_agent( $post_id, $post_data ) {
        global $wpdb;

        $needle = get_post_meta( $post_id, 'REAL_HOMES_agents', true );
        $needle = trim( $needle );

        delete_post_meta( $post_id, 'REAL_HOMES_agents' );

        // Try to detect the agent by post_title

        $agents = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='agent' AND post_title LIKE %s", '%'. $needle . '%' ) );

        //ud_get_wp_rets_client()->write_log( $wpdb->last_query, "info" );
        //ud_get_wp_rets_client()->write_log( json_encode( $agents ), "info" );

        if( !empty( $agents ) && count( $agents ) == 1 ) {
          update_post_meta( $post_id, 'REAL_HOMES_agents', $agents[0] );
          update_post_meta( $post_id, 'REAL_HOMES_agent_display_option', 'agent_info' );
          return;
        }

        // Try to detect the agent by post meta

        $agents = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->postmeta WHERE post_id IN ( SELECT ID FROM $wpdb->posts WHERE post_type='agent' ) meta_value LIKE %s", '%'. $needle . '%' ) );

        //ud_get_wp_rets_client()->write_log( $wpdb->last_query, "info" );
        //ud_get_wp_rets_client()->write_log( json_encode( $agents ), "info" );

        if( !empty( $agents ) && count( $agents ) == 1 ) {
          update_post_meta( $post_id, 'REAL_HOMES_agents', $agents[0] );
          update_post_meta( $post_id, 'REAL_HOMES_agent_display_option', 'agent_info' );
          return;
        }

        $agent = apply_filters( 'wrc_real_homes_theme_default_agent_id', null, $post_id, $post_data );

        if( !empty( $agent ) && is_numeric($agent) ) {
          update_post_meta( $post_id, 'REAL_HOMES_agents', $agent );
          update_post_meta( $post_id, 'REAL_HOMES_agent_display_option', 'agent_info' );
          return;
        }

        update_post_meta( $post_id, 'REAL_HOMES_agent_display_option', 'none' );

      }

    }

  }

}