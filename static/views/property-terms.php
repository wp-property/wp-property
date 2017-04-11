<?php
/**
 * [property_terms] template
 *
 * To modify it, copy it to your theme's root.
 */

$list = get_the_term_list( $property_id, $taxonomy, '<li>', '</li><li>', '</li>' );
if(trim($list) != ""):
if($title != ''){
	echo '<div><strong>'. $title .'</strong></div>';
}
echo WPP_LEGACY_WIDGETS ? '<ul class="' . $taxonomy .'_list">' : '<ul id="property_term_list_v2" class="' . $taxonomy .'_list">';
	echo $list;
echo '</ul>';
endif;