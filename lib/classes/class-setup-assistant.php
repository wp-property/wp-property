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
                    <input type="hidden" name="wpp_freshInstallation" value="<?php echo isset( $freshInstallation ) ? $freshInstallation : 'false'; ?>">
                  </div>
                </div>
              </div>

          </div>

        </form>
      </div>
    </div>
        <?php
      }


      /**
       * Add dummy properties for Setup Assistant.
       *
       * @author raj
       */
      static public function generate_asst_dummy_properties($data)
      {

        // select default property for the dummy properties
        if (count($data['wpp_settings']['property_types']) > 0) {
          $default_prop = array_keys($data['wpp_settings']['property_types']);
          $default_prop = $default_prop[0];
        } else {
          $default_prop = "house";
        }

        global $user_ID, $wp_properties, $wpdb;

        /* Determine if the dummy properties already exist */
        $posts = $wpdb->get_col("
      SELECT `post_title`
      FROM {$wpdb->posts}
      WHERE 
      `post_title` IN ('122 Bishopsgate','East Pointe Marketplace','MIDLEVELS WEST','720 N Larrabee St Apt','460 W Huron St','7846 Charlesmont Road','3212 Ramona Avenue','4602 Chatford Avenue','619 Beechfield Avenue','5109 Eugene Avenue','99 Richfield','9812 NE Avenue')
       AND `post_status` = 'publish'
    ");

        /* Check array to avoid issues in future */
        if (!is_array($posts)) {
          $posts = array();
        }

        /* If Property doesn't exist we create it : ONE */
        if (!in_array('122 Bishopsgate', $posts)) {

          self::generate_asst_dummy_property(array(
            'post_title' => '122 Bishopsgate',
            'post_content' => 'Take notice of this amazing home! It has an original detached 2 garage/workshop built with the home and on a concrete slab along with regular 2 car attached garage. Very nicely landscaped front and back yard. Hardwood floors in Foyer, den, dining and great room. Great room is open to large Kitchen. Carpet in all upstairs bedrooms. Home is located in the Woodlands in the middle of very nice community. You and your family will feel right at home. A must see.',
            'tagline' => 'Need Room for your TOYS! Take notice of this unique Home!',
            'location' => '122 Bishopsgate, Jacksonville, NC 28540, USA',
            'price' => '195000',
            'featured' => 'true',
            'bedrooms' => '4',
            'property_type' => $default_prop,
            'bathrooms' => '4',
            'fees' => '100',
            'year_built' => '2001',
            'living_space' => "1000",
            'total_rooms' => '6',
            'property_feature' => 'cable_prewire',
            'community_feature' => 'dishwasher',
            'phone_number' => '8002700781',
            'img_index' => '1',
          ));

        }

        /* If Property doesn't exist we create it : TWO */
        if (!in_array('East Pointe Marketplace', $posts)) {

          self::generate_asst_dummy_property(array(
            'post_title' => 'East Pointe Marketplace',
            'post_content' => "Convenient suburban shopping experience located in the epicenter of Milwaukee's lower east side.
Adjacent to the Milwaukee School of Engineering
On bus line
Ample off-street parking ",
            'tagline' => 'Need Room for your TOYS! Take notice of this unique Home!',
            'location' => '605 E Lyon St Milwaukee, WI 53202',
            'price' => '215000',
            'bedrooms' => '5',
            'bathrooms' => '4',
            'fees' => '200',
            'property_feature' => 'cable_prewire',
            'community_feature' => 'dishwasher',
            'year_built' => '2002',
            'living_space' => "2000",
            'total_rooms' => '8',
            'property_type' => $default_prop,
            'phone_number' => '8002300781',
            'img_index' => '1',
          ));

        }
        /* If Property doesn't exist we create it : THREE */
        if (!in_array('MIDLEVELS WEST', $posts)) {

          self::generate_asst_dummy_property(array(
            'post_title' => 'MIDLEVELS WEST',
            'post_content' => 'Ideal family flat with 4 bedrooms at upper Conduit Road',
            'tagline' => 'Ideal family flat with 4 bedrooms at upper Conduit Road',
            'location' => '122 Bishopsgate, Jacksonville, NC 28540, USA',
            'price' => '255000',
            'bedrooms' => '8',
            'featured' => 'true',
            'fees' => '300',
            'property_feature' => 'cathedral_ceiling',
            'community_feature' => 'double_oven',
            'year_built' => '2003',
            'living_space' => "3000",
            'total_rooms' => '11',
            'property_type' => $default_prop,
            'bathrooms' => '8',
            'phone_number' => '9992700781',
            'img_index' => '1',
          ));

        }
        /* If Property doesn't exist we create it : FOUR */
        if (!in_array('720 N Larrabee St Apt', $posts)) {

          self::generate_asst_dummy_property(array(
            'post_title' => '720 N Larrabee St Apt',
            'post_content' => 'Beautiful west views of river in ideal River North location close to downtown, Magnificent Mile, shopping, dining, entertainment. Split 2 bedroom, 2 bath floor plan with hardwood floors, granite counters and breakfast bar, stainless steel appliances, gas fireplace, 12-foot ceilings in this trendy loft-style unit with large balcony to enjoy sunset views over the river. Plenty of room for dining table and tons of closet space built out with Elfa shelving. 2nd bedroom closed off to the ceiling for privacy. Washer/dryer in the unit. Full-amenity building with onsite manager/engineer, 24-hour door staff, fitness room, bike storage; indoor heated parking for $35,000 extra, additional storage cage included.',
            'tagline' => 'Great new home',
            'location' => '720 N Larrabee St Apt 1012,Chicago, IL 60654',
            'price' => '985000',
            'bedrooms' => '8',
            'fees' => '400',
            'year_built' => '2004',
            'living_space' => "4000",
            'property_feature' => 'cathedral_ceiling',
            'community_feature' => 'double_oven',
            'total_rooms' => '10',
            'bathrooms' => '8',
            'property_type' => $default_prop,
            'phone_number' => '9856700781',
            'img_index' => '1',
          ));

        }
        /* If Property doesn't exist we create it : FIVE */
        if (!in_array('460 W Huron St', $posts)) {

          self::generate_asst_dummy_property(array(
            'post_title' => '460 W Huron St',
            'post_content' => 'Unique amenities nestled among exquisite building features will make your home feel like an urban oasis while ours dedicated staff will not only fulfill your needs, but anticipate them.',
            'tagline' => 'Only for a limited period DEPOSIT $0!!!!',
            'location' => '460 W Huron St,Chicago, IL 60654',
            'price' => '876000',
            'bedrooms' => '5',
            'featured' => 'true',
            'property_feature' => 'disability_equipped',
            'community_feature' => 'central_vacuum',
            'fees' => '500',
            'year_built' => '2005',
            'living_space' => "5000",
            'total_rooms' => '8',
            'property_type' => $default_prop,
            'bathrooms' => '5',
            'phone_number' => '8002708876',
            'img_index' => '1',
          ));
        }
      }

      static public function generate_asst_dummy_property($data)
      {
        global $wp_properties;

        $defaults = array(
          'post_title' => 'Dummy Listing',
          'post_content' => 'Donec volutpat elit malesuada eros porttitor blandit. Donec sit amet ligula quis tortor molestie sagittis tincidunt at tortor. Phasellus augue leo, molestie in ultricies gravida; blandit et diam. Curabitur quis nisl eros! Proin quis nisi quam, sit amet lacinia nisi. Vivamus sollicitudin magna eu ipsum blandit tempor. Duis rhoncus orci at massa consequat et egestas lectus ornare? Duis a neque magna, quis placerat lacus. Phasellus non nunc sapien, id cursus mi! Mauris sit amet nisi vel felis molestie pretium.',
          'tagline' => 'Donec volutpat elit malesuada eros porttitor blandit',
          'location' => '122 Bishopsgate, Jacksonville, NC 28540, USA',
          'property_type' => 'house',
          'img_index' => '1', // Available: '1', '2'
          'price' => '',
          'bedrooms' => '',
          'bathrooms' => '',
          'phone_number' => '',
        );

        $data = wp_parse_args($data, $defaults);

        //** STEP 1. Create dummy property */

        $insert_id = wp_insert_post(array(
          'post_title' => $data['post_title'],
          'post_status' => 'publish',
          'post_content' => $data['post_content'],
          'post_type' => 'property',
        ));

        $property_type = $data['property_type'];

        update_post_meta($insert_id, 'property_type', $property_type);

        if (!empty($wp_properties['configuration']['address_attribute']) && key_exists($wp_properties['configuration']['address_attribute'], $wp_properties['property_stats'])) {
          update_post_meta($insert_id, 'location', $data['location']);

          if (method_exists('WPP_F', 'revalidate_address')) {
            WPP_F::revalidate_address($insert_id);
          }
        }

        if (!empty($data['tagline'])) {
          update_post_meta($insert_id, 'tagline', $data['tagline']);
        }

        if (!empty($data['featured'])) {
          update_post_meta($insert_id, 'featured', $data['featured']);
        }

        if (!empty($data['price'])) {
          update_post_meta($insert_id, 'price', $data['price']);
        }

        if (!empty($data['bedrooms'])) {
          update_post_meta($insert_id, 'bedrooms', $data['bedrooms']);
        }

        if (!empty($data['bathrooms'])) {
          update_post_meta($insert_id, 'bathrooms', $data['bathrooms']);
        }

        if (!empty($data['phone_number'])) {
          update_post_meta($insert_id, 'phone_number', $data['phone_number']);
        }

        if (!empty($data['total_rooms'])) {
          update_post_meta($insert_id, 'total_rooms', $data['total_rooms']);
        }

        if (!empty($data['fees'])) {
          update_post_meta($insert_id, 'fees', $data['fees']);
        }

        if (!empty($data['year_built'])) {
          update_post_meta($insert_id, 'year_built', $data['year_built']);
        }

        if (!empty($data['living_space'])) {
          update_post_meta($insert_id, 'living_space', $data['living_space']);
        }

        if (!empty($data['property_feature']))
          wp_set_post_terms($insert_id, $data['property_feature'], 'property_feature');
        if (!empty($data['community_feature']))
          wp_set_post_terms($insert_id, $data['community_feature'], 'community_feature');

        update_post_meta($insert_id, 'dummy_property', true);

        //** STEP 2. Create and Move temporary image files */

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $upload_dir = wp_upload_dir();

        $dummy_images = array(
          WPP_Path . "static/splashes/assets/images/dummy_data/property_{$data['img_index']}_img_0.jpg",
          WPP_Path . "static/splashes/assets/images/dummy_data/property_{$data['img_index']}_img_1.jpg",
          WPP_Path . "static/splashes/assets/images/dummy_data/property_{$data['img_index']}_img_2.jpg"
        );

        foreach ($dummy_images as $dummy_path) {
          if (@copy($dummy_path, $upload_dir['path'] . "/" . basename($dummy_path))) {
            $filename = $upload_dir['path'] . "/" . basename($dummy_path);
            $wp_filetype = wp_check_filetype(basename($filename), null);

            $attach_id = wp_insert_attachment(array(
              'post_mime_type' => $wp_filetype['type'],
              'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
              'post_status' => 'inherit'
            ), $filename, $insert_id);

            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
            wp_update_attachment_metadata($attach_id, $attach_data);
          }
        }

        //** Last attached file is set as thumbnail */
        if (isset($attach_id)) {
          update_post_meta($insert_id, '_thumbnail_id', $attach_id);
        }

      }

    }
  }
}

