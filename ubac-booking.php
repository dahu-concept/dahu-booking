<?php
/**
 * Plugin Name: Ubac Booking
 * Description: Lightweight self-hosted appointment booking plugin: two-step calendar widget, availability management, automatic confirmation emails, and secure reschedule/cancel links — no third-party service required.
 * Version: 1.0.1
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Hugo Vial-Jaime
 * Author URI: https://dahu-concept.fr
 * Text Domain: ubac-booking
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Sécurité : empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================
// CONSTANTES DU PLUGIN
// ============================================
define( 'UBAC_BOOKING_VERSION', '1.0.1' );
define( 'UBAC_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'UBAC_BOOKING_URL', plugin_dir_url( __FILE__ ) );
define( 'UBAC_BOOKING_TABLE_BOOKINGS', 'ubac_bookings' );
define( 'UBAC_BOOKING_TABLE_SLOTS', 'ubac_booking_slots' );

// ============================================
// INCLUSIONS DES FICHIERS DU PLUGIN
// ============================================
require_once UBAC_BOOKING_PATH . 'includes/class-ubac-booking-activator.php';
require_once UBAC_BOOKING_PATH . 'includes/class-ubac-booking-db.php';
require_once UBAC_BOOKING_PATH . 'includes/class-ubac-booking-mailer.php';
require_once UBAC_BOOKING_PATH . 'admin/class-ubac-booking-admin.php';
require_once UBAC_BOOKING_PATH . 'public/class-ubac-booking-public.php';

// ============================================
// ACTIVATION / DÉSACTIVATION DU PLUGIN
// ============================================
register_activation_hook( __FILE__, array( 'Ubac_Booking_Activator', 'activate' ) );

// ============================================
// INITIALISATION DES MODULES
// ============================================
function ubac_booking_init() {
    // Module Admin (wp-admin)
    if ( is_admin() ) {
        $admin = new Ubac_Booking_Admin();
        $admin->init();
    }

    // Module Public (front-end : shortcode, AJAX, page de modification)
    $public = new Ubac_Booking_Public();
    $public->init();
}
add_action( 'plugins_loaded', 'ubac_booking_init' );

// ============================================
// PAGE EXTENSIONS — ACTION LINKS ET LIGNE MÉTA
// ============================================

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ),
    function( $links ) {
        $settings = '<a href="' . admin_url( 'admin.php?page=ubac-booking-settings' ) . '">'
            . __( 'Settings', 'ubac-booking' ) . '</a>';
        array_unshift( $links, $settings );
        return $links;
    }
);

add_filter( 'plugin_row_meta', function( $links, $file ) {
    if ( plugin_basename( __FILE__ ) === $file ) {
        $header = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
        $links  = array(
            /* translators: %s: plugin version number */
            sprintf( __( 'Version %s', 'ubac-booking' ), $header['Version'] ),
            '<a href="https://dahu-concept.fr" target="_blank">By Hugo Vial-Jaime — Dahu-Concept</a>',
            '<a href="https://dahu-concept.fr" target="_blank">' . __( 'Visit plugin site', 'ubac-booking' ) . '</a>',
        );
    }
    return $links;
}, 10, 2 );

// ============================================
// BLOC GUTENBERG
// ============================================
function ubac_booking_register_block() {
    require_once UBAC_BOOKING_PATH . 'blocks/booking/render.php';

    register_block_type( UBAC_BOOKING_PATH . 'blocks/booking', array(
        'render_callback' => 'ubac_booking_render_block',
    ) );
}
add_action( 'init', 'ubac_booking_register_block' );
