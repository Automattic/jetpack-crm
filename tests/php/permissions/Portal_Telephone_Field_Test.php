<?php
/**
 * Tests for the client portal's telephone field rendering.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Tests;

use Automattic\JetpackCRM\Details_Endpoint;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;

/**
 * Test what the portal "Your Details" page renders for a mobile number.
 *
 * The page is shown to the contact themselves. They cannot send SMS, so the
 * field must render as a plain input: no send button and no send nonce.
 */
class Portal_Telephone_Field_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Load the portal endpoint classes, which only load with the module.
	 *
	 * @return void
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		if ( ! class_exists( Details_Endpoint::class ) ) {
			require_once ZEROBSCRM_PATH . 'modules/portal/class-client-portal-endpoint.php';
			require_once ZEROBSCRM_PATH . 'modules/portal/endpoints/class-details-endpoint.php';
		}
	}

	/**
	 * Render the telephone field for a contact with a mobile number.
	 *
	 * @return string The rendered markup.
	 */
	private function render_mobile_field(): string {

		// render_telephone_field() does not touch endpoint state, so skip the
		// constructor and its portal wiring.
		$endpoint = ( new ReflectionClass( Details_Endpoint::class ) )->newInstanceWithoutConstructor();

		// A stored contact, so the render has a real mobile number to offer.
		$contact_id = $this->add_contact( array( 'mobtel' => '5551234567' ) );
		$contact    = array(
			'id'     => $contact_id,
			'mobtel' => '5551234567',
		);

		ob_start();
		$endpoint->render_telephone_field( 'mobtel', array( 'tel', 'Mobile Phone', 'Mobile Phone' ), '5551234567', $contact );
		return ob_get_clean();
	}

	/**
	 * @return void
	 */
	#[TestDox( 'The mobile field renders as a plain input for the contact.' )]
	public function test_mobile_field_renders_the_input() {

		$html = $this->render_mobile_field();

		$this->assertStringContainsString( 'zbsc_mobtel', $html, 'The mobile input did not render.' );
		$this->assertStringContainsString( 'value="5551234567"', $html, 'The mobile input lost its value.' );
	}

	/**
	 * @return void
	 */
	#[TestDox( 'The mobile field renders no SMS button.' )]
	public function test_mobile_field_renders_no_sms_button() {

		$html = $this->render_mobile_field();

		$this->assertStringNotContainsString( 'data-smsnum', $html, 'The field rendered an SMS button.' );
		$this->assertStringNotContainsString( 'send-sms', $html, 'The field rendered an SMS button class.' );
	}

	/**
	 * The nonce action is what the Twilio extension prints a send nonce on.
	 * The portal page must not fire it.
	 *
	 * @return void
	 */
	#[TestDox( 'The mobile field fires no Twilio nonce action.' )]
	public function test_mobile_field_fires_no_twilio_hooks() {

		$nonce_fired  = did_action( 'zbs_twilio_nonce' );
		$sms_filtered = 0;
		$callback     = static function ( $value ) use ( &$sms_filtered ) {
			++$sms_filtered;
			return $value;
		};

		add_filter( 'zbs_twilio_sms', $callback );

		try {
			$this->render_mobile_field();
		} finally {
			remove_filter( 'zbs_twilio_sms', $callback );
		}

		$this->assertSame( $nonce_fired, did_action( 'zbs_twilio_nonce' ), 'The render fired the Twilio nonce action.' );
		$this->assertSame( 0, $sms_filtered, 'The render ran the Twilio SMS class filter.' );
	}
}
