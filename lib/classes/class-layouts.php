<?php
/**
 * Custom predefined layouts
 */

namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\Layouts')) {

    /**
     * Class Layouts
     * @package UsabilityDynamics\WPP
     */
    final class Layouts extends Scaffold
    {

      /**
       * Layouts constructor.
       */
      public function __construct()
      {
        parent::__construct();

        add_filter('template_include', array($this, 'page_template'), 99);
        add_action('wp_footer', array($this, 'panels_print_inline_css'));

        add_filter('wpp::layouts::configuration', function ($false) {
          global $wp_properties;

          $available_layouts = get_option('wpp_available_layouts', false);

          /**
           * For property taxonomies
           * property_term_single
           */
          if (is_tax() && in_array('property', get_taxonomy(get_queried_object()->taxonomy)->object_type) || is_property_overview_page()) {
            $layout_id = !empty(get_theme_mod('layouts_property_overview_choice'))
              ? get_theme_mod('layouts_property_overview_choice') : 'false';

            if ($layout_id != 'false') {

              try {
                $layout = json_decode(base64_decode($layout_id), true);
              } catch (\Exception $e) {
                echo $e->getMessage();
              }

              $template_file = !empty(get_theme_mod('layouts_property_overview_select'))
                ? get_theme_mod('layouts_property_overview_select') : 'index.php';

              return array(
                'templates' => array($template_file, 'page.php', 'single.php', 'index.php'),
                'layout_meta' => $layout
              );

            }
          }

          /**
           * For single property
           */
          if (is_singular('property')) {

            $layout_id = !empty(get_theme_mod('layouts_property_single_choice'))
              ? get_theme_mod('layouts_property_single_choice') : 'false';

            if ($layout_id != 'false') {

              try {
                $layout = json_decode(base64_decode($layout_id), true);
              } catch (\Exception $e) {
                echo $e->getMessage();
              }

              $template_file = !empty(get_theme_mod('layouts_property_single_select'))
                ? get_theme_mod('layouts_property_single_select') : 'index.php';

              return array(
                'templates' => array($template_file, 'page.php', 'single.php', 'index.php'),
                'layout_meta' => $layout
              );

            }
          }

          global $wp_query;

          if (!empty($wp_query->wpp_search_page)) {

            $layout_id = !empty(get_theme_mod('layouts_property_overview_choice'))
              ? get_theme_mod('layouts_property_overview_choice') : 'false';

            if ($layout_id != 'false') {

              try {
                $layout = json_decode(base64_decode($layout_id), true);
              } catch (\Exception $e) {
                echo $e->getMessage();
              }

              $template_file = !empty(get_theme_mod('layouts_property_overview_select'))
                ? get_theme_mod('layouts_property_overview_select') : 'index.php';

              return array(
                'templates' => array($template_file, 'page.php', 'single.php', 'index.php'),
                'layout_meta' => $layout
              );
            }
          }
          return $false;
        });
      }

      /**
       * CSS
       */
      public function panels_print_inline_css()
      {
        global $wpp_layouts_panels_inline_css;
        if (!empty($wpp_layouts_panels_inline_css)) {
          $the_css = '';
          foreach ($wpp_layouts_panels_inline_css as $post_id => $css) {
            if (empty($css)) continue;

            $the_css .= '/* wpp Layout ' . esc_attr($post_id) . ' */ ';
            $the_css .= $css;
            $wpp_layouts_panels_inline_css[$post_id] = '';
          }

          if (!empty($the_css)) {
            ?>
            <style type="text/css" media="all"
                   id="wpp-layouts-panels-grids-<?php echo esc_attr(current_filter()) ?>"><?php echo $the_css ?></style><?php
          }
        }
      }

      /**
       * Define a template
       * @param $template
       * @return string
       */
      public function page_template($template)
      {
        global $wp_query;

        $render = apply_filters('wpp::layouts::configuration', false);

        if ($render && !empty($wp_query->post)) {
          $wp_query->post->ID = !empty($render['layout_id']) ? $render['layout_id'] : $wp_query->post->ID;
        }

        if (count($wp_query->posts) > 1) {
          $wp_query->posts = array($wp_query->post);
          $wp_query->post_count = 1;
        }

        if (!$render) return $template;

        add_filter('the_content', array($this, 'the_content'), 1000);
        add_filter('the_excerpt', array($this, 'the_content'), 1000);

        // @note This should probaly be used instead of our content-override.
        // add_filter( 'siteorigin_panels_data', array( $this, 'siteorigin_panels_data' ), 1000, 2 );

        $template = locate_template($render['templates']);

        return $template;

      }

      public function siteorigin_panels_data($panels_data, $post_id)
      {

        // this returns the ID for the layout_id we're using.
        // $render = apply_filters( 'wpp::layouts::settings', false );

        // $_panels_data = get_post_meta($render['layout_id'], 'panels_data', true );

        //die( '<pre>' . print_r( $_panels_data, true ) . '</pre>' );

        return $panels_data;

      }

      /**
       * Replace the content
       *
       *
       * @note Gotta keep the 2nd argument.
       *
       * @param $data
       * @return string
       */
      public function the_content($data)
      {
        global $property, $post;

        $render = apply_filters('wpp::layouts::configuration', false);

        if (!$render) return $data;

        $_layout_config = apply_filters('wpp::layouts::layout_override', false, $render, $post);

        if (!empty($render['layout_id'])) {
          return $this->standard_render($render['layout_id'], $_layout_config);
        }

        if (!empty($render['layout_meta'])) {
          return $this->standard_render($post->ID, $render['layout_meta']);
        }

        return $data;

      }

      /**
       * Native render based on SO
       * @param bool $post_id
       * @param bool $panels_data
       * @return string
       */
      public function standard_render($post_id = false, $panels_data = false)
      {
        if (empty($post_id)) $post_id = get_the_ID();

        if (empty($panels_data)) {
          $panels_data = get_post_meta($post_id, 'panels_data', true);
        }

        $panels_data = apply_filters('wpp::layouts::panels_data', $panels_data, $post_id);
        if (empty($panels_data) || empty($panels_data['grids'])) return 'No panels data found.';

        if (!empty($panels_data['widgets'])) {
          $last_gi = 0;
          $last_ci = 0;
          $last_wi = 0;
          foreach ($panels_data['widgets'] as $wid => &$widget_info) {

            if ($widget_info['panels_info']['grid'] != $last_gi) {
              $last_gi = $widget_info['panels_info']['grid'];
              $last_ci = 0;
              $last_wi = 0;
            } elseif ($widget_info['panels_info']['cell'] != $last_ci) {
              $last_ci = $widget_info['panels_info']['cell'];
              $last_wi = 0;
            }
            $widget_info['panels_info']['cell_index'] = $last_wi++;
          }
        }

        $grids = array();
        if (!empty($panels_data['grids']) && !empty($panels_data['grids'])) {
          foreach ($panels_data['grids'] as $gi => $grid) {
            $gi = intval($gi);
            $grids[$gi] = array();
            for ($i = 0; $i < $grid['cells']; $i++) {
              $grids[$gi][$i] = array();
            }
          }
        }

        if (!empty($panels_data['widgets']) && is_array($panels_data['widgets'])) {
          foreach ($panels_data['widgets'] as $i => $widget) {
            if (empty($panels_data['widgets'][$i]['panels_info'])) {
              $panels_data['widgets'][$i]['panels_info'] = $panels_data['widgets'][$i]['info'];
              unset($panels_data['widgets'][$i]['info']);
            }

            $panels_data['widgets'][$i]['panels_info']['widget_index'] = $i;
          }
        }

        if (!empty($panels_data['widgets']) && is_array($panels_data['widgets'])) {
          foreach ($panels_data['widgets'] as $widget) {
            $grids[intval($widget['panels_info']['grid'])][intval($widget['panels_info']['cell'])][] = $widget;
          }
        }

        ob_start();

        $panel_layout_classes = apply_filters('wpp::layouts::panels_layout_classes', array(), $post_id, $panels_data);
        $panel_layout_attributes = apply_filters('wpp::layouts::panels_layout_attributes', array(
          'class' => implode(' ', $panel_layout_classes),
          'id' => 'pl-' . $post_id
        ), $post_id, $panels_data);

        echo apply_filters('wpp::layouts::before_container', '<div id="wpp_layout">');

        echo '<div';
        foreach ($panel_layout_attributes as $name => $value) {
          if ($value) {
            echo ' ' . $name . '="' . esc_attr($value) . '"';
          }
        }
        echo '>';

        global $wpp_layouts_panels_inline_css;
        if (empty($wpp_layouts_panels_inline_css)) $wpp_layouts_panels_inline_css = array();

        if (!isset($wpp_layouts_panels_inline_css[$post_id])) {
          $wpp_layouts_panels_inline_css[$post_id] = $this->panels_generate_css($post_id, $panels_data);
        }

        foreach ($grids as $gi => $cells) {

          $grid_classes = apply_filters('wpp::layouts::panels_row_classes', array('panel-grid'), $panels_data['grids'][$gi]);
          $grid_id = !empty($panels_data['grids'][$gi]['style']['id']) ? sanitize_html_class($panels_data['grids'][$gi]['style']['id']) : false;

          $grid_attributes = apply_filters('wpp::layouts::panels_row_attributes', array(
            'class' => implode(' ', $grid_classes),
            'id' => !empty($grid_id) ? $grid_id : 'pg-' . $post_id . '-' . $gi,
          ), $panels_data['grids'][$gi]);

          echo apply_filters('wpp::layouts::panels_before_row', '', $panels_data['grids'][$gi], $grid_attributes);

          echo '<div ';
          foreach ($grid_attributes as $name => $value) {
            echo $name . '="' . esc_attr($value) . '" ';
          }
          echo '>';

          $style_attributes = array();
          if (!empty($panels_data['grids'][$gi]['style']['class'])) {
            $style_attributes['class'] = array('panel-row-style-' . $panels_data['grids'][$gi]['style']['class']);
          }

          $row_style_wrapper = $this->panels_start_style_wrapper('row', $style_attributes, !empty($panels_data['grids'][$gi]['style']) ? $panels_data['grids'][$gi]['style'] : array());
          if (!empty($row_style_wrapper)) echo $row_style_wrapper;

          $collapse_order = !empty($panels_data['grids'][$gi]['style']['collapse_order']) ? $panels_data['grids'][$gi]['style']['collapse_order'] : (!is_rtl() ? 'left-top' : 'right-top');

          if ($collapse_order == 'right-top') {
            $cells = array_reverse($cells, true);
          }

          foreach ($cells as $ci => $widgets) {
            $cell_classes = array('panel-grid-cell');
            if (empty($widgets)) {
              $cell_classes[] = 'panel-grid-cell-empty';
            }
            if ($ci == count($cells) - 2 && count($cells[$ci + 1]) == 0) {
              $cell_classes[] = 'panel-grid-cell-mobile-last';
            }
            // Themes can add their own styles to cells
            $cell_classes = apply_filters('wpp::layouts::panels_row_cell_classes', $cell_classes, $panels_data);
            $cell_attributes = apply_filters('wpp::layouts::panels_row_cell_attributes', array(
              'class' => implode(' ', $cell_classes),
              'id' => 'pgc-' . $post_id . '-' . (!empty($grid_id) ? $grid_id : $gi) . '-' . $ci
            ), $panels_data);

            echo '<div ';
            foreach ($cell_attributes as $name => $value) {
              echo $name . '="' . esc_attr($value) . '" ';
            }
            echo '>';

            $cell_style_wrapper = $this->panels_start_style_wrapper('cell', array(), !empty($panels_data['grids'][$gi]['style']) ? $panels_data['grids'][$gi]['style'] : array());
            if (!empty($cell_style_wrapper)) echo $cell_style_wrapper;

            foreach ($widgets as $pi => $widget_info) {
              $widget_style_wrapper = $this->panels_start_style_wrapper('widget', array(), !empty($widget_info['panels_info']['style']) ? $widget_info['panels_info']['style'] : array());
              $this->panels_the_widget($widget_info['panels_info'], $widget_info, $gi, $ci, $pi, $pi == 0, $pi == count($widgets) - 1, $post_id, $widget_style_wrapper);
            }

            if (!empty($cell_style_wrapper)) echo '</div>';
            echo '</div>';
          }

          echo '</div>';

          if (!empty($row_style_wrapper)) echo '</div>';

          echo apply_filters('wpp::layouts::panels_after_row', '', $panels_data['grids'][$gi], $grid_attributes);
        }

        echo '</div>';

        echo apply_filters('wpp::layouts::after_container', '</div>');

        $html = ob_get_clean();

        return apply_filters('wpp::layouts::panels_render', $html, $post_id, !empty($post) ? $post : null);
      }

      /**
       * @param $post_id
       * @param bool $panels_data
       * @return string|void
       */
      public function panels_generate_css($post_id, $panels_data = false)
      {
        // Exit if we don't have panels data
        if (empty($panels_data)) {
          $panels_data = get_post_meta($post_id, 'panels_data', true);
          $panels_data = apply_filters('siteorigin_panels_data', $panels_data, $post_id);
        }
        if (empty($panels_data) || empty($panels_data['grids'])) return;

        $panels_tablet_width = 1024;
        $panels_mobile_width = 780;
        $panels_margin_bottom = 30;
        $panels_margin_bottom_last_row = 0;
        $responsive = true;
        $tablet_layout = false;
        $margin_sides = 30;

        $css = new Panels_Css_Builder();

        $ci = 0;
        foreach ($panels_data['grids'] as $gi => $grid) {

          $cell_count = intval($grid['cells']);
          $grid_id = !empty($grid['style']['id']) ? (string)sanitize_html_class($grid['style']['id']) : intval($gi);

          // Add the cell sizing
          for ($i = 0; $i < $cell_count; $i++) {
            $cell = $panels_data['grid_cells'][$ci++];

            if ($cell_count > 1) {
              $width = round($cell['weight'] * 100, 3) . '%';
              $width = apply_filters('siteorigin_panels_css_cell_width', $width, $grid, $gi, $cell, $ci - 1, $panels_data, $post_id);

              // Add the width and ensure we have correct formatting for CSS.
              $css->add_cell_css($post_id, $grid_id, $i, '', array(
                'width' => str_replace(',', '.', $width)
              ));
            }
          }

          // Add the bottom margin to any grids that aren't the last
          if ($gi != count($panels_data['grids']) - 1 || !empty($grid['style']['bottom_margin']) || !empty($panels_margin_bottom_last_row)) {
            // Filter the bottom margin for this row with the arguments
            $css->add_row_css($post_id, $grid_id, '', array(
              'margin-bottom' => apply_filters('siteorigin_panels_css_row_margin_bottom', $panels_margin_bottom . 'px', $grid, $gi, $panels_data, $post_id)
            ));
          }

          $collapse_order = !empty($grid['style']['collapse_order']) ? $grid['style']['collapse_order'] : (!is_rtl() ? 'left-top' : 'right-top');

          if ($cell_count > 1) {
            $css->add_cell_css($post_id, $grid_id, false, '', array(
              // Float right for RTL
              'float' => $collapse_order == 'left-top' ? 'left' : 'right'
            ));
          } else {
            $css->add_cell_css($post_id, $grid_id, false, '', array(
              // Float right for RTL
              'float' => 'none'
            ));
          }

          if ($responsive) {

            if ($tablet_layout && $cell_count >= 3 && $panels_tablet_width > $panels_mobile_width) {
              // Tablet Responsive
              $css->add_cell_css($post_id, $grid_id, false, '', array(
                'width' => '50%'
              ), $panels_tablet_width);
            }

            // Mobile Responsive
            $css->add_cell_css($post_id, $grid_id, false, '', array(
              'float' => 'none',
              'width' => 'auto'
            ), $panels_mobile_width);

            for ($i = 0; $i < $cell_count; $i++) {
              if (($collapse_order == 'left-top' && $i != $cell_count - 1) || ($collapse_order == 'right-top' && $i !== 0)) {
                $css->add_cell_css($post_id, $grid_id, $i, '', array(
                  'margin-bottom' => $panels_margin_bottom . 'px',
                ), $panels_mobile_width);
              }
            }
          }
        }

        // Add the bottom margins
        $css->add_cell_css($post_id, false, false, '.so-panel', array(
          'margin-bottom' => apply_filters('siteorigin_panels_css_cell_margin_bottom', $panels_margin_bottom . 'px', $grid, $gi, $panels_data, $post_id)
        ));
        $css->add_cell_css($post_id, false, false, '.so-panel:last-child', array(
          'margin-bottom' => apply_filters('siteorigin_panels_css_cell_last_margin_bottom', '0px', $grid, $gi, $panels_data, $post_id)
        ));

        if ($responsive) {
          // Add CSS to prevent overflow on mobile resolution.
          $css->add_row_css($post_id, false, '', array(
            'margin-left' => 0,
            'margin-right' => 0,
          ), $panels_mobile_width);

          $css->add_cell_css($post_id, false, false, '', array(
            'padding' => 0,
          ), $panels_mobile_width);

          // Hide empty cells on mobile
          $css->add_row_css($post_id, false, '.panel-grid-cell-empty', array(
            'display' => 'none',
          ), $panels_mobile_width);

          // Hide empty cells on mobile
          $css->add_row_css($post_id, false, '.panel-grid-cell-mobile-last', array(
            'margin-bottom' => '0px',
          ), $panels_mobile_width);
        }

        // Let other plugins customize various aspects of the rows (grids)
        foreach ($panels_data['grids'] as $gi => $grid) {
          $grid_id = !empty($grid['style']['id']) ? (string)sanitize_html_class($grid['style']['id']) : intval($gi);

          // Let other themes and plugins change the gutter.
          $gutter = apply_filters('siteorigin_panels_css_row_gutter', $margin_sides . 'px', $grid, $gi, $panels_data);

          if (!empty($gutter)) {
            // We actually need to find half the gutter.
            preg_match('/([0-9\.,]+)(.*)/', $gutter, $match);
            if (!empty($match[1])) {
              $margin_half = (floatval($match[1]) / 2) . $match[2];
              $css->add_row_css($post_id, $grid_id, '', array(
                'margin-left' => '-' . $margin_half,
                'margin-right' => '-' . $margin_half,
              ));
              $css->add_cell_css($post_id, $grid_id, false, '', array(
                'padding-left' => $margin_half,
                'padding-right' => $margin_half,
              ));

            }
          }
        }

        foreach ($panels_data['widgets'] as $widget_id => $widget) {
          if (!empty($widget['panels_info']['style']['link_color'])) {
            $selector = '#panel-' . $post_id . '-' . $widget['panels_info']['grid'] . '-' . $widget['panels_info']['cell'] . '-' . $widget['panels_info']['cell_index'] . ' a';
            $css->add_css($selector, array(
              'color' => $widget['panels_info']['style']['link_color']
            ));
          }
        }

        // Let other plugins and components filter the CSS object.
        $css = apply_filters('siteorigin_panels_css_object', $css, $panels_data, $post_id);
        return $css->get_css();
      }

      /**
       * Render panel widget
       * @param $widget_info
       * @param $instance
       * @param $grid
       * @param $cell
       * @param $panel
       * @param $is_first
       * @param $is_last
       * @param bool $post_id
       * @param string $style_wrapper
       */
      public function panels_the_widget($widget_info, $instance, $grid, $cell, $panel, $is_first, $is_last, $post_id = false, $style_wrapper = '')
      {
        $widget = $widget_info['class'];

        if (!class_exists($widget)) {
          return;
        }

        $the_widget = apply_filters('siteorigin_panels_widget_object', new $widget(), $widget, $instance);

        if (empty($post_id)) $post_id = get_the_ID();

        $classes = array('so-panel', 'widget');

        if (!empty($the_widget) && !empty($the_widget->id_base)) $classes[] = 'widget_' . $the_widget->id_base;
        if (!empty($the_widget) && is_array($the_widget->widget_options) && !empty($the_widget->widget_options['classname'])) $classes[] = $the_widget->widget_options['classname'];
        if ($is_first) $classes[] = 'panel-first-child';
        if ($is_last) $classes[] = 'panel-last-child';
        $id = 'panel-' . $post_id . '-' . $grid . '-' . $cell . '-' . $panel;

        $classes = apply_filters('wpp::layouts::panels_widget_classes', $classes, $widget, $instance, $widget_info);
        $classes = explode(' ', implode(' ', $classes));
        $classes = array_filter($classes);
        $classes = array_unique($classes);
        $classes = array_map('sanitize_html_class', $classes);

        $before_title = '<h3 class="widget-title">';
        $after_title = '</h3>';

        $args = array(
          'before_widget' => '<div class="' . esc_attr(implode(' ', $classes)) . '" id="' . $id . '" data-index="' . $widget_info['widget_index'] . '">',
          'after_widget' => '</div>',
          'before_title' => $before_title,
          'after_title' => $after_title,
          'widget_id' => 'widget-' . $grid . '-' . $cell . '-' . $panel
        );

        $args = apply_filters('wpp::layouts::panels_widget_args', $args);

        if (!empty($style_wrapper)) {
          $args['before_widget'] = $args['before_widget'] . $style_wrapper;
          $args['after_widget'] = '</div>' . $args['after_widget'];
        }

        if (!empty($the_widget) && is_a($the_widget, 'WP_Widget')) {
          $the_widget->widget($args, $instance);
        } else {
          echo apply_filters('wpp::layouts::panels_missing_widget', $args['before_widget'] . $args['after_widget'], $widget, $args, $instance);
        }
      }

      /**
       * Start wrapper
       * @param $name
       * @param $style_attributes
       * @param array $style_args
       * @return string
       */
      public function panels_start_style_wrapper($name, $style_attributes, $style_args = array())
      {

        $style_wrapper = '';

        if (empty($style_attributes['class'])) $style_attributes['class'] = array();
        if (empty($style_attributes['style'])) $style_attributes['style'] = '';

        $style_attributes = apply_filters('wpp::layouts::panels_' . $name . '_style_attributes', $style_attributes, $style_args);

        if (empty($style_attributes['class'])) unset($style_attributes['class']);
        if (empty($style_attributes['style'])) unset($style_attributes['style']);

        if (!empty($style_attributes)) {
          if (empty($style_attributes['class'])) $style_attributes['class'] = array();
          $style_attributes['class'][] = 'panel-' . $name . '-style';
          $style_attributes['class'] = array_unique($style_attributes['class']);

          // Filter and sanitize the classes
          $style_attributes['class'] = apply_filters('wpp::layouts::panels_' . $name . '_style_classes', $style_attributes['class'], $style_attributes, $style_args);
          $style_attributes['class'] = array_map('sanitize_html_class', $style_attributes['class']);

          $style_wrapper = '<div ';
          foreach ($style_attributes as $name => $value) {
            if (is_array($value)) {
              $style_wrapper .= $name . '="' . esc_attr(implode(" ", array_unique($value))) . '" ';
            } else {
              $style_wrapper .= $name . '="' . esc_attr($value) . '" ';
            }
          }
          $style_wrapper .= '>';

          return $style_wrapper;
        }

        return $style_wrapper;
      }
    }
  }

  if (!class_exists('UsabilityDynamics\WPP\Panels_Css_Builder')) {
    class Panels_Css_Builder
    {

      private $css;

      function __construct()
      {
        $this->css = array();
      }

      /**
       * Add some general CSS.
       *
       * @param string $selector
       * @param array $attributes
       * @param int $resolution The pixel resolution that this applies to
       */
      function add_css($selector, $attributes, $resolution = 1920)
      {
        $attribute_string = array();
        foreach ($attributes as $k => $v) {
          if (empty($v)) continue;
          $attribute_string[] = $k . ':' . $v;
        }
        $attribute_string = implode(';', $attribute_string);

        // Add everything we need to the CSS selector
        if (empty($this->css[$resolution])) $this->css[$resolution] = array();
        if (empty($this->css[$resolution][$attribute_string])) $this->css[$resolution][$attribute_string] = array();
        $this->css[$resolution][$attribute_string][] = $selector;
      }

      /**
       * Add CSS that applies to a row or group of rows.
       *
       * @param int $li The layout ID. If false, then the CSS applies to all layouts.
       * @param int|bool|string $ri The row index. If false, then the CSS applies to all rows.
       * @param string $sub_selector A sub selector if we need one.
       * @param array $attributes An array of attributes.
       * @param int $resolution The pixel resolution that this applies to
       * @param bool $specify_layout Sometimes for CSS specificity, we need to include the layout ID.
       */
      function add_row_css($li, $ri = false, $sub_selector = '', $attributes = array(), $resolution = 1920, $specify_layout = false)
      {
        $selector = array();

        if ($ri === false) {
          // This applies to all rows
          $selector[] = '#pl-' . $li;
          $selector[] = '.panel-grid';
        } else {
          // This applies to a specific row
          if ($specify_layout) $selector[] = '#pl-' . $li;
          if (is_string($ri)) {
            $selector[] = '#' . $ri;
          } else {
            $selector[] = '#pg-' . $li . '-' . $ri;
          }

        }

        // Add in the sub selector
        if (!empty($sub_selector)) $selector[] = $sub_selector;

        // Add this to the CSS array
        $this->add_css(implode(' ', $selector), $attributes, $resolution);
      }

      /**
       * @param int $li The layout ID. If false, then the CSS applies to all layouts.
       * @param int|bool $ri The row index. If false, then the CSS applies to all rows.
       * @param int|bool $ci The cell index. If false, then the CSS applies to all rows.
       * @param string $sub_selector A sub selector if we need one.
       * @param array $attributes An array of attributes.
       * @param int $resolution The pixel resolution that this applies to
       * @param bool $specify_layout Sometimes for CSS specificity, we need to include the layout ID.
       */
      function add_cell_css($li, $ri = false, $ci = false, $sub_selector = '', $attributes = array(), $resolution = 1920, $specify_layout = false)
      {
        $selector = array();

        if ($ri === false && $ci === false) {
          // This applies to all cells in the layout
          $selector[] = '#pl-' . $li;
          $selector[] = '.panel-grid-cell';
        } elseif ($ri !== false && $ci === false) {
          // This applies to all cells in a row
          if ($specify_layout) $selector[] = '#pl-' . $li;
          $selector[] = is_string($ri) ? ('#' . $ri) : '#pg-' . $li . '-' . $ri;
          $selector[] = '.panel-grid-cell';
        } elseif ($ri !== false && $ci !== false) {
          // This applies to a specific cell
          if ($specify_layout) $selector[] = '#pl-' . $li;
          $selector[] = '#pgc-' . $li . '-' . $ri . '-' . $ci;
        }

        // Add in the sub selector
        if (!empty($sub_selector)) {
          $selector[] = $sub_selector;
        }

        // Add this to the CSS array
        $this->add_css(implode(' ', $selector), $attributes, $resolution);
      }

      /**
       * Gets the CSS for this particular layout.
       */
      function get_css()
      {
        // Build actual CSS from the array
        $css_text = '';
        krsort($this->css);
        foreach ($this->css as $res => $def) {
          if (empty($def)) continue;

          if ($res < 1920) {
            $css_text .= '@media (max-width:' . $res . 'px)';
            $css_text .= '{ ';
          }

          foreach ($def as $property => $selector) {
            $selector = array_unique($selector);
            $css_text .= implode(' , ', $selector) . ' { ' . $property . ' } ';
          }

          if ($res < 1920) $css_text .= ' } ';
        }

        return $css_text;
      }
    }
  }
}