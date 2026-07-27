<?php
namespace Automattic\Jetpack\CRM\PDF\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;

/**
 * Invoice PDF HTML must inline the logo as a data: URI; the portal (browser)
 * HTML must keep the raw URL and trigger no server-side fetch.
 */
class Invoice_Logo_Test extends JPCRM_Base_Integration_TestCase {

	private $png_bytes;
	// Literal public IP: passes jpcrm_url_resolves_to_public_host() without a
	// DNS lookup, so the test is deterministic. The HTTP fetch itself is
	// mocked via the pre_http_request filter below.
	private $logo_url = 'https://8.8.8.8/logo.png';
	private $http_calls = 0;

	public function set_up(): void {
		parent::set_up();
		$this->png_bytes  = base64_decode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
		);
		$this->http_calls = 0;

		add_filter(
			'pre_http_request',
			function () {
				++$this->http_calls;
				return array(
					'body'     => $this->png_bytes,
					'response' => array( 'code' => 200, 'message' => 'OK' ),
					'headers'  => array(),
					'cookies'  => array(),
					'filename' => null,
				);
			}
		);
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		parent::tear_down();
	}

	private function make_invoice_with_logo(): int {
		$contact_id = $this->add_contact();
		// Set the per-invoice logo so the assembler's logo block renders it.
		return $this->add_invoice(
			array(
				'contacts' => array( $contact_id ),
				'logo_url' => $this->logo_url,
			)
		);
	}

	public function test_pdf_template_inlines_data_uri(): void {
		$invoice_id = $this->make_invoice_with_logo();

		// The assembler requires an already-loaded template ($html !== ''),
		// same as its real callers (zeroBSCRM_invoice_generateInvoiceHTML_v3).
		$templated_html = jpcrm_retrieve_template( 'invoices/invoice-pdf.html', false );
		$html           = zeroBSCRM_invoicing_generateInvoiceHTML( $invoice_id, 'pdf', $templated_html );

		$this->assertStringContainsString( 'data:image/png;base64,', $html );
		$this->assertStringNotContainsString( $this->logo_url, $html );
		$this->assertSame( 1, $this->http_calls, 'PDF render should fetch the logo exactly once.' );
	}

	public function test_portal_template_keeps_remote_url_and_makes_no_fetch(): void {
		$invoice_id = $this->make_invoice_with_logo();

		// Same requirement as above, using the portal template's placeholders
		// (zeroBSCRM_invoice_generatePortalInvoiceHTML_v3's real code path).
		$templated_html = jpcrm_retrieve_template( 'invoices/portal-invoice.html', false );
		$html           = zeroBSCRM_invoicing_generateInvoiceHTML( $invoice_id, 'portal', $templated_html );

		$this->assertStringContainsString( $this->logo_url, $html );
		$this->assertStringNotContainsString( 'data:image/png;base64,', $html );
		$this->assertSame( 0, $this->http_calls, 'Portal render must not fetch the logo server-side.' );
	}
}
