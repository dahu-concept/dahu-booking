<?php
/**
 * Gère toute l'interface d'administration (wp-admin) du plugin :
 * - Menu "Dahu Booking"
 * - Page "Réservations" (liste, annulation)
 * - Page "Disponibilités" (jours/heures ouverts, blocage de créneaux)
 * - Page "Réglages" (email admin, lieu, titre, délais)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dahu_Booking_Admin {

    public function init() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_post_dahu_save_settings', array( $this, 'handle_save_settings' ) );
        add_action( 'admin_post_dahu_cancel_booking_admin', array( $this, 'handle_cancel_booking' ) );
        add_action( 'admin_post_dahu_block_slot', array( $this, 'handle_block_slot' ) );
        add_action( 'admin_post_dahu_unblock_slot', array( $this, 'handle_unblock_slot' ) );
    }

    public function register_menu() {
        add_menu_page(
            'Dahu Booking',
            'Dahu Booking',
            'manage_options',
            'dahu-booking',
            array( $this, 'render_bookings_page' ),
            'dashicons-calendar-alt',
            26
        );

        add_submenu_page( 'dahu-booking', __( 'Bookings', 'dahu-booking' ), __( 'Bookings', 'dahu-booking' ), 'manage_options', 'dahu-booking', array( $this, 'render_bookings_page' ) );
        add_submenu_page( 'dahu-booking', __( 'Availability', 'dahu-booking' ), __( 'Availability', 'dahu-booking' ), 'manage_options', 'dahu-booking-availability', array( $this, 'render_availability_page' ) );
        add_submenu_page( 'dahu-booking', __( 'Settings', 'dahu-booking' ), __( 'Settings', 'dahu-booking' ), 'manage_options', 'dahu-booking-settings', array( $this, 'render_settings_page' ) );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'dahu-booking' ) === false ) {
            return;
        }
        wp_enqueue_style( 'dahu-booking-admin-style', DAHU_BOOKING_URL . 'assets/css/dahu-booking-admin.css', array(), DAHU_BOOKING_VERSION );
    }

    // ============================================
    // PAGE : Réservations
    // ============================================
    public function render_bookings_page() {
        $bookings = Dahu_Booking_DB::get_all_bookings();
        include DAHU_BOOKING_PATH . 'admin/views/page-bookings.php';
    }

    public function handle_cancel_booking() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied.', 'dahu-booking' ) );
        }
        check_admin_referer( 'dahu_cancel_booking_admin_action' );

        $token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
        if ( $token ) {
            $booking = Dahu_Booking_DB::get_booking_by_token( $token );
            Dahu_Booking_DB::cancel_booking( $token );
            if ( $booking ) {
                Dahu_Booking_Mailer::send_cancellation_confirmation( $booking );
            }
        }

        wp_safe_redirect( admin_url( 'admin.php?page=dahu-booking&cancelled=1' ) );
        exit;
    }

    // ============================================
    // PAGE : Disponibilités
    // ============================================
    public function render_availability_page() {
        $open_days   = get_option( 'dahu_booking_open_days', array( 1, 2, 3, 4, 5, 6 ) );
        $time_slots  = get_option( 'dahu_booking_time_slots', array() );
        $blocked     = Dahu_Booking_DB::get_blocked_slots();
        include DAHU_BOOKING_PATH . 'admin/views/page-availability.php';
    }

    public function handle_block_slot() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied.', 'dahu-booking' ) );
        }
        check_admin_referer( 'dahu_block_slot_action' );

        $date   = isset( $_POST['slot_date'] ) ? sanitize_text_field( wp_unslash( $_POST['slot_date'] ) ) : '';
        $time   = isset( $_POST['slot_time'] ) ? sanitize_text_field( wp_unslash( $_POST['slot_time'] ) ) : '';
        $reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';

        if ( ! self::is_valid_date_format( $date ) || ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=dahu-booking-availability&error=invalid' ) );
            exit;
        }

        Dahu_Booking_DB::block_slot( $date, $time, $reason );

        wp_safe_redirect( admin_url( 'admin.php?page=dahu-booking-availability&blocked=1' ) );
        exit;
    }

    public function handle_unblock_slot() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied.', 'dahu-booking' ) );
        }
        check_admin_referer( 'dahu_unblock_slot_action' );

        $date = isset( $_POST['slot_date'] ) ? sanitize_text_field( wp_unslash( $_POST['slot_date'] ) ) : '';
        $time = isset( $_POST['slot_time'] ) ? sanitize_text_field( wp_unslash( $_POST['slot_time'] ) ) : '';

        if ( ! self::is_valid_date_format( $date ) || ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=dahu-booking-availability&error=invalid' ) );
            exit;
        }

        Dahu_Booking_DB::unblock_slot( $date, $time );

        wp_safe_redirect( admin_url( 'admin.php?page=dahu-booking-availability&unblocked=1' ) );
        exit;
    }

    // ============================================
    // PAGE : Réglages
    // ============================================
    public function render_settings_page() {
        $settings = array(
            'open_days'        => get_option( 'dahu_booking_open_days', array( 1, 2, 3, 4, 5, 6 ) ),
            'time_slots'       => get_option( 'dahu_booking_time_slots', array() ),
            'duration'         => get_option( 'dahu_booking_duration', 60 ),
            'admin_email'      => get_option( 'dahu_booking_admin_email', get_option( 'admin_email' ) ),
            'min_notice_hours' => get_option( 'dahu_booking_min_notice_hours', 12 ),
            'max_days_ahead'   => get_option( 'dahu_booking_max_days_ahead', 60 ),
            'location'         => get_option( 'dahu_booking_location', '' ),
            'event_title'      => get_option( 'dahu_booking_event_title', '' ),
            'show_meta_step1'  => get_option( 'dahu_booking_show_meta_step1', '0' ),
        );
        include DAHU_BOOKING_PATH . 'admin/views/page-settings.php';
    }

    public function handle_save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied.', 'dahu-booking' ) );
        }
        check_admin_referer( 'dahu_save_settings_action' );

        // Jours ouverts (cases à cocher 0-6)
        $open_days = isset( $_POST['open_days'] ) ? array_map( 'intval', wp_unslash( $_POST['open_days'] ) ) : array();
        update_option( 'dahu_booking_open_days', $open_days );

        // Créneaux horaires (un par ligne dans un textarea, format HH:MM)
        $raw_slots = isset( $_POST['time_slots'] ) ? sanitize_textarea_field( wp_unslash( $_POST['time_slots'] ) ) : '';
        $slots = array_filter( array_map( 'trim', explode( "\n", $raw_slots ) ) );
        $valid_slots = array();
        foreach ( $slots as $slot ) {
            if ( preg_match( '/^\d{2}:\d{2}$/', $slot ) ) {
                $valid_slots[] = $slot;
            }
        }
        sort( $valid_slots );
        update_option( 'dahu_booking_time_slots', $valid_slots );

        update_option( 'dahu_booking_duration', isset( $_POST['duration'] ) ? (int) $_POST['duration'] : 60 );
        update_option( 'dahu_booking_admin_email', isset( $_POST['admin_email'] ) ? sanitize_email( wp_unslash( $_POST['admin_email'] ) ) : get_option( 'admin_email' ) );
        update_option( 'dahu_booking_min_notice_hours', isset( $_POST['min_notice_hours'] ) ? (int) $_POST['min_notice_hours'] : 12 );
        update_option( 'dahu_booking_max_days_ahead', isset( $_POST['max_days_ahead'] ) ? (int) $_POST['max_days_ahead'] : 60 );
        update_option( 'dahu_booking_location', isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '' );
        update_option( 'dahu_booking_event_title', isset( $_POST['event_title'] ) ? sanitize_text_field( wp_unslash( $_POST['event_title'] ) ) : '' );
        update_option( 'dahu_booking_show_meta_step1', isset( $_POST['show_meta_step1'] ) ? '1' : '0' );

        wp_safe_redirect( admin_url( 'admin.php?page=dahu-booking-settings&saved=1' ) );
        exit;
    }

    // ============================================
    // UTILITAIRES
    // ============================================
    private static function is_valid_date_format( $date ) {
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return false;
        }
        $parts = explode( '-', $date );
        return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] );
    }

}

