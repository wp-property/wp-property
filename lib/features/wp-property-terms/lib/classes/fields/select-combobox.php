<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

// Make sure "select" field is loaded
if( defined( 'RWMB_FIELDS_DIR ' ) ) {
  require_once RWMB_FIELDS_DIR . 'select.php';
}

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
      $terms = array();
      $options = $field['_options'];
      $field_name = trim($field['field_name'], '[]');

      foreach ($field['options'] as $id => $label) {
        $terms[] = array('value' => $id, 'label' => $label);
      }

      $meta     = array_values($meta);
      $term_id  = '';
      $term_name  = '';
      if(isset($meta[0])){
        $term_id = $meta[0];
        $term = get_term( $term_id , $options['taxonomy'] );
        $term_name = $term->name; 
        $term_id = "tID_" . $term_id;
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
