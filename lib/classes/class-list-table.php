<?php
/**
 * Properties List Table class.
 *
 */
namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\List_Table')) {

    class List_Table extends \UsabilityDynamics\WPLT\WP_List_Table {

      /**
       * @param array $args
       */
      public function __construct( $args = array() ) {

        $this->args = wp_parse_args( $args, array(
          //singular name of the listed records
          'singular' => \WPP_F::property_label(),
          //plural name of the listed records
          'plural' => \WPP_F::property_label( 'plural' ),
          // Post Type
          'post_type' => 'property',
          'post_status' => 'all',
        ) );

        //Set parent defaults
        parent::__construct( $this->args );

        add_filter( 'wplt_column_title_label', array( $this, 'get_column_title_label' ), 10, 2 );

      }

      public function get_columns() {
        return array(
          'cb' => '<input type="checkbox" />',
          'title' => __( 'Title', ud_get_wp_sms_rentals()->domain ),
        );
      }

      /**
       * Sortable columns
       *
       * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
       */
      public function get_sortable_columns() {
        return array(
          'title'	 	=> array( 'title', true ),	//true means it's already sorted
        );
      }

      /**
       * Returns label for Title Column
       */
      public function get_column_title_label( $title, $post ) {
        $title = get_the_title( $post );
        if ( empty( $title ) )
          $title = __( '(no name)' );
        return $title;
      }

    }

  }

}
