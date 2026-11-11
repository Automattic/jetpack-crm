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
 * Also covers how the runner treats a failed migration: recorded, and not
 * retried until an admin asks.
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
	 * The registered migration list before the test.
	 *
	 * @var array
	 */
	private $migrations_before = array();

	/**
	 * How many times the zbss_origin ALTER has been sent to the database.
	 *
	 * @var int
	 */
	private $origin_alter_attempts = 0;

	/**
	 * Remember which migrations were complete, and mark ours as not yet run.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		global $zeroBSCRM_migrations; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		$this->completed_before  = get_option( 'zbsmigrations', array() );
		$this->migrations_before = $zeroBSCRM_migrations; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$this->set_completed( array_diff( (array) $this->completed_before, array( 'restore_missing_columns' ) ) );
	}

	/**
	 * Put the schema and the completed list back however the test ended.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $wpdb, $zeroBSCRM_migrations; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		remove_filter( 'query', array( $this, 'break_origin_alter_query' ) );
		$wpdb->suppress_errors( false );
		$zeroBSCRM_migrations = $this->migrations_before; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		delete_option( 'jpcrm_migration_failures' );

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
	 * @testdox A failed ALTER is recorded with the database error, not marked complete.
	 */
	#[TestDox( 'A failed ALTER is recorded with the database error, not marked complete.' )]
	public function test_failed_alter_is_recorded() {
		$this->drop_origin_column();
		$this->break_origin_alter();

		zeroBSCRM_migration_restore_missing_columns();

		$failures = jpcrm_migrations_get_failures();
		$this->assertArrayHasKey( 'restore_missing_columns', $failures );
		$this->assertStringContainsString( 'zbss_origin', $failures['restore_missing_columns']['error'] );
		$this->assertStringNotContainsString( 'no database error reported', $failures['restore_missing_columns']['error'] );
		$this->assertNotContains( 'restore_missing_columns', $this->completed() );
	}

	/**
	 * @testdox The runner doesn't retry a failed migration until it's cleared.
	 */
	#[TestDox( "The runner doesn't retry a failed migration until it's cleared." )]
	public function test_runner_waits_for_retry() {
		global $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		$this->only_run_restore_migration();
		$this->drop_origin_column();
		$this->break_origin_alter();

		zeroBSCRM_migrations_run( false, 'init' );
		zeroBSCRM_migrations_run( false, 'wp_loaded' );
		zeroBSCRM_migrations_run( false, 'init' );

		$this->assertSame( 1, $this->origin_alter_attempts );
		$this->assertArrayHasKey( 'restore_missing_columns', jpcrm_migrations_get_failures() );

		// whatever was wrong is fixed, and an admin retries.
		remove_filter( 'query', array( $this, 'break_origin_alter_query' ) );
		jpcrm_migration_clear_failure( 'restore_missing_columns' );
		zeroBSCRM_migrations_run( false, 'init' );

		$this->assertTrue( zeroBSCRM_migration_tableHasColumn( $ZBSCRM_t['externalsources'], 'zbss_origin' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$this->assertContains( 'restore_missing_columns', $this->completed() );
		$this->assertSame( array(), jpcrm_migrations_get_failures() );
	}

	/**
	 * @testdox A retry link attempts a still-failing migration once per request, not once per runner pass.
	 */
	#[TestDox( 'A retry link attempts a still-failing migration once per request, not once per runner pass.' )]
	public function test_retry_link_attempts_once_per_request() {
		$this->only_run_restore_migration();
		$this->drop_origin_column();
		$this->break_origin_alter();

		zeroBSCRM_migrations_run( false, 'init' );
		$this->assertSame( 1, $this->origin_alter_attempts );

		// an admin follows the retry link: the runner fires at init, then again at wp_loaded.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_GET['jpcrm_retry_migration'] = 'restore_missing_columns';
		$_GET['_wpnonce']              = wp_create_nonce( 'jpcrm_retry_migration_restore_missing_columns' );

		try {
			zeroBSCRM_migrations_run( false, 'init' );
			zeroBSCRM_migrations_run( false, 'wp_loaded' );
		} finally {
			unset( $_GET['jpcrm_retry_migration'], $_GET['_wpnonce'] );
		}

		$this->assertSame( 2, $this->origin_alter_attempts );
		$this->assertArrayHasKey( 'restore_missing_columns', jpcrm_migrations_get_failures() );
	}

	/**
	 * Make restore_missing_columns the only migration the runner knows about.
	 *
	 * @return void
	 */
	private function only_run_restore_migration(): void {
		global $zeroBSCRM_migrations; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		$zeroBSCRM_migrations = array( 'restore_missing_columns' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	}

	/**
	 * Make the ALTER that adds zbss_origin fail with a database error, and count attempts.
	 *
	 * @return void
	 */
	private function break_origin_alter(): void {
		global $wpdb;

		$wpdb->suppress_errors( true );
		add_filter( 'query', array( $this, 'break_origin_alter_query' ) );
	}

	/**
	 * Query filter: turn the zbss_origin ALTER into invalid SQL.
	 *
	 * @param string $query SQL query.
	 * @return string
	 */
	public function break_origin_alter_query( $query ) {
		if ( str_contains( $query, 'ADD COLUMN `zbss_origin`' ) ) {
			++$this->origin_alter_attempts;
			return str_replace( 'VARCHAR(400)', 'NOT_A_TYPE', $query );
		}
		return $query;
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
