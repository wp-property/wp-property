<?php

/**
 * Shortcode: [property_overview]
 *
 * @since 2.0.5
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Overview_Shortcode' ) ) {

    class Property_Overview_Shortcode extends Shortcode {

      /**
       * Init
       */
      public function __construct() {

        $custom_attributes = ud_get_wp_property( 'property_stats', array() );

        $options = array(
            'id' => 'property_overview',
            'params' => array(
              'property_id' => array(
                'name' => __( 'Property ID', ud_get_wp_property()->domain ),
                'description' => __( 'If not empty, result will show particular property, which ID is set.', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => ''
              ),
              'post_parent' => array(
                'name' => sprintf( __( 'Parent %s', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ),
                'description' => sprintf( __( 'If not empty, result will show children of particular property, which ID is set. You can use dynamic attributes instead of ID such as %1$s or %2$s.<br/>%1$s - to list all the listings that are a child of the current %3$s.<br/>%2$s - to list all listings that are children of the same parent (i.e. siblings) of the current %3$s', ud_get_wp_property( 'domain' ) ), '<b>post_id</b>', '<b>post_parent</b>', \WPP_F::property_label() ),
                'type' => 'text',
                'default' => ''
              ),
              'property_type' => array(
                'name' => sprintf( __( '%s Type', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ),
                'description' => sprintf( __( 'The list of %s types to be included. If no type checked, all available %s will be shown.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label(), \WPP_F::property_label( 'plural' ) ),
                'type' => 'multi_checkbox',
                'options' => ud_get_wp_property( 'property_types' ),
              ),
              'custom_query' => array(
                'name' => __( 'Custom Query by Attributes Values', ud_get_wp_property()->domain ),
                'description' => sprintf( __( 'Setup your custom query by providing values for specific attributes. Empty values will be ignored. Example:<br/>- to list only %1$s which have minimum 2 and maximum 4 bedrooms, you should set <b>2-4</b> value for your Bedrooms attribute.<br/>- to list only %1$s which have 1 or 3 bathrooms, you should set <b>1,3</b> value for your Batrooms attribute.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ),
                'type' => 'custom_attributes',
                'options' => $custom_attributes,
              ),
              'show_children' => array(
                'name' => __( 'Show Children', ud_get_wp_property()->domain ),
                'description' => __( 'Switches children property displaying.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'true'
              ),
              'child_properties_title' => array(
                'name' => __( 'Child Properties Title', ud_get_wp_property()->domain ),
                'description' => __( 'Title for child properties section.', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => __( 'Floor plans at location:', ud_get_wp_property()->domain )
              ),
              'fancybox_preview' => array(
                'name' => __( 'Fancybox Preview', ud_get_wp_property()->domain ),
                'description' => __( 'Use fancybox preview.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'true'
              ),
              'bottom_pagination_flag' => array(
                'name' => __( 'Bottom Pagination', ud_get_wp_property()->domain ),
                'description' => __( 'Show Bottom Pagination.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              ),
              'thumbnail_size' => array(
                'name' => __( 'Thumbnail Size', ud_get_wp_property()->domain ),
                'description' => sprintf( __( 'Thumbnail Size. E.g.: %s', ud_get_wp_property()->domain ), "'thumbnail', ''medium', 'large'" ),
                'type' => 'text',
                'default' => ''
              ),
              'sort_by_text' => array(
                'name' => __( 'Sort By Text', ud_get_wp_property()->domain ),
                'description' => __( 'Renames "Sort By:" text.', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => __( 'Sort By', ud_get_wp_property()->domain )
              ),
              'sort_by' => array(
                'name' => __( 'Sort By', ud_get_wp_property()->domain ),
                'description' => sprintf( __( 'Sets sorting by attribute or %s', ud_get_wp_property()->domain ), 'post_date, menu_order', 'ID' ),
                'type' => 'text',
                'default' => 'post_date'
              ),
              'sort_order' => array(
                'name' => __( 'Sort Order', ud_get_wp_property()->domain ),
                'description' => __( 'Sort Order', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'DESC' => 'DESC',
                  'ASC'  => 'ASC'
                ),
                'default' => 'DESC'
              ),
              'template' => array(
                'name' => __( 'Template', ud_get_wp_property()->domain ),
                'description' => sprintf( __( 'Sets layout using PHP template name. Your custom template should be stored in your theme\'s root directory. Example:<br/>if your custom template is called %s, the value of template must be %s.', ud_get_wp_property( 'domain' ) ), '<b>property-overview-grid.php</b>', '<b>grid</b>' ),
                'type' => 'text',
              ),
              'sorter_type' => array(
                'name' => __( 'Sorter Type', ud_get_wp_property()->domain ),
                'description' => __( 'Sorter Type', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'none' => __( 'None', ud_get_wp_property()->domain ),
                  'buttons'  => __( 'Buttons', ud_get_wp_property()->domain ),
                  'dropdown'  => __( 'Dropdown', ud_get_wp_property()->domain )
                ),
                'default' => 'buttons'
              ),
              'sorter' => array(
                'name' => __( 'Sorter', ud_get_wp_property()->domain ),
                'description' => __( 'Show Sort UI', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'on' => __( 'On', ud_get_wp_property()->domain ),
                  'off'  => __( 'Off', ud_get_wp_property()->domain )
                ),
                'default' => 'on'
              ),
              'pagination' => array(
                'name' => __( 'Pagination', ud_get_wp_property()->domain ),
                'description' => __( 'Show Pagination', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'on' => __( 'On', ud_get_wp_property()->domain ),
                  'off'  => __( 'Off', ud_get_wp_property()->domain )
                ),
                'default' => 'on'
              ),
              'per_page' => array(
                'name' => __( 'Per Page', ud_get_wp_property()->domain ),
                'description' => __( 'Property quantity per page.', ud_get_wp_property()->domain ),
                'type' => 'number',
                'default' => 10
              ),
              'starting_row' => array(
                'name' => __( 'Starting Row', ud_get_wp_property()->domain ),
                'description' => __( 'Sets starting row.', ud_get_wp_property()->domain ),
                'type' => 'number',
                'default' => 0
              ),
              'detail_button' => array(
                'name' => __( 'Detail Button', ud_get_wp_property()->domain ),
                'description' => __( 'Name of Detail Button. Button will not be shown if the value is empty.', ud_get_wp_property()->domain ),
                'type' => 'text',
              ),
              'hide_count' => array(
                'name' => __( 'Hide Count', ud_get_wp_property()->domain ),
                'description' => __( 'Hide the “10 found.” text.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false'  => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              ),
              'in_new_window' => array(
                'name' => __( 'In new window?', ud_get_wp_property()->domain ),
                'description' => __( 'Open links in new window.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false'  => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              ),
              'strict_search' => array(
                'name' => __( 'Strict Search', ud_get_wp_property()->domain ),
                'description' => __( 'Provides strict search', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              ),
            ),
            'description' => __( 'Renders Property Attributes', ud_get_wp_property()->domain ),
            'group' => 'WP-Property'
        );

        parent::__construct( $options );
      }

      /**
       * @param string $atts
       * @return string|void
       */
      public function call( $atts = "" ) {
        global $wp_properties;

        $data = wp_parse_args( $atts, array(
          'strict_search' => false,
          'show_children' => ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] : 'true' ),
          'child_properties_title' => __( 'Floor plans at location:', ud_get_wp_property()->domain ),
          'fancybox_preview' => ud_get_wp_property( 'configuration.property_overview.fancybox_preview' ),
          'bottom_pagination_flag' => ( isset( $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] ) && $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] == 'true' ? true : false ),
          'thumbnail_size' => ud_get_wp_property( 'configuration.property_overview.thumbnail_size', 'thumbnail' ),
          'sort_by_text' => __( 'Sort By:', ud_get_wp_property()->domain ),
          'sort_by' => 'post_date',
          'sort_order' => 'DESC',
          'template' => false,
          'disable_wrapper' => false,
          'ajax_call' => false,
          'sorter_type' => 'buttons',
          'sorter' => 'on',
          'pagination' => 'on',
          'hide_count' => false,
          'per_page' => 10,
          'starting_row' => 0,
          'unique_hash' => rand( 10000, 99900 ),
          'detail_button' => false,
          'class' => 'wpp_property_overview_shortcode',
          'in_new_window' => false
        ) );

        /* Fix boolean values */
        $boolean_values_map = array(
          'strict_search',
          'template',
          'disable_wrapper',
          'ajax_call',
          'hide_count',
          'detail_button',
          'in_new_window'
        );
        foreach( $data as $k => $v ) {
          if( in_array( $k, $boolean_values_map ) && ( $v === 'false' || empty( $v ) ) ) {
            $data[$k] = false;
          }
        }

        if( !empty( $data[ 'custom_query' ] ) && is_array( $data[ 'custom_query' ] ) ) {
          foreach( $data[ 'custom_query' ] as $k => $v ) {

          }
          unset( $data[ 'custom_query' ] );
        }

        /*
        echo "<pre>";
        print_r( $data );
        echo "</pre>";
        die();
        */

        return \WPP_Core::shortcode_property_overview( $data );

      }

    }

    /**
     * Register
     */
    new Property_Overview_Shortcode();

  }

}