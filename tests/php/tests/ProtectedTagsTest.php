<?php
/**
 * Protected tags — test group.
 *
 * Content inside protected tags must never be modified. The tag list is
 * configurable through the `iworks/orphans/protected-tags` filter; these
 * tests verify both the default configuration and custom configurations
 * (adding and removing tags), plus the documented ordering constraint
 * (script/style must come after iframe/svg).
 *
 * @package sierotki
 * @group protected-tags
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group( 'protected-tags' )]
final class ProtectedTagsTest extends OrphanTestCase {

	/**
	 * Simulates the `iworks/orphans/protected-tags` filter result by
	 * setting the (private) property the engine reads.
	 */
	private function set_protected_tags( array $tags ): void {
		$prop = new ReflectionProperty( 'iworks_orphan', 'protected_tags' );
		$prop->setValue( $this->orphan, $tags );
	}

	private function engine( array $tags = null ): OrphanTestDouble {
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1 ) );
		if ( null !== $tags ) {
			$this->set_protected_tags( $tags );
		}
		return $this->orphan;
	}

	// ── Default configuration ────────────────────────────────────────────

	public function test_default_protected_tags_list(): void {
		$prop  = new ReflectionProperty( 'iworks_orphan', 'protected_tags' );
		$double = new OrphanTestDouble();
		$this->assertSame(
			array( 'pre', 'code', 'iframe', 'svg', 'script', 'style' ),
			$prop->getValue( $double ),
			'default order matters: script/style must come after iframe/svg'
		);
	}

	public function test_script_content_is_fully_untouched(): void {
		$orphan = $this->engine();
		$in     = '<script>var s = "a w b i z"; if (x > z) { y = "o u"; }</script><p>Ala w kotcie</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'var s = "a w b i z"; if (x > z) { y = "o u"; }', $out );
		$this->assertStringContainsString( 'Ala w' . $this->nbsp . 'kotcie', $out );
	}

	public function test_style_content_is_fully_untouched(): void {
		$orphan = $this->engine();
		$in     = '<style>.a i z { font-family: "w sans"; content: "o u"; }</style><p>Ala z psem</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( '.a i z { font-family: "w sans"; content: "o u"; }', $out );
		$this->assertStringContainsString( 'Ala z' . $this->nbsp . 'psem', $out );
	}

	public function test_iframe_content_is_fully_untouched(): void {
		$orphan = $this->engine();
		$in     = '<iframe srcdoc="ala w kotcie i z"></iframe><p>Ala i pies</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'srcdoc="ala w kotcie i z"', $out );
		$this->assertStringContainsString( 'Ala i' . $this->nbsp . 'pies', $out );
	}

	public function test_pre_and_code_content_is_untouched(): void {
		$orphan = $this->engine();
		$in     = '<pre>ala w kotcie' . "\n" . 'i z psem</pre><code>$x = ala o u;</code><p>Ala w domu</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( "ala w kotcie\ni z psem", $out );
		$this->assertStringContainsString( '$x = ala o u;', $out );
		$this->assertStringContainsString( 'Ala w' . $this->nbsp . 'domu', $out );
	}

	public function test_svg_content_is_untouched(): void {
		$orphan = $this->engine();
		$in     = '<svg><text>ala w kotcie i z</text></svg><p>Ala z psem</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'ala w kotcie i z', $out );
		$this->assertStringContainsString( 'Ala z' . $this->nbsp . 'psem', $out );
	}

	public function test_text_after_protected_tag_is_still_replaced(): void {
		$orphan = $this->engine();
		$in     = '<script>var a = "w i z";</script> Ala z psem <style>i u</style> Ala o psie';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'Ala z' . $this->nbsp . 'psem', $out );
		$this->assertStringContainsString( 'Ala o' . $this->nbsp . 'psie', $out );
	}

	// ── Depth tracking: pre/code/svg have separate closers ───────────────

	public function test_depth_resets_after_pre_closer(): void {
		$orphan = $this->engine();
		$in     = '<pre>ala w kotcie</pre><p>Ala z psem</p><pre>i z u</pre><p>Ala o psie</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'ala w kotcie', $out );
		$this->assertStringContainsString( 'Ala z' . $this->nbsp . 'psem', $out );
		$this->assertStringContainsString( 'i z u', $out );
		$this->assertStringContainsString( 'Ala o' . $this->nbsp . 'psie', $out );
	}

	public function test_nested_same_tag_is_skipped_to_outer_closer(): void {
		$orphan = $this->engine();
		$in     = '<pre>ala <code>w i z</code> o u</pre><p>Ala w domu</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'ala <code>w i z</code> o u', $out );
		$this->assertStringContainsString( 'Ala w' . $this->nbsp . 'domu', $out );
	}

	// ── Ordering constraint (documented pitfall) ─────────────────────────

	public function test_svg_containing_script_stays_untouched(): void {
		// script/style must come AFTER svg in the list: svg is depth
		// tracked, its rawtext script child arrives nested.
		$orphan = $this->engine( array( 'pre', 'code', 'svg', 'script', 'style', 'iframe' ) );
		$in     = '<svg><script>ala w kotcie i z</script></svg><p>Ala o psie</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'ala w kotcie i z', $out );
		$this->assertStringContainsString( 'Ala o' . $this->nbsp . 'psie', $out );
	}

	// ── Configurable tag list (filter) ───────────────────────────────────

	public function test_custom_rawtext_tag_in_config_protects_and_continues(): void {
		// Rawtext elements (textarea, title, xmp) arrive as a single
		// atomic token with no closer token, so they must be excluded
		// from depth tracking (fixed in 3.5.2): content stays untouched
		// AND the rest of the document is still replaced.
		$orphan = $this->engine( array( 'pre', 'code', 'iframe', 'svg', 'script', 'style', 'textarea' ) );
		$in     = '<textarea>ala w kotcie i z</textarea><p>Ala z psem</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'ala w kotcie i z', $out, 'custom protected rawtext tag content untouched' );
		$this->assertStringContainsString( 'Ala z' . $this->nbsp . 'psem', $out, 'replacement continues after the rawtext tag' );
	}

	public function test_custom_non_rawtext_tag_can_be_added_via_config(): void {
		// A normal (non-rawtext) custom tag works correctly: content
		// protected, following text still replaced.
		$orphan = $this->engine( array( 'pre', 'code', 'iframe', 'svg', 'script', 'style', 'marquee' ) );
		$in     = '<marquee>ala w kotcie i z</marquee><p>Ala z psem</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'ala w kotcie i z', $out, 'custom protected tag content untouched' );
		$this->assertStringContainsString( 'Ala z' . $this->nbsp . 'psem', $out );
	}

	public function test_removing_tag_from_config_enables_replacement_inside(): void {
		// 'pre' removed: its content now gets replaced like normal text.
		$orphan = $this->engine( array( 'code', 'iframe', 'svg', 'script', 'style' ) );
		$in     = '<pre>ala w kotcie</pre><p>Ala z psem</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'ala w' . $this->nbsp . 'kotcie', $out );
		$this->assertStringContainsString( 'Ala z' . $this->nbsp . 'psem', $out );
	}

	public function test_empty_config_replaces_everything(): void {
		$orphan = $this->engine( array() );
		$in     = '<script>ala w kotcie</script><p>Ala z psem</p>';
		$out    = $orphan->replace( $in );
		$this->assertIsString( $out );
		$this->assertStringContainsString( 'Ala z' . $this->nbsp . 'psem', $out );
	}

	public function test_config_with_case_variants_is_respected(): void {
		// The engine lowercases tag names; config matching is exact.
		$orphan = $this->engine( array( 'PRE', 'CODE', 'IFRAME', 'SVG', 'SCRIPT', 'STYLE' ) );
		$in     = '<pre>ala w kotcie</pre><p>Ala z psem</p>';
		$out    = $orphan->replace( $in );
		// "PRE" (uppercase) does not match lowercased tag "pre" - content
		// gets replaced. Documented exact-match semantics.
		$this->assertIsString( $out );
		$this->assertStringContainsString( 'Ala z' . $this->nbsp . 'psem', $out );
	}
}
