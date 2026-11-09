<?php
/**
 * The booking widget's view, for [ubac_booking].
 * Two steps, driven entirely from JS (ubac-booking.js).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ubac_event_title     = get_option( 'ubac_booking_event_title', __( 'Appointment', 'dahuconcept-booking' ) );
$ubac_duration        = get_option( 'ubac_booking_duration', 60 );
$ubac_location        = get_option( 'ubac_booking_location', '' );
$ubac_show_meta_step1 = get_option( 'ubac_booking_show_meta_step1', '0' ) === '1';
?>
<div class="ubac-booking" id="ubac-booking-widget">

    <!-- ============================================ -->
    <!-- Header (hidden on step 1, shown on step 2)  -->
    <!-- Contains: back button + selected date/time  -->
    <!-- ============================================ -->
    <div class="ubac-booking__header" style="display:none;">
        <button type="button" class="ubac-booking__back" aria-label="<?php esc_attr_e( 'Back', 'dahuconcept-booking' ); ?>" style="display:none;">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="ubac-booking__header-recap">
            <span class="ubac-booking__recap-time"></span>
            <span class="ubac-booking__recap-divider">·</span>
            <span class="ubac-booking__recap-date"></span>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- Event recap (title, duration, location)     -->
    <!-- Displayed on step 2 only, if enabled        -->
    <!-- ============================================ -->
    <?php if ( $ubac_show_meta_step1 ) : ?>
    <div class="ubac-booking__selection-recap ubac-booking__step2-only" style="display:none;">
        <span class="ubac-booking__recap-title"><?php echo esc_html( $ubac_event_title ); ?></span>
        <div class="ubac-booking__recap-meta">
            <span class="ubac-booking__recap-meta-item">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" stroke-linecap="round"/></svg>
                <?php echo esc_html( $ubac_duration ); ?> min
            </span>
            <?php if ( $ubac_location ) : ?>
            <span class="ubac-booking__recap-meta-item">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6-7-12a7 7 0 1 1 14 0c0 6-7 12-7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>
                <?php echo esc_html( $ubac_location ); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="ubac-booking__body">

        <!-- ============================================ -->
        <!-- STEP 1: Calendar + time slots               -->
        <!-- ============================================ -->
        <div class="ubac-booking__step ubac-booking__step--1" data-step="1">

            <h3 class="ubac-booking__step-title"><?php esc_html_e( 'Select a date and time', 'dahuconcept-booking' ); ?></h3>

            <div class="ubac-booking__step1-grid">

                <!-- Panel 1: Calendar (collapses once a date is selected) -->
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
                            <!-- Weekday labels rendered by JS using i18n data -->
                        </div>
                        <div class="ubac-booking__cal-days"></div>
                    </div>
                </div>

                <!-- Panel 2: Time slots (hidden until a date is selected) -->
                <div class="ubac-booking__times-panel" style="display:none;">
                    <div class="ubac-booking__times">
                        <div class="ubac-booking__times-date-label"></div>
                        <div class="ubac-booking__times-list"></div>
                    </div>
                </div>

            </div>

        </div>

        <!-- ============================================ -->
        <!-- STEP 2: Contact form                        -->
        <!-- ============================================ -->
        <div class="ubac-booking__step ubac-booking__step--2" data-step="2" style="display:none;">

            <h3 class="ubac-booking__step-title"><?php esc_html_e( 'Your details', 'dahuconcept-booking' ); ?></h3>

            <form class="ubac-booking__form" autocomplete="on">

                <div class="ubac-booking__field" style="position:absolute; left:-9999px; top:-9999px;" aria-hidden="true">
                    <label for="ubac-hp"><?php esc_html_e( 'Do not fill in this field', 'dahuconcept-booking' ); ?></label>
                    <input type="text" id="ubac-hp" name="ubac_hp" tabindex="-1" autocomplete="off">
                </div>

                <div class="ubac-booking__field">
                    <input type="text" id="ubac-name" name="name" placeholder="<?php esc_attr_e( 'Your name*', 'dahuconcept-booking' ); ?>" required autocomplete="name">
                </div>

                <div class="ubac-booking__field">
                    <input type="email" id="ubac-email" name="email" placeholder="<?php esc_attr_e( 'Your email address*', 'dahuconcept-booking' ); ?>" required autocomplete="email">
                </div>

                <div class="ubac-booking__field">
                    <textarea id="ubac-message" name="message" rows="4" placeholder="<?php esc_attr_e( 'Please share anything that will help prepare for our meeting.', 'dahuconcept-booking' ); ?>"></textarea>
                </div>

                <div class="ubac-booking__form-error" role="alert" style="display:none;"></div>

                <button type="submit" class="ubac-booking__submit">
                    <span class="ubac-booking__submit-text"><?php esc_html_e( 'Confirm appointment', 'dahuconcept-booking' ); ?></span>
                    <span class="ubac-booking__spinner" style="display:none;"></span>
                </button>

            </form>

        </div>

        <!-- ============================================ -->
        <!-- STEP 3: Confirmation                        -->
        <!-- ============================================ -->
        <div class="ubac-booking__step ubac-booking__step--3" data-step="3" style="display:none;">
            <div class="ubac-booking__success">
                <div class="ubac-booking__success-icon">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M8 12l3 3 5-6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3><?php esc_html_e( 'Appointment confirmed!', 'dahuconcept-booking' ); ?></h3>
                <p class="ubac-booking__success-recap"></p>
                <p class="ubac-booking__success-note"><?php esc_html_e( 'A confirmation email has been sent to you, with a link to modify or cancel this appointment if needed.', 'dahuconcept-booking' ); ?></p>
            </div>
        </div>

    </div>

</div>
