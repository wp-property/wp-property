<?php

/**
 * Shortcode: [property_overview]
 *
 * @since 2.0.5
 */
namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\Property_Overview_Shortcode')) {

    class Property_Overview_Shortcode extends Shortcode
    {

      /**
       * Init
       */
      public function __construct()
      {

        /** Ajax pagination for property_overview */
        add_action("wp_ajax_wpp_property_overview_pagination", array($this, "ajax_handler"));
        add_action("wp_ajax_nopriv_wpp_property_overview_pagination", array($this, "ajax_handler"));

        $custom_attributes = ud_get_wp_property('property_stats', array());

        $image_sizes = array('' => __('No Image', ud_get_wp_property()->domain));
        foreach (get_intermediate_image_sizes() as $name) {
          $sizes = \WPP_F::image_sizes($name);
          if (!$sizes) {
            continue;
          }
          $image_sizes[$name] = $name . ' (' . $sizes['width'] . 'x' . $sizes['height'] . 'px)';
        }

        $sort_by = array(
          'post_date' => sprintf(__('Post Date (%s)', ud_get_wp_property()->domain), 'post_date'),
          'post_modified' => sprintf(__('Modified Date (%s)', ud_get_wp_property()->domain), 'post_modified'),
          'random' => sprintf(__('Random (%s)', ud_get_wp_property()->domain), 'random'),
          'menu_order' => sprintf(__('Menu Order (%s)', ud_get_wp_property()->domain), 'menu_order'),
        );

        $sortable_atts = ud_get_wp_property('sortable_attributes', array());
        if (!empty($sortable_atts) && is_array($sortable_atts)) {
          foreach ($sortable_atts as $attr) {
            if (array_key_exists($attr, $custom_attributes)) {
              $sort_by[$attr] = $custom_attributes[$attr] . ' (' . $attr . ')';
            }
          }
        }

        $options = array(
          'id' => 'property_overview',
          'params' => array(
            'property_id' => array(
              'name' => __('Property ID', ud_get_wp_property()->domain),
              'description' => __('If not empty, result will show particular property, which ID is set.', ud_get_wp_property()->domain),
              'type' => 'text',
              'default' => ''
            ),
            'post_parent' => array(
              'name' => sprintf(__('Parent %s', ud_get_wp_property()->domain), \WPP_F::property_label()),
              'description' => sprintf(__('If not empty, result will show children of particular property, which ID is set. You can use dynamic attributes instead of ID such as %1$s or %2$s.<br/>%1$s - to list all the listings that are a child of the current %3$s.<br/>%2$s - to list all listings that are children of the same parent (i.e. siblings) of the current %3$s', ud_get_wp_property()->domain), '<b>post_id</b>', '<b>post_parent</b>', \WPP_F::property_label()),
              'type' => 'text',
              'default' => ''
            ),
            'wpp_agents' => array(
              'name' => __('Assigned Agent ID', ud_get_wp_property()->domain),
              'description' => sprintf( __('Optional. If set, shows only %s to which Agent belongs', ud_get_wp_property()->domain), \WPP_F::property_label( 'plural' ) ),
              'type' => 'text',
              'default' => ''
            ),
            'property_type' => array(
              'name' => sprintf(__('%s Type', ud_get_wp_property()->domain), \WPP_F::property_label()),
              'description' => sprintf(__('The list of %s types to be included. If no type checked, all available %s will be shown.', ud_get_wp_property()->domain), \WPP_F::property_label(), \WPP_F::property_label('plural')),
              'type' => 'multi_checkbox',
              'options' => ud_get_wp_property('property_types'),
            ),
            'featured' => array(
              'name' => sprintf(__('Show only Featured %s', ud_get_wp_property()->domain), \WPP_F::property_label('plural')),
              'type' => 'checkbox',
            ),
            'custom_query' => array(
              'name' => __('Custom Query by Attributes Values', ud_get_wp_property()->domain),
              'description' => sprintf(__('Setup your custom query by providing values for specific attributes. Empty values will be ignored. Example:<br/>- to list only %1$s which have minimum 2 and maximum 4 bedrooms, you should set <b>2-4</b> value for your Bedrooms attribute.<br/>- to list only %1$s which have 1 or 3 bathrooms, you should set <b>1,3</b> value for your Bathrooms attribute.', ud_get_wp_property()->domain), \WPP_F::property_label()),
              'type' => 'custom_attributes',
              'options' => $custom_attributes,
            ),
            'show_children' => array(
              'name' => __('Show Children', ud_get_wp_property()->domain),
              'description' => sprintf(__('Switches children %s displaying.', ud_get_wp_property()->domain), \WPP_F::property_label()),
              'type' => 'select',
              'options' => array(
                'true' => __('Yes', ud_get_wp_property()->domain),
                'false' => __('No', ud_get_wp_property()->domain)
              ),
              'default' => 'true'
            ),
            'child_properties_title' => array(
              'name' => sprintf(__('Child %s Title', ud_get_wp_property()->domain), \WPP_F::property_label('plural')),
              'description' => sprintf(__('Title for Child %s section.', ud_get_wp_property()->domain), \WPP_F::property_label('plural')),
              'type' => 'text',
              'default' => __('Floor plans at location:', ud_get_wp_property()->domain)
            ),
            'fancybox_preview' => array(
              'name' => __('Fancybox Preview', ud_get_wp_property()->domain),
              'description' => __('Use fancybox preview.', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => array(
                'true' => __('Yes', ud_get_wp_property()->domain),
                'false' => __('No', ud_get_wp_property()->domain)
              ),
              'default' => 'true'
            ),
            'bottom_pagination_flag' => array(
              'name' => __('Bottom Pagination', ud_get_wp_property()->domain),
              'description' => __('Show Bottom Pagination.', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => array(
                'true' => __('Yes', ud_get_wp_property()->domain),
                'false' => __('No', ud_get_wp_property()->domain)
              ),
              'default' => 'false'
            ),
            'thumbnail_size' => array(
              'name' => __('Thumbnail Size', ud_get_wp_property()->domain),
              'description' => __('Show image with specific image size', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => $image_sizes,
              'default' => 'thumbnail'
            ),
            'sort_by_text' => array(
              'name' => __('Sort By Text', ud_get_wp_property()->domain),
              'description' => __('Renames "Sort By:" text.', ud_get_wp_property()->domain),
              'type' => 'text',
              'default' => __('Sort By', ud_get_wp_property()->domain)
            ),
            'sort_by' => array(
              'name' => __('Sort By', ud_get_wp_property()->domain),
              'description' => sprintf(__('Sets sorting by sortable attribute or %s.', ud_get_wp_property()->domain), 'post_date, menu_order, random'),
              'type' => 'select',
              'options' => $sort_by,
              'default' => 'post_date'
            ),
            'sort_order' => array(
              'name' => __('Sort Order', ud_get_wp_property()->domain),
              'description' => __('Sort Order', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => array(
                'DESC' => 'DESC',
                'ASC' => 'ASC'
              ),
              'default' => 'DESC'
            ),
            'template' => array(
              'name' => __('Template', ud_get_wp_property()->domain),
              'description' => sprintf(__('Sets layout using PHP template name. Your custom template should be stored in your theme\'s root directory. Example:<br/>if your custom template is called %s, the value of template must be %s.', ud_get_wp_property()->domain), '<b>property-overview-grid.php</b>', '<b>grid</b>'),
              'type' => 'text',
            ),
            'sorter_type' => array(
              'name' => __('Sorter Type', ud_get_wp_property()->domain),
              'description' => __('Sorter Type', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => array(
                'none' => __('None', ud_get_wp_property()->domain),
                'buttons' => __('Buttons', ud_get_wp_property()->domain),
                'dropdown' => __('Dropdown', ud_get_wp_property()->domain)
              ),
              'default' => 'buttons'
            ),
            'sorter' => array(
              'name' => __('Sorter', ud_get_wp_property()->domain),
              'description' => __('Show Sort UI', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => array(
                'on' => __('On', ud_get_wp_property()->domain),
                'off' => __('Off', ud_get_wp_property()->domain)
              ),
              'default' => 'on'
            ),
            'pagination' => array(
              'name' => __('Pagination', ud_get_wp_property()->domain),
              'description' => __('Show Pagination', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => array(
                'on' => __('On', ud_get_wp_property()->domain),
                'off' => __('Off', ud_get_wp_property()->domain)
              ),
              'default' => 'on'
            ),
            'pagination_type' => array(
              'name' => __('Pagination Type', ud_get_wp_property()->domain),
              'description' => __('Determine pagination UI', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => array(
                'slider' => __('slider', ud_get_wp_property()->domain),
                'numeric' => __('numeric', ud_get_wp_property()->domain),
                'loadmore' => __('load more', ud_get_wp_property()->domain)
              ),
              'default' => ud_get_wp_property('configuration.property_overview.pagination_type') ? ud_get_wp_property('configuration.property_overview.pagination_type') : 'loadmore',
            ),
            'per_page' => array(
              'name' => __('Per Page', ud_get_wp_property()->domain),
              'description' => sprintf(__('%s quantity per page.', ud_get_wp_property()->domain), \WPP_F::property_label()),
              'type' => 'number',
              'default' => 10
            ),
            'starting_row' => array(
              'name' => __('Starting Row', ud_get_wp_property()->domain),
              'description' => __('Sets starting row.', ud_get_wp_property()->domain),
              'type' => 'number',
              'default' => 0
            ),
            'detail_button' => array(
              'name' => __('Detail Button', ud_get_wp_property()->domain),
              'description' => __('Name of Detail Button. Button will not be shown if the value is empty.', ud_get_wp_property()->domain),
              'type' => 'text',
            ),
            'hide_count' => array(
              'name' => __('Hide Count', ud_get_wp_property()->domain),
              'description' => __('Hide the "10 found." text.', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => array(
                'true' => __('Yes', ud_get_wp_property()->domain),
                'false' => __('No', ud_get_wp_property()->domain)
              ),
              'default' => 'false'
            ),
            'in_new_window' => array(
              'name' => __('In new window?', ud_get_wp_property()->domain),
              'description' => __('Open links in new window.', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => array(
                'true' => __('Yes', ud_get_wp_property()->domain),
                'false' => __('No', ud_get_wp_property()->domain)
              ),
              'default' => 'false'
            ),
            'strict_search' => array(
              'name' => __('Strict Search', ud_get_wp_property()->domain),
              'description' => __('Provides strict search', ud_get_wp_property()->domain),
              'type' => 'select',
              'options' => array(
                'true' => __('Yes', ud_get_wp_property()->domain),
                'false' => __('No', ud_get_wp_property()->domain)
              ),
              'default' => 'false'
            ),
            'attributes' => array(
              'name' => __('Attributes to display', ud_get_wp_property()->domain),
              'description' => __('The list of attributes which will not be shown.', ud_get_wp_property()->domain),
              'type' => 'multi_checkbox',
              'options' => $custom_attributes,
              'default' => array('phone_number', 'display_address', 'price'),
            ),
          ),
          'description' => sprintf(__('Renders %s Attributes', ud_get_wp_property()->domain), \WPP_F::property_label()),
          'group' => 'WP-Property'
        );

        parent::__construct($options);
      }

      /**
       * @param string $atts
       * @return string|void
       */
      public function call($atts = "")
      {
        return $this::render($atts);
      }

      /**
       * Return property overview data for AJAX calls
       *
       * @since 0.723
       */
      function ajax_handler()
      {

        $params = $_REQUEST['wpp_ajax_query'];

        if (!empty($params['action'])) {
          unset($params['action']);
        }

        $params['ajax_call'] = true;

        $data = $this::render($params);

        die($data);
      }

      /**
       * Displays property overview
       *
       * Performs searching/filtering functions, provides template with $properties file
       * Retirms html content to be displayed after location attribute on property edit page
       *
       * @since 1.081
       *
       * @param string $listing_id Listing ID must be passed
       *
       * @return string $result
       *
       * @uses WPP_F::get_properties()
       *
       */
      static public function render($atts = "")
      {
        global $wp_properties, $wpp_query, $property, $post, $wp_query;

        $_property = $property;
        $wpp_query = array();

        $atts = wp_parse_args($atts, array());
        if (isset($atts['attributes']) && !is_array($atts['attributes'])) {
          $atts['attributes'] = explode(',', $atts['attributes']);
        }

        \WPP_F::force_script_inclusion('jquery-ui-widget');
        \WPP_F::force_script_inclusion('jquery-ui-mouse');
        \WPP_F::force_script_inclusion('jquery-ui-slider');
        \WPP_F::force_script_inclusion('wpp-jquery-address');
        \WPP_F::force_script_inclusion('wpp-jquery-scrollTo');
        \WPP_F::force_script_inclusion('wpp-jquery-fancybox');
        \WPP_F::force_script_inclusion('wp-property-frontend');

        //** Load all queriable attributes **/
        $queryable_keys = array();
        foreach (\WPP_F::get_queryable_keys() as $key) {
          //** This needs to be done because a key has to exist in the $deafult array for shortcode_atts() to load passed value */
          $queryable_keys[$key] = false;
        }

        //** Allow the shorthand of "type" as long as there is not a custom attribute of "type". If "type" does exist as an attribute, then users need to use the full "property_type" query tag. **/
        if (!array_key_exists('type', $queryable_keys) && (is_array($atts) && array_key_exists('type', $atts))) {
          $atts['property_type'] = $atts['type'];
          unset($atts['type']);
        }

        //** Get ALL allowed attributes that may be passed via shortcode (to include property attributes) */
        $defaults['strict_search'] = false;
        $defaults['show_children'] = (isset($wp_properties['configuration']['property_overview']['show_children']) ? $wp_properties['configuration']['property_overview']['show_children'] : 'true');
        $defaults['child_properties_title'] = __('Floor plans at location:', ud_get_wp_property()->domain);
        $defaults['fancybox_preview'] = $wp_properties['configuration']['property_overview']['fancybox_preview'];
        $defaults['bottom_pagination_flag'] = (isset($wp_properties['configuration']['bottom_insert_pagenation']) && $wp_properties['configuration']['bottom_insert_pagenation'] == 'true' ? true : false);
        $defaults['thumbnail_size'] = $wp_properties['configuration']['property_overview']['thumbnail_size'];
        $defaults['sort_by_text'] = __('Sort By:', ud_get_wp_property()->domain);
        $defaults['sort_by'] = 'post_date';
        $defaults['sort_order'] = 'DESC';
        $defaults['template'] = false;
        $defaults['ajax_call'] = false;
        $defaults['disable_wrapper'] = false;
        $defaults['sorter_type'] = 'buttons';
        $defaults['sorter'] = 'on';
        $defaults['pagination'] = 'on';
        $defaults['pagination_type'] = ud_get_wp_property('configuration.property_overview.pagination_type') ? ud_get_wp_property('configuration.property_overview.pagination_type') : 'loadmore';
        $defaults['hide_count'] = false;
        $defaults['per_page'] = 10;
        $defaults['starting_row'] = 0;
        $defaults['unique_hash'] = rand(10000, 99900);
        $defaults['detail_button'] = false;
        $defaults['stats'] = '';
        $defaults['class'] = WPP_LEGACY_WIDGETS ? 'wpp_property_overview_shortcode' : 'wpp_property_overview_shortcode_v2';
        $defaults['in_new_window'] = false;
        $defaults['attributes'] = array('phone_number', 'display_address', 'price');

        $defaults = apply_filters('shortcode_property_overview_allowed_args', $defaults, $atts);

        //* Determine if we should disable sorter */
        if (isset($atts['sorter']) && in_array($atts['sorter'], array('off', 'false'))) {
          $atts['sorter'] = false;
          $atts['sorter_type'] = 'none';
        }

        if (!empty($atts['ajax_call'])) {
          //** If AJAX call then the passed args have all the data we need */
          $wpp_query = $atts;

          //* Fix ajax data. Boolean value false is returned as string 'false'. */
          foreach ($wpp_query as $key => $value) {
            if ($value == 'false') {
              $wpp_query[$key] = false;
            }
          }

          $wpp_query['ajax_call'] = true;

          //** Everything stays the same except for sort order and page */
          $wpp_query['starting_row'] = (($wpp_query['requested_page'] - 1) * $wpp_query['per_page']);

          //** Figure out current page */
          $wpp_query['current_page'] = $wpp_query['requested_page'];

        } else {
          /** Determine if fancybox style is included */
          \WPP_F::force_style_inclusion('wpp-jquery-fancybox-css');

          //** Merge defaults with passed arguments */
          $wpp_query = shortcode_atts($defaults, $atts);
          $wpp_query['query'] = shortcode_atts($queryable_keys, $atts);

          //** Handle search */
          if (!empty($_REQUEST['wpp_search'])) {
            $wpp_query['query'] = shortcode_atts($wpp_query['query'], $_REQUEST['wpp_search']);
            $wpp_query['query'] = \WPP_F::prepare_search_attributes($wpp_query['query']);

            if (isset($_REQUEST['wpp_search']['sort_by'])) {
              $wpp_query['sort_by'] = $_REQUEST['wpp_search']['sort_by'];
            }

            if (isset($_REQUEST['wpp_search']['sort_order'])) {
              $wpp_query['sort_order'] = $_REQUEST['wpp_search']['sort_order'];
            }

            if (isset($_REQUEST['wpp_search']['pagination'])) {
              $wpp_query['pagination'] = $_REQUEST['wpp_search']['pagination'];
            }

            if (isset($_REQUEST['wpp_search']['per_page'])) {
              $wpp_query['per_page'] = $_REQUEST['wpp_search']['per_page'];
            }

            if (isset($_REQUEST['wpp_search']['strict_search'])) {
              $wpp_query['strict_search'] = $_REQUEST['wpp_search']['strict_search'];
            }
          }

        }

        //** Load certain settings into query for get_properties() to use */
        $wpp_query['query']['sort_by'] = $wpp_query['sort_by'];
        $wpp_query['query']['sort_order'] = $wpp_query['sort_order'];

        $wpp_query['query']['pagi'] = $wpp_query['starting_row'] . '--' . $wpp_query['per_page'];

        if (!isset($wpp_query['current_page'])) {
          $wpp_query['current_page'] = ($wpp_query['starting_row'] / $wpp_query['per_page']) + 1;
        }

        //** Load settings that are not passed via shortcode atts */
        $wpp_query['sortable_attrs'] = \WPP_F::get_sortable_keys();

        //** Replace dynamic field values */

        //** Detect currently property for conditional in-shortcode usage that will be replaced from values */
        if (isset($post) && is_object($post)) {

          $dynamic_fields['post_id'] = isset($post->ID) ? $post->ID : 0;
          $dynamic_fields['post_parent'] = isset($post->post_parent) ? $post->post_parent : 0;
          $dynamic_fields['property_type'] = isset($post->property_type) ? $post->property_type : false;

          $dynamic_fields = apply_filters('shortcode_property_overview_dynamic_fields', $dynamic_fields);

          if (is_array($dynamic_fields)) {
            foreach ($wpp_query['query'] as $query_key => $query_value) {
              if (!empty($dynamic_fields[$query_value])) {
                $wpp_query['query'][$query_key] = $dynamic_fields[$query_value];
              }
            }
          }
        }

        //** Remove all blank values */
        $wpp_query['query'] = array_filter($wpp_query['query']);

        //echo "<pre>"; print_r( $wpp_query ); echo "</pre>";

        //** We add # to value which says that we don't want to use LIKE in SQL query for searching this value. */
        $required_strict_search = apply_filters('wpp::required_strict_search', array('wpp_agents'));
        $ignored_strict_search_field_types = apply_filters('wpp:ignored_strict_search_field_types', array('range_dropdown', 'range_input', 'range_date'));

        foreach ($wpp_query['query'] as $key => $val) {
          if (!array_key_exists($key, $defaults) && $key != 'property_type') {
            //** Be sure that the attribute exists of parameter is required for strict search */
            if (
              (in_array($wpp_query['strict_search'], array('true', 'on')) && isset($wp_properties['property_stats'][$key]))
              || in_array($key, $required_strict_search)
            ) {
              /**
               * Ignore specific search attribute fields for strict search.
               * For example, range values must not be included to strict search.
               * Also, be sure to ignore list of values
               */
              if (
                (isset($wp_properties['searchable_attr_fields'][$key]) && in_array($wp_properties['searchable_attr_fields'][$key], (array)$ignored_strict_search_field_types))
                || substr_count($val, ',')
                || substr_count($val, '&ndash;')
                || substr_count($val, '--')
              ) {
                continue;
              } //** Determine if value contains range of numeric values, and ignore it, if so. */
              elseif (substr_count($val, '-')) {
                $_val = explode('-', $val);
                if (count($_val) == 2 && is_numeric($_val[0]) && is_numeric($_val[1])) {
                  continue;
                }
              }
              $wpp_query['query'][$key] = '#' . trim($val, '#') . '#';
            }
          }
        }

        //** Unset this because it gets passed with query (for back-button support) but not used by get_properties() */
        unset($wpp_query['query']['per_page']);
        unset($wpp_query['query']['pagination']);
        unset($wpp_query['query']['requested_page']);

        //** Load the results */
        $wpp_query['properties'] = \WPP_F::get_properties($wpp_query['query'], true);

        //** Calculate number of pages */
        if ($wpp_query['pagination'] == 'on') {
          $wpp_query['pages'] = ceil($wpp_query['properties']['total'] / $wpp_query['per_page']);
        }

        $property_type = isset($wpp_query['query']['property_type']) ? $wpp_query['query']['property_type'] : false;

        if (!empty($property_type) && isset($wp_properties['hidden_attributes'][$property_type])) {
          foreach ((array)$wp_properties['hidden_attributes'][$property_type] as $attr_key) {
            unset($wpp_query['sortable_attrs'][$attr_key]);
          }
        }

        //** Legacy Support - include variables so old templates still work */
        $properties = $wpp_query['properties']['results'];
        $thumbnail_sizes = \WPP_F::image_sizes($wpp_query['thumbnail_size']);
        $child_properties_title = $wpp_query['child_properties_title'];
        $unique = $wpp_query['unique_hash'];
        $thumbnail_size = $wpp_query['thumbnail_size'];

        //* Debugger */
        if (isset($wp_properties['configuration']['developer_mode']) && $wp_properties['configuration']['developer_mode'] == 'true' && !$wpp_query['ajax_call']) {
          echo '<script type="text/javascript">console.log( ' . json_encode($wpp_query) . ' ); </script>';
        }

        ob_start();

        //** Make certain variables available to be used within the single listing page */
        $wpp_overview_shortcode_vars = apply_filters('wpp_overview_shortcode_vars', array(
          'wp_properties' => $wp_properties,
          'wpp_query' => $wpp_query
        ));

        //** By merging our extra variables into $wp_query->query_vars they will be extracted in load_template() */
        if (is_array($wpp_overview_shortcode_vars)) {
          $wp_query->query_vars = array_merge($wp_query->query_vars, $wpp_overview_shortcode_vars);
        }

        $template = $wpp_query['template'];
        $fancybox_preview = $wpp_query['fancybox_preview'];
        $show_children = $wpp_query['show_children'];
        $class = $wpp_query['class'];
        $stats = $wpp_query['stats'];
        $in_new_window = ($wpp_query['in_new_window'] == "true" ? " target=\"_blank\" " : "");

        //** Make query_vars available to emulate WP template loading */
        extract($wp_query->query_vars, EXTR_SKIP);

        //** Try find custom template */
        $template_found = \WPP_F::get_template_part(array(
          "property-overview-{$template}",
          "property-overview-" . sanitize_key($property_type),
          WPP_LEGACY_WIDGETS ? "property-overview" : "property-overview-v2"
        ), array(ud_get_wp_property()->path('static/views', 'dir')));

        if ($template_found) {
          include $template_found;
        }

        $ob_get_contents = ob_get_contents();
        ob_end_clean();

        $ob_get_contents = apply_filters('shortcode_property_overview_content', $ob_get_contents, $wpp_query);

        // Initialize result (content which will be shown) and open wrap (div) with unique id
        if ($wpp_query['disable_wrapper'] != 'true') {
          $result['top'] = '<div id="wpp_shortcode_' . $wpp_query['unique_hash'] . '" class="wpp_ui ' . $wpp_query['class'] . '">';
        }

        $result['top_pagination'] = self::draw_pagination(array(
          'type' => $wpp_query['pagination_type'],
          'class' => 'wpp_top_pagination',
          'sorter_type' => $wpp_query['sorter_type'],
          'hide_count' => $wpp_query['hide_count'],
          'sort_by_text' => $wpp_query['sort_by_text'],
        ));
        $result['result'] = $ob_get_contents;

        if ($wpp_query['bottom_pagination_flag'] == 'true' || $wpp_query['pagination_type'] == 'loadmore') {
          $result['bottom_pagination'] = self::draw_pagination(array(
            'type' => $wpp_query['pagination_type'],
            'class' => 'wpp_bottom_pagination',
            'sorter_type' => $wpp_query['sorter_type'],
            'hide_count' => $wpp_query['hide_count'],
            'sort_by_text' => $wpp_query['sort_by_text'],
            'javascript' => false
          ));
        }

        if ($wpp_query['disable_wrapper'] != 'true') {
          $result['bottom'] = '</div>';
        }

        $result = apply_filters('wpp_property_overview_render', $result);

        /* Reset global property to default. */
        $property = $_property;

        if ($wpp_query['ajax_call']) {
          return json_encode(array('wpp_query' => $wpp_query, 'display' => implode('', $result)));
        } else {
          return implode('', $result);
        }
      }

      /**
       * Renders pagination
       *
       */
      static public function draw_pagination($settings = '')
      {
        global $wpp_query, $wp_properties;

        $use_pagination = false;
        $settings = wp_parse_args($settings, array(
          'type' => 'slider',
          'javascript' => true,
          'return' => true,
          'class' => '',
          'sorter_type' => 'none',
          'hide_count' => false,
          'sort_by_text' => isset($wpp_query['sort_by_text']) ? $wpp_query['sort_by_text'] : ''
        ));

        $settings = apply_filters('wpp:draw_pagination:settings', $settings, $wpp_query, $wp_properties);

        //** Do not show pagination on ajax requests */
        if ($wpp_query['ajax_call']) {
          return null;
        }

        if (is_array($wpp_query) || is_object($wpp_query)) {
          extract($wpp_query);
        }

        if (isset($pagination) && $pagination == 'off' && !empty($hide_count)) {
          return null;
        }

        /** Maybe use custom pagination/sorter instead of default one */
        $custom = apply_filters('wpp:property_overview:custom_pagination', false, $settings, $wpp_query, $wp_properties);

        if (!empty($custom)) {
          if ($settings['return'] == 'true') {
            return $custom;
          } else {
            echo $custom;
            return null;
          }
        }

        if (is_array($wpp_query) || is_object($wpp_query)) {
          extract($wpp_query);
        }

        if ($properties['total'] > $per_page && $pagination != 'off') {
          $use_pagination = true;
        }

        if ($properties['total'] < 2 || $sorter_type == 'none') {
          $sortable_attrs = false;
        }

        //** Determine if we should initialize javascript logic */ */
        if ($settings['javascript']) {

          //** Load pagination script */
          $script_path = apply_filters("wpp::property_overview::script::path", ud_get_wp_property()->path('static/scripts/property_overview.js', 'url'), $settings);
          wp_enqueue_script("property-overview", $script_path, array('jquery'));
          wp_localize_script("property-overview", "_wpp_overview_pagination", array(
            "previous" => __('Previous', ud_get_wp_property()->domain),
            "next" => __('Next', ud_get_wp_property()->domain),
            "first" => __('First', ud_get_wp_property()->domain),
            "last" => __('Last', ud_get_wp_property()->domain),
          ));

          ob_start(); ?>
          <script type="text/javascript">
            jQuery(document).ready(function () {
              if (typeof jQuery.fn.wpp_pagination == 'function') {
                jQuery('#wpp_shortcode_<?php echo $unique_hash ?>').wpp_pagination({
                  "type": "<?php echo $settings['type'] ?>",
                  "unique_id": "<?php echo $unique_hash ?>",
                  "pages": <?php echo !empty($pages) ? $pages : 'null'; ?>,
                  "use_pagination": <?php echo $use_pagination ? $use_pagination : '""'; ?>,
                  "query": <?php echo json_encode($wpp_query); ?>,
                  "ajax_url": "<?php echo admin_url('admin-ajax.php'); ?>"
                });
              }
            });
          </script>
          <?php

          $js_result = ob_get_clean();
          $js_result = apply_filters("wpp::property_overview::{$settings['type']}::script::inline", $js_result, $settings);

        }

        //** Try find pagination template based on type */
        $template_found = \WPP_F::get_template_part(array(
          "pagination"
        ), array(ud_get_wp_property()->path('static/views', 'dir')));

        $result = '';
        if ($template_found) {
          ob_start();
          self::maybe_print_styles();
          include $template_found;
          $result = ob_get_clean();
        }

        //** Combine JS (after minification) with HTML results */
        if ($settings['javascript'] && isset($js_result)) {
          $js_result = \WPP_F::minify_js($js_result);
          $result = $js_result . $result;
        }

        if ($settings['return'] == 'true') {
          return $result;
        }
        echo $result;
      }

      /**
       * Render property_overview default styles at once!
       *
       *
       *
       */
      static public function maybe_print_styles()
      {
        global $_wp_property_overview_style, $wp_properties;

        if ((empty($_wp_property_overview_style) || !$_wp_property_overview_style) && $wp_properties['configuration']['autoload_css'] !== 'false') {
          wp_enqueue_style('wpp-fa-icons');
          $_wp_property_overview_style = true;
          $style_path = apply_filters("property-overview-style-path", ud_get_wp_property()->path('static/styles/property_overview.css', 'url'));
          if (!empty($style_path)) {
            echo "<link rel='stylesheet' href='{$style_path}' type='text/css' media='all' />";
          }
        }
      }

      /**
       * Try find pagination template based on type
       *
       * @param $type
       * @return bool|mixed|null|string|void
       */
      static public function get_pagination_template_based_on_type($type)
      {
        //** Try find pagination template based on type */
        return \WPP_F::get_template_part(array(
          "pagination-{$type}"
        ), array(ud_get_wp_property()->path('static/views', 'dir')));
      }

    }

    /**
     * Register
     */
    new Property_Overview_Shortcode();

  }

}