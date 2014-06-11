<?php
/**
 * Name: Settings
 * Group: Pages
 * Description: Page handles all the settings configuration for WP-Property. Premium features can hook into this page.
 *
 * @copyright  2012-2014 Usability Dyanmics, Inc.
 */

global $wp_properties, $screen_layout_columns;

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
<script>

  // Ghetto hack to accordion module.
  jQuery( document ).ready( function () {
    jQuery( '.nav-tab-wrapper .accordion-section-title' ).on( 'click', function( event ) {
      jQuery( '.accordion-section-title', document.getElementById( event.target.getAttribute( 'data-box-id' ) ) ).trigger( 'click' );
    });
  });

</script>
<style type="text/css">

  .wpp-devel-only {
    display: none;
  }

  .wpp-ui-panel-right .wpp-ui-outer {
    margin-top: 0;
  }

  .accordion-section {
    border-bottom: 0;
  }

  .accordion-section.open {
    border-bottom: 1px solid #dfdfdf;
  }

  .wpp-ui-inner .accordion-section-title {

  }

  .wpp-ui-inner .accordion-section-content {
    background: transparent;
    padding-top: 0;
    padding-left: 10px;
  }

</style>

<div class="wrap wpp_settings_page" data-requires="<?php echo plugins_url( 'static/scripts/src/wpp.admin.settings.vm.js', WPP_Core::$path . '/wpp.admin.settings.vm.js' ); ?>">

  <h2 class="nav-tab-wrapper">
    <?php UsabilityDynamics\UI::do_tabs( get_current_screen()->id, 'main' ); ?>
    <a href="#add-schedule" class="add-new-h2 wpp-devel-only"><?php _e( 'Setup Wizard', 'wpp' ); ?></a>
  </h2>

  <?php settings_errors( 'wpp' ); ?>

  <form id="wpp_settings_form" method="post" action="<?php echo admin_url( 'edit.php?post_type=property&page=property_settings' ); ?>" enctype="multipart/form-data">

    <div class="wpp-ui-panel-right">
      <div class="wpp-ui-outer"><div class="wpp-ui-inner"><?php UsabilityDynamics\UI::do_sections( get_current_screen()->id, 'main', $_model ); ?></div></div>
      <div class="wpp-ui-sidebar"><?php UsabilityDynamics\UI::do_accordion_sections( get_current_screen()->id, 'side', $_model ); ?></div>
    </div>

  </form>
</div>

<script type="text/html" id="wpp.settings.mesage">
  <div data-bind="">Update Message Template</div>
</script>
