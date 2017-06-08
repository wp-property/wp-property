<?php
/**
 * Template Child Properties Widget
 *
 *
 */

global $wp_properties;

echo "<div class='wpp_child_properties_widget'>";

if ( $title ) {
  echo $before_title . $title . $after_title;
}

foreach ( $properties as $property ) {
  $this_property = WPP_F::get_property( $property->ID, 'return_object=true' );
  $image = isset( $this_property->featured_image ) ? wpp_get_image_link( $this_property->featured_image, $image_type, array( 'return' => 'array' ) ) : false;
  $width = ( !empty( $image_size[ 'width' ] ) ? $image_size[ 'width' ] : ( !empty( $image[ 'width' ] ) ? $image[ 'width' ] : '' ) );
  $height = ( !empty( $image_size[ 'height' ] ) ? $image_size[ 'height' ] : ( !empty( $image[ 'height' ] ) ? $image[ 'height' ] : '' ) );
  ?>
  <div class="property_widget_block apartment_entry clearfix"
       style="<?php echo( $width ? 'width: ' . ( $width + 5 ) . 'px;' : '' ); ?>">
    <?php if ( $hide_image !== 'on' ) : ?>
      <?php if ( !empty( $image ) ): ?>
        <a class="sidebar_property_thumbnail thumbnail" href="<?php echo $this_property->permalink; ?>">
          <img width="<?php echo $image[ 'width' ]; ?>" height="<?php echo $image[ 'height' ]; ?>"
               src="<?php echo $image[ 'link' ]; ?>"
               alt="<?php echo sprintf( __( '%s at %s for %s', ud_get_wp_property()->domain ), $this_property->post_title, $this_property->location, $this_property->price ); ?>"/>
        </a>
      <?php else: ?>
        <div class="wpp_no_image" style="width:<?php echo $width; ?>px;height:<?php echo $height; ?>px;"></div>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ( $show_title == 'on' ): ?>
      <p class="title"><a
          href="<?php echo $this_property->permalink; ?>"><?php echo $this_property->post_title; ?></a></p>
    <?php endif; ?>
    <ul class="wpp_widget_attribute_list">
      <?php if ( is_array( $stats ) ): ?>
        <?php foreach ( $stats as $stat ): ?>
          <?php
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
              $content = apply_filters( "wpp_stat_filter_{$stat}",apply_filters( "wpp::attribute::value", $this_property->$stat, $stat ) );
              $content = ( is_array($content) )? implode(', ', $content): $content;
              $content = nl2br( $content );
              break;
            default:
              $content = apply_filters( "wpp_stat_filter_{$stat}", $this_property->$stat );
              $content = ( is_array($content) )? implode(', ', $content): $content;
              $content = nl2br( $content );
              break;
          }
          if ( empty( $content ) ) {
            continue;
          }
          ?>
          <li class="<?php echo $stat ?>"><span class='attribute'><?php echo apply_filters('wpp::attribute::label', $wp_properties[ 'property_stats' ][ $stat ],$stat); ?>:</span> <span class='value'><?php echo $content; ?></span></li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>

    <?php if ( !empty( $instance[ 'enable_more' ] ) && $instance[ 'enable_more' ] == 'on' ) : ?>
      <p class="more"><a href="<?php echo $this_property->permalink; ?>"
                         class="btn btn-info"><?php _e( 'More', ud_get_wp_property()->domain ); ?></a></p>
    <?php endif; ?>
  </div>
  <?php
  unset( $this_property );
}

if ( !empty( $instance[ 'enable_view_all' ] ) && $instance[ 'enable_view_all' ] == 'on' ) {
  echo '<p class="view-all"><a href="' . site_url() . '/' . $wp_properties[ 'configuration' ][ 'base_slug' ] . '" class="btn btn-large">' . __( 'View All', ud_get_wp_property()->domain ) . '</a></p>';
}
echo '<div class="clear"></div>';
echo '</div>';