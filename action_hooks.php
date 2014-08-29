<?php
/**
 * WP-Property Actions and Hooks File
 *
 * Do not modify arrays found in these files, use the filters to modify them in your functions.php file
 * Sets up default settings and loads a few actions.
 *
 * Documentation: http://twincitiestech.com/plugins/wp-property/api-documentation/
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @link http://twincitiestech.com/plugins/wp-property/api-documentation/
 * @version 1.1
 * @author Andy Potanin <andy.potanin@twincitiestech.com>
 * @package WP-Property
*/


  // Load settings out of database to overwrite defaults from action_hooks.
  $wp_properties_db = get_option('wpp_settings');

  /**
   *
   * System-wide Filters and Settings
   *
   */

  // This slug will be used to display properties on the front-end.  Most likely overwriten by get_option('wpp_settings');
  $wp_properties['configuration'] = array(
    'autoload_css' => 'true',
    'automatically_insert_overview' => 'true',
    'base_slug' => 'property',
    'currency_symbol' => '$',
    'address_attribute' => 'location',
    'google_maps_localization' => 'en',
    'display_address_format' => '[city], [state]'
  );

  // Default setings for [property_overview] shortcode
  $wp_properties['configuration']['property_overview'] = array(
    'thumbnail_size' => 'tiny_thumb',
    'fancybox_preview' => 'true',
    'display_slideshow' => 'false',
    'show_children' => 'true'
  );

  $wp_properties['configuration']['single_property_view'] = array(
    'map_image_type' => 'tiny_thumb',
    'gm_zoom_level' => '13'
  );

   // Default setings for admin UI
  $wp_properties['configuration']['admin_ui'] = array(
    'overview_table_thumbnail_size' => 'tiny_thumb'
  );

  // Setup property types to be used.
  if( !isset( $wp_properties_db[ 'property_types' ] ) || !is_array( $wp_properties_db[ 'property_types' ] ) )
    $wp_properties['property_types'] =  array(
      'building' => __('Building','wpp'),
      'floorplan' => __('Floorplan','wpp'),
      'single_family_home' => __('Single Family Home','wpp')
    );


  // Setup property types to be used.
  if( !isset( $wp_properties_db[ 'property_inheritance' ] ) || !is_array( $wp_properties_db[ 'property_inheritance' ] ) )
    $wp_properties['property_inheritance'] =  array(
      'floorplan' => array("street_number", "route", 'state', 'postal_code', 'location', 'display_address', 'address_is_formatted')
    );

  // Property stats. Can be searchable, displayed as input boxes on editing page.
  if( !isset( $wp_properties_db[ 'property_stats' ] ) || !is_array($wp_properties_db[ 'property_stats' ] ) )
    $wp_properties['property_stats'] =  array(
      'location' => __('Address','wpp'),
      'price' => __('Price','wpp'),
      'bedrooms' => __('Bedrooms','wpp'),
      'bathrooms' => __('Bathrooms','wpp'),
      'deposit' => __('Deposit','wpp'),
      'area' => __('Area','wpp'),
      'phone_number' => __('Phone Number','wpp'),
    );

  // Property meta.  Typically not searchable, displayed as textarea on editing page.
  if( !isset( $wp_properties_db[ 'property_meta' ] ) || !is_array( $wp_properties_db[ 'property_meta' ] ) )
    $wp_properties['property_meta'] =  array(
      'lease_terms' => __('Lease Terms','wpp'),
      'pet_policy' => __('Pet Policy','wpp'),
      'school' => __('School','wpp'),
      'tagline' => __('Tagline','wpp')
    );

  // On property editing page - determines which fields to hide for a particular property type
  if( !isset( $wp_properties_db[ 'hidden_attributes' ] ) || !is_array( $wp_properties_db[ 'hidden_attributes' ] ) )
    $wp_properties['hidden_attributes'] = array(
      'floorplan' => array('location', 'parking', 'school'), /*  Floorplans inherit location. Parking and school are generally same for all floorplans in a building */
      'building' => array('price', 'bedrooms', 'bathrooms', 'area', 'deposit'),
      'single_family_home' => array('deposit', 'lease_terms', 'pet_policy')
    );

  // Determines property types that have addresses.
  if( !isset( $wp_properties_db[ 'location_matters' ] ) || !is_array( $wp_properties_db[ 'location_matters' ] ) )
    $wp_properties['location_matters'] = array('building', 'single_family_home');


  /**
   *
   * Searching and Filtering
   *
   */

  // Determine which property types should actually be searchable.
  if( !isset( $wp_properties_db[ 'searchable_property_types' ] ) || !is_array($wp_properties_db[ 'searchable_property_types' ] ) )
    $wp_properties['searchable_property_types'] =  array(
      'floorplan',
      'single_family_home'
    );


  // Attributes to use in searching.
  if( !isset( $wp_properties_db[ 'searchable_attributes' ] ) || !is_array($wp_properties_db[ 'searchable_attributes' ] ) )
    $wp_properties['searchable_attributes'] =  array(
      'area',
      'deposit',
      'bedrooms',
      'bathrooms',
      'city',
      'price'
    );


  // Convert phrases to searchable values.  Converts string stats into numeric values for searching and filtering.
  if( !isset( $wp_properties_db[ 'search_conversions' ] ) || !is_array( $wp_properties_db[ 'search_conversions' ] ) )
    $wp_properties['search_conversions'] =array(
      'bedrooms' => array(
        __('Studio','wpp') => '0.5'
    ));

  /**
   *
   * Display and UI related filters
   *
   */

  //* Don't load defaults if settings exist in db */
  if( !isset( $wp_properties_db[ 'image_sizes' ] ) || !is_array($wp_properties_db[ 'image_sizes' ] ) )
    $wp_properties['image_sizes'] = array(
      'map_thumb' => array('width'=> '75', 'height' => '75'),
      'tiny_thumb' => array('width'=> '100', 'height' => '100'),
      'sidebar_wide' => array('width'=> '195', 'height' => '130'),
      'slideshow' => array('width'=> '640', 'height' => '235')
    );

  $wp_properties['default_coords']['latitude'] = '57.7973333';
  $wp_properties['default_coords']['longitude'] = '12.0502107';

  //* Geo type attributes are predefined and should not be editable on property adding/updating */
  $wp_properties['geo_type_attributes'] = array(
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

  // Image URLs.
  $wp_properties['images']['map_icon_shadow'] = WPP_URL . "images/map_icon_shadow.png";

  if( !isset( $wp_properties_db['configuration']['google_maps']['infobox_attributes'] ) || !is_array($wp_properties_db['configuration']['google_maps']['infobox_attributes'] ) )
    $wp_properties['configuration']['google_maps']['infobox_attributes'] = array(
      'bedrooms',
      'bathrooms',
      'price'
    );

  $wp_properties['configuration']['google_maps']['infobox_settings'] = array(
    'show_direction_link' => true,
    'show_property_title' => true
    );

  // Default attribute label descriptions for the back-end
  $wp_properties['descriptions'] = array(
    'descriptions' => array(
      'property_type' => __('The property type will determine the layout.','wpp'),
      'custom_attribute_overview' => __('Customize what appears in search results in the attribute section.  For example: 1bed, 2baths, area varies slightly.','wpp'),
      'tagline' => __('Will appear on overview pages and on top of every listing page.','wpp'))
  );

  // Overwrite $wp_properties with database setting
  $wp_properties = UD_API::array_merge_recursive_distinct($wp_properties, $wp_properties_db);
  
  
