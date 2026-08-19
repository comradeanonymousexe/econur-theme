<?php
/**
 * Product section 8 — Closing CTA band.
 *
 * Order-oriented only. No WhatsApp CTA anywhere on the site except the footer
 * contact link (client rule). Reinforces the promise + repeats the Order path.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$econ_product = isset( $args['product'] ) ? $args['product'] : null;
$econ_data    = isset( $args['data'] ) ? $args['data'] : array();
if ( ! $econ_product ) {
	return;
}

$econ_init_var_id = ! empty( $econ_data['variations'] ) ? $econ_data['variations'][0]['id'] : 0;
?>
<section class="econ-product-cta">
	<div class="econ-container econ-product-cta__inner" data-econ-buybox>
		<h2 class="econ-product-cta__title">
			<?php
			/* translators: %s: product name — wrapped so GTranslate leaves it in English. */
			printf( esc_html__( 'Ready to try %s?', 'econur' ), econur_notranslate( $econ_product->get_name() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format string escaped, name escaped inside the helper.
			?>
		</h2>
		<p class="econ-product-cta__sub"><?php esc_html_e( 'Honest, lab-verified skincare. Cash on delivery, nationwide across Bangladesh.', 'econur' ); ?></p>

		<div class="econ-product-cta__actions">
			<?php if ( $econ_data['purchasable'] ) : ?>
				<button class="econ-btn econ-btn--order econ-btn--lg" data-econ-order
					data-product-id="<?php echo esc_attr( $econ_data['id'] ); ?>"
					data-variation-id="<?php echo esc_attr( $econ_init_var_id ); ?>">
					<?php econur_icon( 'bag' ); ?><?php esc_html_e( 'Order Now', 'econur' ); ?>
				</button>
				<button class="econ-btn econ-btn--light econ-btn--lg econ-add" data-econ-add
					data-product-id="<?php echo esc_attr( $econ_data['id'] ); ?>"
					data-variation-id="<?php echo esc_attr( $econ_init_var_id ); ?>">
					<?php esc_html_e( 'Add to Cart', 'econur' ); ?>
				</button>
			<?php else : ?>
				<a class="econ-btn econ-btn--light econ-btn--lg" href="<?php echo esc_url( home_url( '/#shop' ) ); ?>"><?php esc_html_e( 'Browse all products', 'econur' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
