<?php
/**
 * Homepage section 5 — CTA band.
 *
 * Final conversion push. Drives to the shop grid / cart — NOT WhatsApp, since
 * checkout is native WooCommerce COD now (spec §4.1.5). WhatsApp stays as the
 * persistent secondary support FAB only.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="econ-cta" data-econ-reveal>
	<div class="econ-container econ-cta__inner">
		<span class="econ-eyebrow econ-cta__eyebrow"><?php esc_html_e( 'Ready when you are', 'econur' ); ?></span>
		<h2 class="econ-cta__title"><?php esc_html_e( 'Give your skin the honest bar.', 'econur' ); ?></h2>
		<p class="econ-cta__sub"><?php esc_html_e( 'Cash on delivery, nationwide. We call within 12 hours to confirm every order.', 'econur' ); ?></p>
		<div class="econ-cta__actions">
			<a class="econ-btn econ-btn--light econ-btn--lg" href="#shop"><?php esc_html_e( 'Shop all soaps', 'econur' ); ?></a>
			<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
				<a class="econ-btn econ-btn--ghost econ-btn--lg econ-cta__ghost" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'View cart', 'econur' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
