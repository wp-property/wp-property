<?php
/**
 * [property_map] template
 *
 * To modify it, copy it to your theme's root.
 */

global $post, $wp_properties;
?>

<?php if(\WPP_F::get_coordinates()): ?>
  <div id="property_map" class="<?php wpp_css('property::property_map'); ?>" style="width:<?php echo $data['width'] ?>; height:<?php echo $data['height'] ?>"></div>
  <script type="application/javascript">
    jQuery(window).load(function(){
      <?php if($coords = WPP_F::get_coordinates()): ?>
      var myLatlng = new google.maps.LatLng(<?php echo $coords['latitude']; ?>,<?php echo $coords['longitude']; ?>);
      var myOptions = {
        zoom: <?php echo (!empty($data['zoom_level']) ? $data['zoom_level'] : ( !empty( $wp_properties['configuration']['gm_zoom_level'] ) ? $wp_properties['configuration']['gm_zoom_level'] : 13 )); ?>,
        center: myLatlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
      };

      map = new google.maps.Map(document.getElementById("property_map"), myOptions);

      marker = new google.maps.Marker({
        position: myLatlng,
        map: map,
        title: '<?php echo addslashes($post->post_title); ?>',
        icon: '<?php echo apply_filters('wpp_supermap_marker', '', $post->ID); ?>'
      });

      <?php if ( $data['hide_infobox'] != 'true' ): ?>
        infowindow = new google.maps.InfoWindow({
          content: '<?php echo WPP_F::google_maps_infobox($post); ?>',
          maxWidth: 500
        });

        google.maps.event.addListener(infowindow, 'domready', function() {
          document.getElementById('infowindow').parentNode.style.overflow='hidden';
          document.getElementById('infowindow').parentNode.parentNode.style.overflow='hidden';
        });

        setTimeout("infowindow.open(map,marker);",1000);
      <?php endif; ?>

      <?php endif; ?>
    });
  </script>
<?php endif; ?>