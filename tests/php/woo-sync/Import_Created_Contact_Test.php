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

// The Test_Sync_Job seam lives beside the one test class that uses it, as in WP_UnitTestCase_Fix.php.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

namespace Automattic\Jetpack\CRM\Woo_Sync\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use Automattic\JetpackCRM\Woo_Sync_Background_Sync_Job;
use PHPUnit\Framework\Attributes\TestDox;

require_once JPCRM_WOO_SYNC_ROOT_PATH . 'includes/class-woo-sync-background-sync-job.php';

/**
 * A sync job whose walk state is set by the test rather than read from settings.
 *
 * `first_import_walk_in_progress()` is the single seam everything the walk does
 * differently hangs off. Standing in for it here keeps these tests clear of the
 * settings and of WooCommerce, neither of which is loaded.
 */
class Test_Sync_Job extends Woo_Sync_Background_Sync_Job {

	/**
	 * Whether this job should behave as a history walk part way through a first import.
	 *
	 * @var bool
	 */
	public $walking = true;

	/**
	 * @return bool
	 */
	protected function first_import_walk_in_progress() {
		return $this->walking;
	}

	/**
	 * Reach the protected decision under test.
	 *
	 * @param array $crm_object_data     Order data.
	 * @param int   $existing_contact_id Contact the order matches, -1 where there is none.
	 *
	 * @return array
	 */
	public function fields_to_protect( $crm_object_data, $existing_contact_id ) {
		return $this->contact_fields_to_protect( $crm_object_data, $existing_contact_id );
	}
}

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
	 * @param bool   $walking  Whether the job is walking order history, as against
	 *                         handling a single order that has just come in.
	 *
	 * @return Test_Sync_Job
	 */
	private function job( $site_key = self::STORE, $walking = true ) {

		$job = new Test_Sync_Job(
			$site_key,
			array(
				'domain' => $site_key,
				'mode'   => 0,
			)
		);

		$job->walking = $walking;

		return $job;
	}

	/**
	 * A job handling an order that arrived on the site, rather than one from history.
	 *
	 * @param string $site_key Sync site key.
	 *
	 * @return Test_Sync_Job
	 */
	private function live_order_job( $site_key = self::STORE ) {
		return $this->job( $site_key, false );
	}

	/**
	 * Order data as `woocommerce_order_to_crm_objects` hands it over.
	 *
	 * @param bool $is_guest Whether the order has no WooCommerce account behind it.
	 *
	 * @return array
	 */
	private function order_data( $is_guest = true ) {

		$contact = array( 'email' => 'alex@example.test' );

		// `wpid` is what says there is an account behind the order.
		if ( ! $is_guest ) {
			$contact['wpid'] = 42;
		}

		return array( 'contact' => $contact );
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
	 * @testdox The walk recognises a contact it created, however old the order that created it.
	 */
	#[TestDox( 'The walk recognises a contact it created, however old the order that created it.' )]
	public function test_the_walk_recognises_a_contact_it_created() {

		$job = $this->job();

		$contact_id = $this->add_contact_from_order( 'alex@example.test', strtotime( '2019-01-01 09:00:00' ) );

		$job->mark_contact_created_by_import( $contact_id );

		$this->assertTrue(
			$job->contact_created_by_import_walk( $contact_id ),
			'A contact this run created should be free for a later order in the same run to replace.'
		);
	}

	/**
	 * @testdox A contact added by hand while the import is running is not one the walk created.
	 */
	#[TestDox( 'A contact added by hand while the import is running is not one the walk created.' )]
	public function test_a_contact_added_by_hand_during_an_import_is_protected() {

		$job = $this->job();

		// No `created` passed, so this lands at the current time, mid-import.
		$contact_id = $this->add_contact( array( 'email' => 'jane@example.test' ) );

		$this->assertGreaterThan( 0, $contact_id, 'Failed to create the contact under test.' );

		$this->assertFalse(
			$job->contact_created_by_import_walk( $contact_id ),
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

		$this->assertFalse(
			$this->job()->contact_created_by_import_walk( $contact_id ),
			'A contact was already in the CRM as far as a second store\'s import is concerned.'
		);
	}

	/**
	 * @testdox An order for an address with no contact behind it has nothing to protect.
	 */
	#[TestDox( 'An order for an address with no contact behind it has nothing to protect.' )]
	public function test_an_unknown_contact_has_nothing_to_protect() {

		$this->assertFalse(
			$this->job()->contact_created_by_import_walk( -1 ),
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

	/**
	 * The decision the rest of this file feeds. A guest order keeps its hands off an
	 * existing contact's details, with one exception: a contact this same run of the
	 * history walk created from an older order.
	 *
	 * @testdox A guest order may only replace details on a contact the same walk created.
	 */
	#[TestDox( 'A guest order may only replace details on a contact the same walk created.' )]
	public function test_only_a_contact_the_walk_created_is_left_unprotected() {

		$job = $this->job();

		$created_by_walk = $this->add_contact_from_order( 'alex@example.test', strtotime( '2019-01-01 09:00:00' ) );
		$job->mark_contact_created_by_import( $created_by_walk );

		$this->assertSame(
			array(),
			$job->fields_to_protect( $this->order_data(), $created_by_walk ),
			'A newer order in the same run should be able to bring the details up to date.'
		);

		$added_by_hand = $this->add_contact( array( 'email' => 'jane@example.test' ) );

		$this->assertNotEmpty(
			$job->fields_to_protect( $this->order_data(), $added_by_hand ),
			'A contact the walk did not create should keep its details.'
		);

		$other_store = $this->add_contact( array( 'email' => 'sam@example.test' ) );
		$this->job( self::OTHER_STORE )->mark_contact_created_by_import( $other_store );

		$this->assertNotEmpty(
			$job->fields_to_protect( $this->order_data(), $other_store ),
			'Another store\'s import does not entitle this one to replace anything.'
		);
	}

	/**
	 * @testdox An order with a WooCommerce account behind it still updates freely.
	 */
	#[TestDox( 'An order with a WooCommerce account behind it still updates freely.' )]
	public function test_an_account_order_is_never_protected() {

		$job = $this->job();

		$contact_id = $this->add_contact( array( 'email' => 'jane@example.test' ) );

		$this->assertSame(
			array(),
			$job->fields_to_protect( $this->order_data( false ), $contact_id ),
			'The account says whose details these are, so they should keep updating.'
		);
	}

	/**
	 * `import_crm_object_data()` serves the history walk and the hooks that fire when an
	 * order arrives on the site. Only the walk creates contacts it is then entitled to
	 * revise; a contact a live order creates is new to the CRM as of now, and a CRM user
	 * may correct it at any point afterwards.
	 *
	 * @testdox A live order never treats a contact as one the import created.
	 */
	#[TestDox( 'A live order never treats a contact as one the import created.' )]
	public function test_a_live_order_does_not_get_the_walk_exemption() {

		$contact_id = $this->add_contact_from_order( 'alex@example.test', strtotime( '2019-01-01 09:00:00' ) );

		// Marked by the walk, exactly as a backfill would have left it.
		$this->job()->mark_contact_created_by_import( $contact_id );

		$live = $this->live_order_job();

		$this->assertFalse(
			$live->contact_created_by_import_walk( $contact_id ),
			'A live order is not part of the walk and must not inherit its licence to overwrite.'
		);

		$this->assertNotEmpty(
			$live->fields_to_protect( $this->order_data(), $contact_id ),
			'A guest checkout during a first import must not overwrite details already recorded.'
		);
	}

	/**
	 * Restarting the import resets its progress, and the marks have to go with it.
	 * Otherwise every contact the previous import created looks like one this run
	 * created, and months of hand corrections are open to being replaced.
	 *
	 * @testdox Restarting an import forgets which contacts the last one created.
	 */
	#[TestDox( 'Restarting an import forgets which contacts the last one created.' )]
	public function test_restarting_an_import_clears_the_marks() {

		$job = $this->job();

		$contact_id = $this->add_contact_from_order( 'alex@example.test', strtotime( '2019-01-01 09:00:00' ) );
		$job->mark_contact_created_by_import( $contact_id );

		$this->assertTrue(
			$job->contact_created_by_import( $contact_id ),
			'Failed to mark the contact under test.'
		);

		Woo_Sync_Background_Sync_Job::clear_first_import_marks( self::STORE );

		$this->assertFalse(
			$job->contact_created_by_import( $contact_id ),
			'A restarted import should not inherit the previous run\'s marks.'
		);

		$this->assertNotEmpty(
			$job->fields_to_protect( $this->order_data(), $contact_id ),
			'After a restart the contact should be protected like any other.'
		);
	}

	/**
	 * @testdox Clearing one store's marks leaves another store's alone.
	 */
	#[TestDox( 'Clearing one store\'s marks leaves another store\'s alone.' )]
	public function test_clearing_marks_is_scoped_to_one_store() {

		$contact_id = $this->add_contact_from_order( 'alex@example.test', strtotime( '2019-01-01 09:00:00' ) );

		$other = $this->job( self::OTHER_STORE );
		$other->mark_contact_created_by_import( $contact_id );

		Woo_Sync_Background_Sync_Job::clear_first_import_marks( self::STORE );

		$this->assertTrue(
			$other->contact_created_by_import( $contact_id ),
			'Restarting one store should not disturb another store\'s import.'
		);
	}
}
