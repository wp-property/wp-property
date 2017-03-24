<?php
/**
 * Advanced Option
 * Used for Attributes/Taxonomies
 */
?>
<label class="wpp-meta-alias-entry">
  <input type="text" class="slug wpp_field_alias" name="wpp_settings[field_alias][<%= slug %>]" placeholder="Alias for <%= slug %>" value="<%= ( typeof filtered_field_alias !== 'undefined' ? filtered_field_alias[slug] : '' ) %>" />
</label>