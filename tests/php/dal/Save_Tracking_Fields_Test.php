<?php
/**
 * Tests for saves clearing the fields CRM tracks on quotes and forms.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\TestDox;

/**
 * The quote and form editors send only the fields they show. An update writes
 * every column, so the ones they don't send (when a quote was accepted and by
 * whom, its public link hash and view count, a form's views and conversions)
 * were written back as blanks every time someone clicked "Save".
 */
class Save_Tracking_Fields_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Create a quote someone has viewed and accepted.
	 *
	 * @return int The quote ID.
	 */
	private function create_accepted_quote(): int {
		global $zbs;

		$id = $zbs->DAL->quotes->addUpdateQuote(
			array(
				'data' => $this->generate_quote_data(
					array(
						'template'       => 1,
						'hash'           => 'kP2xR8vN4qT7wZ1mB5cD',
						'lastviewed'     => 1787000000,
						'viewed_count'   => 3,
						'accepted'       => 1787202585,
						'acceptedsigned' => 'maya.okafor@example.test',
						'acceptedip'     => '203.0.113.7',
					)
				),
			)
		);
		$this->assertGreaterThan( 0, $id, 'Failed to create the quote under test.' );

		return $id;
	}

	/**
	 * The data the quote editor's "Save" sends for an accepted quote: its
	 * fields, content, template and assignments, and none of the tracking.
	 *
	 * @param array $overrides Field overrides.
	 * @return array
	 */
	private function quote_editor_data( array $overrides = array() ): array {
		return array_merge(
			array(
				'title'     => 'Some quote title, edited',
				'value'     => '175.00',
				'date'      => 1676000000,
				'notes'     => 'Some notes',
				'template'  => 1,
				'content'   => '<p>Scope and timeline.</p>',
				'contacts'  => array(),
				'companies' => array(),
				'tags'      => array(),
			),
			$overrides
		);
	}

	/**
	 * Read a quote's tracking columns straight from the table.
	 *
	 * @param int $id The quote ID.
	 * @return array
	 */
	private function quote_tracking_columns( int $id ): array {
		global $wpdb, $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT zbsq_hash, zbsq_lastviewed, zbsq_viewed_count, zbsq_accepted, zbsq_acceptedsigned, zbsq_acceptedip FROM ' . $ZBSCRM_t['quotes'] . ' WHERE ID = %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
				$id
			),
			ARRAY_A
		);
	}

	/**
	 * @testdox Test that saving a quote keeps when it was accepted, by whom, its link hash and its views.
	 */
	#[TestDox( 'Test that saving a quote keeps when it was accepted, by whom, its link hash and its views.' )]
	public function test_quote_save_keeps_tracking_fields() {
		global $zbs;

		$id     = $this->create_accepted_quote();
		$before = $this->quote_tracking_columns( $id );

		$result = $zbs->DAL->quotes->addUpdateQuote(
			array(
				'id'   => $id,
				'data' => $this->quote_editor_data(),
			)
		);
		$this->assertSame( $id, (int) $result );

		$this->assertSame( $before, $this->quote_tracking_columns( $id ) );
		$this->assertSame( 'Some quote title, edited', $zbs->DAL->quotes->getQuote( $id )['title'] );
	}

	/**
	 * @testdox Test that a save can still clear a quote's acceptance, as setting it back to draft does.
	 */
	#[TestDox( "Test that a save can still clear a quote's acceptance, as setting it back to draft does." )]
	public function test_quote_save_can_clear_acceptance() {
		global $zbs;

		$id = $this->create_accepted_quote();

		$zbs->DAL->quotes->addUpdateQuote(
			array(
				'id'   => $id,
				'data' => $this->quote_editor_data(
					array(
						'accepted' => 0,
						'template' => -1,
					)
				),
			)
		);

		$columns = $this->quote_tracking_columns( $id );
		$this->assertSame( '0', (string) $columns['zbsq_accepted'] );
		$this->assertSame( 'kP2xR8vN4qT7wZ1mB5cD', $columns['zbsq_hash'] );
		$this->assertSame( '3', (string) $columns['zbsq_viewed_count'] );
	}

	/**
	 * @testdox Test that a quote reports who accepted it and from where, not the time twice more.
	 */
	#[TestDox( 'Test that a quote reports who accepted it and from where, not the time twice more.' )]
	public function test_quote_reports_who_accepted_it() {
		global $zbs;

		$quote = $zbs->DAL->quotes->getQuote( $this->create_accepted_quote() );

		$this->assertSame( 1787202585, $quote['accepted'] );
		$this->assertSame( 'maya.okafor@example.test', $quote['acceptedsigned'] );
		$this->assertSame( '203.0.113.7', $quote['acceptedip'] );
	}

	/**
	 * @testdox Test that saving a form keeps its view and conversion counts.
	 */
	#[TestDox( 'Test that saving a form keeps its view and conversion counts.' )]
	public function test_form_save_keeps_counts() {
		global $zbs;

		$id = $zbs->DAL->forms->addUpdateForm(
			array(
				'data' => array(
					'title'        => 'Newsletter sign-up',
					'style'        => 'simple',
					'views'        => 1240,
					'conversions'  => 38,
					'label_button' => 'Sign up',
				),
			)
		);
		$this->assertGreaterThan( 0, $id, 'Failed to create the form under test.' );

		// What the form editor's "Save" sends: the title and labels.
		$zbs->DAL->forms->addUpdateForm(
			array(
				'id'   => $id,
				'data' => array(
					'title'        => 'Newsletter sign-up, edited',
					'label_button' => 'Join',
				),
			)
		);

		$form = $zbs->DAL->forms->getForm( $id );
		$this->assertSame( 'Newsletter sign-up, edited', $form['title'] );
		$this->assertSame( 1240, $form['views'] );
		$this->assertSame( 38, $form['conversions'] );
	}

	/**
	 * @testdox Test that an update which doesn't pass a style leaves the form's style alone.
	 */
	#[TestDox( "Test that an update which doesn't pass a style leaves the form's style alone." )]
	public function test_form_save_keeps_style() {
		global $zbs;

		$id = $zbs->DAL->forms->addUpdateForm(
			array(
				'data' => array(
					'title' => 'Newsletter sign-up',
					'style' => 'naked',
				),
			)
		);

		$zbs->DAL->forms->addUpdateForm(
			array(
				'id'   => $id,
				'data' => array( 'title' => 'Newsletter sign-up, edited' ),
			)
		);

		$this->assertSame( 'naked', $zbs->DAL->forms->getForm( $id )['style'] );
	}

	/**
	 * @testdox Test that a save can still set a form's counts when it passes them.
	 */
	#[TestDox( "Test that a save can still set a form's counts when it passes them." )]
	public function test_form_save_can_set_counts() {
		global $zbs;

		$id = $zbs->DAL->forms->addUpdateForm(
			array(
				'data' => array(
					'title'       => 'Newsletter sign-up',
					'views'       => 1240,
					'conversions' => 38,
				),
			)
		);

		$zbs->DAL->forms->addUpdateForm(
			array(
				'id'   => $id,
				'data' => array(
					'title'       => 'Newsletter sign-up',
					'views'       => 0,
					'conversions' => 0,
				),
			)
		);

		$form = $zbs->DAL->forms->getForm( $id );
		$this->assertSame( 0, $form['views'] );
		$this->assertSame( 0, $form['conversions'] );
	}
}
