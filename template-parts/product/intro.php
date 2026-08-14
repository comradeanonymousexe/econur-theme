<?php
/**
 * Product section 7 — Long-form description.
 * Reuses WooCommerce's own product Description (post content), so Bangla copy
 * and rich formatting are preserved (spec §4.2.2). Hidden if empty.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$econ_product = isset( $args['product'] ) ? $args['product'] : null;
if ( ! $econ_product ) {
	return;
}

$econ_desc = $econ_product->get_description();
if ( '' === trim( (string) $econ_desc ) ) {
	return;
}
?>
<section class="econ-product-section">
	<h2 class="econ-product-h2"><?php esc_html_e( 'About this bar', 'econur' ); ?></h2>
	<div class="econ-prose">
		<?php echo apply_filters( 'the_content', $econ_desc ); // phpcs:ignore WordPress.Security.EscapeOutput -- the_content filter handles sanitisation. ?>
	</div>
</section>
