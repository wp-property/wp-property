<?php

$wp_list_table = new UsabilityDynamics\WPP\Object_List_Table( "per_page=25" );

$wp_list_table->prepare_items( false, false );

$wp_list_table->data_tables_script();

?>

<div class="wp_wpp_overview_wrapper wrap">
  <h2>
    <?php echo $wp_properties[ 'labels' ][ 'all_items' ]; ?>
    <a href="<?php echo admin_url( 'post-new.php?post_type=property' ); ?>" class="button add-new-h2"><?php echo $wp_properties[ 'labels' ][ 'add_new_item' ]; ?></a>
  </h2>

  <form id="<?php echo $wp_list_table->table_scope; ?>-filter" action="#" method="POST">
      <div id="poststuff" class="<?php echo get_current_screen()->id; ?>_table metabox-holder <?php echo 2 == $screen_layout_columns ? 'has-right-sidebar' : ''; ?>">
      <div class="wp_wpp_sidebar inner-sidebar">
        <div class="meta-box-sortables ui-sortable">
          <?php do_meta_boxes( get_current_screen(), 'normal', $wp_list_table ); ?>
        </div>
      </div>
      <div id="post-body">
        <div id="post-body-content">
          <?php $wp_list_table->display(); ?>
        </div>
      </div>
      <br class="clear"/>
    </div><!-- /poststuff -->
  </form>

</div>