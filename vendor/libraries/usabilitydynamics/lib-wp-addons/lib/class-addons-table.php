<?php
/**
 * Add-ons Table
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\WPA {

  if( !class_exists( 'UsabilityDynamics\WPA\Addons_Table' ) ) {

    if ( ! defined( 'ABSPATH' ) ) exit; //** Exit if accessed directly */

    if( ! class_exists( 'WP_List_Table' ) ) {
      require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }

    /**
     *
     * @author: palant@UD
     */
    class Addons_Table extends \WP_List_Table {

      public $data;
      public $found_data;
      public $name;
      public $domain;
      public $page;
      public $per_page = 100;

      /**
       * Constructor.
       * @since  1.0.0
       */
      public function __construct ( $args ) {
        global $status, $page;

        $this->name = !empty( $args[ 'name' ] ) ? $args[ 'name' ] : '';
        $this->domain = !empty( $args[ 'domain' ] ) ? $args[ 'domain' ] : false;
        $this->page = !empty( $args[ 'page' ] ) ? $args[ 'page' ] : false;

        $args = array(
          'singular'  => 'add-on',     //singular name of the listed records
          'plural'    => 'add-ons',   //plural name of the listed records
          'ajax'      => false        //does this table support ajax?
        );

        $this->data = array();

        //** Make sure this file is loaded, so we have access to plugins_api(), etc. */
        require_once( ABSPATH . '/wp-admin/includes/plugin-install.php' );

        parent::__construct( $args );
      }

      /**
       * Text to display if no items are present.
       *
       * @since  1.0.0
       * @return  void
       */
      public function no_items () {
        echo wpautop( sprintf( __( 'No active %s products found.', $this->domain ), $this->name ) );
      }

      /**
       * The content of each column.
       *
       * @param  array $item         The current item in the list.
       * @param  string $column_name The key of the current column.
       * @since  1.0.0
       * @return string              Output for the current column.
       */
      public function column_default ( $item, $column_name ) {
        switch( $column_name ) {
          case 'product':
          case 'product_status':
            break;
        }
      }

      /**
       * Retrieve an array of sortable columns.
       * @since  1.0.0
       * @return array
       */
      public function get_sortable_columns () {
        return array();
      }

      /**
       * Retrieve an array of columns for the list table.
       *
       * @since  1.0.0
       * @return array Key => Value pairs.
       */
      public function get_columns () {
        $columns = array(
          'product_name' => __( 'Add-on', $this->domain ),
          'product_status' => __( 'Status', $this->domain ),
          'deactivate_plugin' => __( 'Plugin', $this->domain ),
        );
        return $columns;
      }

      /**
       * Content for the "product_name" column.
       *
       * @param  array  $item The current item.
       * @since  1.0.0
       * @return string       The content of this column.
       */
      public function column_product_name ( $item ) {
        return wpautop( '<strong>' . $item['name'] . '</strong>' );
      }

      /**
       * Content for the "status" column.
       *
       * @param  array  $item The current item.
       * @since  1.0.0
       * @return string       The content of this column.
       */
      public function column_product_status ( $item ) {

        $response = '<ul>' . "\n";
        $response .= '<li><select name="products['.$item['slug'].']"><option value="0">'.esc_attr( __( 'Disabled', $this->domain ) ).'</option><option value="1" '.($item['active'] == 1 ? "selected" : "").'>'.esc_attr( __( 'Enabled', $this->domain ) ).'</option></select><li>' . "\n";
        $response .= '</ul>' . "\n";

        return $response;
      }

      /**
       * Deactivate plugin and use add-on
       *
       * @param  array  $item The current item.
       * @since  1.0.0
       * @return string       The content of this column.
       */
      public function column_deactivate_plugin ( $item ) {

        $response = '';
        if ( isset($item['plugin_deactivate_link']) ) {
          $response = __( sprintf('You have already installed plugin "%s". For correct work please %s plugin and enable add-on', $item['name'], sprintf("<a href='%s'>deactivate</a>", $item['plugin_deactivate_link'])) , $this->domain) . "\n";
        }

        return $response;
      }

      /**
       * Retrieve an array of possible bulk actions.
       *
       * @since  1.0.0
       * @return array
       */
      public function get_bulk_actions () {
        $actions = array();
        return $actions;
      }

      /**
       * Prepare an array of items to be listed.
       * @since  1.0.0
       * @return array Prepared items.
       */
      public function prepare_items () {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $total_items = count( $this->data );
        //** only ncessary because we have sample data */
        $this->found_data = $this->data;
        $this->set_pagination_args( array(
          'total_items' => $total_items, //WE have to calculate the total number of items
          'per_page'    => $total_items //WE have to determine how many items to show on a page
        ) );
        $this->items = $this->found_data;
      }

    }

  }

}
