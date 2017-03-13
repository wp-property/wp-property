<?php
// Not like register_uninstall_hook(), you do NOT have to use a static function.

if( function_exists( 'wpp_fs' ) ) {
  wpp_fs()->add_action('after_uninstall', 'wpp_fs_uninstall_cleanup');
}
