<?php
/**
 * Tests for which requests the Disable Front-End setting stops.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Disable Front-End stops front-end page loads and lets through cron, wp-admin,
 * login page and ?rest_route= requests.
 */
class Disable_Front_End_Test extends JPCRM_Base_TestCase {

	/**
	 * The Disable Front-End setting as it stood before the test ran.
	 *
	 * @var mixed
	 */
	private $killfrontend_before;

	/**
	 * The current page's file name as it stood before the test ran.
	 *
	 * @var string
	 */
	private $pagenow_before;

	/**
	 * Turn Disable Front-End on.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $zbs;
		$this->killfrontend_before = $zbs->settings->get( 'killfrontend' );
		$this->pagenow_before      = $GLOBALS['pagenow'];
		$zbs->settings->update( 'killfrontend', 1 );
	}

	/**
	 * Put the setting and the request state back.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $zbs;
		$zbs->settings->update( 'killfrontend', $this->killfrontend_before );
		$GLOBALS['pagenow']        = $this->pagenow_before; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['current_screen'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		unset( $_GET['rest_route'] );

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

	/**
	 * Requests the check does not stop.
	 *
	 * Not covered here: WP-CLI, XML-RPC and REST_REQUEST requests, because those
	 * constants would stay defined for every later test, and the welcome wizard
	 * and api_key= API requests.
	 *
	 * @return array<string, array{string}>
	 */
	public static function requests_let_through(): array {
		return array(
			'setting off'      => array( 'any request while the setting is off' ),
			'wp-admin page'    => array( 'a wp-admin page' ),
			'login page'       => array( 'the login page' ),
			'rest_route query' => array( 'a ?rest_route= request' ),
		);
	}

	/**
	 * @testdox The Disable Front-End check does not stop $request.
	 *
	 * @dataProvider requests_let_through
	 *
	 * @param string $request The request to set up, in words.
	 */
	#[DataProvider( 'requests_let_through' )]
	#[TestDox( 'The Disable Front-End check does not stop $request.' )]
	public function test_lets_request_through( string $request ) {
		global $zbs;

		if ( 'any request while the setting is off' === $request ) {
			$zbs->settings->update( 'killfrontend', 0 );
		} elseif ( 'a wp-admin page' === $request ) {
			set_current_screen( 'dashboard' );
		} elseif ( 'the login page' === $request ) {
			$GLOBALS['pagenow'] = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			$GLOBALS['pagenow'] = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$_GET['rest_route'] = '/';
		}

		$this->assertFalse( jpcrm_should_stop_front_end() );
	}
}
