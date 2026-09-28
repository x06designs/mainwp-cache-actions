<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Features\CacheActions;

use X06CacheActionsChild\Features\CacheActions\Steps\Step;
use X06CacheActionsChild\Features\CacheActions\Steps\StepResult;

/**
 * Runs every step of an operation, even after one fails: a partial Elementor clear makes
 * the WPFC purge more necessary, not less.
 */
final class OperationRunner {

	public const API_VERSION = 1;

	private StepPlan $plan;

	public function __construct( StepPlan $plan ) {
		$this->plan = $plan;
	}

	/**
	 * @return array{op: string, companion_version: string, api_version: int, steps: list<array{step: string, status: string, detail: string|null, error?: string}>}
	 */
	public function run( string $operation ): array {
		return array(
			'op'                => $operation,
			'companion_version' => X06_CACHE_ACTIONS_CHILD_VERSION,
			'api_version'       => self::API_VERSION,
			'steps'             => array_map(
				fn( Step $step ): array => $this->run_step( $step )->to_array(),
				$this->plan->steps_for( $operation )
			),
		);
	}

	/**
	 * Output would corrupt MainWP's response body, so anything a plugin prints is dropped.
	 */
	private function run_step( Step $step ): StepResult {
		$bufferLevel = ob_get_level();
		ob_start();
		try {
			return $step->run();
		} catch ( \Throwable $throwable ) {
			return StepResult::crashed( $step->name(), $throwable );
		} finally {
			while ( ob_get_level() > $bufferLevel ) {
				ob_end_clean();
			}
		}
	}
}
