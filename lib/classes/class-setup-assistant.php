<?php
/**
 * Setup Assistant
 *
 * This page has been suppressed.
 * OLD FLOW
 * class-dashboard > install.php
 * NEW FLOW
 * class-dashboard > FREEMIUM [ wpp_fs()->_connect_page_render(); ] > include "install.php" > Call above class
 *
 */
namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\Setup_Assistant')) {

    class Setup_Assistant {

      function __construct() {

        //flush_rewrite_rules();
        //** flush Object Cache */
        //wp_cache_flush();

        //add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

      }

      /**
       * Enqueue Scripts.
       *
       */
      static public function admin_enqueue_scripts() {
        //owl-carousel base css
        wp_enqueue_style( 'setup-assist-owl-css', WPP_URL . "styles/owl.carousel.css", array(), WPP_Version, 'screen' );
        //page css
        wp_enqueue_style( 'setup-assist-page-css', WPP_URL . "styles/wpp.admin.setup.css", array(), WPP_Version, 'screen' );
        wp_enqueue_script( 'setup-assist-owl-js', WPP_URL . "scripts/owl.carousel.min.js", array( 'jquery' ), WPP_Version, true );
        wp_enqueue_script( 'setup-assist-page-js', WPP_URL . "scripts/wpp.admin.setup.js", array( 'jquery', 'setup-assist-owl-js' ), WPP_Version, true );
      }

      /**
       * Render UI
       *
       * @param string $freemius_optin_slide
       */
      static public function render_setup_page( $freemius_optin_slide = '' ) {
        global $wp_properties;

        ?>

        <style>
      .ud-badge.wp-badge {
        background-image: url("<?php echo ud_get_wp_property()->path('/static/images/icon.png', 'url'); ?>") !important;
      }

      #wpp-splash-screen .owl-buttons div.owl-prev::before {
        background: rgba(0, 0, 0, 0) url("<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/back.png', 'url'); ?>") no-repeat scroll 0 0 / 100% auto !important;
      }

      #wpp-splash-screen .owl-buttons div.owl-next::before {
        background: rgba(0, 0, 0, 0) url("<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/next.png', 'url'); ?>") no-repeat scroll 0 0 / 100% auto !important;
      }

      .wpp_asst_select, .wpp_asst_screen table.wpp_layouts_table th select {
        background: #fff url("<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/icon1.png', 'url'); ?>") no-repeat scroll 100% 50% !important;
      }

      #wpbody-content {
        background: url("<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/wpp_backimage.png', 'url'); ?>") repeat-x bottom !important;
      }
    </style>
        <div class="wrap about-wrap">
      <div class="wp-badge ud-badge"></div>
      <div id="wpp-splash-screen">

        <?php wp_nonce_field( 'wpp_setting_save' ); ?>

        <div class="loader-div"><img src="<?php echo ud_get_wp_property()->path( '/static/splashes/assets/images/loader.gif', 'url' ); ?>" alt="image"></div>
          <form id="wpp-setup-assistant" name="wpp-setup-assistant">
            <div id="wpp-splash-screen-owl" class="owl-carousel">

              <div class="item">
                <div class="wpp_asst_screen wpp_asst_screen_2">
                  <h2 class="wpp_asst_heading"><b><?php echo __( 'Which Property Types do you want to have on your site?', ud_get_wp_property()->domain ); ?></b></h2>

                  <div class="wpp_asst_inner_wrap">
                    <ul class="">
                      <li class="wpp_asst_label"><?php echo __( 'House', ud_get_wp_property()->domain ); ?>
                        <label for="property_types_house">
                          <input type="checkbox" class="wpp_box asst_prop_types" name="wpp_settings[property_types][house]" value="House" id="property_types_house" <?php if( isset( $wp_properties[ 'property_types' ] ) && in_array( "house", array_keys( $wp_properties[ 'property_types' ] ) ) ) echo "checked"; ?> />
                          <span></span> </label></li>

                      <li class="wpp_asst_label">
                        <?php echo __( 'Condo', ud_get_wp_property()->domain ); ?>
                        <label for="property_types_condo">
                          <input type="checkbox" class="wpp_box asst_prop_types" name="wpp_settings[property_types][condo]" value="Condo" id="property_types_condo" <?php if( isset( $wp_properties[ 'property_types' ] ) && in_array( "condo", array_keys( $wp_properties[ 'property_types' ] ) ) ) echo "checked"; ?> />
                          <span></span> </label></li>

                      <li class="wpp_asst_label"> <?php echo __( 'Townhouse', ud_get_wp_property()->domain ); ?>
                        <label for="property_types_townhouse">
                          <input type="checkbox" class="wpp_settings_property_stats" name="wpp_settings[property_types][townhouse]" id="property_types_townhouse" value="Townhouse" <?php if( isset( $wp_properties[ 'property_types' ] ) && in_array( "townhouse", array_keys( $wp_properties[ 'property_types' ] ) ) ) echo "checked"; ?>/>
                          <span></span> </label></li>

                      <li class="wpp_asst_label"> <?php echo __( 'Multi-Family', ud_get_wp_property()->domain ); ?>
                        <label for="property_types_multifamily">
                          <input class="wpp_box  asst_prop_types" id="property_types_multifamily" type="checkbox" value="Multi-Family" data-label=""
                                 name="wpp_settings[property_types][multifamily]" <?php if( isset( $wp_properties[ 'property_types' ] ) && in_array( "multifamily", array_keys( $wp_properties[ 'property_types' ] ) ) ) echo "checked"; ?>> <span></span> </label></li>

                      <li class="wpp_asst_label"> <?php echo __( 'Land', ud_get_wp_property()->domain ); ?>
                        <label for="property_types_land">
                          <input class="wpp_box asst_prop_types" type="checkbox" value="Land" name="wpp_settings[property_types][land]" id="property_types_land" <?php if( isset( $wp_properties[ 'property_types' ] ) && in_array( "land", array_keys( $wp_properties[ 'property_types' ] ) ) ) echo "checked"; ?>> <span></span> </label></li>

                      <li class="wpp_asst_label"> <?php echo __( 'Commercial', ud_get_wp_property()->domain ); ?>
                        <label for="property_types_commercial">
                          <input class="wpp_box asst_prop_types" type="checkbox" value="Commercial" name="wpp_settings[property_types][commercial]" id="property_types_commercial" <?php if( isset( $wp_properties[ 'property_types' ] ) && in_array( "commercial", array_keys( $wp_properties[ 'property_types' ] ) ) ) echo "checked"; ?> accept=""> <span></span> </label></li>
                    </ul>
                  </div> <!-- wpp_asst_inner_wrap -->

                  <div class="foot-note">
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

              <div class="item  item-wider">
                <div class="wpp_asst_screen wpp_asst_screen_5">

                  <h2 class="wpp_asst_heading"><b><?php echo __( 'Do you need to keep track of agents?', ud_get_wp_property()->domain ); ?></b></h2>

                  <div class="wpp_asst_inner_wrap">
                    <ul>
                      <li class="wpp_asst_label"> <?php echo __( 'Yes Please', ud_get_wp_property()->domain ); ?>
                        <label for="yes-please">
                          <input class="wpp_box" type="radio" value="yes-please" name="wpp_settings[wpp-addons][wpp-agents]" id="yes-please" <?php if( isset( $wp_properties[ 'wpp-addons' ][ 'wpp-agents' ] ) && $wp_properties[ 'wpp-addons' ][ 'wpp-agents' ] == "yes-please" ) echo "checked='checked'"; ?>> <span></span> </label>
                      </li>
                      <li class="wpp_asst_label"><?php echo __( 'No thanks', ud_get_wp_property()->domain ); ?>
                        <label for="no-thanks">
                          <input class="wpp_box" type="radio" value="no-thanks" name="wpp_settings[wpp-addons][wpp-agents]" id="no-thanks" <?php if( isset( $wp_properties[ 'wpp-addons' ][ 'wpp-agents' ] ) && $wp_properties[ 'wpp-addons' ][ 'wpp-agents' ] == "no-thanks" ) echo "checked='checked'"; ?> > <span></span> </label>
                      </li>
                    </ul>

                    <small class="center foot-note">(Installs WP-Property Agents Addon)</small>
                  </div> <!-- wpp_asst_inner_wrap -->

                  <h2 class="wpp_asst_heading"><b><?php echo __( 'Do you want to display interactive searchable maps?', ud_get_wp_property()->domain ); ?></b></h2>
                  <div class="wpp_asst_inner_wrap">
                    <ul>
                      <li class="wpp_asst_label"> <?php echo __( 'Sure', ud_get_wp_property()->domain ); ?>
                        <label for="yes-please">
                          <input class="wpp_box" type="radio" value="yes-please" name="wpp_settings[wpp-addons][wpp-supermap]" id="yes-please" <?php if( isset( $wp_properties[ 'wpp-addons' ][ 'wpp-supermap' ] ) && $wp_properties[ 'wpp-addons' ][ 'wpp-supermap' ] == "yes-please" ) echo "checked='checked'"; ?>> <span></span> </label>
                      </li>
                      <li class="wpp_asst_label"><?php echo __( 'Maybe later', ud_get_wp_property()->domain ); ?>
                        <label for="no-thanks">
                          <input class="wpp_box" type="radio" value="no-thanks" name="wpp_settings[wpp-addons][wpp-supermap]" id="no-thanks" <?php if( isset( $wp_properties[ 'wpp-addons' ][ 'wpp-supermap' ] ) && $wp_properties[ 'wpp-addons' ][ 'wpp-supermap' ] == "no-thanks" ) echo "checked='checked'"; ?> > <span></span> </label>
                      </li>
                    </ul>
                    <small class="center foot-note">(Installs WP-Property Supermap Add)</small>
                  </div> <!-- wpp_asst_inner_wrap -->
                </div>
              </div>

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
                    <input type="hidden" name="wpp_freshInstallation" value="<?php echo $freshInstallation; ?>">
                  </div>
                </div>
              </div>

          </div>

        </form>
      </div>
    </div>
        <?php
      }

    }
  }
}

