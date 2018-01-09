<?php
/**
 * Property Overview Pagination
 * Main template
 *
 * To modify template, copy the file to root of your theme.
 */
$sort_html = '';
$sort_html .= '<div class="properties_pagination ' . $settings['class'] . ' wpp_slider_pagination type-' . $settings['type'] . '">';

if ($settings['type'] !== 'loadmore' || ($settings['type'] == 'loadmore' && $settings['class'] !== 'wpp_bottom_pagination')) {
  $sort_html .= '<div class="wpp_pagination_slider_status">';
  $sort_html .= '<span class="wpp_property_results_options">';
  if ($hide_count != 'true') {
    $wpp_property_results = '<span class="wpp_property_results">';
    $wpp_property_results .= ($properties['total'] > 0 ? \WPP_F::format_numeric($properties['total']) : __('None', ud_get_wp_property()->domain));
    $wpp_property_results .= __(' found. ', ud_get_wp_property()->domain);
    $sort_html .= apply_filters('wpp::wpp_draw_pagination::wpp_property_results', $wpp_property_results, array('properties' => $properties, 'settings' => $settings));
  }
  if (!empty($use_pagination)) {
    $sort_html .= __('Viewing page ', ud_get_wp_property()->domain);
    $sort_html .= '<span class="wpp_current_page_count">1</span>' . __(" of ", ud_get_wp_property()->domain);
    $sort_html .= '<span class="wpp_total_page_count">' . $pages . '</span>.';
  }
  if ($hide_count != 'true') {
    $sort_html .= '</span>';
  }

  /* View template */
  $sort_html .= '<div class="wpp_template_view">';
  $sort_html .= '<span class="wpp_template_view_button wpp_template_grid" wpp_template="grid"></span>';
  $sort_html .= '<span class="wpp_template_view_button wpp_template_row" wpp_template="row"></span>';
  $sort_html .= '</div>';
  $sort_html .= '<div class="clearfix"></div>';

  if ($sortable_attrs) {

    $sort_html .= '<div class="wpp_sorter_options"><span class="wpp_sort_by_text"> ' . $settings['sort_by_text'] . ' </span>';
    if ($settings['sorter_type'] == 'buttons') {
      foreach ($sortable_attrs as $slug => $label) {
        $sort_html .= ' <span class="wpp_sortable_link ';
        if ($sort_by == $slug) {
          $sort_html .= 'wpp_sorted_element';
        }
//            $sort_html .= ($sort_by == $slug ? 'wpp_sorted_element' : '');
        $sort_html .= 'label label-info" sort_order = "' . $sort_order . '" sort_slug = "' . $slug . '" > ' . $label . '</span > ';
      }
    } elseif ($settings['sorter_type'] == 'dropdown') {
      $sort_html .= ' <select class="wpp_sortable_dropdown sort_by label-info" name = "sort_by" > ';
      foreach ($sortable_attrs as $slug => $label) {
        $sort_html .= '<option ';
        if ($sort_by == $slug) {
          $sort_html .= 'class="wpp_sorted_element" selected = "true"';
        }
        $sort_html .= ' sort_slug = "' . $slug . '" value = "' . $slug . '" > ' . $label . '</option > ';
      }
      $sort_html .= '</select > ';
    } else {
      $sort_html .= do_action('wpp_custom_sorter', array('settings' => $settings, 'wpp_query' => $wpp_query, 'sorter_type' => $settings['sorter_type']));
    }
    $sort_html .= ' </div>';
  }

  $sort_html .= '<div class="clear"></div>';
  $sort_html .= '</div>';
}

echo $sort_html;

/* Render Pagination Template based on pagination type */
if ($settings['type'] == 'loadmore' || !empty($use_pagination)) {
  if ($settings['type'] !== 'loadmore' || ($settings['type'] == 'loadmore' && $settings['class'] == 'wpp_bottom_pagination')) {
    $template = self::get_pagination_template_based_on_type($settings['type']);
    if (file_exists($template)) {
      include($template);
    } else {
      _e('No Pagination Template Found', ud_get_wp_property('domain'));
    }
  }
}
?>
  </div>
<?php if ($settings['class'] !== 'wpp_bottom_pagination') { ?>
  <div class="ajax_loader"></div>
<?php } ?>