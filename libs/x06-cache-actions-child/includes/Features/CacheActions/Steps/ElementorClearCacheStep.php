<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Features\CacheActions\Steps;

use Elementor\Plugin;

/**
 * Elementor → Tools → "Clear Files & Data".
 */
final class ElementorClearCacheStep implements Step {

	public function name(): string {
		return 'elementor_clear_cache';
	}

	public function run(): StepResult {
		if ( ! class_exists( Plugin::class ) || ! isset( Plugin::$instance->files_manager ) ) {
			return StepResult::skipped( $this->name(), StepResult::PLUGIN_INACTIVE );
		}

		Plugin::$instance->files_manager->clear_cache();

		return StepResult::done( $this->name() );
	}
}
