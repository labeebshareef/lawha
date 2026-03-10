<?php
/**
 * LAWHA HIJABS Theme Functions
 *
 * @package LAWHA
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LAWHA_VERSION', '1.0.0' );
define( 'LAWHA_DIR', get_template_directory() );
define( 'LAWHA_URI', get_template_directory_uri() );

/* =========================================
   THEME SETUP
   ========================================= */
function lawha_theme_setup() {
    // Let WordPress manage the document title
    add_theme_support( 'title-tag' );

    // Enable post thumbnails (featured images)
    add_theme_support( 'post-thumbnails' );

    // Custom logo support
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 80,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // HTML5 support for core elements
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // WooCommerce support
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );

    // Register navigation menus
    register_nav_menus( array(
        'primary'    => esc_html__( 'Primary Navigation', 'lawha' ),
        'footer'     => esc_html__( 'Footer Navigation', 'lawha' ),
    ) );

    // Set content width
    global $content_width;
    if ( ! isset( $content_width ) ) {
        $content_width = 1440;
    }
}
add_action( 'after_setup_theme', 'lawha_theme_setup' );


/* =========================================
   ENQUEUE STYLES
   ========================================= */
function lawha_enqueue_styles() {
    // Google Fonts — Cormorant Garamond + DM Sans
    wp_enqueue_style(
        'lawha-google-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@400;500;700&display=swap',
        array(),
        null
    );

    // Theme stylesheets (in correct dependency order)
    wp_enqueue_style( 'lawha-reset', LAWHA_URI . '/css/reset.css', array(), LAWHA_VERSION );
    wp_enqueue_style( 'lawha-variables', LAWHA_URI . '/css/variables.css', array( 'lawha-reset' ), LAWHA_VERSION );
    wp_enqueue_style( 'lawha-typography', LAWHA_URI . '/css/typography.css', array( 'lawha-variables' ), LAWHA_VERSION );
    wp_enqueue_style( 'lawha-layout', LAWHA_URI . '/css/layout.css', array( 'lawha-variables' ), LAWHA_VERSION );
    wp_enqueue_style( 'lawha-components', LAWHA_URI . '/css/components.css', array( 'lawha-layout' ), LAWHA_VERSION );
    wp_enqueue_style( 'lawha-animations', LAWHA_URI . '/css/animations.css', array( 'lawha-components' ), LAWHA_VERSION );
    wp_enqueue_style( 'lawha-responsive', LAWHA_URI . '/css/responsive.css', array( 'lawha-components' ), LAWHA_VERSION );

    // WooCommerce overrides (only when WooCommerce is active)
    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_style( 'lawha-woocommerce', LAWHA_URI . '/css/woocommerce.css', array( 'lawha-components' ), LAWHA_VERSION );
    }

    // Main theme stylesheet (WordPress requirement, contains only header)
    wp_enqueue_style( 'lawha-style', get_stylesheet_uri(), array( 'lawha-responsive' ), LAWHA_VERSION );
}
add_action( 'wp_enqueue_scripts', 'lawha_enqueue_styles' );


/* =========================================
   ENQUEUE SCRIPTS
   ========================================= */
function lawha_enqueue_scripts() {
    // Scroll Animations (IntersectionObserver)
    wp_enqueue_script(
        'lawha-scroll-animations',
        LAWHA_URI . '/js/scrollAnimations.js',
        array(),
        LAWHA_VERSION,
        array( 'strategy' => 'defer', 'in_footer' => true )
    );

    // Navbar controller
    wp_enqueue_script(
        'lawha-navbar',
        LAWHA_URI . '/js/navbar.js',
        array(),
        LAWHA_VERSION,
        array( 'strategy' => 'defer', 'in_footer' => true )
    );

    // Interactions (lazy load, smooth scroll, hero, etc.)
    wp_enqueue_script(
        'lawha-interactions',
        LAWHA_URI . '/js/interactions.js',
        array(),
        LAWHA_VERSION,
        array( 'strategy' => 'defer', 'in_footer' => true )
    );

    // Main entry point
    wp_enqueue_script(
        'lawha-main',
        LAWHA_URI . '/js/main.js',
        array( 'lawha-scroll-animations', 'lawha-navbar', 'lawha-interactions' ),
        LAWHA_VERSION,
        array( 'strategy' => 'defer', 'in_footer' => true )
    );
}
add_action( 'wp_enqueue_scripts', 'lawha_enqueue_scripts' );


/* =========================================
   WIDGET AREAS
   ========================================= */
function lawha_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Footer Widget Area', 'lawha' ),
        'id'            => 'footer-widgets',
        'description'   => esc_html__( 'Add widgets for the footer area.', 'lawha' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="footer__heading">',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'lawha_widgets_init' );


/* =========================================
   WOOCOMMERCE CUSTOMIZATIONS
   ========================================= */
if ( class_exists( 'WooCommerce' ) ) {

    // Remove default WooCommerce styles
    add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

    // Products per page
    add_filter( 'loop_shop_per_page', function() {
        return 12;
    } );

    // Product grid columns
    add_filter( 'loop_shop_columns', function() {
        return 4;
    } );

    // Related products args
    add_filter( 'woocommerce_output_related_products_args', function( $args ) {
        $args['posts_per_page'] = 4;
        $args['columns']        = 4;
        return $args;
    } );

    // Remove WooCommerce default wrapper
    remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
    remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

    // Add custom wrappers
    add_action( 'woocommerce_before_main_content', function() {
        echo '<main class="site-main">';
    }, 10 );

    add_action( 'woocommerce_after_main_content', function() {
        echo '</main>';
    }, 10 );

    // Remove default product title, rating, price in loop (we handle in content-product.php)
    remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
    remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
    remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
    remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
}


/* =========================================
   CUSTOM NAV WALKER (optional menu class support)
   ========================================= */
class Lawha_Nav_Walker extends Walker_Nav_Menu {
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;
        $class_string = '';

        // Add navbar__link class + active state
        $link_classes = array( 'navbar__link' );
        if ( in_array( 'current-menu-item', $classes ) || in_array( 'current_page_item', $classes ) ) {
            $link_classes[] = 'active';
        }

        $output .= '<a href="' . esc_url( $item->url ) . '" class="' . esc_attr( implode( ' ', $link_classes ) ) . '">';
        $output .= esc_html( $item->title );
        $output .= '</a>';
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        // No wrapping <li>, just links
    }

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        // No nested <ul>
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        // No nested <ul>
    }
}


/* =========================================
   HELPER FUNCTIONS
   ========================================= */

/**
 * Get the theme asset URI
 */
function lawha_asset( $path ) {
    return esc_url( LAWHA_URI . '/assets/' . ltrim( $path, '/' ) );
}

/**
 * Get featured products for the homepage
 */
function lawha_get_featured_products( $limit = 4 ) {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return array();
    }

    $args = array(
        'limit'    => $limit,
        'status'   => 'publish',
        'featured' => true,
        'orderby'  => 'date',
        'order'    => 'DESC',
    );

    $products = wc_get_products( $args );

    // If no featured products, fall back to latest products
    if ( empty( $products ) ) {
        unset( $args['featured'] );
        $products = wc_get_products( $args );
    }

    return $products;
}

/**
 * Custom excerpt length
 */
function lawha_excerpt_length( $length ) {
    return 20;
}
add_filter( 'excerpt_length', 'lawha_excerpt_length' );

/**
 * Remove WordPress version from head for security
 */
remove_action( 'wp_head', 'wp_generator' );

/**
 * Add body classes
 */
function lawha_body_classes( $classes ) {
    $classes[] = 'page-transition';

    if ( is_front_page() ) {
        $classes[] = 'is-front-page';
    }

    return $classes;
}
add_filter( 'body_class', 'lawha_body_classes' );


/* =========================================
   WOOCOMMERCE AJAX HANDLERS
   ========================================= */
if ( class_exists( 'WooCommerce' ) ) {

    /**
     * Enqueue WooCommerce JS module
     */
    function lawha_enqueue_wc_scripts() {
        wp_enqueue_script(
            'lawha-woocommerce',
            LAWHA_URI . '/js/woocommerce.js',
            array(),
            LAWHA_VERSION,
            true
        );

        wp_localize_script( 'lawha-woocommerce', 'lawhaWC', array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'lawha_wc_nonce' ),
            'cartUrl'     => wc_get_cart_url(),
            'checkoutUrl' => wc_get_checkout_url(),
        ) );
    }
    add_action( 'wp_enqueue_scripts', 'lawha_enqueue_wc_scripts' );

    /**
     * AJAX: Quick View
     */
    function lawha_quick_view_handler() {
        check_ajax_referer( 'lawha_wc_nonce', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $product    = wc_get_product( $product_id );

        if ( ! $product ) {
            wp_send_json_error( array( 'message' => 'Product not found.' ) );
        }

        $image_id   = $product->get_image_id();
        $image_url  = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : wc_placeholder_img_src( 'large' );
        $image_alt  = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : $product->get_name();
        $categories = wp_strip_all_tags( wc_get_product_category_list( $product_id, ', ' ) );
        $max_qty    = $product->get_stock_quantity() ? $product->get_stock_quantity() : 99;

        ob_start();
        ?>
        <div class="lawha-quickview__grid">
            <div class="lawha-quickview__image">
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>">
            </div>
            <div class="lawha-quickview__info">
                <?php if ( $categories ) : ?>
                    <p class="overline"><?php echo esc_html( $categories ); ?></p>
                <?php endif; ?>
                <h2 class="lawha-quickview__title"><?php echo esc_html( $product->get_name() ); ?></h2>
                <div class="divider"></div>
                <p class="lawha-quickview__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
                <?php if ( $product->get_short_description() ) : ?>
                    <div class="lawha-quickview__desc"><?php echo wp_kses_post( $product->get_short_description() ); ?></div>
                <?php endif; ?>

                <?php if ( $product->is_type( 'simple' ) && $product->is_in_stock() ) : ?>
                    <form class="lawha-quickview-cart-form" data-product_id="<?php echo esc_attr( $product_id ); ?>">
                        <div class="lawha-quickview__qty">
                            <label class="form-label"><?php esc_html_e( 'Quantity', 'lawha' ); ?></label>
                            <div class="lawha-qty-wrap">
                                <button type="button" class="lawha-qty-minus">−</button>
                                <input type="number" class="lawha-qty-input" value="1" min="1" max="<?php echo esc_attr( $max_qty ); ?>">
                                <button type="button" class="lawha-qty-plus">+</button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn--primary" style="width:100%;">
                            <?php esc_html_e( 'Add to Cart', 'lawha' ); ?>
                            <span class="btn__arrow">→</span>
                        </button>
                    </form>
                <?php elseif ( ! $product->is_in_stock() ) : ?>
                    <p class="product-detail__stock-status"><?php esc_html_e( 'Out of Stock', 'lawha' ); ?></p>
                <?php endif; ?>

                <a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="btn btn--outline" style="width:100%;margin-top:var(--space-3);">
                    <?php esc_html_e( 'View Full Details', 'lawha' ); ?>
                    <span class="btn__arrow">→</span>
                </a>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }
    add_action( 'wp_ajax_lawha_quick_view', 'lawha_quick_view_handler' );
    add_action( 'wp_ajax_nopriv_lawha_quick_view', 'lawha_quick_view_handler' );

    /**
     * AJAX: Add to Cart
     */
    function lawha_add_to_cart_handler() {
        check_ajax_referer( 'lawha_wc_nonce', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $quantity   = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => 'Invalid product.' ) );
        }

        $added = WC()->cart->add_to_cart( $product_id, $quantity );

        if ( $added ) {
            wp_send_json_success( array(
                'message' => 'Product added to cart.',
                'count'   => WC()->cart->get_cart_contents_count(),
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Could not add product to cart.' ) );
        }
    }
    add_action( 'wp_ajax_lawha_add_to_cart', 'lawha_add_to_cart_handler' );
    add_action( 'wp_ajax_nopriv_lawha_add_to_cart', 'lawha_add_to_cart_handler' );

    /**
     * AJAX: Get Mini Cart HTML
     */
    function lawha_get_mini_cart_handler() {
        check_ajax_referer( 'lawha_wc_nonce', 'nonce' );

        ob_start();
        lawha_render_mini_cart_items();
        $cart_html = ob_get_clean();

        wp_send_json_success( array(
            'cart_html' => $cart_html,
            'subtotal'  => WC()->cart->get_cart_subtotal(),
            'count'     => WC()->cart->get_cart_contents_count(),
        ) );
    }
    add_action( 'wp_ajax_lawha_get_mini_cart', 'lawha_get_mini_cart_handler' );
    add_action( 'wp_ajax_nopriv_lawha_get_mini_cart', 'lawha_get_mini_cart_handler' );

    /**
     * AJAX: Remove Cart Item
     */
    function lawha_remove_cart_item_handler() {
        check_ajax_referer( 'lawha_wc_nonce', 'nonce' );

        $cart_key = isset( $_POST['cart_key'] ) ? sanitize_text_field( $_POST['cart_key'] ) : '';

        if ( $cart_key ) {
            WC()->cart->remove_cart_item( $cart_key );
        }

        wp_send_json_success();
    }
    add_action( 'wp_ajax_lawha_remove_cart_item', 'lawha_remove_cart_item_handler' );
    add_action( 'wp_ajax_nopriv_lawha_remove_cart_item', 'lawha_remove_cart_item_handler' );

    /**
     * AJAX: Update Cart Quantity
     */
    function lawha_update_cart_qty_handler() {
        check_ajax_referer( 'lawha_wc_nonce', 'nonce' );

        $cart_key = isset( $_POST['cart_key'] ) ? sanitize_text_field( $_POST['cart_key'] ) : '';
        $quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;

        if ( $cart_key ) {
            WC()->cart->set_quantity( $cart_key, $quantity );
        }

        wp_send_json_success();
    }
    add_action( 'wp_ajax_lawha_update_cart_qty', 'lawha_update_cart_qty_handler' );
    add_action( 'wp_ajax_nopriv_lawha_update_cart_qty', 'lawha_update_cart_qty_handler' );

    /**
     * Render mini cart items
     */
    function lawha_render_mini_cart_items() {
        $cart = WC()->cart->get_cart();
        if ( empty( $cart ) ) {
            echo '<div class="lawha-minicart__empty">';
            echo '<p>' . esc_html__( 'Your cart is empty.', 'lawha' ) . '</p>';
            echo '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="btn btn--outline" style="margin-top:var(--space-4);">' . esc_html__( 'Browse Collection', 'lawha' ) . ' <span class="btn__arrow">→</span></a>';
            echo '</div>';
            return;
        }

        foreach ( $cart as $cart_key => $cart_item ) {
            $product   = $cart_item['data'];
            $qty       = $cart_item['quantity'];
            $image_id  = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' );
            ?>
            <div class="lawha-minicart__item">
                <div class="lawha-minicart__item-image">
                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">
                </div>
                <div class="lawha-minicart__item-details">
                    <h4 class="lawha-minicart__item-name"><?php echo esc_html( $product->get_name() ); ?></h4>
                    <p class="lawha-minicart__item-price"><?php echo wp_kses_post( WC()->cart->get_product_price( $product ) ); ?> × <?php echo esc_html( $qty ); ?></p>
                    <div class="lawha-minicart__item-actions">
                        <input type="number" class="lawha-minicart-qty" data-cart-key="<?php echo esc_attr( $cart_key ); ?>" value="<?php echo esc_attr( $qty ); ?>" min="1" max="99">
                        <button type="button" class="lawha-minicart-remove" data-cart-key="<?php echo esc_attr( $cart_key ); ?>" aria-label="<?php esc_attr_e( 'Remove item', 'lawha' ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    /**
     * WooCommerce cart fragments (auto-update cart counter)
     */
    function lawha_cart_fragments( $fragments ) {
        $count = WC()->cart->get_cart_contents_count();
        $fragments['.lawha-cart-count'] = '<span class="lawha-cart-count"' . ( $count > 0 ? '' : ' style="display:none"' ) . '>' . esc_html( $count ) . '</span>';
        return $fragments;
    }
    add_filter( 'woocommerce_add_to_cart_fragments', 'lawha_cart_fragments' );
}


/* =========================================
   CONTACT FORM HANDLER
   ========================================= */

/**
 * Generate a unique visitor key using a cookie (avoids session_id issues).
 */
function lawha_visitor_key() {
    if ( isset( $_COOKIE['lawha_vk'] ) ) {
        return sanitize_text_field( $_COOKIE['lawha_vk'] );
    }
    $key = wp_generate_password( 12, false );
    setcookie( 'lawha_vk', $key, time() + 300, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
    $_COOKIE['lawha_vk'] = $key; // make available immediately
    return $key;
}

function lawha_handle_contact_form() {
    if ( ! isset( $_POST['lawha_contact_submit'] ) ) {
        return;
    }

    if ( ! isset( $_POST['lawha_nonce'] ) || ! wp_verify_nonce( $_POST['lawha_nonce'], 'lawha_contact_form' ) ) {
        return;
    }

    $name    = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
    $email   = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
    $subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : 'Contact Form Inquiry';
    $message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';

    // Validate
    $errors = array();
    if ( empty( $name ) )    $errors[] = __( 'Name is required.', 'lawha' );
    if ( empty( $email ) || ! is_email( $email ) ) $errors[] = __( 'A valid email is required.', 'lawha' );
    if ( empty( $message ) ) $errors[] = __( 'Message is required.', 'lawha' );

    if ( ! empty( $errors ) ) {
        set_transient( 'lawha_contact_errors_' . lawha_visitor_key(), $errors, 60 );
        set_transient( 'lawha_contact_data_' . lawha_visitor_key(), $_POST, 60 );
        return;
    }

    $to      = get_option( 'admin_email' );
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $name . ' <' . $email . '>',
        'Reply-To: ' . $email,
    );

    $email_body  = '<h2>New Contact Form Message</h2>';
    $email_body .= '<p><strong>Name:</strong> ' . esc_html( $name ) . '</p>';
    $email_body .= '<p><strong>Email:</strong> ' . esc_html( $email ) . '</p>';
    $email_body .= '<p><strong>Subject:</strong> ' . esc_html( $subject ) . '</p>';
    $email_body .= '<p><strong>Message:</strong></p>';
    $email_body .= '<p>' . nl2br( esc_html( $message ) ) . '</p>';

    $sent = wp_mail( $to, '[LAWHA Contact] ' . $subject, $email_body, $headers );

    if ( $sent ) {
        set_transient( 'lawha_contact_success_' . lawha_visitor_key(), true, 60 );
    } else {
        set_transient( 'lawha_contact_errors_' . lawha_visitor_key(), array( __( 'Failed to send message. Please try again.', 'lawha' ) ), 60 );
    }
}
add_action( 'init', 'lawha_handle_contact_form' );


/* =========================================
   CREATE PAGES ON THEME ACTIVATION
   ========================================= */
function lawha_create_pages() {
    $pages = array(
        'shipping-policy' => array(
            'title'   => 'Shipping Policy',
            'content' => '<h2>Shipping Policy</h2><p>We offer shipping across the UAE and internationally. Standard delivery takes 3-5 business days within the UAE and 7-14 business days for international orders.</p><h3>Shipping Rates</h3><p>Free shipping on all orders above AED 300. Standard shipping fee of AED 25 applies to orders below AED 300.</p><h3>Tracking</h3><p>Once your order is shipped, you will receive a tracking number via email.</p>',
        ),
        'size-guide' => array(
            'title'   => 'Size Guide',
            'content' => '<h2>Hijab Size Guide</h2><p>Our hijabs come in standard generous sizes designed to suit various styling preferences.</p><h3>Standard Hijab</h3><p>180 cm × 70 cm — Perfect for everyday wrapping styles.</p><h3>Maxi Hijab</h3><p>200 cm × 80 cm — Ideal for full coverage and draping styles.</p><h3>Square Hijab</h3><p>120 cm × 120 cm — Versatile for Turkish and folded styles.</p>',
        ),
        'faq' => array(
            'title'   => 'FAQ',
            'content' => '<h2>Frequently Asked Questions</h2><h3>How do I place an order?</h3><p>You can order directly through our website or via WhatsApp at +971 50 123 4567.</p><h3>What payment methods do you accept?</h3><p>We accept credit/debit cards, bank transfers, and cash on delivery within the UAE.</p><h3>Can I return or exchange a product?</h3><p>Yes, we accept returns and exchanges within 14 days of delivery. Items must be unworn and in original packaging.</p><h3>Do you ship internationally?</h3><p>Yes, we ship worldwide. International shipping rates apply.</p><h3>How do I care for my hijab?</h3><p>We recommend hand washing in cold water or using a delicate machine cycle. Lay flat to dry. Iron on low heat if needed.</p>',
        ),
        'returns' => array(
            'title'   => 'Returns & Exchanges',
            'content' => '<h2>Returns & Exchanges</h2><p>We want you to love your LAWHA hijab. If you are not satisfied, we offer hassle-free returns and exchanges.</p><h3>Return Policy</h3><p>Items may be returned within 14 days of delivery. Products must be unworn, unwashed, and in their original packaging.</p><h3>How to Return</h3><p>Contact us at hello@lawhahijabs.com or via WhatsApp to initiate a return. We will provide you with a return shipping label.</p><h3>Exchanges</h3><p>We offer free exchanges for different colors or sizes, subject to availability.</p><h3>Refunds</h3><p>Refunds will be processed within 5-7 business days after we receive the returned item.</p>',
        ),
    );

    foreach ( $pages as $slug => $page_data ) {
        if ( ! get_page_by_path( $slug ) ) {
            wp_insert_post( array(
                'post_title'   => $page_data['title'],
                'post_name'    => $slug,
                'post_content' => $page_data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ) );
        }
    }
}
add_action( 'after_switch_theme', 'lawha_create_pages' );


/* =========================================
   PERFORMANCE & COMPATIBILITY
   ========================================= */

/**
 * Add resource hints for performance
 */
function lawha_resource_hints( $urls, $relation_type ) {
    if ( 'preconnect' === $relation_type ) {
        $urls[] = array(
            'href'        => 'https://fonts.googleapis.com',
            'crossorigin' => true,
        );
        $urls[] = array(
            'href'        => 'https://fonts.gstatic.com',
            'crossorigin' => true,
        );
    }
    return $urls;
}
add_filter( 'wp_resource_hints', 'lawha_resource_hints', 10, 2 );

/**
 * Remove unnecessary meta tags for clean HTML output
 */
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'feed_links_extra', 3 );

/**
 * Add loading="lazy" to all images in content
 */
function lawha_lazy_load_content_images( $content ) {
    if ( is_admin() || is_feed() ) {
        return $content;
    }
    // WordPress 5.5+ adds lazy loading automatically, this is a fallback
    return $content;
}
add_filter( 'the_content', 'lawha_lazy_load_content_images' );

/**
 * Add Yoast SEO breadcrumb support
 */
function lawha_yoast_breadcrumbs() {
    if ( function_exists( 'yoast_breadcrumb' ) && ! is_front_page() ) {
        echo '<div class="container" style="padding-top:var(--space-4);padding-bottom:var(--space-3);">';
        yoast_breadcrumb( '<nav class="lawha-breadcrumbs" style="font-size:var(--text-xs);color:var(--text-secondary);">', '</nav>' );
        echo '</div>';
    }
}
add_action( 'woocommerce_before_main_content', 'lawha_yoast_breadcrumbs', 15 );

/* WooCommerce styles already disabled in WooCommerce Customizations section */

/**
 * Disable emojis for performance
 */
function lawha_disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'lawha_disable_emojis' );


/* =========================================
   MANDATORY PHONE ON REGISTRATION
   ========================================= */

/**
 * Validate that a phone number is provided during WooCommerce registration.
 *
 * @param string   $username  Username.
 * @param string   $email     Email.
 * @param WP_Error $errors    Validation errors.
 */
function lawha_validate_registration_phone( $username, $email, $errors ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC handles nonce
    $phone = isset( $_POST['lawha_reg_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['lawha_reg_phone'] ) ) : '';

    if ( empty( $phone ) ) {
        $errors->add( 'lawha_reg_phone_error', __( '<strong>Error</strong>: Phone number is required.', 'lawha' ) );
        return;
    }

    // Basic E.164-ish validation: must start with + and have 8-15 digits after it.
    $digits_only = preg_replace( '/[^\d]/', '', $phone );
    if ( strlen( $digits_only ) < 8 || strlen( $digits_only ) > 15 ) {
        $errors->add( 'lawha_reg_phone_error', __( '<strong>Error</strong>: Please enter a valid phone number with country code (e.g. +966 5XX XXX XXXX).', 'lawha' ) );
        return;
    }

    // Check for duplicate phone number.
    $normalized = lawha_normalize_phone( $phone );
    $existing   = get_users( array(
        'meta_key'   => 'billing_phone',
        'meta_value' => $normalized,
        'number'     => 1,
        'fields'     => 'ID',
    ) );
    if ( ! empty( $existing ) ) {
        $existing_wfpl = get_users( array(
            'meta_key'   => 'wfpl_phone',
            'meta_value' => $normalized,
            'number'     => 1,
            'fields'     => 'ID',
        ) );
        if ( ! empty( $existing ) || ! empty( $existing_wfpl ) ) {
            $errors->add( 'lawha_reg_phone_error', __( '<strong>Error</strong>: An account with this phone number already exists. Please log in instead.', 'lawha' ) );
        }
    }
}
add_action( 'woocommerce_register_post', 'lawha_validate_registration_phone', 10, 3 );

/**
 * Save user data after successful WooCommerce registration.
 */
function lawha_save_registration_data( $customer_id ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC handles nonce
    $name  = isset( $_POST['lawha_reg_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lawha_reg_name'] ) ) : '';
    $phone = isset( $_POST['lawha_reg_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['lawha_reg_phone'] ) ) : '';

    if ( $name ) {
        $parts = explode( ' ', $name, 2 );
        wp_update_user( array(
            'ID'           => $customer_id,
            'first_name'   => $parts[0],
            'last_name'    => isset( $parts[1] ) ? $parts[1] : '',
            'display_name' => $name,
        ) );
        update_user_meta( $customer_id, 'billing_first_name', $parts[0] );
        update_user_meta( $customer_id, 'billing_last_name', isset( $parts[1] ) ? $parts[1] : '' );
    }

    if ( $phone ) {
        $normalized = lawha_normalize_phone( $phone );
        update_user_meta( $customer_id, 'billing_phone', $normalized );
        update_user_meta( $customer_id, 'wfpl_phone', $normalized );
        update_user_meta( $customer_id, 'wfpl_phone_verified', 1 );
    }

    // Clear OTP session data.
    if ( WC()->session ) {
        WC()->session->set( 'lawha_otp_verified_phone', '' );
    }

    // Send email verification.
    lawha_send_verification_email( $customer_id );
}
add_action( 'woocommerce_created_customer', 'lawha_save_registration_data', 10, 1 );

/**
 * Normalize a phone number to E.164 format.
 *
 * @param  string $phone Raw phone input.
 * @return string        Normalized E.164 phone.
 */
function lawha_normalize_phone( $phone ) {
    $has_plus = ( substr( trim( $phone ), 0, 1 ) === '+' );
    $digits   = preg_replace( '/[^\d]/', '', $phone );

    if ( empty( $digits ) ) {
        return $phone;
    }

    if ( $has_plus ) {
        return '+' . $digits;
    }

    // International prefix 00 → +
    if ( substr( $digits, 0, 2 ) === '00' ) {
        return '+' . substr( $digits, 2 );
    }

    // Saudi local: 05xxxxxxxx → +9665xxxxxxxx
    if ( substr( $digits, 0, 1 ) === '0' && strlen( $digits ) === 10 ) {
        return '+966' . substr( $digits, 1 );
    }

    return '+' . $digits;
}


/* =========================================
   PHONE-FIRST AUTHENTICATION ASSETS
   ========================================= */

/**
 * Enqueue phone-auth.js on account & checkout pages
 * when the WFPL plugin is active in headless mode.
 */
function lawha_enqueue_phone_auth() {
    if ( ! function_exists( 'wfpl_get_option' ) ) {
        return;
    }

    // Only load when Firebase is configured.
    $api_key    = wfpl_get_option( 'firebase_api_key', '' );
    $project_id = wfpl_get_option( 'firebase_project_id', '' );
    if ( empty( $api_key ) || empty( $project_id ) ) {
        return;
    }

    // Load on My Account (login/register) and checkout pages for guests.
    $is_auth_page = ( function_exists( 'is_account_page' ) && is_account_page() )
                 || ( function_exists( 'is_checkout' ) && is_checkout() );

    if ( ! $is_auth_page ) {
        return;
    }

    // The WFPL plugin (headless mode) enqueues firebase SDK + intl-tel-input
    // as 'wfpl-auth'. Our script depends on it.
    wp_enqueue_script(
        'lawha-phone-auth',
        LAWHA_URI . '/js/phone-auth.js',
        array( 'jquery', 'wfpl-auth' ),
        LAWHA_VERSION,
        array( 'in_footer' => true )
    );

    wp_localize_script( 'lawha-phone-auth', 'lawhaAuth', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'lawha_wc_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'lawha_enqueue_phone_auth', 20 );


/* =========================================
   CHECKOUT — FORCE LOGIN (NO GUEST CHECKOUT)
   ========================================= */

/**
 * Redirect guests away from checkout to the login page.
 */
function lawha_checkout_force_login() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    if ( is_checkout() && ! is_user_logged_in() && ! is_wc_endpoint_url( 'order-received' ) ) {
        $myaccount_url = wc_get_page_permalink( 'myaccount' );
        $redirect      = add_query_arg( 'redirect_to', urlencode( wc_get_checkout_url() ), $myaccount_url );
        wp_safe_redirect( $redirect );
        exit;
    }
}
add_action( 'template_redirect', 'lawha_checkout_force_login' );

/* =========================================
   ALLOW PHONE NUMBER AS LOGIN IDENTIFIER
   ========================================= */

/**
 * When a user enters a phone number as username, look up the
 * actual WordPress user and authenticate with their credentials.
 */
function lawha_authenticate_by_phone( $user, $username, $password ) {
    if ( $user instanceof \WP_User || is_wp_error( $user ) ) {
        return $user;
    }

    if ( empty( $username ) || empty( $password ) ) {
        return $user;
    }

    // Check if username looks like a phone number.
    $cleaned = preg_replace( '/[\s\-\(\)]/', '', $username );
    if ( ! preg_match( '/^\+?\d{8,15}$/', $cleaned ) ) {
        return $user;
    }

    $normalized = lawha_normalize_phone( $cleaned );

    $users = get_users( array(
        'meta_query' => array(
            'relation' => 'OR',
            array( 'key' => 'wfpl_phone', 'value' => $normalized ),
            array( 'key' => 'billing_phone', 'value' => $normalized ),
        ),
        'number' => 1,
    ) );

    if ( empty( $users ) ) {
        return $user;
    }

    $found_user = $users[0];

    // Authenticate with the found user's login name.
    $auth_user = wp_authenticate_username_password( null, $found_user->user_login, $password );
    return $auth_user;
}
add_filter( 'authenticate', 'lawha_authenticate_by_phone', 20, 3 );


/* =========================================
   REGISTRATION OTP SESSION HANDLER (AJAX)
   ========================================= */

/**
 * Store verified phone in session when OTP is verified during registration.
 * Called via AJAX from the registration form.
 */
function lawha_ajax_store_otp_verification() {
    check_ajax_referer( 'lawha_wc_nonce', 'nonce' );

    $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
    if ( empty( $phone ) ) {
        wp_send_json_error( array( 'message' => 'Phone number is required.' ) );
    }

    $normalized = lawha_normalize_phone( $phone );

    if ( ! WC()->session ) {
        WC()->initialize_session();
    }
    WC()->session->set( 'lawha_otp_verified_phone', $normalized );

    wp_send_json_success( array( 'phone' => $normalized ) );
}
add_action( 'wp_ajax_lawha_store_otp_verification', 'lawha_ajax_store_otp_verification' );
add_action( 'wp_ajax_nopriv_lawha_store_otp_verification', 'lawha_ajax_store_otp_verification' );


/* =========================================
   FORGOT PASSWORD VIA OTP (AJAX)
   ========================================= */

/**
 * Check if a phone number is associated with an account.
 */
function lawha_ajax_forgot_check_phone() {
    check_ajax_referer( 'lawha_wc_nonce', 'nonce' );

    $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
    if ( empty( $phone ) ) {
        wp_send_json_error( array( 'message' => 'Phone number is required.' ) );
    }

    $normalized = lawha_normalize_phone( $phone );
    $users = get_users( array(
        'meta_query' => array(
            'relation' => 'OR',
            array( 'key' => 'wfpl_phone', 'value' => $normalized ),
            array( 'key' => 'billing_phone', 'value' => $normalized ),
        ),
        'number' => 1,
    ) );

    if ( empty( $users ) ) {
        wp_send_json_error( array( 'message' => 'No account found with this phone number.' ) );
    }

    wp_send_json_success( array( 'found' => true ) );
}
add_action( 'wp_ajax_lawha_forgot_check_phone', 'lawha_ajax_forgot_check_phone' );
add_action( 'wp_ajax_nopriv_lawha_forgot_check_phone', 'lawha_ajax_forgot_check_phone' );

/**
 * Reset password after OTP verification.
 */
function lawha_ajax_forgot_reset_password() {
    check_ajax_referer( 'lawha_wc_nonce', 'nonce' );

    $phone        = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
    $new_password = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
    $id_token     = isset( $_POST['id_token'] ) ? sanitize_text_field( wp_unslash( $_POST['id_token'] ) ) : '';

    if ( empty( $phone ) || empty( $new_password ) ) {
        wp_send_json_error( array( 'message' => 'Phone and new password are required.' ) );
    }

    if ( strlen( $new_password ) < 8 ) {
        wp_send_json_error( array( 'message' => 'Password must be at least 8 characters.' ) );
    }

    // Verify the Firebase ID token to ensure OTP was genuinely completed.
    if ( ! empty( $id_token ) && class_exists( 'WFPL\Firebase_Auth' ) ) {
        $payload = \WFPL\Firebase_Auth::verify_id_token( $id_token );
        if ( is_wp_error( $payload ) ) {
            wp_send_json_error( array( 'message' => 'OTP verification failed. Please try again.' ) );
        }
        $token_phone = isset( $payload['phone_number'] ) ? $payload['phone_number'] : '';
        $normalized  = lawha_normalize_phone( $phone );
        if ( $token_phone !== $normalized ) {
            wp_send_json_error( array( 'message' => 'Phone number mismatch.' ) );
        }
    }

    $normalized = lawha_normalize_phone( $phone );
    $users = get_users( array(
        'meta_query' => array(
            'relation' => 'OR',
            array( 'key' => 'wfpl_phone', 'value' => $normalized ),
            array( 'key' => 'billing_phone', 'value' => $normalized ),
        ),
        'number' => 1,
    ) );

    if ( empty( $users ) ) {
        wp_send_json_error( array( 'message' => 'No account found with this phone number.' ) );
    }

    $user = $users[0];
    wp_set_password( $new_password, $user->ID );

    wp_send_json_success( array( 'message' => 'Password reset successfully.' ) );
}
add_action( 'wp_ajax_lawha_forgot_reset_password', 'lawha_ajax_forgot_reset_password' );
add_action( 'wp_ajax_nopriv_lawha_forgot_reset_password', 'lawha_ajax_forgot_reset_password' );


/* =========================================
   EMAIL VERIFICATION SYSTEM
   ========================================= */

/**
 * Send a verification email to a newly registered user.
 */
function lawha_send_verification_email( $user_id ) {
    $user = get_user_by( 'ID', $user_id );
    if ( ! $user || ! is_email( $user->user_email ) ) {
        return;
    }

    // Don't send for placeholder emails.
    if ( strpos( $user->user_email, '@noreply.' ) !== false ) {
        return;
    }

    $token = wp_generate_password( 32, false );
    update_user_meta( $user_id, 'lawha_email_verify_token', $token );
    update_user_meta( $user_id, 'lawha_email_verify_sent', time() );
    update_user_meta( $user_id, 'lawha_email_verified', 0 );

    // 7-day grace period.
    update_user_meta( $user_id, 'lawha_email_verify_deadline', time() + ( 7 * DAY_IN_SECONDS ) );

    $verify_url = add_query_arg( array(
        'lawha_verify_email' => $token,
        'uid'                => $user_id,
    ), home_url( '/' ) );

    $site_name = get_bloginfo( 'name' );
    $subject   = sprintf( __( 'Verify your email — %s', 'lawha' ), $site_name );
    $message   = sprintf(
        __( "Hello %s,\n\nThank you for creating an account with %s.\n\nPlease verify your email address by clicking the link below:\n\n%s\n\nThis link will expire in 7 days.\n\nIf you did not create this account, you can safely ignore this email.\n\nBest regards,\n%s", 'lawha' ),
        $user->display_name ?: $user->user_login,
        $site_name,
        esc_url( $verify_url ),
        $site_name
    );

    wp_mail( $user->user_email, $subject, $message );
}

/**
 * Handle email verification link clicks.
 */
function lawha_handle_email_verification() {
    if ( ! isset( $_GET['lawha_verify_email'] ) || ! isset( $_GET['uid'] ) ) {
        return;
    }

    $token   = sanitize_text_field( $_GET['lawha_verify_email'] );
    $user_id = absint( $_GET['uid'] );

    if ( empty( $token ) || empty( $user_id ) ) {
        return;
    }

    $stored_token = get_user_meta( $user_id, 'lawha_email_verify_token', true );
    $deadline     = get_user_meta( $user_id, 'lawha_email_verify_deadline', true );

    if ( empty( $stored_token ) || ! hash_equals( $stored_token, $token ) ) {
        wc_add_notice( __( 'Invalid verification link.', 'lawha' ), 'error' );
        wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
        exit;
    }

    if ( $deadline && time() > (int) $deadline ) {
        wc_add_notice( __( 'Verification link has expired. Please request a new one.', 'lawha' ), 'error' );
        wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
        exit;
    }

    update_user_meta( $user_id, 'lawha_email_verified', 1 );
    delete_user_meta( $user_id, 'lawha_email_verify_token' );

    wc_add_notice( __( 'Email verified successfully!', 'lawha' ), 'success' );
    wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
    exit;
}
add_action( 'init', 'lawha_handle_email_verification' );

/**
 * AJAX: Resend verification email.
 */
function lawha_ajax_resend_verification_email() {
    check_ajax_referer( 'lawha_wc_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
    }

    $user_id   = get_current_user_id();
    $last_sent = get_user_meta( $user_id, 'lawha_email_verify_sent', true );

    // Rate limit: 1 email per 60 seconds.
    if ( $last_sent && ( time() - (int) $last_sent ) < 60 ) {
        wp_send_json_error( array( 'message' => 'Please wait before requesting another email.' ) );
    }

    lawha_send_verification_email( $user_id );
    wp_send_json_success( array( 'message' => 'Verification email sent!' ) );
}
add_action( 'wp_ajax_lawha_resend_verification_email', 'lawha_ajax_resend_verification_email' );

