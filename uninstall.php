<?php
/**
 * Runs when the plugin is uninstalled (deleted) from the WordPress admin.
 * Removes all options and database tables created by Dahu Booking.
 *
 * @package Dahu_Booking
 */

// Security: only run when WordPress itself triggers the uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// ============================================
// 1. Remove plugin options
// ============================================
$dahu_options = array(
    'dahu_booking_open_days',
    'dahu_booking_time_slots',
    'dahu_booking_duration',
    'dahu_booking_admin_email',
    'dahu_booking_min_notice_hours',
    'dahu_booking_max_days_ahead',
    'dahu_booking_location',
    'dahu_booking_event_title',
    'dahu_booking_show_meta_step1',
);

foreach ( $dahu_options as $option ) {
    delete_option( $option );
}

// ============================================
// 2. Drop custom database tables
// ============================================
global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'dahu_bookings' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'dahu_booking_slots' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange
