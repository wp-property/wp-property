<?php
/**
 * WP-Property Listing Object
 *
 * Currently not used.
 *
 * @class WP_Error
 * @version 1.0
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @since 1.38
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Listing' ) ) {

    class Listing {

      /**
       * Listing ID.
       *
       * @property $id
       * @type String
       */
      public $id;

      /**
       * Global Property ID.
       *
       * @property $gpid
       * @type String
       */
      public $gpid = '';

      /**
       * Listing Description.
       *
       *
       * @property $content
       * @type String
       */
      public $content = '';

      /**
       * Content Title.
       *
       * @static
       * @property $title
       * @type String
       */
      public $title = '';

      /**
       * Listing Excerpt / Summary.
       *
       * @static
       * @property $excerpt
       * @type String
       */
      public $excerpt = '';

      /**
       * Status.
       *
       * @static
       * @property $status
       * @type String
       */
      public $status = 'publish';

      /**
       * Parent ID.
       *
       * @static
       * @property $parent
       * @type Integer
       */
      public $parent = 0;

      /**
       * @param string $key
       * @param null   $default
       *
       * @return mixed
       */
      public function get( $key = '', $default = null ) {
        return get_post_meta( $this->id, $key );
      }

      /**
       * @param string $key
       * @param string $value
       *
       * @internal param null $default
       *
       * @return mixed
       */
      public function set( $key = '', $value = null ) {
        return update_post_meta( $this->id, $key, $value );
      }

    }

  }

}



