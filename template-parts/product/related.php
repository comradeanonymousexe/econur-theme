<?php
/**
 * Product section 9 — Related products by shared skin_concern taxonomy.
 * This shared-concern signal also feeds the CRM value-ladder logic (spec §4.2.9, §6).
 * Hidden if the product has no concerns or nothing else shares them.
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

$econ_id       = $econ_product->get_id();
$econ_concerns = wp_get_post_terms( $econ_id, 'skin_concern', array( 'fields' => 'ids' ) );
if ( is_wp_error( $econ_concerns ) || empty( $econ_concerns ) ) {
	return;
}

$econ_related = new WP_Query(
	array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => 4,
		'post__not_in'        => array( $econ_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => 'rand',
		'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'skin_concern',
				'field'    => 'term_id',
				'terms'    => $econ_concerns,
			),
		),
	)
);

if ( ! $econ_related->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="econ-related econ-section">
	<div class="econ-container">
		<header class="econ-section-head">
			<span class="econ-eyebrow"><?php esc_html_e( 'You may also like', 'econur' ); ?></span>
			<h2 class="econ-section-head__title"><?php esc_html_e( 'Made for the same skin', 'econur' ); ?></h2>
		</header>

		<div class="econ-related__grid">
			<?php
			while ( $econ_related->have_posts() ) :
				$econ_related->the_post();
				$econ_rel = wc_get_product( get_the_ID() );
				if ( ! $econ_rel ) {
					continue;
				}
				get_template_part(
					'template-parts/product/card',
					null,
					array( 'data' => econur_product_card_data( $econ_rel ) )
				);
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
