<?php
/**
 * WP-Property Settings
 *
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @since 2.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Settings' ) ) {

    class Settings extends \UsabilityDynamics\Settings {

      static function define( $args ) {
        global $wp_properties;

        $_instance = new Settings( $args );

        // @note Hopefully temporary but this exposes settings to the legacy $wp_properties global variable.
        $wp_properties = $_instance ->get();

        self::settings_action();

        return $_instance;

      }

      /**
       * Loads settings into global variable
       * Also restores data from backup file.
       *
       * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
       *
       * As of 1.11 prevents removal of premium feature configurations that are not held in the settings page array
       *
       * 1.12 - added taxonomies filter: wpp_taxonomies
       * 1.14 - added backup from text file
       *
       * @param bool $force_db
       *
       * @return array|$wp_properties
       * @since 1.12
       */
      static function settings_action( $force_db = false ) {
        global $wp_properties;

        //** Handle backup */
        if( isset( $_REQUEST[ 'wpp_settings' ] ) &&
          wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'wpp_setting_save' ) &&
          !empty( $_FILES[ 'wpp_settings' ][ 'tmp_name' ][ 'settings_from_backup' ] )
        ) {
          $backup_file     = $_FILES[ 'wpp_settings' ][ 'tmp_name' ][ 'settings_from_backup' ];
          $backup_contents = file_get_contents( $backup_file );
          if( !empty( $backup_contents ) ) {
            $decoded_settings = json_decode( $backup_contents, true );
          }
          if( !empty( $decoded_settings ) ) {
            //** Allow features to preserve their settings that are not configured on the settings page */
            $wpp_settings = apply_filters( 'wpp_settings_save', $decoded_settings, $wp_properties );
            //** Prevent removal of featured settings configurations if they are not present */
            if( !empty( $wp_properties[ 'configuration' ][ 'feature_settings' ] ) ) {
              foreach( $wp_properties[ 'configuration' ][ 'feature_settings' ] as $feature_type => $preserved_settings ) {
                if( empty( $decoded_settings[ 'configuration' ][ 'feature_settings' ][ $feature_type ] ) ) {
                  $wpp_settings[ 'configuration' ][ 'feature_settings' ][ $feature_type ] = $preserved_settings;
                }
              }
            }
            update_option( 'wpp_settings', $wpp_settings );
            //** Load settings out of database to overwrite defaults from action_hooks. */
            $wp_properties_db = get_option( 'wpp_settings' );
            //** Overwrite $wp_properties with database setting */
            $wp_properties = array_merge( $wp_properties, $wp_properties_db );
            //** Reload page to make sure higher-end functions take affect of new settings */
            //** The filters below will be ran on reload, but the saving functions won't */
            if( $_REQUEST[ 'page' ] == 'property_settings' ) {
              unset( $_REQUEST );
              wp_redirect( admin_url( "edit.php?post_type=property&page=property_settings&message=updated" ) );
              exit;
            }
          }
        }

        if( $force_db ) {

          // Load settings out of database to overwrite defaults from action_hooks.
          $wp_properties_db = get_option( 'wpp_settings' );

          // Overwrite $wp_properties with database setting
          $wp_properties = array_merge( $wp_properties, $wp_properties_db );

        }

        add_filter( 'wpp_image_sizes', array( 'WPP_F', 'remove_deleted_image_sizes' ) );

        // Filers are applied
        $wp_properties[ 'configuration' ]             = apply_filters( 'wpp_configuration', $wp_properties[ 'configuration' ] );
        $wp_properties[ 'location_matters' ]          = apply_filters( 'wpp_location_matters', $wp_properties[ 'location_matters' ] );
        $wp_properties[ 'hidden_attributes' ]         = apply_filters( 'wpp_hidden_attributes', $wp_properties[ 'hidden_attributes' ] );
        $wp_properties[ 'descriptions' ]              = apply_filters( 'wpp_label_descriptions', $wp_properties[ 'descriptions' ] );
        $wp_properties[ 'image_sizes' ]               = apply_filters( 'wpp_image_sizes', $wp_properties[ 'image_sizes' ] );
        $wp_properties[ 'search_conversions' ]        = apply_filters( 'wpp_search_conversions', $wp_properties[ 'search_conversions' ] );
        $wp_properties[ 'searchable_attributes' ]     = apply_filters( 'wpp_searchable_attributes', $wp_properties[ 'searchable_attributes' ] );
        $wp_properties[ 'searchable_property_types' ] = apply_filters( 'wpp_searchable_property_types', $wp_properties[ 'searchable_property_types' ] );
        $wp_properties[ 'property_inheritance' ]      = apply_filters( 'wpp_property_inheritance', $wp_properties[ 'property_inheritance' ] );
        $wp_properties[ 'property_meta' ]             = apply_filters( 'wpp_property_meta', $wp_properties[ 'property_meta' ] );
        $wp_properties[ 'property_stats' ]            = apply_filters( 'wpp_property_stats', $wp_properties[ 'property_stats' ] );
        $wp_properties[ 'property_types' ]            = apply_filters( 'wpp_property_types', $wp_properties[ 'property_types' ] );
        $wp_properties[ 'taxonomies' ]                = apply_filters( 'wpp_taxonomies', ( isset( $wp_properties[ 'taxonomies' ] ) ? $wp_properties[ 'taxonomies' ] : array() ) );

        $wp_properties = stripslashes_deep( $wp_properties );

        return $wp_properties;

      }




    }

  }

}



