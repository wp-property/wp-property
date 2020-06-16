<?php
/**
 * Default FEPS form template
 *
 * Copy this file into your theme directory to customize it.
 *
 * @version 0.1
 * @author korotkov@UD
 * @author peshkov@UD
 * @package WP-Property
 *
 */

?>
<style type="text/css">#wpp_feps_form_<?php echo $form_id; ?> .wpp_feps_preview_thumb,#wpp_feps_form_<?php echo $form_id; ?> .wpp_feps_existing_thumb {max-width: <?php echo $thumbnail_size['width']; ?>px !important;cursor:pointer; }</style>
<div class="<?php wpp_css("feps-default-template::message", ""); ?> wpp_feps_message"></div>
<form action="" method="post" enctype="multipart/form-data" id="wpp_feps_form_<?php echo $form_id; ?>" class="<?php wpp_css("feps-default-template::form","wpp_feps_form"); ?>">
  <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
  <input type="hidden" name="wpp_feps_data[form_id]" value="<?php echo esc_attr(md5($args['form_id'])); ?>" />
  <input type="hidden" name="wpp_feps_data[this_session]" value="<?php echo esc_attr($this_session); ?>" />

  <?php if ( !empty( $parent_id ) ) : ?>
    <input type="hidden" name="wpp_feps_data[parent_id]" value="<?php echo esc_attr($args['parent_id']); ?>" />
  <?php endif; ?>
  <?php if (!empty($property['ID'])) : ?>
    <input type="hidden" name="wpp_feps_data[post]" value="<?php echo esc_attr($property['ID']); ?>" />
  <?php endif; ?>

  <ul class="<?php wpp_css("feps-default-template::fields-wrapper","feps_property_input_fields"); ?>">
    <?php
    $tabindex = 100;
    $required_items = array();
    $required_taxonomy = array();
    foreach ( (array)$form_fields as $row_id => $att_data ) {
      $tabindex++;

      $att_data['tabindex'] = $tabindex;

      if($att_data['required'] || $att_data['required'] == 'on'){
        if($att_data['storage_type'] != 'taxonomy')
          $required_items[$att_data['slug']] = "wpp_feps_data[{$att_data['slug']}]";
        else
          $required_taxonomy[] = $att_data['slug'];
      }
      ?>
      <li class="<?php wpp_css("feps-default-template::row-wrapper", array("wpp_feps_row_wrapper",$att_data['ui_class'],( $att_data['required'] == 'on' ? 'required' : ''))); ?>">
        <div class="<?php wpp_css("feps-default-template::label-wrapper","wpp_feps_label_wrapper"); ?>">
          <label for="wpp_<?php echo $row_id; ?>_input"><span class="<?php wpp_css("feps-default-template::the_title","the_title"); ?>"><?php echo $att_data['title']; ?></span></label>
        </div>
        <div class="<?php wpp_css("feps-default-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div class="<?php wpp_css("feps-default-template::input-content","wpp_feps_input_content"); ?>">
            <?php
            class_wpp_feps::wpp_feps_input( array(
              'this_session' => $this_session,
              'form_dom_id' => 'wpp_feps_form_' . $form_id,
              'att_data' => $att_data,
              'row_id' => $row_id,
              'args' => $args,
              'property' => ( isset( $property ) ? $property : false ),
              'images_limit' => abs( (int) $images_limit ),
              'form_id' => $args['form_id']
            ) );
            ?></div>
        </div>
        <div class="<?php wpp_css("feps-default-template::description_wrapper","wpp_feps_description_wrapper"); ?>">
          <?php if (!empty($att_data['description'])) { ?>
            <div class="<?php wpp_css("feps-default-template::clear","clear"); ?>"></div>
            <span class="<?php wpp_css("feps-default-template::attribute_description_text","attribute_description_text"); ?>"><?php echo nl2br(WPP_F::cleanup_extra_whitespace($att_data['description'])); ?></span>
          <?php } ?>
        </div>
        <div class="<?php wpp_css("feps-default-template::clear","clear"); ?>"></div>
      </li>
    <?php } ?>
  </ul>
  <script type="text/javascript" id="fepsRequiredItems">
    var fepsRequiredItems = <?php echo json_encode($required_items);?>;
    var fepsRequiredTaxonomy = <?php echo json_encode($required_taxonomy);?>;
  </script>
  <ul class="<?php wpp_css("feps-default-template::fields-wrapper","feps_user_input_fields"); ?>">

    <?php if (empty($current_user->ID)) : ?>
      <li class="<?php wpp_css("feps-default-template::row-wrapper", array("wpp_feps_row_wrapper")); ?>">
        <div class="<?php wpp_css("feps-default-template::label-wrapper", array("wpp_feps_label_wrapper")); ?>">
          <label for="<?php echo $form_id; ?>_user_email"><span class="<?php wpp_css("feps-default-template::the_title","the_title"); ?>"><?php _e('Your e-mail:', ud_get_wpp_feps()->domain); ?></span></label>
        </div>
        <div class="<?php wpp_css("feps-default-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div class="<?php wpp_css("feps-default-template::input-content","wpp_feps_input_content"); ?>">
            <input tabindex="<?php echo $tabindex; ?>" type="text" id="<?php echo $form_id; ?>_user_email" name="wpp_feps_data[user_email]" class="<?php wpp_css("feps-default-template::input-class","wpp_feps_user_email"); ?>" value="<?php echo isset($current_user->data->user_email) ? $current_user->data->user_email : ''; ?>" />
          </div>
        </div>
        <div class="<?php wpp_css("feps-default-template::description_wrapper","wpp_feps_description_wrapper"); ?>"></div>
        <div class="<?php wpp_css("feps-default-template::clear","clear"); ?>"></div>
      </li>
      <li class="<?php wpp_css("feps-default-template::row-wrapper", array("wpp_feps_row_wrapper",'user_password')); ?>" style="display:none;">
        <div class="<?php wpp_css("feps-default-template::label-wrapper", array("wpp_feps_label_wrapper")); ?>">
          <label for="<?php echo $form_id; ?>_user_password"><span class="<?php wpp_css("feps-default-template::the_title","the_title"); ?>"><?php _e('Your Password:', ud_get_wpp_feps()->domain); ?></span></label>
        </div>
        <div class="<?php wpp_css("feps-default-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div class="<?php wpp_css("feps-default-template::input-content","wpp_feps_input_content"); ?>">
            <input tabindex="<?php echo $tabindex; ?>" type="password" id="<?php echo $form_id; ?>_user_password" name="wpp_feps_data[user_password]" class="<?php wpp_css("feps-default-template::input-class","wpp_feps_user_password"); ?>" value="" />
          </div>
        </div>
        <div class="<?php wpp_css("feps-default-template::clear","clear"); ?>"></div>
      </li>
    <?php endif; ?>
      
    <?php do_action( 'wpp::feps::edit_property', ( isset( $property ) ? $property : false ) ); ?>
    
    <li class="<?php wpp_css("feps-default-template::input-wrapper","wpp_feps_input_wrapper submit_action"); ?> ">
      <span class="<?php wpp_css("feps-default-template::ajax-message","wpp_feps_ajax_message"); ?>"></span>
      <button tabindex="<?php echo $tabindex; ?>" type="submit" class="<?php wpp_css("feps-default-template::submit","wpp_feps_submit_form btn feps_action_btn"); ?>" >
        <span class="submit_btn_label"><?php echo apply_filters( 'feps::template::submit::btn', __('Submit', ud_get_wpp_feps()->domain), ( isset( $property ) ? $property : false ), $form ); ?>
        </span>
        <span class="wpp-feps-loadingdots  clearfix"></span>
      </button>
    </li>
  </ul>

</form>

<script type="text/javascript">
  jQuery(document).ready(function() { wpp.init_feps_form( "#wpp_feps_form_<?php echo $form_id; ?>" ); });
</script>
