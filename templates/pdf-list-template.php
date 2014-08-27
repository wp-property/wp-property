<?php

$descr = '&nbsp;';
$title = '&nbsp;';
$info = '&nbsp;';
$image_width = '90';
$image_height = '90';

// Prepare and Render view of Property Stats
$wpp_property_stats = class_wpp_pdf_flyer::get_pdf_list_attributes('property_stats');
$exclude_property_stats = array();
foreach ($wpp_property_stats as $key => $value) {
  if(!key_exists($key, $list_data['attributes'])) {
    $exclude_property_stats[] = $key;
  } else {
    unset($list_data['attributes'][$key]);
  }
}
$property_stats = @draw_stats( 'show_true_as_image=false&sort_by_groups=false&display=array&exclude=' . implode(',', $exclude_property_stats), $property );

if(!empty( $property_stats ) && is_array( $property_stats ) ) {
  foreach ($property_stats as $i => $d ) {
    $info .= '<br/>'. $d[ 'label' ] .': '. $d[ 'value' ];
  }
}

// Prepare and Render view of Taxonomies
$wpp_taxonomies = class_wpp_pdf_flyer::get_pdf_list_attributes('taxonomies');
if(is_array($wpp_taxonomies)) {
  foreach ($wpp_taxonomies as $key => $value) {
    if(key_exists($key, $list_data['attributes'])) {
      if(get_features("type=$key&format=count" , $property)) {
        $features = get_features("type=$key&format=array&links=false", $property);
        $info .= '<br/>'. $value .': '. implode($features, ", ");
      }
      unset($list_data['attributes'][$key]);
    }
  }
}

// Prepare other property attributes (image, title, description, tagline, etc)
foreach ($list_data['attributes'] as $attr_id => $attr_value) {
  if ( $attr_id == 'post_thumbnail' && !empty( $property['images']['thumbnail'] ) && WPP_F::can_get_image($property['images']['thumbnail'])) {

    $image = '<table cellspacing="0" cellpadding="5" border="0" style="background-color:' . $list_data['background'] . '"><tr><td>';
    $image .= '<img width="'. $image_width .'" height="'. $image_height .'" src="'. $property['images']['thumbnail'] .'" alt="" />';
    $image .= '</td></tr></table>';

  } elseif( $attr_id == 'post_content' && !empty( $property['post_content'] ) ) {
    // Post Content
    $descr = strip_shortcodes( $property['post_content'] );
    $descr = apply_filters('the_content', $descr);
    $descr = str_replace(']]>', ']]&gt;', $descr);
    $descr = strip_tags($descr);
    $excerpt_length = 65;
    $words = preg_split("/[\n\r\t ]+/", $descr, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
    if ( count($words) > $excerpt_length ) {
      array_pop($words);
      $descr = implode(' ', $words);
      $descr = $descr . '...';
    } else {
      $descr = implode(' ', $words);
    }
  } elseif( $attr_id == 'post_title' && !empty( $property['post_title'] ) ) {
    // Title
    $title = $property['post_title'];
  } elseif( $attr_id == 'tagline' && !empty( $property['tagline'] ) ) {
    // Tagline
    $tagline = '<span><b>' . $property['tagline'] . '</b></span><br/>';
  }else {
    // Attributes (Property Meta)
    $info .= !empty($property[$attr_id]) ? '<br>'. $attr_value .': '. $property[ $attr_id ] : '';
  }
}


echo '<table cellspacing="0" cellpadding="0" width="100%" border="0"><tr>';

if (!empty($image)) {

  echo '<td colspan="7" style="font-size:8px;height:8px;line-height:8px;">$nbsp;</td>';
  echo '</tr><tr>';
  echo '<td width="2%">$nbsp;</td>';
  echo '<td width="12%" align="left" valign="middle">' . $image . '</td>';
  echo '<td width="2%">$nbsp;</td>';
  echo '<td width="25%"><b>'. $title .'</b>'.$info . '</td>';
  echo '<td width="2%">$nbsp;</td>';
  echo '<td width="54%">'. ( isset( $tagline ) ? $tagline : '' ) . $descr .'</td>';
  echo '<td width="2%">$nbsp;</td>';
  echo '</tr><tr>';
  echo '<td colspan="7" style="font-size:8px;height:8px;line-height:8px;">$nbsp;</td>';

} else {

  echo '<td colspan="5" style="font-size:8px;height:8px;line-height:8px;">$nbsp;</td>';
  echo '</tr><tr>';
  echo '<td width="2%">$nbsp;</td>';
  echo '<td width="39%"><b>'. $title .'</b>'.$info . '</td>';
  echo '<td width="2%">$nbsp;</td>';
  echo '<td width="54%">'. ( isset( $tagline ) ? $tagline : '' ) . $descr .'</td>';
  echo '<td width="2%">$nbsp;</td>';
  echo '</tr><tr>';
  echo '<td colspan="5" style="font-size:8px;height:8px;line-height:8px;">$nbsp;</td>';

}

echo '</tr></table>';
