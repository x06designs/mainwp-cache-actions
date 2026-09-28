<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use X06CacheActionsChild\Features\CacheActions\CacheActionsModule;
use X06CacheActionsChild\Features\CacheActions\Operation;
use X06CacheActionsChild\Features\CacheActions\OperationRunner;
use X06CacheActionsChild\Features\CacheActions\StepPlan;
use X06CacheActionsChild\Features\CacheActions\Steps\StepResult;
use X06CacheActionsChild\Tests\Doubles\FakeStep;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class CacheActionsModuleTest extends TestCase {

	private FakeStep $step;

	private CacheActionsModule $module;

	protected function set_up(): void {
		parent::set_up();
		$this->step   = new FakeStep( 'step', static fn(): StepResult => StepResult::done( 'step' ) );
		$this->module = new CacheActionsModule(
			new OperationRunner( new StepPlan( array_fill_keys( Operation::ALL, array( $this->step ) ) ) )
		);
	}

	public function test_raises_the_time_limit_below_the_dashboard_timeout_for_its_operations(): void {
		Functions\expect( 'set_time_limit' )->once()->with( 90 )->andReturn( true );

		$this->module->handle( array(), array( 'x06_cache_op' => 'clear_caches' ) );
	}

	public function test_leaves_the_time_limit_alone_for_foreign_requests(): void {
		Functions\expect( 'set_time_limit' )->never();

		$this->module->handle( array(), array( 'action' => 'something_else' ) );
	}

	public function test_hooks_into_mainwp_child_extra_execution(): void {
		Filters\expectAdded( 'mainwp_child_extra_execution' )
			->once()
			->with( array( $this->module, 'handle' ), 10, 2 );

		$this->module->register();
	}

	/**
	 * @dataProvider foreign_requests
	 *
	 * @param mixed $post Request payload the filter receives.
	 */
	public function test_leaves_foreign_requests_untouched( $post ): void {
		$information = array( 'other_extension' => 'untouched' );

		$this->assertSame( $information, $this->module->handle( $information, $post ) );
		$this->assertSame( 0, $this->step->run_calls );
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public function foreign_requests(): array {
		return array(
			'no op key'     => array( array( 'action' => 'something_else' ) ),
			'unknown op'    => array( array( 'x06_cache_op' => 'drop_database' ) ),
			'op as array'   => array( array( 'x06_cache_op' => array( 'clear_caches' ) ) ),
			'post not array' => array( 'x06_cache_op=clear_caches' ),
		);
	}

	public function test_adds_the_result_under_its_own_key_and_keeps_other_keys(): void {
		$result = $this->module->handle(
			array( 'other_extension' => 'untouched' ),
			array( 'x06_cache_op' => 'sync_library' )
		);

		$this->assertSame( 'untouched', $result['other_extension'] );
		$this->assertSame( 'sync_library', $result['x06_cache_actions']['op'] );
		$this->assertSame( 1, $this->step->run_calls );
	}

	public function test_starts_from_an_empty_response_when_information_is_not_an_array(): void {
		$result = $this->module->handle( null, array( 'x06_cache_op' => 'clear_caches' ) );

		$this->assertSame( array( 'x06_cache_actions' ), array_keys( $result ) );
	}
}
