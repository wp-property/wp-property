<?php
/**
 * Settings 'Developer' Tab
 * Section 'Types'
 * Data (terms_hidden) will be passed from Terms_Bootstrap::init()
 * Adds taxonomies to 'Hidden Attributes' list
 */
?>
{{ jQuery.each( __.get(wp_properties, 'taxonomies', []), function(key, data){ }}
<li class="wpp_development_advanced_option">
  <input id="{{ print( property_slug + "_" + key) }}_taxonomy_hidden" {{= _.wppChecked(terms_hidden, property_slug, key) }} type="checkbox" name="wpp_terms[hidden][{{= property_slug }}][]" value="{{= key }}"/>
  <label for="{{ print( property_slug + "_" + key) }}_taxonomy_hidden">
    {{= data.label }} (<?php _e( 'taxonomy', ud_get_wpp_terms('domain') ) ?>)
  </label>
</li>
{{ }); }}
