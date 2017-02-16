<?php
/**
 *
 */
namespace UsabilityDynamics\WPP {

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
          'ud-site-id' => get_site_option('ud_site_id', 'none'),
          'ud-site-secret-token' => get_site_option('ud_site_secret_token', 'none')
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
       * @return array|\WP_Error
       */
      public function get_layouts()
      {

        $res = wp_remote_get(trailingslashit($this->options['url']), array(
          'headers' => wp_parse_args(array(
            'content-type' => 'application/json'
          ), $this->headers)
        ));

        if (is_wp_error($res)) return $res;

        return $res['body'];

      }

      /**
       * @param $id
       * @return array|\WP_Error
       */
      public function get_layout($id)
      {

        $res = wp_remote_get(trailingslashit($this->options['url']) . trailingslashit($id), array(
          'headers' => wp_parse_args(array(
            'content-type' => 'application/json'
          ), $this->headers)
        ));

        if (is_wp_error($res)) return $res;

        return $res['body'];

      }

      /**
       * @param $id
       * @return array|\WP_Error
       */
      public function delete_layout($id)
      {
        $res = wp_remote_post(trailingslashit($this->options['url']) . trailingslashit($id), array(
          'method' => 'delete',
          'headers' => wp_parse_args(array(
            'content-type' => 'application/json'
          ), $this->headers)
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
        } catch (\Exception $e) {
          return new \WP_Error('100', 'Could not parse query data', $data);
        }

        $res = wp_remote_post(trailingslashit($this->options['url']) . trailingslashit($id), array(
          'headers' => wp_parse_args(array(
            'content-type' => 'application/json'
          ), $this->headers),
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
        } catch (\Exception $e) {
          return new \WP_Error('100', 'Could not parse query data', $data);
        }

        $res = wp_remote_post(trailingslashit($this->options['url']), array(
          'headers' => wp_parse_args(array(
            'content-type' => 'application/json'
          ), $this->headers),
          'body' => $data
        ));

        if (is_wp_error($res)) return $res;

        return $res['body'];

      }

    }
  }
}