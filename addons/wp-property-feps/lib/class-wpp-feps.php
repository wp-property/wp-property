<?php
/**
 * Core
 */

class class_wpp_feps {

  /**
   * (custom) Capability to manage the current feature
   */
  static protected $capability = "manage_wpp_feps";


  /**
   * CRM Notification actions
   *
   * Available template tags you can use in WP CRM plugin for user notifications:
   *
   * For 'pending_property_approve':
   * - [user_email] Email where to send notification
   * - [display_name] User's name
   * - [url] Property url
   * - [title] Property title
   *
   * For 'pending_property_added':
   * - [user_email] Email where to send notification
   * - [pending_url] Pending Property url
   * - [title] Property title
   *
   * For 'pending_property_added_admin':
   * - [property_link] Property link
   * - [user_login] User's login
   * - [user_email] Email where to send notification
   *
   * For 'pending_account_approved':
   * - [user_email] Email where to send notification
   * - [user_login] User's login
   * - [site_url] Current site url
   *
   */
  static protected $crm_notification_actions = array(
    'pending_property_approve'          => 'FEPS: Pending Approval',
    'pending_property_added'            => 'FEPS: Property Submitted',
    'pending_property_added_admin'      => 'FEPS: Property Submitted (Notify Administrator)',
    'pending_account_approved'          => 'FEPS: User Account Approved',
    'feps_use_account_created'          => 'FEPS: User Account Created',
    'feps_status_updated'               => 'FEPS: Property Status Updated',
  );

  /**
   * Available property statuses
   * @var array
   */
  static protected $statuses = array( 'publish', 'pending', 'trash' );

  /**
   * Special functions that must be called prior to init
   *
   */
  static public function wpp_pre_init() {

    self::_handle_upgrade();

    /** Add capability */
    add_filter("wpp_capabilities", array('class_wpp_feps', "add_capability"));

    /** Add CRM notification fire action */
    add_filter("wp_crm_notification_actions", array('class_wpp_feps', 'crm_custom_notification'));

    /** Register feps schedule events */
    self::feps_schedule_events();

    //** If WP-Invoice and SPC premium feature is installed we add custom templates from wp-property */
    add_filter( 'wpi::spc::template', array( __CLASS__, 'wpi_spc_template' ), 0, 1 );

    //** Add localization of javascript files */
    add_filter( 'wpp::js::localization', array( __CLASS__, 'js_localization' ), 0, 1 );

    //** Templates filters */
    add_filter( 'feps::template::submit::btn', array( __CLASS__, 'filter_submit_button' ), 0, 3 );
  }

  /**
   * Primary feature function.  Ran an init level.
   *
   * @since 0.1
   */
  static public function wpp_post_init() {
    global $wp_properties;

    if( current_user_can( self::$capability ) ) {
      //** Add Inquiry page to Property Settings page array */
      add_filter( 'wpp_settings_nav', array( __CLASS__, 'settings_nav' ) );
      //** Add Settings Page */
      add_action( 'wpp_settings_content_feps', array( __CLASS__, 'settings_page' ) );

      //** Adds Contextual Helps */
      add_action( 'load-property_page_page_feps', array( __CLASS__, 'contextual_help_property_forms' ) );
      add_action( 'property_page_property_settings_help', array( __CLASS__, 'contextual_help_settings' ) );

      //** Adds custom FEPS column in 'All Properties' table if needed */
      add_filter( 'wpp_overview_columns', array( __CLASS__, 'overview_column' ) );
      add_filter( 'wpp::single_row::feps', '__return_true', 0, 2 );
      add_filter( 'wpp::single_row::feps::render', array( __CLASS__, 'render_overview_column' ), 0, 2 );
    }

    add_shortcode('wpp_feps_form', array('class_wpp_feps', 'wpp_feps_form'));
    add_shortcode('wpp_feps_menu', array('class_wpp_feps', 'wpp_feps_menu'));
    add_shortcode('wpp_feps_info', array('class_wpp_feps', 'wpp_feps_info'));

    /** Add FEPS shortcode form creation page under Properties nav menu */
    add_action("admin_menu", array("class_wpp_feps", "admin_menu"));

    add_filter("added_post_meta", array('class_wpp_feps', "handle_post_meta"), 10, 4);

    add_action("admin_init", array("class_wpp_feps", "admin_init"));

    add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'post_submitbox_misc_actions' ), 30);
    add_action( 'edit_form_after_title', array( __CLASS__, 'post_property_form_information' ), 0 );

    add_action("wpp_template_redirect", array("class_wpp_feps", "wpp_template_redirect"));

    //** Upload image */
    add_action( "wp_ajax_wpp_feps_image_upload", array("class_wpp_feps", "ajax_feps_image_upload" ) );
    add_action( "wp_ajax_nopriv_wpp_feps_image_upload", array("class_wpp_feps", "ajax_feps_image_upload" ) );

    //** Delete uploaded image */
    add_action( "wp_ajax_wpp_feps_image_delete", array("class_wpp_feps", "ajax_feps_image_delete" ) );
    add_action( "wp_ajax_nopriv_wpp_feps_image_delete", array("class_wpp_feps", "ajax_feps_image_delete" ) );

    add_action( "wp_ajax_wpp_feps_email_lookup", function(){
      die(json_encode(class_wpp_feps::email_lookup($_REQUEST["user_email"],$_REQUEST["user_password"])));
    });
    add_action( "wp_ajax_nopriv_wpp_feps_email_lookup", function(){
      die(json_encode(class_wpp_feps::email_lookup($_REQUEST["user_email"],$_REQUEST["user_password"])));
    });

    add_action( "wp_ajax_wpp_feps_save_property", array( __CLASS__, "ajax_feps_save_property" ) );
    add_action( "wp_ajax_nopriv_wpp_feps_save_property", array( __CLASS__, "ajax_feps_save_property" ) );

    //* On FEPS Form removing */
    add_action( "wp_ajax_wpp_feps_can_remove_form", array("class_wpp_feps", "ajax_can_remove_form") );

    // Ajax login
    add_action( 'wp_ajax_nopriv_feps_user_login', array( __CLASS__, "ajax_user_login" ) );

    add_action("admin_enqueue_scripts", array('class_wpp_feps', "admin_enqueue_scripts"));

    add_action("post_updated", array('class_wpp_feps', "post_updated"), 10, 3);

    add_action("all_admin_notices", array('class_wpp_feps', "all_admin_notices"));

    add_filter( 'wpp_get_property', array( __CLASS__, 'wpp_get_property') );

    add_filter( 'wpp::overview::filter::fields', array( __CLASS__, 'wpp_get_search_filters') );

    add_filter( 'wpp_attribute_filter', array( __CLASS__, 'wpp_attribute_filter'),10, 2 );

    add_filter('wpp_overview_columns', array( __CLASS__ , 'wpp_overview_columns'));

    add_action("wpp_settings_overview_bottom", array( __CLASS__, 'wpp_settings_overview_bottom') );

    add_filter("wpp_attribute_data", array('class_wpp_feps', "add_attribute_data"));

    add_filter("wpp_pending_template_query", array('class_wpp_feps', 'wpp_query_filter'), 10, 2);

    add_filter("authenticate", array('class_wpp_feps', 'authenticate'), 40, 3);

    add_filter("wpp_feps_property_statuses", array('class_wpp_feps', 'property_statuses'));

    add_filter( 'get_queryable_keys', array( __CLASS__, 'queryable_keys' ) );
    
    add_filter( 'wpp::metabox::attribute::show', array( __CLASS__, 'filter_metabox_attribute_show' ), 10, 3 );

    add_action("wpp_feps_account_created", array('class_wpp_feps', 'account_created'), 10);

    /** Notifications */
    add_action( "wpp_feps_submission_approved", array( 'WPP_Mail', 'feps_post_approved' ), 10, 1 );
    add_action( "wpp_feps_submitted", array( 'WPP_Mail', 'feps_post_created' ), 10, 1 );
    add_action( "wpp_feps_submitted", array( 'WPP_Mail', 'feps_notify_admin_post_created' ), 10, 1 );

    add_filter("parse_query", array('class_wpp_feps', 'fix_404'));

    //* Loads template for My FEPS Page */
    add_action( 'wpp_my_feps_page', array( __CLASS__, 'my_feps_page_load' ) );
    //* Loads template for FEPS Edit Page */
    add_action( 'wpp_feps_edit_page', array( __CLASS__, 'feps_edit_page_load' ) );

    //** Always login user */
    add_action('wpp_feps_credentials_verified', array(__CLASS__, 'credentials_verified_action'));

    //** User activator */
    add_action("template_redirect",                       array(__CLASS__, "feps_user_activation"), 1);

    //** FEPS+SPC integration */
    if ( class_exists('wpi_spc') ) {
      add_action('wpp_feps_form_settings_after_general',  array(__CLASS__, 'spc_options'));
      add_action('wpp_feps_form_settings_general_top',    array(__CLASS__, 'spc_options_general'));
      add_action('wpp::feps::save::callback',             array(__CLASS__, 'save_property_callback' ), 10, 3 );
      add_filter('wpp_feps_submit_property_form',         array(__CLASS__, 'filter_form_data'));
      add_filter('wpp::feps::fix_404',                    array(__CLASS__, 'spc_fix_404'), 10, 2);
      add_filter('wpp_feps_form_output',                  array(__CLASS__, 'feps_output_filter'), 10, 2);
      add_action('wpp_feps_add_feps_credits',             array(__CLASS__, 'after_add_credits'));
      add_action('wp_ajax_wpp_feps_pay_now',              array(__CLASS__, 'feps_pay_now'));
      add_action('wpp::feps::edit_property',              array(__CLASS__, 'feps_edit_property_options'), 10 );
      add_action('wp_ajax_wpp_feps_renew_plan',           array(__CLASS__, 'ajax_set_renew_plan'));
      
      //** Loads template for FEPS Credits Adding Page */
      add_action( 'wpp_feps_spc_page',                    array( __CLASS__, 'spc_page_load' ) );
      //** Adds ability to manage user's credits */
      add_action( 'show_user_profile', array( __CLASS__, 'user_profile_fields' ), 0 );
      add_action( 'edit_user_profile', array( __CLASS__, 'user_profile_fields' ), 0 );
      add_action( 'personal_options_update', array( __CLASS__, 'update_user_fields' ), 0 );
      add_action( 'edit_user_profile_update', array( __CLASS__, 'update_user_fields' ), 0 );
    }else{
      add_action('wpp_feps_form_settings_general_top',    array(__CLASS__, 'spc_options_general_miss'));
    }
    
    add_action('wp_head', array( __CLASS__, 'ajaxurl_frontend'), 1);
    add_action('wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts'),11);
  }

  /**
   * Handle pre-headers functions.
   *
   * @author potanin@ud
   */
  static public function admin_init() {
    if( 
      current_user_can( self::$capability ) 
      && isset( $_REQUEST['_wpnonce'] ) 
      && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpp_save_feps_page' ) 
    ) {
      //** Update settings and commit to DB */
      class_wpp_feps::save_feps_settings( $_REQUEST['wpp_feps'] );
      wp_redirect('edit.php?post_type=property&page=page_feps&message=updated');
    }
    $user_id = isset($_GET['user_id'])?$_GET['user_id']:get_current_user_id();
    self::update_user_fields($user_id);
  }

  static public function ajaxurl_frontend() {
    ?><script type="text/javascript">var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script><?php
  }

  static public function enqueue_scripts() {
    wp_enqueue_style('wpp-feps-style', WPP_FEPS_URL . '/static/styles/style.css');
    wp_enqueue_script('wpp-feps-dot', WPP_FEPS_URL . '/static/scripts/dot.js');
  }

  /**
   * Adds 'FEPS Details' column to 'All Properties' table
   *
   * @global array $wp_properties
   * @param array $columns
   * @return array
   * @author peshkov@UD
   * @since 2.0
   */
  static public function overview_column( $columns ) {
    global $wp_properties;

    if ( isset( $wp_properties['configuration']['feps']['enable_column_feps'] ) && $wp_properties['configuration']['feps']['enable_column_feps'] == 'true' ) {
      $columns[ 'feps' ] = __( 'FEPS Details', ud_get_wpp_feps()->domain );
    }
    return $columns;
  }


  /**
   * Adds data to column 'FEPS Details' on 'All Properties' table
   *
   * @param string $value
   * @param object $post
   * @param
   * @author peshkov@UD
   * @since 2.0
   */
  static public function render_overview_column( $value, $post ) {

    $form = self::get_form_by_post( $post->ID );

    if( $form ) {
      $user = get_userdata( $post->post_author );
      $credits = get_the_author_meta( FEPS_USER_CREDITS, $user->ID );
      $expired_time = get_post_meta( $post->ID, FEPS_META_EXPIRED, true );
      $expired_time = !empty( $expired_time ) ? WPP_F::nice_time( $expired_time, array('format'=>'date') ) : __( 'Not set', ud_get_wpp_feps()->domain );
      $sponsored = !empty( $form[ 'feps_credits' ] ) && $form[ 'feps_credits' ] == 'true' ? true : false;
      $plan = get_post_meta( $post->ID, FEPS_META_PLAN, true );

      ob_start();

      ?>
      <ul class="wpp_overview_column_stats wpp_feps_overview_column">
        <li>
          <span class="wpp_label"><?php _e( 'Property Form', ud_get_wpp_feps()->domain ); ?>:</span>
          <span class="wpp_value"><a target="_blank" href="<?php echo admin_url( 'edit.php?post_type=property&page=page_feps' ); ?>"><?php echo $form[ 'title' ]; ?></a></span>
        </li>
        <li>
          <span class="wpp_label"><?php _e( 'Author', ud_get_wpp_feps()->domain ); ?>:</span>
          <?php if ( current_user_can( 'edit_users', $user->ID ) ) : ?>
          <span class="wpp_value"><a target="_blank" href="<?php echo get_edit_user_link( $user->ID ); ?>"><?php echo $user->data->display_name; ?></a></span>
          <?php else : ?>
          <span class="wpp_value"><?php echo $user->data->display_name; ?></span>
          <?php endif; ?>
        </li>
        <li>
          <span class="wpp_label"><?php _e( 'Sponsored', ud_get_wpp_feps()->domain ); ?>:</span>
          <span class="wpp_value"><?php $sponsored ? _e( 'Yes', ud_get_wpp_feps()->domain ) : _e( 'No', ud_get_wpp_feps()->domain ) ; ?></span>
        </li>
        <?php if( $sponsored ) : ?>
          <li>
            <span class="wpp_label"><?php _e( 'Subscription Plan', ud_get_wpp_feps()->domain ); ?>:</span>
            <span class="wpp_value"><?php echo !empty( $plan ) && is_array( $plan ) ? $plan[ 'name' ] : __( 'Not set', ud_get_wpp_feps()->domain ); ?></span>
          </li>
        <?php endif; ?>
        <li>
          <span class="wpp_label"><?php _e( 'Expired date', ud_get_wpp_feps()->domain ); ?>:</span>
          <span class="wpp_value"><?php echo $expired_time; ?></span>
        </li>
      </ul>
      <?php

      $content = ob_get_clean();
      $value = apply_filters( 'wpp::feps::render_overview_column', $content, $post );
    }

    return $value;
  }

  /**
   * Adds admin tools manu to settings page navigation
   *
   * @author peshkov@UD
   * @since 2.0
   */
  static public function settings_nav( $tabs ) {
     $tabs['feps'] = array(
      'slug' => 'feps',
      'title' => __('FEPS',ud_get_wpp_feps()->domain),
    );
    return $tabs;
  }

  /**
   * Displays FEPS management page
   *
   *
   * @author peshkov@UD
   * @since 2.0
   */
  static public function settings_page() {
    global $wp_properties;
    
    $config = isset( $wp_properties['configuration']['feps'] ) ? $wp_properties['configuration']['feps'] : array();
    $config = wp_parse_args( $config, array(
      'menu' => array(),
      'templates' => array(),
      'enable_column_feps' => false,
    ) );
    $config[ 'menu' ] = wp_parse_args( $config[ 'menu' ], array(
      'add_credits_label' => '',
      'add_property_label' => '',
      'disable_if_no_properties' => false,
    ) );
    $config[ 'templates' ] = wp_parse_args( $config[ 'templates' ], array(
      'overview_page' => false,
      'edit_page' => false,
      'add_credits_page' => false,
    ) );
    
    $templates = get_page_templates();

    // Add index page in case theme has no templates.
    if( empty( $templates ) ) {
      $templates[ 'Index' ] = 'index';
    }

    ?>
    <table class="form-table">
      <tbody>
        <tr>
          <th><?php printf( __('%1s Forms',ud_get_wpp_feps()->domain), WPP_F::property_label() );; ?></th>
          <td>
            <?php printf( __( '<a href="%s">Manage %s Forms', ud_get_wpp_feps()->domain ), admin_url( 'edit.php?post_type=property&page=page_feps' ), WPP_F::property_label() ); ?>
          </td>
        </tr>
        <tr>
          <th><?php _e('FEPS Menu',ud_get_wpp_feps()->domain); ?></th>
          <td>
            <p>
              <?php printf( __( 'It is neccessary block menu which contains user\'s specific data and links to submitted %s via FEPS frontend forms.', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' ) ); ?><br/>
              <?php printf( __( 'Menu can be added to your site via <b>FEPS Menu</b> <a href="%s">widget</a> or <b>%s</b> shortcode.', ud_get_wpp_feps()->domain ), admin_url( 'widgets.php' ), '[wpp_feps_menu]' ); ?><br/>
              <?php _e( 'Note, menu is shown only for logged in users.', ud_get_wpp_feps()->domain ); ?>
            </p>
            <ul>
              <?php if ( class_exists( 'wpi_spc' ) ) : ?>
              <li><?php echo WPP_F::input("name=wpp_settings[configuration][feps][menu][add_credits_label]&style=width:250px;", $config['menu']['add_credits_label'] ); ?> <?php _e( '"Add Credits to Balance" custom label', ud_get_wpp_feps()->domain ); ?></li>
              <?php endif; ?>
              <li><?php echo WPP_F::input("name=wpp_settings[configuration][feps][menu][add_property_label]&style=width:250px;", $config[ 'menu' ]['add_property_label']); ?> <?php printf ( __('"Add New %s" custom label',ud_get_wpp_feps()->domain), WPP_F::property_label() ); ?></li>
              <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][feps][menu][disable_if_no_properties]&label=" . sprintf( __('Disable Menu if there are no %s and no credits on Balance', ud_get_wpp_feps()->domain), WPP_F::property_label( 'plural' ) ), $config[ 'menu' ]['disable_if_no_properties']); ?></li>
            </ul>
          </td>
        </tr>
        <tr>
          <th><?php _e('Specific Pages',ud_get_wpp_feps()->domain); ?></th>
          <td>
            <p>
              <?php printf( __( 'The feature has specific pages. The links below can be added directly to your <a href="%s">Menus</a> or wherever else, i.e. if you don\'t want to use FEPS menu.', ud_get_wpp_feps()->domain ), admin_url( 'nav-menus.php' ), WPP_F::property_label( 'plural' ) ); ?><br/>
              <?php _e( 'But note, that the current pages are available only for logged in users.', ud_get_wpp_feps()->domain ); ?>
            </p>
            <ul class="feps_specific_pages_links">
              <li class="feps_title"><?php _e( 'Templates', ud_get_wpp_feps()->domain ); ?></li>
              <li class="feps_templates">
                <label><?php _e( 'Overview Page', ud_get_wpp_feps()->domain ); ?>:</label>
                <select name="wpp_settings[configuration][feps][templates][overview_page]">
                  <option value="" >-</option>
                  <?php if( !empty( $templates ) ) : ?>
                    <?php foreach ( $templates as $title => $slug ) : ?>
                      <option value="<?php echo $slug ?>" <?php echo $config['templates']['overview_page'] == $slug ? 'selected="selected"' : ''; ?> ><?php echo $title; ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </li>
              <li class="feps_templates">
                <label><?php _e( 'Edit Page', ud_get_wpp_feps()->domain ); ?>:</label>
                <select name="wpp_settings[configuration][feps][templates][edit_page]">
                  <option value="" >-</option>
                  <?php if( !empty( $templates ) ) : ?>
                    <?php foreach ( $templates as $title => $slug ) : ?>
                      <option value="<?php echo $slug ?>" <?php echo $config['templates']['edit_page'] == $slug ? 'selected="selected"' : ''; ?> ><?php echo $title; ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </li>
              <li class="feps_templates">
                <label><?php _e( 'Add Credits Page', ud_get_wpp_feps()->domain ); ?>:</label>
                <select name="wpp_settings[configuration][feps][templates][add_credits_page]">
                  <option value="" >-</option>
                  <?php if( !empty( $templates ) ) : ?>
                    <?php foreach ( $templates as $title => $slug ) : ?>
                      <option value="<?php echo $slug ?>" <?php echo $config['templates']['add_credits_page'] == $slug ? 'selected="selected"' : ''; ?> ><?php echo $title; ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </li>
              <li class="feps_title"><?php _e( 'Links', ud_get_wpp_feps()->domain ); ?></li>
              <li><label><?php printf( __( 'Overview Page ( All %s )', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' ) ); ?>:</label><input type="text" class="readonly" readonly="readonly" value="<?php echo WPP_F::base_url( FEPS_VIEW_PAGE, "status=all" ); ?>" /></li>
              <li><label><?php printf( __( 'Overview Page ( Published %s )', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' ) ); ?>:</label><input type="text" class="readonly" readonly="readonly" value="<?php echo WPP_F::base_url( FEPS_VIEW_PAGE, "status=publish" ); ?>" /></li>
              <li><label><?php printf( __( 'Overview Page ( Pending %s )', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' ) ); ?>:</label><input type="text" class="readonly" readonly="readonly" value="<?php echo WPP_F::base_url( FEPS_VIEW_PAGE, "status=pending" ); ?>" /></li>
              <?php if ( class_exists( 'wpi_spc' ) ) : ?>
              <li><label><?php _e( 'Add Credits Page', ud_get_wpp_feps()->domain ); ?>:</label><input type="text" class="readonly" readonly="readonly" value="<?php echo WPP_F::base_url( FEPS_SPC_PAGE ); ?>" /></li>
              <?php endif; ?>
            </ul>
          </td>
        </tr>
        <tr>
          <th><?php _e('Administration',ud_get_wpp_feps()->domain); ?></th>
          <td>
            <ul>
              <li><?php
              echo WPP_F::checkbox( array(
                'name' => 'wpp_settings[configuration][feps][enable_column_feps]',
                'label' => sprintf( __("Enable Column <b>FEPS Details</b> on <a href='%s'>All %s</a> page.", ud_get_wpp_feps()->domain), admin_url( 'edit.php?post_type=property&page=all_properties' ), WPP_F::property_label( 'plural' ) ),
              ), $config['enable_column_feps']);
              ?></li>
            </ul>
          </td>
        </tr>
        <tr>
          <th><?php _e('Notifications',ud_get_wpp_feps()->domain); ?></th>
          <td>
            <?php printf( __( 'All FEPS notifications can be customized via <a href="%s">WP-CRM</a> plugin. For more details, look through <b>FEPS -> Integration with other UsabilityDynamics plugins</b> section in Help tab above.', ud_get_wpp_feps()->domain ), 'https://wp-invoice.github.io/' ); ?>
          </td>
        </tr>
      </tbody>
    </table>
    <?php
  }

  /**
   * Adds contextual help to Property Forms page
   *
   * @author korotkov@ud
   * @author peshkov@UD
   */
  static public function contextual_help_property_forms() {

    $contextual_help = array();

    $tabs = array(
      'general' => __( 'General Information', ud_get_wpp_feps()->domain ),
      'name' => __( 'Form Name', ud_get_wpp_feps()->domain ),
      'shortcode' => __( 'Shortcode', ud_get_wpp_feps()->domain ),
      'options' => __( 'General Options', ud_get_wpp_feps()->domain ),
      'plans' => __( 'Subscriptions Plans', ud_get_wpp_feps()->domain ),
      'attributes' => __( 'Propery Attributes', ud_get_wpp_feps()->domain  ),
    );

    foreach( $tabs as $tab ) {
      $contextual_help[ $tab ] = array();
    }

    $contextual_help[ $tabs['general'] ][] = '<h3>' . __( 'Front End Property Submissions (FEPS)', ud_get_wpp_feps()->domain ) .'</h3>';
    $contextual_help[ $tabs['general'] ][] = '<p><i>' . sprintf( __( 'Note, that <a href="%s">Settings page</a> also contains helpfull information about FEPS premium feature functionality. So if the current \'Help\' does not contain enough information to help you, we suggest to proceed there for looking through FEPS tab and \'Help\' information.', ud_get_wpp_feps()->domain ), admin_url( 'edit.php?post_type=property&page=property_settings' ) ) . '</i></p>';
    $contextual_help[ $tabs['general'] ][] = '<p>' . __( 'Front End Property Submission (FEPS) lets you create forms and display them on the front-end of the website. The forms may be used by visitors to submit properties for free or for credits.', ud_get_wpp_feps()->domain ) .'</p>';
    $contextual_help[ $tabs['general'] ][] = '<p>' . sprintf( __( 'If you want to display a %1$s submission form for homes in a certain neighborhood, you can place the FEPS form into the content of the parent (neighborhood) %1$s like so', ud_get_wpp_feps()->domain ), WPP_F::property_label() ) .':</p>';
    $contextual_help[ $tabs['general'] ][] = '<p><b>[wpp_feps_form form=name_of_form detect_parent=true map_height=300]</b></p>';
    $contextual_help[ $tabs['general'] ][] = '<p>' . sprintf( __( 'On a pending %s a body class is added for styling, which is: <b>%s</b>', ud_get_wpp_feps()->domain ), WPP_F::property_label(), 'feps-pending  wpp_[post_status]' ) . '<br/>';
    $contextual_help[ $tabs['general'] ][] = sprintf( __( 'Pending pages use the same front-end templates as regular %s, and will load conditional %s-type templates if they exist.  Below is the the load order of templates based on specificity:', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' ), WPP_F::property_label() ) .'</p>';
    $contextual_help[ $tabs['general'] ][] = '<ul>';
    $contextual_help[ $tabs['general'] ][] = '<li>your_theme/property-pending.php</li>';
    $contextual_help[ $tabs['general'] ][] = '<li>your_theme/property-pending-[property-type].php</li>';
    $contextual_help[ $tabs['general'] ][] = '<li>your_theme/property-[property-type].php</li>';
    $contextual_help[ $tabs['general'] ][] = '<li>your_theme/property.php</li>';
    $contextual_help[ $tabs['general'] ][] = '</ul>';
    $contextual_help[ $tabs['general'] ][] = '<p>' . sprintf( __( 'So if your %1$s type is called "<b>%2$s</b>", then the %1$s type slug is <b>%3$s</b>, and the template would be: <b>%4$s</b>', ud_get_wpp_feps()->domain ), WPP_F::property_label(), 'Single Family Home', 'single_familly_home', 'property-single-family-home.php' ) . '<br/>';

    $contextual_help[ $tabs['name'] ][] = '<h3>' . $tabs['name'] .'</h3>';
    $contextual_help[ $tabs['name'] ][] = '<p>' . __( 'Form\'s name must be unique. Form slug, which is used for setting shortcode ( attribute "form" ) is generated using form name. So, be carefull with changing name of already existing form!', ud_get_wpp_feps()->domain ) . '</p>';

    $contextual_help[ $tabs['shortcode'] ][] = '<h3>' . $tabs['shortcode'] .'</h3>';
    $contextual_help[ $tabs['shortcode'] ][] = '<p>' . sprintf( __( 'Shortcode [wpp_feps_form form="{form_slug}"] renders passed form on page. You can add it to any content including widget areas on your site. But we strongly recommend to add the current shortcode only to page\'s ( pages, posts, %s ) content to prevent different issues.', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' ) ) . '</p>';
    $contextual_help[ $tabs['shortcode'] ][] = '<h4>' . __( 'Available shortcode attributes', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tabs['shortcode'] ][] = '<ul>';
    $contextual_help[ $tabs['shortcode'] ][] = '<li><b>form</b>: ' . __( 'Required attribute. The slug of the FEPS form, this is required to identify which form must be displayed.', ud_get_wpp_feps()->domain ) . '</li>';
    $contextual_help[ $tabs['shortcode'] ][] = '<li><b>property_id</b>: ' . sprintf( __( 'Default value is not set. Use to set the parent ID of all the submitted %1$s. This works well if you want to group all the submitted %1$s under a parent. For example, if you want to allow visitors to submit condos that below to a certain neighborhood, you would create the neighborhood %1$s, then place FEPS forms into their content.', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' )) . '</li>';
    $contextual_help[ $tabs['shortcode'] ][] = '<li><b>detect_parent</b>: ' . sprintf( __( 'Available values "true" and "false". Default value is "true". If set to "true", the form will automatically detect if it is displayed on a %1$s page, and use the current %1$s as parent for newly added %2$s. If parent_id is set, detect parent does not do anything.', ud_get_wpp_feps()->domain ), WPP_F::property_label(), WPP_F::property_label( 'plural' ) ) . '</li>';
    $contextual_help[ $tabs['shortcode'] ][] = '<li><b>map_height</b>: ' . __( 'Default value is "450px". Sets the height of the address map, in pixels.', ud_get_wpp_feps()->domain ) . '</li>';
    $contextual_help[ $tabs['shortcode'] ][] = '<li><b>not_found_text</b>: ' . __( 'Default value is "Requested FEPS form not found". This is the text to display when the form is not found, this is only needed if a form has been deleted, but shortcodes still reference it throughout your site.', ud_get_wpp_feps()->domain ) . '</li>';
    $contextual_help[ $tabs['shortcode'] ][] = '</ul>';

    $contextual_help[ $tabs['options'] ][] = '<h3>' . $tabs['options'] .'</h3>';
    $contextual_help[ $tabs['options'] ][] = '<p>'.sprintf( __('<h4>Sponsored Listing:</h4>It enables billable %s submissions.<br/><a href="%s">WP-Invoice</a> plugin and <a href="%s">Single Page Checkout</a> plugin must be installed to enable this option.', ud_get_wpp_feps()->domain), WPP_F::property_label(), 'https://wp-invoice.github.io/', 'https://wp-invoice.github.io/addons/spc/' ).'</p>';
    $contextual_help[ $tabs['options'] ][] = '<p>'.sprintf(__('<h4>%1$s Status:</h4>Status to assign to new %2$s after they are submitted.<br /><i>Note, the current option is disabled for sponsored listings. Sponsored Submission will be automatically published after successfull payment. In other case, it has pending status.</i>', ud_get_wpp_feps()->domain), WPP_F::property_label(), WPP_F::property_label( 'plural' ) ).'</p>';
    $contextual_help[ $tabs['options'] ][] = '<p>'.sprintf(__('<h4>%1$s Type:</h4>Default %1$s type of submitted %2$s.', ud_get_wpp_feps()->domain), WPP_F::property_label(), WPP_F::property_label( 'plural' ) ).'</p>';
    $contextual_help[ $tabs['options'] ][] = '<p>'.sprintf(__('<h4>Automatically remove FEPS of the current Form with \'Pending\' status:</h4>Set a period of time in which Pending %1$s will be removed. Set 0 if you don\'t want Pending %1$s to be removed.', ud_get_wpp_feps()->domain), WPP_F::property_label() ).'</p>';
    $contextual_help[ $tabs['options'] ][] = '<p>'.__( '<h4>Preview Thumbnail Size:</h4>The size of the image to be used to display the thumbnail of an uploaded image.', ud_get_wpp_feps()->domain).'</p>';
    $contextual_help[ $tabs['options'] ][] = '<p>'.__( '<h4>Image Upload Limit:</h4>The maximum number of images that can be uploaded per submission.<br /><i>Note, the current option is disabled for sponsored listings. Images limit for sponsored listings must be set for every Subscription plan separately.</i>', ud_get_wpp_feps()->domain).'</p>';
    $contextual_help[ $tabs['options'] ][] = '<p>'.sprintf( __( '<h4>New User Role:</h4>Role to assign to users submitting %1$s if they do not already have an account.', ud_get_wpp_feps()->domain), WPP_F::property_label() ).'</p>';
    $contextual_help[ $tabs['options'] ][] = '<p>'.sprintf( __( '<h4>Allow user to edit and remove his FEPS:</h4>Whether or not to allow user to edit and delete their %1$s submitted by FEPS.', ud_get_wpp_feps()->domain), WPP_F::property_label() ).'</p>';
    $contextual_help[ $tabs['options'] ][] = '<p>'.__( '<h4>Disable new user account creation notification:</h4>Whether or not to send new user notifications.<br /><i>Note, the current option is disabled for sponsored listings.</i>', ud_get_wpp_feps()->domain).'</p>';

    $contextual_help[ $tabs['plans'] ][] = '<h3>' . $tabs['plans'] .'</h3>';
    $contextual_help[ $tabs['plans'] ][] = '<p>' . sprintf( __( 'The current functionality is available only for <b>sponsored listings</b> ( see "%s" tab ) when <a href="%s">WP-Invoice</a> plugin and <a href="%s">Single Page Checkout (SPC)</a> plugin are installed and activated.', ud_get_wpp_feps()->domain ), $tabs['options'], 'https://wp-invoice.github.io/', 'https://wp-invoice.github.io/addons/spc/' ) . '</p>';
    $contextual_help[ $tabs['plans'] ][] = '<h4>' . __( 'Name', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tabs['plans'] ][] = '<p>' . __( 'Just a name of Subscription plan.', ud_get_wpp_feps()->domain ) .'</p>';
    $contextual_help[ $tabs['plans'] ][] = '<h4>' . __( 'Price', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tabs['plans'] ][] = '<p>' . __( 'Currency is set via WP-Invoice settings. The exchange rate is 1 chosen currency = 1 credit.', ud_get_wpp_feps()->domain ) .'</p>';
    $contextual_help[ $tabs['plans'] ][] = '<h4>' . __( 'Duration', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tabs['plans'] ][] = '<p>' . sprintf( __( 'The published period. The expired date is based on this option and will be set after successfully payment. When expiry date is over, status of %s will be automatically set as "%s".', ud_get_wpp_feps()->domain ), WPP_F::property_label(), 'Pending' ) .'</p>';
    $contextual_help[ $tabs['plans'] ][] = '<h4>' . __( 'Is Featured', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tabs['plans'] ][] = '<p>' . sprintf( __( '%s will be featured if option is checked.', ud_get_wpp_feps()->domain ), WPP_F::property_label() ) .'</p>';
    $contextual_help[ $tabs['plans'] ][] = '<h4>' . __( 'Images Limit', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tabs['plans'] ][] = '<p>' . sprintf( __( 'To enable this option, Image Upload must be set in <b>%s</b>. The amount of images which can be uploaded to the %s with the current subscription plan. Note: limit must not be less than 1 or empty.', ud_get_wpp_feps()->domain ), $tabs['attributes'], WPP_F::property_label() ) .'</p>';
    $contextual_help[ $tabs['plans'] ][] = '<h4>' . __( 'Description', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tabs['plans'] ][] = '<p>' . __( 'Optional. Some helpfull information about subscription plan, advertisement, offer or whatever else can be added here.', ud_get_wpp_feps()->domain ) .'</p>';

    $contextual_help[ $tabs['attributes'] ][] = '<h3>' . $tabs['attributes'] .'</h3>';
    $contextual_help[ $tabs['attributes'] ][] = '<p>' . sprintf( __( 'The list of %s attributes fields which will be shown in FEPS form. User will be able to add/edit only the chosen attributes.', ud_get_wpp_feps()->domain ), WPP_F::property_label() ) . '</p>';
    $contextual_help[ $tabs['attributes'] ][] = '<p>' . sprintf( __('Note, <b>%1$s type</b> can be set only in %3$s. So all %2$s added via the same form will have the same %1$s type. ', ud_get_wpp_feps()->domain ), WPP_F::property_label(), WPP_F::property_label( 'plural' ), $tabs['options'] ) . '</p>';

    $contextual_help[ $tabs['attributes'] ][] = '<h4>Auto Generate Title:</h4>';
    $contextual_help[ $tabs['attributes'] ][] = '<p>' . __( "Title can be auto generated/concatenated from other form's attributes. Ex. <b>[title] in [location]</b> or <b>[property_type] in [location]</b>.", ud_get_wpp_feps()->domain ) . '</p>';
    do_action('wpp_contextual_help', array( 'contextual_help' => $contextual_help ) );

  }

  /**
   * Adds contextual help to WP-Property Settings page
   *
   * @author peshkov@UD
   */
  static public function contextual_help_settings( $contextual_help ) {

    $tab = __( 'FEPS', ud_get_wpp_feps()->domain );
    if( !isset( $contextual_help[ $tab ] ) ) {
      $contextual_help[ $tab ] = array();
    }

    $contextual_help[ $tab ][] = '<h3>' . sprintf( __( 'Front End %s Submissions (FEPS)', ud_get_wpp_feps()->domain ), WPP_F::property_label() ) .'</h3>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'Front End %1$s Submission (FEPS) allows you to create multiple forms using the interface found under %2$s -> %1$s Forms. Multiple forms can be used to setup targeted pages that limit the number of images for the form, direct all submissions into a certain %1$s type, or %1$s status.', ud_get_wpp_feps()->domain ), WPP_F::property_label(), WPP_F::property_label( 'plural' ) );
    $contextual_help[ $tab ][] = sprintf( __( 'It also allows to create forms for sponsored listings if you have installed <a href="%s">WP-Invoice</a> plugin and <a href="%s">Single Page Checkout (SPC)</a> plugin.', ud_get_wpp_feps()->domain ), 'https://wp-invoice.github.io/', 'https://wp-invoice.github.io/addons/spc/' ) . '</p>';
    $contextual_help[ $tab ][] = '<br/>';
    $contextual_help[ $tab ][] = '<h4>' . __( 'Property Forms', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'Detailed information can be found on <a href="%s">%s Forms</a> page in Help tab.', ud_get_wpp_feps()->domain ), admin_url( 'edit.php?post_type=property&page=page_feps' ), WPP_F::property_label() ) .'</p>';
    $contextual_help[ $tab ][] = '<br/>';
    $contextual_help[ $tab ][] = '<h4>' . __( 'FEPS Menu Widget/Shortcode', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tab ][] = '<p>' . __( 'It\'s a block menu which contains some specific information and FEPS tools for management.', ud_get_wpp_feps()->domain ) .'</p>';
    $contextual_help[ $tab ][] = '<p>' . __( 'Note, menu is shown only for logged in users.', ud_get_wpp_feps()->domain ) . '</p>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'Menu can be added to frontend pages via <b>FEPS Menu</b> widget or <b>%s</b> shortcode', ud_get_wpp_feps()->domain ), '[wpp_feps_menu]' ) .'</p>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( '<b>%s</b> shortcode has the following available attributes:', ud_get_wpp_feps()->domain ), '[wpp_feps_form]' ) .'</p>';
    $contextual_help[ $tab ][] = '<ul>';
    $contextual_help[ $tab ][] = '<li><b>title</b> - ' . sprintf( __( 'Shows title of menu if attribute is passed. Default is empty. Example, <b>%s</b>.', ud_get_wpp_feps()->domain ), '[wpp_feps_menu title="My Submissions"]' ) . '</li>';
    $contextual_help[ $tab ][] = '<li><b>filters</b> - ' . sprintf( __( 'Shows %s overview links by %s statuses. Available values are <b>true</b>|<b>false</b>. Default is false. Example, <b>%s</b>".', ud_get_wpp_feps()->domain ), WPP_F::property_label(), WPP_F::property_label( 'plural' ), '[wpp_feps_menu filters="true]' ) . '</li>';
    $contextual_help[ $tab ][] = '<li><b>form_page</b> - ' . sprintf( __( 'If set, shows the link to FEPS %s Form page. Default is empty. Example, <b>%s</b>".', ud_get_wpp_feps()->domain ), WPP_F::property_label(), '[wpp_feps_menu title="http::/example.com/my_feps_' . WPP_F::property_label() . '_form_page]' ) . '</li>';
    $contextual_help[ $tab ][] = '<li><b>show_balance</b> - ' . sprintf( __( 'Shows user\'s current credits balance. Available values are <b>true</b>|<b>false</b>. Default is true. Example, <b>%s</b>.', ud_get_wpp_feps()->domain ), '[wpp_feps_menu show_balance="false"]' );
    $contextual_help[ $tab ][] = '<br/><b><i>' . __( 'Note, to use this attribute, WP-Invoice plugin and WP-Invoice Single Page Checkout plugin must be installed.', ud_get_wpp_feps()->domain ). '</i></b></li>';
    $contextual_help[ $tab ][] = '<li><b>show_spc_link</b> - ' . sprintf( __( 'Shows the link to Add Credits page. Available values are <b>true</b>|<b>false</b>. Default is true. Example, <b>%s</b>.', ud_get_wpp_feps()->domain ), '[wpp_feps_menu show_spc_link="false"]' );
    $contextual_help[ $tab ][] = '<br/><b><i>' . __( 'Note, to use this attribute, WP-Invoice plugin and WP-Invoice Single Page Checkout plugin must be installed.', ud_get_wpp_feps()->domain ). '</i></b></li>';

    $contextual_help[ $tab ][] = '<li><b>show_login_form</b> - ' . sprintf( __("Will show login form instead of FEPS Menu if user is not logged in on your site. Available values are <b>true</b>|<b>false</b>."), '[wpp_feps_menu show_login_form="true"]' ) . '</li>';
    $contextual_help[ $tab ][] = '<li><b>show_reg_link</b> - ' . sprintf( __("Will show registration link under the login form when user is not logged in on your site. Available values are <b>true</b>|<b>false</b>."), '[wpp_feps_menu show_reg_link="true"]' ) . '</li>';
    $contextual_help[ $tab ][] = '<li><b>show_remember_link</b> - ' . sprintf( __("Will show Restore password link under the login form when user is not logged in on your site. Available values are <b>true</b>|<b>false</b>."), '[wpp_feps_menu show_remember_link="true"]' ) . '</li>';

    $contextual_help[ $tab ][] = '</ul>';
    $contextual_help[ $tab ][] = '<br/>';
    $contextual_help[ $tab ][] = '<h4>' . __( 'FEPS Information Widget/Shortcode', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tab ][] = '<p>' . __( 'It\'s a block which contains some specific information about current property.', ud_get_wpp_feps()->domain ) .'</p>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'It\'s only shown on Edit FEPS %s page for logged in users', ud_get_wpp_feps()->domain ), WPP_F::property_label() ) .'</p>';
    $contextual_help[ $tab ][] = '<br/>';
    $contextual_help[ $tab ][] = '<h4>' . __( 'Specific Pages', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'FEPS premium feature adds specific <b>frontend</b> user\'s pages which are created on a fly, so they are not available in your existing <a href="%s">Pages</a>.', ud_get_wpp_feps()->domain ), admin_url( 'edit.php?post_type=page' ) ) .'</p>';
    $contextual_help[ $tab ][] = '<p>' . __( 'The links to these pages are added to FEPS Menu. Note, these pages are available only for logged in users.', ud_get_wpp_feps()->domain ) . ':<ul>';
    $contextual_help[ $tab ][] = __( 'The specific FEPS pages are the following', ud_get_wpp_feps()->domain ) . ':<ul>';
    $contextual_help[ $tab ][] = '<li><b>' . __( 'Overview Page', ud_get_wpp_feps()->domain ) . '</b> - ' . sprintf( __( 'The current one shows the list of already submitted via FEPS forms %1$s by current logged in user. If %2$s Form, used for submission, allows to edit %2$s, it also shows the links "Edit" and "Remove" for the current %2$s.', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' ), WPP_F::property_label() );
    $contextual_help[ $tab ][] = sprintf( __( 'Also, FEPS allows to show %s on the current page by statuses. See <b>FEPS Menu</b> and <b>Links</b> section in the current Help tab for details.', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' ) ) . '</li>';
    $contextual_help[ $tab ][] = '<li><b>' . __( 'Add Credits Page', ud_get_wpp_feps()->domain ) . '</b> - ' . sprintf( __( 'On the current page logged in user can add credits to their FEPS balance. The current page is available only if <a href="%s">WP-Invoice</a> plugin and <a href="%s">WP-Invoice Single Page Checkout</a> plugin are installed. <b><i>Note, user\'s credit balance can be manually edited by administrator via Edit User page (profile)</i></b>.', ud_get_wpp_feps()->domain ), 'https://wp-invoice.github.io/', 'https://wp-invoice.github.io/addons/spc/' ) . '</li>';
    $contextual_help[ $tab ][] = '<li><b>' . sprintf( __( 'Edit %s Page', ud_get_wpp_feps()->domain ), WPP_F::property_label() ) . '</b> - ' . sprintf( __( 'Here user can edit the %1$s added via FEPS %1$s form. There are different links for every %1$s, so the link can not be provided. Note, the form, used for submission, must exist and must be enabled option which allows to edit and remove %2$s. ', ud_get_wpp_feps()->domain ), WPP_F::property_label(), WPP_F::property_label( 'plural' ) ) . '</li>';
    $contextual_help[ $tab ][] = '<h5>' . __( 'Templates', ud_get_wpp_feps()->domain ) .'</h5>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'You can set custom available template for FEPS pages. More information you can find <a href="%s">here</a>.', ud_get_wpp_feps()->domain ), 'http://codex.wordpress.org/Pages#Page_Templates' ) . '</p>';
    $contextual_help[ $tab ][] = '<h5>' . __( 'Links', ud_get_wpp_feps()->domain ) .'</h5>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'You can use given links to FEPS pages, for example, for adding custom menu in <a href="%s">Menus</a> or wherever else.', ud_get_wpp_feps()->domain ), admin_url( 'nav-menus.php' ) ) . '</p>';
    $contextual_help[ $tab ][] = '<p>' . __( 'Note, that FEPS pages are available only for logged in users. In other case user will be redirected to home page.', ud_get_wpp_feps()->domain ) .'</p>';
    $contextual_help[ $tab ][] = '<br/>';
    $contextual_help[ $tab ][] = '<h4>' . __( 'Administration', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'Specific column "FEPS details" can be added to the table on <a href="%s">All %s </a>page.', ud_get_wpp_feps()->domain ), admin_url( 'edit.php?post_type=property&page=all_properties' ), WPP_F::property_label( 'plural' ) );
    $contextual_help[ $tab ][] = __( 'To add this column, you should check the option. Column contains detailed FEPS information about the current property, which can be helpful for management.', ud_get_wpp_feps()->domain ) . '</p>';
    $contextual_help[ $tab ][] = '<p>' . __( 'Note, if the option is enabled but you don\'t see the column, you should check "<i>Screen Option</i>" settings.', ud_get_wpp_feps()->domain ) . '</p>';
    $contextual_help[ $tab ][] = '<br/>';
    $contextual_help[ $tab ][] = '<h4>' . __( 'Integration with other UsabilityDynamics plugins', ud_get_wpp_feps()->domain ) .'</h4>';
    $contextual_help[ $tab ][] = sprintf( __( 'Functionality of FEPS premium feature can increased via other following wordpress plugins developed by <a href="%s">UsabilityDynamics, Inc</a>:', ud_get_wpp_feps()->domain ), 'https://udx.io/' ) .'<ul>';
    $contextual_help[ $tab ][] = '<li>' . sprintf( __( '<a href="%s">WP-Invoice</a>', ud_get_wpp_feps()->domain ), 'https://wp-invoice.github.io/' ) .'</li>';
    $contextual_help[ $tab ][] = '<li>' . sprintf( __( '<a href="%s">WP-CRM</a>', ud_get_wpp_feps()->domain ), 'https://wp-crm.github.io/' ) .'</li></ul>';
    $contextual_help[ $tab ][] = '<h5>' . __( 'WP-Invoice', ud_get_wpp_feps()->domain ) .'</h5>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'To add aditional functionality to FEPS premium feature, <a href="%s">WP-Invoice Single Page Checkout</a> plugin must be installed and activated.', ud_get_wpp_feps()->domain ), 'https://wp-invoice.github.io/addons/spc/' ) . '</p>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'It allows you to use FEPS %s Forms as billable service where users have to pay for publishing %s on your site.', ud_get_wpp_feps()->domain ), WPP_F::property_label(), WPP_F::property_label( 'plural' ) ) . '</p>';
    $contextual_help[ $tab ][] = '<p>' . sprintf( __( 'Billable service is called Sponsored listings. Sponsored listings allows you to set different Subscription plans where you are able to set expired dates and images limit for submitted %s.', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' ), WPP_F::property_label() ) . '</p>';
    $contextual_help[ $tab ][] = '<h5>' . __( 'WP-CRM', ud_get_wpp_feps()->domain ) .'</h5>';
    $contextual_help[ $tab ][] = '<p>' . __( 'If WP-Invoice plugin is installed you are able to see all user\'s completed transactions on CRM User Edit page.' ,ud_get_wpp_feps()->domain ) . '</p>';
    $contextual_help[ $tab ][] = '<p>' . __( 'Also, you have ability to manage FEPS notifications using WP-CRM plugin. Go to CRM -> Settings -> Notifications.', ud_get_wpp_feps()->domain ) . '</p>';
    $contextual_help[ $tab ][] = sprintf(__('For your notifications on any of this Trigger actions - <i>FEPS: Pending Approval</i>, <i>FEPS: %s Submitted</i>, <i>FEPS: User Account Approved</i>, <i>FEPS: User Account Created</i> &mdash; you can use the following shortcodes', ud_get_wpp_feps()->domain ), WPP_F::property_label() ) . ':';
    $contextual_help[ $tab ][] = '<ul><li>[notification_type]</li><li>[user_email]</li><li>[display_name]</li><li>[site_url]</li></ul>';
    $contextual_help[ $tab ][] = '<br/>' . __( 'For Trigger action <i>FEPS: Pending Approval</i> - you can use the following shortcodes', ud_get_wpp_feps()->domain ) . ':';
    $contextual_help[ $tab ][] = '<ul><li>[property_link]</li><li>[property_title]</li><li>[status]</li></ul>';
    $contextual_help[ $tab ][] = '<br/>' . sprintf( __( 'For Trigger action <i>FEPS: %s Submitted</i> - you can use the following shortcodes', ud_get_wpp_feps()->domain ), WPP_F::property_label() ) . ':';
    $contextual_help[ $tab ][] = '<ul><li>[property_title]</li><li>[pending_url]</li><li>[status]</li></ul>';
    $contextual_help[ $tab ][] = '<br/>' . sprintf( __( 'For Trigger action <i>FEPS: %s Submitted (Notify Administrator)</i> - you can use the following shortcodes', ud_get_wpp_feps()->domain ), WPP_F::property_label() ) . ':';
    $contextual_help[ $tab ][] = '<ul><li>[property_link]</li><li>[property_title]</li><li>[user_email]</li></ul>';
    $contextual_help[ $tab ][] = '<br/>' . __( 'For Trigger action <i>FEPS: User Account Approved</i> - you can use the following shortcodes', ud_get_wpp_feps()->domain ) . ':';
    $contextual_help[ $tab ][] = '<ul><li>[user_login]</li><li>[user_password]</li><li>[system_message]</li></ul>';
    $contextual_help[ $tab ][] = '<br/>' . __( 'For Trigger action <i>FEPS: User Account Created</i> - you can use this shortcodes', ud_get_wpp_feps()->domain ) . ':';
    $contextual_help[ $tab ][] = '<ul><li>[user_login]</li><li>[user_password]</li><li>[system_message]</li><li>[property_link]</li><li>[property_title]</li><li>[activation_link]</li><li>[status]</li></ul>';

    return $contextual_help;
  }

  /**
   * Ajax action to delete FEPS images
   * @author korotkov@ud
   */
  static public function ajax_feps_image_delete() {
    //** Get data from request */
    $session  = (int)$_REQUEST['session'];
    $filename = $_REQUEST['filename'];

    //** File location */
    $upload_dir = wp_upload_dir();
    $feps_session_files_dir = $upload_dir['basedir'] . '/feps_files/' . (string)$session . '/';

    //** Try to delete file */
    if ( unlink( $feps_session_files_dir.$filename ) )
      die( json_encode( array('success' => 1) ) );

    //** success - 0 if not deleted */
    die( json_encode( array('success' => 0) ) );
  }

  /**
   * Initialize FEPS schedule events
   *
   * @author korotkov@ud
   */
  static public function feps_schedule_events() {

    if ( !wp_next_scheduled( 'delete_feps_files' ) ) {
      wp_schedule_event( time(), 'daily', 'delete_feps_files' );
    }
    add_action( 'delete_feps_files', array( 'class_wpp_feps', 'delete_feps_files' ) );

    //** Removes old FEPS properties with 'Pending' status */
    if ( !wp_next_scheduled( 'feps_time_is_expired' ) ) {
      wp_schedule_event( time(), 'daily', 'feps_time_is_expired' );
    }
    add_action( 'feps_time_is_expired', array( 'class_wpp_feps', 'feps_time_is_expired' ) );

  }

  /**
   * Delete feps files if they are older then $seconds_old
   *
   * @param int $seconds_old
   * @return bool
   */
  static public function delete_feps_files( $seconds_old = 3600 ) {

    /** FEPS temp files dir */
    $uploads = wp_upload_dir();
    $feps_files_dir = $uploads['basedir'].'/feps_files/';

    /** Scan dir if it exists */
    if ( file_exists( $feps_files_dir ) && is_dir( $feps_files_dir ) ) {

     if ( $handle = opendir( $feps_files_dir ) ) {

       while (false !== ($file = readdir($handle))) {
         if ($file != "." && $file != "..") {

           $session_dir = $feps_files_dir.$file;
           /** If dir was modified more then 1 hour ago */
           if ( (int) (time() - filemtime( $session_dir )) > $seconds_old && is_dir( $session_dir ) ) {
             /** Delete it with files in it */
             WPP_F::delete_directory( $session_dir );
           }

         }
       }

       closedir($handle);
       return true;
     }

    } else {
     return false;
    }

  }

  /**
   * Renders FEPS details on Edit Property page
   *
   * @author peshkov@UD
   * @since 2.0
   */
  static public function post_property_form_information() {
    global $post;

    //** Determine if the current post is property */
    if( $post->post_type != "property" ) {
      return null;
    }

    //** Determine if the current post belongs to FEPS */
    $form = self::get_form_by_post( $post->ID );
    if( !$form ) {
      return null;
    }

    //** Determine if user has permissions for FEPS management */
    if( !current_user_can( self::$capability ) ) {
      return null;
    }

    $user = get_userdata( $post->post_author );
    $credits = get_the_author_meta( FEPS_USER_CREDITS, $user->ID );

    $expired_time = get_post_meta( $post->ID, FEPS_META_EXPIRED, true );
    $expired_time = !empty( $expired_time ) ? WPP_F::nice_time( $expired_time, array('format'=>'date') ) : __( 'Not set', ud_get_wpp_feps()->domain );

    $sponsored = !empty( $form[ 'feps_credits' ] ) && $form[ 'feps_credits' ] == 'true' ? true : false;

    $plan = get_post_meta( $post->ID, FEPS_META_PLAN, true );
    if( !empty( $plan ) && is_array( $plan ) ) {
      $plan_details = sprintf( __( 'Price: <b>%01.2f</b> credits', ud_get_wpp_feps()->domain ), $plan[ 'price' ] );
      $plan_details .= ', ' . sprintf( __( 'Duration', ud_get_wpp_feps()->domain ) . ': %d %s', $plan['duration']['value'], _n( $plan['duration']['interval'], $plan['duration']['interval'].'s', $plan['duration']['value'], ud_get_wpp_feps()->domain ) );
      $plan_details .= ', ' . __( 'Images Limit', ud_get_wpp_feps()->domain ) . ': ' . ( !empty( $plan['images_limit'] ) ? $plan['images_limit'] : '1' );
      $plan_details .= ', ' . __( 'Featured', ud_get_wpp_feps()->domain ) . ': ' . ( !empty( $plan['is_featured'] ) && $plan['is_featured'] == 'true' ? __( 'Yes', ud_get_wpp_feps()->domain ) : __( 'No', ud_get_wpp_feps()->domain ) );
    }

    ?>
    <div id="wpp_post_property_form_information" class="postbox wpp_list">
      <div class="handlediv" title="<?php _e( 'Click to toggle', ud_get_wpp_feps()->domain ); ?>"><br/></div>
      <h3 class="hndle"><span><?php _e( 'Front End Property Submission (FEPS) Details', ud_get_wpp_feps()->domain ); ?></span></h3>
      <div class="inside">
        <table class="widefat">
          <tbody>
            <tr class="wpp_attribute_row_parent wpp_attribute_row">
              <th><?php _e( 'Property Form', ud_get_wpp_feps()->domain ); ?></th>
              <td><a target="_blank" href="<?php echo admin_url( 'edit.php?post_type=property&page=page_feps' ); ?>"><?php echo $form[ 'title' ]; ?></a></td>
            </tr>
            <tr class="wpp_attribute_row_parent wpp_attribute_row">
              <th><?php _e( 'Author', ud_get_wpp_feps()->domain ); ?></th>
              <td>
              <?php if ( current_user_can( 'edit_users', $user->ID ) ) : ?>
                <span class="value"><a target="_blank" href="<?php echo get_edit_user_link( $user->ID ); ?>"><?php echo $user->data->display_name; ?></a></span>
                <span class="description">( <?php printf( __( 'The user has <b>%01.2f</b> credits on the balance', ud_get_wpp_feps()->domain ), !empty( $credits ) ? $credits : 0 ); ?> )</span>
              <?php else : ?>
                <span class="value"><?php echo $user->data->display_name; ?></span>
              <?php endif; ?>
              </td>
            </tr>
            <tr class="wpp_attribute_row_parent wpp_attribute_row">
              <th><?php _e( 'Sponsored', ud_get_wpp_feps()->domain ); ?></th>
              <td><?php $sponsored ? _e( 'Yes', ud_get_wpp_feps()->domain ) : _e( 'No', ud_get_wpp_feps()->domain ) ; ?></td>
            </tr>
            <tr class="wpp_attribute_row_parent wpp_attribute_row">
              <th><?php _e( 'Expired date', ud_get_wpp_feps()->domain ); ?></th>
              <td><?php echo $expired_time; ?></td>
            </tr>
            <?php if( $sponsored ) : ?>
            <tr class="wpp_attribute_row_parent wpp_attribute_row">
              <th><?php _e( 'Subscription Plan', ud_get_wpp_feps()->domain ); ?></th>
              <td>
                <?php if( !empty( $plan ) && is_array( $plan ) ) : ?>
                  <?php echo $plan[ 'name' ]; ?> <span class="description">( <?php echo $plan_details; ?> )</span>
                <?php else : ?>
                  <?php _e( 'Not set', ud_get_wpp_feps()->domain ); ?>
                <?php endif; ?>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
  }

  /**
   * Inserts  'Subscription plan' content into the "Publish" metabox on property pages
   *
   * @author peshkov@UD
   * @since 2.0
   */
  static public function post_submitbox_misc_actions() {
    global $post, $wp_properties, $wpi_settings;

    if ( $post->post_status != 'publish' && $form_id = get_post_meta( $post->ID, FEPS_META_FORM, true ) ) {

      $forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];

      if (
        !empty( $forms[$form_id] ) &&
        isset( $forms[$form_id]['feps_credits'] ) &&
        $forms[$form_id]['feps_credits'] == 'true' &&
        !empty( $forms[$form_id]['subscription_plans'] )
      ) {

        $image_field = array_filter((array)$forms[$form_id]['fields'], function($field){ return $field["attribute"]=="image_upload"; });
        $form_has_images = !empty($image_field)?true:false;

        ?>
        <div class="misc-pub-section wpp_feps_subsc_plan">
          <ul>
            <li class="wpp-block-wrap">
              <span class="wpp-block-title"><?php _e('FEPS Subscription Plan', ud_get_wpp_feps()->domain); ?></span>
              <div class="wp-tab-panel">
                <ul>
                  <?php $first = true; ?>
                  <?php foreach( $forms[$form_id]['subscription_plans'] as $plan_key => $plan_data ): ?>
                    <li class="wpp_feps_plan_row">
                      <label>
                        <h3 class="wpp_feps_subscription_plan_title"><?php echo $plan_data['name']; ?></h3>
                        <table>
                          <tr>
                            <th class="wpp_feps_subscription_plan_radio_holder">
                              <input <?php echo $first ? 'checked="checked"' : ''; ?> type="radio" name="feps_subscription_plan" value="<?php echo $plan_key; ?>" />
                            </th>
                            <td>
                              <ul class="wpp_feps_subscription_plan_info">
                                <li>
                                  <label><?php _e('Price:', ud_get_wpp_feps()->domain); ?></label>
                                  <span><?php printf( __( '<b>%01.2f</b> credits', ud_get_wpp_feps()->domain ), $plan_data['price'] ); ?></span>
                                </li>
                                <li>
                                  <label><?php _e('Duration:', ud_get_wpp_feps()->domain); ?></label>
                                  <span><?php echo $plan_data['duration']['value'].' '.$plan_data['duration']['interval'].'(s)'; ?></span>
                                </li>
                                <?php if ($form_has_images): ?>
                                <li>
                                  <label><?php _e('Images:', ud_get_wpp_feps()->domain); ?></label>
                                  <span><?php echo $plan_data['images_limit']; ?></span>
                                </li>
                                <?php endif; ?>
                              </ul>
                            </td>
                          </tr>
                        </table>
                      </label>
                    </li>
                    <?php $first = false; ?>
                  <?php endforeach; ?>
                </ul>
              </div>
            </li>
          </ul>
        </div>
        <?php
      }
    }
  }

  /**
   * Determines if live time of FEPS is expired and change FEPS status.
   * Used by WP Cron
   *
   * @author peshkov@UD
   */
  static public function feps_time_is_expired() {
    global $wpdb, $wp_properties;

    $current_time = time();
    $forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];

    //** STEP 1. Get all FEPS with pending */
    $feps = $wpdb->get_results("
      SELECT p.ID, p.post_status, pm1.meta_value AS expired_time, pm2.meta_value AS form_id, p.post_author
        FROM {$wpdb->posts} AS p
          JOIN {$wpdb->postmeta} AS pm1 ON pm1.post_id = p.ID AND pm1.meta_key = '" . FEPS_META_EXPIRED . "'
          JOIN {$wpdb->postmeta} AS pm2 ON pm2.post_id = p.ID AND pm2.meta_key = '" . FEPS_META_FORM . "'
        WHERE p.post_type = 'property'
          AND p.post_status IN ( 'pending', 'publish' )

    ", ARRAY_A);

    if( empty( $feps ) ) {
      return false;
    }

    //** STEP 2. Go through all returned FEPS and set statuses using expired time information. */
    foreach ( $feps as $r ) {
      if ( !empty( $r['expired_time'] ) && $current_time >= $r['expired_time'] ) {
        switch( $r['post_status'] ) {
          case 'pending':
            $new_status = 'trash';
            break;
          case 'publish':
            //** Maybe Renew Subscription Plan */
            if( !self::maybe_renew_subscripton_plan( $r[ 'ID' ] ) ) {
              $new_status = 'pending';
            }
            break;
        }
        if( !empty( $new_status ) ) {
          $post_id = wp_update_post( array(
            'ID' => $r['ID'],
            'post_status' => $new_status,
          ) );
        }
      }
    }

    return true;
  }
  
  /**
   * Maybe Renew Subscription Plan
   * 
   * @author peshkov@UD
   * @since 2.4
   */
  static public function maybe_renew_subscripton_plan( $post_id ) {
    $form = self::get_form_by_post( $post_id );
    $post = get_post( $post_id );
    
    //** STEP 1. Determine if the current property is sponsored and renewal of Subscription Plan is enabled */
    
    //** Be sure that it's sponsored form */
    $sponsored = !empty( $form[ 'feps_credits' ] ) && $form[ 'feps_credits' ] == 'true' ? true : false;
    if( !$sponsored ) {
      return false;
    }
    
    //** Be sure that renewal of subscription plan is enabled */
    $enabled = get_post_meta( $post->ID, FEPS_RENEW_PLAN, true );
    if( $enabled === 'true' ) {
      return false;
    }
    
    //** STEP 2. Determine if subscription plan for renewal still exists. */
    
    //** Get Subscription plan for renewal */
    $plan = get_post_meta( $post->ID, FEPS_META_PLAN, true );
    if( isset( $plan[ 'slug' ] ) && isset( $form[ 'subscription_plans' ][ $plan[ 'slug' ] ] ) ) {
      $_plan = $plan[ 'slug' ];
      $_credits = $form[ 'subscription_plans' ][ $plan[ 'slug' ] ][ 'price' ];
    } else {
      foreach( $form[ 'subscription_plans' ] as $k => $d ) {
        if( $d[ 'name' ] == $plan[ 'name' ] ) {
          $_plan = $k;
          $_credits = $form[ 'subscription_plans' ][ $k ][ 'price' ];
        }
      }
    }
    //** Property's subscription plan doesn't exist anymore. Break. */
    if( !isset( $_plan ) ) {
      return false;
    }
   
    //** STEP 3. Determine if user has enough credits on their balance to renew Subscription plan */
    $user = get_userdata( $post->post_author );
    $credits = get_the_author_meta( FEPS_USER_CREDITS, $user->ID );
    if( $credits < $_credits ) {
      return false;
    }
    
    //** STEP 4. Renew Subscription plan and Expired Time */
    $credits -= $_credits;
    update_user_meta( $user->ID, FEPS_USER_CREDITS, $credits );
    //** Update Subscription plan data */
    self::set_subscription_plan( $post->ID, $_plan );
    //** Update expired time */
    self::maybe_set_expired_time( $post->ID );
    
    //** Send notification to user */
    $subject = __( 'Renewal of Subscription Plan', ud_get_wpp_feps()->domain );
    $message = sprintf(__('Hello [display_name]%1$s%1$sPublish time for Your %2$s "[property_title]" has expired.%1$s%1$sSubscription Plan has been renewed and [credits] credits were withdrawn from your balance.%1$s%1$sClick this link to view all your [status] %3$s:%1$s[url]', ud_get_wpp_feps()->domain), PHP_EOL, WPP_F::property_label( 'singular' ), WPP_F::property_label( 'plural' ) );
    WPP_Mail::feps_post_status_updated( $post->ID, array_filter( array(
      'subject' => $subject,
      'message' => $message,
      'crm_log_message' => '',
      'data' => array_filter( array(
        'url' => WPP_F::base_url( FEPS_VIEW_PAGE, "status=publish" ),
        'status' => @$wp_post_statuses['publish']->label,
        'credits' => $_credits,
      ) ),
    ) ) );
    
    return true;
  }

  /**
   * Advanced Edit Property options
   * Enable/Disable atomatic renewal of Subscription Plan
   * 
   * @param type $property
   * @author pehkov@UD
   * @since 2.4
   */
  static public function feps_edit_property_options( $property ) {
    if( empty( $property[ 'ID' ] ) ) {
      return null;
    }
    $post_id = $property[ 'ID' ];
    
    $form = self::get_form_by_post( $post_id );
    $plan = get_post_meta( $post_id, FEPS_META_PLAN, true );
    if( empty( $form ) || empty( $plan ) ) {
      return null;
    }
    
    $renew_plan = get_post_meta( $post_id, FEPS_RENEW_PLAN, true );
    $renew_plan = !empty( $renew_plan ) && $renew_plan == 'true' ? 'checked' : '';
    
    ?>
    <li class="<?php wpp_css("feps-default-template::row-wrapper", array("wpp_feps_row_wrapper", "enable_automatic_renewal")); ?>">
      <div class="<?php wpp_css("feps-default-template::label-wrapper", array("wpp_feps_label_wrapper")); ?>">
        <label><span class="<?php wpp_css("feps-default-template::the_title","the_title"); ?>"><?php _e('Options:', ud_get_wpp_feps()->domain); ?></span></label>
      </div>
      <div class="<?php wpp_css("feps-default-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div class="<?php wpp_css("feps-default-template::input-content","wpp_feps_input_content"); ?>">
            <label><input id="renew_plan_<?php echo $post_id; ?>" type="checkbox" value="" <?php echo $renew_plan; ?> /> <?php printf( __( 'Enable automatic renewal of Subscription plan for the current %1$s when subscription time is expired.', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'singular' ) ); ?></label>
          </div>
          <div class="<?php wpp_css("feps-default-template::description_wrapper","wpp_feps_description_wrapper"); ?>">
              <div class="<?php wpp_css("feps-default-template::clear","clear"); ?>"></div>
              <span class="<?php wpp_css("feps-default-template::attribute_description_text","attribute_description_text"); ?>"><?php printf( __( 'Credits will be automatically withdrawn from your balance. Notice, automatic renewal will be done only if you have enough credits on your balance.', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'singular' ) ); ?></span>
          </div>
        </div>
      <div class="<?php wpp_css("feps-default-template::clear","clear"); ?>"></div>
      <script type="text/javascript">
        (function($){
          var el = '#renew_plan_<?php echo $post_id; ?>';
          $(el).click( function(){
            jQuery.post( '<?php echo admin_url('admin-ajax.php'); ?>', {
              'action' : 'wpp_feps_renew_plan',
              'post_id' : <?php echo $post_id; ?>,
              'value' : $(this).is(':checked')
            });
          } );
        })(jQuery);
      </script>
    </li>
    <?php
  }
  
  /**
   * Enables/disables automatic renewal of Subscription plan for passed property (ID)
   * 
   * @author peshkov@UD
   * @since 2.4
   */
  static public function ajax_set_renew_plan() {
    if( self::get_form_by_post( $_REQUEST[ 'post_id' ] ) ) {
      $value = in_array( $_REQUEST[ 'value' ], array( 'true', 'false' ) ) ? $_REQUEST[ 'value' ] : 'false';
      update_post_meta( $_REQUEST[ 'post_id' ], FEPS_RENEW_PLAN, $value );
    }
    die( '1' );
  }
  
  /**
   * Ran after account created
   *
   * @param int $user_id
   * @param array $new_user
   * @param array $form_data
   * @author korotkov@ud
   */
  static public function account_created( $args = false ) {
    global $wp_post_statuses;
    $form_data = array();
    $user_id = '';
    $new_user = 0;
    extract( $args );

    if ( in_array( $form_data['new_post_status'], self::$statuses ) ) {

      //** Do not send notification if specifically disabled */
      if( !isset($form_data['notifications']['user_creation']) || $form_data['notifications']['user_creation'] !== 'disable') {
        update_user_meta( $user_id, 'is_not_approved', 1 );
        WPP_Mail::user_created( $user_id, $new_user );
      }
    }
  }

  /**
   * Set available property statuses
   * @param array $current
   * @return array
   * @author korotkov@ud
   */
  static public function property_statuses( $current ) {

    $return   = array();

    foreach( self::$statuses as $status_slug ) {
      $return[ $status_slug ] = $current[ $status_slug ];
    }

    ksort( $return );

    return $return;
  }

  /**
   * Removes extra attachments from post.
   * Ignores featured image.
   *
   * @author peshkov@UD
   * @since 2.0
   */
  static public function remove_extra_attachments( $post_id ) {

    $form = self::get_form_by_post( $post_id );
    if( !$form ) {
      return null;
    }

    //** Get information about images limit */
    $images_limit = 0;
    if( !empty( $form[ 'feps_credits' ] ) && $form[ 'feps_credits' ] == 'true' ) {
      $subsc_plan = get_post_meta( $post_id, FEPS_META_PLAN, true );
      if( !empty( $subsc_plan ) && is_array( $subsc_plan ) ) {
        $images_limit = !empty( $subsc_plan[ 'images_limit' ] ) ? (int)$subsc_plan[ 'images_limit' ] : 0;
      } else {
        $images_limit = 1;
      }
    } else {
      $images_limit = !empty( $form[ 'images_limit' ] ) ? (int)$form[ 'images_limit' ] : 0;
    }

    if( $images_limit > 0 ) {
      $property = WPP_F::get_property( $post_id, array( 'get_children' => 'false' ) );
      if( isset( $property[ 'gallery' ] ) && is_array( $property[ 'gallery' ] ) && count( $property[ 'gallery' ] ) > $images_limit ) {
        //** Reverse attachents to start remove from the newest ones. */
        $attachments = array_reverse( $property[ 'gallery' ] );
        foreach( $attachments as $k => $attachment ) {
          //** Ignore Featured Image removing */
          if( isset( $property[ 'featured_image' ] ) && $attachment[ 'attachment_id' ] == $property[ 'featured_image' ] ) {
            continue;
          }
          //** Try to remove extra attachment */
          if ( false !== wp_delete_attachment( $attachment[ 'attachment_id' ] ) ) {
            unset( $attachments[$k] );
          }
          //** Stop removing attachments if images limit is the same or more than attachments exist */
          if( count( $attachments ) <= $images_limit ) {
            break;
          }
        }
      }
    }
  }

  /**
   * Handles property actions when a property is saved
   *
   * @todo Check capabilities that current user has the authority to publish a FEPS post
   * @param type $post_ID
   * @param type $post_after
   * @param type $post_before
   */
  static public function post_updated($post_ID, $post_after, $post_before) {
    global $wp_properties,$wp_post_statuses;

    if( $post_after->post_type != 'property' || !get_post_meta( $post_ID, 'wpp_feps_property', true ) ) {
      return null;
    }

    $form = self::get_form_by_post( $post_ID );

    //** Be sure that property is related to existing form */
    if( empty( $form ) ) {
      return null;
    }

    if( $post_before->post_status != $post_after->post_status ) {

      switch ( $post_after->post_status ) {

        case 'pending':
          delete_post_meta( $post_ID, FEPS_META_EXPIRED );
          //** Subscription plan must be provided only for published properties */
          delete_post_meta( $post_ID, FEPS_META_PLAN );

          //** Update expired time for pending status if it's set for the current FEPS Form */
          if ( (int)$form['trash_time'] > 0 ) {
            $expired_time = ( (int)$form['trash_time'] * 86400 ) + time();
            update_post_meta( $post_ID, FEPS_META_EXPIRED, $expired_time );
          }

          if ( $form['notifications']['on_status_updated'] == 'true' ) {
            if( $post_before->post_status == 'publish' ) {
              $subject = __('Publish Time Has Expired', ud_get_wpp_feps()->domain);
              $crm_log_message = __('Publish time has expired for [property_title].', ud_get_wpp_feps()->domain);
              if( !empty( $form[ 'feps_credits' ] ) && $form[ 'feps_credits' ] == 'true' ) {
                if( (int)$form['trash_time'] > 0 ) {
                  $message = sprintf(__('Hello [display_name]%1$s%1$sPublish time for Your %3$s [property_title] has expired.%1$s%1$sYou have [days] %2$s to choose new subscription for your %3$s.%1$s%1$sClick this link to view all your [status] %4$s:%1$s[url]', ud_get_wpp_feps()->domain), PHP_EOL,_n('day','days',(int)$form['trash_time']), WPP_F::property_label( 'singular' ), WPP_F::property_label( 'plural' ) );
                  $days = (int)$form['trash_time'];
                } else {
                  $message = sprintf(__('Hello [display_name]%1$s%1$sPublish time for Your %2$s [property_title] has expired.%1$s%1$sIf You wish you could choose new subscription for your %2$s.%1$s%1$sClick this link to view all your [status] %3$s:%1$s[url]', ud_get_wpp_feps()->domain), PHP_EOL, WPP_F::property_label( 'singular' ),WPP_F::property_label( 'plural' ) );
                }
              } else {
                $message = sprintf(__('Hello [display_name]%1$s%1$sYour %2$s [property_title] status has been changed from Published to Pending. Click this link to view all your [status] %3$s:%1$s[url]', ud_get_wpp_feps()->domain), PHP_EOL, WPP_F::property_label( 'singular' ),WPP_F::property_label( 'plural' ) );
              }
            } elseif ( $post_before->post_status == 'trash' ) {
              $message = sprintf( __( 'Hello [display_name]%1$s%1$sYour %2$s [property_title] has been restored. The current status is [status]. Click this link to view all your [status] %3$s:%1$s[url]' , ud_get_wpp_feps()->domain ), PHP_EOL, WPP_F::property_label( 'singular' ),WPP_F::property_label( 'plural' ) );
            }

            WPP_Mail::feps_post_status_updated( $post_ID, array_filter( array(
              'subject' => !empty( $subject ) ? $subject : false,
              'message' => !empty( $message ) ? $message : false,
              'crm_log_message' => !empty( $crm_log_message ) ? $crm_log_message : false,
              'data' => array_filter( array(
                'days' => !empty( $days ) ? $days : false,
                'url' => WPP_F::base_url( FEPS_VIEW_PAGE, "status={$post_after->post_status}" ),
                'status' => @$wp_post_statuses[$post_after->post_status]->label,
              ) ),
            ) ) );

          }
          break;

        case 'trash':
          delete_post_meta( $post_ID, FEPS_META_EXPIRED );
          //** Subscription plan must be provided only for published properties */
          delete_post_meta( $post_ID, FEPS_META_PLAN );

          if ( isset( $form['notifications']['on_status_updated'] ) && $form['notifications']['on_status_updated']=='true' ) {
            if( $post_before->post_status == 'pending' ) {
              $message = sprintf(__('Hello [display_name]%1$s%1$sPending time for Your %2$s [property_title] was over and it had been removed.%1$s%1$sYou still able to make a new %2$s Submission if you need. Please visit our site:%1$s[site_url]', ud_get_wpp_feps()->domain), PHP_EOL,WPP_F::property_label( 'singular' ) );
            } else {
              $message = sprintf(__('Hello [display_name]%1$s%1$sYour %2$s [property_title] has been removed.%1$s%1$sYou still able to make a new %2$s Submission if you need. Please visit our site:%1$s[site_url]', ud_get_wpp_feps()->domain), PHP_EOL,WPP_F::property_label( 'singular' ) );
            }
            WPP_Mail::feps_post_status_updated( $post_ID, array(
              'subject' => sprintf(__('%1$s was deleted', ud_get_wpp_feps()->domain),ucfirst( WPP_F::property_label( 'singular' ) ) ),
              'message' => $message,
              'crm_log_message' => sprintf(__('%1$s [property_title] was deleted .', ud_get_wpp_feps()->domain),ucfirst( WPP_F::property_label( 'singular' ) ) ),
            ) );
          }
          break;

        case 'publish':
          if( $post_before->post_status == 'trash' ) {
            wp_update_post( array(
              'ID' => $post_ID,
              'post_status' => 'pending',
            ) );
            break;
          } else {
            //** Subscription plan functionality is related only to Sponsored listings */
            if( !empty( $form[ 'feps_credits' ] ) && $form[ 'feps_credits' ] == 'true' ) {
              //* Determine if user has permissions to manually publish FEPS and set its subscription plan */
              if ( !empty( $_REQUEST[ 'feps_subscription_plan' ] ) &&
                   current_user_can( self::$capability ) ) {
                self::set_subscription_plan( $post_ID, $_REQUEST[ 'feps_subscription_plan' ] );
              }
              self::maybe_set_expired_time( $post_ID );
            }
            //** Action on submission approving (publishing) **/
            do_action('wpp_feps_submission_approved', $post_ID );
          }
          break;

        default:
          delete_post_meta( $post_ID, FEPS_META_EXPIRED );
          //** Subscription plan must be provided only for published properties */
          delete_post_meta( $post_ID, FEPS_META_PLAN );
          break;
      }
    }

    self::remove_extra_attachments( $post_ID );
  }
  
  /**
   * Sets ( renew ) expired time based on current Subscription plan
   * 
   * @param int $post_id
   * @author peshkov@UD
   * @since 2.4
   */
  static public function maybe_set_expired_time( $post_id ) {
    //* Set Expired Time meta based on subscription plan. Note: Subscription plan should be added before wp_update_post */
    if ( $plan = get_post_meta( $post_id, FEPS_META_PLAN, true ) ) {
      if ( !empty( $plan['duration']['value'] ) && !empty( $plan['duration']['interval'] ) ) {
        $value = (int)$plan['duration']['value'];
        $interval = $plan['duration']['interval'] . ( $value > 1 ? "s" : "" );
        $expired_time = @strtotime( "+{$value} {$interval}" );
        if ( $expired_time ) {
          update_post_meta( $post_id, FEPS_META_EXPIRED, $expired_time );
        }
      }
    }
  }
  
  /**
   * Set subscription plan for property
   * 
   * @param integer $property_id
   * @param string $plan
   * @return boolean
   * @since 2.6
   * @author peshkov@UD
   */
  static public function set_subscription_plan( $property_id, $plan ) {
    
    $form = self::get_form_by_post( $property_id );
    if ( !isset( $form[ 'subscription_plans' ][ $plan ] ) ) {
      return false;
    }
    
    $data = $form[ 'subscription_plans' ][ $plan ];
    $data[ 'slug' ] = $plan;
    if( !update_post_meta( $property_id, FEPS_META_PLAN, $data ) ) {
      return false;
    }
    
    // Set Property as Featured if option 'Is Featured' is set for current Subscription Plan
    if( isset( $data[ 'is_featured' ] ) && $data[ 'is_featured' ] == 'true' ) {
      update_post_meta( $property_id, 'featured', 'true' );
    }
    
    do_action( 'wpp::feps::set_subscription_plan', $property_id, $plan );
    return true;
    
  }


  /**
   * Look up e-mail address
   * @global object $current_user
   * @param string $user_email
   * @param string $user_password
   * @return array
   */
  static public function email_lookup($user_email, $user_password) {
    global $current_user;

    if ( !empty( $current_user->ID ) ) {
      return array('email_exists' => 'true', 'credentials_verified' => 'true');
    }

    if(!empty($user_email) && $user_id = email_exists($user_email)) {

      //* If password is passed, verify */
      if(!empty($user_password)) {

      $userdata = get_user_by('id', $user_id);

      if(wp_check_password($user_password, $userdata->user_pass, $user_id)) {
        return array('email_exists' => 'true', 'credentials_verified' => 'true');
      } else {
        return array('email_exists' => 'true', 'invalid_credentials' => 'true');
      }

      } else {
        return array('email_exists' => 'true');
      }

    } else {
      return array('email_exists' => 'false');
    }

  }

  /**
   * Adds Custom capability to the current premium feature
   * @param array $capabilities
   * @author korotkov@ud
   * @return array
   */
  static public function add_capability($capabilities) {

    $capabilities[self::$capability] = __('Manage FEPS', ud_get_wpp_feps()->domain);

    return $capabilities;
  }

  /**
   * WPP template redirect.
   *
   * @todo Add a shortcode listener to wpp_template_redirect() in order detect if the current page has a FEPS form on it, if so, properly load the JS files. Also use wp_localize_script() so strings in fileuploader.js can be translated. - potanin@ud
   *
   * @global object $wp_query
   * @global object $post
   * @return null
   */
  static public function wpp_template_redirect() {
    global $wp_query, $post;

    //** STEP 1. Determine if this is My FEPS Page */
    if( isset( $wp_query->wpp_my_feps_page ) && $wp_query->wpp_my_feps_page === true ) {
      do_action( "wpp_my_feps_page" );
    }

    //** STEP 2. Determine if this is FEPS Edit Page */
    if( isset( $wp_query->wpp_feps_edit_page ) && $wp_query->wpp_feps_edit_page === true ) {
      do_action( "wpp_feps_edit_page" );
    }

    //** STEP 3. Determine if this is View Pending Page */
    if( isset( $_REQUEST['wpp_front_end_action'] ) &&
        'wpp_view_pending' == $_REQUEST['wpp_front_end_action'] &&
        $wp_query->query_vars['p'] &&
        !empty($_REQUEST['pending_hash']) ) {

      $maybe_post = WPP_F::get_property($wp_query->query_vars['p']);

      if($_REQUEST['pending_hash'] != $maybe_post['wpp::feps::pending_hash']) {
        return;
      }

      $post = WPP_F::array_to_object($maybe_post);

      add_action("template_redirect_single_property", array("class_wpp_feps", "template_redirect_single_property"));
    }

    //** STEP 4. Determine if this is FEPS Credit Adding Page */
    if( isset( $wp_query->wpp_feps_spc_page ) && $wp_query->wpp_feps_spc_page === true ) {
      do_action( "wpp_feps_spc_page" );
    }
  }

  /**
   * Fix 404 error on pending property pages.
   *
   * @global object $query
   */
  static public function fix_404($query) {
    global $wp, $wp_query, $wpdb;

    $_is_404 = true;

    //** STEP 1. Determine if this is My FEPS Page */
    if( $wp->request == FEPS_VIEW_PAGE ||
        $wp->query_string == "p=" . FEPS_VIEW_PAGE ||
        $query->query_vars[ 'pagename' ] == FEPS_VIEW_PAGE ||
        $query->query_vars[ 'category_name' ] == FEPS_VIEW_PAGE
    ) {
      $wp_query->wpp_my_feps_page = true;
      $_is_404 = false;
    }

    //* STEP 2. Determine if this is View Pending Page */
    if( 
      isset( $_REQUEST['wpp_front_end_action'] )
      && 'wpp_view_pending' == $_REQUEST['wpp_front_end_action'] 
      && !empty( $query->query_vars['p'] )
      && !empty( $_REQUEST['pending_hash'] ) 
    ) {
      $pending_hash = $wpdb->get_var( $wpdb->prepare( "
        SELECT meta_value
          FROM {$wpdb->postmeta}
          WHERE post_id = %d
            AND meta_key = 'wpp::feps::pending_hash'
            AND meta_value = %s
      ", $wp_query->query_vars['p'], $_REQUEST['pending_hash'] ) );

      if( $pending_hash ) {
        $_is_404 = false;
      }
    }

    //* STEP 3. Determine if this is FEPS Edit Page */
    if( !empty( $_REQUEST['feps'] ) && (
        $wp->request == FEPS_EDIT_PAGE ||
        $wp->query_string == "p=" . FEPS_EDIT_PAGE ||
        $query->query_vars[ 'pagename' ] == FEPS_EDIT_PAGE ||
        $query->query_vars[ 'category_name' ] == FEPS_EDIT_PAGE )
    ) {
      $property_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE ID = %d", $_REQUEST['feps'] ) );

      if( $property_id ) {
        //** Additional secure: be sure that request has hash of feps form id and it's the same with the current property's form */
        $form_id = get_post_meta( $property_id, FEPS_META_FORM, true );
        if( !empty( $form_id ) && isset( $_REQUEST[ 'wpp_feps_form' ] ) && $_REQUEST[ 'wpp_feps_form' ] == md5( $form_id ) ) {
          $wp_query->wpp_feps_edit_page = true;
          $_is_404 = false;
        }
      }
    }

    //* STEP 4. Go through filters */
    $_is_404 = apply_filters( 'wpp::feps::fix_404', $_is_404, $query );

    //* STEP 5. Fix 404 if we have page */
    if ( !$_is_404 ) {
      //** Set to override the 404 status */
      add_action('wp', function() { status_header( 200 ); });

      add_action( 'wp', function() { global $wp_query; $wp_query->is_404 = false; }, 0, 10 );

      //** Prevent is_404() in template files from returning true */
      add_action('template_redirect', function() { global $wp_query; $wp_query->is_404 = false; }, 0, 10);
    }

  }

  /**
   * Single Property template redirect
   *
   * Add all pending-property specific hooks and filters here.
   *
   * @global object $post
   * @global object $wp_query
   */
  static public function template_redirect_single_property() {
    global $post, $wp_query;

    add_action('wp_head', function() { do_action('wp_head_single_property'); });

    $wp_query = apply_filters( 'wpp_pending_template_query', $wp_query, $post );

    add_filter('the_title', array('class_wpp_feps', 'feps_the_title'), 0, 2);

    add_action('body_class', array('class_wpp_feps', 'feps_body_class'));

  }

  /**
   * Modify FEPS (unapproved property) title.
   *
   * @param string $title The current title of a property.
   * @param int $post_id The ID of the currently viewed property.
   * @return string
   */
  static public function feps_the_title( $title, $post_id = false) {
    global $post;

    //** Make sure only the globally displayed property */
    if($post->ID != $post_id) {
      return $title;
    }

    return $title . __(' (Pending Approval)', ud_get_wpp_feps()->domain);

  }

  /**
   * Add custom body class for feps pending properties
   *
   * @param array $classes
   * @return Array
   */
  static public function feps_body_class( $classes = array() ) {
    global $post;

    if($post_status = $post->post_status) {
      $classes[] = 'wpp_' . $post_status;
    }

    $classes[] = 'feps-pending';

    return $classes;

  }

  /**
   * Update permalink of FEPS property if it has status pending and
   * current user is author of this FEPS property
   *
   * @param array $_property
   * @author peshkov@UD
   * @since 1.4
   * @version 0.1
   */
  static public function get_feps_permalink( $_property = false, $_is_logged_in = true ) {
    global $property;

    if ( empty( $_property ) ) {
      if ( empty( $property ) ) return false;
      $_property = $property;
    }

    if ( $_is_logged_in ) {
      $_break = true;
      if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        if( $_property['post_author'] == $user->ID ) {
          $_break = false;
        }
      }
      if ( $_break ) {
        return $_property['permalink'];
      }
    }

    if (
      $_property['post_status'] == 'pending' &&
      isset( $_property['wpp_feps_property'] ) &&
      $_property['wpp_feps_property'] == true &&
      strpos( $_property['permalink'], "pending_hash={$_property['wpp::feps::pending_hash']}" ) === false
    ) {
      return $_property['permalink'].'&pending_hash='.$_property['wpp::feps::pending_hash'].'&wpp_front_end_action=wpp_view_pending';
    }

    return $_property['permalink'];
  }


  /**
   * Returns form data belongs to the passed post_id
   *
   * @global array $wp_properties
   * @param int $post_id
   * @return array|boolean
   * @author peshkov@UD
   * @since 2.0
   */
  static public function get_form_by_post( $post_id ) {
    global $wp_properties;

    $form_id = get_post_meta( $post_id, FEPS_META_FORM, true );
    if( !empty( $form_id ) && !empty( $wp_properties['configuration']['feature_settings']['feps']['forms'][ $form_id ] ) ) {
      return $wp_properties['configuration']['feature_settings']['feps']['forms'][ $form_id ];
    }
    return false;
  }

  /**
   * Restores form data from md5 hash
   *
   * @global array $wp_properties
   * @param string $hash
   * @param boolean $onsubmit
   * @return array
   * @author peshkov@UD
   * @since 2.0
   */
  static public function get_form_by_md5( $hash, $onsubmit = true ) {
    global $wp_properties;

    $form = false;
    //** Get form_ID */
    $forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];
    if( is_array( $forms ) ) {
      //** Cycle through forms and match up with passed md5 hash */
      foreach( $forms as $form_id => $form_data ) {
        if( md5( $form_id ) == $hash ) {
          $form = $form_data;
          $form[ 'form_id' ] = $form_id;
          break;
        }
      }
    }
    if( !empty( $form ) ) {
      //** Filter form data to be able to control the process */
      if( $onsubmit ) {
        $form = apply_filters( 'wpp_feps_submit_property_form', $form );
      } else {
        $form = apply_filters( 'wpp_feps_edit_property_form', $form );
      }
    }
    return $form;
  }


  /**
   * AJAX create/edit FEPS property
   *
   * @global array $wp_properties
   * @author peshkov@UD
   * @since 2.0
   */
  static public function ajax_feps_save_property() {
    global $wp_properties;

    $response = array(
      'success' => true,
      'credentials_verified' => false,
      'message' => '',
      'callback' => false,
    );

    $event = __( 'created', ud_get_wpp_feps()->domain );

    try {

      $request = wp_parse_args( $_REQUEST[ 'data' ] );

      if( empty( $request[ 'wpp_feps_data' ] ) ) {
        throw new Exception( __( 'Submitting data is incorrect', ud_get_wpp_feps()->domain ) );
      }

      //** Prevent shortcodes and XSS adding! */
      $data = WPP_F::sanitize_request( $request[ 'wpp_feps_data' ] );
      
      //** Verify nonce */
      if( !ud_get_wpp_feps()->verify_nonce( $request['nonce'], 'submit_feps' ) ) {
        throw new Exception( sprintf( __( '%s submitting is prohibited', ud_get_wpp_feps()->domain ), WPP_F::property_label() ) );
      }
      
      $form_data = self::get_form_by_md5( $data['form_id'] );
      if( !$form_data ) {
        throw new Exception( __('An error occurred, the form could not be found', ud_get_wpp_feps()->domain ) );
      }

      //** Create new property */
      if( empty( $request['wpp_feps_data'][ 'post' ] ) ) {

        if( is_user_logged_in() ) {
          $response[ 'credentials_verified' ] = true;
          $current_user = wp_get_current_user();
          $data[ 'user_id' ] = $current_user->ID;
        }

        //** Verify user credentials if they are passed */
        if( !$response[ 'credentials_verified' ] && $data['user_email'] && $data['user_password']) {
          $data[ 'user_id' ] = email_exists( $data['user_email'] );
          $user = get_user_by('email', $data['user_email'] );
          if( $user && wp_check_password( $data['user_password'], $user->user_pass, $data[ 'user_id' ] ) ) {
            $response[ 'credentials_verified' ] = true;
            do_action('wpp_feps_credentials_verified', $data );
          }
        }

        //** We have a new user */
        if( !$response[ 'credentials_verified' ] ) {
          $new_user['user_login'] = $data['user_email'];
          $new_user['role'] = $form_data['new_role'];
          $new_user['user_email'] = $data['user_email'];
          $new_user['user_pass'] = wp_generate_password();
          $data[ 'user_id' ] = wp_insert_user($new_user);
          if( is_wp_error( $data[ 'user_id' ] ) ) {
            throw new Exception( sprintf( __( 'Your submission was not successful: %s', ud_get_wpp_feps()->domain ), $data[ 'user_id' ]->get_error_message() ) );
          }
        }
        
        $property = self::submit_property( $data );

        //** Check for any issues on creating new property */
        if( is_wp_error( $property ) ) {
          throw new Exception( $property->get_error_message() );
        }

        if( !$response[ 'credentials_verified' ] ) {
          do_action( 'wpp_feps_account_created', array(
            'user_id' => $data[ 'user_id' ],
            'new_user' => $new_user,
            'form_data' => $form_data,
            'property_id' => $property
          ));
        }
      }

      //** Edit existing property */
      else {

        $event = __( 'updated', ud_get_wpp_feps()->domain );

        if( !is_user_logged_in() ) {
          throw new Exception( __('You must be logged in to edit %s', ud_get_wpp_feps()->domain ), WPP_F::property_label() );
        }

        $property = self::edit_property( $data );

        //** Check for any issues on creating new property */
        if( is_wp_error( $property ) ) {
          throw new Exception( $property->get_error_message() );
        }
        
        //** Maybe update slideshow images */
        if( !empty( $form_data['add_to_slideshow'] ) && $form_data['add_to_slideshow'] == 'true' && !empty( $data[ 'image_upload' ] ) ) {
          $slideshow = array();
          foreach( (array) $data[ 'image_upload' ] as $attachment_id => $v ) {
            if( $v == 'on' ) {
              $slideshow[] = 'item=' . $attachment_id;
            }
          }
          if( !empty( $slideshow ) ) {
            $_POST[ 'property_slideshow_image_array' ] = implode( '&', $slideshow );
          }
        }
        
        do_action( 'save_property', $property[ 'ID' ] );

        $response[ 'credentials_verified' ] = true;

      }

    } catch (Exception $e) {
      $response[ 'success' ] = false;
      $response[ 'message' ] = $e->getMessage();
    }

    if( $response[ 'success' ] == true ) {
      //** Do something we need after submit */
      if( $response[ 'credentials_verified' ] ) {
        $response[ 'message' ] = sprintf( __( 'Your %s has been successfully %s.', ud_get_wpp_feps()->domain ), WPP_F::property_label(), $event );
      } else {
        $response[ 'message' ] = sprintf( __( 'Your %s and Account have been successfully %s. Check your e-mail to activate account and log in for continuing.', ud_get_wpp_feps()->domain ), WPP_F::property_label(), $event );
      }
      $response[ 'callback' ] = apply_filters( 'wpp::feps::save::callback', self::get_feps_permalink( $property, false ), $property, $request );
    }

    die( json_encode( $response ) );

  }

  /**
   * Submit new property
   *
   * @author peshkov@UD
   * @since 2.0
   */
  static public function submit_property( $data ) {
    global $wp_properties;

    try {

      $form_data = self::get_form_by_md5( $data['form_id'] );

      if( !$form_data ) {
        throw new Exception( __('An error occurred, the form could not be found', ud_get_wpp_feps()->domain ) );
      }

      $new_property = array(
        'post_content' => isset( $data['post_content'] ) ? $data['post_content'] : '',
        'post_parent'  => isset( $data['parent_id'] ) ? $data['parent_id'] : '',
        // Prevent XSS. Wondered why WP did not do that inside of wp_insert_post();
        'post_title'   => isset( $data['post_title'] ) ? htmlspecialchars( strip_tags( $data['post_title'] ) ) : '',
        'post_author'  => isset( $data['user_id'] ) ? $data[ 'user_id' ] : '',
        'post_status'  => $form_data['new_post_status'],
        'post_type'    => 'property'
      );

      if(isset( $data[ 'wpp_type' ] ))
        $data[ 'property_type' ] = $data[ 'wpp_type' ];

      if(empty($data[ 'property_type' ]) || !array_key_exists($data[ 'property_type' ], $wp_properties['property_types'])){
        $property_type           = $form_data['property_type'];
        $data[ 'wpp_type' ]      = $form_data['property_type'];
        $data[ 'property_type' ] = $form_data['property_type'];
      }

      // Generating title based on auto generating rule.
      if(!empty($form_data['required']['post_title']['auto_generate_rule'])){
        $new_property_title = $form_data['required']['post_title']['auto_generate_rule'];
        foreach ($data as $key => $value) {
          switch (true) {
            case $key == 'post_title':
              $key = 'title';
              break;
            
            case $key == 'property_type':
              if(isset($wp_properties['property_types'][$value])){
                $value = $wp_properties['property_types'][$value];
              }
              break;
            
            case is_array($value):
              $value = implode(', ', $value);
              break;
            
            default:
              # code...
              break;
          }
          $new_property_title = str_replace("[$key]", $value, $new_property_title);
        }
        $new_property['post_title'] = $new_property_title;
      }

      //** Commit basic property data to database */
      $property_id = wp_insert_post( $new_property );

      //** Check for any issues inserting property */
      if( is_wp_error($property_id) ) {
        throw new Exception( $property_id->get_error_message() );
      }

      //** Mark property as FEPS */
      update_post_meta( $property_id, 'wpp_feps_property', true );

      //** Set property type */
      //$property_type = !empty( $data[ 'property_type' ] ) ? $data[ 'property_type' ] : $form_data['property_type'];
      update_post_meta( $property_id, 'property_type', $property_type );

      //** Set pending hash */
      update_post_meta( $property_id, 'wpp::feps::pending_hash', md5($property_id.$data[ 'user_id' ]) );

      //** Store form_id in property meta */
      update_post_meta( $property_id , FEPS_META_FORM, $form_data[ 'form_id' ] );

      //** If FEPS has pending status, set Expired Time for it. */
      if (
        isset( $form_data['new_post_status'] ) &&
        'pending' == $form_data['new_post_status'] &&
        isset( $form_data['trash_time'] ) &&
        (int)$form_data['trash_time'] > 0
      ) {
        $expired_time = ( (int)$form_data['trash_time'] * 86400 ) + time();
        update_post_meta( $property_id, FEPS_META_EXPIRED, $expired_time );
      }

      self::update_feps_data( $property_id, $data, $form_data );

      $property = WPP_F::get_property( $property_id );

      do_action( 'wpp_feps_submitted', $property_id );

    } catch (Exception $e) {
      $property = new WP_Error( 'error', $e->getMessage() );
    }

    return $property;
  }

  /**
   *
   * @global object $current_user
   * @global array $wp_properties
   * @param type $data
   */
  static public function edit_property( $data ) {

    try {

      $form_data = self::get_form_by_md5( $data['form_id'], false );

      if( !$form_data ) {
        throw new Exception( __('An error occurred, the form could not be found', ud_get_wpp_feps()->domain ) );
      }

      $property_id = $data['post'];

      $post_table = array(
        'ID'   => $property_id,
        //** Prevent XSS. Wondered why WP did not do that inside of wp_insert_post(); */
        'post_title'   => htmlspecialchars( strip_tags( $data['post_title'] ) ),
        'post_content' => ( !empty( $data['post_content'] ) ? $data['post_content'] : '' ),
      );

      wp_update_post( $post_table );

      self::update_feps_data( $property_id, $data, $form_data );

      $property = WPP_F::get_property( $property_id );

    } catch (Exception $e) {
      $property = new WP_Error( 'error', $e->getMessage() );
    }

    return $property;

  }

  /**
   * Updates property's data
   *
   * @param int $property_id
   * @param array $data
   * @param array $form_data
   * @return boolean
   * @author peshkov@UD
   * @since 2.0
   */
  static public function update_feps_data( $property_id, $data, $form_data ) {

    if( empty( $form_data ) || !is_array( $form_data ) ) {
      return false;
    }

    foreach( (array)$form_data['fields'] as $attribute ) {

      //** Check if an attribute has been passed */
      $attribute_data = WPP_F::get_attribute_data($attribute['attribute']);

      switch( $attribute_data['storage_type'] ) {

        case 'meta_key':
          if(!isset($data[$attribute['attribute']]))
            break;
          $value = $data[$attribute['attribute']];
          $meta_values[$attribute['attribute']] = $value;
          if( !empty( $attribute_data['currency'] ) || !empty( $attribute_data['numeric'] ) ) {
            $value = str_replace(array("$", ","), '', $value);
          }
          if ( !is_array( $value ) ) {
            $value = strip_tags($value);
          }

          if($attribute_data['data_input_type'] == 'color'){
            if($value == '#')
              break;
          }

          if($attribute_data['multiple']){
            delete_post_meta($property_id, $attribute['attribute']);
            foreach ((array)$value as $key => $val) {
              add_post_meta($property_id, $attribute['attribute'], $val);
            }
          }
          else{
            update_post_meta($property_id, $attribute['attribute'], $value);
          }
          break;

        case 'post_table':
          //** Do nothing - already added by  wp_update_post();
          break;

        case 'image_upload':
          //** Removing images that need to be removed */
          if( isset( $data[$attribute['attribute']] ) && is_array( $data[$attribute['attribute']] ) ) {
            foreach ((array)$data[$attribute['attribute']] as $attachmentid=>$action){
              if ($action=='off'){
                //** Get data from request */
                wp_delete_attachment( $attachmentid );
              }
            }
          }
          //** Move over any uploaded images */
          $this_session = $data['this_session'];
          $upload_dir = wp_upload_dir();
          $feps_files_dir = $upload_dir['basedir'] . '/feps_files/' . $this_session;
          //** Check if a directory exists */
          if(is_dir($feps_files_dir)) {
            /** WordPress Image Administration API */
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            /** WordPress Media Administration API */
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            if ($handle = opendir($feps_files_dir)) {
              while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                  if(file_is_valid_image($feps_files_dir . '/' . $file)) {
                    $moved_images[]  = class_wpp_feps::move_image($property_id, $feps_files_dir . '/' . $file);
                  }
                }
              }
              //** Delete folder */
              unlink($feps_files_dir . '/index.php');
              rmdir($feps_files_dir);
            }
          }
          break;

        case 'taxonomy':
          //wp_set_post_terms ( int $post_id, string|array $tags = '', string $taxonomy = 'post_tag', bool $append = false )
          if(isset($data[$attribute['attribute']])){
            $value = $data[$attribute['attribute']];
            wp_set_post_terms($property_id, $value, $attribute['attribute']);
          }
        break;

        default:
          do_action('wpp_feps_commit_attribute', $attribute, $property_id );
        break;

      }
    }

    return true;
  }

  /**
   * Handles FEPS upgrades
   *
   * @author peshkov@UD
   * @since 1.4
   */
  static public function _handle_upgrade() {
    global $wpdb,$wp_properties;

    if( version_compare( WPP_FEPS_Version, get_option( 'WPP_FEPS_Version' ) ) ) {

      $messages = array( sprintf( __( 'Upgraded FEPS to version %1s.', ud_get_wpp_feps()->domain ), WPP_FEPS_Version ) );

      switch ( WPP_FEPS_Version ) {

        case '1.4':

          $forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];
          $types = array();
          foreach ((array)$forms as $form_id => $form_data){
            if (!in_array($form_data['property_type'],$types)){
              $query  = "
                SELECT p.ID
                  FROM {$wpdb->posts} AS p
                  JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.ID
                  JOIN {$wpdb->postmeta} AS pmt ON pmt.post_id = p.ID
                  WHERE pm.meta_key = 'wpp_feps_property'
                  AND pm.meta_value = '1'
                  AND pmt.meta_key = 'property_type'
                  AND pmt.meta_value = '{$form_data['property_type']}'
                  AND p.post_status IN ('publish', 'pending')

              ";
              $feps = $wpdb->get_results($query, ARRAY_A);
              foreach ((array)$feps as $value){
                update_post_meta($value['ID'], FEPS_META_FORM,$form_id);
              }
              $types[] = $form_data['property_type'];
            }
          }

          $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = '" . FEPS_META_FORM . "' WHERE meta_key = 'wpp_feps_form_id' " );
          $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = 'wpp::feps::pending_hash' WHERE meta_key = 'wpp_feps_pending_hash' " );
        break;

      }

      WPP_F::log( implode( ' ', (array) $messages ), 'FEPS' );

    }

    update_option( 'WPP_FEPS_Version', WPP_FEPS_Version );
  }

  /**
   * Moves images from temporary directory to real folder
   *
   * @since 0.1
   */
  static public function move_image($post_id, $old_path, $post_data = array()) {

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $uploads = wp_upload_dir();

    $original_filename = basename ($old_path);

    $filename = wp_unique_filename( $uploads['path'], $original_filename);

    $new_file = $uploads['path'] . "/$filename";

    $file_data = wp_check_filetype_and_ext($old_path, $original_filename);

    if(!rename($old_path, $new_file)) {
      return false;
    }

    // Set correct file permissions
    $stat = stat( dirname( $new_file ));
    $perms = $stat['mode'] & 0000666;
    @ chmod( $new_file, $perms );

    $moved_image = apply_filters( 'wpp_handle_upload', array(
      'file' => $new_file,
      'url' => $uploads['url'] . "/$filename",
      'type' => $file_data['type']
    ), 'upload' );


    // use image exif/iptc data for title and caption defaults if possible
    if ( $image_meta = @wp_read_image_metadata($new_file) ) {
      if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
        $title = $image_meta['title'];
      }
      if ( trim( $image_meta['caption'] ) ) {
        $content = $image_meta['caption'];
      }
    }

    if(empty($image_meta['title'])) {
      $image_meta['title'] = preg_replace('/\.[^.]+$/', '', basename($new_file));
    }

    // Construct the attachment array
    $attachment = array_merge( array(
      'post_parent' => $post_id,
      'post_mime_type' => $file_data['type'],
      'guid' => $moved_image['url'],
      'post_title' => $image_meta['title'],
      'post_content' => $image_meta['caption'],
    ), $post_data );


    // Save the data
    $id = wp_insert_attachment( $attachment, $new_file, $post_id );

    if ( !is_wp_error($id) ) {
      // first include the image.php file
      // for the function wp_generate_attachment_metadata() to work
      require_once( ABSPATH . 'wp-admin/includes/image.php' );
      $attach_data = wp_generate_attachment_metadata( $id, $new_file );
      wp_update_attachment_metadata( $id, $attach_data );
      
      // Add image to slideshow if option is enabled.
      $form = self::get_form_by_post( $post_id );
      if( !empty( $form['add_to_slideshow'] ) && $form['add_to_slideshow'] == 'true' ) {
        $images = get_post_meta( $post_id, 'slideshow_images', true );
        $images = is_array( $images ) ? $images : array();
        $images[] = $id;
        update_post_meta( $post_id, 'slideshow_images', array_unique( $images ) );
      }
    }

    return $id;

  }

  /**
   * Displays notices on pending property pages on admin
   *
   * @author peshkov@UD
   * @since 0.1
   */
  static public function all_admin_notices() {
    global $wp_properties, $post;

    /**  */
    if( is_object( $post ) && $post->post_type == 'property' && $post->post_status == 'pending' && current_user_can( 'publish_wpp_properties' ) ) {
      //** Determine if the current pending property belongs to sponsored property form. If it does, we ignore notice. */
      $forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];
      $form_id = get_post_meta( $post->ID, FEPS_META_FORM, true );
      if( !empty( $form_id ) && !empty( $forms[ $form_id ] ) ) {
        $form = $forms[ $form_id ];
        if( isset( $form[ 'feps_credits' ] ) && $form[ 'feps_credits' ] == 'true' ) {
          return null;
        }
      }

      ?>
      <script type="text/javascript">
        jQuery(document).ready(function() {
          if ( jQuery('#publish').length > 0 ) {
            jQuery( '.wrap h1.wp-heading-inline' ).before( '<div class="wpp_property_admin_notice widget wp-core-ui"><span><?php _e('This listing is pending your approval.', ud_get_wpp_feps()->domain); ?></span></div>' );
            jQuery( '.wpp_property_admin_notice span' ).append( publish_now = document.createElement('a') );
            jQuery( publish_now )
              .attr('href', 'javascript:void(0);')
              .addClass( 'button button-primary button-large' )
              .text('<?php _e('Publish Now', ud_get_wpp_feps()->domain); ?>')
              .click(function(){
                jQuery( this ).unbind( 'click' );
                jQuery( '#publish' ).trigger( 'click' );
              });
          }
        });
      </script>
      <?php
    }

  }

  /**
   * Checks on FEPS Form removing if there are already added FEPS properties using the current form.
   *
   * @author peshkov@UD
   * @return JSON
   */
  static public function ajax_can_remove_form() {
    global $wpdb;

    $response = array(
      'success' => false,
      'message' => '',
    );

    if( !empty( $_REQUEST['feps_form_id'] ) ) {
      $form_id = $_REQUEST['feps_form_id'];
      $r = $wpdb->get_var( $wpdb->prepare("
        SELECT post_id
          FROM $wpdb->postmeta
          WHERE meta_key = '" . FEPS_META_FORM . "'
            AND meta_value = %s
          LIMIT 1
      ", $form_id) );
      if ( empty( $r ) ) {
        $response['success'] = true;
      } else {
        $response['message'] = __('Form can not be removed because it has Properties which were added using this form. You have to remove all related Properties before.',ud_get_wpp_feps()->domain);
      }
    } else {
      $response['message'] = __('Form ID is undefined.',ud_get_wpp_feps()->domain);
    }

    die( json_encode( $response ) );
  }

  /**
   * Handles ajax file uploads
   *
   * Uploads submitted file into a temporary directory.
   * Handles one file at a time.
   *
   * @todo consider using wp_handle_upload() for this
   * @since 0.1
   */
  static public function ajax_feps_image_upload() {

    $file_name = $_REQUEST['qqfile'];
    $this_session = $_REQUEST['this_session'];

    $upload_dir = wp_upload_dir();
    $feps_files_dir = $upload_dir['basedir'] . '/feps_files';
    $wpp_queue_dir = $feps_files_dir .  '/' . $this_session;
    $wpp_queue_url = $upload_dir['baseurl'] . '/feps_files/' . $this_session;

    if(!is_dir($feps_files_dir)) {
      mkdir($feps_files_dir, 0755);
    }

    if(!is_dir($wpp_queue_dir)) {
      mkdir($wpp_queue_dir, 0755);
      fopen($wpp_queue_dir . '/index.php', "w");
    }

    $path = $wpp_queue_dir . '/'. $file_name;

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
      // for IE!!!
      move_uploaded_file($_FILES['qqfile']['tmp_name'], $path);
    }

    $check_filetype = wp_check_filetype_and_ext($path, $file_name);

    /* if it is image */
    if($check_filetype['type']) {

      $return['success'] = 'true';

      //** Need to get thumb URL */
      $return['thumb_url'] = $wpp_queue_url . '/' . $file_name;

      $return['url'] = $wpp_queue_url . '/' . $file_name;

      die(htmlspecialchars(json_encode($return), ENT_NOQUOTES));

    } else {
      unlink($path);
    }

    die('false');

  }

  /**
   * Adds scripts and styles to slideshow pages.
   * @since 0.1
   */
  static public function admin_menu() {
    $feps_page = add_submenu_page('edit.php?post_type=property', sprintf(__('%1$s Forms',ud_get_wpp_feps()->domain), ucfirst( WPP_F::property_label( 'singular' ) )), sprintf(__('%1$s Forms',ud_get_wpp_feps()->domain), ucfirst( WPP_F::property_label( 'singular' ) )), self::$capability, 'page_feps',array('class_wpp_feps', 'page_feps'));
  }

  /**
   * Load admin scripts
   * @global object $current_screen
   */
  static public function admin_enqueue_scripts() {
    global $current_screen;

    // Load scripts on specific pages
    switch($current_screen->id)  {

      case 'property_page_page_feps':
        wp_enqueue_script( 'wp-property-admin-feps', ud_get_wpp_feps()->path( 'static/scripts/wpp.admin.feps.js' ), array( 'jquery', 'wpp-localization', 'wp-property-global' ), WPP_Version );
        wp_enqueue_script( 'wp-property-backend-global' );
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_script( 'jquery-ui-resizable' );
        wp_enqueue_script( 'jquery-fancybox' );
        wp_enqueue_style ( 'jquery-fancybox' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_style ( 'jquery-fancybox-css' );
        break;
    }

  }

  /**
   * Updates permalink of FEPS property if it has status 'pending'
   *
   * @uses wpp_get_property Filter
   * @param type $property
   * @author peshkov@UD
   * @since 1.4
   * @version 0.1
   */
  static public function wpp_get_property ( $property ) {
    $property['permalink'] = self::get_feps_permalink( $property );
    return $property;
  }


  /**
   * Adds filter by FEPS form on Property overview page
   *
   * @author odokienko@UD
   * @author peshkov@UD
   * @since 2.0
   */
  static public function wpp_get_search_filters ( $filters ){
    global $wpdb, $wp_properties;

    $feps_forms = $wpdb->get_col( "
      SELECT distinct meta_value as form_id
      FROM {$wpdb->postmeta}
      WHERE meta_key = '" . FEPS_META_FORM . "'
    " );

    $forms = !empty( $wp_properties['configuration']['feature_settings']['feps']['forms'] ) ?
      $wp_properties['configuration']['feature_settings']['feps']['forms'] : array();
    
    $values = array();
    foreach ( (array)$forms as $form_id => $data ){
      if ( in_array( $form_id, $feps_forms ) ){
        $values[$form_id] = $data['title'];
      }
    }

    //** show filter only if we have more than one form with FEPSes */
    if ( !empty( $values ) ) {
      $filters[] = array(
        'id' => FEPS_META_FORM,
        'name' => sprintf( __( '%s Form', ud_get_wpp_feps()->get('domain') ), \WPP_F::property_label( 'singular' ) ),
        'type' => 'select_advanced',
        'options' => array( '' => '' ) + $values
      );
    }

    return $filters;
  }

  /**
   *
   */
  static public function wpp_overview_columns($columns) {
    global  $wp_properties;
    if ( 
      isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_feps_column' ] )
      && $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_feps_column' ] =='true' 
    ){
      $columns[ FEPS_META_FORM ] =  sprintf(__('%1$s Form',ud_get_wpp_feps()->domain), ucfirst( WPP_F::property_label( 'singular' ) ));
    }
    return $columns;
  }

  /**
   *
   */
  static public function wpp_settings_overview_bottom($wp_properties){
    ?>
    <div><?php echo WPP_F::checkbox( 'name=wpp_settings[configuration][property_overview][show_feps_column]&label=' . sprintf(__( 'Show <span title="Front End %2$s Submissions">FEPS</span> column in All %1$s Page.',ud_get_wpp_feps()->domain ), ucfirst( WPP_F::property_label( 'plural' ) ),ucfirst( WPP_F::property_label( 'plural' ) ) ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_feps_column' ] ) ; ?></div>
    <?php
  }

  /**
   *
   * @global array $wp_properties
   * @param type $value
   * @param type $column
   * @return type
   */
  static public function wpp_attribute_filter($value, $column){
    global $wp_properties;

    if ( $column== FEPS_META_FORM ){
      $forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];
      $value = (!empty($forms[$value]) ? $forms[$value]['title'] : '');
    }
    return $value;
  }

  /**
   * Shortcode FEPS Menu
   * Renders FEPS menu
   *
   * @param mixed $atts
   * @return string
   */
  static public function wpp_feps_menu( $atts = '' ) {
    global $wp_properties, $wpdb;

    //** STEP 1. Process args */
    $before = '';
    $after = '';
    $title = '';
    $show_balance = 'true';
    $show_spc_link = 'true';
    $filters = 'false';
    $form_page = '';
    $redirect = '';
    $show_login_form = 'false';
    $show_reg_link = 'false';
    $show_remember_link = 'false';
    $login_form_description = '';

    $args = shortcode_atts( array(
      'before' => '',
      'after' => '',
      'title' => '',
      'link_text' => '',
      'show_balance' => 'true',
      'show_spc_link' => 'true',
      'filters' => 'false',
      'form_page' => '', // URL to FEPS form page
      'redirect' => '',
      'show_login_form' => 'false',
      'show_reg_link' => 'false',
      'show_remember_link' => 'false',
      'login_form_description' => '',
    ), $atts );

    extract( $args );

    $add_credits = __( "Add Credits to Balance", ud_get_wpp_feps()->domain );
    if( !empty( $wp_properties['configuration']['feps']['menu']['add_credits_label'] ) ) {
      $add_credits = $wp_properties['configuration']['feps']['menu']['add_credits_label'];
    }

    $add_property = sprintf( __( "Add New %s", ud_get_wpp_feps()->domain ), WPP_F::property_label() );
    if( !empty( $wp_properties['configuration']['feps']['menu']['add_property_label'] ) ) {
      $add_property = $wp_properties['configuration']['feps']['menu']['add_property_label'];
    }

    //* STEP 2. Check all permissions and conditions */

    //** Determine if user is logged in */

    if($args['show_login_form'] == 'true' && !is_user_logged_in() ) {
          //** Find template */
          $login_template = WPP_F::get_template_part(array(
            "feps-login-form-template"
          ), array(ud_get_wpp_feps()->path('static/views', 'dir')));

          ob_start();
          include $login_template;
          $contents = ob_get_clean();
          return $contents;
      }
    elseif ($args['show_login_form'] == 'false' && !is_user_logged_in() ) {
      return false;
    }

    $user = wp_get_current_user();

    //** Determine if user has any FEP submission already. */
    $feps = $wpdb->get_results("
      SELECT p.post_status, count(p.post_status) as amount
        FROM {$wpdb->posts} AS p
        JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.ID
        WHERE pm.meta_key = 'wpp_feps_property'
          AND p.post_author = '{$user->ID}'
          AND p.post_status IN ('publish', 'pending')
          GROUP BY p.post_status
    ", ARRAY_A);

    //** Determine if user has any funds on his FEPS balance */
    $balance = $user->get( FEPS_USER_CREDITS );
    $balance = (float)$balance;

    //** Determine if it's enabled to show menu when there are no properties and no credits on balance */
    if( isset( $wp_properties['configuration']['feps']['menu']['disable_if_no_properties'] ) &&
        $wp_properties['configuration']['feps']['menu']['disable_if_no_properties'] == 'true' ) {

      if( empty( $feps ) || empty( $balance ) ) {
        return false;
      }

    }

    $feps_amount = 0;
    foreach( $feps as $f ) {
      $feps_amount = $feps_amount + $f['amount'];
    }

    //* STEP 3. Render Menu */

    ob_start();

    echo $before;



    ?>
    <div class="wpp_feps_user_menu wpp_shortcode_feps_menu wpp_shortcode">
      <?php if( !empty( $title ) ) : ?>
        <h5><?php echo $title; ?></h5>
      <?php endif; ?>
      <ul>
        <?php if ( class_exists( 'wpi_spc' ) ) : ?>
          <?php if ( $show_balance == 'true' && is_user_logged_in() ) : ?>
          <li class="wpp_feps_balance">
            <span class="title"><?php _e("My Balance", ud_get_wpp_feps()->domain); ?></span>
            <span class="doubledot">:</span>
            <span class="value"><b><?php echo $balance; ?></b> <?php echo $balance == 1 ? __("credit", ud_get_wpp_feps()->domain) : __("credits", ud_get_wpp_feps()->domain); ?></span>
          </li>
          <?php endif; ?>
          <?php if ( $show_spc_link == 'true' && is_user_logged_in() ) : ?>
          <li class="wpp_feps_spc_link">
            <a href="<?php echo  WPP_F::base_url( FEPS_SPC_PAGE ); ?>"><?php echo $add_credits; ?></a>
          </li>
          <?php endif; ?>
        <?php endif; ?>
        <?php if( !empty( $form_page ) ): ?>
          <li class="wpp_feps_form_page_link">
            <a href="<?php echo $form_page; ?>"><?php echo $add_property; ?></a>
          </li>
        <?php endif; ?>
        <?php if ( $filters == 'true' ) : ?>
          <li class="wpp_feps_filter">
            <h5><?php printf( __("My %s", ud_get_wpp_feps()->domain), WPP_F::property_label( 'plural' ) ); ?><span class="doubledot">:</span></h5>
            <ul>
              <li>
                <a href="<?php echo  WPP_F::base_url( FEPS_VIEW_PAGE, "status=all" ); ?>"><?php _e("All", ud_get_wpp_feps()->domain); ?> (<?php echo $feps_amount; ?>)</a>
              </li>
              <?php foreach ( $feps as $f ) : ?>
              <li>
                <a href="<?php echo  WPP_F::base_url( FEPS_VIEW_PAGE, "status={$f['post_status']}" ); ?>"><?php echo WPP_F::clear_post_status( $f['post_status'] ); ?> (<?php echo $f['amount']; ?>)</a>
              </li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php elseif(is_user_logged_in() ) : ?>
          <li class="wpp_feps_all_listings">
            <a href="<?php echo  WPP_F::base_url( FEPS_VIEW_PAGE ); ?>"><?php

              // Show custom link_text on button. - potanin@UD
              if( $args['link_text'] ) {
                echo $args['link_text'];
              } else {
                printf( __("My %s", ud_get_wpp_feps()->domain), WPP_F::property_label( 'plural' ) );
              }

              ?></a>
          </li>
        <?php endif; ?>
      </ul>

    </div>
    <?php

    echo $after;

    $content = ob_get_clean();

    return $content;
  }
  
  /**
   * Shortcode FEPS Information
   * Renders FEPS Information
   * Shows information about current property on Edit Property Page
   *
   * @param mixed $atts
   * @uathor peshkov@UD
   * @since 2.4
   */
  static public function wpp_feps_info( $atts = '' ) {
    global $wp_query;
    
    $args = shortcode_atts( array(
      'before' => '',
      'after' => '',
      'title' => '',
    ), $atts );
    extract( $args );
    
    //* STEP 1. Go through conditions and set vars */
    
    if( !isset( $wp_query->wpp_feps_edit_page ) || $wp_query->wpp_feps_edit_page !== true ) {
      return null;
    }
    
    //** Determine if the current post is property */
    $post = get_post( $_REQUEST[ 'feps' ] );
    if( empty( $post ) || $post->post_type != "property" ) {
      return null;
    }
    
    //** Determine if the current post belongs to FEPS */
    $form = self::get_form_by_post( $post->ID );
    if( !$form ) {
      return null;
    }

    $expired_time = get_post_meta( $post->ID, FEPS_META_EXPIRED, true );
    $expired_time = !empty( $expired_time ) ? WPP_F::nice_time( $expired_time, array('format'=>'date') ) : __( 'Not set', ud_get_wpp_feps()->domain );
    $sponsored = !empty( $form[ 'feps_credits' ] ) && $form[ 'feps_credits' ] == 'true' ? true : false;
    $plan = get_post_meta( $post->ID, FEPS_META_PLAN, true );
    
    //* STEP 2. Render Menu */
    
    ob_start();

    echo $before;

    ?>
    <div class="wpp_feps_property_info wpp_shortcode_feps_info wpp_shortcode wpp_shortcode_feps_menu wpp_feps_user_menu">
      <?php if( !empty( $title ) ) : ?>
        <h5><?php echo $title; ?></h5>
      <?php endif; ?>
      <ul>
        <li>
          <span class="title"><?php _e("Title", ud_get_wpp_feps()->domain); ?></span><span class="doubledot">:</span>
          <span class="value"><?php echo $post->post_title; ?></span>
        </li>
        <li>
          <span class="title"><?php _e("Status", ud_get_wpp_feps()->domain); ?></span><span class="doubledot">:</span>
          <span class="value"><?php echo  WPP_F::clear_post_status( $post->post_status ); ?></span>
        </li>
        <?php if( !empty( $expired_time ) ): ?>
        <li>
          <span class="title"><?php _e("Expired", ud_get_wpp_feps()->domain); ?></span><span class="doubledot">:</span>
          <span class="value"><?php echo $expired_time; ?></span>
        </li>
        <?php endif; ?>
        <?php if( !empty( $plan ) && is_array( $plan ) ) : ?>
        <li>
          <span class="title"><?php _e("Subscription Plan", ud_get_wpp_feps()->domain); ?></span><span class="doubledot">:</span>
          <span class="value"><?php echo $plan[ 'name' ]; ?></span>
        </li>
        <li>
          <span class="title"><?php _e("Paid", ud_get_wpp_feps()->domain); ?></span><span class="doubledot">:</span>
          <span class="value"><?php printf( __( '%01.2f credits', ud_get_wpp_feps()->domain ), $plan[ 'price' ] ); ?></span>
        </li>
        <li>
          <span class="title"><?php _e("Plan Details", ud_get_wpp_feps()->domain); ?></span><span class="doubledot">:</span><br/>
          <span><?php _e("Duration", ud_get_wpp_feps()->domain); ?></span><span class="doubledot">:</span>
          <span class="value"><?php printf( '%d %s', $plan['duration']['value'], _n( $plan['duration']['interval'], $plan['duration']['interval'].'s', $plan['duration']['value'], ud_get_wpp_feps()->domain ) ); ?></span><br/>
          <span><?php _e("Images Limit", ud_get_wpp_feps()->domain); ?></span><span class="doubledot">:</span>
          <span class="value"><?php echo ( !empty( $plan['images_limit'] ) ? $plan['images_limit'] : '1' ); ?></span>
        </li>
        <?php elseif ( $sponsored ): ?>
        <li>
          <span class="title"><?php _e("Subscription Plan", ud_get_wpp_feps()->domain); ?></span><span class="doubledot">:</span>
          <span class="value"><?php _e( 'Not Paid Yet', ud_get_wpp_feps()->domain ); ?></span>
        </li>
        <?php endif; ?>
      </ul>
    </div>
    <?php

    echo $after;
    $content = ob_get_clean();
    return $content;
  }

  /**
   * Used for returning the FEPS form via shortcode
   *
   * @since 0.1
   */
  static public function wpp_feps_form( $atts = '' ) {
    global $post_id, $wp_properties, $post;

    //** Process args */
    $args = shortcode_atts( array(
      'detect_parent' => 'true',
      'parent_id' => '',
      'map_height' => '400',
      'not_found_text' => __('Requested FEPS form not found.',ud_get_wpp_feps()->domain),
      'form' => '',
      'property_id' => false, //398,
    ), $atts );

    //** Hook something and get control */
    $hook = apply_filters( 'wpp_feps_form_filter', false, $args );
    if ( $hook ) return $hook;

    //** If no forms - return */
    if( 
      !isset( $wp_properties['configuration']['feature_settings']['feps']['forms'] ) || 
      !is_array( $wp_properties['configuration']['feature_settings']['feps']['forms'] ) 
    ) {
      return;
    }
    
    //** Get forms */
    $forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];

    //** Remove empty args */
    foreach($args as $arg => $arg_v) {
      if(empty($arg_v)) {
        unset($args[$arg]);
      }
    }

    //** Check if requested form exists */
    foreach($forms as $form_id => $form) {
      if($form['slug'] == $args['form']) {
        $args['the_form'] = $form;
        $args['form_id'] = $form_id;
        break;
      }
    }

    //** Do nothing if requested form is not found */
    if( !isset( $args['the_form'] ) ) {
      if(current_user_can('administrator')) {
        return $args['not_found_text'];
      } else {
        return;
      }
    }

    //** If there are no fields */
    if( !is_array($args['the_form']['fields']) && !is_array($args['the_form']['required']) ) {
      if(current_user_can('administrator')) {
        return __('The requested form does not have any fields.',ud_get_wpp_feps()->domain);
      } else {
        return;
      }
    }

    if(empty($args['parent_id']) && $args['detect_parent'] == 'true' && $post->post_type == 'property') {
      $args['parent_id'] = $post->ID;
    }

    //** Unset arguments that are not needed later */
    unset($args['detect_parent']);

    //** Flush vars */
    $contents = '';

    //** If we have wpp_front_end_action in request - we know that it is some of the next steps */
    $wpp_front_end_action = !empty( $_REQUEST['wpp_front_end_action'] ) ? $_REQUEST['wpp_front_end_action'] : '';

    $_REQUEST['wpp_front_end_action'] = !empty( $_REQUEST['wpp_front_end_action'] ) ? $_REQUEST['wpp_front_end_action'] : 'edit';

    do_action( 'wpp::feps::shortcode', $args );

    //** Determine step */
    switch( $_REQUEST['wpp_front_end_action'] ) {

      //** Step 2 */
      case 'subscription_plan':

        do_action( 'wpp::feps::shortcode::subscription_plan', $args );

        //** Load specific scripts */
        wp_enqueue_script( 'wpp-feps-subscription', ud_get_wpp_feps()->path( 'static/scripts/wpp.feps.subscription.js' ), array( 'jquery', 'wpp-localization', 'wp-property-global' ) );

        $contents = self::sponsored_listing_sub_plan();
        break;

      //** Step 3 */
      case 'checkout':

        do_action( 'wpp::feps::shortcode::checkout', $args );

        //** Load specific scripts */
        wp_enqueue_script( 'wpp-feps-checkout', ud_get_wpp_feps()->path( 'static/scripts/wpp.feps.checkout.js' ), array( 'jquery', 'wpp-localization', 'wp-property-global' ) );

        $contents = self::sponsored_listing_purchase();
        break;

      //** Step 1 */
      case 'edit':

        do_action( 'wpp::feps::shortcode::edit', $args );

        //** Load specific scripts */
        wp_enqueue_script( 'wpp-feps-submit', ud_get_wpp_feps()->path( 'static/scripts/wpp.feps.submit.js' ), array(
          'jquery',
          'wpp-localization',
          'wpp-jquery-gmaps',
          'wp-property-global',
          'wpp-jquery-number-format',
          'wpp-jquery-ajaxupload',
          'wpp-jquery-validate',
        ) );

        $user_can_publish_properties = 'false';
        $form_id                     = rand(99, 9999999);
        $this_session                = rand(99, 9999999);
        $current_user                = wp_get_current_user();
        $nonce                       = ud_get_wpp_feps()->generate_nonce('submit_feps');
        $thumbnail_size              = WPP_F::image_sizes($args['the_form']['thumbnail_size']);
        $images_limit                = ( isset( $args['the_form']['feps_credits'] ) && $args['the_form']['feps_credits'] == 'true' ) ? 1 : $args['the_form']['images_limit'];

        $user_logged_in = is_user_logged_in() ? true : false;

        if ( user_can( $current_user, 'publish_wpp_properties' ) ) {
          $user_can_publish_properties = 'true';
        }

        /** Check if we have Edit FEPS */
        if ( $user_logged_in && !empty( $args['property_id'] ) ){

          $property = get_property( $args['property_id'] );

          if( empty($property) ){
            return (current_user_can('administrator')) ? sprintf(__('Requested %1$s not found.',ud_get_wpp_feps()->domain), ucfirst(WPP_F::property_label('singular'))) : null;
          }

          if ( empty($property['wpp_feps_property']) ){
            return (current_user_can('administrator')) ? sprintf(__('It\'s not FEPS\'s %1$s',ud_get_wpp_feps()->domain), ucfirst(WPP_F::property_label('singular'))) : null;
          }

          /** check if property belongs to current user */
          if( $current_user->data->ID != $property['post_author'] ){
            return (current_user_can('administrator')) ? sprintf(__('It\'s not your %1$s',ud_get_wpp_feps()->domain), WPP_F::property_label('singular') ) : null;
          }

          if ( empty($property[ FEPS_META_FORM ]) || $wp_properties['configuration']['feature_settings']['feps']['forms'][$property[ FEPS_META_FORM ]]['slug']!=$args['form'] ){
            return (current_user_can('administrator')) ? __("Wrong FEPS form used",ud_get_wpp_feps()->domain) : null;
          }

          /** check option  "Allow user to edit and remove his FEPS". */
          if ( empty( $args['the_form']['can_manage_feps'] ) || $args['the_form']['can_manage_feps'] != 'true' ) {
            return (current_user_can('administrator')) ? sprintf(__('Managing of %1$s are not allowed',ud_get_wpp_feps()->domain), WPP_F::property_label('singular') ) : null;
          }

          /** we should pass all conditions to leave template with tabs */
          if ( !($property['post_status'] == 'pending' &&
              !empty($property[ FEPS_META_FORM ]) &&
              isset($wp_properties['configuration']['feature_settings']['feps']['forms'][$property[ FEPS_META_FORM ]]["feps_credits"]) &&
              $wp_properties['configuration']['feature_settings']['feps']['forms'][$property[ FEPS_META_FORM ]]["feps_credits"]=='true'
              )  ){
            remove_filter('wpp_feps_form_output', array(__CLASS__, 'feps_output_filter'));
          }

          $plan = isset( $property[ FEPS_META_PLAN ] ) ? maybe_unserialize( $property[ FEPS_META_PLAN ] ) : false;
          $images_limit = ( is_array( $plan ) ) ? $plan[ 'images_limit' ] : $images_limit;

        }

        /** Build element array from required fields. */
        if(is_array($args['the_form']['required'])) {
          foreach($args['the_form']['required'] as $attribute_data) {
            if(isset($attribute_data['hide_title']) && $attribute_data['hide_title'] == 'on')
              continue;
            $this_field = WPP_F::get_attribute_data( $attribute_data['attribute'] );
            $this_field['required'] = 'on';
            $this_field['description'] = $attribute_data['description'];
            $this_field['title'] = $attribute_data['title'];
            $this_field['value'] = ( (!empty($property[$attribute_data['attribute']])) ? $property[$attribute_data['attribute']] : '' ) ;

            $form_fields[rand(99,9999999)] = $this_field;
          }
        }

        $taxonomies = array_keys($wp_properties[ 'taxonomies' ]);
        /** Build element array from regular fields. */
        if(is_array($args['the_form']['fields'])) {
          foreach($args['the_form']['fields'] as $attribute_data) {
            $this_field = WPP_F::get_attribute_data($attribute_data['attribute']);
            $this_field['required'] = ( isset( $attribute_data['required'] ) && $attribute_data['required'] ? true : false );
            $this_field['description'] = $attribute_data['description'];
            $this_field['title'] = $attribute_data['title'];
            $this_field['value'] = ( isset( $property[$attribute_data['attribute']] ) && $property[$attribute_data['attribute']] ? $property[$attribute_data['attribute']] : false );

            $form_fields[rand(99,9999999)] = $this_field;
          }
        }

        $form_fields = stripslashes_deep($form_fields);

        //** Find template */
        $content_template_found = WPP_F::get_template_part( array(
          "feps-submit-template"
        ), array( ud_get_wpp_feps()->path( 'static/views', 'dir' ) ) );

        ob_start();
        include $content_template_found;
        $contents = ob_get_clean();
        break;
    }

    return apply_filters('wpp_feps_form_output', $contents, $args);
  }

  /**
   * Renders the UI for form creation
   *
   * @since 0.1
   */
  static public function page_feps() {
    global $wp_properties, $wp_post_statuses;

    $wp_post_statuses = apply_filters( 'wpp_feps_property_statuses', $wp_post_statuses );

    if( empty($wp_properties['configuration']['feature_settings']['feps']) ) {
      //** Load default settings into global variable */
      class_wpp_feps::load_defaults();
    }

    if( isset( $_REQUEST['message'] ) ) {
      switch( $_REQUEST['message'] ) {
        case 'updated':
          $wp_messages['notice'][] = __("FEPS forms updated.", ud_get_wpp_feps()->domain);
        break;
      }
    }

    $feps_forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];

    $general_attributes = array( 'General' => array(
      'post_content' => sprintf( __( '%1$s Content', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'singular' ) ),
      'property_type' => sprintf( __( '%1$s Type', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'singular' ) ),
    ) );
    if(array_key_exists('wpp_type', $wp_properties[ 'taxonomies' ])){
      unset($general_attributes['General']['property_type']);
    }

    $other_attributes = WPP_F::get_total_attribute_array('use_optgroups=true', array(
      'image_upload' => __('Image Upload', ud_get_wpp_feps()->domain)
     ));
    
    /** 
     * Prevent appearing "property_type" attribute in list Property Attributes
     * in case if someone adds "property_type" on Developer Tab.
     * This param is required and it alredy set in General Attributes.
     */
    foreach ( $other_attributes as &$group_attributes ) {
      if ( is_array( $group_attributes ) && isset( $group_attributes[ "property_type" ] ) ) {
        unset( $group_attributes[ "property_type" ] );
      }
    }
    $_taxonomies  = array();
    foreach ($wp_properties[ 'taxonomies' ] as $key => $value) {
      $_taxonomies[$key] = $value['label'];
    }
    $taxonomies = array( __("Taxonomies") => $_taxonomies);
    $available_attributes = $general_attributes + $taxonomies + $other_attributes;

    $available_attributes = apply_filters( 'wpp_feps_available_attributes', $available_attributes, $general_attributes, $other_attributes );

    ?>
    <div class="wrap wpp_feps_wrapper wpp_settings_page">
      <h2><?php echo sprintf(__('Front End %1$s Submissions', ud_get_wpp_feps()->domain), ucfirst(WPP_F::property_label('singular'))); ?>
      <span class="wpp_add_tab add-new-h2"><?php _e('Add New', ud_get_wpp_feps()->domain); ?></span>
      </h2>

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

      <form id="save_form" action="<?php echo admin_url('edit.php?post_type=property&page=page_feps&message=updated'); ?>" method="POST">
        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpp_save_feps_page'); ?>" />
        <input class="current_tab" type="hidden" name="current_tab" value="" />

      <div class="wpp_feps_tabs wpp_tabs">

        <ul class="tabs">
          <?php foreach($feps_forms as $form_id => $form) { ?>
            <li feps_form_id="<?php echo esc_attr($form_id); ?>"><a href="#feps_form_<?php echo $form_id; ?>"><span><?php echo $form['title']; ?></span></a></li>
          <?php } ?>
        </ul>
        <?php foreach( $feps_forms as $form_id => $form ) {
          //** Fill out one default field if 'fields' is empty */
          if ( empty( $form['fields'] ) ) {
            $form['fields'] = class_wpp_feps::load_default_field();
          }
        ?>
        <div id="feps_form_<?php echo $form_id; ?>" class="wpp_feps_form ui-tabs-panel" feps_form_id="<?php echo esc_attr($form_id); ?>">

          <!-- Settings form table -->
          <table class="form-table wpp_option_table">
            <tr>
              <th>
                <p><strong><?php _e('Form Name:', ud_get_wpp_feps()->domain); ?></strong></p>
              </th>
              <td>
                <input size="60" class="form_title" type="text" name="wpp_feps[forms][<?php echo $form_id; ?>][title]" value="<?php echo esc_attr($form['title']); ?>" />
                <br/><span class="description">
                  <?php _e('Enter form name. Form slug will be generated using form name. Note that Form Name is used for internal reference.', ud_get_wpp_feps()->domain); ?>
                </span>
                <input type="hidden" class="slug" name="wpp_feps[forms][<?php echo $form_id; ?>][slug]" value="<?php echo esc_attr($form['slug']); ?>" />
              </td>
            </tr>
            <tr>
              <th>
                <p><strong><?php _e('Shortcode:', ud_get_wpp_feps()->domain); ?></strong></p>
              </th>
              <td>
                <input size="60" class="shortcode" type="text" readonly="true" value="[wpp_feps_form form=<?php echo esc_attr($form['slug']); ?>]" />
                <br/><span class="description">
                  <?php _e('Copy and paste this shortcode into any page or post to display the form', ud_get_wpp_feps()->domain); ?>
                </span>
              </td>
            </tr>
            <?php do_action('wpp_feps_form_settings_before_general', array('form'=>$form, 'form_id'=>$form_id)); ?>
            <tr>
              <th>
                <p><strong><?php _e('General Options:', ud_get_wpp_feps()->domain); ?></strong></p>
                <div class="description"><p></p></div>
              </th>
              <td>
                <?php $hidden = ( (class_exists('wpi_spc') && !empty( $form['feps_credits'] ) && $form['feps_credits'] == 'true' ) ? 'style="display:none;"' : '' ); ?>
                <ul>
                  <?php do_action('wpp_feps_form_settings_general_top', array('form'=>$form, 'form_id'=>$form_id)); ?>
                  <li <?php echo $hidden; ?>>
                    <?php echo sprintf(__('%1$s Status:', ud_get_wpp_feps()->domain), ucfirst(WPP_F::property_label('singular'))); ?>
                    <select name="wpp_feps[forms][<?php echo $form_id; ?>][new_post_status]">
                    <?php foreach($wp_post_statuses as $post_status => $post_status_data) { ?>
                      <option <?php selected($post_status, $form['new_post_status']); ?> value="<?php echo esc_attr($post_status); ?>"><?php echo $post_status_data->label; ?></option>
                    <?php } ?>
                    </select>
                  </li>
                  <li>
                    <?php printf( __('%1$s Type:', ud_get_wpp_feps()->domain), WPP_F::property_label() ); ?>
                    <select name="wpp_feps[forms][<?php echo $form_id; ?>][property_type]">
                    <?php foreach($wp_properties['property_types'] as $pt_slug => $pt_label) {  ?>
                      <option <?php if( !empty( $form['property_type'] ) ) selected( $pt_slug, $form['property_type'] ); ?> value="<?php echo esc_attr($pt_slug); ?>"><?php echo $pt_label; ?></option>
                    <?php } ?>
                    </select>
                    <span class="description"><?php printf( __('Note: This type will be set by default if %1$s Type is not set in %1$s Attributes below.', ud_get_wpp_feps()->domain ), WPP_F::property_label() ); ?></span>
                  </li>
                  <li>
                    <?php _e('Automatically remove FEPS of the currrent Form with \'Pending\' status to trash in', ud_get_wpp_feps()->domain); ?>
                    <input class="wpp_number" name="wpp_feps[forms][<?php echo $form_id; ?>][trash_time]" type="text" value="<?php echo esc_attr(abs((int)$form['trash_time'])); ?>" />
                    <?php _e('days.', ud_get_wpp_feps()->domain); ?>
                    <span class="description"><?php _e('Note: If value is 0 or empty, FEPS will not be removed.', ud_get_wpp_feps()->domain); ?></span>
                  </li>
                  <li>
                    <?php _e('Preview Thumbnail Size:', ud_get_wpp_feps()->domain); ?>
                    <?php WPP_F::image_sizes_dropdown("name=wpp_feps[forms][{$form_id}][thumbnail_size]&selected={$form['thumbnail_size']}"); ?>
                  </li>
                  <li <?php echo $hidden; ?>>
                    <?php _e('Image Upload Limit:', ud_get_wpp_feps()->domain); ?>
                    <input class="imageslimit wpp_number" name="wpp_feps[forms][<?php echo $form_id; ?>][images_limit]" type="text" value="<?php echo esc_attr(abs((int)$form['images_limit'])); ?>" />
                  </li>
                  <li>
                    <?php _e('New User Role:', ud_get_wpp_feps()->domain); ?>
                    <select class="wp_crm_role" name="wpp_feps[forms][<?php echo $form_id; ?>][new_role]">
                      <option value=""></option>
                      <?php wp_dropdown_roles($form['new_role']); ?>
                    </select>
                  </li>
                  <?php if( class_exists( 'class_wpp_slideshow' ) ) : ?>
                    <li>
                      <input id="auto_add_to_slideshow_<?php echo $form_id; ?>" type="checkbox" name="wpp_feps[forms][<?php echo $form_id; ?>][add_to_slideshow]" <?php if( isset( $form['add_to_slideshow'] ) ) checked('true', $form['add_to_slideshow']); ?> value="true" />
                      <label for="auto_add_to_slideshow_<?php echo $form_id; ?>"><?php printf( __( 'Automatically load added images into %1$s Slideshow. ', ud_get_wpp_feps()->domain ), WPP_F::property_label() ); ?></label>
                      <span class="description"><?php printf( __('Note: You can use [property_slideshow] shortcode on Single %1$s page to show added images. Also, some premium themes such as Denali support automatic slideshow view on single %1$s pages.', ud_get_wpp_feps()->domain ), WPP_F::property_label() ); ?></span>
                    </li>
                  <?php endif; ?>
                  <li>
                    <?php _e('Notify Email (when form submitted)', ud_get_wpp_feps()->domain); ?>
                    <input class="notify-admin-email" type="email" name="wpp_feps[forms][<?php echo $form_id; ?>][notifications][notify_admin_email]" value="<?php echo ! empty( $form['notifications']['notify_admin_email'] ) ? esc_attr($form['notifications']['notify_admin_email']) : ''; ?>" />
                    <span class="description"><?php printf( __('Note: You can set notification to this email when this form is submitted.', ud_get_wpp_feps()->domain ) ); ?></span>
                  </li>
                  <li>
                    <input id="can_manage_feps_<?php echo $form_id; ?>" type="checkbox" name="wpp_feps[forms][<?php echo $form_id; ?>][can_manage_feps]" <?php if( isset( $form['can_manage_feps'] ) ) checked('true', $form['can_manage_feps']); ?> value="true" />
                    <label for="can_manage_feps_<?php echo $form_id; ?>"><?php _e('Allow user to edit and remove his FEPS.', ud_get_wpp_feps()->domain); ?></label>
                    <span class="description"><?php _e('Note: on remove FEPS will be just moved to trash.', ud_get_wpp_feps()->domain); ?></span>
                  </li>
                  <li <?php echo $hidden; ?>>
                    <input id="user_creation_<?php echo $form_id; ?>" type="checkbox" name="wpp_feps[forms][<?php echo $form_id; ?>][notifications][user_creation]" <?php if( isset( $form['notifications']['user_creation'] ) ) checked('disable', $form['notifications']['user_creation']); ?> value="disable" />
                    <label for="user_creation_<?php echo $form_id; ?>"><?php _e('Disable new user account creation notification.', ud_get_wpp_feps()->domain); ?></label>
                  </li>
                  <li>
                    <input id="on_status_updated_<?php echo $form_id; ?>" type="checkbox" name="wpp_feps[forms][<?php echo $form_id; ?>][notifications][on_status_updated]" <?php if( isset( $form['notifications']['on_status_updated'] ) ) checked('true', $form['notifications']['on_status_updated']); ?> value="true" />
                    <label for="on_status_updated_<?php echo $form_id; ?>"><?php _e('Notify user on update status of his FEPS.', ud_get_wpp_feps()->domain); ?></label>
                  </li>
                </ul>
              </td>
            </tr>
            <tr>
              <th>
                <p><strong><?php _e('Map options:', ud_get_wpp_feps()->domain); ?></strong></p>
                <div class="description"><p></p></div>
              </th>
              <td>
                <ul>
                  <?php
                    $default_coords_latitude = 57.7973333;
                    $default_coords_longitude = 12.0502107;

                  if(isset( $wp_properties[ 'default_coords' ][ 'latitude' ] )){
                    $default_coords_latitude = $wp_properties[ 'default_coords' ][ 'latitude' ];
                  }
                  if(isset( $wp_properties[ 'default_coords' ][ 'longitude' ] )){
                    $default_coords_longitude = $wp_properties[ 'default_coords' ][ 'longitude' ];
                  }
                  ?>
                  <li><?php _e( 'Custom Latitude Coordinate', ud_get_wpp_feps()->domain ); ?>
                    : <?php //echo WPP_F::input( "name=wpp_feps[forms]['. $form_id .'][custom_coords][latitude]&style=width: 100px;", ( isset( $wp_properties[ 'custom_coords' ][ 'latitude' ] ) ? $wp_properties[ 'custom_coords' ][ 'latitude' ] : false ) ); ?>
                    <input class="wpp_latitude" name="wpp_feps[forms][<?php echo $form_id; ?>][custom_coords][latitude]" type="text" value="<?php echo !empty($form['custom_coords']['latitude']) ? esc_attr((float)$form['custom_coords']['latitude']) : $default_coords_latitude ?>" />
                    <span class="description"><?php printf( __( 'Default is "%s"', ud_get_wpp_feps()->domain ), $default_coords_latitude ); ?></span></li>
                  <li><?php _e( 'Custom Longitude Coordinate', ud_get_wpp_feps()->domain ); ?>
                    : <?php //echo WPP_F::input( "name=wpp_feps[forms]['. $form_id .'][custom_coords][longitude]&style=width: 100px;", ( isset( $wp_properties[ 'custom_coords' ][ 'longitude' ] ) ? $wp_properties[ 'custom_coords' ][ 'longitude' ] : false ) ); ?>
                    <input class="wpp_longitude" name="wpp_feps[forms][<?php echo $form_id; ?>][custom_coords][longitude]" type="text" value="<?php echo !empty($form['custom_coords']['longitude']) ? esc_attr((float)$form['custom_coords']['longitude']) : $default_coords_longitude ?>" />
                    <span class="description"><?php printf( __( 'Default is "%s"', ud_get_wpp_feps()->domain ), $default_coords_longitude ); ?></span></li>
                </ul>
              </td>
            </tr>
            <?php do_action('wpp_feps_form_settings_after_general', array('form'=>$form, 'form_id'=>$form_id)); ?>
            <tr>
              <th colspan="2">
                <h3 style="float:left;"><?php echo sprintf(__( '%1$s Attributes', ud_get_wpp_feps()->domain ), ucfirst(WPP_F::property_label('singular'))); ?></h3>
                <table class="ud_ui_dynamic_table widefat wpp_feps_sortable" use_random_row_id="true">
                  <thead>
                    <tr>
                      <th class="wpp_draggable_handle"></th>
                      <th><?php _e('Main', ud_get_wpp_feps()->domain); ?></th>
                      <th><?php _e('Description', ud_get_wpp_feps()->domain); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="wpp_dynamic_table_row required" random_row_id="post_title">
                      <td class="wpp_draggable_handle"></td>
                      <td class="main_feps">
                        <ul>
                          <li>
                            <label><?php _e('Attribute', ud_get_wpp_feps()->domain); ?></label>
                            <input type="hidden" name="wpp_feps[forms][<?php echo $form_id; ?>][required][post_title][attribute]" value="post_title" />
                            <select disabled="disabled" class="wpp_feps_new_attribute">
                              <option><?php echo sprintf(__('%1$s Title', ud_get_wpp_feps()->domain), ucfirst(WPP_F::property_label('singular'))); ?></option>
                            </select>
                          </li>
                          <li class="wpp_development_advanced_option">
                            <label><?php _e('Title', ud_get_wpp_feps()->domain); ?></label>
                            <input type="text" class="title" name="wpp_feps[forms][<?php echo $form_id; ?>][required][post_title][title]" value="<?php echo $form['required']['post_title']['title'] ?>" />
                          </li>
                          <li class="wpp_development_advanced_option">
                            <label><?php _e('Auto Generate', ud_get_wpp_feps()->domain); ?></label>
                            <input type="text" class="title" name="wpp_feps[forms][<?php echo $form_id; ?>][required][post_title][auto_generate_rule]" value="<?php echo !empty($form['required']['post_title']['auto_generate_rule'])?$form['required']['post_title']['auto_generate_rule']:''; ?>" placeholder="Ex. [title] in [location]"/>
                          </li>
                          <li class="wpp_development_advanced_option">
                            <input id="hide_title" type="checkbox" name="wpp_feps[forms][<?php echo $form_id; ?>][required][post_title][hide_title]" 
                            <?php if( !empty($form['required']['post_title']['hide_title'])) checked('on', $form['required']['post_title']['hide_title']); ?> />
                            <label for="hide_title" style="width: auto;"><?php _e('Hide title in form', ud_get_wpp_feps()->domain); ?></label>
                          </li>
                          <li>
                            <span class="wpp_show_advanced"><?php _e('Toggle Advanced Settings', ud_get_wpp_feps()->domain); ?></span>
                          </li>
                        </ul>
                      </td>
                      <td>
                        <textarea class="description wpp_full_width" name="wpp_feps[forms][<?php echo $form_id; ?>][required][post_title][description]"><?php echo $form['required']['post_title']['description'] ?></textarea>
                      </td>
                    </tr>
                    <?php
                      if ( !empty( $form['fields'] ) ) {
                        foreach($form['fields'] as $row_id => $field_data) {
                          $field_data = stripslashes_deep($field_data);
                    ?>
                    <tr class="wpp_dynamic_table_row feps_attribute_row" random_row_id="<?php echo $row_id; ?>">
                      <td class="wpp_draggable_handle"></td>
                      <td class="main_feps">
                        <ul>
                          <li>
                            <label><?php _e('Attribute', ud_get_wpp_feps()->domain); ?></label>
                            <select  name="wpp_feps[forms][<?php echo $form_id; ?>][fields][<?php echo $row_id; ?>][attribute]"  class="wpp_feps_new_attribute feps_attribute">
                              <option></option>
                              <?php foreach($available_attributes as $group_label => $opt_group) { ?>
                                <optgroup label="<?php echo esc_attr($group_label); ?>">
                                <?php foreach($opt_group as $attribute => $label) { ?>
                                <option <?php selected($field_data['attribute'], $attribute); ?> value="<?php echo esc_attr($attribute); ?>"><?php echo esc_attr($label); ?></option>
                                <?php } ?>
                                </optgroup>
                              <?php } ?>
                            </select>
                          </li>
                          <li class="wpp_development_advanced_option">
                            <label><?php _e('Title', ud_get_wpp_feps()->domain); ?></label>
                            <input type="text" class="title" name="wpp_feps[forms][<?php echo $form_id; ?>][fields][<?php echo $row_id; ?>][title]" value="<?php echo $field_data['title']; ?>" />
                          </li>
                          <li class="wpp_development_advanced_option is_required">
                            <input id="required_<?php echo $form_id; ?>_<?php echo $row_id; ?>" type="checkbox" name="wpp_feps[forms][<?php echo $form_id; ?>][fields][<?php echo $row_id; ?>][required]" <?php if( isset( $field_data['required'] ) ) checked('on', $field_data['required']); ?> />
                            <label for="required_<?php echo $form_id; ?>_<?php echo $row_id; ?>"><?php _e('Required', ud_get_wpp_feps()->domain); ?></label>
                          </li>
                          <li class="wpp_development_advanced_option">
                            <a class="wpp_forms_remove_attribute" row="<?php echo $row_id; ?>" href="javascript:void(0);"><?php _e( 'Remove attribute', ud_get_wpp_feps()->domain ); ?></a>
                          </li>
                          <li>
                            <span class="wpp_show_advanced"><?php _e('Toggle Advanced Settings', ud_get_wpp_feps()->domain); ?></span>
                          </li>
                        </ul>
                      </td>
                      <td>
                        <textarea class="description wpp_full_width" name="wpp_feps[forms][<?php echo $form_id; ?>][fields][<?php echo $row_id; ?>][description]"><?php echo $field_data['description']; ?></textarea>
                      </td>
                    </tr>
                    <?php
                        }
                      }
                    ?>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="3">
                        <input type="button" callback_function="wpp.ui.feps.on_added_row" class="wpp_add_row button-secondary" value="<?php _e('Add Row',ud_get_wpp_feps()->domain) ?>" />
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </th>
            </tr>
            <?php do_action('wpp_feps_form_settings_right', array('form'=>$form, 'form_id'=>$form_id)); ?>
          </table>
          <!-- EO Settings form table -->

        </div>
        <div class="clear"></div>
        <?php } ?>
      </div>
      <br class="cb" />
      <p class="wpp_save_changes_row">
        <input type="submit"  value="<?php _e('Save Changes',ud_get_wpp_feps()->domain);?>" class="button-primary btn" name="Submit" />
      </p>

    </div>

    <?php
  }

  /**
   * Loads 'My FEPS Listings' page
   *
   * @author peshkov@UD
   * @since 2.0
   * @version 0.1
   */
  static public function my_feps_page_load() {
    global $wpdb, $wp_query, $wp_properties;

    //** STEP 1. Check permissions */

    //** Determine if user is logged in */
    if ( !is_user_logged_in() ) {
      die( wp_redirect( site_url('') ) );
    }
    $user = wp_get_current_user();

    //** STEP 2. Determine if feps should be removed, check permissions, remove it and reload page */
    //** Determine if user has any FEP submission already. */
    $feps = $wpdb->get_col("
      SELECT p.ID FROM {$wpdb->posts} AS p
        JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.ID
        WHERE pm.meta_key = 'wpp_feps_property'
          AND p.post_author = '{$user->ID}'
    ");

    if( !empty( $_REQUEST['feps'] ) &&
        !empty( $_REQUEST['action'] ) &&
        !empty( $_REQUEST['hash'] ) &&
        $_REQUEST['action'] == 'remove' &&
        in_array( $_REQUEST['feps'], $feps )
    ) {
      $post_id = (int)$_REQUEST['feps'];
      //** Check if hash is correct and move FEPS to trash */
      if ( $_REQUEST['hash'] == get_post_meta( $post_id, 'wpp::feps::pending_hash', true ) ) {
        $form_id = get_post_meta( $post_id, FEPS_META_FORM, true );
        $forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];
        if ( $form_id && !empty( $forms[$form_id]['can_manage_feps'] ) && $forms[$form_id]['can_manage_feps'] == 'true' ) {
          //** Move FEPS to trash */
          wp_trash_post($post_id);
        }
      }
      die( wp_redirect( WPP_F::base_url( FEPS_VIEW_PAGE ) ) );
    }

    //** STEP 3. Try to get templates */
    
    $template = false;
    if( !empty( $wp_properties['configuration']['feps']['templates']['overview_page'] ) ) {
      $template = $wp_properties['configuration']['feps']['templates']['overview_page'];
      $template = str_replace( '.php', '', $template );
    }
    
    //** Load the first found general template */
    $template_found = WPP_F::get_template_part( array_filter( array(
      $template,
      'page',
      'single',
    ) ) );

    //* Determine if all necessary templates are loaded and finish loading. */
    if( $template_found ) {

      //* Set filter data */
      $status = 'publish,pending';
      $additional_title = '';
      if ( !empty( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], array('publish','pending', 'all') ) ) {
        if ( $_REQUEST['status'] == 'all' ) {
          $additional_title = "(" . __('All', ud_get_wpp_feps()->domain) . ")";
        } else {
          $additional_title = "(" . WPP_F::clear_post_status( $_REQUEST['status'] ) . ")";
          $status = $_REQUEST['status'];
        }
      }

      //** Create a fake WP Listings / Search Page */
      $wp_query->is_404 = false;
      $wp_query->post_count = 1;
      $wp_query->posts[0] = (object) array(
        'ID' => '999999',
        'post_title' => ( __('My FEPS Listings', ud_get_wpp_feps()->domain) . " " . $additional_title ),
        'post_content' => "[property_overview post_author={$user->ID} wpp_feps_property=true post_status={$status} template=my-feps]",
        'post_name' => 'my_feps',
        'post_type' => 'page',
        'post_date' => date( 'Y-m-d H:i:s', time() ),
        'post_status' => 'publish',
        'post_author' =>  $user->ID
      );

      die( load_template( $template_found ) );

    } else {

      WPP_F::console_log( 'Could not find MY FEPS template.' );

      $wp_query->is_404 = true;

    }

  }

  /**
   * Loads 'FEPS Edit Listing' page
   *
   * @author peshkov@UD
   * @since 1.4
   * @version 0.1
   */
  static public function feps_edit_page_load() {
    global $wpdb, $wp_query, $property, $wp_properties;

    //** STEP 1. Check property and permissions */

    //** Determine if user is logged in */
    if ( !is_user_logged_in() || !isset($_REQUEST['feps']) || !(int)$_REQUEST['feps'] > 0 ) {
      die( wp_redirect( site_url('') ) );
    }
    $user = wp_get_current_user();

    $property = WPP_F::get_property( (int)$_REQUEST['feps'] );

    //** Check property */
    if( !$property ||
        $property['ID'] != $_REQUEST['feps'] ||
        empty( $property[ FEPS_META_FORM ] ) ||
        empty( $wp_properties['configuration']['feature_settings']['feps']['forms'][$property[ FEPS_META_FORM ]] ) ||
        $property['wpp_feps_property'] != true ||
        $user->ID != $property['post_author']
    ) {
      die( wp_redirect( site_url('') ) );
    }

    $feps_form = $wp_properties['configuration']['feature_settings']['feps']['forms'][$property[ FEPS_META_FORM ]]['slug'];

    //* STEP 2. Try to get and load template */
    
    $template = false;
    if( !empty( $wp_properties['configuration']['feps']['templates']['edit_page'] ) ) {
      $template = $wp_properties['configuration']['feps']['templates']['edit_page'];
      $template = str_replace( '.php', '', $template );
    }
    
    //** Load the first found default template */
    $template_found = WPP_F::get_template_part( array_filter( array(
      $template,
      'page',
      'single',
    ) ) );

    //* STEP 3. */

    $post_content = "[wpp_feps_form form={$feps_form} property_id={$property['ID']} action=edit]";

    //* Determine if template is found and finish loading. */
    if( $template_found ) {

      //** Create a fake Page */
      $wp_query->is_404 = false;
      $wp_query->post_count = 1;
      $wp_query->posts[0] = (object) array(
        'ID' => $property['ID'],
        'post_title' => $property['post_title'] . " " . __('Editing', ud_get_wpp_feps()->domain),
        'post_content' => WPP_F::minify_js($post_content),
        'post_name' => $property['post_name'],
        'post_type' => 'page',
        'post_date' => date( 'Y-m-d H:i:s', time() ),
        'post_status' => 'publish',
        'post_author' =>  $user->ID
      );

      die( load_template( $template_found ) );

    } else {

      WPP_F::console_log( 'Could not find template for FEPS Edit page.' );

      $wp_query->is_404 = true;

    }
  }

  /**
   * Save USP settings
   *
   * @since 0.1
   */
  static public function save_feps_settings($settings) {
    global $wp_properties;

    if ( !empty( $settings['forms'] ) ) {
      foreach( $settings['forms'] as $form_k => $form ) {

        if ( !empty( $form[ 'fields' ] ) && is_array( $form[ 'fields' ] ) ) {
          foreach ( $form[ 'fields' ] as $k => $v ) {
            if ( empty( $v[ 'attribute' ] ) ) {
              unset( $settings[ 'forms' ][$form_k][ 'fields' ][$k] );
            }
          }
        }

        //** Duration value, Images Limit and price must be at least 1 and higher. */
        if( !empty( $form[ 'subscription_plans' ] ) && is_array( $form[ 'subscription_plans' ] ) ) {
          foreach ( $form[ 'subscription_plans' ] as $k => $v ) {
            if( empty( $v[ 'price' ] ) || !(float)$v[ 'price' ] > 0 ) {
              $settings['forms'][ $form_k ][ 'subscription_plans' ][ $k ][ 'price' ] = '1';
            }
            if( empty( $v[ 'images_limit' ] ) || !(int)$v[ 'images_limit' ] > 0 ) {
              $settings['forms'][ $form_k ][ 'subscription_plans' ][ $k ][ 'images_limit' ] = '1';
            }
            if( is_array( $v[ 'duration' ] ) &&  ( empty( $v[ 'duration' ][ 'value' ] ) || !(int)$v[ 'duration' ][ 'value' ] > 0 ) ) {
              $settings['forms'][ $form_k ][ 'subscription_plans' ][ $k ][ 'duration' ][ 'value' ] = '1';
            }
          }
        }
      }
    }

    $wp_properties['configuration']['feature_settings']['feps'] = $settings;
    update_option('wpp_settings', $wp_properties);
    return $wp_properties;
  }


  /**
   * Modifies the way input fields are renderd
   *
   * $data array includes:
   * - this_session - FEPS form settings
   * - att_data - data about the attribute
   * - row_id - unique ID of row
   * - args - FEPS form settings
   * - property - array of property data (if exists)
   *
   * $data['att_data'] includes:
   * - slug
   * - value
   * - required
   * - ui_class
   * - title
   * - description
   * - input_type
   * - storage_type
   * - is_meta
   *
   * @since 0.1
   *
   */
  static public function wpp_feps_input( $data ) {
    global $wp_properties;
      $this_session = false;
      $form_dom_id = '';
      $att_data = '';
      $row_id = '';
      $args = array();
      $property = false;
      $images_limit = 0;
      $return = false;
      $form_id = 0;
    $data = wp_parse_args( $data, array(
      'this_session' => false,
      'form_dom_id' => '',
      'att_data' => array(),
      'row_id' => '',
      'args' => array(),
      'property' => false,
      'images_limit' => 0,
      'return' => false,
      'form_id' => 0
    ));

    extract( $data );

    $styled = '';

    /** Use this only for FEPS with Denali installed */
    if ( strstr(get_option('template'), 'denali') ) {
      $styled = ' styled';
    }
    if($att_data['storage_type'] == 'taxonomy'){
        $availableTags = explode(',', $att_data['predefined_values']);
        $availableTags = array_map("trim", $availableTags);
        $att_data['data_input_type'] = 'taxonomy';
    }
    //** Try to get data input type first, then user regular (search) input type */
    $input_type = ( !empty( $att_data['data_input_type'] ) ? $att_data['data_input_type'] : ( isset( $att_data['input_type'] ) ? $att_data['input_type'] : false ) );
    //** If dropdown, load predefined values */
    if( $input_type == 'dropdown' ) {
      if ( !empty( $att_data['predefined_values'] ) ) {
        $values = explode(',', $att_data['predefined_values']);
        foreach( $values as $k => $v ) {
          $values[$k] = trim( $v );
        }
        $values = array_combine( $values, $values );
      } else {
        $values = array();
      }
    }
    
    if( isset( $att_data[ 'slug' ] ) && $att_data[ 'slug' ] == 'property_type' || $att_data[ 'slug' ] == 'wpp_type' ) {
      $input_type = 'dropdown';
      $att_data['options'] = array();
      unset($att_data['predefined_values']);
      $values = $wp_properties[ 'property_types' ];
      foreach ($wp_properties[ 'property_types' ] as $key => $value) {
        $att_data['options'][$key] = $value;
      }
    }

    if ( !empty( $att_data['ui_class'] ) ) {
      $att_data['ui_class'] .= $att_data['required'] == 'on' || $att_data['required'] == '1' ? ' required' : '';
    } else {
      $att_data['ui_class'] = $att_data['required'] == 'on' || $att_data['required'] == '1' ? ' required' : '';
    }

    ob_start();

    if($input_type == "color"){
      //WP registeer color picker only for backend.
      //wp_enqueue_script( 'wp-color-picker', admin_url( 'js/farbtastic.js' ));
      wp_enqueue_script( 'iris', admin_url( 'js/iris.min.js' ), array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ));
      wp_enqueue_script( 'wp-color-picker', admin_url( 'js/color-picker.min.js' ), array( 'iris' ));
      wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', array(
      'clear' => __( 'Clear' ),
      'defaultString' => __( 'Default' ),
      'pick' => __( 'Select Color' ),
      'current' => __( 'Current Color' ),
    ) );
    }
    elseif($input_type == "file_input" && !is_user_logged_in()){
        $input_type = "text";
    }

    $value = !empty( $property[$att_data['slug']] ) ? $property[$att_data['slug']] : '';
    

    switch($input_type) {
      // By custom code
      case 'image_upload':
        include( WPP_FEPS_Path . "static/views/fields/image_upload.php");
      break;
      case 'taxonomy':
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'wpp-feps-taxonomy-field', WPP_FEPS_URL . '/static/scripts/fields/taxonomy.js', array( 'jquery-ui-autocomplete', 'jquery', 'underscore') );
        include( WPP_FEPS_Path . "static/views/fields/taxonomy.php");
      break;
      // By using metabox
      default:
        $input_type = $input_type?$input_type:'text';
        $field = $att_data;
        $field['id']         = "wpp_feps_data[{$att_data['slug']}]";
        $field['field_name'] = "wpp_feps_data[{$att_data['slug']}]";
        $field['type'] = WPP_F::get_valid_attribute_type($input_type);
        $field['placeholder'] = $att_data['title'];
        $field['clone'] = false;
        $field['desc'] = "";
        $field_class = RWMB_Field::get_class_name( $field );

        if($field_class):
          if(in_array($input_type, array( "image_upload", "image_advanced", "file_advanced"))){
            $field['max_file_uploads'] = $images_limit;
            if(!is_user_logged_in()){
              echo 'Please login to upload files. <a href="' . wp_login_url(get_the_permalink()) . '" title="Login">Login</a>';
              break;
            }
          }

          // If it's multi fields.
          if(WPP_F::is_attribute_multi($att_data['slug'])){
            if ($value == '')
              $value = array();
            else
              $value = (array) $value;
            $field['field_name'] .= "[]";
          }

          if(isset($field['predefined_values'])){
            $options = explode(',', $field['predefined_values']);
            foreach ($options as $key => $v) {
              $field['options'][trim($v)] = trim($v);
            }
          }


          $field = call_user_func(array($field_class , 'normalize'), $field);
          $field['id']         = "wpp_{$row_id}_input";
          
          if(method_exists($field_class, 'print_templates'))
            call_user_func(array($field_class , 'print_templates'));

          call_user_func(array($field_class, 'admin_enqueue_scripts'));
          wp_enqueue_script( 'rwmb-file-advanced',  WPP_FEPS_URL . '/static/scripts/fields/file-advanced.js', array( 'jquery', 'underscore' ), RWMB_VER, true );
          wp_enqueue_script( 'rwmb-image-advanced', WPP_FEPS_URL . '/static/scripts/fields/image-advanced.js', array( 'jquery', 'underscore' ), RWMB_VER, true );

          $html = call_user_func(array($field_class, 'html'), $value, $field);
          echo "<div class='{$att_data['ui_class']} rwmb-field rwmb-{$input_type}-wrapper'><div class='rwmb-input'>$html</div></div>";
          
          if( isset($field['max_file_uploads'] ) && $images_limit > 0): 
            $_n_img_or_file = ($input_type =='image_upload')? _n("image", "images", $images_limit, ud_get_wpp_feps()->domain): _n("file", "files", $images_limit, ud_get_wpp_feps()->domain);
          ?>
            <span class="images_limit"><?php printf(__('No more than %1d %s. Click delete to remove it.', ud_get_wpp_feps()->domain), $images_limit, $_n_img_or_file); ?></span>
          <?php 
          endif;
        else:
        ?>
        <input  tabindex="<?php echo $att_data['tabindex']; ?>"  
                id="wpp_<?php echo $row_id; ?>_input"  
                name="wpp_feps_data[<?php echo $att_data['slug']; ?>]" 
                value="<?php echo $value;?>" 
                type="text" 
                class="<?php echo $att_data['ui_class']; ?>" />
        <?php
        endif;

      break;
    }

    if( isset( $att_data['is_address_attribute'] ) && $att_data['is_address_attribute'] && empty( $att_data['property'][$att_data['slug']] ) ) {

      //** Load coordinates from existing property (parent) or default */
      if(!empty($data['property']['latitude']) && !empty($data['property']['longitude'])) {
        $map_coords['latitude'] = $data['property']['latitude'];
        $map_coords['longitude'] = $data['property']['longitude'];
      } else {
        if(!empty($wp_properties['configuration']['feature_settings']['feps']['forms'][$form_id]['custom_coords']['latitude']) && !empty($wp_properties['configuration']['feature_settings']['feps']['forms'][$form_id]['custom_coords']['longitude'])){
          $map_coords['latitude'] = $wp_properties['configuration']['feature_settings']['feps']['forms'][$form_id]['custom_coords']['latitude'];
          $map_coords['longitude'] = $wp_properties['configuration']['feature_settings']['feps']['forms'][$form_id]['custom_coords']['longitude'];
        } else {
          $map_coords = WPP_F::get_default_coordinates();
        }
      }

      self::render_location_map_input( $row_id, $map_coords, $data['args'] );
    }

    $content = ob_get_contents();
    ob_end_clean();

    $content = apply_filters( 'wpp_feps_input', $content, $data );

    if( $return ) {
      return $content;
    } else {
      echo $content;
    }

  }

  /**
   * Adds meta data to attributes
   *
   * @since 0.1
   */
  static public function add_attribute_data($data) {

    switch ($data['slug']) {

      case 'post_content':
        $data['input_type'] = 'textarea';
        $data['data_input_type'] = 'textarea';
        $data['is_wp_core'] = true;;
        return $data;
      break;

      case 'image_upload':
        $data['label'] = __('Image Upload', ud_get_wpp_feps()->domain);
        $data['is_wp_core'] = true;
        $data['input_type'] = 'image_upload';
        $data['data_input_type'] = 'image_upload';
        $data['storage_type'] = 'image_upload';
        return $data;

      break;
    }

    return $data;

  }

  /**
   * Load default settings
   * @global array $wp_properties
   */
  static public function load_defaults() {
    global $wp_properties;

    $random_id = rand(99, 9999999);

    $wp_properties['configuration']['feature_settings']['feps'] = array(
      'forms' => array(
        $random_id => array(
          'title' => 'Sample Form',
          'new_role' => 'subscriber',
          'slug' => 'sample_form',
          'new_post_status' => 'pending',
          'images_limit' => 6,
          'feps_credits' => '',
          'property_type' => '',
          'trash_time' => '',
          'thumbnail_size' => '',
          'add_to_slideshow' => '',
          'can_manage_feps' => '',
          'notifications' => array(
            'user_creation' => '',
            'on_status_updated' => '',
          ),
          'required' => array(
            'post_title' => array(
              'title' => 'Property Title',
              'attribute' => 'post_title',
              'description' => '',
            )
          )
        )
      )
    );

  }

  /**
   * Load default (first) field if there is no any fields
   * @global array $wp_properties
   * @author Anton Korotkov
   * @return array
   */
  static public function load_default_field() {
    global $wp_properties;
    $row_id = rand(99,9999999);
    if ( !empty( $wp_properties['property_stats'] ) ) {
      $attribute = key( $wp_properties['property_stats'] );

      $field = array();


      $field[$row_id]['title'] = $wp_properties['property_stats'][ $attribute ];
      $field[$row_id]['attribute'] = $attribute;
      $field[$row_id]['required'] = 'false';
      $field[$row_id]['description'] = '';
    } else {
      $field[$row_id]['title'] = '';
      $field[$row_id]['attribute'] = '';
      $field[$row_id]['required'] = 'false';
      $field[$row_id]['description'] = '';
    }

    return $field;

  }

  /**
   * Handle adding post meta value
   * @global array $wp_properties
   * @param int $meta_id
   * @param int $object_id
   * @param string $meta_key
   * @author Anton Korotkov
   * @param string $meta_value
   */
  static public function handle_post_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
    global $wp_properties;

    switch( $meta_key ) {

      default: break;

      case $wp_properties['configuration']['address_attribute']:

        $geo_data = WPP_F::geo_locate_address($meta_value, $wp_properties['configuration']['google_maps_localization'], true);

        if(!empty($geo_data->formatted_address)) {
          update_post_meta($object_id, 'address_is_formatted', true);

          if(!empty($wp_properties['configuration']['address_attribute'])) {
            update_post_meta($object_id, $wp_properties['configuration']['address_attribute'], $geo_data->formatted_address );
          }

          foreach($geo_data as $geo_type => $this_data) {
            update_post_meta($object_id, $geo_type, $this_data );
          }

        } else {
          // Try to figure out why it failed
          update_post_meta( $object_id, 'address_is_formatted', false );
        }

        break;

    }

  }

  /**
   * Filter CRM actions list
   * @param array $current
   * @author Anton Korotkov
   * @return array
   */
  static public function crm_custom_notification($current) {

    foreach( self::$crm_notification_actions as $action_key => $action_name ) {
      $current[$action_key] = str_replace (
              array(
                'property',
                'Property',
                'properties',
                'Properties'
              ),
              array(
                WPP_F::property_label( 'singular' ),
                ucfirst( WPP_F::property_label( 'singular' ) ),
                WPP_F::property_label( 'plural' ),
                ucfirst( WPP_F::property_label( 'plural' ) )
              ),
              $action_name);
    }

    return $current;
  }

  /**
   * Filter query to allow view of pending posts
   * @param object $wp_query
   * @param object $post
   * @author korotkov@ud
   * @return object
   */
  static public function wpp_query_filter( $wp_query, $post ) {

    $post->ancestors = !empty( $post->ancestors ) && is_array( $post->ancestors ) ? $post->ancestors : get_post_ancestors( $post );

    $wp_query->is_404 = false;
    $wp_query->is_single = 1;
    $wp_query->is_preview = 1;
    $wp_query->is_singular = 1;
    $wp_query->post_count = 1;
    $wp_query->posts[0] = $post;
    $wp_query->post = $post;

    return $wp_query;
  }

  /**
   * Adds FEPS specific queryable keys for property_overview shortcode
   *
   * @param array $keys
   * @author peshkov@UD
   * @since 1.4
   * @version 0.1
   */
  static public function queryable_keys( $keys ) {
    $keys[] = 'wpp_feps_property';
    $keys[] = 'post_status';
    return $keys;
  }

  /**
   * Prevent auth of inactive user
   * @param unknown $nothing
   * @param string $username
   * @param string $password
   * @author Anton Korotkov
   * @return WP_Error || null
   */
  static public function authenticate( $nothing, $username, $password ) {
    $user = get_user_by( 'login', $username );
    if ( !empty( $user->is_not_approved ) ) {
      $user_error = new WP_Error('authentication_failed', __('<strong>ERROR</strong>: Account is not approved.', ud_get_wpp_feps()->domain));
      return $user_error;
    }
    return $nothing;

  }

  /**
   * Renders location input map
   *
   * @param int $row_id
   * @param array $default_coords
   * @param array $args
   * @author korotkov@ud
   */
  static public function render_location_map_input( $row_id, $default_coords, $args ) {?>

      <div id="wpp_feps_map_<?php echo $row_id; ?>" class="wpp_feps_map" style="width: 100%; height: <?php echo $args['map_height']; ?>px;"><?php _e('There is a JavaScript error on this page preventing it from displaying the dynamic map.', ud_get_wpp_feps()->domain); ?></div>

      <?php ob_start(); ?>
      <script type="text/javascript">

        function empty( mixed_var ) {
          return ( mixed_var === "" || mixed_var === 0   || mixed_var === "0" || mixed_var === null  || mixed_var === false );
        }

        jQuery(document).ready(function() {

          /* Check if Google Maps is loaded */
          if(typeof google == 'undefined' || typeof google.maps == 'undefined' || typeof qq == 'undefined') {
            return;
          }

          jQuery('#wpp_feps_map_<?php echo $row_id; ?>').gmap({'zoom':10, 'center': new google.maps.LatLng(<?php echo $default_coords['latitude'] ?>,<?php echo $default_coords['longitude'] ?>)});
          jQuery("<?php echo "#wpp_{$row_id}_input"; ?>").change(function() {
            var location_string = jQuery.trim( jQuery(this).val() );
            jQuery('div.location_result_<?php echo $row_id; ?>').hide();
            if ( !empty(location_string) ) {
              jQuery('#wpp_feps_map_<?php echo $row_id; ?>').gmap('search', { 'address': location_string  }, function(isFound,results) {
                if (isFound){
                  jQuery('#wpp_feps_map_<?php echo $row_id; ?>').gmap('getMap').panTo(results[0].geometry.location);
                  jQuery('#wpp_feps_map_<?php echo $row_id; ?>').gmap({'zoom':14});
                  jQuery('#wpp_feps_map_<?php echo $row_id; ?>').gmap('clearMarkers');
                  jQuery('#wpp_feps_map_<?php echo $row_id; ?>').gmap('addMarker', {'title':results[0].formatted_address, 'position': new google.maps.LatLng(results[0].geometry.location.lat(), results[0].geometry.location.lng()) } );
                  jQuery("#wpp_feps_map_<?php echo $row_id; ?>").show();
                  jQuery('div.location_result_<?php echo $row_id; ?>').text("<?php _e('Your location has been successfully found by Google Maps.', ud_get_wpp_feps()->domain); ?>").addClass('wpp_feps_loc_found').removeClass('wpp_feps_loc_not_found').show();
                } else {
                  jQuery("<?php echo "#wpp_{$row_id}_input"; ?>").val('');
                  jQuery('div.location_result_<?php echo $row_id; ?>').text("<?php _e('Your address could not be found, please try again.', ud_get_wpp_feps()->domain); ?>").addClass('wpp_feps_loc_not_found').removeClass('wpp_feps_loc_found').show();
                }
              });
            }
          }).change();
        });
      </script>
    <?php $output_js = ob_get_contents(); ob_end_clean(); echo $output_js; ?>
    <div class="location_result_<?php echo $row_id; ?>"></div>
    <?php
  }


  /**
   * Called only if WPI SPC exists
   *
   * @uses class_wpp_feps::fix_404()
   * @global object $wp
   * @param boolean $_is_404
   * @param object $query
   * @return boolean
   * @author peshkov@UD
   */
  static public function spc_fix_404 ( $_is_404, $query ) {
    global $wp, $wp_query;

    //* STEP 3. Determine if this is FEPS Credits Adding Page */
    if( is_user_logged_in() && (
        $wp->request == FEPS_SPC_PAGE ||
        $wp->query_string == "p=" . FEPS_SPC_PAGE ||
        $query->query_vars[ 'pagename' ] == FEPS_SPC_PAGE ||
        $query->query_vars[ 'category_name' ] == FEPS_SPC_PAGE )
    ) {
      $wp_query->wpp_feps_spc_page = true;
      $_is_404 = false;
    }

    return $_is_404;
  }

  /**
   * Loads 'FEPS Credit Adding' page
   *
   * @author peshkov@UD
   * @since 1.4
   * @version 0.1
   */
  static public function spc_page_load () {
    global $wpdb, $wp_query, $property, $wp_properties;
    
    $user = wp_get_current_user();
    
    $template = false;
    if( !empty( $wp_properties['configuration']['feps']['templates']['add_credits_page'] ) ) {
      $template = $wp_properties['configuration']['feps']['templates']['add_credits_page'];
      $template = str_replace( '.php', '', $template );
    }
    
    $template_found = WPP_F::get_template_part( array_filter( array(
      $template,
      'page',
      'single',
    ) ) );

    //* Determine if template is found and finish loading. */
    if( $template_found ) {

      wp_enqueue_script( 'wpp-feps-spc', ud_get_wpp_feps()->path( 'static/scripts/wpp.feps.spc.js' ), array( 'jquery', 'wpp-localization', 'wp-property-global' ) );

      //** Create a fake Page */
      $wp_query->is_404 = false;
      $wp_query->post_count = 1;
      $wp_query->posts[0] = (object) array(
        'ID' => '999999998',
        'post_title' => __( 'Add Credits to Balance', ud_get_wpp_feps()->domain ),
        'post_content' => "[wpi_checkout custom_amount='true' callback_function='wpp_add_feps_credits' template='feps']",
        'post_name' => FEPS_SPC_PAGE,
        'post_type' => 'page',
        'post_date' => date( 'Y-m-d H:i:s', time() ),
        'post_status' => 'publish',
        'post_author' =>  $user->ID
      );
      
      die( load_template( $template_found ) );

    } else {

      WPP_F::console_log( 'Could not find template for FEPS Credits Adding page.' );

      $wp_query->is_404 = true;

    }
  }

  /**
   * WPI SPC integration
   *
   * @param type $args
   */
  static public function spc_options( $args = '' ) {
    global $wpi_settings;

    $defaults = array(
      'intervals'=>array(
        'day' => __('Days',ud_get_wpp_feps()->domain),
        'week' => __('Weeks',ud_get_wpp_feps()->domain),
        'month' => __('Months',ud_get_wpp_feps()->domain),
        'year' => __('Years',ud_get_wpp_feps()->domain),
        ),
      'plans_default_fields' => array(
        'name' => __('Basic',ud_get_wpp_feps()->domain),
        'price' => '1',
        'duration' => array('value'=>'1','interval'=>'day'),
        'is_featured' => 'false',
        'images_limit' => '1',
        'description' => __('Default One-Day Subscription Plan',ud_get_wpp_feps()->domain)
      )
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $intervals = isset($intervals) ? $intervals : array();
    $plans_default_fields = isset($plans_default_fields) ? $plans_default_fields : array();
    if (empty($form['subscription_plans'])){
      $default_data = true;
      $form['subscription_plans'][WPP_F::create_slug($plans_default_fields['name'])] = $plans_default_fields;
    }

    ?>
    <tr class="wpp_feps_plan_row" style="<?php echo (class_exists('wpi_spc') && empty( $form['feps_credits'] ) || $form['feps_credits'] != 'true' ) ? 'display:none;' : ''; ?>">
      <th colspan="2" style="padding-top:10px;">
        <h3 style="float:left;"><?php _e( 'Subscription Plans', ud_get_wpp_feps()->domain ); ?></h3>
          <table class="ud_ui_dynamic_table widefat wpp_feps_sortable" use_random_row_id="true">
            <thead>
              <tr>
                <th class="wpp_draggable_handle"></th>
                <th class="wpp_plan_name_col"><?php _e('Name', ud_get_wpp_feps()->domain); ?></th>
                <th class="wpp_plan_price_col"><?php _e('Price', ud_get_wpp_feps()->domain); ?></th>
                <th class="wpp_plan_duration_col"><?php _e('Duration', ud_get_wpp_feps()->domain); ?></th>
                <th class="wpp_plan_featured_col"><?php _e('Is Featured', ud_get_wpp_feps()->domain); ?></th>
                <th class="wpp_plan_images_limit_col"><?php _e('Images Limit', ud_get_wpp_feps()->domain); ?></th>
                <th class="wpp_plan_description_col"><?php _e('Description', ud_get_wpp_feps()->domain); ?></th>
                <th class="wpp_plan_action_col"><?php _e( 'Actions',ud_get_wpp_feps()->domain ) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php
                reset($form['subscription_plans']);
                $first_element  = key($form['subscription_plans']);
                foreach((array)$form['subscription_plans'] as $slug => $plan_data): ?>
                <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row="<?php echo( !empty( $default_data ) ? 'true' : 'false'); ?>">
                  <td class="wpp_draggable_handle">&nbsp;</td>
                  <td class="wpp_plan_name_col"><input class="slug_setter" type="text" name="wpp_feps[forms][<?php echo $form_id; ?>][subscription_plans][<?php echo $slug; ?>][name]" value="<?php echo $plan_data['name']; ?>" /></td>

                  <td class="wpp_plan_price_col"><input type="text" name="wpp_feps[forms][<?php echo $form_id; ?>][subscription_plans][<?php echo $slug; ?>][price]" value="<?php echo $plan_data['price']; ?>" /><?php echo '&nbsp;'.(($wpi_settings['currency']['default_currency_code'])?$wpi_settings['currency']['default_currency_code']:'$'); ?></td>
                  <td class="wpp_plan_duration_col">
                    <input type="text" name="wpp_feps[forms][<?php echo $form_id; ?>][subscription_plans][<?php echo $slug; ?>][duration][value]" value="<?php echo $plan_data['duration']['value']; ?>" />
                    <select class="subscription_intervals" name="wpp_feps[forms][<?php echo $form_id; ?>][subscription_plans][<?php echo $slug; ?>][duration][interval]">
                      <?php foreach ($intervals as $i_slug=>$i_name) : ?>
                      <option value="<?php echo $i_slug;?>"<?php echo ($plan_data['duration']['interval']==$i_slug)?' selected="selected"':'' ?>><?php echo $i_name;?></option>
                      <?php endforeach;?>
                    </select>
                  </td>
                  <td class="wpp_plan_featured_col">
                    <input type="hidden" name="wpp_feps[forms][<?php echo $form_id; ?>][subscription_plans][<?php echo $slug; ?>][is_featured]" value="false"/>
                    <input type="checkbox" name="wpp_feps[forms][<?php echo $form_id; ?>][subscription_plans][<?php echo $slug; ?>][is_featured]" value="true" <?php echo $plan_data['is_featured'] == 'true' ? 'checked' : ''; ?> />
                  </td>
                  <td class="wpp_plan_images_limit_col"><input type="text" name="wpp_feps[forms][<?php echo $form_id; ?>][subscription_plans][<?php echo $slug; ?>][images_limit]" value="<?php echo $plan_data['images_limit']; ?>" /></td>
                  <td class="wpp_plan_description_col"><textarea type="text" name="wpp_feps[forms][<?php echo $form_id; ?>][subscription_plans][<?php echo $slug; ?>][description]"><?php echo $plan_data['description']; ?></textarea></td>

                  <td><span class="wpp_delete_row wpp_link"><?php _e('Delete', ud_get_wpp_feps()->domain) ?></span></td>
                </tr>
              <?php endforeach;?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="8">
                  <input type="button" callback_function="wpp.ui.feps.on_added_row" class="wpp_add_row button-secondary" value="<?php _e('Add Subscription Plan',ud_get_wpp_feps()->domain) ?>" />
                </td>
              </tr>
            </tfoot>
          </table>
        </th>
    </tr>
  <?php
  }

  /**
   * SPC integration
   *
   * @param type $args
   */
  static public function spc_options_general( $args = '' ) {
    $defaults = array();

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $form = isset($form) ? $form : array();
    $form_id = isset($form_id) ? $form_id : '';
    ?>
    <li>
      <?php ob_start(); ?>
      <script type="text/javascript">
        jQuery(document).ready(function() {
          jQuery("input[name*='feps_credits']").bind( "change", function(event, ui) {
            extra_objects = jQuery("input[name*='user_creation'], select[name*='new_post_status'], input[name*='images_limit']", jQuery(this).parents('.wpp_feps_form').find('.wpp_option_table ul')).closest('li');
            plan_table = jQuery('.wpp_feps_plan_row', jQuery(this).parents('.wpp_feps_form'));
            if (jQuery(this).attr('checked')){
              extra_objects.hide();
              plan_table.show();
            }else{
              extra_objects.each(function(i,e){
                //* Check if the current object is image option and determine if form has image upload attribute before show option. */
                if ( jQuery('input.imageslimit', e).length > 0 ) {
                  var show_option = false;
                  jQuery("select.wpp_feps_new_attribute option:selected", jQuery(e).closest('.ui-tabs-panel')).each(function(k, p){
                    if ( jQuery(p).val() == 'image_upload' ) {
                      show_option = true;
                      return false;
                    }
                  });
                  if(show_option) {
                    jQuery(e).show();
                  }
                } else {
                  jQuery(e).show();
                }
              });
              plan_table.hide();
            }
          });
        });
      </script>
      <?php $output_js = ob_get_contents(); ob_end_clean(); echo WPP_F::minify_js($output_js); ?>
      <input id="feps_credits_<?php echo $form_id; ?>" type="checkbox" name="wpp_feps[forms][<?php echo $form_id; ?>][feps_credits]" <?php if( isset( $form['feps_credits'] ) ) checked( 'true', $form['feps_credits'] ); ?> class="wpp_show_advanced feps_credits" advanced_option_class="wpp_advanced_option" value="true" />
      <label for="feps_credits_<?php echo $form_id; ?>"><?php _e('This is Sponsored Listing', ud_get_wpp_feps()->domain); ?></label>
    </li>
  <?php
  }

  /**
   * SPC integration
   *
   * @param type $args
   */
  static public function spc_options_general_miss( $args = '' ) {
    $defaults = array();

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    ?>
    <li>
      <input type="checkbox" disabled="disabled" />
      <?php _e('This is Sponsored Listing', ud_get_wpp_feps()->domain); ?>.
      &nbsp;<span class="description">Get <a href="https://wp-invoice.github.io/addons/spc/">WP-Invoice Single Page Checkout (SPC) plugin</a> for <a class="small" href="<?php echo admin_url('plugin-install.php?tab=search&amp;type=term&amp;s=wp-invoice+billing+andypotanin');?>">WP-Invoice</a> to use FEPS for Sponsored Listings.</span>
    </li>
  <?php
  }

  /**
   * Adds wp-property templates path for WP-Invoice Single Page Checkout template.
   *
   * @param $pathes
   * @return array
   * @author peshkov@UD
   * @since 2.0
   */
  static public function wpi_spc_template( $pathes ) {

    if( !is_array( $pathes ) ) {
      $pathes = (array)$pathes;
    }

    $pathes[] = ud_get_wpp_feps()->path( 'static/views', 'dir' );

    return $pathes;
  }

  /**
   * Callback ( redirect url ) after Sponsored listing is submitted
   *
   * @global array $wp_properties
   * @param type $_property
   * @param type $request
   * @author korotkov@ud
   * @author peshkov@UD
   */
  static public function save_property_callback( $callback, $_property, $request ) {
    $form_data = self::get_form_by_md5( $request[ 'wpp_feps_data' ]['form_id'] );
    //** Do this only if for is Sponsored Listing */
    if ( $form_data && !empty( $form_data['feps_credits'] ) && $form_data['feps_credits'] == 'true' && $_property['post_status'] == 'pending' && is_user_logged_in() ) {
      $callback = self::get_edit_feps_permalink( $_property[ 'ID' ], 'subscription_plan' );
    }
    return $callback;
  }

  /**
   * Returns permalink to edit FEPS page ( specific tab )
   *
   * @author peshkov@UD
   * @since 2.0
   */
  static public function get_edit_feps_permalink( $id, $action = 'edit', $mail = false ) {

    $form_id = get_post_meta( $id, FEPS_META_FORM, true );

    return WPP_F::base_url( FEPS_EDIT_PAGE, array_filter( array(
      'feps' => $id,
      'wpp_front_end_action' => $action,
      'wpp_feps_form' => md5( $form_id ),
    ) ) );

  }

  /**
   * Filter form data before inserting the post
   *
   * @param array $current
   * @return string
   * @author korotkov@ud
   */
  static public function filter_form_data( $current ) {
    if ( !empty( $current['feps_credits'] ) && $current['feps_credits'] == 'true' ) {
    $current['new_post_status'] = 'pending';
    }
    return $current;
  }
  
  /**
   * Second feps step
   *
   * @global array $wp_properties
   * @return type
   * @author korotkov@ud
   */
  static public function sponsored_listing_sub_plan() {
    global $wp_properties, $post;

    //** Get forms */
    $forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];

    //** Cycle through forms and match up with passed md5 hash */
    $found = false;
    foreach($forms as $form_id => $form_data) {
      if( md5( $form_id ) == $_REQUEST['wpp_feps_form'] ||
          $form_id == $_REQUEST['wpp_feps_form'] ) {
        $found = true;
        break;
      }
    }

    //** If form found */
    if ( $found ) {
      $content_template_found = WPP_F::get_template_part( array(
        'feps-subplans-template'
      ), array( ud_get_wpp_feps()->path( 'static/views', 'dir' ) ) );

      $subscription_plans = $form_data['subscription_plans'];

      $image_field = array_filter((array)$form_data['fields'], function( $field ) { return $field["attribute"]=="image_upload"; });
      $form_has_images = !empty($image_field)?true:false;

      ob_start();
      include $content_template_found;
      $html = ob_get_clean();
      return $html;
    }
  }

  /**
   * Purchase listing step
   *
   * @global array $wp_properties
   * @return type
   * @author korotkov@ud
   */
  static public function sponsored_listing_purchase() {
    global $wp_properties, $current_user, $wpi_settings;

    if ( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( $_REQUEST['_wpnonce'], 'feps_select_subscription_plan' ) ) return false;
    if ( empty( $_REQUEST['subscription_plan'] ) ) return false;
    if ( empty( $_REQUEST['feps'] ) ) return false;
    if ( empty( $_REQUEST['wpp_feps_form'] ) ) return false;

    //** Get forms */
    $forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];

    //** Cycle through forms and match up with passed md5 hash */
    $found = false;
    foreach($forms as $form_id => $form_data) {
      if( md5( $form_id ) == $_REQUEST['wpp_feps_form'] ) {
        $found = true;
        break;
      }
    }

    //** if plan does not exists - return */feps_subscription_plan
    if ( !is_user_logged_in() || !$found || !key_exists($_REQUEST['subscription_plan'], $form_data['subscription_plans']) ) return false;

    $content_template_found = WPP_F::get_template_part( array(
      'feps-purchase-template'
    ), array( ud_get_wpp_feps()->path( 'static/views', 'dir' ) ) );

    $html = '';
    if( !empty( $content_template_found ) ) {
      $property_id = $_REQUEST['feps'];
      $plan_slug   = $_REQUEST['subscription_plan'];
      $plan        = $form_data['subscription_plans'][$_REQUEST['subscription_plan']];
      $property = get_property( $property_id );
      $feps_user_email = $current_user->get('user_email');
      $credits = is_user_logged_in() ? (float)$current_user->get( FEPS_USER_CREDITS ) : 0;
      $price   = (float)$plan['price'];
      $image_field = array_filter( (array)$form_data['fields'], function( $field ) { return $field["attribute"]=="image_upload"; } );
      $form_has_images = !empty( $image_field ) ? true : false;

      ob_start();
      include $content_template_found;
      $html = ob_get_clean();
    }

    return $html;
  }

  /**
   * Filter output
   *
   * @param type $current
   * @param type $args
   * @return type
   * @author korotkov@ud
   */
  static public function feps_output_filter( $current, $args ) {

    if ( empty( $args['the_form']['feps_credits'] ) || $args['the_form']['feps_credits'] !== 'true' ) return $current;

    $content_template_found = WPP_F::get_template_part( array(
      'feps-tabs-template'
    ), array( ud_get_wpp_feps()->path( 'static/views', 'dir' ) ) );

    ob_start();
    include $content_template_found;
    $html = ob_get_clean();

    return $html;
  }

  /**
   * Login user automaticaly
   *
   * @param type $creds
   * @author korotkov@ud
   */
  static public function credentials_verified_action( $creds ) {
    global $current_user;
    $user = get_user_by('email', $creds['user_email']);
    if ( $user ) {
      $current_user = wp_signon(array(
        'user_login'    => $user->data->user_login,
        'user_password' => $creds['user_password'],
        'remember'      => true
      ));
    }
  }

  /**
   * User activation
   *
   * @return type
   * @author korotkov@ud
   */
  static public function feps_user_activation() {
    if ( empty( $_REQUEST['wpp_user_activation'] ) || empty( $_REQUEST['wpp_user'] ) ) return;
    $user = new WP_User($_REQUEST['wpp_user']);
    $is_not_approved = get_user_meta($user->ID, 'is_not_approved', 1);
    if ( $is_not_approved && md5($user->ID.$user->data->user_email.SECURE_AUTH_SALT) == $_REQUEST['wpp_user_activation'] ) {
      delete_user_meta($user->ID, 'is_not_approved');

      $notification['data']['notification_type'] = __('User Account Approved', ud_get_wpp_feps()->domain);
      $notification['data']['user_email'] = $user->data->user_email;
      $notification['data']['display_name'] = $user->data->display_name;
      $notification['data']['user_login'] = $user->data->user_login;
      $notification['data']['user_password'] = $user->data->user_pass;
      $notification['data']['site_url']     = site_url();
      $notification['trigger_action'] = 'pending_account_approved';
      $notification['user'] = $user;
      $notification['subject'] = __('Account Approved', ud_get_wpp_feps()->domain);
      $notification['message'] = sprintf(__('Hello [display_name]%1$s%1$sYour account on [site_url] has been approved.%1$s%1$sClick this link to visit site:%1$s[site_url]', ud_get_wpp_feps()->domain), PHP_EOL);
      $notification['data']['system_message'] = $notification['message'];
      $notification['crm_log_message'] = __('User Account Approved.', ud_get_wpp_feps()->domain);
      WPP_F::send_notification($notification);

      wp_die( sprintf( __('Thank you! Your account has been activated. <a href="%s">Back to the site!</a>', ud_get_wpp_feps()->domain), home_url() ), __('Account has been activated', ud_get_wpp_feps()->domain));
    } else {
      wp_redirect(home_url());die();
    }
  }

  /**
   * Action after credits added. Publish and charge balance
   *
   * @param type $args
   */
  static public function after_add_credits( $args ) {

    //** Extract args */
    $credits = 0;
    $data = array();
    $defaults = array(
      'credits' => 0,
      'data' => array()
    );

    extract( wp_parse_args( $args, $defaults ) );

    //** If no credits or property ID is not specified - exit */
    if ( !$credits || empty( $data['post_data']['wpp::feps::property_id'] ) ) {
      return null;
    }

    //** Get subscription plan data */
    $property_id = $data['post_data']['wpp::feps::property_id'][0];
    $current_subscription_plan = get_post_meta( $property_id, FEPS_META_PLAN, 1 );

    $credits = (float)$credits;
    $price   = (float)$current_subscription_plan['price'];

    if ( $credits >= $price ) {
      if ( wp_update_post( array( 'ID' => $property_id, 'post_status' => 'publish' ) ) ) {
        $credits -= $price;
        update_user_meta($data['user_data']['ID'], FEPS_USER_CREDITS, $credits);
      }
    }

  }

  /**
   * AJAX processor for Pay Now button
   * 
   */
  static public function feps_pay_now() {
    $amount = 0;
    $property_id = 0;
    $subscription_plan = '';
    //** Extract args */
    $defaults = array(
      'amount'      => 0,
      'property_id' => 0,
      'subscription_plan' => ''
    );
    extract( wp_parse_args( $_REQUEST, $defaults ) );
    
    $response = array(
      'success' => 0,
      'message' => '',
      'property_id' => $property_id,
    );

    //** Check nonce */
    if ( wp_verify_nonce($_REQUEST['_wpnonce'], 'wpp_feps_pay_now') ) {

      //** User should be logged in */
      if ( is_user_logged_in() ) {

        //** Load user */
        $user    = new WP_User( get_current_user_id() );
        $credits = (float)$user->get( FEPS_USER_CREDITS );

        if ( !$property_id ) {
          $response[ 'message' ] = __('Something went wrong. Please, try again later. [Error 001]', ud_get_wpp_feps()->domain);
          die( json_encode( $response ) );
        }

        $form_id = get_post_meta( $property_id, FEPS_META_FORM, 1 );
        if ( !$form_id ) {
          $response[ 'message' ] = __('Something went wrong. Please, try again later. [Error 002]', ud_get_wpp_feps()->domain);
          die( json_encode( $response ) );
        }
        
        if( !self::set_subscription_plan( $property_id, $subscription_plan ) ) {
          $response[ 'message' ] = __('Something went wrong. Please, try again later. [Error 003]', ud_get_wpp_feps()->domain);
          die( json_encode( $response ) );
        }

        //** If really enough credits */
        if ( $credits >= $amount ) {

          //** Try to update post */
          if ( wp_update_post( array( 'ID' => $property_id, 'post_status' => 'publish' ) ) ) {
            $credits -= $amount;

            //** Try to decrease balance */
            if ( update_user_meta($user->ID, FEPS_USER_CREDITS, $credits) ) {
              //** Success */
              $response[ 'success' ] = 1;
              $response[ 'message' ] = __( 'Thank you! Your payment has been successfully made.', ud_get_wpp_feps()->domain );
              if( is_user_logged_in() ) {
                $response[ 'message' ] .= ' ' . sprintf( __( '<a href="%s">View %s</a>', ud_get_wpp_feps()->domain ), WPP_F::base_url( FEPS_VIEW_PAGE ), WPP_F::property_label( 'plural' ) );
              }
              die( json_encode( $response ) );
            }
          }
          //** Error */
          $response[ 'message' ] = __('Something went wrong. Please, try again later. [Error 005]', ud_get_wpp_feps()->domain);
          die( json_encode( $response ) );
        }
        //** Error */
        $response[ 'message' ] = __('Not enough credits.', ud_get_wpp_feps()->domain);
        die( json_encode( $response ) );

      }
      //** Error */
      $response[ 'message' ] = __('You must be logged in to be able to do this action.', ud_get_wpp_feps()->domain);
      die( json_encode( $response ) );
    }
    //** Error */
    $response[ 'message' ] = __('Something went wrong. Please, try again. [Error 006]', ud_get_wpp_feps()->domain);
    die( json_encode( $response ) );
  }

  /**
   * Adds specific user FEPS fields to user's profile ( edit user page ).
   *
   * @param type $user_id
   * @author peshkov@UD
   * @since 2.0
   */
  static public function user_profile_fields( $user ) {
    global $wpi_settings;

    if( $user && current_user_can( 'edit_user', $user->ID ) ) {
      ?>
      <h3><?php _e( 'Front End Property Submissions (FEPS).', ud_get_wpp_feps()->domain ); ?></h3>
      <table class="form-table">
        <tbody>
          <tr>
            <th><?php _e( 'Available Credits (Balance)', ud_get_wpp_feps()->domain ); ?></th>
            <td>
            <?php if( current_user_can( self::$capability ) ) : ?>
              <input type="text" name="<?php echo FEPS_USER_CREDITS; ?>" value="<?php echo esc_attr( get_the_author_meta( FEPS_USER_CREDITS, $user->ID ) ); ?>" />
            <?php else : ?>
              <input type="text" disabled="disabled" value="<?php echo esc_attr( get_the_author_meta( FEPS_USER_CREDITS, $user->ID ) ); ?>" class="disabled" />
            <?php endif; ?>
            <?php if( isset( $wpi_settings['currency']['symbol'] ) && isset( $wpi_settings['currency']['default_currency_code'] ) ) : ?>
              <span class="description"><?php printf( __( '%s%01.2f = %01.2f credit', ud_get_wpp_feps()->domain ), $wpi_settings['currency']['symbol'][$wpi_settings['currency']['default_currency_code']], 1, 1 ); ?></span>
            <?php endif; ?>
            </td>
          </tr>
        </tbody>
      </table>
      <?php
    }
  }

  /**
   * Updates user credits balance using user edit page ( or profile ).
   *
   * @param type $user_id
   * @author peshkov@UD
   * @since 2.0
   */
  static public function update_user_fields( $user_id ) {
    if ( current_user_can( self::$capability ) && current_user_can( 'edit_user', $user_id ) && isset( $_POST[ FEPS_USER_CREDITS ] ) ) {
      update_user_meta( $user_id, FEPS_USER_CREDITS, (float)$_POST[ FEPS_USER_CREDITS ] );
    }
  }


  /**
   * Adds speicific strings to javascript localization
   *
   * @param array $l10n
   * @return array
   * @author peshkov@UD
   * @since 2.0
   */
  static public function js_localization( $l10n ) {

    $l10n = array_merge( $l10n, array(
      'validation_error' => __( 'Validation error. Check form fields', ud_get_wpp_feps()->domain ),
      'type_email' => __('Please type in your e-mail address.', ud_get_wpp_feps()->domain),
      'checking_account' => __('Checking if account exists.', ud_get_wpp_feps()->domain),
      'checking_credentials' => __('Checking your credentials.', ud_get_wpp_feps()->domain),
      'credentials_verified' => __('Your credentials have been verified.', ud_get_wpp_feps()->domain),
      'credentials_incorrect' => __('Your login credentials are not correct.', ud_get_wpp_feps()->domain),
      'account_found_type_password' => __('Account found, please type in your password.', ud_get_wpp_feps()->domain),
      'account_created_check_email' => __('Your account has been created. Check your e-mail to activate account.', ud_get_wpp_feps()->domain),
    ) );

    return $l10n;
  }

  /**
   * Returns all the list of pages which contains [wpp_feps_form] shortcode
   *
   * @author peshkov@UD
   * @since 2.0
   */
  static public function get_all_form_pages() {
    global $wpdb;

    $form_pages = array();

    if( $cache = wp_cache_get( 'wpp::feps::all_form_pages' )) {
      return $cache;
    }

    $results = $wpdb->get_results( "
      SELECT ID, post_title, post_content
        FROM $wpdb->posts
          WHERE post_type = 'page'
          AND post_content LIKE '%wpp_feps_form%';
    ", ARRAY_A );

    if( !empty( $results ) && !is_wp_error( $results ) ) {
      foreach( $results as $result ) {
        preg_match( '/(\[)(\s*wpp_feps_form.*)(\])/', $result[ 'post_content' ], $matches );
        if( $matches ) {
          array_push( $form_pages, array(
            'ID' => $result[ 'ID' ],
            'post_title' => $result[ 'post_title' ],
          ) );
        }
      }
    }

    wp_cache_add( 'wpp::feps::all_form_pages', $form_pages );

    return $form_pages;
  }

  /**
   * Returns label for button's submit form
   * Used in feps-submit-template
   *
   * @author peshkov@UD
   */
  static public function filter_submit_button( $btn_label, $property, $form ) {
    global $wp_properties;

    switch (1){
      case
        /** If new FEPS with feps_credits */
        ( class_exists('wpi_spc') && 
          (
            (empty($property) && !empty( $form['feps_credits'] ) && $form['feps_credits'] == 'true' ) ||
          /** Case edit of pending property and form with credits */
            ( !empty($property) && $property['post_status'] == 'pending' && 
              !empty($property[ FEPS_META_FORM ]) && 
              isset($wp_properties['configuration']['feature_settings']['feps']['forms'][$property[ FEPS_META_FORM ]]["feps_credits"]) &&
              $wp_properties['configuration']['feature_settings']['feps']['forms'][$property[ FEPS_META_FORM ]]["feps_credits"]=='true'
            )
          )
        ):
        $btn_label = __('Next', ud_get_wpp_feps()->domain);
        break;

      case (!empty($property) && $property['post_status'] == 'publish'):
        $btn_label = __('Update', ud_get_wpp_feps()->domain);
        break;

      default:
        $btn_label = current_user_can('publish_wpp_properties')?__('Publish', ud_get_wpp_feps()->domain):__('Submit', ud_get_wpp_feps()->domain);
    }

    return $btn_label;
  }
  
  /**
   * Show ( or not ) attribute on Edit Property page for current property
   * Don't show attribute if it was not selected for Property Form which was used for creating property.
   * It's done to prevent showing extra attributes on frontend: not selected attribute will be added to current property on Edit Property page updating.
   *
   * @author peshkov@UD
   */
  static public function filter_metabox_attribute_show( $boolean, $slug, $property_id ) {
    if( $form = self::get_form_by_post( $property_id ) ) {
      $boolean = false;
      foreach( (array)$form[ 'fields' ] as $field ) {
        if( $field[ 'attribute' ] == $slug ) {
          $boolean = true;
          break;
        }
      }
    }
    return $boolean;
  }

//  Ajax Login

function ajax_user_login(){

  if(!$_POST['feps-login']){
    return;
  }

  // The data from the form fields and check
  $info = array();
  $info['user_login'] = $_POST['username'];
  $info['user_password'] = $_POST['password'];
  $info['remember'] = true;

  $user_signon = wp_signon( $info, false );

  if ( is_wp_error($user_signon) ){
    echo json_encode(array('loggedin'=>false, 'message'=>__('Login or password incorrect')));
  } else {
    echo json_encode(array('loggedin'=>true, 'message'=>__('Login sucsesfull...')));
  }

  die();
}



}

/**
 * MyFepsWidget Class
 */
class MyFepsWidget extends WP_Widget {

  public function __construct() {
    parent::__construct( false, $name = __( 'FEPS Menu' ), array( 'description' => __( 'Shows FEPS Menu. It\'s available only for logged in users', ud_get_wpp_feps()->domain ) ) );
  }

  public function widget($args, $instance) {
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    extract( $args );

    $instance['before'] = $before_widget;
    (is_user_logged_in()) ? $widget_title = apply_filters('widget_title', $instance['title']) : $widget_title = '';
    if ( $widget_title ) {
      $instance['before'] .= $before_title . $widget_title . $after_title;
    }

    $instance['after'] = $after_widget;

    $instance['title'] = false;

    echo class_wpp_feps::wpp_feps_menu( $instance );
  }

  public function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  public function form( $instance ) {
    global $wp_properties;

    $form_pages = class_wpp_feps::get_all_form_pages();

    $title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
    $show_balance = isset( $instance['show_balance'] ) ? $instance['show_balance'] : false;
    $show_spc_link = isset( $instance['show_spc_link'] ) ? $instance['show_spc_link'] : false;
    $show_login_form = isset( $instance['show_login_form'] ) ? $instance['show_login_form'] : false;
    $show_reg_link = isset( $instance['show_reg_link'] ) ? $instance['show_reg_link'] : false;
    $show_remember_link = isset( $instance['show_remember_link'] ) ? $instance['show_remember_link'] : false;
    $filters = isset( $instance['filters'] ) ? $instance['filters'] : false;
    $form_page = isset( $instance['form_page'] ) ? $instance['form_page'] : false;
    ?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', ud_get_wpp_feps()->domain); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
    </p>
    <?php if ( class_exists( 'wpi_spc' ) ) : ?>
    <p>
      <label for="<?php echo $this->get_field_id('show_balance'); ?>">
        <input id="<?php echo $this->get_field_id('show_balance'); ?>" name="<?php echo $this->get_field_name('show_balance'); ?>" type="checkbox" value="false" <?php if( $show_balance=='false' ) echo " checked='checked';"; ?> />
        <?php _e('Don\'t show Funds Balance',ud_get_wpp_feps()->domain); ?>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('show_spc_link'); ?>">
        <input id="<?php echo $this->get_field_id('show_spc_link'); ?>" name="<?php echo $this->get_field_name('show_spc_link'); ?>" type="checkbox" value="false" <?php if($show_spc_link=='false') echo " checked='checked';"; ?> />
        <?php _e('Don\'t show \'Add Credits to Balance\' link ',ud_get_wpp_feps()->domain); ?>
      </label>
    </p>
    <?php endif; ?>
    <p>
      <label for="<?php echo $this->get_field_id('filters'); ?>">
        <input id="<?php echo $this->get_field_id('filters'); ?>" name="<?php echo $this->get_field_name('filters'); ?>" type="checkbox" value="true" <?php if($filters=='true') echo " checked='checked';"; ?> />
        <?php printf( __( 'Show Filter by Statuses instead of default link to \'My %s\' page', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'plural' ) ); ?>
      </label>
    </p>
    <?php if( !empty( $form_pages ) && is_array( $form_pages ) ) : ?>
    <p>
      <label for="<?php echo $this->get_field_id('form_page'); ?>"><?php printf( __('\'Add New %s\' link to page:',ud_get_wpp_feps()->domain), WPP_F::property_label() ); ?><br/>
        <select id="<?php echo $this->get_field_name('form_page'); ?>" name="<?php echo $this->get_field_name('form_page'); ?>" class="widefat" >
          <option value="">-</option>
          <?php foreach( $form_pages as $fp ) : ?>
          <option value="<?php echo WPP_F::base_url( $fp[ 'ID' ] ); ?>" <?php echo $form_page == WPP_F::base_url( $fp[ 'ID' ] ) ? 'selected="selected"' : ''; ?>><?php echo $fp[ 'post_title' ]; ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </p>
    <?php endif; ?>
    <p>
      <label for="<?php echo $this->get_field_id('show_login_form'); ?>">
        <input id="<?php echo $this->get_field_id('show_login_form'); ?>" name="<?php echo $this->get_field_name('show_login_form'); ?>" type="checkbox" value="true" <?php checked('true', $show_login_form); ?> />
        <?php _e('Display login form',ud_get_wpp_feps()->domain); ?>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('show_reg_link'); ?>">
        <input id="<?php echo $this->get_field_id('show_reg_link'); ?>" name="<?php echo $this->get_field_name('show_reg_link'); ?>" type="checkbox" value="true" <?php checked('true', $show_reg_link); ?> />
        <?php _e('Display registration link in login form',ud_get_wpp_feps()->domain); ?>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('show_remember_link'); ?>">
        <input id="<?php echo $this->get_field_id('show_remember_link'); ?>" name="<?php echo $this->get_field_name('show_remember_link'); ?>" type="checkbox" value="true" <?php checked('true', $show_remember_link); ?> />
        <?php _e('Display link "Remember password"  in login form',ud_get_wpp_feps()->domain); ?>
      </label>
    </p>
    <p>
    <!-- Relative URL to redirect user to after a successful login. -->
    <label for="<?php echo $this->get_field_id('redirect'); ?>">
      <?php _e('Path to redirect',ud_get_wpp_feps()->domain); ?>
      <input id="<?php echo ($this->get_field_id('redirect')) ? $this->get_field_id('redirect') : ''; ?>" name="<?php echo ($this->get_field_name('redirect')) ? $this->get_field_name('redirect') : ''; ?>" style="width: 100%;" type="text" value="<?php if($instance['redirect']){ echo esc_attr($instance['redirect']);} ?>" />
    </label>
    </p>
    <?php if(!$instance['login_form_description']){ ?>
      <p  class="fepsShowLoginDescr">
        <?php _e('<b>Click to add description for login form</b>',ud_get_wpp_feps()->domain); ?>
      </p>
      <p class="fepsShowLoginDescrBody" style="display: none">
        <label for="<?php echo ($this->get_field_id('login_form_description')) ? $this->get_field_id('login_form_description') : ''; ?>">
          <textarea id="<?php echo ($this->get_field_id('login_form_description')) ? $this->get_field_id('login_form_description') : ''; ?>" name="<?php echo ($this->get_field_name('login_form_description')) ? $this->get_field_name('login_form_description') : ''; ?>" style="width: 100%;" ><?php echo !empty($instance['login_form_description']) ? esc_attr( $instance['login_form_description'] ) : '' ?></textarea>
        </label>
      </p>
    <?php } else { ?>
      <p class="fepsShowLoginDescrBody">
        <label for="<?php echo ($this->get_field_id('login_form_description')) ? $this->get_field_id('login_form_description') : ''; ?>">
          <?php _e('<b>Description for login form:</b>',ud_get_wpp_feps()->domain); ?>
          <textarea id="<?php echo $this->get_field_id('login_form_description'); ?>" name="<?php echo ($this->get_field_name('login_form_description') ? $this->get_field_name('login_form_description') : ''); ?>" style="width: 100%;" ><?php echo !empty($instance['login_form_description']) ? esc_attr( $instance['login_form_description'] ) : ''; ?></textarea>
        </label>
      </p>
    <?php } ?>
    <script>
      jQuery(document).ready(function(){
        jQuery('.fepsShowLoginDescr').on( 'click', function(){
          jQuery('.fepsShowLoginDescrBody').toggle();
        });
      });
    </script>
    <?php
  }
} // end class MyFepsWidget


/**
 * MyFepsInfoWidget Class
 */
class MyFepsInfoWidget extends WP_Widget {

  public function __construct() {
    parent::__construct( false, $name = __( 'FEPS Information' ), array( 'description' => sprintf( __( 'Shows Information about current FEPS %1$s. It\'s available only for logged in users and shown only on Edit %1$s page.', ud_get_wpp_feps()->domain ), WPP_F::property_label( 'singular' ) ) ) );
  }

  public function widget($args, $instance) {
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    extract( $args );

    $instance['before'] = $before_widget;
    $widget_title = apply_filters('widget_title', $instance['title']);
    if ( $widget_title ) {
      $instance['before'] .= $before_title . $widget_title . $after_title;
    }
    $instance['after'] = $after_widget;
    $instance['title'] = false;

    echo class_wpp_feps::wpp_feps_info( $instance );
  }

  public function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  public function form( $instance ) {
    global $wp_properties;

    $title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
    ?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', ud_get_wpp_feps()->domain); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
    </p>
    <?php
  }
} // end class MyFepsInfoWidget


/**
 * External function for FEPS Credits
 *
 * @param array $data
 * @return array
 * @author korotkov@ud
 */
function wpp_add_feps_credits( $data ) {

  //** If we have required data */
  if ( !empty( $data['user_data']['ID'] ) && !empty( $data['other_meta']['charge_amount'] ) ) {

    //** Get existing FEPS credits */
    $existing_feps_credits = (float)get_user_meta((int)$data['user_data']['ID'], FEPS_USER_CREDITS, 1);

    //** Sum with new credits */
    $existing_feps_credits += (float)$data['other_meta']['charge_amount'];

    //** Update credits meta with new amount */
    update_user_meta((int)$data['user_data']['ID'], FEPS_USER_CREDITS, $existing_feps_credits);
  }

  do_action( 'wpp_feps_add_feps_credits', array( 'data' => $data, 'credits' => $existing_feps_credits ) );

  return $data;
}