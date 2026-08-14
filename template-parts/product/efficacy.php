<?php
/**
 * Product section 5 — Ingredient efficacy deep-dive (heading + explanation
 * blocks, "Heading :: text" per line). (spec §4.2.4)
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

$econ_blocks = econur_pairs( econur_product_meta( $econ_product->get_id(), 'efficacy' ) );
if ( empty( $econ_blocks ) ) {
	return;
}
?>
<section class="econ-product-section">
	<h2 class="econ-product-h2"><?php esc_html_e( 'Why it works', 'econur' ); ?></h2>
	<div class="econ-efficacy">
		<?php foreach ( $econ_blocks as $econ_block ) : ?>
			<div class="econ-efficacy__item">
				<h3 class="econ-efficacy__heading"><?php echo esc_html( $econ_block['label'] ); ?></h3>
				<?php if ( $econ_block['text'] ) : ?>
					<p class="econ-efficacy__text"><?php echo esc_html( $econ_block['text'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
