<?php
/**
 * Theme options via the native WordPress Customizer.
 *
 * No page builder, no settings-framework plugin — the Customizer is the free,
 * built-in home for the admin-configurable bits the spec calls for: the
 * homepage Featured Product (§4.1.1), the hero ingredients list (§4.1.4), the
 * WhatsApp support number, and social links.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'customize_register', 'econur_customize_register' );
/**
 * Register Customizer section, settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function econur_customize_register( $wp_customize ) {

	$wp_customize->add_section(
		'econur_home',
		array(
			'title'    => __( 'Econur — Homepage & Brand', 'econur' ),
			'priority' => 30,
		)
	);

	// --- Homepage carousel: promo / offer slides (optional) ---
	// Featured PRODUCT slides come from WooCommerce's native "Featured" star
	// toggle (Products list → star), so no setting is needed for them — this
	// field only adds custom marketing/offer slides alongside them (spec §4.1.1).
	$wp_customize->add_setting(
		'econur_offer_slides',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'econur_offer_slides',
		array(
			'label'       => __( 'Carousel promo / offer slides', 'econur' ),
			'description' => __( 'One slide per line: "Headline :: Subtext :: Button label :: URL :: Image URL (optional)". Leave blank to show only featured products.', 'econur' ),
			'section'     => 'econur_home',
			'type'        => 'textarea',
		)
	);

	// --- Hero ingredients (Ingredients section — spec §4.1.4) ---
	$wp_customize->add_setting(
		'econur_ingredients',
		array(
			'default'           => econur_default_ingredients(),
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'econur_ingredients',
		array(
			'label'       => __( 'Hero ingredients', 'econur' ),
			'description' => __( 'One per line as "Name :: one-line benefit".', 'econur' ),
			'section'     => 'econur_home',
			'type'        => 'textarea',
		)
	);

	// --- WhatsApp support number ---
	$wp_customize->add_setting(
		'econur_whatsapp_number',
		array(
			'default'           => '8801410753555',
			'sanitize_callback' => 'econur_sanitize_digits',
		)
	);
	$wp_customize->add_control(
		'econur_whatsapp_number',
		array(
			'label'       => __( 'WhatsApp number (digits only, international)', 'econur' ),
			'description' => __( 'Used by the floating chat button. e.g. 8801410753555', 'econur' ),
			'section'     => 'econur_home',
			'type'        => 'text',
		)
	);

	// --- Social links ---
	$socials = array(
		'facebook_url'  => array( __( 'Facebook URL', 'econur' ), 'https://facebook.com/econurskincare' ),
		'instagram_url' => array( __( 'Instagram URL', 'econur' ), 'https://instagram.com/econur.skincare' ),
	);
	foreach ( $socials as $key => $data ) {
		$wp_customize->add_setting(
			'econur_' . $key,
			array(
				'default'           => $data[1],
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			'econur_' . $key,
			array(
				'label'   => $data[0],
				'section' => 'econur_home',
				'type'    => 'url',
			)
		);
	}
}

/**
 * Digits-only sanitizer for phone numbers.
 *
 * @param string $value Raw value.
 * @return string
 */
function econur_sanitize_digits( $value ) {
	return preg_replace( '/\D+/', '', (string) $value );
}

/**
 * Seed the 9 hero ingredients from the brand brief (editable afterwards).
 *
 * @return string
 */
function econur_default_ingredients() {
	return implode(
		"\n",
		array(
			'Olive Oil :: Antioxidant-rich oil that softens and deeply conditions skin.',
			'Neem :: Powerful antibacterial that fights acne and purifies pores.',
			'Green Tea :: Loaded with antioxidants to calm redness and shield skin.',
			'Activated Charcoal :: Deep-cleanses pores by drawing out toxins and impurities.',
			'Honey :: Natural humectant that locks in moisture and heals skin.',
			'Mint :: Cools and refreshes while soothing irritation instantly.',
			'Castor Oil :: Thick, nourishing oil that boosts hydration and suppleness.',
			'Liquorice :: Brightens dark spots and evens out skin tone naturally.',
			'Calendula :: Gentle floral extract that repairs, calms, and regenerates.',
		)
	);
}

add_filter( 'econur_whatsapp_support_number', 'econur_whatsapp_number_from_mod' );
/**
 * Feed the WhatsApp FAB number from the Customizer setting.
 *
 * @param string $number Default number.
 * @return string
 */
function econur_whatsapp_number_from_mod( $number ) {
	$mod = econur_mod( 'whatsapp_number', $number );
	return $mod ? $mod : $number;
}

/**
 * Parse the Customizer promo/offer slides into structured data.
 * One slide per line: "Headline :: Subtext :: Button label :: URL :: Image URL".
 *
 * @return array<int,array<string,string>>
 */
function econur_offer_slides() {
	$slides = array();
	foreach ( econur_lines( econur_mod( 'offer_slides', '' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '::', $line ) );
		if ( empty( $parts[0] ) ) {
			continue;
		}
		$slides[] = array(
			'headline' => $parts[0],
			'subtext'  => isset( $parts[1] ) ? $parts[1] : '',
			'btn'      => isset( $parts[2] ) ? $parts[2] : '',
			'url'      => isset( $parts[3] ) ? $parts[3] : '',
			'image'    => isset( $parts[4] ) ? $parts[4] : '',
		);
	}
	return $slides;
}
