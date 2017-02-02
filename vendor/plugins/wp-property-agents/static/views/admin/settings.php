<?php
/**
 * Agents Tab on WP-Property Settings pgae
 */

global $wpdb, $wp_properties, $wp_roles;

//* Making sure that old data gets removed and that there's at least one row */
$agent_data = isset( $wp_properties['configuration']['feature_settings']['agents'] ) ?
  (array)$wp_properties['configuration']['feature_settings']['agents'] : array( 'agent_roles' => array(), 'agent_fields' => array() );

$agent_roles = $agent_data['agent_roles'];

$agent_fields = stripslashes_deep( self::clean_array( $agent_data['agent_fields'] ) );

if( isset( $agent_data['agent_social_fields'] ) ) {
  $agent_social_fields = stripslashes_deep( self::clean_array( $agent_data['agent_social_fields'] ) );
} else {
  $agent_social_fields = array();
}

//** Force at lest one role to always be associated */
if ( empty($agent_roles) ) {
  $agent_roles =  array('agent');
}

//** if no agent-meta secists, load in default data, and set $default_data to true so first row is treated as new and canbe edited */
if ( empty($agent_fields) ) {
  $default_data = true;
  $agent_fields = array(
    'phone_number' => array('name' => 'Phone Number')
  );
}if ( empty($agent_social_fields) ) {
  $default_data = true;
  $agent_social_fields = array(
    'facebook' => array('name' => 'Facebook')
  );
}

?>
<table class="form-table">
  <tr>
    <th>
      <?php _e('Agent\'s Role Label',ud_get_wpp_agents()->domain); ?>
    </th>
    <td>
      <ul>
        <li><label><?php _e( 'Single', ud_get_wpp_agents()->domain ); ?>: <input type="text" title="" value="<?php echo ud_get_wp_property( 'configuration.feature_settings.agents.label.single', 'Agent' ); ?>" name="wpp_settings[configuration][feature_settings][agents][label][single]" class="" style="" /></label></li>
        <li><label><?php _e( 'Plural', ud_get_wpp_agents()->domain ); ?>: <input type="text" title="" value="<?php echo ud_get_wp_property( 'configuration.feature_settings.agents.label.plural', 'Agents' ); ?>" name="wpp_settings[configuration][feature_settings][agents][label][plural]" class="" style="" /></label></li>
      </ul>
    </td>
  </tr>
  <tr>
    <th>
      <?php _e('Agent Roles',ud_get_wpp_agents()->domain); ?>
      <div class="description"><?php _e('User roles that can be associated with properties as agents.',ud_get_wpp_agents()->domain); ?></div>
    </th>
    <td>
      <ul class="wp-tab-panel ">
        <?php foreach($wp_roles->roles as $role_slug => $role_data){ ?>
          <li>
            <input id="wpp_settings_configuration_feature_settings_agents_agent_roles_<?php echo $role_slug; ?>" type="checkbox" name="wpp_settings[configuration][feature_settings][agents][agent_roles][]" value="<?php echo esc_attr($role_slug); ?>" <?php echo (in_array($role_slug, $agent_roles) ? ' checked="true" ' : ''); ?>/>
            <label for="wpp_settings_configuration_feature_settings_agents_agent_roles_<?php echo $role_slug; ?>"><?php echo $role_data['name']; ?></label>
          </li>
        <?php } ?>
      </ul>
    </td>
  </tr>
  <tr>
    <th>
      <?php _e('Agent Fields',ud_get_wpp_agents()->domain); ?>
      <div class="description"><?php _e('Extra data fields to be used for agent profiles.',ud_get_wpp_agents()->domain); ?></div>
    </th>
    <td>
      <p><?php _e('Custom fields may be used to adapt the information about agents to suit your needs.', ud_get_wpp_agents()->domain); ?></p>

      <table id="wpp_agent_fields" class="ud_ui_dynamic_table widefat" allow_random_slug="true">
        <thead>
        <tr>
          <th><?php _e('Field name', ud_get_wpp_agents()->domain) ?></th>
          <th style="width:50px;"><?php _e('Slug', ud_get_wpp_agents()->domain) ?></th>
          <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($agent_fields as $slug => $field) { ?>
          <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row="<?php echo( isset( $default_data ) && $default_data ? 'true' : 'false'); ?>">
            <td><input class="slug_setter" type="text" name="wpp_settings[configuration][feature_settings][agents][agent_fields][<?php echo $slug; ?>][name]" value="<?php echo $field['name']; ?>" /></td>
            <td><input type="text" value="<?php echo $slug; ?>" readonly="readonly" class="slug" /></td>
            <td><span class="wpp_delete_row wpp_link"><?php _e('Delete', ud_get_wpp_agents()->domain) ?></span></td>
          </tr>
        <?php }  ?>
        </tbody>
        <tfoot>
        <tr>
          <td colspan="3"><input type="button" class="wpp_add_row button-secondary" value="<?php _e('Add Row', ud_get_wpp_agents()->domain) ?>" /></td>
        </tr>
        </tfoot>
      </table>
    </td>
  </tr>

  <tr>
    <th>
      <?php _e('Agent Social Fields',ud_get_wpp_agents()->domain); ?>
      <div class="description"><?php _e('Fields for social links',ud_get_wpp_agents()->domain); ?></div>
    </th>
    <td>
      <p><?php _e('Custom fields may be used to adapt the information about agents to suit your needs.', ud_get_wpp_agents()->domain); ?></p>

      <table id="wpp_agent_social_fields" class="ud_ui_dynamic_table widefat" allow_random_slug="true">
        <thead>
        <tr>
          <th><?php _e('Field name', ud_get_wpp_agents()->domain) ?></th>
          <th style="width:50px;"><?php _e('Slug', ud_get_wpp_agents()->domain) ?></th>
          <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($agent_social_fields as $slug => $field) { ?>
          <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row="<?php echo( isset( $default_data ) && $default_data ? 'true' : 'false'); ?>">
            <td><input class="slug_setter" type="text" name="wpp_settings[configuration][feature_settings][agents][agent_social_fields][<?php echo $slug; ?>][name]" value="<?php echo $field['name']; ?>" /></td>
            <td><input type="text" value="<?php echo $slug; ?>" readonly="readonly" class="slug" /></td>
            <td><span class="wpp_delete_row wpp_link"><?php _e('Delete', ud_get_wpp_agents()->domain) ?></span></td>
          </tr>
        <?php }  ?>
        </tbody>
        <tfoot>
        <tr>
          <td colspan="3"><input type="button" class="wpp_add_row button-secondary" value="<?php _e('Add Row', ud_get_wpp_agents()->domain) ?>" /></td>
        </tr>
        </tfoot>
      </table>
    </td>
  </tr>
</table>