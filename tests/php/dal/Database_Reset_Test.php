<?php
/**
 * Tests for resetting CRM data.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\TestDox;

/**
 * Data Tools' "Delete CRM Data" runs zeroBSCRM_database_reset(). It used to empty
 * the system email templates too, and since a migration creates them on install,
 * nothing brought them back.
 */
class Database_Reset_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * @testdox Test that resetting CRM data removes contacts but keeps the system email templates.
	 */
	#[TestDox( 'Test that resetting CRM data removes contacts but keeps the system email templates.' )]
	public function test_reset_keeps_system_email_templates() {
		global $zbs;

		$count_contacts = array(
			'count'       => true,
			'ignoreowner' => true,
		);

		zeroBSCRM_populateEmailTemplateList();
		$template_count = (int) zeroBSCRM_mailTemplate_count();
		$this->assertGreaterThan( 0, $template_count );

		$this->add_contact();
		$this->assertSame( 1, (int) $zbs->DAL->contacts->getContacts( $count_contacts ) );

		zeroBSCRM_database_reset( false );

		$this->assertSame( 0, (int) $zbs->DAL->contacts->getContacts( $count_contacts ) );
		$this->assertSame( $template_count, (int) zeroBSCRM_mailTemplate_count() );
	}
}
