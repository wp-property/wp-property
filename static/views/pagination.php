<?php
/**
 * Property Overview Pagination
 * Main template
 *
 * To modify template, copy the file to root of your theme.
 */
?>
<div class="properties_pagination <?php echo $settings[ 'class' ]; ?> wpp_slider_pagination type-<?php echo $settings[ 'type' ]; ?>">

  <div class="wpp_pagination_slider_status">
    <span class="wpp_property_results_options">
      <?php if ( $hide_count != 'true' ) {
        $wpp_property_results = '<span class="wpp_property_results">';
        $wpp_property_results .= ( $properties[ 'total' ] > 0 ? \WPP_F::format_numeric( $properties[ 'total' ] ) : __( 'None', ud_get_wp_property()->domain ) );
        $wpp_property_results .= __( ' found.', ud_get_wp_property()->domain );
        echo apply_filters( 'wpp::wpp_draw_pagination::wpp_property_results', $wpp_property_results, array( 'properties' => $properties, 'settings' => $settings ) );
      } ?>
      <?php if ( !empty( $use_pagination ) ) { ?>
        <?php _e( 'Viewing page', ud_get_wp_property()->domain ); ?>
        <span class="wpp_current_page_count">1</span> <?php _e( 'of', ud_get_wp_property()->domain ); ?>
        <span class="wpp_total_page_count"><?php echo $pages; ?></span>.
      <?php } ?>
    </span>
    <?php if ( $sortable_attrs ) { ?>
      <span class="wpp_sorter_options"><span class="wpp_sort_by_text"><?php echo $settings[ 'sort_by_text' ]; ?></span>
        <?php if ( $settings[ 'sorter_type' ] == 'buttons' ) { ?>
          <?php foreach ( $sortable_attrs as $slug => $label ) { ?>
            <span class="wpp_sortable_link <?php echo( $sort_by == $slug ? 'wpp_sorted_element' : '' ); ?> label label-info" sort_order="<?php echo $sort_order ?>" sort_slug="<?php echo $slug; ?>"><?php echo $label; ?></span>
          <?php } ?>
        <?php } elseif ( $settings[ 'sorter_type' ] == 'dropdown' ) { ?>
          <select class="wpp_sortable_dropdown sort_by label-info" name="sort_by">
            <?php foreach ( $sortable_attrs as $slug => $label ) { ?>
              <option <?php echo( $sort_by == $slug ? 'class="wpp_sorted_element" selected="true"' : '' ); ?> sort_slug="<?php echo $slug; ?>" value="<?php echo $slug; ?>"><?php echo $label; ?></option>
            <?php } ?>
          </select>
        <?php } else { ?>
          <?php do_action( 'wpp_custom_sorter', array( 'settings' => $settings, 'wpp_query' => $wpp_query, 'sorter_type' => $settings[ 'sorter_type' ] ) ); ?>
        <?php } ?>
        </span>
    <?php } ?>
    <div class="clear"></div>
  </div>

  <?php /* Render Pagination Template based on pagination type */
  if ( !empty( $use_pagination ) ) {
    $template = self::get_pagination_template_based_on_type( $settings[ 'type' ] );
    if( file_exists( $template ) ) {
      include( $template );
    } else {
      _e( 'No Pagination Template Found', ud_get_wp_property('domain') );
    }
  }
  ?>
</div>
<div class="ajax_loader"></div>