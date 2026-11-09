<?php
/**
 * Page displayed when the client clicks the management link
 * received in their email (reschedule or cancel their appointment).
 *
 * $booking and $token are provided by class-ubac-booking-public.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_style( 'ubac-booking-style' );
wp_enqueue_script( 'ubac-booking-script' );
?>

<div class="ubac-manage-page">

    <?php if ( ! $booking ) : ?>

        <div class="ubac-manage-page__not-found">
            <h2><?php esc_html_e( 'Appointment not found', 'dahuconcept-booking' ); ?></h2>
            <p>
                <?php
				printf(
					/* translators: %1$s: email href, %2$s: email address */
					esc_html__( 'This link is no longer valid. If you think this is an error, please contact us at %1$s.', 'dahuconcept-booking' ),
					'<a href="mailto:' . esc_attr( get_option( 'ubac_booking_admin_email', get_option( 'admin_email' ) ) ) . '">' . esc_html( get_option( 'ubac_booking_admin_email', get_option( 'admin_email' ) ) ) . '</a>'
				);
				?>
            </p>
        </div>

    <?php elseif ( 'cancelled' === $booking['status'] ) : ?>

        <div class="ubac-manage-page__cancelled">
            <h2><?php esc_html_e( 'This appointment has already been cancelled', 'dahuconcept-booking' ); ?></h2>
            <p>
                <?php
				printf(
					/* translators: %s: link to contact page */
					esc_html__( 'You can book a new slot directly from our %s.', 'dahuconcept-booking' ),
					'<a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'contact page', 'dahuconcept-booking' ) . '</a>'
				);
				?>
            </p>
        </div>

    <?php else :
		$ubac_date_obj = new DateTime( $booking['booking_date'] );
		$ubac_heure    = substr( $booking['booking_time'], 0, 5 );
		// Date formatted by WordPress locale (e.g. "Tuesday, June 23, 2026").
		$ubac_date_formatted = wp_date( get_option( 'date_format' ), $ubac_date_obj->getTimestamp() );
	?>

        <div class="ubac-manage-page__content" id="ubac-manage-widget" data-token="<?php echo esc_attr( $token ); ?>">

            <div class="ubac-manage-page__current">
                <span class="ubac-manage-page__label"><?php esc_html_e( 'Your current appointment', 'dahuconcept-booking' ); ?></span>
                <h2>
                    <?php
					printf(
						/* translators: %1$s: date, %2$s: time */
						esc_html__( '%1$s at %2$s', 'dahuconcept-booking' ),
						esc_html( $ubac_date_formatted ),
						esc_html( $ubac_heure )
					);
					?>
                </h2>
                <p><?php echo esc_html( $booking['client_name'] ); ?> · <?php echo esc_html( $booking['client_email'] ); ?></p>
            </div>

            <div class="ubac-manage-page__actions">
                <button type="button" class="ubac-booking-btn ubac-booking-btn--primary" id="ubac-manage-reschedule-btn">
                    <?php esc_html_e( 'Reschedule this appointment', 'dahuconcept-booking' ); ?>
                </button>
                <button type="button" class="ubac-booking-btn ubac-booking-btn--danger" id="ubac-manage-cancel-btn">
                    <?php esc_html_e( 'Cancel this appointment', 'dahuconcept-booking' ); ?>
                </button>
            </div>

            <!-- Rescheduling zone, hidden by default -->
            <div class="ubac-manage-page__reschedule" id="ubac-manage-reschedule-zone" style="display:none;">
                <h3><?php esc_html_e( 'Choose a new time slot', 'dahuconcept-booking' ); ?></h3>
                <div class="ubac-booking" id="ubac-booking-widget-reschedule">
                    <div class="ubac-booking__body">
                        <div class="ubac-booking__step ubac-booking__step--1" data-step="1">
                            <div class="ubac-booking__step1-grid">
                                <div class="ubac-booking__dates-panel">
                                    <div class="ubac-booking__calendar">
                                        <div class="ubac-booking__calendar-nav">
                                            <button type="button" class="ubac-booking__cal-prev" aria-label="<?php esc_attr_e( 'Previous month', 'dahuconcept-booking' ); ?>">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                            <span class="ubac-booking__cal-month-label"></span>
                                            <button type="button" class="ubac-booking__cal-next" aria-label="<?php esc_attr_e( 'Next month', 'dahuconcept-booking' ); ?>">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </div>
                                        <div class="ubac-booking__cal-weekdays">
                                            <!-- Weekday labels rendered by JS -->
                                        </div>
                                        <div class="ubac-booking__cal-days"></div>
                                    </div>
                                </div>
                                <div class="ubac-booking__times-panel" style="display:none;">
                                    <div class="ubac-booking__times">
                                        <div class="ubac-booking__times-date-label"></div>
                                        <div class="ubac-booking__times-list"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="ubac-booking-btn ubac-booking-btn--primary" id="ubac-manage-confirm-reschedule" disabled>
                    <?php esc_html_e( 'Confirm new time slot', 'dahuconcept-booking' ); ?>
                </button>
            </div>

            <!-- Action result -->
            <div class="ubac-manage-page__result" id="ubac-manage-result" style="display:none;"></div>

        </div>

    <?php endif; ?>

</div>
