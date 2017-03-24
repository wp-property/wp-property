<?php
/**
 * Property Search Widget V2 Template
 *
 * Called by draw_property_search_form();
 *
 * @version 2.0.3
 * @author Denis Virobjov <den@udx.io>
 * @package WP-Property
 */
?>
<form action="<?php echo WPP_F::base_url($wp_properties['configuration']['base_slug']); ?>" method="post"
      class="wpp_shortcode_search_form">
  <?php do_action("draw_property_search_form", $args); ?>
  <?php if ($sort_order) { ?>
    <input type="hidden" name="wpp_search[sort_order]" value="<?php echo $sort_order; ?>"/>
  <?php } ?>
  <?php if (!empty($sort_by)) { ?>
    <input type="hidden" name="wpp_search[sort_by]" value="<?php echo $sort_by; ?>"/>
  <?php } ?>
  <?php if (!empty($use_pagination)) { ?>
    <input type="hidden" name="wpp_search[pagination]" value="<?php echo $use_pagination; ?>"/>
  <?php } ?>
  <?php if (!empty($per_page)) { ?>
    <input type="hidden" name="wpp_search[per_page]" value="<?php echo $per_page; ?>"/>
  <?php } ?>
  <?php if (!empty($strict_search)) { ?>
    <input type="hidden" name="wpp_search[strict_search]" value="<?php echo $strict_search; ?>"/>
  <?php } ?>
  <?php
  //** If no property_type passed in search_attributes, we get defaults */
  if (is_array($searchable_property_types) && !array_key_exists('property_type', array_fill_keys($search_attributes, 1))) {
    echo '<input type="hidden" name="wpp_search[property_type]" value="' . implode(',', $searchable_property_types) . '" />';
  }
  ?>
  <div class="wpp_search_elements_v2">
    <?php

    if (isset($group_attributes) && $group_attributes) {
      //** Get group data */
      $groups = $wp_properties['property_groups'];
      $_search_attributes = array();

      foreach ($search_attributes as $attr) {
        $_search_attributes[$attr] = $attr;
      }
      $search_groups = sort_stats_by_groups($_search_attributes);
      unset($_search_attributes);
    } else {
      //** Create an ad-hoc group */
      $search_groups['ungrouped'] = $search_attributes;
    }

    $main_stats_group = isset($wp_properties['configuration']['main_stats_group']) ? $wp_properties['configuration']['main_stats_group'] : false;
    $count = 0;

    foreach ($search_groups as $this_group => $search_attributes) {
      $count++;
      if ($this_group == 'ungrouped' || $this_group === 0 || $this_group == $main_stats_group) {
        $is_a_group = false;
        $this_group = 'not_a_group';
      } else {
        $is_a_group = true;
      }
      ?>
      <div class="wpp_search_group wpp_group_<?php echo $this_group; ?>">
        <?php if ($is_a_group) { ?>
          <span
            class="wpp_search_group_title wpp_group_<?php echo $this_group; ?>_title"><?php echo $groups[$this_group]['name']; ?></span>
        <?php } elseif ($group_attributes && $count == count($search_groups)) { ?>
          <span class="wpp_search_group_title" style="height:1px;line-height:1px;">&nbsp;</span>
        <?php } ?>
        <ul id="wpp_search_group" class="wpp_search_group wpp_group_<?php echo $this_group; ?>">
          <?php
          //** Begin Group Attributes */
          foreach ($search_attributes as $attrib) {
            //** Override search values if they are set in the developer tab */
            if (!empty($wp_properties['predefined_search_values'][$attrib])) {
              //*wpp::attribute::value will return predefined values based on attribute name
              // if WPML not active will return the first value @fadi*/
              $maybe_search_values = explode(',', apply_filters('wpp::attribute::value', $wp_properties['predefined_search_values'][$attrib], $attrib));

              $maybe_search_values = array_map('trim', $maybe_search_values);
              if (is_array($maybe_search_values)) {
                $using_predefined_values = true;
                $search_values[$attrib] = $maybe_search_values;
              } else {
                $using_predefined_values = true;
              }
            }
            //** Don't display search attributes that have no values */
            if (!apply_filters('wpp::show_search_field_with_no_values', isset($search_values[$attrib]), $attrib)) {
              continue;
            }
            $label = apply_filters('wpp::search_attribute::label', (empty($property_stats[$attrib]) ? WPP_F::de_slug($attrib) : $property_stats[$attrib]), $attrib);

            ?>
            <li
              class="wpp_search_form_element seach_attribute_<?php echo $attrib; ?>  wpp_search_attribute_type_<?php echo isset($wp_properties['searchable_attr_fields'][$attrib]) ? $wp_properties['searchable_attr_fields'][$attrib] : $attrib; ?> <?php echo((!empty($wp_properties['searchable_attr_fields'][$attrib]) && $wp_properties['searchable_attr_fields'][$attrib] == 'checkbox') ? 'wpp-checkbox-el' : ''); ?><?php echo((!empty($wp_properties['searchable_attr_fields'][$attrib]) && ($wp_properties['searchable_attr_fields'][$attrib] == 'multi_checkbox' && count($search_values[$attrib]) == 1) || (isset($wp_properties['searchable_attr_fields'][$attrib]) && $wp_properties['searchable_attr_fields'][$attrib] == 'checkbox')) ? ' single_checkbox' : '') ?>">
              <?php $random_element_id = 'wpp_search_element_' . rand(1000, 9999); ?>

              <label for="<?php echo $random_element_id; ?>"
                     class="wpp_search_label wpp_search_label_<?php echo $attrib; ?>"><?php echo $label; ?><span
                  class="wpp_search_post_label_colon">:</span></label>

              <div class="wpp_search_attribute_wrap">
                <?php
                $value = isset($_REQUEST['wpp_search'][$attrib]) ? $_REQUEST['wpp_search'][$attrib] : '';
                ob_start();
                wpp_render_search_input(array(
                  'attrib' => $attrib,
                  'random_element_id' => $random_element_id,
                  'search_values' => $search_values,
                  'value' => $value
                ));
                $this_field = ob_get_contents();
                ob_end_clean();
                echo apply_filters('wpp_search_form_field_' . $attrib, $this_field, $attrib, $label, $value, (isset($wp_properties['searchable_attr_fields'][$attrib]) ? $wp_properties['searchable_attr_fields'][$attrib] : false), $random_element_id);
                ?>
              </div>
              <div class="clear"></div>
            </li>
            <?php
          }
          //** End Group Attributes */
          ?>
        </ul>
        <div class="clear"></div>
      </div>
    <?php } ?>
    <div class="wpp_search_form_element submit">
      <input type="submit" class="wpp_search_button submit btn btn-large"
             value="<?php _e('Search', ud_get_wp_property()->domain) ?>"/>
    </div>
  </div>
</form>