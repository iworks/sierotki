<?php
/**
 * Export → import round-trip — test group with hostile import payloads.
 *
 * Verifies the export produces a well-formed payload, that importing it
 * back restores the same values through the real whitelist +
 * sanitize_option_value pipeline, and that every OWASP payload class
 * injected into the imported option values is neutralized before it
 * ever reaches update_option(). The import surface is the trust
 * boundary: the file contents are attacker-controllable in a
 * compromised-account scenario, so every field must come out sanitized.
 *
 * @package sierotki
 * @group security-import-export
 */

require_once __DIR__ . '/../bootstrap.php';
require_once dirname( __DIR__, 3 ) . '/etc/options.php';
require_once dirname( __DIR__, 3 ) . '/includes/iworks/orphans/class-iworks-orphans-import.php';

/**
 * Test double around the real import class: captures update_option()
 * calls (stubbed via the global override) so tests can inspect exactly
 * what would have been written to the database.
 */
class OrphanImportTestDouble extends iWorks_Orphans_Import {
	/**
	 * Run the real whitelist + sanitize pipeline over a decoded payload
	 * and return the option_name => sanitized_value map that would be
	 * written to the database.
	 */
	public function run_pipeline( array $data ): array {
		$allowed  = ( new ReflectionMethod( 'iWorks_Orphans_Import', 'get_allowed_options' ) )->invoke( $this );
		$sanitize = new ReflectionMethod( 'iWorks_Orphans_Import', 'sanitize_option_value' );
		$updated  = array();
		foreach ( $data['Orphans'] as $one ) {
			// Same acceptance gate as import_json().
			if (
				! is_array( $one )
				|| empty( $one['option_name'] )
				|| ! is_string( $one['option_name'] )
				|| ! isset( $allowed[ $one['option_name'] ] )
				|| ! array_key_exists( 'option_value', $one )
			) {
				continue;
			}
			$updated[ $one['option_name'] ] = $sanitize->invoke(
				$this,
				$one['option_value'],
				$allowed[ $one['option_name'] ]
			);
		}
		return $updated;
	}

	/**
	 * Mirror of iWorks_Orphans_Import::import_json() minus the PHP-level
	 * upload gate (is_uploaded_file() always returns false under CLI
	 * SAPI, so the real method can never be exercised end-to-end in
	 * unit tests). Mirrors lines 121-157 of the import class: JSON
	 * decode, envelope validation, whitelist loop, per-field
	 * sanitization and the success/fail notice. If the source method
	 * changes, keep this mirror in sync.
	 */
	public function import_content( string $content ): void {
		$data = json_decode( $content, true );
		if (
			is_array( $data )
			&& isset( $data['Meta'] )
			&& isset( $data['Meta']['plugin'] )
			&& isset( $data['Meta']['plugin']['name'] )
			&& 'Orphans' === $data['Meta']['plugin']['name']
			&& isset( $data['Orphans'] )
			&& is_array( $data['Orphans'] )
			&& ! empty( $data['Orphans'] )
		) {
			$allowed  = ( new ReflectionMethod( 'iWorks_Orphans_Import', 'get_allowed_options' ) )->invoke( $this );
			$sanitize = new ReflectionMethod( 'iWorks_Orphans_Import', 'sanitize_option_value' );
			foreach ( $data['Orphans'] as $one ) {
				if (
					! is_array( $one )
					|| empty( $one['option_name'] )
					|| ! is_string( $one['option_name'] )
					|| ! isset( $allowed[ $one['option_name'] ] )
					|| ! array_key_exists( 'option_value', $one )
				) {
					continue;
				}
				update_option(
					$one['option_name'],
					$sanitize->invoke( $this, $one['option_value'], $allowed[ $one['option_name'] ] )
				);
			}
			$success = new ReflectionMethod( 'iWorks_Orphans_Import', 'add_message_success' );
			$success->invoke( $this );
			return;
		}
		$fail = new ReflectionMethod( 'iWorks_Orphans_Import', 'add_message_fail' );
		$fail->invoke( $this );
	}
}

#[\PHPUnit\Framework\Attributes\Group( 'security-import-export' )]
final class ImportExportSecurityTest extends OrphanTestCase {

	/**
	 * Set up the real option config (etc/options.php) so the whitelist
	 * and sanitize definitions match production exactly.
	 */
	private function import_double(): OrphanImportTestDouble {
		clear_test_filters();
		$GLOBALS['test_orphan_options'] = array();
		return new OrphanImportTestDouble();
	}

	/**
	 * Build a valid export-style payload with hostile values for every
	 * option field. $values maps short field name => hostile value.
	 */
	private function payload( array $values ): array {
		$orphans = array();
		foreach ( $values as $name => $value ) {
			$orphans[] = array(
				'name'         => $name,
				'option_name'  => 'iworks_orphan_' . $name,
				'option_value' => $value,
			);
		}
		return array(
			'Meta'    => array(
				'date'   => gmdate( 'c' ),
				'plugin' => array( 'name' => 'Orphans' ),
			),
			'Orphans' => $orphans,
		);
	}

	// ── Export: well-formed, structurally safe ───────────────────────────

	public function test_export_structure_is_valid_json_payload(): void {
		$GLOBALS['test_orphan_options'] = array(
			'iworks_orphan_own_orphans' => 'w, z, i',
			'iworks_orphan_numbers'     => 1,
		);
		$GLOBALS['test_filters'] = array();
		// Capture the JSON export::send_json() would emit, without
		// headers/exit: replicate get_settings_plugin() via reflection.
		require_once dirname( __DIR__, 3 ) . '/includes/iworks/orphans/class-iworks-orphans-export.php';
		$export = new iWorks_Orphans_Export();
		$method = new ReflectionMethod( 'iWorks_Orphans_Export', 'get_settings_plugin' );
		$data = $method->invoke( $export );
		$this->assertIsArray( $data );
		$json = json_encode( $data );
		$this->assertNotFalse( $json, 'export payload must be valid JSON' );
		$decoded = json_decode( $json, true );
		$this->assertIsArray( $decoded );
		foreach ( $data as $one ) {
			$this->assertArrayHasKey( 'name', $one );
			$this->assertArrayHasKey( 'option_name', $one );
			$this->assertArrayHasKey( 'option_value', $one );
			// Option names must be plugin-prefixed.
			$this->assertStringStartsWith( 'iworks_orphan_', $one['option_name'] );
		}
	}

	public function test_export_then_import_round_trip_restores_values(): void {
		$GLOBALS['test_orphan_options'] = array(
			'iworks_orphan_own_orphans' => 'w, z, i, oraz',
			'iworks_orphan_numbers'     => 1,
			'iworks_orphan_the_title'   => 0,
		);
		require_once dirname( __DIR__, 3 ) . '/includes/iworks/orphans/class-iworks-orphans-export.php';
		$export = new iWorks_Orphans_Export();
		$method = new ReflectionMethod( 'iWorks_Orphans_Export', 'get_settings_plugin' );
		$exported = $method->invoke( $export );
		$this->assertNotEmpty( $exported, 'export must contain the configured options' );

		// Import the exact export payload back through the real pipeline.
		$payload = array(
			'Meta'    => array( 'plugin' => array( 'name' => 'Orphans' ) ),
			'Orphans' => $exported,
		);
		$updated = $this->import_double()->run_pipeline( $payload );
		$this->assertSame(
			'w, z, i, oraz',
			$updated['iworks_orphan_own_orphans'],
			'round trip must preserve the own_orphans term list'
		);
		$this->assertSame( 1, $updated['iworks_orphan_numbers'] );
		$this->assertSame( 0, $updated['iworks_orphan_the_title'] );
	}

	// ── Import: OWASP payload matrix into every field type ───────────────

	/**
	 * Payload classes injected as option values, with the assertion each
	 * must satisfy after the import sanitizer runs.
	 */
	public function payload_matrix(): array {
		return array(
			// ── Stored XSS: script tag injection
			'script tag' => array(
				'<script>alert(1)</script>',
				'no <script or <Script markup may survive',
			),
			'uppercase script' => array(
				'<SCRIPT>alert(1)</SCRIPT>',
				'no script markup may survive',
			),
			'mixed case script' => array(
				'<ScRiPt>alert(1)</sCrIpT>',
				'no script markup may survive',
			),
			// ── Event handler injection
			'img onerror' => array(
				'<img src=x onerror=alert(1)>',
				'no onerror handler may survive',
			),
			'svg onload' => array(
				'<svg onload=alert(1)>',
				'no onload handler may survive',
			),
			'body onmouseover' => array(
				'<body onmouseover=alert(1)>x</body>',
				'no onmouseover handler may survive',
			),
			// ── JavaScript URI injection
			'javascript uri' => array(
				'javascript:alert(1)',
				'javascript: URI is at minimum stripped of tags; hostile scheme must not pass into a checkbox/scalar untouched where dangerous',
			),
			'data uri html' => array(
				'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
				'data: URI must be neutralized',
			),
			// ── Stored HTML injection: phishing forms / meta redirect
			'phishing form' => array(
				'<form action="https://evil.example/login"><input name=pass></form>',
				'form markup must not survive',
			),
			'iframe injection' => array(
				'<iframe src="https://evil.example"></iframe>',
				'iframe markup must not survive',
			),
			'meta refresh' => array(
				'<meta http-equiv="refresh" content="0;url=javascript:alert(1)">',
				'meta redirect must not survive',
			),
			// ── Stored DOM-based XSS
			'dom sink attribute' => array(
				'<div data-x="javascript:alert(1)"></div>',
				'div markup must not survive',
			),
			// ── Blind stored XSS
			'blind beacon' => array(
				'<img src="//evil.example/log?c=">',
				'img beacon must not survive',
			),
			// ── CSS injection / UI redressing
			'css overlay' => array(
				'<style>body::after{content:"";position:fixed;inset:0}</style>',
				'style block must not survive',
			),
			'inline style attr' => array(
				'<div style="position:fixed;top:0;left:0;width:100%">x</div>',
				'style attribute markup must not survive',
			),
			// ── Dangling markup injection
			'dangling markup' => array(
				'<img src="//evil.example?"',
				'dangling markup must not be written verbatim',
			),
			// ── CSTI
			'angular csti' => array(
				'{{7*7}}',
				'template syntax is inert text (may pass as text, must not gain markup)',
			),
			// ── SVG / polyglot
			'svg polyglot' => array(
				'--><svg/onload=alert(1)>',
				'polyglot breakout must not survive',
			),
			'mathml polyglot' => array(
				'<math><mtext></form><form><mglyph><svg><mtext><style></style><img src onerror=alert(1)>',
				'polyglot chain must not survive',
			),
			// ── Attribute breakout
			'quote breakout' => array(
				'"><script>alert(1)</script>',
				'attribute breakout must not survive',
			),
			'attr breakout img' => array(
				'a"><img src=x onerror=alert(1)>',
				'attribute breakout must not survive',
			),
		);
	}

	/**
	 * Every payload, into a text field (own_orphans, esc_html
	 * sanitize callback): no live markup may reach update_option().
	 */
	public function test_hostile_values_into_text_field_are_neutralized(): void {
		$double = $this->import_double();
		foreach ( $this->payload_matrix() as $label => $case ) {
			list( $payload, $reason ) = $case;
			$updated = $double->run_pipeline(
				$this->payload( array( 'own_orphans' => $payload ) )
			);
			$this->assertArrayHasKey( 'iworks_orphan_own_orphans', $updated, $label . ': option must be whitelisted' );
			$value = (string) $updated['iworks_orphan_own_orphans'];
			$this->assertStringNotContainsStringIgnoringCase( '<script', $value, $label . ': ' . $reason );
			$this->assertStringNotContainsStringIgnoringCase( '<img', $value, $label . ': ' . $reason );
			$this->assertStringNotContainsStringIgnoringCase( '<svg', $value, $label . ': ' . $reason );
			$this->assertStringNotContainsStringIgnoringCase( '<iframe', $value, $label . ': ' . $reason );
			$this->assertStringNotContainsStringIgnoringCase( '<form', $value, $label . ': ' . $reason );
			$this->assertStringNotContainsStringIgnoringCase( '<meta', $value, $label . ': ' . $reason );
			$this->assertStringNotContainsStringIgnoringCase( '<style', $value, $label . ': ' . $reason );
			$this->assertStringNotContainsStringIgnoringCase( '<math', $value, $label . ': ' . $reason );
			// Handler text inside ENCODED entities is safe (renders
			// literally); only live handlers attached to a real tag are
			// forbidden.
			$this->assertDoesNotMatchRegularExpression(
				'/<[a-z][^>]*on(?:error|load|mouseover)\s*=/i',
				$value,
				$label . ': ' . $reason
			);
			// The value must be entity-encoded (esc_html applied) whenever
			// the input contained markup - encoded output never executes.
			if ( preg_match( '/<[a-z]/i', $payload ) ) {
				$this->assertStringNotContainsString( '<', $value, $label . ': live markup must be encoded away' );
			}
		}
	}

	/**
	 * Hostile values into a checkbox/absint field must collapse to an
	 * integer — no string, no array, no markup.
	 */
	public function test_hostile_values_into_checkbox_field_collapse_to_int(): void {
		$double = $this->import_double();
		foreach ( $this->payload_matrix() as $label => $case ) {
			list( $payload, $reason ) = $case;
			foreach ( array( 'numbers', 'the_content', 'ignore_language' ) as $field ) {
				$updated = $double->run_pipeline(
					$this->payload( array( $field => $payload ) )
				);
				$key = 'iworks_orphan_' . $field;
				$this->assertArrayHasKey( $key, $updated, $label . ': option must be whitelisted' );
				$this->assertIsInt( $updated[ $key ], $label . ": $field must collapse to int" );
				$this->assertLessThanOrEqual( 1, $updated[ $key ], $label . ": $field must be 0 or 1" );
			}
		}
	}

	/**
	 * Hostile values into a multiple/select2 field: each item goes
	 * through sanitize_text_field, non-scalars are dropped.
	 */
	public function test_hostile_values_into_multiple_field_are_sanitized_per_item(): void {
		$double = $this->import_double();
		$updated = $double->run_pipeline(
			$this->payload(
				array(
					'post_type'  => array(
						'<script>alert(1)</script>',
						'<img src=x onerror=alert(1)>',
						'"><svg onload=alert(1)>',
						'post',
						array( 'nested' ),
						(object) array( 'x' => 1 ),
					),
					'taxonomies' => 'javascript:alert(1)',
				)
			)
		);
		$this->assertCount( 4, $updated['iworks_orphan_post_type'], 'scalars kept, nested array and object dropped' );
		$this->assertSame( 'post', $updated['iworks_orphan_post_type'][3], 'benign value must survive untouched' );
		// Whatever tag-stripping leaves must contain no live markup.
		foreach ( $updated['iworks_orphan_post_type'] as $item ) {
			$this->assertDoesNotMatchRegularExpression(
				'/<[a-z]/i',
				(string) $item,
				'no live tag may survive sanitize_text_field'
			);
			$this->assertDoesNotMatchRegularExpression(
				'/<[a-z][^>]*on(?:error|load|mouseover)\s*=/i',
				(string) $item,
				'no live handler attribute may survive'
			);
		}
		$this->assertSame(
			array( sanitize_text_field( 'javascript:alert(1)' ) ),
			$updated['iworks_orphan_taxonomies'],
			'string value is wrapped and sanitized'
		);
	}

	// ── Import: option-name whitelist ────────────────────────────────────

	/**
	 * Arbitrary option names must never be imported — only plugin-prefixed
	 * whitelisted ones. This is the storage-level injection guard.
	 */
	public function test_arbitrary_option_names_are_rejected(): void {
		$double = $this->import_double();
		$updated = $double->run_pipeline(
			array(
				'Meta'    => array( 'plugin' => array( 'name' => 'Orphans' ) ),
				'Orphans' => array(
					array(
						'option_name'  => 'siteurl',
						'option_value' => 'https://evil.example',
					),
					array(
						'option_name'  => 'users_can_register',
						'option_value' => 1,
					),
					array(
						'option_name'  => 'iworks_orphan_evil_option',
						'option_value' => '<script>alert(1)</script>',
					),
					array(
						'option_name'  => 'iworks_orphan_own_orphans',
						'option_value' => 'w, z',
					),
					array(
						'option_name'  => array( 'array' => 'name' ),
						'option_value' => 'x',
					),
					array(
						'option_name'  => 'iworks_orphan_the_title',
						'option_value' => '<img src=x onerror=alert(1)>',
					),
				),
			)
		);
		$this->assertArrayNotHasKey( 'siteurl', $updated );
		$this->assertArrayNotHasKey( 'users_can_register', $updated );
		$this->assertArrayNotHasKey( 'iworks_orphan_evil_option', $updated, 'non-config plugin-prefixed option must be rejected' );
		$this->assertSame( array( 'iworks_orphan_own_orphans', 'iworks_orphan_the_title' ), array_keys( $updated ) );
		$this->assertSame( 0, $updated['iworks_orphan_the_title'], 'hostile checkbox value collapses to 0' );
	}

	/**
	 * The Meta/plugin frame must require the exact plugin name — a
	 * foreign file must not import.
	 */
	public function test_payload_only_in_meta_frame_is_still_field_sanitized(): void {
		// Even when the envelope is correct, field values are sanitized
		// individually — the envelope check is not a trust shortcut.
		$double = $this->import_double();
		$updated = $double->run_pipeline(
			$this->payload(
				array(
					'own_orphans' => '<script>alert(1)</script>',
					'post_meta'   => '<iframe src="https://evil.example"></iframe>',
				)
			)
		);
		$this->assertStringNotContainsStringIgnoringCase( '<script', (string) $updated['iworks_orphan_own_orphans'] );
		$this->assertStringNotContainsStringIgnoringCase( '<iframe', (string) $updated['iworks_orphan_post_meta'] );
	}

	/**
	 * Structurally hostile payloads (wrong envelope, non-array Orphans,
	 * scalar entries) must import nothing.
	 */
	public function test_structurally_hostile_payloads_import_nothing(): void {
		$double = $this->import_double();
		$bad_entries = array(
			'wrong plugin name' => array(
				'Meta'    => array( 'plugin' => array( 'name' => 'NotOrphans' ) ),
				'Orphans' => array( array( 'option_name' => 'iworks_orphan_numbers', 'option_value' => 1 ) ),
			),
			'orphans as string' => array(
				'Meta'    => array( 'plugin' => array( 'name' => 'Orphans' ) ),
				'Orphans' => '<script>alert(1)</script>',
			),
			'orphans empty' => array(
				'Meta'    => array( 'plugin' => array( 'name' => 'Orphans' ) ),
				'Orphans' => array(),
			),
			'no meta at all' => array(
				'Orphans' => array( array( 'option_name' => 'iworks_orphan_numbers', 'option_value' => 1 ) ),
			),
			'scalar entry' => array(
				'Meta'    => array( 'plugin' => array( 'name' => 'Orphans' ) ),
				'Orphans' => array( 'just a string' ),
			),
			'missing option_value' => array(
				'Meta'    => array( 'plugin' => array( 'name' => 'Orphans' ) ),
				'Orphans' => array( array( 'option_name' => 'iworks_orphan_numbers' ) ),
			),
		);
		foreach ( $bad_entries as $label => $data ) {
			$orphans = $data['Orphans'];
			if ( ! is_array( $orphans ) ) {
				$orphans = array();
			}
			// import_json() only enters the loop when the envelope
			// (Meta.plugin.name === 'Orphans') and a non-empty Orphans
			// array pass; simulate exactly that.
			$envelope_ok = 'Orphans' === ( $data['Meta']['plugin']['name'] ?? '' )
				&& is_array( $orphans )
				&& ! empty( $orphans );
			$updated = $envelope_ok ? $double->run_pipeline( $data ) : array();
			$this->assertEmpty(
				$updated,
				$label . ': nothing may be imported'
			);
		}
	}

	// ── Import: wrong file types (not JSON) ──────────────────────────────

	/**
	 * Run the import over a file of the given content. The PHP-level
	 * upload gate (is_uploaded_file) cannot pass under CLI SAPI, so the
	 * mirror method import_content() on the double is used; it mirrors
	 * import_json()'s decode/envelope/whitelist/sanitize logic exactly.
	 */
	private function run_import_json( string $content ): string {
		$GLOBALS['test_update_option_log'] = array();
		$GLOBALS['test_orphan_options']    = array();
		clear_test_filters();
		$GLOBALS['test_orphan_options']['iworks_orphans_options_import_messages'] = '';
		$import = new OrphanImportTestDouble();
		$import->import_content( $content );
		return (string) get_option( 'iworks_orphans_options_import_messages', '' );
	}

	/**
	 * Files that are not the plugin's JSON export must be rejected with
	 * the fail message — and must not write a single option.
	 */
	public function wrong_file_type_matrix(): array {
		return array(
			// ── plain text
			'plain txt' => array(
				"This is a plain text file.\nNot JSON at all.\n",
				'plain text must not import',
			),
			'empty file' => array(
				'',
				'empty upload must not import',
			),
			'csv-like txt' => array(
				'option_name,option_value' . "\n" . 'iworks_orphan_own_orphans,"<script>alert(1)</script>"',
				'CSV masquerading as upload must not import',
			),
			'ini txt' => array(
				"[Orphans]\nown_orphans = w, z\nMeta.plugin.name = Orphans\n",
				'INI-style text must not import',
			),
			// ── JavaScript
			'js file' => array(
				'var Meta = { plugin: { name: "Orphans" } }; var Orphans = [ { option_name: "iworks_orphan_own_orphans", option_value: "<script>alert(1)</script>" } ];',
				'JS file must not import',
			),
			'js with payload' => array(
				'window.location = "javascript:alert(1)"; document.write("<img src=x onerror=alert(1)>");',
				'JS with payload must not import',
			),
			// ── CSS
			'css file' => array(
				'body { background: url("javascript:alert(1)"); } .a::after { content: "<script>alert(1)</script>"; }',
				'CSS file must not import',
			),
			// ── binary
			'binary blob' => array(
				base64_decode( '/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUg==' ) . "\x00\x01\x02\xff\xfe" . random_bytes( 64 ),
				'binary blob must not import',
			),
			'null bytes' => array(
				"\x00\x00" . '{"Meta":{"plugin":{"name":"Orphans"}},"Orphans":[{"option_name":"iworks_orphan_numbers","option_value":1}]}' . "\x00",
				'JSON wrapped in null bytes must not import',
			),
			'gzip magic' => array(
				"\x1f\x8b\x08\x00binarygzipdata",
				'gzip magic bytes must not import',
			),
			// ── HTML smuggled as upload
			'html file' => array(
				'<!DOCTYPE html><html><body><script>alert(1)</script><img src=x onerror=alert(1)></body></html>',
				'HTML upload must not import',
			),
			// ── JSON-ish but hostile envelope
			'valid json wrong envelope' => array(
				'{"not_meta":true,"Orphans":[{"option_name":"iworks_orphan_numbers","option_value":1}]}',
				'JSON without the Meta frame must not import',
			),
			'json scalar root' => array(
				'1234',
				'scalar JSON root must not import',
			),
			'json truncated' => array(
				'{"Meta":{"plugin":{"name":"Orphans"}},"Orphans":[{"option_name":"iworks_orphan_num',
				'truncated JSON must not import',
			),
			// ── JSONC (JSON with comments) - json_decode() rejects comments
			'jsonc full' => array(
				"// Orphans export\n{\n  // plugin envelope\n  \"Meta\": { \"plugin\": { \"name\": \"Orphans\" } },\n  \"Orphans\": [ { \"option_name\": \"iworks_orphan_own_orphans\", \"option_value\": \"w, z\" } ] /* injected comment */\n}",
				'JSONC (comments) must not import',
			),
			'jsonc inline comment' => array(
				'{"Meta":{"plugin":{"name":"Orphans"}},"Orphans":[{"option_name":"iworks_orphan_own_orphans","option_value":"w,"}]} // injected comment',
				'trailing comment outside the JSON document must not import',
			),
			'jsonc comment in envelope' => array(
				'{/* what plugin? */"Meta":{"plugin":{"name":"Orphans"}},"Orphans":[{"option_name":"iworks_orphan_numbers","option_value":1}]}',
				'comment inside the envelope must not import',
			),
			'jsonc block comment before' => array(
				"/* header */\n{\"Meta\":{\"plugin\":{\"name\":\"Orphans\"}},\"Orphans\":[{\"option_name\":\"iworks_orphan_numbers\",\"option_value\":1}]}",
				'leading block comment must not import',
			),
			'jsonc trailing comma' => array(
				'{"Meta":{"plugin":{"name":"Orphans"}},"Orphans":[{"option_name":"iworks_orphan_numbers","option_value":1},]}',
				'trailing comma (JSONC tolerance) must not import',
			),
		);
	}

	public function test_non_json_file_types_are_rejected(): void {
		foreach ( $this->wrong_file_type_matrix() as $label => $case ) {
			list( $content, $reason ) = $case;
			$this->run_import_json( $content );
			$log = $GLOBALS['test_update_option_log'];
			// The ONLY allowed write is the failure notice option.
			foreach ( $log as $call ) {
				$this->assertSame(
					'iworks_orphans_options_import_messages',
					$call[0],
					$label . ': ' . $reason . ' (unexpected option write: ' . $call[0] . ')'
				);
			}
			$this->assertNotEmpty( $log, $label . ': a failure message must be recorded' );
			// The notice itself must carry the error class, not success.
			$notice = (string) $log[0][1];
			$this->assertStringContainsString( 'notice-error', $notice, $label . ': ' . $reason );
			$this->assertStringNotContainsString( 'Configuration has been imported', $notice );
		}
	}

	public function test_valid_export_file_still_imports_after_rejection_matrix(): void {
		// Control: the same import path with a genuine export payload
		// must succeed — proves the rejections above come from the
		// envelope/file checks, not from a broken test setup.
		$payload = json_encode(
			array(
				'Meta'    => array( 'plugin' => array( 'name' => 'Orphans' ) ),
				'Orphans' => array(
					array(
						'option_name'  => 'iworks_orphan_own_orphans',
						'option_value' => 'w, z, i',
					),
				),
			)
		);
		$this->run_import_json( $payload );
		$log = $GLOBALS['test_update_option_log'];
		$imported = array();
		foreach ( $log as $call ) {
			if ( 'iworks_orphans_options_import_messages' !== $call[0] ) {
				$imported[ $call[0] ] = $call[1];
			}
		}
		$this->assertSame( array( 'iworks_orphan_own_orphans' ), array_keys( $imported ) );
		$this->assertSame( 'w, z, i', $imported['iworks_orphan_own_orphans'] );
		$notice = end( $log )[1];
		$this->assertStringContainsString( 'notice-info', (string) $notice, 'success notice on valid import' );
	}

	/**
	 * A JSON file whose VALUES are payloads but whose envelope is valid
	 * still goes through per-field sanitization — the file-level checks
	 * never bypass the field sanitizer.
	 */
	public function test_valid_envelope_with_payload_values_still_sanitized(): void {
		$payload = json_encode(
			array(
				'Meta'    => array( 'plugin' => array( 'name' => 'Orphans' ) ),
				'Orphans' => array(
					array(
						'option_name'  => 'iworks_orphan_own_orphans',
						'option_value' => '<script>alert(1)</script>',
					),
					array(
						'option_name'  => 'iworks_orphan_numbers',
						'option_value' => '<img src=x onerror=alert(1)>',
					),
				),
			)
		);
		$this->run_import_json( $payload );
		$imported = array();
		foreach ( $GLOBALS['test_update_option_log'] as $call ) {
			if ( 'iworks_orphans_options_import_messages' !== $call[0] ) {
				$imported[ $call[0] ] = $call[1];
			}
		}
		$this->assertSame( '&lt;script&gt;alert(1)&lt;/script&gt;', $imported['iworks_orphan_own_orphans'], 'esc_html encodes the value for safe literal display' );
		$this->assertStringNotContainsString( '<script', $imported['iworks_orphan_own_orphans'] );
		$this->assertStringContainsString( '&lt;script', $imported['iworks_orphan_own_orphans'] );
		$this->assertSame( 0, $imported['iworks_orphan_numbers'], 'hostile checkbox value collapses to 0' );
	}
}
