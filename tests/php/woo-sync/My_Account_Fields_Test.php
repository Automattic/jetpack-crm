<?php
/**
 * Tests for which contact fields a customer can change from WooCommerce My Account
 * and the Client Portal.
 *
 * @package Automattic\Jetpack\CRM
 */

// The stand-in lives beside the one test class that uses it, as in Import_Created_Contact_Test.php.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

namespace Automattic\Jetpack\CRM\Woo_Sync\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use Automattic\JetpackCRM\Woo_Sync_My_Account_Integration;
use PHPUnit\Framework\Attributes\TestDox;

require_once JPCRM_WOO_SYNC_ROOT_PATH . 'includes/class-woo-sync-my-account-integration.php';

/**
 * Stands in for the Woo Sync module's settings.
 */
class Woo_Sync_Settings_Stub {

	/**
	 * Woo Sync settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Set the settings.
	 *
	 * @param array $settings Woo Sync settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * All settings.
	 *
	 * @return array
	 */
	public function getAll() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- matches the real settings object.
		return $this->settings;
	}
}

/**
 * Test the field rules shared by My Account and the Client Portal.
 */
class My_Account_Fields_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Attach a Woo Sync module stand-in that shows city and second-address city on My Account.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $zbs;
		$zbs->modules->woosync = (object) array( 'settings' => new Woo_Sync_Settings_Stub( array( 'wcport' => 'city,seccity' ) ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Remove the stand-in and restore settings.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $zbs;
		unset( $zbs->modules->woosync );
		$this->set_setting( 'portal_hidefields', 'status,email' );
		$this->set_setting( 'portal_readonlyfields', '' );
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Change a setting and make sure later reads in this request see it.
	 *
	 * A setting written for the first time can read back as unset until the next
	 * request, because the settings cache keeps the miss from the insert's ID lookup.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Value.
	 * @return void
	 */
	private function set_setting( string $key, $value ): void {
		global $zbs;
		$zbs->settings->update( $key, $value );
		\zbsDAL::reset_mitigation_cache_for_issue_3504();
	}

	/**
	 * @testdox My Account saves a field it shows, and ignores status and fields it doesn't show.
	 */
	#[TestDox( 'My Account saves a field it shows, and ignores status and fields it doesn\'t show.' )]
	public function test_my_account_saves_only_shown_editable_fields() {
		global $zbs;

		$user_id    = self::factory()->user->create( array( 'user_email' => 'customer@example.test' ) );
		$contact_id = $this->add_contact(
			array(
				'email'   => 'customer@example.test',
				'status'  => 'Lead',
				'city'    => 'Cork',
				'hometel' => '111',
			)
		);
		zeroBS_setCustomerWPID( $contact_id, $user_id );

		$_POST = array(
			'account_first_name' => 'Alex',
			'account_last_name'  => 'Murphy',
			'account_email'      => 'customer@example.test',
			'zbsc_city'          => 'Galway',
			'zbsc_status'        => 'Customer',
			'zbsc_hometel'       => '999',
		);

		( new Woo_Sync_My_Account_Integration() )->save_my_account_crm_field_changes( $user_id );

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->assertSame( 'Galway', $contact['city'], 'A shown, editable field was not saved.' );
		$this->assertSame( 'Lead', $contact['status'], 'Status was changed from My Account.' );
		$this->assertSame( '111', $contact['hometel'], 'A field not shown on My Account was changed.' );
		$this->assertSame( 'Alex', $contact['fname'], 'The account name no longer syncs.' );
	}

	/**
	 * @testdox By default a contact can edit their address but not their status or email.
	 */
	#[TestDox( 'By default a contact can edit their address but not their status or email.' )]
	public function test_default_self_editable_fields() {
		$this->assertTrue( jpcrm_contact_field_is_self_editable( 'city' ) );
		$this->assertFalse( jpcrm_contact_field_is_self_editable( 'status' ) );
		$this->assertFalse( jpcrm_contact_field_is_self_editable( 'email' ) );
		$this->assertFalse( jpcrm_contact_field_is_self_editable( 'not_a_field' ) );
	}

	/**
	 * @testdox Hiding the second address group stops its fields being edited, and leaves the main address editable.
	 */
	#[TestDox( 'Hiding the second address group stops its fields being edited, and leaves the main address editable.' )]
	public function test_hidden_second_address_group() {
		$this->set_setting( 'portal_hidefields', 'status,email,jpcrm-second-address' );

		$this->assertFalse( jpcrm_contact_field_is_self_editable( 'seccity' ) );
		$this->assertTrue( jpcrm_contact_field_is_self_editable( 'city' ) );
	}

	/**
	 * @testdox A read-only main address group can't be edited.
	 */
	#[TestDox( 'A read-only main address group can\'t be edited.' )]
	public function test_read_only_main_address_group() {
		$this->set_setting( 'portal_readonlyfields', 'jpcrm-main-address' );

		$this->assertFalse( jpcrm_contact_field_is_self_editable( 'city' ) );
		$this->assertTrue( jpcrm_contact_field_is_self_editable( 'seccity' ) );
	}
}
