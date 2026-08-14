<?php
/**
 * Checkout customization: lean fields, Bangladeshi phone validation, COD notice.
 * Loaded only when WooCommerce is active (see functions.php). (spec §5)
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trim checkout to the essentials: Full name, Mobile number, Full delivery
 * address, City/district. Email optional (phone-first COD). The Inside/Outside
 * Dhaka "delivery zone" is the WooCommerce shipping-method choice (two flat
 * rates in one Bangladesh zone) — no extra field needed. (see BUILD_NOTES §5)
 *
 * @param array $fields Checkout fields.
 * @return array
 */
add_filter( 'woocommerce_checkout_fields', 'econur_checkout_fields' );
function econur_checkout_fields( $fields ) {
	if ( empty( $fields['billing'] ) ) {
		return $fields;
	}
	$b = $fields['billing'];

	// Drop clutter that a nationwide COD soap store doesn't need.
	unset( $b['billing_company'], $b['billing_address_2'], $b['billing_state'], $b['billing_postcode'], $b['billing_last_name'] );

	if ( isset( $b['billing_first_name'] ) ) {
		$b['billing_first_name']['label']    = __( 'Full name', 'econur' );
		$b['billing_first_name']['class']    = array( 'form-row-wide' );
		$b['billing_first_name']['priority'] = 10;
	}
	if ( isset( $b['billing_phone'] ) ) {
		$b['billing_phone']['required']    = true;
		$b['billing_phone']['label']       = __( 'Mobile number', 'econur' );
		$b['billing_phone']['placeholder'] = __( 'e.g. 01712345678', 'econur' );
		$b['billing_phone']['class']       = array( 'form-row-wide' );
		$b['billing_phone']['priority']    = 20;
	}
	if ( isset( $b['billing_address_1'] ) ) {
		$b['billing_address_1']['label']       = __( 'Full delivery address', 'econur' );
		$b['billing_address_1']['placeholder'] = __( 'House / road / area', 'econur' );
		$b['billing_address_1']['class']       = array( 'form-row-wide' );
		$b['billing_address_1']['priority']    = 30;
	}
	if ( isset( $b['billing_city'] ) ) {
		$b['billing_city']['label']    = __( 'City / district', 'econur' );
		$b['billing_city']['class']    = array( 'form-row-wide' );
		$b['billing_city']['priority'] = 40;
	}
	if ( isset( $b['billing_email'] ) ) {
		$b['billing_email']['required'] = false;
		$b['billing_email']['label']    = __( 'Email (optional)', 'econur' );
		$b['billing_email']['priority'] = 50;
	}

	$fields['billing'] = $b;
	return $fields;
}

/**
 * Validate the Bangladeshi mobile number format (spec §5): 01[3-9] + 8 digits.
 *
 * @param array    $data   Posted checkout data.
 * @param WP_Error $errors Error object.
 */
add_action( 'woocommerce_after_checkout_validation', 'econur_validate_bd_phone', 10, 2 );
function econur_validate_bd_phone( $data, $errors ) {
	$phone = isset( $data['billing_phone'] ) ? preg_replace( '/[\s-]/', '', $data['billing_phone'] ) : '';
	if ( '' !== $phone && ! preg_match( '/^01[3-9]\d{8}$/', $phone ) ) {
		$errors->add( 'billing_phone', __( 'Please enter a valid Bangladeshi mobile number, e.g. 01712345678.', 'econur' ) );
	}
}

/**
 * COD trust notice above the payment box, matching the dossier copy. (spec §5)
 */
add_action( 'woocommerce_review_order_before_payment', 'econur_cod_notice' );
function econur_cod_notice() {
	echo '<div class="econ-cod-notice"><strong>' . esc_html__( 'Cash on delivery', 'econur' ) . '</strong> — ' .
		esc_html__( 'pay when your order arrives. We will call within 12 hours to confirm your order and delivery.', 'econur' ) .
		'</div>';
}

/**
 * Default the new-account username/password off (guest-friendly) and keep the
 * order-notes field but relabel it for clarity.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
add_filter( 'woocommerce_checkout_fields', 'econur_checkout_order_notes', 20 );
function econur_checkout_order_notes( $fields ) {
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['label']       = __( 'Delivery notes (optional)', 'econur' );
		$fields['order']['order_comments']['placeholder'] = __( 'Landmark, preferred delivery time…', 'econur' );
	}
	return $fields;
}
