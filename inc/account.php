<?php
/**
 * My Account extras: special-date + WhatsApp capture, dashboard nudges, and the
 * value-ladder surface hook. Loaded only when WooCommerce is active. (spec §6)
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extra fields on the Edit Account form: WhatsApp number (prefilled from the
 * checkout phone) and a "special date" the customer wants remembered, stored as
 * user meta. Login stays optional — these are never a purchase gate. (spec §6)
 */
add_action( 'woocommerce_edit_account_form', 'econur_account_extra_fields' );
function econur_account_extra_fields() {
	$user_id = get_current_user_id();
	$date    = get_user_meta( $user_id, 'econur_special_date', true );
	$label   = get_user_meta( $user_id, 'econur_special_date_label', true );
	$wa      = get_user_meta( $user_id, 'econur_whatsapp_number', true );
	if ( ! $wa ) {
		$wa = get_user_meta( $user_id, 'billing_phone', true ); // Prefill, don't force re-entry.
	}
	?>
	<fieldset class="econ-account-extra">
		<legend><?php esc_html_e( 'Stay in touch', 'econur' ); ?></legend>

		<p class="woocommerce-form-row form-row form-row-wide">
			<label for="econur_whatsapp_number"><?php esc_html_e( 'WhatsApp number', 'econur' ); ?></label>
			<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="econur_whatsapp_number" id="econur_whatsapp_number" value="<?php echo esc_attr( $wa ); ?>" placeholder="01712345678">
		</p>

		<p class="woocommerce-form-row form-row form-row-first">
			<label for="econur_special_date"><?php esc_html_e( 'A date to remember', 'econur' ); ?></label>
			<input type="date" class="woocommerce-Input woocommerce-Input--text input-text" name="econur_special_date" id="econur_special_date" value="<?php echo esc_attr( $date ); ?>">
		</p>

		<p class="woocommerce-form-row form-row form-row-last">
			<label for="econur_special_date_label"><?php esc_html_e( 'What is it?', 'econur' ); ?></label>
			<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="econur_special_date_label" id="econur_special_date_label" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( 'Birthday, Anniversary, Eid…', 'econur' ); ?>">
		</p>
	</fieldset>
	<?php
}

/**
 * Persist the extra account fields, then fire an action so the CRM plugin can
 * (re)build the annual special-date reminder. WooCommerce has already verified
 * its edit-account nonce before this hook runs. (spec §6, §7.1)
 *
 * @param int $user_id User ID.
 */
add_action( 'woocommerce_save_account_details', 'econur_account_save_extra_fields' );
function econur_account_save_extra_fields( $user_id ) {
	if ( isset( $_POST['econur_whatsapp_number'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verifies save_account_details_nonce.
		update_user_meta( $user_id, 'econur_whatsapp_number', preg_replace( '/\D+/', '', sanitize_text_field( wp_unslash( $_POST['econur_whatsapp_number'] ) ) ) );
	}
	if ( isset( $_POST['econur_special_date'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$date = sanitize_text_field( wp_unslash( $_POST['econur_special_date'] ) );
		update_user_meta( $user_id, 'econur_special_date', preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '' );
	}
	if ( isset( $_POST['econur_special_date_label'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_user_meta( $user_id, 'econur_special_date_label', sanitize_text_field( wp_unslash( $_POST['econur_special_date_label'] ) ) );
	}

	do_action( 'econur_special_date_saved', $user_id );
}

/**
 * My Account dashboard: nudge to add a special date, and expose the value-ladder
 * hook the CRM plugin fills with a "recommended next product". (spec §6)
 */
add_action( 'woocommerce_account_dashboard', 'econur_account_dashboard_extras' );
function econur_account_dashboard_extras() {
	$user_id = get_current_user_id();

	if ( ! get_user_meta( $user_id, 'econur_special_date', true ) ) {
		printf(
			'<div class="econ-account-nudge">%1$s <a href="%2$s">%3$s</a></div>',
			esc_html__( 'Tell us a date worth remembering — a birthday, anniversary or Eid — and we will send a little reminder.', 'econur' ),
			esc_url( wc_get_endpoint_url( 'edit-account' ) ),
			esc_html__( 'Add it', 'econur' )
		);
	}

	// The CRM plugin renders a personalised recommendation here (Phase 8, spec §6).
	do_action( 'econur_account_recommendation', $user_id );
}

/* -------------------------------------------------------------------------
 * My Account: two tabs only
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_account_menu_items', 'econur_account_menu_items', 20 );
/**
 * Reduce My Account to Dashboard, Profile and Log out.
 *
 * WooCommerce ships six tabs. For a cash-on-delivery soap shop, four of them are
 * dead weight: Downloads (nothing is downloadable), Payment methods (COD stores
 * no cards), Addresses and Account details (both folded into Profile), and Orders
 * (the dashboard already lists them, with reorder).
 *
 * Only the MENU is trimmed — the endpoints stay registered, so existing links,
 * WooCommerce emails and "view order" URLs keep resolving instead of 404ing.
 *
 * @param array $items Menu items.
 * @return array
 */
function econur_account_menu_items( $items ) {
	$keep = array(
		'dashboard'    => __( 'Dashboard', 'econur' ),
		'edit-account' => __( 'Profile', 'econur' ),
		'customer-logout' => isset( $items['customer-logout'] ) ? $items['customer-logout'] : __( 'Log out', 'econur' ),
	);

	// Preserve any third-party tab rather than silently deleting someone's feature.
	$core = array( 'dashboard', 'orders', 'downloads', 'edit-address', 'payment-methods', 'edit-account', 'customer-logout' );
	foreach ( $items as $key => $label ) {
		if ( ! in_array( $key, $core, true ) ) {
			$keep[ $key ] = $label;
		}
	}

	// Log out belongs last.
	$logout = $keep['customer-logout'];
	unset( $keep['customer-logout'] );
	$keep['customer-logout'] = $logout;

	return $keep;
}

add_action( 'woocommerce_edit_account_form_start', 'econur_profile_address_intro' );
/**
 * Addresses no longer have their own tab, so point at them from Profile.
 */
function econur_profile_address_intro() {
	printf(
		'<p class="econ-account-nudge">%1$s <a href="%2$s">%3$s</a></p>',
		esc_html__( 'Delivery address is saved with your orders.', 'econur' ),
		esc_url( wc_get_endpoint_url( 'edit-address' ) ),
		esc_html__( 'Edit addresses', 'econur' )
	);
}
