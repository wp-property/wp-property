<?php
if (!class_exists('WPP_Setup_Assistant')) {

  class WPP_Setup_Assistant {

    function __construct() {
      flush_rewrite_rules();
      //** flush Object Cache */
      wp_cache_flush();
      $this->load_assistant_files();
    }

    function load_assistant_files() {
      //owl-carousel base css
      wp_enqueue_style('setup-assist-owl-css', WPP_URL . "splashes/assets/css/owl.carousel.css", array(), WPP_Version, 'screen');
      //page css
      wp_enqueue_style('setup-assist-page-css', WPP_URL . "splashes/assets/css/setup-assist.css", array(), WPP_Version, 'screen');
      wp_enqueue_script('setup-assist-owl-js', WPP_URL . "splashes/assets/js/owl.carousel.min.js", array('jquery'), WPP_Version, true);
      wp_enqueue_script('setup-assist-page-js', WPP_URL . "splashes/assets/js/setup-assist.js", array('jquery'), WPP_Version, true);
    }

    function render_setup_page($freemius_optin_slide) {
      ?>

      <style>
        .ud-badge.wp-badge {
          background-image: url("<?php echo ud_get_wp_property()->path('/static/images/icon.png', 'url'); ?>")  !important ;
        }
        #wpp-splash-screen .owl-buttons div.owl-prev::before {
          background: rgba(0, 0, 0, 0) url("<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/back.png', 'url'); ?>") no-repeat scroll 0 0 / 100% auto  !important;
        }
        #wpp-splash-screen .owl-buttons div.owl-next::before {
          background: rgba(0, 0, 0, 0) url("<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/next.png', 'url'); ?>") no-repeat scroll 0 0 / 100% auto  !important;
        }
        .wpp_asst_select,.wpp_asst_screen table.wpp_layouts_table th select {
          background: #fff url("<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/icon1.png', 'url'); ?>") no-repeat scroll 100% 50%  !important;
        }
        #wpbody-content {
          background :url("<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/wpp_backimage.png', 'url'); ?>")   repeat-x bottom  !important;  
        }
      </style>

      <?php
      global $wp_properties;

//save backup
      $data = apply_filters('wpp::backup::data', array('wpp_settings' => $wp_properties));
      $timestamp = time();
      if (get_option("wpp_property_backups"))
        $backups = get_option("wpp_property_backups");
      else
        $backups = array();

      $backups[$timestamp] = $data;
      update_option("wpp_property_backups", $backups);

//enable assistant for new installations
      if (!isset($wp_properties['configuration']["show_assistant"])) {
        $freshInstallation = 'yes';
      } else {
        $freshInstallation = 'no';
      }

// add default features and taxanomies. 
//being added here instead of "save_setup_settings" to keep setup speed for ajax under check
      if (taxonomy_exists('property_feature')) {
        wp_insert_term(__('Cable Prewire'), 'property_feature', array('slug' => 'cable_prewire'));
        wp_insert_term(__('Cathedral Ceiling'), 'property_feature', array('slug' => 'cathedral_ceiling'));
        wp_insert_term(__('Disability Equipped'), 'property_feature', array('slug' => 'disability_equipped'));
      }
      if (taxonomy_exists('community_feature')) {
        wp_insert_term(__('Dishwasher'), 'community_feature', array('slug' => 'dishwasher'));
        wp_insert_term(__('Double Oven'), 'community_feature', array('slug' => 'double_oven'));
        wp_insert_term(__('Central Vacuum'), 'community_feature', array('slug' => 'central_vacuum'));
      }

      $wp_properties["properties_page_error"] = __('Please enter Properties Page name', ud_get_wp_property()->domain);
      $wp_properties["no_link_available"] = __('Necessary data missing.Please check all values.', ud_get_wp_property()->domain);
      $property_assistant = json_encode($wp_properties);
      echo "<script> var wpp_property_assistant = $property_assistant; </script>";
      ?>
      <div class="wrap about-wrap">
        <div class="wp-badge ud-badge"></div>  
        <div id="wpp-splash-screen">

          <?php wp_nonce_field('wpp_setting_save'); ?>

          <div class="loader-div"><img src="<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/loader.gif', 'url'); ?>" alt="image"></div> 
          <form id="wpp-setup-assistant" name="wpp-setup-assistant">
            <div id="wpp-splash-screen-owl" class="owl-carousel">

              <div class="item">
                <div class="wpp_asst_screen wpp_asst_screen_freemius">
                  <?php
                  /* if following code is exectuted from setup assistant page
                   * force optin render manually
                   * else if page is called from within freemius optin
                   * then echo the html we already have
                   */
                  echo $freemius_optin_slide;
                  ?>
                </div>
              </div>

              <div class="item">
                <div class="wpp_asst_screen wpp_asst_screen_2">
                  <h2 class="wpp_asst_heading"><b><?php echo __('Which Property Types do you want to have on your site?', ud_get_wp_property()->domain); ?></b></h2>

                  <div class="wpp_asst_inner_wrap">
                    <ul class="">               
                      <li class="wpp_asst_label"><?php echo __('House', ud_get_wp_property()->domain); ?> 
                        <label for="property_types_house">
                          <input type="checkbox" class="wpp_box asst_prop_types" name="wpp_settings[property_types][house]"  value="House" id="property_types_house"  <?php if (isset($wp_properties['property_types']) && in_array("house", array_keys($wp_properties['property_types']))) echo "checked"; ?> />
                          <span></span> </label></li>	

                      <li class="wpp_asst_label"> 
                        <?php echo __('Condo', ud_get_wp_property()->domain); ?>
                        <label for="property_types_condo"> 
                          <input type="checkbox" class="wpp_box asst_prop_types" name="wpp_settings[property_types][condo]"  value="Condo" id="property_types_condo"  <?php if (isset($wp_properties['property_types']) && in_array("condo", array_keys($wp_properties['property_types']))) echo "checked"; ?> />
                          <span></span> </label></li>

                      <li class="wpp_asst_label"> <?php echo __('Townhouse', ud_get_wp_property()->domain); ?>
                        <label for="property_types_townhouse"> 
                          <input type="checkbox" class="wpp_settings_property_stats" name="wpp_settings[property_types][townhouse]" id="property_types_townhouse" value="Townhouse" <?php if (isset($wp_properties['property_types']) && in_array("townhouse", array_keys($wp_properties['property_types']))) echo "checked"; ?>/>
                          <span></span> </label></li>

                      <li class="wpp_asst_label"> <?php echo __('Multi-Family', ud_get_wp_property()->domain); ?>
                        <label for="property_types_multifamily"> 
                          <input class="wpp_box  asst_prop_types" id="property_types_multifamily" type="checkbox" value="Multi-Family" data-label="" 
                                 name="wpp_settings[property_types][multifamily]" <?php if (isset($wp_properties['property_types']) && in_array("multifamily", array_keys($wp_properties['property_types']))) echo "checked"; ?>> <span></span> </label></li>

                      <li class="wpp_asst_label"> <?php echo __('Land', ud_get_wp_property()->domain); ?>
                        <label for="property_types_land"> 
                          <input class="wpp_box asst_prop_types" type="checkbox" value="Land" name="wpp_settings[property_types][land]" id="property_types_land" <?php if (isset($wp_properties['property_types']) && in_array("land", array_keys($wp_properties['property_types']))) echo "checked"; ?>> <span></span> </label></li>

                      <li class="wpp_asst_label"> <?php echo __('Commercial', ud_get_wp_property()->domain); ?>
                        <label for="property_types_commercial"> 
                          <input class="wpp_box asst_prop_types" type="checkbox" value="Commercial" name="wpp_settings[property_types][commercial]" id="property_types_commercial" <?php if (isset($wp_properties['property_types']) && in_array("commercial", array_keys($wp_properties['property_types']))) echo "checked"; ?> accept=""> <span></span> </label></li> 
                    </ul>      
                  </div> <!-- wpp_asst_inner_wrap --> 

                  <div class="foot-note">
                    <a href="javascript:;">
                      <h3> 
                        <?php echo __('If you do not see your property type click here', ud_get_wp_property()->domain); ?></h3>
                    </a>
                    <div class="wpp_toggl_desctiption">
                      <?php echo __('Custom Property Type can be created and managed in Properties/Settings/Developer Tab/Terms', ud_get_wp_property()->domain); ?>
                    </div>
                  </div>

                </div><!-- wpp_asst_screen wpp_asst_screen_2 --> 
              </div><!-- item --> 

              <div class="item  item-wider">
                <div class="wpp_asst_screen wpp_asst_screen_6">
                  <?php
                  $layouts = new UsabilityDynamics\WPP\Layouts_Settings();
                  echo $layouts->setup_assistant_layouts();
                  ?>
                </div>
              </div>
              <div class="item">
                <div class="wpp_asst_screen wpp_asst_screen_6">
                  <h2 class="wpp_asst_heading text-center"><b><?php echo __("Let's view what we have", ud_get_wp_property()->domain); ?></b></h2>
                  <ul class="list-img">

                    <li>
                      <img src="<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/overview-prop.jpg', 'url'); ?>" alt="image">
                    <center><a class="btn_single_page oviews" href="<?php echo get_admin_url(); ?>edit.php?post_type=property&page=all_properties"><?php echo __('OVERVIEW OF PROPERTIES', ud_get_wp_property()->domain); ?></a></center>
                    </li>
                    <li>
                      <span><img src="<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/wpp-single-prop.jpg', 'url'); ?>" alt="image"></span>
                    <center><a class="btn_single_page dash" href="<?php echo get_admin_url(); ?>"><?php echo __('SKIP THIS STEP', ud_get_wp_property()->domain); ?></a></center>
                    </li>
                  </ul>
                  <div class="wpp-asst_hidden-attr">
                    <input  type="hidden" name="wpp_settings[configuration][dummy-prop]"  value="yes-please"> 
                    <!--  add field to recognize the source on save--> 
                    <input  type="hidden" name="wpp_freshInstallation" value="<?php echo $freshInstallation; ?>">      
                  </div>
                </div>
              </div>
            </div>

          </form >
        </div>
      </div>
      <?php
    }

  }

}
/*
 * This page has been suppressed.
 * OLD FLOW
 * class-dashboard > install.php
 * NEW FLOW
 * class-dashboard > FREEMIUM [ wpp_fs()->_connect_page_render(); ] > include "install.php" > Call above class
 * 
 */