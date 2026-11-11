<?php
/**
 * Tests for the Client Portal login template.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\TestDox;

/**
 * Fields other plugins add on `login_form` must be submitted with the portal login.
 *
 * Brute force protection and 2FA plugins add their challenge fields on
 * `login_form`. Printed outside the <form>, they never reach wp-login.php and the
 * login fails or asks again.
 */
class Portal_Login_Form_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * @testdox Fields added on login_form render once, inside the form, before the Log In button, and only there.
	 *
	 * One test, because the template declares a function and can only be included once per run.
	 */
	#[TestDox( 'Fields added on login_form render once, inside the form, before the Log In button, and only there.' )]
	public function test_login_form_fields_are_inside_the_form() {
		add_action(
			'login_form',
			static function () {
				echo '<input type="hidden" name="jpcrm_test_challenge" value="1" />';
			}
		);

		// The template links back to the portal page.
		global $zbs;
		$zbs->settings->update( 'portalpage', self::factory()->post->create( array( 'post_type' => 'page' ) ) );

		ob_start();
		include ZEROBSCRM_PATH . 'modules/portal/templates/login.php';
		$html = ob_get_clean();

		$field  = strpos( $html, 'jpcrm_test_challenge' );
		$submit = strpos( $html, 'id="wp-submit"' );
		$close  = strpos( $html, '</form>' );

		$this->assertSame( 1, substr_count( $html, 'jpcrm_test_challenge' ), 'The login_form fields should render exactly once.' );
		$this->assertNotFalse( $submit );
		$this->assertNotFalse( $close );
		$this->assertLessThan( $submit, $field, 'The login_form fields should come before the Log In button.' );
		$this->assertLessThan( $close, $field, 'The login_form fields should be inside the form.' );

		// Other login forms on the page (a widget, say) must not get them too.
		$this->assertFalse( has_filter( 'login_form_middle' ) );
	}
}
