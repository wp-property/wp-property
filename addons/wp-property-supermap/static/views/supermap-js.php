<?php
/**
 * Supermap Javascript
 *
 * @todo: move to separate javascript file ( static/scripts/supermap.js )
 */

ob_start();
?>
<script type="text/javascript">

  <?php if (wp_script_is( 'jquery-ui-tabs', $list = 'queue' )) : ?>
  jQuery(window).load(function(){
    superMap_<?php echo $rand; ?>();
  });
  <?php else: ?>
  jQuery(document).ready(function() {
    superMap_<?php echo $rand; ?>();
  });
  <?php endif;?>

  jQuery(document).bind("wpp::ui-tabs::tabsshow", function(e,ui) {
    superMap_<?php echo $rand; ?>();
  });

  jQuery(document).bind("wpp_redraw_supermaps", function(e) {
    superMap_<?php echo $rand; ?>();
  });

  /**
   * Renders Supermap
   *
   * styles: file_get_contents( WP_CONTENT_DIR . '/static/config/google-maps.apple.json' );
   *
   */
  function superMap_<?php echo $rand; ?>() {
    /* Map settings */

    var myOptions_<?php echo $rand; ?> = {
      <?php if($zoom): ?>
      zoom: <?php echo $zoom; ?>,
      <?php endif; ?>
      <?php if(!empty($center_on)): ?>
      center:  new google.maps.LatLng(<?php echo $center_on; ?>),
      <?php endif; ?>
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      scrollwheel: <?php echo ( !empty( $scrollwheel ) ? $scrollwheel : 'false' ); ?>
    }

    if(typeof window.map_<?php echo $rand; ?> ==='object' || jQuery("#super_map_<?php echo $rand; ?>:visible").length===0){
      return false;
    }

    /* Set global map, Infowindow and other params */
    window.map_<?php echo $rand; ?> = new google.maps.Map(document.getElementById("super_map_<?php echo $rand; ?>"), myOptions_<?php echo $rand; ?>);
    window.infowindow_<?php echo $rand; ?> = new google.maps.InfoWindow();
    window.bounds_<?php echo $rand; ?> = new google.maps.LatLngBounds();
    window.markers_<?php echo $rand; ?> = [];


    /* Set search params */
    var formFilter = jQuery('#formFilter_<?php echo $rand; ?>');
    window.supermap_<?php echo $rand; ?> = {
      total : '<?php echo $atts['total']; ?>',
      per_page : '<?php echo $atts['per_page']; ?>',
      starting_row : '<?php echo $atts['starting_row']; ?>',
      pagination : '<?php echo $atts['pagination']; ?>',
      sort_order : '<?php echo $atts['sort_order']; ?>',
      sort_by : '<?php echo $atts['sort_by']; ?>',
      action : 'supermap_get_properties',
      random : '<?php echo $rand; ?>',
      property_type: '<?php echo trim(( is_array($atts['property_type']) ? implode(',',$atts['property_type']) : $atts['property_type'] )); ?>',
      search_atts : (formFilter.length > 0 ? formFilter.serialize() : '')
    };

    /* START Markers functionality */
    <?php foreach ((array) $properties as $id => $value) : ?>
    <?php if ( !empty( $value['latitude'] ) && !empty( $value['longitude'] ) ) : ?>

    window.myLatlng_<?php echo $rand; ?>_<?php echo $value['ID']; ?> = new google.maps.LatLng(<?php echo $value['latitude']; ?>,<?php echo $value['longitude']; ?>);
    window.content_<?php echo $rand; ?>_<?php echo $value['ID']; ?> = '<?php echo WPP_F::google_maps_infobox($value); ?>';

    window.marker_<?php echo $rand; ?>_<?php echo $value['ID']; ?> = new google.maps.Marker({
      position: myLatlng_<?php echo $rand; ?>_<?php echo $value['ID']; ?>,
      map: map_<?php echo $rand; ?>,
      title: '<?php echo str_replace( array( "'", "\r", "\n" ), "", !empty($value[$wp_properties['configuration']['address_attribute']]) ? $value[$wp_properties['configuration']['address_attribute']] : '' ); ?>',
      icon: '<?php echo apply_filters('wpp_supermap_marker', '', $value['ID']); ?>'
    });

    window.marker_<?php echo $rand; ?>_<?php echo $value['ID']; ?>.addListener('click', function () {
      window.map_<?php echo $rand; ?>.setCenter(window.marker_<?php echo $rand; ?>_<?php echo $value['ID']; ?>.getPosition());
      setTimeout(function () {
        if (jQuery('#infowindow').hasClass('infowindow-style-new')) {
          jQuery('#infowindow').parent().parent().parent().css('min-height', jQuery('#infowindow').find('img').height());
          jQuery('#infowindow').parent().parent().parent().addClass('scrollable');
        }
      }, 1500);
    });

    window.markers_<?php echo $rand; ?>.push(window.marker_<?php echo $rand; ?>_<?php echo $value['ID']; ?>);

    google.maps.event.addListener(marker_<?php echo $rand; ?>_<?php echo $value['ID']; ?>, 'click', function() {
      infowindow_<?php echo $rand; ?>.close();
      infowindow_<?php echo $rand;  ?>.setContent(content_<?php echo $rand; ?>_<?php echo $value['ID']; ?>);
      infowindow_<?php echo $rand; ?>.open(map_<?php echo $rand; ?>,marker_<?php echo $rand; ?>_<?php echo $value['ID']; ?>);
      loadFuncy();
      /* Highlighting clicked property on the map */
      makeActive(<?php echo $rand; ?>,<?php echo $value['ID']; ?>);
    });

    google.maps.event.addListener(infowindow_<?php echo $rand; ?>, 'domready', function() {
      if (jQuery('#infowindow').hasClass('infowindow-style-new')) {
        jQuery('#infowindow').parent().parent().parent().css('min-height', jQuery('#infowindow').find('img').height());
        jQuery('#infowindow').parent().css('overflow', 'hidden');
        jQuery('#infowindow').parent().parent().css('overflow', 'hidden');
        jQuery('#infowindow').parent().parent().parent().addClass('scrollable');
      }
    });


    bounds_<?php echo $rand; ?>.extend(window.myLatlng_<?php echo $rand; ?>_<?php echo $value['ID']; ?>);
    <?php endif; ?>
    <?php endforeach; ?>
    /* END Markers functionality */

    /* Set zoom */
    map_<?php echo $rand; ?>.setZoom(<?php echo ((int)$zoom != 0 ? $zoom : 10); ?>);
    /* Set center */
    <?php if (!empty($center_on)) : ?>
    map_<?php echo $rand; ?>.setCenter(new google.maps.LatLng(<?php echo $center_on; ?>));
    <?php else: ?>
    <?php foreach ((array) $properties as $id => $p) : ?>
    if (typeof myLatlng_<?php echo $rand; ?>_<?php echo $p['ID']; ?> != 'undefined') {
      map_<?php echo $rand; ?>.setCenter(myLatlng_<?php echo $rand; ?>_<?php echo $p['ID']; ?>);
    }
    <?php endforeach; ?>
    <?php endif; ?>

    /* Prevent issue with map having no height if no CSS is included and no height is set via shortcode */
    if(jQuery("#super_map_<?php echo $rand; ?>").height() === 0) {
      jQuery("#super_map_<?php echo $rand; ?>").height(400);
    }

    <?php if(empty($zoom) && empty($center_on)): ?>
    /* Set defaults */
    map_<?php echo $rand; ?>.fitBounds(bounds_<?php echo $rand; ?>);
    <?php endif; ?>

    <?php if (!empty($area_lines)) : ?>
    /* Renders Areas */
    <?php echo $area_lines; ?>
    <?php endif; ?>

    /* Bind events */
    /* Show More Event */
    jQuery('.show_more', '#super_map_list_<?php echo $rand; ?>').click(function(){
      getProperties(<?php echo $rand; ?>, 'more');
    });


  }

  /**
   * Shows Infobox on Supermap
   */
  function showInfobox_<?php echo $rand; ?>(id) {
    console.log( 'showInfobox', id );
    map_<?php echo $rand; ?>.setCenter(eval('myLatlng_<?php echo $rand; ?>_' + id));
    map_<?php echo $rand; ?>.setZoom(<?php echo (int)$zoom != 0 ? $zoom : 10; ?>);

    makeActive(<?php echo $rand; ?>,id);

    infowindow_<?php echo $rand; ?>.setContent(eval('content_<?php echo $rand; ?>_' + id));

    setTimeout( function(){
      infowindow_<?php echo $rand; ?>.open(map_<?php echo $rand; ?>, eval('marker_<?php echo $rand; ?>_' + id));
      loadFuncy();
    }, 500);
  }

  /**
   * Set property as active in sidebar when property's popup is opened on supermap
   */
  if(typeof makeActive != 'function') {
    function makeActive(rand,id){
      if(jQuery(".property_in_list").length > 0) {
        jQuery(".property_in_list").removeClass("active");
      }
      if(jQuery("#property_in_list_"+rand+"_"+id).length > 0) {
        jQuery("#property_in_list_"+rand+"_"+id).addClass("active");
      }
    }
  }

  /**
   *
   */
  if(typeof loadFuncy != 'function') {
    function loadFuncy(){
      jQuery("a#single_image").fancybox({
        transitionIn: 'elastic',
        transitionOut: 'elastic',
        speedIn: 600,
        speedOut: 200,
        overlayShow: false
      });
    }
  }

  /**
   * Search properties and renders found ones on supermap
   *
   * @param rand
   * @param type
   */
  if(typeof getProperties != 'function') {
    function getProperties(rand, type){
      /* Set default type as 'search' */
      if (typeof type == 'undefined') {
        type = 'search';
      }

      /* Get search settings */
      var s = eval('supermap_' + rand);
      var markers = eval('markers_' + rand);
      var ajaxloader = jQuery('.super_map_list .map_filters .search_loader');

      switch(type) {

        case 'search':
          jQuery('#super_map_list_property_'+rand).html('');
          s.search_atts = jQuery('#formFilter_'+rand).serialize();
          s.starting_row = 0;
          clearMarkers(markers);
          break;

        case 'more':
          s.starting_row = parseInt(s.starting_row) + parseInt(s.per_page);
          break;

      }

      /* Prepare params for Ajax search */
      params = prepareSupermapSearchParams(s);

      ajaxloader.show();

      jQuery.ajax({
        async: false,
        type: "POST",
        url: "<?php echo admin_url('admin-ajax.php'); ?>",
        data:params,
        success: function(msg){
          eval(msg);
        }
      });

      ajaxloader.hide();

      /* Show or hide 'Show More' button */
      var sm = jQuery('.show_more', jQuery('#super_map_list_property_'+rand).parent());
      if(sm.length > 0) {
        if( (parseInt(s.starting_row) + parseInt(s.per_page) ) >= parseInt(s.total)) {
          sm.hide();
        } else {
          sm.show();
        }
      }
    }
  }

  /**
   * Prepares Search params for get Properties
   *
   * @param rand
   * @return string $params Prepared params
   * @author Maxim Peshkov
   */
  if(typeof prepareSupermapSearchParams != 'function') {
    function prepareSupermapSearchParams(obj) {
      var params = '';
      for(var i in obj) {
        if(params != '') {
          params += '&'
        }
        if(i == 'search_atts') {
          params += obj[i];
        } else {
          params += i + '=' + obj[i];
        }
      }
      return params;
    }
  }

  /**
   * Clear Markers on Supermap
   *
   * @param array $markers Array of google map objects (markers)
   * @author Maxim Peshkov
   */
  if(typeof clearMarkers != 'function') {
    function clearMarkers(markers) {
      for (var i in markers) {
        markers[i].setMap(null);
      }
    }
  }

</script>
<?php
$script = ob_get_clean();
echo WPP_F::minify_js($script);