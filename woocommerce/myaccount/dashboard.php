<?php
/**
 * Profile — the My Account dashboard, rebuilt.
 *
 * NOTE ON THE THREE-TEMPLATE CONSTRAINT (spec §1): this is deliberately NOT a new
 * page template. My Account is a WooCommerce-managed page, in the same bracket as
 * cart and checkout, which §1 explicitly scopes out of the "exactly three
 * templates" rule. Building the profile here keeps the template count at three
 * and means WooCommerce's own routing, endpoints and permissions keep working.
 *
 * Sections: greeting + number confirmation, offers, recent orders with one-tap
 * reorder, and a details summary with edit links.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$econ_user_id = get_current_user_id();
$econ_user    = wp_get_current_user();
$econ_first   = $econ_user->first_name ? $econ_user->first_name : $econ_user->display_name;
$econ_orders  = function_exists( 'econur_recent_orders' ) ? econur_recent_orders( 4 ) : array();
$econ_phone   = get_user_meta( $econ_user_id, 'econur_phone_login', true );
$econ_wa      = get_user_meta( $econ_user_id, 'econur_whatsapp_number', true );
$econ_date    = get_user_meta( $econ_user_id, 'econur_special_date', true );
$econ_label   = get_user_meta( $econ_user_id, 'econur_special_date_label', true );
$econ_placeholder_email = (bool) get_user_meta( $econ_user_id, 'econur_placeholder_email', true );
?>

<div class="econ-profile">

	<header class="econ-profile__header">
		<h1 class="econ-profile__greeting">
			<?php
			printf(
				/* translators: %s: customer first name. */
				esc_html__( 'Hello, %s', 'econur' ),
				esc_html( $econ_first )
			);
			?>
		</h1>
		<p class="econ-profile__sub">
			<?php esc_html_e( 'Your orders, your details, and anything we have picked out for you.', 'econur' ); ?>
		</p>
	</header>

	<?php
	/**
	 * Offers the customer currently qualifies for, filled by the CRM plugin's
	 * rules-based value ladder (spec §6). Marketing edits the rules in
	 * Econur CRM → Offer Rules; nothing here is hardcoded.
	 *
	 * Fired before woocommerce_account_dashboard so the ladder knows to suppress
	 * its single "picked for you" card and not say the same thing twice.
	 */
	do_action( 'econur_account_offers', $econ_user_id );

	/**
	 * Referral code, points balance and redemption — filled by the CRM plugin.
	 * Sits above orders because it is the thing we most want the customer to act
	 * on, and it is the only section that asks something of them.
	 */
	do_action( 'econur_account_referrals', $econ_user_id );

	/**
	 * Nudges from inc/account.php, plus anything third-party plugins attach.
	 */
	do_action( 'woocommerce_account_dashboard' );
	?>

	<section class="econ-profile__section">
		<div class="econ-profile__section-head">
			<h2 class="econ-profile__heading"><?php esc_html_e( 'Recent orders', 'econur' ); ?></h2>
			<?php if ( ! empty( $econ_orders ) ) : ?>
				<a class="econ-profile__more" href="<?php echo esc_url( wc_get_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'See all', 'econur' ); ?></a>
			<?php endif; ?>
		</div>

		<?php if ( empty( $econ_orders ) ) : ?>

			<div class="econ-profile__empty">
				<p><?php esc_html_e( 'No orders yet — your first one will show up here.', 'econur' ); ?></p>
				<a class="econ-btn econ-btn--primary" href="<?php echo esc_url( home_url( '/#shop' ) ); ?>"><?php esc_html_e( 'Browse the soaps', 'econur' ); ?></a>
			</div>

		<?php else : ?>

			<ul class="econ-orders">
				<?php foreach ( $econ_orders as $econ_order ) : ?>
					<?php
					$econ_items   = $econ_order->get_items();
					$econ_first_item = $econ_items ? reset( $econ_items ) : null;
					$econ_product = $econ_first_item ? $econ_first_item->get_product() : null;
					$econ_extra   = max( 0, count( $econ_items ) - 1 );
					?>
					<li class="econ-orders__item">
						<div class="econ-orders__thumb" aria-hidden="true">
							<?php
							if ( $econ_product ) {
								echo wp_kses_post( $econ_product->get_image( 'woocommerce_thumbnail' ) );
							}
							?>
						</div>

						<div class="econ-orders__body">
							<p class="econ-orders__title">
								<?php
								if ( $econ_first_item ) {
									echo esc_html( $econ_first_item->get_name() );
									if ( $econ_extra ) {
										echo ' <span class="econ-orders__more">';
										printf(
											/* translators: %d: number of additional items. */
											esc_html( _n( '+%d more item', '+%d more items', $econ_extra, 'econur' ) ),
											(int) $econ_extra
										);
										echo '</span>';
									}
								} else {
									esc_html_e( 'Order', 'econur' );
								}
								?>
							</p>
							<p class="econ-orders__meta">
								<span class="econ-orders__status econ-orders__status--<?php echo esc_attr( $econ_order->get_status() ); ?>">
									<?php echo esc_html( wc_get_order_status_name( $econ_order->get_status() ) ); ?>
								</span>
								<span><?php echo esc_html( wc_format_datetime( $econ_order->get_date_created(), 'j M Y' ) ); ?></span>
								<span><?php echo wp_kses_post( $econ_order->get_formatted_order_total() ); ?></span>
							</p>
						</div>

						<div class="econ-orders__actions">
							<a class="econ-btn econ-btn--ghost econ-btn--sm" href="<?php echo esc_url( $econ_order->get_view_order_url() ); ?>">
								<?php esc_html_e( 'Details', 'econur' ); ?>
							</a>
							<?php if ( function_exists( 'econur_reorder_url' ) && $econ_order->has_status( array( 'processing', 'completed' ) ) ) : ?>
								<a class="econ-btn econ-btn--order econ-btn--sm" href="<?php echo esc_url( econur_reorder_url( $econ_order ) ); ?>">
									<?php esc_html_e( 'Order again', 'econur' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

		<?php endif; ?>
	</section>

	<section class="econ-profile__section">
		<div class="econ-profile__section-head">
			<h2 class="econ-profile__heading"><?php esc_html_e( 'Your details', 'econur' ); ?></h2>
			<a class="econ-profile__more" href="<?php echo esc_url( wc_get_endpoint_url( 'edit-account' ) ); ?>"><?php esc_html_e( 'Edit', 'econur' ); ?></a>
		</div>

		<dl class="econ-details">
			<div class="econ-details__row">
				<dt><?php esc_html_e( 'Name', 'econur' ); ?></dt>
				<dd><?php echo esc_html( trim( $econ_user->first_name . ' ' . $econ_user->last_name ) ? trim( $econ_user->first_name . ' ' . $econ_user->last_name ) : $econ_user->display_name ); ?></dd>
			</div>

			<div class="econ-details__row">
				<dt><?php esc_html_e( 'Mobile', 'econur' ); ?></dt>
				<dd><?php echo $econ_phone ? esc_html( $econ_phone ) : '<span class="econ-details__missing">' . esc_html__( 'Not set', 'econur' ) . '</span>'; ?></dd>
			</div>

			<div class="econ-details__row">
				<dt><?php esc_html_e( 'Email', 'econur' ); ?></dt>
				<dd>
					<?php if ( $econ_placeholder_email ) : ?>
						<span class="econ-details__missing"><?php esc_html_e( 'None — add one so you can reset a forgotten password', 'econur' ); ?></span>
					<?php else : ?>
						<?php echo esc_html( $econ_user->user_email ); ?>
					<?php endif; ?>
				</dd>
			</div>

			<div class="econ-details__row">
				<dt><?php esc_html_e( 'WhatsApp', 'econur' ); ?></dt>
				<dd><?php echo $econ_wa ? esc_html( $econ_wa ) : '<span class="econ-details__missing">' . esc_html__( 'Same as mobile', 'econur' ) . '</span>'; ?></dd>
			</div>

			<div class="econ-details__row">
				<dt><?php esc_html_e( 'Date to remember', 'econur' ); ?></dt>
				<dd>
					<?php if ( $econ_date ) : ?>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: label such as Birthday, 2: formatted date. */
								__( '%1$s — %2$s', 'econur' ),
								$econ_label ? $econ_label : __( 'Special date', 'econur' ),
								date_i18n( 'j F', strtotime( $econ_date ) )
							)
						);
						?>
					<?php else : ?>
						<span class="econ-details__missing"><?php esc_html_e( 'Not set — add one and we will send a reminder', 'econur' ); ?></span>
					<?php endif; ?>
				</dd>
			</div>

			<div class="econ-details__row">
				<dt><?php esc_html_e( 'Delivery address', 'econur' ); ?></dt>
				<dd>
					<?php
					$econ_address = wc_get_account_formatted_address( 'billing', $econ_user_id );
					if ( $econ_address ) {
						echo wp_kses_post( $econ_address );
					} else {
						echo '<span class="econ-details__missing">' . esc_html__( 'Not set', 'econur' ) . '</span>';
					}
					?>
					<a class="econ-details__edit" href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'billing' ) ); ?>"><?php esc_html_e( 'Change', 'econur' ); ?></a>
				</dd>
			</div>
		</dl>
	</section>

	<?php
	// WooCommerce core fires these from its own dashboard template; third-party
	// plugins hook them, so keep them.
	do_action( 'woocommerce_before_my_account' );
	do_action( 'woocommerce_after_my_account' );
	?>

</div>
