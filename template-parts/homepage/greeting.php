<?php
/**
 * Homepage heading — the page's one and only <h1>.
 *
 * The homepage previously had NO h1 at all: the carousel's h1 lives in its
 * empty state, which stops rendering the moment there are featured products, so
 * the live page started at h2. That costs an SEO signal on the most important
 * page and leaves screen-reader users without a top-level landmark.
 *
 * Signed-in customers get a warm, time-aware greeting by name. Everyone else —
 * including every search engine crawler, which is always signed out — gets the
 * keyword-bearing brand statement. So personalisation never costs us the SEO
 * value of the heading.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$econ_user  = is_user_logged_in() ? wp_get_current_user() : null;
$econ_first = '';

if ( $econ_user ) {
	$econ_first = $econ_user->first_name ? $econ_user->first_name : $econ_user->display_name;

	// A synthesised phone-login name reads as a number — better to stay generic.
	if ( '' === trim( $econ_first ) || preg_match( '/^\d+$/', $econ_first ) ) {
		$econ_first = '';
	}
}
?>
<section class="econ-home-head">
	<div class="econ-container">
		<?php if ( $econ_first ) : ?>

			<h1 class="econ-home-head__title econ-home-head__title--greeting">
				<?php
				printf(
					/* translators: 1: time-of-day greeting, 2: customer first name. */
					esc_html__( '%1$s, %2$s', 'econur' ),
					esc_html( econur_time_greeting() ),
					econur_notranslate( $econ_first ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper.
				);
				?>
			</h1>
			<p class="econ-home-head__sub">
				<?php esc_html_e( 'Good to see you again. Here is what is fresh from the workshop.', 'econur' ); ?>
			</p>

		<?php else : ?>

			<h1 class="econ-home-head__title">
				<?php esc_html_e( '100% natural soap, verified pure.', 'econur' ); ?>
			</h1>
			<p class="econ-home-head__sub">
				<?php esc_html_e( 'Cold-pressed oils and plant botanicals, handmade in Bangladesh — and lab-verified, not just claimed.', 'econur' ); ?>
			</p>

		<?php endif; ?>
	</div>
</section>
