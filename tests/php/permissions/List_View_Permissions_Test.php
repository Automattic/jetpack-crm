<?php
/**
 * Tests for the list view type permission gate.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Test the capability required for each list view type.
 */
class List_View_Permissions_Test extends JPCRM_Base_TestCase {

	/**
	 * Create a user holding the given capabilities and make them the current user.
	 *
	 * @param array $caps Capabilities to grant.
	 *
	 * @return void
	 */
	private function set_current_user_with_caps( array $caps ) {

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );

		foreach ( $caps as $cap ) {
			$user->add_cap( $cap );
		}

		wp_set_current_user( $user_id );
	}

	/**
	 * Each list view type, and the capability that unlocks it.
	 *
	 * @return array
	 */
	public static function list_type_capability_provider() {
		return array(
			'contacts'        => array( 'customer', 'admin_zerobs_view_customers' ),
			'companies'       => array( 'company', 'admin_zerobs_view_customers' ),
			'segments'        => array( 'segment', 'admin_zerobs_view_customers' ),
			'quotes'          => array( 'quote', 'admin_zerobs_view_quotes' ),
			'quote templates' => array( 'quotetemplate', 'admin_zerobs_view_quotes' ),
			'invoices'        => array( 'invoice', 'admin_zerobs_view_invoices' ),
			'transactions'    => array( 'transaction', 'admin_zerobs_view_transactions' ),
			'tasks'           => array( 'event', 'admin_zerobs_view_events' ),
			'forms'           => array( 'form', 'admin_zerobs_forms' ),
		);
	}

	/**
	 * A user holding the matching capability can view the list type.
	 *
	 * @param string $list_type  List view type.
	 * @param string $capability Capability that should unlock it.
	 *
	 * @return void
	 */
	#[DataProvider( 'list_type_capability_provider' )]
	#[TestDox( 'The matching capability grants access to a list view type.' )]
	public function test_matching_capability_grants_access( $list_type, $capability ) {

		$this->set_current_user_with_caps( array( $capability ) );

		$this->assertTrue( jpcrm_perms_view_list_type( $list_type ) );
	}

	/**
	 * A user holding every CRM view capability except the matching one is denied.
	 *
	 * @param string $list_type  List view type.
	 * @param string $capability Capability that should unlock it.
	 *
	 * @return void
	 */
	#[DataProvider( 'list_type_capability_provider' )]
	#[TestDox( 'A list view type is denied without its own capability.' )]
	public function test_other_capabilities_do_not_grant_access( $list_type, $capability ) {

		$all_caps = array(
			'admin_zerobs_view_customers',
			'admin_zerobs_view_quotes',
			'admin_zerobs_view_invoices',
			'admin_zerobs_view_transactions',
			'admin_zerobs_view_events',
			'admin_zerobs_forms',
		);

		$this->set_current_user_with_caps( array_diff( $all_caps, array( $capability ) ) );

		$this->assertFalse( jpcrm_perms_view_list_type( $list_type ) );
	}

	/**
	 * A Quote Manager cannot reach the task list view.
	 *
	 * Quote Managers hold the contact and quote view capabilities but not the
	 * task one, so they must not be able to request the task list view.
	 *
	 * @return void
	 */
	#[TestDox( 'A quote manager cannot view the task list view.' )]
	public function test_quote_manager_cannot_view_tasks() {

		$this->set_current_user_with_caps(
			array(
				'admin_zerobs_view_customers',
				'admin_zerobs_view_quotes',
				'admin_zerobs_customers',
				'admin_zerobs_quotes',
			)
		);

		$this->assertTrue( jpcrm_perms_view_list_type( 'quote' ) );
		$this->assertFalse( jpcrm_perms_view_list_type( 'event' ) );
		$this->assertFalse( jpcrm_perms_view_list_type( 'form' ) );
	}

	/**
	 * The task edit capability alone does not grant task list view access.
	 *
	 * @return void
	 */
	#[TestDox( 'The task edit capability alone does not grant list view access.' )]
	public function test_task_edit_capability_does_not_grant_view() {

		$this->set_current_user_with_caps( array( 'admin_zerobs_events' ) );

		$this->assertFalse( jpcrm_perms_view_list_type( 'event' ) );
	}

	/**
	 * Unrecognised list view types are denied, even for a fully capable user.
	 *
	 * @return void
	 */
	#[TestDox( 'Unrecognised list view types are denied.' )]
	public function test_unknown_list_types_are_denied() {

		$this->set_current_user_with_caps(
			array(
				'admin_zerobs_view_customers',
				'admin_zerobs_view_quotes',
				'admin_zerobs_view_invoices',
				'admin_zerobs_view_transactions',
				'admin_zerobs_view_events',
				'admin_zerobs_forms',
			)
		);

		$this->assertFalse( jpcrm_perms_view_list_type( '' ) );
		$this->assertFalse( jpcrm_perms_view_list_type( 'not-a-list-type' ) );
	}
}
