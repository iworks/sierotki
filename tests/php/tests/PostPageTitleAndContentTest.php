<?php
/**
 * Post / page title and content — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('entries')]
final class PostPageTitleAndContentTest extends OrphanTestCase {


	/**
	 * Simulates how WordPress renders an entry: the title goes through
	 * `the_title` (replace_two, object id passed), the content through
	 * `the_content`, with the post-type gate active.
	 */


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
}
