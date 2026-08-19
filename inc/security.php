<?php
/**
 * Site hardening.
 *
 * Everything here closes a finding that was verified against the live site. The
 * pieces that genuinely cannot live in PHP — forcing HTTPS, removing readme.html
 * — are listed at the bottom of this file as server steps.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Response headers
 * ---------------------------------------------------------------------- */

add_action( 'send_headers', 'econur_security_headers' );
/**
 * Send the baseline security headers. The live site had none of these.
 *
 * Note on HSTS: it is only emitted over HTTPS, and deliberately WITHOUT
 * `preload` or `includeSubDomains`. Preload is effectively irreversible and
 * subdomains may not all have certificates — both are decisions for the site
 * owner, not defaults a theme should make.
 */
function econur_security_headers() {
	if ( headers_sent() ) {
		return;
	}

	// Stops the browser guessing a file's type and executing something as script.
	header( 'X-Content-Type-Options: nosniff' );

	// Clickjacking: nothing here is meant to be framed by another site.
	header( 'X-Frame-Options: SAMEORIGIN' );

	// Send the page origin to other sites, never the full path or query.
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );

	// The shop needs none of these; deny by default.
	header( 'Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), interest-cohort=()' );

	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
}

add_action( 'init', 'econur_remove_powered_by', 1 );
/**
 * Drop the X-Powered-By header, which advertised the exact PHP build.
 */
function econur_remove_powered_by() {
	if ( ! headers_sent() ) {
		header_remove( 'X-Powered-By' );
	}
}

/* -------------------------------------------------------------------------
 * Reduce the attack surface
 * ---------------------------------------------------------------------- */

// XML-RPC: a brute-force and pingback-amplification vector this shop never uses.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );

add_filter( 'rest_endpoints', 'econur_block_user_enumeration' );
/**
 * Close REST user enumeration.
 *
 * `/wp-json/wp/v2/users` was returning the administrator's login name to anyone
 * who asked — half of a credential pair, free. Editors still need the endpoint
 * inside wp-admin, so it stays available to authenticated users who can list
 * users, and only that.
 *
 * @param array $endpoints REST endpoints.
 * @return array
 */
function econur_block_user_enumeration( $endpoints ) {
	if ( current_user_can( 'list_users' ) ) {
		return $endpoints;
	}

	unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );

	return $endpoints;
}

add_action( 'template_redirect', 'econur_block_author_scan' );
/**
 * Close the `?author=N` variant of the same enumeration, which redirects to an
 * author archive and leaks the login slug in the URL.
 */
function econur_block_author_scan() {
	if ( is_admin() || ! isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only probe check.
		return;
	}
	wp_safe_redirect( home_url( '/' ), 301 );
	exit;
}

// Version disclosure in markup and feeds.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/* -------------------------------------------------------------------------
 * SERVER-SIDE STEPS — these cannot be done from PHP
 * -------------------------------------------------------------------------
 *
 * 1. FORCE HTTPS. The site answers on http:// with 200 and no redirect, so
 *    passwords and delivery addresses can travel in clear text. Add to the
 *    WordPress root .htaccess, ABOVE the "# BEGIN WordPress" block:
 *
 *        RewriteEngine On
 *        RewriteCond %{HTTPS} !=on
 *        RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
 *
 *    Then set Settings → General → both URLs to https://eco-nur.com
 *
 * 2. DELETE /readme.html from the WordPress root — it prints the WP version.
 *    It reappears on every core update, so add it to the deploy checklist.
 *
 * 3. Directory listing for uploads is handled by wp-content/uploads/.htaccess
 *    and index.php, both shipped with this theme's deploy notes.
 * -------------------------------------------------------------------------- */
