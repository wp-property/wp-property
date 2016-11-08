<?php
// /**
// * Welcome (Upgrade) WP-Property page
// */flush Rewrite Rules */
flush_rewrite_rules();
//** flush Object Cache */
wp_cache_flush();
//owl-carousel base css
wp_enqueue_style('setup-assist-owl-css', WPP_URL . "splashes/assets/css/owl.carousel.css", array(), WPP_Version, 'screen');
//page css
wp_enqueue_style('setup-assist-page-css', WPP_URL . "splashes/assets/css/setup-assist.css", array(), WPP_Version, 'screen');

wp_enqueue_script('setup-assist-owl-js', WPP_URL . "splashes/assets/js/owl.carousel.min.js", array('jquery'), WPP_Version, true);
wp_enqueue_script('setup-assist-page-js', WPP_URL . "splashes/assets/js/setup-assist.js", array('jquery'), WPP_Version, true);
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
  .wpp_asst_select {
    background: #fff url("<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/icon1.png', 'url'); ?>") no-repeat scroll 100% 50%  !important;
  }
  #wpbody-content {
    background :url("<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/wpp_backimage.png', 'url'); ?>")   repeat-x bottom  !important;  
  }
</style>

<?php
global $wp_properties;

//save backup
$data = apply_filters( 'wpp::backup::data', array( 'wpp_settings' => $wp_properties ) );
$timestamp =time();
if(get_option("wpp_property_backups"))
  $backups = get_option("wpp_property_backups") ;
else 
  $backups = array();

$backups[$timestamp]= $data;
update_option("wpp_property_backups",$backups);

//enable assistant for new installations
if(!isset($wp_properties['configuration']["show_assistant"])){
    $freshInstallation = 'yes';
}
else{
    $freshInstallation = 'no';
}
$wp_properties["properties_page_error"] = __('Please enter Properties Page name', ud_get_wp_property()->domain);
$wp_properties["no_link_available"] = __('Necessary data missing.Please check all values.', ud_get_wp_property()->domain);
$property_assistant = json_encode($wp_properties);
echo "<script> var wpp_property_assistant = $property_assistant; </script>";
?>
<div id="wpp-splash-screen">
  <form id="wpp-setup-assistant">
    <?php wp_nonce_field('wpp_setting_save'); ?>
    
    <div class="loader-div"><img src="<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/loader.gif', 'url'); ?>" alt="image"></div> 
    <div id="wpp-splash-screen-owl" class="owl-carousel">

      <div class="item">
        <div class="wpp_asst_screen wpp_asst_screen_1">
          <h2 class="wpp_asst_heading_main"><?php  echo __('WELCOME TO', ud_get_wp_property()->domain); ?></h2>
          <h1><?php echo __('WP-PROPERTY PLUGIN!', ud_get_wp_property()->domain); ?></h1>
          <p class="tagline"><?php echo __('Make few steps in order to set up it on your site!', ud_get_wp_property()->domain); ?></p>
          <center><button type="button" class="btn_letsgo"><?php echo __("LET'S GO!", ud_get_wp_property()->domain); ?></button></center>
        </div>
      </div>

      <div class="item">
        <div class="wpp_asst_screen wpp_asst_screen_2">
          <h2 class="wpp_asst_heading"><b><?php echo __('Which Property Types do you want to have on your site?', ud_get_wp_property()->domain); ?></b></h2>

          <div class="wpp_asst_inner_wrap">
            <ul class="">               
              <li class="wpp_asst_label"><?php echo __('House', ud_get_wp_property()->domain); ?> 
                <label for="property_types_house">
                  <input type="checkbox" class="wpp_box asst_prop_types" name="wpp_settings[property_types][house]"  value="House" id="property_types_house"  <?php if(isset($wp_properties['property_types']) && in_array("house",array_keys($wp_properties['property_types']))) echo "checked";?> />
                  <span></span> </label></li>	
                  
              <li class="wpp_asst_label"> 
                <?php echo __('Condo', ud_get_wp_property()->domain); ?>
                <label for="property_types_condo"> 
              <input type="checkbox" class="wpp_box asst_prop_types" name="wpp_settings[property_types][condo]"  value="Condo" id="property_types_condo"  <?php if(isset($wp_properties['property_types']) && in_array("condo",array_keys($wp_properties['property_types']))) echo "checked";?> />
                  <span></span> </label></li>
                  
              <li class="wpp_asst_label"> <?php echo __('Townhouse', ud_get_wp_property()->domain); ?>
                <label for="property_types_townhouse"> 
                  <input type="checkbox" class="wpp_settings_property_stats" name="wpp_settings[property_types][townhouse]" id="property_types_townhouse" value="Townhouse" <?php if(isset($wp_properties['property_types']) && in_array("townhouse",array_keys($wp_properties['property_types']))) echo "checked";?>/>
                  <span></span> </label></li>
                  
              <li class="wpp_asst_label"> <?php echo __('Multi-Family', ud_get_wp_property()->domain); ?>
                <label for="property_types_multifamily"> 
                  <input class="wpp_box  asst_prop_types" id="property_types_multifamily" type="checkbox" value="Multi-Family" data-label="" 
                         name="wpp_settings[property_types][multifamily]" <?php if(isset($wp_properties['property_types']) && in_array("multifamily",array_keys($wp_properties['property_types']))) echo "checked";?>> <span></span> </label></li>
              
              <li class="wpp_asst_label"> <?php echo __('Land', ud_get_wp_property()->domain); ?>
                <label for="property_types_land"> 
                  <input class="wpp_box asst_prop_types" type="checkbox" value="Land" name="wpp_settings[property_types][land]" id="property_types_land" <?php if(isset($wp_properties['property_types']) && in_array("land",array_keys($wp_properties['property_types']))) echo "checked";?>> <span></span> </label></li>
                  
              <li class="wpp_asst_label"> <?php echo __('Commercial', ud_get_wp_property()->domain); ?>
                <label for="property_types_commercial"> 
                  <input class="wpp_box asst_prop_types" type="checkbox" value="Commercial" name="wpp_settings[property_types][commercial]" id="property_types_commercial" <?php if(isset($wp_properties['property_types']) && in_array("commercial",array_keys($wp_properties['property_types']))) echo "checked";?> accept=""> <span></span> </label></li> 
            </ul>      
          </div> <!-- wpp_asst_inner_wrap --> 

          <div class="foot-note">
            <h3> <?php echo __('We Will add Appropriate attributes for types you have selected', ud_get_wp_property()->domain); ?></h5>
          </div>

        </div><!-- wpp_asst_screen wpp_asst_screen_2 --> 
      </div><!-- item --> 

      <div class="item">
        <div class="wpp_asst_screen wpp_asst_screen_3">
          <h2 class="wpp_asst_heading"><b><?php echo __('Add test properties to the site?', ud_get_wp_property()->domain); ?></b></h2>
          <div class="wpp_asst_inner_wrap">
            <ul>
              <li class="wpp_asst_label"> <?php echo __('Yes Please', ud_get_wp_property()->domain); ?><label for="yes-please"> 
                  <input class="wpp_box" type="radio" value="yes-please" name="wpp_settings[configuration][dummy-prop]" id="yes-please" <?php if(isset($wp_properties['configuration']['dummy-prop']) && $wp_properties['configuration']['dummy-prop']=="yes-please") echo "checked='checked'";?>> <span></span> </label>
              </li> 
              <li class="wpp_asst_label narrow"><?php echo __('No, thanks i have already</br> added properties', ud_get_wp_property()->domain); ?> <label for="no-thanks"> 
                  <input class="wpp_box" type="radio" value="no-thanks" name="wpp_settings[configuration][dummy-prop]" id="no-thanks" <?php if(isset($wp_properties['configuration']['dummy-prop']) && $wp_properties['configuration']['dummy-prop']=="no-thanks") echo "checked='checked'";?> > <span></span> </label>
              </li> 
            </ul>
          </div>

          <h2 class="wpp_asst_heading"><b><?php echo __('Add Widgets to the single property pages?', ud_get_wp_property()->domain); ?></b> </h2>
          <div class="wpp_asst_inner_wrap">
            <ul>
              <li class="wpp_asst_label"><?php echo __('Property Gallery', ud_get_wp_property()->domain); ?> <label for="gallerypropertieswidget"> 
                  <input class="wpp_box" type="checkbox" value="gallerypropertieswidget" name="wpp_settings[configuration][widgets][gallerypropertieswidget]" value="gallerypropertieswidget"  id="gallerypropertieswidget"  <?php if(isset($wp_properties['configuration']['widgets']) && in_array("gallerypropertieswidget",array_keys($wp_properties['configuration']['widgets']))) echo "checked";?>> <span></span> </label>
              </li> 
              
            </ul>
          </div>
        </div><!-- wpp_asst_screen wpp_asst_screen_3 --> 
      </div><!-- item --> 

      <div class="item item-wider">
        <div class="wpp_asst_screen wpp_asst_screen_4">
          <h2 class="wpp_asst_heading"><b><?php echo __('Choose default properties pages', ud_get_wp_property()->domain); ?></b></h2>  
          <div class="wpp_asst_inner_wrap">
            <div class="wpp_asst_select">
              <select id="soflow" name="wpp_settings[configuration][base_slug]">
                <?php
                $args = array('post_type' => 'page', 'post_status' => 'publish');
                $pages = get_pages($args);
                foreach ($pages as $page) {
                    ?>
                    <option value="<?php echo $page->post_name; ?>" <?php selected( $wp_properties[ 'configuration' ][ 'base_slug' ], $page->post_name ); ?> class="list_property"><?php echo $page->post_title; ?></option>
                <?php } ?>
                <option value="create-new" class="list_property"><?php echo __('Create a new page', ud_get_wp_property()->domain); ?></option>
              </select>
            </div>	
            <div class="wpp_asst_select_new">
              <input type="text" name="wpp-base-slug-new" class="wpp-base-slug-new" required="required"/>
            </div>

            <h2 class="wpp_asst_heading"><b><?php echo __('Add list of properties to your Properties page?', ud_get_wp_property()->domain); ?></b></h2>

            <div class="wpp_asst_inner_wrap">
              <ul class="three-sectionals">
                <li class="wpp_asst_label"> <?php echo __('Sure', ud_get_wp_property()->domain); ?><label for="true"> 
                    <input class="wpp_box" type="checkbox" value="true" name="wpp_settings[configuration][automatically_insert_overview]" 
                          <?php if(isset($wp_properties['configuration']['automatically_insert_overview'] ) && !empty($wp_properties['configuration']['automatically_insert_overview'])) echo "checked";?> id="true"> <span></span> </label>
                </li> 
              </ul>
            </div>
          </div>
        </div><!-- wpp_asst_screen wpp_asst_screen_4 --> 
      </div><!-- item --> 
      <div class="item">
        <div class="wpp_asst_screen wpp_asst_screen_5">
          <h2 class="wpp_asst_heading"><b><?php echo __('Google Maps API (optional)', ud_get_wp_property()->domain); ?></b></h2>  
          <div class="wpp_asst_inner_wrap wpp_asst_google_api">
            <?php echo WPP_F::input( "name=wpp_settings[configuration][google_maps_api]", ud_get_wp_property( 'configuration.google_maps_api' ) ); ?>
            <br/>
            <span class="description"><?php printf( __( 'Note, Google Maps has its own limit of usage. You can provide Google Maps API license ( key ) above to increase limit. See more details %shere%s.', ud_get_wp_property('domain') ), '<a href="https://developers.google.com/maps/documentation/javascript/usage#usage_limits" target="_blank">', '</a>' ); ?></span>
            
          </div>
        </div>
      </div>
      <div class="item">
        <div class="wpp_asst_screen wpp_asst_screen_6">
          <h2 class="wpp_asst_heading text-center"><b><?php echo __("Let's view what we have", ud_get_wp_property()->domain); ?></b></h2>
          <ul class="list-img">
            <li>
              <span><img src="<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/wpp-single-prop.jpg', 'url'); ?>" alt="image"></span>
              <center><a class="btn_single_page props" href="javascript:;"><?php echo __('SINGLE PROPERTY PAGE', ud_get_wp_property()->domain); ?></a></center>
            </li>
            <li>
              <img src="<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/overview-prop.jpg', 'url'); ?>" alt="image">
            <center><a class="btn_single_page oviews" href="javascript:;"><?php echo __('OVERVIEW OF PROPERTIES', ud_get_wp_property()->domain); ?></a></center>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="wpp-asst_hidden-attr">
      <!--  add field to recognize the source on save--> 
      <input  type="hidden" name="wpp_freshInstallation" value="<?php echo $freshInstallation; ?>">      
    </div>
  </form >
</div>