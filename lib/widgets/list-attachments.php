<?php

/**
 * List Attachments Widget
 */
namespace UsabilityDynamics\WPP\Widgets {

  /**
   * Class ListAttachmentsWidget
   *
   * @package UsabilityDynamics\WPP\Widgets
   */
  class ListAttachmentsWidget extends \UsabilityDynamics\WPP\Widget {

    /**
     * @var string
     */
    public $shortcode_id = 'list_attachments';

    /**
     * Init
     */
    public function __construct() {
      parent::__construct( 'wpp_list_attachments', $name = __( 'List Attachments', ud_get_wp_property()->domain ), array( 'description' => sprintf( __( 'Used to display attachments of a %s, can also be used in a post.', ud_get_wp_property()->domain ), \WPP_F::property_label() ) ) );
    }

    /**
     * Widget body
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
      $before_widget = '';
      $after_widget = '';
      $before_title = '';
      $after_title = '';
      extract( $args );

      $title = isset( $instance[ '_widget_title' ] ) ? $instance[ '_widget_title' ] : '';

      echo $before_widget;
      if ( !empty( $title ) ) {
        echo $before_title . $title . $after_title;
      }
      echo do_shortcode( '[list_attachments '.$this->shortcode_args( $instance ).']' );
      echo $after_widget;
    }

    /**
     * Renders form based on Shortcode's params
     *
     * @param array $instance
     */
    public function form( $instance ) {
      ?>
      <p>
        <label class="widefat" for="<?php echo $this->get_field_id( '_widget_title' ); ?>"><?php _e( 'Title', ud_get_wp_property( 'domain' ) ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( '_widget_title' ); ?>"
               name="<?php echo $this->get_field_name( '_widget_title' ); ?>" type="text"
               value="<?php echo !empty( $instance[ '_widget_title' ] ) ? $instance[ '_widget_title' ] : ''; ?>"/>
        <span class="description"><?php _e( 'Widget\'s Title', ud_get_wp_property( 'domain' ) ); ?></span>
      </p>
      <?php
      parent::form( $instance );
    }

    /**
     * Update handler
     *
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    public function update( $new_instance, $old_instance ) {
      return $new_instance;
    }
  }

  /**
   * Register this widget
   */
  add_action( 'widgets_init', function() {
    /**
     * Load list-attachments shortcode
     * if the List Attachments Shortcode plugin does not exist
     */
    global $shortcode_tags;
    $shortcodes = array_keys( (array) $shortcode_tags );
    if ( !in_array( 'list-attachments', $shortcodes ) ) {
      register_widget( 'UsabilityDynamics\WPP\Widgets\ListAttachmentsWidget' );
    };
  });
}