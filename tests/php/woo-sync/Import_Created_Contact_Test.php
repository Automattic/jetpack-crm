<?php
/**
 * Tests for telling the contacts a Woo Sync first import created apart from the
 * contacts that were already in the CRM when it started.
 *
 * A first import works through order history oldest first, creating contacts as it
 * goes. Without a way to tell the two apart, the field protection added for guest
 * checkouts locks a contact into the address from its oldest order.
 *
 * The obvious signal, the contact's creation time, does not work: Woo Sync sets
 * `zbsc_created` from the order's date, so a contact the import creates from a 2019
 * order carries a 2019 creation time and reads as though it had been in the CRM for
 * years. `test_woo_sync_stores_the_order_date_as_the_creation_time` pins that, since
 * it is the reason the import marks the contacts it creates instead.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Woo_Sync\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use Automattic\JetpackCRM\Woo_Sync_Background_Sync_Job;
use PHPUnit\Framework\Attributes\TestDox;

require_once JPCRM_WOO_SYNC_ROOT_PATH . 'includes/class-woo-sync-background-sync-job.php';

/**
 * Test the contact bookkeeping a first import relies on.
 */
class Import_Created_Contact_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Sync site key standing in for the store being imported.
	 *
	 * @var string
	 */
	private const STORE = 'shop.example.test';

	/**
	 * A second store connected to the same CRM.
	 *
	 * @var string
	 */
	private const OTHER_STORE = 'other-shop.example.test';

	/**
	 * A sync job for a given store.
	 *
	 * The job is handed its site info directly, so nothing here needs WooCommerce or
	 * the Woo Sync module to be loaded.
	 *
	 * @param string $site_key Sync site key.
	 *
	 * @return Woo_Sync_Background_Sync_Job
	 */
	private function job( $site_key = self::STORE ) {

		return new Woo_Sync_Background_Sync_Job(
			$site_key,
			array(
				'domain' => $site_key,
				'mode'   => 0,
			)
		);
	}

	/**
	 * Adds a contact the way a first import adds one: `created` comes from the order,
	 * not from the moment the row is written.
	 *
	 * @param string $email          Billing email on the order.
	 * @param int    $order_date_uts Date of the order that created the contact.
	 *
	 * @return int Contact ID.
	 */
	private function add_contact_from_order( $email, $order_date_uts ) {

		global $zbs;

		return $zbs->DAL->contacts->addUpdateContact(
			array(
				'data' => $this->generate_contact_data(
					array(
						'email'   => $email,
						'created' => $order_date_uts,
					)
				),
			)
		);
	}

	/**
	 * @testdox Woo Sync stores the order's date as the contact's creation time, so creation time cannot say who made the contact.
	 */
	#[TestDox( 'Woo Sync stores the order\'s date as the contact\'s creation time, so creation time cannot say who made the contact.' )]
	public function test_woo_sync_stores_the_order_date_as_the_creation_time() {

		global $zbs;

		$order_date = strtotime( '2019-01-01 09:00:00' );

		$contact_id = $this->add_contact_from_order( 'alex@example.test', $order_date );

		$this->assertGreaterThan( 0, $contact_id, 'Failed to create the contact under test.' );

		$contact = $zbs->DAL->contacts->getContact( $contact_id, array( 'fields' => array( 'ID', 'zbsc_created' ) ) );

		$this->assertSame(
			$order_date,
			(int) $contact['created'],
			'A contact created from an order carries the order date, not the time the import ran.'
		);
	}

	/**
	 * @testdox A contact the import created is not protected, however old the order that created it.
	 */
	#[TestDox( 'A contact the import created is not protected, however old the order that created it.' )]
	public function test_a_contact_the_import_created_does_not_predate_it() {

		$job = $this->job();

		$contact_id = $this->add_contact_from_order( 'alex@example.test', strtotime( '2019-01-01 09:00:00' ) );

		$job->mark_contact_created_by_import( $contact_id );

		$this->assertFalse(
			$job->contact_predates_import( $contact_id ),
			'A contact this import created should be free for a later order in the same run to replace.'
		);
	}

	/**
	 * @testdox A contact added by hand while the import is running is protected.
	 */
	#[TestDox( 'A contact added by hand while the import is running is protected.' )]
	public function test_a_contact_added_by_hand_during_an_import_is_protected() {

		$job = $this->job();

		// No `created` passed, so this lands at the current time, mid-import.
		$contact_id = $this->add_contact( array( 'email' => 'jane@example.test' ) );

		$this->assertGreaterThan( 0, $contact_id, 'Failed to create the contact under test.' );

		$this->assertTrue(
			$job->contact_predates_import( $contact_id ),
			'A contact the import did not create should keep its protection, whenever it was added.'
		);
	}

	/**
	 * @testdox The mark survives the later orders that update the contact.
	 */
	#[TestDox( 'The mark survives the later orders that update the contact.' )]
	public function test_the_mark_survives_a_contact_update() {

		global $zbs;

		$job = $this->job();

		$contact_id = $this->add_contact_from_order( 'alex@example.test', strtotime( '2019-01-01 09:00:00' ) );

		$job->mark_contact_created_by_import( $contact_id );

		// A later order in the same run comes through and updates the contact.
		$zbs->DAL->contacts->addUpdateContact(
			array(
				'id'   => $contact_id,
				'data' => $this->generate_contact_data(
					array(
						'email' => 'alex@example.test',
						'city'  => 'Cork',
					)
				),
			)
		);

		$this->assertTrue(
			$job->contact_created_by_import( $contact_id ),
			'An update should not lose the record of which contacts the import created.'
		);
	}

	/**
	 * @testdox A contact another store's import created is protected from this one.
	 */
	#[TestDox( 'A contact another store\'s import created is protected from this one.' )]
	public function test_a_contact_from_another_stores_import_is_protected() {

		$contact_id = $this->add_contact_from_order( 'alex@example.test', strtotime( '2019-01-01 09:00:00' ) );

		$this->job( self::OTHER_STORE )->mark_contact_created_by_import( $contact_id );

		$this->assertTrue(
			$this->job()->contact_predates_import( $contact_id ),
			'A contact was already in the CRM as far as a second store\'s import is concerned.'
		);
	}

	/**
	 * @testdox An order for an address with no contact behind it has nothing to protect.
	 */
	#[TestDox( 'An order for an address with no contact behind it has nothing to protect.' )]
	public function test_an_unknown_contact_has_nothing_to_protect() {

		$this->assertFalse(
			$this->job()->contact_predates_import( -1 ),
			'An order about to create a contact has nothing to protect.'
		);
	}

	/**
	 * @testdox An order resolves to the contact its billing address would update.
	 */
	#[TestDox( 'An order resolves to the contact its billing address would update.' )]
	public function test_existing_contact_id_resolves_the_contact_an_order_would_update() {

		$job = $this->job();

		$contact_id = $this->add_contact( array( 'email' => 'alex@example.test' ) );

		$this->assertGreaterThan( 0, $contact_id, 'Failed to create the contact under test.' );

		$this->assertSame(
			$contact_id,
			$job->existing_contact_id( 'alex@example.test' ),
			'The lookup should resolve to the contact the order would update.'
		);

		$this->assertLessThan(
			1,
			$job->existing_contact_id( 'nobody@example.test' ),
			'An address with no contact behind it should resolve to nothing.'
		);

		$this->assertLessThan(
			1,
			$job->existing_contact_id( '' ),
			'An order with no billing email should resolve to nothing.'
		);
	}
}
