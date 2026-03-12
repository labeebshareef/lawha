<?php
/**
 * About Page Template
 * Template Name: About Page
 * Converted from about.html
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
    <img src="<?php echo esc_url( LAWHA_URI . '/assets/banners/about-banner.jpg' ); ?>" alt="<?php esc_attr_e( 'About LAWHA HIJABS', 'lawha' ); ?>" class="page-hero__bg">
    <div class="page-hero__content">
      <h1 class="page-hero__title"><?php esc_html_e( 'Our Story', 'lawha' ); ?></h1>
      <p class="page-hero__subtitle"><?php esc_html_e( 'The heart and soul behind LAWHA HIJABS', 'lawha' ); ?></p>
    </div>
  </section>

  <!-- BRAND STORY -->
  <section class="section" id="brand-story">
    <div class="container">
      <div class="story-section">
        <div class="story-section__image anim-image-reveal">
          <img src="<?php echo esc_url( LAWHA_URI . '/assets/models/about-1.jpg' ); ?>" alt="<?php esc_attr_e( 'LAWHA HIJABS — Our Beginning', 'lawha' ); ?>" loading="lazy">
        </div>
        <div class="story-section__content anim-fade-up">
          <p class="overline"><?php esc_html_e( 'The Beginning', 'lawha' ); ?></p>
          <h3><?php esc_html_e( 'Born From a Vision', 'lawha' ); ?></h3>
          <div class="divider"></div>
          <p><?php esc_html_e( 'LAWHA HIJABS was born from one simple yet powerful belief: that modest fashion deserves to be celebrated, not compromised. Our founder envisioned a brand that would bridge the gap between luxury fashion and modest dressing.', 'lawha' ); ?></p>
          <p><?php echo wp_kses_post( __( 'The name "LAWHA" (لوحة) means "canvas" — because we believe every woman is a masterpiece, and our hijabs are the frame that completes her art.', 'lawha' ) ); ?></p>
        </div>
      </div>
    </div>
  </section>

  <!-- MISSION -->
  <section class="section section--alt" id="mission">
    <div class="container">
      <div class="story-section story-section--reverse">
        <div class="story-section__image anim-image-reveal">
          <img src="<?php echo esc_url( LAWHA_URI . '/assets/models/about-2.jpg' ); ?>" alt="<?php esc_attr_e( 'LAWHA HIJABS — Our Mission', 'lawha' ); ?>" loading="lazy">
        </div>
        <div class="story-section__content anim-fade-up">
          <p class="overline"><?php esc_html_e( 'Our Mission', 'lawha' ); ?></p>
          <h3><?php esc_html_e( 'Empowering Through Elegance', 'lawha' ); ?></h3>
          <div class="divider"></div>
          <p><?php esc_html_e( 'We are on a mission to redefine modest fashion. We believe that wearing a hijab is not a limitation — it is a statement of strength, identity, and grace.', 'lawha' ); ?></p>
          <p><?php esc_html_e( 'Every piece we create is designed to empower women, to help them feel confident in their skin, and to prove that modesty and luxury are not mutually exclusive.', 'lawha' ); ?></p>
        </div>
      </div>
    </div>
  </section>

  <!-- PHILOSOPHY / VALUES -->
  <section class="section" id="philosophy">
    <div class="container">
      <div class="section-header anim-fade-up">
        <p class="section-header__overline"><?php esc_html_e( 'Our Philosophy', 'lawha' ); ?></p>
        <h2 class="section-header__title"><?php esc_html_e( 'What We Stand For', 'lawha' ); ?></h2>
      </div>
      <div class="values-grid stagger-children">
        <?php
        $values = array(
            array( 'num' => '01', 'title' => 'Quality First', 'desc' => 'We source only the finest fabrics — silk, premium chiffon, jersey, and crêpe — to ensure every hijab feels as luxurious as it looks.' ),
            array( 'num' => '02', 'title' => 'Modest Empowerment', 'desc' => 'We believe modesty is a superpower. Our designs celebrate the choice to dress modestly while looking extraordinary.' ),
            array( 'num' => '03', 'title' => 'Timeless Design', 'desc' => 'Fashion fades, but style is eternal. We create pieces that transcend trends and remain relevant season after season.' ),
        );
        foreach ( $values as $value ) :
            ?>
            <div class="value-item anim-fade-up">
              <p class="value-item__number"><?php echo esc_html( $value['num'] ); ?></p>
              <h4 class="value-item__title"><?php echo esc_html( $value['title'] ); ?></h4>
              <p class="value-item__desc"><?php echo esc_html( $value['desc'] ); ?></p>
            </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- EMPOWERMENT QUOTE -->
  <section class="section section--dark" id="empowerment">
    <div class="container container--narrow text-center">
      <div class="anim-fade-up">
        <p class="overline mb-6" style="color: var(--accent);"><?php esc_html_e( 'Our Promise', 'lawha' ); ?></p>
        <blockquote style="border:none;text-align:center;font-size:var(--text-3xl);color:var(--text-light);">
          <?php echo esc_html( '"A woman who wears her hijab with confidence wears a crown the world cannot take away."' ); ?>
        </blockquote>
        <div class="divider divider--center mt-7 mb-5"></div>
        <p class="text-small" style="color:var(--neutral-500);">— <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
      </div>
    </div>
  </section>

  <!-- CTA BANNER -->
  <section class="cta-banner" id="cta">
    <img src="<?php echo esc_url( LAWHA_URI . '/assets/banners/cta-banner.jpg' ); ?>" alt="<?php esc_attr_e( 'LAWHA HIJABS', 'lawha' ); ?>" class="cta-banner__bg" loading="lazy">
    <div class="cta-banner__content anim-fade-up">
      <h2 class="cta-banner__title"><?php esc_html_e( 'Discover Your Crown', 'lawha' ); ?></h2>
      <p class="cta-banner__desc"><?php esc_html_e( 'Explore our premium collection and find the hijab that speaks to your soul.', 'lawha' ); ?></p>
      <?php if ( class_exists( 'WooCommerce' ) ) : ?>
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="btn btn--accent">
          <?php esc_html_e( 'Explore Collections', 'lawha' ); ?>
          <span class="btn__arrow">→</span>
        </a>
      <?php endif; ?>
    </div>
  </section>

<?php get_footer(); ?>
