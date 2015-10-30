<?php
/**
 * Created by PhpStorm.
 * User: maxim
 * Date: 10/30/15
 * Time: 12:42
 */
?>
<div class="properties_pagination <?php echo $settings[ 'class' ]; ?> wpp_slider_pagination" id="properties_pagination_<?php echo $unique_hash; ?>">
          <div class="wpp_pagination_slider_status">
        <span class="wpp_property_results_options">
          <?php if ( $hide_count != 'true' ) {
  $wpp_property_results = '<span class="wpp_property_results">';
  $wpp_property_results .= ( $properties[ 'total' ] > 0 ? \WPP_F::format_numeric( $properties[ 'total' ] ) : __( 'None', ud_get_wp_property()->domain ) );
  $wpp_property_results .= __( ' found.', ud_get_wp_property()->domain );
  echo apply_filters( 'wpp::wpp_draw_pagination::wpp_property_results', $wpp_property_results, array( 'properties' => $properties, 'settings' => $settings ) );
  ?>
<?php } ?>
<?php if ( !empty( $use_pagination ) ) { ?>
  <?php _e( 'Viewing page', ud_get_wp_property()->domain ); ?>
  <span class="wpp_current_page_count">1</span> <?php _e( 'of', ud_get_wp_property()->domain ); ?>
  <span class="wpp_total_page_count"><?php echo $pages; ?></span>.
<?php } ?>
</span>
<?php if ( $sortable_attrs ) { ?>
  <span class="wpp_sorter_options"><span class="wpp_sort_by_text"><?php echo $settings[ 'sort_by_text' ]; ?></span>
    <?php
    if ( $settings[ 'sorter_type' ] == 'buttons' ) {
      ?>
      <?php foreach ( $sortable_attrs as $slug => $label ) { ?>
        <span class="wpp_sortable_link <?php echo( $sort_by == $slug ? 'wpp_sorted_element' : '' ); ?> label label-info" sort_order="<?php echo $sort_order ?>" sort_slug="<?php echo $slug; ?>"><?php echo $label; ?></span>
      <?php }
    } elseif ( $settings[ 'sorter_type' ] == 'dropdown' ) { ?>
      <select class="wpp_sortable_dropdown sort_by label-info" name="sort_by">
        <?php foreach ( $sortable_attrs as $slug => $label ) { ?>
          <option <?php echo( $sort_by == $slug ? 'class="wpp_sorted_element" selected="true"' : '' ); ?> sort_slug="<?php echo $slug; ?>" value="<?php echo $slug; ?>"><?php echo $label; ?></option>
        <?php } ?>
      </select>
      <?php /* <span class="wpp_overview_sorter sort_order <?php echo $sort_order ?> label label-info" sort_order="<?php echo $sort_order ?>"></span> */ ?>
      <?php
    } else {
      do_action( 'wpp_custom_sorter', array( 'settings' => $settings, 'wpp_query' => $wpp_query, 'sorter_type' => $settings[ 'sorter_type' ] ) );
    }
    ?>
        </span>
<?php } ?>
<div class="clear"></div>
</div>
<?php if ( !empty( $use_pagination ) ) { ?>
  <div class="wpp_pagination_slider_wrapper">
    <div class="wpp_pagination_back wpp_pagination_button"><?php _e( 'Prev', ud_get_wp_property()->domain ); ?></div>
    <div class="wpp_pagination_forward wpp_pagination_button"><?php _e( 'Next', ud_get_wp_property()->domain ); ?></div>
    <div class="wpp_pagination_slider"></div>
  </div>
<?php } ?>
</div>
<div class="ajax_loader"></div>