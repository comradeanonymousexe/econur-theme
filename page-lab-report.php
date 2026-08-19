<?php
/**
 * Template Name: Lab Report
 *
 * TEMPLATE 3 of 3 — Lab Report (spec §4.3). Modeled on the dossier's certs.html:
 * eyebrow badge, H1, intro, three editable stat cards, two explainers, the
 * Econur-vs-mass-produced comparison table, the "Econur Difference" close, and a
 * downloads block. Stat numbers + download URLs are editable (meta box, Phase 3);
 * the explanatory copy lives in this template.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$econ_pid = get_the_ID();

	// Editable fields (fall back to seeded defaults via econur_lab_field()).
	$econ_bcsir = econur_lab_field( $econ_pid, 'bcsir_url' );
	$econ_buet  = econur_lab_field( $econ_pid, 'buet_url' );
	$econ_stats = array(
		array( econur_lab_field( $econ_pid, 'stat1_value' ), econur_lab_field( $econ_pid, 'stat1_label' ) ),
		array( econur_lab_field( $econ_pid, 'stat2_value' ), econur_lab_field( $econ_pid, 'stat2_label' ) ),
		array( econur_lab_field( $econ_pid, 'stat3_value' ), econur_lab_field( $econ_pid, 'stat3_label' ) ),
	);
	?>
	<article class="econ-lab">

		<section class="econ-lab-hero">
			<div class="econ-container econ-lab-hero__inner">
				<span class="econ-lab-badge"><?php econur_icon( 'shield', 'econ-icon' ); ?><?php esc_html_e( 'Lab Verified', 'econur' ); ?></span>
				<h1 class="econ-lab-hero__title"><?php echo econur_notranslate_terms( esc_html__( 'BUET & BCSIR Certified: Our Lab-Verified Promise', 'econur' ), econur_untranslatable_terms() ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before term wrapping. */ ?></h1>
				<p class="econ-lab-hero__lead"><?php esc_html_e( 'We would rather show you the numbers than ask you to trust us. Every Econur bar is independently tested — with Total Fatty Matter measured at 75%–86% and nil free alkali. Pure, gentle, and exactly what the label says.', 'econur' ); ?></p>
			</div>
		</section>

		<section class="econ-container econ-lab-section">
			<div class="econ-lab-stats">
				<?php foreach ( $econ_stats as $econ_stat ) : ?>
					<div class="econ-lab-stat">
						<span class="econ-lab-stat__value"><?php echo esc_html( $econ_stat[0] ); ?></span>
						<span class="econ-lab-stat__label"><?php echo esc_html( $econ_stat[1] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="econ-container econ-lab-section">
			<div class="econ-lab-explainers">
				<div class="econ-lab-explainer">
					<h2 class="econ-lab-explainer__title"><?php echo econur_notranslate_terms( esc_html__( 'Unmatched nourishment (75%–86% TFM)', 'econur' ), econur_untranslatable_terms() ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before term wrapping. */ ?></h2>
					<p><?php esc_html_e( 'The industry standard for a quality face bar is a minimum of 75% Total Fatty Matter. Econur reaches up to 86% — a creamy, decadent lather that cleanses thoroughly without stripping your skin of its natural moisture.', 'econur' ); ?></p>
				</div>
				<div class="econ-lab-explainer">
					<h2 class="econ-lab-explainer__title"><?php esc_html_e( 'Pure & gentle (free alkali: nil)', 'econur' ); ?></h2>
					<p><?php esc_html_e( 'Free alkali is leftover, unreacted lye — the harsh residue that makes many soaps sting or dry out sensitive skin. Our lab results show nil, which means Econur is safe and gentle enough for the whole family.', 'econur' ); ?></p>
				</div>
			</div>
		</section>

		<section class="econ-container econ-lab-section">
			<h2 class="econ-lab-h2"><?php esc_html_e( 'Natural artisanal bar vs. chemical "beauty soaps"', 'econur' ); ?></h2>
			<div class="econ-compare-wrap">
				<table class="econ-compare">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Feature', 'econur' ); ?></th>
							<th><?php echo econur_notranslate_terms( esc_html__( 'Econur cold-process bars', 'econur' ), econur_untranslatable_terms() ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before term wrapping. */ ?></th>
							<th><?php esc_html_e( 'Mass-produced beauty bars', 'econur' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<th scope="row"><?php echo econur_notranslate_terms( esc_html__( 'TFM content', 'econur' ), econur_untranslatable_terms() ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before term wrapping. */ ?></th>
							<td class="is-good"><?php esc_html_e( 'High (75%–86%)', 'econur' ); ?></td>
							<td><?php esc_html_e( 'Low / often not disclosed', 'econur' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Key ingredients', 'econur' ); ?></th>
							<td class="is-good"><?php esc_html_e( 'Natural plant oils', 'econur' ); ?></td>
							<td><?php esc_html_e( 'Synthetic surfactants / detergents', 'econur' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Glycerin', 'econur' ); ?></th>
							<td class="is-good"><?php esc_html_e( 'Retained (natural moisturizer)', 'econur' ); ?></td>
							<td><?php esc_html_e( 'Often removed for other products', 'econur' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Skin feel', 'econur' ); ?></th>
							<td class="is-good"><?php esc_html_e( 'Soft, hydrated, balanced', 'econur' ); ?></td>
							<td><?php esc_html_e( 'Tight, dry, "squeaky"', 'econur' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</section>

		<section class="econ-lab-diff">
			<div class="econ-container econ-lab-diff__inner">
				<h2 class="econ-lab-diff__title"><?php echo econur_notranslate_terms( esc_html__( 'The Econur difference', 'econur' ), econur_untranslatable_terms() ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before term wrapping. */ ?></h2>
				<p><?php esc_html_e( 'High TFM, nil free alkali, retained glycerin — three lab-verified reasons Econur feels different on your skin. Not a marketing claim, but a measurable one.', 'econur' ); ?></p>
			</div>
		</section>

		<?php if ( $econ_bcsir || $econ_buet ) : ?>
			<section class="econ-container econ-lab-section">
				<h2 class="econ-lab-h2"><?php esc_html_e( 'Download the reports', 'econur' ); ?></h2>
				<div class="econ-lab-downloads">
					<?php if ( $econ_bcsir ) : ?>
						<a class="econ-lab-download" href="<?php echo esc_url( $econ_bcsir ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="econ-lab-download__icon"><?php econur_icon( 'download', 'econ-icon' ); ?></span>
							<span class="econ-lab-download__text">
								<span class="econ-lab-download__title"><?php echo econur_notranslate_terms( esc_html__( 'BCSIR Lab Report', 'econur' ), econur_untranslatable_terms() ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before term wrapping. */ ?></span>
								<span class="econ-lab-download__meta"><?php esc_html_e( 'PDF document', 'econur' ); ?></span>
							</span>
						</a>
					<?php endif; ?>
					<?php if ( $econ_buet ) : ?>
						<a class="econ-lab-download" href="<?php echo esc_url( $econ_buet ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="econ-lab-download__icon"><?php econur_icon( 'download', 'econ-icon' ); ?></span>
							<span class="econ-lab-download__text">
								<span class="econ-lab-download__title"><?php echo econur_notranslate_terms( esc_html__( 'BUET Lab Certificate', 'econur' ), econur_untranslatable_terms() ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before term wrapping. */ ?></span>
								<span class="econ-lab-download__meta"><?php esc_html_e( 'Image / certificate', 'econur' ); ?></span>
							</span>
						</a>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

	</article>
	<?php
endwhile;

get_footer();
