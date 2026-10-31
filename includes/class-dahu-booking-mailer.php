<?php
/**
 * Handles sending all booking-related emails.
 * Uses wp_mail(), which automatically goes through any configured SMTP plugin
 * (FluentSMTP, WP Mail SMTP, or the server's default mailer).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dahu_Booking_Mailer {

    /**
     * Sends both emails for a new booking:
     * client confirmation + admin notification.
     */
    public static function send_booking_emails( $booking ) {
        self::send_client_confirmation( $booking );
        self::send_admin_notification( $booking );
    }

    /**
     * Confirmation email sent to the client, with a link to modify/cancel.
     */
    public static function send_client_confirmation( $booking ) {
        $to        = $booking['client_email'];
        $site_name = get_bloginfo( 'name' );

        /* translators: %s: site name */
        $subject = sprintf( __( 'Your appointment at %s is confirmed', 'dahu-booking' ), $site_name );

        $date_fmt  = self::format_date( $booking['booking_date'] );
        $heure     = substr( $booking['booking_time'], 0, 5 );
        $location  = get_option( 'dahu_booking_location', '' );
        $event     = get_option( 'dahu_booking_event_title', __( 'Appointment', 'dahu-booking' ) );
        $manage_url = self::get_management_url( $booking['token'] );

        /* translators: %s: client name */
        $body  = sprintf( __( 'Hello %s,', 'dahu-booking' ), $booking['client_name'] ) . "\n\n";
        $body .= __( 'Your appointment is confirmed ✅', 'dahu-booking' ) . "\n\n";
        /* translators: %s: event title */
        $body .= sprintf( __( '📌 %s', 'dahu-booking' ), $event ) . "\n";
        /* translators: %s: date */
        $body .= sprintf( __( '📅 %s', 'dahu-booking' ), $date_fmt ) . "\n";
        /* translators: %s: time */
        $body .= sprintf( __( '🕐 %s', 'dahu-booking' ), $heure ) . "\n";
        if ( $location ) {
            /* translators: %s: location */
            $body .= sprintf( __( '📍 %s', 'dahu-booking' ), $location ) . "\n";
        }
        $body .= "\n";
        $body .= __( 'Need to reschedule or cancel?', 'dahu-booking' ) . "\n";
        $body .= $manage_url . "\n\n";
        $body .= __( 'See you soon,', 'dahu-booking' ) . "\n";
        $body .= self::get_signature();

        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

        wp_mail( $to, $subject, $body, $headers );
    }

    /**
     * Notification email sent to the site admin on each new booking.
     */
    public static function send_admin_notification( $booking ) {
        $to      = get_option( 'dahu_booking_admin_email', get_option( 'admin_email' ) );

        /* translators: %s: client name */
        $subject = sprintf( __( 'New appointment: %s', 'dahu-booking' ), $booking['client_name'] );

        $date_fmt = self::format_date( $booking['booking_date'] );
        $heure    = substr( $booking['booking_time'], 0, 5 );

        $body  = __( 'New booking received.', 'dahu-booking' ) . "\n\n";
        /* translators: %s: client name */
        $body .= sprintf( __( '👤 Name: %s', 'dahu-booking' ), $booking['client_name'] ) . "\n";
        /* translators: %s: client email */
        $body .= sprintf( __( '📧 Email: %s', 'dahu-booking' ), $booking['client_email'] ) . "\n";
        /* translators: %s: date */
        $body .= sprintf( __( '📅 Date: %s', 'dahu-booking' ), $date_fmt ) . "\n";
        /* translators: %s: time */
        $body .= sprintf( __( '🕐 Time: %s', 'dahu-booking' ), $heure ) . "\n";
        if ( ! empty( $booking['client_message'] ) ) {
            $body .= "\n" . __( '💬 Message:', 'dahu-booking' ) . "\n" . $booking['client_message'] . "\n";
        }
        $body .= "\n" . __( 'Manage this booking from your WordPress admin (Dahu Booking).', 'dahu-booking' );

        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

        wp_mail( $to, $subject, $body, $headers );
    }

    /**
     * Email sent to the client when their appointment is rescheduled (by themselves).
     */
    public static function send_reschedule_confirmation( $booking ) {
        $to      = $booking['client_email'];
        $subject = __( 'Your appointment has been rescheduled', 'dahu-booking' );

        $date_fmt   = self::format_date( $booking['booking_date'] );
        $heure      = substr( $booking['booking_time'], 0, 5 );
        $manage_url = self::get_management_url( $booking['token'] );

        /* translators: %s: client name */
        $body  = sprintf( __( 'Hello %s,', 'dahu-booking' ), $booking['client_name'] ) . "\n\n";
        $body .= __( 'Your appointment has been successfully rescheduled ✅', 'dahu-booking' ) . "\n\n";
        /* translators: %s: new date */
        $body .= sprintf( __( '📅 New date: %s', 'dahu-booking' ), $date_fmt ) . "\n";
        /* translators: %s: new time */
        $body .= sprintf( __( '🕐 New time: %s', 'dahu-booking' ), $heure ) . "\n\n";
        $body .= __( 'Need to change it again or cancel?', 'dahu-booking' ) . "\n";
        $body .= $manage_url . "\n\n";
        $body .= __( 'See you soon,', 'dahu-booking' ) . "\n";
        $body .= self::get_signature();

        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

        wp_mail( $to, $subject, $body, $headers );

        // Also notify the admin.
        $admin_to = get_option( 'dahu_booking_admin_email', get_option( 'admin_email' ) );
        /* translators: %s: client name */
        $admin_subject = sprintf( __( 'Appointment rescheduled: %s', 'dahu-booking' ), $booking['client_name'] );
        /* translators: %s: client name */
        $admin_body  = sprintf( __( '%s rescheduled their appointment.', 'dahu-booking' ), $booking['client_name'] ) . "\n\n";
        /* translators: %s: new date */
        $admin_body .= sprintf( __( '📅 New date: %s', 'dahu-booking' ), $date_fmt ) . "\n";
        /* translators: %s: new time */
        $admin_body .= sprintf( __( '🕐 New time: %s', 'dahu-booking' ), $heure ) . "\n";
        wp_mail( $admin_to, $admin_subject, $admin_body, $headers );
    }

    /**
     * Email sent to the client when they cancel their appointment.
     */
    public static function send_cancellation_confirmation( $booking ) {
        $to      = $booking['client_email'];
        $subject = __( 'Your appointment has been cancelled', 'dahu-booking' );

        $date_fmt = self::format_date( $booking['booking_date'] );
        $heure    = substr( $booking['booking_time'], 0, 5 );

        /* translators: %s: client name */
        $body  = sprintf( __( 'Hello %s,', 'dahu-booking' ), $booking['client_name'] ) . "\n\n";
        /* translators: %1$s: date, %2$s: time */
        $body .= sprintf( __( 'Your appointment on %1$s at %2$s has been cancelled.', 'dahu-booking' ), $date_fmt, $heure ) . "\n\n";
        $body .= __( 'Feel free to book a new slot whenever you wish.', 'dahu-booking' ) . "\n\n";
        $body .= self::get_signature();

        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

        wp_mail( $to, $subject, $body, $headers );

        // Notify the admin.
        $admin_to = get_option( 'dahu_booking_admin_email', get_option( 'admin_email' ) );
        /* translators: %s: client name */
        $admin_subject = sprintf( __( 'Appointment cancelled: %s', 'dahu-booking' ), $booking['client_name'] );
        /* translators: %1$s: client name, %2$s: date, %3$s: time */
        $admin_body = sprintf(
            __( '%1$s cancelled their appointment on %2$s at %3$s.', 'dahu-booking' ),
            $booking['client_name'],
            $date_fmt,
            $heure
        );
        wp_mail( $admin_to, $admin_subject, $admin_body, $headers );
    }

    // ============================================
    // UTILITIES
    // ============================================

    /**
     * Builds the email signature for client-facing emails,
     * based on the WordPress site name and the notification email
     * configured in the plugin settings.
     */
    private static function get_signature() {
        $site_name   = get_bloginfo( 'name' );
        $admin_email = get_option( 'dahu_booking_admin_email', get_option( 'admin_email' ) );

        return $site_name . "\n" . $admin_email;
    }

    /**
     * Formats a SQL date (YYYY-MM-DD) using the WordPress date format setting.
     */
    private static function format_date( $sql_date ) {
        $timestamp = strtotime( $sql_date );
        return wp_date( get_option( 'date_format' ), $timestamp );
    }

    /**
     * Builds the secure management URL for an appointment (reschedule/cancel).
     */
    private static function get_management_url( $token ) {
        return add_query_arg(
            array(
                'dahu_booking_manage' => '1',
                'token'               => $token,
            ),
            home_url( '/' )
        );
    }
}
