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
      public $preloaded_layouts = null;

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
         *
         */
        add_filter( 'wpp::layouts::template_files', array( $this, 'filter_template_files' ) );
      }

      /**
       * @param $files
       * @return mixed
       */
      public function filter_template_files( $files ) {

        $unwanted = array(
          '404.php',
          'author.php',
          'sidebar.php',
          'comments.php',
          'footer.php',
          'functions.php',
          'header.php',
          'search.php',
          'searchform.php'
        );

        foreach( $unwanted as $file ) {
          unset( $files[$file] );
        }

        foreach( $files as $file => $path ) {
          if( preg_match( "/sidebar/", $file ) ) {
            unset( $files[$file] );
          }
        }

        return $files;
      }

      /**
       * @return array
       */
      public function preload_layouts() {

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
       * load layouts for setup assistant page
       */
      function setup_assistant_layouts(){

        global $wp_properties;
        $this->preloaded_layouts = $this->preload_layouts();

        $layouts_settings = wp_parse_args( !empty( $wp_properties['configuration']['layouts']['templates'] ) ? $wp_properties['configuration']['layouts']['templates'] : array() , array(
            'property_term_single' => 'false',
            'property_single'      => 'false',
            'search_results'       => 'false'
        ));

        $layouts_template_files = wp_parse_args( !empty( $wp_properties['configuration']['layouts']['files'] ) ? $wp_properties['configuration']['layouts']['files'] : array() , array(
            'property_term_single' => 'page.php',
            'property_single'      => 'single.php',
            'search_results'       => 'page.php'
        ));

        $template_files = apply_filters( 'wpp::layouts::template_files', wp_get_theme()->get_files( 'php', 0 ) );

        ob_start();

        ?>

        <table class="form-table wpp_layouts_table">
          <tbody>
             <tr class="wpp_layout_header"><td colspan="2"><?php _e( 'This is how your Single Property Page will look like', ud_get_wp_property()->domain ); ?>
              <small><?php _e( 'Applies only when layout is selected', ud_get_wp_property()->domain ); ?></small></td></tr>
            <tr id="property-single">
              <td>
                <input type="hidden" name="wpp_settings[configuration][layouts][files][property_single]" value="<?php echo $layouts_template_files['property_single']; ?>">
                <?php
                if ( !empty( $this->preloaded_layouts['single-property'] ) && is_array( $this->preloaded_layouts['single-property'] ) ) {
                  ?>
                  <ul class="layouts-list">
                    <li>
                      <label class="<?php echo $layouts_settings['property_single'] == 'false' ? 'checked' : ''; ?>">
                        <h5><?php _e( 'No Layout', ud_get_wp_property()->domain ); ?></h5>
                        <img width="150" height="150" src="<?php echo WPP_URL . 'images/no-layout.jpg'; ?>" alt="No Layout" />
                        <input <?php checked( 'false', $layouts_settings['property_single'] ); ?> style="display:none;" type="radio" name="wpp_settings[configuration][layouts][templates][property_single]" value="false">
                      </label>
                    </li>
                    <?php
                    foreach( $this->preloaded_layouts['single-property'] as $layout ) {
                      ?>
                      <li>
                        <label class="<?php echo $layout->_id == $layouts_settings['property_single'] ? 'checked' : ''; ?>">
                          <h5><?php echo $layout->title; ?></h5>
                          <img width="150" height="150" src="<?php echo !empty($layout->screenshot)?$layout->screenshot:WPP_URL . 'images/no-preview.jpg'; ?>" alt="<?php echo $layout->title ?>" />
                          <input <?php checked( $layout->_id, $layouts_settings['property_single'] ); ?> style="display:none;" type="radio" name="wpp_settings[configuration][layouts][templates][property_single]" value="<?php echo $layout->_id; ?>">
                        </label>
                      </li>
                      <?php
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
            
            <tr class="wpp_layout_header"><td colspan="2"><?php _e( 'This is how your Property Overview Page will look like', ud_get_wp_property()->domain ); ?>
              <small><?php _e( 'Applies only when layout is selected', ud_get_wp_property()->domain ); ?></small></td></tr>
            <tr id="search-results">
              <td>
                <input type="hidden" name="wpp_settings[configuration][layouts][files][search_results]" value="<?php echo $layouts_template_files['search_results']; ?>">
                <?php
                if ( !empty( $this->preloaded_layouts['search-results'] ) && is_array( $this->preloaded_layouts['search-results'] ) ) {
                  ?>
                
                  <ul class="layouts-list">
                    <li>
                      <label class="<?php echo $layouts_settings['search_results'] == 'false' ? 'checked' : ''; ?>">
                        <h5><?php _e( 'No Layout', ud_get_wp_property()->domain ); ?></h5>
                        <img width="150" height="150" src="<?php echo WPP_URL . 'images/no-layout.jpg'; ?>" alt="No Layout" />
                        <input <?php checked( 'false', $layouts_settings['search_results'] ); ?> style="display:none;" type="radio" name="wpp_settings[configuration][layouts][templates][search_results]" value="false">
                      </label>
                    </li>
                    <?php
                    foreach( $this->preloaded_layouts['search-results'] as $layout ) {
                      ?>
                      <li>
                        <label class="<?php echo $layout->_id == $layouts_settings['search_results'] ? 'checked' : ''; ?>">
                          <h5><?php echo $layout->title; ?></h5>
                          <img width="150" height="150" src="<?php echo !empty($layout->screenshot)?$layout->screenshot:WPP_URL . 'images/no-preview.jpg'; ?>" alt="<?php echo $layout->title ?>" />
                          <input <?php checked( $layout->_id, $layouts_settings['search_results'] ); ?> style="display:none;" type="radio" name="wpp_settings[configuration][layouts][templates][search_results]" value="<?php echo $layout->_id; ?>">
                        </label>
                      </li>
                      <?php
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

        <?php  echo apply_filters( 'wpp::layouts::settings_html', ob_get_clean() );
      }
    }
  }
}
