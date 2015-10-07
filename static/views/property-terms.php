<?php
/**
 * [property_terms] template
 *
 * To modify it, copy it to your theme's root.
 */

echo '<ul class="' . $taxonomy .'_list">';
echo get_the_term_list( $property_id, $taxonomy, '<li>', '</li><li>', '</li>' );
echo '</ul>';