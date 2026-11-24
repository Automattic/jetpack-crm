<?php
/**
 * Tests for which requests the Disable Front-End setting stops.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\TestDox;

/**
 * Disable Front-End stops front-end page loads, but must let WordPress cron run.
 */
class Disable_Front_End_Test extends JPCRM_Base_TestCase {

	/**
	 * The Disable Front-End setting as it stood before the test ran.
	 *
	 * @var mixed
	 */
	private $killfrontend_before;

	/**
	 * Turn Disable Front-End on.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $zbs;
		$this->killfrontend_before = $zbs->settings->get( 'killfrontend' );
		$zbs->settings->update( 'killfrontend', 1 );
	}

	/**
	 * Put the setting back.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $zbs;
		$zbs->settings->update( 'killfrontend', $this->killfrontend_before );

		parent::tear_down();
	}

	/**
	 * @testdox The Disable Front-End check says to stop a front-end request.
	 */
	#[TestDox( 'The Disable Front-End check says to stop a front-end request.' )]
	public function test_stops_front_end_request() {
		$this->assertTrue( jpcrm_should_stop_front_end() );
	}

	/**
	 * @testdox The Disable Front-End check lets a WordPress cron request through.
	 */
	#[TestDox( 'The Disable Front-End check lets a WordPress cron request through.' )]
	public function test_lets_cron_request_through() {
		add_filter( 'wp_doing_cron', '__return_true' );

		$this->assertFalse( jpcrm_should_stop_front_end() );
	}
}
