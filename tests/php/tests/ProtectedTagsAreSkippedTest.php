<?php
/**
 * Protected tags are skipped — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('protected-tags')]
final class ProtectedTagsAreSkippedTest extends OrphanTestCase {


	public function test_script_content_is_never_replaced(): void {
		$in  = '<p>Ala w kotcie</p><script>var s = "a w b";</script>';
		$out = $this->orphan->replace( $in );
		$this->assertStringContainsString( 'var s = "a w b";', $out );
		$this->assertStringContainsString( "Ala w{$this->nbsp}kotcie", $out );
	}

	public function test_style_content_is_never_replaced(): void {
		$in  = '<style>.a { content: "w i z"; }</style><p>Ala z psem</p>';
		$out = $this->orphan->replace( $in );
		$this->assertStringContainsString( 'content: "w i z";', $out );
		$this->assertStringContainsString( "Ala z{$this->nbsp}psem", $out );
	}

	public function test_pre_content_is_never_replaced(): void {
		$in  = "<pre>ala w kotcie\ni z psem</pre><p>Ala w domu</p>";
		$out = $this->orphan->replace( $in );
		$this->assertStringContainsString( 'ala w kotcie', $out );
		$this->assertStringContainsString( "Ala w{$this->nbsp}domu", $out );
	}

	public function test_code_content_is_never_replaced(): void {
		$in  = '<code>$x = ala w tablicy;</code><p>Ala z plecakiem</p>';
		$out = $this->orphan->replace( $in );
		$this->assertStringContainsString( '$x = ala w tablicy;', $out );
		$this->assertStringContainsString( "Ala z{$this->nbsp}plecakiem", $out );
	}

	public function test_nested_protected_tag_is_skipped_to_its_closer(): void {
		// <pre> containing an inline <em>: both stay untouched.
		$in  = '<pre>ala <em>w</em> kotcie</pre><p>Ala i pies</p>';
		$out = $this->orphan->replace( $in );
		$this->assertStringContainsString( 'ala <em>w</em> kotcie', $out );
		$this->assertStringContainsString( "Ala i{$this->nbsp}pies", $out );
	}
}
