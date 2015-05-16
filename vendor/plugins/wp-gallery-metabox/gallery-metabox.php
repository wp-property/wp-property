<?php
/*
Plugin Name: Gallery Metabox
Plugin URI: http://wordpress.org/extend/plugins/gallery-metabox/
Description: Displays all the post's attached images on the Edit screen
Author: Bill Erickson
Version: 1.5.1
Author URI: http://www.billerickson.net
*/

if( !class_exists( 'BE_Gallery_Metabox' ) ) {
  class BE_Gallery_Metabox {

    /**
     * This is our constructor
     *
     * @return BE_Gallery_Metabox
     */
    public function __construct() {

      add_action( 'init', array( $this, 'translations' ) );
      add_action( 'add_meta_boxes', array( $this, 'admin_scripts' ), 5 );
      add_action( 'add_meta_boxes', array( $this, 'metabox_add' ) );
      add_action( 'wp_ajax_refresh_metabox', array( $this, 'refresh_metabox' ) );
      add_action( 'wp_ajax_gallery_remove', array( $this, 'gallery_remove' ) );
    }

    /**
     * Translations
     * @since 1.0
     *
     * @author Bill Erickson
     */

    public function translations() {
      load_plugin_textdomain( 'gallery-metabox', false, basename( dirname( __FILE__ ) ) . '/lib/languages' );
    }

    /**
     * AJAX scripts to load on call
     * @since 1.5
     *
     * @author Bill Erickson
     */

    public function admin_scripts() {

      wp_register_script( 'gallery-metabox-ajax', plugins_url( '/lib/js/gallery-metabox-ajax.js', __FILE__ ), array( 'jquery' ), null, true );
      wp_register_style( 'gallery-metabox-style', plugins_url( '/lib/css/gallery-metabox-style.css', __FILE__ ), array(), null, 'all' );

    }

    /**
     * Add the Metabox
     * @since 1.0
     *
     * @author Bill Erickson
     */
    public function metabox_add() {
      // Filterable metabox settings.
      $post_types = apply_filters( 'be_gallery_metabox_post_types', array( 'post', 'page' ) );
      $context = apply_filters( 'be_gallery_metabox_context', 'normal' );
      $priority = apply_filters( 'be_gallery_metabox_priority', 'high' );

      // Loop through all post types
      foreach( $post_types as $post_type ) {

        // Get post ID
        if( isset( $_GET[ 'post' ] ) ) $post_id = $_GET[ 'post' ];
        elseif( isset( $_POST[ 'post_ID' ] ) ) $post_id = $_POST[ 'post_ID' ];
        if( !isset( $post_id ) ) $post_id = false;

        // Granular filter so you can limit it to single page or page template
        if( apply_filters( 'be_gallery_metabox_limit', true, $post_id ) ) {
          // Add Metabox
          add_meta_box( 'be_gallery_metabox', __( 'Gallery Images', 'gallery-metabox' ), array( $this, 'gallery_metabox' ), $post_type, $context, $priority );
          // Add Necessary Scripts and Styles
          wp_enqueue_script( 'thickbox' );
          wp_enqueue_style( 'thickbox' );
          wp_enqueue_script( 'gallery-metabox-ajax' );
          wp_enqueue_style( 'gallery-metabox-style' );
        }

      }
    }

    /**
     * Build the Metabox
     * @since 1.0
     *
     * @param object $post
     *
     * @author Bill Erickson
     */

    public function gallery_metabox( $post ) {

      $original_post = $post;
      echo $this->gallery_metabox_html( $post->ID );
      $post = $original_post;
    }

    /**
     * Image array for gallery metabox
     * @since 1.3
     *
     * @param int $post_id
     * @return string html output
     *
     * @author Bill Erickson
     */
    public function gallery_images( $post_id ) {

      $args = array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_parent' => $post_id,
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'order' => 'ASC',
        'orderby' => 'menu_order',
      );

      $args = apply_filters( 'be_gallery_metabox_args', $args );

      $images = get_posts( $args );

      return $images;

    }

    /**
     * Display setup for images, which include filters and AJAX return
     * @since 1.3
     *
     * @param int $post_id
     * @return string html output
     *
     * @author Bill Erickson
     */
    public function gallery_display( $loop ) {

      $gallery = '<div class="be-image-wrapper">';
      foreach( $loop as $image ):

        $thumbnail = wp_get_attachment_image_src( $image->ID, apply_filters( 'be_gallery_metabox_image_size', 'thumbnail' ) );

        $gallery .= apply_filters( 'be_gallery_metabox_output', '<img src="' . esc_url( $thumbnail[ 0 ] ) . '" alt="' . esc_attr( $image->post_title ) . '" class="wp-gallery-metabox-item" rel="' . esc_attr( $image->ID ) . '" title="' . esc_attr( $image->post_content ) . '" /> ', $thumbnail[ 0 ], $image );
        // removal button
        $gallery .= apply_filters( 'be_gallery_metabox_remove', '<span class="be-image-remove" rel="' . $image->ID . '"><img src="' . plugins_url( '/lib/img/cross-circle.png', __FILE__ ) . '" alt="Remove Image" title="Remove Image"></span>' );

      endforeach;

      $gallery .= '</div>';

      return $gallery;

    }

    /**
     * Gallery Metabox HTML
     * @since 1.3
     *
     * @param int $post_id
     * @return string html output
     *
     * @author Bill Erickson
     */
    public function gallery_metabox_html( $post_id ) {

      $return = '';

      $intro = '<p class="be-metabox-links">';
      $intro .= '<a href="media-upload.php?post_id=' . $post_id . '&amp;type=image&amp;TB_iframe=1&amp;width=640&amp;height=715" id="add_image" class="be-button thickbox button-secondary" title="' . __( 'Add Image', 'gallery-metabox' ) . '">' . __( 'Upload Images', 'gallery-metabox' ) . '</a>';
      $intro .= '<a href="media-upload.php?post_id=' . $post_id . '&amp;type=image&amp;tab=gallery&amp;TB_iframe=1&amp;width=640&amp;height=715" id="manage_gallery" class="thickbox be-button button-secondary" title="' . __( 'Manage Gallery', 'gallery-metabox' ) . '">' . __( 'Manage Gallery', 'gallery-metabox' ) . '</a>';
      $intro .= '<input id="update-gallery" class="be-button button-secondary" type="button" value="' . __( 'Update Gallery', 'gallery-metabox' ) . '" name="update-gallery"></p>';

      $return .= apply_filters( 'be_gallery_metabox_intro', $intro );

      $loop = $this->gallery_images( $post_id );

      if( empty( $loop ) )
        $return .= '<p>' . __( 'No images', 'gallery-metabox' ) . '.</p>';

      $gallery = $this->gallery_display( $loop );

      $return .= $gallery;

      return $return;
    }

    /**
     * Gallery Metabox AJAX Update
     * @since 1.5
     *
     * This function will refresh image gallery on AJAX call.
     *
     *
     * @author Andrew Norcross
     *
     */

    public function refresh_metabox() {

      $parent = $_POST[ 'parent' ];
      $loop = $this->gallery_images( $parent );
      $images = $this->gallery_display( $loop );

      $ret = array();

      if( !empty( $parent ) ) {
        $ret[ 'success' ] = true;
        $ret[ 'gallery' ] = $images;
      } else {
        $ret[ 'success' ] = false;
      }

      echo json_encode( $ret );
      die();
    }

    /**
     * Gallery image removal
     * @since 1.5
     *
     * This function will remove the image from the gallery by setting
     * the post_parent to 0
     *
     * @author Andrew Norcross
     *
     */

    public function gallery_remove() {

      // content from AJAX post
      $image = $_POST[ 'image' ];
      $parent = $_POST[ 'parent' ];

      // no image ID came through, so bail
      if( empty( $image ) ) {
        $ret[ 'success' ] = false;
        echo json_encode( $ret );
        die();
      }

      // removal function
      $remove = array();
      $remove[ 'ID' ] = $image;
      $remove[ 'post_parent' ] = 0;

      $update = wp_update_post( $remove );

      // AJAX return array
      $ret = array();

      if( $update !== 0 ) {

        // loop to refresh the gallery
        $loop = $this->gallery_images( $parent );
        $images = $this->gallery_display( $loop );
        // return values
        $ret[ 'success' ] = true;
        $ret[ 'gallery' ] = $images;

      } else {
        // failure return. can probably make more verbose
        $ret[ 'success' ] = false;

      }

      echo json_encode( $ret );
      die();
    }

  }

// Instantiate our class
  $BE_Gallery_Metabox = new BE_Gallery_Metabox();

}

