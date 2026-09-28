<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Doubles;

use X06CacheActionsChild\Features\CacheActions\Steps\Step;
use X06CacheActionsChild\Features\CacheActions\Steps\StepResult;

final class FakeStep implements Step {

	public int $run_calls = 0;

	private string $name;

	/** @var callable(): StepResult */
	private $behaviour;

	/**
	 * @param callable(): StepResult $behaviour Invoked on run().
	 */
	public function __construct( string $name, callable $behaviour ) {
		$this->name      = $name;
		$this->behaviour = $behaviour;
	}

	public function name(): string {
		return $this->name;
	}

	public function run(): StepResult {
		++$this->run_calls;
		return ( $this->behaviour )();
	}
}
