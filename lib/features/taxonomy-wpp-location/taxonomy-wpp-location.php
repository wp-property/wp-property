<?php
/**
 *  Adds [wpp_location] taxonomy.
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Taxonomy_WPP_Location' ) ) {

    class Taxonomy_WPP_Location {

      /**
       * Loads WPP_Location Taxonomy stuff
       */
      public function __construct(){

        // Break, if disabled.
        if ( !WPP_FEATURE_FLAG_WPP_LISTING_LOCATION ) {
          return;
        }

        // Register taxonomy.
        add_filter('wpp_taxonomies', array( $this, 'define_taxonomies'), 10 );

        // Handle importing data related to [wpp_location] taxonomy ( WP-RETS-Client )
        // @TODO logic is moved to api.rets.ci, so after checking it can be removed
        //add_filter('retsci::insert_property_terms::handle', array( $this, 'retsci_insert_property_terms' ), 10, 3);

        // Handle revalidate address
        add_filter( 'wpp::revalidate_address::return', array( $this, 'revalidate_address' ), 10, 2 );

      }

      /**
       * Register WPP_Location Taxonomy
       *
       * @param array $taxonomies
       * @return array
       */
      public function define_taxonomies( $taxonomies = array() ) {

        $taxonomies['wpp_location'] = array(
          'default' => true,
          'readonly' => true,
          'system' => true,
          'meta' => true,
          'hidden' => false,
          'hierarchical' => true,
          'public' => true,
          'show_in_nav_menus' => true,
          'show_in_menu' => true,
          'show_ui' => false,
          'show_tagcloud' => false,
          'add_native_mtbox' => false,
          'label' => __('Location', ud_get_wp_property()->domain),
          'labels' => array(
            'name' => __('Locations', ud_get_wp_property()->domain),
            'singular_name' => __('Location', ud_get_wp_property()->domain),
            'search_items' => _x('Search Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'all_items' => _x('All Locations', 'property location taxonomy', ud_get_wp_property()->domain),
            'parent_item' => _x('Parent Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'parent_item_colon' => _x('Parent Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'edit_item' => _x('Edit Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'update_item' => _x('Update Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'add_new_item' => _x('Add New Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'new_item_name' => _x('New Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'not_found' => _x('No location found', 'property location taxonomy', ud_get_wp_property()->domain),
            'menu_name' => __('Locations', ud_get_wp_property()->domain),
          ),
          'query_var' => 'location',
          'rewrite' => array('slug' => 'location')
        );

        return $taxonomies;

      }

      /**
       * @param $return
       * @param $args
       * @return mixed
       */
      public function revalidate_address( $return, $args ) {
        if(  !empty( $args[ 'post_id' ] ) && !empty( $args[ 'geo_data' ] ) ) {
          $return['terms'] = $this->update_location_terms( $args[ 'post_id' ], $args[ 'geo_data' ] );
        }
        return $return;
      }

      /**
       * May be handle inserting property terms on RETS Client data importing
       *
       * @param $handle
       * @param $taxonomy
       * @param $args
       * @return boolean
       */
      public function retsci_insert_property_terms( $handle, $taxonomy, $args ) {

        $ignore = array(
          'rets_location_state',
          'rets_location_county',
          'rets_location_city',
          'rets_location_route',
          'rets_location_neighborhood',
          'rets_location_subdivision',
          'rets_location_zip'
        );

        // Ignore specific taxonomies since [wpp_location] enabled.
        if( in_array( $taxonomy, $ignore ) ) {
          return true;
        }

        if( $taxonomy == 'wpp_listing_location' && !empty( $args[ 'post_id' ] ) && !empty( $args[ 'post_data_tax_input' ] ) ) {
          $_post_data_tax_input = $args['post_data_tax_input'];
          $_geo_tag_fields = array(
            "state" => isset( $_post_data_tax_input["rets_location_state"] ) ? reset( $_post_data_tax_input["rets_location_state"] ) : null,
            "county" => isset( $_post_data_tax_input["rets_location_county"] ) ? reset( $_post_data_tax_input["rets_location_county"] ) : null,
            "city" => isset( $_post_data_tax_input["rets_location_city"] ) ? reset( $_post_data_tax_input["rets_location_city"] ) : null,
            "route" => isset( $_post_data_tax_input["rets_location_route"] ) ? reset( $_post_data_tax_input["rets_location_route"] ) : null,
            "subdivision" => isset( $_post_data_tax_input["rets_location_subdivision"] ) ? reset( $_post_data_tax_input["rets_location_subdivision"] ) : null,
            "neighborhood" => isset( $_post_data_tax_input["rets_location_neighborhood"] ) ? reset( $_post_data_tax_input["rets_location_neighborhood"] ) : null,
            "zip" => isset( $_post_data_tax_input["rets_location_zip"] ) ? reset( $_post_data_tax_input["rets_location_zip"] ) : null,
          );

          $this->update_location_terms( $args[ 'post_id' ], (object) $_geo_tag_fields);

          return true;

        }

        return $handle;

      }

      /**
       * Build terms from address parts.
       *
       * @since 2.2.1
       * @author potanin@UD
       * @param $post_id
       * @param $geo_data
       * @return array
       */
      public function update_location_terms( $post_id, $geo_data ) {

        if( !$geo_data || !is_object( $geo_data ) ) {
          return new WP_Error( 'No [geo_data] argument provided.' );
        }

        $taxonomy = 'wpp_location';

        WPP_F::verify_have_system_taxonomy( $taxonomy );

        $rules = array(
          'state' => array(
            'parent' => false,
            'meta' => array(
              '_type' => 'wpp_location_state'
            )
          ),
          'county' => array(
            'parent' => 'state',
            'meta' => array(
              '_type' => 'wpp_location_county'
            )
          ),
          'city' => array(
            'parent' => 'state',
            'meta' => array(
              '_type' => 'wpp_location_city'
            )
          ),
          'subdivision' => array(
            'parent' => 'city',
            'meta' => array(
              '_type' => 'wpp_location_subdivision'
            )
          ),
          'neighborhood' => array(
            'parent' => 'city',
            'meta' => array(
              '_type' => 'wpp_location_neighborhood'
            )
          ),
          'zip' => array(
            'parent' => 'state',
            'meta' => array(
              '_type' => 'wpp_location_zip'
            )
          ),
          'route' => array(
            'parent' => 'zip',
            'meta' => array(
              '_type' => 'wpp_location_route'
            )
          ),
        );

        $geo_data->terms = array();

        // Set defaults value if some of geo data is missing
        $geo_data->state = !empty($geo_data->state) ? $geo_data->state : 'No State';
        $geo_data->county = !empty($geo_data->county) ? $geo_data->county : 'No County';
        $geo_data->city = !empty($geo_data->city) ? $geo_data->city : 'No City';
        $geo_data->subdivision = !empty($geo_data->subdivision) ? $geo_data->subdivision : 'No Subdivision';
        $geo_data->neighborhood = !empty($geo_data->neighborhood) ? $geo_data->neighborhood : 'No Neighborhood';
        $geo_data->zip = !empty($geo_data->zip) ? $geo_data->zip : 'No Zip';
        $geo_data->route = !empty($geo_data->route) ? $geo_data->route : 'No Route';

        $geo_data->terms['state'] = get_term_by('name', $geo_data->state, $taxonomy, OBJECT);
        $geo_data->terms['county'] = get_term_by('name', $geo_data->county, $taxonomy, OBJECT);
        $geo_data->terms['city'] = get_term_by('name', $geo_data->city, $taxonomy, OBJECT);
        $geo_data->terms['subdivision'] = get_term_by('name', $geo_data->subdivision, $taxonomy, OBJECT);
        $geo_data->terms['neighborhood'] = get_term_by('name', $geo_data->neighborhood, $taxonomy, OBJECT);
        $geo_data->terms['zip'] = get_term_by('name', $geo_data->zip, $taxonomy, OBJECT);
        $geo_data->terms['route'] = get_term_by('name', $geo_data->route, $taxonomy, OBJECT);

        // validate, lookup and add all location terms to object.
        if (isset($geo_data->terms) && is_array($geo_data->terms)) {
          foreach ($geo_data->terms as $_level => $_haveTerm) {

            if ((!$_haveTerm || is_wp_error($_haveTerm)) && isset( $geo_data->{$_level} )) {

              $_value = $geo_data->{$_level};

              $_detail = array();

              $rule = isset( $rules[$_level] ) ? $rules[$_level] : false;

              if( !$rule ) {
                continue;
              }

              if( !empty( $rule[ 'parent' ] ) ) {
                $_detail['description'] = $_value . ' is a ' . $_level . ' within ' . (!empty($geo_data->terms[$rule[ 'parent' ]]) ? $geo_data->terms[$rule[ 'parent' ]]->name : '') . ', a ' . $rule[ 'parent' ] . '.';
                $_detail['parent'] = (!empty($geo_data->terms[$rule[ 'parent' ]]) ? $geo_data->terms[$rule[ 'parent' ]]->term_id : 0);
              } else {
                $_detail['description'] = $_value . ' is a ' . $_level . ' with nothing above it.';
              }

              /*

              $index_key = array_search($_level, array_keys($geo_data->terms), true);
              $_hl = array_slice($geo_data->terms, ($index_key - 1), 1, true);
              $_higher_level = end($_hl);
              $_hln = array_keys(array_slice($geo_data->terms, ($index_key - 1), 1, true));
              $_higher_level_name = end($_hln);

              $_detail = array();

              if ($_higher_level && isset($_higher_level->term_id)) {
                $_detail['description'] = $_value . ' is a ' . $_level . ' within ' . (isset($_higher_level) ? $_higher_level->name : '') . ', a ' . $_higher_level_name . '.';
                $_detail['parent'] = $_higher_level->term_id;
              } else {
                $_detail['description'] = $_value . ' is a ' . $_level . ' with nothing above it.';
              }

              // $_detail[ 'slug' ] = 'city-slug';

              //*/

              // Define slug for location term
              $_detail['slug'] = sanitize_title( $_value );

              // Add prefix for county slug for prevent duplicates with city
              if( $_level === 'county' ) {
                $_detail['slug'] = 'county_' . sanitize_title( $_value );
              }

              $_inserted_term = wp_insert_term( $_value, 'wpp_location', $_detail );

              if (!is_wp_error($_inserted_term) && isset($_inserted_term['term_id'])) {
                $geo_data->terms[$_level] = get_term_by('term_id', $_inserted_term['term_id'], 'wpp_location', OBJECT);

                // Set meta data if rule for particular item contain it.
                if( !empty( $rule[ 'meta' ] ) ) {
                  foreach( $rule[ 'meta' ] as $meta_key => $meta_value ) {
                    add_term_meta( $_inserted_term['term_id'], $meta_key, $meta_value, true );
                  }
                }

              } else {
                error_log('Could not insert [wpp_location] term [' . $_value . '], error: [' . $_inserted_term->get_error_message() . ']');
              }

            }

          }

          $_location_terms = array();

          foreach ($geo_data->terms as $_term_hopefully) {
            if (isset($_term_hopefully->term_id)) {
              $_location_terms[] = $_term_hopefully->term_id;
            }
          }

          // write, ovewriting any settings from before
          wp_set_object_terms($post_id, $_location_terms, $taxonomy, false);

        }

        return $geo_data->terms;

      }


    }

  }

}
