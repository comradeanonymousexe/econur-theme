<?php
/**
 * TEMPLATE 1 of 3 — Homepage.
 *
 * The homepage IS the shop front (spec §1, §9). Six sections in exact order
 * (spec §4.1): 1) showcase hero, 2) product grid, 3) reviews, 4) ingredients,
 * 5) CTA band, 6) footer (rendered by get_footer()).
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

get_template_part( 'template-parts/homepage/showcase' );      // 1
get_template_part( 'template-parts/homepage/product-grid' );  // 2
get_template_part( 'template-parts/homepage/reviews' );       // 3
get_template_part( 'template-parts/homepage/ingredients' );   // 4
get_template_part( 'template-parts/homepage/cta' );           // 5

get_footer();                                                 // 6
