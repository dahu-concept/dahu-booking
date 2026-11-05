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
$dahu_blocked_notice   = isset( $_GET['blocked'] ) ? sanitize_text_field( wp_unslash( $_GET['blocked'] ) ) : '';
$dahu_unblocked_notice = isset( $_GET['unblocked'] ) ? sanitize_text_field( wp_unslash( $_GET['unblocked'] ) ) : '';
?>
<div class="wrap dahu-admin">
    <h1><?php esc_html_e( 'Availability', 'ubac-booking' ); ?></h1>

    <?php if ( $dahu_blocked_notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Slot blocked.', 'ubac-booking' ); ?></p></div>
    <?php endif; ?>
    <?php if ( $dahu_unblocked_notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Slot unblocked — available again.', 'ubac-booking' ); ?></p></div>
    <?php endif; ?>

    <div class="dahu-admin-grid">

        <div class="dahu-admin-card">
            <h2><?php esc_html_e( 'Your regular schedule', 'ubac-booking' ); ?></h2>
            <p>
                <?php
                printf(
                    /* translators: %s: link to Settings page */
                    esc_html__( 'Your default open days and times are configured on the %s page.', 'ubac-booking' ),
                    '<a href="' . esc_url( admin_url( 'admin.php?page=dahu-booking-settings' ) ) . '">' . esc_html__( 'Settings', 'ubac-booking' ) . '</a>'
                );
                ?>
            </p>
            <p><strong><?php esc_html_e( 'Currently open days:', 'ubac-booking' ); ?></strong>
                <?php
                $dahu_day_labels = array();
                foreach ( $open_days as $dahu_day ) {
                    $dahu_day_labels[] = $dahu_jours_label[ $dahu_day ];
                }
                echo esc_html( implode( ', ', $dahu_day_labels ) );
                ?>
            </p>
            <p><strong><?php esc_html_e( 'Available time slots:', 'ubac-booking' ); ?></strong> <?php echo esc_html( implode( ', ', $time_slots ) ); ?></p>
        </div>

        <div class="dahu-admin-card">
            <h2><?php esc_html_e( 'Block a one-off slot', 'ubac-booking' ); ?></h2>
            <p><?php esc_html_e( 'Useful for a personal appointment, holiday, or exceptional unavailability — without touching your regular schedule.', 'ubac-booking' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="dahu_block_slot">
                <?php wp_nonce_field( 'dahu_block_slot_action' ); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="slot_date"><?php esc_html_e( 'Date', 'ubac-booking' ); ?></label></th>
                        <td><input type="date" id="slot_date" name="slot_date" required></td>
                    </tr>
                    <tr>
                        <th><label for="slot_time"><?php esc_html_e( 'Time', 'ubac-booking' ); ?></label></th>
                        <td>
                            <select id="slot_time" name="slot_time" required>
                                <?php foreach ( $time_slots as $dahu_slot_option ) : ?>
                                    <option value="<?php echo esc_attr( $dahu_slot_option ); ?>"><?php echo esc_html( $dahu_slot_option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="reason"><?php esc_html_e( 'Reason (optional)', 'ubac-booking' ); ?></label></th>
                        <td>
                            <input type="text" id="reason" name="reason" class="regular-text"
                                placeholder="<?php esc_attr_e( 'e.g. Personal appointment', 'ubac-booking' ); ?>">
                        </td>
                    </tr>
                </table>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Block this slot', 'ubac-booking' ); ?></button>
            </form>
        </div>

        <div class="dahu-admin-card">
            <h2><?php esc_html_e( 'Currently blocked slots', 'ubac-booking' ); ?></h2>
            <?php if ( empty( $blocked ) ) : ?>
                <p><?php esc_html_e( 'No manually blocked slots at the moment.', 'ubac-booking' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'ubac-booking' ); ?></th>
                            <th><?php esc_html_e( 'Time', 'ubac-booking' ); ?></th>
                            <th><?php esc_html_e( 'Reason', 'ubac-booking' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'ubac-booking' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $blocked as $dahu_blocked_slot ) :
                            $dahu_slot_date_obj = new DateTime( $dahu_blocked_slot['slot_date'] );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $dahu_slot_date_obj->format( 'd/m/Y' ) ); ?></td>
                            <td><?php echo esc_html( substr( $dahu_blocked_slot['slot_time'], 0, 5 ) ); ?></td>
                            <td><?php echo esc_html( $dahu_blocked_slot['reason'] ?: '—' ); ?></td>
                            <td>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                    <input type="hidden" name="action" value="dahu_unblock_slot">
                                    <input type="hidden" name="slot_date" value="<?php echo esc_attr( $dahu_blocked_slot['slot_date'] ); ?>">
                                    <input type="hidden" name="slot_time" value="<?php echo esc_attr( substr( $dahu_blocked_slot['slot_time'], 0, 5 ) ); ?>">
                                    <?php wp_nonce_field( 'dahu_unblock_slot_action' ); ?>
                                    <button type="submit" class="button button-small"><?php esc_html_e( 'Unblock', 'ubac-booking' ); ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>
