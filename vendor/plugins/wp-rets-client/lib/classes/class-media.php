<?php
/**
 * Bootstrap
 *
 * @since 4.0.0
 */
namespace UsabilityDynamics\WPRETSC {

  if( !class_exists( 'UsabilityDynamics\WPRETSC\Media' ) ) {

    final class Media {

      /**
       * Constructor
       *
       */
      public function __construct() {

        /**
         * Filters in order to make remote images to work
         */
        add_filter( 'image_downsize', function ( $false, $id, $size ) {

          if( get_post_meta( $id, '_is_remote', 1 ) ) {
            return array( $this->fix_rets_image_url( $id, $size ) );
          }

          return $false;

        }, 10, 3 );

        /**
         * Filters in order to make remote images to work
         */
        add_filter( 'wp_get_attachment_url', function ( $url, $post_id ) {

          if( get_post_meta( $post_id, '_is_remote', 1 ) ) {
            return $this->fix_rets_image_url( $post_id );
          }

          return $url;
        }, 10, 2 );

        /**
         *
         */
        add_filter( 'wp_get_attachment_image_src', function ( $image, $attachment_id, $size, $icon ) {

          if( get_post_meta( $attachment_id, '_is_remote', 1 ) ) {

            // get available image sizes
            $_image_sizes = \UsabilityDynamics\Utility::all_image_sizes();

            if( isset( $size ) && is_array( $size ) ) {
              return array( $this->fix_rets_image_url( $attachment_id ), $size[ 0 ], $size[ 1 ] );
            }

            if( $size && !isset( $_image_sizes[ $size ] ) ) {
              $size = 'full';
            }

            // add "full" and "medium_large" sizes which are now standard
            if( !isset( $_image_sizes[ 'full' ] ) ) {
              $_image_sizes[ 'full' ][ 'width' ] = get_option( 'large_size_w' );
              $_image_sizes[ 'full' ][ 'height' ] = get_option( 'large_size_h' );
            }

            if( !isset( $_image_sizes[ 'medium_large' ] ) ) {
              $_image_sizes[ 'medium_large' ][ 'width' ] = get_option( 'medium_large_size_w' );
              $_image_sizes[ 'medium_large' ][ 'height' ] = get_option( 'medium_large_size_h' );
            }

            // return expected array of url, width, height

            if( isset( $_image_sizes[ $size ] ) && isset( $_image_sizes[ $size ][ 'width' ] ) && isset( $_image_sizes[ $size ][ 'height' ] ) ) {
              return array(
                $this->fix_rets_image_url( $attachment_id, $size ), $_image_sizes[ $size ][ 'width' ], $_image_sizes[ $size ][ 'height' ]
              );
            }

            return array(
              $this->fix_rets_image_url( $attachment_id, $size )
            );

          }

          return $image;
        }, 10, 4 );

        /**
         * Added filter for rebuild broken(several url in one string) links in srcset
         */
        add_filter ('wp_calculate_image_srcset', function($sources) {
          foreach ($sources as &$source) {
            $source['url'] = substr_count($source['url'], 'http') > 1 ? substr($source['url'], strrpos($source['url'], 'http')) : $source['url'];
          }
          return $sources;
        }, 10, 1);

        /**
         * Filters in order to make remote images to work
         */
        add_filter( 'wp_prepare_attachment_for_js', function ( $response, $attachment, $meta ) {

          $size_array = get_intermediate_image_sizes();

          $response[ 'sizes' ] = array();

          foreach( $size_array as $size ) {

            $attachment_url = wp_get_attachment_url( $attachment->ID );

            $response[ 'sizes' ][ $size ] = array(
              'height' => 'auto',
              'width' => 'auto',
              'url' => $attachment_url,
              'orientation' => 'landscape'
            );

          }

          $response[ 'sizes' ][ 'full' ] = array(
            'height' => 'auto',
            'width' => 'auto',
            'url' => $attachment_url,
            'orientation' => 'landscape'
          );

          return $response;
        }, 10, 3 );

        /**
         *
         */
        add_filter( 'wp_get_attachment_metadata', function ( $data, $post_id ) {
          global $_wp_additional_image_sizes;

          //die( '<pre>' . print_r( $_wp_additional_image_sizes, true ) . '</pre>' );
          // already have data, do nothing
          if( !empty( $data ) ) {
            return $data;
          }

          // check if this is one of our "remote files", if not, do nothing
          if( !get_post_meta( $post_id, '_is_remote', 1 ) ) {
            return $data;
          }

          $_wp_attached_file = get_post_meta( $post_id, '_wp_attached_file', 1 );
          // get URL of attached file
          //_wp_attached_file
          $_intermediate_image_sizes = get_intermediate_image_sizes();

          $data = array(
            'width' => get_option( 'large_size_w' ),
            'height' => get_option( 'large_size_h' ),
            'file' => $_wp_attached_file,
            'sizes' => array(
              'thumbnail' => array(
                'file' => $_wp_attached_file,
                'width' => get_option( 'thumbnail_size_w' ),
                'height' => get_option( 'thumbnail_size_h' ),
              ),
              'medium' => array(
                'file' => $_wp_attached_file,
                'width' => get_option( 'medium_size_w' ),
                'height' => get_option( 'medium_size_h' ),
              ),
              'large' => array(
                'file' => $_wp_attached_file,
                'width' => get_option( 'large_size_w' ),
                'height' => get_option( 'large_size_h' ),
              ),
              'medium_large' => array(
                'file' => $_wp_attached_file,
                'width' => get_option( 'medium_large_size_w' ),
                'height' => get_option( 'medium_large_size_h' ),
              ),
            ),
            'image_meta' => array(
              'aperture' => '0',
              'credit' => '',
              'camera' => '',
              'caption' => '',
              'created_timestamp' => '0',
              'copyright' => '',
              'focal_length' => '0',
              'iso' => '0',
              'shutter_speed' => '0',
              'title' => '',
              'orientation' => '0',
              'keywords' => array()
            )
          );

          // add our intermediate image sizes
          foreach( $_wp_additional_image_sizes as $_size_name => $_size_detail ) {
            $data[ 'sizes' ][ $_size_name ] = array(
              'file' => $_wp_attached_file,
              'width' => $_size_detail[ 'width' ],
              'height' => $_size_detail[ 'height' ],
            );

          }

          //die( '<pre>' . print_r( $_intermediate_image_sizes, true ) . '</pre>' );
          //die( '<pre>' . print_r( $post_id, true ) . '</pre>' );
          return $data;

        }, 10, 2 );

        /**
         *
         */
        add_filter( 'max_srcset_image_width', create_function( '', 'return 1;' ) );

        /**
         * Take care about removing all property attachments
         */
        add_action( 'before_delete_post', array( $this, 'delete_post_attachments' ) );

      }

      /**
       * Take care about removing all property attachments
       *
       */
      function delete_post_attachments($post_id){

        ud_get_wp_rets_client()->write_log( "Removing all attachments for post [$post_id]", "debug" );

        if( get_post_type( $post_id ) !== 'property' ) {
          return;
        }

        $media = get_children( array(
          'post_parent' => $post_id,
          'post_type'   => 'attachment'
        ) );

        if( empty( $media ) ) {
          return;
        }

        foreach( $media as $file ) {
          wp_delete_attachment( $file->ID, true );
        }

      }

      /**
       * @param $id
       * @param bool|false $size
       * @return mixed|string
       */
      public function fix_rets_image_url( $id, $size = false ) {

        // get available image sizes
        $_image_sizes = \UsabilityDynamics\Utility::all_image_sizes();

        // get image url of remote asset
        $_url = get_post_meta( $id, '_wp_attached_file', true );

        if( is_array( $size ) ) {
          $_extension = pathinfo( $_url, PATHINFO_EXTENSION );
          if( empty( $_extension ) ) {
            $_url .= '-' . $size[ 0 ] . 'x' . $size[ 1 ];
          } else {
            $_url = str_replace( '.' . $_extension, '-' . $size[ 0 ] . 'x' . $size[ 1 ] . '.' . $_extension, $_url );
          }
          return $_url;
        }

        //die('$size'.$size);
        // if the size exists in image sizes, append the image-size spedific annex to url
        if( $size && array_key_exists( $size, $_image_sizes ) ) {
          $_extension = pathinfo( $_url, PATHINFO_EXTENSION );
          if( empty( $_extension ) ) {
            $_url .= '-' . $_image_sizes[ $size ][ 'width' ] . 'x' . $_image_sizes[ $size ][ 'height' ];
          } else {

            if( isset( $_image_sizes[ $size ] ) && isset( $_image_sizes[ $size ]['height'] ) && isset( $_image_sizes[ $size ]['width'] ) ) {
              $_url = str_replace( '.' . $_extension, '-' . $_image_sizes[ $size ][ 'width' ] . 'x' . $_image_sizes[ $size ][ 'height' ] . '.' . $_extension, $_url );
            } else {
              //$_url = str_replace( '.' . $_extension, '.' . $_extension, $_url );
            }

          }
        }

        // return finished url
        return $_url;

      }

    }

  }

}
