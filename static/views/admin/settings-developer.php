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
</div>