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

define( 'LAWHA_VERSION', wp_get_theme()->get( 'Version' ) ?: '1.0.0' );
define( 'LAWHA_DIR', get_template_directory() );
define( 'LAWHA_URI', get_template_directory_uri() );

/**
 * Get a cache-busting version for a specific theme asset.
 */
function lawha_get_asset_version( $relative_path ) {
    $relative_path = ltrim( $relative_path, '/' );
    $file_path     = LAWHA_DIR . '/' . $relative_path;

    if ( is_file( $file_path ) ) {
        return (string) filemtime( $file_path );
    }

    return LAWHA_VERSION;
}

/**
 * Build a deploy identifier from the latest changed theme file.
 */
function lawha_get_build_version() {
    static $build_version = null;

    if ( null !== $build_version ) {
        return $build_version;
    }

    $latest_mtime = 0;
    $scan_paths   = array(
        LAWHA_DIR . '/style.css',
        LAWHA_DIR . '/functions.php',
        LAWHA_DIR . '/header.php',
        LAWHA_DIR . '/footer.php',
        LAWHA_DIR . '/css',
        LAWHA_DIR . '/js',
        LAWHA_DIR . '/woocommerce',
    );

    foreach ( $scan_paths as $scan_path ) {
        if ( is_file( $scan_path ) ) {
            $latest_mtime = max( $latest_mtime, (int) filemtime( $scan_path ) );
            continue;
        }

        if ( ! is_dir( $scan_path ) ) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $scan_path, FilesystemIterator::SKIP_DOTS )
        );

        foreach ( $iterator as $file_info ) {
            if ( ! $file_info->isFile() ) {
                continue;
            }

            $extension = strtolower( $file_info->getExtension() );
            if ( ! in_array( $extension, array( 'php', 'css', 'js' ), true ) ) {
                continue;
            }

            $latest_mtime = max( $latest_mtime, $file_info->getMTime() );
        }
    }

    if ( $latest_mtime > 0 ) {
        $build_version = sprintf( '%s.%s', LAWHA_VERSION, gmdate( 'YmdHis', $latest_mtime ) );
        return $build_version;
    }

    $build_version = LAWHA_VERSION;
    return $build_version;
}

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
    wp_enqueue_style( 'lawha-reset', LAWHA_URI . '/css/reset.css', array(), lawha_get_asset_version( 'css/reset.css' ) );
    wp_enqueue_style( 'lawha-variables', LAWHA_URI . '/css/variables.css', array( 'lawha-reset' ), lawha_get_asset_version( 'css/variables.css' ) );
    wp_enqueue_style( 'lawha-typography', LAWHA_URI . '/css/typography.css', array( 'lawha-variables' ), lawha_get_asset_version( 'css/typography.css' ) );
    wp_enqueue_style( 'lawha-layout', LAWHA_URI . '/css/layout.css', array( 'lawha-variables' ), lawha_get_asset_version( 'css/layout.css' ) );
    wp_enqueue_style( 'lawha-components', LAWHA_URI . '/css/components.css', array( 'lawha-layout' ), lawha_get_asset_version( 'css/components.css' ) );
    wp_enqueue_style( 'lawha-animations', LAWHA_URI . '/css/animations.css', array( 'lawha-components' ), lawha_get_asset_version( 'css/animations.css' ) );
    wp_enqueue_style( 'lawha-responsive', LAWHA_URI . '/css/responsive.css', array( 'lawha-components' ), lawha_get_asset_version( 'css/responsive.css' ) );

    // WooCommerce overrides (only when WooCommerce is active)
    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_style( 'lawha-woocommerce', LAWHA_URI . '/css/woocommerce.css', array( 'lawha-components' ), lawha_get_asset_version( 'css/woocommerce.css' ) );
    }

    // Main theme stylesheet (WordPress requirement, contains only header)
    wp_enqueue_style( 'lawha-style', get_stylesheet_uri(), array( 'lawha-responsive' ), lawha_get_asset_version( 'style.css' ) );
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
        lawha_get_asset_version( 'js/scrollAnimations.js' ),
        array( 'strategy' => 'defer', 'in_footer' => true )
    );

    // Navbar controller
    wp_enqueue_script(
        'lawha-navbar',
        LAWHA_URI . '/js/navbar.js',
        array(),
        lawha_get_asset_version( 'js/navbar.js' ),
        array( 'strategy' => 'defer', 'in_footer' => true )
    );

    // Interactions (lazy load, smooth scroll, hero, etc.)
    wp_enqueue_script(
        'lawha-interactions',
        LAWHA_URI . '/js/interactions.js',
        array(),
        lawha_get_asset_version( 'js/interactions.js' ),
        array( 'strategy' => 'defer', 'in_footer' => true )
    );

    // Main entry point
    wp_enqueue_script(
        'lawha-main',
        LAWHA_URI . '/js/main.js',
        array( 'lawha-scroll-animations', 'lawha-navbar', 'lawha-interactions' ),
        lawha_get_asset_version( 'js/main.js' ),
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
            lawha_get_asset_version( 'js/woocommerce.js' ),
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

        if ( ! $product || ! $product->is_visible() ) {
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

        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
            wp_send_json_error( array( 'message' => __( 'This product cannot be purchased.', 'lawha' ) ) );
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

        if ( empty( $cart_key ) || ! isset( WC()->cart->get_cart()[ $cart_key ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Cart item not found.', 'lawha' ) ) );
        }

        WC()->cart->remove_cart_item( $cart_key );
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

        if ( empty( $cart_key ) || ! isset( WC()->cart->get_cart()[ $cart_key ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Cart item not found.', 'lawha' ) ) );
        }

        if ( $quantity < 1 || $quantity > 99 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid quantity.', 'lawha' ) ) );
        }

        WC()->cart->set_quantity( $cart_key, $quantity );
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

    // Honeypot check.
    if ( ! empty( $_POST['lawha_hp_field'] ) ) {
        // Bot detected — silently redirect to prevent enumeration.
        wp_safe_redirect( wp_get_referer() ?: home_url() );
        exit;
    }

    // Rate limit: 3 submissions per 10 minutes per visitor.
    $rate_key    = 'lawha_cf_rate_' . lawha_visitor_key();
    $submissions = absint( get_transient( $rate_key ) );
    if ( $submissions >= 3 ) {
        set_transient(
            'lawha_contact_errors_' . lawha_visitor_key(),
            array( __( 'Too many submissions. Please try again later.', 'lawha' ) ),
            60
        );
        return;
    }
    set_transient( $rate_key, $submissions + 1, 600 );

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
            'content' => '<h2>Frequently Asked Questions</h2><h3>How do I place an order?</h3><p>You can order directly through our website or via WhatsApp at ' . esc_html( lawha_get_contact_phone() ) . '.</p><h3>What payment methods do you accept?</h3><p>We accept credit/debit cards, bank transfers, and cash on delivery within the UAE.</p><h3>Can I return or exchange a product?</h3><p>Yes, we accept returns and exchanges within 14 days of delivery. Items must be unworn and in original packaging.</p><h3>Do you ship internationally?</h3><p>Yes, we ship worldwide. International shipping rates apply.</p><h3>How do I care for my hijab?</h3><p>We recommend hand washing in cold water or using a delicate machine cycle. Lay flat to dry. Iron on low heat if needed.</p>',
        ),
        'returns' => array(
            'title'   => 'Returns & Exchanges',
            'content' => '<h2>Returns & Exchanges</h2><p>We want you to love your LAWHA hijab. If you are not satisfied, we offer hassle-free returns and exchanges.</p><h3>Return Policy</h3><p>Items may be returned within 14 days of delivery. Products must be unworn, unwashed, and in their original packaging.</p><h3>How to Return</h3><p>Contact us at ' . esc_html( lawha_get_contact_email() ) . ' or via WhatsApp to initiate a return. We will provide you with a return shipping label.</p><h3>Exchanges</h3><p>We offer free exchanges for different colors or sizes, subject to availability.</p><h3>Refunds</h3><p>Refunds will be processed within 5-7 business days after we receive the returned item.</p>',
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

add_action( 'save_post_page', function () {
    delete_transient( 'lawha_footer_pages' );
} );


/* =========================================
   CONTACT INFO HELPERS
   ========================================= */
function lawha_get_contact_phone() {
    return get_theme_mod( 'lawha_contact_phone', '+971501234567' );
}

function lawha_get_contact_email() {
    return get_theme_mod( 'lawha_contact_email', 'hello@lawhahijabs.com' );
}

function lawha_get_whatsapp_number() {
    return preg_replace( '/[^\d]/', '', lawha_get_contact_phone() );
}

add_action( 'customize_register', function ( $wp_customize ) {
    $wp_customize->add_section( 'lawha_contact', array(
        'title'    => __( 'Contact Information', 'lawha' ),
        'priority' => 30,
    ) );

    $wp_customize->add_setting( 'lawha_contact_phone', array( 'default' => '+971501234567', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'lawha_contact_phone', array( 'label' => __( 'Phone Number (E.164)', 'lawha' ), 'section' => 'lawha_contact', 'type' => 'text' ) );

    $wp_customize->add_setting( 'lawha_contact_email', array( 'default' => 'hello@lawhahijabs.com', 'sanitize_callback' => 'sanitize_email' ) );
    $wp_customize->add_control( 'lawha_contact_email', array( 'label' => __( 'Email Address', 'lawha' ), 'section' => 'lawha_contact', 'type' => 'email' ) );
} );

/* =========================================
   SECURITY HEADERS
   ========================================= */
function lawha_security_headers( $headers ) {
    $headers['X-Content-Type-Options'] = 'nosniff';
    $headers['X-Frame-Options']        = 'SAMEORIGIN';
    $headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
    $headers['Permissions-Policy']     = 'camera=(), microphone=(), geolocation=()';
    return $headers;
}
add_filter( 'wp_headers', 'lawha_security_headers' );


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

