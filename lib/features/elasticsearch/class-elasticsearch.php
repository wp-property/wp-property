<?php
/**
 * Elastisearch integration for WP-Property
 * based on Elasticpress plugin
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

        //define( 'EP_INDEX_NAME', 'rdc-maxim-test' );

        add_action( 'plugins_loaded', array( $this, 'init' ) );
      }

      /**
       *
       */
      public function init() {

        $_vendor_path = ud_get_wp_property()->path( 'vendor/plugins/elasticpress/elasticpress.php', 'dir' );

        if( !get_option( 'ud_site_id' ) ) {
          return;
        }

        if( !defined( 'EP_VERSION' ) && file_exists( $_vendor_path ) ) {

          // Handles indexing of Terms
          new Elasticsearch_Terms();

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

          // Increase timeout on indexing posts to 3 minutes, because
          // in some cases default 30 seconds is not enough. peshkov@UD
          add_filter( 'ep_bulk_index_posts_request_args', function( $args ) {
            $args['timeout'] = 180;
            return $args;
          } );

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

        // Set index-name.
        add_filter( 'ep_index_name', array( $this, 'ep_index_name' ), 50, 2 );

        // Meta fields to exclude.
        add_filter( 'ep_prepare_meta_excluded_public_keys', array( $this, 'ep_prepare_meta_excluded_public_keys' ), 50, 3 );

        // Parse/Analyze responses.
        add_action( 'ep_index_post_retrieve_raw_response', array( $this, 'ep_index_post_retrieve_raw_response' ), 50, 3 );

        // We prevent multiple sync of the same post during one request.
        // Because, if some extra update of post is proceeded, we save the post to sync queue
        // which is being executed on 'shutdown' action.
        //
        // But on 'shutdown' action we may have new specific added or disabled filters and actions,
        // so, post's data may be broken on running ep_prepare_post() function.
        //
        //
        // Example, we may update 'wpp_gpid' post meta after post indexed.
        //
        // See: https://github.com/wp-property/wp-property/blob/5ff06a4e48df89916e47499d2fe374ba12cd4e7e/lib/class_functions.php#L4990
        //
        // So, it is causing EP indexing of post one more time on 'shutdown' function, here:
        // https://github.com/wp-property/wp-property/blob/5ff06a4e48df89916e47499d2fe374ba12cd4e7e/vendor/plugins/elasticpress/classes/class-ep-sync-manager.php#L46
        //
        // And 'post_content' meta on 'shutdown' is being broken because of using 'the_content' filter,
        // when specific hooks added/removed....
        //
        // @author peshkov@UD
        //
        //add_filter( 'ep_post_sync_kill', function( $bool, $post_args, $post_id ) {
        //  if( doing_action( 'shutdown' ) ) {
        //    return true;
        //  }
        //  return $bool;
        //}, 100, 3 );

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

        $_exclude = array( 'rets_media' );

        if( !empty( $exclude ) && is_array( $exclude ) ) {
          $exclude = array_unique( $exclude + $_exclude );
        } else {
          $exclude = $_exclude;
        }

        return $exclude;

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
       * Extend indexed post object with
       * - tax_input with term meta.
       * - wpp_location_pin
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

        if(
          isset( $post_args['post_meta']['wpp_location_pin'] ) &&
          count( $post_args['post_meta']['wpp_location_pin'] ) == 2
        ) {
          $post_args['wpp_location_pin'] = array(
            "lat" => $post_args['post_meta']['wpp_location_pin'][0],
            "lon" => $post_args['post_meta']['wpp_location_pin'][1]
          );
        }

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

        $input = array();

        if( !empty( $post_args['post_title'] ) ) {

          $input[] = $post_args['post_title'];

          // Split Title to the parts for better Completion Suggestion
          $parts = explode( ' ', $post_args['post_title'] );
          foreach( $parts as $k => $v ) {
            unset( $parts[ $k ] );
            $_parts = $parts;
            if( isset( $_parts[ ( $k + 1 ) ] ) && strlen( $_parts[ ( $k + 1 ) ] ) <= 3 ) {
              unset( $_parts[ ( $k + 1 ) ] );
            }
            $part = trim( implode( ' ', $_parts ) );
            if( strlen( $part ) >= 5 && !in_array( $part, $input ) ) {
              $input[] = $part;
            }
          }

          $input[] = str_replace( array( ' ', '-', ',', '.' ), '', strtolower( sanitize_title( $post_args['post_title'] ) ) );

        }

        $post_args['title_suggest'] = array(
          "input" => $input
        );

        $post_args['title_suggest'] = apply_filters( 'wpp:elastic:title_suggest', $post_args['title_suggest'], $post_args, $post_id );

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
        $index = defined( 'EP_INDEX_NAME' ) ? EP_INDEX_NAME : get_option( 'ud_site_id' );
        return $index;
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

        @$mapping['settings']['index.mapping.total_fields.limit'] = 10000;

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
            'path_match' => 'tax_input.*.meta.*',
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
                'term_type' => array(
                  'type' => 'keyword',
                ),
                'url_path' => array(
                  'type' => 'keyword',
                ),
                'meta' => array(
                  'type' => 'object',
                  "dynamic" => true,
                  'properties' => array()
                )
              ),
            ),
          ),
        );

        $mapping['mappings']['post']['properties']['wpp_location_pin'] = array(
          "ignore_malformed" => true,
          "type" => "geo_point"
        );

        $mapping['mappings']['post']['properties']['tax_input'] = array( "type" => "object" );

        $mapping['mappings']['post']['properties']['title_suggest'] = array(
          'type' => 'completion',
          //'analyzer' => 'nGram_analyzer',
          'analyzer' => 'standard',
          //'search_analyzer' => 'whitespace_analyzer',
          //'preserve_separators' => true,
          //'preserve_position_increments' => true,
          //'max_input_length' => 50,
          'contexts' => array(
            array(
              'name' => 'listing_status',
              'type' => 'category'
            ),
            array(
              'name' => 'listing_type',
              'type' => 'category'
            )
          )
        );

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

        foreach ( $selected_taxonomies as $taxonomy ) {
          $object_terms = get_the_terms( $post->ID, $taxonomy->name );

          if ( ! $object_terms || is_wp_error( $object_terms ) ) {
            continue;
          }

          $terms_dic = array();

          foreach ( $object_terms as $term ) {

            $meta = WPP_F::get_term_metadata( $term );

            $term_type = isset( $meta['term_type'] ) ? $meta['term_type'] : null;
            $term_type = apply_filters( 'wpp:term_type', ( !empty( $term_type ) ? $term_type : $taxonomy->name ), $term, $meta );

            $url_path = str_replace( home_url(), '', get_term_link( $term, $taxonomy->name ) );

            $_term = array_filter(array(
              'term_id'  => $term->term_id,
              'slug'     => $term->slug,
              'name'     => $term->name,
              'parent'   => $term->parent,
              "term_type" => $term_type,
              "url_path" => $url_path,
              'meta'    => $meta,
            ));

            $_term_type = str_replace( '-', '_', $term_type );

            if( !isset( $terms_dic[ $_term_type ] ) ) {
              $terms_dic[ $_term_type ] = array();
            }

            $terms_dic[ $_term_type ][] = $_term;

          }
          $terms[ $taxonomy->name ] = $terms_dic;
        }

        return $terms;
      }

    }

  }

}