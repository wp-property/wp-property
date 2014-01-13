<?php
/**
 * WP-Property Requires
 *
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @since 2.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Requires' ) ) {

    class Requires extends \UsabilityDynamics\Requires {

      static function define( $args ) {

        // Register UDX Libraries.
        // wp_register_script( 'udx.requires',       '//cdn.udx.io/udx.requires.js' );
        // wp_register_script( 'udx.knockout',       '//cdn.udx.io/knockout.js' );
        // wp_register_script( 'udx.utility.cookie', '//cdn.udx.io/utility.cookie.js' );
        // wp_register_script( 'udx.utility.md5',    '//cdn.udx.io/utility.md5.js' );

        // Register WP-Property Global Libraries.
        /*
        wp_register_script( 'wpp.global', WPP_URL . 'scripts/wpp.global.js', array( 'jquery', 'wpp.localization', 'udx.requires' ), WPP_Version );
        wp_register_script( 'wpp.localization', get_bloginfo( 'wpurl' ) . '/wp-admin/admin-ajax.php?action=wpp_js_localization', array(), WPP_Version );

        // Register WP-Property Admin Libraries.
        wp_register_script( 'wpp.admin', WPP_URL . 'scripts/wpp.admin.global.js', array( 'jquery', 'wpp.global', 'wpp.localization' ), WPP_Version );
        wp_register_script( 'wpp.admin.modules', WPP_URL . 'scripts/wpp.admin.modules.js', array( 'wpp.localization', 'udx.requires' ), WPP_Version );
        wp_register_script( 'wpp.admin.settings', WPP_URL . 'scripts/wpp.admin.settings.js', array( 'wpp.localization', 'udx.requires' ), WPP_Version );
        wp_register_script( 'wpp.admin.overview', WPP_URL . 'scripts/wpp.admin.overview.js', array( 'jquery', 'wpp.localization' ), WPP_Version );
        wp_register_script( 'wpp.admin.widgets', WPP_URL . 'scripts/wpp.admin.widgets.js', array( 'jquery', 'wpp.localization' ), WPP_Version );

        // Register Vendor Libraries.
        wp_register_script( 'wp-property-galleria', WPP_URL . 'third-party/galleria/galleria-1.2.5.js', array( 'jquery', 'wpp.localization' ) );
        wp_register_script( 'wpp-jquery-fancybox', WPP_URL . 'third-party/fancybox/jquery.fancybox-1.3.4.pack.js', array( 'jquery', 'wpp.localization' ), '1.7.3' );
        wp_register_script( 'wpp-jquery-colorpicker', WPP_URL . 'vendor/usabilitydynamics/lib-js-colorpicker/scripts/colorpicker.js', array( 'jquery', 'wpp.localization' ) );
        wp_register_script( 'wpp-jquery-easing', WPP_URL . 'third-party/fancybox/jquery.easing-1.3.pack.js', array( 'jquery', 'wpp.localization' ), '1.7.3' );
        wp_register_script( 'wpp-jquery-ajaxupload', WPP_URL . 'scripts/fileuploader.js', array( 'jquery', 'wpp.localization' ) );
        wp_register_script( 'wpp-jquery-nivo-slider', WPP_URL . 'third-party/jquery.nivo.slider.pack.js', array( 'jquery', 'wpp.localization' ) );
        wp_register_script( 'wpp-jquery-address', WPP_URL . 'scripts/jquery.address-1.5.js', array( 'jquery', 'wpp.localization' ) );
        wp_register_script( 'wpp-jquery-scrollTo', WPP_URL . 'scripts/jquery.scrollTo-min.js', array( 'jquery', 'wpp.localization' ) );
        wp_register_script( 'wpp-jquery-validate', WPP_URL . 'scripts/jquery.validate.js', array( 'jquery', 'wpp.localization' ) );
        wp_register_script( 'wpp-jquery-number-format', WPP_URL . 'scripts/jquery.number.format.js', array( 'jquery', 'wpp.localization' ) );
        wp_register_script( 'wpp-jquery-data-tables', WPP_URL . "vendor/datatables/datatables/media/js/jquery.dataTables.js", array( 'jquery', 'wpp.localization' ) );

        */

        // Legacy Scripts for reference.
        // wp_register_script( 'jquery-cookie', WPP_URL . 'scripts/jquery.smookie.js', array( 'jquery', 'wpp.localization' ), '1.7.3' );
        // wp_register_script( 'wpp-md5', WPP_URL . 'third-party/md5.js', array( 'wpp.localization' ), WPP_Version );
        // wp_register_script( 'google-maps', 'https://maps.google.com/maps/api/js?sensor=true' );
        // wp_register_script( 'wpp-jquery-gmaps', WPP_URL . 'scripts/jquery.ui.map.min.js', array( 'google-maps', 'jquery-ui-core', 'jquery-ui-widget', 'wpp.localization' ) );

        //global $wp_scripts;
        //die( '<pre>' . print_r( $wp_scripts, true ) . '</pre>' );

        $_instance = new Requires( $args );

        // add_action( 'admin_print_scripts', array( &$_instance, '' ));

        return $_instance;

      }

    }

  }

}



