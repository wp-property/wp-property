<?php
/**
 * Custom Posts Overview User Interface
 * Renders template for Overview page, adds meta boxes compatibility
 *
 * @author peshkov@UD
 */
namespace UsabilityDynamics\UI {

  if( !class_exists( 'UsabilityDynamics\UI\Page' ) ) {

    /**
     * Class Settings
     *
     */
    class Page {

      /**
       * @var
       */
      var $screen_id;
      var $parent_slug;
      var $page_title;
      var $menu_title;
      var $capability;
      var $menu_slug;

      /**
       *
       */
      public function __construct( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = false ) {

        $this->parent_slug = $parent_slug;
        $this->page_title = $page_title;
        $this->menu_title = $menu_title;
        $this->capability = $capability;
        $this->menu_slug = $menu_slug;

        if( !$function ) {
          $function = array( $this, 'load' );
        }
        $this->screen_id = add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );

        add_action( 'load-' . $this->screen_id, array( $this, 'preload' ) );
        add_action( 'admin_footer-'.$this->screen_id, array( $this, 'print_script_in_footer' ) );
      }

      /**
       * Init Meta Boxes functionality on passed screen ( page )
       *
       */
      public function preload() {
        $screen = get_current_screen();

        /* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
        do_action('add_meta_boxes_'. $screen->id, null );
        do_action('add_meta_boxes', $screen->id, null );

        /* Enqueue WordPress' script for handling the meta boxes */
        wp_enqueue_script('postbox');

        /* Add screen option: user can choose between 1 or 2 columns (default 2) */
        add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
      }

      /**
       * We're loading our default template which handles meta boxes environment
       */
      public function load() {
        ?>
        <div class="wrap">
          <h2><?php echo apply_filters( 'ud:ui:page:title', $this->page_title ); ?></h2>
          <form name="custom-page-form" method="post">
            <?php
            /* Used to save closed meta boxes and their order */
            wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
            wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
            ?>
            <div id="poststuff">
              <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
                <div id="postbox-container-1" class="postbox-container">
                  <?php do_meta_boxes('','side',null); ?>
                </div>
                <div id="postbox-container-2" class="postbox-container">
                  <?php do_meta_boxes('','normal',null); ?>
                  <?php do_meta_boxes('','advanced',null); ?>
                </div>
              </div> <!-- #post-body -->
            </div> <!-- #poststuff -->
          </form>
        </div><!-- .wrap -->
        <?php
      }

      /**
       * Prints script in footer. This 'initialises' the meta boxes
       */
      public function print_script_in_footer() {
        ?><script>jQuery(document).ready(function(){ postboxes.add_postbox_toggles(pagenow); });</script><?php
      }
    
    }

  }

}