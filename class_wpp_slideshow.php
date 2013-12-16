<?php
/*
Name: Slideshow and Carousel Gallery
Class: class_wpp_slideshow
Version: 3.8.9
Minimum Core Version: 1.38.3
Feature ID: 2
Description: Slideshow feature for WP-Property
*/


add_action('wpp_init', array('class_wpp_slideshow', 'init'));

add_action('wpp_pre_init', array('class_wpp_slideshow', 'pre_init'));

class class_wpp_slideshow {

  /*
   * (custom) Capability to manage the current feature
   */
  static protected $capability = "manage_wpp_slideshow";

  /**
   * Special functions that must be called prior to init
   *
   */
  function pre_init() {
    /* Add capability */
    add_filter('wpp_capabilities', array('class_wpp_slideshow', "add_capability"));
  }

  /**
   * Primary feature function.  Ran an init level.
   *
   * @since 3.1
   */
  function init() {
    global $wp_properties;
    //** Load default settings if they do not exist */
    if(!$wp_properties['configuration']['feature_settings']['slideshow']){
      $wp_properties['configuration']['feature_settings']['slideshow']['glob']['dimensions'] = '800x350';
      $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'] = 300;
      $wp_properties['configuration']['feature_settings']['slideshow']['property']['dimensions'] = '640x235';
      $wp_properties['configuration']['feature_settings']['slideshow']['thumb_width'] = 300;
      update_option('wpp_settings', $wp_properties);
    }

    if(current_user_can(self::$capability)) {
      //* Add SLideshow tab to Property Settings tabs array */
      add_filter('wpp_settings_nav', array('class_wpp_slideshow', 'settings_nav'));
      //* Add Settings Page tab content*/
      add_action('wpp_settings_content_slideshow', array('class_wpp_slideshow', 'settings_page'));
      //* Display draggable selection pane for selecting images for slideshow.  */
      add_action('add_meta_boxes', array('class_wpp_slideshow','add_metabox'));
    }

    add_filter('wpp_widget_property_gallery', array('class_wpp_slideshow', 'widget_property_gallery'), 10, 2);

    //** Hook into property_overview shortcode so property_gallery can be called as a template */
    add_filter('shortcode_property_overview_content', array('class_wpp_slideshow', 'shortcode_property_overview_content'), 10, 2);
    add_filter('shortcode_property_overview_allowed_args', array('class_wpp_slideshow', 'shortcode_property_overview_allowed_args'), 10, 2);

    //* Save slideshow selection when saving property *
    add_action('save_property', array('class_wpp_slideshow','save_in_postmeta'));

    //* Add slideshow overview page under Properties nav menu */
    add_action("admin_menu", array("class_wpp_slideshow", "admin_menu"));

    //* Load Nivo slider on all front-end pages */
    add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('wpp-jquery-nivo-slider');"));

    //* Render ajax response to control-panel image selection queries */
    add_action('wp_ajax_wpp_slideshow_get_global_images', array('class_wpp_slideshow', 'ajax_get_global_images'));

    add_action('template_redirect', array('class_wpp_slideshow', 'template_redirect'));
    add_action('admin_init', array('class_wpp_slideshow', 'admin_init'));

    //** Hook into pre-header functions of a single property page */
    add_action('template_redirect_single_property', array('class_wpp_slideshow', 'template_redirect_single_property'));

    //** Add two primary shortcodes */
    add_shortcode('property_slideshow', array('class_wpp_slideshow', 'shortcode_property_slideshow'));
    add_shortcode('property_gallery', array('class_wpp_slideshow', 'shortcode_property_gallery'));
    add_shortcode('global_slideshow', array('class_wpp_slideshow', 'shortcode_global_slideshow'));

  }

  /*
   * Adds Custom capability to the current premium feature
   */
  function add_capability($capabilities) {

    $capabilities[self::$capability] = __('Manage Slideshow','wpp');

    return $capabilities;
  }


  /*
   * Hook into property_overview shortcode defaults and add extra args so shortcode_atts() allows the passed arguments
   *
   * @author potanin@UD
   * @since 3.6.0
   */
  function shortcode_property_overview_allowed_args($defaults = false, $atts = false) {

    if(empty($atts['template']) || $atts['template'] != 'property_gallery') {
      return $defaults;
    }

    //** Just load some values, in practice they should be passed by user */
    $defaults['thumb_size'] = ''; /** Only here for consistancy, thumbnail_size as usual for PO can be used */
    $defaults['large_size'] = 'large';
    $defaults['image_size'] = 'medium';
    $defaults['hide_if_no_images'] = 'true';
    $defaults['enforce_minimum_image_width'] = 'false';
    $defaults['gallery_width'] = '';
    $defaults['gallery_height'] = '';
    $defaults['transition'] = 'fade';

    return $defaults;

  }


  /*
   * Hooks into propert_overview shortcode and insert property_gallery carousel when 'property_gallery' template is set.
   *
   * @author potanin@UD
   * @since 3.6.0
   */
  function shortcode_property_overview_content($result, $wpp_query) {
    global $wpp_query, $wp_query;

    if($wpp_query['template'] != 'property_gallery' ) {
      return $result;
    }

    $wpp_query['disable_wrapper'] = 'true';
    $wpp_query['pagination'] = 'off';
    $wpp_query['sorter_type'] = 'none';
    $wpp_query['hide_count'] = 'true';

    $s['thumb_size'] = ($wpp_query['thumb_size'] ? $wpp_query['thumb_size'] : $wpp_query['thumbnail_size']);
    $s['large_size'] = $wpp_query['large_size'];
    $s['image_size'] = $wpp_query['image_size'];
    $s['transition'] = $wpp_query['galleria_transition'];
    $s['width'] = $wpp_query['gallery_width'];
    $s['height'] = $wpp_query['gallery_height'];
    $s['enforce_minimum_image_width'] = $wpp_query['enforce_minimum_image_width'];


    if(!is_array($wpp_query['properties']['results'])) {
      return false;
    }

    //** Ensure galleria script is included */
    WPP_F::force_script_inclusion('wp-property-galleria');

    //** Get data for all images from results and load a mix of feature image ID and property data */
    foreach($wpp_query['properties']['results'] as $count => $property_id) {
      $property = WPP_F::get_property($property_id, 'load_gallery=false&get_children=false&return_object=true');
      $gallery[$count]['attachment_id'] = $property->featured_image;
      $gallery[$count]['post_title'] = $property->post_title;
      $gallery[$count]['post_excerpt'] = $property->post_excerpt;
      $gallery[$count]['link_url'] = $property->permalink;
    }

    $wp_query->query_vars['property']['gallery'] = $gallery;

    $gallery_result = class_wpp_slideshow::display_gallery($s);

    if(!empty($gallery_result)) {
      $result = $gallery_result;
    }

    return $result;
  }


  /**
   * Main back-end function.
   *
   * @since 3.5.0
   *
   */
  function admin_init() {

    //** Detect if FEPS Page */
    add_action('wpp_widget_slideshow_bottom', array('class_wpp_slideshow', 'widget_gallery'));

  }

  function widget_gallery($args) {

    $this_object = $args['this_object'];
    $instance = $args['instance'];

    $galleria_gallery = $instance['galleria_gallery'];
    $galleria_transition = $instance['galleria_transition'];
    $imageCrop = $instance['imageCrop'];
    $carousel_thumb_size = $instance['carousel_thumb_size'];
    $enforce_minimum_image_width = $instance['enforce_minimum_image_width'];

    $this_id = 'wpp_gallery_galleria_settings_' . rand(1000,9999);
    ?>
    <div class="<?php echo $this_id; ?> wpp_gallery_galleria_settings">

    <style type="text/css">
      .widget .widget-inside .<?php echo $this_id; ?>  p.wpp_gallery_galleria_options{
      <?php if($galleria_gallery != 'on')  { ?>
        display:none;
      <?php } ?>
        background-color: #EAEAEA;
        margin: -6px -6px 5px -6px;
        padding: 9px 9px 6px;
      }
    </style>

    <script type="text/javascript">
      jQuery(document).ready(function() {

        toggle_widget_galleria(".<?php echo $this_id; ?> .wpp_toggle_galleria_options");

         jQuery(".<?php echo $this_id; ?> .wpp_toggle_galleria_options").live("change", function() {
          toggle_widget_galleria(this);
        });

        function toggle_widget_galleria(checkbox) {

          var widget_parent = jQuery(checkbox).closest(".widget-content");

          if(jQuery(checkbox).is(":checked")) {
            jQuery(".wpp_gallery_galleria_options", widget_parent).show();
            jQuery(".wpp_gallery_big_image_type", widget_parent).hide();
            jQuery(".wpp_gallery_show_description", widget_parent).hide();
          } else {
            jQuery(".wpp_gallery_galleria_options", widget_parent).hide();
            jQuery(".wpp_gallery_big_image_type", widget_parent).show();
            jQuery(".wpp_gallery_show_description", widget_parent).show();
          }
        }

      });
    </script>

    <p style="margin-bottom: 20px;">
      <input name="<?php echo $this_object->get_field_name('galleria_gallery'); ?>"  id="<?php echo $this_object->get_field_id('galleria_gallery') ?>" type="checkbox" <?php checked('on', $galleria_gallery); ?> value="on"  class="wpp_toggle_galleria_options" />
      <label for="<?php echo $this_object->get_field_id('galleria_gallery') ?>"><?php _e('Display as carousel gallery.', 'wpp'); ?></label>
    </p>

    <p class="wpp_gallery_galleria_options">
      <label for="<?php echo $this_object->get_field_id('galleria_transition') ?>"><?php _e('Gallery transition type:', 'wpp'); ?></label>
      <select name="<?php echo $this_object->get_field_name('galleria_transition'); ?>"  id="<?php echo $this_object->get_field_id('galleria_transition') ?>">
        <option <?php selected($galleria_transition, 'fade'); ?> value="fade"><?php _e('Crossfade betweens images', 'wpp'); ?></option>
        <option <?php selected($galleria_transition, 'flash'); ?> value="flash"><?php _e('Fade into background color', 'wpp'); ?></option>
        <option <?php selected($galleria_transition, 'pulse'); ?> value="pulse"><?php _e('Pulse', 'wpp'); ?></option>
        <option <?php selected($galleria_transition, 'slide'); ?> value="slide"><?php _e('Slide', 'wpp'); ?></option>
        <option <?php selected($galleria_transition, 'fadeslide'); ?> value="fadeslide"><?php _e('Fadeslide', 'wpp'); ?></option>
      </select>
    </p>

    <p class="wpp_gallery_galleria_options">
      <input name="<?php echo $this_object->get_field_name('imageCrop'); ?>"  id="<?php echo $this_object->get_field_id('imageCrop') ?>" type="checkbox" <?php checked('true', $imageCrop); ?> value="true" />
      <label for="<?php echo $this_object->get_field_id('imageCrop') ?>"><?php _e('Crop images.', 'wpp'); ?></label>
    </p>

    <p class="wpp_gallery_galleria_options">
      <input name="<?php echo $this_object->get_field_name('enforce_minimum_image_width'); ?>"  id="<?php echo $this_object->get_field_id('enforce_minimum_image_width') ?>" type="checkbox" <?php checked('true', $enforce_minimum_image_width); ?> value="true" />
      <label for="<?php echo $this_object->get_field_id('enforce_minimum_image_width') ?>"><?php _e('Enforce minimum image width.', 'wpp'); ?></label>
    </p>

    <p class="wpp_gallery_galleria_options">
      <label for="<?php echo $this_object->get_field_id('carousel_thumb_size'); ?>"><?php _e('Carousel Thumbnail Size:'); ?></label>
      <?php WPP_F::image_sizes_dropdown("name=" . $this_object->get_field_name('carousel_thumb_size') . "&selected=" . $carousel_thumb_size); ?>
    </p>

    <p class="wpp_gallery_galleria_options" style="height: 5px;"></p>

    </div>
    <?php

  }

  /**
   * Take over property widget if it is a galleria widget.
   *
   * @since 3.5.0
   *
   */
  function widget_property_gallery($html, $data) {

    $post = $data['post'];
    $args = $data['args'];
    $instance = $data['instance'];

    if($instance['galleria_gallery'] != 'on') {
      return $html;
    }

    $s['large_size'] = $instance['big_image_type'];
    $s['image_size'] = $instance['image_type'];
    $s['thumb_size'] = $instance['carousel_thumb_size'];
    $s['transition'] = $instance['galleria_transition'];
    $s['enforce_minimum_image_width'] = $instance['enforce_minimum_image_width'];

    //** Ensure galleria script is included */
    WPP_F::force_script_inclusion('wp-property-galleria');

    $html['images'] = class_wpp_slideshow::display_gallery($s);

    return $html;

  }

  /**
   * Main front-end function.
   *
   * @since 3.5.0
   *
   */
  function template_redirect() {

    //** Detect if FEPS Page */
    if(WPP_F::detect_shortcode('property_gallery')) {
      self::enqueue_scripts();
    }



  }

  /**
   * Load any front-end scripts
   *
   * @since 3.5.0
   *
   */
  function enqueue_scripts() {
   wp_enqueue_script('wp-property-galleria');
  }


  /**
   * Perform any pre-header functions of single property pages.
   *
   * If ths current property has a slideshow, we pas it into 'wpp_property_page_vars' filter,
   * which then passed it into $wp_query->query_vars, which is then extracted on the single propety pages.
   *
   * @since 3.3.5
   *
   */
  function template_redirect_single_property() {

    //** Does not check if the slideshow is in header -> just that the property has slideshow images */
    if(class_wpp_slideshow::display_slideshow(array('type' => 'single'))) {
      add_filter('wpp_property_page_vars', create_function('$current' , ' $current[slideshow] = true; return $current; '), 20);
    }

  }

  /**
   * Renders ajax response to query for image selection
   *
   * Used on global slideshow admin page.
   *
   * @since 3.1
   *
   */
  function ajax_get_global_images() {
    global $wp_properties;

    if(!wp_verify_nonce($_REQUEST['_wpnonce'], 'wpp_get_global_images')) {
      die();
    }

    //** Get image size (set globally) */
    $image_type = $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'];

    $good_images = class_wpp_slideshow::get_global_images($_REQUEST['selection']);

    foreach((array)$good_images as $image_obj) {
      $image = $image_obj['image_id'];
      class_wpp_slideshow::draggable_image_block($image, $image_type,true,$image_obj);
    }

    die();
  }


/**
  * Load images based on size and location
  *
  * Used on global slideshow admin page.
  *
  * @since 3.1
  *
  */
function get_global_images($type = false) {
  global $wpdb, $wp_properties;

  $image_type = $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'];
  $image_sizes = WPP_F::image_sizes($image_type);

  if(!$type) {
    $type = 'featured_property_images';
  }


  switch($type) {

    case 'featured_property_images':
      $all_images = $wpdb->get_results("
      SELECT pm.post_id as image_id, pm.meta_value as dimensions, p.post_title as image_title, p2.post_title as property_title, p2.post_type as post_type, m2.meta_value featured_id, p2.ID as property_id
      FROM {$wpdb->postmeta} pm
      LEFT JOIN {$wpdb->posts} p on pm.post_id = p.ID
      LEFT JOIN {$wpdb->posts} p2 on p.post_parent = p2.ID
      LEFT JOIN {$wpdb->postmeta} m2 on pm.post_id = m2.meta_value
      WHERE pm.meta_key = '_wp_attachment_metadata'
      AND p2.post_type = 'property'
      AND m2.meta_value = pm.post_id
      AND m2.meta_key = '_thumbnail_id'
      AND p.post_mime_type IN ('image/jpeg','image/jpg','image/gif','image/png','image/bmp')");
    break;

    case 'all_property_images':
      $all_images = $wpdb->get_results("
      SELECT pm.post_id as image_id, pm.meta_value as dimensions, p.post_title as image_title, p2.post_title as property_title, p2.post_type as post_type, p2.ID as property_id
      FROM {$wpdb->postmeta} pm
      LEFT JOIN {$wpdb->posts} p on pm.post_id = p.ID
      LEFT JOIN {$wpdb->posts} p2 on p.post_parent = p2.ID
      WHERE pm.meta_key = '_wp_attachment_metadata'
      AND p2.post_type = 'property'
      AND p.post_mime_type IN ('image/jpeg','image/jpg','image/gif','image/png','image/bmp')");
    break;


    case 'all_images':
     $all_images = $wpdb->get_results("
      SELECT pm.post_id as image_id, pm.meta_value as dimensions, p.post_title as image_title, p2.post_title as property_title, p2.post_type as post_type, p2.ID as property_id
      FROM {$wpdb->postmeta} pm
      LEFT JOIN {$wpdb->posts} p on pm.post_id = p.ID
      LEFT JOIN {$wpdb->posts} p2 on p.post_parent = p2.ID
      WHERE pm.meta_key = '_wp_attachment_metadata'
      AND p.post_mime_type IN ('image/jpeg','image/jpg','image/gif','image/png','image/bmp')");
    break;

  }


  if(is_array($all_images)) {
    foreach($all_images as $count => $image) {
      $sizes = unserialize($image->dimensions);

       if($sizes['width'] >= $image_sizes['width']) {
        $good_images[$count]['image_id'] = $image->image_id;
        $good_images[$count]['iamge_title'] = $image->image_title;
        $good_images[$count]['property_title'] = $image->property_title;
        $good_images[$count]['post_type'] = $image->post_type;
      }

    }
  }

  return $good_images;
}

/**
  * Add draggable image selection pane to single property page
  *
  * Used on global slideshow admin page.
  *
  * @since 3.1
  *
  */
  function add_metabox(){
    add_meta_box( 'wp_property_slideshow', __( 'Slideshow Options', 'wpp' ), array('class_wpp_slideshow','slideshow_options'), 'property', 'normal' );
  }


/**
  * Draws the draggable image selection pane
  *
  *
  * @todo This function needs to be cleaned up.
  * @since 3.1
  *
  */
  function slideshow_options() {
  global $wpdb, $wp_properties, $post, $post_id;



  $thumb_type = (!empty($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
    && !is_numeric($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
      && $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width']  != '-' ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'] : 'thumbnail');

  $thumb_info = WPP_F::image_sizes($thumb_type);

  $thumb_width = $thumb_info['width'];
  $image_type  = $wp_properties['configuration']['feature_settings']['slideshow']['property']['image_size'];
  $prop_slideshow = WPP_F::image_sizes($image_type);

  if(empty($prop_slideshow['width']))
    $prop_slideshow['width'] = '640';

  if(empty($prop_slideshow['height']))
    $prop_slideshow['height'] = '235';


  /* Fix values if for some reason nothing is set */
  if(empty($thumb_width))
    $thumb_width = '250';

  //** Get current images */
  $current = get_post_meta( $post->ID, 'slideshow_images', 1 );
  $current = is_array( $current ) ? array_unique( $current ) : array();

  $gallery_images = get_post_meta( $post->ID, 'gallery_images', 1 );
  $gallery_images = is_array( $gallery_images ) ? array_unique( $gallery_images ) : array();
  $gallery_images = array_diff( $gallery_images, $current );

  if ( empty( $gallery_images ) ) {

    $args = array(
      'post_type' => 'attachment',
      'numberposts' => -1,
      'post_status' => null,
      'post_parent' => $post->ID
    );
    $attachments = get_posts($args);

    foreach((array)$attachments as $attachment){
      if($attachment->post_mime_type == 'image/jpeg' || $attachment->post_mime_type == 'image/png' || $attachment->post_mime_type == 'image/gif'){
        $all_images[] = $attachment;
      }
    }

  } else {

    $exclude = array();

    foreach( (array)$gallery_images as $gallery_image_id ){
      $exclude[] = $gallery_image_id;
      $attachment = get_post($gallery_image_id);
      if($attachment->post_mime_type == 'image/jpeg' || $attachment->post_mime_type == 'image/png' || $attachment->post_mime_type == 'image/gif'){
        $all_images[] = $attachment;
      }
    }

    $args = array(
      'post_type' => 'attachment',
      'numberposts' => -1,
      'post_status' => null,
      'post_parent' => $post->ID,
      'exclude' => array_merge( $exclude, $current )
    );
    $rest_images = get_posts($args);

    foreach((array)$rest_images as $attachment){
      if($attachment->post_mime_type == 'image/jpeg' || $attachment->post_mime_type == 'image/png' || $attachment->post_mime_type == 'image/gif'){
        $all_images[] = $attachment;
      }
    }

  }

  ?>

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
  width:<?php echo $thumb_info['thumb_width']; ?>px;
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
   <span class="wpp_selected_images_title"> <?php _e('Slideshow Images:','wpp') ?></span>
    <ul id="sortable2" class="connectedSortable clearfix">
   <?php
   if(is_array($current)):
    foreach($current as $curr_id):
       if(count($curr_id)>0){

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
    <span class="wpp_all_images_title"><?php _e('All Images:','wpp') ?></span>

    <div class="wpp_slideshow_global_all_inner_menu">
    <span class="description" id="wpp_slideshow_show_help">Help</span> |
    <span class="description" id="wpp_slideshow_auto_add">Auto Fix</span>
  </div>

   <ul id="sortable1" class="connectedSortable clearfix"  >
   <?php
   foreach((array)$all_images as $image) {
    $image_meta = wp_get_attachment_metadata($image->ID);
    $image_info = wp_get_attachment_image_src($image->ID,$image_type);
    $image_url = $image_info['width'];


    $cur = $current[0];
    /* skip if current */
    if(is_array($cur))
       if(in_array($image->ID, $cur))
        continue;

        class_wpp_slideshow::draggable_image_block($image->ID, $image_type);
   }
   ?>
   </ul>
  </div>
  </div>

  </div>


  <div style="clear:both"></div>

  <?php }

/**
  * Displays an image block for draggable selection lists.
  *
  * Used on global slideshow admin page.
  *
  * @since 3.1
  *
  */
  function draggable_image_block($image_id, $image_type, $echo = true, $image_obj = false) {
    global $wp_properties;

    $thumb_type = (!empty($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
      && !is_numeric($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
        && $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width']  != '-' ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'] : 'thumbnail');

    $image = wpp_get_image_link($image_id, $thumb_type, array('return'=>'array'));

    if(empty($image['link'])) {
      return;
    }

    $required_dimensions = WPP_F::image_sizes($image_type);

    //** Get original image sizes */
    $image_meta = wp_get_attachment_metadata($image_id);
    $primary_image_meta['width'] = $image_meta['width'];
    $primary_image_meta['height'] = $image_meta['height'];

    ob_start();

    ?>
      <li can_use="<?php echo $primary_image_meta['width'] < $required_dimensions['width'] ? 'false' : 'true' ?>" id="image_<?php echo $image_id; ?>"  class="wpp_slideshow_image_block">
        <img class="wpp_image_element" src="<?php echo $image['link']; ?>" style="<?php echo ($image['width'] ? "width: {$image['width']}px;" : ''); ?> <?php echo ($image['height'] ? "height: {$image['height']}px;" : ''); ?>" />

        <?php if($primary_image_meta['width'] < $required_dimensions['width']) { ?>
        <div class="wpp_slideshow_image_stats image_does_not_exist">
        <?php _e('Image is too small.','wpp'); ?>
        </div>
        <?php } ?>

      </li>
    <?php
    $return = ob_get_contents();
    ob_end_clean();

    if(!$echo) {
      return $return;
    }
    echo $return;
  }


  /**
   * Called in property_save to commit slideshow image selection to database.
   *
   *
   * @since 3.1
   *
   */
  function save_in_postmeta( $post_id ){
    if( !empty( $_POST['property_slideshow_image_array'] ) ) {
      /* fix array  */
      $string_array = $_POST['property_slideshow_image_array'];
      $string_array = str_replace('item=', '', $string_array);
      $image_array = explode('&', $string_array);
      update_post_meta($post_id,'slideshow_images', $image_array);
    } else {
      delete_post_meta( $post_id, 'slideshow_images' );
    }
    if( !empty( $_POST['property_gallery_image_array'] ) ) {
      /* fix array  */
      $string_array = $_POST['property_gallery_image_array'];
      $string_array = str_replace('item=', '', $string_array);
      $image_array = explode('&', $string_array);
      update_post_meta( $post_id,'gallery_images', $image_array );
    } else {
      delete_post_meta( $post_id, 'gallery_images' );
    }
  }


  /**
   * Adds slideshow manu to settings page navigation
   *
   * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
   */
  function settings_nav($tabs) {
    $tabs['slideshow'] = array(
      'slug' => 'slideshow',
      'title' => __('Slideshow','wpp')
    );
    return $tabs;
  }


  /**
   * Adds scripts and styles to slideshow pages.
   *
   * @since 3.1
   */
  function admin_menu() {

    $slideshow_page = add_submenu_page('edit.php?post_type=property', __('Slideshow','wpp'), __('Slideshow','wpp'), self::$capability, 'slideshow',array('class_wpp_slideshow', 'page_global_slideshow'));

    /* Insert Scripts */
    add_action('admin_print_scripts-' . $slideshow_page, create_function('', "wp_enqueue_script('jquery-ui-resizable'); wp_enqueue_script('wpp-jquery-fancybox');wp_enqueue_script( 'jquery-ui-sortable'); "));
    /* Insert Styles */
    add_action('admin_print_styles-' . $slideshow_page, create_function('', "wp_enqueue_style('wpp-jquery-fancybox-css');"));

  }



/**
  * Property Settings page - Slideshow Tab content
  *
  * @todo Code needs to be revised and cleaned up.
  * @since 3.1
  *
  */
  function settings_page() {
  global $wp_properties, $wpdb, $class_wpp_slideshow;

  $wp_properties['configuration']['feature_settings']['slideshow'] = WPP_F::array_merge_recursive_distinct($wp_properties['configuration']['feature_settings']['slideshow'], $class_wpp_slideshow);
  $glob_slideshow = $wp_properties['configuration']['feature_settings']['slideshow']['glob'];
  $property_slideshow = $wp_properties['configuration']['feature_settings']['slideshow']['property'];


  if(!isset($glob_slideshow['display_attributes']) || !is_array($glob_slideshow['display_attributes'])) {
    $glob_slideshow['display_attributes'] = array();
  }

  $wpp_slideshow_display_attribs['post_title'] = "Property Title";

  foreach($wp_properties['property_stats'] as $slug => $title) {
    $wpp_slideshow_display_attribs[$slug] = $title;
  }

    $dropdown_options['effect'] = array('sliceDown','sliceDownLeft','sliceUp','sliceUpLeft','sliceUpDown','sliceUpDownLeft','fold','fade','random','slideInRight','slideInLeft','boxRandom','boxRain','boxRainReverse','boxRainGrow','boxRainGrowReverse');
    $dropdown_options['slices'] = array('5', '10', '15', '20', '30', '40');
    $dropdown_options['animSpeed'] = array('100', '500', '1000', '2000', '5000');
    $dropdown_options['pauseTime'] = array('2500', '5000', '10000');


    if(empty($wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['effect']))
      $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['effect']  = 'fold';

    if(empty($wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['slices']))
      $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['slices']  = '20';

    if(empty($wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['animSpeed']))
      $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['animSpeed']  = '500';

    if(empty($wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['pauseTime']))
      $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['pauseTime']  = '5000';

  ?>
  <table class="form-table">

  <tr>
    <th><?php _e('Global Slideshow','wpp'); ?></th>
    <td>
      <ul>

      <li>
      <?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][glob][link_to_property]&label=".__('Clicking on an image will open up the property its attached to.','wpp'), $glob_slideshow['link_to_property']); ?>
      </li>

      <li>
      <?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][glob][show_property_title]&label=".__('Show property title and tagline in slideshow.','wpp'), $glob_slideshow['show_property_title']); ?>
      </li>

      <li>
        <label for="wpp_slideshow_global_size"><?php _e('Slideshow Size: ','wpp'); ?></label>
        <select id="wpp_slideshow_global_size" name="wpp_settings[configuration][feature_settings][slideshow][glob][image_size]">
          <option value=""></option>
         <?php
         $wpp_image_sizes = $wp_properties['image_sizes'];
         foreach(get_intermediate_image_sizes() as $slug){
          $selected = '';
          $slug = trim($slug);
          $image_dimensions = WPP_F::image_sizes($slug, "return_all=true");
            /* Skip images w/o dimensions */
            if(!$image_dimensions) continue;
          if ($glob_slideshow['image_size'] == $slug){
           $selected = 'selected="selected"';
          }
          echo '<option '.$selected.' value="'. $slug . '" >'. $slug .' - '. $image_dimensions['width'] .'px x '. $image_dimensions['height'] .'px</option>';

         }
         ?>
         </select>
         <br class="cb" />
         <span class="description">
		 <?php printf(__('The global slideshow will look for all images that are over the specified size.  Add images to the global slideshow <a href="%1$s">here</a>. The built-in sizes, such as <b>medium</b> and <b>large</b> may not work because WordPress does not crop them during resizing, resulting in artbitrary heights. ','wpp'), admin_url("edit.php?post_type=property&page=slideshow"));?>

         </span>
         </li>
       </ul>

    </td>
  </tr>

  <tr>
    <th><?php _e('Display in Global Slideshow Caption:','wpp'); ?></th>
    <td>
      <ul>

        <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][glob][show_title]&label=" . __('Title.','wpp'), $glob_slideshow['show_title']); ?></li>
        <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][glob][show_excerpt]&label=" . __('Excerpt.','wpp'), $glob_slideshow['show_excerpt']); ?></li>
        <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][glob][show_tagline]&label=" . __('Tagline.','wpp'), $glob_slideshow['show_tagline']); ?></li>
    </ul>
    </td>
  </tr>

   <tr>
    <th><?php _e('Single Listing Slideshow','wpp'); ?></th>
    <td>
      <ul>

        <li>
          <select name="wpp_settings[configuration][feature_settings][slideshow][property][image_size]">
            <option value=""></option>
            <?php
            $wpp_image_sizes = $wp_properties['image_sizes'];
            foreach(get_intermediate_image_sizes() as $slug){
              $selected = '';
              $slug = trim($slug);
              $image_dimensions = WPP_F::image_sizes($slug, "return_all=true");
              /* Skip images w/o dimensions */
              if(!$image_dimensions) continue;
              if ($property_slideshow['image_size'] == $slug){
                $selected = 'selected="selected"';
              }
              echo '<option '.$selected.' value="'. $slug .'">'. $slug .' - '. $image_dimensions['width'] .'px x '. $image_dimensions['height'] .'px</option>';
            }
            ?>
          </select>
          <br />
          <span class="description"><?php _e('Slideshow image size to be used for single property pages.','wpp'); ?></span>
        </li>

        <li>
          <?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][property][navigation]&label=" . __('Show pagination buttons in slideshow.','wpp'), $property_slideshow['navigation']); ?>
        </li>

      </ul>
    </td>
   </tr>

  <tr>
    <th>
    <?php _e('Thumbnail Size','wpp'); ?>
    </th>
    <td>
      <p>
        <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][feature_settings][slideshow][glob][thumb_width]&selected={$glob_slideshow['thumb_width']}"); ?>
        <span class="description"><?php _e( 'This width is used on "Slideshow" page on the Property editing page to display available images, this setting <b>does not</b> affect the actual slideshow on the front-end.', 'wpp' ); ?></span>
      </p>
    </td>
   </tr>
   <tr>
    <th>
    <?php _e('Settings','wpp'); ?>
    </th>
    <td>
      <ul>
        <li>
          <label for="wpp_sllideshow_effect"><?php _e('Effect:', 'wpp'); ?></label>
          <select id="wpp_sllideshow_effect" name="wpp_settings[configuration][feature_settings][slideshow][glob][settings][effect]">
            <?php foreach($dropdown_options['effect']  as $effect): ?>
            <option <?php selected($effect,$wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['effect']); ?>value="<?php echo $effect; ?>"><?php echo $effect; ?></option>
            <?php endforeach; ?>
          </select>
          <span class="description"><?php _e('Effect that will be used to change from one image to the next.', 'wpp'); ?></span>
        </li>

        <li>
          <label for="wpp_sllideshow_slices"><?php _e('Slices:', 'wpp'); ?></label>
          <select id="wpp_sllideshow_slices" name="wpp_settings[configuration][feature_settings][slideshow][glob][settings][slices]">
            <?php foreach($dropdown_options['slices']  as $slices): ?>
            <option <?php selected($slices,$wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['slices']); ?>value="<?php echo $slices; ?>"><?php echo $slices; ?></option>
            <?php endforeach; ?>
          </select>
          <span class="description"><?php _e('If the transition includes slices, this number determines how many slices to cut the image up into during transitions.', 'wpp'); ?></span>
        </li>

        <li>
          <label for="wpp_sllideshow_anim_speed"><?php _e('Animation Speed:', 'wpp'); ?></label>
          <select id="wpp_sllideshow_slices" name="wpp_settings[configuration][feature_settings][slideshow][glob][settings][animSpeed]">
            <?php foreach($dropdown_options['animSpeed']  as $animSpeed): ?>
            <option <?php selected($animSpeed,$wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['animSpeed']); ?>value="<?php echo $animSpeed; ?>"><?php echo $animSpeed; ?></option>
            <?php endforeach; ?>
          </select>
          <span class="description"><?php _e('How quickly the transition should happen, in miliseconds.', 'wpp'); ?></span>
        </li>

        <li>
          <label for="wpp_sllideshow_pause"><?php _e('Pause Time:', 'wpp'); ?></label>
          <select id="wpp_sllideshow_pause" name="wpp_settings[configuration][feature_settings][slideshow][glob][settings][pauseTime]">
            <?php foreach($dropdown_options['pauseTime']  as $pauseTime): ?>
            <option <?php selected($pauseTime,$wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['pauseTime']); ?>value="<?php echo $pauseTime; ?>"><?php echo $pauseTime; ?></option>
            <?php endforeach; ?>
          </select>
          <span class="description"><?php _e('The pause time between transitions.', 'wpp'); ?></span>
        </li>

      </ul>

    </td>
   </tr>



  </table>

  <?php
  }





/**
  * Global slideshow selection page.
  *
  * @todo Make image selection panes resizable.
  * @since 3.1
  *
  */
  function page_global_slideshow() {
  /* Get all images that are big enough  */
  global $wpdb, $wp_properties;


  $image_type = $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'];
  $image_sizes = WPP_F::image_sizes($image_type);

  $glob_slideshow[0]  = $image_sizes['width'];
  $glob_slideshow[1]  = $image_sizes['height'];

  if(empty($glob_slideshow[0]) || empty($glob_slideshow[0])) {
  ?>
  <div class="wrap">
    <h2><?php _e('Global Slideshow','wpp'); ?></h2>
    <p>
    Please visit the <a href="<?php echo admin_url("edit.php?post_type=property&page=property_settings#tab_slideshow"); ?>">slideshow settings page</a> and select the global slideshow size first.
    </p>
  </div>
  <?php
  return;
  }


  $thumb_type = (!empty($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
    && !is_numeric($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
      && $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width']  != '-' ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'] : 'thumbnail');

  $thumb_info = WPP_F::image_sizes($thumb_type);

  $image_type  = $wp_properties['configuration']['feature_settings']['slideshow']['property']['image_size'];
  $prop_slideshow = WPP_F::image_sizes($image_type);

  if(empty($prop_slideshow['width']))
    $prop_slideshow['width'] = '640';

  if(empty($prop_slideshow['height']))
    $prop_slideshow['height'] = '235';

  /* If updating  */
  if(wp_verify_nonce($_REQUEST['_wpnonce'] , 'wpp_update_slideshow')
    && isset($_POST['slideshow_image_array'])) {

    /* fix array  */
    $string_array = $_POST['slideshow_image_array'];
    $string_array = str_replace('item=', '', $string_array);

    if($string_array)
    $image_array = explode('&', $string_array);

    update_option('class_wpp_slideshow_image_array', $image_array);

   require_once(ABSPATH . '/wp-admin/includes/image.php');

    if(is_array($image_array))
      foreach((array)$image_array as $image){
        $orig_image = get_attached_file($image);
        $resized_path = image_resize($orig_image, $glob_slideshow[0], $glob_slideshow[1], true);
      }

    $updated = __('Slideshow selection and order saved.','wpp');
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
  width:<?php echo $thumb_info['thumb_width']; ?>px;
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
  </style><!-- styles -->


  <script type="text/javascript">

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

  });

  function wpp_slideshow_get_global_images() {

    var selection = jQuery('option:selected', jQuery("#wpp_slideshow_global_filter")).val();

    jQuery("#sortable1").html('<?php _e('Loading...', 'wpp'); ?>');

    jQuery.post( wpp.instance.ajax_url, {action:"wpp_slideshow_get_global_images", selection: selection, _wpnonce: '<?php echo wp_create_nonce('wpp_get_global_images'); ?>'}, function(result) {
      jQuery("#sortable1").html(result);
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
    var order = jQuery('#sortable2').sortable('serialize', {key: 'item'});
    if (order != '' ) {
      jQuery("#slideshow_image_array").val(order);
    }else{
      jQuery("#slideshow_image_array").val('item=');
    }
  }


  </script><!-- sorting scripts -->

  <div class="wrap">
  <h2><?php _e('Global Slideshow','wpp'); ?></h2>

  <?php if($updated): ?>
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
       <strong><?php _e('WP-Property Slideshow','wpp'); ?></strong>
       <p><?php _e('This slideshow can be integrated into your front-end pages by either using the shortcode, or pasting PHP code into your theme.','wpp'); ?></p>
    </div>
    <div class="wpp_box_content">
       <p><?php _e('Drag images from selection on the left to the selection on the right, and then click save.','wpp'); ?></p>
       <p><?php echo sprintf(__('This list gets all images from your media library that are over %s pixels wide and %s pixels tall.','wpp'), $glob_slideshow[0], $glob_slideshow[1]); ?></p>
       <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpp_update_slideshow'); ?>" />
       <input type="hidden" name="slideshow_image_array" id="slideshow_image_array" value="" />
    </div>


    <div class="wpp_box_footer">
        <input type="submit" value="<?php _e('Save Selection and Order','wpp') ?>" accesskey="p" tabindex="4" id="publish" class="button-primary btn" name="save">
    </div>
   </div>
  </form>



  <div class="wpp_slideshow_images">
  <div class="wpp_slideshow_global_selected image_block">
   <span class="wpp_selected_images_title"> <?php _e('Slideshow Images:','wpp') ?></span>
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
   <span class="wpp_all_images_title"><?php _e('All Images:','wpp') ?></span>

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
   </ul>
  </div>
  </div>



  </div><!-- /div.wrap -->
  </div><!-- /div.wrap -->


  <?php
  }



/**
  * Main function for rendering a slideshow.
  *
  * http://galleria.aino.se/docs/1.2/options/#list-of-options
  *
  * @param array settings Settings passed by shortcodes to identify the type of slideshow, or configure it.
  *
  * @since 3.5.0
  */
  function display_gallery($settings = false) {
    global $wp_query, $wp_properties;

    $defaults = array(
      'width' => 'auto',
      'height' => 'auto',
      'autoplay' => false,
      'transition' => 'fade',
      'enforce_minimum_image_width' => 'true',
      'debug' => 'false',

      'thumb_size' => 'thumbnail',
      'image_size' => 'medium',
      'large_size' => 'large',

      'hide_if_no_images' => 'false',

      'show_info' => 'true',
      'carousel' => 'true',
      'image_crop' => 'true',
      'image_pan' => 'true',
      'theme' => 'classic/galleria.classic.min.js',
      'element' => 'wpp_gallery_' . rand(1000,9999)
    );

    if($wp_properties['configuration']['developer_mode'] == 'true') {
      $defaults['debug'] = 'true';
    }

    //** Merge default settings with the passed through settings */
    $s = array_merge($defaults, (array)$settings);

    if(is_array($wp_query->query_vars['property'])) {
      $gallery = (array)$wp_query->query_vars['property']['gallery'];
      //** Get gallery images order */
      $gallery_order = array_filter((array)maybe_unserialize($wp_query->query_vars['property']['gallery_images']));
      //** Get slideshow images order */
      $slideshow_order = array_filter((array)maybe_unserialize($wp_query->query_vars['property']['slideshow_images']));
    }

    if(is_object($wp_query->query_vars['property'])) {
      $gallery = (array)$wp_query->query_vars['property']->gallery;
      //** Get gallery images order */
      $gallery_order = array_filter((array)maybe_unserialize($wp_query->query_vars['property']->gallery_images));
      //** Get slideshow images order */
      $slideshow_order = array_filter((array)maybe_unserialize($wp_query->query_vars['property']->slideshow_images));
    }

    if(empty($gallery)) {
      return false;
    }

    if(!$s['image_size']) {
      return false;
    }

    $theme_locations = apply_filters('wpp_property_gallery_theme_locations', array(
      WPP_Path .'third-party/galleria/themes',
      STYLESHEETPATH
    ));

    if(!is_array($theme_locations)) {
      return false;
    }

    foreach($theme_locations as $theme_path) {

      //** Locate theme */
      if(file_exists( $theme_path . '/' . $s['theme'])) {
        $found_theme = $theme_path . '/' . $s['theme'];
        break;
      }

    }

    if(!$found_theme) {
      return false;
    }

    /* Replace dir path to URL */
    $found_theme = str_replace('\\', '/', $found_theme);
    $path = str_replace('\\', '/', ABSPATH);
    $theme_url = str_replace($path, get_bloginfo('wpurl') . '/', $found_theme);

    $expected_dimensions = WPP_F::image_sizes($s['image_size']);

    $prepared_gallery_images = array();

    //** Calculate order of images */
    if ( is_array($slideshow_order) && is_array($gallery_order) ) {
      $order = array_merge($slideshow_order, $gallery_order);

      //** Get images from the list of images by order */
      foreach( $order as $order_id ) {
        foreach( $gallery as $image_slug => $gallery_image_data ) {
          if ( $gallery_image_data['attachment_id'] == $order_id ) {
            $prepared_gallery_images[$image_slug] = $gallery_image_data;
            break;
          }
        }
      }

      //** Be sure we show ALL property images in gallery */
      // $gallery = array_merge($prepared_gallery_images, $gallery);
      // array_merge doesn't store keys, let's use + instead
      $gallery = $prepared_gallery_images + $gallery;

    }

    foreach($gallery as $single) {

      $single = (array) $single;

      //** Check in case get_posts() result is passed */
      if(empty($single['attachment_id']) && isset($single['ID'])) {
        $single['attachment_id'] = $single['ID'];
      }

      if(empty($single['attachment_id'])) {
        continue;
      }

      if(!empty($single['link_url'])) {
        $single_object['link_url'] = $single['link_url'];
      }

      $image = wpp_get_image_link($single['attachment_id'], $s['image_size'], array('return'=>'array'));

      if($image['width'] != $expected_dimensions['width'] && $s['enforce_minimum_image_width'] == 'true') {
        continue;
      }

      $single_object['image'] = $image['link'];

      if($s['thumb_size']) {
        $thumb = wpp_get_image_link($single['attachment_id'], $s['thumb_size'], array('return'=>'array'));
        $single_object['thumb'] = $thumb['link'];
      }

      if($s['large_size']) {
        $big = wpp_get_image_link($single['attachment_id'], $s['large_size'], array('return'=>'array'));
        $single_object['big'] = $big['link'];
      }

      $single_object['title'] = $single['post_title'];
      $single_object['description'] = $single['post_content'];
      $gallery_data[] = $single_object;

      //** Set width and height to be used for wrapper based on image size */
      $width = ($image['width'] ? $image['width'] . 'px' : false);

      //** Add 60 pixels to compensate for the thumbnail size, which may be changed in CSS */
      $height = ($image['height'] ? $image['height'] + 60 . 'px' : false);
    }

    if(count($gallery_data) <1) {

      $no_images = true;

      if($s['hide_if_no_images'] == 'true') {
        return;
      }

      $element_class[] = 'wpp_galleria_no_images';

    }

    $element_class[] = $s['element'] . '_wrapper';
    $element_class[] = 'wpp_galleria_wrapper';

    if(!empty($s['width']) && is_numeric($s['width'])) {
      $s['width'] = $s['width'] . 'px';
    }

    if(!empty($s['height']) && is_numeric($s['height'])) {
      $s['height'] = $s['height'] . 'px';
    }

    if(empty($s['width']) || ($s['width'] == 'auto' && !empty($width))) {
      $s['width'] = $width;
    }

    if(empty($s['height']) || ($s['height'] == 'auto' && !empty($width))) {
      $s['height'] = $height;
    }

    ob_start();

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

    $html = ob_get_contents();
    ob_end_clean();


    return $html;

  }



/**
  * Main function for rendering a slideshow.
  *
  * @todo Finish function to automatically load images (tag:automatically_load_images)
  * @param array settings Settings passed by shortcodes to identify the type of slideshow, or configure it.
  * @since 3.1
  *
  */
  function display_slideshow($settings = false) {
    global $wp_properties, $wpdb;

    //** Support for legacy functions where first variable is the $post_id */
    if(is_numeric($settings)) {
      $post_id = $settings;
    }

    if($post_id) {
      //** Check if $post_id was passed (shortcode function does not do this) */
      $post_id = $post_id;
    }elseif(!empty($settings['id'])) {
      //** Check if post_id was passed using $atts */
      $post_id = $settings['id'];
    }elseif(!$post_id && $settings['type'] == 'single' ) {
      //** Get post_id form global variable */
      global $post;
      $post_id = $post->ID;
    } else {
      //** No post ID passed - global slideshow? */
      if(empty($settings['type']) || $settings['type'] != 'global'){

        return false;
      }
    }


    $defaults = array(
      'effect' => ($wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['effect'] ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['effect'] : 'fold'),
      'slices' => ($wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['slices'] ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['slices'] : '20'),
      'animation_speed' => ($wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['animSpeed'] ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['animSpeed'] : '500'),
      'pause_time' => ($wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['pauseTime'] ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['pauseTime'] : '5000'),
      'image_size' => false,
      'automatically_load_images' => false,
      'show_pagination_buttons' => ($wp_properties['configuration']['feature_settings']['slideshow']['property']['navigation'] ? $wp_properties['configuration']['feature_settings']['slideshow']['property']['navigation'] : 'false'),
      'show_side_navigation' => 'true',
      'show_side_navigation_on_hover_only' => 'true',
      'caption_opacity' => '0.8'
    );


    //** Merge default settings with the passed through settings */
    if($settings) {
      $settings = array_merge($defaults, $settings);
    } else {
      $settings = $defaults;
    }

    do_action( 'wpp::slideshow::property_slideshow', $settings, !empty( $post_id ) ? $post_id : false );

    if($settings['type'] == 'global') {
      //** Perform global slideshow specific actions */
      $images = get_option('class_wpp_slideshow_image_array', false);

      if(empty($settings['image_size'])) {
        $settings['image_size'] = $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'];
      }

      if(empty($settings['link_to_property']) && $wp_properties['configuration']['feature_settings']['slideshow']['glob']['link_to_property'] == 'true') {
        $settings['link_to_property'] = 'true';
      }

      if(empty($settings['show_title']) && $wp_properties['configuration']['feature_settings']['slideshow']['glob']['show_title'] == 'true') {
        $settings['show_title'] = 'true';
      }

      if(empty($settings['show_tagline']) && $wp_properties['configuration']['feature_settings']['slideshow']['glob']['show_tagline'] == 'true') {
        $settings['show_tagline'] = 'true';
      }

      if(empty($settings['show_excerpt']) && $wp_properties['configuration']['feature_settings']['slideshow']['glob']['show_excerpt'] == 'true') {
        $settings['show_excerpt'] = 'true';
      }


    } elseif ($settings['type'] == 'single') {

      //** Perform single slideshow specific actions */
      $images = get_post_meta($post_id, 'slideshow_images', true);
      //** tag:automatically_load_images */

      if(empty($settings['image_size'])) {
        $settings['image_size'] = $wp_properties['configuration']['feature_settings']['slideshow']['property']['image_size'];
      }
    }

    $needed_image_size = WPP_F::image_sizes($settings['image_size']);

    if(empty($images) || !is_array($images)) {
      return false;
    }

    //** Build image array (and regenerate them if necessary using wpp_get_image_link() */
    foreach((array)$images as $image_id){

      if (!is_numeric($image_id)) {
        continue;
      }

      $image_object = wpp_get_image_link($image_id, $settings['image_size'], array('return'=>'array'));


      /* Only check widths  - (on purpose because not all image sizes are cropped) */
      if(($needed_image_size['width'] != $image_object['width'])) {
        continue;
      }

      if(!empty($image_object['link'])) {

        $this_caption = array();

        $parent_id = $wpdb->get_var("SELECT post_parent FROM {$wpdb->prefix}posts WHERE ID = '$image_id'");
        $property_data = $wpdb->get_row("SELECT *  FROM {$wpdb->prefix}posts WHERE ID = '$parent_id' AND post_status = 'publish'");
        $post_title = $property_data->post_title;
        $post_excerpt = $property_data->post_excerpt;
        $property_tagline = get_post_meta($parent_id, 'tagline', true);


        if(!empty($post_title) && $settings['show_title'] == 'true') {
          $this_caption[] = $post_title;
        }

        if(!empty($property_tagline) && $settings['show_tagline'] == 'true') {
          $this_caption[] = '<span>'.$property_tagline.'</span>';
        }

        if(!empty($post_excerpt) && $settings['show_excerpt'] == 'true') {
          $this_caption[] = '<span>'.$post_excerpt.'</span>';
        }

        $this_caption = 'title="' .  implode(" " , $this_caption) . '"';

        $this_image = "<img src=\"{$image_object['link']}\" width=\"{$image_object['width']}\" height=\"{$image_object['height']}\" {$this_caption} />";


        if($settings['link_to_property'] == 'true') {
          $parent_link = get_permalink($parent_id);
          $print_image = "<a href='{$parent_link}' class='wpp_global_slideshow_link'>{$this_image}</a>";
        } else {
          $print_image = $this_image;
        }
        $print_images[] = apply_filters( 'wpp::slideshow::print_image', $print_image, $image_id, $settings );

      }

    }

    if(!empty($print_images)) {

      //** Create random class to ID slider */
      $this_slider_id = 'slider_' . rand(10000,99999);

      $nivoSlider['effect']  = "effect: '{$settings['effect']}'";
      $nivoSlider['slices']  = "slices: {$settings['slices']}";
      $nivoSlider['animSpeed']  = "animSpeed: {$settings['animation_speed']}";
      $nivoSlider['pauseTime']  = "pauseTime: {$settings['pause_time']}";
      $nivoSlider['directionNav']  = "directionNav: {$settings['show_side_navigation']}";
      $nivoSlider['directionNavHide']  = "directionNavHide: {$settings['show_side_navigation_on_hover_only']}";
      $nivoSlider['controlNav']  = "controlNav: {$settings['show_pagination_buttons']}";
      $nivoSlider['captionOpacity']  = "captionOpacity: {$settings['caption_opacity']}";
      $nivoSlider = apply_filters('wpp_slideshow_nivoslider', $nivoSlider, $settings );

      ob_start(); ?>
      <script type="text/javascript">jQuery(window).load(function() {jQuery('div.<?php echo $this_slider_id; ?>').nivoSlider({<?php echo implode(',', $nivoSlider); ?>});});</script>

      <style type='text/css'>
        .<?php echo $this_slider_id; ?> {display:block; width:<?php echo $image_object['width'] ?>px; height:<?php echo $image_object['height']; ?>px;}
        .<?php echo $this_slider_id; ?> img {display:none;}
        .<?php echo $this_slider_id; ?> .nivo-controlNav {position:absolute; bottom: -18px;}
      </style>

      <div class="<?php echo $this_slider_id; ?> slider <?php echo $settings['class']; ?>"><?php echo  implode("", $print_images); ?></div>

      <?php
      $return_result = ob_get_contents();
      ob_end_clean();
    }


    return $return_result;

  }


  /**
  * Used for returning a property-specific slideshow via shortcode
  *
  *
  * @since 3.1
  *
  */
  function shortcode_property_gallery( $atts = '') {

    //** Ensure galleria script is included */
    WPP_F::force_script_inclusion('wp-property-galleria');

    return class_wpp_slideshow::display_gallery($atts);

  }


  /**
   * Used for returning a property-specific slideshow via shortcode
   *
   *
   * @since 3.1
   *
   */
  function shortcode_property_slideshow( $atts = '') {
     $atts['type'] = 'single';

    return class_wpp_slideshow::display_slideshow($atts);

  }

  /**
  * Used for returning the global slideshow via shortcode
  *
  *
  * @todo Consider adding $atts calls here to adjust inline styles
  * @since 3.1
  *
  */
  function shortcode_global_slideshow($atts = '') {
    global $post_id, $image_size, $wp_properties;

    $image_size = $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'];

    $glob_slideshow = WPP_F::image_sizes($image_size);

    $atts['type'] = 'global';

    $return = class_wpp_slideshow::display_slideshow($atts);

    if(empty($return)) {
      return;
    }

    return "<div class='wpp_slideshow_global_wrapper wpp_global_slideshow_shortcode' style='position: relative; width:{$glob_slideshow['width']}px;height:{$glob_slideshow['height']}px;margin: 0 auto;'>{$return}</div>";
  }

}

  if(!function_exists('global_slideshow')) {
 /**
    * Used for calling the function programatically
    *
    * Pointless function, in most cases do_shortcode() should be used.
    *
    * @since 3.1
    *
    */
    function global_slideshow( $return = false){

      $content = do_shortcode("[global_slideshow]");

      if($return) {
        return $content;
      }

      echo $content;

    }
  }


  if(!function_exists('property_slideshow')) {
 /**
    * DEPRECIATED FUNCTION. SHOULD BE REMOVED IN THE NEXT REALEASES. MAXIM PESHKOV
    * I don't see any places where this function is used.
    *
    * Used for calling the function programatically
    *
    * Pointless function, in most cases do_shortcode() should be used.
    *
    * @since 3.1
    *
    */
    function property_slideshow($post_id = false, $return = false, $atts = ''){

      if($post_id) {
        $post_id = " id={$post_id} ";
      } else {
        global $post;
        $post_id = " id={$post->ID} ";
      }

      $content = do_shortcode("[property_slideshow {$post_id} ]");

      if($return) {
        return $content;
      }

      echo $content;

    }

  }
