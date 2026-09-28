<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Features\CacheActions\Steps;

interface Step {

	public function name(): string;

	public function run(): StepResult;
}
