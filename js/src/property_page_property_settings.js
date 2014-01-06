jQuery(document).ready(function() {

  /**
   * Handles form saving
   * Do any validation/data work before the settings page form is submitted
   * @author odokienko@UD
   */
  jQuery(".wpp_settings_page form").submit(function( form ) {
    var error_field = {object:false,tab_index:false};

    /* The next block make validation for required fields    */
     jQuery("form #wpp_settings_tabs :input[validation_required=true],form #wpp_settings_tabs .wpp_required_field :input,form #wpp_settings_tabs :input[required],form #wpp_settings_tabs :input.slug_setter").each(function(){

      /* we allow empty value if dynamic_table has only one row */
      var dynamic_table_row_count = jQuery(this).closest('.wpp_dynamic_table_row').parent().children('tr.wpp_dynamic_table_row').length;

      if (!jQuery(this).val() && dynamic_table_row_count!=1){
        error_field.object = this;
        error_field.tab_index = jQuery('#wpp_settings_tabs a[href="#' + jQuery( error_field.object ).closest( ".ui-tabs-panel" ).attr('id') + '"]').parent().index();
        return false;
      }
    });

    /* if error_field object is not empty then we've error found */
    if (error_field.object != false ) {
      /* do focus on tab with error field */
      if(typeof error_field.tab_index !='undefined') {
        jQuery('#wpp_settings_tabs').tabs('option', 'active', error_field.tab_index);
      }
      /* mark error field and remove mark on keyup */
      jQuery(error_field.object).addClass('ui-state-error').one('keyup',function(){jQuery(this).removeClass('ui-state-error');});
      jQuery(error_field.object).focus();
      return false;
    }
  });

});