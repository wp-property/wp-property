<?php
/**
 * Property Search Widget
 */
class SearchPropertiesWidget extends WP_Widget {
  var $id = false;

  /** constructor */
  function SearchPropertiesWidget() {

    $property_label = strtolower( Utility::property_label() );

    parent::__construct(
      false,
      sprintf( __( '%1s Search', 'wpp' ), Utility::property_label() ),
      array(
        'classname' => 'wpp_property_attributes',
        'description' => sprintf( __( 'Display a highly customizable  %1s search form.', 'wpp' ), $property_label )
      ),
      array(
        'width' => 300
      )
    );

  }

  /** @see WP_Widget::widget */
  function widget( $args, $instance ) {

    global $wp_properties;
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    $widget_id = '';
    extract( $args );
    $title = apply_filters( 'widget_title', $instance[ 'title' ] );

    $instance = apply_filters( 'SearchPropertiesWidget', $instance );
    $search_attributes = $instance[ 'searchable_attributes' ];
    $sort_by = $instance[ 'sort_by' ];
    $sort_order = $instance[ 'sort_order' ];
    $searchable_property_types = $instance[ 'searchable_property_types' ];
    $grouped_searchable_attributes = $instance[ 'grouped_searchable_attributes' ];

    if ( !is_array( $search_attributes ) ) {
      return;
    }

    if ( !function_exists( 'draw_property_search_form' ) ) {
      return;
    }

    //** The current widget can be used on the page twice. So ID of the current DOM element (widget) has to be unique */
    /*
          Removed since this will cause problems with jQuery Tabs in Denali.
          $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);
        */

    echo $before_widget;

    echo '<div class="wpp_search_properties_widget">';

    if ( $title ) {
      echo $before_title . $title . $after_title;
    } else {
      echo '<span class="wpp_widget_no_title"></span>';
    }

    //** Load different attribute list depending on group selection */
    if ( $instance[ 'group_attributes' ] == 'true' ) {
      $search_args[ 'group_attributes' ] = true;
      $search_args[ 'search_attributes' ] = $instance[ 'grouped_searchable_attributes' ];
    } else {
      $search_args[ 'search_attributes' ] = $search_attributes;
    }

    //* Clean searchable attributes: remove unavailable ones */
    $all_searchable_attributes = array_unique( $wp_properties[ 'searchable_attributes' ] );
    foreach ( $search_args[ 'search_attributes' ] as $k => $v ) {
      if ( !in_array( $v, $all_searchable_attributes ) ) {
        //* Don't remove hardcoded attributes (property_type,city) */
        if ( $v != 'property_type' && $v != 'city' ) {
          unset( $search_args[ 'search_attributes' ][ $k ] );
        }
      }
    }

    $search_args[ 'searchable_property_types' ] = $searchable_property_types;

    if ( isset( $instance[ 'use_pagi' ] ) && $instance[ 'use_pagi' ] == 'on' ) {

      if ( empty( $instance[ 'per_page' ] ) ) {
        $instance[ 'per_page' ] = 10;
      }

      $search_args[ 'per_page' ] = $instance[ 'per_page' ];
      $search_args[ 'use_pagination' ] = 'on';
    } else {
      $search_args[ 'use_pagination' ] = 'off';
      $search_args[ 'per_page' ] = $instance[ 'per_page' ];
    }

    $search_args[ 'instance_id' ] = $widget_id;
    $search_args[ 'sort_by' ] = $sort_by;
    $search_args[ 'sort_order' ] = $sort_order;

    draw_property_search_form( $search_args );

    echo "<div class='cboth'></div></div>";

    echo $after_widget;
  }

  /** @see WP_Widget::update */
  function update( $new_instance, $old_instance ) {
    //Recache searchable values for search widget form
    $searchable_attributes = $new_instance[ 'searchable_attributes' ];
    $grouped_searchable_attributes = $new_instance[ 'grouped_searchable_attributes' ];
    $searchable_property_types = $new_instance[ 'searchable_property_types' ];
    $group_attributes = $new_instance[ 'group_attributes' ];


    if ( $group_attributes == 'true' ) {

      Utility::get_search_values( $grouped_searchable_attributes, $searchable_property_types, false, $this->id );
    } else {
      Utility::get_search_values( $searchable_attributes, $searchable_property_types, false, $this->id );
    }

    return $new_instance;
  }

  /**
   *
   * Renders back-end property search widget tools.
   *
   * @complexity 8
   * @author potanin@UD
   *
   */
  function form( $instance ) {
    global $wp_properties;

    //** Get widget-specific data */
    $title = ( $instance[ 'title' ] );
    $searchable_attributes = $instance[ 'searchable_attributes' ];
    $grouped_searchable_attributes = $instance[ 'grouped_searchable_attributes' ];
    $use_pagi = $instance[ 'use_pagi' ];
    $per_page = $instance[ 'per_page' ];
    $sort_by = $instance[ 'sort_by' ];
    $sort_order = $instance[ 'sort_order' ];
    $group_attributes = $instance[ 'group_attributes' ];
    $searchable_property_types = $instance[ 'searchable_property_types' ];
    $property_stats = $wp_properties[ 'property_stats' ];

    //** Get WPP data */
    $all_searchable_property_types = array_unique( $wp_properties[ 'searchable_property_types' ] );
    $all_searchable_attributes = array_unique( $wp_properties[ 'searchable_attributes' ] );
    $groups = $wp_properties[ 'property_groups' ];
    $main_stats_group = $wp_properties[ 'configuration' ][ 'main_stats_group' ];


    if ( !is_array( $all_searchable_property_types ) ) {
      $error[ 'no_searchable_types' ] = true;
    }

    if ( !is_array( $all_searchable_property_types ) ) {
      $error[ 'no_searchable_attributes' ] = true;
    }

    /** Set label for list below only */
    if ( !isset( $property_stats[ 'property_type' ] ) ) {
      $property_stats[ 'property_type' ] = sprintf(__( '%1s Type', 'wpp' ), Utility::property_label( 'singular' ) );
    }

    if ( is_array( $all_searchable_property_types ) && count( $all_searchable_property_types ) > 1 ) {

      //** Add property type to the beginning of the attribute list, even though it's not a typical attribute */
      array_unshift( $all_searchable_attributes, 'property_type' );
    }

    //** Find the difference between selected attributes and all attributes, i.e. unselected attributes */
    if ( is_array( $searchable_attributes ) && is_array( $all_searchable_attributes ) ) {
      $unselected_attributes = array_diff( $all_searchable_attributes, $searchable_attributes );

      //* Clean searchable attributes: remove unavailable ones */
      foreach ( $searchable_attributes as $k => $v ) {
        if ( !in_array( $v, $all_searchable_attributes ) ) {
          //* Don't remove hardcoded attributes (property_type,city) */
          if ( $v != 'property_type' && $v != 'city' ) {
            unset( $searchable_attributes[ $k ] );
          }
        }
      }

      // Build new array beginning with selected attributes, in order, follow by all other attributes
      $ungrouped_searchable_attributes = array_merge( $searchable_attributes, $unselected_attributes );

    } else {
      $ungrouped_searchable_attributes = $all_searchable_attributes;
    }

    $ungrouped_searchable_attributes = array_unique( $ungrouped_searchable_attributes );

    //* Perpare $all_searchable_attributes for using by sort function */
    $temp_attrs = array();

    foreach ( $all_searchable_attributes as $slug ) {
      $attribute_label = $property_stats[ 'property_stats' ][ $slug ];

      if ( empty( $attribute_label ) ) {
        $attribute_label = Utility::de_slug( $slug );
      }

      $temp_attrs[ $attribute_label ] = $slug;
    }

    //* Sort stats by groups */
    $stats_by_groups = sort_stats_by_groups( $temp_attrs );

    //** If the search widget cannot be created without some data, we bail */
    if ( $error ) {
      echo '<p>' . _e( 'No searchable property types were found.', 'wpp' ) . '</p>';
      return;
    }

    ?>

    <ul data-widget_number="<?php echo $this->number; ?>" data-widget="search_properties_widget"
      class="wpp_widget wpp_property_search_wrapper">

      <li class="<?php echo $this->get_field_id( 'title' ); ?>">
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wpp' ); ?>
          <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
            name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
        </label>
      </li>

      <li class="wpp_property_types">
        <p><?php _e( 'Property types to search:', 'wpp' ); ?></p>
        <ul>
          <?php foreach ( $all_searchable_property_types as $property_type ) { ?>
            <li>
              <label
                for="<?php echo $this->get_field_id( 'searchable_property_types' ); ?>_<?php echo $property_type; ?>">
                <input class="wpp_property_types"
                  id="<?php echo $this->get_field_id( 'searchable_property_types' ); ?>_<?php echo $property_type; ?>"
                  name="<?php echo $this->get_field_name( 'searchable_property_types' ); ?>[]"
                  type="checkbox" <?php if ( empty( $searchable_property_types ) ) {
                  echo 'checked="checked"';
                } ?>
                  value="<?php echo $property_type; ?>" <?php if ( is_array( $searchable_property_types ) && in_array( $property_type, $searchable_property_types ) ) {
                  echo " checked ";
                } ?> />
                <?php echo( !empty( $wp_properties[ 'property_types' ][ $property_type ] ) ? $wp_properties[ 'property_types' ][ $property_type ] : ucwords( $property_type ) ); ?>
              </label>
            </li>
          <?php } ?>
        </ul>
      </li>

      <li class="wpp_attribute_selection">
        <p><?php _e( 'Select the attributes you want to search.', 'wpp' ); ?></p>

        <div class="wpp_search_widget_tab wpp_subtle_tabs ">

          <ul class="wpp_section_tabs  tabs">
            <li><a href="#all_atributes_<?php echo $this->id; ?>"><?php _e( 'All Attributes', 'wpp' ); ?></a></li>

            <?php if ( $stats_by_groups ) { ?>
              <li><a href="#grouped_attributes_<?php echo $this->id; ?>"><?php _e( 'Grouped Attributes', 'wpp' ); ?></a>
              </li>
            <?php } ?>
          </ul>

          <div id="all_atributes_<?php echo $this->id; ?>" class="wp-tab-panel wpp_all_attributes">
            <ul class="wpp_sortable_attributes">
              <?php foreach ( $ungrouped_searchable_attributes as $attribute ) { ?>

                <li class="wpp_attribute_wrapper <?php echo $attribute; ?>">
                  <input id="<?php echo $this->get_field_id( 'searchable_attributes' ); ?>_<?php echo $attribute; ?>"
                    name="<?php echo $this->get_field_name( 'searchable_attributes' ); ?>[]"
                    type="checkbox" <?php if ( empty( $searchable_attributes ) ) {
                    echo 'checked="checked"';
                  } ?>
                    value="<?php echo $attribute; ?>" <?php echo( ( is_array( $searchable_attributes ) && in_array( $attribute, $searchable_attributes ) ) ? " checked " : "" ); ?> />
                  <label
                    for="<?php echo $this->get_field_id( 'searchable_attributes' ); ?>_<?php echo $attribute; ?>"><?php echo( !empty( $property_stats[ $attribute ] ) ? $property_stats[ $attribute ] : ucwords( $attribute ) ); ?></label>
                </li>
              <?php } ?>
            </ul>
          </div><?php /* end all (ungrouped) attribute selection */ ?>

          <?php if ( $stats_by_groups ) { ?>
            <div id="grouped_attributes_<?php echo $this->id; ?>" class="wpp_grouped_attributes_container wp-tab-panel">

              <?php foreach ( $stats_by_groups as $gslug => $gstats ) { ?>
                <?php if ( $main_stats_group != $gslug || !key_exists( $gslug, $groups ) ) { ?>
                  <?php $group_name = ( key_exists( $gslug, $groups ) ? $groups[ $gslug ][ 'name' ] : "<span style=\"color:#8C8989\">" . __( 'Ungrouped', 'wpp' ) . "</span>" ); ?>
                  <h2 class="wpp_stats_group"><?php echo $group_name; ?></h2>
                <?php } ?>
                <ul>
                  <?php foreach ( $gstats as $attribute ) { ?>
                    <li>
                      <input
                        id="<?php echo $this->get_field_id( 'grouped_searchable_attributes' ); ?>_<?php echo $attribute; ?>"
                        name="<?php echo $this->get_field_name( 'grouped_searchable_attributes' ); ?>[]"
                        type="checkbox" <?php if ( empty( $grouped_searchable_attributes ) ) {
                        echo 'checked="checked"';
                      } ?>
                        value="<?php echo $attribute; ?>" <?php echo( ( is_array( $grouped_searchable_attributes ) && in_array( $attribute, $grouped_searchable_attributes ) ) ? " checked " : "" ); ?> />
                      <label
                        for="<?php echo $this->get_field_id( 'grouped_searchable_attributes' ); ?>_<?php echo $attribute; ?>"><?php echo( !empty( $property_stats[ $attribute ] ) ? $property_stats[ $attribute ] : ucwords( $attribute ) ); ?></label>
                    </li>
                  <?php } ?>
                </ul>
              <?php } /* End cycle through $stats_by_groups */ ?>
            </div>
          <?php } ?>

        </div>

      </li>

      <li>

        <?php if ($stats_by_groups) { ?>
        <div>
          <input id="<?php echo $this->get_field_id( 'group_attributes' ); ?>" class="wpp_toggle_attribute_grouping"
            type="checkbox" value="true"
            name="<?php echo $this->get_field_name( 'group_attributes' ); ?>" <?php checked( $group_attributes, 'true' ); ?> />
          <label
            for="<?php echo $this->get_field_id( 'group_attributes' ); ?>"><?php _e( 'Group attributes together.', 'wpp' ); ?></label>
        </div>
      </li>
      <?php } ?>

      <li>

        <div class="wpp_something_advanced_wrapper" style="margin-top: 10px;">
          <ul>

            <?php if ( is_array( $wp_properties[ 'sortable_attributes' ] ) ) { ?>
              <li class="wpp_development_advanced_option">
                <div><label
                    for="<?php echo $this->get_field_id( 'sort_by' ); ?>"><?php _e( 'Default Sort Order', 'wpp' ); ?></label>
                </div>
                <select id="<?php echo $this->get_field_id( 'sort_by' ); ?>"
                  name="<?php echo $this->get_field_name( 'sort_by' ); ?>">
                  <option></option>
                  <?php foreach ( $wp_properties[ 'sortable_attributes' ] as $attribute ) { ?>
                    <option
                      value="<?php echo esc_attr( $attribute ); ?>"  <?php selected( $sort_by, $attribute ); ?> ><?php echo $property_stats[ $attribute ]; ?></option>
                  <?php } ?>
                </select>

                <select id="<?php echo $this->get_field_id( 'sort_order' ); ?>"
                  name="<?php echo $this->get_field_name( 'sort_order' ); ?>">
                  <option></option>
                  <option value="DESC"  <?php selected( $sort_order, 'DESC' ); ?> ><?php _e( 'Descending', 'wpp' ); ?></option>
                  <option value="ASC"  <?php selected( $sort_order, 'ASC' ); ?> ><?php _e( 'Acending', 'wpp' ); ?></option>
                </select>

              </li>
            <?php } ?>
            <li class="wpp_development_advanced_option">
              <label for="<?php echo $this->get_field_id( 'use_pagi' ); ?>">
                <input id="<?php echo $this->get_field_id( 'use_pagi' ); ?>"
                  name="<?php echo $this->get_field_name( 'use_pagi' ); ?>" type="checkbox"
                  value="on" <?php if ( $use_pagi == 'on' ) echo " checked='checked';"; ?> />
                <?php _e( 'Use pagination', 'wpp' ); ?>
              </label>
            </li>

            <li class="wpp_development_advanced_option">
              <label for="<?php echo $this->get_field_id( 'per_page' ); ?>"><?php _e( 'Items per page', 'wpp' ); ?>
                <input style="width:30px" id="<?php echo $this->get_field_id( 'per_page' ); ?>"
                  name="<?php echo $this->get_field_name( 'per_page' ); ?>" type="text"
                  value="<?php echo $per_page; ?>"/>
              </label>
            </li>

            <li>
              <span class="wpp_show_advanced"><?php _e( 'Toggle Advanced Search Options', 'wpp' ); ?></span>
            </li>
          </ul>
        </div>
      </li>
    </ul>
  <?php

  }

}