<?php
/**
 * Renders content for Meta Box 'WalkScore'
 *
 */
?>
<div class="walkscore-box">
  <?php if( empty( $walkscore ) ) : ?>
    <p><?php printf( __( 'Walk Score is not set. Please, be sure that current %s has valid address and you setup your %sWalk Score API key%s.', ud_get_wpp_walkscore('domain') ), \WPP_F::property_label(), '<a href="' . admin_url( 'edit.php?post_type=property&page=walkscore' ) . '">', '</a>' ); ?></p>
  <?php else: ?>
    <p><span class="score"><?php echo $walkscore; ?></span></p>
    <p><i><?php _e( 'Use the following shortcode to show Walk Score on Front End:', ud_get_wpp_walkscore('domain') ); ?><br/><code>[property_walkscore property_id=<?php echo $post->ID; ?>]</code></i></p>
    <p><i><?php printf( __( '<strong>Note</strong>, you can setup Walk Score view %shere%s', ud_get_wpp_walkscore('domain') ), '<a href="' . admin_url( 'edit.php?post_type=property&page=walkscore' ) . '#tab-walkscore">', '</a>' ); ?></i></p>
  <?php endif; ?>
</div>