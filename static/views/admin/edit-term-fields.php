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

    <table>

      <tr>
        <th>Attribute Group</th>
        <td><input type="text" readonly="readonly" value="<?php echo $_attribute_data['group_label']; ?>" /></td>
      </tr>

      <tr>
        <th>Internal ID</th>
        <td><input type="text" readonly="readonly" value="<?php echo get_term_meta( $tag->term_id, '_id', true ); ?>" /></td>
      </tr>
      <tr>
        <th>Created</th>
        <td><input type="text" readonly="readonly" value="<?php echo get_term_meta( $tag->term_id, '_created', true ); ?>" /></td>
      </tr>
      <tr>
        <th>Updated</th>
        <td><input type="text" readonly="readonly" value="<?php echo get_term_meta( $tag->term_id, '_updated', true ); ?>" /></td>
      </tr>

    </table>

  </td>
</tr>