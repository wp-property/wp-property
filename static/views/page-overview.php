<?php
/**
 * Name: Listing Overview
 * Group: Pages
 * Description: Listing filter.
 *
 * @copyright  2012-2014 Usability Dyanmics, Inc.
 */
global $wp_properties, $screen_layout_columns;

if( class_exists( 'UsabilityDynamics\WPP\List_Tables\Data_Table' ) ) {
  $wp_list_table = new UsabilityDynamics\WPP\List_Tables\Property_Table("per_page=25");
  $wp_list_table->prepare_items(false, false);
  $wp_list_table->data_tables_script();
} else {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php' );
  $wp_list_table = new WP_Posts_List_Table();
  $wp_list_table->prepare_items(false, false);
}

?>
<div class="wp_wpp_overview_wrapper wrap">
  <h2><?php echo $wp_properties['labels']['all_items']; ?> <a href="<?php echo admin_url('post-new.php?post_type=property'); ?>" class="button add-new-h2"><?php echo $wp_properties['labels']['add_new_item']; ?></a></h2>

  <form id="<?php echo $wp_list_table->table_scope; ?>-filter" action="#" method="POST">

    <div id="poststuff" class="<?php echo get_current_screen()->id; ?>_table metabox-holder <?php echo 2 == $screen_layout_columns ? 'has-right-sidebar' : ''; ?>">
      <div class="wp_wpp_sidebar inner-sidebar">
        <div class="meta-box-sortables ui-sortable">
          <?php do_meta_boxes(get_current_screen()->id, 'normal', $wp_list_table); ?>
        </div>
      </div>
      <div id="post-body">
        <div id="post-body-content">
          <?php $wp_list_table->display(); ?>
        </div> <?php /* .post-body-content */ ?>
      </div> <?php /* .post-body */ ?>
      <br class="clear" />
    </div><!-- /poststuff -->
  </form>
</div> <?php /* .wp_wpp_overview_wrapper */ ?>