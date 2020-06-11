<?php
if ( $s['carousel'] == 'true' ) {

      ?>

      <?php if(!$no_images) { ?>

      <script type="text/javascript">

        var galleria_<?php echo $s['element']; ?>;

        function init_<?php echo $s['element']; ?>(){

          var gallery_element = jQuery("#<?php echo $s['element']; ?>:visible");

          if (typeof galleria_<?php echo $s['element']; ?> === 'undefined' && gallery_element.length){
            galleria_<?php echo $s['element']; ?> = gallery_element.galleria({
              <?php echo ($s['image_crop'] == 'true' ? 'imageCrop: true,': '');?>
              <?php echo ($s['transition']  ? 'transition: "'.$s['transition'].'",': '');?>
              <?php echo ($s['image_pan']  == 'true' ? 'imagePan: true,': '');?>
              <?php echo ($s['autoplay']  == 'true' ? 'autoplay: true,': '');?>
              <?php echo ($s['debug']  == 'true' ? 'debug: true,': 'debug: false,');?>
              <?php echo (is_numeric($s['carouselSpeed']) ? 'carouselSpeed: '.$s['carouselSpeed'].',': '');?>
              showInfo: <?php echo ($s['show_info']  == 'true' ? 'true': 'false');?>,
              width: "<?php echo ($s['width'] ? $s['width'] : 'auto');?>",
              height: "<?php echo ($s['height'] ? $s['height'] : 'auto');?>"
            });
          }
        }

        jQuery(document).ready(function() {
          if(typeof Galleria !== 'undefined') {
            Galleria.loadTheme("<?php echo $theme_url; ?>");
            jQuery(document).bind("wpp::ui-tabs::tabsshow", function(e,ui) {
              init_<?php echo $s['element']; ?>();
            });
          }
        });

        <?php if (wp_script_is( 'jquery-ui-tabs', $list = 'queue' )) : ?>
          jQuery(window).load(function(){
            init_<?php echo $s['element']; ?>();
          });
        <?php else: ?>
          jQuery(document).ready(function() {
            init_<?php echo $s['element']; ?>();
          });
        <?php endif;?>

      </script>
      <?php } ?>

      <div class="<?php echo implode(' ', $element_class); ?>" style="width: <?php echo $s['width']; ?>; height: <?php echo $s['height']; ?>">
      <div id="<?php echo $s['element']; ?>" class="wpp_galleria" style="width: <?php echo $s['width']; ?>; height: <?php echo $s['height']; ?>">
      <?php if(is_array($gallery_data )) { foreach($gallery_data as $single) { ?>
          <a <?php echo ($single['big'] ? 'rel="'.$single['big'].'"' : ''); ?> href="<?php echo $single['image']; ?>"><img src="<?php echo $single['thumb']; ?>" <?php echo ($single['link_url'] ? 'longdesc="'.$single['link_url'].'"' : ''); ?> alt="<?php echo esc_attr($single['description']) ?>" title="<?php echo esc_attr($single['title']) ?>"></a>
      <?php } } ?>
      </div>
      </div>
    <?php

    } else {

      if(!$no_images) {

        foreach((array)$gallery_data as $image) {
          ?>
          <div class="sidebar_gallery_item">
              <a href="<?php echo $image['big']; ?>" class="fancybox_image thumbnail" rel="property_gallery">
                <img src="<?php echo $image['thumb']; ?>" title="<?php echo $image['description'] ?>" alt="<?php echo $image['description'] ?>" class="wpp_shortcode_gallery_image size-thumbnail" />
              </a>
          </div>
          <?php

        }

      }

    }
