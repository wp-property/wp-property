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

        if ( function_exists( 'siteorigin_panels_render' ) ) {

          if( $render[ 'layout_id' ] ) {
            return siteorigin_panels_render( $render[ 'layout_id' ], true, $_layout_config );
          }

          if( $render['layout_meta'] ) {
            return siteorigin_panels_render( $post->ID, true, $render[ 'layout_meta' ] );
          }

        }

        return $data;

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