<?php
/**
 * Numbers stay together — test group.
 *
 * @package sierotki
 */

require_once __DIR__ . '/../OrphanTestCase.php';

#[\PHPUnit\Framework\Attributes\Group('numbers')]
final class NumbersStayTogetherTest extends OrphanTestCase {


	public function test_numbers_option_joins_number_and_unit(): void {
		$this->orphan->set_numbers();
		$this->assertSame(
			'10 lat to' . $this->nbsp . '5 kg',
			$this->orphan->replace( '10 lat to 5 kg' )
		);
	}
}
