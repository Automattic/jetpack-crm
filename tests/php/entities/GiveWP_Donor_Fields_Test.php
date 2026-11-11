<?php
/**
 * Tests for the contact fields a GiveWP donation can set.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use Automattic\JetpackCRM\JPCRM_GiveWP;
use PHPUnit\Framework\Attributes\TestDox;

require_once JPCRM_MODULES_PATH . 'givewp/class-jpcrm-givewp.php';

/**
 * Donor-supplied `jpcrm-` fields can't set identity, ownership or status.
 */
class GiveWP_Donor_Fields_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * @testdox Donor-supplied jpcrm- fields can't set the contact's WordPress user, email or status.
	 */
	#[TestDox( 'Donor-supplied jpcrm- fields can\'t set the contact\'s WordPress user, email or status.' )]
	public function test_donor_fields_cannot_set_identity_or_status() {
		global $zbs;

		$other_user_id = self::factory()->user->create( array( 'user_email' => 'someone-else@example.test' ) );

		$contact_id = $this->add_donor(
			array(
				'email'        => 'donor@example.test',
				'first_name'   => 'Dana',
				'jpcrm-wpid'   => $other_user_id,
				'jpcrm-email'  => 'someone-else@example.test',
				'jpcrm-status' => 'Customer',
				'jpcrm-city'   => 'Cork',
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertSame( 'donor@example.test', $contact['email'] );
		$this->assertSame( 'Donor', $contact['status'] );
		$this->assertNotEquals( $other_user_id, (int) $contact['wpid'] );
		$this->assertSame( 'Cork', $contact['city'], 'Ordinary jpcrm- fields should still map.' );
	}

	/**
	 * Run a donation's donor info through the GiveWP integration.
	 *
	 * @param array $user_info GiveWP user_info.
	 * @return int Contact ID.
	 */
	private function add_donor( array $user_info ): int {
		$integration = new JPCRM_GiveWP();
		$method      = new \ReflectionMethod( $integration, 'add_update_donor' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true ); // A no-op from PHP 8.1, and deprecated in 8.5.
		}

		return (int) $method->invoke(
			$integration,
			123,
			array(
				'user_info'       => $user_info,
				'give_form_title' => 'Test form',
			)
		);
	}
}
