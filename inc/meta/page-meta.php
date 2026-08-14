<?php
/**
 * Lab Report page meta: editable stat cards + download URLs.
 *
 * The spec (§4.3) requires the stat numbers and download file URLs to be
 * editable fields, not hardcoded, so a future lab re-test needs no developer.
 * The meta box only appears on pages using the Lab Report template.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lab field definitions: key => [label, default].
 *
 * @return array<string,array{0:string,1:string}>
 */
function econur_lab_fields() {
	return array(
		'stat1_value' => array( __( 'Stat 1 — value', 'econur' ), '75%–86%' ),
		'stat1_label' => array( __( 'Stat 1 — label', 'econur' ), 'Total Fatty Matter' ),
		'stat2_value' => array( __( 'Stat 2 — value', 'econur' ), 'Nil' ),
		'stat2_label' => array( __( 'Stat 2 — label', 'econur' ), 'Free Alkali Content' ),
		'stat3_value' => array( __( 'Stat 3 — value', 'econur' ), '100%' ),
		'stat3_label' => array( __( 'Stat 3 — label', 'econur' ), 'Natural Ingredients' ),
		'bcsir_url'   => array( __( 'BCSIR lab report (PDF URL)', 'econur' ), '' ),
		'buet_url'    => array( __( 'BUET certificate (image/PDF URL)', 'econur' ), '' ),
	);
}

/**
 * Read a lab field with its default fallback (used by the template too).
 *
 * @param int    $post_id Page ID.
 * @param string $key     Field key.
 * @return string
 */
function econur_lab_field( $post_id, $key ) {
	$fields = econur_lab_fields();
	$value  = get_post_meta( $post_id, '_econur_lab_' . $key, true );
	if ( '' === $value && isset( $fields[ $key ][1] ) ) {
		$value = $fields[ $key ][1];
	}
	return (string) $value;
}

add_action( 'add_meta_boxes', 'econur_add_lab_meta' );
/**
 * Register the Lab Report meta box only on that page template.
 */
function econur_add_lab_meta() {
	$post = get_post();
	if ( ! $post ) {
		return;
	}
	if ( 'page-lab-report.php' !== get_page_template_slug( $post->ID ) ) {
		return;
	}
	add_meta_box(
		'econur_lab_meta',
		__( 'Econur — Lab Report Fields', 'econur' ),
		'econur_render_lab_meta',
		'page',
		'normal',
		'high'
	);
}

/**
 * Render the Lab Report meta box.
 *
 * @param WP_Post $post Current page.
 */
function econur_render_lab_meta( $post ) {
	wp_nonce_field( 'econur_lab_meta', 'econur_lab_meta_nonce' );
	echo '<p class="description">' . esc_html__( 'Editable stat numbers and download URLs for the Lab Report page. Leave a URL blank to hide that download button.', 'econur' ) . '</p>';

	foreach ( econur_lab_fields() as $key => $field ) {
		$value  = get_post_meta( $post->ID, '_econur_lab_' . $key, true );
		if ( '' === $value ) {
			$value = $field[1];
		}
		$id     = 'econur_lab_' . $key;
		$is_url = ( false !== strpos( $key, '_url' ) );

		printf(
			'<p style="margin:12px 0 4px;font-weight:600;"><label for="%1$s">%2$s</label></p>',
			esc_attr( $id ),
			esc_html( $field[0] )
		);
		printf(
			'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" class="widefat">',
			$is_url ? 'url' : 'text',
			esc_attr( $id ),
			esc_attr( $value )
		);
	}
}

add_action( 'save_post_page', 'econur_save_lab_meta' );
/**
 * Persist Lab Report meta.
 *
 * @param int $post_id Page ID.
 */
function econur_save_lab_meta( $post_id ) {
	if ( ! isset( $_POST['econur_lab_meta_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['econur_lab_meta_nonce'] ), 'econur_lab_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( econur_lab_fields() as $key => $field ) {
		$name = 'econur_lab_' . $key;
		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}
		$raw   = wp_unslash( $_POST[ $name ] );
		$clean = ( false !== strpos( $key, '_url' ) ) ? esc_url_raw( $raw ) : sanitize_text_field( $raw );
		update_post_meta( $post_id, '_econur_lab_' . $key, $clean );
	}
}
