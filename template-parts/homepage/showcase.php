<?php
/**
 * Homepage section 1 — Showcase carousel (auto-advancing).
 *
 * Slides = optional promo/offer slides (Customizer) + featured products
 * (WooCommerce native "Featured" star, newest-products fallback). The
 * highest-design-effort surface (spec §4.1.1). Every product slide carries the
 * Order + Add-to-Cart buy box; auto-advance is handled by carousel.js.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$econ_offers   = function_exists( 'econur_offer_slides' ) ? econur_offer_slides() : array();
$econ_featured = function_exists( 'econur_featured_products' ) ? econur_featured_products( 6 ) : array();

if ( empty( $econ_offers ) && empty( $econ_featured ) ) {
	// Nothing to show yet — brand hero that drives to the grid.
	?>
	<section class="econ-showcase econ-showcase--empty">
		<div class="econ-container econ-showcase__empty-inner">
			<span class="econ-eyebrow"><?php esc_html_e( 'Handcrafted in Bangladesh', 'econur' ); ?></span>
			<h1 class="econ-showcase__title"><?php esc_html_e( '100% natural soap, verified pure.', 'econur' ); ?></h1>
			<p class="econ-showcase__lead"><?php esc_html_e( 'Cold-pressed oils and plant botanicals — lab-verified TFM, nil free alkali. Kind to skin, honest by nature.', 'econur' ); ?></p>
			<a class="econ-btn econ-btn--order econ-btn--lg" href="#shop"><?php esc_html_e( 'Shop all soaps', 'econur' ); ?></a>
		</div>
	</section>
	<?php
	return;
}

$econ_lcp_done = false; // First image gets fetchpriority high (LCP candidate).
?>
<section class="econ-showcase">
	<div class="econ-container">
		<div class="econ-carousel" data-econ-carousel data-interval="6000">
			<div class="econ-carousel__viewport">
				<div class="econ-carousel__track" data-econ-carousel-track>

					<?php
					foreach ( $econ_offers as $econ_offer ) :
						$econ_priority = ! $econ_lcp_done;
						$econ_lcp_done = true;
						?>
						<div class="econ-carousel__slide">
							<div class="econ-offer">
								<?php if ( $econ_offer['image'] ) : ?>
									<img class="econ-offer__bg" src="<?php echo esc_url( $econ_offer['image'] ); ?>" alt=""
										<?php echo $econ_priority ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
								<?php endif; ?>
								<div class="econ-offer__inner">
									<span class="econ-eyebrow econ-offer__eyebrow"><?php esc_html_e( 'Special offer', 'econur' ); ?></span>
									<h2 class="econ-offer__title"><?php echo esc_html( $econ_offer['headline'] ); ?></h2>
									<?php if ( $econ_offer['subtext'] ) : ?>
										<p class="econ-offer__sub"><?php echo esc_html( $econ_offer['subtext'] ); ?></p>
									<?php endif; ?>
									<?php if ( $econ_offer['btn'] && $econ_offer['url'] ) : ?>
										<a class="econ-btn econ-btn--light econ-btn--lg" href="<?php echo esc_url( $econ_offer['url'] ); ?>"><?php echo esc_html( $econ_offer['btn'] ); ?></a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>

					<?php
					/*
					 * WP 6.7+ prepends `sizes="auto"` to every lazy-loaded image. That is
					 * correct for images in normal document flow, but wrong inside a
					 * carousel: slides 2+ are translated out of view, so when the browser
					 * resolves `auto` it has no usable layout width and falls back to a
					 * tiny srcset candidate — permanently, since it never re-fetches once
					 * the slide scrolls in. The eager first slide never gets `auto`, which
					 * is why only slide 1 stayed sharp. Disable the injection for this loop
					 * and supply an explicit `sizes` matching the real media column.
					 */
					add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

					foreach ( $econ_featured as $econ_product ) :
						$c                = econur_product_card_data( $econ_product );
						$econ_init_var    = ! empty( $c['variations'] ) ? $c['variations'][0] : null;
						$econ_init_var_id = $econ_init_var ? $econ_init_var['id'] : 0;
						$econ_priority    = ! $econ_lcp_done;
						$econ_lcp_done    = true;
						?>
						<div class="econ-carousel__slide">
							<div class="econ-showcase__grid">
								<div class="econ-showcase__media">
									<?php
									if ( $c['image_id'] ) {
										echo wp_get_attachment_image(
											$c['image_id'],
											'econur-hero',
											false,
											array(
												'class'         => 'econ-showcase__img',
												'alt'           => esc_attr( $c['name'] ),
												'decoding'      => 'async',
												'fetchpriority' => $econ_priority ? 'high' : 'low',
												'loading'       => $econ_priority ? 'eager' : 'lazy',
												// Media column is ~half of the 1240px container from 880px up, full-bleed below.
												'sizes'         => '(min-width: 880px) 620px, 100vw',
											)
										);
									} else {
										echo '<img class="econ-showcase__img" src="' . esc_url( $c['image'] ) . '" alt="' . esc_attr( $c['name'] ) . '">';
									}
									?>
									<span class="econ-showcase__badge"><?php esc_html_e( 'Featured', 'econur' ); ?></span>
								</div>
								<div class="econ-showcase__info" data-econ-buybox>
									<?php if ( ! empty( $c['cat_names'] ) ) : ?>
										<span class="econ-eyebrow"><?php echo esc_html( $c['cat_names'][0] ); ?></span>
									<?php endif; ?>
									<h2 class="econ-showcase__title"><?php echo econur_notranslate( $c['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?></h2>
									<?php if ( $c['positioning'] ) : ?>
										<p class="econ-showcase__lead"><?php echo esc_html( $c['positioning'] ); ?></p>
									<?php endif; ?>
									<div class="econ-showcase__price" data-econ-price>
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
									<div class="econ-showcase__actions">
										<?php if ( $c['purchasable'] ) : ?>
											<button class="econ-btn econ-btn--order econ-btn--lg" data-econ-order
												data-product-id="<?php echo esc_attr( $c['id'] ); ?>" data-variation-id="<?php echo esc_attr( $econ_init_var_id ); ?>">
												<?php econur_icon( 'bag' ); ?><?php esc_html_e( 'Order Now', 'econur' ); ?>
											</button>
											<button class="econ-btn econ-btn--ghost econ-btn--lg econ-add" data-econ-add
												data-product-id="<?php echo esc_attr( $c['id'] ); ?>" data-variation-id="<?php echo esc_attr( $econ_init_var_id ); ?>">
												<?php esc_html_e( 'Add to Cart', 'econur' ); ?>
											</button>
										<?php else : ?>
											<a class="econ-btn econ-btn--ghost econ-btn--lg" href="<?php echo esc_url( $c['permalink'] ); ?>"><?php esc_html_e( 'View details', 'econur' ); ?></a>
										<?php endif; ?>
									</div>
									<p class="econ-showcase__meta"><?php esc_html_e( 'Cash on delivery · Nationwide across Bangladesh', 'econur' ); ?></p>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
					<?php remove_filter( 'wp_img_tag_add_auto_sizes', '__return_false' ); ?>

				</div>
			</div>

			<button class="econ-carousel__btn econ-carousel__btn--prev" type="button" data-econ-carousel-prev aria-label="<?php esc_attr_e( 'Previous slide', 'econur' ); ?>">&#8249;</button>
			<button class="econ-carousel__btn econ-carousel__btn--next" type="button" data-econ-carousel-next aria-label="<?php esc_attr_e( 'Next slide', 'econur' ); ?>">&#8250;</button>
			<div class="econ-carousel__dots" data-econ-carousel-dots></div>
		</div>
	</div>
</section>
