<?php
/**
 * Settings page tab template
 */

?>
<div class="settings-tab <?php echo isset( $menu[ 'id' ] ) ? $menu[ 'id' ] : ''; ?>">
  <?php if( !empty( $menu[ 'desc' ] ) ) : ?>
    <div class="desc"><?php echo $menu[ 'desc' ]; ?></div>
  <?php endif; ?>
  <?php do_action( 'ud:ui:settings:view:tab:' . $menu[ 'id' ] . ':top'  ); ?>
  <div class="accordion-container">
    <ul class="outer-border">
      <?php do_action( 'ud:ui:settings:view:tab:' . $menu[ 'id' ] . ':accordion:top'  ); ?>
      <?php foreach( $this->get( 'sections', 'schema', array() ) as $section ) : ?>
        <?php if( !$menu || $menu[ 'id' ] == $section[ 'menu' ] ) : ?>
          <li class="accordion-section open" ><?php $this->get_template_part( 'section', array( 'section' => $section ) ); ?></li>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php do_action( 'ud:ui:settings:view:tab:' . $menu[ 'id' ] . ':accordion:bottom'  ); ?>
    </ul>
  </div>
  <?php do_action( 'ud:ui:settings:view:tab:' . $menu[ 'id' ] . ':bottom'  ); ?>
</div>