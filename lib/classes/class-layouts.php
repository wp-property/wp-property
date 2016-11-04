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
    final class Layouts extends Scaffold {

      /**
       * Layouts constructor.
       */
      public function __construct() {
        parent::__construct();

        add_action( 'init', array( $this, 'register_layout_post_type' ) );
        add_filter( 'template_include', array( $this, 'page_template' ), 99 );
      }

      /**
       * Define a template
       * @param $template
       * @return string
       */
      public function page_template( $template ) {
        global $wp_query;

        $render = apply_filters( 'wpp::layouts::settings', false );

        if ( $render && !empty( $wp_query->post ) ) {
          $wp_query->post->ID = $render[ 'layout_id' ];
        }

        if ( count( $wp_query->posts ) > 1 ) {
          $wp_query->posts = array($wp_query->post);
          $wp_query->post_count = 1;
        }

        if ( !$render ) return $template;

        add_filter( 'the_content', array( $this, 'the_content' ), 1000 );

        // @note This should probaly be used instead of our content-override.
        // add_filter( 'siteorigin_panels_data', array( $this, 'siteorigin_panels_data' ), 1000, 2 );

        $template = locate_template( $render[ 'templates' ] );

        return $template;

      }

      public function siteorigin_panels_data( $panels_data, $post_id ) {

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
      public function the_content( $data ) {
        global $property, $post;

        $render = apply_filters( 'wpp::layouts::settings', false );

        if ( !$render ) return $data;

        $_layout_config = apply_filters( 'wpp::layouts::layout_override', false, $render, $post );

        if( $render[ 'layout_id' ] ) {
          return function_exists( 'siteorigin_panels_render' ) ?
            siteorigin_panels_render( $render[ 'layout_id' ], true, $_layout_config ) :
            $this->standard_render( $render[ 'layout_id' ], $_layout_config );
        }

        if( $render['layout_meta'] ) {
          return function_exists( 'siteorigin_panels_render' ) ?
            siteorigin_panels_render( $post->ID, true, $render[ 'layout_meta' ] ) :
            $this->standard_render( $post->ID, $render[ 'layout_meta' ] );
        }

        return $data;

      }

      /**
       * Native render based on SO
       * @param bool $post_id
       * @param bool $panels_data
       * @return string
       */
      public function standard_render( $post_id = false, $panels_data = false ) {
        if( empty($post_id) ) $post_id = get_the_ID();

        if( empty( $panels_data ) ) {
          $panels_data = get_post_meta( $post_id, 'panels_data', true );
        }

        $panels_data = apply_filters( 'wpp::layouts::panels_data', $panels_data, $post_id );
        if ( empty( $panels_data ) || empty( $panels_data['grids'] ) ) return 'No panels data found.';

        if ( !empty( $panels_data['widgets'] ) ) {
          $last_gi = 0;
          $last_ci = 0;
          $last_wi = 0;
          foreach ( $panels_data['widgets'] as $wid => &$widget_info ) {

            if ( $widget_info['panels_info']['grid'] != $last_gi ) {
              $last_gi = $widget_info['panels_info']['grid'];
              $last_ci = 0;
              $last_wi = 0;
            }
            elseif ( $widget_info['panels_info']['cell'] != $last_ci ) {
              $last_ci = $widget_info['panels_info']['cell'];
              $last_wi = 0;
            }
            $widget_info['panels_info']['cell_index'] = $last_wi++;
          }
        }

        $grids = array();
        if( !empty( $panels_data['grids'] ) && !empty( $panels_data['grids'] ) ) {
          foreach ( $panels_data['grids'] as $gi => $grid ) {
            $gi = intval( $gi );
            $grids[$gi] = array();
            for ( $i = 0; $i < $grid['cells']; $i++ ) {
              $grids[$gi][$i] = array();
            }
          }
        }

        if( !empty( $panels_data['widgets'] ) && is_array($panels_data['widgets']) ) {
          foreach ( $panels_data['widgets'] as $i => $widget ) {
            if( empty( $panels_data['widgets'][$i]['panels_info'] ) ) {
              $panels_data['widgets'][$i]['panels_info'] = $panels_data['widgets'][$i]['info'];
              unset($panels_data['widgets'][$i]['info']);
            }

            $panels_data['widgets'][$i]['panels_info']['widget_index'] = $i;
          }
        }

        if( !empty( $panels_data['widgets'] ) && is_array($panels_data['widgets']) ){
          foreach ( $panels_data['widgets'] as $widget ) {
            $grids[ intval( $widget['panels_info']['grid']) ][ intval( $widget['panels_info']['cell'] ) ][] = $widget;
          }
        }

        ob_start();

        $panel_layout_classes = apply_filters( 'wpp::layouts::panels_layout_classes', array(), $post_id, $panels_data );
        $panel_layout_attributes = apply_filters( 'wpp::layouts::panels_layout_attributes', array(
            'class' => implode( ' ', $panel_layout_classes ),
            'id' => 'pl-' . $post_id
        ),  $post_id, $panels_data );
        echo '<div';
        foreach ( $panel_layout_attributes as $name => $value ) {
          if ($value) {
            echo ' ' . $name . '="' . esc_attr($value) . '"';
          }
        }
        echo '>';

        foreach ( $grids as $gi => $cells ) {

          $grid_classes = apply_filters( 'wpp::layouts::panels_row_classes', array( 'panel-grid' ), $panels_data['grids'][$gi] );
          $grid_id = !empty($panels_data['grids'][$gi]['style']['id']) ? sanitize_html_class( $panels_data['grids'][$gi]['style']['id'] ) : false;

          $grid_attributes = apply_filters( 'wpp::layouts::panels_row_attributes', array(
              'class' => implode( ' ', $grid_classes ),
              'id' => !empty($grid_id) ? $grid_id : 'pg-' . $post_id . '-' . $gi,
          ), $panels_data['grids'][$gi] );

          echo apply_filters( 'wpp::layouts::panels_before_row', '', $panels_data['grids'][$gi], $grid_attributes );

          echo '<div ';
          foreach ( $grid_attributes as $name => $value ) {
            echo $name.'="'.esc_attr($value).'" ';
          }
          echo '>';

          $style_attributes = array();
          if( !empty( $panels_data['grids'][$gi]['style']['class'] ) ) {
            $style_attributes['class'] = array('panel-row-style-'.$panels_data['grids'][$gi]['style']['class']);
          }

          $row_style_wrapper = $this->panels_start_style_wrapper( 'row', $style_attributes, !empty($panels_data['grids'][$gi]['style']) ? $panels_data['grids'][$gi]['style'] : array() );
          if( !empty($row_style_wrapper) ) echo $row_style_wrapper;

          $collapse_order = !empty( $panels_data['grids'][$gi]['style']['collapse_order'] ) ? $panels_data['grids'][$gi]['style']['collapse_order'] : ( !is_rtl() ? 'left-top' : 'right-top' );

          if( $collapse_order == 'right-top' ) {
            $cells = array_reverse( $cells, true );
          }

          foreach ( $cells as $ci => $widgets ) {
            $cell_classes = array('panel-grid-cell');
            if( empty( $widgets ) ) {
              $cell_classes[] = 'panel-grid-cell-empty';
            }
            if( $ci == count( $cells ) - 2 && count( $cells[ $ci + 1 ] ) == 0 ) {
              $cell_classes[] = 'panel-grid-cell-mobile-last';
            }
            // Themes can add their own styles to cells
            $cell_classes = apply_filters( 'wpp::layouts::panels_row_cell_classes', $cell_classes, $panels_data );
            $cell_attributes = apply_filters( 'wpp::layouts::panels_row_cell_attributes', array(
                'class' => implode( ' ', $cell_classes ),
                'id' => 'pgc-' . $post_id . '-' . ( !empty($grid_id) ? $grid_id : $gi )  . '-' . $ci
            ), $panels_data );

            echo '<div ';
            foreach ( $cell_attributes as $name => $value ) {
              echo $name.'="'.esc_attr($value).'" ';
            }
            echo '>';

            $cell_style_wrapper = $this->panels_start_style_wrapper( 'cell', array(), !empty($panels_data['grids'][$gi]['style']) ? $panels_data['grids'][$gi]['style'] : array() );
            if( !empty($cell_style_wrapper) ) echo $cell_style_wrapper;

            foreach ( $widgets as $pi => $widget_info ) {
              $widget_style_wrapper = $this->panels_start_style_wrapper( 'widget', array(), !empty( $widget_info['panels_info']['style'] ) ? $widget_info['panels_info']['style'] : array() );
              $this->panels_the_widget( $widget_info['panels_info'], $widget_info, $gi, $ci, $pi, $pi == 0, $pi == count( $widgets ) - 1, $post_id, $widget_style_wrapper );
            }

            if( !empty($cell_style_wrapper) ) echo '</div>';
            echo '</div>';
          }

          echo '</div>';

          if( !empty($row_style_wrapper) ) echo '</div>';

          echo apply_filters( 'wpp::layouts::panels_after_row', '', $panels_data['grids'][$gi], $grid_attributes );
        }

        echo '</div>';

        $html = ob_get_clean();

        return apply_filters( 'wpp::layouts::panels_render', $html, $post_id, !empty($post) ? $post : null );
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
      public function panels_the_widget( $widget_info, $instance, $grid, $cell, $panel, $is_first, $is_last, $post_id = false, $style_wrapper = '' ) {
        $widget = $widget_info['class'];

        if ( !class_exists( $widget ) ) {
          return;
        }

        $the_widget = apply_filters( 'siteorigin_panels_widget_object', new $widget(), $widget, $instance );

        if( empty($post_id) ) $post_id = get_the_ID();

        $classes = array( 'so-panel', 'widget' );

        if ( !empty( $the_widget ) && !empty( $the_widget->id_base ) ) $classes[] = 'widget_' . $the_widget->id_base;
        if ( !empty( $the_widget ) && is_array( $the_widget->widget_options ) && !empty( $the_widget->widget_options['classname'] ) ) $classes[] = $the_widget->widget_options['classname'];
        if ( $is_first ) $classes[] = 'panel-first-child';
        if ( $is_last ) $classes[] = 'panel-last-child';
        $id = 'panel-' . $post_id . '-' . $grid . '-' . $cell . '-' . $panel;

        $classes = apply_filters( 'wpp::layouts::panels_widget_classes', $classes, $widget, $instance, $widget_info );
        $classes = explode( ' ', implode( ' ', $classes ) );
        $classes = array_filter( $classes );
        $classes = array_unique( $classes );
        $classes = array_map( 'sanitize_html_class', $classes );

        $before_title = '<h3 class="widget-title">';
        $after_title = '</h3>';

        $args = array(
            'before_widget' => '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" id="' . $id . '" data-index="' . $widget_info['widget_index'] . '">',
            'after_widget' => '</div>',
            'before_title' => $before_title,
            'after_title' => $after_title,
            'widget_id' => 'widget-' . $grid . '-' . $cell . '-' . $panel
        );

        $args = apply_filters('wpp::layouts::panels_widget_args', $args);

        if( !empty($style_wrapper) ) {
          $args['before_widget'] = $args['before_widget'] . $style_wrapper;
          $args['after_widget'] = '</div>' . $args['after_widget'];
        }

        if ( !empty($the_widget) && is_a($the_widget, 'WP_Widget')  ) {
          $the_widget->widget( $args , $instance );
        }
        else {
          echo apply_filters('wpp::layouts::panels_missing_widget', $args['before_widget'] . $args['after_widget'], $widget, $args , $instance);
        }
      }

      /**
       * Start wrapper
       * @param $name
       * @param $style_attributes
       * @param array $style_args
       * @return string
       */
      public function panels_start_style_wrapper($name, $style_attributes, $style_args = array()) {

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

      /**
       * Register post type
       */
      public function register_layout_post_type() {

        $labels = array(
          'name'               => _x( 'Layouts', 'post type general name', ud_get_wp_property()->domain ),
          'singular_name'      => _x( 'Layout', 'post type singular name', ud_get_wp_property()->domain ),
          'menu_name'          => _x( 'Layouts', 'admin menu', ud_get_wp_property()->domain ),
          'name_admin_bar'     => _x( 'Layout', 'add new on admin bar', ud_get_wp_property()->domain ),
          'add_new'            => _x( 'Add New', 'Layout', ud_get_wp_property()->domain ),
          'add_new_item'       => __( 'Add New Layout', ud_get_wp_property()->domain ),
          'new_item'           => __( 'New Layout', ud_get_wp_property()->domain ),
          'edit_item'          => __( 'Edit Layout', ud_get_wp_property()->domain ),
          'view_item'          => __( 'View Layout', ud_get_wp_property()->domain ),
          'all_items'          => __( 'Layouts', ud_get_wp_property()->domain ),
          'search_items'       => __( 'Search Layouts', ud_get_wp_property()->domain ),
          'parent_item_colon'  => __( 'Parent Layouts:', ud_get_wp_property()->domain ),
          'not_found'          => __( 'No Layouts found.', ud_get_wp_property()->domain ),
          'not_found_in_trash' => __( 'No Layouts found in Trash.', ud_get_wp_property()->domain )
        );

        register_post_type( 'wpp_layout', array(
          'public' => false,
          'show_ui' => true,
          'description' => __( 'Layouts for property pages.' ),
          'can_export' > true,
          'rewrite' => false,
          'labels' => $labels,
          'show_in_menu' => 'edit.php?post_type=property',
          'supports' => array(  'title', 'editor', 'revisions'  )
        ) );

      }

    }

  }
}