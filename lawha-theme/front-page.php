<?php
/**
 * Front Page Template
 * Converted from index.html — Homepage with hero, brand intro, featured products, showcase, etc.
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>


  <!-- =========================================
       SECTION 1: HERO
       ========================================= -->
  <section class="hero" id="hero">
    <img
      src="<?php echo esc_url( LAWHA_URI . '/assets/banners/hero-banner.jpg' ); ?>"
      alt="<?php esc_attr_e( 'LAWHA HIJABS — Premium Modest Fashion', 'lawha' ); ?>"
      class="hero__bg"
    >
    <div class="hero__overlay"></div>
    <div class="hero__content">
      <p class="hero__overline"><?php esc_html_e( 'Premium Modest Fashion', 'lawha' ); ?></p>
      <h1 class="hero__title"><?php esc_html_e( 'Wear Your Crown', 'lawha' ); ?></h1>
      <p class="hero__tagline"><?php esc_html_e( 'Elegance woven into every thread', 'lawha' ); ?></p>
      <div class="hero__cta">
        <?php if ( class_exists( 'WooCommerce' ) ) : ?>
          <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="btn btn--secondary">
            <?php esc_html_e( 'Discover Collection', 'lawha' ); ?>
            <span class="btn__arrow">→</span>
          </a>
        <?php else : ?>
          <a href="#featured-collection" class="btn btn--secondary">
            <?php esc_html_e( 'Discover Collection', 'lawha' ); ?>
            <span class="btn__arrow">→</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </section>


  <!-- =========================================
       SECTION 2: BRAND INTRODUCTION
       ========================================= -->
  <section class="section" id="brand-intro">
    <div class="container">
      <div class="brand-intro">
        <div class="brand-intro__image anim-image-reveal">
          <img
            src="<?php echo esc_url( LAWHA_URI . '/assets/models/model-1.jpg' ); ?>"
            alt="<?php esc_attr_e( 'LAWHA HIJABS — Modest Fashion Model', 'lawha' ); ?>"
            loading="lazy"
          >
        </div>
        <div class="brand-intro__content anim-fade-up">
          <p class="overline"><?php esc_html_e( 'Our Story', 'lawha' ); ?></p>
          <h2><?php echo wp_kses_post( __( 'Where Modesty<br>Meets Luxury', 'lawha' ) ); ?></h2>
          <div class="divider"></div>
          <p><?php esc_html_e( 'LAWHA HIJABS was born from a desire to elevate modest fashion. We believe that every woman deserves to feel confident, elegant, and empowered — without compromise.', 'lawha' ); ?></p>
          <p><?php esc_html_e( 'Each hijab in our collection is carefully curated using premium fabrics and timeless designs that celebrate the beauty of modesty.', 'lawha' ); ?></p>
          <?php
          $about_page = get_page_by_path( 'about' );
          if ( $about_page ) :
          ?>
            <a href="<?php echo esc_url( get_permalink( $about_page ) ); ?>" class="btn btn--outline" style="margin-top: var(--space-5);">
              <?php esc_html_e( 'Our Journey', 'lawha' ); ?>
              <span class="btn__arrow">→</span>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>


  <!-- =========================================
       SECTION 3: FEATURED COLLECTION
       ========================================= -->
  <section class="section section--alt" id="featured-collection">
    <div class="container">
      <div class="section-header anim-fade-up">
        <p class="section-header__overline"><?php esc_html_e( 'New Arrivals', 'lawha' ); ?></p>
        <h2 class="section-header__title"><?php esc_html_e( 'Featured Collection', 'lawha' ); ?></h2>
        <p class="section-header__desc"><?php esc_html_e( 'Discover our latest curated selection of premium hijabs, crafted for the modern woman.', 'lawha' ); ?></p>
      </div>

      <div class="grid grid--4 stagger-children">
        <?php
        if ( class_exists( 'WooCommerce' ) ) :
            $featured_products = lawha_get_featured_products( 4 );
            if ( ! empty( $featured_products ) ) :
                foreach ( $featured_products as $product ) :
                    $product_id   = $product->get_id();
                    $image_id     = $product->get_image_id();
                    $image_url    = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : wc_placeholder_img_src( 'large' );
                    $image_alt    = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : $product->get_name();
                    $is_new       = $product->is_featured();
                    $on_sale      = $product->is_on_sale();
                    ?>
                    <div class="product-card anim-fade-up">
                      <div class="product-card__image-wrapper">
                        <?php if ( $on_sale ) : ?>
                          <span class="product-card__badge"><?php esc_html_e( 'Sale', 'lawha' ); ?></span>
                        <?php elseif ( $is_new ) : ?>
                          <span class="product-card__badge"><?php esc_html_e( 'New', 'lawha' ); ?></span>
                        <?php endif; ?>
                        <img
                          src="<?php echo esc_url( $image_url ); ?>"
                          alt="<?php echo esc_attr( $image_alt ); ?>"
                          class="product-card__image"
                          loading="lazy"
                        >
                        <div class="product-card__overlay">
                          <a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="btn btn--secondary" style="font-size: 0.65rem; padding: 10px 20px;"><?php esc_html_e( 'Quick View', 'lawha' ); ?></a>
                        </div>
                      </div>
                      <div class="product-card__info">
                        <h4 class="product-card__name"><?php echo esc_html( $product->get_name() ); ?></h4>
                        <p class="product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
                      </div>
                    </div>
                    <?php
                endforeach;
            endif;
        else :
            // Static fallback when WooCommerce is not active
            $static_products = array(
                array( 'img' => 'product-1.jpg', 'name' => 'Silk Satin — Dusty Rose', 'price' => 'AED 189', 'badge' => 'New' ),
                array( 'img' => 'product-2.jpg', 'name' => 'Jersey Cotton — Midnight', 'price' => 'AED 149', 'badge' => '' ),
                array( 'img' => 'product-3.jpg', 'name' => 'Chiffon — Ivory', 'price' => 'AED 129', 'badge' => '' ),
                array( 'img' => 'product-4.jpg', 'name' => 'Crêpe — Mocha', 'price' => 'AED 159', 'badge' => 'Best Seller' ),
            );
            foreach ( $static_products as $sp ) :
                ?>
                <div class="product-card anim-fade-up">
                  <div class="product-card__image-wrapper">
                    <?php if ( ! empty( $sp['badge'] ) ) : ?>
                      <span class="product-card__badge"><?php echo esc_html( $sp['badge'] ); ?></span>
                    <?php endif; ?>
                    <img
                      src="<?php echo esc_url( LAWHA_URI . '/assets/products/' . $sp['img'] ); ?>"
                      alt="<?php echo esc_attr( $sp['name'] ); ?>"
                      class="product-card__image"
                      loading="lazy"
                    >
                    <div class="product-card__overlay">
                      <a href="#" class="btn btn--secondary" style="font-size: 0.65rem; padding: 10px 20px;"><?php esc_html_e( 'Quick View', 'lawha' ); ?></a>
                    </div>
                  </div>
                  <div class="product-card__info">
                    <h4 class="product-card__name"><?php echo esc_html( $sp['name'] ); ?></h4>
                    <p class="product-card__price"><?php echo esc_html( $sp['price'] ); ?></p>
                  </div>
                </div>
                <?php
            endforeach;
        endif;
        ?>
      </div>

      <div class="text-center" style="margin-top: var(--space-7);">
        <?php if ( class_exists( 'WooCommerce' ) ) : ?>
          <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="btn btn--primary">
            <?php esc_html_e( 'View All Collections', 'lawha' ); ?>
            <span class="btn__arrow">→</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </section>


  <!-- =========================================
       SECTION 4: MODEL SHOWCASE
       ========================================= -->
  <section class="section" id="model-showcase">
    <div class="container">
      <div class="section-header anim-fade-up">
        <p class="section-header__overline"><?php esc_html_e( 'Editorial', 'lawha' ); ?></p>
        <h2 class="section-header__title"><?php esc_html_e( 'The LAWHA Woman', 'lawha' ); ?></h2>
        <p class="section-header__desc"><?php esc_html_e( 'Confidence. Grace. Strength. Our hijabs are designed for the woman who wears her identity with pride.', 'lawha' ); ?></p>
      </div>

      <div class="grid grid--3 stagger-children" style="gap: var(--space-4);">
        <?php
        $showcase_items = array(
            array( 'img' => 'model-2.jpg', 'title' => 'Confidence', 'desc' => 'Silk Satin Collection' ),
            array( 'img' => 'model-3.jpg', 'title' => 'Grace', 'desc' => 'Chiffon Collection' ),
            array( 'img' => 'model-4.jpg', 'title' => 'Strength', 'desc' => 'Premium Crêpe Collection' ),
        );
        foreach ( $showcase_items as $item ) :
            ?>
            <div class="image-card aspect-portrait anim-fade-up">
              <img
                src="<?php echo esc_url( LAWHA_URI . '/assets/models/' . $item['img'] ); ?>"
                alt="<?php echo esc_attr( 'LAWHA HIJABS Model — ' . $item['title'] ); ?>"
                class="image-card__img"
                loading="lazy"
              >
              <div class="image-card__caption">
                <h4><?php echo esc_html( $item['title'] ); ?></h4>
                <p><?php echo esc_html( $item['desc'] ); ?></p>
              </div>
            </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- =========================================
       SECTION 5: WHY CHOOSE LAWHA
       ========================================= -->
  <section class="section section--alt" id="why-lawha">
    <div class="container">
      <div class="section-header anim-fade-up">
        <p class="section-header__overline"><?php esc_html_e( 'Why LAWHA', 'lawha' ); ?></p>
        <h2 class="section-header__title"><?php esc_html_e( 'Crafted With Purpose', 'lawha' ); ?></h2>
      </div>

      <div class="grid grid--4 stagger-children">
        <!-- Feature 1 -->
        <div class="feature-card anim-fade-up">
          <div class="feature-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
          </div>
          <h4 class="feature-card__title"><?php esc_html_e( 'Premium Fabrics', 'lawha' ); ?></h4>
          <p class="feature-card__desc"><?php esc_html_e( 'Only the finest materials — silk, chiffon, jersey, and crêpe — selected for unmatched comfort and luxury.', 'lawha' ); ?></p>
        </div>

        <!-- Feature 2 -->
        <div class="feature-card anim-fade-up">
          <div class="feature-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
              <line x1="2" y1="12" x2="22" y2="12"></line>
            </svg>
          </div>
          <h4 class="feature-card__title"><?php esc_html_e( 'Modest & Modern', 'lawha' ); ?></h4>
          <p class="feature-card__desc"><?php esc_html_e( 'Designs that honor modesty while embracing contemporary fashion trends and editorial aesthetics.', 'lawha' ); ?></p>
        </div>

        <!-- Feature 3 -->
        <div class="feature-card anim-fade-up">
          <div class="feature-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
          </div>
          <h4 class="feature-card__title"><?php esc_html_e( 'Ethically Made', 'lawha' ); ?></h4>
          <p class="feature-card__desc"><?php esc_html_e( 'Committed to sustainable and ethical production practices. Each piece is crafted with care and responsibility.', 'lawha' ); ?></p>
        </div>

        <!-- Feature 4 -->
        <div class="feature-card anim-fade-up">
          <div class="feature-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
          </div>
          <h4 class="feature-card__title"><?php esc_html_e( 'Effortless Style', 'lawha' ); ?></h4>
          <p class="feature-card__desc"><?php esc_html_e( 'Every hijab is designed to drape beautifully, staying in place all day while looking absolutely stunning.', 'lawha' ); ?></p>
        </div>
      </div>
    </div>
  </section>


  <!-- =========================================
       SECTION 6: INSTAGRAM GALLERY
       ========================================= -->
  <section class="section" id="instagram" style="padding-bottom: 0;">
    <div class="container">
      <div class="section-header anim-fade-up">
        <p class="section-header__overline">@lawhahijabs</p>
        <h2 class="section-header__title"><?php esc_html_e( 'Follow Our Journey', 'lawha' ); ?></h2>
      </div>
    </div>
    <div class="insta-grid anim-fade-in">
      <div class="insta-grid__item">
        <img src="<?php echo esc_url( LAWHA_URI . '/assets/models/insta-1.jpg' ); ?>" alt="<?php esc_attr_e( 'LAWHA HIJABS Instagram', 'lawha' ); ?>" loading="lazy">
      </div>
      <div class="insta-grid__item">
        <img src="<?php echo esc_url( LAWHA_URI . '/assets/products/insta-2.jpg' ); ?>" alt="<?php esc_attr_e( 'LAWHA HIJABS Instagram', 'lawha' ); ?>" loading="lazy">
      </div>
      <div class="insta-grid__item">
        <img src="<?php echo esc_url( LAWHA_URI . '/assets/models/insta-3.jpg' ); ?>" alt="<?php esc_attr_e( 'LAWHA HIJABS Instagram', 'lawha' ); ?>" loading="lazy">
      </div>
      <div class="insta-grid__item">
        <img src="<?php echo esc_url( LAWHA_URI . '/assets/products/insta-4.jpg' ); ?>" alt="<?php esc_attr_e( 'LAWHA HIJABS Instagram', 'lawha' ); ?>" loading="lazy">
      </div>
    </div>
  </section>


  <!-- =========================================
       SECTION 7: TESTIMONIALS
       ========================================= -->
  <section class="section" id="testimonials">
    <div class="container">
      <div class="section-header anim-fade-up">
        <p class="section-header__overline"><?php esc_html_e( 'Testimonials', 'lawha' ); ?></p>
        <h2 class="section-header__title"><?php esc_html_e( 'What Our Queens Say', 'lawha' ); ?></h2>
      </div>

      <div class="grid grid--3 stagger-children">
        <?php
        $testimonials = array(
            array(
                'quote'  => 'The quality is unmatched. I\'ve never worn a hijab that feels this luxurious and drapes so perfectly.',
                'author' => 'Fatima A.',
            ),
            array(
                'quote'  => 'LAWHA truly understands modest fashion. Elegant, modern, and absolutely beautiful pieces.',
                'author' => 'Noor M.',
            ),
            array(
                'quote'  => 'Every piece feels like a crown. The attention to detail and fabric quality is incredible.',
                'author' => 'Amira K.',
            ),
        );
        foreach ( $testimonials as $testimonial ) :
            ?>
            <div class="testimonial-card anim-fade-up">
              <p class="testimonial-card__quote"><?php echo esc_html( $testimonial['quote'] ); ?></p>
              <p class="testimonial-card__author"><?php echo esc_html( $testimonial['author'] ); ?></p>
            </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- =========================================
       SECTION 8: CALL TO ACTION
       ========================================= -->
  <section class="cta-banner" id="cta">
    <img
      src="<?php echo esc_url( LAWHA_URI . '/assets/banners/cta-banner.jpg' ); ?>"
      alt="<?php esc_attr_e( 'LAWHA HIJABS — Shop Now', 'lawha' ); ?>"
      class="cta-banner__bg"
      loading="lazy"
    >
    <div class="cta-banner__content anim-fade-up">
      <p class="overline" style="color: var(--accent-light); margin-bottom: var(--space-4);"><?php esc_html_e( 'Ready to Elevate Your Style?', 'lawha' ); ?></p>
      <h2 class="cta-banner__title"><?php esc_html_e( 'Wear Your Crown', 'lawha' ); ?></h2>
      <p class="cta-banner__desc"><?php esc_html_e( 'Explore our latest collection and discover the hijab that speaks to you.', 'lawha' ); ?></p>
      <?php if ( class_exists( 'WooCommerce' ) ) : ?>
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="btn btn--accent">
          <?php esc_html_e( 'Shop Now', 'lawha' ); ?>
          <span class="btn__arrow">→</span>
        </a>
      <?php endif; ?>
    </div>
  </section>


<?php get_footer(); ?>
