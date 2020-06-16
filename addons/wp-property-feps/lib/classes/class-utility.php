<?php

/**
 * Helper Functions List
 *
 * @class Utility
 */

namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\FEPS_Utility')) {

    class FEPS_Utility {

      /**
       * Wrapper for wp_create_nonce().
       * Creates a cryptographic token tied to a specific action, user, and window of time.
       */
      static public function generate_nonce( $action = -1 ) {
        add_filter( 'nonce_user_logged_out', array( __CLASS__, 'filter_nonce_user_logged_out' ) );
        $nonce = wp_create_nonce( $action );
        remove_filter( 'nonce_user_logged_out', array( __CLASS__, 'filter_nonce_user_logged_out' ) );
        return $nonce;
      }

      /**
       * Wrapper for wp_verify_nonce().
       * Verify that correct nonce was used with time limit.
       */
      static public function verify_nonce( $nonce, $action = false ) {
        add_filter( 'nonce_user_logged_out', array( __CLASS__, 'filter_nonce_user_logged_out' ) );
        $boolean = wp_verify_nonce( $nonce, $action );
        remove_filter( 'nonce_user_logged_out', array( __CLASS__, 'filter_nonce_user_logged_out' ) );
        return $boolean;
      }

      /**
       * Filter 'nonce_user_logged_out'.
       * Adds FEPS specific phrase to nonce hash instead of uid if user is not logged in.
       */
      static public function filter_nonce_user_logged_out( $uid ) {
        return 'wpp_feps_uid';
      }

    }

  }
}