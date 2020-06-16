
    <style type="text/css">

    #wp_property_slideshow.postbox {
    background: transparent;
    border:0;
    }
    #wp_property_slideshow.postbox .handlediv{
    display:none;
    }
    #wp_property_slideshow.postbox h3.hndle{
    display:none;
    }
    .wpp_slideshow_global_selected {
    clear: right;
    display: block;
    float: right;
    position: relative;
    margin-right: 20px;
    width: <?php echo ($thumb_info['width'] + 20); ?>px;
    }
    .wpp_slideshow_global_all {
    clear: left;
    float: left;
    margin-right: -<?php echo ($thumb_info['width'] + 100); ?>px;
    width: 100%;
    }

    .wpp_slideshow_global_all_inner {
    position: relative;
    margin-right: <?php echo ($thumb_info['width'] + 60); ?>px;

    }
    .wpp_slideshow_global_all_inner .wpp_slideshow_global_all_inner_menu {
      position: absolute;
        right: 11px;
        text-align: right;
        top: 0;
        width: 440px;
    }

    .wpp_slideshow_global_all_inner #sortable1{
    overflow: auto;
    }

    .wpp_slideshow_image_block {
      margin: 5px;
      position: relative;
    }

    #sortable1 .wpp_slideshow_image_block {
      float: left;
    }

    #wpp_slideshow_show_help,
    #wpp_slideshow_auto_add {
     cursor: pointer;
    }

    .wpp_slideshow_postbox_description {
    display: none;
    background-color: #FFFFE0;
    margin: 5px 0 15px;
    border-color: #E6DB55;
    -moz-border-radius: 3px 3px 3px 3px;
    border-style: solid;
    border-width: 1px;
    padding: 0 0.6em;

    }

    #sortable1, #sortable2 {
    background:none repeat scroll 0 0 #EDEDEF;
    border:7px solid #BABABA;
    list-style-type:none;
    margin:0 10px 0 0;
    min-height:300px;
    padding:10px;
    }
    .image_block .title {font-size: 1.4em;}

    #sortable1 li, #sortable2 li {
    font-size:1.2em;

    padding:0 0 6px;
    text-align:center;
    <?php if(!empty($thumb_info['thumb_width'])): ?>
    width:<?php echo $thumb_info['thumb_width']; ?>px;
    <?php endif; ?>
    }

    #sortable1 li img, #sortable2 li img{
    border: 1px solid #888888;
    cursor: move;
    }

    #sortable1 {
    border:7px solid #DADADA;
    min-height:300px;
    min-width:<?php echo $thumb_info['width'] + 10; ?>px;
    }
    #sortable2 {
    background:none repeat scroll 0 0 #F8FFC6;
    border:7px solid #D5CD9C;
    min-height:300px;
    min-width:<?php echo $thumb_info['width'] + 10; ?>px;
    }

    .wpp_selected_images_title, .wpp_all_images_title {
    display: table;
    padding: 10px;
     font-weight: bold;
    }

    .wpp_image_missing .wpp_image_element {
      display: block;
      position: relative;
      background: #E7E7E7;
    }

    .wpp_selected_images_title {
    background: #D5CD9C;
    }

    .wpp_all_images_title {
    background: #DADADA;

    }

    .wpp_slideshow_image_stats.image_does_not_exist {
      background: none repeat scroll 0 0 #FFCFCF;
      border: 1px solid #BB8C8C;
      color: #543636;
      left: 2%;
      position: absolute;
      top: 4px;
      width: 93%;
    }

    .wpp_slideshow_image_stats.image_exists {
        background: none repeat scroll 0 0 #BDEABC;
        border: 1px solid #57B873;
        color: #46704F;
     }



    </style><!-- styles -->

    <script type="text/javascript">
    jQuery(function() {

      jQuery("#wpp_slideshow_show_help").click(function() {
        jQuery(".wpp_slideshow_postbox_description").toggle();
      });

      jQuery("#wpp_slideshow_auto_add").click(function() {
        wpp_slideshow_add_all();
      });


      jQuery("#sortable1, #sortable2").sortable({
        connectWith: '.connectedSortable',
        update: function() {
          wpp_slideshow_update_order();
        }
      }).disableSelection();

      jQuery(".image_block a").click(function(e){
          e.preventDefault();
      });

      wpp_slideshow_update_order();

    });

    function wpp_slideshow_add_all() {

      jQuery("#sortable1 .wpp_slideshow_image_block").each(function() {
        if(jQuery(this).attr("can_use") == 'true') {
          jQuery(this).appendTo("#sortable2");
        }
      });

      jQuery("#sortable2 .wpp_slideshow_image_block").each(function() {
        if(jQuery(this).attr("can_use") == 'false') {
          jQuery(this).appendTo("#sortable1");
        }
      });


      wpp_slideshow_update_order();

    }

    function wpp_slideshow_update_order() {
      var order = jQuery('#sortable2').sortable('serialize', {key: 'item'});
      if (order != '' ) {
        jQuery("#slideshow_image_array").val(order);
      }else{
        jQuery("#slideshow_image_array").val('item=');
      }

      var gallery_order = jQuery('#sortable1').sortable('serialize', {key: 'item'});
      if (gallery_order != '' ) {
        jQuery("#gallery_image_array").val(gallery_order);
      } else {
        jQuery("#gallery_image_array").val('item=');
      }
    }

    </script><!-- /scripts -->

    <div class="wpp_slideshow_postbox_description " >
    <p>Please ensure the images are large enough for the slideshow. The image sizes for the slideshow are set on <a href="<?php echo admin_url("edit.php?post_type=property&page=property_settings#tab_slideshow"); ?>">Slideshow Settings Page</a>.  If an image is too small, it will not be included in the actual slideshow.  To avoid pixelation, WP-Property will never stretch out your images. </p>
    <p>Current size: <b><?php echo $prop_slideshow['width']; ?>px</b> by <b><?php echo $prop_slideshow['height']; ?>px</b>, using <b><?php echo $image_type; ?></b>.</p>
    <?php if(class_exists('RegenerateThumbnails')): ?>
    <p>If you create a new image size, be sure to <a href="<?php echo admin_url("tools.php?page=regenerate-thumbnails"); ?>">regenerate your thumbnails</a>. </p>
    <?php else: ?>
    <p>If you create a new image size, be sure to regenerate your thumbnails using the <a href="http://wordpress.org/extend/plugins/regenerate-thumbnails/">Regenerate Thumbnails</a>.</p>
    <?php endif; ?>
    </div>

    <input type="hidden" name="property_slideshow_image_array" id="slideshow_image_array" value="" />
    <input type="hidden" name="property_gallery_image_array" id="gallery_image_array" value="" />

    <div class="wpp_slideshow_images">
    <div class="wpp_slideshow_global_selected image_block">
     <span class="wpp_selected_images_title"> <?php _e('Slideshow Images:',ud_get_wpp_slideshow()->domain) ?></span>
      <ul id="sortable2" class="connectedSortable clearfix">
     <?php
     if(is_array($current)):
      foreach($current as $curr_id):
         if(is_array($curr_id) && count($curr_id)>0){

          foreach ((array)$curr_id as $cur_id){
           if($cur_id){
            class_wpp_slideshow::draggable_image_block($cur_id, $image_type);
           }
          }
         }
      endforeach;
     endif; ?>
     </ul>

    </div>


    <div class="wpp_slideshow_global_all image_block">
      <div class="wpp_slideshow_global_all_inner">
        <span class="wpp_all_images_title"><?php _e('All Images:',ud_get_wpp_slideshow()->domain) ?></span>
        <div class="wpp_slideshow_global_all_inner_menu">
          <span class="description" id="wpp_slideshow_show_help">Help</span> |
          <span class="description" id="wpp_slideshow_auto_add">Auto Fix</span>
        </div>
        <ul id="sortable1" class="connectedSortable clearfix"  >
          <?php if( !empty( $all_images ) && is_array( $all_images ) ) : ?>
            <?php foreach( $all_images as $image ) : ?>
              <?php class_wpp_slideshow::draggable_image_block( $image->ID, $image_type ); ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    </div>

    <div style="clear:both"></div>