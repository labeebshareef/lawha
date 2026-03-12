<?php
/**
 * Archive Template
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
      <h1 class="page-hero__title"><?php the_archive_title(); ?></h1>
      <?php if ( get_the_archive_description() ) : ?>
        <p class="page-hero__subtitle"><?php echo wp_kses_post( get_the_archive_description() ); ?></p>
      <?php endif; ?>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <?php if ( have_posts() ) : ?>
        <div class="grid grid--3 stagger-children">
          <?php while ( have_posts() ) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'product-card anim-fade-up' ); ?>>
              <?php if ( has_post_thumbnail() ) : ?>
                <div class="product-card__image-wrapper">
                  <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail( 'large', array( 'class' => 'product-card__image', 'loading' => 'lazy' ) ); ?>
                  </a>
                </div>
              <?php endif; ?>
              <div class="product-card__info">
                <h4 class="product-card__name">
                  <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h4>
                <p class="archive-card__date"><?php echo esc_html( get_the_date() ); ?></p>
              </div>
            </article>
          <?php endwhile; ?>
        </div>

        <div class="text-center mt-7">
          <?php
          the_posts_pagination( array(
              'prev_text' => '← ' . esc_html__( 'Previous', 'lawha' ),
              'next_text' => esc_html__( 'Next', 'lawha' ) . ' →',
          ) );
          ?>
        </div>
      <?php else : ?>
        <div class="text-center">
          <p><?php esc_html_e( 'No posts found.', 'lawha' ); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </section>

<?php get_footer(); ?>
