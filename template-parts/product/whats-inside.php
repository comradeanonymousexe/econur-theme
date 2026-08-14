<?php
/**
 * Product section 3 — "What's inside" ingredient tag list. (spec §4.2.5)
 *
 * NOTE: the spec suggests linking each tag to a sitewide ingredients reference
 * "where practical". There is no dedicated ingredient archive in a 3-template
 * build, so tags render as static chips; the homepage Ingredients section is
 * the reference. Tags stay editable via the product meta box.
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

$econ_ingredients = econur_lines( econur_product_meta( $econ_product->get_id(), 'ingredients' ) );
if ( empty( $econ_ingredients ) ) {
	return;
}
?>
<section class="econ-product-section">
	<h2 class="econ-product-h2"><?php esc_html_e( "What's inside", 'econur' ); ?></h2>
	<ul class="econ-tags">
		<?php foreach ( $econ_ingredients as $econ_ingredient ) : ?>
			<li class="econ-tag econ-tag--filled"><?php echo esc_html( $econ_ingredient ); ?></li>
		<?php endforeach; ?>
	</ul>
</section>
