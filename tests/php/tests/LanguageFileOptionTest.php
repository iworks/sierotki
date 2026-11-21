<?php
/**
 * Language file option (language setting) — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('language-files')]
final class LanguageFileOptionTest extends OrphanTestCase {


	/**
	 * The `language` option (default `function_get_locale`) selects which
	 * etc/terms-*.txt files are loaded. These tests exercise the real file
	 * loader (WP_Filesystem_Direct + the repo's term lists) for every
	 * option value.
	 */

	/**
	 * Builds a fresh iworks_orphan with NO injected terms, so `_terms()`
	 * actually reads the language files; returns the loaded term list.
	 */

	public function test_default_language_function_get_locale_loads_polish_terms(): void {
		$terms = $this->load_terms_for_language( 'function_get_locale' );
		$this->assertNotEmpty( $terms );
		$this->assertContains( 'w', $terms );
		$this->assertContains( 'z', $terms );
		$this->assertContains( 'i', $terms );
		// Polish file only - no Czech-only words.
		$this->assertNotContains( 'cca.', $terms );
		$this->assertNotContains( 'kupř.', $terms );
	}

	public function test_language_pl_pl_loads_polish_terms(): void {
		$terms = $this->load_terms_for_language( 'pl_PL' );
		$this->assertContains( 'w', $terms );
		$this->assertContains( 'z', $terms );
		$this->assertContains( 'oraz', $terms );
		$this->assertNotContains( 'the', $terms );
	}

	public function test_language_cs_cz_loads_czech_terms(): void {
		$terms = $this->load_terms_for_language( 'cs_CZ' );
		$this->assertNotEmpty( $terms );
		// Czech terms like "cca." or "kupř." come from terms-cs.txt
		$this->assertContains( 'cca.', $terms );
		// Czech file has no Polish "w"/"oraz"...
		$this->assertNotContains( 'oraz', $terms );
		$this->assertNotContains( 'the', $terms );
	}

	public function test_language_en_loads_english_terms(): void {
		$terms = $this->load_terms_for_language( 'en' );
		$this->assertNotEmpty( $terms );
		$this->assertContains( 'a', $terms );
		$this->assertContains( 'we', $terms );
		// English file has no Polish-only connectors.
		$this->assertNotContains( 'oraz', $terms );
		$this->assertNotContains( 'w', $terms );
	}

	public function test_language_en_us_resolves_to_english_file(): void {
		// preg_replace( '/_[A-Z]+$/' ) maps en_US -> en -> terms-en.txt.
		$terms = $this->load_terms_for_language( 'en_US' );
		$this->assertContains( 'we', $terms );
		$this->assertContains( 'us', $terms );
	}

	public function test_language_cs_cz_resolves_region_suffix(): void {
		// pl_PL-style region suffix is stripped: cs_CZ -> cs -> terms-cs.txt.
		$terms = $this->load_terms_for_language( 'cs_CZ' );
		$this->assertContains( 'cca.', $terms );
	}

	public function test_language_do_not_load_files_gives_no_terms(): void {
		// "Do not load any files" radio stores value 0 (implicit key).
		foreach ( array( 0, '0' ) as $value ) {
			$this->assertSame(
				array(),
				$this->load_terms_for_language( $value ),
				'language=0 must load no term files'
			);
		}
	}

	public function test_language_unknown_value_gives_no_terms(): void {
		// No terms-unknown_LOCALE.txt exists - the loader must not explode.
		$this->assertSame( array(), $this->load_terms_for_language( 'unknown_LOCALE' ) );
	}

	public function test_function_get_locale_follows_site_locale(): void {
		// With site locale switched to cs_CZ, function_get_locale must load
		// the Czech file, not Polish.
		$terms = $this->load_terms_for_language( 'function_get_locale', 'cs_CZ' );
		$this->assertContains( 'cca.', $terms );
		$this->assertNotContains( 'oraz', $terms );
	}

	public function test_loaded_terms_are_sorted_and_unique_and_lowercase(): void {
		$terms = $this->load_terms_for_language( 'pl_PL' );
		$sorted = $terms;
		sort( $sorted );
		$this->assertSame( $sorted, $terms, 'terms must come out sorted' );
		$this->assertSame( array_unique( $terms ), $terms, 'terms must be unique' );
		foreach ( $terms as $term ) {
			$this->assertSame( strtolower( $term ), $term, "term \"$term\" must be lowercase" );
		}
	}
}
