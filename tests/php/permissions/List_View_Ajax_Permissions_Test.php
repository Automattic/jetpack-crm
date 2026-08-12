<?php
/**
 * Tests for the list view data AJAX endpoint permission checks.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\TestDox;
use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;

/**
 * Test that the list view data endpoint honours per-object view capabilities.
 *
 * Deliberately not in the `ajax` group: the WordPress test harness skips that
 * group by convention, and these are regression tests that must always run.
 */
class List_View_Ajax_Permissions_Test extends WP_Ajax_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * Sign in as a user holding the given capabilities and prime the AJAX request.
	 *
	 * @param array $caps      Capabilities to grant.
	 * @param mixed $list_type List view type to request, as it arrives on the request.
	 *
	 * @return void
	 */
	private function prepare_request( array $caps, $list_type ) {

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );

		foreach ( $caps as $cap ) {
			$user->add_cap( $cap );
		}

		wp_set_current_user( $user_id );

		// These helpers memoise their answer in a global for the rest of the request,
		// which outlives a single test. Clear them so each test sees its own user.
		unset( $GLOBALS['zeroBSCRM_isZBSUser'], $GLOBALS['zeroBSCRM_isZBSBackendUser'] );

		$_POST = array(
			'action' => 'retrieveListViewData',
			'sec'    => wp_create_nonce( 'zbscrmjs-ajax-nonce' ),
			'v'      => array(
				'listtype' => $list_type,
				'count'    => 20,
				'paged'    => 1,
			),
		);
	}

	/**
	 * Run the endpoint and return the decoded response.
	 *
	 * @return array
	 */
	private function fetch_list_view() {

		// admin_init sends admin headers, and PHPUnit has already written to stdout
		// by the time the test runs, so PHP warns that headers were already sent.
		// That is an artefact of running admin AJAX under the CLI test harness, so
		// swallow just that warning and leave every other error to PHPUnit.
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			static function ( $errno, $errstr ) {
				return str_contains( $errstr, 'Cannot modify header information' );
			},
			E_WARNING
		);

		try {
			$this->_handleAjax( 'retrieveListViewData' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		} finally {
			restore_error_handler();
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * Assert that the endpoint refused the request on permission grounds.
	 *
	 * @param array $response Decoded endpoint response.
	 *
	 * @return void
	 */
	private function assert_refused( $response ) {

		$this->assertArrayHasKey( 'success', $response );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 1, $response['data']['no-action-or-rights'] );
	}

	/**
	 * Clean up request state.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$_POST = array();

		parent::tear_down();
	}

	/**
	 * A user without the task view capability is refused the task list view.
	 *
	 * This is the Quote Manager case: they hold contact and quote capabilities,
	 * which is enough to load a CRM list view and obtain a valid nonce, but they
	 * must not be able to swap the requested list type for tasks.
	 *
	 * @return void
	 */
	#[TestDox( 'A quote manager cannot retrieve task list view data.' )]
	public function test_quote_manager_cannot_retrieve_tasks() {

		$this->prepare_request(
			array(
				'admin_zerobs_view_customers',
				'admin_zerobs_view_quotes',
				'admin_zerobs_customers',
				'admin_zerobs_quotes',
			),
			'event'
		);

		$response = $this->fetch_list_view();

		$this->assert_refused( $response );
	}

	/**
	 * A user without the forms capability is refused the form list view.
	 *
	 * @return void
	 */
	#[TestDox( 'A quote manager cannot retrieve form list view data.' )]
	public function test_quote_manager_cannot_retrieve_forms() {

		$this->prepare_request(
			array(
				'admin_zerobs_view_customers',
				'admin_zerobs_view_quotes',
			),
			'form'
		);

		$response = $this->fetch_list_view();

		$this->assert_refused( $response );
	}

	/**
	 * A user holding the task view capability can retrieve the task list view.
	 *
	 * @return void
	 */
	#[TestDox( 'A user with the task view capability can retrieve task list view data.' )]
	public function test_task_viewer_can_retrieve_tasks() {

		$this->prepare_request( array( 'admin_zerobs_view_events' ), 'event' );

		$response = $this->fetch_list_view();

		$this->assertArrayHasKey( 'objects', $response );
		$this->assertArrayNotHasKey( 'no-action-or-rights', $response );
	}

	/**
	 * An unrecognised list view type is refused rather than silently returning.
	 *
	 * @return void
	 */
	#[TestDox( 'An unrecognised list view type is refused.' )]
	public function test_unknown_list_type_is_refused() {

		$this->prepare_request( array( 'admin_zerobs_view_events' ), 'not-a-list-type' );

		$response = $this->fetch_list_view();

		$this->assert_refused( $response );
	}

	/**
	 * An arrayed list view type is refused rather than tripping over itself.
	 *
	 * sanitize_text_field() returns an empty string for an array, so the gate sees
	 * a type it does not recognise. Pinning that, because it rests on a detail of
	 * how WordPress sanitises and the alternative is a PHP error in the switch.
	 *
	 * @return void
	 */
	#[TestDox( 'An arrayed list view type is refused.' )]
	public function test_arrayed_list_type_is_refused() {

		$this->prepare_request( array( 'admin_zerobs_view_customers' ), array( 'customer' ) );

		$response = $this->fetch_list_view();

		$this->assert_refused( $response );
	}

	/**
	 * A list type an extension is listening for reaches the extension.
	 *
	 * This is the case the permission gate nearly broke. The gate has to allow the
	 * type through *and* the handler's default arm has to fire the action, so the
	 * assertion is that the listener ran, not that the gate said yes.
	 *
	 * @return void
	 */
	#[TestDox( 'A list type an extension is listening for reaches the extension.' )]
	public function test_extension_list_type_reaches_the_extension() {

		$fired    = false;
		$callback = static function () use ( &$fired ) {
			$fired = true;
		};

		add_action( 'zerobs_ajax_list_view_mailcampaign', $callback );

		try {
			// admin_zerobs_usr is granted to every CRM back end role.
			$this->prepare_request( array( 'admin_zerobs_usr', 'admin_zerobs_view_customers' ), 'mailcampaign' );

			$response = $this->fetch_list_view();

			$this->assertTrue( $fired, 'The extension list view action did not fire.' );
			$this->assertArrayNotHasKey( 'no-action-or-rights', (array) $response );
		} finally {
			remove_action( 'zerobs_ajax_list_view_mailcampaign', $callback );
		}
	}

	/**
	 * An extension list type is refused to a user with no CRM access.
	 *
	 * @return void
	 */
	#[TestDox( 'An extension list type is refused to a user with no CRM access.' )]
	public function test_extension_list_type_is_refused_to_non_crm_users() {

		$fired    = false;
		$callback = static function () use ( &$fired ) {
			$fired = true;
		};

		add_action( 'zerobs_ajax_list_view_mailcampaign', $callback );

		try {
			$this->prepare_request( array(), 'mailcampaign' );

			$response = $this->fetch_list_view();

			$this->assert_refused( $response );
			$this->assertFalse( $fired, 'The extension list view action fired for a user with no CRM access.' );
		} finally {
			remove_action( 'zerobs_ajax_list_view_mailcampaign', $callback );
		}
	}

	/**
	 * A list type nobody is listening for is refused, whoever asks.
	 *
	 * @return void
	 */
	#[TestDox( 'A list type nobody is listening for is refused.' )]
	public function test_extension_list_type_with_no_listener_is_refused() {

		$this->prepare_request( array( 'admin_zerobs_usr', 'admin_zerobs_view_customers' ), 'mailsequence' );

		$response = $this->fetch_list_view();

		$this->assert_refused( $response );
	}
}
