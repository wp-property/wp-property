<?php
/**
 * Settings 'Developer' Tab
 * Section 'Types'
 *
 * Adds taxonomies to 'Hidden Attributes' list
 */

if( !isset( $property_slug ) ) {
  return;
}

$taxonomies = ud_get_wpp_terms()->get( 'config.taxonomies', array() );
$inheritance = ud_get_wpp_terms()->get( 'config.inherited', array() );

?>
<?php foreach( $taxonomies as $k => $data ) : ?>
<li class="wpp_development_advanced_option">
  <input id="<?php echo $property_slug . "_" . $k; ?>_taxonomy_inheritance" <?php if( isset( $inheritance[ $property_slug ] ) && in_array( $k, $inheritance[ $property_slug ] ) ) echo " checked "; ?> type="checkbox" name="wpp_terms[inherited][<?php echo $property_slug; ?>][]" value="<?php echo $k; ?>"/>
  <label for="<?php echo $property_slug . "_" . $k; ?>_taxonomy_inheritance">
    <?php echo $data['label']; ?> (<?php _e( 'taxonomy', ud_get_wpp_terms('domain') ) ?>)
  </label>
</li>
<?php endforeach; ?>
