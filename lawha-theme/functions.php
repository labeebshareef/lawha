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
