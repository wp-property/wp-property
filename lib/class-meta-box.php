<?php
/**
 * Meta Box
 *
 * @version 1.0
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @since 1.38
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Meta_Box' ) ) {
  
    /**
     * Class Meta_Box
     *
     * @package UsabilityDynamics\WPP
     */
    class Meta_Box {
    
      public function define() {
        global $wpp;
      
        if( !defined( 'RWMB_URL' ) ) {
          define( 'RWMB_URL', $wpp->get( '_computed.url.modules' ) . '/wp-meta-box/' );
          define( 'RWMB_DIR', $wpp->get( '_computed.path.modules' ) . '/wp-meta-box/' );
          define( 'RWMB_VER', '4.3.4' );
          define( 'RWMB_JS_URL', trailingslashit( RWMB_URL . 'js' ) );
          define( 'RWMB_CSS_URL', trailingslashit( RWMB_URL . 'css' ) );
          define( 'RWMB_INC_DIR', trailingslashit( RWMB_DIR . 'inc' ) );
          define( 'RWMB_FIELDS_DIR', trailingslashit( RWMB_INC_DIR . 'fields' ) );
        }
        
        $_instance = new Meta_Box();
        
        add_action( "post_submitbox_misc_actions", array( $_instance, "post_submitbox_misc_actions" ) );
        
        /*
        
        // Adds metabox 'General Information' to Property Edit Page
        add_meta_box( 'wpp_property_meta', __( 'General Information', $wpp->text_domain ), array( '\UsabilityDynamics\WPP\UI', 'metabox_meta' ), 'property', 'normal', 'high' );

        // Adds 'Group' metaboxes to Property Edit Page
        if( !empty( $wp_properties[ 'property_groups' ] ) ) {
          foreach( (array) $wp_properties[ 'property_groups' ] as $slug => $group ) {
            // There is no sense to add metabox if no one attribute assigned to group
            if( !in_array( $slug, $wp_properties[ 'property_stats_groups' ] ) ) {
              continue;
            }
            // Determine if Group name is empty we add 'NO NAME', other way metabox will not be added
            if( empty( $group[ 'name' ] ) ) {
              $group[ 'name' ] = __( 'NO NAME', $wpp->text_domain );
            }
            add_meta_box( $slug, __( $group[ 'name' ], $wpp->text_domain ), array( '\UsabilityDynamics\WPP\UI', 'metabox_meta' ), 'property', 'normal', 'high', array( 'group' => $slug ) );
          }
        }

        add_meta_box( 'propetry_filter', $wp_properties[ 'labels' ][ 'name' ] . ' ' . __( 'Search', $wpp->text_domain ), array( 'UsabilityDynamics\WPP\UI', 'metabox_property_filter' ), 'property_page_all_properties', 'normal' );

        // Add metabox for child properties
        if( $post->post_type == 'property' && $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = '{$post->ID}' AND post_status = 'publish' " ) ) {
          add_meta_box( 'wpp_property_children', sprintf( __( 'Child %1s', $this->text_domain ), Utility::property_label( 'plural' ) ), array( '\UsabilityDynamics\WPP\UI', 'child_properties' ), 'property', 'side', 'high' );
        }
        
        //*/
        
        // Add Metaboxes.
        do_action( 'wpp:metaboxes' );
        
        return $_instance;
      }
      
      /**
       * Inserts content into the "Publish" metabox on property pages
       *
       * @since 1.04
       *
       */
      public function post_submitbox_misc_actions() {
        global $post, $wp_properties;

        if( $post->post_type == 'property' ) {

          ?>
          <div class="misc-pub-section ">

        <ul>
          <li><?php _e( 'Menu Sort Order:', $this->text_domain ) ?> <?php echo Utility::input( "name=menu_order&special=size=4", $post->menu_order ); ?></li>

          <?php if( current_user_can( 'manage_options' ) && $wp_properties[ 'configuration' ][ 'do_not_use' ][ 'featured' ] != 'true' ) { ?>
            <li><?php echo Utility::checkbox( "name=wpp_data[meta][featured]&label=" . __( 'Display in featured listings.', $this->text_domain ), get_post_meta( $post->ID, 'featured', true ) ); ?></li>
          <?php } ?>

          <?php do_action( 'wpp_publish_box_options' ); ?>
        </ul>

      </div>
        <?php

        }

        return;

      }

      /**
       * Instantiate New Meta Box
       *
       * ## Options
       * - size
       * - class
       * - multiple
       * - clone
       * - std
       * - desc
       * - format
       * - before
       * - after
       * - afterfield_name
       * - required
       * - placeholder
       * - context
       * - priority
       * - pages
       * - autosave
       * - default_hidden
       *
       * @param array $args
       */
      private function __construct( $args = false ) {
        

        $args = Utility::parse_args( $args, array(
          'context'  => 'normal',
          'priority' => 'normal',
          'pages'    => array( 'property', 'post' ),
          'autosave' => true,
          //'default_hidden' => false,
          //'size'          => 30,
          //'class'         => 'my-class',
          //'multiple'      => false,
          //'clone'         => false,
          //'std'           => '',
          //'desc'          => '',
          //'format'        => '',
          //'before'        => 'before',
          //'after'         => 'after',
          //'field_name'    => isset( $field['id'] ) ? $field['id'] : '',
          //'required'      => false,
          //'placeholder'   => '',
        ));

        //parent::__construct( (array) $args );

      }

    }

  }

}



