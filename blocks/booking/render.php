<?php
/**
 * Renders the [ubac-booking/widget] Gutenberg block.
 * It reuses the existing shortcode directly rather than duplicating the
 * widget's display logic, so there is a single source of truth:
 * Ubac_Booking_Public::render_shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ubac_booking_render_block' ) ) {
	function ubac_booking_render_block( $attributes, $content, $block ) {
		return do_shortcode( '[ubac_booking]' );
	}
}
