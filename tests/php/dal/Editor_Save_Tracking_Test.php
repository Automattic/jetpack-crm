<?php
/**
 * Tests for the quote and form editors' "Save" keeping what they don't show.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Goes through the editors' own save code with what their forms post. That code
 * defines ZBS_OBJ_SAVED and does nothing on a second call in the same request,
 * so each test runs in its own process.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
class Editor_Save_Tracking_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Load the edit screens' metaboxes, which only admin requests include.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		require_once ZEROBSCRM_INCLUDE_PATH . 'ZeroBSCRM.MetaBox.php';
		require_once ZEROBSCRM_INCLUDE_PATH . 'ZeroBSCRM.MetaBoxes3.Quotes.php';
		require_once ZEROBSCRM_INCLUDE_PATH . 'ZeroBSCRM.MetaBoxes3.Forms.php';

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Clear what a test posted.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$_POST = array();

		parent::tear_down();
	}

	/**
	 * @testdox Test that saving an accepted quote in the editor keeps its acceptance, link hash and views.
	 */
	#[TestDox( 'Test that saving an accepted quote in the editor keeps its acceptance, link hash and views.' )]
	public function test_quote_editor_save_keeps_acceptance() {
		global $zbs;

		$id = $zbs->DAL->quotes->addUpdateQuote(
			array(
				'data' => $this->generate_quote_data(
					array(
						'template'       => 1,
						'hash'           => 'kP2xR8vN4qT7wZ1mB5cD',
						'viewed_count'   => 3,
						'accepted'       => 1787202585,
						'acceptedsigned' => 'maya.okafor@example.test',
						'acceptedip'     => '203.0.113.7',
					)
				),
			)
		);

		// What the quote editor posts for an accepted quote, edited.
		$_POST = array(
			'zbscq_title'       => 'Some quote title, edited',
			'zbscq_value'       => '175.00',
			'zbscq_date'        => '2023-02-10',
			'zbscq_notes'       => 'Some notes',
			'zbscq_hash'        => 'kP2xR8vN4qT7wZ1mB5cD',
			'quote_status'      => 'accepted',
			'zbs_quote_content' => '<p>Scope and timeline.</p>',
		);

		( new \zeroBS__Metabox_Quote( __FILE__ ) )->save_data( $id, array() );

		$quote = $zbs->DAL->quotes->getQuote( $id );
		$this->assertSame( 'Some quote title, edited', $quote['title'] );
		$this->assertSame( 1787202585, $quote['accepted'] );
		$this->assertSame( 'maya.okafor@example.test', $quote['acceptedsigned'] );
		$this->assertSame( 'kP2xR8vN4qT7wZ1mB5cD', $quote['hash'] );
		$this->assertSame( 3, $quote['viewed_count'] );
	}

	/**
	 * @testdox Test that saving a form in the editor saves the style picked and keeps its counts.
	 */
	#[TestDox( 'Test that saving a form in the editor saves the style picked and keeps its counts.' )]
	public function test_form_editor_save_keeps_counts_and_saves_style() {
		global $zbs;

		$id = $zbs->DAL->forms->addUpdateForm(
			array(
				'data' => array(
					'title'       => 'Newsletter sign-up',
					'style'       => 'simple',
					'views'       => 1240,
					'conversions' => 38,
				),
			)
		);

		// What the form editor posts after picking the "naked" style.
		$_POST = array(
			'zbsf_title'        => 'Newsletter sign-up, edited',
			'zbsf_label_button' => 'Join',
			'zbsf_style'        => 'naked',
		);

		( new \zeroBS__Metabox_FormLanguage( __FILE__ ) )->save_data( $id, array() );

		$form = $zbs->DAL->forms->getForm( $id );
		$this->assertSame( 'Newsletter sign-up, edited', $form['title'] );
		$this->assertSame( 'naked', $form['style'] );
		$this->assertSame( 1240, $form['views'] );
		$this->assertSame( 38, $form['conversions'] );
	}
}
