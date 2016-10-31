<?php

/**
 * Compatibility connector for WPML
 */
namespace UsabilityDynamics\WPRETSC\Connectors {

  if ( !class_exists( 'UsabilityDynamics\WPRETSC\Connectors\WPML' ) ) {

    /**
     * Class WPML
     * @package UsabilityDynamics\WPRETSC\Connectors
     */
    final class WPML {

      /**
       * @var bool
       */
      private $trid_to_be_removed = array();

      /**
       * WPML constructor.
       */
      public function __construct() {

        add_action( 'wrc_property_published', array( $this, 'add_other_languages' ) );
        add_action( 'wrc_before_property_deleted', array( $this, 'prepare_remove_other_languages' ) );
        add_action( 'wrc_property_deleted', array( $this, 'remove_other_languages' ) );

      }

      /**
       * Duplicate post to other available languages when added
       * @param $master_post_id
       */
      public function add_other_languages( $master_post_id ) {

        global $sitepress;

        $master_post = get_post( $master_post_id );

        if ( 'auto-draft' === $master_post->post_status || 'revision' === $master_post->post_type ) {
          return;
        }

        $active_langs = $sitepress->get_active_languages();

        foreach ( $active_langs as $lang_to => $one ) {

          $trid      = $sitepress->get_element_trid( $master_post->ID, 'post_' . $master_post->post_type );
          $lang_from = $sitepress->get_source_language_by_trid( $trid );

          if ( $lang_from == $lang_to ) {
            continue;
          }

          $sitepress->make_duplicate( $master_post_id, $lang_to );
        }

      }

      /**
       * Temporary store trid for future reference
       * @param $post_id
       */
      public function prepare_remove_other_languages( $post_id ) {
        global $sitepress;
        $this->trid_to_be_removed[ $post_id ] = $sitepress->get_element_trid( $post_id, 'post_property' );
      }

      /**
       * Delete other translations and duplicated posts when original removed
       * @param $post_id
       */
      public function remove_other_languages( $post_id ) {
        global $sitepress;

        $translations = $sitepress->get_element_translations( $this->trid_to_be_removed[$post_id], 'post_property' );

        if ( !empty( $translations ) && is_array( $translations ) ) {
          foreach( $translations as $translation ) {
            wp_delete_post( $translation->element_id, 1 );
          }
        }

        $sitepress->delete_element_translation( $this->trid_to_be_removed[$post_id], 'post_property' );
      }

    }

  }

}