<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Wpp_Select_Combobox_Field' ) ){
  class RWMB_Wpp_Select_Combobox_Field extends RWMB_Select_Field{
    /**
     * Enqueue scripts and styles
     *
     * @return void
     */
    static function admin_enqueue_scripts(){
      wp_enqueue_style( 'field-wpp-select-combobox', ud_get_wpp_terms()->path( 'static/styles/fields/wpp-select-combobox.css' ), array(),  ud_get_wpp_terms('version'));
      wp_enqueue_script( 'field-wpp-select-combobox', ud_get_wpp_terms()->path( 'static/scripts/fields/wpp-select-combobox.js' ), array( 'jquery', 'jquery-ui-autocomplete', 'underscore' ), ud_get_wpp_terms('version'), true );
    }

    /**
     * Get field HTML
     *
     * @param mixed $meta
     * @param array $field
     *
     * @return string
     */
    static function html( $meta, $field ){
      $options = $field['options'];
      
      $field_name = $field['field_name'];
      if(substr_compare($field_name, '[]', -2) === 0){
        $field_name = substr_replace($field_name, '', -2);
      }

      $term_id  = '';
      $term_name  = '';

      if(is_array($meta) ){
        $meta = reset($meta);
      }

      if($meta){
        $term = get_term( $meta , $options['taxonomy'] );

        if($term && !is_wp_error($term)){
          $term_name = $term->name; 
          $term_id = "tID_" . $term_id;
        }
      }

      ob_start();

      ?>
      <div
        class="rwmb-field wpp-taxonomy-select-combobox wpp_ui"
        data-taxonomy="<?php echo $options['taxonomy'];?>">
        <div class="clearfix term">
          <input
              type = "text"
              class="ui-corner-left wpp-terms-input wpp-terms-term"
              autocomplete="off"
              value="<?php echo $term_name?>"
            >
          <input
              type = "hidden"
              class="wpp-terms-id-input"
              name="<?php echo $field_name;?>[0][term]"
              value="<?php echo $term_id?>"
            >
          <a tabindex="-1" title="Show All Items" class="ui-widget ui-state-default ui-button-icon-only select-combobox-toggle ui-corner-right" role="button">
            <span class="ui-button-icon-primary ui-icon ui-icon-triangle-1-s"></span>
          </a>
        </div>

        <?php if($options['type'] == 'select_tree'):?>
        <a tabindex="-1" class="assign-parent button-link" data-toggle="Unassign Parent">Assign Parent</a>
        <div class="clearfix term-parent hidden">
          <input
              type = "text"
              class="ui-corner-left wpp-terms-input wpp-terms-parent"
              autocomplete="off"
              placeholder="Parent"
            >
          <input
              type = "hidden"
              class="wpp-terms-id-input"
              name="<?php echo $field_name;?>[0][parent]"
            >
          <a tabindex="-1" title="Show All Items" class="ui-widget ui-state-default ui-button-icon-only select-combobox-toggle ui-corner-right" role="button">
            <span class="ui-button-icon-primary ui-icon ui-icon-triangle-1-s"></span>
          </a>
        </div>
        <?php endif;?>

      </div>
      <?php

      $html = ob_get_clean();



      return $html;
    }

  }
}
