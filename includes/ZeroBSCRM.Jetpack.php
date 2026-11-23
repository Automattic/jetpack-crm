<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Jetpack CRM
 * https://jetpackcrm.com
 * V1.20
 *
 * Date: 24th January 2020
 *
 * This file will house any functionality related to Jetpack
 */

/*
======================================================
Breaking Checks ( stops direct access )
======================================================
*/
if ( ! defined( 'ZEROBSCRM_PATH' ) ) {
	exit( 0 );
}

/*
======================================================
/ Breaking Checks
======================================================
*/

/**
 *
 * JETPACK CONTACT FORM CAPTURE => Jetpack CRM
 *
 * This intercepts the jetpack form submission. Capturing the lead in Jetpack CRM.
 * This is the only action that appears to pass the field values in $all_values and
 * oddly named $extra_values which are contact form fields not in all values
 * so...  all values is "almost all values :-)"
 */

add_action( 'grunion_after_feedback_post_inserted', 'zero_bs_crm_capture_jetpack_form', 10, 4 );

/**
 * A value submitted through a Jetpack form, as the text to store on a contact.
 *
 * @param mixed $value      The submitted value. Choice fields submit an array.
 * @param bool  $multi_line Whether to keep line breaks.
 * @return string
 */
function jpcrm_jetpack_form_text_value( $value, $multi_line = false ) {

	if ( is_array( $value ) ) {
		$value = implode( ', ', array_filter( $value, 'is_scalar' ) );
	}

	if ( ! is_scalar( $value ) ) {
		return '';
	}

	return $multi_line ? sanitize_textarea_field( (string) $value ) : sanitize_text_field( (string) $value );
}

/**
 * Creates a ZBS contact from a Jetpack form submission.
 *
 * @param integer $post_id The post id that contains the contact form data.
 * @param array   $all_field_data An array containg the form's Contact_Form_Field objects.
 * @param bool    $is_spam Whether the form submission has been identified as spam.
 * @param array   $entry_values The feedback entry values.
 */
function zero_bs_crm_capture_jetpack_form( $post_id, $all_field_data, $is_spam, $entry_values ) {
	global $zbs;

	if ( $is_spam || ! is_array( $all_field_data ) || empty( $all_field_data ) ) {
		return;
	}

	$first_field = reset( $all_field_data );
	if ( isset( $first_field->form->attributes['jetpackCRM'] ) && ! $first_field->form->attributes['jetpackCRM'] ) {
		// The Jetpack contact form CRM integration toggle is set to off, so bail.
		return;
	}

	$contact_data = array();

	/*
	 * These keys are used by ZBS internally. Don't allow users to set them
	 * using form fields.
	 */
	$restricted_keys = array(
		'externalSources',
		'companies',
		'lastcontacted',
		'created',
		'aliases',
		'id',
		'wpid',
		'owner',
	);

	/*
	 * Try to process the fields using user-specified 'jetpackcrm-' ids.
	 * This is the ideal situation: the user has specified ids with
	 * a 'jetpackcrm-' prefix for each form field, so the fields should map
	 * directly to the CRM contact fields.
	 */
	$jpcrm_field_prefix = 'jetpackcrm-';

	foreach ( (array) $all_field_data as $field_data ) {
		$field_id = $field_data->get_attribute( 'id' );

		if ( str_starts_with( $field_id, $jpcrm_field_prefix ) && isset( $field_data->value ) && $field_data->value !== '' ) {
			$data_key = substr( $field_id, strlen( $jpcrm_field_prefix ) );

			if ( ! in_array( $data_key, $restricted_keys, true ) ) {
				if ( $data_key === 'tags' ) {
					if ( is_array( $field_data->value ) ) {
						$contact_data[ $data_key ] = $field_data->value;
					} else {
						$contact_data[ $data_key ] = explode( ',', $field_data->value );
					}
					$contact_data[ $data_key ] = array_map( 'jpcrm_jetpack_form_text_value', $contact_data[ $data_key ] );
				} elseif ( $field_data->get_attribute( 'type' ) === 'date' ) {
					$contact_data[ $data_key ] = jpcrm_date_str_wp_format_to_uts( $field_data->value, true );
				} else {
					$contact_data[ $data_key ] = jpcrm_jetpack_form_text_value( $field_data->value, $field_data->get_attribute( 'type' ) === 'textarea' );
				}
			}
		}
	}

	if ( empty( $contact_data['email'] ) ) {
		/*
		 * If the field ids aren't prefixed with 'jetpackcrm-', try to get an
		 * email, phone, and name using the field types.
		 */
		foreach ( (array) $all_field_data as $field_data ) {
			if ( 'email' === $field_data->get_attribute( 'type' )
				&& ! isset( $contact_data['email'] )
				&& ! empty( $field_data->value )
			) {
				// Use the first email field that's found.
				$contact_data['email'] = jpcrm_jetpack_form_text_value( $field_data->value );
			}

			// A phone field with the country selector switched on has the type 'phone'.
			if ( in_array( $field_data->get_attribute( 'type' ), array( 'telephone', 'phone' ), true )
				&& ! isset( $contact_data['hometel'] )
				&& ! empty( $field_data->value )
			) {
				// Use the first phone field that's found.
				$contact_data['hometel'] = jpcrm_jetpack_form_text_value( $field_data->value );
			}

			if ( 'name' === $field_data->get_attribute( 'type' )
				&& ! isset( $contact_data['fname'] )
				&& ! empty( $field_data->value )
			) {
				// Use the first name field that's found.
				$name                  = explode( ' ', jpcrm_jetpack_form_text_value( $field_data->value ), 2 );
				$contact_data['fname'] = $name[0];
				if ( ! empty( $name[1] ) ) {
					$contact_data['lname'] = $name[1];
				}
			}
		}
	}

	// If we couldn't find an email, bail.
	if ( empty( $contact_data['email'] ) ) {
		return;
	}

	// The contact is matched on this, and it becomes the ID of its source, so it has
	// to be an email address whichever kind of field it was typed into.
	if ( ! zeroBSCRM_validateEmail( $contact_data['email'] ) ) {
		return;
	}

	// Add the external source.
	$contact_data['externalSources'] = array(
		array(
			'source' => 'jetpack_form',
			'origin' => $zbs->DAL->add_origin_prefix( site_url(), 'domain' ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			'uid'    => $contact_data['email'],
		),
	);

	/*
	 * Get the form entry info for extra meta.
	 */
	$entry_title     = isset( $entry_values['entry_title'] ) ? $entry_values['entry_title'] : null;
	$entry_permalink = isset( $entry_values['entry_permalink'] ) ? $entry_values['entry_permalink'] : null;
	$feedback_id     = isset( $entry_values['feedback_id'] ) ? $entry_values['feedback_id'] : null;

	$extra_meta = array(
		'jp_form_entry_title'     => $entry_title,
		'jp_form_entry_permalink' => $entry_permalink,
		'jp_form_feedback_id'     => $feedback_id,
	);

	$new_user_form_source_short = 'Created from Jetpack Form <i class="fa fa-wpforms"></i>';
	$new_user_form_source_long  = 'User created from the form <span class="zbsEmphasis">' . $entry_title . '</span>,'
		. ' on page: <span class="zbsEmphasis">' . $entry_permalink . '</span>';

	$success_log = array(
		'note_override' =>
			array(
				'type'      => 'Form Filled',
				'shortdesc' => $new_user_form_source_short,
				'longdesc'  => $new_user_form_source_long,
			),
	);

	$existing_user_form_source_short = 'User completed Jetpack form <i class="fa fa-wpforms"></i>';
	$existing_user_form_source_long  = 'Form <span class="zbsEmphasis">' . $entry_title . '</span>,'
		. ' which was filled out from the page: <span class="zbsEmphasis">' . $entry_permalink . '</span>';

	$exists_log = array(
		'type'      => 'Form Filled',
		'shortdesc' => $existing_user_form_source_short,
		'longdesc'  => $existing_user_form_source_long,
	);

	//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$zbs->DAL->contacts->addUpdateContact(
		array(
			'data'                       => $contact_data,
			'automatorPassthrough'       => $success_log,
			'fallBackLog'                => $exists_log,
			'extraMeta'                  => $extra_meta,
			// Anyone can submit the form, against any email address: fill in what an
			// existing contact is missing, and leave what it has.
			'do_not_overwrite_populated' => jpcrm_public_submission_fields_to_protect( $contact_data, 'jetpack_form' ),
			'do_not_update_blanks'       => true,
		)
	);
}
