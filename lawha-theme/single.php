<?php
/**
 * Single Post Template
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
    <?php if ( has_post_thumbnail() ) : ?>
      <?php the_post_thumbnail( 'full', array( 'class' => 'page-hero__bg', 'alt' => get_the_title() ) ); ?>
    <?php endif; ?>
    <div class="page-hero__content">
      <p class="page-hero__subtitle"><?php echo esc_html( get_the_date() ); ?></p>
      <h1 class="page-hero__title"><?php the_title(); ?></h1>
    </div>
  </section>

  <section class="section">
    <div class="container container--narrow">
      <?php
      while ( have_posts() ) :
          the_post();
          ?>
          <article id="post-<?php the_ID(); ?>" <?php post_class( 'anim-fade-up' ); ?>>
            <div class="single-content">
              <?php the_content(); ?>
            </div>

            <?php if ( get_the_tags() ) : ?>
              <div class="single-tags" style="margin-top: var(--space-7);">
                <p class="overline" style="margin-bottom: var(--space-3);"><?php esc_html_e( 'Tags', 'lawha' ); ?></p>
                <?php the_tags( '', ', ', '' ); ?>
              </div>
            <?php endif; ?>
          </article>

          <!-- Post Navigation -->
          <div class="flex-between" style="margin-top: var(--space-8); padding-top: var(--space-6); border-top: 1px solid var(--border-color);">
            <div>
              <?php
              $prev_post = get_previous_post();
              if ( $prev_post ) :
                  ?>
                  <p class="overline" style="margin-bottom: var(--space-2);"><?php esc_html_e( 'Previous', 'lawha' ); ?></p>
                  <a href="<?php echo esc_url( get_permalink( $prev_post ) ); ?>" class="btn btn--outline" style="padding: 8px 16px;">
                    ← <?php echo esc_html( get_the_title( $prev_post ) ); ?>
                  </a>
              <?php endif; ?>
            </div>
            <div style="text-align: right;">
              <?php
              $next_post = get_next_post();
              if ( $next_post ) :
                  ?>
                  <p class="overline" style="margin-bottom: var(--space-2);"><?php esc_html_e( 'Next', 'lawha' ); ?></p>
                  <a href="<?php echo esc_url( get_permalink( $next_post ) ); ?>" class="btn btn--outline" style="padding: 8px 16px;">
                    <?php echo esc_html( get_the_title( $next_post ) ); ?> →
                  </a>
              <?php endif; ?>
            </div>
          </div>
      <?php endwhile; ?>
    </div>
  </section>

<?php get_footer(); ?>
