<?php
/**
 * Fallback template.
 *
 * The real surface area is exactly three templates (spec §1): front-page.php,
 * the WooCommerce single-product override, and page-lab-report.php. This file
 * only exists to satisfy the WordPress template hierarchy.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="econ-container econ-section">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			the_title( '<h1>', '</h1>' );
			the_content();
		}
	} else {
		echo '<h1>' . esc_html__( 'Nothing here', 'econur' ) . '</h1>';
		echo '<p><a class="econ-btn econ-btn--primary" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Back to shop', 'econur' ) . '</a></p>';
	}
	?>
</section>
<?php
get_footer();
