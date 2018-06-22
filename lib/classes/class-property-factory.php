<?php
/**
 * Property Factory
 *
 * @since 2.1.1
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Factory' ) ) {

    class Property_Factory {

      /**
       * Returns property
       *
       * @since 1.11
       *
       * @todo Code pertaining to displaying data should be migrated to prepare_property_for_display() like :$real_value = nl2br($real_value);
       * @todo Fix the long dashes - when in latitude or longitude it breaks it when using static map
       *
       * @param $id
       * @param bool $args
       * @return array|bool|mixed
       */
      static public function get( $id, $args = false ) {
        global $wp_properties;

        if( is_object( $id ) && isset( $id->ID ) ) {
          $id = $id->ID;
        }

        $id = trim( $id );

        extract( $args = wp_parse_args( $args, array(
          'get_children'          => 'true',
          'return_object'         => 'false',
          'load_gallery'          => 'true',
          'load_thumbnail'        => 'true',
          'load_parent'           => 'true',
          'cache'                 => 'true',
        ) ), EXTR_SKIP );

        $get_children          = isset( $get_children ) && $get_children === 'true' ? true : false;
        $return_object         = isset( $return_object ) && $return_object === 'true' ? true : false;
        $load_gallery          = isset( $load_gallery ) && $load_gallery === 'true' ? true : false;
        $load_thumbnail        = isset( $load_thumbnail ) && $load_thumbnail === 'true' ? true : false;
        $load_parent           = isset( $load_parent ) && $load_parent === 'true' ? true : false;
        $cache                 = isset( $cache ) && $cache === 'true' ? true : false;

        if( $cache && $property = wp_cache_get( $id ) ) {

          // Do nothing here since we already have data from cache!

          if( is_array( $property ) ) {
            $property['_cached'] = true;
          }

        } else {

          $property = array();

          $post = get_post( $id, ARRAY_A );

          if( $post[ 'post_type' ] != 'property' ) {
            return false;
          }

          //** Figure out what all the editable attributes are, and get their keys */
          $editable_keys = array_keys( array_merge( isset( $wp_properties[ 'property_meta' ] ) ? (array) $wp_properties[ 'property_meta' ] : array(), (array)$wp_properties[ 'property_stats' ] ) );

          //** Load all meta keys for this object */
          if( $keys = get_post_custom( $id ) ) {

            foreach( $keys as $key => $value ) {

              $attribute = Attributes::get_attribute_data($key);

              if( !$attribute[ 'multiple' ] ) {
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
              if( is_array($value) && count( $value ) > 1 ) {
                $property[ $key ] = $value;
              } else {
                $property[ $key ] = $real_value;
              }

              $property[ $key ] = maybe_unserialize( $property[ $key ] );

            }
          }

          $property = array_merge( $property, $post );

          // Early get_property, before adding standard/computed fields.
          $property = apply_filters( 'wpp::property::early_extend', $property, $args );

          //** Make sure certain keys were not messed up by custom attributes */
          $property[ 'system' ]  = array();
          $property[ 'gallery' ] = array();

          $property[ 'wpp_gpid' ]  = WPP_F::maybe_set_gpid( $id );
          $property[ 'permalink' ] = get_permalink( $id );

          //** Make sure property_type stays as slug, or it will break many things:  (widgets, class names, etc)  */
          if( !empty( $property[ 'property_type' ] ) ) {
            $property[ 'property_type_label' ] = get_property_type( $id );
          }

          //** If phone number is not set but set globally, we load it into property array here */
          if( empty( $property[ 'phone_number' ] ) && !empty( $wp_properties[ 'configuration' ][ 'phone_number' ] ) ) {
            $property[ 'phone_number' ] = $wp_properties[ 'configuration' ][ 'phone_number' ];
          }

          //* Get rid of all empty values */
          foreach( $property as $key => $item ) {

            //** Don't blank keys starting w/ post_  - this should be converted to use get_attribute_data() to check where data is stored for better check - potanin@UD */
            if( strpos( $key, 'post_' ) === 0 ) {
              continue;
            }

            if( empty( $item ) ) {
              unset( $property[ $key ] );
            }

          }

          wp_cache_add( $id, $property );

        }

        /*
         * Load parent if exists and inherit Parent's atttributes.
         */
        if( $load_parent ) {
          $property = self::extend_property_with_parent( $property, $cache );
        }

        /*
         * Load Children and their attributes
         */
        if( $get_children ) {
          $property = self::extend_property_with_children( $property, $cache );
        }

        /*
         * Figure out what the thumbnail is, and load all sizes
         */
        if( $load_thumbnail ) {
          $property = array_merge( $property, self::get_thumbnail( $id, $cache ) );
        }

        /*
         * Load all attached images and their sizes
         */
        if( $load_gallery ) {
          $_meta_attached = get_post_meta($id, 'wpp_media');
          if(is_array($_meta_attached) && count($_meta_attached)){
            $_meta_attached = array_map('intval', $_meta_attached);
            $gallery = self::get_images( $_meta_attached, $cache, 'ids');
          }
          else{
            $gallery = self::get_images( $id, $cache);
          }
          $property[ 'gallery' ] = !empty( $gallery ) ? $gallery : false;
        }

        if( is_array( $property ) ) {
          ksort( $property );
        }

        $property = apply_filters( 'wpp_get_property', $property, $args );

        //** Convert to object */
        if( $return_object ) {
          $property = WPP_F::array_to_object( $property );
        }

        return $property;

      }

      /**
       * Extends particular property with parent's data.
       * Internal method! Do not use it directly.
       *
       * @param $property
       * @param bool|true $cache
       * @return array
       */
      static function extend_property_with_parent( $property, $cache = true ) {
        global $wp_properties;
        if( empty( $property[ 'ID' ] ) || empty( $property[ 'post_parent' ] ) || !$property[ 'post_parent' ] > 0 ) {
          return $property;
        }

        if( $cache && $data = wp_cache_get( $property[ 'ID' ], 'property_parent' ) ) {

          // Do nothing here.

        } else {

          $data = array();

          $parent_object = self::get( $property[ 'post_parent' ], array(
            'get_children'          => 'false',
            'return_object'         => 'false',
            'load_gallery'          => 'false',
            'load_thumbnail'        => 'false',
            'load_parent'           => 'false',
            'cache'                 => 'false',
          ) );

          $data[ 'is_child' ] = true;
          $data[ 'parent_id' ]    = $property[ 'post_parent' ];
          $data[ 'parent_link' ]  = $parent_object[ 'permalink' ];
          $data[ 'parent_title' ] = $parent_object[ 'post_title' ];

          // Inherit things
          if( !empty( $property[ 'property_type' ] ) && !empty( $wp_properties[ 'property_inheritance' ][ $property[ 'property_type' ] ] ) ) {
            foreach( (array)$wp_properties[ 'property_inheritance' ][ $property[ 'property_type' ] ] as $inherit_attrib ) {
              if( !empty( $parent_object[ $inherit_attrib ] ) && empty( $property[ $inherit_attrib ] ) ) {
                $data[ $inherit_attrib ] = $parent_object[ $inherit_attrib ];
              }
            }
          }

          wp_cache_add( $property[ 'ID' ], $data, 'property_parent' );

        }

        $property = array_merge( $property, $data );

        return $property;
      }

      /**
       * Extends particular property with children data.
       * Internal method! Do not use it directly.
       *
       * @param $property
       * @param bool $cache
       * @return array
       */
      static function extend_property_with_children( $property, $cache = true ) {
        global $wpdb, $wp_properties;

        if( empty( $property['ID'] ) ) {
          return $property;
        }

        if( $cache && $data = wp_cache_get( $property[ 'ID' ], 'property_children' ) ) {

          // Do nothing here.

        } else {

          $data = array();

          //** Calculate variables if based off children if children exist */
          $children = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE  post_type = 'property' AND post_status = 'publish' AND post_parent = '{$property['ID']}' ORDER BY menu_order ASC " );

          if( count( $children ) > 0 ) {

            $range = array();

            //** Cycle through children and get necessary variables */
            foreach( $children as $child_id ) {

              $child_object = self::get( $child_id, array(
                'get_children'          => 'false',
                'load_parent'           => 'false',
                'load_gallery'          => 'true',
                'load_thumbnail'        => 'true',
                'cache'                 => 'false',
              ) );

              $data[ 'children' ][ $child_id ] = $child_object;

              //** Save child image URLs into one array for quick access */
              if( !empty( $child_object[ 'featured_image_url' ] ) ) {
                $data[ 'system' ][ 'child_images' ][ $child_id ] = $child_object[ 'featured_image_url' ];
              }

              //** Exclude variables from searchable attributes (to prevent ranges) */
              $excluded_attributes    = $wp_properties[ 'geo_type_attributes' ];
              $excluded_attributes[ ] = $wp_properties[ 'configuration' ][ 'address_attribute' ];

              foreach( $wp_properties[ 'searchable_attributes' ] as $searchable_attribute ) {

                $attribute_data = Attributes::get_attribute_data( $searchable_attribute );

                if( !empty( $attribute_data[ 'numeric' ] ) || !empty( $attribute_data[ 'currency' ] ) ) {

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
                if( !isset( $property[ $range_attribute ] ) ) {
                  $property[ $range_attribute ] = '';
                }
                $data[ $range_attribute ] = $property[ $range_attribute ] . ' ( ' . $range[ $range_attribute ][ 0 ] . ' )';
              }

              if( count( $range[ $range_attribute ] ) > 1 ) {
                if( !isset( $property[ $range_attribute ] ) ) {
                  $property[ $range_attribute ] = '';
                }
                $data[ $range_attribute ] = $property[ $range_attribute ] . ' ( ' . min( $range[ $range_attribute ] ) . " - " . max( $range[ $range_attribute ] ) . ' )';
              }

              //** If we end up with a range, we make a note of it */
              if( !empty( $data[ $range_attribute ] ) ) {
                $data[ 'system' ][ 'upwards_inherited_attributes' ][ ] = $range_attribute;
              }

            }

          }

          wp_cache_add( $property[ 'ID' ], $data, 'property_children' );

        }

        $property = array_merge( $property, $data );

        return $property;
      }

      /**
       * Returns thumbnail ID of property.
       * It thumbnail does not exist,
       * it returns ID of default property image based on property type
       *
       * @param $property_id
       * @return mixed
       * @since 2.1.3
       * @author peshkov@UD
       */
      static public function get_thumbnail_id( $property_id ) {

        $meta_cache = wp_cache_get( $property_id, 'post_meta' );

        if ( !$meta_cache ) {
          $meta_cache = update_meta_cache( 'post', array( $property_id ) );
          $meta_cache = $meta_cache[ $property_id ];
        }

        /* STEP 1:  Try to get ID of featured image */
        if ( isset( $meta_cache[ '_thumbnail_id' ] ) ) {

          if( is_array( $meta_cache[ '_thumbnail_id' ] ) ) {
            $_ = array_values( $meta_cache[ '_thumbnail_id' ] );
            return array_shift( $_ );
          } else {
            return $meta_cache[ '_thumbnail_id' ];
          }

        }

        /* STEP 2:  Try to get ID of any existing attachment (image) */
        else {

          $attachments = get_children( array(
            'numberposts' => '1',
            'post_parent' => $property_id,
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'orderby' => 'menu_order ASC, ID',
            'order' => 'DESC'
          ) );

          if( !empty( $attachments ) ) {

            return key($attachments);

          }

        }

        /* STEP 3:  Try to get ID of default image based on property type */
        $property_type = get_post_meta( $property_id, "property_type", true );
        if( !empty( $property_type ) ) {
          $id = ud_get_wp_property( "configuration.default_image.types.{$property_type}.id" );
          if( !empty( $id ) && is_numeric( $id ) ) {
            return $id;
          }
        }

        /* STEP 4:  Try to get ID of basic default image. See Display Tab on Settings page (UI) */
        $id = ud_get_wp_property( 'configuration.default_image.default.id' );
        return !empty( $id ) && is_numeric( $id ) ? $id : false;

      }

      /**
       * Returns thumbnail's data
       *
       * @param int $id
       * @param bool $cache
       * @return array
       */
      static public function get_thumbnail( $id, $cache = true ) {
        global $wpdb;

        if( $cache && $data = wp_cache_get( $id, 'property_thumbnail' ) ) {

          // Do nothing here.

        } else {

          $data = array();

          $wp_image_sizes = get_intermediate_image_sizes();

          $thumbnail_id = self::get_thumbnail_id( $id );

          if( !empty( $thumbnail_id ) ) {

            foreach( $wp_image_sizes as $image_name ) {
              $this_url = wp_get_attachment_image_src( $thumbnail_id, $image_name, true );
              $data[ 'images' ][ $image_name ] = $this_url[ 0 ];
            }

            $featured_image_id = $thumbnail_id;

          }

          if( !empty( $featured_image_id ) ) {
            $data[ 'featured_image' ] = $featured_image_id;

            $image_title = $wpdb->get_var( "SELECT post_title  FROM {$wpdb->prefix}posts WHERE ID = '{$featured_image_id}' " );

            $data[ 'featured_image_title' ] = $image_title;
            $data[ 'featured_image_url' ]   = wp_get_attachment_url( $featured_image_id );
          }

          wp_cache_add( $id, $data, 'property_thumbnail' );

        }

        return $data;
      }

      /**
       * Returns images data for particular property
       *
       * @param $id
       * @param bool $cache
       * @return array
       */
      static public function get_images( $id, $cache = true, $type = 'parent' ) {
        $cache_id = is_array($id) ? md5(json_encode($id)) : $id;
        if( $cache && $data = wp_cache_get( $cache_id, 'property_images_' . $type ) ) {

          // Do nothing here.

        } else {

          $data = array();

          switch ($type) {
            case 'ids':
              $attachments = get_posts( array(
                'post_type' => 'attachment',
                'include'   => (array) $id,
                'post_mime_type' => 'image',
                'orderby' => 'post__in',
                'order' => 'ASC'
              ) );
              foreach ($attachments as $key => $attachment) {
                $attachments[$attachment->ID] = $attachment;
                unset($attachments[$key]);
              }
              break;
              
            case 'parent':
            default:
              $attachments = get_children( array(
                'post_parent' => $id,
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'orderby' => 'menu_order ASC, ID',
                'order' => 'ASC'
              ) );
              break;
          }

          /* Get property images */
          if( !empty( $attachments ) ) {
            $wp_image_sizes = get_intermediate_image_sizes();
            foreach( (array)$attachments as $attachment_id => $attachment ) {
              $data[ $attachment->post_name ][ 'post_title' ]    = $attachment->post_title;
              $data[ $attachment->post_name ][ 'post_excerpt' ]  = $attachment->post_excerpt;
              $data[ $attachment->post_name ][ 'post_content' ]  = $attachment->post_content;
              $data[ $attachment->post_name ][ 'menu_order' ]  = $attachment->menu_order;
              $data[ $attachment->post_name ][ 'attachment_id' ] = $attachment_id;
              foreach( $wp_image_sizes as $image_name ) {
                $this_url = wp_get_attachment_image_src( $attachment_id, $image_name, true );
                $data[ $attachment->post_name ][ $image_name ] = $this_url[ 0 ];
              }
            }
          }

          wp_cache_add( $cache_id, $data, 'property_images_' . $type );

        }

        return $data;
      }

      /**
       * Flush all caches for particular property.
       *
       * @since 2.1.1
       * @param $id
       */
      static public function flush_cache( $id ) {
        wp_cache_delete( $id );
        wp_cache_delete( $id, 'property_parent' );
        wp_cache_delete( $id, 'property_children' );
        wp_cache_delete( $id, 'property_thumbnail' );
        wp_cache_delete( $id, 'property_images' );
      }

    }

  }

}
