<?php
/**
 * Generic Page Template
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

  <!-- PAGE HERO -->
  <section class="page-hero">
    <div class="page-hero__content">
      <h1 class="page-hero__title"><?php the_title(); ?></h1>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <?php
      while ( have_posts() ) :
          the_post();
          ?>
          <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="anim-fade-up">
              <?php the_content(); ?>
            </div>
          </article>
      <?php endwhile; ?>
    </div>
  </section>

<?php get_footer(); ?>
