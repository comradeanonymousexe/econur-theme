<?php
/**
 * Product section 4 — "What it does for your skin" (benefit chips). (spec §4.2.3)
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

$econ_benefits = econur_lines( econur_product_meta( $econ_product->get_id(), 'benefits' ) );
if ( empty( $econ_benefits ) ) {
	return;
}
?>
<section class="econ-product-section">
	<h2 class="econ-product-h2"><?php esc_html_e( 'What it does for your skin', 'econur' ); ?></h2>
	<ul class="econ-benefits">
		<?php foreach ( $econ_benefits as $econ_benefit ) : ?>
			<li class="econ-benefit">
				<span class="econ-benefit__icon" aria-hidden="true"><?php econur_icon( 'check', 'econ-icon' ); ?></span>
				<span><?php echo esc_html( $econ_benefit ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
