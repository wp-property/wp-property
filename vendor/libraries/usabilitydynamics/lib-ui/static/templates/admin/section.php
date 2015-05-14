<?php
/**
 * Settings page tab template
 */
?>
<h3 class="accordion-section-title hndle" tabindex="0" title="<?php echo esc_attr( $section[ 'name' ] ); ?>"><?php echo esc_html( $section[ 'name' ] ); ?></h3>
<div class="accordion-section-content">
  <div class="inside">
    <?php do_action( 'ud:ui:settings:view:section:' . $section[ 'id' ] . ':top'  ); ?>
    <?php foreach( $this->get_fields( 'section', $section[ 'id' ] ) as $field ) : ?>
      <?php $field->show(); ?>
    <?php endforeach; ?>
    <?php do_action( 'ud:ui:settings:view:section:' . $section[ 'id' ] . ':bottom'  ); ?>
  </div><!-- .inside -->
</div>