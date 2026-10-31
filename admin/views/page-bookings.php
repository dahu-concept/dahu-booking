<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$dahu_jours = array(
    0 => __( 'Sunday', 'dahu-booking' ),
    1 => __( 'Monday', 'dahu-booking' ),
    2 => __( 'Tuesday', 'dahu-booking' ),
    3 => __( 'Wednesday', 'dahu-booking' ),
    4 => __( 'Thursday', 'dahu-booking' ),
    5 => __( 'Friday', 'dahu-booking' ),
    6 => __( 'Saturday', 'dahu-booking' ),
);
$dahu_cancelled_notice = isset( $_GET['cancelled'] ) ? sanitize_text_field( wp_unslash( $_GET['cancelled'] ) ) : '';
?>
<div class="wrap dahu-admin">
    <h1><?php esc_html_e( 'Bookings', 'dahu-booking' ); ?></h1>

    <?php if ( $dahu_cancelled_notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Booking cancelled. The client has been notified by email.', 'dahu-booking' ); ?></p></div>
    <?php endif; ?>

    <?php if ( empty( $bookings ) ) : ?>
        <p><?php esc_html_e( 'No bookings yet.', 'dahu-booking' ); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Date', 'dahu-booking' ); ?></th>
                    <th><?php esc_html_e( 'Time', 'dahu-booking' ); ?></th>
                    <th><?php esc_html_e( 'Name', 'dahu-booking' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'dahu-booking' ); ?></th>
                    <th><?php esc_html_e( 'Message', 'dahu-booking' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'dahu-booking' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'dahu-booking' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $bookings as $dahu_booking ) :
                    $dahu_date_obj = new DateTime( $dahu_booking['booking_date'] );
                    $dahu_day_name = $dahu_jours[ (int) $dahu_date_obj->format( 'w' ) ];
                    $dahu_date_fr  = $dahu_day_name . ' ' . $dahu_date_obj->format( 'd/m/Y' );
                    $dahu_heure    = substr( $dahu_booking['booking_time'], 0, 5 );
                    $dahu_is_past  = strtotime( $dahu_booking['booking_date'] ) < strtotime( 'today' );
                ?>
                <tr class="<?php echo $dahu_is_past ? 'dahu-row--past' : ''; ?>">
                    <td><?php echo esc_html( ucfirst( $dahu_date_fr ) ); ?></td>
                    <td><?php echo esc_html( $dahu_heure ); ?></td>
                    <td><?php echo esc_html( $dahu_booking['client_name'] ); ?></td>
                    <td><a href="mailto:<?php echo esc_attr( $dahu_booking['client_email'] ); ?>"><?php echo esc_html( $dahu_booking['client_email'] ); ?></a></td>
                    <td><?php echo esc_html( wp_trim_words( $dahu_booking['client_message'], 12 ) ); ?></td>
                    <td>
                        <?php if ( 'confirmed' === $dahu_booking['status'] ) : ?>
                            <span class="dahu-badge dahu-badge--confirmed"><?php esc_html_e( 'Confirmed', 'dahu-booking' ); ?></span>
                        <?php else : ?>
                            <span class="dahu-badge dahu-badge--cancelled"><?php esc_html_e( 'Cancelled', 'dahu-booking' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ( 'confirmed' === $dahu_booking['status'] ) : ?>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="dahu_cancel_booking_admin">
                            <input type="hidden" name="token" value="<?php echo esc_attr( $dahu_booking['token'] ); ?>">
                            <?php wp_nonce_field( 'dahu_cancel_booking_admin_action' ); ?>
                            <button type="submit" class="button button-small"><?php esc_html_e( 'Cancel', 'dahu-booking' ); ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
