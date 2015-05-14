<?php
/**
 * Filter for List Table.
 *
 */
namespace UsabilityDynamics\WPLT {

  if( !defined( 'ABSPATH' ) ) {
    die();
  }

  if (!class_exists('UsabilityDynamics\WPLT\Filter')) {

    class Filter {

      /**
       * Additional properties are stored here.
       * It is using __get and __set methods
       */
      private $properties;

      /**
       * @var
       */
      public $name;

      /**
       * @var
       */
      public $map;

      /**
       * @var
       */
      public $fields;

      /**
       *
       */
      public function __construct( $args = array() ) {

        $args = wp_parse_args( $args, array(
          'name' => false,
          'fields' => array(),
        ) );

        $this->name = $args[ 'name' ];
        $this->fields = $this->prepare_fields( $args['fields'] );

      }

      /**
       *
       */
      public function display() {

        if( empty( $this->fields ) ) {
          return;
        }

        $path = apply_filters( 'wplt:filter:template', Utility::path( 'static/views/filter.php', 'dir' ), array( $this->name, $this->map, $this->fields ) );
        if( file_exists( $path ) ) {
          echo "<div class=\"wplt-filter\" data-for=\"{$this->name}\">";
          include( $path );
          echo "</div>";
        }
      }

      /**
       * Returns the list on prepared map
       *
       */
      public function prepare_fields( $fields ) {
        if( !is_array( $fields ) ) {
          return array();
        }
        $_fields = array();
        foreach( $fields as $field ) {
          $field = $this->get_field( $field );
          if(!$field) {
            continue;
          }
          $_fields[] = $field;
        }
        return $_fields;
      }

      /**
       * Normalize Search Map
       *
       */
      public function get_map( $field ) {
        $postmap = array(
          // Search attribute
          's',
          // Post and Page attributes
          'p',
          'post_type',
          'post_status',
          'name',
          'page_id',
          'pagename',
          'post_parent',
          'post_parent__in',
          'post_parent__not_in',
          'post__in',
          'post__not_in',
          // Category attributes
          'cat',
          'category_name',
          'category__and',
          'category__in',
          'category__not_in',
          // Tag attributes
          'tag',
          'tag_id',
          'tag__and',
          'tag__in',
          'tag__not_in',
          'tag_slug__and',
          'tag_slug__in',
        );
        $map = !empty( $field[ 'map' ] ) ? $field[ 'map' ] : array();
        if( empty( $map['class'] ) ) {
          $map['class'] = !empty( $field['id'] ) && in_array( str_replace( 'wplt_filter_', '', $field['id'] ), $postmap ) ? 'post' : 'meta';
        }
        if( empty( $map['type'] ) ) {
          $map['type'] = 'string';
        }
        if( empty( $map['compare'] ) ) {
          $map['compare'] = '=';
        }
        return $map;
      }

      /**
       * Normalize an array of fields
       *
       * @param array $fields Array of fields
       * @return array $fields Normalized fields
       */
      /**
       * Prepares ( normalizes ) field
       *
       * @param array $field
       * @return object $field
       */
      private function get_field( $field ) {
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
          $field[ 'value' ] = !empty( $field[ 'std' ] ) ? $field[ 'std' ] : false;
          $field[ 'field_name' ] = 'wplt_filter.' . str_replace( '.', '|', $field[ 'id' ] );
          $field[ 'id' ] = 'wplt_filter_' . sanitize_key( str_replace( '.', '_', $field[ 'id' ] ) );
          $field[ 'extra' ] = urlencode(json_encode($this->get_map($field)));
          $field = apply_filters( "wplt:filter:field", $field );

          $class = $this->get_field_class_name( $field );
          if(!$class) {
            return false;
          }

          $field = call_user_func( array( $class, 'init' ), $field );

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
      private function get_field_class_name( $field ) {
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
       * Store all custom properties in $this->properties
       *
       * @author peshkov@UD
       */
      public function __set($name, $value) {
        $this->properties[$name] = $value;
      }

      /**
       * Get custom properties
       *
       * @author peshkov@UD
       */
      public function __get($name) {
        return isset($this->properties[$name]) ? $this->properties[$name] : NULL;
      }

    }

  }

}