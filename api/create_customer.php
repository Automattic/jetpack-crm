<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Jetpack CRM
 * https://jetpackcrm.com
 *
 * @package automattic/jetpack-crm
 */

if ( ! defined( 'ZEROBSCRM_PATH' ) ) {
	exit( 0 );
}

global $zbs;

$json_params  = file_get_contents( 'php://input' );
$new_customer = json_decode( $json_params, true );

if ( ! is_array( $new_customer ) ) {
	wp_send_json(
		array(
			'error'   => true,
			'message' => 'Invalid JSON data',
		),
		200,
		JSON_UNESCAPED_SLASHES
	);
}

$contact_id   = isset( $new_customer['id'] ) ? (int) $new_customer['id'] : -1;
$email        = isset( $new_customer['email'] ) ? sanitize_text_field( $new_customer['email'] ) : '';
$assign       = isset( $new_customer['assign'] ) ? (int) $new_customer['assign'] : -1;
$sticky       = empty( $new_customer['sticky'] );
$sticky_stat  = isset( $new_customer['stickystat'] ) ? sanitize_text_field( $new_customer['stickystat'] ) : 'Customer';
$contact_tags = jpcrm_api_sanitize_tags( $new_customer['tags'] ?? false );

// Resolve the contact before preparing fields so omitted values can be preserved.
$existing_id = -1;
if ( $contact_id > 0 ) {
	$existing_id = (int) $zbs->DAL->contacts->getContact( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$contact_id,
		array(
			'withCustomFields' => false,
			'ignoreowner'      => true,
			'onlyID'           => true,
		)
	);
}
if ( $existing_id < 1 && ! empty( $email ) && zeroBSCRM_validateEmail( $email ) ) {
	$existing_id = (int) zeroBS_getCustomerIDWithEmail( $email );
}

$existing_contact = false;
if ( $existing_id > 0 ) {
	$existing_contact = $zbs->DAL->contacts->getContact(
		$existing_id,
		array(
			'ignoreowner' => true,
		)
	);
}

$update_args = jpcrm_api_prepare_contact_fields( $new_customer, $existing_contact );
if ( $existing_id > 0 ) {
	$update_args['id'] = $existing_id;
}

$email = isset( $update_args['zbsc_email'] ) ? sanitize_text_field( $update_args['zbsc_email'] ) : '';

// Preserve a sticky status, otherwise only default status for a new contact.
if ( $existing_id > 0 && $sticky && is_array( $existing_contact ) && $sticky_stat === $existing_contact['status'] ) {
	$update_args['zbsc_status'] = $sticky_stat;
} elseif ( $existing_id < 1 && empty( $update_args['zbsc_status'] ) ) {
	$update_args['zbsc_status'] = zeroBSCRM_getSetting( 'defaultstatus' );
}

if ( ! empty( $contact_tags ) ) {
	$update_args['tags'] = $contact_tags;
}

// The integration's full-row update resets ownership, so restore it when omitted.
if ( ! array_key_exists( 'assign', $new_customer ) && is_array( $existing_contact ) && isset( $existing_contact['owner'] ) ) {
	$assign = (int) $existing_contact['owner'];
}

$existing_user_api_source_short = __( 'Updated by API Action', 'zero-bs-crm' ) . ' <i class="fa fa-random"></i>';
$existing_user_api_source_long  = __( 'API Action fired to update contact', 'zero-bs-crm' );
$new_user_api_source_short = __( 'Created from API Action', 'zero-bs-crm' ) . ' <i class="fa fa-random"></i>';
$new_user_api_source_long  = __( 'API Action fired to create contact', 'zero-bs-crm' );

$external_api_name = jpcrm_api_process_external_api_name();
if ( $external_api_name !== false ) {
	$existing_user_api_source_short = sprintf(
		// Translators: %s is a dynamic service name invoking the API.
		__( 'Updated by %s (API)', 'zero-bs-crm' ) . ' <i class="fa fa-random"></i>',
		$external_api_name
	);
	$existing_user_api_source_long = sprintf(
		// Translators: %s is a dynamic service name invoking the API.
		__( '%s fired an API Action to update this contact', 'zero-bs-crm' ),
		$external_api_name
	);
	$new_user_api_source_short = sprintf(
		// Translators: %s is a dynamic service name invoking the API.
		__( 'Created by %s (API)', 'zero-bs-crm' ) . ' <i class="fa fa-random"></i>',
		$external_api_name
	);
	$new_user_api_source_long = sprintf(
		// Translators: %s is a dynamic service name invoking the API.
		__( '%s fired an API Action to create this contact', 'zero-bs-crm' ),
		$external_api_name
	);
}

$fallback_log = array(
	'type'      => 'API Action',
	'shortdesc' => $existing_user_api_source_short,
	'longdesc'  => $existing_user_api_source_long,
);

$internal_automator_override = array(
	'note_override' => array(
		'type'      => 'API Action',
		'shortdesc' => $new_user_api_source_short,
		'longdesc'  => $new_user_api_source_long,
	),
);

$valid_email = ! empty( $email ) && zeroBSCRM_validateEmail( $email );
if ( $valid_email || $existing_id > 0 ) {
	// The integration requires an external ID even when the contact was found by ID.
	$external_id = $valid_email ? $email : (string) $existing_id;
	$new_contact = zeroBS_integrations_addOrUpdateCustomer(
		'api',
		$external_id,
		$update_args,
		'',
		$fallback_log,
		false,
		$internal_automator_override
	);

	if ( $new_contact > 0 && $assign > 0 ) {
		zeroBS_setOwner( $new_contact, $assign, ZBS_TYPE_CONTACT );
	}

	if ( $new_contact > 0 ) {
		$return_params = $new_customer;
		$return_params['id'] = $new_contact;
		wp_send_json( $return_params, 200, JSON_UNESCAPED_SLASHES );
	}

	wp_send_json( array( 'error' => 100 ), 200, JSON_UNESCAPED_SLASHES );
}

wp_send_json( array( 'errors' => 1 ), 200, JSON_UNESCAPED_SLASHES );
