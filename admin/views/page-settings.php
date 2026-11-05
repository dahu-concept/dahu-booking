<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$dahu_jours_label = array(
    1 => __( 'Monday', 'ubac-booking' ),
    2 => __( 'Tuesday', 'ubac-booking' ),
    3 => __( 'Wednesday', 'ubac-booking' ),
    4 => __( 'Thursday', 'ubac-booking' ),
    5 => __( 'Friday', 'ubac-booking' ),
    6 => __( 'Saturday', 'ubac-booking' ),
    0 => __( 'Sunday', 'ubac-booking' ),
);
$dahu_saved_notice = isset( $_GET['saved'] ) ? sanitize_text_field( wp_unslash( $_GET['saved'] ) ) : '';
?>
<div class="wrap dahu-admin">
    <h1><?php esc_html_e( 'Settings — Dahu Booking', 'ubac-booking' ); ?></h1>

    <?php if ( $dahu_saved_notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'ubac-booking' ); ?></p></div>
    <?php endif; ?>

    <div class="dahu-admin-card dahu-admin-card--shortcode">
        <h2><?php esc_html_e( 'How to display the booking widget', 'ubac-booking' ); ?></h2>
        <p><?php esc_html_e( 'Two equivalent ways to insert the booking calendar on a page:', 'ubac-booking' ); ?></p>
        <ul class="dahu-list">
            <li>
                <strong><?php esc_html_e( 'Shortcode:', 'ubac-booking' ); ?></strong>
                <?php esc_html_e( 'paste', 'ubac-booking' ); ?> <code>[dahu_booking]</code> <?php esc_html_e( 'in any page or text widget.', 'ubac-booking' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Gutenberg block:', 'ubac-booking' ); ?></strong>
                <?php esc_html_e( 'in the page editor, add a block and search for', 'ubac-booking' ); ?> <strong>"Dahu Booking"</strong>.
            </li>
        </ul>
    </div>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="dahu_save_settings">
        <?php wp_nonce_field( 'dahu_save_settings_action' ); ?>

        <table class="form-table">

            <tr>
                <th scope="row"><?php esc_html_e( 'Open days', 'ubac-booking' ); ?></th>
                <td>
                    <?php foreach ( $dahu_jours_label as $dahu_day_value => $dahu_day_label ) : ?>
                        <label class="dahu-day-label">
                            <input type="checkbox" name="open_days[]" value="<?php echo esc_attr( $dahu_day_value ); ?>"
                                <?php checked( in_array( $dahu_day_value, $settings['open_days'], true ) ); ?>>
                            <?php echo esc_html( $dahu_day_label ); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="time_slots"><?php esc_html_e( 'Available time slots', 'ubac-booking' ); ?></label></th>
                <td>
                    <textarea id="time_slots" name="time_slots" rows="8" class="regular-text code"><?php echo esc_textarea( implode( "\n", $settings['time_slots'] ) ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'One time per line, format HH:MM (e.g. 09:00). Remove a line to stop offering that slot.', 'ubac-booking' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="duration"><?php esc_html_e( 'Appointment duration (minutes)', 'ubac-booking' ); ?></label></th>
                <td><input type="number" id="duration" name="duration" value="<?php echo esc_attr( $settings['duration'] ); ?>" min="15" step="15"></td>
            </tr>

            <tr>
                <th scope="row"><label for="event_title"><?php esc_html_e( 'Event title', 'ubac-booking' ); ?></label></th>
                <td><input type="text" id="event_title" name="event_title" value="<?php echo esc_attr( $settings['event_title'] ); ?>" class="regular-text"></td>
            </tr>

            <tr>
                <th scope="row"><label for="location"><?php esc_html_e( 'Meeting location', 'ubac-booking' ); ?></label></th>
                <td><input type="text" id="location" name="location" value="<?php echo esc_attr( $settings['location'] ); ?>" class="regular-text"></td>
            </tr>

            <tr>
                <th scope="row"><label for="admin_email"><?php esc_html_e( 'Notification email (you)', 'ubac-booking' ); ?></label></th>
                <td><input type="email" id="admin_email" name="admin_email" value="<?php echo esc_attr( $settings['admin_email'] ); ?>" class="regular-text"></td>
            </tr>

            <tr>
                <th scope="row"><label for="min_notice_hours"><?php esc_html_e( 'Minimum notice before appointment (hours)', 'ubac-booking' ); ?></label></th>
                <td>
                    <input type="number" id="min_notice_hours" name="min_notice_hours" value="<?php echo esc_attr( $settings['min_notice_hours'] ); ?>" min="0">
                    <p class="description"><?php esc_html_e( 'Prevents last-minute bookings (e.g. 12 = at least 12 hours ahead).', 'ubac-booking' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="max_days_ahead"><?php esc_html_e( 'Bookable period (days in the future)', 'ubac-booking' ); ?></label></th>
                <td><input type="number" id="max_days_ahead" name="max_days_ahead" value="<?php echo esc_attr( $settings['max_days_ahead'] ); ?>" min="1"></td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Show title/duration/location at step 2', 'ubac-booking' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="show_meta_step1" value="1" <?php checked( '1', $settings['show_meta_step1'] ); ?>>
                        <?php
                        printf(
                            /* translators: %s: event title */
                            esc_html__( 'Show "%s", duration and location above the contact form', 'ubac-booking' ),
                            esc_html( $settings['event_title'] )
                        );
                        ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'Unchecked by default. When enabled, this recap appears at step 2 (the form), below the chosen date/time.', 'ubac-booking' ); ?></p>
                </td>
            </tr>

        </table>

        <?php submit_button( __( 'Save settings', 'ubac-booking' ) ); ?>
    </form>

    <div class="dahu-admin-card dahu-admin-card--customizer">
        <h2><?php esc_html_e( 'Customize the widget appearance', 'ubac-booking' ); ?></h2>
        <p><?php esc_html_e( 'To adjust colors, fonts or spacing of the booking widget, use WordPress\'s built-in Additional CSS editor — it is the recommended, site-wide way to add custom styles safely.', 'ubac-booking' ); ?></p>
        <p>
            <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=custom_css' ) ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Open Additional CSS (Customize)', 'ubac-booking' ); ?>
            </a>
        </p>
        <p class="description"><?php esc_html_e( 'WordPress Additional CSS applies site-wide and is preserved across plugin updates.', 'ubac-booking' ); ?></p>
    </div>
</div>
