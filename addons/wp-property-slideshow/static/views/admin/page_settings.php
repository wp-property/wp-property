<?php
global $wp_properties, $wpdb, $class_wpp_slideshow;

$wp_properties['configuration']['feature_settings']['slideshow'] = WPP_F::array_merge_recursive_distinct( $wp_properties['configuration']['feature_settings']['slideshow'], $class_wpp_slideshow );

$glob_slideshow = wp_parse_args( $wp_properties['configuration']['feature_settings']['slideshow']['glob'], array(
  'display_attributes' => array(),
  'link_to_property' => false,
  'show_property_title' => false,
  'image_size' => false,
  'show_title' => false,
  'show_excerpt' => false,
  'show_tagline' => false,
  'thumb_width' => false,
  'settings' => array(),
) );

$property_slideshow = wp_parse_args( $wp_properties['configuration']['feature_settings']['slideshow']['property'], array(
  'image_size' => false,
  'navigation' => false,
) );

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
  <th><?php _e('Global Slideshow',ud_get_wpp_slideshow()->domain); ?></th>
  <td>
    <ul>

    <li>
    <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][feature_settings][slideshow][glob][link_to_property]&label=".__('Clicking on an image will open up the property its attached to.',ud_get_wpp_slideshow()->domain), ( isset( $glob_slideshow['link_to_property'] ) ? $glob_slideshow['link_to_property'] : false ) ); ?>
    </li>

    <li>
    <?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][glob][show_property_title]&label=".__('Show property title and tagline in slideshow.',ud_get_wpp_slideshow()->domain), ( isset( $glob_slideshow['show_property_title'] ) ? $glob_slideshow['show_property_title'] : false ) ); ?>
    </li>

    <li>
      <label for="wpp_slideshow_global_size"><?php _e('Slideshow Size: ',ud_get_wpp_slideshow()->domain); ?></label>
      <select id="wpp_slideshow_global_size" name="wpp_settings[configuration][feature_settings][slideshow][glob][image_size]">
        <option value=""></option>
        <?php
        $wpp_image_sizes = $wp_properties['image_sizes'];
        foreach(get_intermediate_image_sizes() as $slug){
          $slug = trim($slug);
          $image_dimensions = WPP_F::image_sizes($slug, "return_all=true");
          /* Skip images w/o dimensions */
          if( !$image_dimensions ) continue;
          $selected = ( isset( $glob_slideshow['image_size'] ) && $glob_slideshow['image_size'] == $slug ) ? 'selected="selected"' : '';            
          echo '<option '.$selected.' value="'. $slug . '" >'. $slug .' - '. $image_dimensions['width'] .'px x '. $image_dimensions['height'] .'px</option>';
        }
        ?>
      </select>
      <br class="cb" />
      <span class="description">
   <?php printf(__('The global slideshow will look for all images that are over the specified size.  Add images to the global slideshow <a href="%1$s">here</a>. The built-in sizes, such as <b>medium</b> and <b>large</b> may not work because WordPress does not crop them during resizing, resulting in artbitrary heights. ',ud_get_wpp_slideshow()->domain), admin_url("edit.php?post_type=property&page=slideshow"));?>

       </span>
       </li>
     </ul>

  </td>
</tr>

<tr>
  <th><?php _e('Display in Global Slideshow Caption:',ud_get_wpp_slideshow()->domain); ?></th>
  <td>
    <ul>

      <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][glob][show_title]&label=" . __('Title.',ud_get_wpp_slideshow()->domain), $glob_slideshow['show_title']); ?></li>
      <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][glob][show_excerpt]&label=" . __('Excerpt.',ud_get_wpp_slideshow()->domain), $glob_slideshow['show_excerpt']); ?></li>
      <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][glob][show_tagline]&label=" . __('Tagline.',ud_get_wpp_slideshow()->domain), $glob_slideshow['show_tagline']); ?></li>
  </ul>
  </td>
</tr>

 <tr>
  <th><?php _e('Single Listing Slideshow',ud_get_wpp_slideshow()->domain); ?></th>
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
        <span class="description"><?php _e('Slideshow image size to be used for single property pages.',ud_get_wpp_slideshow()->domain); ?></span>
      </li>

      <li>
        <?php echo WPP_F::checkbox("name=wpp_settings[configuration][feature_settings][slideshow][property][navigation]&label=" . __('Show pagination buttons in slideshow.',ud_get_wpp_slideshow()->domain), $property_slideshow['navigation']); ?>
      </li>

    </ul>
  </td>
 </tr>

<tr>
  <th>
  <?php _e('Thumbnail Size',ud_get_wpp_slideshow()->domain); ?>
  </th>
  <td>
    <p>
      <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][feature_settings][slideshow][glob][thumb_width]&selected={$glob_slideshow['thumb_width']}"); ?>
      <span class="description"><?php _e( 'This width is used on "Slideshow" page on the Property editing page to display available images, this setting <b>does not</b> affect the actual slideshow on the front-end.', ud_get_wpp_slideshow()->domain ); ?></span>
    </p>
  </td>
 </tr>
 <tr>
  <th>
  <?php _e('Settings',ud_get_wpp_slideshow()->domain); ?>
  </th>
  <td>
    <ul>
      <li>
        <label for="wpp_sllideshow_effect"><?php _e('Effect:', ud_get_wpp_slideshow()->domain); ?></label>
        <select id="wpp_sllideshow_effect" name="wpp_settings[configuration][feature_settings][slideshow][glob][settings][effect]">
          <?php foreach($dropdown_options['effect']  as $effect): ?>
          <option <?php selected($effect,$wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['effect']); ?>value="<?php echo $effect; ?>"><?php echo $effect; ?></option>
          <?php endforeach; ?>
        </select>
        <span class="description"><?php _e('Effect that will be used to change from one image to the next.', ud_get_wpp_slideshow()->domain); ?></span>
      </li>

      <li>
        <label for="wpp_sllideshow_slices"><?php _e('Slices:', ud_get_wpp_slideshow()->domain); ?></label>
        <select id="wpp_sllideshow_slices" name="wpp_settings[configuration][feature_settings][slideshow][glob][settings][slices]">
          <?php foreach($dropdown_options['slices']  as $slices): ?>
          <option <?php selected($slices,$wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['slices']); ?>value="<?php echo $slices; ?>"><?php echo $slices; ?></option>
          <?php endforeach; ?>
        </select>
        <span class="description"><?php _e('If the transition includes slices, this number determines how many slices to cut the image up into during transitions.', ud_get_wpp_slideshow()->domain); ?></span>
      </li>

      <li>
        <label for="wpp_sllideshow_anim_speed"><?php _e('Animation Speed:', ud_get_wpp_slideshow()->domain); ?></label>
        <select id="wpp_sllideshow_slices" name="wpp_settings[configuration][feature_settings][slideshow][glob][settings][animSpeed]">
          <?php foreach($dropdown_options['animSpeed']  as $animSpeed): ?>
          <option <?php selected($animSpeed,$wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['animSpeed']); ?>value="<?php echo $animSpeed; ?>"><?php echo $animSpeed; ?></option>
          <?php endforeach; ?>
        </select>
        <span class="description"><?php _e('How quickly the transition should happen, in miliseconds.', ud_get_wpp_slideshow()->domain); ?></span>
      </li>

      <li>
        <label for="wpp_sllideshow_pause"><?php _e('Pause Time:', ud_get_wpp_slideshow()->domain); ?></label>
        <select id="wpp_sllideshow_pause" name="wpp_settings[configuration][feature_settings][slideshow][glob][settings][pauseTime]">
          <?php foreach($dropdown_options['pauseTime']  as $pauseTime): ?>
          <option <?php selected($pauseTime,$wp_properties['configuration']['feature_settings']['slideshow']['glob']['settings']['pauseTime']); ?>value="<?php echo $pauseTime; ?>"><?php echo $pauseTime; ?></option>
          <?php endforeach; ?>
        </select>
        <span class="description"><?php _e('The pause time between transitions.', ud_get_wpp_slideshow()->domain); ?></span>
      </li>

    </ul>

  </td>
 </tr>



</table>
