<?php
/**
 * Shortcode: [property_responsive_slideshow]
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Responsive_Slideshow_Shortcode' ) ) {

    class Property_Responsive_Slideshow_Shortcode extends RS_Shortcode {

      /**
       * Constructor
       */
      public function __construct() {

        $attributes = (array) \WPP_F::get_total_attribute_array();
        $options = array(
          'id' => 'property_responsive_slideshow',
          'params' => array(
              // Property ID
              'property_id' => array(
                'name' => sprintf( __( '%s ID', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ),
                'description' => sprintf( __( 'If not empty, Slideshow will show for particular %s, which ID is set. If not provided will show slideshow for current %s', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label(), \WPP_F::property_label() ),
                'type' => 'text',
                'default' => ''
              ),
              // Slideshow layout
              'slideshow_layout' => array(
                'name' => __( 'Slideshow layout', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Set Slideshow layout. Available  Responsive(auto), Strict(strict), Full Width(fullwidth)', ud_get_wp_property( 'domain' ) ),
                'type' => 'select',
                'options' => array(
                                  'auto'=>'Responsive',
                                  'strict'=>'Strict',
                                  'fullwidth'=>'Full Width'
                                ),
                'default' => 'auto',
              ),
              //Slideshow Types
              'slideshow_type' => array(
                'name' => __( 'Slideshow Types', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Type of slideshow. Default is standard. standard and thumbnailCarousel is available.', ud_get_wp_property( 'domain' ) ),
                'type' => 'select',
                'options' => array(
                  'standard'  => 'Standard Slideshow',
                  'thumbnailCarousel'  => 'Thumbnail Carousel Slideshow',
                ),
                'default' => 'thumbnailCarousel',
              ),
              //Slider Type
              'slider_type' => array(
                'name' => __( 'Slider Type', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Type of slider. Default is standard. Also Carousel and Grid Slider is available.', ud_get_wp_property( 'domain' ) ),
                'type' => 'select',
                'options' => array(
                  'standard'  => 'Standard Slider',
                  'carousel'  => 'Carousel Slider',
                  '12grid' => '1:2 Grid Slider',
                  '12mosaic' => '1:2 Mosaic Slider'
                ),
                'default' => 'standard',
              ),
              // Slider Width
              'slider_width' => array(
                'name' => __( 'Slider Width', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Set width of the slideshow.', ud_get_wp_property( 'domain' ) ),
                'type' => 'text',
                'default' => '',
              ),
              // Slider Auto Height
              'slider_auto_height' => array(
                'name' => __( 'Slider Auto Height', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Allows the height of the carousel to adjust based on the image size. If the image size is short or tall, the carousel will rollup/rolldown to new height.', ud_get_wp_property( 'domain' ) ),
                'type' => 'select',
                'options' => array('true'=>'True', 'false'=>'False'),
                'default' => 'false',
              ),
              // Slider Height
              'slider_height' => array(
                'name' => __( 'Slider Height', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Sets the height of the slider container.', ud_get_wp_property( 'domain' ) ),
                'type' => 'text',
                'default' => '',
              ),
              // Slider Minimum Height
              'slider_min_height' => array(
                'name' => __( 'Slider Minimum Height', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Sets the minimum height of the slider.', ud_get_wp_property( 'domain' ) ),
                'type' => 'text',
                'default' => '',
              ),
              // Slider Maximum Height
              'slider_max_height' => array(
                'name' => __( 'Slider Maximum Height', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Sets the maximum height of the slider.', ud_get_wp_property( 'domain' ) ),
                'type' => 'text',
                'default' => '',
              ),
              //Lightbox Title line 1
              'lb_title_1' => array(
                'name' => __( 'Lightbox Title line 1', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Lightbox Title line 1. Select an attribute.', ud_get_wp_property( 'domain' ) ),
                'type' => 'combobox',
                'options' => $attributes,
                'default' => '',
              ),
              //Lightbox Title line 2
              'lb_title_2' => array(
                'name' => __( 'Lightbox Title line 2', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Lightbox Title line 2. Select an attribute.', ud_get_wp_property( 'domain' ) ),
                'type' => 'combobox',
                'options' => $attributes,
                'default' => '',
              ),
            // See params examples in: wp-property/lib/shortcodes

          ),
          'description' => __( 'Renders Responsive Slideshow', ud_get_wpp_resp_slideshow()->domain ),
          'group' => 'WP-Property',
        );

        parent::__construct( $options );

      }

      /**
       *  Renders Shortcode
       */
      public function call( $atts = "" ) {

        $data = shortcode_atts( array(
          'property_id' => '',
          'slideshow_type' => 'thumbnailCarousel',
          'slideshow_layout' => 'auto',
          'slider_type' => 'standard',
          'grid_image_size' => 'medium',
          'lb_title_1' => '',
          'lb_title_2' => '',
          'slider_width' => '',
          'slider_auto_height' => 'false',
          'slider_height' => '',
          'slider_min_height' => '',
          'slider_max_height' => '',
        ), $atts );
        self::maybe_print_styles();
        return $this->get_template( 'property-responsive-shortcode', $data, false );

      }

      /**
       * Render property_overview default styles at once!
       */
      static public function maybe_print_styles() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ?  '' : '.min';
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style("swiper-style", ud_get_wpp_resp_slideshow()->path( "static/styles/swiper/swiper$suffix.css", "url" ));
        wp_enqueue_style("lightbox-style", ud_get_wpp_resp_slideshow()->path( "static/styles/lightbox$suffix.css", "url" ));
        wp_enqueue_style("property-responsive-slideshow-style", ud_get_wpp_resp_slideshow()->path( "static/styles/res-slideshow$suffix.css", "url" ));

        wp_enqueue_script("lightbox-script", ud_get_wpp_resp_slideshow()->path( "static/scripts/lightbox.js", "url" ));
        wp_enqueue_script("swiper-script", ud_get_wpp_resp_slideshow()->path( "static/scripts/swiper.jquery.js", "url" ));
        wp_enqueue_script("property-responsive-slideshow-script", ud_get_wpp_resp_slideshow()->path( "static/scripts/res-slideshow.js", "url" ));
      }

    }
    add_action('init', function(){
      new Property_Responsive_Slideshow_Shortcode();
    });
  }

}

