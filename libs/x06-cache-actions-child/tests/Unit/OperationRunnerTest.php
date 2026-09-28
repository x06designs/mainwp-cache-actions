<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Unit;

use RuntimeException;
use X06CacheActionsChild\Features\CacheActions\Operation;
use X06CacheActionsChild\Features\CacheActions\OperationRunner;
use X06CacheActionsChild\Features\CacheActions\StepPlan;
use X06CacheActionsChild\Features\CacheActions\Steps\StepResult;
use X06CacheActionsChild\Tests\Doubles\FakeStep;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class OperationRunnerTest extends TestCase {

	public function test_reports_operation_versions_and_step_results_in_order(): void {
		$first  = new FakeStep( 'first', static fn(): StepResult => StepResult::done( 'first' ) );
		$second = new FakeStep( 'second', static fn(): StepResult => StepResult::skipped( 'second', StepResult::PLUGIN_INACTIVE ) );

		$result = $this->runner_with( array( $first, $second ) )->run( Operation::CLEAR_CACHES );

		$this->assertSame(
			array(
				'op'                => 'clear_caches',
				'companion_version' => '0.1.0-test',
				'api_version'       => 1,
				'steps'             => array(
					array(
						'step'   => 'first',
						'status' => 'done',
						'detail' => null,
					),
					array(
						'step'   => 'second',
						'status' => 'skipped',
						'detail' => 'plugin_inactive',
					),
				),
			),
			$result
		);
	}

	public function test_keeps_running_after_a_failed_step(): void {
		$failing = new FakeStep( 'failing', static fn(): StepResult => StepResult::failed( 'failing', StepResult::DELETE_FAILED ) );
		$next    = new FakeStep( 'next', static fn(): StepResult => StepResult::done( 'next' ) );

		$result = $this->runner_with( array( $failing, $next ) )->run( Operation::CLEAR_CACHES );

		$this->assertSame( 1, $next->run_calls );
		$this->assertSame( 'failed', $result['steps'][0]['status'] );
		$this->assertSame( 'done', $result['steps'][1]['status'] );
	}

	public function test_turns_a_throwing_step_into_a_failure_and_keeps_running(): void {
		$throwing = new FakeStep(
			'throwing',
			static function (): StepResult {
				throw new RuntimeException( 'secret path /var/www/site' );
			}
		);
		$next     = new FakeStep( 'next', static fn(): StepResult => StepResult::done( 'next' ) );

		$result = $this->runner_with( array( $throwing, $next ) )->run( Operation::CLEAR_CACHES );

		$this->assertSame(
			array(
				'step'   => 'throwing',
				'status' => 'failed',
				'detail' => 'exception',
				'error'  => RuntimeException::class,
			),
			$result['steps'][0]
		);
		$this->assertSame( 1, $next->run_calls );
	}

	public function test_discards_output_printed_by_steps(): void {
		$noisy = new FakeStep(
			'noisy',
			static function (): StepResult {
				echo '<div class="notice">printed</div>';
				ob_start();
				echo 'unclosed buffer';
				return StepResult::done( 'noisy' );
			}
		);
		$level = ob_get_level();

		$this->runner_with( array( $noisy ) )->run( Operation::CLEAR_CACHES );

		$this->assertSame( $level, ob_get_level() );
		$this->expectOutputString( '' );
	}

	public function test_an_operation_without_a_plan_runs_no_steps(): void {
		$result = ( new OperationRunner( StepPlan::default() ) )->run( 'not_an_operation' );

		$this->assertSame( array(), $result['steps'] );
	}

	/**
	 * @param list<FakeStep> $steps Steps for the clear_caches operation.
	 */
	private function runner_with( array $steps ): OperationRunner {
		return new OperationRunner( new StepPlan( array( Operation::CLEAR_CACHES => $steps ) ) );
	}
}
