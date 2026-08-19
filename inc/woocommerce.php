<?php
/**
 * WooCommerce integration & the checkout hard-constraint.
 *
 * Loaded only when WooCommerce is active (see functions.php).
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HARD CONSTRAINT (spec §5): Cash on Delivery is the ONLY payment method.
 *
 * Enforced in code via the available-gateways filter so it cannot be
 * accidentally re-enabled by an admin toggle. Every non-COD gateway is
 * stripped from checkout, leaving COD as the sole, always-visible,
 * pre-selected option.
 *
 * @param array $gateways Available gateways.
 * @return array
 */
add_filter( 'woocommerce_available_payment_gateways', 'econur_cod_only_gateways' );
function econur_cod_only_gateways( $gateways ) {
	if ( is_admin() ) {
		return $gateways; // Leave admin Payments settings untouched.
	}
	return array_filter(
		(array) $gateways,
		static function ( $gateway ) {
			return isset( $gateway->id ) && 'cod' === $gateway->id;
		}
	);
}

/**
 * WooCommerce base CSS strategy (zero-bloat, §8):
 *  - Our fully-custom templates (home, single product, lab) render no Woo
 *    markup, so we drop Woo's three sheets there — they'd be pure weight.
 *  - Cart / checkout / My Account use WooCommerce's own markup, so we KEEP its
 *    base CSS there and layer brand overrides (assets/css/woocommerce.css) on
 *    top, instead of re-implementing all of it.
 *
 * @param array $styles Default Woo stylesheet handles.
 * @return array
 */
add_filter( 'woocommerce_enqueue_styles', 'econur_woocommerce_styles' );
function econur_woocommerce_styles( $styles ) {
	if ( is_front_page() || ( function_exists( 'is_product' ) && is_product() ) || is_page_template( 'page-lab-report.php' ) ) {
		return array();
	}
	return $styles;
}

/**
 * Products per archive page. Archives are not a primary navigable surface here
 * (the homepage IS the shop, spec §9) — kept minimal.
 */
add_filter( 'loop_shop_per_page', 'econur_loop_shop_per_page', 20 );
function econur_loop_shop_per_page() {
	return 12;
}

/**
 * Cart contents count (cache-safe): rendered server-side in the header and
 * refreshed by our lightweight JS.
 *
 * NOTE: WooCommerce cart-fragments scope-down is a §8 performance-phase task;
 * tracked in BUILD_NOTES.md.
 *
 * @return int
 */
function econur_cart_count() {
	if ( function_exists( 'WC' ) && WC()->cart ) {
		return (int) WC()->cart->get_cart_contents_count();
	}
	return 0;
}

/**
 * Keep guest checkout unblocked at the theme layer: login is optional and must
 * never gate purchase (spec §5, §6). We never force registration, and we force
 * the guest-checkout option ON at the front end (the admin settings screen still
 * shows the real stored value, so nothing looks broken in wp-admin).
 */
add_filter( 'woocommerce_checkout_registration_required', '__return_false' );
add_filter(
	'option_woocommerce_enable_guest_checkout',
	static function ( $value ) {
		return is_admin() ? $value : 'yes';
	}
);

/**
 * Expose the header cart count as a WooCommerce cart fragment, so any add-to-cart
 * response can refresh it. Keyed by a CSS selector our JS understands.
 *
 * @param array $fragments Existing fragments.
 * @return array
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'econur_cart_count_fragment' );
function econur_cart_count_fragment( $fragments ) {
	$fragments['span.econ-cart-count'] =
		'<span class="econ-cart-count" data-econ-cart-count>' . esc_html( econur_cart_count() ) . '</span>';
	return $fragments;
}

/**
 * Variation-aware AJAX add-to-cart over WooCommerce's own wc-ajax transport.
 *
 * WooCommerce's built-in wc-ajax=add_to_cart only handles SIMPLE products, so
 * the cards / showcase / product buy boxes (which offer a size/weight variation
 * and a buy-now "Order" action) need this.
 * Endpoint: /?wc-ajax=econur_add_to_cart
 */
add_action( 'wc_ajax_econur_add_to_cart', 'econur_ajax_add_to_cart' );
add_action( 'wc_ajax_nopriv_econur_add_to_cart', 'econur_ajax_add_to_cart' );
function econur_ajax_add_to_cart() {
	check_ajax_referer( 'econur_nonce', 'nonce' );

	$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
	$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
	$quantity     = isset( $_POST['quantity'] ) ? max( 1, absint( wp_unslash( $_POST['quantity'] ) ) ) : 1;

	if ( ! $product_id ) {
		wp_send_json( array( 'success' => false, 'message' => __( 'Invalid product.', 'econur' ) ) );
	}

	// Resolve variation attributes when a variation was chosen.
	$variation_attrs = array();
	if ( $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( $variation && $variation->is_type( 'variation' ) ) {
			$variation_attrs = $variation->get_variation_attributes();
		}
	}

	$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation_attrs );
	$added  = $passed ? WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation_attrs ) : false;

	if ( $added ) {
		wp_send_json(
			array(
				'success'   => true,
				'count'     => WC()->cart->get_cart_contents_count(),
				'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
			)
		);
	}

	$notices = wc_get_notices( 'error' );
	wc_clear_notices();
	wp_send_json(
		array(
			'success' => false,
			'message' => $notices ? wp_strip_all_tags( $notices[0]['notice'] ) : __( 'Could not add to cart.', 'econur' ),
		)
	);
}

/**
 * Lightweight, cache-safe cart-count endpoint. Lets a full-page-cached header
 * refresh its count on load without shipping WooCommerce's cart-fragments JS.
 * Endpoint: /?wc-ajax=econur_cart_count
 */
add_action( 'wc_ajax_econur_cart_count', 'econur_ajax_cart_count' );
add_action( 'wc_ajax_nopriv_econur_cart_count', 'econur_ajax_cart_count' );
function econur_ajax_cart_count() {
	wp_send_json( array( 'count' => econur_cart_count() ) );
}

/**
 * The two or three ingredients that actually distinguish a bar.
 *
 * Sourced from the product's **"Ingredients" attribute**, which staff curate per
 * product (e.g. "Neem, Activated Charcoal, Tea Tree EO").
 *
 * It deliberately does NOT slice `_econur_ingredients`. That meta field is the
 * full INCI-style declaration behind the "What's inside" section, and it opens
 * with the soap base — olive / coconut / castor oil and tallow — which every bar
 * shares. Taking its first three produced the same meaningless line on every
 * card ("With Olive Oil, Coconut Oil, Castor Oil").
 *
 * Falls back to the full list only when no attribute is set, so a product that
 * has not been given hero ingredients still shows something rather than nothing.
 *
 * @param WC_Product $product Product object.
 * @param int        $limit   Maximum ingredients to return.
 * @return string[]
 */
function econur_hero_ingredients( $product, $limit = 3 ) {
	$names = array();

	foreach ( (array) $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}

		// Matches a custom "Ingredients" attribute or a global `pa_ingredients`.
		$key = sanitize_title( $attribute->get_name() );
		if ( 'ingredients' !== $key && 'pa_ingredients' !== $key ) {
			continue;
		}

		if ( $attribute->is_taxonomy() ) {
			$terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
			$names = is_wp_error( $terms ) ? array() : (array) $terms;
		} else {
			$names = array_map( 'trim', (array) $attribute->get_options() );
		}
		break;
	}

	$names = array_values( array_filter( $names, static function ( $n ) {
		return '' !== trim( (string) $n );
	} ) );

	if ( empty( $names ) ) {
		$names = econur_lines( econur_product_meta( $product->get_id(), 'ingredients' ) );
	}

	return array_slice( $names, 0, max( 1, (int) $limit ) );
}

/**
 * Assemble a product's presentation data once, for reuse across the homepage
 * showcase carousel, grid cards, and related products (keeps templates declarative).
 *
 * @param WC_Product $product Product object.
 * @return array
 */
function econur_product_card_data( $product ) {
	$id = $product->get_id();

	$cats      = array();
	$cat_names = array();
	$cat_terms = get_the_terms( $id, 'product_cat' );
	if ( $cat_terms && ! is_wp_error( $cat_terms ) ) {
		foreach ( $cat_terms as $term ) {
			$cats[]      = $term->slug;
			$cat_names[] = $term->name;
		}
	}

	$concerns      = array();
	$concern_names = array();
	$concern_terms = get_the_terms( $id, 'skin_concern' );
	if ( $concern_terms && ! is_wp_error( $concern_terms ) ) {
		foreach ( $concern_terms as $term ) {
			$concerns[]      = $term->slug;
			$concern_names[] = $term->name;
		}
	}

	$benefits    = econur_lines( econur_product_meta( $id, 'benefits' ) );
	$ingredients = econur_lines( econur_product_meta( $id, 'ingredients' ) );
	$best_for    = econur_lines( econur_product_meta( $id, 'best_for' ) );
	$hero_ings   = econur_hero_ingredients( $product, 3 );

	// Available size/weight variations (in stock only).
	$variations = array();
	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_available_variations() as $variation ) {
			$variation_obj = wc_get_product( $variation['variation_id'] );
			if ( ! $variation_obj || ! $variation_obj->is_in_stock() ) {
				continue;
			}
			$label = implode( ' / ', array_filter( array_map( 'wc_clean', array_values( $variation['attributes'] ) ) ) );
			$variations[] = array(
				'id'    => (int) $variation['variation_id'],
				'label' => '' !== $label ? $label : $variation_obj->get_name(),
				'price' => wp_strip_all_tags( wc_price( wc_get_price_to_display( $variation_obj ) ) ),
			);
		}
	}

	return array(
		'id'          => $id,
		'name'        => $product->get_name(),
		'permalink'   => get_permalink( $id ),
		'price_html'  => $product->get_price_html(),
		'image_id'    => $product->get_image_id(),
		'image'       => $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'econur-hero' ) : wc_placeholder_img_src( 'econur-hero' ),
		'cats'          => $cats,
		'cat_names'     => $cat_names,
		'concerns'      => $concerns,
		'concern_names' => $concern_names,
		'positioning'   => econur_product_meta( $id, 'positioning' ),
		'benefits'    => $benefits,
		'ingredients' => $ingredients,
		'hero_ingredients' => $hero_ings,
		'best_for'    => $best_for,
		'is_variable' => $product->is_type( 'variable' ),
		'variations'  => $variations,
		'purchasable' => $product->is_purchasable() && $product->is_in_stock(),
		'search'      => strtolower( trim( $product->get_name() . ' ' . implode( ' ', $ingredients ) . ' ' . implode( ' ', $best_for ) . ' ' . implode( ' ', $cat_names ) . ' ' . implode( ' ', $concern_names ) ) ),
	);
}

/**
 * Featured products for the homepage carousel.
 *
 * Uses WooCommerce's native "Featured" flag (the star toggle on the Products
 * screen) — standard, admin-controlled, no custom config. Falls back to the
 * newest published products so the carousel is never empty.
 *
 * @param int $limit Maximum products.
 * @return WC_Product[]
 */
function econur_featured_products( $limit = 6 ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$featured_ids = function_exists( 'wc_get_featured_product_ids' ) ? wc_get_featured_product_ids() : array();
	if ( ! empty( $featured_ids ) ) {
		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => $limit,
				'include' => $featured_ids,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);
		if ( ! empty( $products ) ) {
			return $products;
		}
	}

	return wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => $limit,
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	);
}
