<?php

/**
 * Compatibility connector for Houzez theme
 */
namespace UsabilityDynamics\WPRETSC\Connectors {

  if ( !class_exists( 'UsabilityDynamics\WPRETSC\Connectors\Houzez' ) ) {

    /**
     * Class Houzez
     * @package UsabilityDynamics\WPRETSC\Connectors
     */
    final class Houzez {

      /**
       * Constructor.
       */
      public function __construct() {

        add_action( 'wrc_property_published', array( $this, 'detect_the_agents' ), 100, 2 );
        add_action( 'wrc_property_published', array( $this, 'update_video_thumbnail' ), 101, 2 );

        add_action( 'wrc_inserted_media', array( $this, 'update_property_gallery' ), 100, 2 );
        add_action( 'wrc_inserted_media', array( $this, 'update_video_thumbnail' ), 101, 2 );

      }

      /**
       * Update Houzez Property Gallery data
       * action: wrc_property_published
       *
       * @param $post_id
       * @param $media_data
       */
      public function update_property_gallery( $post_id, $media_data ) {
        global $wpdb;

        delete_post_meta( $post_id, 'fave_property_images' );

        $attachments = $wpdb->get_results( $wpdb->prepare( "SELECT ID, guid, post_mime_type FROM $wpdb->posts WHERE post_parent=%s AND post_status='inherit' AND post_type='attachment'", $post_id ), ARRAY_A );
        if( !empty( $attachments ) && is_array( $attachments ) ) {
          foreach( $attachments as $attachment ) {
            if( strpos( $attachment[ 'guid' ], 'cdn.rets.ci' ) !== false ) {
              add_post_meta( $post_id, 'fave_property_images', $attachment['ID'] );
            }
          }
        }

      }

      /**
       * Update Houzez Property Video Thumbnail
       * action: wrc_property_published
       *
       * @param $post_id
       * @param $post_data
       */
      public function update_video_thumbnail( $post_id, $post_data ) {

        delete_post_meta( $post_id, 'fave_video_image' );

        $video_url = get_post_meta( $post_id, 'fave_video_url', true );

        if( !empty( $video_url ) ) {
          $media = get_post_meta( $post_id, 'fave_property_images' );
          if( !empty( $media ) && count( $media ) >= 1 ) {
            add_post_meta( $post_id, 'fave_video_image', $media[0] );
          }
        }

      }

      /**
       * Try to detect Houzez Agents for the Property
       * action: wrc_property_published
       *
       * @param $post_id
       * @param $post_data
       */
      public function detect_the_agents( $post_id, $post_data ) {

        // We MUST break if meta_input.fave_agents was not provided in request!
        // Because, in other case, we will try to detect the already detected agent, and, of course,
        // the agent will not be found and the correct agent(s) will be replaced with the default one!
        // peshkov@UDX
        if( !isset( $post_data[ 'meta_input' ][ 'fave_agents' ] ) ) {
          return;
        }

        $houzez_options = get_option( 'houzez_options' );

        $needles = trim( get_post_meta( $post_id, 'fave_agents', true ) );
        $needles = explode( ',', $needles );

        foreach( $needles as $i => $needle ) {
          $needles[$i] = trim($needle);
        }

        $needles = array_filter($needles);

        //ud_get_wp_rets_client()->write_log( "detect_the_agents. Data " . json_encode( $needles ), 'info' );

        if( isset( $houzez_options['enable_multi_agents'] ) && $houzez_options['enable_multi_agents'] ) {
          //ud_get_wp_rets_client()->write_log( "detect_the_agents. Multi [enabled]", 'info' );
        } else {
          //ud_get_wp_rets_client()->write_log( "detect_the_agents. Multi [disabled]", 'info' );
        }

        delete_post_meta( $post_id, 'fave_agents' );

        $agent_ids = array();

        foreach( $needles as $needle ) {
          $agent_id = $this->detect_the_agent( $needle );

          if( $agent_id ) {
            //ud_get_wp_rets_client()->write_log( "detect_the_agents. Detected Agent [$agent_id] by [$needle]", 'info' );
            array_push( $agent_ids, $agent_id );
            if( !isset( $houzez_options['enable_multi_agents'] ) || !$houzez_options['enable_multi_agents'] ) {
              break;
            }
          } else {
            //ud_get_wp_rets_client()->write_log( "detect_the_agents. Agent could NOT be detected by [$needle]", 'info' );
          }
        }

        //ud_get_wp_rets_client()->write_log( "detect_the_agents. Found [" . count($agent_ids) . "] agents", 'info' );

        if( empty( $agent_ids ) ) {
          $agent_id = apply_filters( 'wrc_houses_theme_default_agent_id', null, $post_id, $post_data );
          //ud_get_wp_rets_client()->write_log( "detect_the_agents. Default Agent [$agent_id] is set for post [$post_id]", 'info' );
          if( !empty( $agent_id ) ) {
            array_push( $agent_ids, $agent_id );
          }
        }

        if( !empty( $agent_ids ) ) {
          //ud_get_wp_rets_client()->write_log( "detect_the_agents. Display option [agent_info] is set for post [$post_id]", 'info' );
          update_post_meta( $post_id, 'fave_agent_display_option', 'agent_info' );
          foreach( $agent_ids as $agent_id ) {
            //ud_get_wp_rets_client()->write_log( "detect_the_agents. Assigned agent [$agent_id] to the post [$post_id]", 'info' );
            add_post_meta( $post_id, 'fave_agents', $agent_id );
          }
        } else {
          //ud_get_wp_rets_client()->write_log( "detect_the_agents. Display option [none] is set for post [$post_id]", 'info' );
          update_post_meta( $post_id, 'fave_agent_display_option', 'none' );
        }

      }

      /**
       * Try to detect the agent
       *
       * @param $needle
       * @return bool
       */
      private function detect_the_agent( $needle ) {
        global $wpdb;

        // Try to detect the agent by post_title

        $agents = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='houzez_agent' AND post_title LIKE %s", '%'. $needle . '%' ) );

        // Be sure, IDs are unique!!!!
        if( !empty( $agents ) ) {
          $agents = array_unique( $agents );
        }

        if( !empty( $agents ) && count( $agents ) == 1 ) {
          return $agents[0];
        }

        // Try to detect the agent by post meta

        $agents = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE post_id IN ( SELECT ID FROM $wpdb->posts WHERE post_type='houzez_agent' ) AND meta_value = %s", $needle ) );

        // Be sure, IDs are unique!!!!
        if( !empty( $agents ) ) {
          $agents = array_unique( $agents );
        }

        //ud_get_wp_rets_client()->write_log( "detect_the_agents. Detected by [$needle] postmeta [" . count($agents) .  "] ", 'info' );

        if( !empty( $agents ) && count( $agents ) == 1 ) {
          return $agents[0];
        }

        return false;

      }

    }

  }

}