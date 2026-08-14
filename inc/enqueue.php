<?php
/**
 * Front-end asset enqueues.
 *
 * Mobile-first and lean: self-hosted fonts (no Google round-trip), token CSS,
 * shared components, and per-template CSS loaded only where needed. JS is a
 * single deferred, dependency-free file. (spec §8)
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache-busting version: file mtime when the file exists, theme version else.
 *
 * @param string $relative Path relative to theme root.
 * @return string
 */
function econur_asset_ver( $relative ) {
	$file = ECONUR_DIR . '/' . ltrim( $relative, '/' );
	return file_exists( $file ) ? (string) filemtime( $file ) : ECONUR_VERSION;
}

add_action( 'wp_enqueue_scripts', 'econur_enqueue_assets' );
/**
 * Enqueue styles & scripts.
 */
function econur_enqueue_assets() {

	// Self-hosted fonts (font-display: swap declared inside).
	wp_enqueue_style( 'econur-fonts', ECONUR_URI . '/assets/css/fonts.css', array(), econur_asset_ver( 'assets/css/fonts.css' ) );

	// Tokens + base reset (style.css).
	wp_enqueue_style( 'econur-tokens', get_stylesheet_uri(), array( 'econur-fonts' ), econur_asset_ver( 'style.css' ) );

	// Shared components (header, footer, buttons, chips, cards…).
	wp_enqueue_style( 'econur-components', ECONUR_URI . '/assets/css/components.css', array( 'econur-tokens' ), econur_asset_ver( 'assets/css/components.css' ) );

	// Per-template CSS — loaded only where it's used so every page stays lean.
	if ( is_front_page() ) {
		wp_enqueue_style( 'econur-homepage', ECONUR_URI . '/assets/css/homepage.css', array( 'econur-components' ), econur_asset_ver( 'assets/css/homepage.css' ) );
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_enqueue_style( 'econur-product', ECONUR_URI . '/assets/css/product.css', array( 'econur-components' ), econur_asset_ver( 'assets/css/product.css' ) );
	}
	if ( is_page_template( 'page-lab-report.php' ) ) {
		wp_enqueue_style( 'econur-lab', ECONUR_URI . '/assets/css/lab-report.css', array( 'econur-components' ), econur_asset_ver( 'assets/css/lab-report.css' ) );
	}
	// Brand overrides for Woo's own pages only (cart/checkout/account). Depends on
	// woocommerce-general so it always loads AFTER Woo's base CSS on those pages.
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		wp_enqueue_style( 'econur-woo', ECONUR_URI . '/assets/css/woocommerce.css', array( 'econur-components', 'woocommerce-general' ), econur_asset_ver( 'assets/css/woocommerce.css' ) );
	}

	// Sign-in / register toggle. Enhancement only — the panels already switch
	// server-side — so it loads on the one screen that has the switch, and nowhere else.
	if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ) {
		wp_enqueue_script( 'econur-account', ECONUR_URI . '/assets/js/account.js', array(), econur_asset_ver( 'assets/js/account.js' ), true );
	}

	// Interactivity: one hand-written vanilla JS file, deferred, zero deps.
	wp_enqueue_script( 'econur-main', ECONUR_URI . '/assets/js/main.js', array(), econur_asset_ver( 'assets/js/main.js' ), true );
	wp_localize_script(
		'econur-main',
		'econurData',
		array(
			'cartUrl'     => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
			'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
			'nonce'       => wp_create_nonce( 'econur_nonce' ),
			'addToCart'   => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'econur_add_to_cart' ) : '',
			'cartCount'   => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'econur_cart_count' ) : '',
			'i18n'      => array(
				'added'       => __( 'Added to cart', 'econur' ),
				'adding'      => __( 'Adding…', 'econur' ),
				'error'       => __( 'Could not add. Try again.', 'econur' ),
				'addToCart'   => __( 'Add to Cart', 'econur' ),
				'viewDetails' => __( 'View details', 'econur' ),
				'soldOut'     => __( 'Sold out', 'econur' ),
				'chooseSize'  => __( 'Choose size', 'econur' ),
			),
		)
	);

	// Shared buy-box (size selection + AJAX add-to-cart): homepage + product pages.
	$econur_is_product = function_exists( 'is_product' ) && is_product();
	if ( is_front_page() || $econur_is_product ) {
		wp_enqueue_script( 'econur-buybox', ECONUR_URI . '/assets/js/buybox.js', array( 'econur-main' ), econur_asset_ver( 'assets/js/buybox.js' ), true );
	}

	// Homepage-only interactions (grid filter/search + showcase carousel).
	if ( is_front_page() ) {
		wp_enqueue_script( 'econur-homepage', ECONUR_URI . '/assets/js/homepage.js', array( 'econur-buybox' ), econur_asset_ver( 'assets/js/homepage.js' ), true );
		wp_enqueue_script( 'econur-carousel', ECONUR_URI . '/assets/js/carousel.js', array(), econur_asset_ver( 'assets/js/carousel.js' ), true );
	}

	// Product-page-only interactions (lightweight gallery).
	if ( $econur_is_product ) {
		wp_enqueue_script( 'econur-product-js', ECONUR_URI . '/assets/js/product.js', array( 'econur-buybox' ), econur_asset_ver( 'assets/js/product.js' ), true );
	}
}

/**
 * Add `defer` to our main script tag (non-render-blocking). (spec §8)
 */
add_filter( 'script_loader_tag', 'econur_defer_main_js', 10, 2 );
function econur_defer_main_js( $tag, $handle ) {
	$deferred = array( 'econur-main', 'econur-buybox', 'econur-homepage', 'econur-carousel', 'econur-product-js', 'econur-account' );
	if ( in_array( $handle, $deferred, true ) && false === strpos( $tag, ' defer' ) ) {
		$tag = str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}

/**
 * Preload the two above-the-fold font files so text paints fast without a
 * Google Fonts round-trip. (spec §8)
 */
add_action( 'wp_head', 'econur_preload_fonts', 2 );
function econur_preload_fonts() {
	$fonts = array(
		'/assets/fonts/dmsans-400.woff2',
		'/assets/fonts/playfairdisplay-600.woff2',
	);
	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( ECONUR_URI . $font )
		);
	}
}
