<?php
/**
 * Tests for the empty state helpers.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\FormatHelpers\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Lists, the dashboard, the task calendar and the email screens use
 * jpcrm_empty_state_html() when there's nothing to show.
 */
class Empty_State_Test extends JPCRM_Base_TestCase {

	/**
	 * @testdox Test that an empty state has its icon, title, description and actions, escaped.
	 */
	#[TestDox( 'Test that an empty state has its icon, title, description and actions, escaped.' )]
	public function test_empty_state_markup() {
		$html = jpcrm_empty_state_html(
			array(
				'id'          => 'jpcrm-test-empty',
				'class'       => 'hidden',
				'icon'        => 'receipt',
				'title'       => 'No invoices <yet>',
				'description' => 'Bill your contacts & more.',
				'actions'     => array(
					array(
						'label'  => 'Learn more',
						'url'    => 'https://example.com/docs',
						'target' => '_blank',
					),
					array(
						'label'   => 'Add invoice',
						'url'     => 'https://example.com/add',
						'primary' => true,
					),
				),
			)
		);

		$this->assertStringContainsString( '<div class="jpcrm-empty-state hidden" id="jpcrm-test-empty">', $html );
		$this->assertStringContainsString( '<div class="jpcrm-empty-state-icon"><svg', $html );
		$this->assertStringContainsString( '<h2 class="jpcrm-empty-state-title">No invoices &lt;yet&gt;</h2>', $html );
		$this->assertStringContainsString( '<p class="jpcrm-empty-state-description">Bill your contacts &amp; more.</p>', $html );
		$this->assertStringContainsString( '<a href="https://example.com/docs" class="jpcrm-button white-bg" target="_blank">Learn more</a>', $html );
		$this->assertStringContainsString( '<a href="https://example.com/add" class="jpcrm-button">Add invoice</a>', $html );
	}

	/**
	 * @testdox Test that an action without a URL is a button, and optional parts are left out.
	 */
	#[TestDox( 'Test that an action without a URL is a button, and optional parts are left out.' )]
	public function test_empty_state_button_and_optional_parts() {
		$html = jpcrm_empty_state_html(
			array(
				'title'   => 'No email templates',
				'actions' => array(
					array(
						'label'   => 'Create the default templates',
						'id'      => 'force-email-create',
						'primary' => true,
					),
				),
			)
		);

		$this->assertStringContainsString( '<button type="button" class="jpcrm-button" id="force-email-create">Create the default templates</button>', $html );
		$this->assertStringNotContainsString( 'jpcrm-empty-state-icon', $html );
		$this->assertStringNotContainsString( 'jpcrm-empty-state-description', $html );
		$this->assertStringNotContainsString( ' id=""', $html );
	}

	/**
	 * @testdox Test that an unknown icon name gives no icon.
	 */
	#[TestDox( 'Test that an unknown icon name gives no icon.' )]
	public function test_unknown_icon() {
		$this->assertSame( '', jpcrm_wp_icon_svg( 'not-an-icon' ) );
		$this->assertStringStartsWith( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"', jpcrm_wp_icon_svg( 'people' ) );
	}
}
