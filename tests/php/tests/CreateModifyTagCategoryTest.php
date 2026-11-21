<?php
/**
 * Create / modify: tag, category — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('terms-create-modify')]
final class CreateModifyTagCategoryTest extends OrphanTestCase {


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
}
