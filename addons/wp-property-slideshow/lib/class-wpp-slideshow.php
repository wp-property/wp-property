<?php
/**
 * Core
 */

class class_wpp_slideshow {

  /*
   * (custom) Capability to manage the current feature
   */
  static protected $capability = "manage_wpp_slideshow";

  /**
   * Special functions that must be called prior to init
   *
   */
  static public function pre_init() {
    /* Add capability */
    add_filter('wpp_capabilities', array('class_wpp_slideshow', "add_capability"));
  }

  /**
   * Primary feature function.  Ran an init level.
   *
   * @since 3.1
   */
  static public function init() {
    global $wp_properties;
    
    //** Load default settings if they do not exist */
    if( !isset( $wp_properties['configuration']['feature_settings']['slideshow'] ) ) {
      $wp_properties['configuration']['feature_settings']['slideshow'] = array(
        'glob' => array(
          'dimensions' => '800x350',
          'thumb_width' => 300,
        ),
        'property' => array(
          'dimensions' => '640x235',
        ),
        'thumb_width' => 300
      );
      update_option('wpp_settings', $wp_properties );
    }

    if( current_user_can( self::$capability ) ) {
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
    add_action('wp_enqueue_scripts', function() { wp_enqueue_script('wpp-jquery-nivo-slider'); });

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
  static public function add_capability($capabilities) {

    $capabilities[self::$capability] = __('Manage Slideshow',ud_get_wpp_slideshow()->domain);

    return $capabilities;
  }


  /*
   * Hook into property_overview shortcode defaults and add extra args so shortcode_atts() allows the passed arguments
   *
   * @author potanin@UD
   * @since 3.6.0
   */
  static public function shortcode_property_overview_allowed_args($defaults = false, $atts = false) {

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
  static public function shortcode_property_overview_content($result, $wpp_query) {
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
  static public function admin_init() {

    //** Detect if FEPS Page */
    add_action('wpp_widget_slideshow_bottom', array('class_wpp_slideshow', 'widget_gallery'));
    add_filter( 'rwmb_wpp_media_value', array( 'class_wpp_slideshow', 'wpp_media_to_gallery_images'), 10, 3);

  }

  /**
   *
   */
  static public function widget_gallery($args) {

    $this_object = $args['this_object'];
    $instance = $args['instance'];

    $galleria_gallery = isset( $instance['galleria_gallery'] ) ? $instance['galleria_gallery'] : false;
    $galleria_transition = isset( $instance['galleria_transition'] ) ? $instance['galleria_transition'] : false;
    $imageCrop = isset( $instance['imageCrop'] ) ? $instance['imageCrop'] : false;
    $carousel_thumb_size = isset( $instance['carousel_thumb_size'] ) ? $instance['carousel_thumb_size'] : false;
    $enforce_minimum_image_width = isset( $instance['enforce_minimum_image_width'] ) ? $instance['enforce_minimum_image_width'] : false;

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

         jQuery(document).on("change", ".<?php echo $this_id; ?> .wpp_toggle_galleria_options", function() {
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
      <label for="<?php echo $this_object->get_field_id('galleria_gallery') ?>"><?php _e('Display as carousel gallery.', ud_get_wpp_slideshow()->domain); ?></label>
    </p>

    <p class="wpp_gallery_galleria_options">
      <label for="<?php echo $this_object->get_field_id('galleria_transition') ?>"><?php _e('Gallery transition type:', ud_get_wpp_slideshow()->domain); ?></label>
      <select name="<?php echo $this_object->get_field_name('galleria_transition'); ?>"  id="<?php echo $this_object->get_field_id('galleria_transition') ?>">
        <option <?php selected($galleria_transition, 'fade'); ?> value="fade"><?php _e('Crossfade betweens images', ud_get_wpp_slideshow()->domain); ?></option>
        <option <?php selected($galleria_transition, 'flash'); ?> value="flash"><?php _e('Fade into background color', ud_get_wpp_slideshow()->domain); ?></option>
        <option <?php selected($galleria_transition, 'pulse'); ?> value="pulse"><?php _e('Pulse', ud_get_wpp_slideshow()->domain); ?></option>
        <option <?php selected($galleria_transition, 'slide'); ?> value="slide"><?php _e('Slide', ud_get_wpp_slideshow()->domain); ?></option>
        <option <?php selected($galleria_transition, 'fadeslide'); ?> value="fadeslide"><?php _e('Fadeslide', ud_get_wpp_slideshow()->domain); ?></option>
      </select>
    </p>

    <p class="wpp_gallery_galleria_options">
      <input name="<?php echo $this_object->get_field_name('imageCrop'); ?>"  id="<?php echo $this_object->get_field_id('imageCrop') ?>" type="checkbox" <?php checked('true', $imageCrop); ?> value="true" />
      <label for="<?php echo $this_object->get_field_id('imageCrop') ?>"><?php _e('Crop images.', ud_get_wpp_slideshow()->domain); ?></label>
    </p>

    <p class="wpp_gallery_galleria_options">
      <input name="<?php echo $this_object->get_field_name('enforce_minimum_image_width'); ?>"  id="<?php echo $this_object->get_field_id('enforce_minimum_image_width') ?>" type="checkbox" <?php checked('true', $enforce_minimum_image_width); ?> value="true" />
      <label for="<?php echo $this_object->get_field_id('enforce_minimum_image_width') ?>"><?php _e('Enforce minimum image width.', ud_get_wpp_slideshow()->domain); ?></label>
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
  static public function widget_property_gallery($html, $data) {

    $post = $data['post'];
    $args = $data['args'];
    $instance = $data['instance'];

    if( !isset( $instance['galleria_gallery'] ) || $instance['galleria_gallery'] != 'on' ) {
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
  static public function template_redirect() {

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
  static public function enqueue_scripts() {
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
  static public function template_redirect_single_property() {

    //** Does not check if the slideshow is in header -> just that the property has slideshow images */
    if(class_wpp_slideshow::display_slideshow(array('type' => 'single'))) {
      add_filter('wpp_property_page_vars', function($current) { $current['slideshow'] = true; return $current; }, 20);
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
  static public function ajax_get_global_images() {
    global $wp_properties;

    $output = '';
    if(!wp_verify_nonce($_REQUEST['_wpnonce'], 'wpp_get_global_images')) {
      die();
    }

    //** Get image size (set globally) */
    $image_type = $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'];

    $limit = isset($_REQUEST['limit'])?$_REQUEST['limit']:25;
    $start = isset($_REQUEST['start'])?(int) $_REQUEST['start']:0;

    $result = class_wpp_slideshow::get_global_images($_REQUEST['selection'], $limit, $start, true);
    $good_images = $result['good_images'];
    $more_image = true;//($result['good_images_count'] < $limit)? false:true;

    foreach((array)$good_images as $id => $image_obj) {
      $image = $image_obj['image_id'];
      $output .= class_wpp_slideshow::draggable_image_block($image, $image_type,false,$good_images[$id]);
    }
    wp_send_json(array('html' => $output, 'good_images_count' => $result['good_images_count'], 'total_count' => $result['total_count'], 'more_image' => $more_image));
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
  static public function get_global_images($type = false, $limit = 25, $start = 0, $total_count = false) {
    global $wpdb, $wp_properties;
    $good_images = array();
    $total_count = $start;
    $good_images_count = 0;
    $image_type = $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'];
    $image_sizes = WPP_F::image_sizes($image_type);

    if(!$type) {
      $type = 'featured_property_images';
    }

    while ( $good_images_count < $limit) {
      $all_images = self::_get_global_images($type, $limit, $total_count);
      if( is_array( $all_images ) && count( $all_images ) > 0 ) {
        foreach($all_images as $image) {
          if($good_images_count >= $limit)
            break 2;
          $total_count++;
          $sizes = unserialize($image->dimensions);
          if($sizes['width'] >= $image_sizes['width']) {
            $good_images_count++;
            $good_images[$total_count]['image_id'] = $image->image_id;
            $good_images[$total_count]['iamge_title'] = $image->image_title;
            $good_images[$total_count]['property_title'] = $image->property_title;
            $good_images[$total_count]['post_type'] = $image->post_type;
          }

        }
      }
      else
        break;
    }

    if($total_count == false)
      return $good_images;
    else
      return array( 'good_images' => $good_images, 'good_images_count' => $good_images_count, 'total_count' => $total_count);
  }


  /**
   * Load images based on size and location
   *
   * Used on global slideshow admin page.
   *
   * @since 3.1
   *
   */
  static private function _get_global_images($type = false, $limit = 25, $start = 0) {
    global $wpdb, $wp_properties;
    $image_type = $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'];
    $image_sizes = WPP_F::image_sizes($image_type);

    if(!$type) {
      $type = 'featured_property_images';
    }

    $limit_query = "";
    if($limit != -1)
      $limit_query = " LIMIT $start, $limit ";

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
        AND p.post_mime_type IN ('image/jpeg','image/jpg','image/gif','image/png','image/bmp') $limit_query");
      break;

      case 'all_property_images':
        $all_images = $wpdb->get_results("
        SELECT pm.post_id as image_id, pm.meta_value as dimensions, p.post_title as image_title, p2.post_title as property_title, p2.post_type as post_type, p2.ID as property_id
        FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p on pm.post_id = p.ID
        LEFT JOIN {$wpdb->posts} p2 on p.post_parent = p2.ID
        WHERE pm.meta_key = '_wp_attachment_metadata'
        AND p2.post_type = 'property'
        AND p.post_mime_type IN ('image/jpeg','image/jpg','image/gif','image/png','image/bmp') $limit_query");
      break;


      case 'all_images':
       $all_images = $wpdb->get_results("
        SELECT pm.post_id as image_id, pm.meta_value as dimensions, p.post_title as image_title, p2.post_title as property_title, p2.post_type as post_type, p2.ID as property_id
        FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p on pm.post_id = p.ID
        LEFT JOIN {$wpdb->posts} p2 on p.post_parent = p2.ID
        WHERE pm.meta_key = '_wp_attachment_metadata'
        AND p.post_mime_type IN ('image/jpeg','image/jpg','image/gif','image/png','image/bmp') $limit_query");
      break;

    }

    return $all_images;
  }

  /**
   * Add draggable image selection pane to single property page
   * Used on global slideshow admin page.
   *
   * @since 3.1
   */
  static public function add_metabox(){
    add_meta_box( 'wp_property_slideshow', __( 'Slideshow Options', ud_get_wpp_slideshow()->domain ), array('class_wpp_slideshow','slideshow_options'), 'property', 'normal' );
  }


  /**
   * Draws the draggable image selection pane
   *
   * @todo This function needs to be cleaned up.
   * @since 3.1
   */
  static public function slideshow_options() {
    global $wpdb, $wp_properties, $post, $post_id;

    $thumb_type = (!empty($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
      && !is_numeric($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
        && $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width']  != '-' ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'] : 'thumbnail');

    $thumb_info = WPP_F::image_sizes($thumb_type);

    $thumb_width = $thumb_info['width'];
    
    $image_type  = !empty( $wp_properties['configuration']['feature_settings']['slideshow']['property']['image_size'] ) ? $wp_properties['configuration']['feature_settings']['slideshow']['property']['image_size'] : false;
    
    $prop_slideshow = array();
    
    if( !empty( $image_type ) ) {
      $prop_slideshow = WPP_F::image_sizes( $image_type );
    }
    
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
    
    $exclude = array();
    foreach( (array)$gallery_images as $gallery_image_id ){
      $exclude[] = $gallery_image_id;
      $attachment = get_post($gallery_image_id);
      if($attachment->post_mime_type == 'image/jpeg' || $attachment->post_mime_type == 'image/png' || $attachment->post_mime_type == 'image/gif'){
        $all_images[] = $attachment;
      }
    }
    
    $rest_images = get_posts( array(
      'post_type' => 'attachment',
      'numberposts' => -1,
      'post_status' => null,
      'post_parent' => $post->ID,
      'exclude' => array_merge( $exclude, $current )
    ) );

    foreach( (array)$rest_images as $attachment ){
      if($attachment->post_mime_type == 'image/jpeg' || $attachment->post_mime_type == 'image/png' || $attachment->post_mime_type == 'image/gif'){
        $all_images[] = $attachment;
      }
    }

    include ud_get_wpp_slideshow()->path( 'static/views/slideshow_options.php', 'dir' );
  }

  /**
   * Displays an image block for draggable selection lists.
   * Used on global slideshow admin page.
   *
   * @since 3.1
   */
  static public function draggable_image_block($image_id, $image_type, $echo = true, &$image_obj = false) {
    global $wp_properties;

    if(empty($image_id)) {
      return;
    }
    $thumb_type = (!empty($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
      && !is_numeric($wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'])
        && $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width']  != '-' ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['thumb_width'] : 'thumbnail');

    $image = wpp_get_image_link($image_id, $thumb_type, array('return'=>'array'));
    $image_obj['link'] = $image['link'];
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
        <?php _e('Image is too small.',ud_get_wpp_slideshow()->domain); ?>
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
   * @since 3.1
   */
  static public function save_in_postmeta( $post_id ){
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
  static public function settings_nav($tabs) {
    $tabs['slideshow'] = array(
      'slug' => 'slideshow',
      'title' => __('Slideshow',ud_get_wpp_slideshow()->domain)
    );
    return $tabs;
  }


  /**
   * Adds scripts and styles to slideshow pages.
   *
   * @since 3.1
   */
  static public function admin_menu() {

    $slideshow_page = add_submenu_page('edit.php?post_type=property', __('Slideshow',ud_get_wpp_slideshow()->domain), __('Slideshow',ud_get_wpp_slideshow()->domain), self::$capability, 'slideshow',array('class_wpp_slideshow', 'page_global_slideshow'));

    /* Insert Scripts */
    add_action('admin_print_scripts-' . $slideshow_page, function() {
      wp_enqueue_script( 'wp-property-global' );
      wp_enqueue_script('jquery-ui-resizable');
      wp_enqueue_script('wpp-jquery-fancybox');
      wp_enqueue_script( 'jquery-ui-sortable');
    });
    /* Insert Styles */
    add_action('admin_print_styles-' . $slideshow_page, function() { wp_enqueue_style('wpp-jquery-fancybox-css'); });

  }

  /**
   * Property Settings page - Slideshow Tab content
   *
   * @todo Code needs to be revised and cleaned up.
   * @since 3.1
   */
  static public function settings_page() {
    class_wpp_slideshow::get_template('admin/page_settings');
  }

  /**
   * Global slideshow selection page.
   *
   * @todo Make image selection panes resizable.
   * @since 3.1
   *
   */
  static public function page_global_slideshow() {
    class_wpp_slideshow::get_template('admin/page_global_slideshow');
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
  static public function display_gallery($settings = false) {
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
      ud_get_wp_property()->path( 'static/scripts/galleria/themes', 'dir' ),
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

    include ud_get_wpp_slideshow()->path( 'static/views/display_gallery.php', 'dir' );

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
   */
  static public function display_slideshow( $settings = false ) {
    global $wp_properties, $wpdb;

    //** Support for legacy functions where first variable is the $post_id */
    if( is_numeric( $settings ) ) {
      $post_id = $settings;
    }

    if( !empty( $post_id ) ) {
      //** Check if $post_id was passed (shortcode function does not do this) */
      $post_id = $post_id;
    } elseif( !empty( $settings['id'] ) ) {
      //** Check if post_id was passed using $atts */
      $post_id = $settings['id'];
    } elseif( empty( $post_id ) && $settings['type'] == 'single' ) {
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
      'effect' => ( !empty( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['effect'] ) ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['effect'] : 'fold'),
      'slices' => ( !empty( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['slices'] ) ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['slices'] : '20'),
      'animation_speed' => ( !empty( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['animSpeed'] ) ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['animSpeed'] : '500'),
      'pause_time' => ( !empty( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['pauseTime'] ) ? $wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['pauseTime'] : '5000'),
      'image_size' => false,
      'automatically_load_images' => false,
      'show_pagination_buttons' => ( !empty( $wp_properties['configuration']['feature_settings']['slideshow']['property']['navigation'] ) ? $wp_properties['configuration']['feature_settings']['slideshow']['property']['navigation'] : 'false'),
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
        $settings['image_size'] = !empty( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'] ) ?
          $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'] : 'large';
      }

      if(
        empty( $settings['link_to_property'] ) &&
        isset( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['link_to_property'] ) &&
        $wp_properties['configuration']['feature_settings']['slideshow']['glob']['link_to_property'] == 'true'
      ) {
        $settings['link_to_property'] = 'true';
      }

      if(
        empty( $settings['show_title'] ) &&
        isset( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['show_title'] ) &&
        $wp_properties['configuration']['feature_settings']['slideshow']['glob']['show_title'] == 'true'
      ) {
        $settings['show_title'] = 'true';
      }

      if(
        empty( $settings['show_tagline'] ) &&
        isset( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['show_tagline'] ) &&
        $wp_properties['configuration']['feature_settings']['slideshow']['glob']['show_tagline'] == 'true'
      ) {
        $settings['show_tagline'] = 'true';
      }

      if(
        empty( $settings['show_excerpt'] ) &&
        isset( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['show_excerpt'] ) &&
        $wp_properties['configuration']['feature_settings']['slideshow']['glob']['show_excerpt'] == 'true'
      ) {
        $settings['show_excerpt'] = 'true';
      }

    } elseif ($settings['type'] == 'single') {

      //** Perform single slideshow specific actions */
      $images = get_post_meta($post_id, 'slideshow_images', true);
      //** tag:automatically_load_images */

      if( empty( $settings['image_size'] ) ) {
        if( empty( $wp_properties['configuration']['feature_settings']['slideshow']['property']['image_size'] ) ) {
          return false;
        }
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

        $parent_id = $wpdb->get_var("SELECT post_parent FROM {$wpdb->prefix}posts WHERE ID = '$image_id'");
        $property_data = $wpdb->get_row("SELECT *  FROM {$wpdb->prefix}posts WHERE ID = '$parent_id' AND post_status = 'publish'");
        $post_title = $property_data->post_title;
        $post_excerpt = $property_data->post_excerpt;
        $property_tagline = get_post_meta($parent_id, 'tagline', true);

        $this_caption = array();
        if( !empty( $post_title ) && isset( $settings['show_title'] ) && $settings['show_title'] == 'true' ) {
          $this_caption[] = $post_title;
        }
        if(!empty($property_tagline) && isset( $settings['show_tagline'] ) && $settings['show_tagline'] == 'true') {
          $this_caption[] = '<span>'.$property_tagline.'</span>';
        }
        if(!empty($post_excerpt) && isset( $settings['show_excerpt'] ) && $settings['show_excerpt'] == 'true') {
          $this_caption[] = '<span>'.$post_excerpt.'</span>';
        }
        $this_caption = 'title="' .  implode(" " , $this_caption) . '"';

        $this_image = "<img src=\"{$image_object['link']}\" width=\"{$image_object['width']}\" height=\"{$image_object['height']}\" {$this_caption} />";

        if( isset( $settings['link_to_property'] ) && $settings['link_to_property'] == 'true' ) {
          $parent_link = get_permalink($parent_id);
          $print_image = "<a href='{$parent_link}' class='wpp_global_slideshow_link'>{$this_image}</a>";
        } else {
          $print_image = $this_image;
        }
        $print_images[] = apply_filters( 'wpp::slideshow::print_image', $print_image, $image_id, $settings );

      }

    }

    if( !empty( $print_images ) ) {

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
        .<?php echo $this_slider_id; ?> .nivo-slice img {max-width:none;}
        .<?php echo $this_slider_id; ?> img {display:none;}
        .<?php echo $this_slider_id; ?> .nivo-controlNav {position:absolute; bottom: -18px;}
      </style>

      <div class="<?php echo $this_slider_id; ?> slider <?php echo $settings['class']; ?>"><?php echo  implode("", $print_images); ?></div>

      <?php
      $return_result = ob_get_contents();
      ob_end_clean();
    }

    return !empty( $return_result ) ? $return_result : false;
  }

  /**
   * Used for returning a property-specific slideshow via shortcode
   *
   *
   * @since 3.1
   */
  static public function shortcode_property_gallery( $atts = '') {
    //** Ensure galleria script is included */
    WPP_F::force_script_inclusion('wp-property-galleria');
    return class_wpp_slideshow::display_gallery($atts);
  }

  /**
   * Used for returning a property-specific slideshow via shortcode
   *
   * @since 3.1
   */
  static public function shortcode_property_slideshow( $atts = '' ) {
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
  static public function shortcode_global_slideshow( $atts = array() ) {
    global $image_size, $wp_properties;

    $image_size = isset( $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'] ) ?
      $wp_properties['configuration']['feature_settings']['slideshow']['glob']['image_size'] : 'large';

    $glob_slideshow = WPP_F::image_sizes($image_size);

    $atts['type'] = 'global';

    $return = class_wpp_slideshow::display_slideshow($atts);

    if(empty($return)) {
      return;
    }

    return "<div class='wpp_slideshow_global_wrapper wpp_global_slideshow_shortcode' style='position: relative; width:{$glob_slideshow['width']}px;height:{$glob_slideshow['height']}px;margin: 0 auto;'>{$return}</div>";
  }


  /**
   * Determines template and renders it
   *
   *
   */
  static public function get_template( $template, $data = array(), $output = true ) {
    $name = apply_filters('wpp_slideshow_template_name', array( $template ) );
    /* Set possible pathes where templates could be stored. */
    $path = apply_filters('wpp_slideshow_template_path', array(
      ud_get_wpp_slideshow()->path( 'static/views', 'dir' ),
    ) );

    $path = \UsabilityDynamics\Utility::get_template_part( $name, $path, array(
      'load' => false
    ) );

    if($path){
      if( $output ) {
        extract( $data );
        include $path;
      } else {
        ob_start();
        extract( $data );
        include $path;
        return ob_get_clean();
      }
    }
  }

  /**
   * Add media to gallery.
   * 
   */
  static public function wpp_media_to_gallery_images($new, $field, $old){
    if(!empty($_POST['ID']) && is_array($new)){
      // so that we don't modify the original $new
      $_new = $new;
      $slideshow_order  = array();
      $gallery_order   = array();

      // Converting from string format to array format.
      if( !empty( $_POST['property_slideshow_image_array'] ) ) {
        /* fix array  */
        $string_array = $_POST['property_slideshow_image_array'];
        $string_array = str_replace('item=', '', $string_array);
        $slideshow_order = explode('&', $string_array);
      }

      // Converting from string format to array format.
      if( !empty( $_POST['property_gallery_image_array'] ) ) {
        /* fix array  */
        $string_array = $_POST['property_gallery_image_array'];
        $string_array = str_replace('item=', '', $string_array);
        $gallery_order = explode('&', $string_array);
      }
      
      $all_images = array_merge($slideshow_order, $gallery_order);
      // Remove items already in slideshow.
      foreach ($all_images as $order_id) {
        $key = array_search($order_id, $_new);
        if($key !== false){
          unset($_new[$key]);
        }
      }

      // adding _new images to end of the gallery.
      $gallery_order = array_values(array_unique(array_merge($gallery_order, $_new)));
      // altering the $_POST variable in case save_property action happen later.
      $_POST['property_gallery_image_array'] = 'item=' . implode('&item=', $gallery_order);
      // Updating the meta in case did action save_property.
      update_post_meta( $_POST['ID'], 'gallery_images', $gallery_order );
    }
    return $new;
  }
  

}

if(!function_exists('global_slideshow')) {
  /**
   * Used for calling the function programatically
   * Pointless function, in most cases do_shortcode() should be used.
   *
   * @since 3.1
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
   * Used for calling the function programatically
   * Pointless function, in most cases do_shortcode() should be used.
   *
   * @since 3.1
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
