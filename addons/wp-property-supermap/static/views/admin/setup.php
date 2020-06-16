<?php
/**
 * WP-Property Setup Assistant
 */

global $wp_properties;
?>
<div class="item  item-wider">
  <div class="wpp_asst_screen wpp_asst_screen_5">
    <h2 class="wpp_asst_heading"><b><?php echo __( 'Do you want to display interactive searchable maps?', ud_get_wpp_supermap()->domain ); ?></b></h2>
    <div class="wpp_asst_inner_wrap">
      <ul>
        <li class="wpp_asst_label"> <?php echo __( 'Sure', ud_get_wpp_supermap()->domain ); ?>
          <label for="yes-please">
            <input class="wpp_box" type="radio" value="yes-please" name="wpp_settings[wpp-addons][wpp-supermap]" id="yes-please" <?php if( isset( $wp_properties[ 'wpp-addons' ][ 'wpp-supermap' ] ) && $wp_properties[ 'wpp-addons' ][ 'wpp-supermap' ] == "yes-please" ) echo "checked='checked'"; ?>> <span></span> </label>
        </li>
        <li class="wpp_asst_label"><?php echo __( 'Maybe later', ud_get_wpp_supermap()->domain ); ?>
          <label for="no-thanks">
            <input class="wpp_box" type="radio" value="no-thanks" name="wpp_settings[wpp-addons][wpp-supermap]" id="no-thanks" <?php if( isset( $wp_properties[ 'wpp-addons' ][ 'wpp-supermap' ] ) && $wp_properties[ 'wpp-addons' ][ 'wpp-supermap' ] == "no-thanks" ) echo "checked='checked'"; ?> > <span></span> </label>
        </li>
      </ul>
      <small class="center foot-note">(<?php echo __( 'Installs WP-Property Supermap Add', ud_get_wpp_supermap()->domain ); ?>)</small>
    </div> <!-- wpp_asst_inner_wrap -->
  </div>
</div>
