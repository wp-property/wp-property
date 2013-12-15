<?php
/*
Name: Supermap
Class: class_wpp_supermap
Version: 3.4.9.3
Minimum Core Version: 1.37.2
Feature ID: 3
Description: A big map for property overview.
*/

add_action('wpp_init', array('class_wpp_supermap', 'init'));
add_action('wpp_pre_init', array('class_wpp_supermap', 'pre_init'));

if(!class_exists('class_wpp_supermap')) :

class class_wpp_supermap {

  /**
   * (custom) Capability to manage the current feature
   */
  static protected $capability = "manage_wpp_supermap";

  /**
   * Special functions that must be called prior to init
   *
   */
  function pre_init() {
    //* Add capability */
    add_filter('wpp_capabilities', array('class_wpp_supermap', "add_capability"));
    //* Check and set specific supermap meta_keys */
    //* Check supermap markers files */
    add_filter('wpp_settings_save', array('class_wpp_supermap','settings_save'));
  }

  /**
   * Something like constructor
   *
   */
  function init() {
    add_shortcode('supermap', array('class_wpp_supermap', 'shortcode_supermap'));

    add_action('wp_ajax_supermap_get_properties', array('class_wpp_supermap','ajax_get_properties'));
    add_action('wp_ajax_nopriv_supermap_get_properties', array('class_wpp_supermap','ajax_get_properties'));
    add_action('template_redirect', array('class_wpp_supermap','supermap_template_redirect'));

    //* Load admin header scripts */
    add_action('admin_enqueue_scripts', array('class_wpp_supermap', 'admin_enqueue_scripts'));

    add_filter('wpp_supermap_marker', array('class_wpp_supermap', 'get_marker_by_post_id'), 10, 2);

    //* Add to settings page nav */
    if(current_user_can(self::$capability)) {
      add_filter('wpp_settings_nav', array('class_wpp_supermap', 'settings_nav'));
      add_filter('wpp_property_type_settings', array('class_wpp_supermap', 'property_type_settings'), 10, 2);

      //* Add Settings Page */
      add_action('wpp_settings_content_supermap', array('class_wpp_supermap', 'settings_page'));

      //* For Admin panel */
      add_action('add_meta_boxes', array('class_wpp_supermap','add_metabox'));
      add_action('save_post', array('class_wpp_supermap','save_post'));

      //* Upload files (markers) */
      add_action('wp_ajax_supermap_upload_marker', array('class_wpp_supermap','ajax_marker_upload'));
    }
    //** Filter meta keys during import process @author korotkov@ud */
    add_filter('wpp_xml_import_value_on_import', array('class_wpp_supermap', 'importer_meta_filter'), 10, 4);
  }

  /**
   * Adds Custom capability to the current premium feature
   *
   * @param array $capabilities
   * @return array $capabilities
   */
  function add_capability($capabilities) {

    $capabilities[self::$capability] = __('Manage Supermap','wpp');

    return $capabilities;
  }

  /**
   * Adds slideshow menu to settings page navigation
   * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
   *
   * @param array $tabs
   */
  function settings_nav($tabs) {
    $tabs['supermap'] = array(
      'slug' => 'supermap',
      'title' => __('Supermap','wpp')
    );
    return $tabs;
  }

  /**
   * Add Supermap tab's content on Settings Page
   *
   */
  function settings_page() {
    global $wp_properties, $wpdb, $class_wpp_slideshow;

    $supermap_configuration = $wp_properties['configuration']['feature_settings']['supermap'];

    if(empty($supermap_configuration)) {
      $supermap_configuration = array();
    }

    //* Set default Marker */
    if(empty($supermap_configuration['markers'])) {
      $supermap_configuration['markers']['custom']['name'] = 'Custom';
      $supermap_configuration['markers']['custom']['file'] = '';
    }

    //* Set example of Area */
    if(empty($supermap_configuration['areas'])) {
      $supermap_configuration['areas']['example_area']['strokeColor'] = '#a49b8a';
      $supermap_configuration['areas']['example_area']['strokeOpacity'] = '#a49b8a';
      $supermap_configuration['areas']['example_area']['fillColor'] = '#a49b8a';
      $supermap_configuration['areas']['example_area']['fillOpacity'] = '0.5';
      $supermap_configuration['areas']['example_area']['paths'] = '';
    }
    ?>
    <style>
      #wpp_supermap_markers .wpp_supermap_ajax_uploader {
        position:relative;
        width: 42px;
        height: 42px;
        background: #ffffff;
        overflow:hidden;
        border: 1px solid #DFDFDF;
        text-align:center;
        cursor:pointer;
      }
      #wpp_supermap_markers .wpp_supermap_ajax_uploader div.qq-uploader {
        color:#fff;
      }
      #wpp_supermap_markers .wpp_supermap_ajax_uploader div.qq-upload-drop-area {
        display:none;
      }
      #wpp_supermap_markers .wpp_supermap_ajax_uploader .spinner {
        position:absolute;
        top:0;
        bottom:0;
        left:0;
        right:0;
        background: url("<?php echo WPP_URL; ?>images/ajax_loader.gif") center center no-repeat;
        display: none;
      }
      #wpp_supermap_markers .wpp_supermap_ajax_uploader img {
        padding:5px 0;
      }
    </style>
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row"><?php _e('Sidebar Attributes', 'wpp'); ?></th>
          <td>
            <p><?php _e('Select the attributes you want to display in the left sidebar on the supermap.', 'wpp'); ?></p>
            <div class="wp-tab-panel">
            <ul>
              <?php foreach($wp_properties['property_stats'] as $slug => $title): ?>
                <li>
                  <input <?php if(@in_array($slug, $supermap_configuration['display_attributes'])) echo " CHECKED ";  ?> value='<?php echo $slug; ?>' type="checkbox" id="display_attribute_<?php echo $slug; ?>" name="wpp_settings[configuration][feature_settings][supermap][display_attributes][]" />
                  <label for="display_attribute_<?php echo $slug; ?>"><?php echo $title; ?></label>
                </li>
              <?php endforeach; ?>
            </ul>
            </div>
            <ul style="margin-top:10px;">
              <li>
                <input <?php if(@in_array('view_property', $supermap_configuration['display_attributes'])) echo " CHECKED ";  ?> value='view_property' type="checkbox" id="display_attribute_view_property" name="wpp_settings[configuration][feature_settings][supermap][display_attributes][]" />
                <label for="display_attribute_view_property"><?php _e('Display "View Property" link in the left sidebar. It directs user to Property Page.','wpp') ?></label>
              </li>
            </ul>
          </td>
        </tr>
        <tr>
          <th><?php _e('Supermap Sidebar Thumbnail:','wpp') ?></th>
          <td>
            <ul>
              <li>
                <input <?php checked('true', $supermap_configuration['hide_sidebar_thumb']); ?> value='true' type="checkbox" id="supermap_hide_sidebar_thumb" name="wpp_settings[configuration][feature_settings][supermap][hide_sidebar_thumb]" />
                <label for="supermap_hide_sidebar_thumb"><?php _e('Do not show a property thumbnail in sidebar.','wpp') ?></label>
              </li>
              <li><?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][feature_settings][supermap][supermap_thumb]&selected=" . $supermap_configuration['supermap_thumb']); ?></li>
              <li><?php _e('If you create a new image size, please be sure to regenerate all thumbnails. ','wpp') ?></li>
            </ul>
          </td>
        </tr>
        <tr>
          <th><?php _e('Map Markers:','wpp') ?></th>
          <td>
            <table id="wpp_supermap_markers" class="wpp_sortable ud_ui_dynamic_table widefat" allow_random_slug="true">
              <thead>
                <tr>
                  <th style="width:10px;" class="wpp_draggable_handle">&nbsp;</th>
                  <th style="width:50px;"><?php _e('Image','wpp') ?></th>
                  <th style="width:150px;"><?php _e('Name','wpp') ?></th>
                  <th style="width:250px;"><?php _e('Slug','wpp') ?></th>
                  <th style="width:50px;">&nbsp;</th>
                </tr>
              </thead>
              <tbody>
              <?php
              $upload_dir = wp_upload_dir();
              $markers_dir = $upload_dir['basedir'] . '/supermap_files/markers';
              $markers_url = $upload_dir['baseurl'] . '/supermap_files/markers';
              foreach($supermap_configuration['markers'] as $slug => $marker):  ?>
                  <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row='false'>
                    <td class="wpp_draggable_handle">&nbsp;</td>
                    <td class="wpp_ajax_image_upload">
                      <div class="wpp_supermap_ajax_uploader">
                      <?php if(!empty($marker['file']) && file_exists($markers_dir . '/' . $marker['file'])) : ?>
                        <img class="wpp_marker_image" src="<?php echo ($markers_url . '/' . $marker['file']) ; ?>" alt="" />
                      <?php endif; ?>
                      </div>
                      <input type="hidden" class="wpp_supermap_marker_file" name="wpp_settings[configuration][feature_settings][supermap][markers][<?php echo $slug; ?>][file]" value="<?php echo $marker['file']; ?>" />
                    </td>
                    <td>
                      <input class="slug_setter" type="text" name="wpp_settings[configuration][feature_settings][supermap][markers][<?php echo $slug; ?>][name]" value="<?php echo $marker['name']; ?>" />
                    </td>
                    <td>
                      <input type="text" value="<?php echo $slug; ?>" readonly="readonly" class="slug wpp_marker_slug">
                    </td>
                    <td>
                      <span class="wpp_delete_row wpp_link"><?php _e('Delete','wpp') ?></span>
                    </td>
                  </tr>
              <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan='5'>
                  <input type="button" class="wpp_add_row button-secondary btn" value="<?php _e('Add Marker','wpp') ?>" />
                  </td>
                </tr>
              </tfoot>
            </table>
            <script type="text/javascript">
              jQuery(document).ready(function(){

                function wpp_supermap_init_ajax_uploader(e) {
                  // Don't init ajaxuploader if image already exists
                  var img = jQuery(e).find('img');
                  if(img.length > 0) {
                    return;
                  }

                  var row = jQuery(e).parents('.wpp_dynamic_table_row');
                  if(row.length > 0) {
                    var slug = row.attr('slug');

                    if(typeof eval('window.wpp_uploader_'+slug) == 'undefined' ||
                       eval('window.wpp_uploader_'+slug) == null) {

                      /* Determine if slug field is empty, - it's new row without own slug */
                      if(jQuery('input.slug', row).val() == '') {
                        return;
                      }
                      /* Initialize Uploader */
                      var uploader = new qq.FileUploader({
                        element: e,
                        action: '<?php echo admin_url('admin-ajax.php'); ?>',
                        params: {
                          action: 'supermap_upload_marker',
                          slug: slug
                        },
                        onSubmit: function() {
                          if(!jQuery('.spinner', e).length > 0){
                            jQuery(e).append('<div class="spinner"></div>');
                          }
                          jQuery('.spinner', e).show();
                        },
                        onComplete: function(id, fileName, responseJSON){
                          jQuery('.spinner', e).hide();
                          if(responseJSON.success == 'true') {
                            jQuery('.qq-uploader', e).remove();
                            var url = responseJSON.url;
                            var filename = responseJSON.filename;
                            jQuery(e).html('<img class="wpp_marker_image" src="' + url + '" alt="" />');
                            jQuery('input.wpp_supermap_marker_file', row).val(filename);
                            //eval('window.wpp_uploader_'+slug+' = null');
                          }
                        }
                      });
                      eval('window.wpp_uploader_'+slug+' = uploader');
                    }
                  }
                }

                /* Adds ajaxuploader functionality */
                jQuery('#wpp_supermap_markers .wpp_supermap_ajax_uploader').each(function(i,e){
                  wpp_supermap_init_ajax_uploader(e);
                });

                /* Remove ajaxuploader and image on Slug changing */
                jQuery('#wpp_supermap_markers input.slug').live('change', function(){
                  var e = this;
                  var row = jQuery(e).parents('.wpp_dynamic_table_row');
                  if(row.length > 0) {
                    var slug = row.attr('slug');
                    eval('window.wpp_uploader_'+slug+' = null');
                    jQuery('.wpp_supermap_ajax_uploader', row).html('');
                    jQuery('input.wpp_supermap_marker_file', row).val('');

                    var uploader = jQuery('.wpp_supermap_ajax_uploader', row).get(0);
                    wpp_supermap_init_ajax_uploader(uploader);
                  }
                });

                /* Remove image from new Row */
                jQuery('#wpp_supermap_markers tr').live('added', function(){
                  jQuery('input.slug', this).trigger('change');
                });

                /* Fire event after row removing to check table's DOM */
                jQuery('#wpp_supermap_markers').live('row_removed', function(){
                  var row_count = jQuery(this).find(".wpp_delete_row:visible").length;
                  if(row_count == 1) {
                    var slug = jQuery(this).find('input.wpp_marker_slug').val();
                    if(slug == '') {
                      jQuery('.wpp_supermap_ajax_uploader', this).html('');
                      jQuery('input.wpp_supermap_marker_file', this).val('');
                      jQuery('tr', this).attr('new_row', 'true');
                    }
                  };
                });

              });
            </script>
          </td>
        </tr>
        <tr>
          <th><?php _e('Map Areas:','wpp') ?></th>
          <td>
            <?php _e('<p>Map areas let you draw our areas on the map, such as neighborhoods.</p><p>Just add to shortcode attribute <b>show_areas=all</b> to draw all areas on the map. Also You can use area\'s slugs to show them on the map, like as <b>show_areas=new_york,washington</b>. Please, use coordinates in this format: <b>(82.72, -37.79)(69.54, -57.48)(68.93, -18.63).</b></p><p><i>This is an experimental feature, you may not want to use it on a live site.  We\'re eager to hear your feedback regarding this feature and the capabilities that would be useful to you.</i></p>','wpp') ?>
            <table id="wpp_supermap_areas" class="ud_ui_dynamic_table widefat">
              <thead>
                <tr>
                  <th><?php _e('Name','wpp') ?></th>
                  <th style="width:50px;"><?php _e('Coordinates','wpp') ?></th>
                  <th><?php _e('Fill Color','wpp') ?></th>
                  <th><?php _e('Opacity','wpp') ?></th>
                  <th><?php _e('Stoke Color','wpp') ?></th>
                  <th><?php _e('Hover Color','wpp') ?></th>
                  <th>&nbsp;</th>
                </tr>
              </thead>
              <tbody>
              <?php
                foreach($supermap_configuration['areas'] as $slug => $area_data):  ?>
                  <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row='true'>
                    <td >
                      <input class="slug_setter" type="text" name="wpp_settings[configuration][feature_settings][supermap][areas][<?php echo $slug; ?>][name]" value="<?php echo $area_data['name']; ?>" />
                      <input type="text" value="<?php echo $slug; ?>" readonly="readonly" class="slug">
                    </td>
                    <td>
                      <textarea name="wpp_settings[configuration][feature_settings][supermap][areas][<?php echo $slug; ?>][paths]"><?php echo $area_data['paths']; ?></textarea>
                    </td>
                    <td>
                      <input type="text" class="wpp_input_colorpicker" id="" name="wpp_settings[configuration][feature_settings][supermap][areas][<?php echo $slug; ?>][fillColor]" value="<?php echo $area_data['fillColor']; ?>" />
                    </td>
                    <td>
                      <input style="width:40px;" type="text" name="wpp_settings[configuration][feature_settings][supermap][areas][<?php echo $slug; ?>][fillOpacity]" value="<?php echo $area_data['fillOpacity']; ?>" />
                    </td>
                    <td>
                      <input type="text" class="wpp_input_colorpicker" name="wpp_settings[configuration][feature_settings][supermap][areas][<?php echo $slug; ?>][strokeColor]" value="<?php echo $area_data['strokeColor']; ?>" />
                    </td>
                    <td>
                      <input type="text" class="wpp_input_colorpicker" name="wpp_settings[configuration][feature_settings][supermap][areas][<?php echo $slug; ?>][hoverColor]" value="<?php echo $area_data['hoverColor']; ?>" />
                    </td>
                    <td>
                      <span class="wpp_delete_row wpp_link"><?php _e('Delete','wpp') ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan='7'>
                  <input type="button" class="wpp_add_row button-secondary btn" value="<?php _e('Add Row','wpp') ?>" />
                  </td>
                </tr>
              </tfoot>
            </table>
          </td>
        </tr>
      </tbody>
    </table>
    <?php
  }

  /**
   * Add supermap settings for property type
   *
   * @param $settings
   * @param $slug
   * @return $settings
   * @author Maxim Peshkov
   */
  function property_type_settings($settings, $slug) {
    global $wp_properties;

    $supermap_configuration = $wp_properties['configuration']['feature_settings']['supermap'];
    $upload_dir = wp_upload_dir();
    $markers_url = $upload_dir['baseurl'] . '/supermap_files/markers';
    $markers_dir = $upload_dir['basedir'] . '/supermap_files/markers';
    $default_marker = WPP_URL . 'images/google_maps_marker.png';

    ob_start();
    ?>
    <div class="wp-tab-panel supermap_marker_settings">
    <div class="wpp_property_type_supermap_settings">
      <div class="wpp_supermap_marker_image">
      <?php $marker_url = $markers_url . "/" . $supermap_configuration['property_type_markers'][$slug]; ?>
      <?php $marker_dir = $markers_dir . "/" . $supermap_configuration['property_type_markers'][$slug]; ?>
      <?php if (!empty($supermap_configuration['property_type_markers'][$slug]) && file_exists($marker_dir)) : ?>
      <img src="<?php echo $marker_url; ?>" alt="" />
      <?php else : ?>
      <img src="<?php echo $default_marker; ?>" alt="" />
      <?php endif; ?>
      </div>
      <div class="wpp_supermap_marker_selector">
      <label for="wpp_setting_property_type_<?php echo $slug ?>_marker"><?php _e('Map Marker', 'wpp'); ?>:</label>
      <select class="wpp_setting_property_type_marker" id="wpp_setting_property_type_<?php echo $slug ?>_marker" name="wpp_settings[configuration][feature_settings][supermap][property_type_markers][<?php echo $slug; ?>]" >
        <option value=""><?php _e('Default by Google', 'wpp'); ?></option>
        <?php if(!empty($supermap_configuration['markers'])) : ?>
          <?php foreach ($supermap_configuration['markers'] as $mslug => $mvalue) : ?>
            <option value="<?php echo $mvalue['file']; ?>" <?php selected($supermap_configuration['property_type_markers'][$slug], $mvalue['file']); ?>><?php echo $mvalue['name']; ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
      </div>
      <div class="clear"></div>
    </div>
    <script type="text/javascript">
      jQuery(document).ready(function(){
        if(typeof property_type_marker_events == 'undefined') {
          /* Change marker's image preview on marker changing */
          jQuery('select.wpp_setting_property_type_marker').live('change', function(){
            var e = jQuery(this).parents('.wpp_property_type_supermap_settings');
            var filename = jQuery(this).val();
            var rand = Math.random();
            var HTML = '';
            if(filename != '') {
              HTML = '<img src="<?php echo $markers_url; ?>/' + filename + '?' + rand + '" alt="" />';
            } else {
              HTML = '<img src="<?php echo $default_marker; ?>" alt="" />';
            }
            e.find('.wpp_supermap_marker_image').html(HTML);
          });

          /* Fire marker's image changing Event after Row is added */
          if(jQuery('#wpp_inquiry_property_types').length > 0) {
            jQuery('#wpp_inquiry_property_types tr').live('added', function(){
              jQuery('select.wpp_setting_property_type_marker', this).trigger('change');
            });
          }

          property_type_marker_events = true;
        }
      });
    </script>
    </div>
    <?php
    $content = ob_get_contents();
    ob_end_clean();

    $settings[] = $content;

    return $settings;
  }

  /**
   * Handles ajax file uploads.
   * Uploads submitted file.
   *
   * @author Maxim Peshkov
   */
  function ajax_marker_upload() {
    global $wp_properties;

    $return = array();

    $file_name = $_REQUEST['qqfile'];
    $slug = $_REQUEST['slug'];

    //* Available Extensions */
    $exts = array('jpg','jpeg','png','gif','bmp');
    $ext = pathinfo($file_name, PATHINFO_EXTENSION);

    if(!in_array($ext, $exts)) {
      $return['error'] = __('File should be an image','wpp');
    } else {
      $upload_dir = wp_upload_dir();
      $files_dir = $upload_dir['basedir'] . '/supermap_files';
      $files_markers_dir = $files_dir .  '/markers';
      $files_markers_url = $upload_dir['baseurl'] . '/supermap_files/markers';

      if(!is_dir($files_dir)) {
        mkdir($files_dir, 0755);
      }

      if(!is_dir($files_markers_dir)) {
        mkdir($files_markers_dir, 0755);
        fopen($files_markers_dir . '/index.php', "w");
      }

      $path = $files_markers_dir . '/'. $file_name;

      if ( empty( $_FILES ) ) {
        $temp = tmpfile();
        $input = fopen("php://input", "r");
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
      } else {
        //* for IE!!! */
        move_uploaded_file($_FILES['qqfile']['tmp_name'], $path);
      }

      //* Try to resize file */
      $rf = image_make_intermediate_size($path, 32, 32, false);
      if(!empty($rf['file'])) {
        $resized_file_old = $files_markers_dir . '/'. $rf['file'];
      } else {
        $resized_file_old = $path;
      }

      //* Rename file */
      $resized_file_new = $files_markers_dir . '/'. $slug . '.' . $ext;
      if(file_exists($resized_file_new)) {
        $return['step'] = $resized_file_new;
        unlink($resized_file_new);
      }
      rename($resized_file_old, $resized_file_new);
      if(file_exists($path)) {
        unlink($path);
      }
      //* END Resize and Rename file */

      if(!file_exists($resized_file_new)) {
        $return['error'] = __('Looks like, Image can not be uploaded.', 'wpp');
      } else {
        $return['success'] = 'true';
        $return['url'] = $files_markers_url . '/' . $slug . '.' . $ext . '?' . (rand(0,100));
        $return['filename'] = $slug . '.' . $ext;
      }
    }

    die(htmlspecialchars(json_encode($return), ENT_NOQUOTES));
  }

  /**
   * Enqueue scripts on specific pages, and print content into head
   *
   * @uses $current_screen global variable
   * @author Maxim Peshkov
   */
  function admin_enqueue_scripts() {
    global $current_screen, $wp_properties;

    //* WPP Settings Page */
    if($current_screen->id == 'property_page_property_settings') {
      wp_enqueue_script('wpp-jquery-ajaxupload');
    }

    //* Add custom supermap styles */
    ?>
    <style>
      .supermap_marker_settings .wpp_supermap_marker_image {
        float:left;
        position:relative;
        width: 42px;
        height: 38px;
        padding-top:5px;
        background: #ffffff;
        overflow:hidden;
        border: 1px solid #DFDFDF;
        text-align:center;
      }
      .wp-tab-panel.supermap_marker_settings {
        height:auto;
      }
      .supermap_marker_settings .wpp_supermap_marker_selector {
        margin-left:5px;
        float:left;
        width:70%;
      }
      .supermap_marker_settings .wpp_supermap_marker_selector select {
        width:100%;
      }
      .supermap_marker_settings label {
        line-height:20px;
      }
    </style>
    <?php
  }

  /**
   * Determine if post has 'supermap' shortcode and
   * Enqueue scripts and style
   *
   */
  function supermap_template_redirect(){
    global $post;

    if(strpos($post->post_content, "supermap")) {
      wp_enqueue_script('google-maps');
      wp_enqueue_script('google-infobubble');
      wp_enqueue_script('wpp-jquery-fancybox');
      wp_enqueue_style('wpp-jquery-fancybox-css');
    }
  }

  /**
   * Adds metabox to property editor
   *
   */
  function add_metabox(){
    add_meta_box( 'wp_property_supermap', __( 'Supermap Options', 'wpp' ),
    array('class_wpp_supermap','property_supermap_options'), 'property', 'side' );
  }

  /**
   * Renders content for metabox
   *
   */
  function property_supermap_options(){
    global $post_id, $wp_properties;

    //* Exclude From Supermap checkbox */
    $disable_exclude = get_post_meta($post_id, 'exclude_from_supermap', true);
    $text = __('Exclude property from Supermap','wpp');
    echo WPP_F::checkbox("name=exclude_from_supermap&id=exclude_from_supermap&label=$text", $disable_exclude);

    //* START Renders Supermap Marker's settings */
    //* Get supermap marker for the current property */
    $supermap_marker = get_post_meta($post_id, 'supermap_marker', true);
    $default_marker = WPP_URL . 'images/google_maps_marker.png';

    $supermap_configuration = $wp_properties['configuration']['feature_settings']['supermap'];
    if(empty($supermap_configuration['property_type_markers'])) {
      $supermap_configuration['property_type_markers'] = array();
    }

    $property_type = get_post_meta($post_id, 'property_type', true);
    if(empty($supermap_marker) && !empty($property_type)) {
      $supermap_marker = $supermap_configuration['property_type_markers'][$property_type];
    }

    $upload_dir = wp_upload_dir();
    $markers_url = $upload_dir['baseurl'] . '/supermap_files/markers';
    $markers_dir = $upload_dir['basedir'] . '/supermap_files/markers';

    //* Set default marker image */
    if(empty($supermap_marker)) {
      $marker_url = $default_marker;
    } else {
      $marker_url = $markers_url . "/" . $supermap_marker;
      $marker_dir = $markers_dir . "/" . $supermap_marker;
      if(!file_exists($marker_dir)) {
        $marker_url = $default_marker;
      }
    }
    ?>
    <div class="wp-tab-panel supermap_marker_settings" id="wpp_supermap_marker_settings" style="margin-top:10px;">
      <div class="wpp_supermap_marker_image">
        <img src="<?php echo $marker_url; ?>" alt="" />
      </div>
      <div class="wpp_supermap_marker_selector">
      <label for="wpp_setting_supermap_marker"><?php _e('Map Marker', 'wpp'); ?>:</label>
      <select id="wpp_setting_supermap_marker" name="supermap_marker">
        <option value="default_google_map_marker"><?php _e('Default by Google', 'wpp'); ?></option>
        <?php if(!empty($supermap_configuration['markers'])) : ?>
          <?php foreach ($supermap_configuration['markers'] as $mslug => $mvalue) : ?>
            <option value="<?php echo $mvalue['file']; ?>" <?php selected($supermap_marker, $mvalue['file']); ?>><?php echo $mvalue['name']; ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
      </div>
      <div class="clear"></div>
      <script type="text/javascript">
        /* The list of markers images for property types */
        var property_type_markers = <?php echo json_encode((array)$supermap_configuration['property_type_markers']); ?>

        jQuery(document).ready(function(){
          /* Change marker image */
          jQuery('#wpp_setting_supermap_marker').live('change', function(){
            var e = jQuery('#wpp_supermap_marker_settings');
            var filename = jQuery(this).val();
            var rand = Math.random();
            var HTML = '';
            if(filename != '' && filename != 'default_google_map_marker') {
              HTML = '<img src="<?php echo $markers_url; ?>/' + filename + '?' + rand + '" alt="" />';
            } else {
              HTML = '<img src="<?php echo $default_marker; ?>" alt="" />';
            }
            e.find('.wpp_supermap_marker_image').html(HTML);
          });

          /* Change supermap marker on Property Type 'change' Event */
          jQuery('#wpp_meta_property_type').live('change', function(){
            var property_type = jQuery(this).val();
            for(var i in property_type_markers) {
              if(property_type == i) {
                jQuery('#wpp_setting_supermap_marker').val(property_type_markers[i]);
                jQuery('#wpp_setting_supermap_marker').trigger('change');
              }
            }
          });
        });
      </script>
    </div>
    <?php
    //* END Renders Supermap Marker's settings */
  }

  /**
   * Updates/Adds custom 'supermap' postmeta on post saving
   *
   * @param int $post_id
   */
  function save_post($post_id){
    global $wp_properties;

    if(isset($_POST['exclude_from_supermap'])) {
      update_post_meta($post_id, 'exclude_from_supermap', $_POST['exclude_from_supermap']);
    }

    //* Save custom supermap marker for property */
    if(isset($_POST['supermap_marker'])) {
      $supermap_marker = $_POST['supermap_marker'];

      /* Determine if property marker is the same as property's 'property type' marker
       * We reset (clear) property marker to avoid the issues on 'property type' marker changes.
       * peshkov@UD
       */
      if(!empty($_POST['wpp_data']['meta']['property_type'])) {
        $property_type = $_POST['wpp_data']['meta']['property_type'];
        $supermap_configuration = $wp_properties['configuration']['feature_settings']['supermap'];

        if(!empty($supermap_configuration['property_type_markers'])) {
          if($supermap_configuration['property_type_markers'][$property_type] == $supermap_marker) {
            $supermap_marker = '';
          }
        }
      }
      update_post_meta($post_id, 'supermap_marker', $supermap_marker);
    }
  }

  /**
   * Returns Supermap marker url for property if exists
   * If not, returns empty string
   *
   * @param string $marker_url
   * @param integer $post_id
   * @return string $marker_url
   *
   * @author Maxim Peshkov
   */
  function get_marker_by_post_id($marker_url = '', $post_id) {
    global $wp_properties;

    //* Get supermap marker for the current property */
    $supermap_marker = get_post_meta($post_id, 'supermap_marker', true);

    //* Return empty string if property uses default marker */
    if($supermap_marker == 'default_google_map_marker') {
      return '';
    }

    $supermap_configuration = $wp_properties['configuration']['feature_settings']['supermap'];
    if(empty($supermap_configuration['property_type_markers'])) {
      $supermap_configuration['property_type_markers'] = array();
    }

    $property_type = get_post_meta($post_id, 'property_type', true);
    if(empty($supermap_marker) && !empty($property_type)) {
      $supermap_marker = $supermap_configuration['property_type_markers'][$property_type];
    }

    $upload_dir = wp_upload_dir();
    $markers_url = $upload_dir['baseurl'] . '/supermap_files/markers';
    $markers_dir = $upload_dir['basedir'] . '/supermap_files/markers';

    //* Set default marker image */
    if(empty($supermap_marker)) {
      $marker_url = '';
    } else {
      $marker_url = $markers_url . "/" . $supermap_marker;
      $marker_dir = $markers_dir . "/" . $supermap_marker;
      if(!file_exists($marker_dir)) {
        $marker_url = '';
      }
    }

    return $marker_url;
  }

  /**
   * Returns supermap for shortcode
   * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
   *
   * Example of Atts:
   * zoom=5
   * center_on=74.3434,-130.22
   *
   * @param array $atts Attributes of shortcode
   */
  function shortcode_supermap($atts = array()) {
    global $wp_properties, $wp_scripts;
    $css_class = empty($css_class) ? '' : $css_class;
    $defaults = array(
      'per_page' => 10,
      'css_class' => '',
      'starting_row' => 0,
      'pagination' => 'on',
      'sidebar_width' => '',
      'hide_sidebar' => 'false',
      'map_height' => '',
      'map_width' => '',
      'options_label' => __('Options','wpp'),
      'silent_failure' => 'true',
      'sort_order' => 'DESC',
      'sort_by' => 'post_date'
    );

    $atts = array_merge($defaults, (array)$atts);

    WPP_F::load_assets();

    //** Quit function if Google Maps is not loaded */
    if(!WPP_F::is_asset_loaded('google-maps')) {
      return ($atts['silent_failure'] == 'true' ? false : sprintf(__('Element cannot be rendered, missing %1s script.', 'wpp'), 'google-maps'));
    }

    //* Available search attributes */
    $searchable_attributes = (array) $wp_properties['searchable_attributes'];

    //* Set property types */
    if(!isset($atts['property_type'])) {
      //* Need this for better UI and to avoid mistakes */
      //* @TODO: need to determine if custom attribute 'type' does not isset at first to use this condition. peshkov@UD */
      if(!empty($atts['type'])) {
        $atts['property_type'] = $atts['type'];
      } else {
        $atts['property_type'] = $wp_properties['searchable_property_types'];
      }
    }
    /* END Set property types */

    //* Set Available query keys */
    $query_keys = array_flip($searchable_attributes);
    foreach($query_keys as $key => $val) {
      $query_keys[$key] = '';
    }

    $query_keys['property_type'] = '';

    //* START Set query */
    $query = shortcode_atts($query_keys, $atts);

    if (isset($_REQUEST['wpp_search'])){
      $query = shortcode_atts($query, $_REQUEST['wpp_search']);
    }

    /* HACK: Remove attribute with value 'all' from query to avoid search result issues:
     * Because 'all' means any attribute's value,
     * But if property has no the attribute, which has value 'all' - query doesn't return this property
     */
    foreach ($query as $k => $v) {
      if($v == 'all') {
        unset($query[$k]);
      }
    }

    //* Exclude properties which has no latitude,longitude keys */
    $query['latitude'] = 'all';
    $query['longitude'] = 'all';
    $query['address_is_formatted'] = 'true';

    //* Add only properties which are not excluded from supermap (option on Property editing form) */
    $query['exclude_from_supermap'] = 'false,0';

    //* Prepare search attributes to use them in get_properties() */
    $query = WPP_F::prepare_search_attributes($query);

    if($atts['pagination'] == 'on') {
      $query['pagi'] = $atts['starting_row'] . '--' . $atts['per_page'];
    }

    $query['sort_by'] = $atts['sort_by'];
    $query['sort_order'] = $atts['sort_order'];

    //* END Set query */

    //* Get properties */
    $property_ids = WPP_F::get_properties( $query , true );

    //** We do this so if there are properties that are so close the icons overlap, the properties in the beginning of the array will be he overlapping ones */

    if(is_array($property_ids['results'])) {
      $property_ids['results'] = array_reverse($property_ids['results']);
    }

    if (!empty($property_ids['results'])) {

      $atts['total'] = $property_ids['total'];

      $properties = array();
      foreach ($property_ids['results'] as $key => $id) {

        $property = prepare_property_for_display( $id, array(
          'load_gallery' => 'false',
          'get_children' => 'false',
          'load_parent' => 'false',
          'scope' => 'supermap_sidebar'
        ) );

        $properties[$id] = $property;
      }

      //* Get supermap content */
      $supermap = self::supermap_template($properties, $atts);

      return $supermap;
    }
  }

  /**
   * Ajax. Returns javascript:
   * list of properties and markers
   *
   */
  function ajax_get_properties() {
    global $wpdb, $wp_properties;

    $defaults = array(
      'per_page' => 10,
      'starting_row' => 0,
      'pagination' => 'on',
      'sort_order' => 'ASC',
      'sort_by' => 'menu_order',
      'property_type' => ( $wp_properties['searchable_property_types'] )
    );

    $atts = shortcode_atts($defaults, $_REQUEST);

    //* Supermap configuration */
    $supermap_configuration = $wp_properties['configuration']['feature_settings']['supermap'];
    if(empty($supermap_configuration['supermap_thumb'])) {
      $supermap_configuration['supermap_thumb'] = 'thumbnail';
    }

    //* START Prepare search params for get_properties() */
    $query = array();
    if(!empty($_REQUEST['wpp_search'])) {
      //* Available search attributes */
      $searchable_attributes = (array)$wp_properties['searchable_attributes'];
      $query_keys = array_flip($searchable_attributes);
      foreach($query_keys as $key => $val) {
        $query_keys[$key] = '';
      }
      $query = $_REQUEST['wpp_search'];
      $query = shortcode_atts($query_keys, $query);
    }

    //* Exclude properties which has no latitude,longitude keys */
    $query['latitude'] = 'all';
    $query['longitude'] = 'all';
    $query['address_is_formatted'] = '1';
    //* Add only properties which are not excluded from supermap (option on Property editing form) */
    $query['exclude_from_supermap'] = 'false,0';
    //* Set Property type */
    $query['property_type'] = $atts['property_type'];

    //* Prepare Query params */
    $query = WPP_F::prepare_search_attributes($query);

    if($atts['pagination'] == 'on') {
      $query['pagi'] = $atts['starting_row'] . '--' . $atts['per_page'];
    }
    $query['sort_by'] = $atts['sort_by'];
    $query['sort_order'] = $atts['sort_order'];
    //* END Prepare search params for get_properties() */

    //* Get Properties */
    $property_ids = WPP_F::get_properties($query, true);

    if (!empty($property_ids['results'])) {
      $properties = array();
      foreach ((array)$property_ids['results'] as $key => $id) {

        $property =  (array) prepare_property_for_display($id, array(
          'load_gallery' => 'false',
          'get_children' => 'false',
          'load_parent' => 'false',
          'scope' => 'supermap_sidebar'
        ));

        $properties[$id] = $property;
      }
    }


    $supermap_configuration['display_attributes'] = (is_array($supermap_configuration['display_attributes']) ? $supermap_configuration['display_attributes'] : array());

    foreach($supermap_configuration['display_attributes'] as $attribute) {
      $display_attributes[$attribute] = $wp_properties['property_stats'][$attribute];
    }

    ob_start();

    if(!empty($properties)) : ?>
      var HTML = '';
      window.supermap_<?php echo $_POST['random']; ?>.total = '<?php echo $property_ids['total']; ?>';
      <?php

      $labels_to_keys = array_flip($wp_properties['property_stats']);

      foreach ($properties as $property_id => $value) {

        $attributes = array();

        $property_stats = WPP_F::get_stat_values_and_labels($value, array(
          'property_stats' => $display_attributes
        ));

        if(is_array($property_stats)) {
          foreach($property_stats as $attribute_label => $attribute_value) {

            $boolean_field = false;

            $attribute_slug = $labels_to_keys[$attribute_label];
            $attribute_data = WPP_F::get_attribute_data($attribute_slug);

            if(empty($attribute_value)) {
              continue;
            }

            if( (  $attribute_data['data_input_type']=='checkbox' && ($attribute_value == 'true' || $attribute_value == 1) ) )
            {
              if($wp_properties['configuration']['google_maps']['show_true_as_image'] == 'true') {
                $attribute_value = '<div class="true-checkbox-image"></div>';
              } else {
                $attribute_value = __('Yes', 'wpp');
              }
              $boolean_field = true;
            } elseif ($attribute_value == 'false') {
              continue;
            }

            $attributes[] =  '<li class="supermap_list_' . $attribute_slug . ' wpp_supermap_attribute_row">';
            $attributes[] =  '<span class="attribute">' . $attribute_label . (!$boolean_field ? ':' : '') . ' </span>';
            $attributes[] =  '<span class="value">' . $attribute_value . '</span>';
            $attributes[] =  '</li>';

          }
        }

        if(in_array('view_property', $supermap_configuration['display_attributes'])) {
          $attributes[] =  '<li class="supermap_list_view_property"><a href="' . get_permalink($value['ID']) . '"><span>'  . __('View Property', 'wpp') . '</span></a></li>';
        }

        if($supermap_configuration['hide_sidebar_thumb'] != 'true') {
          $image = wpp_get_image_link($value['featured_image'], $supermap_configuration['supermap_thumb'], array('return'=>'array'));
        }

      ?>
      window.myLatlng_<?php echo $_POST['random']; ?>_<?php echo $value['ID']; ?> = new google.maps.LatLng(<?php echo $value['latitude']; ?>,<?php echo $value['longitude']; ?>);
      window.content_<?php echo $_POST['random']; ?>_<?php echo $value['ID']; ?> = '<?php echo WPP_F::google_maps_infobox($value); ?>';

      window.marker_<?php echo $_POST['random']; ?>_<?php echo $value['ID']; ?> = new google.maps.Marker({
        position: myLatlng_<?php echo $_POST['random']; ?>_<?php echo $value['ID']; ?>,
        map: map_<?php echo $_POST['random']; ?>,
        title: '<?php echo str_replace("'","\'", $value['location']); ?>',
        icon: '<?php echo apply_filters('wpp_supermap_marker', '', $value['ID']); ?>'
      });

      window.markers_<?php echo $_POST['random']; ?>.push(window.marker_<?php echo $_POST['random']; ?>_<?php echo $value['ID']; ?>);

      google.maps.event.addListener(marker_<?php echo $_POST['random']; ?>_<?php echo $value['ID']; ?>, 'click', function() {
        infowindow_<?php echo $_POST['random']; ?>.close();
        infowindow_<?php echo $_POST['random']; ?>.setContent(content_<?php echo $_POST['random']; ?>_<?php echo $value['ID']; ?>);
        infowindow_<?php echo $_POST['random']; ?>.open(map_<?php echo $_POST['random']; ?>,marker_<?php echo $_POST['random']; ?>_<?php echo $value['ID']; ?>);
        loadFuncy();
        makeActive(<?php echo $_POST['random']; ?>,<?php echo $value['ID']; ?>);
      });

      google.maps.event.addListener(infowindow_<?php echo $_POST['random']; ?>, 'domready', function() {
        document.getElementById('infowindow').parentNode.style.overflow='';
        document.getElementById('infowindow').parentNode.parentNode.style.overflow='';
      });

      bounds_<?php echo $_POST['random']; ?>.extend(window.myLatlng_<?php echo $_POST['random']; ?>_<?php echo $value['ID']; ?>);
      map_<?php echo $_POST['random']; ?>.fitBounds(bounds_<?php echo $_POST['random']; ?>);

      HTML += '<div id="property_in_list_<?php echo $_POST['random']; ?>_<?php echo $value['ID']; ?>" class="property_in_list clearfix">';
      HTML += '<ul class="property_in_list_items clearfix">';
      HTML += '<li class="supermap_list_thumb">';
      HTML += '<span  onclick="showInfobox_<?php echo $_POST['random']; ?>(<?php echo $value['ID']; ?>);">';
      <?php if($supermap_configuration['hide_sidebar_thumb'] != 'true') { ?>
      HTML += '<img src="<?php echo (empty($image['link'])) ? WPP_URL . 'templates/images/no_image.png' : $image['link'];?>" width="<?php echo esc_attr($image['width']); ?>" alt="<?php echo esc_attr($value['post_title']); ?>" />';
      <?php } ?>
      HTML += '</span>';
      HTML += '</li>';
      HTML += '<li class="supermap_list_title">';
      HTML += '<span onclick="showInfobox_<?php echo $_POST['random']; ?>(<?php echo $value['ID']; ?>);"><?php echo addslashes(trim($value['post_title'])) ?></span>';
      HTML += '</li>';
      <?php if(count($attributes) > 0) { echo "HTML += '".addcslashes(implode('', $attributes), "'")."';"; } ?>
      HTML += '</ul></div>';
      <?php } ?>

      var wpp_supermap_<?php echo $_POST['random']; ?> = document.getElementById('super_map_list_property_<?php echo $_POST['random']; ?>');
      wpp_supermap_<?php echo $_POST['random']; ?>.innerHTML += HTML;

    <?php else : ?>

      window.supermap_<?php echo $_POST['random']; ?>.total = '0';

      var wpp_supermap_<?php echo $_POST['random']; ?> = document.getElementById("super_map_list_property_<?php echo $_POST['random']; ?>");
      var y = '<div style="text-align:center;" class="no_properties"><?php _e('No results found.', 'wpp'); ?></div>';

      wpp_supermap_<?php echo $_POST['random']; ?>.innerHTML += y;

    <?php endif; ?>
    <?php

    $result = ob_get_contents();
    ob_end_clean();

    echo WPP_F::minify_js($result);

    exit();
  }

  /**
   * Renders Supermap Content
   *
   * @param array $properties
   * @param array $atts
   * @return string $content HTML
   */
  function supermap_template($properties, $atts = array()) {
    global $wp_properties;

    //* Determine if properties exist */
    if(empty($properties)) {
      return '';
    }

    //* Default settings */
    $hide_sidebar = empty($hide_sidebar) ? false : $hide_sidebar;
    $show_areas = empty($show_areas) ? false : $show_areas;
    $rand = empty($rand) ? '' : $rand;
    $zoom = empty($zoom) ? '' : $zoom;
    $css_class = empty($css_class) ? '' : $css_class;
    $options_label = empty($options_label) ? __('Options','wpp') : $options_label;
    $defaults = array(
      'hide_sidebar' => 'false',
      'css_class' => '',
      'show_areas' => false,
      'sidebar_width' => '',
      'map_height' => '',
      'map_width' => '',
      'zoom' => '',
      'options_label' => __('Options','wpp'),
      'center_on' => '',
      'property_type' => (array) $wp_properties['searchable_property_types'],
      'rand' => rand(1000,5000)
    );

    if(!empty($sidebar_width)) {
      $sidebar_width = trim(str_replace(array('%', 'px'), '', $sidebar_width));
    }

    //* Supermap configuration */
    $supermap_configuration = $wp_properties['configuration']['feature_settings']['supermap'];
    if(empty($supermap_configuration['supermap_thumb'])) {
      $supermap_configuration['supermap_thumb'] = 'thumbnail';
    }

    //* Set available search attributes for 'Options' form */
    $searchable_attributes = (array)$wp_properties['searchable_attributes'];
    $flip =  array_flip($searchable_attributes);
    if(is_array($flip) & is_array($atts)){
      $searchable_attributes = (array_intersect_key($atts, $flip));
    } else {
      unset($searchable_attributes);
    }

    //* Get template Attributes */
    extract(shortcode_atts($defaults, $atts));

    //** Get and set any inline styles */
    if($hide_sidebar != "true" && !empty($sidebar_width)) {
      $inline_styles['sidebar']['width'] = 'width: '. $sidebar_width . '%';
      $inline_styles['map']['width'] = 'width: '. (100 - $sidebar_width). '%;';
      $inline_styles['map']['margin'] = 'margin: 0;'; /* If using fluid widths, must elimiate all margins */
      $inline_styles['map']['padding'] = 'padding: 0;'; /* If using fluid widths, must elimiate all padding */
   }

    if(!isset($inline_styles['map']['width']) && !empty($map_width)) {
      $inline_styles['map']['width'] = 'width: '. str_replace( 'px', '', $map_width ) . 'px;';
    }

    if( !empty($map_height) ) {
      $inline_styles['map']['height'] = 'height: '. str_replace( 'px', '', $map_height ) . 'px;';
      $inline_styles['sidebar']['height'] = 'height: '. str_replace( 'px', '', $map_height ) . 'px;';
    }

    $inline_styles['map'] = 'style="' . implode( ' ', (array) $inline_styles['map']). '"';
    $inline_styles['sidebar'] = 'style="' . implode( ' ', (array) $inline_styles['sidebar'] ) . '"';

    //* START Render Javascript functionality for Areas */
    $areas = $wp_properties['configuration']['feature_settings']['supermap']['areas'];
    $area_lines = array();
    // Plot areas
    if(is_array($areas) && $show_areas) {
      // Check attribute 'show_areas'
      if($show_areas != 'all') {
        $show_areas = explode(',',$show_areas);
        $show_areas = array_fill_keys($show_areas, 1);
      }
      foreach($areas as $count => $area) {
        // If the current area (slug) is not added to shortcode, we didn't draw it.
        if((is_array($show_areas) && !array_key_exists($count, $show_areas)) || $count == 'example_area' ) {
          continue;
        }

        // Set defaults
        if(empty($area['strokeColor'])) $area['strokeColor'] =  '#a49b8a';
        if(empty($area['fillColor'])) $area['fillColor'] =  '#dad1c2';
        if(empty($area['hoverColor'])) $area['hoverColor'] =  '#bfb89a';
        if(empty($area['fillOpacity'])) $area['fillOpacity'] =  '0.6';
        if(empty($area['strokeOpacity'])) $area['strokeOpacity'] =  '1';
        if(empty($area['strokeWeight'])) $area['strokeWeight'] =  '1';

        $area['paths'] = str_replace(")(", ")|(", $area['paths']);
        $area['paths'] = explode("|", $area['paths']);

        if(count($area['paths']) < 1) {
          continue;
        }
        unset($this_area_coords);

        foreach($area['paths'] as $coords) {
          if(empty($coords))
            continue;
          $this_area_coords[] = "new google.maps.LatLng{$coords}";
        }

        if(empty($this_area_coords)) {
          continue;
        }

        $area_lines[] = "var areaCoords_{$count} = [" . implode(",\n", $this_area_coords) . "]";
        $area_lines[] = "
            areaCoords_{$count} = new google.maps.Polygon({
            paths: areaCoords_{$count},
            strokeColor: '{$area['strokeColor']}',
            strokeOpacity: {$area['strokeOpacity']},
            strokeWeight: {$area['strokeWeight']},
            fillColor: '{$area['fillColor']}',
            fillOpacity: {$area['fillOpacity']}
          });
          areaCoords_{$count}.setMap(map_{$rand});
          google.maps.event.addListener(areaCoords_{$count},'click',function(event){
            // Set content and Replace our Info Window's position
            infowindow_{$rand}.setContent('<div id=\"infowindow\" style=\"height:50px;line-height:50px;text-align:center;font-weight:bold;\">{$area['name']}</div>');
            infowindow_{$rand}.setPosition(event.latLng);
            infowindow_{$rand}.open(map_{$rand});
          });
          google.maps.event.addListener(areaCoords_{$count},'mouseover',function(event){
            this.setOptions({
              fillColor: '{$area['hoverColor']}'
            });
          });
          google.maps.event.addListener(areaCoords_{$count},'mouseout',function(event){
            this.setOptions({
              fillColor: '{$area['fillColor']}'
            });
          });
        ";
      }
    }
    $area_lines = implode('', $area_lines);

    //* END Render Areas */

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
          mapTypeId: google.maps.MapTypeId.ROADMAP
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
        <?php if ($value['latitude'] && $value['longitude']) : ?>
        window.myLatlng_<?php echo $rand; ?>_<?php echo $value['ID']; ?> = new google.maps.LatLng(<?php echo $value['latitude']; ?>,<?php echo $value['longitude']; ?>);
        window.content_<?php echo $rand; ?>_<?php echo $value['ID']; ?> = '<?php echo WPP_F::google_maps_infobox($value); ?>';

        window.marker_<?php echo $rand; ?>_<?php echo $value['ID']; ?> = new google.maps.Marker({
          position: myLatlng_<?php echo $rand; ?>_<?php echo $value['ID']; ?>,
          map: map_<?php echo $rand; ?>,
          title: '<?php echo str_replace("'","\'", !empty($value[$wp_properties['configuration']['address_attribute']]) ? $value[$wp_properties['configuration']['address_attribute']] : '' ); ?>',
          icon: '<?php echo apply_filters('wpp_supermap_marker', '', $value['ID']); ?>'
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
          document.getElementById('infowindow').parentNode.style.overflow='hidden';
          document.getElementById('infowindow').parentNode.parentNode.style.overflow='hidden';
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
    $return_content['script'] = ob_get_contents();
    ob_end_clean();

    //** For now if only one attribute exists, and it's the property_type, we do not render the form at all */
    if(count($searchable_attributes) == 1 && in_array('property_type', array_keys((array)$searchable_attributes))) {
      $searchable_attributes = false;
    }

    $supermap_configuration['display_attributes'] = (is_array($supermap_configuration['display_attributes']) ? $supermap_configuration['display_attributes'] : array());

    foreach($supermap_configuration['display_attributes'] as $attribute) {
      $display_attributes[$attribute] = $wp_properties['property_stats'][$attribute];
    }

    ob_start();
    ?>
    <div id="map_cont_<?php echo $rand; ?>" class="wpp_supermap_wrapper <?php echo $css_class; ?>" supermap_id="<?php echo $rand; ?>">
      <div id="super_map_<?php echo $rand; ?>" class="super_map <?php if($hide_sidebar == 'true'): ?>no_sidebar<?php endif; ?>" <?php echo $inline_styles['map']; ?>></div>
      <?php if($hide_sidebar == 'false'): ?>
        <div id="super_map_list_<?php echo $rand; ?>" class="super_map_list" <?php echo $inline_styles['sidebar']; ?>>
        <?php if (!empty($searchable_attributes)) : ?>
          <?php //* hide the option link if  supermap shortcode doesn't include any attribute connected with sortable attribute */ ?>
          <div class="supermap_filter_wrapper">
            <div class="hide_filter">
              <a onclick="jQuery('#map_filters_<?php echo $rand; ?>').slideToggle('fast');return false;" href="javascript:;"><?php echo $options_label; ?></a>
            </div>
            <div id="map_filters_<?php echo $rand; ?>" class="map_filters">
              <?php //* Dynamic search options (attributes sets in shortcode) */ ?>
      <?php class_wpp_supermap::draw_supermap_options_form($searchable_attributes, $atts['property_type'], $rand); ?>
            </div>
          </div><!-- END  .supermap_filter_wrapper -->
        <?php endif; ?>
        <div id="super_map_list_property_<?php echo $rand; ?>" class="super_map_list_property">
        <?php if (!empty($properties)) {

          foreach ($properties as $key => $value) {
            $attributes = array();

            $property_stats = WPP_F::get_stat_values_and_labels($value, array(
              'property_stats' => $display_attributes
            ));

            if(is_array($property_stats)) {
              $labels_to_keys = array_flip($wp_properties['property_stats']);

              foreach($property_stats as $attribute_label => $attribute_value) {
                $boolean_field = false;
                $attribute_slug = $labels_to_keys[$attribute_label];
                $attribute_data = WPP_F::get_attribute_data($attribute_slug);

                if(empty($attribute_value)) {
                  continue;
                }

                if( (  $attribute_data['data_input_type']=='checkbox' && ($attribute_value == 'true' || $attribute_value == 1) ) )
                {
                  if($wp_properties['configuration']['google_maps']['show_true_as_image'] == 'true') {
                    $attribute_value = '<div class="true-checkbox-image"></div>';
                  } else {
                    $attribute_value = __('Yes', 'wpp');
                  }
                  $boolean_field = true;
                } elseif ($attribute_value == 'false') {
                  continue;
                }

                $attributes[] =  '<li class="supermap_list_' . $attribute_slug . ' wpp_supermap_attribute_row">';
                $attributes[] =  '<span class="attribute">' . $attribute_label . (!$boolean_field ? ':' : '') . ' </span>';
                $attributes[] =  '<span class="value">' . $attribute_value . '</span>';
                $attributes[] =  '</li>';
              }
            }

            if(in_array('view_property', $supermap_configuration['display_attributes'])) {
              $attributes[] =  '<li class="supermap_list_view_property"><a href="' . get_permalink($value['ID']) . '" class="btn btn-info btn-small"><span>'  . __('View Property', 'wpp') . '</span></a></li>';
            }
            $image = array();
            if($supermap_configuration['hide_sidebar_thumb'] != 'true') {
              $image = wpp_get_image_link($value['featured_image'], $supermap_configuration['supermap_thumb'], array('return'=>'array'));
            }

            ?>
            <?php if ($value['latitude'] && $value['longitude'] && $value['ID']) { ?>
              <div id="property_in_list_<?php echo $rand; ?>_<?php echo $value['ID']; ?>" class="property_in_list clearfix">
                <ul class='property_in_list_items clearfix'>
                  <?php if($supermap_configuration['hide_sidebar_thumb'] != 'true') { ?>
                  <li class='supermap_list_thumb'><span  onclick="showInfobox_<?php echo $rand; ?>(<?php echo $value['ID']; ?>);"><img class="<?php echo ($image['link'] ? 'wpp_supermap_thumb' : 'wpp_supermap_thumb wpp_default_iamge'); ?>" src="<?php echo (empty($image['link']) ? WPP_URL . 'templates/images/no_image.png' : $image['link']); ?>" style="<?php echo ($image['width'] ? 'width: '.$image['width'].'px; ' : ''); ?>" alt="<?php echo $value['post_title']; ?>" /></span></li>
                  <?php } ?>
                  <li class='supermap_list_title'><span onclick="showInfobox_<?php echo $rand; ?>(<?php echo $value['ID']; ?>);"><?php echo  stripslashes($value['post_title']); ?></span></li>
                <?php if(count($attributes) > 0) { echo implode('', $attributes); } ?>
                </ul>
              </div>
            <?php } ?>
          <?php } ?>
        <?php } ?>
        </div>
        <?php if($atts['pagination'] == 'on') { ?>
        <div class="show_more btn" style="<?php echo count($properties) < $atts['total'] ? '' : 'display:none;'; ?>">
          <?php _e('Show More', 'wpp'); ?>
          <div class="search_loader" style="display:none"><?php _e('Loading...', 'wpp'); ?></div>
        </div>
        <?php }?>
      </div>

      <?php endif; /*hide_sidebar */?>
    <br class="cb clear" />
    </div>
    <?php
    $return_content['html'] = ob_get_contents();
    ob_end_clean();

    $return_content['script'] = WPP_F::minify_js($return_content['script']);


    return implode($return_content);

  }

  /**
   * Draws Option Form on sidebar of Supermap
   *
   * @param $search_attributes
   * @param $searchable_property_types
   * @param $rand
   */
  function draw_supermap_options_form($search_attributes = false, $searchable_property_types = false, $rand = 0) {
    global $wp_properties;
    if(!$search_attributes) {
      return;
    }

    $search_values = WPP_F::get_search_values(array_keys((array)$search_attributes), $searchable_property_types );
    ?>
      <form id="formFilter_<?php echo $rand; ?>" name="formFilter" action="">
        <div class="class_wpp_supermap_elements">
          <ul>
            <?php if(is_array($search_attributes)) foreach($search_attributes as $attrib => $search_value) {
              // Don't display search attributes that have no values
              if(!isset($search_values[$attrib]))
                continue;

              $delimiter = ',';
              /* Set $search_value to use it in form fields */
              if (strtolower($search_value) == 'all') {
                $search_value = '';
              } else if (strpos($search_value, '-') !== false) {
                $v = explode('-', $search_value);
                if(is_array($v) && count($v) == 2 && ((int)$v[0] > 0 || (int)$v[1] > 0)) {
                  $delimiter = '-';
                  $search_value = array(
                    'min' => (int)$v[0],
                    'max' => (int)$v[1],
                  );
                }
              } else if (strpos($search_value, ',') !== false) {
                $v = explode(',', $search_value);
                if(is_array($v)) {
                  $search_value = $v;
                }
              }
            ?>
            <li class="seach_attribute_<?php echo $attrib; ?> field_<?php echo $wp_properties['searchable_attr_fields'][$attrib]; ?>">
              <label class="class_wpp_supermap_label class_wpp_supermap_label_<?php echo $attrib; ?>" for="class_wpp_supermap_input_field_<?php echo $attrib; ?>_<?php echo $rand; ?>">
                <?php echo (empty($wp_properties['property_stats'][$attrib]) ? ucwords($attrib) : $wp_properties['property_stats'][$attrib]) ?>:
              </label>
              <?php
              wpp_render_search_input(array(
                'attrib' => $attrib,
                'random_element_id' => "class_wpp_supermap_input_field_{$attrib}_{$rand}",
                'search_values' => $search_values,
                'value' => $search_value
              ));
              ?>
              <div class="clear"></div>
            </li>
          <?php } ?>
        </ul>
        <input class="search_b btn" type="button" value="Search" onclick="getProperties(<?php echo $rand; ?>)" />
        <div class="search_loader" style="display:none"><?php _e('Loading','wpp') ?></div>
      </div> <?php //end of class_wpp_supermap_elements ?>
    </form>
  <?php
  }

  /**
   * Check specific supermap keys to properties
   * and add/remove/modify them to avoid issues on supermap
   * &
   * Check supermap files (markers): remove unused.
   *
   * @author Maxim Peshkov
   */
  function settings_save($wpp_settings) {
    global $wpdb;

    //* START Markers (files) checking */
    $upload_dir = wp_upload_dir();
    $markers_dir = $upload_dir['basedir'] . '/supermap_files/markers';
    $markers = $wpp_settings['configuration']['feature_settings']['supermap']['markers'];
    //* Get all markers files */
    $files = array();
    foreach ((array)$markers as $marker) {
      if(!empty($marker['file'])) {
        $files[] = $marker['file'];
      }
    }
    //* Remove image if it's not related to marker */
    if(file_exists($markers_dir)) {
      if ($dh = opendir($markers_dir)) {
        while (($file = readdir($dh)) !== false) {
          if (!is_dir($file) && preg_match("/(.*)\.(bmp|jpe?g|gif|png)$/", $file, $matches)) {
            if (!in_array($file, $files)) {
              unlink($markers_dir . '/' . $file);
            }
          }
        }
        closedir($dh);
      }
    }
    //* END Markers (files) checking */
    return $wpp_settings;
  }

  /**
   * Filters every import attribute
   *
   * @global array $wp_properties
   * @param mixed $value
   * @param string $attribute
   * @param string $type
   * @param int $post_id
   * @return mixed
   * @author korotkov@ud
   */
  function importer_meta_filter( $value, $attribute, $type, $post_id ) {
    global $wp_properties;

    /**
     * Add missed meta required for Supermap
     */
    if ( $type && $type == 'meta_field' ) {
      $address_attribute = !empty( $wp_properties['configuration']['address_attribute'] ) ? $wp_properties['configuration']['address_attribute'] : '';
      if ( $address_attribute == $attribute ) {
        update_post_meta($post_id, 'exclude_from_supermap', 'false');
      }
    }

    return $value;
  }

}

endif; // Class Exists
