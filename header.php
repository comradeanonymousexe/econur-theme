<?php
/**
 * Site header.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="econ-skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'econur' ); ?></a>

<header class="econ-header" id="site-header" data-econ-header>
	<div class="econ-header__inner econ-container">

		<button class="econ-header__burger" type="button"
				aria-label="<?php esc_attr_e( 'Toggle menu', 'econur' ); ?>"
				aria-controls="econ-primary-nav" aria-expanded="false"
				data-econ-nav-toggle>
			<span></span><span></span><span></span>
		</button>

		<div class="econ-header__brand">
			<?php
			if ( has_custom_logo() ) {
				// A logo set in Appearance → Customize always wins.
				the_custom_logo();
			} else {
				/*
				 * Theme-bundled brand mark. Intrinsic size is declared so the header
				 * reserves its space before the image decodes (guards CLS), and it is
				 * eager + high priority because it sits above the fold.
				 */
				printf(
					'<a class="econ-logo econ-logo--mark" href="%1$s" rel="home"><img src="%2$s" alt="%3$s" width="858" height="189" decoding="async" fetchpriority="high"></a>',
					esc_url( home_url( '/' ) ),
					esc_url( ECONUR_URI . '/assets/images/logo.png' ),
					esc_attr( get_bloginfo( 'name' ) )
				);
			}
			?>
		</div>

		<nav class="econ-nav" id="econ-primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'econur' ); ?>" data-econ-nav>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'econ-nav__list',
					'fallback_cb'    => 'econur_primary_menu_fallback',
					'depth'          => 2,
				)
			);
			?>
		</nav>

		<div class="econ-header__actions">
			<?php
			/*
			 * Language switcher (GTranslate). Rendered here so it sits with the other
			 * header controls instead of floating over the page. Only output when the
			 * plugin is actually active — otherwise the slot stays empty and nothing
			 * is hidden. The CSS in components.css turns GTranslate's 173px-wide
			 * "flag + language name" control into a 44px icon button.
			 */
			if ( shortcode_exists( 'gtranslate' ) ) {
				/*
				 * The globe is what the visitor sees; GTranslate's own <select> is laid
				 * transparently on top of it (see .econ-lang in components.css). The
				 * select already carries its own aria-label — the "Select language
				 * label" value from the plugin's settings — so it needs nothing here.
				 */
				echo '<div class="econ-lang">';
				econur_icon( 'globe', 'econ-lang__icon' );
				echo do_shortcode( '[gtranslate]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin-generated markup.
				echo '</div>';
			}
			?>
			<?php
			// Account entry point. Purely an entry point — the cart and checkout
			// beside it stay fully open to guests (spec §5, §6).
			if ( function_exists( 'econur_account_link' ) ) :
				$econ_account = econur_account_link();
				?>
				<a class="econ-icon-btn <?php echo is_user_logged_in() ? 'is-signed-in' : ''; ?>"
					href="<?php echo esc_url( $econ_account['url'] ); ?>"
					aria-label="<?php echo esc_attr( $econ_account['label'] ); ?>">
					<?php econur_icon( 'user' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
				<a class="econ-icon-btn econ-cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'View cart', 'econur' ); ?>">
					<?php econur_icon( 'bag' ); ?>
					<span class="econ-cart-count" data-econ-cart-count><?php echo esc_html( econur_cart_count() ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<div class="econ-nav-scrim" data-econ-nav-scrim></div>

<main id="main" class="econ-main">
