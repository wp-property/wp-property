<?php
/**
 * Functions to be used in templates.  Overrided by anything in template functions.php
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @version 1.4
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
 */
if (!function_exists('wpp_alternating_row')) {
  /**
   * Display a class for the row
   *
   * @since 1.17.3
   */
  function wpp_alternating_row()
  {
    global $wpp_current_row;
    if ($wpp_current_row == 'wpp_odd_row') {
      $wpp_current_row = 'wpp_even_row';
    } elseif ($wpp_current_row == 'wpp_even_row') {
      $wpp_current_row = 'wpp_odd_row';
    }
    if (!isset($wpp_current_row)) {
      $wpp_current_row = 'wpp_odd_row';
    }
    echo $wpp_current_row;
  }
}

if (!function_exists('get_property_type')) {
  /**
   * Return Label of Property Type for current or particular property
   *
   * @param mixed $post_id Property ID.
   * @return string
   */
  function get_property_type($post_id = false)
  {
    global $post;

    if (!is_numeric($post_id)) {
      if (isset($post->ID)) {
        $post_id = $post->ID;
      } else {
        return '';
      }
    }

    $property_types = (array)ud_get_wp_property('property_types');
    $type = (string)get_post_meta($post_id, 'property_type', true);

    if (array_key_exists($type, $property_types)) {
      return $property_types[$type];
    } else {
      return '';
    }
  }
}

if (!function_exists('get_attribute')) {
  /**
   * Get an attribute for the property
   *
   * @since 1.17.3
   */
  function get_attribute($attribute = false, $args = '')
  {
    global $property, $wp_properties;

    $defaults = array(
      'return' => 'false',
      'property_object' => false,
      'property_id' => false,
      'do_not_format' => false
    );

    $args = wp_parse_args($args, $defaults);

    //** Check if property object/array was passed */
    if (!empty($args['property_object'])) {
      $this_property = (array)$args['property_object'];
    }

    //** Check if a property_id was passed */
    if (!isset($this_property) && !empty($args['property_id'])) {

      $this_property = WPP_F::get_property($args['property_id']);

      if ($args['do_not_format'] != "true") {
        $this_property = prepare_property_for_display($this_property);
      }
    }

    //** If no property data passed, get from global variable */
    if (!isset($this_property)) {
      $this_property = (array)$property;
    }

    switch ($attribute) {

      case 'map':
        $value = do_shortcode("[property_map property_id={$this_property[ 'ID' ]}]");
        break;

      default:
        $value = isset($this_property[$attribute]) ? $this_property[$attribute] : false;
        break;

    }

    $value = apply_filters('wpp_get_attribute', $value, array(
      'attribute' => $attribute,
      'args' => $args,
      'property' => $this_property
    ));
    // Getting translation
    $return[ 'value' ] = apply_filters( 'wpp::attribute::value', $value, $attribute );

    if ($args['return'] == 'true') {
      return $value;
    } else {
      echo $value;
    }
  }
}

if (!function_exists('property_overview_image')) {
  /**
   * Renders the overview image of current property
   *
   * Used for property_overview to render the overview image based on current query and global $property object
   *
   * @args return, image_type
   *
   * @since 1.17.3
   */
  function property_overview_image($args = '')
  {
    global $wpp_query, $property;

    $thumbnail_size = $wpp_query['thumbnail_size'];

    $defaults = array(
      'return' => 'false',
      'image_type' => $thumbnail_size,
    );
    $args = wp_parse_args($args, $defaults);

    /* Make sure that a feature image URL exists prior to committing to fancybox */
    if ($wpp_query['fancybox_preview'] == 'true' && !empty($property['featured_image_url'])) {
      $thumbnail_link = $property['featured_image_url'];
      $link_class = "fancybox_image";
    } else {
      $thumbnail_link = $property['permalink'];
      $link_class = '';
    }

    $image = !empty($property['featured_image']) ? wpp_get_image_link($property['featured_image'], $thumbnail_size, array('return' => 'array')) : false;

    if (!empty($image)) {
      ob_start();
      ?>
      <div class="property_image">
        <a href="<?php echo $thumbnail_link; ?>"
           title="<?php echo $property['post_title'] . (!empty($property['parent_title']) ? __(' of ', ud_get_wp_property()->domain) . $property['parent_title'] : ""); ?>"
           class="property_overview_thumb property_overview_thumb_<?php echo $thumbnail_size; ?> <?php echo $link_class; ?> thumbnail"
           rel="<?php echo $property['post_name'] ?>">
          <img width="<?php echo $image['width']; ?>" height="<?php echo $image['height']; ?>"
               src="<?php echo $image['link']; ?>" alt="<?php echo $property['post_title']; ?>"
               style="width:<?php echo $image['width']; ?>px;height:auto;"/>
        </a>
      </div>
      <?php
      $html = ob_get_contents();
      ob_end_clean();
    } else {
      $html = '';
    }
    if ($args['return'] == 'true') {
      return $html;
    } else {
      echo $html;
    }
  }
}

if (!function_exists('returned_properties')) {
  /**
   * Gets returned property loop, and loads the property objects
   *
   * @since 1.17.3
   */
  function returned_properties($args = false)
  {
    global $wpp_query;
    $properties = array();
    foreach ($wpp_query['properties']['results'] as $property_id) {
      $properties[] = prepare_property_for_display($property_id, $args);
    }
    return $properties;
  }
}

if (!function_exists('have_properties')) {
  /**
   * Eulated have_posts
   *
   * @since 1.17.3
   */
  function have_properties()
  {
    global $wpp_query;
    if (!empty($wpp_query['properties']) && is_array($wpp_query['properties'])) {
      return true;
    }
    return false;
  }
}

if (!function_exists('is_property_overview_page')):
  /**
   * Figures out if current page is the property overview page
   *
   * @since 1.10
   *
   */
  function is_property_overview_page()
  {
    global $wp_query;
    if (!isset($wp_query)) {
      _doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1');
      return false;
    }
    return isset($wp_query->is_property_overview) ? $wp_query->is_property_overview : false;
  }
endif;

if (!function_exists('prepare_property_for_display')):
  /**
   * Runs all filters through property variables
   *
   * Main function for preparing the property object to be displayed on the front-end.
   * Same args are applied to main object, and child objects that are loade. So if gallery is not loaded for parent, it will not be loaded for children.
   *
   * Called in the_post() via WPP_F::the_post()
   *
   * @todo There is an issue with quotes being converted to &quot; and not working well when value has a shortcode.
   * @since 1.4
   *
   */
  function prepare_property_for_display($property, $args = false, $force = false)
  {
    global $wp_properties;

    if (empty($property)) {
      return;
    }
    // translate taxonomies labels before display it.
    if (isset($wp_properties['taxonomies'])) {
      $wp_properties['taxonomies'] = apply_filters('wpp::taxonomies::labels', $wp_properties['taxonomies']);
    }
    $_args = is_array($args) ? http_build_query($args) : (string)$args;

    /* Used to apply different filters depending on where the attribute is displayed. i.e. google_map_infobox  */
    $attribute_scope = (!empty($args['scope'])) ? $args['scope'] : false;

    $return_type = (is_object($property) ? 'object' : 'array');

    if (is_numeric($property)) {

      $property_id = $property;
      $property = get_property($property);

    } elseif (is_object($property)) {

      $property = (array)$property;
      $property_id = $property['ID'];

    } elseif (is_array($property)) {

      $property_id = $property['ID'];

    }

    //** Check if this function has already been done */
    if (is_array($property) && isset($property['system']['prepared_for_display']) && $force == false) {

      return $property;
    }

    //** Load property from cache, or function, if not passed */
    if (!is_array($property)) {

      if ($cache_property = wp_cache_get(md5('display_' . $property_id . $_args))) {
        return $cache_property;
      }

      //** Cache not found, load property */
      $property = (array)WPP_F::get_property($property_id, $args);
    }

    // Go through children properties
    if (isset($property['children']) && is_array($property['children'])) {
      foreach ($property['children'] as $child => $child_data) {
        $property['children'][$child] = prepare_property_for_display($child_data, $args);
      }
    }

    $attributes = ud_get_wp_property('property_stats', array());

    foreach ($property as $meta_key => $attribute_value) {
      //** Only execute shortcodes for defined property attributes to prevent different issues */
      if (!array_key_exists($meta_key, (array)$attributes)) {
        continue;
      }
      $attribute_data = WPP_F::get_attribute_data($meta_key);

      if ($meta_key === 'post_content') {
        die('<pre>' . print_r($attribute_data, true) . '</pre>');
      }
      //** Only executed shortcodes if the value isn't an array */
      if (!is_array($attribute_value)) {
        if ((!empty($args['do_not_execute_shortcodes']) && $args['do_not_execute_shortcodes'] == 'true') || $meta_key == 'post_content') {
          continue;
        }
        //** Determine if the current attribute is address and set it as display address */
        if ($meta_key == $wp_properties['configuration']['address_attribute'] && !empty($property['display_address'])) {
          $attribute_value = $property['display_address'];
          /*
            Replace address with generated taxonomy links.
            depend on add_display_address();
          */
          if (WPP_FEATURE_FLAG_WPP_LISTING_LOCATION) {
            $address_format = $wp_properties['configuration']['display_address_format'];
            preg_match_all('/\[(.*?)\]/', $address_format, $matches);
            if (isset($matches[1]) && is_array($matches[1])) {
              foreach ($matches[1] as $value) {
                $term_link = !empty($property[$value]) ? $property[$value] : "";
                if ($term_link && $term = get_term_by('name', $property[$value], 'wpp_location')) {
                  $term_link = "<a href='" . get_term_link($term->term_id) . "'>{$term->name}</a>";
                }
                $address_format = str_replace("[$value]", $term_link, $address_format);
              }
              $attribute_value = $address_format;
            }
          }
          /* Trim down trailing comma "," or space " " */
          $attribute_value = trim($attribute_value, ", ");
        }
        // No display formating is needed for wysiwyg because it's formatted.
        if (!empty($attribute_data['data_input_type']) && $attribute_data['data_input_type'] == 'wysiwyg') {
          $attribute_value = do_shortcode($attribute_value);
        } else {
          $attribute_value = do_shortcode(html_entity_decode($attribute_value));
          $attribute_value = str_replace("\n", "", nl2br($attribute_value));
        }

      }
      $attribute_value = apply_filters("wpp::attribute::display", $attribute_value, $meta_key);
      $property[$meta_key] = apply_filters("wpp_stat_filter_{$meta_key}", $attribute_value, $attribute_scope);
    }

    $property['system']['prepared_for_display'] = true;

    wp_cache_add(md5('display_' . $property_id . $_args), $property);

    if ($return_type == 'object') {
      return (object)$property;
    } else {
      return $property;
    }

  }
endif;

if (!function_exists('property_slideshow')):
  /**
   * DEPRECIATED FUNCTION. SHOULD BE REMOVED IN THE NEXT REALEASES. MAXIM PESHKOV
   * I don't see any places where this function is used.
   *
   * Returns property slideshow images, or single image if plugin not installed
   *
   * @since 1.0
   *
   */
  function property_slideshow($args = "")
  {
    global $wp_properties, $post;
    $defaults = array('force_single' => false, 'return' => false);
    $args = wp_parse_args($args, $defaults);
    if ($wp_properties[configuration][property_overview][display_slideshow] == 'false')
      return;
    ob_start();
    // Display slideshow if premium plugin exists and the property isn't set to hide slideshow
    if ($wp_properties[plugins][slideshow][status] == 'enabled' && !$post->disable_slideshow) {
      wpp_slideshow::display_property_slideshow(wpp_slideshow::get_property_slideshow_images($post->ID));
    } else {
      // Get slideshow image type for featured image
      if (!empty($post->slideshow)) {
        echo "<a href='{$post->featured_image_url}' class='fancybox_image'>";
        echo "<img src='{$post->slideshow}' alt='{$post->featured_image_title}' />";
        echo "</a>";
      }
    }
    $content = ob_get_contents();
    ob_end_clean();
    if (empty($content))
      return false;
    if ($return)
      return $content;
    echo $content;
  }
endif; // property_slideshow

if (!function_exists('get_property')) {
  /**
   *
   * Extends get_post by dumping all metadata into array
   *
   * @param $id
   * @param string $args
   *
   * @return bool|mixed|object|stdClass|void
   */
  function get_property($id, $args = "")
  {
    return \UsabilityDynamics\WPP\Property_Factory::get($id, $args);
  }
}

if (!function_exists('the_tagline')) {
  function the_tagline($before = '', $after = '', $echo = true)
  {
    global $post;

    $content = isset($post->tagline) ? $post->tagline : '';

    if (strlen($content) == 0) {
      return;
    }

    $content = $before . $content . $after;

    if ($echo) {
      echo $content;
    } else {
      return $content;
    }

  }
}

if (!function_exists('get_features')) {
  /**
   * @param string $args
   * @param bool $property
   * @return array|bool|int
   */
  function get_features($args = '', $property = false)
  {
    global $post;

    if (is_array($property)) {
      $property = (object)$property;
    }

    if (!$property) {
      $property = $post;
    }

    $args = wp_parse_args($args, array(
      'type' => 'property_feature',
      'format' => 'comma',
      'links' => true
    ));

    $features = get_the_terms($property->ID, $args['type']);

    $features_html = array();

    if ($features) {

      foreach ($features as $feature) {
        if ($args['links'] == 'true') {

          $link = get_term_link($feature->slug, $args['type']);
          if (is_wp_error($link)) {
            continue;
          }

          array_push($features_html, '<a href="' . $link . '">' . $feature->name . '</a>');
        } else {
          array_push($features_html, $feature->name);
        }
      }

      if ($args['format'] == 'comma') {
        echo implode($features_html, ", ");
      }
      if ($args['format'] == 'array') {
        return $features_html;
      }
      if ($args['format'] == 'count') {
        return (count($features) > 0 ? count($features) : false);
      }
      if ($args['format'] == 'list') {
        echo "<li>" . implode($features_html, "</li><li>") . "</li>";
      }
    }

  }
}

if (!function_exists('draw_stats')):
  /**
   * Returns printable array of property stats
   *
   *
   * @todo #property_stats is currently used in multiple instances when attribute list is displayed by groups.  Cannot remove to avoid breaking styles. - potanin@UD (11/5/2011)
   * @since 1.11
   * @args: exclude, return_blank, make_link
   */
  function draw_stats($args = false, $property = false)
  {
    global $wp_properties, $post;

    if (!$property) {
      $property = $post;
    }

    $property = prepare_property_for_display($property, false, false);

    if (is_array($property)) {
      $property = WPP_F::array_to_object($property);
    }

    $defaults = array(
      'sort_by_groups' => 'false',
      'display' => 'dl_list',
      'show_true_as_image' => 'false',
      'make_link' => 'true',
      'hide_false' => 'false',
      'first_alt' => 'false',
      'return_blank' => 'false',
      'include' => '',
      'exclude' => '',
      'make_terms_links' => 'false',
      'include_taxonomies' => 'false',
      'include_clsf' => 'attribute', // Show attributes or meta ( details ). Available value: "detail"
      'stats_prefix' => sanitize_key(WPP_F::property_label('singular'))
    );

    if (!empty($wp_properties['configuration']['property_overview']['sort_stats_by_groups'])) {
      $defaults['sort_by_groups'] = $wp_properties['configuration']['property_overview']['sort_stats_by_groups'];
    }

    if (!empty($wp_properties['configuration']['property_overview']['show_true_as_image'])) {
      $defaults['show_true_as_image'] = $wp_properties['configuration']['property_overview']['show_true_as_image'];
    }

    extract($args = wp_parse_args($args, $defaults), EXTR_SKIP);

    $property_stats = array();
    $groups = isset($wp_properties['property_groups']) ? (array)$wp_properties['property_groups'] : array();

    /**
     * Determine if we should draw meta data.
     * The functionality below is related to WPP2.0
     * Now it just adds compatibility with new Denali versions
     */
    if ($args['include_clsf'] === 'detail') {
      $sort_by_groups = 'false';
      if (!empty($wp_properties['property_meta'])) {
        foreach ($wp_properties['property_meta'] as $k => $v) {
          if ($k == 'tagline') {
            continue;
          }
          if (!empty($property->$k)) {
            $property_stats[$k] = array('label' => $v, 'value' => $property->$k);
          }
        }
      }
    } else {
      $property_stats = WPP_F::get_stat_values_and_labels($property, array('label_as_key' => 'false'));
    }

    /* Extend $property_stats with property taxonomy */
    if (($args['include_taxonomies'] === 'true' || $args['include_taxonomies'] === true) && !empty($wp_properties['taxonomies']) && is_array($wp_properties['taxonomies'])) {
      foreach ($wp_properties['taxonomies'] as $taxonomy => $data) {
        if ($data['public'] && empty($wp_properties['taxonomies'][$taxonomy]['hidden']))
          $property_stats[$taxonomy] = array('label' => $data['label'], 'value' => $property->$taxonomy);
      }
    }

    /** Include only passed attributes */
    if (!empty($include)) {
      $include = !is_array($include) ? explode(',', $include) : $include;
      foreach ((array)$property_stats as $k => $v) {
        if (!in_array($k, $include)) {
          unset($property_stats[$k]);
        }
      }
    }

    /** Exclude specific attributes from list */
    if (!empty($exclude)) {
      $exclude = !is_array($exclude) ? explode(',', $exclude) : $exclude;
      foreach ($exclude as $k) {
        if (isset($property_stats[$k])) {
          unset($property_stats[$k]);
        }
      }
    }

    if (empty($property_stats)) {
      return false;
    }

    //* Prepare values before display */
    $property_stats = apply_filters('wpp::draw_stats::attributes', $property_stats, $property, $args);

    $stats = array();

    foreach ($property_stats as $tag => $data) {

      if (empty($data['value'])) {
        continue;
      }

      $value = $data['value'];

      $attribute_data = UsabilityDynamics\WPP\Attributes::get_attribute_data($tag);

      //print_r($attribute_data);
      //** Do not show attributes that have value of 'value' if enabled */
      if ($args['hide_false'] == 'true' && $value == 'false') {
        continue;
      }

      //* Skip blank values (check after filters have been applied) */
      if ($args['return_blank'] == 'false' && empty($value)) {
        continue;
      }

      if (!is_array($value))
        $value = html_entity_decode($value);

      if (is_array($value) && isset($attribute_data['data_input_type']) && ($attribute_data['data_input_type'] == 'image_advanced' || $attribute_data['data_input_type'] == 'image_upload')) {
        $imgs = implode(',', $value);
        $img_html = do_shortcode("[gallery ids='$imgs']");
        $value = "<ul>" . $img_html . "</ul>";
      } elseif (isset($attribute_data['data_input_type']) && $attribute_data['data_input_type'] == 'oembed') {
        $value = wp_oembed_get(trim($value));
      } elseif (isset($attribute_data['data_input_type']) && $attribute_data['data_input_type'] == 'date') {
        $value = date_i18n(get_option('date_format'), strtotime($value));
      } elseif (isset($attribute_data['data_input_type']) && $attribute_data['data_input_type'] == 'datetime') {
        $value = date_i18n(get_option('date_format') . " " . get_option('time_format'), strtotime($value));
      } elseif (isset($attribute_data['data_input_type']) && $attribute_data['data_input_type'] == 'time') {
        $value = date_i18n(get_option('time_format'), strtotime($value));
      } elseif (isset($attribute_data['data_input_type']) && $attribute_data['data_input_type'] == 'file_advanced') {
        wp_enqueue_style('front-file-style', ud_get_wp_property()->path('static/styles/fields/front-file.css'), array(), ud_get_wp_property('version'));

        $file_html = '';
        $imgs = array();
        $files = array();
        foreach ($value as $file) {
          $isIMG = wp_attachment_is_image($file);
          if ($isIMG) {
            $imgs[] = $file;
          } else {
            $files[] = $file;
          }
        }

        if (count($imgs)) {
          $imgs = implode(",", $imgs);
          $file_html .= do_shortcode("[gallery ids='$imgs']");
        }

        if (count($files)) {
          foreach ($files as $file) {
            $li = '
            <li id="item_%s">
              <div class="rwmb-icon">%s</div>
              <div class="rwmb-info">
                <a href="%s" target="_blank">%s</a>
                <p>%s</p>
              </div>
            </li>';
            $mime_type = get_post_mime_type($file);
            $file_html .= sprintf(
              $li,
              $file,
              @wp_get_attachment_image($file, array(60, 60), true), // Wp genereate warning if image not found.
              wp_get_attachment_url($file),
              get_the_title($file),
              $mime_type
            );
          }
        }

        $value = "<ul class='rwmb-file'>" . $file_html . "</ul>";
      }

      // Taxonomies. Adding terms link, only if multi-value taxonomy.
      if (isset($attribute_data['storage_type']) && $attribute_data['storage_type'] == 'taxonomy' && isset($attribute_data['multiple']) && $attribute_data['multiple']) {

        $terms = wp_get_post_terms($property->ID, $tag);
        if (count($terms) == 0 || is_wp_error($terms)) {
          continue;
        }

        $value = "<ul>";

        foreach ($terms as $key => $term) {
          $term_link = $term->name;
          if (isset($make_terms_links) && $make_terms_links == "true") {
            $term_link = "<a href='" . get_term_link($term->term_id, $tag) . "'>{$term->name}</a>";
          }
          $value .= "<li class='property-terms property-term-{$term->slug}'>$term_link</li>";
        }

        $value .= "</ul>";

      }

      if ($tag == "property_type" || $tag == "wpp_listing_type") {
        $terms = wp_get_post_terms($property->ID, "wpp_listing_type");
        if (count($terms) && !is_wp_error($terms)) {
          foreach ($terms as $key => $term) {
            $value = "<a href='" . get_term_link($term->term_id, "wpp_listing_type") . "'>{$term->name}</a>";
          }
        }
      }

      //** Single "true" is converted to 1 by get_properties() we check 1 as well, as long as it isn't a numeric attribute */
      if (isset($attribute_data['data_input_type']) && $attribute_data['data_input_type'] == 'checkbox' && in_array(strtolower($value), array('true', '1', 'yes'))) {
        if ($args['show_true_as_image'] == 'true') {
          $value = '<div class="true-checkbox-image"></div>';
        } else {
          $value = __('Yes', ud_get_wp_property()->domain);
        }
      } else if ($value == 'false') {
        if ($args['show_true_as_image'] == 'true') {
          continue;
        }
        $value = __('No', ud_get_wp_property()->domain);
      }

      //* Make URLs into clickable links */
      $label = $data['label'];
      if (is_array($value)) {
        if ($args['make_link'] == 'true') {
          $link_value = array();
          foreach ($value as $val) {
            if (WPP_F::isURL($val)) {
              $link = "<a href='{$val}' title='{$label}' target='_blank'>{$label}</a>";
            } else {
              $term = get_term_by('name', $val, $tag);
              if ($term && !is_wp_error($term)) {
                $term_url = get_term_link($term->term_taxonomy_id, $term->taxonomy);
                if (!is_wp_error($term_url)) {
                  $link = "<a href='{$term_url}' title='{$term->name}'>{$term->name}</a>";
                }
              } else {
                $link = $val;
              }
            }
            array_push($link_value, $link);
          }
          $value = $link_value;
        }
        $value = implode(', ', $value);
      } else {
        if ($args['make_link'] == 'true') {
          if (WPP_F::isURL($value)) {
            $value = str_replace('&ndash;', '-', $value);
            $value = "<a href='{$value}' title='{$label}' target='_blank'>{$value}</a>";
          } else {
            $term = get_term_by('name', $value, $tag);
            if($term && !is_wp_error($term)){
              $term_url = get_term_link($term->term_taxonomy_id, $term->taxonomy);
              if (!is_wp_error($term_url)) {
                $value = "<a href='{$term_url}' title='{$term->name}'>{$term->name}</a>";
              }
            }
          }
        }
      }

      //* Make emails into clickable links */
      if ($args['make_link'] == 'true' && WPP_F::is_email($value)) {
        $value = "<a href='mailto:{$value}'>{$value}</a>";
      }

      $data['value'] = $value;
      $stats[$tag] = $data;
    }

    if (empty($stats)) {
      return false;
    }

    if ($args['display'] == 'array') {

      if ($args['sort_by_groups'] == 'true' && is_array($groups)) {

        $stats = sort_stats_by_groups($stats);

        foreach ($stats as $gslug => $gstats) {

          foreach ($gstats as $tag => $data) {
            $data['label'] = apply_filters('wpp::attribute::label', $data['label']);
            //check if the tag is property type to get the translated value for it
            $data['value'] = ($tag == 'property_type') ? apply_filters('wpp_stat_filter_property_type', $data['value']) : apply_filters('wpp::attribute::value', $data['value'], $tag);
            $gstats[$tag] = $data;
          }

          $stats[$gslug] = $gstats;
        }

      } else {

        foreach ($stats as $tag => $data) {
          $data['label'] = apply_filters('wpp::attribute::label', $data['label']);
          //check if the tag is property type to get the translated value for it
          $data['value'] = ($tag == 'property_type') ? apply_filters('wpp_stat_filter_property_type', $data['value']) : apply_filters('wpp::attribute::value', $data['value'], $tag);
          $stats[$tag] = $data;
        }

      }

      return $stats;

    }

    $alt = $args['first_alt'] == 'true' ? "" : "alt";

    //** Disable regular list if groups are NOT enabled, or if groups is not an array */
    if ($args['sort_by_groups'] != 'true' || !is_array($groups)) {

      if (!WPP_LEGACY_WIDGETS) echo '<div class="wpp_features_box wpp_features_box_without_groups">'; // for v2 widget

      foreach ($stats as $tag => $data) {

        $label = apply_filters('wpp::attribute::label', $data['label']);
        //check if the tag is property type to get the translated value for it
        $value = ($tag == 'property_type') ? apply_filters('wpp_stat_filter_property_type', $data['value']) : apply_filters('wpp::attribute::value', $data['value'], $tag);
        $alt = ($alt == "alt") ? "" : "alt";

        switch ($args['display']) {
          case 'dl_list':
            ?>
            <dt
              class="<?php echo $args['stats_prefix']; ?>_<?php echo $tag; ?> wpp_stat_dt_<?php echo $tag; ?>"><?php echo $label; ?>
              <span class="wpp_colon">:</span></dt>
            <dd
              class="<?php echo $args['stats_prefix']; ?>_<?php echo $tag; ?> wpp_stat_dd_<?php echo $tag; ?> <?php echo $alt; ?>"><?php echo $value; ?>
              &nbsp;</dd>
            <?php
            break;
          case 'list':
            ?>
            <li
              class="<?php echo $args['stats_prefix']; ?>_<?php echo $tag; ?> wpp_stat_plain_list_<?php echo $tag; ?> <?php echo $alt; ?>">
              <span class="attribute"><?php echo $label; ?><span class="wpp_colon">:</span></span>
              <span class="value"><?php echo $value; ?>&nbsp;</span>
            </li>
            <?php
            break;
          case 'plain_list':
            ?>
            <span class="<?php echo $args['stats_prefix']; ?>_<?php echo $tag; ?> attribute"><?php echo $label; ?>
              :</span>
            <span class="<?php echo $args['stats_prefix']; ?>_<?php echo $tag; ?> value"><?php echo $value; ?>
              &nbsp;</span>
            <br/>
            <?php
            break;
          case 'detail':
            ?>
            <h4 class="wpp_attribute"><?php echo $label; ?><span class="separator">:</span></h4>
            <p class="value"><?php echo $value; ?>&nbsp;</p>
            <?php
            break;
        }
      }

      if (!WPP_LEGACY_WIDGETS) echo '</div>'; // for v2 widget

    } else {

      $stats_by_groups = sort_stats_by_groups($stats);
      $main_stats_group = $wp_properties['configuration']['main_stats_group'];

      if (!WPP_LEGACY_WIDGETS) echo '<div class="wpp_features_box">'; // for v2 widget

      foreach ($stats_by_groups as $gslug => $gstats) {
        ?>
        <div class="wpp_feature_list">
          <?php
          if ($main_stats_group != $gslug || !@array_key_exists($gslug, $groups)) {
            $group_name = (@array_key_exists($gslug, $groups) ? $groups[$gslug]['name'] : __('Other', ud_get_wp_property()->domain));
            ?>
            <h2 class="wpp_stats_group"><?php echo $group_name; ?></h2>
            <?php
          }

          switch ($args['display']) {
            case 'dl_list':
              ?>
              <dl class="wpp_property_stats overview_stats">
                <?php foreach ($gstats as $tag => $data) : ?>
                  <?php
                  $label = apply_filters('wpp::attribute::label', $data['label']);
                  //check if the tag is property type to get the translated value for it
                  $value = ($tag == 'property_type') ? apply_filters('wpp_stat_filter_property_type', $data['value']) : $data['value'];
                  ?>
                  <?php $alt = ($alt == "alt") ? "" : "alt"; ?>
                  <dt
                    class="<?php echo $stats_prefix; ?>_<?php echo $tag; ?> wpp_stat_dt_<?php echo $tag; ?>"><?php echo $label; ?></dt>
                  <dd
                    class="<?php echo $stats_prefix; ?>_<?php echo $tag; ?> wpp_stat_dd_<?php echo $tag; ?> <?php echo $alt; ?>"><?php echo $value; ?>
                    &nbsp;</dd>
                <?php endforeach; ?>
              </dl>
              <?php
              break;
            case 'list':
              ?>
              <ul class="overview_stats wpp_property_stats list">
                <?php foreach ($gstats as $tag => $data) : ?>
                  <?php
                  $label = apply_filters('wpp::attribute::label', $data['label']);
                  //check if the tag is property type to get the translated value for it
                  $value = ($tag == 'property_type') ? apply_filters('wpp_stat_filter_property_type', $data['value']) : apply_filters('wpp::attribute::value', $data['value'], $tag);
                  $alt = ($alt == "alt") ? "" : "alt";
                  ?>
                  <li
                    class="<?php echo $stats_prefix; ?>_<?php echo $tag; ?> wpp_stat_plain_list_<?php echo $tag; ?> <?php echo $alt; ?>">
                    <span class="attribute"><?php echo $label; ?>:</span>
                    <span class="value"><?php echo $value; ?>&nbsp;</span>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php
              break;
            case 'plain_list':
              foreach ($gstats as $tag => $data) {
                $label = apply_filters('wpp::attribute::label', $data['label']);
                //check if the tag is property type to get the translated value for it
                $value = ($tag == 'property_type') ? apply_filters('wpp_stat_filter_property_type', $data['value']) : $data['value'];
                if (WPP_LEGACY_WIDGETS) { // If use old widget
                  ?>
                  <span class="<?php echo $stats_prefix; ?>_<?php echo $tag; ?> attribute"><?php echo $label; ?>
                    :</span>
                  <span class="<?php echo $stats_prefix; ?>_<?php echo $tag; ?> value"><?php echo $value; ?>
                    &nbsp;</span>
                  <br/>
                  <?php
                } else {
                  ?>
                  <div class="wpp_attribute_row">
                    <span class="<?php echo $stats_prefix; ?>_<?php echo $tag; ?> attribute"><?php echo $label; ?>
                      :</span>
                    <span class="<?php echo $stats_prefix; ?>_<?php echo $tag; ?> value"><?php echo $value; ?>
                      &nbsp;</span>
                  </div>
                  <?php
                }
              }
              break;
            case 'detail':
              foreach ($gstats as $tag => $data) {
                $label = apply_filters('wpp::attribute::label', $data['label']);
                //check if the tag is property type to get the translated value for it
                $value = ($tag == 'property_type') ? apply_filters('wpp_stat_filter_property_type', $data['value']) : $data['value'];
                if (WPP_LEGACY_WIDGETS) { // If use old widget
                  ?>
                  <strong class="wpp_attribute <?php echo $stats_prefix; ?>_<?php echo $tag; ?>"><?php echo $label; ?>
                    <span class="separator">:</span></strong>
                  <p class="value"><?php echo $value; ?>&nbsp;</p>
                  <br/>
                  <?php
                } else {
                  ?>
                  <div class="wpp_attribute_row">
                    <strong
                      class="wpp_attribute <?php echo $stats_prefix; ?>_<?php echo $tag; ?>"><?php echo $label; ?>
                      <span class="separator">:</span></strong>
                    <p class="value"><?php echo $value; ?>&nbsp;</p>
                  </div>
                  <?php
                }
              }
              ?>
              <?php
              break;
          }
          ?>
        </div>
        <?php
      }

      if (!WPP_LEGACY_WIDGETS) echo '</div>'; // for v2 widget

    }

  }
endif;

/**
 *
 * Sorts property stats by groups.
 *
 * Takes a passed array of attributes, and breaks them up into their groups.
 *
 * @param array $stats Property stats
 *
 * @return array $stats Modified array of stats which sorted by groups
 * @author Maxim Peshkov
 */
if (!function_exists('sort_stats_by_groups')):
  function sort_stats_by_groups($stats = false)
  {
    global $wp_properties;

    if (empty($stats) || !is_array($stats)) {
      return false;
    }

    //** Get group deta */
    $groups = isset($wp_properties['property_groups']) ? $wp_properties['property_groups'] : false;
    /** Get attribute-group association */
    $stats_groups = isset($wp_properties['property_stats_groups']) ? $wp_properties['property_stats_groups'] : false;

    if (!is_array($groups) || !is_array($stats_groups)) {
      return false;
    }

    $group_keys = array_keys((array)$wp_properties['property_groups']);

    //** Get group from settings, or set to first group as default */
    $main_stats_group = (!empty($wp_properties['configuration']['main_stats_group']) ? $wp_properties['configuration']['main_stats_group'] : $group_keys[0]);

    $filtered_stats = array($main_stats_group => array());

    foreach ((array)$stats as $slug => $data) {

      $g_slug = !empty($stats_groups[$slug]) ? $stats_groups[$slug] : false;

      //** Handle adding special attributes to groups automatically - only if they do not have groups set. */
      if (!$g_slug) {
        switch ($slug) {
          case 'property_type':
            $g_slug = $main_stats_group;
            break;
          case 'city':
            if (empty($stats_groups['city'])) {
              $g_slug = $main_stats_group;
            } else {
              $g_slug = '_other';
            }
            break;
          default:
            $g_slug = '_other';
            break;
        }
      }

      //** Build array of attributes in groups */
      $filtered_stats[$g_slug][$slug] = $data;
    }

    //** Cycle back through to make sure we don't have any empty groups */
    foreach ($filtered_stats as $key => $data) {
      if (empty($data)) {
        unset($filtered_stats[$key]);
      }
    }

    //** Sort by saved groups order. */
    $main_ordered = array();
    $ordered = array();
    foreach ($group_keys as $key) {
      if (array_key_exists($key, $filtered_stats)) {
        if ($key == $main_stats_group) {
          $main_ordered[$key] = $filtered_stats[$key];
        } else {
          $ordered[$key] = $filtered_stats[$key];
        }
        unset($filtered_stats[$key]);
      }
    }

    $filtered_stats = $main_ordered + $ordered + $filtered_stats;

    //echo "<pre>";print_r($filtered_stats);echo "</pre>";die();
    return $filtered_stats;
  }
endif;

/**
 * Draws search form
 *
 *
 * @return array|$wp_properties
 * @since 0.57
 * @version 1.14
 *
 */
if (!function_exists('draw_property_search_form')):
  function draw_property_search_form($args = false)
  {
    global $wp_properties;

    if (WPP_LEGACY_WIDGETS) {
      WPP_F::force_style_inclusion('jquery-ui-datepicker');
    } else {
      wp_enqueue_script('wpp-search-form');
    }

    WPP_F::force_script_inclusion('wpp-jquery-number-format');
    WPP_F::force_script_inclusion('jquery-ui-datepicker');
    WPP_F::force_script_inclusion('uisf-date');

    $args = wp_parse_args($args, array(
      'search_attributes' => false,
      'searchable_property_types' => false,
      'use_pagination' => 'on',
      'per_page' => '10',
      'group_attributes' => false,
      'strict_search' => false,
      'instance_id' => false,
      'sort_order' => false,
      'cache' => true
    ));


    if (empty($args['search_attributes']) && isset($args['searchable_attributes'])) {
      $args['search_attributes'] = $args['searchable_attributes'];
    }

    extract($args, EXTR_SKIP);
    $search_values = array();
    $property_type_flag = false;

    //** Bail if no search attributes passed */
    if (!is_array($args['search_attributes'])) {
      return;
    }

    $property_stats = $wp_properties['property_stats'];

    if (!isset($property_stats['property_type'])) {
      $property_stats['property_type'] = sprintf(__('%s Type', ud_get_wp_property()->domain), WPP_F::property_label());
    }

    //** Load search values for attributes (from cache, or generate) */
    if (!empty($search_attributes) && !empty($searchable_property_types)) {
      $search_values = WPP_F::get_search_values($search_attributes, $searchable_property_types, $args['cache'], $args['instance_id']);
    }

    //** This looks clumsy - potanin@UD */
    if (array_key_exists('property_type', array_fill_keys($search_attributes, 1)) && is_array($searchable_property_types) && count($searchable_property_types) > 1) {
      $spt = array_fill_keys($searchable_property_types, 1);
      if (!empty($wp_properties['property_types'])) {
        foreach ($wp_properties['property_types'] as $key => $value) {
          if (array_key_exists($key, $spt)) {
            $search_values['property_type'][$key] = $value;
          }
        }
        if (isset($search_values['property_type']) && count($search_values['property_type']) <= 1) {
          unset ($search_values['property_type']);
        }
      }
    }

    $template_found = \WPP_F::get_template_part(array(
      WPP_LEGACY_WIDGETS ? "property-search-form" : "property-search-form-v2"
    ), array(ud_get_wp_property()->path('static/views', 'dir')));

    if ($template_found) {
      include $template_found;
    }
    
  }
endif;

/**
 * Draws a search form element
 *
 *
 * @return array|$wp_properties
 * @since 1.22.1
 * @version 1.14
 *
 */
if (!function_exists('wpp_render_search_input')):
  function wpp_render_search_input($args = false)
  {
    global $wp_properties;
    extract($args = wp_parse_args($args, array(
      'type' => 'input',
      'input_type' => false,
      'search_values' => false,
      'attrib' => false,
      'random_element_id' => 'wpp_search_element_' . rand(1000, 9999),
      'value' => false,
      'placeholder' => false
    )));

    $attribute_data = UsabilityDynamics\WPP\Attributes::get_attribute_data($args['attrib']);

    if (!empty($args['input_type'])) {
      $use_input_type = $args['input_type'];
    } else {
      $use_input_type = isset($wp_properties['searchable_attr_fields'][$attrib]) ? $wp_properties['searchable_attr_fields'][$attrib] : false;
    }

    ob_start();

    if (!empty($use_input_type)) {
      switch ($use_input_type) {
        case 'input':
          ?>
          <input id="<?php echo $random_element_id; ?>" class="<?php echo $attribute_data['ui_class']; ?>"
                 name="wpp_search[<?php echo $attrib; ?>]" value="<?php echo $value; ?>"
                 placeholder="<?php echo $placeholder; ?>" type="text"/>
          <?php
          break;
        case 'range_input':
          /* Determine if $value has correct format, and if not - fix it. */
          $value = (!is_array($value) ? array('min' => '', 'max' => '') : $value);
          $value['min'] = (in_array('min', $value) ? $value['min'] : '');
          $value['max'] = (in_array('max', $value) ? $value['max'] : '');
          ?>
          <input id="<?php echo $random_element_id; ?>"
                 class="wpp_search_input_field wpp_range_field wpp_search_input_field_min wpp_search_input_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
                 type="text" name="wpp_search[<?php echo $attrib; ?>][min]" value="<?php echo $value['min']; ?>"
                 placeholder="<?php _e('Min', ud_get_wp_property()->domain); ?>"/>
          <span class="wpp_dash">-</span>
          <input
            class="wpp_search_input_field wpp_range_field wpp_search_input_field_max wpp_search_input_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
            type="text" name="wpp_search[<?php echo $attrib; ?>][max]" value="<?php echo $value['max']; ?>"
            placeholder="<?php _e('Max', ud_get_wp_property()->domain); ?>"/>
          <?php
          break;
        case 'range_dropdown':
          ?>
          <?php $grouped_values = group_search_values($search_values[$attrib]); ?>
          <select id="<?php echo $random_element_id; ?>"
                  class="wpp_search_select_field wpp_search_select_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
                  name="wpp_search[<?php echo $attrib; ?>][min]">
            <option value="-1"><?php _e('Any', ud_get_wp_property()->domain) ?></option>
            <?php foreach ($grouped_values as $v) : ?>
              <option
                value='<?php echo (int)$v; ?>' <?php if (isset($value['min']) && $value['min'] == $v) echo " selected='true' "; ?>>
                <?php echo apply_filters("wpp_stat_filter_{$attrib}", $v); ?> +
              </option>
            <?php endforeach; ?>
          </select>
          <?php
          break;
        case 'advanced_range_dropdown':
          ?>
          <?php $grouped_values = !empty($search_values[$attrib]) ? $search_values[$attrib] : group_search_values($search_values[$attrib]); ?>
          <select id="<?php echo $random_element_id; ?>"
                  class="wpp_search_select_field wpp_range_field wpp_search_select_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
                  name="wpp_search[<?php echo $attrib; ?>][min]">
            <option value=""><?php _e('Min', ud_get_wp_property()->domain) ?></option>
            <?php foreach ($grouped_values as $v) : ?>
              <option
                value='<?php echo (int)$v; ?>' <?php if (isset($value['min']) && $value['min'] == (int)$v) echo " selected='selected' "; ?>>
                <?php echo apply_filters("wpp_stat_filter_{$attrib}", $v); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="delimiter">-</span>
          <select id="<?php echo $random_element_id; ?>"
                  class="wpp_search_select_field wpp_range_field wpp_search_select_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
                  name="wpp_search[<?php echo $attrib; ?>][max]">
            <option value=""><?php _e('Max', ud_get_wp_property()->domain) ?></option>
            <?php foreach ($grouped_values as $v) : ?>
              <option
                value='<?php echo (int)$v; ?>' <?php if (isset($value['max']) && $value['max'] == (int)$v) echo " selected='selected' "; ?>>
                <?php echo apply_filters("wpp_stat_filter_{$attrib}", $v); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php
          break;
        case 'dropdown':
          ?>
          <select id="<?php echo $random_element_id; ?>"
                  class="wpp_search_select_field wpp_search_select_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
                  name="wpp_search[<?php echo $attrib; ?>]">
            <option value="-1"><?php _e('Any', ud_get_wp_property()->domain) ?></option>
            <?php foreach ($search_values[$attrib] as $v) : ?>
              <option
                value="<?php echo esc_attr($v); ?>" <?php selected($value, $v); ?>><?php echo esc_attr(apply_filters("wpp_stat_filter_{$attrib}", $v)); ?></option>
            <?php endforeach; ?>
          </select>
          <?php
          break;
        case 'multi_checkbox':
          ?>
          <ul id="wpp_multi_checkbox" class="wpp_multi_checkbox <?php echo $attribute_data['ui_class']; ?>">
            <?php foreach ($search_values[$attrib] as $value_label) : ?>
              <?php $unique_id = rand(10000, 99999); ?>
              <li>
                <input
                  name="wpp_search[<?php echo $attrib; ?>][]" <?php echo(is_array($value) && in_array($value_label, $value) ? 'checked="true"' : ''); ?>
                  id="wpp_attribute_checkbox_<?php echo $unique_id; ?>" type="checkbox"
                  value="<?php echo $value_label; ?>"/>
                <label for="wpp_attribute_checkbox_<?php echo $unique_id; ?>"
                       class="wpp_search_label_second_level"><?php echo $value_label; ?></label>
              </li>
            <?php endforeach; ?>
          </ul>
          <?php
          break;
        case 'checkbox':
          ?>
          <input id="<?php echo $random_element_id; ?>" type="checkbox"
                 class="<?php echo $attribute_data['ui_class']; ?>"
                 name="wpp_search[<?php echo $attrib; ?>]" <?php checked($value, 'true'); ?> value="true"/>
          <?php
          break;
        case 'range_date':
          ?>
          <input id="<?php echo $random_element_id; ?>"
                 class="uisf-date wpp_search_input_field wpp_range_field wpp_search_date_field_from wpp_search_date_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
                 type="text" name="wpp_search[<?php echo $attrib; ?>][from]" value="" placeholder=""/>
          <span class="wpp_dash">-</span>
          <input
            class="uisf-date wpp_search_input_field wpp_range_field wpp_search_date_field_to wpp_search_date_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
            type="text" name="wpp_search[<?php echo $attrib; ?>][to]" value="" placeholder=""/>
          <?php
          break;
        default:
          echo apply_filters('wpp::render_search_input::custom', '', $args);
          break;
      }
    } else {
      ?>
      <?php if (empty($search_values[$attrib])) : ?>
        <input id="<?php echo $random_element_id; ?>"
               class="wpp_search_input_field wpp_search_input_field_<?php echo $attrib; ?>"
               name="wpp_search[<?php echo $attrib; ?>]" value="<?php echo $value; ?>" type="text"/>
        <?php //* Determine if attribute is a numeric range */ ?>
      <?php elseif (WPP_F::is_numeric_range($search_values[$attrib])) : ?>
        <input
          class="wpp_search_input_field wpp_range_field wpp_range_input wpp_search_input_field_min wpp_search_input_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
          type="text" name="wpp_search[<?php echo $attrib; ?>][min]"
          value="<?php echo isset($value['min']) ? $value['min'] : ''; ?>"/>
        <span class="wpp_dash">-</span>
        <input
          class="wpp_search_input_field wpp_range_field wpp_range_input wpp_search_input_field_max wpp_search_input_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
          type="text" name="wpp_search[<?php echo $attrib; ?>][max]"
          value="<?php echo isset($value['max']) ? $value['max'] : ''; ?>"/>
      <?php else : ?>
        <?php /* Not a numeric range */ ?>
        <select id="<?php echo $random_element_id; ?>"
                class="wpp_search_select_field wpp_search_select_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"
                name="wpp_search[<?php echo $attrib; ?>]">
          <option
            value="<?php echo(($attrib == 'property_type' && is_array($search_values[$attrib])) ? implode(',', (array_flip($search_values[$attrib]))) : '-1'); ?>"><?php _e('Any', ud_get_wp_property()->domain) ?></option>
          <?php foreach ($search_values[$attrib] as $key => $v) : ?>
            <option
              value='<?php echo(($attrib == 'property_type') ? $key : $v); ?>' <?php if ($value == (($attrib == 'property_type') ? $key : $v)) echo " selected='true' "; ?>>
              <?php echo apply_filters("wpp_stat_filter_{$attrib}", $v); ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
      <?php
    }

    echo apply_filters('wpp_render_search_input', ob_get_clean(), $args);
  }
endif;

if (!function_exists('wpp_get_image_link')):
  /*
   * Returns Image link (url)
   *
   * If image with the current size doesn't exist, we try to generate it.
   * If image cannot be resized, the URL to the main image (original) is returned.
   *
   * @todo Add something to check if requested image size is bigger than the original, in which case cannot be "resized"
   * @todo Add a check to see if the specified image dimensions have changed. Right now only checks if slug exists, not the actualy size.
   *
   * @param string $size. Size name
   * @param string(integer) $thumbnail_link. attachment_id
   * @param string $args. Additional conditions
   * @return string or array. Default is string (image link)
   */
  function wpp_get_image_link($attachment_id, $size, $args = array())
  {
    global $wp_properties;

    if (empty($size) || empty($attachment_id)) {
      return false;
    }
    //** Optional arguments */
    $defaults = array(
      'return' => 'string'
    );

    extract(wp_parse_args($args, $defaults), EXTR_SKIP);

    // If media item has "_is_remote" meta, we never try to resize it.
    $_is_remote = get_post_meta($attachment_id, '_is_remote', true);

    if (!$_is_remote && isset($wp_properties['configuration']['automatically_regenerate_thumbnail']) && $wp_properties['configuration']['automatically_regenerate_thumbnail'] == 'true') {
      //* Do the default action of attempting to regenerate image if needed. */
      $uploads_dir = wp_upload_dir();
      //** Get image path from meta table (if this doesn't exist, nothing we can do */
      if ($_wp_attached_file = get_post_meta($attachment_id, '_wp_attached_file', true)) {
        $attachment_path = $uploads_dir['basedir'] . '/' . $_wp_attached_file;
      } else {
        return false;
      }
      //** Get meta of main image (may not exist if XML import) */
      $image_meta = wp_get_attachment_metadata($attachment_id);
      //** Real URL of full image */
      $img_url = wp_get_attachment_url($attachment_id);
      //** Filenme of image */
      $img_url_basename = wp_basename($img_url);
      if (isset($image_meta['sizes'][$size]) && !empty($image_meta['sizes'][$size]['file'])) {
        //** Image image meta exists, we get the path and URL to the requested image size */
        $requested_size_filepath = str_replace($img_url_basename, $image_meta['sizes'][$size]['file'], $attachment_path);
        $requested_image_url = str_replace($img_url_basename, $image_meta['sizes'][$size]['file'], $img_url);
        $image_path = $requested_size_filepath;
        //** Meta is there, now check if file still exists on disk */
        if (file_exists($requested_size_filepath)) {
          $requested_image_exists = true;
        }
      }
      if (isset($requested_image_exists) && $requested_image_exists) {
        $i[0] = $requested_image_url;
      } else {
        //** Image with the current size doesn't exist. Try generate file */
        if (WPP_F::generate_image($attachment_id, $size)) {
          //** Get Image data again */
          $image = image_downsize($attachment_id, $size);
          if (is_array($image)) {
            $i = $image;
          }
        } else {
          //** Failure because image could not be resized. Return original URL */
          $i[0] = $img_url;
          $image_path = str_replace($uploads_dir['baseurl'], $uploads_dir['basedir'], $img_url);
        }
      }
    } else {

      $default_return = wp_get_attachment_image_src($attachment_id, $size, true);
      $i[0] = $default_return[0];
      $i[1] = $default_return[1];
      $i[2] = $default_return[2];

    }

    // Must check that $image_path exists, for remotely stored images this will be empty and will cause an an error within getimagesize.
    if (isset($image_path) && $image_path) {
      $getimagesize = @getimagesize($image_path);
    } else {
      $getimagesize = array('', '');
    }

    //** Get true image dimensions or returned URL */
    $i[1] = $getimagesize[0];
    $i[2] = $getimagesize[1];
    //** Return image data as requested */
    if ($i) {
      switch ($return) {
        case 'array':
          if ($i[1] == 0 || $i[2] == 0) {
            $s = WPP_F::image_sizes($size);
            $i[1] = $s['width'];
            $i[2] = $s['height'];
          }
          return array(
            'link' => $i[0],
            'src' => $i[0],
            'url' => $i[0],
            'width' => $i[1],
            'height' => $i[2]
          );
          break;
        case 'string':
        default:
          return $i[0];
          break;
      }
    }
    return false;
  }
endif;

if (!function_exists('wpp_inquiry_form')):
  /*
   * Overwrites default Wordpress function comment_form()
   * @param array $args Options for strings, fields etc in the form
   * @param mixed $post_id Post ID to generate the form for, uses the current post if null
   * @return void
   */
  function wpp_inquiry_form($args = array(), $post_id = null)
  {
    global $post, $user_identity, $id;
    $inquiry = true;
    /* Determine if post is property */
    if ($post->post_type != 'property') {
      $inquiry = false;
    }
    $inquiry = apply_filters('pre_render_inquiry_form', $inquiry);
    if (!$inquiry) {
      /* If conditions are failed, use default Wordpress function */
      comment_form($args, $post_id);
    } else {
      /* The functionality below based on comment_form() function */
      if (null === $post_id) {
        $post_id = $id;
      } else {
        $id = $post_id;
      }
      $commenter = wp_get_current_commenter();
      $req = get_option('require_name_email');
      $aria_req = ($req ? " aria-required='true'" : '');
      $fields = array(
        'author' => '<p class="comment-form-author">' . '<label for="author">' . __('Name') . '</label> ' . ($req ? '<span class="required">*</span>' : '') .
          '<input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30"' . $aria_req . ' /></p>',
        'email' => '<p class="comment-form-email"><label for="email">' . __('Email') . '</label> ' . ($req ? '<span class="required">*</span>' : '') .
          '<input id="email" name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" size="30"' . $aria_req . ' /></p>',
        'url' => '<p class="comment-form-url"><label for="url">' . __('Website') . '</label>' .
          '<input id="url" name="url" type="text" value="' . esc_attr($commenter['comment_author_url']) . '" size="30" /></p>',
      );
      $required_text = sprintf(' ' . __('Required fields are marked %s'), '<span class="required">*</span>');
      $defaults = array(
        'fields' => apply_filters('comment_form_default_fields', $fields),
        'comment_field' => '<p class="comment-form-comment"><label for="comment">' . _x('Comment', 'noun') . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>',
        'must_log_in' => '<p class="must-log-in">' . sprintf(__('You must be <a href="%s">logged in</a> to post a comment.'), wp_login_url(apply_filters('the_permalink', get_permalink($post_id)))) . '</p>',
        'logged_in_as' => '<p class="logged-in-as">' . sprintf(__('Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>'), admin_url('profile.php'), $user_identity, wp_logout_url(apply_filters('the_permalink', get_permalink($post_id)))) . '</p>',
        'comment_notes_before' => '<p class="comment-notes">' . __('Your email address will not be published.') . ($req ? $required_text : '') . '</p>',
        'comment_notes_after' => '<p class="form-allowed-tags">' . sprintf(__('You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes: %s'), ' <code>' . allowed_tags() . '</code>') . '</p>',
        'id_form' => 'commentform',
        'id_submit' => 'submit',
        'title_reply' => __('Leave a Reply'),
        'title_reply_to' => __('Leave a Reply to %s'),
        'cancel_reply_link' => __('Cancel reply'),
        'label_submit' => __('Post Comment'),
      );
      $args = wp_parse_args($args, apply_filters('comment_form_defaults', $defaults));
      ?>
      <?php if (comments_open()) : ?>
        <?php do_action('comment_form_before'); ?>
        <div id="respond">
          <h3 id="reply-title"><?php comment_form_title($args['title_reply'], $args['title_reply_to']); ?>
            <small><?php cancel_comment_reply_link($args['cancel_reply_link']); ?></small>
          </h3>
          <?php if (get_option('comment_registration') && !is_user_logged_in()) : ?>
            <?php echo $args['must_log_in']; ?>
            <?php do_action('comment_form_must_log_in_after'); ?>
          <?php else : ?>
            <form action="<?php echo site_url('/wp-comments-post.php'); ?>" method="post"
                  id="<?php echo esc_attr($args['id_form']); ?>">
              <?php do_action('comment_form_top'); ?>
              <?php if (is_user_logged_in()) : ?>
                <?php echo apply_filters('comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity); ?>
                <?php do_action('comment_form_logged_in_after', $commenter, $user_identity); ?>
              <?php endif; ?>
              <?php echo $args['comment_notes_before']; ?>
              <?php
              do_action('comment_form_before_fields');
              foreach ((array)$args['fields'] as $name => $field) {
                echo apply_filters("comment_form_field_{$name}", $field) . "\n";
              }
              do_action('comment_form_after_fields');
              ?>
              <?php echo apply_filters('comment_form_field_comment', $args['comment_field']); ?>
              <?php echo $args['comment_notes_after']; ?>
              <p class="form-submit">
                <input name="submit" type="submit" id="<?php echo esc_attr($args['id_submit']); ?>"
                       value="<?php echo esc_attr($args['label_submit']); ?>" class="btn"/>
                <?php comment_id_fields($post_id); ?>
              </p>
              <?php do_action('comment_form', $post_id); ?>
            </form>
          <?php endif; ?>
        </div><!-- #respond -->
        <?php do_action('comment_form_after'); ?>
      <?php else : ?>
        <?php do_action('comment_form_comments_closed'); ?>
      <?php endif; ?>
      <?php
    }
  }
endif;

if (!function_exists('wpp_css')):

  /**
   * It returns specific classes for element.
   * This function is just wrapper.
   * See: WPP_F::get_css_classes();
   *
   * @author peshkov@UD
   * @version 0.1
   *
   * @param string $element [required] It's used for determine which classes should be filtered. It can be set of template and element: "{template}::{element}"
   * @param bool $classes [optional] Set of classes
   * @param bool $return [optional] If false, prints classes. If true returns array of classes
   * @param array $args [optional] Any set of additional arguments which can be needed.
   * @return array|echo
   */
  function wpp_css($element = '', $classes = false, $return = false, $args = array())
  {
    $args = array_merge((array)$args, array(
      'instance' => 'wpp',
      'element' => $element,
      'classes' => $classes,
      'return' => $return,
    ));

    if (is_callable(array('WPP_F', 'get_css_classes'))) {
      return WPP_F::get_css_classes($args);
    }
    return false;

  }

endif;


/**
 * Will class to body if on mobile device.
 */
add_filter('body_class', 'wpp_is_mobile_body_class');
function wpp_is_mobile_body_class($classes)
{
  if (wp_is_mobile())
    $classes[] = 'wpp_is_mobile';
  return $classes;
}