<?php
/**
 * Tests for the migration that restores the external sources uid index.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\DAL\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

require_once ZEROBSCRM_PATH . 'includes/ZeroBSCRM.Migrations.php';

/**
 * The restore_externalsources_uid_index migration puts back the uid+source+objtype index.
 *
 * These tests alter the real CRM tables. ALTER TABLE commits implicitly, so the
 * test transaction can't undo it: tear_down() always puts the index back.
 */
class Restore_Uid_Index_Test extends JPCRM_Base_TestCase {

	const INDEX = 'zbss_uid+zbss_source+zbss_objtype';

	/**
	 * Migrations completed before the test.
	 *
	 * @var array
	 */
	private $completed_before = array();

	/**
	 * Remember which migrations were complete, and mark ours as not yet run.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->completed_before = get_option( 'zbsmigrations', array() );
		update_option( 'zbsmigrations', array_values( array_diff( (array) $this->completed_before, array( 'restore_externalsources_uid_index' ) ) ), false );
	}

	/**
	 * Put the index, the completed list and any failure record back.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $wpdb;

		remove_filter( 'query', array( $this, 'break_index_alter_query' ) );
		$wpdb->suppress_errors( false );
		delete_option( 'jpcrm_migration_failures' );

		jpcrm_migration_ensure_externalsources_uid_index();
		update_option( 'zbsmigrations', $this->completed_before, false );
		parent::tear_down();
	}

	/**
	 * @testdox A missing uid index is restored with a 191-char prefix on zbss_uid.
	 */
	#[TestDox( 'A missing uid index is restored with a 191-char prefix on zbss_uid.' )]
	public function test_missing_index_is_restored() {
		$this->drop_index();

		zeroBSCRM_migration_restore_externalsources_uid_index();

		$this->assertSame(
			array(
				'zbss_uid'     => 191,
				'zbss_source'  => null,
				'zbss_objtype' => null,
			),
			$this->index_columns()
		);
		$this->assertContains( 'restore_externalsources_uid_index', (array) get_option( 'zbsmigrations' ) );
	}

	/**
	 * @testdox An existing index, even full-width, is left alone.
	 */
	#[TestDox( 'An existing index, even full-width, is left alone.' )]
	public function test_existing_index_is_left_alone() {
		global $wpdb, $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		$this->drop_index();
		$wpdb->query( 'ALTER TABLE ' . $ZBSCRM_t['externalsources'] . ' ADD INDEX `' . self::INDEX . '` (`zbss_uid`,`zbss_source`,`zbss_objtype`)' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		zeroBSCRM_migration_restore_externalsources_uid_index();

		$this->assertSame(
			array(
				'zbss_uid'     => null,
				'zbss_source'  => null,
				'zbss_objtype' => null,
			),
			$this->index_columns()
		);
		$this->assertContains( 'restore_externalsources_uid_index', (array) get_option( 'zbsmigrations' ) );
	}

	/**
	 * @testdox A failed ALTER is recorded with the database error, not marked complete.
	 */
	#[TestDox( 'A failed ALTER is recorded with the database error, not marked complete.' )]
	public function test_failed_alter_is_recorded() {
		global $wpdb;

		$this->drop_index();
		$wpdb->suppress_errors( true );
		add_filter( 'query', array( $this, 'break_index_alter_query' ) );

		zeroBSCRM_migration_restore_externalsources_uid_index();

		$failures = jpcrm_migrations_get_failures();
		$this->assertArrayHasKey( 'restore_externalsources_uid_index', $failures );
		$this->assertStringContainsString( 'Could not add index ' . self::INDEX, $failures['restore_externalsources_uid_index']['error'] );
		$this->assertStringNotContainsString( 'no database error reported', $failures['restore_externalsources_uid_index']['error'] );
		$this->assertNotContains( 'restore_externalsources_uid_index', (array) get_option( 'zbsmigrations' ) );
	}

	/**
	 * Query filter: turn the index ALTER into invalid SQL.
	 *
	 * @param string $query SQL query.
	 * @return string
	 */
	public function break_index_alter_query( $query ) {
		if ( str_contains( $query, 'ADD INDEX `' . self::INDEX . '`' ) ) {
			return str_replace( 'USING BTREE', 'USING NOT_AN_INDEX_TYPE', $query );
		}
		return $query;
	}

	/**
	 * Drop the uid index if it exists.
	 *
	 * @return void
	 */
	private function drop_index(): void {
		global $wpdb, $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		if ( jpcrm_migration_table_has_index( $ZBSCRM_t['externalsources'], self::INDEX ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			$wpdb->query( 'ALTER TABLE ' . $ZBSCRM_t['externalsources'] . ' DROP INDEX `' . self::INDEX . '`' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		}

		$this->assertFalse( jpcrm_migration_table_has_index( $ZBSCRM_t['externalsources'], self::INDEX ), "Couldn't drop the index to set up the test." ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	}

	/**
	 * The index's columns, in order, mapped to their prefix length (null for full width).
	 *
	 * @return array<string, int|null>
	 */
	private function index_columns(): array {
		global $wpdb, $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		$rows = $wpdb->get_results( $wpdb->prepare( 'SHOW INDEX FROM ' . $ZBSCRM_t['externalsources'] . ' WHERE Key_name = %s', self::INDEX ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		$columns = array();
		foreach ( $rows as $row ) {
			$columns[ $row->Column_name ] = null === $row->Sub_part ? null : (int) $row->Sub_part; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		return $columns;
	}
}
