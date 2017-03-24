<?php
/**
 * WP-Property Overview Template
 *
 * To customize this file, copy it into your theme directory, and the plugin will
 * automatically load your version.
 *
 * You can also customize it based on property type.  For example, to create a custom
 * overview page for 'building' property type, create a file called property-overview-building.php
 * into your theme directory.
 *
 *
 * Settings passed via shortcode:
 * $properties: either array of properties or false
 * $show_children: default true
 * $thumbnail_size: slug of thumbnail to use for overview page
 * $thumbnail_sizes: array of image dimensions for the thumbnail_size type
 * $fancybox_preview: default loaded from configuration
 * $child_properties_title: default "Floor plans at location:"
 *
 *
 *
 * @version 1.4
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
 */ ?>
<?php
if (have_properties()) {

  $thumbnail_dimentions = WPP_F::get_image_dimensions($wpp_query['thumbnail_size']);

  ?>
<div class="<?php wpp_css('property_overview::row_view', "wpp_row_view wpp_property_view_result"); ?>">
<div class="<?php wpp_css('property_overview::all_properties', "all-properties"); ?>">
  <?php foreach (returned_properties('load_gallery=false') as $property) { ?>

  <div class="<?php wpp_css('property_overview::property_div', "property_div {$property['post_type']}"); ?>">

    <div class="<?php wpp_css('property_overview::left_column', "wpp_overview_left_column"); ?>"
         style="width:<?php echo $thumbnail_dimentions['width']; ?>px; ">
      <?php property_overview_image(); ?>
    </div>

  <div class="<?php wpp_css('property_overview::right_column', "wpp_overview_right_column"); ?>">

    <ul class="<?php wpp_css('property_overview::data', "wpp_overview_data"); ?>">
      <li class="property_title">
        <a <?php echo $in_new_window; ?>
          href="<?php echo $property['permalink']; ?>"><?php echo $property['post_title']; ?></a>
        <?php if (!empty($property['is_child'])): ?>
          <?php _e('of', ud_get_wp_property()->domain); ?> <a <?php echo $in_new_window; ?>
            href='<?php echo $property['parent_link']; ?>'><?php echo $property['parent_title']; ?></a>
        <?php endif; ?>
      </li>

      <?php if (!empty($property['custom_attribute_overview']) || !empty($property['tagline'])): ?>
        <li class="property_tagline">
          <?php if (isset($property['custom_attribute_overview']) && $property['custom_attribute_overview']): ?>
            <?php echo $property['custom_attribute_overview']; ?>
          <?php elseif ($property['tagline']): ?>
            <?php echo $property['tagline']; ?>
          <?php endif; ?>
        </li>
      <?php endif; ?>

      <?php 
      if( is_array($wpp_query[ 'attributes' ]) ){
        foreach ($wpp_query[ 'attributes' ] as $attribute){
          if(!empty($property[$attribute])){
            $attribute_data = WPP_F::get_attribute_data($attribute);
            $data = $property[$attribute];
            if(is_array($data)){
              $data = implode( ', ', $data);
            }
            echo "<li class='property_attributes property_$attribute'><span class='title'>{$attribute_data['title']}:</span> {$property[$attribute]}</li>";
          }
        }
      }
      ?>

      <?php if ($show_children && !empty($property['children'])): ?>
        <li class="child_properties">
          <div class="wpd_floorplans_title"><?php echo $child_properties_title; ?></div>
          <table class="wpp_overview_child_properties_table">
            <?php foreach ($property['children'] as $child): ?>
              <tr class="property_child_row">
                <th class="property_child_title"><a
                    href="<?php echo $child['permalink']; ?>"><?php echo $child['post_title']; ?></a></th>
                <td class="property_child_price"><?php echo isset($child['price']) ? $child['price'] : ''; ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </li>
      <?php endif; ?>

      <?php if (!empty($wpp_query['detail_button'])) : ?>
        <li><a <?php echo $in_new_window; ?> class="button"
                                             href="<?php echo $property['permalink']; ?>"><?php echo $wpp_query['detail_button'] ?></a>
        </li>
      <?php endif; ?>
    </ul>

    </div><?php // .wpp_right_column ?>

    </div><?php // .property_div ?>

  <?php } /** end of the propertyloop. */ ?>
  </div><?php // .all-properties ?>
  </div><?php // .wpp_row_view ?>
<?php } else { ?>
  <div class="wpp_nothing_found">
    <p><?php echo sprintf(__('Sorry, no properties found - try expanding your search, or <a href="%s">view all</a>.', ud_get_wp_property()->domain), site_url() . '/' . $wp_properties['configuration']['base_slug']); ?></p>
  </div>
<?php } ?>