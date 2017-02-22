<?php
/**
 * Render term fields related to WP-Property schema.
 *
 */

use UsabilityDynamics\WPP\Attributes;

$_attribute_data = Attributes::get_attribute_data( $tag->slug, array( 'use_cache' => false ) );

?>
<tr class="form-field wpp-term-meta-wrap">
  <th scope="row" >
    <label for="description hidden"></label>
  </th>
  <td>

    <table class="form-table">

      <?php if( isset( $_attribute_data['group' ] ) && $_attribute_data['group' ] ) {?>
      <tr>
        <th><?php _e('Attribute Group'); ?></th>
        <td><input type="text" class="regular-text code" readonly="readonly" value="<?php echo isset( $_attribute_data ) ? $_attribute_data['group_label'] : ''; ?>" /></td>
      </tr>
      <?php } ?>

      <?php if( get_term_meta( $tag->term_id, '_id', true ) ) { ?>
      <tr>
        <th><?php _e('Unique ID'); ?></th>
        <td><input type="text" class="regular-text code" readonly="readonly" value="<?php echo get_term_meta( $tag->term_id, '_id', true ); ?>" /></td>
      </tr>
      <?php } ?>

      <?php if( get_term_meta( $tag->term_id, '_created', true ) ) { ?>
      <tr>
        <th><?php _e('Term Created'); ?></th>
        <td><input type="text" class="regular-text code" readonly="readonly" value="<?php echo get_term_meta( $tag->term_id, '_created', true ); ?>" /></td>
      </tr>
      <?php } ?>

      <?php if( get_term_meta( $tag->term_id, '_updated', true ) ) { ?>
      <tr>
        <th><?php _e('Term Updated'); ?></th>
        <td><input type="text" class="regular-text code" readonly="readonly" value="<?php echo get_term_meta( $tag->term_id, '_updated', true ); ?>" /></td>
      </tr>
      <?php } ?>

    </table>

  </td>
</tr>