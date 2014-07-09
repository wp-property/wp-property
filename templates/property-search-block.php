<?php
/**
 * Property Search Block
 *
 * Called in property-search.php template
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @version 1.3
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/
?>
<div class="<?php wpp_css('property_search_block::result_block', "wpp_s_result_block"); ?>" onclick="window.location='<?php echo $property[permalink]; ?>'">
  <div class="wpp_hidden_stats" style="display:none;">
    <?php
    foreach($wp_properties['searchable_attributes'] as $searchable_attribute):
    echo "<input type='hidden' class='{$searchable_attribute}' value='".WPP_F::do_search_conversion($searchable_attribute, $property[$searchable_attribute]) ."' />";
    endforeach;
    ?>
  </div>

  <div class="<?php wpp_css('property_search_block::header', "wpp_s_header"); ?>">
    <div class="wpp_s_title"><?php echo $property[post_title]; ?></div>
    <div class="wpp_s_tagline"><?php echo $property[tagline]; ?></div>

    <?php if(empty($property[tagline])): ?>
    <div class="wpp_s_location"><?php echo $property[location]; ?></div>
    <?php endif; ?>
    <div class="wpp_s_contact_number"><?php echo $property[contact_number]; ?></div>
  </div>

  <div class="<?php wpp_css('property_search_block::thumb', "wpp_search_thumb"); ?>">
    <a href="<?php echo $property[permalink]; ?>"><img src="<?php echo $property[grid_view]; ?>" alt="<?php echo $property[post_title]; ?>" /></a>
  </div>

  <div class="<?php wpp_css('property_search_block::body', "wpp_search_body"); ?>">
    <div class="wpp_s_price">$<?php echo $property[price]; ?></div>
    <div class="wpp_s_bedroom">
      <?php if(!empty($property[custom_attribute_overview])): ?>
              <?php echo $property[custom_attribute_overview]; ?>
      <?php else: ?>
              <?php echo $property[bedrooms]; ?> beds, <?php echo $property[bathrooms]; ?> baths <br />
              <?php echo $property[area]; ?> sq. ft.
      <?php endif; ?>
    </div>
  </div>
</div>