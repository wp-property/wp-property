<?php
/**
 * [property_walkscore_neighborhood] template
 *
 * To modify it, copy it to your theme's root.
 */
?>

<script type='text/javascript'>
  <?php foreach( $data as $k => $v ) {
    // Set default values for required parameters or ignore optional ones if they do not have values.
    if( empty( $v ) ) {
      switch( $k ) {
        case "ws_width":
          $v = '100%';
          break;
        case "ws_height":
          $v = '400';
          break;
        case "ws_layout":
          $v = 'horizontal';
          break;
        case "ws_map_modules":
          $v = 'default';
          break;
      }
    }
    if( !empty( $v ) ) echo "var {$k}=\"{$v}\";";
  } ?>
</script>

<div id='ws-walkscore-tile'></div>
<script type='text/javascript' src='//www.walkscore.com/tile/show-walkscore-tile.php'></script>


