<?php
/**
 * Testimonial custom post type + rating/location meta.
 *
 * Non-public by design: testimonials are rendered by the theme via WP_Query on
 * the homepage, and have no single-page route — so this adds NO new front-end
 * template and keeps us at exactly three templates (spec §1). (spec §4.1.3)
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'econur_register_testimonial_cpt' );
/**
 * Register the testimonial post type.
 */
function econur_register_testimonial_cpt() {
	register_post_type(
		'econ_testimonial',
		array(
			'labels'       => array(
				'name'          => __( 'Testimonials', 'econur' ),
				'singular_name' => __( 'Testimonial', 'econur' ),
				'add_new_item'  => __( 'Add New Testimonial', 'econur' ),
				'edit_item'     => __( 'Edit Testimonial', 'econur' ),
				'new_item'      => __( 'New Testimonial', 'econur' ),
				'view_item'     => __( 'View Testimonial', 'econur' ),
				'search_items'  => __( 'Search Testimonials', 'econur' ),
				'menu_name'     => __( 'Testimonials', 'econur' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-format-quote',
			'menu_position' => 26,
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'has_archive'  => false,
			'rewrite'      => false,
		)
	);
}

add_action( 'add_meta_boxes', 'econur_add_testimonial_meta' );
/**
 * Register the testimonial details meta box.
 */
function econur_add_testimonial_meta() {
	add_meta_box(
		'econur_testimonial_meta',
		__( 'Testimonial Details', 'econur' ),
		'econur_render_testimonial_meta',
		'econ_testimonial',
		'side',
		'high'
	);
}

/**
 * Render the testimonial meta box.
 *
 * @param WP_Post $post Current testimonial.
 */
function econur_render_testimonial_meta( $post ) {
	wp_nonce_field( 'econur_testimonial_meta', 'econur_testimonial_meta_nonce' );

	$rating   = (int) get_post_meta( $post->ID, '_econur_rating', true );
	$rating   = ( $rating >= 1 && $rating <= 5 ) ? $rating : 5;
	$location = get_post_meta( $post->ID, '_econur_location', true );

	echo '<p><label for="econur_rating" style="font-weight:600;display:block;margin-bottom:4px;">' . esc_html__( 'Rating', 'econur' ) . '</label>';
	echo '<select id="econur_rating" name="econur_rating" class="widefat">';
	for ( $i = 5; $i >= 1; $i-- ) {
		printf( '<option value="%1$d"%2$s>%1$d ★</option>', $i, selected( $rating, $i, false ) );
	}
	echo '</select></p>';

	echo '<p><label for="econur_location" style="font-weight:600;display:block;margin-bottom:4px;">' . esc_html__( 'Location', 'econur' ) . '</label>';
	printf(
		'<input type="text" id="econur_location" name="econur_location" value="%s" class="widefat" placeholder="%s"></p>',
		esc_attr( $location ),
		esc_attr__( 'e.g. Dhaka', 'econur' )
	);

	echo '<p class="description">' . esc_html__( 'Post title = customer name. The content editor = the quote itself. Featured image = optional avatar.', 'econur' ) . '</p>';
}

add_action( 'save_post_econ_testimonial', 'econur_save_testimonial_meta' );
/**
 * Persist testimonial meta.
 *
 * @param int $post_id Testimonial ID.
 */
function econur_save_testimonial_meta( $post_id ) {
	if ( ! isset( $_POST['econur_testimonial_meta_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['econur_testimonial_meta_nonce'] ), 'econur_testimonial_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$rating = isset( $_POST['econur_rating'] ) ? absint( $_POST['econur_rating'] ) : 5;
	$rating = min( 5, max( 1, $rating ) );
	update_post_meta( $post_id, '_econur_rating', $rating );

	$location = isset( $_POST['econur_location'] ) ? sanitize_text_field( wp_unslash( $_POST['econur_location'] ) ) : '';
	update_post_meta( $post_id, '_econur_location', $location );
}

/* -------------------------------------------------------------------------
 * One-time seeding
 *
 * Folded in from the old inc/seed.php, which also seeded four product
 * categories — "Face Care", "Baby Care", "Hair Care", "Daily Care". Those never
 * matched the real catalog (which uses face / body / baby from the product CSV),
 * so they only ever added empty duplicates to the homepage filter. Category
 * seeding is therefore dropped: WooCommerce's CSV import creates the real terms.
 *
 * Seeding testimonials still earns its place — a fresh install renders the
 * reviews section empty otherwise — so it lives here, next to the post type it
 * creates, rather than in a separate file.
 * ---------------------------------------------------------------------- */

add_action( 'after_switch_theme', 'econur_seed_testimonials' );
/**
 * Create the three brief testimonials (spec §4.1.3) on first activation.
 *
 * Guarded twice: it never runs if ANY testimonial already exists, so it cannot
 * duplicate content or overwrite copy the client has edited.
 *
 * PLACEHOLDER: this copy comes from the brief and is not a verified customer
 * review. Replace or delete before launch.
 */
function econur_seed_testimonials() {
	if ( ! post_type_exists( 'econ_testimonial' ) ) {
		return;
	}

	$existing = get_posts(
		array(
			'post_type'        => 'econ_testimonial',
			'numberposts'      => 1,
			'fields'           => 'ids',
			'post_status'      => 'any',
			'suppress_filters' => false,
		)
	);
	if ( ! empty( $existing ) ) {
		return;
	}

	$seed = array(
		array(
			'name'     => 'Nadia Rahman',
			'location' => 'Dhaka',
			'rating'   => 5,
			'quote'    => 'Softest my skin has felt in years — this bar is a permanent fixture in my shower now. I won\'t go back to commercial soap.',
		),
		array(
			'name'     => 'Tanvir Hossain',
			'location' => 'Chittagong',
			'rating'   => 5,
			'quote'    => 'The Active Defense Bar cleared my nose congestion and blackheads in two weeks. Genuinely impressed.',
		),
		array(
			'name'     => 'Priya Chowdhury',
			'location' => 'Sylhet',
			'rating'   => 5,
			'quote'    => 'Ordered a full set as a gift — the eco packaging and the scents are beautiful. Everyone loved them.',
		),
	);

	foreach ( $seed as $item ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'econ_testimonial',
				'post_status'  => 'publish',
				'post_title'   => $item['name'],
				'post_content' => $item['quote'],
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_econur_rating', (int) $item['rating'] );
			update_post_meta( $post_id, '_econur_location', $item['location'] );
		}
	}
}
