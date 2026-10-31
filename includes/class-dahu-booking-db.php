<?php
/**
 * Centralise tous les accès à la base de données pour le plugin.
 * Aucune requête SQL ne doit être écrite ailleurs que dans cette classe.
 *
 * Toutes les requêtes utilisent $wpdb->prepare(), y compris pour les noms
 * de table (placeholder %i), conformément aux recommandations WordPress.
 *
 * Les lectures fréquentes (créneaux réservés/bloqués pour une date) passent
 * par l'object cache WordPress avec une courte durée de vie. Le cache est
 * systématiquement invalidé à chaque écriture pour éviter d'afficher des
 * créneaux obsolètes (ex: déjà réservés par un autre visiteur).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dahu_Booking_DB {

    const CACHE_GROUP = 'dahu_booking';
    const CACHE_TTL    = 30; // secondes : assez court pour rester fiable sur la dispo des créneaux

    /**
     * Retourne le nom complet de la table bookings (avec préfixe WP).
     */
    private static function table_bookings() {
        global $wpdb;
        return $wpdb->prefix . DAHU_BOOKING_TABLE_BOOKINGS;
    }

    /**
     * Retourne le nom complet de la table slots bloqués.
     */
    private static function table_slots() {
        global $wpdb;
        return $wpdb->prefix . DAHU_BOOKING_TABLE_SLOTS;
    }

    // ============================================
    // LECTURE : Disponibilités
    // ============================================

    /**
     * Retourne la liste des créneaux horaires (HH:MM) déjà réservés
     * pour une date donnée (statut 'confirmed' uniquement).
     */
    public static function get_booked_times_for_date( $date ) {
        $cache_key = 'booked_' . $date;
        $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table = self::table_bookings();

        $results = $wpdb->get_col( $wpdb->prepare(
            "SELECT TIME_FORMAT(booking_time, '%%H:%%i') FROM %i
             WHERE booking_date = %s AND status = 'confirmed'",
            $table,
            $date
        ) );

        $results = $results ? $results : array();

        wp_cache_set( $cache_key, $results, self::CACHE_GROUP, self::CACHE_TTL );

        return $results;
    }

    /**
     * Retourne la liste des créneaux horaires bloqués manuellement
     * pour une date donnée.
     */
    public static function get_blocked_times_for_date( $date ) {
        $cache_key = 'blocked_' . $date;
        $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table = self::table_slots();

        $results = $wpdb->get_col( $wpdb->prepare(
            "SELECT TIME_FORMAT(slot_time, '%%H:%%i') FROM %i
             WHERE slot_date = %s AND is_blocked = 1",
            $table,
            $date
        ) );

        $results = $results ? $results : array();

        wp_cache_set( $cache_key, $results, self::CACHE_GROUP, self::CACHE_TTL );

        return $results;
    }

    /**
     * Calcule les créneaux disponibles pour une date donnée,
     * en croisant : jours ouverts, heures proposées, réservations existantes,
     * blocages manuels, et délai minimum de prévenance.
     */
    public static function get_available_times_for_date( $date ) {
        $open_days  = get_option( 'dahu_booking_open_days', array( 1, 2, 3, 4, 5, 6 ) );
        $all_slots  = get_option( 'dahu_booking_time_slots', array() );
        $min_notice = (int) get_option( 'dahu_booking_min_notice_hours', 12 );

        // Vérifie que le jour de la semaine est ouvert
        $day_of_week = (int) wp_date( 'w', strtotime( $date ) );
        if ( ! in_array( $day_of_week, $open_days, true ) ) {
            return array();
        }

        $booked  = self::get_booked_times_for_date( $date );
        $blocked = self::get_blocked_times_for_date( $date );
        $unavailable = array_merge( $booked, $blocked );

        $now = current_time( 'timestamp' );
        $available = array();

        foreach ( $all_slots as $slot ) {
            if ( in_array( $slot, $unavailable, true ) ) {
                continue;
            }

            // Vérifie le délai minimum de prévenance
            $slot_timestamp = strtotime( $date . ' ' . $slot );
            $hours_until_slot = ( $slot_timestamp - $now ) / 3600;

            if ( $hours_until_slot < $min_notice ) {
                continue;
            }

            $available[] = $slot;
        }

        return $available;
    }

    // ============================================
    // ÉCRITURE : Réservations
    // ============================================

    /**
     * Crée une nouvelle réservation. Retourne l'ID créé ou false en cas d'échec.
     * Effectue une double vérification anti-collision (race condition).
     */
    public static function create_booking( $date, $time, $name, $email, $message ) {
        global $wpdb;
        $table = self::table_bookings();

        // Double vérification : le créneau est-il encore libre au moment T ?
        $still_available = self::get_available_times_for_date( $date );
        if ( ! in_array( $time, $still_available, true ) ) {
            return false;
        }

        $token = self::generate_unique_token();

        $inserted = $wpdb->insert(
            $table,
            array(
                'booking_date'    => $date,
                'booking_time'    => $time . ':00',
                'client_name'     => sanitize_text_field( $name ),
                'client_email'    => sanitize_email( $email ),
                'client_message'  => sanitize_textarea_field( $message ),
                'status'          => 'confirmed',
                'token'           => $token,
                'created_at'      => current_time( 'mysql' ),
                'updated_at'      => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            return false;
        }

        self::invalidate_date_cache( $date );

        return array(
            'id'    => $wpdb->insert_id,
            'token' => $token,
        );
    }

    /**
     * Met à jour la date/heure d'une réservation existante (déplacement).
     * Identifiée par son token sécurisé.
     */
    public static function reschedule_booking( $token, $new_date, $new_time ) {
        global $wpdb;
        $table = self::table_bookings();

        // Récupère l'ancienne date pour invalider son cache aussi
        $existing  = self::get_booking_by_token( $token );
        $old_date  = $existing ? $existing['booking_date'] : null;

        // Vérifie que le nouveau créneau est disponible
        $available = self::get_available_times_for_date( $new_date );
        if ( ! in_array( $new_time, $available, true ) ) {
            return false;
        }

        $updated = $wpdb->update(
            $table,
            array(
                'booking_date' => $new_date,
                'booking_time' => $new_time . ':00',
                'updated_at'   => current_time( 'mysql' ),
            ),
            array( 'token' => $token ),
            array( '%s', '%s', '%s' ),
            array( '%s' )
        );

        if ( false !== $updated ) {
            self::invalidate_date_cache( $new_date );
            if ( $old_date ) {
                self::invalidate_date_cache( $old_date );
            }
        }

        return false !== $updated;
    }

    /**
     * Annule une réservation (passe son statut à 'cancelled').
     * Le créneau redevient ainsi disponible.
     */
    public static function cancel_booking( $token ) {
        global $wpdb;
        $table = self::table_bookings();

        $existing = self::get_booking_by_token( $token );

        $updated = $wpdb->update(
            $table,
            array(
                'status'     => 'cancelled',
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'token' => $token ),
            array( '%s', '%s' ),
            array( '%s' )
        );

        if ( false !== $updated && $existing ) {
            self::invalidate_date_cache( $existing['booking_date'] );
        }

        return false !== $updated;
    }

    /**
     * Récupère une réservation par son token sécurisé.
     */
    public static function get_booking_by_token( $token ) {
        global $wpdb;
        $table = self::table_bookings();

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM %i WHERE token = %s",
            $table,
            $token
        ), ARRAY_A );
    }

    /**
     * Récupère une réservation par son ID.
     */
    public static function get_booking_by_id( $id ) {
        global $wpdb;
        $table = self::table_bookings();

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM %i WHERE id = %d",
            $table,
            $id
        ), ARRAY_A );
    }

    /**
     * Liste toutes les réservations à venir (pour l'admin), triées par date.
     */
    public static function get_upcoming_bookings() {
        global $wpdb;
        $table = self::table_bookings();
        $today = current_time( 'Y-m-d' );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM %i
             WHERE booking_date >= %s AND status = 'confirmed'
             ORDER BY booking_date ASC, booking_time ASC",
            $table,
            $today
        ), ARRAY_A );
    }

    /**
     * Liste toutes les réservations (tous statuts, historique inclus).
     */
    public static function get_all_bookings() {
        global $wpdb;
        $table = self::table_bookings();

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM %i ORDER BY booking_date DESC, booking_time DESC",
            $table
        ), ARRAY_A );
    }

    // ============================================
    // ÉCRITURE : Blocage manuel de créneaux
    // ============================================

    /**
     * Bloque un créneau précis (le rend indisponible côté client).
     */
    public static function block_slot( $date, $time, $reason = '' ) {
        global $wpdb;
        $table = self::table_slots();

        $result = $wpdb->replace(
            $table,
            array(
                'slot_date'   => $date,
                'slot_time'   => $time . ':00',
                'is_blocked'  => 1,
                'reason'      => sanitize_text_field( $reason ),
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%d', '%s', '%s' )
        );

        if ( false !== $result ) {
            self::invalidate_date_cache( $date );
        }

        return $result;
    }

    /**
     * Débloque un créneau précédemment bloqué manuellement.
     */
    public static function unblock_slot( $date, $time ) {
        global $wpdb;
        $table = self::table_slots();

        $result = $wpdb->delete(
            $table,
            array(
                'slot_date' => $date,
                'slot_time' => $time . ':00',
            ),
            array( '%s', '%s' )
        );

        if ( false !== $result ) {
            self::invalidate_date_cache( $date );
        }

        return $result;
    }

    /**
     * Liste tous les créneaux bloqués manuellement à venir.
     */
    public static function get_blocked_slots() {
        global $wpdb;
        $table = self::table_slots();
        $today = current_time( 'Y-m-d' );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM %i WHERE slot_date >= %s ORDER BY slot_date ASC, slot_time ASC",
            $table,
            $today
        ), ARRAY_A );
    }

    // ============================================
    // UTILITAIRES
    // ============================================

    /**
     * Génère un token unique et sécurisé pour identifier une réservation
     * sans exposer son ID numérique dans les emails/liens.
     */
    private static function generate_unique_token() {
        return bin2hex( random_bytes( 24 ) ); // 48 caractères hexadécimaux
    }

    /**
     * Invalide le cache des créneaux (réservés + bloqués) pour une date donnée.
     * Appelé après chaque écriture qui modifie la disponibilité de cette date.
     */
    private static function invalidate_date_cache( $date ) {
        wp_cache_delete( 'booked_' . $date, self::CACHE_GROUP );
        wp_cache_delete( 'blocked_' . $date, self::CACHE_GROUP );
    }
}
