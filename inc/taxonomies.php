<?php
/**
 * Custom product taxonomy: skin_concern.
 *
 * DEVIATION NOTE (spec §2 also asked for a custom `product_category` taxonomy):
 * we reuse WooCommerce's built-in `product_cat` for Face / Baby / Hair / Daily
 * Care instead of registering a parallel taxonomy. Per spec §0.3 (follow best
 * practice, document the deviation): a duplicate category taxonomy would fight
 * WooCommerce's own product admin, its CSV importer (`tax:product_cat` column),
 * and Store Analytics — pure bloat with no upside. `skin_concern` has NO
 * WooCommerce equivalent, so it is the single new taxonomy we register.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'econur_register_taxonomies' );
/**
 * Register the skin_concern taxonomy (oily, acne-prone, sensitive…).
 * Non-hierarchical (tag-like), exposed to REST + the WooCommerce CSV importer.
 */
function econur_register_taxonomies() {
	register_taxonomy(
		'skin_concern',
		array( 'product' ),
		array(
			'hierarchical'      => false,
			'labels'            => array(
				'name'                       => __( 'Skin Concerns', 'econur' ),
				'singular_name'              => __( 'Skin Concern', 'econur' ),
				'menu_name'                  => __( 'Skin Concerns', 'econur' ),
				'all_items'                  => __( 'All Skin Concerns', 'econur' ),
				'edit_item'                  => __( 'Edit Skin Concern', 'econur' ),
				'view_item'                  => __( 'View Skin Concern', 'econur' ),
				'update_item'                => __( 'Update Skin Concern', 'econur' ),
				'add_new_item'               => __( 'Add New Skin Concern', 'econur' ),
				'new_item_name'              => __( 'New Skin Concern Name', 'econur' ),
				'search_items'               => __( 'Search Skin Concerns', 'econur' ),
				'popular_items'              => __( 'Popular Skin Concerns', 'econur' ),
				'separate_items_with_commas' => __( 'Separate concerns with commas', 'econur' ),
				'add_or_remove_items'        => __( 'Add or remove concerns', 'econur' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'econur' ),
				'not_found'                  => __( 'No skin concerns found', 'econur' ),
			),
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true, // Block editor + REST + CSV importer support.
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'skin-concern' ),
		)
	);
}
