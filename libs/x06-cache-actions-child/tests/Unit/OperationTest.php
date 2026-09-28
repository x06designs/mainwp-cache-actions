<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Unit;

use X06CacheActionsChild\Features\CacheActions\Operation;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class OperationTest extends TestCase {

	/**
	 * @dataProvider known_operations
	 */
	public function test_parses_known_operations( string $value ): void {
		$this->assertSame( $value, Operation::try_from( $value ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public function known_operations(): array {
		return array(
			'clear caches'          => array( 'clear_caches' ),
			'clear caches minified' => array( 'clear_caches_minified' ),
			'sync library'          => array( 'sync_library' ),
		);
	}

	/**
	 * @dataProvider unknown_values
	 *
	 * @param mixed $value Raw request value.
	 */
	public function test_rejects_anything_else( $value ): void {
		$this->assertNull( Operation::try_from( $value ) );
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public function unknown_values(): array {
		return array(
			'null'          => array( null ),
			'empty string'  => array( '' ),
			'unknown op'    => array( 'drop_database' ),
			'different case' => array( 'CLEAR_CACHES' ),
			'padded'        => array( ' clear_caches' ),
			'array'         => array( array( 'clear_caches' ) ),
			'integer'       => array( 1 ),
		);
	}
}
