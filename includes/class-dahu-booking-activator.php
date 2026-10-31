<?php
/**
 * Gère la création des tables SQL et des options par défaut
 * lors de l'activation du plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dahu_Booking_Activator {

    /**
     * Fonction appelée à l'activation du plugin.
     */
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // ============================================
        // TABLE 1 : Les réservations (bookings)
        // ============================================
        $table_bookings = $wpdb->prefix . DAHU_BOOKING_TABLE_BOOKINGS;

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

        // ============================================
        // TABLE 2 : Les créneaux bloqués manuellement
        // (permet à Hugo de désactiver un créneau précis,
        // même s'il tombe dans une plage normalement ouverte)
        // ============================================
        $table_slots = $wpdb->prefix . DAHU_BOOKING_TABLE_SLOTS;

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

        // ============================================
        // OPTIONS PAR DÉFAUT (réglages généraux)
        // ============================================

        // Jours ouverts (0 = dimanche ... 6 = samedi). Par défaut : lun-sam.
        if ( false === get_option( 'dahu_booking_open_days' ) ) {
            update_option( 'dahu_booking_open_days', array( 1, 2, 3, 4, 5, 6 ) );
        }

        // Heures proposées par défaut
        if ( false === get_option( 'dahu_booking_time_slots' ) ) {
            update_option( 'dahu_booking_time_slots', array(
                '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'
            ) );
        }

        // Durée d'un rendez-vous en minutes (pour affichage uniquement)
        if ( false === get_option( 'dahu_booking_duration' ) ) {
            update_option( 'dahu_booking_duration', 60 );
        }

        // Email de notification (toi)
        if ( false === get_option( 'dahu_booking_admin_email' ) ) {
            update_option( 'dahu_booking_admin_email', get_option( 'admin_email' ) );
        }

        // Délai minimum avant réservation (en heures) — évite les RDV dans 10 minutes
        if ( false === get_option( 'dahu_booking_min_notice_hours' ) ) {
            update_option( 'dahu_booking_min_notice_hours', 12 );
        }

        // Nombre de jours max dans le futur pour réserver
        if ( false === get_option( 'dahu_booking_max_days_ahead' ) ) {
            update_option( 'dahu_booking_max_days_ahead', 60 );
        }

        // Lieu du rendez-vous (affiché dans le récap)
        if ( false === get_option( 'dahu_booking_location' ) ) {
            update_option( 'dahu_booking_location', '' );
        }

        // Titre de l'événement
        if ( false === get_option( 'dahu_booking_event_title' ) ) {
            update_option( 'dahu_booking_event_title', 'Rendez-vous' );
        }

        // Affichage du titre/meta (durée, lieu) sur l'étape 1
        if ( false === get_option( 'dahu_booking_show_meta_step1' ) ) {
            update_option( 'dahu_booking_show_meta_step1', '0' );
        }
    }
}
