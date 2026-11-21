<?php
/**
 * Pass-through methods — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('engine-internals')]
final class PassThroughMethodsTest extends OrphanTestCase {


	public function test_string_replacement_pass_through_matches_replace(): void {
		$in = 'Ala z psem';
		$this->assertSame( $this->orphan->replace( $in ), $this->orphan->test_string_replacement( $in ) );
	}

	public function test_unconditional_replacement_pass_through_on_html(): void {
		$in = '<p>Ala z psem</p>';
		$this->assertSame( $this->orphan->replace( $in ), $this->orphan->test_unconditional_replacement( $in ) );
	}
}
