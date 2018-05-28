<?php
/**
 * Shortcode: [property_attribute]
 *
 * ### Usage:
 *
 *    [property_attribute attribute=post_content]
 *
 * @since 2.1
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Attribute_Shortcode' ) ) {

    class Property_Attribute_Shortcode extends Shortcode {

      /**
       * Init
       */
      public function __construct() {

        $options = array(
            'id' => 'property_attribute',
            'params' => array(
              'attribute' => array(
                'type' => 'text',
                'name' => __( 'Attribute', ud_get_wp_property()->domain ),
                'description' => __( 'Renders single attribute data', ud_get_wp_property()->domain ),
                'default' => ''
              ),
              'before' => array(
                'type' => 'text',
                'name' => __( 'Before', ud_get_wp_property()->domain ),
                'description' => __( 'Before attribute', ud_get_wp_property()->domain ),
                'default' => ''
              ),
              'after' => array(
                'type' => 'text',
                'name' => __( 'After', ud_get_wp_property()->domain ),
                'description' => __( 'After attribute', ud_get_wp_property()->domain ),
                'default' => ''
              ),
              'if_empty' => array(
                'type' => 'text',
                'name' => __( 'If Empty', ud_get_wp_property()->domain ),
                'description' => __( 'What to show if attribute is empty', ud_get_wp_property()->domain ),
                'default' => ''
              ),
              'do_not_format' => array(
                'type' => 'text',
                'name' => __( 'Do not format', ud_get_wp_property()->domain ),
                'description' => __( 'Uh?', ud_get_wp_property()->domain ),
                'default' => ''
              ),
              'make_terms_links' => array(
                'type' => 'select',
                'name' => __( 'Make links of terms', ud_get_wp_property()->domain ),
                'description' => __( 'Make links of terms', ud_get_wp_property()->domain ),
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              ),
              'separator' => array(
                'type' => 'text',
                'name' => __( 'Separator', ud_get_wp_property()->domain ),
                'description' => __( 'Separator', ud_get_wp_property()->domain ),
                'default' => ' '
              ),
              'strip_tags' => array(
                'type' => 'text',
                'name' => __( 'Strip Tags', ud_get_wp_property()->domain ),
                'description' => __( 'Strip tags', ud_get_wp_property()->domain ),
                'default' => ''
              )
            ),
            'description' => sprintf( __( 'Renders %s Attribute', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
            'group' => 'WP-Property'
        );

        parent::__construct( $options );
      }

      /**
       * @param string $atts
       * @return string|void
       */
      public function call( $atts = "" ) {

        global $post, $property;

        $this_property = $property;

        if( empty( $this_property ) && $post->post_type == 'property' ) {
          $this_property = $post;
        }

        $this_property = (array)$this_property;

        if( !$atts ) {
          $atts = array();
        }

        $defaults = array(
            'property_id' => $this_property[ 'ID' ],
            'attribute' => '',
            'before' => '',
            'after' => '',
            'if_empty' => '',
            'do_not_format' => '',
            'make_terms_links' => 'false',
            'separator' => ' ',
            'strip_tags' => ''
        );

        $args = array_merge( $defaults, $atts );

        if( empty( $args[ 'attribute' ] ) ) {
          return false;
        }

        $attribute = $args[ 'attribute' ];

        if( $args[ 'property_id' ] != $this_property[ 'ID' ] ) {

          $this_property = \WPP_F::get_property( $args[ 'property_id' ] );

          if( $args[ 'do_not_format' ] != "true" ) {
            $this_property = prepare_property_for_display( $this_property );
          }

        }

        if( taxonomy_exists( $attribute ) && is_object_in_taxonomy( 'property', $attribute ) ) {
          foreach( wp_get_object_terms( $this_property[ 'ID' ], $attribute ) as $term_data ) {

            if( $args[ 'make_terms_links' ] == 'true' ) {
              $terms[ ] = '<a class="wpp_term_link" href="' . get_term_link( $term_data, $attribute ) . '"><span class="wpp_term">' . $term_data->name . '</span></a>';
            } else {
              $terms[ ] = '<span class="wpp_term">' . $term_data->name . '</span>';
            }
          }

          if( isset( $terms ) && is_array( $terms ) && !empty( $terms ) ) {
            $value = implode( $args[ 'separator' ], $terms );
          }

        }

        //** Try to get value using get get_attribute() function */
        if( !isset( $value ) || !$value && function_exists( 'get_attribute' ) ) {
          $value = get_attribute( $attribute, array(
              'return' => 'true',
              'property_object' => $this_property
          ) );
        }

        // parse shortcodes for the post_content field
        if( $attribute === 'post_content' && $value ) {
          $value = do_shortcode( $value );;
        }

        if( !empty( $args[ 'before' ] ) ) {
          $return[ 'before' ] = html_entity_decode( $args[ 'before' ] );
        }

        $return[ 'value' ] = apply_filters( 'wpp_property_attribute_shortcode', $value, $this_property );
        // Getting translation
        $return[ 'value' ] = apply_filters( 'wpp::attribute::value', $value, $attribute );

        if( $args[ 'strip_tags' ] == "true" && !empty( $return[ 'value' ] ) ) {
          $return[ 'value' ] = strip_tags( $return[ 'value' ] );
        }

        if( !empty( $args[ 'after' ] ) ) {
          $return[ 'after' ] = html_entity_decode( $args[ 'after' ] );
        }

        //** When no value is found */
        if( empty( $return[ 'value' ] ) ) {

          if( !empty( $args[ 'if_empty' ] ) ) {
            return $args[ 'if_empty' ];
          } else {
            return false;
          }
        }

        if( is_array( $return ) ) {
          return implode( '', $return );
        }

        return false;

      }

    }

    /**
     * Register
     */
    new Property_Attribute_Shortcode();

  }

}