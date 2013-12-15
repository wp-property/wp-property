<?php
if ( !class_exists( 'UD_Asset' ) ) {

  /**
   * UD Assets Compiler Functions
   *
   * Description: Assets Compiler
   *
   * Notice: It's not static class. Object should be initialized
   *
   * @author team@UD
   * @version 0.1.1
   * @package UD
   * @subpackage Functions
   */
  class UD_Asset {

    /**
     * The list of assets.
     *
     * @var type
     */
    private $assets = array();

    /**
     * Recompile assets in any case.
     * If false $this->monitor is used
     *
     * @var boolean
     */
    private $recompute = false;

    /**
     * Recompile assets only if input data was modified and if $this->recompute false
     *
     * @var boolean
     */
    private $monitor = false;

    /**
     * Pathes to all required libraries
     *
     * @var array
     */
    private $pathes = array(
      'jsmin' => false, // JSMin library to minify JS
      'lessc' => false, // Less PHP library. See https://github.com/leafo/lessphp
    );

    /**
     * Prefix is used in dynamic asset's permalink
     *
     * @var string
     */
    private $prefix = 'ud';

    /**
     * Constructor.
     * All pathes to libraries must be defined here.
     * Should be called before init, because it adds specific hooks
     *
     * @param mixed $args
     *
     * @author peshkov@UD
     */
    function __construct( $args = array() ) {
      global $wp;

      $args = wp_parse_args( $args );

      foreach ( $args as $k => $v ) {
        switch ( $k ) {
          case 'assets':
            $this->set_assets( $v );
            break;
          case 'recompute':
            $this->recompute = $v;
            break;
          case 'monitor':
            $this->monitor = $v;
            break;
          case 'prefix':
            $this->prefix = !empty( $v ) && is_string( $v ) ? $v : $this->prefix;
            break;
          case 'pathes':
            if ( is_array( $v ) ) {
              foreach ( $v as $lib => $path ) {
                if ( file_exists( $path ) && in_array( $lib, array( 'jsmin', 'lessc' ) ) ) {
                  $this->pathes[ $lib ] = $path;
                }
              }
            }
            break;
        }
      }

      //** Rewrite Vars */
      $wp->add_query_var( $this->prefix . '_asset' );

      //** Updates all WPP rewrite rules on flush_rewrite_rules() */
      add_action( 'rewrite_rules_array', array( $this, '_rewrite_rules' ), 20 );

      //** Handle high-level routing */
      add_filter( 'parse_request', array( $this, '_parse_request' ) );

    }

    /**
     * Get the list of all assets
     *
     * @author peshkov@UD
     */
    function get_assets() {
      return $this->assets;
    }

    /**
     * Sets assets
     *
     * @author peshkov@UD
     *
     * @param $assets
     *
     * @return array
     */
    function set_assets( $assets ) {
      $this->assets = array();
      $permalink = ( '' != get_option( 'permalink_structure' ) ) ? true : false;

      foreach ( $assets as $k => $v ) {
        $v = array_merge( array(
          'file' => false,
          'type' => false,
          'scope' => 'default', // Available data: 'default','cdn'
          'url' => false,
          'compile_options' => false
        ), (array) $v );

        switch ( $v[ 'scope' ] ) {

          case 'default':
            //** Ignore WPP asset if any required param is missed. */
            if ( empty( $v[ 'file' ] ) || empty( $v[ 'type' ] ) ) {
              continue;
            }
            //** Set url to asset */
            if ( empty( $v[ 'url' ] ) ) {
              $scheme = is_ssl() ? 'https' : null;
              $v[ 'url' ] = $permalink ? home_url( "{$this->prefix}_asset/{$k}", $scheme ) : home_url( "index.php?{$this->prefix}_asset={$k}", $scheme );
            }
            break;

          case 'cdn':
            //** Ignore CDN asset if url is not set. */
            if ( empty( $v[ 'url' ] ) ) {
              continue;
            }
            break;

        }

        $this->assets[ $k ] = $v;
      }

      //echo "<pre>";print_r( $this->assets );echo "</pre>";die();

      return $this->assets;

    }

    /**
     * Get URL for asset
     *
     * @param $asset
     *
     * @return bool
     */
    function get_dynamic_asset_url( $asset ) {
      $url = false;
      if ( is_array( $this->assets[ $asset ] ) ) {
        $url = $this->assets[ $asset ][ 'url' ];
      }
      return $url;
    }

    /**
     * Returns compiled asset's data.
     *
     * @author peshkov@UD
     *
     * @param $asset
     * @param array $args
     *
     * @return array|bool|type|WP_Error
     */
    function get_dynamic_asset( $asset, $args = array() ) {

      $args = wp_parse_args( $args, array(
        'recompute' => $this->recompute,
        'monitor' => $this->monitor,
      ) );

      if ( empty( $this->assets[ $asset ] ) ) {
        return new WP_Error( __METHOD__, 'Asset doesn\'t exist' );
      }

      $data = false;
      $_asset = $this->assets[ $asset ];

      switch ( $_asset[ 'type' ] ) {
        case 'css':
          $mime_type = 'text/css';
          break;
        case 'js':
          $mime_type = 'text/javascript';
          break;
        default:
          return new WP_Error( __METHOD__, "Type '{$_asset['type']}' is not supported" );
          break;
      }

      switch ( $_asset[ 'scope' ] ) {

        /**
         * Returns local compiled asset.
         * It file doesn't exist we try to compile it.
         */
        case 'default':

          if ( !empty( $_asset[ 'compile_options' ] ) && ( !file_exists( $_asset[ 'file' ] ) || $args[ 'monitor' ] ) ) {
            $result = $this->compile_asset( $asset, $args );
            if ( is_wp_error( $result ) ) {
              return $result;
            }
          } else if ( !$_asset[ 'compile_options' ] && !file_exists( $_asset[ 'file' ] ) ) {
            return new WP_Error( __METHOD__, 'Asset doesn\'t exist and can not be compiled.' );
          }

          //** Be sure that file exists on this step */
          if ( !file_exists( $_asset[ 'file' ] ) ) {
            return new WP_Error( __METHOD__, 'Asset doesn\'t exist' );
          }

          $data = array(
            'data' => file_get_contents( $_asset[ 'file' ] ),
            'updated' => filemtime( $_asset[ 'file' ] ),
            'mime_type' => $mime_type,
          );

          break;

        /**
         * Returns CDN Media Item
         */
        case 'cdn':
          // @todo: finish implementation for CDN assets. peshkov@UD
          wp_die( 'WIP. Media Item Handler.' );
          break;

      }

      return $data;

    }

    /**
     * Recompiles all assets
     *
     * @param boolean $hard. Recompute all assets or just recompile modified ones
     *
     * @author peshkov@Ud
     */
    function recompile_all_assets( $hard = false ) {
      foreach ( $this->assets as $asset => $data ) {
        $this->compile_asset( $asset, array(
          'recompute' => $hard ? true : false,
          'monitor' => true,
        ) );
      }
    }

    /**
     * Compiles assets ( css, js )
     *
     * @see WPP_Config::get_assets();
     *
     * @param string $asset
     * @param array $args
     *
     * @return type
     * @author odokienko@UD
     * @author peshkov@UD
     */
    function compile_asset( $asset, $args = array() ) {

      $args = wp_parse_args( $args, array(
        'recompute' => $this->recompute,
        'monitor' => $this->monitor,
      ) );

      if ( empty( $this->assets[ $asset ] ) ) {
        return new WP_Error( __METHOD__, 'Asset doesn\'t exist' );
      }

      $_asset = $this->assets[ $asset ];

      if ( $_asset[ 'scope' ] != 'default' ) {
        return new WP_Error( __METHOD__, 'Asset\'s scope should be default to proceed.' );
      }

      $options = array_merge( $_asset[ 'compile_options' ], array(
        'output' => $_asset[ 'file' ],
        'recompute' => $args[ 'recompute' ]
      ) );

      $result = false;

      if ( !file_exists( $_asset[ 'file' ] ) || $args[ 'monitor' ] ) {
        switch ( $_asset[ 'type' ] ) {
          case 'css':
            $result = $this->compile_less( $options );
            break;
          case 'js':
            $result = $this->compile_js( $options );
            break;
        }
      }

      if ( !$result ) {
        return new WP_Error( __METHOD__, 'Monitoring is turned off or file already exists.' );
      }

      return $result;
    }

    /**
     * Builds CSS via LessPHP
     *
     * @todo Need to handle LESS output when directory is not writable same as JS Compiling. - potanin@UD 11/27/2012
     * @source https://github.com/leafo/lessphp
     * @author potanin@UD
     * @author peshkov@UD
     */
    function compile_less( $args = array() ) {

      if ( !class_exists( 'lessc' ) ) {
        if ( !$this->pathes[ 'lessc' ] ) {
          return new WP_Error( __METHOD__, 'Unable to load LESS' );
        }
        include_once $this->pathes[ 'lessc' ];
      }

      $args = wp_parse_args( $args, array(
        'input' => '',
        'output' => '',
        'formatter' => 'lessjs', // lessjs|compressed|classic
        'recompute' => $this->recompute,
        'variables' => array(),
      ) );

      if ( !$args[ 'input' ] || !$args[ 'output' ] ) {
        return new WP_Error( __METHOD__, 'Missed Arguments' );
      }

      try {

        $transient_key = MD5( (string) $args[ 'input' ] . (string) $args[ 'output' ] );

        if ( is_file( $args[ 'output' ] ) && !$args[ 'recompute' ] && $transient = get_transient( 'wpp::less::' . $transient_key ) ) {
          //** Probably we don't need to recompile less. Let's check to be sure */
          //** Check if we have the same arguments to be sure that all compiled files and variables are the same. */
          $dif1 = array_diff_assoc( $args[ 'variables' ], $transient[ 'variables' ] );
          $dif2 = array_diff_assoc( $transient[ 'variables' ], $args[ 'variables' ] );
          //** Get the latest time of files changes */
          $max_fmt = 0;
          foreach ( (array) $transient[ 'files' ] as $lfile ) {
            if ( !file_exists( $lfile ) ) {
              $max_fmt = 0;
              break;
            }
            $fmt = filemtime( $lfile );
            $max_fmt = $max_fmt < $fmt ? $fmt : $max_fmt;
          }

          if ( empty( $dif1 ) && empty( $dif2 ) && $max_fmt && filemtime( $args[ 'output' ] ) > $max_fmt ) {
            return array(
              'output' => $args[ 'output' ],
              'size' => $this->_get_filesize( $args[ 'output' ] ),
              'updated' => filemtime( $args[ 'output' ] )
            );
          }
        }

        $less = new lessc;
        $less->setVariables( (array) $args[ 'variables' ] );
        $less->setFormatter( $args[ 'formatter' ] );

        $cache = $less->cachedCompile( $args[ 'input' ] );

        $dirname = dirname( $args[ 'output' ] );
        if ( !is_dir( $dirname ) ) {
          wp_mkdir_p( $dirname );
        }

        if ( isset( $cache[ 'compiled' ] ) && is_writable( $dirname ) && file_put_contents( $args[ 'output' ], $cache[ 'compiled' ] ) ) {
          //** Set transient which is needed to determine if we need to recompile less file */
          set_transient( 'wpp::less::' . $transient_key, array(
            'variables' => $args[ 'variables' ],
            'files' => array_keys( (array) $cache[ 'files' ] ),
          ) );
        } else {
          throw new Exception( 'No file created.' );
        }

      } catch ( exception $error ) {
        return new WP_Error( __METHOD__, $error->getMessage() );
      }

      return array(
        'output' => $args[ 'output' ],
        'size' => $this->_get_filesize( $args[ 'output' ] ),
        'updated' => filemtime( $args[ 'output' ] ),
      );

    }

    /**
     * Process Uncompressed JavaScript File and Create Minified Version
     *
     * @author potanin@UD
     */
    function compile_js( $args = '' ) {

      $args = array_filter( wp_parse_args( $args, array(
        'input' => array(),
        'output' => '',
        'recompute' => $this->recompute,
        'monitor' => $this->monitor,
      ) ) );

      $js = array();

      foreach ( (array) $args[ 'input' ] as $path ) {
        if ( !file_exists( $path ) ) {
          continue;
        }
        $js[ basename( $path ) ] = file_get_contents( $path );
        $args[ 'updated' ][ basename( $path ) ] = filemtime( $path );
      }

      if ( is_file( $args[ 'output' ] ) && !$args[ 'recompute' ] && ( filemtime( $args[ 'output' ] ) >= max( $args[ 'updated' ] ) ) ) {
        return array(
          'output' => $args[ 'output' ],
          'size' => $this->_get_filesize( $args[ 'output' ] ),
          'updated' => filemtime( $args[ 'output' ] )
        );
      }

      $js = implode( '', (array) $js );

      $js = $this->minify_js( $js, array( 'engine' => 'google_closure' ) );

      if ( is_wp_error( $js ) ) {
        return $js;
      }

      $dirname = dirname( $args[ 'output' ] );
      if ( !is_dir( $dirname ) ) {
        wp_mkdir_p( $dirname );
      }

      if ( !is_writable( $dirname ) || !file_put_contents( $args[ 'output' ], $js ) ) {
        return new WP_Error( __METHOD__, 'No file created' );
      }

      return array(
        'output' => $args[ 'output' ],
        'size' => $this->_get_filesize( $args[ 'output' ] ),
        'updated' => filemtime( $args[ 'output' ] ),
      );

    }

    /**
     * Minify JavaScript
     *
     * Uses third-party JSMin if class isn't declared.
     * If WP3 is detected, class not loaded to avoid footer warning error.
     * If for some reason W3_Plugin is active, but JSMin is not found,
     * we load ours to avoid breaking property maps.
     *
     * @todo Add Google Cosure API call.
     * @updated 2.0
     * @since 1.06
     */
    function minify_js( $data = '', $args = array() ) {

      $args = wp_parse_args( $args, array(
        'engine' => 'jsmin'
      ) );

      switch ( $args[ 'engine' ] ) {

        case 'jsmin':

          if ( class_exists( 'W3_Plugin' ) && file_exists( WP_PLUGIN_DIR . '/w3-total-cache/lib/Minify/JSMin.php' ) ) {
            include_once WP_PLUGIN_DIR . '/w3-total-cache/lib/Minify/JSMin.php';
          } elseif ( !empty( $this->pathes[ 'jsmin' ] ) ) {
            include_once $this->pathes[ 'jsmin' ];
          }

          if ( !class_exists( 'JSMin' ) ) {
            return new WP_Error( __METHOD__, 'Class JSMin is undefined' );
          }

          $data = JSMin::minify( $data );

          break;

        case 'google_closure':

          $_post = wp_remote_post( 'http://closure-compiler.appspot.com/compile', array(
            'body' => wp_parse_args( $args[ 'params' ], array(
              'js_code' => $data,
              'compilation_level' =>
              'SIMPLE_OPTIMIZATIONS',
              'output_format' => 'json',
              'output_info' => 'compiled_code'
            ) )
          ) );

          if ( is_wp_error( $_post ) ) {
            return $_post;
          }

          $response = json_decode( $_post[ 'body' ] );

          /** Check if we have success response from google closure, if not - we try to use jsmin library */
          $data = $response->compiledCode ? $response->compiledCode : $this->minify_js( $data, array( 'engine' => 'jsmin' ) );

          break;

      }

      return $data;

    }

    /**
     * Rewrite Rules - called only on flush.
     *
     * @action rewrite_rules_array ( 20 )
     * @author peshkov@UD
     */
    public function _rewrite_rules( $rules ) {
      //* Add rewrite rule for assets */
      $rules = array( "{$this->prefix}_asset/(.+?)/?$" => "index.php?{$this->prefix}_asset=\$matches[1]" ) + $rules;
      return $rules;
    }

    /**
     * Returns Frontend Data Model and Scripts. Fallback when a file cannot be saved to disk.
     *
     * @author peshkov@UD
     * @author odokienko@UD
     */
    public function _parse_request( $query ) {
      if ( !empty( $query->query_vars[ "{$this->prefix}_asset" ] ) ) {
        $result = $this->get_dynamic_asset( $query->query_vars[ "{$this->prefix}_asset" ] );
        if ( !is_wp_error( $result ) && is_array( $result ) ) {
          header( 'Content-Length: ' . strlen( $result[ 'data' ] ) );
          header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $result[ 'updated' ] ) . ' GMT' );
          header( 'ETag: ' . md5( $result[ 'updated' ] ) );
          header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );
          header( 'Content-Type: ' . $result[ 'mime_type' ] );
          header( 'X-Content-Type-Options: nosniff' );
          die( $result[ 'data' ] );
        } elseif ( is_wp_error( $result ) ) {
          do_action( 'ud::asset::error', $result );
        }
        die();
      }
    }

    /**
     * Get filesize of a file.
     */
    private function _get_filesize( $file ) {
      if ( !is_file( $file ) ) {
        return '';
      }
      $bytes = filesize( $file );
      $s = array( 'b', 'Kb', 'Mb', 'Gb' );
      $e = floor( log( $bytes ) / log( 1024 ) );
      return sprintf( '%.2f ' . $s[ $e ], !empty( $bytes ) ? ( $bytes / pow( 1024, floor( $e ) ) ) : false );
    }

  }

}