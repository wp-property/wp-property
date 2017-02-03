<?php
/**
 * Setup Assistant
 *
 * This page has been suppressed.
 * OLD FLOW
 * class-dashboard > install.php
 * NEW FLOW
 * class-dashboard > FREEMIUM [ wpp_fs()->_connect_page_render(); ] > include "install.php" > Call above class
 *
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Setup_Assistant' ) ) {

    class Setup_Assistant {

      function __construct() {

        //flush_rewrite_rules();
        //** flush Object Cache */
        //wp_cache_flush();

        if( !defined( 'WPP_API_URL_STANDARDS' ) ) {
          define( 'WPP_API_URL_STANDARDS', 'https://api.usabilitydynamics.com/product/property/standard/v1' );
        }


        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        add_action( 'wp_ajax_wpp_save_setup_settings', array( 'UsabilityDynamics\WPP\Setup_Assistant', 'save_setup_settings' ) );

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
        global $wp_properties;

        $data = WPP_F::parse_str( $_REQUEST[ 'data' ] );


        $_setup = array(
          'api' => WPP_API_URL_STANDARDS,
          'data' => $data,
          'schema' => self::get_settings_schema()
        );

        $_current_settings = get_option('wpp_settings');
        //die( '<pre>' . print_r( $_current_settings, true ) . '</pre>' );
        $_modified_settings = WPP_F::extend( $_current_settings, $_setup['schema'] );

        //die( '<pre>' . print_r( $_modified_settings , true ) . '</pre>' );
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

        wp_send_json( $return );

      }

      /**
       * Add dummy properties for Setup Assistant.
       *
       * @author raj
       */
      static public function generate_asst_dummy_properties( $data ) {

        // select default property for the dummy properties
        if( count( $data[ 'wpp_settings' ][ 'property_types' ] ) > 0 ) {
          $default_prop = array_keys( $data[ 'wpp_settings' ][ 'property_types' ] );
          $default_prop = $default_prop[ 0 ];
        } else {
          $default_prop = "house";
        }

        global $user_ID, $wp_properties, $wpdb;

        /* Determine if the dummy properties already exist */
        $posts = $wpdb->get_col( "
      SELECT `post_title`
      FROM {$wpdb->posts}
      WHERE 
      `post_title` IN ('122 Bishopsgate','East Pointe Marketplace','MIDLEVELS WEST','720 N Larrabee St Apt','460 W Huron St','7846 Charlesmont Road','3212 Ramona Avenue','4602 Chatford Avenue','619 Beechfield Avenue','5109 Eugene Avenue','99 Richfield','9812 NE Avenue')
       AND `post_status` = 'publish'
    " );

        /* Check array to avoid issues in future */
        if( !is_array( $posts ) ) {
          $posts = array();
        }

        /* If Property doesn't exist we create it : ONE */
        if( !in_array( '122 Bishopsgate', $posts ) ) {

          self::generate_asst_dummy_property( array(
            'post_title' => '122 Bishopsgate',
            'post_content' => 'Take notice of this amazing home! It has an original detached 2 garage/workshop built with the home and on a concrete slab along with regular 2 car attached garage. Very nicely landscaped front and back yard. Hardwood floors in Foyer, den, dining and great room. Great room is open to large Kitchen. Carpet in all upstairs bedrooms. Home is located in the Woodlands in the middle of very nice community. You and your family will feel right at home. A must see.',
            'tagline' => 'Need Room for your TOYS! Take notice of this unique Home!',
            'location' => '122 Bishopsgate, Jacksonville, NC 28540, USA',
            'price' => '195000',
            'featured' => 'true',
            'bedrooms' => '4',
            'property_type' => $default_prop,
            'bathrooms' => '4',
            'fees' => '100',
            'year_built' => '2001',
            'living_space' => "1000",
            'total_rooms' => '6',
            'property_feature' => 'cable_prewire',
            'community_feature' => 'dishwasher',
            'phone_number' => '8002700781',
            'img_index' => '1',
          ) );

        }

        /* If Property doesn't exist we create it : TWO */
        if( !in_array( 'East Pointe Marketplace', $posts ) ) {

          self::generate_asst_dummy_property( array(
            'post_title' => 'East Pointe Marketplace',
            'post_content' => "Convenient suburban shopping experience located in the epicenter of Milwaukee's lower east side.
Adjacent to the Milwaukee School of Engineering
On bus line
Ample off-street parking ",
            'tagline' => 'Need Room for your TOYS! Take notice of this unique Home!',
            'location' => '605 E Lyon St Milwaukee, WI 53202',
            'price' => '215000',
            'bedrooms' => '5',
            'bathrooms' => '4',
            'fees' => '200',
            'property_feature' => 'cable_prewire',
            'community_feature' => 'dishwasher',
            'year_built' => '2002',
            'living_space' => "2000",
            'total_rooms' => '8',
            'property_type' => $default_prop,
            'phone_number' => '8002300781',
            'img_index' => '1',
          ) );

        }
        /* If Property doesn't exist we create it : THREE */
        if( !in_array( 'MIDLEVELS WEST', $posts ) ) {

          self::generate_asst_dummy_property( array(
            'post_title' => 'MIDLEVELS WEST',
            'post_content' => 'Ideal family flat with 4 bedrooms at upper Conduit Road',
            'tagline' => 'Ideal family flat with 4 bedrooms at upper Conduit Road',
            'location' => '122 Bishopsgate, Jacksonville, NC 28540, USA',
            'price' => '255000',
            'bedrooms' => '8',
            'featured' => 'true',
            'fees' => '300',
            'property_feature' => 'cathedral_ceiling',
            'community_feature' => 'double_oven',
            'year_built' => '2003',
            'living_space' => "3000",
            'total_rooms' => '11',
            'property_type' => $default_prop,
            'bathrooms' => '8',
            'phone_number' => '9992700781',
            'img_index' => '1',
          ) );

        }
        /* If Property doesn't exist we create it : FOUR */
        if( !in_array( '720 N Larrabee St Apt', $posts ) ) {

          self::generate_asst_dummy_property( array(
            'post_title' => '720 N Larrabee St Apt',
            'post_content' => 'Beautiful west views of river in ideal River North location close to downtown, Magnificent Mile, shopping, dining, entertainment. Split 2 bedroom, 2 bath floor plan with hardwood floors, granite counters and breakfast bar, stainless steel appliances, gas fireplace, 12-foot ceilings in this trendy loft-style unit with large balcony to enjoy sunset views over the river. Plenty of room for dining table and tons of closet space built out with Elfa shelving. 2nd bedroom closed off to the ceiling for privacy. Washer/dryer in the unit. Full-amenity building with onsite manager/engineer, 24-hour door staff, fitness room, bike storage; indoor heated parking for $35,000 extra, additional storage cage included.',
            'tagline' => 'Great new home',
            'location' => '720 N Larrabee St Apt 1012,Chicago, IL 60654',
            'price' => '985000',
            'bedrooms' => '8',
            'fees' => '400',
            'year_built' => '2004',
            'living_space' => "4000",
            'property_feature' => 'cathedral_ceiling',
            'community_feature' => 'double_oven',
            'total_rooms' => '10',
            'bathrooms' => '8',
            'property_type' => $default_prop,
            'phone_number' => '9856700781',
            'img_index' => '1',
          ) );

        }
        /* If Property doesn't exist we create it : FIVE */
        if( !in_array( '460 W Huron St', $posts ) ) {

          self::generate_asst_dummy_property( array(
            'post_title' => '460 W Huron St',
            'post_content' => 'Unique amenities nestled among exquisite building features will make your home feel like an urban oasis while ours dedicated staff will not only fulfill your needs, but anticipate them.',
            'tagline' => 'Only for a limited period DEPOSIT $0!!!!',
            'location' => '460 W Huron St,Chicago, IL 60654',
            'price' => '876000',
            'bedrooms' => '5',
            'featured' => 'true',
            'property_feature' => 'disability_equipped',
            'community_feature' => 'central_vacuum',
            'fees' => '500',
            'year_built' => '2005',
            'living_space' => "5000",
            'total_rooms' => '8',
            'property_type' => $default_prop,
            'bathrooms' => '5',
            'phone_number' => '8002708876',
            'img_index' => '1',
          ) );
        }
      }

      static public function generate_asst_dummy_property( $data ) {
        global $wp_properties;

        $defaults = array(
          'post_title' => 'Dummy Listing',
          'post_content' => 'Donec volutpat elit malesuada eros porttitor blandit. Donec sit amet ligula quis tortor molestie sagittis tincidunt at tortor. Phasellus augue leo, molestie in ultricies gravida; blandit et diam. Curabitur quis nisl eros! Proin quis nisi quam, sit amet lacinia nisi. Vivamus sollicitudin magna eu ipsum blandit tempor. Duis rhoncus orci at massa consequat et egestas lectus ornare? Duis a neque magna, quis placerat lacus. Phasellus non nunc sapien, id cursus mi! Mauris sit amet nisi vel felis molestie pretium.',
          'tagline' => 'Donec volutpat elit malesuada eros porttitor blandit',
          'location' => '122 Bishopsgate, Jacksonville, NC 28540, USA',
          'property_type' => 'house',
          'img_index' => '1', // Available: '1', '2'
          'price' => '',
          'bedrooms' => '',
          'bathrooms' => '',
          'phone_number' => '',
        );

        $data = wp_parse_args( $data, $defaults );

        //** STEP 1. Create dummy property */

        $insert_id = wp_insert_post( array(
          'post_title' => $data[ 'post_title' ],
          'post_status' => 'publish',
          'post_content' => $data[ 'post_content' ],
          'post_type' => 'property',
        ) );

        $property_type = $data[ 'property_type' ];

        update_post_meta( $insert_id, 'property_type', $property_type );

        if( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) && key_exists( $wp_properties[ 'configuration' ][ 'address_attribute' ], $wp_properties[ 'property_stats' ] ) ) {
          update_post_meta( $insert_id, 'location', $data[ 'location' ] );

          if( method_exists( 'WPP_F', 'revalidate_address' ) ) {
            WPP_F::revalidate_address( $insert_id );
          }
        }

        if( !empty( $data[ 'tagline' ] ) ) {
          update_post_meta( $insert_id, 'tagline', $data[ 'tagline' ] );
        }

        if( !empty( $data[ 'featured' ] ) ) {
          update_post_meta( $insert_id, 'featured', $data[ 'featured' ] );
        }

        if( !empty( $data[ 'price' ] ) ) {
          update_post_meta( $insert_id, 'price', $data[ 'price' ] );
        }

        if( !empty( $data[ 'bedrooms' ] ) ) {
          update_post_meta( $insert_id, 'bedrooms', $data[ 'bedrooms' ] );
        }

        if( !empty( $data[ 'bathrooms' ] ) ) {
          update_post_meta( $insert_id, 'bathrooms', $data[ 'bathrooms' ] );
        }

        if( !empty( $data[ 'phone_number' ] ) ) {
          update_post_meta( $insert_id, 'phone_number', $data[ 'phone_number' ] );
        }

        if( !empty( $data[ 'total_rooms' ] ) ) {
          update_post_meta( $insert_id, 'total_rooms', $data[ 'total_rooms' ] );
        }

        if( !empty( $data[ 'fees' ] ) ) {
          update_post_meta( $insert_id, 'fees', $data[ 'fees' ] );
        }

        if( !empty( $data[ 'year_built' ] ) ) {
          update_post_meta( $insert_id, 'year_built', $data[ 'year_built' ] );
        }

        if( !empty( $data[ 'living_space' ] ) ) {
          update_post_meta( $insert_id, 'living_space', $data[ 'living_space' ] );
        }

        if( !empty( $data[ 'property_feature' ] ) )
          wp_set_post_terms( $insert_id, $data[ 'property_feature' ], 'property_feature' );
        if( !empty( $data[ 'community_feature' ] ) )
          wp_set_post_terms( $insert_id, $data[ 'community_feature' ], 'community_feature' );

        update_post_meta( $insert_id, 'dummy_property', true );

        //** STEP 2. Create and Move temporary image files */

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $upload_dir = wp_upload_dir();

        $dummy_images = array(
          WPP_Path . "static/images/dummy_data/property_{$data['img_index']}_img_0.jpg",
          WPP_Path . "static/images/dummy_data/property_{$data['img_index']}_img_1.jpg",
          WPP_Path . "static/images/dummy_data/property_{$data['img_index']}_img_2.jpg"
        );

        foreach( $dummy_images as $dummy_path ) {
          if( @copy( $dummy_path, $upload_dir[ 'path' ] . "/" . basename( $dummy_path ) ) ) {
            $filename = $upload_dir[ 'path' ] . "/" . basename( $dummy_path );
            $wp_filetype = wp_check_filetype( basename( $filename ), null );

            $attach_id = wp_insert_attachment( array(
              'post_mime_type' => $wp_filetype[ 'type' ],
              'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
              'post_status' => 'inherit'
            ), $filename, $insert_id );

            $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
            wp_update_attachment_metadata( $attach_id, $attach_data );
          }
        }

        //** Last attached file is set as thumbnail */
        if( isset( $attach_id ) ) {
          update_post_meta( $insert_id, '_thumbnail_id', $attach_id );
        }

      }

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

      static public function get_settings_schema() {

        $_schema = json_decode( wp_remote_retrieve_body( wp_remote_get( WPP_API_URL_STANDARDS . '/schema' ) ), true );

        return $_schema['data'];

      }

    }
  }
}

