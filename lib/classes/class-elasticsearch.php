<?php
/**
 * Setup Assistant
 *
 *
 *
 * wp elasticpress index --posts-per-page=1 --nobulk
 * wp elasticpress index --posts-per-page=1 --show-bulk-errors
 *
 *
 *
 *
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Elasticsearch' ) ) {

    class Elasticsearch {

      /**
       * Elasticsearch constructor.
       */
      function __construct() {

        $_vendor_path = dirname( __FILE__, 3 ) . '/vendor/plugins/elasticpress/elasticpress.php';

        if( !get_option( 'ud_site_id' ) ) {
          return;
        }

        if( !defined( 'EP_VERSION' ) && file_exists( $_vendor_path ) ) {

          // Load plugin.
          require_once( $_vendor_path );

          // Force host to be set to api.realty.ci.
          if( !defined( 'EP_HOST' ) ) {
            define( 'EP_HOST', 'https://api.realty.ci/elasticsearch/v1' );
          }

          // Only allow property post type if we are running our version of Elasticpress.
          add_filter( 'ep_admin_supported_post_types', array( $this, 'ep_admin_supported_post_types' ) );
          add_filter( 'ep_indexable_post_types', array( $this, 'ep_indexable_post_types' ), 50, 1 );
          add_filter( 'ep_indexable_post_status', array( $this, 'ep_indexable_post_status' ), 50, 1 );

          // Remove menus.
          add_action( 'admin_menu', array( $this, 'admin_menu' ), 200 );

          add_filter( 'ep_keep_index', '__return_true' );
          add_filter( 'ep_sync_terms_allow_hierarchy', '__return_true' );

        }

        add_filter( 'option_ep_index_meta', array( $this, 'option_ep_index_meta' ) );
        add_filter( 'option_ep_active_modules', array( $this, 'option_ep_active_modules' ) );

        // Can't change these in mapping.
        add_filter( 'ep_default_index_number_of_replicas', array( $this, 'ep_default_index_number_of_replicas' ) );
        add_filter( 'ep_default_index_number_of_shards', array( $this, 'ep_default_index_number_of_shards' ) );

        // Add Mapping.
        add_filter( 'ep_config_mapping', array( $this, 'ep_config_mapping' ) );

        // Add our request headers.
        add_filter( 'ep_format_request_headers', array( $this, 'ep_format_request_headers' ) );

        add_filter( 'ep_post_sync_args_post_prepare_meta', array( $this, 'ep_post_sync_args_post_prepare_meta' ), 40, 2 );
        add_filter( 'ep_post_sync_args', array( $this, 'ep_post_sync_args' ), 40, 2 );
        add_filter( 'ep_post_sync_args', array( $this, 'post_title_suggest' ), 50, 2 );
        add_filter( 'ep_post_sync_args', array( $this, 'filter_term_suggest' ), 60, 2 );

        // Set index-name.
        add_filter( 'ep_index_name', array( $this, 'ep_index_name' ), 50, 2 );

        // Meta fields to exclude.
        add_filter( 'ep_prepare_meta_excluded_public_keys', array( $this, 'ep_prepare_meta_excluded_public_keys' ), 50, 3 );

        // Parse/Analyze responses.
        add_action( 'ep_index_post_retrieve_raw_response', array( $this, 'ep_index_post_retrieve_raw_response' ), 50, 3 );
        // add_filter( 'ep_config_mapping_request', array( $this, 'ep_config_mapping_request' ), 50, 3 );

      }

      /**
       * Always off because it'll be always on. Prevents it from starting to sync.
       *
       * The process can still start in browser and run using AJAX but it will not record state.
       *
       * @param $value
       * @return bool
       */
      public function option_ep_index_meta( $value ) {
        return array( );
      }

      public function option_ep_active_modules( $value ) {
        return array( 'search', 'admin' );
      }

      /**
       * Number of shards for index.
       *
       * @param $value
       * @return int
       */
      public function ep_default_index_number_of_shards( $value ) {
        return 3;
      }

      /**
       * Number of replicas for index.
       *
       * @param $value
       * @return int
       */
      public function ep_default_index_number_of_replicas( $value ) {
        return 0;
      }

      /**
       * Exclude meta fields. Should use [is_protected_meta] at some point.
       *
       * @param $exclude
       * @return array
       */
      public function ep_prepare_meta_excluded_public_keys( $exclude ) {

        return array( 'rets_media', 'wpp_import_time', 'wpp_import_schedule_id' );

      }

      /**
       * Debug failed mapping update request's response.
       *
       * @param $request
       * @param $index
       * @param $mapping
       * @return mixed
       */
      public function ep_config_mapping_request( $request, $index, $mapping ) {

        if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
          // error_log( 'WP-Property Elasticsearch mapping update error ' . print_r( $request, true ) );
        }

        return $request;

      }

      /**
       * Debug failed index response.
       *
       * @param $request
       * @param $index
       * @param $mapping
       * @return mixed
       */
      public function ep_index_post_retrieve_raw_response( $request, $index, $mapping ) {

        if ( 201 !== wp_remote_retrieve_response_code( $request ) && 200 !== wp_remote_retrieve_response_code( $request ) ) {
          //error_log( 'WP-Property Elasticsearch index update error ' . print_r($request, true) );
        }

        return $request;

      }

      /**
       * Extend indexed post object with tax_input with term meta.
       *
       * @param $post_args
       * @param $post_id
       * @return mixed
       */
      public function ep_post_sync_args( $post_args, $post_id ) {

        $post = get_post( $post_id );

        $post_args = apply_filters( 'wpp:elastic:prepare', $post_args, $post_id, $post );

        // Prepare our tax_input fields.
        $post_args['tax_input'] = $this->prepare_terms( $post );

        return $post_args;

      }

      /**
       * Exclude fields from [meta], like [wpp_location_pin].
       *
       * @param $post_args
       * @param $post_id
       * @return mixed
       */
      public function ep_post_sync_args_post_prepare_meta( $post_args, $post_id  ) {

        unset( $post_args['meta']['wpp_location_pin'] );

        return $post_args;
      }

      /**
       * Adds [title_suggest] field for autocompletion.
       *
       * @todo Expand the input array with common terms.
       *
       * @param $post_args
       * @param $post_id
       * @return mixed
       */
      public function post_title_suggest( $post_args, $post_id ) {

        $post_args['title_suggest'] = array(
          "input" => array(
            $post_args['post_title']
          ),
          "output" => $post_args['post_title'],
          "payload" => array(
            "post_id" => $post_id,
            "post_status" => $post_args['post_status'],
            "post_title" => $post_args['post_title'],
            "post_name" => $post_args['post_name'],
            "permalink" => str_replace( array( get_home_url(), get_site_url() ), '', $post_args['permalink'] )
          )
        );

        $post_args['title_suggest'] = apply_filters( 'wpp:elastic:title_suggest', $post_args['title_suggest'], $post_args, $post_id );

        // lowercase/tokenize fields.
        foreach( $post_args['title_suggest']['input'] as $_input_index => $_input_vaue ) {
          $post_args['title_suggest']['input'][ $_input_index  ] = str_replace( array( ' ', '-', ',', '.' ), '', strtolower( sanitize_title( $_input_vaue ) ) );
        }

        return $post_args;

      }

      /**
       * Create [term_suggest] list.
       *
       *
       *
       * @param $post_args
       * @param $post_id
       * @return mixed
       */
      public function filter_term_suggest( $post_args, $post_id ) {

        $_suggestion_taxonomies = array(
          'wpp_listing_location',
          'wpp_schools',
          'wpp_listing_type',
          'wpp_listing_status',
          'wpp_listing_label',
          'wpp_agent',
          'wpp_office',
          //'wpp_listing_category'
        );

        if ( ! empty( $post_args['terms'] ) ) {
          foreach ( $post_args['terms'] as $_tax => $taxonomy ) {

            foreach ( $taxonomy as $term ) {

              if( !in_array( $_tax, $_suggestion_taxonomies )) {
                continue;
              }

              $_term_metadata = WPP_F::get_term_metadata( get_term( $term['term_id'], $_tax ) );

              $suggest[] = array(
                "input" => array_unique( array(
                  str_replace( '&amp;', '&', $term['name'] ),
                  strtolower( str_replace( '&amp;', '&', $term['name'] ) ),
                  str_replace( array( ' ', '-', ',', '.' ), '', strtolower( sanitize_title( $term['name'] ) ) )
                )),
                "output" => $term['name'],
                "payload" => array_filter( array(
                  "term_id" => $term['term_id'],
                  "term_type" => isset( $_term_metadata['term_type'] ) ? $_term_metadata['term_type'] : null,
                  "slug" => $term['slug'],
                  "name" => str_replace( '&amp;', '&', $term['name'] ),
                  "tax" => $_tax,
                  "url_path" => isset( $_term_metadata['url_path'] ) ? $_term_metadata['url_path'] : null
                ))
              );

            }

          }
        }

        if ( ! empty( $suggest ) ) {
          $post_args['term_suggest'] = $suggest;
        }

        return $post_args;
      }

      /**
       * Set api.realty.ci Index Name
       *
       * @param $index_name
       * @param null $blog_id
       * @return mixed|void
       */
      static public function ep_index_name( $index_name, $blog_id = null ) {
        return get_option( 'ud_site_id' );
      }

      /**
       * @param $index_name
       * @param null $blog_id
       */
      static public function admin_menu( $index_name, $blog_id = null ) {

        remove_menu_page( 'elasticpress' );
        remove_submenu_page( 'elasticpress', 'elasticpress-setting' );

      }

      /**
       * Enforce property to be only type indexed.
       *
       * @param array $post_types
       * @return array
       */
      static public function ep_admin_supported_post_types( $post_types = array() ) {

        return array(
          'property' => 'property'
        );

      }

      /**
       * Only Property.
       *
       * @param array $post_types
       * @return array
       */
      static public function ep_indexable_post_types( $post_types = array() ) {

        return array(
          'property' => 'property'
        );

      }

      /**
       * Define publishable post statuses.
       *
       * @param array $post_types
       * @return array
       */
      static public function ep_indexable_post_status( $post_types = array() ) {

        return array('publish');

      }

      /**
       * Set api.realty.ci Document Mapping.
       *
       *
       *    wp elasticpress put-mapping
       *    wp elasticpress index --nobulk --post-type=property
       *    wp elasticpress index --posts-per-page=1 --post-type=property
       *    wp elasticpress index --posts-per-page=50 --post-type=property
       *
       *
       * @param $mapping
       * @return mixed
       */
      static public function ep_config_mapping( $mapping ) {

        $mapping['settings']['analysis']['filter']['autocomplete_filter'] = array(
          "min_gram" => 1,
          "max_gram" => 20,
          "type" => "edge_ngram"
        );

        $mapping['settings']['analysis']['filter']['nGram_filter'] = array(
          "type" => "edge_ngram",
          "token_chars" => array(
            "letter",
            "digit",
            "punctuation",
            "symbol"
          ),
          "min_gram" => 1,
          "max_gram" => 10,
        );
        $mapping['settings']['analysis']['analyzer']['autocomplete'] = array(
          "type" => "custom",
          "filter" => array(
            "lowercase",
            "autocomplete_filter"
          ),
          "tokenizer" => "standard"
        );

        $mapping['settings']['analysis']['analyzer']['nGram_analyzer'] = array(
          "type" => "custom",
          "filter" => array(
            "lowercase",
            "asciifolding",
            "nGram_filter"
          ),
          "tokenizer" => "whitespace",
          "max_token_length" => 500
        );

        $mapping['settings']['analysis']['analyzer']['whitespace_analyzer'] = array(
          "type" => "custom",
          "filter" => array(
            "lowercase",
            "asciifolding"
          ),
          "tokenizer" => "whitespace",
          "max_token_length" => 500
        );

        $mapping['mappings']['post']['dynamic_templates'][] = array(
          'tax_input_meta' => array(
            'path_match' => 'tax_input.meta.*',
            'mapping' => array(
              'type' => 'string',
              "index" => "not_analyzed"
            ),
          ),
        );

        $mapping['mappings']['post']['dynamic_templates'][] = array(
          'tax_input' => array(
            'path_match' => 'tax_input.*',
            'mapping' => array(
              'type' => 'object',
              "index" => "not_analyzed",
              'path' => 'full',
              'properties' => array(
                'name' => array(
                  'type' => 'string',
                  'fields' => array(
                    'raw' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'sortable' => array(
                      'type' => 'string',
                      'analyzer' => 'ewp_lowercase',
                    ),
                  ),
                ),
                'term_id' => array(
                  'type' => 'long',
                ),
                'parent' => array(
                  'type' => 'long',
                ),
                'slug' => array(
                  'type' => 'string',
                  'index' => 'not_analyzed',
                ),
                'meta' => array(
                  'type' => 'object',
                  "dynamic" => true,
                  'properties' => array(
                    'term_id' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'term_type' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'wpp_schools_city' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'related_taxonomy' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'related_term' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'related_type' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'pattern' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'url_path' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'url_slug' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'wpp_schools_state' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),
                    'wpp_schools_county' => array(
                      'type' => 'string',
                      'index' => 'not_analyzed',
                    ),

                  )
                )
              ),
            ),
          ),
        );

        $mapping['mappings']['post']['properties']['post_meta']['properties']['wpp_location_pin'] = array(
          "lat_lon" => true,
          "ignore_malformed" => true,
          "type" => "geo_point"
        );

        $mapping['mappings']['post']['properties']['tax_input'] = array( "type" => "object" );

        $mapping['mappings']['post']['properties']['title_suggest'] = array(
          'type' => 'completion',
          'analyzer' => 'nGram_analyzer',
          'search_analyzer' => 'whitespace_analyzer',
          'preserve_separators' => true,
          'preserve_position_increments' => true,
          'max_input_length' => 50,
          'payloads' => true
        );

        $mapping['mappings']['post']['properties']['term_suggest'] = array(
          'type' => 'completion',
          'analyzer' => 'whitespace',
          'search_analyzer' => 'whitespace_analyzer',
          'payloads' => true
        );

        //die(json_encode($mapping, JSON_PRETTY_PRINT));

        return $mapping;
      }

      /**
       * Set api.realty.ci Request Headers.
       *
       * @param $headers
       * @return mixed
       */
      static public function ep_format_request_headers( $headers ) {

        $headers[ 'x-site-id' ] = get_option( 'ud_site_id' );
        $headers[ 'x-site-public-key' ] = get_option( 'ud_site_public_key' );
        $headers[ 'x-site-secret-token' ] = get_option( 'ud_site_secret_token' );

        return $headers;
      }

      /**
       * Recursively get all the ancestor terms of the given term
       * @param $terms
       * @param $term
       * @param $tax_name
       * @return array
       */
      private function get_parent_terms( $terms, $term, $tax_name ) {
        $parent_term = get_term( $term->parent, $tax_name );
        if( ! $parent_term || is_wp_error( $parent_term ) )
          return $terms;
        if( ! isset( $terms[ $parent_term->term_id ] ) ) {

          $terms[ $parent_term->term_id ] = array_filter( array(
            'term_id' => $parent_term->term_id,
            'slug'    => $parent_term->slug,
            'name'    => $parent_term->name,
            'parent'  => $parent_term->parent,
            'meta' => WPP_F::get_term_metadata( $term )
          ));

        }
        return $this->get_parent_terms( $terms, $parent_term, $tax_name );
      }

      /**
       * Prepare terms to send to ES.
       *
       * @param object $post
       *
       * @since 0.1.0
       * @return array
       */
      private function prepare_terms( $post ) {
        $taxonomies          = get_object_taxonomies( $post->post_type, 'objects' );
        $selected_taxonomies = array();

        foreach ( $taxonomies as $taxonomy ) {
          if ( $taxonomy->public ) {
            $selected_taxonomies[] = $taxonomy;
          }
        }

        $selected_taxonomies = apply_filters( 'ep_sync_taxonomies', $selected_taxonomies, $post );

        if ( empty( $selected_taxonomies ) ) {
          return array();
        }

        $terms = array();

        $allow_hierarchy = apply_filters( 'ep_sync_terms_allow_hierarchy', false );

        foreach ( $selected_taxonomies as $taxonomy ) {
          $object_terms = get_the_terms( $post->ID, $taxonomy->name );

          if ( ! $object_terms || is_wp_error( $object_terms ) ) {
            continue;
          }

          $terms_dic = array();

          foreach ( $object_terms as $term ) {
            if( ! isset( $terms_dic[ $term->term_id ] ) ) {

              $terms_dic[ $term->term_id ] = array_filter(array(
                'term_id'  => $term->term_id,
                'slug'     => $term->slug,
                'name'     => $term->name,
                'parent'   => $term->parent,
                'meta'    => WPP_F::get_term_metadata( $term )
              ));

              if( $allow_hierarchy ){
                $terms_dic = $this->get_parent_terms( $terms_dic, $term, $taxonomy->name );
              }
            }
          }
          $terms[ $taxonomy->name ] = array_values( $terms_dic );
        }

        return $terms;
      }

    }

  }

}

