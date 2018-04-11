<?php
/**
 * Bootstrap
 *
 * @since 0.2.0
 */
namespace UsabilityDynamics\WPRETSC {

  use UsabilityDynamics\SAAS_UTIL\Register;

  if( !class_exists( 'UsabilityDynamics\WPRETSC\Bootstrap' ) ) {

    final class Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {
      
      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type UsabilityDynamics\WPRETSC\Bootstrap object
       */
      protected static $instance = null;

      /**
       * @var string
       */
      public $logfile = 'wp-content/rets-log.log';

      /**
       * @var string
       */
      public $debug_file = 'wp-content/rets-debug.log';

      /**
       * Returns the version of plugin
       *
       * @return string
       */
      public function get_version() {
        return $this->args['version'];
      }

      /**
       * Instantaite class.
       */
      public function init() {

        // Initialize XML-RPC handler
        new XMLRPC();

        // Initialize Media handler
        new Media();

        // Register Product with SaaS Services.
        if( class_exists( 'UsabilityDynamics\SAAS_UTIL\Register' ) && $this->get_schema( "extra.saasProduct", false ) ) {
          Register::product( $this->get_schema( "extra.saasProduct" ), array(
            "name" => $this->name,
            "slug" => $this->slug,
            "version" => $this->args[ "version" ],
            "type" => "plugin"
          ) );
        }

        // 3d-party compatibility
        new Connectors\Loader();

        // AJAX
        new Ajax();

        add_action( 'wp_dashboard_setup', function(){

          if( defined( 'RETSCI_FEATURE_FLAG_DASHBOARD_WIDGET' ) && RETSCI_FEATURE_FLAG_DASHBOARD_WIDGET ) {
            new Widget();
          }

        } );

        add_action('init', function() {

          // Needed for import associationa and tracking of what schedule a listing came from
          register_taxonomy( 'rets_schedule', array( 'property' ), array(
            'hierarchical'      => false,
            //'update_count_callback' => null,
            'labels'            => array(
              'name'              => _x( 'Schedules', 'taxonomy general name', ud_get_wp_rets_client()->domain ),
              'singular_name'     => _x( 'Schedule', 'taxonomy singular name', ud_get_wp_rets_client()->domain ),
              'search_items'      => __( 'Search Schedules', ud_get_wp_rets_client()->domain ),
              'all_items'         => __( 'All Schedules', ud_get_wp_rets_client()->domain ),
              'parent_item'       => __( 'Parent Schedule', ud_get_wp_rets_client()->domain ),
              'parent_item_colon' => __( 'Parent Schedule:', ud_get_wp_rets_client()->domain ),
              'edit_item'         => __( 'Edit Schedule', ud_get_wp_rets_client()->domain ),
              'update_item'       => __( 'Update Schedule', ud_get_wp_rets_client()->domain ),
              'add_new_item'      => __( 'Add New Schedule', ud_get_wp_rets_client()->domain ),
              'new_item_name'     => __( 'New Schedule Name', ud_get_wp_rets_client()->domain ),
              'menu_name'         => __( 'Schedules' ),
            ),
            'show_ui'           => true,
            'show_in_menu'      => false,
            'show_admin_column' => false,
            'meta_box_cb' => false,
            'query_var'         => false,
            'rewrite'           => false
          ) );

          add_shortcode('wp-rets-client', function() {
            return '<!--Delivered by rets.ci.-->';
          });

        });

        /**
         * Delete Elasticsearch documents when RETS properties are deleted.
         *
         * @todo: we must not remove property on ES directly! Implement RETS API on `api.rets.ci` for it
         */
        add_action( 'before_delete_post', function( $post_id ) {

          // Do nothing if does not have a "rets_index"
          if( !$_rets_index = get_post_meta( $post_id, 'rets_index', true )) {
            return;
          }

          if( !defined( 'RETS_ES_LINK' ) ) {
            return;
          }

          // temporary hack to get post deletion/updates to work faster globally
          remove_filter( 'transition_post_status', '_update_term_count_on_transition_post_status', 10 );

          // this is a fire-and-forget event, we should be recording failure son our end to keep the WP process quicker
          wp_remote_request( trailingslashit( RETS_ES_LINK ) . $_rets_index . '/property/' . $post_id, array(
            'method' => 'DELETE',
            'blocking' => false
          ));

        });

      }

      /**
       * Plugin Activation
       *
       */
      public function activate() {}

      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {}

      /**
       * Determine if Utility class contains missed function
       * in other case, just return NULL to prevent ERRORS
       *
       * @author peshkov@UD
       * @param $name
       * @param $arguments
       * @return mixed|null
       */
      public function __call($name, $arguments) {
        if (is_callable(array("\\UsabilityDynamics\\WPRETSC\\Utility", $name))) {
          return call_user_func_array(array("\\UsabilityDynamics\\WPRETSC\\Utility", $name), $arguments);
        } else {
          return NULL;
        }
      }

    }

  }

}
