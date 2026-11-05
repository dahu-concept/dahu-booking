<?php
/**
 * Creates the SQL tables and the default options when the plugin is
 * activated.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ubac_Booking_Activator {

    /**
     * Called when the plugin is activated.
     */
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // TABLE 1: the bookings.
        $table_bookings = $wpdb->prefix . UBAC_BOOKING_TABLE_BOOKINGS;

        $sql_bookings = "CREATE TABLE $table_bookings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            client_name VARCHAR(255) NOT NULL,
            client_email VARCHAR(255) NOT NULL,
            client_message TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
            token VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY booking_date_time (booking_date, booking_time),
            KEY status (status)
        ) $charset_collate;";

        // TABLE 2: slots blocked by hand. This is what lets the site owner close
        // one precise slot even when it falls inside a range that is otherwise
        // open.
        $table_slots = $wpdb->prefix . UBAC_BOOKING_TABLE_SLOTS;

        $sql_slots = "CREATE TABLE $table_slots (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            slot_date DATE NOT NULL,
            slot_time TIME NOT NULL,
            is_blocked TINYINT(1) NOT NULL DEFAULT 1,
            reason VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slot_date_time (slot_date, slot_time)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_bookings );
        dbDelta( $sql_slots );

        // DEFAULT OPTIONS (general settings).

        // Open days (0 = Sunday ... 6 = Saturday). Monday to Saturday by default.
        if ( false === get_option( 'ubac_booking_open_days' ) ) {
            update_option( 'ubac_booking_open_days', array( 1, 2, 3, 4, 5, 6 ) );
        }

        // Times offered by default.
        if ( false === get_option( 'ubac_booking_time_slots' ) ) {
            update_option( 'ubac_booking_time_slots', array(
                '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'
            ) );
        }

        // Length of an appointment in minutes (for display only).
        if ( false === get_option( 'ubac_booking_duration' ) ) {
            update_option( 'ubac_booking_duration', 60 );
        }

        // Email de notification (toi)
        if ( false === get_option( 'ubac_booking_admin_email' ) ) {
            update_option( 'ubac_booking_admin_email', get_option( 'admin_email' ) );
        }

        // Shortest notice before a booking, in hours -- keeps anyone from
        // booking ten minutes from now.
        if ( false === get_option( 'ubac_booking_min_notice_hours' ) ) {
            update_option( 'ubac_booking_min_notice_hours', 12 );
        }

        // How many days ahead a booking may be made.
        if ( false === get_option( 'ubac_booking_max_days_ahead' ) ) {
            update_option( 'ubac_booking_max_days_ahead', 60 );
        }

        // Where the appointment takes place (shown in the summary).
        if ( false === get_option( 'ubac_booking_location' ) ) {
            update_option( 'ubac_booking_location', '' );
        }

        // The event's title.
        if ( false === get_option( 'ubac_booking_event_title' ) ) {
            update_option( 'ubac_booking_event_title', 'Rendez-vous' );
        }

        // Whether the title and meta (length, place) show on step one.
        if ( false === get_option( 'ubac_booking_show_meta_step1' ) ) {
            update_option( 'ubac_booking_show_meta_step1', '0' );
        }
    }
}
