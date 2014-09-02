<?php
/**
 * Property Search Template
 *
 * Called by WPP_Core::shortcode_property_search();
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @version 1.3
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/
?>

<script type="text/javascript">
	jQuery(function() {


	// Function that goes through every element and figures out if it should be hidden based on slider settings
	function wpp_s_filter() {
		jQuery('.wpp_s_result_block').each(function(index) {
			if(wpp_s_is_hidden(this))
				jQuery(this).fadeOut();
			else
				jQuery(this).fadeIn();

		});
	}

	<?php foreach($wp_properties['searchable_attributes'] as $searchable_attribute): ?>
	jQuery("#<?php echo $searchable_attribute; ?>_slider").slider({
			range: true,

			<?php
			// load search range if passed, otherwise use max and mim values
			$min_value = strtolower((!empty($_REQUEST['wpp_search'][$searchable_attribute]['value1']) ? $_REQUEST['wpp_search'][$searchable_attribute]['value1'] : min($search_values[$searchable_attribute])));
			$max_value = strtolower((!empty($_REQUEST['wpp_search'][$searchable_attribute]['value2']) ? $_REQUEST['wpp_search'][$searchable_attribute]['value2'] :  max($search_values[$searchable_attribute])));


			// Fix values (i.e. studio => 0)
			if(isset($wp_properties['search_conversions'][$searchable_attribute][$min_value]))
				$min_value  = $wp_properties['search_conversions'][$searchable_attribute][$min_value];

			if(isset($wp_properties['search_conversions'][$searchable_attribute][$max_value]))
				$max_value  = $wp_properties['search_conversions'][$searchable_attribute][$max_value];


			?>
 			values: [<?php echo $min_value; ?>, <?php echo $max_value; ?>],
 			min: <?php echo min($search_values[$searchable_attribute]); ?>,
			max: <?php echo max($search_values[$searchable_attribute]); ?>,
			slide: function(event, ui) {
				jQuery("#<?php echo $searchable_attribute; ?>_result").val('<?php echo WPP_F::get_attrib_prefix($searchable_attribute); ?>' + ui.values[0] + '<?php echo WPP_F::get_attrib_annex($searchable_attribute); ?> - <?php echo WPP_F::get_attrib_prefix($searchable_attribute); ?>' + ui.values[1] + '<?php echo WPP_F::get_attrib_annex($searchable_attribute); ?>');
			},
			stop: function(event, ui) {
        wpp_s_filter();
      }
		});
		jQuery("#<?php echo $searchable_attribute; ?>_result").val('<?php echo WPP_F::get_attrib_prefix($searchable_attribute); ?>' + jQuery("#<?php echo $searchable_attribute; ?>_slider").slider("values", 0) + '<?php echo WPP_F::get_attrib_annex($searchable_attribute); ?> - <?php echo WPP_F::get_attrib_prefix($searchable_attribute); ?>' + jQuery("#<?php echo $searchable_attribute; ?>_slider").slider("values", 1) + '<?php echo WPP_F::get_attrib_annex($searchable_attribute); ?>');

	<?php endforeach; ?>


		// Checks if particular element should be hidden
		function wpp_s_is_hidden(element) {

			<?php foreach($wp_properties['searchable_attributes'] as $searchable_attribute): ?>
			// cycle through every attribute and check if this element should b hidden
			var min = jQuery("#<?php echo $searchable_attribute; ?>_slider").slider("values", 0);
			var max = jQuery("#<?php echo $searchable_attribute; ?>_slider").slider("values", 1);
			var this_value = jQuery('.wpp_hidden_stats .<?php echo $searchable_attribute; ?>', element).val();

			//jQuery(".<?php echo $searchable_attribute;?>_test").val("this_value: " + this_value + " min: " + min + " max: " + max);

			if(this_value < min || this_value > max)
				return true;


 			<?php endforeach; ?>

		}


	// Do filtering on page load. Need to do it no after all the sliders are setup.
	wpp_s_filter();



	});
</script>

<div class="<?php wpp_css('property_search::s_filter_box', "wpp_s_filter_box clearfix"); ?>">
  <div  class="clearfix">
  <?php foreach($wp_properties['searchable_attributes'] as $searchable_attribute): ?>
    <div class="wpp_s_filter_container">
      <p>
        <label for="<?php echo $searchable_attribute; ?>_result"><?php echo $wp_properties['property_stats'][$searchable_attribute]; ?>:</label>
        <input type="text" id="<?php echo $searchable_attribute; ?>_result" style="border:0; color:#f6931f; font-weight:bold; width: 155px;"  />
      </p>
      <div id="<?php echo $searchable_attribute; ?>_slider"></div>
    </div>
  <?php endforeach; ?>
  </div>
</div>

<div id="content" class="<?php wpp_css('property_search::content', "page type-page hentry"); ?>" >
  <div style="width: 890px; float: left;">
    <?php
    $searchable_properties = WPP_F::get_searchable_properties();

    if($searchable_properties):
      foreach($searchable_properties as $property_id):
        $property = WPP_F::get_property($property_id);
        // 1. Try template in theme folder
        $template_found = WPP_F::get_template_part(array(
          "property-search-block"
        ), array(WPP_Templates));
        if($template_found) {
          include $template_found;
        }
      endforeach;
    endif;
    ?>
  </div>
</div>

