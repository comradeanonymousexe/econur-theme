<?php
/**
 * Content parsing + option helpers.
 *
 * These back the "simplest possible" native fields (chosen over ACF): staff
 * type one item per line (or "Heading :: description"), and these helpers turn
 * that into structured data for the templates. No third-party dependency.
 *
 * @package Econur
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Split a textarea value into trimmed, non-empty lines.
 *
 * @param string $value Raw textarea value.
 * @return string[]
 */
function econur_lines( $value ) {
	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return array();
	}
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $value ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line ) {
			$out[] = $line;
		}
	}
	return $out;
}

/**
 * Parse "Left :: Right" lines into [ ['label' => .., 'text' => ..], .. ].
 * A line with no delimiter puts everything in 'label' with empty 'text'
 * (so the same helper serves both benefit chips and heading+body blocks).
 *
 * @param string $value     Raw textarea value.
 * @param string $delimiter Pair delimiter.
 * @return array<int,array{label:string,text:string}>
 */
function econur_pairs( $value, $delimiter = '::' ) {
	$pairs = array();
	foreach ( econur_lines( $value ) as $line ) {
		if ( false !== strpos( $line, $delimiter ) ) {
			$parts = array_map( 'trim', explode( $delimiter, $line, 2 ) );
			$pairs[] = array(
				'label' => $parts[0],
				'text'  => isset( $parts[1] ) ? $parts[1] : '',
			);
		} else {
			$pairs[] = array(
				'label' => $line,
				'text'  => '',
			);
		}
	}
	return $pairs;
}

/**
 * Read a Customizer theme option with a default. Keys are namespaced 'econur_'.
 *
 * @param string $key     Option key without the econur_ prefix.
 * @param mixed  $default Fallback value.
 * @return mixed
 */
function econur_mod( $key, $default = '' ) {
	return get_theme_mod( 'econur_' . $key, $default );
}

/**
 * Read a product narrative meta field.
 *
 * @param int    $product_id Product ID.
 * @param string $key        Field key without the _econur_ prefix.
 * @return string
 */
function econur_product_meta( $product_id, $key ) {
	return (string) get_post_meta( $product_id, '_econur_' . $key, true );
}
