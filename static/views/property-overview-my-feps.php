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
$thumbnail_dimentions = WPP_F::get_image_dimensions($wpp_query['thumbnail_size'])
 ?>

<?php if ( have_properties() ): ?>

  <div class="<?php wpp_css('property_overview::row_view', 'wpp_row_view wpp_feps_row' ); ?>">

  <?php foreach ( (array)returned_properties( 'load_gallery=false' ) as $property ): /* the_post(); */ ?>
    <div class="<?php wpp_css('property_overview::property_div', 'property_div' ); ?>">
      <div class="<?php wpp_css('property_overview::left_column', 'wpp_overview_left_column' ); ?> " style="width:<?php echo $thumbnail_dimentions['width']+12; /* 12 is boubled image border */?>px; float:left; ">
        <?php property_overview_image(); ?>
      </div>
      <div class="<?php wpp_css('property_overview::right_column', 'wpp_overview_right_column' ); ?>" style="margin-left:<?php echo $thumbnail_dimentions['width']+12; /* 12 is boubled image border */?>px; ">
        <ul class="<?php wpp_css('property_overview::data', 'wpp_overview_data' ); ?>">
          <li class="<?php wpp_css('property_overview::property_title', 'property_title' ); ?>">
            <a <?php echo $in_new_window; ?> href="<?php echo $property['permalink']; ?>"><?php echo $property['post_title']; ?></a>
          </li>
          <?php echo @draw_stats('display=list&sort_by_groups=false', $property ); ?>
        </ul>

      </div>
      <div class="<?php wpp_css('property_overview::right_column', 'wpp_overview_feps_column' ); ?>" style="margin-left:<?php echo $thumbnail_dimentions['width']+12; /* 12 is boubled image border */?>px; ">
        <ul class="<?php wpp_css('property_overview::data', 'wpp_overview_data feps_action clearfix' ); ?>">
          <li class="<?php wpp_css('property_overview::post_status', 'post_status' ); ?>">
            <span class="title"><?php _e('Status', 'wpp'); ?></span>
            <span class="doubledot">:</span>
            <span class="value"><?php echo WPP_F::clear_post_status( $property['post_status'] ); ?></span>
          </li>
          <?php $class = !empty( $property[ FEPS_META_EXPIRED ] ) ? '' : 'empty'; ?>
          <?php $expired_time = !empty( $property[ FEPS_META_EXPIRED ] ) ? WPP_F::nice_time( $property[ FEPS_META_EXPIRED ], array('format'=>'date') ) : __('Not set', 'wpp'); ?>
          <li class="<?php wpp_css('property_overview::expired_date', "expired_date {$class}" ); ?>">
            <span class="title"><?php _e('Expiry Date', 'wpp'); ?></span>
            <span class="doubledot">:</span>
            <span class="value"><?php echo $expired_time; ?></span>
          </li>
          <?php if ( isset( $property[ FEPS_META_FORM ] ) && !empty( $forms[$property[ FEPS_META_FORM ]]['can_manage_feps'] ) && $forms[$property[ FEPS_META_FORM ]]['can_manage_feps'] == 'true' ) : ?>
          <li class="<?php wpp_css('property_overview::actions', 'actions' ); ?>">
            <ul class="clearfix">
              <li>
                <a class="<?php wpp_css('property_overview::button', 'button wpp_edit' ); ?>" href="<?php echo class_wpp_feps::get_edit_feps_permalink( $property['ID'], 'edit' ); ?>"><?php _e('Edit', 'wpp'); ?></a>
              </li>
              <li>
                <a class="<?php wpp_css('property_overview::button', 'button' ); ?>" href="<?php echo WPP_F::base_url( FEPS_VIEW_PAGE, array( 'feps' => $property['ID'], 'hash' => $property['wpp::feps::pending_hash'], 'action' => 'remove' ) ); ?>" onclick="return confirm('<?php _e('Are you sure?', 'wpp'); ?>')"><?php _e('Remove', 'wpp'); ?></a>
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


