<?php
/*
Name: PDF Flyer
Feature ID: 6
Version: 2.1.7
Minimum Core Version: 1.38.3
Internal Slug: property_pdf
JS Slug: wpp_property_pdf
Global Variable: wpp_pdf_flyer
Class: class_wpp_pdf_flyer
Description: Create flyers for properties on the fly.
*/

/** Class including moved into functions in which it is really required. korotkov@ud */
/** @include_once(WPP_Path.'third-party/tcpdf/tcpdf.php'); */

add_action('wpp_init', array('class_wpp_pdf_flyer', 'init'));
add_action('wpp_pre_init', array('class_wpp_pdf_flyer', 'pre_init'));

/* Any front-end Functions */
add_action('template_redirect', array('class_wpp_pdf_flyer', 'template_redirect'));

class class_wpp_pdf_flyer {

  /*
   * (custom) Capability to manage the current feature
   */
  static protected $capability = "manage_wpp_pdfflyer";

  /**
  * Special functions that must be called prior to init
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function pre_init() {

    /* Preserve PDF List settings */
    add_filter('wpp_settings_save', array('class_wpp_pdf_flyer', 'wpp_settings_save'), 0, 2);
    /* Determine, check and open PDF List file in query (request) */
    add_action("parse_request", array('class_wpp_pdf_flyer', 'parse_request_for_pdf_list'), 11);
    add_filter("query_vars", array('class_wpp_pdf_flyer', "add_pdf_query_vars"));

    /* Add capability */
    add_filter('wpp_capabilities', array('class_wpp_pdf_flyer', "add_capability"));
  }

  /**
  * Called at end of WPP init hook, in WP init hook
  *
  * Run-time settings are stored in $wpp_property_pdf['runtime']
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function init() {
    global $wpp_pdf_flyer, $wp_properties;

    if(current_user_can(self::$capability)) {
      /* Add to settings page nav */
      add_filter('wpp_settings_nav', array('class_wpp_pdf_flyer', 'settings_nav'));
      /* Add Settings Page */
      add_action('wpp_settings_content_pdf_flyer', array('class_wpp_pdf_flyer', 'settings_page'));

      //For Admin panel
      add_action('add_meta_boxes', array('class_wpp_pdf_flyer','add_metabox'));
      add_action('save_post', array('class_wpp_pdf_flyer','save_post'));
    }

    //** Load settings */
    $wpp_pdf_flyer = (array)$wp_properties['configuration']['feature_settings']['wpp_pdf_flyer'];

    //** Add shortcode */
    add_shortcode('property_flyer', array('class_wpp_pdf_flyer', 'shortcode_pdf_flyer'));
    add_shortcode('wpp_pdf_list', array('class_wpp_pdf_flyer', 'shortcode_pdf_list'));

    /* Creates the PDF on the fly when a property is saved */

    $callback = ( isset( $wpp_pdf_flyer['generate_flyers_on_the_fly'] ) && $wpp_pdf_flyer[ 'generate_flyers_on_the_fly' ] == 'on' ) ? 'check_flyer_pdf' : 'create_flyer_pdf';
    add_action('save_property', array('class_wpp_pdf_flyer', $callback ));

    /* Add "View Flyer" message to overview page actions */
    add_filter('page_row_actions', array('class_wpp_pdf_flyer', 'page_row_actions'),0,2);

    /* Testing actions from the flyer. Should be in functions.php */
    add_action('wpp_flyer_left_column', array('class_wpp_pdf_flyer', 'flyer_left_column'), 10, 2);
    add_action('wpp_flyer_middle_column', array('class_wpp_pdf_flyer', 'flyer_middle_column'), 10, 2);
    add_action('wpp_flyer_right_column', array('class_wpp_pdf_flyer', 'flyer_right_column'), 10, 2);

    add_action('wpp_settings_help_tab', array('class_wpp_pdf_flyer', 'wpp_settings_help_tab'));

    add_action('admin_menu', array('class_wpp_pdf_flyer', 'admin_menu'));

    add_filter('wpp_flyer_description', array('class_wpp_pdf_flyer', 'flyer_description'), 0, 2);

    /* AJAX */
    add_action('wp_ajax_wpp_get_property_ids', array('class_wpp_pdf_flyer', 'ajax_get_properties'));
    add_action('wp_ajax_wpp_generate_pdf_flyer', array('class_wpp_pdf_flyer', 'ajax_generate_pdf_flyer'));
    add_action('wp_ajax_wpp_generate_pdf_list', array('class_wpp_pdf_flyer', 'ajax_generate_pdf_list'));

    /* Add "View Flyer" message to update message after a property is saved */
    add_filter('wpp_updated_messages', array('class_wpp_pdf_flyer', 'wpp_updated_messages'));

    // Load admin header scripts
    add_action('admin_enqueue_scripts', array('class_wpp_pdf_flyer', 'admin_enqueue_scripts'));
  }

  /*
   * Adds Custom capability to the current premium feature
   */
  function add_capability($capabilities) {

    $capabilities[self::$capability] = __('Manage PDF Flyer','wpp');

    return $capabilities;
  }

  /**
   * Enqueue scripts on PDF pages, and print content into head
   *
   *
   * @uses $current_screen global variable
   * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
   */
  function admin_enqueue_scripts($hook) {
    global $current_screen;

    //** Property Overview Page */
    if($current_screen->id == 'property_page_wpp_property_pdf_lists') {
      wp_enqueue_script( 'wp-property-backend-global' );
      wp_enqueue_script('jquery');
      wp_enqueue_script('wpp-jquery-colorpicker');
      wp_enqueue_style('wpp-jquery-colorpicker-css');
    }
  }

  /**
   * Settings page load handler
   * @author korotkov@ud
   */
  function wpp_flyer_pdf_list_page_load() {

    //** Default help items */
    $contextual_help['General Settings'][] = '<h3>' . __('General Settings', 'wpp') . '</h3>';
    $contextual_help['General Settings'][] .= '<p>' . __('Unless <b>Don\'t make accessible for public</b> is checked for a list, it will be accessible by anybody who visits your website, provided they have a link to the list.  The lists are stored in your uploads folder, but to the users they appear to be below your main <b>Property Page</b>.  In other words, if your default property page is \'all-properties\', all your lists will have urls that will go something like: http://website.com/all-properties/the-list-filename.pdf', 'wpp') . '</p>';
    $contextual_help['General Settings'][] .= '<p>' . __('By default, the PDF lists are generated every time you save this page, and when any property is updated or created.  You may select <b>Regenerate list every time it is opened</b> to have the list generated every time it is opened in a browser, however, this may cause some unnecessary strain on your server.', 'wpp') . '</p>';
    $contextual_help['Advanced Query'][] .= '<h3>' . __('Advanced Query', 'wpp') . '</h3>';
    $contextual_help['Advanced Query'][] .= '<p>' . __('The advanced query can be used just like the [property_overview] shortcode. For example, if you wanted the list to only show two bedroom properties, your query would simply be: <b>[property_overview bedrooms=2]</b>.', 'wpp') . '</p>';
    $contextual_help['Advanced Query'][] .= '<p>' . __('You could make these as complex as you would like. If you wanted to query all apartments between $400 and $700 dollars in St. Paul, you would use: <b>[property_overview property_type=apartment price=400-700 city=\'St. Paul\' sort_by=route limit_query=10]</b>.', 'wpp') . '</p>';
    $contextual_help['List Creation'][] .= '<h3>' . __('List Creation', 'wpp') . '</h3>';
    $contextual_help['List Creation'][] .= '<p>' . __('PDF Lists are deleted every time a change is made to this page, and may either be regenerated by clicking the Regenerate button below, or by trying to open the list on the front-end.', 'wpp') . '</p>';
    $contextual_help['List Creation'][] .= '<p>' . __('When a single property is updated, the list(s) it is included on are deleted as well.  It is up to you if you want to regenerate the lists manually, or wait for a visitor to attempt to open a list, at which point it will be regenerated automatically.', 'wpp') . '</p>';

    //** Hook this action is you want to add info */
    $contextual_help = apply_filters('wpp_flyer_pdf_list_page_help', $contextual_help);

    do_action('wpp_contextual_help', array('contextual_help'=>$contextual_help));

  }

  /**
   * Adds Metabox on Property Editing Form
   *
   * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
   */
  function add_metabox(){
    add_meta_box( 'wpp_pdf_flyer', __( 'PDF Flyer Options', 'wpp' ),
    array('class_wpp_pdf_flyer','metabox_options'), 'property', 'side' );
  }

  /**
   * Renders Metabox's Settings on Property Editing Form
   *
   * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
   */
  function metabox_options(){
    global $post_id;
    $disableExclude = get_post_meta($post_id, 'exclude_from_pdf_lists', true);
    $text = __('Exclude property from PDF Lists','wpp');
    echo WPP_F::checkbox("name=exclude_from_pdf_lists&id=exclude_from_pdf_lists&label=$text", $disableExclude);
  }

  /**
   * Updates property PDF postmeta
   * Remove PDF Lists which contain the current post (They will generated again)
   * @param int $post_id. ID
   *
   * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
   */
  function save_post($post_id){
    global $post;

    if($post->post_type == 'property') {
      if(isset($_POST['exclude_from_pdf_lists'])) {
        update_post_meta($post_id, 'exclude_from_pdf_lists', $_POST['exclude_from_pdf_lists']);
      }

      // Get PDF Lists data
      $pdf_lists_info = get_option('pdf_lists_info');

      // Get PDF List Dir
      $uploads = wp_upload_dir();
      $pdf_list_dir = $uploads['basedir'].'/wpp-files/';
      if(is_array($pdf_lists_info)) {
        // Remove all PDF Lists which contain the current post (They will generate again)
        foreach ($pdf_lists_info as $slug => $data) {
          $key = array_search($post_id, $data);
          if($key !== false) {
            if(class_wpp_pdf_flyer::pdf_list_exists($slug)) {
              unlink($pdf_list_dir.$slug.'.pdf');
            }
          }
        }
      }
    }
  }

  /**
  * Preserve PDF Flyer settings from being overwritten during settings page saving.
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function wpp_settings_save($new_settings, $old_settings) {
    $preserved_settings = $old_settings['configuration']['feature_settings']['wpp_pdf_flyer']['pdf_lists'];
    $new_settings['configuration']['feature_settings']['wpp_pdf_flyer']['pdf_lists'] = $preserved_settings;
    return $new_settings;
  }

  /**
  * Render PDF list in browser
  *
  *
  * @todo Update error handling so warnings (such as bad images) can be caught and saved in a log.
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function render_pdf_list($list_id, $args = '') {
    //** Error Silencer */
    ob_start();

    //** Include TCPDF here to avoid double class declaration. korotkov@ud */
    @include_once(WPP_Path.'third-party/tcpdf/tcpdf.php');
    //** Include extended TCPDF class TCPDF_List */
    @include_once(WPP_Path.'third-party/tcpdf/tcpdf_list.php');

    global $wp_properties;

    set_time_limit(600);

    $defaults = array(
      'display' => 'true',
      'force_generate' => 'false'
    );

    $args = wp_parse_args( $args, $defaults );

    $list_data = $wp_properties['configuration']['feature_settings']['wpp_pdf_flyer']['pdf_lists'][$list_id];

    $uploads = wp_upload_dir();

    //** Create PDF list
    if(!class_wpp_pdf_flyer::pdf_list_exists($list_id) || $args['force_generate'] == 'true') {
      //** Check the uploads directory
      if(!file_exists($uploads['basedir'].'/wpp-files')) {
        mkdir($uploads['basedir'].'/wpp-files');
      }

      //** Checks if filename doesn't exists, we set it
      if(empty($list_data['filename'])) {
        $list_data['filename'] = $list_id . '.pdf';
      }

      //** Update atrributes of list data: adds values
      if( !empty( $list_data['attributes'] ) && is_array( $list_data['attributes'] ) ) {
        $available_atts = class_wpp_pdf_flyer::get_pdf_list_attributes();
        $tmp_atts = array();
        foreach ($list_data['attributes'] as $value) {
          $tmp_atts[$value] = $available_atts[$value];
        }
        $list_data['attributes'] = $tmp_atts;
        unset($tmp_atts);
      } else {
        $list_data['attributes'] = array();
      }

      //** Set Backgound Color to avoid bugs on PDF generation
      if(empty($list_data['background'])) {
        $list_data['background'] = '#737788';
      }

      //** Set Query Attributes
      $atts = array();
      if(!empty($list_data['advanced_query'])) {
        //** Check shortcode and get attributes from it
        $advanced_query_content = $list_data['advanced_query'];
        $pattern = get_shortcode_regex();
        preg_match('/'.$pattern.'/s', $advanced_query_content, $matches);
        if(!empty($matches) && $matches[2] == 'property_overview') {
          $atts = shortcode_parse_atts( $matches[3] );
        }
      }

      //** Get property IDs
      $properties = WPP_F::get_properties($atts);

      //** Check properties array for structure */
      if(isset($properties['results']) && isset($properties['total'])) {
        $property_ids = $properties['results'];
      } else {
        $property_ids = $properties;
      }

      //** Determine if Group by is set and get values */
      if(!empty($list_data['group_by'])) {
        $group_by_key = $list_data['group_by'];
        $group_by_values = WPP_F::get_all_attribute_values($list_data['group_by']);
      }

      //** Get Property Items and clean them using PDF List settings */
      $tmp_properties = array();
      if( !empty( $property_ids ) && is_array( $property_ids ) ) {
        //** Determine if Child Properties are allowed */
        $child_properties = false;
        if(is_array($list_data['options'])) {
          $key = array_search('show_child_properties', $list_data['options']);
          if($key !== false) {
            $child_properties = true;
          }
        }

        foreach ($property_ids as $key => $id) {
          if($key !== 'total') {
            $property = prepare_property_for_display( $id , array('scope' => 'pdf'));

            //** Determine if child properties are not allowed */
            if(!$child_properties && !empty($property['is_child'])) {
              continue;
            }

            //** Determine if property is excluded from PDF Lists */
            if(!empty($property['exclude_from_pdf_lists'])) {
              continue;
            }

            if(!empty($group_by_values)) {
              $key = sanitize_title($property[$group_by_key]);
              if(empty($list_data['properties'][$key]['label'])) {
                $tmp_properties[$key]['label'] = $property[$group_by_key]; // !!!!!!!!!!!
              }
              $tmp_properties[$key]['items'][$id] = $property;
            } else {
              $tmp_properties[$id] = $property;
            }

          }
        }
      }

      $properties = $tmp_properties;

      //** Set Attributes for Header and Footer */
      $atts = array(
        'background' => $list_data['background'],
        'text_color' => $list_data['header_text_color'],
        'default_text_color' => $list_data['text_color'],
        'title' => $list_data['title'],
        'tagline' => $list_data['tagline'],
        'contact_info' => $list_data['contact_info'],
        'setfont' => !empty($list_data['setfont']) ? $list_data['setfont'] : 'helvetica'
        );

      ini_set('memory_limit', '608M');

      //** Create new PDF List */
      $pdf = new TCPDF_List('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, $atts);

      //** set margins */
      $pdf->SetMargins(0, 10, 0);
      $pdf->SetHeaderMargin(5);
      $pdf->SetFooterMargin(5);

      //** set auto page breaks */
      $pdf->SetAutoPageBreak(TRUE, 8);

      $pdf->setFontSubsetting(true);
      if ($atts['setfont']){
        $pdf->SetFont($atts['setfont']);
      }

      $pdf->SetCreator("WP-Property");
      $pdf->SetAuthor("WP-Property");
      $pdf->SetTitle($list_data['title']);

      $hr = '<table cellspacing="0" cellpadding="0" width="100%"><tr>';
      $hr .= '<td width="2%" style="font-size:1px;height:1px;line-height:1px;">$nbsp;</td>';
      $hr .= '<td width="96%" style="font-size:1px;height:1px;line-height:1px;background-color:' . $list_data['background'] . '">$nbsp;</td>';
      $hr .= '<td width="2%" style="font-size:1px;height:1px;line-height:1px;">$nbsp;</td>';
      $hr .= '</tr></table>';

      try {

        //** Generates HTML */
        if ( !empty( $properties ) && is_array( $properties ) ) {
          //** Set How many properties will be shown per page */
          $per_page = (!empty($list_data['per_page'])) ? $list_data['per_page'] : 4;
          $count = 0;
          $html = '';
          $pdf->AddPage();
          if (!empty($list_data['group_by'])) {
            //** Generate Grouped List */
            foreach($properties as $group) {
              if( !empty( $group['items'] ) && is_array( $group['items'] ) ) {
                if($count != 0 && ($count%$per_page == 0)) {
                  @$pdf->writeHTML( $html, true, false, true, false, '' );
                  $html = '';
                  $pdf->AddPage();
                  $ignore = true;
                }
                if($html == '') {
                  $html .= '<br/><br/>';
                }

                //** Render Label of Group */
                $html .= '<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr>';
                $html .= '<td width="2%">$nbsp;</td>';
                $html .= '<td width="98%" align="left" valign="middle">';
                $html .= '<b style="font-size:1.1em;color:'. $list_data['background'] .'">'. WPP_F::de_slug($group_by_key) .': '. WPP_F::de_slug($group['label']) . '</b>';
                $html .= '<table cellspacing="0" cellpadding="5" width="100%"><tr><td>';
                $html .= '<div style="font-size:5px;height:5px;line-height:5px;">$nbsp;</div>'. $hr;
                $html .= '</td></tr></table>';
                $html .= '</td>';
                $html .= '</tr></table>';

                $current = 0;
                foreach($group['items'] as $property) {
                  if($count != 0 && ($count%$per_page == 0) && !$ignore) {
                    @$pdf->writeHTML( $html, true, false, true, false, '' );
                    $html = '';
                    $pdf->AddPage();

                  }
                  $html .= class_wpp_pdf_flyer::pdf_list_template($property, $list_data);
                  $count++;
                  if(++$current < count($group['items']) && $count%$per_page != 0) {
                    $html .= $hr;
                  }
                  $ignore = false;
                }
              }
            }
          } else {
            //** Generate non Grouped List */
            $current = 0;
            foreach($properties as $property) {
              if($count != 0 && ($count%$per_page == 0)) {
                @$pdf->writeHTML( $html, true, false, true, false, '' );
                $html = '';
                $pdf->AddPage();
              }
              $html .= class_wpp_pdf_flyer::pdf_list_template($property, $list_data);
              $count++;
              if(++$current < count($properties) && $count%$per_page != 0) {
                $html .= $hr;
              }
            }
          }

        }

        @$pdf->writeHTML( $html, true, false, true, false, '' );


      } catch (ErrorException $e) {

      }


      $pdf->Output($uploads['basedir'].'/wpp-files/'.$list_data['filename'], 'F');

      //** Update statistic about Property IDs for PDF Lists */
      //** And Clean Up Files and Data */
      if(!empty($property_ids)) {
        //** Remove 'total' from array to avoid bugs in future */
        if(!empty($property_ids['total'])) {
          unset($property_ids['total']);
        }
        //** Get PDF Lists data */
        $pdf_lists_info = get_option('pdf_lists_info');
        //** If Option doesn't exist or no array, init it */
        if(!is_array($pdf_lists_info)) {
          $pdf_lists_info = array();
        }
        //** Update (set) the current PDF List data (property IDs) */
        $pdf_lists_info[$list_id] = $property_ids;
        //** Get Dir Path */
        $uploads = wp_upload_dir();
        $pdf_list_dir = $uploads['basedir'].'/wpp-files/';
        //** Clean Up Files and Data */
        foreach($pdf_lists_info as $slug => $data) {
          //** Remove PDF List's File if the List doesn't exist */
          if(!isset($wp_properties['configuration']['feature_settings']['wpp_pdf_flyer']['pdf_lists'][$slug])) {
            if(class_wpp_pdf_flyer::pdf_list_exists($slug)) {
              unlink($pdf_list_dir.$slug.'.pdf');
            }
          }
          //** Remove PDF List data if file doesn't exist */
          if(!class_wpp_pdf_flyer::pdf_list_exists($slug)) {
            unset($pdf_lists_info[$slug]);
          }
        }
        //** Update PDF Lists Option */
        update_option('pdf_lists_info', $pdf_lists_info);
      }

    }
    //** Error Silencer */
    ob_end_clean();

    if($args['display'] == 'true') {
      //** We'll be outputting a PDF */
      header('Content-type: application/pdf');
      //** It will be called downloaded.pdf */
      header('Content-Disposition: attachment; filename="'.$list_data['filename'].'"');
      //** The PDF source is in original.pdf */
      readfile($uploads['basedir'].'/wpp-files/'.$list_data['filename']);
      die();
    }
  }

  /**
  * Determine if PDF list exists
  *
  * @param string $slug Slug of the PDF List
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function pdf_list_exists($slug) {
    global $wp_properties;

    $uploads = wp_upload_dir();

    if(file_exists($uploads['basedir'].'/wpp-files/'.$slug.'.pdf')) {
      return true;
    }

    return false;

  }


  /**
   * Add all the variables that we want to have parsed by the WP core.
   * This is the callback that we've registered for the "query_vars"
   * filter. "Our" variables will be used by the "parse_request" action
   * callback.
   *
   * @param array $qvars The array containing all query variables
   * @return The modified array
   */
  public function add_pdf_query_vars ($qvars) {
    $qvars[] = "pdf_list";
    return $qvars;
  }

  /**
  * Determine if the current request is the PDF List link
  * Check request and PDF List options and Open PDF
  *
  * @param object $wp Request Object
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function parse_request_for_pdf_list ($wp) {
    global $wp_query, $wp_properties, $wpdb;

    ob_start();
    //** Determine if Base Slug exists */
    if(empty($wp_properties['configuration']['base_slug'])){
      return $wp;
    }

    //** If request is empty we try to parse Query Vars */
    if(empty($wp->request)) {
      if(!empty($wp->query_vars['p']) && !empty($wp->query_vars['pdf_list'])){
        if($wp->query_vars['p'] != $wp_properties['configuration']['base_slug']) {
          return $wp;
        }
      } elseif (!empty($wp->query_vars['page_id']) && !empty($wp->query_vars['pdf_list'])) {
        $post = get_post($wp->query_vars['page_id']);
        if($post->post_name != $wp_properties['configuration']['base_slug']) {
          return $wp;
        }
      } else {
        return $wp;
      }
      $filename = $wp->query_vars['pdf_list'];
    } else {
      //** Check Request for necessary request attributes */
      preg_match('/'.$wp_properties['configuration']['base_slug'].'/', $wp->request, $bs_m);
      if(empty($bs_m)) {
        return $wp;
      }
      //** Determine if Query var 'name' exists */
      if(!empty($wp->query_vars['name'])) {
        $filename = $wp->query_vars['name'];
      } else {
        $filename = preg_replace('/^.*\/(.*\.pdf)$/', '$1' ,$wp->request);
      }
      if(empty($filename)) {
        return $wp;
      }
    }

    //** Check file name */
    preg_match('/\.pdf$/', $filename, $pdf_m);
    if(empty($pdf_m)) {
      return $wp;
    }
    //** Get Slug */
    $slug = str_replace('.pdf','',$filename);

    if(empty($slug)) {
      return $wp;
    }

    if (!empty($wp_properties['configuration']['feature_settings']['wpp_pdf_flyer']['pdf_lists'][$slug])) {
      $list_data = $wp_properties['configuration']['feature_settings']['wpp_pdf_flyer']['pdf_lists'][$slug];
    } else {
      return $wp;
    }

    //** Set arguments for PDF rendering */
    $args = array('display' => 'true');

    if(is_array($list_data['options'])) {
      foreach ($list_data['options'] as $option) {
        if($option == 'restrict_public_access') {
          //** Restricted Public Access */
          $user = wp_get_current_user();
          if($user->{$wpdb->prefix.'user_level'} == 0) {
            return $wp;
          }
        } else if ($option == 'do_not_cache_list') {
          $args['force_generate'] = 'true';
        }
      }
    }
    ob_end_clean();
    //** Render PDF List */
    class_wpp_pdf_flyer::render_pdf_list($slug, $args);
    return false;
  }

  /**
  * Get a PDF list template, or use default
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function pdf_list_template($property, $list_data) {

    ob_start();
    //** Attempt to get design from template */

    $template_found = WPP_F::get_template_part(array(
      "pdf-list-template"
    ), array(WPP_Templates));

    if($template_found) {
      include $template_found;
    } else {
      self::default_pdf_list_template($property, $list_data);
    }

    $pdf_list_contents = ob_get_contents();
    ob_end_clean();

    return $pdf_list_contents;
  }

  /**
  * Default PDF list template
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function default_pdf_list_template($property, $list_data) {
    $descr = '&nbsp;';
    $title = '&nbsp;';
    $info = '&nbsp;';
    $image_width = '90';
    $image_height = '90';

    //** Prepare and Render view of Property Stats */
    $wpp_property_stats = class_wpp_pdf_flyer::get_pdf_list_attributes('property_stats');
    $exclude_property_stats = array();
    foreach ((array)$wpp_property_stats as $key => $value) {
      if(!array_key_exists($key, $list_data['attributes'])) {
        $exclude_property_stats[] = $key;
      } else {
        unset($list_data['attributes'][$key]);
      }
    }
    $property_stats = @draw_stats( 'exclude=' . implode(',', $exclude_property_stats) . '&display=array', $property );

    foreach ((array)$property_stats as $label => $value) {
      $info .= '<br/>'. $label .': '. $value;
    }


    //** Prepare and Render view of Taxonomies */
    $wpp_taxonomies = class_wpp_pdf_flyer::get_pdf_list_attributes('taxonomies');
    if(is_array($wpp_taxonomies)) {
      foreach ($wpp_taxonomies as $key => $value) {
        if(array_key_exists($key, $list_data['attributes'])) {
          if(get_features("type=$key&format=count" , $property)) {
            $features = get_features("type=$key&format=array&links=false", $property);
            $info .= '<br/>'. $value .': '. implode($features, ", ");
          }
          unset($list_data['attributes'][$key]);
        }
      }
    }

    //** Prepare other property attributes (image, title, description, tagline, etc) */
    foreach ( (array)$list_data['attributes'] as $attr_id => $attr_value) {
      if ( $attr_id == 'post_thumbnail' && !empty( $property['images']['thumbnail'] ) && WPP_F::can_get_image($property['images']['thumbnail'])) {

        $image = '<table cellspacing="0" cellpadding="5" border="0" style="background-color:' . $list_data['background'] . '"><tr><td>';
        $image .= '<img width="'. $image_width .'" height="'. $image_height .'" src="'. $property['images']['thumbnail'] .'" alt="" />';
        $image .= '</td></tr></table>';

      } elseif( $attr_id == 'post_content' && !empty( $property['post_content'] ) ) {
        //** Post Content */
        $descr = strip_shortcodes( $property['post_content'] );
        $descr = apply_filters('the_content', $descr);
        $descr = str_replace(']]>', ']]&gt;', $descr);
        $descr = strip_tags($descr);
        $excerpt_length = 65;
        $words = preg_split("/[\n\r\t ]+/", $descr, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
        if ( count($words) > $excerpt_length ) {
          array_pop($words);
          $descr = implode(' ', $words);
          $descr = $descr . '...';
        } else {
          $descr = implode(' ', $words);
        }
      } elseif( $attr_id == 'post_title' && !empty( $property['post_title'] ) ) {
        //** Title */
        $title = $property['post_title'];
      } elseif( $attr_id == 'tagline' && !empty( $property['tagline'] ) ) {
        //** Tagline */
        $tagline = '<span><b>' . $property['tagline'] . '</b></span><br/>';
      }else {
        //** Attributes (Property Meta) */
        $info .= !empty($property[$attr_id]) ? '<br>'. $attr_value .': '. $property[ $attr_id ] : '';
      }
    }

    echo '<table cellspacing="0" cellpadding="0" width="100%" border="0"><tr>';
    if (!empty($image)) {
      echo '<td colspan="7" style="font-size:8px;height:8px;line-height:8px;">$nbsp;</td>';
      echo '</tr><tr>';
      echo '<td width="2%">$nbsp;</td>';
      echo '<td width="12%" align="left" valign="middle">' . $image . '</td>';
      echo '<td width="2%">$nbsp;</td>';
      echo '<td width="25%"><b>'. $title .'</b>'.$info . '</td>';
      echo '<td width="2%">$nbsp;</td>';
      echo '<td width="54%">'. $tagline . $descr .'</td>';
      echo '<td width="2%">$nbsp;</td>';
      echo '</tr><tr>';
      echo '<td colspan="7" style="font-size:8px;height:8px;line-height:8px;">$nbsp;</td>';

    } else {
      echo '<td colspan="5" style="font-size:8px;height:8px;line-height:8px;">$nbsp;</td>';
      echo '</tr><tr>';
      echo '<td width="2%">$nbsp;</td>';
      echo '<td width="39%"><b>'. $title .'</b>'.$info . '</td>';
      echo '<td width="2%">$nbsp;</td>';
      echo '<td width="54%">'. $tagline . $descr .'</td>';
      echo '<td width="2%">$nbsp;</td>';
      echo '</tr><tr>';
      echo '<td colspan="5" style="font-size:8px;height:8px;line-height:8px;">$nbsp;</td>';
    }
    echo '</tr></table>';
  }

  /**
  * Regenerate PDF lists.
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function regenerate_pdf_lists() {
    global $wp_properties;

     $lists = $wp_properties['configuration']['feature_settings']['wpp_pdf_flyer']['pdf_lists'];

     if(!is_array($lists))
      return;

     foreach($lists as $slug => $list) {
      class_wpp_pdf_flyer::render_pdf_list($slug, "force_generate=true&display=false");
     }

  }

  /**
  * Adds pages
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function admin_menu() {
    global $wp_properties;

    if($wp_properties['configuration']['feature_settings']['wpp_pdf_flyer']['use_pdf_property_lists'] == 'on')
      $wp_properties['runtime']['pages']['wpp_flyer_pdf_list'] = add_submenu_page( 'edit.php?post_type=property', __('PDF Lists','wpp'), __('PDF Lists','wpp'), self::$capability, 'wpp_property_pdf_lists', array('class_wpp_pdf_flyer','page_property_lists'));

    if ( !empty( $wp_properties['runtime']['pages']['wpp_flyer_pdf_list'] ) ) {
      add_action("load-{$wp_properties['runtime']['pages']['wpp_flyer_pdf_list']}", array('class_wpp_pdf_flyer', 'wpp_flyer_pdf_list_page_load'));
    }
  }

  /**
  * Get available attributes for PDF Flyer
  * @return array $available_stats
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  static function get_pdf_list_attributes($type = false) {
    global $wp_properties;

    $available_stats = array();

    if (!$type) {
      $available_stats['post_title'] = sprintf(__('%1$s Title', 'wpp'), ucfirst(WPP_F::property_label('singular')));
      //**
      // Not sure that Excerpt is needed here.
      // And what is the highest priority: content or excerpt (if both will be checked)? Maxim Peshkov.
      //$available_stats['post_excerpt'] = __('Excerpt', 'wpp');
      //*/
      $available_stats['post_content'] = __('Full Description', 'wpp');
      $available_stats['post_thumbnail'] = __('Thumbnail', 'wpp');
    }

    if ((!$type || $type == 'property_stats') && is_array($wp_properties['property_stats'])) {
      foreach( (array)$wp_properties['property_stats'] as $slug => $label )
        $available_stats[$slug] = $label;
    }

    if((!$type || $type == 'taxonomies') && is_array($wp_properties['taxonomies'])) {
      foreach($wp_properties['taxonomies'] as $slug => $tax_data)
        $available_stats[$slug] = $tax_data['label'];
    }

    if((!$type || $type == 'property_meta') && is_array($wp_properties['property_meta'])) {
      foreach($wp_properties['property_meta'] as $slug => $label)
        $available_stats[$slug] = $label;
    }

    $available_stats = apply_filters('wpp_flyer_list_available_stats', $available_stats);

    return $available_stats;
  }

  /**
  * Renders page to create and configure PDF lists
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function page_property_lists() {
    global $wp_properties;

    @include_once( WPP_Path.'third-party/tcpdf/wpp_tcpdf.php' );

    //** Save settings */
    if(wp_verify_nonce($_REQUEST['_wpnonce'], 'wpp_flyer_lists_page')) {

      $wp_messages['notice'][] = __('List settings updated and old lists have been deleted. ', 'wpp');

      $pdf_lists = is_array($_REQUEST['wpp_flyer_list_settings']) ? $_REQUEST['wpp_flyer_list_settings'] : array();

      $uploads = wp_upload_dir();

      $pdf_list_dir = $uploads['basedir'].'/wpp-files/';


      //** Check PDF Lists and set filenames */
      $tmp_pdf_lists = array();
      foreach((array)$pdf_lists as $slug => $list) {
        if(!empty($list['title'])) {
          $list['filename'] = $slug . '.pdf';
          $tmp_pdf_lists[$slug] = $list;
        }

        //** Delete exisitng lists */
         if(class_wpp_pdf_flyer::pdf_list_exists($slug)) {
            unlink($pdf_list_dir.$slug.'.pdf');
          }
      }

      $pdf_lists = $tmp_pdf_lists;
      unset($tmp_pdf_lists);



      $wp_properties['configuration']['feature_settings']['wpp_pdf_flyer']['pdf_lists'] = $pdf_lists;

      update_option('wpp_settings', $wp_properties);
    }

    if(!$pdf_lists) {
      $pdf_lists = $wp_properties['configuration']['feature_settings']['wpp_pdf_flyer']['pdf_lists'];
    }

    //** Set default (sample_pdf_list) List, if there is no any list */
    if(empty($pdf_lists)) {
      $pdf_lists['sample_pdf_list']['title'] = __('Sample PDF List', 'wpp');
    }

    $available_stats = class_wpp_pdf_flyer::get_pdf_list_attributes();

    ?>
    <style type="text/css">
      ul.wpp_flyer_list_settings label {
        display: block;
        float: left;
        margin-top: 3px;
        width: 70px;
      }
      ul.wpp_flyer_list_settings label span {
        visibility: hidden;
      }
    </style>
    <script type='text/javascript'>
      jQuery(document).ready(function() {
        var wpp_pdf_root_url = jQuery(".wpp_pdf_root_url").val();

        jQuery('.slug_setter').live('change', function() {
          var value = jQuery(this).val();
          var parent = jQuery(this).parents('tr.wpp_dynamic_table_row');
          var slug = wpp_create_slug(value);

          jQuery('.wpp_pdf_list_name', parent).val(slug + '.pdf');

          jQuery(".wpp_link", parent).attr("href", wpp_pdf_root_url + slug + '.pdf');

        });

        jQuery(".wpp_flyer_show_advanced_query").live('click', function() {
          var parent = jQuery(this).parents('li');
          jQuery(".wpp_flyer_advanced_query_container", parent).toggle();
        });

        //**
        // When the .slug_setter input field is modified, we update names of other elements in row
        // This event also added in wp-property-backend-global.js file but only for new rows [new_row=true],
        // So be careful to avoid duplicate events functionality
        //*/
        if(window.updateRowNames) {
          jQuery(".wpp_dynamic_table_row[new_row=false] input.slug_setter").live("change", function() {
            updateRowNames(this);
          });
        }

        jQuery('#wpp_ajax_regenerate_all_flyers').click(function(){
          var ajaxSpinner = jQuery('#regenerate_all_flyers_ajax_spinner');
          var closeButton = jQuery("#wpp_ajax_regenerate_all_flyers_close");
          var resultBox = jQuery('#wpp_ajax_regenerate_all_flyers_result');
          var lists = [];

          jQuery('.wpp_dynamic_table_row[new_row="false"]').each(function(i,e){
            var slug = jQuery(e).attr('slug');
            var name = jQuery('.slug_setter', jQuery(e)).val();
            lists.push( { 'slug': slug, 'name' : name } );
          });

          var wpp_recursively_generate_pdf_list = function( data, callback ) {
            var item = data.shift();
            jQuery.ajax({
              url: '<?php echo admin_url('admin-ajax.php'); ?>',
              data: 'action=wpp_generate_pdf_list&slug=' + item.slug,
              complete: function( r, status ) {

                if( status == 'success' ) {
                  var result = eval('(' + r.responseText + ')');
                  if(result.success == 1) {
                    putLog('PDF List "' + item.name + '" is generated.', resultBox);
                  } else {
                    putLog('<b>Error. PDF List "' + item.name + '" could not be generated.</b>', resultBox);
                  }
                } else {
                  putLog('Could not regenerate PDF List "' + item.name + '". Looks like, something caused error on server.', resultBox);
                }

                if ( data.length == 0 ) {
                  if( typeof callback === 'function' ) {
                    callback();
                  }
                } else {
                  wpp_recursively_generate_pdf_list( data, callback );
                }

              }
            });
          }

          ajaxSpinner.show( 'fast', function() {
            resultBox.show( 'fast', function() {
              putLog("<?php _e("Regenerating PDF Lists. If you have a lot of properties this process may take a while, please do not close this browser window until it is complete."); ?>", resultBox);

              // Loop all properties
              wpp_recursively_generate_pdf_list( lists, function() {
                putLog('<?php _e("Finished."); ?>', resultBox);
                ajaxSpinner.hide();
                closeButton.show();
              } );
            } );
          } );

          return false;
        });

        function putLog (log, el) {
          if (typeof log != 'undefined' && typeof el == 'object'){
            if (jQuery('.logs', el).length == 0) {
              el.append('<ul class="logs"></ul>');
            }
            jQuery('.logs', el).append('<li>' + log + '</li>');
          }
        }

      });
    </script>
    <div class='wrap'>
      <h2><?php _e('PDF Lists', 'wpp'); ?></h2>

      <?php if(isset($wp_messages['error']) && $wp_messages['error']): ?>
      <div class="error">
      <?php foreach($wp_messages['error'] as $error_message): ?>
        <p><?php echo $error_message; ?>
      <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if(isset($wp_messages['notice']) && $wp_messages['notice']): ?>
      <div class="updated fade">
      <?php foreach($wp_messages['notice'] as $notice_message): ?>
        <p><?php echo $notice_message; ?>
      <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form action="<?php echo admin_url('edit.php?post_type=property&page=wpp_property_pdf_lists'); ?>" method="post">
      <input type="hidden" class="wpp_pdf_root_url" value="<?php echo get_pdf_list_permalink(); ?>" />
      <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpp_flyer_lists_page'); ?>" />
      <table class="ud_ui_dynamic_table widefat form-table" allow_random_slug="true">
          <thead>
            <tr>
              <th><?php _e('Settings', 'wpp'); ?></th>
              <th style="width:200px;"><?php _e('Options', 'wpp'); ?></th>
              <th style="width:200px;"><?php _e('Attributes', 'wpp'); ?></th>
               <th class='wpp_delete_col'>&nbsp;</th>
            </tr>
          </thead>
          <tbody>

          <?php foreach( (array)$pdf_lists as $slug => $list):  ?>

              <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row="false">
                <td>
                  <ul class='wpp_flyer_list_settings'>

                  <li>
                    <label for=""><?php _e('List Name:', 'wpp'); ?></label>
                    <input type="text" class="slug_setter" name="wpp_flyer_list_settings[<?php echo $slug; ?>][title]" value="<?php echo $list['title']; ?>" />
                  </li>

                  <li>
                    <label for=""><?php _e('File Name:', 'wpp'); ?></label>
                    <input type="text" readonly="readonly" class="wpp_pdf_list_name" name="wpp_flyer_list_settings[<?php echo $slug; ?>][filename]" value="<?php echo $list['filename']; ?>" />
                  </li>

                  <li>
                    <label><span><?php _e('Actions:', 'wpp'); ?></span></label>
                    <a href='<?php echo get_pdf_list_permalink($slug); ?>' class="wpp_link" target="_blank"><?php _e('View PDF'); ?></a>
                    <?php do_action('wpp_flyer_list_ui_actions'); ?>
                  </li>


                 </ul>
                </td>

                <td>
                  <ul>
                    <li>
                      <label><?php _e('Group by:', 'wpp'); ?></label>
                      <?php echo WPP_F::draw_attribute_dropdown("name=wpp_flyer_list_settings[{$slug}][group_by]&selected={$list['group_by']}", array('property_type' => __('Property Type'))); ?>
                    </li>

                    <li>
                      <label><?php _e('Properties per page:', 'wpp'); ?></label>
                      <select name="wpp_flyer_list_settings[<?php echo $slug; ?>][per_page]" style="width:40px;">
                        <?php for ($i=4; $i>=1; $i--) : ?>
                        <option value="<?php echo $i; ?>" <?php echo ($list['per_page'] == $i) ? 'selected="selected"' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                      </select>
                    </li>

                    <li>
                      <input type="checkbox" name="wpp_flyer_list_settings[<?php echo $slug; ?>][options][]" value="show_child_properties" <?php echo WPP_F::checked_in_array('show_child_properties', $list['options']); ?> />
                      <label><?php _e('Show child properties.', 'wpp'); ?></label>
                    </li>

                    <li>
                      <input type="checkbox" name="wpp_flyer_list_settings[<?php echo $slug; ?>][options][]" value="restrict_public_access" <?php echo WPP_F::checked_in_array('restrict_public_access', $list['options']); ?> />
                      <label><?php _e('Don\'t make accessible for public.', 'wpp'); ?></label>
                    </li>

                    <li>
                      <input type="checkbox" name="wpp_flyer_list_settings[<?php echo $slug; ?>][options][]" value="do_not_cache_list" <?php echo WPP_F::checked_in_array('do_not_cache_list', $list['options']); ?> />
                      <label><?php _e('Regenerate list every time it is opened.', 'wpp'); ?></label>
                    </li>

                    <?php do_action('wpp_flyer_list_ui_options'); ?>

                    <li>
                      <div class="wpp_link wpp_flyer_show_advanced_query" style='margin-bottom: 4px;'><?php _e('Tagline','wpp'); ?></div>
                      <div class="wpp_flyer_advanced_query_container <?php echo (empty($list['tagline']) ? ' hidden ' : ''); ?>">
                        <textarea class='large-text code' name="wpp_flyer_list_settings[<?php echo $slug; ?>][tagline]"><?php echo $list['tagline']; ?></textarea>
                      </div>
                    </li>

                    <li>
                      <div class="wpp_link wpp_flyer_show_advanced_query" style='margin-bottom: 4px;'><?php _e('Contact Information','wpp'); ?></div>
                      <div class="wpp_flyer_advanced_query_container <?php echo (empty($list['contact_info']) ? ' hidden ' : ''); ?>">
                        <textarea class='large-text code' name="wpp_flyer_list_settings[<?php echo $slug; ?>][contact_info]"><?php echo $list['contact_info']; ?></textarea>
                        <div class="description"><?php _e("Contact Information will be shown on every page", 'wpp'); ?></div>
                      </div>
                    </li>

                    <li>
                      <div class="wpp_link wpp_flyer_show_advanced_query" style='margin-bottom: 4px;'><?php _e('Advanced Query','wpp'); ?></div>
                      <div class="wpp_flyer_advanced_query_container <?php echo (empty($list['advanced_query']) ? ' hidden ' : ''); ?>">
                        <textarea class='large-text code' name="wpp_flyer_list_settings[<?php echo $slug; ?>][advanced_query]"><?php echo $list['advanced_query']; ?></textarea>
                        <div class="description"><?php _e('You may use WPP Query arguments here to perform a more advanced queryies, and limits. Wrap your query into [property_overview] as with a regular shortcode.', 'wpp'); ?></div>
                      </div>
                    </li>


                    <li>
                      <label style="width:50px;display:block;float:left;line-height:22px;"><?php _e('Font:', 'wpp'); ?></label>
                      <?php wpp_tcpdf_get_HTML_font_list("name=wpp_flyer_list_settings[{$slug}][setfont]&selected={$list['setfont']}"); ?>
                      <br/><span class="description"><?php _e('The default font is Helvetica. If you have any problems with current font try choosing another one from the list.','wpp'); ?></span>
                      <div class="clear"></div>
                    </li>
                    <li>
                      <label style="width:220px;display:block;float:left;line-height:22px;"><?php _e('Header & Footer background color:', 'wpp'); ?></label>
                      <input type="text" class="wpp_input_colorpicker" name="wpp_flyer_list_settings[<?php echo $slug; ?>][background]" value="<?php echo $list['background']; ?>" />
                      <div class="clear"></div>
                    </li>
                    <li>
                      <label style="width:220px;display:block;float:left;line-height:22px;"><?php _e('Header & Footer text color:', 'wpp'); ?></label>
                      <input type="text" class="wpp_input_colorpicker" name="wpp_flyer_list_settings[<?php echo $slug; ?>][header_text_color]" value="<?php echo $list['header_text_color']; ?>" />
                      <div class="clear"></div>
                    </li>
                    <li>
                      <label style="width:220px;display:block;float:left;line-height:22px;"><?php _e('Text color:', 'wpp'); ?></label>
                      <input type="text" class="wpp_input_colorpicker" name="wpp_flyer_list_settings[<?php echo $slug; ?>][text_color]" value="<?php echo $list['text_color']; ?>" />
                      <div class="clear"></div>
                    </li>

                  </ul>
                </td>

                <td>
                  <div class="wp-tab-panel">
                  <ul>
                    <?php foreach( (array) $available_stats as $s_slug => $s_label): ?>
                    <li>
                      <input <?php echo WPP_F::checked_in_array($s_slug, $list['attributes']); ?> type="checkbox" name="wpp_flyer_list_settings[<?php echo $slug; ?>][attributes][]" value="<?php echo $s_slug; ?>" />
                      <label><?php echo $s_label;?></label>
                    </li>
                    <?php endforeach; ?>
                  </ul>

                </td>


                <td>
                  <ul>

                    <li><span verify_action="true" class="wpp_delete_row delete wpp_link"><?php _e('Delete', 'wpp'); ?></span></li>
                  </ul>

                  </td>
              </tr>
           <?php endforeach; ?>

            </tbody>
          <tfoot>
            <tr>
              <td colspan="4">
              <input type="button" class="wpp_add_row button-secondary" value="<?php _e('Add List'); ?>">
              <input  type="submit" name="Submit" class="button-primary btn" style="margin-left: 20px;" value="<?php _e('Save Lists'); ?>" />
              </td>
            </tr>
          </tfoot>
        </table>


        <div class="wpp_settings_block" style="margin: 10px 0;">
          <span><?php _e("Regenerate all PDF Lists.  This will create, or recreate, all your lists."); ?></span>
          <input type="button" id="wpp_ajax_regenerate_all_flyers" value="Regenerate">&nbsp;<img style="display:none;" id="regenerate_all_flyers_ajax_spinner" src="<?php echo WPP_URL; ?>images/ajax_loader.gif" />
          <br/><input style="display:none;" type="button" id="wpp_ajax_regenerate_all_flyers_close" value="<?php _e('Refresh Page'); ?>" onClick="window.location.reload();">
          <pre class="wpp_class_pre hidden" id="wpp_ajax_regenerate_all_flyers_result" style="height:300px;"></pre>
        </div>

        <div class='widefat' style="margin:10px 0;padding:10px;width:auto;">
          <p><span class="description"><?php _e('Shortcode examples:'); ?></span></p>
          <ul>
            <li><span class="description"><b>[wpp_pdf_list name='list_name']</b> - <?php _e('Returns a formatted HTML link to the PDF List.'); ?></span></li>
            <li><span class="description"><b>[wpp_pdf_list name='list_name' title='PDF Flyer']</b> - <?php _e('Returns a html link to the PDF List with custom title.'); ?></span></li>
            <li><span class="description"><b>[wpp_pdf_list name='list_name' urlonly='yes']</b> -  <?php _e('Returns the raw URL to the PDF List (for use in custom html).'); ?></span></li>
            <li><span class="description"><b>[wpp_pdf_list name='list_name' class='custom_css_class']</b> - <?php _e('For use with a custom CSS class.'); ?></span></li>
            <li><span class="description"><b>[wpp_pdf_list name='list_name' image='url_to_custom_image']</b> - <?php _e('Returns url_to_custom_image with a link to the PDF List.'); ?></span></li>
          </ul>
        </div>



        </form>

    </div>
    <?php


  }


/**
  * Handles front-end functions.
  *
  * Creates a flyer on-the-fly if wpp_flyer_create is passed, then redirects to it automatically.
  * If wpp_flyer_create is passed, but flyer already exists, simply opens the flyer -> does not re-generate
  *
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function template_redirect() {
    global $post, $wpdb;


    if($_REQUEST['wpp_flyer_create'] == 'true' && $post->ID)  {

      //** Make sure flyer doesn't already exist * /
      if(self::flyer_exists($post->ID)) {
        wp_redirect(get_pdf_flyer_permalink($post->ID)); exit;
      }

      if(self::create_flyer_pdf($post->ID) !== false)  {
        wp_redirect(get_pdf_flyer_permalink($post->ID)); exit;
      } else {
        return;
      }
    }
  }


  /**
  * Adds a PDF Flyer link to property objects in the back-end property view table
  *
  * Returns link if it flyer exists, or if on-the-fly creation is enabled.
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function page_row_actions($actions, $post)
  {

    if($post->post_type != 'property')
      return $actions;

    $pdf_link = get_pdf_flyer_permalink($post->ID);

    if($pdf_link)
      $actions['pdf_flyer'] = "<a href='$pdf_link'>". __('PDF Flyer', 'wpp') . "</a>";

    return $actions;

  }

    /**
    * Default left middle added via hook into flyer template
    *
    *
    * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
    */
    function flyer_left_column( $property, $wpp_pdf_flyer ){
      global $wp_properties;

      $map_width = round($wpp_pdf_flyer['first_col_width'] / 2 - 27 );

      $static_google_map = "http://maps.google.com/maps/api/staticmap?center={$property[latitude]},{$property[longitude]}&zoom=".
      apply_filters('wpp_flyer_map_scale', 14, $property, $wpp_pdf_flyer)
      ."&size={$map_width}x250&scale=2&sensor=true&markers=color:blue%7C{$property[latitude]},{$property[longitude]}";

      if(!WPP_F::can_get_image($static_google_map)) {
        $static_google_map = false;
      }

      //** disabled Groupped attributes */
      //$sort_by_groups = !empty( $wp_properties[ 'property_groups' ] ) && $wp_properties[ 'configuration' ][ 'property_overview' ][ 'sort_stats_by_groups' ] == 'true' ? 'true' : 'false';

      $property['post_content'] = apply_filters('wpp_flyer_description', $property['post_content'], $wpp_pdf_flyer);


      if (!empty($wpp_pdf_flyer['pr_details']) && !empty($wpp_pdf_flyer['detail_attributes'])): ?>

        <tr>
            <td>
              <div class="heading_text"><?php echo __('Details', 'wpp'); ?></div>
            </td>
        </tr>
        <tr>
            <td class="pdf-text"><br/>
                <?php echo @draw_stats( 'exclude='.$wpp_pdf_flyer['excluded_details_stats'].'&display=plain_list&sort_by_groups=false', $property ); ?>
            </td>
        </tr>
        <?php endif; ?>

        <?php if (!empty($wpp_pdf_flyer['pr_description']) && $property['post_content'] != '') : ?>
        <tr>
            <td><div class="heading_text"><?php echo __('Description', 'wpp'); ?></div>
            </td>
        </tr>
        <tr>
            <td class="pdf-text"><br/>
                <?php echo $property['post_content']; ?>
            </td>
        </tr>
        <tr>
            <td height="15">&nbsp;
            </td>
        </tr>
        <?php endif; ?>

        <?php if (!empty($wpp_pdf_flyer['pr_location']) && !empty($property['latitude']) && !empty($property['longitude'])) : ?>
        <tr>
            <td><div class="heading_text"><?php echo __('Location', 'wpp'); ?></div>
            </td>
        </tr>
        <?php if($static_google_map) { ?>
        <tr>
            <td class="pdf-text"><br/>
                <table cellspacing="0" cellpadding="10" border="0" class="bg-section">
                <tr>
                    <td><img width="<?php echo $map_width?>" src="<?php echo $static_google_map; ?>" /></td>
                </tr>
                </table>
            </td>
        </tr>
        <?php
        }
        endif;
    }


    /**
    * Default column middle added via hook into flyer template
    *
    *
    * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
    */
    function flyer_middle_column( $property, $wpp_pdf_flyer )
    {
        global $wp_properties;

        if (!empty($wpp_pdf_flyer['pr_features'])) {
          if(!empty($wp_properties['taxonomies']))
            foreach($wp_properties['taxonomies'] as $tax_slug => $tax_data): ?>
              <?php if(get_features("type=$tax_slug&format=count", $property)):  ?>
                <tr>
                    <td><div class="heading_text"><?php echo $tax_data['label']; ?></div>
                    </td>
                </tr>
                <tr>
                    <td class="pdf-text"><br/>
                        <?php get_features("type=$tax_slug&format=comma&links=false", $property); ?>
                    </td>
                </tr>
                <tr>
                    <td height="15">&nbsp;
                    </td>
                </tr>
            <?php endif;
            endforeach;
        }
    }

  /**
  * Default left middle added via hook into flyer template
  *
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function flyer_right_column( $property, $wpp_pdf_flyer ) {
    if(!empty($wpp_pdf_flyer['qr_code'])) {
    ?>
        <tr>
            <td><table cellspacing="0" cellpadding="10" class="bg-section" width="100%">
                <tr>
                    <td style="text-align:justify;"><a href="<?php echo $property['permalink']?>"><img width="<?php echo ($wpp_pdf_flyer['second_photo_width'] - 20 ); ?>" src="<?php echo $wpp_pdf_flyer['qr_code']; ?>" alt="" /></a>
                    <?php if (!empty($wpp_pdf_flyer['qr_code_note'])) : ?>
                    <br/><br/><span class="pdf-note">Scan the code above with your mobile device or click it to go directly to the <?php echo get_bloginfo(); ?> website for <?php echo $property['post_title']; ?></span>
                    <?php endif; ?>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
    <?php
    }
  }

  /**
  * 'Cleans' Property Description before display it in Flyer
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  * @return string $description
  * @author Maxim Peshkov
  * @since 1.5.1
  */
  function flyer_description ($description, $wpp_pdf_flyer) {

    //** Cleans description */
    $description = strip_shortcodes( $description );
    $description = apply_filters('the_content', $description);
    $description = str_replace(']]>', ']]&gt;', $description);
    $description = strip_tags($description);

    //** Truncates description when it's enabled in PDF Flyer Settings */
    if($wpp_pdf_flyer['truncate_description'] == 'on') {
      $excerpt_length = 25;
      $words = preg_split("/[\n\r\t ]+/", $description, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
      if ( count($words) > $excerpt_length ) {
        array_pop($words);
        $description = implode(' ', $words);
        $description = $description . '...';
      } else {
        $description = implode(' ', $words);
      }
    }

    return $description;
  }

  /**
  * Modifies updated message after saving / creating a property with link to flyer
  *
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function wpp_updated_messages($messages) {
    global $post;
    $messages['property'][1] = $messages['property'][1] . sprintf( __(', or <a href="%s" target="_blank">view flyer</a>.','wpp'), esc_url( get_pdf_flyer_permalink($post->ID) ) );
    return $messages;

  }

  /**
  * Adds pdf flyer menu to settings page navigation
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function settings_nav($tabs) {

  $tabs['pdf_flyer'] = array(
    'slug' => 'pdf_flyer',
    'title' => __('PDF','wpp')
  );
  return $tabs;
  }

  /**
  * Content for PDF Flyer settings on WPP settings page
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function settings_page($tabs) {
    global $wp_properties;

    @include_once(WPP_Path.'third-party/tcpdf/wpp_tcpdf.php');

    $wpp_pdf_flyer = $wp_properties['configuration']['feature_settings']['wpp_pdf_flyer'];
    $uploads = wp_upload_dir();

    if(empty($wpp_pdf_flyer['header_color']))
      $wpp_pdf_flyer['header_color'] = '#e6e6fa';

    if(empty($wpp_pdf_flyer['section_bgcolor']))
      $wpp_pdf_flyer['section_bgcolor'] = '#bbbbbb';

    if(empty($wpp_pdf_flyer['num_pictures']))
      $wpp_pdf_flyer['num_pictures'] = '3';

    $available_image_sizes = get_intermediate_image_sizes();

  ?>

  <table class="form-table">
  <tbody>

  <?php if(!is_writable($uploads['path'])) { ?>
    <tr valign="top">
    <th scope="row" colspan="2" style="color:red">
      <div class="updated fade"><p><?php printf(__("Warning: <b>%s</b> is not writable, PDF flyer cannot be created.", 'wpp'), $uploads['path']); ?></p></div>
    </th>
    </tr>
  <?php } ?>

    <tr valign="top">
      <th scope="row"><?php _e('Options','wpp'); ?></th>
      <td>
      <ul>

        <li>
            <input type="checkbox" id="use_pdf_property_lists" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][use_pdf_property_lists]" <?php checked($wpp_pdf_flyer['use_pdf_property_lists'],'on'); ?>>
            <label for="use_pdf_property_lists" class="description"><?php _e('Show panel that allows you to create PDF property lists.','wpp'); ?></label>
        </li>

        <li>
            <input type="checkbox" id="generate_flyers_on_the_fly" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][generate_flyers_on_the_fly]" <?php checked($wpp_pdf_flyer['generate_flyers_on_the_fly'],'on'); ?>>
            <label for="generate_flyers_on_the_fly" class="description"><?php _e('Generate property flyers automatically when somebody tries to view them for the first time.','wpp'); ?></label>
        </li>

        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code]" <?php if(isset($wpp_pdf_flyer['qr_code']) && $wpp_pdf_flyer['qr_code']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code]"><?php _e("Generate QR Code.", 'wpp'); ?></label></span>
        </li>

        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][truncate_description]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][truncate_description]" <?php if(isset($wpp_pdf_flyer['truncate_description']) && $wpp_pdf_flyer['truncate_description']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][truncate_description]"><?php _e('Truncate the Description when it\'s displayed in Flyer (It can be helpful, when the data can not be placed on one page).','wpp'); ?></label></span>
        </li>

      </ul>
      </td>
    </tr>

    <tr valign="top">
      <th scope="row"><?php _e('Flyer Display','wpp'); ?></th>
      <td>
        <ul>
            <li>
              <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_title]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_title]" <?php if(isset($wpp_pdf_flyer['pr_title']) && $wpp_pdf_flyer['pr_title']=='on') echo " CHECKED "; ?>>
              <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_title]"><?php _e('Title (Header)','wpp'); ?></label></span>
            </li>

            <li>
              <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_tagline]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_tagline]" <?php if(isset($wpp_pdf_flyer['pr_tagline']) && $wpp_pdf_flyer['pr_tagline']=='on') echo " CHECKED "; ?>>
              <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_tagline]"><?php _e('Tagline under Title (Header)','wpp'); ?></label></span>
            </li>

            <li>
              <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code_note]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code_note]" <?php if(isset($wpp_pdf_flyer['qr_code_note']) && $wpp_pdf_flyer['qr_code_note']=='on') echo " CHECKED "; ?>>
              <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code_note]"><?php _e("QR Code's note (a note explaining what a QR Code is, if it exists)", 'wpp'); ?></label></span>
            </li>

            <li>
              <?php $attrs = self::get_pdf_list_attributes('property_stats'); ?>
              <?php if (!empty($attrs)) : ?>
              <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_details]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_details]" <?php if(isset($wpp_pdf_flyer['pr_details']) && $wpp_pdf_flyer['pr_details']=='on') echo " CHECKED "; ?>>
              <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_details]"><?php _e('Details','wpp'); ?></label></span>
              <div class="flyer-detail-attributes wp-tab-panel hidden">
                <ul>
                <?php foreach ($attrs as $slug => $attr) : ?>
                  <li>
                    <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][detail_attributes][<?php echo $slug; ?>]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][detail_attributes][<?php echo $slug; ?>]" <?php if(isset($wpp_pdf_flyer['detail_attributes'][$slug]) && $wpp_pdf_flyer['detail_attributes'][$slug]=='on') echo " CHECKED "; ?>>
                    <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][detail_attributes][<?php echo $slug; ?>]"><?php echo $attr; ?></label></span>
                  </li>
                <?php endforeach; ?>
                </ul>

              </div>

              <script type="text/javascript">
                jQuery(document).ready(function(){
                  var pr_details = jQuery("input[name='wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_details]']");
                  var attrs_block = jQuery(".flyer-detail-attributes");
                  /* When details option is checked we show options for attributes */
                  if (pr_details.is(':checked')) {
                    attrs_block.show();
                  }

                  pr_details.change(function(){
                    if (pr_details.is(':checked')) {
                      attrs_block.show('slow');
                    } else {
                      attrs_block.hide('slow');
                    }
                  });
                });
              </script>
              <?php endif; ?>
            </li>

            <li>
              <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_description]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_description]" <?php if(isset($wpp_pdf_flyer['pr_description']) && $wpp_pdf_flyer['pr_description']=='on') echo " CHECKED "; ?>>
              <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_description]"><?php _e('Description (if exists)','wpp'); ?></label></span>
            </li>

            <li>
              <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_location]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_location]" <?php if(isset($wpp_pdf_flyer['pr_location']) && $wpp_pdf_flyer['pr_location']=='on') echo " CHECKED "; ?>>
              <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_location]"><?php _e('Location on Map (if exists)','wpp'); ?></label></span>
            </li>

            <li>
              <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_features]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_features]" <?php if(isset($wpp_pdf_flyer['pr_features']) && $wpp_pdf_flyer['pr_features']=='on') echo " CHECKED "; ?>>
              <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_features]"><?php _e('Features (if exists)','wpp'); ?></label></span>
            </li>

            <li>
              <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_agent_info]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_agent_info]" <?php if(isset($wpp_pdf_flyer['pr_agent_info']) && $wpp_pdf_flyer['pr_agent_info']=='on') echo " CHECKED "; ?>>
              <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_agent_info]"><?php _e('Agent Information (if exists)','wpp'); ?></label></span>
            </li>
        </ul>
      </td>
    </tr>

    <tr valign="top">
    <th scope="row"><?php _e('Primary Photo Size','wpp'); ?></th>
    <td>
      <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][feature_settings][wpp_pdf_flyer][primary_photo_size]&selected={$wpp_pdf_flyer['primary_photo_size']}"); ?>
    </td>
    </tr>
    <tr valign="top">
      <th scope="row"><?php _e('Font','wpp'); ?></th>
      <td>
        <?php wpp_tcpdf_get_HTML_font_list("name=wpp_settings[configuration][feature_settings][wpp_pdf_flyer][setfont]&selected={$wpp_pdf_flyer['setfont']}"); ?><br>
        <span class="description"><?php _e('The default font is Helvetica. If you have any problems with the current font try choosing another one from the list.','wpp'); ?></span>
      </td>
    </tr>
    <tr>
      <th><?php _e('Secondary Images','wpp'); ?></th>
      <td>
      <ul>
        <li>
        <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][feature_settings][wpp_pdf_flyer][secondary_photos]&selected={$wpp_pdf_flyer['secondary_photos']}"); ?>
      </li>
      <li>
	  <?php printf(__('Show %1$s images.', 'wpp'), '<input type="text" size="5" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][num_pictures]" value='.$wpp_pdf_flyer['num_pictures'].'>'); ?>
      </li>
      </ul>
      </td>
    </tr>
    <tr valign="top">
      <th scope="row"><?php _e('Logo URL','wpp'); ?></th>
      <td>
      <input type="text" size="60" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][logo_url]" value="<?php echo $wpp_pdf_flyer['logo_url']; ?>">
      <span class="description"><?php _e('Use JPEG and GIF images only.','wpp'); ?></span>
      </td>
    </tr>
    <?php /* Removed because sized are hardcoded in generation.
    <tr valign="top">
      <th scope="row"><?php _e('Page Format', 'wpp'); ?></th>
      <td>
      <select name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][flyer_page_format]">
        <option value="A4" <?php selected('A4', $wpp_pdf_flyer['flyer_page_format']); ?>>A4</option>
        <option value="LETTER" <?php selected('LETTER', $wpp_pdf_flyer['flyer_page_format']); ?>>Letter</option>
        <option value="LEGAL" <?php selected('LEGAL', $wpp_pdf_flyer['flyer_page_format']); ?>>US Legal</option>
      </select>
      </td>
    </tr>
    */ ?>
    <tr valign="top">
      <th scope="row"><?php _e('Header color','wpp'); ?></th>
      <td>
      <input type="text" class="wpp_input_colorpicker" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][header_color]" value="<?php echo $wpp_pdf_flyer['header_color']; ?>">
      <span class="description"><?php _e('Header color','wpp'); ?></span>
      </td>
    </tr>

    <tr valign="top">
      <th scope="row"><?php _e('Section background color','wpp'); ?></th>
      <td>
      <input type="text" class="wpp_input_colorpicker" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][section_bgcolor]" value="<?php echo $wpp_pdf_flyer['section_bgcolor']; ?>">
       </td>
    </tr>

     <?php do_action('wpp_flyer_settings_table_bottom', $wpp_pdf_flyer); ?>

    </table>



    <table class='form-table'>
    <tr valign="top">
      <td colspan="2">
      <br class="cb" />
      <span class="description">
      <?php _e('Shortcode examples:<br />','wpp'); ?>
      <?php _e('<strong>[property_flyer]</strong> - Returns a html link to the PDF Flyer<br />','wpp'); ?>
      <?php _e('<strong>[property_flyer title=\'PDF Flyer\']</strong> - Returns a html link to the PDF Flyer with custom title.<br />','wpp'); ?>
      <?php _e('<strong>[property_flyer urlonly=\'yes\']</strong> -  Returns the raw URL to the PDF Flyer (for use in custom html).<br />','wpp'); ?>
      <?php _e('<strong>[property_flyer class=\'custom_css_class\']</strong> - For use with a custom CSS class.<br />','wpp'); ?>
      <?php _e('<strong>[property_flyer image=\'url_to_custom_image\']</strong> - Returns url_to_custom_image with a link to the PDF Flyer.','wpp'); ?>
      </span>
      </td>
    </tr>
    </table>

    <div class="wpp_settings_block" style="margin: 10px 10px 0;">
        <span><?php _e('You can regenerate all your PDF flyers, but depending on your server, it can be very time consuming if you have many properties.','wpp'); ?></span>
        <input type="button" id="wpp_ajax_regenerate_all_flyers" value="<?php _e('Regenerate all Flyers','wpp'); ?>">&nbsp;<img style="display:none;" id="regenerate_all_flyers_ajax_spinner" src="<?php echo WPP_URL; ?>images/ajax_loader.gif" />
        <br/><input style="display:none;" type="button" id="wpp_ajax_regenerate_all_flyers_close" value="<?php _e('Close Result\'s Logs','wpp'); ?>">
        <pre class="wpp_class_pre hidden" id="wpp_ajax_regenerate_all_flyers_result" style="height:300px;"></pre>
    </div>
    <script type="text/javascript">
    jQuery('#wpp_ajax_regenerate_all_flyers').click(function(){
        var ajaxSpinner = jQuery('#regenerate_all_flyers_ajax_spinner');
        var closeButton = jQuery("#wpp_ajax_regenerate_all_flyers_close");
        var resultBox = jQuery('#wpp_ajax_regenerate_all_flyers_result');

        var wpp_recursively_generate_pdf_flyer = function( data, callback ) {
          var item = data.shift();
          jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: 'action=wpp_generate_pdf_flyer&post_id=' + item.post_id,
            complete: function( r, status ) {
              if( status === 'success' ) {
                var result = eval('(' + r.responseText + ')');
                if(result.success == 1) {
                  putLog('<?php _e('PDF Flyer for property "','wpp'); ?>' + item.post_title + '<?php _e('" is generated.','wpp'); ?>', resultBox);
                } else {
                  putLog('<?php _e('<b>Error. PDF Flyer for property "','wpp'); ?>' + item.post_title + '<?php _e('" could not be generated.</b>','wpp'); ?>', resultBox);
                }
              } else {
                putLog('<?php _e('Could not regenerate PDF Flyer for property "','wpp'); ?>' + item.post_title + '<?php _e('". Looks like, something caused error on server.','wpp'); ?>', resultBox);
              }
              if ( data.length == 0 ) {
                if( typeof callback === 'function' ) {
                  callback();
                }
              } else {
                wpp_recursively_generate_pdf_flyer( data, callback );
              }
            }
          });
        }

        ajaxSpinner.show( 'fast', function() {
          resultBox.show( 'fast', function() {
            jQuery.ajax({
              url: '<?php echo admin_url('admin-ajax.php'); ?>',
              //async: false,
              data: 'action=wpp_get_property_ids',
              complete: function( r, status ) {

                if( status === 'success' ) {

                  var data = eval('(' + r.responseText + ')');
                  if( data.length > 0 ) {
                    putLog('<?php _e('Property List is got. Start generate PDF Flyers...','wpp'); ?>', resultBox);

                    // Loop all properties
                    wpp_recursively_generate_pdf_flyer( data, function() {
                      putLog('<?php _e('Finished.','wpp'); ?>', resultBox);
                      ajaxSpinner.hide();
                      closeButton.show();
                    } );

                  } else {
                    putLog('<?php _e('There are no any property to generate PDF Flyer for.','wpp'); ?>', resultBox);
                    ajaxSpinner.hide();
                    closeButton.show();
                  }
                } else {
                  putLog('<?php _e('Looks like, something caused error on server. Please, try to regenerate PDF Flyers later.','wpp'); ?>', resultBox);
                  ajaxSpinner.hide();
                  closeButton.show();
                }

              }
            } );
          } );
        } );
        return false;
    });

    jQuery("#wpp_ajax_regenerate_all_flyers_close").click(function(){
        var closeButton = jQuery(this);
        var resultBox = jQuery('#wpp_ajax_regenerate_all_flyers_result');

        resultBox.hide();
        resultBox.html('');
        closeButton.hide();
    });


    function putLog (log, el) {
        if (typeof log != 'undefined' && typeof el == 'object'){
            if (jQuery('.logs', el).length == 0) {
                el.append('<ul class="logs"></ul>');
            }
            jQuery('.logs', el).append('<li>' + log + '</li>');
        }
    }

    </script>
    <?php

  }


    /**
    * Return default PDF settings
    *
    * @return array
    */
    function return_defaults() {
        $default = array ();
        $default['num_pictures'] = 3;
        $default['header_color'] = '#e6e6fa';
        $default['section_bgcolor'] = '#bbbbbb';
        return $default;
    }

  /**
   * Handles the pdf_flyer shortcodes and it's attributes.
   *
   * @param string $atts parameter string
   * @global $post
   * @uses get_pdf_flyer_permalink
   * @return string
   */
  function shortcode_pdf_flyer( $atts ) {
    global $post;

    if($post->post_type != 'property') {
      return false;
    }
    $title = '';
    $urlonly = 'no';
    $class = 'wpp_pdf_link btn btn-info btn-large';
    $image = false;
    extract(shortcode_atts(array(
      'title' => '<span class="wpp_pdf_flyer_icon">[PDF]</span> ' . $post->post_title,
      'urlonly' => 'no',
      'class' => 'wpp_pdf_link btn btn-info btn-large',
      'image' => false
    ), $atts));

    $get_pdf_url = get_pdf_flyer_permalink($post->ID);

    if( $get_pdf_url == false ) {
      return sprintf(__('Could not find a PDF flyer for %1s.', 'wpp'), $post->post_name);
    }

    /**
     * Image attribute is set. Return the image linked to the pdf document.
     */
    if( $image != false ) {
      return '<a href="' . $get_pdf_url . '" class="' . $class .'"><img src="'. $image . '" /></a>';
    }

    /**
     * Only returns url to the PDF, not any formatted html. So users can format their
     * own html easily.
     */
    if( $urlonly == 'yes' ) {
      return $get_pdf_url;
    }


    return '<a href="' . $get_pdf_url . '" class="' . $class .'">' . $title . '</a>';

  }

  /**
   * Handles the pdf_flyer shortcodes and it's attributes.
   *
   * @param string $atts parameter string
   * @global $post
   * @uses get_pdf_flyer_permalink
   * @return string
   */
  function shortcode_pdf_list( $atts ) {
    global $wp_properties, $wpdb;
    $title = '<span class="wpp_pdf_flyer_icon">[PDF]</span> ';
    $name = '';
    $urlonly = 'no';
    $class = 'wpp_pdf_link btn btn-info btn-large';
    $image = false;
    extract(shortcode_atts(array(
      'title' => '<span class="wpp_pdf_flyer_icon">[PDF]</span> ' . $atts['name'],
      'name' => '',
      'urlonly' => 'no',
      'class' => 'wpp_pdf_link btn btn-info btn-large',
      'image' => false
      ), $atts));

    //** If name is not set, there is no sense to continue */
    if(empty($name)) {
      return '';
    }

    //** Get all PDF Lists */
    $pdf_lists = $wp_properties['configuration']['feature_settings']['wpp_pdf_flyer']['pdf_lists'];

    //** Try find PDF List by title (name) */
    if(is_array($pdf_lists)) {
      foreach($pdf_lists as $key => $list) {
        if(strtolower($name) == strtolower($list['title'])) {
          $slug = $key;
          $pdf_list = $list;
          break;
        }
      }
    }

    if(empty($pdf_list) || empty($slug)) {
      return '';
    }

    //** Determine if Restricted Public Access is set */
    if(is_array($pdf_list['options'])) {
      $key = array_search('restrict_public_access', $pdf_list['options']);
      if($key !== false) {
        $user = wp_get_current_user();
        if($user->{$wpdb->prefix.'user_level'} == 0) {
          return '';
        }
      }
    }

    $pdf_url = get_pdf_list_permalink($slug);

    //** Image attribute is set. Return the image linked to the pdf document. */
    if( $image != false ) {
      return '<a href="' . $pdf_url . '" class="' . $class .'"><img src="'. $image . '" /></a>';
    }

    //** Only returns url to the PDF, not any formatted html. So users can format their */
    //** own html easily. */
    if( $urlonly == 'yes' ) {
      return $pdf_url;
    }

    return '<a href="' . $pdf_url . '" class="' . $class .'">' . $title . '</a>';
  }

   /**
    * Removed PDF flyer on property save.
    * It will be created on next request.
    *
    * Hooks into save_property hook.
    *
    * @author odokienko@UD
    */
    function check_flyer_pdf ($post_id){
      global $wp_properties, $wpdb;

      foreach( (array) $wpdb->get_col( $wpdb->prepare( "
        SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_title like %s AND post_type = 'attachment'
      ", $post_id, '% ' . __('Flyer', 'wpp') ) ) as $attachment_id) {

        wp_delete_attachment( $attachment_id, true );

      }

      return;

    }


    /**
    * Creates the PDF flyer and saves it to disk.
    *
    * Hooks into save_property hook.
    *
    * @todo Fix the dimensions so they can actually be set by user to A4, Letter, US Legal, etc. Right now an arbitrary document size is generated based on image sizes. - potanin@UD
    * @todo Need to display some sort of warning if PDF cache directy cannot be created
    *
    * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
    */
    function create_flyer_pdf($post_id, $debug = false)  {
        global $wp_properties, $post, $wpdb;

        //** Include TCPDF here to avoid double class declaration. korotkov@ud */
        @include_once(WPP_Path.'third-party/tcpdf/tcpdf.php');

        //** Include extended TCPDF class WPP_PDF_Flyer */
        @include_once(WPP_Path.'third-party/tcpdf/wpp_pdf_flyer.php');

        ini_set('memory_limit', '608M');

        $property = WPP_F::get_property( $post_id );

        //** Load PDF Settings */
        $wpp_pdf_flyer = $wp_properties['configuration']['feature_settings']['wpp_pdf_flyer'];

        //** If, some reason, PDF Settings don't exist, we load PDF default settings */
        if(empty($wpp_pdf_flyer)) {
          $wpp_pdf_flyer = class_wpp_pdf_flyer::return_defaults();
        }

        //** Check Primary Photo's (Featured Photo) size */
        if (empty($wpp_pdf_flyer['primary_photo_size']) || trim($wpp_pdf_flyer['primary_photo_size']) == '-') {
            $wpp_pdf_flyer['primary_photo_size'] = 'medium';
        }
        //** Check Secondary Photo's size */
        if (empty($wpp_pdf_flyer['secondary_photos']) || trim($wpp_pdf_flyer['secondary_photos']) == '-') {
            $wpp_pdf_flyer['secondary_photos'] = 'medium';
        }
        //** Check Agent Photo's size */
        if (empty($wpp_pdf_flyer['agent_photo_size']) || trim($wpp_pdf_flyer['agent_photo_size']) == '-') {
            $wpp_pdf_flyer['agent_photo_size'] = 'thumbnail';
        }

        if (empty($wpp_pdf_flyer['flyer_page_format'])) {
          $wpp_pdf_flyer['flyer_page_format'] = 'A4';
        }

        $uploads = wp_upload_dir();

        //** Make sure that PDF cache folder is writable, attempt making the directory
        if(is_writable($uploads['path'])) {
          if(!file_exists($uploads['path'])) mkdir($uploads['path']);
        } else {
        //** @TODO: Need to display some sort of warning if PDF cache directy cannot be created */

          return false;
        }

        $property_type = $property['property_type'];

      //** Load best template, or $template_path is false, and default will be loaded from this file */

        $template_path = WPP_F::get_template_part(array(
          "property-flyer-$property_type",
          'custom-flyer',
          'property-flyer'
        ), array(WPP_Templates) );

      //** At this point everything should be in order to load TCPDF files */
        require_once WPP_Path.'third-party/tcpdf/phpqrcode.php';

      //** Check, if featured image's url exsists we approve featured image's url */
        if( !empty( $property['featured_image'] ) ) {

            $primary_image = wpp_get_image_link($property['featured_image'], $wpp_pdf_flyer['primary_photo_size'], array('return' => 'array'));

            if(WPP_F::file_in_uploads_exists_by_url($primary_image['link'])) {
              if(isset($headers['Content-Type']) && strpos( $headers['Content-Type'], 'image' ) === false) {
                unset($featured_image_url);
                unset($property['featured_image_url']);
              } else {
                $featured_image_url = $primary_image['link'];
              }
            } else {
              unset($featured_image_url);
              unset($property['featured_image_url']);
            }
        }

      //** Check, if logo image's url exsists we approve logo's image url */
        if( !empty( $wpp_pdf_flyer['logo_url'] ) ) {
            $headers = @get_headers( $wpp_pdf_flyer['logo_url'] , 1 );
            if( strpos( $headers[0], '200' )) {
              if(isset($headers['Content-Type']) && strpos( $headers['Content-Type'], 'image' ) === false) {
                unset($logo_url);
                unset($wpp_pdf_flyer['logo_url']);
              } else {
                $logo_url= $wpp_pdf_flyer['logo_url'];
              }
            } else {
              unset($logo_url);
              unset($wpp_pdf_flyer['logo_url']);
            }
        }
        $filename = $property['post_name'];
        $filename = remove_accents ($filename);     // WordPress remove_accents. /wp-includes/formatting.php.
        $filename = sanitize_file_name ($filename);   // WordPresssanitize_file_name /wp-includes/formatting.php
        $filename = ereg_replace("[^-_.A-Za-z0-9]", "", $filename); // remove all character symbols
        $filename = ereg_replace("_-", "_", $filename); // Change "abc_-abc" to "abc_abc"
        $filename = ereg_replace("-_", "-", $filename); // Change "abc-_abc" to "abc-abc"

//** Set QR code. If image's file doesn't exist, we create it. */
        $qrcode_path = $uploads['path'].'/'.$filename.'_qr.png';
        $qrcode      = $uploads['url'] .'/'.$filename.'_qr.png';
        if($wpp_pdf_flyer['qr_code']=='on' && class_exists('QRcode')) {
          // If, some reason, file already exists, - remove it to avoid conflict.
          if(file_exists($qrcode_path)) {
            unlink($qrcode_path);
          }
          // Generates QR Code image file
          QRcode::png($property['permalink'], $qrcode_path,2,2);
        }

        $wpp_pdf_flyer['featured_image_url'] = $featured_image_url;
        $wpp_pdf_flyer['featured_image_width'] = $primary_image['width'];
        $wpp_pdf_flyer['featured_image_height'] = $primary_image['height'];
        if(file_exists($qrcode_path)) {
          $wpp_pdf_flyer['qr_code'] = $qrcode;
        }

      //** Set list of excluded property stats */
        $property_stats = self::get_pdf_list_attributes('property_stats');
        $excluded_stats = array();
        foreach((array)$property_stats as $slug => $attr) {
          if(!array_key_exists($slug, (array)$wpp_pdf_flyer['detail_attributes'])) {
            $excluded_stats[] = $slug;
          }
        }
        $wpp_pdf_flyer['excluded_details_stats'] = implode(',',$excluded_stats);

        if(!empty($logo_url)) {
            $wpp_pdf_flyer['logo_url'] = $logo_url;
        }

        $wpp_pdf_flyer['agent_photo_width'] = class_wpp_pdf_flyer::get_pdf_image_size($wpp_pdf_flyer['agent_photo_size']);

        if ( $wpp_pdf_flyer['featured_image_width'] < ($wpp_pdf_flyer['agent_photo_width'] * 2 + 400) ) {
            $wpp_pdf_flyer['first_col_width'] = $wpp_pdf_flyer['agent_photo_width'] * 2 + 400;
        } else {
            $wpp_pdf_flyer['first_col_width'] = $wpp_pdf_flyer['featured_image_width'];
        }

        $wpp_pdf_flyer['second_photo_width'] = class_wpp_pdf_flyer::get_pdf_image_size($wpp_pdf_flyer['secondary_photos']);
        $wpp_pdf_flyer['pdf_width'] = $wpp_pdf_flyer['first_col_width'] + $wpp_pdf_flyer['second_photo_width'] + 70;

      //** Set font sizes */
        $em = round($wpp_pdf_flyer['pdf_width'] / 350);
        if ($em == 0) $em = 1;
        $wpp_pdf_flyer['font_size_content'] = $em * 16;
        $wpp_pdf_flyer['font_size_header'] = $em * 26;
        $wpp_pdf_flyer['font_size_note'] = $em * 12;

      //** Set format of PDF document */
        $wpp_pdf_flyer['format'] = array(round($wpp_pdf_flyer['pdf_width'], 3), round($wpp_pdf_flyer['pdf_width'] *  841.89 / 595.276, 3));

        //** Scan through images and verify they are accessible */
        foreach( (array) $property['gallery'] as $attachment_key => $attachment ) {
          if( !file_exists( get_attached_file( $attachment['attachment_id'] )  ) ) {
            unset(  $property[ 'gallery' ][ $attachment_key ] );
          }
        }

        $wpp_pdf_flyer = array_filter( (array) $wpp_pdf_flyer );

        //** TODO: if a custom template is placed in the theme directory. Suggest calling it property-flyer.php */
        if( $template_path ) {
            $site_url = site_url();
            ob_start();
            include $template_path;
            $html = ob_get_contents();
            ob_end_clean();
        } else {
            //** No custom template, using the default one from the function */
            $html = class_wpp_pdf_flyer::default_flyer_template( $wpp_pdf_flyer, $property );
        }



        $pdf = new WPP_PDF_Flyer('P', PDF_UNIT, $wpp_pdf_flyer['format'], true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setFontSubsetting(true);
        if ($wpp_pdf_flyer['setfont']){
          $pdf->SetFont($wpp_pdf_flyer['setfont']);
        }
        $pdf->SetCreator("WP-Property");
        $pdf->SetAuthor("WP-Property");
        $pdf->SetTitle($property['post_name']);
        $pdf->SetSubject($property['permalink']);
        $pdf->SetFooterMargin(0);
        $pdf->SetTopMargin(1);
        $pdf->SetLeftMargin(10);
        $pdf->SetRightMargin(10);
        $pdf->AddPage('P', $wpp_pdf_flyer['format']);

        if($pdf->wpp_error_log) {
          update_post_meta($post_id, 'wpp_post_error', $pdf->wpp_error_log);
        }

        $pdf->writeHTML( $html, true, false, true, false, '' );

        $lastPage = $pdf->getPage();
        $lastPageContentLength = strlen($pdf->getPageBuffer($lastPage));
        if ($lastPageContentLength < 400){
          $pdf->deletePage($lastPage);
        }


        if(!empty($property['post_name'])) {
          $pdf->Output($uploads['path'].'/'.$filename.'.pdf', 'F');
        }

        //** Checks if the flyer has been created, and adds it as an attachment to the Post */
        if( file_exists($uploads['path'].'/'.$filename.'.pdf') ) {
            $attachment_title = $property['post_title'] . ' ' . __('Flyer', 'wpp');
              //** Check to see if the flyer is already attached to the object */
            $flyer_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type='attachment'", $attachment_title ));

            if($flyer_id){
              update_attached_file( $flyer_id, $uploads['path'].'/'.$filename.'.pdf' );
            }

            if(!$flyer_id){
                $flyer_url =  $uploads['url'].'/'.$filename.'.pdf';//get_pdf_flyer_permalink( $post_id );

                // Attach the PDF and QR code as attachments, need filter to exclude QR code from image galleries
                $object = array(
                    'post_title' => $attachment_title,
                    'post_content' => __('PDF flyer for ', 'wpp') . $property['post_title'],
                    'post_type' => 'attachment',
                    'post_parent' => $post_id,
                    'post_date' =>  current_time('mysql'),
                    'post_mime_type' => 'application/pdf',
                    'guid' => $flyer_url
                );

                $flyer_id = wp_insert_attachment($object);
                update_attached_file( $flyer_id, $uploads['path'].'/'.$filename.'.pdf' );
            }
        }

        if(file_exists($qrcode_path)) {
          //** Remove QR code image if it exists. The reason: It's not used anywhere except the current function */
          unlink($qrcode_path);
        }

        return $flyer_id ? $flyer_id : false;
    }


    /**
    * Returns HTML for a default PDF flyer template
    *
    * Called by class_wpp_pdf_flyer::create_flyer_pdf()  if no template is found.
    * @ Todo fix default template.
    * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
    */
    function default_flyer_template($wpp_pdf_flyer,$property) {
        ob_start();
        ?>
        <html>
            <head>
                <title></title>
                <style type="text/css">
                    div.heading_text {font-size:<?php echo $wpp_pdf_flyer['font_size_header']; ?>px;border-bottom:2px solid <?php echo (!empty($wpp_pdf_flyer['section_bgcolor']) ? $wpp_pdf_flyer['section_bgcolor'] : '#DADADA'); ?>;}
                    .pdf-text {font-size:<?php echo $wpp_pdf_flyer['font_size_content']; ?>px;}
                    .pdf-text .attribute .separator{ display: inline-block; padding: 0 5px 0 2px; }
                    .pdf-note {font-size:<?php echo $wpp_pdf_flyer['font_size_note']; ?>px;}
                    table.bg-header {background-color:<?php echo (!empty($wpp_pdf_flyer['header_color']) ? $wpp_pdf_flyer['header_color'] : '#EDEDED'); ?>;}
                    table.bg-section {background-color:<?php echo (!empty($wpp_pdf_flyer['section_bgcolor']) ? $wpp_pdf_flyer['section_bgcolor'] : '#EDEDED'); ?>;}
                </style>
            </head>
            <body><table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td height="15">&nbsp;
                        </td>
                    </tr>
                    <?php if( !empty( $wpp_pdf_flyer['logo_url'] ) ) : ?>
                    <tr>
                        <td><img class="header_logo_image" src="<?php echo $wpp_pdf_flyer['logo_url']; ?>" alt=""/>
                        </td>
                    </tr>
                    <tr>
                        <td height="15">&nbsp;
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if ( !empty( $wpp_pdf_flyer['pr_title']) ) : ?>
                    <tr>
                        <td><table cellspacing="0" cellpadding="10" border="0" class="bg-header" style="text-align:left;" width="100%">
                                <tr>
                                    <td><span style="font-size:<?php echo $wpp_pdf_flyer['font_size_header']; ?>px;"><b><?php echo $property['post_title'];?></b></span>
                                        <?php $tagline = $property['tagline']; ?>
                                        <?php if (!empty($wpp_pdf_flyer['pr_tagline']) && !empty($tagline)) : ?>
                                      <br/><span style="font-size:<?php echo $wpp_pdf_flyer['font_size_content']; ?>px;color:#797979;"><?php echo $tagline ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td height="15">&nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td><table cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="<?php echo $wpp_pdf_flyer['first_col_width'] ?>"><table>
                                        <?php if( !empty( $wpp_pdf_flyer['featured_image_url']) ) : ?>
                                        <tr>
                                            <td colspan="3"><table cellspacing="0" cellpadding="10" border="0" class="bg-section">
                                                <tr>
                                                    <td><img src="<?php echo $wpp_pdf_flyer['featured_image_url']; ?>" width="<?php echo ($wpp_pdf_flyer['first_col_width']-20); ?>" alt="" />
                                                    </td>
                                                </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="15">&nbsp;
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td id="left_column" border="0" width="<?php echo ($wpp_pdf_flyer['first_col_width'] / 2 - 7 ); ?>"><table cellspacing="0" cellpadding="0" border="0">
                                                <?php do_action( 'wpp_flyer_left_column', $property, $wpp_pdf_flyer ); ?>
                                                <tr>
                                                    <td></td>
                                                </tr>
                                                </table>
                                            </td>
                                            <td width="14">&nbsp;
                                            </td>
                                            <td id="middle_column" border="0" width="<?php echo ($wpp_pdf_flyer['first_col_width'] / 2 - 7 ); ?>"><table cellspacing="0" cellpadding="0" border="0">
                                                <?php do_action( 'wpp_flyer_middle_column', $property, $wpp_pdf_flyer ); ?>
                                                <tr>
                                                    <td></td>
                                                </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                    <td width="15">&nbsp;
                                    </td>
                                    <td width=""><table cellspacing="0" cellpadding="0" width="100%">
                                        <?php if(is_array($property['gallery']) && !empty($property['gallery'])) :
                                          $counter = 0;
                                        ?>
                                        <?php foreach($property['gallery'] as $image) : ?>
                                            <?php
                                            if($counter == $wpp_pdf_flyer['num_pictures']): break; endif;
                                            $counter++;
                                            $this_image = wpp_get_image_link($image['attachment_id'], $wpp_pdf_flyer['secondary_photos'], array('return' => 'array'));
                                            if( empty($this_image)): continue; endif;
                                            ?>

                                              <tr>
                                                  <td><table cellspacing="0" cellpadding="10" border="0" class="bg-section">
                                                      <tr>
                                                          <td>
                                                            <img width="<?php echo ($this_image['width'] - 20 ); ?>" src="<?php echo $this_image['link']; ?>" alt="" />

                                                          </td>
                                                      </tr>
                                                      </table>
                                                  </td>
                                              </tr>
                                              <tr>
                                                  <td height="15">&nbsp;
                                                  </td>
                                              </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php do_action( 'wpp_flyer_right_column', $property, $wpp_pdf_flyer );?>
                                        <tr>
                                          <td width="15">&nbsp;
                                          </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
        </html>
        <?php
        $html = ob_get_contents();
        ob_clean();
        return $html;
    }

  /**
  * Check if a flyer exists
  *
  * @author odokienko@UD
  */
  function flyer_exists($post_id) {
    global $wpdb;

    foreach( (array) $wpdb->get_col( $wpdb->prepare( "
      SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_title like %s AND post_type = 'attachment';
    ", $post_id, '% ' . __( 'Flyer', 'wpp' ) ) ) as $attachment_id) {

      $url = wp_get_attachment_url($attachment_id);
      //var_dump("\$url", $url);

      $result = wp_remote_retrieve_response_code($url, array( 'timeout' => 10));

      if( $result == 200) {
        return true;
      }

    }

    return false;
  }


    /**
  * Add things to Help tab
  *
  * Copyright 2011 TwinCitiesTech.com, Inc.
  */
  function wpp_settings_help_tab() {
  }

  /*
   * Get image size for pdf document
   */
  function get_pdf_image_size($image_size){
    if ($image_size == 'thumbnail'){
      return 150;
    } elseif ($image_size == 'medium'){
      return 300;
    } elseif ($image_size == 'large'){
      return 1024;
    } else {
      $size = WPP_F::image_sizes($image_size);
      return (!empty($size['width'])) ? $size['width'] : false;
    }
  }

    /*
     * Returns ids of properties.
     * Used by AJAX call
     *
     *
     * @return json
     */
    function ajax_get_properties () {
        global $wpdb;
        ob_start();
        $ids = $wpdb->get_results("
            SELECT `pm`.`post_id`,
                `p`.`post_title`
            FROM {$wpdb->prefix}postmeta as `pm`
            LEFT JOIN {$wpdb->prefix}posts AS `p` ON `p`.`ID` = `pm`.`post_id`
            WHERE (meta_key = 'property_type')
        ");

        if(!is_array($ids) && empty($ids)) {
            $ids = array();
        }
        ob_end_clean();
        print json_encode($ids);
        exit();
    }

    /*
     * Generate PDF Flyer for property.
     * Used by AJAX call
     *
     * @return json
     */
    function ajax_generate_pdf_flyer () {
        ob_start();
        $result = array(
            "success" => 1
        );

        if (!empty($_REQUEST['post_id'])) {
            if(self::create_flyer_pdf((int)$_REQUEST['post_id']) === false)  {
                $result['success'] = 0;
                $result['message'] = 'Can not generate PDF Flyer.';
            }
        } else {
            $result['success'] = 0;
            $result['message'] = 'Property ID is absent.';
        }
        ob_end_clean();
        print json_encode($result);
        exit();
    }

    /*
     * Generate PDF List.
     * Used by AJAX call
     *
     * @return json
     */
    function ajax_generate_pdf_list () {
        ob_start();
        $result = array(
            "success" => 1
        );

        if (!empty($_REQUEST['slug'])) {
            if(self::render_pdf_list($_REQUEST['slug'], "force_generate=true&display=false")) {
                $result['success'] = 0;
                $result['message'] = "Can not generate PDF List.";
            }
        } else {
            $result['success'] = 0;
            $result['message'] = "List's Slug is absent.";
        }

        ob_end_clean();
        print json_encode($result);
        exit();
    }

}

  /**
  * Returns link to flyer, or creates a link to on-the-fly Flyer generation if enabled
  *
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  * @author odokienko@UD
  */
  function get_pdf_flyer_permalink($post_id = false, $name = false) {
    global $post, $wpdb, $wp_properties;

    if(!$post_id)
      $post_id = $post->ID;


    if(!$post_id)
      return false;

    foreach( (array) $wpdb->get_col( $wpdb->prepare( "
      SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_title like %s AND post_type = 'attachment'
    ", $post_id, '% ' . __( 'Flyer', 'wpp' ) ) ) as $attachment_id) {
      //var_dump(wp_get_attachment_metadata($attachment_id));
      return wp_get_attachment_url($attachment_id);

    }

    //** Flyer does not exist, create it */
    $permalink = get_option('permalink_structure');
    return get_permalink($post_id) . ( '' != $permalink ? '?' : '&') . 'wpp_flyer_create=true';

  }

  /**
  * Return permalink for PDF List
  *
  * @param string $slug Slug of the PDF List
  * Copyright Usability Dynamics, Inc. <http://usabilitydynamics.com>
  */
  function get_pdf_list_permalink ($slug = false) {
    global $wp_properties;

    //** Get Base Url for PDF List */
    $url = WPP_F::base_url($wp_properties['configuration']['base_slug']);

    //** Determine if permalink is set */
    //** If not, we use GET params */
    $permalink = get_option('permalink_structure');
    if ( '' == $permalink) {
      $slug = '&pdf_list=' . $slug;
    } else {
      //** Check url for slash in the end */
      preg_match('/\/$/', $url, $m);
      if(!$m) {
        $url .= '/';
      }
    }

    return $url . ($slug ? $slug . '.pdf' : '');
  }
