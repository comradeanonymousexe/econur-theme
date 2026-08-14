<?php
/**
 * Site footer: brand, nav, socials, trust copy, Salesfind credit, dynamic year.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main><!-- #main -->

<footer class="econ-footer" role="contentinfo">
	<div class="econ-container econ-footer__inner">

		<div class="econ-footer__brand">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				printf( '<span class="econ-logo econ-logo--light">%s</span>', esc_html( get_bloginfo( 'name' ) ) );
			}
			?>
			<p class="econ-footer__tagline">
				<?php esc_html_e( 'Handcrafted, 100% natural, chemical-free soap — made in Bangladesh from cold-pressed oils and plant botanicals.', 'econur' ); ?>
			</p>
		</div>

		<nav class="econ-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'econur' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'econ-footer__list',
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>

		<div class="econ-footer__connect">
			<h3 class="econ-footer__heading"><?php esc_html_e( 'Connect', 'econur' ); ?></h3>
			<?php
			$econ_fb = econur_mod( 'facebook_url', 'https://facebook.com/econurskincare' );
			$econ_ig = econur_mod( 'instagram_url', 'https://instagram.com/econur.skincare' );
			$econ_wa = 'https://wa.me/' . apply_filters( 'econur_whatsapp_support_number', '8801410753555' );
			?>
			<ul class="econ-social">
				<?php if ( $econ_fb ) : ?><li><a href="<?php echo esc_url( $econ_fb ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><?php econur_icon( 'facebook' ); ?></a></li><?php endif; ?>
				<?php if ( $econ_ig ) : ?><li><a href="<?php echo esc_url( $econ_ig ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><?php econur_icon( 'instagram' ); ?></a></li><?php endif; ?>
				<li><a href="<?php echo esc_url( $econ_wa ); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><?php econur_icon( 'wa' ); ?></a></li>
			</ul>
			<p class="econ-footer__trust">
				<?php esc_html_e( 'Nationwide delivery across Bangladesh. We reply within 12 hours.', 'econur' ); ?>
			</p>
		</div>
	</div>

	<div class="econ-footer__bar">
		<div class="econ-container econ-footer__bar-inner">
			<p>
				&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?>
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'econur' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Powered by', 'econur' ); ?>
				<a href="https://salesfind.org" target="_blank" rel="noopener noreferrer">Salesfind Marketing &amp; IT</a>
			</p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
