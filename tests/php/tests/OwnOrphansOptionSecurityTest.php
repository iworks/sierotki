<?php
/**
 * Own orphans option: security — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('security-own-orphans')]
final class OwnOrphansOptionSecurityTest extends OrphanTestCase {


	/**
	 * The `iworks_orphan_own_orphans` option (admin textarea, comma
	 * separated) is merged into the term list and used to build the
	 * replacement regexes. These tests cover: regex metacharacter
	 * neutralization (preg_quote), ReDoS resistance with hostile
	 * quantifier payloads, HTML entity round-trip (esc_html on save,
	 * html_entity_decode on load), and junk normalization.
	 */

	/**
	 * Loads terms from a given own_orphans string with no language files.
	 */

	public function test_own_orphans_regex_metacharacters_are_preg_quoted(): void {
		// A term like "w(a)" or "z+" must never corrupt the pattern.
		$terms = $this->load_own_orphans( 'w(a),i),z+,to?,a{2},[x]' );
		$this->assertContains( 'w(a)', $terms );
		$this->assertContains( 'i)', $terms );
		$this->assertContains( 'z+', $terms );
		$this->assertContains( 'a{2}', $terms );
		$this->assertContains( '[x]', $terms );
		// And they are usable in the replacement engine without preg errors.
		$sprop = new ReflectionProperty( 'iworks_orphan', 'terms' );
		$sprop->setValue( $this->orphan, $terms );
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1 ) );
		$out = $this->orphan->test_string_replacement( 'kot w(a) domu i) ogrodzie' );
		$this->assertStringContainsString( 'w(a)' . $this->nbsp, $out );
		$this->assertStringContainsString( 'i)' . $this->nbsp, $out );
	}

	public function test_own_orphans_catastrophic_quantifiers_cause_no_redos(): void {
		// Quantifier payloads like "a?a?a?..." stay literal after
		// preg_quote, so a hostile input string cannot trigger
		// catastrophic backtracking. Must complete far below a second.
		$evil_terms = str_repeat( 'a?', 200 ) . ',' . str_repeat( '(b)*', 100 ) . ',w,i,z';
		$terms      = $this->load_own_orphans( $evil_terms );
		$sprop = new ReflectionProperty( 'iworks_orphan', 'terms' );
		$sprop->setValue( $this->orphan, $terms );
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1 ) );
		$evil_content = str_repeat( 'aaaaa!bbbb!', 5000 );
		$start        = microtime( true );
		$this->orphan->test_string_replacement( $evil_content );
		$elapsed = microtime( true ) - $start;
		$this->assertLessThan( 2.0, $elapsed, 'replacement with hostile terms must not backtrack catastrophically' );
	}

	public function test_own_orphans_html_entities_are_decoded(): void {
		// The option is saved through esc_html(); on load the entities are
		// decoded back, so the round-trip preserves the admin's intent.
		$terms = $this->load_own_orphans( '&quot;q&quot;,&amp;x,&#65;&#76;&#65;' );
		$this->assertContains( '"q"', $terms );
		$this->assertContains( '&x', $terms );
		$this->assertContains( 'ala', $terms );
	}

	public function test_own_orphans_are_lowercased(): void {
		$terms = $this->load_own_orphans( 'ALa,WiELKI' );
		$this->assertContains( 'ala', $terms );
		$this->assertContains( 'wielki', $terms );
		$this->assertNotContains( 'ALa', $terms );
	}

	public function test_own_orphans_junk_is_normalized(): void {
		// Multiple commas collapse and empties drop out. Note: a space
		// BEFORE a comma survives ("i ," -> "i ") - only spaces AFTER the
		// comma are consumed by the split pattern; that is the documented
		// (quirky but harmless) behavior.
		$terms = $this->load_own_orphans( 'w,,,i,w,,' );
		$this->assertSame( array( 'i', 'w' ), $terms );
		$terms = $this->load_own_orphans( 'w, i , w' );
		$this->assertContains( 'w', $terms );
		$this->assertContains( 'i ', $terms ); // trailing space kept
	}

	public function test_own_orphans_with_null_bytes_are_harmless(): void {
		// A null byte inside a term must not break the regex build or the
		// replacement run.
		$terms = $this->load_own_orphans( "w\0x,i" );
		$out_terms = array_values( array_filter( $terms ) );
		$sprop = new ReflectionProperty( 'iworks_orphan', 'terms' );
		$sprop->setValue( $this->orphan, $out_terms );
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1 ) );
		// Must not throw preg errors; output stays a string.
		$out = $this->orphan->test_string_replacement( 'ala i kot' );
		$this->assertIsString( $out );
		$this->assertStringContainsString( 'i' . $this->nbsp, $out );
	}

	public function test_own_orphans_empty_option_loads_nothing(): void {
		$this->assertSame( array(), $this->load_own_orphans( '' ) );
		$this->assertSame( array(), $this->load_own_orphans( ',,,' ) );
	}
}
