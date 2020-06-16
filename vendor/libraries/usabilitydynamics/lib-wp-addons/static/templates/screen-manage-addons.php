<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div id="col-container" class="<?php //about-wrap ?>">
  <?php
  echo '<div class="licenses-notice">' . wpautop( sprintf( __( 'See below for a list of %s Add-ons installed on %s. You can view %s on how this works. %s', $this->domain ), $this->name, get_bloginfo( 'name' ), sprintf('<a target="_blank" href="%s">documentation</a>', $this->addons_homepage), '&nbsp;&nbsp;<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '" class="button">' . __( 'Check for Updates', $this->domain ) . '</a>' ) ) . '</div>' . "\n";
  ?>
    <div>
        <form id="activate-addons" method="post" action="" class="validate">
            <input type="hidden" name="action" value="activate-addons" />
            <input type="hidden" name="page" value="<?php echo esc_attr( $this->page_slug ); ?>" />
          <?php
          //echo "<pre>"; print_r( $this ); echo "</pre>"; die();
          $this->list_table = new UsabilityDynamics\WPA\Addons_Table( array(
            'name' => $this->name,
            'domain' => $this->domain,
            'page' => $this->menu_slug,
          ) );
          $addons = $this->get_detected_addons();
          if ( !empty($addons)) {
            foreach( $addons as $k=>$addon ) {
              $this->list_table->data[$k] = $addon;
            }
          }
          $this->list_table->prepare_items();
          $this->list_table->display();
          submit_button( __( 'Save', $this->domain ), 'button-primary' );
          ?>
        </form>
    </div><!--/.col-wrap-->
</div><!--/#col-container-->