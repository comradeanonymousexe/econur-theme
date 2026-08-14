<?php
/**
 * Reusable product card — image, name, short blurb (hero ingredients + target
 * skin type), inline size selector, and Order + Add-to-Cart. Used by the
 * homepage grid and the related-products grid. (client card spec)
 *
 * Expects $args['data'] = econur_product_card_data( $product ).
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c = isset( $args['data'] ) ? $args['data'] : null;
if ( ! $c ) {
	return;
}

$econ_init_var    = ! empty( $c['variations'] ) ? $c['variations'][0] : null;
$econ_init_var_id = $econ_init_var ? $econ_init_var['id'] : 0;

// "hero ingredients + target skin type" blurb line.
$econ_hero_ings = array_slice( $c['ingredients'], 0, 3 );
$econ_skin      = ! empty( $c['best_for'] ) ? array_slice( $c['best_for'], 0, 2 ) : array_slice( $c['concern_names'], 0, 2 );
?>
<article class="econ-card" data-econ-card
	data-cats="<?php echo esc_attr( implode( ' ', $c['cats'] ) ); ?>"
	data-concerns="<?php echo esc_attr( implode( ' ', $c['concerns'] ) ); ?>"
	data-search="<?php echo esc_attr( $c['search'] ); ?>">

	<a class="econ-card__media" href="<?php echo esc_url( $c['permalink'] ); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( $c['image_id'] ) {
			echo wp_get_attachment_image(
				$c['image_id'],
				'econur-card',
				false,
				array( 'class' => 'econ-card__img', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => esc_attr( $c['name'] ) )
			);
		} else {
			echo '<img class="econ-card__img" src="' . esc_url( wc_placeholder_img_src( 'econur-card' ) ) . '" alt="" loading="lazy">';
		}
		?>
	</a>

	<div class="econ-card__body" data-econ-buybox>
		<?php if ( ! empty( $c['cat_names'] ) ) : ?>
			<span class="econ-card__cat"><?php echo esc_html( $c['cat_names'][0] ); ?></span>
		<?php endif; ?>

		<h3 class="econ-card__title"><a href="<?php echo esc_url( $c['permalink'] ); ?>"><?php echo esc_html( $c['name'] ); ?></a></h3>

		<?php if ( $c['positioning'] ) : ?>
			<p class="econ-card__desc"><?php echo esc_html( $c['positioning'] ); ?></p>
		<?php endif; ?>

		<?php if ( $econ_hero_ings || $econ_skin ) : ?>
			<p class="econ-card__meta">
				<?php if ( $econ_hero_ings ) : ?>
					<b><?php esc_html_e( 'With', 'econur' ); ?></b> <?php echo esc_html( implode( ', ', $econ_hero_ings ) ); ?>
				<?php endif; ?>
				<?php
				if ( $econ_hero_ings && $econ_skin ) {
					echo ' &middot; ';
				}
				?>
				<?php if ( $econ_skin ) : ?>
					<b><?php esc_html_e( 'For', 'econur' ); ?></b> <?php echo esc_html( implode( ', ', $econ_skin ) ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<div class="econ-card__buy">
			<div class="econ-card__price" data-econ-price>
				<?php echo $econ_init_var ? esc_html( $econ_init_var['price'] ) : wp_kses_post( $c['price_html'] ); ?>
			</div>

			<?php if ( ! empty( $c['variations'] ) ) : ?>
				<div class="econ-sizes" role="group" aria-label="<?php esc_attr_e( 'Choose size', 'econur' ); ?>" data-econ-sizes>
					<?php foreach ( $c['variations'] as $econ_i => $econ_v ) : ?>
						<button type="button" class="econ-size<?php echo 0 === $econ_i ? ' is-active' : ''; ?>"
							data-variation-id="<?php echo esc_attr( $econ_v['id'] ); ?>"
							data-price="<?php echo esc_attr( $econ_v['price'] ); ?>"
							aria-pressed="<?php echo 0 === $econ_i ? 'true' : 'false'; ?>">
							<?php echo esc_html( $econ_v['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="econ-card__actions">
				<?php if ( $c['purchasable'] ) : ?>
					<button type="button" class="econ-btn econ-btn--order" data-econ-order
						data-product-id="<?php echo esc_attr( $c['id'] ); ?>"
						data-variation-id="<?php echo esc_attr( $econ_init_var_id ); ?>">
						<?php econur_icon( 'bag' ); ?><?php esc_html_e( 'Order Now', 'econur' ); ?>
					</button>
					<button type="button" class="econ-btn econ-btn--ghost econ-add" data-econ-add
						data-product-id="<?php echo esc_attr( $c['id'] ); ?>"
						data-variation-id="<?php echo esc_attr( $econ_init_var_id ); ?>">
						<?php esc_html_e( 'Add to Cart', 'econur' ); ?>
					</button>
				<?php else : ?>
					<a class="econ-btn econ-btn--ghost" href="<?php echo esc_url( $c['permalink'] ); ?>"><?php esc_html_e( 'View details', 'econur' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</article>
