<?php
/**
 * Settings User Interface
 *
 * @author peshkov@UD
 */
namespace UsabilityDynamics\UI {

  if( !class_exists( 'UsabilityDynamics\UI\Settings' ) ) {

    /**
     * Class Settings
     *
     */
    class Settings {
    
      /**
       * Class version.
       *
       * @public
       * @static
       * @type string
       */
      public static $version = '0.1.2';

      private $_settings;

      private $_schema;

      /**
       * Constructor
       *
       * @param
       * UsabilityDynamics\UI\Settings object $settings
       * @param                            $schema
       *
       * @internal param array $args
       */
      public function __construct( $settings = null, $schema = null ) {
        
        //echo "<pre>"; print_r( $schema ); echo "</pre>"; die();
        
        //** Break if settings var is incorrect */
        if( $settings && !is_subclass_of( $settings, 'UsabilityDynamics\Settings' ) ) {

          if( get_class( $settings ) !== 'UsabilityDynamics\Settings' ) {
            // return;
          }

        }

        $this->_settings = $settings;
        
        //** Break if schema is incorrect */
        if( $schema && ( !$this->_schema = $this->is_valid_schema( $schema ) ) ) {
          return;
        }
        
        //** Initializes settings UI */
        
        foreach ( $this->get_fields() as $field ) {
          $field->add_actions();
        }

        if( !did_action( 'admin_menu' ) ) {
          add_action( 'admin_menu', array( $this, 'admin_menu' ), 100 );
        }

      }

      /**
       * Multiple actions (action on admin_menu hook):
       * - parse (validate) schema
       * - add settings page to menu
       * - add specific hooks
       *
       */
      public function admin_menu() {
        global $submenu, $menu;
        
        extract( $this->_schema[ 'configuration' ] );
        
        $parent_slug = false;
        $capability = 'manage_options';
        
        // Maybe add main menu
        if ( isset( $main_menu ) && is_string( $main_menu ) ) {
          if ( !isset( $submenu[ $main_menu ] ) ) {
            // Menu must exists if we pass the string.
            return false;
          }
          $parent_slug = $main_menu;
          // Maybe set the same capability for secondary menu as main menu has
          foreach( $menu as $item ) {
            if( $item[2] == $main_menu ) {
              $capability = $item[1];
              break;
            }
          }
        }  elseif ( isset( $main_menu ) && is_array( $main_menu ) ) {
          extract( $main_menu = wp_parse_args( $main_menu, array(
            'page_title' => '',
            'menu_title' => '',
            'capability' => $capability,
            'menu_slug' => false,
            'icon_url' => '',
            'position' => 61,
          ) ) );
          add_menu_page( $page_title, $menu_title, $capability, $menu_slug, array( $this, 'render' ), $icon_url, $position );
          $parent_slug = $menu_slug;
        }
        
        //Maybe add secondary menu
        if ( is_array( $secondary_menu ) ) {
          extract( $secondary_menu = wp_parse_args( $secondary_menu, array(
            'parent_slug' => $parent_slug,
            'page_title' => '',
            'menu_title' => '',
            'capability' => $capability,
            'menu_slug' => '',
          ) ) );
          if( isset( $parent_slug ) ) {
            $id = add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, array( $this, 'render' ) );
            add_action( 'load-' . $id, array( $this, 'request' ) );
          }
        }
        
      }
      
      /**
       * Saves data.
       * 
       */
      public function request() {
        // Determine if we have to do form submit
        if ( !isset( $_POST['_wpnonce'] ) || !wp_verify_nonce( $_POST['_wpnonce'], 'ui_settings' )  ) {
          return false;
        } 
        
        $fields = $this->get( 'fields', 'schema' );
        
        $changed = false;
        foreach( $_POST as $i => $v ) {
          $id = str_replace( '|', '.', $i );
          $match = false;
          foreach( $fields as $field ) {
            if( $field[ 'id' ] == $id ) {
              $match = true;
              break;
            }
          }
          if( $match ) {
            $changed = true;
            $this->_settings->set( $id, $v );
          }
        }
        
        if( $changed ) {
          $this->_settings->commit();
          wp_redirect( $_POST[ '_wp_http_referer' ] . '&message=updated' );
          exit;
        }
        
      }
      
      /**
       * Render Settings page
       * 
       */
      public function render() {
        
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_script( 'accordion' );
        
        wp_enqueue_script( 'ud-ui-settings', plugin_dir_url( dirname( __DIR__ ) . '/' . basename( __FILE__ ) ) . 'static/scripts/admin/ui.settings.js', array(
          'jquery-ui-tabs', 
          'accordion' 
        ) );
        
        wp_enqueue_style( 'ud-ui-settings', plugin_dir_url( dirname( __DIR__ ) . '/' . basename( __FILE__ ) ) . 'static/styles/admin/ui.settings.css' );
        
        //** Initializes settings UI */
        foreach ( $this->get_fields() as $field ) {
          $field->admin_enqueue_scripts();
        }
        
        do_action( 'ud:ui:settings:render' );
        
        $this->get_template_part( 'main' );
      }
      
      /**
       * Renders template part.
       * 
       */
      public function get_template_part( $name, $data = array() ) {
        if( is_array( $data ) ) {
          extract( $data );
        }
        $path = dirname( __DIR__ ) . '/static/templates/admin/' . $name . '.php';
        if( file_exists( $path ) ) {
          include( $path );
        }
      }
      
      /**
       * 
       *
       */
      public function get( $key, $type = 'settings', $default = null ) {
        switch( $type ) {
          case 'settings':
            return $this->_settings->get( $key, $default );
            break;
          case 'schema':
            // Resolve dot-notated key.
            if( strpos( $key, '.' ) ) {
              return $this->parse_schema( $key, $default );
            }
            // Return value or default.
            return isset( $this->_schema[ $key ] ) ? $this->_schema[ $key ] : $default;
            break;
        }
        return false;
      }
      
      /**
       * Returns the list on prepared fields
       *
       */
      public function get_fields( $group = false, $v = false ) {
      
        $fields = array();
      
        if( !empty( $group ) && !empty( $v ) ) {
          switch( $group ) {
          
            case 'section':
              foreach( $this->get( 'fields', 'schema' ) as $field ) {
                $field = $this->get_field( $field );
                if( $field && $field->section == $v ) {
                  $fields[] = $field;
                }
              }
              break;
              
            case 'menu':
            
            case 'tab':
              $sections = array();
              foreach( $this->get( 'sections', 'schema' ) as $section ) {
                if( isset( $section[ 'menu' ] ) && $section[ 'menu' ] == $v ) {
                  $sections[] = $section[ 'id' ];
                }
              }
              foreach( $this->get( 'fields', 'schema' ) as $field ) {
                $field = $this->get_field( $field );
                if( $field && in_array( $field->section, $sections ) ) {
                  $fields[] = $field;
                }
              }
              break;
              
            default:
              foreach( $this->get( 'fields', 'schema' ) as $field ) {
                $fields[] = $this->get_field( $field );
              }
              break;
              
          }
        }
      
        return $fields;
      }
      
      /**
       * Prepares ( normalizes ) field
       *
       * @param array $field
       * @return object $field
       */
      public function get_field( $field ) {
        static $fields = array();
        
        if( is_object( $field ) && is_subclass_of( $field, 'UsabilityDynamics\UI\Field' ) ) {
          return $field;
        } else {
          // Something went wrong. Variable must not be an object on this step.
          if( is_object( $field ) ) {
            return false;
          }
          
          // Probably we already initialized field object. So, just return it.
          if( !empty( $fields[ $field[ 'id' ] ] ) ) {
            return $fields[ $field[ 'id' ] ];
          }
          
          $field[ 'type' ] = !empty( $field[ 'type' ] ) ? $field[ 'type' ] : 'text';
          $field[ 'value' ] = $this->get( $field[ 'id' ] );
          $field[ 'field_name' ] = str_replace( '.', '|', $field[ 'id' ] );
          $field[ 'id' ] = sanitize_key( str_replace( '.', '_', $field[ 'id' ] ) );
          $field = apply_filters( "ud:ui:field", $field );
          
          $field = call_user_func( array( $this->get_field_class_name( $field ), 'init' ), $field );
          
          if( !$field ) {
            return false;
          }
          $fields[ $field->id ] = $field;
        }
        return $field;
      }
      
      /**
       * Get field class name
       *
       * @param array $field Field array
       *
       * @return bool|string Field class name OR false on failure
       */
      static function get_field_class_name( $field ) {
        // Convert underscores to whitespace so ucwords works as expected. Otherwise: plupload_image -> Plupload_image instead of Plupload_Image
        $_type = str_replace( '_', ' ', $field['type'] );
        $_type = ucwords( $_type );
        // Replace whitespace with underscores
        $_type = str_replace( ' ', '_', $_type );
        
        $class = "\UsabilityDynamics\UI\Field_{$_type}";
        if ( !class_exists( $class ) ) {
          return false;
        }
        return $class;
      }
      
      /**
       * Validates schema
       * @todo: implement schema validator
       */
      private function is_valid_schema( $schema ) {
        return $schema;
      }

      /**
       * Resolve dot-notated key.
       *
       * @source http://stackoverflow.com/questions/14704984/best-way-for-dot-notation-access-to-multidimensional-array-in-php
       *
       * @param       $path
       * @param null  $default
       *
       * @internal param $a
       * @internal param array $a
       * @return array|null
       */
      private function parse_schema( $path, $default = null ) {
        $current = $this->_schema;
        $p = strtok( $path, '.' );
        while( $p !== false ) {
          if( !isset( $current[ $p ] ) ) {
            return $default;
          }
          $current = $current[ $p ];
          $p = strtok( '.' );
        }
        return $current;
      }
    
    }

  }

}