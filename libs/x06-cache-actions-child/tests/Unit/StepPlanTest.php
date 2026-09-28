<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Unit;

use X06CacheActionsChild\Features\CacheActions\Operation;
use X06CacheActionsChild\Features\CacheActions\StepPlan;
use X06CacheActionsChild\Features\CacheActions\Steps\Step;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class StepPlanTest extends TestCase {

	public function test_clear_caches_runs_elementor_before_wpfc(): void {
		$this->assertSame(
			array( 'elementor_clear_cache', 'wpfc_delete_cache' ),
			$this->step_names( Operation::CLEAR_CACHES )
		);
	}

	public function test_clear_caches_minified_runs_elementor_before_wpfc_with_minified(): void {
		$this->assertSame(
			array( 'elementor_clear_cache', 'wpfc_delete_cache_and_minified' ),
			$this->step_names( Operation::CLEAR_CACHES_MINIFIED )
		);
	}

	public function test_sync_library_only_syncs(): void {
		$this->assertSame(
			array( 'elementor_sync_library' ),
			$this->step_names( Operation::SYNC_LIBRARY )
		);
	}

	public function test_every_operation_has_a_plan(): void {
		$plan = StepPlan::default();
		foreach ( Operation::ALL as $operation ) {
			$this->assertNotEmpty( $plan->steps_for( $operation ), $operation );
		}
	}

	/**
	 * @return list<string>
	 */
	private function step_names( string $operation ): array {
		return array_map(
			static function ( Step $step ): string {
				return $step->name();
			},
			StepPlan::default()->steps_for( $operation )
		);
	}
}
