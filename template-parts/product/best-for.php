<?php
/**
 * Product section 2 — "Best for" skin-type / use-case tag list. (spec §4.2.6)
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

$econ_best_for = econur_lines( econur_product_meta( $econ_product->get_id(), 'best_for' ) );
if ( empty( $econ_best_for ) ) {
	return;
}
?>
<section class="econ-product-section">
	<h2 class="econ-product-h2"><?php esc_html_e( 'Best for', 'econur' ); ?></h2>
	<ul class="econ-tags">
		<?php foreach ( $econ_best_for as $econ_tag ) : ?>
			<li class="econ-tag notranslate" translate="no"><?php echo esc_html( $econ_tag ); ?></li>
		<?php endforeach; ?>
	</ul>
</section>
