<?php
/**
 * Product Install Notice
 */
?>
<style>
  .ud-install-notice.updated {
    padding: 11px;
    position: relative;
    background-color: #F8FFF2;
  }
  .ud-install-notice-content {
    float: left;
    width: 70%;
  }
  .ud-install-notice-content {
    font-size: 16px;
    line-height: 21px;
    font-weight: 400;
    margin-bottom: 36px;
  }
  .ud-install-notice-dismiss {
    position: absolute;
    bottom: 11px;
    left: 11px;
    font-size: 14px;
  }
  .ud-install-notice-icon {
    float: right;
    text-align: right;
    width: 30%;
  }
  .ud-install-notice-icon img {
    max-width: 100px;
    max-height: 100px;
    display: inline-block;
  }
  .ud-install-notice-clear {
    display: block;
    clear: both;
    height: 1px;
    line-height: 1px;
    font-size: 1px;
    margin: -1px 0 0 0;
    padding: 0;
  }
</style>
<div class="<?php echo $this->slug; ?> ud-install-notice updated fade">
  <div class="ud-install-notice-content">
    <?php
    if( !empty( $content ) ) {
      echo $content;
    } else {
      printf( __( 'Thank you for using <a href="%s" target="_blank">Usability Dynamics</a>\' %s <b>%s</b>. Please, proceed to this <a href="%s">link</a> to see more details.' ),
        'https://www.usabilitydynamics.com',
        $type,
        $name,
        $dashboard_link
      );
    }
    do_action( 'ud::bootstrap::upgrade_notice::additional_info', $this->slug, $vars );
    ?>
    <div class="ud-install-notice-dismiss">
      <?php printf( __( '<a href="%s" class="">Dismiss this notice</a>' ), $dismiss_link ); ?>
      <?php if( !empty( $home_link ) ) : ?>
        | <?php printf( __( '<a href="%s" target="_blank" class="">%s\'s Home page</a>' ), $home_link, ucfirst( $type ) ); ?>
      <?php endif; ?>
    </div>
  </div>
  <?php if( !empty( $icon ) ) : ?>
    <div class="ud-install-notice-icon">
      <img src="<?php echo $icon ?>" alt="" />
    </div>
  <?php endif; ?>
  <div class="ud-install-notice-clear"></div>
</div>