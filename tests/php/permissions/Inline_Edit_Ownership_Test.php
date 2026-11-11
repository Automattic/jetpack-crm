<?php
/**
 * Tests for the list view inline-edit AJAX endpoint ownership checks.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\TestDox;
use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;

/**
 * Test that the inline-edit endpoint honours the same ownership rules as the
 * edit page (ZeroBSCRM.Edit.php, ZeroBSCRM.MetaBoxes3.Contacts.php).
 *
 * Deliberately not in the `ajax` group: the WordPress test harness skips that
 * group by convention, and these are regression tests that must always run.
 */
class Inline_Edit_Ownership_Test extends WP_Ajax_UnitTestCase {
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
	 * This class cannot extend JPCRM_Base_TestCase, which is where the rest of
	 * the suite gets its clone and restore of $GLOBALS['zbs'], because driving
	 * the endpoint needs WP_Ajax_UnitTestCase. It does the same thing by hand.
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
	 * Sign in as the given user and run an inline edit against a contact.
	 *
	 * @param int    $user_id    The acting user.
	 * @param int    $contact_id The contact to edit.
	 * @param string $field      The field to write ('status' or 'assigned').
	 * @param string $value      The value to write.
	 *
	 * @return array Decoded endpoint response.
	 */
	private function inline_edit( int $user_id, int $contact_id, string $field, string $value ): array {

		wp_set_current_user( $user_id );

		// These helpers memoise their answer in a global for the rest of the
		// request, which outlives a single test.
		unset( $GLOBALS['zeroBSCRM_isZBSUser'], $GLOBALS['zeroBSCRM_isZBSBackendUser'] );

		$_POST = array(
			'action'   => 'zbs_list_save_inline_edit',
			'sec'      => wp_create_nonce( 'zbscrmjs-ajax-nonce' ),
			'listtype' => 'customer',
			'id'       => (string) $contact_id,
			'field'    => $field,
			'v'        => $value,
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
			$this->_handleAjax( 'zbs_list_save_inline_edit' );
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
	 * Assert that the endpoint reported a saved edit.
	 *
	 * @param array $response Decoded endpoint response.
	 *
	 * @return void
	 */
	private function assert_saved( $response ) {

		$this->assertSame( array( 'success' => 1 ), $response );
	}

	/**
	 * With assignment on and ownership-giving off, the edit page blocks one
	 * manager from touching another's contact; the inline edit must match it.
	 *
	 * @return void
	 */
	#[TestDox( 'A manager cannot reassign another manager\'s contact when ownership-giving is off.' )]
	public function test_manager_cannot_reassign_anothers_contact() {
		global $zbs;

		$attacker_id = $this->create_manager();
		$victim_id   = $this->create_manager();
		$contact_id  = $this->create_contact_owned_by( $victim_id );

		$this->set_ownership_settings( 1, 0 );

		$response = $this->inline_edit( $attacker_id, $contact_id, 'assigned', (string) $attacker_id );

		$this->assert_refused( $response );
		$this->assertSame( $victim_id, (int) $zbs->DAL->contacts->getContactOwner( $contact_id ), 'The contact changed owner.' );
	}

	/**
	 * @return void
	 */
	#[TestDox( 'A manager cannot change the status of another manager\'s contact when ownership-giving is off.' )]
	public function test_manager_cannot_edit_anothers_contact_status() {
		global $zbs;

		$editor_id  = $this->create_manager();
		$owner_id   = $this->create_manager();
		$contact_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 1, 0 );

		$response = $this->inline_edit( $editor_id, $contact_id, 'status', 'Customer' );

		$this->assert_refused( $response );
		$this->assertSame( 'Lead', $zbs->DAL->contacts->getContactStatus( $contact_id ), 'The contact changed status.' );
	}

	/**
	 * @return void
	 */
	#[TestDox( 'A manager can change the status of their own contact when ownership-giving is off.' )]
	public function test_owner_can_edit_their_own_contact_status() {
		global $zbs;

		$owner_id   = $this->create_manager();
		$contact_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 1, 0 );

		$response = $this->inline_edit( $owner_id, $contact_id, 'status', 'Customer' );

		$this->assert_saved( $response );
		$this->assertSame( 'Customer', $zbs->DAL->contacts->getContactStatus( $contact_id ), 'The status edit did not save.' );
	}

	/**
	 * The edit page only offers the owner dropdown to users who may give
	 * ownership, so owners cannot hand off their own contacts either.
	 *
	 * @return void
	 */
	#[TestDox( 'A manager cannot reassign their own contact when ownership-giving is off.' )]
	public function test_owner_cannot_reassign_their_own_contact() {
		global $zbs;

		$owner_id   = $this->create_manager();
		$other_id   = $this->create_manager();
		$contact_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 1, 0 );

		$response = $this->inline_edit( $owner_id, $contact_id, 'assigned', (string) $other_id );

		$this->assert_refused( $response );
		$this->assertSame( $owner_id, (int) $zbs->DAL->contacts->getContactOwner( $contact_id ), 'The contact changed owner.' );
	}

	/**
	 * @return void
	 */
	#[TestDox( 'A manager can reassign another manager\'s contact when ownership-giving is on.' )]
	public function test_manager_can_reassign_when_ownership_giving_is_on() {
		global $zbs;

		$editor_id  = $this->create_manager();
		$owner_id   = $this->create_manager();
		$contact_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 1, 1 );

		$response = $this->inline_edit( $editor_id, $contact_id, 'assigned', (string) $editor_id );

		$this->assert_saved( $response );
		$this->assertSame( $editor_id, (int) $zbs->DAL->contacts->getContactOwner( $contact_id ), 'The reassignment did not save.' );
	}

	/**
	 * With assignment off (the default), any contact manager may edit any
	 * contact, as on the edit page. Pinned so the gate cannot regress the
	 * common configuration.
	 *
	 * @return void
	 */
	#[TestDox( 'A manager can edit any contact when assignment is off.' )]
	public function test_manager_can_edit_when_assignment_is_off() {
		global $zbs;

		$editor_id  = $this->create_manager();
		$owner_id   = $this->create_manager();
		$contact_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 0, 0 );

		$response = $this->inline_edit( $editor_id, $contact_id, 'status', 'Customer' );

		$this->assert_saved( $response );
		$this->assertSame( 'Customer', $zbs->DAL->contacts->getContactStatus( $contact_id ), 'The status edit did not save.' );
	}

	/**
	 * @return void
	 */
	#[TestDox( 'An administrator can reassign any contact whatever the settings.' )]
	public function test_administrator_can_always_reassign() {
		global $zbs;

		$admin_id   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$owner_id   = $this->create_manager();
		$contact_id = $this->create_contact_owned_by( $owner_id );

		$this->set_ownership_settings( 1, 0 );

		$response = $this->inline_edit( $admin_id, $contact_id, 'assigned', (string) $admin_id );

		$this->assert_saved( $response );
		$this->assertSame( $admin_id, (int) $zbs->DAL->contacts->getContactOwner( $contact_id ), 'The reassignment did not save.' );
	}
}
