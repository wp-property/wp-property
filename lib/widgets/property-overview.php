<?php

/**
 * Property Overview Widget
 */
namespace UsabilityDynamics\WPP\Widgets {

  /**
   * Class PropertyOverviewWidget
   *
   * @package UsabilityDynamics\WPP\Widgets
   */
  class PropertyOverviewWidget extends \UsabilityDynamics\WPP\Widget
  {

    /**
     * @var string
     */
    public $shortcode_id = 'property_overview';

    /**
     * Init
     */
    public function __construct()
    {
      parent::__construct(WPP_LEGACY_WIDGETS ? 'wpp_property_overview' : 'wpp_property_overview_v2', $name = sprintf(__('%1s Overview', ud_get_wp_property()->domain), \WPP_F::property_label('singular')), array('description' => __('Property Overview Widget', ud_get_wp_property()->domain)));
    }

    /**
     * Widget body
     *
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance)
    {
      $before_widget = '';
      $after_widget = '';
      $before_title = '';
      $after_title = '';
      extract($args);

      $title = isset($instance['_widget_title']) ? $instance['_widget_title'] : '';

      echo $before_widget;
      if (!empty($title)) {
        echo $before_title . $title . $after_title;
      }
      //die('[property_overview '.$this->shortcode_args( $instance ).']' );
      echo do_shortcode('[property_overview ' . $this->shortcode_args($instance) . ']');

      echo $after_widget;
    }

    /**
     * Renders form based on Shortcode's params
     *
     * @param array $instance
     * @return bool|void
     */
    public function form($instance)
    {
      ?>
      <p>
        <label class="widefat"
               for="<?php echo $this->get_field_id('_widget_title'); ?>"><?php _e('Title', ud_get_wp_property('domain')); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('_widget_title'); ?>"
               name="<?php echo $this->get_field_name('_widget_title'); ?>" type="text"
               value="<?php echo !empty($instance['_widget_title']) ? $instance['_widget_title'] : ''; ?>"/>
        <span class="description"><?php _e('Widget\'s Title', ud_get_wp_property('domain')); ?></span>
      </p>
      <?php
      parent::form($instance);
    }

    /**
     * Update handler
     *
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    public function update($new_instance, $old_instance)
    {
      return $new_instance;
    }
  }

  /**
   * Register this widget
   */
  add_action('widgets_init', function () {
    register_widget('UsabilityDynamics\WPP\Widgets\PropertyOverviewWidget');
  });
}