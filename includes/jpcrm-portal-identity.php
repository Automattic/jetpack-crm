<?php
/**
 * Which contact a WordPress user is, for the Client Portal.
 *
 * A WordPress user acts as a contact only when both of these hold:
 *
 * - the user has confirmed their current account email address, and
 * - a contact has that address as its own email (not as an alias).
 *
 * That contact is then linked to the user (`zbsc_wpid`). The link alone gives
 * nothing: it is also written by syncs and imports that only matched an email.
 *
 * An email address is confirmed by opening a link sent to it while logged in
 * (ours, or WooCommerce's), by the CRM creating the account for the contact, or
 * by an administrator setting it. Changing the address makes it unconfirmed again.
 *
 * @package automattic/jetpack-crm
 */

// Block direct access.
defined( 'ZEROBSCRM_PATH' ) || exit( 0 );

/**
 * User meta: the email address this user last confirmed, lower case.
 */
const JPCRM_PORTAL_CONFIRMED_EMAIL_META = 'jpcrm_portal_confirmed_email';

/**
 * User meta: the confirmation that has been sent and not yet used.
 */
const JPCRM_PORTAL_CONFIRMATION_META = 'jpcrm_portal_email_confirmation';

/**
 * Query argument a confirmation link carries its token in.
 */
const JPCRM_PORTAL_CONFIRMATION_QUERY_ARG = 'jpcrm_confirm_email';

/**
 * An email address in the form it is compared in.
 *
 * @param string $email An email address.
 * @return string
 */
function jpcrm_portal_normalise_email( $email ) {
	return strtolower( trim( (string) $email ) );
}

/**
 * The account email of a user, normalised, or '' if there is no such user.
 *
 * @param int $user_id WordPress user ID.
 * @return string
 */
function jpcrm_portal_user_email( $user_id ) {
	$user = get_userdata( (int) $user_id );

	return $user ? jpcrm_portal_normalise_email( $user->user_email ) : '';
}

/**
 * Whether a user has confirmed the email address their account has now.
 *
 * @param int $user_id WordPress user ID. Defaults to the current user.
 * @return bool
 */
function jpcrm_portal_email_is_confirmed( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$email   = jpcrm_portal_user_email( $user_id );

	if ( '' === $email ) {
		return false;
	}

	return jpcrm_portal_normalise_email( get_user_meta( $user_id, JPCRM_PORTAL_CONFIRMED_EMAIL_META, true ) ) === $email;
}

/**
 * The contact with this address as its own email, not as an alias.
 *
 * @param string $email An email address.
 * @return array|false The contact, or false.
 */
function jpcrm_portal_contact_with_email( $email ) {
	global $zbs;

	$email = jpcrm_portal_normalise_email( $email );
	if ( '' === $email ) {
		return false;
	}

	$contact = $zbs->DAL->contacts->getContact( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		-1,
		array(
			'email'            => $email,
			'withCustomFields' => false,
			'ignoreowner'      => true,
		)
	);

	if ( ! is_array( $contact ) || jpcrm_portal_normalise_email( $contact['email'] ) !== $email ) {
		return false;
	}

	return $contact;
}

/**
 * The contact a WordPress user is, for the Client Portal and anywhere else a
 * logged-in customer acts on their own record.
 *
 * @param int $user_id WordPress user ID. Defaults to the current user.
 * @return int Contact ID, or 0 when the user is not (yet) any contact.
 */
function jpcrm_portal_contact_id_for_user( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();

	if ( $user_id < 1 || ! jpcrm_portal_email_is_confirmed( $user_id ) ) {
		return 0;
	}

	$contact = jpcrm_portal_contact_with_email( jpcrm_portal_user_email( $user_id ) );

	if ( ! $contact ) {
		return 0;
	}

	// The link is kept in step with what has been proved. Syncs and imports also write
	// it, on the strength of an email address alone, and can point it at another user.
	if ( (int) $contact['wpid'] !== $user_id ) {
		global $zbs;

		$zbs->DAL->contacts->addUpdateContactWPID( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			array(
				'id'   => (int) $contact['id'],
				'WPID' => $user_id,
			)
		);
	}

	return (int) $contact['id'];
}

/**
 * Add a line to a contact's activity log.
 *
 * @param int    $contact_id  Contact ID.
 * @param string $type        Log type.
 * @param string $short       Short description.
 * @param string $description Long description.
 * @return void
 */
function jpcrm_portal_identity_log( $contact_id, $type, $short, $description ) {
	global $zbs;

	$zbs->DAL->logs->addUpdateLog( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		array(
			'id'    => -1,
			'owner' => -1,
			'data'  => array(
				'objtype'   => ZBS_TYPE_CONTACT,
				'objid'     => (int) $contact_id,
				'type'      => $type,
				'shortdesc' => $short,
				'longdesc'  => $description,
			),
		)
	);
}

/**
 * Record that a user's current email address is confirmed, and bring their
 * contact into line with it.
 *
 * - A contact that has the confirmed address is linked to the user.
 * - Otherwise, a contact the user was already confirmed for follows them to the
 *   new address.
 * - When both exist, neither is changed: the user becomes the contact that has
 *   the address, and both records get a note for staff.
 *
 * @param int      $user_id            WordPress user ID.
 * @param string[] $also_previously    Further addresses the user's contact may still hold, besides the
 *                                     one they last confirmed, if it is to follow them.
 * @return int The contact the user now is, or 0.
 */
function jpcrm_portal_mark_email_confirmed( $user_id, $also_previously = array() ) {
	global $zbs;

	$user_id = (int) $user_id;
	$email   = jpcrm_portal_user_email( $user_id );

	if ( '' === $email ) {
		return 0;
	}

	// The address they last confirmed comes first: that is the contact they were.
	$previous_emails = array_merge(
		array( get_user_meta( $user_id, JPCRM_PORTAL_CONFIRMED_EMAIL_META, true ) ),
		(array) $also_previously
	);

	update_user_meta( $user_id, JPCRM_PORTAL_CONFIRMED_EMAIL_META, $email );
	delete_user_meta( $user_id, JPCRM_PORTAL_CONFIRMATION_META );

	$contact_with_email = jpcrm_portal_contact_with_email( $email );

	// The contact this user was, under the address they had confirmed before.
	$previous_contact = false;
	$previous_email   = '';
	foreach ( $previous_emails as $candidate_email ) {
		$candidate_email = jpcrm_portal_normalise_email( $candidate_email );
		if ( '' === $candidate_email || $candidate_email === $email ) {
			continue;
		}
		$candidate = jpcrm_portal_contact_with_email( $candidate_email );
		if ( $candidate && (int) $candidate['wpid'] === $user_id ) {
			$previous_contact = $candidate;
			$previous_email   = $candidate_email;
			break;
		}
	}

	if ( $contact_with_email ) {

		if ( (int) $contact_with_email['wpid'] !== $user_id ) {
			$zbs->DAL->contacts->addUpdateContactWPID( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				array(
					'id'   => (int) $contact_with_email['id'],
					'WPID' => $user_id,
				)
			);
		}

		if ( $previous_contact && (int) $previous_contact['id'] !== (int) $contact_with_email['id'] ) {
			$short = __( 'Attempted email change', 'zero-bs-crm' );
			jpcrm_portal_identity_log(
				$previous_contact['id'],
				'contact_change_details_attempt',
				$short,
				/* translators: 1: old email address, 2: new email address, 3: contact ID. */
				sprintf( __( 'The WordPress user for this contact changed their email from `%1$s` to `%2$s` and confirmed it. This contact was not changed, because another contact (#%3$d) already uses that email address. The user now signs in to the Client Portal as that contact.', 'zero-bs-crm' ), $previous_email, $email, (int) $contact_with_email['id'] )
			);
			jpcrm_portal_identity_log(
				$contact_with_email['id'],
				'contact_change_details_attempt',
				$short,
				/* translators: 1: contact ID, 2: old email address, 3: new email address. */
				sprintf( __( 'The WordPress user for another contact (#%1$d) changed their email from `%2$s` to `%3$s`, which is this contact\'s email address, and confirmed it. The user now signs in to the Client Portal as this contact.', 'zero-bs-crm' ), (int) $previous_contact['id'], $previous_email, $email )
			);
		}

		return (int) $contact_with_email['id'];
	}

	if ( $previous_contact ) {

		$zbs->DAL->contacts->update_contact_email( (int) $previous_contact['id'], $email ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		jpcrm_portal_identity_log(
			$previous_contact['id'],
			'contact_changed_details_via_wpprofile',
			__( 'Email changed', 'zero-bs-crm' ),
			/* translators: 1: old email address, 2: new email address. */
			sprintf( __( 'Contact email changed from `%1$s` to `%2$s`', 'zero-bs-crm' ), $previous_email, $email )
		);

		return (int) $previous_contact['id'];
	}

	return 0;
}

/**
 * Where a confirmation link leads, and where the user lands afterwards: the
 * Client Portal, or WooCommerce My Account on a site that only uses that.
 *
 * @return string
 */
function jpcrm_portal_confirmation_url() {

	if ( zeroBSCRM_isExtensionInstalled( 'portal' ) ) {
		$url = zeroBS_portal_link( 'dash' );
	} elseif ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'myaccount' );
	}

	if ( empty( $url ) ) {
		$url = home_url( '/' );
	}

	/**
	 * Filters where Client Portal email confirmation links lead.
	 *
	 * @param string $url A front-end URL on this site.
	 */
	return (string) apply_filters( 'jpcrm_portal_confirmation_url', $url );
}

/**
 * How long a confirmation link works for, in seconds.
 *
 * @return int
 */
function jpcrm_portal_confirmation_lifetime() {
	/**
	 * Filters how long a Client Portal email confirmation link works for.
	 *
	 * @param int $seconds Lifetime in seconds. Default one day.
	 */
	return (int) apply_filters( 'jpcrm_portal_confirmation_lifetime', DAY_IN_SECONDS );
}

/**
 * How long a user has to wait before asking for another confirmation email.
 *
 * @return int
 */
function jpcrm_portal_confirmation_resend_wait() {
	/**
	 * Filters the wait between Client Portal confirmation emails to one user.
	 *
	 * @param int $seconds Wait in seconds. Default one minute.
	 */
	return (int) apply_filters( 'jpcrm_portal_confirmation_resend_wait', MINUTE_IN_SECONDS );
}

/**
 * The confirmation waiting for a user, if it is for their current address and
 * has not expired.
 *
 * @param int $user_id WordPress user ID.
 * @return array|false Keys `hash`, `email`, `expires`, `sent`.
 */
function jpcrm_portal_pending_confirmation( $user_id ) {
	$pending = get_user_meta( (int) $user_id, JPCRM_PORTAL_CONFIRMATION_META, true );

	if (
		! is_array( $pending )
		|| empty( $pending['hash'] )
		|| empty( $pending['email'] )
		|| empty( $pending['expires'] )
		|| (int) $pending['expires'] < time()
		|| $pending['email'] !== jpcrm_portal_user_email( $user_id )
	) {
		return false;
	}

	return $pending;
}

/**
 * Seconds until a user may ask for another confirmation email.
 *
 * @param int $user_id WordPress user ID.
 * @return int 0 when they may ask now.
 */
function jpcrm_portal_confirmation_wait_remaining( $user_id ) {
	$pending = jpcrm_portal_pending_confirmation( $user_id );

	if ( ! $pending || empty( $pending['sent'] ) ) {
		return 0;
	}

	return max( 0, (int) $pending['sent'] + jpcrm_portal_confirmation_resend_wait() - time() );
}

/**
 * Send a user a link to confirm their account email address.
 *
 * @param int $user_id WordPress user ID.
 * @return true|WP_Error
 */
function jpcrm_portal_send_email_confirmation( $user_id ) {
	global $zbs;

	$user_id = (int) $user_id;
	$email   = jpcrm_portal_user_email( $user_id );

	if ( '' === $email || ! zeroBSCRM_validateEmail( $email ) ) {
		return new WP_Error( 'jpcrm_no_email', __( 'This account has no email address to confirm.', 'zero-bs-crm' ) );
	}

	if ( jpcrm_portal_confirmation_wait_remaining( $user_id ) > 0 ) {
		return new WP_Error( 'jpcrm_wait', __( 'An email was sent a moment ago. Please wait before asking for another.', 'zero-bs-crm' ) );
	}

	$token = wp_generate_password( 40, false );

	update_user_meta(
		$user_id,
		JPCRM_PORTAL_CONFIRMATION_META,
		array(
			'hash'    => hash( 'sha256', $token ),
			'email'   => $email,
			'expires' => time() + jpcrm_portal_confirmation_lifetime(),
			'sent'    => time(),
		)
	);

	$link = add_query_arg( JPCRM_PORTAL_CONFIRMATION_QUERY_ARG, rawurlencode( $token ), jpcrm_portal_confirmation_url() );

	$paragraph = '<p style="font-family:sans-serif;font-size:14px;font-weight:normal;margin:0;Margin-bottom:15px;">';
	$title     = __( 'Confirm your email address', 'zero-bs-crm' );
	$body      = $paragraph . esc_html__( 'Please confirm your email address so you can access your details and documents.', 'zero-bs-crm' ) . '</p>'
		. $paragraph . '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Confirm my email address', 'zero-bs-crm' ) . '</a></p>'
		. $paragraph . esc_html__( 'You may be asked to log in first.', 'zero-bs-crm' ) . '</p>'
		. $paragraph . esc_html__( 'The link can only be used once and expires after 24 hours.', 'zero-bs-crm' ) . '</p>'
		. $paragraph . esc_html__( 'If you didn\'t request this, you can safely ignore this email.', 'zero-bs-crm' ) . '</p>';

	// Our standard email wrapper, as the other portal emails use.
	$html      = $body;
	$templated = jpcrm_retrieve_template( 'emails/default-email.html', false );
	if ( ! empty( $templated ) ) {
		$templating            = $zbs->get_templating();
		$html                  = $templating->replace_single_placeholder( 'msg-content', $body, $templated );
		$replacements          = $templating->get_generic_replacements();
		$replacements['title'] = $title;
		$html                  = $templating->replace_placeholders( array( 'global' ), $html, $replacements );
	}

	$sent = zeroBSCRM_mailDelivery_sendMessage(
		-1,
		array(
			'toEmail'  => $email,
			'toName'   => '',
			'subject'  => $title,
			'headers'  => array( 'Content-Type: text/html; charset=UTF-8' ),
			'body'     => $html,
			'textbody' => '',
			'options'  => array(
				'html' => 1,
			),
		)
	);

	if ( ! $sent || ( is_array( $sent ) && isset( $sent[0] ) && false === $sent[0] ) ) {
		return new WP_Error( 'jpcrm_not_sent', __( 'We could not send the email. Please try again later.', 'zero-bs-crm' ) );
	}

	return true;
}

/**
 * Check a confirmation token for a user and, if it is theirs, confirm their
 * email address.
 *
 * @param int    $user_id WordPress user ID.
 * @param string $token   The token from the link.
 * @return bool Whether the address was confirmed.
 */
function jpcrm_portal_confirm_email_with_token( $user_id, $token ) {
	$pending = jpcrm_portal_pending_confirmation( $user_id );

	if ( ! $pending || ! is_string( $token ) || '' === $token ) {
		return false;
	}

	if ( ! hash_equals( $pending['hash'], hash( 'sha256', $token ) ) ) {
		return false;
	}

	jpcrm_portal_mark_email_confirmed( $user_id );

	return true;
}

/**
 * Catch a confirmation link being opened.
 *
 * The link only works for the user it was sent to, so somebody who is not
 * logged in is sent to log in first and brought back.
 *
 * @return void
 */
function jpcrm_portal_catch_email_confirmation() {

	if ( ! isset( $_GET[ JPCRM_PORTAL_CONFIRMATION_QUERY_ARG ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the token is the credential.
		return;
	}

	$token = sanitize_text_field( wp_unslash( $_GET[ JPCRM_PORTAL_CONFIRMATION_QUERY_ARG ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! is_user_logged_in() ) {
		$return_to = add_query_arg( JPCRM_PORTAL_CONFIRMATION_QUERY_ARG, rawurlencode( $token ), jpcrm_portal_confirmation_url() );
		wp_safe_redirect( wp_login_url( $return_to ) );
		exit( 0 );
	}

	$confirmed = jpcrm_portal_confirm_email_with_token( get_current_user_id(), $token );

	nocache_headers();
	wp_safe_redirect( add_query_arg( 'jpcrm_email_confirmed', $confirmed ? '1' : '0', jpcrm_portal_confirmation_url() ) );
	exit( 0 );
}
add_action( 'template_redirect', 'jpcrm_portal_catch_email_confirmation', 1 );

/**
 * Whether the Client Portal should ask the current user to confirm their email
 * address before showing them anything.
 *
 * Staff reach the portal without being a contact, so they are not asked.
 *
 * @return bool
 */
function jpcrm_portal_user_needs_email_confirmation() {

	if ( ! is_user_logged_in() || zeroBSCRM_isZBSAdminOrAdmin() ) {
		return false;
	}

	return ! jpcrm_portal_email_is_confirmed( get_current_user_id() );
}

/**
 * An administrator changing somebody else's email address confirms it.
 *
 * This is how a user who can no longer reach their old address gets back in.
 * It runs before the profile sync, which then finds the contact up to date.
 *
 * @param int     $user_id       WordPress user ID.
 * @param WP_User $old_user_data The user before the update.
 * @return void
 */
function jpcrm_portal_admin_email_change_confirms( $user_id, $old_user_data ) {
	$user_id   = (int) $user_id;
	$old_email = isset( $old_user_data->user_email ) ? jpcrm_portal_normalise_email( $old_user_data->user_email ) : '';
	$new_email = jpcrm_portal_user_email( $user_id );

	if ( '' === $new_email || $old_email === $new_email ) {
		return;
	}

	$actor_id = get_current_user_id();
	if ( $actor_id < 1 || $actor_id === $user_id || ! current_user_can( 'edit_users' ) ) {
		return;
	}

	jpcrm_portal_mark_email_confirmed( $user_id, array( $old_email ) );
}
add_action( 'profile_update', 'jpcrm_portal_admin_email_change_confirms', 5, 2 );

/**
 * WooCommerce confirming a customer's email address confirms it for us too.
 *
 * WooCommerce (11.0+) fires this once the customer has proved the address their
 * account has at that moment, which is the same thing our own link proves.
 *
 * @param int $user_id WordPress user ID.
 * @return void
 */
function jpcrm_portal_woocommerce_email_verified( $user_id ) {
	jpcrm_portal_mark_email_confirmed( (int) $user_id );
}
add_action( 'woocommerce_customer_email_verified', 'jpcrm_portal_woocommerce_email_verified' );
