<?php
/**
 * Meta Box UI
 *
 * @since 2.0.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;
  use WP_Post;

  if( !class_exists( 'UsabilityDynamics\WPP\Meta_Box' ) ) {

    class Meta_Box {

      /**
       * Constructor
       * Sets default data.
       * @param bool $args
       */
      public function __construct( $args = false ) {

        /* Be sure all required files are loaded. */
        add_action( 'init', array( $this, 'load_files' ), 1 );

        /* Register all RWMB meta boxes */
        add_action( 'rwmb_meta_boxes', array( $this, 'register_meta_boxes' ) );

        //** Add metaboxes hook */
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1 );

        //** add attachement id to a temporary post meta in which the attachment is attached.
        //** this will process later by add_attached_image_to_wpp_media method  */
        add_action( 'add_attachment', array( $this, 'add_attachment' ));
        //** Add push recently attached image to wpp_media meta. */
        add_filter( 'rwmb_wpp_media_value', array( $this, 'add_attached_image_to_wpp_media'), 9, 3);

      }

      /**
       * Register metaboxes.
       *
       */
      function add_meta_boxes() {

        // Template selection only used when layouts are not enabled.
        if( !WP_PROPERTY_LAYOUTS ) {
          add_meta_box( 'wpp_property_template', __( 'Template', ud_get_wp_property('domain') ), array( $this, 'render_template_meta_box' ), 'property', 'side', 'default' );
        }

      }

      /**
       * May be loads all required RWMB Meta Box files
       */
      public function load_files() {

        if( !class_exists( '\RW_Meta_Box' ) ) {
          include_once(dirname(  __DIR__) . '/features/meta-box/meta-box/meta-box.php');
        }

        if( !class_exists( '\MB_Conditional_Logic' ) ) {
          include_once(dirname(  __DIR__) . '/features/meta-box/meta-box-conditional-logic/meta-box-conditional-logic.php');
        }

        if( !class_exists( '\MB_Show_Hide' ) ) {
          include_once(dirname(  __DIR__) . '/features/meta-box/meta-box-show-hide/meta-box-show-hide.php');
        }

        if( !class_exists( '\RWMB_Group' ) ) {
          include_once(dirname(  __DIR__) . '/features/meta-box/meta-box-group/meta-box-group.php');
        }

        if( !class_exists( '\MB_Tabs' ) ) {
          include_once(dirname(  __DIR__) . '/features/meta-box/meta-box-tabs/meta-box-tabs.php');
        }

      }

      /**
       * Loaded if this is a property page, and child properties exist.
       *
       * @version 1.26.0
       * @author Andy Potanin <andy.potanin@twincitiestech.com>
       * @package WP-Property
       */
      public function render_child_properties_meta_box( $post ) {

        $list_table = new Children_List_Table( array(
          'name' => 'wpp_edit_property_page',
          'per_page' => 10,
        ) );

        $list_table->prepare_items();
        $list_table->display();

      }

      /**
       * Adds Template Manager for Property
       *
       * @since 2.1.0
       * @author peshkov@UD
       * @param $post
       */
      public function render_template_meta_box( $post ) {

        $config = ud_get_wp_property( 'configuration.single_property', array() );
        $redeclare = get_post_meta( $post->ID, '_wpp_redeclare_template', true );

        if( !empty( $redeclare ) && $redeclare == 'true' ) {
          $template = get_post_meta( $post->ID, '_wpp_template', true );
          $page_template = get_post_meta( $post->ID, '_wpp_page_template', true );
        }

        if( empty( $template ) ) {
          $template = !empty( $config[ 'template' ] ) ? $config[ 'template' ] : 'property';
        }

        if( empty( $page_template ) ) {
          $page_template = !empty( $config[ 'page_template' ] ) ? $config[ 'page_template' ] : 'default';
        }

        $file = ud_get_wp_property()->path( 'static/views/admin/metabox-template.php', 'dir' );
        if( file_exists( $file ) ) {
          include( $file );
        }

      }

      /**
       * Register all meta boxes here.
       * @param $meta_boxes
       * @return array
       */
      public function register_meta_boxes( $meta_boxes ) {
        global $wpdb;

        $_meta_boxes = array();

        /* May be determine property_type to know which attributes should be hidden and which ones just readable. */
        $post = new WP_Post( new \stdClass );

        $post_id = isset( $_REQUEST['post'] ) && is_numeric( $_REQUEST['post'] ) ? $_REQUEST['post'] : false;

        if( !$post_id && !empty( $_REQUEST['post_ID'] ) && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'editpost' ) {
          $post_id = $_REQUEST['post_ID'];
        }

        if( $post_id ) {

          $p = get_property( $post_id, array(
            'get_children'          => 'true',
            'return_object'         => 'true',
            'load_gallery'          => 'false',
            'load_thumbnail'        => 'false',
            'load_parent'           => 'true',
            'cache'                 => 'false'
          ) );

          if( !empty($p) ) {
            $post = $p;
          }

        }

        $groups = ud_get_wp_property( 'property_groups', array() );
        $property_stats_groups = ud_get_wp_property( 'property_stats_groups', array() );

        /* Register 'General Information' metabox for Edit Property page */
        $meta_box = $this->get_property_meta_box( array(
          'name' => __( 'General', ud_get_wp_property()->domain )
        ), $post );

        if( $meta_box ) {
          $_meta_boxes[] = $meta_box;
        }

        if( WPP_FEATURE_FLAG_DISABLE_EDITOR ) {
          $_meta_boxes[] = array(
            'id' => 'wpp_content',
            'title' => __( "Content", ud_get_wp_property()->domain ),
            'pages' => array( 'property' ),
            'context' => 'normal',
            'priority' => 'high',
            'fields' => array(
              $this->get_editor_field( $post )
            )
          );
        }

        $_meta_boxes[] = array(
          'id' => 'wpp_media',
          'title' => __( "Media", ud_get_wp_property()->domain ),
          'pages' => array( 'property' ),
          'context' => 'normal',
          'priority' => 'high',
          'fields' => array(
            $this->get_media_field( $post )
          )
        );

        /**
         * Add meta box (Tab) for child properties
         */
        if( isset( $post ) && $post->post_type == 'property' && $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = '{$post->ID}' AND post_status = 'publish' " ) ) {

          $_meta_boxes[] = array(
            'id' => 'wpp_child_properties',
            'title' => sprintf( __( 'Child %s', ud_get_wp_property('domain') ), WPP_F::property_label( 'plural' ) ),
            'pages' => array( 'property' ),
            'context' => 'normal',
            'priority' => 'high',
            'fields' => array(
              array(
                'id' => 'wpp_child_properties_field',
                'type' => 'wpp_child_properties',
              )
            )
          );

        }

        if( WPP_FEATURE_FLAG_WPP_ROOMS ) {

          $_meta_boxes[] = array(
            'id' => 'wpp_rooms',
            'title' => "Rooms",
            'pages' => array( 'property' ),
            'context' => 'normal',
            'priority' => 'high',
            'fields' => array(
              $this->get_rooms_field( $post )
            )
          );

        }

        /* Register Meta Box for every Attributes Group separately */
        if ( !empty( $groups) && !empty( $property_stats_groups ) ) {
          foreach ( $groups as $slug => $group ) {
            $meta_box = $this->get_property_meta_box( array_filter( array_merge( $group, array( 'id' => $slug ) ) ), $post );
            if( $meta_box ) {
              $_meta_boxes[] = $meta_box;
            }
          }
        }

        /**
         * Allow to customize our meta boxes via external add-ons ( modules, or whatever else ).
         */
        $_meta_boxes = apply_filters( 'wpp::meta_boxes', $_meta_boxes );

        if( !is_array( $_meta_boxes ) ) {
          return $meta_boxes;
        }

        /** Get rid of meta box without fields data. */
        foreach( $_meta_boxes as $k => $meta_box ) {
          if( empty( $meta_box[ 'fields' ] ) ) {
            unset( $_meta_boxes[ $k ] );
          }
        }

        /**
         *  Probably convert Meta Boxes to single one with tabs
         */
        $_meta_boxes = $this->maybe_convert_to_tabs( $_meta_boxes );

        if( is_array( $meta_boxes ) ) {
          $meta_boxes = $meta_boxes + $_meta_boxes;
        }

        return $meta_boxes;
      }

      /**
       * @param $meta_boxes
       * @return array
       */
      public function maybe_convert_to_tabs( $meta_boxes ) {

        if( ud_get_wp_property( 'configuration.disable_meta_box_tabs', false ) ) {
          return $meta_boxes;
        }

        if( count( $meta_boxes ) <= 1 ) {
          return $meta_boxes;
        }

        $meta_box = array(
          'id' => '_general',
          'title' => sprintf( __( '%s Details', ud_get_wp_property()->domain ), WPP_F::property_label() ),
          'pages' => array( 'property' ),
          'context' => 'advanced',
          'priority' => 'low',
          'tab_style' => 'left',
          'tabs' => array(),
          'fields' => array(),
        );

        $icons = apply_filters( 'wpp::meta_boxes::icons', array(
          '_general' => 'dashicons-admin-home',
        ) );

        foreach( $meta_boxes as $b ) {

          if( !isset( $meta_box['tabs'][ $b['id'] ] ) ) {
            $meta_box['tabs'][ $b['id'] ] = array(
              'label' => $b['title'],
              'icon' => array_key_exists($b['id'], $icons) ? $icons[$b['id']] : 'dashicons-admin-page',
            );
          }

          if( !empty( $b['fields'] ) && is_array( $b['fields'] ) ) {
            foreach( $b['fields'] as $field ) {
              array_push( $meta_box[ 'fields' ], array_merge( $field, array(
                'tab' => $b['id'],
              ) ) );
            }
          }

        }

        return array($meta_box);
      }

      /**
       * Prepare Property Meta Box based on Attributes' Groups for registration.
       *
       * @since 2.0
       * @author peshkov@UD
       * @param array $group
       * @param bool $post
       * @return mixed|void
       */
      public function get_property_meta_box( $group = array(), $post = false ) {

        $group = wp_parse_args( $group, array(
          'id' => false,
          'name' => __( 'NO NAME', ud_get_wp_property()->domain ),
        ) );

        $fields = array();

        /**
         * Get all data we need to operate with.
         */
        $attributes = ud_get_wp_property( 'property_stats', array() );
        $taxonomies = ud_get_wp_property( 'taxonomies', array() );
        $defaults = ud_get_wp_property( 'default_values', array() );
        $geo_type_attributes = ud_get_wp_property( 'geo_type_attributes', array() );
        $hidden_attributes = ud_get_wp_property( 'hidden_attributes', array() );
        $inherited_attributes = ud_get_wp_property( 'property_inheritance', array() );

        // @todo Implement, if a property type does NOT support hierarchies, we should now hos "Falls Under" field.
        // $type_supports_hierarchy = ud_get_wp_property( 'type_supports_hierarchy', array() );

        $property_stats_groups = ud_get_wp_property( 'property_stats_groups', array() );

        $predefined_values = ud_get_wp_property( 'predefined_values', array() );
        $descriptions = ud_get_wp_property( 'descriptions', array() );
        $input_types = ud_get_wp_property( 'admin_attr_fields' );

        //** Detect attributes that were taken from a range of child properties. */
        $aggregated_attributes = !empty( $post->system[ 'upwards_inherited_attributes' ] ) ? (array)$post->system[ 'upwards_inherited_attributes' ] : array();

        /**
         * If group ID is not defined, it means that we're registering main Meta Box
         * So, here, we're adding custom fields for management!
         */
        if( $group['id'] == false ) {

          /* May be add Property Parent field - 'Falls Under' */
          $field = apply_filters( "wpp::rwmb_meta_box::field::parent_property", $this->get_parent_property_field( $post ), $post );
          //* Ignore Hidden Attributes */
          if (
            empty( $post->property_type )
            || empty( $hidden_attributes[ $post->property_type ] )
            || !in_array( 'parent', (array)$hidden_attributes[ $post->property_type ] )
          ) {
            if( !empty($field) ) {
              $fields[] = $field;
            }
          }

          /* May be add Property Type field. */
          if( !array_key_exists( 'property_type', $attributes ) ) {
            $field = apply_filters( "wpp::rwmb_meta_box::field::property_type", $this->get_property_type_field( $post ), $post );
            if( !empty($field) ) {
              $fields[] = $field;
            }
          }

          if( WPP_FEATURE_FLAG_WPP_LISTING_STATUS ) {

              $fields[] = apply_filters( 'wpp::rwmb_meta_box::field', array_filter( array(
                'id' => 'wpp_listing_status',
                'name' => $taxonomies['wpp_listing_status']['label'],
                'type' => 'taxonomy',
                //'placeholder' => sprintf( __( 'Select %s Type', ud_get_wp_property()->domain ), WPP_F::property_label() ),
                'multiple' => false,
                'options' => array(
                  'taxonomy' => 'wpp_listing_status',
                  'type' => 'select',
                  'args' => array(),
                )
              ) ), 'wpp_listing_status', $post );

          }

          /* May be add Meta fields */
          foreach( ud_get_wp_property()->get( 'property_meta', array() ) as $slug => $label ) {

            $fields[] = apply_filters( 'wpp::rwmb_meta_box::field', array_filter( array(
              'id' => $slug,
              'name' => $label,
              'type' => 'textarea',
              'desc' => __( 'Meta description.', ud_get_wp_property()->domain ),
            ) ), $slug, $post );

          }

        }


        /**
         * Loop through all available attributes and determine if any of them must be added to current meta box.
         */
        foreach ( $attributes as $slug => $label ) {

          /**
           * Determine if we should add attribute's field in this meta box
           */

          //** Show ( or not ) attribute field on Edit property page for current Property. */
          if( !apply_filters( 'wpp::metabox::attribute::show', true, $slug, $post->ID ) ) {
            continue;
          }

          //* Determine if attribute is assigned to group. */
          if ( !empty( $property_stats_groups[ $slug ] ) && $property_stats_groups[ $slug ] != $group[ 'id' ] ) {
            continue;
          }

          //** Do not show attribute in group's meta box if it's not assigned to groups at all */
          if( empty( $property_stats_groups[ $slug ] ) && !empty( $group[ 'id' ] ) ) {
            continue;
          }

          //* Ignore Hidden Attributes */
          if (
            !empty( $post->property_type )
            && ( ! is_object( $post->property_type ) || ! is_array( $post->property_type ) )
            && !empty( $hidden_attributes[ $post->property_type ] )
            && in_array( $slug, (array)$hidden_attributes[ $post->property_type ] )
          ) {
            continue;
          }

          /**
           * Looks like we have to add attribute's field to the current meta box.
           * So, setup data for it now.
           */

          //* HACK. If property_type is set as attribute, we register it here. */
          if( $slug == 'property_type' ) {
            $field = apply_filters( "wpp::rwmb_meta_box::field::property_type", $this->get_property_type_field( $post ), $post );
            if( !empty($field) ) {
              $fields[] = $field;
            }
            continue;
          }

          $attribute = Attributes::get_attribute_data( $slug );

          $description = array();
          $description[ ] = ( isset( $attribute[ 'numeric' ] ) || isset( $attribute[ 'currency' ] ) ? __( 'Numbers only.', ud_get_wp_property()->domain ) : '' );
          $description[ ] = ( !empty( $descriptions[ $slug ] ) ? $descriptions[ $slug ] : '' );

          /**
           * PREPARE INPUT TYPE
           * May be convert input_type to valid type.
           */
          $input_type = !empty( $input_types[ $slug ] ) ? $input_types[ $slug ] : 'text';

          //* Geo Attributes */
          if( in_array( $slug, $geo_type_attributes ) ) {
            $input_type = 'wpp_readonly';
            $description[] = __( 'The value is being generated automatically on Google Address Validation.', ud_get_wp_property()->domain );
          }

          //* Legacy compatibility */
          if( in_array( $input_type, array( 'input' ) ) ) {
            $input_type = 'text';
          }

          //* Legacy compatibility */
          if( in_array( $input_type, array( 'dropdown' ) ) ) {
            $input_type = 'select';
          }

          //* Legacy compatibility */
          if( in_array( $input_type, array( 'checkbox' ) ) ) {
            $input_type = 'wpp_checkbox';
          }

          //* Legacy compatibility */
          if( in_array( $input_type, array( 'multi_checkbox' ) ) ) {
            $input_type = 'checkbox_list';
          }

          //* Fix currency */
          if( $input_type == 'currency' ) {
            $input_type = 'text'; // HTML5 does not allow to use float, so we have to use default 'text' here
            $description[] =  __( 'Currency.', ud_get_wp_property('domain') );
          }
          if( $input_type == 'number' ) {
            $input_type = 'text'; // HTML5 does not allow to use float, so we have to use default 'text' here
          }

          //** Determine if current attribute is used by Google Address Validator. */
          if( ud_get_wp_property( 'configuration.address_attribute' ) == $slug ) {
            $input_type = 'wpp_address';

            // Too obvious, I believe. -potanin@UD
            // $description[] = __( 'The value is being used by Google Address Validator to determine and prepare address to valid format. However you can set coordinates manually.', ud_get_wp_property()->domain );
          }

          $original_type = $input_type;
          //* Is current attribute inherited from parent? If so, set it as readonly!. */
          if(
            isset( $post->post_parent ) &&
            $post->post_parent > 0 &&
            isset( $post->property_type ) &&
            !empty( $inherited_attributes[ $post->property_type ] ) &&
            in_array( $slug, $inherited_attributes[ $post->property_type ] )
          ) {
            $input_type = 'wpp_inherited';
            $description[] = sprintf( __( 'The value is inherited from Parent %s.', ud_get_wp_property()->domain ), WPP_F::property_label() );
          }

          //** Is current attribute's value aggregated from child properties? If so, set it as readonly! */
          if( !empty( $aggregated_attributes ) && in_array( $slug, $aggregated_attributes ) ) {
            $input_type = 'wpp_aggregated';
            $description[] = sprintf( __( 'The value is aggregated from Child %s.', ud_get_wp_property()->domain ), WPP_F::property_label( 'plural' ) );
          }

          //** Determine if current attribute is used by Google Address Validator. */
          if( ud_get_wp_property( 'configuration.address_attribute' ) == $slug ) {
            if( $input_type == 'wpp_inherited' ) {
              $input_type = 'wpp_inherited_address';
            } else {
              $input_type = 'wpp_address';
              //$description[] = __( 'The value is being used by Google Address Validator to determine and prepare address to valid format. However you can set coordinates manually.', ud_get_wp_property()->domain );
            }
          }

          /**
           * Check for pre-defined values
           */
          $options = array();
          if( $input_type == 'select' ) {
            $options[''] = __( 'Not Selected', ud_get_wp_property()->domain );
          }
          if ( !empty( $predefined_values[ $slug ] ) && is_string( $predefined_values[ $slug ] ) ) {
            $_options = explode( ',', trim( $predefined_values[ $slug ] ) );
            foreach( $_options as $option ) {
              $option = trim( preg_replace( "/\r|\n/", "", $option ) );
              $options[ esc_attr( $option ) ] = apply_filters( 'wpp_stat_filter_' . $slug, $option );
            }
          }

          $default = isset($defaults[$slug])?$defaults[$slug]:"";
          //if(!empty($default) && $attribute['multiple']){
          //  $_defaults = explode(',', $default);
          //  $default = array();
          //  foreach ($_defaults as $key => $d) {
          //    $d = trim( preg_replace( "/\r|\n/", "", $d ) );
          //    $default[esc_attr( $d ) ] = apply_filters( 'wpp_stat_filter_' . $slug, $d );
          //  }
          //}
          /**
           * Well, init field now.
           */
          $fields[] = apply_filters( 'wpp::rwmb_meta_box::field', array_filter( array(
            'id' => $slug,
            'name' => $label,
            'type' => $input_type,
            'std' => $default,
            'original_type' => $original_type,
            'desc' => implode( ' ', (array) $description ),
            'options' => $options,
          ) ), $slug, $post );

        }

        $meta_box = apply_filters( 'wpp::rwmb_meta_box', array(
          'id'       => !empty( $group['id'] ) ? $group['id'] : '_general',
          'title'    => $group['name'],
          'pages'    => array( 'property' ),
          'context'  => 'normal',
          'priority' => 'high',
          'fields'   => array_filter( $fields ),
        ), $group, $post );

        return $meta_box;
      }

      /**
       * Parent Property Selection
       *
       * @return array
       */
      public function get_parent_property_field( ) {

        $field = array(
          'name' => __('Falls Under', ud_get_wp_property()->domain),
          'id' => 'parent_id',
          'type' => 'wpp_parent',
          'show' => array( 'wpp_listing_type', '=', 'Building' ),
          'hide' => array(
            'relation'      => 'OR',
            'wpp_listing_type' => array( 6487, 'Building' )
          ),
          'options' => admin_url( 'admin-ajax.php?action=wpp_autocomplete_property_parent' ),
        );


        return $field;
      }

      /**
       * Repeatable Rooms Field
       *
       * @author potanin@UD
       * @param $post
       * @return array
       */
      public function get_rooms_field( $post ) {

        return array(
          'id'     => 'wpp_rooms',
          'type'   => 'group',
          'clone'  => true,
          'sort_clone' => true,
          'fields' => array(
            array(
              'name'    => __( 'Type', 'rwmb' ),
              'id'      => 'room_type',
              'type'    => 'select_advanced',
              'options' => array(
                'utility'  => __( 'Utility', 'rwmb' ),
                'master-bedroom'  => __( 'Master Bedroom', 'rwmb' ),
                'bedroom'  => __( 'Bedroom', 'rwmb' ),
                'office' => __( 'Office', 'rwmb' ),
                'basement' => __( 'Basement', 'rwmb' ),
                'dining' => __( 'Dining', 'rwmb' ),
                'kitchen' => __( 'Kitchen', 'rwmb' ),
              ),
            ),
            array(
              'name' => __( 'Level', 'rwmb' ),
              'id'   => 'level',
              'type' => 'text',
            ),
            array(
              'name' => __( 'Description', 'rwmb' ),
              'id'   => 'description',
              'type' => 'text',
            ),
            array(
              'name' => __( 'Dimensions', 'rwmb' ),
              'id'   => 'text',
              'type' => 'text'
            ),
            array(
              'name' => __( 'Detail', 'rwmb' ),
              'id'   => 'key_value',
              'type' => 'key_value',
            ),
            array(
              'name'  => __( 'Image', 'rw_' ),
              'id'    => "room_image",
              'type'  => 'image_advanced',
              'max_file_uploads' => 1,
            ),
          ),
        );

      }

      /**
       * Editor Field.
       *
       * @todo Make save/udpate post_content.
       *
       * @param $post
       * @return array
       */
      public function get_editor_field( $post ) {

        return array(
          'id'     => 'wpp_description',
          'type' => 'wysiwyg',
          'options' => array(
            'teeny' => true,
            'editor_height' => 225,
            'tinymce' => true,
            'quicktags' => false,
            'media_buttons' => false,
            'drag_drop_upload' => false,
          )
        );

      }

      /**
       * Media View/Upload
       *
       * @todo Add featured thumbnail selection. - potanin@UD
       *
       * @param $post
       * @return array
       */
      public function get_media_field( $post ) {
        $_meta_attached = array();
        
        if(!empty($post->ID)){
          $_meta_attached = get_post_meta( $post->ID, 'wpp_media' );
          // Backward compatibility
          if(empty($_meta_attached)){
            // getting unordered media.
            $_attached        = array_keys( get_attached_media( 'image', $post->ID ));
            // wpp slideshow field
            $slideshow_order  = get_post_meta($post->ID, 'slideshow_images', true);
            // wpp slideshow field
            $gallery_order    = get_post_meta( $post->ID, 'gallery_images', true );
            $ordered          = array_unique(array_merge((array) $slideshow_order, (array) $gallery_order));
            
            // removing ordered images from unordered image
            // so that we can add unordered images at the end.
            foreach ($ordered as $order_id) {
              $key = array_search($order_id, $_attached);
              if($key !== false){
                unset($_attached[$key]);
              }
            }
            
            $_meta_attached = array_values(array_merge($ordered, $_attached));
          }
        }

        return array(
          'id' => 'wpp_media',
          'type' => 'image_advanced',
          //'max_file_uploads' => 15,
          'js_options' => array(
            'ids' => $_meta_attached
          )
        );
      }

      /**
       * add attachement id to a temporary post meta in which the attachment is attached.
       * this will process later by add_attached_image_to_wpp_media method  
       */
      public function add_attachment($attachment_id){
        $parent_id = wp_get_post_parent_id($attachment_id);
        if($parent_id){
          $new_attached_media = get_post_meta( $parent_id, 'new_attached_media', true );
          if(!is_array($new_attached_media)){
            $new_attached_media = array();
          }
          $new_attached_media[] = $attachment_id;
          update_post_meta($parent_id, 'new_attached_media', $new_attached_media);
        }
      }

      /**
       * Push recently attached image to wpp_media meta.
       * And delete the temporary meta.
       */
      public function add_attached_image_to_wpp_media($new, $field, $old){
        if(!empty($_POST['ID'])){
          $pid = $_POST['ID'];
          $new_attached_media = get_post_meta( $pid, 'new_attached_media', true );
          
          if($new_attached_media && is_array($new_attached_media)){
            foreach($new_attached_media as $attachment_id){
              if(!in_array($attachment_id, $new)){
                $new[] = $attachment_id;
              }
            }
          }
          
          delete_post_meta( $pid, 'new_attached_media' );
        }
        return $new;
      }

      /**
       * Return RWMB Field for Property Type
       *
       */
      public function get_property_type_field( ) {

        $types = ud_get_wp_property( 'property_types', array() );

        if( empty( $types ) ) {
          return false;
        }

        if( count( $types ) > 1 ) {
          $types = array_merge( array( '' => __( 'No Selected', ud_get_wp_property()->domain ) ), $types );
          $field = array(
            'id' => 'property_type',
            'name' => sprintf( __( '%s Type', ud_get_wp_property()->domain ), WPP_F::property_label() ),
            // 'desc' => sprintf( __( '%s Attributes are related to Property Type. They can be aggregated, inherited or hidden after updating the current type.', ud_get_wp_property()->domain ), WPP_F::property_label() ),
            'type' => 'select',
            'options' => $types,
          );
        } else {
          $field = array(
            'id' => 'property_type',
            'type' => 'hidden',
            'std' => key($types),
          );
        }

        return $field;
      }

    }

  }

}
