<?php
/**
 * The plugin's whole front end:
 * - the [ubac_booking] shortcode
 * - the AJAX calls (fetching slots, submitting a booking)
 * - the manage page (reschedule, cancel) reached through a token link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ubac_Booking_Public {

	public function init() {
		add_shortcode( 'ubac_booking', array( $this, 'render_shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX endpoints, reachable signed in or not.
		add_action( 'wp_ajax_ubac_get_available_times', array( $this, 'ajax_get_available_times' ) );
		add_action( 'wp_ajax_nopriv_ubac_get_available_times', array( $this, 'ajax_get_available_times' ) );

		add_action( 'wp_ajax_ubac_create_booking', array( $this, 'ajax_create_booking' ) );
		add_action( 'wp_ajax_nopriv_ubac_create_booking', array( $this, 'ajax_create_booking' ) );

		add_action( 'wp_ajax_ubac_reschedule_booking', array( $this, 'ajax_reschedule_booking' ) );
		add_action( 'wp_ajax_nopriv_ubac_reschedule_booking', array( $this, 'ajax_reschedule_booking' ) );

		add_action( 'wp_ajax_ubac_cancel_booking', array( $this, 'ajax_cancel_booking' ) );
		add_action( 'wp_ajax_nopriv_ubac_cancel_booking', array( $this, 'ajax_cancel_booking' ) );

		// Catches ?ubac_booking_manage=1&token=... to show the manage page.
		add_action( 'template_redirect', array( $this, 'maybe_render_management_page' ) );
	}

	/**
	 * Loads the CSS and JS only on the pages that need them.
	 */
	public function enqueue_assets() {
		global $post;

		$has_shortcode = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ubac_booking' );
		$has_block     = is_a( $post, 'WP_Post' ) && has_block( 'ubac-booking/widget', $post );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a presence test, used only to decide whether this page needs the widget's stylesheet and script.
		$is_manage_page = isset( $_GET['ubac_booking_manage'] );

		if ( ! $has_shortcode && ! $has_block && ! $is_manage_page ) {
			return;
		}

		wp_enqueue_style(
			'ubac-booking-style',
			UBAC_BOOKING_URL . 'assets/css/ubac-booking.css',
			array(),
			UBAC_BOOKING_VERSION
		);

		wp_enqueue_script(
			'ubac-booking-script',
			UBAC_BOOKING_URL . 'assets/js/ubac-booking.js',
			array(),
			UBAC_BOOKING_VERSION,
			true
		);

		wp_localize_script( 'ubac-booking-script', 'ubacBookingData', array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'ubac_booking_nonce' ),
			'eventTitle'    => get_option( 'ubac_booking_event_title', __( 'Appointment', 'dahuconcept-booking' ) ),
			'duration'      => get_option( 'ubac_booking_duration', 60 ),
			'location'      => get_option( 'ubac_booking_location', '' ),
			'showMetaStep1' => get_option( 'ubac_booking_show_meta_step1', '0' ) === '1',
			'maxDaysAhead'  => (int) get_option( 'ubac_booking_max_days_ahead', 60 ),
			'openDays'      => get_option( 'ubac_booking_open_days', array( 1, 2, 3, 4, 5, 6 ) ),
			'i18n'          => array(
				'selectDateTime' => __( 'Select a date and time', 'dahuconcept-booking' ),
				'yourInfo'       => __( 'Your details', 'dahuconcept-booking' ),
				'name'           => __( 'Name', 'dahuconcept-booking' ),
				'email'          => __( 'Email', 'dahuconcept-booking' ),
				'message'        => __( 'Please share anything that will help prepare for our meeting.', 'dahuconcept-booking' ),
				'confirm'        => __( 'Confirm appointment', 'dahuconcept-booking' ),
				'next'           => __( 'Next', 'dahuconcept-booking' ),
				'back'           => __( 'Back', 'dahuconcept-booking' ),
				'noSlots'        => __( 'No available slots on this day.', 'dahuconcept-booking' ),
				'pickDate'       => __( 'Please choose a date from the calendar.', 'dahuconcept-booking' ),
				'loading'        => __( 'Loading…', 'dahuconcept-booking' ),
				'sending'        => __( 'Sending…', 'dahuconcept-booking' ),
				'success'        => __( 'Your appointment is confirmed! A confirmation email has been sent to you.', 'dahuconcept-booking' ),
				'error'          => __( 'An error occurred. Please try again.', 'dahuconcept-booking' ),
				'slotTaken'         => __( 'This slot was just booked by someone else. Please choose another one.', 'dahuconcept-booking' ),
				'cancelSuccess'     => __( 'Your appointment has been cancelled. A confirmation email will be sent to you.', 'dahuconcept-booking' ),
				'rescheduleSuccess' => __( 'Your appointment has been rescheduled. A confirmation email will be sent to you.', 'dahuconcept-booking' ),
				'confirmReschedule' => __( 'Confirm new time slot', 'dahuconcept-booking' ),
				/* Weekday abbreviations for the calendar header (Mon–Sun, starting Monday) */
				'weekdays'       => array(
					__( 'Mon', 'dahuconcept-booking' ),
					__( 'Tue', 'dahuconcept-booking' ),
					__( 'Wed', 'dahuconcept-booking' ),
					__( 'Thu', 'dahuconcept-booking' ),
					__( 'Fri', 'dahuconcept-booking' ),
					__( 'Sat', 'dahuconcept-booking' ),
					__( 'Sun', 'dahuconcept-booking' ),
				),
				/* Full month names for the calendar label (January–December) */
				'months'         => array(
					__( 'January', 'dahuconcept-booking' ),
					__( 'February', 'dahuconcept-booking' ),
					__( 'March', 'dahuconcept-booking' ),
					__( 'April', 'dahuconcept-booking' ),
					__( 'May', 'dahuconcept-booking' ),
					__( 'June', 'dahuconcept-booking' ),
					__( 'July', 'dahuconcept-booking' ),
					__( 'August', 'dahuconcept-booking' ),
					__( 'September', 'dahuconcept-booking' ),
					__( 'October', 'dahuconcept-booking' ),
					__( 'November', 'dahuconcept-booking' ),
					__( 'December', 'dahuconcept-booking' ),
				),
				/* Full day names (Sunday–Saturday, JS getDay() order) for the date label */
				'days'           => array(
					__( 'Sunday', 'dahuconcept-booking' ),
					__( 'Monday', 'dahuconcept-booking' ),
					__( 'Tuesday', 'dahuconcept-booking' ),
					__( 'Wednesday', 'dahuconcept-booking' ),
					__( 'Thursday', 'dahuconcept-booking' ),
					__( 'Friday', 'dahuconcept-booking' ),
					__( 'Saturday', 'dahuconcept-booking' ),
				),
			),
		) );
	}

	/**
	 * Rendu du shortcode [ubac_booking]
	 */
	public function render_shortcode( $atts ) {
		ob_start();
		include UBAC_BOOKING_PATH . 'public/views/booking-widget.php';
		return ob_get_clean();
	}

	// AJAX: the slots available on one date.
	public function ajax_get_available_times() {
		check_ajax_referer( 'ubac_booking_nonce', 'nonce' );

		$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

		if ( ! $this->is_valid_date( $date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date.', 'dahuconcept-booking' ) ) );
		}

		$available = Ubac_Booking_DB::get_available_times_for_date( $date );

		wp_send_json_success( array( 'times' => $available ) );
	}

	// ============================================
	// AJAX: Create a new booking
	// ============================================
	/**
	 * Creates a booking from the public form.
	 *
	 * ubac:public-endpoint -- the visitor is the one booking, so there is no
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
		check_ajax_referer( 'ubac_booking_nonce', 'nonce' );

		// Honeypot anti-spam: hidden field filled only by bots.
		if ( ! empty( $_POST['ubac_hp'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'dahuconcept-booking' ) ) );
		}

		// Simple rate limit by IP (max 1 booking every 20 seconds).
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( $ip ) {
			$rate_key = 'ubac_booking_rate_' . md5( $ip );
			if ( get_transient( $rate_key ) ) {
				wp_send_json_error( array( 'message' => __( 'Please wait a few seconds before trying again.', 'dahuconcept-booking' ) ) );
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
			wp_send_json_error( array( 'message' => __( 'Invalid time slot.', 'dahuconcept-booking' ) ) );
		}

		if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please provide a valid name and email address.', 'dahuconcept-booking' ) ) );
		}

		$result = Ubac_Booking_DB::create_booking( $date, $time, $name, $email, $message );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => 'slot_taken' ) );
		}

		$booking = Ubac_Booking_DB::get_booking_by_id( $result['id'] );
		Ubac_Booking_Mailer::send_booking_emails( $booking );

		wp_send_json_success( array( 'message' => __( 'Booking confirmed.', 'dahuconcept-booking' ) ) );
	}

	// ============================================
	// AJAX: Reschedule an existing booking (from the management page)
	// ============================================
	public function ajax_reschedule_booking() {
		check_ajax_referer( 'ubac_booking_nonce', 'nonce' );

		$token    = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$new_date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
		$new_time = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';

		$booking = Ubac_Booking_DB::get_booking_by_token( $token );
		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => __( 'Booking not found.', 'dahuconcept-booking' ) ) );
		}

		if ( ! $this->is_valid_date( $new_date ) || ! preg_match( '/^\d{2}:\d{2}$/', $new_time ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid time slot.', 'dahuconcept-booking' ) ) );
		}

		$success = Ubac_Booking_DB::reschedule_booking( $token, $new_date, $new_time );

		if ( ! $success ) {
			wp_send_json_error( array( 'message' => 'slot_taken' ) );
		}

		$updated_booking = Ubac_Booking_DB::get_booking_by_token( $token );
		Ubac_Booking_Mailer::send_reschedule_confirmation( $updated_booking );

		wp_send_json_success( array( 'message' => __( 'Appointment rescheduled.', 'dahuconcept-booking' ) ) );
	}

	// ============================================
	// AJAX : Cancel a booking (from the management page)
	// ============================================
	public function ajax_cancel_booking() {
		check_ajax_referer( 'ubac_booking_nonce', 'nonce' );

		$token   = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$booking = Ubac_Booking_DB::get_booking_by_token( $token );

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => 'Booking not found.' ) );
		}

		$cancelled = Ubac_Booking_DB::cancel_booking( $token );

		if ( ! $cancelled ) {
			wp_send_json_error( array( 'message' => 'Could not cancel booking.' ) );
		}

		Ubac_Booking_Mailer::send_cancellation_confirmation( $booking );

		wp_send_json_success( array( 'message' => 'Booking cancelled.' ) );
	}

	// ============================================
	// Management page (reschedule / cancel), reached by the token link
	// ============================================
	public function maybe_render_management_page() {
		// THIS PAGE CANNOT CARRY A NONCE, and that is the design rather than an
		// oversight. It is reached from the link in the visitor's confirmation
		// email, by someone who has no account on the site and may open it days
		// later: a nonce is tied to a logged-in session and expires, so there is
		// nothing to verify against. The token in the link IS the credential —
		// it is generated per booking, unguessable, and the only thing it grants
		// is the right to move or cancel that one appointment.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see just above.
		if ( ! isset( $_GET['ubac_booking_manage'] ) || ! isset( $_GET['token'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see just above.
		$token   = sanitize_text_field( wp_unslash( $_GET['token'] ) );
		$booking = Ubac_Booking_DB::get_booking_by_token( $token );

		get_header();
		include UBAC_BOOKING_PATH . 'public/views/manage-booking.php';
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
