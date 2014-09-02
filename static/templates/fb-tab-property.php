<?php
/**
 * Template Name: Default Property
 * Type: property
 */

global $property, $wp_properties;

?><!DOCTYPE html>
<html>
  <head>
<?php wp_head(); ?>
  </head>
  <body class="facebook">

<?php the_post(); ?>
<?php $property = WPP_F::get_property( $post->ID ); ?>
  <div id="container" class="<?php echo (!empty($property[ 'property_type' ]) ? $property[ 'property_type' ] . "_container" : "");?>">
    <div id="content" role="main" class="property_content">
      <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>


      <div class="building_title_wrapper">
        <h1 class="property-title entry-title"><?php the_title(); ?></h1>
        <h3 class="entry-subtitle"><?php the_tagline(); ?></h3>
      </div>


      <div class="entry-content">
        <div class="wpp_the_content"><?php @the_content(); ?></div>

        <?php if ( empty($wp_properties['property_groups']) || $wp_properties['configuration']['property_overview']['sort_stats_by_groups'] != 'true' ) : ?>
          <ul id="property_stats" class="<?php wpp_css('fb_property::property_stats', "property_stats overview_stats list"); ?>">
            <?php if(!empty($property[ 'display_address' ])): ?>
            <li class="wpp_stat_plain_list_location alt">
              <span class="attribute"><?php echo $wp_properties['property_stats'][$wp_properties['configuration']['address_attribute']]; ?><span class="wpp_colon">:</span></span>
              <span class="value"><?php echo $property[ 'display_address' ]; ?>&nbsp;</span>
            </li>
            <?php endif; ?>
            <?php @draw_stats("display=list&make_link=true&exclude={$wp_properties['configuration']['address_attribute']}", $property ); ?>
          </ul>
        <?php else: ?>
          <?php if(!empty($property[ 'display_address' ])): ?>
          <ul id="property_stats" class="<?php wpp_css('fb_property::property_stats', "property_stats overview_stats list"); ?>">
            <li class="wpp_stat_plain_list_location alt">
              <span class="attribute"><?php echo $wp_properties['property_stats'][$wp_properties['configuration']['address_attribute']]; ?><span class="wpp_colon">:</span></span>
              <span class="value"><?php echo $property[ 'display_address' ]; ?>&nbsp;</span>
            </li>
          </ul>
          <?php endif; ?>
          <?php @draw_stats("display=list&make_link=true&exclude={$wp_properties['configuration']['address_attribute']}", $property ); ?>
        <?php endif; ?>

        <?php if(!empty($wp_properties['taxonomies'])) foreach($wp_properties['taxonomies'] as $tax_slug => $tax_data): ?>
          <?php if(get_features("type={$tax_slug}&format=count")):  ?>
          <div class="<?php echo $tax_slug; ?>_list">
          <h2><?php echo $tax_data['label']; ?></h2>
          <ul class="clearfix">
          <?php get_features("type={$tax_slug}&format=list&links=true"); ?>
          </ul>
          </div>
          <?php endif; ?>
        <?php endforeach; ?>

        <?php if(is_array($wp_properties['property_meta'])): ?>
        <?php foreach($wp_properties['property_meta'] as $meta_slug => $meta_title):
          if(empty($property[ $meta_slug ]) || $meta_slug == 'tagline')
            continue;
        ?>
          <h2><?php echo $meta_title; ?></h2>
          <p><?php echo  do_shortcode(html_entity_decode($property[ $meta_slug ])); ?></p>
        <?php endforeach; ?>
        <?php endif; ?>


        <?php if(WPP_F::get_coordinates()): ?>
          <div id="property_map" style="width:100%; height:450px"></div>
        <?php endif; ?>

        <?php if($property[ 'post_parent' ]): ?>
          <a href="<?php echo $property[ 'parent_link' ]; ?>" class="btn btn-return"><?php _e('Return to building page.','wpp') ?></a>
        <?php endif; ?>

      </div><!-- .entry-content -->
    </div><!-- #post-## -->

    </div><!-- #content -->
  </div><!-- #container -->

  <?php wp_footer(); ?>

  <script type="text/javascript">
    var map;
    var marker;
    var infowindow;

    jQuery(document).ready(function() {

      if (top !== self) {
        document.__current_href = document.location.href;

        jQuery("body").delegate("a","click",function() {
          var a = new RegExp('/' + window.location.host + '/');
          if(a.test(this.href)) {
            top.location.href = this.href;
            return false;
          }
        });
      }

      if(typeof jQuery.fn.fancybox == 'function') {
        jQuery("a.fancybox_image, .gallery-item a").fancybox({
          'transitionIn'  :  'elastic',
          'transitionOut'  :  'elastic',
          'speedIn'    :  600,
          'speedOut'    :  200,
          'overlayShow'  :  false
        });
      }

      if(typeof google == 'object') {
        initialize_this_map();
      } else {
        jQuery("#property_map").hide();
      }

    });


    function initialize_this_map() {
      <?php if($coords = WPP_F::get_coordinates()): ?>
      var myLatlng = new google.maps.LatLng(<?php echo $coords['latitude']; ?>,<?php echo $coords['longitude']; ?>);
      var myOptions = {
        zoom: <?php echo (!empty($wp_properties['configuration']['gm_zoom_level']) ? $wp_properties['configuration']['gm_zoom_level'] : 13); ?>,
        center: myLatlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
      }

      map = new google.maps.Map(document.getElementById("property_map"), myOptions);

      infowindow = new google.maps.InfoWindow({
        content: '<?php echo WPP_F::google_maps_infobox( $property ); ?>',
        maxWidth: 500
      });

       marker = new google.maps.Marker({
        position: myLatlng,
        map: map,
        title: '<?php echo addslashes($post->post_title); ?>',
        icon: '<?php echo apply_filters('wpp_supermap_marker', '', $post->ID); ?>'
      });

      google.maps.event.addListener(infowindow, 'domready', function() {
      document.getElementById('infowindow').parentNode.style.overflow='hidden';
      document.getElementById('infowindow').parentNode.parentNode.style.overflow='hidden';
     });

     setTimeout("infowindow.open(map,marker);",1000);

      <?php endif; ?>
    }

  </script>

  </body>
</html>