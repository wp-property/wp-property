<?php
/**
 * Template Name: Default Page
 * Type: page
 */
?><!DOCTYPE html>
<html>
  <head>
<?php wp_head(); ?>
  </head>
  <body class="facebook">
    <div id="container">
      <div id="content" role="main">

      <?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

        <div <?php post_class(); ?>>
          <h2 class="entry-title"><?php the_title(); ?></h2>
          <div class="entry-content">
            <?php the_content(); ?>
          </div>
        </div>

        <?php endwhile; ?>

      </div>
    </div>
<?php wp_footer(); ?>
  </body>
</html>
