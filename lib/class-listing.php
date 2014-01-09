<?php
/**
 * WP-Property Listing Object
 *
 * Currently not used.
 *
 * @class WP_Error
 * @version 1.0
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @since 1.38
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Listing' ) ) {

    class Listing {

      /**
       * Listing ID.
       *
       * @property $id
       * @type String
       */
      public $id;

      /**
       * Global Property ID.
       *
       * @property $gpid
       * @type String
       */
      public $gpid = '';

      /**
       * Listing Description.
       *
       *
       * @property $content
       * @type String
       */
      public $content = '';

      /**
       * Content Title.
       *
       * @static
       * @property $title
       * @type String
       */
      public $title = '';

      /**
       * Listing Excerpt / Summary.
       *
       * @static
       * @property $excerpt
       * @type String
       */
      public $excerpt = '';

      /**
       * Status.
       *
       * @static
       * @property $status
       * @type String
       */
      public $status = 'publish';

      /**
       * Parent ID.
       *
       * @static
       * @property $parent
       * @type Integer
       */
      public $parent = 0;

      /**
       * @param string $key
       * @param null   $default
       *
       * @return mixed
       */
      public function get( $key = '', $default = null ) {
        return get_post_meta( $this->id, $key );
      }

      /**
       * @param string $key
       * @param string $value
       *
       * @internal param null $default
       *
       * @return mixed
       */
      public function set( $key = '', $value = null ) {
        return update_post_meta( $this->id, $key, $value );
      }

      /**
       * Hooks into save_post function and saves additional property data
       *
       * @todo Add some sort of custom capability so not only admins can make properties as featured. i.e. Agents can make their own properties featured.
       *
       * @since 1.04
       */
      public function save( $id ) {
        global $wp_properties, $wp_version;

        $_wpnonce = ( version_compare( $wp_version, '3.5', '>=' ) ? 'update-post_' : 'update-property_' ) . $id;

        if( !wp_verify_nonce( $_POST[ '_wpnonce' ], $_wpnonce ) || $_POST[ 'post_type' ] !== 'property' ) {
          return $id;
        }

        //* Delete cache files of search values for search widget's form */
        $directory = WPP_Path . 'cache/searchwidget';

        if( is_dir( $directory ) ) {
          $dir = opendir( $directory );
          while( ( $cachefile = readdir( $dir ) ) ) {
            if( is_file( $directory . "/" . $cachefile ) ) {
              unlink( $directory . "/" . $cachefile );
            }
          }
        }

        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
          return $id;
        }

        $update_data = $_REQUEST[ 'wpp_data' ][ 'meta' ];

        //** Neccessary meta data which is required by Supermap Premium Feature. Should be always set even the Supermap disabled. peshkov@UD */
        if( empty( $_REQUEST[ 'exclude_from_supermap' ] ) ) {
          if( !metadata_exists( 'post', $id, 'exclude_from_supermap' ) ) {
            $update_data[ 'exclude_from_supermap' ] = 'false';
          }
        }

        if( (float) $update_data[ 'latitude' ] == 0 ) $update_data[ 'latitude' ] = '';
        if( (float) $update_data[ 'longitude' ] == 0 ) $update_data[ 'longitude' ] = '';

        /* get old coordinates and location */
        $old_lat  = get_post_meta( $id, 'latitude', true );
        $old_lng  = get_post_meta( $id, 'longitude', true );
        $geo_data = array(
          'old_coordinates' => ( ( empty( $old_lat ) ) || ( empty( $old_lng ) ) ) ? "" : array( 'lat' => $old_lat, 'lng' => $old_lng ),
          'old_location'    => ( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) ) ? get_post_meta( $id, $wp_properties[ 'configuration' ][ 'address_attribute' ], true ) : ''
        );

        foreach( (array) $update_data as $meta_key => $meta_value ) {
          $attribute_data = Utility::get_attribute_data( $meta_key );

          //* Cleans the user input */
          $meta_value = Utility::encode_mysql_input( $meta_value, $meta_key );

          //* Only admins can mark properties as featured. */
          if( $meta_key == 'featured' && !current_user_can( 'manage_options' ) ) {
            //** But be sure that meta 'featured' exists at all */
            if( !metadata_exists( 'post', $id, $meta_key ) ) {
              $meta_value = 'false';
            } else {
              continue;
            }
          }

          //* Remove certain characters */

          if( $attribute_data[ 'currency' ] || $attribute_data[ 'numeric' ] ) {
            $meta_value = str_replace( array( "$", "," ), '', $meta_value );
          }

          //* Overwrite old post meta allowing only one value */
          delete_post_meta( $id, $meta_key );
          add_post_meta( $id, $meta_key, $meta_value );
        }

        //* Check if property has children */
        $children = get_children( "post_parent=$id&post_type=property" );

        //* Write any data to children properties that are supposed to inherit things */
        if( count( $children ) > 0 ) {
          foreach( (array) $children as $child_id => $child_data ) {
            //* Determine child property_type */
            $child_property_type = get_post_meta( $child_id, 'property_type', true );
            //* Check if child's property type has inheritence rules, and if meta_key exists in inheritance array */
            if( is_array( $wp_properties[ 'property_inheritance' ][ $child_property_type ] ) ) {
              foreach( (array) $wp_properties[ 'property_inheritance' ][ $child_property_type ] as $i_meta_key ) {
                $parent_meta_value = get_post_meta( $id, $i_meta_key, true );
                //* inheritance rule exists for this property_type for this meta_key */
                update_post_meta( $child_id, $i_meta_key, $parent_meta_value );
              }
            }
          }
        }

        self::maybe_set_gpid( $id );

        if( isset( $_REQUEST[ 'parent_id' ] ) ) {
          $_REQUEST[ 'parent_id' ] = self::update_parent_id( $_REQUEST[ 'parent_id' ], $id );
        }

        self::geolocate( $id );

        do_action( 'wpp:save_property', $id, $this );

        return true;

      }

      /**
       * Address validation function
       *
       * Since 1.37.2 extracted from save_property and revalidate_all_addresses to make same functionality
       *
       * @global array  $wp_properties
       *
       * @param integer $id
       * @param array   $args
       *
       * @return array
       * @since 1.37.2
       * @author odokienko@UD
       */
      public static function geolocate( $id, $args = array() ) {
        global $wp_properties;

        $args = wp_parse_args( $args, array(
          'skip_existing'   => 'false',
          'return_geo_data' => false,
        ) );

        extract( $args, EXTR_SKIP );
        $skip_existing   = isset( $skip_existing ) ? $skip_existing : 'false';
        $return_geo_data = isset( $return_geo_data ) ? $return_geo_data : false;

        $return = array();

        $geo_data             = false;
        $geo_data_coordinates = false;
        $latitude             = get_post_meta( $id, 'latitude', true );
        $longitude            = get_post_meta( $id, 'longitude', true );
        $current_coordinates  = $latitude . $longitude;
        $address_is_formatted = get_post_meta( $id, 'address_is_formatted', true );

        $address = get_post_meta( $id, $wp_properties[ 'configuration' ][ 'address_attribute' ], true );

        $coordinates = ( empty( $latitude ) || empty( $longitude ) ) ? "" : array( 'lat' => get_post_meta( $id, 'latitude', true ), 'lng' => get_post_meta( $id, 'longitude', true ) );

        if( $skip_existing == 'true' && !empty( $current_coordinates ) && in_array( $address_is_formatted, array( '1', 'true' ) ) ) {
          $return[ 'status' ] = 'skipped';

          return $return;
        }

        if( !( empty( $coordinates ) && empty( $address ) ) ) {

          /* will be true if address is empty and used manual_coordinates and coordinates is not empty */
          $manual_coordinates = get_post_meta( $id, 'manual_coordinates', true );
          $manual_coordinates = ( $manual_coordinates != 'true' && $manual_coordinates != '1' ) ? false : true;

          $address_by_coordinates = !empty( $coordinates ) && $manual_coordinates && empty( $address );

          if( !empty( $address ) ) {
            $geo_data = Utility::geo_locate_address( $address, $wp_properties[ 'configuration' ][ 'google_maps_localization' ], true );
          }

          if( !empty( $coordinates ) && $manual_coordinates ) {
            $geo_data_coordinates = Utility::geo_locate_address( $address, $wp_properties[ 'configuration' ][ 'google_maps_localization' ], true, $coordinates );
          }

          /** if Address was invalid or empty but we have valid $coordinates we use them */
          if( !empty( $geo_data_coordinates->formatted_address ) && ( $address_by_coordinates || empty( $geo_data->formatted_address ) ) ) {
            $geo_data = $geo_data_coordinates;
            /** clean up $address to remember that addres was empty or invalid*/
            $address = '';
          }

          if( empty( $geo_data ) ) {
            $return[ 'status' ] = 'empty_address';
          }

        }

        if( !empty( $geo_data->formatted_address ) ) {

          foreach( (array) $wp_properties[ 'geo_type_attributes' ] + array( 'display_address' ) as $meta_key ) {
            delete_post_meta( $id, $meta_key );
          }

          update_post_meta( $id, 'address_is_formatted', true );

          if( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) && ( !$manual_coordinates || $address_by_coordinates ) ) {
            update_post_meta( $id, $wp_properties[ 'configuration' ][ 'address_attribute' ], Utility::encode_mysql_input( $geo_data->formatted_address, $wp_properties[ 'configuration' ][ 'address_attribute' ] ) );
          }

          foreach( $geo_data as $geo_type => $this_data ) {
            if( in_array( $geo_type, (array) $wp_properties[ 'geo_type_attributes' ] ) && !in_array( $geo_type, array( 'latitude', 'longitude' ) ) ) {
              update_post_meta( $id, $geo_type, Utility::encode_mysql_input( $this_data, $geo_type ) );
            }
          }

          update_post_meta( $id, 'wpp::last_address_validation', time() );

          update_post_meta( $id, 'latitude', $manual_coordinates ? $coordinates[ 'lat' ] : $geo_data->latitude );
          update_post_meta( $id, 'longitude', $manual_coordinates ? $coordinates[ 'lng' ] : $geo_data->longitude );

          if( $return_geo_data ) {
            $return[ 'geo_data' ] = $geo_data;
          }

          $return[ 'status' ] = 'updated';

        }

        //** Logs the last validation status for better troubleshooting */
        update_post_meta( $id, 'wpp::google_validation_status', $geo_data->status );

        // Try to figure out what went wrong
        if( !empty( $geo_data->status ) && ( $geo_data->status == 'OVER_QUERY_LIMIT' || $geo_data->status == 'REQUEST_DENIED' ) ) {
          $return[ 'status' ] = 'over_query_limit';
        } elseif( empty( $address ) && empty( $geo_data ) ) {

          foreach( (array) $wp_properties[ 'geo_type_attributes' ] + array( 'display_address' ) as $meta_key ) {
            delete_post_meta( $id, $meta_key );
          }

          $return[ 'status' ] = 'empty_address';
          update_post_meta( $id, 'address_is_formatted', false );
        } elseif( empty( $return[ 'status' ] ) ) {
          $return[ 'status' ] = 'failed';
          update_post_meta( $id, 'address_is_formatted', false );
        }

        //** Neccessary meta data which is required by Supermap Premium Feature. Should be always set even the Supermap disabled. peshkov@UD */
        if( !metadata_exists( 'post', $id, 'exclude_from_supermap' ) ) {
          add_post_meta( $id, 'exclude_from_supermap', 'false' );
        }

        return $return;

      }

      /**
       * Updates parent ID.
       * Determines if parent exists and it doesn't have own parent.
       *
       * @param integer $parent_id
       * @param integer $id
       *
       * @return int
       * @author peshkov@UD
       * @since 1.37.5
       */
      public static function update_parent_id( $parent_id, $id ) {
        global $wpdb, $wp_properties;

        $parent_id = !empty( $parent_id ) ? $parent_id : 0;

        $post = get_post( $_REQUEST[ 'parent_id' ] );

        if( !$post ) {
          $parent_id = 0;
        } else {
          if( $post->post_parent > 0 ) {
            if( empty( $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] ) || $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] != 'true' ) {
              $parent_id = 0;
            }
          }
        }

        if( $parent_id == 0 ) {
          $wpdb->query( "UPDATE {$wpdb->posts} SET post_parent=0 WHERE ID={$id}" );
        }

        update_post_meta( $id, 'parent_gpid', self::maybe_set_gpid( $parent_id ) );

        return $parent_id;
      }

      /**
       * Generates Global Property ID for standard reference point during imports.
       *
       * Property ID is currently not used.
       *
       * @return integer. Global ID number
       *
       * @param bool|int $id . Property ID.
       *
       * @param bool     $check_existance
       *
       * @todo API call to UD server to verify there is no duplicates
       * @since 1.6
       */
      public static function get_gpid( $id = false, $check_existance = false ) {

        if( $check_existance && $id ) {
          $exists = get_post_meta( $id, 'wpp_gpid', true );

          if( $exists ) {
            return $exists;
          }
        }

        return 'gpid_' . rand( 1000000000, 9999999999 );

      }

      /**
       * Generates Global Property ID if it does not exist
       *
       * @param bool $id
       *
       * @return string | Returns GPID
       * @since 1.6
       */
      public static function maybe_set_gpid( $id = false ) {

        if( !$id ) {
          return false;
        }

        $exists = get_post_meta( $id, 'wpp_gpid', true );

        if( $exists ) {
          return $exists;
        }

        $gpid = self::get_gpid( $id, true );

        update_post_meta( $id, 'wpp_gpid', $gpid );

        return $gpid;

      }

      /**
       * Load property information into an array or an object
       *
       * @version 1.11 Added support for multiple meta values for a given key
       *
       * @since 1.11
       * @version 1.14 - fixed problem with drafts
       * @todo Code pertaining to displaying data should be migrated to prepare_property_for_display() like :$real_value = nl2br($real_value);
       * @todo Fix the long dashes - when in latitude or longitude it breaks it when using static map
       *
       */
      static function load( $id, $args = false ) {
        global $wp_properties, $wpdb;

        $id = trim( $id );

        extract( wp_parse_args( $args, array(
          'get_children'          => 'true',
          'return_object'         => 'false',
          'load_gallery'          => 'true',
          'load_thumbnail'        => 'true',
          'allow_multiple_values' => 'false',
          'load_parent'           => 'true'
        )), EXTR_SKIP );

        $get_children          = isset( $get_children ) ? $get_children : 'true';
        $return_object         = isset( $return_object ) ? $return_object : 'false';
        $load_gallery          = isset( $load_gallery ) ? $load_gallery : 'true';
        $load_thumbnail        = isset( $load_thumbnail ) ? $load_thumbnail : 'true';
        $allow_multiple_values = isset( $allow_multiple_values ) ? $allow_multiple_values : 'false';
        $load_parent           = isset( $load_parent ) ? $load_parent : 'true';

        $args = is_array( $args ) ? http_build_query( $args ) : (string) $args;

        if( $return = wp_cache_get( $id . $args ) ) {
          return $return;
        }

        $post = get_post( $id, ARRAY_A );

        if( $post[ 'post_type' ] != 'property' ) {
          return false;
        }

        //** Figure out what all the editable attributes are, and get their keys */
        $wp_properties[ 'property_meta' ]  = ( is_array( $wp_properties[ 'property_meta' ] ) ? $wp_properties[ 'property_meta' ] : array() );
        $wp_properties[ 'property_stats' ] = ( is_array( $wp_properties[ 'property_stats' ] ) ? $wp_properties[ 'property_stats' ] : array() );
        $editable_keys                     = array_keys( array_merge( $wp_properties[ 'property_meta' ], $wp_properties[ 'property_stats' ] ) );

        $return = array();

        //** Load all meta keys for this object */
        if( $keys = get_post_custom( $id ) ) {
          foreach( $keys as $key => $value ) {

            if( $allow_multiple_values == 'false' ) {
              $value = $value[ 0 ];
            }

            $keyt = trim( $key );

            //** If has _ prefix it's a built-in WP key */
            if( '_' == $keyt{0} ) {
              continue;
            }

            // Fix for boolean values
            switch( $value ) {

              case 'true':
                $real_value = true; //** Converts all "true" to 1 */
                break;

              case 'false':
                $real_value = false;
                break;

              default:
                $real_value = $value;
                break;

            }

            // Handle keys with multiple values
            if( count( $value ) > 1 ) {
              $return[ $key ] = $value;
            } else {
              $return[ $key ] = $real_value;
            }

          }
        }

        $return = array_merge( $return, $post );

        //** Make sure certain keys were not messed up by custom attributes */
        $return[ 'system' ]  = array();
        $return[ 'gallery' ] = array();

        // Figure out what the thumbnail is, and load all sizes
        if( $load_thumbnail == 'true' ) {

          $wp_image_sizes = \get_intermediate_image_sizes();

          $thumbnail_id = \get_post_meta( $id, '_thumbnail_id', true );
          $attachments  = \get_children( array( 'post_parent' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC' ) );

          if( $thumbnail_id ) {
            foreach( $wp_image_sizes as $image_name ) {
              $this_url                          = wp_get_attachment_image_src( $thumbnail_id, $image_name, true );
              $return[ 'images' ][ $image_name ] = $this_url[ 0 ];
            }

            $featured_image_id = $thumbnail_id;

          } elseif( $attachments ) {
            foreach( $attachments as $attachment_id => $attachment ) {

              foreach( $wp_image_sizes as $image_name ) {
                $this_url                          = wp_get_attachment_image_src( $attachment_id, $image_name, true );
                $return[ 'images' ][ $image_name ] = $this_url[ 0 ];
              }

              $featured_image_id = $attachment_id;
              break;
            }
          }

          if( $featured_image_id ) {
            $return[ 'featured_image' ] = $featured_image_id;

            $image_title = $wpdb->get_var( "SELECT post_title  FROM {$wpdb->prefix}posts WHERE ID = '{$featured_image_id}' " );

            $return[ 'featured_image_title' ] = $image_title;
            $return[ 'featured_image_url' ]   = wp_get_attachment_url( $featured_image_id );
          }

        }

        // Load all attached images and their sizes
        if( $load_gallery == 'true' ) {

          // Get gallery images
          if( $attachments ) {
            foreach( $attachments as $attachment_id => $attachment ) {
              $return[ 'gallery' ][ $attachment->post_name ][ 'post_title' ]    = $attachment->post_title;
              $return[ 'gallery' ][ $attachment->post_name ][ 'post_excerpt' ]  = $attachment->post_excerpt;
              $return[ 'gallery' ][ $attachment->post_name ][ 'post_content' ]  = $attachment->post_content;
              $return[ 'gallery' ][ $attachment->post_name ][ 'attachment_id' ] = $attachment_id;
              foreach( $wp_image_sizes as $image_name ) {
                $this_url                                                     = wp_get_attachment_image_src( $attachment_id, $image_name, true );
                $return[ 'gallery' ][ $attachment->post_name ][ $image_name ] = $this_url[ 0 ];
              }
            }
          } else {
            $return[ 'gallery' ] = false;
          }
        }

        // Load parent if exists and inherit Parent's atttributes.
        if( $load_parent == 'true' && $post[ 'post_parent' ] ) {

          $return[ 'is_child' ] = true;

          $parent_object = self::load( $post[ 'post_parent' ], array( 'load_gallery' => $load_gallery, 'get_children' => false ) );

          $return[ 'parent_id' ]    = $post[ 'post_parent' ];
          $return[ 'parent_link' ]  = $parent_object[ 'permalink' ];
          $return[ 'parent_title' ] = $parent_object[ 'post_title' ];

          // Inherit things
          if( is_array( $wp_properties[ 'property_inheritance' ][ $return[ 'property_type' ] ] ) ) {
            foreach( $wp_properties[ 'property_inheritance' ][ $return[ 'property_type' ] ] as $inherit_attrib ) {
              if( !empty( $parent_object[ $inherit_attrib ] ) && empty( $return[ $inherit_attrib ] ) ) {
                $return[ $inherit_attrib ] = $parent_object[ $inherit_attrib ];
              }
            }
          }
        }

        // Load Children and their attributes
        if( $get_children == 'true' ) {

          //** Calculate variables if based off children if children exist */
          $children = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE  post_type = 'property' AND post_status = 'publish' AND post_parent = '{$id}' ORDER BY menu_order ASC " );

          if( count( $children ) > 0 ) {

            //** Cycle through children and get necessary variables */
            foreach( $children as $child_id ) {

              $child_object                      = self::load( $child_id, array( 'load_gallery' => $load_gallery, 'load_parent' => false ) );
              $return[ 'children' ][ $child_id ] = $child_object;

              //** Save child image URLs into one array for quick access */
              if( !empty( $child_object[ 'featured_image_url' ] ) ) {
                $return[ 'system' ][ 'child_images' ][ $child_id ] = $child_object[ 'featured_image_url' ];
              }

              //** Exclude variables from searchable attributes (to prevent ranges) */
              $excluded_attributes    = $wp_properties[ 'geo_type_attributes' ];
              $excluded_attributes[ ] = $wp_properties[ 'configuration' ][ 'address_attribute' ];

              foreach( $wp_properties[ 'searchable_attributes' ] as $searchable_attribute ) {

                $attribute_data = Utility::get_attribute_data( $searchable_attribute );

                if( $attribute_data[ 'numeric' ] || $attribute_data[ 'currency' ] ) {

                  if( !empty( $child_object[ $searchable_attribute ] ) && !in_array( $searchable_attribute, $excluded_attributes ) ) {
                    $range[ $searchable_attribute ][ ] = $child_object[ $searchable_attribute ];
                  }

                }
              }
            }

            //* Cycle through every type of range (i.e. price, deposit, bathroom, etc) and fix-up the respective data arrays */
            foreach( (array) $range as $range_attribute => $range_values ) {

              //* Cycle through all values of this range (attribute), and fix any ranges that use dashes */
              foreach( $range_values as $key => $single_value ) {

                //* Remove dollar signs */
                $single_value = str_replace( "$", '', $single_value );

                //* Fix ranges */
                if( strpos( $single_value, '&ndash;' ) ) {

                  $split = explode( '&ndash;', $single_value );

                  foreach( $split as $new_single_value )

                    if( !empty( $new_single_value ) ) {
                      array_push( $range_values, trim( $new_single_value ) );
                    }

                  //* Unset original value with dash */
                  unset( $range_values[ $key ] );

                }
              }

              //* Remove duplicate values from this range */
              $range[ $range_attribute ] = array_unique( $range_values );

              //* Sort the values in this particular range */
              sort( $range[ $range_attribute ] );

              if( count( $range[ $range_attribute ] ) < 2 ) {
                $return[ $range_attribute ] = $return[ $range_attribute ] . ' ( ' . $range[ $range_attribute ][ 0 ] . ' )';
              }

              if( count( $range[ $range_attribute ] ) > 1 ) {
                $return[ $range_attribute ] = $return[ $range_attribute ] . ' ( ' . min( $range[ $range_attribute ] ) . " - " . max( $range[ $range_attribute ] ) . ' )';
              }

              //** If we end up with a range, we make a note of it */
              if( !empty( $return[ $range_attribute ] ) ) {
                $return[ 'system' ][ 'upwards_inherited_attributes' ][ ] = $range_attribute;
              }

            }

          }
        }

        if( !empty( $return[ 'location' ] ) && !in_array( 'address', $editable_keys ) && !isset( $return[ 'address' ] ) ) {
          $return[ 'address' ] = $return[ 'location' ];
        }

        $return[ 'wpp_gpid' ]  = self::maybe_set_gpid( $id );

        $return[ 'permalink' ] = get_permalink( $id );

        //** Make sure property_type stays as slug, or it will break many things:  (widgets, class names, etc)  */
        $return[ 'property_type_label' ] = $wp_properties[ 'property_types' ][ $return[ 'property_type' ] ];

        if( empty( $return[ 'property_type_label' ] ) ) {
          foreach( $wp_properties[ 'property_types' ] as $pt_key => $pt_value ) {
            if( strtolower( $pt_value ) == strtolower( $return[ 'property_type' ] ) ) {
              $return[ 'property_type' ]       = $pt_key;
              $return[ 'property_type_label' ] = $pt_value;
            }
          }
        }

        //** If phone number is not set but set globally, we load it into property array here */
        if( empty( $return[ 'phone_number' ] ) && !empty( $wp_properties[ 'configuration' ][ 'phone_number' ] ) ) {
          $return[ 'phone_number' ] = $wp_properties[ 'configuration' ][ 'phone_number' ];
        }

        if( is_array( $return ) ) {
          ksort( $return );
        }

        $return = apply_filters( 'wpp_get_property', $return );
        $return = apply_filters( 'wpp:get_property', $return );

        //* Get rid of all empty values */
        foreach( $return as $key => $item ) {

          //** Don't blank keys starting w/ post_  - this should be converted to use get_attribute_data() to check where data is stored for better check - potanin@UD */
          if( strpos( $key, 'post_' ) === 0 ) {
            continue;
          }

          if( empty( $item ) ) {
            unset( $return[ $key ] );
          }

        }

        //** Convert to object */
        if( $return_object == 'true' ) {
          $return = Utility::array_to_object( $return );
        }

        wp_cache_add( $id . $args, $return );

        return $return;

      }

    }

  }

}



