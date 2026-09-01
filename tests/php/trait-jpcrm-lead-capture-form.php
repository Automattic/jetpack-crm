<?php
/**
 * Shared fixtures for the lead capture form endpoint.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Tests;

/**
 * Ways to put a submission through `zbs_lead_form_capture()`.
 *
 * The endpoint's argument list to the contacts DAL lives here once. It has
 * grown twice recently, and a second copy that falls behind stops testing the
 * thing it claims to.
 */
trait JPCRM_Lead_Capture_Form {

	/**
	 * Create a form for a submission to arrive against.
	 *
	 * The endpoint counts a conversion against the form it was given, so the
	 * form has to exist.
	 *
	 * @param string $style One of 'zbs_simple', 'zbs_naked', 'zbs_cgrab'.
	 * @return int The new form ID.
	 */
	protected function create_lead_capture_form( string $style = 'zbs_cgrab' ): int {
		global $zbs;

		$form_id = $zbs->DAL->forms->addUpdateForm(
			array(
				'data' => array(
					'title'       => 'Newsletter signup',
					'style'       => $style,
					'views'       => 0,
					'conversions' => 0,
				),
			)
		);

		$this->assertGreaterThan( 0, $form_id, 'Failed to create the form under test.' );

		return $form_id;
	}

	/**
	 * Put a submission through the endpoint itself.
	 *
	 * `zbs_lead_form_capture()` reads `$_POST`, so this fills it in and hands
	 * the exit-and-answer part to `capture_json_response()`. Going through the
	 * endpoint is the point: it is the only way to cover the arguments each
	 * form style passes to the contacts DAL.
	 *
	 * @param array $post The submitted fields, on top of a valid empty submission.
	 * @return array The decoded JSON response.
	 */
	protected function submit_lead_capture_form( array $post ): array {
		$original_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- simulating the public form endpoint, which has no nonce by design.

		$_POST = array_merge(
			array(
				// The honeypot is hidden from humans, so a real submission leaves it blank.
				'zbs_hpot_email' => '',
				'zbs_form_style' => 'zbs_cgrab',
				'zbs_notes'      => '',
			),
			$post
		);

		try {
			$decoded = $this->capture_json_response( 'zbs_lead_form_capture' );
		} finally {
			$_POST = $original_post;
		}

		$this->assertIsArray( $decoded, 'The endpoint did not answer with JSON.' );

		return $decoded;
	}

	/**
	 * Call the contacts DAL with the arguments the endpoint passes for a
	 * 'zbs_cgrab' form.
	 *
	 * Quicker than the endpoint, and enough where the test is about what the
	 * DAL does with those arguments rather than about which ones it is sent.
	 *
	 * @param array $fields Contact fields, `zbsc_` prefixed as the endpoint passes them.
	 * @return int|false The contact ID.
	 */
	protected function submit_lead_form( array $fields ) {
		return zeroBS_integrations_addOrUpdateCustomer(
			'form',
			$fields['zbsc_email'],
			$fields,
			'',
			'none',
			false,
			false,
			'update',
			'zbsc_',
			array( 'fname', 'lname', 'status' ),
			jpcrm_form_capture_do_not_update_blanks( $fields['zbsc_email'] )
		);
	}
}
