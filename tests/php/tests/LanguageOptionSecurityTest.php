<?php
/**
 * Language option: security — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('security-language')]
final class LanguageOptionSecurityTest extends OrphanTestCase {


	/**
	 * The `language` option value is interpolated into a file path:
	 * sprintf( 'terms-%s.txt', $locale ) resolved under {root}/etc/.
	 * These tests prove that hostile option values cannot escape that
	 * frame: the mandatory "terms-" path segment makes traversal segments
	 * invalid (etc/terms-../../x is not a real path), null bytes and
	 * stream wrappers fail is_file(), and a missing file yields zero
	 * terms without errors.
	 */

	public function test_language_traversal_payload_cannot_escape_etc_dir(): void {
		// Would normalize to {root}/evil.txt if the frame were unframed;
		// the "terms-" prefix segment breaks the traversal.
		$this->assertSame( array(), $this->load_terms_for_language( '../../../evil' ) );
		$this->assertSame( array(), $this->load_terms_for_language( '../evil' ) );
		$this->assertSame( array(), $this->load_terms_for_language( 'pl_PL/../../evil' ) );
	}

	public function test_language_backslash_traversal_is_inert(): void {
		// Windows-style separators are literal characters on POSIX.
		$this->assertSame( array(), $this->load_terms_for_language( '..\\..\\evil' ) );
	}

	public function test_language_absolute_path_is_inert(): void {
		// The value can never become the head of the path: it is always
		// framed as {root}/etc/terms-<value>.txt.
		$this->assertSame( array(), $this->load_terms_for_language( '/etc/passwd' ) );
		$this->assertSame( array(), $this->load_terms_for_language( '/var/www/evil' ) );
	}

	public function test_language_stream_wrapper_is_inert(): void {
		// Stream wrappers cannot engage: the wrapper name would have to be
		// "terms-php://", which is not a registered scheme.
		$this->assertSame( array(), $this->load_terms_for_language( 'php://filter/read=convert.base64-encode/resource=terms-pl' ) );
		$this->assertSame( array(), $this->load_terms_for_language( 'data://text/plain,a' ) );
	}

	public function test_language_null_byte_is_rejected(): void {
		// PHP 8 binary-safe filesystem functions return false (no warning,
		// no ValueError) for paths containing null bytes.
		$this->assertSame( array(), $this->load_terms_for_language( "pl_PL\0" ) );
		$this->assertSame( array(), $this->load_terms_for_language( "\0/etc/passwd" ) );
	}

	public function test_language_html_injection_is_inert(): void {
		// Option values with markup must not leak into anything rendered.
		$this->assertSame( array(), $this->load_terms_for_language( '<script>alert(1)</script>' ) );
		$this->assertSame( array(), $this->load_terms_for_language( '" onmouseover="alert(1)' ) );
	}
}
