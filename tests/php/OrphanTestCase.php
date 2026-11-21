<?php
/**
 * Orphan replacement engine tests.
 *
 * Covers the public `replace()` entry point end-to-end: the shortcode
 * guard (3.5.1 Divi regression), plain-string and HTML processing paths,
 * protected-tag skipping and the non-breaking space (U+00A0) output.
 *
 * The class methods under test are private; tests drive them through the
 * public `replace()` with `is_admin`/`is_feed` stubs and injected terms,
 * mirroring how WordPress invokes the plugin.
 *
 * @package sierotki
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

/**
 * Test double: exposes the private replacement engine and injects
 * settings/terms (both properties are private in iworks_orphan, so
 * reflection is used) without touching the database.
 */
class OrphanTestDouble extends iworks_orphan {

	public function __construct() {
		parent::__construct();
		$this->set_private( 'settings', array( 'numbers' => 0, 'ignore_language' => 0 ) );
		$this->set_private( 'terms', array( 'w', 'z', 'i', 'o', 'u', 'a', 'to', 'oraz' ) );
	}

	private function set_private( $name, $value ) {
		$prop = new ReflectionProperty( 'iworks_orphan', $name );
		$prop->setValue( $this, $value );
	}

	/**
	 * Public pass-through to the private string replacement.
	 */
	public function set_numbers() {
		$this->set_private( 'settings', array( 'numbers' => 1, 'ignore_language' => 0 ) );
	}

	public function set_ignore_language() {
		$this->set_private( 'settings', array( 'numbers' => 0, 'ignore_language' => 1 ) );
	}

	public function set_settings( array $settings ) {
		$this->set_private( 'settings', $settings );
	}

	public function test_string_replacement( $content ) {
		$method = new ReflectionMethod( 'iworks_orphan', 'string_replacement' );
		return $method->invoke( $this, $content );
	}

	/**
	 * Public pass-through to the private unconditional replacement.
	 */
	public function test_unconditional_replacement( $content ) {
		$method = new ReflectionMethod( 'iworks_orphan', 'unconditional_replacement' );
		return $method->invoke( $this, $content );
	}
}

/**
 * Shared base for all Orphans test groups.
 *
 * Each group lives in its own test class so PHPUnit's --testdox output
 * prints a named header per group.
 *
 * @package sierotki
 */
abstract class OrphanTestCase extends TestCase {


	protected OrphanTestDouble $orphan;

	/**
	 * The real U+00A0 non-breaking space the plugin inserts.
	 */
	protected string $nbsp = "\u{00A0}";

	protected function setUp(): void {
		$this->orphan = new OrphanTestDouble();
	}


	protected function load_own_orphans( $own_orphans ): array {
		$GLOBALS['test_orphan_options'] = array(
			'language'                   => '0',
			'iworks_orphan_own_orphans' => $own_orphans,
		);
		$GLOBALS['test_locale'] = 'pl_PL';
		$orphan                 = new iworks_orphan();
		$prop                   = new ReflectionProperty( 'iworks_orphan', 'terms' );
		$prop->setValue( $orphan, array() );
		$method = new ReflectionMethod( 'iworks_orphan', '_terms' );
		return $method->invoke( $orphan );
	}


	protected function stored_entry( $post_type = 'post', $id = 1 ) {
		return array(
			'post' => new WP_Post( array(
				'ID'           => $id,
				'post_type'    => $post_type,
				'post_title'   => 'Ala w kotcie',
				'post_content' => '<p>Ala i kot z psem</p>',
			) ),
		);
	}


	protected function with_post( $post_type ) {
		$GLOBALS['test_current_filter'] = 'the_title';
		$GLOBALS['test_post']           = new WP_Post( array( 'ID' => 1, 'post_type' => $post_type ) );
		global $post;
		$post = $GLOBALS['test_post'];
	}


	protected function enable_entry_filters( array $post_types = array( 'post', 'page' ) ) {
		$this->orphan->set_settings( array(
			'numbers'         => 0,
			'ignore_language' => 0,
			'the_title'       => 1,
			'the_content'     => 1,
			'post_type'       => $post_types,
		) );
	}


	protected function stored_term( $taxonomy, $id = 10 ) {
		return new WP_Term( array(
			'term_id'     => $id,
			'taxonomy'    => $taxonomy,
			'name'        => 'Ala w kotcie',
			'description' => 'Kategoria i tag z opisem',
		) );
	}


	protected function with_queried_term( $taxonomy ) {
		$GLOBALS['test_queried_object'] = $this->stored_term( $taxonomy );
		$GLOBALS['test_current_filter'] = 'term_description';
	}


	protected function enable_term_filters( array $taxonomies = array( 'category', 'post_tag' ) ) {
		$this->orphan->set_settings( array(
			'numbers'         => 0,
			'ignore_language' => 0,
			'term_description' => 1,
			'taxonomy_title'  => 1,
			'taxonomies'      => $taxonomies,
		) );
	}


	protected function load_terms_for_language( $language, string $locale = 'pl_PL' ): array {
		$GLOBALS['test_orphan_options'] = array( 'language' => $language );
		$GLOBALS['test_locale']         = $locale;
		$orphan                         = new iworks_orphan();
		// Clear the constructor-injected terms so the file loader runs.
		$prop = new ReflectionProperty( 'iworks_orphan', 'terms' );
		$prop->setValue( $orphan, array() );
		$method = new ReflectionMethod( 'iworks_orphan', '_terms' );
		return $method->invoke( $orphan );
	}
}
