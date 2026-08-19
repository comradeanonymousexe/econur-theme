<?php
/**
 * Econur theme bootstrap.
 *
 * Intentionally thin: it only wires the theme together by requiring focused
 * modules from /inc. No business or presentational logic lives here. (spec §2)
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ECONUR_VERSION', '0.1.0' );
define( 'ECONUR_DIR', get_template_directory() );
define( 'ECONUR_URI', get_template_directory_uri() );

/**
 * Require a theme module if it exists (keeps the theme load-safe while later
 * phases are still landing their files).
 *
 * @param string $relative Path relative to the theme root.
 */
function econur_require( $relative ) {
	$file = ECONUR_DIR . '/' . ltrim( $relative, '/' );
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

econur_require( 'inc/security.php' );
econur_require( 'inc/setup.php' );
econur_require( 'inc/template-tags.php' );
econur_require( 'inc/content-helpers.php' );
econur_require( 'inc/enqueue.php' );
econur_require( 'inc/taxonomies.php' );
econur_require( 'inc/performance.php' );

// WooCommerce-dependent wiring loads only when WooCommerce is active.
if ( class_exists( 'WooCommerce' ) ) {
	econur_require( 'inc/woocommerce.php' );
	econur_require( 'inc/auth.php' );
	econur_require( 'inc/account.php' );
	econur_require( 'inc/profile.php' );
	econur_require( 'inc/import.php' );
}

// Landing in later phases — guarded so activation never fatals early.
econur_require( 'inc/customizer.php' );          // Phase 3: Featured Product, social links.
econur_require( 'inc/meta/product-meta.php' );   // Phase 3: product narrative meta boxes.
econur_require( 'inc/meta/page-meta.php' );       // Phase 3: lab-report + homepage fields.
econur_require( 'inc/cpt-testimonial.php' );      // Phase 3: Testimonial custom post type.
