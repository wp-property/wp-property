<?php
/**
 * Settings 'Developer' Tab
 *
 */
global $wpdb, $wp_properties;

$wpp_inheritable_attributes = $wp_properties[ 'property_stats' ];

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
        if( !empty( $tab[ 'template' ] ) && file_exists( $tab[ 'template' ] ) ) {
          include( $tab[ 'template' ] );
        } else {
          _e( 'Invalid Template: File does not exist or template is undefined.', ud_get_wp_property()->domain );
        }
        ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>