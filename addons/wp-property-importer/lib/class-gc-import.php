<?php
/**
 * The import class for Google Spreadsheets
 *
 * @version 1.0
 */

class gc_import {

  /** Class variable for gdata array - it contains all the gdata variables required */
  var $gdata = array();

  /*
   * This function parses a URL for the Google Spreadsheet Key
   * @since 1.0
   * @param string $url The spreadsheet URL
   * @return mixed The spreadsheet key or false if not a valid url
   */
  public function parse_spreadsheet_key( $url ) {
    /** Example URL: https://spreadsheets.google.com/ccc?key=0AmNnyiAqu-JBdDhRMG16a09MN3d5OXJIcUR4M3o4a3c&hl=en#gid=0 */
    /** Example URL: https://docs.google.com/spreadsheets/d/1jVAxgL94u4Ab-dEeqi0wJ3ZMTyoQ7ylHkH2fU2p5DMA/edit#gid=0 */
    if( !preg_match( "/^.*key=([^#&]*)/i", $url, $t ) ) {
      throw new Exception( "Invalid Google Spreadsheet URL. Remember to copy and paste directly from your address bar when viewing a Google Spreadsheet." );
    }
    return ( $t[ 1 ] );
  }

  /*
   * This function parses a URL for the Google worksheet ID
   * @since 1.0
   * @param string $url The spreadsheet URL
   * @return string The worksheet ID
   */
  public function parse_worksheet_key( $url ) {
    /** Example URL: https://spreadsheets.google.com/feeds/worksheets/0AmNnyiAqu-JBdDhRMG16a09MN3d5OXJIcUR4M3o4a3c/private/full/od6 */
    if( !preg_match( "/^.*\/(.*)$/i", $url, $t ) ) {
      throw new Exception( "Invalid Google Spreadsheet Worksheet key." );
    }
    return ( $t[ 1 ] );
  }

  /*
   * This function is an funcition that takes a spreadsheet and grabs the worksheets in it
   * @param string $url The URL of the spreadsheet
   * @return mixed An assoc. array of worksheet and ID's / Or false upon error
   * @since 1.0
   */
  public function get_worksheets( $url ) {
    try {
      /** Parse the key */
      $spreadsheet_key = $this->parse_spreadsheet_key( $url );

      /** Connect to Gdata service */
      $this->gdata_connect();

      /** Setup the query */
      $query = new Zend_Gdata_Spreadsheets_DocumentQuery();
      $query->setSpreadsheetKey( $spreadsheet_key );
      $feed = $this->gdata[ 'ss_service' ]->getWorksheetFeed( $query );

      /** Build the final array */
      $ret = array();
      foreach( $feed->entries AS $entry ) {
        $title = $entry->getTitle();
        $ret[ $this->parse_worksheet_key( $entry->getId() ) ] = $title->__toString();
      }
      return ( $ret );
    } catch ( Exception $e ) {
      $err = $e->getMessage();
      return false;
    }
  }

  /**
   * This funciton uses the locally stored gdata information to connect to the service, we assume
   * the try/catch block is outside of this function
   * @since 1.0
   * @param string $user
   * @param string $pass
   * @return boolean True or false based upon connection
   */
  public function gdata_connect( $user = "", $pass = "" ) {
    if( empty( $user ) || empty( $pass ) ) return false;
    /** Connect to the Gdata service */
    if( !isset( $this->gdata[ 'ss_service' ] ) || empty( $this->gdata[ 'ss_service' ] ) ) {
      $this->gdata[ 'service' ] = Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME;
      $this->gdata[ 'client' ] = Zend_Gdata_ClientLogin::getHttpClient( $user, $pass, $this->gdata[ 'service' ] );
      $this->gdata[ 'ss_service' ] = new Zend_Gdata_Spreadsheets( $this->gdata[ 'client' ] );
      return true;
    }
  }

  /**
   * This fuction is for debugging purposes, it returns the fieldnames
   * @since 1.0
   * @param string $url The URL of the spreadsheet
   * @param string $ws_id The ID of the worksheet
   * @return mixed False if failure, array if success
   */
  public function get_field_names( $url, $ws_id ) {
    try {
      $this->gdata_connect();
      $fields = array();

      //Setup the query
      $query = new Zend_Gdata_Spreadsheets_ListQuery();
      $query->setSpreadsheetKey( $this->parse_spreadsheet_key( $url ) );
      $query->setWorksheetId( $ws_id );
      $listFeed = $this->gdata[ 'ss_service' ]->getListFeed( $query );

      $rowData = $listFeed->entries[ 0 ]->getCustom();
      foreach( $rowData as $customEntry ) $fields[ ] = $customEntry->getColumnName();

      return $fields;
    } catch ( Exception $e ) {
      $err[ ] = $e->getMessage();
      return false;
    }
  }

}
