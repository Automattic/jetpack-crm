<?php
/**
 * Tests for saving a company's email from its edit screen.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Metabox\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use zeroBS__Metabox_Company;

/**
 * The edit screen keeps a company's email only when it is an email address,
 * as the contact edit screen does.
 *
 * `save_data()` runs once per request (it defines a constant), so this class
 * can make one save.
 */
class Company_Email_Save_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * @testdox A value that is not an email address is not saved, and the screen says so.
	 */
	#[TestDox( 'A value that is not an email address is not saved, and the screen says so.' )]
	public function test_a_value_that_is_not_an_email_address_is_not_saved() {
		global $zbs;

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$company_id = $this->add_company( array( 'email' => 'hello@example.test' ) );
		$this->assertGreaterThan( 0, $company_id, 'Failed to create the company under test.' );

		$original_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- simulating the edit screen's save.
		$_POST         = wp_slash(
			array(
				'zbsco_name'  => 'My Company',
				'zbsco_email' => 'not an "email" address',
			)
		);

		$zbs->pageMessages = array(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		try {
			( new zeroBS__Metabox_Company( ZBS_ROOTFILE ) )->save_data( $company_id, array() );
		} finally {
			$_POST = $original_post;
		}

		$this->assertSame( 'hello@example.test', $zbs->DAL->companies->getCompanyEmail( $company_id ), 'The company email was replaced by a value that is not an email address.' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$messages = implode( '', $zbs->pageMessages ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->assertStringContainsString( 'is not valid', $messages, 'The screen did not say the email was not saved.' );
		$this->assertStringContainsString( 'not an &quot;email&quot; address', $messages, 'The notice does not show the value as text.' );
	}
}
