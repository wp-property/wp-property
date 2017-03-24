<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

// Make sure "select" field is loaded
if( defined( 'RWMB_FIELDS_DIR ' ) ) {
  require_once RWMB_FIELDS_DIR . 'select.php';
}

if ( ! class_exists( 'RWMB_Wpp_Select_Advanced_Field' ) ){
  class RWMB_Wpp_Select_Advanced_Field extends RWMB_Select_Field{
    /**
     * Enqueue scripts and styles
     *
     * @return void
     */
    static function admin_enqueue_scripts(){
      wp_enqueue_style( 'field-wpp-taxonomy-inherited', ud_get_wpp_terms()->path( 'static/styles/fields/wpp-select-advance.css' ), array('wp-admin'),  ud_get_wpp_terms('version'));
      wp_enqueue_script( 'wpp-select-advance', ud_get_wpp_terms()->path( 'static/scripts/fields/wpp-select-advance.js' ), array( 'jquery', 'jquery-ui-autocomplete', 'underscore' ), ud_get_wpp_terms('version'), true );
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
      global $wpp_terms_taxonomy_field_counter;
      $wpp_terms_taxonomy_field_counter++;
      $tax_counter = 0;
      $terms = array();
      $options = $field['_options'];
      foreach ($field['options'] as $id => $label) {
        $terms[] = array('value' => $id, 'label' => $label);
      }
      $field_name = trim($field['field_name'], '[]');
      ob_start();

      ?>
      <div 
        class="rwmb-field rwmb-wpp-taxonomy-wrapper" 
        data-name="<?php echo $field_name;?>" 
        data-taxonomy="<?php echo $options['taxonomy'];?>" 
        data-tax-counter="<?php echo $wpp_terms_taxonomy_field_counter;?>">
        <div class="taxsdiv">
          <div class="jaxtag">
            <div class="ui-widget clearfix">
              <input type="text" class="wpp-terms-input wpp-terms-term" size="<?php $field['size'];?>" autocomplete="off" value="">
              <input type="button" id="terms-input-auto-<?php echo $wpp_terms_taxonomy_field_counter;?>" class="button taxadd" value="Add">

              <?php if($options['type'] == 'select_tree'):?>
              <div class="clearfix"></div>
              <a tabindex="-1" class="assign-parent button-link">Assign Parent</a>
              <div class="clearfix"></div>
              <input type="text" class="wpp-terms-input wpp-terms-parent hidden" size="<?php $field['size'];?>" autocomplete="off" value="" placeholder="Parent">
              <?php endif;?>

            </div>
            <p class="howto" id="new-tag-property_feature-desc">Separate tags with commas</p>
          </div>
          <div class="tagchecklist">
            <?php
            if(is_array($meta))
              foreach ($meta as $term) {
                $term = get_term( $term , $options['taxonomy'] );
                $term_id = "tID_" . $term->term_id;
                echo "<span class='tax-tag'>";
                  echo "<a class='ntdelbutton notice-dismiss' tabindex='0'>X</a>&nbsp;{$term->name}";
                  echo "<input type='hidden' name='{$field_name}[$tax_counter][term]' value='{$term_id}' />";
                echo "</span>";
                $tax_counter++;
              }
            ?>
          </div>
        </div>
      </div>
      <?php if($wpp_terms_taxonomy_field_counter == 1):?>
      <script type="text/html" id="wpp-terms-taxnomy-template">
        <span class="tax-tag">
          <a class='ntdelbutton notice-dismiss' tabindex='0'>X</a>&nbsp;<%= label %>
          <input type='hidden' name='<%= name %>[term]' value='<%= term %>' />
          <input type='hidden' name='<%= name %>[parent]' value='<%= parent %>' />
        </span>
      </script>
      <?php endif;

      $html = ob_get_clean();
      return $html;
    }

    static function get_term($term, $terms){
      foreach ($terms as $key => $t) {
        if($term == $t['value'])
          return $t;
      }
    }

  }
}
