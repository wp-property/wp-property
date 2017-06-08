<?php

/**
 *
 */
namespace UsabilityDynamics\WPRETSC\Connectors {

  if ( !class_exists( 'UsabilityDynamics\WPRETSC\Connectors\Loader' ) ) {

    /**
     * Class Loader
     * @package UsabilityDynamics\WPRETSC\Connectors
     */
    final class Loader {

      /**
       * @var array
       */
      private $connectors = array();

      /**
       * Loader constructor.
       */
      public function __construct() {

        if ( class_exists( 'SitePress' ) ) {
          $this->connectors[] = new WPML();
        }

      }

    }

  }

}