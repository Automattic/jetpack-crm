<?php
/**
 * Confirm Email Page
 *
 * Shown to a logged-in user who has not confirmed their email address yet.
 * Nothing about any contact is shown until they have.
 *
 * @package automattic/jetpack-crm
 */

defined( 'ZEROBSCRM_PATH' ) || exit( 0 );

$jpcrm_user_id = get_current_user_id();
$jpcrm_user    = get_userdata( $jpcrm_user_id );
$jpcrm_notice  = '';
$jpcrm_error   = '';

// Asked for another email?
if (
	isset( $_POST['jpcrm_resend_confirmation'], $_POST['_wpnonce'] )
	&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'jpcrm-resend-email-confirmation' )
) {
	$jpcrm_sent = jpcrm_portal_send_email_confirmation( $jpcrm_user_id );
	if ( is_wp_error( $jpcrm_sent ) ) {
		$jpcrm_error = $jpcrm_sent->get_error_message();
	} else {
		$jpcrm_notice = __( 'We have sent the email again.', 'zero-bs-crm' );
	}
} elseif ( ! jpcrm_portal_pending_confirmation( $jpcrm_user_id ) ) {
	// First visit, or the last link has expired: send one.
	$jpcrm_sent = jpcrm_portal_send_email_confirmation( $jpcrm_user_id );
	if ( is_wp_error( $jpcrm_sent ) ) {
		$jpcrm_error = $jpcrm_sent->get_error_message();
	}
}

// A link that did not work.
if ( isset( $_GET['jpcrm_email_confirmed'] ) && '0' === $_GET['jpcrm_email_confirmed'] && '' === $jpcrm_notice ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$jpcrm_error = __( 'That link did not work. It may have expired, or been opened while logged in to a different account. Please use the newest email we sent you.', 'zero-bs-crm' );
}

?>
<div class="alignwide zbs-site-main zbs-portal-grid no-nav">
	<div class="zbs-portal-wrapper">
		<div class="zbs-portal-content jpcrm-portal-confirm-email">
			<h2><?php esc_html_e( 'Confirm your email address', 'zero-bs-crm' ); ?></h2>

			<?php if ( '' !== $jpcrm_notice ) : ?>
				<div class="zbs_alert"><?php echo esc_html( $jpcrm_notice ); ?></div>
			<?php endif; ?>

			<?php if ( '' !== $jpcrm_error ) : ?>
				<div class="zbs_alert danger"><?php echo esc_html( $jpcrm_error ); ?></div>
			<?php endif; ?>

			<p>
				<?php
				printf(
					/* translators: %s: the user's email address. */
					esc_html__( 'Before we show you your documents, we need to check that %s is your email address.', 'zero-bs-crm' ),
					'<strong>' . esc_html( $jpcrm_user->user_email ) . '</strong>'
				);
				?>
			</p>
			<p><?php esc_html_e( 'We have sent you an email with a link. Open it and you will come straight back here. You may be asked to log in first. You only need to do this once.', 'zero-bs-crm' ); ?></p>
			<p><?php esc_html_e( 'If it has not arrived after a few minutes, check your spam folder, or ask for another.', 'zero-bs-crm' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( 'jpcrm-resend-email-confirmation' ); ?>
				<input type="hidden" name="jpcrm_resend_confirmation" value="1" />
				<button type="submit" class="button"><?php esc_html_e( 'Send the email again', 'zero-bs-crm' ); ?></button>
			</form>

			<p class="jpcrm-portal-confirm-email-help"><?php esc_html_e( 'Not your email address any more? Please contact us and we will update it for you.', 'zero-bs-crm' ); ?></p>
		</div>
	</div>
</div>
