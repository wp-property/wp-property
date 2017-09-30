<?php
/**
 *
 *
 */
global $wp_properties;

$_standard = UsabilityDynamics\WPP\Setup_Assistant::get_standard_data();

?>
<div class="wrap about-wrap wpp-setup-assistant">

<div class="wp-badge ud-badge"></div>

<div id="wpp-splash-screen">

<?php wp_nonce_field( 'wpp_setting_save' ); ?>

<div class="loader-div"><img src="<?php echo ud_get_wp_property()->path( '/static/images/loader.gif', 'url' ); ?>" alt="image"></div>

<form id="wpp-setup-assistant" name="wpp-setup-assistant">

  <div id="wpp-splash-screen-owl" class="owl-carousel">

    <div class="item">
      <div class="wpp_asst_screen wpp_asst_screen_2">
        <h2 class="wpp_asst_heading"><b><?php echo __( 'Which Property Types do you want to have on your site?', ud_get_wp_property()->domain ); ?></b></h2>

        <div class="wpp_asst_inner_wrap">
          <ul class="wpp-property-types">

            <?php foreach( $_standard->types as $_type ) { ?>
            <li class="wpp_asst_label">

              <label>
                <span><?php echo $_type->displayTitle; ?></span>
                <input type="checkbox" class="wpp_box asst_prop_types" name="wpp_settings[property_types][<?php echo $_type->slug; ?>]" value="<?php echo $_type->displayTitle; ?>" <?php if( isset( $wp_properties[ 'property_types' ] ) && in_array( $_type->slug, array_keys( $wp_properties[ 'property_types' ] ) ) ) echo "checked"; ?> />
                <span></span>
              </label>
            </li>

            <?php } ?>
          </ul>
        </div> <!-- wpp_asst_inner_wrap -->

        <div class="foot-note hidden">
          <a href="javascript:;">
            <h3>
              <?php echo __( 'If you do not see your property type click here', ud_get_wp_property()->domain ); ?></h3>
          </a>
          <div class="wpp_toggl_desctiption">
            <?php echo __( 'Custom Property Type can be created and managed in Properties/Settings/Developer Tab/Terms', ud_get_wp_property()->domain ); ?>
          </div>
        </div>

      </div><!-- wpp_asst_screen wpp_asst_screen_2 -->
    </div><!-- item -->

    <?php do_action( 'wpp::setup_assistant::view' ); ?>

    <div class="item">
      <div class="wpp_asst_screen wpp_asst_screen_6">
        <h2 class="wpp_asst_heading maybe_away text-center"><b><?php echo __( "We have created test properties for you", ud_get_wp_property()->domain ); ?></b></h2>
        <ul class="list-img">
          <li>
          <center><a class="btn_single_page dash" href="<?php echo get_admin_url(); ?>edit.php?post_type=property&page=all_properties"><?php echo __( 'Add/edit properties', ud_get_wp_property()->domain ); ?></a></center>
          </li>
          <li>
          <center><a class="btn_single_page oviews" href="<?php echo get_admin_url(); ?>edit.php?post_type=property&page=all_properties"><?php echo __( 'View property listings', ud_get_wp_property()->domain ); ?></a></center>
          </li>

        </ul>
        <div class="wp-install-addons">
          <a href="<?php echo get_admin_url(); ?>edit.php?post_type=property&page=wp-property-addons">Finish the wizard and continue installing addon(s)</a>
        </div>
        <div class="wpp-asst_hidden-attr">
          <input type="hidden" name="wpp_settings[configuration][dummy-prop]" value="yes-please">
          <!--  add field to recognize the source on save-->
          <input type="hidden" name="wpp_freshInstallation" value="<?php echo isset( $freshInstallation ) ? $freshInstallation : 'false'; ?>">
        </div>
      </div>
    </div>

</div>

</form>
</div>

</div>