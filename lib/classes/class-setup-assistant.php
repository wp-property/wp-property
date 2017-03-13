<?php
/**
 * Setup Assistant
 *
 *
 * ### API Actions
 *
 * - wpp_insert_demo_properties - Get demo listings from API and inset.
 * - wpp_flush_demo_properties - Remove any demo listings.
 * - wpp_save_setup_settings - Update setup settings, fetch standard Schema from API, insert.
 *
 *
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Setup_Assistant' ) ) {

    class Setup_Assistant {

      function __construct() {

        if( !defined( 'WPP_API_URL_STANDARDS' ) ) {
          define( 'WPP_API_URL_STANDARDS', 'https://api.usabilitydynamics.com/product/property/standard/v1' );
        }

        if( !defined( 'WPP_API_URL_DEMO_DATA' ) ) {
          define( 'WPP_API_URL_DEMO_DATA', 'https://api.usabilitydynamics.com/product/property/demo/v1' );
        }

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        // Add API Actions
        add_action( 'wp_ajax_wpp_save_setup_settings', array( 'UsabilityDynamics\WPP\Setup_Assistant', 'save_setup_settings' ) );
        add_action( 'wp_ajax_wpp_insert_demo_properties', array( 'UsabilityDynamics\WPP\Setup_Assistant', 'insert_demo_properties' ) );
        add_action( 'wp_ajax_wpp_flush_demo_properties', array( 'UsabilityDynamics\WPP\Setup_Assistant', 'flush_demo_properties' ) );

      }

      /**
       * Enqueue Scripts.
       * @param null $slug
       */
      static public function admin_enqueue_scripts($slug = null) {

        if( $slug !== 'property_page_property_settings' || !isset( $_GET['splash'] ) ) {
          return;
        }

        //owl-carousel base css
        wp_enqueue_style( 'setup-assist-owl-css', WPP_URL . "styles/owl.carousel.css", array(), WPP_Version, 'screen' );
        //page css
        wp_enqueue_style( 'setup-assist-page-css', WPP_URL . "styles/wpp.admin.setup.css", array(), WPP_Version, 'screen' );
        wp_enqueue_script( 'setup-assist-owl-js', WPP_URL . "scripts/owl.carousel.min.js", array( 'jquery' ), WPP_Version, true );
        wp_enqueue_script( 'setup-assist-page-js', WPP_URL . "scripts/wpp.admin.setup.js", array( 'jquery', 'setup-assist-owl-js' ), WPP_Version, true );

      }

      /**
       * Render UI
       *
       * UsabilityDynamics\WPP\Setup_Assistant::render_page()
       *
       * @internal param string $freemius_optin_slide
       */
      static public function render_page() {

        include ud_get_wp_property()->path( "static/views/admin/setup.php", 'dir' );

      }

      /**
       * AJAX Handler for Setup Assistant.
       * proxies to save_settings()
       *
       * @author raj
       */
      static public function save_setup_settings() {

        $data = WPP_F::parse_str( $_REQUEST[ 'data' ] );

        $_setup = array(
          'api' => WPP_API_URL_STANDARDS,
          'data' => $data,
          'schema' => self::get_settings_schema()
        );

        $_current_settings = get_option('wpp_settings');

        $_modified_settings = WPP_F::extend( $_current_settings, $_setup['schema'] );

        //die( '<pre>' . print_r( $_modified_settings['field_alias'], true ) . '</pre>' );

        if( is_array( $_modified_settings['property_stats_groups'] ) ) {
          // $_modified_settings['property_stats_groups'] = array_unique( $_modified_settings['property_stats_groups'] );
        }

        // @note This kills c.rabbit.ci response via Varnish, perhaps some sort of log output somewhere.
        if( is_array( $_modified_settings['searchable_attributes'] ) ) {
          // $_modified_settings[ 'searchable_attributes' ] = array_unique( $_modified_settings[ 'searchable_attributes' ] );
        }

        // preserve field aliases
        $_modified_settings['field_alias'] = $_current_settings['field_alias'];

        $_modified_settings['_updated'] = time();

        update_option( 'wpp_settings', $_modified_settings );

        $posts_array = get_posts( array(
          'posts_per_page' => 1,
          'orderby' => 'date',
          'order' => 'DESC',
          'post_type' => 'property',
          'post_status' => 'publish',
          'suppress_filters' => true
        ) );

        $return[ 'props_single' ] = get_permalink( $posts_array[ 0 ]->ID );

        $return[ '_settings' ] = $data[ 'wpp_settings' ];

        self::flush_cache();

        wp_send_json( $return );

      }

      /**
       * Also need to flush Memcached.
       *
       * deletes wpp_categorical_children
       * deletes wpp_location_children
       */
      static public function flush_cache( ) {

        foreach( array( 'wpp_categorical', 'wpp_location') as $taxonomy ) {
          wp_cache_delete( 'all_ids', $taxonomy );
          wp_cache_delete( 'get', $taxonomy );
          delete_option( "{$taxonomy}_children" );
          _get_term_hierarchy( $taxonomy );
        }

      }

      /**
       * Insert Demo Listings.
       *
       * @author potanin@UD
       */
      static public function insert_demo_properties( ) {

        $_result = array();

        foreach( self::fetch_demo_properties() as $_listing ) {
          $_result[] = self::insert_demo_listing( $_listing );
        }

        wp_send_json( $_result );


      }

      /**
       * Remove Demo Listings.
       *
       * @author potanin@UD
       */
      static public function flush_demo_properties() {

        $_query = array(
          'post_type' => 'property',
          'post_status' => array( 'draft', 'publish', 'private' ),
          'meta_key' => 'wpp_demo_listing'
        );

        $_result = array();

        foreach( query_posts($_query) as $_demo_listing ) {
          $_result[] = wp_delete_post( $_demo_listing->ID, true );
        }

        wp_send_json( array(
          "ok" => true,
          "result" => $_result
        ));

      }

      /**
       * Inserts a Single Demo Listing
       *
       * @param $data
       * @return array
       */
      static public function insert_demo_listing( $data ) {

        $defaults = array(
          'post_type' => 'property',
          'post_status' => 'publish',
        );

        $data = wp_parse_args( $data, $defaults );

        $_exists = query_posts(array(
          'post_type' => $data['post_type'],
          'post_status' => array( 'draft', 'publish', 'private' ),
          'meta_key' => 'wpp_import_id',
          'meta_value' => $data[ 'meta_input']['wpp_import_id'],
        ));

        // Get existing and bump version, if found.
        if( $_exists && $_exists[0] && $_exists[0]->ID ) {
          $data['ID'] = $_exists[0]->ID;
          $data['meta_input']['wpp_version'] = ( get_post_meta( $_exists[0]->ID, 'wpp_version', true ) ? (int) get_post_meta( $_exists[0]->ID, 'wpp_version', true ) : 0 ) + 1;
        }

        // Ensure we have this.
        $data['meta_input']['wpp_demo_listing'] = true;

        $insert_id = wp_insert_post( $data );

        // @todo Implement attachment with remote URLs.
        foreach( $data['meta_input']['wpp_media'] as $_media_item ) {
          continue;
        }

        //** Last attached file is set as thumbnail */
        if( isset( $attach_id ) ) {
          update_post_meta( $insert_id, '_thumbnail_id', $attach_id );
        }

        return array(
          "created" => $data['ID'] ? false : true,
          "id" => $insert_id
        );

      }

      /**
       * API Call for Standard Data
       *
       * @return object
       */
      static public function get_standard_data() {

        $_types = json_decode( wp_remote_retrieve_body( wp_remote_get( WPP_API_URL_STANDARDS . '/types' ) ) );
        $_fields = json_decode( wp_remote_retrieve_body( wp_remote_get( WPP_API_URL_STANDARDS . '/fields' ) ) );
        $_groups = json_decode( wp_remote_retrieve_body( wp_remote_get( WPP_API_URL_STANDARDS . '/groups' ) ) );

        return (object) array(
          'types' => $_types->data,
          'fields' => $_fields->data,
          'groups' => $_groups->data,
        );

      }

      /**
       * API Call for Standard Schema
       *
       * @return mixed
       */
      static public function get_settings_schema() {

        $_schema = json_decode( wp_remote_retrieve_body( wp_remote_get( WPP_API_URL_STANDARDS . '/schema' ) ), true );

        return isset( $_schema['data'] ) ? $_schema['data'] : array();

      }

      /**
       * API Call for Demo Listings
       *
       * @return array
       */
      static public function fetch_demo_properties() {

        $_listings = json_decode( wp_remote_retrieve_body( wp_remote_get( WPP_API_URL_DEMO_DATA . '/listings' ) ), true );

        return is_array( $_listings['data'] ) ? $_listings['data'] : array();

      }

    }

  }

}

