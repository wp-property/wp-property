<?php
/**
 * Supermap template
 */

//** For now if only one attribute exists, and it's the property_type, we do not render the form at all */
if(count($searchable_attributes) == 1 && in_array('property_type', array_keys((array)$searchable_attributes))) {
  $searchable_attributes = false;
}

$supermap_configuration['display_attributes'] = isset( $supermap_configuration['display_attributes'] ) && is_array($supermap_configuration['display_attributes']) ? $supermap_configuration['display_attributes'] : array();

$display_attributes = array();
foreach( $supermap_configuration['display_attributes'] as $attribute ) {
  if( isset( $wp_properties['property_stats'][$attribute] ) ) {
    $display_attributes[$attribute] = $wp_properties['property_stats'][$attribute];
  }
}

$show_filter = false;

foreach ((array) $searchable_attributes as $key => $value) {
  if($value == 'all'){
    $show_filter = true;
    break;
  }
}
?>
<div id="map_cont_<?php echo $rand; ?>" class="wpp_supermap_wrapper <?php echo $css_class; ?>" supermap_id="<?php echo $rand; ?>">
  <div id="super_map_<?php echo $rand; ?>" class="super_map <?php if($hide_sidebar == 'true'): ?>no_sidebar<?php endif; ?>" <?php echo $inline_styles['map']; ?>></div>
  <?php if($hide_sidebar != 'true'): ?>
    <div id="super_map_list_<?php echo $rand; ?>" class="super_map_list" <?php echo $inline_styles['sidebar']; ?>>
      <?php if (!empty( $searchable_attributes) && empty( $_REQUEST[ 'wpp_search' ] ) && $show_filter == true ) : ?>
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
      <?php elseif ( !empty( $_REQUEST[ 'wpp_search' ]  ) ) : ?>
        <?php //* Set hidden form with attributes to handle search results on supermap ( supermap page can be used as default search result page ) */ ?>
        <?php class_wpp_supermap::draw_supermap_options_form( false, $atts['property_type'], $rand); ?>
      <?php endif; ?>
      <div id="super_map_list_property_<?php echo $rand; ?>" class="super_map_list_property">
        <?php if( !empty( $properties ) ) : ?>
          <?php foreach( $properties as $property ) : ?>
            <?php ud_get_wpp_supermap()->render_property_item( $property, array(
              'rand' => $rand,
              'supermap_configuration' => $supermap_configuration,
            ) ); ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php if($atts['pagination'] == 'on') { ?>
        <div class="show_more btn" style="<?php echo count($properties) < $atts['total'] ? '' : 'display:none;'; ?>">
          <?php _e('Show More', ud_get_wpp_supermap()->domain); ?>
          <div class="search_loader" style="display:none"><?php _e('Loading...', ud_get_wpp_supermap()->domain); ?></div>
        </div>
      <?php }?>
    </div>

  <?php endif; /*hide_sidebar */?>
  <br class="cb clear" />
</div>