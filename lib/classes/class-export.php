<?php
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if (!class_exists('UsabilityDynamics\WPP\Export')) {

    /**
     * This class holds the functionality that allows us to export our properties to an XML feed or CSV file
     *
     */
    class Export extends Scaffold {

      /**
       * Adds all required hooks
       */
      public function __construct() {

        parent::__construct();

        add_action( 'wpp_settings_help_tab', array( $this, 'help_tab' ), 10, 4 );

        add_action( 'wp_ajax_wpp_export_properties', array( $this, 'wpp_export_properties' ) );
        add_action( 'wp_ajax_nopriv_wpp_export_properties', array( $this, 'wpp_export_properties' ) );

        /** May be export properties to CSV file */
        add_action( "template_redirect", array( $this, 'maybe_export_properties_to_scv' ) );

      }

      /**
       * Converts arrays, objects, strings to XML object
       *
       * @see class XML_Serializer
       *
       * @param array $data
       * @param array $options Serializer options
       *
       * @return array|string|\WP_Error
       * @author peshkov@UD
       */
      public function convert_to_xml( $data, $options ) {

        //** An array of serializer options */
        $options = wp_parse_args( $options, array(
          'indent' => " ",
          'linebreak' => "\n",
          'addDecl' => true,
          'encoding' => 'utf-8',
          'rootName' => 'objects',
          'defaultTagName' => 'object',
          'mode' => false
        ) );

        try {

          if ( !class_exists( 'XML_Serializer' ) ) {
            set_include_path( get_include_path() . PATH_SEPARATOR . WPP_Path . 'lib/third-party/XML/' );
            @require_once 'Serializer.php';
          }

          //** If class still doesn't exist, for whatever reason, we fail */
          if ( !class_exists( 'XML_Serializer' ) ) {
            throw new \Exception( __( 'XML_Serializer could not be loaded.', 'pea' ) );
          }

          $Serializer = new \XML_Serializer( $options );

          $status = $Serializer->serialize( $data );

          if ( \PEAR::isError( $status ) ) {
            throw new \Exception( __( 'Could not convert data to XML.', 'pea' ) );
          }

          $data = $Serializer->getSerializedData();

        } catch ( \Exception $e ) {
          return new \WP_Error( 'error', $e->getMessage() );
        }

        return $data;
      }

      /**
       * This function takes all your properties and exports it as an XML feed
       *
       * @todo Improve efficiency of function, times out quickly for feeds of 500 properties. memory_limit and set_time_limit should be removed once efficiency is improved
       *
       */
      public function wpp_export_properties() {
        global $wp_properties;

        ini_set( 'memory_limit', -1 );

        $mtime = microtime();
        $mtime = explode( " ", $mtime );
        $mtime = $mtime[ 1 ] + $mtime[ 0 ];
        $starttime = $mtime;

        // Set a new path
        set_include_path( get_include_path() . PATH_SEPARATOR . WPP_Path . 'lib/third-party/XML/' );
        // Include our necessary libaries
        require_once 'Serializer.php';
        require_once 'Unserializer.php';

        $taxonomies = $wp_properties[ 'taxonomies' ];

        if ( isset( $_REQUEST[ 'limit' ] ) ) {
          $per_page = $_REQUEST[ 'limit' ];
          $starting_row = 0;
        }

        if ( isset( $_REQUEST[ 'per_page' ] ) ) {
          $per_page = $_REQUEST[ 'per_page' ];
        }

        if ( isset( $_REQUEST[ 'starting_row' ] ) ) {
          $starting_row = $_REQUEST[ 'starting_row' ];
        }

        if ( isset( $_REQUEST[ 'property_type' ] ) ) {
          $property_type = $_REQUEST[ 'property_type' ];
        } else {
          $property_type = 'all';
        }

        if ( strtolower( $_REQUEST[ 'format' ] ) == 'xml' ) {
          $xml_format = true;
        } else {
          $xml_format = false;
        }

        $wpp_query[ 'query' ][ 'pagi' ] = $starting_row . '--' . $per_page;
        $wpp_query[ 'query' ][ 'sort_by' ] = ( isset( $_REQUEST[ 'sort_by' ] ) ? $_REQUEST[ 'sort_by' ] : 'post_date' );
        $wpp_query[ 'query' ][ 'sort_order' ] = ( isset( $_REQUEST[ 'sort_order' ] ) ? $_REQUEST[ 'sort_order' ] : 'ASC' );
        $wpp_query[ 'query' ][ 'property_type' ] = $property_type;

        $wpp_query[ 'query' ] = apply_filters( 'wpp::xml::export::query', $wpp_query[ 'query' ] );

        $wpp_query = \WPP_F::get_properties( $wpp_query[ 'query' ], true );

        $results = $wpp_query[ 'results' ];

        if ( count( $results ) == 0 ) {
          die( __( 'No published properties.', ud_get_wp_property()->domain ) );
        }

        if ( $xml_format ) {

        } else {

        }

        $properties = array();

        foreach ( $results as $count => $id ) {

          //** Reserve time on every iteration. */
          set_time_limit( 0 );

          $property = \WPP_F::get_property( $id, "return_object=false&load_parent=false&get_children=false" );

          if ( $property[ 'post_parent' ] && !$property[ 'parent_gpid' ] ) {
            $property[ 'parent_gpid' ] = \WPP_F::maybe_set_gpid( $property[ 'post_parent' ] );
          }

          // Unset unnecessary data
          unset(
            $property[ 'comment_count' ],
            $property[ 'post_modified_gmt' ],
            $property[ 'comment_status' ],
            $property[ 'post_password' ],
            $property[ 'guid' ],
            $property[ 'filter' ],
            $property[ 'post_author' ],
            $property[ 'permalink' ],
            $property[ 'ping_status' ],
            $property[ 'post_modified' ],
            $property[ 'post_mime_type' ]
          );

          // Set unique site ID
          $property[ 'wpp_unique_id' ] = md5( get_site_url() . $property[ 'ID' ] );

          if(is_array($property['wpp_agents']) && count($property['wpp_agents'])){
            foreach ($property['wpp_agents'] as $key => $agent_id) {
              $agent = get_userdata($agent_id);
              if ( is_object($agent) && isset($agent->user_email) ) {
                 $property['wpp_agents'][$key] = $agent->user_email;
              }   
            }
          }
          //** Get taxonomies */
          if ( $taxonomies ) {
            foreach ( $taxonomies as $taxonomy_slug => $taxonomy_data ) {
              if ( $these_terms = wp_get_object_terms( $property[ 'ID' ], $taxonomy_slug, array( 'fields' => 'names' ) ) ) {
                $property[ 'taxonomies' ][ $taxonomy_slug ] = $these_terms;
              }
            }
          }

          $fixed_property = array();
          foreach ( $property as $meta_key => $meta_value ) {
            // Maybe Unserialize
            $meta_value = maybe_unserialize( $meta_value );
            if ( is_array( $meta_value ) || is_object( $meta_value ) ) {
              $fixed_property[ $meta_key ] = $meta_value;
              continue;
            }
            $fixed_property[ $meta_key ] = $meta_value;
          }
          $properties[ $id ] = $fixed_property;

        }

        $properties = apply_filters( 'wpp::xml::export::data', $properties );

        if ( $xml_format ) {
          $result = $this->convert_to_xml( $properties, apply_filters( 'wpp::xml::export::serializer_options', array() ) );

          /** Deprecated. peshkov@UD
          $result = json_encode( $properties );
          $result = WPP_F::json_to_xml( $result, apply_filters( 'wpp::xml::export::serializer_options', array() ) );
          //*/

          if ( !$result ) {
            die( __( 'There is an Error on trying to create XML feed.', ud_get_wp_property()->domain ) );
          }
          header( 'Content-type: text/xml; charset=utf-8' );
          header( 'Content-Disposition: inline; filename="wpp_xml_data.xml"' );
        } else {
          $result = json_encode( $properties );
          header( 'Content-type: application/json' );
          header( 'Content-Disposition: inline; filename="wpp_xml_data.json"' );
        }

        header( "Cache-Control: no-cache" );
        header( "Pragma: no-cache" );

        die( $result );
      }

      /**
       * This function generates your unique site's export feed
       *
       * @returns string URL to site's export feed
       */
      public function get_property_export_url() {
        return admin_url( 'admin-ajax.php' ) . "?action=wpp_export_properties";
      }

      /**
       * Exports all properties to CSV file
       */
      public function maybe_export_properties_to_scv() {

        if(
          !empty( $_REQUEST[ 'action' ] ) &&
          $_REQUEST[ 'action' ] == 'wpp_export_to_scv' &&
          !empty( $_REQUEST[ 'nonce' ] ) &&
          wp_verify_nonce( $_REQUEST[ 'nonce' ],'export_properties_to_scv' )
        ) {

          // output headers so that the file is downloaded rather than displayed
          header('Content-Type: text/csv; charset=utf-8');
          header('Content-Disposition: attachment; filename=properties.csv');

          $headings = array(
            'ID' => __( 'ID', ud_get_wp_property( 'domain' ) ),
            'post_title' => __( 'Title', ud_get_wp_property( 'domain' ) ),
            'post_content' => __( 'Content', ud_get_wp_property( 'domain' ) ),
            'post_date' => __( 'Date', ud_get_wp_property( 'domain' ) ),
            'post_modified' => __( 'Modified Date', ud_get_wp_property( 'domain' ) ),
            'post_parent' => __( 'Falls Under', ud_get_wp_property( 'domain' ) ),
            'menu_order' => __( 'Menu Order', ud_get_wp_property( 'domain' ) ),
            'post_author' => __( 'Author', ud_get_wp_property( 'domain' ) ),
            'property_type_label' => __( 'Property Type', ud_get_wp_property( 'domain' ) ),
          );

          $headings = array_merge( $headings, (array) ud_get_wp_property( 'property_stats', array() ) );
          $headings = array_merge( $headings, (array) ud_get_wp_property( 'property_meta', array() ) );

          foreach( (array) ud_get_wp_property( 'geo_type_attributes', array() ) as $k ) {
            $headings[$k] = \WPP_F::de_slug($k);
          }
          $headings[ 'latitude' ] = __( 'Latitude', ud_get_wp_property( 'domain' ) );
          $headings[ 'longitude' ] = __( 'Longitude', ud_get_wp_property( 'domain' ) );
          $headings[ 'permalink' ] = __( 'Permalink', ud_get_wp_property( 'domain' ) );

          // create a file pointer connected to the output stream
          $output = fopen('php://output', 'w');
          // output the column headings
          fputcsv( $output, array_values( $headings ) );

          $ids = \WPP_F::get_properties();
          $keys = array_keys( $headings );
          foreach( $ids as $id ) {
            $property = Property_Factory::get( $id, array(
              'get_children' => 'false',
              'load_gallery' => 'false',
              'load_thumbnail' => 'true',
              'load_parent' => 'false',
            ) );

            $data = array();
            foreach ( $keys as $k ) {
              $v = isset( $property[ $k ] ) ? $property[ $k ] : '';
              if( is_array( $v ) ) {
                $v = implode( ',', $v );
              }

              switch( $k ) {
                case 'post_content':
                  $v = strip_shortcodes( $v );
                  $v = apply_filters( 'the_content', $v );
                  $v = str_replace(']]>', ']]&gt;', $v);
                  $v = wp_trim_words( $v, 55, '...' );
                  break;
                case 'author':
                  $v = get_the_author_meta('display_name', $v);
                  break;
              }

              $data[$k] = $v;
            }
            fputcsv( $output, $data );
          }

          exit;

        }

      }

      /**
       * This function shows help stuff on the properties settings help tab
       */
      public function help_tab() {
        $export_url = $this->get_property_export_url();

        if ( !$export_url ) {
          return;
        }

        $export_url = $export_url . '&limit=10&format=xml';

        $this->get_template_part( 'admin/settings-help-export', array(
          'export_url' => $export_url,
        ) );
      }

    }

  }

}