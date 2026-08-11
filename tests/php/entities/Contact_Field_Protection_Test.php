<?php
/**
 * Tests for the `do_not_overwrite_populated` option on contact add/update.
 *
 * Contacts are identified by email address. A caller that hands
 * `addUpdateContact()` a set of fields without a contact ID therefore updates
 * whichever contact already holds that email.
 *
 * That is what we want when the details come from someone who has signed in,
 * or from a CRM user. It is less obviously right when they come from a
 * checkout or a form that anyone can fill in, where the email address is
 * typed alongside everything else. `do_not_overwrite_populated` lets those
 * callers name the fields that should be filled in when blank and otherwise
 * left as they are.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Test the populated-field protection used for self-reported contact details.
 */
class Contact_Field_Protection_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Fields a self-reported source may fill in but not replace.
	 *
	 * Mirrors the list Woo Sync passes for guest orders.
	 *
	 * @var string[]
	 */
	private const PROTECTED_FIELDS = array(
		'fname',
		'lname',
		'addr1',
		'addr2',
		'city',
		'county',
		'postcode',
		'country',
		'hometel',
	);

	/**
	 * The email address both sides of each test share.
	 *
	 * @var string
	 */
	private const CONTACT_EMAIL = 'alex.murphy@example.test';

	/**
	 * Create a contact holding details a CRM user has already tidied up.
	 *
	 * @param array $overrides Field overrides.
	 * @return int The new contact ID.
	 */
	private function create_tidied_contact( array $overrides = array() ): int {
		global $zbs;

		$data = $this->generate_contact_data(
			array_merge(
				array(
					'email'    => self::CONTACT_EMAIL,
					'fname'    => 'Alex',
					'lname'    => 'Murphy',
					'addr1'    => '19 Prospect Hill',
					'hometel'  => '+353 21 427 0000',
					'postcode' => 'T12 XY45',
				),
				$overrides
			)
		);

		$id = $zbs->DAL->contacts->addUpdateContact( array( 'data' => $data ) );

		$this->assertGreaterThan( 0, $id, 'Failed to create the contact under test.' );

		return $id;
	}

	/**
	 * Details typed into a checkout or form against the same email address.
	 *
	 * @return array
	 */
	private function self_reported_data(): array {
		return array(
			'email'    => self::CONTACT_EMAIL,
			'fname'    => 'alex',
			'lname'    => 'murph',
			'addr1'    => '66 Sundays Well Road',
			'hometel'  => '+353 21 427 0001',
			'postcode' => 'T23 AB12',
		);
	}

	/**
	 * @testdox Self-reported details do not replace fields that are already filled in.
	 */
	#[TestDox( 'Self-reported details do not replace fields that are already filled in.' )]
	public function test_populated_fields_are_not_overwritten() {
		global $zbs;

		$contact_id = $this->create_tidied_contact();

		$returned_id = $zbs->DAL->contacts->addUpdateContact(
			array(
				'data'                       => $this->self_reported_data(),
				'do_not_update_blanks'       => true,
				'do_not_overwrite_populated' => self::PROTECTED_FIELDS,
			)
		);

		// Matching on email still resolves to the same record. That part is intentional.
		$this->assertSame( $contact_id, (int) $returned_id, 'Expected the email match to resolve to the existing contact.' );

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( 'Alex', $contact['fname'], 'First name was replaced.' );
		$this->assertSame( 'Murphy', $contact['lname'], 'Last name was replaced.' );
		$this->assertSame( '19 Prospect Hill', $contact['addr1'], 'Address was replaced.' );
		$this->assertSame( '+353 21 427 0000', $contact['hometel'], 'Telephone number was replaced.' );
		$this->assertSame( 'T12 XY45', $contact['postcode'], 'Postcode was replaced.' );
	}

	/**
	 * @testdox Self-reported details still fill in fields that are empty.
	 */
	#[TestDox( 'Self-reported details still fill in fields that are empty.' )]
	public function test_empty_fields_are_still_filled() {
		global $zbs;

		// Nothing on record for the second address line or the city.
		$contact_id = $this->create_tidied_contact(
			array(
				'addr2' => '',
				'city'  => '',
			)
		);

		$zbs->DAL->contacts->addUpdateContact(
			array(
				'data'                       => array_merge(
					$this->self_reported_data(),
					array(
						'addr2' => 'Flat 4',
						'city'  => 'Cork',
					)
				),
				'do_not_update_blanks'       => true,
				'do_not_overwrite_populated' => self::PROTECTED_FIELDS,
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( 'Flat 4', $contact['addr2'], 'An empty field should still be filled in from an order or form.' );
		$this->assertSame( 'Cork', $contact['city'], 'An empty field should still be filled in from an order or form.' );
	}

	/**
	 * @testdox Fields outside the protected list are still updated.
	 */
	#[TestDox( 'Fields outside the protected list are still updated.' )]
	public function test_unprotected_fields_are_still_updated() {
		global $zbs;

		$contact_id = $this->create_tidied_contact( array( 'mobtel' => '33333333' ) );

		$zbs->DAL->contacts->addUpdateContact(
			array(
				'data'                       => array_merge(
					$this->self_reported_data(),
					array( 'mobtel' => '44444444' )
				),
				'do_not_update_blanks'       => true,
				// Note: `mobtel` is deliberately absent from this list.
				'do_not_overwrite_populated' => self::PROTECTED_FIELDS,
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( '44444444', $contact['mobtel'], 'Only the named fields should be protected.' );
	}

	/**
	 * @testdox A brand new contact is created with all the details supplied.
	 */
	#[TestDox( 'A brand new contact is created with all the details supplied.' )]
	public function test_new_contact_is_unaffected() {
		global $zbs;

		$contact_id = $zbs->DAL->contacts->addUpdateContact(
			array(
				'data'                       => array(
					'email' => 'first.time.buyer@example.test',
					'fname' => 'Robin',
					'lname' => 'Kelly',
					'addr1' => '1 Barrack Street',
				),
				'do_not_update_blanks'       => true,
				'do_not_overwrite_populated' => self::PROTECTED_FIELDS,
			)
		);

		$this->assertGreaterThan( 0, $contact_id, 'A new contact should still be created.' );

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( 'Robin', $contact['fname'], 'A new contact should keep the details it was created with.' );
		$this->assertSame( '1 Barrack Street', $contact['addr1'], 'A new contact should keep the details it was created with.' );
	}

	/**
	 * Submit through the same helper, with the same arguments, the lead capture
	 * endpoint uses for a 'zbs_cgrab' form.
	 *
	 * @param array $fields Contact fields, `zbsc_` prefixed as the endpoint passes them.
	 * @return int|false The contact ID.
	 */
	private function submit_lead_form( array $fields ) {
		return zeroBS_integrations_addOrUpdateCustomer(
			'form',
			$fields['zbsc_email'],
			$fields,
			'',
			'none',
			false,
			false,
			'update',
			'zbsc_',
			array( 'fname', 'lname', 'status' )
		);
	}

	/**
	 * @testdox A form submission does not replace a name that is already filled in.
	 */
	#[TestDox( 'A form submission does not replace a name that is already filled in.' )]
	public function test_form_submission_does_not_replace_populated_name() {
		global $zbs;

		$contact_id = $this->create_tidied_contact();

		$this->submit_lead_form(
			array(
				'zbsc_status' => 'Lead',
				'zbsc_email'  => self::CONTACT_EMAIL,
				'zbsc_fname'  => 'alex',
				'zbsc_lname'  => 'murph',
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( 'Alex', $contact['fname'], 'A form submission replaced a first name already on record.' );
		$this->assertSame( 'Murphy', $contact['lname'], 'A form submission replaced a last name already on record.' );
	}

	/**
	 * @testdox A form submission does not reset the status of an existing contact.
	 */
	#[TestDox( 'A form submission does not reset the status of an existing contact.' )]
	public function test_form_submission_does_not_reset_status() {
		global $zbs;

		$contact_id = $this->create_tidied_contact( array( 'status' => 'Customer' ) );

		$this->submit_lead_form(
			array(
				'zbsc_status' => 'Lead',
				'zbsc_email'  => self::CONTACT_EMAIL,
				'zbsc_fname'  => 'Alex',
				'zbsc_lname'  => 'Murphy',
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( 'Customer', $contact['status'], 'A form submission moved an existing customer back to a lead.' );
	}

	/**
	 * @testdox A form submission still captures a contact that does not exist yet.
	 */
	#[TestDox( 'A form submission still captures a contact that does not exist yet.' )]
	public function test_form_submission_still_creates_new_contacts() {
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
	 * @testdox Callers that do not pass the option are unaffected.
	 */
	#[TestDox( 'Callers that do not pass the option are unaffected.' )]
	public function test_protection_is_opt_in() {
		global $zbs;

		$contact_id = $this->create_tidied_contact();

		$zbs->DAL->contacts->addUpdateContact(
			array(
				'data'                 => $this->self_reported_data(),
				'do_not_update_blanks' => true,
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( 'alex', $contact['fname'], 'Callers that do not opt in should behave as they did before.' );
	}
}
