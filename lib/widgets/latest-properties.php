<?php

/**
 * Class LatestPropertiesWidget
 *
 * Deprecated Widget. Enable Legacy Features option on Settings page to activate it.
 */
class LatestPropertiesWidget extends WP_Widget {

  /**
   * constructor
   */
  function __construct() {
    parent::__construct( false, $name = sprintf( __( 'Latest %1s', ud_get_wp_property()->domain ), WPP_F::property_label( 'plural' ) ), array( 'description' => __( 'List of the latest properties created on this site', ud_get_wp_property()->domain ) ) );
  }

  /**
   * @see WP_Widget::widget
   */
  function widget( $args, $instance ) {
    global $wp_properties;
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    extract( $args );

    $instance = apply_filters( 'LatestPropertiesWidget', $instance );
    $title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
    $stats = isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : array();
    $image_type = !empty( $instance[ 'image_type' ] ) ? $instance[ 'image_type' ] : '';
    $hide_image = isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false;
    $show_title = isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false;
    $address_format = isset( $instance[ 'address_format' ] ) ? $instance[ 'address_format' ] : '';
    $property_stats = isset( $wp_properties[ 'property_stats' ] ) ? $wp_properties[ 'property_stats' ] : array();

    if ( !empty( $image_type ) ) {
      $image_size = WPP_F::image_sizes( $image_type );
    }

    if ( !isset( $property_stats[ 'property_type' ] ) ) {
      $property_stats[ 'property_type' ] = sprintf( __( '%s Type', ud_get_wp_property()->domain ), WPP_F::property_label() );
    }

    $arg = array(
        'post_type' => 'property',
        'numberposts' => ( !empty( $instance[ 'amount_items' ] ) ? $instance[ 'amount_items' ] : 5 ),
        'post_status' => 'publish',
        'post_parent' => null, // any parent
        'order' => 'DESC',
        'orderby' => 'post_date',
        'suppress_filters' => 0
    );

    $postslist = get_posts( $arg );

    echo $before_widget;
    echo "<div class='wpp_latest_properties_widget'>";

    if ( $title ) {
      echo $before_title . $title . $after_title;
    }

    echo !WPP_LEGACY_WIDGETS ? '<div class="wpp_latest_properties_widget_wrapper">' : '';

    foreach ( $postslist as $post ) {
      $this_property = WPP_F::get_property( $post->ID, 'return_object=true' );
      $image = isset( $this_property->featured_image ) ? wpp_get_image_link( $this_property->featured_image, $image_type, array( 'return' => 'array' ) ) : false;
      $width = ( !empty( $image_size[ 'width' ] ) ? $image_size[ 'width' ] : ( !empty( $image[ 'width' ] ) ? $image[ 'width' ] : '' ) );
      $height = ( !empty( $image_size[ 'height' ] ) ? $image_size[ 'height' ] : ( !empty( $image[ 'height' ] ) ? $image[ 'height' ] : '' ) );
      ?>
      <div class="property_widget_block latest_entry clearfix"
        <?php if (WPP_LEGACY_WIDGETS) { ?>
          style="<?php echo($width ? 'width: ' . ($width + 5) . 'px;' : ''); ?>"
        <?php } ?>
      >
        <?php if ( $hide_image !== 'on' ) { ?>
          <?php if ( !empty( $image ) ) : ?>
            <a class="sidebar_property_thumbnail latest_property_thumbnail thumbnail"
               href="<?php echo $this_property->permalink; ?>">
              <img width="<?php echo $image[ 'width' ]; ?>" height="<?php echo $image[ 'height' ]; ?>"
                   src="<?php echo $image[ 'url' ]; ?>"
                   alt="<?php echo sprintf( __( '%s at %s for %s', ud_get_wp_property()->domain ), $this_property->post_title, $this_property->location, $this_property->price ); ?>"/>
            </a>
          <?php else : ?>
            <div class="wpp_no_image" style="width:<?php echo $width; ?>px;height:<?php echo $height; ?>px;"></div>
          <?php endif; ?>
        <?php
        }
        if ( $show_title == 'on' ) {
          echo '<p class="title"><a href="' . $this_property->permalink . '">' . $post->post_title . '</a></p>';
        }

        echo '<ul class="wpp_widget_attribute_list">';
        if ( is_array( $stats ) ) {
          foreach ( $stats as $stat ) {
            if( !isset( $this_property->$stat ) ) {
              continue;
            }
            switch( true ) {
              case ( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) && $wp_properties[ 'configuration' ][ 'address_attribute' ] == $stat ):
                $content = wpp_format_address_attribute( $this_property->$stat, $this_property, $address_format );
                break;
              case ( $stat == 'property_type' ):
                $content = nl2br( apply_filters( "wpp_stat_filter_property_type_label", $this_property->property_type_label ) );
                break;
              case ( !empty($wp_properties["predefined_values"][$stat]) ):
                $content = nl2br( apply_filters( "wpp_stat_filter_{$stat}",apply_filters( "wpp::attribute::value", $this_property->$stat, $stat ) ) );
                break;
              default:
                $content = nl2br( apply_filters( "wpp_stat_filter_{$stat}", $this_property->$stat ) );
                break;
            }
            if ( empty( $content ) ) {
              continue;
            }
            echo '<li class="' . $stat . '"><span class="attribute">' . apply_filters('wpp::attribute::label',$property_stats[ $stat ],$stat) . ':</span> <span class="value">' . $content . '</span></li>';
          }
        }
        echo '</ul>';

        if ( isset( $instance[ 'enable_more' ] ) && $instance[ 'enable_more' ] == 'on' ) {
          echo '<p class="more"><a href="' . $this_property->permalink . '" class="btn btn-info">' . __( 'More', ud_get_wp_property()->domain ) . '</a></p>';
        }
        ?>
      </div>
      <?php
      unset( $this_property );
    }

    if ( isset( $instance[ 'enable_view_all' ] ) && $instance[ 'enable_view_all' ] == 'on' ) {
      echo '<p class="view-all"><a href="' . site_url() . '/' . $wp_properties[ 'configuration' ][ 'base_slug' ] . '" class="btn btn-large">' . __( 'View All', ud_get_wp_property()->domain ) . '</a></p>';
    }
    echo '<div class="clear"></div>';
    echo !WPP_LEGACY_WIDGETS ? '</div>' : '';
    echo '</div>';
    echo $after_widget;
  }

  /**
   * @see WP_Widget::update
   */
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  /**
   * @see WP_Widget::form
   */
  function form( $instance ) {
    global $wp_properties;

    $title = isset( $instance[ 'title' ] ) ? esc_attr( $instance[ 'title' ] ) : '';
    $amount_items = isset( $instance[ 'amount_items' ] ) ? esc_attr( $instance[ 'amount_items' ] ) : false;
    $address_format = !empty( $instance[ 'address_format' ] ) ? $instance[ 'address_format' ] : "[street_number] [street_name],[city], [state]";
    $instance_stats = isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : array();
    $image_type = isset( $instance[ 'image_type' ] ) ? esc_attr( $instance[ 'image_type' ] ) : false;
    $hide_image = isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false;
    $show_title = isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false;
    $enable_more = isset( $instance[ 'enable_more' ] ) ? $instance[ 'enable_more' ] : false;
    $enable_view_all = isset( $instance[ 'enable_view_all' ] ) ? $instance[ 'enable_view_all' ] : false;
    $property_stats = isset( $wp_properties[ 'property_stats' ] ) ? $wp_properties[ 'property_stats' ] : array();

    if ( !isset( $property_stats[ 'property_type' ] ) ) {
      $property_stats[ 'property_type' ] = sprintf( __( '%s Type', ud_get_wp_property()->domain ), WPP_F::property_label() );
    }
    ?>
    <script type="text/javascript">
      //hide and show dropdown whith thumb settings
      jQuery( document ).ready( function ( $ ) {
        jQuery( 'input.check_me' ).change( function () {
          if ( $( this ).attr( 'checked' ) !== true ) {
            jQuery( 'p#choose_thumb' ).css( 'display', 'block' );
          } else {
            jQuery( 'p#choose_thumb' ).css( 'display', 'none' );
          }
        } );
      } );
    </script>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', ud_get_wp_property()->domain ); ?>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
               name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
               value="<?php echo ( !empty( $title ) ) ? $title : __( 'Latest Properties', ud_get_wp_property()->domain ); ?>"/>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'hide_image' ); ?>">
        <input id="<?php echo $this->get_field_id( 'hide_image' ); ?>" class="check_me"
               name="<?php echo $this->get_field_name( 'hide_image' ); ?>" type="checkbox"
               value="on" <?php if ( $hide_image == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Hide Images?', ud_get_wp_property()->domain ); ?>
      </label>
    </p>
    <p id="choose_thumb" <?php echo( $hide_image !== 'on' ? 'style="display:block;"' : 'style="display:none;"' ); ?>>
      <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Image Size:', ud_get_wp_property()->domain ); ?>
        <?php WPP_F::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'per_page' ); ?>"><?php _e( 'Listings to display?', ud_get_wp_property()->domain ); ?>
        <input style="width:30px" id="<?php echo $this->get_field_id( 'amount_items' ); ?>"
               name="<?php echo $this->get_field_name( 'amount_items' ); ?>" type="text"
               value="<?php echo ( empty( $amount_items ) ) ? 5 : $amount_items; ?>"/>
      </label>
    </p>
    <p><?php _e( 'Select stats you want to display', ud_get_wp_property()->domain ) ?></p>
    <p>
      <label for="<?php echo $this->get_field_id( 'show_title' ); ?>">
        <input id="<?php echo $this->get_field_id( 'show_title' ); ?>"
               name="<?php echo $this->get_field_name( 'show_title' ); ?>" type="checkbox"
               value="on" <?php if ( $show_title == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Title', ud_get_wp_property()->domain ); ?>
      </label>
      <?php foreach ( $property_stats as $stat => $label ): ?>
        <br/>
        <label for="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>">
          <input id="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>"
                 name="<?php echo $this->get_field_name( 'stats' ); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
          <?php if ( is_array( $instance_stats ) && in_array( $stat, $instance_stats ) ) echo " checked "; ?>">
          <?php echo $label; ?>
        </label>
      <?php endforeach; ?>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'address_format' ); ?>"><?php _e( 'Address Format:', ud_get_wp_property()->domain ); ?>
        <textarea style="width: 100%" id="<?php echo $this->get_field_id( 'address_format' ); ?>"
                  name="<?php echo $this->get_field_name( 'address_format' ); ?>"><?php echo $address_format; ?></textarea>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'enable_more' ); ?>">
        <input id="<?php echo $this->get_field_id( 'enable_more' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_more' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_more == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Show "More" link?', ud_get_wp_property()->domain ); ?>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'enable_view_all' ); ?>">
        <input id="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_view_all' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_view_all == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Show "View All" link?', ud_get_wp_property()->domain ); ?>
      </label>
    </p>
  <?php
  }

}

/**
 * Register widget
 */
add_action( 'widgets_init', function() {
  if( ud_get_wp_property( 'configuration.enable_legacy_features' ) == 'true' ) {
    register_widget("LatestPropertiesWidget");
  }
});