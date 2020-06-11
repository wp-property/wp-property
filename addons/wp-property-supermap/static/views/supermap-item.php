<?php
/**
 * Supermap Property Item
 *
 */
global $wp_properties;

$attributes = array();

$property_stats = WPP_F::get_stat_values_and_labels($property, array(
  'property_stats' => isset( $display_attributes ) ? $display_attributes : array()
));

$labels_to_keys = array_flip( (array) $wp_properties['property_stats']);

if(is_array($property_stats)){
foreach( $property_stats as $attribute_label => $attribute_value) {
  $boolean_field = false;
  $attribute_slug = false;
  $attribute_data = false;

  if( is_array( $labels_to_keys ) ) {
    $attribute_slug = $labels_to_keys[$attribute_label];
    $attribute_data = UsabilityDynamics\WPP\Attributes::get_attribute_data($attribute_slug);
  }

  if( !isset( $attribute_value ) || empty($attribute_value)) {
    continue;
  }

  if( !in_array($attribute_slug, $supermap_configuration['display_attributes'])) {
    continue;
  }

  if( (  $attribute_data['data_input_type'] == 'checkbox' && ($attribute_value == 'true' || $attribute_value == 1) ) ) {
    if($wp_properties['configuration']['google_maps']['show_true_as_image'] == 'true') {
      $attribute_value = '<div class="true-checkbox-image"></div>';
    } else {
      $attribute_value = __('Yes', ud_get_wpp_supermap()->domain);
    }
    $boolean_field = true;
  } elseif ($attribute_value == 'false') {
    continue;
  }

  if(is_array($attribute_value)){
    $attribute_value = implode(', ', $attribute_value);
  }

  $attributes[] =  '<li class="supermap_list_' . $attribute_slug . ' wpp_supermap_attribute_row">';
  $attributes[] =  '<span class="attribute">' . $attribute_label . (!$boolean_field ? ':' : '') . ' </span>';
  $attributes[] =  '<span class="value">' . $attribute_value . '</span>';
  $attributes[] =  '</li>';
}
} //End if

if(in_array('view_property', $supermap_configuration['display_attributes'])) {
  $attributes[] =  '<li class="supermap_list_view_property"><a href="' . get_permalink($property['ID']) . '" class="btn btn-info btn-small"><span>'  . sprintf( __('View %s', ud_get_wpp_supermap()->domain), WPP_F::property_label() ) . '</span></a></li>';
}

?>

<?php if (!empty($property['latitude']) && !empty($property['longitude']) && $property['ID']) { ?>

  <div id="property_in_list_<?php echo isset( $rand ) ? $rand : ''; ?>_<?php echo $property['ID']; ?>" class="property_in_list but_smaller">
    <ul class='property_in_list_items clearfix'>

      <?php if( !empty( $property['featured_image'] ) && ( !isset( $supermap_configuration['hide_sidebar_thumb'] ) || $supermap_configuration['hide_sidebar_thumb'] != 'true' ) ) { ?>
        <?php $default_image_width = !empty($default_image_width)?$default_image_width:'';?>
        <?php $image = wpp_get_image_link( $property['featured_image'], isset( $supermap_configuration['supermap_thumb'] ) ? $supermap_configuration['supermap_thumb'] : 'thumbnail', array('return'=>'array')); ?>
        <li class='supermap_list_thumb'><span  onclick="showInfobox_<?php echo $rand; ?>(<?php echo $property['ID']; ?>);"><img class="<?php echo ($image['link'] ? 'wpp_supermap_thumb' : 'wpp_supermap_thumb wpp_default_iamge'); ?>" src="<?php echo (empty($image['link']) ? WPP_URL . 'templates/images/no_image.png' : $image['link']); ?>" style="<?php echo ($image['width'] ? 'width: '.$image['width'].'px; ' : 'width: '.$default_image_width.'px;'); ?>" alt="<?php echo $property['post_title']; ?>" /></span></li>
      <?php } ?>
        <li class='supermap_list_title'><span onclick="showInfobox_<?php echo $rand; ?>(<?php echo $property['ID']; ?>);"><?php echo  stripslashes($property['post_title']); ?></span></li>
      <?php if(count($attributes) > 0) { echo implode('', $attributes); } ?>


    </ul>

  </div>
<?php }