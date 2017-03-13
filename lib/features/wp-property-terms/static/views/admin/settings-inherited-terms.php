<?php
/**
 * Settings 'Developer' Tab
 * Section 'Types'
 * Data (terms_inheritance) will be passed from Terms_Bootstrap::init()
 *
 * Adds taxonomies to 'inherited Attributes' list
 */

?>
<% jQuery.each( wp_properties.taxonomies, function(key, data){ %>
<li class="wpp_development_advanced_option">
  <input id="<% print( property_slug + "_" + key) %>_taxonomy_inherited" <% if( typeof terms_inheritance[ property_slug ] != 'undefined' && jQuery.inArray(key, terms_inheritance[ property_slug ]) != -1){ print( 'CHECKED'); } %> type="checkbox" name="wpp_terms[inherited][<%= property_slug %>][]" value="<%= key %>"/>
  <label for="<% print( property_slug + "_" + key) %>_taxonomy_inherited">
    <%= data.label %> (<?php _e( 'taxonomy', ud_get_wpp_terms('domain') ) ?>)
  </label>
</li>
<% }); %>
