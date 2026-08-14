<?php
/**
 * Profile page helpers — the small functions the My Account dashboard template
 * leans on.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A customer's recent orders for the profile page.
 *
 * @param int $limit How many.
 * @return WC_Order[]
 */
function econur_recent_orders( $limit = 4 ) {
	if ( ! function_exists( 'wc_get_orders' ) || ! is_user_logged_in() ) {
		return array();
	}
	return wc_get_orders(
		array(
			'customer_id' => get_current_user_id(),
			'limit'       => $limit,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'status'      => array_keys( wc_get_order_statuses() ),
		)
	);
}

/**
 * WooCommerce's native "order again" URL — refills the cart from a past order.
 *
 * @param WC_Order $order Order.
 * @return string
 */
function econur_reorder_url( $order ) {
	return wp_nonce_url(
		add_query_arg( 'order_again', $order->get_id(), wc_get_cart_url() ),
		'woocommerce-order_again'
	);
}
