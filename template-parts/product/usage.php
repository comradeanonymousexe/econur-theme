<?php
/**
 * Product section 6 — Usage & storage. (spec §4.2.7)
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

$econ_usage = econur_product_meta( $econ_product->get_id(), 'usage' );
if ( '' === trim( (string) $econ_usage ) ) {
	return;
}
?>
<section class="econ-product-section">
	<h2 class="econ-product-h2"><?php esc_html_e( 'Usage & storage', 'econur' ); ?></h2>
	<div class="econ-prose econ-usage">
		<?php echo wp_kses_post( wpautop( esc_html( $econ_usage ) ) ); ?>
	</div>
</section>
