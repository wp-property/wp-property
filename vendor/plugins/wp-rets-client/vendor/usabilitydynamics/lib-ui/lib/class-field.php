<?php
/**
 * Settings User Interface
 *
 * @author peshkov@UD
 */
namespace UsabilityDynamics\UI {

  if( !class_exists( 'UsabilityDynamics\UI\Field' ) ) {

    class Field {
    
      public $id = NULL;
      public $name = NULL;
      public $multiple = NULL;
      public $clone = NULL;
      public $clone_group = NULL;
      public $desc = NULL;
      public $type = NULL;
      public $format = NULL;
      public $before = NULL;
      public $after = NULL;
      public $field_name = NULL;
      public $required = NULL;
      public $placeholder = NULL;
      public $value = NULL;
      public $extra = NULL;
      
      /**
       * Constructor.
       * can not be called directly. Use 'init' method.
       */
      private function __construct( $params = array() ) {
        foreach( $params as $i => $v ) {
          $this->{$i} = $v;
        }
        $this::admin_enqueue_scripts();
      }

      /**
       * Initializes an object
       *
       * @param array $params
       *
       * @return object
       */
      static public function init( $params = array() ) {
        $params = wp_parse_args( (array)$params, array(
          'id'             => false,
          'name'           => isset( $params['id'] ) ? $params['id'] : '',
          'multiple'       => false,
          'clone'          => false,
          'clone_group'    => false,
          'desc'           => '',
          'format'         => '',
          'type'           => 'text',
          'before'         => '',
          'after'          => '',
          'field_name'     => isset( $params['id'] ) ? $params['id'] : false,
          'required'       => false,
          'placeholder'    => '',
          'value'          => '',
          'options'        => array()
        ) );
        // 'id' and 'field_name' are required! Break if we don'ts have any of them.
        if( !$params[ 'id' ] || empty( $params[ 'field_name' ] ) ) {
          return false;
        }

        $class = get_called_class();
        $params = $class::normalize_field( $params );

        return new $class( $params );
      }
    
      /**
       * Add actions
       *
       * @return void
       */
      public function add_actions() {}

      /**
       * Enqueue scripts and styles
       *
       * @return void
       */
      static public function admin_enqueue_scripts() {}

      /**
       * Show field HTML
       *
       * @return string
       */
      public function show() {

        $group = '';	// Empty the clone-group field
        $type = $this->type;
        $id   = $this->id;

        $begin = $this->begin_html();
        
        // Apply filter to field begin HTML
        // 1st filter applies to all fields
        // 2nd filter applies to all fields with the same type
        $begin = apply_filters( 'ud::ui::field::begin_html', $begin, $this );
        $begin = apply_filters( "ud::ui::field::{$type}_begin_html", $begin, $this );

        // Separate code for cloneable and non-cloneable fields to make easy to maintain

        // Cloneable fields
        if ( $this->clone ) {
          if ( $this->clone_group ) {
            $group = " clone-group='{$this->clone_group}'";
          }
          
          $this->value = (array) $this->value;

          $field_html = '';

          foreach ( $this->value as $index => $sub_value ) {
            $sub_field = $this;
            $sub_field->field_name = $this->field_name . "[{$index}]";
            if ( $this->multiple ) {
              $sub_field->field_name .= '[]';
            }
            // Wrap field HTML in a div with class="uisf-clone" if needed
            $input_html = '<div class="uisf-clone">';

            // Call separated methods for displaying each type of field
            $input_html .= $this::html( $sub_value, $sub_field );

            // Apply filter to field HTML
            // 1st filter applies to all fields with the same type
            // 2nd filter applies to current field only
            $input_html = apply_filters( "ud::ui::field::{$type}_html", $input_html, $this, $sub_value );
            $input_html = apply_filters( "ud::ui::field::{$id}_html", $input_html, $this, $sub_value );

            // Add clone button
            $input_html .= $this->clone_button();

            $input_html .= '</div>';

            $field_html .= $input_html;
          }
        }
        // Non-cloneable fields
        else {
          // Call separated methods for displaying each type of field
          $field_html = $this->html( $this->value, $this );

          // Apply filter to field HTML
          // 1st filter applies to all fields with the same type
          // 2nd filter applies to current field only
          $field_html = apply_filters( "ud::ui::field::{$type}_html", $field_html, $this );
          $field_html = apply_filters( "ud::ui::field::{$id}_html", $field_html, $this );
        }

        $end = $this->end_html();

        // Apply filter to field end HTML
        // 1st filter applies to all fields
        // 2nd filter applies to all fields with the same type
        // 3rd filter applies to current field only
        $end = apply_filters( 'ud::ui::field::end_html', $end, $this );
        $end = apply_filters( "ud::ui::field::{$type}_end_html", $end, $this );
        $end = apply_filters( "ud::ui::field::{$id}_end_html", $end, $this );

        // Apply filter to field wrapper
        // This allow users to change whole HTML markup of the field wrapper (i.e. table row)
        // 1st filter applies to all fields with the same type
        // 2nd filter applies to current field only
        $html = apply_filters( "ud::ui::field::{$type}_wrapper_html", "{$begin}{$field_html}{$end}", $this );
        $html = apply_filters( "ud::ui::field::{$id}_wrapper_html", $html, $this );

        // Display label and input in DIV and allow user-defined classes to be appended
        $classes = array( 'uisf-field', "uisf-{$type}-wrapper" );
        if ( 'hidden' === $this->type )
          $classes[] = 'hidden';
        if ( !empty( $this->required ) )
          $classes[] = 'required';
        if ( !empty( $this->class ) )
          $classes[] = $field['class'];

        printf(
          $this->before . '<div class="%s"%s>%s</div>' . $this->after,
          implode( ' ', $classes ),
          $group,
          $html
        );
      }

      /**
       * Get field HTML
       *
       * @param mixed $value
       * @param array $field
       *
       * @return string
       */
      static public function html( $value, $field ) {
        return '';
      }

      /**
       * Show begin HTML markup for fields
       *
       * @return string
       */
      public function begin_html() {
        if ( empty( $this->name ) ) {
          return '<div class="uisf-input">';
        }

        return sprintf(
          '<div class="uisf-label">
            <label for="%s">%s</label>
          </div>
          <div class="uisf-input">',
          $this->id,
          $this->name
        );
      }

      /**
       * Show end HTML markup for fields
       *
       * @return string
       */
      public function end_html() {

        $button = '';
        if ( $this->clone ) {
          $button = '<a href="#" class="uisf-button button-primary add-clone">' . __( '+' ) . '</a>';
        }
        $desc = !empty( $this->desc ) ? "<p id='{$this->id}_description' class='description'>{$this->desc}</p>" : '';

        // Closes the container
        $html = "{$button}{$desc}</div>";

        return $html;
      }

      /**
       * Add clone button
       *
       * @return string $html
       */
      public function clone_button() {
        return '<a href="#" class="uisf-button button remove-clone">' . __( '&#8211;' ) . '</a>';
      }

      /**
       * Normalize parameters for field
       *
       * @param array $field
       *
       * @return array
       */
      static public function normalize_field( $field ) {
        return $field;
      }
      
    }
    
  }

}