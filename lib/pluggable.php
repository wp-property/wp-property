<?php
/**
 * Assorted Pluggable Functions
 *
 * @author potanin@UD
 * @since 2.0.0
 */

/**
 * Implementing this for old versions of PHP
 *
 * @since 1.15.9
 */
if( !function_exists( 'array_fill_keys' ) ) {

  function array_fill_keys( $target, $value = '' ) {

    if( is_array( $target ) ) {

      foreach( $target as $key => $val ) {

        $filledArray[ $val ] = is_array( $value ) ? $value[ $key ] : $value;

      }

    }

    return $filledArray;

  }

}

/**
 * Delete a file or recursively delete a directory
 *
 * @param string  $str Path to file or directory
 * @param boolean $flag If false, doesn't remove root directory
 *
 * @version 0.1
 * @since 1.32.2
 * @author peshkov@UD
 */
if( !function_exists( 'wpp_recursive_unlink' ) ) {
  function wpp_recursive_unlink( $str, $flag = false ) {
    if( is_file( $str ) ) {
      return @unlink( $str );
    } elseif( is_dir( $str ) ) {
      $scan = glob( rtrim( $str, '/' ) . '/*' );
      foreach( $scan as $index => $path ) {
        wpp_recursive_unlink( $path, true );
      }
      if( $flag ) {
        return @rmdir( $str );
      } else {
        return true;
      }
    }
  }
}

/**
 * Add 'property' to the list of RSSable post_types.
 *
 * @todo Why is this a stand-alone function?
 *
 * @param string $request
 *
 * @return string
 * @author korotkov@ud
 * @since 1.36.2
 */
if( !function_exists( 'property_feed' ) ) {
  function property_feed( $qv ) {

    if( isset( $qv[ 'feed' ] ) && !isset( $qv[ 'post_type' ] ) ) {
      $qv[ 'post_type' ] = get_post_types( $args = array(
        'public'   => true,
        '_builtin' => false
      ) );
      array_push( $qv[ 'post_type' ], 'post' );
    }

    return $qv;

  }
}
