<?php
/**
 * Admin Notice
 */
?>
<style>
  .ud-server-notice.updated {
    padding: 11px;
    position: relative;
    background-color: #F8FFF2;
  }
  .ud-server-notice-content {
    font-size: 16px;
    line-height: 21px;
    font-weight: 400;
    margin-bottom: 36px;
    float: left;
    width: 70%;
  }
  .ud-server-notice-dismiss {
    position: absolute;
    bottom: 11px;
    left: 11px;
    font-size: 14px;
  }
  .ud-server-notice-icon {
    float: right;
    text-align: right;
    width: 30%;
  }
  .ud-server-notice-icon img {
    max-width: 100px;
    max-height: 100px;
    display: inline-block;
  }
  .ud-server-notice-clear {
    display: block;
    clear: both;
    height: 1px;
    line-height: 1px;
    font-size: 1px;
    margin: -1px 0 0 0;
    padding: 0;
  }
</style>
<div class="ud-server-notice updated fade">
  <div class="ud-server-notice-content">
    <?php if( !empty( $notice ) ) echo $notice; ?>
    <?php if( !empty( $dismiss_url ) ) : ?>
      <div class="ud-server-notice-dismiss">
        <?php printf( __( '<a href="%s" class="">Dismiss this notice</a>' ), $dismiss_url ); ?>
      </div>
    <?php endif; ?>
  </div>
  <?php if( !empty( $icon ) ) : ?>
    <div class="ud-server-notice-icon">
      <a href="https://www.usabilitydynamics.com"><img src="<?php echo $icon ?>" alt="" /></a>
    </div>
  <?php endif; ?>
  <div class="ud-server-notice-clear"></div>
</div>