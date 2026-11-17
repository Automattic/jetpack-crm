<?php
/**
 * Tests for the company introduction sentence.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\FormatHelpers\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * The sentence under a company's name on its view page links its email
 * address. Whatever is stored there is shown as an address and nothing else.
 */
class Company_Intro_Sentence_Test extends JPCRM_Base_TestCase {

	/**
	 * Render the sentence for a company with the given email.
	 *
	 * @param string $email The stored email value.
	 */
	private function render( string $email ): string {
		return zeroBSCRM_html_companyIntroSentence(
			array(
				'id'      => 1,
				'email'   => $email,
				'created' => 0,
			)
		);
	}

	/**
	 * @testdox An email address is linked as before.
	 */
	#[TestDox( 'An email address is linked as before.' )]
	public function test_an_email_address_is_linked() {
		$this->assertSame(
			'Email:<a href="mailto:hello@example.test" class="coemail"> hello@example.test</a><br/>',
			$this->render( 'hello@example.test' )
		);
	}

	/**
	 * @testdox A stored value with markup characters is printed as text.
	 */
	#[TestDox( 'A stored value with markup characters is printed as text.' )]
	public function test_a_value_with_markup_characters_is_printed_as_text() {
		$html = $this->render( 'a"b title="c' );

		$document = new \DOMDocument();
		$document->loadHTML( '<div>' . $html . '</div>', LIBXML_NOERROR );
		$link = $document->getElementsByTagName( 'a' )->item( 0 );

		$this->assertNotNull( $link, 'The email is no longer linked.' );
		$this->assertSame( array( 'href', 'class' ), array_keys( iterator_to_array( $link->attributes ) ), 'The stored value added an attribute to the link.' );
		$this->assertSame( ' a"b title="c', $link->textContent, 'The stored value is not shown as it was entered.' );
	}
}
