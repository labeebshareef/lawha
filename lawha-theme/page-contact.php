<?php
/**
 * Contact Page Template
 * Template Name: Contact Page
 * Converted from contact.html
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
    <img src="<?php echo esc_url( LAWHA_URI . '/assets/banners/contact-banner.jpg' ); ?>" alt="<?php esc_attr_e( 'Contact LAWHA HIJABS', 'lawha' ); ?>" class="page-hero__bg">
    <div class="page-hero__content">
      <h1 class="page-hero__title"><?php esc_html_e( 'Get in Touch', 'lawha' ); ?></h1>
      <p class="page-hero__subtitle"><?php esc_html_e( "We'd love to hear from you", 'lawha' ); ?></p>
    </div>
  </section>

  <!-- CONTACT INFO CARDS -->
  <section class="section" id="contact-info">
    <div class="container">
      <div class="section-header anim-fade-up">
        <p class="section-header__overline"><?php esc_html_e( 'Reach Out', 'lawha' ); ?></p>
        <h2 class="section-header__title"><?php esc_html_e( 'How to Order', 'lawha' ); ?></h2>
        <p class="section-header__desc"><?php esc_html_e( 'We make ordering easy. Reach out to us through any of the channels below.', 'lawha' ); ?></p>
      </div>

      <div class="grid grid--3 stagger-children" style="margin-bottom: var(--space-8);">
        <!-- WhatsApp -->
        <a href="<?php echo esc_url( 'https://wa.me/971501234567' ); ?>" class="contact-info-card anim-fade-up hover-lift" style="text-decoration:none;">
          <div class="contact-info-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"></path></svg>
          </div>
          <div>
            <h4 class="contact-info-card__title"><?php esc_html_e( 'WhatsApp', 'lawha' ); ?></h4>
            <p class="contact-info-card__text">+971 50 123 4567<br><?php esc_html_e( 'Fastest way to order', 'lawha' ); ?></p>
          </div>
        </a>

        <!-- Phone -->
        <a href="tel:+971501234567" class="contact-info-card anim-fade-up hover-lift" style="text-decoration:none;">
          <div class="contact-info-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"></path></svg>
          </div>
          <div>
            <h4 class="contact-info-card__title"><?php esc_html_e( 'Phone', 'lawha' ); ?></h4>
            <p class="contact-info-card__text">+971 50 123 4567<br><?php esc_html_e( 'Sun — Thu, 9am — 6pm', 'lawha' ); ?></p>
          </div>
        </a>

        <!-- Email -->
        <a href="mailto:hello@lawhahijabs.com" class="contact-info-card anim-fade-up hover-lift" style="text-decoration:none;">
          <div class="contact-info-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
          </div>
          <div>
            <h4 class="contact-info-card__title"><?php esc_html_e( 'Email', 'lawha' ); ?></h4>
            <p class="contact-info-card__text">hello@lawhahijabs.com<br><?php esc_html_e( 'We reply within 24 hours', 'lawha' ); ?></p>
          </div>
        </a>
      </div>

      <!-- WhatsApp CTA Button -->
      <div class="text-center anim-fade-up">
        <a href="<?php echo esc_url( 'https://wa.me/971501234567?text=Hi%20LAWHA!%20I%27d%20like%20to%20place%20an%20order.' ); ?>" class="btn btn--whatsapp" style="font-size: var(--text-sm); padding: var(--space-4) var(--space-7);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          <?php esc_html_e( 'Order via WhatsApp', 'lawha' ); ?>
        </a>
      </div>
    </div>
  </section>

  <!-- CONTACT FORM -->
  <section class="section section--alt" id="contact-form">
    <div class="container">
      <div class="contact-grid">
        <div class="anim-fade-up">
          <p class="overline" style="margin-bottom: var(--space-4);"><?php esc_html_e( 'Send a Message', 'lawha' ); ?></p>
          <h3 style="margin-bottom: var(--space-5);"><?php esc_html_e( "We'd Love to Hear From You", 'lawha' ); ?></h3>
          <p style="margin-bottom: var(--space-6);"><?php esc_html_e( 'Have a question, want to collaborate, or simply want to say hello? Fill out the form and our team will get back to you shortly.', 'lawha' ); ?></p>
          <form id="contactForm" method="post" action="">
            <?php wp_nonce_field( 'lawha_contact_form', 'lawha_nonce' ); ?>
            <input type="hidden" name="lawha_contact_submit" value="1">

            <?php
            // Display success message
            $sid = lawha_visitor_key();
            $success = get_transient( 'lawha_contact_success_' . $sid );
            $errors  = get_transient( 'lawha_contact_errors_' . $sid );
            $data    = get_transient( 'lawha_contact_data_' . $sid );

            if ( $success ) :
                delete_transient( 'lawha_contact_success_' . $sid );
                ?>
                <div class="lawha-form-success">
                  <?php esc_html_e( 'Thank you! Your message has been sent successfully. We\'ll get back to you shortly.', 'lawha' ); ?>
                </div>
            <?php
            endif;

            if ( $errors ) :
                delete_transient( 'lawha_contact_errors_' . $sid );
                delete_transient( 'lawha_contact_data_' . $sid );
                ?>
                <div class="lawha-form-errors">
                  <ul>
                    <?php foreach ( $errors as $error ) : ?>
                      <li><?php echo esc_html( $error ); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
            <?php endif; ?>

            <div class="form-group">
              <label for="name" class="form-label"><?php esc_html_e( 'Name', 'lawha' ); ?></label>
              <input type="text" id="name" name="name" class="form-input" placeholder="<?php esc_attr_e( 'Your full name', 'lawha' ); ?>" value="<?php echo isset( $data['name'] ) ? esc_attr( $data['name'] ) : ''; ?>" required>
            </div>
            <div class="form-group">
              <label for="email" class="form-label"><?php esc_html_e( 'Email', 'lawha' ); ?></label>
              <input type="email" id="email" name="email" class="form-input" placeholder="<?php esc_attr_e( 'your@email.com', 'lawha' ); ?>" required>
            </div>
            <div class="form-group">
              <label for="subject" class="form-label"><?php esc_html_e( 'Subject', 'lawha' ); ?></label>
              <input type="text" id="subject" name="subject" class="form-input" placeholder="<?php esc_attr_e( 'How can we help?', 'lawha' ); ?>">
            </div>
            <div class="form-group">
              <label for="message" class="form-label"><?php esc_html_e( 'Message', 'lawha' ); ?></label>
              <textarea id="message" name="message" class="form-textarea" placeholder="<?php esc_attr_e( 'Tell us more...', 'lawha' ); ?>" required></textarea>
            </div>
            <button type="submit" class="btn btn--primary"><?php esc_html_e( 'Send Message', 'lawha' ); ?> <span class="btn__arrow">→</span></button>
          </form>
        </div>

        <div class="anim-fade-up" style="padding-left: var(--space-7);">
          <p class="overline" style="margin-bottom: var(--space-4);"><?php esc_html_e( 'Follow Us', 'lawha' ); ?></p>
          <h3 style="margin-bottom: var(--space-5);"><?php esc_html_e( 'Stay Connected', 'lawha' ); ?></h3>
          <p style="margin-bottom: var(--space-6);"><?php esc_html_e( 'Follow us on social media for the latest collections, styling tips, and behind-the-scenes content.', 'lawha' ); ?></p>
          <div class="flex flex-col gap-4">
            <a href="#" class="contact-info-card hover-lift" style="text-decoration:none;">
              <div class="contact-info-card__icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
              </div>
              <div>
                <h4 class="contact-info-card__title"><?php esc_html_e( 'Instagram', 'lawha' ); ?></h4>
                <p class="contact-info-card__text">@lawhahijabs</p>
              </div>
            </a>
            <a href="#" class="contact-info-card hover-lift" style="text-decoration:none;">
              <div class="contact-info-card__icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path></svg>
              </div>
              <div>
                <h4 class="contact-info-card__title"><?php esc_html_e( 'TikTok', 'lawha' ); ?></h4>
                <p class="contact-info-card__text">@lawhahijabs</p>
              </div>
            </a>
            <a href="#" class="contact-info-card hover-lift" style="text-decoration:none;">
              <div class="contact-info-card__icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path></svg>
              </div>
              <div>
                <h4 class="contact-info-card__title"><?php esc_html_e( 'Facebook', 'lawha' ); ?></h4>
                <p class="contact-info-card__text">LAWHA HIJABS</p>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

<?php get_footer(); ?>
