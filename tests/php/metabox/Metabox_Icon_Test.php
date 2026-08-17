<?php
/**
 * Tests for metabox icons being carried as their own field.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Metabox\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;
use zeroBS__Metabox;

/**
 * Metaboxes used to have their icon markup concatenated into the title string,
 * which made every render point an HTML sink. These tests pin the replacement:
 * the icon is its own key on the box, and titles are plain text.
 */
class Metabox_Icon_Test extends JPCRM_Base_TestCase {

	/**
	 * Reset the metabox registry and load what wp-admin brings.
	 */
	public function set_up(): void {
		parent::set_up();

		// zeroBSCRM_add_meta_box() and the render functions both reach into
		// wp-admin, which the test bootstrap does not load for us.
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		require_once ABSPATH . 'wp-admin/includes/template.php';

		global $zbs;
		$zbs->metaboxes = array();
	}

	/**
	 * Render a metabox and hand back the HTML it echoed.
	 *
	 * @param array  $box  A box array as stored in $zbs->metaboxes.
	 * @param string $page Screen the box is being rendered on.
	 * @return string
	 */
	private function render_box( array $box, string $page = 'zbs-view-contact' ): string {
		ob_start();
		zeroBSCRM_do_meta_box_html( $box, $page, array(), null, array() );
		return ob_get_clean();
	}

	/**
	 * Build a box array of the shape zeroBSCRM_add_meta_box() stores.
	 *
	 * @param array $overrides Keys to override on the default box.
	 * @return array
	 */
	private function make_box( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'           => 'zbs-test-box',
				'title'        => 'Activity',
				'icon'         => 'heartbeat',
				'callback'     => '__return_null',
				'args'         => array(),
				'headless'     => false,
				'extraclasses' => '',
				'capabilities' => array( 'can_minimise' => true ),
			),
			$overrides
		);
	}


	/**
	 * A box without an icon key gets no icon markup.
	 */
	public function test_icon_html_is_empty_when_the_box_has_no_icon_key() {
		$this->assertSame( '', jpcrm_metabox_icon_html( array() ) );
	}

	/**
	 * An empty icon gets no icon markup either.
	 */
	public function test_icon_html_is_empty_when_the_icon_is_an_empty_string() {
		$this->assertSame( '', jpcrm_metabox_icon_html( array( 'icon' => '' ) ) );
	}

	/**
	 * An icon renders as a Semantic UI <i>, hidden from screen readers.
	 */
	public function test_icon_html_renders_a_decorative_semantic_ui_icon() {
		$this->assertSame(
			'<i class="heartbeat icon" aria-hidden="true"></i> ',
			jpcrm_metabox_icon_html( array( 'icon' => 'heartbeat' ) )
		);
	}

	/**
	 * The icon name is escaped, so it cannot break out of the class attribute.
	 */
	public function test_icon_html_escapes_the_class_attribute() {
		$html = jpcrm_metabox_icon_html( array( 'icon' => 'heartbeat" onmouseover="alert(1)' ) );

		// Nothing may escape the class attribute, so the whole thing is one value.
		$this->assertSame(
			'<i class="heartbeat&quot; onmouseover=&quot;alert(1) icon" aria-hidden="true"></i> ',
			$html
		);
	}


	/**
	 * Registration keeps the icon and the title apart.
	 */
	public function test_add_meta_box_stores_the_icon_alongside_the_title() {
		global $zbs;

		zeroBSCRM_add_meta_box( 'zbs-test-box', 'Activity', '__return_null', 'zbs-view-contact', 'side', 'high', array(), false, '', false, 'heartbeat' );

		$box = $zbs->metaboxes['zbs-view-contact']['side']['high']['zbs-test-box'];

		$this->assertSame( 'heartbeat', $box['icon'] );
		$this->assertSame( 'Activity', $box['title'] );
	}

	/**
	 * Callers that pass no icon still get an icon key.
	 */
	public function test_add_meta_box_defaults_the_icon_to_an_empty_string() {
		global $zbs;

		zeroBSCRM_add_meta_box( 'zbs-test-box', 'Activity', '__return_null', 'zbs-view-contact', 'side', 'high' );

		$this->assertSame( '', $zbs->metaboxes['zbs-view-contact']['side']['high']['zbs-test-box']['icon'] );
	}

	/**
	 * Registering against an array of screens keeps the icon on each.
	 */
	public function test_add_meta_box_keeps_the_icon_when_registering_against_several_screens() {
		global $zbs;

		zeroBSCRM_add_meta_box(
			'zbs-test-box',
			'Activity',
			'__return_null',
			array( 'zbs-view-contact', 'zbs-view-company' ),
			'side',
			'high',
			array(),
			false,
			'',
			false,
			'heartbeat'
		);

		$this->assertSame( 'heartbeat', $zbs->metaboxes['zbs-view-contact']['side']['high']['zbs-test-box']['icon'] );
		$this->assertSame( 'heartbeat', $zbs->metaboxes['zbs-view-company']['side']['high']['zbs-test-box']['icon'] );
	}

	/**
	 * Re-registering at 'sorted' priority carries the icon over with the title.
	 *
	 * A 'sorted' caller passes no title, callback or icon: the box is being moved
	 * to where the user dragged it, so those are read back off the existing
	 * registration. Miss the icon there and a dragged box loses it. The only
	 * caller is commented out today, which is exactly why this needs pinning.
	 */
	public function test_add_meta_box_keeps_the_icon_when_re_registering_as_sorted() {
		global $zbs;

		zeroBSCRM_add_meta_box( 'zbs-test-box', 'Activity', '__return_null', 'zbs-view-contact', 'side', 'high', array(), false, '', false, 'heartbeat' );

		// The shape the sorted caller uses: id, screen, context and 'sorted', nothing else.
		zeroBSCRM_add_meta_box( 'zbs-test-box', null, null, 'zbs-view-contact', 'side', 'sorted' );

		$box = $zbs->metaboxes['zbs-view-contact']['side']['sorted']['zbs-test-box'];

		$this->assertSame( 'heartbeat', $box['icon'] );
		$this->assertSame( 'Activity', $box['title'] );

		// The box moved priority rather than being duplicated.
		$this->assertArrayNotHasKey( 'zbs-test-box', $zbs->metaboxes['zbs-view-contact']['side']['high'] );
	}


	/**
	 * Init no longer prepends icon markup to the title.
	 */
	public function test_init_metabox_leaves_the_title_as_plain_text() {
		global $zbs;

		$metabox                  = new class() extends zeroBS__Metabox {}; // phpcs:ignore Squiz.WhiteSpace.SemicolonSpacing.Incorrect
		$metabox->metaboxID       = 'zbs-test-box';
		$metabox->metaboxTitle    = 'Activity';
		$metabox->metaboxIcon     = 'heartbeat';
		$metabox->metaboxScreen   = 'zbs-view-contact';
		$metabox->metaboxArea     = 'side';
		$metabox->metaboxLocation = 'high';
		$metabox->initMetabox();

		$this->assertSame( 'Activity', $metabox->metaboxTitle );

		$box = $zbs->metaboxes['zbs-view-contact']['side']['high']['zbs-test-box'];
		$this->assertSame( 'Activity', $box['title'] );
		$this->assertSame( 'heartbeat', $box['icon'] );
	}

	/**
	 * Register one of the shipped icon-bearing metaboxes and check what it produces.
	 *
	 * The box body is not what these tests are about, so the callback is swapped
	 * out before rendering: it wants a real contact or company to report on.
	 *
	 * @param string $class_name Metabox class.
	 * @param string $screen     Screen it registers against.
	 * @param string $metabox_id Its metabox ID.
	 */
	private function assert_shipped_metabox_renders_its_icon( $class_name, $screen, $metabox_id ): void {
		global $zbs;

		new $class_name( ZBS_ROOTFILE );

		$box = $zbs->metaboxes[ $screen ]['side']['high'][ $metabox_id ];

		$this->assertSame( 'heartbeat', $box['icon'] );
		$this->assertStringNotContainsString( '<i', $box['title'] );

		$box['callback'] = '__return_null';

		$html = $this->render_box( $box, $screen );

		$this->assertStringContainsString( '<i class="heartbeat icon" aria-hidden="true"></i> ', $html );
		$this->assertStringNotContainsString( '&lt;i class=', $html );
	}

	/**
	 * The shipped contact Activity metabox still shows its heartbeat.
	 */
	public function test_contact_activity_metabox_registers_and_renders_its_icon() {
		$this->assert_shipped_metabox_renders_its_icon(
			\zeroBS__Metabox_Contact_Activity::class,
			'zbs-view-contact',
			'zbs-contact-activity-metabox'
		);
	}

	/**
	 * The shipped company Activity metabox still shows its heartbeat.
	 */
	public function test_company_activity_metabox_registers_and_renders_its_icon() {
		$this->assert_shipped_metabox_renders_its_icon(
			\zeroBS__Metabox_Company_Activity::class,
			'zerobs_view_company',
			'zbs-company-activity-metabox'
		);
	}


	/**
	 * The box header renders the icon in front of the title.
	 */
	public function test_metabox_header_renders_the_icon() {
		$html = $this->render_box( $this->make_box() );

		$this->assertStringContainsString( '<i class="heartbeat icon" aria-hidden="true"></i> Activity', $html );
	}

	/**
	 * The box header escapes the title.
	 */
	public function test_metabox_header_escapes_the_title() {
		$html = $this->render_box( $this->make_box( array( 'title' => 'Activity <script>alert(1)</script>' ) ) );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	/**
	 * The drag-drop blocker overlay renders the icon and escapes the title.
	 */
	public function test_drag_blocker_renders_the_icon_and_escapes_the_title() {
		$html = $this->render_box( $this->make_box( array( 'title' => 'Activity <b>x</b>' ) ) );

		// The blocker is the element that covers a box while metaboxes are dragged.
		$this->assertMatchesRegularExpression(
			'~class="zbs-metabox-block"><div><i class="heartbeat icon" aria-hidden="true"></i> Activity &lt;b&gt;x&lt;/b&gt;</div>~',
			$html
		);
	}

	/**
	 * A headless box has no header, but its blocker still shows the icon.
	 */
	public function test_headless_metabox_still_renders_the_blocker_icon() {
		$html = $this->render_box( $this->make_box( array( 'headless' => true ) ) );

		$this->assertStringNotContainsString( 'zbs-metabox-head', $html );
		$this->assertStringContainsString( '<i class="heartbeat icon" aria-hidden="true"></i> Activity', $html );
	}

	/**
	 * A tab head renders the icon and escapes the title.
	 */
	public function test_tab_head_renders_the_icon_and_escapes_the_title() {
		ob_start();
		zeroBSCRM_do_meta_box_htmlTabHead(
			'zbs-test-tabs',
			array(
				'boxes' => array(
					$this->make_box( array( 'title' => 'Activity <b>x</b>' ) ),
				),
			)
		);
		$html = ob_get_clean();

		$this->assertStringContainsString( '<i class="heartbeat icon" aria-hidden="true"></i> Activity &lt;b&gt;x&lt;/b&gt;', $html );
	}


	/**
	 * The screen options list renders the icon and escapes the title.
	 */
	public function test_screen_options_list_renders_the_icon_and_escapes_the_title() {
		$html = jpcrm_screen_options_metabox_list_html(
			array( 'zbs-test-box' => $this->make_box( array( 'title' => 'Activity <b>x</b>' ) ) ),
			array(),
			'Main Column'
		);

		$this->assertStringContainsString( '<i class="heartbeat icon" aria-hidden="true"></i> Activity &lt;b&gt;x&lt;/b&gt;', $html );
		$this->assertStringNotContainsString( '<b>x</b>', $html );
	}

	/**
	 * The screen options list escapes the metabox ID it puts in attributes.
	 */
	public function test_screen_options_list_escapes_the_metabox_id() {
		$html = jpcrm_screen_options_metabox_list_html(
			array( 'zbs-test"box' => $this->make_box() ),
			array(),
			'Main Column'
		);

		$this->assertStringNotContainsString( 'id="zbs-mb-zbs-test"box"', $html );
		$this->assertStringContainsString( 'zbs-test&quot;box', $html );
	}

	/**
	 * A hidden metabox comes back unchecked.
	 */
	public function test_screen_options_list_marks_a_hidden_metabox_unchecked() {
		$html = jpcrm_screen_options_metabox_list_html(
			array( 'zbs-test-box' => $this->make_box() ),
			array( 'zbs-test-box' ),
			'Main Column'
		);

		$this->assertStringNotContainsString( 'checked="checked"', $html );
	}

	/**
	 * A metabox that cannot be hidden comes back checked and disabled.
	 */
	public function test_screen_options_list_disables_a_metabox_that_cannot_be_hidden() {
		$html = jpcrm_screen_options_metabox_list_html(
			array(
				'zbs-test-box' => $this->make_box( array( 'capabilities' => array( 'can_hide' => false ) ) ),
			),
			array(),
			'Main Column'
		);

		$this->assertStringContainsString( 'disabled="disabled"', $html );
	}
}
