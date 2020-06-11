<?php
/**
 * Bulk Score Request UI
 *
 * Adds UI to re-generate Walk Score meta for all properties
 *
 * peshkov@UD
 */
?>
<div id="ws-bulk-request">
  <p><?php printf( __( 'By default Walk Score is being requested on %s saving. But if you just installed the current plugin or some of your %s do not have Walk Scores because of different reasons, you are able to generate them right now.' ), WPP_F::property_label(), WPP_F::property_label( 'plural' ) ); ?></p>
  <p><?php printf( __( 'Generate Walk Scores for all %s which do not have it: %s %s %s', ud_get_wpp_walkscore( 'domain' ) ),  WPP_F::property_label( 'plural' ), '<button class="button" id="ws-bulk-request-button">' . __( 'Generate Walk Scores', ud_get_wpp_walkscore( 'domain' ) ) . '</button>', '<img id="ws-ajax-spinner" src="' . ud_get_wpp_walkscore()->path( 'static/images/ajax_loader.gif', 'url' ) . '" alt="" title="" />', '<button class="button" id="ws-close-log-button">' . __( 'Close Log Data', ud_get_wpp_walkscore( 'domain' ) ) . '</button>' ); ?></p>
  <p><?php _e( '<strong>Note</strong>, if you are using Free API Version you have only 5,000 calls per day.', ud_get_wpp_walkscore( 'domain' ) ); ?></p>
  <pre id="ws-response-logs" class="wpp_class_pre"></pre>
</div>