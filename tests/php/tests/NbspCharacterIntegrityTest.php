<?php
/**
 * NBSP character integrity — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('nbsp')]
final class NbspCharacterIntegrityTest extends OrphanTestCase {


	public function test_real_nbsp_character_is_used_not_entity(): void {
		$out = $this->orphan->replace( 'Ala w kotcie' );
		$this->assertStringContainsString( "\u{00A0}", $out );
		$this->assertStringNotContainsString( '&nbsp;', $out );
	}

	public function test_nbsp_is_not_double_encoded_in_html(): void {
		$out = $this->orphan->replace( '<p>Ala w kotcie</p>' );
		$this->assertStringNotContainsString( '&amp;nbsp;', $out );
		$this->assertStringNotContainsString( 'Â', $out );
	}

	public function test_polish_typographic_quote_starts_orphan_chain(): void {
		$this->assertSame(
			"powiedzial „cos w{$this->nbsp}srodku”",
			$this->orphan->replace( 'powiedzial „cos w srodku”' )
		);
	}
}
