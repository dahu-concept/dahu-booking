<?php
/**
 * Plugin Name: Ubac Booking
 * Description: Lightweight self-hosted appointment booking plugin: two-step calendar widget, availability management, automatic confirmation emails, and secure reschedule/cancel links — no third-party service required.
 * Version: 1.0.3
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Hugo Vial-Jaime
 * Author URI: https://dahu-concept.fr
 * Text Domain: ubac-booking
 * Domain Path: /languages
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

// Security: blocks direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================
// PLUGIN CONSTANTS
// ============================================
define( 'UBAC_BOOKING_VERSION', '1.0.3' );
define( 'UBAC_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'UBAC_BOOKING_URL', plugin_dir_url( __FILE__ ) );
define( 'UBAC_BOOKING_TABLE_BOOKINGS', 'ubac_bookings' );
define( 'UBAC_BOOKING_TABLE_SLOTS', 'ubac_booking_slots' );
define( 'UBAC_BOOKING_FILE', __FILE__ );

// ============================================
// PLUGIN FILE INCLUDES
// ============================================
require_once UBAC_BOOKING_PATH . 'includes/class-ubac-booking-activator.php';
require_once UBAC_BOOKING_PATH . 'includes/class-ubac-booking-db.php';
require_once UBAC_BOOKING_PATH . 'includes/class-ubac-booking-mailer.php';
require_once UBAC_BOOKING_PATH . 'admin/class-ubac-booking-admin.php';
require_once UBAC_BOOKING_PATH . 'public/class-ubac-booking-public.php';
require_once UBAC_BOOKING_PATH . 'includes/ubac-welcome.php';

// ============================================
// PLUGIN ACTIVATION / DEACTIVATION
// ============================================
register_activation_hook( __FILE__, array( 'Ubac_Booking_Activator', 'activate' ) );

/*
 * The flag, not a redirect: an activation hook that redirects breaks bulk
 * activation and WP-CLI, which activate several plugins in one request. The
 * welcome screen reads the flag on the next page load, where a redirect is
 * safe, and clears it when several plugins were activated together.
 */
register_activation_hook(
	__FILE__,
	static function () {
		Ubac_Welcome::mark_activated( 'ubac-booking' );
	}
);

add_action(
	'init',
	static function () {
		Ubac_Welcome::register(
			array(
				'slug'       => 'ubac-booking',
				'name'       => __( 'Appointment booking', 'ubac-booking' ),
				'tagline'    => __( 'Appointments booked on your own site, with no third-party service.', 'ubac-booking' ),
				'file'       => UBAC_BOOKING_FILE,
				'version'    => UBAC_BOOKING_VERSION,
				'textdomain' => 'ubac-booking',
				'mark'       => array(
					'view'  => '0 0 112 112',
					'paths' => array(
						'M20.0 24.0 H92.0 A10.0 10.0 0 0 1 102.0 34.0 V90.0 A10.0 10.0 0 0 1 92.0 100.0 H20.0 A10.0 10.0 0 0 1 10.0 90.0 V34.0 A10.0 10.0 0 0 1 20.0 24.0 Z',
						'M10 50 L102 50',
						'M34 10 L34 32 M78 10 L78 32',
						'M72 72 L88 72 M72 86 L88 86',
						'M30.0 62.0 H52.0 A6.0 6.0 0 0 1 58.0 68.0 V82.0 A6.0 6.0 0 0 1 52.0 88.0 H30.0 A6.0 6.0 0 0 1 24.0 82.0 V68.0 A6.0 6.0 0 0 1 30.0 62.0 Z',
					),
				),
				/*
				 * The welcome screen's words come from here: the shared file is
				 * copied byte for byte across the whole family, so it can carry
				 * no text domain of its own.
				 */
				'strings'    => array(
					'next'   => __( 'Next', 'ubac-booking' ),
					'done'   => __( 'Done', 'ubac-booking' ),
					'skip'   => __( 'Skip', 'ubac-booking' ),
					'of'     => __( 'of', 'ubac-booking' ),
					'denied' => __( 'You are not allowed to view this page.', 'ubac-booking' ),
				),
				'points'     => array(
					array(
						'title' => __( 'Open the hours you keep', 'ubac-booking' ),
						'text'  => __( 'Availability is set once and reused.', 'ubac-booking' ),
					),
					array(
						'title' => __( 'A two-step calendar', 'ubac-booking' ),
						'text'  => __( 'The visitor picks a day, then a time.', 'ubac-booking' ),
					),
					array(
						'title' => __( 'Confirmed by email', 'ubac-booking' ),
						'text'  => __( 'With links to reschedule or cancel.', 'ubac-booking' ),
					),
				),
			)
		);
	}
);

// ============================================
// MODULE INITIALISATION
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
// PLUGINS PAGE -- ACTION LINKS AND META ROW
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
// GUTENBERG BLOCK
// ============================================
function ubac_booking_register_block() {
	require_once UBAC_BOOKING_PATH . 'blocks/booking/render.php';

	register_block_type( UBAC_BOOKING_PATH . 'blocks/booking', array(
		'render_callback' => 'ubac_booking_render_block',
	) );
}
add_action( 'init', 'ubac_booking_register_block' );
