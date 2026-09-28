<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Features\CacheActions;

use X06CacheActionsChild\Features\CacheActions\Steps\ElementorClearCacheStep;
use X06CacheActionsChild\Features\CacheActions\Steps\ElementorSyncLibraryStep;
use X06CacheActionsChild\Features\CacheActions\Steps\Step;
use X06CacheActionsChild\Features\CacheActions\Steps\WpfcDeleteCacheStep;

/**
 * Which steps each operation runs, in order.
 */
final class StepPlan {

	/** @var array<string, list<Step>> */
	private array $steps_by_operation;

	/**
	 * @param array<string, list<Step>> $stepsByOperation Steps keyed by operation.
	 */
	public function __construct( array $stepsByOperation ) {
		$this->steps_by_operation = $stepsByOperation;
	}

	/**
	 * Elementor runs before WP Fastest Cache: clearing Elementor's CSS while WPFC keeps
	 * serving cached HTML that links the deleted files leaves pages unstyled.
	 */
	public static function default(): self {
		return new self(
			array(
				Operation::CLEAR_CACHES          => array( new ElementorClearCacheStep(), new WpfcDeleteCacheStep( false ) ),
				Operation::CLEAR_CACHES_MINIFIED => array( new ElementorClearCacheStep(), new WpfcDeleteCacheStep( true ) ),
				Operation::SYNC_LIBRARY          => array( new ElementorSyncLibraryStep() ),
			)
		);
	}

	/**
	 * @return list<Step>
	 */
	public function steps_for( string $operation ): array {
		return $this->steps_by_operation[ $operation ] ?? array();
	}
}
