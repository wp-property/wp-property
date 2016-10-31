<?php
/**
 * Renders content for
 * Meta Box 'Template'
 * on Edit Property page
 */
?>
<div>
  <input type="hidden" name="wpp_data[meta][_wpp_redeclare_template]" value="false" checked />
  <label><input type="checkbox" name="wpp_data[meta][_wpp_redeclare_template]" value="true" <?php echo $redeclare == 'true' ? 'checked' : ''; ?> /> <?php printf( 'Override global template for current property with selected one below.', ud_get_wp_property( 'domain' ) ); ?></label>
</div>
<ul>
  <li>
    <label><input type="radio" name="wpp_data[meta][_wpp_template]" value="property" <?php echo $template == 'property' ? 'checked' : ''; ?> /> <?php printf( 'Default Property Template', ud_get_wp_property( 'domain' ) ); ?></label>
  </li>
  <li>
    <label><input type="radio" name="wpp_data[meta][_wpp_template]" value="single" <?php echo $template == 'single' ? 'checked' : ''; ?> /> <?php printf( 'Single Post Template', ud_get_wp_property( 'domain' ) ); ?></label>
  </li>
  <li>
    <label><input type="radio" name="wpp_data[meta][_wpp_template]" value="page" <?php echo $template == 'page' ? 'checked' : ''; ?> /> <?php printf( 'Page Template', ud_get_wp_property( 'domain' ) ); ?></label><br/><br/>
    <div>
      <label><b><?php printf( __( 'Page Template', ud_get_wp_property( 'domain' ) ) ); ?></b></label><br/>
      <select name="wpp_data[meta][_wpp_page_template]">
        <option value="default" <?php echo $page_template == 'default' ? 'selected="selected"' : ''; ?> ><?php _e( 'Default Template', ud_get_wp_property( 'domain' ) ); ?></option>
        <?php foreach ( get_page_templates() as $title => $slug ) : ?>
          <option value="<?php echo $slug ?>" <?php echo $page_template == $slug ? 'selected="selected"' : ''; ?> ><?php echo $title; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </li>
</ul>
<p class="description"><?php printf( __( 'For more details, see <b>Single Property template</b> section on Main tab of %s Settings page.', ud_get_wp_property( 'domain' ) ), 'WP-Property' ); ?></p>