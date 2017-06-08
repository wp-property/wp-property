<?php
/**
 * The default page for property overview page.
 *
 * Used when no WordPress page is setup to display overview via shortcode.
 * Will be rendered as a 404 not-found, but still can display properties.
 *
 * @package WP-Property
 */
 global $post, $wp_properties;
get_header(); ?>

<div id="container" class="<?php wpp_css('property_overview_page::container'); ?>">
  <div id="content" class="<?php wpp_css('property_overview_page::content'); ?>" role="main">
    <div id="wpp_default_overview_page" >
      <h1 class="entry-title"><?php if($post && $post->post_title) echo $post->post_title; ?></h1>
      <div class="<?php wpp_css('property_overview_page::entry_content', "entry-content"); ?>">
        <?php if(is_404()): ?>
        <p><?php _e('Sorry, we could not find what you were looking for.  Since you are here, take a look at some of our properties.',ud_get_wp_property()->domain) ?></p>
        <?php endif; ?>
        <?php
        if(isset( $wp_properties['configuration']['do_not_override_search_result_page'] ) && $wp_properties['configuration']['do_not_override_search_result_page'] == 'true')
          echo $content = apply_filters('the_content', $post->post_content);
        ?>
        <div class="<?php wpp_css('property_overview_page::all_properties', "all-properties"); ?>">
          <?php echo do_shortcode( '[property_overview]' ); ?>
        </div>
      </div><!-- .entry-content -->
    </div><!-- #post-## -->
  </div><!-- #content -->
</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
