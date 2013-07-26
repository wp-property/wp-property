<div class="<?php wpp_css("feps-tab-template::wrapper",'wpp_feps_sponsored_listing'); ?>">
  <ul class="<?php wpp_css("feps-tab-template::ul",'wpp_feps_step_tabs'); ?>">
    <li class="<?php wpp_css("feps-tab-template::li",'wpp_feps_tab tab1'); ?> <?php echo $feps_step==1?'active':'inactive'; ?>"><?php _e('Information', 'wpp'); ?></li>
    <li class="<?php wpp_css("feps-tab-template::li",'wpp_feps_tab tab2'); ?> <?php echo $feps_step==2?'active':'inactive'; ?>"><?php _e('Subscription Plan', 'wpp'); ?></li>
    <li class="<?php wpp_css("feps-tab-template::li",'wpp_feps_tab tab3'); ?> <?php echo $feps_step==3?'active':'inactive'; ?>"><?php _e('Checkout', 'wpp'); ?></li>
  </ul>
  <div class="<?php wpp_css("feps-tab-template::tab_content","wpp_feps_tab_content"); ?>">
    <?php echo $current; ?>
  </div>
</div>
