<?php
/**
 * Every database access for this plugin goes through here.
 * No SQL query should be written anywhere but in this class.
 *
 * Every query uses $wpdb->prepare(), table names included (the %i
 * placeholder), as WordPress recommends.
 *
 * The frequent reads -- booked and blocked slots for one date -- go
 * through the WordPress object cache with a short lifetime. The cache is
 * invalidated on every write without exception, so that slots another
 * visitor has already taken are never shown as free.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ubac_Booking_DB {

	const CACHE_GROUP = 'ubac_booking';
	const CACHE_TTL    = 30; // Seconds: short enough to stay honest about slot availability.

	/**
	 * The bookings table's full name, WordPress prefix included.
	 */
	private static function table_bookings() {
		global $wpdb;
		return $wpdb->prefix . UBAC_BOOKING_TABLE_BOOKINGS;
	}

	/**
	 * The blocked-slots table's full name, WordPress prefix included.
	 */
	private static function table_slots() {
		global $wpdb;
		return $wpdb->prefix . UBAC_BOOKING_TABLE_SLOTS;
	}

	// READING: availability.

	/**
	 * The times (HH:MM) already booked for one date, counting only
	 * bookings with the 'confirmed' status.
	 */
	public static function get_booked_times_for_date( $date ) {
		$cache_key = 'booked_' . $date;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table = self::table_bookings();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and this read is cached: wp_cache_get above, wp_cache_set below, invalidated by flush_date_cache().
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
	 * The times blocked by hand for one date.
	 */
	public static function get_blocked_times_for_date( $date ) {
		$cache_key = 'blocked_' . $date;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table = self::table_slots();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and this read is cached: wp_cache_get above, wp_cache_set below, invalidated by flush_date_cache().
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
	 * The slots available on one date, crossing open days, the times on
	 * offer, the bookings already made, the slots blocked by hand, and the
	 * shortest notice allowed.
	 */
	public static function get_available_times_for_date( $date ) {
		$open_days  = get_option( 'ubac_booking_open_days', array( 1, 2, 3, 4, 5, 6 ) );
		$all_slots  = get_option( 'ubac_booking_time_slots', array() );
		$min_notice = (int) get_option( 'ubac_booking_min_notice_hours', 12 );

		// Is that day of the week open at all?
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

			// Check the shortest notice allowed.
			$slot_timestamp = strtotime( $date . ' ' . $slot );
			$hours_until_slot = ( $slot_timestamp - $now ) / 3600;

			if ( $hours_until_slot < $min_notice ) {
				continue;
			}

			$available[] = $slot;
		}

		return $available;
	}

	// WRITING: bookings.

	/**
	 * Creates a booking and returns its id, or false on failure.
	 * Checks twice against a collision, since two visitors can reach the
	 * same slot at the same moment.
	 */
	public static function create_booking( $date, $time, $name, $email, $message ) {
		global $wpdb;
		$table = self::table_bookings();

		// Second check: is the slot still free at this very moment?
		$still_available = self::get_available_times_for_date( $date );
		if ( ! in_array( $time, $still_available, true ) ) {
			return false;
		}

		$token = self::generate_unique_token();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and a write is not something to cache; the two date keys are dropped by flush_date_cache() right after.
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
	 * Moves an existing booking to another date and time, found by its
	 * secure token.
	 */
	public static function reschedule_booking( $token, $new_date, $new_time ) {
		global $wpdb;
		$table = self::table_bookings();

		// Read the old date so its cache is invalidated too.
		$existing  = self::get_booking_by_token( $token );
		$old_date  = $existing ? $existing['booking_date'] : null;

		// Is the new slot available?
		$available = self::get_available_times_for_date( $new_date );
		if ( ! in_array( $new_time, $available, true ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and a write is not something to cache; the two date keys are dropped by flush_date_cache() right after.
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
	 * Cancels a booking by setting its status to 'cancelled', which frees
	 * the slot again.
	 */
	public static function cancel_booking( $token ) {
		global $wpdb;
		$table = self::table_bookings();

		$existing = self::get_booking_by_token( $token );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and a write is not something to cache; the two date keys are dropped by flush_date_cache() right after.
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
	 * One booking, found by its secure token.
	 */
	public static function get_booking_by_token( $token ) {
		global $wpdb;
		$table = self::table_bookings();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and this one is deliberately not cached: it is the lookup behind the link in the visitor's email, and a cached row would answer with the appointment as it was before a reschedule or a cancellation.
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM %i WHERE token = %s",
			$table,
			$token
		), ARRAY_A );
	}

	/**
	 * One booking, found by its id.
	 */
	public static function get_booking_by_id( $id ) {
		global $wpdb;
		$table = self::table_bookings();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and this one is deliberately not cached: it is the lookup behind the link in the visitor's email, and a cached row would answer with the appointment as it was before a reschedule or a cancellation.
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM %i WHERE id = %d",
			$table,
			$id
		), ARRAY_A );
	}

	/**
	 * Every booking still to come, for the admin screen, oldest first.
	 */
	public static function get_upcoming_bookings() {
		global $wpdb;
		$table = self::table_bookings();
		$today = current_time( 'Y-m-d' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and one query per view of an admin screen: nothing here is a hot path, and a cache would mostly serve the shop its own bookings one refresh late.
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM %i
             WHERE booking_date >= %s AND status = 'confirmed'
             ORDER BY booking_date ASC, booking_time ASC",
			$table,
			$today
		), ARRAY_A );
	}

	/**
	 * Every booking, whatever its status, history included.
	 */
	public static function get_all_bookings() {
		global $wpdb;
		$table = self::table_bookings();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and one query per view of an admin screen: nothing here is a hot path, and a cache would mostly serve the shop its own bookings one refresh late.
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM %i ORDER BY booking_date DESC, booking_time DESC",
			$table
		), ARRAY_A );
	}

	// WRITING: slots blocked by hand.

	/**
	 * Blocks one precise slot, so a visitor can no longer take it.
	 */
	public static function block_slot( $date, $time, $reason = '' ) {
		global $wpdb;
		$table = self::table_slots();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and a write is not something to cache; the two date keys are dropped by flush_date_cache() right after.
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
	 * Frees a slot that had been blocked by hand.
	 */
	public static function unblock_slot( $date, $time ) {
		global $wpdb;
		$table = self::table_slots();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and a write is not something to cache; the two date keys are dropped by flush_date_cache() right after.
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
	 * Every slot still to come that has been blocked by hand.
	 */
	public static function get_blocked_slots() {
		global $wpdb;
		$table = self::table_slots();
		$today = current_time( 'Y-m-d' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- own table, and one query per view of an admin screen: nothing here is a hot path, and a cache would mostly serve the shop its own bookings one refresh late.
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
	 * A unique, secure token that identifies a booking without putting its
	 * numeric id in emails or links.
	 */
	private static function generate_unique_token() {
		return bin2hex( random_bytes( 24 ) ); // Forty-eight hexadecimal characters.
	}

	/**
	 * Invalidates the slot cache -- booked and blocked alike -- for one
	 * date. Called after every write that changes what is available on
	 * that date.
	 */
	private static function invalidate_date_cache( $date ) {
		wp_cache_delete( 'booked_' . $date, self::CACHE_GROUP );
		wp_cache_delete( 'blocked_' . $date, self::CACHE_GROUP );
	}
}
