<?php
/**
 * XMLI Schedules Overview page
 */

global $wp_properties, $wpdb, $wpp_property_import, $current_screen, $wp_messages;

if( isset( $_REQUEST[ 'message' ] ) ) {

  switch( $_REQUEST[ 'message' ] ) {

    case 'imported':
      $wp_messages[ 'notice' ][ ] = __( 'Schedule imported from file.', ud_get_wpp_importer()->domain );
      break;

  }
}

?>
<style type="text/css">
  body.property_page_wpp_property_import #wpp_property_import_step {
    background: none repeat scroll 0 0 #FFFADE;
    border: 1px solid #D7D3BC;
    margin: 10px 0;
    padding: 10px;
  }

  body.property_page_wpp_property_import #wpp_property_import_setup .wpp_i_preview_raw_data_result {
    margin-left: 8px;
  }

  body.property_page_wpp_property_import #wpp_property_import_setup .wpp_i_error_text {
    background: none repeat scroll 0 0 #924848;
    border-radius: 3px 3px 3px 3px;
    color: #FFFCFC;
    max-width: none;
    padding: 2px 7px;
  }

  body.property_page_wpp_property_import #wpp_property_import_setup .wpp_i_ajax_message.wpp_i_error_text {
    margin-top: 2px;
  }

  body.property_page_wpp_property_import #wpp_property_import_setup .wpp_i_close_preview.wpp_link {
    float: right;
    line-height: 30px;
    margin-right: 7px;
  }

</style>

<script type="text/javascript">

  <?php

   // Load list of all usable attributes into global JS array

   $get_total_attribute_array = WPP_F::get_total_attribute_array();
   if( is_array( $get_total_attribute_array ) ) {
     $get_total_attribute_array = array_keys( $get_total_attribute_array );
     echo "var wpp_attributes = ['" . implode( "','", $get_total_attribute_array ) . "'];";
   }

   ?>

</script>

<div class="wrap">
  <h2><?php _e( 'Property Importer', ud_get_wpp_importer()->domain ); ?>
    <a id="wpp_property_import_add_import" class="button add-new-h2" href="#add_new_schedule"><?php _e( 'Add New', ud_get_wpp_importer()->domain ); ?></a>
    <span class="wpp_xi_loader"></span>
  </h2>

  <?php if( isset( $wp_messages[ 'error' ] ) && $wp_messages[ 'error' ] ): ?>
    <div class="error">
      <?php foreach ($wp_messages[ 'error' ] as $error_message): ?>
      <p><?php echo $error_message; ?>
        <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if( isset( $wp_messages[ 'notice' ] ) && $wp_messages[ 'notice' ] ): ?>
    <div class="updated fade">
      <?php foreach ($wp_messages[ 'notice' ] as $notice_message): ?>
      <p><?php echo $notice_message; ?>
        <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div id="wpp_property_import_ajax"></div>

  <?php if (!empty( $wpp_property_import[ 'schedules' ] )):
    $cron_path = ud_get_wpp_importer()->path( 'cron.php', 'dir' );
    ?>

    <?php if( count( $wpp_property_import[ 'schedules' ] ) > 1 ) { ?>
    <ul class="subsubsub wpp_import_overview_page_element">
      <li class="all"><?php _e( 'Sort by:', ud_get_wpp_importer()->domain ); ?></a> </li>
      <li class="wpp_i_sort_schedules" sort_direction="ASC" sort_by="lastrun"><a href="#"><?php _e( 'Last Run', ud_get_wpp_importer()->domain ); ?></a> |</li>
      <li class="wpp_i_sort_schedules" sort_direction="ASC" sort_by="created"><a href="#"><?php _e( 'Created Properties', ud_get_wpp_importer()->domain ); ?> </a> |</li>
      <li class="wpp_i_sort_schedules" sort_direction="ASC" sort_by="updated"><a href="#"><?php _e( 'Updated Properties', ud_get_wpp_importer()->domain ); ?> </a> | </li>
      <li class="wpp_i_sort_schedules" sort_direction="ASC" sort_by="total_properties"><a href="#"><?php _e( 'Total Properties', ud_get_wpp_importer()->domain ); ?> </a> | </li>
      <li class="wpp_i_sort_schedules" sort_direction="ASC" sort_by="limit"><a href="#"><?php _e( 'Limit', ud_get_wpp_importer()->domain ); ?></a></li>
    </ul>
  <?php } ?>



    <table id="wpp_property_import_overview" class="widefat wpp_import_overview_page_element">
      <thead>
      <tr>
        <th><?php _e( "Saved Import Schedules", ud_get_wpp_importer()->domain ); ?></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach( $wpp_property_import[ 'schedules' ] as $sch_id => $sch ):

        if( empty( $sch_id ) ) {
          continue;
        }

        $this_row_data = array();

        if( !empty( $sch[ 'lastrun' ][ 'time' ] ) ) {
          $vital_stats[ $sch_id ][ ] = __( 'Last run ', ud_get_wpp_importer()->domain ) . human_time_diff( $sch[ 'lastrun' ][ 'time' ] ) . __( ' ago.', ud_get_wpp_importer()->domain );
          $this_row_data[ ] = "lastrun=\"{$sch['lastrun']['time']}\" ";
        }

        if( !empty( $sch[ 'lastrun' ][ 'u' ] ) ) {
          $vital_stats[ $sch_id ][ ] = __( 'Updated ', ud_get_wpp_importer()->domain ) . $sch[ 'lastrun' ][ 'u' ] . __( ' objects.', ud_get_wpp_importer()->domain );
          $this_row_data[ ] = "updated=\"{$sch['lastrun']['u']}\" ";
        }

        if( !empty( $sch[ 'lastrun' ][ 'c' ] ) ) {
          $vital_stats[ $sch_id ][ ] = __( 'Created ', ud_get_wpp_importer()->domain ) . $sch[ 'lastrun' ][ 'c' ] . __( ' objects.', ud_get_wpp_importer()->domain );
          $this_row_data[ ] = "created=\"{$sch['lastrun']['c']}\" ";
        }

        if( !empty( $sch[ 'limit_properties' ] ) ) {
          $vital_stats[ $sch_id ][ ] = __( 'Limited to ', ud_get_wpp_importer()->domain ) . $sch[ 'limit_properties' ] . __( ' objects.', ud_get_wpp_importer()->domain );
          $this_row_data[ ] = "limit=\"{$sch['limit_properties']}\" ";
        }

        if( $total_properties = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( ID ) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE post_type = 'property' AND meta_key = 'wpp_import_schedule_id' and meta_value = %s ", $sch_id ) ) ) {
          $vital_stats[ $sch_id ][ ] = __( 'Total Properties: ', ud_get_wpp_importer()->domain ) . WPP_F::format_numeric( $total_properties );
          $this_row_data[ ] = "total_properties=\"{$total_properties}\" ";
        } else {
          $total_properties = false;
        }

        $alt_cron = '';
        if( isset( $sch[ 'alt_cron_enabled' ] ) && $sch[ 'alt_cron_enabled' ] == 'true' ) {
          $alt_cron = __( 'Enabled', ud_get_wpp_importer()->domain );
        } else {
          $alt_cron = __( 'Disabled', ud_get_wpp_importer()->domain );
        }

        ?>
        <tr <?php echo implode( '', $this_row_data ); ?> class="wpp_i_schedule_row" schedule_id="<?php echo $sch_id; ?>" import_title="<?php echo esc_attr( $sch[ 'name' ] ); ?>">
          <td class="post-title column-title">
            <ul>
              <li>
                <strong><a href="#<?php echo $sch_id; ?>" schedule_id="<?php echo $sch_id; ?>" class="wpp_property_import_edit_report"><?php echo $sch[ 'name' ]; ?></a></strong>
              </li>
              <li><?php _e( 'Source URL:', ud_get_wpp_importer()->domain ); ?>
                <span class="wpp_i_overview_special_data"><?php echo $sch[ 'url' ]; ?></span>
              </li>


              <?php if( isset( $sch['source_type'] ) && $sch['source_type']  === 'rets' ) { ?>

              <li><?php _e( 'RETS Classification:', ud_get_wpp_importer()->domain ); ?>
                <span class="wpp_i_overview_special_data"><?php echo $sch[ 'rets_resource' ]; ?> / <?php echo $sch[ 'rets_class' ]; ?></span>
              </li>

              <li><?php _e( 'RETS Query:', ud_get_wpp_importer()->domain ); ?>
                <span class="wpp_i_overview_special_data"><?php echo $sch[ 'rets_query' ]; ?></span>
              </li>

              <?php } ?>

              <li>
                <?php _e( 'Cron Command:', ud_get_wpp_importer()->domain ); ?>
                <?php if( !empty( $sch[ 'hash' ] ) ) : ?>
                  <span class="wpp_i_overview_special_data">php -q <?php echo $cron_path . ' do_xml_import ' . $sch[ 'hash' ] . ( is_multisite() ? " " . parse_url( site_url(), PHP_URL_HOST ) . parse_url( site_url(), PHP_URL_PATH ) : '' ); ?></span>
                <?php else: ?>
                  <span class="wpp_i_overview_special_data"><?php _e( 'There is an issue with your schedule. Please, re-save it.', ud_get_wpp_importer()->domain ); ?></span>
                <?php endif; ?>
              </li>

              <?php if( isset( $sch[ 'alt_cron_enabled' ] ) && $sch[ 'alt_cron_enabled' ] === 'true' ) {?>
              <li>
                <?php _e( 'Alternative Cron:', ud_get_wpp_importer()->domain ); ?> <span class="wpp_i_overview_special_data"><?php echo $alt_cron; ?></span>
              </li>
              <?php } ?>

              <?php if( !empty( $vital_stats[ $sch_id ] ) && is_array( $vital_stats[ $sch_id ] ) ) : ?>
                <li>
                  <span class="wpp_i_overview_special_data"><?php echo implode( ' | ', $vital_stats[ $sch_id ] ); ?></span>
                </li>
              <?php endif; ?>
              <li>
                <a href="#<?php echo $sch_id; ?>" schedule_id="<?php echo $sch_id; ?>" class="wpp_property_import_edit_report"><?php _e( 'Edit', ud_get_wpp_importer()->domain ); ?></a> |
                <a href="#" schedule_id="<?php echo $sch_id; ?>" class="wpp_property_import_delete_report"><?php _e( 'Delete', ud_get_wpp_importer()->domain ); ?></a> |
                <a href="<?php echo site_url() . "/?wpp_schedule_import=" . $sch[ 'hash' ] . "&echo_log=true"; ?>" target="_blank"/><?php _e( 'Run Import in Browser', ud_get_wpp_importer()->domain ); ?></a>
                |
                <a href="<?php echo wp_nonce_url( "edit.php?post_type=property&page=wpp_property_import&wpp_action=download-wpp-import-schedule&schedule_id={$sch_id}", 'download-wpp-import-schedule' ); ?>" class=""><?php _e( 'Save to File', ud_get_wpp_importer()->domain ); ?></a> |
                <?php if( $total_properties > 0 ) { ?>
                  <a href="#" schedule_id="<?php echo $sch_id; ?>" class="wppi_delete_all_feed_properties"><?php _e( 'Delete All Properties', ud_get_wpp_importer()->domain ); ?></a>
                <?php } ?>
                <span class="wpp_loader"></span>
                <div class="run_progressbar" style="width:500px"></div>
              </li>
            </ul>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
  <p class="wpp_import_overview_page_element"><?php echo __( 'You do not have any saved schedules. Create one now.', ud_get_wpp_importer()->domain ); ?>
    <?php endif; ?>


  <div class="wpp_import_import_schedule wpp_import_overview_page_element">
    <form method="post" action="<?php echo admin_url( 'edit.php?post_type=property&page=wpp_property_import' ); ?>" enctype="multipart/form-data"/>

    <input type="hidden" name="wpp_action" value="import_wpp_schedule"/>
    <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'wpp_import_import_schedule' ); ?>"/>
    <?php _e( "Import Schedule", ud_get_wpp_importer()->domain ); ?>
    : <input name="wpp_import[import_schedule]" type="file"/>

    <input type="submit" value="<?php _e( 'Upload File', ud_get_wpp_importer()->domain ); ?>" class="btn"/>
    </form>
  </div>

</div>