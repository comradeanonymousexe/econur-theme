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
			<a class="econ-icon-btn" href="<?php echo esc_url( home_url( '/#shop' ) ); ?>" aria-label="<?php esc_attr_e( 'Search soaps', 'econur' ); ?>">
				<?php econur_icon( 'search' ); ?>
			</a>
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
