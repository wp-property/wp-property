<?php
/**
 * Overview UI
 *
 * @author UsabilityDynamics, inc
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;
  use UsabilityDynamics\UI;

  if (!class_exists('UsabilityDynamics\WPP\Admin_Overview')) {

    /**
     *
     *
     * @author UsabilityDynamics, inc
     */
    class Admin_Overview extends Scaffold {

      /**
       * Constructor
       *
       * @author peshkov@UD
       */
      public function __construct() {
        parent::__construct();

        /** Handle screen option 'per page' */
        add_filter('set-screen-option', array( $this, 'set_per_page_option' ), 10, 3);

        /** Init Administration Menu */
        add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );

      }

      /**
       * Rentals Administration Menu
       */
      public function admin_menu() {
        global $wp_properties, $submenu;

        /* Add submenu page using already existing UI for overview page */
        $this->page = new UI\Page( 'edit.php?post_type=property', $this->get( 'labels.all_items' ), $this->get( 'labels.all_items' ), 'edit_wpp_properties', 'all_properties' );

        add_action( 'load-' . $this->page->screen_id, array( $this, 'preload' ) );
        /* Register meta boxes */
        add_action( 'add_meta_boxes_'.$this->page->screen_id, array( $this, 'add_meta_boxes' ) );
        add_filter( 'ud:ui:page:title', array( $this, 'render_page_title' ));

        /**
         * Next used to add custom submenu page 'All Properties' with Javascript dataTable
         * @author Anton K
         */
        if( !empty( $submenu[ 'edit.php?post_type=property' ] ) ) {
          // Comment next line if you want to get back old Property list page.
          array_shift( $submenu[ 'edit.php?post_type=property' ] );
          foreach( $submenu[ 'edit.php?post_type=property' ] as $key => $page ) {
            if( $page[ 2 ] == 'all_properties' ) {
              unset( $submenu[ 'edit.php?post_type=property' ][ $key ] );
              array_unshift( $submenu[ 'edit.php?post_type=property' ], $page );
            } elseif( $page[ 2 ] == 'post-new.php?post_type=property' ) {
              // Removes 'Add Property' from menu if user can not edit properties. peshkov@UD
              if( !current_user_can( 'edit_wpp_properties' ) ) {
                unset( $submenu[ 'edit.php?post_type=property' ][ $key ] );
              }
            }
          }
        }

        if (isset($_GET['post'])) {
          $post_data = get_post($_GET['post']); // disable 'trash' post button from edit post page
          if ($post_data->post_name == $wp_properties['configuration']['base_slug']) {
            add_action('post_submitbox_start', function () {
              echo '<style>form#post #delete-action {display: none}</style>';
            });
          }
        }

        do_action( 'wpp_admin_menu' );
      }

      /**
       * Set our custom screen option 'per_page' ( 'wp_properties_per_page' )
       *
       * @param $status
       * @param $option
       * @param $value
       * @return mixed
       */
      public function set_per_page_option( $status, $option, $value ){
        if ( 'wp_properties_per_page' == $option ) return $value;
        return $status;
      }

        /**
       * Init our List Table before page loading
       */
      public function preload(){

        /** Add 'Per Page' screen option and retrieve the current user's value */

        $per_page_default = 20;

        add_screen_option( 'per_page', array(
          'label' => __( 'Number of Rows per page.', ud_get_wp_property( 'domain' ) ),
          'default' => $per_page_default,
          'option' => 'wp_properties_per_page'
        ) );

        $per_page = get_user_meta( get_current_user_id(), 'wp_properties_per_page' , true );
        if( empty( $per_page ) ) {
          $per_page = $per_page_default;
        }

        /** Init our List Table */

        $this->list_table = new List_Table( array(
          'name' => 'wpp_overview',
          'per_page' => $per_page,
          'filter' => array(
            'fields' => $this->get_filter_fields(),
          )
        ) );

      }

      /**
       * Prepare and return list of filter fields
       *
       * @return array
       */
      public function get_filter_fields() {
        $fields = array(
          array(
            'id' => 's',
            'name' => __( 'Global Search', $this->get('domain') ),
            'placeholder' => __( 'Search', $this->get('domain') ),
            'type' => 'text',
          ),
          array(
            'id' => 'page_id',
            'name' => __( 'Property ID', $this->get('domain') ),
            'placeholder' => __( 'Property ID', $this->get('domain') ),
            'type' => 'text',
          ),
          array(
            'id' => 'post_status',
            'name' => __( 'Status', $this->get('domain') ),
            'type' => 'select_advanced',
            'js_options' => array(
              'allowClear' => false,
            ),
            'options' => $this->get_post_statuses(),
          ),
          array(
            'id' => 'author',
            'name' => __( 'Author', $this->get('domain') ),
            'type' => 'select_advanced',
            'js_options' => array(
                'allowClear' => true,
            ),
            'map' => array(
              'class' => 'post'
            ),
            'options' => array( 0 => __( 'All', ud_get_wp_property()->domain ) ) + (array) WPP_F::get_users_of_post_type('property')
          ),
          array(
            'id' => 'property_type',
            'name' => sprintf( __( '%s Type', $this->get('domain') ), WPP_F::property_label( 'plural' ) ),
            'type' => 'select_advanced',
            'js_options' => array(
              'allowClear' => true,
            ),
            'options' => array_merge( array( '' => '' ), ud_get_wp_property('property_types', array()) ),
          ),
          array(
            'id' => 'featured',
            'name' => __( 'Featured', $this->get('domain') ),
            'type' => 'checkbox',
          ),
          array(
            'id' => 'post_date_min',
            'name' => __( 'Added Date from', ud_get_wp_property()->domain ),
            'type' => 'date',
            'js_options' => array(
              'allowClear' => true,
            ),
            'map' => array(
              'class' => 'date_query',
              'compare' => 'after'
            )
          ),
          array(
            'id' => 'post_date_max',
            'name' => __( 'Added Date to', ud_get_wp_property()->domain ),
            'type' => 'date',
            'js_options' => array(
              'allowClear' => true,
            ),
            'map' => array(
              'class' => 'date_query',
              'compare' => 'before'
            )
          )
        );

        global $wpp_property_import;
        if (!empty($wpp_property_import['schedules'])) {
          $schedules_list = $this->get_post_schedule_id();
          $schedules_list = array_reverse($schedules_list, true);
          $schedules_list[""] = '';
          $schedules_list = array_reverse($schedules_list, true);

          $schedules_array = array(
            'id' => 'wpp_import_schedule_id',
            'name' => __('Schedule', $this->get('domain')),
            'type' => 'select_advanced',
            'js_options' => array(
              'allowClear' => true,
            ),
            'options' => $schedules_list,
          );

          array_push($fields, $schedules_array);
        }

        $defined = array();
        foreach($fields as $field) {
          array_push( $defined, $field['id'] );
        }

        /** Add to filter Searchable Attributes */
        $attributes = ud_get_wp_property( 'property_stats', array() );
        $searchable_attributes = ud_get_wp_property( 'searchable_attributes', array() );
        $search_types = ud_get_wp_property( 'searchable_attr_fields', array() );
        $entry_types = ud_get_wp_property( 'admin_attr_fields', array() );
        $search_schema = ud_get_wp_property('attributes.searchable', array());

        foreach( $searchable_attributes as $attribute ) {
          /** Ignore current attribute if field with the same name already exists */
          if( in_array( $attribute, $defined ) ) {
            continue;
          }

          /**
           * Determine if type is searchable:
           *
           * Attribute must:
           * - have 'Data Entry'
           * - have 'Search Input'
           * - be searchable
           * - have valid 'Search Input'. See schema: ud_get_wp_property('attributes.searchable', array())
           */
          if( !isset( $attribute ) || !isset( $entry_types[ $attribute ] ) || empty( $entry_types[ $attribute ] ) ) {
            continue;
          }
          
          if( !isset( $search_schema[ $entry_types[ $attribute ] ] ) || empty( $search_schema[ $entry_types[ $attribute ] ] ) ) {
            continue;
          }

          if( !isset( $search_types[ $attribute ] ) || !isset( $entry_types[ $attribute ] ) || !in_array( $search_types[ $attribute ], $search_schema[ $entry_types[ $attribute ] ] ) ) {
            continue;
          }

          $type = $search_types[ $attribute ];
          $options = array();
          $map = array();

          /** Maybe Convert input types to valid ones and prepare options. */
          switch($type) {
            case 'input':
              $type = 'text';
              $map = array(
                'compare' => 'LIKE',
              );
              break;
            case 'range_input':
            case 'range_dropdown':
            case 'advanced_range_dropdown':
            case 'dropdown':
              $values = WPP_F::get_all_attribute_values( $attribute );
              $type = 'select_advanced';
              break;
            case 'multi_checkbox':
              $values = WPP_F::get_all_attribute_values( $attribute );
              $type = 'checkbox_list';
              break;
          }

          if( !empty( $values ) ) {
            $options = array( '' => '' );
            foreach( $values as $value ) {
              $options[$value] = $value;
            }
          }

          if ( 'range_date' == $type ) {
            array_push( $fields, array_filter( array(
                'id' => $attribute,
                'name' => $attributes[$attribute] . ' From',
                'type' => 'date',
                'js_options' => array(
                    'allowClear' => true,
                ),
                'options' => $options,
                'map' => array(
                    'class' => 'meta',
                    'compare' => 'BETWEEN'
                ),
            ) ) );
            array_push( $fields, array_filter( array(
                'id' => $attribute,
                'name' => $attributes[$attribute] . ' To',
                'type' => 'date',
                'js_options' => array(
                    'allowClear' => true,
                ),
                'options' => $options,
                'map' => array(
                    'class' => 'meta',
                    'compare' => 'BETWEEN'
                ),
            ) ) );
          } else {
            array_push( $fields, array_filter( array(
                'id' => $attribute,
                'name' => $attributes[$attribute],
                'type' => $type,
                'js_options' => array(
                    'allowClear' => true,
                ),
                'options' => $options,
                'map' => $map,
            ) ) );
          }
        }

        $fields = apply_filters( 'wpp::overview::filter::fields', $fields );

        return $fields;
      }

      /**
       * Add Meta Boxes to All Properties page.
       */
      public function add_meta_boxes() {
        $screen = get_current_screen();
        add_meta_box( 'posts_list', __('Overview',ud_get_wp_property('domain')), array($this, 'render_list_table'), $screen->id,'normal');
        add_meta_box( 'posts_filter', sprintf( __('%s Search',ud_get_wp_property('domain')), WPP_F::property_label('plural') ), array($this, 'render_filter'), $screen->id,'side');
      }

      /**
       * Render List Table in Overview Meta Box
       */
      public function render_list_table() {
		    do_action('wpp::above_list_table');
        $this->list_table->prepare_items();
        $this->list_table->display();
      }

      /**
       * Render Search Filter
       */
      public function render_filter() {
        $this->list_table->filter();
      }

      /**
       * Returns the list of schedules.
       *
       * @return array
       */
      public function get_post_schedule_id()
      {
        global $wpp_property_import;
        $schedules = array();
        if (!empty($wpp_property_import['schedules'])) {
          foreach ($wpp_property_import['schedules'] as $key => $schedule) {
            $schedules[$key] = mb_strimwidth($schedule['name'], 0, 35, '...');
          }
          return $schedules;
        } else {
          return array();
        }
      }

      /**
       * Returns the list of property statuses.
       *
       * @return array
       */
      public function get_post_statuses() {
        $all   = 0;
        $_attrs = WPP_F::get_all_attribute_values('post_status');
        $attrs = array();
        if( is_array( $_attrs ) ) {
          foreach( $_attrs as $attr ) {
            $count = WPP_F::get_properties_quantity( array( $attr ) );
            switch( $attr ) {
              case 'publish':
                $label = __( 'Published', $this->get('domain') );
                $all += $count;
                break;
              case 'pending':
                $label = __( 'Pending', $this->get('domain') );
                $all += $count;
                break;
              case 'trash':
                $label = __( 'Trashed', $this->get('domain') );
                break;
              case 'auto-draft':
                $label = __( 'Auto-Draft', $this->get('domain') );
                break;
              default:
                $label = strtoupper( substr( $attr, 0, 1 ) ) . substr( $attr, 1, strlen( $attr ) );
                $all += $count;
            }
            $attrs[ $attr ] = $label . ' (' . WPP_F::format_numeric( $count ) . ')';
          }
        } else {
          return array();
        }
        $attrs[ 'any' ] = __( 'Any', $this->get('domain') ) . ' (' . WPP_F::format_numeric( $all ) . ')';
        ksort( $attrs );
        $attrs = apply_filters('admin_overview_post_statuses', $attrs);
        return $attrs;
      }

      /**
       * Adds 'Add Property' button near page title
       *
       * @param $title
       * @return string
       */
      public function render_page_title( $title ) {
        $screen = get_current_screen();
        if( $screen->id == $this->page->screen_id ) {
          $post_type_object = get_post_type_object( 'property' );
          if ( current_user_can( $post_type_object->cap->create_posts ) ) {
            $title .= ' <a href="' . esc_url( admin_url( 'post-new.php?post_type=' . $post_type_object->name ) ) . '" class="add-new-h2">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
          }
        }
        return $title;
      }	  
    }
	
  }
}