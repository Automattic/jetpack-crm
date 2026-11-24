<?php
/**
 * Tests for the saved list of Woo Sync store connections.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Woo_Sync\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use Automattic\JetpackCRM\Woo_Sync;
use PHPUnit\Framework\Attributes\TestDox;

require_once JPCRM_WOO_SYNC_ROOT_PATH . 'includes/class-woo-sync.php';

/**
 * Test how store connections are added to the saved list.
 */
class Sync_Sites_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Woo Sync module under test.
	 *
	 * @var Woo_Sync
	 */
	private $woo_sync;

	/**
	 * The saved connection list as it was before the test.
	 *
	 * @var mixed
	 */
	private $sync_sites_before;

	/**
	 * Build a Woo Sync module with its constants and settings only, without
	 * running the rest of its constructor, which registers hooks and runs migrations.
	 * Record the saved connection list so tear_down() can put it back.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->woo_sync = ( new \ReflectionClass( Woo_Sync::class ) )->newInstanceWithoutConstructor();

		( function () {
			if ( ! defined( 'JPCRM_WOO_SYNC_MODE_LOCAL' ) ) {
				$this->definitions();
			}
			$this->init_settings();
		} )->call( $this->woo_sync );

		$this->sync_sites_before = $this->woo_sync->settings->get( 'sync_sites' );
	}

	/**
	 * Put the saved connection list back. Woo Sync keeps a copy of its settings
	 * in memory on the shared CRM settings object, and the database reset between
	 * tests does not clear it, so without this the stores a test saved would still
	 * be there in later tests.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$this->woo_sync->settings->update( 'sync_sites', $this->sync_sites_before );

		parent::tear_down();
	}

	/**
	 * Save one remote store connection that has already synced.
	 *
	 * @return array The saved connection.
	 */
	private function save_remote_store(): array {
		$remote = array(
			'mode'                  => JPCRM_WOO_SYNC_MODE_API,
			'domain'                => 'https://shop.example.test',
			'key'                   => 'ck_remote',
			'secret'                => 'cs_remote',
			'prefix'                => 'shop-',
			'last_sync_fired'       => 1700000000,
			'resume_from_page'      => 7,
			'total_order_count'     => 42,
			'total_customer_count'  => 12,
			'first_import_complete' => true,
		);
		$this->woo_sync->settings->update( 'sync_sites', array( 'shop_example_test' => $remote ) );

		return $remote;
	}

	/**
	 * @testdox Adding the local store leaves the saved remote store unchanged.
	 */
	#[TestDox( 'Adding the local store leaves the saved remote store unchanged.' )]
	public function test_adding_local_store_keeps_remote_stores() {
		$remote = $this->save_remote_store();

		$this->woo_sync->add_sync_site(
			array(
				'site_key' => 'local',
				'mode'     => JPCRM_WOO_SYNC_MODE_LOCAL,
				'domain'   => site_url(),
			)
		);

		$sync_sites = $this->woo_sync->settings->get( 'sync_sites' );
		$this->assertSame( array( 'shop_example_test', 'local' ), array_keys( $sync_sites ) );
		$this->assertSame( $remote, $sync_sites['shop_example_test'] );
	}

	/**
	 * @testdox Reading the connections while WooCommerce is active leaves the saved remote store unchanged.
	 */
	#[TestDox( 'Reading the connections while WooCommerce is active leaves the saved remote store unchanged.' )]
	public function test_reading_connections_with_woocommerce_active_keeps_remote_stores() {
		$remote = $this->save_remote_store();

		// WooCommerce is not loaded in these tests. woocommerce_is_active() also counts
		// a set $GLOBALS['woocommerce'] as WooCommerce running, so set it for this one call.
		$GLOBALS['woocommerce'] = true;
		try {
			$this->woo_sync->get_active_sync_sites();
		} finally {
			unset( $GLOBALS['woocommerce'] );
		}

		$sync_sites = $this->woo_sync->settings->get( 'sync_sites' );
		$this->assertSame( array( 'shop_example_test', 'local' ), array_keys( $sync_sites ) );
		$this->assertSame( $remote, $sync_sites['shop_example_test'] );
	}
}
