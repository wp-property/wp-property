<?php
/**
 * Settings page main template
 */
?>
<div class="wrap">
  <h2><?php echo $this->get( 'configuration.secondary_menu.page_title', 'schema' ); ?></h2>
  <?php if( isset( $_REQUEST[ 'message' ] ) ) : ?>
    <?php if( $_REQUEST[ 'message' ] == 'updated' ) : ?>
      <div class="updated fade"><?php _e( 'Settings updated' ); ?></div>
    <?php endif; ?>
  <?php endif; ?>
  <div class="settings-content" style="display:none;">
    <form id="uis_form" action="" method="post" >
      <?php do_action( 'ud:ui:settings:view:main:top' ); ?>
      <?php wp_nonce_field( 'ui_settings' ); ?>
      <?php if( $this->get( 'menu', 'schema', false ) ) : ?>
        <div class="tabs-wrap">
          <div id="tabs">
            <ul class="tabs">
              <?php foreach( $this->get( 'menu', 'schema' ) as $menu ) : ?>
                <li><a href="#tab-<?php echo $menu[ 'id' ]; ?>"><?php echo $menu[ 'name' ]; ?></a></li>
              <?php endforeach; ?>
              <?php do_action( 'ud:ui:settings:view:tab_link' ); ?>
            </ul>
            <?php foreach( $this->get( 'menu', 'schema' ) as $menu ) : ?>
              <div id="tab-<?php echo $menu[ 'id' ]; ?>" >
                <?php $this->get_template_part( 'tab', array( 'menu' => $menu ) ); ?>
              </div>
            <?php endforeach; ?>
            <?php do_action( 'ud:ui:settings:view:tab_container' ); ?>
          </div>
        </div>
      <?php else : ?>
        <div class="no-tabs">
          <?php $this->get_template_part( 'tab', array( 'menu' => false ) ); ?>
        </div>
      <?php endif; ?>
      <?php do_action( 'ud:ui:settings:view:main:bottom' ); ?>
      <div class="actions">
        <?php submit_button( __( 'Save' ), 'button' ); ?>
        <?php do_action( 'ud:ui:settings:view:main:actions' ); ?>
      </div>
    </form>
  </div>
</div>