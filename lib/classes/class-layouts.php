<?php
/**
 * Custom predefined layouts
 *
 *
 * Filters:
 *
 * wpp::layouts::layout_override
 * wpp::layouts::configuration
 * wpp::layouts::default_template
 * wpp::layouts::current
 * wpp::layouts::panels_data
 * wpp::layouts::panels_layout_classes
 * wpp::layouts::panels_layout_attributes
 * wpp::layouts::before_container
 * wpp::layouts::after_container
 * wpp::layouts::panels_render
 *
 */
namespace UsabilityDynamics\WPP {

  use WP_Error;
  use Exception;
  use ChromePhp;

  if (!class_exists('UsabilityDynamics\WPP\Layouts')) {

    /**
     * Class Layouts
     * @package UsabilityDynamics\WPP
     */
    final class Layouts extends Scaffold
    {

      /**
       * @var array
       */
      private $possible_tags = array(
        'single-property', 'property-overview', 'term-overview'
      );

      /**
       * @var
       */
      private $api_client;

      /**
       * Layouts constructor.
       */
      public function __construct()  {

        /**
         *
         * @todo Construct runs on every page load, it seems. Breaks term landing pages. Does not chedck for property type and term pages and overrides $wp_query->posts to first only. - potanin@UD
         */
        parent::__construct();

        $this->api_client = new Layouts_API_Client(array(
          'url' => defined('UD_API_LAYOUTS_URL') ? UD_API_LAYOUTS_URL : 'https://api.usabilitydynamics.com/product/property/layouts/v1'
        ));

        // Identify page template to use.
        add_filter('template_include', array($this, 'page_template'), 98);

        // Override Layout metadata.
        add_action( 'get_header', array( $this, 'get_header' ), 50 );
        add_action( 'get_footer', array( $this, 'get_footer' ), 50 );

        // Set default layout configuration.
        add_filter('wpp::layouts::configuration', array( $this, 'get_configuration' ) );


        // Add footer CSS. (Hopefully not used).
        add_action('wp_footer', array($this, 'panels_print_inline_css'));


      }

      /**
       * Extends layouts with local layouts.
       *
       * @param $layouts
       * @return mixed
       */
      public function add_local_layouts( $layouts ) {

        $local_layouts = get_option('wpp_available_local_layouts', array());

        foreach ($local_layouts as $value) {

          $value->local = true;

          foreach( (array) $value->tags as $_tag ) {

            // Create array if it does not exist.
            $layouts[ $_tag->tag ] = isset( $layouts[ $_tag->tag ] ) && is_array( $layouts[ $_tag->tag ] ) ? $layouts[ $_tag->tag ] : array();

            $value->local = true;
            $value->_id = isset( $value->_id ) ? $value->_id : sanitize_title( $value->title );

            if( isset( $layouts[ $_tag->tag ] ) && is_array( $layouts[ $_tag->tag ] ) ) {
              $layouts[ $_tag->tag ] = array_merge( array( $value ), $layouts[ $_tag->tag ] );
            }

          }

        }

        return $layouts;

      }

      /**
       * Gets layouts from options or from the API.
       *
       * @param array $args
       * @return array|mixed|void|WP_Error
       */
      public function get_public_layouts( $args = array() ) {
        // self::debug( 'get_public_layouts' );

        $args = wp_parse_args($args, array(
          'refresh' => false
        ));

        if( !$args['refresh'] ) {

          $_available_layouts = get_option('wpp_available_layouts', false);

          if( $_available_layouts ) {
            return $_available_layouts;
          }

        }

        $_layouts = $this->api_client->get_layouts();

        if( is_wp_error( $_layouts ) ) {
          return $_layouts;
        }

        if (is_array($_layouts)) {

          $_available_layouts = array();

          foreach ($this->possible_tags as $p_tag) {

            if( !isset( $_available_layouts[ $p_tag ] ) ) {
              $_available_layouts[ $p_tag ] = array();
            }

            foreach ($_layouts as $layout) {

              $layout->local = false;

              if (empty($layout->tags) || !is_array($layout->tags)) {
                continue;
              }

              $_found = false;

              foreach ($layout->tags as $_tag) {

                if ($_tag->tag == $p_tag) {
                  $_found = true;
                }

              }

              if (!$_found) {
                continue;
              }

              $_available_layouts[$p_tag][] = $layout;

            }
          }

          update_option( 'wpp_available_layouts', $_available_layouts );

          return $_available_layouts;

        }

        return new WP_Error( 'layouts-error', __( 'Layouts could not be loaded.' ));

      }

      /**
       * Returns appropriate layout based on page being viewed.
       *
       *
       * - layouts_property_overview_id
       *
       * - layouts_property_single_id - template to use for front-end. (e.g. page.php)
       * - layouts_property_overview_select
       * - layouts_term_overview_select
       *
       * @return array .templates
       * @internal param bool $use_layouts
       * @internal param $false
       */
      public function get_configuration() {
        global $wp_query;

        self::debug( 'get_configuration' );

        // fetch or get from cache
        $_layouts = $this->get_public_layouts();

        // add local layouts, if they exist.
        $_layouts = $this->add_local_layouts( $_layouts );

        $_options = array(
          'layout_id' => null,
          'layout_type' => 'public',
          'render_type' => null,
          'template_file' => null,
          'templates' => array( apply_filters( 'wpp::layouts::default_template', 'single.php' ) ),
          'layout_meta' => null,
          'layout_options' => array()
        );

        // Property Results / Property Overview
        if ( is_property_overview_page()) {
          $_options['render_type'] = 'property-overview';
          $_options['layout_id'] = get_theme_mod('layouts_property_overview_id', isset( $_layouts['property-overview'] ) ? reset($_layouts['property-overview'])->_id : null );
          $_options['template_file'] = get_theme_mod('layouts_property_overview_select', null );
        }

        // Property Terms
        if ( !is_property_overview_page() && (is_tax() || (get_queried_object() && get_queried_object()->taxonomy && in_array('property', get_taxonomy(get_queried_object()->taxonomy)->object_type)))) {
          $_options['render_type'] = 'term-overview';
          $_options['layout_id'] = get_theme_mod('layouts_property_term_id', isset( $_layouts['term-overview'] ) ? reset($_layouts['term-overview'])->_id : null );
          $_options['template_file'] = get_theme_mod('layouts_term_overview_select', null );
        }

        // For single property
        if (is_singular('property')) {
          $_options['render_type'] = 'single-property';
          $_options['layout_id'] = get_theme_mod('layouts_property_single_id', isset( $_layouts['single-property'] ) ? reset($_layouts['single-property'])->_id : null );
          $_options['template_file'] = get_theme_mod('layouts_property_single_select', null );
        }

        if (!empty($wp_query->wpp_search_page)) {
          $_options['render_type'] = 'property-search';
          $_options['layout_id'] = get_theme_mod('layouts_property_overview_id', isset( $_layouts['property-overview'] ) ? reset($_layouts['property-overview'])->_id : null );
          $_options['template_file'] = get_theme_mod('layouts_property_overview_select', null );
        }

        $_options = apply_filters('wpp::layouts::current', $_options, $_options );

        if( ( !isset( $_options['layout_meta'] ) || !$_options['layout_meta'] ) && $_single_layout = $this->get_layout( $_options['layout_id'], $_layouts ) ) {

          if( isset( $_single_layout->options ) ) {
            $_options['layout_options'] = $_single_layout->options;
          }

          $_options['layout_meta'] = $_single_layout->layout_meta;
          $_options['layout_type'] = ( isset( $_single_layout->local ) && $_single_layout->local ) ? 'local' : 'public';
        }

        if( $_options['template_file'] ) {
          $_options['templates'] = array_unique( array_merge( array( $_options['template_file'] ), $_options['templates'] ) );
        }

        if( isset( $_options['layout_meta'] ) && $_options['layout_meta'] ) {
          return $_options;
        }

        return false;

      }

      /**
       * Get [layout_meta] from provided layout array. If not found, fetch from API.
       *
       * @param $_id
       * @param $_layouts
       *
       * @return array|mixed|null|object
       */
      public function get_layout( $_id, $_layouts ) {
        // self::debug( 'get_layout_meta ' . $_id );

        if( !$_layouts ) {
          // @todo load layouts
        }
        foreach( (array) $_layouts as $_type => $_type_layouts ) {

          foreach( (array) $_type_layouts as $_single_layout ) {

            if( strval( $_single_layout->_id ) === strval( $_id ) ) {

              if( is_object( $_single_layout->layout ) ) {
                $_single_layout->layout_meta = $_single_layout->layout;
              }

              if( is_string( $_single_layout->layout ) ) {
                $_single_layout->layout_meta = json_decode( base64_decode($_single_layout->layout), true );
              }

              return $_single_layout;

            }

          }
        }

        return null;

      }

      /**
       * ChromePHP Logger
       *
       * @param bool $text
       * @param null $detail
       * @return bool|void
       */
      static public function debug($text = false, $detail = null)
      {

        global $wp_properties;

        $_debug = false;

        if( defined( 'WP_DEBUG' ) && WP_DEBUG && ( ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) || ( defined( 'WP_DEBUG_CONSOLE' ) && WP_DEBUG_CONSOLE ) ) ) {
          $_debug = true;
        }

        if ( !$_debug && ( !isset($wp_properties['configuration']['developer_mode']) || $wp_properties['configuration']['developer_mode'] !== 'true') ) {
          $_debug = false;
        }

        if($_debug && class_exists( 'ChromePhp' ) && !headers_sent() ) {

          // truncate strings to avoid sending oversized header.
          if( strlen( $text ) > 1000 ) {
            $text = '[truncated]';
          }

          if( $detail ) {
            ChromePhp::log( '[wp-property:layouts]', $text, $detail);
          } else {
            ChromePhp::log( '[wp-property:layouts]', $text );
          }

          return true;
        }

        return false;

      }

      /**
       * Adds hooks at template level.
       *
       */
      public function get_header() {

        // Add metadata override filter.
        add_filter( 'get_post_metadata', array( 'UsabilityDynamics\WPP\Layouts', 'override_metadata' ), 50, 4 );

      }


      /**
       * Revert back to original Post ID.
       *
       */
      public function get_footer() {

        // Remove our metadata override filter.
        remove_filter( 'get_post_metadata', array( 'UsabilityDynamics\WPP\Layouts', 'override_metadata' ), 50 );

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
            <style type="text/css" media="all" id="wpp-layouts-panels-grids-<?php echo esc_attr(current_filter()) ?>"><?php echo $the_css ?></style><?php
          }
        }
      }

      /**
       * If valid page for template and have a layout, override query, injecting the layout object.
       *
       * @param $template
       * @return string
       */
      public function page_template($template)
      {

        global $wp_query;

        $_layout = apply_filters('wpp::layouts::configuration', false);

        if (!$_layout) {
          return $template;
        }

        // Override currently queried post's ID so our layout can override metadata.
        if ($_layout && !empty($wp_query->post) && !empty($_layout['layout_id']) && $_layout['layout_id'] ) {
          $wp_query->post->_original_id = $wp_query->post->ID;
          $wp_query->post->_layout = $_layout;
          $wp_query->post->ID = intval( $_layout['layout_id'] );
        }

        // Set post count to 0 to avoid loops.
        if (count($wp_query->posts) > 1) {
          $wp_query->posts = array($wp_query->post);
          $wp_query->post_count = 1;
        }

        // Makes the layout meta be honored by themes like Divi. Otherwise our page wil be treated as a 404/archive page and sidebars may be added.
        $wp_query->is_page = true;
        $wp_query->is_archive = false;
        $wp_query->is_tax = false;
        $wp_query->is_single = true;

        //self::debug( 'page_template : ', $_layout['layout_id' ] );

        add_filter('the_content', array($this, 'the_content'), 1000);
        add_filter('the_excerpt', array($this, 'the_content'), 1000);

        self::debug( 'page_template', $_layout['templates'] );

        // Reset original post ID.
        if( isset( $wp_query->post ) && isset( $wp_query->post->_original_id ) ) {
          $wp_query->post->ID = $wp_query->post->_original_id;
        }

        $template = locate_template($_layout['templates']);

        return $template;

      }

      /**
       * Concept of overriding post meta to add theme-specific support.
       *
       * @param $default
       * @param $object_id
       * @param $meta_key
       * @param $single
       * @return string
       */
      static public function override_metadata( $default, $object_id, $meta_key, $single) {
        global $wp_query;

        //if( isset( $_layout['layout_options'] ) && isset( $_layout['layout_options']->layoutMetaOptions ) ) {}

        // Only apply to the main Layout/Post meta.
        if( !isset( $wp_query->post->_original_id ) && !isset( $wp_query->post ) || !isset( $wp_query->post->_layout ) || ( $wp_query->post->ID !== $object_id && $wp_query->post->_original_id !== $object_id ) ) {
          return $default;
        }

        if(  $meta_key === '_et_pb_use_builder' ) {
          // self::debug( 'override_metadata:_et_pb_use_builder' );
          return 'off';
        }

        if(  $meta_key === '_et_pb_page_layout' ) {
          // self::debug( 'override_metadata:_et_pb_page_layout -> et_full_width_page' );
          return 'et_full_width_page';
        }

        if(  $meta_key === '_et_pb_show_title' ) {
          // self::debug( 'override_metadata:_et_pb_show_title' );
          return 'off';
        }

        return $default;

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
        global $post;

        $render = apply_filters('wpp::layouts::configuration', false);

        if (!$render) {
          self::debug( "the_content - No layout." );
          return $data;
        }

        if (!empty($render['layout_meta'])) {
          $modified_data = $this->standard_render($post->ID, $render['layout_meta']);
        }

        // $_layout_config = apply_filters('wpp::layouts::layout_override', false, $render, $post);
        // $modified_data = $this->standard_render($render['layout_id'], $_layout_config);

        if( isset( $modified_data ) ) {
          return $modified_data;
        }

        return $data;

      }

      /**
       * Native render based on SO
       *
       * @param bool $post_id - ID of property to render.
       * @param bool $panels_data - Object with layout.
       * @return string
       */
      public function standard_render($post_id = false, $panels_data = false)
      {
        global $wpp_layouts_panels_inline_css;

        if (empty($post_id)) {
          $post_id = get_the_ID();
        }

        if (empty($panels_data)) {
          $panels_data = get_post_meta($post_id, 'panels_data', true);
        }

        $panels_data = apply_filters('wpp::layouts::panels_data', $panels_data, $post_id);

        if (empty($panels_data) || empty( $panels_data['grids'] )) {
          return 'No panels data found.';
        }

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

              $widget_style_wrapper = $this->panels_start_style_wrapper('widget', array(
                'class' => isset( $widget_info['panels_info']['style']['class'] ) ? array( $widget_info['panels_info']['style']['class'] ) : array(),
                'style' => isset( $widget_info['panels_info']['style']['widget_css'] ) ? $widget_info['panels_info']['style']['widget_css'] : '',
              ), !empty($widget_info['panels_info']['style']) ? $widget_info['panels_info']['style'] : array());

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

        $html = apply_filters('wpp::layouts::panels_render', $html, $post_id, !empty($post) ? $post : null);

        return $html;
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
        $css = apply_filters('siteorigin_panels_css_object', $css, $panels_data, $post_id, false);
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

        $classes = array('so-panel', 'widget', 'wpp-layout-element');

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
        if( $name === 'widget' ) {
          //$style_attributes = array('class' => '', 'style' => 'asdfas', 'data-stuff'=> 'asds' );
        }

        //if( $name === 'widget' ) {die( '<pre>' . print_r( $style_args, true ) . '</pre>' );}

        if (empty($style_attributes['class'])) $style_attributes['class'] = array();
        if (empty($style_attributes['style'])) $style_attributes['style'] = '';

        $style_attributes = apply_filters('wpp::layouts::panels_' . $name . '_style_attributes', $style_attributes, $style_args);

        if (empty($style_attributes['class'])) unset($style_attributes['class']);
        if (empty($style_attributes['style'])) unset($style_attributes['style']);



        if (!empty($style_attributes)) {

          if (empty($style_attributes['class'])) {
            $style_attributes['class'] = array();
          }

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

//          if( $name === 'widget' ) {echo( '<pre>$style_wrapper' . print_r( $style_wrapper, true ) . '</pre>' );}


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