<?php
/**
 * One-time content seeding on theme activation.
 *
 * Creates the four canonical product categories (so empty ones render as "Soon"
 * chips) and the three brief testimonials (spec §4.1.3) — rewritten to reference
 * only confirmed-live products (no phantom "Lavender Serenity" / "Charcoal
 * Detox" as purchasable). Guarded so it only ever runs once.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_switch_theme', 'econur_seed_content' );
/**
 * Seed categories + testimonials if not already present.
 */
function econur_seed_content() {
	// 1) Canonical product categories.
	if ( taxonomy_exists( 'product_cat' ) ) {
		foreach ( array( 'Face Care', 'Baby Care', 'Hair Care', 'Daily Care' ) as $cat ) {
			if ( ! term_exists( $cat, 'product_cat' ) ) {
				wp_insert_term( $cat, 'product_cat' );
			}
		}
	}

	// 2) Seed testimonials once.
	if ( ! post_type_exists( 'econ_testimonial' ) ) {
		return;
	}
	$existing = get_posts(
		array(
			'post_type'   => 'econ_testimonial',
			'numberposts' => 1,
			'fields'      => 'ids',
			'post_status' => 'any',
		)
	);
	if ( ! empty( $existing ) ) {
		return;
	}

	$seed = array(
		array( 'Nadia Rahman', 'Dhaka', 5, 'Softest my skin has felt in years — this bar is a permanent fixture in my shower now. I won\'t go back to commercial soap.' ),
		array( 'Tanvir Hossain', 'Chittagong', 5, 'The Active Defense Bar cleared my nose congestion and blackheads in two weeks. Genuinely impressed.' ),
		array( 'Priya Chowdhury', 'Sylhet', 5, 'Ordered a full set as a gift — the eco packaging and the scents are beautiful. Everyone loved them.' ),
	);

	foreach ( $seed as $t ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'econ_testimonial',
				'post_status'  => 'publish',
				'post_title'   => $t[0],
				'post_content' => $t[3],
			)
		);
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_econur_rating', (int) $t[2] );
			update_post_meta( $post_id, '_econur_location', $t[1] );
		}
	}
}
