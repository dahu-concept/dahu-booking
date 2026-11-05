<?php
/**
 * Fonction de rendu du bloc Gutenberg [ubac-booking/widget].
 * Réutilise directement le shortcode existant pour ne pas dupliquer la logique
 * d'affichage du widget (une seule source de vérité : Ubac_Booking_Public::render_shortcode).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ubac_booking_render_block' ) ) {
    function ubac_booking_render_block( $attributes, $content, $block ) {
        return do_shortcode( '[ubac_booking]' );
    }
}
