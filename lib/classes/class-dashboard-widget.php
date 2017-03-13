<?php

namespace UsabilityDynamics\WPP {

  if (!class_exists('\UsabilityDynamics\WPP\Dashboard_Widget')) {

    class Dashboard_Widget {

      /**
       * Widget ID
       * @var type
       */
      var $widget_id;

      /**
       * Widget Title
       * @var type
       */
      var $widget_title;

      /**
       * Construct
       */
      function __construct($default_options = array())
      {

        //** Register widget settings */
        self::update_dashboard_widget_options(
            $this->widget_id,
            $default_options,
            true
        );

        //** Register the widget */
        wp_add_dashboard_widget(
            $this->widget_id,
            $this->widget_title,
            array($this, 'widget'),
            array($this, 'config')
        );
      }

      /**
       * Widget output. Needs to be redeclared.
       * @param type $args
       * @param type $instance
       */
      function widget($args, $instance) {
        echo 'function Dashboard_Widget::widget() must be over-ridden in a sub-class.';
        return;
      }

      /**
       * Config UI output. May be redeclared.
       */
      function config() {
        echo '<p class="no-options-widget">There are no options for this widget.</p>';
      }

      /**
       * Get specific option of specific widget
       * @param type $widget_id
       * @param type $option
       * @param type $default
       * @return boolean
       */
      public static function get_dashboard_widget_option($widget_id, $option, $default = NULL) {

        $opts = self::get_dashboard_widget_options($widget_id);

        //** If widget opts dont exist, return false */
        if (!$opts)
          return false;

        //** Otherwise fetch the option or use default */
        if (isset($opts[$option]) && !empty($opts[$option]))
          return $opts[$option];
        else
          return (isset($default)) ? $default : false;
      }

      /**
       * Update options of specific widget
       * @param $widget_id
       * @param array $args
       * @param bool $add_only
       * @return bool
       */
      public static function update_dashboard_widget_options($widget_id, $args = array(), $add_only = false)
      {

        //** Fetch ALL dashboard widget options from the db */
        $opts = get_option('dashboard_widget_options');

        //** Get just our widget's options, or set empty array */
        $w_opts = (isset($opts[$widget_id])) ? $opts[$widget_id] : array();

        if ($add_only) {
          //** Flesh out any missing options (existing ones overwrite new ones) */
          $opts[$widget_id] = array_merge($args, $w_opts);
        } else {
          //** Merge new options with existing ones, and add it back to the widgets array */
          $opts[$widget_id] = array_merge($w_opts, $args);
        }

        //** Save the entire widgets array back to the db */
        return update_option('dashboard_widget_options', $opts);
      }

      /**
       * Get all options of specific widget
       * @param string $widget_id
       * @return bool|mixed|void
       */
      public static function get_dashboard_widget_options($widget_id = '')
      {

        //** Fetch ALL dashboard widget options from the db */
        $opts = get_option('dashboard_widget_options');

        //** If no widget is specified, return everything */
        if (empty($widget_id))
          return $opts;

        //** If we request a widget and it exists, return it */
        if (isset($opts[$widget_id]))
          return $opts[$widget_id];

        //** Something went wrong */
        return false;
      }

    }
  }

}