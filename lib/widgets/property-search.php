<?php

/**
 * Class SearchPropertiesWidget
 */
class SearchPropertiesWidget extends WP_Widget
{
  var $id = false;

  /**
   * Construct
   */
  function __construct()
  {

    $property_label = strtolower(WPP_F::property_label());

    parent::__construct(
      false,
      sprintf(__('%1s Search', ud_get_wp_property()->domain), WPP_F::property_label()),
      array(
        'classname' => 'wpp_property_attributes',
        'description' => sprintf(__('Display a highly customizable  %1s search form.', ud_get_wp_property()->domain), $property_label)
      ),
      array(
        'width' => 300
      )
    );

  }

  /**
   * Widget body
   * @param array $args
   * @param array $instance
   */
  function widget($args, $instance)
  {

    global $wp_properties;
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    $widget_id = '';
    extract($args);
    $title = apply_filters('widget_title', $instance['title']);

    $instance = apply_filters('SearchPropertiesWidget', $instance);
    $search_attributes = isset($instance['searchable_attributes']) ? $instance['searchable_attributes'] : false;
    $sort_by = isset($instance['sort_by']) ? $instance['sort_by'] : false;
    $sort_order = isset($instance['sort_order']) ? $instance['sort_order'] : false;
    $searchable_property_types = isset($instance['searchable_property_types']) ? $instance['searchable_property_types'] : array();
    $grouped_searchable_attributes = isset($instance['grouped_searchable_attributes']) ? $instance['grouped_searchable_attributes'] : array();

    if (!is_array($search_attributes)) {
      return;
    }

    if (!function_exists('draw_property_search_form')) {
      return;
    }

    //** The current widget can be used on the page twice. So ID of the current DOM element (widget) has to be unique */
    /*
          Removed since this will cause problems with jQuery Tabs in Denali.
          $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);
        */

    echo $before_widget;

    echo WPP_LEGACY_WIDGETS ? '<div class="wpp_search_properties_widget">' : '<div class="wpp_search_properties_widget_v2">';

    if ($title) {
      echo $before_title . $title . $after_title;
    } else {
      echo '<span class="wpp_widget_no_title"></span>';
    }

    //** Load different attribute list depending on group selection */
    if (isset($instance['group_attributes']) && $instance['group_attributes'] == 'true') {
      $search_args['group_attributes'] = true;
      $search_args['search_attributes'] = $grouped_searchable_attributes;
    } else {
      $search_args['search_attributes'] = $search_attributes;
    }

    //* Clean searchable attributes: remove unavailable ones */
    $all_searchable_attributes = array_unique($wp_properties['searchable_attributes']);
    foreach ($search_args['search_attributes'] as $k => $v) {
      if (!in_array($v, $all_searchable_attributes)) {
        //* Don't remove hardcoded attributes (property_type,city) */
        if ($v != 'property_type' && $v != 'city') {
          unset($search_args['search_attributes'][$k]);
        }
      }
    }

    $search_args['searchable_property_types'] = $searchable_property_types;

    if (isset($instance['use_pagi']) && $instance['use_pagi'] == 'on') {

      if (empty($instance['per_page'])) {
        $instance['per_page'] = 10;
      }

      $search_args['per_page'] = $instance['per_page'];
      $search_args['use_pagination'] = 'on';
    } else {
      $search_args['use_pagination'] = 'off';
      $search_args['per_page'] = isset($instance['per_page']) ? $instance['per_page'] : false;
    }

    $search_args['instance_id'] = $widget_id;
    $search_args['sort_by'] = $sort_by;
    $search_args['sort_order'] = $sort_order;
    $search_args['strict_search'] = (!empty($instance['strict_search']) && $instance['strict_search'] == 'on' ? 'true' : 'false');

    draw_property_search_form($search_args);

    echo "<div class='cboth'></div></div>";

    echo $after_widget;
  }

  /** @see WP_Widget::update */
  function update($new_instance, $old_instance)
  {
    //Recache searchable values for search widget form
    $searchable_attributes = $new_instance['searchable_attributes'];
    $grouped_searchable_attributes = $new_instance['grouped_searchable_attributes'];
    $searchable_property_types = $new_instance['searchable_property_types'];
    $group_attributes = !empty($new_instance['group_attributes']) ? $new_instance['group_attributes'] : 'false';


    if ($group_attributes == 'true') {

      WPP_F::get_search_values($grouped_searchable_attributes, $searchable_property_types, false, $this->id);
    } else {
      WPP_F::get_search_values($searchable_attributes, $searchable_property_types, false, $this->id);
    }

    return $new_instance;
  }

  /**
   *
   * Renders back-end property search widget tools.
   *
   * @complexity 8
   * @author potanin@UD
   *
   */
  function form($instance)
  {
    global $wp_properties;

    //** Get widget-specific data */
    $title = isset($instance['title']) ? $instance['title'] : false;
    $searchable_attributes = isset($instance['searchable_attributes']) ? $instance['searchable_attributes'] : false;
    $grouped_searchable_attributes = isset($instance['grouped_searchable_attributes']) ? $instance['grouped_searchable_attributes'] : false;
    $use_pagi = isset($instance['use_pagi']) ? $instance['use_pagi'] : false;
    $per_page = isset($instance['per_page']) ? $instance['per_page'] : false;
    $strict_search = isset($instance['strict_search']) ? $instance['strict_search'] : false;
    $sort_by = isset($instance['sort_by']) ? $instance['sort_by'] : false;
    $sort_order = isset($instance['sort_order']) ? $instance['sort_order'] : false;
    $group_attributes = isset($instance['group_attributes']) ? $instance['group_attributes'] : false;
    $searchable_property_types = isset($instance['searchable_property_types']) ? $instance['searchable_property_types'] : false;
    $property_stats = isset($wp_properties['property_stats']) ? $wp_properties['property_stats'] : array();

    //** Get WPP data */
    $all_searchable_property_types = array_unique($wp_properties['searchable_property_types']);
    $all_searchable_attributes = array_unique($wp_properties['searchable_attributes']);
    $groups = isset($wp_properties['property_groups']) ? $wp_properties['property_groups'] : false;
    $main_stats_group = isset($wp_properties['configuration']['main_stats_group']) ? $wp_properties['configuration']['main_stats_group'] : false;

    $error = array();

    if (!is_array($all_searchable_property_types)) {
      $error['no_searchable_types'] = true;
    }

    if (!is_array($all_searchable_property_types)) {
      $error['no_searchable_attributes'] = true;
    }

    /** Set label for list below only */
    if (!isset($property_stats['property_type'])) {
      $property_stats['property_type'] = sprintf(__('%s Type', ud_get_wp_property()->domain), WPP_F::property_label());
    }

    if (is_array($all_searchable_property_types) && count($all_searchable_property_types) > 1) {

      //** Add property type to the beginning of the attribute list, even though it's not a typical attribute */
      array_unshift($all_searchable_attributes, 'property_type');
    }

    //** Find the difference between selected attributes and all attributes, i.e. unselected attributes */
    if (is_array($searchable_attributes) && is_array($all_searchable_attributes)) {
      $unselected_attributes = array_diff($all_searchable_attributes, $searchable_attributes);

      //* Clean searchable attributes: remove unavailable ones */
      foreach ($searchable_attributes as $k => $v) {
        if (!in_array($v, $all_searchable_attributes)) {
          //* Don't remove hardcoded attributes (property_type,city) */
          if ($v != 'property_type' && $v != 'city') {
            unset($searchable_attributes[$k]);
          }
        }
      }

      // Build new array beginning with selected attributes, in order, follow by all other attributes
      $ungrouped_searchable_attributes = array_merge($searchable_attributes, $unselected_attributes);

    } else {
      $ungrouped_searchable_attributes = $all_searchable_attributes;
    }

    $ungrouped_searchable_attributes = array_unique($ungrouped_searchable_attributes);

    //* Perpare $all_searchable_attributes for using by sort function */
    $temp_attrs = array();

    foreach ($all_searchable_attributes as $slug) {
      $temp_attrs[$slug] = $slug;
    }

    //* Sort stats by groups */
    $stats_by_groups = sort_stats_by_groups($temp_attrs);

    //** If the search widget cannot be created without some data, we bail */
    if (!empty($error)) {
      echo '<p>' . printf(__('No searchable %s types were found.', ud_get_wp_property()->domain), WPP_F::property_label()) . '</p>';
      return;
    }

    ?>

    <ul data-widget_number="<?php echo $this->number; ?>" data-widget="search_properties_widget"
        class="wpp_widget wpp_property_search_wrapper">

      <li class="<?php echo $this->get_field_id('title'); ?>">
        <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', ud_get_wp_property()->domain); ?>
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                 name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>"/>
        </label>
      </li>

      <li class="wpp_property_types">
        <p><?php printf(__('%s types to search:', ud_get_wp_property()->domain), WPP_F::property_label()); ?></p>
        <ul>
          <?php foreach ($all_searchable_property_types as $property_type) { ?>
            <li>
              <label
                for="<?php echo $this->get_field_id('searchable_property_types'); ?>_<?php echo $property_type; ?>">
                <input class="wpp_property_types"
                       id="<?php echo $this->get_field_id('searchable_property_types'); ?>_<?php echo $property_type; ?>"
                       name="<?php echo $this->get_field_name('searchable_property_types'); ?>[]"
                       type="checkbox" <?php if (empty($searchable_property_types)) {
                  echo 'checked="checked"';
                } ?>
                       value="<?php echo $property_type; ?>" <?php if (is_array($searchable_property_types) && in_array($property_type, $searchable_property_types)) {
                  echo " checked ";
                } ?> />
                <?php echo(!empty($wp_properties['property_types'][$property_type]) ? $wp_properties['property_types'][$property_type] : ucwords($property_type)); ?>
              </label>
            </li>
          <?php } ?>
        </ul>
      </li>

      <li class="wpp_attribute_selection">
        <p><?php _e('Select the attributes you want to search.', ud_get_wp_property()->domain); ?></p>

        <div class="wpp_search_widget_tab wpp_subtle_tabs ">

          <ul class="wpp_section_tabs  tabs">
            <li><a
                href="#all_atributes_<?php echo $this->id; ?>"><?php _e('All Attributes', ud_get_wp_property()->domain); ?></a>
            </li>

            <?php if ($stats_by_groups) { ?>
              <li><a
                  href="#grouped_attributes_<?php echo $this->id; ?>"><?php _e('Grouped Attributes', ud_get_wp_property()->domain); ?></a>
              </li>
            <?php } ?>
          </ul>

          <div id="all_atributes_<?php echo $this->id; ?>" class="wp-tab-panel wpp_all_attributes">
            <ul class="wpp_sortable_attributes">
              <?php foreach ($ungrouped_searchable_attributes as $attribute) { ?>

                <li class="wpp_attribute_wrapper <?php echo $attribute; ?>">
                  <input id="<?php echo $this->get_field_id('searchable_attributes'); ?>_<?php echo $attribute; ?>"
                         name="<?php echo $this->get_field_name('searchable_attributes'); ?>[]"
                         type="checkbox" <?php if (empty($searchable_attributes)) {
                    echo 'checked="checked"';
                  } ?>
                         value="<?php echo $attribute; ?>" <?php echo((is_array($searchable_attributes) && in_array($attribute, $searchable_attributes)) ? " checked " : ""); ?> />
                  <label
                    for="<?php echo $this->get_field_id('searchable_attributes'); ?>_<?php echo $attribute; ?>"><?php echo apply_filters('wpp::search_attribute::label', (empty($property_stats[$attribute]) ? WPP_F::de_slug($attribute) : $property_stats[$attribute]), $attribute); ?></label>
                </li>
              <?php } ?>
            </ul>
          </div><?php /* end all (ungrouped) attribute selection */ ?>

          <?php if ($stats_by_groups) { ?>
            <div id="grouped_attributes_<?php echo $this->id; ?>" class="wpp_grouped_attributes_container wp-tab-panel">

              <?php foreach ($stats_by_groups as $gslug => $gstats) { ?>
                <?php if ($main_stats_group != $gslug || !key_exists($gslug, $groups)) { ?>
                  <?php $group_name = (key_exists($gslug, $groups) ? $groups[$gslug]['name'] : "<span style=\"color:#8C8989\">" . __('Ungrouped', ud_get_wp_property()->domain) . "</span>"); ?>
                  <h2 class="wpp_stats_group"><?php echo $group_name; ?></h2>
                <?php } ?>
                <ul>
                  <?php foreach ($gstats as $attribute) { ?>
                    <li>
                      <input
                        id="<?php echo $this->get_field_id('grouped_searchable_attributes'); ?>_<?php echo $attribute; ?>"
                        name="<?php echo $this->get_field_name('grouped_searchable_attributes'); ?>[]"
                        type="checkbox" <?php if (empty($grouped_searchable_attributes)) {
                        echo 'checked="checked"';
                      } ?>
                        value="<?php echo $attribute; ?>" <?php echo((is_array($grouped_searchable_attributes) && in_array($attribute, $grouped_searchable_attributes)) ? " checked " : ""); ?> />
                      <label
                        for="<?php echo $this->get_field_id('grouped_searchable_attributes'); ?>_<?php echo $attribute; ?>"><?php echo apply_filters('wpp::search_attribute::label', (empty($property_stats[$attribute]) ? WPP_F::de_slug($attribute) : $property_stats[$attribute]), $attribute); ?></label>
                    </li>
                  <?php } ?>
                </ul>
              <?php } /* End cycle through $stats_by_groups */ ?>
            </div>
          <?php } ?>

        </div>

      </li>

      <li>

        <?php if ($stats_by_groups) { ?>
        <div>
          <input id="<?php echo $this->get_field_id('group_attributes'); ?>" class="wpp_toggle_attribute_grouping"
                 type="checkbox" value="true"
                 name="<?php echo $this->get_field_name('group_attributes'); ?>" <?php checked($group_attributes, 'true'); ?> />
          <label
            for="<?php echo $this->get_field_id('group_attributes'); ?>"><?php _e('Group attributes together.'); ?></label>
        </div>
      </li>
      <?php } ?>

      <li>

        <div class="wpp_something_advanced_wrapper" style="margin-top: 10px;">
          <ul>

            <?php if (!empty($wp_properties['sortable_attributes']) && is_array($wp_properties['sortable_attributes'])) : ?>
              <li class="wpp_development_advanced_option">
                <div><label
                    for="<?php echo $this->get_field_id('sort_by'); ?>"><?php _e('Default Sort Order', ud_get_wp_property()->domain); ?></label>
                </div>
                <select id="<?php echo $this->get_field_id('sort_by'); ?>"
                        name="<?php echo $this->get_field_name('sort_by'); ?>">
                  <option></option>
                  <?php foreach ($wp_properties['sortable_attributes'] as $attribute) { ?>
                    <option
                      value="<?php echo esc_attr($attribute); ?>" <?php selected($sort_by, $attribute); ?> ><?php echo $property_stats[$attribute]; ?></option>
                  <?php } ?>
                </select>

                <select id="<?php echo $this->get_field_id('sort_order'); ?>"
                        name="<?php echo $this->get_field_name('sort_order'); ?>">
                  <option></option>
                  <option
                    value="DESC" <?php selected($sort_order, 'DESC'); ?> ><?php _e('Descending', ud_get_wp_property()->domain); ?></option>
                  <option
                    value="ASC" <?php selected($sort_order, 'ASC'); ?> ><?php _e('Ascending', ud_get_wp_property()->domain); ?></option>
                </select>

              </li>
            <?php endif; ?>

            <li class="wpp_development_advanced_option">
              <label for="<?php echo $this->get_field_id('use_pagi'); ?>">
                <input id="<?php echo $this->get_field_id('use_pagi'); ?>"
                       name="<?php echo $this->get_field_name('use_pagi'); ?>" type="checkbox"
                       value="on" <?php if ($use_pagi == 'on') echo " checked='checked';"; ?> />
                <?php _e('Use pagination', ud_get_wp_property()->domain); ?>
              </label>
            </li>

            <li class="wpp_development_advanced_option">
              <label for="<?php echo $this->get_field_id('strict_search'); ?>">
                <input id="<?php echo $this->get_field_id('strict_search'); ?>"
                       name="<?php echo $this->get_field_name('strict_search'); ?>" type="checkbox"
                       value="on" <?php if ($strict_search == 'on') echo " checked='checked';"; ?> />
                <?php _e('Strict Search', ud_get_wp_property()->domain); ?>
              </label>
            </li>

            <li class="wpp_development_advanced_option">
              <label
                for="<?php echo $this->get_field_id('per_page'); ?>"><?php _e('Items per page', ud_get_wp_property()->domain); ?>
                <input style="width:30px" id="<?php echo $this->get_field_id('per_page'); ?>"
                       name="<?php echo $this->get_field_name('per_page'); ?>" type="text"
                       value="<?php echo $per_page; ?>"/>
              </label>
            </li>

            <li>
              <span
                class="wpp_show_advanced"><?php _e('Toggle Advanced Search Options', ud_get_wp_property()->domain); ?></span>
            </li>
          </ul>
        </div>
      </li>
    </ul>
    <?php

  }

}

/**
 * Register widget
 */
add_action('widgets_init', function () {
  register_widget("SearchPropertiesWidget");
});