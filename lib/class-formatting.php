<?php
/**
 * WP-Property Formatting
 *
 * Handle value conversions, object normalization, schema mapping, etc.
 *
 * @since 2.0
 * @author team@ud
 * @package WP-Property
 * @subpackage Formatting
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Formatting' ) ) {

    class Formatting extends \UsabilityDynamics\WPP\Utility {

      /**
       *
       *
       * @since unknown
       */
      static function initialize() {
        global $wp_properties;

        foreach( array_keys( (array) $wp_properties[ '_attribute_classifications' ] ) as $classification ) {
          foreach( array( 'system', 'edit', 'human' ) as $action ) {
            if( is_callable( array( 'WPP_Formatting', "formatting_attribute_{$classification}_{$action}" ) ) ) {
              add_filter( "wpp::classification::{$action}::{$classification}", array( 'WPP_Formatting', "formatting_attribute_{$classification}_{$action}" ), 10, 2 );
            }
          }
        }

        foreach( (array) $wp_properties[ 'attribute_classification' ] as $attribute => $classification ) {
          if( is_callable( array( 'WPP_Formatting', "formatting_attribute_{$classification}_human" ) ) ) {
            add_filter( "wpp::attribute::{$attribute}", array( 'WPP_Formatting', 'wpp_stat_filter' ), 10, 2 );
          }
        }

        //** Registering filters for Property Atrributes Metabox on Property Edit Page. odokienko@UD */
        if( !empty( $wp_properties[ 'attribute_classification' ] ) && is_array( $wp_properties[ 'attribute_classification' ] ) ) {
          foreach( $wp_properties[ 'attribute_classification' ] as $slug => $type ) {
            if( is_callable( array( 'WPP_Formatting', "metabox_meta_field_{$type}" ) ) ) {
              add_filter( "wpp::metabox::input::{$slug}", array( 'WPP_Formatting', "metabox_meta_field_{$type}" ), 10, 2 );
            }
          }
        }

        //** Property type should have predefined values! peshkov@UD */
        add_filter( 'wpp::predefined_values', array( 'WPP_Formatting', 'get_predefined_values' ), 10, 2 );

      }

      /**
       * Returns predefined values for passed attribute
       *
       * @param array  $values
       * @param string $attribute
       *
       * @return array
       * @author peshkov@UD
       */
      static function get_predefined_values( $values, $attribute ) {
        global $wp_properties;

        $return = array();

        //* Prepare values. It should be array and values must not be empty */
        if( !is_array( $values ) ) {
          $values = explode( ',', $values );
        }
        foreach( $values as $k => $v ) {
          $v = trim( $v );
          if( !empty( $v ) ) $values[ $k ] = $v;
          else unset( $values[ $k ] );
        }

        //** Prepare array */
        switch( $attribute ) {
          //** Property type should have predefined values! peshkov@UD */
          case 'property_type':
            foreach( (array) $wp_properties[ 'property_types' ] as $k => $v ) {
              if( in_array( $k, $wp_properties[ 'searchable_property_types' ] ) ) {
                $return[ $k ] = $v;
              }
            }
            break;

          default:
            if( is_array( $values ) ) {
              foreach( $values as $k => $v ) {
                $return[ $v ] = $v;
              }
            }
            break;
        }

        //** Check input type and change predefined values based on input type if needed */
        if( is_admin() ) {
          $input_type = isset( $wp_properties[ 'admin_attr_fields' ][ $attribute ] ) ? $wp_properties[ 'admin_attr_fields' ][ $attribute ] : 'input';
        } else {
          $input_type = isset( $wp_properties[ 'search_attr_fields' ][ $attribute ] ) ? $wp_properties[ 'search_attr_fields' ][ $attribute ] : 'input';
        }

        switch( $input_type ) {
          case 'checkbox':
            $return = array(
              'true'  => __( 'Yes', 'wpp' ),
              'false' => __( 'No', 'wpp' ),
            );
            break;
        }

        //** Go through additional filters ( attributes specific ) */
        $return = apply_filters( "wpp::predefined_values::{$attribute}", $return, $values );
        $return = is_array( $return ) ? $return : array();

        return $return;
      }

      /**
       *
       *
       * @since unknown
       */
      static function currency_format( $content, $args = '' ) {
        return self::formatting_attribute_currency_human( $content, $args );
      }

      /**
       * Returns area unit by slug
       *
       * @param bool|\UsabilityDynamics\WPP\type $slug
       *
       * @global type                            $wp_properties
       *
       * @return string
       * @author odokienko@UD
       */
      static function get_area_unit( $slug = false ) {
        global $wp_properties;

        $unit_slug = ( $slug ) ? $slug : $wp_properties[ 'configuration' ][ 'area_unit_type' ];

        switch( $unit_slug ) {
          case 'square_foot':
            $return = __( " sq ft", "wpp" );
            break;
          case 'square_kilometer':
            $return = __( " sq km", "wpp" );
            break;
          case 'square_mile':
            $return = __( " sq mi", "wpp" );
            break;
          case 'square_meter':
          default:
            $return = __( " sq m", "wpp" );
            break;
        }

        return $return;
      }

      /**
       * Filters attribute's value based on classification
       *
       * @since 2.0
       * @author odokienko@UD
       */
      static function wpp_stat_filter( $value, $args = array() ) {
        global $wp_properties;

        $args[ 'slug' ] = substr( current_filter(), 16 );

        if( !isset( $wp_properties[ 'attribute_classification' ][ $args[ 'slug' ] ] ) ) {
          return $value;
        }

        if( !empty( $wp_properties[ 'attribute_classification' ][ $args[ 'slug' ] ] ) ) {
          $args[ 'classification' ] = $wp_properties[ 'attribute_classification' ][ $args[ 'slug' ] ];
          $value                    = apply_filters( "wpp::classification::human::{$wp_properties[ 'attribute_classification' ][$args[ 'slug' ]]}", $value, $args );
        }

        return $value;
      }

      /**
       *
       *
       * @since unknown
       */
      static function formatting_attribute_detail_human( $content, $args = false ) {

        $content = do_shortcode( html_entity_decode( $content ) );

        $content = str_replace( "\n", "", nl2br( \UsabilityDynamics\WPP\Utility::cleanup_extra_whitespace( $content ) ) );

        return $content;

      }

      /**
       *
       *
       * @since unknown
       */
      static function formatting_attribute_string_human( $content, $args = false ) {

        $content = do_shortcode( html_entity_decode( $content ) );

        return $content;

      }

      /**
       * Converts value to currency.
       *
       * @updated 1.37.0 - renamed from add_dollar_sign to currency_format and moved into the wpp_default_api class
       * @since 1.15.3
       */
      static function formatting_attribute_currency_human( $content, $args = false ) {
        global $wp_properties, $wp_locale;

        $currency_symbol           = ( !empty( $wp_properties[ 'configuration' ][ 'currency_symbol' ] ) ? $wp_properties[ 'configuration' ][ 'currency_symbol' ] : "$" );
        $currency_symbol_placement = ( !empty( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ] ) ? $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ] : "before" );

        $content = preg_replace( array( '~<nobr>~', '~<\/nobr>~', '~\$~', '~,~', '~\s*~' ), "", $content );

        $hyphen = '~(-|&ndash;)~';
        if( !is_numeric( $content ) && preg_match( $hyphen, $content ) ) {
          $hyphen_between = preg_split( $hyphen, $content, PREG_SPLIT_DELIM_CAPTURE );
          foreach( $hyphen_between as &$part ) {
            $part = self::formatting_attribute_currency_human( $part );
          }

          return implode( ' &ndash; ', $hyphen_between );
        } elseif( !is_numeric( $content ) ) {
          return $content;
        } else {

          //** if Decimal settings are present */
          if( is_numeric( $wp_locale->number_format[ 'decimals' ] ) ) {
            $amount = number_format_i18n( $content, ( is_numeric( $wp_locale->number_format[ 'decimals' ] ) ? $wp_locale->number_format[ 'decimals' ] : 0 ) );
          } //** we have two ways*/
          else {
            //** at first, we do format  number with two decimals */
            $amount = number_format_i18n( $content, 2 );
            //** if we have no decimal value, just drop it */
            if( substr( $amount, -2 ) == '00' ) {
              $amount = substr( $amount, 0, -3 );
            }
          }

          return "<nobr>" . ( $currency_symbol_placement == 'before' ? $currency_symbol . ' ' : '' ) . $amount . ( $currency_symbol_placement == 'after' ? ' ' . $currency_symbol : '' ) . "</nobr>";
        }

      }

      /**
       *
       * @since 2.0
       * @author odokienko@UD
       */
      static function formatting_attribute_currency_system( $content, $args = false ) {
        $content = preg_replace( '~[^\d|\.]~i', '', $content );

        return $content;
      }

      /**
       * Formats address on print.  If address it not formatted, makes an on-the-fly call to GMaps for validation.
       *
       * @since 1.04
       * @author odokienko@UD
       */
      static function formatting_attribute_location_human( $content, $args = false ) {
        global $wp_properties;

        $args = wp_parse_args( $args, array(
          'property' => false,
          'slug'     => false
        ) );

        if( empty( $args[ 'slug' ] ) || empty( $args[ 'property' ] ) ) return $content;

        $property = (array) $args[ 'property' ];

        if( is_string( $property ) ) {
          $property = \UsabilityDynamics\WPP\Utility::get_property( $property );
        }

        if( empty( $property ) ) return $content;

        $slug = $args[ 'slug' ];

        $format = $wp_properties[ 'configuration' ][ 'display_address_format' ];

        if( empty( $format ) && !empty( $property[ $slug . '_formatted_address' ] ) ) {

          return $property[ $slug . '_formatted_address' ];

        } else {

          foreach( (array) $wp_properties[ '_geo_attributes' ] as $part ) {
            $format = str_replace( "[{$part}]", $property[ $slug . '_' . $part ], $format );
          }

          $format = preg_replace( '/^\n+|^[\t\s]*\n+/m', "", $format );
          $format = preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $format );
          $format = preg_replace( "/\[.*?\]/", "", $format );
          $format = trim( $format, ',' );

          if( str_replace( array( ' ', ',' ), '', $format ) == '' ) {
            return $content;
          }

          return $format;
        }
      }

      /**
       *
       *
       * @since unknown
       */
      static function formatting_attribute_link_human( $content, $args = false ) {

        if( \UsabilityDynamics\WPP\Utility::isURL( $content ) ) {
          $content = str_replace( '&ndash;', '-', $content );
          $content = "<a href='{$content}'>{$content}</a>";
        }

        return $content;

      }

      /**
       *
       *
       * @since unknown
       */
      static function formatting_attribute_timestamp_system( $content, $args = false ) {

        if( ( $timestamp = strtotime( $content ) ) !== false ) {
          return $timestamp;
        }

        return $content;
      }

      /**
       *
       *
       * @since unknown
       */
      static function formatting_attribute_timestamp_human( $content, $args = false ) {
        global $wp_properties;

        if( (int) $content === strtotime( date( 'c', (int) $content ) ) ) {
          $content = \UsabilityDynamics\WPP\Utility::nice_time( (int) $content, array( 'format' => 'date' ) );
        }

        return $content;
      }

      /**
       *
       *
       * @since unknown
       */
      static function formatting_attribute_timestamp_edit( $content, $args = false ) {

        if( empty( $content ) ) return '';

        $content = date( 'm/d/Y', (int) $content );

        return $content;
      }

      /**
       *
       *
       * @since unknown
       */
      static function formatting_attribute_number_human( $content, $args = false ) {

        if( is_numeric( $content ) ) {
          $content = number_format_i18n( $content );
        }

        return $content;
      }

      /**
       *
       *
       * @since 2.0
       */
      static function formatting_attribute_boolean_human( $content, $args = false ) {

        $content = \UsabilityDynamics\WPP\Utility::from_boolean( $content );

        return $content;
      }

      /**
       *
       *
       * @since 2.0
       */
      static function formatting_attribute_boolean_system( $content, $args = false ) {

        $content = \UsabilityDynamics\WPP\Utility::to_boolean( $content ) ? 'true' : 'false';

        return $content;
      }

      /**
       *
       *
       * @since 2.0
       */
      static function formatting_attribute_boolean_edit( $content, $args = false ) {

        $content = \UsabilityDynamics\WPP\Utility::to_boolean( $content ) ? 'true' : 'false';

        return $content;
      }

      /**
       * Formats areas on print.
       *
       * @since 1.04
       */
      static function formatting_attribute_area_human( $content, $args = false ) {

        if( !empty( $content ) ) {
          $content = number_format_i18n( $content, 1 ) . self::get_area_unit();
        }

        return $content;
      }

      /**
       * Add UI to add currency sign for currency fields on property editing page
       *
       * @since 2.0
       * @author odokienko@UD
       */
      static function metabox_meta_field_currency( $content ) {
        global $wp_properties;

        $symbol    = "<span class=\"currency\">" . ( ( $wp_properties[ 'configuration' ][ 'currency_symbol' ] ) ? $wp_properties[ 'configuration' ][ 'currency_symbol' ] : '$' ) . "</span>";
        $placement = ( !empty( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ] ) ) ? $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ] : 'before';

        switch( $placement ) {
          case 'before':
            $content = $symbol . '&nbsp;' . $content;
            break;
          case 'after':
            $content = $content . '&nbsp;' . $symbol;
            break;
        }

        return $content;
      }

      /**
       * Add UI to add area units for area fields on property editing page
       *
       * @since 2.0
       * @author odokienko@UD
       */
      static function metabox_meta_field_area( $content ) {
        $symbol = '<span class="symbol">' . ( self::get_area_unit() ) . '</span>';

        return $content . '&nbsp;' . $symbol;
      }

      /**
       * Add UI to set custom coordinates for location fields on property editing page
       *
       * @since 2.0
       * @author odokienko@UD
       */
      static function metabox_meta_field_location( $content, $object ) {
        global $wp_properties;

        $slug          = substr( current_filter(), 21 );
        $main_location = ( $slug == $wp_properties[ 'configuration' ][ 'address_attribute' ] ) ? true : false;
        $latitude      = $object[ $slug . '_latitude' ] ? $object[ $slug . '_latitude' ] : ( $main_location && $object[ 'latitude' ] ? $object[ 'latitude' ] : '' );
        $longitude     = $object[ $slug . '_longitude' ] ? $object[ $slug . '_longitude' ] : ( $main_location && $object[ 'longitude' ] ? $object[ 'longitude' ] : '' );
        $formatted     = ( !empty( $object[ $slug . '_address_is_formatted' ] ) || ( $main_location && !empty( $object[ 'address_is_formatted' ] ) ) ) ? true : ( !empty( $object[ $slug ] ) ? false : null );
        $class         = "";
        $icon          = "";

        switch( $formatted ) {
          case true:
            $class = 'address_is_formatted';
            $icon  = '<span class="wpp_google_maps_icon" title="' . __( "Address was successfully validated by Google's Geocoding Service", 'wpp' ) . '" ></span>';
            break;
          case false:
            $class = 'address_is_not_formatted';
            $icon  = '<span class="wpp_google_maps_icon" title="' . __( 'Address is not validated yet.', 'wpp' ) . '" ></span>';
            break;
        }

        ob_start();
        ?>
        <div class="wpp_attribute_row_address <?php echo $class; ?>">
      <?php echo $content; ?><?php echo $icon; ?>
          <div class="wpp_attribute_row_address_options">
        <input type="hidden" name="wpp_data[meta][<?php echo $slug; ?>_manual_coordinates]" value="false"/>
        <input type="checkbox" id="<?php echo $slug; ?>_wpp_manual_coordinates" class="wpp_manual_coordinates" name="wpp_data[meta][<?php echo $slug; ?>_manual_coordinates]" value="true" <?php checked( ( $object[ $slug . '_manual_coordinates' ] || ( $main_location && $object[ 'manual_coordinates' ] ) ), 1 ); ?> />
        <label for="<?php echo $slug; ?>_wpp_manual_coordinates"><?php echo __( 'Set Coordinates Manually.', 'wpp' ); ?></label>
        <div id="<?php echo $slug; ?>_wpp_coordinates" class="wpp_coordinates_wrapper hidden">
          <ul>
            <li>
              <input type="text" id="<?php echo $slug; ?>_wpp_meta_latitude" name="wpp_data[meta][<?php echo $slug; ?>_latitude]" value="<?php echo $latitude; ?>"/>
              <label><?php echo __( 'Latitude', 'wpp' ) ?></label>
              <div class="wpp_clear"></div>
            </li>
            <li>
              <input type="text" id="<?php echo $slug; ?>_wpp_meta_longitude" name="wpp_data[meta][<?php echo $slug; ?>_longitude]" value="<?php echo $longitude; ?>"/>
              <label><?php echo __( 'Longitude', 'wpp' ) ?></label>
              <div class="wpp_clear"></div>
            </li>
          </ul>
        </div>
      </div>
    </div>
        <?php

        $content = ob_get_contents();
        ob_end_clean();

        return $content;
      }

    }
  }

}