<?php
/**
 * [property_walkscore] template
 *
 * To modify it, copy it to your theme's root.
 */
?>
<div class="walkscore-from-api">
  <?php if ( isset( $ws_view ) && $ws_view == 'badge' ) : ?>
    <a class="score-link" href="<?php echo $link ?>" target="_blank">
        <img alt="<?php _e( 'What\'s your Walk Score?', ud_get_wpp_walkscore('domain') ); ?>" src="//www.walkscore.com/badge/walk/score/<?php echo $walkscore; ?>.svg">
    </a>
  <?php elseif ( isset( $ws_view ) && $ws_view == 'icon' ) : ?>
    <a class="score-link" href="<?php echo $link ?>" target="_blank">
      <img width="120" border="0" height="19" alt="<?php _e( 'What\'s your Walk Score?', ud_get_wpp_walkscore('domain') ); ?>" src="<?php echo $walkscore_data[ 'logo_url' ]; ?>">
    </a>
    <strong><a class="scoretext up2 score-link" href="<?php ?>" target="_blank"><?php echo $walkscore; ?></a></strong>
    <span class="ws_info"><a target="_blank" href="<?php echo $link ?>">
      <img width="13" height="13" src="<?php echo $walkscore_data[ 'more_info_icon' ]; ?>">
    </a></span>
  <?php else : ?>
    <p><a class="score-link" href="<?php echo $link ?>" target="_blank"><?php _e( 'Walk Score', ud_get_wpp_walkscore('domain') ); ?></a><sup>&reg;</sup>:<a class="scoretext score-link" href="<?php echo $link ?>" target="_blank"><?php echo $walkscore; ?></a></p>
  <?php endif; ?>
</div>



