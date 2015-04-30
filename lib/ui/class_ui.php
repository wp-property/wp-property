<?php
/**
 * Class WPP_UI
 *
 * @class WPP_UI
 */
class WPP_UI {

  /**
   * Loaded if this is a property page, and child properties exist.
   *
   * @method child_properties
   * @version 1.26.0
   * @author Andy Potanin <andy.potanin@twincitiestech.com>
   * @package WP-Property
   */
  static public function child_properties( $post ) {

    $children = get_posts( array(
      'post_parent' => $post->ID,
      'post_type' => 'property',
      'numberposts' => -1,
    ) );

    ?>
    <div class="wp-tab-panel">
      <ul>
        <?php  foreach ( $children as $child ) {
          echo '<li><a href="' . get_edit_post_link( $child->ID ) . '">' . $child->post_title . '</a></li>';
        } ?>
      </ul>
    </div>


  <?php

  }

  /**
   * Renders Filter Metabox ( All Properties page )
   *
   * @global type $wp_properties
   *
   * @param type $wp_list_table
   */
  static public function metabox_property_filter( $wp_list_table ) {

    $wp_list_table->search_box( 'Search', 'property' );

    $filters = WPP_F::get_search_filters();

    ?>
    <div class="misc-pub-section">
      <?php if ( !empty( $filters ) ) : ?>
        <?php foreach ( $filters as $key => $filter ) : ?>
          <?php
          //** If there are not available values we ignore filter */
          if ( empty( $filter[ 'values' ] ) || !is_array( $filter[ 'values' ] ) ) {
            continue;
          }
          ?>
          <ul class="wpp_overview_filters <?php echo $key; ?>">
            <li class="wpp_filter_section_title"><?php echo $filter[ 'label' ]; ?><a
                class="wpp_filter_show"><?php echo $key == 'post_status' ? __( 'Hide', 'wpp' ) : __( 'Show', 'wpp' ) ?></a>
            </li>
            <li class="all wpp_checkbox_filter" <?php echo $key == 'post_status' ? 'style="display:block;"' : '' ?> >
              <?php
              switch ( $filter[ 'type' ] ) {

                default:
                  break;

                case 'multi_checkbox':
                  ?>
                  <ul class="wpp_multi_checkbox">
                    <?php foreach ( $filter[ 'values' ] as $value => $label ) : ?>
                      <?php $unique_id = rand( 10000, 99999 ); ?>
                      <li>
                        <input name="wpp_search[<?php echo $key; ?>][]"
                          id="wpp_attribute_checkbox_<?php echo $unique_id; ?>" type="checkbox"
                          value="<?php echo $value; ?>"/>
                        <label for="wpp_attribute_checkbox_<?php echo $unique_id; ?>"><?php echo $label; ?></label>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                  <?php
                  break;

                case 'dropdown':
                  $unique_id = rand( 10000, 99999 );
                  ?>
                  <select id="wpp_attribute_dropdown_<?php echo $unique_id; ?>"  class="wpp_search_select_field wpp_search_select_field_<?php echo $key; ?>"  name="wpp_search[<?php echo $key; ?>]">
                    <?php foreach ( $filter[ 'values' ] as $value => $label ) : ?>
                      <option
                        value="<?php echo esc_attr( $value ); ?>" <?php echo $value == $filter[ 'default' ] ? 'selected="selected"' : '' ?> >
                        <?php echo $label; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php
                  break;

                case 'radio':
                  ?>
                  <ul>
                    <?php foreach ( $filter[ 'values' ] as $value => $label ) : ?>
                      <li>
                        <input id="radio_filter_<?php echo $value; ?>" type="radio"
                          value="<?php echo esc_attr( $value ); ?>"
                          name="wpp_search[<?php echo $key; ?>]" <?php echo( $value == $filter[ 'default' ] ? 'checked="checked"' : '' ); ?> />
                        <label for="radio_filter_<?php echo $value; ?>"><?php echo $label; ?></label>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                  <?php
                  break;

              }
              ?>
            </li>
          </ul>

        <?php endforeach; ?>
      <?php endif; ?>
      <?php do_action( 'wpp_invoice_list_filter' ); ?>
    </div>

    <div class="major-publishing-actions">
      <div class="publishing-action">
        <?php submit_button( __( 'Filter Results', 'wpp' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
      </div>
      <br class='clear'/>
    </div>

  <?php
  }

}
