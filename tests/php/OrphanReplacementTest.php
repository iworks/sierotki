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

final class OrphanReplacementTest extends TestCase {

	private OrphanTestDouble $orphan;

	/**
	 * The real U+00A0 non-breaking space the plugin inserts.
	 */
	private string $nbsp = "\u{00A0}";

	protected function setUp(): void {
		$this->orphan = new OrphanTestDouble();
	}

	// ------------------------------------------------------------ plain strings

	public function test_replaces_space_after_single_letter_w(): void {
		$this->assertSame(
			"Ala w{$this->nbsp}kotcie",
			$this->orphan->replace( 'Ala w kotcie' )
		);
	}

	public function test_replaces_space_after_single_letter_z(): void {
		$this->assertSame(
			"Kotek z{$this->nbsp}psem",
			$this->orphan->replace( 'Kotek z psem' )
		);
	}

	public function test_replaces_all_orphan_occurrences(): void {
		$this->assertSame(
			"Ala i{$this->nbsp}kot z{$this->nbsp}psem a{$this->nbsp}takze o{$this->nbsp}psie",
			$this->orphan->replace( 'Ala i kot z psem a takze o psie' )
		);
	}

	public function test_does_not_touch_words_without_orphans(): void {
		$content = 'Wieder一句话 bez zmian';
		$this->assertSame( $content, $this->orphan->replace( $content ) );
	}

	public function test_leaves_quoted_text_intact_when_no_orphan(): void {
		$this->assertSame(
			'powiedział "cos tam"',
			$this->orphan->replace( 'powiedział "cos tam"' )
		);
	}

	public function test_empty_string_returns_unchanged(): void {
		$this->assertSame( '', $this->orphan->replace( '' ) );
	}

	public function test_non_string_returns_unchanged(): void {
		$this->assertNull( $this->orphan->replace( null ) );
		$this->assertSame( 123, $this->orphan->replace( 123 ) );
	}

	// ------------------------------------------------------------- HTML content

	public function test_replaces_inside_html_text_nodes(): void {
		$this->assertSame(
			"<p>Ala w{$this->nbsp}kotcie</p>",
			$this->orphan->replace( '<p>Ala w kotcie</p>' )
		);
	}

	public function test_html_attributes_are_never_rewritten(): void {
		// The anchor text gets the nbsp; href and title attribute values must
		// stay byte-identical.
		$in  = '<a href="https://example.com/?a=1" title="Kotek z psem">kotek z psem</a>';
		$out = $this->orphan->replace( $in );
		$this->assertStringContainsString( 'href="https://example.com/?a=1"', $out );
		$this->assertStringContainsString( 'title="Kotek z psem"', $out );
		$this->assertStringContainsString( ">kotek z{$this->nbsp}psem</a>", $out );
	}

	public function test_no_quote_entities_are_introduced_in_attributes(): void {
		// Straight quotes in ATTRIBUTES must never be re-encoded (3.5.0 Divi
		// regression). Text nodes are HTML-encoded by set_modifiable_text()
		// by design - only attribute/shortcode integrity is asserted here.
		$out = $this->orphan->replace( '<a href="https://example.com/?q=x" title="Kotek z psem">kotek z psem</a>' );
		$this->assertStringNotContainsString( '&quot;', $out );
		$this->assertStringContainsString( 'title="Kotek z psem"', $out );
	}

	// ------------------------------------------------- protected tags are skipped

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

	// ----------------------------------------------------- shortcode guard (3.5.1)

	public function test_raw_shortcode_content_is_skipped_entirely(): void {
		// Divi parent modules pass raw child shortcodes; quotes must never be
		// re-encoded to &quot; (3.5.0 regression, fixed in 3.5.1).
		$in = '[dica_divi_carouselitem title="Imie Nazwisko" button_text="zobacz w portfolio" image="https://example.com/photo.jpg"]';
		$this->assertSame( $in, $this->orphan->replace( $in ) );
	}

	public function test_raw_shortcode_with_html_inside_attribute_is_skipped(): void {
		$in = '[dica_divi_carouselitem title="<h3>Imie Nazwisko</h3>" button_text="zobacz w portfolio"]';
		$this->assertSame( $in, $this->orphan->replace( $in ) );
	}

	public function test_shortcode_pair_is_skipped(): void {
		$in = '[dica_divi_carousel]Ala w kotcie[dica_divi_carouselitem title="Imie"][/dica_divi_carousel]';
		$this->assertSame( $in, $this->orphan->replace( $in ) );
	}

	public function test_square_brackets_in_prose_still_get_replaced(): void {
		// "[key]" has no space/slash after the word - not a shortcode.
		$this->assertSame(
			"Zobacz [key] w{$this->nbsp}tabeli",
			$this->orphan->replace( 'Zobacz [key] w tabeli' )
		);
	}

	public function test_markdown_style_bracket_with_space_is_skipped(): void {
		// Documented trade-off: "[zobacz " looks like a shortcode tag.
		$in = 'cos [zobacz w linku](https://example.com)';
		$this->assertSame( $in, $this->orphan->replace( $in ) );
	}

	// ------------------------------------------------- nbsp character integrity

	public function test_real_nbsp_character_is_used_not_entity(): void {
		$out = $this->orphan->replace( 'Ala w kotcie' );
		$this->assertStringContainsString( "\u{00A0}", $out );
		$this->assertStringNotContainsString( '&nbsp;', $out );
	}

	public function test_nbsp_is_not_double_encoded_in_html(): void {
		$out = $this->orphan->replace( '<p>Ala w kotcie</p>' );
		$this->assertStringNotContainsString( '&amp;nbsp;', $out );
		$this->assertStringNotContainsString( 'Â', $out );
	}

	public function test_polish_typographic_quote_starts_orphan_chain(): void {
		$this->assertSame(
			"powiedzial „cos w{$this->nbsp}srodku”",
			$this->orphan->replace( 'powiedzial „cos w srodku”' )
		);
	}

	// ------------------------------------------------------- numbers stay together

	public function test_numbers_option_joins_number_and_unit(): void {
		$this->orphan->set_numbers();
		$this->assertSame(
			'10 lat to' . $this->nbsp . '5 kg',
			$this->orphan->replace( '10 lat to 5 kg' )
		);
	}

	// ------------------------------------------- post / page title and content

	/**
	 * Simulates how WordPress renders an entry: the title goes through
	 * `the_title` (replace_two, object id passed), the content through
	 * `the_content`, with the post-type gate active.
	 */
	private function with_post( $post_type ) {
		$GLOBALS['test_current_filter'] = 'the_title';
		$GLOBALS['test_post']           = new WP_Post( array( 'ID' => 1, 'post_type' => $post_type ) );
		global $post;
		$post = $GLOBALS['test_post'];
	}

	private function enable_entry_filters( array $post_types = array( 'post', 'page' ) ) {
		$this->orphan->set_settings( array(
			'numbers'         => 0,
			'ignore_language' => 0,
			'the_title'       => 1,
			'the_content'     => 1,
			'post_type'       => $post_types,
		) );
	}

	public function test_post_title_gets_hard_space(): void {
		$this->enable_entry_filters();
		$this->with_post( 'post' );
		$this->assertSame(
			'Ala w' . $this->nbsp . 'kotcie',
			$this->orphan->replace_two( 'Ala w kotcie', 1 )
		);
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_page_title_gets_hard_space(): void {
		$this->enable_entry_filters();
		$this->with_post( 'page' );
		$this->assertSame(
			'Poradnik z' . $this->nbsp . 'przykładami',
			$this->orphan->replace_two( 'Poradnik z przykładami', 2 )
		);
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_post_content_gets_hard_space(): void {
		$this->enable_entry_filters();
		$GLOBALS['test_current_filter'] = 'the_content';
		$this->with_post( 'post' );
		$this->assertSame(
			'<p>Ala w' . $this->nbsp . 'kotcie i' . $this->nbsp . 'z' . $this->nbsp . 'psem</p>',
			$this->orphan->replace( '<p>Ala w kotcie i z psem</p>' )
		);
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_title_is_skipped_when_post_type_not_selected(): void {
		$this->enable_entry_filters( array( 'page' ) );
		$this->with_post( 'post' );
		$this->assertSame(
			'Ala w kotcie',
			$this->orphan->replace_two( 'Ala w kotcie', 1 ),
			'post type "post" is not in the allowed list - no replacement expected'
		);
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_title_is_skipped_without_object_id(): void {
		$this->enable_entry_filters();
		$this->with_post( 'post' );
		// replace_two without an object id (e.g. menu contexts) bails out.
		$this->assertSame( 'Ala w kotcie', $this->orphan->replace_two( 'Ala w kotcie', null ) );
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_title_stays_untouched_in_admin(): void {
		$this->enable_entry_filters();
		$this->with_post( 'post' );
		$GLOBALS['test_is_admin']       = true;
		$GLOBALS['test_current_filter'] = '';
		$this->assertSame( 'Ala w kotcie', $this->orphan->replace_two( 'Ala w kotcie', 1 ) );
		$GLOBALS['test_is_admin'] = false;
	}

	// ------------------------------------- update / delete: stored content integrity

	/**
	 * The plugin never writes replacements back to the database - it only
	 * replaces at display time. Simulate an update (post fields change) and
	 * a delete (post removed) and assert the stored title/content are
	 * byte-identical to what was saved, while the display-side filters
	 * still add hard spaces.
	 */

	private function stored_entry( $post_type = 'post', $id = 1 ) {
		return array(
			'post' => new WP_Post( array(
				'ID'           => $id,
				'post_type'    => $post_type,
				'post_title'   => 'Ala w kotcie',
				'post_content' => '<p>Ala i kot z psem</p>',
			) ),
		);
	}

	public function test_update_post_stores_content_without_hard_spaces(): void {
		$entry = $this->stored_entry( 'post', 1 );
		// Simulated save: wp_update_post() stores raw content; the plugin has
		// no save-time hook, so the "stored" value is untouched.
		$stored_title   = $entry['post']->post_title;
		$stored_content = $entry['post']->post_content;
		$this->assertSame( 'Ala w kotcie', $stored_title );
		$this->assertSame( '<p>Ala i kot z psem</p>', $stored_content );
		$this->assertStringNotContainsString( $this->nbsp, $stored_title );
		$this->assertStringNotContainsString( $this->nbsp, $stored_content );
	}

	public function test_update_page_stores_content_without_hard_spaces(): void {
		$entry = $this->stored_entry( 'page', 2 );
		$this->assertSame( 'Ala w kotcie', $entry['post']->post_title );
		$this->assertSame( '<p>Ala i kot z psem</p>', $entry['post']->post_content );
		$this->assertStringNotContainsString( $this->nbsp, $entry['post']->post_content );
	}

	public function test_stored_content_differs_from_display_output(): void {
		$this->enable_entry_filters();
		$GLOBALS['test_current_filter'] = 'the_content';
		$entry                          = $this->stored_entry( 'post', 1 );
		// global $post wired for the post-type gate.
		$GLOBALS['test_post'] = $entry['post'];
		global $post;
		$post = $entry['post'];
		$displayed = $this->orphan->replace( $entry['post']->post_content );
		$this->assertNotSame( $entry['post']->post_content, $displayed );
		$this->assertSame(
			'<p>Ala i' . $this->nbsp . 'kot z' . $this->nbsp . 'psem</p>',
			$displayed
		);
		// Stored copy is still clean after display-time replacement.
		$this->assertSame( '<p>Ala i kot z psem</p>', $entry['post']->post_content );
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_delete_post_leaves_no_trace_in_content(): void {
		$entry = $this->stored_entry( 'post', 1 );
		// Simulate delete: the stored object is removed; nothing was written
		// back, so there is no nbsp anywhere in what was stored.
		$stored = $entry['post'];
		unset( $entry['post'] ); // deletion
		$this->assertSame( 'Ala w kotcie', $stored->post_title );
		$this->assertSame( '<p>Ala i kot z psem</p>', $stored->post_content );
		$this->assertStringNotContainsString( $this->nbsp, $stored->post_title . $stored->post_content );
	}

	public function test_delete_page_leaves_no_trace_in_content(): void {
		$entry = $this->stored_entry( 'page', 2 );
		$stored = $entry['post'];
		unset( $entry['post'] );
		$this->assertStringNotContainsString( $this->nbsp, $stored->post_title );
		$this->assertStringNotContainsString( $this->nbsp, $stored->post_content );
	}

	public function test_repeated_display_cycles_do_not_accumulate_replacements(): void {
		$this->enable_entry_filters();
		$GLOBALS['test_current_filter'] = 'the_content';
		$entry                          = $this->stored_entry( 'post', 1 );
		$GLOBALS['test_post']           = $entry['post'];
		global $post;
		$post = $entry['post'];
		$once  = $this->orphan->replace( $entry['post']->post_content );
		$twice = $this->orphan->replace( $once );
		$this->assertSame( $once, $twice );
		$GLOBALS['test_current_filter'] = '';
	}

	// ------------------------------------ update / delete: tag, category integrity

	/**
	 * Same display-time contract as posts/pages, applied to terms:
	 * term descriptions (term_description) and taxonomy titles
	 * (single_cat_title / single_tag_title) get hard spaces only at
	 * display time; stored term data stays clean on update and delete.
	 */

	private function stored_term( $taxonomy, $id = 10 ) {
		return new WP_Term( array(
			'term_id'     => $id,
			'taxonomy'    => $taxonomy,
			'name'        => 'Ala w kotcie',
			'description' => 'Kategoria i tag z opisem',
		) );
	}

	private function with_queried_term( $taxonomy ) {
		$GLOBALS['test_queried_object'] = $this->stored_term( $taxonomy );
		$GLOBALS['test_current_filter'] = 'term_description';
	}

	private function enable_term_filters( array $taxonomies = array( 'category', 'post_tag' ) ) {
		$this->orphan->set_settings( array(
			'numbers'         => 0,
			'ignore_language' => 0,
			'term_description' => 1,
			'taxonomy_title'  => 1,
			'taxonomies'      => $taxonomies,
		) );
	}

	public function test_update_category_stores_description_without_hard_spaces(): void {
		$term = $this->stored_term( 'category' );
		$this->assertSame( 'Kategoria i tag z opisem', $term->description );
		$this->assertStringNotContainsString( $this->nbsp, $term->description );
		$this->assertStringNotContainsString( $this->nbsp, $term->name );
	}

	public function test_update_tag_stores_description_without_hard_spaces(): void {
		$term = $this->stored_term( 'post_tag' );
		$this->assertSame( 'Kategoria i tag z opisem', $term->description );
		$this->assertStringNotContainsString( $this->nbsp, $term->name );
	}

	public function test_category_description_gets_hard_space_on_display(): void {
		$this->enable_term_filters();
		$this->with_queried_term( 'category' );
		$this->assertSame(
			'Kategoria i' . $this->nbsp . 'tag z' . $this->nbsp . 'opisem',
			$this->orphan->replace( 'Kategoria i tag z opisem' )
		);
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_tag_description_gets_hard_space_on_display(): void {
		$this->enable_term_filters();
		$this->with_queried_term( 'post_tag' );
		$this->assertSame(
			'Kategoria i' . $this->nbsp . 'tag z' . $this->nbsp . 'opisem',
			$this->orphan->replace( 'Kategoria i tag z opisem' )
		);
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_term_description_skipped_when_taxonomy_not_selected(): void {
		$this->enable_term_filters( array( 'category' ) );
		$this->with_queried_term( 'post_tag' );
		$this->assertSame(
			'Kategoria i tag z opisem',
			$this->orphan->replace( 'Kategoria i tag z opisem' )
		);
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_category_title_gets_hard_space_on_display(): void {
		$this->enable_term_filters();
		$GLOBALS['test_current_filter'] = 'single_cat_title';
		$this->assertSame(
			'Ala w' . $this->nbsp . 'kotcie',
			$this->orphan->replace( 'Ala w kotcie' )
		);
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_tag_title_gets_hard_space_on_display(): void {
		$this->enable_term_filters();
		$GLOBALS['test_current_filter'] = 'single_tag_title';
		$this->assertSame(
			'Ala z' . $this->nbsp . 'psem',
			$this->orphan->replace( 'Ala z psem' )
		);
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_delete_category_leaves_no_trace_in_stored_data(): void {
		$term = $this->stored_term( 'category' );
		// Simulate delete: stored term removed; nothing was written back.
		$stored = $term;
		unset( $GLOBALS['test_queried_object'] );
		$this->assertSame( 'Ala w kotcie', $stored->name );
		$this->assertSame( 'Kategoria i tag z opisem', $stored->description );
		$this->assertStringNotContainsString( $this->nbsp, $stored->name . $stored->description );
	}

	public function test_delete_tag_leaves_no_trace_in_stored_data(): void {
		$term = $this->stored_term( 'post_tag' );
		$stored = $term;
		unset( $GLOBALS['test_queried_object'] );
		$this->assertStringNotContainsString( $this->nbsp, $stored->name . $stored->description );
	}

	// --------------------------------- create / modify: tag, category save + display

	/**
	 * Simulates the save path (wp_insert_term / wp_update_term): the name
	 * and description written by the user must be stored verbatim - no
	 * hard spaces injected at creation or modification - while the display
	 * filters still produce them.
	 */

	public function test_create_category_stores_name_and_description_verbatim(): void {
		// User saves a new category with orphans in both fields.
		$created = new WP_Term( array(
			'term_id'     => 21,
			'taxonomy'    => 'category',
			'name'        => 'Porady z ogrodu',
			'description' => 'Wszystko o sadzie i warzywniku',
		) );
		$this->assertSame( 'Porady z ogrodu', $created->name );
		$this->assertSame( 'Wszystko o sadzie i warzywniku', $created->description );
		$this->assertStringNotContainsString( $this->nbsp, $created->name );
		$this->assertStringNotContainsString( $this->nbsp, $created->description );
	}

	public function test_create_tag_stores_name_and_description_verbatim(): void {
		$created = new WP_Term( array(
			'term_id'     => 22,
			'taxonomy'    => 'post_tag',
			'name'        => 'wypoczynek z dzieckiem',
			'description' => 'Pomysly na urlop i wakacje',
		) );
		$this->assertSame( 'wypoczynek z dzieckiem', $created->name );
		$this->assertStringNotContainsString( $this->nbsp, $created->name . $created->description );
	}

	public function test_modify_category_description_keeps_storage_clean(): void {
		$term = $this->stored_term( 'category' );
		// User edits the description - the new value is stored verbatim.
		$term->description = 'Zmieniony opis i tag';
		$this->assertSame( 'Zmieniony opis i tag', $term->description );
		$this->assertStringNotContainsString( $this->nbsp, $term->description );
	}

	public function test_modify_tag_name_keeps_storage_clean(): void {
		$term = $this->stored_term( 'post_tag' );
		$term->name = 'nowy tag z opisem';
		$this->assertSame( 'nowy tag z opisem', $term->name );
		$this->assertStringNotContainsString( $this->nbsp, $term->name );
	}

	public function test_modified_category_renders_with_hard_spaces(): void {
		$this->enable_term_filters();
		$term = $this->stored_term( 'category' );
		$term->description = 'Zmieniony opis i tag';
		$GLOBALS['test_queried_object'] = $term;
		$GLOBALS['test_current_filter'] = 'term_description';
		$this->assertSame(
			'Zmieniony opis i' . $this->nbsp . 'tag',
			$this->orphan->replace( $term->description )
		);
		// Storage still clean after rendering.
		$this->assertSame( 'Zmieniony opis i tag', $term->description );
		$GLOBALS['test_current_filter'] = '';
	}

	public function test_created_tag_name_renders_with_hard_spaces(): void {
		$this->enable_term_filters();
		$GLOBALS['test_current_filter'] = 'single_tag_title';
		$this->assertSame(
			'wypoczynek z' . $this->nbsp . 'dzieckiem',
			$this->orphan->replace( 'wypoczynek z dzieckiem' )
		);
		$GLOBALS['test_current_filter'] = '';
	}

	// ------------------------------------------------------------- locale gate

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

	// ------------------------------------------------------- pass-through methods

	public function test_string_replacement_pass_through_matches_replace(): void {
		$in = 'Ala z psem';
		$this->assertSame( $this->orphan->replace( $in ), $this->orphan->test_string_replacement( $in ) );
	}

	public function test_unconditional_replacement_pass_through_on_html(): void {
		$in = '<p>Ala z psem</p>';
		$this->assertSame( $this->orphan->replace( $in ), $this->orphan->test_unconditional_replacement( $in ) );
	}

	// ------------------------------------------------------------- admin/feed guard

	public function test_admin_context_returns_content_unchanged(): void {
		$GLOBALS['test_is_admin'] = true;
		$in                       = 'Ala w kotcie';
		$this->assertSame( $in, $this->orphan->replace( $in ) );
		$GLOBALS['test_is_admin'] = false;
	}

	public function test_feed_context_returns_content_unchanged(): void {
		$GLOBALS['test_is_feed'] = true;
		$in                      = 'Ala w kotcie';
		$this->assertSame( $in, $this->orphan->replace( $in ) );
		$GLOBALS['test_is_feed'] = false;
	}
}

