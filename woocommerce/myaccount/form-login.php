<?php
/**
 * Sign in / create account.
 *
 * Overrides WooCommerce's default side-by-side login-register form. One panel is
 * visible at a time, switched by a segmented control — the convention people
 * expect, and the only sane layout on a phone, which is where most of this
 * traffic is (spec §8).
 *
 * The switch is built from real links (`?econ_view=register`), so it works with
 * JavaScript disabled: the server reads the query and renders the right panel.
 * assets/js/account.js upgrades it to an instant client-side toggle.
 *
 * Two changes of substance beyond layout (spec §6):
 *  - the login identifier accepts a mobile number as well as an email;
 *  - the registration email field is optional, because this is a phone-first,
 *    cash-on-delivery store and most customers never give an email.
 * Everything else — nonces, hooks, error handling — is WooCommerce's own.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_customer_login_form' );

$econur_can_register = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$econur_base_url     = wc_get_page_permalink( 'myaccount' );

/*
 * Which panel opens: registration if the customer asked for it, or if a
 * registration attempt just failed and WooCommerce is redisplaying their input —
 * landing them back on the sign-in tab with an error they can't see would be a
 * small cruelty.
 */
$econur_view = 'login';
if ( $econur_can_register ) {
	// phpcs:disable WordPress.Security.NonceVerification -- Read-only view selection; the forms verify their own nonces.
	// Own namespaced param rather than the generic `action`, which WordPress and
	// plugins both reach for and which we'd rather not fight over.
	$econur_requested = isset( $_GET['econ_view'] ) ? sanitize_key( wp_unslash( $_GET['econ_view'] ) ) : '';
	if ( 'register' === $econur_requested || ! empty( $_POST['register'] ) ) {
		$econur_view = 'register';
	}
	// phpcs:enable WordPress.Security.NonceVerification
}
?>

<div class="econ-auth" data-econ-auth data-view="<?php echo esc_attr( $econur_view ); ?>">

	<?php if ( $econur_can_register ) : ?>
		<div class="econ-auth__switch" role="tablist" aria-label="<?php esc_attr_e( 'Sign in or create an account', 'econur' ); ?>">
			<a class="econ-auth__tab" id="econ-tab-login" href="<?php echo esc_url( add_query_arg( 'econ_view', 'login', $econur_base_url ) ); ?>"
				role="tab" aria-controls="econ-panel-login" aria-selected="<?php echo 'login' === $econur_view ? 'true' : 'false'; ?>"
				data-econ-auth-tab="login">
				<?php esc_html_e( 'Sign in', 'econur' ); ?>
			</a>
			<a class="econ-auth__tab" id="econ-tab-register" href="<?php echo esc_url( add_query_arg( 'econ_view', 'register', $econur_base_url ) ); ?>"
				role="tab" aria-controls="econ-panel-register" aria-selected="<?php echo 'register' === $econur_view ? 'true' : 'false'; ?>"
				data-econ-auth-tab="register">
				<?php esc_html_e( 'Create account', 'econur' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<section class="econ-auth__panel" id="econ-panel-login" data-econ-auth-panel="login"
		<?php echo $econur_can_register ? 'role="tabpanel" aria-labelledby="econ-tab-login"' : ''; ?>>

		<h2 class="econ-auth__title"><?php esc_html_e( 'Welcome back', 'econur' ); ?></h2>
		<p class="econ-auth__lead"><?php esc_html_e( 'Sign in to reorder in a tap and keep your delivery details on file.', 'econur' ); ?></p>

		<form class="woocommerce-form woocommerce-form-login login" method="post">

			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username"><?php esc_html_e( 'Mobile number or email', 'econur' ); ?>&nbsp;<span class="required">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username"
					placeholder="01712345678"
					value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Redisplay of a rejected value; WC verifies the login nonce. ?>
			</p>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password"><?php esc_html_e( 'Password', 'econur' ); ?>&nbsp;<span class="required">*</span></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>

			<p class="form-row econ-auth__actions">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
					<span><?php esc_html_e( 'Stay signed in', 'econur' ); ?></span>
				</label>
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="econ-btn econ-btn--primary woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Sign in', 'econur' ); ?>"><?php esc_html_e( 'Sign in', 'econur' ); ?></button>
			</p>

			<p class="woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgotten your password?', 'econur' ); ?></a>
				<span class="econ-field-hint"><?php esc_html_e( 'Password resets are sent by email. If you signed up with a mobile number only, message us on WhatsApp and we will help.', 'econur' ); ?></span>
			</p>

			<?php do_action( 'woocommerce_login_form_end' ); ?>

		</form>

		<?php if ( $econur_can_register ) : ?>
			<p class="econ-auth__crosslink">
				<?php esc_html_e( 'No account yet?', 'econur' ); ?>
				<a href="<?php echo esc_url( add_query_arg( 'econ_view', 'register', $econur_base_url ) ); ?>" data-econ-auth-tab="register"><?php esc_html_e( 'Create one', 'econur' ); ?></a>
			</p>
		<?php endif; ?>
	</section>

	<?php if ( $econur_can_register ) : ?>

		<section class="econ-auth__panel" id="econ-panel-register" data-econ-auth-panel="register"
			role="tabpanel" aria-labelledby="econ-tab-register">

			<h2 class="econ-auth__title"><?php esc_html_e( 'Create your account', 'econur' ); ?></h2>
			<p class="econ-auth__lead"><?php esc_html_e( 'Optional — you can always check out as a guest. It just saves you typing your address every time.', 'econur' ); ?></p>

			<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>

				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_username"><?php esc_html_e( 'Username', 'econur' ); ?>&nbsp;<span class="required">*</span></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>
					</p>
				<?php endif; ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_email"><?php esc_html_e( 'Email address', 'econur' ); ?> <span class="econ-optional"><?php esc_html_e( '(optional)', 'econur' ); ?></span></label>
					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" /><?php // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>
					<span class="econ-field-hint"><?php esc_html_e( 'Only used for order confirmations and password resets.', 'econur' ); ?></span>
				</p>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_password"><?php esc_html_e( 'Password', 'econur' ); ?>&nbsp;<span class="required">*</span></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" minlength="8" />
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'econur' ); ?></p>
				<?php endif; ?>

				<?php do_action( 'woocommerce_register_form' ); ?>

				<p class="woocommerce-form-row form-row econ-auth__actions">
					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<?php // Not econ-btn--order: that treatment is reserved for the purchase action alone (client rule). ?>
					<button type="submit" class="econ-btn econ-btn--primary woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Create account', 'econur' ); ?>"><?php esc_html_e( 'Create account', 'econur' ); ?></button>
				</p>

				<?php do_action( 'woocommerce_register_form_end' ); ?>

			</form>

			<p class="econ-auth__crosslink">
				<?php esc_html_e( 'Already have an account?', 'econur' ); ?>
				<a href="<?php echo esc_url( add_query_arg( 'econ_view', 'login', $econur_base_url ) ); ?>" data-econ-auth-tab="login"><?php esc_html_e( 'Sign in', 'econur' ); ?></a>
			</p>
		</section>

	<?php endif; ?>

</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
