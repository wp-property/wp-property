<?php

namespace UsabilityDynamics\WPP {

  use WPP_F;
  use WP_CLI;

  if( !class_exists( 'UsabilityDynamics\WPP\Elasticsearch_Terms' ) ) {

    class Elasticsearch_Terms {

      private $terms = array();

      private $bulk = array();

      public function __construct() {

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
          // Extend Mapping with Terms type
          add_filter( 'ep_config_mapping', array( $this, 'ep_config_mapping' ));
          // Index all terms
          add_action( 'ep_cli_post_bulk_index', array( $this, 'index' ) );
        }

      }

      /**
       * Extend Mapping with Terms type
       *
       */
      public function ep_config_mapping( $mapping ) {
        $mapping['mappings']['term'] = array(
          'date_detection' => false,
          'dynamic_templates' => array(
            array(
              'meta' => array(
                'path_match' => 'meta.*',
                'mapping' => array(
                  'type' => 'keyword'
                ),
              ),
            ),
          ),
          '_all' => array(
            'analyzer' => 'simple',
          ),
          'properties' => array(
            'term_id' => array(
              'type' => 'long',
            ),
            'term_type' => array(
              'type' => 'keyword',
            ),
            'slug' => array(
              'type' => 'keyword',
            ),
            'name' => array(
              'type' => 'text',
              'fields' => array(
                'name' => array(
                  'type' => 'text',
                  'analyzer' => 'standard'
                ),
                'raw' => array(
                  'type' => 'keyword'
                ),
                'sortable' => array(
                  'type' => 'keyword'
                )
              )
            ),
            'taxonomy' => array(
              'type' => 'keyword',
            ),
            'parent' => array(
              'type' => 'long',
            ),
            'url_path' => array(
              'type' => 'text',
              'fields' => array(
                'url_path' => array(
                  'type' => 'text',
                  'analyzer' => 'standard'
                ),
                'raw' => array(
                  'type' => 'keyword'
                ),
                'sortable' => array(
                  'type' => 'keyword'
                )
              )
            ),
            'meta' => array(
              'type' => 'object'
            ),
            'term_suggest' => array(
              'type' => 'completion',
              'analyzer' => 'whitespace',
              'search_analyzer' => 'whitespace_analyzer',
              'contexts' => array(
                array(
                  'name' => 'term_type',
                  'type' => 'category'
                )
              )
            )
          ),
        );

        return $mapping;
      }

      /**
       * @param $posts
       */
      public function index( $posts ) {
        foreach( $posts as $post_id => $bulk ) {
          $post = json_decode( $bulk[1], ARRAY_A );
          if( !empty( $post['terms'] ) && is_array( $post['terms'] ) ) {
            foreach( $post['terms'] as $taxonomy => $terms ) {
              foreach( $terms as $term ) {
                $this->queue_term( $taxonomy, $term );
              }
            }
          }
        }
        if( !empty( $this->bulk ) ) {
          WP_CLI::log( sprintf( __( 'Indexing [%s] terms', 'elasticpress' ), count( $this->bulk ) ) );
          $this->bulk_index();
          WP_CLI::log( __( 'Terms indexed.', 'elasticpress' ) );
        }
      }

      /**
       * Add term to bulk
       *
       */
      private function queue_term( $taxonomy, $term ) {
        // Ignore already indexed terms
        if( in_array( $term['term_id'], $this->terms ) ) {
          return;
        }
        // Ignore terms already added to bulk
        if( array_key_exists( $term['term_id'], $this->bulk ) ) {
          return;
        }

        //WP_CLI::log( 'Starting indexing term ' . $term[ 'term_id' ] );

        $_term = get_term( $term['term_id'], $taxonomy );

        $meta = WPP_F::get_term_metadata( $_term );
        $term_type = isset( $meta['term_type'] ) ? $meta['term_type'] : null;

        $input = array_unique( array(
          str_replace( '&amp;', '&', $term['name'] ),
          strtolower( str_replace( '&amp;', '&', $term['name'] ) ),
          str_replace( array( ' ', '-', ',', '.' ), '', strtolower( sanitize_title( $term['name'] ) ) )
        ) );

        $context_term_type = array( $taxonomy );
        if( !empty( $term_type ) ) {
          $context_term_type[] = $term_type;
        }
        $context_term_type = array_unique( $context_term_type );

        $args = array(
          "term_id" => $term['term_id'],
          "term_type" => apply_filters( 'wpp:term_type', ( !empty( $term_type ) ? $term_type : $taxonomy ), $term, $meta ),
          "slug" => $term['slug'],
          "name" => $term['name'],
          "parent" => $_term->parent,
          "taxonomy" => $taxonomy,
          "url_path" => str_replace( home_url(), '', get_term_link( $_term, $taxonomy ) ),
          "meta" => $meta,
          "term_suggest" => array(
            "input" => $input,
            "contexts" => array(
              "term_type" => $context_term_type
            )
          )
        );

        // put the post into the queue
        $this->bulk[ $term['term_id'] ][] = '{ "index": { "_id": "' . absint( $term['term_id'] ) . '" } }';
        if ( function_exists( 'wp_json_encode' ) ) {
          $this->bulk[ $term['term_id'] ][] = addcslashes( wp_json_encode( $args ), "\n" );
        } else {
          $this->bulk[ $term['term_id'] ][] = addcslashes( json_encode( $args ), "\n" );
        }

        $this->terms[] = $term['term_id'];

        //WP_CLI::log( 'Ended indexing term ' . $term[ 'term_id' ] );

      }

      /**
       *
       */
      public function get_ep_bulk_index_term_request_path( $path ) {
        return trailingslashit( ep_get_index_name() ) . 'term/_bulk';
      }

      /**
       * Perform the bulk index operation
       *
       */
      private function bulk_index() {
        // monitor how many times we attempt to add this particular bulk request
        static $attempts = 0;

        // augment the attempts
        ++$attempts;

        // make sure we actually have something to index
        if ( empty( $this->bulk ) ) {
          WP_CLI::error( 'There are no terms to index.' );
        }

        $flatten = array();

        foreach ( $this->bulk as $post ) {
          $flatten[] = $post[0];
          $flatten[] = $post[1];
        }

        // make sure to add a new line at the end or the request will fail
        $body = rtrim( implode( "\n", $flatten ) ) . "\n";

        // show the content length in bytes if in debug
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
          WP_CLI::log( 'Request string length: ' . size_format( mb_strlen( $body, '8bit' ), 2 ) );
        }

        add_filter( 'ep_bulk_index_post_request_path', array( $this, 'get_ep_bulk_index_term_request_path' ), 100 );

        // decode the response
        $response = ep_bulk_index_posts( $body );

        remove_action( 'ep_bulk_index_post_request_path', array( $this, 'get_ep_bulk_index_term_request_path' ), 100 );

        if ( is_wp_error( $response ) ) {
          WP_CLI::error( implode( "\n", $response->get_error_messages() ) );
        }

        // if we did have errors, try to add the documents again
        if ( isset( $response['errors'] ) && $response['errors'] === true ) {
          if ( $attempts < 5 ) {
            foreach ( $response['items'] as $item ) {
              if ( empty( $item['index']['error'] ) ) {
                unset( $this->posts[$item['index']['_id']] );
              }
            }
            $this->bulk_index();
          } else {
            foreach ( $response['items'] as $item ) {
              if ( ! empty( $item['index']['_id'] ) ) {
                $this->failed_posts[] = $item['index']['_id'];
                $this->failed_posts_message[$item['index']['_id']] = $item['index']['error'];
              }
            }
            $attempts = 0;
          }
        } else {
          // there were no errors, all the posts were added
          $attempts = 0;
        }

        // Flush bulk body
        $this->bulk = array();
      }

    }

  }

}