<?php
/**
 * Widget base
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\Widget')) {

    class Widget extends \WP_Widget {

      /**
       * Determines template and renders it
       *
       *
       */
      public function get_template( $template, $data, $output = true ) {
        $name = apply_filters( $this->id . '_template_name', array( $template ), $this );
        /* Set possible pathes where templates could be stored. */
        $path = apply_filters( $this->id . '_template_path', array(
            ud_get_wp_property()->path( 'static/views', 'dir' ),
        ) );

        $path = \UsabilityDynamics\Utility::get_template_part( $name, $path, array(
            'load' => false
        ) );

        if( $output ) {
          extract( $data );
          include $path;
        } else {
          ob_start();
          extract( $data );
          include $path;
          return ob_get_clean();
        }
      }

      /**
       * Prepare instance for shortcode
       *
       * @param $instance
       * @return string
       */
      public function shortcode_args( $instance ) {

        $args = array();

        $_shortcode = \UsabilityDynamics\Shortcode\Manager::get_by( 'id', $this->shortcode_id );

        if ( is_object( $_shortcode ) && !empty( $_shortcode->params ) && is_array( $_shortcode->params ) ) {

          foreach( $_shortcode->params as $param ) {

            if( !array_key_exists( $param['id'], $instance ) ) {
              continue;
            }

            $value = $instance[ $param['id'] ];

            switch( $param['type'] ) {

              case 'custom_attributes':
                if( is_array( $value ) ) {
                  foreach( $value as $k => $v ) {
                    if( !empty( $v ) && is_string( $v ) ) {
                      $args[] = $k . '="' . esc_attr( $v ) . '"';
                    }
                  }
                }

                break;

              default:
                if ( is_array( $value ) ) {
                  $value = implode( ',', array_keys( $value ) );
                }
                $args[] = $param['id'] . '="' . esc_attr( $value ) . '"';

            }

          }

        } else {

          if ( !empty( $instance ) && is_array( $instance ) ) {
            foreach( $instance as $name => $value ) {
              if ( is_array( $value ) ) {
                $value = implode( ',', array_keys( $value ) );
              }
              $args[] = $name . '="' . esc_attr( $value ) . '"';
            }
          }

        }

        return implode( ' ', $args );

      }

      /**
       * Form handler
       *
       * @param array $instance
       * @return bool
       */
      public function form($instance) {

        $_shortcode = \UsabilityDynamics\Shortcode\Manager::get_by( 'id', $this->shortcode_id );

        if ( is_object( $_shortcode ) && !empty( $_shortcode->params ) && is_array( $_shortcode->params ) ) {

          foreach( $_shortcode->params as $param ) : ?>

            <p>
              <label class="widefat" for="<?php echo $this->get_field_id( $param['id'] ); ?>"><?php echo $param['name']; ?></label>
              <?php
              switch( $param['type'] ) {

                case 'text':
                  ?>
                  <input class="widefat" id="<?php echo $this->get_field_id( $param['id'] ); ?>"
                         name="<?php echo $this->get_field_name( $param['id'] ); ?>" type="text"
                         value="<?php echo esc_attr( !empty( $instance[ $param['id'] ] ) ? $instance[ $param['id'] ] : $param['default'] ); ?>"/>
                  <?php
                  break;

                case 'number':
                  ?>
                  <input class="widefat" id="<?php echo $this->get_field_id( $param['id'] ); ?>"
                         min="<?php echo $param['min']; ?>"
                         name="<?php echo $this->get_field_name( $param['id'] ); ?>" type="number"
                         value="<?php echo esc_attr( !empty( $instance[ $param['id'] ] ) ? $instance[ $param['id'] ] : $param['default'] ); ?>"/>
                  <?php
                  break;

                case 'select':
                  ?>
                  <select class="widefat" id="<?php echo $this->get_field_id( $param['id'] ); ?>"
                          name="<?php echo $this->get_field_name( $param['id'] ); ?>">
                    <?php
                    if ( !empty( $param['options'] ) && is_array( $param['options'] ) ) {
                      foreach( $param['options'] as $opt_name => $opt_label ) {
                        ?>
                        <option value="<?php echo $opt_name; ?>" <?php selected( $opt_name, !empty( $instance[ $param['id'] ] ) ? $instance[ $param['id'] ] : $param['default'] ) ?>><?php echo $opt_label; ?></option>
                      <?php
                      }
                    }
                    ?>
                  </select>
                  <?php
                  break;

                case 'checkbox':
                  ?>
                  <input type="checkbox"
                          <?php echo ( !empty( $instance[ $param['id'] ] ) ) ? 'checked' : ''; ?>
                          id="<?php echo $this->get_field_id( $param['id'] ); ?>"
                          value="true"
                          name="<?php echo $this->get_field_name( $param['id'] ); ?>">
                  <?php
                  break;

                case 'multi_checkbox':
                  if ( !empty( $param['options'] ) && is_array( $param['options'] ) ) { ?>
                  <ul class="wpp-multi-checkbox-wrapper">
                    <?php foreach( $param['options'] as $opt_name => $opt_label ) { ?>
                      <li><label><input type="checkbox"
                          <?php echo ( !empty( $instance[ $param['id'] ][ $opt_name ] ) ) ? 'checked' : ''; ?>
                          class="widefat"
                          id="<?php echo $this->get_field_id( $param['id'] ) . '_' . $opt_name; ?>"
                          name="<?php echo $this->get_field_name( $param['id'] ); ?>[<?php echo $opt_name; ?>]"> <?php echo $opt_label; ?></label>
                      </li>
                    <?php } ?>
                  </ul>
                  <?php }
                  break;

                case 'custom_attributes':
                  if ( !empty( $param['options'] ) && is_array( $param['options'] ) ) { ?>
                  <ul class="wpp-multi-checkbox-wrapper">
                    <?php foreach( $param['options'] as $opt_name => $opt_label ) { ?>
                      <li><label><?php echo $opt_label; ?> <input type="text"
                          value="<?php echo ( !empty( $instance[ $param['id'] ][ $opt_name ] ) ) ? $instance[ $param['id'] ][ $opt_name ] : ''; ?>"
                          class="widefat"
                          id="<?php echo $this->get_field_id( $param['id'] ) . '_' . $opt_name; ?>"
                          name="<?php echo $this->get_field_name( $param['id'] ); ?>[<?php echo $opt_name; ?>]"></label>
                      </li>
                    <?php } ?>
                  </ul>
                  <?php }
                  break;


              } ?>

              <?php if( !empty( $param[ 'description' ] ) ) : ?>
                <span class="description"><?php echo $param[ 'description' ]; ?></span>
              <?php endif; ?>
            </p>

          <?php endforeach;

        }

      }

    }

  }
}