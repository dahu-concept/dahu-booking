<?php
/**
 * Fonction de rendu du bloc Gutenberg [dahu-booking/widget].
 * Réutilise directement le shortcode existant pour ne pas dupliquer la logique
 * d'affichage du widget (une seule source de vérité : Dahu_Booking_Public::render_shortcode).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'dahu_booking_render_block' ) ) {
    function dahu_booking_render_block( $attributes, $content, $block ) {
        return do_shortcode( '[dahu_booking]' );
    }
}
