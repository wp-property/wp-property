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
                         value="<?php echo !empty( $instance[ $param['id'] ] ) ? $instance[ $param['id'] ] : $param['default']; ?>"/>
                  <?php
                  break;
                case 'number':
                  ?>
                  <input class="widefat" id="<?php echo $this->get_field_id( $param['id'] ); ?>"
                         min="<?php echo $param['min']; ?>"
                         name="<?php echo $this->get_field_name( $param['id'] ); ?>" type="number"
                         value="<?php echo !empty( $instance[ $param['id'] ] ) ? $instance[ $param['id'] ] : $param['default']; ?>"/>
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
                  ?>
                <?php } ?>
            </p>

          <?php endforeach;

        } else {
          _e( '<p>No options available.</p>', ud_get_wp_property()->domain );
        }

      }

    }

  }
}