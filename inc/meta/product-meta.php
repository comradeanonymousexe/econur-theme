<?php
/**
 * Native product "story" meta box (no ACF).
 *
 * Simplest-possible UI (client choice): text + textarea fields with documented
 * one-item-per-line / "Heading :: description" conventions. No admin JS/CSS.
 * The long-form intro deliberately reuses WooCommerce's own product
 * Description editor rather than adding a duplicate field.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field definitions: key => [label, type, description].
 *
 * @return array<string,array{label:string,type:string,desc:string}>
 */
function econur_product_fields() {
	return array(
		'positioning' => array(
			'label' => __( 'Hero positioning line', 'econur' ),
			'type'  => 'text',
			'desc'  => __( 'One short line under the product name. e.g. "Deep-cleansing charcoal bar for oily, acne-prone skin."', 'econur' ),
		),
		'benefits'    => array(
			'label' => __( 'What it does for skin (benefit chips)', 'econur' ),
			'type'  => 'textarea',
			'desc'  => __( 'One benefit per line; each becomes a chip. e.g. "Deep pore cleansing".', 'econur' ),
		),
		'efficacy'    => array(
			'label' => __( 'Ingredient efficacy deep-dive', 'econur' ),
			'type'  => 'textarea',
			'desc'  => __( 'One block per line as "Heading :: 1–2 sentence explanation". e.g. "Charcoal deep cleansing :: Activated charcoal draws out toxins and unclogs pores."', 'econur' ),
		),
		'ingredients' => array(
			'label' => __( "What's inside (ingredient tags)", 'econur' ),
			'type'  => 'textarea',
			'desc'  => __( 'One ingredient per line. e.g. "Activated Charcoal". Rendered as tags.', 'econur' ),
		),
		'best_for'    => array(
			'label' => __( 'Best for (use-case tags)', 'econur' ),
			'type'  => 'textarea',
			'desc'  => __( 'One tag per line. e.g. "Oily skin", "People who work outdoors".', 'econur' ),
		),
		'usage'       => array(
			'label' => __( 'Usage & storage', 'econur' ),
			'type'  => 'textarea',
			'desc'  => __( 'Short usage and storage guidance. Line breaks are preserved.', 'econur' ),
		),
	);
}

add_action( 'add_meta_boxes', 'econur_add_product_meta_box' );
/**
 * Register the meta box on the product screen.
 */
function econur_add_product_meta_box() {
	add_meta_box(
		'econur_product_details',
		__( 'Econur — Product Story', 'econur' ),
		'econur_render_product_meta_box',
		'product',
		'normal',
		'high'
	);
}

/**
 * Render the meta box.
 *
 * @param WP_Post $post Current product.
 */
function econur_render_product_meta_box( $post ) {
	wp_nonce_field( 'econur_product_meta', 'econur_product_meta_nonce' );
	echo '<p class="description" style="margin:0 0 14px;">' .
		esc_html__( 'The long-form intro paragraphs come from the main Product description editor above. These fields power the rest of the product template.', 'econur' ) .
		'</p>';

	foreach ( econur_product_fields() as $key => $field ) {
		$value = get_post_meta( $post->ID, '_econur_' . $key, true );
		$id    = 'econur_' . $key;

		printf(
			'<p style="margin:0 0 4px;font-weight:600;"><label for="%1$s">%2$s</label></p>',
			esc_attr( $id ),
			esc_html( $field['label'] )
		);

		if ( 'textarea' === $field['type'] ) {
			printf(
				'<textarea id="%1$s" name="%1$s" rows="5" class="widefat" style="font-family:ui-monospace,monospace;">%2$s</textarea>',
				esc_attr( $id ),
				esc_textarea( $value )
			);
		} else {
			printf(
				'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="widefat">',
				esc_attr( $id ),
				esc_attr( $value )
			);
		}

		printf( '<p class="description" style="margin:4px 0 18px;">%s</p>', esc_html( $field['desc'] ) );
	}
}

add_action( 'save_post_product', 'econur_save_product_meta' );
/**
 * Persist the meta box fields.
 *
 * @param int $post_id Product ID.
 */
function econur_save_product_meta( $post_id ) {
	if ( ! isset( $_POST['econur_product_meta_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['econur_product_meta_nonce'] ), 'econur_product_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( econur_product_fields() as $key => $field ) {
		$name = 'econur_' . $key;
		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}
		$raw   = wp_unslash( $_POST[ $name ] );
		$clean = ( 'textarea' === $field['type'] ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		update_post_meta( $post_id, '_econur_' . $key, $clean );
	}
}
