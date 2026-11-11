<?php
namespace Automattic\Jetpack\CRM\PDF\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;

/**
 * Unit tests for jpcrm_pdf_logo_data_uri().
 */
class Logo_Data_URI_Test extends JPCRM_Base_TestCase {

	/**
	 * Raw bytes of a valid 1x1 PNG.
	 *
	 * @var string
	 */
	private $png_bytes;

	public function set_up(): void {
		parent::set_up();
		$this->png_bytes = base64_decode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
		);
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'option_siteurl' );
		remove_all_filters( 'option_home' );
		remove_all_filters( 'site_url' );
		parent::tear_down();
	}

	/**
	 * Register a fake HTTP response for every request during a test.
	 *
	 * @param string $body   Response body bytes.
	 * @param int    $status HTTP status code.
	 */
	private function fake_http( string $body, int $status = 200 ): void {
		add_filter(
			'pre_http_request',
			static function () use ( $body, $status ) {
				return array(
					'body'     => $body,
					'response' => array(
						'code'    => $status,
						'message' => 'OK',
					),
					'headers'  => array(),
					'cookies'  => array(),
					'filename' => null,
				);
			},
			10,
			3
		);
	}

	public function test_returns_data_uri_for_png(): void {
		$this->fake_http( $this->png_bytes );

		// Literal public IP: passes jpcrm_url_resolves_to_public_host() without a DNS lookup, so the test is deterministic. The HTTP fetch itself is mocked via pre_http_request.
		$result = jpcrm_pdf_logo_data_uri( 'https://8.8.8.8/logo.png' );

		$this->assertIsString( $result );
		$this->assertStringStartsWith( 'data:image/png;base64,', $result );
		$this->assertSame(
			'data:image/png;base64,' . base64_encode( $this->png_bytes ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$result
		);
	}

	public function test_rejects_svg(): void {
		$this->fake_http( '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>' );

		$this->assertNull( jpcrm_pdf_logo_data_uri( 'https://8.8.8.8/logo.svg' ) );
	}

	public function test_rejects_non_image_body(): void {
		$this->fake_http( '<html><body>not an image</body></html>' );

		$this->assertNull( jpcrm_pdf_logo_data_uri( 'https://8.8.8.8/page.html' ) );
	}

	public function test_rejects_non_200(): void {
		$this->fake_http( $this->png_bytes, 404 );

		$this->assertNull( jpcrm_pdf_logo_data_uri( 'https://8.8.8.8/missing.png' ) );
	}

	public function test_rejects_oversized_body(): void {
		// 2 MB cap + 1 byte, but still a "valid" PNG prefix is irrelevant: size check fires first.
		$this->fake_http( str_repeat( 'A', ( 2 * MB_IN_BYTES ) + 1 ) );

		$this->assertNull( jpcrm_pdf_logo_data_uri( 'https://8.8.8.8/huge.png' ) );
	}

	public function test_rejects_empty_url(): void {
		$this->assertNull( jpcrm_pdf_logo_data_uri( '' ) );
		$this->assertNull( jpcrm_pdf_logo_data_uri( '   ' ) );
	}

	public function test_rejects_private_host(): void {
		// Register a pre_http_request filter that declines to short-circuit
		// (returns false), so the request falls through to WP core's real
		// reject_unsafe_urls handling in WP_Http::request(), which calls
		// wp_http_validate_url() and must reject the loopback host.
		//
		// Note: unlike the original brief, this does not additionally assert
		// that the filter callback was never invoked. WP core
		// (wp-includes/class-wp-http.php) applies the pre_http_request filter
		// unconditionally, before reject_unsafe_urls validation runs, so the
		// callback always fires once registered, regardless of whether the URL
		// is ultimately safe. What matters — and what wp_http_validate_url()
		// guarantees — is that no real HTTP transport call is ever reached for
		// a blocked host.
		add_filter( 'pre_http_request', '__return_false' );

		$this->assertNull( jpcrm_pdf_logo_data_uri( 'http://127.0.0.1/logo.png' ) );
	}

	public function test_rejects_link_local_host(): void {
		// Link-local addresses (169.254.0.0/16) are not covered by
		// wp_http_validate_url(), so the jpcrm_url_resolves_to_public_host()
		// pre-check is what rejects them. The non-short-circuiting filter ensures
		// no real HTTP call is made even if the guard were ever bypassed.
		add_filter( 'pre_http_request', '__return_false' );

		$this->assertNull( jpcrm_pdf_logo_data_uri( 'http://169.254.169.254/logo.png' ) );
	}

	public function test_allows_same_site_host_even_when_private(): void {
		// Point the site at a private IP; a logo on that SAME host must still be
		// inlined. It can only pass via the same-site allowance, since a private
		// IP fails the public-host check — proving same-site is honored.
		add_filter( 'option_siteurl', array( $this, 'force_private_site_url' ) );
		add_filter( 'option_home', array( $this, 'force_private_site_url' ) );
		add_filter( 'site_url', array( $this, 'force_private_site_url' ) );
		$this->fake_http( $this->png_bytes );

		// Confirm the filters actually make site_url() resolve to the private
		// host — this is what jpcrm_dompdf_assist_validate_remote_uri() compares
		// against, so the rest of the test is meaningless if this doesn't hold.
		$this->assertSame( '10.10.10.10', wp_parse_url( site_url(), PHP_URL_HOST ) );

		$result = jpcrm_pdf_logo_data_uri( 'http://10.10.10.10/wp-content/uploads/logo.png' );

		$this->assertStringStartsWith( 'data:image/png;base64,', (string) $result );
	}

	public function force_private_site_url(): string {
		return 'http://10.10.10.10';
	}
}
