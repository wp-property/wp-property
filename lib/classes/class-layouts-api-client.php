<?php
/**
 *
 */
namespace UsabilityDynamics\WPP {

  use Exception;
  use WP_Error;

  if (!class_exists('UsabilityDynamics\WPP\Layouts_API_Client')) {

    /**
     * Class Layouts_Builder
     * @package UsabilityDynamics\WPP
     */
    class Layouts_API_Client extends Scaffold
    {

      /**
       * API URL
       * @var string
       */
      private $options = array();

      /**
       * @var array
       */
      private $headers = array();

      /**
       * Layouts_API_Client constructor.
       * @param array $options
       */
      public function __construct($options = array())
      {

        $this->headers = array(
          'x-site-id' => get_site_option('ud_site_id', 'none'),
          'x-site-secret-token' => get_site_option('ud_site_secret_token', 'none'),
          'x-site-theme' => strtolower( wp_get_theme()->get( 'Name' ) ) . '/' . strtolower( wp_get_theme()->get( 'Version' ) ),
          'x-site-template' => strtolower( wp_get_theme()->get( 'Template' ) ),
          'x-site-stylesheet' => get_stylesheet(),
          'content-type' => 'application/json'
        );

        $this->options = wp_parse_args($options, array(
          'url' => false
        ));

      }

      /**
       * @return URL
       */
      public function get_url()
      {
        return $this->options['url'];
      }

      /**
       * @return Headers
       */
      public function get_headers()
      {
        return $this->headers;
      }

      /**
       *
       * Return array or error object.
       *
       * Uses "wp-property:layouts" cache key.
       *
       * @return array|\WP_Error
       */
      public function get_layouts()
      {

        $_cache = wp_cache_get( 'layouts', 'wp-property' );

        if( $_cache && is_object( $_cache ) ) {
          $_cache->_cached = true;
          return $_cache;
        }

        $res = wp_remote_get(trailingslashit($this->options['url']), array(
          'headers' => $this->headers
        ));

        if (is_wp_error($res)) {
          return $res;
        }

        $_body = json_decode( wp_remote_retrieve_body( $res ) );

        wp_cache_set( 'layouts', $_body, 'wp-property', 360 );

        return $_body->data;

      }

      /**
       * @param $id
       * @return array|\WP_Error
       */
      public function get_layout($id)
      {

        $_cache = wp_cache_get( 'layout-' . $id, 'wp-property' );

        if( $_cache && is_object( $_cache ) ) {
          $_cache->_cached = true;
          return $_cache;
        }

        $res = wp_remote_get(trailingslashit($this->options['url']) . trailingslashit($id), array(
          'headers' => $this->headers
        ));

        if (is_wp_error($res)) {
          return $res;
        }

        $_body = json_decode( wp_remote_retrieve_body( $res ) );

        if( !$_body || !is_object( $_body ) ) {
          return new WP_Error( 'layouts-error', 'Unable to get single layout.' );
        }

        wp_cache_set( 'layouts-' . $id, $_body, 'wp-property', 360 );

        return $_body->data;

      }

      /**
       * @param $id
       * @return array|\WP_Error
       */
      public function delete_layout($id)
      {
        $res = wp_remote_post(trailingslashit($this->options['url']) . trailingslashit($id), array(
          'method' => 'delete',
          'headers' => $this->headers
        ));

        if (is_wp_error($res)) return $res;

        return $res['body'];

      }

      /**
       * Update layout on server
       * @param $id
       * @param $data
       * @return array|\WP_Error
       */
      public function update_layout($id, $data)
      {

        try {
          $data['layout'] = base64_encode(json_encode($data['layout']));
          $data = json_encode($data);
        } catch (Exception $e) {
          return new \WP_Error('100', 'Could not parse query data', $data);
        }

        $res = wp_remote_post(trailingslashit($this->options['url']) . trailingslashit($id), array(
          'headers' => $this->headers,
          'body' => $data
        ));

        if (is_wp_error($res)) return $res;

        return $res['body'];

      }

      /**
       * Add layout to server
       * @param $data
       * @return array|\WP_Error
       */
      public function add_layout($data)
      {

        try {
          $data['layout'] = base64_encode(json_encode($data['layout']));
          $data = json_encode($data);
        } catch (Exception $e) {
          return new \WP_Error('100', 'Could not parse query data', $data);
        }

        $res = wp_remote_post(trailingslashit($this->options['url']), array(
          'headers' => $this->headers,
          'body' => $data
        ));

        if (is_wp_error($res)) return $res;

        return $res['body'];

      }

    }
  }
}