        <img class="wpp_feps_images_loading" src="<?php echo WPP_URL . 'images/ajax_loader.gif' ?>" style="visibility:hidden;" />
        <div class="wpp_image_upload">
          <div class="ajax_uploader"><?php _e('Upload Image', ud_get_wpp_feps()->domain); ?></div>
          <span id="status"></span>
          <ul id="files">
            <?php if( !empty( $property['gallery'] ) && is_array( $property['gallery'] ) ) : ?>
              <?php foreach ( $property['gallery'] as $imkey => $im_data ): ?>
              <li class="qq-old-images"><img src="<?php echo $im_data['thumbnail']; ?>" class="wpp_feps_existing_thumb" session="<?php echo $this_session; ?>" filename="<?php echo sanitize_key( $im_data['post_title'] ); ?>" title="Click to Remove"></li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
          <?php if( !empty( $property['gallery'] ) && is_array( $property['gallery'] ) ) : ?>
            <?php foreach ( $property['gallery'] as $imkey => $im_data ):?>
              <input type="hidden" name="wpp_feps_data[<?php echo $att_data['slug']; ?>][<?php echo $im_data['attachment_id']; ?>]" id="<?php echo sanitize_key( $im_data['post_title'] ); ?>" value="on" />
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <?php if($images_limit > 0) { ?>
        <span class="images_limit"><?php printf(__('No more than %1d image(s). Click image to delete it.', ud_get_wpp_feps()->domain), $images_limit); ?></span>
        <?php } ?>

        <?php ob_start(); ?>
        <script type="text/javascript">
          jQuery(document).ready(function() {
            //** Remove image handler - korotkov@ud */
            jQuery(document).on('click', 'img.wpp_feps_preview_thumb', function(){
              //** turn on loader */
              jQuery('.wpp_feps_images_loading').css({'visibility':'visible'});
              //** init request data */
              var image = jQuery(this);
              var data = {
                action: 'wpp_feps_image_delete',
                session: image.attr('session'),
                filename: image.attr('filename')
              };
              //** send  */
              jQuery.post(
                '<?php echo admin_url('admin-ajax.php'); ?>',
                data,
                function(response) {
                  //** turn off loader */
                  jQuery('.wpp_feps_images_loading').css({'visibility':'hidden'});
                  //** if image removed */
                  if ( response.success ) {
                    //** remove image (not it's parent li - important) from page */
                    image.remove();
                    //** increase images count and check if we can upload more images */
                    if ( ++max_images > 0 ) {
                      jQuery('div.qq-upload-drop-area').show();
                      jQuery('div.qq-upload-button input').show();
                      jQuery('div.qq-upload-button').css({'visibility':'visible'});
                    }
                  }
                },
                'json'
              );
            });

            jQuery(document).on('click', 'img.wpp_feps_existing_thumb', function(){
              //** turn on loader */
              jQuery('.wpp_feps_images_loading').css({'visibility':'visible'});
              //** init request data */
              jQuery(this).closest('li.qq-old-images').remove();
              jQuery('input#'+jQuery(this).attr('filename')).attr('value','off');
              ++max_images;

              if ( ++max_images > 0 ) {
                jQuery('div.qq-upload-drop-area').show();
                jQuery('div.qq-upload-button input').show();
                jQuery('div.qq-upload-button').css({'visibility':'visible'});
              }
              jQuery('.wpp_feps_images_loading').css({'visibility':'hidden'});
            });

            if(typeof(qq) == 'undefined') {
              return;
            }

            var max_images = <?php echo !empty( $data['property']['gallery'] ) ? $images_limit - count((array)$data['property']['gallery']) : $images_limit; ?>;

            var this_form = jQuery("#<?php echo $form_dom_id; ?>");

            var uploader = new qq.FileUploader({
              element: jQuery('.wpp_image_upload .ajax_uploader', this_form)[0],
              action: '<?php echo admin_url('admin-ajax.php'); ?>',
              params: {
                  action: 'wpp_feps_image_upload',
                  this_session: '<?php echo $this_session; ?>'
              },
              name: 'wpp_feps_files',
              onComplete: function(id, fileName, responseJSON){
                if ( responseJSON ) {
                  max_images--;
                  if ( max_images <= 0 ) {
                    jQuery('div.qq-upload-drop-area').hide();
                    jQuery('div.qq-upload-button input').hide();
                    jQuery('div.qq-upload-button').css({'visibility':'hidden'});
                  }
                  var thumb_url = responseJSON.thumb_url;
                  if ( jQuery.browser.msie || jQuery.browser.opera ) {
                    id = String(id).substring(String(id).length, String(id).length-1);
                  }
                  jQuery( jQuery("ul.qq-upload-list li").get(id) ).html('<img title="Click to Remove" filename="'+fileName+'" session="<?php echo $this_session; ?>" class="wpp_feps_preview_thumb" src="' + thumb_url + '"/>');
                }
              }
            });

            if ( max_images <= 0 ) {
              jQuery('div.qq-upload-drop-area').hide();
              jQuery('div.qq-upload-button input').hide();
              jQuery('div.qq-upload-button').css({'visibility':'hidden'});
            }

          });
        </script>
        <?php
          $output_js = ob_get_contents();
          ob_end_clean();
          echo WPP_F::minify_js($output_js);
