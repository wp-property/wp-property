<?php

/**
 * Overview UI
 *
 * @author UsabilityDynamics, inc
 */

namespace UsabilityDynamics\WPP {

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

        /** Init Administration Menu */
        add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );

      }

      /**
       * Rentals Administration Menu
       */
      public function admin_menu() {
        global $wp_properties, $submenu;

        /* Add submenu page using already existing UI for overview page */
        $this->page = new \UsabilityDynamics\UI\Page( 'edit.php?post_type=property', $this->get( 'labels.all_items' ), $this->get( 'labels.all_items' ), 'edit_wpp_properties', 'all_properties' );

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
              if( !current_user_can( 'edit_wpp_property' ) ) {
                unset( $submenu[ 'edit.php?post_type=property' ][ $key ] );
              }
            }
          }
        }

        do_action( 'wpp_admin_menu' );
      }

      /**
       * Init our List Table before page loading
       */
      public function preload(){
        $this->list_table = new List_Table( array(
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
            'id' => 'post_status',
            'name' => __( 'Status', $this->get('domain') ),
            'type' => 'select_advanced',
            'js_options' => array(
              'allowClear' => false,
            ),
            'options' => $this->get_post_statuses(),
          ),
          array(
            'id' => 'property_type',
            'name' => sprintf( __( '%s Type', $this->get('domain') ), \WPP_F::property_label( 'plural' ) ),
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
        );

        return $fields;
      }

      /**
       * Add Meta Boxes to All Properties page.
       */
      public function add_meta_boxes() {
        $screen = get_current_screen();
        add_meta_box( 'posts_list', __('Overview',ud_get_wp_property('domain')), array($this, 'render_list_table'), $screen->id,'normal');
        add_meta_box( 'posts_filter', sprintf( __('%s Search',ud_get_wp_property('domain')), \WPP_F::property_label('plural') ), array($this, 'render_filter'), $screen->id,'side');
      }

      /**
       * Render List Table in Overview Meta Box
       */
      public function render_list_table() {
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
       * Returns the list of property statuses.
       *
       * @return array
       */
      public function get_post_statuses() {
        $all   = 0;
        $_attrs = \WPP_F::get_all_attribute_values('post_status');
        $attrs = array();
        if( is_array( $_attrs ) ) {
          foreach( $_attrs as $attr ) {
            $count = \WPP_F::get_properties_quantity( array( $attr ) );
            switch( $attr ) {
              case 'publish':
                $label = __( 'Published', $this->get('domain') );
                break;
              case 'pending':
                $label = __( 'Pending', $this->get('domain') );
                break;
              case 'trash':
                $label = __( 'Trashed', $this->get('domain') );
                break;
              default:
                $label = strtoupper( substr( $attr, 0, 1 ) ) . substr( $attr, 1, strlen( $attr ) );
            }
            $attrs[ $attr ] = $label . ' (' . \WPP_F::format_numeric( $count ) . ')';
            $all += $count;
          }
        } else {
          return array();
        }
        $attrs[ 'all' ] = __( 'All', $this->get('domain') ) . ' (' . \WPP_F::format_numeric( $all ) . ')';
        ksort( $attrs );
        return $attrs;
      }

      /**
       *
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