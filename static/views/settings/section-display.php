<?php
/**
 * Name: Display
 * Group: Settings
 *
 */
?>
<table class="form-table">

  <tr>
    <th><?php _e( 'Image Sizes', 'wpp' ); ?></th>
    <td>
      <p><?php _e( 'Image sizes used throughout the plugin.', 'wpp' ); ?> </p>

        <table id="wpp_image_sizes" class="ud_ui_dynamic_table widefat">
          <thead>
            <tr>
              <th><?php _e( 'Slug', 'wpp' ); ?></th>
              <th><?php _e( 'Width', 'wpp' ); ?></th>
              <th><?php _e( 'Height', 'wpp' ); ?></th>
              <th>&nbsp;</th>
            </tr>
          </thead>
          <tbody>
        <?php
        $wpp_image_sizes = $wp_properties[ 'image_sizes' ];

        foreach( get_intermediate_image_sizes() as $slug ):

          $slug = trim( $slug );

          // We return all, including images with zero sizes, to avoid default data overriding what we save
          $image_dimensions = WPP_F::image_sizes( $slug, "return_all=true" );

          // Skip images w/o dimensions
          if( !$image_dimensions )
            continue;

          // Disable if WP not a WPP image size
          if( @!is_array( $wpp_image_sizes[ $slug ] ) )
            $disabled = true;
          else
            $disabled = false;

          if( !$disabled ):
            ?>
            <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>">
            <td class="wpp_slug">
              <input class="slug_setter slug wpp_slug_can_be_empty" type="text" value="<?php echo $slug; ?>"/>
            </td>
            <td class="wpp_width">
              <input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][width]" value="<?php echo $image_dimensions[ 'width' ]; ?>"/>
            </td>
            <td class="wpp_height">
              <input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][height]" value="<?php echo $image_dimensions[ 'height' ]; ?>"/>
            </td>
            <td><span class="wpp_delete_row wpp_link"><?php _e( 'Delete', 'wpp' ) ?></span></td>
          </tr>

          <?php else: ?>
            <tr>
            <td>
              <div class="wpp_permanent_image"><?php echo $slug; ?></div>
            </td>
            <td>
              <div class="wpp_permanent_image"><?php echo $image_dimensions[ 'width' ]; ?></div>
            </td>
            <td>
              <div class="wpp_permanent_image"><?php echo $image_dimensions[ 'height' ]; ?></div>
            </td>
            <td>&nbsp;</td>
          </tr>

          <?php endif; ?>


        <?php endforeach; ?>

          </tbody>
          <tfoot>
            <tr>
              <td colspan='4'><input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', 'wpp' ) ?>"/></td>
            </tr>
          </tfoot>
        </table>

     </td>
  </tr>

  <tr>
    <th><?php _e( 'Overview Shortcode', 'wpp' ) ?></th>
    <td>
      <p>
      <?php printf( __( 'These are the settings for the [property_overview] shortcode.  The shortcode displays a list of all building / root %1s.<br />The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property-overview.php</b> file.  To avoid losing your changes during updates, create a <b>property-overview.php</b> file in your template directory, which will be automatically loaded.', 'wpp' ), WPP_F::property_label( 'plural' ) ); ?>
      </p>
      <ul>
        <li><?php _e( 'Thumbnail size:', 'wpp' ) ?> <?php WPP_F::image_sizes_dropdown( "name=wpp_settings[configuration][property_overview][thumbnail_size]&selected=" . $wp_properties[ 'configuration' ][ 'property_overview' ][ 'thumbnail_size' ] ); ?></li>
        <li><?php echo WPP_F::checkbox( 'name=wpp_settings[configuration][property_overview][show_children]&label=' . sprintf( __( 'Show children %1s.', 'wpp' ), $object_label[ 'plural' ] ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] ); ?></li>
        <li><?php echo WPP_F::checkbox( 'name=wpp_settings[configuration][property_overview][fancybox_preview]&label=' . sprintf( __( 'Show larger image of %1s when image is clicked using fancybox.', 'wpp' ), $object_label[ 'singular' ] ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'fancybox_preview' ] ); ?></li>
        <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][bottom_insert_pagenation]&label=" . __( 'Show pagination on bottom of results.', 'wpp' ), $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] ); ?></li>
        <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][property_overview][add_sort_by_title]&label=" . sprintf( __( 'Add sorting by %1s\'s title.', 'wpp' ), $object_label[ 'singular' ] ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'add_sort_by_title' ] ); ?></li>
        <?php do_action( 'wpp::settings::display::overview_shortcode' ); ?>
      </ul>

    </td>
  </tr>

  <tr>
    <th><?php printf( __( '%1s Page', 'wpp' ), WPP_F::property_label( 'singular' ) ); ?></th>
    <td>
      <p><?php _e( 'The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property.php</b> file.  To avoid losing your changes during updates, create a <b>property.php</b> file in your template directory, which will be automatically loaded.', 'wpp' ) ?>
      <ul>
        <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][property_overview][sort_stats_by_groups]&label=" . sprintf( __( 'Sort %1s stats by groups.', 'wpp' ), WPP_F::property_label( 'singular' ) ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'sort_stats_by_groups' ] ); ?></li>
        <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][property_overview][show_true_as_image]&label=" . sprintf( __( 'Show Checkboxed Image instead of "%s" and hide "%s" for %s/%s values', 'wpp' ), __( 'Yes', 'wpp' ), __( 'No', 'wpp' ), __( 'Yes', 'wpp' ), __( 'No', 'wpp' ) ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_true_as_image' ] ); ?></li>
        <?php do_action( 'wpp_settings_page_property_page' ); ?>
      </ul>

    </td>
  </tr>

  <tr>
    <th><?php _e( 'Google Maps', 'wpp' ) ?></th>
    <td>

      <ul>
        <li><?php _e( 'Map Thumbnail Size:', 'wpp' ); ?> <?php WPP_F::image_sizes_dropdown( "name=wpp_settings[configuration][single_property_view][map_image_type]&selected=" . $wp_properties[ 'configuration' ][ 'single_property_view' ][ 'map_image_type' ] ); ?></li>
        <li><?php _e( 'Map Zoom Level:', 'wpp' ); ?> <?php echo WPP_F::input( "name=wpp_settings[configuration][gm_zoom_level]&style=width: 30px;", $wp_properties[ 'configuration' ][ 'gm_zoom_level' ] ); ?></li>
        <li>
          <?php _e( 'Custom Latitude Coordinate', 'wpp' ); ?>: <?php echo WPP_F::input( "name=wpp_settings[custom_coords][latitude]&style=width: 100px;", $wp_properties[ 'custom_coords' ][ 'latitude' ] ); ?>
          <span class="description"><?php printf( __( 'Default is "%s"', 'wpp' ), $wp_properties[ 'default_coords' ][ 'latitude' ] ); ?></span>
        </li>
        <li><?php _e( 'Custom Longitude Coordinate', 'wpp' ); ?>: <?php echo WPP_F::input( "name=wpp_settings[custom_coords][longitude]&style=width: 100px;", $wp_properties[ 'custom_coords' ][ 'longitude' ] ); ?>
          <span class="description"><?php printf( __( 'Default is "%s"', 'wpp' ), $wp_properties[ 'default_coords' ][ 'longitude' ] ); ?></span></li>
        <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][google_maps][show_true_as_image]&label=" . sprintf( __( 'Show Checkboxed Image instead of "%s" and hide "%s" for %s/%s values', 'wpp' ), __( 'Yes', 'wpp' ), __( 'No', 'wpp' ), __( 'Yes', 'wpp' ), __( 'No', 'wpp' ) ), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'show_true_as_image' ] ); ?></li>
      </ul>

      <p><?php printf( __( 'Attributes to display in popup after a %1s on a map is clicked.', 'wpp' ), WPP_F::property_label( 'singular' ) ); ?></p>
      <div class="wp-tab-panel">
        <ul>

          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][google_maps][infobox_settings][show_property_title]&label=" . sprintf( __( 'Show %1s Title', 'wpp' ), WPP_F::property_label( 'singular' ) ), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'show_property_title' ] ); ?></li>

          <?php foreach( $wp_properties[ 'property_stats' ] as $attrib_slug => $attrib_title ): ?>
            <li><?php
              $checked = ( in_array( $attrib_slug, $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_attributes' ] ) ? true : false );
              echo WPP_F::checkbox( "id=google_maps_attributes_{$attrib_title}&name=wpp_settings[configuration][google_maps][infobox_attributes][]&label=$attrib_title&value={$attrib_slug}", $checked );
              ?></li>
          <?php endforeach; ?>

          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][google_maps][infobox_settings][show_direction_link]&label=" . __( 'Show Directions Link', 'wpp' ), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'show_direction_link' ] ); ?></li>
          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][google_maps][infobox_settings][do_not_show_child_properties]&label=" . sprintf( __( 'Do not show a list of child %1s in Infobox. ', 'wpp' ), WPP_F::property_label( 'plural' ) ), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'do_not_show_child_properties' ] ); ?></li>
        </ul>
      </div>
    </td>
  </tr>

  <tr>
    <th><?php _e( 'Address Display', 'wpp' ) ?></th>
    <td>

      <textarea name="wpp_settings[configuration][display_address_format]" style="width: 70%;"><?php echo $wp_properties[ 'configuration' ][ 'display_address_format' ]; ?></textarea>
      <br/>
      <span class="description">
             <?php _e( 'Available tags:', 'wpp' ) ?> [street_number] [street_name], [city], [state], [state_code], [county],  [country], [zip_code].
      </span>
    </td>
  </tr>

  <tr>
    <th><?php _e( 'Currency & Numbers', 'wpp' ); ?></th>
    <td>
      <ul>
        <li><?php echo WPP_F::input( "name=currency_symbol&label=" . __( 'Currency symbol.', 'wpp' ) . "&group=wpp_settings[configuration]&style=width: 50px;", $wp_properties[ 'configuration' ][ 'currency_symbol' ] ); ?></li>
        <li>
          <?php _e( 'Thousands separator symbol:', 'wpp' ); ?>
          <select name="wpp_settings[configuration][thousands_sep]">
            <option value=""> - </option>
            <option value="." <?php selected( $wp_properties[ 'configuration' ][ 'thousands_sep' ], '.' ); ?>><?php _e( '. (period)', 'wpp' ); ?></option>
            <option value="," <?php selected( $wp_properties[ 'configuration' ][ 'thousands_sep' ], ',' ); ?>><?php _e( ', (comma)', 'wpp' ); ?></option>
           </select>
           <span class="description"><?php _e( 'The character separating the 1 and the 5: $1<b>,</b>500' ); ?></span>

        </li>

        <li>
          <?php _e( 'Currency symbol placement:', 'wpp' ); ?>
          <select name="wpp_settings[configuration][currency_symbol_placement]">
            <option value=""> - </option>
            <option value="before" <?php selected( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ], 'before' ); ?>><?php _e( 'Before number', 'wpp' ); ?></option>
            <option value="after" <?php selected( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ], 'after' ); ?>><?php _e( 'After number', 'wpp' ); ?></option>
          </select>
        </li>

        <li>
          <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][show_aggregated_value_as_average]&label=" . __( 'Parent property\'s aggregated value should be set as average of children values. If not, - the aggregated value will be set as sum of children values.', 'wpp' ), $wp_properties[ 'configuration' ][ 'show_aggregated_value_as_average' ] ); ?>
          <br/><span class="description"><?php printf( __( 'Aggregated value is set only for numeric and currency attributes and can be updated ( set ) only on child %1s\'s saving.', 'wpp' ), WPP_F::property_label( 'singular' ) ); ?></span>
        </li>

     </ul>
    </td>
  </tr>

  <tr>
    <th>
      <?php _e( 'Admin Settings', 'wpp' ) ?>
    </th>
      <td>
      <ul>
        <li><?php printf( __( 'Thumbnail size for %1s images displayed on %2s page: ', 'wpp' ), WPP_F::property_label( 'singular' ), WPP_F::property_label( 'plural' ) ) ?> <?php WPP_F::image_sizes_dropdown( "name=wpp_settings[configuration][admin_ui][overview_table_thumbnail_size]&selected=" . $wp_properties[ 'configuration' ][ 'admin_ui' ][ 'overview_table_thumbnail_size' ] ); ?></li>
        <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][completely_hide_hidden_attributes_in_admin_ui]&label=" . sprintf( __( 'Completely hide hidden attributes when editing %1s.', 'wpp' ), WPP_F::property_label( 'plural' ) ), $wp_properties[ 'configuration' ][ 'completely_hide_hidden_attributes_in_admin_ui' ] ); ?></li>
      </ul>
    </td>
  </tr>

  <?php do_action( 'wpp_settings_display_tab_bottom' ); ?>

  <?php do_settings_fields( get_current_screen()->id, 'display' ); ?>

</table>
