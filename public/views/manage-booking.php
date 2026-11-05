<?php
/**
 * Page displayed when the client clicks the management link
 * received in their email (reschedule or cancel their appointment).
 *
 * $booking and $token are provided by class-dahu-booking-public.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_enqueue_style( 'dahu-booking-style' );
wp_enqueue_script( 'dahu-booking-script' );
?>

<div class="dahu-manage-page">

    <?php if ( ! $booking ) : ?>

        <div class="dahu-manage-page__not-found">
            <h2><?php esc_html_e( 'Appointment not found', 'ubac-booking' ); ?></h2>
            <p>
                <?php
                printf(
                    /* translators: %1$s: email href, %2$s: email address */
                    esc_html__( 'This link is no longer valid. If you think this is an error, please contact us at %1$s.', 'ubac-booking' ),
                    '<a href="mailto:' . esc_attr( get_option( 'dahu_booking_admin_email', get_option( 'admin_email' ) ) ) . '">' . esc_html( get_option( 'dahu_booking_admin_email', get_option( 'admin_email' ) ) ) . '</a>'
                );
                ?>
            </p>
        </div>

    <?php elseif ( 'cancelled' === $booking['status'] ) : ?>

        <div class="dahu-manage-page__cancelled">
            <h2><?php esc_html_e( 'This appointment has already been cancelled', 'ubac-booking' ); ?></h2>
            <p>
                <?php
                printf(
                    /* translators: %s: link to contact page */
                    esc_html__( 'You can book a new slot directly from our %s.', 'ubac-booking' ),
                    '<a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'contact page', 'ubac-booking' ) . '</a>'
                );
                ?>
            </p>
        </div>

    <?php else :
        $dahu_date_obj = new DateTime( $booking['booking_date'] );
        $dahu_heure    = substr( $booking['booking_time'], 0, 5 );
        // Date formatted by WordPress locale (e.g. "Tuesday, June 23, 2026").
        $dahu_date_formatted = wp_date( get_option( 'date_format' ), $dahu_date_obj->getTimestamp() );
    ?>

        <div class="dahu-manage-page__content" id="dahu-manage-widget" data-token="<?php echo esc_attr( $token ); ?>">

            <div class="dahu-manage-page__current">
                <span class="dahu-manage-page__label"><?php esc_html_e( 'Your current appointment', 'ubac-booking' ); ?></span>
                <h2>
                    <?php
                    printf(
                        /* translators: %1$s: date, %2$s: time */
                        esc_html__( '%1$s at %2$s', 'ubac-booking' ),
                        esc_html( $dahu_date_formatted ),
                        esc_html( $dahu_heure )
                    );
                    ?>
                </h2>
                <p><?php echo esc_html( $booking['client_name'] ); ?> · <?php echo esc_html( $booking['client_email'] ); ?></p>
            </div>

            <div class="dahu-manage-page__actions">
                <button type="button" class="dahu-booking-btn dahu-booking-btn--primary" id="dahu-manage-reschedule-btn">
                    <?php esc_html_e( 'Reschedule this appointment', 'ubac-booking' ); ?>
                </button>
                <button type="button" class="dahu-booking-btn dahu-booking-btn--danger" id="dahu-manage-cancel-btn">
                    <?php esc_html_e( 'Cancel this appointment', 'ubac-booking' ); ?>
                </button>
            </div>

            <!-- Rescheduling zone, hidden by default -->
            <div class="dahu-manage-page__reschedule" id="dahu-manage-reschedule-zone" style="display:none;">
                <h3><?php esc_html_e( 'Choose a new time slot', 'ubac-booking' ); ?></h3>
                <div class="dahu-booking" id="dahu-booking-widget-reschedule">
                    <div class="dahu-booking__body">
                        <div class="dahu-booking__step dahu-booking__step--1" data-step="1">
                            <div class="dahu-booking__step1-grid">
                                <div class="dahu-booking__dates-panel">
                                    <div class="dahu-booking__calendar">
                                        <div class="dahu-booking__calendar-nav">
                                            <button type="button" class="dahu-booking__cal-prev" aria-label="<?php esc_attr_e( 'Previous month', 'ubac-booking' ); ?>">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                            <span class="dahu-booking__cal-month-label"></span>
                                            <button type="button" class="dahu-booking__cal-next" aria-label="<?php esc_attr_e( 'Next month', 'ubac-booking' ); ?>">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </div>
                                        <div class="dahu-booking__cal-weekdays">
                                            <!-- Weekday labels rendered by JS -->
                                        </div>
                                        <div class="dahu-booking__cal-days"></div>
                                    </div>
                                </div>
                                <div class="dahu-booking__times-panel" style="display:none;">
                                    <div class="dahu-booking__times">
                                        <div class="dahu-booking__times-date-label"></div>
                                        <div class="dahu-booking__times-list"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="dahu-booking-btn dahu-booking-btn--primary" id="dahu-manage-confirm-reschedule" disabled>
                    <?php esc_html_e( 'Confirm new time slot', 'ubac-booking' ); ?>
                </button>
            </div>

            <!-- Action result -->
            <div class="dahu-manage-page__result" id="dahu-manage-result" style="display:none;"></div>

        </div>

    <?php endif; ?>

</div>
