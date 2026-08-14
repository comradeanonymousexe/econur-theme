<?php
/**
 * Homepage section 2 — Product Grid.
 *
 * Full catalog with client-side filter + live search, no page reloads
 * (spec §4.1.2). Each card is self-contained (image, blurb, size selector,
 * Order + Add-to-Cart), so there is no quick-view modal.
 *
 * Categories reuse WooCommerce `product_cat`; empty canonical categories render
 * as disabled "Soon" chips. Concerns come from the `skin_concern` taxonomy.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_products' ) ) {
	return;
}

$econ_products = wc_get_products(
	array(
		'status'  => 'publish',
		'limit'   => -1,
		'orderby' => 'menu_order title',
		'order'   => 'ASC',
	)
);

$econ_cat_terms     = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
$econ_concern_terms = get_terms( array( 'taxonomy' => 'skin_concern', 'hide_empty' => true ) );
?>
<section class="econ-grid-section econ-section" id="shop">
	<div class="econ-container">

		<header class="econ-grid-head">
			<div class="econ-grid-head__intro">
				<span class="econ-eyebrow"><?php esc_html_e( 'Our Soaps', 'econur' ); ?></span>
				<h2 class="econ-grid-head__title"><?php esc_html_e( 'Find your blend', 'econur' ); ?></h2>
			</div>
			<label class="econ-search">
				<span class="screen-reader-text"><?php esc_html_e( 'Search soaps', 'econur' ); ?></span>
				<?php econur_icon( 'search', 'econ-search__icon' ); ?>
				<input type="search" class="econ-search__input" data-econ-search
					placeholder="<?php esc_attr_e( 'Search soaps, ingredients…', 'econur' ); ?>" autocomplete="off">
			</label>
		</header>

		<div class="econ-filters">
			<?php if ( ! is_wp_error( $econ_cat_terms ) && $econ_cat_terms ) : ?>
				<div class="econ-filters__row" role="group" aria-label="<?php esc_attr_e( 'Filter by category', 'econur' ); ?>" data-econ-filter="cat">
					<button type="button" class="econ-chip is-active" data-value="*" aria-pressed="true"><?php esc_html_e( 'All', 'econur' ); ?></button>
					<?php
					foreach ( $econ_cat_terms as $term ) :
						if ( 'uncategorized' === $term->slug ) {
							continue;
						}
						if ( $term->count > 0 ) :
							?>
							<button type="button" class="econ-chip" data-value="<?php echo esc_attr( $term->slug ); ?>" aria-pressed="false"><?php echo esc_html( $term->name ); ?></button>
							<?php
						else :
							?>
							<span class="econ-chip econ-chip--soon" aria-disabled="true"><?php echo esc_html( $term->name ); ?></span>
							<?php
						endif;
					endforeach;
					?>
				</div>
			<?php endif; ?>

			<?php if ( ! is_wp_error( $econ_concern_terms ) && $econ_concern_terms ) : ?>
				<div class="econ-filters__row econ-filters__row--concern" role="group" aria-label="<?php esc_attr_e( 'Filter by skin concern', 'econur' ); ?>" data-econ-filter="concern">
					<button type="button" class="econ-chip is-active" data-value="*" aria-pressed="true"><?php esc_html_e( 'All concerns', 'econur' ); ?></button>
					<?php foreach ( $econ_concern_terms as $term ) : ?>
						<button type="button" class="econ-chip" data-value="<?php echo esc_attr( $term->slug ); ?>" aria-pressed="false"><?php echo esc_html( $term->name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( empty( $econ_products ) ) : ?>
			<p class="econ-grid-empty is-visible"><?php esc_html_e( 'The catalog is being stocked — please check back soon.', 'econur' ); ?></p>
		<?php else : ?>
			<div class="econ-grid" data-econ-grid>
				<?php
				foreach ( $econ_products as $econ_product ) :
					get_template_part(
						'template-parts/product/card',
						null,
						array( 'data' => econur_product_card_data( $econ_product ) )
					);
				endforeach;
				?>
			</div>
			<p class="econ-grid-empty" data-econ-grid-empty hidden><?php esc_html_e( 'No soaps match your filters.', 'econur' ); ?></p>
			<p class="screen-reader-text" role="status" aria-live="polite" data-econ-grid-status></p>
		<?php endif; ?>
	</div>
</section>
