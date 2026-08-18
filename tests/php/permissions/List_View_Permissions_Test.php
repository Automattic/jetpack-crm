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

		// These helpers memoise their answer in a global for the rest of the request,
		// which outlives a single test. Clear them so each test sees its own user.
		unset( $GLOBALS['zeroBSCRM_isZBSUser'], $GLOBALS['zeroBSCRM_isZBSBackendUser'] );
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

	/**
	 * A list type an extension is listening for is allowed through for CRM users.
	 *
	 * Extensions serve their own list views by hooking
	 * `zerobs_ajax_list_view_{$list_type}`, and apply their own check when they do.
	 * Denying every unrecognised type would take those list views offline.
	 */
	#[TestDox( 'A list type an extension is listening for is allowed through for CRM users.' )]
	public function test_extension_list_types_are_allowed_for_crm_users() {

		$callback = '__return_true';
		add_action( 'zerobs_ajax_list_view_mailcampaign', $callback );

		try {
			// admin_zerobs_usr is granted to every CRM back end role.
			$this->set_current_user_with_caps( array( 'admin_zerobs_usr', 'admin_zerobs_view_customers' ) );
			$this->assertTrue( jpcrm_perms_view_list_type( 'mailcampaign' ) );

			// Still nothing for a type nobody is listening for.
			$this->assertFalse( jpcrm_perms_view_list_type( 'mailsequence' ) );
		} finally {
			remove_action( 'zerobs_ajax_list_view_mailcampaign', $callback );
		}
	}

	/**
	 * An extension list type is denied to users with no CRM access at all.
	 */
	#[TestDox( 'An extension list type is denied to users with no CRM access at all.' )]
	public function test_extension_list_types_are_denied_to_non_crm_users() {

		$callback = '__return_true';
		add_action( 'zerobs_ajax_list_view_mailcampaign', $callback );

		try {
			$this->set_current_user_with_caps( array() );
			$this->assertFalse( jpcrm_perms_view_list_type( 'mailcampaign' ) );
		} finally {
			remove_action( 'zerobs_ajax_list_view_mailcampaign', $callback );
		}
	}

	/**
	 * An extension can refuse its own list type through the filter.
	 */
	#[TestDox( 'An extension can refuse its own list type through the filter.' )]
	public function test_extension_can_deny_its_own_list_type_via_filter() {

		$callback = '__return_true';
		$filter   = static function ( $allowed, $list_type ) {
			return 'mailcampaign' === $list_type ? false : $allowed;
		};

		add_action( 'zerobs_ajax_list_view_mailcampaign', $callback );
		add_filter( 'jpcrm_perms_view_list_type', $filter, 10, 2 );

		try {
			// admin_zerobs_usr is granted to every CRM back end role.
			$this->set_current_user_with_caps( array( 'admin_zerobs_usr', 'admin_zerobs_view_customers' ) );
			$this->assertFalse( jpcrm_perms_view_list_type( 'mailcampaign' ) );
		} finally {
			remove_filter( 'jpcrm_perms_view_list_type', $filter, 10 );
			remove_action( 'zerobs_ajax_list_view_mailcampaign', $callback );
		}
	}

	/**
	 * The filter can refuse one of our own list types.
	 */
	#[TestDox( 'The filter can refuse one of our own list types.' )]
	public function test_filter_can_deny_a_core_list_type() {

		$filter = static function ( $allowed, $list_type ) {
			return 'invoice' === $list_type ? false : $allowed;
		};

		add_filter( 'jpcrm_perms_view_list_type', $filter, 10, 2 );

		try {
			$this->set_current_user_with_caps( array( 'admin_zerobs_view_invoices', 'admin_zerobs_view_quotes' ) );

			$this->assertFalse( jpcrm_perms_view_list_type( 'invoice' ) );

			// Other list types are untouched.
			$this->assertTrue( jpcrm_perms_view_list_type( 'quote' ) );
		} finally {
			remove_filter( 'jpcrm_perms_view_list_type', $filter, 10 );
		}
	}

	/**
	 * The filter can allow one of our own list types to a user without the capability.
	 *
	 * This is the case the filter exists for on our own types: granting a custom
	 * role access to a list view without granting it the capability outright.
	 */
	#[TestDox( 'The filter can allow one of our own list types to a user without the capability.' )]
	public function test_filter_can_allow_a_core_list_type() {

		$filter = static function ( $allowed, $list_type ) {
			return 'event' === $list_type ? true : $allowed;
		};

		add_filter( 'jpcrm_perms_view_list_type', $filter, 10, 2 );

		try {
			$this->set_current_user_with_caps( array( 'admin_zerobs_view_quotes' ) );

			$this->assertTrue( jpcrm_perms_view_list_type( 'event' ) );

			// Still nothing for the list types the filter does not touch.
			$this->assertFalse( jpcrm_perms_view_list_type( 'form' ) );
		} finally {
			remove_filter( 'jpcrm_perms_view_list_type', $filter, 10 );
		}
	}
}
