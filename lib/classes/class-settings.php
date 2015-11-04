<?php
/**
 * Settings
 *
 * @since 2.0.0
 * @todo move default data to schemas. peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Settings' ) ) {

    class Settings extends \UsabilityDynamics\Settings {

      /**
       * Constructor
       * Sets default data.
       *
       *
       * @todo For the love of god, only apply the defaults on installation. - potanin@UD
       * @param bool $args
       */
      public function __construct( $args = false ) {
        global $wp_properties;
        
        //** STEP 1. Default */
        
        parent::__construct( $args );
        
        //** STEP 2. */
        
        $data = array();
         
        //** This slug will be used to display properties on the front-end.  Most likely overwriten by get_option('wpp_settings'); */
        $data['configuration'] = array(
          'autoload_css' => 'true',
          'automatically_insert_overview' => 'true',
          'base_slug' => 'property',
          'currency_symbol' => '$',
          'address_attribute' => 'location',
          'google_maps_localization' => 'en',
          'display_address_format' => '[city], [state]'
        );

        //** Default setings for [property_overview] shortcode */
        $data['configuration']['property_overview'] = array(
          'thumbnail_size' => 'tiny_thumb',
          'fancybox_preview' => 'true',
          'display_slideshow' => 'false',
          'show_children' => 'true',
          'pagination_type' => 'slider' // @todo: change to 'numeric' when compatibility will be added to Madison theme. peshkov@UD
        );

        $data['configuration']['single_property_view'] = array(
          'map_image_type' => 'tiny_thumb',
          'gm_zoom_level' => '13'
        );

        //** Default setings for admin UI */
        $data['configuration']['admin_ui'] = array(
          'overview_table_thumbnail_size' => 'tiny_thumb'
        );
        
        $data['default_coords']['latitude'] = '57.7973333';
        $data['default_coords']['longitude'] = '12.0502107';

        //** Geo type attributes are predefined and should not be editable on property adding/updating */
        // @notice All these fields are automatically added as post_meta on revalidation.
        $data['geo_type_attributes'] = array(
          'formatted_address',
          'street_number',
          'route',
          'district',
          'city',
          'county',
          'state',
          'state_code',
          'country',
          'country_code',
          'postal_code'
        );

        //** Image URLs. */
        $data['images']['map_icon_shadow'] = WPP_URL . "images/map_icon_shadow.png";
        
        $data['configuration']['google_maps']['infobox_settings'] = array(
          'show_direction_link' => true,
          'show_property_title' => true
        );

        //** Default attribute label descriptions for the back-end */
        $data[ 'descriptions' ] = array(
          'descriptions' => array(
            'property_type' => sprintf( __('The %s type will determine the layout.',ud_get_wp_property()->domain), \WPP_F::property_label() ),
            'custom_attribute_overview' => __('Customize what appears in search results in the attribute section.  For example: 1bed, 2baths, area varies slightly.',ud_get_wp_property()->domain),
            'tagline' => __('Will appear on overview pages and on top of every listing page.',ud_get_wp_property()->domain)
          )
        );

        $_stored_settings = $this->get();

        //** Merge with default data. */
        $this->set( \UsabilityDynamics\Utility::extend( $data, $this->get() ) );

        // Check if settings have or have been upated. (we determine if configuration is good)
        // @todo Add a better _version_ check.
        if( isset( $_stored_settings[ '_updated' ] ) && isset( $_stored_settings[ 'version' ] ) && $_stored_settings[ 'version' ] === '2.0.0' ) {
          return $wp_properties = $this->get();
        }

        // Continue on to load/enforce defaults.

        //** STEP 3. */
        
        //** Setup default property types to be used. */
        $d = $this->get( 'property_types', false );

        // Should only be set on install, not added on every request. These literally can not be removed from settings... -potanin@UD
        // It is adding these defaults only if types are empty (install) - korotkov@UD
        if( empty( $d ) || !is_array( $d ) ) {
          $this->set( 'property_types', array(
            'building' => __( 'Building', ud_get_wp_property()->domain ),
            'floorplan' => __( 'Floorplan', ud_get_wp_property()->domain ),
            'single_family_home' => __( 'Single Family Home', ud_get_wp_property()->domain )
          ) );
        }

        //** Setup property types to be used. */
        $d = !$this->get( 'property_inheritance', false );
        if( !$d || !is_array( $d ) ) {
          $this->set( 'property_inheritance', array(
            'floorplan' => array( 'street_number', 'route', 'state', 'postal_code', 'location', 'display_address', 'address_is_formatted' )
          ) );
        }
          
        //** Property stats. Can be searchable, displayed as input boxes on editing page. */
        $d = $this->get( 'property_stats', false );
        if( !$d || !is_array( $d ) ) {
          $this->set( 'property_stats', array(
            'location' => __('Address',ud_get_wp_property()->domain),
            'price' => __('Price',ud_get_wp_property()->domain),
            'deposit' => __('Deposit',ud_get_wp_property()->domain),
            'area' => __('Area',ud_get_wp_property()->domain),
            'phone_number' => __('Phone Number',ud_get_wp_property()->domain),
          ) );
        }

        //** Property meta.  Typically not searchable, displayed as textarea on editing page. */
        $d = $this->get( 'property_meta', false );
        if( !$d || !is_array( $d ) ) {
          $this->set( 'property_meta', array(
            'lease_terms' => __('Lease Terms',ud_get_wp_property()->domain),
            'pet_policy' => __('Pet Policy',ud_get_wp_property()->domain),
            'school' => __('School',ud_get_wp_property()->domain),
            'tagline' => __('Tagline',ud_get_wp_property()->domain)
          ) );
        }

        //** On property editing page - determines which fields to hide for a particular property type */
        $d = $this->get( 'hidden_attributes', false );

        if( !is_array( $d ) ) {
          $this->set( 'hidden_attributes', array(
            'floorplan' => array('location', 'parking', 'school'), /*  Floorplans inherit location. Parking and school are generally same for all floorplans in a building */
            'building' => array('price', 'bedrooms', 'bathrooms', 'area', 'deposit'),
            'single_family_home' => array('deposit', 'lease_terms', 'pet_policy')
          ) );
        }

        //** Determines property types that have addresses. */
        $d = $this->get( 'location_matters', false );
        if( !is_array( $d ) ) {
          $this->set( 'location_matters', array(
            'building',
            'single_family_home' 
          ) );
        }

        //** Determine which property types should actually be searchable. */
        $d = $this->get( 'searchable_property_types', false );
        if( !is_array( $d ) ) {
          $this->set( 'searchable_property_types', array(
            'floorplan',
            'single_family_home'
          ) );
        }
        
        //** Attributes to use in searching. */
        $d = $this->get( 'searchable_attributes', false );
        if( !is_array( $d ) ) {
          $this->set( 'searchable_attributes', array(
            'area',
            'deposit',
            'bedrooms',
            'bathrooms',
            'city',
            'price'
          ) );
        }

        //** Convert phrases to searchable values.  Converts string stats into numeric values for searching and filtering. */
        $d = $this->get( 'search_conversions', false );
        if( !is_array( $d ) ) {
          $this->set( 'search_conversions', array(
            'bedrooms' => array( __( 'Studio', ud_get_wp_property()->domain ) => '0.5' )
          ) );
        }

        //** Don't load defaults if settings exist in db */
        $d = $this->get( 'image_sizes', false );
        if( !is_array( $d ) ) {
          $this->set( 'image_sizes', array(
            'map_thumb' => array('width'=> '75', 'height' => '75'),
            'tiny_thumb' => array('width'=> '100', 'height' => '100'),
            'sidebar_wide' => array('width'=> '195', 'height' => '130'),
            'slideshow' => array('width'=> '640', 'height' => '235')
          ) );
        }

        $d = $this->get( 'configuration.google_maps.infobox_attributes', false );
        if( !is_array( $d ) ) {
          $this->set( 'configuration.google_maps.infobox_attributes', array(
            'bedrooms',
            'bathrooms',
            'price'
          ) );
        }
        
        //** STEP 4. */

        $wp_properties = $this->get();

        
      }

    }

  }

}
