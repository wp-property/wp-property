<?php
global $property_resp_slideshow_counter;

if(empty($property_id)){
	global $post;
	$property_id = $post->ID;
}
$title = get_the_title($property_id);
$_meta_attached = get_post_meta($property_id, 'wpp_media');
if(!empty($_meta_attached) && is_array($_meta_attached) && count($_meta_attached) > 0){
  $images = UsabilityDynamics\WPP\Property_Factory::get_images($_meta_attached, true, 'ids');
}
else{
  $images = UsabilityDynamics\WPP\Property_Factory::get_images($property_id);
}

$imgs = array();
$property_resp_slideshow_counter++;

$slider_type = wp_is_mobile() ? 'standard' : $slider_type;

if($slider_type == "12grid" || $slider_type == "12mosaic")
  $img_size = "full";
else
  $img_size = "large";

foreach ($images as $img) {
	$attach_id = $img['attachment_id'];
	$full = wp_get_attachment_image_src( $attach_id, "full");
    if(!wp_is_mobile()){
        $large = wp_get_attachment_image($attach_id, $img_size);
        $thumb = wp_get_attachment_image($attach_id);
    }
    else{
        $large_src = wp_get_attachment_image_src($attach_id, $img_size);
        $large = "<img data-src='{$large_src[0]}' class='swiper-lazy' width='{$large_src[1]}' height='{$large_src[2]}' /><div class='swiper-lazy-preloader'></div>";
        $thumb = '';
    }
	$imgs[$attach_id] = array(
								'full' => $full,
								'large' => $large,
								'thumb' => $thumb
							);
  if($slider_type == "12grid" || $slider_type == "12mosaic"){
    $img_size = $grid_image_size;
  }
}
?>
<?php if(count($imgs)>0):?>
<!-- Swiper -->
<style type="text/css">
/* ajax loader */
.property-resp-slideshow.wpp-responsive-slideshow-loading {
  background: rgba(179, 179, 179, 0.31) url(<?php echo ud_get_wpp_resp_slideshow()->path('static/images/ajax-loader.gif', 'url');?>) no-repeat scroll center center;
}
.property-resp-slideshow.wpp-responsive-slideshow-loading > * {
  visibility: hidden;
}
</style>
<?php 
$mobile_class = wp_is_mobile()?'mobile':'';
echo "<div id='wpprs-$property_resp_slideshow_counter' 
                 class='property-resp-slideshow slider-type-$slider_type slideshow-type-$slideshow_type $mobile_class wpp-responsive-slideshow-loading' 
                 data-slideshow-type='$slideshow_type' 
                 data-slider-type='$slider_type'>";?>
    <div class="modal-header">
    <?php
    if($lb_title_1 != '' || $lb_title_2 != ''){
        $lb_title_1 = explode(',', $lb_title_1);
        $lb_title_2 = explode(',', $lb_title_2);   
        $title_line_1 = '';
        $title_line_2 = '';
        foreach ($lb_title_1 as $key => $value) {
            $value = trim($value);
            if(empty($value)) continue;
            $meta = get_post_meta($property_id, $value);
            $title_line_1 .= implode(' ', $meta) . " ";
        }
        foreach ($lb_title_2 as $key => $value) {
            $value = trim($value);
            if(empty($value)) continue;
            $meta = get_post_meta($property_id, $value);
            $title_line_2 .= implode(' ', $meta ) . " ";
        }
      echo '<div class="lb-title pull-left">';
        if($lb_title_1)
          echo "<div class='line-1'>$title_line_1</div>";
        if($lb_title_2)
          echo "<div class='line-2'>$title_line_2</div>";
      echo '</div>';
    }
    ?>
      <div class="pull-right">
        <a class="viewOriginal button" aria-label="View Original" href="javascript:void(0);" target="_blank">
          View Original <i class="dashicons dashicons-external"></i>
        </a>
        <a class="close" aria-label="Close"><i class="dashicons dashicons-no"></i></a>
      </div>
      <div class="clearfix"></div>
    </div>
    <div class="swiper-container gallery-top"
         data-slideshow_layout = "<?php echo $slideshow_layout;?>" 
         data-slider_width = "<?php echo $slider_width;?>" 
         data-slider_height = "<?php echo $slider_height;?>" 
         data-slider_auto_height = "<?php echo $slider_auto_height;?>" 
         data-slider_min_height = "<?php echo $slider_min_height;?>" 
         data-slider_max_height = "<?php echo $slider_max_height;?>" 
    >
        <div class="swiper-wrapper">
        <?php foreach ($imgs as $key => $img) {
        	echo "<div class='swiper-slide' data-src='{$img['full'][0]}' data-width='{$img['full'][1]}' data-height='{$img['full'][2]}' data-title='$title'>{$img['large']}</div>";
        }
        ?>
        </div>
        <!-- Add Arrows  swiper-button-white-->
        <div class="swiper-button-prev"><i class="dashicons dashicons-arrow-left-alt2"></i></div>
        <div class="swiper-button-next"><i class="dashicons dashicons-arrow-right-alt2"></i></div>
        <span class="count-progress">
            <span class="current">1</span> / 
            <span class="total"><?php echo count($imgs);?></span>
        </span> 
    </div>
    <?php 
    if($slideshow_type == 'standard'): 
        //echo '<div class="swiper-pagination"></div>';
    elseif(!wp_is_mobile()):?>
        <div class="swiper-container gallery-thumbs">
            <div class="swiper-wrapper">
        <?php 
            foreach ($imgs as $key => $img) {
            	echo "<div class='swiper-slide'>{$img['thumb']}</div>";
            }
        ?>
            </div>
        </div>
    <?php endif;?>
</div>
<!-- END Swiper -->
<?php endif;?>