<?php
/**
 * Tests for lead capture submissions clearing contact fields they did not send.
 *
 * A lead capture form collects a name and an email address, and the contact it
 * matches on that address keeps whatever else is already recorded against it.
 * Anything else is data loss triggered by a form anyone can fill in.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use Automattic\Jetpack\CRM\Tests\JPCRM_Lead_Capture_Form;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Test that a form submission does not empty fields it never collected.
 */
class Form_Capture_Blanking_Test extends JPCRM_Base_Integration_TestCase {

	use JPCRM_Lead_Capture_Form;

	/**
	 * The email address both sides of each test share.
	 *
	 * @var string
	 */
	private const CONTACT_EMAIL = 'alex.murphy@example.test';

	/**
	 * Create a contact with details a CRM user has filled in by hand.
	 *
	 * None of these fields appear on a lead capture form.
	 *
	 * @param array $overrides Field overrides.
	 * @return int The new contact ID.
	 */
	private function create_contact_with_details( array $overrides = array() ): int {
		global $zbs;

		$data = $this->generate_contact_data(
			array_merge(
				array(
					'email'    => self::CONTACT_EMAIL,
					'fname'    => 'Alex',
					'lname'    => 'Murphy',
					'addr1'    => '19 Prospect Hill',
					'city'     => 'Cork',
					'postcode' => 'T12 XY45',
					'mobtel'   => '+353 87 000 0000',
					'hometel'  => '+353 21 427 0000',
				),
				$overrides
			)
		);

		$id = $zbs->DAL->contacts->addUpdateContact( array( 'data' => $data ) );

		$this->assertGreaterThan( 0, $id, 'Failed to create the contact under test.' );

		return $id;
	}

	/**
	 * A submission carrying only what a 'zbs_cgrab' form collects.
	 *
	 * @return array
	 */
	private function cgrab_submission(): array {
		return array(
			'zbsc_status' => 'Lead',
			'zbsc_email'  => self::CONTACT_EMAIL,
			'zbsc_fname'  => 'Alex',
			'zbsc_lname'  => 'Murphy',
		);
	}

	/**
	 * Post a submission to the endpoint in the given form style.
	 *
	 * Every style gets the whole payload. Each one reads the fields it collects
	 * and ignores the rest, which is what a form of that style would send.
	 *
	 * @param string $style One of 'zbs_simple', 'zbs_naked', 'zbs_cgrab'.
	 * @return array The decoded JSON response.
	 */
	private function submit_through_endpoint( string $style ): array {
		return $this->submit_lead_capture_form(
			array(
				'zbs_form_id'    => (string) $this->create_lead_capture_form( $style ),
				'zbs_form_style' => $style,
				'zbs_email'      => self::CONTACT_EMAIL,
				'zbs_fname'      => 'Alex',
				'zbs_lname'      => 'Murphy',
				'zbs_notes'      => 'Please get in touch.',
			)
		);
	}

	/**
	 * Assert a submission through the endpoint left the contact's details alone.
	 *
	 * @param string $style One of 'zbs_simple', 'zbs_naked', 'zbs_cgrab'.
	 * @return void
	 */
	private function assert_endpoint_keeps_unsent_fields( string $style ) {
		global $zbs;

		$contact_id = $this->create_contact_with_details();

		$response = $this->submit_through_endpoint( $style );

		$this->assertSame( 'success', $response['code'], 'The endpoint rejected the submission: ' . wp_json_encode( $response ) );

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( '19 Prospect Hill', $contact['addr1'], 'A submission emptied the address.' );
		$this->assertSame( 'Cork', $contact['city'], 'A submission emptied the city.' );
		$this->assertSame( 'T12 XY45', $contact['postcode'], 'A submission emptied the postcode.' );
		$this->assertSame( '+353 87 000 0000', $contact['mobtel'], 'A submission emptied the mobile number.' );
		$this->assertSame( '+353 21 427 0000', $contact['hometel'], 'A submission emptied the telephone number.' );
	}

	/**
	 * Each form style passes its own arguments to the contacts DAL, so each one
	 * has to be submitted for itself. These are the tests that would catch a
	 * style added or edited without the option.
	 *
	 * @testdox A submission to the endpoint keeps unsent fields on a simple form.
	 */
	#[TestDox( 'A submission to the endpoint keeps unsent fields on a simple form.' )]
	public function test_endpoint_keeps_unsent_fields_on_a_simple_form() {
		$this->assert_endpoint_keeps_unsent_fields( 'zbs_simple' );
	}

	/**
	 * @testdox A submission to the endpoint keeps unsent fields on a naked form.
	 */
	#[TestDox( 'A submission to the endpoint keeps unsent fields on a naked form.' )]
	public function test_endpoint_keeps_unsent_fields_on_a_naked_form() {
		$this->assert_endpoint_keeps_unsent_fields( 'zbs_naked' );
	}

	/**
	 * @testdox A submission to the endpoint keeps unsent fields on a content grab form.
	 */
	#[TestDox( 'A submission to the endpoint keeps unsent fields on a content grab form.' )]
	public function test_endpoint_keeps_unsent_fields_on_a_content_grab_form() {
		$this->assert_endpoint_keeps_unsent_fields( 'zbs_cgrab' );
	}

	/**
	 * Where a contact came from is recorded outside the fields, and a limited
	 * field update used to skip it. Leaving blanks alone must not cost us the
	 * record of the form that was filled in.
	 *
	 * @testdox A form submission still records the form as a source of the contact.
	 */
	#[TestDox( 'A form submission still records the form as a source of the contact.' )]
	public function test_submission_records_the_form_as_a_source() {
		global $zbs;

		$contact_id = $this->create_contact_with_details();

		$this->assertSame( $contact_id, $this->submit_lead_form( $this->cgrab_submission() ) );

		$contact = $zbs->DAL->contacts->getContact( $contact_id, array( 'withExternalSources' => true ) );

		$sources = wp_list_pluck( $contact['external_sources'], 'source' );

		$this->assertContains( 'form', $sources, 'A form submission stopped recording where the contact came from.' );
	}

	/**
	 * @testdox A form submission does not clear details the form never collected.
	 */
	#[TestDox( 'A form submission does not clear details the form never collected.' )]
	public function test_submission_does_not_clear_unsent_fields() {
		global $zbs;

		$contact_id = $this->create_contact_with_details();

		$this->submit_lead_form( $this->cgrab_submission() );

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( '19 Prospect Hill', $contact['addr1'], 'A form submission emptied the address.' );
		$this->assertSame( 'Cork', $contact['city'], 'A form submission emptied the city.' );
		$this->assertSame( 'T12 XY45', $contact['postcode'], 'A form submission emptied the postcode.' );
		$this->assertSame( '+353 87 000 0000', $contact['mobtel'], 'A form submission emptied the mobile number.' );
		$this->assertSame( '+353 21 427 0000', $contact['hometel'], 'A form submission emptied the telephone number.' );
	}

	/**
	 * The whole point of lead capture is that a submission still records what it
	 * did collect, and still fills in what is missing.
	 *
	 * @testdox A form submission still fills in details the contact is missing.
	 */
	#[TestDox( 'A form submission still fills in details the contact is missing.' )]
	public function test_submission_still_fills_blank_fields() {
		global $zbs;

		$contact_id = $this->create_contact_with_details(
			array(
				'fname' => '',
				'lname' => '',
			)
		);

		$this->submit_lead_form( $this->cgrab_submission() );

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( 'Alex', $contact['fname'], 'A blank first name should still be filled in from a submission.' );
		$this->assertSame( 'Murphy', $contact['lname'], 'A blank last name should still be filled in from a submission.' );
		$this->assertSame( '19 Prospect Hill', $contact['addr1'], 'A form submission emptied the address.' );
	}

	/**
	 * @testdox A form submission still captures a contact that does not exist yet.
	 */
	#[TestDox( 'A form submission still captures a contact that does not exist yet.' )]
	public function test_submission_still_creates_new_contacts() {
		global $zbs;

		$contact_id = $this->submit_lead_form(
			array(
				'zbsc_status' => 'Lead',
				'zbsc_email'  => 'new.lead@example.test',
				'zbsc_fname'  => 'Sam',
				'zbsc_lname'  => 'Nolan',
			)
		);

		$this->assertGreaterThan( 0, $contact_id, 'A form submission should still capture a brand new lead.' );

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( 'Sam', $contact['fname'], 'A new lead should keep the name it was captured with.' );
		$this->assertSame( 'Lead', $contact['status'], 'A new lead should keep the status it was captured with.' );
	}

	/**
	 * The setting decides this for the API and every other non-manual source.
	 * Forms no longer ask it, so leaving it at the default must not bring the
	 * blanking back.
	 *
	 * @testdox The Overwrite Option setting does not re-enable blanking for forms.
	 */
	#[TestDox( 'The Overwrite Option setting does not re-enable blanking for forms.' )]
	public function test_setting_does_not_re_enable_blanking() {
		global $zbs;

		$zbs->settings->update( 'fieldoverride', -1 );

		$contact_id = $this->create_contact_with_details();

		$this->submit_lead_form( $this->cgrab_submission() );

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( '19 Prospect Hill', $contact['addr1'], 'The default setting brought the blanking back.' );
	}

	/**
	 * @testdox A site can restore the old behaviour through the filter.
	 */
	#[TestDox( 'A site can restore the old behaviour through the filter.' )]
	public function test_filter_can_restore_blanking() {
		global $zbs;

		$contact_id = $this->create_contact_with_details();

		add_filter( 'jpcrm_form_capture_do_not_update_blanks', '__return_false' );

		try {
			$this->submit_lead_form( $this->cgrab_submission() );
		} finally {
			remove_filter( 'jpcrm_form_capture_do_not_update_blanks', '__return_false' );
		}

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( '', $contact['addr1'], 'The filter should be able to restore the old behaviour.' );
	}

	/**
	 * @testdox The submitted email address is passed to the filter.
	 */
	#[TestDox( 'The submitted email address is passed to the filter.' )]
	public function test_filter_receives_the_submitted_email() {

		$seen = null;

		$callback = static function ( $do_not_update_blanks, $submitted_email ) use ( &$seen ) {
			$seen = $submitted_email;
			return $do_not_update_blanks;
		};

		add_filter( 'jpcrm_form_capture_do_not_update_blanks', $callback, 10, 2 );

		try {
			jpcrm_form_capture_do_not_update_blanks( self::CONTACT_EMAIL );
		} finally {
			remove_filter( 'jpcrm_form_capture_do_not_update_blanks', $callback, 10 );
		}

		$this->assertSame( self::CONTACT_EMAIL, $seen, 'The filter should be given the address the form was submitted with.' );
	}
}
