<?php
/**
 * Tests for where the contact edit screen stores uploaded files.
 *
 * Files attached to a contact belong in that contact's folder in CRM storage,
 * the same as files added from the contact's "Add file" page. The edit
 * screen's file boxes should write there directly rather than staging the
 * upload anywhere else first.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use WPDieException;

/**
 * Test the contact edit screen's file uploads.
 */
class Contact_File_Upload_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Uploads routed through wp_upload_bits() during the test.
	 *
	 * @var string[]
	 */
	private $upload_bits_names = array();

	/**
	 * Temporary file standing in for the uploaded file.
	 *
	 * @var string
	 */
	private $tmp_file = '';

	/**
	 * Sign in as an administrator and watch wp_upload_bits().
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->upload_bits_names = array();
		add_filter( 'wp_upload_bits', array( $this, 'record_upload_bits' ) );
		add_filter( 'wp_die_handler', array( $this, 'get_die_handler' ) );

		$this->tmp_file = wp_tempnam( 'contact-upload' );
		file_put_contents( $this->tmp_file, "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Clean up request state.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'wp_upload_bits', array( $this, 'record_upload_bits' ) );
		remove_filter( 'wp_die_handler', array( $this, 'get_die_handler' ) );

		$_FILES = array();
		$_POST  = array();
		$_GET   = array();
		unset( $GLOBALS['pagenow'], $GLOBALS['current_screen'] );

		if ( $this->tmp_file && file_exists( $this->tmp_file ) ) {
			wp_delete_file( $this->tmp_file );
		}

		parent::tear_down();
	}

	/**
	 * Record a wp_upload_bits() call and stop it writing anything.
	 *
	 * @param array $upload The wp_upload_bits() arguments.
	 * @return array
	 */
	public function record_upload_bits( $upload ) {
		$this->upload_bits_names[] = $upload['name'];

		return array( 'error' => 'stopped by test' );
	}

	/**
	 * Turn wp_die() into an exception.
	 *
	 * @return callable
	 */
	public function get_die_handler() {
		return static function ( $message ) {
			throw new WPDieException( is_string( $message ) ? $message : '' );
		};
	}

	/**
	 * @testdox The contact "Other Files" box saves into the contact's storage folder.
	 */
	#[TestDox( 'The contact "Other Files" box saves into the contact\'s storage folder.' )]
	public function test_files_box_saves_into_contact_storage() {
		$contact_id = $this->add_contact();

		$this->fake_upload( 'zbsc_file_attachment', 'agreement.pdf' );

		$metabox = new \zeroBS__Metabox_ContactFiles( ZBS_ROOTFILE );
		$this->save_ignoring_die( $metabox, $contact_id );

		$this->assertSame( array(), $this->upload_bits_names, 'The upload was staged outside the contact storage folder.' );
	}

	/**
	 * @testdox A contact custom file box saves into the contact's storage folder.
	 */
	#[TestDox( 'A contact custom file box saves into the contact\'s storage folder.' )]
	public function test_custom_file_box_saves_into_contact_storage() {
		global $zbs;

		if ( defined( 'ZBS_CUSTOMFILES_SAVED' ) ) {
			$this->markTestSkipped( 'The custom file boxes only save once per request.' );
		}

		$custom_fields                   = $zbs->settings->get( 'customfields' );
		$custom_fields                   = is_array( $custom_fields ) ? $custom_fields : array();
		$custom_fields['customersfiles'] = array( array( 'Signed Contract' ) );
		$zbs->settings->update( 'customfields', $custom_fields );

		$contact_id = $this->add_contact();

		// The custom file boxes only save on the contact edit screen.
		set_current_screen( 'dashboard' );
		$GLOBALS['pagenow'] = 'admin.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$_GET               = array(
			'page'    => 'zbs-add-edit',
			'action'  => 'edit',
			'zbstype' => 'contact',
		);

		$this->fake_upload( 'zbsc_file_' . $zbs->DAL->makeSlug( 'Signed Contract' ), 'contract.pdf' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$metabox = new \zeroBS__Metabox_ContactCustomFiles( ZBS_ROOTFILE );
		$this->save_ignoring_die( $metabox, $contact_id );

		$this->assertSame( array(), $this->upload_bits_names, 'The upload was staged outside the contact storage folder.' );
	}

	/**
	 * Put a file into $_FILES as if it had been posted with the contact form.
	 *
	 * Not a real HTTP upload, so the final move into storage is refused. What
	 * matters here is the route taken to get there.
	 *
	 * @param string $field     The form field name.
	 * @param string $file_name The uploaded file's name.
	 * @return void
	 */
	private function fake_upload( string $field, string $file_name ): void {
		$_FILES[ $field ] = array(
			'name'     => $file_name,
			'type'     => 'application/pdf',
			'tmp_name' => $this->tmp_file,
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize( $this->tmp_file ),
		);

		$_POST['zbsc_file_attachment_nonce'] = wp_create_nonce( plugin_basename( ZEROBSCRM_PATH . 'includes/ZeroBSCRM.MetaBoxes3.Contacts.php' ) );
	}

	/**
	 * Run a metabox save, which ends in wp_die() for a file that was not
	 * really uploaded.
	 *
	 * @param object $metabox    The metabox.
	 * @param int    $contact_id The contact ID.
	 * @return void
	 */
	private function save_ignoring_die( $metabox, int $contact_id ): void {
		try {
			$metabox->save_data( $contact_id, array() );
		} catch ( WPDieException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Expected: see fake_upload().
		}
	}
}
