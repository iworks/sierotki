<?php
/**
 * Locale gate — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('locale-gate')]
final class LocaleGateTest extends OrphanTestCase {


	/**
	 * The plugin only applies Polish/Czech rules; every default test assumes
	 * pl_PL (set in bootstrap), so lock it in explicitly here.
	 */
	public function test_default_locale_is_polish(): void {
		$this->assertSame( 'pl_PL', get_locale() );
	}

	public function test_replacement_works_for_polish_locale(): void {
		$this->assertSame(
			'Ala w' . $this->nbsp . 'kotcie',
			$this->orphan->replace( 'Ala w kotcie' )
		);
	}

	public function test_replacement_is_skipped_for_non_polish_locale(): void {
		$GLOBALS['test_locale'] = 'en_US';
		$this->assertSame( 'Ala w kotcie', $this->orphan->replace( 'Ala w kotcie' ) );
		$GLOBALS['test_locale'] = 'de_DE';
		$this->assertSame( 'Ala w kotcie', $this->orphan->replace( 'Ala w kotcie' ) );
		$GLOBALS['test_locale'] = 'pl_PL';
	}

	public function test_replacement_works_for_czech_locale(): void {
		// cs_CZ is the other supported language (since 3.2.0).
		$GLOBALS['test_locale'] = 'cs_CZ';
		$this->assertSame(
			'Ala w' . $this->nbsp . 'kotcie',
			$this->orphan->replace( 'Ala w kotcie' )
		);
		$GLOBALS['test_locale'] = 'pl_PL';
	}

	public function test_ignore_language_option_bypasses_locale_gate(): void {
		$this->orphan->set_ignore_language();
		$GLOBALS['test_locale'] = 'en_US';
		$this->assertSame(
			'Ala w' . $this->nbsp . 'kotcie',
			$this->orphan->replace( 'Ala w kotcie' )
		);
		$GLOBALS['test_locale'] = 'pl_PL';
	}
}
