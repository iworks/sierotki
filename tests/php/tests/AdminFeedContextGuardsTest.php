<?php
/**
 * Admin / feed context guards — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('admin-feed-guards')]
final class AdminFeedContextGuardsTest extends OrphanTestCase {


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
