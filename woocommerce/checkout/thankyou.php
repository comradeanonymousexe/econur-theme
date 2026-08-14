<?php
/**
 * Custom "Order received" / thank-you.
 *
 * Restyles WooCommerce's default confirmation to match the design system while
 * preserving the important hooks (payment-method output, emails, etc.). Keeps
 * the old site's "Order Placed!" beat, restyled. (spec §5)
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="econ-thankyou woocommerce-order">

	<?php
	// This page has no notice outlet of its own, but the account-claim form below
	// posts back here — so its success/error feedback needs somewhere to land.
	if ( function_exists( 'wc_print_notices' ) ) {
		wc_print_notices();
	}
	?>

	<?php if ( $order ) : ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<div class="econ-thankyou__panel is-failed">
				<h1 class="econ-thankyou__title"><?php esc_html_e( 'Payment could not be completed', 'econur' ); ?></h1>
				<p class="econ-thankyou__lead"><?php esc_html_e( 'Please try placing your order again, or contact us and we will help.', 'econur' ); ?></p>
				<p class="econ-thankyou__actions">
					<a class="econ-btn econ-btn--order econ-btn--lg" href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>"><?php esc_html_e( 'Try again', 'econur' ); ?></a>
				</p>
			</div>

		<?php else : ?>

			<div class="econ-thankyou__panel">
				<span class="econ-thankyou__check" aria-hidden="true"><?php econur_icon( 'check', 'econ-icon' ); ?></span>
				<h1 class="econ-thankyou__title"><?php esc_html_e( 'Order placed!', 'econur' ); ?></h1>
				<p class="econ-thankyou__lead">
					<?php esc_html_e( 'Thank you. We will call within 12 hours to confirm your order and delivery. Pay cash when it arrives.', 'econur' ); ?>
				</p>

				<ul class="econ-thankyou__meta">
					<li>
						<span class="econ-thankyou__meta-label"><?php esc_html_e( 'Order number', 'econur' ); ?></span>
						<span class="econ-thankyou__meta-value"><?php echo esc_html( $order->get_order_number() ); ?></span>
					</li>
					<li>
						<span class="econ-thankyou__meta-label"><?php esc_html_e( 'Date', 'econur' ); ?></span>
						<span class="econ-thankyou__meta-value"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
					</li>
					<li>
						<span class="econ-thankyou__meta-label"><?php esc_html_e( 'Total', 'econur' ); ?></span>
						<span class="econ-thankyou__meta-value"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
					</li>
					<li>
						<span class="econ-thankyou__meta-label"><?php esc_html_e( 'Payment', 'econur' ); ?></span>
						<span class="econ-thankyou__meta-value"><?php echo esc_html( $order->get_payment_method_title() ); ?></span>
					</li>
				</ul>

				<p class="econ-thankyou__actions">
					<a class="econ-btn econ-btn--primary econ-btn--lg" href="<?php echo esc_url( home_url( '/#shop' ) ); ?>"><?php esc_html_e( 'Continue shopping', 'econur' ); ?></a>
				</p>
			</div>

			<?php
			// Highest-intent moment to offer an account: details are already on
			// file, so all that's missing is a password (spec §6). Renders nothing
			// for signed-in customers or when the order can't be claimed.
			if ( function_exists( 'econur_order_account_claim_form' ) ) {
				econur_order_account_claim_form( $order );
			}

			// Preserve WooCommerce's order details, customer details, and hooks.
			do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
			do_action( 'woocommerce_thankyou', $order->get_id() );
			?>

		<?php endif; ?>

	<?php else : ?>

		<div class="econ-thankyou__panel">
			<h1 class="econ-thankyou__title"><?php esc_html_e( 'Thank you', 'econur' ); ?></h1>
			<p class="econ-thankyou__lead"><?php esc_html_e( 'Your order has been received.', 'econur' ); ?></p>
		</div>

	<?php endif; ?>
</div>
