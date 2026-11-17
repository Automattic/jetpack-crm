<?php
/**
 * Tests for how tag names are stored.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\DAL\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Test that a tag's name is stored as plain text, whichever caller creates it.
 */
class Tag_Name_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Create a transaction tag and return its stored name.
	 *
	 * @param string $name The name to store.
	 * @return string|false The stored name, or false if no tag was made.
	 */
	private function stored_tag_name( string $name ) {
		global $zbs;

		$tag_id = $zbs->DAL->addUpdateTag( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			array(
				'data' => array(
					'objtype' => ZBS_TYPE_TRANSACTION,
					'name'    => $name,
				),
			)
		);

		if ( empty( $tag_id ) ) {
			return false;
		}

		$tag = $zbs->DAL->getTag( $tag_id, array( 'objtype' => ZBS_TYPE_TRANSACTION ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		return $tag['name'];
	}

	/**
	 * @testdox An ordinary tag name is stored as it was given.
	 */
	#[TestDox( 'An ordinary tag name is stored as it was given.' )]
	public function test_plain_name_is_unchanged() {
		$this->assertSame( 'Annual plan & support', $this->stored_tag_name( 'Annual plan & support' ) );
	}

	/**
	 * @testdox Markup in a tag name is removed before it is stored.
	 */
	#[TestDox( 'Markup in a tag name is removed before it is stored.' )]
	public function test_markup_is_removed() {
		$this->assertSame( 'Annual plan', $this->stored_tag_name( 'Annual <b>plan</b>' ) );
	}

	/**
	 * @testdox A tag name with nothing but markup in it makes no tag.
	 */
	#[TestDox( 'A tag name with nothing but markup in it makes no tag.' )]
	public function test_markup_only_name_makes_no_tag() {
		$this->assertFalse( $this->stored_tag_name( '<br>' ) );
	}
}
