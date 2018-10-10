<?php
/**
 * WP-Property General Functions
 *
 * Contains all the general functions used by the plugin.
 *
 * @version 1.00
 * @author Andy Potanin <andy.potanin@twincitiestech.com>
 * @package WP-Property
 * @subpackage Functions
 */
class WPP_F extends UsabilityDynamics\Utility
{

  /**
   * Get term met values.
   *
   * @param $term
   * @param array $fields - array of fields to return
   * @return array
   */
  static public function get_term_metadata( $term, $fields = array() ) {

    //$_attribute_data = UsabilityDynamics\WPP\Attributes::get_attribute_data( $term->slug, array( 'use_cache' => false ) );

    if( is_array( $term ) ) {
      $term = (object) $term;
    }

    $_taxonomy = (array) get_taxonomy( $term->taxonomy );

    $_type_prefix = get_term_meta( $term->term_id, '_type', true );

    $_meta = array();

    $_meta[ 'term_id' ] = get_term_meta( $term->term_id, '_id', true );
    $_meta[ 'term_type' ] = get_term_meta( $term->term_id, '_type', true );

    if( isset( $_taxonomy[ 'wpp_term_meta_fields' ] ) ) {
      foreach( (array) $_taxonomy[ 'wpp_term_meta_fields' ] as $_meta_field ) {
        $_slug = str_replace( $term->taxonomy . '_', '', $_meta_field[ 'slug' ] );
        $_meta_slug = $_type_prefix . '-' . $_slug;
        $_meta[ $_meta_field[ 'slug' ] ] = get_term_meta( $term->term_id, $_meta_slug, true );
      }
    }

    return array_filter( $_meta );

  }

  /**
   * Insert Term.
   *
   * - If term exists, we update it. Otherwise its created.
   * - All extra fields are inserted as term meta.
   *
   *
   * @author potanin@UD
   *
   * @param $term_data
   * @param $term_data._id - Unique ID, stored in term meta. Usually source ID. Native term_id used if not provided.
   * @param $term_data._type - Taxonomy.
   * @param $term_data._parent - ID of parent.
   * @param $term_data.name - Print-friendly name of term.
   * @param $term_data.slug - Optional, used for URL..
   * @param $term_data.meta - Array, addedd to term_meta;
   * @param $term_data.description - String. Default description of term.
   * @param $term_data.post_meta - Array, added to post meta, extends relationship between term and post.
   * @param $term_data.post_meta.description - Will overwrite term description for a post.
   * @return array|WP_Error
   */
  static public function insert_term( $term_data ) {
    global $wpdb;

    $result = array(
      '_id' => null,
      '_type' => null,
      'meta' => array(),
      'errors' => array()
    );

    $term_data = apply_filters( 'wpp:insert_term', $term_data );

    if( !isset( $term_data['_type'] )) {
      return new \WP_Error( 'missing-type' );
    }

    // Use _type for _taxonomy if not provided.
    if( !isset( $term_data['_taxonomy'] ) && isset( $term_data['_type'] ) ) {
      $term_data['_taxonomy'] = $term_data['_type'];
    }

    // try to find by [_id]
    if( isset( $term_data[ '_id' ] ) &&  $term_data[ '_id' ] ) {

      $term_data['term_id'] = intval( $wpdb->get_var($wpdb->prepare("
            SELECT tm.term_id
              FROM $wpdb->termmeta as tm
              LEFT JOIN $wpdb->term_taxonomy as tt ON tt.term_id = tm.term_id
              WHERE tm.meta_key=%s AND tm.meta_value=%s AND tt.taxonomy=%s;
          ", array( '_id', $term_data['_id'], $term_data['_taxonomy']))));

    }

    // Parent set, try to find it.
    if( isset( $term_data[ '_parent' ] ) && $term_data[ '_parent' ] ) {

      $term_data['meta']['parent_term_id'] = intval( $wpdb->get_var($wpdb->prepare("
            SELECT tm.term_id
              FROM $wpdb->termmeta as tm
              LEFT JOIN $wpdb->term_taxonomy as tt ON tt.term_id = tm.term_id
              WHERE tm.meta_key=%s AND tm.meta_value=%s AND tt.taxonomy=%s;
          ", array( '_id', $term_data['_parent'], $term_data['_taxonomy']))) );

      // Parent not found, we try finding it using slug as [_parent].
      if( !$term_data['meta']['parent_term_id'] ) {
        $_exists = get_term_by( 'slug', $term_data['meta']['_parent'], $term_data['_taxonomy'], ARRAY_A );
        // If parent found, we record the actual [term_id] of parent into our main term's meta.
        if( $_exists ) {
          $term_data['meta']['parent_term_id'] = $_exists['term_id'];
        }
      }

      // Parent could not be found, we will attempt to create.
      if( !$term_data['meta']['parent_term_id'] ) {
        // Insert new term, using the parent's name, from main terms' meta.
        $_parent_term = self::insert_term( array(
          '_id' => $term_data[ '_parent' ],
          '_type' => $term_data[ '_type' ],
          '_taxonomy' => $term_data[ '_taxonomy' ],
          'name' => $term_data[ '_parent' ],
        ) );
        // If parent still could not be created, then we have a serious error. Otherwise, update the parent's meta.
        if( isset( $_parent_term ) && is_wp_error( $_parent_term ) ) {
          error_log( "Unable to insert parent term [" . $term_data['_parent'] . "] for taxonomy [" . $term_data['_taxonomy']. "]." );
          error_log( print_r($_parent_term,true));
        } else {
          $term_data['meta']['parent_term_id'] = $_parent_term['term_id'];
        }
      }

    }

    // try to get by [name]
    if( !$term_data['term_id'] ) {
      $_exists = term_exists( $term_data['name'], $term_data['_taxonomy'] );
      if(  $_exists && isset( $term_data[ '_id' ] ) ) {
        // So we should add prefix to our slug here
        $slug = sanitize_title( $term_data[ 'name' ] );
        if( get_term_by( 'slug', $slug, $term_data['_taxonomy']  ) ) {
          $prefix = !empty( $term_data[ '_type' ] ) ? $term_data[ '_type' ] : rand( 1000, 9999);
          $slug = sanitize_title( $prefix. '-' . $slug );
        }
      }
      else if( $_exists ) {
        $term_data['term_id'] = $_exists['term_id'];
      }
    }

    // Term not found, new term
    if( !$term_data['term_id'] ) {
      $_term_args = array( 'description' => isset( $term_data['description'] ) ? $term_data['description'] : null );
      if( isset( $term_data['meta']['parent_term_id'] ) ) {
        $_term_args['parent']  = $term_data['meta']['parent_term_id'];
      }

      // Set term slug if it exist
      if(isset($slug)){
        $_term_args['slug'] = $slug;
      }

      $_term_created = wp_insert_term( $term_data['name'], $term_data['_taxonomy'], $_term_args);
      if( isset( $_term_created ) && is_wp_error( $_term_created ) ) {
        error_log( "Unable to insert term [" . $term_data['_taxonomy']. "]." );
        return $_term_created;
      }
      $term_data['term_id'] = $_term_created['term_id'];
    } else {
      $_exists = get_term( $term_data[ 'term_id'], $term_data['_taxonomy'], ARRAY_A );
      $_existings_metadata = get_term_meta( $term_data[ 'term_id'] );
    }

    // Could not create term.
    if( !isset( $term_data[ 'term_id'] ) ) {
      // error_log( '$_term_created' . print_r($_term_created,true ));
      // error_log( '$term_data' . print_r($term_data,true ));
      return new WP_Error('unable-to-create-term', 'Can not create term.' );
    }

    $result['_type'] = $term_data['_type'];
    $result['_taxonomy'] = $term_data['_taxonomy'];
    $result['_created'] = isset( $_exists ) && isset( $_exists['term_id'] ) ? false : true;
    $result['term_id'] = $term_data['term_id'];

    if( isset( $term_data['meta']['parent_term_id'] ) ) {
      $result['parent'] = $term_data['meta']['parent_term_id'];
    }

    $result['name'] = isset( $term_data['name'] ) ? $term_data['name'] : null;
    $result['slug'] = isset( $term_data['slug'] ) ? $term_data['slug'] : sanitize_title( $term_data['name'] );
    $term_data[ 'meta' ]['_id'] = $term_data[ '_id' ];
    $result['updated'] = array();

    // set _id
    if( !isset( $_existings_metadata['_id'] ) || ( isset( $_existings_metadata['_id'][0] ) && $_existings_metadata['_id'][0] !== $term_data[ '_id' ] ) ) {
      update_term_meta($term_data['term_id'], '_id', $term_data[ '_id' ] );
      $result['updated'][] = '_id';
    }

    // set _type, same as _prefix, I suppose.
    if( !isset( $_existings_metadata['_type'] ) || ( isset( $_existings_metadata['_type'][0] ) && $_existings_metadata['_type'][0] !== $term_data[ '_type' ] ) ) {
      update_term_meta( $term_data[ 'term_id' ], '_type', $term_data[ '_type' ] );
      $result['updated'][] = '_type';
    }

    // This is most likely going to be removed.
    if( $result['_created'] ) {
      update_term_meta( $term_data['term_id'], '_created', time() );
    }

    foreach( $term_data[ 'meta' ] as $_meta_key => $meta_value ) {
      if( $_meta_key === '_id' ) { continue; }
      if( $_meta_key === 'parent_slug' ) { continue; }
      if( $_meta_key === 'parent_name' ) { continue; }
      if( $_meta_key === 'parent_term_id' ) { continue; }
      $_term_meta_key = $term_data[ '_type' ] . '-' . $_meta_key;
      if( !isset( $_existings_metadata[ $_term_meta_key ] ) || ( isset( $_existings_metadata[$_term_meta_key][0] ) && $_existings_metadata[$_term_meta_key][0] !== $meta_value ) ) {
        update_term_meta( $term_data[ 'term_id' ], $_term_meta_key, $meta_value );
        $result['updated'][] = $_term_meta_key;
      }
      $result['meta'][ ( $term_data['_type'] . '-' . $_meta_key ) ] = $meta_value;
    }

    $_term_update_detail = array_filter(array(
      'name' => isset( $term_data['name'] ) ? $term_data['name'] : null,
      'slug' => isset( $term_data['slug'] ) ? $term_data['slug'] : null,
      'parent' => isset( $result['parent'] ) ? $result['parent'] : null,
      'description' => isset( $term_data['description'] ) ? $term_data['description'] : null,
      'term_group' => isset( $term_data['term_group'] ) ? $term_data['term_group'] : null
    ));

    // If term already exists, we allow to use already existing name
    // So, administrator has ability to re-name Term label
    // Note: it makes sense ONLY if term has system '_id'
    if( $_exists && isset( $_exists['name'] ) && isset( $_term_update_detail[ 'name' ] ) ) {
      unset( $_term_update_detail['name']);
    }

    // If term already exists, we allow to use already existing slug
    // So, administrator has ability to re-name Term slug
    // Note: it makes sense ONLY if term has system '_id'
    if( $_exists && isset( $_exists['slug'] ) && isset( $_term_update_detail[ 'slug' ] ) ) {
      unset( $_term_update_detail['slug']);
    }

    if( $_exists && isset( $_exists['description'] ) &&  isset( $_term_update_detail[ 'description' ] ) && $_exists['description'] == $_term_update_detail[ 'description' ]) {
      unset( $_term_update_detail['description']);
    }

    if( $_exists && isset( $_exists['parent'] ) && isset( $_term_update_detail[ 'parent' ] ) && $_exists['parent'] == $_term_update_detail[ 'parent' ]) {
      unset( $_term_update_detail['parent']);
    }

    // Update other fields.
    if( !empty( $_term_update_detail ) ) {
      wp_update_term( $term_data['term_id'], $term_data['_taxonomy'], array_filter( $_term_update_detail ));
      $result['updated'] = array_merge( $result['updated'], $_term_update_detail );
    }

    if( isset( $result['updated'] ) && !empty( $result['updated'] ) ) {
      $result['_updated'] = time();
      update_term_meta( $term_data['term_id'], '_updated', $result['_updated'] );
      //error_log( '$result ' . print_r( $result, true ));
      //error_log( '$_existings_metadata ' . print_r( $_existings_metadata, true ));
    }

    return array_filter( $result );
  }

  /**
   * Insert Multiple Terms
   *
   * @author potanin@UD
   * @param $object_id
   * @param $terms - An array of terms.
   * @param array $defaults
   * @return array
   */
  static public function insert_terms( $object_id, $terms, $defaults = array() ) {

    $_terms = array();

    $_results = array(
      'post_id' => $object_id,
      'set_terms' => array(),
      'terms' => array(),
      'meta_override' => array(),
      'errors' => array()
    );

    foreach( $terms as $_index => $_term ) {

      $_terms[ $_index ] = self::insert_term( array_merge( (array) $_term, (array) $defaults ));

      if( is_wp_error( $_terms[ $_index ] ) ) {
        $_results['errors'][] = array( $_term, $_terms[ $_index ] );
        unset( $_terms[ $_index ] );
        continue;
      }

      if( !is_wp_error( $_terms[ $_index ] ) ) {

        $_results['set_terms'] = array_merge( $_results['set_terms'], wp_set_object_terms( $object_id, intval( $_terms[ $_index ]['term_id'] ), $_terms[ $_index ]['_taxonomy'], true ) );

        if( isset( $_term['post_meta'] ) ) {
          $_results['meta_override'][] = update_post_meta( $object_id, '_wpp_term_meta_override', $_term['post_meta'] );
        }

      } else {
        $_results[ 'errors' ][] = array( $_term, $_terms[ $_index ] );
      }

    }

    $_results[ 'terms' ] = $_terms;

    if( empty( $_results['meta_override'] )) {
      unset( $_results['meta_override'] );
    }

    if( empty( $_results['errors'] )) {
      unset( $_results['errors'] );
    } else {
      error_log( 'WP-Properrty Errors creating terms: ' . print_r($_results['errors'],true) );
    }

    return $_results;

  }

  /**
   * Registers a system taxonomy if needed with most essential arguments.
   *
   * @since 2.2.1
   * @author potanin@UD
   * @param string $taxonomy
   * @param array $args
   * @return string
   */
  static public function verify_have_system_taxonomy($taxonomy = '', $args = array())
  {

    $args = wp_parse_args($args, array(
      'hierarchical' => true
    ));

    if (taxonomy_exists($taxonomy)) {
      return $taxonomy;
    }

    register_taxonomy( substr( $taxonomy, 0, 32 ), array( 'property' ), array(
      'hierarchical' => $args['hierarchical'],
      // 'update_count_callback' => null,
      'labels' => array(),
      'show_ui' => false,
      'show_in_menu' => false,
      'show_admin_column' => false,
      'meta_box_cb' => false,
      'query_var' => false,
      'rewrite' => false
    ));

    if (taxonomy_exists($taxonomy)) {
      return $taxonomy;
    } else {
      return false;
    }

  }

  /**
   * This function grabs the API key from UD's servers
   *
   * @updated 1.36.0
   */
  static public function get_api_key($args = false)
  {

    $args = wp_parse_args($args, array(
      'force_check' => false
    ));

    //** check if API key already exists */
    $ud_api_key = get_option('ud_api_key');

    //** if key exists, and we are not focing a check, return what we have */
    if ($ud_api_key && !$args['force_check']) {
      return $ud_api_key;
    }

    $blogname = get_bloginfo('url');
    $blogname = urlencode(str_replace(array('http://', 'https://'), '', $blogname));
    $system = 'wpp';
    $wpp_version = get_option("wpp_version");

    $check_url = "http://updates.usabilitydynamics.com/key_generator.php?system=$system&site=$blogname&system_version=$wpp_version";

    $response = @wp_remote_get($check_url);

    if (!$response) {
      return false;
    }

    // Check for errors
    if (is_wp_error($response)) {
      WPP_F::log('API Check Error: ' . $response->get_error_message());

      return false;
    }

    // Quit if failture
    if ($response['response']['code'] != '200') {
      return false;
    }

    $response['body'] = trim($response['body']);

    //** If return is not in MD5 format, it is an error */
    if (strlen($response['body']) != 40) {

      if ($args['return']) {
        return $response['body'];
      } else {
        WPP_F::log("API Check Error: " . sprintf(__('An error occurred during API key request: <b>%s</b>.', ud_get_wp_property()->domain), $response['body']));

        return false;
      }
    }

    //** update wpp_key is DB */
    update_option('ud_api_key', $response['body']);

    // Go ahead and return, it should just be the API key
    return $response['body'];

  }

  /**
   * Wrapper for the UD_API::log() function that includes the prefix automatically.
   *
   * @author peshkov@UD
   *
   * @param bool $message
   * @param string $type
   * @param bool $object
   * @param array $args
   *
   * @return boolean
   */
  static public function log($message = false, $type = 'default', $object = false, $args = array())
  {
    $args = wp_parse_args((array)$args, array(
      'type' => $type,
      'object' => $object,
      'instance' => 'WP-Property',
    ));

    return parent::log($message, $args);
  }

  /**
   * Get the label for "Property"
   *
   * @since 1.10
   * @param string $type
   * @return string|void
   */
  static public function property_label($type = 'singular')
  {
    global $wp_post_types;
    $label = '';

    if ($type == 'plural') {
      $label = (!empty($wp_post_types['property']->labels->name) ? $wp_post_types['property']->labels->name : __('Properties'));
    }

    if ($type == 'singular') {
      $label = (!empty($wp_post_types['property']->labels->singular_name) ? $wp_post_types['property']->labels->singular_name : __('Property'));
    }
    $label = apply_filters('property_label', $label, $type);
    return $label;

  }

  /**
   * Setup widgets and widget areas.
   *
   * @since 1.31.0
   *
   */
  static public function widgets_init()
  {
    global $wp_properties;

    //** Register a sidebar for each property type */
    if (
      !isset($wp_properties['configuration']['do_not_register_sidebars']) ||
      (isset($wp_properties['configuration']['do_not_register_sidebars']) && $wp_properties['configuration']['do_not_register_sidebars'] != 'true')
    ) {
      foreach ((array)$wp_properties['property_types'] as $property_slug => $property_title) {

        $disabled = ud_get_wp_property('configuration.disable_widgets.wpp_sidebar_' . $property_slug);

        if (!$disabled || $disabled !== 'true') {
          register_sidebar(array(
            'name' => sprintf(__('%s: %s', ud_get_wp_property()->domain), WPP_F::property_label(), $property_title),
            'id' => "wpp_sidebar_{$property_slug}",
            'description' => sprintf(__('Sidebar located on the %s page.', ud_get_wp_property()->domain), $property_title),
            'before_widget' => '<li id="%1$s"  class="wpp_widget %2$s">',
            'after_widget' => '</li>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
          ));
        }

      }
    }
  }

  /**
   * Useful Taxonomies with extra handlers.
   *
   * @return array
   */
  static public function wpp_commom_taxonomies() {

    $taxonomies = array();

    // Add [property_features] and [community_features] taxonomies.
    if( WP_PROPERTY_FLAG_ENABLE_LEGACY_TAXONOMIES ) {

      $taxonomies[ 'property_feature' ] = array(
        'default' => true,
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'add_native_mtbox' => true,
        'label' => _x( 'Features', 'taxonomy general name', ud_get_wp_property()->domain ),
        'labels' => array(
          'name' => _x( 'Features', 'taxonomy general name', ud_get_wp_property()->domain ),
          'singular_name' => _x( 'Feature', 'taxonomy singular name', ud_get_wp_property()->domain ),
          'search_items' => __( 'Search Features', ud_get_wp_property()->domain ),
          'all_items' => __( 'All Features', ud_get_wp_property()->domain ),
          'parent_item' => __( 'Parent Feature', ud_get_wp_property()->domain ),
          'parent_item_colon' => __( 'Parent Feature:', ud_get_wp_property()->domain ),
          'edit_item' => __( 'Edit Feature', ud_get_wp_property()->domain ),
          'update_item' => __( 'Update Feature', ud_get_wp_property()->domain ),
          'add_new_item' => __( 'Add New Feature', ud_get_wp_property()->domain ),
          'new_item_name' => __( 'New Feature Name', ud_get_wp_property()->domain ),
          'menu_name' => __( 'Feature', ud_get_wp_property()->domain )
        ),
        'query_var' => 'property_feature',
        'rewrite' => array( 'slug' => 'feature' )
      );

      $taxonomies[ 'community_feature' ] = array(
        'default' => true,
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'add_native_mtbox' => true,
        'label' => _x( 'Community Features', 'taxonomy general name', ud_get_wp_property()->domain ),
        'labels' => array(
          'name' => _x( 'Community Features', 'taxonomy general name', ud_get_wp_property()->domain ),
          'singular_name' => _x( 'Community Feature', 'taxonomy singular name', ud_get_wp_property()->domain ),
          'search_items' => __( 'Search Community Features', ud_get_wp_property()->domain ),
          'all_items' => __( 'All Community Features', ud_get_wp_property()->domain ),
          'parent_item' => __( 'Parent Community Feature', ud_get_wp_property()->domain ),
          'parent_item_colon' => __( 'Parent Community Feature:', ud_get_wp_property()->domain ),
          'edit_item' => __( 'Edit Community Feature', ud_get_wp_property()->domain ),
          'update_item' => __( 'Update Community Feature', ud_get_wp_property()->domain ),
          'add_new_item' => __( 'Add New Community Feature', ud_get_wp_property()->domain ),
          'new_item_name' => __( 'New Community Feature Name', ud_get_wp_property()->domain ),
          'menu_name' => __( 'Community Feature', ud_get_wp_property()->domain )
        ),
        'query_var' => 'community_feature',
        'rewrite' => array( 'slug' => 'community_feature' )
      );

    }

    return $taxonomies;

  }

  /**
   * Modify Taxonomy Query.
   *
   * Looks in termmeta table for "url_path" values.
   * For this to work the custom "wpp_listing_category" rewrite is requited that does not break terms by slash, as done by native term matching.
   *
   *
   * @todo Convert lookup to use "slug" after replacing all slahes with dashes.
   *
   * @note The $wp_query->query_vars includes original value of term.
   * @note Could/should modify queried_terms as well. - potanin@UD
   * @param $context
   */
  static public function parse_tax_query( $context ) {
    global $wpdb;

    if( !isset( $context->tax_query ) || !isset( $context->tax_query->queries ) ) {
      return;
    }

    foreach( (array) $context->tax_query->queries as $_index => $_query ) {

      if( isset( $_query ) && isset( $_query['taxonomy'] ) && $_query['taxonomy'] === 'wpp_listing_category' && isset( $context->query ) && isset( $context->query[ $_query['taxonomy'] ] ) ) {

        $_meta_value = '/' . $context->query[ $_query['taxonomy'] ] . '/';

        $_term_id = wp_cache_get( $_meta_value, 'wpp_listing_category_term_query' );

        if( !$_term_id ) {
          $_sql_query = $wpdb->prepare( "SELECT term_id FROM $wpdb->termmeta WHERE meta_key='listing-category-url_path' AND meta_value='%s';", $_meta_value );

          // Get term_id by "url_path".
          $_term_id = $wpdb->get_var( $_sql_query );

          wp_cache_set( $_meta_value, $_term_id, 'wpp_listing_category_term_query' );
        }

        // Change search field to term_id if we found a match, and override search terms.
        if( $_term_id ) {
          $context->tax_query->queries[$_index]['field'] = 'term_id';
          $context->tax_query->queries[$_index]['terms'] = array( $_term_id );
          $context->tax_query->queries[$_index]['_extended'] = 'wpp_listing_category_helper';

          // Have to set this so get_queried_object() works.
          $context->tax_query->queried_terms['wpp_listing_category']['terms'] = array($_term_id);
          $context->tax_query->queried_terms['wpp_listing_category']['field'] ='term_id';

        }

      }

    }

    //die( '<pre>' . print_r( $_sql_query, true ) . '</pre>' );
  }

  /**
   * Registers post types and taxonomies.
   *
   * @since 1.31.0
   *
   */
  static public function register_post_type_and_taxonomies()
  {
    global $wp_properties;

    // @note legacy taxonomies. (perhaps disable for new installers, but make available via some option)
    add_filter('wpp_taxonomies', array('WPP_F', 'wpp_commom_taxonomies'), 4 );

    if (WPP_FEATURE_FLAG_WPP_LISTING_CATEGORY) {
      add_action( 'parse_tax_query', array( 'WPP_F', 'parse_tax_query' ), 50 );
    }

    // Setup taxonomies
    $wp_properties['taxonomies'] = apply_filters('wpp_taxonomies', array());

    ud_get_wp_property()->set('taxonomies', $wp_properties['taxonomies']);

    ud_get_wp_property()->set('labels', apply_filters('wpp_object_labels', array(
      'name' => __('Properties', ud_get_wp_property()->domain),
      'all_items' => __('All Properties', ud_get_wp_property()->domain),
      'singular_name' => __('Property', ud_get_wp_property()->domain),
      'add_new' => __('Add Property', ud_get_wp_property()->domain),
      'add_new_item' => __('Add New Property', ud_get_wp_property()->domain),
      'edit_item' => __('Edit Property', ud_get_wp_property()->domain),
      'new_item' => __('New Property', ud_get_wp_property()->domain),
      'view_item' => __('View Property', ud_get_wp_property()->domain),
      'search_items' => __('Search Properties', ud_get_wp_property()->domain),
      'not_found' => __('No properties found', ud_get_wp_property()->domain),
      'not_found_in_trash' => __('No properties found in Trash', ud_get_wp_property()->domain),
      'parent_item_colon' => ''
    )));

    $supports = array('title', 'editor', 'thumbnail');

    if( WPP_FEATURE_FLAG_DISABLE_EDITOR ) {
      $supports = array('title', 'thumbnail');
    } else {
      $supports = array('title', 'editor', 'thumbnail');
    }

    if (isset($wp_properties['configuration']['enable_revisions']) && $wp_properties['configuration']['enable_revisions'] == 'true') {
      array_push($supports, 'revisions');
    }

    if (isset($wp_properties['configuration']['enable_comments']) && $wp_properties['configuration']['enable_comments'] == 'true') {
      array_push($supports, 'comments');
    }

    // Register custom post types
    register_post_type('property', apply_filters('wpp_post_type', array(
      'labels' => ud_get_wp_property('labels'),
      'public' => true,
      'exclude_from_search' => (isset($wp_properties['configuration']['exclude_from_regular_search_results']) && $wp_properties['configuration']['exclude_from_regular_search_results'] == 'true' ? true : false),
      'show_ui' => true,
      '_edit_link' => 'post.php?post=%d',
      'capability_type' => array('wpp_property', 'wpp_properties'),
      'capabilities' => array(
            'create_posts' => 'create_wpp_properties',
            'edit_published_posts' => 'edit_wpp_properties',
            'delete_published_posts' => 'delete_wpp_properties',
        ),
      'map_meta_cap' => true,
      'hierarchical' => true,
      'rewrite' => array(
        'slug' => $wp_properties['configuration']['base_slug']
      ),
      'query_var' => $wp_properties['configuration']['base_slug'],
      'supports' => $supports,
      'menu_icon' => 'dashicons-admin-home'
    )));

    if (!empty($wp_properties['taxonomies']) && is_array($wp_properties['taxonomies'])) {
      foreach ((array)$wp_properties['taxonomies'] as $taxonomy => $data) {

        if (!isset($data['show_ui'])) {
          $data['show_ui'] = (current_user_can('manage_wpp_categories') ? true : false);
        }

        register_taxonomy($taxonomy, 'property', $wp_properties['taxonomies'][$taxonomy] = apply_filters('wpp::register_taxonomy', array(
          'hierarchical' => isset($data['hierarchical']) ? $data['hierarchical'] : false,
          'label' => isset($data['label']) ? $data['label'] : $taxonomy,
          'labels' => isset($data['labels']) ? $data['labels'] : array(),
          'query_var' => $taxonomy,
          'rewrite' => isset($data['rewrite']) ? $data['rewrite'] : array('slug' => $taxonomy),
          'public' => isset($data['public']) ? $data['public'] : true,
          'show_ui' => isset($data['show_ui']) ? $data['show_ui'] : true,
          'show_in_nav_menus' => isset($data['show_in_nav_menus']) ? $data['show_in_nav_menus'] : true,
          'show_tagcloud' => isset($data['show_tagcloud']) ? $data['show_tagcloud'] : true,
          'update_count_callback' => '_update_post_term_count',
          'wpp_term_meta_fields' => isset($data['wpp_term_meta_fields']) ? $data['wpp_term_meta_fields'] : null,
          'capabilities' => array(
            'manage_terms' => 'manage_wpp_categories',
            'edit_terms' => 'manage_wpp_categories',
            'delete_terms' => 'manage_wpp_categories',
            'assign_terms' => 'manage_wpp_categories'
          )
        ), $taxonomy));

      }
    }

  }

  /**
   * Create a new default page for properites
   * Default can be changed from the WPP settings page
   * author Raj
   */
  static public function register_properties_page()
  {
    global $wp_properties;

    //check if Properties page existed
    $pageName = "Properties";
    if (!get_page_by_path($pageName) && !get_page_by_path($wp_properties['configuration']['base_slug'])) {
      $new_page = array(
        'post_type' => 'page',
        'post_title' => $pageName,
        'post_content' => '[property_overview]',
        'post_status' => 'publish',
        'post_author' => 1,
      );
      $new_page_id = wp_insert_post($new_page);
      $post = get_post($new_page_id);
      $slug = $post->post_name;
      $wp_properties['configuration']['base_slug'] = $slug;
      update_option('wpp_settings', $wp_properties);
    }
  }

  /**
   * Loads applicable WP-Property scripts and styles
   *
   * @since 1.10
   * @param array $types
   */
  static public function load_assets($types = array())
  {
    global $post, $property, $wp_properties;

    add_action('wp_enqueue_scripts', function(){wp_enqueue_script('jquery-ui-slider');});
    add_action('wp_enqueue_scripts', function(){wp_enqueue_script('jquery-ui-mouse');});
    add_action('wp_enqueue_scripts', function(){wp_enqueue_script('jquery-ui-widget');});
    add_action('wp_enqueue_scripts', function(){wp_enqueue_script('wpp-jquery-fancybox');});
    add_action('wp_enqueue_scripts', function(){wp_enqueue_script('wpp-jquery-address');});
    add_action('wp_enqueue_scripts', function(){wp_enqueue_script('wpp-jquery-scrollTo');});
    add_action('wp_enqueue_scripts', function(){wp_enqueue_script('wp-property-frontend');});
    wp_enqueue_style('wpp-jquery-fancybox-css');
    wp_enqueue_style('jquery-ui');

    foreach ($types as $type) {

      switch ($type) {

        case 'single':

          if (!isset($wp_properties['configuration']['do_not_use']['locations']) || $wp_properties['configuration']['do_not_use']['locations'] != 'true') {
            add_action('wp_enqueue_scripts', function(){wp_enqueue_script('google-maps');});
          }

          break;

        case 'overview':

          break;

      }

    }

  }

  /**
   * Returns attribute information.
   *
   * @see UsabilityDynamics\WPP\Attributes::get_attribute_data()
   * @internal Probably will be removed in next releases.
   * @param bool $attribute
   * @return mixed
   */
  static function get_attribute_data($attribute = false)
  {
    return UsabilityDynamics\WPP\Attributes::get_attribute_data($attribute);
  }

  /**
   * Returns valid attribute type.
   *
   * @see UsabilityDynamics\WPP\Attributes::get_valid_attribute_type()
   * @param bool $type //ud_get_wp_property()->set( 'attributes.types'
   * @return mixed
   */
  static function get_valid_attribute_type($type = false)
  {
    return UsabilityDynamics\WPP\Attributes::get_valid_attribute_type($type);
  }

  static function is_attribute_multi($attribute)
  {
    return UsabilityDynamics\WPP\Attributes::is_attribute_multi($attribute);
  }

  /**
   * Checks if script or style have been loaded.
   *
   * @todo Add handler for styles.
   * @since Denali 3.0
   *
   */
  static public function is_asset_loaded($handle = false)
  {
    global $wp_scripts;

    if (empty($handle)) {
      return;
    }

    $footer = (array)$wp_scripts->in_footer;
    $done = (array)$wp_scripts->done;
    $queue = (array)$wp_scripts->queue;

    $accepted = array_merge($footer, $done, $queue);

    if (!in_array($handle, $accepted)) {
      return false;
    }

    return true;

  }

  /**
   * ChromePHP Logger
   *
   * @param bool $text
   * @param null $detail
   * @return bool|void
   */
  static public function debug($text = false, $detail = null)
  {

    global $wp_properties;

    $_debug = false;

    if( defined( 'WP_DEBUG' ) && WP_DEBUG && ( ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) || ( defined( 'WP_DEBUG_CONSOLE' ) && WP_DEBUG_CONSOLE ) ) ) {
      $_debug = true;
    }

    if ( !$_debug && ( !isset($wp_properties['configuration']['developer_mode']) || $wp_properties['configuration']['developer_mode'] !== 'true') ) {
      $_debug = false;
    }

    if($_debug && class_exists( 'ChromePhp' ) && !headers_sent() ) {

      // truncate strings to avoid sending oversized header.
      if( strlen( $text ) > 1000 ) {
        $text = '[truncated]';
      }

      if( $detail ) {
        ChromePhp::log( '[wp-property]', $text, $detail);
      } else {
        ChromePhp::log( '[wp-property]', $text );
      }

      return true;
    }

    return false;

  }

  /**
   * PHP function to echoing a message to JS console
   *
   * @since 1.32.0
   */
  static public function console_log( $text = false ) {
    self::debug( $text );
    return;
  }

  /**
   * Tests if remote script or CSS file can be opened prior to sending it to browser
   *
   *
   * @version 1.26.0
   */
  static public function can_get_script($url = false, $args = array())
  {
    global $wp_properties;

    if (empty($url)) {
      return false;
    }

    $match = false;

    if (empty($args)) {
      $args['timeout'] = 10;
    }

    $result = wp_remote_get($url, $args);
    if (is_wp_error($result)) {
      return false;
    }

    $type = $result['headers']['content-type'];

    if (strpos($type, 'javascript') !== false) {
      $match = true;
    }

    if (strpos($type, 'css') !== false) {
      $match = true;
    }

    if (!$match || $result['response']['code'] != 200) {

      if ($wp_properties['configuration']['developer_mode'] == 'true') {
        WPP_F::console_log("Remote asset ($url) could not be loaded, content type returned: " . $result['headers']['content-type']);
      }

      return false;
    }

    return true;

  }

  /**
   * Tests if remote image can be loaded, before sending to browser or TCPDF
   * @todo Does not work with self-signed SSL and with allow_url_fopen = Off
   * @version 1.26.0
   */
  static public function can_get_image($url = false)
  {
    global $wp_properties;

    if (empty($url)) {
      return false;
    }

    $result = wp_remote_get($url, array('timeout' => 10));

    //** Image content types should always begin with 'image' (I hope) */
    if ((is_object($result) && get_class($result) == 'WP_Error') || strpos((string)$result['headers']['content-type'], 'image') === false) {
      return false;
    }

    return true;

  }

  /**
   * @param string $url
   * @return int
   * @since 2.0.3
   *
   * Checks if URL is valid
   */
  static public function is_valid_url($url = '')
  {
    return preg_match('@^(https?|ftp)://[^\s/$.?#].[^\s]*$@iS', $url);
  }

  /**
   * Remove non-XML characters
   *
   * @version 1.30.2
   */
  static public function strip_invalid_xml($value)
  {

    $ret = "";

    $bad_chars = array('\u000b');

    $value = str_replace($bad_chars, ' ', $value);

    if (empty($value)) {
      return $ret;
    }

    $length = strlen($value);

    for ($i = 0; $i < $length; $i++) {

      $current = ord($value{$i});

      if (($current == 0x9) || ($current == 0xA) || ($current == 0xD) ||
        (($current >= 0x20) && ($current <= 0xD7FF)) ||
        (($current >= 0xE000) && ($current <= 0xFFFD)) ||
        (($current >= 0x10000) && ($current <= 0x10FFFF))
      ) {

        $ret .= chr($current);

      } else {
        $ret .= " ";
      }
    }

    return $ret;
  }

  /**
   * Convert JSON data to XML if it is in JSON
   *
   * @version 1.26.0
   */
  static public function json_to_xml($json, $options = array())
  {

    //** An array of serializer options */
    $options = wp_parse_args($options, array(
      'indent' => " ",
      'linebreak' => "\n",
      'addDecl' => true,
      'encoding' => 'ISO-8859-1',
      'rootName' => 'objects',
      'defaultTagName' => 'object',
      'mode' => false
    ));

    if (empty($json)) {
      return false;
    }

    if (!class_exists('XML_Serializer')) {
      set_include_path(get_include_path() . PATH_SEPARATOR . WPP_Path . 'lib/third-party/XML/');
      @require_once 'Serializer.php';
    }

    //** If class still doesn't exist, for whatever reason, we fail */
    if (!class_exists('XML_Serializer')) {
      return false;
    }

    $encoding = function_exists('mb_detect_encoding') ? mb_detect_encoding($json) : 'UTF-8';

    if ($encoding == 'UTF-8') {
      $json = preg_replace('/[^(\x20-\x7F)]*/', '', $json);
    }

    $json = WPP_F::strip_invalid_xml($json);

    $data = json_decode($json, true);

    //** If could not decode, return false so we presume with XML format */
    if (!is_array($data)) {
      return false;
    }

    $Serializer = new XML_Serializer($options);

    $status = $Serializer->serialize($data);

    if (PEAR::isError($status)) {
      return false;
    }

    if ($Serializer->getSerializedData()) {
      return $Serializer->getSerializedData();
    }

    return false;

  }

  /**
   * Convert CSV to XML
   *
   * Function ported over from List Attachments Shortcode plugin.
   *
   * @version 1.32.0
   */
  static public function detect_encoding($string)
  {

    $encoding = array(
      'UTF-8',
      'windows-1251',
      'ISO-8859-1',
      'GBK',
      'ASCII',
      'JIS',
      'EUC-JP',
    );

    if (!function_exists('mb_detect_encoding')) {
      return;
    }

    foreach ($encoding as $single) {
      if (@mb_detect_encoding($string, $single, true)) {
        $matched = $single;
      }
    }

    return $matched ? $matched : new WP_Error('encoding_error', __('Could not detect.', ud_get_wp_property()->domain));

  }

  /**
   * Convert CSV to XML
   *
   * Function ported over from List Attachments Shortcode plugin.
   *
   * @version 1.32.0
   */
  static public function csv_to_xml($string, $args = false)
  {

    $uploads = wp_upload_dir();

    $defaults = array(
      'delimiter' => ',',
      'enclosure' => '"',
      'escape' => "\\"
    );

    extract(wp_parse_args($args, $defaults), EXTR_SKIP);

    $temp_file = $uploads['path'] . time() . '.csv';

    file_put_contents($temp_file, $string);

    ini_set("auto_detect_line_endings", 1);
    $current_row = 1;

    $handle = fopen($temp_file, "r");
    $header_array = array();
    $csv = array();

    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
      $number_of_fields = count($data);
      if ($current_row == 1) {
        for ($c = 0; $c < $number_of_fields; $c++) {
          $header_array[$c] = str_ireplace('-', '_', sanitize_key($data[$c]));
        }
      } else {

        $data_array = array();

        for ($c = 0; $c < $number_of_fields; $c++) {

          //** Clean up values */
          $value = trim($data[$c]);
          $data_array[$header_array[$c]] = $value;

        }

        /** Removing - this removes empty values from the CSV, we want to leave them to make sure the associative array is consistant for the importer - $data_array = array_filter($data_array); */

        if (!empty($data_array)) {
          $csv[] = $data_array;
        }

      }
      $current_row++;
    }

    fclose($handle);

    unlink($temp_file);

    //** Get it into XML (We want to use json_to_xml because it does all the cleansing of weird characters) */
    $xml = WPP_F::json_to_xml(json_encode($csv));

    return $xml;

  }

  /**
   * Get filesize of a file.
   *
   * Function ported over from List Attachments Shortcode plugin.
   *
   * @version 1.25.0
   */
  static public function get_filesize($file)
  {
    $bytes = filesize($file);
    $s = array('b', 'Kb', 'Mb', 'Gb');
    $e = floor(log($bytes) / log(1024));

    return sprintf('%.2f ' . $s[$e], ($bytes / pow(1024, floor($e))));
  }

  /**
   * Set all existing property objects' property type
   *
   * @todo Add regex to check for opening and closing bracket.
   * @version 1.23.1
   */
  static public function mass_set_property_type($property_type = false)
  {
    global $wpdb;

    if (!$property_type) {
      return false;
    }

    //** Get all properties */
    $ap = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'property' AND post_status != 'auto-draft'");

    if (!$ap) {
      return false;
    }

    foreach ($ap as $id) {

      if (update_post_meta($id, 'property_type', $property_type)) {
        $success[] = true;
      }

    }

    if (!$success) {
      return sprintf(__('Set %s %s to "%s" %s type', ud_get_wp_property()->domain), count($success), WPP_F::property_label('plural'), WPP_F::property_label(), $property_type);
    }

    return sprintf(__('Set %s %s to "%s" %s type', ud_get_wp_property()->domain), count($success), WPP_F::property_label('plural'), WPP_F::property_label(), $property_type);

  }

  /**
   * Attempts to detect if current page has a given shortcode
   *
   * @todo Add regex to check for opening and closing bracket.
   * @version 1.23.1
   */
  static public function detect_shortcode($shortcode = false)
  {
    global $post;

    if (!$post) {
      return false;
    }

    $shortcode = '[' . $shortcode;

    if (strpos($post->post_content, $shortcode) !== false) {
      return true;
    }

    return false;

  }

  /**
   * Reassemble address from parts
   *
   * @version 1.23.0
   */
  static public function reassemble_address($property_id = false)
  {

    if (!$property_id) {
      return false;
    }

    $address_part[] = get_post_meta($property_id, 'street_number', true);
    $address_part[] = get_post_meta($property_id, 'route', true);
    $address_part[] = get_post_meta($property_id, 'city', true);
    $address_part[] = get_post_meta($property_id, 'state', true);
    $address_part[] = get_post_meta($property_id, 'state_code', true);
    $address_part[] = get_post_meta($property_id, 'country', true);
    $address_part[] = get_post_meta($property_id, 'postal_code', true);

    $maybe_address = trim(implode(' ', $address_part));

    if (!empty($maybe_address)) {
      return $maybe_address;
    }

    return false;

  }

  /**
   * Creates a nonce, similar to wp_create_nonce() but does not depend on user being logged in
   *
   * @version 1.17.3
   */
  static public function generate_nonce($action = -1)
  {

    $user = wp_get_current_user();

    $uid = (int)$user->ID;

    if (empty($uid)) {
      $uid = $_SERVER['REMOTE_ADDR'];
    }

    $i = wp_nonce_tick();

    return substr(wp_hash($i . $action . $uid, 'nonce'), -12, 10);

  }

  /**
   * Verifies nonce.
   *
   * @version 1.17.3
   */
  static public function verify_nonce($nonce, $action = false)
  {

    $user = wp_get_current_user();
    $uid = (int)$user->ID;

    if (empty($uid)) {
      $uid = $_SERVER['REMOTE_ADDR'];
    }

    $i = wp_nonce_tick();

    // Nonce generated 0-12 hours ago
    if (substr(wp_hash($i . $action . $uid, 'nonce'), -12, 10) == $nonce)
      return 1;
    // Nonce generated 12-24 hours ago
    if (substr(wp_hash(($i - 1) . $action . $uid, 'nonce'), -12, 10) == $nonce)
      return 2;

    // Invalid nonce
    return false;

  }

  /**
   * Makes sure the script is loaded, otherwise loads it
   *
   * @version 1.17.3
   */
  static public function force_script_inclusion($handle = false)
  {
    global $wp_scripts;

    //** WP 3.3+ allows inline wp_enqueue_script(). Yay. */
    wp_enqueue_script($handle);

    if (!$handle) {
      return;
    }

    //** Check if already included */
    if (wp_script_is($handle, 'done')) {
      return true;
    }

    //** Check if script has dependancies that have not been loaded */
    if (is_array($wp_scripts->registered[$handle]->deps)) {
      foreach ($wp_scripts->registered[$handle]->deps as $dep_handle) {
        if (!wp_script_is($dep_handle, 'done')) {
          $wp_scripts->in_footer[] = $dep_handle;
        }
      }
    }
    //** Force script into footer */
    $wp_scripts->in_footer[] = $handle;
  }

  /**
   * Makes sure the style is loaded, otherwise loads it
   *
   * @param bool|string $handle registered style's name
   *
   * @return bool
   * @author Maxim Peshkov
   */
  static public function force_style_inclusion($handle = false)
  {
    global $wp_styles;
    static $printed_styles = array();

    if (!$handle) {
      return;
    }

    wp_enqueue_style($handle);

    //** Check if already included */
    if (wp_style_is($handle, 'done') || isset($printed_styles[$handle])) {
      return true;
    } elseif (headers_sent()) {
      $printed_styles[$handle] = true;
      wp_print_styles($handle);
    } else {
      return false;
    }

  }

  /**
   * Returns an array of all keys that can be queried using property_overview
   *
   * @version 1.17.3
   */
  static public function get_queryable_keys()
  {
    global $wp_properties;

    $keys = array_keys((array)$wp_properties['property_stats']);

    foreach ($wp_properties['searchable_attributes'] as $attr) {
      if (!in_array($attr, $keys)) {
        $keys[] = $attr;
      }
    }

    $keys[] = 'id';
    $keys[] = 'property_id';
    $keys[] = 'post_id';
    $keys[] = 'post_author';
    $keys[] = 'post_title';
    $keys[] = 'post_date';
    $keys[] = 'post_parent';
    $keys[] = 'property_type';
    $keys[] = 'featured';

    //* Adds filter for ability to apply custom queryable keys */
    $keys = apply_filters('get_queryable_keys', $keys);

    return $keys;
  }

  /**
   * Returns array of sortable attributes if set, or default
   *
   * @version 1.17.2
   */
  static public function get_sortable_keys()
  {
    global $wp_properties;

    $sortable_attrs = array();

    if (isset($wp_properties['configuration']['property_overview']['add_sort_by_title']) && $wp_properties['configuration']['property_overview']['add_sort_by_title'] != 'false') {
      $sortable_attrs['post_title'] = __('Title', ud_get_wp_property()->domain);
    }

    if (!empty($wp_properties['property_stats']) && !empty($wp_properties['sortable_attributes'])) {
      foreach ((array)$wp_properties['property_stats'] as $slug => $label) {
        if (in_array($slug, (array)$wp_properties['sortable_attributes'])) {
          $sortable_attrs[$slug] = apply_filters('wpp::attribute::label', $label, $slug);
        }
      }
    }

    //* If not set, menu_order will not be used at all if any of the attributes are marked as searchable */
    if (empty($sortable_attrs)) {
      $sortable_attrs['menu_order'] = __('Default', ud_get_wp_property()->domain);
    }

    $sortable_attrs = apply_filters('wpp::get_sortable_keys', $sortable_attrs);

    return $sortable_attrs;
  }

  /**
   * Pre post query - for now mostly to disable caching
   *
   * Called in &get_posts() in query.php
   *
   * @todo This function is a hack. Need to use post_type rewrites better. - potanin@UD
   *
   * @version 1.26.0
   * @param $posts
   * @return array
   */
  static public function posts_results($posts)
  {
    global $wpdb, $wp_query;

    //** Look for child properties */
    if (!empty($wp_query->query_vars['attachment'])) {
      $post_name = $wp_query->query_vars['attachment'];

      if ($child = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE post_name = '{$post_name}' AND post_type = 'property' AND post_parent != '' LIMIT 0, 1")) {
        $posts[0] = $child;

        return $posts;
      }
    }

    //** Look for regular pages that are placed under base slug */
    if (
      isset($wp_query->query_vars['post_type'])
      && $wp_query->query_vars['post_type'] == 'property'
    ) {
      $props = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE post_name = '{$wp_query->query_vars['name']}' AND post_type = 'property'  LIMIT 0, 1");
      if ( empty( $props ) ) {
        $posts[] = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE post_name = '{$wp_query->query_vars['name']}' AND post_type = 'page'  LIMIT 0, 1");
      }
    }

    return $posts;
  }

  /**
   * Pre post query - for now mostly to disable caching
   *
   * @version 1.17.2
   */
  static public function pre_get_posts($query)
  {
    global $wp_properties;

    if (!isset($wp_properties['configuration']['disable_wordpress_postmeta_cache']) || $wp_properties['configuration']['disable_wordpress_postmeta_cache'] != 'true') {
      return;
    }

    if (isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == 'property') {
      $query->query_vars['cache_results'] = false;
    }

  }

  /**
   * Format a number as numeric
   *
   * @version 1.16.3
   */
  static public function format_numeric($content = '')
  {
    global $wp_properties;

    if( is_string( $content ) ) {
      $content = trim($content);
    }

    $dec_point = (!empty($wp_properties['configuration']['dec_point']) ? $wp_properties['configuration']['dec_point'] : ".");
    $thousands_sep = (!empty($wp_properties['configuration']['thousands_sep']) ? $wp_properties['configuration']['thousands_sep'] : ",");

    if (is_numeric($content)) {
      $decimals = self::is_decimal($content) ? 2 : 0;
      $content = number_format($content, $decimals, $dec_point, $thousands_sep);
    }

    return $content;
  }

  /**
   * Determine if variable is decimal
   *
   * @param mixed $val
   *
   * @return bool
   * @author peshkov@UD
   */
  static public function is_decimal($val)
  {
    return is_numeric($val) && floor($val) != $val;
  }

  /**
   * Checks if an file exists in the uploads directory from a URL
   *
   * Only works for files in uploads folder.
   *
   * @todo update to handle images outside the uploads folder
   *
   * @version 1.16.3
   */
  static public function file_in_uploads_exists_by_url($image_url = '')
  {

    if (empty($image_url)) {
      return false;
    }

    $upload_dir = wp_upload_dir();
    $image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image_url);

    if (file_exists($image_path)) {
      return true;
    }

    return false;

  }

  /**
   * Setup default property page.
   *
   *
   * @version 1.16.3
   */
  static public function setup_default_property_page()
  {
    global $wpdb, $wp_properties, $user_ID;

    $base_slug = $wp_properties['configuration']['base_slug'];

    //** Check if this page actually exists */
    $post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name = '{$base_slug}'");

    if ($post_id) {
      //** Page already exists */
      return $post_id;
    }

    //** Check if page with this post name already exists */
    if ($post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name = 'properties'")) {
      return array(
        'post_id' => $post_id,
        'post_name' => 'properties'
      );
    }

    $property_page = array(
      'post_title' => __('Properties', ud_get_wp_property()->domain),
      'post_content' => '[property_overview]',
      'post_name' => 'properties',
      'post_type' => 'page',
      'post_status' => 'publish',
      'post_author' => $user_ID
    );

    $post_id = wp_insert_post($property_page);

    if (!is_wp_error($post_id)) {
      //** get post_name of new page */
      $post_name = $wpdb->get_var("SELECT post_name FROM {$wpdb->posts} WHERE ID = '{$post_id}'");

      return array(
        'post_id' => $post_id,
        'post_name' => $post_name
      );

    }

    return false;

  }

  /**
   * Perform WPP related things when a post is being deleted
   *
   * Makes sure all attached files and images get deleted.
   *
   *
   * @version 1.16.1
   */
  static public function before_delete_post($post_id)
  {
    global $wpdb, $wp_properties;

    if ( isset( $wp_properties['configuration']['auto_delete_attachments'] ) && $wp_properties['configuration']['auto_delete_attachments'] != 'true') {
      return;
    }

    //* Make sure this is a property */
    $is_property = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE ID = {$post_id} AND post_type = 'property'");

    if (!$is_property) {
      return;
    }

    $uploads = wp_upload_dir();

    //* Get Attachments */
    $attachments = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent = {$post_id} AND post_type = 'attachment' ");

    if ($attachments) {
      foreach ($attachments as $attachment_id) {

        $file_path = $wpdb->get_var("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = {$attachment_id} AND meta_key = '_wp_attached_file' ");

        wp_delete_attachment($attachment_id, true);

        if ($file_path) {
          $attachment_directories[] = $uploads['basedir'] . '/' . dirname($file_path);
        }

      }
    }

    if (isset($attachment_directories) && is_array($attachment_directories)) {
      $attachment_directories = array_unique($attachment_directories);
      foreach ($attachment_directories as $dir) {
        @rmdir($dir);
      }
    }

  }

  /**
   * Get advanced details about an image (mostly for troubleshooting)
   *
   * @todo add some sort of light validating that the the passed item here is in fact an image
   *
   */
  static public function get_property_image_data($requested_id)
  {
    global $wpdb;

    if (empty($requested_id)) {
      return false;
    }

    ob_start();

    if (is_numeric($requested_id)) {

      $post_type = $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID = '$requested_id'");
    } else {
      //** Try and image search */
      $image_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '%{$requested_id}%' ");

      if ($image_id) {
        $post_type = 'image';
        $requested_id = $image_id;
      }
    }

    if ($post_type == 'property') {

      //** Get Property Images */
      $property = WPP_F::get_property($requested_id);

      echo 'Requested Property: ' . $property['post_title'];
      $data = get_children(array('post_parent' => $requested_id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC'));
      echo "\nProperty has: " . count($data) . ' images.';

      foreach ($data as $img) {
        $image_data['ID'] = $img->ID;
        $image_data['post_title'] = $img->post_title;

        $img_meta = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = '{$img->ID}'");

        foreach ($img_meta as $i_m) {
          $image_data[$i_m->meta_key] = maybe_unserialize($i_m->meta_value);
        }
        print_r($image_data);

      }

    } else {

      $data = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE ID = '$requested_id'");
      $image_meta = $wpdb->get_results("SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = '$requested_id'");
      foreach ($image_meta as $m_data) {

        print_r($m_data->meta_id);
        echo "<br />";
        print_r($m_data->meta_key);
        echo "<br />";
        print_r(maybe_unserialize($m_data->meta_value));
      }

    }

    $return_data = ob_get_contents();
    ob_end_clean();

    return $return_data;

  }

  /**
   * Resizes (generate) image.
   *
   * @todo add some sort of light validating that the the passed item here is in fact an image
   *
   * If image has no meta data (for instance, if imported via XML Importer), this function
   * what _wp_attachment_metadata the wp_generate_attachment_metadata() function would ideally regenerate.
   *
   * @todo Update so when multiple images are passed the first requested image data is returned
   *
   * @param integer(string) $attachment_id
   * @param array $sizes . Arrays with sizes, or single name, later converted into array
   *
   * @return array. Image data for first image size (if multiple provided). Or FALSE if file could not be generated.
   * @since 1.6
   */
  static public function generate_image($attachment_id, $sizes = array())
  {
    global $_wp_additional_image_sizes;

    // Determine if params are empty
    if (empty($attachment_id) || empty($sizes)) {
      return false;
    }

    if (!is_array($sizes)) {
      $sizes = array($sizes);
    }

    // Check if image file exists
    $file = get_attached_file($attachment_id);
    if (empty($file)) {
      return false;
    }

    //** Get attachment metadata */
    $metadata = get_post_meta($attachment_id, '_wp_attachment_metadata', true);

    if (empty($metadata)) {

      include_once ABSPATH . 'wp-admin/includes/image.php';

      /*
        If image has been imported via XML it may not have meta data
        Here we attempt tp replicate wp_generate_attachment_metadata() but only generate the
        minimum requirements for image meta data and we do not create ALL variations of image, just the requested.
      */

      $metadata = array();
      $imagesize = @getimagesize($file);
      $metadata['width'] = $imagesize[0];
      $metadata['height'] = $imagesize[1];

      // Make the file path relative to the upload dir
      $metadata['file'] = _wp_relative_upload_path($file);

      if ($image_meta = wp_read_image_metadata($file)) {
        $metadata['image_meta'] = $image_meta;
      }

    }

    //** Get width, height and crop for new image */
    foreach ($sizes as $size) {
      if (isset($_wp_additional_image_sizes[$size]['width'])) {
        $width = intval($_wp_additional_image_sizes[$size]['width']); // For theme-added sizes
      } else {
        $width = get_option("{$size}_size_w"); // For default sizes set in options
      }
      if (isset($_wp_additional_image_sizes[$size]['height'])) {
        $height = intval($_wp_additional_image_sizes[$size]['height']); // For theme-added sizes
      } else {
        $height = get_option("{$size}_size_h"); // For default sizes set in options
      }
      if (isset($_wp_additional_image_sizes[$size]['crop'])) {
        $crop = intval($_wp_additional_image_sizes[$size]['crop']); // For theme-added sizes
      } else {
        $crop = get_option("{$size}_crop"); // For default sizes set in options
      }

      //** Try to generate file and update attachment data */
      $resized[$size] = image_make_intermediate_size($file, $width, $height, $crop);

    }

    if (empty($resized[$size])) {
      return false;
    }

    //** Cycle through resized and remove any blanks (would happen if image already exists)  */
    foreach ($resized as $key => $size_info) {
      if (empty($size_info)) {
        unset($resized[$key]);
      }
    }

    if (!empty($resized)) {

      foreach ($resized as $size => $resize) {
        $metadata['sizes'][$size] = $resize;
      }

      update_post_meta($attachment_id, '_wp_attachment_metadata', $metadata);

      //** Return first requested image **/

      return $resized;

    }

    return false;
  }

  /**
   * Check if theme-specific stylesheet exists.
   *
   * get_option('template') seems better choice than get_option('stylesheet'), which returns the current theme's slug
   * which is a problem when a child theme is used. We want the parent theme's slug.
   *
   * @since 1.6
   *
   */
  static public function has_theme_specific_stylesheet()
  {

    $theme_slug = get_option('template');

    if (file_exists(WPP_Path . "static/styles/theme-specific/{$theme_slug}.css")) {
      return true;
    }

    return false;

  }

  /**
   * Revalidate all addresses
   *
   * Revalidates addresses of all publishd properties.
   * If Google daily addres lookup is exceeded, breaks the function and notifies the user.
   *
   * @since 1.05
   *
   */
  static public function revalidate_all_addresses($args = '')
  {
    global $wp_properties, $wpdb;

    set_time_limit(0);
    ob_start();

    $args = wp_parse_args($args, array(
      'property_ids' => false,
      'echo_result' => 'true',
      'skip_existing' => 'false',
      'return_geo_data' => false,
      'attempt' => 1,
      'max_attempts' => 7,
      'delay' => 0, //Delay validation in seconds
      'increase_delay_by' => 0.25
    ));

    extract($args, EXTR_SKIP);
    $delay = isset($delay) ? $delay : 0;
    $attempt = isset($attempt) ? $attempt : 1;
    $max_attempts = isset($max_attempts) ? $max_attempts : 10;
    $increase_delay_by = isset($increase_delay_by) ? $increase_delay_by : 0.25;
    $echo_result = isset($echo_result) ? $echo_result : 'true';
    $skip_existing = isset($skip_existing) ? $skip_existing : 'false';
    $return_geo_data = isset($return_geo_data) ? $return_geo_data : false;
    if (is_array($args['property_ids'])) {
      $all_properties = $args['property_ids'];
    } else {
      $all_properties = $wpdb->get_col("
        SELECT ID FROM {$wpdb->posts} p
        left outer join {$wpdb->postmeta} pm on (pm.post_id=p.ID and pm.meta_key='wpp::last_address_validation' )
        WHERE p.post_type = 'property' AND p.post_status = 'publish'
        ORDER by pm.meta_value DESC
      ");
    }

    $return['updated'] = $return['failed'] = $return['over_query_limit'] = $return['over_query_limit'] = array();

    $google_map_localizations = WPP_F::draw_localization_dropdown('return_array=true');

    foreach ((array)$all_properties as $post_id) {
      if ($delay) {
        sleep($delay);
      }

      $result = WPP_F::revalidate_address($post_id, array('skip_existing' => $skip_existing, 'return_geo_data' => $return_geo_data));

      $return[$result['status']][] = $post_id;

      if ($return_geo_data) {
        $return['geo_data'][$post_id] = $result['geo_data'];
      }

    }

    $return['attempt'] = $attempt;

    /* // Instead of re-revalidate, must be overwritten UI: to dp separate AJAX request for every property and output results dynamicly.
    if ( !empty( $return[ 'over_query_limit' ] ) && $max_attempts >= $attempt && $delay < 2 ) {

      $_args = array(
          'property_ids' => $return[ 'over_query_limit' ],
          'echo_result' => false,
          'attempt' => $attempt + 1,
          'delay' => $delay + $increase_delay_by,
        ) + $args;

      $rerevalidate_result = self::revalidate_all_addresses( $_args );

      $return[ 'updated' ]          = array_merge( (array) $return[ 'updated' ], (array) $rerevalidate_result[ 'updated' ] );
      $return[ 'failed' ]           = array_merge( (array) $return[ 'failed' ], (array) $rerevalidate_result[ 'failed' ] );
      $return[ 'over_query_limit' ] = $rerevalidate_result[ 'over_query_limit' ];

      $return[ 'attempt' ] = $rerevalidate_result[ 'attempt' ];
    }
    //*/

    foreach (array('updated', 'over_query_limit', 'failed', 'empty_address') as $status) {
      $return[$status] = ($echo_result == 'true') ? count(array_unique((array)$return[$status])) : array_unique((array)$return[$status]);
    }

    $return['success'] = 'true';
    $return['message'] = sprintf(__('Updated %1$d %2$s using the %3$s localization.', ud_get_wp_property()->domain), ($echo_result == 'true') ? $return['updated'] : count($return['updated']), WPP_F::property_label('plural'), $google_map_localizations[$wp_properties['configuration']['google_maps_localization']]);

    if ($return['empty_address']) {
      $return['message'] .= "<br />" . sprintf(__('%1$d %2$s has empty address.', ud_get_wp_property()->domain), ($echo_result == 'true') ? $return['empty_address'] : count($return['empty_address']), WPP_F::property_label('plural'));
    }

    if ($return['failed']) {
      $return['message'] .= "<br />" . sprintf(__('%1$d %2$s could not be updated.', ud_get_wp_property()->domain), ($echo_result == 'true') ? $return['failed'] : count($return['failed']), WPP_F::property_label('plural'));
    }

    if ($return['over_query_limit']) {
      $return['message'] .= "<br />" . sprintf(__('%1$d %2$s was ignored because query limit was exceeded.', ud_get_wp_property()->domain), ($echo_result == 'true') ? $return['over_query_limit'] : count($return['over_query_limit']), WPP_F::property_label('plural'));
    }

    //** Warning Silincer */
    ob_end_clean();

    if ($echo_result == 'true') {
      die(json_encode($return));
    } else {
      return $return;
    }

  }

  /**
   * Address validation function
   *
   * Since 1.37.2 extracted from save_property and revalidate_all_addresses to make same functionality
   *
   * @global array $wp_properties
   *
   * @param integer $post_id
   * @param array $args
   *
   * @return array
   * @since 1.37.2
   * @author odokienko@UD
   */
  static public function revalidate_address($post_id, $args = array())
  {
    global $wp_properties;

    $args = wp_parse_args($args, array(
      'skip_existing' => 'false',
      'return_geo_data' => false
    ));

    extract($args, EXTR_SKIP);
    $skip_existing = isset($skip_existing) ? $skip_existing : 'false';
    $return_geo_data = isset($return_geo_data) ? $return_geo_data : false;

    $return = array();

    $geo_data = false;
    $geo_data_coordinates = false;
    $latitude = get_post_meta($post_id, 'latitude', true);
    $longitude = get_post_meta($post_id, 'longitude', true);
    $current_coordinates = $latitude . $longitude;
    $address_is_formatted = get_post_meta($post_id, 'address_is_formatted', true);

    $address = get_post_meta($post_id, $wp_properties['configuration']['address_attribute'], true);

    $coordinates = (empty($latitude) || empty($longitude)) ? false : array('lat' => get_post_meta($post_id, 'latitude', true), 'lng' => get_post_meta($post_id, 'longitude', true));

    if ($skip_existing == 'true' && !empty($current_coordinates) && in_array($address_is_formatted, array('1', 'true'))) {
      $return['status'] = 'skipped';

      return $return;
    }

    if (!(empty($coordinates) && empty($address))) {

      /* will be true if address is empty and used manual_coordinates and coordinates is not empty */
      $manual_coordinates = get_post_meta($post_id, 'manual_coordinates', true);
      $manual_coordinates = ($manual_coordinates != 'true' && $manual_coordinates != '1') ? false : true;

      $address_by_coordinates = !empty($coordinates) && $manual_coordinates && empty($address);

      if (!empty($coordinates) && $manual_coordinates) {
        $geo_data_coordinates = WPP_F::geo_locate_address($address, $wp_properties['configuration']['google_maps_localization'], true, $coordinates);
      } elseif (!empty($address)) {
        $geo_data = WPP_F::geo_locate_address($address, $wp_properties['configuration']['google_maps_localization'], true);
      }

      /** if Address was invalid or empty but we have valid $coordinates we use them */
      if (!empty($geo_data_coordinates->formatted_address) && ($address_by_coordinates || empty($geo_data->formatted_address))) {
        $geo_data = $geo_data_coordinates;
        /** clean up $address to remember that addres was empty or invalid*/
        $address = '';
      }

      if (empty($geo_data)) {
        $return['status'] = 'empty_address';
      }

    }

    $return['geo_data'] = $geo_data;

    if (!empty($geo_data->formatted_address)) {

      foreach ((array)$wp_properties['geo_type_attributes'] + array('display_address') as $meta_key) {
        delete_post_meta($post_id, $meta_key);
      }

      update_post_meta($post_id, 'address_is_formatted', true);

      if (!empty($wp_properties['configuration']['address_attribute']) && (!$manual_coordinates || $address_by_coordinates)) {
        update_post_meta($post_id, $wp_properties['configuration']['address_attribute'], $geo_data->formatted_address);
      }

      foreach ($geo_data as $geo_type => $this_data) {
        if (in_array($geo_type, (array)$wp_properties['geo_type_attributes']) && !in_array($geo_type, array('latitude', 'longitude'))) {
          update_post_meta($post_id, $geo_type, $this_data);
        }
      }

      update_post_meta($post_id, 'wpp::last_address_validation', time());

      if (isset($manual_coordinates) && $manual_coordinates == true) {
        $lat = !empty($coordinates['lat']) ? $coordinates['lat'] : 0;
        $lng = !empty($coordinates['lng']) ? $coordinates['lng'] : 0;
      } else {
        $lat = $geo_data->latitude;
        $lng = $geo_data->longitude;
      }

      update_post_meta($post_id, 'latitude', $lat);
      update_post_meta($post_id, 'longitude', $lng);

      if ($return_geo_data) {
        $return['geo_data'] = $geo_data;
      }

      $return['status'] = 'updated';

    }

    //** Logs the last validation status for better troubleshooting */
    update_post_meta($post_id, 'wpp::google_validation_status', (isset($geo_data->status) ? $geo_data->status : 'success'));

    // Try to figure out what went wrong
    if (!empty($geo_data->status) && ($geo_data->status == 'OVER_QUERY_LIMIT' || $geo_data->status == 'REQUEST_DENIED')) {
      $return['status'] = 'over_query_limit';
    } elseif (empty($address) && empty($geo_data)) {

      foreach ((array)$wp_properties['geo_type_attributes'] + array('display_address') as $meta_key) {
        delete_post_meta($post_id, $meta_key);
      }

      $return['status'] = 'empty_address';
      update_post_meta($post_id, 'address_is_formatted', false);
    } elseif (empty($return['status'])) {
      $return['status'] = 'failed';
      update_post_meta($post_id, 'address_is_formatted', false);
    }

    //** Neccessary meta data which is required by Supermap Premium Feature. Should be always set even the Supermap disabled. peshkov@UD */
    if (!metadata_exists('post', $post_id, 'exclude_from_supermap')) {
      add_post_meta($post_id, 'exclude_from_supermap', 'false');
    }

    $return = apply_filters( 'wpp::revalidate_address::return', $return, wp_parse_args( $args, array(
      'post_id' => $post_id,
      'geo_data' => $geo_data
    ) ) );

    return $return;
  }

  /**
   * Build terms from address parts.
   *
   * @todo Add "location_" prefix.
   *
   * Feature Flag: WPP_FEATURE_FLAG_WPP_LISTING_LOCATION
   * @since 2.2.1
   * @author potanin@UD
   * @param $post_id
   * @param $geo_data
   * @return array
   */
  static public function update_location_terms($post_id, $geo_data)
  {

    if( !$geo_data || !is_object( $geo_data ) ) {
      return new WP_Error( 'No [geo_data] argument provided.' );
    }

    $taxonomy = 'wpp_location';

    self::verify_have_system_taxonomy( $taxonomy );

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
        'parent' => 'county',
        'meta' => array(
          '_type' => 'wpp_location_city'
        )
      ),
      'route' => array(
        'parent' => 'city',
        'meta' => array(
          '_type' => 'wpp_location_route'
        )
      ),
      'zip' => array(
        'parent' => 'state',
        'meta' => array(
          '_type' => 'wpp_location_zip'
        )
      ),
      'subdivision' => array(
        'parent' => false,
        'meta' => array(
          '_type' => 'wpp_location_subdivision'
        )
      ),
      'city_state' => array(
        'parent' => false,
        'meta' => array(
          '_type' => 'wpp_location_city_state'
        )
      )
    );

    $geo_data->terms = array();

    // May be set city_state term
    if( empty( $geo_data->city_state ) && !empty( $geo_data->city ) && !empty( $geo_data->state ) ) {
      $geo_data->city_state = trim( $geo_data->city ) . ', ' . trim( $geo_data->state );
    }

    $geo_data->terms['state'] = !empty($geo_data->state) ? get_term_by('name', $geo_data->state, $taxonomy, OBJECT) : false;
    $geo_data->terms['county'] = !empty($geo_data->county) ? get_term_by('name', $geo_data->county, $taxonomy, OBJECT) : false;
    $geo_data->terms['city'] = !empty($geo_data->city) ? get_term_by('name', $geo_data->city, $taxonomy, OBJECT) : false;
    $geo_data->terms['route'] = !empty($geo_data->route) ? get_term_by('name', $geo_data->route, $taxonomy, OBJECT) : false;
    $geo_data->terms['zip'] = !empty($geo_data->zip) ? get_term_by('name', $geo_data->zip, $taxonomy, OBJECT) : false;
    $geo_data->terms['subdivision'] = !empty($geo_data->subdivision) ? get_term_by('name', $geo_data->subdivision, $taxonomy, OBJECT) : false;
    $geo_data->terms['city_state'] = !empty($geo_data->city_state) ? get_term_by('name', $geo_data->city_state, $taxonomy, OBJECT) : false;

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
      wp_set_object_terms($post_id, $_location_terms, 'wpp_location', false);

    }

    return $geo_data->terms;

  }

  /**
   * Returns location information from Google Maps API call
   *
   *
   * @version 2.0
   * @since 1.0.0
   *
   * @param bool $address
   * @param string $localization
   * @param bool $return_obj_on_fail
   * @param bool $latlng
   *
   * @return object
   */
  static public function geo_locate_address($address = false, $localization = "en", $return_obj_on_fail = false, $latlng = false)
  {

    if (!$address && !$latlng) {
      return false;
    }

    if (is_array($address)) {
      return false;
    }

    $return = new stdClass();

    $url = add_query_arg(apply_filters('wpp:geocoding_request', array(
      "address" => rawurlencode($address),
      "language" => $localization,
      "key" => ud_get_wp_property('configuration.google_maps_api')
    )), "https://maps.googleapis.com/maps/api/geocode/json");

    $obj = wp_remote_get($url);
    $body = json_decode(wp_remote_retrieve_body($obj));

    if (!$body || $body->status != "OK") {

      // Return Google result if needed instead of just false
      if ($return_obj_on_fail) {
        return $body;
      }

      return false;

    }

    $results_object = $body->results[0];
    $geometry = $results_object->geometry;

    $return->formatted_address = $results_object->formatted_address;
    $return->latitude = $geometry->location->lat;
    $return->longitude = $geometry->location->lng;

    // Cycle through address component objects picking out the needed elements, if they exist
    foreach ((array)$results_object->address_components as $ac) {

      // types is returned as an array, look through all of them
      foreach ((array)$ac->types as $type) {
        switch ($type) {

          case 'street_number':
            $return->street_number = $ac->long_name;
            break;

          case 'route':
            $return->route = $ac->long_name;
            break;

          case 'locality':
            $return->city = $ac->long_name;
            break;

          case 'administrative_area_level_3':
            if (empty($return->city))
              $return->city = $ac->long_name;
            break;

          case 'administrative_area_level_2':
            $return->county = $ac->long_name;
            break;

          case 'administrative_area_level_1':
            $return->state = $ac->long_name;
            $return->state_code = $ac->short_name;
            break;

          case 'country':
            $return->country = $ac->long_name;
            $return->country_code = $ac->short_name;
            break;

          case 'postal_code':
            $return->postal_code = $ac->long_name;
            break;

          case 'sublocality':
            $return->district = $ac->long_name;
            break;

        }
      }
    }

    // legacy filter
    $return = apply_filters('ud::geo_locate_address', $return, $results_object, $address, $localization);

    //** API Callback (Legacy) - If no actions have been registered for the new hook, we support the old one. */
    if (!has_action('ud::geo_locate_address')) {
      $return = apply_filters('geo_locate_address', $return, $results_object, $address, $localization);
    }

    // modern filter
    $return = apply_filters('wpp::geo_locate_address', $return, $results_object, $address, $localization);

    return $return;

  }

  /**
   * Returns avaliability of Google's Geocoding Service based on time of last returned status OVER_QUERY_LIMIT
   * @uses const self::blocking_for_new_validation_interval
   * @uses option ud::geo_locate_address_last_OVER_QUERY_LIMIT
   * @param bool|type $update used to set option value in time()
   * @return bool
   * @author odokienko@UD
   */
  static public function available_address_validation($update = false)
  {
    global $wpdb;

    if (empty($update)) {

      $last_error = (int)get_option('ud::geo_locate_address_last_OVER_QUERY_LIMIT');
      if (!empty($last_error) && (time() - (int)$last_error) < 2) {
        sleep(1);
      }
      /*if (!empty($last_error) && (((int)$last_error + self::blocking_for_new_validation_interval ) > time()) ){
        sleep(1);
        //return false;
      }else{
        //** if last success validation was less than a seccond ago we will wait for 1 seccond
        $last = $wpdb->get_var("
          SELECT if(DATE_ADD(FROM_UNIXTIME(pm.meta_value), INTERVAL 1 SECOND) < NOW(), 0, UNIX_TIMESTAMP()-pm.meta_value) LAST
          FROM {$wpdb->postmeta} pm
          WHERE pm.meta_key='wpp::last_address_validation'
          LIMIT 1
        ");
        usleep((int)$last);
      }*/
    } else {
      update_option('ud::geo_locate_address_last_OVER_QUERY_LIMIT', time());
      return false;
    }

    return true;
  }

  /**
   * Minify JavaScript
   *
   * Uses third-party JSMin if class isn't declared.
   * If WP3 is detected, class not loaded to avoid footer warning error.
   * If for some reason W3_Plugin is active, but JSMin is not found,
   * we load ours to avoid breaking property maps.
   *
   * @since 1.06
   */
  static public function minify_js($data)
  {

    if (!class_exists('W3_Plugin')) {
      include_once WPP_Path . 'lib/third-party/jsmin.php';
    } elseif (file_exists(WP_PLUGIN_DIR . '/w3-total-cache/lib/Minify/JSMin.php')) {
      include_once WP_PLUGIN_DIR . '/w3-total-cache/lib/Minify/JSMin.php';
    } else {
      include_once WPP_Path . 'lib/third-party/jsmin.php';
    }

    if (class_exists('JSMin')) {
      try {
        $data = JSMin::minify($data);
      } catch (Exception $e) {
        return $data;
      }
    }

    return $data;

  }

  /**
   * Minify CSS
   *
   * Syntax:
   * string CssMin::minify(string $source [, array $filters = array()][, array $plugins = array()]);
   *
   * string $source
   * The source css as string.
   * array $filters
   * The filter configuration as array (optional). See Filter Configuration
   * array $plugins
   * The plugin configuration as array (optional). See: Plugin Configuration
   * Example
   * //Simple minification WITHOUT filter or plugin configuration
   * $result = CssMin::minify(file_get_contents("path/to/source.css"));
   * //Minification WITH filter or plugin configuration
   * $filters = array();
   * $plugins = array();
   * // Minify via CssMin adapter function
   * $result = CssMin::minify(file_get_contents("path/to/source.css"), $filters, $plugins);
   * // Minify via CssMinifier class
   * $minifier = new CssMinifier(file_get_contents("path/to/source.css"), $filters, $plugins);
   * $result = $minifier->getMinified();
   *
   * @since 1.37.3.2
   * @author odokienko@UD
   */
  static public function minify_css($data)
  {

    include_once WPP_Path . 'lib/third-party/cssmin.php';

    if (class_exists('CssMin')) {
      try {
        $minified_data = CssMin::minify($data);

        return $minified_data;
      } catch (Exception $e) {
        return $data;
      }
    }

    return $data;

  }

  /**
   * Gets image dimensions for WP-Property images.
   *
   * This function is no longer used, only here for legacy support.
   *
   * @since 1.0
   *
   */
  static public function get_image_dimensions($type = false)
  {
    return WPP_F::image_sizes($type);
  }

  /**
   * Determines most common property type (used for defaults when needed)
   *
   *
   * @since 0.55
   *
   */
  static public function get_most_common_property_type($array = false)
  {
    global $wpdb, $wp_properties;

    $type_slugs = array_keys((array)$wp_properties['property_types']);

    $top_property_type = $wpdb->get_col("
      SELECT DISTINCT(meta_value)
      FROM {$wpdb->postmeta}
      WHERE meta_key = 'property_type'
      GROUP BY meta_value
      ORDER BY  count(meta_value) DESC
    ");

    if (is_array($top_property_type)) {
      foreach ($top_property_type as $slug) {
        if (isset($wp_properties['property_types'][$slug])) {
          return $slug;
        }
      }
    }

    //* No DB entries, return first property type in settings */
    return $type_slugs[0];

  }

  /**
   * Splits a query string properly, using preg_split to avoid conflicts with dashes and other special chars.
   *
   * @param string $query string to split
   *
   * @return Array
   */
  static public function split_query_string($query)
  {
    /**
     * Split the string properly, so no interference with &ndash; which is used in user input.
     */
    //$data = preg_split( "/&(?!&ndash;)/", $query );
    //$data = preg_split( "/(&(?!.*;)|&&)/", $query );
    $data = preg_split("/&(?!([a-zA-Z]+|#[0-9]+|#x[0-9a-fA-F]+);)/", $query);

    return $data;
  }

  /**
   * Determines if all of the arrays values are numeric
   *
   *
   * @since 0.55
   *
   */
  static public function is_numeric_range($array = false)
  {
    if (!is_array($array) || empty($array)) {
      return false;
    }
    foreach ($array as $value) {
      if (!is_numeric($value)) {
        return false;
      }
    }

    return true;
  }

  static public function draw_property_type_dropdown($args = '')
  {
    global $wp_properties;

    $defaults = array('id' => 'wpp_property_type', 'name' => 'wpp_property_type', 'selected' => '');
    extract(wp_parse_args($args, $defaults), EXTR_SKIP);
    $id = isset($id) ? $id : 'wpp_property_type';
    $selected = isset($selected) ? $selected : '';

    if (!is_array($wp_properties['property_types']))
      return;

    $return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
    foreach ($wp_properties['property_types'] as $slug => $label)
      $return .= "<option value='$slug' " . ($selected == $slug ? " selected='true' " : "") . "'>$label</option>";
    $return .= "</select>";

    return $return;

  }

  /**
   *
   */
  static public function draw_property_dropdown($args = '')
  {
    global $wp_properties, $wpdb;

    $defaults = array('id' => 'wpp_properties', 'name' => 'wpp_properties', 'selected' => '');
    extract(wp_parse_args($args, $defaults), EXTR_SKIP);
    $id = isset($id) ? $id : 'wpp_property_type';
    $selected = isset($selected) ? $selected : '';
    $all_properties = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'property' AND post_status = 'publish'");

    if (!is_array($all_properties))
      return;

    $return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
    foreach ($all_properties as $p_data)
      $return .= "<option value='$p_data->id' " . ($selected == $p_data->id ? " selected='true' " : "") . "'>{$p_data->post_title}</option>";
    $return .= "</select>";

    return $return;

  }

  /**
   * Return an array of all available attributes and meta keys
   *
   */
  static public function get_total_attribute_array($args = '', $extra_values = false)
  {
    global $wp_properties;

    $defaults = array(
      'use_optgroups' => 'false'
    );

    $args = wp_parse_args($args, $defaults);

    $property_stats = $wp_properties['property_stats'];

    if (!is_array($extra_values)) {
      $extra_values = array();
    }

    if ($args[ 'use_optgroups' ] == 'true') {
      $attributes['Attributes'] = $property_stats;
      $attributes['Other'] = $extra_values;
    } else {
      $attributes = (array) $property_stats + (array) $extra_values;
    }

    $attributes = apply_filters('wpp_total_attribute_array', $attributes, $args );

    if (!is_array($attributes)) {
      $attributes = array();
    }

    return $attributes;

  }

  /**
   * Render a dropdown of property attributes.
   *
   */
  static public function draw_attribute_dropdown($args = '', $extra_values = false)
  {
    global $wp_properties, $wpdb;

    $defaults = array('id' => 'wpp_attribute', 'name' => 'wpp_attribute', 'selected' => '');
    extract(wp_parse_args($args, $defaults), EXTR_SKIP);
    $id = isset($id) ? $id : 'wpp_attribute';
    $selected = isset($selected) ? $selected : 'false';
    $name = isset($name) ? $name : 'wpp_attribute';

    $attributes = $wp_properties['property_stats'];

    if (is_array($extra_values)) {
      $attributes = array_merge($extra_values, $attributes);
    }

    if (!is_array($attributes))
      return;

    $return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
    $return .= "<option value=''> - </option>";

    foreach ($attributes as $slug => $label)
      $return .= "<option value='$slug' " . ($selected == $slug ? " selected='true' " : "") . ">$label ($slug)</option>";
    $return .= "</select>";

    return $return;

  }

  static public function draw_localization_dropdown($args = '')
  {
    global $wp_properties, $wpdb;

    $defaults = array('id' => 'wpp_google_maps_localization', 'name' => 'wpp_google_maps_localization', 'selected' => '', 'return_array' => 'false');
    extract(wp_parse_args($args, $defaults), EXTR_SKIP);
    $return_array = isset($return_array) ? $return_array : 'false';
    $id = isset($id) ? $id : 'wpp_google_maps_localization';
    $selected = isset($selected) ? $selected : '';

    $attributes = array(
      'en' => 'English',
      'ar' => 'Arabic',
      'bg' => 'Bulgarian',
      'cs' => 'Czech',
      'da' => 'Danish',
      'nl' => 'Dutch',
      'de' => 'German',
      'zh-TW' => 'Chinese',
      'el' => 'Greek',
      'fi' => 'Finnish',
      'fr' => 'French',
      'hu' => 'Hungarian',
      'it' => 'Italian',
      'ja' => 'Japanese',
      'ko' => 'Korean',
      'no' => 'Norwegian',
      'pt' => 'Portuguese',
      'pt-BR' => 'Portuguese (Brazil)',
      'pt-PT' => 'Portuguese (Portugal)',
      'ru' => 'Russian',
      'es' => 'Spanish',
      'sv' => 'Swedish',
      'th' => 'Thai',
      'uk' => 'Ukrainian'
    );

    $attributes = apply_filters("wpp_google_maps_localizations", $attributes);

    if (!is_array($attributes))
      return;

    if ($return_array == 'true')
      return $attributes;

    $return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
    foreach ($attributes as $slug => $label)
      $return .= "<option value='$slug' " . ($selected == $slug ? " selected='true' " : "") . "'>$label ($slug)</option>";
    $return .= "</select>";

    return $return;

  }

  /**
   * Maybe add cache file
   *
   * @version 0.1
   * @since 1.40.0
   * @author peshkov@UD
   */
  static public function set_cache($key, $data, $live = 3600)
  {

    return wp_cache_set($key, $data, 'wpp', $live);

  }

  /**
   * Maybe get data from cache file
   *
   *
   * @todo Convert to use WP Object Cache like everything else. -potanin@UD.
   *
   * @version 0.1
   * @since 1.40.0
   * @author peshkov@UD
   */
  static public function get_cache($key, $live = 3600)
  {

    if ($_cache = wp_cache_get($key, 'wpp')) {
      return $_cache;
    }

    return false;

  }

  /**
   * Makes a given property featured, usuall called via ajax
   *
   * @since 0.721
   */
  static public function toggle_featured($post_id = false)
  {
    global $current_user;

    if (!current_user_can('manage_wpp_make_featured'))
      return;

    if (!$post_id)
      return;

    $featured = get_post_meta($post_id, 'featured', true);

    // Check if already featured
    if ($featured == 'true') {
      $value = 'false';
      $status = 'not_featured';
    } else {
      $value = 'true';
      $status = 'featured';
    }

    update_post_meta($post_id, 'featured', $value);

    do_action('wpp::toggle_featured', $value, $post_id);

    echo json_encode(array('success' => 'true', 'status' => $status, 'post_id' => $post_id));
  }

  /**
   * Displays dropdown of available property size images
   *
   *
   * @since 0.54
   *
   */
  static public function image_sizes_dropdown($args = "")
  {
    global $wp_properties;

    $defaults = array(
      'name' => 'wpp_image_sizes',
      'selected' => 'none',
      'blank_selection_label' => ' - '
    );

    extract(wp_parse_args($args, $defaults), EXTR_SKIP);
    $blank_selection_label = isset($blank_selection_label) ? $blank_selection_label : ' - ';
    $selected = isset($selected) ? $selected : 'none';

    if (empty($id) && !empty($name)) {
      $id = $name;
    }

    $image_array = get_intermediate_image_sizes();

    ?>
    <select id="<?php echo $id ?>" name="<?php echo $name ?>">
      <option value=""><?php echo $blank_selection_label; ?></option>
      <?php
      foreach ($image_array as $name) {
        $sizes = WPP_F::image_sizes($name);

        if (!$sizes) {
          continue;
        }

        ?>
        <option value='<?php echo $name; ?>' <?php if ($selected == $name) echo 'SELECTED'; ?>>
          <?php echo $name; ?>: <?php echo $sizes['width']; ?>px by <?php echo $sizes['height']; ?>px
        </option>
      <?php } ?>
    </select>

    <?php
  }

  /**
   * Returns image sizes for a passed image size slug
   *
   * Looks through all images sizes.
   *
   * @since 0.54
   *
   * @param bool $type
   * @param string $args
   *
   * @returns array keys: 'width' and 'height' if image type sizes found.
   */
  static public function image_sizes($type = false, $args = "")
  {
    global $_wp_additional_image_sizes;

    $defaults = array(
      'return_all' => false
    );

    extract(wp_parse_args($args, $defaults), EXTR_SKIP);
    $return_all = isset($return_all) ? $return_all : 'none';

    if (!$type) {
      return false;
    }

    if (isset($_wp_additional_image_sizes[$type]) && is_array($_wp_additional_image_sizes[$type])) {
      $return = $_wp_additional_image_sizes[$type];

    } else {

      if ($type == 'thumbnail' || $type == 'thumb') {
        $return = array('width' => intval(get_option('thumbnail_size_w')), 'height' => intval(get_option('thumbnail_size_h')));
      }

      if ($type == 'medium') {
        $return = array('width' => intval(get_option('medium_size_w')), 'height' => intval(get_option('medium_size_h')));
      }

      if ($type == 'large') {
        $return = array('width' => intval(get_option('large_size_w')), 'height' => intval(get_option('large_size_h')));
      }

    }

    if (!isset($return) || !is_array($return)) {
      return;
    }

    if (!$return_all) {

      // Zeroed out dimensions means they are deleted
      if (empty($return['width']) || empty($return['height'])) {
        return;
      }

      // Zeroed out dimensions means they are deleted
      if ($return['width'] == '0' || $return['height'] == '0') {
        return;
      }

    }

    // Return dimensions
    return $return;

  }

  public function widget_childpropertieswidget_data()
  {
    return
      array('title' => '',
        'image_type' => 'medium',
        'big_image_type' => 'large',
        'gallery_count' => 10
      );
  }

  public function widget_gallerypropertieswidget_data()
  {
    return
      array('title' => '',
        'image_type' => 'medium',
        'big_image_type' => 'large',
        'amount_items' => 5,
        'sort_by' => '',
        'sort_order' => 'ASC',
        'address_format' => ' [street_number] [street_name], [city], [state]'
      );
  }

  /**
   * AJAX Handler for manaually creating backups.
   *
   * @author raj
   */
  static public function create_settings_backup()
  {
    global $wp_properties;
    //save backup


    $data = apply_filters('wpp::backup::data', array('wpp_settings' => $wp_properties));
    $timestamp = time();
    if (get_option("wpp_property_backups"))
      $backups = get_option("wpp_property_backups");
    else
      $backups = array();

    $backups[$timestamp] = $data;
    update_option("wpp_property_backups", $backups);
    $message = '<a href="' . wp_nonce_url("edit.php?post_type=property&page=property_settings&wpp_action=download-wpp-backup&timestamp=" . $timestamp, 'download-wpp-backup') . '">' . date('d-m-Y H:i', $timestamp) . '</a>&nbsp;&nbsp;&nbsp;';
    echo json_encode(array("success" => true, 'message' => $message));
  }

  /**
   * AJAX Handler.
   * Saves WPP Settings
   *
   * @author peshkov@UD
   * @since 1.38.3
   */
  static public function save_settings()
  {
    global $wp_properties;

    $data = self::parse_str($_REQUEST['data']);

    $return = array(
      'success' => true,
      'message' => '',
      'redirect' => admin_url("edit.php?post_type=property&page=property_settings&message=updated")
    );

    try {
      if (empty($data['wpp_settings']) || !wp_verify_nonce($data['_wpnonce'], 'wpp_setting_save')) {
        throw new Exception(__('Request can not be verified.', ud_get_wp_property()->domain));
      }
      //** Allow features to preserve their settings that are not configured on the settings page */
      $wpp_settings = apply_filters('wpp_settings_save', $data['wpp_settings'], $wp_properties);
      //** Prevent removal of featured settings configurations if they are not present */
      if (!empty($wp_properties['configuration']['feature_settings'])) {
        foreach ($wp_properties['configuration']['feature_settings'] as $feature_type => $preserved_settings) {
          if (empty($data['wpp_settings']['configuration']['feature_settings'][$feature_type])) {
            $wpp_settings['configuration']['feature_settings'][$feature_type] = $preserved_settings;
          }
        }
      }

      update_option('wpp_settings', $wpp_settings);

      do_action('wpp::save_settings', $data);
      /* Remove all WordPress cache items. */
      if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
      }
      /* Flush WPP cache */

    } catch (Exception $e) {
      $return['success'] = false;
      $return['message'] = $e->getMessage();
    }

    return json_encode($return);
  }

  /**
   * Get settings page html and wpp_settings
   *
   * @author alim
   * @param none
   * @return array
   */
  public static function wpp_ajax_get_settings_page() {
    ud_get_wp_property()->core->wpp_settings_remove_lock();
    $return['lock_removed'] = true;

    $settings = new UsabilityDynamics\WPP\Settings(array(
      'key' => 'wpp_settings',
      'store' => 'options',
    ));
    ob_start();
      $settings->render_page();
    $return['wpp_settings_page'] = ob_get_clean();

    $return['wpp_settings'] = apply_filters( 'wpp::localization::instance', ud_get_wp_property()->get() );

    wp_send_json($return);
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
  static public function settings_action($force_db = false)
  {
    global $wp_properties;

    //** Handle backup */
    if (isset($_REQUEST['wpp_settings']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'wpp_setting_save') && !empty($_FILES['wpp_settings']['tmp_name']['settings_from_backup'])) {
      $backup_file = $_FILES['wpp_settings']['tmp_name']['settings_from_backup'];
      $backup_contents = file_get_contents($backup_file);

      if (!empty($backup_contents)) {
        $decoded_settings = json_decode($backup_contents, true);
      }

      if (!empty($decoded_settings)) {

        // Fix legacy backup data
        if (empty($decoded_settings['wpp_settings'])) {
          $decoded_settings = array('wpp_settings' => $decoded_settings);
        }

        // Note! We are restoring all options.
        // Since some add-ons are storing data under own options.
        foreach ($decoded_settings as $option_key => $data) {

          // Handle core settings ( legacy support )
          if ($option_key == 'wpp_settings') {
            //** Allow features to preserve their settings that are not configured on the settings page */

            //** Prevent removal of featured settings configurations if they are not present */
            if (!empty($wp_properties['configuration']['feature_settings'])) {
              foreach ($wp_properties['configuration']['feature_settings'] as $feature_type => $preserved_settings) {
                if (empty($data['configuration']['feature_settings'][$feature_type])) {
                  $data['configuration']['feature_settings'][$feature_type] = $preserved_settings;
                }
              }
            }

            foreach( $wp_properties as $_suboption_key  => $_suboption_data ) {
              // WPP_F::debug( "Checking [$_suboption_key]/" );

              if( !isset( $data[ $_suboption_key ] ) ) {
                WPP_F::debug( "Preserving [$_suboption_key]." );
                $data[ $_suboption_key ] = $wp_properties[ $_suboption_key ] ;
              }

            }

            $data = apply_filters('wpp_settings_save', $data, $wp_properties);

          }

          update_option($option_key, $data);

        }

        //** Reload page to make sure higher-end functions take affect of new settings */
        //** The filters below will be ran on reload, but the saving functions won't */
        if ($_REQUEST['page'] == 'property_settings') {
          unset($_REQUEST);
          wp_redirect(admin_url("edit.php?post_type=property&page=property_settings&message=restored"));
          exit;
        }

      }

    }

    if ($force_db) {

      // Load settings out of database to overwrite defaults from action_hooks.
      $wp_properties_db = get_option('wpp_settings');

      // Overwrite $wp_properties with database setting
      $wp_properties = array_merge($wp_properties, $wp_properties_db);

    }

    add_filter('wpp_image_sizes', array('WPP_F', 'remove_deleted_image_sizes'));

    $wp_properties = wp_parse_args($wp_properties, array(
      'configuration' => array(),
      'location_matters' => array(),
      'hidden_attributes' => array(),
      'descriptions' => array(),
      'image_sizes' => array(),
      'search_conversions' => array(),
      'searchable_attributes' => array(),
      'searchable_property_types' => array(),
      'property_inheritance' => array(),
      'property_meta' => array(),
      'property_stats' => array(),
      'property_types' => array(),
      'taxonomies' => array(),
    ));

    // Filers are applied
    $wp_properties['configuration'] = apply_filters('wpp_configuration', $wp_properties['configuration']);
    $wp_properties['location_matters'] = apply_filters('wpp_location_matters', $wp_properties['location_matters']);
    $wp_properties['hidden_attributes'] = apply_filters('wpp_hidden_attributes', $wp_properties['hidden_attributes']);
    $wp_properties['descriptions'] = apply_filters('wpp_listing_label_descriptions', $wp_properties['descriptions']);
    $wp_properties['image_sizes'] = apply_filters('wpp_image_sizes', $wp_properties['image_sizes']);
    $wp_properties['search_conversions'] = apply_filters('wpp_search_conversions', $wp_properties['search_conversions']);
    $wp_properties['searchable_attributes'] = apply_filters('wpp_searchable_attributes', $wp_properties['searchable_attributes']);
    $wp_properties['searchable_property_types'] = apply_filters('wpp_searchable_property_types', $wp_properties['searchable_property_types']);
    $wp_properties['property_inheritance'] = apply_filters('wpp_property_inheritance', $wp_properties['property_inheritance']);
    $wp_properties['property_meta'] = apply_filters('wpp_property_meta', $wp_properties['property_meta']);
    $wp_properties['property_stats'] = apply_filters('wpp_property_stats', $wp_properties['property_stats']);
    $wp_properties['property_types'] = apply_filters('wpp_property_types', $wp_properties['property_types']);
    $wp_properties['taxonomies'] = apply_filters('wpp_taxonomies', $wp_properties['taxonomies']);

    $wp_properties = stripslashes_deep($wp_properties);

    return $wp_properties;

  }

  /**
   * Utility to remove deleted image sizes.
   *
   * @param $sizes
   *
   * @return mixed
   */
  static public function remove_deleted_image_sizes($sizes)
  {
    global $wp_properties;

    foreach ($sizes as $slug => $size) {
      if ($size['width'] == '0' || $size['height'] == '0')
        unset($sizes[$slug]);

    }

    return $sizes;

  }

  /**
   * Loads property values into global $post variables.
   *
   * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
   * Ran after template_redirect.
   * $property is loaded in WPP_Core::template_redirect();
   *
   * @since 0.54
   *
   */
  static public function the_post($post)
  {
    global $post, $property;

    if ($post->post_type != 'property' || empty($property)) {
      return $post;
    }

    $_property = (array)$property;

    if ($_property['ID'] != $post->ID) {
      return $post;
    }

    //** Update global $post object to include property specific attributes */
    $post = (object)((array)$post + $_property);

  }

  /**
   * Returns array of searchable property IDs
   *
   *
   * @return array|$wp_properties
   * @since 0.621
   *
   */
  static public function get_searchable_properties()
  {
    global $wp_properties;

    $searchable_properties = array();

    if (!is_array($wp_properties['searchable_property_types']))
      return;

    // Get IDs of all property types
    foreach ($wp_properties['searchable_property_types'] as $property_type) {

      $this_type_properties = WPP_F::get_properties("property_type=$property_type");

      if (is_array($this_type_properties) && is_array($searchable_properties))
        $searchable_properties = array_merge($searchable_properties, $this_type_properties);
    }

    if (is_array($searchable_properties))
      return $searchable_properties;

    return false;

  }

  /**
   * Returns array of searchable attributes and their ranges
   *
   *
   * @param      $search_attributes
   * @param      $searchable_property_types
   * @param bool $cache
   * @param bool $instance_id
   *
   * @return array|$range
   * @since 0.57
   */
  static public function get_search_values($search_attributes, $searchable_property_types, $cache = true, $instance_id = false)
  {
    global $wpdb, $wp_properties;

    // Non post_meta fields
    $non_post_meta = array(
      'ID' => 'equal',
      'post_date' => 'date'
    );

    if ($instance_id && $cache) {
      $result = WPP_F::get_cache($instance_id);
    }

    if (empty($result)) {
      $query_attributes = "";
      $query_types = "";
      $range = array();

      //** Use the requested attributes, or all searchable */
      if (!is_array($search_attributes)) {
        $search_attributes = $wp_properties['searchable_attributes'];
      }

      if ($searchable_property_types == 'all') {
        $searchable_property_types = $wp_properties['searchable_property_types'];
      } else if (!is_array($searchable_property_types)) {
        $searchable_property_types = explode(',', $searchable_property_types);
        foreach ($searchable_property_types as $k => $v) {
          $searchable_property_types[$k] = trim($v);
        }
      }
      $searchable_property_types_sql = "AND pm2.meta_value IN ('" . implode("','", $searchable_property_types) . "')";

      //** Cycle through requested attributes */
      foreach ($search_attributes as $searchable_attribute) {

        if ($searchable_attribute == 'property_type') {
          foreach ($wp_properties['searchable_property_types'] as $property_type) {
            $range['property_type'][$property_type] = $wp_properties['property_types'][$property_type];
          }
          continue;
        }

        //** Load attribute data */
        $attribute_data = UsabilityDynamics\WPP\Attributes::get_attribute_data($searchable_attribute);

        if (isset($attribute_data['numeric']) || isset($attribute_data['currency'])) {
          $is_numeric = true;
        } else {
          $is_numeric = false;
        }

        //** Check to see if this attribute has predefined values or if we have to get them from DB */
        //** If the attributes has predefind values, we use them */
        if (!empty($wp_properties['predefined_search_values'][$searchable_attribute])) {
          $predefined_search_values = $wp_properties['predefined_search_values'][$searchable_attribute];
          $predefined_search_values = str_replace(array(', ', ' ,'), array(',', ','), trim($predefined_search_values));
          $predefined_search_values = explode(',', $predefined_search_values);

          if (is_array($predefined_search_values)) {
            foreach ($predefined_search_values as $value) {
              $range[$searchable_attribute][] = $value;
            }
          } else {
            $range[$searchable_attribute][] = $predefined_search_values;
          }

        } elseif (array_key_exists($searchable_attribute, $non_post_meta)) {

          $type = $non_post_meta[$searchable_attribute];

          //** No predefined value exist */
          $db_values = $wpdb->get_col("
            SELECT DISTINCT(" . ($type == 'data' ? "DATE_FORMAT(p1.{$searchable_attribute}, '%Y%m')" : "p1.{$searchable_attribute}") . ")
            FROM {$wpdb->posts} p1
            LEFT JOIN {$wpdb->postmeta} pm2 ON p1.ID = pm2.post_id
            WHERE pm2.meta_key = 'property_type' 
              AND p1.post_status = 'publish'
              $searchable_property_types_sql
            order by p1.{$searchable_attribute}
          ");

          //* Get all available values for this attribute for this property_type */
          $range[$searchable_attribute] = $db_values;

        } else {

          //** No predefined value exist */
          $db_values = $wpdb->get_col("
            SELECT DISTINCT(pm1.meta_value)
            FROM {$wpdb->posts} p1
            LEFT JOIN {$wpdb->postmeta} pm1 ON p1.ID = pm1.post_id
            LEFT JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = '{$searchable_attribute}' 
              AND pm2.meta_key = 'property_type'
              AND pm1.meta_value != ''
              AND p1.post_status = 'publish'
              $searchable_property_types_sql
            ORDER BY " . ($is_numeric ? 'ABS(' : '') . "pm1.meta_value" . ($is_numeric ? ')' : '') . " ASC
          ");

          //* Get all available values for this attribute for this property_type */
          $range[$searchable_attribute] = $db_values;

        }

        //** Get unique values*/
        if (is_array($range[$searchable_attribute])) {
          $range[$searchable_attribute] = array_unique($range[$searchable_attribute]);
        } else {
          //* This should not happen */
        }

        foreach ($range[$searchable_attribute] as $key => $value) {

          $original_value = $value;

          // Clean up values if a conversion exists
          $value = WPP_F::do_search_conversion($searchable_attribute, trim($value));

          // Fix value with special chars. Disabled here, should only be done in final templating stage.
          // $value = htmlspecialchars($value, ENT_QUOTES);

          //* Remove bad characters signs if attribute is numeric or currency */
          if ($is_numeric) {
            $value = str_replace(array(",", "$"), '', $value);
          }

          //** Put cleaned up value back into array */
          $range[$searchable_attribute][$key] = $value;

        }

        //** Sort values */
        sort($range[$searchable_attribute], SORT_REGULAR);

      } //** End single attribute data gather */

      $result = $range;

      if ($instance_id && $cache) {
        WPP_F::set_cache($instance_id, $result);
      }
    }

    foreach ($result as $attribute_slug => &$value) {
      $value = apply_filters('wpp::attribute::value', $value, $attribute_slug);
      if(!is_array($value))
        $value = explode(',', $value);
      $value = array_map('trim', $value );
    }
    
    return apply_filters('wpp::get_search_values', $result, array(
      'search_attributes' => $search_attributes,
      'searchable_property_types' => $searchable_property_types,
      'cache' => $cache,
      'instance_id' => $instance_id,
    ));
  }

  /**
   * Check if a search converstion exists for a attributes value
   */
  static public function do_search_conversion($attribute, $value, $reverse = false)
  {
    global $wp_properties;

    if (!isset($wp_properties['search_conversions'][$attribute])) {
      return $value;
    }

    // First, check if any conversions exists for this attribute, if not, return value
    if (count($wp_properties['search_conversions'][$attribute]) < 1) {
      return $value;
    }

    // If reverse is set to true, means we are trying to convert a value to integerer (most likely),
    // For isntance: in "bedrooms", $value = 0 would be converted to "Studio"
    if ($reverse) {

      $flipped_conversion = array_flip($wp_properties['search_conversions'][$attribute]);

      if (!empty($flipped_conversion[$value])) {
        return $flipped_conversion[$value];
      }

    }
    // Need to $conversion == '0' or else studios will not work, since they have 0 bedrooms
    $conversion = isset($wp_properties['search_conversions'][$attribute][$value]) ? $wp_properties['search_conversions'][$attribute][$value] : false;
    if ($conversion === '0' || !empty($conversion))
      return $conversion;

    // Return value in case something messed up
    return $value;

  }

  /**
   * Primary static function for queries properties  based on type and attributes
   *
   * @todo There is a limitation when doing a search such as 4,5+ then mixture of specific and open ended search is not supported.
   * @since 1.08
   *
   * @param string $args / $args
   *
   * @param bool $total
   *
   * @return bool|mixed|void
   */
  static public function get_properties($args = "", $total = false)
  {
    global $wpdb, $wp_properties, $wpp_query;


    //** Cleanup (fix) ID argument if it's passed */
    $args = wp_parse_args($args);
    if (isset($args['id'])) {
      $args['ID'] = $args['id'];
      unset($args['id']);
    }
    //** property_id is replaced with ID only if Property Attribute with slug 'property_id' does not exist */
    if (isset($args['property_id']) && !key_exists('property_id', $wp_properties['property_stats'])) {
      $args['ID'] = $args['property_id'];
      unset($args['property_id']);
    }

    //** Prints args to firebug if debug mode is enabled */
    $log = is_array($args) ? urldecode(http_build_query($args)) : $args;

    //** The function can be overwritten using the filter below. */
    $response = apply_filters('wpp::get_properties::custom', null, $args, $total);
    if ($response !== null) {
      return $response;
    }

    $_query_keys = array();

    /* Define keys that should not be used to query data */
    $_system_keys = array(
      'pagi',
      'pagination',
      'limit_query',
      'starting_row',
      'sort_by',
      'sort_order'
    );

    // Non post_meta fields
    $non_post_meta = array(
      'post_title' => 'like',
      'post_status' => 'equal',
      'post_author' => 'equal',
      'ID' => 'or',
      'post_parent' => 'equal',
      'post_date' => 'date'
    );

    /**
     * Specific meta data can contain value with commas. E.g. location field ( address_attribute )
     * The current list contains meta slugs which will be ignored for comma parsing. peshkov@UD
     */
    $commas_ignore = apply_filters('wpp::get_properties::commas_ignore', array_filter(array($wp_properties['configuration']['address_attribute'])));

    $capture_sql_args = array('limit_query');

    //** added to avoid range and "LIKE" searches on single numeric values *
    if (is_array($args)) {
      foreach ((array)$args as $thing => $value) {

        if (in_array($thing, (array)$capture_sql_args)) {
          $sql_args[$thing] = $value;
          unset($args[$thing]);
          continue;
        }

        // unset empty filter options
        if (empty($value)) {
          unset($args[$thing]);
          continue;
        }

        if (is_array($value)) {
          $value = implode(',', $value);
        }
        $value = trim($value);

        $original_value = $value;

        $numeric = !empty($wp_properties['numeric_attributes']) && in_array($thing, (array)$wp_properties['numeric_attributes']) ? true : false;

        //** If not CSV and last character is a +, we look for open-ended ranges, i.e. bedrooms: 5+
        if (substr($original_value, -1, 1) == '+' && !strpos($original_value, ',') && $numeric) {
          //** User requesting an open ended range, we leave it off with a dash, i.e. 500- */
          $args[$thing] = str_replace('+', '', $value) . '-';
        } elseif (is_numeric($value) && $numeric) {
          //** If number is numeric, we do a specific search, i.e. 500-500 */
          if (!array_key_exists($thing, $non_post_meta)) {
            $args[$thing] = $value . '-' . $value;
          }
        } elseif (is_string($value)) {
          $args[$thing] = $value;
        }
      }
    }

    $defaults = array(
      'property_type' => 'all',
      'pagi' => false,
      'sort_by' => false,
    );

    $query = wp_parse_args($args, $defaults);

    $query = apply_filters('wpp_get_properties_query', $query);

    //WPP_F::console_log("get_properties() args: {$log}");
    WPP_F::debug("get_properties()", array( 'query' => $query, 'args' => $args )  );

    $query_keys = array_keys((array)$query);

    //** Search by non meta values */
    $additional_sql = '';

    //** Show 'publish' posts if status is not specified */
    if (!array_key_exists('post_status', $query)) {
      $additional_sql .= " AND p.post_status = 'publish' ";
    } else {
      if ($query['post_status'] != 'all') {
        if (strpos($query['post_status'], ',') === false) {
          $additional_sql .= " AND p.post_status = '{$query['post_status']}' ";
        } else {
          $post_status = explode(',', $query['post_status']);
          foreach ($post_status as &$ps) {
            $ps = trim($ps);
          }
          $additional_sql .= " AND p.post_status IN ( '" . implode("','", $post_status) . "') ";
        }
      } else {
        $additional_sql .= " AND p.post_status <> 'auto-draft' ";
      }
      unset($query['post_status']);
    }

    foreach ((array)$non_post_meta as $field => $condition) {
      if (array_key_exists($field, $query)) {
        if ($condition == 'like') {
          $additional_sql .= " AND p.$field LIKE '%{$query[$field]}%' ";
        } else if ($condition == 'equal') {
          $additional_sql .= " AND p.$field = '{$query[$field]}' ";
        } else if ($condition == 'or') {
          $f = '';
          $d = !is_array($query[$field]) ? explode(',', $query[$field]) : $query[$field];
          foreach ($d as $k => $v) {
            $f .= (!empty($f) ? ",'" . trim($v) . "'" : "'" . trim($v) . "'");
          }
          $additional_sql .= " AND p.$field IN ({$f}) ";
        } else if ($condition == 'date') {
          $additional_sql .= " AND YEAR( p.$field ) = " . substr($query[$field], 0, 4) . " AND MONTH( p.$field ) = " . substr($query[$field], 4, 2) . " ";
        }
        unset($query[$field]);
      }
    }

    if (!empty($sql_args['limit_query'])) {
      $sql_args['starting_row'] = ($sql_args['starting_row'] ? $sql_args['starting_row'] : 0);
      $limit_query = "LIMIT {$sql_args['starting_row']}, {$sql_args['limit_query']};";

    } elseif (substr_count($query['pagi'], '--')) {
      $pagi = explode('--', $query['pagi']);
      if (count($pagi) == 2 && is_numeric($pagi[0]) && is_numeric($pagi[1])) {
        $limit_query = "LIMIT $pagi[0], $pagi[1];";
      }
    }

    /** Handles the sort_by parameter in the Short Code */
    if ($query['sort_by']) {
      $sql_sort_by = $query['sort_by'];
      $sql_sort_order = isset($query['sort_order']) ? strtoupper($query['sort_order']) : 'ASC';
    } else {
      $sql_sort_by = 'post_date';
      $sql_sort_order = 'ASC';
    }

    //** Unsert arguments that will conflict with attribute query */
    foreach ((array)$_system_keys as $system_key) {
      unset($query[$system_key]);
    }

    // Go down the array list narrowing down matching properties
    foreach ((array)$query as $meta_key => $criteria) {

      $specific = '';

      // Stop filtering ( loop ) because no IDs left
      if (isset($matching_ids) && empty($matching_ids)) {
        break;
      }

      $numeric = (isset($wp_properties['numeric_attributes']) && in_array($meta_key, (array)$wp_properties['numeric_attributes'])) ? true : false;

      if (!in_array($meta_key, (array)$commas_ignore) && substr_count($criteria, ',') || (substr_count($criteria, '-') && $numeric) || substr_count($criteria, '--')) {

        if (substr_count($criteria, '-') && !substr_count($criteria, ',')) {
          $cr = explode('-', $criteria);
          // Check pieces of criteria. Array should contains 2 int's elements
          // In other way, it's just value of meta_key
          if (count($cr) > 2 || (( float )$cr[0] == 0 && ( float )$cr[1] == 0)) {
            $specific = $criteria;
          } else {
            $hyphen_between = $cr;
            // If min value doesn't exist, set 1
            if (empty($hyphen_between[0]) && $hyphen_between[0] != "0") {
              $hyphen_between[0] = 1;
            }
          }
        }

        if (substr_count($criteria, ',')) {
          $comma_and = explode(',', $criteria);
        }

      } else {
        $specific = $criteria;
      }

      if (!isset($limit_query)) {
        $limit_query = '';
      }
      switch ($meta_key) {

        case 'property_type':

          // Get all property types
          if ($specific == 'all') {
            if (isset($matching_ids)) {
              $matching_id_filter = implode("' OR ID ='", $matching_ids);
              $matching_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE (ID ='$matching_id_filter' ) AND post_type = 'property'");
            } else {
              $matching_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'property'");
            }
            break;
          }

          //** If comma_and is set, $criteria is ignored, otherwise $criteria is used */
          $property_type_array = isset($comma_and) && is_array($comma_and) ? $comma_and : array($specific);

          //** Make sure property type is in slug format */
          foreach ($property_type_array as $key => $this_property_type) {
            foreach ((array)$wp_properties['property_types'] as $pt_key => $pt_value) {
              if (strtolower($pt_value) == strtolower($this_property_type)) {
                $property_type_array[$key] = $pt_key;
              }
            }
          }

          if (!empty($property_type_array)) {
            //** Multiple types passed */
            $where_string = implode("' OR meta_value ='", $property_type_array);
          } else {
            //** Only on type passed */
            $where_string = $property_type_array[0];
          }

          // See if mathinc_ids have already been filtered down
          if (isset($matching_ids)) {
            $matching_id_filter = implode("' OR post_id ='", $matching_ids);
            $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE (post_id ='$matching_id_filter' ) AND ( meta_key = 'property_type' AND (meta_value ='$where_string' ))");
          } else {
            $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE (meta_key = 'property_type' AND (meta_value ='$where_string' ))");
          }

          break;

        case apply_filters('wpp::get_properties::custom_case', false, $meta_key):

          $matching_ids = apply_filters('wpp::get_properties::custom_key', $matching_ids, $meta_key, $criteria);

          break;

        default:

          // Get all properties for that meta_key
          if ($specific == 'all' && empty($comma_and) && empty($hyphen_between)) {

            if (isset($matching_ids)) {
              $matching_id_filter = implode("' OR post_id ='", $matching_ids);
              $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE (post_id ='$matching_id_filter' ) AND ( meta_key = '$meta_key' )");
            } else {
              $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE (meta_key = '$meta_key' )");
            }
            break;

          } else {

            if (!empty($comma_and)) {
              $where_and = "( meta_value ='" . implode("' OR meta_value ='", $comma_and) . "')";
              $specific = $where_and;
            }

            if (!empty($hyphen_between)) {
              // We are going to see if we are looking at some sort of date, in which case we have a special MySQL modifier
              $adate = false;
              if (preg_match('%\\d{1,2}/\\d{1,2}/\\d{4}%i', $hyphen_between[0])) $adate = true;

              if (!empty($hyphen_between[1])) {

                if (preg_match('%\\d{1,2}/\\d{1,2}/\\d{4}%i', $hyphen_between[1])) {
                  foreach ($hyphen_between as $key => $value) {
                    $value = date('m-d-Y', strtotime($value));
                    $hyphen_between[$key] = "STR_TO_DATE( '{$value}', '%m-%d-%Y' )";
                  }
                  $where_between = "STR_TO_DATE( `meta_value`, '%Y-%m-%d' ) BETWEEN " . implode(" AND ", $hyphen_between) . "";
                } else {
                  $where_between = "`meta_value` BETWEEN " . implode(" AND ", $hyphen_between) . "";
                }

              } else {

                if ($adate) {
                  $where_between = "STR_TO_DATE( `meta_value`, '%Y-%m-%d' ) >= STR_TO_DATE( '{$hyphen_between[0]}', '%m-%d-%Y' )";
                } else {
                  $where_between = "`meta_value` >= $hyphen_between[0]";
                }

              }
              $specific = $where_between;
            }

            if ($specific == 'true') {
              // If properties data were imported, meta value can be '1' instead of 'true'
              // So we're trying to find also '1'
              $specific = "meta_value IN ( 'true', '1' )";
            } elseif (!substr_count($specific, 'meta_value')) {
              //** Determine if we don't need to use LIKE in SQL query */
              preg_match("/^#(.+)#$/", $specific, $matches);
              if ($matches) {
                $specific = " meta_value = '{$matches[1]}'";
              } else {
                //** Adds conditions for Searching by partial value */
                $s = explode(' ', trim($specific));
                $specific = '';
                $count = 0;
                foreach ($s as $p) {
                  if ($count > 0) {
                    $specific .= " AND ";
                  }
                  $specific .= "meta_value LIKE '%{$p}%'";
                  $count++;
                }
              }
            }

            if (isset($matching_ids)) {
              $matching_id_filter = implode(",", $matching_ids);
              $sql_query = "SELECT post_id FROM {$wpdb->postmeta} WHERE post_id IN ( $matching_id_filter ) AND meta_key = '$meta_key' AND $specific";
            } else {
              $sql_query = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '$meta_key' AND $specific";
            }

            //** Some specific additional conditions can be set in filters */
            $sql_query = apply_filters('wpp::get_properties::meta_key::sql_query', $sql_query, array(
              'meta_key' => $meta_key,
              'specific' => $specific,
              'matching_id_filter' => isset($matching_id_filter) ? $matching_id_filter : false,
              'criteria' => $criteria,
            ));

            $matching_ids = $wpdb->get_col($sql_query);

          }
          break;

      } // END switch

      unset($comma_and);
      unset($hyphen_between);

    } // END foreach

    // Return false, if there are any result using filter conditions
    if (empty($matching_ids)) {
      return false;
    }

    // Remove duplicates
    $matching_ids = array_unique($matching_ids);

    $matching_ids = apply_filters('wpp::get_properties::matching_ids', $matching_ids, array_merge((array)$query, array(
      'additional_sql' => $additional_sql,
      'total' => $total,
    )));

    $result = apply_filters('wpp::get_properties::custom_sort', false, array(
      'matching_ids' => $matching_ids,
      'additional_sql' => $additional_sql,
      'sort_by' => $sql_sort_by,
      'sort_order' => $sql_sort_order,
      'limit_query' => $limit_query,
    ));

    if (!$result) {

      // Sorts the returned Properties by the selected sort order
      if ($sql_sort_by &&
        $sql_sort_by != 'menu_order' &&
        $sql_sort_by != 'post_date' &&
        $sql_sort_by != 'post_modified' &&
        $sql_sort_by != 'post_title'
      ) {

        //** Sorts properties in random order. */
        if ($sql_sort_by === 'random') {

          $result = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts } AS p
            WHERE ID IN (" . implode(",", $matching_ids) . ")
            $additional_sql
            ORDER BY RAND() $sql_sort_order
            $limit_query");

        } else {

          //** Determine if attribute has numeric format or all values of meta_key are numbers we use CAST in SQL query to avoid sort issues */
          if ((isset($wp_properties['numeric_attributes']) && in_array($sql_sort_by, $wp_properties['numeric_attributes'])) ||
            self::meta_has_number_data_type($matching_ids, $sql_sort_by)
          ) {
            $meta_value = "CAST( meta_value AS DECIMAL(20,3)) {$sql_sort_order}, p.ID";
          } else {
            $meta_value = "meta_value";
          }

          $result = $wpdb->get_col("
            SELECT p.ID , (SELECT pm.meta_value FROM {$wpdb->postmeta} AS pm WHERE pm.post_id = p.ID AND pm.meta_key = '{$sql_sort_by}' LIMIT 1 ) as meta_value
              FROM {$wpdb->posts} AS p
              WHERE p.ID IN ( " . implode(",", $matching_ids) . ")
              {$additional_sql}
              ORDER BY {$meta_value} {$sql_sort_order}
              {$limit_query}");

        }

      } else {

        $result = $wpdb->get_col("
          SELECT ID FROM {$wpdb->posts } AS p
          WHERE ID IN (" . implode(",", $matching_ids) . ")
          $additional_sql
          ORDER BY $sql_sort_by $sql_sort_order
          $limit_query");

      }

    }

    // Stores the total Properties returned
    if ($total) {
      $total = count($wpdb->get_col("
        SELECT p.ID
          FROM {$wpdb->posts} AS p
          WHERE p.ID IN (" . implode(",", $matching_ids) . ")
          {$additional_sql}"));
    }

    WPP_F::console_log("get_properties() total: $total");

    self::debug('get_properties', $args);

    if (!empty($result)) {
      $return = array();
      if (!empty($total)) {
        $return['total'] = $total;
        $return['results'] = $result;
      } else {
        $return = $result;
      }

      return apply_filters('wpp::get_properties::result', $return, $args);
    }

    return false;

  }

  /**
   * Determine if property has children
   *
   * @param int $id
   *
   * @return boolean
   * @author peshkov@UD
   * @since 1.37.5
   */
  static public function has_children($id)
  {

    $children = get_posts(array(
      'post_type' => 'property',
      'post_parent' => $id
    ));

    if (!empty($children)) {
      return true;
    }

    return false;
  }

  /**
   * Prepares Request params for get_properties() function
   *
   * @param array $attrs
   * @return array $attrs
   */
  static public function prepare_search_attributes($attrs)
  {
    global $wp_properties;

    $prepared = array();

    $non_numeric_chars = apply_filters('wpp_non_numeric_chars', array('-', '$', ','));

    foreach ($attrs as $search_key => $search_query) {

      //** Fix search form passed paramters to be usable by get_properties();
      if (is_array($search_query)) {
        //** Array variables are either option lists or minimum and maxim variables
        $stack = array_keys($search_query);
        if (is_numeric(array_shift($stack))) {
          //** get regular arrays (non associative) */
          $search_query = implode(',', $search_query);
        } elseif (isset($search_query['options']) && is_array($search_query['options'])) {
          //** Get queries with options */
          $search_query = implode(',', $search_query['options']);
        } elseif (in_array('min', array_keys($search_query)) ||
          in_array('max', array_keys($search_query))
        ) {
          //** Get arrays with minimum and maxim ranges */

          //* There is no range if max value is empty and min value is -1 */
          if ($search_query['min'] == '-1' && empty($search_query['max'])) {
            $search_query = '-1';
          } else {
            //* Set range */
            //** Ranges are always numeric, so we clear it up */
            foreach ($search_query as $range_indicator => $value) {
              $search_query[$range_indicator] = str_replace($non_numeric_chars, '', $value);
            }

            if (empty($search_query['min']) && empty($search_query['max'])) {
              continue;
            }

            if (empty($search_query['min'])) {
              $search_query['min'] = '0';
            }

            if (empty($search_query['max'])) {
              $search_query = $search_query['min'] . '+';
            } else {
              $search_query = str_replace($non_numeric_chars, '', $search_query['min']) . '-' . str_replace($non_numeric_chars, '', $search_query['max']);
            }
          }
        } elseif (in_array('from', array_keys($search_query)) ||
          in_array('to', array_keys($search_query))
        ) {
          //** Get arrays with from and to date ranges */

          //* There is no range if max value is empty and min value is -1 */
          if (empty($search_query['from']) && empty($search_query['to'])) {
            continue;
          }
          //* Set range */
          $search_query = date('m/d/Y', strtotime($search_query['from'])) . '-' . date('m/d/Y', strtotime($search_query['to']));
        }
      }

      if (is_string($search_query)) {
        if ($search_query != '-1' && $search_query != '-') {
          $prepared[$search_key] = trim($search_query);
        }
      }

    }

    return $prepared;
  }

  /**
   * Returns array of all values for a particular attribute/meta_key
   */
  static public function get_all_attribute_values($slug)
  {
    global $wpdb;

    // Non post_meta fields
    $non_post_meta = array(
      'post_title',
      'post_status',
      'post_author',
      'post_date'
    );

    if (!in_array($slug, $non_post_meta))
      $prefill_meta = $wpdb->get_col("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '$slug'");
    else
      $prefill_meta = $wpdb->get_col("SELECT $slug FROM {$wpdb->posts} WHERE post_type = 'property' AND post_status != 'auto-draft'");
    /**
     * @todo check if this condition is required - Anton Korotkov
     */
    /*if(empty($prefill_meta[0]))
      unset($prefill_meta);*/

    $prefill_meta = apply_filters('wpp_prefill_meta', $prefill_meta, $slug);

    if (count($prefill_meta) < 1)
      return false;

    $return = array();
    // Clean up values
    foreach ($prefill_meta as $meta) {

      if (empty($meta))
        continue;

      $return[] = $meta;

    }

    if (!empty($return) && !empty($return)) {
      // Remove duplicates
      $return = array_unique($return);

      sort($return);

    }

    return $return;

  }

  /**
   * Load property information into an array or an object
   * Deprecated since 2.1.1
   *
   * @param mix ID or post object
   * @param array $args
   * @return mix array or object
   */
  static public function get_property($id, $args = array())
  {
    return \UsabilityDynamics\WPP\Property_Factory::get($id, $args);
  }

  /**
   * Gets prefix to an attribute
   *
   * @todo This should be obsolete, in any case we can't assume everyone uses USD - potanin@UD (11/22/11)
   *
   */
  static public function get_attrib_prefix($attrib)
  {

    if ($attrib == 'price') {
      return "$";
    }

    if ($attrib == 'deposit') {
      return "$";
    }

  }

  /**
   * Gets annex to an attribute. (Unused Function)
   *
   * @todo This function does not seem to be used by anything. potanin@UD (11/12/11)
   *
   */
  static public function get_attrib_annex($attrib)
  {

    if ($attrib == 'area') {
      return __(' sq ft.', ud_get_wp_property()->domain);
    }

  }

  /**
   * Get coordinates for property out of database
   * @param bool $listing_id
   * @return array|bool
   */
  static public function get_coordinates($listing_id = false)
  {
    global $post, $property;

    if (!$listing_id) {
      if (empty($property)) {
        return false;
      }
      $listing_id = is_object($property) ? $property->ID : $property['ID'];
    }

    $latitude = get_post_meta($listing_id, 'latitude', true);
    $longitude = get_post_meta($listing_id, 'longitude', true);

    if (empty($latitude) || empty($longitude)) {
      /** Try parent */
      if (!empty($property->parent_id)) {
        $latitude = get_post_meta($property->parent_id, 'latitude', true);
        $longitude = get_post_meta($property->parent_id, 'longitude', true);
      }
      /** Still nothing */
      if (empty($latitude) || empty($longitude)) {
        return false;
      }
    }

    return array('latitude' => $latitude, 'longitude' => $longitude);
  }

  /**
   * Validate if a URL is valid.
   */
  static public function isURL($url)
  {
    if( is_string( $url ) ) {
      return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
    }

    return false;
  }

  /**
   * Validate if a URL is a valid image.
   */
  static public function isIMG($url)
  {
    return preg_match('@(?:([^:/?#]+):)?(?://([^/?#]*))?([^?#]*\.(?:jpg|jpeg|gif|png))(?:\?([^#]*))?(?:#(.*))?@i', $url);
  }

  /**
   * Determine if a email is valid.
   *
   * @param $value
   *
   * @return boolean
   */
  static public function is_email($value)
  {
    return preg_match('/^[_a-z0-9-]+(.[_a-z0-9-]+)*@[a-z0-9-]+(.[a-z0-9-]+)*(.[a-z]{2,3})$/', strtolower($value));
  }

  /**
   * Returns an array of a property's stats and their values.
   *
   * Query is array of variables to use load ours to avoid breaking property maps.
   *
   * @since 1.0
   *
   */
  static public function get_stat_values_and_labels($property_object, $args = false)
  {
    global $wp_properties;

    $defaults = array(
      'label_as_key' => 'true',
    );
    $return_multi = ud_get_wp_property('attributes.multiple', array());
    // Need to improve the way
    // By: Md. Alimuzzaman Alim
    if (($key = array_search('multi_checkbox', $return_multi)) !== false) {
      unset($return_multi[$key]);
    }
    if (is_array($property_object)) {
      $property_object = (object)$property_object;
    }

    $property_types = $wp_properties['property_types'];
    $property_type = '';
    if (!empty($property_object->property_type)) {
      if (array_key_exists($property_object->property_type, $property_types)) {
        $property_type = $property_object->property_type;
      } else {
        $property_type = array_search($property_object->property_type, $property_types);
      }
    }
    extract(wp_parse_args($args, $defaults), EXTR_SKIP);

    $exclude = isset($exclude) ? (is_array($exclude) ? $exclude : explode(',', $exclude)) : false;
    $include = isset($include) ? (is_array($include) ? $include : explode(',', $include)) : false;

    if (empty($property_stats)) {
      $property_stats = $wp_properties['property_stats'];
    }

    $return = array();
    $parent_property_object = "";
    if (isset($property_object->is_child) && $property_object->is_child &&
      isset($property_object->parent_id) && $property_object->parent_id
    ) {
      $parent_property_object = UsabilityDynamics\WPP\Property_Factory::get($property_object->parent_id, array('return_object' => 'true'));
    }

    foreach ($property_stats as $slug => $label) {

      // Determine if it's frontend and the attribute is hidden for frontend
      if (
        isset($wp_properties['hidden_frontend_attributes'])
        && in_array($slug, (array)$wp_properties['hidden_frontend_attributes'])
        && !current_user_can('manage_options')
      ) {
        continue;
      }

      // Exclude passed variables
      if (is_array($exclude) && in_array($slug, $exclude)) {
        continue;
      }

      $_property_object = $property_object;
      $attribute_data = UsabilityDynamics\WPP\Attributes::get_attribute_data($slug);
      $input_type = isset($attribute_data['data_input_type']) ? $attribute_data['data_input_type'] : false;

      if (!$input_type)
        $input_type = isset($attribute_data['input_type']) ? $attribute_data['input_type'] : "";

      $single = !in_array($input_type, $return_multi);
      if (is_object($parent_property_object) &&
        isset($attribute_data['inheritance']) &&
        in_array($property_type, $attribute_data['inheritance'])
      ) {
        $_property_object = $parent_property_object;
      }

      if ($single && !empty($_property_object->{$slug})) {
        $value = $_property_object->{$slug};
      } else {
        $value = get_post_meta($_property_object->ID, $slug, $single);
      }

      if ($value === true) {
        $value = 'true';
      }

      //** Override property_type slug with label */
      if ($slug == 'property_type') {
        $value = $_property_object->property_type_label;
      }

      // Include only passed variables
      if (is_array($include) && in_array($slug, $include)) {
        if (!empty($value)) {
          if ($label_as_key == 'true') $return[$label] = $value;
          else $return[$slug] = array('label' => $label, 'value' => $value);
        }
        continue;
      }

      if (!is_array($include)) {
        if (!empty($value)) {
          if ($label_as_key == 'true') $return[$label] = $value;
          else $return[$slug] = array('label' => $label, 'value' => $value);
        }
      }

    }

    if (count($return) > 0) {
      return $return;
    }

    return false;

  }

  static public function array_to_object($array = array())
  {
    if (is_array($array)) {
      $data = new stdClass();

      foreach ($array as $akey => $aval) {
        if( empty( $akey ) ) {
          continue;
        }
        $data->{$akey} = $aval;
      }

      return $data;
    }

    return (object)false;
  }

  /**
   * Returns a minified Google Maps Infobox
   *
   * Used in property map and supermap
   *
   * @filter wpp_google_maps_infobox
   * @version 1.11 - added return if $post or address attribute are not set to prevent fatal error
   * @since 1.081
   *
   */
  static public function google_maps_infobox($post, $args = false)
  {
    global $wp_properties;

    $map_image_type = $wp_properties['configuration']['single_property_view']['map_image_type'];
    $infobox_attributes = $wp_properties['configuration']['google_maps']['infobox_attributes'];
    $infobox_settings = $wp_properties['configuration']['google_maps']['infobox_settings'];

    if (empty($wp_properties['configuration']['address_attribute'])) {
      return;
    }

    if (empty($post)) {
      return;
    }

    if (is_array($post)) {
      $post = (object)$post;
    }

    $property = (array)prepare_property_for_display($post, array(
      'load_gallery' => 'false',
      'scope' => 'google_map_infobox'
    ));

    if (empty($infobox_attributes)) {
      $infobox_attributes = array(
        //'price',
        //'bedrooms',
        //'bathrooms'
      );
    }

    if (empty($infobox_settings)) {
      $infobox_settings = array(
        'show_direction_link' => true,
        'show_property_title' => true
      );
    }

    $infobox_style = (!empty($infobox_settings['minimum_box_width'])) ? 'style="min-width: ' . $infobox_settings['minimum_box_width'] . 'px;"' : '';

    $property_stats = array();
    foreach ($infobox_attributes as $attribute) {
      if (!empty($wp_properties['property_stats'][$attribute])) {
        $property_stats[$attribute] = $wp_properties['property_stats'][$attribute];
      }
    }

    if (!empty($property_stats)) {
      $property_stats = WPP_F::get_stat_values_and_labels($property, array(
        'property_stats' => $property_stats
      ));
    }

    //** Check if we have children */
    if (!empty($property['children']) && (!isset($wp_properties['configuration']['google_maps']['infobox_settings']['do_not_show_child_properties']) || $wp_properties['configuration']['google_maps']['infobox_settings']['do_not_show_child_properties'] != 'true')) {
      foreach ($property['children'] as $child_property) {
        $child_property = (array)$child_property;
        if ($infobox_settings['show_child_property_attributes'] === 'true') {
          $html_child_properties_attributes = '<ul class="ir__child_property_attributes">';
          foreach ($infobox_attributes as $attribute) {
            if (!empty($child_property[$attribute])) {
              $attribute_data = WPP_F::get_attribute_data($attribute);
              $html_child_properties_attributes .= '<li>' . $attribute_data['title'] . ': ' . $child_property[$attribute] . '</li>';
            }
          }
          $html_child_properties_attributes .= '</ul>';
        }
        $html_child_properties[] = '<a href="' . get_permalink($child_property['ID']) . '">' . $child_property['post_title'] . '</a>' . $html_child_properties_attributes;
      }
    }

    if (!empty($property['featured_image'])) {
      $image = wp_get_attachment_image_src($property['featured_image'], $map_image_type);
      if (!empty($image) && is_array($image)) {
        $imageHTML = "<img width=\"{$image[1]}\" height=\"{$image[2]}\" src=\"{$image[0]}\" alt=\"" . addslashes($post->post_title) . "\" />";
        if (@$wp_properties['configuration']['property_overview']['fancybox_preview'] == 'true' && !empty($property['featured_image_url'])) {
          $imageHTML = "<a href=\"{$property['featured_image_url']}\" class=\"fancybox_image thumbnail\">{$imageHTML}</a>";
        }
      }
    }

    ob_start();
    if( isset( $infobox_settings['infowindow_styles'] ) && $infobox_settings['infowindow_styles'] === 'new') {
      ?>

      <div id="infowindow" class="infowindow-style-new" <?php echo $infobox_style; ?>>

        <div class="infowindow_box">
          <?php if (!empty($imageHTML)) { ?>
            <div class="infowindow_left">
              <div class="il__image">
                <?php echo $imageHTML; ?>
              </div>
              <div class="il__title">
                <?php if (!empty($property['price'])) : ?><label><?php echo $property['price']; ?></label><?php endif; ?>
                <?php if (!empty($property['post_title'])) : ?>
                  <div class="property-title"><a
                    href="<?php echo get_permalink($property['ID']); ?>"><?php echo $property['post_title']; ?></a>
                  </div><?php endif; ?>
                <?php if (!empty($property['display_address']) && !empty($property['latitude']) && !empty($property['longitude'])) : ?>
                  <a target="_blank"
                     href="http://maps.google.com/maps?gl=us&daddr=<?php echo $property['latitude'] ?>,<?php echo $property['longitude']; ?>"
                     target="_blank"><?php echo $property['display_address']; ?></a><?php endif; ?>
              </div>
            </div>
          <?php } ?>

          <div class="infowindow_right <?php echo !empty($imageHTML) ? '' : ' infowindow_full_width'; ?> ">
            <?php if (empty($imageHTML)) : ?>
              <div class="ib__title"><?php echo $property['post_title']; ?></div>
              <div class="ib__location_link">
                <a target="_blank"
                   href="http://maps.google.com/maps?gl=us&daddr=<?php echo $property['latitude'] ?>,<?php echo $property['longitude']; ?>"
                   target="_blank"><?php echo $property['display_address']; ?></a>
              </div>
              <div class="ib__price"><?php echo !empty($property['price'])?$property['price']:''; ?></div>
            <?php endif; ?>

            <?php
            $content = $property['post_content'];
            if (!empty($content)) :
              echo '<div class="ir__title ir__title_description">' . __('Description', ud_get_wp_property()->domain) . '</div>';
              echo '<div class="ir__description">' . substr($content, 0, 100) . '...</div>';
            endif;
            ?>

            <?php
            $attributes = array();

            $labels_to_keys = array_flip($wp_properties['property_stats']);

            if (is_array($property_stats)) {
              foreach ($property_stats as $attribute_label => $value) {

                $attribute_slug = $labels_to_keys[$attribute_label];
                $attribute_data = UsabilityDynamics\WPP\Attributes::get_attribute_data($attribute_slug);

                if (empty($value)) {
                  continue;
                }

                if ((!empty($attribute_data['data_input_type']) && $attribute_data['data_input_type'] == 'checkbox' && ($value == 'true' || $value == 1))) {
                  if ($wp_properties['configuration']['google_maps']['show_true_as_image'] == 'true') {
                    $value = '<div class="true-checkbox-image"></div>';
                  } else {
                    $value = __('Yes', ud_get_wp_property()->domain);
                  }
                } elseif ($value == 'false') {
                  if ($wp_properties['configuration']['google_maps']['show_true_as_image'] == 'true') {
                    $value = '<div class="false-checkbox-image"></div>';
                  } else {
                    $value = __('No', ud_get_wp_property()->domain);
                  }
                }

                // to get attribute label and value translation @auther fadi
                $attribute_label = apply_filters('wpp::attribute::label', $attribute_label, $attribute_slug);
                if ($attribute_slug == 'property_type') {
                  $value = apply_filters("wpp_stat_filter_property_type_label", $value);
                } elseif (!empty($wp_properties["predefined_values"][$attribute_slug])) {
                  $value = apply_filters("wpp::attribute::value", $value, $attribute_slug);
                }
                $attributes[] = '<li class="' . $attribute_slug . '">';
                $attributes[] = '<label>' . $attribute_label . '</label>';
                $attributes[] = '<span>' . $value . '</span>';
                $attributes[] = '</li>';
              }
            }

            if (count($attributes) > 0) {
              echo "<div class='ir__title'>" . __('Overview', ud_get_wp_property()->domain) . "</div>";
              echo '<ul class="ir__list">' . implode('', $attributes) . '</ul>';
            }

            if (!empty($html_child_properties)) {
              echo '<div class="ir__title">' . __('Child Properties', ud_get_wp_property()->domain) . '</div>';
              echo '<ul class="ir__child_properties_list">';
              foreach ($html_child_properties as $value) {
                echo '<li>' . $value . '</li>';
              }
              echo '</ul>';
            }

            if (!empty($imageHTML) && $infobox_settings['show_direction_link'] == 'true' && !empty($property['latitude']) && !empty($property['longitude'])) {
              ?>
              <div class="ir__directions">
                <a target="_blank"
                   href="http://maps.google.com/maps?gl=us&daddr=<?php echo $property['latitude'] ?>,<?php echo $property['longitude']; ?>"
                   target="_blank"><?php _e('Get directions', ud_get_wp_property()->domain); ?></a>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>

      <?php
    } else {
      ?>

      <div id="infowindow" <?php echo $infobox_style; ?>>
        <?php if ($infobox_settings['show_property_title'] == 'true') { ?>
          <div class="wpp_google_maps_attribute_row_property_title">
            <a href="<?php echo get_permalink($property['ID']); ?>"><?php echo $property['post_title']; ?></a>
          </div>
        <?php } ?>
        <?php 

        $show_direction_link = (!empty($imageHTML) && $infobox_settings['show_direction_link'] == 'true' && !empty($property['latitude']) && !empty($property['longitude']));
        $show_right_col = ((is_array($property_stats) && count($property_stats)) || !empty($html_child_properties));
          
        if(!empty($imageHTML) || $show_right_col){
        ?>
        <table cellpadding="0" cellspacing="0" class="wpp_google_maps_infobox_table" style="">
          <tr>
            <?php if (!empty($imageHTML)) { ?>
              <td class="wpp_google_maps_left_col" style="width: <?php echo $image[1]; ?>px">
                <?php echo $imageHTML; ?>
                <?php if ($infobox_settings['show_direction_link'] == 'true' && !empty($property['latitude']) && !empty($property['longitude'])): ?>
                  <div class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_directions_link">
                    <a target="_blank"
                       href="http://maps.google.com/maps?gl=us&daddr=<?php echo $property['latitude'] ?>,<?php echo $property['longitude']; ?>"
                       class="btn btn-info"><?php _e('Get Directions', ud_get_wp_property()->domain) ?></a>
                  </div>
                <?php endif; ?>
              </td>
            <?php } ?>

            <?php 

            if ($show_right_col) { ?>
            <td class="wpp_google_maps_right_col" vertical-align="top" style="vertical-align: top;">
              <?php if ($show_direction_link && empty($imageHTML)) { ?>
                <div class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_directions_link">
                  <a target="_blank"
                     href="http://maps.google.com/maps?gl=us&daddr=<?php echo $property['latitude'] ?>,<?php echo $property['longitude']; ?>"
                     class="btn btn-info"><?php _e('Get Directions', ud_get_wp_property()->domain) ?></a>
                </div>
                <?php
              }

              $attributes = array();

              $labels_to_keys = array_flip((array)$wp_properties['property_stats']);

              if (is_array($property_stats)) {
                foreach ($property_stats as $attribute_label => $value) {

                  $attribute_slug = $labels_to_keys[$attribute_label];
                  $attribute_data = UsabilityDynamics\WPP\Attributes::get_attribute_data($attribute_slug);

                  if (empty($value)) {
                    continue;
                  }

                  if ((!empty($attribute_data['data_input_type']) && $attribute_data['data_input_type'] == 'checkbox' && ($value == 'true' || $value == 1))) {
                    if ($wp_properties['configuration']['google_maps']['show_true_as_image'] == 'true') {
                      $value = '<div class="true-checkbox-image"></div>';
                    } else {
                      $value = __('Yes', ud_get_wp_property()->domain);
                    }
                  } elseif ($value == 'false') {
                    continue;
                  }
                  // to get attribute label and value translation @auther fadi
                  $attribute_label = apply_filters('wpp::attribute::label', $attribute_label, $attribute_slug);
                  if ($attribute_slug == 'property_type') {
                    $value = apply_filters("wpp_stat_filter_property_type_label", $value);
                  } elseif (!empty($wp_properties["predefined_values"][$attribute_slug])) {
                    $value = apply_filters("wpp::attribute::value", $value, $attribute_slug);
                  }

                  $attributes[] = '<li class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_' . $attribute_slug . '">';
                  $attributes[] = '<span class="attribute">' . $attribute_label . '</span>';
                  $attributes[] = '<span class="value">' . $value . '</span>';
                  $attributes[] = '</li>';
                }
              }

              if (count($attributes) > 0) {
                echo '<ul class="wpp_google_maps_infobox">' . implode('', $attributes) . '<li class="wpp_google_maps_attribute_row wpp_fillter_element">&nbsp;</li></ul>';
              }

              if (!empty($html_child_properties)) {
                echo '<ul class="infobox_child_property_list">' . implode('', $html_child_properties) . '<li class="infobox_child_property wpp_fillter_element">&nbsp;</li></ul>';
              }

              ?>

            </td>
            <?php } ?>
          </tr>
        </table>
        <?php } ?>

      </div>
      
    <?php
    }
      
    $data = ob_get_contents();
    $data = preg_replace(array('/[\r\n]+/'), array(""), $data);
    $data = addslashes($data);

    ob_end_clean();

    $data = apply_filters('wpp_google_maps_infobox', $data, $post);

    return $data;
  }

  /**
   * Returns property object for displaying on map
   *
   * Used for speeding up property queries, only returns:
   * ID, post_title, atitude, longitude, exclude_from_supermap, location, supermap display_attributes and featured image urls
   *
   * 1.11: addded htmlspecialchars and addslashes to post_title
   *
   * @since 1.11
   *
   */
  static public function get_property_map($id, $args = '')
  {
    global $wp_properties, $wpdb;

    $defaults = array(
      'thumb_type' => (!empty($wp_properties['feature_settings']['supermap']['supermap_thumb']) ? $wp_properties['feature_settings']['supermap']['supermap_thumb'] : 'thumbnail'),
      'return_object' => 'false',
      'map_image_type' => $wp_properties['configuration']['single_property_view']['map_image_type']
    );

    extract(wp_parse_args($args, $defaults), EXTR_SKIP);

    if (class_exists('class_wpp_supermap'))
      $display_attributes = $wp_properties['configuration']['feature_settings']['supermap']['display_attributes'];

    $return['ID'] = $id;

    $data = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = $id GROUP BY meta_key");

    foreach ($data as $row) {
      $return[$row->meta_key] = $row->meta_value;
    }

    $return['post_title'] = htmlspecialchars(addslashes($wpdb->get_var("SELECT post_title FROM {$wpdb->posts} WHERE ID = $id")));

    // Get Images
    $wp_image_sizes = get_intermediate_image_sizes();

    $thumbnail_id = get_post_meta($id, '_thumbnail_id', true);
    $attachments = get_children(array('post_parent' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC'));

    if ($thumbnail_id) {
      foreach ($wp_image_sizes as $image_name) {
        $this_url = wp_get_attachment_image_src($thumbnail_id, $image_name, true);
        $return['images'][$image_name] = $this_url[0];
      }

      $featured_image_id = $thumbnail_id;

    } elseif ($attachments) {
      foreach ($attachments as $attachment_id => $attachment) {

        foreach ($wp_image_sizes as $image_name) {
          $this_url = wp_get_attachment_image_src($attachment_id, $image_name, true);
          $return['images'][$image_name] = $this_url[0];
        }

        $featured_image_id = $attachment_id;
        break;
      }
    }

    if ($featured_image_id) {
      $return['featured_image'] = $featured_image_id;

      $image_title = $wpdb->get_var("SELECT post_title  FROM {$wpdb->prefix}posts WHERE ID = '$featured_image_id' ");

      $return['featured_image_title'] = $image_title;
      $return['featured_image_url'] = wp_get_attachment_url($featured_image_id);

    }

    return $return;

  }

  /**
   * Generates Global Property ID for standard reference point during imports.
   *
   * Property ID is currently not used.
   *
   * @return integer. Global ID number
   *
   * @param bool|int $property_id . Property ID.
   *
   * @param bool $check_existance
   *
   * @todo API call to UD server to verify there is no duplicates
   * @since 1.6
   */
  static public function get_gpid($property_id = false, $check_existance = false)
  {

    if ($check_existance && $property_id) {
      $exists = get_post_meta($property_id, 'wpp_gpid', true);

      if ($exists) {
        return $exists;
      }
    }

    return 'gpid_' . rand(1000000000, 9999999999);

  }

  /**
   * Generates Global Property ID if it does not exist
   *
   * @param bool $property_id
   *
   * @return string | Returns GPID
   * @since 1.6
   */
  static public function maybe_set_gpid($property_id = false)
  {

    if (!$property_id) {
      return false;
    }

    $exists = get_post_meta($property_id, 'wpp_gpid', true);

    if ($exists) {
      return $exists;
    }

    $gpid = WPP_F::get_gpid($property_id, true);

    update_post_meta($property_id, 'wpp_gpid', $gpid);

    return $gpid;

  }

  /**
   * Returns post_id fro GPID if it exists
   *
   * @since 1.6
   */
  static public function get_property_from_gpid($gpid = false)
  {
    global $wpdb;

    if (!$gpid) {
      return false;
    }

    $post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id  WHERE meta_key = 'wpp_gpid' AND meta_value = '{$gpid}' ");

    if (is_numeric($post_id)) {
      return $post_id;
    }

    return false;

  }

  /**
   * This static function is not actually used, it's only use to hold some common translations that may be used by our themes.
   *
   * Translations for Denali theme.
   *
   * @since 1.14
   *
   */
  static public function strings_for_translations()
  {

    __('General Settings', ud_get_wp_property()->domain);
    __('Find your property', ud_get_wp_property()->domain);
    __('Edit', ud_get_wp_property()->domain);
    __('City', ud_get_wp_property()->domain);
    __('Contact us', ud_get_wp_property()->domain);
    __('Login', ud_get_wp_property()->domain);
    __('Explore', ud_get_wp_property()->domain);
    __('Message', ud_get_wp_property()->domain);
    __('Phone Number', ud_get_wp_property()->domain);
    __('Name', ud_get_wp_property()->domain);
    __('E-mail', ud_get_wp_property()->domain);
    __('Send Message', ud_get_wp_property()->domain);
    __('Submit Inquiry', ud_get_wp_property()->domain);
    __('Inquiry', ud_get_wp_property()->domain);
    __('Comment About', ud_get_wp_property()->domain);
    __('Inquire About', ud_get_wp_property()->domain);
    __('Inquiry About:', ud_get_wp_property()->domain);
    __('Inquiry message:', ud_get_wp_property()->domain);
    __('You forgot to enter your e-mail.', ud_get_wp_property()->domain);
    __('You forgot to enter a message.', ud_get_wp_property()->domain);
    __('You forgot to enter your  name.', ud_get_wp_property()->domain);
    __('Error with sending message. Please contact site administrator.', ud_get_wp_property()->domain);
    __('Thank you for your message.', ud_get_wp_property()->domain);
  }

  /**
   * Determine if all values of meta key have 'number type'
   * If yes, returns boolean true
   *
   * @param mixed $property_ids
   * @param string $meta_key
   *
   * @return boolean
   * @since 1.16.2
   * @author Maxim Peshkov
   */
  static public function meta_has_number_data_type($property_ids, $meta_key)
  {
    global $wpdb;

    /* There is no sense to continue if no ids */
    if (empty($property_ids)) {
      return false;
    }

    if (is_array($property_ids)) {
      $property_ids = implode(",", $property_ids);
    }

    $values = $wpdb->get_col("
      SELECT pm.meta_value
      FROM {$wpdb->prefix}posts AS p
      JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = p.ID
        WHERE p.ID IN (" . $property_ids . ")
          AND p.post_status = 'publish'
          AND pm.meta_key = '$meta_key'
    ");

    foreach ($values as $value) {
      $value = trim($value);

      //** Hack for child properties. Skip values with dashes */
      if (empty($value) || strstr($value, '&ndash;') || strstr($value, 'â€“')) {
        continue;
      }

      preg_match('#^[\d,\.\,]+$#', $value, $matches);
      if (empty($matches)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Returns users' ids of post type
   *
   * @global object $wpdb
   *
   * @param string $post_type
   *
   * @return array
   */
  static public function get_users_of_post_type($post_type)
  {
    global $wpdb;

    switch ($post_type) {

      case 'property':
        $results = $wpdb->get_results($wpdb->prepare("
          SELECT DISTINCT u.ID, u.display_name
          FROM {$wpdb->posts} AS p
          JOIN {$wpdb->users} AS u ON u.ID = p.post_author
          WHERE p.post_type = '%s'
            AND p.post_status != 'auto-draft'
          ", $post_type), ARRAY_N);
        break;

      default:
        break;
    }

    if (empty($results)) {
      return false;
    }

    $users = array();
    foreach ($results as $result) {
      $users[$result[0]] = $result[1];
    }

    $users = apply_filters('wpp_get_users_of_post_type', $users, $post_type);

    return $users;
  }

  /**
   * Settings page load handler
   *
   * @author korotkov@ud
   */
  static public function property_page_property_settings_load()
  {

    //** Default Help items */
    $contextual_help['Main'][] = '<h3>' . __('Default Properties Page', ud_get_wp_property()->domain) . '</h3>';
    $contextual_help['Main'][] = '<p>' . __('The default <b>property page</b> will be used to display property search results, as well as be the base for property URLs. ', ud_get_wp_property()->domain) . '</p>';
    $contextual_help['Main'][] = '<p>' . sprintf(__('By default, the <b>Default Properties Page</b> is set to <b>%s</b>, which is a dynamically created page used for displaying property search results. ', ud_get_wp_property()->domain), 'property') . '</p>';
    $contextual_help['Main'][] = '<p>' . __('We recommend you create an actual WordPress page to be used as the <b>Default Properties Page</b>. For example, you may create a root page called "Real Estate" - the URL of the default property page will be ' . get_bloginfo('url') . '<b>/real_estate/</b>, and you properties will have the URLs of ' . get_bloginfo('url') . '/real_estate/<b>property_name</b>/', ud_get_wp_property()->domain) . '</p>';

    $contextual_help['Display'][] = '<h3>' . __('Display', ud_get_wp_property()->domain) . '</h3>';
    $contextual_help['Display'][] = '<p>' . __('This tab allows you to do many things. Make custom picture sizes that will let you to make posting pictures easier. Change the way you view property photos with the use of Fancy Box, Choose  to use pagination on the bottom of property pages and whether or not to show child properties. Manage Google map attributes and map thumbnail sizes. Select here which attributes you want to show once a property is pin pointed on your map. Change your currency and placement of symbols.', ud_get_wp_property()->domain) . '</p>';

    $contextual_help['Help'][] = '<h3>' . __('Help', ud_get_wp_property()->domain) . '</h3>';
    $contextual_help['Help'][] = '<p>' . __('This tab will help you troubleshoot your plugin, do exports and check for updates for Premium Features', ud_get_wp_property()->domain) . '</p>';

    //** Hook this action is you want to add info */
    $contextual_help = apply_filters('property_page_property_settings_help', $contextual_help);

    $contextual_help['More Help'][] = '<h3>' . __('More Help', ud_get_wp_property()->domain) . '</h3>';
    $contextual_help['More Help'][] = '<p>' . __('Visit <a target="_blank" href="https://usabilitydynamics.com/products/wp-property/">WP-Property Help Page</a> on UsabilityDynamics.com for more help.', ud_get_wp_property()->domain) . '</>';

    do_action('wpp_contextual_help', array('contextual_help' => $contextual_help));

  }

  /**
   * Returns custom default coordinates
   *
   * @global array $wp_properties
   * @return array
   * @author peshkov@UD
   * @since 1.37.6
   */
  static public function get_default_coordinates()
  {
    global $wp_properties;

    $coords = $wp_properties['default_coords'];

    if (!empty($wp_properties['custom_coords']['latitude'])) {
      $coords['latitude'] = $wp_properties['custom_coords']['latitude'];
    }

    if (!empty($wp_properties['custom_coords']['longitude'])) {
      $coords['longitude'] = $wp_properties['custom_coords']['longitude'];
    }

    return $coords;
  }

  /**
   * Counts properties by post types
   *
   * @global object $wpdb
   *
   * @param array $post_status
   *
   * @return int
   */
  static public function get_properties_quantity($post_status = array('publish'))
  {
    global $wpdb;

    $results = $wpdb->get_col("
      SELECT ID
      FROM {$wpdb->posts}
      WHERE post_status IN ('" . implode("','", $post_status) . "')
        AND post_type = 'property'
    " );

    $results = apply_filters('wpp_get_properties_quantity', $results, $post_status);

    return count($results);

  }

  /**
   * Returns month periods of properties
   *
   * @global object $wpdb
   * @global object $wp_locale
   * @return array
   */
  static public function get_property_month_periods()
  {
    global $wpdb, $wp_locale;

    $months = $wpdb->get_results("
      SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
      FROM $wpdb->posts
      WHERE post_type = 'property'
        AND post_status != 'auto-draft'
      ORDER BY post_date DESC
    ");

    $months = apply_filters('wpp_get_property_month_periods', $months);

    $results = array();

    foreach ($months as $date) {

      $month = zeroise($date->month, 2);
      $year = $date->year;

      $results[$date->year . $month] = $wp_locale->get_month($month) . " $year";

    }

    return $results;

  }

  /**
   * Deletes directory recursively
   *
   * @param string $dirname
   *
   * @return bool
   * @author korotkov@ud
   */
  static public function delete_directory($dirname)
  {

    if (is_dir($dirname))
      $dir_handle = opendir($dirname);

    if (!$dir_handle)
      return false;

    while ($file = readdir($dir_handle)) {
      if ($file != "." && $file != "..") {

        if (!is_dir($dirname . "/" . $file))
          unlink($dirname . "/" . $file);
        else
          delete_directory($dirname . '/' . $file);

      }
    }

    closedir($dir_handle);

    return rmdir($dirname);

  }

  /**
   * Prevent Facebook integration if 'Facebook Tabs' did not installed.
   *
   * @author korotkov@ud
   */
  static public function check_facebook_tabs()
  {
    //** Check if FB Tabs is not installed to prevent an ability to use WPP as Facebook App or Page Tab */
    if (!class_exists('class_wpp_facebook_tabs')) {

      //** If request goes really from Facebook */
      if (!empty($_REQUEST['signed_request']) && strstr($_SERVER['HTTP_REFERER'], 'facebook.com')) {

        //** Show message */
        die(sprintf(__('You cannot use your site as Facebook Application. You should <a href="%s">get</a> WP-Property Premium Feature "Facebook Tabs" to manage your Facebook Tabs.', ud_get_wp_property()->domain), 'https://usabilitydynamics.com/products/wp-property/premium/'));
      }
    }
  }

  /**
   * Formats phone number for display
   *
   * @since 1.36.0
   * @source WPP_F
   *
   * @param string $phone_number
   *
   * @return string $phone_number
   */
  static public function format_phone_number($phone_number)
  {

    $phone_number = ereg_replace("[^0-9]", '', $phone_number);
    if (strlen($phone_number) != 10) return (False);
    $sArea = substr($phone_number, 0, 3);
    $sPrefix = substr($phone_number, 3, 3);
    $sNumber = substr($phone_number, 6, 4);
    $phone_number = "(" . $sArea . ") " . $sPrefix . "-" . $sNumber;

    return $phone_number;
  }

  /**
   * Shorthand function for drawing checkbox input fields.
   *
   * @since 1.36.0
   * @source WPP_F
   *
   * @param string $args List of arguments to overwrite the defaults.
   * @param bool $checked Option, default is false. Whether checkbox is checked or not.
   *
   * @return string Checkbox input field and hidden field with the opposive value
   */
  static public function checkbox($args = '', $checked = false)
  {
    $defaults = array(
      'name' => '',
      'id' => false,
      'class' => false,
      'group' => false,
      'special' => '',
      'value' => 'true',
      'label' => false,
      'maxlength' => false
    );

    extract(wp_parse_args($args, $defaults), EXTR_SKIP);
    $name = isset($name) ? $name : '';
    $id = isset($id) ? $id : false;
    $class = isset($class) ? $class : false;
    $group = isset($group) ? $group : false;
    $special = isset($special) ? $special : '';
    $value = isset($value) ? $value : 'true';
    $label = isset($label) ? $label : false;
    $maxlength = isset($maxlength) ? $maxlength : false;

    // Get rid of all brackets
    if (strpos("$name", '[') || strpos("$name", ']')) {

      $class_from_name = $name;

      //** Remove closing empty brackets to avoid them being displayed as __ in class name */
      $class_from_name = str_replace('][]', '', $class_from_name);

      $replace_variables = array('][', ']', '[');
      $class_from_name = 'wpp_' . str_replace($replace_variables, '_', $class_from_name);
    } else {
      $class_from_name = 'wpp_' . $name;
    }

    // Setup Group
    if ($group) {
      if (strpos($group, '|')) {
        $group_array = explode("|", $group);
        $count = 0;
        $group_string = '';
        foreach ($group_array as $group_member) {
          $count++;
          if ($count == 1) {
            $group_string .= $group_member;
          } else {
            $group_string .= "[{$group_member}]";
          }
        }
      } else {
        $group_string = $group;
      }
    }

    if (is_array($checked)) {

      if (in_array($value, $checked)) {
        $checked = true;
      } else {
        $checked = false;
      }
    } else {
      $checked = strtolower($checked);
      if ($checked == 'yes') $checked = 'true';
      if ($checked == 'true') $checked = 'true';
      if ($checked == 'no') $checked = false;
      if ($checked == 'false') $checked = false;
    }

    $id = ($id ? $id : $class_from_name);
    $insert_id = ($id ? " id='$id' " : " id='$class_from_name' ");
    $insert_name = (isset($group_string) ? " name='" . $group_string . "[$name]' " : " name='$name' ");
    $insert_checked = ($checked ? " checked='checked' " : " ");
    $insert_value = " value=\"$value\" ";
    $insert_class = " class='$class_from_name $class wpp_checkbox " . ($group ? 'wpp_' . $group . '_checkbox' : '') . "' ";
    $insert_maxlength = ($maxlength ? " maxlength='$maxlength' " : " ");

    $opposite_value = '';

    // Determine oppositve value
    switch ($value) {
      case 'yes':
        $opposite_value = 'no';
        break;

      case 'true':
        $opposite_value = 'false';
        break;

      case 'open':
        $opposite_value = 'closed';
        break;

    }

    $return = '';

    // Print label if one is set
    if ($label) $return .= "<label for='$id'>";

    // Print hidden checkbox if there is an opposite value */
    if ($opposite_value) {
      $return .= '<input type="hidden" value="' . $opposite_value . '" ' . $insert_name . ' />';
    }

    // Print checkbox
    $return .= "<input type='checkbox' $insert_name $insert_id $insert_class $insert_checked $insert_maxlength  $insert_value $special />";
    if ($label) $return .= " $label</label>";

    return $return;
  }

  /**
   * Shorthand function for drawing a textarea
   *
   * @since 1.36.0
   * @source WPP_F
   *
   * @param string $args List of arguments to overwrite the defaults.
   *
   * @return string Input field and hidden field with the opposive value
   */
  static public function textarea($args = '')
  {
    $defaults = array('name' => '', 'id' => false, 'checked' => false, 'class' => false, 'style' => false, 'group' => '', 'special' => '', 'value' => '', 'label' => false, 'maxlength' => false);
    extract(wp_parse_args($args, $defaults), EXTR_SKIP);
    $name = isset($name) ? $name : '';
    $id = isset($id) ? $id : false;
    $checked = isset($checked) ? $checked : false;
    $class = isset($class) ? $class : false;
    $style = isset($style) ? $style : false;
    $group = isset($group) ? $group : '';
    $special = isset($special) ? $special : '';
    $value = isset($value) ? $value : '';
    $label = isset($label) ? $label : false;
    $maxlength = isset($maxlength) ? $maxlength : false;
    $return = isset($return) ? $return : '';

    // Get rid of all brackets
    if (strpos("$name", '[') || strpos("$name", ']')) {
      $replace_variables = array('][', ']', '[');
      $class_from_name = $name;
      $class_from_name = 'wpp_' . str_replace($replace_variables, '_', $class_from_name);
    } else {
      $class_from_name = 'wpp_' . $name;
    }

    // Setup Group
    if ($group) {
      if (strpos($group, '|')) {
        $group_array = explode("|", $group);
        $count = 0;
        $group_string = '';
        foreach ($group_array as $group_member) {
          $count++;
          if ($count == 1) {
            $group_string .= "$group_member";
          } else {
            $group_string .= "[$group_member]";
          }
        }
      } else {
        $group_string = "$group";
      }
    }

    $id = ($id ? $id : $class_from_name);

    $insert_id = ($id ? " id='$id' " : " id='$class_from_name' ");
    $insert_name = ($group_string ? " name='" . $group_string . "[$name]' " : " name=' wpp_$name' ");
    $insert_checked = ($checked ? " checked='true' " : " ");
    $insert_style = ($style ? " style='$style' " : " ");
    $insert_value = ($value ? $value : "");
    $insert_class = " class='$class_from_name input_textarea $class' ";
    $insert_maxlength = ($maxlength ? " maxlength='$maxlength' " : " ");

    // Print label if one is set

    // Print checkbox
    $return .= "<textarea $insert_name $insert_id $insert_class $insert_checked $insert_maxlength $special $insert_style>$insert_value</textarea>";

    return $return;
  }

  /**
   * Shorthand function for drawing regular or hidden input fields.
   *
   * @since 1.36.0
   * @source WPP_F
   *
   * @param string $args List of arguments to overwrite the defaults.
   * @param bool|string $value Value may be passed in arg array or seperately
   *
   * @return string Input field and hidden field with the opposive value
   */
  static public function input($args = '', $value = false)
  {
    $defaults = array('name' => '', 'group' => '', 'special' => '', 'value' => $value, 'title' => '', 'type' => 'text', 'class' => false, 'hidden' => false, 'style' => false, 'readonly' => false, 'label' => false);
    extract(wp_parse_args($args, $defaults), EXTR_SKIP);
    $name = isset($name) ? $name : '';
    $label = isset($label) ? $label : false;
    $style = isset($style) ? $style : false;
    $type = isset($type) ? $type : 'text';
    $class = isset($class) ? $class : false;
    $hidden = isset($hidden) ? $hidden : false;
    $group = isset($group) ? $group : '';
    $readonly = isset($readonly) ? $readonly : false;
    $special = isset($special) ? $special : '';
    $title = isset($title) ? $title : '';

    // Add prefix
    if ($class) {
      $class = "wpp_$class";
    }

    // if [ character is present, we do not use the name in class and id field
    if (!strpos("$name", '[')) {
      $id = $name;
      $class_from_name = $name;
    }

    $return = '';

    if ($label) $return .= "<label for='$name'>";
    $return .= "<input " . ($type ? "type=\"$type\" " : '') . " " . ($style ? "style=\"$style\" " : '') . (isset($id) ? "id=\"$id\" " : '') . " class=\"" . ($type ? "" : "input_field ") . (isset($class_from_name) ? $class_from_name : '') . " $class " . ($hidden ? " hidden " : '') . "" . ($group ? "group_$group" : '') . " \"    name=\"" . ($group ? $group . "[" . $name . "]" : $name) . "\"   value=\"" . stripslashes($value) . "\"   title=\"$title\" $special " . ($type == 'forget' ? " autocomplete='off'" : '') . " " . ($readonly ? " readonly=\"readonly\" " : "") . " />";
    if ($label) $return .= " $label </label>";

    return $return;
  }

  /**
   * Recursive conversion of an object into an array
   *
   * @since 1.36.0
   * @source WPP_F
   *
   */
  static public function objectToArray($object)
  {

    if (!is_object($object) && !is_array($object)) {
      return $object;
    }

    if (is_object($object)) {
      $object = get_object_vars($object);
    }

    return array_map(array('WPP_F', 'objectToArray'), $object);
  }

  /**
   * Get a URL of a page.
   *
   * @since 1.36.0
   * @source WPP_F
   *
   */
  static public function base_url($page = '', $get = '')
  {
    global $wpdb, $wp_properties;

    $permalink = '';
    $permalink_structure = get_option('permalink_structure');

    //** Using Permalinks */
    if ('' != $permalink_structure) {
      $page_id = false;
      if (!is_numeric($page)) {
        $page_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} where post_name = %s", $page));
      } else {
        $page_id = $page;
      }
      //** If the page doesn't exist, return default url ( base_slug ) */
      if (empty($page_id)) {
        $home_url = explode('?', home_url());
        $permalink = $home_url[0] . "/" . (!is_numeric($page) ? $page : $wp_properties['configuration']['base_slug']) . (!empty($home_url[1]) ? '?' . $home_url[1] : '/');
      } else {
        $permalink = get_permalink($page_id);
      }
    } //** Not using permalinks */
    else {
      //** If a slug is passed, convert it into ID */
      if (!is_numeric($page)) {
        $page_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} where post_name = %s AND post_status = 'publish' AND post_type = 'page'", $page));
        //* In case no actual page_id was found, we continue using non-numeric $page, it may be 'property' */
        if (!$page_id) {
          $query = '?p=' . $page;
        } else {
          $query = '?page_id=' . $page_id;
        }
      } else {
        $page_id = $page;
        $query = '?page_id=' . $page_id;
      }
      $permalink = home_url($query);
    }

    //** Now set GET params */
    if (!empty($get)) {
      $get = wp_parse_args($get);
      $get = http_build_query($get, '', '&');
      $permalink .= (strpos($permalink, '?') === false) ? '?' : '&';
      $permalink .= $get;
    }

    return $permalink;

  }

  /**
   * Fixes images permalinks in [gallery] shortcode
   *
   * @param string $output
   * @param int $id
   * @param type $size
   * @param type $permalink
   * @param type $icon
   * @param type $text
   *
   * @return string
   * @author peshkov@UD
   * @since 1.37.6
   */
  static public function wp_get_attachment_link($output, $id, $size, $permalink, $icon, $text)
  {

    if (function_exists('debug_backtrace') && !is_admin()) {
      $backtrace = debug_backtrace();
      foreach ((array)$backtrace as $f) {
        if ($f['function'] === 'gallery_shortcode') {
          $link = wp_get_attachment_url($id);
          $output = preg_replace('/href=[\",\'](.*?)[\",\']/', 'href=\'' . $link . '\'', $output);
          break;
        }
      }
    }

    return $output;
  }

  /**
   * Returns clear post status
   *
   * @author peshkov@UD
   * @version 0.1
   */
  static public function clear_post_status($post_status = '', $ucfirst = true)
  {
    switch ($post_status) {
      case 'publish':
        $post_status = __('published', ud_get_wp_property()->domain);
        break;
      case 'pending':
        $post_status = __('pending', ud_get_wp_property()->domain);
        break;
      case 'trash':
        $post_status = __('trashed', ud_get_wp_property()->domain);
        break;
      case 'inherit':
        $post_status = __('inherited', ud_get_wp_property()->domain);
        break;
      case 'auto-draft':
        $post_status = __('drafted', ud_get_wp_property()->domain);
        break;
    }
    return ($ucfirst ? ucfirst($post_status) : $post_status);
  }

  /**
   * Sanitizes data.
   * Prevents shortcodes and XSS adding!
   *
   * @todo: remove the method since it's added in utility library.
   * @author peshkov@UD
   */
  static public function sanitize_request($data)
  {
    if (is_array($data)) {
      foreach ($data as $k => $v) {
        $data[$k] = self::sanitize_request($v);
      }
    } else {
      $data = strip_shortcodes($data);
      $data = filter_var($data, FILTER_SANITIZE_STRING);
    }
    return $data;
  }

  /**
   * @todo remove in future releases
   * @deprecated
   */
  static public function feature_check()
  {
    return false;
  }


  /**
   * Apply default value to all new and empty and existing properties.
   *
   * @param none
   *
   * @return nothing.
   * @author Md. Alimuzzaman Alim
   * @since 2.1.5
   */
  static function apply_default_value()
  {
    global $wpdb;
    $replaced_row = 0;
    $added_row = 0;
    $prefix = $wpdb->prefix;
    $data = $_POST['data'];
    $attribute = $data['attribute'];
    $value = $data['value'];
    $response = array();
    $property_label = WPP_F::property_label();
    $property_label_plural = WPP_F::property_label('plural');

    $chk_meta_key = "SELECT DISTINCT p.ID FROM {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id  WHERE p.post_type = 'property' AND pm.meta_key = '$attribute'";
    if (isset($data['confirmed']) && $data['confirmed']) {
      if ($data['confirmed'] == 'all') {
        $sql = "UPDATE {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id SET pm.meta_value = '$value' WHERE p.post_type = 'property' AND pm.meta_key = '$attribute'";
        $replaced_row = $wpdb->query($sql);
      }

      if ($data['confirmed'] == 'all' || $data['confirmed'] == 'empty-or-not-exist') {
        $_chk_meta_key = "SELECT DISTINCT p.ID FROM {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id  WHERE p.post_type = 'property' AND pm.meta_key = '$attribute' and pm.meta_value != ''";
        $sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} AS p WHERE p.post_type = 'property' AND p.ID NOT IN($_chk_meta_key)";
        $results = $wpdb->get_results($sql);
        foreach ($results as $post) {
          $wpdb->insert(
            $wpdb->postmeta,
            array(
              'post_id' => $post->ID,
              'meta_key' => $attribute,
              'meta_value' => $value,
            )
          );
        }
        $added_row = count($results);
      }
      //"Attributes replaced in $replaced_row Property and added in $added_row Property."

      // Whether it's plural or not.
      $replaced_property_label = ($replaced_row > 1) ? $property_label_plural : $property_label;
      $added_property_label = ($added_row > 1) ? $property_label_plural : $property_label;
      $response = array(
        'status' => "replaced",
        'message' => sprintf(__("Attributes replaced in %d %s and added in %d %s.", ud_get_wp_property()->domain), number_format_i18n($replaced_row), $replaced_property_label, number_format_i18n($added_row), $added_property_label),
      );
    } else {
      $chk_meta_results = $wpdb->get_results($chk_meta_key);
      if (is_array($chk_meta_results) && $count = count($chk_meta_results)) {
        $key_exist_property_label = ($count > 1) ? $property_label_plural : $property_label;
        $_those = __("those", ud_get_wp_property()->domain);
        $_values = __("values", ud_get_wp_property()->domain);
        $str_that = _n("that", $_those, $count, ud_get_wp_property()->domain);
        $str_value = _n("value", $_values, $count, ud_get_wp_property()->domain);
        $response = array(
          'status' => "confirm",
          'message' => sprintf(__("Attribute value already exist (In %d %s). Do you want to replace %s %s?", ud_get_wp_property()->domain), number_format_i18n($count), $key_exist_property_label, $str_that, $str_value),
        );
      } else {
        $sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} AS p WHERE p.post_type = 'property' AND p.ID";
        $results = $wpdb->get_results($sql);
        $count = count($results);
        $property_label = ($count > 1) ? $property_label_plural : $property_label;
        foreach ($results as $post) {
          $wpdb->insert(
            $wpdb->postmeta,
            array(
              'post_id' => $post->ID,
              'meta_key' => $attribute,
              'meta_value' => $value,
            )
          );
        }

        $response = array(
          'status' => 'success',
          'message' => sprintf(__("Applied to %d %s.", ud_get_wp_property()->domain), number_format_i18n($count), $property_label),
        );
      }
    }

    echo json_encode($response);
  }

  /**
   * This function is only for generate_is_remote_meta() function.
   * Used to search more than one array (ex. array of arrays).
   *
   * @param array $array : The array of arrays.
   * @param string $key : The key to search in $array.
   * @param string $value : The value to search for $key.
   *
   * @return array of element_id found.
   */
  private static function _found_in_array($array, $key, $value = '1')
  {
    $return = array();
    if (is_array($array)) {
      foreach ($array as $k => $v) {
        if ($key == 'wpml_media_processed') {
          if ($v['_is_remote'] == '')
            $return[] = $v['element_id'];
        } else if ($v[$key] == $value)
          $return[] = $v['element_id'];
      }
    }

    if (empty($return))
      return false;
    if (count($return) == 1)
      return $return[0];

    return $return;
  }


  /**
   * Used in generate_is_remote_meta() function.
   * Used to search more than one array (ex. array of arrays).
   *
   * @param none .
   *
   * @return bool whether succeed or not.
   */
  static function generate_is_remote_meta()
  {
    global $wpdb;
    $update_is_remote = array();
    $sql = "
    SELECT tr.element_id, tr.trid, tr.source_language_code, pm1.meta_value AS _is_remote, pm2.meta_value AS wpml_media_processed  
    FROM `{$wpdb->prefix}icl_translations` AS tr 
    LEFT JOIN {$wpdb->postmeta} AS pm1 ON tr.element_id = pm1.post_id AND pm1.meta_key = '_is_remote'
    LEFT JOIN {$wpdb->postmeta} AS pm2 ON tr.element_id = pm2.post_id AND pm2.meta_key = 'wpml_media_processed'
    WHERE tr.element_type = 'post_attachment'";

    $sql = $wpdb->prepare($sql);
    $results = $wpdb->get_results($sql);
    $translations = array();
    if (is_array($results)) {
      foreach ($results as $key => $row) {
        $translations[$row->trid][] = (array)$row;
      }

      foreach ($translations as $trid => $trans_group) {
        if (count($trans_group) < 2) continue;
        if (WPP_F::_found_in_array($trans_group, '_is_remote') == WPP_F::_found_in_array($trans_group, 'source_language_code', '')) {
          $destination_pid = WPP_F::_found_in_array($trans_group, 'wpml_media_processed', '1');
          if ($destination_pid != false) {
            foreach ((array)$destination_pid as $pid) {
              $update_is_remote[] = $pid;
            }
          }
        }
      }

      if (count($update_is_remote) == 0) {
        _e("Already up to date.");
        return;
      }

      $delete_sql = "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_is_remote' AND post_id IN(" . implode(', ', $update_is_remote) . ");";
      $delete_sql = $wpdb->prepare($delete_sql);
      $wpdb->query($delete_sql);

      $insert_array = array();
      foreach ($update_is_remote as $pid) {
        $insert_array[] = "('$pid', '_is_remote', '1')";
      }

      $insert_sql = "INSERT INTO {$wpdb->postmeta} (`post_id`, `meta_key`, `meta_value`) VALUES ";
      $insert_sql .= implode(', ', $insert_array);
      $insert_sql = $wpdb->prepare($insert_sql);
      $row_updated = $wpdb->query($insert_sql);
      printf(__("%s image meta updated.", ud_get_wp_property()->domain), $row_updated);

    }
  }

  /**
   * Deactivate wp-property-terms.
   * Because it's now bundled with wp-property.
   *
   */
  static function deactive_wp_property_terms()
  {
    deactivate_plugins('wp-property-terms/wp-property-terms.php', true);
  }
  
  /**
   * Show notice for deprecated action..
   * Then do the action.
   */
  static function do_action_deprecated($tag, $args, $version, $replacement = false, $message = null ){
    if(function_exists('do_action_deprecated')){
      do_action_deprecated($tag, $args, $version, $replacement, $message);
    }
    else{
      if ( ! has_action( $tag ) ) {
          return;
      }
   
      self::_deprecated_hook( $tag, $version, $replacement, $message );

      call_user_func_array( 'do_action', array_merge(array($tag), $args) );
    }
  }

  /**
   * Show notice for deprecated action..
   * Then apply the filter.
   */
  static function apply_filters_deprecated($tag, $args, $version, $replacement = false, $message = null){
    if(function_exists('apply_filters_deprecated')){
      return apply_filters_deprecated($tag, $args, $version, $replacement, $message);
    }
    else{
      if ( ! has_filter( $tag ) ) {
        return $args[0];
      }

      self::_deprecated_hook( $tag, $version, $replacement, $message );
 
      return call_user_func_array( 'apply_filters', array_merge(array($tag), $args) );
    }
  }


  /**
   * Fires when a deprecated hook is called.
   *
   * @since 4.6.0
   *
   * @param string $hook        The hook that was called.
   * @param string $replacement The hook that should be used as a replacement.
   * @param string $version     The version of WordPress that deprecated the argument used.
   * @param string $message     A message regarding the change.
   */
  static function _deprecated_hook( $hook, $version, $replacement = null, $message = null ) {
    if ( WP_DEBUG ) {
      $message = empty( $message ) ? '' : ' ' . $message;
      if ( ! is_null( $replacement ) ) {
        /* translators: 1: WordPress hook name, 2: version number, 3: alternative hook name */
        trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ), $hook, $version, $replacement ) . $message );
      } else {
        /* translators: 1: WordPress hook name, 2: version number */
        trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ), $hook, $version ) . $message );
      }
    }
  }

}

/**
 * Implementing this for old versions of PHP
 *
 * @since 1.15.9
 *
 */
if (!function_exists('array_fill_keys')) {

  function array_fill_keys($target, $value = '')
  {

    if (is_array($target)) {

      foreach ($target as $key => $val) {

        $filledArray[$val] = is_array($value) ? $value[$key] : $value;

      }

    }

    return $filledArray;

  }

}

/**
 * Delete a file or recursively delete a directory
 *
 * @param string $str Path to file or directory
 * @param boolean $flag If false, doesn't remove root directory
 *
 * @version 0.1
 * @since 1.32.2
 * @author Maxim Peshkov
 */
if (!function_exists('wpp_recursive_unlink')) {
  function wpp_recursive_unlink($str, $flag = false)
  {
    if (is_file($str)) {
      return @unlink($str);
    } elseif (is_dir($str)) {
      $scan = glob(rtrim($str, '/') . '/*');
      foreach ($scan as $index => $path) {
        wpp_recursive_unlink($path, true);
      }
      if ($flag) {
        return @rmdir($str);
      } else {
        return true;
      }
    }
  }
}

/**
 * Add 'property' to the list of RSSable post_types.
 *
 * @param string $request
 *
 * @return string
 * @author korotkov@ud
 * @since 1.36.2
 */
if (!function_exists('property_feed')) {
  function property_feed($qv)
  {

    if (isset($qv['feed']) && !isset($qv['post_type'])) {
      $qv['post_type'] = get_post_types($args = array(
        'public' => true,
        '_builtin' => false
      ));
      array_push($qv['post_type'], 'post');
    }

    return $qv;

  }
}

// prevent automatic add slashes while using parse_str. By Md. Alimuzzaman Alim
function stripslashes_array($array)
{
  return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
}

add_filter('wpp_settings_save', 'wpp_settings_save_stripslashes');
function wpp_settings_save_stripslashes($data)
{
  return stripslashes_array($data);
}

/**
 *  Get changelog from changes.md file for Splash page
 *
 * @author: Den@ud.com
 */
function wpp_get_update_changes()
{
  $changes_file = file_get_contents(WPP_Path . 'changes.md');
  $current_version = ud_get_wp_property('version');
  $current_version = str_replace('.', '\.', $current_version);
  preg_match('/### ' . $current_version . '.+?\)([\s\S]+?)###/', $changes_file, $current_changes);
  $current_changes = str_replace('* ', '', $current_changes[1]);
  $changes = array_filter(explode("\n", $current_changes));
  return $changes;
}