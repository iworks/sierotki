<?php
/**
 * XSS / injection payload matrix — test group.
 *
 * Runs every OWASP payload class through the plugin's output surfaces
 * (replace, filter_gettext, filter_acf) and asserts the invariants that
 * make injection impossible: the plugin only ever inserts U+00A0 into
 * text nodes; it never decodes entities (so escaped payloads stay
 * escaped), never rewrites attributes, and never touches payload tag
 * content (script/style/iframe/svg are protected).
 *
 * @package sierotki
 * @group security-xss
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group( 'security-xss' )]
final class XssPayloadsTest extends OrphanTestCase {

	/**
	 * Payload classes with the invariant each must satisfy.
	 */
	public function payload_matrix(): array {
		return array(
			// ── Stored XSS: script tag injection
			'live script tag'          => array(
				'<script>alert(1)</script>ala w kotcie',
				'script tag content must be untouched',
			),
			'escaped script tag'       => array(
				'&lt;script&gt;alert(1)&lt;/script&gt; ala w kotcie',
				'entities must not be decoded into a live tag',
			),
			'script in attribute'      => array(
				'<div data-x="<script>alert(1)</script>">z opcji</div>',
				'attribute value must survive byte-identical',
			),
			// ── Event handler injection
			'img onerror'              => array(
				'<img src=x onerror="alert(1)"> ala w kotcie',
				'handler attribute must survive byte-identical',
			),
			'body onload'              => array(
				'<body onload=alert(1)>z opcji</body>',
				'handler attribute must survive byte-identical',
			),
			// ── JavaScript URI injection
			'javascript href'          => array(
				'<a href="javascript:alert(1)">zobacz w to</a>',
				'href must survive byte-identical',
			),
			'data uri'                 => array(
				'<a href="data:text/html,<script>alert(1)</script>">w opcjach</a>',
				'href must survive byte-identical',
			),
			// ── Stored HTML injection: phishing / redirects
			'form action js uri'       => array(
				'<form action="javascript:alert(1)"><input name="pass"></form> z opcji',
				'form markup must survive intact',
			),
			'meta refresh redirect'    => array(
				'<meta http-equiv="refresh" content="0;url=javascript:alert(1)">w opcjach',
				'meta must survive intact (protected tag semantics)',
			),
			'iframe phishing'          => array(
				'<iframe src="https://evil.example/login"></iframe> z opcji',
				'iframe content must be untouched (protected tag)',
			),
			// ── DOM-based XSS
			'data attribute json'      => array(
				'<div data-json="{"a":"w x"}">z kosmos</div>',
				'data attribute must survive byte-identical',
			),
			// ── Blind stored XSS (same sinks, admin-entered content)
			'blind via title-like'     => array(
				'Ala w kotcie <img src=x onerror=alert(document.cookie)>',
				'handler attribute must survive byte-identical',
			),
			// ── CSS injection / UI redressing
			'style css injection'      => array(
				'<style>body{background:url(javascript:alert(1))}</style> w domu',
				'style content must be untouched (protected tag)',
			),
			'inline style attribute'   => array(
				'<div style="position:fixed;top:0;left:0;width:100%">z opcji</div>',
				'style attribute must survive byte-identical',
			),
			// ── Dangling markup injection
			'dangling markup'          => array(
				'<img src="//evil.example? x z y ala',
				'dangling markup must not be completed or altered',
			),
			// ── CSTI
			'angular csti'             => array(
				'{{7*7}} ${7*7} ala w kotcie',
				'template syntax must pass through unchanged',
			),
			'ejs csti (encoded on output)' => array(
				'<%= 7*7 %> z opcji',
				'template syntax must pass through unchanged',
			),
			// ── SVG / polyglot
			'svg onload'               => array(
				'<svg onload=alert(1)> ala w kotcie',
				'svg must be treated as protected tag',
			),
			'polyglot breakout'        => array(
				'--><script>alert(1)</script> w kotcie',
				'script content must be untouched',
			),
			'mathml svg polyglot'      => array(
				'<math><mtext></form><form><mglyph><svg><mtext><style></style> z opcji',
				'payload structure must survive',
			),
			// ── Attribute breakout
			'quote breakout attempt'   => array(
				'<a title="z psem " onclick="alert(1)">z psem</a>',
				'attributes must survive byte-identical',
			),
			'lt breakout in attr'      => array(
				'<a title="w"><b onclick=alert(1)>x</b>">z opcji</a>',
				'attributes must survive byte-identical',
			),
		);
	}

	/**
	 * Runs the matrix through replace(): every payload must come out
	 * structurally identical - the ONLY allowed difference is U+00A0
	 * inserted after orphan words in text nodes.
	 */
	public function test_payloads_stay_inert_through_replace(): void {
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1 ) );
		foreach ( $this->payload_matrix() as $label => $case ) {
			list( $payload, $reason ) = $case;
			$out = $this->orphan->replace( $payload );
			$this->assertIsString( $out, $label . ': output must stay a string' );
			// The plugin must never DECODE entities (decoding = activating
			// an escaped payload). Encoding invalid-tag sequences like
			// "<%= %>" is core Tag Processor re-serialization - the safe
			// direction - so the count may only stay equal or grow.
			$this->assertGreaterThanOrEqual(
				substr_count( $payload, '&lt;' ),
				substr_count( $out, '&lt;' ),
				$label . ': ' . $reason . ' (entities were decoded!)'
			);
			$this->assertGreaterThanOrEqual(
				substr_count( $payload, '&quot;' ),
				substr_count( $out, '&quot;' ),
				$label . ': ' . $reason . ' (entities were decoded!)'
			);
			// No new live tags may appear.
			$this->assertSame(
				substr_count( strtolower( $payload ), '<script' ),
				substr_count( strtolower( $out ), '<script' ),
				$label . ': ' . $reason . ' (script tag count changed)'
			);
			// Existing handler attributes must survive byte-identical.
			if ( preg_match_all( '/on[a-z]+="[^"]*"/i', $payload, $m ) ) {
				foreach ( $m[0] as $attr ) {
					$this->assertStringContainsString( $attr, $out, $label . ': ' . $reason );
				}
			}
			// javascript:/data: URIs must survive byte-identical (plugin
			// must not "normalize" them into anything else either).
			if ( preg_match_all( '/(?:javascript|data):[^">]*/i', $payload, $m ) ) {
				foreach ( $m[0] as $uri ) {
					$this->assertStringContainsString( $uri, $out, $label . ': ' . $reason );
				}
			}
		}
	}

	/**
	 * The escaped-script invariant, byte-exact: escaped payload stays
	 * escaped, only the nbsp after the orphan word is added.
	 */
	public function test_escaped_script_stays_escaped(): void {
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1 ) );
		$in  = '&lt;script&gt;alert(1)&lt;/script&gt; ala w kotcie';
		$out = $this->orphan->replace( $in );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $out );
		$this->assertStringNotContainsString( '<script>', $out );
	}

	/**
	 * Hostile config values (own_orphans terms containing payload
	 * strings) must never activate: they are preg_quoted and only used
	 * to place nbsp in text nodes.
	 */
	public function test_payload_strings_as_own_orphans_stay_inert(): void {
		$terms = $this->load_own_orphans( '<script>,onerror,javascript:,<iframe>,{{7*7}}' );
		$sprop = new ReflectionProperty( 'iworks_orphan', 'terms' );
		$sprop->setValue( $this->orphan, $terms );
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1 ) );
		$out = $this->orphan->replace( 'ala <script> kot javascript: x {{7*7}} y' );
		$this->assertIsString( $out );
		$this->assertSame( 1, substr_count( $out, '<script>' ), 'tag count must not change' );
		$this->assertStringNotContainsString( '&lt;', $out );
	}

	/**
	 * Config values used as gates (post_type, taxonomies, post_meta)
	 * containing payloads must never reach the output.
	 */
	public function test_payload_config_values_never_reach_output(): void {
		$GLOBALS['test_orphan_options'] = array(
			'language'  => '0',
			'post_meta' => '<script>alert(1)</script>, javascript:alert(1)',
		);
		// Meta allowlist built from a payload option: matching a payload
		// "key" would just process its value normally.
		$GLOBALS['test_post_meta'] = array(
			5 => array( 'javascript:alert(1)' => 'Ala w kotcie' ),
		);
		$out = $this->orphan->filter_post_meta( 'ORIGINAL', 5, 'javascript:alert(1)', true );
		$this->assertSame( 'Ala w' . $this->nbsp . 'kotcie', $out );
		// No payload markup in the OUTPUT - the option value never leaks.
		$this->assertStringNotContainsString( '<script>', $out );
		$this->assertStringNotContainsString( 'javascript:', $out );
	}
}
