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

//enable assistant for new installations
$wp_properties['configuration']["show_assistant"] = true;
update_option("wpp_settings", $wp_properties);

$property_assistant = json_encode($wp_properties);
echo "<script> var wpp_property_assistant = $property_assistant; </script>";
?>
<div id="wpp-splash-screen">
  <form id="wpp-setup-assistant">
    <?php wp_nonce_field('wpp_setting_save'); ?>

    <div id="wpp-splash-screen-owl" class="owl-carousel">

      <div class="item">
        <div class="wpp_asst_screen wpp_asst_screen_1">
          <h2 class="wpp_asst_heading_main">CONGRATULATIONS,YOU HAVE SET UP</h2>
          <h1>WP-PROPERTY PLUGIN!</h1>
          <p class="tagline">Make few steps in order to set up it on your site!</p>
          <center><button type="button" class="btn_letsgo">LET'S GO!</button></center>
        </div>
      </div>

      <div class="item">
        <div class="wpp_asst_screen wpp_asst_screen_2">
          <h2 class="wpp_asst_heading"><b>Which Property Types do you want to have on your site?</b></h2>

          <div class="wpp_asst_inner_wrap">
            <ul class="">               
              <li class="wpp_asst_label"> House<label for="accessible"><input class="wpp_box asst_prop_types" type="checkbox" value="House" name="house" id="accessible"> <span></span> </label></li>				
              <li class="wpp_asst_label"> Condo<label for="test5"> <input class="wpp_box asst_prop_types" type="checkbox" value="Condo" name="condo" id="test5"> <span></span> </label></li>
              <li class="wpp_asst_label"> Townhouse<label for="test2"> <input class="wpp_box asst_prop_types" type="checkbox" value="Townhouse" name="townhouse" id="test2"> <span></span> </label></li>
              <li class="wpp_asst_label"> Multi-Family<label for="test3"> <input class="wpp_box  asst_prop_types" type="checkbox" value="Multi-Family" data-label="" name="multifamily" id="test3"> <span></span> </label></li>
              <li class="wpp_asst_label"> Land<label for="test4"> <input class="wpp_box asst_prop_types" type="checkbox" value="land" name="Land" id="test4"> <span></span> </label></li>
              <li class="wpp_asst_label"> Commercial<label for="test6"> <input class="wpp_box asst_prop_types" type="checkbox" value="Commercial" name="commercial" id="test6"> <span></span> </label></li> 
            </ul>      
          </div> <!-- wpp_asst_inner_wrap --> 

          <div class="foot-note">
            <h3> We Will add Appropriate attributes for types you have selected</h5>
          </div>

        </div><!-- wpp_asst_screen wpp_asst_screen_2 --> 
      </div><!-- item --> 

      <div class="item">
        <div class="wpp_asst_screen wpp_asst_screen_3">
          <h2 class="wpp_asst_heading"><b>Add 10 test properties to the site?</b></h2>
          <div class="wpp_asst_inner_wrap">
            <ul>
              <li class="wpp_asst_label"> Yes Please<label for="yes-please"> 
                  <input class="wpp_box" type="checkbox" value="yes-please" name="quality" id="yes-please"> <span></span> </label>
              </li> 
              <li class="wpp_asst_label narrow"> No, thanks i have already</br> added properties<label for="no-thanks"> 
                  <input class="wpp_box" type="checkbox" value="no-thanks" name="quality" id="no-thanks"> <span></span> </label>
              </li> 
            </ul>
          </div>

          <h2 class="wpp_asst_heading"><b>Add Widgets to the single property pages?</b> </h2>
          <div class="wpp_asst_inner_wrap">
            <ul>
              <li class="wpp_asst_label">Property Gallery <label for="property-gallery"> 
                  <input class="wpp_box" type="checkbox" value="property-gallery" name="quality" id="property-gallery"> <span></span> </label>
              </li> 
              <li class="wpp_asst_label"> Child Properties<label for="child-properties"> 
                  <input class="wpp_box" type="checkbox" value="child-properties" name="quality" id="child-properties"> <span></span> </label>
              </li> 
            </ul>
          </div>
        </div><!-- wpp_asst_screen wpp_asst_screen_3 --> 
      </div><!-- item --> 

      <div class="item item-wider">
        <div class="wpp_asst_screen wpp_asst_screen_4">
          <h2 class="wpp_asst_heading"><b>Choose default properties pages</b></h2>  
          <div class="wpp_asst_inner_wrap">
            <div class="wpp_asst_select">
              <select id="soflow" name="wpp_settings[configuration][base_slug]">
                <option value="property" class="list_property">Properties (Default)</option>
                <?php
                $args = array('post_type' => 'page', 'post_status' => 'publish');
                $pages = get_pages($args);
                foreach ($pages as $page) {
                  ?>
                  <option value="<?php echo $page->post_name; ?>" class="list_property"><?php echo $page->post_title; ?></option>
                <?php } ?>
              </select>
            </div>	

            <h2 class="wpp_asst_heading"><b>List Propery pages</b></h2>
            <div class="wpp_asst_inner_wrap">

              <ul class="three-sectionals">
                <li class="wpp_asst_label"> Property Search<label for="property-search"> 
                    <input class="wpp_box" type="checkbox" value="property-search" name="quality" id="property-search"> <span></span> </label>
                </li> 
                <li class="wpp_asst_label">Featured Property<label for="featured-properties"> 
                    <input class="wpp_box" type="checkbox" value="featured-properties" name="quality" id="featured-properties"> <span></span> </label>
                </li> 
                <li class="wpp_asst_label"> Latest Property<label for="latest-properties"> 
                    <input class="wpp_box" type="checkbox" value="latest-properties" name="quality" id="latest-properties"> <span></span> </label>
                </li> 
              </ul>
            </div>

            <h2 class="wpp_asst_heading"><b>Add overview of properties to your Properties page?</b></h2>

            <div class="wpp_asst_inner_wrap">
              <ul class="three-sectionals">
                <li class="wpp_asst_label"> Sure<label for="true"> 
                    <input class="wpp_box" type="checkbox" value="true" name="wpp_settings[configuration][automatically_insert_overview]" 
                           id="true"> <span></span> </label>
                </li> 
              </ul>
            </div>
          </div>
        </div><!-- wpp_asst_screen wpp_asst_screen_4 --> 
      </div><!-- item --> 

      <div class="item">
        <div class="wpp_asst_screen wpp_asst_screen_5">
          <h2 class="wpp_asst_heading text-center"><b>Let's view what we have</b></h2>
          <ul class="list-img">
            <li>
              <span><img src="<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/wpp-single-prop.jpg', 'url'); ?>" alt="image"></span>
            <center><button class="btn_single_page">SINGLE PROPERTY PAGES</button></center>
            </li>
            <li>
              <img src="<?php echo ud_get_wp_property()->path('/static/splashes/assets/images/overview-prop.jpg', 'url'); ?>" alt="image">
            <center><button class="btn_single_page">OVERVIEW OF PROPERTIES</button></center>
            </li>
          </ul>


        </div>
      </div>
    </div>
    <div class="wpp-asst_hidden-attr">
      <!--  add field to recognize the source on save--> 
      <input  type="hidden" name="wpp_settings[configuration][show_assistant]" value="true">

      <!-- Additional attributes for location  --> 
      <input  type="hidden" name="wpp_settings[admin_attr_fields][location]" value="input">
      <input  type="hidden" name="wpp_settings[searchable_attr_fields][location]" value="input">
      <input  type="hidden"  name="wpp_settings[configuration][address_attribute]" value="input">

      <!--  Additional attributes for price --> 
      <input  type="hidden" name="wpp_settings[admin_attr_fields][price]" value="currency">
      <input  type="hidden" name="wpp_settings[searchable_attr_fields][price]" value="range_dropdown">

      <!--  Additional attributes attached here (dynamically)
          includes property types, attributes for each property type --> 

    </div>
  </form >
</div>