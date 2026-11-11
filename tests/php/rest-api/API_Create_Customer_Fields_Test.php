<?php

namespace Automattic\Jetpack\CRM\API\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Tests for the legacy create_customer endpoint field preparation.
 */
class API_Create_Customer_Fields_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Ensure partial API updates preserve fields omitted from the payload.
	 */
	#[TestDox( 'Partial customer updates preserve omitted fields and allow explicit clears.' )]
	public function test_partial_update_preserves_omitted_fields() {
		global $zbs;

		$contact_id = $this->add_contact(
			array(
				'email'   => 'partial-update@example.com',
				'fname'   => 'Original',
				'lname'   => 'Preserved',
				'addr1'   => '123 Existing Street',
				'worktel' => '01234 567890',
				'tw'      => 'preserved-social-handle',
			)
		);

		$existing_contact = $zbs->DAL->contacts->getContact( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$contact_id,
			array( 'ignoreowner' => true )
		);
		$update_args       = jpcrm_api_prepare_contact_fields(
			array(
				'fname'   => 'Updated',
				'worktel' => '',
			),
			$existing_contact
		);
		$update_args['id'] = $contact_id;

		$result = zeroBS_integrations_addOrUpdateCustomer(
			'api',
			'partial-update@example.com',
			$update_args
		);

		$this->assertSame( $contact_id, $result );

		$updated_contact = $zbs->DAL->contacts->getContact( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$contact_id,
			array( 'ignoreowner' => true )
		);

		$this->assertSame( 'Updated', $updated_contact['fname'] );
		$this->assertSame( '', $updated_contact['worktel'] );
		$this->assertSame( 'Preserved', $updated_contact['lname'] );
		$this->assertSame( '123 Existing Street', $updated_contact['addr1'] );
		$this->assertSame( 'preserved-social-handle', $updated_contact['tw'] );
	}
}
