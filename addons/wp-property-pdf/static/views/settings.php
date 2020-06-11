<?php
/**
 * PDF Tab on WP-Property Settings page
 */

?>
<table class="form-table">
  <tbody>

  <?php if(!is_writable($uploads['path'])) { ?>
    <tr valign="top">
      <th scope="row" colspan="2" style="color:red">
        <div class="updated fade"><p><?php printf(__("Warning: <b>%s</b> is not writable, PDF flyer cannot be created.", ud_get_wpp_pdf()->domain), $uploads['path']); ?></p></div>
      </th>
    </tr>
  <?php } ?>

  <tr valign="top">
    <th scope="row"><?php _e('Options',ud_get_wpp_pdf()->domain); ?></th>
    <td>
      <ul>

        <li>
          <input type="checkbox" id="use_pdf_property_lists" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][use_pdf_property_lists]" <?php checked( $wpp_pdf_flyer['use_pdf_property_lists'],'on' ); ?>>
          <label for="use_pdf_property_lists" class="description"><?php printf(__('Show panel that allows you to create PDF %1s lists.',ud_get_wpp_pdf()->domain), WPP_F::property_label( 'singular' )); ?></label>
        </li>

        <li>
          <input type="checkbox" id="generate_flyers_on_the_fly" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][generate_flyers_on_the_fly]" <?php checked($wpp_pdf_flyer['generate_flyers_on_the_fly'],'on'); ?>>
          <label for="generate_flyers_on_the_fly" class="description"><?php printf(__('Generate %1s flyers automatically when somebody tries to view them for the first time.',ud_get_wpp_pdf()->domain), WPP_F::property_label( 'singular' )); ?></label>
        </li>

        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code]" <?php if(isset($wpp_pdf_flyer['qr_code']) && $wpp_pdf_flyer['qr_code']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code]"><?php _e("Generate QR Code.", ud_get_wpp_pdf()->domain); ?></label></span>
        </li>

        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][truncate_description]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][truncate_description]" <?php if(isset($wpp_pdf_flyer['truncate_description']) && $wpp_pdf_flyer['truncate_description']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][truncate_description]"><?php _e('Truncate the Description when it\'s displayed in Flyer (It can be helpful, when the data can not be placed on one page).',ud_get_wpp_pdf()->domain); ?></label></span>
        </li>

      </ul>
    </td>
  </tr>

  <tr valign="top">
    <th scope="row"><?php _e('Flyer Display',ud_get_wpp_pdf()->domain); ?></th>
    <td>
      <ul>
        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_title]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_title]" <?php if(isset($wpp_pdf_flyer['pr_title']) && $wpp_pdf_flyer['pr_title']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_title]"><?php _e('Title (Header)',ud_get_wpp_pdf()->domain); ?></label></span>
        </li>

        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_tagline]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_tagline]" <?php if(isset($wpp_pdf_flyer['pr_tagline']) && $wpp_pdf_flyer['pr_tagline']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_tagline]"><?php _e('Tagline under Title (Header)',ud_get_wpp_pdf()->domain); ?></label></span>
        </li>

        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code_note]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code_note]" <?php if(isset($wpp_pdf_flyer['qr_code_note']) && $wpp_pdf_flyer['qr_code_note']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][qr_code_note]"><?php _e("QR Code's note (a note explaining what a QR Code is, if it exists)", ud_get_wpp_pdf()->domain); ?></label></span>
        </li>

        <li>
          <?php $attrs = class_wpp_pdf_flyer::get_pdf_list_attributes('property_stats'); ?>
          <?php if (!empty($attrs)) : ?>
            <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_details]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_details]" <?php if(isset($wpp_pdf_flyer['pr_details']) && $wpp_pdf_flyer['pr_details']=='on') echo " CHECKED "; ?>>
            <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_details]"><?php _e('Details',ud_get_wpp_pdf()->domain); ?></label></span>
            <div class="flyer-detail-attributes wp-tab-panel hidden">
              <ul>
                <?php foreach ($attrs as $slug => $attr) : ?>
                  <li>
                    <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][detail_attributes][<?php echo $slug; ?>]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][detail_attributes][<?php echo $slug; ?>]" <?php if(isset($wpp_pdf_flyer['detail_attributes'][$slug]) && $wpp_pdf_flyer['detail_attributes'][$slug]=='on') echo " CHECKED "; ?>>
                    <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][detail_attributes][<?php echo $slug; ?>]"><?php echo $attr; ?></label></span>
                  </li>
                <?php endforeach; ?>
              </ul>

            </div>

            <script type="text/javascript">
              jQuery(document).ready(function(){
                var pr_details = jQuery("input[name='wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_details]']");
                var attrs_block = jQuery(".flyer-detail-attributes");
                /* When details option is checked we show options for attributes */
                if (pr_details.is(':checked')) {
                  attrs_block.show();
                }

                pr_details.change(function(){
                  if (pr_details.is(':checked')) {
                    attrs_block.show('slow');
                  } else {
                    attrs_block.hide('slow');
                  }
                });
              });
            </script>
          <?php endif; ?>
        </li>

        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_description]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_description]" <?php if(isset($wpp_pdf_flyer['pr_description']) && $wpp_pdf_flyer['pr_description']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_description]"><?php _e('Description (if exists)',ud_get_wpp_pdf()->domain); ?></label></span>
        </li>

        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_location]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_location]" <?php if(isset($wpp_pdf_flyer['pr_location']) && $wpp_pdf_flyer['pr_location']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_location]"><?php _e('Location on Map (if exists)',ud_get_wpp_pdf()->domain); ?></label></span>
        </li>

        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_features]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_features]" <?php if(isset($wpp_pdf_flyer['pr_features']) && $wpp_pdf_flyer['pr_features']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_features]"><?php _e('Features (if exists)',ud_get_wpp_pdf()->domain); ?></label></span>
        </li>

        <li>
          <input type="checkbox" id="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_agent_info]" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_agent_info]" <?php if(isset($wpp_pdf_flyer['pr_agent_info']) && $wpp_pdf_flyer['pr_agent_info']=='on') echo " CHECKED "; ?>>
          <span class="description"><label for="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][pr_agent_info]"><?php _e('Agent Information (if exists)',ud_get_wpp_pdf()->domain); ?></label></span>
        </li>
      </ul>
    </td>
  </tr>

  <tr valign="top">
    <th scope="row"><?php _e('Primary Photo Size',ud_get_wpp_pdf()->domain); ?></th>
    <td>
      <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][feature_settings][wpp_pdf_flyer][primary_photo_size]&selected={$wpp_pdf_flyer['primary_photo_size']}"); ?>
    </td>
  </tr>
  <tr valign="top">
    <th scope="row"><?php _e('Font',ud_get_wpp_pdf()->domain); ?></th>
    <td>
      <?php wpp_tcpdf_get_HTML_font_list("name=wpp_settings[configuration][feature_settings][wpp_pdf_flyer][setfont]&selected={$wpp_pdf_flyer['setfont']}"); ?><br>
      <span class="description"><?php _e('The default font is Helvetica. If you have any problems with the current font try choosing another one from the list.',ud_get_wpp_pdf()->domain); ?></span>
    </td>
  </tr>
  <tr>
    <th><?php _e('Secondary Images',ud_get_wpp_pdf()->domain); ?></th>
    <td>
      <ul>
        <li>
          <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][feature_settings][wpp_pdf_flyer][secondary_photos]&selected={$wpp_pdf_flyer['secondary_photos']}"); ?>
        </li>
        <li>
          <?php printf(__('Show %1$s images.', ud_get_wpp_pdf()->domain), '<input type="text" size="5" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][num_pictures]" value='.$wpp_pdf_flyer['num_pictures'].'>'); ?>
        </li>
      </ul>
    </td>
  </tr>
  <tr valign="top">
    <th scope="row"><?php _e('Logo URL',ud_get_wpp_pdf()->domain); ?></th>
    <td>
      <input type="text" size="60" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][logo_url]" value="<?php echo $wpp_pdf_flyer['logo_url']; ?>">
      <span class="description"><?php _e('Use JPEG and GIF images only.',ud_get_wpp_pdf()->domain); ?></span>
    </td>
  </tr>
  <tr valign="top">
    <th scope="row"><?php _e('Header color',ud_get_wpp_pdf()->domain); ?></th>
    <td>
      <input type="text" class="wpp_input_colorpicker" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][header_color]" value="<?php echo $wpp_pdf_flyer['header_color']; ?>">
      <span class="description"><?php _e('Header color',ud_get_wpp_pdf()->domain); ?></span>
    </td>
  </tr>

  <tr valign="top">
    <th scope="row"><?php _e('Section background color',ud_get_wpp_pdf()->domain); ?></th>
    <td>
      <input type="text" class="wpp_input_colorpicker" name="wpp_settings[configuration][feature_settings][wpp_pdf_flyer][section_bgcolor]" value="<?php echo $wpp_pdf_flyer['section_bgcolor']; ?>">
    </td>
  </tr>

  <?php do_action('wpp_flyer_settings_table_bottom', $wpp_pdf_flyer); ?>

</table>



<table class='form-table'>
  <tr valign="top">
    <td colspan="2">
      <br class="cb" />
        <span class="description">
        <?php _e('Shortcode examples:<br />',ud_get_wpp_pdf()->domain); ?>
        <?php _e('<strong>[property_flyer]</strong> - Returns a html link to the PDF Flyer<br />',ud_get_wpp_pdf()->domain); ?>
        <?php _e('<strong>[property_flyer title=\'PDF Flyer\']</strong> - Returns a html link to the PDF Flyer with custom title.<br />',ud_get_wpp_pdf()->domain); ?>
        <?php _e('<strong>[property_flyer urlonly=\'yes\']</strong> -  Returns the raw URL to the PDF Flyer (for use in custom html).<br />',ud_get_wpp_pdf()->domain); ?>
        <?php _e('<strong>[property_flyer class=\'custom_css_class\']</strong> - For use with a custom CSS class.<br />',ud_get_wpp_pdf()->domain); ?>
        <?php _e('<strong>[property_flyer image=\'url_to_custom_image\']</strong> - Returns url_to_custom_image with a link to the PDF Flyer.',ud_get_wpp_pdf()->domain); ?>
        </span>
    </td>
  </tr>
</table>

<div class="wp-core-ui">
  <div class="wpp_settings_block" style="margin: 10px 10px 0;">
    <span><?php printf(__('You can regenerate all your PDF flyers, but depending on your server, it can be very time consuming if you have many %1s.',ud_get_wpp_pdf()->domain), WPP_F::property_label( 'plural' )); ?></span>
    <input type="button" class="button" id="wpp_ajax_regenerate_all_flyers" value="<?php _e('Regenerate all Flyers',ud_get_wpp_pdf()->domain); ?>">&nbsp;<img style="display:none;" id="regenerate_all_flyers_ajax_spinner" src="<?php echo WPP_URL; ?>images/ajax_loader.gif" />
    <br/><input style="display:none;" type="button" id="wpp_ajax_regenerate_all_flyers_close" value="<?php _e('Close Result\'s Logs',ud_get_wpp_pdf()->domain); ?>">
    <pre class="wpp_class_pre hidden" id="wpp_ajax_regenerate_all_flyers_result" style="height:300px;"></pre>
  </div>
</div>
<script type="text/javascript">
  jQuery( '#wpp_ajax_regenerate_all_flyers' ).click(function(){
    var ajaxSpinner = jQuery('#regenerate_all_flyers_ajax_spinner');
    var closeButton = jQuery("#wpp_ajax_regenerate_all_flyers_close");
    var resultBox = jQuery('#wpp_ajax_regenerate_all_flyers_result');

    var wpp_recursively_generate_pdf_flyer = function( data, callback ) {
      var item = data.shift();
      jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        data: 'action=wpp_generate_pdf_flyer&post_id=' + item.post_id,
        complete: function( r, status ) {
          if( status === 'success' ) {
            var result = eval('(' + r.responseText + ')');
            if(result.success == 1) {
              putLog('<?php _e('PDF Flyer for property "',ud_get_wpp_pdf()->domain); ?>' + item.post_title + '<?php _e('" is generated.',ud_get_wpp_pdf()->domain); ?>', resultBox);
            } else {
              putLog('<?php _e('<b>Error. PDF Flyer for property "',ud_get_wpp_pdf()->domain); ?>' + item.post_title + '<?php _e('" could not be generated.</b>',ud_get_wpp_pdf()->domain); ?>', resultBox);
            }
          } else {
            putLog('<?php _e('Could not regenerate PDF Flyer for property "',ud_get_wpp_pdf()->domain); ?>' + item.post_title + '<?php _e('". Looks like, something caused error on server.',ud_get_wpp_pdf()->domain); ?>', resultBox);
          }
          if ( data.length == 0 ) {
            if( typeof callback === 'function' ) {
              callback();
            }
          } else {
            wpp_recursively_generate_pdf_flyer( data, callback );
          }
        }
      });
    }

    ajaxSpinner.show( 'fast', function() {
      resultBox.show( 'fast', function() {
        jQuery( '#wpp_ajax_regenerate_all_flyers' ).prop( 'disabled', true );
        jQuery.ajax({
          url: '<?php echo admin_url('admin-ajax.php'); ?>',
          //async: false,
          data: 'action=wpp_get_property_ids',
          complete: function( r, status ) {

            if( status === 'success' ) {

              var data = eval('(' + r.responseText + ')');
              if( data.length > 0 ) {
                putLog('<?php _e('Property List is got. Start generate PDF Flyers...',ud_get_wpp_pdf()->domain); ?>', resultBox);

                // Loop all properties
                wpp_recursively_generate_pdf_flyer( data, function() {
                  jQuery( '#wpp_ajax_regenerate_all_flyers' ).prop( 'disabled', false );
                  putLog('<?php _e('Finished.',ud_get_wpp_pdf()->domain); ?>', resultBox);
                  ajaxSpinner.hide();
                  closeButton.show();
                } );

              } else {
                putLog('<?php _e('There are no any property to generate PDF Flyer for.',ud_get_wpp_pdf()->domain); ?>', resultBox);
                ajaxSpinner.hide();
                closeButton.show();
              }
            } else {
              putLog('<?php _e('Looks like, something caused error on server. Please, try to regenerate PDF Flyers later.',ud_get_wpp_pdf()->domain); ?>', resultBox);
              ajaxSpinner.hide();
              closeButton.show();
            }

          }
        } );
      } );
    } );
    return false;
  });

  jQuery("#wpp_ajax_regenerate_all_flyers_close").click(function(){
    var closeButton = jQuery(this);
    var resultBox = jQuery('#wpp_ajax_regenerate_all_flyers_result');

    resultBox.hide();
    resultBox.html('');
    closeButton.hide();
  });


  function putLog (log, el) {
    if (typeof log != 'undefined' && typeof el == 'object'){
      if (jQuery('.logs', el).length == 0) {
        el.append('<ul class="logs"></ul>');
      }
      jQuery('.logs', el).append('<li>' + log + '</li>');
    }
  }

</script>