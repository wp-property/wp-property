lib-wp-list-table 
================

## Description
Advanced AJAX WP_List_Table library for Wordpress

#### Loader
```php

/**
 * Load WP List Table library.
 *
 * Attention: It must be loaded not later than on 'init' action
 * Loader registers all library's AJAX hooks.
 */
new \UsabilityDynamics\WPLT\Bootstrap();

```

#### Table & Filter Usage

Filter has the very similar fields with wp-meta-box plugin.
You can find more information about filter fields here: http://metabox.io/docs/define-fields/

List Table filter supports the following list of fields:
* text
* select
* select_advanced
* taxonomy
* user
* checkbox
* checkbox_list
* radio

Also partially supports the following fields:
* datetime
* date
* time

##### Code Example

```php

/**
 * You must declare child class \UsabilityDynamics\WPLT\WP_List_Table
 * to operate with
 *
 */
class My_WP_List_Table extends \UsabilityDynamics\WPLT\WP_List_Table {

  /**
   * Set columns for your table
   */
  public function get_columns() {
    return array(
      'title' => __('Title'),
      'description' => __('Description')
    );
  }
  
  /**
   * Handle description column.
   *
   * Note: every column is being handled via method:
   * column_{slug_of_your_column}()
   */
  public function column_description( $post ){
    return get_post_meta( $post->ID, 'description', true );
  }

}

/**
 * Init list table object with Filter.
 * 
 * To find more arguments for initialisation, - 
 * see \UsabilityDynamics\WPLT\WP_List_Table constructor
 */
$list_table = new My_WP_List_Table( array(
  'filter' => array(
    'fields' => array(
      array(
        'id' => 's',
        'name' => __( 'Search' ),
        'placeholder' => __( 'Search...' ),
        'type' => 'text',
        'map' => array(
          'class' => 'post', // Available: 'post','meta','taxonomy'
          'type' => 'string' // Available: 'string', 'number'
          'compare' => '=' // Available: '=', 'IN', 'NOT IN', etc..
        )
      ),
      array(
        'id' => 'post_status',
        'name' => __( 'Status' ),
        'type' => 'select_advanced',
        'js_options' => array(
          'allowClear' => false,
        ),
        'options' => array(
          'all' => __( 'All' ),
          'publish' => __( 'Publish' ),
          'draft' => __( 'Draft' ),
          'trash' => __( 'Trash' ),
        )
      ),
      array(
        'id' => 'post_tag',
        'name' => __( 'Tags' ),
        'type' => 'taxonomy',
        'multiple' => true,
        'options' => array(
          'taxonomy' => 'post_tag',
          'type' => 'checkbox_list', // 'select_advanced'
          'args' => array(),
        ),
        'map' => array(
          'class' => 'taxonomy',
        ),
      ),
    )
  )
) );

/**
 * Renders Filter.
 * It can be rendered anywhere in your DOM.
 */
$list_table->filter();

/**
 * Prepare items and render table
 * Renders Filter.
 */
$list_table->prepare_items();
$list_table->display();


```

#### UI

The current library is using lib-ui library, which allows to easy create custom overview page with Meta Boxes compatibility.

```php

/** 
 * Add submenu page with already existing template and Meta Boxes complatibility
 */
public function my_admin_menu() {
  /**
   * The current class gets the same arguments as native add_submenu_page() function,
   * except of $function argument in the end. However you can pass it too, if
   * you need to use custom template for your needs.
   */
  $page = new \UsabilityDynamics\UI\Page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug );
  
  /* We need to init list table before page loaded to put filter to separate meta box */
  add_action( 'load-' . $this->page->screen_id, 'page_preload' );
  /* Register meta boxes */
  add_action( 'add_meta_boxes_' . $page->screen_id, 'add_meta_boxes' );
}
add_action( 'admin_menu', 'my_admin_menu' );

/**
 * We need to init list table before page loaded to put filter to separate meta box
 */
public function page_preload() {
  global $list_table;
  
  $list_table = new My_WP_List_Table( array(
    'filter' => array(
      'fields' => array(
        array(
          'id' => 's',
          'name' => __( 'Search' ),
          'placeholder' => __( 'Search...' ),
          'type' => 'text',
          'map' => array(
            'class' => 'post', // Available: 'post','meta','taxonomy'
            'type' => 'string' // Available: 'string', 'number'
            'compare' => '=' // Available: '=', 'IN', 'NOT IN', etc..
          )
        ),
        array(
          'id' => 'post_status',
          'name' => __( 'Status' ),
          'type' => 'select_advanced',
          'js_options' => array(
            'allowClear' => false,
          ),
          'options' => array(
            'all' => __( 'All' ),
            'publish' => __( 'Publish' ),
            'draft' => __( 'Draft' ),
            'trash' => __( 'Trash' ),
          )
        ),
      )
    )
  ) );
    
}

/**
 *
 */
public function add_meta_boxes() {
  $screen = get_current_screen();
  add_meta_box( 'posts_list', __('Overview'), 'render_list_table', $screen->id,'normal');
  add_meta_box( 'posts_filter', __('Filter'), 'render_filter', $screen->id,'side');
}

/**
 * Prepare our items and render table.
 */
public function render_list_table() {
  global $list_table;
  
  $list_table->prepare_items();
  $list_table->display();
}

/**
 * Render our filter on sidebar
 */
public function render_filter() {
  global $list_table;
  
  $list_table->filter();
}

```


## License

(The MIT License)

Copyright (c) 2013 Usability Dynamics, Inc. &lt;info@usabilitydynamics.com&gt;

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
