<?php
    /* Get all images that are big enough  */
    global $wpdb, $wp_properties;

    if( !empty( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'] ) ) {
      $image_type = $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'];
      $image_sizes = WPP_F::image_sizes( $image_type );
      
      $glob_slideshow[0]  = !empty( $image_sizes['width'] ) ? $image_sizes['width'] : false;
      $glob_slideshow[1]  = !empty( $image_sizes['height'] ) ? $image_sizes['height'] : false;
    }
    
    if( empty( $glob_slideshow[0] ) || empty( $glob_slideshow[1] ) ) {
      ?>
      <div class="wrap">
        <h2><?php _e('Global Slideshow',ud_get_wpp_slideshow()->domain); ?></h2>
        <p>
        Please visit the <a href="<?php echo admin_url("edit.php?post_type=property&page=property_settings#tab_slideshow"); ?>">slideshow settings page</a> and select the global slideshow size first.
        </p>
      </div>
      <?php
      return false;
    }

    $thumb_type = (!empty($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
      && !is_numeric($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
        && $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width']  != '-' ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'] : 'thumbnail');

    $thumb_info = WPP_F::image_sizes($thumb_type);

    $image_type = false;
    $prop_slideshow = array();
    if( !empty( $wp_properties['configuration']['feature_settings']['slideshow']['property']['image_size'] ) ) {
      $image_type  = $wp_properties['configuration']['feature_settings']['slideshow']['property']['image_size'];
      $prop_slideshow = WPP_F::image_sizes( $image_type );
    }

    if( empty( $prop_slideshow['width'] ) )
      $prop_slideshow['width'] = '640';

    if( empty( $prop_slideshow['height'] ) )
      $prop_slideshow['height'] = '235';

    /* If updating  */
    if( 
      isset( $_REQUEST[ '_wpnonce' ] )
      && isset( $_POST[ 'slideshow_image_array' ] )
      && wp_verify_nonce( $_REQUEST[ '_wpnonce' ] , 'wpp_update_slideshow' )
    ) {

      $image_array = $_POST['slideshow_image_array'];
      $image_array = str_replace( 'item=', '', $image_array );
      $image_array = !empty( $image_array ) ? explode( '&', $image_array ) : array();

      update_option( 'class_wpp_slideshow_image_array', $image_array );

      $updated = __( 'Slideshow selection and order saved.', ud_get_wpp_slideshow()->domain );
    }

    /* Get current images  */
    $current = get_option('class_wpp_slideshow_image_array');

    $good_images = class_wpp_slideshow::get_global_images('featured_property_images');

    ?>
    <style type="text/css">
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

    .wpp_slideshow_global_all_inner #sortable1{
      overflow: auto;
    }

    .wpp_slideshow_global_all_inner .wpp_slideshow_global_all_inner_menu {
        position: absolute;
        right: 11px;
        text-align: right;
        top: 0;
        width: 440px;
    }

    #sortable1 .wpp_slideshow_image_block {
      float: left;
    }

    .wpp_slideshow_image_block {
      margin: 5px;
      position: relative;
    }

    #wpp_slideshow_remove_all,
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
    width:<?php echo $thumb_info['thumb_width']?$thumb_info['thumb_width']:150; ?>px;
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
    font-size: 1.3em;
    font-weight: bold;
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

     #sortable1 .slideshow_load_more{
      width: 100%;
      position: static !important; 
     }
     #sortable1 .slideshow_load_more button{
      width: 100%;
      text-align: center;
     }

    </style><!-- styles -->
    <script type="text/javascript">
      var wpps_total_count = 0;
      jQuery(document).ready(function() {

        wpp_slideshow_resize_global_all();
        
        jQuery("#wpp_slideshow_show_help").click(function() {
          jQuery(".wpp_slideshow_postbox_description").toggle();
        });

        jQuery("#wpp_slideshow_auto_add").click(function() {
          wpp_slideshow_auto_add();
        });

        jQuery("#wpp_slideshow_remove_all").click(function() {
          wpp_slideshow_remove_all();
        });

        jQuery("#sortable1, #sortable2").sortable({
          connectWith: '.connectedSortable',
          update: function() {
            wpp_slideshow_update_order();
          }
        }).disableSelection();

        jQuery('#wpp_slideshow_global_filter').change(function() {
          wpp_slideshow_get_global_images();
        });
        
        wpp_slideshow_update_order();

        jQuery('#sortable1').on('click', '.slideshow_load_more button', function(event){
          event.preventDefault();
          var btn = jQuery(this);
          var selection = jQuery('option:selected', jQuery("#wpp_slideshow_global_filter")).val();
          
          btn.attr("disabled", "disabled");
          jQuery.post( wpp.instance.ajax_url,
          {
            action:"wpp_slideshow_get_global_images",
            selection: selection,
            start: wpps_total_count,
            _wpnonce: '<?php echo wp_create_nonce('wpp_get_global_images'); ?>'
          },
          function(result) {
            if(result == ''){
              btn.fadeOut();
              return;
            }
            wpps_total_count += result.total_count;
            jQuery(result.html).insertBefore(btn.parent());
            if(result.html == "" || !result.more_image)
              btn.fadeOut();
          }).always(function(){
            btn.removeAttr("disabled");
          });
        });

      });
     
      function wpp_slideshow_get_global_images() {
        var selection = jQuery('option:selected', jQuery("#wpp_slideshow_global_filter")).val();
        jQuery("#sortable1").html('<?php _e('Loading...', ud_get_wpp_slideshow()->domain); ?>');
        jQuery.post( wpp.instance.ajax_url,
        {
          action:"wpp_slideshow_get_global_images",
          selection: selection,
          _wpnonce: '<?php echo wp_create_nonce('wpp_get_global_images'); ?>'
        }, function(result) {
          wpps_total_count = result.total_count;
          var wrapper = jQuery("#sortable1").html(result.html);
          if(result.more_image)
            wrapper.append('<li class="slideshow_load_more"><button class="button" ><?php _e("Load more images");?></button></li>');
        });
      }

      function wpp_slideshow_remove_all() {
        jQuery("#sortable2 .wpp_slideshow_image_block").each(function() {
          jQuery(this).appendTo("#sortable1");
        });
        wpp_slideshow_update_order();
      }

      function wpp_slideshow_auto_add() {
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

      function wpp_slideshow_resize_global_all() {
        var height = jQuery(".wpp_slideshow_global_selected").height();
        if(height > 400)
          jQuery(".wpp_slideshow_global_all_inner #sortable1").css("height", (height - 70));
        else
          jQuery(".wpp_slideshow_global_all_inner #sortable1").css("height", 400);
      }

      function wpp_slideshow_update_order() {
        var loadMore = jQuery('#sortable1').find('.slideshow_load_more');
        var order = jQuery('#sortable2').sortable('serialize', {key: 'item'});
        loadMore.parent().append(loadMore);
        if (order != '' ) {
          jQuery("#slideshow_image_array").val(order);
        }else{
          jQuery("#slideshow_image_array").val('item=');
        }
      }
    </script><!-- sorting scripts -->

    <div class="wrap">
    <h2><?php _e('Global Slideshow',ud_get_wpp_slideshow()->domain); ?></h2>

    <?php if( isset( $updated ) && $updated ): ?>
     <div class="updated fade"><p><?php echo $updated; ?></p></div>
    <?php endif; ?>
    <div class="wpp_slideshow_postbox_description " >
      <p>Please ensure the images are large enough for the slideshow. The image sizes for the slideshow are set on <a href="<?php echo admin_url("edit.php?post_type=property&page=property_settings#tab_slideshow"); ?>">Slideshow Settings Page</a>.  If an image is too small, it will not be included in the actual slideshow.  To avoid pixelation, WP-Property will never stretch out your images. </p>
      <p>Current size: <b><?php echo $prop_slideshow['width']; ?>px</b> by <b><?php echo $prop_slideshow['height']; ?>px</b>, using <b><?php echo $image_type; ?></b>.</p>
      <?php if(class_exists('RegenerateThumbnails')): ?>
      <p>If you create a new image size, be sure to <a href="<?php echo admin_url("tools.php?page=regenerate-thumbnails"); ?>">regenerate your thumbnails</a>. </p>
      <?php else: ?>
      <p>If you create a new image size, be sure to regenerate your thumbnails using the <a href="http://wordpress.org/extend/plugins/regenerate-thumbnails/">Regenerate Thumbnails</a>.</p>
      <?php endif; ?>
    </div>
    <form action="<?php admin_url('edit.php?post_type=property&page=slideshow'); ?>" method="post">
     <div class="wpp_box">
      <div class="wpp_box_header">
         <strong><?php _e('WP-Property Slideshow',ud_get_wpp_slideshow()->domain); ?></strong>
         <p><?php _e('This slideshow can be integrated into your front-end pages by either using the shortcode, or pasting PHP code into your theme.',ud_get_wpp_slideshow()->domain); ?></p>
      </div>
      <div class="wpp_box_content">
         <p><?php _e('Drag images from selection on the left to the selection on the right, and then click save.',ud_get_wpp_slideshow()->domain); ?></p>
         <p><?php echo sprintf(__('This list gets all images from your media library that are over %s pixels wide and %s pixels tall.',ud_get_wpp_slideshow()->domain), $glob_slideshow[0], $glob_slideshow[1]); ?></p>
         <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpp_update_slideshow'); ?>" />
         <input type="hidden" name="slideshow_image_array" id="slideshow_image_array" value="" />
      </div>

      <div class="wpp_box_footer">
          <input type="submit" value="<?php _e('Save Selection and Order',ud_get_wpp_slideshow()->domain) ?>" accesskey="p" tabindex="4" id="publish" class="button-primary btn" name="save">
      </div>
     </div>
    </form>

    <div class="wpp_slideshow_images">
    <div class="wpp_slideshow_global_selected image_block">
     <span class="wpp_selected_images_title"> <?php _e('Slideshow Images:',ud_get_wpp_slideshow()->domain) ?></span>
     <ul id="sortable2" class="connectedSortable clearfix">
     <?php
      if(is_array($current)):
      foreach($current as $curr_id):

         if($curr_id){
         class_wpp_slideshow::draggable_image_block($curr_id, $image_type);
         }
      endforeach; endif; ?>
     </ul>
    </div>
    <div class="wpp_slideshow_global_all image_block">
    <div class="wpp_slideshow_global_all_inner">
     <span class="wpp_all_images_title"><?php _e('All Images:',ud_get_wpp_slideshow()->domain) ?></span>

      <div class="wpp_slideshow_global_all_inner_menu">

        Show:
        <select id="wpp_slideshow_global_filter">
          <option selected="true" value="featured_property_images">Primary Property Images</option>
          <option value="all_property_images">All Property Images</option>
          <option value="all_images">All Images</option>
        </select>

        <span class="description" id="wpp_slideshow_show_help">Help</span> |
        <span class="description" id="wpp_slideshow_auto_add">Auto Fix</span> |
        <span class="description" id="wpp_slideshow_remove_all">Remove All</span>
      </div>

     <ul id="sortable1" class="connectedSortable clearfix">
      <?php
      foreach((array)$good_images as $image_obj) {
        $image = $image_obj['image_id'];
        /* skip if current  */
        if(is_array($current))
        if(in_array($image, $current))
         continue;

        class_wpp_slideshow::draggable_image_block($image, $image_type,true,$image_obj);
      }
      ?>
      <li></li>
     </ul>
    </div>
    </div>
    </div><!-- /div.wrap -->
    </div><!-- /div.wrap -->
  