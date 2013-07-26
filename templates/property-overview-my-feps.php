<?php
/**
 * WP-Property Overview Template - called by [property_overview] shortcode.
 * Attention: it must be used only by My FEPS Listings Page
 *
 * To customize this file, copy it into your theme directory, and the plugin will
 * automatically load your version.

 * @version 0.1
 * @author peshkov@UD
 * @package WP-Property
 *
 */

global $wp_properties;
$forms = $wp_properties['configuration']['feature_settings']['feps']['forms'];
 ?>

<?php if ( have_properties() ): ?>

  <div class="<?php wpp_css('property_overview::row_view', 'wpp_row_view wpp_feps_row' ); ?>">

  <?php foreach ( (array)returned_properties( 'load_gallery=false' ) as $property ): the_post(); ?>
    <div class="<?php wpp_css('property_overview::property_div', 'property_div' ); ?>">
      <div class="<?php wpp_css('property_overview::left_column', 'wpp_overview_left_column' ); ?>">
        <?php property_overview_image(); ?>
      </div>
      <div class="<?php wpp_css('property_overview::right_column', 'wpp_overview_right_column' ); ?>">
        <ul class="<?php wpp_css('property_overview::data', 'wpp_overview_data' ); ?>">
          <li class="<?php wpp_css('property_overview::property_title', 'property_title' ); ?>">
            <a <?php echo $in_new_window; ?> href="<?php echo $property['permalink']; ?>"><?php echo $property['post_title']; ?></a>
          </li>
          <li class="<?php wpp_css('property_overview::post_status', 'post_status' ); ?>">
            <span class="title"><?php _e('Status', 'wpp'); ?></span>
            <span class="doubledot">:</span>
            <span class="value"><?php echo WPP_F::clear_post_status( $property['post_status'] ); ?></span>
          </li>
          <?php $class = !empty( $property['wpp::feps::expired_time'] ) ? '' : 'empty'; ?>
          <?php $expired_time = !empty( $property['wpp::feps::expired_time'] ) ? WPP_F::nice_time( $property['wpp::feps::expired_time'], array('format'=>'date') ) : __('Not set', 'wpp'); ?>
          <li class="<?php wpp_css('property_overview::expired_date', "expired_date {$class}" ); ?>">
            <span class="title"><?php _e('Expiry Date', 'wpp'); ?></span>
            <span class="doubledot">:</span>
            <span class="value"><?php echo $expired_time; ?></span>
          </li>
          <?php if ( isset( $property['wpp::feps::form_id'] ) && !empty( $forms[$property['wpp::feps::form_id']]['can_manage_feps'] ) && $forms[$property['wpp::feps::form_id']]['can_manage_feps'] == 'true' ) : ?>
          <li class="<?php wpp_css('property_overview::actions', 'actions' ); ?>">
            <ul>
              <li>
                <a class="<?php wpp_css('property_overview::button', 'denali-button button wpp_edit' ); ?>" href="<?php echo WPP_F::base_url( FEPS_EDIT_PAGE, array( 'feps' => $property['ID'] ) ); ?>"><?php _e('Edit', 'wpp'); ?></a>
              </li>
              <li>
                <a class="<?php wpp_css('property_overview::button', 'denali-button button' ); ?>" href="<?php echo WPP_F::base_url( FEPS_VIEW_PAGE, array( 'feps' => $property['ID'], 'hash' => $property['wpp::feps::pending_hash'], 'action' => 'remove' ) ); ?>"><?php _e('Remove', 'wpp'); ?></a>
              </li>
            </ul>
          </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  <?php endforeach; ?>

  </div>

<?php else: ?>

<?php endif; ?>


