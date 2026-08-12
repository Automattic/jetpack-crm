<?php
/**
 * Tests for what an update does to the user a contact is assigned to.
 *
 * `addUpdateContact()` takes an owner, defaulting to -1. Most callers never
 * pass one, because most of them are updating fields and have no opinion about
 * who the contact belongs to. The full update path writes every column, so
 * that -1 used to land on the record and unassign it.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Test that an update leaves the owner alone unless it is given one.
 */
class Contact_Ownership_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Create a user who is allowed to own a contact.
	 *
	 * `addUpdateContact()` drops an owner who cannot, so a test that skipped
	 * this would be asserting against -1 either way.
	 *
	 * @return int The new user ID.
	 */
	private function create_contact_owner(): int {
		$owner_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->assertTrue( user_can( $owner_id, 'admin_zerobs_usr' ), 'The owner under test cannot own a contact.' );

		return $owner_id;
	}

	/**
	 * An update that says nothing about ownership is the common case: the
	 * client portal saving a profile, an integration syncing an address.
	 *
	 * @testdox An update that does not name an owner leaves the contact assigned.
	 */
	#[TestDox( 'An update that does not name an owner leaves the contact assigned.' )]
	public function test_update_without_an_owner_keeps_the_owner() {
		global $zbs;

		$owner_id = $this->create_contact_owner();

		$contact_id = $zbs->DAL->contacts->addUpdateContact(
			array(
				'owner' => $owner_id,
				'data'  => $this->generate_contact_data( array( 'email' => 'alex.murphy@example.test' ) ),
			)
		);

		$zbs->DAL->contacts->addUpdateContact(
			array(
				'id'   => $contact_id,
				'data' => $this->generate_contact_data(
					array(
						'email' => 'alex.murphy@example.test',
						'city'  => 'Cork',
					)
				),
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( $owner_id, (int) $contact['owner'], 'An update unassigned the contact.' );
		$this->assertSame( 'Cork', $contact['city'], 'The update did not go through.' );
	}

	/**
	 * @testdox An update that names an owner still assigns the contact.
	 */
	#[TestDox( 'An update that names an owner still assigns the contact.' )]
	public function test_update_with_an_owner_still_assigns() {
		global $zbs;

		$first_owner_id  = $this->create_contact_owner();
		$second_owner_id = $this->create_contact_owner();

		$contact_id = $zbs->DAL->contacts->addUpdateContact(
			array(
				'owner' => $first_owner_id,
				'data'  => $this->generate_contact_data( array( 'email' => 'alex.murphy@example.test' ) ),
			)
		);

		$zbs->DAL->contacts->addUpdateContact(
			array(
				'id'    => $contact_id,
				'owner' => $second_owner_id,
				'data'  => $this->generate_contact_data( array( 'email' => 'alex.murphy@example.test' ) ),
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( $second_owner_id, (int) $contact['owner'], 'An update did not hand the contact over.' );
	}

	/**
	 * The column is written on insert whatever happens, and -1 is what an
	 * unowned contact holds.
	 *
	 * @testdox A new contact created without an owner is unowned.
	 */
	#[TestDox( 'A new contact created without an owner is unowned.' )]
	public function test_new_contact_without_an_owner_is_unowned() {
		global $zbs;

		$contact_id = $zbs->DAL->contacts->addUpdateContact(
			array(
				'data' => $this->generate_contact_data( array( 'email' => 'sam.nolan@example.test' ) ),
			)
		);

		$this->assertGreaterThan( 0, $contact_id, 'The contact was not created.' );

		$contact = $zbs->DAL->contacts->getContact( $contact_id );

		$this->assertSame( -1, (int) $contact['owner'], 'A contact created without an owner should be unowned.' );
	}
}
