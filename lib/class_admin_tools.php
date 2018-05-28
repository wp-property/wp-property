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
      $data[ 'Google Map API key' ][ ] = '<h3>' . __( 'Setting up Google Map API keys', ud_get_wp_property()->domain ) . '</h3>';
      $data[ 'Google Map API key' ][ ] = '<p>' . __( "If your client application does not use OAuth 2.0, then it must include an API key when it calls an API that's enabled within a Google Cloud Platform project. The application passes this key into all API requests as a key=API_key parameter.", ud_get_wp_property()->domain ) . '</p>';
      $data[ 'Google Map API key' ][ ] = '<p>' . __( "To create your application's API key:", ud_get_wp_property()->domain ) . '</p>';

      $data[ 'Google Map API key' ][ ] = '<ol>';
      $data[ 'Google Map API key' ][ ] = '<li>' . sprintf(__( "Go to the <a href='%s'>API Console</a>.", ud_get_wp_property()->domain ), 'https://console.developers.google.com/') . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "From the projects list, select a project or create a new one.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . sprintf(__( "If the API Manager page isn't already open, open the menu %s and select <b>API Manager</b>.", ud_get_wp_property()->domain ), "&nbsp;<img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAKCAYAAABrGwT5AAAABHNCSVQICAgIfAhkiAAAADZJREFUKJFjrKio+M9AJmD59+/fGXI1DxxgLCsre0euZiZquoR+gLGsrOw0uZpZmJiYTMjVDAAtHArypQrPMgAAAABJRU5ErkJggg==' height='10'>&nbsp;") . '</li>';

      $data[ 'Google Map API key' ][ ] = '<li>' . __( "From Dashboard click <b>Enable API</b> button at top or click on <b>Library</b> from left menu.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "Click <b>Google Maps JavaScript API</b> and <b>Google Maps Geolocation API </b> from <b>Google Maps APIs</b> one by one.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "To enable the api click <b>Enable</b>. Do this for both <b>Google Maps JavaScript API</b> and <b>Google Maps Geolocation API </b>,", ud_get_wp_property()->domain ) . '</li>';

      if(function_exists('ud_get_wpp_pdf'))
        $data[ 'Google Map API key' ][ ] = '<li>' . __( "Also enable <b>Google Static Maps API</b>. It's required for <b>WP-Property: PDF Flyer</b> to work.", ud_get_wp_property()->domain ) . '</li>';

      
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "On the left, choose <b>Credentials</b>.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "Click <b>Create credentials</b> and then select API key.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "Copy API key from <b>Your API key</b> field.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '</ol>';

      $data[ 'Google Map API key' ][ ] = '<p>You can use this key for both Browser Key and Server Key, or follow the below step to secure your key.</p>';

      $data[ 'Google Map API key' ][ ] = '<h4>Optional but highly recommended (To to prevent unauthorized use in production):</h4>';

      $data[ 'Google Map API key' ][ ] = '<ol start="7">';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "Click <b>Restrict key</b> button.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "Optional: You can give your API key a name in name field. Useful when you have lots of API keys.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "Select <b>HTTP referrers (web sites)</b> for Browser Key and <b>IP addresses (web servers, cron jobs, etc.)</b> for Server Key.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "Add your web site address (for browser key) or server IP addresses (for server key) to next input.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "Click save.", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '<li>' . __( "You have do whole process couple of time. Once for <b>Browser key</b> and once for <b>Server key</b>", ud_get_wp_property()->domain ) . '</li>';
      $data[ 'Google Map API key' ][ ] = '</ol>';

      $data[ 'Google Map API key' ][ ] = '<p>' . sprintf(__( 'For more info <a target="_blank" href="%s">click here</a>', ud_get_wp_property()->domain ), "https://developers.google.com/maps/documentation/geocoding/get-api-key") . '</p>';
      $data[ 'Google Map API key' ][ ] = '<p>' . sprintf(__( 'You can also read <a target="_blank" href="%s">Best practices for securely using API keys</a>', ud_get_wp_property()->domain ), "https://support.google.com/googleapi/answer/6310037") . '</p>';


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
      global $wp_properties;

      $developer_tabs = array(
        'attributes' => array(
          'label' => __( 'Attributes', ud_get_wp_property()->domain ),
          'template' => ud_get_wp_property()->path( 'static/views/admin/settings-developer-attributes.php', 'dir' ),
          'order' => 10
        ),
        'types' => array(
          'label' => __( 'Types', ud_get_wp_property()->domain ),
          'template' => ud_get_wp_property()->path( 'static/views/admin/settings-developer-types.php', 'dir' ),
          'order' => 30
        ),
      );

      $tabs = apply_filters( 'wpp::settings_developer::tabs',  $developer_tabs);

      /* Sort Tabs by 'order' */
      uasort( $tabs, function( $a, $b ) {
        if ($a['order'] == $b['order']) {
          return 0;
        }
        return ($a['order'] > $b['order']) ? 1 : -1;
      } );

      $template = ud_get_wp_property()->path( 'static/views/admin/settings-developer.php', 'dir' );
      include( $template );

    }

  }

}
