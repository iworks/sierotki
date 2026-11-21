<?php
/**
 * HTML content — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('html')]
final class HtmlContentTest extends OrphanTestCase {


	public function test_replaces_inside_html_text_nodes(): void {
		$this->assertSame(
			"<p>Ala w{$this->nbsp}kotcie</p>",
			$this->orphan->replace( '<p>Ala w kotcie</p>' )
		);
	}

	public function test_html_attributes_are_never_rewritten(): void {
		// The anchor text gets the nbsp; href and title attribute values must
		// stay byte-identical.
		$in  = '<a href="https://example.com/?a=1" title="Kotek z psem">kotek z psem</a>';
		$out = $this->orphan->replace( $in );
		$this->assertStringContainsString( 'href="https://example.com/?a=1"', $out );
		$this->assertStringContainsString( 'title="Kotek z psem"', $out );
		$this->assertStringContainsString( ">kotek z{$this->nbsp}psem</a>", $out );
	}

	public function test_no_quote_entities_are_introduced_in_attributes(): void {
		// Straight quotes in ATTRIBUTES must never be re-encoded (3.5.0 Divi
		// regression). Text nodes are HTML-encoded by set_modifiable_text()
		// by design - only attribute/shortcode integrity is asserted here.
		$out = $this->orphan->replace( '<a href="https://example.com/?q=x" title="Kotek z psem">kotek z psem</a>' );
		$this->assertStringNotContainsString( '&quot;', $out );
		$this->assertStringContainsString( 'title="Kotek z psem"', $out );
	}
}
