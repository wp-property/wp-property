<?php
/**
 * Property List Table
 *
 */
if( !class_exists( 'WPP_Object_List_Table' ) ) {

  class WPP_Object_List_Table extends WPP_List_Table {

    /**
     * @param string $args
     */
    function __construct( $args = '' ) {
      $args = wp_parse_args( $args, array(
        'plural' => '',
        'iColumns' => 3,
        'per_page' => 20,
        'iDisplayStart' => 0,
        'ajax_action' => 'wpp_ajax_list_table',
        'current_screen' => '',
        'table_scope' => 'wpp_overview',
        'singular' => '',
        'ajax' => false
      ) );

      parent::__construct( $args );
    }

    /**
     * Get a list of sortable columns.
     *
     * @since 3.1.0
     * @access protected
     *
     * @return array
     */
    function get_sortable_columns() {
      global $wp_properties;

      return array();
    }

    /**
     * Set Bulk Actions
     *
     * @since 3.1.0
     *
     * @return array
     */
    public function get_bulk_actions() {
      $actions = array();

      if ( current_user_can( 'delete_wpp_property' ) ) {
        $actions[ 'untrash' ] = __( 'Restore' );
        $actions[ 'delete' ] = __( 'Delete' );
      }

      $actions = apply_filters( 'wpp::all_properties::bulk_actions', $actions );

      return $actions;
    }

    /**
     * Generate HTML for a single row on the users.php admin panel.
     *
     */
    function single_row( $ID ) {
      global $post, $wp_properties;

      $ID = (int) $ID;

      $post = WPP_F::get_property( $ID );

      $post = (object) $post;

      $title = _draft_or_post_title( $post->ID );
      $post_type_object = get_post_type_object( $post->post_type );
      $can_edit_post = current_user_can( $post_type_object->cap->edit_post );
      $can_edit_post = apply_filters( 'wpp_list_table_can_edit_post', $can_edit_post );

      $can_delete_post = current_user_can( $post_type_object->cap->delete_post, $post->ID );
      $can_delete_post = apply_filters( 'wpp_list_table_can_delete_post', $can_delete_post );

      $result = "<tr id='object-{$ID}' class='wpp_parent_element'>";

      list( $columns, $hidden ) = $this->get_column_info();
      $ajax_cells = array();
      foreach ( $columns as $column => $column_display_name ) {

        $class = "class=\"$column column-$column\"";
        $style = '';

        if ( in_array( $column, $hidden ) ) {
          $style = ' style="display:none;"';
        }

        $attributes = "$class$style";

        $result .= "<td {$attributes}>";

        $r = "";

        switch ( $column ) {

          //** Adds ability to customize any column we want. peshkov@UD */
          case ( apply_filters( "wpp::single_row::{$column}", false, $post ) ):
            $r .= apply_filters( "wpp::single_row::{$column}::render", '', $post );
          break;

          case 'cb':
            if ( $can_edit_post ) {
              $r .= '<input type="checkbox" name="post[]" value="' . get_the_ID() . '"/>';
            } else {
              $r .= '&nbsp;';
            }
            break;

          case 'title':
            $attributes = 'class="post-title page-title column-title"' . $style;
            if ( $can_edit_post && $post->post_status != 'trash' && $post->post_status != 'archived' ) {
              $r .= '<a class="row-title" href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ) . '">' . $title . '</a>';
            } else {
              $r .= $title;
            }
            $r .= ( isset( $parent_name ) ? ' | ' . $post_type_object->labels->parent_item_colon . ' ' . esc_html( $parent_name ) : '' );

            $actions = array();
            if ( $can_edit_post && 'trash' != $post->post_status && 'archived' != $post->post_status ) {
              $actions[ 'edit' ] = '<a href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr( __( 'Edit this item' ) ) . '">' . __( 'Edit' ) . '</a>';
            }

            if ( $can_delete_post ) {
              if ( 'trash' == $post->post_status ) {
                global $wp_version;
                $_wpnonce = ( version_compare( $wp_version, '3.5', '>=' ) ? 'untrash-post_' : 'untrash-' . $post->post_type . '_' ) . $post->ID;
                $actions[ 'untrash' ] = "<a title='" . esc_attr( __( 'Restore this item from the Trash' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), $_wpnonce ) . "'>" . __( 'Restore' ) . "</a>";
              } elseif ( EMPTY_TRASH_DAYS && 'pending' != $post->post_status ) {
                $actions[ 'trash' ] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash' ) . "</a>";
              }

              if ( 'trash' == $post->post_status || !EMPTY_TRASH_DAYS ) {
                $actions[ 'delete' ] = "<a class='submitdelete permanently' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently' ) . "</a>";
              }
            }

            if ( 'trash' != $post->post_status && 'archived' != $post->post_status ) {
              $actions[ 'view' ] = '<a target="_blank" href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">' . __( 'View' ) . '</a>';
            }

            $actions = apply_filters( is_post_type_hierarchical( $post->post_type ) ? 'page_row_actions' : 'post_row_actions', $actions, $post );
            $r .= $this->row_actions( $actions );
            break;

          case 'property_type':
            $property_type = $post->property_type;
            $r .= isset( $wp_properties[ 'property_types' ][ $property_type ] ) ? $wp_properties[ 'property_types' ][ $property_type ] : $property_type;
          break;

          case 'overview':

            $overview_stats = $wp_properties[ 'property_stats' ];

            unset( $overview_stats[ 'phone_number' ] );

            $stat_count = 0;
            $hidden_count = 0;

            $display_stats = array();
            foreach($overview_stats as $stat => $label) {

              $values = isset( $post->$stat ) ? $post->$stat : null;

              if ( !is_array( $values ) ) {
                $values = array( $values );
              }

              foreach ( $values as $value ) {

                $print_values = array();

                if ( empty( $value ) || strlen( $value ) > 15 ) {
                  continue;
                }

                $print_values[ ] = apply_filters( "wpp_stat_filter_{$stat}", $value );

                $print_values = implode( '<br />', $print_values );

                $stat_count++;
                $stat_row_class = '';
                if($stat_count > 5) {
                  $stat_row_class = 'hidden wpp_overview_hidden_stats';
                  $hidden_count++;
                }

                $display_stats[ $stat ] = '<li class="' . $stat_row_class . '"><span class="wpp_label">' . $label . ':</span> <span class="wpp_value">' . $print_values . '</span></li>';

              }

            }

            if ( is_array( $display_stats ) && count( $display_stats ) > 0 ) {

              if ( $stat_count > 5 ) {
                $display_stats[ 'toggle_advanced' ] = '<li class="wpp_show_advanced" advanced_option_class="wpp_overview_hidden_stats">' . sprintf( __( 'Toggle %1s more.', 'wpp' ), $hidden_count ) . '</li>';
              }

              $r .= '<ul class="wpp_overview_column_stats wpp_something_advanced_wrapper">' . implode( '', $display_stats ) . '</ul>';
            }

            break;

          case 'features':
            $features = get_the_terms( $post->ID, "property_feature" );
            $features_html = array();

            if ( $features && !is_wp_error( $features ) ) {
              foreach ( $features as $feature ) {

                $feature_link = get_term_link( $feature, "property_feature" );

                //** If for some reason get_term_link() returns a WP error object, we avoid using it in URL */
                if ( is_wp_error( $feature_link ) ) {
                  continue;
                }

                array_push( $features_html, '<a href="' . $feature_link . '">' . $feature->name . '</a>' );
              }

              $r .= implode( $features_html, ", " );
            }

            break;

          case 'thumbnail':

            if ( isset( $post->featured_image ) && $post->featured_image ) {

              $overview_thumb_type = $wp_properties[ 'configuration' ][ 'admin_ui' ][ 'overview_table_thumbnail_size' ];

              if ( empty( $overview_thumb_type ) ) {
                $overview_thumb_type = 'thumbnail';
              }

              $image_thumb_obj = wpp_get_image_link( $post->featured_image, $overview_thumb_type, array( 'return' => 'array' ) );

            }

            if ( !empty( $image_thumb_obj ) ) {
              $r .= '<a href="' . $post->images[ 'large' ] . '" class="fancybox" rel="overview_group" title="' . $post->post_title . '"><img src="' . $image_thumb_obj[ 'url' ] . '" width="' . $image_thumb_obj[ 'width' ] . '" height="' . $image_thumb_obj[ 'height' ] . '" /></a>';
            } else {
              $r .= " - ";
            }

            break;

          case 'featured':

            if ( current_user_can( 'manage_options' ) ) {
              if ( isset( $post->featured ) && $post->featured )
                $r .= "<input type='button' id='wpp_feature_{$post->ID}' class='wpp_featured_toggle wpp_is_featured' nonce='" . wp_create_nonce( 'wpp_make_featured_' . $post->ID ) . "' value='" . __( 'Featured', 'wpp' ) . "' />";
              else
                $r .= "<input type='button' id='wpp_feature_{$post->ID}' class='wpp_featured_toggle' nonce='" . wp_create_nonce( 'wpp_make_featured_' . $post->ID ) . "'  value='" . __( 'Add to Featured', 'wpp' ) . "' />";
            } else {

              if ( isset( $post->featured ) && $post->featured )
                $r .= __( 'Featured', 'wpp' );
              else
                $r .= "";

            }

            break;

          default:

            $print_values = array();

            $value = $post->{$column};

            if ( !is_array( $value ) ) {
              $value = array( $value );
            }

            foreach ( $value as $single_value ) {
              $print_values[ ] = apply_filters( "wpp_attribute_filter", $single_value, $column );
            }

            $print_values = implode( '<br />', $print_values );

            $r .= $print_values;

            break;

        }

        $ajax_cells[ ] = $r;

        $result .= $r;
        $result .= "</td>";
      }

      $result .= '</tr>';

      if ( $this->_args[ 'ajax' ] ) {
        return $ajax_cells;
      }

      return $result;
    }

  }

}