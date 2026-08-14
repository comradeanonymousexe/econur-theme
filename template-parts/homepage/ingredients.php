<?php
/**
 * Homepage section 4 — Ingredients grid.
 *
 * Hero ingredients from the (editable) Customizer list, one "Name :: benefit"
 * per line, seeded with the brief's nine ingredients (spec §4.1.4).
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$econ_default   = function_exists( 'econur_default_ingredients' ) ? econur_default_ingredients() : '';
$econ_ing_lines = econur_pairs( econur_mod( 'ingredients', $econ_default ) );

if ( empty( $econ_ing_lines ) ) {
	return;
}
?>
<section class="econ-ingredients econ-section" data-econ-reveal>
	<div class="econ-container">
		<header class="econ-section-head">
			<span class="econ-eyebrow"><?php esc_html_e( 'From the earth', 'econur' ); ?></span>
			<h2 class="econ-section-head__title"><?php esc_html_e( 'Hero ingredients', 'econur' ); ?></h2>
			<p class="econ-section-head__sub"><?php esc_html_e( 'Plant-powered actives, chosen for what they do — nothing synthetic.', 'econur' ); ?></p>
		</header>

		<ul class="econ-ing-grid">
			<?php foreach ( $econ_ing_lines as $econ_ing ) : ?>
				<li class="econ-ing">
					<span class="econ-ing__leaf" aria-hidden="true"><?php econur_icon( 'leaf', 'econ-icon' ); ?></span>
					<h3 class="econ-ing__name"><?php echo esc_html( $econ_ing['label'] ); ?></h3>
					<?php if ( $econ_ing['text'] ) : ?>
						<p class="econ-ing__benefit"><?php echo esc_html( $econ_ing['text'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
