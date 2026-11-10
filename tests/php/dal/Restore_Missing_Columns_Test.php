<?php
/**
 * Tests for the migration that restores columns earlier migrations failed to add.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\DAL\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

require_once ZEROBSCRM_PATH . 'includes/ZeroBSCRM.Migrations.php';

/**
 * The restore_missing_columns migration puts back zbss_origin and zbsl_pinned.
 *
 * These tests alter the real CRM tables. ALTER TABLE commits implicitly, so the
 * test transaction can't undo it: tear_down() always puts both columns back.
 */
class Restore_Missing_Columns_Test extends JPCRM_Base_TestCase {

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
		$this->set_completed( array_diff( (array) $this->completed_before, array( 'restore_missing_columns' ) ) );
	}

	/**
	 * Put the schema and the completed list back however the test ended.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		jpcrm_migration_ensure_externalsources_origin_column();
		jpcrm_migration_ensure_logs_pinned_column();
		update_option( 'zbsmigrations', $this->completed_before, false );
		parent::tear_down();
	}

	/**
	 * @testdox Columns a completed 5.0/5.4 migration failed to add are restored, with a prefix index.
	 */
	#[TestDox( 'Columns a completed 5.0/5.4 migration failed to add are restored, with a prefix index.' )]
	public function test_missing_columns_are_restored() {
		global $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		// The customer's state: both columns gone, 5.0 and 5.4 still marked complete.
		$this->drop_origin_column();
		$this->drop_column( $ZBSCRM_t['logs'], 'zbsl_pinned' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$this->set_completed( array_unique( array_merge( $this->completed(), array( '50', '54' ) ) ) );

		zeroBSCRM_migration_restore_missing_columns();

		$this->assertTrue( zeroBSCRM_migration_tableHasColumn( $ZBSCRM_t['externalsources'], 'zbss_origin' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$this->assertTrue( zeroBSCRM_migration_tableHasColumn( $ZBSCRM_t['logs'], 'zbsl_pinned' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$this->assertSame( 191, $this->origin_index_prefix() );
		$this->assertContains( 'restore_missing_columns', $this->completed() );
	}

	/**
	 * @testdox A missing zbss_origin index is added back when the column is present.
	 */
	#[TestDox( 'A missing zbss_origin index is added back when the column is present.' )]
	public function test_missing_index_is_restored() {
		global $wpdb, $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		if ( jpcrm_migration_table_has_index( $ZBSCRM_t['externalsources'], 'zbss_origin' ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			$wpdb->query( 'ALTER TABLE ' . $ZBSCRM_t['externalsources'] . ' DROP INDEX `zbss_origin`' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		}

		zeroBSCRM_migration_restore_missing_columns();

		$this->assertSame( 191, $this->origin_index_prefix() );
		$this->assertContains( 'restore_missing_columns', $this->completed() );
	}

	/**
	 * @testdox On a healthy schema the migration changes nothing and marks itself complete.
	 */
	#[TestDox( 'On a healthy schema the migration changes nothing and marks itself complete.' )]
	public function test_healthy_schema_is_left_alone() {
		global $wpdb, $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		$before = $wpdb->get_results( 'SHOW CREATE TABLE ' . $ZBSCRM_t['externalsources'], ARRAY_N ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		zeroBSCRM_migration_restore_missing_columns();

		$after = $wpdb->get_results( 'SHOW CREATE TABLE ' . $ZBSCRM_t['externalsources'], ARRAY_N ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		$this->assertSame( $before, $after );
		$this->assertContains( 'restore_missing_columns', $this->completed() );
	}

	/**
	 * Drop zbss_origin and its index.
	 *
	 * @return void
	 */
	private function drop_origin_column(): void {
		global $wpdb, $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		if ( jpcrm_migration_table_has_index( $ZBSCRM_t['externalsources'], 'zbss_origin' ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			$wpdb->query( 'ALTER TABLE ' . $ZBSCRM_t['externalsources'] . ' DROP INDEX `zbss_origin`' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		}
		$this->drop_column( $ZBSCRM_t['externalsources'], 'zbss_origin' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	}

	/**
	 * Drop a column if it exists.
	 *
	 * @param string $table  Table name.
	 * @param string $column Column name.
	 * @return void
	 */
	private function drop_column( string $table, string $column ): void {
		global $wpdb;

		if ( zeroBSCRM_migration_tableHasColumn( $table, $column ) ) {
			$wpdb->query( 'ALTER TABLE ' . $table . ' DROP COLUMN `' . $column . '`' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		}

		$this->assertFalse( zeroBSCRM_migration_tableHasColumn( $table, $column ), "Couldn't drop $column to set up the test." );
	}

	/**
	 * The prefix length of the zbss_origin index, or null if there isn't one.
	 *
	 * @return int|null
	 */
	private function origin_index_prefix(): ?int {
		global $wpdb, $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		$row = $wpdb->get_row( $wpdb->prepare( 'SHOW INDEX FROM ' . $ZBSCRM_t['externalsources'] . ' WHERE Key_name = %s', 'zbss_origin' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		return $row ? (int) $row->Sub_part : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Completed migration keys.
	 *
	 * @return array
	 */
	private function completed(): array {
		return (array) get_option( 'zbsmigrations', array() );
	}

	/**
	 * Set the completed migration keys.
	 *
	 * @param array $keys Migration keys.
	 * @return void
	 */
	private function set_completed( array $keys ): void {
		update_option( 'zbsmigrations', array_values( $keys ), false );
	}
}
