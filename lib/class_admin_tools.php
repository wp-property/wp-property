<?php
/*
Name: Admin Tools
Feature ID: 1
Minimum Core Version: 1.36.0
Version: 3.5.2
Description: Tools for developing themes and extensions for WP-Property.
Class: class_admin_tools
*/

add_action( 'wpp_init', array( 'class_admin_tools', 'init' ) );
add_action( 'wpp_pre_init', array( 'class_admin_tools', 'pre_init' ) );

if( !class_exists( 'class_admin_tools' ) ) {

  /**
   * class_admin_tools Class
   *
   * Contains administrative functions
   *
   * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
   *
   * @version 1.0
   * @author Andy Potanin <andy.potanin@twincitiestech.com>
   * @package WP-Property
   * @subpackage Admin Functions
   */
  class class_admin_tools {

    /*
     * (custom) Capability to manage the current feature
     */
    static protected $capability = "manage_wpp_admintools";

    /**
     * Special functions that must be called prior to init
     *
     */
    static function pre_init() {
      /* Add capability */
      add_filter( 'wpp_capabilities', array( 'class_admin_tools', "add_capability" ) );
    }

    /*
     * Apply feature's Hooks and other functionality
     */
    static function init() {

      if( current_user_can( self::$capability ) ) {
        //** Add Inquiry page to Property Settings page array */
        add_filter( 'wpp_settings_nav', array( 'class_admin_tools', 'settings_nav' ) );
        //** Add Settings Page */
        add_action( 'wpp_settings_content_admin_tools', array( 'class_admin_tools', 'settings_page' ) );
        //** Contextual Help */
        add_action( 'property_page_property_settings_help', array( 'class_admin_tools', 'wpp_contextual_help' ) );
      }

    }

    /*
     * Adds Custom capability to the current premium feature
     */
    static function add_capability( $capabilities ) {

      $capabilities[ self::$capability ] = __( 'Manage Admin Tools', ud_get_wp_property()->domain );

      return $capabilities;
    }

    /**
     * Add Contextual help item
     *
     * @param type $data
     *
     * @return string
     * @author korotkov@ud
     */
    static function wpp_contextual_help( $data ) {

      $data[ 'Developer' ][ ] = '<h3>' . __( 'Developer', ud_get_wp_property()->domain ) . '</h3>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'The <b>slug</b> is automatically created from the title and is used in the back-end.  It is also used for template selection, example: floorplan will look for a template called property-floorplan.php in your theme folder, or default to property.php if nothing is found.', ud_get_wp_property()->domain ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'If <b>Searchable</b> is checked then the property will be loaded for search, and available on the property search widget.', ud_get_wp_property()->domain ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'If <b>Location Matters</b> is checked, then an address field will be displayed for the property, and validated against Google Maps API.  Additionally, the property will be displayed on the SuperMap, if the feature is installed.', ud_get_wp_property()->domain ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . sprintf( __( '<b>Hidden Attributes</b> determine which attributes are not applicable to the given %s type, and will be grayed out in the back-end.', ud_get_wp_property()->domain ), WPP_F::property_label() ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( '<b>Inheritance</b> determines which attributes should be automatically inherited from the parent property', ud_get_wp_property()->domain ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'Property attributes are meant to be short entries that can be searchable, on the back-end attributes will be displayed as single-line input boxes. On the front-end they are displayed using a definitions list.', ud_get_wp_property()->domain ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'Making an attribute as "searchable" will list it as one of the searchable options in the Property Search widget settings.', ud_get_wp_property()->domain ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( 'Be advised, attributes added via add_filter() function supercede the settings on this page.', ud_get_wp_property()->domain ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( '<b>Search Input:</b> Select and input type and enter comma-separated values that you would like to be used in property search, on the front-end.', ud_get_wp_property()->domain ) . '</p>';
      $data[ 'Developer' ][ ] = '<p>' . __( '<b>Data Entry:</b> Enter comma-separated values that you would like to use on the back-end when editing properties.', ud_get_wp_property()->domain ) . '</p>';


      // Google map api key
      $data[ 'Google Map API key' ][ ] = '<h3>' . __( 'Google Map API key', ud_get_wp_property()->domain ) . '</h3>';
      $data[ 'Google Map API key' ][ ] = '<p>' . __( 'Google Maps has its own limit of usage. Google map API key is needed for map in frontend (browser key) and to validate address in backend (server key).', ud_get_wp_property()->domain ) . '</p>';
      // Browser key
      $data[ 'Google Map API key' ][ ] = '<h4>' . __( 'Browser Key', ud_get_wp_property()->domain ) . '</h4>';
      $data[ 'Google Map API key' ][ ] = '<ol>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'Go to the <a target="_blank" href="https://console.developers.google.com/flows/enableapi?apiid=maps_backend,geocoding_backend,directions_backend,distance_matrix_backend,elevation_backend,places_backend&amp;keyType=CLIENT_SIDE&amp;reusekey=true" target="_blank">Google API Console</a>.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'Create or select a project.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'Click <b>Continue</b> to enable the API and any related services.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'On the<b> Credentials</b> page, get a <b>Browser key</b> (and set the API Credentials).<br>Note: If you have an existing <b>Browser key</b>, you may use that key.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'To prevent quota theft, <a target="_blank" href="https://support.google.com/cloud/answer/6310037">secure your API key following these best practices</a>.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( '(Optional) Enable billing. See <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/usage">Usage Limits</a> for more information.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'For more info <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">click here</a>', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '</ol>';

      // Server key
      $data[ 'Google Map API key' ][ ] = '<h4>' . __( 'Server Key', ud_get_wp_property()->domain ) . '</h4>';
      $data[ 'Google Map API key' ][ ] = '<ol>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'Go to the <a target="_blank" href="https://console.developers.google.com/flows/enableapi?apiid=geocoding_backend&amp;keyType=SERVER_SIDE&amp;reusekey=true">Google API Console</a>.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'Create or select a project.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'Click <b>Continue</b> to Enable the API.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'On the <b>Credentials</b> page, get a <b>Server key</b> (and set the API Credentials).<br>Note: If you have an existing <b>Server key</b>, you may use that key.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'To prevent quota theft, <a target="_blank" href="https://support.google.com/cloud/answer/6310037">secure your API key following these best practices</a>.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( '(Optional) Enable billing. See <a target="_blank" href="https://developers.google.com/maps/documentation/geocoding/usage-limits">Usage Limits</a> for more information.', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( 'For more info <a target="_blank" href="https://developers.google.com/maps/documentation/geocoding/get-api-key">click here</a>', ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '</ol>';


      return $data;

    }

    /**
     * Adds admin tools menu to settings page navigation
     *
     * @version 1.0
     * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
     */
    static function settings_nav( $tabs ) {

      $tabs[ 'admin_tools' ] = array(
        'slug'  => 'admin_tools',
        'title' => __( 'Developer', ud_get_wp_property()->domain )
      );

      return $tabs;
    }

    /**
     * Displays advanced management page
     *
     * @version 2.0
     */
    static function settings_page() {

      $tabs = apply_filters( 'wpp::settings_developer::tabs', array(
        'attributes' => array(
          'label' => __( 'Attributes', ud_get_wp_property()->domain ),
          'template' => ud_get_wp_property()->path( 'static/views/admin/settings-developer-attributes.php', 'dir' ),
          'order' => 10
        ),
        'meta' => array(
          'label' => __( 'Meta', ud_get_wp_property()->domain ),
          'template' => ud_get_wp_property()->path( 'static/views/admin/settings-developer-meta.php', 'dir' ),
          'order' => 20
        ),
        'types' => array(
          'label' => __( 'Types', ud_get_wp_property()->domain ),
          'template' => ud_get_wp_property()->path( 'static/views/admin/settings-developer-types.php', 'dir' ),
          'order' => 30
        ),
      ) );

      /* Sort Tabs by 'order' */
      uasort( $tabs, create_function( '$a,$b', 'if ($a[\'order\'] == $b[\'order\']) { return 0; } return ($a[\'order\'] > $b[\'order\']) ? 1 : -1;' ) );

      $template = ud_get_wp_property()->path( 'static/views/admin/settings-developer.php', 'dir' );
      include( $template );

    }

  }

}
