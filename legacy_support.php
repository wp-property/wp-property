<?php
/**
 * Legacy Support
 *
 * This file deals with upgrading and backwards compatability issues.
 *
 * @package WP-Property
*/


//add_filter('wpp_property_overview_render', array('wpp_legacy', 'wpp_property_overview_render'));


class wpp_legacy {



/** 
   * Hook into shortcode_property_overview() as it returns property_overview results
   *
   */
    function wpp_property_overview_render($result) {
      global $wpp_query;
      //die("<pre style='color: white;'> props_atts:" . print_r($wpp_query, true). "</pre>");

      /* 
        Manually load pagination for sites that are using a custom property-overview.php template 
        because in 1.17.3 we stopped using property-pagination.php template and started using
        wpi_draw_pagination() and wpp_draw_sorter() to render pagination and sorting
      */
         
      if(file_exists(STYLESHEETPATH . "/property-overview.php") ||
        file_exists(TEMPLATEPATH . "/property-overview.php")) {
        //** User has a custom prperty-overview.php template, we inject pagination. */
        //** Issue: how do we identify property-overview.php files that are using the new style ? *
 
        $top_pagination = wpi_draw_pagination(array('return' => true, 'class' => 'wpp_top_pagination'));
        $bottom_pagination = wpi_draw_pagination(array('return' => true, 'class' => 'wpp_bottom_pagination'));
        
        //$result['result'] = $top_pagination . $result['result'] . $bottom_pagination;
        //$result['result'] = $result['result'];
        
        $new_result[] = $result['top'] . 'test' . $top_pagination . $result['result'] . $bottom_pagination . $result['bottom'];
         return $new_result;
        
      }
       return $result;
  
    }

}