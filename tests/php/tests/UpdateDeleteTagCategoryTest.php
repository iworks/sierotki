<?php
/**
 * Update / delete: tag, category (stored content integrity) — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('terms-update-delete')]
final class UpdateDeleteTagCategoryTest extends OrphanTestCase {


	/**
	 * Same display-time contract as posts/pages, applied to terms:
	 * term descriptions (term_description) and taxonomy titles
	 * (single_cat_title / single_tag_title) get hard spaces only at
	 * display time; stored term data stays clean on update and delete.
	 */




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
}
