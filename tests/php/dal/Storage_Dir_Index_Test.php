<?php
/**
 * Tests for the index files written into CRM storage folders.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\DAL\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;

/**
 * CRM storage folders get both an index.php and an index.html, since a web
 * server uses whichever its directory index setting names.
 */
class Storage_Dir_Index_Test extends JPCRM_Base_TestCase {

	/**
	 * Folder created for the test.
	 *
	 * @var string
	 */
	private $dir = '';

	/**
	 * Pick a fresh folder path.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->dir = trailingslashit( get_temp_dir() ) . 'jpcrm-index-test-' . wp_generate_password( 8, false );
	}

	/**
	 * Remove the folder.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		if ( is_dir( $this->dir ) ) {
			foreach ( array_diff( scandir( $this->dir ), array( '.', '..' ) ) as $file ) {
				wp_delete_file( $this->dir . '/' . $file );
			}
			rmdir( $this->dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}
		parent::tear_down();
	}

	/**
	 * A new folder gets both index files, and the PHP one outputs nothing.
	 *
	 * @return void
	 */
	public function test_new_folder_gets_both_index_files(): void {
		$this->assertTrue( jpcrm_create_and_secure_dir_from_external_access( $this->dir, false ) );

		$this->assertFileExists( $this->dir . '/index.html' );
		$this->assertFileExists( $this->dir . '/index.php' );

		ob_start();
		include $this->dir . '/index.php';
		$this->assertSame( '', ob_get_clean() );
	}

	/**
	 * A folder created before index.php was added gets it the next time the
	 * folder is checked, and its existing index.html is left alone.
	 *
	 * @return void
	 */
	public function test_existing_folder_gains_index_php(): void {
		wp_mkdir_p( $this->dir );
		file_put_contents( $this->dir . '/index.html', 'existing' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$this->assertTrue( jpcrm_create_and_secure_dir_from_external_access( $this->dir, false ) );

		$this->assertFileExists( $this->dir . '/index.php' );
		$this->assertStringEqualsFile( $this->dir . '/index.html', 'existing' );
	}
}
