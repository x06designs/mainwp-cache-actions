<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Features\CacheActions\Steps;

/**
 * WP Fastest Cache → "Delete Cache" / "Delete Cache and Minified CSS/JS".
 *
 * deleteCache() returns nothing and reports failure only through admin notices, which are
 * not loaded on MainWP requests. WPFC fires `wpfc_delete_cache` only after a successful
 * purge, so a new firing of that action is the success signal.
 */
final class WpfcDeleteCacheStep implements Step {

	private const SUCCESS_ACTION = 'wpfc_delete_cache';

	private bool $is_minified_included;

	public function __construct( bool $isMinifiedIncluded ) {
		$this->is_minified_included = $isMinifiedIncluded;
	}

	public function name(): string {
		return $this->is_minified_included ? 'wpfc_delete_cache_and_minified' : 'wpfc_delete_cache';
	}

	public function run(): StepResult {
		$wpfc = $GLOBALS['wp_fastest_cache'] ?? null;
		if ( ! is_object( $wpfc ) || ! method_exists( $wpfc, 'deleteCache' ) ) {
			return StepResult::skipped( $this->name(), StepResult::PLUGIN_INACTIVE );
		}

		$firedBefore = did_action( self::SUCCESS_ACTION );
		$wpfc->deleteCache( $this->is_minified_included );

		if ( did_action( self::SUCCESS_ACTION ) <= $firedBefore ) {
			return StepResult::failed( $this->name(), StepResult::DELETE_FAILED );
		}

		return StepResult::done( $this->name() );
	}
}
