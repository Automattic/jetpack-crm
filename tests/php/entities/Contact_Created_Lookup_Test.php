<?php
/**
 * Tests for looking a contact's creation time up by email address.
 *
 * Woo Sync uses this to tell a contact that was already in the CRM apart from one its
 * own import created moments earlier, so that a first import does not leave everybody
 * holding the address from their oldest order.
 *
 * The lookup asks `getContact()` for a limited field set, which returns the raw row
 * through `lazyTidyGeneric()` rather than `tidy_contact()`. That strips the `zbsc_`
 * prefix, so the caller reads `created` rather than `zbsc_created`. Getting that key
 * wrong fails silently: the comparison simply never matches and the protection quietly
 * stops applying. Hence this test.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Test the limited-field lookup Woo Sync relies on.
 */
class Contact_Created_Lookup_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * The fields Woo Sync asks for.
	 *
	 * @var string[]
	 */
	private const LOOKUP_FIELDS = array( 'ID', 'zbsc_created' );

	/**
	 * @testdox A contact's creation time can be looked up by email address.
	 */
	#[TestDox( 'A contact\'s creation time can be looked up by email address.' )]
	public function test_created_is_returned_for_an_email_lookup() {
		global $zbs;

		$before = time();

		$contact_id = $zbs->DAL->contacts->addUpdateContact(
			array(
				'data' => $this->generate_contact_data( array( 'email' => 'lookup@example.test' ) ),
			)
		);

		$this->assertGreaterThan( 0, $contact_id, 'Failed to create the contact under test.' );

		$contact = $zbs->DAL->contacts->getContact(
			-1,
			array(
				'email'            => 'lookup@example.test',
				'fields'           => self::LOOKUP_FIELDS,
				'withCustomFields' => false,
			)
		);

		$this->assertIsArray( $contact, 'The email lookup should resolve to a contact.' );
		$this->assertSame( $contact_id, (int) $contact['id'], 'The lookup resolved to the wrong contact.' );

		$this->assertArrayHasKey( 'created', $contact, 'A limited field lookup should return `created`, not `zbsc_created`.' );
		$this->assertGreaterThanOrEqual( $before, (int) $contact['created'], 'The creation time should be a usable timestamp.' );
		$this->assertLessThanOrEqual( time(), (int) $contact['created'], 'The creation time should be a usable timestamp.' );
	}

	/**
	 * @testdox An email address with no contact behind it looks up to nothing.
	 */
	#[TestDox( 'An email address with no contact behind it looks up to nothing.' )]
	public function test_unknown_email_returns_nothing_usable() {
		global $zbs;

		$contact = $zbs->DAL->contacts->getContact(
			-1,
			array(
				'email'            => 'nobody@example.test',
				'fields'           => self::LOOKUP_FIELDS,
				'withCustomFields' => false,
			)
		);

		$this->assertEmpty( $contact, 'An unknown email address should not resolve to a contact.' );
	}

	/**
	 * @testdox A contact created before a given moment is distinguishable from one created after it.
	 */
	#[TestDox( 'A contact created before a given moment is distinguishable from one created after it.' )]
	public function test_created_can_be_compared_against_an_import_start() {
		global $zbs;

		// Someone already in the CRM, tidied by hand.
		$existing_id = $zbs->DAL->contacts->addUpdateContact(
			array(
				'data' => $this->generate_contact_data( array( 'email' => 'already.here@example.test' ) ),
			)
		);

		$this->assertGreaterThan( 0, $existing_id );

		// An import starts a little later.
		$import_started = time() + 1;

		// ...and creates a contact of its own as it works through order history.
		$imported_id = $zbs->DAL->contacts->addUpdateContact(
			array(
				'data' => $this->generate_contact_data(
					array(
						'email'   => 'made.by.the.import@example.test',
						'created' => $import_started + 1,
					)
				),
			)
		);

		$this->assertGreaterThan( 0, $imported_id );

		$existing = $zbs->DAL->contacts->getContact( -1, array( 'email' => 'already.here@example.test', 'fields' => self::LOOKUP_FIELDS ) );
		$imported = $zbs->DAL->contacts->getContact( -1, array( 'email' => 'made.by.the.import@example.test', 'fields' => self::LOOKUP_FIELDS ) );

		$this->assertLessThan( $import_started, (int) $existing['created'], 'A contact already in the CRM should predate the import.' );
		$this->assertGreaterThanOrEqual( $import_started, (int) $imported['created'], 'A contact the import created should not predate it.' );
	}
}
