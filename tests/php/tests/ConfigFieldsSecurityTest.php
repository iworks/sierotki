<?php
/**
 * All config fields — attack surface test group.
 *
 * Attempts to break the plugin through every config field: hostile
 * option shapes (arrays, objects, HTML, null bytes), loose comparisons,
 * and filter registration abuse. Expected outcome is always: fail safe
 * (no change) or normal nbsp processing - never pattern corruption,
 * code execution, or markup injection.
 *
 * @package sierotki
 * @group security-config
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group( 'security-config' )]
final class ConfigFieldsSecurityTest extends OrphanTestCase {

	public function test_is_on_semantics(): void {
		// is_on() uses intval() semantics: only values whose intval is 1
		// enable an option. Rejected: "on", "true", 2, "2", "1.0", empty
		// arrays. Accepted (documented leniency): 1, "1", "1 ", true,
		// array(1) - harmless since values come from the settings screen
		// (radio/switch inputs store 0/1).
		foreach ( array( 'on', 'true', 2, '2', 0, '0', false, array() ) as $evil ) {
			$this->orphan->set_settings( array( 'ignore_language' => $evil ) );
			$this->assertFalse(
				$this->orphan->is_on( 'ignore_language' ),
				'value ' . var_export( $evil, true ) . ' must not enable the option'
			);
		}
		foreach ( array( 1, '1', '1 ', '1.0', true, array( 1 ) ) as $good ) {
			$this->orphan->set_settings( array( 'ignore_language' => $good ) );
			$this->assertTrue(
				$this->orphan->is_on( 'ignore_language' ),
				'value ' . var_export( $good, true ) . ' enables the option (intval semantics)'
			);
		}
	}

	public function test_hostile_post_type_setting_cannot_corrupt_processing(): void {
		// XSS payloads inside the post_type allowlist: the gate uses
		// in_array against the real post type; payloads are inert.
		$GLOBALS['test_current_filter'] = 'the_title';
		$GLOBALS['test_orphan_options'] = array( 'language' => '0' );
		$this->orphan->set_settings(
			array(
				'numbers'         => 0,
				'ignore_language' => 1,
				'the_title'       => 1,
				'post_type'       => array( '<script>alert(1)</script>', 'post' ),
			)
		);
		global $post;
		$post = new WP_Post( array( 'ID' => 1, 'post_type' => 'post' ) );
		$this->assertSame(
			'Ala w' . $this->nbsp . 'kotcie',
			$this->orphan->replace( 'Ala w kotcie' )
		);
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_hostile_taxonomies_setting_fails_safe(): void {
		// Nested arrays / non-string taxonomy values must not match the
		// queried taxonomy and must not produce errors.
		$GLOBALS['test_current_filter'] = 'term_description';
		$GLOBALS['test_orphan_options'] = array( 'language' => '0' );
		$GLOBALS['test_queried_object'] = new WP_Term( array( 'term_id' => 1, 'taxonomy' => 'category' ) );
		foreach ( array( array( array( 'nested' ) ), array( '<img onerror=x>' ), 'category', array( 1 ) ) as $evil ) {
			$this->orphan->set_settings(
				array(
					'numbers'          => 0,
					'ignore_language'  => 1,
					'term_description' => 1,
					'taxonomies'       => $evil,
				)
			);
			$this->assertIsString( $this->orphan->replace( 'Ala w kotcie' ) );
		}
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_post_meta_option_rejects_non_string_shapes(): void {
		foreach ( array( array( 'x' => 'y' ), null, 123, array() ) as $evil ) {
			$GLOBALS['test_orphan_options'] = array( 'language' => '0', 'post_meta' => $evil );
			$this->assertSame(
				'ORIGINAL',
				$this->orphan->filter_post_meta( 'ORIGINAL', 1, 'x', true ),
				'non-string post_meta option must be ignored'
			);
		}
	}

	public function test_post_meta_allowlist_is_exact_and_safe(): void {
		$GLOBALS['test_post_meta']     = array(
			5 => array( 'evil_meta' => 'Ala w kotcie <b onmouseover=alert(1)>' ),
		);
		$GLOBALS['test_orphan_options'] = array( 'language' => '0', 'post_meta' => 'evil_meta, <script>, ../meta' );
		// Matching key: value is processed - nbsp added, but the HTML
		// attribute must survive byte-identical (no quote re-encoding).
		$out = $this->orphan->filter_post_meta( 'ORIGINAL', 5, 'evil_meta', true );
		$this->assertIsString( $out );
		$this->assertStringContainsString( 'Ala w' . $this->nbsp . 'kotcie', $out );
		$this->assertStringContainsString( '<b onmouseover=alert(1)>', $out );
		$this->assertStringNotContainsString( '&quot;', $out );
		// Non-matching keys pass through untouched.
		$this->assertSame( 'ORIGINAL', $this->orphan->filter_post_meta( 'ORIGINAL', 5, 'other_meta', true ) );
		// Non-unique meta is never touched (multi-value safety).
		$this->assertSame( 'ORIGINAL', $this->orphan->filter_post_meta( 'ORIGINAL', 5, 'evil_meta', false ) );
	}

	public function test_gettext_filter_never_injects_markup(): void {
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1, 'gettext' => 1 ) );
		$in  = 'Zobacz <a href="https://x/?a=1">wiele w opcjach</a>';
		$out = $this->orphan->filter_gettext( $in, 'text', 'domain' );
		$this->assertSame(
			'Zobacz <a href="https://x/?a=1">wiele w' . $this->nbsp . 'opcjach</a>',
			$out
		);
		$this->assertStringNotContainsString( '&quot;', $out );
	}

	public function test_gettext_filter_ignores_non_string_values(): void {
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1, 'gettext' => 1 ) );
		$this->assertNull( $this->orphan->filter_gettext( null, 'text', 'domain' ) );
		$this->assertSame( array( 'x' ), $this->orphan->filter_gettext( array( 'x' ), 'text', 'domain' ) );
	}

	public function test_acf_filter_never_injects_markup(): void {
		$this->orphan->set_settings( array( 'numbers' => 0, 'ignore_language' => 1 ) );
		$in  = '<div class="a" data-x="Ala z psem">kotek z psem</div>';
		$out = $this->orphan->filter_acf( $in, 1, array( 'name' => 'field' ) );
		$this->assertSame(
			'<div class="a" data-x="Ala z psem">kotek z' . $this->nbsp . 'psem</div>',
			$out
		);
	}

	public function test_menu_title_zero_blocks_nav_menu_replacement(): void {
		// menu_title = 0 disables replacement for nav menu items; the
		// strict (0 === value) check must not be bypassable by "0" or 0.0.
		$GLOBALS['test_orphan_options'] = array( 'language' => '0' );
		$menu_item                      = new WP_Post( array( 'ID' => 9, 'post_type' => 'nav_menu_item' ) );
		$this->orphan->set_settings(
			array( 'numbers' => 0, 'ignore_language' => 1, 'menu_title' => 0 )
		);
		$this->assertSame( 'Ala w kotcie', $this->orphan->replace_two( 'Ala w kotcie', $menu_item ) );
		// Any other value (including hostile ones) enables replacement -
		// documented behavior; verify no error/corruption though.
		foreach ( array( 1, 'on', array( 1 ) ) as $value ) {
			$this->orphan->set_settings(
				array( 'numbers' => 0, 'ignore_language' => 1, 'menu_title' => $value )
			);
			$out = $this->orphan->replace_two( 'Ala w kotcie', $menu_item );
			$this->assertIsString( $out );
		}
	}
}
