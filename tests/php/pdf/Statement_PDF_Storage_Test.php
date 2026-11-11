<?php
/**
 * Tests for where statement PDFs are built.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\PDF\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;

/**
 * Statement PDFs are built in the CRM's own working folder, alongside the
 * other PDFs the CRM generates, and not in the media library's folder.
 */
class Statement_PDF_Storage_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Sign in as an administrator.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * The statement PDF is built in the CRM working folder.
	 *
	 * @return void
	 */
	public function test_statement_pdf_is_built_in_crm_storage(): void {
		$contact_id = $this->add_contact();
		$this->add_invoice( array( 'contacts' => array( $contact_id ) ) );

		$pdf_path = zeroBSCRM_invoicing_generateStatementPDF( $contact_id, false );

		$this->assertIsString( $pdf_path );
		$this->assertFileExists( $pdf_path );

		$work_dir = zeroBSCRM_privatisedDirCheckWorks();
		$uploads  = wp_upload_dir();

		$this->assertStringStartsWith( trailingslashit( $work_dir['path'] ), $pdf_path );
		$this->assertStringStartsNotWith( trailingslashit( $uploads['basedir'] ), $pdf_path );

		wp_delete_file( $pdf_path );
	}
}
