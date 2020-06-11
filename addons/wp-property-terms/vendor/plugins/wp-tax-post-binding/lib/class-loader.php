<?php

/**
 * Loader
 *
 * @since 1.0.0
 */

namespace UsabilityDynamics\CFTPB {

  if (!class_exists('UsabilityDynamics\CFTPB\Loader')) {

    final class Loader {

      /**
       * The list of post types which have extended taxonomies
       *
       * @var array
       */
      var $post_types = array();

      /**
       * The list of taxonomies which must not be extended.
       *
       * @var array
       */
      var $exclude = array();

      /**
       *
       */
      public function __construct( $args = array() ) {

        if( !empty( $args['post_types'] ) && is_array( $args['post_types'] ) ) {
          $this->post_types = $args['post_types'];
        }

        if( !empty( $args['exclude'] ) && is_array( $args['exclude'] ) ) {
          $this->exclude = $args['exclude'];
        }

        if( !defined( 'CF_TAX_POST_BINDING' ) && ( current_filter( 'plugins_loaded' ) || did_action( 'plugins_loaded' ) ) ) {
          include_once( dirname( dirname( __FILE__ ) ) . '/cf-tax-post-binding.php' );
        }

        add_filter( 'cftpb_configs', array( $this, 'setup_extended_taxonomies' ) );

      }

      /**
       * Add taxonomies whihc should be extended to 'CF Taxonomy Post Type Binding' configutation
       */
      public function setup_extended_taxonomies( $configs ) {

        if( !is_array( $configs ) ) {
          $configs = array();
        }

        $existed_taxonomies = array();
        foreach( $configs as $config ) {
          $tax_name = false;
          if (empty($config['taxonomy'])) {
            continue;
          }
          if (is_string($config['taxonomy'])) {
            $tax_name = $config['taxonomy'];
          }
          else if (is_array($config['taxonomy'])) {
            $args = count($config['taxonomy']);
            if (!$args == 3) {
              continue;
            }
            else if (!is_string($config['taxonomy'][0])) {
              continue;
            }
            if (taxonomy_exists($config['taxonomy'][0])) {
              $tax_name = $config['taxonomy'][0];
            }
          }
          if( !empty($tax_name) ) {
            array_push( $existed_taxonomies, $tax_name );
          }
        }

        $extended_taxonomies = array();
        foreach( $this->post_types as $post_type  ) {
          $taxonomies = get_object_taxonomies( $post_type );
          if( !empty( $taxonomies ) ) {
            foreach( $taxonomies as $taxonomy ) {
              /* Be sure taxonomy should not be excluded. */
              if( in_array( $taxonomy, $this->exclude ) ) {
                continue;
              }
              /* Determine if taxonomy already added to the list. */
              if( in_array( $taxonomy, $extended_taxonomies ) ) {
                continue;
              }
              /* Determine if taxonomy already added to configuration. */
              if( in_array( $taxonomy, $existed_taxonomies ) ) {
                continue;
              }
              $extended_taxonomies[] = $taxonomy;
            }
          }
        }

        foreach( $extended_taxonomies as $taxonomy ){
          $_taxonomy = get_taxonomy( $taxonomy );
          $rewrite = isset($_taxonomy->rewrite['slug'])?array('slug' => $_taxonomy->rewrite['slug'] ):false;
          array_push( $configs, array(
            'taxonomy' => $taxonomy,
            'post_type' => array(
              substr( $taxonomy, 0, 20 ),
              array(
                'label' => $_taxonomy->labels->name,
                'hierarchical' => $_taxonomy->hierarchical,
                'public' => true,
                'publicly_queryable' => true,
                'show_ui' => true,
                'show_in_menu' => false,
                'rewrite' => $rewrite,
                'rewrite' => $_taxonomy->rewrite,
                'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
              )
            ),
            'slave_title_editable' => false,
            'slave_slug_editable' => false,
          ) );
        }

        return $configs;
      }

    }

  }
}
