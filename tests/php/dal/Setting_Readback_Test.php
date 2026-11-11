<?php
/**
 * Tests for reading settings back within the request that wrote them.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\DAL\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * A setting reads back as written, including the first time it's written.
 */
class Setting_Readback_Test extends JPCRM_Base_TestCase {

	/**
	 * A key no other code uses.
	 *
	 * @var string
	 */
	private const KEY = 'jpcrm_test_readback';

	/**
	 * Start with the setting absent.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		global $zbs;
		$zbs->settings->delete( self::KEY );
	}

	/**
	 * Remove the setting.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $zbs;
		$zbs->settings->delete( self::KEY );
		parent::tear_down();
	}

	/**
	 * @testdox A setting written for the first time reads back in the same request.
	 */
	#[TestDox( 'A setting written for the first time reads back in the same request.' )]
	public function test_first_write_reads_back() {
		global $zbs;

		$zbs->settings->update( self::KEY, 'first value' );

		$this->assertSame( 'first value', $zbs->settings->get( self::KEY ) );
	}

	/**
	 * @testdox A setting read while absent, then written, reads back the new value.
	 */
	#[TestDox( 'A setting read while absent, then written, reads back the new value.' )]
	public function test_read_absent_then_write_reads_back() {
		global $zbs;

		$this->assertFalse( $zbs->settings->get( self::KEY ) );

		$zbs->settings->update( self::KEY, 'later value' );

		$this->assertSame( 'later value', $zbs->settings->get( self::KEY ) );
	}

	/**
	 * @testdox A deleted setting reads back as absent in the same request.
	 */
	#[TestDox( 'A deleted setting reads back as absent in the same request.' )]
	public function test_delete_reads_back_absent() {
		global $zbs;

		$zbs->settings->update( self::KEY, 'doomed' );
		$this->assertSame( 'doomed', $zbs->settings->get( self::KEY ) );

		$zbs->settings->delete( self::KEY );

		$this->assertFalse( $zbs->settings->get( self::KEY ) );
	}

	/**
	 * @testdox An existing setting reads back its updated value.
	 */
	#[TestDox( 'An existing setting reads back its updated value.' )]
	public function test_update_reads_back() {
		global $zbs;

		$zbs->settings->update( self::KEY, 'one' );
		$zbs->settings->update( self::KEY, 'two' );

		$this->assertSame( 'two', $zbs->settings->get( self::KEY ) );
	}
}
