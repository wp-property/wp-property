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

/*
 * search box with auto population and displaying particular label on selection in search box
 */ 
  jQuery(document).ready( function($) {
	  main_tabs = new Array();
	  main_tabs_href = new Array();
	  inner_tabs = new Array();
	  tabs_labels = all_label_key = all_label = new Array();
	  all_label_count = k=0;
	  jQuery("#wpp_settings_tabs ul.ui-tabs-nav:first li.ui-tabs-tab").each(function(){
		  main_tabs[k] = jQuery(this).children("a:first").attr("id");
		  main_tabs_href[k] = jQuery(this).children("a:first").attr("href");
		  tabs_labels[main_tabs[k]]= new Array();
		  k++;
	  });
	  for (j=0;j<k;j++){
		  label_count = 0;
		  jQuery(main_tabs_href[j]+" table.form-table th").each(function(){
			  label_count++;
			  html_text = jQuery(this).html();
			  html_text_arr = html_text.split('<');
			  tabs_labels[main_tabs[j]][label_count] = html_text_arr[0];
			  all_label[all_label_count++]={"id":main_tabs[j]+"~~"+label_count,"label":tabs_labels[main_tabs[j]][label_count],"value":tabs_labels[main_tabs[j]][label_count]};
		  });

	  }
//		console.log(main_tabs);
//		console.log(all_label);
	var availableTags = all_label;
    $( "#wpp_search_tags" ).autocomplete({
      source: availableTags,
	  select: function( event, ui ) {
		  jQuery(".show-selected").removeClass("show-selected");
		id_arr = ui.item.id.split("~~");
		main_tab_id = id_arr[0];
		label_index = id_arr[1];
		href_attr = jQuery("#"+main_tab_id).attr('href');
		jQuery("#"+main_tab_id).click();
		jQuery(href_attr+" table.form-table tr:nth-child("+label_index+") th:first").addClass("show-selected");//("background","black");
		
		jQuery('html,body').animate({
			scrollTop: jQuery(href_attr+" table.form-table tr:nth-child("+label_index+") th:first").offset().top -40},'slow');
      }
    });
  } );
