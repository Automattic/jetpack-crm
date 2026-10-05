<?php
/**
 * Tests for assigning a company and tags to a task through the integration
 * helper, as the create_event API endpoint does.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Test that task creation via `zeroBS_integrations_addOrUpdateTask()` carries
 * `company` and `tags` through to the stored task, the way `customer` always
 * has. The create_event API endpoint builds exactly this field array.
 */
class Task_Company_And_Tags_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Build the field array the API endpoint would hand the helper.
	 *
	 * @param array $extra Fields to add to the baseline.
	 *
	 * @return array
	 */
	private function api_task_fields( array $extra = array() ): array {

		return array_merge(
			array(
				'title'    => 'Sales call',
				'customer' => -1,
				'company'  => -1,
				'notes'    => '',
				'from'     => '09/02/2026 14:00:00',
				'to'       => '09/02/2026 15:00:00',
				'notify'   => -1,
				'complete' => -1,
				'owner'    => -1,
			),
			$extra
		);
	}

	/**
	 * The report's exact scenario: a task posted with a customer, a company
	 * and two tag names.
	 *
	 * @return void
	 */
	#[TestDox( 'A task created with company and tag names stores all three links.' )]
	public function test_task_stores_company_and_tags() {
		global $zbs;

		$contact_id = $this->add_contact();
		$company_id = $this->add_company();

		$task_id = zeroBS_integrations_addOrUpdateTask(
			-1,
			$this->api_task_fields(
				array(
					'customer' => $contact_id,
					'company'  => $company_id,
					'tags'     => array( 'Sales Call', 'Quarterly' ),
				)
			),
			array()
		);

		$this->assertIsInt( $task_id );
		$this->assertGreaterThan( 0, $task_id );

		$task = $zbs->DAL->events->getEvent( $task_id );

		$this->assertSame( $contact_id, (int) $task['contact'][0]['id'], 'The contact link did not store.' );
		$this->assertSame( $company_id, (int) $task['company'][0]['id'], 'The company link did not store.' );

		$tag_names = array_map(
			static function ( $tag ) {
				return $tag['name'];
			},
			is_array( $task['tags'] ) ? $task['tags'] : array()
		);
		sort( $tag_names );
		$this->assertSame( array( 'Quarterly', 'Sales Call' ), $tag_names, 'The tags did not store.' );
	}

	/**
	 * Tag IDs are accepted as well as names, matching the DAL's tag_input
	 * contract.
	 *
	 * @return void
	 */
	#[TestDox( 'A task created with an existing tag ID stores that tag.' )]
	public function test_task_stores_tags_by_id() {
		global $zbs;

		$tag_id = $zbs->DAL->addUpdateTag(
			array(
				'data' => array(
					'objtype' => ZBS_TYPE_TASK,
					'name'    => 'Pre-existing',
				),
			)
		);
		$this->assertGreaterThan( 0, (int) $tag_id );

		$task_id = zeroBS_integrations_addOrUpdateTask(
			-1,
			$this->api_task_fields( array( 'tags' => array( $tag_id ) ) ),
			array()
		);

		$task = $zbs->DAL->events->getEvent( $task_id );

		$this->assertCount( 1, $task['tags'], 'The tag did not store.' );
		$this->assertSame( (int) $tag_id, (int) $task['tags'][0]['id'] );
	}

	/**
	 * The nine fields the endpoint always sent still behave as before when no
	 * company or tags are posted.
	 *
	 * @return void
	 */
	#[TestDox( 'A task created without company or tags stores neither.' )]
	public function test_task_without_company_or_tags_stores_neither() {
		global $zbs;

		$contact_id = $this->add_contact();

		$task_id = zeroBS_integrations_addOrUpdateTask(
			-1,
			$this->api_task_fields( array( 'customer' => $contact_id ) ),
			array()
		);

		$task = $zbs->DAL->events->getEvent( $task_id );

		$this->assertSame( $contact_id, (int) $task['contact'][0]['id'], 'The contact link did not store.' );
		$this->assertTrue( empty( $task['company'] ), 'An unexpected company link stored.' );
		$this->assertTrue( empty( $task['tags'] ), 'Unexpected tags stored.' );
	}
}
