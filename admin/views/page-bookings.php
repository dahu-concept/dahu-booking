<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ubac_jours = array(
	0 => __( 'Sunday', 'dahuconcept-booking' ),
	1 => __( 'Monday', 'dahuconcept-booking' ),
	2 => __( 'Tuesday', 'dahuconcept-booking' ),
	3 => __( 'Wednesday', 'dahuconcept-booking' ),
	4 => __( 'Thursday', 'dahuconcept-booking' ),
	5 => __( 'Friday', 'dahuconcept-booking' ),
	6 => __( 'Saturday', 'dahuconcept-booking' ),
);
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a redirect flag read back to decide whether to print a notice; it writes nothing, and the worst a forged one can do is show the admin a message.
$ubac_cancelled_notice = isset( $_GET['cancelled'] ) ? sanitize_text_field( wp_unslash( $_GET['cancelled'] ) ) : '';
?>
<div class="wrap ubac-admin">
    <h1><?php esc_html_e( 'Bookings', 'dahuconcept-booking' ); ?></h1>

    <?php if ( $ubac_cancelled_notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Booking cancelled. The client has been notified by email.', 'dahuconcept-booking' ); ?></p></div>
    <?php endif; ?>

    <?php if ( empty( $bookings ) ) : ?>
        <p><?php esc_html_e( 'No bookings yet.', 'dahuconcept-booking' ); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Date', 'dahuconcept-booking' ); ?></th>
                    <th><?php esc_html_e( 'Time', 'dahuconcept-booking' ); ?></th>
                    <th><?php esc_html_e( 'Name', 'dahuconcept-booking' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'dahuconcept-booking' ); ?></th>
                    <th><?php esc_html_e( 'Message', 'dahuconcept-booking' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'dahuconcept-booking' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'dahuconcept-booking' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $bookings as $ubac_booking ) :
					$ubac_date_obj = new DateTime( $ubac_booking['booking_date'] );
					$ubac_day_name = $ubac_jours[ (int) $ubac_date_obj->format( 'w' ) ];
					$ubac_date_fr  = $ubac_day_name . ' ' . $ubac_date_obj->format( 'd/m/Y' );
					$ubac_heure    = substr( $ubac_booking['booking_time'], 0, 5 );
					$ubac_is_past  = strtotime( $ubac_booking['booking_date'] ) < strtotime( 'today' );
				?>
                <tr class="<?php echo $ubac_is_past ? 'ubac-row--past' : ''; ?>">
                    <td><?php echo esc_html( ucfirst( $ubac_date_fr ) ); ?></td>
                    <td><?php echo esc_html( $ubac_heure ); ?></td>
                    <td><?php echo esc_html( $ubac_booking['client_name'] ); ?></td>
                    <td><a href="mailto:<?php echo esc_attr( $ubac_booking['client_email'] ); ?>"><?php echo esc_html( $ubac_booking['client_email'] ); ?></a></td>
                    <td><?php echo esc_html( wp_trim_words( $ubac_booking['client_message'], 12 ) ); ?></td>
                    <td>
                        <?php if ( 'confirmed' === $ubac_booking['status'] ) : ?>
                            <span class="ubac-badge ubac-badge--confirmed"><?php esc_html_e( 'Confirmed', 'dahuconcept-booking' ); ?></span>
                        <?php else : ?>
                            <span class="ubac-badge ubac-badge--cancelled"><?php esc_html_e( 'Cancelled', 'dahuconcept-booking' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ( 'confirmed' === $ubac_booking['status'] ) : ?>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="ubac_cancel_booking_admin">
                            <input type="hidden" name="token" value="<?php echo esc_attr( $ubac_booking['token'] ); ?>">
                            <?php wp_nonce_field( 'ubac_cancel_booking_admin_action' ); ?>
                            <button type="submit" class="button button-small"><?php esc_html_e( 'Cancel', 'dahuconcept-booking' ); ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
