<?php
/**
 * Shortcode guard (3.5.1 Divi regression) — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('shortcode-guard')]
final class ShortcodeGuardTest extends OrphanTestCase {


	public function test_raw_shortcode_content_is_skipped_entirely(): void {
		// Divi parent modules pass raw child shortcodes; quotes must never be
		// re-encoded to &quot; (3.5.0 regression, fixed in 3.5.1).
		$in = '[dica_divi_carouselitem title="Imie Nazwisko" button_text="zobacz w portfolio" image="https://example.com/photo.jpg"]';
		$this->assertSame( $in, $this->orphan->replace( $in ) );
	}

	public function test_raw_shortcode_with_html_inside_attribute_is_skipped(): void {
		$in = '[dica_divi_carouselitem title="<h3>Imie Nazwisko</h3>" button_text="zobacz w portfolio"]';
		$this->assertSame( $in, $this->orphan->replace( $in ) );
	}

	public function test_shortcode_pair_is_skipped(): void {
		$in = '[dica_divi_carousel]Ala w kotcie[dica_divi_carouselitem title="Imie"][/dica_divi_carousel]';
		$this->assertSame( $in, $this->orphan->replace( $in ) );
	}

	public function test_square_brackets_in_prose_still_get_replaced(): void {
		// "[key]" has no space/slash after the word - not a shortcode.
		$this->assertSame(
			"Zobacz [key] w{$this->nbsp}tabeli",
			$this->orphan->replace( 'Zobacz [key] w tabeli' )
		);
	}

	public function test_markdown_style_bracket_with_space_is_skipped(): void {
		// Documented trade-off: "[zobacz " looks like a shortcode tag.
		$in = 'cos [zobacz w linku](https://example.com)';
		$this->assertSame( $in, $this->orphan->replace( $in ) );
	}
}
