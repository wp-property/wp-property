<?php

namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Layouts_Settings' ) ) {

    /**
     * Class Layouts_Builder
     * @package UsabilityDynamics\WPP
     */
    class Layouts_Settings extends Scaffold {

      /**
       * @var null
       */
      private $preloaded_layouts = null;

      /**
       * @var array
       */
      private $possible_tags = array(
        'search-results', 'single-property', 'single-property-term'
      );

      /**
       * @var
       */
      private $api_client;

      /**
       * Layouts_Settings constructor.
       */
      public function __construct() {
        parent::__construct();

        /**
         *
         */
        $this->api_client = new Layouts_API_Client(array(
            'url' => 'https://api.usabilitydynamics.com/v1/layouts/'
        ));

        /**
         * Add settings tab
         */
        add_filter( 'wpp_settings_nav', array( $this, 'settings_nav' ) );

        /**
         * Add settings page
         */
        add_action( 'wpp_settings_content_layouts', array( $this, 'settings_page' ) );
      }

      /**
       * @return array
       */
      private function preload_layouts() {

        $res = $this->api_client->get_layouts();

        try {
          $res = json_decode( $res );
        } catch ( \Exception $e ) {
          return array();
        }

        if ( $res->ok && !empty( $res->data ) && is_array( $res->data ) ) {

          $_available_layouts = array();

          foreach( $this->possible_tags as $p_tag ) {
            foreach( $res->data as $layout ) {

              if ( empty( $layout->tags ) || !is_array( $layout->tags ) ) continue;
              $_found = false;
              foreach( $layout->tags as $_tag ) {

                if ( $_tag->tag == $p_tag ) {
                  $_found = true;
                }
              }
              if ( !$_found ) continue;

              $_available_layouts[$p_tag][$layout->_id] = $layout;
            }
          }

          update_option( 'wpp_available_layouts', $_available_layouts );
          return $_available_layouts;
        } else {
          if ( $_available_layouts = get_option( 'wpp_available_layouts', false ) ) {
            return $_available_layouts;
          } else {
            return array();
          }
        }

        return array();

      }

      /**
       * @param $tabs
       * @return mixed
       */
      public function settings_nav( $tabs ) {

        $this->preloaded_layouts = $this->preload_layouts();

        $tabs['layouts'] = array(
            'slug' => 'layouts',
            'title' => __('Layouts', ud_get_wp_property()->domain ),
        );
        return $tabs;
      }

      /**
       *
       */
      public function settings_page() {
        global $wp_properties;

        $layouts_settings = wp_parse_args( !empty( $wp_properties['configuration']['layouts']['templates'] ) ? $wp_properties['configuration']['layouts']['templates'] : array() , array(
          'property_term_single' => 'false',
          'property_single'      => 'false',
          'search_results'       => 'false'
        ));

        ob_start();

        ?>

        <table class="form-table">
          <tbody>

            <tr id="property-term-single">
              <th><?php _e( 'Property Term Single', ud_get_wp_property()->domain ); ?></th>
              <td>
                <?php
                  if ( !empty( $this->preloaded_layouts['single-property-term'] ) && is_array( $this->preloaded_layouts['single-property-term'] ) ) {
                ?>
                  <ul class="layouts-list">
                    <li>
                      <label class="<?php echo $layouts_settings['property_term_single'] == 'false' ? 'checked' : ''; ?>">
                        <h5><?php _e( 'No Layout', ud_get_wp_property()->domain ); ?></h5>
                        <img width="150" height="150" src="//placehold.it/150?text=No+Layout" alt="No Layout" />
                        <input <?php checked( 'false', $layouts_settings['property_term_single'] ); ?> style="display:none;" type="radio" name="wpp_settings[configuration][layouts][templates][property_term_single]" value="false">
                      </label>
                    </li>
                <?php
                  foreach( $this->preloaded_layouts['single-property-term'] as $layout ) {
                ?>
                    <li>
                      <label class="<?php echo $layout->_id == $layouts_settings['property_term_single'] ? 'checked' : ''; ?>">
                        <h5><?php echo $layout->title; ?></h5>
                        <img width="150" height="150" src="<?php echo !empty($layout->screenshot)?$layout->screenshot:'//placehold.it/150?text=No+preview'; ?>" alt="<?php echo $layout->title ?>" />
                        <input <?php checked( $layout->_id, $layouts_settings['property_term_single'] ); ?> style="display:none;" type="radio" name="wpp_settings[configuration][layouts][templates][property_term_single]" value="<?php echo $layout->_id; ?>">
                      </label>
                    </li>
                <?
                  }
                ?>
                  </ul>
                <?php
                  } else {
                    _e( 'There are no available layouts. Default view is used.', ud_get_wp_property()->domain );
                  }
                ?>
              </td>
            </tr>

            <tr id="property-single">
              <th><?php _e( 'Property Single', ud_get_wp_property()->domain ); ?></th>
              <td>
                <?php
                if ( !empty( $this->preloaded_layouts['single-property'] ) && is_array( $this->preloaded_layouts['single-property'] ) ) {
                  ?>
                  <ul class="layouts-list">
                    <li>
                      <label class="<?php echo $layouts_settings['property_single'] == 'false' ? 'checked' : ''; ?>">
                        <h5><?php _e( 'No Layout', ud_get_wp_property()->domain ); ?></h5>
                        <img width="150" height="150" src="//placehold.it/150?text=No+Layout" alt="No Layout" />
                        <input <?php checked( 'false', $layouts_settings['property_single'] ); ?> style="display:none;" type="radio" name="wpp_settings[configuration][layouts][templates][property_single]" value="false">
                      </label>
                    </li>
                    <?php
                    foreach( $this->preloaded_layouts['single-property'] as $layout ) {
                      ?>
                      <li>
                        <label class="<?php echo $layout->_id == $layouts_settings['property_single'] ? 'checked' : ''; ?>">
                          <h5><?php echo $layout->title; ?></h5>
                          <img width="150" height="150" src="<?php echo !empty($layout->screenshot)?$layout->screenshot:'//placehold.it/150?text=No+preview'; ?>" alt="<?php echo $layout->title ?>" />
                          <input <?php checked( $layout->_id, $layouts_settings['property_single'] ); ?> style="display:none;" type="radio" name="wpp_settings[configuration][layouts][templates][property_single]" value="<?php echo $layout->_id; ?>">
                        </label>
                      </li>
                      <?
                    }
                    ?>
                  </ul>
                  <?php
                } else {
                  _e( 'There are no available layouts. Default view is used.', ud_get_wp_property()->domain );
                }
                ?>
              </td>
            </tr>

            <tr id="search-results">
              <th><?php _e( 'Search Results', ud_get_wp_property()->domain ); ?></th>
              <td>
                <?php
                if ( !empty( $this->preloaded_layouts['search-results'] ) && is_array( $this->preloaded_layouts['search-results'] ) ) {
                  ?>
                  <ul class="layouts-list">
                    <li>
                      <label class="<?php echo $layouts_settings['search_results'] == 'false' ? 'checked' : ''; ?>">
                        <h5><?php _e( 'No Layout', ud_get_wp_property()->domain ); ?></h5>
                        <img width="150" height="150" src="//placehold.it/150?text=No+Layout" alt="No Layout" />
                        <input <?php checked( 'false', $layouts_settings['search_results'] ); ?> style="display:none;" type="radio" name="wpp_settings[configuration][layouts][templates][search_results]" value="false">
                      </label>
                    </li>
                    <?php
                    foreach( $this->preloaded_layouts['search-results'] as $layout ) {
                      ?>
                      <li>
                        <label class="<?php echo $layout->_id == $layouts_settings['search_results'] ? 'checked' : ''; ?>">
                          <h5><?php echo $layout->title; ?></h5>
                          <img width="150" height="150" src="<?php echo !empty($layout->screenshot)?$layout->screenshot:'//placehold.it/150?text=No+preview'; ?>" alt="<?php echo $layout->title ?>" />
                          <input <?php checked( $layout->_id, $layouts_settings['search_results'] ); ?> style="display:none;" type="radio" name="wpp_settings[configuration][layouts][templates][search_results]" value="<?php echo $layout->_id; ?>">
                        </label>
                      </li>
                      <?
                    }
                    ?>
                  </ul>
                  <?php
                } else {
                  _e( 'There are no available layouts. Default view is used.', ud_get_wp_property()->domain );
                }
                ?>
              </td>
            </tr>

          </tbody>
        </table>

        <style type="text/css">
          .layouts-list {}
          .layouts-list li {
            float: left;
            margin-left: 10px;
            margin-bottom: 10px;
          }
          .layouts-list li label img {
            display: block;
            border: 5px solid white;
            transition: border .5s;
          }
          .layouts-list li label.checked img {
            border: 5px solid #0083ff;
          }
        </style>

        <script type="application/javascript">
          jQuery(document).ready(function(){
            jQuery('#property-term-single .layouts-list li label').on( 'click', function(e) {
              jQuery('#property-term-single .layouts-list li label').removeClass( 'checked' );
              jQuery(this).addClass( 'checked' );
            });

            jQuery('#property-single .layouts-list li label').on( 'click', function(e) {
              jQuery('#property-single .layouts-list li label').removeClass( 'checked' );
              jQuery(this).addClass( 'checked' );
            });

            jQuery('#search-results .layouts-list li label').on( 'click', function(e) {
              jQuery('#search-results .layouts-list li label').removeClass( 'checked' );
              jQuery(this).addClass( 'checked' );
            });
          });
        </script>

        <?php

        echo apply_filters( 'wpp::layouts::settings_html', ob_get_clean() );
      }
    }
  }
}