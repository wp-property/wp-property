<?php
/**
 * Bootstrap
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPP {

  use UsabilityDynamics\WP\Bootstrap_Plugin;
  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Terms_Bootstrap' ) ) {

    final class Terms_Bootstrap extends Bootstrap_Plugin {

      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type \UsabilityDynamics\WPP\Terms_Bootstrap object
       */
      protected static $instance = null;

      /**
       * Instantaite class.
       */
      public function init() {

        if( !class_exists( '\UsabilityDynamics\Settings' ) ) {
          $this->errors->add( __( 'Class \UsabilityDynamics\Settings is undefined.', $this->domain ) );
          return;
        }

        /**
         * Add Terms UI on Settings page.
         */
        if( current_user_can( 'manage_wpp_categories' ) ) {

          /** Add Settings on Developer Tab */
          add_filter( 'wpp::settings_developer::tabs', function( $tabs ){
            $tabs['terms'] = array(
              'label' => __( 'Taxonomies', ud_get_wpp_terms()->domain ),
              'template' => ud_get_wpp_terms()->path( 'static/views/admin/settings-developer-terms.php', 'dir' ),
              'order' => 25
            );
            return $tabs;
          } );

          add_filter( 'wpp::settings::developer::types', function($attributes = array()){
            $inheritance = ud_get_wpp_terms()->get( 'config.inherited', array() );
            $hidden = ud_get_wpp_terms()->get( 'config.hidden', array() );

            $attributes['terms_hidden'] = $hidden;
            $attributes['terms_inheritance'] = $inheritance;
            return $attributes;
          });

          /** Add Hidden Taxonomies on Types Tab */
          add_action( 'wpp::settings::developer::types::hidden_attributes', function( $property_slug ){
            include ud_get_wpp_terms()->path( 'static/views/admin/settings-hidden-terms.php', 'dir' );
          } );

          /** Add Inherited Taxonomies on Types Tab */
          add_action( 'wpp::settings::developer::types::inherited_attributes', function( $property_slug ){
            include ud_get_wpp_terms()->path( 'static/views/admin/settings-inherited-terms.php', 'dir' );
          } );

          // Priority must be greater than 1 for save_settings to make tax post binding work.
          add_action( 'wpp::save_settings', array( $this, 'save_settings' ) );

          // Add terms settings to backup
          add_filter( 'wpp::backup::data', array( $this, 'backup_settings' ), 50, 2 );

        }

        /** Load admin scripts */
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Watch for updates to wpp_settings field.
        add_action( 'update_option_wpp_settings', array( $this, 'update_option_wpp_settings' ), 20, 3 );

        /** Define our custom taxonomies. */
        add_filter( 'wpp_taxonomies', array( $this, 'define_taxonomies' ), 20 );

        /** Prepare taxonomy's arguments before registering taxonomy. */
        add_filter( 'wpp::register_taxonomy', array( $this, 'prepare_taxonomy' ), 99, 2 );
        add_filter( 'wpp::register_taxonomy', array( $this, 'register_taxonomy' ), 99, 2 );

        /** Add Meta Box to manage taxonomies on Edit Property page. */
        add_filter( 'wpp::meta_boxes', array( $this, 'add_meta_box' ), 99 );

        /** Handle inherited taxonomies on property saving. */
        add_action( 'save_property', array( $this, 'save_property' ), 11 );

        /** Search hooks ( get_properties, property_overview shortcode, etc ) */
        add_filter( 'get_queryable_keys', array( $this, 'get_queryable_keys' ) );
        add_filter( 'wpp::get_properties::custom_case', array( $this, 'custom_search_case' ), 99, 2 );
        add_filter( 'wpp::get_properties::custom_key', array( $this, 'custom_search_query' ), 99, 3 );

        /** Add Search fields on 'All Properties' page ( admin panel ) */
        add_filter( 'wpp::overview::filter::fields', array( $this, 'get_filter_fields' ) );

        /** Property Search shortcode hooks */
        add_filter( 'wpp::search_attribute::label', array( $this, 'get_search_attribute_label' ), 10, 2 );

        /** on Clone Property action */
        add_action( 'wpp::clone_property::action', array( $this, 'clone_property_action' ), 99, 2 );

        add_action( 'admin_menu' , array( $this, 'maybe_remove_native_meta_boxes' ), 11 );

        add_action( 'wp_ajax_term_autocomplete', array($this, 'ajax_term_autocomplete'));

        // Extend property object early.
        add_filter( 'wpp::property::early_extend', array($this, 'extend_property_object'), 5, 2 );
        add_filter( 'wpp_get_property', array($this, 'finalize_property_object'), 100, 2 );

        // Inject single-value terms into attributes
        add_filter( 'wpp::draw_stats::attributes', array($this, 'draw_attributes'), 10, 3 );

      }

      /**
       * Manage Admin Scripts and Styles.
       *
       */
      public function enqueue_scripts() {
        global $current_screen;

        switch( $current_screen->id ) {

          /** Edit Property page */
          case 'property':
            wp_enqueue_style( 'wpp-terms-admin-property', $this->path( '/static/styles/wpp.terms.property.css', 'url' ) );
            break;

          //** Settings Page */
          case 'property_page_property_settings':
            wp_enqueue_script( 'settings-developer-terms', $this->path( '/static/scripts/admin/settings-developer-terms.js', 'url' ), array( 'wp-property-admin-settings', 'wp-property-global' ) );

        }

      }

      /**
       * Fix label for Taxonomy in Property Search form
       *
       * @see draw_property_search_form()
       * @action wpp::search_attribute::label
       * @param string $label
       * @param string $taxonomy
       * @return string $label
       */
      public function get_search_attribute_label( $label, $taxonomy ) {
        $taxonomies = get_object_taxonomies( 'property' );
        if( in_array( $taxonomy, $taxonomies ) ) {
          $taxonomy = get_taxonomy( $taxonomy );
          $label = $taxonomy->labels->name;
          if( is_admin() ) {
            $label .= ' (' . __( 'taxonomy' ) . ')';
          }
        }
        return $label;
      }

      /**
       * Remove Taxonomy Meta Boxes if they added
       * for hidden and inherited taxonomies to prevent issues.
       *
       */
      public function maybe_remove_native_meta_boxes() {
        // Removing nativ metabox if  Show in Admin Menu and add native Meta Box isn't set.
        $taxonomies = $this->get( 'config.taxonomies', array() );
        foreach ($taxonomies as $taxonomy => $args) {
          if($args['hierarchical'])
            $_id = $taxonomy . "div";
          else
            $_id = "tagsdiv-$taxonomy";

          if(!isset($args['add_native_mtbox']) || $args['add_native_mtbox'] == false)
            remove_meta_box( $_id, 'property', 'side' );
        }

        if( isset( $_REQUEST['post'] ) && is_numeric( $_REQUEST['post'] ) ) {
          $type = get_post_meta( $_REQUEST['post'], 'property_type', true );
        }

        if( !isset( $type ) || !$type ) {
          return;
        }

        /** Remove meta boxes for all inherited taxonomies */
        $inherited = $this->get( 'config.inherited.' . $type, array() );
        if( !empty( $inherited ) && is_array( $inherited ) ) {
          foreach( $inherited as $taxonomy ) {
            $args = $taxonomies[$taxonomy];
            if($args['hierarchical'])
              $_id = $taxonomy . "div";
            else
              $_id = "tagsdiv-" . $taxonomy;
            remove_meta_box( $_id, 'property', 'side' );
          }
        }

        /** Remove meta boxes for all hidden taxonomies */
        $hidden = $this->get( 'config.hidden.' . $type, array() );
        if( !empty( $hidden ) && is_array( $hidden ) ) {
          foreach( $hidden as $taxonomy ) {
            $args = $taxonomies[$taxonomy];
            if($args['hierarchical'])
              $_id = $taxonomy . "div";
            else
              $_id = "tagsdiv-$taxonomy";
            remove_meta_box( $_id, 'property', 'side' );
          }
        }

      }

      /**
       * Makes sure WPP-Terms doesn't override read-only taxonomies.
       *
       * @todo Update to allow labels to be overwritten for system taxonomies. - potanin@UD
       *
       * @param $taxonomies - Passed down via wpp_taxonomies filter, not yet registered with WP.
       */
      public function prepare_taxonomies( $taxonomies ) {

        /** Be sure that we have any taxonomy to register. If not, we set default taxonomies of WP-Property. */
        if( !$this->get( 'config.taxonomies' ) ) {
          $this->set( 'config.taxonomies', $taxonomies );
          $types = array();
          foreach ($taxonomies as $taxonomy => $data) {
            $taxonomies[$taxonomy] = $data = $this->prepare_taxonomy($data, $taxonomy);
            $types[$taxonomy] = isset($data['unique']) && $data['unique'] ? 'unique' : 'multiple';
          }
          $this->set( 'config.types', $types );
        }

        $_taxonomies = $this->get( 'config.taxonomies', array() );

        foreach( $taxonomies as $_taxonomy => $_taxonomy_data ) {

          // Make sure we dont override any [system] taxonomies.
          if( isset( $_taxonomy_data[ 'system' ] ) && $_taxonomy_data[ 'system' ]) {

            if( isset( $_taxonomies[ $_taxonomy ] ) ) {
              $_original_taxonomy = $_taxonomies[ $_taxonomy ];
            }

            $_taxonomies[ $_taxonomy ] = $this->prepare_taxonomy($_taxonomy_data, $_taxonomy);

            // Preserve [wpp_term_meta_fields] fields.
            if( isset( $_original_taxonomy ) && !isset( $_taxonomies[ $_taxonomy ]['wpp_term_meta_fields'] ) && isset( $_original_taxonomy['wpp_term_meta_fields'] ) ) {
              $_taxonomies[ $_taxonomy ]['wpp_term_meta_fields'] = $_original_taxonomy['wpp_term_meta_fields'];
            }

            // Allow show_in_menu setting to be set
            if( isset( $_original_taxonomy ) && !isset( $_taxonomies[ $_taxonomy ]['show_in_menu'] ) && isset( $_original_taxonomy['show_in_menu'] ) ) {
              $_taxonomies[ $_taxonomy ]['show_in_menu'] = $_original_taxonomy['show_in_menu'];
            }

            // Allow rich_taxonomy to be enabled.
            if( isset( $_original_taxonomy ) && !isset( $_taxonomies[ $_taxonomy ]['rich_taxonomy'] ) && isset( $_original_taxonomy['rich_taxonomy'] ) ) {
              $_taxonomies[ $_taxonomy ]['rich_taxonomy'] = $_original_taxonomy['rich_taxonomy'];
            }

            // Allow rich_taxonomy to be enabled.
            if( isset( $_original_taxonomy ) && !isset( $_taxonomies[ $_taxonomy ]['query_var'] ) && isset( $_original_taxonomy['query_var'] ) ) {
              $_taxonomies[ $_taxonomy ]['query_var'] = $_original_taxonomy['query_var'];
            }

            if( isset( $_original_taxonomy ) && !isset( $_taxonomies[ $_taxonomy ]['rewrite'] ) && isset( $_original_taxonomy['rewrite'] ) ) {
              $_taxonomies[ $_taxonomy ]['rewrite'] = $_original_taxonomy['rewrite'];
            }

            if( isset( $_original_taxonomy ) && !isset( $_taxonomies[ $_taxonomy ]['public'] ) && isset( $_original_taxonomy['public'] ) ) {
              $_taxonomies[ $_taxonomy ]['public'] = $_original_taxonomy['public'];
            }

          } else if ( !isset( $_taxonomies[ $_taxonomy ] ) ) {
            $_taxonomies[ $_taxonomy ] = $_taxonomy_data;
          }

        }

        $this->set( 'config.taxonomies', $_taxonomies );

      }

      /**
       * Maybe extend taxonomy functionality
       *
       */
      public function maybe_extend_taxonomies() {

        $taxonomies = $this->get( 'config.taxonomies', array() );

        $exclude = array();

        foreach( $taxonomies as $key => $data ) {

          if( !isset( $data[ 'rich_taxonomy' ] ) || !$data[ 'rich_taxonomy' ] ) {
            array_push( $exclude, $key );
          }

        }

        new \UsabilityDynamics\CFTPB\Loader( array(
          'post_types' => array( 'property' ),
          'exclude' => $exclude,
        ) );

      }

      /**
       * Handle inherited taxonomies on property saving.
       *
       * @see \UsabilityDynamics\WPP\WPP_Core::save_property
       * @action save_property
       * @param in $post_id
       */
      public function save_property( $post_id ) {

        //* Check if property has children */
        $children = get_children( "post_parent=$post_id&post_type=property" );
        //* Write any data to children properties that are supposed to inherit things */
        if( count( $children ) > 0 ) {
          //* Go through all children */
          foreach( $children as $id => $data ) {
            //* Determine child property_type */
            $type = get_post_meta( $id, 'property_type', true );
            //* Check if child's property type has inheritence rules, and if meta_key exists in inheritance array */
            $inherited = $this->get( 'config.inherited.' . $type, array() );
            if( !empty( $inherited ) && is_array( $inherited ) ) {
              foreach( $inherited as $taxonomy ) {
                $terms = wp_get_object_terms( $post_id, $taxonomy, array("fields" => "ids") );
                wp_set_object_terms( $id, $terms, $taxonomy );
              }
            }
          }
        }

      }

      /**
       * Apply filter fields for available taxonomies.
       *
       * @see \UsabilityDynamics\WPP\Admin_Overview::get_filter_fields()
       * @action wpp::overview::filter::fields
       * @param array $fields
       * @return array
       */
      public function get_filter_fields( $fields ) {
        if( !is_array( $fields ) ) {
          $fields = array();
        }

        /* Get all existing fields names */
        $defined = array();
        foreach( $fields as $field ) {
          array_push( $defined, $field['id'] );
        }

        $taxonomies = $this->get( 'config.taxonomies', array() );

        if( !empty($taxonomies) && is_array($taxonomies) ) {
          foreach( $taxonomies as $k => $v ) {

            // ignore terms that are not explicitly set as searchable
            if( !isset( $v['admin_searchable'] ) || !$v['admin_searchable'] ) {
              continue;
            }

            /* Ignore taxonomy if field with the same name already exists */
            if( in_array( $k, $defined ) ) {
              continue;
            }
            array_push( $fields, array(
              'id' => $k,
              'name' => $v['label'],
              'type' => 'taxonomy',
              'multiple' => true,
              'js_options' => array(
                'allowClear' => false,
              ),
              'options' => array(
                'taxonomy' => $k,
                'type' => 'select_advanced',
                'args' => array(),
              ),
              'map' => array(
                'class' => 'taxonomy',
              ),
            ) );
          }
        }

        return $fields;
      }

      /**
       * On property cloning we also clone terms.
       *
       * @see UsabilityDynamics\WPP\Ajax::action_wpp_clone_property()
       * @action wpp::clone_property::action
       * @param array $old_property
       * @param int $new_post_id
       */
      public function clone_property_action( $old_property, $new_post_id ) {
        $taxonomies = $this->get( 'config.taxonomies', array() );

        if( !empty($taxonomies) && is_array($taxonomies) ) {
          foreach( $taxonomies as $k => $v ) {
            $terms = wp_get_object_terms( $old_property['ID'], $k, array("fields" => "ids") );
            if( !empty( $terms ) ) {
              wp_set_object_terms( $new_post_id, $terms, $k );
            }
          }
        }
      }

      /**
       * Determine if search key belongs taxonomy.
       *
       * @action wpp::get_properties::custom_case
       * @see WPP_F::get_properties()
       * @param bool $bool
       * @param string $key
       * @return bool
       */
      public function custom_search_case( $bool, $key ) {
        $taxonomies = $this->get( 'config.taxonomies', array() );
        if( !empty( $taxonomies ) && is_array( $taxonomies ) && in_array( $key, array_keys($taxonomies) ) ) {
          return true;
        }
        return $bool;
      }

      /**
       * Do search for taxonomies.
       *
       * @param array $matching_ids
       * @param string $key
       * @param string $criteria
       * @return array
       */
      public function custom_search_query( $matching_ids, $key, $criteria ) {
        // Be sure that queried key belongs to taxonomy
        $taxonomies = $this->get( 'config.taxonomies', array() );
        if( empty( $taxonomies ) || !is_array( $taxonomies ) || !in_array( $key, array_filter(array_keys($taxonomies)) ) ) {
          return $matching_ids;
        }

        if( !is_array( $criteria ) ) {
          $criteria = explode( ',', trim( $criteria ) );
        }

        $is_numeric = true;
        foreach($criteria as $i => $v) {
          $criteria[$i] = trim($v);
          if( !is_numeric($criteria[$i]) ) {
            $is_numeric = false;
          }
        }

        if($is_numeric) {
          $tax_query = array(
              array(
                  'taxonomy' => $key,
                  'field'    => 'term_id',
                  'terms'    => $criteria,
              )
          );
        } else {
          $tax_query = array(
              'relation' => 'OR',
              array(
                  'taxonomy' => $key,
                  'field'    => 'name',
                  'terms'    => $criteria,
              ),
              array(
                  'taxonomy' => $key,
                  'field'    => 'slug',
                  'terms'    => $criteria,
              ),
          );
        }

        $tax_query = apply_filters( 'wpp_terms_custom_search_tax_query', $tax_query, $key, $criteria, $matching_ids );

        $args = array(
          'post_type' => 'property',
          'post_status' => 'publish',
          'posts_per_page' => '-1',
          'tax_query' => $tax_query,
        );

        if( !empty( $matching_ids ) && is_array( $matching_ids ) ) {
          $args[ 'post__in' ] = $matching_ids;
        }

        $wp_query = new \WP_Query( $args );
        $matching_ids = array();
        if( $wp_query->have_posts() ) {
          while ( $wp_query->have_posts() ) {
            $wp_query->the_post();
            array_push( $matching_ids, get_the_ID() );
          }
          wp_reset_postdata();
        }

        return $matching_ids;
      }

      /**
       * Adds taxonomies keys to queryable keys list.
       *
       * @see WPP_F::get_queryable_keys()
       * @param array $keys
       * @return array
       */
      public function get_queryable_keys( $keys ) {
        $taxonomies = $this->get( 'config.taxonomies', array() );
        if( !empty( $taxonomies ) && is_array( $taxonomies ) && is_array( $keys ) ) {
          $keys = array_unique( array_merge( $keys, array_keys($taxonomies) ) );
        }
        return $keys;
      }

      /**
       * Save Custom Taxonomies
       *
       * This runs after [update_option_wpp_settings] action therefore overwriting anything it set.
       *
       * @param array $data
       */
      public function save_settings( $data = array() ) {

        if( !empty( $data[ 'wpp_terms' ] ) ) {

          /** Take care about available taxonomies */
          if( !empty($data[ 'wpp_terms' ][ 'taxonomies' ]) && is_array( $data[ 'wpp_terms' ][ 'taxonomies' ] ) ) {
            $taxonomies = array();
            foreach( $data[ 'wpp_terms' ][ 'taxonomies' ] as $taxonomy => $v ) {
              $taxonomy = substr($taxonomy, 0, 32);
              /* Ignore missed Taxonomy */
              if( empty( $v[ 'label' ] ) && count( $data[ 'wpp_terms' ] ) == 1 ) {
                break;
              }

              // Converting types to unique field
              if(isset($data[ 'wpp_terms' ][ 'types' ][$taxonomy]) && $unique = $data[ 'wpp_terms' ][ 'types' ][$taxonomy]){
                $v['unique'] = $unique == 'unique'? true: false;
              }
              $taxonomies[ $taxonomy ] = $this->prepare_taxonomy( $v, $taxonomy );
            }
            $this->set( 'config.taxonomies', $taxonomies );
          }

          /** Take care about taxonomies groups */
          if( isset($data[ 'wpp_terms' ][ 'groups' ]) ) {
            $this->set( 'config.groups', $data[ 'wpp_terms' ][ 'groups' ] );
          }

          /** Take care about taxonomies types */
          if( isset($data[ 'wpp_terms' ][ 'types' ]) ) {
            $this->set( 'config.types', $data[ 'wpp_terms' ][ 'types' ] );
          }

          /** Take care about hidden taxonomies */
          if( isset($data[ 'wpp_terms' ][ 'hidden' ]) ) {
            $this->set( 'config.hidden', $data[ 'wpp_terms' ][ 'hidden' ] );
          }

          /** Take care about inherited taxonomies */
          if( isset($data[ 'wpp_terms' ][ 'inherited' ]) ) {
            $this->set( 'config.inherited', $data[ 'wpp_terms' ][ 'inherited' ] );
          }

          $this->settings->commit();

        }

      }


      /**
       * Iterates of taxonomy settings. Cleans up, standardizes.
       *
       * @note This is triggered before [wpp::save_settings] action.
       *
       * @author potanin@UD
       *
       * @param $old_value
       * @param $settings
       * @param $option
       */
      public function update_option_wpp_settings( $old_value = null, $settings = array(), $option = '' ) {

        $_wpp_terms = array(
          'taxonomies' => isset( $settings ) && isset( $settings['taxonomies'] ) ? $settings['taxonomies'] : array(),

          // Groups term belongs to.
          'groups' => array(),

          // Set to "multiple" or "unique"
          'types' => array(),

          // List of hidden/system taxonomies
          'hidden' => array()

        );

        if( isset( $settings['taxonomies'] ) ) {
          $_wpp_terms['taxonomies'] = $settings['taxonomies'];
        }

        foreach( (array) $settings['taxonomies'] as $_taxonomy => $_taxonomy_data ) {

          // Removed legacy/unused field(s)
          if( isset( $_taxonomy_data['wpp_hidden'] )  && !$_taxonomy_data['wpp_hidden'] ) {
            unset( $_wpp_terms['taxonomies'][ $_taxonomy ]['wpp_hidden'] );
          }

          // Removed legacy/unused field(s)
          if( isset( $_taxonomy_data['wpp_input_type'] )  && !$_taxonomy_data['wpp_input_type'] ) {
            unset( $_wpp_terms['taxonomies'][ $_taxonomy ]['wpp_input_type'] );
          }

          if( isset( $_taxonomy_data['fieldGroup'] ) ) {
            unset( $_wpp_terms['taxonomies'][ $_taxonomy ]['fieldGroup'] );
          }

          // Verify rewrite rules are legic.
          if( isset( $_taxonomy_data['rewrite'] ) && isset( $_taxonomy_data['rewrite']['slug'] ) ) {

            if( $_wpp_terms['taxonomies'][ $_taxonomy ]['rewrite'] !== false ) {

              $_wpp_terms['taxonomies'][ $_taxonomy ]['rewrite'] = array(
                'slug' => isset( $_taxonomy_data['rewrite']['slug'] ) ? $_taxonomy_data['rewrite']['slug'] : null,
                'hierarchical' => isset( $_taxonomy_data['rewrite']['hierarchical'] ) ? $_taxonomy_data['rewrite']['hierarchical'] : true,
                'with_front' => isset( $_taxonomy_data['rewrite']['with_front'] ) ? $_taxonomy_data['rewrite']['with_front'] : false
              );

            }

          } else {
            unset( $_wpp_terms['taxonomies'][ $_taxonomy ]['rewrite'] );
          }

          // Set Group
          if( isset( $_taxonomy_data['wpp_group']) ) {
            $_wpp_terms['groups'][ $_taxonomy ] = $_taxonomy_data['wpp_group'];
          }

          // Set Type
          if( isset( $_taxonomy_data['wpp_input_type']) ) {
            $_wpp_terms['types'][ $_taxonomy ] = $_taxonomy_data['wpp_input_type'];
          }

          // Set Hidden flag
          if( isset( $_taxonomy_data['hidden'] ) &&  ( $_taxonomy_data['hidden'] === true || $_taxonomy_data['hidden'] === 'true' || $_taxonomy_data['hidden'] === '1'  ) ) {
            $_wpp_terms['hidden'][] = $_taxonomy;
          }

        }

        // Clean up fields
        $_wpp_terms['types'] = array_filter( $_wpp_terms['types'] );
        $_wpp_terms['groups'] = array_filter( $_wpp_terms['groups'] );
        $_wpp_terms['hidden'] = array_filter( $_wpp_terms['hidden'] );

        // clean up array.
        $_wpp_terms = array_filter( $_wpp_terms );

        self::save_settings(array(
          'wpp_terms' => $_wpp_terms
        ));

        //echo( '<pre>' . print_r( $_wpp_terms, true ) . '</pre>' );

      }

      /**
       * Add terms settings to WP-Property backup's data
       *
       * @param $data
       * @param array $options
       * @return
       * @since 1.0.3
       */
      public function backup_settings( $data, $options = array() ) {

        $data['wpp_terms'] = $this->get();

        // Exprt only field-related data.
        if( isset( $options ) && is_array( $options ) && isset( $options[ 'type' ] ) && $options[ 'type' ] === 'fields' ) {
          unset( $data['wpp_terms']['types'] );
        }

        return $data;

      }

      /**
       * Get all taxonomies are thare used for-single-value storage.
       *
       * @author potanin@UD
       * @param $args
       * @return array
       */
      public function get_single_value_taxonomies( $args = array() ) {
        global $wp_properties;

        $_taxonomies = $this->get( 'config.taxonomies', array() );
        $_types = $this->get( 'config.types', array() );

        $_results = array();

        foreach( $_taxonomies  as $_tax_key => $_tax_data ) {

          if( isset( $_types[ $_tax_key ] ) && $_types[ $_tax_key ] === 'unique' ) {
            $_results[ $_tax_key ] = $_tax_data;
          }

        }

        return (array) $_results;

      }

      /**
       * Get taxonomies for multi-value storage.
       *
       * @param array $args
       * @return array
       */
      public function get_multi_value_taxonomies( $args = array() ) {
        global $wp_properties;

        $_taxonomies = $this->get( 'config.taxonomies', array() );
        $_types = $this->get( 'config.types', array() );

        $_results = array();

        foreach( $_taxonomies  as $_tax_key => $_tax_data ) {

          if( isset( $_types ) && isset( $_types[ $_tax_key ] ) && $_types[ $_tax_key ] === 'multiple' ) {
            $_results[ $_tax_key ] = $_tax_data;
          }

        }

        return (array) $_results;

      }

      /**
       * Register Meta Box for taxonomies on Edit Property Page
       *
       * @author potanin@UD
       * @param $meta_boxes
       * @return array
       */
      public function add_meta_box( $meta_boxes ) {
        global $wp_properties;

        if( !is_array( $meta_boxes ) ) {
          $meta_boxes = array();
        }

        $type = false;
        if( isset( $_REQUEST['post'] ) && is_numeric( $_REQUEST['post'] ) ) {
          $post_id = $_REQUEST['post'];
          $type = get_post_meta( $post_id, 'property_type', true );
        }

        $taxonomies = $this->get( 'config.taxonomies', array() );
        $groups = $this->get( 'config.groups', array() );
        $types = $this->get( 'config.types', array() );

        $hidden = array();
        $inherited = array();
        if( $type ) {
          $hidden = $this->get( 'config.hidden.' . $type, array() );
          $inherited = $this->get( 'config.inherited.' . $type, array() );
        }

        $fields = array();

        foreach($taxonomies as $k => $d) {

          $d = $this->prepare_taxonomy( $d, $k );

          $field = array();

          switch( true ) {
            // Hidden
            case ( in_array( $k, $hidden ) || !empty($d['hidden'])  ):
              // Ignore field, since it's hidden.
              break;
            case ( in_array( $k, $inherited ) ):
              $field = array(
                'name' => $d['label'],
                'id' => $k,
                'type' => 'wpp_taxonomy_inherited',
                'desc' => sprintf( __( 'The terms are inherited from Parent %s.', $this->get('domain') ), \WPP_F::property_label() ),
                'options' => array(
                  'taxonomy' => $k,
                  'type' => 'inherited',
                  'args' => array(),
                )
              );
              break;

            default:
              /** Do not add taxonomy field if native meta box is being used for it. */
              if( (isset($d[ 'add_native_mtbox' ]) && $d[ 'add_native_mtbox' ])) {
                break;
              }
              if( isset($d[ 'readonly' ]) && $d[ 'readonly' ] ) {
                $field = array(
                  'name' => $d['label'],
                  'id' => $k,
                  //'type' => 'wpp_taxonomy_readonly',
                  'type' => 'wpp_taxonomy',
                  'options' => array(
                    'taxonomy' => $k,
                    'hierarchical' => ( isset( $d[ 'hierarchical' ] ) && $d[ 'hierarchical' ] == true ? true : false ),
                    'type' => ( isset( $d[ 'hierarchical' ] ) && $d[ 'hierarchical' ] == true ? 'select_tree' : 'select_advanced' ),
                    'meta' => ( isset( $d[ 'meta' ] ) && $d[ 'meta' ] == true ? true : false ),
                    'args' => array(),
                  ) );
              } else {
                $field = array(
                  'name' => $d['label'],
                  'id' => $k,
                  'type' => 'wpp_taxonomy',
                  'multiple' => ( isset( $types[ $k ] ) && $types[ $k ] == 'unique' ? false : true ),
                  'options' => array(
                    'taxonomy' => $k,
                    'type' => ( isset( $d[ 'hierarchical' ] ) && $d[ 'hierarchical' ] == true ? 'select_tree' : 'select_advanced' ),
                    'args' => array(),
                  ) );
              }
              break;
          }

          if( !empty($field) ) {

            $group = !empty( $groups[ $k ] ) ? $groups[ $k ] : '_general';

            $pushed = false;
            foreach( $meta_boxes as $k => $meta_box ) {
              if( $group == $meta_box[ 'id' ] ) {
                if( !isset( $meta_boxes[$k][ 'fields' ] ) || !is_array( $meta_boxes[$k][ 'fields' ] ) ) {
                  $meta_boxes[$k][ 'fields' ] = array();
                }
                array_push( $meta_boxes[$k][ 'fields' ], $field );
                $pushed = true;
                break;
              }
            }

            if( !$pushed ) {
              array_push( $fields, $field );
            }

          }

        }

        /** It may happen only if we could not find related group. */
        if( !empty( $fields ) ) {

          $taxonomy_box = array(
            'id' => '_terms',
            'title' => __( 'Taxonomies', ud_get_wpp_terms()->domain ),
            'pages' => array( 'property' ),
            'context' => 'advanced',
            'priority' => 'low',
            'fields' => $fields,
          );

          $_meta_boxes = array();
          $added = false;
          foreach( $meta_boxes as $meta_box ) {
            /** We want to add Taxonomies under General Meta Box */
            array_push($_meta_boxes,  $meta_box );
            if( $meta_box['id'] == '_general' ) {
              array_push($_meta_boxes,  $taxonomy_box );
              $added = true;
            }
          }

          /* In case we did not add meta box, we do it at last. */
          if(!$added) {
            array_push($_meta_boxes,  $taxonomy_box );
          }
          $meta_boxes = $_meta_boxes;

        }

        return $meta_boxes;
      }

      /**
       * Define our custom taxonomies on wpp_taxonomies hook on level 30 after WPP_F::wpp_commom_taxonomies
       *
       *
       * @param $taxonomies
       * @return \UsabilityDynamics\type
       */
      public function define_taxonomies( $taxonomies ) {

        /** Init Settings */
        $this->settings = new \UsabilityDynamics\Settings( array(
          'key'  => 'wpp_terms',
          'store'  => 'options',
          'data' => array(
            'name' => $this->name,
            'version' => $this->args[ 'version' ],
            'domain' => $this->domain,
            'types' => array(
              'multiple' => array(
                'label' => __( 'Multiple Terms', $this->domain ),
                'desc'  => __( 'Property can have multiple terms. It\'s a native WordPress functionality.', $this->domain ),
              ),
              'unique' => array(
                'label' => __( 'Unique Term', $this->domain ),
                'desc'  => __( 'Property can have only one term. ', $this->domain ),
              ),
            )
          )
        ));

        $this->prepare_taxonomies( $taxonomies );

        /**
         * Rich Taxonomies ( adds taxonomy post type )
         */
        $this->maybe_extend_taxonomies();

        /**
         * Extend Property Search with Taxonomies
         */
        $this->extend_wpp_settings();

        return $this->get( 'config.taxonomies', array() );
      }

      /**
       * Extend WP-Property settings:
       * - Extend Property Search with Taxonomies
       * - Adds Taxonomies to groups
       *
       */
      public function extend_wpp_settings() {
        global $wp_properties;

        /** STEP 1. Add taxonomies to searchable attributes */

        $taxonomies = $this->get( 'config.taxonomies', array() );

        if( !isset( $wp_properties[ 'searchable_attributes' ] ) || !is_array( $wp_properties[ 'searchable_attributes' ] ) ) {
          $wp_properties[ 'searchable_attributes' ] = array();
        }

        foreach( $taxonomies as $taxonomy => $data ) {
          if( isset( $data['public'] ) && $data['public'] ) {
            array_push( $wp_properties[ 'searchable_attributes' ], $taxonomy );
          }
        }

        ud_get_wp_property()->set( 'searchable_attributes', $wp_properties[ 'searchable_attributes' ] );

        /** STEP 2. Add taxonomies to property stats groups */

        $groups = $this->get( 'config.groups', array() );

        if( !isset( $wp_properties[ 'property_stats_groups' ] ) || !is_array( $wp_properties[ 'property_stats_groups' ] ) ) {
          $wp_properties[ 'property_stats_groups' ] = array();
        }

        $wp_properties[ 'property_stats_groups' ] = array_merge( $wp_properties[ 'property_stats_groups' ], $groups );

        ud_get_wp_property()->set( 'property_stats_groups', $wp_properties[ 'property_stats_groups' ] );

        /** STEP 3. Extend Property Search form */

        add_filter( 'wpp::show_search_field_with_no_values', function( $bool, $slug ) {
          $taxonomies = ud_get_wpp_terms( 'config.taxonomies', array() );
          if( array_key_exists( $slug, $taxonomies ) ) {
            return true;
          }
          return $bool;
        }, 10, 2 );

        /** Take care about Taxonomies fields */
        foreach( $taxonomies as $taxonomy => $data ){

          add_filter( 'wpp_search_form_field_' . $taxonomy, array($this, 'wpp_search_form_field'), 10, 6 );

        }
      }

      function wpp_search_form_field( $html, $taxonomy, $label, $value, $input, $random_id ) {

            $search_input = ud_get_wp_property( "searchable_attr_fields.{$taxonomy}" );
            $terms = get_terms( $taxonomy, array( 'fields' => 'all' ) );
            $_terms = $this->prepare_terms_hierarchicaly($terms);

            $terms = array(); // Clearing $terms variable;
            foreach ($_terms as $t) {
              $terms[$t['term_id']] = $t['name'];
            }

            ob_start();

            switch( $search_input ) {

              case 'multi_checkbox':
                ?>
                <ul class="wpp_multi_checkbox taxonomy <?php echo $taxonomy; ?>">
                  <?php foreach ( $terms as $term_id => $label ) : ?>
                    <?php $unique_id = rand( 10000, 99999 ); ?>
                    <li>
                      <input name="wpp_search[<?php echo $taxonomy; ?>][]" <?php echo( is_array( $value ) && in_array( $term_id, $value ) ? 'checked="true"' : '' ); ?> id="wpp_attribute_checkbox_<?php echo $unique_id; ?>" type="checkbox" value="<?php echo $term_id; ?>"/>
                      <label for="wpp_attribute_checkbox_<?php echo $unique_id; ?>" class="wpp_search_label_second_level"><?php echo $label; ?></label>
                    </li>
                  <?php endforeach; ?>
                </ul>
                <?php
                break;

              case 'dropdown':
              default:
                ?>
                <select id="<?php echo $random_id; ?>" class="wpp_search_select_field taxonomy <?php echo $taxonomy; ?>" name="wpp_search[<?php echo $taxonomy; ?>]">
                  <option value="-1"><?php _e( 'Any', ud_get_wpp_terms('domain') ) ?></option>
                  <?php foreach ( $terms as $term_id => $label ) : ?>
                    <option value="<?php echo $term_id; ?>" <?php selected( $value, $term_id ); ?>><?php echo $label; ?></option>
                  <?php endforeach; ?>
                </select>
                <?php
                break;

            }

            return ob_get_clean();

      }

      /**
       * Prepare arguments only for registering taxonomies
       */
      public function register_taxonomy( $args, $taxonomy ) {
        $taxonomies = $this->get( 'config.taxonomies', array() );
        // Enabling UI unless terms will not be accessible.
        $args['show_ui'] = true;
        // Enabling show_in_menu
        if(isset($taxonomies[$taxonomy]['show_in_menu']) && $taxonomies[$taxonomy]['show_in_menu']){
          $args['show_in_menu'] = true;
        }
        return $args;
      }

      /**
       * Prepare arguments
       * @param $args
       * @param $taxonomy
       * @return array
       */
      public function prepare_taxonomy( $args, $taxonomy ) {

        $args = wp_parse_args( $args, array(
          'default' => false,
          'readonly' => false,
          'system' => false,
          'meta' => false,
          'hidden' => false,
          'unique' => false,
          'label' => $taxonomy,
          'labels' => array(),
          'public' => false,
          'hierarchical' => false,
          'show_in_menu' => false,
          'add_native_mtbox' => false,
          'show_in_nav_menus' => false,
          'admin_searchable' => false,
          'show_tagcloud' => false,
          'rich_taxonomy' => false,
          'capabilities' => array(
            'manage_terms' => 'manage_wpp_categories',
            'edit_terms'   => 'manage_wpp_categories',
            'delete_terms' => 'manage_wpp_categories',
            'assign_terms' => 'manage_wpp_categories'
          ),
        ) );

        /* May be fix data type */
        foreach( $args as &$arg ) {
          if( is_string( $arg ) && $arg === 'true' ) {
            $arg = true;
          } else if( is_string( $arg ) && $arg === 'false' ) {
            $arg = false;
          }
        }

        // Ensure [hierarchical] is set unless [rewrite] is explicitly set to false.
        if( $args[ 'hierarchical' ] && isset( $args[ 'rewrite' ] ) && $args[ 'rewrite' ] !== false ) {
          $args[ 'rewrite' ][ 'hierarchical' ] = true;
        }

        return $args;
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
       * Extends property object with taxonomies, immediatly after meta fields are loaded.
       *
       * @author potanin@UD
       * @param $property
       * @param $args
       * @return mixed
       */
      public function extend_property_object( $property, $args = array() ){

        $_values = array();

        foreach( self::get_single_value_taxonomies() as $_tax_key => $_tax_data ) {

          if( isset( $_tax_data['hidden'] ) && $_tax_data['hidden']) { continue; }

          $_terms = wp_get_object_terms( $property['ID'], $_tax_key, array( 'fields' => 'names' ) );

          if( !empty( $_terms ) ) {
            $_values[ $_tax_key ] = end( $_terms );
            //WPP_F::debug("Getting single-value terms for [$_tax_key], setting to [" . $_values[ $_tax_key ] . ']' );
          } else {
            //WPP_F::debug("Getting single-value terms for [$_tax_key], no values found." );
          }

        }

        foreach( self::get_multi_value_taxonomies() as $_tax_key => $_tax_data ) {

          if( isset( $_tax_data['hidden'] ) && $_tax_data['hidden'] === true ) {
            continue;
          }

          $_terms = wp_get_object_terms( $property['ID'], $_tax_key, array( 'fields' => 'names' ) );

          if( !empty( $_terms ) ) {
            $_values[ $_tax_key ] = $_terms;
            //WPP_F::debug("Getting multi-value terms for [$_tax_key], setting to [" . join( ', ', $_terms ) . ']' );
          } else {
            //WPP_F::debug("Getting multi-value terms for [$_tax_key], no values found." );
          }

        }

        // Extend property object with taxonomy data.
        $_result = array_merge( $property, $_values );

        // WPP_F::debug("Terms has extneded property object.", $_values );
        return $_result;

      }

      /**
       * Iterate over property object, verify now single-value taxonomies are being handled as arrays.
       *
       * @param $property
       * @param array $args
       * @return mixed
       */
      public function finalize_property_object( $property, $args = array() ) {

        foreach( self::get_single_value_taxonomies() as $_tax_key => $_tax_data ) {

          if( isset(  $property[$_tax_key] ) && is_array( $property[$_tax_key] ) ) {

            $property[$_tax_key] = end( $property[$_tax_key] );
          }

        }

        return $property;

      }

      /**
       * Extend the draw_attributes function with single-value taxonomies being treatued as regualr attributes
       *
       * @param $property_stats
       * @param $property
       * @param array $args
       */
      public function draw_attributes( $property_stats, $property, $args = array() ){

        foreach( self::get_single_value_taxonomies() as $_tax_key => $_tax_data ) {

          if( is_object( $property ) ) {
            if(!isset($property->{$_tax_key})){
              $term = get_the_terms( $property->ID, $_tax_key);
              $property->{$_tax_key} = isset($term[0])?$term[0]->name:'';
            }

            $_item = array(
              'label' => $_tax_data['label'],
              'value' => $property->{$_tax_key}
            );

          }

          if( is_array( $property ) ) {
            if(!isset($property[$_tax_key])){
              $term = get_the_terms( $property['ID'], $_tax_key);
              $property[$_tax_key] = isset($term[0])?$term[0]->name:'';
            }

            $_item = array(
              'label' => $_tax_data['label'],
              'value' => $property[$_tax_key]
            );

          }

          if( isset( $_item ) && ( $args['return_blank'] == 'true' || $_item['value'] ) ) {

            $property_stats[ $_tax_key ] = $_item;

          }

        }

        return $property_stats;

      }

      /**
       *
       */
      public function ajax_term_autocomplete(){
        $terms = get_terms($_REQUEST['taxonomy'], array('fields' => 'all', 'hide_empty'=>false));
        $return = $this->prepare_terms_hierarchicaly($terms);

        // Converting keys from term_id to value and name to label
        $return = array_map(function($t){
          return array('value' => $t['term_id'], 'label' => $t['name']);
        }, $return);
        wp_send_json($return);
        die();
      }

      /**
       *
       * prepare_terms_hierarchicaly
       *
       * @param $terms
       *
       * @return array
       */
      public function prepare_terms_hierarchicaly($terms){
        $_terms = array();
        $return = array();

        if(count($terms) == 0)
          return $return;

        // Prepering terms
        foreach ($terms as $term) {
          $_terms[$term->parent][] = array('term_id' => $term->term_id, 'name' => $term->name);
        }

        // Making terms as hierarchical by prefix
        foreach ($_terms[0] as $term) { // $_terms[0] is parent or parentless terms
          $return[] = $term;
          $this->get_children($term['term_id'], $_terms, $return);
        }

        return $return;
      }

      // Helper function for prepare_terms_hierarchicaly
      public function get_children($term_id, $terms, &$return, $prefix = "-"){
        if(isset($terms[$term_id])){
          foreach ($terms[$term_id] as $child) {
            $child['name'] = $prefix . " " . $child['name'];
            $return[] = $child;
            $this->get_children($child['term_id'], $terms, $return, $prefix . "-");
          }
        }
      }

      /**
       * Run Upgrade Process.
       *
       */
      public function run_upgrade_process() {
        Terms_Upgrade::run( $this->old_version, $this->args['version'] );
      }

    }

  }

}
