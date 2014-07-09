<div class="<?php wpp_css("feps-tab-template::wrapper",'wpp_feps_sponsored_listing'); ?>">
  <ul class="<?php wpp_css("feps-tab-template::ul",'wpp_feps_step_tabs'); ?>">
    <li class="<?php wpp_css("feps-tab-template::li",'wpp_feps_tab tab1'); ?> <?php echo $_REQUEST[ 'wpp_front_end_action' ]=='edit'?'active':'inactive'; ?>"><span class="<?php wpp_css("feps-tab-template::li::span",'wpp_feps_tab_value'); ?>"><?php _e('Information', 'wpp'); ?></span><span class="<?php wpp_css("feps-tab-template::li::arrow",'wpp_feps_arrow'); ?>"></span></li>
    <li class="<?php wpp_css("feps-tab-template::li",'wpp_feps_tab tab2'); ?> <?php echo $_REQUEST[ 'wpp_front_end_action' ]=='subscription_plan'?'active':'inactive'; ?>"><span class="<?php wpp_css("feps-tab-template::li::span",'wpp_feps_tab_value'); ?>"><?php _e('Subscription Plan', 'wpp'); ?></span><span class="<?php wpp_css("feps-tab-template::li::arrow",'wpp_feps_arrow'); ?>"></span></li>
    <li class="<?php wpp_css("feps-tab-template::li",'wpp_feps_tab tab3'); ?> <?php echo $_REQUEST[ 'wpp_front_end_action' ]=='checkout'?'active':'inactive'; ?>"><span class="<?php wpp_css("feps-tab-template::li::span",'wpp_feps_tab_value'); ?>"><?php _e('Checkout', 'wpp'); ?></span><span class="<?php wpp_css("feps-tab-template::li::arrow",'wpp_feps_arrow'); ?>"></span></li>
  </ul>
  <div class="<?php wpp_css("feps-tab-template::tab_content","wpp_feps_tab_content"); ?>">
    <?php echo $current; ?>
  </div>
</div>
