<?php
/**
 * Product section 1 — Hero: lightweight gallery, name, positioning, price,
 * size selector, quantity, Add to Cart, stock status (spec §4.2.1).
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$econ_product = isset( $args['product'] ) ? $args['product'] : null;
$econ_data    = isset( $args['data'] ) ? $args['data'] : array();
if ( ! $econ_product ) {
	return;
}

$econ_main_id     = $econ_product->get_image_id();
$econ_gallery_ids = $econ_product->get_gallery_image_ids();
$econ_init_var    = ! empty( $econ_data['variations'] ) ? $econ_data['variations'][0] : null;
$econ_init_var_id = $econ_init_var ? $econ_init_var['id'] : 0;
?>
<section class="econ-product-hero">
	<div class="econ-container econ-product-hero__grid">

		<div class="econ-gallery">
			<div class="econ-gallery__main" data-econ-gallery-main>
				<?php
				if ( $econ_main_id ) {
					echo wp_get_attachment_image(
						$econ_main_id,
						'econur-hero',
						false,
						array(
							'class'         => 'econ-gallery__img',
							'alt'           => esc_attr( $econ_product->get_name() ),
							'fetchpriority' => 'high',
							'decoding'      => 'async',
						)
					);
				} else {
					echo '<img class="econ-gallery__img" src="' . esc_url( wc_placeholder_img_src( 'econur-hero' ) ) . '" alt="' . esc_attr( $econ_product->get_name() ) . '">';
				}
				?>
			</div>

			<?php
			if ( $econ_main_id && $econ_gallery_ids ) :
				$econ_thumbs = array_merge( array( $econ_main_id ), $econ_gallery_ids );
				?>
				<div class="econ-gallery__thumbs">
					<?php
					foreach ( $econ_thumbs as $econ_i => $econ_tid ) :
						$econ_full   = wp_get_attachment_image_url( $econ_tid, 'econur-hero' );
						$econ_srcset = wp_get_attachment_image_srcset( $econ_tid, 'econur-hero' );
						?>
						<button type="button" class="econ-gallery__thumb<?php echo 0 === $econ_i ? ' is-active' : ''; ?>"
							data-econ-thumb
							data-full="<?php echo esc_url( $econ_full ); ?>"
							data-srcset="<?php echo esc_attr( $econ_srcset ); ?>"
							aria-label="<?php esc_attr_e( 'View image', 'econur' ); ?>">
							<?php echo wp_get_attachment_image( $econ_tid, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="econ-product-hero__info" data-econ-buybox>
			<?php if ( ! empty( $econ_data['cat_names'] ) ) : ?>
				<span class="econ-eyebrow"><?php echo esc_html( $econ_data['cat_names'][0] ); ?></span>
			<?php endif; ?>

			<h1 class="econ-product-hero__title"><?php the_title(); ?></h1>

			<?php if ( ! empty( $econ_data['positioning'] ) ) : ?>
				<p class="econ-product-hero__lead"><?php echo esc_html( $econ_data['positioning'] ); ?></p>
			<?php endif; ?>

			<div class="econ-product-hero__price" data-econ-price>
				<?php echo $econ_init_var ? esc_html( $econ_init_var['price'] ) : wp_kses_post( $econ_product->get_price_html() ); ?>
			</div>

			<p class="econ-stock <?php echo $econ_product->is_in_stock() ? 'is-in' : 'is-out'; ?>">
				<?php echo $econ_product->is_in_stock() ? esc_html__( 'In stock', 'econur' ) : esc_html__( 'Out of stock', 'econur' ); ?>
			</p>

			<?php if ( ! empty( $econ_data['variations'] ) ) : ?>
				<div class="econ-field">
					<span class="econ-field__label"><?php esc_html_e( 'Size', 'econur' ); ?></span>
					<div class="econ-sizes" role="group" aria-label="<?php esc_attr_e( 'Choose size', 'econur' ); ?>" data-econ-sizes>
						<?php foreach ( $econ_data['variations'] as $econ_i => $econ_v ) : ?>
							<button type="button" class="econ-size<?php echo 0 === $econ_i ? ' is-active' : ''; ?>"
								data-variation-id="<?php echo esc_attr( $econ_v['id'] ); ?>"
								data-price="<?php echo esc_attr( $econ_v['price'] ); ?>"
								aria-pressed="<?php echo 0 === $econ_i ? 'true' : 'false'; ?>">
								<?php echo esc_html( $econ_v['label'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="econ-product-hero__buy">
				<div class="econ-qty">
					<label class="screen-reader-text" for="econ-qty"><?php esc_html_e( 'Quantity', 'econur' ); ?></label>
					<input id="econ-qty" class="econ-qty__input" type="number" inputmode="numeric" min="1" value="1" data-econ-qty>
				</div>
				<?php if ( $econ_data['purchasable'] ) : ?>
					<button class="econ-btn econ-btn--order econ-btn--lg" data-econ-order
						data-product-id="<?php echo esc_attr( $econ_data['id'] ); ?>"
						data-variation-id="<?php echo esc_attr( $econ_init_var_id ); ?>">
						<?php econur_icon( 'bag' ); ?><?php esc_html_e( 'Order Now', 'econur' ); ?>
					</button>
				<?php else : ?>
					<button class="econ-btn econ-btn--order econ-btn--lg" disabled><?php esc_html_e( 'Sold out', 'econur' ); ?></button>
				<?php endif; ?>
			</div>

			<?php if ( $econ_data['purchasable'] ) : ?>
				<button class="econ-btn econ-btn--ghost econ-btn--lg econ-btn--block econ-add" data-econ-add
					data-product-id="<?php echo esc_attr( $econ_data['id'] ); ?>"
					data-variation-id="<?php echo esc_attr( $econ_init_var_id ); ?>">
					<?php esc_html_e( 'Add to Cart', 'econur' ); ?>
				</button>
			<?php endif; ?>

			<p class="econ-product-hero__meta"><?php esc_html_e( 'Cash on delivery · We call within 12 hours to confirm', 'econur' ); ?></p>
		</div>
	</div>
</section>
