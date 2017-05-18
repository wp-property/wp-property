<?php
/**
 * Children Properties List Table class.
 *
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Children_List_Table' ) ) {

    class Children_List_Table extends List_Table {

      /**
       * Constructor
       *
       * @param array $args
       */
      public function __construct( $args = array() ) {
        global $post;
        if( $post && !isset( $args[ 'query' ][ 'post_parent' ] ) ) {
          $args[ 'query' ][ 'post_parent' ] = $post->ID;
        }

        parent::__construct( $args );

        add_filter( 'page_row_actions', function( $actions, $post ){
          return array();
        }, 10, 2 );
      }

      /**
       * Children List Table Columns
       *
       * @return mixed|void
       */
      public function get_columns() {
        $columns = apply_filters( 'wpp::children_list_table::columns', array(
          'cb' => '<input type="checkbox" />',
          'title' => __( 'Title', ud_get_wp_property( 'domain' ) ),
          'status' => __( 'Status', ud_get_wp_property( 'domain' ) ),
          'overview' => __( 'Overview', ud_get_wp_property( 'domain' ) ),
          'featured' => __( 'Featured', ud_get_wp_property( 'domain' ) ),
          'thumbnail' => __( 'Thumbnail', ud_get_wp_property( 'domain' ) )
        ) );
        return $columns;
      }

      /**
       * Add Bulk Actions
       *
       * @return array
       */
      public function get_bulk_actions() {
        $actions = array(
          'unassign' => sprintf( __( 'Un-Assign from current %s', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ),
        );
        return apply_filters( 'wpp::children_list_table::bulk_actions', $actions );
      }

      /**
       * Handle Bulk Action's request
       *
       */
      public function process_bulk_action() {
        global $wpdb;

        try {

          switch( $this->current_action() ) {

            case 'unassign':
              if( empty( $_REQUEST[ 'post_ids' ] ) || !is_array( $_REQUEST[ 'post_ids' ] ) ) {
                throw new \Exception( sprintf( __( 'Invalid request: no %s IDs provided.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ) );
              }
              $unauthorized = 0;
              $post_ids = $_REQUEST[ 'post_ids' ];
              foreach( $post_ids as $post_id ) {
                $post_id = (int)$post_id;
                if( !$post_id ) {
                  throw new \Exception( sprintf( __( 'Invalid request: incorrect %s IDs provided.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ) );
                }

                if(!current_user_can('edit_wpp_property', $post_id)){
                  $unauthorized++;
                  continue;
                }

                $wpdb->query( $wpdb->prepare( "
                  UPDATE {$wpdb->posts}
                  SET post_parent = '0'
                  WHERE ID = %d
                ", $post_id ) );
                clean_post_cache( $post_id );
              }

              if( $unauthorized > 0 ) {
                $this->message = sprintf( __( 'You don\'t have permission to edit one or more selected %s.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() );
              }else{
                $label = count($post_ids) > 1 ? __( 'Children', ud_get_wp_property('domain') ) : __( 'Child', ud_get_wp_property('domain') );
                $this->message = sprintf( __( 'Selected %s have been successfully un-assigned from current %s.', ud_get_wp_property( 'domain' ) ), $label, \WPP_F::property_label() );
              }

              break;

            default:
              //** Any custom action can be processed using action hook */
              do_action( 'wpp::children_list_table::process_bulk_action', $this->current_action() );
              break;

          }

        } catch ( \Exception $e ) {
          $this->error = $e->getMessage();
        }

      }

      /**
       * Specific WP_Query arguments
       *
       * @param array $args
       * @return array
       */
      public function filter_wp_query( $args ) {
        return apply_filters( 'wpp::children_list_table::wp_query::args', $args );
      }

    }

  }

}
