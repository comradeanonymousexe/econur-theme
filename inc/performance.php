<?php
/**
 * Performance & mobile hardening (spec §8).
 *
 * Release-blocking targets: LCP < 2.5s, INP < 200ms, CLS < 0.1 on mid-range
 * Android/4G. These measures trim render-blocking weight on the fully-custom
 * customer pages and remove WooCommerce's cart-fragments TTFB cost.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True on our three fully-custom, block-free customer templates.
 *
 * @return bool
 */
function econur_is_custom_template() {
	return is_front_page()
		|| ( function_exists( 'is_product' ) && is_product() )
		|| is_page_template( 'page-lab-report.php' );
}

/**
 * Trim front-end weight.
 */
add_action( 'wp_enqueue_scripts', 'econur_perf_dequeue', 100 );
function econur_perf_dequeue() {
	// Gutenberg block CSS + global styles are unused on our custom pages. Keep
	// them on cart/checkout/account, which may render blocks.
	if ( econur_is_custom_template() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	}

	// WooCommerce cart-fragments AJAX runs on every page load and is a common
	// cause of slow mobile TTFB. We render the cart count server-side and refresh
	// it cache-safely in main.js, so drop it everywhere except the cart/checkout
	// where its live totals are genuinely useful. (spec §8)
	if ( function_exists( 'is_cart' ) && ! is_cart() && ! is_checkout() ) {
		wp_dequeue_script( 'wc-cart-fragments' );
	}
}

/**
 * Remove the jQuery Migrate legacy shim on the front end (nothing here needs it).
 *
 * @param WP_Scripts $scripts Scripts registry.
 */
add_action( 'wp_default_scripts', 'econur_perf_no_jquery_migrate' );
function econur_perf_no_jquery_migrate( $scripts ) {
	if ( is_admin() ) {
		return;
	}
	if ( isset( $scripts->registered['jquery'] ) && ! empty( $scripts->registered['jquery']->deps ) ) {
		$scripts->registered['jquery']->deps = array_diff(
			$scripts->registered['jquery']->deps,
			array( 'jquery-migrate' )
		);
	}
}

/**
 * Inline a tiny critical-CSS block that reserves the header height and paints
 * the page background immediately, so first paint is stable before the
 * stylesheets load (guards against CLS / flash). Mirrors token values. (spec §8)
 */
add_action( 'wp_head', 'econur_perf_critical_css', 1 );
function econur_perf_critical_css() {
	echo '<style id="econ-critical">body{margin:0;background:#f0fdf8;color:#0d3027;font-family:"DM Sans",system-ui,-apple-system,sans-serif}.econ-header{min-height:72px}.econ-main{display:block;min-height:55vh}img{max-width:100%;height:auto}</style>' . "\n";
}
