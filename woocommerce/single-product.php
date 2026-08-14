<?php
/**
 * TEMPLATE 2 of 3 — Single Product.
 *
 * ONE template used by every soap (spec §4.2). Fully overrides WooCommerce's
 * default single-product wrapper so we control the whole layout as our own
 * template parts. Nine sections, each field per-product editable via the
 * "Econur — Product Story" meta box (and Woo's own Description for the intro).
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$econ_product = wc_get_product( get_the_ID() );
	if ( ! $econ_product ) {
		continue;
	}
	$GLOBALS['product'] = $econ_product;

	$econ_args = array(
		'product' => $econ_product,
		'data'    => econur_product_card_data( $econ_product ),
	);
	?>
	<article <?php wc_product_class( 'econ-product', $econ_product ); ?>>
		<div class="econ-container econ-product__notices">
			<?php
			if ( function_exists( 'woocommerce_output_all_notices' ) ) {
				woocommerce_output_all_notices();
			}
			?>
		</div>

		<?php
		get_template_part( 'template-parts/product/hero', null, $econ_args );          // 1 Hero
		get_template_part( 'template-parts/product/best-for', null, $econ_args );      // 2 Best for
		get_template_part( 'template-parts/product/whats-inside', null, $econ_args );  // 3 What's inside
		get_template_part( 'template-parts/product/benefits', null, $econ_args );      // 4 What it does for your skin
		get_template_part( 'template-parts/product/efficacy', null, $econ_args );      // 5 Why it works
		get_template_part( 'template-parts/product/usage', null, $econ_args );         // 6 Usage & storage
		get_template_part( 'template-parts/product/intro', null, $econ_args );         // 7 Description (Woo product description)
		get_template_part( 'template-parts/product/closing-cta', null, $econ_args );   // 8 Closing CTA
		get_template_part( 'template-parts/product/related', null, $econ_args );       // 9 Related
		?>
	</article>
	<?php
endwhile;

get_footer();
