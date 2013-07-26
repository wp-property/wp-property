<?php
  global $current_screen;
  include WPP_Path . 'core/ui/class_wpp_object_list_table.php';

  $wp_list_table = new WPP_Object_List_Table("per_page=25");
 
  $wp_list_table->prepare_items(false, false);

  $wp_list_table->data_tables_script();
  
?>

<div class="wp_wpp_overview_wrapper wrap">
    <?php screen_icon(); ?>
    <h2><?php echo $wp_properties['labels']['all_items']; ?> <a href="<?php echo admin_url('post-new.php?post_type=property'); ?>" class="button add-new-h2"><?php echo $wp_properties['labels']['add_new_item']; ?></a></h2>
    <?php //WPI_Functions::print_messages(); ?>
    
    <div id="poststuff" class="<?php echo $current_screen->id; ?>_table metabox-holder has-right-sidebar">
    <form id="<?php echo $wp_list_table->table_scope; ?>-filter" action="#" method="POST">

    <div class="wp_wpp_sidebar inner-sidebar">
      <div class="meta-box-sortables ui-sortable">
        <?php do_meta_boxes($current_screen->id, 'normal', $wp_list_table); ?>
      </div>
    </div>

    <div id="post-body">
      <div id="post-body-content">
        <?php $wp_list_table->display(); ?>
      </div> <?php /* .post-body-content */ ?>
    </div> <?php /* .post-body */ ?>

    </form>
    <br class="clear" />

  </div> <?php /* #poststuff */ ?>
</div> <?php /* .wp_wpi_overview_wrapper */ ?>