<?php
/**
 * Render term fields related to WP-Property schema.
 *
 * - _type - A sub-categorization of terms within a taxonomy.
 * - _id - Unique textual ID.
 * - meta.url_path - Stores full relative path to the term.
 *
 * @todo Use WPP_F::get_term_metadata()
 *
 * @todo Implement saving/updating fields and then remove readonly.
 *
 */

use UsabilityDynamics\WPP\Attributes;

$_attribute_data = Attributes::get_attribute_data( $tag->slug, array( 'use_cache' => false ) );

$_taxonomy = (array) get_taxonomy( $taxonomy );

$_type_prefix = get_term_meta( $tag->term_id, '_type', true );
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

      <?php if( get_term_meta( $tag->term_id, '_type', true ) ) { ?>
        <tr>
          <th><?php _e('Term Type'); ?></th>
          <td><input type="text" class="regular-text code" readonly="readonly" value="<?php echo get_term_meta( $tag->term_id, '_type', true ); ?>" /></td>
        </tr>
      <?php } ?>

      <?php if( get_term_meta( $tag->term_id, '_id', true ) ) { ?>
      <tr>
        <th><?php _e('Unique ID'); ?></th>
        <td><input type="text" class="regular-text code" readonly="readonly" value="<?php echo get_term_meta( $tag->term_id, '_id', true ); ?>" /></td>
      </tr>
      <?php } ?>

      <?php foreach( (array) $_taxonomy[ 'wpp_term_meta_fields' ] as $_meta_field ) {
        $_slug = str_replace( $taxonomy . '_', '', $_meta_field['slug'] );
        $_meta_slug = $_type_prefix . '-' . $_slug
        ?>
      <tr class="wpp-term-field-custom">
        <th><?php echo $_meta_field['label']; ?></th>
        <td>
          <input type="text" name="wpp_term_meta_fields[<?php echo $tag->term_id; ?>][<?php echo $_slug; ?>]" readonly="readonly" data-meta-slug="<?php echo $_meta_slug; ?>" data-field-type="<?php echo $_type_prefix; ?>" data-field-slug="<?php echo $_slug; ?>" class="regular-text code" value="<?php echo get_term_meta( $tag->term_id, $_meta_slug, true ); ?>" />
          <?php if( isset( $_meta_field['description'] ) ) { ?>
          <p class="description"><?php echo $_meta_field['description']; ?></p>
          <?php } ?>
        </td>
      </tr>
      <?php } ?>

      <?php if( get_term_meta( $tag->term_id, '_created', true ) ) { ?>
      <tr class="wpp-term-meta-row">
        <th><?php _e('Created'); ?></th>
        <td><input type="text" class="regular-text code" readonly="readonly" value="<?php echo human_time_diff( get_term_meta( $tag->term_id, '_created', true ) ); ?>" /></td>
      </tr>
      <?php } ?>

      <?php if( get_term_meta( $tag->term_id, '_updated', true ) ) { ?>
      <tr class="wpp-term-meta-row">
        <th><?php _e('Updated'); ?></th>
        <td><input type="text" class="regular-text code" readonly="readonly" value="<?php echo get_term_meta( $tag->term_id, '_updated', true ); ?>" /></td>
      </tr>
      <?php } ?>

    </table>

  </td>
</tr>