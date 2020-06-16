<?php
/**
 * Bootstrap
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\WS_Bootstrap' ) ) {

    final class WS_Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {
      
      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type \UsabilityDynamics\WPP\WS_Bootstrap object
       */
      protected static $instance = null;
      
      /**
       * Instantaite class.
       */
      public function init() {

        $this->define_settings();

        /**
         * WP-Property 'Walk Score' attribute Handler.
         * It adds attribute if it does not exists and handles some stuff related to it.
         *
         * Note! Attribute is required and CAN NOT BE REMOVED!!
         */
        new WS_Attribute();

        /**
         * May be load Shortcodes
         */
        if( class_exists( '\UsabilityDynamics\Shortcode\Shortcode' ) ) {
          $this->load_files( $this->path('lib/shortcodes', 'dir') );
        }

        /**
         * May be load Widgets
         */
        $this->load_files( $this->path('lib/widgets', 'dir') );

        /** Init our AJAX Handler */
        new WS_Ajax();

        /**
         * Load Admin UI
         */
        if( is_admin() ) {
          new WS_Admin();
        }

        add_action( 'save_property', array( $this, 'save_property' ), 99, 2 );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        
      }

      /**
       * Define Plugin Settings
       *
       */
      private function define_settings() {
        $this->settings = new \UsabilityDynamics\Settings( array(
          'key'  => 'wpp_walkscore_settings',
          'store'  => 'options',
          'data' => array(
            'name' => $this->name,
            'version' => $this->args[ 'version' ],
            'domain' => $this->domain,
          )
        ) );
      }

      /**
       * May be do request to Walk Score to get Score data
       * on property saving
       *
       */
      public function save_property( $post_id, $args ) {

        /* Even do not continue if API key is not provided. */
        $api_key = $this->get( 'config.api.key' );
        if( empty( $api_key ) ) {
          return;
        }

        /* Determine if address attribute exists */
        $attribute = ud_get_wp_property( 'configuration.address_attribute' );
        if( empty( $attribute ) ) {
          return;
        }

        /**
         * We break, if we coordinates were not been changed
         * and Walk Score already exists.
         */
        $walkscore = get_post_meta( $post_id, '_ws_walkscore', true );
        $walkscore_link = get_post_meta( $post_id, '_ws_link', true );
        if(
          isset( $args[ 'update_data' ][ 'latitude' ] ) &&
          isset( $args[ 'update_data' ][ 'longitude' ] ) &&
          isset( $args[ 'geo_data' ][ 'old_coordinates' ][ 'lat' ] ) &&
          isset( $args[ 'geo_data' ][ 'old_coordinates' ][ 'lng' ] ) &&
          $args[ 'update_data' ][ 'latitude' ] == $args[ 'geo_data' ][ 'old_coordinates' ][ 'lat' ] &&
          $args[ 'update_data' ][ 'longitude' ] == $args[ 'geo_data' ][ 'old_coordinates' ][ 'lng' ] &&
          !empty( $walkscore ) &&
          !empty( $walkscore_link )
        ) {
          return;
        }

        /* Do our API request to WalkScore */
        $response = WS_API::get_score( array(
          'address' => get_post_meta( $post_id, $attribute, true ),
          'lat' => get_post_meta( $post_id, 'latitude', true ),
          'lon' => get_post_meta( $post_id, 'longitude', true )
        ), $post_id, true );

        /** // Response Example
        $response = array(
          'status' => '1',
          'walkscore' => '63',
          'description' => "walker's paradise",
          'updated' => '2009-12-25 03:40:16.006257',
          'logo_url' => 'https://cdn.walk.sc/images/api-logo.png',
          'more_info_icon' => 'https://cdn.walk.sc/images/api-more-info.gif',
          'ws_link' => 'http://www.walkscore.com/score/1119-8th-Avenue-Seattle-WA-98101/lat=47.6085/lng=-122.3295/?utm_source=myrealtysite.com&utm_medium=ws_api&utm_campaign=ws_api',
          'help_link' => 'https://www.redfin.com/how-walk-score-works',
          'snapped_lat' => '47.6085',
          'snapped_lon' => '-122.3295',
        );
        // */

        if( !empty( $response ) ) {
          update_post_meta( $post_id, '_ws_walkscore', $response[ 'walkscore' ] );
          update_post_meta( $post_id, '_ws_walkscore_response', $response );
        } else {
          WS_API::store_error_log( $post_id );
        }

      }

      /**
       * Admin Notice
       */
      public function admin_notices() {
        global $post;
        if( !empty( $post ) && is_object( $post ) && !empty( $post->ID ) && $post->post_type == 'property' ) {
          $log = WS_API::get_error_log( $post->ID );
          if( !empty( $log ) ) {
            $log = implode( '<br/>', (array)$log );
            echo '<div class="error updated" style="padding: 10px;">';
            printf( __( 'Error occurred on trying to get Walk Score information for current property: %s' ), $log );
            echo '</div>';
          }
          WS_API::clear_error_log( $post->ID );
        }
      }

      /**
       * Includes all PHP files from specific folder
       *
       * @param string $dir Directory's path
       * @author peshkov@UD
       */
      public function load_files($dir = '') {
        $dir = trailingslashit($dir);
        if (!empty($dir) && is_dir($dir)) {
          if ($dh = opendir($dir)) {
            while (( $file = readdir($dh) ) !== false) {
              if (!in_array($file, array('.', '..')) && is_file($dir . $file) && 'php' == pathinfo($dir . $file, PATHINFO_EXTENSION)) {
                include_once( $dir . $file );
              }
            }
            closedir($dh);
          }
        }
      }

      /**
       * Plugin Activation
       *
       */
      public function activate() {
        //** flush Object Cache */
        wp_cache_flush();
        //** set transient to flush WP-Property cache */
        set_transient( 'wpp_cache_flush', time() );
      }

      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {
        //** flush Object Cache */
        wp_cache_flush();
      }

      /**
       * Return localization's list.
       *
       * Example:
       * If schema in composer.json contains l10n.{key} values:
       *
       * { 'config': 'l10n.hello_world' }
       *
       * the current function should return something below:
       *
       * return array(
       *   'hello_world' => __( 'Hello World', $this->domain ),
       * );
       *
       * @author peshkov@UD
       * @return array
       */
      public function get_localization() {
        return apply_filters( 'wpp::walkscore::localization', array(
          'walkscore' => __( 'Walk Score', $this->domain ),
          'settings_page_title' => __( 'WP-Property: Walk Score Settings', $this->domain ),
          'general' => __( 'General', $this->domain ),
          'general_settings' => sprintf( __( 'To start using %sWalk Score%s on your site you have to setup the options below at first.', $this->domain ), '<a href="https://www.walkscore.com/professional/" target="_blank">', '</a>' ),
          'map_api_settings' => __( 'Walk Score ID', $this->domain ),
          'desc_score_api_settings' => sprintf( __( 'The following API adds ability to:%s %s %s <strong>Be aware</strong>, that the current API is supported in the United States, Canada, Australia, and New Zealand.', $this->domain ), '</p>', $this->get_score_api_features_list(), '<br/><p>' ),
          'map_api_key' => __( 'ID Key', $this->domain ),
          'desc_map_api_key' => sprintf( __( 'Walk Score\'s %sNeighborhood Map%s requires Walk Score ID to start. %sGet your ID Key%s', $this->domain ), '<a href="https://www.walkscore.com/professional/neighborhood-map.php" target="_blank">', '</a>', '<a href="https://www.walkscore.com/professional/sign-up.php" target="_blank">', '</a>' ),
          'score_api_settings' => __( 'Walk Score and Public Transit API', $this->domain ),
          'desc_map_api_settings' => sprintf( __( 'You have to setup Walk Score ID to have your %s shortcode and <i>Walk Score Neighborhood Map</i> widget working.', $this->domain ), '<code>[property_walkscore_neighborhood]</code>' ),
          'score_api_key' => __( 'API Key', $this->domain ),
          'desc_score_api_key' => sprintf( __( 'Walk Score requires API Key to start. %sGet your API Key%s. <strong>Note</strong>, Walk Score ID and API have very similar, but <strong>different Sign Up</strong> pages. Be careful!', $this->domain ), '<a href="https://www.walkscore.com/professional/api-sign-up.php" target="_blank">', '</a>' ),
          'neighborhood_map' => sprintf( __( 'Neighborhood Map', $this->domain ) ),
          'desc_neighborhood_map' => sprintf( __( 'Setup your %s shortcode advanced settings below. The current shortcode renders %sNeighborhood Map%s.<br/>The settings below will be used as default ones for all your shortcodes.</p><p>By default, shortcode uses current property for showing map. But if you want to show another property or use shortcode on non-property page you can use attribute <strong>property_id</strong>. Example: <code>[property_walkscore_neighborhood property_id=777]</code>.<br/>Also, you are able to use custom coordinates instead of property_id. Example: <code>[property_walkscore_neighborhood ws_lat="37.720309" ws_lon="-122.390668"]</code></p><p><strong>Note</strong>, you can overwrite any option manually in your shortcode. See shortcode\'s available attribute under option you want to change. Example: %s</p><p>Do you want to use widget instead of the current shortcode? Well, just go to %sWidgets%s settings and setup your <strong>Walk Score Neighborhood Map</strong> widget there.</p><p>Need more information? You can find Neighborhood Map\'s API Documentation %shere%s.</p><p><strong>Attention!</strong> To prevent issues, you must not use more than one Neighborhood Map on page!', $this->domain ), '<code>[property_walkscore_neighborhood]</code>', '<a href="https://www.walkscore.com/professional/neighborhood-map.php" target="_blank">', '</a>', '<code>[property_walkscore_neighborhood ws_width=600 ws_height=300 ws_layout=vertical]</code>', '<a href="' . admin_url( 'widgets.php' ) . '" target="_blank">', '</a>', '<a href="https://www.walkscore.com/professional/neighborhood-map-docs.php" target="_blank">', '</a>' ),
          'layout' => sprintf( __( 'Layout', $this->domain ) ),
          'width' => sprintf( __( 'Width', $this->domain ) ),
          'desc_width' => sprintf( __( '%s - shortcode\'s attribute.<br/>The pixel width of the Neighborhood Map. For responsive design or liquid layouts, you can use value %s.', $this->domain ), '<code>ws_width</code>', '<strong>100&#37;</strong>' ),
          'height' => sprintf( __( 'Height', $this->domain ) ),
          'desc_height' => sprintf( __( '%s - shortcode\'s attribute.<br/>The pixel height of the Neighborhood Map.', $this->domain ), '<code>ws_height</code>' ),
          'desc_layout' => sprintf( __( '%s - shortcode\'s attribute.<br/>The Neighborhood Map has two layout modes: "horizontal" or "vertical". Vertical layouts (ws_layout = "vertical") will work best in most responsive design situations.<br/>If you use a large map in a layout that includes some wider aspect ratios you can also try "none" value which does automatic layout switching based on the dimensions.', $this->domain ), '<code>ws_layout</code>' ),
          'distance_units' => sprintf( __( 'Distance Units', $this->domain ) ),
          'desc_distance_units' => sprintf( __( '%s - shortcode\'s attribute.<br/>Setup the distance units (km or mi).', $this->domain ), '<code>ws_distance_units</code>' ),
          'commute_report' => sprintf( __( 'Commute Report', $this->domain ) ),
          'commute' => sprintf( __( 'Commute', $this->domain ) ),
          'desc_commute' => sprintf( __( '%s - shortcode\'s attribute.<br/>Show commute report on Neighborhood Map that displays drive, transit, walk, and bike times. Example: %s', $this->domain ), '<code>ws_commute</code>', '<code>[property_walkscore_neighborhood ws_commute="true"]</code>' ),
          'commute_address' => sprintf( __( 'Commute Address', $this->domain ) ),
          'desc_commute_address' => sprintf( __( '%s - shortcode\'s attribute.<br/>Optional. Specify a pre-determined destination address for the commute. Example: %s', $this->domain ), '<code>ws_commute_address</code>', '<code>[property_walkscore_neighborhood ws_commute_address="3503 NE 45th St Seattle"]</code>' ),
          'default_view' => sprintf( __( 'Default View', $this->domain ) ),
          'desc_default_view' => sprintf( __( '%s - shortcode\'s attribute.<br/>Set the initial tile view.', $this->domain ), '<code>ws_default_view</code>' ),
          'industry_specific_amenity_categories' => sprintf( __( 'Industry-Specific Amenity Categories', $this->domain ) ),
          'industry_type' => sprintf( __( 'Industry Type', $this->domain ) ),
          'desc_industry_type' => sprintf( __( '%s - shortcode\'s attribute.<br/>Choose which set of amenities to show.', $this->domain ), '<code>ws_industry_type</code>' ),
          'map_modules' => sprintf( __( 'Map Modules', $this->domain ) ),
          'desc_map_modules' => sprintf( __( '%s - shortcode\'s attribute.<br/>Choose which map types to enable from among the following using a comma separated list, or set to "all", "default" or "none". %s Example: %s', $this->domain ), '<code>ws_map_modules</code>', '</p>' . $this->get_available_map_modules() . '<p class="description">', '<code>[property_walkscore_neighborhood ws_map_modules="street_view,walkability"]</code>' ),
          'base_map' => sprintf( __( 'Base Map', $this->domain ) ),
          'desc_base_map' => sprintf( __( '%s - shortcode\'s attribute.<br/>Choose which map type is shown on load. Default is \'google_map\'. If the selected module is not available for a location, the first module menu option is enabled.', $this->domain ), '<code>ws_base_map</code>' ),
          'for_premium_users' => sprintf( __( 'The following parameters are for %sWalk Score Premium%s customers.', $this->domain ), '<a href="https://www.walkscore.com/professional/pricing.php" target="_blank">', '</a>' ),
          'transit_score_and_public_transit' => sprintf( __( 'Transit Score and Public Transit', $this->domain ) ),
          'transit_score' => sprintf( __( 'Transit Score', $this->domain ) ),
          'desc_transit_score' => sprintf( __( '%s - shortcode\'s attribute.<br/>Display Transit Score if available, as well as a summary of nearby stops and routes.', $this->domain ), '<code>ws_transit_score</code>' ),
          'public_transit' => sprintf( __( 'Public Transit', $this->domain ) ),
          'desc_public_transit' => sprintf( __( '%s - shortcode\'s attribute.<br/>Show nearby transit stops and routes and a description of the number of nearby routes. Note: ws_transit_score should be used for most sites. Sites that want to show public transit but not Transit Score can use ws_public_transit.', $this->domain ), '<code>ws_public_transit</code>' ),
          'amenity_reviews' => sprintf( __( 'Amenity Reviews', $this->domain ) ),
          'show_reviews' => sprintf( __( 'Show Reviews', $this->domain ) ),
          'desc_show_reviews' => sprintf( __( '%s - shortcode\'s attribute.<br/>how thumbnail images and a link to reviews in the info bubble when available.', $this->domain ), '<code>ws_show_reviews</code>' ),
          'map_icon' => sprintf( __( 'Map Icon', $this->domain ) ),
          'map_icon_type' => sprintf( __( 'Map Icon Type', $this->domain ) ),
          'desc_map_icon_type' => sprintf( __( '%s - shortcode\'s attribute.<br/>Choose which icon to use at the center of the map.', $this->domain ), '<code>ws_map_icon_type</code>' ),
          'custom_pin' => sprintf( __( 'Custom Pin', $this->domain ) ),
          'desc_custom_pin' => sprintf( __( '%s - shortcode\'s attribute.<br/>Provide a URL for a custom icon. Must be a .png file. Set to "none" to hide the map icon completely.', $this->domain ), '<code>ws_custom_pin</code>' ),
          'map_view' => sprintf( __( 'Map View', $this->domain ) ),
          'map_zoom' => sprintf( __( 'Map Zoom', $this->domain ) ),
          'desc_map_zoom' => sprintf( __( '%s - shortcode\'s attribute.<br/>Set an initial zoom-level for the map. Example: %s', $this->domain ), '<code>ws_map_zoom</code>', '<code>[property_walkscore_neighborhood ws_map_zoom="10"]</code>' ),
          'colors_and_styling' => sprintf( __( 'Colors and Styling', $this->domain ) ),
          'background_color' => sprintf( __( 'Background Color', $this->domain ) ),
          'desc_background_color' => sprintf( __( '%s - shortcode\'s attribute.<br/>A background color for the whole Neighborhood Map. Light colors recommended. (default: #fff).', $this->domain ), '<code>ws_background_color</code>' ),
          'map_frame_color' => sprintf( __( 'Map Frame Color', $this->domain ) ),
          'desc_map_frame_color' => sprintf( __( '%s - shortcode\'s attribute.<br/>Color for the double frame (default: #999).', $this->domain ), '<code>ws_map_frame_color</code>' ),
          'address_box_frame_color' => sprintf( __( 'Address Box Frame Color', $this->domain ) ),
          'desc_address_box_frame_color' => sprintf( __( '%s - shortcode\'s attribute.<br/>Color for the address field\'s border (default #aaa).', $this->domain ), '<code>ws_address_box_frame_color</code>' ),
          'address_box_bg_color' => sprintf( __( 'Address Box BG Color', $this->domain ) ),
          'desc_address_box_bg_color' => sprintf( __( '%s - shortcode\'s attribute.<br/>Color for the address field\'s background (default #aaa).', $this->domain ), '<code>ws_address_box_bg_color</code>' ),
          'address_box_text_color' => sprintf( __( 'Address Box Text Color', $this->domain ) ),
          'desc_address_box_text_color' => sprintf( __( '%s - shortcode\'s attribute.<br/>Color for the address field\'s text (default #aaa).', $this->domain ), '<code>ws_address_box_text_color</code>' ),
          'category_color' => sprintf( __( 'Category Color', $this->domain ) ),
          'desc_category_color' => sprintf( __( '%s - shortcode\'s attribute.<br/>Color for the category names (default: #777).', $this->domain ), '<code>ws_category_color</code>' ),
          'result_color' => sprintf( __( 'Result Color', $this->domain ) ),
          'desc_result_color' => sprintf( __( '%s - shortcode\'s attribute.<br/>Color for the names and distances of each destination (default #333).', $this->domain ), '<code>ws_result_color</code>' ),
          'disable_features' => sprintf( __( 'Disable Features', $this->domain ) ),
          'hide_bigger_map' => sprintf( __( 'Hide Bigger Map', $this->domain ) ),
          'desc_hide_bigger_map' => sprintf( __( '%s - shortcode\'s attribute.<br/>Hide the "Bigger map" link.', $this->domain ), '<code>ws_hide_bigger_map</code>' ),
          'disable_street_view' => sprintf( __( 'Disable Street View', $this->domain ) ),
          'desc_disable_street_view' => sprintf( __( '%s - shortcode\'s attribute.<br/>Turn off Street View.', $this->domain ), '<code>ws_disable_street_view</code>' ),
          'no_link_info_bubbles' => sprintf( __( 'No Link Info Bubbles', $this->domain ) ),
          'desc_no_link_info_bubbles' => sprintf( __( '%s - shortcode\'s attribute.<br/>Remove links from the info bubbles and removes the More link from the amenity list.', $this->domain ), '<code>ws_no_link_info_bubbles</code>' ),
          'hide_scores_below' => sprintf( __( 'Hide Scores Below', $this->domain ) ),
          'desc_hide_scores_below' => sprintf( __( '%s - shortcode\'s attribute.<br/>By default, the Neighborhood Map displays scores from 0 to 100. If you prefer not to show low scores, you can use this to define the cutoff. Example: %s (this will hide scores 0-49, and show scores 50-100)', $this->domain ), '<code>ws_hide_scores_below</code>', '<code>[property_walkscore_neighborhood ws_hide_scores_below="50"]</code>' ),
          'desc_walkscore' => sprintf( __( 'Setup your %s shortcode advanced settings below. The current shortcode renders %sWalk Score%s.<br/>The settings below will be used as default ones for all your shortcodes.</p><p>By default, shortcode uses current property for showing Walk Score. But if you want to show Walk Score of another property or use shortcode on non-property page you can use attribute <strong>property_id</strong>. Example: <code>[property_walkscore property_id=777]</code>.</p><p><strong>Note</strong>, you can overwrite any option manually in your shortcode. See shortcode\'s available attribute under option you want to change. Example: %s</p><p>Do you want to use widget instead of the current shortcode? Well, just go to %sWidgets%s settings and setup your <strong>Walk Score</strong> widget there.', $this->domain ), '<code>[property_walkscore]</code>', '<a href="https://www.redfin.com/how-walk-score-works" target="_blank">', '</a>', '<code>[property_walkscore ws_view=badge ws_type=free]</code>', '<a href="' . admin_url( 'widgets.php' ) . '" target="_blank">', '</a>' ),
          'walkscore_shortcode' => sprintf( __( 'Default Settings', $this->domain ) ),
          'walkscore_shortcode_desc' => sprintf( __( 'Be aware, default templates for %s shortcode follow Walk Score\'s %sbranding and linking requirements%s.', $this->domain ), '<code>[property_walkscore]</code>', '<a target="_blank" href="https://www.walkscore.com/professional/branding-requirements.php">', '</a>' ),
          'walkscore_view' => sprintf( __( 'View', $this->domain ) ),
          'desc_walkscore_view' => sprintf( __( '%s - shortcode\'s attribute.<br/> Setup default view for your Walk Score', $this->domain ), '<code>ws_view</code>' ),
          'walkscore_type' => sprintf( __( 'Type', $this->domain ) ),
          'desc_walkscore_type' => sprintf( __( '%s - shortcode\'s attribute.<br/> Premium API subscribers may link to the Walk Score property page %s instead of the \'How Walk Score Works\' page %s. Note! you must not use %s type if you are not Premium API subscriber. More details you can find %shere%s.', $this->domain ), '<code>ws_type</code>', '<strong>(type: <i>premium</i>)</strong>', '<strong>(type: <i>free</i>)</strong>', '<strong>premium</strong>', '<a target="_blank" href="https://www.walkscore.com/professional/branding-requirements.php">', '</a>' ),
        ) );
      }

      /**
       * Returns the list of available features for Walk Score and Public Transit API
       * Just additional method for localization.
       */
      public function get_score_api_features_list() {
        return implode( '', array(
          '<ul class="score_api_features">',
          '<li>' . __( 'Display the Walk Score of a location.', $this->domain ) .  '</li>',
          '<li>' . __( 'Search your listings by Walk Score on your site.', $this->domain ) .  '</li>',
          '<li>' . __( 'Sort your listings by Walk Score on your site.', $this->domain ) .  '</li>',
          '</ul>'
        ) );
      }

      /**
       * Returns available Map Modules for Neighborhood Map
       * Just additional method for localization.
       */
      public function get_available_map_modules() {
        return implode( '', array(
          '<ul class="map_modules">',
          '<li>' . __( 'google_map: [default] Google Street Map', $this->domain ) .  '</li>',
          '<li>' . __( 'street_view: [default] Google Street View', $this->domain ) .  '</li>',
          '<li>' . __( 'satellite: [default] Google Satellite View', $this->domain ) .  '</li>',
          '<li>' . __( 'walkability: [default] Walk Score heat map', $this->domain ) .  '</li>',
          '<li>' . __( 'walkshed: [default] 15 minute walking range', $this->domain ) .  '</li>',
          '<li>' . __( 'panoramio: Local pictures from Panoramio', $this->domain ) .  '</li>',
          '</ul>'
        ) );
      }

    }

  }

}
