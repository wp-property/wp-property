<?php
/**
 * Settings 'Developer' Tab
 *
 */
global $wp_properties;

?>
<div id="wpp_developer_tab" class="wpp_subtle_tabs ui-tabs-vertical ui-helper-clearfix clearfix">
  <?php if ( !empty( $tabs ) && is_array( $tabs ) ) : ?>
    <ul class="tabs clearfix">
      <?php foreach( $tabs as $slug => $tab ) : ?>
        <li><a href="#developer_<?php echo $slug; ?>"><?php echo $tab['label']; ?></a></li>
      <?php endforeach; ?>
    </ul>
    <?php foreach( $tabs as $slug => $tab ) : ?>
      <div id="developer_<?php echo $slug; ?>" class="developer-panel">
        <?php
        if( !empty( $tab[ 'template' ] ) && is_string( $tab[ 'template' ] ) && file_exists( $tab[ 'template' ] ) ) {
          include($tab['template']);
        } elseif( !empty( $tab[ 'template' ] ) && is_callable( $tab[ 'template' ] ) ) {
          call_user_func( $tab[ 'template' ] );
        } else {
          _e( 'Invalid Template: File does not exist or callback function is undefined.', ud_get_wp_property()->domain );
        }
        ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
  <div id="wpp_dialog_wrapper_for_groups"></div>
  <div id="wpp_attribute_groups">
    <table cellpadding="0" cellspacing="0" allow_random_slug="true" class="ud_ui_dynamic_table widefat wpp_sortable">
      <thead>
      <tr>
        <th class="wpp_group_assign_col">&nbsp;</th>
        <th class='wpp_draggable_handle'>&nbsp;</th>
        <th class="wpp_group_name_col"><?php _e( 'Group Name', ud_get_wp_property()->domain ) ?></th>
        <th class="wpp_group_slug_col"><?php _e( 'Slug', ud_get_wp_property()->domain ) ?></th>
        <th class='wpp_group_main_col'><?php _e( 'Main', ud_get_wp_property()->domain ) ?></th>
        <th class="wpp_group_color_col"><?php _e( 'Group Color', ud_get_wp_property()->domain ) ?></th>
        <th class="wpp_group_action_col">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
      <?php
      if( empty( $wp_properties[ 'property_groups' ] ) ) {
        //* If there is no any group, we set default */
        $wp_properties[ 'property_groups' ] = array(
          'main' => array(
            'name'  => 'Main',
            'color' => '#bdd6ff'
          )
        );
      }
      ?>
      <?php foreach( $wp_properties[ 'property_groups' ] as $slug => $group ): ?>
        <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row='false'>
          <td class="wpp_group_assign_col">
            <input type="button" class="wpp_assign_to_group button-secondary" value="<?php _e( 'Assign', ud_get_wp_property()->domain ) ?>"/>
          </td>
          <td class="wpp_draggable_handle">&nbsp;</td>
          <td class="wpp_group_name_col">
            <input class="slug_setter" type="text" name="wpp_settings[property_groups][<?php echo $slug; ?>][name]" value="<?php echo $group[ 'name' ]; ?>"/>
          </td>
          <td class="wpp_group_slug_col">
            <input type="text" class="slug" readonly='readonly' value="<?php echo $slug; ?>"/>
          </td>
          <td class="wpp_group_main_col">
            <input type="radio" class="wpp_no_change_name" name="wpp_settings[configuration][main_stats_group]" <?php echo( isset( $wp_properties[ 'configuration' ][ 'main_stats_group' ] ) && $wp_properties[ 'configuration' ][ 'main_stats_group' ] == $slug ? "checked=\"checked\"" : "" ); ?> value="<?php echo $slug; ?>"/>
          </td>
          <td class="wpp_group_color_col">
            <input type="text" class="wpp_input_colorpicker" name="wpp_settings[property_groups][<?php echo $slug; ?>][color]" value="<?php echo $group[ 'color' ]; ?>"/>
          </td>
          <td class="wpp_group_action_col">
            <span class="wpp_delete_row wpp_link"><?php _e( 'Delete', ud_get_wp_property()->domain ) ?></span>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
      <tr>
        <td colspan='7'>
          <div style="float:left;text-align:left;">
            <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Group', ud_get_wp_property()->domain ) ?>"/>
            <input type="button" class="wpp_unassign_from_group button-secondary" value="<?php _e( 'Unassign from Group', ud_get_wp_property()->domain ) ?>"/>
          </div>
          <div style="float:right;">
            <input type="button" class="wpp_close_dialog button-secondary" value="<?php _e( 'Apply', ud_get_wp_property()->domain ) ?>"/>
          </div>
          <div class="clear"></div>
        </td>
      </tr>
      </tfoot>
    </table>
  </div>
</div>