<?php
/**
 * Tests for the list view bulk action AJAX endpoint ownership checks.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\TestDox;
use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;

/**
 * Test that the bulk action endpoint honours the same ownership rules as the
 * edit page and the inline editor.
 *
 * Deliberately not in the `ajax` group: the WordPress test harness skips that
 * group by convention, and these are regression tests that must always run.
 *
 * The harness here repeats Inline_Edit_Ownership_Test's; a shared AJAX base
 * class for the three copies is tracked as a public follow-up.
 */
class Bulk_Action_Ownership_Test extends WP_Ajax_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * The capabilities a CRM user needs to open a contact list view and hold
	 * (or be given) a contact.
	 *
	 * @var array
	 */
	private const MANAGER_CAPS = array(
		'admin_zerobs_usr',
		'admin_zerobs_view_customers',
		'admin_zerobs_customers',
	);

	/**
	 * The ZBS instance as it stood before the test ran.
	 *
	 * @var ?\ZeroBSCRM
	 */
	private $original_zbs;

	/**
	 * Store the initial state of ZBS.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $zbs;
		$this->original_zbs = clone $zbs;
	}

	/**
	 * Clean up request and CRM state.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$_POST = array();

		global $zbs;
		$zbs->settings->update( 'perusercustomers', 0 );
		$zbs->settings->update( 'usercangiveownership', 0 );

		zeroBSCRM_database_reset( false );

		// Signing a user in memoises these for the rest of the request. Clear
		// them so nothing is left behind for whatever suite runs next.
		unset( $GLOBALS['zeroBSCRM_isZBSUser'], $GLOBALS['zeroBSCRM_isZBSBackendUser'] );

		parent::tear_down();

		$zbs = $this->original_zbs;
	}

	/**
	 * Create a user holding the contact manager capability set.
	 *
	 * @return int The new user ID.
	 */
	private function create_manager(): int {

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );

		foreach ( self::MANAGER_CAPS as $cap ) {
			$user->add_cap( $cap );
		}

		return $user_id;
	}

	/**
	 * Create a contact assigned to the given user.
	 *
	 * @param int $owner_id The user the contact is assigned to.
	 *
	 * @return int The contact ID.
	 */
	private function create_contact_owned_by( int $owner_id ): int {
		global $zbs;

		$contact_id = $zbs->DAL->contacts->addUpdateContact(
			array(
				'owner' => $owner_id,
				'data'  => array(
					'fname'  => 'Owned',
					'lname'  => 'Contact',
					'email'  => 'owned.contact.' . $owner_id . '@example.test',
					'status' => 'Lead',
				),
			)
		);

		$this->assertIsInt( $contact_id, 'Could not create the contact under test.' );
		$this->assertSame( $owner_id, (int) $zbs->DAL->contacts->getContactOwner( $contact_id ), 'The contact under test did not take its owner.' );

		return $contact_id;
	}

	/**
	 * Apply the ownership settings under test.
	 *
	 * @param int $per_user_customers   The "Contact Assignment" setting.
	 * @param int $can_give_ownership   The "Assign Ownership" setting.
	 *
	 * @return void
	 */
	private function set_ownership_settings( int $per_user_customers, int $can_give_ownership ): void {
		global $zbs;

		$zbs->settings->update( 'perusercustomers', $per_user_customers );
		$zbs->settings->update( 'usercangiveownership', $can_give_ownership );
	}

	/**
	 * Sign in as the given user and run a customer bulk action.
	 *
	 * @param int    $user_id     The acting user.
	 * @param array  $contact_ids The contacts to act on.
	 * @param string $action_str  The bulk action ('changestatus' or 'delete').
	 * @param array  $extra       Extra request fields (e.g. 'newstatus').
	 *
	 * @return array Decoded endpoint response.
	 */
	private function bulk_action( int $user_id, array $contact_ids, string $action_str, array $extra = array() ): array {

		wp_set_current_user( $user_id );

		// These helpers memoise their answer in a global for the rest of the
		// request, which outlives a single test.
		unset( $GLOBALS['zeroBSCRM_isZBSUser'], $GLOBALS['zeroBSCRM_isZBSBackendUser'] );

		$_POST = array_merge(
			array(
				'action'    => 'enactListViewBulkAction',
				'sec'       => wp_create_nonce( 'zbscrmjs-ajax-nonce' ),
				'objtype'   => 'customer',
				'actionstr' => $action_str,
				'ids'       => array_map( 'strval', $contact_ids ),
			),
			$extra
		);

		// admin_init sends admin headers, and PHPUnit has already written to
		// stdout, so PHP warns that headers were already sent. That is an
		// artefact of running admin AJAX under the CLI test harness; swallow
		// just that warning and hand everything else back.
		$previous = set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			static function ( $errno, $errstr, $errfile = '', $errline = 0 ) use ( &$previous ) {

				if ( str_contains( $errstr, 'Cannot modify header information' ) ) {
					return true;
				}

				if ( null === $previous ) {
					return false;
				}

				return ( $previous )( $errno, $errstr, $errfile, $errline );
			}
		);

		try {
			$this->_handleAjax( 'enactListViewBulkAction' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		} finally {
			restore_error_handler();
		}

		$response             = json_decode( $this->_last_response, true );
		$this->_last_response = '';

		return $response;
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
	 * With assignment on and ownership-giving off, the inline editor refuses
	 * writes to another manager's contact; the bulk path must match it.
	 *
	 * @return void
	 */
	#[TestDox( 'A manager cannot bulk-change the status of another manager\'s contact when ownership-giving is off.' )]
	public function test_manager_cannot_bulk_change_anothers_status() {
		global $zbs;

		$editor_id  = $this->create_manager();
		$owner_id   = $this->create_manager();
		$contact_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 1, 0 );

		$response = $this->bulk_action( $editor_id, array( $contact_id ), 'changestatus', array( 'newstatus' => 'Customer' ) );

		$this->assert_refused( $response );
		$this->assertSame( 'Lead', $zbs->DAL->contacts->getContactStatus( $contact_id ), 'The contact changed status.' );
	}

	/**
	 * Deletion is the strongest bulk write, so it gets its own pin.
	 *
	 * @return void
	 */
	#[TestDox( 'A manager cannot bulk-delete another manager\'s contact when ownership-giving is off.' )]
	public function test_manager_cannot_bulk_delete_anothers_contact() {
		global $zbs;

		$editor_id  = $this->create_manager();
		$owner_id   = $this->create_manager();
		$contact_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 1, 0 );

		$response = $this->bulk_action( $editor_id, array( $contact_id ), 'delete' );

		$this->assert_refused( $response );
		$this->assertNotEmpty( $zbs->DAL->contacts->getContact( $contact_id ), 'The contact was deleted.' );
	}

	/**
	 * A mixed selection acts on what the user may edit and skips the rest,
	 * and the reported count reflects only what was done.
	 *
	 * @return void
	 */
	#[TestDox( 'A mixed bulk selection only changes the manager\'s own contacts.' )]
	public function test_mixed_bulk_selection_only_changes_own_contacts() {
		global $zbs;

		$editor_id = $this->create_manager();
		$owner_id  = $this->create_manager();
		$own_id    = $this->create_contact_owned_by( $editor_id );
		$others_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 1, 0 );

		$response = $this->bulk_action( $editor_id, array( $own_id, $others_id ), 'changestatus', array( 'newstatus' => 'Customer' ) );

		$this->assertSame( array( 'accepted' => 1 ), $response );
		$this->assertSame( 'Customer', $zbs->DAL->contacts->getContactStatus( $own_id ), 'The manager\'s own contact did not change.' );
		$this->assertSame( 'Lead', $zbs->DAL->contacts->getContactStatus( $others_id ), 'The other manager\'s contact changed status.' );
	}

	/**
	 * With assignment off (the default), any contact manager may bulk-edit
	 * any contact. Pinned so the gate cannot regress the common configuration.
	 *
	 * @return void
	 */
	#[TestDox( 'A manager can bulk-change any contact when assignment is off.' )]
	public function test_manager_can_bulk_change_when_assignment_is_off() {
		global $zbs;

		$editor_id  = $this->create_manager();
		$owner_id   = $this->create_manager();
		$contact_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 0, 0 );

		$response = $this->bulk_action( $editor_id, array( $contact_id ), 'changestatus', array( 'newstatus' => 'Customer' ) );

		$this->assertSame( array( 'accepted' => 1 ), $response );
		$this->assertSame( 'Customer', $zbs->DAL->contacts->getContactStatus( $contact_id ), 'The status edit did not save.' );
	}

	/**
	 * @return void
	 */
	#[TestDox( 'An administrator can bulk-change any contact whatever the settings.' )]
	public function test_administrator_can_always_bulk_change() {
		global $zbs;

		$admin_id   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$owner_id   = $this->create_manager();
		$contact_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 1, 0 );

		$response = $this->bulk_action( $admin_id, array( $contact_id ), 'changestatus', array( 'newstatus' => 'Customer' ) );

		$this->assertSame( array( 'accepted' => 1 ), $response );
		$this->assertSame( 'Customer', $zbs->DAL->contacts->getContactStatus( $contact_id ), 'The status edit did not save.' );
	}
}
