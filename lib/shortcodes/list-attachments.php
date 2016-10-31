<?php

/**
 * Shortcode: [list_attachments]
 *
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_List_Attachments_Shortcode' ) ) {

    class Property_List_Attachments_Shortcode extends Shortcode {

      /**
       * init
       */
      public function __construct() {
        $options = array(
          'id' => 'list_attachments',
          'params' => array(
            'type' => array(
              'name' => __( 'Type', ud_get_wp_property()->domain ),
              'description' => __( 'Comma-separated list of file extensions that should be included in the list', ud_get_wp_property()->domain ),
              'type' => 'text',
              'default' => ''
            ),
            'sort_order' => array(
              'name' => __( 'Sort Order', ud_get_wp_property()->domain ),
              'description' => __( 'Indicate whether the list should be sorted in ascending or descending order.', ud_get_wp_property()->domain ),
              'type' => 'select',
              'options' => array(
                'ASC'  => 'ASC',
                'DESC' => 'DESC'
              ),
              'default' => 'ASC'
            ),
            'groupby' => array(
              'name' => __( 'Group By', ud_get_wp_property()->domain ),
              'description' => sprintf( __( 'If you would like the list split into specific groups, you can indicate any of the WordPress post object parameters as the value of this %s. For instance, you can use a common <b>%s</b> for your attachments to organize them into groups. In that case, you would use <b>%s</b> as the <b>%s</b> parameter for this shortcode or widget.', ud_get_wp_property()->domain ), \WPP_F::property_label(), 'description', 'post_content', 'groupby' ),
              'type' => 'text',
              'default' => ''
            ),
            'opening' => array(
              'name' => __( 'Opening List', ud_get_wp_property()->domain ),
              'description' => __( 'Any HTML code you want to appear before the list begins opening - the opening tag(s) for the list.', ud_get_wp_property()->domain ),
              'type' => 'text',
              'default' => '<ul class="attachment-list wpp_attachment_list">'
            ),
            'closing' => array(
              'name' => __( 'CLosing List', ud_get_wp_property()->domain ),
              'description' => __( 'The closing tag(s) for the list.', ud_get_wp_property()->domain ),
              'type' => 'text',
              'default' => '</ul>'
            ),
            'before_item' => array(
              'name' => __( 'Before Item', ud_get_wp_property()->domain ),
              'description' => __( 'The opening tag(s) for each item of the list.', ud_get_wp_property()->domain ),
              'type' => 'text',
              'default' => '<li>'
            ),
            'after_item' => array(
              'name' => __( 'After Item', ud_get_wp_property()->domain ),
              'description' => __( 'The closing tag(s) for each item of the list.', ud_get_wp_property()->domain ),
              'type' => 'text',
              'default' => '</li>',
            ),
          ),
          'description' => sprintf( __( 'Used to display attachments of a %s, can also be used in a post. Ported over from List Attachments Shortcode plugin.  If plugin exists, the WP-Property version of shortcode is not loaded.', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
          'group' => 'WP-Property'
        );

        parent::__construct( $options );
      }

      /**
       * @param string $atts
       * @return string|void
       */
      public function call( $atts = "" ) {
        global $post, $wp_query;

        $r = '';

        $atts = wp_parse_args( $atts, array(
          'type' => NULL,
          'orderby' => NULL,
          'groupby' => NULL,
          'order' => NULL,
          'post_id' => false,
          'before_list' => '',
          'after_list' => '',
          'opening' => '<ul class="attachment-list wpp_attachment_list">',
          'closing' => '</ul>',
          'before_item' => '<li>',
          'after_item' => '</li>',
          'show_descriptions' => true,
          'include_icon_classes' => true,
          'showsize' => false
        ) );

        if ( isset( $atts[ 'post_id' ] ) && is_numeric( $atts[ 'post_id' ] ) ) {
          $post = get_post( $atts[ 'post_id' ] );
        }

        if ( !$post ) {
          return;
        }

        if ( !empty( $atts[ 'type' ] ) ) {
          $types = explode( ',', str_replace( ' ', '', $atts[ 'type' ] ) );
        } else {
          $types = array();
        }

        $showsize = ( $atts[ 'showsize' ] == true || $atts[ 'showsize' ] == 'true' || $atts[ 'showsize' ] == 1 ) ? true : false;
        $upload_dir = wp_upload_dir();

        $op = clone $post;
        $oq = clone $wp_query;

        foreach ( array( 'before_list', 'after_list', 'opening', 'closing', 'before_item', 'after_item' ) as $htmlItem ) {
          $atts[ $htmlItem ] = str_replace( array( '&lt;', '&gt;', '&quot;' ), array( '<', '>', '"' ), $atts[ $htmlItem ] );
        }

        $args = array(
          'post_type' => 'attachment',
          'numberposts' => -1,
          'post_status' => null,
          'post_parent' => $post->ID,
        );

        if ( !empty( $atts[ 'orderby' ] ) ) {
          $args[ 'orderby' ] = $atts[ 'orderby' ];
        }
        if ( !empty( $atts[ 'order' ] ) ) {
          $atts[ 'order' ] = ( in_array( $atts[ 'order' ], array( 'a', 'asc', 'ascending' ) ) ) ? 'asc' : 'desc';
          $args[ 'order' ] = $atts[ 'order' ];
        }
        if ( !empty( $atts[ 'groupby' ] ) ) {
          $args[ 'orderby' ] = $atts[ 'groupby' ];
        }

        $attachments = get_posts( $args );

        if ( $attachments ) {
          $grouper = $atts[ 'groupby' ];
          $test = $attachments;
          $test = array_shift( $test );
          if ( !property_exists( $test, $grouper ) ) {
            $grouper = 'post_' . $grouper;
          }

          $attlist = array();

          foreach ( $attachments as $att ) {
            $key = ( !empty( $atts[ 'groupby' ] ) ) ? $att->$grouper : $att->ID;
            $key .= ( !empty( $atts[ 'orderby' ] ) ) ? $att->$atts[ 'orderby' ] : '';

            $attlink = wp_get_attachment_url( $att->ID );

            if ( count( $types ) ) {
              foreach ( $types as $t ) {
                if ( substr( $attlink, ( 0 - strlen( '.' . $t ) ) ) == '.' . $t ) {
                  $attlist[ $key ] = clone $att;
                  $attlist[ $key ]->attlink = $attlink;
                }
              }
            } else {
              $attlist[ $key ] = clone $att;
              $attlist[ $key ]->attlink = $attlink;
            }
          }
          if ( $atts[ 'groupby' ] ) {
            if ( $atts[ 'order' ] == 'asc' ) {
              ksort( $attlist );
            } else {
              krsort( $attlist );
            }
          }
        }

        if ( count( $attlist ) ) {
          $open = false;
          $r = $atts[ 'before_list' ] . $atts[ 'opening' ];
          foreach ( $attlist as $att ) {

            $container_classes = array( 'attachment_container' );

            //** Determine class to display for this file type */
            if ( $atts[ 'include_icon_classes' ] ) {

              switch ( $att->post_mime_type ) {

                case 'application/zip':
                  $class = 'zip';
                  break;

                case 'vnd.ms-excel':
                  $class = 'excel';
                  break;

                case 'image/jpeg':
                case 'image/png':
                case 'image/gif':
                case 'image/bmp':
                  $class = 'image';
                  break;

                default:
                  $class = 'default';
                  break;
              }
            }

            $icon_class = ( $class ? 'wpp_attachment_icon file-' . $class : false );

            //** Determine if description shuold be displayed, and if it is not empty */
            $echo_description = ( $atts[ 'show_descriptions' ] && !empty( $att->post_content ) ? ' <span class="attachment_description"> ' . $att->post_content . ' </span> ' : false );

            $echo_title = ( $att->post_excerpt ? $att->post_excerpt : __( 'View ', ud_get_wp_property()->domain ) . apply_filters( 'the_title_attribute', $att->post_title ) );

            if ( $icon_class ) {
              $container_classes[ ] = 'has_icon';
            }

            if ( !empty( $echo_description ) ) {
              $container_classes[ ] = 'has_description';
            }

            //** Add conditional classes if class is not already passed into container */
            if ( !strpos( $atts[ 'before_item' ], 'class' ) ) {
              $this_before_item = str_replace( '>', ' class="' . implode( ' ', $container_classes ) . '">', $atts[ 'before_item' ] );
            }

            $echo_size = ( ( $showsize ) ? ' <span class="attachment-size">' . WPP_F::get_filesize( str_replace( $upload_dir[ 'baseurl' ], $upload_dir[ 'basedir' ], $attlink ) ) . '</span>' : '' );

            if( !isset( $current_group ) ) $current_group = false;
            if ( !empty( $atts[ 'groupby' ] ) && $current_group != $att->$grouper ) {
              if ( $open ) {
                $r .= $atts[ 'closing' ] . $atts[ 'after_item' ];
                $open = false;
              }
              $r .= $atts[ 'before_item' ] . '<h3>' . $att->$grouper . '</h3>' . $atts[ 'opening' ];
              $open = true;
              $current_group = $att->$grouper;
            }
            $attlink = $att->attlink;
            $r .= $this_before_item . '<a href="' . $attlink . '" title="' . $echo_title . '" class="wpp_attachment ' . $icon_class . '">' . apply_filters( 'the_title', $att->post_title ) . '</a>' . $echo_size . $echo_description . $atts[ 'after_item' ];
          }
          if ( $open ) {
            $r .= $atts[ 'closing' ] . $atts[ 'after_item' ];
          }
          $r .= $atts[ 'closing' ] . $atts[ 'after_list' ];
        }

        $wp_query = clone $oq;
        $post = clone $op;

        return $r;

      }

    }

    /**
     * Load list-attachments shortcode
     * if the List Attachments Shortcode plugin does not exist
     */
    global $shortcode_tags;
    $shortcodes = array_keys( (array) $shortcode_tags );
    if ( !in_array( 'list-attachments', $shortcodes ) ) {
      new Property_List_Attachments_Shortcode();
    };

  }

}