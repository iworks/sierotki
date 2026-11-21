<?php
/**
 * Update / delete: post, page (stored content integrity) — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('entries-update-delete')]
final class UpdateDeletePostPageTest extends OrphanTestCase {


	/**
	 * The plugin never writes replacements back to the database - it only
	 * replaces at display time. Simulate an update (post fields change) and
	 * a delete (post removed) and assert the stored title/content are
	 * byte-identical to what was saved, while the display-side filters
	 * still add hard spaces.
	 */


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
}
