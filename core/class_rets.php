<?php

if( !class_exists( 'phRETS' ) ) {
  include_once( dirname( __DIR__ ) . '/third-party/phrets.php' );
}

if( !class_exists( 'WPP_phRETS' ) ) {
  /**
   * Just a wrapper for phRETS
   * which fixes different issues and can be used for data modifying.
   */
  class WPP_phRETS extends phRETS {

    /**
     * Just a copy of parent::Connect()
     * Fixes cookie file creating ( tempname() )
     */
    public function Connect( $login_url, $username, $password, $ua_pwd = "" ) {
      $this->reset_error_info();

      if( empty( $login_url ) ) {
        die( "PHRETS: Login URL missing from Connect()" );
      }
      if( empty( $username ) ) {
        die( "PHRETS: Username missing from Connect()" );
      }
      if( empty( $password ) ) {
        die( "PHRETS: Password missing from Connect()" );
      }
      if( empty( $this->static_headers[ 'RETS-Version' ] ) ) {
        $this->AddHeader( "RETS-Version", "RETS/1.5" );
      }
      if( empty( $this->static_headers[ 'User-Agent' ] ) ) {
        $this->AddHeader( "User-Agent", "PHRETS/1.0" );
      }
      if( empty( $this->static_headers[ 'Accept' ] ) && $this->static_headers[ 'RETS-Version' ] == "RETS/1.5" ) {
        $this->AddHeader( "Accept", "*/*" );
      }

      // chop up Login URL to use for later requests
      $url_parts             = parse_url( $login_url );
      $this->server_hostname = $url_parts[ 'host' ];
      $this->server_port     = ( empty( $url_parts[ 'port' ] ) ) ? 80 : $url_parts[ 'port' ];
      $this->server_protocol = $url_parts[ 'scheme' ];

      $this->capability_url[ 'Login' ] = $url_parts[ 'path' ];

      if( isset( $url_parts[ 'query' ] ) && !empty( $url_parts[ 'query' ] ) ) {
        $this->capability_url[ 'Login' ] .= "?{$url_parts['query']}";
      }

      $this->username = $username;
      $this->password = $password;

      if( !empty( $ua_pwd ) ) {
        // force use of RETS 1.7 User-Agent Authentication
        $this->ua_auth = true;
        $this->ua_pwd  = $ua_pwd;
      }

      if( empty( $this->cookie_file ) ) {
        $this->cookie_file = tempnam( sys_get_temp_dir(), "phrets" );
      }

      @touch( $this->cookie_file );

      if( !is_writable( $this->cookie_file ) ) {
        $this->set_error_info( "phrets", -1, "Cookie file \"{$this->cookie_file}\" cannot be written to.  Must be an absolute path and must be writable" );

        return false;
      }

      // start cURL magic
      $this->ch = curl_init();
      curl_setopt( $this->ch, CURLOPT_HEADERFUNCTION, array( &$this, 'read_custom_curl_headers' ) );
      if( $this->debug_mode == true ) {
        // open file handler to be used by cURL debug log
        $this->debug_log = @fopen( $this->debug_file, 'a' );

        if( $this->debug_log ) {
          curl_setopt( $this->ch, CURLOPT_VERBOSE, 1 );
          curl_setopt( $this->ch, CURLOPT_STDERR, $this->debug_log );
        } else {
          echo "Unable to save debug log to {$this->debug_file}\n";
        }
      }
      curl_setopt( $this->ch, CURLOPT_HEADER, false );
      if( $this->force_basic_authentication == true ) {
        curl_setopt( $this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
      } else {
        curl_setopt( $this->ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC );
      }
      if( $this->disable_follow_location != true ) {
        curl_setopt( $this->ch, CURLOPT_FOLLOWLOCATION, 1 );
      }
      curl_setopt( $this->ch, CURLOPT_USERPWD, $this->username . ":" . $this->password );
      curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $this->ch, CURLOPT_COOKIEFILE, $this->cookie_file );
      curl_setopt( $this->ch, CURLOPT_TIMEOUT, 0 );
      curl_setopt( $this->ch, CURLOPT_SSL_VERIFYPEER, false );

      // make request to Login transaction
      $result = $this->RETSRequest( $this->capability_url[ 'Login' ] );
      if( !$result ) {
        return false;
      }

      list( $headers, $body ) = $result;

      // parse body response
      $xml = $this->ParseXMLResponse( $body );
      if( !$xml ) {
        return false;
      }

      // log replycode and replytext for reference later
      $this->last_request[ 'ReplyCode' ] = "{$xml['ReplyCode']}";
      $this->last_request[ 'ReplyText' ] = "{$xml['ReplyText']}";

      // chop up login response
      // if multiple parts of the login response aren't found splitting on \r\n, redo using just \n
      $login_response = array();

      if( $this->server_version == "RETS/1.0" ) {
        if( isset( $xml ) ) {
          $login_response = explode( "\r\n", $xml );
          if( empty( $login_response[ 3 ] ) ) {
            $login_response = explode( "\n", $xml );
          }
        }
      } else {
        if( isset( $xml->{'RETS-RESPONSE'} ) ) {
          $login_response = explode( "\r\n", $xml->{'RETS-RESPONSE'} );
          if( empty( $login_response[ 3 ] ) ) {
            $login_response = explode( "\n", $xml->{'RETS-RESPONSE'} );
          }
        }
      }

      // parse login response.  grab all capability URLs known and ones that begin with X-
      // otherwise, it's a piece of server information to save for reference
      foreach( $login_response as $line ) {
        $name  = null;
        $value = null;

        if( strpos( $line, '=' ) !== false ) {
          @list( $name, $value ) = explode( "=", $line, 2 );
        }

        $name  = trim( $name );
        $value = trim( $value );
        if( !empty( $name ) && !empty( $value ) ) {
          if( isset( $this->allowed_capabilities[ $name ] ) || preg_match( '/^X\-/', $name ) == true ) {
            $this->capability_url[ $name ] = $value;
          } else {
            $this->server_information[ $name ] = $value;
          }
        }
      }

      // if 'Action' capability URL is provided, we MUST request it following the successful Login
      if( isset( $this->capability_url[ 'Action' ] ) && !empty( $this->capability_url[ 'Action' ] ) ) {
        $result = $this->RETSRequest( $this->capability_url[ 'Action' ] );
        if( !$result ) {
          return false;
        }
        list( $headers, $body ) = $result;
      }

      if( $this->compression_enabled == true ) {
        curl_setopt( $this->ch, CURLOPT_ENCODING, "gzip" );
      }

      if( $this->last_request[ 'ReplyCode' ] == 0 ) {
        return true;
      } else {
        $this->set_error_info( "rets", $this->last_request[ 'ReplyCode' ], $this->last_request[ 'ReplyText' ] );

        return false;
      }

    }

  }
}
