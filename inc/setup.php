<?php
/**
 * Theme setup: supports, navigation menus, image sizes.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'econur_setup' );
/**
 * Register core theme supports.
 */
function econur_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 96,
			'width'       => 300,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	// Base WooCommerce support. NOTE: we deliberately DO NOT enable
	// wc-product-gallery-zoom/lightbox/slider — those enqueue heavy scripts
	// (flexslider, zoom, photoswipe). A lightweight custom gallery is built in
	// the single-product phase instead, to protect the mobile-speed budget (§8).
	add_theme_support( 'woocommerce' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'econur' ),
			'footer'  => __( 'Footer Menu', 'econur' ),
		)
	);

	// Mobile-first, high-DPI product imagery.
	add_image_size( 'econur-card', 640, 640, true );   // grid cards.
	add_image_size( 'econur-hero', 1200, 1200, false ); // showcase / product hero.
}

/**
 * Content width for oEmbeds.
 */
add_action( 'after_setup_theme', 'econur_content_width', 0 );
function econur_content_width() {
	$GLOBALS['content_width'] = 1240;
}

/**
 * Trim front-end bloat: drop the emoji detection script/styles (unused, adds
 * a render-blocking inline script + external request). (spec §8)
 */
add_action( 'init', 'econur_disable_emojis' );
function econur_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}

/* -------------------------------------------------------------------------
 * Translation guards for markup we do not otherwise control
 * ---------------------------------------------------------------------- */

add_action( 'after_setup_theme', 'econur_guard_title_tag', 20 );
/**
 * Render <title> with translate="no".
 *
 * The document title carries the brand name and the bar name, and a machine
 * translation of it shows up in the browser tab, in bookmarks, and in anything
 * that scrapes the page. It cannot be wrapped in a span the way body copy can —
 * <title> takes no markup — so the only lever is the attribute on the element
 * itself, and WordPress core renders that element for us.
 *
 * We therefore swap core's renderer for an identical one that adds the
 * attribute. Behaviour is otherwise unchanged: same hook, same priority, same
 * wp_get_document_title() output, deliberately unescaped exactly as core does
 * it (the title is already passed through wptexturize/convert_chars, so
 * escaping here would double-encode entities).
 */
function econur_guard_title_tag() {
	if ( ! current_theme_supports( 'title-tag' ) ) {
		return;
	}
	remove_action( 'wp_head', '_wp_render_title_tag', 1 );
	add_action( 'wp_head', 'econur_render_title_tag', 1 );
}

/**
 * Core's _wp_render_title_tag(), plus translate="no".
 */
function econur_render_title_tag() {
	echo '<title translate="no">' . wp_get_document_title() . '</title>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- matches core; wp_get_document_title() is already filtered.
}

add_filter( 'language_attributes', 'econur_html_translate_hint' );
/**
 * Declare the document's source language explicitly on <html>.
 *
 * GTranslate and Chrome's built-in translator both key off this when deciding
 * what to translate FROM. Without it they guess, and a page whose visible words
 * are mostly product names can be mis-detected — which is how a page ends up
 * "translated" from the wrong language into nonsense.
 *
 * @param string $output Existing language attributes.
 * @return string
 */
function econur_html_translate_hint( $output ) {
	if ( false !== strpos( $output, 'lang=' ) ) {
		return $output;
	}
	return $output . ' lang="en"';
}
