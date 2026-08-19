<?php
/**
 * Accounts & authentication (spec §6).
 *
 * The build already had the *post*-login surface (My Account extras, special-date
 * capture, value-ladder hook in inc/account.php) but nothing that let a customer
 * actually get an account or sign in. This module supplies that missing layer:
 *
 *  - Sign in with EITHER a Bangladeshi mobile number OR an email address.
 *  - Registration is phone-first: mobile is required, email is optional.
 *  - Account creation is offered in three places, none of which gate a purchase:
 *      1. My Account (/my-account/) — combined login + register screen.
 *      2. A checkout opt-in checkbox.
 *      3. A one-tap claim on the order-received page, for guests who just bought.
 *  - Guest checkout is untouched. Nothing here ever blocks buying (spec §5, §6).
 *
 * We build on WooCommerce's native customer/auth system rather than a parallel
 * one (spec §6) — everything below is filters and hooks over WC_Form_Handler and
 * wp_authenticate.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Domain used for synthesised addresses when a phone-first customer gives no
 * email. `.invalid` is reserved by RFC 2606 and can never resolve, so these can
 * never accidentally reach a real inbox. Mail to them is dropped (see
 * econur_block_placeholder_mail).
 */
const ECONUR_PLACEHOLDER_EMAIL_DOMAIN = 'phone.invalid';

/* -------------------------------------------------------------------------
 * Phone helpers
 * ---------------------------------------------------------------------- */

/**
 * Normalise any user-entered Bangladeshi mobile number to canonical 01XXXXXXXXX.
 *
 * Accepts +8801…, 8801…, 01…, 1… and any spacing/dashes. Returns '' when the
 * number is not a valid BD mobile — same rule as checkout validation (spec §5).
 *
 * @param string $raw Raw input.
 * @return string Canonical number, or '' if invalid.
 */
function econur_normalize_bd_phone( $raw ) {
	$digits = preg_replace( '/\D+/', '', (string) $raw );

	if ( '' === $digits ) {
		return '';
	}
	if ( 0 === strpos( $digits, '880' ) ) {
		$digits = substr( $digits, 3 );
	}
	if ( 10 === strlen( $digits ) && '1' === $digits[0] ) {
		$digits = '0' . $digits; // Entered without the leading zero.
	}

	return preg_match( '/^01[3-9]\d{8}$/', $digits ) ? $digits : '';
}

/**
 * Find the account that owns a mobile number.
 *
 * `econur_phone_login` is our canonical, always-normalised lookup key; it is kept
 * in sync with billing_phone (see econur_sync_phone_login_meta). We fall back to
 * a raw billing_phone match so accounts created before this module still resolve.
 *
 * @param string $phone Canonical number from econur_normalize_bd_phone().
 * @return WP_User|null
 */
function econur_get_user_by_phone( $phone ) {
	if ( '' === $phone ) {
		return null;
	}

	foreach ( array( 'econur_phone_login', 'billing_phone' ) as $meta_key ) {
		$users = get_users(
			array(
				'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $phone,    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
				'orderby'    => 'ID',
				'order'      => 'ASC',
			)
		);
		if ( ! empty( $users ) ) {
			return $users[0];
		}
	}

	return null;
}

/**
 * Keep the canonical phone-login key in sync whenever billing_phone is written —
 * by checkout, by the address book, or by an admin. Hooking the meta write means
 * we catch every path without having to enumerate them.
 *
 * @param int    $meta_id  Ignored.
 * @param int    $user_id  User ID.
 * @param string $meta_key Meta key.
 * @param mixed  $value    Meta value.
 */
add_action( 'updated_user_meta', 'econur_sync_phone_login_meta', 10, 4 );
add_action( 'added_user_meta', 'econur_sync_phone_login_meta', 10, 4 );
function econur_sync_phone_login_meta( $meta_id, $user_id, $meta_key, $value ) {
	if ( 'billing_phone' !== $meta_key ) {
		return;
	}
	$phone = econur_normalize_bd_phone( $value );
	if ( '' === $phone ) {
		return;
	}
	// Never let a second account hijack a number that already belongs to someone.
	$owner = econur_get_user_by_phone( $phone );
	if ( $owner && (int) $owner->ID !== (int) $user_id ) {
		return;
	}
	update_user_meta( $user_id, 'econur_phone_login', $phone );
}

/* -------------------------------------------------------------------------
 * Placeholder emails (phone-first accounts)
 * ---------------------------------------------------------------------- */

/**
 * Synthesise an address for a customer who registered with a phone and no email.
 *
 * @param string $phone Canonical number.
 * @return string
 */
function econur_placeholder_email( $phone ) {
	return $phone . '@' . ECONUR_PLACEHOLDER_EMAIL_DOMAIN;
}

/**
 * Is this one of our synthesised addresses?
 *
 * @param string $email Email address.
 * @return bool
 */
function econur_is_placeholder_email( $email ) {
	return (bool) preg_match( '/@' . preg_quote( ECONUR_PLACEHOLDER_EMAIL_DOMAIN, '/' ) . '$/i', (string) $email );
}

/**
 * Drop any mail addressed only to a synthesised address.
 *
 * Without this, WooCommerce's "new account" and order emails would queue up
 * permanent bounces for every phone-only customer. Returning true short-circuits
 * wp_mail() and reports success, which is what the calling code expects.
 *
 * @param null|bool $short_circuit Null by default.
 * @param array     $atts          wp_mail() arguments.
 * @return null|bool
 */
add_filter( 'pre_wp_mail', 'econur_block_placeholder_mail', 10, 2 );
function econur_block_placeholder_mail( $short_circuit, $atts ) {
	$to = isset( $atts['to'] ) ? $atts['to'] : array();
	$to = is_array( $to ) ? $to : explode( ',', (string) $to );

	foreach ( $to as $recipient ) {
		if ( '' !== trim( $recipient ) && ! econur_is_placeholder_email( $recipient ) ) {
			return $short_circuit; // At least one real recipient — send normally.
		}
	}

	return empty( $to ) ? $short_circuit : true;
}

/* -------------------------------------------------------------------------
 * Sign in with phone OR email
 * ---------------------------------------------------------------------- */

/**
 * Phone-number sign-in.
 *
 * Runs at priority 30, i.e. AFTER core's username (20) and email (20) checks, so
 * the standard paths keep working untouched and we only handle what they could
 * not resolve. We re-run the wp_authenticate_user gate so blocked/spam accounts
 * are still rejected.
 *
 * @param null|WP_User|WP_Error $user     Result so far.
 * @param string                $username Submitted identifier.
 * @param string                $password Submitted password.
 * @return null|WP_User|WP_Error
 */
add_filter( 'authenticate', 'econur_authenticate_by_phone', 30, 3 );
function econur_authenticate_by_phone( $user, $username, $password ) {
	if ( $user instanceof WP_User ) {
		return $user;
	}
	if ( '' === (string) $username || '' === (string) $password ) {
		return $user;
	}

	$phone = econur_normalize_bd_phone( $username );
	if ( '' === $phone ) {
		return $user; // Not a phone number — leave core's error intact.
	}

	$found = econur_get_user_by_phone( $phone );
	if ( ! $found ) {
		return $user;
	}

	if ( ! wp_check_password( $password, $found->user_pass, $found->ID ) ) {
		return new WP_Error(
			'econur_incorrect_password',
			__( 'The password for that mobile number is incorrect.', 'econur' )
		);
	}

	return apply_filters( 'wp_authenticate_user', $found, $password );
}

/* -------------------------------------------------------------------------
 * WooCommerce account options — forced on at the front end
 * ---------------------------------------------------------------------- */

/**
 * The accounts feature needs registration switched on to exist at all, so we
 * force the relevant WooCommerce options at the front end. The admin Accounts
 * screen still reads the real stored value, so nothing looks broken in wp-admin
 * (same pattern already used for guest checkout in inc/woocommerce.php).
 *
 * - registration on My Account: the login/register screen.
 * - signup from checkout:       the "create an account" opt-in checkbox.
 * - generate username:          derived from the email (or, for phone-first
 *                               accounts, from the synthesised address — which
 *                               yields the phone number itself as the username).
 * - generate password:          off, so customers choose their own.
 */
foreach (
	array(
		'woocommerce_enable_myaccount_registration'        => 'yes',
		'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
		'woocommerce_registration_generate_username'       => 'yes',
		'woocommerce_registration_generate_password'       => 'no',
	) as $econur_option => $econur_forced
) {
	add_filter(
		'option_' . $econur_option,
		static function ( $value ) use ( $econur_forced ) {
			return is_admin() ? $value : $econur_forced;
		}
	);
}

/* -------------------------------------------------------------------------
 * Registration form
 * ---------------------------------------------------------------------- */

/**
 * Extra registration fields: mobile (required, the primary identifier) and the
 * two personalisation fields the CRM reminder engine feeds on (spec §6, §7.1).
 *
 * The WhatsApp number is intentionally NOT a separate input here — it defaults to
 * the mobile above, which is the whole point of the client's "don't make me enter
 * data twice" requirement. Customers can split the two later in Edit Account.
 */
add_action( 'woocommerce_register_form_start', 'econur_register_form_phone' );
function econur_register_form_phone() {
	$posted = isset( $_POST['econur_reg_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['econur_reg_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Redisplay only; WC verifies its own nonce.
	?>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="econur_reg_phone"><?php esc_html_e( 'Mobile number', 'econur' ); ?>&nbsp;<span class="required">*</span></label>
		<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="econur_reg_phone" id="econur_reg_phone"
			autocomplete="tel" inputmode="numeric" placeholder="01712345678" required
			value="<?php echo esc_attr( $posted ); ?>">
		<span class="econ-field-hint"><?php esc_html_e( 'This is how you sign in, and how we reach you about your order.', 'econur' ); ?></span>
	</p>
	<?php
}

/**
 * Optional personalisation fields, shown after the password (spec §6: capture
 * these on account creation). Both are optional so registration stays a
 * 15-second job on a phone.
 */
add_action( 'woocommerce_register_form', 'econur_register_form_special_date' );
function econur_register_form_special_date() {
	?>
	<fieldset class="econ-register-extra">
		<legend><?php esc_html_e( 'A date worth remembering (optional)', 'econur' ); ?></legend>
		<p class="econ-field-hint"><?php esc_html_e( 'A birthday, anniversary or Eid — we will send a small reminder when it comes around.', 'econur' ); ?></p>

		<p class="woocommerce-form-row form-row form-row-first">
			<label for="econur_reg_special_date"><?php esc_html_e( 'Date', 'econur' ); ?></label>
			<input type="date" class="woocommerce-Input woocommerce-Input--text input-text" name="econur_reg_special_date" id="econur_reg_special_date">
		</p>
		<p class="woocommerce-form-row form-row form-row-last">
			<label for="econur_reg_special_date_label"><?php esc_html_e( 'What is it?', 'econur' ); ?></label>
			<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="econur_reg_special_date_label" id="econur_reg_special_date_label" placeholder="<?php esc_attr_e( 'Birthday, Anniversary, Eid…', 'econur' ); ?>">
		</p>
	</fieldset>
	<?php
}

/**
 * Make the mobile number carry the account when no email is given.
 *
 * WHY THIS MUTATES $_POST — there is no alternative. WC_Form_Handler::process_registration
 * gates on `isset( $_POST['register'], $_POST['email'] )` and reads the address straight
 * into a local variable (WooCommerce includes/class-wc-form-handler.php, ~line 1186-1189).
 * Every downstream hook runs too late: `woocommerce_process_registration_errors` receives
 * the email by value and can only return errors, and `woocommerce_new_customer_data` sits
 * inside wc_create_new_customer(), which already rejected the empty address. Supplying the
 * value before the handler runs is therefore the only mechanism available.
 *
 * We verify WooCommerce's own registration nonce FIRST so a stray POST can never cause us
 * to rewrite the superglobal — WooCommerce will verify it again, and a request that fails
 * here is one we simply leave untouched.
 *
 * A bad or missing phone means we substitute nothing and WooCommerce's own "email is
 * required" error stands. The phone itself is validated in econur_validate_registration().
 */
add_action( 'wp_loaded', 'econur_registration_email_fallback', 19 );
function econur_registration_email_fallback() {
	if ( is_admin() || empty( $_POST['register'] ) ) {
		return;
	}

	// Verify before touching anything. WooCommerce re-verifies in its own handler.
	$nonce = isset( $_POST['woocommerce-register-nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['woocommerce-register-nonce'] ) )
		: '';
	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'woocommerce-register' ) ) {
		return;
	}

	// Only fill a genuinely absent address; never overwrite what the customer typed.
	if ( ! empty( $_POST['email'] ) ) {
		return;
	}

	$phone = econur_normalize_bd_phone(
		isset( $_POST['econur_reg_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['econur_reg_phone'] ) ) : ''
	);
	if ( '' === $phone ) {
		return;
	}

	$_POST['email'] = econur_placeholder_email( $phone );
}

/**
 * Validate the mobile number and reject duplicates.
 *
 * @param WP_Error $errors Validation errors.
 * @return WP_Error
 */
add_filter( 'woocommerce_process_registration_errors', 'econur_validate_registration' );
function econur_validate_registration( $errors ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies its nonce before this filter runs.
	$raw   = isset( $_POST['econur_reg_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['econur_reg_phone'] ) ) : '';
	$phone = econur_normalize_bd_phone( $raw );
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( '' === $raw ) {
		$errors->add( 'econur_phone_required', __( 'Please enter your mobile number — it is how you sign in.', 'econur' ) );
		return $errors;
	}
	if ( '' === $phone ) {
		$errors->add( 'econur_phone_invalid', __( 'Please enter a valid Bangladeshi mobile number, e.g. 01712345678.', 'econur' ) );
		return $errors;
	}
	if ( econur_get_user_by_phone( $phone ) ) {
		$errors->add(
			'econur_phone_exists',
			sprintf(
				/* translators: %s: My Account URL. */
				wp_kses_post( __( 'An account already uses that mobile number. <a href="%s">Sign in instead</a>.', 'econur' ) ),
				esc_url( wc_get_page_permalink( 'myaccount' ) )
			)
		);
	}

	return $errors;
}

/**
 * Persist everything the registration form collected, once the customer exists.
 *
 * @param int $customer_id New user ID.
 */
add_action( 'woocommerce_created_customer', 'econur_save_registration_meta' );
function econur_save_registration_meta( $customer_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verified its nonce before creating the customer.
	$phone = econur_normalize_bd_phone( isset( $_POST['econur_reg_phone'] ) ? wp_unslash( $_POST['econur_reg_phone'] ) : '' );

	if ( '' !== $phone ) {
		econur_attach_phone_to_user( $customer_id, $phone );
	}

	if ( ! empty( $_POST['econur_reg_special_date'] ) ) {
		$date = sanitize_text_field( wp_unslash( $_POST['econur_reg_special_date'] ) );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			update_user_meta( $customer_id, 'econur_special_date', $date );
			update_user_meta(
				$customer_id,
				'econur_special_date_label',
				! empty( $_POST['econur_reg_special_date_label'] )
					? sanitize_text_field( wp_unslash( $_POST['econur_reg_special_date_label'] ) )
					: __( 'Special date', 'econur' )
			);
			// Let the CRM plugin build the annual reminder (spec §7.1).
			do_action( 'econur_special_date_saved', $customer_id );
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	econur_flag_placeholder_email( $customer_id );
}

/**
 * Write the phone number everywhere it needs to live: our canonical login key,
 * WooCommerce's billing field (so checkout prefills), and the CRM's WhatsApp
 * contact number (spec §6 — never make the customer type it twice).
 *
 * @param int    $user_id User ID.
 * @param string $phone   Canonical number.
 */
function econur_attach_phone_to_user( $user_id, $phone ) {
	update_user_meta( $user_id, 'econur_phone_login', $phone );

	if ( ! get_user_meta( $user_id, 'billing_phone', true ) ) {
		update_user_meta( $user_id, 'billing_phone', $phone );
	}
	if ( ! get_user_meta( $user_id, 'econur_whatsapp_number', true ) ) {
		update_user_meta( $user_id, 'econur_whatsapp_number', $phone );
	}
}

/**
 * Mark (or clear) the "this account has no real email" flag.
 *
 * @param int $user_id User ID.
 */
function econur_flag_placeholder_email( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}
	if ( econur_is_placeholder_email( $user->user_email ) ) {
		update_user_meta( $user_id, 'econur_placeholder_email', 1 );
	} else {
		delete_user_meta( $user_id, 'econur_placeholder_email' );
	}
}

/**
 * Re-evaluate the flag whenever the customer edits their account — adding a real
 * address in Edit Account is how they switch password recovery back on.
 *
 * @param int $user_id User ID.
 */
add_action( 'woocommerce_save_account_details', 'econur_flag_placeholder_email', 20 );

/* -------------------------------------------------------------------------
 * Checkout opt-in
 * ---------------------------------------------------------------------- */

/**
 * Reword WooCommerce's "create an account?" checkout checkbox so the benefit is
 * concrete. The checkbox itself is native WooCommerce and stays optional —
 * unchecked by default, and guest checkout is unaffected (spec §5).
 *
 * @return string
 */
add_filter( 'woocommerce_create_account_checkbox_label', 'econur_checkout_account_copy' );
function econur_checkout_account_copy() {
	return __( 'Save my details for next time (creates an account — optional)', 'econur' );
}

/**
 * Let a customer tick "save my details" without having given an email.
 *
 * WC_Checkout::process_customer() hands $data['billing_email'] straight to
 * wc_create_new_customer(), which rejects an empty address before any of its own
 * filters run — so the substitution has to happen in the posted data. We only do
 * it when the opt-in box is actually ticked; a plain guest order is left alone.
 *
 * @param array $data Posted checkout data.
 * @return array
 */
add_filter( 'woocommerce_checkout_posted_data', 'econur_checkout_account_email_fallback' );
function econur_checkout_account_email_fallback( $data ) {
	if ( empty( $data['createaccount'] ) || ! empty( $data['billing_email'] ) ) {
		return $data;
	}
	$phone = econur_normalize_bd_phone( isset( $data['billing_phone'] ) ? $data['billing_phone'] : '' );
	if ( '' !== $phone ) {
		$data['billing_email'] = econur_placeholder_email( $phone );
	}
	return $data;
}

/**
 * ...but keep the synthesised address off the order itself. The account needs it;
 * the order record does not, and leaving it there would show a fake email in
 * WooCommerce's order screen and in the CRM's customer view.
 *
 * Runs after process_customer(), which is where the address was actually needed.
 *
 * @param WC_Order $order Order being built.
 */
add_action( 'woocommerce_checkout_create_order', 'econur_strip_placeholder_from_order', 20 );
function econur_strip_placeholder_from_order( $order ) {
	if ( econur_is_placeholder_email( $order->get_billing_email() ) ) {
		$order->set_billing_email( '' );
	}
}

/**
 * Give a checkout-created account a sensible username when WooCommerce has none
 * to derive (phone-first accounts get the phone number itself).
 *
 * @param array $data Customer data about to be passed to wp_insert_user().
 * @return array
 */
add_filter( 'woocommerce_new_customer_data', 'econur_checkout_new_customer_data' );
function econur_checkout_new_customer_data( $data ) {
	if ( ! empty( $data['user_login'] ) || empty( $data['user_email'] ) ) {
		return $data;
	}
	if ( econur_is_placeholder_email( $data['user_email'] ) ) {
		$data['user_login'] = strstr( $data['user_email'], '@', true );
	}
	return $data;
}

/**
 * Finish wiring an account that was created during checkout.
 *
 * @param WC_Customer $customer Customer object (empty ID for guests).
 * @param array       $data     Posted checkout data.
 */
add_action( 'woocommerce_checkout_update_customer', 'econur_checkout_seed_customer', 10, 2 );
function econur_checkout_seed_customer( $customer, $data ) {
	$user_id = is_object( $customer ) && method_exists( $customer, 'get_id' ) ? (int) $customer->get_id() : 0;
	if ( ! $user_id ) {
		return;
	}
	$phone = econur_normalize_bd_phone( isset( $data['billing_phone'] ) ? $data['billing_phone'] : '' );
	if ( '' !== $phone ) {
		econur_attach_phone_to_user( $user_id, $phone );
	}
	econur_flag_placeholder_email( $user_id );
}

/* -------------------------------------------------------------------------
 * Post-checkout account claim (order-received page)
 * ---------------------------------------------------------------------- */

/**
 * Can this order still be turned into an account?
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function econur_order_is_claimable( $order ) {
	if ( is_user_logged_in() || ! $order instanceof WC_Order ) {
		return false;
	}
	if ( $order->get_customer_id() ) {
		return false; // Already belongs to an account.
	}

	$phone = econur_normalize_bd_phone( $order->get_billing_phone() );
	if ( '' === $phone || econur_get_user_by_phone( $phone ) ) {
		return false;
	}

	$email = $order->get_billing_email();
	return ! ( $email && email_exists( $email ) );
}

/**
 * The claim form, rendered by woocommerce/checkout/thankyou.php.
 *
 * This is the highest-intent moment we have: the customer has just bought, their
 * details are already on file, and all that is missing is a password. One field.
 *
 * @param WC_Order $order Order.
 */
function econur_order_account_claim_form( $order ) {
	if ( ! econur_order_is_claimable( $order ) ) {
		return;
	}
	$email = $order->get_billing_email();
	?>
	<form class="econ-claim" method="post" action="<?php echo esc_url( $order->get_checkout_order_received_url() ); ?>">
		<h2 class="econ-claim__title"><?php esc_html_e( 'Keep your details for next time', 'econur' ); ?></h2>
		<p class="econ-claim__lead">
			<?php
			printf(
				/* translators: %s: customer mobile number. */
				esc_html__( 'Pick a password and %s becomes your login — no re-typing your address on your next order, and you can track this one from your account.', 'econur' ),
				'<strong>' . esc_html( $order->get_billing_phone() ) . '</strong>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			?>
		</p>

		<?php if ( ! $email ) : ?>
			<p class="econ-claim__row">
				<label for="econur_claim_email"><?php esc_html_e( 'Email (optional — lets you reset a forgotten password)', 'econur' ); ?></label>
				<input type="email" name="econur_claim_email" id="econur_claim_email" autocomplete="email">
			</p>
		<?php endif; ?>

		<p class="econ-claim__row">
			<label for="econur_claim_password"><?php esc_html_e( 'Choose a password', 'econur' ); ?></label>
			<input type="password" name="econur_claim_password" id="econur_claim_password" autocomplete="new-password" minlength="8" required>
		</p>

		<input type="hidden" name="econur_claim_order" value="<?php echo esc_attr( $order->get_id() ); ?>">
		<input type="hidden" name="econur_claim_key" value="<?php echo esc_attr( $order->get_order_key() ); ?>">
		<?php wp_nonce_field( 'econur_claim_account', 'econur_claim_nonce' ); ?>

		<button type="submit" class="econ-btn econ-btn--primary" name="econur_claim_account" value="1">
			<?php esc_html_e( 'Create my account', 'econur' ); ?>
		</button>
		<p class="econ-claim__skip"><?php esc_html_e( 'Not now? Your order is already confirmed either way.', 'econur' ); ?></p>
	</form>
	<?php
}

/**
 * Handle the claim submission.
 *
 * Possession of the order key is the proof of identity here, which is why this —
 * and only this — flow is allowed to attach the order to the new account. Plain
 * registration deliberately does NOT adopt matching guest orders: a phone number
 * typed into a signup form proves nothing, and past orders carry the customer's
 * home address.
 */
add_action( 'template_redirect', 'econur_handle_account_claim' );
function econur_handle_account_claim() {
	if ( empty( $_POST['econur_claim_account'] ) || is_user_logged_in() ) {
		return;
	}
	if ( ! isset( $_POST['econur_claim_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['econur_claim_nonce'] ) ), 'econur_claim_account' ) ) {
		return;
	}

	$order_id = isset( $_POST['econur_claim_order'] ) ? absint( wp_unslash( $_POST['econur_claim_order'] ) ) : 0;
	$key      = isset( $_POST['econur_claim_key'] ) ? sanitize_text_field( wp_unslash( $_POST['econur_claim_key'] ) ) : '';
	$order    = $order_id ? wc_get_order( $order_id ) : false;

	if ( ! $order || ! hash_equals( $order->get_order_key(), $key ) || ! econur_order_is_claimable( $order ) ) {
		wc_add_notice( __( 'That order could not be verified. Please create your account from the sign-in page.', 'econur' ), 'error' );
		return;
	}

	$password = isset( $_POST['econur_claim_password'] ) ? (string) wp_unslash( $_POST['econur_claim_password'] ) : '';
	if ( strlen( $password ) < 8 ) {
		wc_add_notice( __( 'Please choose a password of at least 8 characters.', 'econur' ), 'error' );
		return;
	}

	$phone = econur_normalize_bd_phone( $order->get_billing_phone() );
	$email = $order->get_billing_email();
	if ( ! $email && ! empty( $_POST['econur_claim_email'] ) ) {
		$candidate = sanitize_email( wp_unslash( $_POST['econur_claim_email'] ) );
		if ( is_email( $candidate ) && ! email_exists( $candidate ) ) {
			$email = $candidate;
		}
	}
	if ( ! $email ) {
		$email = econur_placeholder_email( $phone );
	}

	$customer_id = wc_create_new_customer( $email, $phone, $password, array( 'source' => 'econur-order-claim' ) );
	if ( is_wp_error( $customer_id ) ) {
		wc_add_notice( $customer_id->get_error_message(), 'error' );
		return;
	}

	// Carry the order's details across so the next checkout is prefilled.
	$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	wp_update_user(
		array(
			'ID'           => $customer_id,
			'first_name'   => $order->get_billing_first_name(),
			'last_name'    => $order->get_billing_last_name(),
			'display_name' => $name ? $name : $phone,
		)
	);
	foreach ( array( 'address_1', 'city', 'country' ) as $field ) {
		$getter = 'get_billing_' . $field;
		update_user_meta( $customer_id, 'billing_' . $field, $order->$getter() );
	}
	econur_attach_phone_to_user( $customer_id, $phone );
	econur_flag_placeholder_email( $customer_id );

	$order->set_customer_id( $customer_id );
	$order->save();

	wc_set_customer_auth_cookie( $customer_id );
	wc_add_notice( __( 'Account created — you are signed in. Add a date worth remembering in your account and we will send you a nudge.', 'econur' ), 'success' );

	wp_safe_redirect( $order->get_checkout_order_received_url() );
	exit;
}

/* -------------------------------------------------------------------------
 * Account surface
 * ---------------------------------------------------------------------- */

/**
 * Prompt phone-only customers to add a real email, since without one they cannot
 * use the "lost password" flow. Sits alongside the special-date nudge already in
 * inc/account.php.
 */
add_action( 'woocommerce_account_dashboard', 'econur_account_email_nudge', 5 );
function econur_account_email_nudge() {
	if ( ! get_user_meta( get_current_user_id(), 'econur_placeholder_email', true ) ) {
		return;
	}
	printf(
		'<div class="econ-account-nudge econ-account-nudge--warn">%1$s <a href="%2$s">%3$s</a></div>',
		esc_html__( 'You signed up with your mobile number only. Add an email address so you can reset your password if you ever forget it.', 'econur' ),
		esc_url( wc_get_endpoint_url( 'edit-account' ) ),
		esc_html__( 'Add an email', 'econur' )
	);
}

/**
 * Don't let a phone-only account start a password reset it can never finish.
 *
 * The reset mail would be addressed to a synthesised `.invalid` address and
 * silently dropped, leaving the customer staring at a "check your email" screen
 * forever. Fail loudly instead, and point them at a channel that works.
 *
 * @param bool|WP_Error $allow   Whether reset is allowed.
 * @param int           $user_id User ID.
 * @return bool|WP_Error
 */
add_filter( 'allow_password_reset', 'econur_block_placeholder_password_reset', 10, 2 );
function econur_block_placeholder_password_reset( $allow, $user_id ) {
	if ( ! $allow || is_wp_error( $allow ) ) {
		return $allow;
	}
	if ( ! get_user_meta( $user_id, 'econur_placeholder_email', true ) ) {
		return $allow;
	}
	return new WP_Error(
		'econur_no_email_on_file',
		__( 'This account was created with a mobile number only, so we have no email address to send a reset link to. Message us on WhatsApp and we will get you back in.', 'econur' )
	);
}

/**
 * Header account control: label and destination depend on sign-in state. Login is
 * an entry point, never a gate — the cart and checkout beside it stay open to
 * guests (spec §5, §6).
 *
 * @return array{url:string,label:string}
 */
function econur_account_link() {
	$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );

	if ( is_user_logged_in() ) {
		$user  = wp_get_current_user();
		$first = $user->first_name ? $user->first_name : $user->display_name;
		return array(
			'url'   => $url,
			/* translators: %s: customer first name. */
			'label' => sprintf( __( 'Your account (%s)', 'econur' ), $first ),
		);
	}

	return array(
		'url'   => $url,
		'label' => __( 'Sign in or create an account', 'econur' ),
	);
}

/**
 * Send customers back to the homepage after logging out, rather than to a bare
 * WordPress screen — the homepage IS the shop here (spec §9).
 *
 * @param string $redirect Default redirect.
 * @return string
 */
add_filter( 'woocommerce_logout_default_redirect_url', 'econur_logout_redirect' );
function econur_logout_redirect( $redirect ) {
	return home_url( '/' );
}
