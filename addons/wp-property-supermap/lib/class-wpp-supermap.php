<?php
/**
 * Core
 */

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
  static public function pre_init() {
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
  static public function init() {

    add_filter ( 'wp_prepare_attachment_for_js',  array(__CLASS__, 'add_image_sizes_to_js') , 10, 3  );

    wp_register_script('wpp-supermap-settings', ud_get_wpp_supermap()->path( 'static/scripts/supermap.settings.js', 'url' ), array('jquery'), '1.0.0');
    wp_enqueue_style('wp-property-supermap', ud_get_wpp_supermap()->path('static/styles/wp-property-supermap.css', 'url'));

    add_shortcode('supermap', array('class_wpp_supermap', 'shortcode_supermap'));
    add_image_size( 'supermap_marker', 32, 32, 0 );

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
      add_action('save_post', array('class_wpp_supermap','save_post'));

      add_action('wpp_publish_box_options', array('class_wpp_supermap','property_supermap_options'));
    }
    //** Filter meta keys during import process @author korotkov@ud */
    add_filter('wpp_xml_import_value_on_import', array('class_wpp_supermap', 'importer_meta_filter'), 10, 4);
    add_filter('strict_search', array('class_wpp_supermap', 'apply_strict_search'), 10, 2);
  }

  /**
   * @param $response
   * @param $attachment
   * @param $meta
   * @return mixed
   */
  public static function add_image_sizes_to_js( $response, $attachment, $meta ) {

    $size_array = array( 'supermap_marker' ) ;

    foreach ( $size_array as $size ):

      if ( isset( $meta['sizes'][ $size ] ) ) {
        $attachment_url = wp_get_attachment_url( $attachment->ID );
        $base_url = str_replace( wp_basename( $attachment_url ), '', $attachment_url );
        $size_meta = $meta['sizes'][ $size ];

        $response['sizes'][ $size ] = array(
            'height'        => $size_meta['height'],
            'width'         => $size_meta['width'],
            'url'           => $base_url . $size_meta['file'],
            'orientation'   => $size_meta['height'] > $size_meta['width'] ? 'portrait' : 'landscape',
        );
      }

    endforeach;

    return $response;
  }

  /**
   * Adds Custom capability to the current premium feature
   *
   * @param array $capabilities
   * @return array $capabilities
   */
  static public function add_capability($capabilities) {

    $capabilities[self::$capability] = __('Manage Supermap',ud_get_wpp_supermap()->domain);

    return $capabilities;
  }

  /**
   * Adds slideshow menu to settings page navigation
   * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
   *
   * @param array $tabs
   */
  static public function settings_nav($tabs) {
    $tabs['supermap'] = array(
      'slug' => 'supermap',
      'title' => __('Supermap',ud_get_wpp_supermap()->domain)
    );
    return $tabs;
  }

  /**
   * Add Supermap tab's content on Settings Page
   *
   */
  static public function settings_page() {
    global $wp_properties, $wpdb, $class_wpp_slideshow;

    wp_enqueue_media();

    $supermap_configuration = isset( $wp_properties['configuration']['feature_settings']['supermap'] ) ? 
      $wp_properties['configuration']['feature_settings']['supermap'] : array();
    $default_marker = isset($supermap_configuration['default_marker'])?$supermap_configuration['default_marker']:'';

    $supermap_configuration = wp_parse_args( $supermap_configuration, array(
      'markers' => array(),
      'areas' => array(),
      'display_attributes' => array(),
      'hide_sidebar_thumb' => false,
      'supermap_thumb' => false,
    ) );

    //* Set default Marker */
    if(empty( $supermap_configuration['markers']) ) {
      $supermap_configuration['markers']['custom']['name'] = 'Custom';
      $supermap_configuration['markers']['custom']['file'] = '';
    }

    //* Set example of Area */
    if(empty($supermap_configuration['areas'])) {
      $supermap_configuration['areas']['example_area']['name'] = __( 'Example Area', ud_get_wpp_supermap()->domain );
      $supermap_configuration['areas']['example_area']['hoverColor'] = '';
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
        background: url(<?php echo WPP_URL; ?>images/ajax_loader.gif) center center no-repeat;
        display: none;
      }
      #wpp_supermap_markers .wpp_supermap_ajax_uploader {
        padding:5px 0;
      }
      #wpp_supermap_markers .wpp_supermap_ajax_uploader img {
        max-width: 100%;
        max-height: 100%;
      }
    </style>
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row"><?php _e('Sidebar Attributes', ud_get_wpp_supermap()->domain); ?></th>
          <td>
            <p><?php _e('Select the attributes you want to display in the left sidebar on the supermap.', ud_get_wpp_supermap()->domain); ?></p>
            <div class="wp-tab-panel">
            <ul>
              <?php foreach($wp_properties['property_stats'] as $slug => $title): ?>
                <li>
                  <input <?php if(@in_array($slug, (array)$supermap_configuration['display_attributes'])) echo " CHECKED ";  ?> value='<?php echo $slug; ?>' type="checkbox" id="display_attribute_<?php echo $slug; ?>" name="wpp_settings[configuration][feature_settings][supermap][display_attributes][]" />
                  <label for="display_attribute_<?php echo $slug; ?>"><?php echo $title; ?></label>
                </li>
              <?php endforeach; ?>
            </ul>
            </div>
            <ul style="margin-top:10px;">
              <li>
                <input <?php if(@in_array('view_property', (array)$supermap_configuration['display_attributes'])) echo " CHECKED ";  ?> value='view_property' type="checkbox" id="display_attribute_view_property" name="wpp_settings[configuration][feature_settings][supermap][display_attributes][]" />
                <label for="display_attribute_view_property"><?php printf(__('Display "View %s" link in the left sidebar. It directs user to %s Page.',ud_get_wpp_supermap()->domain), WPP_F::property_label(), WPP_F::property_label()); ?></label>
              </li>
            </ul>
          </td>
        </tr>
        <tr>
          <th><?php _e('Supermap Sidebar Thumbnail:',ud_get_wpp_supermap()->domain) ?></th>
          <td>
            <ul>
              <li>
                <input <?php if( isset( $supermap_configuration['hide_sidebar_thumb'] ) ) checked( 'true', $supermap_configuration[ 'hide_sidebar_thumb' ] ); ?> value='true' type="checkbox" id="supermap_hide_sidebar_thumb" name="wpp_settings[configuration][feature_settings][supermap][hide_sidebar_thumb]" />
                <label for="supermap_hide_sidebar_thumb"><?php _e('Do not show a property thumbnail in sidebar.',ud_get_wpp_supermap()->domain) ?></label>
              </li>
              <li><?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][feature_settings][supermap][supermap_thumb]&selected=" . (isset( $supermap_configuration['supermap_thumb'] ) && $supermap_configuration['supermap_thumb' ] ? $supermap_configuration['supermap_thumb'] : 'thumbnail' ) ); ?></li>
              <li><?php _e('If you create a new image size, please be sure to regenerate all thumbnails. ',ud_get_wpp_supermap()->domain) ?></li>
            </ul>
          </td>
        </tr>
        <tr>
          <th><?php _e('Map Markers:',ud_get_wpp_supermap()->domain) ?></th>
          <td>
            <table id="wpp_supermap_markers" class="wpp_sortable ud_ui_dynamic_table widefat" allow_random_slug="true">
              <thead>
                <tr>
                  <th style="width:10px;" class="wpp_draggable_handle">&nbsp;</th>
                  <th style="width:50px;"><?php _e('Image',ud_get_wpp_supermap()->domain) ?></th>
                  <th style="width:150px;"><?php _e('Name',ud_get_wpp_supermap()->domain) ?></th>
                  <th style="width:250px;"><?php _e('Slug',ud_get_wpp_supermap()->domain) ?></th>
                  <th style="width:50px;">&nbsp;</th>
                </tr>
              </thead>
              <tbody>
              <?php
              $upload_dir = wp_upload_dir();
              $markers_url = $upload_dir['baseurl'] . '/supermap_files/markers';
              foreach($supermap_configuration['markers'] as $slug => $marker): ?>

                <?php
                  $marker_image_url = preg_match( '/(http|https):\/\//', $marker['file'] )
                                      ? $marker['file'] : $markers_url . '/' . $marker['file'];
                ?>

                <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row='false'>
                  <td class="wpp_draggable_handle">&nbsp;</td>
                  <td class="wpp_ajax_image_upload">
                    <div class="wpp_supermap_ajax_uploader">
                    <?php if(!empty($marker_image_url)) : ?>
                      <img class="wpp_marker_image" src="<?php echo $marker_image_url; ?>" alt="" />
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
                    <span class="wpp_delete_row wpp_link"><?php _e('Delete',ud_get_wpp_supermap()->domain) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan='5'>
                  <input type="button" class="wpp_add_row button-secondary btn" value="<?php _e('Add Marker',ud_get_wpp_supermap()->domain) ?>" />
                  </td>
                </tr>
              </tfoot>
            </table>
            <script type="text/javascript">
              jQuery(document).ready(function(){

                jQuery('.wpp_ajax_image_upload').map_marker_select({
                  image: "img"
                }).on('change', function(event, image_url){
                  var slug = jQuery(this).parents('.wpp_dynamic_table_row').attr('slug');
                  jQuery(document).trigger('wpp_supermap_marker_image_changed', [slug, image_url]);
                });

                /* Remove ajaxuploader and image on Slug changing */
                jQuery(document).on('change', '#wpp_supermap_markers input.slug', function(){
                  var e = this;
                  var row = jQuery(e).parents('.wpp_dynamic_table_row');
                  if(row.length > 0) {
                    var slug = row.attr('slug');
                    eval('window.wpp_uploader_'+slug+' = null');
                    jQuery('.wpp_supermap_ajax_uploader', row).html('');
                    jQuery('input.wpp_supermap_marker_file', row).val('');
                  }
                });

                /* Remove image from new Row */
                jQuery(document).on('added', '#wpp_supermap_markers tr', function(){
                  jQuery('input.slug', this).trigger('change');
                  jQuery('.wpp_ajax_image_upload', this).map_marker_select({
                    image: "img"
                  }).on('change', function(event, image_url){
                    var slug = jQuery(this).parents('.wpp_dynamic_table_row').attr('slug');
                    jQuery(document).trigger('wpp_supermap_marker_image_changed', [slug, image_url]);
                  });
                  jQuery(document).trigger('wpp_supermap_marker_added');
                });

                /* Fire event after row removing to check table's DOM */
                jQuery(document).on('row_removed', '#wpp_supermap_markers', function(){
                  var row_count = jQuery(this).find(".wpp_delete_row:visible").length;
                  if(row_count == 1) {
                    var slug = jQuery(this).find('input.wpp_marker_slug').val();
                    if(slug == '') {
                      jQuery('.wpp_supermap_ajax_uploader', this).html('');
                      jQuery('input.wpp_supermap_marker_file', this).val('');
                      jQuery('tr', this).attr('new_row', 'true');
                    }
                  };
                  jQuery(document).trigger('wpp_supermap_marker_removed');
                });

              });
            </script>
          </td>
        </tr>
        <tr>
          <th><?php _e('Default Map Marker:',ud_get_wpp_supermap()->domain) ?></th>
          <td>
            <ul>
              <li>
                <select id="supermap_default_marker" name="wpp_settings[configuration][feature_settings][supermap][default_marker]">
                  <option value="">Select Marker</option>
                <?php foreach($supermap_configuration['markers'] as $slug => $marker): ?>
                  <option value="<?php echo $slug; ?>" <?php selected($default_marker, $slug);?>><?php echo !empty($marker['name']) ? $marker['name'] : $slug; ?></option>
                <?php endforeach; ?>
                </select>
              </li>
              <li><?php _e('Set you default marker. ',ud_get_wpp_supermap()->domain) ?></li>
            </ul>
          </td>
        </tr>
        <tr>
          <th><?php _e('Map Areas:',ud_get_wpp_supermap()->domain) ?></th>
          <td>
            <?php _e('<p>Map areas let you draw our areas on the map, such as neighborhoods.</p><p>Just add to shortcode attribute <b>show_areas=all</b> to draw all areas on the map. Also You can use area\'s slugs to show them on the map, like as <b>show_areas=new_york,washington</b>. Please, use coordinates in this format: <b>(82.72, -37.79)(69.54, -57.48)(68.93, -18.63).</b></p><p><i>This is an experimental feature, you may not want to use it on a live site.  We\'re eager to hear your feedback regarding this feature and the capabilities that would be useful to you.</i></p>',ud_get_wpp_supermap()->domain) ?>
            <table id="wpp_supermap_areas" class="ud_ui_dynamic_table widefat">
              <thead>
                <tr>
                  <th><?php _e('Name',ud_get_wpp_supermap()->domain) ?></th>
                  <th style="width:50px;"><?php _e('Coordinates',ud_get_wpp_supermap()->domain) ?></th>
                  <th><?php _e('Fill Color',ud_get_wpp_supermap()->domain) ?></th>
                  <th><?php _e('Opacity',ud_get_wpp_supermap()->domain) ?></th>
                  <th><?php _e('Stoke Color',ud_get_wpp_supermap()->domain) ?></th>
                  <th><?php _e('Hover Color',ud_get_wpp_supermap()->domain) ?></th>
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
                      <span class="wpp_delete_row wpp_link"><?php _e('Delete',ud_get_wpp_supermap()->domain) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan='7'>
                  <input type="button" class="wpp_add_row button-secondary btn" value="<?php _e('Add Row',ud_get_wpp_supermap()->domain) ?>" />
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
  static public function property_type_settings($settings, $slug) {
    global $wp_properties;

    $supermap_configuration = isset( $wp_properties['configuration']['feature_settings']['supermap'] ) ? 
      $wp_properties['configuration']['feature_settings']['supermap'] : array();
    $upload_dir = wp_upload_dir();
    $markers_url = $upload_dir['baseurl'] . '/supermap_files/markers';
    $markers_dir = $upload_dir['basedir'] . '/supermap_files/markers';

    $default_google_map_marker = $default_marker = apply_filters( 'wpp:default_pin_icon', WPP_URL . 'images/google_maps_marker.png' );

    if(!empty($supermap_configuration['default_marker'])){
      $dm_slug = $supermap_configuration['default_marker'];
      $default_marker = !empty($supermap_configuration['markers'][$dm_slug]['file'])?$supermap_configuration['markers'][$dm_slug]['file']:$default_marker;
    }

    $selected_marker = !empty($supermap_configuration['property_type_markers'][$slug])?$supermap_configuration['property_type_markers'][$slug]:'';

    ob_start();
    ?>
    <div class="wp-tab-panel supermap_marker_settings">
    <div class="wpp_property_type_supermap_settings">
      <div class="wpp_supermap_marker_image">
        <img src="<?php echo $default_marker; ?>" alt="" />
      </div>
      <div class="wpp_supermap_marker_selector">
      <label for="wpp_setting_property_type_<?php echo $slug ?>_marker"><?php _e('Map Marker', ud_get_wpp_supermap()->domain); ?>:</label>
      <select class="wpp_setting_property_type_marker" id="wpp_setting_property_type_<?php echo $slug ?>_marker" name="wpp_settings[configuration][feature_settings][supermap][property_type_markers][<?php echo $slug; ?>]" >
        <option value=""><?php _e('Select Marker', ud_get_wpp_supermap()->domain); ?></option>
        <option value="default_google_map_marker" <?php selected($selected_marker, 'default_google_map_marker'); ?>><?php _e('Default by Google', ud_get_wpp_supermap()->domain); ?></option>
        <?php if( !empty( $supermap_configuration['markers'] ) && is_array( $supermap_configuration['markers'] ) ) : ?>
          <?php foreach ($supermap_configuration['markers'] as $mslug => $mvalue ) : ?>
            <option slug="<?php echo $mslug;?>" value="<?php echo $mvalue['file']; ?>" <?php selected($selected_marker, $mvalue['file']); ?>><?php echo $mvalue['name']; ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
      </div>
      <div class="clear"></div>
    </div>
    <script type="text/javascript">
      jQuery(document).ready(function(){
        if(typeof property_type_marker_events == 'undefined') {
          window.wpp_default_marker = '<?php echo $default_marker; ?>';
          /* Change marker's image preview on marker changing */
          jQuery(document).on('change', 'select.wpp_setting_property_type_marker', function(){
            var e = jQuery(this).parents('.wpp_property_type_supermap_settings');
            var filename = jQuery(this).val();
            var rand = Math.random();
            var HTML = '';
            if(filename == 'default_google_map_marker') {
              HTML = '<img src="' + '<?php echo $default_google_map_marker;?>' + '?' + rand + '" alt="" />';
            } else if(filename != '') {
              HTML = '<img src="' + filename + '?' + rand + '" alt="" />';
            } else {
              HTML = '<img src="' + wpp_default_marker + '" alt="" />';
            }
            e.find('.wpp_supermap_marker_image').html(HTML);
          });

          /* Fire marker's image changing Event after marker image changed in supermap tab */
          jQuery(document).on('wpp_supermap_marker_image_changed', function(event, slug, image_url){
            var dMarker = jQuery('#supermap_default_marker').val();
            if(dMarker == slug)
              window.wpp_default_marker = image_url;
            jQuery('select.wpp_setting_property_type_marker option', this).each(function(){
              if(jQuery(this).attr('slug') == slug && jQuery(this).val() != image_url)
                jQuery(this).val(image_url).trigger('change');
            });
          });
          
          /* Fire marker's image changing Event after marker image changed in supermap tab */
          jQuery('#supermap_default_marker').on('change', function(event){
            var dMarker = jQuery(this).val();
            jQuery('#wpp_supermap_markers .wpp_dynamic_table_row').each(function(){
              if(jQuery(this).attr('slug') == dMarker){
                window.wpp_default_marker = jQuery(this).find('.wpp_supermap_marker_file').val();
                jQuery('select.wpp_setting_property_type_marker').trigger('change');
                return false;
              }
            });


          });
          
          jQuery('select.wpp_setting_property_type_marker', this).trigger('change');

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
   * Enqueue scripts on specific pages, and print content into head
   *
   * @uses $current_screen global variable
   * @author Maxim Peshkov
   */
  static public function admin_enqueue_scripts() {
    global $current_screen, $wp_properties;

    //* WPP Settings Page */
    if($current_screen->id == 'property_page_property_settings') {
      wp_enqueue_script('wpp-supermap-settings');
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
        padding-bottom:5px;
        background: #ffffff;
        overflow:hidden;
        border: 1px solid #DFDFDF;
        text-align:center;
      }
      .supermap_marker_settings .wpp_supermap_marker_image img {
        max-width: 100%;
        max-height: 100%;
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
  static public function supermap_template_redirect(){
    global $post;

    if( $post && strpos($post->post_content, "supermap")) {
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
  static public function add_metabox(){
    //add_meta_box( 'wp_property_supermap', __( 'Supermap Options', ud_get_wpp_supermap()->domain ), array('class_wpp_supermap','property_supermap_options'), 'property', 'side' );
  }

  /**
   * Renders content for metabox
   *
   */
  static public function property_supermap_options(){
    global $post_id, $wp_properties;

    //* Exclude From Supermap checkbox */
    $disable_exclude = get_post_meta($post_id, 'exclude_from_supermap', true);
    $text = __('Exclude property from Supermap',ud_get_wpp_supermap()->domain);
    echo WPP_F::checkbox("name=exclude_from_supermap&id=exclude_from_supermap&label=$text", $disable_exclude);

    //* START Renders Supermap Marker's settings */
    //* Get supermap marker for the current property */
    $supermap_marker = get_post_meta($post_id, 'supermap_marker', true);

    $default_google_map_marker = $default_marker = apply_filters( 'wpp:default_pin_icon', WPP_URL . 'images/google_maps_marker.png' );

    $supermap_configuration = !empty( $wp_properties['configuration']['feature_settings']['supermap'] ) ? $wp_properties['configuration']['feature_settings']['supermap'] : array();
    if(empty($supermap_configuration['property_type_markers'])) {
      $supermap_configuration['property_type_markers'] = array();
    }

    if(!empty($supermap_configuration['default_marker'])){
      $dm_slug = $supermap_configuration['default_marker'];
      $default_marker = !empty($supermap_configuration['markers'][$dm_slug]['file'])?$supermap_configuration['markers'][$dm_slug]['file']:$default_marker;
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
    } else if($supermap_marker == 'default_google_map_marker') {
      $marker_url = $default_google_map_marker;
    } else {
      if ( preg_match( '/(http|https):\/\//', $supermap_marker ) ) {
        $marker_url = $supermap_marker;
      } else {
        $marker_url = $markers_url . "/" . $supermap_marker;
        $marker_dir = $markers_dir . "/" . $supermap_marker;
        if (!file_exists($marker_dir)) {
          $marker_url = $default_marker;
        }
      }
    }
    ?>
    <div class="wp-tab-panel supermap_marker_settings" id="wpp_supermap_marker_settings" style="margin-top:10px;">
      <div class="wpp_supermap_marker_image">
        <img src="<?php echo $marker_url; ?>" alt="" />
      </div>
      <div class="wpp_supermap_marker_selector">
      <label for="wpp_setting_supermap_marker"><?php _e('Map Marker', ud_get_wpp_supermap()->domain); ?>:</label>
      <select id="wpp_setting_supermap_marker" name="supermap_marker">
        <option value=""><?php _e('Select Marker', ud_get_wpp_supermap()->domain); ?></option>
        <option value="default_google_map_marker" <?php selected($supermap_marker, 'default_google_map_marker'); ?>><?php _e('Default by Google', ud_get_wpp_supermap()->domain); ?></option>
        <?php if(!empty($supermap_configuration['markers'])) : ?>
          <?php foreach ($supermap_configuration['markers'] as $mslug => $mvalue) : ?>
            <?php
            $marker_image_url = preg_match( '/(http|https):\/\//', $mvalue['file'] )
                ? $mvalue['file'] : $marker_url . '/' . $mvalue['file'];
            ?>
            <option value="<?php echo $marker_image_url; ?>" <?php selected($supermap_marker, $marker_image_url); ?>><?php echo $mvalue['name']; ?></option>
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
          jQuery(document).on('change', '#wpp_setting_supermap_marker', function(){
            var e = jQuery('#wpp_supermap_marker_settings');
            var filename = jQuery(this).val();
            var rand = Math.random();
            var HTML = '';
            if(filename == 'default_google_map_marker') {
              HTML = '<img src="' + '<?php echo $default_google_map_marker;?>' + '?' + rand + '" alt="" />';
            } else if(filename != '' && filename != 'default_google_map_marker') {
              HTML = '<img src="' + filename + '?' + rand + '" alt="" />';
            } else {
              HTML = '<img src="<?php echo $default_marker; ?>" alt="" />';
            }
            e.find('.wpp_supermap_marker_image').html(HTML);
          });

          /* Change supermap marker on Property Type 'change' Event */
          jQuery(document).on('change', '#wpp_meta_property_type', function(){
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
  static public function save_post($post_id){
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
        $supermap_configuration = isset( $wp_properties['configuration']['feature_settings']['supermap'] ) ? 
          $wp_properties['configuration']['feature_settings']['supermap'] : array();

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
  static public function get_marker_by_post_id($marker_url = '', $post_id) {
    global $wp_properties;

    if(!isset($wp_properties['configuration']['feature_settings']['supermap'])) {
      return $marker_url;
    }

    $supermap_configuration = $wp_properties['configuration']['feature_settings']['supermap'];
    if(empty($supermap_configuration['property_type_markers'])) {
      $supermap_configuration['property_type_markers'] = array();
    }

    //* Get supermap marker for the current property */
    $supermap_marker = get_post_meta($post_id, 'supermap_marker', true);

    //* Return empty string if property uses default marker */
    if($supermap_marker == 'default_google_map_marker') {
      return $marker_url;
    }

    $property_type = get_post_meta($post_id, 'property_type', true);
    if(
      empty($supermap_marker) &&
      !empty($property_type) &&
      !empty( $supermap_configuration['property_type_markers'][$property_type] )
    ) {
      $supermap_marker = $supermap_configuration['property_type_markers'][$property_type];
    }

    //* Return again empty string if property uses default marker in Types developer tab */
    if($supermap_marker == 'default_google_map_marker') {
      return $marker_url;
    }

    if(empty($supermap_marker) && !empty($supermap_configuration['default_marker'])){
      $dm_slug = $supermap_configuration['default_marker'];
      if(!empty($supermap_configuration['markers'][$dm_slug]['file']))
        $supermap_marker = $supermap_configuration['markers'][$dm_slug]['file'];
    }

    $upload_dir = wp_upload_dir();
    $markers_url = $upload_dir['baseurl'] . '/supermap_files/markers';
    $markers_dir = $upload_dir['basedir'] . '/supermap_files/markers';

    //* Set default marker image */
    if(empty($supermap_marker)) {
      $marker_url = '';
    } else {
      if ( preg_match( '/(http|https):\/\//', $supermap_marker ) ) {
        $marker_url = $supermap_marker;
      } else {
        $marker_url = $markers_url . "/" . $supermap_marker;
        $marker_dir = $markers_dir . "/" . $supermap_marker;
        if(!file_exists($marker_dir)) {
          $marker_url = '';
        }
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
   * @return mixed|string
   */
  static public function shortcode_supermap($atts = array()) {
    global $wp_properties, $wp_scripts;

    $defaults = array(
      'per_page' => 10,
      'css_class' => '',
      'starting_row' => 0,
      'pagination' => 'on',
      'sidebar_width' => '',
      'hide_sidebar' => 'false',
      'map_height' => '',
      'map_width' => '',
      'options_label' => __('Options',ud_get_wpp_supermap()->domain),
      'silent_failure' => 'true',
      'sort_order' => 'DESC',
      'strict_search' => '',
      'sort_by' => 'post_date'
    );

    $atts = array_merge($defaults, (array)$atts);

    wp_enqueue_script( 'google-maps' );

    //** Quit function if Google Maps is not loaded */
    if(!WPP_F::is_asset_loaded('google-maps')) {
      return ($atts['silent_failure'] == 'true' ? false : sprintf(__('Element cannot be rendered, missing %1s script.', ud_get_wpp_supermap()->domain), 'google-maps'));
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
    $query_keys['strict_search'] = '';

    //* START Set query */
    $query = shortcode_atts($query_keys, $atts);

    if (isset($_REQUEST['wpp_search'])){
      $query = shortcode_atts($query, $_REQUEST['wpp_search']);
    }

    $query = apply_filters('strict_search', $query, $defaults);

    /* HACK: Remove attribute with value 'all' from query to avoid search result issues:
     * Because 'all' means any attribute's value,
     * But if property has no the attribute, which has value 'all' - query doesn't return this property
     */
    foreach ($query as $k => $v) {
      if($v == 'all' || empty($v)) {
        unset($query[$k]);
      }
    }

    // End From property-overview.php

    //* Exclude properties which has no latitude,longitude keys */
    $query['latitude'] = 'all';
    $query['longitude'] = 'all';
    //$query['address_is_formatted'] = 'true';
    $query['exclude_from_supermap'] = 'false,0';

    $query = apply_filters( 'wpp:supermap:query_defaults', $query, $atts );

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

      $supermap = "";

      /**
       * Call function which prepares data and renders template.
       */
      $template_function = apply_filters( 'wpp::supermap::template_function', array( __CLASS__, 'supermap_template' ), $query, $properties, $atts );
      if( is_callable($template_function) ) {
        $supermap = call_user_func_array( $template_function, array( $properties, $atts ) );
      }
      return $supermap;

    } else if ( isset( $_REQUEST[ 'wpp_search' ] ) ) {

      return '<span class="wpp-no-listings">'. sprintf( __( 'Sorry, no %s found, try expanding your search.', ud_get_wpp_supermap()->domain ), WPP_F::property_label( 'plural' ) ) . '</span>';

    }

  }

  /**
   * Prepares data, enquires javascript,
   * includes template and returns it
   *
   * Note, you can redeclare function by calling your own one using filter:
   * wpp::supermap::template_function
   */
  static public function supermap_template( $properties, $atts = array() ) {
    global $wp_properties;

    //* Determine if properties exist */
    if(empty($properties)) {
      return '';
    }

    //* Default settings */
    $defaults = array(
      'hide_sidebar' => 'false',
      'css_class' => '',
      'show_areas' => false,
      'sidebar_width' => '',
      'map_height' => '',
      'map_width' => '',
      'zoom' => '',
      'options_label' => __('Options',ud_get_wpp_supermap()->domain),
      'center_on' => '',
      'scrollwheel' => '',
      'strict_search' => '',
      'property_type' => (array) $wp_properties['searchable_property_types'],
      'rand' => rand(1000,5000)
    );

    if(!empty($sidebar_width)) {
      $sidebar_width = trim(str_replace(array('%', 'px'), '', $sidebar_width));
    }

    //* Supermap configuration */
    if ( !empty( $wp_properties['configuration']['feature_settings']['supermap'] ) ) {
      $supermap_configuration = $wp_properties['configuration']['feature_settings']['supermap'];
    } else {
      $supermap_configuration = array();
    }
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

    $inline_styles['map'] = 'style="' . implode( ' ', ( !empty( $inline_styles['map'] ) ? (array) $inline_styles['map'] : array() ) ). '"';
    $inline_styles['sidebar'] = 'style="' . implode( ' ', ( !empty( $inline_styles['sidebar'] ) ? (array) $inline_styles['sidebar'] : array() ) ) . '"';

    //* START Render Javascript functionality for Areas */
    $areas = !empty( $supermap_configuration['areas'] ) ? $supermap_configuration['areas'] : array();
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
          $coords = trim($coords, "()" );
          $coords = explode(',', $coords);
          $this_area_coords[] = "{lat: {$coords[0]}, lng: {$coords[1]}}";
        }

        if(empty($this_area_coords)) {
          continue;
        }

        /* @todo: must be moved to static/scripts/supermap.js ! */
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

    /** Enqueue script  */
    //wp_enqueue_script( 'wpp-supermap', ud_get_wpp_supermap()->path( 'static/scripts/supermap.js', 'url' ), array(), ud_get_wpp_supermap()->version );

    $supermap = "";

    /**** TEMP SOLUTION *****/
    /**
     * @todo move current php template to javascript file (static/scripts/supermap.js)
     */
    /** Try find Supermap Template */
    $jstemplate = ud_get_wpp_supermap()->path( 'static/views/supermap-js.php', 'dir' );
    if( file_exists( $jstemplate ) ) {
      ob_start();
      include $jstemplate;
      $supermap .= ob_get_clean();
    }

    /**** END TEMP SOLUTION *****/

    /** Try find Supermap Template */
    $template = WPP_F::get_template_part(
      apply_filters( "wpp::supermap::template_name", array( "supermap" ) ),
      apply_filters( "wpp::supermap::template_path", array( ud_get_wpp_supermap()->path( 'static/views', 'dir' ) ) )
    );

    if( $template ) {
      ob_start();
      include $template;
      $supermap .= ob_get_clean();
    }

    return $supermap;
  }

  /**
   * Ajax. Returns javascript:
   * list of properties and markers
   *
   */
  static public function ajax_get_properties() {
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
      $query = apply_filters('strict_search', $query, $defaults);
      $query = shortcode_atts($query_keys, $query);
    }

    //* Exclude properties which has no latitude,longitude keys */
    $query['latitude'] = 'all';
    $query['longitude'] = 'all';

    //$query['address_is_formatted'] = '1';
    //* Add only properties which are not excluded from supermap (option on Property editing form) */
    //$query['exclude_from_supermap'] = 'false,0';
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

    $supermap_configuration['display_attributes'] = isset( $supermap_configuration['display_attributes'] ) && is_array( $supermap_configuration['display_attributes'] ) ? 
      $supermap_configuration['display_attributes'] : array();

    $display_attributes = array();
    foreach($supermap_configuration['display_attributes'] as $attribute) {
      if( isset( $wp_properties['property_stats'][$attribute] ) ) {
        $display_attributes[$attribute] = $wp_properties['property_stats'][$attribute];
      }
    }

    ob_start();

    if(!empty($properties)) : ?>
      var HTML = '';
      window.supermap_<?php echo $_POST['random']; ?>.total = '<?php echo $property_ids['total']; ?>';
      <?php

      $labels_to_keys = array_flip($wp_properties['property_stats']);

      foreach ($properties as $property_id => $value) {
        if ( !(isset( $value['latitude']) && $value['latitude']) || !(isset( $value['longitude']) && $value['longitude']) ){
          continue;
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

      HTML += '<?php echo str_replace("'","\'", trim( preg_replace('/\s\s+/', ' ', ud_get_wpp_supermap()->render_property_item( $value, array( 'rand' => $_POST['random'], 'supermap_configuration' => $supermap_configuration, ), true ) ) ) ); ?>';

      <?php } ?>

      var wpp_supermap_<?php echo $_POST['random']; ?> = document.getElementById('super_map_list_property_<?php echo $_POST['random']; ?>');

      if( wpp_supermap_<?php echo $_POST['random']; ?> !== null ) {
        wpp_supermap_<?php echo $_POST['random']; ?>.innerHTML += HTML;
      }

    <?php else : ?>

      window.supermap_<?php echo $_POST['random']; ?>.total = '0';

      var wpp_supermap_<?php echo $_POST['random']; ?> = document.getElementById("super_map_list_property_<?php echo $_POST['random']; ?>");
      var y = '<div style="text-align:center;" class="no_properties"><?php _e('No results found.', ud_get_wpp_supermap()->domain); ?></div>';

      if( wpp_supermap_<?php echo $_POST['random']; ?> !== null ) {
        wpp_supermap_<?php echo $_POST['random']; ?>.innerHTML += y;
      }

    <?php endif; ?>
    <?php

    $result = ob_get_contents();
    ob_end_clean();

    echo WPP_F::minify_js($result);

    exit();
  }

  /**
   * Draws Option Form on sidebar of Supermap
   *
   * @param $search_attributes
   * @param $searchable_property_types
   * @param $rand
   */
  static public function draw_supermap_options_form($search_attributes = false, $atts = array(), $rand = 0) {
    global $wp_properties;

    $searchable_property_types = isset($atts['property_type'])?$atts['property_type']:false;
    $is_strict = (isset($atts['strict_search']))? $atts['strict_search']:'';

    if( !empty( $_REQUEST[ 'wpp_search' ] ) ) {
    
      /** 
       * Render hidden form in case we have wpp_search request. 
       * because supermap page can be used as default search results page
       */
      $fields = array();
      foreach( $wp_properties[ 'property_stats' ] as $k => $v ) {
        if( key_exists( $k, $_REQUEST[ 'wpp_search' ] ) ) {
          $data = $_REQUEST[ 'wpp_search' ][ $k ];
          if( is_array( $data ) ) {
            foreach( $data as $name => $value ) {
              $fields[] = array(
                'name' => "wpp_search[{$k}][{$name}]",
                'value' => $value,
              );
            }
          } else {
            $fields[] = array(
              'name' => "wpp_search[{$k}]",
              'value' => $data,
            );
          }
        }
      }
      
      if( !empty( $fields ) ) {
        echo "<form id=\"formFilter_{$rand}\" name=\"formFilter\" action=\"\">";
        do_action( "draw_property_search_form", array() );
        foreach( $fields as $field ) {
          echo "<input type=\"hidden\" name=\"{$field['name']}\" value=\"{$field['value']}\" />";
        }
        echo "</form>";
      }
      
    } else {
    
      if( !$search_attributes) {
        return;
      }

      $search_values = WPP_F::get_search_values(array_keys((array)$search_attributes), $searchable_property_types );
      ?>
      <form id="formFilter_<?php echo $rand; ?>" name="formFilter" action="">
        <input type="hidden" name="wpp_search[strict_search]" value="<?php echo $is_strict; ?>">
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
          <div class="search_loader" style="display:none"><?php _e('Loading',ud_get_wpp_supermap()->domain) ?></div>
        </div> <?php //end of class_wpp_supermap_elements ?>
      </form>
      <?php
    }
  }

  /**
   * Check specific supermap keys to properties
   * and add/remove/modify them to avoid issues on supermap
   * &
   * Check supermap files (markers): remove unused.
   *
   * @author Maxim Peshkov
   */
  static public function settings_save($wpp_settings) {
    global $wpdb;

    //* START Markers (files) checking */
    $upload_dir = wp_upload_dir();
    $markers_dir = $upload_dir['basedir'] . '/supermap_files/markers';
    $markers = isset( $wpp_settings['configuration']['feature_settings']['supermap']['markers'] ) ? 
      (array)$wpp_settings['configuration']['feature_settings']['supermap']['markers'] : array();
      
    //* Get all markers files */
    $files = array();
    foreach ( $markers as $marker ) {
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
  static public function importer_meta_filter( $value, $attribute, $type, $post_id ) {
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

  static public function apply_strict_search($query, $defaults = array()){
    global $wp_properties;

    // From property-overview.php
    //** We add # to value which says that we don't want to use LIKE in SQL query for searching this value. */
    $required_strict_search = apply_filters( 'wpp::required_strict_search', array( 'wpp_agents' ) );
    $ignored_strict_search_field_types = apply_filters( 'wpp:ignored_strict_search_field_types', array( 'range_dropdown', 'range_input', 'range_date' ) );

    foreach( $query as $key => $val ) {
      if( !array_key_exists( $key, $defaults ) && $key != 'property_type' && $val ) {
        //** Be sure that the attribute exists of parameter is required for strict search */
        if(
          ( in_array( $query[ 'strict_search' ], array( 'true', 'on' ) ) && isset( $wp_properties[ 'property_stats' ][ $key ] ) )
          || in_array( $key, $required_strict_search )
        ) {
          /**
           * Ignore specific search attribute fields for strict search.
           * For example, range values must not be included to strict search.
           * Also, be sure to ignore list of values
           */
          if(
            ( isset( $wp_properties[ 'searchable_attr_fields' ][ $key ] ) && in_array( $wp_properties[ 'searchable_attr_fields' ][ $key ], (array)$ignored_strict_search_field_types ) )
            || substr_count( $val, ',' )
            || substr_count( $val, '&ndash;' )
            || substr_count( $val, '--' )
          ) {
            continue;
          } //** Determine if value contains range of numeric values, and ignore it, if so. */
          elseif( substr_count( $val, '-' ) ) {
            $_val = explode( '-', $val );
            if( count( $_val ) == 2 && is_numeric( $_val[ 0 ] ) && is_numeric( $_val[ 1 ] ) ) {
              continue;
            }
          }
          $query[ $key ] = '#' . trim( $val, '#' ) . '#';
        }
      }
    }
    unset($query['strict_search']);
    return $query;
  }

}

endif; // Class Exists
