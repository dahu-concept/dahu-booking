<?php
/**
 * Runs when the plugin is uninstalled (deleted) from the WordPress admin.
 * Removes all options and database tables created by Ubac Booking.
 *
 * @package Ubac_Booking
 */

// Security: only run when WordPress itself triggers the uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

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
);

foreach ( $ubac_options as $option ) {
    delete_option( $option );
}

// ============================================
// 2. Drop custom database tables
// ============================================
global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ubac_bookings' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ubac_booking_slots' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange
