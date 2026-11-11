<?php
/**
 * Tests for the extra role given to users created for client portal access.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Permissions\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * The portal "extra role" setting only accepts roles without staff access.
 */
class Portal_Extra_Role_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Add two custom roles: a harmless membership role and a site-managing one.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		add_role( 'jpcrm_test_member', 'Test member', array( 'read' => true ) );
		add_role(
			'jpcrm_test_manager',
			'Test manager',
			array(
				'read'           => true,
				'manage_options' => true,
			)
		);
	}

	/**
	 * Remove the custom roles and the setting.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $zbs;
		$zbs->settings->update( 'portalusers_extrarole', '' );
		remove_role( 'jpcrm_test_member' );
		remove_role( 'jpcrm_test_manager' );
		parent::tear_down();
	}

	/**
	 * Roles the setting must refuse.
	 *
	 * @return array
	 */
	public static function refused_roles(): array {
		return array(
			'administrator'         => array( 'administrator' ),
			'editor'                => array( 'editor' ),
			'author'                => array( 'author' ),
			'CRM admin'             => array( 'zerobs_admin' ),
			'CRM contact manager'   => array( 'zerobs_customermgr' ),
			'custom manage_options' => array( 'jpcrm_test_manager' ),
			'unknown role'          => array( 'no_such_role' ),
			'empty'                 => array( '' ),
		);
	}

	/**
	 * @testdox Roles with staff access are refused as the portal extra role.
	 *
	 * @param string $role Role slug.
	 */
	#[DataProvider( 'refused_roles' )]
	#[TestDox( 'Roles with staff access are refused as the portal extra role.' )]
	public function test_staff_roles_are_refused( $role ) {
		$this->assertFalse( jpcrm_portal_extra_role_is_allowed( $role ) );
	}

	/**
	 * @testdox Subscriber and plain membership roles are allowed as the portal extra role.
	 */
	#[TestDox( 'Subscriber and plain membership roles are allowed as the portal extra role.' )]
	public function test_plain_roles_are_allowed() {
		$this->assertTrue( jpcrm_portal_extra_role_is_allowed( 'subscriber' ) );
		$this->assertTrue( jpcrm_portal_extra_role_is_allowed( 'jpcrm_test_member' ) );
	}

	/**
	 * @testdox A staff role already saved in the setting is not given to new portal users.
	 */
	#[TestDox( 'A staff role already saved in the setting is not given to new portal users.' )]
	public function test_saved_staff_role_is_not_applied() {
		$user = $this->create_portal_user_with_extra_role( 'administrator' );

		$this->assertSame( array( 'zerobs_customer' ), array_values( $user->roles ) );
	}

	/**
	 * @testdox An allowed role saved in the setting is given to new portal users.
	 */
	#[TestDox( 'An allowed role saved in the setting is given to new portal users.' )]
	public function test_saved_plain_role_is_applied() {
		$user = $this->create_portal_user_with_extra_role( 'jpcrm_test_member' );

		$this->assertEqualsCanonicalizing( array( 'jpcrm_test_member', 'zerobs_customer' ), array_values( $user->roles ) );
	}

	/**
	 * Save an extra role, then create a portal user for a new contact.
	 *
	 * @param string $role Role to save in the setting.
	 * @return \WP_User The created user.
	 */
	private function create_portal_user_with_extra_role( string $role ): \WP_User {
		global $zbs;

		$zbs->settings->update( 'portalusers_extrarole', $role );

		$email      = 'portal-' . strtolower( wp_generate_password( 6, false ) ) . '@example.test';
		$contact_id = $this->add_contact( array( 'email' => $email ) );

		// Doesn't return the new user, so look it up by email.
		zeroBSCRM_createClientPortalUserFromRecord( $contact_id );
		$user = get_user_by( 'email', $email );

		$this->assertInstanceOf( \WP_User::class, $user );

		return $user;
	}
}
