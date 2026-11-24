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
	 * Build a Woo Sync module with its constants and settings only, without
	 * running the rest of its constructor, which registers hooks and runs migrations.
	 *
	 * @return Woo_Sync
	 */
	private function woo_sync(): Woo_Sync {
		$woo_sync = ( new \ReflectionClass( Woo_Sync::class ) )->newInstanceWithoutConstructor();

		( function () {
			if ( ! defined( 'JPCRM_WOO_SYNC_MODE_LOCAL' ) ) {
				$this->definitions();
			}
			$this->init_settings();
		} )->call( $woo_sync );

		return $woo_sync;
	}

	/**
	 * @testdox Adding the local store leaves the saved remote store unchanged.
	 */
	#[TestDox( 'Adding the local store leaves the saved remote store unchanged.' )]
	public function test_adding_local_store_keeps_remote_stores() {
		$woo_sync = $this->woo_sync();

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
		$woo_sync->settings->update( 'sync_sites', array( 'shop_example_test' => $remote ) );

		$woo_sync->add_sync_site(
			array(
				'site_key' => 'local',
				'mode'     => JPCRM_WOO_SYNC_MODE_LOCAL,
				'domain'   => site_url(),
			)
		);

		$sync_sites = $woo_sync->settings->get( 'sync_sites' );
		$this->assertSame( array( 'shop_example_test', 'local' ), array_keys( $sync_sites ) );
		$this->assertSame( $remote, $sync_sites['shop_example_test'] );
	}
}
