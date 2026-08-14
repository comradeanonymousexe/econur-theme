<?php
/**
 * CSV import glue.
 *
 * WooCommerce's importer natively brings in "Meta: _econur_*" columns as post
 * meta (so all our narrative fields import for free). It does NOT natively
 * import a custom taxonomy, so we ship the skin concerns as a temporary meta
 * column and convert it to `skin_concern` terms right after each product is
 * imported. (spec §10.4)
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map the imported `_econur_skin_concern_names` meta to skin_concern terms.
 *
 * @param WC_Product $object Imported product.
 */
add_action( 'woocommerce_product_import_inserted_product_object', 'econur_import_map_skin_concerns', 10, 1 );
function econur_import_map_skin_concerns( $object ) {
	if ( ! $object || ! is_callable( array( $object, 'get_id' ) ) ) {
		return;
	}
	$id  = $object->get_id();
	$raw = get_post_meta( $id, '_econur_skin_concern_names', true );
	if ( ! $raw ) {
		return;
	}

	$names = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
	if ( $names ) {
		// Non-hierarchical taxonomy: passing names creates any missing terms.
		wp_set_object_terms( $id, $names, 'skin_concern' );
	}
	delete_post_meta( $id, '_econur_skin_concern_names' );
}
