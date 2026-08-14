<?php
/**
 * Homepage section 3 — Reviews.
 *
 * Testimonial cards from the (staff-editable) Testimonial CPT, with 5-star
 * display (spec §4.1.3). Section hides itself entirely if there are none.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$econ_reviews = new WP_Query(
	array(
		'post_type'      => 'econ_testimonial',
		'post_status'    => 'publish',
		'posts_per_page' => 9,
		'no_found_rows'  => true,
	)
);

if ( ! $econ_reviews->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="econ-reviews econ-section" data-econ-reveal>
	<div class="econ-container">
		<header class="econ-section-head">
			<span class="econ-eyebrow"><?php esc_html_e( 'Loved by skin', 'econur' ); ?></span>
			<h2 class="econ-section-head__title"><?php esc_html_e( 'What our customers say', 'econur' ); ?></h2>
		</header>

		<div class="econ-reviews__grid">
			<?php
			while ( $econ_reviews->have_posts() ) :
				$econ_reviews->the_post();
				$econ_rating   = (int) get_post_meta( get_the_ID(), '_econur_rating', true );
				$econ_rating   = ( $econ_rating >= 1 && $econ_rating <= 5 ) ? $econ_rating : 5;
				$econ_location = get_post_meta( get_the_ID(), '_econur_location', true );
				?>
				<figure class="econ-review" data-econ-reveal>
					<div class="econ-review__stars" role="img"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %d: star rating out of 5. */ __( '%d out of 5 stars', 'econur' ), $econ_rating ) ); ?>">
						<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
							<span class="econ-star<?php echo $s <= $econ_rating ? ' is-on' : ''; ?>" aria-hidden="true">&#9733;</span>
						<?php endfor; ?>
					</div>
					<blockquote class="econ-review__quote"><?php echo esc_html( get_the_content() ); ?></blockquote>
					<figcaption class="econ-review__by">
						<span class="econ-review__name"><?php the_title(); ?></span>
						<?php if ( $econ_location ) : ?>
							<span class="econ-review__loc"><?php echo esc_html( $econ_location ); ?></span>
						<?php endif; ?>
					</figcaption>
				</figure>
				<?php
			endwhile;
			?>
		</div>
	</div>
</section>
<?php
wp_reset_postdata();
