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

  //** Admin Overview page */
  'show'                            => __( 'Show', 'wpp' ),
  'hide'                            => __( 'Hide', 'wpp' ),
  'featured'                        => __( 'Featured', 'wpp' ),
  'add_to_featured'                 => __( 'Add to Featured', 'wpp' ),

  //** Admin Settings page */
  'undefined_error'                 => __( 'Undefined Error.', 'wpp' ),
  'set_property_type_confirmation'  => __( 'You are about to set ALL your properties to the selected property type. Are you sure?', 'wpp' ),
  'processing'                      => __( 'Processing...', 'wpp' ),

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

  //** XML Importer */
  'xmli' => array(
    'request_error'                 => __( 'Request error:', 'wpp' ),
    'evaluation_500_error'          => __( 'The source evaluation resulted in an Internal Server Error!', 'wpp' ),
    'automatically_match'           => __( 'Automatically Match', 'wpp' ),
    'unique_id_attribute'           => __( 'Unique ID attribute.', 'wpp' ),
    'select_unique_id'              => __( 'Select a unique ID attribute.', 'wpp' ),
    'settings'                      => __( 'Settings', 'wpp' ),
    'enabled_options'               => __( 'Enabled Options', 'wpp' ),
    'are_you_sure'                  => __( 'Are you sure?', 'wpp' ),
    'error_occured'                 => __( 'An error occured.', 'wpp' ),
    'save'                          => __( 'Save Configuration', 'wpp' ),
    'saved'                         => __( 'Schedule has been saved.', 'wpp' ),
    'saving'                        => __( 'Saving the XML Importer schedule, please wait...', 'wpp' ),
    'updating'                      => __( 'Updating the XML Importer schedule, please wait...', 'wpp' ),
    'updated'                       => __( 'Schedule has been updated.', 'wpp' ),
    'out_of_memory'                 => __( '500 Internal Server Error! Your hosting account is most likely running out of memory.', 'wpp' ),
    'loading'                       => __( 'Loading...', 'wpp' ),
    'please_save'                   => __( 'Please save schedule first.', 'wpp' ),
    'toggle_advanced'               => __( 'Toggle Advanced', 'wpp' ),
    'processing'                    => __( 'Processing...', 'wpp' ),
    'cannot_reload_source'          => __( 'Cannot Load Source: Reload.', 'wpp' ),
    'internal_server_error'         => __( 'Internal Server Error!.', 'wpp' ),
    'source_is_good'                => __( 'Source Is Good. Reload.', 'wpp' )
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

