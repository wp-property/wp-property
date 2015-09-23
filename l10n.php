<?php
/**
 * Javascript Localization
 *
 * @version 0.1
 * @since 1.37.3.2
 * @author peshkov@UD
 * @package WP-Property
 */

$l10n = array(

  //** Edit Property page */
  'clone_property'                  => sprintf( __( 'Clone %s', 'wpp' ), WPP_F::property_label() ),
  'delete'                            => __( 'Delete', 'wpp' ),

  //** Admin Overview page */
  'show'                            => __( 'Show', 'wpp' ),
  'hide'                            => __( 'Hide', 'wpp' ),
  'featured'                        => __( 'Featured', 'wpp' ),
  'add_to_featured'                 => __( 'Add to Featured', 'wpp' ),

  //** Admin Settings page */
  'undefined_error'                 => __( 'Undefined Error.', 'wpp' ),
  'set_property_type_confirmation'  => __( 'You are about to set ALL your properties to the selected property type. Are you sure?', 'wpp' ),
  'processing'                      => __( 'Processing...', 'wpp' ),
  'geo_attribute_usage'             => __( 'Attention! This attribute (slug) is used by Google Validator and Address Display functionality. It is set automatically and can not be edited on Property Adding/Updating page.','wpp' ),

  //** Ajaxupload */
  'uploading'                       => __( 'Uploading', 'wpp' ),
  'drop_file'                       => __( 'Drop files here to upload', 'wpp' ),
  'upload_images'                   => __( 'Upload Image', 'wpp' ),
  'cancel'                          => __( 'Cancel', 'wpp' ),
  'fail'                            => __( 'Failed', 'wpp' ),

  //** Datatables Library */
  'dtables' => array(
    'first'                         => __( 'First', 'wpp' ),
    'previous'                      => __( 'Previous', 'wpp' ),
    'next'                          => __( 'Next', 'wpp' ),
    'last'                          => __( 'Last', 'wpp' ),
    'processing'                    => __( 'Processing...', 'wpp' ),
    'show_menu_entries'             => sprintf( __( 'Show %s entries', 'wpp' ), '_MENU_' ),
    'no_m_records_found'            => __( 'No matching records found', 'wpp' ),
    'no_data_available'             => __( 'No data available in table', 'wpp' ),
    'loading'                       => __( 'Loading...', 'wpp' ),
    'showing_entries'               => sprintf( __( 'Showing %s to %s of %s entries', 'wpp' ), '_START_', '_END_', '_TOTAL_' ),
    'showing_entries_null'          => sprintf( __( 'Showing % to % of % entries', 'wpp' ), '0', '0', '0' ),
    'filtered_from_total'           => sprintf( __( '(filtered from %s total entries)', 'wpp' ), '_MAX_' ),
    'search'                        => __( 'Search:', 'wpp' ),
    'display'                       => __( 'Display:', 'wpp' ),
    'records'                       => __( 'records', 'wpp' ),
    'all'                           => __( 'All', 'wpp' ),
  ),

  'feps' => array(
    'unnamed_form'                  => __( 'Unnamed Form', 'wpp' ),
    'form_could_not_be_removed_1'   => __( 'Form could not be removed because of some server error.', 'wpp' ),
    'form_could_not_be_removed_2'   => __( 'Form could not be removed because form ID is undefined.', 'wpp' ),
  ),
  
  'fbtabs' => array(
    'unnamed_canvas'                  => __( 'Unnamed Canvas', 'wpp' ),
  ),

);

