<?php
/**
 * Runs when the plugin is uninstalled (deleted) from the WordPress admin.
 *
 * The options go unconditionally: they are configuration, and a reinstall
 * sets them again.
 *
 * The two tables hold the appointments -- every booking ever taken, with the
 * name, email address and message the customer gave -- and the availability
 * behind them. Until 06/11/2026 they were dropped unconditionally, so deleting
 * the plugin destroyed the lot with no flag, no choice and no way back, while
 * every other plugin of the family guarded far less personal data behind a
 * box. They are now behind the same kind of box, off by default: ticked on the
 * settings screen, which writes `ubac_booking_delete_data_on_uninstall`.
 *
 * @package Ubac_Booking
 */

// Security: only run when WordPress itself triggers the uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Removes this plugin's options, and its tables when the site asked for it.
 *
 * One site at a time, because every option and both tables are per-site. Until
 * 06/11/2026 this file ran its work once, at top level, with no get_sites()
 * loop: on a network with the plugin active network-wide, only the site the
 * deletion happened to run on was cleaned. Every other site kept its options,
 * its three welcome flags among them -- so its welcome screen and tour stayed
 * silent for good with nothing on screen to explain why -- and a site that had
 * ticked the box kept its tables, its explicit choice having no effect.
 *
 * @return void
 */
function ubac_booking_uninstall_site() {

	// ============================================
	// 1. Remove plugin options
	// ============================================
	$ubac_options = array(
		'ubac_booking_open_days',
		'ubac_booking_time_slots',
		'ubac_booking_duration',
		'ubac_booking_admin_email',
		'ubac_booking_min_notice_hours',
		'ubac_booking_max_days_ahead',
		'ubac_booking_location',
		'ubac_booking_event_title',
		'ubac_booking_show_meta_step1',
		'ubac_booking_delete_data_on_uninstall',

		// The welcome screen's own three flags.
		//
		// They are interface state, not the site's data: whatever a site chose to
		// keep, a reinstall has to greet it again. `includes/ubac-welcome.php`
		// writes them with update_option(), so they outlive the plugin unless
		// they are deleted here — and the welcome screen and the guided tour
		// would then stay silent for good, with nothing on screen to explain why.
		'ubac_welcome_seen_ubac-booking',
		'ubac_welcome_pending_ubac-booking',
		'ubac_welcome_tour_ubac-booking',
	);

	// Read before the loop below deletes it along with the rest of the options.
	$ubac_booking_delete_data = (string) get_option( 'ubac_booking_delete_data_on_uninstall', '0' );

	foreach ( $ubac_options as $ubac_booking_option ) {
		delete_option( $ubac_booking_option );
	}

	// ============================================
	// 2. Drop custom database tables
	// ============================================
	global $wpdb;

	/*
	 * Compared against '1' rather than cast to bool.
	 *
	 * With this plugin's own '1'/'0' convention a cast would in fact be correct:
	 * measured, `(bool) '0'` is FALSE and `empty( '0' )` is TRUE. An earlier
	 * version of this comment claimed the opposite, and a wrong justification is
	 * worse than none, because it is the thing meant to stop the next person
	 * reintroducing the bug.
	 *
	 * The reason to compare explicitly is the OTHER convention in this family:
	 * WooCommerce's checkboxes store 'yes'/'no', and `(bool) 'no'` IS true. An
	 * explicit comparison is right under both, so it is the one rule worth
	 * holding everywhere -- and the bench refuses a cast for that reason.
	 *
	 * The flag is in the option list above and so is deleted with the rest, which
	 * is why it is read into a variable before that loop runs.
	 */
	if ( '1' === $ubac_booking_delete_data ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ubac_bookings' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ubac_booking_slots' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange
	}
}

if ( is_multisite() ) {

	/*
	 * `number => 0` for every site, not the first hundred: WP_Site_Query's own
	 * default caps the list at a hundred, and a network larger than that would
	 * keep this plugin's options on every site past the cap.
	 */
	$ubac_booking_site_ids = get_sites(
		array(
			'fields'        => 'ids',
			'number'        => 0,
			'no_found_rows' => true,
		)
	);

	foreach ( $ubac_booking_site_ids as $ubac_booking_site_id ) {
		switch_to_blog( (int) $ubac_booking_site_id );
		ubac_booking_uninstall_site();
		restore_current_blog();
	}
} else {
	// if/else rather than an unconditional call followed by the loop: the loop
	// includes the current site, so the call outside it would run twice on it.
	ubac_booking_uninstall_site();
}
