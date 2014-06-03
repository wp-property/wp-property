<?php
/**
 * Page handles all the settings configuration for WP-Property. Premium features can hook into this page.
 *
 * @version 1.12
 * @package   WP-Property
 * @author     team@UD
 * @copyright  2012-2014 Usability Dyanmics, Inc.
 */

$_model = array(
  'errors'          => get_settings_errors( 'wpp' ),
  'object_label'    => array(
    'singular'        => WPP_F::property_label( 'singular' ),
    'plural'          => WPP_F::property_label( 'plural' )
  ),
  'ajax_url'        => admin_url( 'admin-ajax.php' ),
  'home_url'        => home_url(),
  'user_logged_in'  => is_user_logged_in() ? 'true' : 'false',
  'site_domain'     => WPP_F::site_domain(),
  'custom_css'      => ( file_exists( STYLESHEETPATH . '/wp_properties.css' ) || file_exists( TEMPLATEPATH . '/wp_properties.css' ) ),
  'settings_nav'    => apply_filters( 'wpp_settings_nav', array() ),
  'conditionals'    => array( get_option( 'permalink_structure' ) == '' ? 'no_permalinks' : 'have_permalinks' ),
  'custom_styles'   => apply_filters( 'wpp::custom_styles', false ),
  'localization'    => WPP_F::draw_localization_dropdown( 'return_array=true' ),
  'wp_properties'   => $wp_properties
);

?>
<div class="wrap wpp_settings_page" data-requires="<?php echo plugins_url( 'static/scripts/src/wpp.admin.settings.vm.js', WPP_Core::$path ); ?>">

  <h2 class="nav-tab-wrapper hidden">
    <a href="#main" class="nav-tab nav-tab-active"><?php _e( 'Main', 'wpp' ); ?></a>
    <a href="#modules" class="nav-tab"><?php _e( 'Modules', 'wpp' ); ?></a>
    <a href="#tools" class="nav-tab"><?php _e( 'Tools', 'wpp' ); ?></a>
    <a href="#add-schedule" class="add-new-h2"><?php _e( 'Setup Wizard', 'wpp' ); ?></a>
  </h2>

  <?php settings_errors( 'wpp' ); ?>

  <form id="wpp_settings_form" method="post" action="<?php echo admin_url( 'edit.php?post_type=property&page=property_settings' ); ?>" enctype="multipart/form-data">

    <div class="wpp-ui-panel-right">
      <div class="wpp-ui-outer"><div class="wpp-ui-inner"><?php do_accordion_sections( get_current_screen()->id, 'main', $_model ); ?></div></div>
      <div class="wpp-ui-sidebar"><?php do_accordion_sections( get_current_screen()->id, 'side', $_model ); ?></div>
    </div>

  </form>
</div>

<?php // do_meta_boxes( get_current_screen()->id, 'templates', $_model ); ?>