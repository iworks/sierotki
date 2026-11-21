<?php
/**
 * Plain string replacement — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('plain-strings')]
final class PlainStringReplacementTest extends OrphanTestCase {


	public function test_replaces_space_after_single_letter_w(): void {
		$this->assertSame(
			"Ala w{$this->nbsp}kotcie",
			$this->orphan->replace( 'Ala w kotcie' )
		);
	}

	public function test_replaces_space_after_single_letter_z(): void {
		$this->assertSame(
			"Kotek z{$this->nbsp}psem",
			$this->orphan->replace( 'Kotek z psem' )
		);
	}

	public function test_replaces_all_orphan_occurrences(): void {
		$this->assertSame(
			"Ala i{$this->nbsp}kot z{$this->nbsp}psem a{$this->nbsp}takze o{$this->nbsp}psie",
			$this->orphan->replace( 'Ala i kot z psem a takze o psie' )
		);
	}

	public function test_does_not_touch_words_without_orphans(): void {
		$content = 'Wieder一句话 bez zmian';
		$this->assertSame( $content, $this->orphan->replace( $content ) );
	}

	public function test_leaves_quoted_text_intact_when_no_orphan(): void {
		$this->assertSame(
			'powiedział "cos tam"',
			$this->orphan->replace( 'powiedział "cos tam"' )
		);
	}

	public function test_empty_string_returns_unchanged(): void {
		$this->assertSame( '', $this->orphan->replace( '' ) );
	}

	public function test_non_string_returns_unchanged(): void {
		$this->assertNull( $this->orphan->replace( null ) );
		$this->assertSame( 123, $this->orphan->replace( 123 ) );
	}
}
