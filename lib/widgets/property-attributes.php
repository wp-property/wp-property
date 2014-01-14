<?php
/**
 * Name: Property Attributes
 * ID: Property_Attributes_Widget
 * Type: widget
 * Group: WP-Property
 * Class: \UsabilityDynamics\WPP\Property_Attributes_Widget
 * Version: 2.0.0
 * Description: Display a list of selected properties attributes when loaded on a single property page
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Attributes_Widget' ) ) {
    /**
     * Property Attributes widget.
     *
     * @author potanin@UD
     * @since 1.32.0
     */
    class Property_Attributes_Widget extends Widget {

      /**
       * Initialize the widget.
       *
       * @since 1.31.0
       */
      function __construct() {
      
        parent::__construct(
          'wpp_property_attributes',
          sprintf( __( '%1s Attributes', 'wpp' ), Utility::property_label() ),
          array(
            'classname'   => 'wpp_property_attributes',
            'description' => sprintf( __( 'Display a list of selected %1$s attributes when loaded on a single %1$s page.', 'wpp' ), Utility::property_label() )
          ),
          array(
            'width' => 300
          )
        );
        
      }

      /**
       * Handles any special functions when the widget is being updated.
       *
       * @since 1.31.0
       */
      function update( $new_instance ) {
        return $new_instance;
      }

      /**
       * Renders the widget on the front-end.
       *
       * @since 1.31.0
       */
      function widget( $args, $instance ) {
        $before_widget = '';
        $before_title  = '';
        $after_title   = '';
        $after_widget  = '';
        extract( $args );

        if( empty( $instance ) ) {
          return;
        }

        $html[ ] = $before_widget;

        if( $instance[ 'title' ] ) {
          $html[ ] = $before_title . $instance[ 'title' ] . $after_title;
        }

        if( $instance[ 'show_labels' ] == 'true' ) {
          $show_labels = true;
        }

        unset( $instance[ 'show_labels' ] );
        unset( $instance[ 'title' ] );

        foreach( $instance as $slug => $option ) {

          if( $option != 'true' ) {
            continue;
          }

          $value = get_attribute( $slug, array( 'return' => 'true' ) );

          if( empty( $value ) ) {
            continue;
          }

          if( $show_labels ) {
            $attribute = Utility::get_attribute_data( $slug );
          }

          $attributes[ ] = '<li class="' . $slug . '">' . ( $show_labels ? '<span class="attribute">' . $attribute[ 'label' ] . '<span class="separator">:</span> </span>' : '' ) . '</span><span class="value">' . $value . '</span></li>';
        }

        if( !empty( $attributes ) ) {
          $html[ 'attributes' ] = '<ul class="wpp_widget_attribute_list">' . implode( '', $attributes ) . '</ul>';
        }

        $html[ ] = $after_widget;

        if( !empty( $html[ 'attributes' ] ) ) {
          echo implode( '', $html );
        } else {
          return false;
        }

      }

      /**
       * Renders widget UI in control panel.
       *
       *
       * @todo Needs to make use of sortable attributes.
       * @uses $current_screen global variable
       * @since 1.31.0
       */
      function form( $instance ) {
        global $wp_properties;

        $main_options   = array();
        $widget_options = ( is_array( $instance ) ? $instance : array() );

        foreach( Utility::get_total_attribute_array() as $slug => $label ) {
          $main_options[ $slug ] = Utility::get_attribute_data( $slug );
        }

        //** We don't want to mix in the title into our array */
        unset( $widget_options[ 'title' ] );
        unset( $widget_options[ 'show_labels' ] );

        //** Cycle through saved attributes, to maintain order, and remove any that are no longer in main list */
        foreach( $widget_options as $slug => $selected ) {
          if( !in_array( $slug, array_keys( $main_options ) ) ) {
            unset( $widget_options[ $slug ] );
          } else {
            $widget_options[ $slug ] = $main_options[ $slug ];
          }
        }

        //** Cycle through main options, and add any to array that are not already there */
        foreach( $main_options as $slug => $data ) {
          if( !in_array( $slug, array_keys( $widget_options ) ) ) {
            $widget_options[ $slug ] = $data;
          }
        }

        ?>
        <div class="wpp_widget" data-widget="property_attributes_widget" data-widget_number="<?php echo $this->number; ?>">
          <p>
            <label for="<?php echo $this->get_field_id( 'widget_title' ); ?>"><?php _e( 'Title:', 'wpp' ); ?>
              <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                value="<?php echo esc_attr( $instance[ 'title' ] ); ?>"/></label>
          </p>

          <p><?php _e( 'Attributes to Display:', 'wpp' ); ?></p>

          <div class="wpp_subtle_tabs wp-tab-panel">
            <ul class="wpp_sortable_attributes">
              <?php foreach( $widget_options as $slug => $data ) { ?>
                <li class="wpp_attribute_wrapper">
                  <input type="hidden" name="<?php echo $this->get_field_name( $slug ); ?>" value="false"/>
                  <input class="checkbox" type="checkbox" <?php checked( $instance[ $slug ], 'true' ) ?>
                    id="<?php echo $this->get_field_id( $slug ); ?>"
                    name="<?php echo $this->get_field_name( $slug ); ?>" value="true"/>
                  <label for="<?php echo $this->get_field_id( $slug ); ?>"><?php echo $data[ 'label' ]; ?></label>
                </li>
              <?php } ?>
            </ul>
          </div>
          <ul>
            <li>
              <input class="checkbox" type="checkbox" <?php checked( $instance[ 'show_labels' ], 'true' ) ?>
                id="<?php echo $this->get_field_id( 'show_labels' ); ?>"
                name="<?php echo $this->get_field_name( 'show_labels' ); ?>" value="true"/>
              <label
                for="<?php echo $this->get_field_id( 'show_labels' ); ?>"><?php _e( 'Display attributes labels.', 'wpp' ); ?></label>
            </li>
          </ul>
        </div>
        <?php
      }
  
  }

} 



}