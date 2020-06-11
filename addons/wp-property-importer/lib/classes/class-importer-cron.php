<?php
/**
 * Cron Manager
 *
 * @since 5.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Importer_Cron' ) ) {

    final class Importer_Cron
    {

      /**
       * Constructor
       * Adds all specific hooks for managing cron stuff.
       *
       */
      public function __construct()
      {
        if (!defined('WPP_XMLI_CRON_RUNNING')) {
          add_action('wpp_init', array($this, 'maybe_run_cron'));
        }
      }

      /**
       * Runs every hour to determine if there is any xmli cron job must be run.
       *
       * Features:
       * - checks xmli for cron jobs once per hour
       * - runs only one cron job per call
       *
       * @author peshkov@UD
       */
      public function maybe_run_cron()
      {
        /* Break on AJAX, XMLRPC and POST requests */
        if (!empty($_POST) || defined('DOING_AJAX') || defined('XMLRPC_REQUEST')) {
          return;
        }
        /* Run cron once per hour. */
        if (!$trnst = get_transient('wpp:xmli:alt_cron')) {
          if ($schedule = $this->get_schedule_for_cron_job()) {
            $this->spawn_cron(array(
              'hash' => $schedule['hash'],
              'request' => (!empty($schedule['alt_cron_sys_command']) && $schedule['alt_cron_sys_command'] == 'true') ? 'shell' : 'http',
            ));
          }
          set_transient('wpp:xmli:alt_cron', time(), 1 * MINUTE_IN_SECONDS);
        }

      }

      /**
       * Returns first found schedule where cron job should be run.
       *
       * @author peshkv@UD
       */
      public function get_schedule_for_cron_job()
      {
        global $wp_properties;

        if (
          empty($wp_properties['configuration']['feature_settings']['property_import']['schedules']) ||
          !is_array($wp_properties['configuration']['feature_settings']['property_import']['schedules'])
        ) {
          return false;
        }

        $schedules = $wp_properties['configuration']['feature_settings']['property_import']['schedules'];
        foreach ($schedules as $id => $schedule) {
          /* Determine if Alternative Cron Enabled */
          if (!isset($schedule['alt_cron_enabled']) || $schedule['alt_cron_enabled'] != 'true') {
            continue;
          }
          /* Determine if it's time to run Cron. */
          $timer = !empty($schedule['alt_cron_run_custom']) && is_numeric($schedule['alt_cron_run_custom']) ? $schedule['alt_cron_run_custom'] : $schedule['alt_cron_run'];
          $lastrun = !empty($schedule['lastrun']['time']) ? $schedule['lastrun']['time'] : 0;
          if ((time() - $lastrun) > $timer) {
            return $schedule;
          }
        }
        return false;
      }

      /**
       * Send request to run cron through HTTP request that doesn't halt page loading
       * or via exec.
       *
       * @author peshkov@UD
       */
      public function spawn_cron($args)
      {

        $args = wp_parse_args($args, array(
          'hash' => false, // Unique hash of XMLI schedule
          'request' => 'http', // Available values: 'http', 'shell'. eval CRON job via HTTP request or using Shell Command
        ));

        switch ($args['request']) {

          case 'http':
            $url = add_query_arg(array(
              'action' => 'do_xml_import',
              'hash' => $args['hash'],
              'cb' => rand(),
            ), ud_get_wpp_importer()->path('cron.php', 'url'));

            @wp_remote_get($url, array('sslverify'   => false, 'blocking' => false, 'headers' => array('Cache-Control' => 'private, max-age=0, no-cache, no-store, must-revalidate')));
            break;

          case 'shell':
            $cron_path = ud_get_wpp_importer()->path( 'cron.php', 'dir' );
            //@exec('nohup curl "' . $url . '" > /dev/null 2>&1 &');
            @exec( 'nohup php -q ' . $cron_path . ' do_xml_import ' . $args['hash'] . ' > /dev/null 2>&1 &' );
            break;

        }

        return true;
      }

    }

  }

}
