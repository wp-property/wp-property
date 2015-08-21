<?php
/**
 * Advanced AJAX List Table class.
 *
 */
namespace UsabilityDynamics\WPLT {

  if( !defined( 'ABSPATH' ) ) {
    die();
  }

  /**
   * Load WP core classes.
   */
  if( !class_exists( '\WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
  }

  if (!class_exists('UsabilityDynamics\WPLT\WP_List_Table')) {

    /** ************************ CREATE A PACKAGE CLASS ****************************
     *
     * Create a new list table package that extends the core WP_List_Table class.
     * WP_List_Table contains most of the framework for generating the table, but we
     * need to define and override some methods so that our data can be displayed
     * exactly the way we need it to be.
     *
     * To display this example on a page, you will first need to instantiate the class,
     * then call $yourInstance->prepare_items() to handle any data manipulation, then
     * finally call $yourInstance->display() to render the table to the page.
     *
     * Our theme for this list table is going to be movies.
     */
    class WP_List_Table extends \WP_List_Table {

      /**
       * Additional properties are stored here.
       * It is using __get and __set methods
       */
      private $properties;

      /**
       * @var
       */
      public $_column_headers;

      /**
       * Cached bulk actions
       *
       * @access private
       */
      private $_actions;

      /**
       * Notices
       */
      public $message;
      public $warning;
      public $error;

      public $options = array();

      /**
       * REQUIRED. Set up a constructor that references the parent constructor. We
       * use the parent reference to set some default configs.
       * @param array $args
       */
      public function __construct( $args = array() ) {

        if( function_exists( 'get_current_screen' ) ) {
          $screen = get_current_screen();
        }

        //Set parent defaults
        parent::__construct( $args = wp_parse_args( $args, array(
          //singular name of the listed records
          'singular'	=> '',
          //plural name of the listed records
          'plural'	=> '',
          //does this table support ajax?
          'ajax'		=> true,
          // Per Page
          'per_page' => 20,
          // Post Type and Screen
          'post_type' => ( isset($screen) && is_object($screen) ? $screen->id : false ),
          'screen' => ( isset($screen) && is_object($screen) ? $screen->id : false ),
          'post_status' => 'any',
          // Pagination
          'paged' => 1,
          // Order By
          'orderby' => 'menu_order title',
          'order' => 'asc',
          // Specific Options
          'options' => array(),
          // HTML attributes
          'name' => false,
          // Extra parameters. Use it to provide any additional data.
          'extra' => array(),
          // WP_Query arguments
          'query' => array(),
          // AJAX Specific
          'query2' => array(),
        ) ) );

        foreach( $args as $k => $v ) {
          // May be setup value from Request
          if( isset( $_REQUEST[ $k ] ) ) {
            $args[ $k ] = $_REQUEST[ $k ];
          }
          switch( $k ) {
            case 'screen':
              // Do Nothing!
              break;
            case 'options':
              $this->options = wp_parse_args($args['options'], array(
                'show_filter' => false,
                'show_bulk_actions' => true,
                'show_pagination' => true,
              ));
              break;
            /* Filter Query ( Used by ajax requests ) */
            case 'query2':
              $this->query2 = $this->parse_query( $v );
              break;
            /* Per Page */
            case 'per_page':
              /** This filter is documented in wp-admin/includes/post.php */
              $this->per_page = apply_filters( 'edit_posts_per_page', $v, isset( $args[ 'post_type' ] ) ? $args[ 'post_type' ] : false );
              break;
            /* Set order params in $_GET (required to have working ordering) */
            case 'order':
            case 'orderby':
              $_GET[$k] = $v;
            default:
              $this->{$k} = $v;
              break;
          }
        }

        if( !$this->name ) {
          $this->name = $this->post_type . '_' . rand( 1001, 9999 );
        }

        wp_enqueue_style( 'list-table-ajax', Utility::path( 'static/styles/wp-list-table.css', 'url' ) );
        wp_enqueue_script( 'list-table-ajax', Utility::path( 'static/scripts/wp-list-table.js', 'url' ), array('jquery') );
        wp_localize_script( 'list-table-ajax', '__wplt', array(
          'spinner_url' => Utility::path( 'static/images/ajax-loader.gif', 'url' ),
        ) );

      }

      /**
       * Recommended. This method is called when the parent class can't find a method
       * specifically build for a given column. Generally, it's recommended to include
       * one method for each column you want to render, keeping your package class
       * neat and organized. For example, if the class needs to process a column
       * named 'title', it would first see if a method named $this->column_title()
       * exists - if it does, that method will be used. If it doesn't, this one will
       * be used. Generally, you should try to use custom column methods as much as
       * possible.
       *
       * Since we have defined a column_title() method later on, this method doesn't
       * need to concern itself with any column with a name of 'title'. Instead, it
       * needs to handle everything else.
       *
       * For more detailed insight into how columns are handled, take a look at
       * WP_List_Table::single_row_columns()
       *
       * @param array $item A singular item (one full row's worth of data)
       * @param array $column_name The name/slug of the column to be processed
       *
       * @return string Text or HTML to be placed inside the column <td>
       */
      public function column_default( $item, $column_name )
      {
        switch ($column_name) {
          default:
            //Show the whole array for troubleshooting purposes
            if (isset($item->{$column_name}) && is_string( $item->{$column_name} )) {
              return $item->{$column_name};
            } else {
              return 'undefined';
            }
        }
      }

      /**
       * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
       * is given special treatment when columns are processed. It ALWAYS needs to
       * have it's own method.
       *
       * @see WP_List_Table::single_row_columns()
       *
       * @param array $item A singular item (one full row's worth of data)
       *
       * @return string Text to be placed inside the column <td> (movie title only)
       */
      public function column_cb( $post ) {
        return sprintf(
          '<input type="checkbox" name="%1$s[]" value="%2$s" />',
          /*$1%s*/ $this->_args['singular'],  	//Let's simply repurpose the table's singular label ("movie")
          /*$2%s*/ $post->ID			//The value of the checkbox should be the record's id
        );
      }

      /**
       * REQUIRED! This method dictates the table's columns and titles. This should
       * return an array where the key is the column slug (and class) and the value
       * is the column's title text. If you need a checkbox for bulk actions, refer
       * to the $columns array below.
       *
       * The 'cb' column is treated differently than the rest. If including a checkbox
       * column in your table you must create a column_cb() method. If you don't need
       * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
       *
       * @see WP_List_Table::single_row_columns()
       *
       * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
       */
      public function get_columns() {
        return $columns = array(
          'cb'		=> '<input type="checkbox" />', //Render a checkbox instead of text
          'post_title'		=> __( 'Title' ),
        );
      }

      /**
       * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
       * you will need to register it here. This should return an array where the
       * key is the column that needs to be sortable, and the value is db column to
       * sort by. Often, the key and value will be the same, but this is not always
       * the case (as the value is a column name from the database, not the list table).
       *
       * This method merely defines which columns should be sortable and makes them
       * clickable - it does not handle the actual sorting. You still need to detect
       * the ORDERBY and ORDER querystring variables within prepare_items() and sort
       * your data accordingly (usually by modifying your query).
       *
       * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
       */
      public function get_sortable_columns() {
        return array(
          'title'	 	=> array( 'title', false ),	//true means it's already sorted
        );
      }

      /**
       * Optional. If you need to include bulk actions in your list table, this is
       * the place to define them. Bulk actions are an associative array in the format
       * 'slug'=>'Visible Title'
       *
       * If this method returns an empty value, no bulk action will be rendered. If
       * you specify any bulk actions, the bulk actions box will be rendered with
       * the table automatically on display().
       *
       * Also note that list tables are not automatically wrapped in <form> elements,
       * so you will need to create those manually in order for bulk actions to function.
       *
       * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
       */
      public function get_bulk_actions() {
        return array();
      }

      /**
       * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
       * For this example package, we will handle it in the class to keep things
       * clean and organized.
       *
       * @see $this->prepare_items()
       */
      public function process_bulk_action() {}

      /**
       * Get the current action selected from the bulk actions dropdown.
       *
       * @access public
       * @return string|bool The action name or False if no action was selected
       */
      public function current_action() {

        if ( isset( $_REQUEST['doaction'] ) && -1 != $_REQUEST['doaction'] )
          return $_REQUEST['doaction'];

        return false;
      }

      /**
       * REQUIRED! This is where you prepare your data for display. This method will
       * usually be used to query the database, sort and filter the data, and generally
       * get it ready to be displayed. At a minimum, we should set $this->items and
       * $this->set_pagination_args(), although the following properties and methods
       * are frequently interacted with here...
       *
       * @uses $this->_column_headers
       * @uses $this->items
       * @uses $this->get_columns()
       * @uses $this->get_sortable_columns()
       * @uses $this->get_pagenum()
       * @uses $this->set_pagination_args()
       */
      public function prepare_items() {

        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        /**
         * REQUIRED. Finally, we build an array to be used by the class for column
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);

        /**
         * Prepare Query
         */
        $query = $this->wp_query();

        /**
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = $query->found_posts;
        $total_pages = $query->max_num_pages;

        /**
         * REQUIRED. Now we can add our query results to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $query->posts;

        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
          //WE have to calculate the total number of items
          'total_items'	=> $total_items,
          //WE have to determine how many items to show on a page
          'per_page'	=> $this->per_page,
          //WE have to calculate the total number of pages
          'total_pages'	=> $total_pages,
          // Set ordering values if needed (useful for AJAX)
          'orderby'	=> $this->orderby,
          'order'		=> $this->order,
        ) );

        wp_reset_query();
      }

      /**
       * Wrapper for get_hidden_columns() function.
       * It determines if 'Checkbox' or 'Primary' column were added to 'hidden columns' list by accident
       * and removes them from the list.
       *
       * @return array
       */
      private function get_hidden_columns() {
        $hidden = !empty( $this->screen ) ? get_hidden_columns( $this->screen ) : array();

        if( !empty( $hidden ) ) {

          $hidden = array_unique( $hidden );

          $primary_column = false;

          if( method_exists( $this, 'get_primary_column_name' ) ) {

            $primary_column = $this->get_primary_column_name();

          } else {

            $columns = $this->get_columns();
            // We need a primary defined so responsive views show something,
            // so let's fall back to the first non-checkbox column.
            foreach( $columns as $col => $column_name ) {
              if ( 'cb' === $col ) {
                continue;
              }
              $primary_column = $col;
              break;
            }

          }

          if( !empty( $primary_column ) ) {
            foreach( $hidden as $k => $v ) {
              if( $v == $primary_column || $v == 'cb' ) {
                unset( $hidden[ $k ] );
              }
            }
          }

        }

        return $hidden;
      }

      /**
       * The method must not be overwritten.
       * Use filter_wp_query() method to add custom arguments for WP_Query
       *
       * @return object WP_Query
       */
      private function wp_query() {

        $args = array_merge( array(
          'post_type' => $this->post_type,
          'post_status' => $this->post_status,
          'paged' => $this->paged,
          'posts_per_page' => $this->per_page,
          'orderby' => $this->orderby,
          'order' => strtoupper( $this->order ),
        ), array_merge_recursive( $this->query, $this->query2 ) );

        /* Prepare Order arguments */

        $predefined_orderby = array(
          'none',
          'ID',
          'author',
          'title',
          'name',
          'type',
          'date',
          'modified',
          'parent',
          'rand',
          'comment_count',
          'menu_order',
          'post__in',
          'meta_value',
        );

        $orderby = explode( ' ', trim( $args['orderby'] ) );
        $args[ 'orderby' ] = array();
        foreach( $orderby as $ob ) {
          $ob = trim($ob);
          if( empty($ob) ) {
            continue;
          }
          if( !in_array( $ob, $predefined_orderby ) ) {
            if( !empty( $args[ 'meta_key' ] ) ) {
              if( $args[ 'meta_key' ] !== $ob ) {
                continue;
              }
            } else {
              $args[ 'meta_key' ] = $ob;
            }
            /**
             * Use hook 'wplt:orderby:is_numeric' in child class
             * to set specific meta key as numeric ( e.g. price ).
             */
            $is_numeric = apply_filters( 'wplt:orderby:is_numeric', false, $ob );
            $key = $is_numeric ? 'meta_value_num' : 'meta_value';
            $args[ 'orderby' ][ $key ] = $args[ 'order' ];
            /**
             * Use hook 'wplt:orderby:meta_type' in child class
             * to set specific meta type:
             * 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'
             */
            $meta_type = apply_filters( 'wplt:orderby:meta_type', false, $ob );
            if( !empty( $meta_type ) ) {
              $args[ 'meta_type' ] = $meta_type;
            }
          } else {
            $args[ 'orderby' ][ $ob ] = $args[ 'order' ];
          }
        }

        /* Use method below to pass extra arguments or modify existing ones. */
        $args = $this->filter_wp_query( $args );

        return new \WP_Query( $args );
      }

      /**
       * Parses Filter Query (query2)
       */
      protected function parse_query( $query ){
        $_query = array();

        if( !empty( $query ) ) {

          /* Merge queries with the same names */
          $_prepared_query = array();
          foreach( $query as $q ) {
            if( isset( $_prepared_query[ $q['name'] ] ) ) {
              if( !is_array($_prepared_query[ $q['name'] ]['value'] ) ) {
                $_prepared_query[ $q['name'] ]['value'] = array($_prepared_query[ $q['name'] ]['value']);
              }
              if( !is_array($q['value'] ) ) {
                $q['value'] = array($q['value']);
              }
              $q['value'] = array_unique( array_merge( $_prepared_query[ $q['name'] ]['value'], $q['value'] ) );
              $_prepared_query[ $q['name'] ] = array_merge( $_prepared_query[ $q['name'] ], $q );
            } else {
              $_prepared_query[ $q['name'] ] = $q;
            }
          }
          $query = $_prepared_query;

          foreach( $query as $q ) {
            if( empty($q['value']) ) {
              continue;
            }
            // It should not happen, but we check it in just case
            if(!isset($q['map']) || !isset($q['name']) || !isset($q['value'])){
              continue;
            }
            $map = json_decode(urldecode($q['map']), true);
            // One more check. It should not happen too, but we check it in just case
            if( !is_array($map) || !isset($map['class']) || !isset($map['type']) || !isset($map['compare']) ) {
              continue;
            }

            switch( $map['class'] ) {
              case 'post':

                $_query[ $q['name'] ] = $q['value'];

                break;

              case 'meta':

                if( !isset( $_query[ 'meta_query' ] ) ) {
                  $_query[ 'meta_query' ] = array();
                }

                if( is_array( $q['value'] ) && !in_array( $map['compare'], array( 'NOT IN', 'IN' ) ) ) {
                  $map['compare'] = 'IN';
                }

                $args = array(
                  'key' => $q['name'],
                  'value' => $q['value'],
                  'compare' => $map['compare'],
                );

                array_push( $_query[ 'meta_query' ], $args );

                break;

              case 'taxonomy':

                if( !isset( $_query[ 'tax_query' ] ) ) {
                  $_query[ 'tax_query' ] = array();
                }

                if( $map[ 'type' ] === 'string' ) {
                  $map[ 'type' ] = 'term_id';
                }

                if( $map[ 'compare' ] === '=' ) {
                  $map[ 'compare' ] = false;
                }

                if( !is_array( $q['value'] ) ) {
                  $q['value'] = array( $q['value'] );
                }

                /**
                 * Determine, which criteria we should use for search: 'AND' or 'OR'
                 */
                if( in_array( $map[ 'compare' ], array( 'NOT IN', 'IN' ) ) ) {

                  $args = array_filter( array(
                    'taxonomy' => $q['name'],
                    'field' => $map['type'],
                    'terms' => array($q['value']),
                    'operator' => $map[ 'compare' ] == 'NOT IN' ? $map[ 'compare' ] : false,
                  ) );

                  array_push( $_query[ 'tax_query' ], $args );

                } else {
                  /**
                   * We add every value separately to search using criteria 'AND' ( not 'OR' ).
                   */
                  $args = array( 'relation' => 'AND' );
                  foreach( $q['value'] as $value ) {
                    $args[] = array(
                      'taxonomy' => $q['name'],
                      'field' => $map['type'],
                      'terms' => array($value),
                    );
                  }

                  array_push( $_query[ 'tax_query' ], $args );
                }

                break;

              case 'date_query':

                if( !isset( $_query[ 'date_query' ] ) ) {
                  $_query[ 'date_query' ] = array();
                }

                if ( !empty( $q['value'] ) ) {

                  $_query['date_query'][0][$map['compare']] = $q['value'];

                  $_query['date_query'][0]['inclusive'] = true;
                }

                break;
            }
          }

        }

        return $_query;
      }

      /**
       * Redeclare method in child class to
       * pass extra arguments or modify existing ones for WP_Query
       *
       * @param array $args
       * @return array
       */
      public function filter_wp_query( $args ) {
        return $args;
      }

      /**
       * Generate the table navigation above or below the table
       *
       * @access protected
       * @param string $which
       */
      protected function display_tablenav( $which ) {
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

          <?php if( isset( $this->options['show_bulk_actions'] ) && $this->options['show_bulk_actions']  ) { ?>

          <div class="alignleft actions bulkactions">
            <?php $this->bulk_actions( $which ); ?>
          </div>

          <?php } ?>

          <?php if( isset( $this->options['show_pagination'] ) && $this->options['show_pagination'] ) {  ?>
          <?php $this->extra_tablenav( $which ); ?>
          <?php $this->pagination( $which ); ?>
          <?php  }  ?>

          <br class="clear" />
        </div>
        <?php
      }

      /**
       * Display the bulk actions dropdown.
       *
       * @access protected
       * @param string $which The location of the bulk actions: 'top' or 'bottom'.
       *                      This is designated as optional for backwards-compatibility.
       */
      protected function bulk_actions( $which = '' ) {
        if ( is_null( $this->_actions ) ) {
          $no_new_actions = $this->_actions = $this->get_bulk_actions();
          /**
           * Filter the list table Bulk Actions drop-down.
           *
           * The dynamic portion of the hook name, `$this->screen->id`, refers
           * to the ID of the current screen, usually a string.
           *
           * This filter can currently only be used to remove bulk actions.
           *
           * @since 3.5.0
           *
           * @param array $actions An array of the available bulk actions.
           */
          $screen = is_object( $this->screen ) ? $this->screen->id : $this->screen;
          $this->_actions = apply_filters( "bulk_actions-{$screen}", $this->_actions );
          $this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
          $two = '';
        } else {
          $two = '2';
        }

        if ( empty( $this->_actions ) )
          return;

        echo "<label for='bulk-action-selector-" . esc_attr( $which ) . "' class='screen-reader-text'>" . __( 'Select bulk action' ) . "</label>";
        echo "<select name='doaction$two' id='bulk-action-selector-" . esc_attr( $which ) . "'>\n";
        echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions' ) . "</option>\n";

        foreach ( $this->_actions as $name => $title ) {
          $class = 'edit' == $name ? ' class="hide-if-no-js"' : '';

          echo "\t<option value='$name'$class>$title</option>\n";
        }

        echo "</select>\n";

        submit_button( __( 'Apply' ), 'action', false, false, array( 'id' => "doaction$two" ) );
        echo "\n";
      }

      /**
       * Address Column
       *
       * @return string
       */
      public function column_title( $post ) {
        $data = "";

        $lock_holder = wp_check_post_lock( $post->ID );
        if ( $lock_holder ) {
          $lock_holder = get_userdata( $lock_holder );
        }

        $post_type_object = get_post_type_object( $post->post_type );
        $edit_link = get_edit_post_link( $post->ID );
        $can_edit_post = current_user_can( 'edit_post', $post->ID );
        $title = apply_filters( 'wplt_column_title_label', _draft_or_post_title( $post ), $post );

        if ( $format = get_post_format( $post->ID ) ) {
          $label = get_post_format_string( $format );
          $data .= '<a href="' . esc_url( add_query_arg( array( 'post_format' => $format, 'post_type' => $post->post_type ), 'edit.php' ) ) . '" class="post-state-format post-format-icon post-format-' . $format . '" title="' . $label . '">' . $label . ":</a> ";
        }

        if ( $can_edit_post && $post->post_status != 'trash' ) {
          $data .= '<a class="row-title" href="' . $edit_link . '" title="' . esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ) . '">' . $title . '</a>';
        } else {
          $data .= $title;
        }

        ob_start();
        _post_states( $post );
        $data .= ob_get_clean();

        if ( isset( $parent_name ) )
          $data .= ' | ' . $post_type_object->labels->parent_item_colon . ' ' . esc_html( $parent_name );

        $data .= "</strong>\n";

        if ( $can_edit_post && $post->post_status != 'trash' ) {
          if ( $lock_holder ) {
            $locked_avatar = get_avatar( $lock_holder->ID, 18 );
            $locked_text = esc_html( sprintf( __( '%s is currently editing' ), $lock_holder->display_name ) );
          } else {
            $locked_avatar = $locked_text = '';
          }

          $data .= '<div class="locked-info"><span class="locked-avatar">' . $locked_avatar . '</span> <span class="locked-text">' . $locked_text . "</span></div>\n";
        }

        $actions = array();
        if ( $can_edit_post && 'trash' != $post->post_status ) {
          $actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr__( 'Edit this item' ) . '">' . __( 'Edit' ) . '</a>';
        }
        if ( current_user_can( 'delete_post', $post->ID ) ) {
          if ( 'trash' == $post->post_status )
            $actions['untrash'] = "<a title='" . esc_attr__( 'Restore this item from the Trash' ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ) . "'>" . __( 'Restore' ) . "</a>";
          elseif ( EMPTY_TRASH_DAYS )
            $actions['trash'] = "<a class='submitdelete' title='" . esc_attr__( 'Move this item to the Trash' ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash' ) . "</a>";
          if ( 'trash' == $post->post_status || !EMPTY_TRASH_DAYS )
            $actions['delete'] = "<a class='submitdelete' title='" . esc_attr__( 'Delete this item permanently' ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently' ) . "</a>";
        }
        if ( $post_type_object->public ) {
          if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ) ) ) {
            if ( $can_edit_post ) {
              $preview_link = set_url_scheme( get_permalink( $post->ID ) );
              /** This filter is documented in wp-admin/includes/meta-boxes.php */
              $preview_link = apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $preview_link ), $post );
              $actions['view'] = '<a href="' . esc_url( $preview_link ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';
            }
          } elseif ( 'trash' != $post->post_status ) {
            $actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">' . __( 'View' ) . '</a>';
          }
        }

        if ( is_post_type_hierarchical( $post->post_type ) ) {

          /**
           * Filter the array of row action links on the Pages list table.
           *
           * The filter is evaluated only for hierarchical post types.
           *
           * @since 2.8.0
           *
           * @param array   $actions An array of row action links. Defaults are
           *                         'Edit', 'Quick Edit', 'Restore, 'Trash',
           *                         'Delete Permanently', 'Preview', and 'View'.
           * @param WP_Post $post    The post object.
           */
          $actions = apply_filters( 'page_row_actions', $actions, $post );
        } else {

          /**
           * Filter the array of row action links on the Posts list table.
           *
           * The filter is evaluated only for non-hierarchical post types.
           *
           * @since 2.8.0
           *
           * @param array   $actions An array of row action links. Defaults are
           *                         'Edit', 'Quick Edit', 'Restore, 'Trash',
           *                         'Delete Permanently', 'Preview', and 'View'.
           * @param WP_Post $post    The post object.
           */
          $actions = apply_filters( 'post_row_actions', $actions, $post );
        }

        $data .= $this->row_actions( $actions );

        return $data;
      }

      /**
       * Display the table
       * Adds a Nonce field and calls parent's display method
       *
       * @since 3.1.0
       * @access public
       * @param array $args
       */
      public function display( $args = array() ) {

        echo "<div id=\"{$this->name}\" class=\"wplt_container\">";

        if( $this->options['show_filter'] ) {
          $this->filter();
        }

        $singular = $this->_args['singular'];

        $this->display_tablenav( 'top' );

        ?>
        <table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">

          <thead>
          <tr><?php $this->print_column_headers(); ?></tr>
          </thead>

          <tfoot>
            <tr><?php $this->print_column_headers( false ); ?></tr>
          </tfoot>

          <tbody id="the-list"<?php if ( $singular ) { echo " data-wp-lists='list:$singular'"; } ?>>
            <?php $this->display_rows_or_placeholder(); ?>
          </tbody>

        </table>

        <?php $this->display_tablenav( 'bottom' ); ?>

        </div>

        <?php echo "<script type=\"text/javascript\">
          jQuery( document ).ready( function(){
            if( typeof jQuery.fn.wp_list_table !== 'undefined' ) {
              if(typeof window.wplt == 'undefined'){
                window.wplt = {};
              }
              window.wplt.{$this->name} = jQuery( '#{$this->name}' ).wp_list_table({
                '_wpnonce': '" . wp_create_nonce( '_wplt_list_nonce' ) . "',
                'order': '{$this->order}',
                'orderby': '{$this->orderby}',
                'singular': '{$this->singular}',
                'plural': '{$this->plural}',
                'class': '" . urlencode( get_class( $this ) ) . "',
                'per_page': '{$this->per_page}',
                'post_type': '{$this->post_type}',
                'screen': '" . ( is_object( $this->screen ) ? $this->screen->id : $this->screen ) . "',
                'post_status': " . ( is_array( $this->post_status ) ? json_encode( $this->post_status ) : "'" . $this->post_status . "'" ) .  ",
                'extra': " . ( is_array( $this->extra ) ? json_encode( $this->extra ) : '{}' ) . ",
                'query': " . ( is_array( $this->query ) ? json_encode( $this->query ) : '{}' ) . "
              });
            }
          } );
        </script>"; ?>

      <?php

      }

      /**
       * Renders Search Filter
       */
      public function filter() {
        $f = $this->filter;
        if( $f && !empty( $f['fields'] ) ) {
          $filter = new Filter( array_merge( $f, array( 'name' => $this->name ) ) );
          $filter->display();
        }
      }

      /**
       * Handle an incoming ajax request (called from admin-ajax.php)
       *
       * @since 3.1.0
       * @access public
       */
      public function ajax_response() {

        if( !isset($_REQUEST[ '_wpnonce' ]) || !wp_verify_nonce( $_REQUEST[ '_wpnonce' ], '_wplt_list_nonce' ) ) {
          return false;
        }

        $response = array();

        $this->process_bulk_action();

        $this->prepare_items();

        ob_start();
        if ( ! empty( $_REQUEST['no_placeholder'] ) )
          $this->display_rows();
        else
          $this->display_rows_or_placeholder();
        $response['rows'] = ob_get_clean();

        ob_start();
        $this->print_column_headers();
        $response['column_headers'] = ob_get_clean();

        ob_start();
        $this->bulk_actions('top');
        $response['bulk_actions']['top'] = ob_get_clean();

        ob_start();
        $this->bulk_actions('bottom');
        $response['bulk_actions']['bottom'] = ob_get_clean();

        ob_start();
        $this->pagination('top');
        $response['pagination']['top'] = ob_get_clean();

        ob_start();
        $this->pagination('bottom');
        $response['pagination']['bottom'] = ob_get_clean();

        $response = apply_filters( 'wplt::ajax_response', $response );

        /* Notices */
        $response['notice'] = array();
        if( !empty( $this->message ) ) {
          $response['notice']['message'] = $this->message;
        }
        if( !empty( $this->warning ) ) {
          $response['notice']['warning'] = $this->warning;
        }
        if( !empty( $this->error ) ) {
          $response['notice']['error'] = $this->error;
        }

        return $response;
      }
      
      /**
       * Send required variables to JavaScript land
       *
       * @access public
       */
      public function _js_vars() {
        if( function_exists( 'get_current_screen' ) ) {
          $screen = get_current_screen();
        }
        $args = array(
          'class'  => get_class( $this ),
          'screen' => array(
            'id'   => isset( $screen ) && is_object( $screen ) ? $screen->id : $this->screen,
            'base' => isset( $screen ) && is_object( $screen ) ? $screen->base : false,
          )
        );
        printf( "<script type='text/javascript'>list_args = %s;</script>\n", wp_json_encode( $args ) );
      }

      /**
       * Store all custom properties in $this->properties
       *
       * @author peshkov@UD
       */
      public function __set($name, $value) {
        $this->properties[$name] = $value;
      }

      /**
       * Get custom properties
       *
       * @author peshkov@UD
       */
      public function __get($name) {
        return isset($this->properties[$name]) ? $this->properties[$name] : NULL;
      }

    }

  }

}
