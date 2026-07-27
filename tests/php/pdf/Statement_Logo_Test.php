<?php
namespace Automattic\Jetpack\CRM\PDF\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;

/**
 * The statement PDF must inline the business logo as a data: URI, never as a
 * remote URL that dompdf would fetch.
 */
class Statement_Logo_Test extends JPCRM_Base_Integration_TestCase {

	private $png_bytes;

	public function set_up(): void {
		parent::set_up();
		$this->png_bytes = base64_decode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
		);
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		parent::tear_down();
	}

	public function test_statement_inlines_logo_as_data_uri(): void {
		global $zbs;

		// Literal public IP: passes jpcrm_url_resolves_to_public_host() without
		// a DNS lookup, so the test is deterministic. The HTTP fetch itself is
		// mocked via the pre_http_request filter below.
		$logo_url = 'https://8.8.8.8/logo.png';
		$zbs->settings->update( 'invoicelogourl', $logo_url );

		add_filter(
			'pre_http_request',
			function () {
				return array(
					'body'     => $this->png_bytes,
					'response' => array( 'code' => 200, 'message' => 'OK' ),
					'headers'  => array(),
					'cookies'  => array(),
					'filename' => null,
				);
			}
		);

		$contact_id = $this->add_contact();
		$this->add_invoice( array( 'contacts' => array( $contact_id ) ) );

		$html = zeroBSCRM_invoicing_generateStatementHTML( $contact_id );

		$this->assertStringContainsString( 'data:image/png;base64,', $html );
		$this->assertStringNotContainsString( $logo_url, $html );
	}
}
