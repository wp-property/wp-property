<?php

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
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @subpackage Agents
 */
class class_agents
{

  /*
   * (custom) Capability to manage the current feature
   */
  static protected $capability = "manage_wpp_agents";

  /**
   *
   */
  static public function init()
  {
    global $wp_roles;

    /* Setup White Labels */
    $single_label = ud_get_wp_property('configuration.feature_settings.agents.label.single', false);
    if (empty($single_label)) {
      ud_get_wp_property()->set('configuration.feature_settings.agents.label.single', __('Agents', ud_get_wpp_agents()->domain));
    }

    $plural_label = ud_get_wp_property('configuration.feature_settings.agents.label.plural', false);
    if (empty($plural_label)) {
      ud_get_wp_property()->set('configuration.feature_settings.agents.label.plural', __('Agents', ud_get_wpp_agents()->domain));
    }

    // New standard taxonomies. Ran late, to force after Terms_Bootstrap::define_taxonomies
    add_filter('wpp_taxonomies', array('class_agents', 'wpp_taxonomies'), 15 );

    /* Add capability */
    add_filter('wpp_capabilities', array('class_agents', "add_capability"));

    //* Add (remove) role Agent to Administrator */
    add_filter('wpp_settings_save', array('class_agents', 'add_role_agent_to_admin'));

    add_filter('wpp_feps_submitted', array('class_agents', 'assign_agent_to_new_feps_listing'));

    //* Add role */
    add_role('agent', ud_get_wp_property('configuration.feature_settings.agents.label.single'), self::get_agent_capabilities());
    add_filter('wpp_role_description_agent', array('class_agents', "role_description"));

    //* Use Custom Agent's Role Name ( White Label ) */
    if (!isset($wp_roles)) {
      $wp_roles = new WP_Roles();
    }


    if (current_user_can(self::$capability)) {
      // Add settings page
      add_filter('wpp_settings_nav', array('class_agents', 'settings_nav'));
      add_action('wpp_settings_content_agents', array('class_agents', 'settings_page'));

      // Posts
      add_action('wpp_publish_box_options', array('class_agents', 'publish_box_options'));
    }

    add_action('save_property', array('class_agents', 'save_property'));

    add_action('admin_init', array('class_agents', 'admin_init'));
    add_action('admin_enqueue_scripts', array('class_agents', 'admin_enqueue_scripts'));

    wp_enqueue_style('wp-property-agents', ud_get_wpp_agents()->path('static/styles/wp-property-agents.css'));

    add_filter('wpp_agent_widget_field_agent_image', array('class_agents', 'wpp_agent_widget_field_agent_image'), 0, 4);

    // User
    add_action('edit_agent_profile', array('class_agents', 'show_profile_fields'), 10);
    add_action('edit_agent_profile_update', array('class_agents', 'save_profile_basic'), 10);
    add_action('edit_agent_profile_update', array('class_agents', 'save_profile_fields'), 11);
    add_action('edit_agent_profile_update', array('class_agents', 'redirect_agent_page'), 12);

    //* Upload Image */
    add_action('wp_ajax_wpp_save_agent_image', array('class_agents', 'save_agent_image'));

    add_action('init', array('class_agents', 'remove_agent_image'), 4);

    //** Add correct views to agent overview page */
    add_filter('views_property_page_show_agents', array('class_agents', 'overview_view'));

    add_filter('wpp_get_property', array('class_agents', 'wpp_get_property'));
    add_filter('wpp_list_table_can_edit_post', array('class_agents', 'can_edit_post'));
    add_filter('wpp_list_table_can_delete_post', array('class_agents', 'can_edit_post'));

    /* 'All Properties' page. Ignore non-Agents properties ( if agent has no administrator permissions ) */
    add_filter('wpp::all_properties::wp_query::args', array(__CLASS__, 'filter_wp_query'));
    add_filter("wpp_get_properties_quantity", array('class_agents', 'filter_properties_quantity'), 10, 2);

    add_filter("manage_users_custom_column", array('class_agents', 'manage_users_custom_column'), 0, 3);
    add_filter("wpp::overview::filter::fields", array(__CLASS__, 'add_filter_field'));
    add_filter("wpp_get_property_month_periods", array('class_agents', 'filter_month_periods_filter'));
    add_filter("wpp_get_users_of_post_type", array('class_agents', 'filter_users_filter'));

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

    // Checking if current user should have ability.

    add_filter('user_has_cap', array('class_agents', 'agent_has_cap'), 10, 4);

    // Checking if current user should have ability.
    add_filter('wp_crm_user_level_roles', array('class_agents', 'wp_crm_user_level_roles'), 10);
  }

  /**
   * Add Agent/Office taxonomies.
   *
   * Since version 2.2.1 of WP-Property agents and offices will be stored as terms.
   * If an agent requires login capability, a user will be created and then associated with the term.
   * The agent/office terms container profile information while users account will contain authentication and permission information.
   *
   * @author potanin@UD
   * @param array $taxonomies
   * @return array
   */
  static public function wpp_taxonomies( $taxonomies = array() ) {

    // Add [wpp_agent] taxonomy.
    $taxonomies['wpp_agent'] = array(
      'default' => true,
      'system' => true,
      'meta' => true,
      'readonly' => true,
      'hidden' => true,
      'hierarchical' => false,
      'public' => true,
      'show_in_nav_menus' => false,
      'show_ui' => false,
      'show_tagcloud' => false,
      'add_native_mtbox' => false,
      'label' => sprintf(_x('%s Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain), WPP_F::property_label()),
      'query_var' => 'agents',
      'labels' => array(
        'name' => sprintf(_x('%s Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain), WPP_F::property_label()),
        'singular_name' => sprintf(_x('%s Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain), WPP_F::property_label()),
        'search_items' => _x('Search Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'all_items' => _x('All Agents', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'parent_item' => _x('Parent Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'parent_item_colon' => _x('Parent Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'edit_item' => _x('Edit Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'update_item' => _x('Update Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'add_new_item' => _x('Add New Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'new_item_name' => _x('New Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'not_found' => _x('No location found', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'menu_name' => sprintf(_x('%s Agent', 'property agent taxonomy', ud_get_wpp_agents()->domain), WPP_F::property_label()),
      ),
      'rewrite' => array('slug' => 'agents')
    );

    // Add [wpp_office] taxonomy.
    $taxonomies['wpp_office'] = array(
      'default' => true,
      'readonly' => true,
      'system' => true,
      'meta' => true,
      'hidden' => true,
      'hierarchical' => false,
      'public' => true,
      'show_in_nav_menus' => false,
      'show_ui' => false,
      'show_tagcloud' => false,
      'add_native_mtbox' => false,
      'label' => sprintf(_x('%s Office', 'property office taxonomy', ud_get_wpp_agents()->domain), WPP_F::property_label()),
      'query_var' => 'office',
      'labels' => array(
        'name' => __('Offices',  ud_get_wpp_agents()->domain ),
        'singular_name' => __('Office',  ud_get_wpp_agents()->domain ),
        'search_items' => _x('Search Office', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'all_items' => _x('All Offices', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'parent_item' => _x('Parent Office', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'parent_item_colon' => _x('Parent Office', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'edit_item' => _x('Edit Office', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'update_item' => _x('Update Office', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'add_new_item' => _x('Add New Office', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'new_item_name' => _x('New Office', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'not_found' => _x('No location found', 'property agent taxonomy', ud_get_wpp_agents()->domain),
        'menu_name' => __('Office',  ud_get_wpp_agents()->domain)
      ),
      'rewrite' => array('slug' => 'offices')
    );

    return $taxonomies;

  }

  /**
   * Add agent role to the wp-crm user level dropdown.
   *
   * @param $roles : Roles array.
   *
   * @return Array of roles.
   *
   */
  public static function wp_crm_user_level_roles($roles)
  {
    $roles['agent'] = (array)get_role('agent');
    return $roles;
  }

  public static function save_agent_image()
  {
    if (!empty($_POST['agent_id']) && !empty($_POST['attachment_id'])) {
      if (update_user_meta($_POST['agent_id'], 'agent_images', array($_POST['attachment_id']))) {
        wp_send_json_success(array(
          'message' => __('Saved', ud_get_wpp_agents()->domain)
        ));
      } else {
        wp_send_json_error(array(
          'message' => __('Same image', ud_get_wpp_agents()->domain)
        ));
      }
    }
  }

  /**
   * Updates Role Label
   *
   * @param $name
   * @param $display_name
   * @param $capabilities
   */
  public static function update_role()
  {
    global $wp_roles;
    if (isset($wp_roles->roles['agent'])) {
      $wp_roles->roles['agent']['name'] = ud_get_wp_property('configuration.feature_settings.agents.label.single');
      update_option($wp_roles->role_key, $wp_roles->roles);
    }
  }

  /**
   * @param $fields
   * @return array
   * Make filter work with new WPTL
   */
  static public function add_filter_field($fields)
  {

    /**
     * Ignore adding Agent field for Agents.
     * @since 2.0.1
     */
    if (!current_user_can('manage_options')) {
      global $current_user;
      $user_roles = $current_user->roles;
      if (in_array('agent', $user_roles)) {
        return $fields;
      }
    }

    $options = array();

    $agents = self::get_agents();

    if (!empty($agents) && is_array($agents)) {
      foreach ($agents as $agent) {
        $options[$agent->ID] = $agent->display_name;
      }
    }

    $fields[] = array(
      'id' => 'wpp_agents',
      'name' => ud_get_wp_property('configuration.feature_settings.agents.label.single'),
      'type' => 'select_advanced',
      'options' => array('' => '') + $options
    );

    return $fields;
  }

  /**
   * Agent Settings page load handler
   * @author korotkov@ud
   */
  static public function property_page_show_agents_load()
  {
    global $wp_properties;

    $label = sprintf(__('%s Shortcodes', ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.single'));

    /** Screen Options */
    add_screen_option('layout_columns', array('max' => 2, 'default' => 2));

    $contextual_help[$label][] = '<h3>' . $label . '</h3>';
    $contextual_help[$label][] = '<p>' . sprintf(__("Show %s information:", ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.single')) . ' [agent_card agent_id=1]' . __(", or you can specify which fields to show:", ud_get_wpp_agents()->domain) . ' [agent_card agent_id=1 fields=display_name,email]</p>';
    $contextual_help[$label][] = '<p>' . __("To query agent properties:", "wpp") . '  [property_overview wpp_agents=1]</p>';

    $contextual_help['Shortcode Attributes'][] = '<h3>' . __("Shortcode Attribute Fields", "wpp") . '</h3>';
    $contextual_help['Shortcode Attributes'][] = '<p>' . __('The following attributes can be used in the [agent_card] shortcode.', ud_get_wpp_agents()->domain) . '</p>';
    $fields = array();
    $fields['agent_image'] = array('name' => 'Agent Image');
    $fields['display_name'] = array('name' => 'Display Name');
    $fields['user_email'] = array('name' => 'Email Address');
    $fields['widget_bio'] = array('name' => 'Widget Bio');
    $fields['full_bio'] = array('name' => 'Full Bio');
    $fields['flyer_content'] = array('name' => 'Flyer Writeup');

    $fields = array_merge($fields, self::clean_array(!empty($wp_properties['configuration']['feature_settings']['agents']['agent_fields']) ? $wp_properties['configuration']['feature_settings']['agents']['agent_fields'] : array()));
    $agent_social_fields = array_merge($fields, self::clean_array(!empty($wp_properties['configuration']['feature_settings']['agents']['agent_social_fields']) ? $wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'] : array()));

    $contextual_help['Shortcode Attributes'][] = '<ul>';
    foreach ($fields as $key => $label) {
      $all_agent_fields[] = $key;
      $contextual_help['Shortcode Attributes'][] = "<li>{$label['name']} - $key </li>";
    }
    foreach ($agent_social_fields as $key => $label) {
      $all_agent_fields[] = $key;
      $contextual_help['Shortcode Attributes'][] = "<li>{$label['name']} - $key </li>";
    }
    $contextual_help['Shortcode Attributes'][] = '</ul>';

    $contextual_help['Examples'][] = '<h3>' . __("Example Using All Available Fields", "wpp") . '</h3>';

    $contextual_help['Examples'][] = '[agent_card agent_id=1 fields=' . implode(',', $all_agent_fields) . ']';

    //** Hook this is you need to add some helps to Agents Settings page */
    $contextual_help = apply_filters('property_page_show_agents_help', $contextual_help);

    do_action('wpp_contextual_help', array('contextual_help' => $contextual_help));

  }

  /**
   *
   *
   */
  static public function admin_init()
  {
    global $current_screen, $wp_properties, $plugin_page;

    if (isset($_GET['page']) && $_GET['page'] == 'show_agents' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'update') {
      do_action('edit_agent_profile_update', $_POST['user_id']);
    }
  }

  /*
   * Adds Custom capability to the current premium feature
   */
  static public function add_capability($capabilities)
  {

    $capabilities[self::$capability] = sprintf(__('Manage %s', ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.plural'));

    return $capabilities;
  }

  /**
   * Display user role selection on agent page if more than one role are allowed to be agents.
   *
   * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function overview_view($views)
  {
    global $wp_properties, $wp_roles;

    if (empty($wp_properties['configuration']['feature_settings']['agents']['agent_roles'])) {
      return false;
    }

    //* Making sure that old data gets removed and that there's at least one row */
    $agent_roles = $wp_properties['configuration']['feature_settings']['agents']['agent_roles'];
    //** Do nothing if less than 2 rules */
    if (!is_array($agent_roles) || count($agent_roles) < 2) {
      return false;
    }

    $url = 'edit.php?post_type=property&page=show_agents';
    $users_of_blog = count_users();
    $total_users = $users_of_blog['total_users'];
    $avail_roles =& $users_of_blog['avail_roles'];
    $role_names = $wp_roles->role_names;

    foreach ($agent_roles as $role) {

      if (!$avail_roles[$role]) {
        continue;
      }

      $name = $role_names[$role];

      if ($role == $_REQUEST['role']) {
        $current_role = $role;
        $class = ' class="current"';
      } else {
        $class = '';
      }

      $name = translate_user_role($name);

      $name = sprintf(__('%1$s <span class="count">(%2$s)</span>'), $name, $avail_roles[$role]);
      $role_links[$role] = "<a href='" . esc_url(add_query_arg('role', $role, $url)) . "'$class>$name</a>";

    }

    return $role_links;
  }


  /**
   * Add extra options to XML Importer
   *
   * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function wpp_import_advanced_options($current_settings = false)
  {
    global $wp_properties;

    $agent_fields['display_name'] = 'Display Name';
    $agent_fields['user_email'] = 'Email Address';

    if (is_array($wp_properties['configuration']['feature_settings']['agents']['agent_fields'])) {
      foreach ($wp_properties['configuration']['feature_settings']['agents']['agent_fields'] as $meta_key => $meta_data) {
        $agent_fields[$meta_key] = $meta_data['name'];
      }
    }
    if (is_array($wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'])) {
      foreach ($wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'] as $meta_key => $meta_data) {
        $agent_fields[$meta_key] = $meta_data['name'];
      }
    }
    ?>
    <li>
      <label
        class="description"><?php printf(__('Match the "%s %s" attribute to ', ud_get_wpp_agents()->domain), WPP_F::property_label(), ud_get_wp_property('configuration.feature_settings.agents.label.single')); ?></label>
      <select name="wpp_property_import[wpp_agent_attribute_match]">
        <?php foreach ($agent_fields as $field_slug => $field_title) { ?>
          <option
            value="<?php echo esc_attr($field_slug); ?>" <?php if (isset($current_settings['wpp_agent_attribute_match'])) selected($field_slug, $current_settings['wpp_agent_attribute_match']); ?>><?php echo $field_title; ?></option>
        <?php } ?>
      </select>
      <label
        class="description"><?php echo __(' field, when it is found, and associate with agent.', ud_get_wpp_agents()->domain); ?></label>
    </li>
    <?php

  }

  /**
   * Sends notification to an agent.
   *
   * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function send_agent_notification($agent_id, $subject = 'Notification', $message = '', $headers = '')
  {

    if (!is_numeric($agent_id))
      return;

    $agent_data = get_userdata($agent_id);

    if (wp_mail($agent_data->user_email, $subject, $message, $headers))
      return true;

    return false;

  }

  /**
   * Adds agent IDs in array format to the property object
   *
   * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function wpp_get_property($property)
  {
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
  static public function handle_comments($comment)
  {

    $property_id = $comment->comment_post_ID;

    //Does the property have agents
    $wpp_agents = get_post_meta($property_id, 'wpp_agents');

    if (empty($wpp_agents))
      return;
    // We have agents
  }

  /**
   * Adds agent-specific settings to flyer page
   *
   *
   * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function flyer_settings($wpp_pdf_flyer)
  {
    $wpp_pdf_flyer['agent_photo_size'] = isset($wpp_pdf_flyer['agent_photo_size']) ? $wpp_pdf_flyer['agent_photo_size'] : '';
    ?>
    <tr>
      <th><?php sprintf(__("%s Image Size", ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.single')); ?></th>
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
  static public function pdf_flyer_insert($property, $wpp_pdf_flyer)
  {
    global $wp_properties;

    if (!isset($property['wpp_agents']) || !is_array($property['wpp_agents'])) {
      $property['wpp_agents'] = get_post_meta($property['ID'], 'wpp_agents');
    }

    if (!empty($wpp_pdf_flyer['pr_agent_info']) && !empty($property['wpp_agents'])) {
      $agent_photo_size = $wpp_pdf_flyer['agent_photo_size'];
      $agent_photo_width = $wpp_pdf_flyer['agent_photo_width'];
      ?>
      <tr>
        <td>
          <div
            class="heading_text"><?php printf(__('%s Information', ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.single')); ?></div>
        </td>
      </tr>
      <tr>
        <td><br/>
          <?php
          foreach ($property['wpp_agents'] as $agent_id) {
            if ($agent_photo_size) {
              $agent_images = class_agents::get_agent_images($agent_id, $agent_photo_size);
            }
            ?>
            <table cellspacing="0" cellpadding="0" border="0">
              <tr>
                <?php if (!empty($agent_images[0]['src'])) : ?>
                  <td width="<?php echo $agent_photo_width; ?>"><?php
                    /** Make sure image exists */
                    if (preg_match('@^(https?|ftp)://[^\s/$.?#].[^\s]*$@iS', $agent_images[0]['src'])) { ?>
                      <table cellspacing="0" cellpadding="10" border="0" class="bg-section">
                        <tr>
                          <td><img width="<?php echo($agent_photo_width - 20); ?>"
                                   src="<?php echo $agent_images[0]['src']; ?>" alt=""/>
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
   * @param $nothing
   * @param $column_name
   * @param $user_id
   * @return mixed|string
   */
  static public function manage_users_custom_column($nothing, $column_name, $user_id)
  {
    global $wpdb;

    $user_data = get_userdata($user_id);

    switch ($column_name) {

      case 'id':
        return $user_id;
        break;

      case 'display_name':
        $display_name = stripslashes($user_data->display_name);
        ob_start();
        ?>
        <strong><a href="<?php echo self::get_link_edit($user_id); ?>"><?php echo $display_name; ?></a></strong><br>
        <div class="row-actions">
          <span class="edit"><a
              href="<?php echo self::get_link_edit($user_id); ?>"><?php _e('Edit', ud_get_wpp_agents()->domain); ?></a> | </span>
          <span class="delete"><a href="<?php echo self::get_link_delete($user_id); ?>"
                                  class="submitdelete"><?php _e('Delete', ud_get_wpp_agents()->domain); ?></a></span>
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

  /**
   * @param $columns
   * @return mixed
   */
  static public function manage_property_page_show_agents_columns($columns)
  {
    global $wp_properties;

    if( isset( $wp_properties['configuration']['feature_settings'] ) && isset( $wp_properties['configuration']['feature_settings']['agents'] ) ) {
      $agent_fields = $wp_properties['configuration']['feature_settings']['agents']['agent_fields'];
      $agent_social_fields = $wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'];
    }

    $columns['id'] = "ID";
    $columns['display_name'] = "Display Name";
    $columns['user_email'] = "Email";

    if ( isset( $agent_fields ) && !empty($agent_fields)) {
      foreach ($agent_fields as $slug => $data) {
        $columns[$slug] = $data['name'];
      }
    }
    if ( isset( $agent_social_fields ) && !empty($agent_social_fields)) {
      foreach ($agent_social_fields as $slug => $data) {
        $columns[$slug] = $data['name'];
      }
    }

    $columns['related_properties'] = "Properties";

    return $columns;
  }

  /**
   * @param $text
   * @param $display_fields
   * @param $slug
   * @param $this_field
   * @return string
   */
  static public function wpp_agent_widget_field_agent_image($text, $display_fields, $slug, $this_field)
  {
    global $wp_properties;
    $image_ids = get_user_meta($this_field, 'agent_images', false);
    if (empty($image_ids)) return array();
    else $image_ids = $image_ids[0];

    if ($image_ids) {
      $return = array();

      $return[] = '<div class="wpp_agent_images">';
      foreach ($image_ids as $image) {
        $title = trim(strip_tags(get_the_title($image)));
        $alt = trim(strip_tags(get_post_meta($image, '_wp_attachment_image_alt', true)));
        if (empty($alt))
          $alt = $title;
        $return[] = wp_get_attachment_image($image, apply_filters('wpp_agent_widget_image_size', 'thumbnail'), false, array('alt' => $alt, 'title' => $title));
      }
      $return[] = '</div>';

      return implode('', $return);
    } else {
      return '';
    }

  }

  /**
   *
   * @param $user
   *
   */
  static public function metabox_related_properties($user)
  {
    global $wpdb;

    /* get properties */
    $related = $wpdb->get_results("SELECT DISTINCT(post_title), post_id, post_status FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p on pm.post_id = p.ID WHERE meta_key = 'wpp_agents' AND meta_value='{$user->ID}' ");

    if (empty($related)) {
      _e('No Properties.', ud_get_wpp_agents()->domain);
      return;
    }

    echo "<ul class='wpp_agents_related_properties wp-tab-panel'>";
    foreach ($related as $row) {
      echo "<li class='wpp_post_status_{$row->post_status}'><a href='" . get_edit_post_link($row->post_id) . "'>{$row->post_title}</a></li>";
    }
    echo "</ul>";
  }

  /**
   *
   * @param $user
   */
  static public function metabox_save($user)
  {
    ?>
    <input type="hidden" value="update" name="action">
    <input type="hidden" value="<?php echo $user->ID; ?>" id="user_id" name="user_id">

    <div id="major-publishing-actions">
      <div id="publishing-action">
        <input type="submit" id="publish"
               value="<?php if (isset($user->new_user) && $user->new_user) printf(__('Create %s', ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.single')); else printf(__('Update %s', ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.single')); ?>"
               class="button-primary btn" id="submit" name="submit"/>
      </div>
    </div>
    <?php
  }


  /**
   * Renders Agents options ( fields )
   *
   * @param $user
   */
  static public function metabox_primary_info($user)
  {
    ?>
    <table class="form-table">
      <tbody>

      <tr class='column-display_name'>
        <th><label for="display_name"><?php _e('Display Name', ud_get_wpp_agents()->domain); ?></label></th>
        <td><input type="text" style="width: 300px; font-size: 1.5em;" class="regular-text"
                   value="<?php print esc_attr($user->display_name); ?>" id="display_name" name="display_name"></td>
      </tr>

      <tr>
        <th><label for="user_login"><?php _e('Username', ud_get_wpp_agents()->domain); ?></label></th>
        <td>
          <input type="text" class="regular-text"
                 <?php if (!isset($user->new_user)) echo ' disabled="disabled"'; ?>value="<?php echo esc_attr($user->user_login); ?>"
                 id="user_login" name="user_login">
          <div class="description"><?php _e('Usernames cannot be changed.', ud_get_wpp_agents()->domain); ?></div>
        </td>
      </tr>

      <tr class='column-user_email'>
        <th><label for="email"><?php _e('E-Mail', ud_get_wpp_agents()->domain); ?></label></th>
        <td><input type="text" class="regular-text" value="<?php echo $user->user_email; ?>" id="email" name="email">
        </td>
      </tr>

      <tr>
        <th><label for="widget_bio"><?php _e('Widget Bio', ud_get_wpp_agents()->domain); ?></label></th>
        <td>
          <ul>
            <li><textarea class="code" id="widget_bio" name="agent_fields[widget_bio]"
                          style="width:300px;"><?php echo $user->widget_bio; ?></textarea></li>
            <li><span
                class="description"><?php printf(__('Content that is displayed in the %s widget.', ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.plural')); ?></span>
            </li>
          </ul>
        </td>
      </tr>

      <tr class="wpp_agent_options hidden">
        <th><label><?php _e('Options', ud_get_wpp_agents()->domain); ?></label></th>
        <td>
          <?php $options = apply_filters('wpp::agents::agent::options', array(), $user); ?>
          <?php if (!empty($options)) : ?>
            <ul class="wpp_agents_agent_options">
              <?php foreach ($options as $option) : ?>
                <li><?php echo $option; ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div class="wpp_agents_agent_options"><?php do_action('wpp_agents_agent_options', $user); ?></div>
        </td>
      </tr>

      <tr class="wpp_agent_options hidden">
        <th><label><?php _e('Options', ud_get_wpp_agents()->domain); ?></label></th>
        <td>
          <?php $options = apply_filters('wpp::agents::agent::options', array(), $user); ?>
          <?php if (!empty($options)) : ?>
            <ul class="wpp_agents_agent_options">
              <?php foreach ($options as $option) : ?>
                <li><?php echo $option; ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div class="wpp_agents_agent_options"><?php do_action('wpp_agents_agent_options', $user); ?></div>
        </td>
      </tr>

      <tr>
        <th><label for="full_bio"><?php _e('Full Bio', ud_get_wpp_agents()->domain); ?></label></th>
        <td>
          <ul>
            <li><textarea class="code" id="full_bio" name="agent_fields[full_bio]"
                          style="width:300px;"><?php echo $user->full_bio; ?></textarea></li>
            <li><span
                class="description"><?php _e('Content that can be displayed in the shortcode.', ud_get_wpp_agents()->domain); ?></span>
            </li>
          </ul>
        </td>
      </tr>

      </tbody>
    </table>
    <?php do_action('edit_agent_profile', $user); ?>
    <?php do_action('edit_agent_profile_extra', $user); ?>
    <?php
  }

  /**
   *
   */
  static public function admin_enqueue_scripts()
  {
    global $current_screen, $wp_properties;

    if (!isset($current_screen->id)) {
      return;
    }

    if ($current_screen->id == 'property_page_show_agents' && (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit')) {
      wp_enqueue_script('post');
      wp_enqueue_script('postbox');
      wp_enqueue_script('wpp-edit-agent', ud_get_wpp_agents()->path('static/scripts/wpp.admin.agent.js'), array('jquery', 'wpp-localization', 'wp-property-global'), WPP_Version);

      add_meta_box('metabox_primary_info', __('Primary'), array('class_agents', 'metabox_primary_info'), 'property_page_show_agents', 'normal', 'core');
      add_meta_box('metabox_save', __('Save'), array('class_agents', 'metabox_save'), 'property_page_show_agents', 'side', 'core');
      add_meta_box('metabox_related_properties', __('Associated Properties'), array('class_agents', 'metabox_related_properties'), 'property_page_show_agents', 'side', 'core');
      add_meta_box('metabox_profile_images', __('Images'), array('class_agents', 'metabox_profile_images'), 'property_page_show_agents', 'side', 'core');
    }

    if ($current_screen->id == 'property_page_show_agents') {
      wp_enqueue_script('wp-property-backend-global');
      wp_enqueue_script('wp-property-global');

      //** Only do on agent overview page */
      if (!isset($_REQUEST['action'])) {
        /* add_screen_option( 'per_page', array('label' => _x( 'Users', 'users per page (screen options)' )) ); */
        add_filter("manage_property_page_show_agents_columns", array('class_agents', 'manage_property_page_show_agents_columns'));
      }
    }
  }

  /**
   * Adds menu to settings page navigation
   */
  static public function settings_nav($tabs)
  {
    $tabs['agents'] = array(
      'slug' => 'agents',
      'title' => ud_get_wp_property('configuration.feature_settings.agents.label.plural'),
    );
    return $tabs;
  }

  /**
   * Displays advanced management page
   */
  static public function settings_page()
  {
    /* May be update Role label */
    self::update_role();
    include ud_get_wpp_agents()->path('static/views/admin/settings.php', 'dir');
  }

  /**
   * Hack.
   * Determine if the current page is post editor and current user is Agent and the current post
   * is not assigned to him or user is not author of post and has no capabilities for edit it
   * we redirect user to general 'All Properties' (overview) page
   *
   * @param object $screen
   * @return object $screen
   * @author peshkov@UD
   */
  static public function current_screen($screen)
  {
    if ($screen->base == "post" && $screen->post_type == "property") {
      if (!empty($_REQUEST['post'])) {
        $id = (int)$_REQUEST['post'];
        $post = get_post($id);
        if (empty($post)) {
          return $screen;
        }
        $current_user = wp_get_current_user();
        $roles = (array)$current_user->roles;

        if (in_array('agent', $roles) && !in_array('administrator', $roles)) {
          $agents = (array)get_post_meta($id, 'wpp_agents');
          $post_type_object = get_post_type_object($post->post_type);
          if (current_user_can("edit_others_wpp_properties") || in_array($current_user->ID, $agents)) {
            return $screen;
          }
          if ($post->post_author != $current_user->ID &&
            !current_user_can($post_type_object->cap->edit_others_posts)
          ) {
            wp_redirect('edit.php?post_type=property&page=all_properties');
            exit();
          }
        }
      }
    }

    return $screen;
  }

  /**
   * Determine if Administrator can have Agent role
   * and Add/Remove Agent role to/from Administrator
   *
   * @param array $wpp_settings
   * @return array $wpp_settings
   * @author peshkov@UD
   */
  static public function add_role_agent_to_admin($wpp_settings)
  {
    global $wp_roles;

    //* Determine if Agent role exists at all */
    if (!array_key_exists('agent', $wp_roles->role_names)) {
      return $wpp_settings;
    }


    //* Gel all Administrators */
    $users = get_users(array(
      'role' => 'administrator'
    ));

    //* Add or Remove Agent role depending on option */
    foreach ($users as $u) {
      $user = new WP_User($u->ID);
      //* Get option from WPP settings which allows administrator to be an agent */
      if (
        isset($wpp_settings['configuration']['feature_settings']['agents']['add_role_to_admin'])
        && in_array($wpp_settings['configuration']['feature_settings']['agents']['add_role_to_admin'], array('true', 'on'))
      ) {
        $user->add_role('agent');
      } else {
        $user->remove_role('agent');
      }
    }

    return $wpp_settings;
  }

  /**
   * Add menu to WP's navigation
   *
   * @todo Make sure that the position after WPP page isnt occupied. - potanin@UD
   */
  static public function admin_menu()
  {
    global $wp_properties, $menu;

    $label = ud_get_wp_property('configuration.feature_settings.agents.label.plural');
    add_submenu_page('edit.php?post_type=property', $label, $label, self::$capability, 'show_agents', array('class_agents', 'show_agents'));
    
  }

  /**
   * Helper functions
   * @param $user_id
   * @param bool $amp
   * @return string
   */
  static public function get_link_edit($user_id, $amp = true)
  {
    $del = $amp ? '&amp;' : '&';
    return 'edit.php?post_type=property' . $del . 'page=show_agents' . $del . 'user_id=' . $user_id . $del . 'action=edit';
  }


  static public function get_link_delete($user_id)
  {
    return self::get_link_edit($user_id) . '&amp;action=delete';
  }


  /**
   * Displays a list of agents.
   */
  static public function show_agents()
  {
    global $wpdb, $wp_messages;

    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {
      self::edit_agent($_REQUEST['user_id']);
      return;
    }

    if (!isset($_REQUEST['role'])) {
      $_REQUEST['role'] = 'agent';
    }

    ?>
    <style type="text/css">
      th.column-id {
        width: 18px;
      }
    </style>
    <div class="wrap">
      <div class="icon32" id="icon-users"><br/></div>
      <h2><?php echo ud_get_wp_property('configuration.feature_settings.agents.label.plural'); ?> <a
          class="button add-new-h2"
          href="<?php echo self::get_link_edit(-1); ?>"><?php _e('Add New', ud_get_wpp_agents()->domain); ?></a></h2>


      <?php if (isset($wp_messages['error']) && $wp_messages['error']): ?>
        <div class="error">
          <?php foreach ($wp_messages['error'] as $error_message): ?>
          <p><?php echo $error_message; ?>
            <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (isset($wp_messages['notice']) && $wp_messages['notice']): ?>
        <div class="updated fade">
          <?php foreach ($wp_messages['notice'] as $notice_message): ?>
          <p><?php echo $notice_message; ?>
            <?php endforeach; ?>
        </div>
      <?php endif; ?>


      <?php if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') : ?>
        <?php self::delete_agent($_REQUEST['user_id']); ?>
        <div class="updated fade" id="message"><p><?php _e('User removed.', ud_get_wpp_agents()->domain); ?></p></div>
      <?php endif; ?>
      <?php
      $wpp_agents_list_table = _get_list_table('WP_Users_List_Table');
      $wpp_agents_list_table->prepare_items();

      ?>
      <div class="tablenav">

        <?php
        $wpp_agents_list_table->pagination('top');
        $wpp_agents_list_table->views();
        ?>
        <br class="clear"/>
      </div>
      <?php
      $wpp_agents_list_table->display();
      ?>
      <br class="clear">
    </div>
    <script type="text/javascript">
      jQuery('.tablenav.top').css('display', 'none');
      jQuery('.tablenav.bottom').css('display', 'none');
    </script>
    <?php
  }

  /**
   * Displays a list of agents.
   */
  static public function edit_agent($userId)
  {
    global $wp_messages, $screen_layout_columns, $wp_properties;

    $newUser = $userId == -1 ? true : false;
    //** create new user */
    if ($newUser) {
      $user = array(
        'ID' => $userId,
        'new_user' => true,
        'display_name' => '',
        'user_login' => '',
        'user_email' => '',
        'widget_bio' => '',
        'full_bio' => '',
        'flyer_content' => '',
      );
      if (!empty($wp_properties['configuration']['feature_settings']['agents']['agent_fields'])) {
        foreach ((array)$wp_properties['configuration']['feature_settings']['agents']['agent_fields'] as $i => $data) {
          $user[$i] = '';
        }
      }
      if (!empty($wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'])) {
        foreach ((array)$wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'] as $i => $data) {
          $user[$i] = '';
        }
      }
      $user = (object)$user;
    } //** update existing user */
    else {
      $user = get_userdata($userId);
    }

    $use = stripslashes_deep($user);
    ?>
    <script type="text/javascript">
      jQuery(document).ready(function () {

        jQuery("#your-profile").submit(function () {

          if (jQuery("input#display_name").val() == '') {
            jQuery("input#display_name").focus();
            return false;
          }

          if (jQuery("input#user_login").val() == '') {
            jQuery("input#user_login").focus();
            return false;
          }

          if (!wpp_validate_email(jQuery("input#email").val())) {
            jQuery("input#email").focus();
            jQuery('.wpp_agent_email_nag').remove();
            jQuery("<div class='wpp_agent_email_nag description'><?php _e('Please enter a valid e-mail.'); ?></div>").insertAfter(jQuery("input#email"));
            return false;
          }

        });
      });
    </script>
    <div id="profile-page" class="wpp_agent_page wrap">
      <div class="icon32" id="icon-users"><br/></div>
      <?php if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'update') : ?>
        <div class="updated fade" id="message"><p><?php _e('Agent updated.', ud_get_wpp_agents()->domain); ?></p></div>
      <?php endif; ?>
      <?php if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'create') : ?>
        <div class="updated fade" id="message">
          <p><?php printf(__('%s created.', ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.single')); ?></p>
        </div>
      <?php endif; ?>

      <h2><?php printf(__('Edit %s', ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.single')); ?><?php echo $user->display_name; ?></h2>

      <?php if (isset($_REQUEST['error']) && $_REQUEST['error']): ?>
        <div class="error">
          <p><?php echo $_REQUEST['error']; ?></p>
        </div>
      <?php endif; ?>

      <?php if (isset($wp_messages['notice']) && $wp_messages['notice']): ?>
        <div class="updated fade">
          <?php foreach ($wp_messages['notice'] as $notice_message): ?>
          <p><?php echo $notice_message; ?>
            <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="" id="your-profile">
        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpp_edit_agent'); ?>"/>
        <?php if (!WPP_F::is_older_wp_version('3.4')) : ?>
          <div id="poststuff" class="crm-wp-v34">
            <div id="post-body"
                 class="metabox-holder <?php echo 2 == $screen_layout_columns ? 'columns-2' : 'columns-1'; ?>">
              <div id="postbox-container-1" class="postbox-container">
                <div id="side-sortables" class="meta-box-sortables ui-sortable">
                  <?php do_meta_boxes('property_page_show_agents', 'side', $user); ?>
                </div>
              </div>
              <div id="postbox-container-2" class="postbox-container">
                <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                  <?php do_meta_boxes('property_page_show_agents', 'normal', $user); ?>
                </div>
                <div id="advanced-sortables" class="meta-box-sortables ui-sortable">
                  <?php do_meta_boxes('property_page_show_agents', 'advanced', $user); ?>
                </div>
              </div>
            </div>
          </div><!-- /poststuff -->
        <?php else : ?>
          <div id="poststuff"
               class="metabox-holder <?php echo 2 == $screen_layout_columns ? 'has-right-sidebar' : ''; ?>">
            <div id="side-info-column" class="inner-sidebar">
              <?php do_meta_boxes('property_page_show_agents', 'side', $user); ?>
            </div>

            <div id="post-body">
              <div id="post-body-content">
                <?php do_meta_boxes('property_page_show_agents', 'normal', $user); ?>
                <?php do_meta_boxes('property_page_show_agents', 'advanced', $user); ?>
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
  static public function publish_box_options()
  {
    /* Do not show if there are no agetns */
    $agents = self::get_agents();

    if (empty($agents)) {
      return;
    }

    $agents_meta = self::get_agents_postmeta(); ?>

    <li class="wpp_agent_select_wrap">
      <span
        class="wpp_agent_selector_title"><?php printf(__('Associated %s', ud_get_wpp_agents()->domain), ud_get_wp_property('configuration.feature_settings.agents.label.plural')); ?></span>
      <div class="wpp_agent_selector wp-tab-panel">
        <ul>
          <?php foreach ($agents as $agent) : $agent = stripslashes_deep($agent); ?>
            <li>
              <input type="checkbox" name="wpp_agents[]" id="wpp_agent_<?php echo $agent->ID; ?>"
                     value="<?php echo $agent->ID; ?>" <?php echo(in_array($agent->ID, $agents_meta) ? 'checked=checked' : ''); ?> />
              <label for="wpp_agent_<?php echo $agent->ID; ?>"><a
                  href="<?php echo self::get_link_edit($agent->ID); ?>"> <?php echo $agent->display_name; ?> </a></label>
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
  static public function save_property($post_id)
  {

    $current_screen = get_current_screen();

    if (current_user_can(self::$capability) && !empty($current_screen)) {
      //** Delete all old agents */
      delete_post_meta($post_id, 'wpp_agents');
      if (!empty($_REQUEST['wpp_agents']) && is_array($_REQUEST['wpp_agents'])) {
        foreach ($_REQUEST['wpp_agents'] as $agent_id) {
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
    if (in_array('agent', $roles) && !in_array('administrator', $roles)) {
      $agents = self::get_agents_postmeta();
      if (!in_array($current_user->ID, $agents)) {
        add_post_meta($post_id, 'wpp_agents', $current_user->ID);
      }
    }
  }


  /**
   * Adds section to user edit screen
   */
  static public function show_profile_fields($user)
  {
    global $wp_properties;
    $fields = self::clean_array(!empty($wp_properties['configuration']['feature_settings']['agents']['agent_fields']) ? $wp_properties['configuration']['feature_settings']['agents']['agent_fields'] : array());
    $social_fields = self::clean_array(!empty($wp_properties['configuration']['feature_settings']['agents']['agent_social_fields']) ? $wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'] : array());
    ?>
    <table class="form-table">
      <?php if (is_array($social_fields) and !empty($social_fields)) : ?>
        <?php foreach ($social_fields as $key => $social_field) : ?>
          <tr class='column-<?php echo $key; ?>'>
            <th><label><?php echo $social_field['name']; ?></label></th>
            <td>
              <input type="text" class="regular-text" name="agent_social_fields[<?php echo $key; ?>]"
                     value="<?php echo esc_attr($user->{$key}); ?>"/>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif;
      if (is_array($fields) and !empty($fields)) : ?>
        <?php foreach ($fields as $key => $field) : ?>
          <tr class='column-<?php echo $key; ?>'>
            <th><label><?php echo $field['name']; ?></label></th>
            <td>
              <input type="text" class="regular-text" name="agent_fields[<?php echo $key; ?>]"
                     value="<?php echo esc_attr($user->{$key}); ?>"/>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td
            colspan="2"><?php printf(__('You can add fields on the <a href="%s">settings page</a>.', ud_get_wpp_agents()->domain), 'edit.php?post_type=property&page=property_settings#tab_agents'); ?></td>
        </tr>
      <?php endif; ?>

      <?php if (class_exists('class_wpp_pdf_flyer')) : ?>
        <tr>
          <th><label for="flyerwriteup"><?php _e('Flyer Writeup', ud_get_wpp_agents()->domain); ?></label></th>
          <td>
            <textarea cols="30" rows="5" id="flyerwriteup" name="agent_fields[flyer_content]"
                      style="width:300px;"><?php echo esc_attr($user->flyer_content); ?></textarea><br/>
            <span
              class="description"><?php _e('Please enter a writeup for flyers.', ud_get_wpp_agents()->domain); ?></span>
          </td>
        </tr>
      <?php endif; ?>
    </table>
    <?php
  }

  /**
   * Saves user data.
   */
  static public function save_profile_basic($user_id)
  {
    if ($user_id == -1)
      self::create_agent($_POST['user_login'], $_POST['display_name'], $_POST['email']);
    else
      self::update_agent($user_id, $_POST['display_name'], $_POST['email']);
  }

  /**
   * Saves user fields.
   */
  static public function save_profile_fields($user_id)
  {
    global $wpdb, $wpp_agent_user_id;

    # In case user was just created
    if ($user_id == -1 && !empty( $wpp_agent_user_id ) && $wpp_agent_user_id > 0 ) {
      $user_id =  $wpp_agent_user_id;
    } else if ($user_id == -1) {
      // Something went wrong.... Break here
      return;
    }

    # Custom fields
    if (isset($_POST['agent_fields']) && is_array($_POST['agent_fields'])) {
      foreach ($_POST['agent_fields'] as $key => $agent_field) {
        update_user_meta($user_id, $key, $agent_field);
      }
    }
    if (isset($_POST['agent_social_fields']) && is_array($_POST['agent_social_fields'])) {
      foreach ($_POST['agent_social_fields'] as $key => $agent_field) {
        update_user_meta($user_id, $key, $agent_field);
      }
    }

    $user = $wpdb->get_row( $wpdb->prepare(
      "SELECT * FROM $wpdb->users WHERE ID = %s", $user_id
    ) );
    wp_cache_set($user_id, $user, 'users');
  }

  /**
   * Redirect to edit page.
   */
  static public function redirect_agent_page($user_id)
  {
    # In case user was just created
    global $wp_messages;
    if (isset($wp_messages['wp_agent_error']) && $wp_messages['wp_agent_error'] != '') {
      $wp_agent_error = $wp_messages['wp_agent_error'];
      wp_redirect('edit.php?post_type=property&page=show_agents&user_id=' . $user_id . '&action=edit&error=' . urlencode($wp_agent_error));
      die();
    }

    if ($user_id == -1) {
      $user_id = $GLOBALS['wpp_agent_user_id'];
      wp_redirect('edit.php?post_type=property&page=show_agents&user_id=' . $user_id . '&action=edit&status=create');
      die();
    }
    wp_redirect('edit.php?post_type=property&page=show_agents&user_id=' . $user_id . '&action=edit&status=update');
    die();
  }

  /**
   * Adds section to user edit screen
   */
  static public function metabox_profile_images($user)
  {
    wp_enqueue_media();

    if ($user->ID == -1) {
      echo '<p class="pad10">' . __('Please save first to add images.', ud_get_wpp_agents()->domain) . '</p>';
      return;
    }
    $images = self::get_agent_images($user->ID);
    ?>

    <ul id="wpp_agent_image_gallery">
      <?php if (empty($images)) : ?>
        <li
          class="wpp_agent_single_image wpp_no_agent_images"><?php _e('No images found.', ud_get_wpp_agents()->domain); ?></li>
        <li style="display: none;" class='wpp_agent_single_image'>
          <table>
            <tr>
              <td>
                <img src="" alt="" style="width: 100%;"/>
              </td>
            </tr>
          </table>
        </li>
      <?php else : ?>
        <?php $image = $images[0]; ?>
        <li class='wpp_agent_single_image'>
          <table>
            <tr>
              <td>
                <img src="<?php echo $image['src']; ?>" alt="" style="width: 100%;"/>
              </td>
            </tr>
          </table>
          <div class='delete_image'>
            <a
              href="<?php echo self::get_link_edit($user->ID) . '&amp;remove_agent_image=' . $image['id']; ?>"><?php _e('Remove', ud_get_wpp_agents()->domain); ?></a>
          </div>
        </li>
      <?php endif; ?>
    </ul>
    <div class="clear"></div>

    <button class="button-secondary agent-image-select clearfix" style="width:100px;margin-bottom:10px;margin-left:7px;"
            data-uploader_title="<?php _e('Select', ud_get_wpp_agents()->domain); ?>"><?php _e('Select', ud_get_wpp_agents()->domain); ?></button>
    <script type="text/javascript">
      jQuery(document).ready(function () {
        jQuery('.agent-image-select').agent_image_select({
          image: ".wpp_agent_single_image img"
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
  static public function remove_agent_image()
  {
    if (!isset($_REQUEST['remove_agent_image']) || !isset($_REQUEST['user_id'])) {
      return;
    }
    $attachment_id = $_REQUEST['remove_agent_image'];
    $user_id = $_REQUEST['user_id'];
    if (!is_numeric($attachment_id) || !is_numeric($user_id)) return;
    /** Remove attachment id from user meta */
    $agent_images = get_user_meta($user_id, 'agent_images', true);
    if (!in_array($attachment_id, (array)$agent_images)) return;
    foreach ($agent_images as $i => $id) {
      if ($id == $attachment_id || $id == 0) {
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
  static public function get_agents()
  {
    global $blog_id, $wp_properties;

    $agent_roles = !empty($wp_properties['configuration']['feature_settings']['agents']['agent_roles']) ?
      $wp_properties['configuration']['feature_settings']['agents']['agent_roles'] : array('agent');

    $agent_users = array();
    foreach ($agent_roles as $agent_role) {
      if ($found_agents = get_users(array('blog_id' => $blog_id, 'role' => $agent_role))) {
        $agent_users = array_merge($agent_users, $found_agents);
      }
    }

    if (count($agent_users)) {
      return $agent_users;
    }

    return false;
  }


  /**
   * Create a user.
   */
  static public function create_agent($user_login, $display_name, $email)
  {
    global $wp_messages, $wpp_agent_user_id;

    if ($user_login == '' || $display_name == '' || $email == '') {
      $wp_messages['wp_agent_error'] = "Display name, User name and Email address is required field! Please reinput required field.";
      return;
    }

    $user_id = username_exists($user_login);
    if (empty($user_id) == false) {
      $wp_messages['wp_agent_error'] = "User already exist!";
      return;
    }

    $user_id = email_exists($email);
    if (empty($user_id) == false) {
      $wp_messages['wp_agent_error'] = 'Email already exist!';
      return;
    }

    # Create user
    $random_password = wp_generate_password(12, false);
    $wpp_agent_user_id = $user_id = wp_create_user($user_login, $random_password, $email);

    //NOTIFY NEW USER (AGENT) BY MAIL: wp_new_user_notification( $user_id, $random_password );
    //wp_new_user_notification( $user_id, $random_password );

    # Role
    global $wpdb;
    update_user_meta($user_id, $wpdb->prefix . 'capabilities', array('agent' => 1));

    # Basics
    self::update_agent($user_id, $display_name, $email);

    return $user_id;
  }


  /**
   * Update a user.
   */
  static public function update_agent($user_id, $display_name, $email)
  {
    if (empty($user_id)) return;

    global $wp_messages;

    $check_user_id = email_exists($email);
    if (empty($check_user_id) == false && $check_user_id != $user_id) {
      $wp_messages['wp_agent_error'] = 'Email already exist!';
      return;
    }

    global $wpdb;

    $wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->users . ' SET user_email = "%s" WHERE ID = %d', array($email, $user_id)));
    $wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->users . ' SET user_nicename = "%s" WHERE ID = %d', array($display_name, $user_id)));
    $wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->users . ' SET display_name = "%s" WHERE ID = %d', array($display_name, $user_id)));
  }


  /**
   * Removes user with given ID.
   */
  static public function delete_agent($user_id)
  {
    if (!is_numeric($user_id)) return;
    global $wpdb;
    $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->users . ' WHERE ID = %d', array($user_id)));
    $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->usermeta . ' WHERE user_id = %d', array($user_id)));
  }


  /**
   * Returns agents associated with current post.
   */
  static public function get_agents_postmeta()
  {
    global $post;
    $agents = get_post_meta($post->ID, 'wpp_agents');
    if (empty($agents) or !is_array($agents)) return array();
    return $agents;
  }


  /**
   * Returns images linked to an agent.
   */
  static public function get_agent_images($user_id, $size = 'thumbnail', $args = array())
  {
    if (!is_numeric($user_id) and intval($user_id) > 0) return array();

    $image_ids = get_user_meta($user_id, 'agent_images', false);
    if (empty($image_ids)) return array();
    else $image_ids = $image_ids[0];

    //** Optional arguments */
    $defaults = array(
      'return' => 'array'
    );
    $args = wp_parse_args($args, $defaults);

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
      if (empty($image)) continue;
      $image['id'] = $image_id;
      $images[] = $image;
    }

    return $images;
  }


  /**
   * Returns true in case the given agent is in the given array, otherwise false.
   */
  static public function contains_agent(array $agents, $agent)
  {
    if (empty($agents) or !is_array($agents))
      return false;
    foreach ($agents as $a)
      if ($a == $agent) return true;
    return false;
  }


  /**
   * Returns the default capabilites for agents.
   */
  static public function get_agent_capabilities()
  {
    global $wp_properties;
    $editor_role = get_role('author');

    if (!$editor_role) {
      return array();
    }

    $agent_capabilities = (empty($wp_properties['agent_capabilities'])) ? $editor_role->capabilities : $wp_properties['agent_capabilities'];
    #$agent_capabilities = $editor_role->capabilities; # TODO restore to default
    return $agent_capabilities;
  }


  /**
   * Returns an array with nice descriptions for capabilities.
   */
  static public function get_capabilities_pretty()
  {
    // Hint: Only a subset of all capabilities, mostly the ones for an editor
    return array('moderate_comments' => __('Moderate comments', ud_get_wpp_agents()->domain),
      'manage_categories' => __('Manage categories', ud_get_wpp_agents()->domain),
      'manage_links' => __('Manage links', ud_get_wpp_agents()->domain),
      'upload_files' => __('Upload files', ud_get_wpp_agents()->domain),
      'edit_posts' => __('Edit posts', ud_get_wpp_agents()->domain),
      'edit_others_posts' => __("Edit other's posts", ud_get_wpp_agents()->domain),
      'edit_published_posts' => __('Edit published posts', ud_get_wpp_agents()->domain),
      'publish_posts' => __('Publish posts', ud_get_wpp_agents()->domain),
      'edit_pages' => __('Edit pages', ud_get_wpp_agents()->domain),
      'read' => __('Read', ud_get_wpp_agents()->domain),
      'edit_others_pages' => __("Edit other's pages", ud_get_wpp_agents()->domain),
      'edit_published_pages' => __('Edit published pages', ud_get_wpp_agents()->domain),
      'publish_pages' => __('Publish pages', ud_get_wpp_agents()->domain),
      'delete_pages' => __('Delete pages', ud_get_wpp_agents()->domain),
      'delete_others_pages' => __("Delete other's pages", ud_get_wpp_agents()->domain),
      'delete_published_pages' => __('Delete published pages', ud_get_wpp_agents()->domain),
      'delete_posts' => __('Delete posts', ud_get_wpp_agents()->domain),
      'delete_others_posts' => __("Delete other's posts", ud_get_wpp_agents()->domain),
      'delete_published_posts' => __('Delete published posts', ud_get_wpp_agents()->domain),
      'delete_private_posts' => __('Delete private posts', ud_get_wpp_agents()->domain),
      'edit_private_posts' => __('Edit private posts', ud_get_wpp_agents()->domain),
      'read_private_posts' => __('Read private posts', ud_get_wpp_agents()->domain),
      'delete_private_pages' => __('Delete private pages', ud_get_wpp_agents()->domain),
      'edit_private_pages' => __('Edit private pages', ud_get_wpp_agents()->domain),
      'read_private_pages' => __('Read private pages', ud_get_wpp_agents()->domain));
  }


  /**
   * Utility function that removes empty elements from the given array.
   */
  static public function clean_array($array)
  {
    if (empty($array) or !is_array($array))
      return array();

    $newArray = array();
    foreach ($array as $key => $value) {
      if (empty($value)) continue;

      # Special case for agent fields
      if (empty($value['name'])) continue;

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
  static public function shortcode_agent_card($atts)
  {
    global $property;

    $this_property = (object)$property;

    if (!empty($atts['user_id'])) {
      $user_id = $atts['user_id'];
    } else if (!empty($atts['agent_id'])) {
      $user_id = $atts['agent_id'];
    } else if (!empty($atts['agent'])) {
      $user_id = $atts['agent'];
    }

    $fields = '';

    extract(shortcode_atts(array(
      'fields' => 'display_name,agent_image,full_bio'
    ), $atts));

    $class = isset($atts['class']) ? $atts['class'] : array();

    if (WPP_LEGACY_WIDGETS) {
      $class[] = 'wpp_agents_content_agent_card';
    } else {
      $class[] = 'wpp_agents_content_agent_card_v2';
    }

    if (empty($user_id)) {

      $wpp_agents = $this_property->wpp_agents;

      if (is_array($wpp_agents)) {
        foreach ($wpp_agents as $agent_id) {
          $return[] = '<div class="' . implode('', $class) . '">' . class_agents::display_agent_card($agent_id, "fields=$fields") . '</div>';
        }
      }

    } else {
      $return[] = '<div class="' . implode('', $class) . '">' . class_agents::display_agent_card($user_id, "fields=$fields") . '</div>';
    }

    if (is_array($return)) {
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
  static public function display_agent_card($agent_id, $args = '')
  {
    global $wp_properties;

    $defaults = array(
      'fields' => 'display_name,agent_image,full_bio',
      'return' => 'true'
    );

    $args = wp_parse_args($args, $defaults);

    $fields = explode(',', $args['fields']);

    // Load all titles
    if (!empty($wp_properties['configuration']['feature_settings']['agents']['agent_fields'])) {
      foreach ($wp_properties['configuration']['feature_settings']['agents']['agent_fields'] as $slug => $attr_data) {
        $display_fields[$slug] = $attr_data['name'];
      }
    }
    if (!empty($wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'])) {
      foreach ($wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'] as $slug => $attr_data) {
        $display_social_fields[$slug] = $attr_data['name'];
      }
    }

    // Setup manual titles (not dynamic attributes)
    $display_fields['user_email'] = __('Email', ud_get_wpp_agents()->domain);
    $display_fields['display_name'] = __('Name', ud_get_wpp_agents()->domain);
    $display_fields['widget_bio'] = "";
    $display_fields['full_bio'] = "";

    $no_label_items = apply_filters('wpp_agent_no_label_items', array('display_name', 'agent_image', 'widget_bio', 'full_bio', 'agent_bio', 'flyer_content'));

    $user_data = get_userdata($agent_id);
    $user_data = stripslashes_deep($user_data);

    if (!$user_data) {
      return false;
    }

    ob_start();
    if (WPP_LEGACY_WIDGETS) {
      include ud_get_wpp_agents()->path('static/views/agent-widget.php', 'dir');
    } else {
      include ud_get_wpp_agents()->path('static/views/agent-widget-v2.php', 'dir');
    }
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
  static public function get_queryable_keys($keys)
  {
    $keys[] = 'wpp_agents';
    return $keys;
  }

  /*
   * Get Role description
   * used by another premium feature 'WPP Capabilities'.
   *
   * @author Maxim Peshkov
   */
  static public function role_description($description)
  {
    $description = __('Agent can see only the properties which are assigned to him. Agent will be automatically assigned to the property which he will create.', ud_get_wpp_agents()->domain);
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
  static public function prepare_wpp_properties_search($wpp_search)
  {
    /*
     * Determine if current user is agent
     * if so, we modify $wpp_search
     */
    $current_user = wp_get_current_user();
    $roles = $current_user->roles;
    if (in_array('agent', $roles) && !in_array('administrator', $roles)) {
      if (!is_array($wpp_search)) {
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
  static public function filter_property_filter($prefill_meta, $slug)
  {
    global $wpdb;

    // Non post_meta fields
    $non_post_meta = array(
      'post_title',
      'post_status',
      'post_author'
    );

    if (current_user_can("edit_others_wpp_properties")) {
      return $prefill_meta;
    }

    // Simple sanitizing
    $prefill_meta = array_unique($prefill_meta);
    foreach ($prefill_meta as $key => $meta) {
      if (empty($meta)) unset($prefill_meta[$key]);
    }

    $current_user = wp_get_current_user();
    $roles = $current_user->roles;

    // Process meta fields
    if (!in_array($slug, $non_post_meta)) {

      if (in_array('agent', $roles) && !in_array('administrator', $roles)) {
        $meta_ids = $wpdb->get_col("
            SELECT post_id FROM {$wpdb->prefix}postmeta
            WHERE meta_key = 'wpp_agents'
              AND meta_value = '{$current_user->ID}';
        ");
        
        if(!empty($meta_ids)){
          $prefill_meta = $wpdb->get_col("
            SELECT meta_value FROM {$wpdb->prefix}postmeta
            WHERE post_id IN (" . implode(",", $meta_ids) . ")
            AND meta_value IN ('" . implode("','", $prefill_meta) . "')
          ");
        }
      }

      $prefill_meta = array_unique($prefill_meta);

    } // Process NON meta fields
    else {

      if (in_array('agent', $roles) && !in_array('administrator', $roles)) {
        $meta_ids = $wpdb->get_col("
            SELECT post_id FROM {$wpdb->prefix}postmeta
            WHERE meta_key = 'wpp_agents'
              AND meta_value = '{$current_user->ID}';
        ");

        if(!empty($meta_ids)){
          $prefill_meta = $wpdb->get_col("
            SELECT $slug FROM {$wpdb->posts}
            WHERE ID IN (" . implode(",", $meta_ids) . ")
              AND post_type = 'property'
          ");
        }
      }

      $prefill_meta = array_unique($prefill_meta);

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
  static public function filter_properties_quantity($results, $post_status)
  {
    global $wpdb;

    if (current_user_can('manage_options') || current_user_can("edit_others_wpp_properties")) {
      return $results;
    }

    $current_user = wp_get_current_user();
    $roles = $current_user->roles;

    if (in_array('agent', $roles)) {

      $results = $wpdb->get_col("
        SELECT p.ID FROM {$wpdb->prefix}posts AS p
          LEFT JOIN {$wpdb->prefix}postmeta AS pm
          ON p.ID = pm.post_id
          WHERE pm.meta_key = 'wpp_agents'
            AND pm.meta_value = '{$current_user->ID}'
            AND p.post_status IN ('" . implode("','", $post_status) . "')
            AND p.post_type = 'property'
        ");

    }

    return $results;

  }

  /**
   * Filters List Tables results on All Properties page
   * by current Agent ( if agent does not have administrator's permissions )
   *
   * @param $args
   * @return mixed
   * @since 2.0.1
   */
  static public function filter_wp_query($args)
  {

    if (current_user_can('manage_options') || current_user_can("edit_others_wpp_properties")) {
      return $args;
    }

    $current_user = wp_get_current_user();
    $roles = $current_user->roles;

    if (!in_array('agent', $roles)) {
      return $args;
    }

    if (empty($args['meta_query']) || !is_array($args['meta_query'])) {
      $args['meta_query'] = array();
    }

    $args['meta_query'][] = array(
      'key' => 'wpp_agents',
      'value' => get_current_user_id(),
      'compare' => '=',
    );

    return $args;
  }

  /**
   * Filters available users from wpp
   * @param array $users
   * @return array
   */
  static public function filter_users_filter($users)
  {

    if (!current_user_can('edit_others_wpp_properties')) {
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
  static public function filter_month_periods_filter($months)
  {
    global $wpdb;

    $current_user = wp_get_current_user();
    $roles = $current_user->roles;

    if (in_array('agent', $roles) && !in_array('administrator', $roles)) {

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
   * Assigns the submittor as an agent, if their role permits
   *
   * @param array $return
   * @param array $data - array of user_id, return, property_id and form_id
   */
  static public function assign_agent_to_new_feps_listing($property_id)
  {
    global $wpdb, $wp_properties, $wp_roles;

    $_property = WPP_F::get_property($property_id, array('get_children' => 'false'));

    $user_id = $_property['post_author'];
    $user_data = get_userdata($user_id);

    $user_roles = $user_data->roles;

    $allowed_roles = isset($wp_properties['configuration']['feature_settings']['agents']['agent_roles']) ?
      $wp_properties['configuration']['feature_settings']['agents']['agent_roles'] : false;

    if ($property_id && is_array($user_roles) && is_array($allowed_roles) && array_intersect($user_roles, $allowed_roles)) {

      //** Just in case */
      delete_post_meta($property_id, 'wpp_agents');

      //** Add the new user as an agent */
      add_post_meta($property_id, 'wpp_agents', $user_id);

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
  static public function can_edit_post($value)
  {
    global $post, $current_user;

    if ($post->post_type) {
      $roles = (array)$current_user->roles;
      if (in_array('agent', $roles) && !in_array('administrator', $roles)) {
        if (current_user_can("edit_others_wpp_properties")) {
          return true;
        }
        $agents = (array)get_post_meta($post->ID, 'wpp_agents');
        $post_type_object = get_post_type_object($post->post_type);
        if (!in_array($current_user->ID, $agents) ||
          $post->post_author != $current_user->ID &&
          !current_user_can($post_type_object->cap->edit_others_posts)
        ) {
          return false;
        }
      }
    }

    return $value;
  }

  /*  Function agent_has_cap();
   *  Parameters: 
   *    $allcaps: All capabilities user have.
   *    $cap: All required capabilities
   *    $args[0]: The capability
   *    $args[1]: User ID
   *    $args[2]: Post ID
   *    $user: User object.
   *  Return: Extended $allcaps.
   */
  static public function agent_has_cap($allcaps, $caps, $args, $user)
  {
    if (!in_array("agent", $user->roles))
      return $allcaps;
    if (!isset($args[2])) {
      global $post;
      $pid = $post ? $post->ID : 0;
    } else {
      $pid = $args[2];
      $post = get_post($pid);
    }
    $cap = $args[0];

    $allowed_caps = array(
      'delete_post',
      'delete_others_posts',
      'edit_others_posts',
      'edit_wpp_property',
      'publish_wpp_properties',
      'edit_wpp_properties',
      'edit_others_wpp_properties',
    );

    if ($post && ($post->post_type == 'attachment' || $post->post_type == 'property')) {
      $parent_id = ($post->post_type == 'attachment') ? $post->post_parent : $pid;
      $agents = (array)get_post_meta($parent_id, 'wpp_agents');

      // Bail out if the user is the post author:
      if ($user->ID == $post->post_author)
        return $allcaps;

      if (in_array($user->ID, $agents) || array_key_exists('edit_others_wpp_properties', $allcaps)) {
        foreach ($allowed_caps as $key => $c) {
          $allcaps[$c] = true;
        }
      }

    }

    return $allcaps;

  }

} // end class_agents