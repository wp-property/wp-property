<?php
/**
 * WP-Property Setup Assistant
 */

global $wp_properties;
?>
<div class="item  item-wider">
  <div class="wpp_asst_screen wpp_asst_screen_5">

    <h2 class="wpp_asst_heading"><b><?php echo __( 'Do you need to keep track of agents?', ud_get_wpp_agents()->domain ); ?></b></h2>

    <div class="wpp_asst_inner_wrap">
      <ul>
        <li class="wpp_asst_label"> <?php echo __( 'Yes Please', ud_get_wpp_agents()->domain ); ?>
          <label for="yes-please">
            <input class="wpp_box" type="radio" value="yes-please" name="wpp_settings[wpp-addons][wpp-agents]" id="yes-please" <?php if( isset( $wp_properties[ 'wpp-addons' ][ 'wpp-agents' ] ) && $wp_properties[ 'wpp-addons' ][ 'wpp-agents' ] == "yes-please" ) echo "checked='checked'"; ?>> <span></span> </label>
        </li>
        <li class="wpp_asst_label"><?php echo __( 'No thanks', ud_get_wpp_agents()->domain ); ?>
          <label for="no-thanks">
            <input class="wpp_box" type="radio" value="no-thanks" name="wpp_settings[wpp-addons][wpp-agents]" id="no-thanks" <?php if( isset( $wp_properties[ 'wpp-addons' ][ 'wpp-agents' ] ) && $wp_properties[ 'wpp-addons' ][ 'wpp-agents' ] == "no-thanks" ) echo "checked='checked'"; ?> > <span></span> </label>
        </li>
      </ul>

      <small class="center foot-note">(<?php echo __( 'Installs WP-Property Agents Addon', ud_get_wpp_agents()->domain ); ?>)</small>
    </div> <!-- wpp_asst_inner_wrap -->
</div>
