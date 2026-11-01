<?php
/**
 * Gère toute la partie front-end du plugin :
 * - Le shortcode [dahu_booking]
 * - Les appels AJAX (récupération des créneaux, soumission de réservation)
 * - La page de gestion (modifier/annuler) via lien token
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dahu_Booking_Public {

    public function init() {
        add_shortcode( 'dahu_booking', array( $this, 'render_shortcode' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Endpoints AJAX (accessibles connecté ou non)
        add_action( 'wp_ajax_dahu_get_available_times', array( $this, 'ajax_get_available_times' ) );
        add_action( 'wp_ajax_nopriv_dahu_get_available_times', array( $this, 'ajax_get_available_times' ) );

        add_action( 'wp_ajax_dahu_create_booking', array( $this, 'ajax_create_booking' ) );
        add_action( 'wp_ajax_nopriv_dahu_create_booking', array( $this, 'ajax_create_booking' ) );

        add_action( 'wp_ajax_dahu_reschedule_booking', array( $this, 'ajax_reschedule_booking' ) );
        add_action( 'wp_ajax_nopriv_dahu_reschedule_booking', array( $this, 'ajax_reschedule_booking' ) );

        add_action( 'wp_ajax_dahu_cancel_booking', array( $this, 'ajax_cancel_booking' ) );
        add_action( 'wp_ajax_nopriv_dahu_cancel_booking', array( $this, 'ajax_cancel_booking' ) );

        // Intercepte l'URL ?dahu_booking_manage=1&token=... pour afficher la page de gestion
        add_action( 'template_redirect', array( $this, 'maybe_render_management_page' ) );
    }

    /**
     * Charge le CSS/JS uniquement sur les pages qui en ont besoin.
     */
    public function enqueue_assets() {
        global $post;

        $has_shortcode = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'dahu_booking' );
        $has_block     = is_a( $post, 'WP_Post' ) && has_block( 'dahu-booking/widget', $post );
        $is_manage_page = isset( $_GET['dahu_booking_manage'] );

        if ( ! $has_shortcode && ! $has_block && ! $is_manage_page ) {
            return;
        }

        wp_enqueue_style(
            'dahu-booking-style',
            DAHU_BOOKING_URL . 'assets/css/dahu-booking.css',
            array(),
            DAHU_BOOKING_VERSION
        );

        wp_enqueue_script(
            'dahu-booking-script',
            DAHU_BOOKING_URL . 'assets/js/dahu-booking.js',
            array(),
            DAHU_BOOKING_VERSION,
            true
        );

        wp_localize_script( 'dahu-booking-script', 'dahuBookingData', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'dahu_booking_nonce' ),
            'eventTitle'    => get_option( 'dahu_booking_event_title', __( 'Appointment', 'dahu-booking' ) ),
            'duration'      => get_option( 'dahu_booking_duration', 60 ),
            'location'      => get_option( 'dahu_booking_location', '' ),
            'showMetaStep1' => get_option( 'dahu_booking_show_meta_step1', '0' ) === '1',
            'maxDaysAhead'  => (int) get_option( 'dahu_booking_max_days_ahead', 60 ),
            'openDays'      => get_option( 'dahu_booking_open_days', array( 1, 2, 3, 4, 5, 6 ) ),
            'i18n'          => array(
                'selectDateTime' => __( 'Select a date and time', 'dahu-booking' ),
                'yourInfo'       => __( 'Your details', 'dahu-booking' ),
                'name'           => __( 'Name', 'dahu-booking' ),
                'email'          => __( 'Email', 'dahu-booking' ),
                'message'        => __( 'Please share anything that will help prepare for our meeting.', 'dahu-booking' ),
                'confirm'        => __( 'Confirm appointment', 'dahu-booking' ),
                'next'           => __( 'Next', 'dahu-booking' ),
                'back'           => __( 'Back', 'dahu-booking' ),
                'noSlots'        => __( 'No available slots on this day.', 'dahu-booking' ),
                'pickDate'       => __( 'Please choose a date from the calendar.', 'dahu-booking' ),
                'loading'        => __( 'Loading…', 'dahu-booking' ),
                'sending'        => __( 'Sending…', 'dahu-booking' ),
                'success'        => __( 'Your appointment is confirmed! A confirmation email has been sent to you.', 'dahu-booking' ),
                'error'          => __( 'An error occurred. Please try again.', 'dahu-booking' ),
                'slotTaken'         => __( 'This slot was just booked by someone else. Please choose another one.', 'dahu-booking' ),
                'cancelSuccess'     => __( 'Your appointment has been cancelled. A confirmation email will be sent to you.', 'dahu-booking' ),
                'rescheduleSuccess' => __( 'Your appointment has been rescheduled. A confirmation email will be sent to you.', 'dahu-booking' ),
                'confirmReschedule' => __( 'Confirm new time slot', 'dahu-booking' ),
                /* Weekday abbreviations for the calendar header (Mon–Sun, starting Monday) */
                'weekdays'       => array(
                    __( 'Mon', 'dahu-booking' ),
                    __( 'Tue', 'dahu-booking' ),
                    __( 'Wed', 'dahu-booking' ),
                    __( 'Thu', 'dahu-booking' ),
                    __( 'Fri', 'dahu-booking' ),
                    __( 'Sat', 'dahu-booking' ),
                    __( 'Sun', 'dahu-booking' ),
                ),
                /* Full month names for the calendar label (January–December) */
                'months'         => array(
                    __( 'January', 'dahu-booking' ),
                    __( 'February', 'dahu-booking' ),
                    __( 'March', 'dahu-booking' ),
                    __( 'April', 'dahu-booking' ),
                    __( 'May', 'dahu-booking' ),
                    __( 'June', 'dahu-booking' ),
                    __( 'July', 'dahu-booking' ),
                    __( 'August', 'dahu-booking' ),
                    __( 'September', 'dahu-booking' ),
                    __( 'October', 'dahu-booking' ),
                    __( 'November', 'dahu-booking' ),
                    __( 'December', 'dahu-booking' ),
                ),
                /* Full day names (Sunday–Saturday, JS getDay() order) for the date label */
                'days'           => array(
                    __( 'Sunday', 'dahu-booking' ),
                    __( 'Monday', 'dahu-booking' ),
                    __( 'Tuesday', 'dahu-booking' ),
                    __( 'Wednesday', 'dahu-booking' ),
                    __( 'Thursday', 'dahu-booking' ),
                    __( 'Friday', 'dahu-booking' ),
                    __( 'Saturday', 'dahu-booking' ),
                ),
            ),
        ) );
    }

    /**
     * Rendu du shortcode [dahu_booking]
     */
    public function render_shortcode( $atts ) {
        ob_start();
        include DAHU_BOOKING_PATH . 'public/views/booking-widget.php';
        return ob_get_clean();
    }

    // ============================================
    // AJAX : Récupérer les créneaux disponibles pour une date
    // ============================================
    public function ajax_get_available_times() {
        check_ajax_referer( 'dahu_booking_nonce', 'nonce' );

        $date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

        if ( ! $this->is_valid_date( $date ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date.', 'dahu-booking' ) ) );
        }

        $available = Dahu_Booking_DB::get_available_times_for_date( $date );

        wp_send_json_success( array( 'times' => $available ) );
    }

    // ============================================
    // AJAX: Create a new booking
    // ============================================
    /**
     * Creates a booking from the public form.
     *
     * dahu:public-endpoint -- the visitor is the one booking, so there is no
     * capability to check and no account to check it against. What stands in
     * its place, in order: the nonce below, a honeypot field a human never
     * fills, a rate limit of one booking per IP every twenty seconds, and
     * every value sanitised where it is read. Said here because a reader who
     * finds no current_user_can() is right to look for the reason, and the
     * reason belongs in the code rather than in somebody's memory.
     *
     * @return void
     */
    public function ajax_create_booking() {
        check_ajax_referer( 'dahu_booking_nonce', 'nonce' );

        // Honeypot anti-spam: hidden field filled only by bots.
        if ( ! empty( $_POST['dahu_hp'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'dahu-booking' ) ) );
        }

        // Simple rate limit by IP (max 1 booking every 20 seconds).
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        if ( $ip ) {
            $rate_key = 'dahu_booking_rate_' . md5( $ip );
            if ( get_transient( $rate_key ) ) {
                wp_send_json_error( array( 'message' => __( 'Please wait a few seconds before trying again.', 'dahu-booking' ) ) );
            }
            set_transient( $rate_key, 1, 20 );
        }

        $date    = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
        $time    = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';
        $name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        // Server-side validation (never trust the front-end alone).
        if ( ! $this->is_valid_date( $date ) || ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid time slot.', 'dahu-booking' ) ) );
        }

        if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please provide a valid name and email address.', 'dahu-booking' ) ) );
        }

        $result = Dahu_Booking_DB::create_booking( $date, $time, $name, $email, $message );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'slot_taken' ) );
        }

        $booking = Dahu_Booking_DB::get_booking_by_id( $result['id'] );
        Dahu_Booking_Mailer::send_booking_emails( $booking );

        wp_send_json_success( array( 'message' => __( 'Booking confirmed.', 'dahu-booking' ) ) );
    }

    // ============================================
    // AJAX: Reschedule an existing booking (from the management page)
    // ============================================
    public function ajax_reschedule_booking() {
        check_ajax_referer( 'dahu_booking_nonce', 'nonce' );

        $token    = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
        $new_date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
        $new_time = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';

        $booking = Dahu_Booking_DB::get_booking_by_token( $token );
        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'dahu-booking' ) ) );
        }

        if ( ! $this->is_valid_date( $new_date ) || ! preg_match( '/^\d{2}:\d{2}$/', $new_time ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid time slot.', 'dahu-booking' ) ) );
        }

        $success = Dahu_Booking_DB::reschedule_booking( $token, $new_date, $new_time );

        if ( ! $success ) {
            wp_send_json_error( array( 'message' => 'slot_taken' ) );
        }

        $updated_booking = Dahu_Booking_DB::get_booking_by_token( $token );
        Dahu_Booking_Mailer::send_reschedule_confirmation( $updated_booking );

        wp_send_json_success( array( 'message' => __( 'Appointment rescheduled.', 'dahu-booking' ) ) );
    }

    // ============================================
    // AJAX : Cancel a booking (from the management page)
    // ============================================
    public function ajax_cancel_booking() {
        check_ajax_referer( 'dahu_booking_nonce', 'nonce' );

        $token   = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
        $booking = Dahu_Booking_DB::get_booking_by_token( $token );

        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => 'Booking not found.' ) );
        }

        $cancelled = Dahu_Booking_DB::cancel_booking( $token );

        if ( ! $cancelled ) {
            wp_send_json_error( array( 'message' => 'Could not cancel booking.' ) );
        }

        Dahu_Booking_Mailer::send_cancellation_confirmation( $booking );

        wp_send_json_success( array( 'message' => 'Booking cancelled.' ) );
    }

    // ============================================
    // Page de gestion (modifier / annuler) via lien token
    // ============================================
    public function maybe_render_management_page() {
        if ( ! isset( $_GET['dahu_booking_manage'] ) || ! isset( $_GET['token'] ) ) {
            return;
        }

        $token   = sanitize_text_field( wp_unslash( $_GET['token'] ) );
        $booking = Dahu_Booking_DB::get_booking_by_token( $token );

        get_header();
        include DAHU_BOOKING_PATH . 'public/views/manage-booking.php';
        get_footer();
        exit;
    }

    // ============================================
    // UTILITAIRE
    // ============================================
    private function is_valid_date( $date ) {
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return false;
        }
        $parts = explode( '-', $date );
        return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] );
    }
}
