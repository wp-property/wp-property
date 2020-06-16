<?php
/**
 * Property Agents Widget
 */

/**
 * AgentWidget Class
 */
class AgentWidget extends WP_Widget {

  function __construct() {
    $name = sprintf( '%s %s', WPP_F::property_label(), ud_get_wp_property( 'configuration.feature_settings.agents.label.plural' ) );
    parent::__construct(false, $name );
  }

  function widget($args, $instance) {

    global $post, $property, $wp_properties;
    $before_widget = $after_widget = $after_title = $before_title = '';

    extract( $args );

    $saved_fields = !empty( $instance['saved_fields'] ) ? $instance['saved_fields'] : false;
    $widget_title = apply_filters('widget_title', $instance['title']);
    $widget_agent_id = false;

    if(!empty( $instance['agent_id'] )){
      $widget_agent_id = explode(',', $instance['agent_id']);
    }
    

    $agents = !empty ($widget_agent_id) ? $widget_agent_id : (!empty( $post->wpp_agents ) ? $post->wpp_agents : ( !empty( $property['wpp_agents'] ) ? $property['wpp_agents'] : false ));

    if( empty( $agents ) || !is_array( $agents ) ) {
      return false;
    }

    if( empty( $saved_fields ) ) {
      return false;
    }

    $agents = array_unique( $agents );
    foreach($agents as $agent_id) {
      $this_agent = class_agents::display_agent_card($agent_id,"fields=" . implode(',',$saved_fields));

      if(!empty($this_agent)) {
        $agent_data[] = $this_agent;
      }
    }

    if(empty($agent_data)) {
      return;
    }

    echo $before_widget;

    if ( $widget_title ) {
      echo $before_title . $widget_title . $after_title;
    }

    echo "<div class='wpp_agent_widget_wrapper " . (empty($widget_title)  ? 'wpp_no_widget_title' : ' wpp_has_widget_title ') . "'>";
    echo implode($agent_data);
    echo "</div>";

    do_action( 'wpp:agent:widget:before_end', $agent_data );

    echo $after_widget;
    do_action( 'wpp:agent:widget:end', $agent_data );
  }

  function update($new_instance, $old_instance) {
    return $new_instance;
  }

  function form($instance) {
    global $wp_properties;
    $title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
    $agent_id = isset( $instance['agent_id'] ) ? esc_attr( $instance['agent_id'] ) : '';
    $saved_fields = isset( $instance['saved_fields'] ) ? $instance['saved_fields'] : array();

    $display_fields['display_name'] = __('Display Name', ud_get_wpp_agents()->domain);
    $display_fields['agent_image'] = __('Image', ud_get_wpp_agents()->domain);
    $display_fields['widget_bio'] = __('Widget Text', ud_get_wpp_agents()->domain);
    $display_fields['full_bio'] = __('Full Bio', ud_get_wpp_agents()->domain);

    if(!empty($wp_properties['configuration']['feature_settings']['agents']['agent_fields'])) {
      foreach ($wp_properties['configuration']['feature_settings']['agents']['agent_fields'] as $slug => $attr_data) {
        $display_fields[$slug] = $attr_data['name'];
      }
    }
    if(!empty($wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'])) {
      foreach ($wp_properties['configuration']['feature_settings']['agents']['agent_social_fields'] as $slug => $attr_data) {
        $display_fields[$slug] = $attr_data['name'];
      }
    }

    $display_fields['user_email'] = __('Email Address', ud_get_wpp_agents()->domain);
    ?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('agent_id'); ?>"><?php _e('Agent ID:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('agent_id'); ?>" name="<?php echo $this->get_field_name('agent_id'); ?>" type="text" value="<?php echo $agent_id; ?>" />
    </p>

    <div class="wp-tab-panel">
      <ul>
        <?php foreach($display_fields as $stat => $label): if(empty($label)) continue; ?>
          <li>
            <input id="<?php echo $this->get_field_id('saved_fields'); ?>_<?php echo $stat; ?>" name="<?php echo $this->get_field_name('saved_fields'); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
              <?php if(is_array($saved_fields) && in_array($stat, $saved_fields)) echo " checked "; ?>    />
            <label for="<?php echo $this->get_field_id('saved_fields'); ?>_<?php echo $stat; ?>"><?php echo $label;?></label>
          </li>
          <?php  ?>

        <?php endforeach; ?>
      </ul>

    </div>
    <?php
  }
} // end class AgentWidget

add_action('widgets_init', function(){
  return register_widget( "AgentWidget" );
} );