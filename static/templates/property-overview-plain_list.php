<?php
/**
 * WP-Property Property Over - "Plain List" Template
 *
 * To include in a post or page, use shortcode: [property_overview pagination=off template=plain_list per_page=1000]
 *
 * To use in template, use do_shortcode([property_overview...]) function.
 *
 * To customize this file, copy it into your theme directory, and the plugin will
 * automatically load your version.
 *
 * @version 1.4
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/

if($properties): ?>
<ul class="<?php wpp_css('property_overview_plain_list::row_view', "wpp_row_view"); ?>">
    <?php foreach($properties as $property_id): ?>

    <?php $property = prepare_property_for_display(get_property($property_id, ($show_children ? "get_property['children']={$show_property['children']}" : ""))); ?>

    <li class="<?php wpp_css('property_overview_plain_list::property_div', "property_div"); ?>">
      <a href="<?php echo $property['permalink']; ?>"><?php echo $property['post_title']; ?></a>
      <?php if($show_children && $property['children']): ?>
        <ul class="child_properties">
            <?php foreach($property['children'] as $child): ?>
            <li><a <?php echo $in_new_window; ?> href="<?php echo $child['permalink']; ?>"><?php echo $child['post_title']; ?></a></li>
            <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
</ul><?php // .wpp_property_list ?>

<?php endif; ?>