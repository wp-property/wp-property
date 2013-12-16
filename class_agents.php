<?php
/*
Name: Real Astate Agents
Class: class_agents
Feature ID: 7
Minimum Core Version: 1.38.3
Version: 1.9.6.1
Description: Allows managing real estate agents.
Screen ID: property_page_show_agents
*/

add_action('wpp_init', array('class_agents', 'init'));
add_action('wpp_pre_init', array('class_agents', 'pre_init'));
add_action('widgets_init', create_function('', 'return register_widget("AgentWidget");'));

/**
 * class_agents Class
 *
 * Allows managing real estate agents.
 *
 * Documentation
 *  Information about special issues regarding this plugin.
 *  1. Adding images to real estate agents
 *     - the user may add as many images to the profile of an agent as he wants to.
 *     - when clicking the "Add Image" button the following happens:
 *       - a certain value is added to the session
 *       - the user is redirected to the upload form of the media library
 *       - as soon as the user uploaded an image he will be redirected back to the
 *         edit screen of the agent he was working on
 *       - the value is removed from the session
 *
 * Copyright 2012 Usability Dynamics, Inc.  <info@usabilitydynamics.com>
 *
 * @version 1.0
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @subpackage Agents
 */
class class_agents {

  /*
   * (custom) Capability to manage the current feature
   */
  static protected $capability = "manage_wpp_agents";

  /**
   * Special functions that must be called prior to init
   *
   */
  function pre_init() {
    // Add role
    add_role('agent', 'Real Estate Agent', self::get_agent_capabilities());
    add_filter('wpp_role_description_agent', array('class_agents', "role_description"));

    /* Add capability */
    add_filter('wpp_capabilities', array('class_agents', "add_capability"));

    //* Add (remove) role Agent to Administrator */
    add_filter('wpp_settings_save', array('class_agents','add_role_agent_to_admin'));

    add_filter('wpp_feps_submitted', array('class_agents','assign_agent_to_new_feps_listing'));
  }

  /**
   *
   *
   */
  static function init() {

    if(current_user_can(self::$capability)) {
      // Add settings page
      add_filter('wpp_settings_nav', array('class_agents', 'settings_nav'));
      add_action('wpp_settings_content_agents', array('class_agents', 'settings_page'));

      // Posts
      add_action('wpp_publish_box_options', array('class_agents', 'publish_box_options'));
    }

    add_action('save_property', array('class_agents', 'save_property'));

    add_action('admin_init', array('class_agents', 'admin_init'));
    add_action('admin_enqueue_scripts', array('class_agents', 'admin_enqueue_scripts'));

    add_filter('wpp_agent_widget_field_agent_image', array('class_agents', 'wpp_agent_widget_field_agent_image'),0,4);

    // User
    add_action('edit_agent_profile', array('class_agents', 'show_profile_fields'), 10 );
    add_action('edit_agent_profile_update', array('class_agents', 'save_profile_basic'), 10 );
    add_action('edit_agent_profile_update', array('class_agents', 'save_profile_fields'), 11 );
    add_action('edit_agent_profile_update', array('class_agents', 'redirect_agent_page'), 12 );

    //* Upload files (markers) */
    add_action('wp_ajax_wpp_agent_image_upload', array('class_agents','ajax_image_upload'));

    add_action('init', array('class_agents', 'remove_agent_image'), 4 );

    // Agent(s) column to overview page
    add_filter('wpp_admin_overview_columns', array('class_agents', 'wpp_admin_overview_columns'));

    //** Add correct views to agent overview page */
    add_filter('views_property_page_show_agents', array('class_agents', 'overview_view'));

    add_filter('wpp_get_property', array('class_agents', 'wpp_get_property'));
    add_filter('wpp_list_table_can_edit_post', array('class_agents', 'can_edit_post'));
    add_filter('wpp_list_table_can_delete_post', array('class_agents', 'can_edit_post'));

    add_filter("manage_users_custom_column", array('class_agents', 'manage_users_custom_column'), 0, 3);

    add_filter("wpp_get_search_filters", array('class_agents', 'filter_get_search_filters'));
    add_filter("wpp_get_property_month_periods", array('class_agents', 'filter_month_periods_filter'));
    add_filter("wpp_get_users_of_post_type", array('class_agents', 'filter_users_filter'));
    add_filter("wpp_get_properties_quantity", array('class_agents', 'filter_properties_quantity'), 10, 2);
    add_filter("wpp_prefill_meta", array('class_agents', 'filter_property_filter'), 10, 2);
    // Add entry in nav menu
    add_action('admin_menu', array('class_agents', 'admin_menu'));

    // Media Upload
    add_action('media_upload_wpp_agent_image', array('class_agents', 'media_upload_wpp_agent_image'));
    add_action('wpp_flyer_middle_column', array('class_agents', 'pdf_flyer_insert'), 20, 2);
    add_action('wpp_flyer_settings_table_bottom', array('class_agents', 'flyer_settings'), 20, 2);

    // Agent cart
    add_shortcode('agent_card', array('class_agents', 'shortcode_agent_card'));

    // Add action for Denali-specific coments
    add_action('wpp_insert_property_comment', array('class_agents', 'handle_comments'));

    add_action('wpp_import_advanced_options', array('class_agents', 'wpp_import_advanced_options'));

    //* Add filter for wpp_search ( used by WPP_List_Table class for 'All Properties' page ) */
    add_filter('prepare_wpp_properties_search', array('class_agents', 'prepare_wpp_properties_search'));

    //* Hack. Used to avoid issues of some WPP capabilities */
    add_filter('current_screen', array('class_agents', 'current_screen'));

    //* Apply custom 'wpp_agents' queryable key (used in property_overview shortcode) */
    add_filter('get_queryable_keys', array('class_agents', 'get_queryable_keys'));

    //** Agents page load handler */
    add_action('load-property_page_show_agents', array('class_agents', 'property_page_show_agents_load'));
  }

  /**
   * Agent Settings page load handler
   * @author korotkov@ud
   */
  function property_page_show_agents_load() {
    global $wp_properties;


    /** Screen Options */
    add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );

    $contextual_help['Agent Shortcodes'][] = '<h3>'. __("Agent Shortcodes", "wpp") . '</h3>';
    $contextual_help['Agent Shortcodes'][] = '<p>' . __("Show agent information:", "wpp") . ' [agent_card agent_id=1]' . __(", or you can specify which fields to show:", "wpp") . ' [agent_card agent_id=1 fields=display_name,email]</p>';
    $contextual_help['Agent Shortcodes'][] = '<p>' . __("To query agent properties:", "wpp") . '  [property_overview wpp_agents=1]</p>';

    $contextual_help['Shortcode Attributes'][] = '<h3>' . __("Shortcode Attribute Fields", "wpp") . '</h3>';
    $contextual_help['Shortcode Attributes'][] = '<p>'.__('The following attributes can be used in the [agent_card] shortcode.', 'wpp').'</p>';
    $fields = array();
    $fields['agent_image'] = array('name' => 'Agent Image');
    $fields['display_name'] = array('name' => 'Display Name');
    $fields['user_email'] = array('name' => 'Email Address');
    $fields['widget_bio'] = array('name' => 'Widget Bio');
    $fields['full_bio'] = array('name' => 'Full Bio');
    $fields['flyer_content'] = array('name' => 'Flyer Writeup');

    $fields = array_merge($fields, self::clean_array( $wp_properties['configuration']['feature_settings']['agents']['agent_fields'] ));

    $contextual_help['Shortcode Attributes'][] = '<ul>';
    foreach($fields as $key => $label) {
      $all_agent_fields[] = $key;
      $contextual_help['Shortcode Attributes'][] = "<li>{$label[name]} - $key </li>";
    }
    $contextual_help['Shortcode Attributes'][] = '</ul>';

    $contextual_help['Examples'][] = '<h3>' . __("Example Using All Available Fields", "wpp") . '</h3>';

    $contextual_help['Examples'][] = '[agent_card agent_id=1 fields=' . implode(',',$all_agent_fields) . ']';

    //** Hook this is you need to add some helps to Agents Settings page */
    $contextual_help = apply_filters('property_page_show_agents_help', $contextual_help);

    do_action('wpp_contextual_help', array('contextual_help'=>$contextual_help));

  }

  /**
   *
   *
   */
  function admin_init() {
    global $current_screen, $wp_properties, $plugin_page;

    if ($_GET['page'] == 'show_agents' && isset($_REQUEST['action']) &&  $_REQUEST['action'] == 'update' ) {
      do_action('edit_agent_profile_update', $_POST['user_id']);
    }
  }

  /*
   * Adds Custom capability to the current premium feature
   */
  function add_capability($capabilities) {

    $capabilities[self::$capability] = __('Manage Real Estate Agents','wpp');

    return $capabilities;
  }

  /**
  * Display user role selection on agent page if more than one role are allowed to be agents.
  *
  * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
  */
  function overview_view($views) {
    global $wp_properties, $wp_roles;

    //* Making sure that old data gets removed and that there's at least one row */
    $agent_roles = $wp_properties['configuration']['feature_settings']['agents']['agent_roles'];

    //** Do nothing if less than 2 rules */
    if(!is_array($agent_roles) || count($agent_roles) < 2) {
      return false;
    }

    $url = 'edit.php?post_type=property&page=show_agents';
    $users_of_blog = count_users();
    $total_users = $users_of_blog['total_users'];
		$avail_roles =& $users_of_blog['avail_roles'];
    $role_names = $wp_roles->role_names;

    foreach($agent_roles as $role) {

      if(!$avail_roles[$role]) {
        continue;
      }

      $role_object = get_role($role);

      $name = $role_names[$role_object->name];

			if ( $role == $_REQUEST['role'] ) {
				$current_role = $role;
				$class = ' class="current"';
			} else {
        $class = '';
      }


			$name = translate_user_role( $name );

			$name = sprintf( __('%1$s <span class="count">(%2$s)</span>'), $name, $avail_roles[$role] );
			$role_links[$role] = "<a href='" . esc_url( add_query_arg( 'role', $role, $url ) ) . "'$class>$name</a>";

    }

    return $role_links;
  }


  /**
  * Add extra options to XML Importer
  *
  * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
  */
  function wpp_import_advanced_options($current_settings = false) {
    global $wp_properties;

    $agent_fields['display_name'] = 'Display Name';
    $agent_fields['user_email'] = 'Email Address';

    if(is_array($wp_properties['configuration']['feature_settings']['agents']['agent_fields'])) {
      foreach($wp_properties['configuration']['feature_settings']['agents']['agent_fields'] as $meta_key => $meta_data) {
        $agent_fields[$meta_key] = $meta_data['name'];
      }
    }
  ?>
      <li>
        <label class="description"><?php echo __('Match the "Property Agent" attribute to ','wpp');?></label>
        <select  name="wpp_property_import[wpp_agent_attribute_match]">
        <?php foreach($agent_fields as $field_slug => $field_title) { ?>
          <option value="<?php echo esc_attr($field_slug); ?>" <?php selected($field_slug,$current_settings['wpp_agent_attribute_match']); ?>><?php echo $field_title; ?></option>
        <?php } ?>
        </select>
        <label  class="description"><?php echo __(' field, when it is found, and associate with agent.','wpp');?></label>
    </li>
  <?php

  }

  /**
  * Sends notification to an agent.
  *
  * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
  */
  function send_agent_notification($agent_id, $subject = 'Notification', $message = '', $headers='') {

    if(!is_numeric($agent_id))
      return;

    $agent_data = get_userdata($agent_id);

    if(wp_mail($agent_data->user_email, $subject, $message, $headers))
      return true;

    return false;

  }

  /**
  * Adds agent IDs in array format to the property object
  *
  * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
  */
  function wpp_get_property($property) {
    global $wpdb;
    $wpp_agents = get_post_meta($property['ID'], 'wpp_agents');
    $property['wpp_agents'] = $wpp_agents;
    return $property;
  }

  /**
  * Called after a comment is inserted on a property
  *
  * @todo Complete sending agent specific notifications
  *
  * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
  */
  function handle_comments($comment) {

  $property_id = $comment->comment_post_ID;

  //Does the property have agents
  $wpp_agents = get_post_meta($property_id, 'wpp_agents');

  if(empty($wpp_agents))
    return;
  // We have agents
  }

  /**
  * Adds agent-specific settings to flyer page
  *
  *
  * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
  */
  function flyer_settings($wpp_pdf_flyer) {
  ?>
    <tr>
      <th><?php _e("Agent Image Size", 'wpp'); ?></th>
      <td>
           <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][feature_settings][wpp_pdf_flyer][agent_photo_size]&selected={$wpp_pdf_flyer['agent_photo_size']}"); ?>
      </td>
   </tr>
  <?php
  }

  /**
  * Insert agent data into PDF flyer
  *
  *
  * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
  */
  function pdf_flyer_insert( $property, $wpp_pdf_flyer) {
    global $wp_properties;

    if(!is_array($property['wpp_agents'])){
      $property['wpp_agents'] = get_post_meta($property['ID'], 'wpp_agents');
    }

    if(!empty($wpp_pdf_flyer['pr_agent_info']) && !empty($property['wpp_agents'])) {
      $agent_photo_size = $wpp_pdf_flyer['agent_photo_size'];
      $agent_photo_width = $wpp_pdf_flyer['agent_photo_width'];
        ?>
        <tr>
            <td><div class="heading_text"><?php echo __('Agent Information', 'wpp'); ?></div>
            </td>
        </tr>
        <tr>
            <td><br/>
        <?php
        foreach($property['wpp_agents'] as $agent_id) {
          if($agent_photo_size){
            $agent_images = class_agents::get_agent_images($agent_id, $agent_photo_size);
          }
          ?>
            <table cellspacing="0" cellpadding="0" border="0">
              <tr>
                <?php if(!empty($agent_images[0]['src'])) : ?>
                <td width="<?php echo $agent_photo_width; ?>"><?php
                  /** Make sure image exists */
                  if(@getimagesize($agent_images[0]['src'])) { ?><table cellspacing="0" cellpadding="10" border="0" class="bg-section">
                    <tr>
                        <td><img width="<?php echo ($agent_photo_width - 20); ?>" src="<?php echo $agent_images[0]['src']; ?>" alt=""/>
                        </td>
                    </tr>
                    </table>
                  <?php } ?>
                </td>
                <td width="10"></td>
                <?php endif; ?>
                <td width="" class="pdf-text"><?php echo nl2br(get_user_meta($agent_id, 'flyer_content', true)); ?>
                </td>
              </tr>
              <tr>
                <td colspan="3" height="15">&nbsp;
                </td>
              </tr>
            </table>
         <?php
        }
        ?>
            </td>
        </tr>
        <?php
    }
  }

  /**
  * Adds a colum to the overview table
  *
  * Copyright 2011 TwinCitiesTech.com, Inc.
  */
  function wpp_admin_overview_columns($columns) {
    $columns['wpp_agents'] =  __('Agent','wpp');
    return $columns;
  }


  function manage_users_custom_column($nothing, $column_name, $user_id) {
    global $wpdb;

    $user_data  = get_userdata($user_id);

    switch($column_name) {

      case 'id':
        return $user_id;
      break;

      case 'display_name':
        $display_name = stripslashes($user_data->display_name);
        ob_start();
        ?>
        <strong><a href="<?php echo self::get_link_edit($user_id); ?>"><?php echo $display_name; ?></a></strong><br>
        <div class="row-actions">
        <span class="edit"><a href="<?php echo self::get_link_edit($user_id); ?>"><?php _e('Edit', 'wpp'); ?></a> | </span>
        <span class="delete"><a href="<?php echo self::get_link_delete($user_id); ?>" class="submitdelete"><?php _e('Delete', 'wpp'); ?></a></span>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
      break;

      case 'user_email';
        return $user_data->user_email;
      break;

      case 'related_properties':
        $agent_properties = $wpdb->get_var("SELECT count(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = 'wpp_agents' AND meta_value = '{$user_id}'");
        return $agent_properties;
      break;

      default:

        return get_user_meta($user_id, $column_name, true);
      break;
    }
  }



  function manage_property_page_show_agents_columns($columns) {
    global $wp_properties;

    $agent_fields = $wp_properties['configuration']['feature_settings']['agents']['agent_fields'];
    $columns['id'] = "ID";
    $columns['display_name'] = "Display Name";
    $columns['user_email'] = "Email";

    if(!empty($agent_fields))
      foreach($agent_fields as $slug => $data)
        $columns[$slug] = $data['name'];

    $columns['related_properties'] = "Properties";

    return $columns;
  }

  function wpp_agent_widget_field_agent_image($text, $display_fields, $slug, $this_field) {
    global $wp_properties;
    $images =  class_agents::get_agent_images($this_field, apply_filters('wpp_agent_widget_image_size', 'thumbnail'));

    if ($images){
      $return[] = "<li class='wpp_agent_stats_{$slug}'>";
      $return[] ='<div class="wpp_agent_images">';
      foreach ($images as $image){
        $return[] ='<img width="'.$image['width'].'" height="'.$image['height'].'" src="'.$image['src'].'"/>';
      }
      $return[] ='</div>';
      $return[] ="</li>";
    }

    if(is_array($return))
      return implode('',$return);
  }

  /**
   *
   * @param $user
   *
   */
  function metabox_related_properties($user) {
    global $wpdb;

    /* get properties */
    $related = $wpdb->get_results("SELECT DISTINCT(post_title), post_id, post_status FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p on pm.post_id = p.ID WHERE meta_key = 'wpp_agents' AND meta_value='{$user->ID}' ");

    if(empty($related)) {
       _e('No Properties.', 'wpp');
       return;
    }

    echo "<ul class='wpp_agents_related_properties wp-tab-panel'>";
    foreach($related as $row) {
      echo "<li class='wpp_post_status_{$row->post_status}'><a href='".get_edit_post_link($row->post_id)."'>{$row->post_title}</a></li>";
    }
    echo "</ul>";
  }

  /**
   *
   * @param $user
   */
  function metabox_save($user) {
     ?>
    <input type="hidden" value="update" name="action">
    <input type="hidden" value="<?php echo $user->ID; ?>" id="user_id" name="user_id">

    <div id="major-publishing-actions">
      <div id="publishing-action">
        <input type="submit" id="publish" value="<?php if (isset($user->new_user) && $user->new_user) _e('Create Agent', 'wpp'); else _e('Update Agent', 'wpp'); ?>" class="button-primary btn" id="submit" name="submit" />
      </div>
    </div>
    <?php
  }


  /**
   * Renders Agents options ( fields )
   *
   * @param $user
   */
  function metabox_primary_info($user) {
    ?>
    <table class="form-table">
      <tbody>

        <tr class='column-display_name'>
          <th><label for="display_name"><?php _e('Display Name', 'wpp'); ?></label></th>
          <td><input type="text" style="width: 300px; font-size: 1.5em;" class="regular-text" value="<?php print esc_attr($user->display_name); ?>" id="display_name" name="display_name"></td>
        </tr>

        <tr>
          <th><label for="user_login"><?php _e('Username', 'wpp'); ?></label></th>
          <td>
            <input type="text" class="regular-text" <?php if (!isset($user->new_user)) echo ' disabled="disabled"'; ?>value="<?php echo esc_attr($user->user_login); ?>" id="user_login" name="user_login">
            <div class="description"><?php _e('Usernames cannot be changed.', 'wpp'); ?></div>
          </td>
        </tr>

        <tr class='column-user_email'>
          <th><label for="email"><?php _e('E-Mail', 'wpp'); ?></label></th>
          <td><input type="text" class="regular-text" value="<?php echo $user->user_email; ?>" id="email" name="email"></td>
        </tr>

        <tr>
          <th><label for="widget_bio"><?php _e('Widget Bio', 'wpp'); ?></label></th>
          <td>
            <ul>
              <li><textarea   class="code" id="widget_bio" name="agent_fields[widget_bio]" style="width:300px;"><?php echo $user->widget_bio; ?></textarea></li>
              <li><span class="description"><?php _e('Content that is displayed in the agent widget.', 'wpp'); ?></span></li>
            </ul>
          </td>
        </tr>

        <tr class="wpp_agent_options hidden">
          <th><label><?php _e('Options', 'wpp'); ?></label></th>
          <td>
            <?php $options = apply_filters( 'wpp::agents::agent::options', array(), $user ); ?>
            <?php if( !empty( $options ) ) : ?>
              <ul class="wpp_agents_agent_options" >
              <?php foreach( $options as $option ) : ?>
                <li><?php echo $option; ?></li>
              <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <div class="wpp_agents_agent_options"><?php do_action('wpp_agents_agent_options', $user); ?></div>
          </td>
        </tr>

        <tr>
          <th><label for="full_bio"><?php _e('Full Bio', 'wpp'); ?></label></th>
          <td>
            <ul>
              <li><textarea   class="code" id="full_bio" name="agent_fields[full_bio]" style="width:300px;"><?php echo $user->full_bio; ?></textarea></li>
              <li><span class="description"><?php _e('Content that can be displayed in the shortcode.', 'wpp'); ?></span></li>
            </ul>
          </td>
        </tr>

      </tbody>
    </table>
    <?php do_action('edit_agent_profile', $user); ?>
    <?php do_action('edit_agent_profile_extra', $user); ?>
    <?php
  }


  function admin_enqueue_scripts() {
    global $current_screen, $wp_properties;

    if(!isset($current_screen->id)) {
      return;
    }

    if($current_screen->id == 'property_page_show_agents' && (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit')) {
      wp_enqueue_script('post');
      wp_enqueue_script('postbox');
      wp_enqueue_script('wpp-jquery-ajaxupload');
      wp_enqueue_script('wpp-edit-agent', WPP_URL . 'js/wpp.admin.agent.js', array( 'jquery', 'wpp-localization' ), WPP_Version );

      add_meta_box('metabox_primary_info', __('Primary'), array('class_agents','metabox_primary_info'), 'property_page_show_agents', 'normal', 'core');
      add_meta_box('metabox_save', __('Save'), array('class_agents','metabox_save'), 'property_page_show_agents', 'side', 'core');
      add_meta_box('metabox_related_properties', __('Associated Properties'), array('class_agents','metabox_related_properties'), 'property_page_show_agents', 'side', 'core');
      add_meta_box('metabox_profile_images', __('Images'), array('class_agents','metabox_profile_images'), 'property_page_show_agents', 'side', 'core');
    }

    if($current_screen->id == 'property_page_show_agents') {
      wp_enqueue_script('wp-property-backend-global');
      wp_enqueue_script('wp-property-global');

      //** Only do on agent overview page */
      if(!isset($_REQUEST['action'])) {
        /* add_screen_option( 'per_page', array('label' => _x( 'Users', 'users per page (screen options)' )) ); */
        add_filter("manage_property_page_show_agents_columns", array('class_agents', 'manage_property_page_show_agents_columns'));
      }
    }
  }

  /**
   * Adds menu to settings page navigation
   */
  function settings_nav($tabs) {
    $tabs['agents'] = array(
      'slug' => 'agents',
      'title' => __('Agents','wpp')
    );
    return $tabs;
  }

  /**
   * Displays advanced management page
   */
  function settings_page() {
    global $wpdb, $wp_properties, $wp_roles;

    //* Making sure that old data gets removed and that there's at least one row */
    $agent_data = $wp_properties['configuration']['feature_settings']['agents'];

    $agent_roles = $agent_data['agent_roles'];

    $agent_fields = stripslashes_deep(self::clean_array($agent_data['agent_fields']));

    //** Force at lest one role to always be associated */
    if ( empty($agent_roles) ) {
      $agent_roles =  array('agent');
    }

    //** if no agent-meta secists, load in default data, and set $default_data to true so first row is treated as new and canbe edited */
    if ( empty($agent_fields) ) {
      $default_data = true;
      $agent_fields = array(
        'phone_number' => array('name' => 'Phone Number')
      );
    }

    ?>

  <script type='text/javascript'>
    jQuery(document).ready(function() {});
  </script>
  <table class="form-table">
    <tr>
      <th>
        <?php _e('Agent Roles','wpp'); ?>
        <div class="description"><?php _e('User roles that can be associated with properties as agents.','wpp'); ?></div>
        </th>
      <td>
        <ul class="wp-tab-panel ">

          <?php foreach($wp_roles->roles as $role_slug => $role_data){ ?>
            <li>
              <input id="wpp_settings_configuration_feature_settings_agents_agent_roles_<?php echo $role_slug; ?>" type="checkbox" name="wpp_settings[configuration][feature_settings][agents][agent_roles][]" value="<?php echo esc_attr($role_slug); ?>" <?php echo (in_array($role_slug, $agent_roles) ? ' checked="true" ' : ''); ?>/>
              <label for="wpp_settings_configuration_feature_settings_agents_agent_roles_<?php echo $role_slug; ?>"><?php echo $role_data['name']; ?></label>
            </li>
          <?php } ?>
      </ul>

      </td>
    </tr>
    <tr>
      <th>
        <?php _e('Agent Fields','wpp'); ?>
        <div class="description"><?php _e('Extra data fields to be used for agent profiles.','wpp'); ?></div>
      </th>
      <td>
        <p><?php _e('Custom fields may be used to adapt the information about agents to suit your needs.', 'wpp'); ?></p>

        <table id="wpp_agent_fields" class="ud_ui_dynamic_table widefat" allow_random_slug="true">
          <thead>
            <tr>
              <th><?php _e('Field name', 'wpp') ?></th>
              <th style="width:50px;"><?php _e('Slug', 'wpp') ?></th>
              <th>&nbsp;</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($agent_fields as $slug => $field) { ?>
              <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row="<?php echo($default_data ? 'true' : 'false'); ?>">
                <td><input class="slug_setter" type="text" name="wpp_settings[configuration][feature_settings][agents][agent_fields][<?php echo $slug; ?>][name]" value="<?php echo $field['name']; ?>" /></td>
                <td><input type="text" value="<?php echo $slug; ?>" readonly="readonly" class="slug" /></td>
                <td><span class="wpp_delete_row wpp_link"><?php _e('Delete', 'wpp') ?></span></td>
              </tr>
            <?php }  ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3"><input type="button" class="wpp_add_row button-secondary" value="<?php _e('Add Row', 'wpp') ?>" /></td>
            </tr>
          </tfoot>
        </table>
      </td>
    </tr>
  </table>
<?php
  }

  /*
   * Hack.
   * Determine if the current page is post editor and current user is Agent and the current post
   * is not assigned to him or user is not author of post and has no capabilities for edit it
   * we redirect user to general 'All Properties' (overview) page
   *
   * @param object $screen
   * @return object $screen
   * @author peshkov@UD
   */
  function current_screen($screen){
    if($screen->base == "post" && $screen->post_type == "property") {
      if(!empty($_REQUEST['post'])) {
        $id = (int)$_REQUEST['post'];
        $post = get_post($id);
        if(empty($post)) {
          return $screen;
        }
        $current_user = wp_get_current_user();
        $roles = (array)$current_user->roles;

        if(in_array('agent', $roles) && !in_array('administrator', $roles)) {
          $agents = (array)get_post_meta($id, 'wpp_agents');
          $post_type_object = get_post_type_object( $post->post_type );
          if(!in_array($current_user->ID , $agents) ||
            $post->post_author != $current_user->ID &&
            !current_user_can($post_type_object->cap->edit_others_posts)) {
            wp_redirect('edit.php?post_type=property&page=all_properties');
            exit();
          }
        }
      }
    }

    return $screen;
  }

  /*
   * Determine if Administrator can have Agent role
   * and Add/Remove Agent role to/from Administrator
   *
   * @param array $wpp_settings
   * @return array $wpp_settings
   * @author Maxim Peshkov
   */
  function add_role_agent_to_admin($wpp_settings) {
    global $wp_roles;

    //* Determine if Agent role exists at all */
    if(!array_key_exists('agent', $wp_roles->role_names)) {
      return $wpp_settings;
    }

    //* Gel all Administrators */
    $users = get_users(array(
      'role' => 'administrator'
    ));

    //* Get option from WPP settings which allows administrator to be an agent */
    $add_role_to_admin = $wpp_settings['configuration']['feature_settings']['agents']['add_role_to_admin'];

    //* Add or Remove Agent role depending on option */
    foreach($users as $u) {
      $user = new WP_User( $u->ID );
      if(!empty($add_role_to_admin) && $add_role_to_admin == 'true') {
        $user->add_role('agent');
      } else {
        $user->remove_role('agent');
      }
    }

    return $wpp_settings;
  }

  /**
   * Add menu to WP's navigation
   */
  function admin_menu() {
    global $wp_properties;


    if(!empty($wp_properties['labels']['agents']['plural'])) {
      $label = $wp_properties['labels']['agents']['plural'];
    } else {
     $label = __('Agents', 'wpp');
   }

    add_submenu_page('edit.php?post_type=property',$label,$label, self::$capability, 'show_agents', array('class_agents', 'show_agents'));
  }


  /**
   * Helper functions
   */
  function get_link_edit($user_id, $amp = true) {
    $del = $amp ? '&amp;' : '&';
    return 'edit.php?post_type=property'.$del.'page=show_agents'.$del.'user_id='.$user_id.$del.'action=edit';
  }
  function get_link_delete($user_id) {
    return self::get_link_edit($user_id) . '&amp;action=delete';
  }


  /**
   * Displays a list of agents.
   */
  function show_agents() {
    global $wpdb, $wp_messages;

    if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' ) {
      self::edit_agent( $_REQUEST['user_id'] );
      return;
    }

    if(!isset($_REQUEST['role'])) {
      $_REQUEST['role'] = 'agent';
    }

    ?>
    <style type="text/css">
      th.column-id {width: 18px;}
    </style>
    <div class="wrap">
    <div class="icon32" id="icon-users"><br/></div>
    <h2><?php _e('Agents', 'wpp'); ?> <a class="button add-new-h2" href="<?php echo self::get_link_edit(-1); ?>"><?php _e('Add New', 'wpp'); ?></a></h2>


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


    <?php if (isset($_REQUEST['action']) &&  $_REQUEST['action'] == 'delete' ) : ?>
      <?php self::delete_agent( $_REQUEST['user_id'] ); ?>
      <div class="updated fade" id="message"><p><?php _e('User removed.', 'wpp'); ?></p></div>
    <?php endif; ?>
    <?php
      $wpp_agents_list_table = _get_list_table('WP_Users_List_Table');
      $wpp_agents_list_table->prepare_items();

      ?>
      <div class="tablenav">

    <?php
    $wpp_agents_list_table->pagination( 'top' );
    $wpp_agents_list_table->views( );
    ?>
    <br class="clear" />
    </div>
    <?php
    $wpp_agents_list_table->display();
    ?>
    <br class="clear">
    </div>
    <script type="text/javascript">
      jQuery('.tablenav.top').css('display','none');
      jQuery('.tablenav.bottom').css('display','none');
    </script>
    <?php
  }

  /**
   * Displays a list of agents.
   */
  function edit_agent($userId) {
    global $wp_messages, $screen_layout_columns;

    $newUser = $userId == -1 ? true : false;
    # create new user
    if ( $newUser ) {
      $user = new stdClass();
      $user->ID = $userId;
      $user->new_user = true;
    }
    # update existing user
    else {
      $user = get_userdata($userId);
    }

    $use = stripslashes_deep($user);
?>
    <script type="text/javascript">
      jQuery(document).ready(function() {

        jQuery("#your-profile").submit(function() {

          if(jQuery("input#display_name").val() == '') {
            jQuery("input#display_name").focus();
            return false;
          }

          if(jQuery("input#user_login").val() == '') {
            jQuery("input#user_login").focus();
            return false;
          }

          if(!wpp_validate_email(jQuery("input#email").val())) {
            jQuery("input#email").focus();
            jQuery('.wpp_agent_email_nag').remove();
            jQuery("<div class='wpp_agent_email_nag description'><?php _e('Please enter a valid e-mail.'); ?></div>").insertAfter( jQuery("input#email"));
            return false;
          }

        });
      });
    </script>
    <div id="profile-page" class="wpp_agent_page wrap">
      <div class="icon32" id="icon-users"><br/></div>
      <?php if (isset($_REQUEST['status']) &&  $_REQUEST['status'] == 'update' ) : ?>
        <div class="updated fade" id="message"><p><?php _e('Agent updated.', 'wpp'); ?></p></div>
      <?php endif; ?>
      <?php if (isset($_REQUEST['status']) &&  $_REQUEST['status'] == 'create' ) : ?>
        <div class="updated fade" id="message"><p><?php _e('Agent created.', 'wpp'); ?></p></div>
      <?php endif; ?>

      <h2><?php _e('Edit Agent', 'wpp'); ?> <?php echo $user->display_name; ?></h2>

      <?php if(isset($_REQUEST['error']) && $_REQUEST['error']): ?>
      <div class="error">
          <p><?php echo $_REQUEST['error']; ?></p>
      </div>
      <?php endif; ?>

      <?php if(isset($wp_messages['notice']) && $wp_messages['notice']): ?>
      <div class="updated fade">
        <?php foreach($wp_messages['notice'] as $notice_message): ?>
          <p><?php echo $notice_message; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="post" action="" id="your-profile">
        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpp_edit_agent'); ?>" />
        <?php if(!WPP_F::is_older_wp_version('3.4')) : ?>
        <div id="poststuff" class="crm-wp-v34">
          <div id="post-body" class="metabox-holder <?php echo 2 == $screen_layout_columns ? 'columns-2' : 'columns-1'; ?>">
            <div id="postbox-container-1" class="postbox-container">
              <div id="side-sortables" class="meta-box-sortables ui-sortable">
                <?php do_meta_boxes( 'property_page_show_agents', 'side', $user ); ?>
              </div>
            </div>
            <div id="postbox-container-2" class="postbox-container">
              <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                <?php do_meta_boxes( 'property_page_show_agents', 'normal', $user ); ?>
              </div>
              <div id="advanced-sortables" class="meta-box-sortables ui-sortable">
                <?php do_meta_boxes( 'property_page_show_agents', 'advanced', $user ); ?>
              </div>
            </div>
          </div>
        </div><!-- /poststuff -->
        <?php else : ?>
        <div id="poststuff" class="metabox-holder <?php echo 2 == $screen_layout_columns ? 'has-right-sidebar' : ''; ?>">
          <div id="side-info-column" class="inner-sidebar">
            <?php do_meta_boxes( 'property_page_show_agents', 'side', $user ); ?>
          </div>

          <div id="post-body">
            <div id="post-body-content">
              <?php do_meta_boxes( 'property_page_show_agents', 'normal', $user ); ?>
              <?php do_meta_boxes( 'property_page_show_agents', 'advanced', $user ); ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </form>
    </div>
    <?php
  }

  /**
   * Inserts content into the "Publish" metabox on property pages
   */
  function publish_box_options() {
    /* Do not show if there are no agetns */
    $agents = self::get_agents();

    if ( empty($agents) ) {
      return;
    }

    $agents_meta = self::get_agents_postmeta();?>

    <li class="wpp_agent_select_wrap">
      <span class="wpp_agent_selector_title"><?php _e('Associated Agents', 'wpp'); ?></span>
      <div class="wpp_agent_selector wp-tab-panel">
      <ul>
      <?php foreach ( $agents as $agent ) : $agent = stripslashes_deep($agent); ?>
        <li>
          <input type="checkbox" name="wpp_agents[]"  id="wpp_agent_<?php echo $agent->ID; ?>" value="<?php echo $agent->ID; ?>" <?php echo (in_array($agent->ID, $agents_meta) ? 'checked=checked' : ''); ?> />
          <label for="wpp_agent_<?php echo $agent->ID; ?>"><a href="<?php echo self::get_link_edit($agent->ID); ?>"> <?php echo $agent->display_name; ?> </a></label>
        </li>
      <?php endforeach; ?>
      </ul>
      </div>

    </li>
  <?php
  }


  /**
   *  Stores data when saving a property.
   */
  function save_property( $post_id ) {
    global $wpdb;
    if(current_user_can(self::$capability)) {
      //** Delete all old agents */
      delete_post_meta($post_id, 'wpp_agents');
      if($_REQUEST['wpp_agents']) {
        foreach($_REQUEST['wpp_agents'] as $agent_id) {
          add_post_meta($post_id, 'wpp_agents', $agent_id);
        }
      }
    }
    /*
     * Determine if the current user is Agent
     * If so, user will be assigned to the post as agent
     */
    $current_user = wp_get_current_user();
    $roles = $current_user->roles;
    if(in_array('agent', $roles) && !in_array('administrator', $roles)) {
      $agents = self::get_agents_postmeta();
      if(!in_array($current_user->ID, $agents)) {
        add_post_meta($post_id, 'wpp_agents', $current_user->ID);
      }
    }
  }


  /**
   * Adds section to user edit screen
   */
  function show_profile_fields($user) {
    global $wp_properties;
    $fields = self::clean_array( $wp_properties['configuration']['feature_settings']['agents']['agent_fields'] );
    ?>
    <table class="form-table">
      <?php if ( is_array($fields) and !empty($fields) ) : ?>
        <?php foreach($fields as $key => $field) : ?>
          <tr class='column-<?php echo $key; ?>'>
            <th><label><?php echo $field['name']; ?></label></th>
            <td>
              <input type="text" class="regular-text" name="agent_fields[<?php echo $key; ?>]" value="<?php echo esc_attr( $user->{$key}); ?>" />
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="2"><?php printf(__('You can add fields on the <a href="%s">settings page</a>.', 'wpp'), 'edit.php?post_type=property&page=property_settings#tab_agents'); ?></td></tr>
      <?php endif; ?>

      <?php if ( class_exists('class_wpp_pdf_flyer') ) : ?>
        <tr>
          <th><label for="flyerwriteup"><?php _e('Flyer Writeup', 'wpp'); ?></label></th>
          <td>
            <textarea cols="30" rows="5" id="flyerwriteup" name="agent_fields[flyer_content]" style="width:300px;"><?php echo esc_attr( $user->flyer_content); ?></textarea><br/>
            <span class="description"><?php _e('Please enter a writeup for flyers.', 'wpp'); ?></span>
          </td>
        </tr>
      <?php endif; ?>
    </table>
    <?php
  }

  /**
   * Saves user data.
   */
  function save_profile_basic($user_id) {
    if ( $user_id == -1 )
      self::create_agent($_POST['user_login'], $_POST['display_name'], $_POST['email']);
    else
      self::update_agent($user_id, $_POST['display_name'], $_POST['email']);
  }

  /**
   * Saves user fields.
   */
  function save_profile_fields($user_id) {
    # In case user was just created
    if ($user_id == -1)
      $user_id = $GLOBALS['wpp_agent_user_id'];

    # Custom fields
    if ( is_array($_POST['agent_fields']) )
      foreach ($_POST['agent_fields'] as $key => $agent_field){
        update_user_meta($user_id, $key, $agent_field);
			}
  }
  /**
   * Redirect to edit page.
   */
  function redirect_agent_page($user_id){
    # In case user was just created
    global $wp_messages;
    if (isset($wp_messages['wp_agent_error']) && $wp_messages['wp_agent_error'] != ''){
      $wp_agent_error = $wp_messages['wp_agent_error'];
      wp_redirect('edit.php?post_type=property&page=show_agents&user_id='.$user_id.'&action=edit&error='.urlencode($wp_agent_error));
      die();
    }

    if ($user_id == -1){
      $user_id = $GLOBALS['wpp_agent_user_id'];
      wp_redirect('edit.php?post_type=property&page=show_agents&user_id='.$user_id.'&action=edit&status=create');
      die();
    }
    wp_redirect('edit.php?post_type=property&page=show_agents&user_id='.$user_id.'&action=edit&status=update');
    die();
  }

  /**
   * Ajax File (Image) Uploader
   *
   * @return string JSON
   * @author peshkov@UD
   */
  function ajax_image_upload() {
    global $wp_properties;

    $return = array();
    $file_name = $_REQUEST['qqfile'];
    $agent_id = $_REQUEST['agentId'];
    /** Available Extensions */
    $exts = array('jpg','jpeg','png','gif','bmp');
    $ext = pathinfo($file_name, PATHINFO_EXTENSION);

    if(!in_array($ext, $exts)) {
      $return['error'] = __('File should be an image','wpp');
    } elseif(empty($agent_id) || (int)$agent_id == 0){
      $return['error'] = __('Agent ID is not set','wpp');
    } else {
      $upload_dir = wp_upload_dir();
      $files_dir = $upload_dir['basedir'] . '/wpp_agents';
      $files_url = $upload_dir['baseurl'] . '/wpp_agents';
      /** Create DIR if it doesn't exist */
      if(!is_dir($files_dir)) {
        mkdir($files_dir, 0755);
        fopen($files_dir . '/index.php', "w");
      }
      $file_name = wp_unique_filename($files_dir, $file_name);
      $path = $files_dir . '/'. $file_name;
      $url = $files_url . '/'. $file_name;

      /** Try to upload file */
      if ( empty( $_FILES ) ) {
        $temp = tmpfile();
        $input = fopen("php://input", "r");
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
      } else {
        /** for IE!!! */
        move_uploaded_file($_FILES['qqfile']['tmp_name'], $path);
      }

      /* Checks if the image has been uploaded, and adds it to user postmeta */
      if(!file_exists($path)) {
        $return['error'] = __('Looks like, Image can not be uploaded.', 'wpp');
      } else {
        /* Save image as attachment */
        $attachment = array(
          'post_title' => __('Image of Real Estate Agent #', 'wpp') . $agent_id,
          'post_content' => __('Image for User #', 'wpp') . $agent_id,
          'post_type' => "attachment",
          'post_date' =>  current_time('mysql'),
          'post_mime_type' => "image/{$ext}",
          'guid' => $url
        );
        $attachment_id = wp_insert_attachment($attachment);
        update_attached_file( $attachment_id, $path );

        /* Retrieve existing images */
        $image_ids = get_user_meta($agent_id, 'agent_images', false);
        if ( empty($image_ids) ) {
          $image_ids = array();
        } else {
          $image_ids = $image_ids[0];
        }
        /* Add currently added attachment */
        $image_ids[] = intval($attachment_id);
        /* Update user meta */
        update_user_meta($agent_id, 'agent_images', $image_ids);

        $return['success'] = 'true';
        $return['url'] = wpp_get_image_link($attachment_id , 'thumbnail') . '?' . (rand(0,100));
        $return['filename'] = $file_name;
        $return['attachment_id'] = $attachment_id;
      }
    }

    die(htmlspecialchars(json_encode($return), ENT_NOQUOTES));
  }

  /**
   * Adds section to user edit screen
   */
  function metabox_profile_images($user) {
    if ($user->ID == -1){
      echo '<p class="pad10">' .__('Please save first to add images.', 'wpp') . '</p>';
      return;
    }
    $images = self::get_agent_images($user->ID);
    ?>
    <ul id="wpp_agent_image_gallery">
    <?php if ( empty($images) ) : ?>
      <li class="wpp_agent_single_image wpp_no_agent_images"><?php _e('No images found.', 'wpp'); ?></li>
    <?php else : ?>
    <?php foreach ($images as $image) : ?>
      <li class='wpp_agent_single_image'>
        <table>
          <tr><td>
          <img src="<?php echo $image['src']; ?>" alt="" />
          </td></tr>
        </table>
        <div class='delete_image'>
          <a href="<?php echo self::get_link_edit($user->ID).'&amp;remove_agent_image='.$image['id']; ?>"><?php _e('Remove', 'wpp'); ?></a>
        </div>
      </li>
    <?php endforeach; ?>
    <?php endif; ?>
    </ul>
    <div class="clear"></div>
    <div id="wpp_ajax_uploader"></div>
    <noscript>
      <p><?php _e('Please enable JavaScript to use file uploader','wpp'); ?></p>
    </noscript>
    <div class="wpp_widget_action">
      <div class="wpp_agent_image_wrapper">
        <a title="Add an Image" class="button" href="javascript:;" id="wpp_add_agent_image"><?php _e('Add Image', 'wpp'); ?></a>
      </div>
      <div class="wpp_ajax_loader"></div>
      <div class="clear"></div>
    </div>
    <script type="text/javascript">
      jQuery(document).ready(function() {
        if(typeof(qq) == 'undefined') {
          return;
        }
        var uploader = new qq.FileUploader({
          element: jQuery('#wpp_ajax_uploader')[0],
          action: '<?php echo admin_url('admin-ajax.php'); ?>',
          params: {
            action: 'wpp_agent_image_upload',
            agentId: <?php echo $user->ID; ?>
          },
          button: jQuery('#wpp_add_agent_image')[0],
          allowedExtensions: ['jpg', 'jpeg', 'png', 'gif'],
          onSubmit: function(id, fileName){
            jQuery(".wpp_ajax_loader").show();
          },
          onComplete: function(id, fileName, responseJSON){
            jQuery(".wpp_ajax_loader").hide();
            if ( responseJSON ) {
              if(jQuery('#wpp_agent_image_gallery .wpp_no_agent_images').length > 0) {
                jQuery('#wpp_agent_image_gallery .wpp_no_agent_images').remove();
              }
              var url = responseJSON.url;
              var image_id = responseJSON.attachment_id;
              var html = "<li class=\"wpp_agent_single_image\">";
              html += "<table><tr><td><img src=\""+url+"\" alt=\"\" /></td></tr></table>";
              html += "<div class=\"delete_image\">";
              html += "<a href=\"<?php echo self::get_link_edit($user->ID)?>&amp;remove_agent_image="+image_id+"\"><?php _e('Remove', 'wpp'); ?></a>";
              html += "</div></li>";
              jQuery('#wpp_agent_image_gallery').append(html);
            }
           }
        });
      });
    </script>
    <?php
  }

  /**
   * Removes the given image
   * and cleans up the user meta
   *
   */
  function remove_agent_image() {
    if(!isset($_REQUEST['remove_agent_image']) && !isset($_REQUEST['user_id'])) {
      return;
    }
    $attachment_id = $_REQUEST['remove_agent_image'];
    $user_id = $_REQUEST['user_id'];
    if ( !is_numeric($attachment_id) || !is_numeric($user_id) ) return;
    /** Remove attachment id from user meta */
    $agent_images = get_user_meta($user_id, 'agent_images', true);
    if(!in_array($attachment_id, (array)$agent_images)) return;
    foreach($agent_images as $i => $id) {
      if($id == $attachment_id || $id == 0) {
        unset($agent_images[$i]);
      }
    }
    /** Update user meta */
    update_user_meta($user_id, 'agent_images', $agent_images);
    /** Try to remove attachment */
    wp_delete_attachment($attachment_id);
  }

  /**
   * Returns a list of users with the real estate agent role.
   */
  function get_agents() {
    global $blog_id, $wp_properties;

    $agent_roles = $wp_properties['configuration']['feature_settings']['agents']['agent_roles'];

    if(empty($agent_roles)) {
      $agent_roles = array('agent');
    }

    $agent_users = array();

    foreach($agent_roles as $agent_role) {

      if($found_agents = get_users(array('blog_id' => $blog_id, 'role' => $agent_role))) {
        $agent_users = array_merge($agent_users, $found_agents);
      }

    }

    if(count($agent_users)) {
      return $agent_users;
    }

    return false;

  }


  /**
   * Create a user.
   */
  function create_agent($user_login, $display_name, $email) {
    global $wp_messages;

    if ($user_login == '' || $display_name == '' || $email == ''){
      $wp_messages['wp_agent_error'] = "Display name, User name and Email address is required field! Please reinput required field.";
      return;
    }

    $user_id = username_exists($user_login);
    if ( empty($user_id) == false ){
      $wp_messages['wp_agent_error'] = "User already exist!";
      return;
    }

    $user_id = email_exists($email);
    if ( empty($user_id) == false ){
      $wp_messages['wp_agent_error'] = 'Email already exist!';
      return;
    }

    # Create user
    $random_password = wp_generate_password(12, false);
    $user_id = wp_create_user($user_login, $random_password, $email);

    //NOTIFY NEW USER (AGENT) BY MAIL: wp_new_user_notification( $user_id, $random_password );
    //wp_new_user_notification( $user_id, $random_password );

    # Role
    global $wpdb;
    update_user_meta($user_id, $wpdb->prefix.'capabilities', array('agent' => 1));

    # Basics
    self::update_agent($user_id, $display_name, $email);

    # Other
    $GLOBALS['wpp_agent_user_id'] = $user_id;

    return $user_id;
  }


  /**
   * Update a user.
   */
  function update_agent($user_id, $display_name, $email) {
    if ( empty($user_id) ) return;

    global $wp_messages;

    $check_user_id = email_exists($email);
    if ( empty($check_user_id) == false && $check_user_id != $user_id){
      $wp_messages['wp_agent_error'] = 'Email already exist!';
      return;
    }

    global $wpdb;

    $wpdb->query( $wpdb->prepare('UPDATE '.$wpdb->users.' SET user_email = "%s" WHERE ID = %d', array($email, $user_id)) );
    $wpdb->query( $wpdb->prepare('UPDATE '.$wpdb->users.' SET user_nicename = "%s" WHERE ID = %d', array($display_name, $user_id)) );
    $wpdb->query( $wpdb->prepare('UPDATE '.$wpdb->users.' SET display_name = "%s" WHERE ID = %d', array($display_name, $user_id)) );
  }


  /**
   * Removes user with given ID.
   */
  function delete_agent($user_id) {
    if ( !is_numeric($user_id) ) return;
    global $wpdb;
    $wpdb->query( $wpdb->prepare('DELETE FROM '.$wpdb->users.' WHERE ID = %d', array($user_id) )  );
    $wpdb->query( $wpdb->prepare('DELETE FROM '.$wpdb->usermeta.' WHERE user_id = %d', array($user_id) ) );
  }


  /**
   * Returns agents associated with current post.
   */
  function get_agents_postmeta() {
    global $post;
    $agents = get_post_meta($post->ID, 'wpp_agents');
    if ( empty($agents) or !is_array($agents) ) return array();
    return $agents;
  }


  /**
   * Returns images linked to an agent.
   */
  function get_agent_images($user_id, $size = 'thumbnail', $args = array()) {
    if ( !is_numeric($user_id) and intval($user_id) > 0 ) return array();

    $image_ids = get_user_meta($user_id, 'agent_images', false);
    if ( empty($image_ids) ) return array();
    else $image_ids = $image_ids[0];

    //** Optional arguments */
    $defaults = array(
      'return' => 'array'
    );
    $args = wp_parse_args( $args, $defaults );

    $images = array();
    foreach ($image_ids as $image_id) {
      $image = wpp_get_image_link($image_id, $size, $args);
      /*
      if ($type == 'src') {
        $image = wp_get_attachment_image_src($image_id, $size);
        if ( !empty($image) )
          $image['id'] = $image_id;
      } else {
        $image = wp_get_attachment_image($image_id, $size);
      }
      //*/
      if ( empty($image) ) continue;
      $image['id'] = $image_id;
      $images[] = $image;
    }

    return $images;
  }


  /**
   * Returns true in case the given agent is in the given array, otherwise false.
   */
  function contains_agent(array $agents, $agent) {
    if ( empty($agents) or !is_array($agents))
      return false;
    foreach ($agents as $a)
      if ($a == $agent) return true;
    return false;
  }


  /**
   * Returns the default capabilites for agents.
   */
  function get_agent_capabilities() {
    global $wp_properties;
    $editor_role = get_role('author');
    $agent_capabilities = ( empty($wp_properties['agent_capabilities']) ) ? $editor_role->capabilities : $wp_properties['agent_capabilities'];
    #$agent_capabilities = $editor_role->capabilities; # TODO restore to default
    return $agent_capabilities;
  }


  /**
   * Returns an array with nice descriptions for capabilities.
   */
  function get_capabilities_pretty() {
    // Hint: Only a subset of all capabilities, mostly the ones for an editor
    return array('moderate_comments' => __('Moderate comments', 'wpp'),
     'manage_categories' => __('Manage categories', 'wpp'),
     'manage_links' => __('Manage links', 'wpp'),
     'upload_files' => __('Upload files', 'wpp'),
     'edit_posts' => __('Edit posts', 'wpp'),
     'edit_others_posts' => __("Edit other's posts", 'wpp'),
     'edit_published_posts' => __('Edit published posts', 'wpp'),
     'publish_posts' => __('Publish posts', 'wpp'),
     'edit_pages' => __('Edit pages', 'wpp'),
     'read' => __('Read', 'wpp'),
     'edit_others_pages' => __("Edit other's pages", 'wpp'),
     'edit_published_pages' => __('Edit published pages', 'wpp'),
     'publish_pages' => __('Publish pages', 'wpp'),
     'delete_pages' => __('Delete pages', 'wpp'),
     'delete_others_pages' => __("Delete other's pages", 'wpp'),
     'delete_published_pages' => __('Delete published pages', 'wpp'),
     'delete_posts' => __('Delete posts', 'wpp'),
     'delete_others_posts' => __("Delete other's posts", 'wpp'),
     'delete_published_posts' => __('Delete published posts', 'wpp'),
     'delete_private_posts' => __('Delete private posts', 'wpp'),
     'edit_private_posts' => __('Edit private posts', 'wpp'),
     'read_private_posts' => __('Read private posts', 'wpp'),
     'delete_private_pages' => __('Delete private pages', 'wpp'),
     'edit_private_pages' => __('Edit private pages', 'wpp'),
     'read_private_pages' => __('Read private pages', 'wpp'));
  }


  /**
   * Utility function that removes empty elements from the given array.
   */
  function clean_array($array) {
    if ( empty($array) or !is_array($array) )
      return array();

    $newArray = array();
    foreach ($array as $key => $value) {
      if ( empty($value) ) continue;

      # Special case for agent fields
      if ( empty($value['name']) ) continue;

      $newArray[$key] = $value;
    }
    return $newArray;
  }



  /**
  * Display agent card via shortcode
  *
  * @todo Complete sending agent specific notifications
  *
  * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
  */
  function shortcode_agent_card($atts) {
    global $property;

    $this_property = (object) $property;

    $user_id = $atts['user_id'];

    if(empty($user_id)) {
      $user_id = $atts['agent_id'];
    }

    if(empty($user_id)) {
      $user_id = $atts['agent'];
    }

    $fields = '';

    extract( shortcode_atts( array(
      'fields' => 'display_name,agent_image,full_bio'
    ), $atts ) );

    $class = $atts['class'];

    $class[] = 'wpp_agents_content_agent_card';

    if(empty($user_id)) {

      $wpp_agents = $this_property->wpp_agents;

      if(is_array($wpp_agents)) {
        foreach($wpp_agents as $agent_id) {
          $return[] = '<div class="'. implode('', $class) .'">' . class_agents::display_agent_card($agent_id, "fields=$fields") .'</div>';
        }
      }

    } else {
      $return[] = '<div class="'. implode('', $class) .'">' . class_agents::display_agent_card($user_id, "fields=$fields") .'</div>';
    }

    if(is_array($return)) {
      return implode("", $return);
    }


  }


  /**
  * Displays standardized agent information.
  *
  * @todo Complete sending agent specific notifications
  *
  * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
  */
  function display_agent_card($agent_id, $args = '') {
    global $wp_properties;

    $defaults = array(
      'fields' => 'display_name,agent_image,full_bio',
      'return' => 'true'
    );

    $args = wp_parse_args( $args, $defaults );

    $fields = explode(',',$args['fields']);

     // Load all titles
    if(!empty($wp_properties['configuration']['feature_settings']['agents']['agent_fields']))
      foreach($wp_properties['configuration']['feature_settings']['agents']['agent_fields'] as $slug => $attr_data)
        $display_fields[$slug] = $attr_data['name'];

    // Setup manual titles (not dynamic attributes)
    $display_fields['user_email'] = __('Email', 'wpp');
    $display_fields['display_name'] =  __('Name', 'wpp');
    $display_fields['widget_bio'] = "";
    $display_fields['full_bio'] = "";

    $no_label_items = apply_filters('wpp_agent_no_label_items', array('display_name', 'agent_image', 'widget_bio','full_bio','agent_bio','flyer_content'));

    $user_data = get_userdata($agent_id);
    $user_data = stripslashes_deep($user_data);

    if(!$user_data) {
      return false;
    }

    ob_start();
    ?>
    <div class='wpp_agent_info_single_wrapper'>

        <?php
          foreach($fields as $slug):

          if(empty($slug))
            continue;

            if(!in_array(trim($slug), $no_label_items))
              continue;

            $this_field = nl2br($user_data->$slug);

            $this_field = do_shortcode($this_field);

            $ul_print_rows[] = apply_filters('wpp_agent_widget_field_' . $slug, "<li class='wpp_agent_stats_{$slug}'><p>{$this_field}</p></li>", $display_fields, $slug, $user_data->ID);
          endforeach;
        ?>


      <?php if($ul_print_rows): ?>
      <ul class="wpp_agent_info_list"><?php echo implode('', $ul_print_rows); ?></ul>
      <?php endif; ?>


        <?php
        if($fields)
        foreach($fields as $slug):

           if(empty($slug))
            continue;

             // Skip if this attribute is a no label attribute
          if(in_array(trim($slug), $no_label_items))
            continue;

          if($slug)
            $this_field = nl2br($user_data->$slug);

          if(empty($this_field))
            continue;

          $dt_element = "<dt class='wpp_agent_stats_{$slug}'>{$display_fields[$slug]}:</dt>";

          // Make link
          if(WPP_F::isURL($this_field)) {
            $this_field = "<a class='wpp_agent_attribute_link' href='{$this_field}' title='{$this_field}'>{$display_fields[$slug]}</a>";
            $dt_element = "<dt class='wpp_agent_stats_{$slug}'></dt>";
          }

          $this_field = do_shortcode($this_field);

          $dl_print_fields[] = apply_filters('wpp_agent_widget_field_' . $slug, "{$dt_element}<dd class='wpp_agent_stats_{$slug}'>{$this_field}</dd>", $display_fields, $slug, $user_data->ID);

      endforeach;
        ?>

      <?php if($dl_print_fields): ?>
      <dl class="wpp_agent_info_list"><?php echo implode('', $dl_print_fields); ?></dl>
      <?php endif; ?>

    </div>

    <?php
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
  }

  /*
   * Applies custom 'wpp_agents' queryable key
   * Used by property_overview shortcode for wpp_query
   *
   * @param array $keys Queryable keys
   * @return array $keys
   * @author Maxim Peshkov
   */
  function get_queryable_keys($keys) {

    $keys[] = 'wpp_agents';

    return $keys;
  }

  /*
   * Get Role description
   * used by another premium feature 'WPP Capabilities'.
   *
   * @author Maxim Peshkov
   */
  function role_description($description) {

    $description = __('Agent can see only the properties which are assigned to him. Agent will be automaticly assigned to the property which he will create.', 'wpp');

    return $description;
  }

  /*
   * Modify wpp_seacrh for prepare_items() of WPP_List_Table class
   * Agent should can edit only the properties which are assigned to him
   *
   * @param array $wpp_search
   * @return array $wpp_search
   * @author Maxim Peshkov
   */
  function prepare_wpp_properties_search($wpp_search) {
    /*
     * Determine if current user is agent
     * if so, we modify $wpp_search
     */
    $current_user = wp_get_current_user();
    $roles = $current_user->roles;
    if(in_array('agent', $roles) && !in_array('administrator', $roles)) {
      if(!is_array($wpp_search)) {
        $wpp_search = array();
      }
      $wpp_search['wpp_agents'] = $current_user->ID;
    }

    return $wpp_search;
  }

  /**
   * Filters properties filter array
   * @global object $wpdb
   * @param array $prefill_meta
   * @param string $slug
   * @return array
   */
  function filter_property_filter( $prefill_meta, $slug ) {
    global $wpdb;

    // Non post_meta fields
    $non_post_meta = array(
      'post_title',
      'post_status',
      'post_author'
    );

    // Simple sanitizing
    $prefill_meta = array_unique( $prefill_meta );
    foreach( $prefill_meta as $key => $meta ) {
      if ( empty( $meta ) ) unset( $prefill_meta[ $key ] );
    }

    $current_user = wp_get_current_user();
    $roles = $current_user->roles;

    // Process meta fields
    if ( !in_array( $slug, $non_post_meta ) ) {

      if(in_array('agent', $roles) && !in_array('administrator', $roles)) {
        $meta_ids = $wpdb->get_col("
            SELECT post_id FROM {$wpdb->prefix}postmeta
            WHERE meta_key = 'wpp_agents'
              AND meta_value = '{$current_user->ID}';
        ");
        $prefill_meta = $wpdb->get_col("
            SELECT meta_value FROM {$wpdb->prefix}postmeta
            WHERE post_id IN (".implode(",", $meta_ids).")
            AND meta_value IN ('".implode("','", $prefill_meta)."')
        ");
      }

      $prefill_meta = array_unique( $prefill_meta );

    }
    // Process NON meta fields
    else {

      if(in_array('agent', $roles) && !in_array('administrator', $roles)) {
        $meta_ids = $wpdb->get_col("
            SELECT post_id FROM {$wpdb->prefix}postmeta
            WHERE meta_key = 'wpp_agents'
              AND meta_value = '{$current_user->ID}';
        ");
        $prefill_meta = $wpdb->get_col("
            SELECT $slug FROM {$wpdb->posts}
            WHERE ID IN (".implode(",", $meta_ids).")
              AND post_type = 'property'
        ");

      }

      $prefill_meta = array_unique( $prefill_meta );

    }

    return $prefill_meta;

  }

  /**
   * Filters quantity of properties from wpp
   * @global object $wpdb
   * @param array $results
   * @param array $post_status
   * @return array
   */
  function filter_properties_quantity( $results, $post_status ) {
    global $wpdb;

    $current_user = wp_get_current_user();
    $roles = $current_user->roles;

    if(in_array('agent', $roles) && !in_array('administrator', $roles)) {

      $results = $wpdb->get_col("
        SELECT p.ID FROM {$wpdb->prefix}posts AS p
          LEFT JOIN {$wpdb->prefix}postmeta AS pm
          ON p.ID = pm.post_id
          WHERE pm.meta_key = 'wpp_agents'
            AND pm.meta_value = '{$current_user->ID}'
            AND p.post_status IN ('". implode( "','", $post_status ) ."')
            AND p.post_type = 'property'
        ");

    }

    return $results;

  }

  /**
   * Filters available users from wpp
   * @param array $users
   * @return array
   */
  function filter_users_filter( $users ) {

    if ( !current_user_can( 'edit_others_wpp_properties' ) ) {
      $users = array();
    }

    return $users;

  }

  /**
   * Filters wpp months periods
   * @global object $wpdb
   * @param array $months
   * @return array
   */
  function filter_month_periods_filter( $months ) {
    global $wpdb;

    $current_user = wp_get_current_user();
    $roles = $current_user->roles;

    if(in_array('agent', $roles) && !in_array('administrator', $roles)) {

      $months = $wpdb->get_results("
        SELECT DISTINCT YEAR( p.post_date ) AS year, MONTH( p.post_date ) AS month
        FROM $wpdb->posts AS p
        JOIN {$wpdb->prefix}postmeta AS pm
          ON p.ID = pm.post_id
        WHERE p.post_type = 'property'
          AND p.post_status != 'auto-draft'
          AND pm.meta_key = 'wpp_agents'
          AND pm.meta_value = '{$current_user->ID}'
        ORDER BY post_date DESC
      ");

    }

    return $months;

  }

  /**
   * Filters wpp search options
   * @param array $filters
   */
  function filter_get_search_filters( $filters ) {

    $filter_fields = array(
      'default' => '0',
      'type'    => 'dropdown',
      'label'   => __('Agent', 'wpp')
    );

    $agents = self::get_agents();

    if(is_array($agents)) {
      $filter_fields['values'][0] = __('Any', 'wpp');
      foreach( $agents as $agent ) {
        $filter_fields['values'][$agent->ID] = $agent->display_name;
      }
    }

    //* Determine if agents are added (exist) to filter */
    if(count($filter_fields['values']) <= 1) {
      return $filters;
    }

    $current_user = wp_get_current_user();
    $roles = $current_user->roles;

    if(in_array('administrator', $roles) && !in_array('agent', $roles)) {
      $filters['wpp_agents'] = $filter_fields;
    }

    return $filters;


  }

  /**
   * Assigns the submittor as an agent, if their role permits
   *
   *
   *
   * @param array $return
   * @param array $data - array of user_id, return, property_id and form_id
   *
   */
  function assign_agent_to_new_feps_listing( $property_id ) {
    global $wpdb, $wp_properties, $wp_roles;

    $_property = WPP_F::get_property( $property_id, array( 'get_children' => 'false' ) );

    $user_id = $_property[ 'post_author' ];
    $user_data = get_userdata($user_id);

    $user_roles = $user_data->roles;

    $allowed_roles = $wp_properties['configuration']['feature_settings']['agents']['agent_roles'];

    if( $property_id && is_array($user_roles) && is_array($allowed_roles) && array_intersect($user_roles, $allowed_roles)) {

      //** Just in case */
      delete_post_meta( $property_id, 'wpp_agents' );

      //** Add the new user as an agent */
      add_post_meta( $property_id, 'wpp_agents', $user_id );

    }

    return true;

  }

  /**
   * Determine if the current user can edit the current property
   *
   * @global type $post
   * @param type $value
   * @return type $value
   * @author peshkov@UD
   */
  function can_edit_post ($value) {
    global $post, $current_user;

    if( $post->post_type ) {
      $roles = (array)$current_user->roles;
      if(in_array('agent', $roles) && !in_array('administrator', $roles)) {
        $agents = (array)get_post_meta($post->ID, 'wpp_agents');
        $post_type_object = get_post_type_object( $post->post_type );
        if(!in_array($current_user->ID , $agents) ||
          $post->post_author != $current_user->ID &&
          !current_user_can($post_type_object->cap->edit_others_posts)) {
          return false;
        }
      }
    }

    return $value;
  }

} // end class_agents


/**
 * Compatibility pre WP 3.1
 */
if (!function_exists('get_users')) {
  function get_users() {
    return array();
  }
}


/**
 * AgentWidget Class
 */
class AgentWidget extends WP_Widget {

  function AgentWidget() {
    parent::WP_Widget(false, $name = 'Property Agents');
  }

  function widget($args, $instance) {
    global $post, $property, $wp_properties;
    $before_widget = $after_widget = $after_title = $before_title = '';

    extract( $args );

    $saved_fields = $instance['saved_fields'];
    $widget_title = apply_filters('widget_title', $instance['title']);

    $agents = ($post->wpp_agents)?$post->wpp_agents:$property['wpp_agents'];

    if(!is_array($agents)) {
      return;
    }

    if(empty($saved_fields)) {
      return;
    }


    foreach($agents as $agent_id) {
      $this_agent = class_agents::display_agent_card($agent_id,"fields=" . implode(',',$saved_fields));

      if(!empty($this_agent)) {
        $agent_data[] = $this_agent;
      }
    }

    if(empty($agent_data)) {
      return;
    }

    echo $before_widget;

    if ( $widget_title ) {
      echo $before_title . $widget_title . $after_title;
    }

    echo "<div class='wpp_agent_widget_wrapper " . (empty($widget_title)  ? 'wpp_no_widget_title' : ' wpp_has_widget_title ') . "'>";
    echo implode($agent_data);
    echo "</div>";

    echo $after_widget;

  }

  function update($new_instance, $old_instance) {
    return $new_instance;
  }

  function form($instance) {
    global $wp_properties;
    $title = esc_attr($instance['title']);
    $saved_fields = $instance['saved_fields'];

    $display_fields['display_name'] = __('Display Name', 'wpp');
    $display_fields['agent_image'] = __('Image', 'wpp');
    $display_fields['widget_bio'] = __('Widget Text', 'wpp');
    $display_fields['full_bio'] = __('Full Bio', 'wpp');

    if(!empty($wp_properties['configuration']['feature_settings']['agents']['agent_fields']))
      foreach($wp_properties['configuration']['feature_settings']['agents']['agent_fields'] as $slug => $attr_data)
        $display_fields[$slug] = $attr_data['name'];

    $display_fields['user_email'] = __('Email Address', 'wpp');
  ?>
  <p>
    <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
  </p>

  <div class="wp-tab-panel">
    <ul>
    <?php foreach($display_fields as $stat => $label): if(empty($label)) continue; ?>
      <li>
        <input id="<?php echo $this->get_field_id('saved_fields'); ?>_<?php echo $stat; ?>" name="<?php echo $this->get_field_name('saved_fields'); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
        <?php if(is_array($saved_fields) && in_array($stat, $saved_fields)) echo " checked "; ?>    />
        <label for="<?php echo $this->get_field_id('saved_fields'); ?>_<?php echo $stat; ?>"><?php echo $label;?></label>
      </li>
      <?php  ?>

    <?php endforeach; ?>
    </ul>

  </div>
  <?php
  }
} // end class AgentWidget
